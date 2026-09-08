/**
 * Product Out Module
 * Handles UI interactions, calculations, and validation for the Godown Product Out page.
 */
document.addEventListener("DOMContentLoaded", function () {

    // Initialize Select2 dropdowns
    bindSelect2();

    $('#dc_in_date').focus();

    new DateInput('#dc_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('#dc_in_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
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
        const grossWeight = parseFloat($('#gross_weight').val()) || 0;
        
        if (grossWeight > 0) {
            calculateNetWeight('#gross_weight', '#tare_weight', '#net_weight', 3);
            calculateNetWeightWithoutBags('#gross_weight', '#tare_weight', '#bag_qty', '#bag_type', '#net_weight_wt_bag', 3);

            // Ensure negative weights display as 0.000
            const netWeight = parseFloat($('#net_weight').val()) || 0;
            if (netWeight < 0) {
                $('#net_weight').val('0.000');
            }
            const netWeightWtBag = parseFloat($('#net_weight_wt_bag').val()) || 0;
            if (netWeightWtBag < 0) {
                $('#net_weight_wt_bag').val('0.000');
            }
        } else {
            $('#net_weight').val('0.000');
            $('#net_weight_wt_bag').val('0.000');
        }
    };

    // Trigger calculation on weight input and selection changes
    $('.weight-input, #bag_qty').on('input', calculateWeights);
    $(document).on('change', '#bag_type', calculateWeights);

    /**
     * Conditional Visibility
     */
    $('#godown_id').on('change', function() {
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
                    $.each(data, function(key, value) {
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
    
   // Fetch pending sales orders when Customer is selected
    $('#party_id').on('change', function() {
        const accountId = $(this).val();
        const $soSelect = $('#so_no');
        
        $soSelect.empty().append('<option value="">--Select Sales Order--</option>');

        if (accountId) {
            // Fetch account details to populate Challan Type
            $.ajax({
                url: masterRoutes.accountDetails(accountId),
                type: 'GET',
                success: function(res) {
                    if (res && res.data) {
                        const gstType = res.data.gst_type;
                        const formatGstType = gstType === GST_TYPE.LOCAL ? 'LOCAL' : 'INTERSTATE';
                        $('#challan_type').val(formatGstType).trigger('change');
                    }
                }
            });

            $.ajax({
                url: fetchPendingSalesOrdersUrl,
                method: 'GET',
                data: { account_id: accountId },
                success: function(response) {
                    if (response.success && response.data) {
                        response.data.forEach(function(so) {
                            $soSelect.append(`<option value="${so.id}">${so.order_serial}</option>`);
                        });

                        const pendingSoVal = $soSelect.data('pending-val');
                        if (pendingSoVal) {
                            $soSelect.val(pendingSoVal).trigger('change');
                            $soSelect.removeData('pending-val');
                        } else {
                            $soSelect.trigger('change');
                        }
                    }
                }
            });
        } else {
            $('#challan_type').val('');
            $soSelect.trigger('change');
        }
    });



    $('#dc_serial').on('change', function() {
        const challanSerial = $(this).val();
        
        if (!challanSerial) {
            $('#id').val('');
            $('#is_set_vehicle').val('0');
            return;
        }

        $.ajax({
            url: fetchChallanDetailsUrl,
            method: 'GET',
            data: { grn_serial: challanSerial, type: 'out' },
         success: function(data) {
                if (data) {
                    // Header Info
                    $('#delivery_challan_id').val(data.id);
                    
                    if (data.godown_module) {
                        $('#id').val(data.godown_module.id);
                        $('#is_set_vehicle').val(data.godown_module.id);
                    } else {
                        $('#id').val('');
                        $('#is_set_vehicle').val('0');
                    }

                    if (data.gst_type) {
                        const challanType = data.gst_type.toUpperCase() === 'INTERSTATE' ? 'INTERSTATE' : 'LOCAL';
                        $('#challan_type').val(challanType).trigger('change');
                    }

                    const recordDate = data.dc_date || data.grn_date;
                    if (recordDate) {
                        const dmyDate = formatDateToDMY(recordDate);
                        $('#dc_in_date').val(dmyDate);

                        if (data.godown_module && data.godown_module.date_in) {
                            $('#date_in').val(formatDateToDMY(String(data.godown_module.date_in).substring(0, 10)));
                        } else {
                            $('#date_in').val(dmyDate);
                        }

                        if (data.godown_module && data.godown_module.time_in) {
                            $('#time_in').val(data.godown_module.time_in);
                        } else {
                            if (typeof formatAndSetTimeFields === 'function') {
                                formatAndSetTimeFields(data.created_at);
                            }
                        }
                    }

                    if (data.vehicle_number) {
                        $('#vehicle_number').val(data.vehicle_number);
                    }
                    
                    if (data.remarks) {
                        $('#remarks').val(data.remarks);
                    }
                    
                    // Party & Broker
                    if (data.account_id) $('#party_id').val(data.account_id).trigger('change');
                    if (data.broker_id) $('#broker_id').val(data.broker_id).trigger('change');
                    
                    // Product & Details
                    if (data.details && data.details.length > 0) {
                        const firstItem = data.details[0];
                        if (firstItem.sales_order_id) {
                            $('#so_no').data('pending-val', firstItem.sales_order_id);
                        }
                        $('#item_id').val(firstItem.item_id).trigger('change');
                        if (firstItem.rate) $('input[name="rate"]').val(parseFloat(firstItem.rate).toFixed(3));
                        


                    }

                    // Transaction Info
                    if (data.reference_number) $('#reference_number').val(data.reference_number);
                    if (data.bag_count) $('input[name="bag_qty"]').val(data.bag_count);
                    if (data.bag_type) $('#bag_type').val(data.bag_type).trigger('change');

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
                        if (gm.party_destination_id) $('#party_destination').val(gm.party_destination_id).trigger('change');
                        if (gm.dairy_po) $('#dairy_po').val(gm.dairy_po);
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
                        if (gm.is_crossing !== null) {
                            $('#is_crossing').val(gm.is_crossing ? 'Yes' : 'No');
                            if ($('#hidden_is_crossing_submit').length === 0) {
                                $('#is_crossing').after('<input type="hidden" name="is_crossing" id="hidden_is_crossing_submit">');
                                $('#is_crossing').removeAttr('name');
                            }
                            $('#hidden_is_crossing_submit').val(gm.is_crossing ? '1' : '0');
                        }
                        // if (gm.is_cycle) $('#is_cycle').val(gm.is_cycle).trigger('change');
                        // if (gm.is_manual !== null && gm.is_manual !== undefined) $('#is_manual').val(gm.is_manual ? '1' : '0').trigger('change');

                        if (gm.gross_weight !== null && gm.gross_weight !== undefined) {
                            $('#gross_weight').val(parseFloat(gm.gross_weight).toFixed(3)).trigger('change');
                        }
                        if (gm.tare_weight !== null && gm.tare_weight !== undefined) {
                            $('#tare_weight').val(parseFloat(gm.tare_weight).toFixed(3)).trigger('change');
                        }
                        if (gm.bag_count !== null && gm.bag_count !== undefined) {
                            $('input[name="bag_qty"]').val(gm.bag_count);
                        } 
                        if (gm.challan_weight !== null && gm.challan_weight !== undefined) {
                            $('input[name="challan_weight"]').val(gm.challan_weight);
                        }
                        if (gm.bag_type) {
                            $('#bag_type').val(gm.bag_type.toLowerCase()).trigger('change');
                        }
                        if (gm.remarks) {
                            $('#remarks').val(gm.remarks);
                        }
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
                if (typeof showToast === 'function') showToast('error', 'Failed to fetch delivery challan details.');
            }
        });
    });

    initValidation();

    // Auto-select and fetch data if grn_serial query parameter is present (edit mode)
    const urlParams = new URLSearchParams(window.location.search);
    const grnSerial = urlParams.get('grn_serial');
    if (grnSerial) {
        $('#dc_serial').val(grnSerial).trigger('change');
    }
});

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

        const originalText = $submitBtn.html();
        $submitBtn.prop('disabled', true);

        const formData = new FormData(form);
        
        // --- Date Formatting ---
        const dateFields = ['dc_in_date', 'date_in', 'date_out'];
        dateFields.forEach(field => {
            if (formData.has(field)) {
                formData.set(field, formatDateToYMD(formData.get(field)));
            }
        });

    // If vehicle was selected from modal, we are essentially completing/closing that cycle
    const Id = formData.get('id');
    const isUpdate = Id && Id !== '0' && Id !== '';
    
    formData.append('_method', 'PUT');
    formData.set('in_out_status', 'out');

        if (formData.has('party_destination')) {
            formData.set('party_destination_id', formData.get('party_destination'));
            formData.delete('party_destination');
        }

        if (formData.has('bag_qty')) {
            formData.set('bag_count', formData.get('bag_qty'));
            formData.delete('bag_qty');
        }

        $.ajax({    
            url: productOutUpdateUrl.replace(':id', Id),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                showLoader(`Please wait, updating Product Out…`);
            },
            success: function (response) {
                if (response.success) {
                    const orderSerial = response.data?.grn_serial ?? '';
                    const html = `Product Out <b>${orderSerial}</b> updated successfully`;
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
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function (xhr) {
                $submitBtn.prop('disabled', false).html(originalText);
                const msg = xhr.responseJSON?.message ?? 'An unexpected error occurred.';
                showToast('error', msg, 5000);
            },
            complete: function () {
                hideLoader();
                $submitBtn.prop('disabled', false);
            }
        });
    }
    /**
     * Form Validation using JustValidate
     */
    function initValidation() {
        if (typeof JustValidate === 'undefined') return;

        let toastShown = false;
        const validator = new JustValidate('#product_out_form', {
            validateBeforeSubmitting: true,
            focusInvalidField: true,
            errorLabelCssClass: 'text-danger small mt-1',
        });

    validator
        .addField('#dc_in_date', [{ rule: 'required', errorMessage: 'GRN In Date is required' }])
        .addField('#vehicle_number', [{ rule: 'required', errorMessage: 'Vehicle Number is required' }], { errorsContainer: '#vehicle_number_error' })
        .addField('#item_id', [{ rule: 'required', errorMessage: 'Item is required' }])
        .addField('#godown_id', [{ 
            validator: (value) => {
                    const isSetVehicle = $('#is_set_vehicle').val();
                    if (isSetVehicle && isSetVehicle !== '0') {
                        return !!value && value.toString().trim() !== '';
                    }
                    return true;
                }, errorMessage: 'Godown is required' }])
        .addField('#godown_unit_id', [{ 
                validator: (value) => {
                    const isSetVehicle = $('#is_set_vehicle').val();
                    if (isSetVehicle && isSetVehicle !== '0') {
                        return !!value && value.toString().trim() !== '';
                    }
                    return true;
                }, errorMessage: 'Godown Unit is required' }])
        .addField('#party_id', [{ rule: 'required', errorMessage: 'Party is required' }])
        .addField('#transporter_id', [{ rule: 'required', errorMessage: 'Transporter is required' }])
        .addField('#lr_number', [{ rule: 'required', errorMessage: 'L.R. Number is required' }])
        
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
 * Initialize Select2 for all relevant dropdowns
 */
function bindSelect2() {
    const selectIdArray = [
        '#item_id', '#party_id', 
        '#party_destination','#godown_id', 
        '#godown_unit_id', '#so_no'
    ];

    selectIdArray.forEach(function (id) {
        $(id).select2({
            theme: 'bootstrap-5',
            allowClear: true,
            placeholder: 'Select ...',
            width: $(id).attr('style') && $(id).attr('style').includes('width') ? 'element' : '100%'
        });
    });

    $(document).off('select2:open.manual_focus').on('select2:open.manual_focus', function (e) {
        const selectElement = $(e.target);
        const searchInput = selectElement.data('select2').$dropdown.find('.select2-search__field');

        searchInput.off('keydown.select2Enter').on('keydown.select2Enter', function (event) {
            if (event.which === 13) {
                event.preventDefault();
                selectElement.select2('close');
                if (typeof moveFocusToNextField === 'function') {
                    moveFocusToNextField(selectElement);
                }
            }
        });
    });
}