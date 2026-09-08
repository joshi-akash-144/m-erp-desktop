$(document).ready(function() {
    // bindSelect2();
    let pbill_pay_amount = 0;
    let base_pbill_pay_amount = 0;
    
     
    function safeParseFloat(val, fallback = 0) {
        const parsed = parseFloat(val);
        return isNaN(parsed) ? fallback : parsed;
    }
    
    
    $('#invoice_serial').focus();             

    // Clear form when bill number is removed
    $('#invoice_serial').on('change', function() {
        if (!$(this).val()) {
            clearFormData();
            $('#btn_find_sales').html('Find').prop('disabled', false);        
        }
    });

    $('#btn_find_sales').on('click', function() {
        const invoiceSerial = $('#invoice_serial').val();
        if (!invoiceSerial) {
            showToast('warning', 'Please Enter a Bill Number');            
            return;
        }                    

        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled', true);

        $.ajax({
            url: getSalesInvUrl,
            method: 'POST',
            data: { 
                invoice_serial: invoiceSerial,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {                
                    populateFormData(response);
                     $('.actual').first().focus();                                      
                } else {
                    // showToast('error', response.message || 'Could not find bill details'); 
                    Swal.fire({
                        icon: "warning",
                        title: "Error",
                        text: response.message || 'Error fetching data',
                        confirmButtonText: "OK",
                        allowEnterKey: true, 
                        allowOutsideClick: false,
                    }).then((result) => {   
                        if (result.isConfirmed) {
                            $('#dairy_analysis_form').trigger('reset');
                            $('#invoice_serial').trigger('change');
                            clearFormData();
                        }
                }   )}
            },
            error: function(xhr) {
            console.error(xhr);                
                // showToast('message', xhr.responseJSON.message || 'Error fetching data');
                Swal.fire({
                        icon: "warning",
                        title: "Error",
                        text: xhr.responseJSON.message || 'Error fetching data',
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
        // console.log('parameter', data);
        
        // --- Smart Form: Detection & Mode Switch ---
        const id = data.general ? data.general.id : null;
        const $form = $('#dairy_analysis_form');
        const $submitBtn = $('#btn_submit_main');
        const $printBtn = $('#print_btn');
        const $emailBtn = $('#email_btn');
               
        if (id) {
            // Mode: Update
            const updateUrl = updateAnalysisUrl.replace(':id', id);
            $form.attr('action', updateUrl);
            $submitBtn.html('<i class="fa-solid fa-pencil me-1"></i> Update')
                      .removeClass('btn-primary').addClass('btn-pink'); // primary for update
            $printBtn.removeClass('d-none').val(id); // Store ID for printing
            $emailBtn.removeClass('d-none');          
            
        } else {
            // Mode: Save
            $form.attr('action', storeAnalysisUrl);
            $submitBtn.html('<i class="fa-solid fa-floppy-disk me-1"></i> Save')
                      .removeClass('btn-primary').addClass('btn-pink'); // pink for save
            $printBtn.addClass('d-none');
            $emailBtn.addClass('d-none');
        }

        // --- Sales Data ---
        const sales = data.sales_data;               
        sales_bill_id = data.sales_data.sales_bill_id;
        $('#sbill_customer').text(sales.sbill_customer || '--');
        $('#sbill_customer_city').text(sales.sbill_customer_city || '--');
        $('#sbill_product').text(sales.sbill_product || '--');
        $('#sbill_condition').text(sales.sbill_condition || '--');
        $('#sbill_date').text(formatDateToDMY(sales.sbill_date) || '--');
        $('#sbill_grn_no').text(sales.sbill_grn_no || '--');
        $('#sbill_vehical_no').text(sales.sbill_vehical_no || '--');
        $('#sbill_qty').text(safeParseFloat(sales.sbill_qty).toFixed(3));
        $('#sbill_rate').text(safeParseFloat(sales.sbill_inclusive_rate).toFixed(2));       
        $('#sbill_amount').text((safeParseFloat(sales.sbill_qty) * safeParseFloat(sales.sbill_inclusive_rate)).toFixed(2));

        // --- Purchase Data ---
        const purch = data.purchase_data;
        purchase_bill_id = data.purchase_data.purchase_bill_id;
        pbill_pay_amount = purch.pbill_pay_amount;
        base_pbill_pay_amount = purch.pbill_pay_amount;
        $('#reference_number').text(purch.reference_number || '--');
        $('#pbill_supp').text(purch.pbill_supp || '--');
        $('#pbill_supp_city').text(purch.pbill_supp_city || '--');
        $('#pbill_pro').text(purch.pbill_pro || '--');
        $('#pbill_date').text(formatDateToDMY(purch.pbill_date) || '--');
        $('#pbill_grn_no').text(purch.pbill_grn_no || '--');
        $('#pbill_vehicle_no').text(purch.pbill_vehicle_no || '--');
        $('#pbill_destination').text(purch.pbill_destination || '--');
        $('#pbill_condition').text(purch.pbill_condition || '--');
        $('#pbill_file_no').text(purch.pbill_file_no || '--');
        $('#pbill_qty').text(safeParseFloat(purch.pbill_qty).toFixed(3));
        // $('#pbill_rate').text(safeParseFloat(purch.pbill_rate).toFixed(2));                
        $('#pbill_net_amount').text(safeParseFloat(purch.pbill_pay_amount).toFixed(2));
        

        let totalAmount = 0;
        let details = [];
        // console.log('details of purchase invoice', purch.details);
        
        if (Array.isArray(purch.details)) {
            purch.details.forEach(item => {
                let qty = safeParseFloat(item.qty !== undefined ? item.qty : item.quantity);
                let rate = safeParseFloat(item.inclusive_rate);
                let amt = qty * rate;                                  
                totalAmount += amt;
                details.push(`(${rate.toFixed(2)} × ${qty.toFixed(3)} = ${amt.toFixed(2)})`);
            });
            
            let rates = [];
            purch.details.forEach(item => {
                rates.push(safeParseFloat(item.inclusive_rate).toFixed(2));
            });
            $('#pbill_rate').text(rates.join(', '));
        }
        $("#pbill_amount").text(totalAmount.toFixed(2));
        
        let $detailSpan = $("#pbill_amount").closest("td").find(".detail-span");

        if (details.length > 1) {
            $detailSpan
                .removeClass("d-none")        
                .html(details.join("<br>"));
        } else {
            $detailSpan.addClass("d-none")    
                        .empty();              
        }

        $('#pbill_condition').text(purch.pbill_condition || '--');
        $('#pbill_file_no').text(purch.pbill_file_no || '--');

        // --- Analysis Table ---
        const tableBody = $('#parameter_table tbody');
        tableBody.empty();
        $('#paraTotal').empty();
        
        if (data.parameter && data.parameter.length > 0) {
            let rows = '';
            data.parameter.forEach(function(item, index) {  
                
                                                                       
                rows += `
                    <tr data-range="${item.element_range || 0}" data-element-id="${item.element}">
                        <td> <span class="fw-bold p-2" id="element_name_${index}">${item.element_name}</span></td>
                        <td>
                             <span id="guarantee_${index}" class="fw-bold form-control border-0 bg-light text-end">${item.guarantee || '0.00'}</span>
                        </td>
                        <td>
                            <input type="number" step="any" 
                                   id="actual_${index}" 
                                   class="form-control fw-bold calc-actual text-end actual" 
                                   data-index="${index}" 
                                   value="${(item.actual_val !== undefined && item.actual_val !== null) ? safeParseFloat(item.actual_val).toFixed(4) : ''}"
                                   placeholder="0.0000">
                        </td>
                        <td>
                             <span class="form-control fw-bold align-middle border-0 bg-light text-end" id="diff_${index}">${item.diff_val || '0.0000'}</span>
                        </td>
                        <td>
                             <span id="s_rebate_${index}" 
                                   data-rate="${item.s_rebate || 0}" 
                                   data-ranges='${JSON.stringify(item.ranges || [])}'
                                   class="form-control fw-bold align-middle border-0 bg-light text-end">${item.s_rebate_val || '0.00'}</span>
                        </td>
                        <td>
                             <span id="s_premium_${index}" data-rate="${item.s_premium || 0}" class="form-control fw-bold align-middle border-0 bg-light text-end">${item.s_premium_val || '0.00'}</span>
                        </td>
                        <td>
                             <span id="p_rebate_${index}" 
                                   data-rate="${item.p_rebate || 0}" 
                                   data-ranges='${JSON.stringify(item.ranges || [])}'
                                   class="form-control fw-bold align-middle border-0 bg-light text-end">${item.p_rebate_val || '0.00'}</span>
                        </td>
                        <td>
                             <span id="p_premium_${index}" data-rate="${item.p_premium || 0}" class="form-control fw-bold align-middle border-0 bg-light text-end">${item.p_premium_val || '0.00'}</span>
                        </td>
                    </tr>
                `;
            });
            tableBody.html(rows);
            attachTableEvents();
            renderTableFooter();
            // $('.calc-actual').trigger('change'); 
        } else {
            tableBody.append('<tr><td colspan="8" class="text-center text-muted p-4">No parameters found for the selected bill.</td></tr>');
            renderTableFooter();
        }
    }

    function renderTableFooter() {
        const html = `
            <tr>
                <td colspan="4" class="text-end text-primary fw-bold text-uppercase border-0" style="font-size: 0.8rem;">Total Sales Rebate / Premium:</td>
                <td><input class="form-control text-end fw-bold text-danger font-monospace" type="text" readonly="" value="0.00" id="srebate_total" name="srebate_total" style="font-size: 0.85rem;"></td>
                <td><input class="form-control text-end fw-bold text-dark" type="text" readonly="" value="0.00" id="spremium_total" name="spremium_total" style="font-size: 0.85rem;"></td>
                <td><input class="form-control text-end fw-bold text-danger font-monospace" type="text" readonly="" value="0.00" id="prebate_total" name="prebate_total" style="font-size: 0.85rem;"></td>
                <td><input class="form-control text-end fw-bold text-dark" type="text" readonly="" value="0.00" id="ppremium_total" name="ppremium_total" style="font-size: 0.85rem;"></td>
            </tr>
            <tr class="bg-white">
                <td colspan="4" class="border-0"></td>
                <td colspan="2" class="text-end text-primary p-1 fw-bold text-uppercase border-0" style="font-size: 0.8rem;">Purchase Bill Pay Amount:</td>
                <td colspan="1" class="p-0 border-0">
                    <input type="text" class="val-box-large w-100 py-1 text-end"  readonly="" value="0.00" id="pbill_pay_amount" name="pbill_pay_amount" style="font-size: 1.1rem;">
                </td>
            </tr>
        `;
        $('#paraTotal').html(html);
        calculateGrandTotals(); // Refresh totals on footer render
    }
    
    function attachTableEvents() {
    // --- Calculate Difference & Navigation ---
    $('.calc-actual').on('change', function() {
        const index = $(this).data('index');
        const val = $(this).val();

        // If field is empty, show 0.00 and return
        if (val === '') {
            $(`#diff_${index}, #s_rebate_${index}, #s_premium_${index}, #p_rebate_${index}, #p_premium_${index}`).text('0.00');
            $(`#diff_${index}`).text('0.0000');
            $(`#s_rebate_${index}`).data('pct', 0);
            $(`#p_rebate_${index}`).data('pct', 0);
            calculateGrandTotals();
            return;
        }

        // Round to 2 decimal places for calculation (e.g., 3.7567 -> 3.76)
        const actual = Math.round((parseFloat(val) || 0) * 100) / 100;
        const guarantee = parseFloat($(`#guarantee_${index}`).text()) || 0;
        const diff = actual - guarantee;
        
        // Display full precision in UI (4 decimals) without minus sign
        const displayDiff = Math.abs((parseFloat(val) || 0) - guarantee);
        $(`#diff_${index}`).text(displayDiff.toFixed(4));

        if (diff === 0) {
            $(`#s_rebate_${index}, #s_premium_${index}, #p_rebate_${index}, #p_premium_${index}`).text('0.00');
            $(`#s_rebate_${index}`).data('pct', 0);
            $(`#p_rebate_${index}`).data('pct', 0);
            calculateGrandTotals();
            return;
        }

        const getRebateResults = (actualVal, guaranteeVal) => {
            const $s_el = $(`#s_rebate_${index}`);
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

            const results = { s_rebate: 0, s_premium: 0, p_rebate: 0, p_premium: 0 };
            const elementName = ($(`#element_name_${index}`).text() || '').toUpperCase();

            if (elementName.includes('TORN')) {
                results.s_rebate = actualVal;
                results.p_rebate = actualVal;
                return results;
            }
        
            if (elementName.includes('ALBUMIN') && actualVal === 0) {
                results.s_rebate = 0;
                results.p_rebate = 0;
                results.s_premium = 0;
                results.p_premium = 0;
                return results;
            }
            
            const sBillStr = $('#sbill_amount').text().replace(/[^\d.-]/g, '') || '0';
            const pBillStr = $('#pbill_amount').text().replace(/[^\d.-]/g, '') || '0';
            const sBill = parseFloat(sBillStr) || 0;
            const pBill = parseFloat(pBillStr) || 0;

            if (slabs.length > 0) {
                let totalRebatePct = 0;
                let totalPremiumPct = 0;
                let remainingValue = Math.abs(valueToSearch);
                let lastSlab = null;

                // Sort slabs based on range type
                // If High bad (rangeType 2), sort ascending (e.g., 10 to 11, then 11 to 12)
                // If Low bad (rangeType 1), sort descending (e.g., 15 to 13, then 13 to 11)
                if (rangeType === 1) {
                    slabs.sort((a, b) => parseFloat(b.from) - parseFloat(a.from));
                } else {
                    slabs.sort((a, b) => parseFloat(a.from) - parseFloat(b.from));
                }

                for (let slab of slabs) {
                    const rangeSize = Math.abs(parseFloat(slab.to) - parseFloat(slab.from));
                    const s_rate = parseFloat(slab.rebate) || 0;
                    const p_rate = parseFloat(slab.premium) || 0;
                    lastSlab = slab;

                    if (remainingValue <= 0) break;

                    const consumed = Math.min(remainingValue, rangeSize);
                    if (valueToSearch > 0) totalRebatePct += consumed * s_rate;
                    else totalPremiumPct += consumed * p_rate;
                    
                    remainingValue -= consumed;
                }

                // Last Slab Exhaustion Rule
                if (remainingValue > 0 && lastSlab) {
                    const s_rate = parseFloat(lastSlab.rebate) || 0;
                    const p_rate = parseFloat(lastSlab.premium) || 0;
                    if (valueToSearch > 0) totalRebatePct += remainingValue * s_rate;
                    else totalPremiumPct += remainingValue * p_rate;
                }

                results.s_rebate = (totalRebatePct / 100) * sBill;
                results.p_rebate = (totalRebatePct / 100) * pBill;

                // purcahse  preminum as it based on the ranges
                const elementId = parseInt($(`#element_name_${index}`).closest('tr').data('element-id')) || 0;
                let matchedPremium = 0;
                if (elementId !== 5 ) {
                    const match = slabs.find(slab => actualVal >= parseFloat(slab.from) && actualVal <= parseFloat(slab.to));
                    if (match) {
                        matchedPremium = parseFloat(match.premium) || 0;
                    }
                }
                // results.s_premium = matchedPremium;
                results.p_premium = matchedPremium;

                // results.s_rebate_pct = totalRebatePct;
                results.p_rebate_pct = totalRebatePct; // usually same logic applied
                // results.s_premium_pct = 0;
                results.p_premium_pct = 0;
            }
            return results;
        };

        const rebateResults = getRebateResults(actual, guarantee);
        $(`#s_rebate_${index}`).text(rebateResults.s_rebate.toFixed(2)).data('pct', rebateResults.s_rebate_pct || 0);
        $(`#s_premium_${index}`).text(rebateResults.s_premium.toFixed(2));
        $(`#p_rebate_${index}`).text(rebateResults.p_rebate.toFixed(2)).data('pct', rebateResults.p_rebate_pct || 0);
        $(`#p_premium_${index}`).text(rebateResults.p_premium.toFixed(2));
        
        calculateGrandTotals();
    });

    // --- Up and Down Key Navigation ---
    $('.calc-actual').on('keydown', function(e) {
        const index = parseInt($(this).data('index'));
        let targetIndex;

        if (e.which === 38 || e.which === 40 || e.which === 13) {
            e.preventDefault(); // Stop browser from changing values
        }

        if (e.which === 38) { // Up Arrow
            targetIndex = index - 1;
        } else if (e.which === 40 || e.which === 13) { // Down Arrow or Enter
            targetIndex = index + 1;
        }

        if (targetIndex !== undefined) {
            // Format existing value to 4 decimals in UI only
            const currentVal = parseFloat($(this).val()) || 0;
            $(this).val(currentVal.toFixed(4));

            const $nextInput = $(`.calc-actual[data-index="${targetIndex}"]`);
            if ($nextInput.length) {
                e.preventDefault(); 
                $nextInput.focus().select();                 
            }
        }
    });

    // Format to 4 decimals when clicking away (Blur)
    $('.calc-actual').on('blur', function() {
        const val = parseFloat($(this).val());
        if (!isNaN(val)) {
            $(this).val(val.toFixed(4));
        }
    });

    // --- Other calculations ---
    $('.calc-val').on('input', function() {
        calculateGrandTotals();
    });
}

    function calculateGrandTotals() {
        let tsr = 0, tsp = 0, tpr = 0, tpp = 0;

        // Sum values from the dynamic row spans
        $('[id^="s_rebate_"]').each(function() { tsr += parseFloat($(this).text()) || 0; });
        $('[id^="s_premium_"]').each(function() { tsp += parseFloat($(this).text()) || 0; });
        $('[id^="p_rebate_"]').each(function() { tpr += parseFloat($(this).text()) || 0; });
        $('[id^="p_premium_"]').each(function() { tpp += parseFloat($(this).text()) || 0; });

        $('#srebate_total').val(tsr.toFixed(2));
        $('#spremium_total').val(tsp.toFixed(2));
        $('#prebate_total').val(tpr.toFixed(2));
        $('#ppremium_total').val(tpp.toFixed(2));

        // Calculate Final Valuation
        // Using correct selector to match the Blade ID (pbill_amount)
        // const purchNetStr = $('#pbill_amount').text() || '0';
        // const purchNet = parseFloat(purchNetStr.replace(/[^\d.-]/g, '')) || 0;
        
        // const netAdjustment = (tsp + tpp) - (tsr + tpr);
        // const finalValuation = purchNet + netAdjustment;
        
        $('#pbill_pay_amount').val(safeParseFloat(pbill_pay_amount).toFixed(2));
    }

    // --- Form Submission ---
    $('#dairy_analysis_form').on('submit', function(e) {
        e.preventDefault();
        const uuid = $('#uuid').val();
        const $btn = $('#btn_submit_main');
        const invoiceSerial = $('#invoice_serial').val();
        if (!invoiceSerial) {
            showToast('warning', 'Please select a Bill Number');
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
                        sales_rebate: $(`#s_rebate_${index}`).text() || 0,
                        sales_premium: $(`#s_premium_${index}`).text() || 0,
                        purchase_rebate: $(`#p_rebate_${index}`).text() || 0,
                        purchase_premium: $(`#p_premium_${index}`).text() || 0,
                        // sales_rebate_percentage: $(`#s_rebate_${index}`).data('pct') || 0,
                        // sales_premium_percentage: $(`#s_premium_${index}`).data('pct') || 0,
                        // purchase_rebate_percentage: $(`#p_rebate_${index}`).data('pct') || 0,
                        // purchase_premium_percentage: $(`#p_premium_${index}`).data('pct') || 0,
                        guarantee: $(`#guarantee_${index}`).text() || 0
                    });
                }
            });

            // Sum per-row rebate percentages for top-level fields
            let totalSalesRebatePct = 0;
            let totalPurchaseRebatePct = 0;
            items.forEach(item => {
                totalSalesRebatePct += parseFloat(item.sales_rebate_percentage) || 0;
                totalPurchaseRebatePct += parseFloat(item.purchase_rebate_percentage) || 0;
            });

            const formData = {
                uuid: uuid,
                sales_bill_id: sales_bill_id,
                purchase_bill_id: purchase_bill_id,
                invoice_serial: invoiceSerial,
                sales_rebate_total: $('#srebate_total').val() || 0,
                sales_premium_total: $('#spremium_total').val() || 0,
                purchase_rebate_total: $('#prebate_total').val() || 0,
                purchase_premium_total: $('#ppremium_total').val() || 0,
                sales_rebate_percentage: totalSalesRebatePct.toFixed(4),
                purchase_rebate_percentage: totalPurchaseRebatePct.toFixed(4),
                items: items,
                _token: $('meta[name="csrf-token"]').attr('content')
            };
            
            const formAction = $('#dairy_analysis_form').attr('action') || storeAnalysisUrl;
            const isUpdate = $btn.text().trim() === 'Update';
            let method = isUpdate ? 'PUT' : 'POST';

            $btn.html('<i class="fa fa-spinner fa-spin me-1"></i> Processing...').prop('disabled', true);            
            // Laravel Method Spoofing for better compatibility
            if (method === 'PUT') {
                formData._method = 'PUT';
                method = 'POST';
            }

            $.ajax({
                url: formAction,
                method: method,
                data: formData,            
                success: function(response) {            
                    if (response.success) {                    
                        Swal.fire({
                            title: isUpdate ? "Updated!" : "Saved!",
                            text: response.message || (isUpdate ? "Dairy Analysis updated successfully" : "Dairy Analysis saved successfully"),
                            icon: "success",
                            confirmButtonText: "OK",
                            allowEnterKey: true, 
                            allowOutsideClick: false,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $('#dairy_analysis_form').trigger('reset');
                                $('#invoice_serial').trigger('change');
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
                    // Only restore if success didn't already change the text
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
        
        const prebate_total = parseFloat($('#prebate_total').val()) || 0;
        
        if (prebate_total <= 0) {
            let text = "Rebate is - " + prebate_total.toFixed(2) + ". Are you sure you want to pass this report?";

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

    function clearFormData() {
        // --- Clear Sales Section ---
        $('#sbill_customer, #sbill_customer_city, #sbill_product, #sbill_condition, #sbill_date, #sbill_grn_no, #sbill_vehical_no').text('--');
        $('#sbill_qty, #sbill_rate, #sbill_amount').text('0.00');
        $('[name="sbill_amount"]').val('0.00');

        // --- Clear Purchase Section ---
        $('#reference_number, #pbill_supp, #pbill_supp_city, #pbill_pro, #pbill_date, #pbill_grn_no, #pbill_vehicle_no, #pbill_destination, #pbill_condition, #pbill_file_no').text('--');
        $('#pbill_qty, #pbill_rate, #pbill_amount').text('0.00');
        $('.detail-span').addClass('d-none').empty();

        // --- Clear Analysis Table & Footer ---
        $('#parameterTable').empty();
        $('#paraTotal').empty();
        $('#srebate_total, #spremium_total, #prebate_total, #ppremium_total').val('0.00');
        pbill_pay_amount = 0;
        $('#pbill_pay_amount').val('0.00');
    }
                    

    // --- Print Action ---
    $("#print_btn").on("click", function (e) {
        e.preventDefault();

        const invoiceSerial = $('#invoice_serial').val();
        if (!invoiceSerial) {
            showToast('warning', 'Please Enter a Bill Number');            
            return;
        }      
        let printId = $(this).val();
        
        const url = printAnalysisUrl.replace(':id', printId);
        window.open(url, '_blank');
    });

    // --- Email Action ---
    $("#email_btn").on("click", function (e) {
        e.preventDefault();

        const dairyAnalysisId = $('#print_btn').val();
        
        if (!dairyAnalysisId) {
            Swal.fire({
                icon: "warning",
                title: "Warning",
                text: "Please select a saved record first",
                confirmButtonText: "OK",
            });
            return;
        }

        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled', true);

        $.ajax({
            url: emailPreviewUrl,
            method: 'POST',
            data: {
                dairy_analysis_ids: [dairyAnalysisId],
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (!response.success) {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: response.message || 'Error fetching data',
                        confirmButtonText: "OK",
                    });
                } else {
                    const data = response.data;
                    $('#pa_email_to').val(data.to_email || '');
                    $('#pa_email_subject').val(data.subject || '');
                    
                    // CC — rebuild options from accounts list
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
                    
                    $('#paEmailModal').modal('show');
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: xhr.responseJSON?.message || 'Something went wrong',
                    confirmButtonText: "OK",
                });
            },
            complete: function() {
                $btn.html(originalHtml).prop('disabled', false);
            }
        });
    });

    // --- Send Email Action ---
    $("#pa_email_send_btn").on("click", function(e) {
        e.preventDefault();

        const dairyAnalysisId = $('#print_btn').val();
        if (!dairyAnalysisId) {
            Swal.fire({ icon: "warning", title: "Warning", text: "No record selected.", confirmButtonText: "OK" });
            return;
        }

        const toEmail = $('#pa_email_to').val();
        const ccEmails = $('#pa_email_cc').val() || [];
        const subject = $('#pa_email_subject').val();
        
        let bodyHtml = '';
        if (window.paEditorReady && hugerte.get('pa_email_message')) {
            bodyHtml = hugerte.get('pa_email_message').getContent();
        } else {
            bodyHtml = $('#pa_email_message').val();
        }

        if (!toEmail) {
            Swal.fire({ icon: "warning", title: "Warning", text: "Recipient email is required.", confirmButtonText: "OK" });
            return;
        }

        if (!subject) {
            Swal.fire({ icon: "warning", title: "Warning", text: "Subject is required.", confirmButtonText: "OK" });
            return;
        }

        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin me-1"></i> Sending...').prop('disabled', true);

        $.ajax({
            url: emailSendUrl,
            method: 'POST',
            data: {
                dairy_analysis_ids: [dairyAnalysisId],
                to_email: toEmail,
                cc_emails: ccEmails,
                subject: subject,
                body: bodyHtml,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#paEmailModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Sent!',
                        text: response.message || 'Email sent successfully.',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to send email.',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message || 'Something went wrong',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                $btn.html(originalHtml).prop('disabled', false);
            }
        });
    });

});

// HugeRTE for single-party compose modal
var paHugerteInitialized      = false;
window.paEditorReady          = false;
window.paEditorPendingContent = null;

function paSetEditorContent(html) {
  if (window.paEditorReady) {
    var ed = hugerte.get('pa_email_message');
    if (ed) ed.setContent(html);
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
    base_url    : typeof hugertePath !== 'undefined' ? hugertePath : '',
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



// function bindSelect2() {
//     const selectIdArray = ['#invoice_serial'];
//     selectIdArray.forEach(element => {
//         $(element).select2({
//             theme: "bootstrap-5",
//             allowClear: true,
//             placeholder: "--Select Bill No--",
//             width: null,            
//         });
//     });

//     $(document).on("select2:open", function (e) {
//         const selectElement = $(e.target);
//         const searchInput = selectElement
//             .data("select2")
//             .$dropdown.find(".select2-search__field");

//         searchInput
//             .off("keydown.select2Enter")
//             .on("keydown.select2Enter", (event) => {
//                 if (event.which === 13) {
//                     event.preventDefault();
//                     selectElement.select2("close");
//                     moveFocusToNextField(selectElement);
//                 }
//             });
//     });
// }
