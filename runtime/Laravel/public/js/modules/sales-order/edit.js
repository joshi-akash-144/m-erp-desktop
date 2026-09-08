$(document).ready(function () {
    $('#sales_order_id').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#sales_order_id').parent(),
    });
    $('#sales_order_id').on('change', function () {
        const salesOrderId = $(this).val();
        getSalesOrder(salesOrderId);
    });
    $('#sales_order_id').trigger('change');
});

function getSalesOrder(salesOrderId) {    
    if (!salesOrderId) {
        return;
    }
    $.ajax({
        url: getSalesOrderUrl.replace(':id', salesOrderId),
        type: 'GET',
        beforeSend: function () {
            showLoader('Fetching Sales Order...');
        },
        success: function (response) {
            setData(response.data);
        },
        complete: function () {
            hideLoader();
        }
    });
}

function setData(data) {
    // Header Mapping for Sales Order
    $('#purchase_order_number').val(data.purchase_order_number ?? '');

    let poDate = data.purchase_order_date ? formatDateToDMY(data.purchase_order_date) : '';
    $('#purchase_order_date').val(poDate);

    let deliveryDate = data.delivery_date ? formatDateToDMY(data.delivery_date) : '';
    $('#delivery_date').val(deliveryDate);

    $('#delivery_days').val(data.delivery_days ?? '');

    // Set global gstType for calculations
    gstType = data.gst_type ?? "";
    const cityEl = $("#account_id_city");
    const typeEl = $("#account_id_type");
    cityEl.val(data.account?.city ?? "");
    typeEl.val(gstType === 'local' ? "LOCAL" : "INTERSTATE");

    let brokerId = data.broker_id ? data.broker_id : '';
    brokerId ? $('#broker_id').val(brokerId).trigger('change') : $('#broker_id').val('');

    let accountId = data.account_id ? data.account_id : '';
    accountId ? $('#account_id').val(accountId).trigger('change') : $('#account_id').val('');

    let remarks = data.remarks ? data.remarks : '';
    remarks ? $('#remarks').val(remarks) : $('#remarks').val('');

    // =====================================================
    // Populate Rows
    // =====================================================
    const tbody = document.getElementById("item_table_body");

    if (Array.isArray(data.details)) {
        data.details.forEach((detail, index) => {
            console.log("detail", detail);
            

            // Clone logic — reuse first row, clone for subsequent
            let row;
            if (index === 0) {
                row = tbody.querySelector("tr");
            } else {
                const firstRow = tbody.querySelector("tr");
                row = firstRow.cloneNode(true);

                // Strip stale select2 artifacts before appending
                $(row).find(".select2-container").remove();
                $(row).find("select").each(function () {
                    $(this).removeClass("select2-hidden-accessible");
                    $(this).removeAttr("data-select2-id");
                });

                tbody.appendChild(row);
            }

            row.dataset.row   = index + 1;
            row.dataset.rowId = index + 1;

            // ── item_id ──────────────────────────────────────────────
            // Set value WITHOUT triggering change to avoid AJAX overwriting tax fields
            const itemSelect = row.querySelector("select.item_id");
            if (itemSelect) {
                itemSelect.name = `items[${index}][item_id]`;
                $(itemSelect).val(detail.item_id ?? "");
                // Refresh select2 display only (no 'change' event = no AJAX)
                if ($(itemSelect).hasClass("select2-hidden-accessible")) {
                    $(itemSelect).trigger("change.select2");
                }
            }

            // ── condition_id ─────────────────────────────────────────
            const conditionSelect = row.querySelector("select.condition_id");
            if (conditionSelect) {
                conditionSelect.name = `items[${index}][condition_id]`;
                $(conditionSelect).val(detail.condition_id ?? "").trigger("change");
            }

            // ── destination_id ───────────────────────────────────────
            const destinationSelect = row.querySelector("select.destination_id");
            if (destinationSelect) {
                destinationSelect.name = `items[${index}][destination_id]`;
                $(destinationSelect).val(detail.destination_id ?? "").trigger("change");
            }

            // ── plain inputs — set directly from saved data ───────────
            const setInput = (selector, value) => {
                const el = row.querySelector(selector);
                if (el) el.value = value ?? "";
            };
            
            setInput("input.unit_name",     detail.item?.unit?.name ?? "");
            setInput("input.cgst_rate",     detail.cgst_rate ?? "");
            setInput("input.sgst_rate",     detail.sgst_rate ?? "");
            setInput("input.igst_rate",     detail.igst_rate ?? "");
            setInput("input.inclusive_rate", detail.inclusive_rate ?? "");
            setInput("input.rate",           detail.rate ?? "");
            setInput("input.amount",         detail.amount ?? "");

            // quantity uses ordered_qty field from API
            const qtyEl = row.querySelector("input.quantity");
            if (qtyEl) {
                qtyEl.name  = `items[${index}][quantity]`;
                const qty   = Number(detail.ordered_qty);
                qtyEl.value = isNaN(qty) ? "" : qty.toFixed(DECIMALS.QTY);
            }
        });
    }

    // Refresh totals
    calculateTotal();
    calculateTotalQty();

    $('#purchase_order_number').focus();
}

