/**
 * Debit Note (Purchase Return) — create.js
 *
 * Handles:
 *  - Date pickers with FY validation
 *  - Supplier → load purchase invoices for Bill No.
 *  - Invoice Type (Purchase Type) → populate GST rates
 *  - Item table: add / remove rows, qty × rate = amount
 *  - Bill Sundry particulars (reuses BillSundry module)
 *  - Form submit (create & edit modes)
 */

$(document).ready(function () {

    // ── Date pickers ──────────────────────────────────────────────────────────
    new DateInput('#debit_note_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    setTimeout(function () {
        $('#debit_note_date').val(currentDate).trigger('change');
    }, 100);

    $('#debit_note_date').on('blur', function () {
        var d = formatDateToYMD($(this).val());
        if (d && !isWithinFY(d, FINANCIAL_YEAR_START, FINANCIAL_YEAR_END)) {
            showToast('error', 'Date must be within the Financial Year.', 7000);
            $(this).val('');
        }
    });

    // ── Select2 ───────────────────────────────────────────────────────────────
    bindSelect2();
    BillSundry.init();
    bindItemEvents();
    validateForm();

    // ── Edit-mode: auto-load DN data ──────────────────────────────────────────
    if (typeof editDebitNoteId !== 'undefined' && editDebitNoteId) {
        loadEditData(editDebitNoteId);
    }
    setTimeout(() => {
        $('#debit_note_date').focus().select();
    }, 100);

    // ── Save shortcut (Alt + S) ───────────────────────────────────────────────
    $(document).on('keydown', function (e) {
        if (e.altKey && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            if (!$('#save_btn').prop('disabled')) {
                $('#debit_note_form').submit();
            }
        }
    });
});

// ────────────────────────────────────────────────────────────────────────────
// SELECT2
// ────────────────────────────────────────────────────────────────────────────
function bindSelect2() {
    ['#account_id', '#purchase_invoice_id', '#purchase_type_id', '#debit_note_id', '.item_id', '.condition_id', '.destination_id']
        .forEach(function (el) {
            $(el).select2({ theme: 'bootstrap-5' });
        });

    $('#account_id').on('change', function () {
        // Reset bill and invoice type when supplier changes
        $('#purchase_invoice_id').val('').trigger('change.select2');
        $('#purchase_invoice_serial').val('');
        $('#purchase_type_id').val('').trigger('change');
        window.invoicePurchaseTypeMap = {};

        loadPurchaseInvoicesForSupplier($(this).val());
    });

    // Bill No. change → populate serial hidden + auto-set purchase_type_id
    $('#purchase_invoice_id').on('change', function () {
        var selected    = $(this).find('option:selected');
        var invoiceId   = $(this).val();
        $('#purchase_invoice_serial').val(selected.data('serial') || '');

        var purchaseTypeId = window.invoicePurchaseTypeMap[invoiceId];
        if (purchaseTypeId) {
            $('#purchase_type_id').val(purchaseTypeId).trigger('change');
        }
    });

    // Invoice Type change → load GST rates
    $('#purchase_type_id').on('change', function () {
        loadPurchaseTypeDetails($(this).val());
    });

    // Debit Note change → reload data in edit mode
    $('#debit_note_id').on('change', function () {
        var dnId = $(this).val();
        if (dnId) {
            loadEditData(dnId);
        }
    });

    $(document).off('select2:open.manual_focus').on('select2:open.manual_focus', function (e) {
    const selectElement = $(e.target);
    const searchInput = selectElement.data('select2').$dropdown.find('.select2-search__field');
    searchInput.off('keydown.select2Enter').on('keydown.select2Enter', function (event) {
        if (event.which === 13) {
            event.preventDefault();
            selectElement.select2('close');
            if (typeof moveFocusToNextField === 'function') moveFocusToNextField(selectElement);
        }
    });
});
}

// ────────────────────────────────────────────────────────────────────────────
// LOAD PURCHASE INVOICES FOR SUPPLIER
// ────────────────────────────────────────────────────────────────────────────
window.invoicePurchaseTypeMap = {};

function loadPurchaseInvoicesForSupplier(accountId) {
    var $select = $('#purchase_invoice_id');
    $select.empty().append('<option value="">--Select Invoice--</option>');
    window.invoicePurchaseTypeMap = {};

    if (!accountId) return;

    $.ajax({
        url: purchaseInvoicesRoute,
        type: 'GET',
        data: { account_id: accountId },
        beforeSend: function () {
            showLoader("Loading Purchase Inv Bill");
        },
        success: function (res) {
            if (res.success && res.data) {
                res.data.forEach(function (inv) {
                    let date = formatDateToDMY(inv.bill_date);
                    $select.append(
                        '<option value="' + inv.id + '" data-serial="'+ inv.purchase_invoice_serial + '">'
                        + inv.purchase_invoice_serial + ' (' + date + ' | ₹' + inv.net_amount + ')'
                        + '</option>'
                    );
                    if (inv.purchase_type_id) {
                        window.invoicePurchaseTypeMap[inv.id] = inv.purchase_type_id;
                    }
                });
                $select.trigger('change.select2');
            }
        },
        complete: function () {
            hideLoader();
        },
        error: function () { showToast('error', 'Failed to load invoices.'); }
    });
}

// ────────────────────────────────────────────────────────────────────────────
// PURCHASE TYPE — GST RATES
// ────────────────────────────────────────────────────────────────────────────
function loadPurchaseTypeDetails(purchaseTypeId) {
    if (!purchaseTypeId) return;

    $.ajax({
        url: masterRoutes.purchaseTypeDetails(purchaseTypeId),
        type: 'GET',
        success: function (res) {
            if (!res.success || !res.data) return;
            var d = res.data;

            BillSundry.setPurchaseTypeDetail(d);
            BillSundry.setGstType(d.region || null);
            BillSundry.fillGstPercentages();
            updateItemTotal();
        }
    });
}

// ────────────────────────────────────────────────────────────────────────────
// ITEM TABLE
// ────────────────────────────────────────────────────────────────────────────
var itemRowIndex = 1;

function bindItemEvents() {
    // Item select → unit name
    $(document).on('change', '.item_id', function () {
        var $row   = $(this).closest('tr');
        var itemId = $(this).val();
        if (!itemId) { $row.find('.unit_name').val(''); return; }

        $.ajax({
            url: masterRoutes.itemDetails(itemId),
            type: 'GET',
            success: function (res) {
                var d = res.data || {};
                $row.find('.unit_name').val(d.unit_name || '');
                $row.find('.unit_name_hidden').val(d.unit_name || '');
            },
            error: function () { showToast('error', 'Failed to fetch item details.'); }
        });
    });

    // Qty / Rate blur → recalc
    $(document).on('input change', '.qty, .rate', function () {
        var $row = $(this).closest('tr');
        calcRowAmount($row);
    });
}

function buildItemRow(idx, data) {
    data = data || {};
    var itemOptions = buildItemOptions(data.item_id);

    return `
    <tr class="item-row">
        <td>
            <select name="items[${idx}][item_id]" class="form-select form-select-sm item_id select2">
                <option value="">--Select Product--</option>
                ${itemOptions}
            </select>
            <input type="hidden" name="items[${idx}][unit_name]" class="unit_name_hidden" value="${data.unit_name || ''}">
        </td>
        <td><input type="text"   name="items[${idx}][unit_name_display]" class="form-control form-control-sm unit_name" readonly value="${data.unit_name || ''}"></td>
        <td><input type="number" name="items[${idx}][quantity]"          class="form-control form-control-sm qty text-end"  min="0" step="0.001" value="${data.quantity || 0}"></td>
        <td><input type="number" name="items[${idx}][rate]"              class="form-control form-control-sm rate text-end" min="0" step="0.01"  value="${data.rate || 0}"></td>
        <td><input type="number" name="items[${idx}][amount]"            class="form-control form-control-sm amount text-end" readonly value="${data.amount || 0}"></td>
    </tr>`;
}

function buildItemOptions(selectedId) {
    if (typeof window.masterItems === 'undefined') return '';
    return window.masterItems.map(function (item) {
        var sel = (item.id == selectedId) ? 'selected' : '';
        return '<option value="' + item.id + '" ' + sel + '>' + item.name + '</option>';
    }).join('');
}

function calcRowAmount($row) {
    var qty  = parseFloat($row.find('.qty').val())  || 0;
    var rate = parseFloat($row.find('.rate').val()) || 0;
    var amt  = roundTo(qty * rate, 2);
    $row.find('.amount').val(amt);
    updateItemTotal();
}

function updateItemTotal() {
    var totalQty = 0, totalAmt = 0;
    $('#item_table_body .item-row').each(function () {
        totalQty += parseFloat($(this).find('.qty').val())    || 0;
        totalAmt += parseFloat($(this).find('.amount').val()) || 0;
    });
    $('#total_qty').val(totalQty.toFixed(3));
    $('#base_total_amount')
        .val('₹' + totalAmt.toFixed(2))
        .data('amount', totalAmt)
        .attr('data-amount', totalAmt);

    BillSundry.calculate(); // writes directly to #net_total input
}

// ────────────────────────────────────────────────────────────────────────────
// FORM SUBMIT
// ────────────────────────────────────────────────────────────────────────────
function validateForm() {
    $('#debit_note_form').on('submit', function (e) {
        e.preventDefault();

        var mode     = $(this).data('form-mode');
        var formData = collectFormData();
        console.log(formData);
        

        if (!formData.reference_number || !formData.reference_number.trim()) { showToast('error', 'Ref No. is required.');           return; }
        if (!formData.account_id)                        { showToast('error', 'Please select a supplier.');      return; }
        if (!formData.purchase_type_id)                      { showToast('error', 'Please select an invoice type.'); return; }
        if (!formData.items || !formData.items.length) {
            showToast('error', 'Please add at least one item.'); return;
        }

        var sundries = BillSundry.collect();
        if (sundries && sundries.error) { showToast('error', sundries.error); return; }
        formData.bill_sundries = sundries || [];

        var url    = (mode === 'edit') ? dnUpdateUrl.replace('__ID__', formData.debit_note_id) : dnStoreUrl;
        var method = (mode === 'edit') ? 'PUT' : 'POST';

        submitAjaxForm(url, method, formData);
    });
}

function collectFormData() {
    var $form = $('#debit_note_form');
    var data  = {
        uuid:                  $form.find('[name="uuid"]').val(),
        debit_note_id:        $form.find('[name="debit_note_id"]').val(),
        debit_note_date:      formatDateToYMD($('#debit_note_date').val()),
        reference_number:      ($('#reference_number').val() || '').trim(),
        account_id:            $('#account_id').val(),
        purchase_type_id:          $('#purchase_type_id').val(),
        purchase_invoice_id:      $('#purchase_invoice_id').val()      || null,
        purchase_invoice_serial:  $('#purchase_invoice_serial').val()  || null,
        remarks:               $('#remarks').val(),
        items:                 [],
        bill_sundries:         [],
    };

    $('#item_table_body .item-row').each(function (i) {
        var qty  = parseFloat($(this).find('.qty').val())    || 0;
        var rate = parseFloat($(this).find('.rate').val())   || 0;
        var amt  = parseFloat($(this).find('.amount').val()) || 0;
        var itemId = $(this).find('.item_id').val();
        if (!itemId || amt <= 0) return;

        data.items.push({
            item_id:       itemId,
            unit_name:     $(this).find('.unit_name').val(),
            quantity:      qty,
            rate:          rate,
            amount:        amt,
            inclusive_rate:0,
            bag_count:     0,
        });
    });

    return data;
}

function submitAjaxForm(url, method, data) {
    $('#save_btn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

    $.ajax({
        url:      url,
        type:     method,
        data:     JSON.stringify(data),
        contentType: 'application/json',
        headers:  { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (res) {
            if (res.success) {
                Swal.fire({
                    title: 'Success!',
                    html: res.message || 'Debit Note created successfully.',
                    icon: 'success',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                }).then(function (result) {
                    if (result.isConfirmed) {
                        location.reload();
                    }
                });
            } else {
                showErrorSwal(res.message, res.errors);
            }
        },
        error: function (xhr) {
            var json = xhr.responseJSON || {};
            showErrorSwal(json.message, json.errors);
        },
        complete: function () {
            $('#save_btn').prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Save');
        }
    });
}

// ────────────────────────────────────────────────────────────────────────────
// EDIT: load existing data
// ────────────────────────────────────────────────────────────────────────────
function loadEditData(dnId) {
    $.ajax({
        url:  dnEditFetchUrl,
        type: 'GET',
        data: { debit_note_id: dnId },
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        success: function (res) {
            if (!res.success || !res.data) { showToast('error', 'Failed to load data.'); return; }
            populateForm(res.data.debitNote);
        },
        error: function () { showToast('error', 'Failed to load Debit Note data.'); }
    });
}

function populateForm(dn) {
    $('#debit_note_date').val(formatDateToDMY(dn.debit_note_date));
    $('#reference_number').val(dn.reference_number || '');
    $('#account_id').val(dn.account_id).trigger('change');

    // Load invoices for the supplier then set the value
    loadPurchaseInvoicesForSupplier(dn.account_id);
    setTimeout(function () {
        $('#purchase_invoice_id').val(dn.purchase_invoice_id || '').trigger('change.select2');
        $('#purchase_invoice_serial').val(dn.purchase_invoice_serial || '');
    }, 600);

    $('#purchase_type_id').val(dn.purchase_type_id).trigger('change');
    $('#remarks').val(dn.remarks || '');

    // Items
    $('#item_table_body').empty();
    itemRowIndex = 0;
    if (dn.details && dn.details.length) {
        dn.details.forEach(function (row) {
            var idx  = itemRowIndex++;
            var $row = $(buildItemRow(idx, row));
            $('#item_table_body').append($row);
            $row.find('.item_id').select2({ theme: 'bootstrap-5' });
            $row.find('.item_id').val(row.item_id).trigger('change.select2');
        });
    }

    updateItemTotal();

    // Bill sundries
    if (dn.bill_sundries && dn.bill_sundries.length) {
        fillBillSundryData(dn.bill_sundries);
    }
}

function fillBillSundryData(billSundries) {
    if (!billSundries || !billSundries.length) return;

    // ── Remove any extra rows added by the user during the previous invoice ────
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
    if (newRows.length) {
        newRows.forEach(function (entry) {
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

function _fillBillSundryRow(rowId, billSundry) {
    var $select = $('#particular_id_' + rowId);
    if ($select.length) {
        $select.val(billSundry.sundry_id || '').trigger('change');
    }

    if (typeof BillSundry !== 'undefined' && typeof BillSundry.setRowDrCr === 'function') {
        var drId = billSundry.bill_sundry_modal_dr_id || null;
        var drText = (billSundry.dr_account && billSundry.dr_account.name) ? billSundry.dr_account.name : '';
        var crId = billSundry.bill_sundry_modal_cr_id || null;
        var crText = (billSundry.cr_account && billSundry.cr_account.name) ? billSundry.cr_account.name : '';
        BillSundry.setRowDrCr(rowId, drId, drText, crId, crText);
    }

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

    var $val = $('#particular_value_' + rowId);
    if ($val.length && billSundry.value !== null && billSundry.value !== undefined) {
        billSundry.value == 0 ? $val.val('') : $val.val(parseFloat(billSundry.value).toFixed(DECIMALS.AMOUNT));
    }

    if (billSundry.code == '1009') {
        $pct.data('original-pct', billSundry.rate_percent != null ? parseFloat(billSundry.rate_percent) : null);
        $pct.data('original-val', billSundry.value != null ? parseFloat(billSundry.value) : null);
    }

    setTimeout(function () {
        BillSundry.calculate();
    }, 30);
}

// ────────────────────────────────────────────────────────────────────────────
// UTILS
// ────────────────────────────────────────────────────────────────────────────
function roundTo(val, places) {
    return Math.round(val * Math.pow(10, places)) / Math.pow(10, places);
}

function showValidationErrors(errors) {
    if (!errors) return;
    var msg = '';
    $.each(errors, function (k, v) { msg += (Array.isArray(v) ? v.join(', ') : v) + '<br>'; });
    showToast('error', msg, 10000);
}

function showErrorSwal(message, errors) {
    var html = '<div class="text-start">';
    html += '<p class="mb-2">' + (message || 'Failed to save.') + '</p>';
    if (errors && typeof errors === 'object') {
        html += '<ul class="mb-0 ps-3">';
        $.each(errors, function (_k, v) {
            var msgs = Array.isArray(v) ? v : [v];
            msgs.forEach(function (m) { html += '<li>' + m + '</li>'; });
        });
        html += '</ul>';
    }
    html += '</div>';

    Swal.fire({
        title: 'Error!',
        html: html,
        icon: 'error',
        confirmButtonText: 'OK',
    });
}
