$(document).ready(function () {
  new DateInput("#voucher_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

  new DateInput("#cheque_date_input", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

  $("#voucher_date").on("blur", function () {
    let day = getDayFromDate($(this).val());
    let invDate = formatDateToYMD($(this).val());
    if (!invDate) return;

    $("#weekday").val(day);
    if (!isWithinFY(invDate, FINANCIAL_YEAR_START, FINANCIAL_YEAR_END)) {
      showToast(
        "error",
        `Date must be within Financial Year:<br>(${formatDateToDMY(FINANCIAL_YEAR_START)} to ${formatDateToDMY(FINANCIAL_YEAR_END)})`,
        9000,
      );
      $(this).val("");
    }
  });

   $("#cheque_date_input").on("blur", function () {
    let day = getDayFromDate($(this).val());
    let invDate = formatDateToYMD($(this).val());
    if (!invDate) return;
    if (!isWithinFY(invDate, FINANCIAL_YEAR_START, FINANCIAL_YEAR_END)) {
      showToast(
        "error",
        `Date must be within Financial Year:<br>(${formatDateToDMY(FINANCIAL_YEAR_START)} to ${formatDateToDMY(FINANCIAL_YEAR_END)})`,
        9000,
      );
      $(this).val("");
    }
  });

  const formMode = $("#form_mode").val();

  if (formMode === "edit") {
    $("#voucher_id").select2({ theme: "bootstrap-5" });
    setTimeout(() => {
      $("#voucher_id").focus();
    }, 50);
  } else {
    $("#voucher_date").focus();
  }

  bindTableEvent();
});

function bindTableEvent() {
  preloadAccounts();
  window.voucherTableManager = new VoucherTableManager(
    "#payment_voucher_table",
  );
}

let accountMasterData = [];

async function preloadAccounts() {
  showLoader("Loading accounts, please wait...");
  try {
    const { data } = await $.ajax({
      url: ledgerAccountsUrl,
      method: "GET",
      dataType: "json",
    });

    accountMasterData = data.map((acc) => ({
      id: acc.id,
      text: acc.name,
      original_name: acc.original_name,
      code: acc.code,
      is_billwise: acc.is_billwise,
      is_bank_account: acc.is_bank_account,
      is_cash_account: acc.is_cash_account,
      is_party_account: acc.is_party_account,
      payee_category: acc.payee_category,
      tds_category: acc.tds_category,
      allow_tds: acc.allow_tds,
      tds_category_detail: acc.tds_category_detail,
      pan_no: acc.pan_no,
      gst_type: acc.gst_type,
      tax_category_id: acc.tax_category_id,
      rcm_nature: acc.rcm_nature,
      itc_eligibility: acc.itc_eligibility,
      gst_no: acc.gst_no,
      type: acc.type,
      hsn_code: acc.hsn_code,
    }));

    initSelect2ForAllRows();
  } catch (e) {
    console.error("Failed to load accounts", e);
  } finally {
    hideLoader();
  }
}

function initSelect2ForAllRows() {
  $(".account-id").select2({
    placeholder: " ",
    allowClear: true,
    theme: "bootstrap-5",
    width: "100%",
    minimumInputLength: 0,
    selectOnClose: false,
    templateSelection: function (data) {
      if (!data || data.id === undefined || data.id === "__NULL__") return "";
      return data.text;
    },
    ajax: {
      transport: function (params, success) {
        let term = (params.data.term || "").toLowerCase();
        let results = accountMasterData.filter((a) =>
          a.text.toLowerCase().includes(term),
        );
        results.unshift({ id: "__NULL__", text: "— None —" });
        success({ results });
      },
      cache: true,
    },
  });

  $(".account-id").on("select2:select", function (e) {
    if (e.params.data.id === "__NULL__") {
      $(this).val("").trigger("change");
    }
  });

  $(".account-id").on("select2:open", function () {
    let selectEl = $(this);
    var searchInput = selectEl
      .data("select2")
      .$dropdown.find(".select2-search__field");
    searchInput.on("keydown", function (event) {
      if (event.which === 13) {
        selectEl.select2("close");
        moveFocusToNextField(selectEl);
      }
    });
  });
}

class VoucherTableManager {
  constructor(tableSelector) {
    this.table = $(tableSelector);
    this.dataStore = new VoucherDataStore();
    this.rowManager = new VoucherRowManager(tableSelector, this.dataStore);
    this.calculationService = new VoucherCalculationService(this.rowManager);
    this.ledgerService = new LedgerService(this.dataStore);
    this.referenceManager = new ReferenceManager(
      this.dataStore,
      this.rowManager,
    );

    this.initEvents();
  }

  initEvents() {
    this.table.on("keydown", ".dr-cr", (e) => this.handleDrCrKey(e));
    this.table.on("focus", ".dr-cr", (e) => this.handleDrCrFocus(e));
    this.table.on("focus", ".debit-amount, .credit-amount", (e) =>
      this.autoBalance(e),
    );
    this.table.on("blur", ".dr-cr", (e) => this.handleSelect2Open(e));
    this.table.on("blur", ".debit-amount, .credit-amount", (e) => {
      const $el = $(e.target);
      const val = parseFloat($el.val().replace(/,/g, "")) || 0;
      $el.val(val > 0 ? val.toFixed(2) : "");
      this.referenceManager.open(this.rowManager.getRow(e.target));
      this.updateTotals();
    });
    this.table.on("change", ".account-id", (e) => this.handleLedgerChange(e));
    $("#payment_voucher_form").on("submit", (e) => this.handleStore(e));
    this.initChequeModal();
  }

  initChequeModal() {
    const $modal = $("#cheque_details_modal");

    // Initialize Select2 once — dropdownParent keeps z-index correct inside modal
    $("#cheque_ac_pay").select2({
      theme: "bootstrap-5",
      // minimumResultsForSearch: Infinity,
      dropdownParent: $modal,
    });
    $("#cheque_rtgs_yn").select2({
      theme: "bootstrap-5",
      // minimumResultsForSearch: Infinity,
      dropdownParent: $modal,
    });

    // Enter key inside Select2 closes dropdown and moves focus forward
    const bindSelect2Enter = (id) => {
      $("#" + id).on("select2:open", function () {
        const $sel = $(this);
        $sel
          .data("select2")
          .$dropdown.find(".select2-search__field")
          .on("keydown", function (e) {
            if (e.which === 13) {
              $sel.select2("close");
              moveFocusToNextField($sel);
            }
          });
      });
    };
    bindSelect2Enter("cheque_ac_pay");
    bindSelect2Enter("cheque_rtgs_yn");

    // Focus first field after modal is fully visible
    $modal.on("shown.bs.modal", () => {
      $("#cheque_ac_pay").select2("open");
    });

    // RTGS toggle: update Name Of Cheque
    $("#cheque_rtgs_yn").on("change", () => {
      const bank = this._chequeBankClean || "";
      const party = this._chequePartyName || "";
      const isRtgs = $("#cheque_rtgs_yn").val() === "Y";
      $("#cheque_payee_name").val(
        isRtgs && bank ? `${bank} RTGS FOR ${party}` : party,
      );
    });

    // Action buttons
    $("#btn_cheque_voucher_only").on("click", () =>
      this.onChequeAction("voucher_only"),
    );
    $("#btn_cheque_print").on("click", () => {
      if (!this.validateChequeModal()) return;
      this.onChequeAction("print_cheque");
    });
    $("#btn_cheque_pass_rtgs").on("click", () =>
      this.onChequeAction("pass_rtgs"),
    );
    $("#btn_send_approval").on("click", () => this.onChequeAction("approval"));
    $("#btn_cheque_cancel, #cheque_modal_x_btn").on("click", () => {
      $modal.modal("hide");
      const isEdit = $("#form_mode").val() === "edit";
      $("#save_btn")
        .prop("disabled", false)
        .html(
          `<i class="fa-solid fa-floppy-disk me-1"></i> ${isEdit ? "Update" : "Save"}`,
        );
    });
  }

  validateChequeModal() {
    const payeeName = $("#cheque_payee_name").val()?.trim();
    const chequeDate = $("#cheque_date_input").val()?.trim();
    const chequeAlphaNo = $("#cheque_alpha_number").val()?.trim();
    const chequeNo = $("#cheque_number").val()?.trim();
    const amount = parseFloat($("#cheque_total_display").val()) || 0;

    if (!payeeName) {
      showToast("error", "Cheque name (payee) is required.");
      document.getElementById("cheque_payee_name")?.focus();
      return false;
    }
    if (!chequeDate) {
      showToast("error", "Cheque date is required.");
      document.getElementById("cheque_date_input")?.focus();
      return false;
    }
    if (!chequeAlphaNo) {
      showToast("error", "Cheque alpha number is required.");
      document.getElementById("cheque_alpha_number")?.focus();
      return false;
    }
    if (!chequeNo) {
      showToast("error", "Cheque number is required.");
      document.getElementById("cheque_number")?.focus();
      return false;
    }
    if (amount <= 0) {
      showToast("error", "Cheque amount must be greater than zero.");
      document.getElementById("cheque_total_display")?.focus();
      return false;
    }

    return true;
  }

  buildPayload() {
    const voucherDate = formatDateToYMD($("#voucher_date").val());

    const rows = [];
    this.rowManager.getAllRows().each((index, tr) => {
      const $tr = $(tr);
      const accountId = $tr.find(".account-id").val();
      const debit =
        parseFloat($tr.find(".debit-amount").val().replace(/,/g, "") || 0) || 0;
      const credit =
        parseFloat($tr.find(".credit-amount").val().replace(/,/g, "") || 0) ||
        0;

      if (!accountId || (debit === 0 && credit === 0)) return;

      const refs = this.dataStore.getReferences(index);
      const po   = this.dataStore.getPurchaseOrder(index);
      rows.push({
        account_id: accountId,
        dr_cr: $tr.find(".dr-cr").val(),
        debit_amount: debit,
        credit_amount: credit,
        purchase_order_id: po ? po.id : null,
        references: refs.map((ref) => ({
          ref_id: ref.refId || null,
          method: ref.method,
          ref_number: ref.refName,
          ref_amount: parseFloat(ref.refAmt) || 0,
          purchase_order_id: po ? po.id : null,
          purchase_order_number: po ? (po.order_serial || po.order_number || null) : null,
        })),
      });
    });

    return {
      uuid: $("#uuid").val(),
      voucher_date: voucherDate,
      voucher_serial: $("#voucher_serial").val(),
      narration: $("#voucher_narration").val(),
      rows: rows,
    };
  }

  validatePayload(payload) {
    if (!payload.voucher_date) {
      showToast("error", "Voucher date is required.");
      $("#voucher_date").focus();
      return false;
    }

    if (payload.rows.length === 0) {
      showToast("error", "Please enter at least one voucher row.");
      return false;
    }

    const bankOnCr = payload.rows.some((row) => {
      const acc = accountMasterData.find((a) => a.id == row.account_id);
      return acc && acc.is_bank_account && row.credit_amount > 0;
    });
    if (bankOnCr && payload.rows.filter((r) => r.debit_amount > 0).length > 1) {
      showToast(
        "error",
        "When a bank account is on the CR side, only <b>one DR account</b> is allowed.",
        7000,
      );
      return false;
    }

    return true;
  }

  hasBankCrDrViolation() {
    let bankOnCr = false;
    let drCount = 0;

    this.rowManager.getAllRows().each((_, tr) => {
      const $tr = $(tr);
      const accountId = $tr.find(".account-id").val();
      if (!accountId) return;

      const acc = accountMasterData.find((a) => a.id == accountId);
      if (!acc) return;

      const drCr = $tr.find(".dr-cr").val()?.toUpperCase();
      const debit = parseFloat($tr.find(".debit-amount").val()?.replace(/,/g, "") || 0) || 0;
      const credit = parseFloat($tr.find(".credit-amount").val()?.replace(/,/g, "") || 0) || 0;

      const isCr = drCr === "CR" || (!drCr && credit > 0 && debit === 0);
      const isDr = drCr === "DR" || (!drCr && debit > 0 && credit === 0);

      if (acc.is_bank_account && isCr) bankOnCr = true;
      if (isDr) drCount++;
    });

    return bankOnCr && drCount > 1;
  }

  hasBankOnCrSide() {
    return this.rowManager.getAllRows().toArray().some((tr) => {
      const $tr = $(tr);
      const accountId = $tr.find(".account-id").val();
      const credit =
        parseFloat($tr.find(".credit-amount").val()?.replace(/,/g, "") || 0) ||
        0;
      if (!accountId || credit <= 0) return false;
      const acc = accountMasterData.find((a) => a.id == accountId);
      return acc && acc.is_bank_account;
    });
  }

  async handleStore(e) {
    e.preventDefault();

    const payload = this.buildPayload();
    if (!this.validatePayload(payload)) return;

    // CR is cash only — no cheque modal needed, submit directly as cash payment
    if (!this.hasBankOnCrSide()) {
      payload.cheque_action   = "voucher_only";
      payload.bank_account_id = null;
      payload.party_account_id = null;
      payload.bank_amount     = 0;
      await this.submitVoucher(payload);
      return;
    }

    this.showChequeModal(payload);
  }

  showChequeModal(payload) {
    this._pendingPayload = payload;

    let bankName = "";
    let chequeAmount = 0;
    let bankAccountId = null;
    // Separate tracking: prefer a party-group DR row; fall back to any non-bank DR
    let partyRow    = null; // { name, id } — first DR with is_party_account = true
    let fallbackRow = null; // { name, id } — first non-bank, non-cash DR (e.g. GST payable)

    this.rowManager.getAllRows().each((_i, tr) => {
      const $tr = $(tr);
      const accountId = $tr.find(".account-id").val();
      if (!accountId) return;

      const credit =
        parseFloat($tr.find(".credit-amount").val()?.replace(/,/g, "") || 0) ||
        0;
      const debit =
        parseFloat($tr.find(".debit-amount").val()?.replace(/,/g, "") || 0) ||
        0;
      const acc = accountMasterData.find((a) => a.id == accountId);
      if (!acc) return;

      // Bank account identified by is_bank_account flag — its credit is the cheque amount
      if (acc.is_bank_account && credit > 0 && !bankName) {
        // Strip trailing code suffix e.g. "AXIS BANK LTD-5352" → "AXIS BANK LTD"
        bankName = (acc.original_name || acc.text).replace(/-\d+$/, "").trim();
        chequeAmount = credit;
        bankAccountId = accountId;
      }

      if (!acc.is_bank_account && !acc.is_cash_account && debit > 0) {
        if (acc.is_party_account && !partyRow) {
          partyRow = { name: acc.original_name || acc.text, id: accountId };
        } else if (!fallbackRow) {
          fallbackRow = { name: acc.original_name || acc.text, id: accountId };
        }
      }

      // Cash account in DR → payee is SELF (lowest priority fallback)
      if (acc.is_cash_account && debit > 0 && !partyRow && !fallbackRow) {
        fallbackRow = { name: "SELF", id: accountId };
      }
    });

    // Party account takes priority; for government/tax payments use the DR liability account
    const chosen = partyRow || fallbackRow || {};
    const partyName = chosen.name || "";
    const partyAccountId = chosen.id || null;

    const now = new Date();
    const hh = String(now.getHours()).padStart(2, "0");
    const mm = String(now.getMinutes()).padStart(2, "0");

    // Store for RTGS change handler and cheque payload
    this._chequeBankClean = bankName;
    this._chequePartyName = partyName;
    this._chequeBankAccountId = bankAccountId;
    this._chequePartyAccountId = partyAccountId;
    this._chequeBankAmount = chequeAmount;

    $("#cheque_date_input").val($("#voucher_date").val());
    $("#cheque_time_input").val(`${hh}:${mm}`);
    $("#cheque_payee_name").val(partyName);
    $("#cheque_bank_name").val(bankName);
    $("#cheque_alpha_number").val("");
    $("#cheque_number").val("");
    $("#cheque_ac_pay").val("Y").trigger("change");
    $("#cheque_rtgs_yn").val("N").trigger("change");
    $("#cheque_total_display").val(
      chequeAmount > 0 ? chequeAmount.toFixed(2) : "0.00",
    );

    const $btn = $("#save_btn");
    $btn
      .prop("disabled", true)
      .html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Processing...');

    $("#cheque_details_modal")
      .modal({ backdrop: "static", keyboard: false })
      .modal("show");
  }

  async onChequeAction(action) {
    const payload = this._pendingPayload;
    if (!payload) return;

    payload.cheque_action = action;
    payload.bank_account_id = this._chequeBankAccountId || null;
    payload.party_account_id = this._chequePartyAccountId || null;
    payload.bank_amount = this._chequeBankAmount || 0;

    if (action == "print_cheque") {
      const isRtgs = action === "pass_rtgs";
      payload.cheque = {
        ac_pay: $("#cheque_ac_pay").val(),
        rtgs: $("#cheque_rtgs_yn").val(),
        cheque_name: $("#cheque_payee_name").val(),
        cheque_time: $("#cheque_time_input").val(),
        bank_name: $("#cheque_bank_name").val(),
        amount: parseFloat($("#cheque_total_display").val()) || 0,
        cheque_print: action === "print_cheque",
        mode: action,
        // cheque-specific — omitted for RTGS (no physical cheque)
        ...(!isRtgs && {
          cheque_date: formatDateToYMD($("#cheque_date_input").val()),
          cheque_alpha_number: $("#cheque_alpha_number").val(),
          cheque_no: $("#cheque_number").val(),
        }),
      };
    }

    $("#cheque_details_modal").modal("hide");

    await this.submitVoucher(payload);
  }

  async submitVoucher(payload) {
    const $btn = $("#save_btn");
    const isEdit = $("#form_mode").val() === "edit";
    $btn
      .prop("disabled", true)
      .html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

    const url = isEdit
      ? `${updatePaymentVoucher}/${$("#voucher_id").val()}`
      : storePaymentVoucher;
    const method = isEdit ? "PUT" : "POST";

    try {
      const response = await $.ajax({
        url,
        method,
        data: JSON.stringify(payload),
        contentType: "application/json",
        dataType: "json",
        headers: {
          "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
      });

      const data = response.data ?? {};
      const serial = data.voucher?.voucher_serial ?? "";
      const chequeAction = payload.cheque_action ?? "voucher_only";
      const isChequePrint = data.is_cheque_print ?? false;
      const isRtgsForm = data.is_rtgs_form_print ?? false;

      const actionLabel =
        {
          print_cheque: isRtgsForm
            ? "Cheque (RTGS) ready to print."
            : "Cheque ready to print.",
          pass_rtgs: "Passed to RTGS screen.",
          approval: "Sent for approval.",
        }[chequeAction] ?? "";

      const swalOpts = {
        icon: "success",
        title: "Success",
        html: `Voucher <b class="text-primary">${serial}</b> ${isEdit ? "updated" : "created"} successfully.${actionLabel ? `<br><small class="text-muted">${actionLabel}</small>` : ""}`,
        allowOutsideClick: false,
        allowEscapeKey: false,
        confirmButtonText: "OK",
        confirmButtonColor: "#28a745",
      };

      if (isChequePrint && data.cheque_print_html) {
        const blob = new Blob([data.cheque_print_html], { type: "text/html" });
        const url = URL.createObjectURL(blob);
        window.open(url, "_blank");
      }

      if (isRtgsForm && data.is_rtgs_form_print) {
        swalOpts.showDenyButton = true;
        swalOpts.denyButtonText =
          '<i class="fa-solid fa-print me-1"></i> Print RTGS Form';
        swalOpts.denyButtonColor = "#0d6efd";
      }

      Swal.fire(swalOpts).then((result) => {
        if (result.isDenied && isRtgsForm && data.is_rtgs_form_print) {
          const blob = new Blob([data.rtgs_form_html], { type: "text/html" });
          const url = URL.createObjectURL(blob);
          window.open(url, "_blank");
        }

        location.reload();
      });
    } catch (err) {
      const msg = err?.responseJSON?.errors || err?.responseJSON?.message || "Something went wrong. Please try again.";
      showToast("error", msg, 7000);
    } finally {
      $btn
        .prop("disabled", false)
        .html(
          `<i class="fa-solid fa-floppy-disk me-1"></i> ${isEdit ? "Update" : "Save"}`,
        );
    }
  }

  applyDrCrMode(drCrEl, type, row) {
    const $row = row || this.rowManager.getRow(drCrEl);
    const drInput = $row.find(".debit-amount");
    const crInput = $row.find(".credit-amount");
    const isDR = type === "dr";
    drCrEl.value = type.toUpperCase();
    drInput.prop("readonly", !isDR).toggleClass("cursor-not-allowed", !isDR);
    crInput.prop("readonly", isDR).toggleClass("cursor-not-allowed", isDR);
  }

  predictDrCr() {
    const totals = this.calculationService.calculateTotals();
    if (totals.isBalanced && totals.debit > 0) return null; // already balanced → no prediction
    if (totals.debit > totals.credit) return "cr"; // DR heavy → need CR
    if (totals.credit > totals.debit) return "dr"; // CR heavy → need DR
    return "dr"; // both zero → first row defaults DR
  }

  handleDrCrFocus(e) {
    const row = this.rowManager.getRow(e.target);
    const amounts = this.rowManager.getAmounts(row);
    if (amounts.debit > 0 || amounts.credit > 0) return;

    const predicted = this.predictDrCr();
    if (!predicted) return;

    this.applyDrCrMode(e.target, predicted, row);
  }

  handleDrCrKey(e) {
    const key = e.key?.toLowerCase();
    if (key === "enter") return;

    const row = this.rowManager.getRow(e.target);
    const drInput = row.find(".debit-amount");
    const crInput = row.find(".credit-amount");
    const currentDr = drInput.val();
    const currentCr = crInput.val();

    if (key === "d") {
      e.preventDefault();
      this.applyDrCrMode(e.target, "dr", row);
      if (currentCr) drInput.val(currentCr);
      crInput.val("");
    } else if (key === "c") {
      e.preventDefault();
      this.applyDrCrMode(e.target, "cr", row);
      if (currentDr) crInput.val(currentDr);
      drInput.val("");
    } else {
      e.preventDefault();
      e.target.value = "";
    }
  }

  autoBalance(e) {
    const row = this.rowManager.getRow(e.target);
    const type = $(e.target).hasClass("debit-amount") ? "dr" : "cr";
    if (e.target.value) return;
    const diff = this.calculationService.calculateDifference(type);
    if (diff > 0) e.target.value = diff.toFixed(2);
  }

  updateTotals() {
    const totals = this.calculationService.calculateTotals();
    $("#total_debit").val(formatIndianNumber(totals.debit.toFixed(2)));
    $("#total_credit").val(formatIndianNumber(totals.credit.toFixed(2)));
  }

  handleSelect2Open(e) {
    const $row = $(e.target).closest("tr");
    const debitAmount =
      $row.find(".debit-amount").val()?.replace(/,/g, "") || 0;
    const creditAmount =
      $row.find(".credit-amount").val()?.replace(/,/g, "") || 0;
    const totals = this.calculationService.calculateTotals();

    if (
      totals.isBalanced &&
      totals.debit > 0 &&
      debitAmount == 0 &&
      creditAmount == 0
    ) {
      setTimeout(() => {
        $("#voucher_narration").focus();
      }, 10);
    }
  }

  async handleLedgerChange(e) {
    const row = this.rowManager.getRow(e.target);
    const rowIndex = this.rowManager.getRowIndex(row);
    const accountId = $(e.target).val();

    this.dataStore.clearReferences(rowIndex);
    this.referenceManager.refreshPreview();

    if (!accountId) {
      this.dataStore.setRowData(rowIndex, {});
      return;
    } else {
      let data = accountMasterData.find((a) => a.id == accountId);
      this.dataStore.setRowData(rowIndex, data);
    }

    if (this.hasBankCrDrViolation()) {
      $(e.target).val(null).trigger("change");
      this.dataStore.setRowData(rowIndex, {});
      showToast(
        "error",
        "When a bank account is on the CR side, only <b>one DR account</b> is allowed.",
        6000,
      );
      return;
    }

    const loader = row.find(".ledger-balance-loader");
    loader.removeClass("d-none");

    try {
      const balance = await this.ledgerService.fetchBalance(accountId);
      let formattedBalance = this.ledgerService.formatBalance(balance);
      row.find(".ledger-balance").val(formattedBalance);
    } catch (error) {
      console.error(error);
    } finally {
      loader.addClass("d-none");
    }
  }
}

class ReferenceManager {
  constructor(dataStore, rowManager) {
    this.dataStore = dataStore;
    this.rowManager = rowManager;
    this.currentRowIndex = null;
    this._currentPO = null;

    this.initModal();
    this.initOffcanvas();
    this.initPOModal();
  }

  initModal() {
    this.modalEl = document.getElementById("reference_modal");
    this.$modal = $(this.modalEl);
    this.refTableBody = this.$modal.find(".ref-table-body");
    this.refAccountName = this.$modal.find("#ref_account_name");
    this.refAmount = this.$modal.find("#ref_amount");
    this.refPopupError = this.$modal.find(".ref-error");

    this.bindModalEvents();
  }

  bindModalEvents() {
    this.$modal.on("submit", (e) => this.saveReferences(e));
    $("#all_ref_clear").on("click", () => this.clearAllReferences());
  }

  initPOModal() {
    this.$poModal = $("#pending_po_modal");

    $("#btn_attach_po").on("click", () => this.openPOModal());
    $("#btn_attach_po_confirm").on("click", () => this.attachSelectedPO());

    // Single-select: row click selects its checkbox and deselects others
    $("#pending_po_table").on("click", "tbody tr", function (e) {
      if ($(e.target).is("input[type=checkbox]")) return;
      const $cb = $(this).find(".po-select");
      const wasChecked = $cb.prop("checked");
      $("#pending_po_table tbody .po-select").prop("checked", false);
      $("#pending_po_table tbody tr").removeClass("po-row-active");
      if (!wasChecked) {
        $cb.prop("checked", true);
        $(this).addClass("po-row-active");
      }
    });

    // Checkbox change enforces single-select
    $("#pending_po_table").on("change", ".po-select", function () {
      $("#pending_po_table tbody .po-select").not(this).prop("checked", false);
      $("#pending_po_table tbody tr").removeClass("po-row-active");
      if ($(this).prop("checked")) $(this).closest("tr").addClass("po-row-active");
    });

    // Client-side search filter
    $("#pending_po_search").on("input", () => this.filterPOTable());

    // When PO modal closes, return focus to the first ref-number in the ref modal
    this.$poModal.on("hidden.bs.modal", () => {
      setTimeout(() => {
        this.refTableBody.find(".ref-number").first().focus();
      }, 50);
    });
  }

  open(row) {
    if (!this.rowManager.isReferenceRequired(row)) return;
    if ($("body").hasClass("modal-open")) return;

    this.currentRowIndex = this.rowManager.getRowIndex(row);
    const amounts = this.rowManager.getAmounts(row);
    const transactionType = this.rowManager.getTransactionType(row);
    const rowAmount = transactionType === "DR" ? amounts.debit : amounts.credit;

    if (!rowAmount) return;

    if (typeof showLoader === "function")
      showLoader("Loading Reference Form...");

    this.clear();
    const formattedAmount =
      typeof formatIndianNumber === "function"
        ? formatIndianNumber(rowAmount)
        : rowAmount;
    this.refAmount.text(
      `${formattedAmount} ${transactionType === "DR" ? "Dr" : "Cr"}`,
    );
    this.refAccountName.text(
      row.find(".account-id option:selected").text() || "",
    );

    const existingRefs = this.dataStore.getReferences(this.currentRowIndex);
    this._currentPO = this.dataStore.getPurchaseOrder(this.currentRowIndex);
    this.renderPODisplay();

    if (existingRefs.length > 0) {
      this.loadReferences(existingRefs, transactionType);
    } else {
      this.loadNewReference(rowAmount, transactionType);
    }

    this.$modal.modal({ backdrop: "static", keyboard: false }).modal("show");

    setTimeout(() => {
      this.refTableBody.find("tr").first().find(".ref-number").focus();
      $(".ref-method").select2({ theme: "bootstrap-5" });
    }, 150);

    if (typeof hideLoader === "function") hideLoader();
    this.calculateTotals();
  }

  loadReferences(references, transactionType) {
    this.refTableBody.html("");
    references.forEach((ref, i) => {
      const newRow = this.createRow(i + 1, ref, transactionType);
      this.refTableBody.append(newRow);
    });
  }

  loadNewReference(amount, transactionType) {
    const first = this.refTableBody.find("tr").first();
    first.find(".ref-method").val("advance");
    first.find(".ref-number").val("ONAC");
    first.find(".ref-amount").val(amount.toFixed(2));
    first.find(".transaction-type").val(transactionType === "DR" ? "Dr" : "Cr");
  }

  createRow(sno, data = {}, transactionType = "") {
    const row = $(this.getRowTemplate());
    row.find(".ref-sno").text(sno);
    row.find(".ref-id").val(data.refId || "");
    row.find(".ref-method").val(data.method || "advance");
    row.find(".ref-number").val(data.refName || "");
    row.find(".ref-amount").val(Number(data.refAmt || 0).toFixed(2));
    row.find(".transaction-type").val(transactionType);

    $(".ref-method").select2({ theme: "bootstrap-5" }).trigger("change");

    if (data.method != "new_ref") {
      row.find(".ref-method").prop("disabled", true);
    }

    return row;
  }

  getRowTemplate() {
    return `
      <tr class="ref-row">
        <td class="ref-sno text-center">1</td>
        <td>
          <input type="hidden" class="ref-id" value="">
          <select class="ref-method form-select custom-select2">
            <option value="advance">On Advance</option>
            <option value="adjustment">Adjustment</option>
          </select>
        </td>
        <td><input type="text" class="form-control ref-number"></td>
        <td><input type="text" class="form-control ref-amount text-end only-number"></td>
        <td><input type="text" class="form-control transaction-type text-center" disabled></td>
      </tr>
    `;
  }

  clear() {
    this.refPopupError.text("");
    this.refTableBody.find(".ref-row:not(:first)").remove();
    const first = this.refTableBody.find(".ref-row").first();
    first.find("input").val("");
    first.find(".ref-method").val("advance").trigger("change");
  }

  clearAllReferences() {
    if (this.currentRowIndex === null) return;

    this.dataStore.setReferences(this.currentRowIndex, []);
    this.clear();

    const row = this.rowManager.getAllRows().eq(this.currentRowIndex);
    const amounts = this.rowManager.getAmounts(row);
    const transactionType = this.rowManager.getTransactionType(row);
    const rowAmount = transactionType === "DR" ? amounts.debit : amounts.credit;

    if (rowAmount) this.loadNewReference(rowAmount, transactionType);

    this.calculateTotals();
    this.refreshPreview();
    showToast("success", "All references cleared successfully.");
  }

  saveReferences(e) {
    e.preventDefault();

    const refs = [];
    let drTotal = 0;
    let crTotal = 0;
    let hasError = false;

    this.refTableBody.find(".ref-row").each(function () {
      const refName = $(this).find(".ref-number").val().trim();

      if (!refName) {
        hasError = true;
        showToast("error", "Please enter a reference No.");
        $(this).find(".ref-number").focus();
        return false;
      }

      const refAmount = parseFloat($(this).find(".ref-amount").val()) || 0;
      const refType = $(this).find(".transaction-type").val() || "";
      const typeUpper = refType.toUpperCase();

      refs.push({
        refId: $(this).find(".ref-id").val(),
        refName: refName,
        refAmt: refAmount,
        method: $(this).find(".ref-method").val(),
        transactionType: refType,
      });

      if (typeUpper === "DR") drTotal += refAmount;
      else if (typeUpper === "CR") crTotal += refAmount;
    });

    if (hasError) return;

    const row = this.rowManager.getAllRows().eq(this.currentRowIndex);
    const amounts = this.rowManager.getAmounts(row);
    const transactionType = this.rowManager
      .getTransactionType(row)
      .toUpperCase();
    const requiredAmount =
      transactionType === "DR" ? amounts.debit : amounts.credit;

    const netAmount = drTotal - crTotal;
    const totalAllocated = Math.abs(netAmount);
    const calculatedType = netAmount >= 0 ? "DR" : "CR";

    if (
      totalAllocated.toFixed(2) !== requiredAmount.toFixed(2) ||
      calculatedType !== transactionType
    ) {
      this.refPopupError.text(
        `Allocated: ${totalAllocated.toFixed(2)} ${calculatedType === "DR" ? "Dr" : "Cr"} | Required: ${requiredAmount.toFixed(2)} ${transactionType === "DR" ? "Dr" : "Cr"}`,
      );
      return;
    }

    this.dataStore.setReferences(this.currentRowIndex, refs);
    this.dataStore.setPurchaseOrder(this.currentRowIndex, this._currentPO);
    this.refreshPreview();
    this.$modal.modal("hide");

    let nextIndex = this.currentRowIndex + 1;
    $(`tr[data-index="${nextIndex}"]`).find(".dr-cr").focus();
  }

  async openPOModal() {
    const row = this.rowManager.getAllRows().eq(this.currentRowIndex);
    const accountId = this.rowManager.getAccountId(row);

    const $body = $("#pending_po_table_body");
    $body.find("tr:not(#po_loader_row):not(#po_no_data_row)").remove();
    $("#po_no_data_row").addClass("d-none");
    $("#po_loader_row").removeClass("d-none");
    $("#pending_po_search").val("");

    this.$poModal.modal({ backdrop: true, keyboard: true }).modal("show");

    try {
      const res = await $.ajax({
        url: pendingPurchaseOrderUrl,
        method: "GET",
        data: { account_id: accountId },
      });
      const orders = res.data || [];
      $("#po_loader_row").addClass("d-none");

      if (orders.length === 0) {
        $("#po_no_data_row").removeClass("d-none");
      } else {
        const html = orders.map((po) => this.createPORow(po)).join("");
        $body.find("tr:not(#po_loader_row):not(#po_no_data_row)").remove();
        $body.append(html);

        if (this._currentPO) {
          const $sel = $body.find(`tr[data-po-id="${this._currentPO.id}"]`);
          $sel.find(".po-select").prop("checked", true);
          $sel.addClass("po-row-active");
        }
      }
    } catch (e) {
      $("#po_loader_row").addClass("d-none");
      showToast("error", "Failed to load purchase orders.");
    }
  }

  createPORow(po) {
    const fmt = (d) =>
      d
        ? typeof formatYMDtoDMY === "function"
          ? formatYMDtoDMY(d)
          : d
        : "-";
    return `
      <tr data-po-id="${po.id}" data-order-number="${po.order_number || ''}" data-order-serial="${po.order_serial || ''}">
        <td class="text-center">
          <input type="checkbox" class="form-check-input border border-1 border-dark-subtle po-select" value="${po.id}">
        </td>
        <td class="text-center">${po.order_serial || "-"}</td>
        <td class="text-center">${po.order_number || "-"}</td>
        <td class="text-center">${fmt(po.order_date)}</td>
        <td class="text-end">${po.ordered_qty ?? "-"}</td>
        <td class="text-end">${po.balance_qty ?? "-"}</td>
        <td>${po.destination_name || "-"}</td>
        <td>${po.broker_name || "-"}</td>
        <td class="text-center">${po.delivery_days ?? "-"}</td>
        <td class="text-center">${fmt(po.due_date)}</td>
      </tr>`;
  }

  attachSelectedPO() {
    const $checked = $("#pending_po_table tbody .po-select:checked");
    if (!$checked.length) {
      showToast("error", "Please select a purchase order.");
      return;
    }
    const $row = $checked.closest("tr");
    this._currentPO = {
      id: $row.data("po-id"),
      order_number: $row.data("order-number"),
      order_serial: $row.data("order-serial"),
    };
    this.renderPODisplay();
    this.$poModal.modal("hide");

    // PO and pending-ref adjustments are mutually exclusive — reset refs to blank new-ref row
    const row = this.rowManager.getAllRows().eq(this.currentRowIndex);
    const amounts = this.rowManager.getAmounts(row);
    const transactionType = this.rowManager.getTransactionType(row);
    const rowAmount = transactionType === "DR" ? amounts.debit : amounts.credit;
    this.clear();
    if (rowAmount) this.loadNewReference(rowAmount, transactionType);
    this.calculateTotals();
  }

  renderPODisplay() {
    const $display = $("#ref_po_display");
    if (this._currentPO) {
      $display.removeClass("d-none").html(`
        <div class="d-flex align-items-center gap-2 px-3 py-2 rounded border border-success-subtle bg-success-lt">
          <i class="fa-solid fa-paperclip text-success"></i>
          <span style="font-size:0.85rem;">
            Attach Purchase Order No:
            <strong class="text-success">${this._currentPO.order_serial}</strong>
          </span>
          <button type="button" class="btn btn-sm btn-ghost-danger ms-auto px-1 py-0" id="btn_clear_po" title="Remove attached PO">
            <i class="fa-solid fa-times"></i>
          </button>
        </div>
      `);
      $("#btn_clear_po")
        .off("click")
        .on("click", () => {
          this._currentPO = null;
          this.renderPODisplay();
        });
    } else {
      $display.addClass("d-none").html("");
    }
  }

  filterPOTable() {
    const term = $("#pending_po_search").val().toLowerCase();
    let visible = 0;
    $("#pending_po_table tbody tr:not(#po_loader_row):not(#po_no_data_row)").each(
      function () {
        const match = $(this).text().toLowerCase().includes(term);
        $(this).toggleClass("d-none", !match);
        if (match) visible++;
      },
    );
    $("#po_no_data_row").toggleClass("d-none", visible > 0);
  }

  calculateTotals() {
    let drTotal = 0;
    let crTotal = 0;

    this.refTableBody.find(".ref-row").each(function () {
      const amount = parseFloat($(this).find(".ref-amount").val()) || 0;
      const type =
        $(this).find(".transaction-type").val()?.toUpperCase() || "DR";
      if (type === "DR") drTotal += amount;
      else if (type === "CR") crTotal += amount;
    });

    const netAmount = drTotal - crTotal;
    const total = Math.abs(netAmount);
    const transactionType = netAmount >= 0 ? "Dr" : "Cr";

    const formatted =
      typeof formatIndianNumber === "function"
        ? formatIndianNumber(total.toFixed(2))
        : total.toFixed(2);

    $("#total-ref-amount").val(formatted);
    $("#total-transaction-type").val(transactionType);
  }

  initOffcanvas() {
    this.offcanvasEl = document.getElementById("pending_ref_offcanvas");
    this.$offCanvas = $(this.offcanvasEl);
    this.bindOffcanvasEvents();
  }

  bindOffcanvasEvents() {
    $("#pending_ref").on("click", (e) => this.openPendingRefOffcanvas(e));
    $("#check_all_refs").on("click", () => this.checkAllReferences());
    $("#pending_ref_table").on("change", "input.ref-select", () =>
      this.updateSelection(),
    );
    $("#apply_selected_btn").on("click", () => this.applySelected());

    $(this.offcanvasEl).on("hidden.bs.offcanvas", () => {
      $("#custom_overlay").hide();
      if (this._pendingSelectedRefs) {
        this.setSelectedReferences(this._pendingSelectedRefs);
        this._pendingSelectedRefs = null;
      }
    });
  }

  openPendingRefOffcanvas() {
    const row = this.rowManager.getAllRows().eq(this.currentRowIndex);
    this.openOffcanvas(row);
  }

  async openOffcanvas(row) {
    const accountId = this.rowManager.getAccountId(row);
    const tbody = $("#pending_ref_table_body");

    tbody.empty();
    $("#offcanvas_loader").removeClass("d-none");

    const ledgerService = new LedgerService(this.dataStore);
    const pendingRefs = await ledgerService.fetchPendingReferences(accountId);

    $("#offcanvas_loader").addClass("d-none");

    if (pendingRefs.length === 0) {
      tbody.html(
        '<tr><td colspan="7" class="text-center text-muted">No Pending References</td></tr>',
      );
    } else {
      const rowsHtml = pendingRefs
        .map((ref) => this.createPendingRefRow(ref))
        .join("");
      tbody.html(rowsHtml);
    }

    $("#custom_overlay").show();
    this.$offCanvas.offcanvas("show");
    this.updateSelection();
  }

  createPendingRefRow(ref) {
    const refDate = ref.reference_date
      ? typeof formatYMDtoDMY === "function"
        ? formatYMDtoDMY(ref.reference_date)
        : ref.reference_date
      : "";

    return `
      <tr>
        <td class="text-center">
          <input type="checkbox" class="form-check-input border border-1 border-dark-subtle ref-select" value="${ref.reference_id}">
        </td>
        <td class="text-center">${refDate}</td>
        <td>${ref.reference_number}</td>
        <td class="text-end pending-amount">${ref.pending_amount}</td>
        <td class="text-end bill-amount">${ref.amount}</td>
        <td class="text-center">${ref.direction === "debit" ? "Dr" : "Cr"}</td>
      </tr>
    `;
  }

  checkAllReferences() {
    $("#pending_ref_table tbody input.ref-select").prop("checked", true);
    this.updateSelection();
  }

  updateSelection() {
    let total = 0;
    let count = 0;

    $("#pending_ref_table tbody tr").each(function () {
      const checkbox = $(this).find("input.ref-select");
      if (checkbox.is(":checked")) {
        count++;
        total += parseFloat($(this).find(".pending-amount").text().replace(/,/g, "")) || 0;
      }
    });

    $("#selected_count").text(count);
    const formatted =
      typeof formatIndianNumber === "function"
        ? formatIndianNumber(total.toFixed(2))
        : total.toFixed(2);
    $("#selected_total").text(formatted);
  }

  applySelected() {
    const selectedRefs = [];
    $("#pending_ref_table tbody tr").each(function () {
      const checkbox = $(this).find("input.ref-select");
      if (checkbox.is(":checked")) {
        selectedRefs.push({
          refId: checkbox.val(),
          refNo: $(this).find("td").eq(2).text(),
          amount: parseFloat($(this).find(".pending-amount").text().replace(/,/g, "")) || 0,
          transactionType: $(this).find("td").eq(5).text(),
        });
      }
    });

    if (selectedRefs.length === 0) {
      showToast("error", "Please select at least one reference.");
      return;
    }

    $("#adjustment_offcanvas_error").html("");
    this._pendingSelectedRefs = selectedRefs;
    this.$offCanvas.offcanvas("hide");
  }

  setSelectedReferences(refs) {
    this.refTableBody.html("");
    refs.forEach((ref, i) => {
      const newRow = this.createRow(
        i + 1,
        {
          refId: ref.refId,
          method: "against_ref",
          refName: ref.refNo,
          refAmt: ref.amount,
        },
        ref.transactionType,
      );
      newRow.find(".ref-method").prop("disabled", true);
      this.refTableBody.append(newRow);
    });
    this.calculateTotals();

    // Pending refs and PO are mutually exclusive — clear PO when refs are applied
    this._currentPO = null;
    this.renderPODisplay();
  }

  refreshPreview() {
    const previewBox = $("#ref_current_row_preview");
    const allRows = this.rowManager.getAllRows();
    let html = "";

    allRows.each((index, tr) => {
      const refs = this.dataStore.getReferences(index);
      if (refs.length === 0) return;

      const $tr = $(tr);
      const rowData = this.dataStore.getRowData(index);
      const accountName = rowData.text || `Row ${index + 1}`;
      const rowTransactionType = this.rowManager
        .getTransactionType($tr)
        .toUpperCase();
      const drCrLabel = rowTransactionType === "DR" ? "Dr" : "Cr";

      let drTotal = 0;
      let crTotal = 0;

      refs.forEach((r) => {
        const amt = Number(r.refAmt || 0);
        const type = (r.transactionType || drCrLabel).toUpperCase();
        if (type === "DR") drTotal += amt;
        else crTotal += amt;
      });

      const netAmount = drTotal - crTotal;
      const total = Math.abs(netAmount);
      const calculatedType = netAmount >= 0 ? "Dr" : "Cr";

      const formattedTotal =
        typeof formatIndianNumber === "function"
          ? formatIndianNumber(total.toFixed(2))
          : total.toFixed(2);

      html += `
        <div class="ref-preview-group mb-2">
          <div class="d-flex justify-content-between align-items-center px-2 py-1 rounded mb-1"
               style="background:#e8f0fe; border-left:3px solid #4285f4;">
            <span class="fw-semibold text-primary" style="font-size:0.82rem;">${accountName}</span>
            <span class="badge bg-primary text-white" style="font-size:0.75rem;">${formattedTotal} ${calculatedType}</span>
          </div>
          <table class="table table-sm table-bordered mb-0" style="font-size:0.78rem;">
            <thead class="table-light">
              <tr>
                <th class="text-center" style="width:28px;">#</th>
                <th>Ref No</th>
                <th>Method</th>
                <th class="text-end">Amount</th>
                <th class="text-center">D/C</th>
              </tr>
            </thead>
            <tbody>
              ${refs
                .map(
                  (ref, i) => `
                <tr>
                  <td class="text-center">${i + 1}</td>
                  <td>${ref.refName || "-"}</td>
                  <td class="text-center">${{ new_ref: "New Ref", against_ref: "Against Ref", advance: "On Advance", adjustment: "Adjustment" }[ref.method] || ref.method || "-"}</td>
                  <td class="text-end">${typeof formatIndianNumber === "function" ? formatIndianNumber(Number(ref.refAmt || 0).toFixed(2)) : Number(ref.refAmt || 0).toFixed(2)}</td>
                  <td class="text-center">${ref.transactionType ? (ref.transactionType.toUpperCase() === "DR" ? "Dr" : "Cr") : drCrLabel}</td>
                </tr>
              `,
                )
                .join("")}
            </tbody>
          </table>
        </div>
      `;
    });

    if (!html) {
      previewBox.html(
        '<p class="text-muted text-center py-2" style="font-size:0.8rem;">No references set.</p>',
      );
    } else {
      previewBox.html(html);
    }
  }
}

class LedgerService {
  constructor(dataStore) {
    this.dataStore = dataStore;
  }

  async fetchBalance(accountId) {
    const cached = this.dataStore.getLedgerBalance(accountId);
    if (cached !== undefined) return cached;

    try {
      const response = await $.ajax({
        url: closingBalanceUrl,
        type: "GET",
        data: { account_ids: [accountId] },
      });

      let balance = 0;
      const row = response.data[accountId];
      if (row) balance = row.closing;

      this.dataStore.setLedgerBalance(accountId, balance);
      return balance;
    } catch (error) {
      console.error("Balance fetch error:", error);
      throw error;
    }
  }

  formatBalance(balance) {
    const absBalance = Math.abs(balance).toFixed(2);
    const formatted =
      typeof formatIndianNumber === "function"
        ? formatIndianNumber(absBalance)
        : absBalance;
    return balance >= 0 ? `${formatted} Dr` : `${formatted} Cr`;
  }

  async fetchPendingReferences(accountId) {
    try {
      const response = await $.ajax({
        url: pendingRefUrl,
        method: "GET",
        data: { account_id: accountId },
      });
      return response.data.reference || [];
    } catch (error) {
      console.error("Error fetching references:", error);
      return [];
    }
  }
}

class VoucherRowManager {
  constructor(table, dataStore) {
    this.table = $(table);
    this.dataStore = dataStore;
  }

  getRowIndex(row) {
    return this.table.find("tbody tr").index(row);
  }
  getRow(element) {
    return $(element).closest("tr");
  }

  getRowData(row) {
    const rowIndex = this.getRowIndex(row);
    return this.dataStore.getRowData(rowIndex);
  }

  setRowData(row, data) {
    const rowIndex = this.getRowIndex(row);
    this.dataStore.setRowData(rowIndex, data);
  }

  getAccountId(row) {
    return row.find(".account-id").val();
  }
  getTransactionType(row) {
    return row.find(".dr-cr").val();
  }

  getAmounts(row) {
    return {
      debit: parseFloat(row.find(".debit-amount").val()) || 0,
      credit: parseFloat(row.find(".credit-amount").val()) || 0,
    };
  }

  isReferenceRequired(row) {
    const rowData = this.getRowData(row);
    return rowData.is_billwise == true;
  }

  getAllRows() {
    return this.table.find("tbody tr");
  }
}

class VoucherCalculationService {
  constructor(rowManager) {
    this.rowManager = rowManager;
  }

  calculateTotals() {
    let drTotal = 0;
    let crTotal = 0;

    this.rowManager.getAllRows().each(function () {
      drTotal +=
        parseFloat($(this).find(".debit-amount").val().replace(/,/g, "")) || 0;
      crTotal +=
        parseFloat($(this).find(".credit-amount").val().replace(/,/g, "")) || 0;
    });

    return {
      debit: drTotal,
      credit: crTotal,
      isBalanced: drTotal.toFixed(2) === crTotal.toFixed(2),
    };
  }

  calculateDifference(type = "dr") {
    let debitSum = 0;
    let creditSum = 0;

    this.rowManager.getAllRows().each(function () {
      debitSum += parseFloat($(this).find(".debit-amount").val()) || 0;
      creditSum += parseFloat($(this).find(".credit-amount").val()) || 0;
    });

    return type === "dr" ? creditSum - debitSum : debitSum - creditSum;
  }
}

class VoucherDataStore {
  constructor() {
    this.rowData = new Map();
    this.references = new Map();
    this.ledgerBalances = new Map();
    this.purchaseOrders = new Map();
  }

  setRowData(rowIndex, data) {
    this.rowData.set(rowIndex, { ...this.rowData.get(rowIndex), ...data });
  }
  getRowData(rowIndex) {
    return this.rowData.get(rowIndex) || {};
  }

  setReferences(rowIndex, references) {
    this.references.set(rowIndex, references);
  }
  getReferences(rowIndex) {
    return this.references.get(rowIndex) || [];
  }
  clearReferences(rowIndex) {
    this.references.delete(rowIndex);
  }

  setLedgerBalance(accountId, balance) {
    this.ledgerBalances.set(accountId, balance);
  }
  getLedgerBalance(accountId) {
    return this.ledgerBalances.get(accountId);
  }

  setPurchaseOrder(rowIndex, data) {
    if (data) this.purchaseOrders.set(rowIndex, data);
    else this.purchaseOrders.delete(rowIndex);
  }
  getPurchaseOrder(rowIndex) {
    return this.purchaseOrders.get(rowIndex) || null;
  }

  clear() {
    this.rowData.clear();
    this.references.clear();
    this.ledgerBalances.clear();
    this.purchaseOrders.clear();
  }
}
