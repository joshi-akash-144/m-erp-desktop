/**
 * Product In Module
 * Handles UI interactions, calculations, and validation for the Godown Product In page.
 */
document.addEventListener("DOMContentLoaded", function () {

    // Initialize Select2 dropdowns
    bindSelect2();

    new DateInput('#grn_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('#challan_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('#date_in', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('#date_out', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    // Initialize Vehicle Registration Validation
    if (typeof initVehicleRegValidation === 'function') {
        initVehicleRegValidation('.txtRegNo');
    }

    /**
     * Weight Calculations
     */
    const calculateWeights = () => {
        const tareWeight = parseFloat($('#tare_weight').val()) || 0;
        
        if (tareWeight > 0) {
            calculateNetWeight('#gross_weight', '#tare_weight', '#net_weight', 3);
            calculateNetWeightWithoutBags('#gross_weight', '#tare_weight', '#bag_qty', '#bag_type', '[name="net_weight_wt_bag"]', 3);
        } else {
            $('#net_weight').val('0.000');
            $('[name="net_weight_wt_bag"]').val('0.000');
        }
    };

    // Weight calculations are now triggered on input and selection changes
    $('.weight-input, #bag_qty').on('input', calculateWeights);
    $(document).on('change', '#bag_type', calculateWeights);

    /**
     * Conditional Visibility
     */
    $('#godown_id').on('change', function () {
        const destinationId = $(this).val();
        if (destinationId) {
            $('#godown_unit_container').show();

            // Fetch Godowns dynamically via AJAX
            $.ajax({
                url: getGodownsUrl,
                method: 'GET',
                data: { destination_id: destinationId },
                success: function(data) {
                    $('#godown_unit_id').empty().append('<option value="">--Select Godown Unit--</option>');
                    $.each(data, function (key, value) {
                        $('#godown_unit_id').append('<option value="' + value.id + '">' + value.godown_name + '</option>');
                    });
                    
                    const pendingVal = $('#godown_unit_id').data('pending-val');
                    if (pendingVal) {
                        $('#godown_unit_id').val(pendingVal).trigger('change');
                        $('#godown_unit_id').removeData('pending-val');
                    } else {
                        $('#godown_unit_id').trigger('change');
                    }
                }
            });
        } else {
            $('#godown_unit_container').hide();
        }
    }).trigger('change');

    // Fetch pending purchase orders when Supplier is selected
    $('#party_id').on('change', function() {
        const accountId = $(this).val();

        if (accountId) {
            // Fetch account details to populate GRN Type
            $.ajax({
                url: masterRoutes.accountDetails(accountId),
                type: 'GET',
                success: function(res) {
                    if (res && res.data) {
                        const gstType = res.data.gst_type;
                        const formatGstType = gstType === GST_TYPE.LOCAL ? 'LOCAL' : 'INTERSTATE';
                        $('#grn_type').val(formatGstType).trigger('change');
                    }
                }
            });
        }
    });

    /**
     * Fetch GRN Details when GRN No is selected
     */
    $('#grn_serial').on('change', function() {
        const grnSerial = $(this).val();
        if (!grnSerial) return;

        $.ajax({
            url: fetchGrnDetailsUrl,
            method: 'GET',
            data: { grn_serial: grnSerial },
            success: function(data) {
                if (data) {
                    // Header Info
                    $('#grn_id').val(data.id);

                    if (data.godown_module) {
                        $('#id').val(data.godown_module.id);
                    } else {
                        $('#id').val('');
                    }

                    if (data.gst_type) {
                        const grnType = data.gst_type.toUpperCase() === 'INTERSTATE' ? 'INTERSTATE' : 'LOCAL';
                        $('#grn_type').val(grnType).trigger('change');
                    }

                    if (data.grn_date) {
                        const dmyDate = formatDateToDMY(String(data.grn_date).substring(0, 10));
                        $('#grn_date').val(dmyDate);
                    }
                    if (data.in_date) {
                        const dmyDate = formatDateToDMY(String(data.in_date).substring(0, 10));
                        $('#date_in').val(dmyDate);
                    }
                    if (data.challan_date) {
                        const dmyDate = formatDateToDMY(String(data.challan_date).substring(0, 10));
                        $('#challan_date').val(dmyDate);
                    }
                    $('#vehicle_number').val(data.vehicle_number);
                    
                    if (data.remarks) {
                        $('#remarks').val(data.remarks);
                    }
                    
                    // Party (Account)
                    if (data.account_id) $('#party_id').val(data.account_id).trigger('change');
                    
                    // Product & Details
                    if (data.details && data.details.length > 0) {
                        const firstItem = data.details[0];
                        $('#item_id').val(firstItem.item_id).trigger('change');
                        if (firstItem.rate) $('#rate').val(parseFloat(firstItem.rate).toFixed(3));
                    }

                    // Transaction Info
                    if (data.reference_number) $('#reference_number').val(data.reference_number);
                    if (data.bag_count) $('#bag_qty').val(data.bag_count);
                    if (data.bag_type) {
                        const bagVal = data.bag_type.toLowerCase();
                        const displayVal = bagVal.charAt(0).toUpperCase() + bagVal.slice(1);
                        $('#bag_type').val(displayVal);
                        
                        if ($('#hidden_bag_type_submit').length === 0) {
                            $('#bag_type').after('<input type="hidden" name="bag_type" id="hidden_bag_type_submit">');
                            $('#bag_type').removeAttr('name');
                        }
                        $('#hidden_bag_type_submit').val(bagVal);
                    }

                    // Weights
                    if (data.gross_weight !== undefined && data.gross_weight !== null) {
                        const gross = parseFloat(data.gross_weight).toFixed(3);
                        $('#gross_weight').val(gross).trigger('change');
                    }
                    if (data.tare_weight !== undefined && data.tare_weight !== null) {
                        const tare = parseFloat(data.tare_weight).toFixed(3);
                        $('#tare_weight').val(tare).trigger('change');
                    }
                    
                    // Godown Module specific fields
                    if (data.godown_module) {
                        const gm = data.godown_module;
                        if (gm.godown_id) $('#godown_id').val(gm.godown_id).trigger('change');
                        if (gm.godown_unit_id) $('#godown_unit_id').data('pending-val', gm.godown_unit_id);
                        if (gm.transporter) {
                            $('#transporter_id').val(gm.transporter.name);
                            if ($('#hidden_transporter_id_submit').length === 0) {
                                $('#transporter_id').after('<input type="hidden" name="transporter_id" id="hidden_transporter_id_submit">');
                                $('#transporter_id').removeAttr('name');
                            }
                            $('#hidden_transporter_id_submit').val(gm.transporter_id);
                        } else if (gm.transporter_id) {
                            $('#transporter_id').val(gm.transporter_id).trigger('change');
                        }
                        if (gm.lr_number) $('#lr_number').val(gm.lr_number);
                        if (gm.challan_weight) $('#challan_weight').val(parseFloat(gm.challan_weight).toFixed(3));
                        if (gm.challan_bags) $('#challan_bags').val(gm.challan_bags);
                        if (gm.is_crossing !== null) {
                            $('#is_crossing').val(gm.is_crossing ? 'Yes' : 'No');
                            if ($('#hidden_is_crossing_submit').length === 0) {
                                $('#is_crossing').after('<input type="hidden" name="is_crossing" id="hidden_is_crossing_submit">');
                                $('#is_crossing').removeAttr('name');
                            }
                            $('#hidden_is_crossing_submit').val(gm.is_crossing ? '1' : '0');
                        }
                        // if (gm.is_cycle) $('#is_cycle').val(gm.is_cycle).trigger('change');
                        // if (gm.is_manual !== null) $('#is_manual').val(gm.is_manual ? '1' : '0').trigger('change');
                        
                        if (gm.challan_date) {
                            $('#challan_date').val(formatDateToDMY(String(gm.challan_date).substring(0, 10)));
                        }
                        if (gm.in_date) {
                            $('#date_in').val(formatDateToDMY(String(gm.in_date).substring(0, 10)));
                        }

                        if (gm.in_time) {
                            const timeParts = gm.in_time.split(':');
                            if (timeParts.length >= 2) {
                                const h = parseInt(timeParts[0], 10);
                                const m = timeParts[1];
                                const ampm = h >= 12 ? 'PM' : 'AM';
                                const h12 = h % 12 || 12;
                                $('input[name="time_in_display"]').val(String(h12).padStart(2, '0') + ':' + m + ' ' + ampm);
                                $('#time_in').val(gm.in_time.substring(0, 5));
                            }
                        }

                        if (gm.out_date) {
                            const dmyOut = formatDateToDMY(String(gm.out_date).substring(0, 10));
                            $('#grn_out_date').val(dmyOut);
                            $('#date_out').val(dmyOut);
                        }

                        if (gm.out_time) {
                            const timeParts = gm.out_time.split(':');
                            if (timeParts.length >= 2) {
                                const h = parseInt(timeParts[0], 10);
                                const m = timeParts[1];
                                const ampm = h >= 12 ? 'PM' : 'AM';
                                const h12 = h % 12 || 12;
                                $('input[name="time_out_display"]').val(String(h12).padStart(2, '0') + ':' + m + ' ' + ampm);
                                $('#time_out').val(gm.out_time.substring(0, 5));
                            }
                        }
                    }

                    calculateWeights();
                }
            },
            error: function() {
                if (typeof showToast === 'function') showToast('error', 'Failed to fetch GRN details.');
            }
        });
    });

    /**
     * Form Validation & Submission
     */
    // Reset vehicle ID if input is cleared manually
    $("#vehicle_number").on("input", function() {
        if (!$(this).val().trim()) {
            $("#is_set_vehicle").val(0);
            $("#id").val("");
        }
    });

    initValidation();

    // Auto-select and fetch data if grn_serial query parameter is present (edit mode)
    const urlParams = new URLSearchParams(window.location.search);
    const grnSerial = urlParams.get('grn_serial');
    if (grnSerial) {
        $('#grn_serial').val(grnSerial).trigger('change');
    }

    setTimeout(() => {
        $('#grn_date').focus().select();
    }, 100);
});

/**
 * Initialize Select2 for all relevant dropdowns
 */
function bindSelect2() {
    const selectIdArray = [
        '#item_id','#party_id','#godown_id','#godown_unit_id', '#weight_location'
    ];

    selectIdArray.forEach(function (id) {
        $(id).select2({
            theme: 'bootstrap-5',
            allowClear: true,
            placeholder: 'Select ...',
            width: $(id).attr('style') && $(id).attr('style').includes('width') ? 'element' : '100%'
        });
    });

    // Handle Enter-key on Select2 search fields
    // Use namespaced event and .off() to prevent duplicate listeners
    $(document).off('select2:open.manual_focus').on('select2:open.manual_focus', function (e) {
        const selectElement = $(e.target);
        const searchInput = selectElement.data('select2').$dropdown.find('.select2-search__field');

        searchInput.off('keydown.select2Enter').on('keydown.select2Enter', function (event) {
            if (event.which === 13) {
                event.preventDefault();
                selectElement.select2('close');
                moveFocusToNextField(selectElement);
            }
        });
    });
}

/**
 * Form Validation using JustValidate
 */
function initValidation() {
    if (typeof JustValidate === 'undefined') return;

    let toastShown = false;
    const validator = new JustValidate('#product_in_form', {
        validateBeforeSubmitting: true,
        focusInvalidField: true,
        errorLabelCssClass: 'text-danger small mt-1',
    });

    validator
        .addField('#grn_date', [{ rule: 'required', errorMessage: 'GRN Date is required' }])
        .addField('#vehicle_number', [{ rule: 'required', errorMessage: 'Vehicle No is required' }], { errorsContainer: '#vehicle_number_error' })
        .addField('#item_id', [{ rule: 'required', errorMessage: 'Item is required' }])
        .addField('#godown_id', [{ 
                validator: (value) => {
                    const isSetVehicle = $('#is_set_vehicle').val();
                    if (isSetVehicle && isSetVehicle !== '0') {
                        return !!value && value.toString().trim() !== '';
                    }
                    return true;
                },errorMessage: 'Godown is required' }])
        .addField('#godown_unit_id', [{ 
                 validator: (value) => {
                    const isSetVehicle = $('#is_set_vehicle').val();
                    if (isSetVehicle && isSetVehicle !== '0') {
                        return !!value && value.toString().trim() !== '';
                    }
                    return true;
                }, errorMessage: 'Godown Unit is required' }])
        .addField('#party_id', [{ rule: 'required', errorMessage: 'Party is required' }])
        .addField('#reference_number', [{ rule: 'required', errorMessage: 'Party Bill No is required' }])
        .addField('#transporter_id', [{ rule: 'required', errorMessage: 'Transporter is required' }])
        .addField('#lr_number', [{ rule: 'required', errorMessage: 'L.R. Number is required' }])
        .addField('#challan_bags', [
            {
                validator: (value) => {
                    const isSetVehicle = $('#is_set_vehicle').val();
                    if (isSetVehicle && isSetVehicle !== '0') {
                        return !!value && value.toString().trim() !== '';
                    }
                    return true;
                },
                errorMessage: 'Challan Bags is required'
            }
        ])
        .onSuccess(function (event) {
            event.preventDefault();
            submitGodownForm(event.target);
        })
        .onFail(() => {
            if (toastShown) return;
            toastShown = true;
            showToast("error", "Please fix the highlighted fields before saving.");
            setTimeout(() => (toastShown = false), 800);
        });
}

/**
 * Submit the Godown Form via AJAX
 */
function submitGodownForm(form) {
    const $form = $(form);
    const $submitBtn = $form.find('button[type="submit"]');

    // Prevent submission if duplicate reference or LR number is flagged
    if ($('#reference_number').hasClass('is-invalid') || $('#lr_number').hasClass('is-invalid')) {
        showToast('error', 'Please fix duplicate errors before saving.', 5000);
        return;
    }

    $submitBtn.prop('disabled', true);

    // Convert dates to Y-m-d for backend validation
    const formData = new FormData(form);
    const dateFields = ['grn_date', 'challan_date', 'date_in', 'grn_out_date'];
    
    dateFields.forEach(field => {
        if (formData.has(field)) {
            formData.set(field, formatDateToYMD(formData.get(field)));
        }
    });

    // Mapping for Backend to match database columns
    const mappings = {
        'bag_qty': 'bag_count',
        'grn_type': 'gst_type',
        'po_no': 'purchase_order_id',
        'party_destination': 'party_godown_id'
    };

    for (const [oldKey, newKey] of Object.entries(mappings)) {
        if (formData.has(oldKey)) {
            formData.set(newKey, formData.get(oldKey));
            if (oldKey !== newKey) {
                formData.delete(oldKey);
            }
        }
    }

    if (formData.has('gst_type')) {
        formData.set('gst_type', formData.get('gst_type').toLowerCase());
    }

    const Id = formData.get('id');
    formData.append('_method', 'PUT');
    formData.set('in_out_status', 'in');

    $.ajax({
        url: productInSelfUpdateUrl.replace(':id', Id),
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function () {
            showLoader(`Please wait, updating Product In Self…`);
        },
        success: function (response) {
            if (response.success) {
                const orderSerial = response.data?.grn_serial ?? '';
                const html = `Product In <b>${orderSerial}</b> updated successfully`;
                Swal.fire({
                    title: 'Success!',
                    html: html,
                    icon: 'success',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = godownIndex;
                    }
                });
            } else {
                showToast('error', response.message || 'Failed to process request', 5000);
            }
        },
        error: function (xhr) {
            const msg = xhr.responseJSON?.message ?? 'An unexpected error occurred.';
            showToast('error', msg, 5000);
        },
        complete: function () {
            hideLoader();
            $submitBtn.prop('disabled', false);
        }
    });
}
