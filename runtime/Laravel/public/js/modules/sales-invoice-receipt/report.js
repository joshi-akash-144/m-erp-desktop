let reportTable = null;
let reportFilter = {};

$(document).ready(function () {
    bindSelect2();

    new DateInput("#start_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#end_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    $("#filter_form").on("submit", function (e) {
        e.preventDefault();
        applyFilter();
    });

    $("#filter_clear").on("click", function (e) {
        e.preventDefault();
        clearFilter();
    });

    $("#btn_print").on("click", function () {
        printReport(salesInvoiceReceiptReportPrintUrl, { currentFilter: reportFilter });
    });
});

function bindSelect2() {
    $("#customer_id").select2({
        theme: "bootstrap-5",
        allowClear: true,
        placeholder: "All Customers",
        width: null,
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

function buildTable() {
    reportTable = new Tabulator("#sales_invoice_receipt_report_table", {
        height: "550px",
        layout: "fitColumns",
        placeholder: `
            <div class="text-center py-5">
                <h3 class="text-muted">No receipts found</h3>
                <p class="text-muted">Try adjusting your filters.</p>
            </div>
        `,
        pagination: true,
        paginationMode: "remote",
        paginationSize: 50,
        paginationSizeSelector: [20, 50, 100, 200],
        paginationDataSent: { page: "page", size: "size" },
        paginationDataReceived: { last_page: "last_page", data: "data", total: "total" },
        ajaxURL: salesInvoiceReceiptReportDataUrl,
        ajaxParams: () => reportFilter,
        ajaxURLGenerator: (url, config, params) => {
            const qp = new URLSearchParams({ ...reportFilter, page: params.page || 1, size: params.size || 50 });
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
                        inv_date: "", truck_no: "", bags: totalBags, wt: totalWt, inv_no: "", rem_qty: "", rem_days: "",
                        received_date: "", received_by_name: ""
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
                    inv_date: "", truck_no: "", bags: totalBags, wt: totalWt, inv_no: "", rem_qty: "", rem_days: "",
                    received_date: "", received_by_name: ""
                });
            }

            response.data = newData;
            return response;
        },
        rowFormatter: function(row) {
            var data = row.getData();
            if (data.is_subtotal) {
                var el = row.getElement();
                el.style.setProperty("background-color", "#ffe0b2", "important");
                el.style.setProperty("font-weight", "bold", "important");
                el.style.setProperty("color", "#d84315", "important");
            }
        },
        columns: getTableColumns(),
    });
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
        { title: '"', formatter: "rownum", width: 50, headerSort: false, hozAlign: "center" },
        { title: "DATE", field: "so_date", width: 100, headerSort: false, hozAlign: "center" },
        { title: "DALAL N", field: "dalal", width: 100, headerSort: false, hozAlign: "center" },
        { title: "P.O NO", field: "po_no", width: 90, headerSort: false, hozAlign: "center" },
        { title: "DEL STION", field: "delivery", minWidth: 120, headerSort: false, hozAlign: "center" },
        { title: "ITEM", field: "item", minWidth: 120, headerSort: false, hozAlign: "center" },
        { title: "RATE", field: "rate", width: 90, headerSort: false, hozAlign: "right", headerHozAlign: "right" },
        { title: "DAYS", field: "days", width: 70, headerSort: false, hozAlign: "center" },
        { title: "QTY", field: "qty", width: 90, headerSort: false, hozAlign: "right", headerHozAlign: "right", formatter: "money", formatterParams: { precision: 3, symbol: "" } },
        { title: "DATE", field: "inv_date", width: 100, headerSort: false, hozAlign: "center" },
        { title: "TRUCK NO", field: "truck_no", width: 120, headerSort: false, hozAlign: "center" },
        { title: "BAGS", field: "bags", width: 80, headerSort: false, hozAlign: "center" },
        { title: "WT", field: "wt", width: 100, headerSort: false, hozAlign: "right", headerHozAlign: "right", formatter: "money", formatterParams: { precision: 3, symbol: "" }, bottomCalc: customSumCalc, bottomCalcFormatter: "money", bottomCalcFormatterParams: { precision: 3, symbol: "" } },
        { title: "IN NO", field: "inv_no", width: 100, headerSort: false, hozAlign: "right", headerHozAlign: "right" },
        { title: "REM. QTY", field: "rem_qty", width: 100, headerSort: false, hozAlign: "right", headerHozAlign: "right", formatter: "money", formatterParams: { precision: 3, symbol: "" } },
        { title: "REM. DAYS", field: "rem_days", width: 100, headerSort: false, hozAlign: "center" },
        { title: "REC. DATE", field: "received_date", width: 100, headerSort: false, hozAlign: "center" },
        { title: "REC. BY", field: "received_by_name", width: 120, headerSort: false, hozAlign: "center" },
    ];
}

function formatDateSafe(value) {
    if (!value) return "—";
    return typeof formatDateToDMY === "function" ? formatDateToDMY(value) : value;
}

function applyFilter() {
    reportFilter = {};

    var customerId = $("#customer_id").val();
    if (!customerId) {
        showToast("error", "Please select a customer first.");
        return;
    }
    
    reportFilter.customer_id = customerId;

    var startDate = $("#start_date").val().trim();
    var endDate = $("#end_date").val().trim();
    if (startDate) reportFilter.start_date = normalizeDate(startDate);
    if (endDate) reportFilter.end_date = normalizeDate(endDate);

    if (!reportTable) {
        buildTable();
    } else {
        reportTable.setData();
    }
}

function clearFilter() {
    $("#customer_id").val("").trigger("change");
    $("#start_date").val("");
    $("#end_date").val("");
    reportFilter = {};
    if (reportTable) {
        reportTable.clearData();
    }
}

function normalizeDate(dateStr) {
    if (!dateStr) return "";
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr;
    return typeof formatDateToYMD === "function" ? formatDateToYMD(dateStr) : dateStr;
}
