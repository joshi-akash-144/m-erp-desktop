let table;
let currentFilter = {};
let currentPermissions = {};
let savedColWidths = {};
let totalFilteredRecords = 0;
let grandTotalRecords = 0;


const FILTER_KEY = "godown_analysis_filter";
const WIDTH_KEY = "godown_analysis_col_widths";

const filterFields = [   
    'start_date', 
    'end_date', 
    'account_id', 
    'supplier_id', 
    'destination_id',
    'grn_id'
];


$(document).ready(function() {    
    bindSelect2();

    // date input with validation
    new DateInput("#start_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#end_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    restoreFilters();

    // Initialize Tabulator
    table = new Tabulator("#godown_analysis_table", {  
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
    });
     
    $("#filter_apply").on("click",  function(e){
        e.preventDefault();       
        applyFilter();
    });
    $("#filter_clear").on("click", function (e) {
        e.preventDefault();               
        clearFilter();                  
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
        printReport(route, { format: format, ...currentFilter });
        break;
 
      case "excel":
        downloadExcel(route, { ...currentFilter });
        break;
    }
  });
  // Handle Delete Action
  $(document).on("click", ".delete", function (e) {
      e.preventDefault();
      const id = $(this).data("id");
      
      Swal.fire({
          title: "Are you sure?",
          text: "You won't be able to revert this!",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#d33",
          cancelButtonColor: "#3085d6",
          confirmButtonText: "Yes, delete it!"
      }).then((result) => {
          if (result.isConfirmed) {
              $.ajax({
                  url: deleteAnalysisUrl.replace(":id", id),
                  type: "DELETE",
                  data: {
                      _token: $('meta[name="csrf-token"]').attr("content")
                  },
                  success: function (response) {
                      if (response.success) {
                          showToast("success", response.message);
                          if (table) table.setData();
                      } else {
                          showToast("error", response.message);
                      }
                  },
                  error: function (xhr) {
                      showToast("error", "An error occurred while deleting the record.");
                  }
              });
          }
      });
  }); 
    
});

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
            formatter: "rownum",
        },
        { 
            title: "DATE", 
            field: "grn_date", 
            headerHozAlign: "center",
            hozAlign: "center", 
            width: 130, 
            vertAlign: "middle",
            headerSort: false
        },
        { 
            title: "GRN Number", 
            field: "grn_serial", 
            headerHozAlign: "center",
            hozAlign: "center", 
            width: 130, 
            vertAlign: "middle",
            headerSort: false
        },
        { 
            title: "Supplier Name", 
            field: "supplier_name", 
            width: 600, 
            vertAlign: "middle",    
            formatter: (cell) => {
                const supplierName = cell.getValue();
                const rowData = cell.getRow().getData(); 
                const city = rowData.city;

                if (supplierName) {
                    const cityText = city && city !== "--" ? ` <span class="fw-semibold text-truncate">(${city})</span>` : "";
                    return `<span class="fw-semibold text-truncate">${supplierName}${cityText}</span>`;              
                }
                
                return "--";
            },        
        },        

        { 
            title: "Rebate Amount", 
            field: "rebate_amount", 
            headerHozAlign: "right",
            hozAlign: "right", 
            width: 150,  
            vertAlign: "middle", 
            headerSort: false,
            bottomCalc: "sum",
            bottomCalcFormatter: function(cell) {                                            
                const val = cell.getValue() || 0;
                return `<span class="fw-bold">${formatIndianNumber(val)}</span>`;
            },                                     
        },
        { 
            title: "Created By", 
            field: "created_by", 
            width: 150, 
            vertAlign: "middle",
            headerSort: false,
            formatter: (cell) => {
                const creatorName = cell.getValue();
                return creatorName && creatorName !== '--'
                    ? `<span class="badge bg-danger-lt ">${creatorName}</span>`
                    : `<span class="badge bg-cyan-lt">--</span>`;
            },
        },
        { 
            title: "Updated By", 
            field: "updated_by", 
            width: 150, 
            vertAlign: "middle",
            headerSort: false,
            formatter: (cell) => {
                const updatorName = cell.getValue();
                return updatorName && updatorName !== '--'
                      ? `<span class="badge bg-danger-lt ">${updatorName}</span>`
                      : `<span class="badge bg-cyan-lt">--</span>`;
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

                const canDelete = currentPermissions.delete ?? false;

                const deleteIcon = icons.delete;

                let actions = '<div class="d-flex gap-2 align-items-center justify-content-center h-100 w-100">';

                if (canDelete) {
                    actions += `
              <span class="erp-btn-icon delete" data-id="${row.id}" title="Delete Godown Analysis">
                ${deleteIcon}
              </span>`;
                }
                actions += "</div>";
                return actions;
            },
        },
    ];
}

function bindSelect2() {
    const selectIdArray = ['#account_id', '#supplier_id', '#destination_id', '#grn_id'];
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

// Mail PDS Report Action
$(document).on("click", "#mailPdsBtn", function (e) {
    e.preventDefault();

    const selectedRows = table.getSelectedData();
    const dairyAnalysisIds = selectedRows.map(row => row.id);
    
    if (dairyAnalysisIds.length === 0) {
         Swal.fire({ 
             icon: "warning", 
             title: "Warning", 
             text: "Please select at least one record to mail.", 
             confirmButtonText: "OK" 
         });
         return;
    }

    // Call the shared function defined in pds-email-modal.js
    openDairyAnalysisEmailModal(dairyAnalysisIds, $(this));
});
