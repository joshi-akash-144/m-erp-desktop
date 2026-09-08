// ===========================================================
// GLOBALS
// ===========================================================
let table;
let currentFilter = { grn_status: "open" };
let currentPermissions = {};
let grandTotalRecords = 0;
let totalFilteredRecords = 0;

const FILTER_KEY = "good_receipt_filter";
const WIDTH_KEY  = "good_receipt_col_widths";
const PAGE_SIZE_KEY = "grn_page_size";
const PAGE_NUM_KEY  = "grn_page_num";

const filterFields = [
    "grn_no",
    "grn_serial",
    "start_date",
    "end_date",
    "account_id",
    "item_id",
    "broker_id",
    "condition_id",
    "destination_id",
    "grn_status",
    "qc_status",
    "reference_number"
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
    const [day, month, year] = d.split("-");
    return year?.length === 4 ? `${day}-${month}-${year}` : "";
}

/** Render multi-row details */
function renderDetailsList(details, key, numeric, decimal, customFormatter) {
    numeric         = numeric         !== undefined ? numeric         : false;
    decimal         = decimal         !== undefined ? decimal         : 2;
    customFormatter = customFormatter !== undefined ? customFormatter : null;

    if (!Array.isArray(details) || details.length === 0)
        return "<span class='text-muted'>--</span>";

    var bullet = details.length > 1;
    var html   = "";

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
        var el    = $("#" + id).get(0);
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

    // Default grn_status to 'open' if not explicitly selected
    if (!currentFilter['grn_status']) {
        currentFilter['grn_status'] = 'open';
        // Also sync the dropdown UI to reflect the default
        var $statusDropdown = $("#grn_status");
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

    currentFilter = { grn_status: "open" }
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
            formatter: "rowSelection",
            titleFormatter: "rowSelection",
            hozAlign: "center",
            headerSort: false,
            width: 40,
            frozen: true,
        },
        {
            title: "Grn Serial.",
            field: "grn_serial",
            width: saved.grn_serial ?? 150,
            hozAlign: "center",
        },

        // {
        //     title: "GRN No.",
        //     field: "grn_number",
        //     width: saved.grn_number ?? 150,
        //     hozAlign: "left",
        // },

        {
            title: "P.O. No.",
            field: "details",
            headerSort: false,
            headerHozAlign: "right",
            hozAlign: "right",
            width: 100,
            formatter: function (cell) {
                return renderDetailsList(cell.getValue(), "purchase_order_serial");
            },
        },

        {
            title: "Bill No.",
            field: "reference_number",
            headerHozAlign: "right",
            headerSort: false,
            width: saved.grn_serial ?? 150,
            hozAlign: "right",
        },

        {
            title: "Date",
            field: "grn_date",
            width: saved.grn_date ?? 120,
            formatter: function (c) {
                return formatDateToDMY(c.getValue());
            },
        },

        {
            title: "Supplier Name",
            field: "account.name",
            width: saved.account ?? 450,
            formatter: function (cell) {
                var accountName = cell.getValue();
                var rowData     = cell.getData();
                var city        = (rowData.account && rowData.account.city) ? rowData.account.city : "";

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
            width: saved.broker ?? 450,
            formatter: function (cell) {
                var brokerName = cell.getValue();
                var rowData    = cell.getData();
                var city       = (rowData.broker && rowData.broker.city) ? rowData.broker.city : "";

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
            width: saved.items ?? 200,
            formatter: function (c) {
                var htmlString = renderDetailsList(c.getValue(), "item.name");
                var $tempDiv   = $("<div>").html(htmlString);

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
            width: saved.destination ?? 200,
            formatter: function (c) {
                var htmlString = renderDetailsList(c.getValue(), "destination.name");
                var $tempDiv   = $("<div>").html(htmlString);

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
            width: saved.condition ?? 200,
            formatter: function (c) {
                var htmlString = renderDetailsList(c.getValue(), "condition.name");
                var $tempDiv   = $("<div>").html(htmlString);

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
            bottomCalcFormatter: function(cell) {                
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
            title: "Inc. Rate",
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
            title: "GRN Status",
            field: "grn_status",
            width: saved.grn_status ?? 130,
            headerHozAlign: "right",
            hozAlign: "center",
            vertAlign: "middle",
            formatter: function (c) {
                var val = c.getValue() || "--";
                var map = {
                    open:     "bg-success-lt",
                    billed:   "badge bg-purple-lt",
                    rejected: "bg-red-lt",
                    hold:     "bg-blue-lt",
                    close:    "bg-danger-lt",
                    cancel:   "bg-orange-lt",
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
        // const deleteIcon = getIcon("delete");

        let actions = '<div class="d-flex gap-2 align-items-center justify-content-center h-100 w-100">';

        // if (canView) {
        //   actions += `
        //       <span class="erp-btn-icon view" data-id="${row.id}" title="View GRN">
        //         ${viewIcon}
        //       </span>`;
        // }

        if (canEdit) {
          actions += `
              <span class="erp-btn-icon edit" data-id="${row.id}" title="Edit GRN">
                ${editIcon}
              </span>`;
        }

        if (canPrint) {
          actions += `
              <span class="erp-btn-icon print" data-id="${row.id}" style="stroke-width:1px !important" title="Print GRN">
                ${printIcon}
              </span>`;
        }

        // if (canDelete) {
        //   actions += `
        //       <span class="erp-btn-icon delete" data-id="${row.id}" title="Delete Order">
        //         ${deleteIcon}
        //       </span>`;
        // }

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
        "#grn_serial", "#account_id", "#item_id","#broker_id", "#condition_id", "#destination_id","#grn_status", "#qc_status"
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

    table = new Tabulator("#grn_table", {
        height: "550px",
        layout: "fitColumns",

        placeholder: "<div class=\"text-center py-5\"><h3 class=\"text-muted\">No data found</h3><p class=\"text-muted\">Try adjusting your filters or search criteria</p></div>",

        pagination: true,
        paginationMode: "remote",
        paginationSize: parseInt(localStorage.getItem(PAGE_SIZE_KEY)) || 50,
        paginationInitialPage: parseInt(localStorage.getItem(PAGE_NUM_KEY)) || 1,
        paginationSizeSelector: [10, 30, 50, 100, 150, 200, 250, 300],
        paginationDataSent: {
            page: "page",
            size: "size",
        },
        paginationDataReceived: {
            last_page: "last_page",
            data: "data",
            total: "total",
        },

        ajaxURL: grnListUrl,

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
            totalFilteredRecords = response.total || 0;
            grandTotalRecords    = response.grand_total || 0;
            return response;
        },

        paginationCounter: function(pageSize, currentRow, _currentPage, _totalRows, _totalPages) {
            const total = totalFilteredRecords;
            if (!total) return "No entries found";
            const start = currentRow;
            const end   = Math.min(currentRow + pageSize - 1, total);
            let text = `Showing ${start} to ${end} of ${total} entries`;
            if (grandTotalRecords > 0 && grandTotalRecords !== total) {
                text += ` (filtered from ${grandTotalRecords} total entries)`;
            }
            return text;
        },

        columns: getColumns(savedWidths),
    });

    table.on("columnResized", saveColWidths);

    table.on("pageLoaded", function(pageNo) {
        localStorage.setItem(PAGE_NUM_KEY, pageNo);
        localStorage.setItem(PAGE_SIZE_KEY, table.getPageSize());
    });

    table.on("rowSelectionChanged", function(data, rows) {
        if (data.length > 0) {
            $("#btn_delete_selected").removeClass("d-none");
        } else {
            $("#btn_delete_selected").addClass("d-none");
        }
    });

    $("#btn_delete_selected").on("click", function () {
        var selectedRows = table.getSelectedRows();
        var selectedIds = selectedRows.map(row => row.getData().id);
        var totalSelected = selectedIds.length;

        if (totalSelected === 0) return;

        Swal.fire({
            title: "Are you sure?",
            text: `You want to delete ${totalSelected} selected GRN(s)! This process cannot be undone.`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: typeof grnDeleteSelectedUrl !== 'undefined' ? grnDeleteSelectedUrl : "/vouchers/grns/delete-selected",
                    type: "POST",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        _method: "DELETE",
                        ids: selectedIds
                    },
                    success: function (response) {
                        table.setData();
                        Swal.fire("Deleted!", response.message, "success");
                        $("#btn_delete_selected").addClass("d-none");
                    },
                    error: function (xhr) {
                        Swal.fire("Error!", xhr.responseJSON?.message || "Something went wrong.", "error");
                    }
                });
            }
        });
    });

    // registerTable("grn_table", table);

    // ----- Action button clicks (view / edit / delete) -----
    $(document).on("click", function (e) {
        var $btn = $(e.target).closest(".erp-btn-icon");
        if (!$btn.length) return;

        var id = $btn.data("id");

        if ($btn.hasClass("view")) {
            viewGrnData(id);
        } else if ($btn.hasClass("edit")) {
            if (typeof grnEditUrl !== 'undefined') {
                window.location.href = grnEditUrl.replace(':id', id);
            } else {
                window.location.href = `/vouchers/goods-receipt-notes/${id}/edit`;
            }
        } else if ($btn.hasClass("print")) {
            if (typeof grnPrintRecieptUrl !== 'undefined') {
                const url = grnPrintRecieptUrl.replace(":id", id);
                printReport(url);
            }
        } else if ($btn.hasClass("delete")) {
            // deletePurchaseOrder(id);
        }
    });

    // ----- Filter buttons -----
    $("#filter_apply").on("click", function(e) {
        e.preventDefault();
        applyFilter();
    });

    $("#filter_clear").on("click", function (e) {
        e.preventDefault();

        clearFilter();
        
        // Reset GRN status dropdown to 'open'
        var $statusDropdown = $("#grn_status");
        if (!$statusDropdown.length) return;

        if ($statusDropdown.data("select2")) {
            $statusDropdown.val("open").trigger("change");
        } else {
            $statusDropdown.val("open");
        }

        
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
 * Fetch and display GRN data in the modal
 */
function viewGrnData(grnId) {
    if (!grnId) {
        console.warn("Grn ID is required to load data.");
        return;
    }

    // Use ziggy or fallback
    let url = "";
    if (typeof grnViewUrl !== "undefined") {
        url = grnViewUrl.replace(":id", grnId);
    } else {
        url = "/vouchers/goods-receipt-notes/" + grnId;
    }

    if (typeof showLoader === "function") {
        showLoader("Loading GRN data...");
    }

    $.ajax({
        url: url,
        type: "GET",
        data: {
            grn_id: grnId
        },
        dataType: "json",

        success: function (response) {
            let grnData = response.data || response;

            if (grnData) {
                formatGrnData(grnData);

                const orderStatusMap = {
                    open: "bg-green text-green-fg",
                    cancel: "bg-orange text-orange-fg",
                    close: "bg-red text-red-fg",
                    billed: "bg-purple text-purple-fg",
                    hold: "bg-blue text-blue-fg",
                    rejected: "bg-red text-red-fg",
                };

                renderStatusBadge("#grn_status_view", grnData.grn_status, orderStatusMap);
                showGrnModal();
            }
        },

        error: function (error) {
            console.error("Error loading GRN View:", error);

            if (typeof showToast === "function") {
                showToast("error", "Oops! Failed to load GRN details.");
            }
        },

        complete: function () {
            if (typeof hideLoader === "function") {
                hideLoader();
            }
        }
    });
}

function showGrnModal() {
    const grnModalElement = document.getElementById("grn_modal");
    if (!grnModalElement) return;

    if (typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(grnModalElement, {
            backdrop: "static",
            keyboard: false,
        });
        bsModal.show();
    } else {
        // jQuery fallback
        $(grnModalElement).modal({
            backdrop: 'static',
            keyboard: false
        });
        $(grnModalElement).modal('show');
    }
}

function formatGrnData(data) {
    
    // =====================================================
    // Set main fields
    // =====================================================
    $("#account_name").val(data.account?.name ?? "");
    $("#broker_name").val(data.broker?.name ?? "");
    $("#account_id_city").val(data.account?.city ?? "");
    $("#account_id_type").val(data.gst_type === GST_TYPE.INTERSTATE ? "INTERSTATE" : "LOCAL");

    // =====================================================
    // Date & Other Fields
    // =====================================================
    $("#grn_id").val(data.grn_serial);
    $("#grn_date").val(data.grn_date ? formatDateToDMY(data.grn_date) : "");
    $("#grn_in_date").val(data.grn_in_date ? formatDateToDMY(data.grn_in_date) : "");
    $("#grn_out_date").val(data.grn_out_date ? formatDateToDMY(data.grn_out_date) : "");

    $("#contract_number").val(data.contract_number ?? "");
    $("#reference_number").val(data.reference_number ?? "");
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
    } else {
        $("#net_weight_bag").text(data.net_weight_wt_bag ?? "0.000");
        $("#total_qty").text(data.total_quantity || "0.000");
    }

    $("#total_amount").text(RUPEE_SIGN + formatIndianNumber(data.total_amount || 0, DECIMALS.AMOUNT));

    // =====================================================
    // Items Table
    // =====================================================
    const tbody = document.querySelector("#item_table_body");
    if (!tbody) return;

    // Remove existing rows (except first one which we use as template)
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

            row.dataset.row = index + 1;
            row.dataset.rowId = index + 1;
            
            const serialNumberSpan = row.querySelector("td:first-child span");
            if (serialNumberSpan) {
                serialNumberSpan.textContent = index + 1;
            }

            // Map fields
            const selectors = [
                ["span.item_id", detail.item?.name ?? ""],
                ["span.unit_name", detail.item?.unit?.name ?? ""],
                ["span.purchase_order_serial", detail.purchase_order_serial ?? ""],
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
                if (el) {
                    el.textContent = value;
                }
            });
        });
    }
}

