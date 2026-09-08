/* Salary Voucher – edit.js
 * Reuses all interaction logic from create.js.
 * After voucher data loads, pre-fills the form from window.editExpense.
 * Submits a PUT request to updateSalaryModuleVoucherUrl.
 */

/* global DateInput */

// Explicit column order for Enter-key row navigation
var ROW_FIELD_ORDER = [
    '.row-bill-no', '.row-account', '.row-expense-account', '.row-amount', '.row-remark'
];

$(function () {

    bindSelect2();
    preloadPickerMasterData();

    // ── Fetch voucher data ────────────────────────────────────────────────────
    $.ajax({ url: getSalaryModuleVoucherDataUrl, dataType: 'json' })
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

    // Prefix Updater moved to global scope

    $('#month').on('change', function() {
        updateRowPrefixes();
        var monthStr = $(this).val();
        // if (monthStr) {
        //     $('#narration').val('Being salary for the month of ' + monthStr);
        // } else {
        //     $('#narration').val('');
        // }
    });
    // Note: Initial prepopulation happens after prefillForm

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





    // ── Table Enter: advance to next cell ─────────────────────────────────────
    $('#salary_module_table_body').on('keydown', 'input', function (e) {
        if (e.which !== 13) return;
        e.preventDefault();
        e.stopPropagation();
        rowAdvance($(this));
    });

    // ── Table arrow-key navigation ────────────────────────────────────────────
    $('#salary_module_table_body').on('keydown', 'input', function (e) {
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
    $('#salary_module_table_body').on('keydown', '.row-picker', function (e) {
        if (e.key !== 'Backspace' && e.key !== 'Delete') return;
        e.preventDefault();
        $(this).val('').removeAttr('data-picked-id');
    });

    // ── Picker trigger: printable key in picker cell → open modal ────────────
    $('#salary_module_table_body').on('keydown', '.row-picker', function (e) {
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
    $('#salary_module_table_body').on('click', '.row-picker', function () {
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
                url:      _pickerUrl,
                dataType: 'json',
                delay:    250,
                data: function (params) {
                    return { q: params.term || '' };
                },
                processResults: function (res) {
                    return {
                        results: (res.data || []).map(function (d) {
                            return { id: d.id, text: d.name };
                        })
                    };
                },
                complete: function () {
                    setTimeout(highlightFirstPickerResult, 30);
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
            _pickerTarget
                .val(data[0].text)
                .attr('data-picked-id', String(data[0].id));
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
        $('#add_row_after').val($('#salary_module_table_body tr').length);
        $('#add_row_count').val(5);
    });

    $('#add_row_confirm_btn').on('click', function () {
        var afterSerial = parseInt($('#add_row_after').val()) || 0;
        var count       = parseInt($('#add_row_count').val()) || 1;

        if (count < 1 || count > 100) {
            showToast('error', 'Row count must be between 1 and 100.');
            return;
        }

        var $tbody      = $('#salary_module_table_body');
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
    $('#salary_module_form').on('submit', function (e) {
        e.preventDefault();

        var voucherDate      = $('#voucher_date').val();
        var month            = $('#month').val();
        var expenseAccountId = $('#expense_account_id').val();

        if (!voucherDate)      { showToast('error', 'Voucher Date is required.');        return; }
        if (!month)            { showToast('error', 'Please select Month.');             return; }
        if (!expenseAccountId) { showToast('error', 'Please select Expense Account (Dr).'); return; }

        var rowError = null;
        var seenAccounts = {};
        var monthStr = $('#month').val();
        var shortMonth = monthStr ? monthStr.substring(0, 3).toUpperCase() : '';
        var prefix = shortMonth ? 'SAL-' + shortMonth : 'SAL-';

        $('#salary_module_table_body tr').each(function (idx) {
            if (rowError) return false;
            var $row = $(this);
            var billNo   = ($row.find('.row-bill-no').val() || '').trim();

            var accountId = $row.find('.row-account').attr('data-picked-id');
            var vehicleId = $row.find('.row-expense-account').attr('data-picked-id');
            var amount   = parseFloat($row.find('.row-amount').val()) || 0;
            var remark   = ($row.find('.row-remark').val() || '').trim();

            var isDirty = (billNo && billNo !== prefix) || accountId || vehicleId || amount || remark;
            if (!isDirty) return;

            var sr = idx + 1;
            if (!accountId) { rowError = 'Row ' + sr + ': Account Name(Cr) is required.'; return false; }
            if (!vehicleId) { rowError = 'Row ' + sr + ': Vehicle is required.'; return false; }
            if (!amount)    { rowError = 'Row ' + sr + ': Amount is required.';  return false; }

            if (accountId) {
                if (seenAccounts[accountId]) {
                    rowError = 'Row ' + sr + ': Same account name not valid in same month (also in row ' + seenAccounts[accountId] + ').';
                    return false;
                }
                seenAccounts[accountId] = sr;
            }
        });
        if (rowError) {
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
            month:              month,
            expense_account_id: expenseAccountId,
            narration:          $('#narration').val(),
            items:              items,
            _token:             $('meta[name="csrf-token"]').attr('content'),
        };

        function doUpdate() {
            $('#save_btn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Updating...');

            $.ajax({
                url:         updateSalaryModuleVoucherUrl,
                type:        'PUT',
                data:        JSON.stringify(payload),
                contentType: 'application/json',
                success: function (res) {
                    Swal.fire({
                        icon:              'success',
                        title:             'Updated!',
                        html:              'Salary Voucher updated successfully.<br>' +
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

    $('#day_for').val(e.day_for || '');

    if (e.month) {
        $('#month').val(e.month).trigger('change');
    }

    var $tbody = $('#salary_module_table_body');
    $tbody.empty();

    var items = e.items || [];
    items.forEach(function (item) {
        var $tr = buildNewRow();

        if (item.bill_no) $tr.find('.row-bill-no').val(item.bill_no);
        if (item.account_id && item.account_name) {
            $tr.find('.row-account')
                .val(item.account_name)
                .attr('data-picked-id', String(item.account_id));
        }
        if (item.vehicle_id && item.vehicle_name) {
            $tr.find('.row-expense-account')
                .val(item.vehicle_name)
                .attr('data-picked-id', String(item.vehicle_id));
        }
        if (item.amount) $tr.find('.row-amount').val(item.amount);
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

    // Trigger prefix update to ensure all empty rows have proper SAL prefix
    updateRowPrefixes();
}

function bindSelect2() {
    var selectIdArray = ['#month', '#expense_account_id'];

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
var _pickerMasterData   = { vehicles: null, accounts: null };
var _pickerDataRequests = { vehicles: null, accounts: null };

// ── Picker ────────────────────────────────────────────────────────────────────

function openPicker($el, initialSearch) {
    if (_pickerTarget !== null) return;

    var type = $el.data('picker');
    var url  = type === 'vehicles' ? masterRoutes.vehicles : (type === 'accounts' ? masterRoutes.accounts : null);

    if (!url) {
        showToast('error', 'Unknown picker type.');
        return;
    }

    _pickerTarget        = $el;
    _pickerUrl           = url;
    _pickerSelected      = false;
    _pickerInitialSearch = initialSearch || '';

    var titles = { vehicles: 'Vehicles', accounts: 'Account Name' };
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
    _pickerDataRequests.accounts = $.ajax({ url: masterRoutes.preLoadLedgers, method: 'GET', dataType: 'json' })
        .done(function (res) {
            _pickerMasterData.accounts = (res.data || []).map(function (d) {
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
    var monthStr = $('#month').val();
    var shortMonth = monthStr ? monthStr.substring(0, 3).toUpperCase() : '';
    var prefix = shortMonth ? 'SAL-' + shortMonth : 'SAL-';
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control row-bill-no" placeholder="">').val(prefix)
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control row-picker row-account" data-picker="accounts" placeholder="">')
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
    $('#salary_module_table_body tr').each(function (index) {
        $(this).find('.row-serial').text(index + 1);
    });
}

function recalcTableTotals() {
    var totalAmount = 0;
    $('#salary_module_table_body tr').each(function () {
        totalAmount += parseFloat($(this).find('.row-amount').val()) || 0;
    });
    $('#total_amount').val(totalAmount.toFixed(2));
}



function collectRows() {
    var rows = [];
    var monthStr = $('#month').val();
    var shortMonth = monthStr ? monthStr.substring(0, 3).toUpperCase() : '';
    var prefix = shortMonth ? 'SAL-' + shortMonth : 'SAL-';

    $('#salary_module_table_body tr').each(function () {
        var $row = $(this);
        var billNo   = ($row.find('.row-bill-no').val() || '').trim();
        var accountId = $row.find('.row-account').attr('data-picked-id');
        var vehicleId = $row.find('.row-expense-account').attr('data-picked-id');
        var amount   = parseFloat($row.find('.row-amount').val()) || 0;
        var remark   = ($row.find('.row-remark').val() || '').trim();

        var isDirty = (billNo && billNo !== prefix) || accountId || vehicleId || amount || remark;
        if (!isDirty) return;

        rows.push({
            bill_no:    billNo,
            account_id: accountId || null,
            vehicle_id: vehicleId || null,
            amount:     amount,
            remark:     remark,
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

// ── Prefix Updater ────────────────────────────────────────────────────────
function updateRowPrefixes() {
    var monthStr = $('#month').val();
    var shortMonth = monthStr ? monthStr.substring(0, 3).toUpperCase() : '';
    var prefix = shortMonth ? 'SAL-' + shortMonth : 'SAL-';
    
    $('.row-bill-no').each(function() {
        var val = $(this).val().trim();
        var currentPrefixMatch = val.match(/^SAL-[a-zA-Z]*-?/);
        if (currentPrefixMatch) {
            $(this).val(val.replace(currentPrefixMatch[0], prefix));
        } else if (val) {
            $(this).val(prefix + val);
        } else {
            $(this).val(prefix);
        }
    });
}
