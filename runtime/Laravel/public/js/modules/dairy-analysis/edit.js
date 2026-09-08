$(document).ready(function () {

    /* =========================================================
     * State — bill IDs set after analysis is loaded
     * ========================================================= */
    let salesBillId    = null;
    let purchaseBillId = null;
    let pBillPayAmount = 0;
    let currentAnalysisId = null;


    /* =========================================================
     * Utility
     * ========================================================= */

    function safeFloat(val, fallback = 0) {
        const n = parseFloat(val);
        return isNaN(n) ? fallback : n;
    }

    function spanAmount(selector) {
        return safeFloat($(selector).text().replace(/[^\d.-]/g, ''));
    }

    function alertError(message) {
        return Swal.fire({
            icon: 'error', title: 'Error', text: message,
            confirmButtonText: 'OK', allowEnterKey: true, allowOutsideClick: false,
        });
    }

    function alertWarning(message, title = 'Warning') {
        return Swal.fire({
            icon: 'warning', title, text: message,
            confirmButtonText: 'OK', allowEnterKey: true, allowOutsideClick: false,
        });
    }


    /* =========================================================
     * Analysis Select2 Dropdown
     * ========================================================= */

    $('#analysis_select').select2({
        theme:       'bootstrap-5',
        placeholder: '-- Select Bill --',
        allowClear:  true,
        width:       '100%',
    });

    $('#analysis_select').on('change', function () {
        const id = $(this).val();
        if (!id) {
            clearFormData();
            return;
        }
        loadAnalysisById(parseInt(id));
    });

    // Auto-load when arriving via ?id=xxx (e.g. from the duplicate Swal link on create page)
    const urlId = new URLSearchParams(window.location.search).get('id');
    if (urlId && $('#analysis_select option[value="' + urlId + '"]').length) {
        $('#analysis_select').val(urlId).trigger('change');
    }


    /* =========================================================
     * Load Analysis by ID
     * ========================================================= */

    function loadAnalysisById(id) {
        showLoader('Loading analysis data...');

        $.ajax({
            url:    getAnalysisByIdUrl,
            method: 'GET',
            data:   { id: id },
            success: function (response) {
             
                if (response.success) {
                    populateFormData(response);
                    $('.actual-input').first().focus();
                } else {
                    alertWarning(response.message || 'Could not load analysis data.').then(() => {
                        clearFormData();
                        $('#analysis_select').val(null).trigger('change');
                    });
                }
            },
            error: function (xhr) {
                alertError(xhr.responseJSON?.message || 'An unexpected error occurred while fetching data.');
            },
            complete: function () {
                hideLoader();
            },
        });
    }


    /* =========================================================
     * Populate Form
     * ========================================================= */

    function populateFormData(data) {
        const analysisId = data.general ? data.general.id : null;
        currentAnalysisId = analysisId;

        const $form      = $('#dairy_analysis_form');
        const $printBtn  = $('#btn_print');
        const $emailBtn  = $('#btn_email');

        // Always update mode on edit page
        $form.attr('action', updateAnalysisUrl.replace(':id', analysisId)).data('mode', 'update');
        if (analysisId) {
            $printBtn.removeClass('d-none').attr('data-record-id', analysisId);
            $emailBtn.removeClass('d-none');
        }

        // --- Sales section ---
        const s     = data.sales_data;
        salesBillId = s.s_id;

        $('#s_customer_name').text(s.s_customer          || '--');
        $('#s_customer_city').text(s.s_customer_city     || '--');
        $('#s_bill_date').text(formatDateToDMY(s.s_date) || '--');
        $('#s_grn_no').text(s.s_grn_no                   || '--');
        $('#s_vehicle_no').text(s.s_vehicle_no           || '--');
        $('#s_gross_qty').text(safeFloat(s.s_qty).toFixed(3));
        $('#s_inclusive_rate').text(safeFloat(s.s_inclusive_rate).toFixed(2));
        $('#s_bill_amount').text(
            (safeFloat(s.s_qty) * safeFloat(s.s_inclusive_rate)).toFixed(2)
        );

        // --- Purchase section ---
        const p        = data.purchase_data;
        purchaseBillId = p.p_id;
        pBillPayAmount = safeFloat(p.p_pay_amount);

        const invSerial = p.p_inv_serial || '--';
        const refText   = p.p_ref_no || '--';

        $('#p_reference_no').text(`${invSerial} / ${refText}`);
        $('#p_supplier_name').text(p.p_supp              || '--');
        $('#p_supplier_city').text(p.p_supp_city         || '--');
        $('#p_product_name').text(p.p_product            || '--');
        $('#p_bill_date').text(formatDateToDMY(p.p_date) || '--');
        $('#p_grn_no').text(p.p_grn_no                   || '--');
        $('#p_vehicle_no').text(p.p_vehicle_no           || '--');
        $('#p_destination').text(p.p_destination         || '--');
        $('#p_condition').text(p.p_condition             || '--');
        $('#p_file_no').text(p.p_file_no                 || '00');

        let totalQty       = 0;
        let purchaseTotalAmt = 0;
        const rates        = [];
        const poLineHtml   = [];

        if (Array.isArray(p.p_details)) {
            p.p_details.forEach(function (item, i) {
                const qty  = safeFloat(item.qty);
                const rate = safeFloat(item.inclusive_rate);
                const amt  = item.net_amount ? safeFloat(item.net_amount) : qty * rate;
                totalQty         += qty;
                purchaseTotalAmt += amt;
                rates.push(rate.toFixed(2));
                poLineHtml.push(
                    `<div class="d-flex align-items-center gap-3 py-1 border-bottom" style="border-color:#bbf7d0 !important">` +
                        `<span class="badge bg-success text-white fw-bold" style="min-width:52px">PO ${i + 1}</span>` +
                        `<span class="text-muted">${qty.toFixed(3)} &times; ${rate.toFixed(2)}</span>` +
                        `<span class="ms-auto fw-bold text-success-emphasis">= ${amt.toFixed(2)}</span>` +
                    `</div>`
                );
            });
        }

        $('#p_gross_qty').text(totalQty.toFixed(3));
        $('#p_inclusive_rate').text(rates.join(' / '));
        $('#p_net_amount').text(purchaseTotalAmt.toFixed(2));

        const $poLines = $('#p_po_lines');
        if (poLineHtml.length > 1) {
            $poLines.removeClass('d-none').html(poLineHtml.join(''));
        } else {
            $poLines.addClass('d-none').empty();
        }

        buildParameterTable(data.parameter);
    }


    /* =========================================================
     * Parameter Table — build, footer, events
     * ========================================================= */

    function buildParameterTable(parameters) {
        $('#parameter_table_body').empty();
        $('#parameter_table_foot').empty();

        if (!parameters || parameters.length === 0) {
            $('#parameter_table_body').html(
                '<tr><td colspan="9" class="text-center text-muted p-4">No parameters found for the selected bill.</td></tr>'
            );
            renderTableFooter();
            return;
        }

        let rows = '';
        parameters.forEach(function (item, index) {
            const actualVal = (item.actual_val !== undefined && item.actual_val !== null)
                ? safeFloat(item.actual_val).toFixed(4)
                : '';

            rows += `
                <tr data-range="${item.element_range || 0}" data-element-id="${item.element}">
                    <td>
                        <span class="fw-bold p-2" id="el_name_${index}">${item.element_name}</span>
                    </td>
                    <td>
                        <span id="el_guarantee_${index}"
                              class="fw-bold form-control border-0 bg-light text-end">${item.guarantee || '0.00'}</span>
                    </td>
                    <td>
                        <input type="number" step="any"
                               id="el_actual_${index}"
                               class="form-control fw-bold text-end actual-input"
                               data-index="${index}"
                               value="${actualVal}"
                               placeholder="0.0000">
                    </td>
                    <td>
                        <span id="el_diff_${index}"
                              class="form-control fw-bold border-0 bg-light text-end">${item.diff_val || '0.0000'}</span>
                    </td>
                    <td>
                        <span id="el_rebate_pct_${index}"
                              class="form-control fw-bold border-0 bg-light text-end">${item.rebate_pct_val || '0.00'}</span>
                    </td>
                    <td>
                        <span id="el_s_rebate_${index}"
                              data-rate="${item.s_rebate || 0}"
                              data-ranges='${JSON.stringify(item.ranges || [])}'
                              class="form-control fw-bold border-0 bg-light text-end">${item.s_rebate_val || '0.00'}</span>
                    </td>
                    <td>
                        <span id="el_s_premium_${index}"
                              data-rate="${item.s_premium || 0}"
                              class="form-control fw-bold border-0 bg-light text-end">${item.s_premium_val || '0.00'}</span>
                    </td>
                    <td>
                        <span id="el_p_rebate_${index}"
                              data-rate="${item.p_rebate || 0}"
                              data-ranges='${JSON.stringify(item.ranges || [])}'
                              class="form-control fw-bold border-0 bg-light text-end">${item.p_rebate_val || '0.00'}</span>
                    </td>
                    <td>
                        <span id="el_p_premium_${index}"
                              data-rate="${item.p_premium || 0}"
                              class="form-control fw-bold border-0 bg-light text-end">${item.p_premium_val || '0.00'}</span>
                    </td>
                </tr>
            `;
        });

        $('#parameter_table_body').html(rows);
        attachTableEvents();
        renderTableFooter();
    }

    function renderTableFooter() {
        const html = `
            <tr>
                <td colspan="5" class="text-end text-primary fw-bold text-uppercase border-0"
                    style="font-size: 0.8rem;">Total Sales Rebate / Premium:</td>
                <td>
                    <input type="text" id="total_s_rebate" name="srebate_total"
                           class="form-control text-end fw-bold text-danger font-monospace"
                           readonly value="0.00" style="font-size: 0.85rem;">
                </td>
                <td>
                    <input type="text" id="total_s_premium" name="spremium_total"
                           class="form-control text-end fw-bold text-dark"
                           readonly value="0.00" style="font-size: 0.85rem;">
                </td>
                <td>
                    <input type="text" id="total_p_rebate" name="prebate_total"
                           class="form-control text-end fw-bold text-danger font-monospace"
                           readonly value="0.00" style="font-size: 0.85rem;">
                </td>
                <td>
                    <input type="text" id="total_p_premium" name="ppremium_total"
                           class="form-control text-end fw-bold text-dark"
                           readonly value="0.00" style="font-size: 0.85rem;">
                </td>
            </tr>
            <tr class="bg-white">
                <td colspan="5" class="border-0"></td>
                <td colspan="2" class="text-end text-primary p-1 fw-bold text-uppercase border-0"
                    style="font-size: 0.8rem;">Purchase Bill Pay Amount:</td>
                <td colspan="1" class="p-0 border-0">
                    <input type="text" id="pbill_pay_amount" name="pbill_pay_amount"
                           class="val-box-large w-100 py-1 text-end"
                           readonly value="0.00" style="font-size: 1.1rem;">
                </td>
            </tr>
        `;
        $('#parameter_table_foot').html(html);
        calculateGrandTotals();
    }


    /* =========================================================
     * Table Events — actual input calculations
     * ========================================================= */

    function attachTableEvents() {

        $('.actual-input').on('change', function () {
            const index  = $(this).data('index');
            const rawVal = $(this).val();

            if (rawVal === '') {
                $(`#el_diff_${index}`).text('0.0000');
                $(`#el_rebate_pct_${index}`).text('0.00');
                $(`#el_s_rebate_${index}, #el_s_premium_${index}, #el_p_rebate_${index}, #el_p_premium_${index}`).text('0.00');
                calculateGrandTotals();
                return;
            }

            const actual    = Math.round(safeFloat(rawVal) * 100) / 100;
            const guarantee = safeFloat($(`#el_guarantee_${index}`).text());
            const diff      = actual - guarantee;

            $(`#el_diff_${index}`).text(Math.abs(safeFloat(rawVal) - guarantee).toFixed(4));

            if (diff === 0) {
                $(`#el_rebate_pct_${index}`).text('0.00');
                $(`#el_s_rebate_${index}, #el_s_premium_${index}, #el_p_rebate_${index}, #el_p_premium_${index}`).text('0.00');
                calculateGrandTotals();
                return;
            }

            const res = computeRebatePremium(index, actual, guarantee);
            $(`#el_rebate_pct_${index}`).text(res.rebate_pct.toFixed(4));
            $(`#el_s_rebate_${index}`).text(res.s_rebate.toFixed(2));
            $(`#el_s_premium_${index}`).text(res.s_premium.toFixed(2));
            $(`#el_p_rebate_${index}`).text(res.p_rebate.toFixed(2));
            $(`#el_p_premium_${index}`).text(res.p_premium.toFixed(2));

            calculateGrandTotals();
        });

        $('.actual-input').on('keydown', function (e) {
            const index = parseInt($(this).data('index'));

            if ([13, 38, 40].includes(e.which)) e.preventDefault();

            let next;
            if (e.which === 38)                        next = index - 1;
            else if (e.which === 40 || e.which === 13) next = index + 1;

            if (next !== undefined) {
                const v = safeFloat($(this).val());
                if ($(this).val() !== '') $(this).val(v.toFixed(4));

                const $nextInput = $(`.actual-input[data-index="${next}"]`);
                if ($nextInput.length) $nextInput.focus().select();
            }
        });

        $('.actual-input').on('blur', function () {
            if ($(this).val() !== '') $(this).val(safeFloat($(this).val()).toFixed(4));
        });
    }

    function computeRebatePremium(index, actual, guarantee) {
        const $row = $(`#el_name_${index}`).closest('tr');
        let slabs  = $(`#el_s_rebate_${index}`).data('ranges');
        try { if (typeof slabs === 'string') slabs = JSON.parse(slabs); } catch (e) { slabs = []; }
        slabs = Array.isArray(slabs) ? slabs : [];

        const res         = { s_rebate: 0, s_premium: 0, p_rebate: 0, p_premium: 0, rebate_pct: 0 };
        const elementName = ($(`#el_name_${index}`).text() || '').toUpperCase();

        if (elementName.includes('TORN')) {
            res.s_rebate = actual;
            res.p_rebate = actual;
            return res;
        }

        if (elementName.includes('ALBUMIN') && actual === 0) return res;

        if (slabs.length === 0) return res;

        const rowRange  = parseInt($row.data('range')) || 0;
        const rangeType = slabs[0].element_range ? parseInt(slabs[0].element_range) : rowRange;

        const inBadDirection = (rangeType === 2) ? actual >= guarantee : actual <= guarantee;
        if (!inBadDirection || actual === guarantee) return res;

        const deviation = Math.abs(actual - guarantee);
        const sBill     = spanAmount('#s_bill_amount');
        const pBill     = spanAmount('#p_net_amount');

        if (rangeType === 1)      slabs.sort((a, b) => safeFloat(b.from) - safeFloat(a.from));
        else if (rangeType === 2) slabs.sort((a, b) => safeFloat(a.from) - safeFloat(b.from));

        const blockWidths  = [];
        const blockRebates = [];
        let matchedPremium = 0;
        let matched        = false;

        for (const slab of slabs) {
            const lo    = Math.min(safeFloat(slab.from), safeFloat(slab.to));
            const hi    = Math.max(safeFloat(slab.from), safeFloat(slab.to));
            const width = hi - lo;

            blockWidths.push(width);
            blockRebates.push(width * safeFloat(slab.rebate));
            matchedPremium = safeFloat(slab.premium);  // track last slab's premium as fallback

            if (actual >= lo && actual <= hi) {
                matched = true;
                break;
            }
        }

        if (blockWidths.length === 0) return res;

        const widthsRev   = [...blockWidths].reverse();
        const rebatesRev  = [...blockRebates].reverse();
        const totalWidth  = blockWidths.reduce((s, v) => s + v, 0);
        const totalRebate = blockRebates.reduce((s, v) => s + v, 0);

        // Overflow beyond last slab: all blocks fully consumed → rebate capped at cumulative total
        let rebatePct;
        if (!matched) {
            rebatePct = totalRebate;
        } else {
            const unused = parseFloat((totalWidth - deviation).toFixed(2));
            rebatePct    = totalRebate - (rebatesRev[0] * unused) / widthsRev[0];
        }

        res.rebate_pct = rebatePct;
        res.s_rebate   = (rebatePct / 100) * sBill;
        res.p_rebate   = (rebatePct / 100) * pBill;
        res.p_premium  = matchedPremium;

        return res;
    }


    /* =========================================================
     * Grand Totals
     * ========================================================= */

    function calculateGrandTotals() {
        let tsr = 0, tsp = 0, tpr = 0, tpp = 0;

        $('[id^="el_s_rebate_"]').each(function ()  { tsr += safeFloat($(this).text()); });
        $('[id^="el_s_premium_"]').each(function () { tsp += safeFloat($(this).text()); });
        $('[id^="el_p_rebate_"]').each(function ()  { tpr += safeFloat($(this).text()); });
        $('[id^="el_p_premium_"]').each(function () { tpp += safeFloat($(this).text()); });

        $('#total_s_rebate').val(tsr.toFixed(2));
        $('#total_s_premium').val(tsp.toFixed(2));
        $('#total_p_rebate').val(tpr.toFixed(2));
        $('#total_p_premium').val(tpp.toFixed(2));

        $('#pbill_pay_amount').val(pBillPayAmount.toFixed(2));
    }


    /* =========================================================
     * Form Submission (always PUT / update)
     * ========================================================= */

    $('#dairy_analysis_form').on('submit', function (e) {
        e.preventDefault();

        if (!currentAnalysisId) {
            showToast('warning', 'Please select an analysis record to update');
            return;
        }

        const prebateTotal = safeFloat($('#total_p_rebate').val());

        if (prebateTotal <= 0) {
            Swal.fire({
                title: 'Warning!',
                text: `Purchase rebate is ${prebateTotal.toFixed(2)}. Are you sure you want to update?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Update',
                cancelButtonText: 'Cancel',
                allowEnterKey: true,
                allowOutsideClick: false,
            }).then(result => { if (result.isConfirmed) submitForm(); });
        } else {
            submitForm();
        }
    });

    function submitForm() {
        const $form = $('#dairy_analysis_form');
        const $btn  = $('#btn_submit');

        const items = [];
        $('#parameter_table_body tr').each(function () {
            const elementId = $(this).data('element-id');
            if (!elementId) return;
            const index = $(this).find('.actual-input').data('index');
            items.push({
                element_id:       elementId,
                actual:            $(`#el_actual_${index}`).val()         || 0,
                diff:              $(`#el_diff_${index}`).text()          || 0,
                rebate_percentage: $(`#el_rebate_pct_${index}`).text()    || 0,
                sales_rebate:      $(`#el_s_rebate_${index}`).text()      || 0,
                sales_premium:    $(`#el_s_premium_${index}`).text()  || 0,
                purchase_rebate:  $(`#el_p_rebate_${index}`).text()   || 0,
                purchase_premium: $(`#el_p_premium_${index}`).text()  || 0,
                guarantee:        $(`#el_guarantee_${index}`).text()  || 0,
            });
        });

        const formData = {
            uuid:                   $('#uuid').val(),
            sales_bill_id:          salesBillId,
            purchase_bill_id:       purchaseBillId,
            invoice_serial:         $('#analysis_select option:selected').text().trim(),
            sales_rebate_total:     $('#total_s_rebate').val()  || 0,
            sales_premium_total:    $('#total_s_premium').val() || 0,
            purchase_rebate_total:  $('#total_p_rebate').val()  || 0,
            purchase_premium_total: $('#total_p_premium').val() || 0,
            items:                  items,
            _token:                 $('meta[name="csrf-token"]').attr('content'),
            _method:                'PUT',
        };

        $btn.html('<i class="fa fa-spinner fa-spin me-1"></i> Updating...').prop('disabled', true);
        showLoader('Updating...');

        $.ajax({
            url:    $form.attr('action'),
            method: 'POST',
            data:   formData,
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Updated!',
                        text:  response.message || 'Analysis updated successfully.',
                        icon:  'success',
                        confirmButtonText: 'OK',
                        allowEnterKey: true, allowOutsideClick: false,
                    }).then(() => window.location.reload());
                } else {
                    showToast('error', response.message || 'Failed to update. Please try again.');
                }
            },
            error: function (xhr) {
                alertError(xhr.responseJSON?.message || 'An unexpected error occurred.');
            },
            complete: function () {
                hideLoader();
                $btn.html('<i class="fa-solid fa-pencil me-1"></i> Update').prop('disabled', false);
            },
        });
    }


    /* =========================================================
     * Clear Form
     * ========================================================= */

    $('#btn_clear').on('click', function () {
        clearFormData();
        $('#analysis_select').val(null).trigger('change');
    });

    function clearFormData() {
        $('#s_customer_name, #s_customer_city, #s_bill_date, #s_grn_no, #s_vehicle_no').text('--');
        $('#s_gross_qty').text('0.000');
        $('#s_inclusive_rate, #s_bill_amount').text('0.00');

        $('#p_reference_no, #p_supplier_name, #p_supplier_city, #p_product_name').text('--');
        $('#p_bill_date, #p_grn_no, #p_vehicle_no, #p_destination, #p_condition').text('--');
        $('#p_file_no').text('00');
        $('#p_gross_qty').text('0.000');
        $('#p_inclusive_rate, #p_net_amount').text('0.00');
        $('#p_po_lines').addClass('d-none').empty();

        $('#parameter_table_body').empty();
        $('#parameter_table_foot').empty();

        $('#btn_print').addClass('d-none').removeAttr('data-record-id');
        $('#btn_email').addClass('d-none');

        salesBillId       = null;
        purchaseBillId    = null;
        pBillPayAmount    = 0;
        currentAnalysisId = null;

        $('#dairy_analysis_form').removeAttr('action').data('mode', 'update');
    }


    /* =========================================================
     * Print
     * ========================================================= */

    $('#btn_print').on('click', function (e) {
        e.preventDefault();
        const recordId = $(this).attr('data-record-id');
        if (!recordId) { showToast('warning', 'No saved record found to print'); return; }
        window.open(printAnalysisUrl.replace(':id', recordId), '_blank');
    });


    /* =========================================================
     * Email — open compose modal
     * ========================================================= */

    $('#btn_email').on('click', function (e) {
        e.preventDefault();

        const recordId = $('#btn_print').attr('data-record-id');
        if (!recordId) { alertWarning('Please select a saved record first.'); return; }

        const $btn = $(this);
        $btn.html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled', true);

        // Open modal first so the loader inside the message field is visible
        $('#pa_email_to').val('');
        $('#pa_email_subject').val('');
        $('#pa_email_cc').empty().trigger('change');
        if (window.paEditorReady && typeof hugerte !== 'undefined' && hugerte.get('pa_email_message')) {
            hugerte.get('pa_email_message').setContent('');
        }
        $('#pa_editor_loader').css('display', 'flex');
        $('#paEmailModal').modal('show');

        $.ajax({
            url:    emailPreviewUrl,
            method: 'POST',
            data:   { dairy_analysis_ids: [recordId], _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                if (!response.success) {
                    $('#pa_editor_loader').hide();
                    $('#paEmailModal').modal('hide');
                    alertError(response.message || 'Failed to load email preview.');
                    return;
                }

                const data = response.data;
                $('#pa_email_to').val(data.to_email || '');
                $('#pa_email_subject').val(data.subject || '');

                const $cc = $('#pa_email_cc');
                $cc.empty();
                (data.cc_accounts || []).forEach(function (acc) {
                    $cc.append(new Option(`${acc.name} <${acc.email}>`, acc.email, false, false));
                });
                $cc.val(null).trigger('change');

                if (typeof paSetEditorContent === 'function') {
                    paSetEditorContent(data.body || '');
                }
            },
            error: function (xhr) {
                $('#pa_editor_loader').hide();
                $('#paEmailModal').modal('hide');
                alertError(xhr.responseJSON?.message || 'Something went wrong loading the preview.');
            },
            complete: function () {
                $btn.html('<i class="fa-solid fa-envelope me-1"></i> Send Mail').prop('disabled', false);
            },
        });
    });


    /* =========================================================
     * Email — send
     * ========================================================= */

    $('#pa_email_send_btn').on('click', function (e) {
        e.preventDefault();

        const recordId = $('#btn_print').attr('data-record-id');
        if (!recordId) { alertWarning('No record selected.'); return; }

        const toEmail = $('#pa_email_to').val().trim();
        const subject = $('#pa_email_subject').val().trim();
        if (!toEmail) { alertWarning('Recipient email is required.'); return; }
        if (!subject) { alertWarning('Subject is required.');         return; }

        const ccEmails = $('#pa_email_cc').val() || [];
        const bodyHtml = (window.paEditorReady && hugerte.get('pa_email_message'))
            ? hugerte.get('pa_email_message').getContent()
            : $('#pa_email_message').val();

        const $btn = $(this);
        $btn.html('<i class="fa fa-spinner fa-spin me-1"></i> Sending...').prop('disabled', true);
        showLoader('Sending email...');

        $.ajax({
            url:    emailSendUrl,
            method: 'POST',
            data:   {
                dairy_analysis_ids: [recordId],
                to_email:  toEmail,
                cc_emails: ccEmails,
                subject:   subject,
                body:      bodyHtml,
                _token:    $('meta[name="csrf-token"]').attr('content'),
            },
            success: function (response) {
                if (response.success) {
                    $('#paEmailModal').modal('hide');
                    Swal.fire({ icon: 'success', title: 'Sent!', text: response.message || 'Email sent successfully.', confirmButtonText: 'OK' });
                } else {
                    alertError(response.message || 'Failed to send email.');
                }
            },
            error: function (xhr) {
                alertError(xhr.responseJSON?.message || 'Something went wrong while sending.');
            },
            complete: function () {
                $btn.html('<i class="fa-solid fa-paper-plane me-1"></i> Send').prop('disabled', false);
                hideLoader();
            },
        });
    });

}); // end document.ready


/* =========================================================
 * HugerTE — lazy-initialized on first modal open
 * ========================================================= */
var paHugerteInitialized      = false;
window.paEditorReady          = false;
window.paEditorPendingContent = null;

function paSetEditorContent(html) {
    if (window.paEditorReady) {
        const ed = hugerte.get('pa_email_message');
        if (ed) ed.setContent(html);
        $('#pa_editor_loader').hide();
    } else {
        window.paEditorPendingContent = html;
    }
}

$('#paEmailModal').on('shown.bs.modal', function () {
    if (paHugerteInitialized) return;
    paHugerteInitialized = true;

    hugerte.init({
        selector:      '#pa_email_message',
        height:        320,
        menubar:       false,
        base_url:      typeof hugertePath !== 'undefined' ? hugertePath : '',
        suffix:        '.min',
        plugins:       'lists link code',
        toolbar:       'bold italic underline | bullist numlist | alignleft aligncenter alignright | link | code',
        content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
        setup: function (editor) {
            editor.on('init', function () {
                window.paEditorReady = true;
                if (window.paEditorPendingContent !== null) {
                    editor.setContent(window.paEditorPendingContent);
                    window.paEditorPendingContent = null;
                    $('#pa_editor_loader').hide();
                }
            });
        },
    });
});


/* =========================================================
 * Select2 — CC field in email compose modal
 * ========================================================= */
(function initEmailCcSelect2() {
    $('#pa_email_cc').select2({
        dropdownParent:  $('#paEmailModal'),
        theme:           'bootstrap-5',
        placeholder:     'Add CC recipients',
        allowClear:      true,
        tags:            true,
        tokenSeparators: [','],
        createTag: function (params) {
            const term = $.trim(params.term);
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(term)) return null;
            return { id: term, text: term, newTag: true };
        },
    });
})();
