$(document).ready(function () {
  new DateInput("#voucher_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
  if ($("#bill_date").length) {
    new DateInput("#bill_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
  }
  // check within financial year date
  $("#voucher_date").on("blur", function () {
    let day = getDayFromDate($(this).val());
    let invDate = formatDateToYMD($(this).val());
    if (!invDate) return;

    $('#weekday').val(day);
    if (!isWithinFY(invDate, FINANCIAL_YEAR_START, FINANCIAL_YEAR_END)) {
      showToast(
        "error",
        `Date must be within Financial Year:<br>(${formatDateToDMY(
          FINANCIAL_YEAR_START,
        )} to ${formatDateToDMY(FINANCIAL_YEAR_END)})`,
        9000,
      );
      $(this).val("");
    }
  });

  const formMode = $("#form_mode").val();

  if (formMode === "edit") {
    $("#voucher_id").select2({
      theme: "bootstrap-5",
    });
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
  
  bindSelect2();
  window.voucherTableManager = new VoucherTableManager(
    "#journal_voucher_table",
  );
}

// -------------------------------------------------------
// SELECT2 BINDING
// -------------------------------------------------------
function bindSelect2() {
    var selects = ['#gst_nature', '#vehicle_id'];
    selects.forEach(function (el) {
         $(el).select2({
                theme: 'bootstrap-5',
        });
    });

    $(document).on('select2:open', function (e) {
        var $select     = $(e.target);
        var $search     = $select.data('select2').$dropdown.find('.select2-search__field');
        $search.off('keydown.select2Enter').on('keydown.select2Enter', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                $select.select2('close');
                moveFocusToNextField($select);
            }
        });
    });

    
}
let accountMasterData = [];
let isAccountDataLoaded = false;

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
      is_billwise: acc.is_billwise,
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
    isAccountDataLoaded = true;
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
      if (!data || data.id === undefined || data.id === "__NULL__") {
        return "";
      }
      return data.text;
    },
    ajax: {
      transport: function (params, success) {
        let term = (params.data.term || "").toLowerCase();
        let results = accountMasterData.filter((a) =>
          a.text.toLowerCase().includes(term),
        );
        results.unshift({
          id: "__NULL__",
          text: "— None —",
        });
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

  var array = [".account-id", "#gst_nature"];

  array.forEach((element) => {
    $(element).on("select2:open", function () {
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
    this.tdsManager = new TdsManager(this);

    this.initEvents();
  }

  initEvents() {
    // Handle Dr Cr
    this.table.on("keydown", ".dr-cr", (e) => this.handleDrCrKey(e));
    this.table.on("focus", ".dr-cr", (e) => this.handleDrCrFocus(e));
    // Manage Auto Balance
    this.table.on("focus", ".debit-amount, .credit-amount", (e) =>
      this.autoBalance(e),
    );

    this.table.on("blur", ".dr-cr", (e) => this.handleSelect2Open(e));

    this.table.on("blur", ".debit-amount, .credit-amount", async (e) => {
      const $el = $(e.target);
      const val = parseFloat($el.val().replace(/,/g, "")) || 0;
      $el.val(val > 0 ? val.toFixed(2) : "");
      if ($el.val() !== $el.data("original-value")) {
        const row = this.rowManager.getRow(e.target);
        const rowIndex = this.rowManager.getRowIndex(row);
        // this.markRowDirty(rowIndex);
      }
      this.updateTotals();
      
      // Auto-trigger TDS Check on blur if not already applied
      let tdsTriggered = false;
      if (this.tdsManager && typeof this.tdsManager.checkForTds === 'function') {
          tdsTriggered = await this.tdsManager.checkForTds(this.rowManager.getRow(e.target));
      }
      
      if (!tdsTriggered) {
          this.referenceManager.open(this.rowManager.getRow(e.target));
      }
    });

    // accountId Change
    this.table.on("change", ".account-id", (e) => this.handleLedgerChange(e));

    // Form submit
    $("#journal_voucher_form").on("submit", (e) => this.handleStore(e));
  }

  buildPayload() {
    const voucherDate = formatDateToYMD($("#voucher_date").val());

    const rows = [];
    this.rowManager.getAllRows().each((index, tr) => {
      const $tr = $(tr);
      const accountId = $tr.find(".account-id").val();
      const debit = parseFloat($tr.find(".debit-amount").val().replace(/,/g, "") || 0) || 0;
      const credit = parseFloat($tr.find(".credit-amount").val().replace(/,/g, "") || 0) || 0;

      if (!accountId || (debit === 0 && credit === 0)) return;

      const refs = this.dataStore.getReferences(index);
      rows.push({
        account_id: accountId,
        dr_cr: $tr.find(".dr-cr").val(),
        debit_amount: debit,
        credit_amount: credit,
        references: refs.map((ref) => ({
          ref_id: ref.refId || null,
          method: ref.method,
          ref_number: ref.refName,
          ref_amount: parseFloat(ref.refAmt) || 0,
          file_no: ref.file_no || null,
        })),
      });
    });

    return {
      uuid: $("#uuid").val(),
      voucher_date: voucherDate,
      voucher_serial: $("#voucher_serial").val(),
      gst_nature: $("#gst_nature").val(),
      vehicle_id: $("#vehicle_id").val() || null,
      bill_date: $("#bill_date").length ? (formatDateToYMD($("#bill_date").val()) || null) : null,
      reference_number: $("#reference_number").length ? $("#reference_number").val() : null,
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

    return true;
  }

  async handleStore(e, fromTdsModal = false) {
    if (e) e.preventDefault();

    const payload = this.buildPayload();
    if (!this.validatePayload(payload)) return;

    if (this.tdsManager && this.tdsManager.tdsApplied && !fromTdsModal) {
        // Refresh the TDS amounts from the grid in case the user modified them manually
        this.tdsManager.refreshTdsModalFromGrid();
        // Pop up the TDS Deduction Details modal before final save!
        $('#tds_modal').modal('show');
        return;
    }

    // Check if TDS is applicable before saving (if it hasn't been applied yet)
    const isTdsApplicable = await this.tdsManager.checkForTds(null, true);
    
    if (isTdsApplicable) {
        // TDS process takes over, execution will resume upon confirmation
        return;
    }

    if (this.tdsManager && this.tdsManager.tdsDetails) {
        payload.tds_details = this.tdsManager.tdsDetails;
    }

    // Proceed to save if no TDS is applicable
    this.executeStore(payload);
  }

  async executeStore(payload) {
    const $btn = $("#save_btn");
    $btn.prop("disabled", true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

    const isEdit = $("#form_mode").val() === "edit";
    const voucherId = isEdit ? $("#voucher_id").val() : null;
    const url = isEdit ? `${updateJournalVoucher}/${voucherId}` : storeJournalVoucher;
    const method = isEdit ? "PUT" : "POST";
    const action = isEdit ? "updated" : "created";

    try {
      const response = await $.ajax({
        url: url,
        method: method,
        data: JSON.stringify(payload),
        contentType: "application/json",
        dataType: "json",
        headers: {
          "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
      });

      Swal.fire({
        icon: "success",
        title: "Success",
        html: `Journal Voucher <b class='text-primary'>${response.data.voucher_serial}</b> has been ${action} successfully.`,
        allowOutsideClick: false,
        allowEscapeKey: false,
        confirmButtonText: "OK",
        confirmButtonColor: "#28a745",
      }).then((result) => {
        if (result.isConfirmed) {
          location.reload();
        }
      });
    } catch (err) {
      let msg = err?.responseJSON?.errors;
      
      // If errors is empty array/object or undefined, fallback to message
      if (!msg || (typeof msg === 'object' && Object.keys(msg).length === 0)) {
          msg = err?.responseJSON?.message || "An error occurred. Please try again.";
      }

      if (typeof msg === 'object') {
          // If it's a validation error object, format it
          let html = '<ul class="text-start">';
          for (const key in msg) {
              // Ensure we don't crash if msg[key] isn't an array
              const errorText = Array.isArray(msg[key]) ? msg[key][0] : msg[key];
              html += `<li>${errorText}</li>`;
          }
          html += '</ul>';
          Swal.fire({ icon: "error", title: "Validation Error", html: html });
      } else {
          Swal.fire({ icon: "error", title: "Error", html: msg });
      }
    } finally {
      $btn.prop("disabled", false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Save');
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
    if (totals.debit > totals.credit) return "cr";           // DR heavy → need CR
    if (totals.credit > totals.debit) return "dr";           // CR heavy → need DR
    return "dr";                                              // both zero → first row defaults DR
  }

  handleDrCrFocus(e) {
    const row = this.rowManager.getRow(e.target);
    const amounts = this.rowManager.getAmounts(row);
    if (amounts.debit > 0 || amounts.credit > 0) return; // row already has a value, leave it

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
      e.preventDefault(); // block typing
      e.target.value = "";
    }
  }

  autoBalance(e) {
    const row = this.rowManager.getRow(e.target);
    const type = $(e.target).hasClass("debit-amount") ? "dr" : "cr";

    if (e.target.value) return; // Don't overwrite existing value

    const diff = this.calculationService.calculateDifference(type);
    if (diff > 0) {
      e.target.value = diff.toFixed(2);
    }
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
        if ($("body").hasClass("modal-open") || $(".swal2-container").length) return;
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
    
    // Reset TDS flags on account change so it can re-trigger if needed
    if (this.tdsManager) {
        this.tdsManager.tdsApplied = false;
        this.tdsManager.tdsIgnored = false;
    }

    if (!accountId) {
      this.dataStore.setRowData(rowIndex, {});
      return;
    } else {
      let data = accountMasterData.find((a) => a.id == accountId);
      this.dataStore.setRowData(rowIndex, data);
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

    if (this.tdsManager && typeof this.tdsManager.checkForTds === 'function') {
        await this.tdsManager.checkForTds();
    }
  }
}

class ReferenceManager {
  constructor(dataStore, rowManager) {
    this.dataStore = dataStore;
    this.rowManager = rowManager;
    this.currentRowIndex = null;
    // this.preventOpen = false;

    this.initModal();
    this.initOffcanvas();
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
    // this.refTableBody.on("change", ".ref-method", (e) =>
    //   this.handleMethodChange(e),
    // );
    // this.refTableBody.on("input", ".ref-amount", () => this.calculateTotals());
    // this.refTableBody.on("change", ".transaction-type", () =>
    //   this.calculateTotals(),
    // );

    // Bootstrap native event for reliable focus when modal finishes opening
    this.$modal.on('shown.bs.modal', () => {
      $(".ref-method").select2({
        theme: "bootstrap-5",
        dropdownParent: this.$modal
      });
      setTimeout(() => {
        this.refTableBody.find("tr").first().find(".ref-number").focus();
      }, 50); // Small delay to let Select2 finish rendering before grabbing focus
    });

    // $(this.modalEl).on("hidden.bs.modal", () => this.onModalHidden());
  }

  open(row) {
    if (!this.rowManager.isReferenceRequired(row)) return;

    if ($("body").hasClass("modal-open")) {
      console.warn("Another modal is already open. Preventing new modal.");
      return;
    }

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

    if (existingRefs.length > 0) {
      this.loadReferences(existingRefs, transactionType);
    } else {
      this.loadNewReference(rowAmount, transactionType);
    }

    this.$modal
      .modal({
        backdrop: "static",
        keyboard: false,
      })
      .modal("show");

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
    first.find(".ref-method").val("new_ref");
    first.find(".ref-number").val("");
    first.find(".ref-amount").val(amount.toFixed(2));
    first.find(".transaction-type").val(transactionType === "DR" ? "Dr" : "Cr");
    first.find(".ref-file-no").val("0");
  }

  createRow(sno, data = {}, transactionType = "") {
    const row = $(this.getRowTemplate());
    row.find(".ref-sno").text(sno);
    row.find(".ref-id").val(data.refId || "");
    row.find(".ref-method").val(data.method || "new_ref");
    row.find(".ref-number").val(data.refName || "");
    row.find(".ref-amount").val(Number(data.refAmt || 0).toFixed(2));
    row.find(".transaction-type").val(transactionType);
    row.find(".ref-file-no").val(data.file_no !== undefined && data.file_no !== null && data.file_no !== "" ? data.file_no : "0");

    $(".ref-method").select2({
      theme: "bootstrap-5",
    }).trigger('change');

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
              <option value="new_ref">New Ref</option>
              <option value="against_ref">Again Ref</option>
            </select>
          </td>
          <td><input type="text" class="form-control ref-number"></td>
          <td><input type="text" class="form-control ref-amount text-end only-number"></td>
          <td><input type="text" class="form-control transaction-type text-center" disabled></td>
          <td><input type="text" class="form-control ref-file-no text-center"></td>
        </tr>
      `;
  }

  clear() {
    this.refPopupError.text("");
    this.refTableBody.find(".ref-row:not(:first)").remove();
    const first = this.refTableBody.find(".ref-row").first();
    first.find("input").val("");
    first.find(".ref-method").val("new_ref");
  }

  clearAllReferences() {
    if (this.currentRowIndex === null) return;

    // Clear references from memory
    this.dataStore.setReferences(this.currentRowIndex, []);

    // Reset the modal UI
    this.clear();

    const row = this.rowManager.getAllRows().eq(this.currentRowIndex);
    const amounts = this.rowManager.getAmounts(row);
    const transactionType = this.rowManager.getTransactionType(row);
    const rowAmount = transactionType === "DR" ? amounts.debit : amounts.credit;

    if (rowAmount) {
      this.loadNewReference(rowAmount, transactionType);
    }

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
        file_no: $(this).find(".ref-file-no").val(),
        transactionType: refType,
      });

      if (typeUpper === "DR") {
        drTotal += refAmount;
      } else if (typeUpper === "CR") {
        crTotal += refAmount;
      }
    });

    if (hasError) return;

    const row = this.rowManager.getAllRows().eq(this.currentRowIndex);
    const amounts = this.rowManager.getAmounts(row);
    const transactionType = this.rowManager.getTransactionType(row).toUpperCase();
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
    this.refreshPreview();
    this.$modal.modal("hide");

    let nextIndex = this.currentRowIndex + 1;
    $(`tr[data-index="${nextIndex}"]`).find(".dr-cr").focus();
  }

  calculateTotals() {
    let drTotal = 0;
    let crTotal = 0;

    this.refTableBody.find(".ref-row").each(function () {
      const amount = parseFloat($(this).find(".ref-amount").val()) || 0;
      const type = $(this).find(".transaction-type").val()?.toUpperCase() || "DR";

      if (type === "DR") {
        drTotal += amount;
      } else if (type === "CR") {
        crTotal += amount;
      }
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

//    Pending ref function 
  initOffcanvas() {
    this.offcanvasEl = document.getElementById("pending_ref_offcanvas");
    this.$offCanvas = $(this.offcanvasEl);

    this.bindOffcanvasEvents();
  }

  bindOffcanvasEvents() {
    $("#pending_ref").on("click", (e) => this.openPendingRefOffcanvas(e));
    $('#check_all_refs').on('click', () => this.checkAllReferences());
    $('#pending_ref_table').on('change', 'input.ref-select', () => this.updateSelection());
    $('#apply_selected_btn').on('click', () => this.applySelected());

    $(this.offcanvasEl).on('hidden.bs.offcanvas', () => {
      $('#custom_overlay').hide();
      if (this._pendingSelectedRefs) {
        this.setSelectedReferences(this._pendingSelectedRefs);
        this._pendingSelectedRefs = null;
      }
    });
  }

  openPendingRefOffcanvas() {
    // this.$offCanvas.offcanvas("show");
    // $('#offcanvas_loader').removeClass('d-none');

    const row = this.rowManager.getAllRows().eq(this.currentRowIndex)

    this.openOffcanvas(row)
  }

    async openOffcanvas(row) {
      const accountId = this.rowManager.getAccountId(row);
      const tbody = $('#pending_ref_table_body');

      tbody.empty();
      $('#offcanvas_loader').removeClass('d-none');

      const ledgerService = new LedgerService(this.dataStore);
      const pendingRefs = await ledgerService.fetchPendingReferences(accountId);

      $('#offcanvas_loader').addClass('d-none');

      if (pendingRefs.length === 0) {
        tbody.html('<tr><td colspan="7" class="text-center text-muted">No Pending References</td></tr>');
      } else {
        const rowsHtml = pendingRefs.map(ref => this.createPendingRefRow(ref)).join('');
        tbody.html(rowsHtml);
      }

      $('#custom_overlay').show();
      this.$offCanvas.offcanvas("show");
      this.updateSelection();
    }

    createPendingRefRow(ref) {
      // formatYMDtoDMY from helper.js
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
          <td class="text-end">${ref.file_number}</td>
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

      $('#pending_ref_table tbody tr').each(function () {
        const checkbox = $(this).find('input.ref-select');
        if (checkbox.is(':checked')) {
          count++;
          total += parseFloat($(this).find('.pending-amount').text().replace(/,/g, '')) || 0;
        }
      });

      $('#selected_count').text(count);
      const formatted = typeof formatIndianNumber === 'function' ? formatIndianNumber(total.toFixed(2)) : total.toFixed(2);
      $('#selected_total').text(formatted);
    }

    applySelected() {
      const selectedRefs = [];
      $('#pending_ref_table tbody tr').each(function () {
        const checkbox = $(this).find('input.ref-select');
        if (checkbox.is(':checked')) {
          selectedRefs.push({
            refId: checkbox.val(),
            refNo: $(this).find('td').eq(2).text(),
            amount: parseFloat($(this).find('.pending-amount').text().replace(/,/g, '')) || 0,
            fileNo: $(this).find('td').eq(6).text(),
            transactionType: $(this).find('td').eq(5).text()
          });
        }
      });

      if (selectedRefs.length === 0) {
        showToast('error', 'Please select at least one reference.');
        return;
      }

      $('#adjustment_offcanvas_error').html('');
      this._pendingSelectedRefs = selectedRefs;
      this.$offCanvas.offcanvas('hide');
    }

    setSelectedReferences(refs) {
      this.refTableBody.html('');
      refs.forEach((ref, i) => {
        const newRow = this.createRow(i + 1, {
          refId: ref.refId,
          method: 'against_ref',
          refName: ref.refNo,
          refAmt: ref.amount,
          file_no: ref.fileNo
        }, ref.transactionType);
        newRow.find('.ref-method').prop('disabled', true);
        this.refTableBody.append(newRow);
      });

      this.calculateTotals();
    }

  refreshPreview() {
    const previewBox = $('#ref_current_row_preview');
    const allRows = this.rowManager.getAllRows();
    let html = '';

    allRows.each((index, tr) => {
      const refs = this.dataStore.getReferences(index);
      if (refs.length === 0) return;

      const $tr = $(tr);
      const rowData = this.dataStore.getRowData(index);
      const accountName = rowData.text || `Row ${index + 1}`;
      const rowTransactionType = this.rowManager.getTransactionType($tr).toUpperCase();
      const drCrLabel = rowTransactionType === 'DR' ? 'Dr' : 'Cr';

      let drTotal = 0;
      let crTotal = 0;

      refs.forEach(r => {
        const amt = Number(r.refAmt || 0);
        const type = (r.transactionType || drCrLabel).toUpperCase();
        if (type === 'DR') drTotal += amt;
        else crTotal += amt;
      });

      const netAmount = drTotal - crTotal;
      const total = Math.abs(netAmount);
      const calculatedType = netAmount >= 0 ? 'Dr' : 'Cr';

      const formattedTotal = typeof formatIndianNumber === 'function'
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
                <th class="text-center">File No</th>
              </tr>
            </thead>
            <tbody>
              ${refs.map((ref, i) => `
                <tr>
                  <td class="text-center">${i + 1}</td>
                  <td>${ref.refName || '-'}</td>
                  <td class="text-center">${ref.method === 'new_ref' ? 'New Ref' : 'Against Ref'}</td>
                  <td class="text-end">${typeof formatIndianNumber === 'function' ? formatIndianNumber(Number(ref.refAmt || 0).toFixed(2)) : Number(ref.refAmt || 0).toFixed(2)}</td>
                  <td class="text-center">${ref.transactionType ? (ref.transactionType.toUpperCase() === 'DR' ? 'Dr' : 'Cr') : drCrLabel}</td>
                  <td class="text-center">${ref.file_no || '-'}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      `;
    });

    if (!html) {
      previewBox.html('<p class="text-muted text-center py-2" style="font-size:0.8rem;">No references set.</p>');
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
    // Check cache first
    const cached = this.dataStore.getLedgerBalance(accountId);
    if (cached !== undefined) return cached;

    try {
      const response = await $.ajax({
        url: closingBalanceUrl,
        type: "GET",
        data: { account_ids: [accountId] },
      });

      let balance = 0;
      let data = response.data;

      let row = data[accountId];

      if (!row) {
        return balance;
      }

      balance = row.closing;

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

  setAmount(row, type, value) {
    row.find(`.${type}-amount`).val(value);
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

  findEmptyRow(type) {
    let emptyRow = null;
    this.rowManager.getAllRows().each(function () {
      const rowType = $(this).find(".dr-cr").val()?.toLowerCase();
      const dr = parseFloat($(this).find(".debit-amount").val()) || 0;
      const cr = parseFloat($(this).find(".credit-amount").val()) || 0;

      if (rowType === type && (type === "dr" ? dr === 0 : cr === 0)) {
        emptyRow = $(this);
        return false; // break loop
      }
    });
    return emptyRow;
  }
}

class VoucherDataStore {
  constructor() {
    this.rowData = new Map(); // key: row index, value: row data
    this.references = new Map(); // key: row index, value: references array
    this.ledgerBalances = new Map(); // key: accountId, value: balance
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

  clear() {
    this.rowData.clear();
    this.references.clear();
    this.ledgerBalances.clear();
  }

  getAllRowsData() {
    return Array.from(this.rowData.entries()).map(([index, data]) => ({
      index,
      ...data,
      references: this.getReferences(index),
    }));
  }
}

class TdsManager {
  constructor(tableManager) {
    this.tableManager = tableManager;
    this.currentPayload = null;
    this.initEvents();
  }

  initEvents() {
    // Initialize Select2 for TDS Category
    $('#tds_category_id').select2({
        dropdownParent: $('#tds_modal'),
        width: '100%',
        theme: "bootstrap-5",
    });

    // Focus on Ref. No. when modal opens
    $('#tds_modal').on('shown.bs.modal', function () {
        $('#tds_ref_no').focus();
    });

    // Auto-calculate TDS on input change
    $('#tds_percentage, #tds_assessable_value').on('input', () => this.calculateTds('percentage'));
    $('#tds_amount').on('input', () => this.calculateTds('amount'));
    
    // Form submission inside modal (TDS Deduction Details Modal on Save)
    $('#tds_computation_form').on('submit', (e) => {
      e.preventDefault();
      $('#tds_modal').modal('hide');
      
      if (this.tdsDetails) {
          this.tdsDetails.tds_amount = parseFloat($('#tds_amount').val()) || 0;
          this.tdsDetails.percentage = parseFloat($('#tds_percentage').val()) || 0;
          this.tdsDetails.reference_no = $('#tds_ref_no').val();
          this.tdsDetails.pan_no = $('#tds_party_pan').val();
      }
      
      // Proceed to final save
      if (this.tableManager) {
          this.tableManager.handleStore(null, true);
      }
    });
    
    // If modal is closed/cancelled, do nothing since it's just viewing before save
    $('#tds_modal').on('hidden.bs.modal', () => {
        // No longer tied to opening reference modal here
    });

    // Re-check threshold if user changes category
    $('#tds_category_id').on('change', (e, skipAjax) => {
        if (skipAjax) return;
        const payload = this.tableManager ? this.tableManager.buildPayload() : {};
        // Since we are already in the modal, we just need to run the ajax check again
        // We will mock the required variables from current modal state
        const partyId = $('#tds_party_id').val();
        const expenseId = $('#tds_expense_id').val();
        const categoryId = $('#tds_category_id').val();
        const payeeId = $('#tds_payee_id').val();
        const amount = $('#tds_assessable_value').val();
        
        if (typeof showLoader === 'function') showLoader('Checking TDS Threshold...');
        $.ajax({
            url: '/fas/tds-entries/check-threshold',
            method: 'POST',
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            data: { 
                party_id: partyId, 
                expense_id: expenseId, 
                tds_category_id: categoryId,
                payee_category_id: payeeId,
                amount: amount 
            }
        }).done((response) => {
            if (typeof hideLoader === 'function') hideLoader();
            
            const prevAmt = parseFloat(response.previous_amount) || 0;
            const formattedPrev = typeof formatIndianNumber === 'function' ? formatIndianNumber(prevAmt.toFixed(2)) : prevAmt.toFixed(2);
            $('#tds_previous_turnover').val(formattedPrev);
            
            if (response.is_applicable) {
                $('#tds_percentage').val(parseFloat(response.percentage) || 0);
                this.defaultAccount = response.default_account;
            } else {
                $('#tds_percentage').val(0);
                this.defaultAccount = response.default_account;
                showToast("warning", "TDS is not applicable for this category under the current threshold.");
            }
            this.calculateTds('percentage');
        }).fail(() => {
            if (typeof hideLoader === 'function') hideLoader();
            showToast("error", "Failed to calculate TDS.");
        });
    });
  }

  calculateTds() {
    const assessable = parseFloat($('#tds_assessable_value').val()) || 0;
    const percentage = parseFloat($('#tds_percentage').val()) || 0;
    const tdsAmount = (assessable * percentage) / 100;
    
    const tdsAmountRounded = tdsAmount.toFixed(2);
    const assessableRounded = assessable.toFixed(2);
    
    $('#tds_amount').val(tdsAmountRounded);
    
    $('#tds_total_tax_cell').text(tdsAmountRounded);
    $('#tds_foot_tds').text(tdsAmountRounded);
    $('#tds_foot_total').text(tdsAmountRounded);
    $('#tds_foot_expense').text(assessableRounded);
    
    const netPayable = assessable - tdsAmount;
    const formattedNet = typeof formatIndianNumber === 'function' 
        ? formatIndianNumber(netPayable.toFixed(2)) 
        : netPayable.toFixed(2);
        
    $('#tds_net_payable').text('₹ ' + formattedNet);
  }

  refreshTdsModalFromGrid() {
      // If the user changed the expense amount in the grid, we want to capture that updated amount
      // before showing the TDS modal on save.
      const expenseId = $('#tds_expense_id').val();
      if (!expenseId) return;

      let newAssessableValue = 0;
      
      this.tableManager.rowManager.getAllRows().each((index, tr) => {
          const $tr = $(tr);
          const accountId = $tr.find(".account-id").val();
          const dr_cr = $tr.find(".dr-cr").val();
          const debit_amount = parseFloat($tr.find(".debit-amount").val().replace(/,/g, "")) || 0;
          
          if (accountId == expenseId && dr_cr === 'DR') {
              newAssessableValue += debit_amount;
          }
      });
      
      if (newAssessableValue > 0) {
          $('#tds_assessable_value').val(newAssessableValue.toFixed(2));
          this.calculateTds();
      }
  }

  async checkForTds(triggerRow = null, fromStore = false) {
    if (window.isVoucherLoading) return false;
    if (this.tdsApplied || this.tdsIgnored) return false;

    let expenseAccount = null;
    let partyAccount = null;
    let assessableValue = 0;
    
    this.pendingReferenceRow = triggerRow;
    
    // Analyze all rows in the UI to find TDS-applicable Expense (Debit) and Party (Credit)
    this.tableManager.rowManager.getAllRows().each((index, tr) => {
       const $tr = $(tr);
       const accountId = $tr.find(".account-id").val();
       const dr_cr = $tr.find(".dr-cr").val();
       const debit_amount = parseFloat($tr.find(".debit-amount").val().replace(/,/g, "")) || 0;
       
       if (!accountId) return;
       
       const account = accountMasterData.find(a => a.id == accountId);
       if (!account) return;
       
       if (dr_cr === 'DR' && (account.tds_category || account.allow_tds)) {
           expenseAccount = account;
           assessableValue += debit_amount;
       }
       if (dr_cr === 'CR') {
           // We take the CR account as the Party, but avoid overwriting a valid party with a Tax ledger
           if (account.is_party_account) {
               partyAccount = account;
           } else if (!partyAccount) {
               partyAccount = account;
           }
       }
    });

    if (expenseAccount && partyAccount && assessableValue > 0) {
        // The user might set BOTH categories on the Expense, or one on Expense and one on Party.
        const tdsCategoryId = expenseAccount.tds_category || partyAccount.tds_category;
        const payeeCategoryId = expenseAccount.payee_category || partyAccount.payee_category;

        // If we don't have both IDs, TDS cannot be calculated
        if (!tdsCategoryId || !payeeCategoryId) {
            return false;
        }

        // Populate modal data
        const panNumber = partyAccount.pan_no || 'No PAN';
        $('#tds_party_name').val(partyAccount.text);
        $('#tds_party_id').val(partyAccount.id);
        $('#tds_party_pan').val(panNumber);
        
        $('#tds_sub_party_name').text(partyAccount.text);
        $('#tds_sub_party_pan').text(panNumber);
        
        $('#tds_expense_name').val(expenseAccount.text);
        $('#tds_expense_id').val(expenseAccount.id);
        
        const voucherDate = $('#voucher_date').val() || new Date().toISOString().split('T')[0];
        $('#tds_ded_on').val(voucherDate);
        $('#tds_exp_date').val(voucherDate);
        
        // Set Section/Category
        $('#tds_category_id').val(tdsCategoryId).trigger('change', [true]);
        
        // We might not have payee category name readily available if set on expense, but we can set the ID
        $('#tds_payee_name').val(payeeCategoryId);
        $('#tds_payee_id').val(payeeCategoryId);

        // Pre-fill calculation values
        $('#tds_assessable_value').val(assessableValue.toFixed(2));
        $('#tds_percentage').val(0); // To be fetched from threshold check later
        this.calculateTds();
        
        // Backend AJAX Threshold Check
        try {
            // Use the global showLoader/hideLoader if available, otherwise just use jQuery
            if (typeof showLoader === 'function') showLoader('Checking TDS Threshold...');
            
            const response = await $.ajax({
                url: '/fas/tds-entries/check-threshold',
                method: 'POST',
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                },
                data: { 
                    party_id: partyAccount.id, 
                    expense_id: expenseAccount.id, 
                    tds_category_id: tdsCategoryId,
                    payee_category_id: payeeCategoryId,
                    amount: assessableValue 
                }
            });
            
            if (typeof hideLoader === 'function') hideLoader();

            console.log("response", response);
            
            
            // If the threshold is NOT crossed, we don't need TDS
            if(!response.is_applicable) {
                return false; 
            }
            
            // Threshold is crossed or we are in edit mode
            if (window.existingTdsEntry && window.existingTdsEntry.tds_rate != null) {
                $('#tds_percentage').val(parseFloat(window.existingTdsEntry.tds_rate));
            } else {
                $('#tds_percentage').val(parseFloat(response.percentage) || 0);
            }
            
            const prevAmt = parseFloat(response.previous_amount) || 0;
            const formattedPrev = typeof formatIndianNumber === 'function' ? formatIndianNumber(prevAmt.toFixed(2)) : prevAmt.toFixed(2);
            $('#tds_previous_turnover').val(formattedPrev);
            
            this.defaultAccount = response.default_account;
            this.calculateTds();
            
            // Check if TDS Tax row is already present in the grid (e.g. Edit mode or manual entry)
            let hasTdsRowInGrid = false;
            this.tableManager.rowManager.getAllRows().each((i, tr) => {
                const accId = $(tr).find('.account-id').val();
                if (accId) {
                    // Check if it matches the default TDS account returned by the server
                    if (this.defaultAccount && this.defaultAccount.id == accId) {
                        hasTdsRowInGrid = true;
                    } else {
                        // Fallback heuristic
                        const acc = accountMasterData.find(a => a.id == accId);
                        if (acc && !acc.is_party_account && acc.text && acc.text.toLowerCase().includes('tds')) {
                            hasTdsRowInGrid = true;
                        }
                    }
                }
            });

            if (hasTdsRowInGrid) {
                this.tdsApplied = true;
                this.tdsDetails = {
                    party_id: $('#tds_party_id').val(),
                    expense_id: $('#tds_expense_id').val(),
                    tds_category_id: $('#tds_category_id').val(),
                    payee_category_id: $('#tds_payee_id').val(),
                    assessable_value: parseFloat($('#tds_assessable_value').val()) || 0,
                    percentage: parseFloat($('#tds_percentage').val()) || 0,
                    tds_amount: parseFloat($('#tds_amount').val()) || 0
                };
                
                // If it's a blur event, we don't spam the modal. Just log that TDS is applied and return.
                if (!fromStore) {
                    return true;
                }
                
                // Only pop open the modal if the user explicitly clicks Save/Update
                this.refreshTdsModalFromGrid();
                $('#tds_modal').modal('show');
                return true; // Halts the normal save flow until modal is confirmed
            }
            
            // Show confirmation using SweetAlert
            const limitAmt = parseFloat(response.threshold_limit) || 0;
            const totalAmtFloat = parseFloat(response.total_amount) || 0;
            const limit = typeof formatIndianNumber === 'function' ? formatIndianNumber(limitAmt.toFixed(2)) : limitAmt.toFixed(2);
            const totalAmt = typeof formatIndianNumber === 'function' ? formatIndianNumber(totalAmtFloat.toFixed(2)) : totalAmtFloat.toFixed(2);
            
            const calcTdsAmtFloat = parseFloat($('#tds_amount').val()) || 0;
            const calcTdsAmt = typeof formatIndianNumber === 'function' ? formatIndianNumber(calcTdsAmtFloat.toFixed(2)) : calcTdsAmtFloat.toFixed(2);
            const calcPerc = parseFloat($('#tds_percentage').val()) || 0;
            
            const confirmResult = await Swal.fire({
                title: 'TDS Calculation!',
                html: `
                    <div style="text-align: center; margin-bottom: 20px;">
                        <p style="font-size: 15px; color: #555; margin-bottom: 5px;">
                            Threshold Limit for <strong>${partyAccount.text}</strong> (PAN - ${panNumber}) is Rs. <strong>${limit}</strong>
                        </p>
                        <p style="font-size: 15px; color: #555; margin-bottom: 5px;">
                            Expense amount incurred with ${partyAccount.text} including this voucher is Rs. <strong>${totalAmt}</strong>
                        </p>
                        <p style="font-size: 16px; color: #d33; font-weight: bold; margin-bottom: 15px;">
                            Threshold Limit is being crossed ? YES
                        </p>
                        <div style="background-color: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #dee2e6; display: inline-block;">
                            <span style="font-size: 15px; font-weight: bold; color: #0056b3;">
                                Calculated TDS Deduction (${calcPerc}%): Rs. ${calcTdsAmt}
                            </span>
                        </div>
                    </div>
                    <hr>
                    <p style="font-size: 18px; font-weight: bold; text-align: center; margin-top: 15px;">
                        Calculate TDS for Current Voucher?
                    </p>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#dc3545',
                allowOutsideClick: false,
                allowEscapeKey: false,
                width: '600px'
            });
            
            if (confirmResult.isConfirmed) {
                // User said Yes -> calculate and apply immediately to grid
                const tdsAmt = parseFloat($('#tds_amount').val()) || 0;
                if (tdsAmt > 0) {
                    this.tdsDetails = {
                        party_id: $('#tds_party_id').val(),
                        expense_id: $('#tds_expense_id').val(),
                        tds_category_id: $('#tds_category_id').val(),
                        payee_category_id: $('#tds_payee_id').val(),
                        assessable_value: parseFloat($('#tds_assessable_value').val()) || 0,
                        percentage: parseFloat($('#tds_percentage').val()) || 0,
                        tds_amount: tdsAmt
                    };
                    
                    const partyRowEl = this.applyTdsToGrid();
                    this.tdsApplied = true;
                    
                    if (partyRowEl && this.tableManager) {
                        // Automatically balance the grid to populate the Party row's credit amount!
                        const creditInput = partyRowEl.find('.credit-amount');
                        if (!creditInput.val() || creditInput.val() === '') {
                            if (typeof this.tableManager.autoBalance === 'function') {
                                this.tableManager.autoBalance({ target: creditInput[0] });
                                this.tableManager.updateTotals(); // Ensure totals are updated after auto-balancing
                            }
                        }

                        // Open Reference Modal for the adjusted party row
                        if (this.tableManager.referenceManager) {
                            setTimeout(() => {
                                this.tableManager.referenceManager.open(partyRowEl);
                            }, 400); // Wait for SweetAlert to close fully
                        }
                    }
                }
                return true; 
            } else {
                this.tdsIgnored = true;
                return false;
            }
            
        } catch (error) {
            if (typeof hideLoader === 'function') hideLoader();
            console.error("TDS Check Error", error);
            // Fallback: If error, just return false to let the user save normally without breaking the UI
            return false;
        }
    }
    
    return false; // No TDS applicable, continue normal save
  }
  
  // confirmTds is no longer used since we apply directly on SweetAlert confirm

  
  applyTdsToGrid() {
      if (!this.defaultAccount || !this.defaultAccount.id) {
          console.warn("No default account set for this TDS category");
          return;
      }
      
      const partyId = this.tdsDetails.party_id;
      const allRows = this.tableManager.rowManager.getAllRows();
      let partyRowEl = null;
      let isPartyCredit = false;
      
      allRows.each((index, tr) => {
          const $tr = $(tr);
          const accId = $tr.find('.account-id').val();
          if (accId == partyId) {
              partyRowEl = $tr;
              if (parseFloat($tr.find('.credit-amount').val()) > 0) isPartyCredit = true;
          }
      });
      
      if (partyRowEl) {
          // Adjust party amount
          if (isPartyCredit) {
              let cr = parseFloat(partyRowEl.find('.credit-amount').val().replace(/,/g, '')) || 0;
              cr -= this.tdsDetails.tds_amount;
              partyRowEl.find('.credit-amount').val(cr > 0 ? cr.toFixed(2) : '');
          } else {
              let dr = parseFloat(partyRowEl.find('.debit-amount').val().replace(/,/g, '')) || 0;
              dr -= this.tdsDetails.tds_amount;
              partyRowEl.find('.debit-amount').val(dr > 0 ? dr.toFixed(2) : '');
          }
          
          // Find an empty row to insert the TDS Account
          let emptyRow = null;
          this.tableManager.rowManager.getAllRows().each((i, tr) => {
              if (!$(tr).find('.account-id').val()) {
                  emptyRow = $(tr);
                  return false;
              }
          });
          
          if (emptyRow) {
              // Set account ID (Select2)
              const accountSelect = emptyRow.find('.account-id');
              if (accountSelect.find(`option[value="${this.defaultAccount.id}"]`).length === 0) {
                  accountSelect.append(new Option(this.defaultAccount.name, this.defaultAccount.id, true, true));
              }
              accountSelect.val(this.defaultAccount.id).trigger('change');
              
              // Set Amount
              if (isPartyCredit) {
                  emptyRow.find('.dr-cr').val('CR');
                  emptyRow.find('.credit-amount').val(this.tdsDetails.tds_amount.toFixed(2));
                  emptyRow.find('.debit-amount').val('');
              } else {
                  emptyRow.find('.dr-cr').val('DR');
                  emptyRow.find('.debit-amount').val(this.tdsDetails.tds_amount.toFixed(2));
                  emptyRow.find('.credit-amount').val('');
              }
          } else {
              alert("Could not find an empty row to append TDS automatically.");
          }
          
          this.tableManager.updateTotals();
          return partyRowEl;
      }
      return null;
  }
}
