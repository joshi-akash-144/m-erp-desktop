/* global DateInput */

// ── Picker state ──────────────────────────────────────────────────────────────
var _pickerTarget        = null;   // input cell that opened the picker
var _pickerType          = null;   // 'vehicles' or 'drivers'
var _pickerSelected      = false;  // did user confirm a selection?
var _pickerShouldAdvance = false;  // should move to next cell (selected OR bypassed)
var _pickerInitialSearch = '';     // all characters typed before modal was fully open

// Explicit column order for Enter-key row navigation
var ROW_FIELD_ORDER = [
    '.row-challan_number', '.row-vehicle', '.row-driver', '.row-last-date',
    '.row-today', '.row-rate', '.row-diesel', '.row-old-km', '.row-new-km',
    '.row-amount', '.row-remark'
];

$(function () {
    bindSelect2();

    $('#master_loader').hide();
    $('#voucher_date').trigger('focus');

    // ── Date pickers ──────────────────────────────────────────────────────────
    new DateInput('#voucher_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('.row-last-date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('.row-today',     FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);


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
        rowAdvance($(this));
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
                if ($n.length && !$n.prop('disabled') && $n.attr('tabindex') !== '-1') { arrowFocus($n); return; }
            }
        } else if (goLeft) {
            for (var j = curIdx - 1; j >= 0; j--) {
                var $n = $row.find(ROW_FIELD_ORDER[j]);
                if ($n.length && !$n.prop('disabled') && $n.attr('tabindex') !== '-1') { arrowFocus($n); return; }
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
    $('#diesel_table_body').on('keydown', '.row-picker', function (e) {
        if (e.key !== 'Backspace' && e.key !== 'Delete') return;
        e.preventDefault();
        $(this).val('').removeAttr('data-picked-id');
    });

    // ── Picker trigger: printable key in picker cell → open modal ────────────
    $('#diesel_table_body').on('keydown', '.row-picker', function (e) {
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
    $('#diesel_table_body').on('click', '.row-picker', function () {
        openPicker($(this), '');
    });

    // ── Picker modal: show loader while animation plays ───────────────────────
    $('#pickerModal').on('show.bs.modal', function () {
        $('#picker_loader').show();
        $('#pickerModal .select2-container').remove();
    });

    // ── Picker modal: initialize Select2 once fully shown ──────────
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
                    setTimeout(highlightFirstPickerResult, 0);
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

            if (_pickerType === 'vehicles') {
                var $row = _pickerTarget.closest('tr');
                var vehicleId = data[0].id;
                
                var $lastDateCell = $row.find('.row-last-date');
                var $oldKmCell    = $row.find('.row-old-km');

                // Show spinner in cell
                $lastDateCell.hide().after('<div class="cell-spinner text-center text-secondary pt-1"><i class="fa-solid fa-spinner fa-spin"></i></div>');
                $oldKmCell.hide().after('<div class="cell-spinner text-center text-secondary pt-1"><i class="fa-solid fa-spinner fa-spin"></i></div>');

                $.ajax({
                    url: getVehicleLatestDataUrl,
                    type: 'GET',
                    data: { vehicle_id: vehicleId },
                    success: function (res) {
                        $row.find('.cell-spinner').remove();
                        $lastDateCell.show();
                        $oldKmCell.show();

                        if (res.success && res.data) {
                            if (res.data.last_date) {
                                $lastDateCell.val(res.data.last_date).attr('readonly', true).attr('tabindex', '-1');
                            } else {
                                $lastDateCell.val('').removeAttr('readonly').removeAttr('tabindex');
                            }
                            
                            if (res.data.old_km !== null && res.data.old_km !== undefined) {
                                $oldKmCell.val(res.data.old_km).attr('readonly', true).attr('tabindex', '-1');
                            } else {
                                $oldKmCell.val('0').removeAttr('readonly').removeAttr('tabindex');
                            }
                            
                            recalcRow($row);
                            recalcTableTotals();
                        } else {
                            $lastDateCell.val('').removeAttr('readonly').removeAttr('tabindex');
                            $oldKmCell.val('0').removeAttr('readonly').removeAttr('tabindex');
                            recalcRow($row);
                            recalcTableTotals();
                        }
                    },
                    error: function() {
                        $row.find('.cell-spinner').remove();
                        $lastDateCell.show();
                        $oldKmCell.show();
                        
                        $lastDateCell.val('').removeAttr('readonly').removeAttr('tabindex');
                        $oldKmCell.val('0').removeAttr('readonly').removeAttr('tabindex');
                        recalcRow($row);
                        recalcTableTotals();
                    }
                });
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
            rowAdvance($target);    // selected or bypassed → move to next cell
        } else if ($target) {
            $target.focus();        // cancelled (Escape/X) → return focus to cell
        }
    });

    // ── Table Totals Recalculation ────────────────────────────────────────────
    $(document).on('input', '.row-diesel, .row-rate, .row-old-km, .row-new-km, .row-amount', function () {
        recalcRow($(this).closest('tr'));
        recalcTableTotals();
    });

    $('#diesel_rate').on('input', function () {
        var val = $(this).val();
        val = val.replace(/[^0-9.]/g, '');
        var parts = val.split('.');
        if (parts.length > 2) {
            val = parts[0] + '.' + parts.slice(1).join('');
        }
        $(this).val(val);

        $('#diesel_table_body tr').each(function () {
            recalcRow($(this));
        });
        recalcTableTotals();
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
    $('#diesel_form').on('submit', function (e) {
        e.preventDefault();

        var voucherDate = $('#voucher_date').val();
        var accountId   = $('#account_id').val();
        var dieselRate  = $('#diesel_rate').val();

        if (!voucherDate) { showToast('error', 'Voucher Date is required.'); return; }
        if (!accountId)   { showToast('error', 'Please select a Diesel Account.'); return; }

        var rowError = null;
        var items = [];
        var seenChallans = new Set();
        
        $('#diesel_table_body tr').each(function (idx) {
            if (rowError) return false;
            var $row = $(this);
            
            var challan = ($row.find('.row-challan_number').val() || '').trim();
            var vehicleId = $row.find('.row-vehicle').attr('data-picked-id');
            var driverId  = $row.find('.row-driver').attr('data-picked-id');
            var lastDate = ($row.find('.row-last-date').val() || '').trim();
            var todayDate = ($row.find('.row-today').val() || '').trim();
            var rate = parseFloat($row.find('.row-rate').val()) || 0;
            var diesel = parseFloat($row.find('.row-diesel').val()) || 0;
            var oldKm  = parseFloat($row.find('.row-old-km').val()) || 0;
            var newKm  = parseFloat($row.find('.row-new-km').val()) || 0;
            var amount = parseFloat($row.find('.row-amount').val()) || 0;
            var diff   = parseFloat($row.find('.row-diff').val()) || 0;
            var average= parseFloat($row.find('.row-average').val()) || 0;
            var remark = ($row.find('.row-remark').val() || '').trim();

            var isDirty = challan || vehicleId || driverId || lastDate || todayDate || rate || diesel || oldKm || newKm || amount || remark;
            if (!isDirty) return;

            var oldKmRaw = ($row.find('.row-old-km').val() || '').trim();
            var newKmRaw = ($row.find('.row-new-km').val() || '').trim();
            var dieselRaw = ($row.find('.row-diesel').val() || '').trim();
            var amountRaw = ($row.find('.row-amount').val() || '').trim();

            var sr = idx + 1;
            if (!challan) { rowError = 'Row ' + sr + ': Challan No is required.'; return false; }
            if (seenChallans.has(challan)) { rowError = 'Row ' + sr + ': Challan No ' + challan + ' is duplicated.'; return false; }
            seenChallans.add(challan);
            
            if (!vehicleId) { rowError = 'Row ' + sr + ': Vehicle is required.'; return false; }
            if (!driverId) { rowError = 'Row ' + sr + ': Driver is required.'; return false; }
            if (!lastDate) { rowError = 'Row ' + sr + ': Last Date is required.'; return false; }
            if (!todayDate) { rowError = 'Row ' + sr + ': Today Date is required.'; return false; }
            if (rate <= 0)  { rowError = 'Row ' + sr + ': Rate must be greater than zero.'; return false; }
            if (dieselRaw === '' || parseFloat(dieselRaw) <= 0) { rowError = 'Row ' + sr + ': Diesel must be greater than zero.'; return false; }
            // if (oldKmRaw === '') { rowError = 'Row ' + sr + ': Old K.M is required.'; return false; }
            // if (newKmRaw === '' || parseFloat(newKmRaw) <= 0) { rowError = 'Row ' + sr + ': New K.M must be greater than zero.'; return false; }
            if (amountRaw === '' || parseFloat(amountRaw) <= 0) { rowError = 'Row ' + sr + ': Amount must be greater than zero.'; return false; }

            items.push({
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

        if (rowError) {
            showToast('error', rowError);
            return;
        }
        
        if (items.length === 0) {
            showToast('error', 'Please enter at least one item row.');
            return;
        }

        var payload = {
            uuid: $('#uuid').val(),
            voucher_date: voucherDate,
            account_id: accountId,
            narration: $('#narration').val() || '',
            items: items,
            _token: $('meta[name="csrf-token"]').attr('content'),
        };

        function doSave() {
            $('#save_btn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

            $.ajax({
                url:         storeDieselUrl,
                type:        'POST',
                data:        JSON.stringify(payload),
                contentType: 'application/json',
                success: function (res) {
                    Swal.fire({
                        icon:              'success',
                        title:             'Saved!',
                        html:              res.message || 'Diesel entry saved successfully.',
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
        doSave();
    });
});

function bindSelect2() {
    $('#account_id').select2({
        theme: 'bootstrap-5',
        allowClear: true,
        placeholder: '--Select Account--',
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

function openPicker($el, initialSearch) {
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

function highlightFirstPickerResult() {
    $('#pickerModal .select2-results__option[aria-selected]:not(.select2-results__option--disabled)')
        .first().trigger('mouseenter');
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

function recalcRow($row) {
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

function recalcTableTotals() {
    var totalDiesel = 0, totalAmount = 0, noOfVehicles = 0;
    $('#diesel_table_body tr').each(function () {
        var v = parseFloat($(this).find('.row-diesel').val()) || 0;
        var a = parseFloat($(this).find('.row-amount').val()) || 0;
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

    new DateInput($tr.find('.row-last-date')[0], FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput($tr.find('.row-today')[0], FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    return $tr;
}

function renumberRows() {
    $('#diesel_table_body tr').each(function (index) {
        $(this).find('.row-serial').text(index + 1);
    });
}
