let table;
let currentFilter = {};
let currentPermissions = {};
let savedColWidths = {};
let totalFilteredRecords = 0;
let grandTotalRecords = 0;


const FILTER_KEY = "sales_purchase_analysis_filter";
const WIDTH_KEY = "sales_purchase_analysis_col_widths";

const filterFields = [   
    'start_date',
    'end_date',
    'account_id',
    'item_id',
    'so_serial',
    'po_serial'
];


$(document).ready(function() {    
    // date input with validation
    new DateInput("#start_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#end_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    bindSelect2();

    restoreFilters();

    // Initialize Tabulator
    table = new Tabulator("#analysis_table", {  
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
        ajaxURL: getRegisterDataUrl,
        ajaxParams: () => currentFilter,
        ajaxURLGenerator: (url, config, params) => {
            const page = params.page ?? 1;
            const size = params.size ?? 50;
            const queryParams = new URLSearchParams({ ...currentFilter, page, size });
            return `${url}?${queryParams.toString()}`;
        },
        ajaxResponse(url, params, response) {
            if (response.permissions) currentPermissions = response.permissions;
            totalFilteredRecords = response.total || response.data.length || 0;
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
    });
     
    

    $("#filter_apply").on("click",  function(e){
        e.preventDefault();       
        applyFilter();
    });
    $("#filter_clear").on("click", function (e) {
        e.preventDefault();               
        clearFilter();                  
    });
});


function bindSelect2() {
    const selectIdArray = ['#account_id', '#item_id'];
    selectIdArray.forEach(element => {
        $(element).select2({
            theme: "bootstrap-5",
            allowClear: true,
            placeholder: "Select " + element.replace('#', '').replace('.', '').replace('_id', '') + "...",
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

 function getTableColumns(savedColWidths) {
    return [
        {
            title: "No",
            field: "no",
            width: 70,
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
            resizable: false,
            frozen: true,
            vertAlign: "middle",
            formatter: "rownum",
        },
        // { 
        //     title: "PO No.", 
        //     field: "po_number", 
        //     headerHozAlign: "center",
        //     hozAlign: "center", 
        //     width: 70, 
        //     vertAlign: "middle",
        //     headerSort: false
        // },
        // { 
        //     title: "Purchase Invoice", 
        //     field: "purchase_number", 
        //     headerHozAlign: "center",
        //     hozAlign: "center", 
        //     width: 140, 
        //     vertAlign: "middle",
        //     headerSort: false
        // },
        // { 
        //     title: "Purchase Date", 
        //     field: "purchase_date", 
        //     headerHozAlign: "center",
        //     hozAlign: "center", 
        //     width: 120, 
        //     vertAlign: "middle",
        //     headerSort: false,
        //     formatter: (cell) => cell.getValue() ? formatDateToDMY(cell.getValue()) : '--'
        // },
        { 
            title: "Dairy PO No.", 
            field: "buyer_po_number", 
            headerHozAlign: "center",
            hozAlign: "center", 
            width: 120, 
            vertAlign: "middle",
            headerSort: false,
        },
        // { 
        //     title: "Sale Invoice", 
        //     field: "sales_number", 
        //     headerHozAlign: "center",
        //     hozAlign: "center", 
        //     width: 110, 
        //     vertAlign: "middle",
        //     headerSort: false
        // },
        // { 
        //     title: "Sale Date", 
        //     field: "sales_date", 
        //     headerHozAlign: "center",
        //     hozAlign: "center", 
        //     width: 110, 
        //     vertAlign: "middle",
        //     headerSort: false,
        //     formatter: (cell) => cell.getValue() ? formatDateToDMY(cell.getValue()) : '--'
        // },
        { 
            title: "Customer Account", 
            field: "sales_party_name", 
            width: 500, 
            vertAlign: "middle",
            headerSort: false,
            formatter: (cell) => `<span class="fw-semibold text-truncate" title="${cell.getValue() || ''}">${cell.getValue() || '--'}</span>`
        },
        { 
            title: "Item Name", 
            field: "item_name", 
            width: 250, 
            vertAlign: "middle",
            headerSort: false,    
            formatter: (cell) => `<span class="fw-semibold">${cell.getValue() || '--'}</span>`
        },
        { 
            title: "Avg. Purchase Rate", 
            field: "purchase_rate", 
            headerHozAlign: "right",
            hozAlign: "right", 
            width: 170,  
            vertAlign: "middle", 
            headerSort: false,
            formatter: (cell) => `<span class="fw-bold">${formatIndianNumber(cell.getValue() || 0)}</span>`
        },
        { 
            title: "Sale Rate", 
            field: "sales_rate", 
            headerHozAlign: "right",
            hozAlign: "right", 
            width: 130,  
            vertAlign: "middle", 
            headerSort: false,
            formatter: (cell) => `<span class="fw-bold">${formatIndianNumber(cell.getValue() || 0)}</span>`
        },
        // {
        //     title: "Rate Comparison",
        //     field: "rate_comparison",
        //     width: 160,
        //     vertAlign: "middle",
        //     headerSort: false,
        //     formatter: function(cell) {
        //         const row = cell.getRow().getData();
        //         const sales = parseFloat(row.sales_rate || 0);
        //         const purchase = parseFloat(row.purchase_rate || 0);
        //         if(sales === 0 && purchase === 0) return "--";
        //         
        //         const max = Math.max(sales, purchase, 1);
        //         const spct = (sales / max) * 100;
        //         const ppct = (purchase / max) * 100;
        //         
        //         return `
        //             <div class="d-flex flex-column justify-content-center gap-1 w-100 py-1" style="font-size: 9px; font-weight: 700; letter-spacing: 0.2px;">
        //                 <div class="d-flex align-items-center">
        //                     <span class="text-muted me-1 fw-bold" style="width: 10px;">S</span>
        //                     <div class="progress flex-grow-1 position-relative" style="height: 14px; background-color: #f1f5f9; border-radius: 3px;">
        //                         <div class="progress-bar bg-success" style="width: ${spct}%;"></div>
        //                         <div class="position-absolute w-100 h-100 d-flex align-items-center justify-content-between px-1" style="left: 0; top: 0;">
        //                             <span style="color: ${spct > 40 ? '#fff' : '#475569'};">${formatIndianNumber(sales)}</span>
        //                             <span style="color: ${spct > 80 ? '#fff' : '#475569'};">${spct.toFixed(0)}%</span>
        //                         </div>
        //                     </div>
        //                 </div>
        //                 <div class="d-flex align-items-center">
        //                     <span class="text-muted me-1 fw-bold" style="width: 10px;">P</span>
        //                     <div class="progress flex-grow-1 position-relative" style="height: 14px; background-color: #f1f5f9; border-radius: 3px;">
        //                         <div class="progress-bar bg-warning" style="width: ${ppct}%;"></div>
        //                         <div class="position-absolute w-100 h-100 d-flex align-items-center justify-content-between px-1" style="left: 0; top: 0;">
        //                             <span style="color: ${ppct > 40 ? '#fff' : '#475569'};">${formatIndianNumber(purchase)}</span>
        //                             <span style="color: ${ppct > 80 ? '#fff' : '#475569'};">${ppct.toFixed(0)}%</span>
        //                         </div>
        //                     </div>
        //                 </div>
        //             </div>
        //         `;
        //     }
        // },
        { 
            title: "Profit/Loss Rate", 
            field: "profit_loss_rate", 
            headerHozAlign: "right",
            hozAlign: "right", 
            width: 150,  
            vertAlign: "middle", 
            headerSort: false,
            formatter: (cell) => {
                const val = parseFloat(cell.getValue() || 0);
                if (Math.abs(val) < 0.01) {
                    return `<div class="d-flex flex-column align-items-end justify-content-center h-100"><span class="fw-bold text-primary" style="font-size: 15px;">EQUAL</span></div>`;
                }
                const row = cell.getRow().getData();
                const purchase = parseFloat(row.purchase_rate || 0);
                
                let pctStr = "";
                if(purchase > 0) {
                    const pct = (val / purchase) * 100;
                    pctStr = `<div class="text-muted mt-1" style="font-size: 10px; font-weight: 500;">(${pct > 0 ? '+' : ''}${pct.toFixed(1)}%)</div>`;
                }

                const colorClass = val > 0 ? "text-success" : "text-danger";
                const icon = val > 0 ? "<i class='fa-solid fa-arrow-up'></i>" : "<i class='fa-solid fa-arrow-down'></i>";
                return `
                    <div class="d-flex flex-column align-items-end justify-content-center">
                        <span class="fw-bold ${colorClass}">${icon} ${formatIndianNumber(Math.abs(val))}</span>
                        ${pctStr}
                    </div>
                `;
            }
        },
        // {
        //     title: "Amount Comparison",
        //     field: "amount_comparison",
        //     width: 170,
        //     vertAlign: "middle",
        //     headerSort: false,
        //     formatter: function(cell) {
        //         const row = cell.getRow().getData();
        //         const sales = parseFloat(row.sales_amount || 0);
        //         const purchase = parseFloat(row.purchase_amount || 0);
        //         if(sales === 0 && purchase === 0) return "--";
        //         
        //         const max = Math.max(sales, purchase, 1);
        //         const spct = (sales / max) * 100;
        //         const ppct = (purchase / max) * 100;
        //         
        //         return `
        //             <div class="d-flex flex-column justify-content-center gap-1 w-100 py-1" style="font-size: 9px; font-weight: 700; letter-spacing: 0.2px;">
        //                 <div class="d-flex align-items-center">
        //                     <span class="text-muted me-1 fw-bold" style="width: 10px;">S</span>
        //                     <div class="progress flex-grow-1 position-relative" style="height: 14px; background-color: #f1f5f9; border-radius: 3px;">
        //                         <div class="progress-bar bg-success" style="width: ${spct}%;"></div>
        //                         <div class="position-absolute w-100 h-100 d-flex align-items-center justify-content-between px-1" style="left: 0; top: 0;">
        //                             <span style="color: ${spct > 40 ? '#fff' : '#475569'};">${formatIndianNumber(sales)}</span>
        //                             <span style="color: ${spct > 80 ? '#fff' : '#475569'};">${spct.toFixed(0)}%</span>
        //                         </div>
        //                     </div>
        //                 </div>
        //                 <div class="d-flex align-items-center">
        //                     <span class="text-muted me-1 fw-bold" style="width: 10px;">P</span>
        //                     <div class="progress flex-grow-1 position-relative" style="height: 14px; background-color: #f1f5f9; border-radius: 3px;">
        //                         <div class="progress-bar bg-warning" style="width: ${ppct}%;"></div>
        //                         <div class="position-absolute w-100 h-100 d-flex align-items-center justify-content-between px-1" style="left: 0; top: 0;">
        //                             <span style="color: ${ppct > 40 ? '#fff' : '#475569'};">${formatIndianNumber(purchase)}</span>
        //                             <span style="color: ${ppct > 80 ? '#fff' : '#475569'};">${ppct.toFixed(0)}%</span>
        //                         </div>
        //                     </div>
        //                 </div>
        //             </div>
        //         `;
        //     }
        // },
        { 
            title: "Profit/Loss Amount", 
            field: "profit_loss_amount", 
            headerHozAlign: "right",
            hozAlign: "right", 
            width: 180,  
            vertAlign: "middle", 
            headerSort: false,
            bottomCalc: "sum",
            bottomCalcFormatter: function(cell) {                                            
                if (cell.getTable().getDataCount() === 0) {
                    return "";
                }
                const val = cell.getValue() || 0;
                if (Math.abs(val) < 0.01) {
                    return `<span class="fw-bold text-primary">EQUAL</span>`;
                }
                const colorClass = val > 0 ? "text-success" : "text-danger";
                return `<span class="fw-bold ${colorClass}">${formatIndianNumber(val)}</span>`;
            },
            formatter: (cell) => {
                const val = parseFloat(cell.getValue() || 0);
                if (Math.abs(val) < 0.01) {
                    return `<div class="d-flex flex-column align-items-end justify-content-center h-100"><span class="fw-bold text-primary" style="font-size: 13px;">EQUAL</span></div>`;
                }
                const row = cell.getRow().getData();
                const purchase = parseFloat(row.purchase_amount || 0);
                
                let pctStr = "";
                if(purchase > 0) {
                    const pct = (val / purchase) * 100;
                    pctStr = `<div class="text-muted mt-1" style="font-size: 10px; font-weight: 500;">(${pct > 0 ? '+' : ''}${pct.toFixed(1)}%)</div>`;
                }

                const colorClass = val > 0 ? "text-success" : "text-danger";
                const icon = val > 0 ? "<i class='fa-solid fa-arrow-up'></i>" : "<i class='fa-solid fa-arrow-down'></i>";
                return `
                    <div class="d-flex flex-column align-items-end justify-content-center">
                        <span class="fw-bold ${colorClass}">${icon} ${formatIndianNumber(Math.abs(val))}</span>
                        ${pctStr}
                    </div>
                `;
            }
        },
        {
            title: "Action",
            field: "",
            width: 80,
            hozAlign: "center",
            vertAlign: "middle",
            headerSort: false,
            formatter: (cell) => {
                const viewIcon = icons.view; 
                return `<button class="erp-btn-icon view ">${viewIcon}</button>`;
            },
            cellClick: function(e, cell) {
                const row = cell.getRow().getData();
                const salesOrderId = row.sales_order_id;
                if(!salesOrderId) return;
                
                // Clear old data
                $("#modal_dairy_po_number").text("-");
                $("#modal_sales_rate").text("-");
                $("#modal_account_name").text("-");
                $("#modal_item_name").text("-");
                $("#modal_destination").text("-");
                $("#modal_total_qty").text("-");
                $("#modal_purchased_qty").text("-");
                $("#modal_billed_qty_po").text("-");
                $("#modal_godown_qty").text("-");
                $("#modal_total_billed_qty").text("-");
                $("#modal_remaining_qty").text("-");
                $("#dairy_po_footer_summary").hide();
                
                window.currentDetailedSalesOrderId = salesOrderId;
                
                $('#dairy_po_analysis_modal').modal('show');
                $("#dairy_po_chart_container").html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><div class="mt-2 text-muted">Loading analysis...</div></div>');
                
                $.ajax({
                    url: getRegisterDataUrl,
                    data: { sales_order_id: salesOrderId, size: 500, detailed: 1 },
                    success: function(response) {
                        renderPoAnalysisChart(response.data || [], row);
                    },
                    error: function() {
                        $("#dairy_po_chart_container").html('<div class="text-center py-5 text-danger"><i class="fa-solid fa-triangle-exclamation fa-2x mb-2"></i><div>Failed to load data</div></div>');
                    }
                });
            }
        }
    ];
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

function renderPoAnalysisChart(data, gridRow) {
    // Use rates from the main grid row (exact match to main grid's AVG calculation)
    let gridSalesRate    = gridRow ? (parseFloat(gridRow.sales_rate)    || 0) : 0;
    let gridPurchaseRate = gridRow ? (parseFloat(gridRow.purchase_rate) || 0) : 0;
    
    let poNumberText = (data && data.length > 0) ? (data[0].buyer_po_number || '') : '';
    let salesRateText = gridSalesRate > 0 ? '₹ ' + formatIndianNumber(gridSalesRate) : '--';
    let accountName = data && data.length > 0 ? data[0].sales_party_name : '';
    let itemName = data && data.length > 0 ? data[0].item_name : ''; 
    let unitName = data && data.length > 0 && data[0].unit_name ? ` (${data[0].unit_name})` : '';
    let destination = data && data.length > 0 ? (data[0].destination_name || '') : '--';
    let totalQty = gridRow ? (parseFloat(gridRow.so_total_qty) || 0) : ((data && data.length > 0) ? (parseFloat(data[0].so_total_qty) || 0) : 0);
    
    $("#modal_dairy_po_number").text(poNumberText);
    $("#modal_sales_rate").text(salesRateText);
    $("#modal_account_name").text(accountName);
    $("#modal_item_name").text(itemName + unitName);
    $("#modal_destination").text(destination);
    $("#modal_total_qty").text(totalQty > 0 ? formatIndianNumber(totalQty) : '--');

    if (!data || data.length === 0) {
        $("#dairy_po_chart_container").html('<div class="text-center py-5 text-muted"><i class="fa-solid fa-folder-open fa-2x mb-3"></i><h5>No data found for this PO</h5></div>');
        $("#dairy_po_footer_summary").hide();
        $("#modal_total_qty").text('--');
        return;
    }
    
    let sumSalesAmount = 0;
    let sumPurchaseAmount = 0;
    let sumDiffAmount = 0;
    let sumSalesQty = 0;
    let sumPurchaseQty = 0;
    
    let filteredData = [];

    data.forEach(function(row) {
        let pr = parseFloat(row.purchase_rate) || 0;
        let sr = parseFloat(row.sales_rate) || 0;
        
        if (pr === 0 && sr === 0) return;
        
        sumSalesAmount += (parseFloat(row.sales_amount) || 0);
        sumPurchaseAmount += (parseFloat(row.purchase_amount) || 0);
        sumDiffAmount += (parseFloat(row.profit_loss_amount) || 0);
        sumSalesQty += (parseFloat(row.sales_qty) || 0);
        sumPurchaseQty += (parseFloat(row.purchase_qty) || 0);
        
        filteredData.push(row);
    });
    
    let fulfillmentPct = totalQty > 0 ? (sumPurchaseQty / totalQty) * 100 : 0;
    let fulfillmentStr = sumPurchaseQty > 0 ? formatIndianNumber(sumPurchaseQty) : '0';
    if (fulfillmentPct > 0) {
        fulfillmentStr += ` <span class="fs-6 text-muted">(${fulfillmentPct.toFixed(1)}%)</span>`;
    }
    $("#modal_purchased_qty").html(fulfillmentStr);
    
    let soReceivedQty = gridRow ? (parseFloat(gridRow.so_received_qty) || 0) : 0;
    
    let godownQty = Math.max(0, soReceivedQty - sumPurchaseQty);
    let billedFromPo = soReceivedQty - godownQty;
    let remainingQty = Math.max(0, totalQty - soReceivedQty);
    
    $("#modal_billed_qty_po").text(billedFromPo > 0 ? formatIndianNumber(billedFromPo) : '0');
    $("#modal_godown_qty").text(godownQty > 0 ? formatIndianNumber(godownQty) : '0');
    $("#modal_total_billed_qty").text(soReceivedQty > 0 ? formatIndianNumber(soReceivedQty) : '0');
    $("#modal_remaining_qty").text(remainingQty > 0 ? formatIndianNumber(remainingQty) : '0');
    
    $("#dairy_po_chart_container").html('<div id="modal_tabulator_table"></div>');

    new Tabulator("#modal_tabulator_table", {
        data: filteredData,
        layout: "fitColumns",
        height: "100%",
        placeholder: "No data available",
        initialSort: [
            {column: "purchase_party_name", dir: "asc"},
        ],
        columns: [
            { 
                title: "Supplier Name", 
                field: "purchase_party_name", 
                vertAlign: "middle",
                formatter: (cell) => `<span class="fw-semibold">${cell.getValue() || '--'}</span>`
            },
            { 
                title: "Supplier PO No", 
                field: "po_number", 
                vertAlign: "middle",
                width: 150,
                formatter: (cell) => `<span>${cell.getValue() || '--'}</span>`
            },
            { 
                title: "Purchase Rate", 
                field: "purchase_rate", 
                hozAlign: "right",
                vertAlign: "middle",
                width: 140,
                formatter: (cell) => {
                    const pr = parseFloat(cell.getValue()) || 0;
                    return pr > 0 ? `<span class="fw-bold">₹ ${formatIndianNumber(pr)}</span>` : `<span class="fw-bold">₹ 0.00</span>`;
                }
            },
            {
                title: "",
                field: "visual_bar",
                headerSort: false,
                vertAlign: "middle",
                width: 250,
                formatter: function(cell) {
                    const row = cell.getRow().getData();
                    const sr = parseFloat(row.sales_rate) || 0;
                    const pr = parseFloat(row.purchase_rate) || 0;
                    
                    let barColor = "#206bc4"; // primary color
                    if (Math.abs(sr - pr) > 0.01) {
                        barColor = (sr > pr) ? '#28a745' : '#dc3545';
                    }
                    
                    let barWidthPct = (pr > 0) ? ((sr / pr) * 100) : 100;
                    let barWidth = Math.min(barWidthPct, 100); 
                    if (barWidth < 1) barWidth = 1; 

                    return `
                    <div class="w-100" style="height: 14px; background-color: #f1f5f9; border-radius: 4px; overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); margin-top: 4px;">
                        <div style="height: 100%; background-color: ${barColor}; width: ${barWidth}%;"></div>
                    </div>`;
                }
            },
            { 
                title: "Profit/Loss Percentage", 
                field: "profit_loss_pct",
                hozAlign: "right",
                vertAlign: "middle",
                headerSort: false,
                width: 180,
                formatter: (cell) => {
                    const row = cell.getRow().getData();
                    const sr = parseFloat(row.sales_rate) || 0;
                    const pr = parseFloat(row.purchase_rate) || 0;
                    
                    if (Math.abs(sr - pr) <= 0.01) {
                        return `<span class="fw-bold text-primary">0.0%</span>`;
                    }

                    const plPct = (pr > 0) ? (((sr - pr) / pr) * 100) : 0;
                    const percentageStr = (plPct > 0 ? '+' : '') + plPct.toFixed(1) + '%';
                    const colorClass = (sr > pr) ? 'text-success' : 'text-danger';
                    
                    return `<span class="fw-bold ${colorClass}">${percentageStr}</span>`;
                }
            }
        ]
    });
    
    // Use the main grid row's rates for the footer to ensure exact match
    let avgSalesRate    = gridSalesRate;
    let avgPurchaseRate = gridPurchaseRate;
    let diffRate = avgSalesRate - avgPurchaseRate;
    
    // Use the main grid row's amounts for the footer to ensure exact match
    let totalSales    = gridRow ? (parseFloat(gridRow.sales_amount) || 0) : sumSalesAmount;
    let totalPurchase = gridRow ? (parseFloat(gridRow.purchase_amount) || 0) : sumPurchaseAmount;
    let diffAmount    = gridRow ? (parseFloat(gridRow.profit_loss_amount) || 0) : sumDiffAmount;
    
    // Update footer summary
    $("#summary_sales_rate").text(formatIndianNumber(avgSalesRate));
    $("#summary_avg_purchase_rate").text(formatIndianNumber(avgPurchaseRate));
    
    $("#summary_diff_rate")
        .text(formatIndianNumber(Math.abs(diffRate)))
        .removeClass("text-success text-danger")
        .addClass(diffRate >= 0 ? "text-success" : "text-danger");
        
    let resultBadge = '';
    if (Math.abs(diffRate) <= 0.01) {
        resultBadge = '<span class="d-inline-block px-3 py-1 rounded shadow-sm" style="background-color: #4299e1; color: white; font-weight: 700; font-size: 13px; letter-spacing: 0.5px; border: 1px solid #3182ce;">EQUAL</span>';
    } else if (diffRate > 0) {
        resultBadge = '<span class="d-inline-block px-3 py-1 rounded shadow-sm" style="background-color: #48bb78; color: white; font-weight: 700; font-size: 13px; letter-spacing: 0.5px; border: 1px solid #38a169;">PROFIT</span>';
    } else {
        resultBadge = '<span class="d-inline-block px-3 py-1 rounded shadow-sm" style="background-color: #f56565; color: white; font-weight: 700; font-size: 13px; letter-spacing: 0.5px; border: 1px solid #e53e3e;">LOSS</span>';
    }
    $("#summary_result_badge").html(resultBadge);
        
    $("#summary_total_purchase").text(formatIndianNumber(totalPurchase));
    $("#summary_total_sales").text(formatIndianNumber(totalSales));
    
    $("#summary_diff_amount")
        .text(formatIndianNumber(Math.abs(diffAmount)))
        .removeClass("text-success text-danger")
        .addClass(diffAmount >= 0 ? "text-success" : "text-danger");
        
    let overallPct = 0;
    if (avgPurchaseRate > 0) {
        overallPct = ((avgSalesRate - avgPurchaseRate) / avgPurchaseRate) * 100;
    }
    let overallPctStr = (overallPct > 0 ? '+' : '') + overallPct.toFixed(1) + '%';
    let overallBadgeHTML = '';
    
    if (Math.abs(overallPct) <= 0.01) {
        overallPctStr = '0.0%';
        overallBadgeHTML = `<span class="d-inline-block px-3 py-1 rounded shadow-sm" style="background-color: #4299e1; color: white; font-weight: 700; font-size: 13px; letter-spacing: 0.5px; border: 1px solid #3182ce;">${overallPctStr}</span>`;
    } else if (overallPct > 0) {
        overallBadgeHTML = `<span class="d-inline-block px-3 py-1 rounded shadow-sm" style="background-color: #48bb78; color: white; font-weight: 700; font-size: 13px; letter-spacing: 0.5px; border: 1px solid #38a169;">${overallPctStr}</span>`;
    } else {
        overallBadgeHTML = `<span class="d-inline-block px-3 py-1 rounded shadow-sm" style="background-color: #f56565; color: white; font-weight: 700; font-size: 13px; letter-spacing: 0.5px; border: 1px solid #e53e3e;">${overallPctStr}</span>`;
    }

    $("#summary_overall_percentage").html(overallBadgeHTML);
        
    $("#dairy_po_footer_summary").show();
}

// Export to Excel logic
$(document).ready(function() {
    $('#export-excel-btn').on('click', function(e) {
        e.preventDefault();
        
        let filters = {
            start_date: $('#start_date').val() || '',
            end_date: $('#end_date').val() || '',
            account_id: $('#account_id').val() || '',
            item_id: $('#item_id').val() || '',
            so_serial: $('#so_serial').val() || ''
        };

        downloadExcel(exportExcelUrl, filters);
    });
    
    $('#export-detailed-excel-btn').on('click', function(e) {
        e.preventDefault();
        
        if (!window.currentDetailedSalesOrderId) {
            notyf.error("No specific PO selected for export.");
            return;
        }

        let filters = {
            sales_order_id: window.currentDetailedSalesOrderId,
            buyer_po_number: $('#modal_dairy_po_number').text(),
            start_date: $('#start_date').val() || '',
            end_date: $('#end_date').val() || '',
            account_id: $('#account_id').val() || '',
            item_id: $('#item_id').val() || '',
            so_serial: $('#so_serial').val() || ''
        };

        downloadExcel(exportDetailedExcelUrl, filters);
    });
});
