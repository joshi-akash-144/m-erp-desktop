/**
 * Product Out Module
 * Handles UI interactions, calculations, and validation for the Godown Product Out page.
 */
document.addEventListener("DOMContentLoaded", function () {

    // Initialize Select2 dropdowns
    bindSelect2();

    setTimeout(() => {
        $('#dc_in_date').focus().select();
    }, 100);

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
                    $('#godown_unit_id').empty().append('<option value="0">--Select Godown Unit--</option>');
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

    /**
     * Vehicle List Modal Logic
     */
    const vehicleModal = $('#vehicle_list_modal');
    const vehicleSearch = $('#vehicle_search');
    const vehicleTableBody = $('#vehicle_list_body');
    let _vehicleActiveIndex = -1;

    function vehicleHighlightRow(index) {
        const $rows = vehicleTableBody.find('.selectable-row:visible');
        $rows.removeClass('vehicle-row-active table-primary');
        if (index >= 0 && index < $rows.length) {
            _vehicleActiveIndex = index;
            const $active = $rows.eq(index);
            $active.addClass('vehicle-row-active table-primary');
            
            const modalBody = document.querySelector('#vehicle_list_modal .modal-body');
            if (modalBody) {
                const rowTop = $active[0].offsetTop;
                const rowHeight = $active[0].offsetHeight;
                const bodyHeight = modalBody.clientHeight;
                if (rowTop < modalBody.scrollTop) {
                    modalBody.scrollTop = rowTop;
                } else if (rowTop + rowHeight > modalBody.scrollTop + bodyHeight) {
                    modalBody.scrollTop = rowTop + rowHeight - bodyHeight;
                }
            }
        }
    }

    function applyVehicleSelection($tr) {
        const godownId = $tr.data('id');
        const vehicleNo = $tr.data('vehicle');
        const challanSerial = $tr.data('challan-serial');
        
        $('#is_set_vehicle').val(godownId);
        $('#id').val(godownId);
        $('#vehicle_number').val(vehicleNo);

        const remarks = $tr.data('remarks');
        if (remarks !== undefined && remarks !== null) {
            $('#remarks').val(remarks);
        }

        // Populate additional fields from godown module row
        const challanWeight = $tr.data('challan-weight');
        if (challanWeight !== undefined && challanWeight !== null) {
            $('input[name="challan_weight"]').val(parseFloat(challanWeight).toFixed(3));
        }

        const challanBags = $tr.data('challan-bags');
        if (challanBags !== undefined && challanBags !== null && challanBags !== '') {
            const parsedBags = parseFloat(challanBags);
            if (!isNaN(parsedBags)) {
                $('input[name="challan_bags"]').val(parsedBags);
            } else {
                $('input[name="challan_bags"]').val('');
            }
        } else {
            $('input[name="challan_bags"]').val('');
        }

        const soId = $tr.data('so-id');
        if (soId !== undefined && soId !== null && soId !== '') {
            $('#dairy_po').val(soId);
        } else {
            $('#dairy_po').val('');
        }

        const godownUnitId = $tr.data('godown-unit-id');
        if (godownUnitId) {
            $('#godown_unit_id').data('pending-val', godownUnitId);
        }

        const godownDestinationId = $tr.data('godown-id') || $tr.data('destination-id');
        if (godownDestinationId) {
            $('#godown_id').val(godownDestinationId).trigger('change');
        }

        const partyDestinationId = $tr.data('party-destination');
        if (partyDestinationId) {
            $('#party_destination').val(partyDestinationId).trigger('change');
        }

        const transporterId = $tr.data('transporter-id');
        if (transporterId) {
            $('#transporter_id').val(transporterId).trigger('change');
        }

        const lrNumber = $tr.data('lr-number');
        if (lrNumber !== undefined && lrNumber !== null && lrNumber !== '') {
            $('#lr_number').val(lrNumber);
        }
        
        if ($tr.data('tare') !== undefined) {
            const parsedTare = parseFloat($tr.data('tare'));
            if (!isNaN(parsedTare)) {
                const tare = parsedTare.toFixed(3);
                $('#tare_weight').val(tare).trigger('change');
            }
        }
        if ($tr.data('bagQty') !== undefined) $('#bag_qty').val($tr.data('bagQty'));

        const inDate = $tr.data('in-date');
        if (inDate) {
            $('#date_in').val(formatDateToDMY(inDate));
        }

        const inTime = $tr.data('in-time');
        if (inTime) {
            const [h, m] = inTime.split(':');
            const d = new Date();
            d.setHours(parseInt(h), parseInt(m));
            const formatted = d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
            $('[name="time_in_display"]').val(formatted);
            $('#time_in').val(inTime.substring(0, 5));
        } else {
            formatAndSetTimeFields($tr.data('created-at'));
        }

        const outDate = $tr.data('out-date');
        if (outDate) {
            $('#date_out').val(formatDateToDMY(outDate));
        }

        const outTime = $tr.data('out-time');
        if (outTime) {
            $('#time_out').val(outTime.substring(0, 5));
        }

        calculateWeights();

        if (!challanSerial) {
            const accountId = $tr.data('account-id');
            if (accountId) {
                $('#party_id').val(accountId).trigger('change');
            }
            vehicleModal.data('selection-confirmed', true);
            vehicleModal.modal('hide');
            return;
        }

        $.ajax({
            url: fetchChallanDetailsUrl,
            method: 'GET',
            data: { grn_serial: challanSerial },
         success: function(data) {
                if (data) {
                    // Header Info
                    $('#delivery_challan_id').val(data.id);
                    $('#dc_serial').val(data.challan_serial || data.grn_serial);

                    if (data.gst_type) {
                        const challanType = data.gst_type.toUpperCase() === 'INTERSTATE' ? 'INTERSTATE' : 'LOCAL';
                        $('#challan_type').val(challanType).trigger('change');
                    }

                    const recordDate = data.dc_date || data.grn_date;
                    if (recordDate) {
                        const dmyDate = formatDateToDMY(recordDate);
                        $('#dc_in_date').val(dmyDate);
                    }

                    // Map independent Godown Dates & Times from the backend response
                    if (data.in_date) {
                        $('#date_in').val(formatDateToDMY(data.in_date));
                    }
                    if (data.in_time) {
                        const [h, m] = data.in_time.split(':');
                        const d = new Date();
                        d.setHours(parseInt(h), parseInt(m));
                        const formatted = d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
                        $('[name="time_in_display"]').val(formatted);
                        $('#time_in').val(data.in_time.substring(0, 5));
                    }
                    if (data.out_date) {
                        $('#date_out').val(formatDateToDMY(data.out_date));
                    }
                    if (data.out_time) {
                        $('#time_out').val(data.out_time.substring(0, 5));
                    }

                    if (data.vehicle_number) {
                        $('#vehicle_number').val(data.vehicle_number);
                    }
                    
                    if (data.remarks) {
                        $('#remarks').val(data.remarks);
                    } else {
                        const remarks = $tr.data('remarks');
                        if (remarks !== undefined && remarks !== null) {
                            $('#remarks').val(remarks);
                        }
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
                        
                        // Set Destinations
                        if (godownUnitId) {
                            $('#godown_unit_id').data('pending-val', godownUnitId);
                        }
                        if (firstItem.destination_id) {
                            $('#godown_id').val(firstItem.destination_id).trigger('change');
                        }
                        
                        // party_destination is already populated from the row click earlier, but we can also set it from SO if empty
                        if (!$('#party_destination').val() && firstItem.sales_order && firstItem.sales_order.destination_id) {
                            $('#party_destination').val(firstItem.sales_order.destination_id).trigger('change');
                        }
                    }

                    // Transaction Info
                    if (data.reference_number) $('#reference_number').val(data.reference_number);
                    if (data.bag_count) $('input[name="bag_qty"]').val(data.bag_count);
                    if (data.bag_type) $('#bag_type').val(data.bag_type.toLowerCase()).trigger('change');

                    // Weights (Only update if current values are empty or zero, to avoid overwriting modal selection)
                    const currentGross = parseFloat($('#gross_weight').val()) || 0;
                    const currentTare = parseFloat($('#tare_weight').val()) || 0;

                    if (data.gross_weight !== undefined && data.gross_weight !== null) {
                        const gross = parseFloat(data.gross_weight).toFixed(3);
                        if (currentGross <= 0) {
                            $('#gross_weight').val(gross).trigger('change');
                            $('#challan_weight').val(gross).trigger('change');
                        }
                    }
                    if (data.tare_weight !== undefined && data.tare_weight !== null) {
                        if (currentTare <= 0) {
                            $('#tare_weight').val(parseFloat(data.tare_weight).toFixed(3)).trigger('change');
                        }
                    }
                    
                    calculateWeights();

                    // Load moisture data if exists
                    if (data.moisture) {
                        $('#old_challan_weight').val(data.moisture.challan_weight);
                        $('#old_gross_weight').val(data.moisture.gross_weight);
                        $('#old_tare_weight').val(data.moisture.tare_weight);
                        $('#old_net_weight').val(data.moisture.net_weight);
                        $('#old_net_weight_wt_bag').val(data.moisture.net_weight_wt_bag);
                        
                        $('#new_challan_weight').val(data.moisture.new_challan_weight);
                        $('#new_gross_weight').val(data.moisture.new_gross_weight);
                        $('#new_tare_weight').val(data.moisture.new_tare_weight);
                        $('#new_net_weight').val(data.moisture.new_net_weight);
                        $('#new_net_weight_wt_bag').val(data.moisture.new_net_weight_wt_bag);
                    }
                }
            },
            error: function() {
                if (typeof showToast === 'function') showToast('error', 'Failed to fetch delivery challan details.');
            }
        });

        vehicleModal.data('selection-confirmed', true);
        vehicleModal.modal('hide');
        calculateWeights();
    }

    vehicleModal.on('show.bs.modal', function() {
        vehicleModal.data('selection-confirmed', false);
        _vehicleActiveIndex = -1;
        vehicleSearch.val('');
    });

    vehicleModal.on('shown.bs.modal', function() {
        vehicleSearch.focus();
        const $rows = vehicleTableBody.find('.selectable-row:visible');
        if ($rows.length > 0) vehicleHighlightRow(0);
    });

    vehicleModal.on('hidden.bs.modal', function() {
        if (vehicleModal.data('selection-confirmed')) {
            setTimeout(() => {
                const isManual = $('input[name="is_manual"]').val() === '1';
                if (!isManual) {
                    $('button[type="submit"]').addClass('disabled cursor-not-allowed');
                    $('#gross_weight').prop('readonly', true);
                    $('#weight_location').focus();
                } else {
                    $('#gross_weight').focus();
                }
            }, 50);
        }
    });

    vehicleTableBody.on('click', '.selectable-row', function() {
        applyVehicleSelection($(this));
    });

    // Modal Keyboard Navigation
    vehicleModal.on('keydown', function(e) {
        const $rows = vehicleTableBody.find('.selectable-row:visible');
        if (!$rows.length) return;
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            vehicleHighlightRow(Math.min(_vehicleActiveIndex + 1, $rows.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            vehicleHighlightRow(Math.max(_vehicleActiveIndex - 1, 0));
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (_vehicleActiveIndex >= 0 && _vehicleActiveIndex < $rows.length) {
                applyVehicleSelection($rows.eq(_vehicleActiveIndex));
            }
        }
    });

    vehicleSearch.on('input', function() {
        const value = $(this).val().toLowerCase();
        _vehicleActiveIndex = -1;
        
        vehicleTableBody.find('.selectable-row').filter(function() {
            const match = $(this).text().toLowerCase().indexOf(value) > -1;
            $(this).toggle(match);
            return match;
        });

        const visibleRows = vehicleTableBody.find('.selectable-row:visible');
        $('#vehicle_no_data_row').toggleClass('d-none', visibleRows.length > 0);
        if (visibleRows.length > 0) vehicleHighlightRow(0);
    });

    /**
     * Moisture Button Logic
     */
    $('#btn_moisture').on('click', function() {
        const moisture = parseFloat($('input[name="moisture"]').val()) || 0;
        
        let oldTare = parseFloat($('#old_tare_weight').val()) || 0;
        let oldGross = parseFloat($('#old_gross_weight').val()) || 0;
        let oldChallan = parseFloat($('#old_challan_weight').val()) || 0;

        if (oldTare === 0) {
            oldTare = parseFloat($('#tare_weight').val()) || 0;
            $('#old_tare_weight').val(oldTare);
        }
        
        if (oldGross === 0) {
            oldGross = parseFloat($('#gross_weight').val()) || 0;
            $('#old_gross_weight').val(oldGross);
        }
        
        if (oldChallan === 0) {
            oldChallan = parseFloat($('#challan_weight').length ? $('#challan_weight').val() : $('input[name="challan_weight"]').val()) || 0;
            $('#old_challan_weight').val(oldChallan);
        }
        
        if ((parseFloat($('#old_net_weight').val()) || 0) === 0) {
            $('#old_net_weight').val(oldGross - oldTare);
        }

        const baseTare = oldTare;
        const baseNet = oldGross - oldTare;

        let adjustment = 0;

        if (moisture === 0) {
            adjustment = 0;
        } else if (baseNet > oldChallan) {
            const diffWeight = baseNet - oldChallan;
            adjustment = diffWeight + moisture;
        } else {
            adjustment = moisture;
        }

        const newTare = baseTare + adjustment;

        $('#new_tare_weight').val(newTare.toFixed(3));
        $('#new_gross_weight').val(oldGross.toFixed(3));
        $('#new_challan_weight').val(oldChallan.toFixed(3));
        
        $('#tare_weight').val(newTare.toFixed(3)).trigger('change');
        calculateWeights();
        
        $('#new_net_weight').val($('#net_weight').val());
        $('#new_net_weight_wt_bag').val($('#net_weight_wt_bag').val());
    });

    /**
     * Weight Button Actions
     */
    $('#btn_get_weight').on('click', function() {
        const $btn = $(this);
        const $submitBtn = $('button[type="submit"]');
        const originalHtml = $btn.html();
        const $weightLocation = $('#weight_location');
        const $liveWeight = $('.net-weight-value');

        // 1. Check if weight location is selected
        const locationId = $weightLocation.val();
        if (!locationId) {
            showToast('error', 'Please select a weight location.');
            return;
        }

        // 2. Perform AJAX Call to fetch weight from scale
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i> FETCHING...');

        $.ajax({
            url: fetchWeightUrl.replace(':id', locationId),
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                $btn.prop('disabled', false).html(originalHtml);
                if (response.value !== undefined && response.value !== null) {
                    const fetchedWeight = parseFloat(response.value) || 0;
                    const vehicleVal = $('#is_set_vehicle').val();
                    const isVehicleSelected = vehicleVal && vehicleVal !== '0' && vehicleVal !== '';
                    
                    if (isVehicleSelected) {
                        $('#gross_weight').val(fetchedWeight.toFixed(3));
                        $('#old_gross_weight').val(fetchedWeight.toFixed(3));
                    } else {
                        $('#tare_weight').val(fetchedWeight.toFixed(3));
                        $('#old_tare_weight').val(fetchedWeight.toFixed(3));
                    }
                    
                    // Reset moisture state on new weight
                    $('input[name="moisture"]').val(0);
                    $('#new_tare_weight').val(0);
                    $('#new_gross_weight').val(0);
                    $('#new_challan_weight').val(0);
                    $('#new_net_weight').val(0);
                    $('#new_net_weight_wt_bag').val(0);
                    
                    $liveWeight.text(fetchedWeight.toFixed(2));
                    if (fetchedWeight > 0) {
                        $submitBtn.removeClass('disabled cursor-not-allowed');
                    } else {
                        $submitBtn.addClass('disabled cursor-not-allowed');
                    }
                    calculateWeights();
                    
                    // Set net weights as old net weights since no moisture is applied yet
                    $('#old_net_weight').val($('#net_weight').val());
                    $('#old_net_weight_wt_bag').val($('#net_weight_wt_bag').val());
                } else if (response.error) {
                    showToast('error', response.error);
                } else {
                    showToast('error', 'Failed to capture weight');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(originalHtml);
                let message = 'An error occurred while fetching weight.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    message = xhr.responseJSON.error;
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showToast('error', message);
            }
        });
    });


    $('#btn_get_weight').on('keydown', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $(this).trigger('click');
        }
    });




    initValidation();
});

    /**
     * Submit the Godown Form via AJAX
     */
    function submitGodownForm(form) {
        const $form = $(form);
        const $submitBtn = $form.find('button[type="submit"]');

        if ($submitBtn.hasClass('disabled')) {
            showToast('warning', 'Please capture weight before saving.');
            return;
        }

        // Auto-trigger moisture calculation before saving to ensure new_* weights are populated
        const moistureVal = parseFloat($('input[name="moisture"]').val()) || 0;
        if (moistureVal > 0) {
            $('#btn_moisture').trigger('click');
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
    
    let url = productOutStoreUrl;

    if (isUpdate) {
        formData.set('is_cycle', 'close');
        url = productOutUpdateUrl.replace(':id', Id);
        formData.append('_method', 'PUT');
    } else {
        formData.set('is_cycle', 'open');
        formData.set('net_weight', 0);
        formData.set('net_weight_wt_bag', 0);
    }

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
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                const actionWord = isUpdate ? 'updating' : 'creating';
                showLoader(`Please wait, ${actionWord} Product Out…`);
            },
            success: function (response) {
                if (response.success) {
                    const orderSerial = response.data?.grn_serial ?? '';
                    const successWord = isUpdate ? 'updated' : 'created';
                    const html = `Product Out <b>${orderSerial}</b> ${successWord} successfully`;
                    Swal.fire({
                        title: 'Success!',
                        html: html,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.reload();
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
        // .addField('#lr_number', [{ rule: 'required', errorMessage: 'L.R. Number is required' }])
        
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

    // Reset vehicle ID if input is cleared manually
    $("#vehicle_number").on("input", function() {
        if (!$(this).val().trim()) {
            $("#is_set_vehicle").val(0);
            $("#id").val("");
        }
    });

/**
 * Initialize Select2 for all relevant dropdowns
 */
function bindSelect2() {
    const selectIdArray = [
        '#broker_id', '#item_id', '#party_id', 
        '#party_destination', '#bag_type', '#godown_id', 
        '#godown_unit_id', '#is_crossing', '#weight_location','#so_no','#transporter_id'
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