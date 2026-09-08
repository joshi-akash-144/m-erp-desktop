$(document).ready(function () {
    const formMode = $("#purchase_invoice_form").data("form-mode");
    if (formMode == 'create') {
        const savedFileNumber = localStorage.getItem('purchase_invoice_file_number');
        if (savedFileNumber && !$('#file_number').val()) {
            $('#file_number').val(savedFileNumber);
        }
        $('#invoice_date').trigger('focus');
    } else {
        setTimeout(() => {
            $('#purchase_invoice_id').select2('open');
        }, 50);
    }

    // date input with validation
    new DateInput("#invoice_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#party_bill_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

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


    bindSelect2();

    // Init bill sundry (renders preloaded rows in order)
    BillSundry.init();

    // Init GRN module (saves row template, binds #grn_id change)
    // GrnModule.init();


    validateForm();

    // -------------------------------------------------------
    // ACCOUNT CHANGE
    // -------------------------------------------------------
    $(document).on('change', '#account_id', function () {
        var accountId = $(this).val();
        if (accountId) {
            handleAccountId(accountId);
            getSupplierTurnOver(accountId);
        } else {
            $("#account_id_city").val("");
            $("#account_id_type").val("");
            gstType = "";

            $(".item-row").each(function () {
                var row = $(this);
                row.find(".cgst_rate, .sgst_rate, .igst_rate").val(0);
                rateBlur(row);
            });
            updateItemTotal();
        }
    });

    // -------------------------------------------------------
    // PURCHASE TYPE CHANGE → fetch GST details
    // -------------------------------------------------------
    $(document).on('change', '#purchase_type_id', function () {
        var purchaseTypeId = $(this).val();
        if (!purchaseTypeId) return;

        $('#purchase_type_loader').removeClass('d-none');

        $.ajax({
            url: masterRoutes.purchaseTypeDetails(purchaseTypeId),
            type: 'GET',
            success: function (response) {
                var detail = response.data || {};
                BillSundry.setPurchaseTypeDetail(detail);
                BillSundry.setGstType(detail.region || null);
                BillSundry.fillGstPercentages();

                // // Recalculate inclusive rate and amount on every item row
                // $('.item-row').each(function () {
                //     rateBlur($(this));
                // });
                updateItemTotal();
            },
            error: function () {
                showToast('error', 'Failed to fetch purchase type details');
            },
            complete: function () {
                $('#purchase_type_loader').addClass('d-none');
            }
        });
    });

    // -------------------------------------------------------
    // ITEM CHANGE → fetch item details (GST rates, unit)
    // -------------------------------------------------------
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
                // $row.find('.cgst_rate').val(data.cgst_rate || 0);
                // $row.find('.sgst_rate').val(data.sgst_rate || 0);
                // $row.find('.igst_rate').val(data.igst_rate || 0);
                rateBlur($row);
                updateItemTotal();
            },
            error: function () {
                showToast('error', 'Failed to fetch item details');
            },
            complete: function () {
                $row.find('.unit_name_loader, .item_details_loader').addClass('d-none');
            }
        });
    });

    // -------------------------------------------------------
    // ITEM CALCULATION EVENTS
    // -------------------------------------------------------
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

});

// -------------------------------------------------------
// GLOBAL STATE
// -------------------------------------------------------
var gstType = "";

// -------------------------------------------------------
// UPDATE ITEM TOTAL → feeds into bill sundry calculation
// -------------------------------------------------------
function updateItemTotal() {
    var total = 0;
    $('.item-row').each(function () {
        total += parseFloat($(this).find('.amount').val()) || 0;
    });

    var amtDec = DECIMALS.AMOUNT;
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
// SELECT2 BINDING
// -------------------------------------------------------
function bindSelect2() {
    grnSelect2();
    var selects = ['#broker_id', '#account_id', '.destination_id', '.item_id', '.condition_id', '#purchase_type_id'];
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

    validateReferenceNumber();
    initVehicleRegValidation('.txtRegNo');
}

function grnSelect2() {
    $('#grn_id').select2({
        theme: 'bootstrap-5',
        matcher: function(params, data) {
        if ($.trim(params.term) === '') {
            return data;
        }

        if (!data.text) {
            return null;
        }

        if (data.text.toLowerCase() === params.term.toLowerCase()) {
            return data;
        }

        return null;
    }
    });
}
// -------------------------------------------------------
// ACCOUNT DETAILS FETCH
// -------------------------------------------------------
function handleAccountId(accountId) {
    if (!accountId) return;

    $.ajax({
        url: masterRoutes.accountDetails(accountId),
        type: 'GET',
        beforeSend: function () {
            $('#account_id_loader').removeClass('d-none');
        },
        success: function (response) {
            fillAccountDetails(response.data);
        },
        error: function () {
            showToast('error', 'Failed to fetch account details', 5000);
        },
        complete: function () {
            $('#account_id_loader').addClass('d-none');
        }
    });
}

// -------------------------------------------------------
// VALIDATE FORM (JustValidate)
// -------------------------------------------------------
function validateForm() {
    var validator = new JustValidate('#purchase_invoice_form', {
        validateBeforeSubmitting: true,
        focusInvalidField: true,
        errorLabelCssClass: 'text-danger',
    });

    validator
        .addField('#invoice_date',      [{ rule: 'required' }])
        .addField('#file_number',       [{ rule: 'required' }])
        .addField('#account_id',        [{ rule: 'required' }])
        .addField('#reference_number',  [{ rule: 'required' }])
        .addField('#purchase_type_id',  [{ rule: 'required' }]);

    // document.querySelectorAll('.item-row').forEach(function (row) {
    //     var itemId = row.querySelector('.item_id');
    //     if (itemId) validator.addField(itemId, [{ rule: 'required' }]);
    // });

    var toastShown = false;
    validator.onFail(function () {
        if (toastShown) return;
        toastShown = true;
        showToast('error', 'Please fix the highlighted fields before saving.');
        setTimeout(function () { toastShown = false; }, 800);
    });

    validator.onSuccess(function (event) {
        event.preventDefault();
        submitFormAjax(document.getElementById('purchase_invoice_form'));
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

    // Build FormData (disabled fields are excluded by the browser)
    var formData = new FormData(form);



    // Laravel method spoofing for update
 

    // Convert DD-MM-YYYY dates to Y-m-d for Laravel validation
    ['invoice_date', 'party_bill_date'].forEach(function (field) {
        var val = formData.get(field);
        if (val) formData.set(field, formatDateToYMD(val));
    });

    // Net total — input is readonly/formatted; use the raw data-value
    formData.set('net_total', $('#net_total').data('value') || '0.00');

    // Inject GRN-sourced disabled field values (FormData skips disabled)
    // if (GrnModule.isActive()) {
    //     GrnModule.prepareSubmitData(formData);
    // }

    // Bill sundries as JSON string (backend decodes via prepareForValidation)
    if (particulars && particulars.length) {
        formData.set('bill_sundries', JSON.stringify(particulars));
    }

    let ajaxUrl, ajaxType;
    if (isEdit) {
        const purchaseInvoiceId = $('#purchase_invoice_id').val();
        if (!purchaseInvoiceId) {
            showToast('error', 'Please select a Purchase Invoice to update.');
            return;
        }
        // Laravel requires _method=PUT for HTML/AJAX form spoofing
        ajaxUrl = updatePurchaseInvoiceUrl.replace(':id', purchaseInvoiceId);
        ajaxType = 'POST';
        formData.set('_method', 'PUT');
    } else {
        ajaxUrl = form.action;
        ajaxType = 'POST';
    }
    
    $.ajax({
        url: ajaxUrl,
        type: ajaxType,
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function () {
            showLoader('Please wait, ' + (isEdit ? 'updating' : 'saving') + ' Purchase Invoice…');
            $('#purchase_invoice_form .form-save-btn').prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-2" role="status"></span>' + (isEdit ? 'Updating' : 'Saving') + ' <span class="animated-dots"></span>'
            );
        },
        success: function (response) {
            // console.log("response", response);

            if (response.success) {
                var fileNumberVal = $('#file_number').val();
                if (fileNumberVal) {
                    localStorage.setItem('purchase_invoice_file_number', fileNumberVal);
                }

                if (typeof BillSundry !== 'undefined' && typeof BillSundry.cacheOrder === 'function') {
                    BillSundry.cacheOrder();
                }

                var invoiceSerial = response.data?.invoice_serial ?? '';
                var action = isEdit ? 'updated' : 'created';
                var html = 'Purchase Invoice <span class="fw-bold text-danger">' + invoiceSerial + '</span> ' + action + ' successfully';
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
        error: function (xhr) {
            handleAjaxError(xhr);
        },
        complete: function () {
            $('#purchase_invoice_form .form-save-btn').prop('disabled', false).html(
                '<i class="fa-solid fa-floppy-disk me-1"></i> Save'
            );
            hideLoader();
        }
    });
}

function fillAccountDetails(data) {
    var $city = $('#account_id_city');
    var $type = $('#account_id_type');
    $city.val('');
    $type.val('');

    if (!data) return;

    gstType = data.gst_type;
    var formatGstType = (gstType === GST_TYPE.LOCAL) ? 'LOCAL' : 'INTERSTATE';

    $city.val(data.city || '');
    $type.val(formatGstType);

    BillSundry.setGstType(gstType);

    // Refresh GST rates on all item rows
    $('.item-row').each(function () {
        $(this).find('.item_id').trigger('change');
    });
}


function getSupplierTurnOver(supplierId, isEdit = false) {
    $('#turn_over_loader').removeClass('d-none');
    $('#turn_over').removeClass('bg-danger text-white');

    $.ajax({
        url: getSupplierTurnOverRoute,
        type: 'GET',
        data: {
            supplier_id: supplierId
        },
        success: function (response) {
            if (response.success) {
                let turnOver = response.data.turn_over || 0;
                let formattedValue = formatIndianNumber(turnOver);
                $('#turn_over').val(formattedValue);


                if (turnOver > 5000000) {
                    $('#turn_over').addClass('bg-danger text-white fw-bold');
                    if (typeof billSundryData !== 'undefined') {
                        let tdsSundry = billSundryData.find(s => String(s.code) === '1009');
                        if (tdsSundry && !isEdit) {
                            $('.particular_id').each(function () {
                                if (String($(this).val()) === String(tdsSundry.id)) {
                                    let row = $(this).data('row');
                                    $('#particular_percentage_' + row).val('0.1').trigger('blur');
                                }
                            });
                        }
                    }
                } else {
                    $('#turn_over').removeClass('bg-danger text-white');
                    if (typeof billSundryData !== 'undefined') {
                        let tdsSundry = billSundryData.find(s => String(s.code) === '1009');
                        if (tdsSundry && !isEdit) {
                            $('.particular_id').each(function () {
                                if (String($(this).val()) === String(tdsSundry.id)) {
                                    let row = $(this).data('row');
                                    $('#particular_percentage_' + row).val('0');
                                    $('#particular_value_' + row).val('0.00');
                                    if (typeof BillSundry !== 'undefined') {
                                        BillSundry.calculate();
                                    }
                                }
                            });
                        }
                    }
                }
            }
            else {
                $('#turn_over').val('0.00');
            }
        },
        error: function (xhr) {
            $('#turn_over').val('0.00');
        },
        complete: function () {
            $('#turn_over_loader').addClass('d-none');
        }
    });
}