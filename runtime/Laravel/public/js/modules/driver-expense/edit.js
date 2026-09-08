/* Driver Expense – edit.js
 * Reuses all interaction logic from create.js.
 * After master data loads, pre-fills the form from window.editExpense.
 * Submits a PUT request to updateDriverExpenseUrl.
 */

/* global DateInput */

// ── Original expense total (used to reverse double-counting in remaining balance) ─
var _originalExpenseTotal = 0;

// ── Dairy import cache ────────────────────────────────────────────────────────
var _dairyCache = {};

// ── Picker state ──────────────────────────────────────────────────────────────
var _pickerTarget        = null;
var _pickerUrl           = null;   // AJAX search URL for this picker type
var _pickerSelected      = false;
var _pickerShouldAdvance = false;
var _pickerInitialSearch = '';

// ── Picker master-data preload (in-memory search, no per-keystroke AJAX) ──────
var _pickerType         = null;    // 'expenseAccount' | 'destination' | 'product'
var _pickerMasterData   = { expenseAccount: null, destination: null, product: null };
var _pickerDataRequests = { expenseAccount: null, destination: null, product: null };

var ROW_FIELD_ORDER = [
    '.row-date', '.row-expense-account', '.row-from', '.row-to',
    '.row-dc-lr', '.row-product', '.row-rate', '.row-bags',
    '.row-weight', '.row-trips', '.row-amount', '.row-remark'
];

$(function () {

    bindSelect2();
    preloadPickerMasterData();

    // ── Fetch expense data only — master data loaded on demand via AJAX picker ──
    var fetchExpenseData = $.ajax({ url: getExpenseDataUrl, dataType: 'json' });

    setLoaderStep('Loading…', 'Loading expense data…', 50);

    fetchExpenseData
        .then(function (expRes) {
            window.editExpense = expRes;
            setLoaderStep('✓', 'Filling form…', 100);
            prefillForm();
            $('#master_loader').fadeOut(300);
            if (!isLocked) $('#voucher_date').trigger('focus');
        })
        .fail(function () {
            $('#master_loader_text').html(
                '<span class="text-danger"><i class="fa-solid fa-circle-exclamation me-1"></i>' +
                'Failed to load data. Please refresh the page.</span>'
            );
            $('#master_loader_progress').hide();
        });

    function setLoaderStep(step, text, pct) {
        $('#master_loader_step').text(step);
        $('#master_loader_text').text(text);
        $('#master_loader_bar').css('width', pct + '%').attr('aria-valuenow', pct);
    }

    // ── Date pickers ──────────────────────────────────────────────────────────
    new DateInput('#voucher_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('.row-date',     FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    // ── Date blur → auto-fill from dairy import data ──────────────────────────
    $('#driver_expense_table_body').on('blur', '.row-date', function () {
        var dateVal   = $(this).val().trim();
        var vehicleId = $('#vehicle_id').val();
        if (!isValidDateDMY(dateVal) || !vehicleId) return;

        var $startRow = $(this).closest('tr');
        var cacheKey  = vehicleId + '_' + dateVal;

        function fillRows(entry) {
            var unused = entry.items.filter(function (item) {
                return entry.usedIds.indexOf(item.id) === -1;
            });
            if (!unused.length) return;
            unused.forEach(function (item, idx) {
                var $row = idx === 0 ? $startRow : $startRow.nextAll('tr').eq(idx - 1);
                if (!$row.length) return;
                entry.usedIds.push(item.id);
                $row.attr('data-dairy-item-id', item.id);
                if (idx > 0) $row.find('.row-date').val(dateVal);
                if (item.expense_account_id && item.expense_account_name)
                    $row.find('.row-expense-account').val(item.expense_account_name).attr('data-picked-id', String(item.expense_account_id));
                if (item.to_id && item.to_name)
                    $row.find('.row-to').val(item.to_name).attr('data-picked-id', String(item.to_id));
                if (item.from_id && item.from_name)
                    $row.find('.row-from').val(item.from_name).attr('data-picked-id', String(item.from_id));
                if (item.product_id && item.product_name)
                    $row.find('.row-product').val(item.product_name).attr('data-picked-id', String(item.product_id));
                if (item.bags)
                    $row.find('.row-bags').val(item.bags);
                if (item.rate)     $row.find('.row-rate').val(item.rate);

                // Auto-calculate amount = rate × weight after import fill
                var _rate = parseFloat($row.find('.row-rate').val()) || 0;
                var _bags = parseFloat($row.find('.row-bags').val()) || 0;
                if (_rate > 0 && _bags > 0) {
                    $row.find('.row-amount').val((_rate * _bags).toFixed(2));
                }

                var dcLr = item.dc_number || item.lr_number;
                if (dcLr) $row.find('.row-dc-lr').val(dcLr);
            });
            recalcTableTotals();   // update #total_bags / #total_weight / #total_amount
            recalcExpenseTotal();  // update #expense_total using the refreshed #total_amount
        }

        if (_dairyCache[cacheKey]) { fillRows(_dairyCache[cacheKey]); return; }

        var $dateTd = $startRow.find('.row-date').closest('td');
        $dateTd.css('position', 'relative').append(
            '<div class="date-fetch-loader" style="position:absolute;inset:0;background:rgba(255,255,255,0.82);display:flex;align-items:center;justify-content:center;z-index:5;">' +
            '<div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>'
        );
        $.ajax({
            url: getDairyImportDataUrl, method: 'GET', data: { date: dateVal, vehicle_id: vehicleId },
            success: function (res) {
                _dairyCache[cacheKey] = { items: res.found ? res.items : [], usedIds: [] };
                fillRows(_dairyCache[cacheKey]);
            },
            complete: function () { $dateTd.find('.date-fetch-loader').remove(); }
        });
    });

    // ── Date blur → enforce ascending order ───────────────────────────────────
    $('#driver_expense_table_body').on('blur', '.row-date', function () {
        var dateVal = $(this).val().trim();
        var $thisRow = $(this).closest('tr');
        var $prevRow = $thisRow.prev('tr');
        if (!$prevRow.length) return;
        if (!isValidDateDMY(dateVal)) return;
        var prevDateVal = $prevRow.find('.row-date').val().trim();
        if (!prevDateVal || !isValidDateDMY(prevDateVal)) return;
        if (parseDMY(dateVal) < parseDMY(prevDateVal)) {
            $(this).val('');
            showToast('error', 'Date must not be earlier than previous row date (' + prevDateVal + ').');
        }
    });

    // ── Enter key in table ────────────────────────────────────────────────────
    $('#driver_expense_table_body').on('keydown', 'input', function (e) {
        if (e.which !== 13) return;
        e.preventDefault(); e.stopPropagation();
        rowAdvance($(this));
    });

    // ── Arrow-key navigation ──────────────────────────────────────────────────
    $('#driver_expense_table_body').on('keydown', 'input', function (e) {
        var key = e.key;
        var el  = this;
        var goLeft  = key === 'ArrowLeft'  && el.selectionStart === 0 && el.selectionEnd === 0;
        var goRight = key === 'ArrowRight' && el.selectionStart === el.value.length && el.selectionEnd === el.value.length;
        var goUp    = key === 'ArrowUp';
        var goDown  = key === 'ArrowDown';
        if (!goLeft && !goRight && !goUp && !goDown) return;
        e.preventDefault(); e.stopPropagation();
        var $el = $(this), $row = $el.closest('tr'), curIdx = -1;
        for (var i = 0; i < ROW_FIELD_ORDER.length; i++) {
            if ($el.is(ROW_FIELD_ORDER[i])) { curIdx = i; break; }
        }
        if (curIdx === -1) return;
        if (goRight) {
            for (var j = curIdx + 1; j < ROW_FIELD_ORDER.length; j++) {
                var $n = $row.find(ROW_FIELD_ORDER[j]);
                if ($n.length && !$n.prop('disabled')) { arrowFocus($n); return; }
            }
        } else if (goLeft) {
            for (var j = curIdx - 1; j >= 0; j--) {
                var $n = $row.find(ROW_FIELD_ORDER[j]);
                if ($n.length && !$n.prop('disabled')) { arrowFocus($n); return; }
            }
        } else if (goDown) {
            var $nextRow = $row.next('tr');
            if ($nextRow.length) { var $n = $nextRow.find(ROW_FIELD_ORDER[curIdx]); if ($n.length) arrowFocus($n); }
        } else if (goUp) {
            var $prevRow = $row.prev('tr');
            if ($prevRow.length) { var $n = $prevRow.find(ROW_FIELD_ORDER[curIdx]); if ($n.length) arrowFocus($n); }
        }
    });

    // ── Picker: Backspace / Delete ────────────────────────────────────────────
    $('#driver_expense_table_body').on('keydown', '.row-picker', function (e) {
        if (e.key !== 'Backspace' && e.key !== 'Delete') return;
        e.preventDefault();
        $(this).val('').removeAttr('data-picked-id');
    });

    // ── Picker trigger: printable key ─────────────────────────────────────────
    $('#driver_expense_table_body').on('keydown', '.row-picker', function (e) {
        if (e.which === 9 || e.which === 27) return;
        if (e.which === 13) return;
        if (e.ctrlKey || e.altKey || e.metaKey) return;
        if (!e.key || e.key.length !== 1) return;
        e.preventDefault(); e.stopPropagation();
        if (_pickerTarget !== null) { _pickerInitialSearch += e.key; }
        else { openPicker($(this), e.key); }
    });

    // ── Picker trigger: click ─────────────────────────────────────────────────
    $('#driver_expense_table_body').on('click', '.row-picker', function () {
        openPicker($(this), '');
    });

    // ── Picker modal events ───────────────────────────────────────────────────
    $('#pickerModal').on('show.bs.modal', function () {
        $('#picker_loader').show();
        $('#pickerModal .select2-container').remove();
    });

    $('#pickerModal').on('shown.bs.modal', function () {
        var $sel = $('#picker_select'), $loader = $('#picker_loader');
        if ($sel.data('select2')) { $sel.select2('destroy'); }
        $sel.empty().append('<option value=""></option>');
        $sel.select2({
            theme:              'bootstrap-5',
            width:              '100%',
            dropdownParent:     $('#pickerModal'),
            closeOnSelect:      true,
            placeholder:        'Type to search…',
            allowClear:         false,
            minimumInputLength: 0,
            // ── ROLLBACK: previous server-side AJAX search (per-keystroke backend call).
            // If the in-memory search below causes issues, delete the block after this
            // comment and uncomment this one to restore the old behavior.
            // ajax: {
            //     url:      _pickerUrl,
            //     dataType: 'json',
            //     delay:    300,
            //     data: function (params) { return { q: params.term || '' }; },
            //     processResults: function (res) {
            //         return {
            //             results: (res.data || []).map(function (d) {
            //                 return { id: d.id, text: d.name };
            //             })
            //         };
            //     },
            //     complete: function () { setTimeout(highlightFirstPickerResult, 30); },
            //     cache: true,
            // },
            ajax: {
                transport: function (params, success) {
                    var term    = (params.data.term || '').toLowerCase();
                    var request = _pickerDataRequests[_pickerType];

                    if (!request) { success({ results: [] }); return; }

                    request.done(function () {
                        var dataset = _pickerMasterData[_pickerType] || [];
                        var results = term
                            ? dataset.filter(function (d) { return d.text.toLowerCase().indexOf(term) !== -1; })
                            : dataset;
                        success({ results: results });
                        setTimeout(highlightFirstPickerResult, 0);
                    });
                },
                cache: true,
            },
            templateSelection: function (d) { return d.id ? d.text : ''; },
        }).val('').trigger('change.select2');
        $loader.hide();
        $sel.select2('open');
        setTimeout(function () {
            var $searchBox = $('#pickerModal .select2-search__field');
            $searchBox.trigger('focus');
            if (_pickerInitialSearch) { $searchBox.val(_pickerInitialSearch).trigger('input'); }
            var el = $searchBox[0];
            if (el) {
                el.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        e.stopImmediatePropagation();
                        _pickerShouldAdvance = false; _pickerSelected = false;
                        $('#pickerModal').modal('hide'); return;
                    }
                    if (e.key !== 'Enter') return;
                    var highlighted = !!$('#pickerModal .select2-results__option--highlighted').length;
                    if (highlighted) return;
                    if (this.value === '') {
                        e.stopImmediatePropagation();
                        _pickerShouldAdvance = true;
                        $('#pickerModal').modal('hide');
                    } else {
                        e.stopImmediatePropagation();
                        $('#pickerModal .select2-results__option[aria-selected]:not(.select2-results__option--disabled)').first().trigger('mouseup');
                    }
                }, true);
            }
        }, 0);
    });

    $('#picker_select').on('select2:select', function () {
        var data = $(this).select2('data');
        if (!data || !data.length || !data[0].id) return;
        _pickerSelected = true; _pickerShouldAdvance = true;
        if (_pickerTarget) {
            _pickerTarget.val(data[0].text).attr('data-picked-id', String(data[0].id));
        }
        $('#pickerModal').modal('hide');
    });

    $('#pickerModal').on('hidden.bs.modal', function () {
        var $target = _pickerTarget, shouldAdvance = _pickerShouldAdvance;
        if ($('#picker_select').data('select2')) { $('#picker_select').select2('destroy'); }
        _pickerTarget = null; _pickerSelected = false; _pickerShouldAdvance = false; _pickerInitialSearch = '';
        if (shouldAdvance && $target) { rowAdvance($target); }
        else if ($target) { $target.focus(); }
    });

    // ── Vehicle change → clear dairy cache ───────────────────────────────────
    $('#vehicle_id').on('change', function () {
        _dairyCache = {};
        var vehicleText = $(this).find('option:selected').text().trim();
        var currentNarration = $('#narration').val().trim();
        if (vehicleText) {
            var prefix = 'Vehicle no : ';
            if (currentNarration === '' || currentNarration.startsWith(prefix)) {
                $('#narration').val(prefix + vehicleText);
            }
        }
    });

    // ── Driver change → fetch balance ─────────────────────────────────────────
    $('#driver_id').on('change', function () {
        var accountId = $(this).find(':selected').data('account-id');
        var $balField = $('#driver_silak_balance');
        if (!accountId) {
            $balField.data('raw-balance', 0).val('0.00');
            recalcExpenseTotal(); return;
        }
        $balField.val('Loading…');
        $.ajax({
            url: closingBalanceUrl, type: 'GET', data: { account_ids: [accountId] },
            success: function (res) {
                var row     = (res.data || {})[accountId];
                var closing = row ? (parseFloat(row.closing) || 0) : 0;
                $balField.data('raw-balance', closing);
                $balField.val(formatBalanceDrCr(closing));
                recalcExpenseTotal();
            },
            error: function () {
                $balField.data('raw-balance', 0).val('0.00');
                recalcExpenseTotal();
            }
        });
    });

    // ── KM calculations ───────────────────────────────────────────────────────
    $('#start_kms, #end_kms').on('input', function () {
        var start = parseFloat($('#start_kms').val()) || 0;
        var end   = parseFloat($('#end_kms').val())   || 0;
        $('#total_kms').val((end - start).toFixed(2));
    });

    // ── Rate × Bags → Amount ──────────────────────────────────────────────────
    $(document).on('input', '.row-rate, .row-bags', function () {
        var $row = $(this).closest('tr');
        var rate = parseFloat($row.find('.row-rate').val()) || 0;
        var bags = parseFloat($row.find('.row-bags').val()) || 0;
        if (rate > 0 && bags > 0) { $row.find('.row-amount').val((rate * bags).toFixed(2)); }
        recalcTableTotals();
        recalcExpenseTotal();
    });

    $(document).on('input', '.row-weight, .row-trips, .row-amount', function () {
        recalcTableTotals();
        recalcExpenseTotal();
    });

    // ── Add Row modal ─────────────────────────────────────────────────────────
    $('#addRowModal').on('show.bs.modal', function () {
        $('#add_row_after').val($('#driver_expense_table_body tr').length);
        $('#add_row_count').val(5);
    });

    $('#add_row_confirm_btn').on('click', function () {
        var afterSerial = parseInt($('#add_row_after').val()) || 0;
        var count       = parseInt($('#add_row_count').val()) || 1;
        if (count < 1 || count > 100) { showToast('error', 'Row count must be between 1 and 100.'); return; }
        var $tbody = $('#driver_expense_table_body');
        var totalBefore = $tbody.find('tr').length;
        var $newRows = $();
        for (var i = 0; i < count; i++) { $newRows = $newRows.add(buildNewRow('')); }
        if (afterSerial <= 0 || afterSerial >= totalBefore) { $tbody.append($newRows); }
        else { $tbody.find('tr:nth-child(' + afterSerial + ')').after($newRows); }
        renumberRows();
        $('#addRowModal').modal('hide');
        showToast('success', count + ' row(s) added.');
    });

    // ── Delete row ────────────────────────────────────────────────────────────
    $(document).on('click', '.delete-row-btn', function () {
        $(this).closest('tr').remove();
        renumberRows();
        recalcTableTotals();
        recalcExpenseTotal();
    });

    // ── Form submit ───────────────────────────────────────────────────────────
    $('#driver_expense_form').on('submit', function (e) {
        e.preventDefault();
        if (isLocked) { showToast('error', 'This expense is locked. Payment has been applied.'); return; }

        var voucherDate = $('#voucher_date').val();
        var vehicleId   = $('#vehicle_id').val();
        var driverId    = $('#driver_id').val();

        if (!voucherDate) { showToast('error', 'Voucher Date is required.'); return; }
        if (!vehicleId)   { showToast('error', 'Please select a Vehicle.');  return; }
        if (!driverId)    { showToast('error', 'Please select a Driver.');   return; }

        var items = collectRows();
        if (items.length === 0) { showToast('error', 'Please enter at least one item row.'); return; }

        var expenseTotal = parseFloat($('#expense_total').val()) || 0;
        if (expenseTotal === 0) { showToast('error', 'Expense Total cannot be 0.'); return; }

        var payload = {
            _method:              'PUT',
            voucher_date:         dmyToYmd(voucherDate),
            vehicle_id:           vehicleId,
            driver_id:            driverId,
            narration:            $('#narration').val(),
            start_kms:            $('#start_kms').val()  || 0,
            end_kms:              $('#end_kms').val()     || 0,
            total_kms:            $('#total_kms').val()  || 0,
            start_diesel:         0,
            end_diesel:           0,
            diesel_average:       0,
            idle_days:            0,
            idle_day_wage:        0,
            idle_day_wage_amount: 0,
            expense_total:        $('#expense_total').val() || 0,
            items:                items,
            _token:               $('meta[name="csrf-token"]').attr('content'),
        };

        var remainingBalance = $('#remaining_balance').data('raw-remaining') || 0;

        function doUpdate() {
            $('#save_btn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Updating...');

            $.ajax({
                url:         updateDriverExpenseUrl,
                type:        'POST',
                data:        JSON.stringify(payload),
                contentType: 'application/json',
                success: function (res) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        html: 'Driver Expense updated successfully.<br>' +
                              '<strong>JV No: ' + (res.voucher_serial || '') + '</strong>',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#0d6efd',
                    }).then(function () {
                        history.go(-1);
                    });
                },
                error: function (xhr) {
                    var json  = xhr.responseJSON || {};
                    var title = 'Update Failed';
                    var body  = json.message || 'An unexpected error occurred.';
                    if (json.errors) {
                        var errLines = [];
                        $.each(json.errors, function (field, msgs) {
                            errLines.push('<li>' + (Array.isArray(msgs) ? msgs[0] : msgs) + '</li>');
                        });
                        body = '<ul style="text-align:left;margin:0;padding-left:1.2rem">' + errLines.join('') + '</ul>';
                    }
                    Swal.fire({ icon: 'error', title: title, html: body, confirmButtonText: 'Close', confirmButtonColor: '#dc3545' });
                    $('#save_btn').prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Update');
                }
            });
        }

        if (remainingBalance < 0) {
            Swal.fire({
                icon:               'warning',
                title:              'Negative Driver Balance',
                html:               'Driver balance will go <strong>negative</strong> by <strong>₹' +
                                    Math.abs(remainingBalance).toFixed(2) + '</strong>.<br>Do you want to proceed?',
                showCancelButton:   true,
                confirmButtonText:  'Yes, Proceed',
                cancelButtonText:   'No, Cancel',
                confirmButtonColor: '#f59f00',
                cancelButtonColor:  '#6c757d',
            }).then(function (result) {
                if (result.isConfirmed) { doUpdate(); }
            });
        } else {
            doUpdate();
        }
    });

});

// ── Pre-fill form with existing expense data ──────────────────────────────────
function prefillForm() {
    var e = window.editExpense;
    if (!e) return;

    // Track original expense so we can reverse it from the fetched ledger balance.
    // The closing balance API returns the CURRENT balance which already includes
    // this expense being credited. Adding it back gives the pre-expense balance.
    _originalExpenseTotal = parseFloat(e.expense_total) || 0;

    var $tbody = $('#driver_expense_table_body');
    $tbody.empty();

    // Build one row per existing item
    var items = e.items || [];
    items.forEach(function (item) {
        var $tr = buildNewRow('');

        if (item.date)
            $tr.find('.row-date').val(item.date);
        if (item.expense_account_id && item.expense_account_name)
            $tr.find('.row-expense-account')
               .val(item.expense_account_name)
               .attr('data-picked-id', String(item.expense_account_id));
        if (item.from_id && item.from_name)
            $tr.find('.row-from')
               .val(item.from_name)
               .attr('data-picked-id', String(item.from_id));
        if (item.to_id && item.to_name)
            $tr.find('.row-to')
               .val(item.to_name)
               .attr('data-picked-id', String(item.to_id));
        if (item.dc_lr)
            $tr.find('.row-dc-lr').val(item.dc_lr);
        if (item.item_id && item.item_name)
            $tr.find('.row-product')
               .val(item.item_name)
               .attr('data-picked-id', String(item.item_id));
        if (item.rate)   $tr.find('.row-rate').val(item.rate);
        if (item.bags)   $tr.find('.row-bags').val(item.bags);
        if (item.weight) $tr.find('.row-weight').val(item.weight);
        if (item.trips)  $tr.find('.row-trips').val(item.trips);
        if (item.amount) $tr.find('.row-amount').val(item.amount);
        if (item.remark) $tr.find('.row-remark').val(item.remark);

        $tbody.append($tr);
    });

    // Pad with blank rows so the table defaults to 250 rows (same as create),
    // but never truncate — if there are already more items than that, no padding is added.
    var DEFAULT_TOTAL_ROWS = 250;
    var blankRowsNeeded = Math.max(DEFAULT_TOTAL_ROWS - items.length, 0);
    for (var i = 0; i < blankRowsNeeded; i++) { $tbody.append(buildNewRow('')); }

    renumberRows();
    recalcTableTotals();
    recalcExpenseTotal();

    // Apply DateInput to newly created rows
    new DateInput('.row-date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    // Trigger driver balance fetch
    $('#driver_id').trigger('change');

    // If locked, disable all form inputs
    if (isLocked) {
        $('#driver_expense_form input, #driver_expense_form select, #driver_expense_form textarea').prop('disabled', true);
        $('#save_btn').prop('disabled', true);
        $('#add_row_btn').prop('disabled', true);
    }
}

// ── bindSelect2 ───────────────────────────────────────────────────────────────
function bindSelect2() {
    ['#driver_id', '#vehicle_id'].forEach(function (el) {
        $(el).select2({ theme: 'bootstrap-5', allowClear: true, placeholder: 'Select ...' });
    });
    $(document).on('select2:open', function (e) {
        var selectElement = $(e.target);
        if (selectElement.attr('id') === 'picker_select') return;
        var searchInput = selectElement.data('select2').$dropdown.find('.select2-search__field');
        searchInput.off('keydown.select2Enter').on('keydown.select2Enter', function (event) {
            if (event.which === 13) { event.preventDefault(); selectElement.select2('close'); }
        });
    });
}

// ── Picker ────────────────────────────────────────────────────────────────────
function openPicker($el, initialSearch) {
    if (_pickerTarget !== null) return;
    var type = $el.data('picker');
    var url  = type === 'expenseAccount' ? masterRoutes.expenseAccounts
             : type === 'destination'    ? masterRoutes.destinations
             : type === 'product'        ? masterRoutes.items
             : null;
    if (!url) { showToast('error', 'Unknown picker type.'); return; }
    _pickerTarget = $el; _pickerUrl = url; _pickerType = type; _pickerSelected = false; _pickerInitialSearch = initialSearch || '';
    var titles = { expenseAccount: 'Expense Account', destination: 'Destination', product: 'Product' };
    $('#picker_title').text(titles[type] || 'Select');
    $('#pickerModal').modal('show');
}

function highlightFirstPickerResult() {
    $('#pickerModal .select2-results__option[aria-selected]:not(.select2-results__option--disabled)').first().trigger('mouseenter');
}

// ── Preload full expense-account / destination / product lists once ──────────
// so the picker modal can filter in memory instead of hitting the backend
// on every keystroke.

function preloadPickerMasterData() {
    var configs = [
        { type: 'expenseAccount', url: masterRoutes.expenseAccounts },
        { type: 'destination',    url: masterRoutes.allDestinations },
        { type: 'product',        url: masterRoutes.allItems },
    ];

    configs.forEach(function (cfg) {
        _pickerDataRequests[cfg.type] = $.ajax({ url: cfg.url, method: 'GET', dataType: 'json' })
            .done(function (res) {
                _pickerMasterData[cfg.type] = (res.data || []).map(function (d) {
                    return { id: d.id, text: d.name };
                });
            });
    });
}

function arrowFocus($el) {
    $el.trigger('focus');
    setTimeout(function () { var el = $el[0]; if (el && typeof el.select === 'function') el.select(); }, 0);
}

function rowAdvance($el) {
    var $row = $el.closest('tr'), curIdx = -1;
    for (var i = 0; i < ROW_FIELD_ORDER.length; i++) {
        if ($el.is(ROW_FIELD_ORDER[i])) { curIdx = i; break; }
    }
    for (var j = curIdx + 1; j < ROW_FIELD_ORDER.length; j++) {
        var $next = $row.find(ROW_FIELD_ORDER[j]);
        if (!$next.length || $next.prop('disabled')) continue;
        $next.focus(); return;
    }
    var $nextRow = $row.next('tr');
    if ($nextRow.length) { var $first = $nextRow.find(ROW_FIELD_ORDER[0]); if ($first.length) $first.focus(); }
}

// ── Row builder ───────────────────────────────────────────────────────────────
function buildNewRow(defaultDate) {
    var $tr = $('<tr>');
    $tr.append($('<td class="text-center align-middle fw-bold text-secondary" style="font-size:12px;">').append($('<span class="row-serial">').text('')));
    $tr.append($('<td class="text-center p-1 align-middle">').append(
        $('<button type="button" class="delete-row-btn delete-row non-selectable" title="Delete row">').append(
            $('<i class="fa-solid fa-trash-can" style="font-size:11px;">')
        )
    ));
    $tr.append($('<td>').append($('<input type="text" class="form-control date-format row-date" placeholder="DD-MM-YYYY">').val(defaultDate || '')));
    $tr.append($('<td>').append($('<input type="text" class="form-control row-picker row-expense-account" data-picker="expenseAccount" placeholder="">')));
    $tr.append($('<td>').append($('<input type="text" class="form-control row-picker row-from" data-picker="destination" placeholder="">')));
    $tr.append($('<td>').append($('<input type="text" class="form-control row-picker row-to" data-picker="destination" placeholder="">')));
    $tr.append($('<td>').append($('<input type="text" class="form-control row-dc-lr" placeholder="">')));
    $tr.append($('<td>').append($('<input type="text" class="form-control row-picker row-product" data-picker="product" placeholder="">')));
    $tr.append($('<td>').append($('<input type="text" class="form-control text-end row-rate only-number" placeholder="0.00">')));
    $tr.append($('<td>').append($('<input type="text" class="form-control text-end row-bags only-number" value="0" placeholder="0">')));
    $tr.append($('<td>').append($('<input type="text" class="form-control text-end row-weight only-number" value="0" placeholder="0">')));
    $tr.append($('<td>').append($('<input type="text" class="form-control text-end row-trips only-number" placeholder="">')));
    $tr.append($('<td>').append($('<input type="text" class="form-control text-end row-amount only-number" placeholder="0.00">')));
    $tr.append($('<td>').append($('<input type="text" class="form-control row-remark" placeholder="">')));
    return $tr;
}

function renumberRows() {
    $('#driver_expense_table_body tr').each(function (index) { $(this).find('.row-serial').text(index + 1); });
}

function recalcTableTotals() {
    var totalBags = 0, totalWeight = 0, totalAmount = 0;
    $('#driver_expense_table_body tr').each(function () {
        totalBags   += parseFloat($(this).find('.row-bags').val())   || 0;
        totalWeight += parseFloat($(this).find('.row-weight').val()) || 0;
        totalAmount += parseFloat($(this).find('.row-amount').val()) || 0;
    });
    $('#total_bags').val(totalBags.toFixed(2));
    $('#total_weight').val(totalWeight.toFixed(2));
    $('#total_amount').val(totalAmount.toFixed(2));
}

function recalcExpenseTotal() {
    var tableAmount  = parseFloat($('#total_amount').val()) || 0;
    var expenseTotal = tableAmount;
    $('#expense_total').val(expenseTotal.toFixed(2));
    // The fetched closing balance already reflects the previously-posted expense.
    // Add back the original expense total to get the pre-expense balance, then
    // subtract the new expense total to show what the balance will be after update.
    var silakBalance    = $('#driver_silak_balance').data('raw-balance') || 0;
    var adjustedBalance = silakBalance + _originalExpenseTotal;
    var remaining       = adjustedBalance - expenseTotal;
    $('#remaining_balance').data('raw-remaining', remaining).val(formatBalanceDrCr(remaining));
}

function formatBalanceDrCr(value) {
    var abs   = Math.abs(value).toFixed(2);
    var label = value >= 0 ? 'Dr' : 'Cr';
    return abs + ' ' + label;
}

function collectRows() {
    var rows = [];
    $('#driver_expense_table_body tr').each(function () {
        var $row = $(this);
        var date   = $row.find('.row-date').val().trim();
        var expAcc = $row.find('.row-expense-account').attr('data-picked-id');
        var from   = $row.find('.row-from').attr('data-picked-id');
        var to     = $row.find('.row-to').attr('data-picked-id');
        var dcLr   = $row.find('.row-dc-lr').val().trim();
        var prod   = $row.find('.row-product').attr('data-picked-id');
        var rate   = parseFloat($row.find('.row-rate').val()) || 0;
        var bags   = parseFloat($row.find('.row-bags').val()) || 0;
        var weight = parseFloat($row.find('.row-weight').val()) || 0;
        var trips  = parseInt($row.find('.row-trips').val()) || 0;
        var amount = parseFloat($row.find('.row-amount').val()) || 0;
        var remark = $row.find('.row-remark').val().trim();
        var isDirty = date || expAcc || from || to || dcLr || prod || rate || bags || weight || trips || amount || remark;
        if (!isDirty) return;
        rows.push({
            date: dmyToYmd(date),
            expense_account_id: expAcc || null,
            from_id:  from  || null,
            to_id:    to    || null,
            dc_lr:    dcLr,
            item_id:  prod  || null,
            rate:     rate,
            bags:     bags,
            weight:   weight,
            trips:    trips,
            amount:   amount,
            remark:   remark,
        });
    });
    return rows;
}

function isValidDateDMY(str) {
    if (!str || str.length !== 10) return false;
    var p = str.split('-');
    if (p.length !== 3) return false;
    var d = parseInt(p[0]), m = parseInt(p[1]), y = parseInt(p[2]);
    return d >= 1 && d <= 31 && m >= 1 && m <= 12 && y >= 1900;
}

function parseDMY(str) {
    var p = str.split('-');
    return new Date(+p[2], +p[1] - 1, +p[0]);
}

function dmyToYmd(str) {
    if (!str) return '';
    var p = str.split('-');
    if (p.length !== 3) return str;
    return p[2] + '-' + p[1] + '-' + p[0];
}
