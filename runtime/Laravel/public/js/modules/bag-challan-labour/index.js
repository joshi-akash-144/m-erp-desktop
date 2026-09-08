let table;
let currentFilter = {};
let totalFilteredRecords = 0;
let grandTotalRecords = 0;

const FILTER_KEY = "bag_challan_labour_list_filter";

// ===========================================================
// HELPER FUNCTIONS
// ===========================================================

function getTableColumns() {
    return [
        {
            title: "No",
            formatter: "rownum",
            width: 70,
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
        },        
        {
            title: "Bags",
            field: "bags",
            width: 110,
            hozAlign: "right",
            headerHozAlign: "right",
            headerSort: false,
            formatter: (cell) => parseFloat(cell.getValue() || 0).toFixed(2),
        },
        {
            title: "Rate",
            field: "rate",
            width: 110,
            hozAlign: "right",
            headerHozAlign: "right",
            headerSort: false,
            formatter: (cell) => parseFloat(cell.getValue() || 0).toFixed(2),
        },
        {
            title: "Bag Amount",
            field: "bag_amount",
            width: 140,
            hozAlign: "right",
            headerHozAlign: "right",
            headerSort: false,
            formatter: (cell) => parseFloat(cell.getValue() || 0).toFixed(2),
        },
        {
            title: "Loading Amount",
            field: "loading_amount",
            width: 150,
            hozAlign: "right",
            headerHozAlign: "right",
            headerSort: false,
            formatter: (cell) => parseFloat(cell.getValue() || 0).toFixed(2),
        },
        {
            title: "Total Amount",
            field: "total_amount",
            width: 140,
            hozAlign: "right",
            headerHozAlign: "right",
            headerSort: false,
            formatter: (cell) => `<span class="fw-bold">${parseFloat(cell.getValue() || 0).toFixed(2)}</span>`,
        },
        {
            title: "Action",
            field: "id",
            hozAlign: "center",
            headerHozAlign: "center",
            vertAlign: "middle",
            headerSort: false,
            width: 80,
            formatter(cell) {
                const row = cell.getRow().getData();
                const printIcon = icons.print;

                let actions =
                '<div class="d-flex gap-2 align-items-center justify-content-center h-100 w-100">';
                    actions += `<span class="erp-btn-icon print" data-id="${row.id}" style="stroke-width:1px !important" title="Print">
                                        ${printIcon}
                                    </span>`;
                actions += "</div>";
                return actions; 
            },
        },
    ];
}

$(document).on("click", ".print", function (e) {
    e.preventDefault();
    const id = $(this).data("id");    
    
    if (!id) {
        alert("Missing id for this record.");
        return;
    }

    $.ajax({
        type: "get",
        url: bagsChallanLabourPrintUrl,
        data: {
            bagsChallanLabour_id: id,
        },
        success: function (response) {
            if (!response.success) {
                ShowToast(response.message, "error");
                return;
            }
            var w = window.open("", "_blank");
            w.document.write(response.data.html);
            w.document.close();
            w.onload = function () {
                w.print();
            };
        },
        error: function (xhr) {
            console.log(xhr.responseJSON?.message || "Failed to fetch print data.");
        }
    });
    
});



// ===========================================================
// FILTER FUNCTIONS
// ===========================================================

function applyFilter() {
    const startDate = $("#start_date").val();
    const endDate   = $("#end_date").val();

    currentFilter = {};

    if (startDate && isValidDateDMY(startDate)) {
        currentFilter.start_date = startDate;
    }
    if (endDate && isValidDateDMY(endDate)) {
        currentFilter.end_date = endDate;
    }

    localStorage.setItem(FILTER_KEY, JSON.stringify(currentFilter));
    if (table) table.setData();
}

function clearFilter() {
    $("#start_date").val("");
    $("#end_date").val("");

    currentFilter = {};
    localStorage.removeItem(FILTER_KEY);
    if (table) table.setData();
}

function restoreFilters() {
    const saved = localStorage.getItem(FILTER_KEY);
    if (!saved) return;
    currentFilter = JSON.parse(saved);
    if (currentFilter.start_date) $("#start_date").val(currentFilter.start_date);
    if (currentFilter.end_date)   $("#end_date").val(currentFilter.end_date);
}

// ===========================================================
// DOM INITIALIZATION
// ===========================================================
$(function () {
    // Date pickers
    if (typeof DateInput !== "undefined") {
        new DateInput("#start_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
        new DateInput("#end_date",   FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    }

    restoreFilters();

    // Tabulator
    table = new Tabulator("#bag_challan_labour_list_table", {
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
        </div>`,

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
        ajaxURL: BagChallanLabourListUrl,
        ajaxURLGenerator: (url, _config, params) => {
            const page = params.page || 1;
            const size = params.size || 50;
            const queryParams = new URLSearchParams({ ...currentFilter, page, size });
            return `${url}?${queryParams.toString()}`;
        },
        ajaxResponse(_url, _params, response) {
            totalFilteredRecords = response.total || 0;
            grandTotalRecords    = response.grand_total || 0;
            return response;
        },
        paginationCounter: function (pageSize, currentRow, _currentPage, totalRows, _totalPages) {
            const total = totalFilteredRecords || totalRows;
            if (!total) return "No entries found";
            const start = currentRow;
            const end   = Math.min(currentRow + pageSize - 1, total);
            let text = `Showing ${start} to ${end} of ${total} entries`;
            if (grandTotalRecords > 0 && grandTotalRecords !== total) {
                text += ` (filtered from ${grandTotalRecords} total entries)`;
            }
            return text;
        },
        columns: getTableColumns(),
    });

    // Filter buttons
    $("#filter_apply").on("click", function (e) {
        e.preventDefault();
        applyFilter();
    });

    $("#filter_clear").on("click", function (e) {
        e.preventDefault();
        clearFilter();
    });
});
