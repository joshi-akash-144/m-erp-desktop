// ===========================================================
// GLOBALS
// ===========================================================
let table;
let currentFilter = { delivery_status: "open" };
let currentPermissions = {};

const FILTER_KEY = "delivery_challan_filter";
const WIDTH_KEY = "delivery_challan_col_widths";

const filterFields = [
    "dc_no",
    "dc_number",
    "start_date",
    "end_date",
    "account_id",
    "item_id",
    "broker_id",
    "condition_id",
    "destination_id",
    "delivery_status"
];

// ===========================================================
// UTILITY FUNCTIONS
// ===========================================================

/** Set field value safely (Select2 / normal input) */
function setFieldValue(el, value) {
    if (!el) return;
    var $el = $(el);

    if ($el.hasClass("select2-hidden-accessible")) {
        $el.val(value).trigger("change");
    } else {
        $el.val(value);
    }
}

/** Read field value safely */
function getFieldValue(el) {
    if (!el) return "";
    var $el = $(el);

    if ($el.hasClass("select2-hidden-accessible")) {
        return $el.val() || "";
    }
    return ($el.val() || "").trim();
}

/** Convert DD-MM-YYYY → YYYY-MM-DD */
function normalizeDate(d) {
    if (!d || d === "__-__-____") return "";
    var parts = d.split("-");
    var day = parts[0], month = parts[1], year = parts[2];
    return year && year.length === 4 ? (day + "-" + month + "-" + year) : "";
}

/** Render multi-row details */
function renderDetailsList(details, key, numeric, decimal, customFormatter) {
    numeric = numeric !== undefined ? numeric : false;
    decimal = decimal !== undefined ? decimal : 2;
    customFormatter = customFormatter !== undefined ? customFormatter : null;

    if (!Array.isArray(details) || details.length === 0)
        return "<span class='text-muted'>--</span>";

    var bullet = details.length > 1;
    var html = "";

    $.each(details, function (i, row) {
        var val = key.split(".").reduce(function (o, k) {
            return o && o[k] !== undefined ? o[k] : undefined;
        }, row);

        if (customFormatter) {
            if (val !== undefined && val !== null) {
                val = customFormatter(val);
            }
        } else if (numeric && val !== undefined && val !== null) {
            val = Number(val).toFixed(decimal);
        }

        html += "<div class=\"" + (numeric ? "text-end" : "") + "\">" +
            (bullet ? "<span class=\"me-1 text-muted\">\u2022</span>" : "") +
            (val !== null && val !== undefined ? val : "--") +
            "</div>";
    });

    return "<div>" + html + "</div>";
}

// ===========================================================
// FILTER HANDLING
// ===========================================================
function applyFilter() {
    currentFilter = {};

    $.each(filterFields, function (i, id) {
        var el = $("#" + id).get(0);
        var value = getFieldValue(el);

        if (value) currentFilter[id] = value;
    });

    // process date fields
    $.each(["start_date", "end_date"], function (i, k) {
        if (currentFilter[k]) {
            var formatted = normalizeDate(currentFilter[k]);
            if (formatted) {
                currentFilter[k] = formatted;
            } else {
                delete currentFilter[k];
            }
        }
    });

    localStorage.setItem(FILTER_KEY, JSON.stringify(currentFilter));

    if (table) table.setData();
}

function clearFilter() {
    $.each(filterFields, function (i, id) {
        var el = $("#" + id).get(0);
        setFieldValue(el, "");
    });

    currentFilter = {};
    localStorage.removeItem(FILTER_KEY);

    if (table) table.setData();
}

/** Restore filters on page load */
function restoreFilters() {
    var saved = localStorage.getItem(FILTER_KEY);
    if (!saved) return;

    currentFilter = JSON.parse(saved);

    $.each(currentFilter, function (id, value) {
        var el = $("#" + id).get(0);
        setFieldValue(el, value);
    });
}

// ===========================================================
// COLUMN WIDTH SAVING
// ===========================================================
function saveColWidths() {
    var widths = {};
    table.getColumns().forEach(function (col) {
        var field = col.getField();
        if (field) widths[field] = col.getWidth();
    });
    localStorage.setItem(WIDTH_KEY, JSON.stringify(widths));
}

function getSavedWidths() {
    try {
        return JSON.parse(localStorage.getItem(WIDTH_KEY)) || {};
    } catch (e) {
        return {};
    }
}

// ===========================================================
// TABLE COLUMNS
// ===========================================================
function getColumns(saved) {
    return [
        {
            title: "DC Serial.",
            field: "challan_serial",
            width: saved.challan_serial ?? 150,
            hozAlign: "center",
        },

        {
            title: "DC No.",
            field: "challan_number",
            width: saved.challan_number ?? 150,
            hozAlign: "left",
        },

        {
            title: "S.O. No.",
            field: "details",
            headerSort: false,
            headerHozAlign: "right",
            hozAlign: "right",
            width: 100,
            formatter: function (cell) {
                return renderDetailsList(cell.getValue(), "sales_order_serial");
            },
        },
        {
            title: "Date",
            field: "challan_date",
            headerSort: false,
            width: saved.challan_date ?? 120,
            formatter: function (c) {
                return formatDateToDMY(c.getValue());
            },
        },

        {
            title: "Customer Name",
            field: "account.name",
            headerSort: false,
            width: saved.account ?? 450,
            formatter: function (cell) {
                var accountName = cell.getValue();
                var rowData = cell.getData();
                var city = (rowData.account && rowData.account.city) ? rowData.account.city : "";

                if (accountName == null) return "";

                var formattedName = String(accountName)
                    .replace(/_/g, " ")
                    .toUpperCase()
                    .replace(/\b\w/g, function (ch) { return ch.toUpperCase(); });

                if (city) {
                    return "<span class=\"fw-semibold text-truncate\">" + formattedName + " (" + city.toUpperCase() + ") </span>";
                } else {
                    return "<span class=\"fw-semibold text-truncate\">" + formattedName + " </span>";
                }
            },
        },

        {
            title: "Broker Name",
            field: "broker.name",
            headerSort: false,
            width: saved.broker ?? 200,
            formatter: function (cell) {
                var brokerName = cell.getValue();
                var rowData = cell.getData();
                var city = (rowData.broker && rowData.broker.city) ? rowData.broker.city : "";

                if (brokerName == null) return "";

                var formattedName = String(brokerName)
                    .replace(/_/g, " ")
                    .toUpperCase()
                    .replace(/\b\w/g, function (ch) { return ch.toUpperCase(); });

                if (city) {
                    return "<span class=\"text-truncate\">" + formattedName + " (" + city.toUpperCase() + ") </span>";
                } else {
                    return "<span class=\"text-truncate\">" + formattedName + " </span>";
                }
            },
        },

        {
            title: "Items Name",
            field: "details",
            headerSort: false,
            width: saved.items ?? 200,
            formatter: function (c) {
                var htmlString = renderDetailsList(c.getValue(), "item.name");
                var $tempDiv = $("<div>").html(htmlString);

                var $children = $tempDiv.children().first().children();
                var items = [];
                $children.each(function () {
                    var txt = $(this).text().replace("\u2022", "").trim();
                    if (txt !== "" && txt !== "--") items.push(txt);
                });

                if (items.length === 0) return "<span class='text-muted'>--</span>";

                var showBullet = items.length > 1;
                var content = $.map(items, function (item) {
                    return "<div class=\"text-truncate\" style=\"max-width:100%;\" title=\"" +
                        item.replace(/"/g, "&quot;") + "\">" +
                        (showBullet ? "<span class=\"me-1 text-muted\">\u2022</span>" : "") +
                        item +
                        "</div>";
                }).join("");

                return "<div class=\"d-block w-100\">" + content + "</div>";
            },
        },

        {
            title: "Destination",
            field: "details",
            headerSort: false,
            width: saved.destination ?? 200,
            formatter: function (c) {
                var htmlString = renderDetailsList(c.getValue(), "destination.name");
                var $tempDiv = $("<div>").html(htmlString);

                var $children = $tempDiv.children().first().children();
                var items = [];
                $children.each(function () {
                    var txt = $(this).text().replace("\u2022", "").trim();
                    if (txt !== "" && txt !== "--") items.push(txt);
                });

                if (items.length === 0) return "<span class='text-muted'>--</span>";

                var showBullet = items.length > 1;
                var content = $.map(items, function (item) {
                    return "<div class=\"text-truncate\" style=\"max-width:100%;\" title=\"" +
                        item.replace(/"/g, "&quot;") + "\">" +
                        (showBullet ? "<span class=\"me-1 text-muted\">\u2022</span>" : "") +
                        item +
                        "</div>";
                }).join("");

                return "<div class=\"d-block w-100\">" + content + "</div>";
            },
        },

        {
            title: "Condition",
            field: "details",
            headerSort:false,
            width: saved.condition ?? 200,
            formatter: function (c) {
                var htmlString = renderDetailsList(c.getValue(), "condition.name");
                var $tempDiv = $("<div>").html(htmlString);

                var $children = $tempDiv.children().first().children();
                var items = [];
                $children.each(function () {
                    var txt = $(this).text().replace("\u2022", "").trim();
                    if (txt !== "" && txt !== "--") items.push(txt);
                });

                if (items.length === 0) return "<span class='text-muted'>--</span>";

                var showBullet = items.length > 1;
                var content = $.map(items, function (item) {
                    return "<div class=\"text-truncate\" style=\"max-width:100%;\" title=\"" +
                        item.replace(/"/g, "&quot;") + "\">" +
                        (showBullet ? "<span class=\"me-1 text-muted\">\u2022</span>" : "") +
                        item +
                        "</div>";
                }).join("");

                return "<div class=\"d-block w-100\">" + content + "</div>";
            },
            bottomCalcFormatter: function (cell) {
                return `<div class="text-dark fw-bold" style="text-align: right; width: 100%; padding-right: 5px;">Total</div>`;
            }
        },

        {
            title: "Party Qty",
            field: "details_party_qty",
            headerHozAlign: "right",
            headerSort: false,
            width: saved.party_qty ?? 130,
            hozAlign: "right",
            // formatter: function (c) {
            //     return renderDetailsList(c.getValue(), "party_quantity", true, DECIMALS.QTY);
            // },
            bottomCalc: function (values, data, calcParams) {
                let total = 0;
                data.forEach(row => {
                    const details = row.details || [];
                    details.forEach(item => {
                        const val = parseFloat(item.party_quantity);
                        if (!isNaN(val)) total += val;
                    });
                });
                return total > 0 ? `<span class="fw-bold text-danger">${formatIndianNumber(total, typeof DECIMALS !== 'undefined' ? DECIMALS.QTY : 3)}</span>` : "";
            },
            bottomCalcFormatter: "html",
            formatter: (cell) => {
                const details = cell.getData().details || [];
                return renderDetailsList(
                    details,
                    "party_quantity",
                    true,
                    DECIMALS.QTY,
                    function (v) { return formatIndianNumber(v, DECIMALS.QTY); }
                );
            },
        },

        {
            title: "Qty",
            field: "details_qty",
            headerHozAlign: "right",
            headerSort: false,
            width: saved.qty ?? 100,
            hozAlign: "right",
            // formatter: function (c) {
            //     return renderDetailsList(c.getValue(), "quantity", true, DECIMALS.QTY);
            // },
            bottomCalc: function (values, data, calcParams) {
                let total = 0;
                data.forEach(row => {
                    const details = row.details || [];
                    details.forEach(item => {
                        const val = parseFloat(item.quantity);
                        if (!isNaN(val)) total += val;
                    });
                });
                return total > 0 ? `<span class="fw-bold text-dark">${formatIndianNumber(total, typeof DECIMALS !== 'undefined' ? DECIMALS.QTY : 3)}</span>` : "";
            },
            bottomCalcFormatter: "html",
            formatter: (cell) => {
                const details = cell.getData().details || [];
                return renderDetailsList(
                    details,
                    "quantity",
                    true,
                    DECIMALS.QTY,
                    function (v) { return formatIndianNumber(v, DECIMALS.QTY); }
                );
            },
        },

        {
            title: "Incl. Rate",
            field: "details",
            headerHozAlign: "right",
            hozAlign: "right",
            headerSort: false,
            width: saved.inc_rate ?? 130,
            formatter: function (c) {
                return renderDetailsList(
                    c.getValue(),
                    "inclusive_rate",
                    true,
                    DECIMALS.INCLUSIVE_RATE,
                    function (v) { return formatIndianNumber(v, DECIMALS.INCLUSIVE_RATE); }
                );
            },
        },

        {
            title: "Rate",
            field: "details",
            headerHozAlign: "right",
            headerSort: false,
            width: saved.rate ?? 130,
            hozAlign: "right",
            formatter: function (c) {
                return renderDetailsList(
                    c.getValue(),
                    "rate",
                    true,
                    DECIMALS.RATE,
                    function (v) { return formatIndianNumber(v, DECIMALS.RATE); }
                );
            },
        },

        // {
        //     title: "Total",
        //     field: "total_amount",
        //     headerHozAlign: "right",
        //     headerSort: false,
        //     width: saved.total ?? 150,
        //     hozAlign: "right",
        //     formatter: function (c) {
        //         var totalAmount = c.getValue();
        //         return formatIndianNumber(totalAmount !== null && totalAmount !== undefined ? totalAmount : "0.00");
        //     },
        // },

        // {
        //     title: "QC Status",
        //     field: "qc_status",
        //     width: saved.qc_status ?? 130,
        //     formatter: function (c) {
        //         var val = c.getValue() || "--";
        //         var cls = { passed: "bg-success-lt", failed: "bg-danger-lt", pending: "bg-orange-lt" }[val] || "bg-secondary-lt";
        //         return "<span class=\"badge " + cls + " fw-semibold\">" + upperCase(val) + "</span>";
        //     },
        // },

        {
            title: "DC Status",
            field: "challan_status",
            headerSort:false,
            width: saved.challan_status ?? 130,
            headerHozAlign: "right",
            hozAlign: "center",
            vertAlign: "middle",
            formatter: function (c) {
                var val = c.getValue() || "--";
                var map = {
                    open: "bg-success-lt",
                    billed: "badge bg-purple-lt",
                    rejected: "bg-red-lt",
                    hold: "bg-blue-lt",
                    close: "bg-danger-lt",
                    cancel: "bg-orange-lt",
                };
                var cls = map[val] || "bg-secondary-lt";
                return "<span class=\"badge " + cls + " fw-semibold\">" + upperCase(val) + "</span>";
            },
        },

        {
            title: "Actions",
            field: "actions",
            width: 130,
            hozAlign: "center",
            vertAlign: "middle",
            headerHozAlign: "center",
            headerSort: false,
            frozen: true,
            formatter: (cell) => {
                const row = cell.getData();
                const canView = currentPermissions.view ?? false;
                const canEdit = currentPermissions.update ?? false;
                const canPrint = currentPermissions.print ?? false;

                // const canDelete = currentPermissions.delete ?? false;

                const viewIcon = icons.view;
                const editIcon = icons.edit;
                const printIcon = icons.print;

                let actions = '<div class="d-flex gap-2 align-items-center justify-content-center h-100 w-100">';

                if (canView) {
                    actions += `
              <span class="erp-btn-icon view" data-id="${row.id}" title="View Delivery Challan">
                ${viewIcon}
              </span>`;
                }

                if (canEdit) {
                    actions += `
              <span class="erp-btn-icon edit" data-id="${row.id}" title="Edit Delivery Challan">
                ${editIcon}
              </span>`;
                }

                if (canPrint) {
                  actions += `
                      <span class="erp-btn-icon print" data-id="${row.id}" title="Print Delivery Challan">
                        ${printIcon}
                      </span>`;
                }

                actions += "</div>";
                return actions;
            },
        },
    ];
}


// ===========================================================
// SELECT2 INIT
// ===========================================================
function bindSelect2() {
    var selectIdArray = [
        "#dc_number", "#account_id", "#item_id", "#broker_id", "#condition_id", "#destination_id", "#delivery_status", "#qc_status"
    ];

    $.each(selectIdArray, function (i, element) {
        $(element).select2({
            theme: "bootstrap-5",
            allowClear: true,
            placeholder: "Select ...",
            width: null,
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

// ===========================================================
// BOOTSTRAP
// ===========================================================
$(document).ready(function () {

    // ----- Select2 -----
    bindSelect2();

    // ----- Date inputs -----
    new DateInput("#start_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#end_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    // ----- Restore saved filters -----
    restoreFilters();

    // ----- Tabulator table -----
    var savedWidths = getSavedWidths();

    table = new Tabulator("#delivery_challan_table", {
        height: "550px",
        layout: "fitColumns",

        placeholder: "<div class=\"text-center py-5\"><h3 class=\"text-muted\">No data found</h3><p class=\"text-muted\">Try adjusting your filters or search criteria</p></div>",

        pagination: true,
        paginationMode: "remote",
        paginationSize: 50,
        paginationSizeSelector: [10, 20, 50, 100, 300, true],
        paginationDataSent: {
            page: "page",
            size: "size",
        },
        paginationDataReceived: {
            last_page: "last_page",
            data: "data",
            total: "total",
        },

        ajaxURL: deliveryChallanListUrl,

        ajaxParams: function () {
            return currentFilter;
        },

        ajaxURLGenerator: function (url, config, params) {
            var page = params.page || 1;
            var size = params.size || 50;
            var qp = new URLSearchParams($.extend({}, currentFilter, { page: page, size: size }));
            return url + "?" + qp.toString();
        },

        ajaxResponse: function (url, params, response) {

            if (response.permissions) currentPermissions = response.permissions;
            return response;
        },

        columns: getColumns(savedWidths),
    });

    table.on("columnResized", saveColWidths);

    // ----- Action button clicks (view / edit / delete) -----
    $(document).on("click", ".erp-btn-icon", function (e) {
        var $btn = $(this);
        var id = $btn.data("id");

        if ($btn.hasClass("view")) {
            viewDeliveryChallanData(id);
        } else if ($btn.hasClass("edit")) {
            window.location.href = deliveryChallanEditUrl.replace(':id', id);
        } else if ($btn.hasClass("print")) {
            printReport(deliveryChallanPrintLetterUrl, { ids: id });            
        }
    });

    // ----- Filter buttons -----
    $("#filter_apply").on("click", function (e) {
        e.preventDefault();
        applyFilter();
    });

    $("#filter_clear").on("click", function (e) {
        e.preventDefault();
        clearFilter();

        // Reset status dropdown to 'open'
        var $statusDropdown = $("#delivery_status");
        if (!$statusDropdown.length) return;

        if ($statusDropdown.data("select2")) {
            $statusDropdown.val("open").trigger("change");
        } else {
            $statusDropdown.val("open");
        }

        applyFilter();
    });
    // ----- Print / Excel dropdown actions -----
    $(document).on("click", ".dropdown-item", function () {
        let $action = $(this);
        let type    = $action.data("type");
        let route   = $action.data("route");
        let format  = $action.data("format");

        if (!type) return;

        var safeFilter = (currentFilter && Object.keys(currentFilter).length > 0)
            ? currentFilter
            : [];

        switch (type) {
            case "print":
                printReport(route, { format: format, currentFilter: currentFilter });
                break;

            case "excel":
                downloadExcel(route, { currentFilter: currentFilter });
                break;
        }
    });
});

/**
 * Fetch and display Delivery Challan data in the modal
 */
function viewDeliveryChallanData(challanId) {
    if (!challanId) {
        console.warn("Challan ID is required to load data.");
        return;
    }

    let url = deliveryChallanViewUrl.replace(":id", challanId);

    if (typeof showLoader === "function") {
        showLoader("Loading Delivery Challan data...");
    }

    $.ajax({
        url: url,
        type: "GET",
        dataType: "json",
        success: function (response) {
            let challanData = response.data || response;

            if (challanData) {
                formatDeliveryChallanData(challanData);

                const statusMap = {
                    open: "bg-green text-green-fg",
                    cancel: "bg-orange text-orange-fg",
                    close: "bg-red text-red-fg",
                    billed: "bg-purple text-purple-fg",
                    hold: "bg-blue text-blue-fg",
                    rejected: "bg-red text-red-fg",
                };

                renderStatusBadge("#delivery_challan_status_view", challanData.challan_status, statusMap);
                showDeliveryChallanModal();
            }
        },
        error: function (error) {
            console.error("Error loading Delivery Challan View:", error);
            if (typeof showToast === "function") {
                showToast("error", "Oops! Failed to load Delivery Challan details.");
            }
        },
        complete: function () {
            if (typeof hideLoader === "function") {
                hideLoader();
            }
        }
    });
}

function showDeliveryChallanModal() {
    const modalElement = document.getElementById("delivery_challan_modal"); // Matching blade ID
    if (!modalElement) return;

    if (typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modalElement, {
            backdrop: "static",
            keyboard: false,
        });
        bsModal.show();
    } else {
        $(modalElement).modal('show');
    }
}

function formatDeliveryChallanData(data) {
    // Main fields
    $("#account_name").val(data.account?.name ?? "");
    $("#broker_name").val(data.broker?.name ?? "");
    $("#account_id_city").val(data.account?.city ?? "");
    $("#account_id_type").val(data.gst_type === GST_TYPE.INTERSTATE ? "INTERSTATE" : "LOCAL");

    // Date & Other Fields
    $("#dc_id").val(data.challan_serial); // Matching dc_id in blade
    $("#delivery_challan_date").val(data.challan_date ? formatDateToDMY(data.challan_date) : "");
    $("#vehicle_number").val(data.vehicle_number ?? "");
    $("#remarks").text(data.remarks ?? "");

    $("#gross_weight").text(data.gross_weight ?? "0.00");
    $("#tare_weight").text(data.tare_weight ?? "0.00");
    $("#net_weight").text(data.net_weight ?? "0.00");

    $("#bag_type").text((data.bag_type ?? "").toUpperCase());
    $("#bag_count").text(data.bag_count ?? "0");

    if (typeof formatQty === 'function') {
        $("#net_weight_bag").text(formatQty(data.net_weight_wt_bag ?? "0.000"));
        $("#total_qty").text(formatIndianNumber(data.total_quantity || 0, DECIMALS.QTY));
        $("#total_amount").text(formatCurrency(data.total_amount || 0));
    } else {
        $("#net_weight_bag").text(data.net_weight_wt_bag ?? "0.000");
        $("#total_qty").text(data.total_quantity || "0.000");
        $("#total_amount").text(data.total_amount || "0.00");
    }

    // Items Table
    const tbody = document.querySelector("#item_table_body");
    if (!tbody) return;

    // Remove existing rows
    const rows = tbody.querySelectorAll("tr");
    rows.forEach((tr, i) => {
        if (i > 0) tr.remove();
    });

    // Populate Rows
    if (Array.isArray(data.details)) {
        data.details.forEach((detail, index) => {
            let row;
            if (index === 0) {
                row = tbody.querySelector("tr");
            } else {
                const firstRow = tbody.querySelector("tr");
                row = firstRow.cloneNode(true);
                tbody.appendChild(row);
            }

            const serialNumberSpan = row.querySelector("td:first-child span");
            if (serialNumberSpan) {
                serialNumberSpan.textContent = index + 1;
            }

            // Map fields
            const selectors = [
                ["span.item_id", detail.item?.name ?? ""],
                ["span.unit_name", detail.item?.unit?.name ?? ""],
                ["span.sales_order_serial", detail.sales_order_serial ?? ""],
                ["span.destination_id", detail.destination?.name ?? ""],
                ["span.condition_id", detail.condition?.name ?? ""],
                ["span.cgst_rate", detail.cgst_rate ?? "0.00"],
                ["span.sgst_rate", detail.sgst_rate ?? "0.00"],
                ["span.igst_rate", detail.igst_rate ?? "0.00"],
                ["span.bag_count", detail.bag_count ?? "0"],
                ["span.party_quantity", Number(detail.party_quantity || 0).toFixed(DECIMALS.QTY)],
                ["span.quantity", Number(detail.quantity || 0).toFixed(DECIMALS.QTY)],
                ["span.inclusive_rate", formatIndianNumber(detail.inclusive_rate || 0, DECIMALS.INCLUSIVE_RATE)],
                ["span.rate", formatIndianNumber(detail.rate || 0, DECIMALS.RATE)],
                ["span.amount", formatIndianNumber(detail.amount || 0, DECIMALS.AMOUNT)],
            ];

            selectors.forEach(([selector, value]) => {
                const el = row.querySelector(selector);
                if (el) el.textContent = value;
            });
        });
    }
}

