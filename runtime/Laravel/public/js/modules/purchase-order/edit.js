$(document).ready(function () {
    $('#purchase_order_id').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#purchase_order_id').parent(),        
    });
    $('#purchase_order_id').on('change', function () {
        const purchaseOrderId = $(this).val();
        getPurchaseOrder(purchaseOrderId);
    });
    $('#purchase_order_id').trigger('change');
});

function getPurchaseOrder(purchaseOrderId) {    
    if (!purchaseOrderId) {
        return;
    }
    $.ajax({
        url: getPurchaseOrderUrl.replace(':id', purchaseOrderId),
        type: 'GET',
        beforeSend: function () {
            showLoader('Fetching Purchase Order...');
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
    let orderDate = data.order_date ? formatDateToDMY(data.order_date) : '';
    orderDate ? $('#order_date').val(orderDate) : $('#order_date').val('');

    let deliveryDays = data.delivery_days ? data.delivery_days : '';
    deliveryDays ? $('#delivery_days').val(deliveryDays) : $('#delivery_days').val('');

    let dueDate = data.due_date ? formatDateToDMY(data.due_date) : '';
    dueDate ? $('#due_date').val(dueDate) : $('#due_date').val('');

    let contractNo = data.contract_number ? data.contract_number : '';
    contractNo ? $('#contract_number').val(contractNo) : $('#contract_number').val('');

    let contractDate = data.contract_date ? formatDateToDMY(data.contract_date) : '';
    contractDate ? $('#contract_date').val(contractDate) : $('#contract_date').val('');

    let brokerId = data.broker_id ? data.broker_id : '';
    brokerId ? $('#broker_id').val(brokerId).trigger('change') : $('#broker_id').val('');

    let accountId = data.account_id ? data.account_id : '';
    accountId ? $('#account_id').val(accountId).trigger('change') : $('#account_id').val('');

    let destinationId = data.destination_id ? data.destination_id : '';
    destinationId ? $('#destination_id').val(destinationId).trigger('change') : $('#destination_id').val('');

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

    orderDate ? $('#order_date').select() : '';
}

