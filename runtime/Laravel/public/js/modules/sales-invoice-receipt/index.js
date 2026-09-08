let table = null;
let currentFilter = {};
let isSaving = false;

$(document).ready(function () {
    bindSelect2();

    new DateInput("#start_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#end_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    $("#customer_id").on("change", function () {
        var customerId = $(this).val();
        $("#btn_save").prop("disabled", true);

        if (!customerId) {
            if (table) table.clearData();
            updateSelectedCount();
        }
    });

    $("#filter_form").on("submit", function (e) {
        e.preventDefault();
        applyFilter();
    });

    $("#filter_clear").on("click", function (e) {
        e.preventDefault();
        clearFilter();
    });

    $("#btn_save").on("click", function () {
        saveSelectedInvoices();
    });

    $("#quick_scan").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            var val = $(this).val().trim().toLowerCase();
            if (!val || !table) return;

            var rows = table.getRows();
            var found = false;

            for (var i = 0; i < rows.length; i++) {
                var data = rows[i].getData();
                if (data.is_subtotal) continue;

                var invNo = (data.inv_no || "").toString().toLowerCase();
                var truckNo = (data.truck_no || "").toString().toLowerCase();

                if (invNo === val || truckNo === val) {
                    rows[i].select();
                    rows[i].scrollTo("center");
                    found = true;
                    break;
                }
            }

            if (!found) {
                showToast("error", "Invoice No not found!");
            }

            $(this).val("");
            $(this).focus();
        }
    });
});

function bindSelect2() {
    var selectIdArray = ["#customer_id", "#destination_id", "#item_id"];
    selectIdArray.forEach(function (element) {
        $(element).select2({
            theme: "bootstrap-5",
            // allowClear: true,
            // placeholder: "Select ...",
            // width: null,
        });
    });

    $(document).on("select2:open", function (e) {
        var selectElement = $(e.target);
        var searchInput = selectElement
            .data("select2")
            .$dropdown.find(".select2-search__field");

        searchInput
            .off("keydown.select2Enter")
            .on("keydown.select2Enter", function (event) {
                if (event.which === 13) {
                    event.preventDefault();
                    selectElement.select2("close");
                    moveFocusToNextField(selectElement);
                }
            });
    });
}

function applyFilter() {
    var customerId = $("#customer_id").val();

    if (!customerId) {
        showToast("error", "Please select a customer first.");
        return;
    }

    currentFilter = { customer_id: customerId };

    var destinationId = $("#destination_id").val();
    var itemId = $("#item_id").val();
    var startDate = $("#start_date").val().trim();
    var endDate = $("#end_date").val().trim();

    if (startDate && endDate) {
        var start = new Date(normalizeDate(startDate));
        var end = new Date(normalizeDate(endDate));

        if (start > end) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Date Range',
                text: 'Start date cannot be greater than end date.'
            });
            return;
        }

        var diffTime = Math.abs(end - start);
        var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays > 31) {
            Swal.fire({
                icon: 'warning',
                title: 'Date Range Too Large',
                text: 'Please select a date range of 31 days or less.'
            });
            return;
        }
    }

    if (destinationId) currentFilter.destination_id = destinationId;
    if (itemId) currentFilter.item_id = itemId;
    if (startDate) currentFilter.start_date = normalizeDate(startDate);
    if (endDate) currentFilter.end_date = normalizeDate(endDate);

    if (!table) {
        buildTable();
    } else {
        table.setData();
    }
}

function clearFilter() {
    $("#customer_id").val("").trigger("change");
    $("#destination_id").val("").trigger("change");
    $("#item_id").val("").trigger("change");
    $("#start_date").val("");
    $("#end_date").val("");
}

function normalizeDate(dateStr) {
    if (!dateStr) return "";
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr;
    return typeof formatDateToYMD === "function" ? formatDateToYMD(dateStr) : dateStr;
}

function buildTable() {
    table = new Tabulator("#sales_invoice_receipt_table", {
        height: "500px",
        layout: "fitColumns",
        placeholder: `
            <div class="text-center py-5">
                <h3 class="text-muted">No pending invoices found</h3>
                <p class="text-muted">Select a customer and search to load pending invoices.</p>
            </div>
        `,
        pagination: false,
        ajaxURL: salesInvoiceReceiptPendingUrl,
        ajaxParams: () => currentFilter,
        ajaxURLGenerator: (url, config, params) => {
            const qp = new URLSearchParams({ ...currentFilter, size: 1000000 });
            return `${url}?${qp.toString()}`;
        },
        ajaxResponse: function (url, params, response) {
            var data = response.data || [];
            var newData = [];
            var currentPo = null;
            var totalWt = 0;
            var totalBags = 0;

            for (var i = 0; i < data.length; i++) {
                var row = data[i];
                var po = row.raw_so_id;

                if (currentPo !== null && currentPo !== po) {
                    newData.push({
                        id: 'subtotal_' + i,
                        is_subtotal: true,
                        po_no: "Total",
                        so_date: "", dalal: "", delivery: "", item: "", rate: "", days: "", qty: "",
                        inv_date: "", truck_no: "", bags: totalBags, wt: totalWt, inv_no: "", rem_qty: "", rem_days: ""
                    });
                    totalWt = 0;
                    totalBags = 0;
                }

                newData.push(row);
                totalWt += parseFloat(row.wt || 0);
                totalBags += parseFloat(row.bags || 0);
                currentPo = po;
            }

            if (data.length > 0) {
                newData.push({
                    id: 'subtotal_end',
                    is_subtotal: true,
                    po_no: "Total",
                    so_date: "", dalal: "", delivery: "", item: "", rate: "", days: "", qty: "",
                    inv_date: "", truck_no: "", bags: totalBags, wt: totalWt, inv_no: "", rem_qty: "", rem_days: ""
                });
            }

            return newData;
        },
        rowFormatter: function (row) {
            var data = row.getData();
            if (data.is_subtotal) {
                var el = row.getElement();
                el.style.setProperty("background-color", "#ffe0b2", "important");
                el.style.setProperty("font-weight", "bold", "important");
                el.style.setProperty("color", "#d84315", "important");

                // Hide checkbox for subtotal rows
                var cell = row.getCell("select");
                if (cell) cell.getElement().innerHTML = "";
            }
        },
        columns: getTableColumns(),
    });

    table.on("dataLoaded", updateSelectedCount);
    table.on("rowSelectionChanged", updateSelectedCount);
}

function customSumCalc(values, data, calcParams) {
    var sum = 0;
    for (var i = 0; i < data.length; i++) {
        if (!data[i].is_subtotal) {
            var val = parseFloat(values[i]);
            if (!isNaN(val)) {
                sum += val;
            }
        }
    }
    return sum;
}

function getTableColumns() {
    return [
        {
            title: "",
            field: "select",
            formatter: "rowSelection",
            titleFormatter: "rowSelection",
            hozAlign: "center",
            headerSort: false,
            width: 45,
            cellClick: function (e, cell) {
                if (!cell.getRow().getData().is_subtotal) {
                    cell.getRow().toggleSelect();
                }
            },
        },
        { title: "PO DATE", field: "so_date", width: 120, headerSort: false, hozAlign: "center" },
        { title: "Broker", field: "dalal", width: 100, headerSort: false, hozAlign: "center" },
        { title: "P.O NO", field: "po_no", width: 90, headerSort: false, hozAlign: "center" },
        { title: "DEL STATION", field: "delivery", minWidth: 80, headerSort: false, hozAlign: "left" },
        { title: "ITEM", field: "item", minWidth: 100, headerSort: false, hozAlign: "left" },
        { title: "RATE", field: "rate", width: 140, headerSort: false, hozAlign: "right", headerHozAlign: "right" },
        { title: "DAYS", field: "days", width: 70, headerSort: false, hozAlign: "center" },
        { title: "QTY", field: "qty", width: 120, headerSort: false, hozAlign: "right", headerHozAlign: "right", formatter: "money", formatterParams: { precision: 3, symbol: "" } },
        { title: "INV DATE", field: "inv_date", width: 120, headerSort: false, hozAlign: "center" },
        { title: "TRUCK NO", field: "truck_no", width: 130, headerSort: false, hozAlign: "center" },
        { title: "BAGS", field: "bags", width: 80, headerSort: false, hozAlign: "center" },
        { title: "WT", field: "wt", width: 130, headerSort: false, hozAlign: "right", headerHozAlign: "right", formatter: "money", formatterParams: { precision: 3, symbol: "" }, bottomCalc: customSumCalc, bottomCalcFormatter: "money", bottomCalcFormatterParams: { precision: 3, symbol: "" } },
        { title: "INV NO", field: "inv_no", width: 100, headerSort: false, hozAlign: "right", headerHozAlign: "right" },
        { title: "REM. QTY", field: "rem_qty", width: 130, headerSort: false, hozAlign: "right", headerHozAlign: "right", formatter: "money", formatterParams: { precision: 3, symbol: "" } },
        { title: "REM. DAYS", field: "rem_days", width: 100, headerSort: false, hozAlign: "center" },
    ];
}

function formatDateSafe(value) {
    if (!value) return "—";
    return typeof formatDateToDMY === "function" ? formatDateToDMY(value) : value;
}

function updateSelectedCount() {
    var selectedRows = table ? table.getSelectedRows() : [];
    var validRows = selectedRows.filter(r => !r.getData().is_subtotal);
    var count = validRows.length;

    var totalWt = validRows.reduce((sum, row) => sum + parseFloat(row.getData().wt || 0), 0);

    var $selLabel = $("#sel_label");
    var labelText = count + " invoice" + (count !== 1 ? "s" : "") + " selected";

    if (count > 0) {
        $selLabel.html('<span style="padding: 6px 15px; font-weight: bold; color: #000; background-color: #fff3cd; border-radius: 4px; font-size: 16px;">' + labelText + ' <span class="ms-3">| Total Selected WT: <strong>' + totalWt.toFixed(3) + '</strong></span></span>').removeClass("text-muted");
    } else {
        $selLabel.html(labelText).addClass("text-muted");
    }

    $("#btn_save").prop("disabled", count === 0 || isSaving);
}

function saveSelectedInvoices() {
    var customerId = $("#customer_id").val();
    var selectedRows = table ? table.getSelectedData() : [];

    if (!customerId) {
        showToast("error", "Please select a customer first.");
        return;
    }

    if (selectedRows.length === 0) {
        showToast("error", "Please select at least one invoice.");
        return;
    }

    if (isSaving) return;

    var validRows = selectedRows.filter(r => !r.is_subtotal);
    var invoiceIds = validRows.map((row) => row.id);
    var totalWt = validRows.reduce((sum, row) => sum + parseFloat(row.wt || 0), 0);

    Swal.fire({
        title: 'Confirm Save',
        html: `Are you sure you want to process Hisab Bill for <b>${invoiceIds.length}</b> invoice(s)?<br><br>Total WT: <b>${totalWt.toFixed(3)}</b>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Save',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
    }).then((result) => {
        if (result.isConfirmed) {
            isSaving = true;
            var $btn = $("#btn_save");
            var originalHtml = $btn.html();
            $btn.prop("disabled", true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

            $.ajax({
                url: salesInvoiceReceiptStoreUrl,
                type: "POST",
                contentType: "application/json",
                data: JSON.stringify({
                    customer_id: customerId,
                    sales_invoice_ids: invoiceIds,
                }),
                success: function (res) {
                    showToast("success", res.message || "Invoices marked as received.");
                    table.setData();
                },
                error: function (xhr) {
                    var json = xhr.responseJSON || {};
                    var message = json.message || "Failed to save. Please try again.";
                    if (json.errors) {
                        var firstError = Array.isArray(Object.values(json.errors)[0])
                            ? Object.values(json.errors)[0][0]
                            : Object.values(json.errors)[0];
                        if (firstError) message = firstError;
                    }
                    showToast("error", message);
                },
                complete: function () {
                    isSaving = false;
                    $btn.prop("disabled", false).html(originalHtml);
                },
            });
        }
    });
}
