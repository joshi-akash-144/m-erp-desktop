/**
 * Product Out Manual Module
 * Handles UI interactions, calculations, and validation for the Godown Product Out Manual page.
 */
document.addEventListener("DOMContentLoaded", function () {

    // Initialize Select2 dropdowns
    bindSelect2();

    $('#dc_date').focus();

    new DateInput('#dc_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
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
            calculateNetWeightWithoutBags('#gross_weight', '#tare_weight', '#bag_count', '#bag_type', '#net_weight_wt_bag', 3);

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
    $('.weight-input, #bag_count').on('input', calculateWeights);
    $(document).on('change', '#bag_type', calculateWeights);

    // Fetch pending sales orders when Customer is selected
    $('#account_id').on('change', function() {
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
     * Conditional Visibility
     */
    $('#destination_id').on('change', function() {
        const destinationId = $(this).val();
        if (destinationId) {
            $('#godown_unit_container').show();

            // Fetch Godowns dynamically via AJAX
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
        const challanSerial = $tr.data('challan-serial');
        
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
                $('#challan_bags').val(parsedBags);
            } else {
                $('#challan_bags').val('');
            }
        } else {
            $('#challan_bags').val('');
        }

        const soId = $tr.data('so-id');
        if (soId !== undefined && soId !== null && soId !== '') {
            $('#so_no').data('pending-val', soId);
        }

        const godownUnitLocationId = $tr.data('godown-unit-location-id');
        if (godownUnitLocationId) {
            $('#godown_unit_location_id').data('pending-val', godownUnitLocationId);
        }

        const destinationId = $tr.data('destination-id');
        if (destinationId) {
            $('#destination_id').val(destinationId).trigger('change');
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
        
        if ($tr.data('tare') !== undefined) {
            const parsedTare = parseFloat($tr.data('tare'));
            if (!isNaN(parsedTare)) {
                const tare = parsedTare.toFixed(3);
                $('#tare_weight').val(tare).trigger('change');
            }
        }
        if ($tr.data('bagQty') !== undefined) $('#bag_count').val($tr.data('bagQty'));

        const dateIn = $tr.data('date-in');
        if (dateIn) {
            const dmyDate = formatDateToDMY(dateIn);
            $('#date_in').val(dmyDate);
        }

        formatAndSetTimeFields($tr.data('created-at'));

        calculateWeights();

        if (!challanSerial) {
            const accountId = $tr.data('account-id');
            if (accountId) {
                $('#account_id').val(accountId).trigger('change');
            }
            vehicleModal.data('selection-confirmed', true);
            vehicleModal.modal('hide');
            return;
        }

        $.ajax({
            url: fetchChallanDetailsUrl,
            method: 'GET',
            data: { challan_serial: challanSerial },
            success: function(data) {
                if (data) {
                    // Header Info
                    $('#delivery_challan_id').val(data.id);
                    $('#dc_serial').val(data.challan_serial);

                    if (data.gst_type) {
                        const challanType = data.gst_type.toUpperCase() === 'INTERSTATE' ? 'INTERSTATE' : 'LOCAL';
                        $('#challan_type').val(challanType).trigger('change');
                    }

                    if (data.dc_date) {
                        const dmyDate = formatDateToDMY(data.dc_date);
                        $('#dc_date').val(dmyDate);

                        const dateInAttr = $tr.data('date-in');
                        if (dateInAttr) {
                            const dmyDateIn = formatDateToDMY(dateInAttr);
                            $('#date_in').val(dmyDateIn);

                            formatAndSetTimeFields($tr.data('created-at'));
                        } else {
                            $('#date_in').val(dmyDate); // Set Date In as DC Date

                            // Set Time In as DC Creation Time
                            formatAndSetTimeFields(data.created_at);
                        }
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
                        if (firstItem.sales_order_id) {
                            $('#so_no').data('pending-val', firstItem.sales_order_id);
                        }
                        $('#item_id').val(firstItem.item_id).trigger('change');
                        if (firstItem.rate) $('input[name="rate"]').val(parseFloat(firstItem.rate).toFixed(3));
                        
                        // Set Destinations
                        if (godownUnitLocationId) {
                            $('#godown_unit_location_id').data('pending-val', godownUnitLocationId);
                        }
                        if (firstItem.destination_id) {
                            $('#destination_id').val(firstItem.destination_id).trigger('change');
                        }

                        if(firstItem.party_destination){
                            $('#party_destination').val(firstItem.party_destination).trigger('change');
                        }
                        
                        if (firstItem.sales_order && firstItem.sales_order.destination_id) {
                            $('#party_destination').val(firstItem.sales_order.destination_id).trigger('change');
                        }
                    }

                    // Transaction Info
                    if (data.reference_number) $('#reference_number').val(data.reference_number);
                    if (data.bag_count) $('#bag_count').val(data.bag_count);
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
                $('#gross_weight').focus();
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
     * Submit the Godown Form via AJAX
     */
    function submitGodownForm(form) {
        const $form = $(form);
        const $submitBtn = $form.find('button[type="submit"]');

        const originalText = $submitBtn.html();
        $submitBtn.prop('disabled', true);

        const formData = new FormData(form);
        
        // --- Date Formatting ---
        const dateFields = ['dc_date', 'date_in', 'date_out'];
        dateFields.forEach(field => {
            if (formData.has(field)) {
                formData.set(field, formatDateToYMD(formData.get(field)));
            }
        });

        const setVehicleId = formData.get('is_set_vehicle');
        const isUpdate = setVehicleId && setVehicleId != '0';
        if (isUpdate) {
            formData.set('godown_module_id', setVehicleId);
            formData.set('is_cycle', 'close');
        } else {
            formData.set('is_cycle', 'open');
        }

        formData.set('in_out_status', 'out');

        if (formData.has('party_destination')) {
            formData.set('party_destination_id', formData.get('party_destination'));
            formData.delete('party_destination');
        }

        $.ajax({    
            url: productOutManualStoreUrl,
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
                    const orderSerial = response.data?.dc_serial ?? '';
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
            }
        });
    }

    // Reset vehicle ID if input is cleared manually
    $("#vehicle_no").on("input", function() {
        if (!$(this).val().trim()) {
            $("#is_set_vehicle").val(0);
        }
    });

    /**
     * Form Validation & Submission
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
            .addField('#dc_date', [{ rule: 'required', errorMessage: 'Challan Date is required' }])
            .addField('#item_id', [{ rule: 'required', errorMessage: 'Product is required' }])
            .addField('#account_id', [{ rule: 'required', errorMessage: 'Party is required' }])
            .addField('#date_out', [{ rule: 'required', errorMessage: 'Date Out is required' }])
            .addField('#godown_unit_location_id', [{ rule: 'required', errorMessage: 'Godown Unit is required' }])
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

    initValidation();
});

/**
 * Initialize Select2 for all relevant dropdowns with focus management
 */
function bindSelect2() {
    const selectIdArray = [
        '#broker_id', '#item_id', '#account_id',
        '#party_destination', '#bag_type', '#destination_id','#godown_unit_location_id',
        '#is_crossing', '#weight_location','#so_no', '#transporter_id'
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
