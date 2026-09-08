$(document).ready(function () {
    $('#grn_id').select2({
        theme: "bootstrap-5",
        placeholder: "Select GRN ...",
    });
    
    // Focus the select2 input when editing

    setTimeout(() => {
        $('#grn_id').select2('open');
    }, 20);

    $('#grn_id').on('change', function () {
        const grnId = $(this).val();
        if (grnId) getGrn(grnId);
    });

    // Auto-load if a GRN is pre-selected (edit route with ID)
    const preSelected = $('#grn_id').val();
    if (preSelected) {
        getGrn(preSelected);
    }
});

// ── Fetch GRN data from server ─────────────────────────────────────
function getGrn(grnId) {
    if (!grnId) return;

    $.ajax({
        url: getGrnUrl.replace(':id', grnId), // route('grns.edit', ':id') — no ID substitution needed
        type: 'GET',
        data: { grn_id: grnId },  // controller checks $request->has('grn_id')
        beforeSend: function () {
            showLoader('Fetching GRN data…');
        },
        success: function (response) {
            if (response.success && response.data) {
                setData(response.data);
            } else {
                showToast('error', response.message ?? 'Failed to fetch GRN', 5000);
            }
        },
        error: function (xhr) {
            showToast('error', xhr.responseJSON?.message ?? 'Error fetching GRN', 5000);
        },
        complete: function () {
            hideLoader();
        }
    });
}

// ── Populate all form fields from API response ─────────────────────
function setData(data) {

    // ── Master fields ──────────────────────────────────────────────
    $('#grn_date').val(data.grn_date     ? formatDateToDMY(data.grn_date)     : '');
    $('#grn_in_date').val(data.grn_in_date  ? formatDateToDMY(data.grn_in_date)  : '');
    $('#grn_out_date').val(data.grn_out_date ? formatDateToDMY(data.grn_out_date) : '');

    $('#contract_number').val(data.contract_number ?? '');
    $('#reference_number').val(data.reference_number ?? '');
    $('#vehicle_number').val(data.vehicle_number ?? '');
    $('#remarks').val(data.remarks ?? '');

    // ── Weight fields ──────────────────────────────────────────────
    $('#gross_weight').val(data.gross_weight ? parseFloat(data.gross_weight).toFixed(DECIMALS.WEIGHT_KG) : '');
    $('#tare_weight').val(data.tare_weight  ? parseFloat(data.tare_weight).toFixed(DECIMALS.WEIGHT_KG)  : '');
    $('#net_weight').val(data.net_weight   ? parseFloat(data.net_weight).toFixed(DECIMALS.WEIGHT_KG)   : '');
    $('#net_weight_without_bags').val(data.net_weight_wt_bag ? parseFloat(data.net_weight_wt_bag).toFixed(DECIMALS.WEIGHT_KG) : '');
    $('#bag_count').val(data.bag_count ?? '');
    $('#bag_type').val(data.bag_type ?? '').trigger('change');

    // ── Broker (plain <select>, options pre-loaded by Blade) ────────
    $('#broker_id').val(data.broker_id ?? '').trigger('change');

    // ── Account (supplier) — triggers GST type / city fill ─────────
    // Use silent set first to display value, then let handleAccountId fill city/type
    if (data.account_id) {
        $('#account_id').val(data.account_id).trigger('change');
    }

    // ── Item table rows ────────────────────────────────────────────
    populateItemRows(data.details ?? []);

    // ── Totals ─────────────────────────────────────────────────────
    calculateTotal();
    calculateTotalQty();
}

// ── Render detail rows ─────────────────────────────────────────────
function populateItemRows(details) {
    const $tbody = $('#item_table_body');

    // Remove all rows except the first before repopulating so stale rows
    // from a previously loaded GRN don't persist when the new GRN has fewer items.
    $tbody.find('.item-row').not(':first').remove();

    details.forEach(function (detail, index) {
        let $row;

        if (index === 0) {
            // Reuse the existing first row
            $row = $tbody.find('.item-row').first();
        } else {
            // Add new row via create.js's addRow(), then reference the last one
            addRow(true);
            $row = $tbody.find('.item-row').last();
        }

        // ── Re-index field names ───────────────────────────────────
        $row.find('input, select').each(function () {
            if (this.name) {
                this.name = this.name.replace(/items\[\d+\]/, 'items[' + index + ']');
            }
        });
        $row.attr('data-row-id', index);

        // ── item_id — via Select2 (no .change event so no AJAX overwrite) ──
        const $itemSel = $row.find('select.item_id');
        if ($itemSel.length) {
            $itemSel.val(detail.item_id ?? '').trigger('change.select2');
        }

        // ── destination_id ─────────────────────────────────────────
        const $destSel = $row.find('select.destination_id');
        if ($destSel.length) {
            $destSel.val(detail.destination_id ?? '').trigger('change.select2');
        }

        // ── condition_id ───────────────────────────────────────────
        const $condSel = $row.find('select.condition_id');
        if ($condSel.length) {
            $condSel.val(detail.condition_id ?? '').trigger('change.select2');
        }

        // ── Plain inputs (set directly — no AJAX side-effects) ─────
        const set = function (cls, value) {
            const el = $row.find(cls)[0];
            if (el) el.value = value ?? '';
        };

        set('input.unit_name',       detail.item?.unit?.name ?? '');
        set('input.cgst_rate',       detail.cgst_rate  ?? '');
        set('input.sgst_rate',       detail.sgst_rate  ?? '');
        set('input.igst_rate',       detail.igst_rate  ?? '');
        set('input.rate',            detail.rate        ?? '');
        set('input.inclusive_rate',  detail.inclusive_rate ?? '');
        set('input.amount',          detail.amount      ?? '');
        set('input.bag_count',       detail.bag_count   ?? '');
        const qtyEl = $row.find('input.quantity')[0];
        if (qtyEl) {
            const qty = parseFloat(detail.quantity);
            qtyEl.value = isNaN(qty) ? '' : qty.toFixed(DECIMALS.QTY);
        }

        const partyQtyEl = $row.find('input.party_quantity')[0];
        if (partyQtyEl) {
            const partyQty = parseFloat(detail.party_quantity);
            partyQtyEl.value = isNaN(partyQty) ? '' : partyQty.toFixed(DECIMALS.QTY);
        }

        // ── PO serial & hidden IDs ─────────────────────────────────
        set('input.purchase_order_serial',   detail.purchase_order_serial   ?? '');
        set('input.purchase_order_id',       detail.purchase_order_id       ?? '');
        set('input.purchase_order_item_id',  detail.purchase_order_item_id  ?? '');

        // Show/hide the ✖ clear-PO button
        if (detail.purchase_order_serial) {
            $row.find('.clear-po').removeClass('d-none');
        } else {
            $row.find('.clear-po').addClass('d-none');
        }
    });
}
function printGrn(grnId) {
    if (!Number(grnId)) {
        Swal.fire({
            icon: "warning",
            title: "GRN Number Required",
            text: "GRN Number is required to print the receipt. Please select GRN Number.",
            confirmButtonText: "OK",
            allowOutsideClick: false,
            allowEscapeKey: false,
        });
        return;
    }

    showLoader("Please wait, Preparing Print Data...");

    $.ajax({
        url: grnPrintRecieptUrl.replace(':id', grnId),
        type: "GET",
        data: {
            format: "print"
        },
        success: function (response) {
            const printWindow = window.open("", "_blank");

            if (!printWindow) {
                showToast("error", "Pop-ups were blocked. Please allow pop-ups for this site to print the report.");
                return;
            }

            printWindow.document.open();
            printWindow.document.write(response.data.html);
            printWindow.document.close();

            printWindow.focus();

            setTimeout(function () {
                printWindow.print();
            }, 100);
        },
        error: function () {
            showToast("error", "Could not load print data. Please try again");
        },
        complete: function () {
            hideLoader();
        }
    });
}