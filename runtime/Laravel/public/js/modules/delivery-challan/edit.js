$(document).ready(function () {
    $('#challan_id').select2({
        theme: "bootstrap-5",
        placeholder: "Select Challan ...",
    });

    $('#challan_id').on('change', function () {
        const challanId = $(this).val();
        if (challanId) getChallan(challanId);
    });

    // Auto-load if a Challan is pre-selected
    const preSelected = $('#challan_id').val();
    if (preSelected) {
        getChallan(preSelected);
    }
});

// ── Fetch Challan data from server ─────────────────────────────────────
function getChallan(challanId) {
    if (!challanId) return;

    $.ajax({
        url: getChallanUrl.replace(':id', challanId),
        type: 'GET',
        beforeSend: function () {
            showLoader('Fetching Delivery Challan data…');
        },
        success: function (response) {
            const data = response.data || response;
            if (data) {
                setChallanData(data);
            } else {
                showToast('error', 'Failed to fetch Delivery Challan', 5000);
            }
        },
        error: function (xhr) {
            showToast('error', xhr.responseJSON?.message ?? 'Error fetching Delivery Challan', 5000);
        },
        complete: function () {
            hideLoader();
        }
    });
}

// ── Populate all form fields from API response ─────────────────────
function setChallanData(data) {

    // ── Master fields ──────────────────────────────────────────────
    $('#challan_date').val(data.challan_date ? formatDateToDMY(data.challan_date) : '');
    $('#vehicle_number').val(data.vehicle_number ?? '');
    $('#remarks').val(data.remarks ?? '');

    // ── Weight fields ──────────────────────────────────────────────
    $('#gross_weight').val(data.gross_weight ? parseFloat(data.gross_weight).toFixed(DECIMALS.WEIGHT_KG) : '');
    $('#tare_weight').val(data.tare_weight ? parseFloat(data.tare_weight).toFixed(DECIMALS.WEIGHT_KG) : '');
    $('#net_weight').val(data.net_weight ? parseFloat(data.net_weight).toFixed(DECIMALS.WEIGHT_KG) : '');
    $('#net_weight_without_bags').val(data.net_weight_wt_bag ? parseFloat(data.net_weight_wt_bag).toFixed(DECIMALS.WEIGHT_KG) : '');
    $('#bag_count').val(data.bag_count ?? '');
    $('#bag_type').val(data.bag_type ?? '').trigger('change');

    // ── Broker
    $('#broker_id').val(data.broker_id ?? '').trigger('change');

    // ── Account (customer)
    if (data.account_id) {
        $('#account_id').val(data.account_id).trigger('change');
    }

    // ── Item table rows ────────────────────────────────────────────
    populateItemRows(data.details ?? []);

    // ── Totals ─────────────────────────────────────────────────────
    if (typeof calculateTotal === 'function') calculateTotal();
    if (typeof calculateTotalQty === 'function') calculateTotalQty();
}

// ── Render detail rows ─────────────────────────────────────────────
function populateItemRows(details) {
    const $tbody = $('#item_table_body');

    // Clear existing rows except the first one if it's empty
    const $rows = $tbody.find('.item-row');
    if ($rows.length > 1) {
        $rows.slice(1).remove();
    }

    details.forEach(function (detail, index) {
        let $row;

        if (index === 0) {
            $row = $tbody.find('.item-row').first();
        } else {
            if (typeof addRow === 'function') {
                addRow();
                $row = $tbody.find('.item-row').last();
            } else {
                console.error("addRow function not found in create.js");
                return;
            }
        }

        // ── Re-index field names ───────────────────────────────────
        $row.find('input, select').each(function () {
            if (this.name) {
                this.name = this.name.replace(/items\[\d+\]/, 'items[' + index + ']');
            }
        });
        $row.attr('data-row-id', index);

        // ── item_id ──
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

        // ── Plain inputs ─────
        const set = function (cls, value) {
            const el = $row.find(cls)[0];
            if (el) el.value = value ?? '';
        };

        set('input.unit_name', detail.item?.unit?.name ?? '');
        set('input.cgst_rate', detail.cgst_rate ?? '');
        set('input.sgst_rate', detail.sgst_rate ?? '');
        set('input.igst_rate', detail.igst_rate ?? '');
        set('input.rate', detail.rate ?? '');
        set('input.inclusive_rate', detail.inclusive_rate ?? '');
        set('input.amount', detail.amount ?? '');
        set('input.bag_count', detail.bag_count ?? '');
        set('input.party_quantity', detail.party_quantity ?? '');

        const qtyEl = $row.find('input.quantity')[0];
        if (qtyEl) {
            const qty = parseFloat(detail.quantity);
            qtyEl.value = isNaN(qty) ? '' : qty.toFixed(DECIMALS.QTY);
        }

        // ── SO serial & hidden IDs ─────────────────────────────────
        set('input.sales_order_serial', detail.sales_order_serial ?? '');
        set('input.sales_order_id', detail.sales_order_id ?? '');
        set('input.sales_order_item_id', detail.sales_order_item_id ?? '');

        // Show/hide the ✖ clear-SO button
        if (detail.sales_order_serial) {
            $row.find('.clear-so').removeClass('d-none');
        } else {
            $row.find('.clear-so').addClass('d-none');
        }
    });
}

function printChallan(challanId) {
    if (!Number(challanId)) {
        Swal.fire({
            icon: "warning",
            title: "DC Number Required",
            text: "DC Number is required to print the receipt. Please select DC Number.",
            confirmButtonText: "OK",
        });
        return;
    }
    // console.log("Printing challan ID :", challanId);
    $.ajax({
        url: deliveryChallanPrintLetterUrl,
        type: "GET",
        data: {
            format: "print",
            ids: challanId
        },
        beforeSend: function () {
            showLoader('Printing challan...');
        },
        success: function (response) {
            console.log("Print response:", response);
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