let table;
let currentFilter = {};
let totalFilteredRecords = 0;
let grandTotalRecords = 0;

// ===========================================================
// HELPER FUNCTIONS
// ===========================================================

function getTableColumns() {
    return [
        {
            title: "No",
            formatter: "rownum",
            width: 80,
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
        },
        {
            title: "Vehicle Number",
            field: "vehicle_number",
            width: 300,
            headerSort: false,
            sorter: "string",
        },
        {
            title: "Transporter",
            field: "transporter.name",
            width: 300,
            headerSort: false,
            formatter: (cell) => cell.getValue() || "N/A",
        },
        {
            title: "GRN Number",
            field: "grn_serial",
            width: 150,
            headerSort: false,
        },
        {
            title: "LR Number",
            field: "lr_number",
            width: 100,
            headerSort: false,
        },
        {
            title: "Gross Weight",
            field: "gross_weight",
            width: 170,
            hozAlign: "right",
            headerHozAlign: "right",
            headerSort: false,
            formatter: (cell) => parseFloat(cell.getValue() || 0).toFixed(3),
        },
        {
            title: "Tare Weight",
            field: "tare_weight",
            width: 200,
            hozAlign: "right",
            headerHozAlign: "right",
            headerSort: false,
            formatter: (cell) => parseFloat(cell.getValue() || 0).toFixed(3),
        },
        {
            title: "Net Weight",
            field: "net_weight",
            width: 200,
            hozAlign: "right",
            headerHozAlign: "right",
            headerSort: false,
            formatter: (cell) => parseFloat(cell.getValue() || 0).toFixed(3),
        },
        {
            title: "Net Wt (W/O Bag)",
            field: "net_weight_wt_bag",
            width: 190,
            hozAlign: "right",
            headerHozAlign: "right",
            headerSort: false,
            formatter: (cell) => parseFloat(cell.getValue() || 0).toFixed(3),
        },
    ];
}

// ===========================================================
// FILTER FUNCTIONS
// ===========================================================

function applyFilter() {
    const transporterId = $("#transporter_id").val();
    const lrNumber = $("#lr_number").val();
    const startDate = $("#start_date").val();
    const endDate = $("#end_date").val();

    currentFilter = {};
    if (transporterId) currentFilter.transporter_id = transporterId;
    if (lrNumber) currentFilter.lr_number = lrNumber;

    // Convert DD-MM-YYYY to Y-m-d for backend
    if (startDate && isValidDateDMY(startDate)) {
        currentFilter.start_date = formatDateToYMD(startDate);
    }
    if (endDate && isValidDateDMY(endDate)) {
        currentFilter.end_date = formatDateToYMD(endDate);
    }

    if (table) table.setData();
}

function clearFilter() {
    $("#transporter_id").val("").trigger("change");
    $("#lr_number").val("");
    $("#start_date").val("");
    $("#end_date").val("");

    currentFilter = {};
    if (table) table.setData();
}

// ===========================================================
// DOM INITIALIZATION
// ===========================================================
$(function () {
    const $applyFilterBtn = $("#filter_apply");
    const $clearFilterBtn = $("#filter_clear");

    // date input with validation
    new DateInput("#start_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#end_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    // Initialize Select2
    bindSelect2();

    // Create Tabulator Table
    table = new Tabulator("#transporter_list_table", {
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
        ajaxURL: transporterListUrl,
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
        paginationCounter: function(pageSize, currentRow, _currentPage, totalRows, _totalPages) {
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

    // Apply Filter
    $applyFilterBtn.on("click",function(e){
        e.preventDefault();
        applyFilter();
    });

    // Clear Filter
    $clearFilterBtn.on("click", function(e){
        e.preventDefault();
        clearFilter();
    });

    // Apply filter on Enter key
    $("#search").on("keypress", (e) => {
        if (e.key === "Enter") applyFilter();
    });

    // Report actions click listener
    $(document).on("click", ".dropdown-item", function (e) {
        const action = $(this).closest(".dropdown-item");
        const type = action.data("type");
        const route = action.data("route");
        const format = action.data("format");

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

function bindSelect2() {
    const selectIdArray = ['#transporter_id'];
    selectIdArray.forEach(element => {
        $(element).select2({
            theme: "bootstrap-5",
            allowClear: true,
            placeholder: "Select " + element.replace('#', '').replace('.', '').replace(/_/g, ' ').replace('_id', '').replace('id', '') + "...",
            width: '100%',
        });
    });

    $(document).on("select2:open", function (e) {
        const selectElement = $(e.target);
        const searchInput = selectElement
            .data("select2")
            .$dropdown.find(".select2-search__field");

        // Fix for Select2 focus jump in jQuery 3.6+
        if (searchInput.length > 0) {
            setTimeout(() => {
                searchInput[0].focus();
            }, 0);
        }

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