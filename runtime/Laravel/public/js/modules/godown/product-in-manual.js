/**
 * Product In Manual Module
 * Handles UI interactions, calculations, and validation for the Godown Product In Manual page.
 */
document.addEventListener("DOMContentLoaded", function () {

    // Initialize Select2 dropdowns
    bindSelect2();

    $('#grn_date').focus();
    
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
    const calculateNetWeights = () => {
        const tareWeight = parseFloat($('#tare_weight').val()) || 0;
        
        if (tareWeight > 0) {
            calculateNetWeight('#gross_weight', '#tare_weight', '#net_weight', 3);
            calculateNetWeightWithoutBags('#gross_weight', '#tare_weight', '#bag_qty', '#bag_type', '#net_weight_no_bags', 3);
        } else {
            $('#net_weight').val('0.000');
            $('#net_weight_no_bags').val('0.000');
        }

        // Update live summary display
        const net = parseFloat($('#net_weight').val()) || 0;
        if(Number(net) !== 0){
            $('.net-weight-display').text(net.toFixed(3));
        }else{
            $('.net-weight-display').text('0.000');
        }
    };

    $('.weight-input, #bag_qty').on('input', calculateNetWeights);
    $(document).on('change', '#bag_type', calculateNetWeights);

    /**
     * Conditional Visibility & Godown Fetching
     */
    $('#godown_destination').on('change', function() {
        const destinationId = $(this).val();
        if (destinationId) {
            $('#godown_unit_container').show();

            $.ajax({
                url: getGodownsUrl,
                method: 'GET',
                data: { destination_id: destinationId },
                success: function(data) {
                    const $godownUnit = $('#godown_unit_location_id');
                    $godownUnit.empty();
                    $godownUnit.append('<option value="">--Select Godown Unit--</option>');
                    
                    if (data && data.length > 0) {
                        data.forEach(function(item) {
                            $godownUnit.append(`<option value="${item.id}">${item.godown_name}</option>`);
                        });
                    }
                    
                    const pendingVal = $godownUnit.data('pending-val');
                    if (pendingVal) {
                        $godownUnit.val(pendingVal).trigger('change');
                        $godownUnit.removeData('pending-val');
                    } else {
                        $godownUnit.trigger('change');
                    }
                }
            });
        } else {
            $('#godown_unit_container').hide();
        }
    }).trigger('change');

    // Fetch pending purchase orders when Supplier is selected
    $('#account_id').on('change', function() {
        const accountId = $(this).val();
        const $poSelect = $('#po_no');
        
        $poSelect.empty().append('<option value="">--Select Purchase Order--</option>');

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

            $.ajax({
                url: fetchPendingPurchaseOrdersUrl,
                method: 'GET',
                data: { account_id: accountId },
                success: function(response) {
                    if (response.success && response.data) {
                        response.data.forEach(function(po) {
                            $poSelect.append(`<option value="${po.id}">${po.order_serial}</option>`);
                        });

                        const pendingPoVal = $poSelect.data('pending-val');
                        if (pendingPoVal) {
                            $poSelect.val(pendingPoVal).trigger('change');
                            $poSelect.removeData('pending-val');
                        } else {
                            $poSelect.trigger('change');
                        }
                    }
                }
            });
        } else {
            $('#grn_type').val('');
            $poSelect.trigger('change');
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
        const grnSerial = $tr.data('grn-serial');
        
        $('#is_set_vehicle').val(godownId);
        $('#vehicle_no').val(vehicleNo);

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

        const poId = $tr.data('po-id');
        if (poId !== undefined && poId !== null && poId !== '') {
            $('#po_no').data('pending-val', poId);
        }

        const godownUnitLocationId = $tr.data('godown-unit-location-id');
        if (godownUnitLocationId) {
            $('#godown_unit_location_id').data('pending-val', godownUnitLocationId);
        }

        const destinationId = $tr.data('destination-id');
        if (destinationId) {
            $('#godown_destination').val(destinationId).trigger('change');
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
        if (lrNumber) {
            $('#lr_number').val(lrNumber);
        }
        
        if ($tr.data('gross') !== undefined) {
            const parsedGross = parseFloat($tr.data('gross'));
            if (!isNaN(parsedGross)) {
                const gross = parsedGross.toFixed(3);
                $('#gross_weight').val(gross).trigger('change');
            }
        }
        if ($tr.data('bagQty') !== undefined) $('#bag_qty').val($tr.data('bagQty'));

        calculateNetWeights();

        if (!grnSerial) {
            const accountId = $tr.data('account-id');
            if (accountId) {
                $('#account_id').val(accountId).trigger('change');
            }
            vehicleModal.data('selection-confirmed', true);
            vehicleModal.modal('hide');
            return;
        }

        // Fetch Full Details via AJAX (same as Product In)
        $.ajax({
            url: fetchGrnDetailsUrl,
            method: 'GET',
            data: { grn_serial: grnSerial },
            success: function(data) {
                if (data) {
                    // Set flag and store original values for validation
                    $('#original_vehicle_no').val(data.vehicle_number);
                    $('#original_party_bill_no').val(data.reference_number);

                    // Header Info
                    $('#grn_id').val(data.id);
                    $('#grn_serial').val(data.grn_serial);
                    if (data.gst_type) {
                        const grnType = data.gst_type.toUpperCase() === 'INTERSTATE' ? 'INTERSTATE' : 'LOCAL';
                        $('#grn_type').val(grnType).trigger('change');
                    }

                    if (data.grn_date) {
                        const dmyDate = formatDateToDMY(data.grn_date);
                        $('#grn_date').val(dmyDate);
                        $('#challan_date').val(dmyDate);
                        $('#date_in').val(dmyDate); // Set Date In as GRN Date

                        // Set Time In as GRN Creation Time
                        formatAndSetTimeFields(data.created_at);
                    }

                    $('#vehicle_no').val(data.vehicle_number);
                    
                    if (data.remarks) {
                        $('#remarks').val(data.remarks);
                    } else {
                        const remarks = $tr.data('remarks');
                        if (remarks !== undefined && remarks !== null) {
                            $('#remarks').val(remarks);
                        }
                    }
                    
                    // Party & Broker
                    if (data.account_id) $('#account_id').val(data.account_id).trigger('change');
                    if (data.broker_id) $('#broker_id').val(data.broker_id).trigger('change');
                    
                    // Product & Details
                    if (data.details && data.details.length > 0) {
                        const firstItem = data.details[0];
                        $('#item_id').val(firstItem.item_id).trigger('change');
                        if (firstItem.rate) $('input[name="rate"]').val(parseFloat(firstItem.rate).toFixed(3));
                        
                        // Set Destinations
                        if (godownUnitLocationId) {
                            $('#godown_unit_location_id').data('pending-val', godownUnitLocationId);
                        }
                        if (firstItem.destination_id) {
                            $('#godown_destination').val(firstItem.destination_id).trigger('change');
                        }

                        if(firstItem.party_destination){
                            $('#party_destination').val(firstItem.party_destination).trigger('change');
                        }
                        
                        if (firstItem.purchase_order && firstItem.purchase_order.destination_id) {
                            $('#party_destination').val(firstItem.purchase_order.destination_id).trigger('change');
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
                    
                    calculateNetWeights();
                }
            },
            error: function() {
                if (typeof showToast === 'function') showToast('error', 'Failed to fetch GRN details.');
            }
        });

        vehicleModal.data('selection-confirmed', true);
        vehicleModal.modal('hide');
        calculateNetWeights();
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
            setTimeout(() => { $('#vehicle_no').focus(); }, 50);
        }
    });

    vehicleTableBody.on('click', '.selectable-row', function() {
        applyVehicleSelection($(this));
    });

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
     * Submit Form
     */
    function submitGodownForm(form) {
        const $form = $(form);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.html();

        $submitBtn.prop('disabled', true);

        const formData = new FormData(form);
        
        // Mapping for Backend to match database columns
        const mappings = {
            'bag_qty': 'bag_count',
            'grn_type': 'gst_type',
            'po_no': 'purchase_order_id',
            'net_weight_no_bags': 'net_weight_wt_bag',
            'party_destination': 'party_destination_id'
        };

        for (const [oldKey, newKey] of Object.entries(mappings)) {
            if (formData.has(oldKey)) {
                formData.set(newKey, formData.get(oldKey));
                if (oldKey !== newKey) {
                    formData.delete(oldKey);
                }
            }
        }

        // Date Formatting
        const dateFields = ['grn_date', 'challan_date', 'date_in', 'date_out'];
        dateFields.forEach(field => {
            if (formData.has(field)) {
                formData.set(field, formatDateToYMD(formData.get(field)));
            }
        });

        if (formData.has('gst_type')) {
            formData.set('gst_type', formData.get('gst_type').toLowerCase());
        }

        formData.set('in_out_status', 'in');
        formData.set('is_manual', '1');
        
        const setVehicleId = formData.get('is_set_vehicle');
        const isUpdate = setVehicleId && setVehicleId != '0';
        if (isUpdate) {
            formData.set('godown_module_id', setVehicleId);
            formData.set('is_cycle', 'close');
        } else {
            formData.set('is_cycle', 'open');
        }

        $.ajax({
            url: productInManualStoreUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                const actionWord = isUpdate ? 'updating' : 'creating';
                showLoader(`Please wait, ${actionWord} Product In Manual…`);
            },
            success: function (response) {
                if (response.success) {
                    const orderSerial = response.data?.grn_serial ?? '';
                    const successWord = isUpdate ? 'updated' : 'created';
                    const html = `Product In Manual <b>${orderSerial}</b> ${successWord} successfully`;
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
                    showToast('error', response.message || 'Failed to save record.', 5000);
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function (xhr) {
                $submitBtn.prop('disabled', false).html(originalText);
                const message = xhr.responseJSON?.message || 'Server error occurred.';
                showToast('error', message, 5000);
            },
            complete: function () {
                hideLoader();
            }
        });
    }

    /**
     * Form Validation
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
            .addField('#vehicle_no', [{ rule: 'required', errorMessage: 'Vehicle No is required' }])
            .addField('#grn_date', [{ rule: 'required', errorMessage: 'GRN Date is required' }])
            .addField('#item_id', [{ rule: 'required', errorMessage: 'Product is required' }])
            .addField('#godown_destination', [{ rule: 'required', errorMessage: 'Destination is required' }])
            .addField('#godown_unit_location_id', [{ rule: 'required', errorMessage: 'Godown Unit is required' }])
            .addField('#account_id', [{ rule: 'required', errorMessage: 'Party is required' }])
            .addField('#reference_number', [{ rule: 'required', errorMessage: 'Party Bill No is required' }])
            // .addField('#challan_bags', [{ rule: 'required', errorMessage: 'Challan Bags is required' }])
            .addField('#party_destination', [{ rule: 'required', errorMessage: 'Party Destination is required' }])
            .addField('#transporter_id', [{ rule: 'required', errorMessage: 'Transporter is required' }])
            // .addField('#lr_number', [{ rule: 'required', errorMessage: 'L.R. Number is required' }])
            .onSuccess(function (event) {
                submitGodownForm(event.target);
            })
            .onFail(() => {
                if (toastShown) return;
                toastShown = true;
                if (typeof showToast === 'function') showToast("error", "Please fix the highlighted fields before saving.");
                setTimeout(() => (toastShown = false), 800);
            });
    }

    // Reset vehicle ID if input is cleared manually
    $("#vehicle_no").on("input", function() {
        if (!$(this).val().trim()) {
            $("#is_set_vehicle").val(0);
        }
    });

    initValidation();
});

/**
 * Initialize Select2
 */
function bindSelect2() {
    const selectIdArray = [
        '#broker_id', '#item_id', '#account_id',
        '#party_destination', '#bag_type', '#godown_destination',
        '#godown_unit_location_id', '#is_crossing', '#weight_location','#po_no',
        '#transporter_id'
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
                if (typeof moveFocusToNextField === 'function') moveFocusToNextField(selectElement);
            }
        });
    });
}
