/* global DateInput */

// ── Dairy import cache (keyed by "vehicleId_date") ────────────────────────────
// Each entry: { items: [...], usedIds: [id, ...] }
// usedIds tracks which DairyImportItem IDs have already been placed in a row,
// so the same record is never auto-filled into two rows.
var _dairyCache = {};

// ── Picker state ──────────────────────────────────────────────────────────────
var _pickerTarget        = null;   // input cell that opened the picker
var _pickerUrl           = null;   // AJAX search URL for this picker type
var _pickerSelected      = false;  // did user confirm a selection?
var _pickerShouldAdvance = false;  // should move to next cell (selected OR bypassed)
var _pickerInitialSearch = '';     // all characters typed before modal was fully open

// ── Picker master-data preload (in-memory search, no per-keystroke AJAX) ──────
var _pickerType         = null;    // 'expenseAccount' | 'destination' | 'product'
var _pickerMasterData   = { expenseAccount: null, destination: null, product: null };
var _pickerDataRequests = { expenseAccount: null, destination: null, product: null };

// Explicit column order for Enter-key row navigation
var ROW_FIELD_ORDER = [
    '.row-date', '.row-expense-account', '.row-from', '.row-to',
    '.row-dc-lr', '.row-product', '.row-rate', '.row-bags',
    '.row-weight', '.row-trips', '.row-amount', '.row-remark'
];

$(function () {

    bindSelect2();
    preloadPickerMasterData();

    $('#master_loader').hide();
    $('#voucher_date').trigger('focus');

    // ── Date pickers ──────────────────────────────────────────────────────────
    new DateInput('#voucher_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('.row-date',     FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    // ── Header selects ────────────────────────────────────────────────────────
    // $('#vehicle_id, #driver_id').select2({ theme: 'bootstrap-5' });

    // ── Date blur → auto-fill from dairy import data (memory-cached) ───────────
    $('#driver_expense_table_body').on('blur', '.row-date', function () {
        var dateVal   = $(this).val().trim();
        var vehicleId = $('#vehicle_id').val();

        if (!isValidDateDMY(dateVal) || !vehicleId) return;

        var $startRow = $(this).closest('tr');
        var cacheKey  = vehicleId + '_' + dateVal;

        function fillRows(entry) {
            // Only place items that haven't been used yet in this form session
            var unused = entry.items.filter(function (item) {
                return entry.usedIds.indexOf(item.id) === -1;
            });
            if (!unused.length) return;

            unused.forEach(function (item, idx) {
                var $row = idx === 0 ? $startRow : $startRow.nextAll('tr').eq(idx - 1);
                if (!$row.length) return;

                entry.usedIds.push(item.id);   // mark as placed

                // Stamp the dairy import item id so it travels in the payload
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
                if (item.rate)
                    $row.find('.row-rate').val(item.rate);

                // Auto-calculate amount = rate × bags after import fill
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

        if (_dairyCache[cacheKey]) {
            // Already fetched — use memory, no round-trip
            fillRows(_dairyCache[cacheKey]);
            return;
        }

        // Show loader on the date cell while fetching
        var $dateTd = $startRow.find('.row-date').closest('td');
        $dateTd.css('position', 'relative').append(
            '<div class="date-fetch-loader" style="' +
            'position:absolute;inset:0;' +
            'background:rgba(255,255,255,0.82);' +
            'display:flex;align-items:center;justify-content:center;z-index:5;">' +
            '<div class="spinner-border spinner-border-sm text-primary" role="status">' +
            '<span class="visually-hidden">Loading…</span></div></div>'
        );

        $.ajax({
            url:     getDairyImportDataUrl,
            method:  'GET',
            data:    { date: dateVal, vehicle_id: vehicleId },
            success: function (res) {
                _dairyCache[cacheKey] = {
                    items:   res.found ? res.items : [],
                    usedIds: []
                };
                fillRows(_dairyCache[cacheKey]);
            },
            complete: function () {
                $dateTd.find('.date-fetch-loader').remove();
            }
        });
    });

    // ── Date blur → enforce ascending date order across rows ─────────────────
    $('#driver_expense_table_body').on('blur', '.row-date', function () {
        var dateVal = $(this).val().trim();
        var $thisRow = $(this).closest('tr');
        var $prevRow = $thisRow.prev('tr');

        if (!$prevRow.length) return;                             // first row – nothing to compare
        if (!isValidDateDMY(dateVal)) return;                     // not a valid date yet – leave as-is

        var prevDateVal = $prevRow.find('.row-date').val().trim();
        if (!prevDateVal || !isValidDateDMY(prevDateVal)) return; // previous row has no valid date

        if (parseDMY(dateVal) < parseDMY(prevDateVal)) {
            $(this).val('');
            showToast('error', 'Date must not be earlier than previous row date (' + prevDateVal + ').');
        }
    });

    // ── Table Enter: advance to next cell (fires before main.js body handler) ─
    $('#driver_expense_table_body').on('keydown', 'input', function (e) {
        if (e.which !== 13) return;
        e.preventDefault();
        e.stopPropagation();
        rowAdvance($(this));
    });

    // ── Table arrow-key navigation ────────────────────────────────────────────
    $('#driver_expense_table_body').on('keydown', 'input', function (e) {
        var key = e.key;
        var el  = this;

        // Left: only when cursor is already at the start of the text
        // Right: only when cursor is already at the end of the text
        // Up / Down: always navigate
        var goLeft  = key === 'ArrowLeft'  && el.selectionStart === 0 && el.selectionEnd === 0;
        var goRight = key === 'ArrowRight' && el.selectionStart === el.value.length && el.selectionEnd === el.value.length;
        var goUp    = key === 'ArrowUp';
        var goDown  = key === 'ArrowDown';

        if (!goLeft && !goRight && !goUp && !goDown) return;

        e.preventDefault();
        e.stopPropagation();

        var $el    = $(this);
        var $row   = $el.closest('tr');
        var curIdx = -1;

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
            if ($nextRow.length) {
                var $n = $nextRow.find(ROW_FIELD_ORDER[curIdx]);
                if ($n.length) arrowFocus($n);
            }
        } else if (goUp) {
            var $prevRow = $row.prev('tr');
            if ($prevRow.length) {
                var $n = $prevRow.find(ROW_FIELD_ORDER[curIdx]);
                if ($n.length) arrowFocus($n);
            }
        }
    });

    // ── Picker: Backspace / Delete clears entire value + stale picked ID ────────
    $('#driver_expense_table_body').on('keydown', '.row-picker', function (e) {
        if (e.key !== 'Backspace' && e.key !== 'Delete') return;
        e.preventDefault();
        $(this).val('').removeAttr('data-picked-id');
    });

    // ── Picker trigger: printable key in picker cell → open modal ────────────
    $('#driver_expense_table_body').on('keydown', '.row-picker', function (e) {
        if (e.which === 9 || e.which === 27) return;       // Tab / Escape: browser default
        if (e.which === 13) return;                        // Enter: already handled above
        if (e.ctrlKey || e.altKey || e.metaKey) return;   // shortcuts: ignore
        if (!e.key || e.key.length !== 1) return;          // non-printable (arrow, F-key…): ignore
        e.preventDefault();
        e.stopPropagation();
        if (_pickerTarget !== null) {
            // Modal already opening — accumulate extra chars typed during animation
            _pickerInitialSearch += e.key;
        } else {
            openPicker($(this), e.key);
        }
    });

    // ── Picker trigger: click on picker cell → open modal ────────────────────
    $('#driver_expense_table_body').on('click', '.row-picker', function () {
        openPicker($(this), '');
    });

    // ── Picker modal: show loader while animation plays ───────────────────────
    $('#pickerModal').on('show.bs.modal', function () {
        $('#picker_loader').show();
        // Remove any leftover Select2 container from a previous open
        $('#pickerModal .select2-container').remove();
    });

    // ── Picker modal: initialize Select2 with AJAX once fully shown ──────────
    $('#pickerModal').on('shown.bs.modal', function () {
        var $sel    = $('#picker_select');
        var $loader = $('#picker_loader');

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
            //     data: function (params) {
            //         return { q: params.term || '' };
            //     },
            //     processResults: function (res) {
            //         return {
            //             results: (res.data || []).map(function (d) {
            //                 return { id: d.id, text: d.name };
            //             })
            //         };
            //     },
            //     complete: function () {
            //         setTimeout(highlightFirstPickerResult, 30);
            //     },
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

            if (_pickerInitialSearch) {
                $searchBox.val(_pickerInitialSearch).trigger('input');
            }

            // Capture-phase keydown: intercepts before Select2's own handlers
            var el = $searchBox[0];
            if (el) {
                el.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        // Intercept before Select2's Escape handler so select2:close never fires
                        e.stopImmediatePropagation();
                        _pickerShouldAdvance = false;
                        _pickerSelected      = false;
                        $('#pickerModal').modal('hide');
                        return;
                    }

                    if (e.key !== 'Enter') return;

                    var highlighted = !!$('#pickerModal .select2-results__option--highlighted').length;

                    if (highlighted) {
                        // Arrow-keyed onto an item → let Select2's own Enter handler select it
                        return;
                    }

                    if (this.value === '') {
                        // Empty search + nothing highlighted → bypass this cell
                        e.stopImmediatePropagation();
                        _pickerShouldAdvance = true;
                        $('#pickerModal').modal('hide');
                    } else {
                        // Has text but highlight not yet applied → select first visible result
                        e.stopImmediatePropagation();
                        $('#pickerModal .select2-results__option[aria-selected]:not(.select2-results__option--disabled)')
                            .first()
                            .trigger('mouseup');
                    }
                }, true);
            }
        }, 0);
    });

    // ── Picker: user confirmed a selection ────────────────────────────────────
    $('#picker_select').on('select2:select', function () {
        var data = $(this).select2('data');
        if (!data || !data.length || !data[0].id) return;
        _pickerSelected      = true;
        _pickerShouldAdvance = true;
        if (_pickerTarget) {
            _pickerTarget
                .val(data[0].text)
                .attr('data-picked-id', String(data[0].id));
        }
        $('#pickerModal').modal('hide');
    });

    // select2:close fires as a side-effect of select2('destroy') in hidden.bs.modal.
    // Modal closing is handled explicitly (select2:select, Escape, bypass, X button) — no action needed here.

    // ── Picker modal: cleanup after fully hidden ───────────────────────────────
    $('#pickerModal').on('hidden.bs.modal', function () {
        var $target      = _pickerTarget;
        var shouldAdvance = _pickerShouldAdvance;

        // Destroy before resetting flags so any stray select2:close still
        // sees the original _pickerSelected / _pickerShouldAdvance values.
        if ($('#picker_select').data('select2')) {
            $('#picker_select').select2('destroy');
        }

        _pickerTarget        = null;
        _pickerSelected      = false;
        _pickerShouldAdvance = false;
        _pickerInitialSearch = '';

        if (shouldAdvance && $target) {
            rowAdvance($target);    // selected or bypassed → move to next cell
        } else if ($target) {
            $target.focus();        // cancelled (Escape/X) → return focus to cell
        }
    });

    // ── Vehicle change → clear dairy cache (different vehicle = different data) ─
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

    // ── Driver change → fetch live closing ledger balance ─────────────────────
    $('#driver_id').on('change', function () {
        var accountId = $(this).find(':selected').data('account-id');
        var $balField = $('#driver_silak_balance');

        if (!accountId) {
            $balField.val('0.00');
            recalcExpenseTotal();
            return;
        }

        $balField.val('Loading…');

        $.ajax({
            url:    closingBalanceUrl,
            type:   'GET',
            data:   { account_ids: [accountId] },
            success: function (res) {
                var row     = (res.data || {})[accountId];
                var closing = row ? (parseFloat(row.closing) || 0) : 0;
                $balField.data('raw-balance', closing);
                $balField.val(formatBalanceDrCr(closing));
                recalcExpenseTotal();
            },
            error: function () {
                $balField.data('raw-balance', 0);
                $balField.val('0.00');
                recalcExpenseTotal();
            }
        });
    });

    // ── KM calculations ───────────────────────────────────────────────────────
    $('#start_kms, #end_kms').on('input', function () {
        var start = parseFloat($('#start_kms').val()) || 0;
        var end   = parseFloat($('#end_kms').val())   || 0;
        $('#total_kms').val((end - start).toFixed(2));
        recalcDieselAverage();
    });

    $('#start_diesel, #end_diesel').on('input', recalcDieselAverage);

    // ── Idle day wage ─────────────────────────────────────────────────────────
    $('#idle_days, #idle_day_wage').on('input', function () {
        var days = parseFloat($('#idle_days').val())     || 0;
        var wage = parseFloat($('#idle_day_wage').val()) || 0;
        $('#idle_day_wage_amount').val((days * wage).toFixed(2));
        recalcExpenseTotal();
    });

    // ── Rate × Bags → Amount ──────────────────────────────────────────────────
    $(document).on('input', '.row-rate, .row-bags', function () {
        var $row = $(this).closest('tr');
        var rate = parseFloat($row.find('.row-rate').val()) || 0;
        var bags = parseFloat($row.find('.row-bags').val()) || 0;
        if (rate > 0 && bags > 0) {
            $row.find('.row-amount').val((rate * bags).toFixed(2));
        }
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
        $('#add_row_default_date').val('');
        new DateInput('#add_row_default_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    });

    $('#add_row_confirm_btn').on('click', function () {
        var afterSerial = parseInt($('#add_row_after').val()) || 0;
        var count       = parseInt($('#add_row_count').val()) || 1;
        var defaultDate = $('#add_row_default_date').val();

        if (count < 1 || count > 100) {
            showToast('error', 'Row count must be between 1 and 100.');
            return;
        }

        var $tbody      = $('#driver_expense_table_body');
        var totalBefore = $tbody.find('tr').length;
        var $newRows    = $();

        for (var i = 0; i < count; i++) {
            $newRows = $newRows.add(buildNewRow(defaultDate));
        }

        if (afterSerial <= 0 || afterSerial >= totalBefore) {
            $tbody.append($newRows);
        } else {
            $tbody.find('tr:nth-child(' + afterSerial + ')').after($newRows);
        }

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

        var voucherDate = $('#voucher_date').val();
        var vehicleId   = $('#vehicle_id').val();
        var driverId    = $('#driver_id').val();

        if (!voucherDate) { showToast('error', 'Voucher Date is required.'); return; }
        if (!vehicleId)   { showToast('error', 'Please select a Vehicle.');  return; }
        if (!driverId)    { showToast('error', 'Please select a Driver.');   return; }

        var items = collectRows();
        if (items.length === 0) {
            showToast('error', 'Please enter at least one item row.');
            return;
        }

        var expenseTotal = parseFloat($('#expense_total').val()) || 0;
        if (expenseTotal === 0) {
            showToast('error', 'Expense Total cannot be 0.');
            return;
        }

        var payload = {
            uuid: $('#uuid').val(),
            voucher_date: dmyToYmd(voucherDate),
            vehicle_id:           vehicleId,
            driver_id:            driverId,
            expense_account_id:   $('#expense_account_id').val(),
            narration:            $('#narration').val(),
            start_kms:            $('#start_kms').val()            || 0,
            end_kms:              $('#end_kms').val()              || 0,
            total_kms:            $('#total_kms').val()            || 0,
            start_diesel:         $('#start_diesel').val()         || 0,
            end_diesel:           $('#end_diesel').val()           || 0,
            diesel_average:       $('#diesel_average').val()       || 0,
            idle_days:            $('#idle_days').val()            || 0,
            idle_day_wage:        $('#idle_day_wage').val()        || 0,
            idle_day_wage_amount: $('#idle_day_wage_amount').val() || 0,
            expense_total:        $('#expense_total').val()        || 0,
            items:                items,
            _token:               $('meta[name="csrf-token"]').attr('content'),
        };

        var remainingBalance = $('#remaining_balance').data('raw-remaining') || 0;

        function doSave() {
            $('#save_btn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

            $.ajax({
                url:         storeDriverExpenseUrl,
                type:        'POST',
                data:        JSON.stringify(payload),
                contentType: 'application/json',
                success: function (res) {
                    Swal.fire({
                        icon:              'success',
                        title:             'Saved!',
                        html:              'Driver Expense saved successfully.<br>' +
                                           '<strong>JV No: ' + (res.voucher_serial || '') + '</strong>',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#0d6efd',
                    }).then(function () {
                        location.reload();
                    });
                },
                error: function (xhr) {
                    var json  = xhr.responseJSON || {};
                    var title = 'Save Failed';
                    var body  = json.message || 'An unexpected error occurred. Please try again.';
                    if (json.errors) {
                        var errLines = [];
                        $.each(json.errors, function (field, msgs) {
                            errLines.push('<li>' + (Array.isArray(msgs) ? msgs[0] : msgs) + '</li>');
                        });
                        body = '<ul style="text-align:left;margin:0;padding-left:1.2rem">' + errLines.join('') + '</ul>';
                    }
                    Swal.fire({
                        icon:              'error',
                        title:             title,
                        html:              body,
                        confirmButtonText: 'Close',
                        confirmButtonColor: '#dc3545',
                    });
                    $('#save_btn').prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Save');
                }
            });
        }

        if (remainingBalance < 0) {
            Swal.fire({
                icon:                'warning',
                title:               'Negative Driver Balance',
                html:                'Driver balance will go <strong>negative</strong> by <strong>₹' +
                                     Math.abs(remainingBalance).toFixed(2) + '</strong>.<br>Do you want to proceed?',
                showCancelButton:    true,
                confirmButtonText:   'Yes, Proceed',
                cancelButtonText:    'No, Cancel',
                confirmButtonColor:  '#f59f00',
                cancelButtonColor:   '#6c757d',
            }).then(function (result) {
                if (result.isConfirmed) {
                    doSave();
                }
            });
        } else {
            doSave();
        }
    });

});


function bindSelect2() {
    var selectIdArray = ['#driver_id', '#vehicle_id'];

    selectIdArray.forEach(function (element) {
        $(element).select2({
            theme: 'bootstrap-5',
            allowClear: true,
            placeholder: 'Select ...',
        });
    });

    $(document).on('select2:open', function (e) {
        var selectElement = $(e.target);

        // Prevent generic Enter key focus movement for the picker modal
        if (selectElement.attr('id') === 'picker_select') return;

        var searchInput = selectElement.data('select2').$dropdown.find('.select2-search__field');

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
// ── Picker ────────────────────────────────────────────────────────────────────

function openPicker($el, initialSearch) {
    if (_pickerTarget !== null) return;   // already handling a picker

    var type = $el.data('picker');
    var url  = type === 'expenseAccount' ? masterRoutes.expenseAccounts
             : type === 'destination'    ? masterRoutes.destinations
             : type === 'product'        ? masterRoutes.items
             : null;

    if (!url) {
        showToast('error', 'Unknown picker type.');
        return;
    }

    _pickerTarget        = $el;
    _pickerUrl           = url;
    _pickerType          = type;
    _pickerSelected      = false;
    _pickerInitialSearch = initialSearch || '';

    var titles = { expenseAccount: 'Expense Account', destination: 'Destination', product: 'Product' };
    $('#picker_title').text(titles[type] || 'Select');
    $('#pickerModal').modal('show');
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

// ── Highlight first result in the open picker dropdown ───────────────────────

function highlightFirstPickerResult() {
    $('#pickerModal .select2-results__option[aria-selected]:not(.select2-results__option--disabled)')
        .first()
        .trigger('mouseenter');
}

// ── Arrow-key focus: focus then select all text ───────────────────────────────

function arrowFocus($el) {
    $el.trigger('focus');
    // setTimeout ensures select runs after the browser finishes placing the cursor
    setTimeout(function () {
        var el = $el[0];
        if (el && typeof el.select === 'function') el.select();
    }, 0);
}

// ── Row navigation ────────────────────────────────────────────────────────────

function rowAdvance($el) {
    var $row   = $el.closest('tr');
    var curIdx = -1;

    for (var i = 0; i < ROW_FIELD_ORDER.length; i++) {
        if ($el.is(ROW_FIELD_ORDER[i])) { curIdx = i; break; }
    }

    for (var j = curIdx + 1; j < ROW_FIELD_ORDER.length; j++) {
        var $next = $row.find(ROW_FIELD_ORDER[j]);
        if (!$next.length || $next.prop('disabled')) continue;
        $next.focus();
        return;
    }

    // End of row → first cell of next row
    var $nextRow = $row.next('tr');
    if ($nextRow.length) {
        var $first = $nextRow.find(ROW_FIELD_ORDER[0]);
        if ($first.length) $first.focus();
    }
}

// ── Table helpers ─────────────────────────────────────────────────────────────

function buildNewRow(defaultDate) {
    var $tr = $('<tr>');

    $tr.append($('<td class="text-center align-middle fw-bold text-secondary" style="font-size:12px;">').append(
        $('<span class="row-serial">').text('')
    ));
    $tr.append($('<td class="text-center p-1 align-middle">').append(
        $('<button type="button" class="delete-row-btn delete-row non-selectable" title="Delete row">').append(
            $('<i class="fa-solid fa-trash-can" style="font-size:11px;">')
        )
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control date-format row-date" placeholder="DD-MM-YYYY">').val(defaultDate || '')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control row-picker row-expense-account" data-picker="expenseAccount" placeholder="">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control row-picker row-from" data-picker="destination" placeholder="">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control row-picker row-to" data-picker="destination" placeholder="">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control row-dc-lr" placeholder="">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control row-picker row-product" data-picker="product" placeholder="">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control text-end row-rate only-number" placeholder="0.00">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control text-end row-bags only-number" value="0" placeholder="0">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control text-end row-weight only-number" value="0" placeholder="0">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control text-end row-trips only-number" placeholder="">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control text-end row-amount only-number" placeholder="0.00">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control row-remark" placeholder="">')
    ));

    return $tr;
}

function renumberRows() {
    $('#driver_expense_table_body tr').each(function (index) {
        $(this).find('.row-serial').text(index + 1);
    });
}

function recalcDieselAverage() {
    var start   = parseFloat($('#start_diesel').val()) || 0;
    var end     = parseFloat($('#end_diesel').val())   || 0;
    var totalKm = parseFloat($('#total_kms').val())    || 0;
    var diff    = end - start;
    $('#diesel_average').val((diff > 0 && totalKm > 0) ? (totalKm / diff).toFixed(2) : '0.00');
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
    var tableAmount  = parseFloat($('#total_amount').val())         || 0;
    var idleAmount   = parseFloat($('#idle_day_wage_amount').val()) || 0;
    var expenseTotal = tableAmount + idleAmount;
    $('#expense_total').val(expenseTotal.toFixed(2));
    var silakBalance = $('#driver_silak_balance').data('raw-balance') || 0;
    var remaining    = silakBalance - expenseTotal;
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
        var date = $row.find('.row-date').val().trim();
        var expAcc = $row.find('.row-expense-account').attr('data-picked-id');
        var from = $row.find('.row-from').attr('data-picked-id');
        var to = $row.find('.row-to').attr('data-picked-id');
        var dcLr = $row.find('.row-dc-lr').val().trim();
        var prod = $row.find('.row-product').attr('data-picked-id');
        var rate = parseFloat($row.find('.row-rate').val()) || 0;
        var bags = parseFloat($row.find('.row-bags').val()) || 0;
        var weight = parseFloat($row.find('.row-weight').val()) || 0;
        var trips = parseInt($row.find('.row-trips').val()) || 0;
        var amount = parseFloat($row.find('.row-amount').val()) || 0;
        var remark = $row.find('.row-remark').val().trim();

        var isDirty = date || expAcc || from || to || dcLr || prod || rate || bags || weight || trips || amount || remark;
        if (!isDirty) return;

        var dairyItemId = parseInt($row.attr('data-dairy-item-id')) || null;

        rows.push({
            date: dmyToYmd(date),
            expense_account_id:   expAcc || null,
            from_id:              from || null,
            to_id:                to || null,
            dc_lr:                dcLr,
            item_id:              prod || null,
            rate:                 rate,
            bags:                 bags,
            weight:               weight,
            trips:                trips,
            amount:               amount,
            remark:               remark,
            dairy_import_item_id: dairyItemId,
        });
    });
    return rows;
}

// ── Parse DD-MM-YYYY string into a Date (for comparison only) ─────────────────
function parseDMY(str) {
    var p = str.split('-');
    return new Date(+p[2], +p[1] - 1, +p[0]);
}

// ── Convert DD-MM-YYYY → YYYY-MM-DD for backend ───────────────────────────────
function dmyToYmd(str) {
    if (!str) return '';
    var p = str.split('-');
    if (p.length !== 3) return str;
    return p[2] + '-' + p[1] + '-' + p[0];
}
