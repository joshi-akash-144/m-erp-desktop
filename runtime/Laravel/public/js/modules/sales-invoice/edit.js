// SALES INVOICE – EDIT
// Handles: invoice selection → AJAX fetch → form population

$(document).ready(function () {

    $('#sales_invoice_id').select2({ theme: 'bootstrap-5' });

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

    $(document).on('change', '#sales_invoice_id', function () {
        var id = $(this).val();
        if (!id) return;
        fetchInvoiceData(id);
    });

    // Trigger load if an invoice is pre-selected (e.g. URL parameter)
    var initialId = $('#sales_invoice_id').val();
    if (initialId) {
        $('#sales_invoice_id').trigger('change');
    }
});

// -------------------------------------------------------
// FETCH INVOICE DATA VIA AJAX
// -------------------------------------------------------
function fetchInvoiceData(id) {
    $.ajax({
        url: editSalesInvoiceRoute,
        type: 'GET',
        data: { sales_invoice_id: id },
        beforeSend: function () { showLoader('Fetching Sales Invoice…'); },
        success: function (response) {
            if (response.data) {
                loadInvoiceData(response.data);
            } else {
                showToast('error', response.message || 'Invoice not found');
            }
        },
        error: function () { showToast('error', 'Failed to fetch invoice data'); },
        complete: function () { hideLoader(); }
    });
}

// -------------------------------------------------------
// POPULATE FORM WITH INVOICE DATA
// -------------------------------------------------------
function loadInvoiceData(allData) {
    const data = allData.salesInvoice || {};
    const salesOrders = allData.salesOrders || null;

    // ── Header fields ────────────────────────────────────────
    $('#invoice_date').val(data.invoice_date ? formatDateToDMY(data.invoice_date) : '');
    $('#delivery_challan_number').val(data.delivery_challan_number || '');
    $('#grn_number').val(data.grn_number || '');
    $('#last_invoice_date').val(data.last_invoice_date ? formatDateToDMY(data.last_invoice_date) : '');

    $('#vehicle_number').val(data.vehicle_number || '');
    $('#delivery_date').val(data.delivery_date ? formatDateToDMY(data.delivery_date) : '');
    $('#remarks').val(data.remarks || '');
    $('#ewaybill_number').val(data.ewaybill_number || '');

    // ── Account (customer) ───────────────────────────────────
    let accountGstType = data.gst_type ? data.gst_type : '';
    $('#account_id').val(data.account_id || '').trigger('change.select2');
    $('#account_id_city').val(data.account_city ? data.account_city : '');
    $('#account_id_type').val(accountGstType === GST_TYPE.LOCAL ? 'LOCAL' : 'INTERSTATE');
    $('#kms').val(data.kms || 0);
    BillSundry.setGstType(accountGstType);

    // ── Sale type ────────────────────────────────────────────
    // Set BillSundry state directly from invoice data (avoids AJAX race condition)
    if (data.sale_type) {
        
        BillSundry.setSaleTypeDetail(data.sale_type);
        if (data.sale_type.region) {
            BillSundry.setGstType(data.sale_type.region);
        }
    }
    $('#sale_type_id').val(data.sale_type_id || '').trigger('change.select2');

    // console.log("data", data);
    

    _fillItemRowsFromSoInvoice(data);
    calculateTotalQty();
    updateItemTotal();

    // ── Sales order ──────────────────────────────────────────
    if (data.sales_order_id) {
        $('#sales_order_id').val(data.sales_order_id);
        $('#purchase_order_number').val(data.purchase_order_number || '');
        _lockSoFields();
    } else {
        $('#sales_order_id').val('');
        $('#purchase_order_number').val('');
        _unlockSoFields();
    }

    fillBillSundryData(data.bill_sundries || []);
}



// -------------------------------------------------------
// FILL & LOCK ITEM ROWS WHEN INVOICE IS LINKED TO AN SO
// Maps invoice detail relations to the format expected by
// _fillItemRowsFromSaleOrder (defined in get-sale-order.js),
// then fills quantity/party_quantity/GST rates afterward.
// -------------------------------------------------------
function _fillItemRowsFromSoInvoice(data) {

    data.details.forEach(function (item, index) {
        // console.log("item===>", item);
        

        var $row = $('#item_table_body .item-row').eq(index);
        if (!$row.length) return;

        
        $row.find('.item_id').val(item.item_id || '').trigger('change.select2');
        $row.find('.unit_name').val(
            item.unit_name ? item.unit_name : 'N/A'
        );
        $row.find('.destination_id').val(item.destination_id || '').trigger('change.select2');
        $row.find('.bag_count').val(item.bag_count || '');
        $row.find('.rate').val(item.rate ? parseFloat(item.rate).toFixed(DECIMALS.RATE) : '');
        $row.find('.inclusive_rate').val(item.inclusive_rate ? parseFloat(item.inclusive_rate).toFixed(DECIMALS.INCLUSIVE_RATE) : '');
        $row.find('.amount').val(item.amount ? parseFloat(item.amount).toFixed(DECIMALS.AMOUNT) : '');
        $row.find('.condition_id').val(item.condition_id || '').trigger('change.select2');
        $row.find('.quantity').val(item.quantity ? parseFloat(item.quantity).toFixed(DECIMALS.QTY) : '');
        $row.find('.party_quantity').val(item.party_quantity || '');
        $row.find('.cgst_rate').val(item.cgst_rate !== null ? item.cgst_rate : '');
        $row.find('.sgst_rate').val(item.sgst_rate !== null ? item.sgst_rate : '');
        $row.find('.igst_rate').val(item.igst_rate !== null ? item.igst_rate : '');
    });
}

const lockableSoFields = ['#account_id', '.item_id', '.destination_id', '.condition_id'];

// -------------------------------------------------------
// LOCK account_id + sale_type_id when SO is linked
// Does NOT trigger change (avoids unwanted account AJAX)
// -------------------------------------------------------
function _lockSoFields() {
    
    lockableSoFields.forEach(selector => {
        lockSelect2(selector);
    });

    $('#sale_type_id').addClass('non-selectable');
}

// -------------------------------------------------------
// UNLOCK SO fields when a non-SO invoice is loaded
// -------------------------------------------------------
function _unlockSoFields() {
    lockableSoFields.forEach(selector => {
        unlockSelect2(selector);
    });

    $('#sale_type_id').removeClass('non-selectable');
}

// -------------------------------------------------------
// FILL BILL SUNDRY DATA (edit mode — mirrors purchase edit.js)
// -------------------------------------------------------
function fillBillSundryData(billSundries) {
    // Remove any user-added (deletable) rows from a prior load
    $('#particular_table_body tr').each(function () {
        var $deleteBtn = $(this).find('.delete-particular-row');
        if ($deleteBtn.length) {
            $deleteBtn.trigger('click');
        }
    });

    if (!billSundries || !billSundries.length) {
        if (typeof BillSundry !== 'undefined') BillSundry.calculate();
        return;
    }

    // Sort ascending by sort_order
    var sorted = billSundries.slice().sort(function (a, b) {
        return (a.sort_order || 0) - (b.sort_order || 0);
    });

    // Separate rows already in DOM from rows that need to be created
    var existingRows = [];
    var newRows = [];

    sorted.forEach(function (billSundry, index) {
        var rowId = index + 1;
        if ($('#particular_row_' + rowId).length) {
            existingRows.push({ rowId: rowId, data: billSundry });
        } else {
            newRows.push({ rowId: rowId, data: billSundry });
        }
    });

    // Fill already-existing preloaded rows immediately
    existingRows.forEach(function (entry) {
        _fillBillSundryRow(entry.rowId, entry.data);
    });

    // For new rows: trigger the add-row button, then fill after Select2 init settles
    if (newRows.length) {
        newRows.forEach(function () {
            $('#add_particular_row').trigger('click');
        });

        setTimeout(function () {
            newRows.forEach(function (entry) {
                _fillBillSundryRow(entry.rowId, entry.data);
            });
            if (typeof BillSundry !== 'undefined') BillSundry.calculate();
        }, 30);
    } else {
        if (typeof BillSundry !== 'undefined') BillSundry.calculate();
    }
}

// ── Internal helper: fill one bill-sundry row by rowId ───────────────────────
function _fillBillSundryRow(rowId, billSundry) {
    // Use trigger('change') — NOT trigger('change.select2') — so that
    // onParticularChange() fires, which calls setMeta() to register this row
    // in BillSundry's internal state.
    var $select = $('#particular_id_' + rowId);
    if ($select.length) {
        $select.val(billSundry.sundry_id || '').trigger('change');
    }

    // Restore saved DR / CR account into BillSundry meta.
    // trigger('change') above resets meta from master data (no dr/cr) — patch it back.
    if (typeof BillSundry !== 'undefined' && typeof BillSundry.setRowDrCr === 'function') {
        var drId   = billSundry.bill_sundry_modal_dr_id || null;
        var drText = (billSundry.dr_account && billSundry.dr_account.name) ? billSundry.dr_account.name : '';
        var crId   = billSundry.bill_sundry_modal_cr_id || null;
        var crText = (billSundry.cr_account && billSundry.cr_account.name) ? billSundry.cr_account.name : '';
        BillSundry.setRowDrCr(rowId, drId, drText, crId, crText);
    }

    // Percentage — override the default set by applyMetaToRow
    var $pct = $('#particular_percentage_' + rowId);
    if ($pct.length && billSundry.rate_percent !== null && billSundry.rate_percent !== undefined) {
        if (billSundry.calculation_type === 'percentage') {
            var code = billSundry.code;
            if (code === '1002' || code === '1003' || code === '1004') {
                $pct.prop('disabled', true);
            } else {
                $pct.prop('disabled', false);
            }
        } else {
            $pct.prop('disabled', true);
        }
        billSundry.rate_percent == 0
            ? $pct.val('')
            : $pct.val(parseFloat(billSundry.rate_percent).toFixed(DECIMALS.PERCENTAGE));
    }

    // Value — override the default set by applyMetaToRow
    var $val = $('#particular_value_' + rowId);
    if ($val.length && billSundry.value !== null && billSundry.value !== undefined) {
        billSundry.value == 0
            ? $val.val('')
            : $val.val(parseFloat(billSundry.value).toFixed(DECIMALS.AMOUNT));
    }

    setTimeout(function () {
        BillSundry.calculate();
        $('#invoice_date').trigger('focus');
        window.scrollTo(0, 0);
    }, 30);
}

// -------------------------------------------------------
// FILL ITEM ROWS (all rows are editable in sales invoice)
// -------------------------------------------------------
function _fillItemRows(details) {
    // Add extra rows beyond the first
    for (var i = 1; i < details.length; i++) {
        _addSaleItemRow(i);
    }

    details.forEach(function (item, index) {
        var $row = $('#item_table_body .item-row').eq(index);
        if (!$row.length) return;

        // Update name indices on all named inputs in this row
        $row.find('[name]').each(function () {
            var name = $(this).attr('name');
            $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
        });

        // Selects via Select2
        $row.find('.item_id').val(item.item_id || '').trigger('change.select2');
        $row.find('.destination_id').val(item.destination_id || '').trigger('change.select2');
        $row.find('.condition_id').val(item.condition_id || '').trigger('change.select2');

        // GST rates
        $row.find('.cgst_rate').val(item.cgst_rate !== null ? item.cgst_rate : '');
        $row.find('.sgst_rate').val(item.sgst_rate !== null ? item.sgst_rate : '');
        $row.find('.igst_rate').val(item.igst_rate !== null ? item.igst_rate : '');

        // Numeric fields
        $row.find('.quantity').val(item.quantity ? parseFloat(item.quantity).toFixed(DECIMALS.QTY) : '');
        $row.find('.party_quantity').val(item.party_quantity || '');
        $row.find('.bag_count').val(item.bag_count || '');
        $row.find('.rate').val(item.rate ? parseFloat(item.rate).toFixed(DECIMALS.RATE) : '');
        $row.find('.inclusive_rate').val(item.inclusive_rate ? parseFloat(item.inclusive_rate).toFixed(DECIMALS.INCLUSIVE_RATE) : '');
        $row.find('.amount').val(item.amount ? parseFloat(item.amount).toFixed(DECIMALS.AMOUNT) : '');

        // Unit name
        $row.find('.unit_name').val(
            (item.item && item.item.unit) ? item.item.unit.name : ''
        );
    });

    if (typeof updateItemTotal === 'function') updateItemTotal();
    if (typeof calculateTotalQty === 'function') calculateTotalQty();
}
