// Payment Online / RTGS — module JS
$(document).ready(function () {
  new DateInput("#rtgs_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

  $("#rtgs_date").on("blur", function () {
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

  $("#rtgs_chq_alpha_number").on("keydown", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();
      setTimeout(() => {
        $("#rtgs_chq_number").focus();
      }, 50);
    }
  });

  $("#rtgs_chq_number").on("keydown", function (e) {
    $('#cheque_number').val(''); // clear file number field when user starts entering cheque number
    if (e.key === "Enter") {
      e.preventDefault();
      setTimeout(() => {
        $("#rtgs_save_update_btn").focus();
      }, 50);
      saveOrUpdateCheque();
    }
  });

  $('#cheque_number').on('keydown', function (e) {
    $('#rtgs_chq_number').val(''); // clear cheque number field when user starts entering file number
  });

  $("#rtgs_save_update_btn").on("click", function () {
    saveOrUpdateCheque();
  });

  $("#rtgs_cheque_print_btn").on("click", function () {
    chequePrint();
  });

  $('#rtgs_register_print_btn').on('click', function() {
    printPaymentRegister();
  });

  $('#rtgs_print_btn').on('click', function() {
    printRtgsForm();
  });

  $('#rtgs_get_chq_btn').on('click', function() {
    getOldChequeData();
  });

  $('#rtgs_send_bank_email_btn').on('click', function() {
    sendToBankEmail();
  });

  $('#rtgs_payment_advice_all_btn').on('click', function() {
    printPaymentAdvice();
  });

  $('#rtgs_payment_advice_email_btn').on('click', function () {
    paymentAdviceEmail();
  });

  $('#btn_entry_reverse').on('click', function () {
    deletePaymentVouchers();
  });

  bindSelect2();
  bindShowButton();
  bindCheckboxEvents();
  bindRefNoFind();
  bindBankChqName();
  initRtgsEmailModal();

  setTimeout(() => {
    $('#rtgs_date').focus();
  }, 20);
});

function getOldChequeData() {
    const paymentDate = $("#rtgs_date").val().trim();
    const bankId      = $("#bank_id").val() || null;
    const chequeNo    = $("#cheque_number").val().trim() || null;

    if (!paymentDate) {
        showToast("error", "Please enter Payment Date.", 4000);
        $("#rtgs_date").focus();
        return;
    }
    if (!bankId) {
        showToast("error", "Please select a Bank.", 4000);
        $("#bank_id").focus();
        return;
    }
    if (!chequeNo) {
        showToast("error", "Please enter Cheque No. to search.", 4000);
        $("#cheque_number").focus();
        return;
    }

    showLoader();

    $.ajax({
        url: rtgsFetchByChequeUrl,
        method: "GET",
        data: {
            cheque_no: chequeNo,
            bank_id:   bankId,
            date:      formatDateToYMD(paymentDate),
        },
        success: function (res) {
            hideLoader();
            if (!res.success) {
                Swal.fire({ icon: "error", title: "Not Found", text: res.message });
                return;
            }
            $("#rtgs_table_body").html(res.data.html);
            $("#rtgs_chq_number").val(res.data.cheque_no);
            $("#chk_all_head, #chk_all").prop("checked", false).prop("indeterminate", false);
            updateTotals();
            showToast("success", "Cheque data loaded.", 3000);
        },
        error: function (xhr) {
            hideLoader();
            var msg = xhr.responseJSON?.message || "Failed to load cheque data.";
            Swal.fire({ icon: "error", title: "Error", text: msg });
        },
    });
}


function printPaymentRegister(){
    const paymentDate = $("#rtgs_date").val().trim();
    const bankId = $("#bank_id").val() || null;
    const chequeNo = $("#rtgs_chq_number").val().trim() || $('#cheque_number').val().trim() || null;

    if (!paymentDate) {
        Swal.fire({
            icon: "error",
            title: "Payment Date Required",
            text: "Please enter the Payment Date before printing the register.",
            confirmButtonText: "OK",
        });
        $("#rtgs_date").focus();
        return;
    }

    if (!bankId) {
        Swal.fire({
            icon: "error",
            title: "Bank Required",
            text: "Please select a Bank before printing the register.",
            confirmButtonText: "OK",
        });
        $("#bank_id").focus();
        return;
    }

    if (!chequeNo) {
        Swal.fire({
            icon: "error",
            title: "Cheque Number Required",
            text: "Please enter the Cheque Number before printing the register.",
            confirmButtonText: "OK",
        });
        $("#rtgs_chq_number").focus();
        return;
    }

    $.ajax({
        url: rtgsRegisterPrintUrl,
        method: "GET",
        data: {
            date: formatDateToYMD(paymentDate),
            bank_id: bankId,
            cheque_no: chequeNo,
        },
        beforeSend: () => showLoader('Preparing register print...'),
        success: function (res) {
            hideLoader();
            if (!res.success) {
                Swal.fire({
                    icon: "error",
                    title: "No Data",
                    text: res.message || "Failed to prepare register print.",
                });
                return;
            }
            
            const blob = new Blob([res.data.register_print_html], { type: "text/html" });
            const url = URL.createObjectURL(blob);
            window.open(url, "_blank");
        },
        error: function (xhr) {
            hideLoader();
            var msg = xhr.responseJSON?.message || "Failed to prepare register print. Please try again.";
            Swal.fire({ icon: "error", title: "Error", text: msg });
        },
    });
}

function chequePrint() {
  const paymentDate = $("#rtgs_date").val().trim()
    ? formatDateToYMD($("#rtgs_date").val().trim())
    : null;
  const bankId = $("#bank_id").val() || null;
  const chequeNo = $("#rtgs_chq_number").val().trim() || null;

  //  first validate in row and chequeNo field same cheque number 
    let mismatch = false;
    $(".rtgs-row-chk:checked").each(function () {      
    let rowChqNo = $(this).closest("tr").find(".chq-number-td").text().trim();
        if (rowChqNo !== chequeNo) {
        mismatch = true;
        return false; // break loop
        }
    });

    if (mismatch) { 
        Swal.fire({
            icon: "error",
            title: "Cheque Number Mismatch",
            text: "The Cheque Number in selected rows does not match the Cheque Number field. Please ensure they are the same before printing.",
            confirmButtonText: "OK",
        });
        return;
     }

    if(!chequeNo) {
        Swal.fire({
            icon: "error",
            title: "Cheque Number Required",
            text: "Please enter the Cheque Number before printing.",
            confirmButtonText: "OK",
        });
        $("#rtgs_chq_number").focus();
        return;
    }

    // if no payment record selected
    if ($(".rtgs-row-chk:checked").length === 0) {
        Swal.fire({
            icon: "warning",
            title: "No Records Selected",
            text: "Please select at least one payment record to print the cheque.",
            confirmButtonText: "OK",
        });
        return;
    }

     $.ajax({
        url: rtgsChequePrintUrl,
        method: "GET",
        data: {
          date: paymentDate,
          bank_id: bankId,
          cheque_no: chequeNo,
          payment_voucher_ids: $(".rtgs-row-chk:checked")
            .map(function () {
              return $(this).data("id");
            })
            .get(),
        },
        beforeSend: () => showLoader('Preparing cheque print...'),
        success: function (res) {
            console.log("res", res);
            
            hideLoader();
          if (!res.success) {
            Swal.fire({
              icon: "error",
              title: "Error",
              text: res.message || "Failed to prepare cheque print.",
            });
            return;
          }
            const blob = new Blob([res.data.cheque_print_html.html], { type: "text/html" });
            const url = URL.createObjectURL(blob);
            window.open(url, "_blank");
        },
        error: function (xhr) {
            hideLoader();
          var msg =
            xhr.responseJSON?.message ||
            "Failed to prepare cheque print. Please try again.";
          Swal.fire({ icon: "error", title: "Error", text: msg });
        },
      });


}

function printRtgsForm() {
  const paymentDate = $("#rtgs_date").val().trim()
    ? formatDateToYMD($("#rtgs_date").val().trim())
    : null;
  const bankId = $("#bank_id").val() || null;
  const chequeNo = $("#rtgs_chq_number").val().trim() || null;

  let mismatch = false;
  $(".rtgs-row-chk:checked").each(function () {
    let rowChqNo = $(this).closest("tr").find(".chq-number-td").text().trim();
    if (rowChqNo !== chequeNo) {
      mismatch = true;
      return false; // break loop
    }
  });

  if (mismatch) {
    Swal.fire({
      icon: "error",
      title: "Cheque Number Mismatch",
      text: "The Cheque Number in selected rows does not match the Cheque Number field. Please ensure they are the same before printing.",
      confirmButtonText: "OK",
    });
    return;
  }

  if (!chequeNo) {
    Swal.fire({
      icon: "error",
      title: "Cheque Number Required",
      text: "Please enter the Cheque Number before printing.",
      confirmButtonText: "OK",
    });
    $("#rtgs_chq_number").focus();
    return;
  }

  if ($(".rtgs-row-chk:checked").length === 0) {
    Swal.fire({
      icon: "warning",
      title: "No Records Selected",
      text: "Please select at least one payment record to print the RTGS form.",
      confirmButtonText: "OK",
    });
    return;
  }

  $.ajax({
    url: rtgsPrintUrl,
    method: "GET",
    data: {
      date: paymentDate,
      bank_id: bankId,
      cheque_no: chequeNo,
      payment_voucher_ids: $(".rtgs-row-chk:checked")
        .map(function () {
          return $(this).data("id");
        })
        .get(),
    },
    beforeSend: () => showLoader('Preparing RTGS print...'),
    success: function (res) {
      hideLoader();
      if (!res.success) {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: res.message || "Failed to prepare RTGS print.",
        });
        return;
      }
      const blob = new Blob(res.data.rtgs_print_html, { type: "text/html" });
      const url = URL.createObjectURL(blob);
      window.open(url, "_blank");
    },
    error: function (xhr) {
      hideLoader();
      var msg =
        xhr.responseJSON?.message ||
        "Failed to prepare RTGS print. Please try again.";
      Swal.fire({ icon: "error", title: "Error", text: msg });
    },
  });
}

function saveOrUpdateCheque() {
  var selected = [];
  $(".rtgs-row-chk:checked").each(function () {
    selected.push($(this).data("id"));
  });

  if (selected.length === 0) {
    Swal.fire({
      icon: "warning",
      title: "No Selection",
      text: "Please select at least one payment record for RTGS and Cheque Print.",
      confirmButtonText: "OK",
    });
    return;
  }

  var chqName = $("#rtgs_chq_name").val().trim();
  var chqAlphaNo = $("#rtgs_chq_alpha_number").val().trim();
  var chqNumber = $("#rtgs_chq_number").val().trim();
  var acPayee = $("#rtgs_ac_payee").val();
  const bankId = $('#bank_id').val();
  const paymentDate = $('#rtgs_date').val();

  if (!chqName) {
    showToast("error", "Please enter Cheque Name.", 4000);
    $("#rtgs_chq_name").focus();
    return;
  }
  if (!chqAlphaNo) {
    showToast("error", "Please enter Cheque Alpha No.", 4000);
    $("#rtgs_chq_alpha_number").focus();
    return;
  }
  if (!chqNumber) {
    showToast("error", "Please enter RTGS / Cheque No.", 4000);
    $("#rtgs_chq_number").focus();
    return;
  }

  if (!bankId) {
    showToast("error", "Please select Bank Name.", 4000);
    $("#bank_id").focus();
    return;
  }

  if (!paymentDate) {
    showToast("error", "Please Enter Payment Date.", 4000);
    $("#rtgs_date").focus();
    return;
  }

  // update cheque no in row td
  $(".rtgs-row-chk:checked").each(function () {
    var $row = $(this).closest("tr");
    $row.find(".chq-number-td").text(chqNumber);
  });

  var $btn = $("#rtgs_save_update_btn")
    .prop("disabled", true)
    .html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

  $.ajax({
    url: rtgsSaveUpdateChequeUrl,
    method: "POST",
    beforeSend: () => showLoader('Saving cheque data...'),
    data: {
      _token: $('meta[name="csrf-token"]').attr("content"),
      payment_voucher_ids: selected,
      chq_name: chqName,
      chq_alpha_number: chqAlphaNo,
      chq_number: chqNumber,
      ac_payee: acPayee,
      bank_id : bankId,
      payment_date:formatDateToYMD(paymentDate)
    },
    success: function (res) {
        hideLoader();
      $btn
        .prop("disabled", false)
        .html(
          '<i class="fa-solid fa-floppy-disk me-1"></i> Save-Update Chq No.',
        );
      if (!res.success) {
        showToast("error", res.message || "Failed to save.", 5000);
        return;
      }
      showToast(
        "success",
        res.message || "Cheque No. saved successfully.",
        4000,
      );
    },
    error: function (xhr) {
        hideLoader();
      $btn
        .prop("disabled", false)
        .html(
          '<i class="fa-solid fa-floppy-disk me-1"></i> Save-Update Chq No.',
        );
      var msg = xhr.responseJSON?.message || "Failed to save cheque data.";
      showToast("error", msg, 5000);
    },
  });
}

/*--------------------------------------------------------------
| Select2
--------------------------------------------------------------*/
function bindSelect2() {
  $("#bank_id").select2({ theme: "bootstrap-5" });

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

/*--------------------------------------------------------------
| Show button — fetch pending RTGS records
--------------------------------------------------------------*/
function bindShowButton() {
  $("#rtgs_show_btn").on("click", function () {
    fetchRtgsData();
  });
}

function fetchRtgsData() {
  $("#chk_all").prop("checked", false).prop("indeterminate", false);
  $('#cheque_number').val('');
  $('#rtgs_chq_number').val('');
  $('#rtgs_chq_alpha_number').val('');
  $('#rtgs_ref_no').val();
  clearValidation();

  var rawDate = $("#rtgs_date").val().trim();
  var bankId = $("#bank_id").val();
  var fileNo = $("#file_number").val().trim();
  var chequeNo = $("#cheque_number").val().trim();

  // at least one filter must be provided
  if (!rawDate) {
    markInvalid("#rtgs_date", "Please enter a date.");
    $("#rtgs_date").focus();
    showToast("error", "Please provide Payment Date.", 5000);
    return;
  }

  if (!bankId) {
    markInvalid("#bank_id", "Please select a bank.");
    $("#bank_id").focus();
    showToast("error", "Please provide Bank.", 5000);
    return;
  }

  // validate date format when provided
  if (rawDate && !isValidDateDMY(rawDate)) {
    markInvalid("#rtgs_date", "Date must be in DD-MM-YYYY format.");
    showToast("error", "Invalid date format. Use DD-MM-YYYY.", 5000);
    $("#rtgs_date")[0].focus();
    return;
  }

  var date = rawDate ? formatDateToYMD(rawDate) : null;

  showLoader();

  $.ajax({
    url: rtgsPendingListUrl,
    method: "GET",
    data: {
      date: date || null,
      bank_id: bankId || null,
      file_number: fileNo || null,
      cheque_no: chequeNo || null,
    },
    success: function (res) {
      hideLoader();
      if (!res.success) {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: res.message || "Failed to load data.",
        });
        return;
      }

      var records = res.data || [];

      if (records.length === 0) {
        clearTable();
        updateTotals();
        Swal.fire({
          icon: "info",
          title: "No Records Found",
          text: "No pending RTGS payments found for the selected filters.",
          confirmButtonText: "OK",
        });
        return;
      }

      // Render table rows
      $("#rtgs_table_body").html(res.data.html);
      updateTotals();
    },
    error: function (xhr) {
      hideLoader();
      var msg =
        xhr.responseJSON?.message ||
        "Failed to load RTGS data. Please try again.";
      Swal.fire({ icon: "error", title: "Error", text: msg });
    },
  });
}

function clearTable() {
  $("#rtgs_table_body").html("");
  $("#chk_all_head").prop("checked", false).prop("indeterminate", false);
}

/*--------------------------------------------------------------
| Checkbox logic — row, header, totals
--------------------------------------------------------------*/
function bindCheckboxEvents() {
  // header checkbox → select / deselect all rows
  $(document).on("change", "#chk_all_head, #chk_all", function () {
    var checked = $(this).prop("checked");
    $(".rtgs-row-chk").prop("checked", checked);
    $("#chk_all_head, #chk_all").prop("checked", checked);
    updateTotals();
  });

  // row checkbox → sync header state + recalculate totals
  $(document).on("change", ".rtgs-row-chk", function () {
    syncHeaderCheckbox();
    updateTotals();
  });
}

function syncHeaderCheckbox() {
  var total = $(".rtgs-row-chk").length;
  var checked = $(".rtgs-row-chk:checked").length;

  if (total === 0 || checked === 0) {
    $("#chk_all_head, #chk_all")
      .prop("checked", false)
      .prop("indeterminate", false);
  } else if (checked === total) {
    $("#chk_all_head, #chk_all")
      .prop("checked", true)
      .prop("indeterminate", false);
  } else {
    $("#chk_all_head, #chk_all")
      .prop("checked", false)
      .prop("indeterminate", true);
  }
}

function updateTotals() {
  var total = 0;
  $(".rtgs-row-chk:checked").each(function () {
    total += parseFloat($(this).data("amount")) || 0;
  });
  $("#rtgs_total").val(formatIndianNumber(total));
}

/*--------------------------------------------------------------
| Bank change — auto-fill Chq Name
--------------------------------------------------------------*/
function bindBankChqName() {
  $("#bank_id").on("change", function () {
    var text = $(this).find("option:selected").text().trim(); // e.g. "AXIS BANK LTD-5352"
    var bankName = text.includes("-")
      ? text.split("-").slice(0, -1).join("-").trim()
      : text;
    $("#rtgs_chq_name").val(bankName ? bankName + ", FOR RTGS" : ", FOR RTGS");
  });
}

/*--------------------------------------------------------------
| Reference No — Find & tick matching row
--------------------------------------------------------------*/
function bindRefNoFind() {
  $("#rtgs_find_btn").on("click", function () {
    findByRefNo();
  });

  $("#rtgs_ref_no").on("keydown", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();
      findByRefNo();
    }
  });
}

function findByRefNo() {
  var ref = $("#rtgs_ref_no").val().trim().toLowerCase();
  if (!ref) return;

  var $match = $(".rtgs-row-chk").filter(function () {
    return $(this).data("ref") == ref;
  });

  if ($match.length === 0) {
    showToast(
      "error",
      "Reference No <strong>" +
        escHtml($("#rtgs_ref_no").val().trim()) +
        "</strong> not available.",
      4000,
    );
    return;
  }

  $match.prop("checked", true).trigger("change");

  // scroll the matched row into view
  var $row = $match.closest("tr");
  $row.addClass("table-warning");
  setTimeout(function () {
    $row.removeClass("table-warning");
  }, 1500);
  $row[0].scrollIntoView({ behavior: "smooth", block: "center" });
}

/*--------------------------------------------------------------
| Payment Advice Print
--------------------------------------------------------------*/
function printPaymentAdvice() {
  const selected = $('.rtgs-row-chk:checked').map(function () {
    return $(this).data('id');
  }).get();

  if (selected.length === 0) {
    Swal.fire({
      icon: 'warning',
      title: 'No Selection',
      text: 'Please select at least one payment record to print the payment advice.',
      confirmButtonText: 'OK',
    });
    return;
  }

  $.ajax({
    url: rtgsPaymentAdvicePrintUrl,
    method: 'GET',
    data: { payment_voucher_ids: selected },
    beforeSend: () => showLoader('Preparing payment advice…'),
    success: function (res) {
      hideLoader();
      if (!res.success) {
        Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Failed to prepare payment advice.' });
        return;
      }
      const blob = new Blob([res.data.html], { type: 'text/html' });
      window.open(URL.createObjectURL(blob), '_blank');
    },
    error: function (xhr) {
      hideLoader();
      const msg = xhr.responseJSON?.message || 'Failed to prepare payment advice. Please try again.';
      Swal.fire({ icon: 'error', title: 'Error', text: msg });
    },
  });
}

/*--------------------------------------------------------------
| Send to Bank Email — Step 1: fetch preview, open compose modal
--------------------------------------------------------------*/
function sendToBankEmail() {
  const bankId   = $('#bank_id').val() || null;
  const chequeNo = $('#rtgs_chq_number').val().trim() || $('#cheque_number').val().trim();
  const date     = formatDateToYMD($('#rtgs_date').val().trim());
  const selected = $('.rtgs-row-chk:checked').map(function () { return $(this).data('id'); }).get();

  if (!bankId) {
    showToast('error', 'Please select a Bank.', 4000);
    $('#bank_id').focus();
    return;
  }
  if (!chequeNo) {
    Swal.fire({ icon: 'warning', title: 'Cheque No. Required',
      text: 'Please enter RTGS / Cheque No. before sending.', confirmButtonText: 'OK' });
    $('#rtgs_chq_number').focus();
    return;
  }
  if (!date) {
    showToast('error', 'Please enter Payment Date.', 4000);
    $('#rtgs_date').focus();
    return;
  }
  if (selected.length === 0) {
    Swal.fire({ icon: 'warning', title: 'No Selection',
      text: 'Please select at least one payment record to send to bank.', confirmButtonText: 'OK' });
    return;
  }
  // Open modal first so the loader inside the message field is visible
  $('#rtgs_email_to').val('');
  $('#rtgs_email_subject').val('');
  $('#rtgs_email_cc').empty().trigger('change');
  $('#rtgs_email_extra_file').val('');
  $('#rtgs_email_excel_link').hide();
  if (window.rtgsEditorReady && hugerte.get('rtgs_email_message')) {
      hugerte.get('rtgs_email_message').setContent('');
  }
  $('#rtgs_editor_loader').css('display', 'flex');
  $('#rtgsEmailModal').modal('show');

  $.ajax({
    url: rtgsEmailPreviewUrl,
    method: 'POST',
    data: {
      _token: $('meta[name="csrf-token"]').attr('content'),
      payment_voucher_ids: selected,
      bank_id: bankId,
      cheque_no: chequeNo,
      date: date,
    },
    success: function (res) {
      if (!res.success) {
        $('#rtgs_editor_loader').hide();
        $('#rtgsEmailModal').modal('hide');
        Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Failed to load preview.' });
        return;
      }
      populateEmailModal(res.data, selected, bankId, chequeNo, date);
    },
    error: function (xhr) {
      $('#rtgs_editor_loader').hide();
      $('#rtgsEmailModal').modal('hide');
      Swal.fire({ icon: 'error', title: 'Error',
        text: xhr.responseJSON?.message || 'Failed to load email preview. Please try again.' });
    },
  });
}

/*--------------------------------------------------------------
| Populate compose modal with preview data
--------------------------------------------------------------*/
function populateEmailModal(data, voucherIds, bankId, chequeNo, date) {
  $('#rtgs_email_to').val(data.to_email || '');
  $('#rtgs_email_subject').val(data.subject || '');
  $('#rtgs_modal_excel_filename').val(data.excel_filename || '');
  $('#rtgs_modal_bank_id').val(bankId);
  $('#rtgs_modal_cheque_no').val(chequeNo);
  $('#rtgs_modal_date').val(date);
  $('#rtgs_modal_voucher_ids').val(JSON.stringify(voucherIds));

  // Excel attachment link
  if (data.excel_filename) {
    $('#rtgs_email_excel_link')
      .attr('href', '/storage/rtgs_register/' + data.excel_filename)
      .show();
    $('#rtgs_email_excel_name').text(data.excel_filename);
  } else {
    $('#rtgs_email_excel_link').hide();
  }

  // CC — rebuild options from accounts list
  const ccSelect = $('#rtgs_email_cc');
  ccSelect.empty();
  if (data.cc_accounts && data.cc_accounts.length) {
    data.cc_accounts.forEach(function (acc) {
      const label = acc.name + ' <' + acc.email + '>';
      ccSelect.append(new Option(label, acc.email, false, false));
    });
  }
  ccSelect.val(null).trigger('change');

  // Editor content
  rtgsSetEditorContent(data.body || '');

  // Clear extra file input
  $('#rtgs_email_extra_file').val('');
}

/*--------------------------------------------------------------
| Email compose modal
|
| HugeRTE throws "document is not in standards mode" when
| hugerte.init() targets an element inside a hidden Bootstrap
| modal (display:none). The fix: defer init until shown.bs.modal
| fires, at which point the modal — and the textarea — are fully
| visible in the DOM.
|
| Flow:
|  1. populateEmailModal() queues body via rtgsSetEditorContent()
|  2. modal('show') is called → Bootstrap animation runs
|  3. shown.bs.modal fires → hugerte.init() runs (first time only)
|  4. editor 'init' callback applies any queued content
|  5. On subsequent opens: editor already ready, content set directly
--------------------------------------------------------------*/
var rtgsHugerteInitialized      = false;
window.rtgsEditorReady          = false;
window.rtgsEditorPendingContent = null;

function rtgsSetEditorContent(html) {
  if (window.rtgsEditorReady) {
    var ed = hugerte.get('rtgs_email_message');
    if (ed) ed.setContent(html);
    $('#rtgs_editor_loader').hide();
  } else {
    window.rtgsEditorPendingContent = html;
  }
}

// Initialise HugeRTE only when the modal is shown (element visible)
$('#rtgsEmailModal').on('shown.bs.modal', function () {
  if (rtgsHugerteInitialized) return;
  rtgsHugerteInitialized = true;

  hugerte.init({
    selector    : '#rtgs_email_message',
    height      : 350,
    menubar     : false,
    base_url    : hugertePath,
    suffix      : '.min',
    plugins     : 'lists link code',
    toolbar     : 'bold italic underline strikethrough | bullist numlist | alignleft aligncenter alignright | link | code',
    content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
    setup: function (editor) {
      editor.on('init', function () {
        window.rtgsEditorReady = true;
        if (window.rtgsEditorPendingContent !== null) {
          editor.setContent(window.rtgsEditorPendingContent);
          window.rtgsEditorPendingContent = null;
          $('#rtgs_editor_loader').hide();
        }
      });
    },
  });
});

// select2 for CC — safe to init on page load (no iframe, no standards check)
function initRtgsEmailModal() {
  $('#rtgs_email_cc').select2({
    dropdownParent: $('#rtgsEmailModal'),
    theme: 'bootstrap-5',
    placeholder: 'Please select',
    allowClear: true,
    tags: true,
    tokenSeparators: [','],
    createTag: function (params) {
      var term = $.trim(params.term);
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(term)) return null;
      return { id: term, text: term, newTag: true };
    },
  });
}

/*--------------------------------------------------------------
| Modal send button — Step 2: submit email
--------------------------------------------------------------*/
$(document).on('click', '#rtgs_email_send_btn', function () {
  const toEmail  = $('#rtgs_email_to').val().trim();
  const subject  = $('#rtgs_email_subject').val().trim();
  const ccEmails = $('#rtgs_email_cc').val() || [];

  hugerte.triggerSave && hugerte.triggerSave();
  const body = hugerte.get('rtgs_email_message')
    ? hugerte.get('rtgs_email_message').getContent()
    : $('#rtgs_email_message').val();

  const excelFilename = $('#rtgs_modal_excel_filename').val();
  const bankId        = $('#rtgs_modal_bank_id').val();
  const chequeNo      = $('#rtgs_modal_cheque_no').val();
  const date          = $('#rtgs_modal_date').val();
  const voucherIds    = JSON.parse($('#rtgs_modal_voucher_ids').val() || '[]');

  if (!toEmail) {
    showToast('error', 'To email is required.', 4000);
    $('#rtgs_email_to').focus();
    return;
  }
  if (!subject) {
    showToast('error', 'Subject is required.', 4000);
    $('#rtgs_email_subject').focus();
    return;
  }

  const $btn = $(this).prop('disabled', true)
    .html('<span class="spinner-border spinner-border-sm me-1"></span> Sending…');

  $.ajax({
    url: rtgsSendToBankUrl,
    method: 'POST',
    data: {
      _token: $('meta[name="csrf-token"]').attr('content'),
      payment_voucher_ids: voucherIds,
      bank_id: bankId,
      cheque_no: chequeNo,
      date: date,
      to_email: toEmail,
      cc_emails: ccEmails,
      subject: subject,
      body: body,
      excel_filename: excelFilename,
    },
    success: function (res) {
      $btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-1"></i> Send Email');
      if (!res.success) {
        Swal.fire({ icon: 'error', title: 'Failed', text: res.message || 'Could not send email.' });
        return;
      }
      $('#rtgsEmailModal').modal('hide');
      Swal.fire({
        icon: 'success',
        title: 'Email Sent!',
        html: `Email sent to <strong>${escHtml(res.data.bank_email)}</strong><br>` +
              `Cheque: <strong>${escHtml(res.data.cheque_no)}</strong> &nbsp;|&nbsp; ` +
              `${res.data.records_count} record(s)`,
        confirmButtonText: 'OK',
      });
    },
    error: function (xhr) {
      $btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-1"></i> Send Email');
      var msg = xhr.responseJSON?.message || 'Failed to send email. Please try again.';
      Swal.fire({ icon: 'error', title: 'Error', text: msg });
    },
  });
});

/*==============================================================
| PAYMENT ADVICE EMAIL
|
| Single party  → preview → compose modal → send (with PDF)
| Multiple       → preview → bulk-confirm modal → send all
|                  results show sent / failed lists
==============================================================*/

/*--------------------------------------------------------------
| Entry point: called by the Payment-Advice-Email button
--------------------------------------------------------------*/
function paymentAdviceEmail() {
  const selected = $('.rtgs-row-chk:checked').map(function () {
    return $(this).data('id');
  }).get();

  if (selected.length === 0) {
    Swal.fire({
      icon: 'warning',
      title: 'No Selection',
      text: 'Please select at least one payment record to send the payment advice.',
      confirmButtonText: 'OK',
    });
    return;
  }

  $.ajax({
    url: paEmailPreviewUrl,
    method: 'POST',
    data: {
      _token: $('meta[name="csrf-token"]').attr('content'),
      payment_voucher_ids: selected,
    },
    beforeSend: () => showLoader('Preparing payment advice email…'),
    success: function (res) {
      hideLoader();
      if (!res.success) {
        Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Failed to load preview.' });
        return;
      }

      const data         = res.data;
      const totalParties = data.parties.length + data.parties_no_email.length;

      if (data.parties.length === 0) {
        // All selected parties have no email
        let names = data.parties_no_email.map(p => escHtml(p.account_name)).join(', ');
        Swal.fire({
          icon: 'error',
          title: 'No Email Address',
          html: `None of the selected parties have an email address.<br>
                 <small class="text-muted">${names}</small>`,
        });
        return;
      }

      if (totalParties === 1) {
        // Single party with email — open compose modal
        $('#pa_editor_loader').css('display', 'flex');
        populatePaymentAdviceModal(data);
        $('#paEmailModal').modal('show');
      } else {
        // Multiple parties — open bulk confirm modal
        populatePaBulkConfirmModal(data);
        $('#paBulkModal').modal('show');
      }
    },
    error: function (xhr) {
      hideLoader();
      Swal.fire({
        icon: 'error', title: 'Error',
        text: xhr.responseJSON?.message || 'Failed to load preview. Please try again.',
      });
    },
  });
}

/*--------------------------------------------------------------
| Populate single-party compose modal
--------------------------------------------------------------*/
function populatePaymentAdviceModal(data) {
  const party = data.parties[0];

  $('#pa_email_to').val(party.email || '');
  $('#pa_email_subject').val(data.subject || '');
  $('#pa_modal_voucher_ids').val(JSON.stringify(party.voucher_ids));

  // CC — rebuild from accounts list
  const ccSelect = $('#pa_email_cc');
  ccSelect.empty();
  if (data.cc_accounts && data.cc_accounts.length) {
    data.cc_accounts.forEach(function (acc) {
      const label = acc.name + ' <' + acc.email + '>';
      ccSelect.append(new Option(label, acc.email, false, false));
    });
  }
  ccSelect.val(null).trigger('change');

  paSetEditorContent(data.body || '');
}

/*--------------------------------------------------------------
| HugeRTE for single-party compose modal
--------------------------------------------------------------*/
var paHugerteInitialized      = false;
window.paEditorReady          = false;
window.paEditorPendingContent = null;

function paSetEditorContent(html) {
  if (window.paEditorReady) {
    var ed = hugerte.get('pa_email_message');
    if (ed) ed.setContent(html);
    $('#pa_editor_loader').hide();
  } else {
    window.paEditorPendingContent = html;
  }
}

$('#paEmailModal').on('shown.bs.modal', function () {
  if (paHugerteInitialized) return;
  paHugerteInitialized = true;

  hugerte.init({
    selector    : '#pa_email_message',
    height      : 320,
    menubar     : false,
    base_url    : hugertePath,
    suffix      : '.min',
    plugins     : 'lists link code',
    toolbar     : 'bold italic underline | bullist numlist | alignleft aligncenter alignright | link | code',
    content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
    setup: function (editor) {
      editor.on('init', function () {
        window.paEditorReady = true;
        if (window.paEditorPendingContent !== null) {
          editor.setContent(window.paEditorPendingContent);
          window.paEditorPendingContent = null;
          $('#pa_editor_loader').hide();
        }
      });
    },
  });
});

// Init Select2 for CC on page load
(function initPaEmailModal() {
  $('#pa_email_cc').select2({
    dropdownParent: $('#paEmailModal'),
    theme: 'bootstrap-5',
    placeholder: 'Please select',
    allowClear: true,
    tags: true,
    tokenSeparators: [','],
    createTag: function (params) {
      var term = $.trim(params.term);
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(term)) return null;
      return { id: term, text: term, newTag: true };
    },
  });
})();

/*--------------------------------------------------------------
| Single-party send button (Step 2)
--------------------------------------------------------------*/
$(document).on('click', '#pa_email_send_btn', function () {
  const toEmail  = $('#pa_email_to').val().trim();
  const subject  = $('#pa_email_subject').val().trim();
  const ccEmails = $('#pa_email_cc').val() || [];

  hugerte.triggerSave && hugerte.triggerSave();
  const body = hugerte.get('pa_email_message')
    ? hugerte.get('pa_email_message').getContent()
    : $('#pa_email_message').val();

  const voucherIds = JSON.parse($('#pa_modal_voucher_ids').val() || '[]');

  if (!toEmail) {
    showToast('error', 'To email is required.', 4000);
    $('#pa_email_to').focus();
    return;
  }
  if (!subject) {
    showToast('error', 'Subject is required.', 4000);
    $('#pa_email_subject').focus();
    return;
  }

  const $btn = $(this).prop('disabled', true)
    .html('<span class="spinner-border spinner-border-sm me-1"></span> Sending…');

  $.ajax({
    url: paEmailSendUrl,
    method: 'POST',
    data: {
      _token: $('meta[name="csrf-token"]').attr('content'),
      payment_voucher_ids: voucherIds,
      to_email: toEmail,
      cc_emails: ccEmails,
      subject: subject,
      body: body,
    },
    success: function (res) {
      $btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-1"></i> Send Email');
      if (!res.success) {
        Swal.fire({ icon: 'error', title: 'Failed', text: res.message || 'Could not send email.' });
        return;
      }
      $('#paEmailModal').modal('hide');
      Swal.fire({
        icon: 'success',
        title: 'Email Sent!',
        html: `Payment advice sent to <strong>${escHtml(res.data.to_email)}</strong>`,
        confirmButtonText: 'OK',
      });
    },
    error: function (xhr) {
      $btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-1"></i> Send Email');
      Swal.fire({
        icon: 'error', title: 'Error',
        text: xhr.responseJSON?.message || 'Failed to send email. Please try again.',
      });
    },
  });
});

/*--------------------------------------------------------------
| Populate bulk-confirm modal
--------------------------------------------------------------*/
function populatePaBulkConfirmModal(data) {
  const parties    = data.parties;
  const noEmail    = data.parties_no_email;

  let html = '';

  if (parties.length) {
    html += `<p class="fw-semibold mb-2" style="font-size:13px;">
               <i class="fa-solid fa-circle-check text-success me-1"></i>
               Will send to <strong>${parties.length}</strong> party/parties:
             </p>
             <ul class="list-unstyled mb-3 ps-2">`;
    parties.forEach(function (p) {
      html += `<li class="mb-1">
                 <i class="fa-solid fa-envelope text-primary me-1"></i>
                 <strong>${escHtml(p.account_name)}</strong>
                 <span class="ms-1 px-1 rounded" style="background:#fff3cd; font-size:12px; font-weight:600; color:#856404;">&lt;${escHtml(p.email)}&gt;</span>
               </li>`;
    });
    html += '</ul>';
    html += `<div class="alert alert-warning py-2 mb-2" style="font-size:12px;">
               <i class="fa-solid fa-triangle-exclamation me-1"></i>
               <strong>Please verify all email addresses above before sending.</strong>
               Emails sent to incorrect addresses cannot be recalled.
             </div>`;
  }

  if (noEmail.length) {
    html += `<div class="alert alert-danger py-2 mb-2" style="font-size:13px;">
               <i class="fa-solid fa-circle-xmark me-1"></i>
               <strong>${noEmail.length}</strong> party/parties will be <strong>skipped</strong> (no email configured):
             </div>
             <ul class="list-unstyled ps-2 mb-0">`;
    noEmail.forEach(function (p) {
      html += `<li class="mb-1 text-danger" style="font-size:13px;">
                 <i class="fa-solid fa-circle-xmark me-1"></i> ${escHtml(p.account_name)}
               </li>`;
    });
    html += '</ul>';
  }

  $('#pa_bulk_party_list').html(html);

  // Reset state to confirm panel
  $('#pa_bulk_confirm_panel').show();
  $('#pa_bulk_result_panel').hide();
  $('#pa_bulk_confirm_actions').removeClass('d-none');   // use d-none so Bootstrap !important is respected
  $('#pa_bulk_close_btn').addClass('d-none');
  $('#pa_bulk_modal_header').css('background', '#1a3c5e');
  $('#pa_bulk_modal_title').text('Send Payment Advice Emails');
  $('#pa_bulk_send_btn')
    .prop('disabled', parties.length === 0)
    .html('<i class="fa-solid fa-paper-plane me-1"></i> Send All');

  // Store all sendable voucher ids
  const allIds = parties.flatMap(function (p) { return p.voucher_ids; });
  $('#pa_bulk_modal_voucher_ids').val(JSON.stringify(allIds));
}

/*--------------------------------------------------------------
| Bulk send button
--------------------------------------------------------------*/
$(document).on('click', '#pa_bulk_send_btn', function () {
  const voucherIds = JSON.parse($('#pa_bulk_modal_voucher_ids').val() || '[]');

  if (voucherIds.length === 0) return;

  const $btn = $(this).prop('disabled', true)
    .html('<span class="spinner-border spinner-border-sm me-1"></span> Sending…');

  $.ajax({
    url: paEmailBulkSendUrl,
    method: 'POST',
    data: {
      _token: $('meta[name="csrf-token"]').attr('content'),
      payment_voucher_ids: voucherIds,
    },
    success: function (res) {
      $btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-1"></i> Send All');
      if (!res.success) {
        Swal.fire({ icon: 'error', title: 'Failed', text: res.message || 'Could not send emails.' });
        return;
      }
      showPaBulkResults(res.data.sent || [], res.data.failed || []);
    },
    error: function (xhr) {
      $btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-1"></i> Send All');
      Swal.fire({
        icon: 'error', title: 'Error',
        text: xhr.responseJSON?.message || 'Failed to send emails. Please try again.',
      });
    },
  });
});

/*--------------------------------------------------------------
| Show bulk results inside the modal
--------------------------------------------------------------*/
function showPaBulkResults(sent, failed) {
  let html = '';

  if (sent.length > 0) {
    html += `<div class="mb-3">
               <p class="fw-semibold text-success mb-1" style="font-size:13px;">
                 <i class="fa-solid fa-circle-check me-1"></i> ${sent.length} sent successfully:
               </p>
               <ul class="list-unstyled ps-2 mb-0">`;
    sent.forEach(function (s) {
      html += `<li class="mb-1" style="font-size:13px;">
                 <i class="fa-solid fa-check text-success me-1"></i>
                 <strong>${escHtml(s.account_name)}</strong>
                 <span class="text-muted ms-1">&lt;${escHtml(s.email)}&gt;</span>
               </li>`;
    });
    html += '</ul></div>';
  }

  if (failed.length > 0) {
    html += `<div class="mb-0">
               <p class="fw-semibold text-danger mb-1" style="font-size:13px;">
                 <i class="fa-solid fa-circle-xmark me-1"></i> ${failed.length} failed:
               </p>
               <ul class="list-unstyled ps-2 mb-0">`;
    failed.forEach(function (f) {
      const emailPart = f.email ? ` &lt;${escHtml(f.email)}&gt;` : '';
      html += `<li class="mb-1" style="font-size:13px;">
                 <i class="fa-solid fa-xmark text-danger me-1"></i>
                 <strong>${escHtml(f.account_name)}</strong>${emailPart}
                 <br><span class="text-muted ps-4" style="font-size:12px;">${escHtml(f.error)}</span>
               </li>`;
    });
    html += '</ul></div>';
  }

  $('#pa_bulk_result_content').html(html);
  $('#pa_bulk_confirm_panel').hide();
  $('#pa_bulk_result_panel').show();
  $('#pa_bulk_confirm_actions').addClass('d-none');  // d-none beats Bootstrap's d-flex !important
  $('#pa_bulk_close_btn').removeClass('d-none');

  // Update header colour: all ok → green, all failed → red, mixed → amber
  let headerColor = '#2fb344'; // green
  if (sent.length === 0) {
    headerColor = '#d63939'; // red
    $('#pa_bulk_modal_title').text('All Emails Failed');
  } else if (failed.length > 0) {
    headerColor = '#f59f00'; // amber
    $('#pa_bulk_modal_title').text('Partially Sent');
  } else {
    $('#pa_bulk_modal_title').text('All Emails Sent');
  }
  $('#pa_bulk_modal_header').css('background', headerColor);
}

/*--------------------------------------------------------------
| Loader helpers
--------------------------------------------------------------*/
function showLoader() {
  $("#rtgs_loader").show();
}

function hideLoader() {
  $("#rtgs_loader").hide();
}

function clearValidation() {
  $(".is-invalid").removeClass("is-invalid");
  $(".invalid-feedback").remove();
}

// auto-clear validation state when the user corrects a field
$(document).on("input change", "#rtgs_date, #file_number", function () {
  $(this).removeClass("is-invalid");
  $(this).next(".invalid-feedback").remove();
});
$(document).on("select2:select select2:clear", "#bank_id", function () {
  $(this).removeClass("is-invalid");
  $(this).next(".invalid-feedback").remove();
});

/*--------------------------------------------------------------
| Utility
--------------------------------------------------------------*/
function escHtml(str) {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

/*--------------------------------------------------------------
| Delete selected payment vouchers (Entry Reverse)
--------------------------------------------------------------*/
function deletePaymentVouchers() {
  const selected = $('.rtgs-row-chk:checked').map(function () {
    return $(this).data('id');
  }).get();

  if (selected.length === 0) {
    Swal.fire({
      icon: 'warning',
      title: 'No Selection',
      text: 'Please select at least one payment record to delete.',
      confirmButtonText: 'OK',
    });
    return;
  }

  Swal.fire({
    title: 'Delete Payment Voucher(s)?',
    html: `You are about to delete <strong>${selected.length}</strong> payment voucher(s).<br><br>
           This will:<br>
           <ul class="mt-2 mb-0" style="display:inline-block; text-align:left;">
             <li>Cancel any associated cheque</li>
             <li>Reverse all reference allocations</li>
             <li>Reopen any settled references</li>
           </ul><br>
           This action <strong>cannot be undone</strong>.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d63939',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Yes, Delete',
    cancelButtonText: 'Cancel',
  }).then((result) => {
    if (!result.isConfirmed) return;

    showLoader('Deleting voucher(s)...');

    $.ajax({
      url: rtgsDeleteSelectedUrl,
      type: 'DELETE',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      data: { payment_voucher_ids: selected },
      success: function (res) {
        hideLoader();
        if (res.success) {
          Swal.fire({
            icon: 'success',
            title: 'Deleted!',
            text: res.message || 'Payment voucher(s) deleted successfully.',
            timer: 1800,
            showConfirmButton: false,
          }).then(() => {
            $('#rtgs_table_body').html('');
            updateTotals();
            $('#chk_all_head, #chk_all').prop('checked', false).prop('indeterminate', false);
          });
        } else {
          Swal.fire({ icon: 'error', title: 'Cannot Delete', text: res.message || 'Failed to delete.' });
        }
      },
      error: function (xhr) {
        hideLoader();
        const msg = xhr.responseJSON?.message || 'Failed to delete payment vouchers.';
        Swal.fire({ icon: 'error', title: 'Error', text: msg });
      },
    });
  });
}
