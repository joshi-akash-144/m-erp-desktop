$(document).ready(function() {
    
    function safeParseFloat(val, fallback = 0) {
        const parsed = parseFloat(val);
        return isNaN(parsed) ? fallback : parsed;
    }
    
    let grn_id = null;
    
    bindSelect2();
    
    // Focus the Select2 dropdown on page load
    $('#grn_id').select2('focus');

    // Clear form when bill number is removed
    $('#grn_id').on('change', function() {
        if (!$(this).val()) {
            clearFormData();
            $('#btn_find_grn').html('Find').prop('disabled', false);        
        }
    });

    $('#print_btn').on('click', function() {
        const id = $(this).data('id');
        if (id) {
            const url = printIndividualAnalysisUrl.replace(':id', id);
            printReport(url);
        }
    });

    $('#email_btn').on('click', function() {
        const id = $(this).data('id');
        if (id) {
            openEmailModal([id], $(this));
        }
    });

    $('#btn_find_grn').on('click', function() {
        const grnSerial = $('#grn_id').val();
        if (!grnSerial) {
            showToast('warning', 'Please Enter a GRN Serial');            
            return;
        }                    

        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled', true);

        $.ajax({
            url: getGrnDataUrl,
            method: 'GET',
            data: { 
                grn_serial: grnSerial,
            },
            success: function(response) {
                if (response.success) {                
                    populateFormData(response);
                    $('.actual').first().focus();                                      
                } else {
                    Swal.fire({
                        icon: "warning",
                        title: "Error",
                        text: response.message || 'Error fetching data',
                        confirmButtonText: "OK",
                        allowEnterKey: true, 
                        allowOutsideClick: false,
                    }).then((result) => {   
                        if (result.isConfirmed) {
                            $('#godown_analysis_form').trigger('reset');
                            $('#grn_serial').trigger('change');
                            clearFormData();
                        }
                    });
                }
            },
            error: function(xhr) {
                console.error(xhr);                
                Swal.fire({
                    icon: "warning",
                    title: "Error",
                    text: xhr.responseJSON?.message || 'Error fetching data',
                    confirmButtonText: "OK",
                    allowEnterKey: true, 
                    allowOutsideClick: false,
                }); 
            },
            complete: function() {
                $btn.html(originalHtml).prop('disabled', false);
            }
        });
    });

    function populateFormData(data) {
        // --- Smart Form: Detection & Mode Switch ---
        const existing = data.existing_analysis;
        const $form = $('#godown_analysis_form');
        const $submitBtn = $('#btn_submit_main');
               
        if (existing) {
            $submitBtn.html('<i class="fa-solid fa-pencil me-1"></i> Update').prop('disabled', false);
            const updateUrl = updateAnalysisUrl.replace(':id', existing.id);
            $form.attr('action', updateUrl);
            $('#uuid').val(existing.uuid);
            $('#print_btn').data('id', existing.id);
            $('#email_btn').data('id', existing.id);
            $('#print_btn, #email_btn').removeClass('d-none');
        } else {
            $submitBtn.html('<i class="fa-solid fa-floppy-disk me-1"></i> Save').prop('disabled', false);
            $form.attr('action', storeAnalysisUrl);
            $('#print_btn, #email_btn').addClass('d-none');
        }

        // --- GRN Data ---
        const grn = data.grn;
        grn_id = grn.id;
        
        $('#account_name').text(grn.account ? grn.account.name : '--');
        $('#account_city').text(grn.account ? grn.account.city : '--');
        $('#date').text(grn.grn_date ? formatDateToDMY(grn.grn_date) : '--');
        $('#reference_no').text(grn.reference_number || '--');
        $('#vehicle_no').text(grn.vehicle_number || '--');
        $('#file_no').text(grn.file_number || '--');

        let totalAmount = 0;
        let detailsText = [];
        let totalQty = 0;
        let rates = [];
        let conditions = [];
        let products = [];
        let destinations = [];
        
        if (Array.isArray(grn.details)) {
            grn.details.forEach(item => {
                let qty = safeParseFloat(item.party_quantity);
                let rate = safeParseFloat(item.inclusive_rate);
                let amt = qty * rate;                                  
                totalAmount += amt;
                totalQty += qty;
                detailsText.push(`(${rate.toFixed(2)} × ${qty.toFixed(3)} = ${amt.toFixed(2)})`);
                rates.push(rate.toFixed(2));
                
                if (item.condition && !conditions.includes(item.condition.name)) {
                    conditions.push(item.condition.name);
                }
                if (item.item && !products.includes(item.item.name)) {
                    products.push(item.item.name);
                }
                if (item.destination && !destinations.includes(item.destination.name)) {
                    destinations.push(item.destination.name);
                }
            });
        }
        
        $('#qty').text(totalQty.toFixed(3));
        $('#rate').text(rates.length > 0 ? rates.join(', ') : '0.00');
        $("#amount").text(totalAmount.toFixed(2));
        $('#condition').text(conditions.length > 0 ? conditions.join(', ') : '--');
        $('#pro').text(products.length > 0 ? products.join(', ') : '--');
        $('#destination').text(destinations.length > 0 ? destinations.join(', ') : '--');
        
        let $detailSpan = $("#amount").closest("td").find(".detail-span");

        if (detailsText.length > 1) {
            $detailSpan
                .removeClass("d-none")        
                .html(detailsText.join("<br>"));
        } else {
            $detailSpan.addClass("d-none").empty();              
        }

        // --- Analysis Table ---
        const tableBody = $('#parameter_table tbody');
        tableBody.empty();
        $('#paraTotal').empty();
        
        if (data.parameters && data.parameters.length > 0) {
            let rows = '';
            data.parameters.forEach(function(param, index) {  
                let actualVal = '';
                
                rows += `
                    <tr data-range="${param.element_range || 0}" data-element-id="${param.element}">
                        <td> <span class="fw-bold p-2" id="element_name_${index}">${param.element_name || '--'}</span></td>
                        <td>
                             <span id="guarantee_${index}" class="fw-bold form-control border-0 bg-light text-end">${safeParseFloat(param.guarantee).toFixed(2)}</span>
                        </td>
                        <td>
                            <input type="number" step="any" 
                                   id="actual_${index}" 
                                   class="form-control fw-bold calc-actual text-end actual" 
                                   data-index="${index}" 
                                   value="${(param.actual_val !== undefined && param.actual_val !== null) ? safeParseFloat(param.actual_val).toFixed(4) : ''}"
                                   placeholder="0.0000">
                        </td>
                        <td>
                             <span class="form-control fw-bold align-middle border-0 bg-light text-end" id="diff_${index}">${(param.diff_val !== undefined && param.diff_val !== null) ? Math.abs(safeParseFloat(param.diff_val)).toFixed(4) : '0.0000'}</span>
                        </td>
                        <td>
                             <span id="rebate_per_${index}" 
                                   class="form-control fw-bold align-middle border-0 bg-light text-end">${(param.rebate_percentage_val !== undefined && param.rebate_percentage_val !== null) ? safeParseFloat(param.rebate_percentage_val).toFixed(2) : '0.00'}</span>
                        </td>
                        <td>
                             <span id="rebate_${index}" 
                                   data-ranges='${JSON.stringify(param.ranges || [])}'
                                   data-pct='${param.rebate_percentage_val || 0}'
                                   class="form-control fw-bold align-middle border-0 bg-light text-end">${(param.rebate_val !== undefined && param.rebate_val !== null) ? safeParseFloat(param.rebate_val).toFixed(2) : '0.00'}</span>
                        </td>
                        <td>
                             <span id="premium_${index}" class="form-control fw-bold align-middle border-0 bg-light text-end">${(param.premium_val !== undefined && param.premium_val !== null) ? safeParseFloat(param.premium_val).toFixed(2) : '0.00'}</span>
                        </td>
                    </tr>
                `;
            });
            tableBody.html(rows);
            renderTableFooter();
            calculateGrandTotals();
        } else {
            tableBody.append('<tr><td colspan="8" class="text-center text-muted p-4">No parameters found for the selected GRN.</td></tr>');
            renderTableFooter();
        }
    }

    function renderTableFooter() {
        const html = `
            <tr>
                <td colspan="4" class="text-end text-primary fw-bold text-uppercase border-0" style="font-size: 0.8rem;">Total GRN Rebate:</td>
                <td><input class="form-control text-end fw-bold text-danger font-monospace" type="text" readonly="" value="0.00" id="rebate_percentage_total" name="rebate_percentage_total" style="font-size: 0.85rem;"></td>
                <td><input class="form-control text-end fw-bold text-danger font-monospace" type="text" readonly="" value="0.00" id="rebate_total" name="rebate_total" style="font-size: 0.85rem;"></td>
                <td><input class="form-control text-end fw-bold text-dark" type="text" readonly="" value="0.00" id="premium_total" name="premium_total" style="font-size: 0.85rem;"></td>
            </tr>
        `;
        $('#paraTotal').html(html);
    }
    
    function clearFormData() {
        // --- Clear GRN Section ---
        $('#reference_number, #account_name, #account_city, #date, #grn_no, #vehicle_no, #condition').text('--');
        $('#qty, #rate, #amount').text('0.00');
        $('.detail-span').addClass('d-none').empty();

        // --- Clear Analysis Table & Footer ---
        $('#parameterTable').empty();
        $('#paraTotal').empty();
    }
    
    // Form Submission
    $('#godown_analysis_form').on('submit', function(e) {
        e.preventDefault();
        const uuid = $('#uuid').val();
        const $btn = $('#btn_submit_main');
        const grnSerial = $('#grn_id').val();
        
        if (!grnSerial || !grn_id) {
            showToast('warning', 'Please select a GRN Number');
            return;
        }

        const performSubmit = () => {
            // Collect Table Rows
            const items = [];
            $('#parameter_table tbody tr').each(function() {
                const index = $(this).find('.calc-actual').data('index');
                const elementId = $(this).attr('data-element-id');

                if (elementId) {
                    items.push({
                        element_id: elementId,
                        actual: $(`#actual_${index}`).val() || 0,
                        diff: $(`#diff_${index}`).text() || 0,
                        rebate: $(`#rebate_${index}`).text() || 0,
                        premium: $(`#premium_${index}`).text() || 0,
                        rebate_percentage: $(`#rebate_per_${index}`).text() || 0,
                        guarantee: $(`#guarantee_${index}`).text() || 0
                    });
                }
            });

            // Sum per-row rebate percentages
            let totalRebatePct = 0;
            $('#parameter_table tbody tr').each(function() {
                const index = $(this).find('.calc-actual').data('index');
                totalRebatePct += parseFloat($(`#rebate_${index}`).data('pct')) || 0;
            });

            const formData = {
                uuid: uuid,
                grn_id: grn_id,
                grn_serial: grnSerial,
                rebate_total: $('#rebate_total').val() || 0,
                premium_total: $('#premium_total').val() || 0,
                rebate_percentage: totalRebatePct.toFixed(4),
                items: items,
                _token: $('meta[name="csrf-token"]').attr('content')
            };
            
            const formAction = $('#godown_analysis_form').attr('action') || storeAnalysisUrl;
            const isUpdate = $btn.text().trim() === 'Update';

            $btn.html('<i class="fa fa-spinner fa-spin me-1"></i> Processing...').prop('disabled', true);            

            $.ajax({
                url: formAction,
                method: "POST",
                data: formData,            
                success: function(response) {            
                    if (response.success) {                    
                        Swal.fire({
                            title: isUpdate ? "Updated!" : "Saved!",
                            html: isUpdate ? `Godown Analysis for <span class="fw-bold">${grnSerial}</span> updated successfully.` : `Godown Analysis for <span class="fw-bold">${grnSerial}</span> saved successfully.`,
                            icon: "success",
                            confirmButtonText: "OK",
                            allowEnterKey: true, 
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $('#godown_analysis_form').trigger('reset');
                                $('#grn_id').val(null).trigger('change');
                                clearFormData();
                                window.location.reload();
                            }
                        });
                        
                    } else {
                        showToast('error', response.message || 'Error saving data');
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'Internal Server Error';
                    showToast('error', errorMsg);
                    console.error(xhr);
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    if ($btn.text().trim() === 'Processing...') {
                        if (isUpdate) {
                            $btn.html('<i class="fa-solid fa-pencil me-1"></i> Update');
                        } else {
                            $btn.html('<i class="fa-solid fa-floppy-disk me-1"></i> Save');
                        }
                    }
                }
            });
        };
        
        const rebate_total = parseFloat($('#rebate_total').val()) || 0;
        
        if (rebate_total <= 0) {
            let text = "Rebate is " + rebate_total.toFixed(2) + ". Are you sure you want to pass this report?";

            Swal.fire({
                title: "Warning!",
                text: text,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "OK",
                cancelButtonText: "Cancel",
                allowEnterKey: true, 
                allowOutsideClick: false,   
            }).then((result) => {
                if (result.isConfirmed) {
                    performSubmit();
                }
            });
        } else {
            performSubmit();
        }
    });

    function calculateGrandTotals() {
        let tsr = 0, tsp = 0, tsrp = 0;

        // Sum values from the dynamic row spans
        $('#parameter_table tbody tr').each(function() {
            const index = $(this).find('.calc-actual').data('index');
            tsr += parseFloat($(`#rebate_${index}`).text()) || 0;
            tsp += parseFloat($(`#premium_${index}`).text()) || 0;
            tsrp += parseFloat($(`#rebate_per_${index}`).text()) || 0;
        });

        $('#rebate_percentage_total').val(tsrp.toFixed(2));
        $('#rebate_total').val(tsr.toFixed(2));
        $('#premium_total').val(tsp.toFixed(2));
    }

    // Calculate difference when actual is entered
    $(document).on('focusout', '.calc-actual', function() {
        const index = $(this).data('index');
        const val = $(this).val();
        
        if (val === '') {
            $(`#diff_${index}, #rebate_${index}, #premium_${index}`).text('0.00');
            $(`#diff_${index}`).text('0.0000');
            $(`#rebate_${index}`).data('pct', 0);
            calculateGrandTotals();
            return;
        }

        const rawActual = parseFloat(val) || 0;
        // Calculation only: round up only if 3rd decimal digit is strictly > 5, else truncate
        const _thirdDecimal = Math.floor(Math.abs(rawActual) * 1000) % 10;
        const actualForCalc = _thirdDecimal > 5
            ? Math.ceil(rawActual * 100) / 100
            : Math.trunc(rawActual * 100) / 100;
        const guarantee = parseFloat($(`#guarantee_${index}`).text()) || 0;
        
        // Display diff using raw actual (full precision)
        const displayDiff = guarantee - rawActual;
        $(`#diff_${index}`).text(Math.abs(displayDiff).toFixed(4));
        
        // Use rounded actual for rebate/premium calculation only
        const diff = guarantee - actualForCalc;
        
        if (diff === 0) {
            $(`#rebate_${index}, #premium_${index}`).text('0.00');
            $(`#rebate_${index}`).data('pct', 0);
            calculateGrandTotals();
            return;
        }
        
        const getRebateResults = (actualVal, guaranteeVal) => {
            const $s_el = $(`#rebate_${index}`);
            let slabs = $s_el.data('ranges');
            if (typeof slabs === 'string') { try { slabs = JSON.parse(slabs); } catch (e) { slabs = []; } }
            slabs = Array.isArray(slabs) ? slabs : [];

            const rowRange = parseInt($(`#element_name_${index}`).closest('tr').data('range')) || 0;
            const rangeType = (slabs.length > 0 && slabs[0].element_range) ? parseInt(slabs[0].element_range) : rowRange;
            
            let valueToSearch = 0;
            if (rangeType === 2) { // Inverse (High bad)
                valueToSearch = actualVal - guaranteeVal;
            } else if (rangeType === 1) { // Standard (Low bad)
                valueToSearch = guaranteeVal - actualVal;
            }

            const results = { rebate: 0, premium: 0, rebate_pct: 0 };
            const elementName = ($(`#element_name_${index}`).text() || '').toUpperCase();

            if (elementName.includes('TORN')) {
                results.rebate = actualVal;
                return results;
            }
        
            if (elementName.includes('ALBUMIN') && actualVal === 0) {
                results.rebate = 0;
                results.premium = 0;
                return results;
            }
            
            const billStr = $('#amount').text().replace(/[^\d.-]/g, '') || '0';
            const bill = parseFloat(billStr) || 0;

            if (slabs.length > 0) {
                let totalRebatePct = 0;
                let totalPremiumPct = 0;
                let remainingValue = Math.abs(valueToSearch);
                let lastSlab = null;

                if (rangeType === 1) {
                    slabs.sort((a, b) => parseFloat(b.from) - parseFloat(a.from));
                } else {
                    slabs.sort((a, b) => parseFloat(a.from) - parseFloat(b.from));
                }

                for (let slab of slabs) {
                    const rangeSize = Math.abs(parseFloat(slab.to) - parseFloat(slab.from));
                    const rate = parseFloat(slab.rebate) || 0;
                    const p_rate = parseFloat(slab.premium) || 0;
                    lastSlab = slab;

                    if (remainingValue <= 0) break;

                    const consumed = Math.min(remainingValue, rangeSize);
                    if (valueToSearch > 0) totalRebatePct += consumed * rate;
                    else totalPremiumPct += consumed * p_rate;
                    
                    remainingValue -= consumed;
                }

                if (remainingValue > 0 && lastSlab) {
                    const rate = parseFloat(lastSlab.rebate) || 0;
                    const p_rate = parseFloat(lastSlab.premium) || 0;
                    if (valueToSearch > 0) totalRebatePct += remainingValue * rate;
                    else totalPremiumPct += remainingValue * p_rate;
                }

                results.rebate = (totalRebatePct / 100) * bill;
                results.rebate_pct = totalRebatePct;

                const elementId = parseInt($(`#element_name_${index}`).closest('tr').data('element-id')) || 0;
                let matchedPremium = 0;
                if (elementId !== 5 ) {
                    const match = slabs.find(slab => actualVal >= parseFloat(slab.from) && actualVal <= parseFloat(slab.to));
                    if (match) {
                        matchedPremium = parseFloat(match.premium) || 0;
                    }
                }
                results.premium = matchedPremium;
            }
            return results;
        };
        
        const rebateResults = getRebateResults(actualForCalc, guarantee);
        $(`#rebate_per_${index}`).text((rebateResults.rebate_pct || 0).toFixed(2));
        $(`#rebate_${index}`).text(rebateResults.rebate.toFixed(2)).data('pct', rebateResults.rebate_pct || 0);
        $(`#premium_${index}`).text(rebateResults.premium.toFixed(2));
        
        calculateGrandTotals();
    });

    $(document).on('blur', '.calc-actual', function() {
        const val = $(this).val();
        if (val !== '') {
            $(this).val(safeParseFloat(val).toFixed(4));
        }
    });

});

function bindSelect2() {
    const selectIdArray = ['#grn_id'];
    selectIdArray.forEach(element => {
        $(element).select2({
            theme: "bootstrap-5",
            allowClear: true,
            placeholder: "Select " + element.replace('#', '').replace('.', '').replace('_id', '').replace('_serial', '') + " ...",
        });
    });

    $(document).on("select2:open", function (e) {
        const selectElement = $(e.target);
        const searchInput = selectElement
            .data("select2")
            .$dropdown.find(".select2-search__field");

        searchInput
            .off("keydown.select2Enter")
            .on("keydown.select2Enter", (event) => {
                if (event.which === 13) {
                    event.preventDefault();
                    selectElement.select2("close");
                    moveFocusToNextField(selectElement);
                }
            });
    });
}
