let table;
let currentFilter = {};
let currentPermissions = {};
let savedColWidths = {};
let totalFilteredRecords = 0;
let grandTotalRecords = 0;


const FILTER_KEY = "dairy_analysis_rebate_pending_filter";
const WIDTH_KEY = "dairy_analysis_rebate_pending_col_widths";

const filterFields = [   
    'start_date', 
    'end_date', 
    'account_id', 
    'supplier_id', 
    'destination_id', 
    'rebate_status',
    'item_id'     
];


$(document).ready(function() {    
    bindSelect2();
    filterSelect2();

    // date input with validation
    new DateInput("#start_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#end_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    restoreFilters();

    // Initialize Tabulator
    table = new Tabulator("#dairy_analysis_rebate_pending_table", {  
        height: "550px",
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

        pagination: true,
        paginationMode: "remote",
        paginationSize: 50,
        paginationSizeSelector: [10, 20, 50, 100, 300],
        paginationDataSent: {
            page: "page",
            size: "size",
        },
        paginationDataReceived: {
            last_page: "last_page",
            data: "data",
            total: "total",
        },
        ajaxURL: dairyAnalysisRebatePendingUrl,
        ajaxParams: () => currentFilter,
        ajaxURLGenerator: (url, config, params) => {
            const page = params.page || 1;
            const size = params.size || 50;
            const queryParams = new URLSearchParams({ ...currentFilter, page, size });
            return `${url}?${queryParams.toString()}`;
            },
            ajaxResponse(url, params, response) {
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
            columns: getTableColumns(savedColWidths),
            tableBuilt: function() {
                this.setData();
            },
    });
     
    $("#filter_apply").on("click", function (e) {
        e.preventDefault();
        applyFilter();
    });
    $("#filter_clear").on("click", function (e) {
        e.preventDefault();
        clearFilter();
        
        // Reset rebate status dropdown to 'All'
        var $statusDropdown = $("#rebate_status");
        if (!$statusDropdown.length) return;
 
        if ($statusDropdown.data("select2")) {
            $statusDropdown.val("").trigger("change");
        } else {
            $statusDropdown.val("");
        }

    });
   
    // Print Report
    $(document).on("click", ".dropdown-item", function (e) {   
    const action = e.target.closest(".dropdown-item");
    if (!action) return;
 
    const type = action.dataset.type; // print / export
    const route = action.dataset.route;
    const format = action.dataset.format;
 
    if (!type) return;
 
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

 function getTableColumns(savedColWidths) {
    return [
        { 
            title: "",
            field: "select", 
            formatter: "rowSelection", 
            titleFormatter: "rowSelection",
            hozAlign: "center", 
            vertAlign: "middle",
            headerSort: false, 
            width: 43,
            cellClick: function(e, cell){
                cell.getRow().toggleSelect();
            }
        },
        { 
            title: "Customer Name", 
            field: "customer_name", 
            width: 250, 
            vertAlign: "middle",
            headerSort: false,
        },
        { 
            title: "Destination", 
            field: "destination", 
            width: 130, 
            vertAlign: "middle",
            headerSort: false,
        },
        { 
            title: "Po. No.", 
            field: "po_no", 
            width: 100, 
            hozAlign: "center",
            vertAlign: "middle",
            headerSort: false,
        },
        { 
            title: "S.BillNo.", 
            field: "invoice_serial", 
            hozAlign: "center", 
            width: 100, 
            vertAlign: "middle",
            headerSort: false
        },
        { 
            title: "S.Item Name", 
            field: "item_name", 
            width: 160, 
            vertAlign: "middle",
            headerSort: false,
        },
        { 
            title: "Sales bill Date", 
            field: "s_date", 
            hozAlign: "center", 
            width: 120, 
            vertAlign: "middle",
            headerSort: false
        },
        { 
            title: "GRN", 
            field: "grn_no", 
            hozAlign: "center",
            headerHozAlign: "center",
            width: 100, 
            vertAlign: "middle",
            headerSort: false  
        },
        { 
            title: "Days", 
            field: "days", 
            hozAlign: "center", 
            hozAlign: "center",
            width: 70, 
            vertAlign: "middle",
            headerSort: false  
        },
        { 
            title: "File No.", 
            field: "file_no", 
            hozAlign: "center", 
            width: 80, 
            vertAlign: "middle",
            headerSort: false  
        },
        { 
            title: "P.BillNo.", 
            field: "reference_number", 
            hozAlign: "left", 
            
            width: 150, 
            vertAlign: "middle",
            headerSort: false
        },
        { 
            title: "Supplier Name", 
            field: "supplier_name", 
            width: 240, 
            vertAlign: "middle",    
            formatter: (cell) => {
                const supplierName = cell.getValue();
                if (supplierName) {
                    return `<span class="fw-semibold text-truncate">${supplierName} </span>`;              
                }
                return "";
            },        
        },
        { 
            title: "City", 
            field: "city", 
            width: 140, 
            vertAlign: "middle",
            headerSort: false,
        },
        { 
            title: "P.Qty", 
            field: "p_qty", 
            hozAlign: "right", 
            width: 100, 
            vertAlign: "middle", 
            cssClass: "fw-bold",
            formatter: "money",
            formatterParams: { precision: 3 },
            headerSort: false                
        },
        { 
            title: "Bill Balance", 
            field: "bill_balance", 
            hozAlign: "right", 
            width: 130,
            headerSort: false,	                  
            vertAlign: "middle",
            formatter: "money",
            formatterParams: { precision: 2, symbol: "" },
            // bottomCalc: "sum",
            // formatter: (cell) => {
            //     const billBalance = cell.getValue();
            //     if (billBalance) {
            //         return `<span class="fw-semibold">${formatIndianNumber(billBalance)} </span>`;              
            //     }
            //     return "--";
            // },
            // bottomCalcFormatter: function(cell) {
            //     const value = cell.getValue();
            //     return `<span class="text-dark fw-bold">${formatIndianNumber(value)}</span>`;
            // }
        },
    ];
}

function filterSelect2() {
    const selectIdArray = ['#rebate_status'];
    selectIdArray.forEach(element => {
        $(element).select2({
            theme: "bootstrap-5",
            // allowClear: true,
            // placeholder: "Select " + element.replace('#', '').replace('.', '').replace('_id', '') + " ...",
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

function bindSelect2() {
    const selectIdArray = ['#account_id', '#supplier_id', '#destination_id', '#rebate_status','#item_id'];
    selectIdArray.forEach(element => {
        $(element).select2({
            theme: "bootstrap-5",
            allowClear: true,
            placeholder: "Select " + element.replace('#', '').replace('.', '').replace('_id', '') + " ...",
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


/**
 * Get value from DOM element
 * @param {HTMLElement} el 
 * @returns {string}
 */
function getFieldValue(el) {
    if (!el) return "";
    return $(el).val();
}

/**
 * Set value to DOM element and trigger change for Select2
 * @param {HTMLElement} el 
 * @param {any} value 
 */
function setFieldValue(el, value) {
    if (!el) return;
    var $el = $(el);
    $el.val(value);
    // If it's a select2 dropdown, trigger change to update UI
    if ($el.hasClass("select2-hidden-accessible")) {
        $el.trigger("change");
    }
}

/**
 * Normalize date string to YYYY-MM-DD
 * @param {string} dateStr 
 * @returns {string}
 */
function normalizeDate(dateStr) {
    if (!dateStr || dateStr === "-") return "";
    
    // If it matches DD-MM-YYYY, return as is
    if (/^\d{2}-\d{2}-\d{4}$/.test(dateStr)) return dateStr;  

    // If already YYYY-MM-DD, return as is
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr;

    if (typeof formatDateToYMD === "function") {
        return formatDateToYMD(dateStr);
    }
    return dateStr;
}