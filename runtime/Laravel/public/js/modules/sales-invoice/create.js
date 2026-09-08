$(document).ready(function () {
    // Date inputs with FY validation
    new DateInput('#invoice_date',    FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('#last_invoice_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('#delivery_date',   FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    // check within financial year date
    $('#invoice_date').on('blur', function () {
        let invDate = formatDateToYMD($(this).val());
        if (!invDate) return;
        if (!isWithinFY(invDate, FINANCIAL_YEAR_START, FINANCIAL_YEAR_END)) {
            showToast(
                "error",
                `Date must be within Financial Year:<br>(${formatDateToDMY(
                    FINANCIAL_YEAR_START
                )} to ${formatDateToDMY(FINANCIAL_YEAR_END)})`,
                9000
            );
            $(this).val("");
        }
    })

    var formMode = $('#sales_invoice_form').data('form-mode');
    if (formMode === 'edit') {
        setTimeout(function () { 
            $('#sales_invoice_id').next('.select2-container').find('.select2-selection').focus(); 
        }, 100);
    } else {
        setTimeout(function () { $('#invoice_date').val(currentDate).focus(); }, 100);
    }

    // Init bill sundry (renders preloaded rows in order)
    BillSundry.init();

    bindSelect2();
    bindItemEvents();
    validateForm();
});

// -------------------------------------------------------
// GLOBAL STATE
// -------------------------------------------------------
var gstType = '';

// -------------------------------------------------------
// SELECT2 BINDING
// -------------------------------------------------------
function bindSelect2() {
    var selects = ['#account_id', '#sale_type_id', '.item_id', '.destination_id', '.condition_id'];
    selects.forEach(function (el) {
        $(el).select2({ theme: 'bootstrap-5' });
    });

    $(document).on('select2:open', function (e) {
        var $select = $(e.target);
        var $search = $select.data('select2').$dropdown.find('.select2-search__field');
        $search.off('keydown.select2Enter').on('keydown.select2Enter', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                $select.select2('close');
                moveFocusToNextField($select);
            }
        });
    });

    $('#account_id').on('change', function () {
        handleAccountId($(this).val());
    });

    $('#grn_number').on('blur', function () {
        validateGrn();
    });

    initVehicleRegValidation('.txtRegNo');
}

// -------------------------------------------------------
// ITEM CALCULATION EVENTS
// -------------------------------------------------------
function bindItemEvents() {
    // Item change → fetch unit name
    $(document).on('change', '.item_id', function () {
        var itemId = $(this).val();
        var $row   = $(this).closest('.item-row');

        if (!itemId) {
            $row.find('.unit_name').val('');
            $row.find('.cgst_rate, .sgst_rate, .igst_rate').val(0);
            rateBlur($row);
            updateItemTotal();
            return;
        }

        $row.find('.item_details_loader').removeClass('d-none');
        $row.find('.unit_name_loader').removeClass('d-none');

        $.ajax({
            url: masterRoutes.itemDetails(itemId),
            type: 'GET',
            success: function (response) {
                var data = response.data || {};
                $row.find('.unit_name').val(data.unit_name || '');
                rateBlur($row);
                updateItemTotal();
            },
            error: function () { showToast('error', 'Failed to fetch item details'); },
            complete: function () {
                $row.find('.unit_name_loader, .item_details_loader').addClass('d-none');
            }
        });
    });

    $(document).on('blur', '.quantity', function () {
        qtyBlur($(this).closest('.item-row'));
        calculateTotalQty();
        updateItemTotal();
    });

    $(document).on('blur', '.rate', function () {
        rateBlur($(this).closest('.item-row'));
        updateItemTotal();
    });

    $(document).on('blur', '.inclusive_rate', function () {
        inclusiveBlur($(this).closest('.item-row'));
        updateItemTotal();
    });

    $(document).on('blur', '.amount', function () {
        updateItemTotal();
    });
}

// -------------------------------------------------------
// SALE TYPE CHANGE → fetch GST rates
// -------------------------------------------------------
$(document).on('change', '#sale_type_id', function () {
    var saleTypeId = $(this).val();
    if (!saleTypeId) return;

    $('#sale_type_loader').removeClass('d-none');

    $.ajax({
        url: masterRoutes.saleTypeDetails(saleTypeId),
        type: 'GET',
        success: function (response) {
            var detail = response.data || {};
            BillSundry.setSaleTypeDetail(detail);
            BillSundry.setGstType(detail.region || null);
            BillSundry.fillGstPercentages();
            updateItemTotal();
        },
        error: function () { showToast('error', 'Failed to fetch sale type details'); },
        complete: function () { $('#sale_type_loader').addClass('d-none'); }
    });
});

// -------------------------------------------------------
// ACCOUNT DETAILS FETCH
// -------------------------------------------------------
function handleAccountId(accountId) {
    if (!accountId) return;

    $.ajax({
        url: masterRoutes.accountDetails(accountId),
        type: 'GET',
        beforeSend: function () { $('#account_id_loader').removeClass('d-none'); },
        success: function (response) {
            fillAccountDetails(response.data);
            validateGrn();
        },
        error: function () { showToast('error', 'Failed to fetch account details', 5000); },
        complete: function () { $('#account_id_loader').addClass('d-none'); }
    });
}

function fillAccountDetails(data) {
    var $city = $('#account_id_city');
    var $type = $('#account_id_type');
    var $kms  = $('#kms');

    $city.val('');
    $type.val('');
    $kms.val('');

    if (!data) return;

    gstType = data.gst_type;
    var formatGstType = (gstType === GST_TYPE.LOCAL) ? 'LOCAL' : 'INTERSTATE';

    $city.val(data.city || '');
    $type.val(formatGstType);
    $kms.val(data.kms || 0);

    BillSundry.setGstType(gstType);

    // Refresh GST rates on all item rows
    $('.item-row').each(function () {
        $(this).find('.item_id').trigger('change');
    });
}

// -------------------------------------------------------
// GRN VALIDATION
// -------------------------------------------------------
function validateGrn() {
    var grnNumber = $('#grn_number').val();
    var accountId = $('#account_id').val();

    if (!accountId || !grnNumber) return;

    $.ajax({
        url: validateGrnUrl,
        type: 'GET',
        data: {
            grn_number:       grnNumber,
            account_id:       accountId,
            sales_invoice_id: $('#sales_invoice_id').val() || null,
        },
        beforeSend: function () { $('#grn_id_loader').removeClass('d-none'); },
        success: function (response) {
            if (response.status === 'error') { showToast('error', response.message, 5000); return; }
            if (response.data.is_duplicate) {
                showToast('error', 'GRN number already exists with this Customer', 5000);
                $('#grn_number').val('').trigger('focus');
            }
        },
        error: function (response) {
            showToast('error', response.responseJSON.message, 5000);
            $('#grn_number').val('');
        },
        complete: function () { $('#grn_id_loader').addClass('d-none'); }
    });
}

// -------------------------------------------------------
// UPDATE ITEM TOTAL → feeds bill sundry calculation
// -------------------------------------------------------
function updateItemTotal() {
    var total = 0;
    $('.item-row').each(function () {
        total += parseFloat($(this).find('.amount').val()) || 0;
    });

    var amtDec    = DECIMALS.AMOUNT;
    var formatted = '₹' + formatIndianNumber(total, amtDec);

    $('#base_total_amount')
        .val(formatted)
        .data('amount', total)
        .attr('data-amount', total);

    // Recalculate bill sundries with new base
    BillSundry.calculate();
}

// -------------------------------------------------------
// CALCULATE TOTAL QTY
// -------------------------------------------------------
function calculateTotalQty() {
    var total = 0;
    $('.item-row').each(function () {
        total += parseFloat($(this).find('.quantity').val()) || 0;
    });
    var qtyDec = typeof DECIMALS !== 'undefined' ? DECIMALS.QTY : 3;
    $('#total_qty').val(total > 0 ? formatIndianNumber(total, qtyDec) : '');
}

// -------------------------------------------------------
// FORM VALIDATION (JustValidate)
// -------------------------------------------------------
function validateForm() {
    var validator = new JustValidate('#sales_invoice_form', {
        validateBeforeSubmitting: true,
        focusInvalidField: true,
        errorLabelCssClass: 'text-danger',
    });

    validator
        .addField('#invoice_date',  [{ rule: 'required' }])
        .addField('#account_id',    [{ rule: 'required' }])
        .addField('#sale_type_id',  [{ rule: 'required' }])
        .addField('#delivery_date', [{ rule: 'required' }]);

    var toastShown = false;
    validator.onFail(function () {
        if (toastShown) return;
        toastShown = true;
        showToast('error', 'Please fix the highlighted fields before saving.');
        setTimeout(function () { toastShown = false; }, 800);
    });

    validator.onSuccess(function (event) {
        event.preventDefault();
        submitFormAjax(document.getElementById('sales_invoice_form'));
    });
}

// -------------------------------------------------------
// FORM SUBMIT — AJAX
// -------------------------------------------------------
function submitFormAjax(form) {
    if (!form) return;

    var isEdit = $(form).data('form-mode') === 'edit';

    // Validate net total > 0
    var netTotal = parseFloat($('#net_total').data('value')) || 0;
    if (netTotal <= 0) {
        showToast('error', 'Net Total must be greater than 0.');
        return;
    }

    // Validate bill sundries
    var particulars = BillSundry.collect();
    if (particulars && particulars.error) {
        showToast('error', particulars.error);
        return;
    }

    var formData = new FormData(form);

    // Convert DD-MM-YYYY dates to Y-m-d for backend
    ['invoice_date', 'last_invoice_date', 'delivery_date'].forEach(function (field) {
        var val = formData.get(field);
        if (val) formData.set(field, formatDateToYMD(val));
    });

    // Raw net_total value (readonly field has formatted display)
    formData.set('net_total', $('#net_total').data('value') || '0.00');

    // Bill sundries as JSON string
    if (particulars && particulars.length) {
        formData.set('bill_sundries', JSON.stringify(particulars));
    }

    // Hidden SO-locked field values (name attribute set on hidden inputs by get-sale-order.js)
    // FormData picks them up automatically via name attributes

    var ajaxUrl, ajaxType;
    if (isEdit) {
        var salesInvoiceId = $('#sales_invoice_id').val();
        if (!salesInvoiceId) {
            showToast('error', 'Please select a Sales Invoice to update.');
            return;
        }
        ajaxUrl  = updateSalesInvoiceUrl.replace(':id', salesInvoiceId);
        ajaxType = 'POST';
        formData.set('_method', 'PUT');
    } else {
        ajaxUrl  = form.action;
        ajaxType = 'POST';
    }

    $.ajax({
        url: ajaxUrl,
        type: ajaxType,
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function () {
            showLoader('Please wait, ' + (isEdit ? 'updating' : 'saving') + ' Sales Invoice…');
            $('#sales_invoice_form .form-save-btn').prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-2" role="status"></span>'
                + (isEdit ? 'Updating' : 'Saving')
                + ' <span class="animated-dots"></span>'
            );
        },
        success: function (response) {
            if (response.success) {
                var invoiceSerial = response.data ? response.data.invoice_serial : '';
                var action = isEdit ? 'updated' : 'created';
                var html   = 'Sales Invoice <b>' + invoiceSerial + '</b> ' + action + ' successfully';
                Swal.fire({
                    title: 'Success!',
                    html: html,
                    icon: 'success',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                }).then(function (result) {
                    if (result.isConfirmed) location.reload();
                });
            } else {
                showToast('error', response.message || 'Failed to save', 5000);
            }
        },
        error: function (xhr) { handleAjaxError(xhr); },
        complete: function () {
            $('#sales_invoice_form .form-save-btn').prop('disabled', false).html(
                '<i class="fa-solid fa-floppy-disk me-1"></i> Save'
            );
            hideLoader();
        }
    });
}
