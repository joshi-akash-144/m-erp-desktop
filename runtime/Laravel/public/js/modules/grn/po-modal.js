// ─────────────────────────────────────────────────────────────
//  GRN – Purchase Order Modal
//  Triggered by Space key on .purchase_order_serial input
// ─────────────────────────────────────────────────────────────

/** The item-row that triggered the modal */
let _activePoRow = null;

/** Full PO list fetched from server (used for client-side search) */
let _poData = [];

// ────────────────────────────────────────
//  Open / Close
// ────────────────────────────────────────

function openPurchaseOrderModal(row) {
    const accountId = $('#account_id').val();
    const brokerId = $('#broker_id').val();
    const itemId = row.find('.item_id').val();

    if (!accountId) {
        showToast('error', 'Please select supplier first', 5000);
        return;
    }

    _activePoRow = row;
    getAllPendingPurchaseOrders(accountId, brokerId, itemId);
    $('#pending_po_modal').modal('show');
}

function closePurchaseOrderModal() {
    $('#pending_po_modal').modal('hide');
    // Return focus to the serial input of the active row
    if (_activePoRow) {
        _activePoRow.find('.purchase_order_serial').focus();
    }
}

// ────────────────────────────────────────
//  Fetch from server
// ────────────────────────────────────────

function getAllPendingPurchaseOrders(accountId, brokerId, itemId) {
    

    $.ajax({
        url: pendingPurchaseOrders,
        type: 'GET',
        data: { account_id: accountId, broker_id: brokerId, item_id: itemId },
        beforeSend: function () {
            renderPoLoading();
        },
        success: function (response) {
            _poData = response.data ?? [];
            renderPoRows(_poData);
        },
        error: function (error) {
            console.warn(error);
            showToast('error', 'Failed to fetch purchase order details', 5000);
            renderPoEmpty();
        },
    });
}

// ────────────────────────────────────────
//  Render helpers
// ────────────────────────────────────────

function renderPoLoading() {
    const $body = $('#pending_po_table_body');
    $body.html(`
        <tr>
            <td colspan="9" class="text-center py-4 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                Loading purchase orders…
            </td>
        </tr>
    `);
}

function renderPoEmpty() {
    const $body = $('#pending_po_table_body');
    $body.html(`
        <tr id="po_no_data_row" class="text-center">
            <td colspan="9" class="py-4 text-muted">
                <div class="list-group-item text-center py-5 border border-dashed rounded-4 bg-primary-lt">
                    <i class="fa-solid fa-file-circle-xmark fa-3x text-secondary mb-3"></i>
                    <h5 class="mb-1 text-secondary fw-bold">No Purchase Order Found</h5>
                    <p class="mb-0 text-muted">Try adjusting your filters or search keywords.</p>
                </div>
            </td>
        </tr>
    `);
}

/**
 * Returns purchase_order_item_id values already selected in other item rows.
 * The active row is excluded so the user can re-pick for the row they opened.
 */
function getSelectedDetailIds() {
    const ids = [];
    $('.item-row').each(function () {
        if (_activePoRow && $(this).is(_activePoRow)) return;
        const v = $(this).find('.purchase_order_item_id').val();
        if (v) ids.push(parseInt(v, 10));
    });
    return ids;
}

/**
 * Render a flat list of PO detail rows.
 * Each detail (item line) inside a PO becomes one table row.
 * Details already selected in other rows are excluded.
 */
function renderPoRows(poList) {
    const $body            = $('#pending_po_table_body');
    const selectedDetailIds = getSelectedDetailIds();
    $body.empty();

    // Flatten: one row per PO detail line
    const rows = [];
    poList.forEach(function (po) {
        (po.details ?? []).forEach(function (detail) {
            if (selectedDetailIds.includes(detail.id)) return;
            const balQty = (parseFloat(detail.ordered_qty) - parseFloat(detail.received_qty)).toFixed(3);
            rows.push({
                po_id           : po.id,
                order_serial    : po.order_serial,
                order_number    : po.order_number ?? '—',
                order_date      : formatDisplayDate(po.order_date),
                ordered_qty     : parseFloat(detail.ordered_qty).toFixed(3),
                bal_qty         : balQty,
                destination_id  : po.destination_id ?? '',
                destination     : po.destination?.name ?? '—',
                broker          : po.broker?.name ?? '—',
                delivery_days   : po.delivery_days ?? '—',
                due_date        : formatDisplayDate(po.due_date),
                detail_id       : detail.id,
                item_name       : detail.item?.name ?? '—',
                condition_id    : detail.condition_id ?? '',
                rate            : detail.rate ?? '',
                inclusive_rate  : detail.inclusive_rate ?? '',
                broker_id       : po.broker_id ?? '',
                contract_number   : po.contract_number ?? '',
            });
        });
    });

    if (!rows.length) {
        renderPoEmpty();
        return;
    }

    rows.forEach(function (r, idx) {
        const balClass = parseFloat(r.bal_qty) <= 0 ? 'text-danger' : 'text-success';
        $body.append(`
            <tr class="po-select-row"
                data-po-id="${r.po_id}"
                data-detail-id="${r.detail_id}"
                data-order-serial="${htmlEsc(r.order_serial)}"
                data-destination-id="${htmlEsc(String(r.destination_id))}"
                data-condition-id="${htmlEsc(String(r.condition_id))}"
                data-rate="${htmlEsc(String(r.rate))}"
                data-inclusive-rate="${htmlEsc(String(r.inclusive_rate))}"
                data-index="${idx}"
                data-broker-id="${htmlEsc(r.broker_id)}"
                data-broker-name="${htmlEsc(r.broker)}"
                data-contract-number="${htmlEsc(String(r.contract_number))}"
                style="cursor:pointer;">
                <td class="text-center">${htmlEsc(r.order_serial)}</td>
                <td class="text-center">${htmlEsc(r.order_date)}</td>
                <td class="text-end">${htmlEsc(r.ordered_qty)}</td>
                <td class="text-end ${balClass} fw-semibold">${htmlEsc(r.bal_qty)}</td>
                <td class="text-end">${r.inclusive_rate ? parseFloat(r.inclusive_rate).toFixed(2) : '—'}</td>
                <td>${htmlEsc(r.destination)}</td>
                <td>${htmlEsc(r.broker)}</td>
                <td class="text-center">${htmlEsc(String(r.delivery_days))}</td>
                <td class="text-center">${htmlEsc(r.due_date)}</td>
            </tr>
        `);
    });
}

// ────────────────────────────────────────
//  Utility: safe HTML escape & date format
// ────────────────────────────────────────

function htmlEsc(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function formatDisplayDate(dateStr) {
    if (!dateStr) return '—';
    // Convert YYYY-MM-DD → DD-MM-YYYY
    const parts = dateStr.split('-');
    if (parts.length === 3) return `${parts[2]}-${parts[1]}-${parts[0]}`;
    return dateStr;
}

// ────────────────────────────────────────
//  Row Selection → set data in item-row
// ────────────────────────────────────────

function applyPoSelectionToRow($tr) {
    if (!_activePoRow) return;

    const poId          = $tr.data('po-id');
    const detailId      = $tr.data('detail-id');
    const orderSerial   = $tr.data('order-serial');
    const destinationId = $tr.data('destination-id');
    const conditionId   = $tr.data('condition-id');
    const rate          = $tr.data('rate');
    const inclusiveRate = $tr.data('inclusive-rate');
    const brokerId      = $tr.data('broker-id');
    const contractNumber = $tr.data('contract-number');

    // PO identifiers
    _activePoRow.find('.purchase_order_serial').val(orderSerial);
    _activePoRow.find('.purchase_order_id').val(poId);
    _activePoRow.find('.purchase_order_item_id').val(detailId);

    // Destination — set via Select2 so the dropdown reflects it
    if (destinationId) {
        const $dest = _activePoRow.find('.destination_id');
        if ($dest.find(`option[value="${destinationId}"]`).length) {
            $dest.val(destinationId).trigger('change');
        } else {
            // Option not yet in list — create it dynamically
            const destText = $tr.find('td:nth-child(6)').text().trim();
            $dest.append(new Option(destText, destinationId, true, true)).trigger('change');
        }
    }

    if (brokerId) {
        const $brokerSelect = $('#broker_id');
        if ($brokerSelect.find(`option[value="${brokerId}"]`).length) {
            $brokerSelect.val(brokerId).trigger('change');
        } else {
            const brokerName = $tr.data('broker-name');
            $brokerSelect.append(new Option(brokerName, brokerId, true, true)).trigger('change');
        }
    }
    if(contractNumber) {
        $('#contract_number').val(contractNumber);
    }

    // Condition — same pattern
    if (conditionId) {
        const $cond = _activePoRow.find('.condition_id');
        if ($cond.find(`option[value="${conditionId}"]`).length) {
            $cond.val(conditionId).trigger('change');
        } else {
            const condText = $tr.find('td').eq(5).text().trim(); // fallback label
            $cond.append(new Option(condText, conditionId, true, true)).trigger('change');
        }
    }

    // Rate & Inclusive Rate
    if (rate)          _activePoRow.find('.rate').val(rate);
    if (inclusiveRate) _activePoRow.find('.inclusive_rate').val(inclusiveRate);

    // Recalculate row amounts, then update footer totals
    if (typeof rateBlur === 'function') {
        rateBlur(_activePoRow);
    }
    if (typeof calculateTotal === 'function')    calculateTotal();
    if (typeof calculateTotalQty === 'function') calculateTotalQty();

    // Show the clear (✖) icon
    _activePoRow.find('.clear-po').removeClass('d-none');

    closePurchaseOrderModal();
}

// ────────────────────────────────────────
//  Keyboard navigation inside modal
// ────────────────────────────────────────

let _poActiveIndex = -1;

function poHighlightRow(index) {
    const $rows = $('#pending_po_table_body .po-select-row:visible');
    $rows.removeClass('po-row-active table-primary');
    if (index >= 0 && index < $rows.length) {
        _poActiveIndex = index;
        const $active = $rows.eq(index);
        $active.addClass('po-row-active table-primary');
        // Scroll into view
        const modalBody = document.querySelector('#pending_po_modal .modal-body');
        if (modalBody) {
            const rowTop    = $active[0].offsetTop;
            const rowHeight = $active[0].offsetHeight;
            const bodyHeight = modalBody.clientHeight;
            if (rowTop < modalBody.scrollTop) {
                modalBody.scrollTop = rowTop;
            } else if (rowTop + rowHeight > modalBody.scrollTop + bodyHeight) {
                modalBody.scrollTop = rowTop + rowHeight - bodyHeight;
            }
        }
    }
}

// ────────────────────────────────────────
//  Search filter
// ────────────────────────────────────────

function filterPoTable(query) {
    const q = query.toLowerCase().trim();
    if (!q) {
        renderPoRows(_poData);
        return;
    }

    const filtered = _poData.filter(function (po) {
        const serial = String(po.order_serial ?? '').toLowerCase();
        const num    = String(po.order_number ?? '').toLowerCase();
        const dest   = String(po.destination?.name ?? '').toLowerCase();
        const broker = String(po.broker?.name ?? '').toLowerCase();
        return serial.includes(q) || num.includes(q) || dest.includes(q) || broker.includes(q);
    });

    renderPoRows(filtered);
}

// ────────────────────────────────────────
//  Event binding (runs once on DOM ready)
// ────────────────────────────────────────

$(document).ready(function () {

    // Click on a PO row → select it
    $(document).on('click', '#pending_po_table_body .po-select-row', function () {
        applyPoSelectionToRow($(this));
    });

    // Search filter
    $('#pending_po_search').on('input', function () {
        _poActiveIndex = -1;
        filterPoTable($(this).val());
    });

    // Keyboard navigation inside modal
    $('#pending_po_modal').on('keydown', function (e) {
        const $rows = $('#pending_po_table_body .po-select-row:visible');
        if (!$rows.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            poHighlightRow(Math.min(_poActiveIndex + 1, $rows.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            poHighlightRow(Math.max(_poActiveIndex - 1, 0));
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (_poActiveIndex >= 0 && _poActiveIndex < $rows.length) {
                applyPoSelectionToRow($rows.eq(_poActiveIndex));
            }
        }
        // Note: Escape is handled by hidden.bs.modal below (covers all dismiss paths)
    });

    // Reset state when modal opens
    $('#pending_po_modal').on('show.bs.modal', function () {
        _poActiveIndex = -1;
        $('#pending_po_search').val('');
    });

    // Focus search on modal open
    $('#pending_po_modal').on('shown.bs.modal', function () {
        $('#pending_po_search').focus();
    });

    // Restore focus to purchase_order_serial whenever modal closes
    // (covers: Escape key, ✕ button, backdrop click, or programmatic hide)
    $('#pending_po_modal').on('hidden.bs.modal', function () {
        if (_activePoRow) {
            _activePoRow.find('.purchase_order_serial').focus();
        }
    });

    // Clear PO selection from a row
    $(document).on('click', '.clear-po', function (e) {
        e.stopPropagation();
        const row = $(this).closest('.item-row');
        row.find('.purchase_order_serial').val('');
        row.find('.purchase_order_id').val('');
        row.find('.purchase_order_item_id').val('');
        $(this).addClass('d-none');
    });
});
