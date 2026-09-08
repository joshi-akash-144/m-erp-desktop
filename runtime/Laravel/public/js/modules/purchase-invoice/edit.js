$(document).ready(function () {

    $('#purchase_invoice_id').select2({
        theme: 'bootstrap-5',
    });

    $(document).on('select2:open', function (e) {
        var $select = $(e.target);
        var $search = $select.data('select2').$dropdown.find('.select2-search__field');
        $search.off('keydown.select2Enter').on('keydown.select2Enter', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                $select.select2('close');
                moveFocusToNextField($select);
            }
        });
    });

    // ── Load invoice data on selection ────────────────────────
    $(document).on('change', '#purchase_invoice_id', function () {
        var purchase_invoice_id = $(this).val();
        if (!purchase_invoice_id) return;

        $.ajax({
            url: editRoute.replace(':id', purchase_invoice_id),
            type: 'GET',
            beforeSend: function () { showLoader('Fetching Purchase Invoice…'); },
            success: function (response) {
                loadInvoiceData(response.data);
            },
            complete: function () { hideLoader(); }
        });
    });

    // Trigger initial load if ID is already selected
    var initialId = $('#purchase_invoice_id').val();
    if (initialId) {
        $('#purchase_invoice_id').trigger('change');
    }
});


// -------------------------------------------------------
// LOAD INVOICE DATA INTO FORM
// -------------------------------------------------------
function loadInvoiceData(data) {

    // ── Reset item table to clean state first ────────────────
    _resetItemTable();

    // ── Master / header fields ───────────────────────────────
    $('#invoice_date').val(data.invoice_date ? formatDateToDMY(data.invoice_date) : '');
    $('#file_number').val(data.file_number || '');
    $('#sales_invoice_serial').val(data.sales_invoice_serial || '');
    $('#reference_number').val(data.reference_number || '');
    $('#vehicle_number').val(data.vehicle_number || '');
    $('#remarks').val(data.remarks || '');
    $('#party_bill_date').val(data.party_bill_date ? formatDateToDMY(data.party_bill_date) : '');

    $('#account_id').val(data.account_id || '').trigger('change.select2');
    $('#account_id_city').val(data.account?.city || '');

    var gstTypeVal = data.account?.gst_type ?? '';
    var accountType = (gstTypeVal === GST_TYPE.INTERSTATE) ? 'INTERSTATE' : 'LOCAL';
    $('#account_id_type').val(accountType);

    $('#broker_id').val(data.broker_id || '').trigger('change.select2');
    $('#purchase_type_id').val(data.purchase_type_id || '').trigger('change.select2');

    // Purchase Type 
    let purchaseType = data.purchase_type;
    let cgst = purchaseType?.cgst ?? 0;
    let sgst = purchaseType?.sgst ?? 0;
    let igst = purchaseType?.igst ?? 0;

    getSupplierTurnOver(data.account_id, true);

    // ── GRN field ────────────────────────────────────────────
    if (data.grn_id) {
        // Ensure the option exists in #grn_id select (it may not be in the preloaded list)
        if ($('#grn_id option[value="' + data.grn_id + '"]').length === 0) {
            var label = data.grn_serial || data.grn_id;
            $('#grn_id').append(new Option(label, data.grn_id, true, true));
        }
        $('#grn_id').val(data.grn_id).trigger('change.select2');

        // ── GRN-linked: fill rows as locked (disabled) inputs ─
        if (data.details && data.details.length) {
            _fillItemRowsLocked(data.details, cgst, sgst, igst);
        }

        lockSelect2('#account_id');
        lockSelect2('#broker_id');
        $('#reference_number').prop('readonly', true)
        $('#vehicle_number').prop('readonly', true)
        // Show the Refetch GRN button (edit mode only)
        if (typeof _showRefetchBtn === 'function') _showRefetchBtn();

    } else {
        if($('#account_id').hasClass('select2-locked')){
           unLockSelect2('#account_id'); 
        }
        if($('#broker_id').hasClass('select2-locked')){
            unLockSelect2('#broker_id');
        }
        // ── No GRN: clear grn_id, fill rows as editable ──────
        $('#grn_id').val('').trigger('change.select2');
        if (typeof _hideRefetchBtn === 'function') _hideRefetchBtn();

        if (data.details && data.details.length) {
            _fillItemRowsEditable(data.details, cgst, sgst, igst);
        }

        $('#reference_number').prop('readonly', false)
        $('#vehicle_number').prop('readonly', false)
    }

    // ── Bill Sundry particulars ────────────────────────────────
    fillBillSundryData(data.bill_sundries);
}

// -------------------------------------------------------
// RESET item table back to a single clean row
// (clears any previously injected grn inputs / rows)
// -------------------------------------------------------
function _resetItemTable() {
    $('#item_table_body').html(originalTableHtml);

    $('#item_table_body .item-row').find('.item_id, .destination_id, .condition_id').select2({
        theme: 'bootstrap-5'
    });
}

// -------------------------------------------------------
// FILL ITEM ROWS — GRN-linked (locked / read-only)
// Exactly like applyGrnToForm but sourced from invoice data
// -------------------------------------------------------
function _fillItemRowsLocked(details, fallbackCgst, fallbackSgst, fallbackIgst) {
    fallbackCgst = fallbackCgst || 0;
    fallbackSgst = fallbackSgst || 0;
    fallbackIgst = fallbackIgst || 0;

    details.forEach(function (item, index) {
        var $row = $('#item_table_body').find('.item-row').eq(index);

        // If multi grn item detail populate it
        if (!$row.length) {
            var $firstRow = $('#item_table_body').find('.item-row').first();
            $row = $firstRow.clone();

            $row.find('.select2-container').remove();
            $row.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id');

            $row.find('input, select, textarea').each(function () {
                if (this.name) {
                    this.name = this.name.replace(/items\[\d+\]/, 'items[' + index + ']');
                }
            });

            $('#item_table_body').append($row);
            $row.find('.item_id, .destination_id, .condition_id').select2({ theme: 'bootstrap-5' });
        }

        $row.find('.item_id').val(item.item_id || '').trigger('change.select2');
        $row.find('.destination_id').val(item.destination_id || '').trigger('change.select2');
        $row.find('.condition_id').val(item.condition_id || '').trigger('change.select2');

        // GST rates — use saved per-row value, fall back to purchase type level
        var rowCgst = (item.cgst_rate !== null && item.cgst_rate !== undefined) ? item.cgst_rate : fallbackCgst;
        var rowSgst = (item.sgst_rate !== null && item.sgst_rate !== undefined) ? item.sgst_rate : fallbackSgst;
        var rowIgst = (item.igst_rate !== null && item.igst_rate !== undefined) ? item.igst_rate : fallbackIgst;
        $row.find('.cgst_rate').val(rowCgst);
        $row.find('.sgst_rate').val(rowSgst);
        $row.find('.igst_rate').val(rowIgst);

        // Numeric / text fields — fill and disable (GRN-locked)
        _setRowFieldDisabled($row, '.quantity', item.quantity);
        _setRowFieldDisabled($row, '.party_quantity', item.party_quantity);
        _setRowFieldDisabled($row, '.bag_count', item.bag_count);
        _setRowFieldDisabled($row, '.rate', item.rate);
        _setRowFieldDisabled($row, '.inclusive_rate', item.inclusive_rate);
        _setRowFieldDisabled($row, '.amount', item.amount);
        _setRowFieldDisabled($row, '.purchase_order_serial', item.purchase_order_serial);
        $row.find('.purchase_order_id').val(item.purchase_order_id || '');
        $row.find('.purchase_order_item_id').val(item.purchase_order_item_id || '');
        $row.find('.unit_name').val(
            (item.item && item.item.unit) ? item.item.unit.name : ''
        );
    });

    lockSelect2('.item_id');
    lockSelect2('.destination_id');
    lockSelect2('.condition_id');

    if (typeof updateItemTotal === 'function') updateItemTotal();
    if (typeof calculateTotalQty === 'function') calculateTotalQty();
}

// -------------------------------------------------------
// FILL ITEM ROWS — No GRN (editable via Select2)
// -------------------------------------------------------
function _fillItemRowsEditable(details, fallbackCgst, fallbackSgst, fallbackIgst) {
    fallbackCgst = fallbackCgst || 0;
    fallbackSgst = fallbackSgst || 0;
    fallbackIgst = fallbackIgst || 0;

    details.forEach(function (item, index) {
        var $row = $('#item_table_body').find('.item-row').eq(index);

         if (!$row.length) {
            var $firstRow = $('#item_table_body').find('.item-row').first();
            $row = $firstRow.clone();

            $row.find('.select2-container').remove();
            $row.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id');

            $row.find('input, select, textarea').each(function () {
                if (this.name) {
                    this.name = this.name.replace(/items\[\d+\]/, 'items[' + index + ']');
                }
            });

            $('#item_table_body').append($row);
            $row.find('.item_id, .destination_id, .condition_id').select2({ theme: 'bootstrap-5' });
        }

        // Set Select2 value (option must exist in the preloaded <select>)
        $row.find('.item_id').val(item.item_id || '').trigger('change.select2');
        $row.find('.destination_id').val(item.destination_id || '').trigger('change.select2');
        $row.find('.condition_id').val(item.condition_id || '').trigger('change.select2');

        // GST rates — use saved per-row value, fall back to purchase type level
        var rowCgst = (item.cgst_rate !== null && item.cgst_rate !== undefined) ? item.cgst_rate : fallbackCgst;
        var rowSgst = (item.sgst_rate !== null && item.sgst_rate !== undefined) ? item.sgst_rate : fallbackSgst;
        var rowIgst = (item.igst_rate !== null && item.igst_rate !== undefined) ? item.igst_rate : fallbackIgst;
        $row.find('.cgst_rate').val(rowCgst);
        $row.find('.sgst_rate').val(rowSgst);
        $row.find('.igst_rate').val(rowIgst);

        $row.find('.quantity').val(formatQty(item.quantity) || '');
        $row.find('.party_quantity').val(formatQty(item.party_quantity) || '');
        $row.find('.bag_count').val(item.bag_count || '');
        $row.find('.rate').val(item.rate || '');
        $row.find('.inclusive_rate').val(item.inclusive_rate || '');
        $row.find('.amount').val(item.amount || '');
        $row.find('.purchase_order_serial').val(item.purchase_order_serial || '');
        $row.find('.purchase_order_id').val(item.purchase_order_id || '');
        $row.find('.purchase_order_item_id').val(item.purchase_order_item_id || '');
        $row.find('.unit_name').val(
            (item.item && item.item.unit) ? item.item.unit.name : ''
        );
    });

    if (typeof updateItemTotal === 'function') updateItemTotal();
    if (typeof calculateTotalQty === 'function') calculateTotalQty();
}


const lockablePoFields = ['#account_id', '#broker_id', '.item_id', '.destination_id', '.condition_id'];

// -------------------------------------------------------
// LOCK account_id + purchase_type_id when SO is linked
// Does NOT trigger change (avoids unwanted account AJAX)
// -------------------------------------------------------
// function _lockPoFields() {
    
//     lockablePoFields.forEach(selector => {
//         lockSelect2(selector);
//     });

//     $('#purchase_type_id').addClass('non-selectable');
// }

// // -------------------------------------------------------
// // UNLOCK SO fields when a non-SO invoice is loaded
// // -------------------------------------------------------
// function _unlockPoFields() {
//     lockablePoFields.forEach(selector => {
//         unlockSelect2(selector);
//     });

//     $('#purchase_type_id').removeClass('non-selectable');
// }



// -------------------------------------------------------
// HELPER: set a field value and visually disable it
// (readonly + bg-light) — used for GRN-locked fields
// -------------------------------------------------------
function _setRowFieldDisabled($row, cls, value) {
    $row.find(cls)
        .val(value || '')
        .prop('readonly', true)
        .addClass('bg-light border-secondary-subtle');
}

function fillBillSundryData(billSundries) {
    if (!billSundries || !billSundries.length) return;

    // ── Remove any extra rows added by the user during the previous invoice ────
    // Deletable rows (user-added) have a .delete-particular-row button inside them.
    // Clicking it triggers the internal onDelete() which also cleans up BillSundry state.
    // Preloaded fixed rows do NOT have this button, so they stay untouched.
    $('#particular_table_body tr').each(function () {
        var $deleteBtn = $(this).find('.delete-particular-row');
        if ($deleteBtn.length) {
            $deleteBtn.trigger('click');
        }
    });


    // Sort ascending by sort_order before iterating
    var sorted = billSundries.slice().sort(function (a, b) {
        return (a.sort_order || 0) - (b.sort_order || 0);
    });


    // Separate rows that already exist in the DOM from rows that need to be created first
    var existingRows = [];
    var newRows = [];

    sorted.forEach(function (billSundry, index) {
        var rowId = (index + 1);
        if ($('#particular_row_' + rowId).length) {
            existingRows.push({ rowId: rowId, data: billSundry });
        } else {
            newRows.push({ rowId: rowId, data: billSundry });
        }
    });

    // ── Fill already-existing preloaded rows immediately ──────────────────────
    existingRows.forEach(function (entry) {
        _fillBillSundryRow(entry.rowId, entry.data);
    });

    // ── For user-added rows: trigger the add-row button (appends <tr> synchronously)
    //    then fill after the 20ms Select2 init settles ─────────────────────────
    if (newRows.length) {
        newRows.forEach(function (entry) {
            // The <tr> is appended synchronously inside onAddRow(); only Select2 init
            // is deferred via setTimeout(20). So clicking here makes the row available.
            $('#add_particular_row').trigger('click');
        });

        // After 30ms all Select2 instances are ready — fill the new rows
        setTimeout(function () {
            newRows.forEach(function (entry) {
                _fillBillSundryRow(entry.rowId, entry.data);
            });
            if (typeof BillSundry !== 'undefined') BillSundry.calculate();
        }, 30);
    } else {
        // Recalculate net/gross totals after all rows are populated
        if (typeof BillSundry !== 'undefined') BillSundry.calculate();
    }
}

// ── Internal helper: fill one bill-sundry row by rowId ───────────────────────
function _fillBillSundryRow(rowId, billSundry) {
    // ── Particular select ─────────────────────────────────
    // Use trigger('change') — NOT trigger('change.select2') — so that
    // onParticularChange() fires, which calls setMeta() to register this row
    // in BillSundry's internal state. Without this, getMeta(row) returns {}
    // and calculate() silently skips the row → wrong net_total on submission.
    var $select = $('#particular_id_' + rowId);
    if ($select.length) {
        $select.val(billSundry.sundry_id || '').trigger('change');
        // ^ onParticularChange runs synchronously: sets meta from master allSundries.
        // That overwrites any dr/cr data, so we restore it below.
    }

    // ── Restore saved DR / CR account into BillSundry meta ───────────────────
    // openModal() reads meta.selected_dr_id / selected_cr_id to pre-fill selects.
    // trigger('change') above set meta from master data (no dr/cr) — patch it back.
    if (typeof BillSundry !== 'undefined' && typeof BillSundry.setRowDrCr === 'function') {
        var drId = billSundry.bill_sundry_modal_dr_id || null;
        var drText = (billSundry.dr_account && billSundry.dr_account.name) ? billSundry.dr_account.name : '';
        var crId = billSundry.bill_sundry_modal_cr_id || null;
        var crText = (billSundry.cr_account && billSundry.cr_account.name) ? billSundry.cr_account.name : '';
        BillSundry.setRowDrCr(rowId, drId, drText, crId, crText);
    }

    // ── Percentage — override the default set by applyMetaToRow ──────────────
    var $pct = $('#particular_percentage_' + rowId);
    if ($pct.length && billSundry.rate_percent !== null && billSundry.rate_percent !== undefined) {
        if (billSundry.calculation_type == 'percentage') {
            var code = billSundry.code;
            if (code == '1002' || code == '1003' || code == '1004') {
                $pct.prop('disabled', true);
            } else {
                $pct.prop('disabled', false);
            }
        } else {
            $pct.prop('disabled', true);
        }
        billSundry.rate_percent == 0 ? $pct.val('') : $pct.val(parseFloat(billSundry.rate_percent).toFixed(DECIMALS.PERCENTAGE));
    }

    // ── Value — override the default set by applyMetaToRow ───────────────────
    var $val = $('#particular_value_' + rowId);
    if ($val.length && billSundry.value !== null && billSundry.value !== undefined) {
        billSundry.value == 0 ? $val.val('') : $val.val(parseFloat(billSundry.value).toFixed(DECIMALS.AMOUNT));
    }

    // For TDS: tag pct input with original saved values so edit-mode blur can detect changes
    if (billSundry.code == '1009') {
        $pct.data('original-pct', billSundry.rate_percent != null ? parseFloat(billSundry.rate_percent) : null);
        $pct.data('original-val', billSundry.value != null ? parseFloat(billSundry.value) : null);
    }

    setTimeout(function () {
        BillSundry.calculate();

        window.scrollTo(0, 0);
    }, 30);
}

