// SALES INVOICE – BILL SUNDRY
// Adapted from purchase-invoice/bill-sundry.js for sale_ fields

var BillSundry = (function ($) {
    'use strict';

    // -------------------------------------------------------
    // STATE
    // -------------------------------------------------------
    var state = {
        allSundries:     [],   // master list (sorted by sales_preload_order)
        particularMeta:  {},   // { [rowId]: cloned sundry object + selected_dr/cr }
        activeRow:       null,
        ADDITIVE:        'additive',
        SUBTRACTIVE:     'subtractive',
        gstType:         null,
        saleTypeDetail:  null,
    };

    // -------------------------------------------------------
    // GETTERS / SETTERS
    // -------------------------------------------------------
    function getMeta(row) { return state.particularMeta[row] || null; }

    function setMeta(row, meta) { state.particularMeta[row] = $.extend(true, {}, meta); }

    function deleteMeta(row) { delete state.particularMeta[row]; }

    function setGstType(type)          { state.gstType = type; }
    function setSaleTypeDetail(detail) { state.saleTypeDetail = detail; }

    // -------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------

    /**
     * Apply upper-level (ceiling) rounding when bill_sundry_amount_round_off is true.
     * Otherwise return the plain numeric value unchanged.
     */
    function applyRoundOff(amount, meta) {
        if (meta && meta.bill_sundry_amount_round_off) {
            return Math.round(amount);
        }
        return amount;
    }

    function notAdjustInSalesAmount(meta) {
        if (!meta) return false;
        return (meta.sale_adjust_in_amount === false || meta.sale_adjust_in_amount === 0)
            && meta.sale_account_type === 'specify_account_in_voucher';
    }

    function notAdjustInPartyAmount(meta) {
        if (!meta) return false;
        return (meta.sale_adjust_in_party_amount === false || meta.sale_adjust_in_party_amount === 0)
            && meta.sale_party_account_type === 'specify_account_in_voucher';
    }

    function getBaseAmount() {
        return Number($('#base_total_amount').data('amount')) || 0;
    }

    // -------------------------------------------------------
    // ROW TEMPLATE
    // -------------------------------------------------------
    function deleteIconHTML(row) {
        return '<span class="erp-btn-icon delete delete-particular-row" data-row="' + row + '">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"'
            + ' fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"'
            + ' stroke-linejoin="round" class="icon icon-tabler-trash">'
            + '<path stroke="none" d="M0 0h24v24H0z" fill="none"/>'
            + '<path d="M4 7l16 0"/>'
            + '<path d="M10 11l0 6"/>'
            + '<path d="M14 11l0 6"/>'
            + '<path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>'
            + '<path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>'
            + '</svg></span>';
    }

    function rowTemplate(row, nonSelectable, isDeletable) {
        var delIcon = (isDeletable !== false) ? deleteIconHTML(row) : '';
        var nsClass = nonSelectable ? 'non-selectable' : '';
        return '<tr id="particular_row_' + row + '" data-row-id="' + row + '">'
            + '<td class="bg-white text-center">' + delIcon + '</td>'
            + '<td>'
            +   '<select class="form-select select2-small particular_id ' + nsClass + '"'
            +   ' id="particular_id_' + row + '" data-row="' + row + '"></select>'
            + '</td>'
            + '<td>'
            +   '<input type="number" step="any" class="form-control text-center particular_percentage"'
            +   ' id="particular_percentage_' + row + '" data-row="' + row + '" value="">'
            + '</td>'
            + '<td>'
            +   '<input type="text" class="form-control text-end particular_value"'
            +   ' id="particular_value_' + row + '" data-row="' + row + '" value="">'
            + '</td>'
            + '</tr>';
    }

    // -------------------------------------------------------
    // SELECT2 INIT FOR A ROW
    // -------------------------------------------------------
    function initSelect(row) {
        var options = state.allSundries.map(function (s) {
            return { id: s.id, text: s.name };
        });

        var $el = $('#particular_id_' + row);
        if ($el.hasClass('select2-hidden-accessible')) { $el.select2('destroy'); }

        $el.select2({
            placeholder: 'Select Particular',
            theme: 'bootstrap-5',
            width: '100%',
            data: [{ id: '', text: '' }].concat(options),
            allowClear: false,
            dropdownParent: $('#particular_table'),
        }).val(null).trigger('change');
    }

    // -------------------------------------------------------
    // RENDER PRELOADED ROWS (sorted by sales_preload_order)
    // -------------------------------------------------------
    function renderRows() {
        var preloaded = state.allSundries.filter(function (s) {
            return !!s.preload_in_sales;
        });

        var $tbody = $('#particular_table_body');
        $tbody.empty();
        state.particularMeta = {};

        if (!preloaded.length) return;

        var rowIndex = 1;
        preloaded.forEach(function (p) {
            setMeta(rowIndex, p);

            var html = rowTemplate(rowIndex, true, false);
            $tbody.append(html);
            initSelect(rowIndex);

            var $select = $('#particular_id_' + rowIndex);
            $select.val(p.id).trigger('change.select2');

            applyMetaToRow(rowIndex, p);
            rowIndex++;
        });
    }

    // -------------------------------------------------------
    // APPLY META CONFIG TO A ROW
    // -------------------------------------------------------
    function applyMetaToRow(row, meta) {
        var $pct = $('#particular_percentage_' + row);
        var $val = $('#particular_value_' + row);

        if (!meta || !meta.id) {
            $pct.prop('disabled', true).val('');
            return;
        }

        if (meta.calculation_type === 'percentage') {
            var isGst = (meta.bill_sundry_nature === 'gst');
            $pct.prop('disabled', isGst);

            if (!isGst && meta.default_value) {
                $pct.val(parseFloat(meta.default_value).toFixed(DECIMALS.PERCENTAGE));
            } else {
                $pct.val('');
            }
        } else {
            $pct.prop('disabled', true).val('');
            if (meta.default_value) {
                var fixedAmt = applyRoundOff(parseFloat(meta.default_value), meta);
                $val.val(fixedAmt.toFixed(DECIMALS.AMOUNT));
            }
        }
    }

    // -------------------------------------------------------
    // CALCULATE BILL SUNDRIES
    // -------------------------------------------------------
    function calculate() {
        var baseAmount = getBaseAmount();
        var netTotal   = baseAmount;
        var grossTotal = baseAmount;
        var hasGrossItems = false;

        $('#particular_table_body tr').each(function () {
            var row  = $(this).data('row-id');
            var meta = getMeta(row);
            if (!meta || !meta.id) return;

            var $tr    = $(this);
            var $valEl = $tr.find('.particular_value');

            // Auto-calc GST rows when sale type detail is set
            if (state.saleTypeDetail && state.gstType) {
                var ptd = state.saleTypeDetail;

                if (state.gstType === GST_TYPE.LOCAL) {
                    if (meta.code === '1002') {
                        var cgstAmt = applyRoundOff((baseAmount * (ptd.cgst || 0)) / 100, meta);
                        $valEl.val(cgstAmt.toFixed(DECIMALS.AMOUNT));
                    }
                    if (meta.code === '1003') {
                        var sgstAmt = applyRoundOff((baseAmount * (ptd.sgst || 0)) / 100, meta);
                        $valEl.val(sgstAmt.toFixed(DECIMALS.AMOUNT));
                    }
                }

                if (state.gstType === GST_TYPE.INTERSTATE) {
                    if (meta.code === '1004') {
                        var igstAmt = applyRoundOff((baseAmount * (ptd.igst || 0)) / 100, meta);
                        $valEl.val(igstAmt.toFixed(DECIMALS.AMOUNT));
                    }
                }
            }

            var value = Number($valEl.val()) || 0;

            var noAdjAmt   = (meta.sale_adjust_in_amount === false || meta.sale_adjust_in_amount === 0);
            var noAdjParty = (meta.sale_adjust_in_party_amount === false || meta.sale_adjust_in_party_amount === 0);

            if (noAdjAmt && noAdjParty) {
                hasGrossItems = true;
            } else {
                if (meta.bill_sundry_type === state.ADDITIVE)    netTotal += value;
                if (meta.bill_sundry_type === state.SUBTRACTIVE) netTotal -= value;
            }

            if (meta.bill_sundry_type === state.ADDITIVE)    grossTotal += value;
            if (meta.bill_sundry_type === state.SUBTRACTIVE) grossTotal -= value;
        });

        $('#gross_total_row').toggleClass('d-none', !hasGrossItems);
        $('#gross_total').val(formatIndianNumber(grossTotal.toFixed(DECIMALS.AMOUNT)));

        var netVal = netTotal.toFixed(DECIMALS.AMOUNT);
        $('#net_total')
            .data('value', netVal)
            .attr('data-value', netVal)
            .val(formatIndianNumber(netVal));
    }

    function itemGstChanged(ptd) {
        var cgst = ptd.cgst || 0;
        var sgst = ptd.sgst || 0;
        var igst = ptd.igst || 0;
        var totalGst = parseFloat(cgst) + parseFloat(sgst) + parseFloat(igst);
        var gstMultiplier = 1 + totalGst / 100;

        $('.item-row').each(function () {
            var row = $(this);
            row.find('.cgst_rate').val(cgst);
            row.find('.sgst_rate').val(sgst);
            row.find('.igst_rate').val(igst);

            var rate          = parseFloat(row.find('.rate').val()) || 0;
            var inclusiveRate = parseFloat(row.find('.inclusive_rate').val()) || 0;

            if (rate) {
                row.find('.inclusive_rate').val((rate * gstMultiplier).toFixed(DECIMALS.AMOUNT));
            } else if (inclusiveRate) {
                row.find('.rate').val((inclusiveRate / gstMultiplier).toFixed(DECIMALS.AMOUNT));
            }
        });
    }

    // -------------------------------------------------------
    // FILL GST PERCENTAGES AFTER GST TYPE / SALE TYPE CHANGES
    // -------------------------------------------------------
    function fillGstPercentages() {
        if (!state.saleTypeDetail || !state.gstType) return;

        var ptd = state.saleTypeDetail;
        itemGstChanged(ptd);

        $('#particular_table_body tr').each(function () {
            var row  = $(this).data('row-id');
            var meta = getMeta(row);
            if (!meta) return;

            var $pct = $('#particular_percentage_' + row);
            var $val = $('#particular_value_' + row);

            if (state.gstType === GST_TYPE.LOCAL) {
                if (meta.code === '1002')      $pct.val((parseFloat(ptd.cgst) || 0).toFixed(DECIMALS.PERCENTAGE));
                else if (meta.code === '1003') $pct.val((parseFloat(ptd.sgst) || 0).toFixed(DECIMALS.PERCENTAGE));
                else if (meta.code === '1004') { $pct.val('0.00'); $val.val(''); }
            } else if (state.gstType === GST_TYPE.INTERSTATE) {
                if (meta.code === '1004')      $pct.val((parseFloat(ptd.igst) || 0).toFixed(DECIMALS.PERCENTAGE));
                else if (meta.code === '1002' || meta.code === '1003') { $pct.val('0.00'); $val.val(''); }
            }
        });
    }

    // -------------------------------------------------------
    // HANDLERS
    // -------------------------------------------------------
    function onParticularChange(el) {
        var row = parseInt($(el).data('row'));
        var id  = el.value;

        if (!id) { setMeta(row, {}); return; }

        var meta = state.allSundries.find(function (s) { return String(s.id) === String(id); });
        if (!meta) return;

        setMeta(row, meta);
        applyMetaToRow(row, meta);

        if (meta.code === '1002' || meta.code === '1003' || meta.code === '1004') {
            fillGstPercentages();
        }

        calculate();
    }
    
    function onPercentageBlur(el) {
        var $el     = $(el);
        var row     = parseInt($el.data('row'));
        var meta    = getMeta(row);
        var pct     = parseFloat($el.val());

        if (!pct) return;

        var base = getBaseAmount();
        var runningValue = base;
        if (meta && meta.apply_on === 'running_total') {
            $('#particular_table_body .particular_value').each(function () {
                var r = parseInt($(this).data('row'));
                if (r >= row) return;
                var rowMeta = getMeta(r);
                var val = parseFloat($(this).val()) || 0;
                if (rowMeta && rowMeta.bill_sundry_type === state.SUBTRACTIVE) {
                    runningValue -= val;
                } else {
                    runningValue += val;
                }
            });
        }

        var amount = applyRoundOff((runningValue * pct) / 100, meta);
        $('#particular_value_' + row).val(amount > 0 ? amount.toFixed(DECIMALS.AMOUNT) : '');
        calculate();
    }

    function onValueInput(el) {
        var val   = el.value.replace(/[^0-9.]/g, '');
        var parts = val.split('.');
        el.value  = parts.length > 2 ? parts[0] + '.' + parts[1] : val;
    }

    function onValueBlur(el) {
        var val = Math.abs(parseFloat(el.value));
        el.value = val ? val.toFixed(DECIMALS.AMOUNT) : '';
        calculate();

        var formatted = el.value;
        if (!formatted || formatted === '0.00') return;

        var modalEl = document.getElementById('particular_modal');
        if (modalEl && modalEl.classList.contains('show')) return;

        var row  = $(el).closest('tr').data('row-id');
        var meta = getMeta(row);
        if (!meta) return;

        var isDr = notAdjustInSalesAmount(meta);
        var isCr = notAdjustInPartyAmount(meta);

        if (isDr || isCr) { openModal(isDr, isCr, row, meta); }
    }

    function onValueEnter(el) { $(el).trigger('blur'); }

    function onDelete(btn) {
        var row      = parseInt($(btn).data('row'));
        var metaKeys = Object.keys(state.particularMeta);

        if (metaKeys.length <= 1) {
            showToast('info', 'At least one particular is required');
            return;
        }

        deleteMeta(row);
        $('#particular_row_' + row).remove();
        calculate();
    }

    function onAddRow() {
        var $tbody  = $('#particular_table_body');
        var lastRow = Number($tbody.find('tr:last').data('row-id')) || 0;
        var row     = lastRow + 1;

        setMeta(row, {});
        $tbody.append(rowTemplate(row, false, true));

        setTimeout(function () {
            initSelect(row);
            $('#particular_id_' + row).trigger('focus');
        }, 20);
    }

    function onAddCustomParticulars() {
        var total = 5;
        $('#particular_table_body').empty();
        state.particularMeta = {};

        var html = '';
        for (var i = 1; i <= total; i++) {
            setMeta(i, {});
            html += rowTemplate(i, false, true);
        }
        $('#particular_table_body').append(html);

        setTimeout(function () {
            $('.particular_id').each(function () {
                initSelect(parseInt($(this).data('row')));
            });
        }, 20);

        setTimeout(function () { $('#particular_id_1').trigger('focus'); }, 30);
    }

    // -------------------------------------------------------
    // MODAL (DR / CR ACCOUNT SELECTION)
    // -------------------------------------------------------
    function openModal(isDr, isCr, row, meta) {
        if (typeof showLoader === 'function') showLoader('Loading... ');

        setTimeout(function() {
            var $modal = $('#particular_modal');
            if (!$modal.length) {
                if (typeof hideLoader === 'function') hideLoader();
                return;
            }

            $modal.find('#particular_div_dr, #particular_div_cr').addClass('d-none');
            if (isDr) $modal.find('#particular_div_dr').removeClass('d-none');
            if (isCr) $modal.find('#particular_div_cr').removeClass('d-none');

            var dynamicHeight = (isDr && isCr) ? 600 : 400;
            $modal.find('.modal-content').css({ height: dynamicHeight + 'px' });

        $modal.data('row-id', row);
        state.activeRow = row;

        var $drSelect = $('#particular_dr_id');
        var $crSelect = $('#particular_cr_id');

        if ($drSelect.hasClass('select2-hidden-accessible')) $drSelect.select2('destroy');
        if ($crSelect.hasClass('select2-hidden-accessible')) $crSelect.select2('destroy');

        var ledgerOptions = Array.isArray(allLedgers)
            ? allLedgers.map(function (l) { return { id: l.id, text: l.name }; })
            : Object.entries(allLedgers).map(function (e) { return { id: e[0], text: e[1] }; });

        $drSelect.select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Select Account…', dropdownParent: $modal, data: ledgerOptions });
        $crSelect.select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Select Account…', dropdownParent: $modal, data: ledgerOptions });

        if (meta.selected_dr_id) {
            $drSelect.append(new Option(meta.selected_dr_text, meta.selected_dr_id, true, true)).trigger('change');
        } else {
            $drSelect.val(null).trigger('change');
        }

        if (meta.selected_cr_id) {
            $crSelect.append(new Option(meta.selected_cr_text, meta.selected_cr_id, true, true)).trigger('change');
        } else {
            $crSelect.val(null).trigger('change');
        }

        $('#particular_modal').modal('show');

        if (typeof hideLoader === 'function') hideLoader();

        $('#particular_modal').one('shown.bs.modal', function () {
            if (isDr) $drSelect.trigger('focus');
            else if (isCr) $crSelect.trigger('focus');
        });
        }, 50); // Small delay to allow the loader to render
    }

    function saveModal() {
        var row  = parseInt($('#particular_modal').data('row-id'));
        var meta = getMeta(row);

        if (!meta) { showToast('error', 'Data not saved properly'); return; }

        var dr = $('#particular_dr_id').val();
        var cr = $('#particular_cr_id').val();

        if (dr && cr && dr === cr) {
            showToast('info', 'Dr and Cr accounts cannot be the same');
            $('#particular_dr_id').trigger('focus');
            return;
        }

        var drData = $('#particular_dr_id').select2('data') || [];
        var crData = $('#particular_cr_id').select2('data') || [];

        if (notAdjustInSalesAmount(meta) && drData.length === 0) {
            showToast('error', 'Dr Account is required');
            return;
        }
        if (notAdjustInPartyAmount(meta) && crData.length === 0) {
            showToast('error', 'Cr Account is required');
            return;
        }

        meta.selected_dr_id   = dr   || null;
        meta.selected_dr_text = drData[0] ? drData[0].text : '';
        meta.selected_cr_id   = cr   || null;
        meta.selected_cr_text = crData[0] ? crData[0].text : '';

        setMeta(row, meta);

        var activeRow = state.activeRow;
        state.activeRow = null;
        $('#particular_modal').modal('hide');

        setTimeout(function () {
            var totalRows = $('#particular_table_body tr').length;
            if (!activeRow || (activeRow + 1) > totalRows) {
                $('#save_btn').trigger('focus');
            } else {
                var $next = $('#particular_value_' + (activeRow + 1));
                if ($next.length) $next.trigger('focus');
                else $('#save_btn').trigger('focus');
            }
        }, 50);
    }

    // -------------------------------------------------------
    // COLLECT DATA FOR FORM SUBMISSION
    // -------------------------------------------------------
    function collect() {
        var particulars   = [];
        var seenIds       = {};
        var duplicateName = null;
        var rowIndex      = 0;

        $('#particular_table_body tr').each(function () {
            var row       = $(this).data('row-id');
            var $idSelect = $('#particular_id_' + row);

            var id         = $idSelect.val();
            var name       = $idSelect.find('option:selected').text();
            var percentage = $('#particular_percentage_' + row).val();
            var value      = $('#particular_value_' + row).val();
            var meta       = getMeta(row) || {};

            if (!id && !value) return;

            if (id && seenIds[id]) { duplicateName = name; return false; }
            if (id) seenIds[id] = true;

            rowIndex++;
            particulars.push({
                bill_sundry_id:          id          || null,
                bill_sundry_percentage:  percentage  || null,
                bill_sundry_value:       value       || 0,
                bill_sundry_modal_dr_id: meta.selected_dr_id || null,
                bill_sundry_modal_cr_id: meta.selected_cr_id || null,
                sort_order:              rowIndex,
            });
        });

        if (duplicateName) {
            return { error: 'Duplicate Particular selected: ' + duplicateName };
        }

        return particulars;
    }

    // -------------------------------------------------------
    // EVENT BINDING
    // -------------------------------------------------------
    function bindEvents() {
        $(document).on('change', '.particular_id', function () { onParticularChange(this); });
        $(document).on('blur', '.particular_percentage', function () { onPercentageBlur(this); });
        $(document).on('input', '.particular_value', function () { onValueInput(this); });
        $(document).on('blur', '.particular_value', function () { onValueBlur(this); });
        $(document).on('keydown', '.particular_value', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); onValueEnter(this); }
        });
        $(document).on('click', '.delete-particular-row', function () { onDelete(this); });
        $(document).on('click', '#add_particular_row', function () { onAddRow(); });
        $(document).on('click', '#add_custom_particulars', function () { onAddCustomParticulars(); });
        $(document).on('click', '#save_particular_btn', function () { saveModal(); });
        $(document).on('click', '#reload_bill_sundry_table', function () {
            renderRows();
            calculate();
        });

        // -------------------------------------------------------
        // ROW HIGHLIGHT ON FOCUS
        // -------------------------------------------------------

        // Inject CSS once — overrides bg-white !important on first td
        if (!document.getElementById('bill-sundry-highlight-style')) {
            var styleEl = document.createElement('style');
            styleEl.id  = 'bill-sundry-highlight-style';
            styleEl.textContent = '#particular_table_body tr.table-active td { background-color: #dbeafe !important; }';
            document.head.appendChild(styleEl);
        }

        function setActiveRow($tr) {
            $('#particular_table_body tr').removeClass('table-active');
            $tr.addClass('table-active');
        }

        function clearRowIfUnfocused($tr) {
            setTimeout(function () {
                var stillFocused = $tr.find('input:focus').length > 0
                                || $tr.find('.select2-container--open').length > 0;
                if (!stillFocused) {
                    $tr.removeClass('table-active');
                }
            }, 100);
        }

        // Input focus
        $('#particular_table').on('focusin', 'input', function () {
            setActiveRow($(this).closest('tr'));
        });
        $('#particular_table').on('focusout', 'input', function () {
            clearRowIfUnfocused($(this).closest('tr'));
        });

        // Select2 focus
        $(document).on('select2:open', '.particular_id', function () {
            setActiveRow($(this).closest('tr'));
        });
        $(document).on('select2:close', '.particular_id', function () {
            clearRowIfUnfocused($(this).closest('tr'));
        });

        // Arrow-key grid navigation
        $('#particular_table').on('keydown', 'input', function (e) {
            var key = e.key;
            if (key !== 'ArrowUp' && key !== 'ArrowDown' && key !== 'ArrowLeft' && key !== 'ArrowRight') return;
            e.preventDefault();

            var $input   = $(this);
            var $td      = $input.closest('td');
            var $tr      = $input.closest('tr');
            var colIndex = $td.index();

            function findInput($startTd, dir) {
                var $ptr = $startTd;
                while ($ptr.length) {
                    var $found = $ptr.find('input:not([disabled])').first();
                    if ($found.length) return $found;
                    $ptr = (dir === 'next') ? $ptr.nextAll('td').first() : $ptr.prevAll('td').first();
                }
                return null;
            }

            switch (key) {
                case 'ArrowRight': { var $next = findInput($td.nextAll('td').first(), 'next'); if ($next) $next.trigger('focus').trigger('select'); break; }
                case 'ArrowLeft':  { var $prev = findInput($td.prevAll('td').first(), 'prev'); if ($prev) $prev.trigger('focus').trigger('select'); break; }
                case 'ArrowDown':  {
                    var $nextRow = $tr.next('tr');
                    while ($nextRow.length) {
                        var $downInput = $nextRow.find('td').eq(colIndex).find('input:not([disabled])');
                        if ($downInput.length) { $downInput.trigger('focus').trigger('select'); return; }
                        $nextRow = $nextRow.next('tr');
                    }
                    break;
                }
                case 'ArrowUp': {
                    var $prevRow = $tr.prev('tr');
                    while ($prevRow.length) {
                        var $upInput = $prevRow.find('td').eq(colIndex).find('input:not([disabled])');
                        if ($upInput.length) { $upInput.trigger('focus').trigger('select'); return; }
                        $prevRow = $prevRow.prev('tr');
                    }
                    break;
                }
            }
        });
    }

    // -------------------------------------------------------
    // INIT
    // -------------------------------------------------------
    function init() {
        if (typeof billSundryData === 'undefined' || !Array.isArray(billSundryData)) return;

        // Sort by sales_preload_order (falls back to 0 if field absent)
        state.allSundries = billSundryData.slice().sort(function (a, b) {
            return (a.sales_preload_order || 0) - (b.sales_preload_order || 0);
        });

        renderRows();
        bindEvents();
        calculate();
    }

    // -------------------------------------------------------
    // LOAD SAVED BILL SUNDRIES (edit mode)
    // -------------------------------------------------------
    function loadSaved(savedSundries) {
        var $tbody = $('#particular_table_body');

        console.log("savedSundries", savedSundries);
        

        $tbody.find('.particular_id').each(function () {
            if ($(this).hasClass('select2-hidden-accessible')) $(this).select2('destroy');
        });

        $tbody.empty();
        state.particularMeta = {};

        if (!savedSundries || !savedSundries.length) { renderRows(); return; }

        var rowIndex = 1;
        savedSundries.forEach(function (saved) {
            var masterMeta = state.allSundries.find(function (s) {
                return String(s.id) === String(saved.sundry_id);
            });

            var meta = $.extend(true, {}, masterMeta || { id: saved.sundry_id, name: saved.name || '' });

            if (saved.bill_sundry_modal_dr_id) {
                meta.selected_dr_id   = saved.bill_sundry_modal_dr_id;
                meta.selected_dr_text = saved.dr_account ? saved.dr_account.name : '';
            }
            if (saved.bill_sundry_modal_cr_id) {
                meta.selected_cr_id   = saved.bill_sundry_modal_cr_id;
                meta.selected_cr_text = saved.cr_account ? saved.cr_account.name : '';
            }

            setMeta(rowIndex, meta);

            var isPreloaded = !!masterMeta && !!masterMeta.preload_in_sales;
            $tbody.append(rowTemplate(rowIndex, isPreloaded, !isPreloaded));

            initSelect(rowIndex);
            $('#particular_id_' + rowIndex).val(meta.id).trigger('change.select2');

            var $pct = $('#particular_percentage_' + rowIndex);
            var $val = $('#particular_value_' + rowIndex);

            if (saved.rate_percent !== null && saved.rate_percent !== undefined && saved.rate_percent !== '') {
                $pct.val(parseFloat(saved.rate_percent).toFixed(DECIMALS.PERCENTAGE));
            }
            if (saved.value !== null && saved.value !== undefined && saved.value !== '') {
                $val.val(parseFloat(saved.value).toFixed(DECIMALS.AMOUNT));
            }

            rowIndex++;
        });

        calculate();
    }

    // -------------------------------------------------------
    // PATCH SAVED DR/CR INTO ROW META (edit mode)
    // -------------------------------------------------------
    function setRowDrCr(row, drId, drText, crId, crText) {
        var meta = getMeta(row);
        if (!meta) return;
        if (drId) { meta.selected_dr_id = drId; meta.selected_dr_text = drText || ''; }
        if (crId) { meta.selected_cr_id = crId; meta.selected_cr_text = crText || ''; }
        setMeta(row, meta);
    }

    // -------------------------------------------------------
    // PUBLIC API
    // -------------------------------------------------------
    return {
        init:               init,
        calculate:          calculate,
        collect:            collect,
        setGstType:         setGstType,
        setSaleTypeDetail:  setSaleTypeDetail,
        fillGstPercentages: fillGstPercentages,
        loadSaved:          loadSaved,
        setRowDrCr:         setRowDrCr,
    };

})(jQuery);
