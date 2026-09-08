let savedColWidths = {};
let table = null;
let footerData = {};
let currentFilter = { order_status: "open" };

const filterFields = [
    "so_id",
    "start_date",
    "end_date",
    "account_id",
    "item_id",
    "broker_id",
    "condition_id",
    "destination_id",
    "order_status",
];

const FILTER_KEY = "sales_order_detail_filter";
const WIDTH_KEY = "sales_order_detail_col_widths";

$(document).ready(function () {
    // select2 binding
    bindSelect2();

    // date input with validation
    new DateInput("#start_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#end_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    // Restore saved filters
    restoreFilters();

    // Tabulator
    table = new Tabulator("#sales_order_detail_table", {
    height: "550px", // dynamic height that fits most ERP screens
    layout: "fitColumns",
    placeholder: `
        <div class="text-center py-5">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"
                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="icon text-muted mb-3">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                <path d="M10 12l4 4m0 -4l-4 4" />
            </svg>
            <h3 class="text-muted">No data found</h3>
            <p class="text-muted">Try adjusting your filters or search criteria</p>
        </div>
    `,

    ajaxURL: salesOrderDetailListUrl,
    ajaxParams: () => currentFilter,
    ajaxURLGenerator: (url, config, params) => {
            const normalizedFilter = { ...currentFilter };
            if (normalizedFilter.start_date) normalizedFilter.start_date = normalizeDate(normalizedFilter.start_date);
            if (normalizedFilter.end_date) normalizedFilter.end_date = normalizeDate(normalizedFilter.end_date);

            const qp = new URLSearchParams({ ...normalizedFilter });
            return `${url}?${qp}`;
        },
        ajaxResponse: (url,params, response) => {
            footerData = response.footerData || {};
            // If response is the standard {data: [...], ...} return just data if not paginated
            return response.data || response;
        },
    columns: getTableColumns(savedColWidths),
    rowFormatter: function(row) {
            var data = row.getData();
            var $rowElement = $(row.getElement()); // Wrap the DOM element with jQuery
            $rowElement.addClass('text-ellipsis');
            $rowElement.css('white-space', 'nowrap');
            $rowElement.css('overflow', 'hidden');
            $rowElement.css('text-overflow', 'ellipsis');
            if (data.rate === 'Total') {
                // Add Bootstrap classes using jQuery
                $rowElement.addClass("bg-secondary text-dark fw-bold border-top border-bottom border-dark");
            } else {
                // Remove classes for other rows (required for Virtual DOM stability)
                $rowElement.removeClass("bg-secondary text-white border-top border-bottom border-dark");
            }
        },

  });

    $("#filter_apply").on("click", function(e) {
        e.preventDefault();
        applyFilter();
    });

    $("#filter_clear").on("click", function (e) {
        e.preventDefault();
        clearFilter();
    });


function getTableColumns(savedColWidths) {
    return [
        {   title: "S. O. No.",
            field: "so_no",
            width: savedColWidths.so_no ?? 80,
            headerSort:false,
            hozAlign: "center"

        },
        {
            title: "S. O. Date.",
            field: "so_date",
            width:  110,
            headerSort:false,
            headerHozAlign: "right",
            hozAlign: "right",
            formatter: (cell) => formatDateToDMY(cell.getValue()),
        },
        {
            title: "Customer Name",
            field: "customer_name",
            width:  350,
            tooltip: true,
            formatter: function(cell) {
                const value = cell.getValue() || "";
                return `<span class="d-inline-block text-truncate" style="max-width: 100%;" title="${value}">${value}</span>`;
            }
        },
        {
            title: "P.O. No.",
            field: "purchase_order_number",
            width: savedColWidths.purchase_order_number ?? 200,
            formatter: function(cell) {
                const value = cell.getValue() || "";
                return `<span class="d-inline-block text-truncate" style="max-width: 100%;">${value}</span>`;
            }
        },
        {
            title: "Items Name",
            field: "product_name",
            width: savedColWidths.product_name ?? 150,
            formatter: function(cell) {
                const value = cell.getValue() || "";
                return `<span class="d-inline-block text-truncate" style="max-width: 100%;">${value}</span>`;
            }
        },

        {
            title: "Destination",
            field: "destination_name",
            width: 125,
            formatter: function(cell) {
                const value = cell.getValue() || "";
                return `<span class="d-inline-block text-truncate" style="max-width: 100%;">${value}</span>`;
            }
        },
        {
            title: "Rate",
            field: "rate",
            width: savedColWidths.rate ?? 110,
            headerHozAlign: "right",
            headerSort: false,
            hozAlign: "right",
            bottomCalcFormatter: function(cell) {
                return `<span class="text-dark fw-bold">GrandTotal</span>`;
            }
        },
        {
            title: "Order Qty",
            field: "qty",
            width: 130,
            headerHozAlign: "right",
            headerSort: false,
            hozAlign: "right",
            bottomCalc: () => footerData.order_qty_total || 0,
            bottomCalcFormatter: function(cell) {
                let val = cell.getValue();
                let formatted = formatIndianNumber(val);
                return `<span class="fw-bold text-dark">${formatted}</span>`;
            }
        },
        {
            title: "Invoice Date",
            field: "invoice_date",
            width:  115,
            headerSort: false,
            formatter: (cell) => formatDateToDMY(cell.getValue()),
        },

        {
            title: "GRN Number",
            field: "grn_no",
            width:  120,
            headerHozAlign: "center",
            headerSort: false,
            hozAlign: "center",
        },
        {
            title: "Invoice No.",
            field: "invoice_no",
            width: savedColWidths.invoice_no ?? 90,
            headerHozAlign: "center",
            headerSort: false,
            hozAlign: "center",
        },
        {
            title: "Vehicle No.",
            field: "vehicle_no",
            width: savedColWidths.vehicle_no ?? 120,
            headerHozAlign: "center",
            headerSort: false,
            hozAlign: "center",
        },
        {
            title: "Bags",
            field: "bags",
            width: savedColWidths.bags ?? 60,
            headerHozAlign: "right",
            hozAlign: "right",
            headerSort: false,
        },
        {
            title: "P. Qty.",
            field: "p_qty",
            width: savedColWidths.p_qty ?? 80,
            headerHozAlign: "right",
            hozAlign: "right",
            headerSort: false,
        },
        {
            title:"Bill. Qty.",
            field: "rec_qty",
            width: 130,
            headerHozAlign: "right",
            hozAlign: "right",
            headerSort: false,
            bottomCalc: () => footerData.rec_qty_total || 0,
            bottomCalcFormatter: function(cell) {
                let val = cell.getValue();
                let formatted = formatIndianNumber(val);
                return `<span class="fw-bold text-success" style="font-size:1.1em;">${formatted}</span>`;
            }
        },
        {
            title:"Rem. Qty.",
            field: "remaining_qty",
            width: 130,
            headerHozAlign: "right",
            headerSort: false,
            hozAlign: "right",
            bottomCalc: () => footerData.rem_qty_total || 0.00,
            bottomCalcFormatter: function(cell) {
                let val = cell.getValue();
                let formatted = formatIndianNumber(val);

                return `<span class="fw-bold text-danger">${formatted}</span>`;
            }
        },
    ];
}

function bindSelect2() {
    const selectIdArray = ['#so_id', '#account_id', '#item_id', '#broker_id', '#destination_id', '#condition_id', '#order_status'];
    selectIdArray.forEach(element => {
        $(element).select2({
            theme: "bootstrap-5",
            allowClear: true,
            placeholder: "Select " + element.replace('#', '').replace('.', '').replace('_id', '') + " ...",
            width:null,
        });
    });

    $(document).on("select2:open", function (e) {
        const selectElement = $(e.target);
        const searchInput = selectElement
            .data("select2")
            .$dropdown.find(".select2-search__field");

        searchInput
            .off("keydown.select2Enter")
            .on("keydown.select2Enter", (event) => {
                if (event.which === 13) {
                    event.preventDefault();
                    selectElement.select2("close");
                    moveFocusToNextField(selectElement);
                }
            });
    });
}


});


function applyFilter() {
    currentFilter = {};

    $.each(filterFields, function (i, id) {
        var el    = $("#" + id).get(0);
        var value = getFieldValue(el);

        if (value) currentFilter[id] = value;
    });

    // Default order_status to 'open' if not explicitly selected
    if (!currentFilter['order_status']) {
            currentFilter['order_status'] = 'open';
            // Also sync the dropdown UI to reflect the default
            var $statusDropdown = $("#order_status");
            if ($statusDropdown.data("select2")) {
                $statusDropdown.val("open").trigger("change.select2");
            } else {
                $statusDropdown.val("open");
            }
        }

    localStorage.setItem(FILTER_KEY, JSON.stringify(currentFilter));

    if (table) table.setData();
}

function clearFilter() {
    $.each(filterFields, function (i, id) {
        var el = $("#" + id).get(0);
        setFieldValue(el, "");
    });

    currentFilter = { order_status: 'open' };
    localStorage.removeItem(FILTER_KEY);

    var $statusDropdown = $("#order_status");
    if ($statusDropdown.length) {
        if ($statusDropdown.data("select2")) {
            $statusDropdown.val("open").trigger("change.select2");
        } else {
            $statusDropdown.val("open");
        }
    }

    if (table) table.setData();
}

/** Restore filters on page load */
function restoreFilters() {
    var saved = localStorage.getItem(FILTER_KEY);
    if (saved) {
        currentFilter = JSON.parse(saved);
    }

    // Always default to 'open' if not explicitly selected/saved
    if (!currentFilter['order_status']) {
        currentFilter['order_status'] = 'open';
    }

    $.each(currentFilter, function (id, value) {
        var el = $("#" + id).get(0);
        setFieldValue(el, value);
    });
}

function getFieldValue(el) {
    if (!el) return "";
    return $(el).val();
}

function setFieldValue(el, value) {
    if (!el) return;
    var $el = $(el);
    $el.val(value);
    // If it's a select2 dropdown, trigger change to update UI
    if ($el.hasClass("select2-hidden-accessible")) {
        $el.trigger("change");
    }
}

function normalizeDate(dateStr) {
    if (!dateStr || dateStr === "-") return "";

    // If already YYYY-MM-DD, return as is
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr;

    if (typeof formatDateToYMD === "function") {
        return formatDateToYMD(dateStr);
    }
    return dateStr;
}

  document.addEventListener("click", function (e) {
    const action = e.target.closest(".dropdown-item");
    if (!action) return;

    const type = action.dataset.type; // print / export
    const route = action.dataset.route;
    const format = action.dataset.format;

    if (!type) return;
    switch (type) {
      case "print":
        printReport(route, { format: format, currentFilter });
        break;

      case "excel":
        downloadExcel(route, { currentFilter });
        break;
    }
  });
