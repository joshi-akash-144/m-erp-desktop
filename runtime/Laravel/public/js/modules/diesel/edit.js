/* global DateInput */

// ── Picker state ──────────────────────────────────────────────────────────────
var _pickerTarget        = null;
var _pickerType          = null;
var _pickerSelected      = false;
var _pickerShouldAdvance = false;
var _pickerInitialSearch = '';

// Explicit column order for Enter-key row navigation
var ROW_FIELD_ORDER = [
    '.row-challan_number', '.row-vehicle', '.row-driver', '.row-last-date',
    '.row-today', '.row-rate', '.row-diesel', '.row-old-km', '.row-new-km',
    '.row-amount', '.row-remark'
];

$(function () {
    bindSelect2Edit();

    $('#master_loader').hide();
    $('#voucher_date').trigger('focus');

    // ── Date pickers ──────────────────────────────────────────────────────────
    new DateInput('#voucher_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('.row-last-date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('.row-today',     FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    // ── Recalc all pre-filled rows on load, then update totals ────────────────
    setTimeout(function () {
        $('#diesel_table_body tr').each(function () {
            recalcRowEdit($(this));
        });
        recalcTableTotalsEdit();
    }, 100);

    // Autofill today_date and previous rate on the specific row when challan number is entered
    $(document).on('blur', '.row-challan_number', function () {
        var vDate = $('#voucher_date').val();
        var challan = $(this).val().trim();
        if (vDate && challan) {
            var $row = $(this).closest('tr');
            
            var $today = $row.find('.row-today');
            if (!$today.val()) {
                $today.val(vDate);
            }

            var $rate = $row.find('.row-rate');
            if (!$rate.val()) {
                var prevRate = $row.prev('tr').find('.row-rate').val();
                if (prevRate) {
                    $rate.val(prevRate);
                    recalcRow($row);
                    recalcTableTotals();
                }
            }
        }
    });

    // ── Table Enter: advance to next cell ─────────────────────────────────────
    $('#diesel_table_body').on('keydown', 'input', function (e) {
        if (e.which !== 13) return;
        e.preventDefault();
        e.stopPropagation();
        rowAdvanceEdit($(this));
    });

    // ── Table arrow-key navigation ────────────────────────────────────────────
    $('#diesel_table_body').on('keydown', 'input', function (e) {
        var key = e.key;
        var el  = this;
        var goLeft  = key === 'ArrowLeft'  && el.selectionStart === 0 && el.selectionEnd === 0;
        var goRight = key === 'ArrowRight' && el.selectionStart === el.value.length && el.selectionEnd === el.value.length;
        var goUp    = key === 'ArrowUp';
        var goDown  = key === 'ArrowDown';

        if (!goLeft && !goRight && !goUp && !goDown) return;
        e.preventDefault(); e.stopPropagation();

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
                if ($n.length && !$n.prop('disabled') && $n.attr('tabindex') !== '-1') { arrowFocusEdit($n); return; }
            }
        } else if (goLeft) {
            for (var j = curIdx - 1; j >= 0; j--) {
                var $n = $row.find(ROW_FIELD_ORDER[j]);
                if ($n.length && !$n.prop('disabled') && $n.attr('tabindex') !== '-1') { arrowFocusEdit($n); return; }
            }
        } else if (goDown) {
            var $nextRow = $row.next('tr');
            if ($nextRow.length) {
                var $n = $nextRow.find(ROW_FIELD_ORDER[curIdx]);
                if ($n.length) arrowFocusEdit($n);
            }
        } else if (goUp) {
            var $prevRow = $row.prev('tr');
            if ($prevRow.length) {
                var $n = $prevRow.find(ROW_FIELD_ORDER[curIdx]);
                if ($n.length) arrowFocusEdit($n);
            }
        }
    });


    $(document).on('keydown', '.row-picker', function (e) {
        if (_pickerTarget !== null) return;
        if (e.which === 13 || e.which === 32) { e.preventDefault(); openPickerEdit($(this), ''); return; }
        var ch = String.fromCharCode(e.which);
        if (/^[a-zA-Z0-9]$/.test(ch)) openPickerEdit($(this), ch);
    });

    // ── Picker modal: pre-populate search ─────────────────────────────────────
    $('#pickerModal').on('show.bs.modal', function () {
        $('#picker_loader').show();
        $('#pickerModal .select2-container').remove();
    });

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
                    var term = (params.data.term || '').toLowerCase();
                    var dataset = window._pickerMasterData[_pickerType] || [];
                    var results = term
                        ? dataset.filter(function (d) { return d.text.toLowerCase().indexOf(term) !== -1; })
                        : dataset;
                    success({ results: results });
                    setTimeout(highlightFirstPickerResultEdit, 0);
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
                            .first().trigger('mouseup');
                    }
                }, true);
            }
        }, 0);
    });

    // ── Picker: select confirmed ───────────────────────────────────────────────
    $('#picker_select').on('select2:select', function (e) {
        var selected = e.params.data;
        if (!_pickerTarget) return;

        _pickerTarget.val(selected.text).attr('data-picked-id', selected.id);
        _pickerSelected      = true;
        _pickerShouldAdvance = true;

        recalcTableTotalsEdit();
        $('#pickerModal').modal('hide');
    });

    // ── Picker modal: cleanup after fully hidden ───────────────────────────────
    $('#pickerModal').on('hidden.bs.modal', function () {
        var $target       = _pickerTarget;
        var shouldAdvance = _pickerShouldAdvance;

        if ($('#picker_select').data('select2')) {
            $('#picker_select').select2('destroy');
        }

        _pickerTarget        = null;
        _pickerSelected      = false;
        _pickerShouldAdvance = false;
        _pickerInitialSearch = '';

        if (shouldAdvance && $target) {
            rowAdvanceEdit($target);
        } else if ($target) {
            $target.focus();
        }
    });

    // ── Table Totals Recalculation ────────────────────────────────────────────
    $(document).on('input', '.row-rate, .row-diesel, .row-old-km, .row-new-km, .row-amount', function () {
        recalcRowEdit($(this).closest('tr'));
        recalcTableTotalsEdit();
    });

    // ── Add Row modal ─────────────────────────────────────────────────────────
    $('#addRowModal').on('show.bs.modal', function () {
        $('#add_row_after').val($('#diesel_table_body tr').length);
        $('#add_row_count').val(5);
    });

    $('#add_row_confirm_btn').on('click', function () {
        var afterSerial = parseInt($('#add_row_after').val()) || 0;
        var count       = parseInt($('#add_row_count').val()) || 1;

        if (count < 1 || count > 100) {
            showToast('error', 'Row count must be between 1 and 100.');
            return;
        }

        var $tbody      = $('#diesel_table_body');
        var totalBefore = $tbody.find('tr').length;
        var $newRows    = $();

        for (var i = 0; i < count; i++) {
            $newRows = $newRows.add(buildNewRowEdit());
        }

        if (afterSerial <= 0 || afterSerial >= totalBefore) {
            $tbody.append($newRows);
        } else {
            $tbody.find('tr:nth-child(' + afterSerial + ')').after($newRows);
        }

        renumberRowsEdit();
        $('#addRowModal').modal('hide');
        showToast('success', count + ' row(s) added.');
    });

    // ── Delete row ────────────────────────────────────────────────────────────
    $(document).on('click', '.delete-row-btn', function () {
        $(this).closest('tr').remove();
        renumberRowsEdit();
        recalcTableTotalsEdit();
    });

    // ── Form submit (UPDATE) ──────────────────────────────────────────────────
    $('#diesel_form').on('submit', function (e) {
        e.preventDefault();

        var voucherDate = $('#voucher_date').val();
        var accountId   = $('#account_id').val();

        if (!voucherDate) { showToast('error', 'Voucher Date is required.'); return; }
        if (!accountId)   { showToast('error', 'Please select a Diesel Account.'); return; }

        var rowError = null;
        var items    = [];
        var seenChallans = new Set();

        $('#diesel_table_body tr').each(function (idx) {
            if (rowError) return false;
            var $row = $(this);

            var challan   = ($row.find('.row-challan_number').val() || '').trim();
            var vehicleId = $row.find('.row-vehicle').attr('data-picked-id');
            var driverId  = $row.find('.row-driver').attr('data-picked-id');
            var lastDate  = ($row.find('.row-last-date').val() || '').trim();
            var todayDate = ($row.find('.row-today').val() || '').trim();
            var rate      = parseFloat($row.find('.row-rate').val()) || 0;
            var diesel    = parseFloat($row.find('.row-diesel').val()) || 0;
            var oldKm     = parseFloat($row.find('.row-old-km').val()) || 0;
            var newKm     = parseFloat($row.find('.row-new-km').val()) || 0;
            var amount    = parseFloat($row.find('.row-amount').val()) || 0;
            var diff      = parseFloat($row.find('.row-diff').val()) || 0;
            var average   = parseFloat($row.find('.row-average').val()) || 0;
            var remark    = ($row.find('.row-remark').val() || '').trim();

            var detailId  = parseInt($row.attr('data-detail-id')) || null;

            // Skip closed rows — they are read-only and managed by the server
            if ($row.attr('data-is-closed') === '1') return;

            var isDirty = challan || vehicleId || driverId || lastDate || todayDate || rate || diesel || oldKm || newKm || amount || remark;
            if (!isDirty) return;

            var dieselRaw = ($row.find('.row-diesel').val() || '').trim();
            var amountRaw = ($row.find('.row-amount').val() || '').trim();

            var sr = idx + 1;
            if (!challan)   { rowError = 'Row ' + sr + ': Challan No is required.'; return false; }
            if (seenChallans.has(challan)) { rowError = 'Row ' + sr + ': Challan No ' + challan + ' is duplicated.'; return false; }
            seenChallans.add(challan);
            
            if (!vehicleId) { rowError = 'Row ' + sr + ': Vehicle is required.'; return false; }
            if (!driverId)  { rowError = 'Row ' + sr + ': Driver is required.'; return false; }
            if (!lastDate)  { rowError = 'Row ' + sr + ': Last Date is required.'; return false; }
            if (!todayDate) { rowError = 'Row ' + sr + ': Today Date is required.'; return false; }
            if (rate <= 0)  { rowError = 'Row ' + sr + ': Rate must be greater than zero.'; return false; }
            if (dieselRaw === '' || parseFloat(dieselRaw) <= 0) { rowError = 'Row ' + sr + ': Diesel must be greater than zero.'; return false; }
            if (amountRaw === '' || parseFloat(amountRaw) <= 0) { rowError = 'Row ' + sr + ': Amount must be greater than zero.'; return false; }

            items.push({
                id:             detailId,
                challan_number: challan,
                vehicle_id:     vehicleId || null,
                driver_id:      driverId || null,
                last_date:      lastDate || null,
                today_date:     todayDate || null,
                rate:           rate,
                diesel:         diesel,
                old_km:         oldKm,
                new_km:         newKm,
                amount:         amount,
                diff:           diff,
                average:        average,
                remark:         remark,
            });
        });

        if (rowError) { showToast('error', rowError); return; }
        if (items.length === 0) { showToast('error', 'Please enter at least one item row.'); return; }

        var payload = {
            voucher_date: voucherDate,
            account_id:   accountId,
            narration:    $('#narration').val() || '',
            items:        items,
            _token:       $('meta[name="csrf-token"]').attr('content'),
        };

        $('#save_btn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Updating...');

        $.ajax({
            url:         dieselUpdateUrl,
            type:        'PUT',
            data:        JSON.stringify(payload),
            contentType: 'application/json',
            success: function (res) {
                Swal.fire({
                    icon:               'success',
                    title:              'Updated!',
                    html:               res.message || 'Diesel entry updated successfully.',
                    confirmButtonText:  'OK',
                    confirmButtonColor: '#0d6efd',
                }).then(function () {
                    window.location.href = '/transports/diesel';
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
                    icon:               'error',
                    title:              title,
                    html:               body,
                    confirmButtonText:  'Close',
                    confirmButtonColor: '#dc3545',
                });
                $('#save_btn').prop('disabled', false).html('<i class="fa-solid fa-pencil me-1"></i> Update');
            }
        });
    });
});

// ─────────────────────────────────────────────────────────────────────────────
//  Helper Functions
// ─────────────────────────────────────────────────────────────────────────────

function bindSelect2Edit() {
    $('#account_id').select2({
        theme:       'bootstrap-5',
        allowClear:  true,
        placeholder: '--Select Account--',
    });
}

function openPickerEdit($el, initialSearch) {
    if (_pickerTarget !== null) return;

    var type = $el.data('picker');
    if (!type || !window._pickerMasterData[type]) {
        showToast('error', 'Unknown picker type.');
        return;
    }

    _pickerTarget        = $el;
    _pickerType          = type;
    _pickerSelected      = false;
    _pickerInitialSearch = initialSearch || '';

    var titles = { vehicles: 'Vehicles', drivers: 'Drivers' };
    $('#picker_title').text(titles[type] || 'Select');
    $('#pickerModal').modal('show');
}

function highlightFirstPickerResultEdit() {
    $('#pickerModal .select2-results__option[aria-selected]:not(.select2-results__option--disabled)')
        .first().trigger('mouseenter');
}

function arrowFocusEdit($el) {
    $el.trigger('focus');
    setTimeout(function () {
        var el = $el[0];
        if (el && typeof el.select === 'function') el.select();
    }, 0);
}

function rowAdvanceEdit($el) {
    var $row   = $el.closest('tr');
    var curIdx = -1;
    for (var i = 0; i < ROW_FIELD_ORDER.length; i++) {
        if ($el.is(ROW_FIELD_ORDER[i])) { curIdx = i; break; }
    }
    for (var j = curIdx + 1; j < ROW_FIELD_ORDER.length; j++) {
        var $next = $row.find(ROW_FIELD_ORDER[j]);
        if (!$next.length || $next.prop('disabled') || $next.attr('tabindex') === '-1') continue;
        $next.focus();
        return;
    }
    var $nextRow = $row.next('tr');
    if ($nextRow.length) {
        var $first = $nextRow.find(ROW_FIELD_ORDER[0]);
        if ($first.length) $first.focus();
    }
}

function recalcRowEdit($row) {
    var rate   = parseFloat($row.find('.row-rate').val()) || 0;
    var diesel = parseFloat($row.find('.row-diesel').val()) || 0;
    var oldKm  = parseFloat($row.find('.row-old-km').val()) || 0;
    var newKm  = parseFloat($row.find('.row-new-km').val()) || 0;

    var amount = rate * diesel;
    $row.find('.row-amount').val(amount > 0 ? amount.toFixed(2) : '');

    var diff = newKm - oldKm;
    $row.find('.row-diff').val(newKm > 0 && oldKm > 0 ? diff : '');

    var average = (diff > 0 && diesel > 0) ? (diff / diesel) : 0;
    $row.find('.row-average').val(average > 0 ? average.toFixed(2) : '');
}

function recalcTableTotalsEdit() {
    var totalDiesel = 0, totalAmount = 0, noOfVehicles = 0;
    $('#diesel_table_body tr').each(function () {
        var v       = parseFloat($(this).find('.row-diesel').val()) || 0;
        var a       = parseFloat($(this).find('.row-amount').val()) || 0;
        var vehicle = $(this).find('.row-vehicle').attr('data-picked-id');

        totalDiesel += v;
        totalAmount += a;
        if (vehicle) noOfVehicles++;
    });
    $('#total_diesel').val(totalDiesel.toFixed(2));
    $('#total_amount_footer').val(totalAmount.toFixed(2));
    $('#total_amount').val(totalAmount.toFixed(2));
    $('#no_of_vehicles').val(noOfVehicles);
}

function renumberRowsEdit() {
    $('#diesel_table_body tr').each(function (i) {
        $(this).attr('data-index', i).find('.row-serial').text(i + 1);
    });
}

function buildNewRowEdit() {
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
        $('<input type="text" class="form-control row-challan_number" placeholder="">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control row-picker row-vehicle" data-picker="vehicles" placeholder="Enter Vehicle">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control row-picker row-driver" data-picker="drivers" placeholder="Enter Driver">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control date-format row-last-date" placeholder="DD-MM-YYYY">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control date-format row-today" placeholder="DD-MM-YYYY">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control text-end row-rate only-number" placeholder="0.00">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control text-end row-diesel only-number" placeholder="0.00">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control text-end row-old-km only-number" value="0" placeholder="0">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control text-end row-new-km only-number" value="0" placeholder="0">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control text-end row-amount only-number" placeholder="0.00">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control text-end row-diff only-number" placeholder="0" readonly tabindex="-1">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control text-end row-average only-number" placeholder="0.00" readonly tabindex="-1">')
    ));
    $tr.append($('<td>').append(
        $('<input type="text" class="form-control row-remark" placeholder="">')
    ));

    return $tr;
}
