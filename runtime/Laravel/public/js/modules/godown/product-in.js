/**
 * Product In Module
 * Handles UI interactions, calculations, and validation for the Godown Product In page.
 */
document.addEventListener("DOMContentLoaded", function () {

    // Initialize Select2 dropdowns
    bindSelect2();

    new DateInput('#grn_in_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
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
                    $('#godown_unit_id').empty().append('<option value="0">--Select Godown Unit--</option>');
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
     * Vehicle List Modal Logic (Mirrors PO Modal pattern)
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
            
            // Scroll into view
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
        const grnSerial = $tr.data('grn-serial');
        
        $('#is_set_vehicle').val(1);
        $('#id').val(godownId);
        $('#vehicle_number').val(vehicleNo);

        const remarks = $tr.data('remarks');
        if (remarks !== undefined && remarks !== null) {
            $('#remarks').val(remarks);
        }

        // Populate additional fields from godown module row
        const challanWeight = $tr.data('challan-weight');
        if (challanWeight !== undefined && challanWeight !== null) {
            $('#challan_weight').val(parseFloat(challanWeight).toFixed(3));
        }

        const challanBags = $tr.data('challan-bags');
        if (challanBags !== undefined && challanBags !== null && challanBags !== '') {
            const parsedBags = parseFloat(challanBags);
            if (!isNaN(parsedBags)) {
                $('#challan_bags').val(parsedBags);
            } else {
                $('#challan_bags').val('');
            }
        } else {
            $('#challan_bags').val('');
        }

        const godownUnitId = $tr.data('godown-unit-id');
        if (godownUnitId) {
            $('#godown_unit_id').data('pending-val', godownUnitId);
        }

        const destinationId = $tr.data('godown-id') || $tr.data('destination-id');
        if (destinationId) {
            $('#godown_id').val(destinationId).trigger('change');
        }

        // const partyDestinationId = $tr.data('party-destination');
        // if (partyDestinationId) {
        //     $('#party_destination').val(partyDestinationId).trigger('change');
        // }

        const transporterId = $tr.data('transporter-id');
        if (transporterId) {
            $('#transporter_id').val(transporterId).trigger('change');
        }

        const lrNumber = $tr.data('lr-number');
        if (lrNumber !== undefined && lrNumber !== null) {
            $('#lr_number').val(lrNumber);
        }

        const challanDate = $tr.data('challan-date');
        if (challanDate) {
            $('#challan_date').val(formatDateToDMY(challanDate));
        } else {
            $('#challan_date').val('');
        }

        // Populate Weights & Times from stored Godown entry values
        const inDate = $tr.data('in-date');
        if (inDate) {
            $('#date_in').val(formatDateToDMY(inDate));
        }

        const inTime = $tr.data('in-time');
        if (inTime) {
            // Show as H:i AM/PM in the readonly display field
            const [h, m] = inTime.split(':');
            const date = new Date();
            date.setHours(parseInt(h), parseInt(m));
            const formatted = date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
            $('[name="time_in_display"]').val(formatted);
            $('#time_in').val(inTime.substring(0, 5)); // store H:i in hidden input
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

        if ($tr.data('gross') !== undefined) {
            const parsedGross = parseFloat($tr.data('gross'));
            if (!isNaN(parsedGross)) {
                const gross = parsedGross.toFixed(3);
                $('#gross_weight').val(gross).trigger('change');
            }
        }
        if ($tr.data('bagQty') !== undefined) $('#bag_qty').val($tr.data('bagQty'));

        calculateWeights();

        if (!grnSerial) {
            const accountId = $tr.data('account-id');
            if (accountId) {
                $('#party_id').val(accountId).trigger('change');
            }
            vehicleModal.data('selection-confirmed', true);
            vehicleModal.modal('hide');
            return;
        }

        $.ajax({
            url: fetchGrnDetailsUrl,
            method: 'GET',
            data: { grn_serial: grnSerial },
         success: function(data) {
                if (data) {
                    // Header Info
                    $('#grn_id').val(data.id);
                    $('#grn_serial').val(data.grn_serial);

                    if (data.gst_type) {
                        const grnType = data.gst_type.toUpperCase() === 'INTERSTATE' ? 'INTERSTATE' : 'LOCAL';
                        $('#grn_type').val(grnType).trigger('change');
                    }

                    if (data.grn_in_date) {
                        const dmyDate = formatDateToDMY(data.grn_in_date);
                        $('#grn_in_date').val(dmyDate);
                        // $('#date_in').val(dmyDate); // Set Date In as GRN Date

                        // Set Time In as GRN Creation Time
                        formatAndSetTimeFields(data.created_at);
                    }
                    if(data.date_in){
                        const dmyDate = formatDateToDMY(data.date_in);
                        $('#date_in').val(dmyDate);
                    }
                    if(data.challan_date){
                        const challanDate = formatDateToDMY(data.challan_date);
                        $('#challan_date').val(challanDate);
                    }

                    $('#vehicle_number').val(data.vehicle_number);
                    
                    if (data.remarks) {
                        $('#remarks').val(data.remarks);
                    } else {
                        const remarks = $tr.data('remarks');
                        if (remarks !== undefined && remarks !== null) {
                            $('#remarks').val(remarks);
                        }
                    }
                    
                    // Party (Account)
                    if (data.account_id) $('#party_id').val(data.account_id).trigger('change');
                    
                    // Product & Details
                    if (data.details && data.details.length > 0) {
                        const firstItem = data.details[0];
                        $('#item_id').val(firstItem.item_id).trigger('change');
                        if (firstItem.rate) $('#rate').val(parseFloat(firstItem.rate).toFixed(3));
                        
                        // Set Destinations
                        if (godownUnitId) {
                            $('#godown_unit_id').data('pending-val', godownUnitId);
                        }

                        // if(firstItem.party_destination){
                        //     $('#party_destination').val(party_destination).trigger('change');
                        // }
                        
                        // if (firstItem.purchase_order && firstItem.purchase_order.destination_id) {
                        //     $('#party_destination').val(firstItem.purchase_order.destination_id).trigger('change');
                        // }
                    }

                    // Transaction Info
                    if (data.reference_number) $('#reference_number').val(data.reference_number);
                    if (data.bag_count) $('#bag_qty').val(data.bag_count);
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
                        
                        // Calculate the displayed moisture difference if it exists
                        const moistureDiff = data.moisture.new_tare_weight - data.moisture.tare_weight;
                        if (moistureDiff > 0) {
                            // If baseNet was more than challan weight, the adjustment was diff + moisture
                            // However, we just need to set the value. Since we don't save the raw `moisture` value,
                            // we just reconstruct an approximation or leave it as the stored difference.
                            // The user sets it manually anyway.
                        }
                    }
                }
            },
            error: function() {
                if (typeof showToast === 'function') showToast('error', 'Failed to fetch GRN details.');
            }
        });

        vehicleModal.data('selection-confirmed', true);
        vehicleModal.modal('hide');
        calculateWeights();
    }

    // Reset state when modal opens
    vehicleModal.on('show.bs.modal', function() {
        vehicleModal.data('selection-confirmed', false);
        _vehicleActiveIndex = -1;
        vehicleSearch.val('');
    });

    // Focus search when modal opens
    vehicleModal.on('shown.bs.modal', function() {
        vehicleSearch.focus();
        const $rows = vehicleTableBody.find('.selectable-row:visible');
        if ($rows.length > 0) {
            vehicleHighlightRow(0);
        }
    });

    // Move focus only after modal is fully hidden to prevent focus hijacking
    vehicleModal.on('hidden.bs.modal', function() {
        if (vehicleModal.data('selection-confirmed')) {
            setTimeout(() => {
                const isManual = $('input[name="is_manual"]').val() === '1';
                if (!isManual) {
                    $('button[type="submit"]').addClass('disabled cursor-not-allowed');
                    $('#tare_weight').prop('readonly', true);
                    $('#weight_location').focus();
                } else {
                    $('#tare_weight').focus();
                }
            }, 50);
        }
    });

    // Row selection via click
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

    // Client-side Filter
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
        
        if (visibleRows.length > 0) {
            vehicleHighlightRow(0);
        }
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
        
        // Also ensure old net weights are captured if empty
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

        // Apply new tare and trigger recalculation of net weight
        $('#new_tare_weight').val(newTare.toFixed(3));
        $('#new_gross_weight').val(oldGross.toFixed(3));
        $('#new_challan_weight').val(oldChallan.toFixed(3));
        
        $('#tare_weight').val(newTare.toFixed(3)).trigger('change');
        calculateWeights();
        
        // After calculation, update new net weights
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
                        $('#tare_weight').val(fetchedWeight.toFixed(3));
                        $('#old_tare_weight').val(fetchedWeight.toFixed(3));
                    } else {
                        $('#gross_weight').val(fetchedWeight.toFixed(3));
                        $('#old_gross_weight').val(fetchedWeight.toFixed(3));
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

    setTimeout(() => {
        $('#grn_in_date').focus().select();
    }, 100);
});

/**
 * Initialize Select2 for all relevant dropdowns
 */
function bindSelect2() {
    const selectIdArray = [
        '#item_id', '#party_id','#godown_id','#bag_type',
        '#godown_unit_id', '#transporter_id', 
        '#is_crossing', '#weight_location'
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
        .addField('#grn_in_date', [{ rule: 'required', errorMessage: 'GRN In Date is required' }])
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
        // .addField('#lr_number', [{ rule: 'required', errorMessage: 'L.R. Number is required' }])
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

    if ($submitBtn.hasClass('disabled')) {
        showToast('warning', 'Please capture weight before saving.');
        return;
    }

    // Auto-trigger moisture calculation before saving to ensure new_* weights are populated
    const moistureVal = parseFloat($('input[name="moisture"]').val()) || 0;
    if (moistureVal > 0) {
        $('#btn_moisture').trigger('click');
    }

    $submitBtn.prop('disabled', true);

    // Convert dates to Y-m-d for backend validation
    const formData = new FormData(form);
    const dateFields = ['grn_in_date', 'challan_date', 'date_in', 'grn_out_date'];

    dateFields.forEach(field => {
        if (formData.has(field)) {
            const val = formData.get(field).trim();
            if (val) {
                formData.set(field, formatDateToYMD(val));
            } else {
                // Remove empty date fields so backend receives null (not empty string)
                formData.delete(field);
            }
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

    // If vehicle was selected from modal, we are essentially completing/closing that cycle
    const Id = formData.get('id');
    const isUpdate = Id && Id !== '0' && Id !== '';
    
    let url = productInStoreUrl;

    if (isUpdate) {
        formData.set('is_cycle', 'close');
        url = productInUpdateUrl.replace(':id', Id);
        formData.append('_method', 'PUT');
    } else {
        formData.set('is_cycle', 'open');
        formData.set('net_weight', 0);
        formData.set('net_weight_wt_bag', 0);
    }

    formData.set('in_out_status', 'in');

    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function () {
            const actionWord = isUpdate ? 'updating' : 'creating';
            showLoader(`Please wait, ${actionWord} Product In…`);
        },
        success: function (response) {
            if (response.success) {
                const orderSerial = response.data?.grn_serial ?? '';
                const successWord = isUpdate ? 'updated' : 'created';
                const html = `Product In <b>${orderSerial}</b> ${successWord} successfully`;
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
