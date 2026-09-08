$(document).ready(function () {
  $("#payment_payable_form").on("submit", function (e) {
    e.preventDefault();

    getReferenceDetail();

  });

  bindSelect2();

  lockSelect2('#ledger_id');
  lockSelect2('#ledger_transaction_type');

  dateEventBind();


  $('#filter_by').on('change', function () {
    getReferenceDetail();
  })

  $('#on_advance_check').on('change', function () {
    let accountId = $("#account_id").val();
    if (!accountId) {
      Swal.fire({
        title: "Warning",
        html: `Please Select Account for On Advance Process!`,
        icon: "warning",
        confirmButtonText: "OK",
      });
      $(this).prop("checked", false);
      return;
    }
    let filterBy = $('#filter_by').val();
    if(filterBy != 'all'){
      $('#filter_by').val('cr').trigger('change');
    }
    getReferenceDetail();
  });

  $("#account_id").on("change", function () {
    let accountId = $(this).val();

    if (accountId) {
      $('#ledger_amount').prop('disabled', false);
      unlockSelect2('#ledger_id');
      unlockSelect2('#ledger_transaction_type');
      handleAccountId(accountId);
    } else {
      $('#ledger_amount').val(0.00);
      $('#ledger_amount').prop('disabled', true);
      $('#ledger_balance').val('0.00');
      $('#ledger_id').val(null).trigger('change');
      $('#ledger_transaction_type').val(null).trigger('change');
      lockSelect2('#ledger_id');
      lockSelect2('#ledger_transaction_type');
    }
  });

  // Select All checkbox
  $("#all_check").on("change", function () {
    let checked = $(this).is(":checked");
    $("#payment_payable_table_body .row-checkbox").prop("checked", checked);

    // if (checked) {
    //   $("#payment_payable_table_body tr").addClass("table-secondary");
    // } else {
    //   $("#payment_payable_table_body tr").removeClass("table-secondary");
    // }

    calculateCheckedTotal();
    SummaryManager.recalc();
    SummaryManager.render();
    if (checked) {
      const firstAccountId = $("#summary_table_body tr[data-summary-id]").first().data('summary-id');
      if (firstAccountId) scrollToSummaryRow(firstAccountId);
    }
  });

  // Individual row checkbox
  $(document).on("change", "#payment_payable_table_body .row-checkbox", function () {
    let total = $("#payment_payable_table_body .row-checkbox").length;
    let checked = $("#payment_payable_table_body .row-checkbox:checked").length;
    $("#all_check").prop("checked", total > 0 && total === checked);

    // if ($(this).is(":checked")) {
    //   $(this).closest('tr').addClass("table-secondary");
    // } else {
    //   $(this).closest('tr').removeClass("table-secondary");
    // }

    calculateCheckedTotal();
    SummaryManager.recalc();
    SummaryManager.render();

    const accountId = $(this).closest('tr').find('.account-id').data('account-id');
    scrollToSummaryRow(accountId);
  });

  // Mark dirty + highlight + recalculate when user actually types in pending-amount
  // Using keyup because input-event delegation is unreliable on contenteditable <td> elements
  $(document).on("keyup", "#payment_payable_table_body .pending-amount", function (e) {
    const navKeys = ['ArrowUp','ArrowDown','ArrowLeft','ArrowRight','Tab','Enter',
                     'Escape','Shift','Control','Alt','Meta','CapsLock','Home','End'];
    if (navKeys.includes(e.key)) return;
    $(this).data('dirty', true).css('background-color', 'rgba(13, 110, 253, 0.35)');
    
    calculateCheckedTotal();
    SummaryManager.recalc();
    SummaryManager.render();
  });

  // Click handler for bill-no links
  $(document).on("click", "#payment_payable_table_body .bill-no-link", function (e) {
    e.preventDefault();
    let referenceNumber = $(this).data("reference-number");
    handleFindBillNo(referenceNumber);
  });

  /**
   * CD Percent Change Event
   * -----------------------
   * Triggers when user edits CD %
   */
  $(document).on("blur", '.cd-percent', function () {

    let tr = $(this).closest('tr');
    let id = tr.data('id');
    let value = $(this).text().trim();
    let rowId = tr.data('row');   // use data-row instead of index for proper tracking

    updateCD(id, 'cdPercent', value, rowId);
  });

  /**
   * CD Amount Change Event
   * -----------------------
   * Triggers when user edits CD Amount
   */
  $(document).on("blur", '.cd-amount', function () {
    let tr = $(this).closest('tr');
    let id = tr.data('id');
    let value = $(this).text().trim();
    let rowId = tr.data('row');

    updateCD(id, 'cdAmount', value, rowId);
  });

  $(document).on("blur", '.pending-amount', function () {

    const isDirty = $(this).data('dirty');
    // $(this).removeData('dirty').css('background-color', '');

    // No typing happened — just navigated through, cell is already correct
    if (!isDirty) return;

    let tr = $(this).closest('tr');
    let id = tr.data('id');
    let strValue = $(this).text().trim();
    let rowId = tr.data('row');

    let bill = references.find(x => x.id == id);
    if (!bill) return;

    // _diffCd  : total CD adjustment already applied (newCd - originalCd from DB)
    // _payAmount: user-anchored explicit pay amount (null = not manually set yet)
    bill._diffCd = bill._diffCd ?? 0;
    bill._payAmount = bill._payAmount ?? null;

    let originalPending = parseFloat(bill.pending_amount) || 0;
    let numValue = parseFloat(strValue) || 0;
    let effectivePending = originalPending - bill._diffCd;  // pending after CD adjustment

    let isDelta = strValue.startsWith('-') || strValue.startsWith('+');

    if (isDelta) {
      // Delta mode: e.g. "-200" means reduce pay amount by 200 from current.
      let base = bill._payAmount !== null ? bill._payAmount : effectivePending;
      bill._payAmount = base + numValue;  // numValue is negative for '-'
    } else {
      // Absolute mode: user typed exact amount they want to pay.
      bill._payAmount = numValue;
    }

    // Clamp pay amount within [0, effectivePending]
    if (bill._payAmount > effectivePending) bill._payAmount = effectivePending;
    if (bill._payAmount < 0) bill._payAmount = 0;

    bill._updatedPendingAmount = bill._payAmount;

    let row = $(`#row_${rowId}`);
    row.find('.row-checkbox').prop('checked', true).trigger('change');

    refreshRow(rowId, bill);
    calculateCheckedTotal();
    if (typeof SummaryManager !== 'undefined') {
      SummaryManager.recalc();
      SummaryManager.render();
      scrollToSummaryRow(tr.find('.account-id').data('account-id'));
    }
  });

  // Recalculate days when show-date changes, update bg color, tick checkbox
  $(document).on("blur", "#payment_payable_table_body .show-date", function () {
    const $td  = $(this);
    const $tr  = $td.closest('tr');

    const showDateStr    = $td.text().trim();
    const originalStr    = $td.data('original') ?? '';

    // Only act if the date was actually changed
    if (showDateStr === originalStr) return;

    const paymentDateStr = $('#payment_date').val().trim();

    if (!showDateStr || !isValidDateDMY(showDateStr))    return;
    if (!paymentDateStr || !isValidDateDMY(paymentDateStr)) return;

    const showDate = new Date(formatDateToYMD(showDateStr));
    const payDate  = new Date(formatDateToYMD(paymentDateStr));
    const diffDays = Math.round(Math.abs(payDate - showDate) / 86400000);

    const $daysTd = $tr.find('td.days');
    $daysTd.text(diffDays);

    if (diffDays > 10) {
      $daysTd.addClass('bg-warning bg-opacity-50 text-dark semi-bold');
    } else {
      $daysTd.removeClass('bg-warning bg-opacity-50 text-dark semi-bold');
    }

    $tr.find('.row-checkbox').prop('checked', true).trigger('change');
  });

  // Highlight focused row and select all text in contenteditable cells
  $(document).on("focus", "#payment_payable_table_body .show-date, #payment_payable_table_body .pending-amount, #payment_payable_table_body .cd-percent", function () {
    $("#payment_payable_table_body tr.nav-hover").removeClass("nav-hover");
    $(this).closest("tr").addClass("nav-hover");

    const el = this;
    if (el.isContentEditable && el.getAttribute('contenteditable') === 'true') {
      setTimeout(() => {
        if (document.activeElement === el) {
          document.execCommand('selectAll', false, null);
        }
      }, 0);
    }
  });

  // Arrow key and Enter navigation for show-date, pending-amount, cd-percent
  $(document).on("keydown", "#payment_payable_table_body .show-date, #payment_payable_table_body .pending-amount, #payment_payable_table_body .cd-percent", function (e) {
    let $this = $(this);
    let $tr = $this.closest("tr");

    let navClasses = ['.show-date', '.pending-amount', '.cd-percent'];
    let currentClassIndex = navClasses.findIndex(c => $this.hasClass(c.replace('.', '')));

    if (currentClassIndex === -1) return;

    let key = e.key;
    if (e.which === 13 || e.keyCode === 13) {
      key = 'Enter';
    }

    if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Enter'].includes(key)) {

      let $nextElement = null;

      if (key === 'ArrowLeft') {
        e.preventDefault();
        $nextElement = findPrevFocusableOnArrow($tr, currentClassIndex, navClasses);
      } else if (key === 'ArrowRight') {
        e.preventDefault();
        $nextElement = findNextFocusableOnEnter($tr, currentClassIndex, navClasses);
      } else if (key === 'Enter') {
        e.preventDefault();
        $nextElement = findNextFocusableOnEnter($tr, currentClassIndex, navClasses);
        if (!$nextElement) {
          $('#bank_id').select2('open');
          return;
        }
      } else if (key === 'ArrowUp') {
        e.preventDefault();
        let $prevRow = $tr.prevAll("tr").filter(function () {
          let $cell = $(this).find(navClasses[currentClassIndex]);
          return $cell.length && $cell.attr('contenteditable') === 'true';
        }).first();
        if ($prevRow.length) $nextElement = $prevRow.find(navClasses[currentClassIndex]);
      } else if (key === 'ArrowDown') {
        e.preventDefault();
        let $nextRow = $tr.nextAll("tr").filter(function () {
          let $cell = $(this).find(navClasses[currentClassIndex]);
          return $cell.length && $cell.attr('contenteditable') === 'true';
        }).first();
        if ($nextRow.length) $nextElement = $nextRow.find(navClasses[currentClassIndex]);
      }

      if ($nextElement && $nextElement.length) {
        focusAndSelectAll($nextElement);
      }
    }
  });

  // Prevent HTML paste in contenteditable cells — plain text only
  $(document).on("paste", "#payment_payable_table_body [contenteditable='true']", function (e) {
    e.preventDefault();
    const text = (e.originalEvent.clipboardData || window.clipboardData).getData('text/plain');
    document.execCommand('insertText', false, text);
  });

  // Enter key handler for find_bill_no input
  $("#find_bill_no").on("keydown", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();
      let referenceNumber = $(this).val().trim();
      handleFindBillNo(referenceNumber);
    }
  });

  $('#create_payment_voucher').on('click', function (e) {
    e.preventDefault();
    const totalAmount = $('#payment_total').val() || '0.00';
    const checkedCount = $('#payment_payable_table_body .row-checkbox:checked').length;

    // Block if total is negative
    const totalAmtRaw = parseFloat(totalAmount.replace(/,/g, ''));
    if (totalAmtRaw < 0) {
      Swal.fire({
        title: 'Cannot Post Voucher!',
        html: `<div class="text-start">
                   <p>The total selected bill amount is <b class="text-danger">₹ ${totalAmtRaw.toFixed(2)}</b> (negative).</p>
                   <p>This happens when <b>Debit (DR) entries</b> selected exceed <b>Credit (CR) bills</b>.</p>
                   <p class="mb-0 text-muted">Please deselect debit entries or select matching credit invoices so the total is positive before posting.</p>
               </div>`,
        icon: 'error',
        confirmButtonText: 'OK',
      });
      return;
    }

    Swal.fire({
      title: 'Confirm Payment',
      html: `You are about to process payment for <b>${checkedCount}</b> bill(s) with a total amount of <b>₹ ${totalAmount}</b>.<br><br>Do you want to proceed?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, Proceed',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#0d6efd',
      cancelButtonColor: '#6c757d',
    }).then((result) => {
      if (result.isConfirmed) {
        savePayment();
      }
    });
  });

  $('#hold_bill').on('click', function (e) {
    e.preventDefault();
    const checkedCount = $('#payment_payable_table_body .row-checkbox:checked').length;

    if (checkedCount === 0) {
      Swal.fire({
        title: 'Error!',
        text: 'Please select at least one bill to hold.',
        icon: 'error',
        confirmButtonText: 'OK',
      });
      return;
    }

    let referenceIds = [];
    $("#payment_payable_table_body .row-checkbox:checked").each(function () {
      const $tr = $(this).closest("tr");
      referenceIds.push($tr.data("reference-id"));
    });

    Swal.fire({
      title: 'Hold Bills?',
      html: `Are you sure you want to hold <b>${checkedCount}</b> bill(s)?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, Hold',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#0d6efd',
      cancelButtonColor: '#6c757d',
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: typeof holdBillUrl !== 'undefined' ? holdBillUrl : '',
          method: "POST",
          data: {
            ids: referenceIds,
            _token: $('meta[name="csrf-token"]').attr('content')
          },
          beforeSend: function () {
            showLoader("Holding bills...");
          },
          success: function (response) {
            if (response.success) {
              showToast("success", response.message);
              getReferenceDetail();
            } else {
              showToast("error", response.message);
            }
          },
          error: function (xhr) {
            showToast("error", "An error occurred while holding bills.");
          },
          complete: function () {
            hideLoader();
          },
        });
      }
    });
  });

  setTimeout(() => {
    $("#file_number").focus();
    const today = currentDate();
    $('#payment_date').val(today);
    $('#day').val(getDayFromDate(today));
  }, 50);
});

let references = [];
let summaryData = [];

// ── Contenteditable cell helpers ──────────────────────────────────────────────

function getTextVal($el) {
  const el = $el[0];
  if (!el) return '';
  return el.isContentEditable ? $el.text().trim() : ($el.val() || '');
}

function setTextVal($el, val) {
  const el = $el[0];
  if (!el) return;
  if (el.isContentEditable) $el.text(val);
  else $el.val(val);
}

function isReadonly($el) {
  const el = $el[0];
  if (!el) return false;
  return el.isContentEditable
    ? $el.attr('contenteditable') === 'false'
    : $el.prop('readonly');
}

function focusAndSelectAll($el) {
  const el = $el[0];
  if (!el) return;
  el.focus();
  if (el.isContentEditable) {
    const range = document.createRange();
    range.selectNodeContents(el);
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
  } else if (!$el.prop('readonly')) {
    $el.select();
  }
}

function caretAtStart(el) {
  if (el.isContentEditable) {
    const sel = window.getSelection();
    if (!sel || !sel.rangeCount) return true;
    return sel.getRangeAt(0).startOffset === 0;
  }
  return el.selectionStart === 0;
}

function caretAtEnd(el) {
  if (el.isContentEditable) {
    const sel = window.getSelection();
    if (!sel || !sel.rangeCount) return true;
    const range = sel.getRangeAt(0);
    return range.collapsed && range.endOffset === el.textContent.length;
  }
  return el.selectionEnd === el.value.length;
}

function isAllSelected(el) {
  if (el.isContentEditable) {
    const sel = window.getSelection();
    if (!sel || !sel.rangeCount) return false;
    const range = sel.getRangeAt(0);
    return !range.collapsed && range.toString().length === el.textContent.length;
  }
  return el.selectionStart === 0 && el.selectionEnd === el.value.length;
}

// ─────────────────────────────────────────────────────────────────────────────

function isCellFocusable($cell) {
  return $cell.length && $cell.is(':visible') && $cell.attr('contenteditable') === 'true';
}

function findNextFocusableOnEnter($tr, currentClassIndex, navClasses) {
  for (let i = currentClassIndex + 1; i < navClasses.length; i++) {
    let $cell = $tr.find(navClasses[i]);
    if (isCellFocusable($cell)) return $cell;
  }

  let $nextRow = $tr.nextAll('tr').filter(function () {
    return $(this).find(navClasses[0]).length > 0;
  }).first();

  while ($nextRow.length) {
    for (let i = 0; i < navClasses.length; i++) {
      let $cell = $nextRow.find(navClasses[i]);
      if (isCellFocusable($cell)) return $cell;
    }
    $nextRow = $nextRow.nextAll('tr').filter(function () {
      return $(this).find(navClasses[0]).length > 0;
    }).first();
  }

  return null;
}

function findPrevFocusableOnArrow($tr, currentClassIndex, navClasses) {
  for (let i = currentClassIndex - 1; i >= 0; i--) {
    let $cell = $tr.find(navClasses[i]);
    if (isCellFocusable($cell)) return $cell;
  }

  let $prevRow = $tr.prevAll('tr').filter(function () {
    return $(this).find(navClasses[0]).length > 0;
  }).first();

  while ($prevRow.length) {
    for (let i = navClasses.length - 1; i >= 0; i--) {
      let $cell = $prevRow.find(navClasses[i]);
      if (isCellFocusable($cell)) return $cell;
    }
    $prevRow = $prevRow.prevAll('tr').filter(function () {
      return $(this).find(navClasses[0]).length > 0;
    }).first();
  }

  return null;
}

function getReferenceDetail() {

  let paymentVoucherDate = $('#payment_date').val().trim();

  if (paymentVoucherDate === "") {
    showToast('error', 'Payment Date is required');
    $('#payment_date').focus();
    return;
  }
  if (!isValidDateDMY(paymentVoucherDate)) {
    showToast('error', 'Enter valid Payment Date');
  }
  paymentVoucherDate = formatDateToYMD(paymentVoucherDate);

  let payload = {
    account_id: $("#account_id").val(),
    file_number: $("#file_number").val(),
    filter_by: $("#filter_by").val(),
    on_advance: $("#on_advance_check").is(":checked") ? 1 : 0,    
    payment_voucher_date: paymentVoucherDate
  };


  $.ajax({
    url: getPaymentPayableDataUrl,
    method: "GET",
    data: payload,
    beforeSend: function () {
      showLoader("Loading payable data...");
    },
    success: function (response) {
      references = [];
      if (response.success) {
        references.push(...response.data.reference)
        $("#payment_payable_table_body").html(response.data.html);
        $("#unique_request_id").val(response.data.unique_request_id);
        $("#all_check").prop("checked", false);
        calculateCheckedTotal();
        SummaryManager.data = [];
        SummaryManager.render();
        setTimeout(() => {
          const $firstRow = $("#payment_payable_table_body tr[data-row='1']");
          const $showDate = $firstRow.find('.show-date');
          const isReadonly = $showDate.attr('contenteditable') === 'false';
          $firstRow.find(isReadonly ? '.pending-amount' : '.show-date').trigger('focus');
        }, 80);

      } else {
        showToast("error", response.message);
      }
    },
    error: function (xhr) {
      references = [];
      showToast("error", "An error occurred while processing the payment.");
    },
    complete: function () {
      hideLoader();
    },
  });
}


function updateCD(id, field, value, rowId) {

  // Find bill by ID
  let bill = references.find(x => x.id == id);
  if (!bill) return;

  let billAmount = parseFloat(bill.taxable_amount) || 0;
  let originalPending = parseFloat(bill.pending_amount) || 0;
  let originalCdAmount = parseFloat(bill.cd) || 0;

  // Capture the OLD diffCd before recomputing, so we know the incremental CD change.
  let oldDiffCd = bill._diffCd ?? 0;
  bill._payAmount = bill._payAmount ?? null;  // null = no manual hold set
  bill._updatedCdPercent = bill._updatedCdPercent ?? 0;
  bill._updatedCdAmount = bill._updatedCdAmount ?? 0;
  bill._diffCd = 0;   // recomputed below as newCd - originalCd (from DB)

  // ----------- CD PERCENT UPDATED -------------
  if (field === "cdPercent") {
    bill._updatedCdPercent = parseFloat(value) || 0;
    bill._updatedCdAmount = Math.round((billAmount * bill._updatedCdPercent) / 100);  // always rounded
    bill._diffCd = bill._updatedCdAmount - originalCdAmount;
  }

  // ----------- CD AMOUNT UPDATED -------------
  else if (field === "cdAmount") {
    bill._updatedCdAmount = Math.round(parseFloat(value) || 0);  // always rounded
    bill._updatedCdPercent = billAmount > 0
      ? (bill._updatedCdAmount / billAmount) * 100
      : 0;
    bill._diffCd = bill._updatedCdAmount - originalCdAmount;
  }

  // ---- Pending Calculation ----
  // effectivePending = bill value after the new CD is applied.
  let effectivePending = originalPending - bill._diffCd;

  if (bill._payAmount !== null) {
    // User has set an explicit pay amount (partial payment / hold).
    // RULE: Hold amount (in rupees) must stay fixed. So pay amount shrinks by
    // however much MORE CD was added compared to last time _payAmount was set.
    let cdDelta = bill._diffCd - oldDiffCd;  // incremental CD change this call
    bill._payAmount = bill._payAmount - cdDelta;
    // Clamp within [0, effectivePending]
    if (bill._payAmount > effectivePending) bill._payAmount = effectivePending;
    if (bill._payAmount < 0) bill._payAmount = 0;
    bill._updatedPendingAmount = bill._payAmount;
  } else {
    // No manual hold yet — pay the full effectivePending.
    bill._updatedPendingAmount = effectivePending;
  }

  // Update UI
  refreshRow(rowId, bill);
  calculateCheckedTotal();

  if (typeof SummaryManager !== 'undefined') {
    SummaryManager.recalc();
    SummaryManager.render();
  }
}

/**
 * Refresh a specific table row
 */
function refreshRow(rowId, billData) {

  let row = $(`#row_${rowId}`);

  if (!row.length || !billData) return;

  if (billData._updatedCdPercent !== undefined) {
    row.find('.cd-percent').text(billData._updatedCdPercent.toFixed(2));
  }
  if (billData._updatedCdAmount !== undefined) {
    row.find('.cd-amount').text(billData._updatedCdAmount.toFixed(2));
  }
  row.find('.pending-amount').text((billData._updatedPendingAmount || 0).toFixed(2));
}

function handleAccountId(accountId) {
  if (!accountId) {
    $("#ledger_balance").val("0.00");
    return;
  }

  closingBalance(accountId, closingBalanceUrl, function (result) {
    let data = result.data;

    let row = data[accountId];

    if (!row) {
      $("#ledger_balance").val("0.00 Dr");
      return;
    }

    let closing = row.closing;

    let formattedBalance = amountDirection(closing);


    $("#ledger_balance").val(formattedBalance);
  });
}

function dateEventBind() {
  new DateInput("#payment_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
  new DateInput(".show-date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

  // check within financial year date
  $("#payment_date").on("blur", function () {
    let invDate = formatDateToYMD($(this).val());
    if (!invDate) return;
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

  //   $("#payment_date").val(currentDate());
  //   let todayDay = getDayFromDate(currentDate());
  //   $("#day").val(todayDay);

  $("#payment_date").on("blur", function () {
    let changeDate = $(this).val();

    if (changeDate) {
      let getDay = getDayFromDate(changeDate); // Your function
      $("#day").val(getDay);
      if (getDay === "Sunday") {
        Swal.fire({
          title: "Warning!",
          html: `Payment is not permitted on <b style="color:red;">Sunday</b>. Please choose another date.`,
          icon: "warning",
          confirmButtonText: "OK",
          focusConfirm: true,
        });
      }
    }
  });

  // check within financial year date
  // $(document).on("blur", ".show-date", function () {
  //   let invDate = formatDateToYMD($(this).text().trim());
  //   if (!invDate) return;
  //   if (!isWithinFY(invDate, FINANCIAL_YEAR_START, FINANCIAL_YEAR_END)) {
  //     showToast(
  //       "error",
  //       `Date must be within Financial Year:<br>(${formatDateToDMY(
  //         FINANCIAL_YEAR_START,
  //       )} to ${formatDateToDMY(FINANCIAL_YEAR_END)})`,
  //       9000,
  //     );
  //     $(this).text("");
  //   }
  // });
}

function calculateCheckedTotal() {
  let total = 0;
  $("#payment_payable_table_body .row-checkbox:checked").each(function () {
    let amount = parseFloat($(this).closest("tr").find(".pending-amount").text().trim()) || 0;
    let direction = $(this).closest("tr").data("direction");
    if (direction === "debit") {
      total -= amount;
    } else {
      total += amount;
    }
  });
  let formatted = total.toFixed(2);
  $("#payment_total").val(formatIndianNumber(formatted)).attr("data-value", formatted);
}

function bindSelect2() {
  var selects = [
    "#account_id",
    "#filter_by",
    "#bank_id",
    "#ledger_id",
    "#ledger_transaction_type",
  ];
  selects.forEach(function (el) {
    $(el).select2({
      theme: "bootstrap-5",
    });
  });

  $(document).on("select2:open", function (e) {
    var $select = $(e.target);
    var $search = $select
      .data("select2")
      .$dropdown.find(".select2-search__field");
    $search
      .off("keydown.select2Enter")
      .on("keydown.select2Enter", function (event) {
        if (event.key === "Enter") {
          event.preventDefault();
          $select.select2("close");
          moveFocusToNextField($select);
        }
      });
  });
}

function handleAllCheckboxChange(e, el) {
  const $rows = $("#payment_payable_table tbody tr");

  if (el.checked) {
    $rows.find(".row-checkbox").prop("checked", true);
  } else {
    $rows.find(".row-checkbox").prop("checked", false);
  }

  // Always do fresh calculation
  SummaryManager.recalc();
  SummaryManager.render();
}

function handleFindBillNo(referenceNumber) {
  if (!referenceNumber) return;

  const allCheckboxes = document.querySelectorAll(".row-checkbox");
  if (allCheckboxes.length === 0) {
    showToast("error", "Table is empty. No bills available.");
    return;
  }

  const checkbox = document.querySelector(
    `.row-checkbox[data-ref="${referenceNumber}"]`
  );

  if (checkbox) {
    checkbox.checked = !checkbox.checked; // Toggle the state
    checkbox.focus();
    checkbox.dispatchEvent(new Event("change", { bubbles: true }));
    // calculateTotal();
  } else {
    showToast("error", `Bill No. "${referenceNumber}" not found.`);
  }

  setTimeout(() => {
    requestAnimationFrame(() => {
      const input = document.getElementById("find_bill_no");
      input.focus();
      input.select();
    });
  }, 200);
}

const SummaryManager = {
  data: [],

  // add(account_id, account_name, account_city, amount) {
  //   amount = parseFloat(amount);

  //   const exist = this.data.find((x) => x.account_id == account_id);

  //   if (exist) {
  //     exist.amount = (parseFloat(exist.amount) + amount).toFixed(2);
  //   } else {
  //     this.data.push({
  //       account_id,
  //       account_name,
  //       account_city,
  //       amount: amount.toFixed(2),
  //     });
  //   }
  // },

  // remove(account_id, amount) {
  //   amount = parseFloat(amount);

  //   const exist = this.data.find((x) => x.account_id == account_id);
  //   if (!exist) return;

  //   let newAmount = parseFloat(exist.amount) - amount;

  //   if (newAmount <= 0) {
  //     this.data = this.data.filter((x) => x.account_id != account_id);
  //   } else {
  //     exist.amount = newAmount.toFixed(2);
  //   }
  // },

  // clear() {
  //   this.data = [];
  // },

  recalc() {
    const summaryMap = new Map();

    $("#payment_payable_table_body .row-checkbox:checked").each(function () {
      const $tr = $(this).closest("tr");

      const account_id = $tr.find(".account-id").data("account-id");
      const account_name = $tr.find(".account-id").data("account-name") ?? "--";
      const account_city = $tr.find(".account-city").data("account-city") ?? "--";
      const direction = $tr.data("direction");
      const amount = parseFloat(
        ($tr.find(".pending-amount").text().trim() || "0").replace(/,/g, "")
      );

      if (!summaryMap.has(account_id)) {
        summaryMap.set(account_id, {
          account_id,
          account_name,
          account_city,
          amount: 0,
        });
      }

      const entry = summaryMap.get(account_id);

      if (direction === "debit") {
        entry.amount -= amount;
      } else {
        entry.amount += amount;
      }
    });

    this.data = Array.from(summaryMap.values());

    this.data.forEach((x) => {
      x.amount = x.amount.toFixed(2);
    });
  },

  render() {
    let html = "";

    if (this.data.length === 0) {
      html = `
        <tr>
          <td colspan="4" class="text-center text-muted py-2">
            -- No Data Found --
          </td>
        </tr>
      `;
    } else {
      let grandTotal = 0;

      this.data.forEach((p, i) => {
        grandTotal += parseFloat(p.amount);
        let amount = formatIndianNumber(Number(p.amount).toFixed(2));
        html += `
          <tr data-summary-id="${p.account_id}">
            <td class="text-center">${i + 1}.</td>
            <td>${p?.account_name}</td>
            <td>${p?.account_city}</td>
            <td class="text-end fw-bold">${amount}</td>
          </tr>
        `;
      });

      html += `
        <tr class="table-primary fw-bold border-top border-2" style="position:sticky;bottom:0;">
          <td colspan="3" class="text-end">Total</td>
          <td class="text-end">${formatIndianNumber(grandTotal.toFixed(2))}</td>
        </tr>
      `;
    }

    $("#summary_table_body").html(html);
  },
};

function scrollToSummaryRow(accountId) {
  const $row = $(`#summary_table_body tr[data-summary-id="${accountId}"]`);
  if (!$row.length) return;

  $row[0].scrollIntoView({ block: 'center', behavior: 'smooth' });

  $row.css({ 'background-color': '#ffc107', 'transition': 'background-color 0.3s ease' });
  setTimeout(() => $row.css('background-color', ''), 1200);
}

function savePayment() {
  let references = [];

  const accountId = $("#account_id").val();
  const filterBy = $("#filter_by").val();
  const onAdvance = $("#on_advance_check").is(":checked") ? 1 : 0;
  const bankId = $("#bank_id").val();
  const ledgerTransactionType = $("#ledger_transaction_type").val();
  const ledgerId = $("#ledger_id").val();
  const ledgerAmount = $("#ledger_amount").val();
  const fileNumber = $("#file_number").val();
  let paymentDate = $("#payment_date").val();

  // ── Frontend required-field guards ───────────────────────
  if (!paymentDate || !paymentDate.trim()) {
    showToast("error", "Payment Date is required. Please enter a valid date.");
    $("#payment_date").focus();
    return;
  }

  if (!bankId) {
    showToast("error", "Bank / Cash account is required. Please select a bank.");
    $("#bank_id").select2("open");
    return;
  }

  // Always send date to backend in Y-m-d format
  paymentDate = formatDateToYMD(paymentDate);

  $("#payment_payable_table_body .row-checkbox:checked").each(function () {
    const $tr = $(this).closest("tr");

    const reference_id = $tr.data("reference-id");
    const account_id = $tr.find(".account-id").data("account-id");
    const source_id = $tr.data("source-id");
    const source_type = $tr.data("source-type");
    const cd_percent = $tr.find(".cd-percent").text().trim();
    const cd_amount = $tr.find(".cd-amount").text().trim();
    const voucher_id = $tr.data("voucher-id");
    let showDate = $tr.find(".show-date").text().trim();
    let billDate = $tr.find(".reference_date").text().trim();

    showDate = showDate ? formatDateToYMD(showDate) : formatDateToYMD(billDate);

    const pendingAmount = parseFloat(
      ($tr.find(".pending-amount").text().trim() || "0").replace(/,/g, "")
    );

    references.push({
      reference_id: reference_id,
      source_type: source_type,
      source_id: source_id,
      account_id: account_id,
      voucher_id: voucher_id,
      payment_amount: pendingAmount,
      cd_percent: cd_percent,
      cd_amount: cd_amount,
      show_date: showDate,
    });
  });

  if (references.length === 0) {
    showToast("error", "Please select at least one reference.");
    return;
  }

  // ── Block if total amount is negative ────────────────────
  const totalAmtRaw = parseFloat(($('#payment_total').val() || '0').replace(/,/g, ''));
  if (totalAmtRaw < 0) {
    Swal.fire({
      title: 'Cannot Post Voucher!',
      html: `<div class="text-start">
                 <p>The total selected bill amount is <b class="text-danger">₹ ${totalAmtRaw.toFixed(2)}</b> (negative).</p>
                 <p>This happens when <b>Debit (DR) entries</b> selected exceed <b>Credit (CR) bills</b>.</p>
                 <p class="mb-0 text-muted">Please deselect debit entries or select matching credit invoices so the total is positive before posting.</p>
             </div>`,
      icon: 'error',
      confirmButtonText: 'OK',
    });
    return;
  }

  let paymentData = JSON.stringify({
    account_id: accountId,
    file_number: fileNumber,
    filter_by: filterBy,
    on_advance: onAdvance,
    references: references,
    bank_id: bankId,
    ledger_transaction_type: ledgerTransactionType,
    ledger_id: ledgerId,
    ledger_amount: ledgerAmount,
    payment_date: paymentDate,
    unique_request_id: $("#unique_request_id").val(),
  });

  $.ajax({
    url: savePaymentPayableUrl,
    type: "POST",
    data: paymentData,
    beforeSend: function () {
      showLoader("Saving payment voucher...");
    },
    contentType: "application/json",
    success: function (response) {
      if (response.success) {
        Swal.fire({
          title: "Success!",
          text: response.message,
          icon: "success",
          confirmButtonText: "OK"
        });
        getReferenceDetail();
      } else {
        Swal.fire({
          title: "Error!",
          text: response.message,
          icon: "error",
          confirmButtonText: "OK"
        });
      }
    },
    error: function (xhr) {
      const json = xhr.responseJSON;
      const msg  = json?.message
                || (json?.errors ? Object.values(json.errors).flat().join('\n') : null)
                || "An error occurred while processing the payment.";
      Swal.fire({
        title: "Error!",
        text: msg,
        icon: "error",
        confirmButtonText: "OK",
      });
    },
    complete: function () {
      hideLoader();
    },
  });
}
$(document).on('click', '#printPendingPayment', function(e) {
    e.preventDefault();
    let btn = $(this);
    let originalHtml = btn.html();
    btn.html('<i class="fas fa-spinner fa-spin text-white me-2"></i><span> Printing...</span>').prop('disabled', true);

    $.ajax({
        url: pendingPaymentUrl,
        type: 'GET',
        success: function(response) {
            btn.html(originalHtml).prop('disabled', false);
            let printWindow = window.open('', '_blank');
            if (printWindow) {
                printWindow.document.write(response);
                printWindow.document.close();
            } else {
                Swal.fire({
                    title: "Error!",
                    text: "Please allow popups for this website",
                    icon: "error",
                    confirmButtonText: "OK",
                });
            }
        },
        error: function(xhr) {
            btn.html(originalHtml).prop('disabled', false);
            const json = xhr.responseJSON;
            const msg  = json?.message
                      || (json?.errors ? Object.values(json.errors).flat().join('\n') : null)
                      || "An error occurred while processing the payment.";
            Swal.fire({
                title: "Error!",
                text: msg,
                icon: "error",
                confirmButtonText: "OK",
            });
        }
    });
});
