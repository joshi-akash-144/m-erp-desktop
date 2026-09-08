/* Multi Expense Voucher – edit.js
 * Reuses all interaction logic from create.js.
 * After voucher data loads, pre-fills the form from window.editExpense.
 * Submits a PUT request to updateMultiExpenseVoucherUrl.
 */

/* global DateInput */

// Explicit column order for Enter-key row navigation
var ROW_FIELD_ORDER = [
    '.row-challan-no', '.row-bill-no', '.row-date', '.row-expense-account', '.row-amount', '.row-remark'
];

$(function () {

    bindSelect2();
    preloadPickerMasterData();

    // ── Fetch voucher data ────────────────────────────────────────────────────
    $.ajax({ url: getMultiExpenseVoucherDataUrl, dataType: 'json' })
        .then(function (res) {
            window.editExpense = res;
            prefillForm();
            $('#master_loader').fadeOut(300);
        })
        .fail(function () {
            $('#master_loader_text').html(
                '<span class="text-danger"><i class="fa-solid fa-circle-exclamation me-1"></i>' +
                'Failed to load data. Please refresh the page.</span>'
            );
        });

    // ── Date pickers ──────────────────────────────────────────────────────────
    new DateInput('#voucher_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('.row-date',     FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    // ── Day of Week Updater ──────────────────────────────────────────────────
    function updateVoucherDay() {
        var dateVal = $('#voucher_date').val().trim();
        if (dateVal && typeof isValidDateDMY === 'function' && isValidDateDMY(dateVal)) {
            var dateObj = parseDMY(dateVal);
            var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $('#day_for').val(days[dateObj.getDay()]);
        } else {
            $('#day_for').val('');
        }
    }
    $('#voucher_date').on('blur change', updateVoucherDay);

    // ── Account Change: Fetch Pending Challans for Picker ───────────────────
    $('#account_id').on('change', function () {
        _pickerMasterData['challans'] = [];
        var accountId = $(this).val();
        if (!accountId) return;

        _pickerDataRequests['challans'] = $.ajax({
            url: pendingChallansUrl,
            type: 'GET',
            data: { 
                account_id: accountId,
                voucher_id: window.editExpense ? window.editExpense.id : ''
            }
        }).done(function (res) {
            if (res.success && res.data) {
                _pickerMasterData['challans'] = res.data.map(function (ch) {
                    var vName = ch.vehicle ? ch.vehicle.name : 'Unknown';
                    return {
                        id: ch.challan_number,
                        text: ch.challan_number + ' | ' + vName,
                        challanNo: ch.challan_number,
                        vehicleId: ch.vehicle_id,
                        vehicleName: vName,
                        amount: ch.amount,
                        date: ch.today_date,
                        liter: ch.liter,
                        rate: ch.diesel_rate
                    };
                });
            }
        });
    });

    // ── Challan No change → auto-fill vehicle and amount ───────────────────────
    $('#multi_expense_table_body').on('change', '.row-challan-no', function () {
        var val = $(this).val().trim();
        var $row = $(this).closest('tr');
        var $vehicleInput = $row.find('.row-expense-account');
        var $amountInput = $row.find('.row-amount');
        var $dateInput = $row.find('.row-date');
        var $remarkInput = $row.find('.row-remark');

        if (!val) {
            $vehicleInput.prop('readonly', false).css('pointer-events', 'auto');
            $amountInput.prop('readonly', false);
            return;
        }

        var found = (_pickerMasterData['challans'] || []).find(function(c) {
            return String(c.challanNo).toLowerCase() === val.toLowerCase();
        });

        if (found) {
            $vehicleInput.val(found.vehicleName).attr('data-picked-id', String(found.vehicleId));
            $amountInput.val(found.amount);
            
            if (found.date) {
                var p = String(found.date).substring(0, 10).split('-');
                if (p.length === 3) {
                    $dateInput.val(p[2] + '-' + p[1] + '-' + p[0]);
                }
            }
            
            if (found.liter) {
                var liter = parseFloat(found.liter) || 0;
                var rate = parseFloat(found.rate) || 0;
                var amt = parseFloat(found.amount) || 0;

                if (amt === 0 && rate > 0 && liter > 0) {
                    amt = rate * liter;
                    $amountInput.val(amt.toFixed(2));
                }

                var vNameStr = found.vehicleName ? ' | ' + found.vehicleName : '';
                var cNoStr = found.challanNo ? ' | Challan No: ' + found.challanNo : '';
                $remarkInput.val(rate.toFixed(2) + ' * ' + liter.toFixed(2) + ' Ltr = ' + amt.toFixed(2) + vNameStr + cNoStr);
            }

            // Make readonly
            $vehicleInput.prop('readonly', true).css('pointer-events', 'none');
            $amountInput.prop('readonly', true);
            
            recalcTableTotals();
        } else {
            $vehicleInput.prop('readonly', false).css('pointer-events', 'auto');
            $amountInput.prop('readonly', false);
            
            $(this).val('');
            showToast('error', 'No diesel data found for this row');
        }
    });

    // ── Bill No blur → flag duplicate bill numbers across rows ───────────────
    $('#multi_expense_table_body').on('blur', '.row-bill-no', function () {
        checkDuplicateBillNos();
    });

    // ── Date blur → enforce ascending date order across rows ─────────────────
    $('#multi_expense_table_body').on('blur', '.row-date', function () {
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

    // ── Table Enter: advance to next cell ─────────────────────────────────────
    $('#multi_expense_table_body').on('keydown', 'input', function (e) {
        if (e.which !== 13) return;
        e.preventDefault();
        e.stopPropagation();
        rowAdvance($(this));
    });

    // ── Table arrow-key navigation ────────────────────────────────────────────
    $('#multi_expense_table_body').on('keydown', 'input', function (e) {
        var key = e.key;
        var el  = this;

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
    $('#multi_expense_table_body').on('keydown', '.row-picker', function (e) {
        if ($(this).prop('readonly')) return;
        if (e.key !== 'Backspace' && e.key !== 'Delete') return;
        e.preventDefault();
        $(this).val('').removeAttr('data-picked-id');
    });

    // ── Picker trigger: printable key in picker cell → open modal ────────────
    $('#multi_expense_table_body').on('keydown', '.row-picker', function (e) {
        if ($(this).prop('readonly')) return;
        if (e.which === 9 || e.which === 27) return;
        if (e.which === 13) return;
        if (e.ctrlKey || e.altKey || e.metaKey) return;
        if (!e.key || e.key.length !== 1) return;
        e.preventDefault();
        e.stopPropagation();
        if (_pickerTarget !== null) {
            _pickerInitialSearch += e.key;
        } else {
            openPicker($(this), e.key);
        }
    });

    // ── Picker trigger: click on picker cell → open modal ────────────────────
    $('#multi_expense_table_body').on('click', '.row-picker', function () {
        if ($(this).prop('readonly')) return;
        openPicker($(this), '');
    });

    // ── Picker modal: show loader while animation plays ───────────────────────
    $('#pickerModal').on('show.bs.modal', function () {
        $('#picker_loader').show();
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

            var el = $searchBox[0];
            if (el) {
                el.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        e.stopImmediatePropagation();
                        _pickerShouldAdvance = false;
                        _pickerSelected      = false;
                        $('#pickerModal').modal('hide');
                        return;
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
            if (_pickerType === 'challans') {
                _pickerTarget.val(data[0].challanNo).attr('data-picked-id', data[0].challanNo);
                var $row = _pickerTarget.closest('tr');
                var $vehicleInput = $row.find('.row-expense-account');
                if (!$vehicleInput.val() && data[0].vehicleId) {
                    $vehicleInput.val(data[0].vehicleName).attr('data-picked-id', String(data[0].vehicleId));
                }
            } else {
                _pickerTarget
                    .val(data[0].text)
                    .attr('data-picked-id', String(data[0].id));

                var $row = _pickerTarget.closest('tr');
                var challanVal = ($row.find('.row-challan-no').val() || '').trim();
                var $remarkInput = $row.find('.row-remark');

                if (!challanVal) {
                    var currentRemark = $remarkInput.val().trim();
                    var vName = data[0].text;
                    if (!currentRemark) {
                        $remarkInput.val(vName);
                    } else if (currentRemark.indexOf(vName) === -1) {
                        $remarkInput.val(currentRemark + ' | ' + vName);
                    }
                }
            }
        }
        $('#pickerModal').modal('hide');
    });

    // ── Picker modal: cleanup after fully hidden ───────────────────────────────
    $('#pickerModal').on('hidden.bs.modal', function () {
        var $target      = _pickerTarget;
        var shouldAdvance = _pickerShouldAdvance;

        if ($('#picker_select').data('select2')) {
            $('#picker_select').select2('destroy');
        }

        _pickerTarget        = null;
        _pickerSelected      = false;
        _pickerShouldAdvance = false;
        _pickerInitialSearch = '';

        if (shouldAdvance && $target) {
            rowAdvance($target);
        } else if ($target) {
            $target.focus();
        }
    });

    // ── Table Totals Recalculation ────────────────────────────────────────────
    $(document).on('input', '.row-amount', function () {
        recalcTableTotals();
    });

    // ── Add Row modal ─────────────────────────────────────────────────────────
    $('#addRowModal').on('show.bs.modal', function () {
        $('#add_row_after').val($('#multi_expense_table_body tr').length);
        $('#add_row_count').val(5);
    });

    $('#add_row_confirm_btn').on('click', function () {
        var afterSerial = parseInt($('#add_row_after').val()) || 0;
        var count       = parseInt($('#add_row_count').val()) || 1;

        if (count < 1 || count > 100) {
            showToast('error', 'Row count must be between 1 and 100.');
            return;
        }

        var $tbody      = $('#multi_expense_table_body');
        var totalBefore = $tbody.find('tr').length;
        var $newRows    = $();

        for (var i = 0; i < count; i++) {
            $newRows = $newRows.add(buildNewRow());
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
    });

    // ── Form submit ───────────────────────────────────────────────────────────
    $('#multi_expense_form').on('submit', function (e) {
        e.preventDefault();

        var voucherDate      = $('#voucher_date').val();
        var accountId        = $('#account_id').val();
        var expenseAccountId = $('#expense_account_id').val();

        if (!voucherDate)      { showToast('error', 'Voucher Date is required.');        return; }
        if (!accountId)        { showToast('error', 'Please select Account Name (Cr).'); return; }
        if (!expenseAccountId) { showToast('error', 'Please select Expense Account (Dr).'); return; }

        var rowError = null;
        var seenBillNos = {};
        $('#multi_expense_table_body tr').each(function (idx) {
            if (rowError) return false;
            var $row = $(this);
            var challanNo = ($row.find('.row-challan-no').val() || '').trim();
            var billNo   = ($row.find('.row-bill-no').val() || '').trim();
            var date     = ($row.find('.row-date').val() || '').trim();
            var vehicleId = $row.find('.row-expense-account').attr('data-picked-id');
            var amount   = parseFloat($row.find('.row-amount').val()) || 0;
            var remark   = ($row.find('.row-remark').val() || '').trim();

            var isDirty = challanNo || billNo || date || vehicleId || amount || remark;
            if (!isDirty) return;

            var sr = idx + 1;
            if (!vehicleId) { rowError = 'Row ' + sr + ': Vehicle is required.'; return false; }
            if (!amount)    { rowError = 'Row ' + sr + ': Amount is required.';  return false; }

            if (billNo) {
                var key = billNo.toLowerCase();
                if (seenBillNos[key]) {
                    rowError = 'Row ' + sr + ': Bill No "' + billNo + '" is already used in row ' + seenBillNos[key] + '.';
                    return false;
                }
                seenBillNos[key] = sr;
            }
        });
        if (rowError) {
            checkDuplicateBillNos();
            showToast('error', rowError);
            return;
        }

        var items = collectRows();
        if (items.length === 0) {
            showToast('error', 'Please enter at least one item row.');
            return;
        }

        var payload = {
            voucher_date:       dmyToYmd(voucherDate),
            day_for:            $('#day_for').val(),
            account_id:         accountId,
            expense_account_id: expenseAccountId,
            narration:          $('#narration').val(),
            items:              items,
            _token:             $('meta[name="csrf-token"]').attr('content'),
        };

        function doUpdate() {
            $('#save_btn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Updating...');

            $.ajax({
                url:         updateMultiExpenseVoucherUrl,
                type:        'PUT',
                data:        JSON.stringify(payload),
                contentType: 'application/json',
                success: function (res) {
                    Swal.fire({
                        icon:              'success',
                        title:             'Updated!',
                        html: 'Multi Expense Voucher updated successfully.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#0d6efd',
                    }).then(function () {
                        history.go(-1);
                    });
                },
                error: function (xhr) {
                    var json  = xhr.responseJSON || {};
                    var title = 'Update Failed';
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
                    $('#save_btn').prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Update');
                }
            });
        }

        doUpdate();
    });

});

// ── Pre-fill form with existing voucher data ──────────────────────────────────
function prefillForm() {
    var e = window.editExpense;
    if (!e) return;

    $('#account_id').trigger('change');

    $('#day_for').val(e.day_for || '');

    var $tbody = $('#multi_expense_table_body');
    $tbody.empty();

    var items = e.items || [];
    items.forEach(function (item) {
        var $tr = buildNewRow();

        if (item.id) $tr.attr('data-item-id', item.id);
        if (item.bill_no) $tr.find('.row-bill-no').val(item.bill_no);
        if (item.bill_date) $tr.find('.row-date').val(item.bill_date);
        if (item.vehicle_id && item.vehicle_name) {
            $tr.find('.row-expense-account')
                .val(item.vehicle_name)
                .attr('data-picked-id', String(item.vehicle_id));
        }
        if (item.amount) $tr.find('.row-amount').val(item.amount);
        if (item.challan_number) {
            $tr.find('.row-challan-no').val(item.challan_number);
            $tr.find('.row-expense-account').prop('readonly', true).css('pointer-events', 'none');
            $tr.find('.row-amount').prop('readonly', true);
        }
        if (item.remark) $tr.find('.row-remark').val(item.remark);

        $tbody.append($tr);
    });

    // Pad with blank rows so the table defaults to 250 rows (same as create),
    // but never truncate — if there are already more items than that, no padding is added.
    var DEFAULT_TOTAL_ROWS = 250;
    var blankRowsNeeded = Math.max(DEFAULT_TOTAL_ROWS - items.length, 0);
    for (var i = 0; i < blankRowsNeeded; i++) { $tbody.append(buildNewRow()); }

    renumberRows();
    recalcTableTotals();

    // Apply DateInput to newly created rows
    new DateInput('.row-date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
}

function bindSelect2() {
    var selectIdArray = ['#account_id', '#expense_account_id'];

    selectIdArray.forEach(function (element) {
        $(element).select2({
            theme: 'bootstrap-5',
            allowClear: true,
            placeholder: 'Select ...',
        });
    });

    $(document).on('select2:open', function (e) {
        var selectElement = $(e.target);
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

// ── Picker state ──────────────────────────────────────────────────────────────
var _pickerTarget        = null;
var _pickerUrl           = null;
var _pickerSelected      = false;
var _pickerShouldAdvance = false;
var _pickerInitialSearch = '';

// ── Picker master-data preload (in-memory search, no per-keystroke AJAX) ──────
var _pickerType         = null;    // 'vehicles'
var _pickerMasterData   = { vehicles: null };
var _pickerDataRequests = { vehicles: null };

// ── Picker ────────────────────────────────────────────────────────────────────

function openPicker($el, initialSearch) {
    if (_pickerTarget !== null) return;

    var type = $el.data('picker');
    var url  = null;
    if (type === 'vehicles') {
        url = masterRoutes.vehicles;
    } else if (type === 'challans') {
        url = pendingChallansUrl;
    }

    if (!url) {
        showToast('error', 'Unknown picker type.');
        return;
    }

    _pickerTarget        = $el;
    _pickerUrl           = url;
    _pickerType          = type;
    _pickerSelected      = false;
    _pickerInitialSearch = initialSearch || '';

    var titles = { vehicles: 'Vehicles', challans: 'Challans' };
    $('#picker_title').text(titles[type] || 'Select');
    $('#pickerModal').modal('show');
}

// ── Preload full vehicle list once so the picker modal can filter in memory ───
// instead of hitting the backend on every keystroke.

function preloadPickerMasterData() {
    _pickerDataRequests.vehicles = $.ajax({ url: masterRoutes.allVehicles, method: 'GET', dataType: 'json' })
        .done(function (res) {
            _pickerMasterData.vehicles = (res.data || []).map(function (d) {
                return { id: d.id, text: d.name };
            });
        });
}

// ── Preload full vehicle list once so the picker modal can filter in memory ───
// instead of hitting the backend on every keystroke.

function preloadPickerMasterData() {
    _pickerDataRequests.vehicles = $.ajax({ url: masterRoutes.allVehicles, method: 'GET', dataType: 'json' })
        .done(function (res) {
            _pickerMasterData.vehicles = (res.data || []).map(function (d) {
                return { id: d.id, text: d.name };
            });
        });
}

function highlightFirstPickerResult() {
    $('#pickerModal .select2-results__option[aria-selected]:not(.select2-results__option--disabled)')
        .first()
        .trigger('mouseenter');
}

function arrowFocus($el) {
    $el.trigger('focus');
    setTimeout(function () {
        var el = $el[0];
        if (el && typeof el.select === 'function') el.select();
    }, 0);
}

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

    var $nextRow = $row.next('tr');
    if ($nextRow.length) {
        var $first = $nextRow.find(ROW_FIELD_ORDER[0]);
        if ($first.length) $first.focus();
    }
}

function buildNewRow() {
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
        $('<input type="text" class="form-control row-challan-no" placeholder="">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control row-bill-no" placeholder="">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control date-format row-date" placeholder="DD-MM-YYYY">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control row-picker row-expense-account" data-picker="vehicles" placeholder="">')
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
    $('#multi_expense_table_body tr').each(function (index) {
        $(this).find('.row-serial').text(index + 1);
    });
}

function recalcTableTotals() {
    var totalAmount = 0;
    $('#multi_expense_table_body tr').each(function () {
        totalAmount += parseFloat($(this).find('.row-amount').val()) || 0;
    });
    $('#total_amount').val(totalAmount.toFixed(2));
}

// ── Bill No: mark any row whose Bill No repeats an earlier row ────────────────
function checkDuplicateBillNos() {
    var seen = {};
    var duplicateFound = false;

    $('#multi_expense_table_body tr').each(function () {
        var $input = $(this).find('.row-bill-no');
        var billNo = ($input.val() || '').trim();
        $input.removeClass('is-invalid');
        if (!billNo) return;

        var key = billNo.toLowerCase();
        if (seen[key]) {
            $input.addClass('is-invalid');
            seen[key].addClass('is-invalid');
            duplicateFound = true;
        } else {
            seen[key] = $input;
        }
    });

    return duplicateFound;
}

function collectRows() {
    var rows = [];
    $('#multi_expense_table_body tr').each(function () {
        var $row = $(this);
        var challanNo = ($row.find('.row-challan-no').val() || '').trim();
        var billNo   = ($row.find('.row-bill-no').val() || '').trim();
        var date     = ($row.find('.row-date').val() || '').trim();
        var vehicleId = $row.find('.row-expense-account').attr('data-picked-id');
        var amount   = parseFloat($row.find('.row-amount').val()) || 0;
        var remark   = ($row.find('.row-remark').val() || '').trim();

        var isDirty = challanNo || billNo || date || vehicleId || amount || remark;
        if (!isDirty) return;

        var itemId = $row.attr('data-item-id') || null;

        rows.push({
            id: itemId,
            bill_no:    billNo,
            bill_date:  date ? dmyToYmd(date) : null,
            vehicle_id: vehicleId || null,
            amount:     amount,
            remark:     remark,
            challan_number: challanNo || null,
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
