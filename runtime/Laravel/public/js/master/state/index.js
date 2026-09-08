let currentFilter = {};
let currentPermissions = {};
let totalFilteredRecords = 0;
let grandTotalRecords = 0;
$(document).ready(function () {

  bindSelect2()
  
  table = new Tabulator("#state_table", {
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

    pagination: true,
    paginationMode: "remote",
    paginationSize: 50,
    paginationSizeSelector: [  10, 20, 50, 100, 300, true],
    paginationDataSent: {
      page: "page",
      size: "size",
    },
    paginationDataReceived: {
      last_page: "last_page",
      data: "data",
      total: "total",
    },

  

    ajaxURL: stateListUrl,
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
    paginationCounter: function(pageSize, currentRow, _currentPage, totalRows, _totalPages) {
      const total = totalFilteredRecords || totalRows;
      if (!total) return "";
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

  // Events for filters
  document.getElementById("filter_apply")?.addEventListener("click", applyFilter);

  document.getElementById("filter_clear")?.addEventListener("click", clearFilter);

  // Apply filter on Enter
  document.getElementById("search")?.addEventListener("keypress", function (e) {
      if (e.key === "Enter") applyFilter();
  });
   
});

let savedColWidths = {};

function getTableColumns(savedColWidths) {
  return [
    // =======================
    // Row Number Column
    // =======================
    {
      title: "No",
      field: "no",
      width: 80,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      resizable: false,
      formatter: "rownum",
      frozen: true,
    },

    // =======================
    // states Name
    // =======================
    {
      title: "Name",
      field: "name",
      width: savedColWidths.name || 200,
      headerSort: true,
      sorter: "string",
      resizable: true,
    },

    // =======================
    //  states Code
    // =======================
    {
      title: "Code",
      field: "code",
      width: savedColWidths.code || 220,
      headerSort: true,
      sorter: "string",
      resizable: true,
    },

    // =======================
    //  GST CODE
    // =======================
    {
      title: "Gst Code",
      field: "gst_code",
      width: savedColWidths.gst_code || 200,
      headerSort: true,
      sorter: "string",
      resizable: true,
    },
    // =======================
    // Status (Active/Inactive)
    // =======================
    {
      title: "Status",
      field: "status",
      width: savedColWidths.status || 120,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      resizable: true,
      formatter: (cell) =>
        cell.getValue()
          ? `<span class="badge bg-teal-lt">Active</span>`
          : `<span class="badge bg-danger-lt">Inactive</span>`,
    },
    // =======================
    // Created By
    // =======================
    {
      title: "Created By",
      field: "created_by",
      width: savedColWidths.created_by || 150,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      resizable: true,
      formatter: (cell) => {
        const creatorName = cell.getRow().getData().creator?.name || null;
        return creatorName
          ? `<span class="ms-1">${creatorName}</span>`
          : `<span class="badge bg-cyan-lt">System</span>`;
      },
    },

    // =======================
    // Updated By
    // =======================
    {
      title: "Updated By",
      field: "updated_by",
      width: savedColWidths.updated_by || 150,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      resizable: true,
      formatter: (cell) => {
        const updaterName = cell.getRow().getData().updater?.name || null;
        return updaterName ? `<span class="ms-1">${updaterName}</span>` : "--";
      },
    },

    // =======================
    // Action Buttons
    // =======================
    {
      title: "Actions",
      field: "actions",
      width: 150,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      frozen: true,
      formatter: (cell) => {
        const rowData = cell.getData();
        const canView = currentPermissions.view || false;
        const canEdit = currentPermissions.update || false;
        const canDelete = currentPermissions.delete || false;

        const viewIcon = icons.view;
        const editIcon = icons.edit;
        const deleteIcon = icons.delete;

        let actions = '<div class="d-flex gap-2 justify-content-center">';

        if (canView) {
          actions += `
        <a href="${Routes.states.view(rowData.id)}"
            class="erp-btn-icon view"
            title="View Record">
            ${viewIcon}
        </a>`;
        }

        if (canEdit) {
          actions += `
    <a href="${Routes.states.edit(rowData.id)}"
            class="erp-btn-icon edit"
            title="View Record">
            ${editIcon}
        </a>`;

          // if (canDelete) {
          //   actions += `
          //   <span
          //     class="erp-btn-icon delete"
          //     data-id="${rowData.id}"
          //     title="Delete Record">
          //     ${deleteIcon}
          //   </span>`;
          // }
        }
        actions += "</div>";
        return actions || '<span class="text-muted">No actions</span>';
      },
    },
  ];
}


/**
 * Initialize Select2 on form selects + Enter key navigation
 */
function bindSelect2() {
    var selectIdArray = ['#state_id'];

    selectIdArray.forEach(function (element) {
        $(element).select2({
            theme: 'bootstrap-5',
            allowClear: true,
            placeholder: 'Select ...',
        });
    });

    $(document).on('select2:open', function (e) {
        var selectElement = $(e.target);
        var searchInput = selectElement.data('select2').$dropdown.find('.select2-search__field');

        searchInput.off('keydown.select2Enter').on('keydown.select2Enter', function (event) {
            if (event.which === 13) {
                event.preventDefault();
                selectElement.select2('close');
                if (typeof moveFocusToNextField === 'function') {
                    moveFocusToNextField(selectElement);
                }
            }
        });
    });
}
 
  // ==========================================
  // FILTER FUNCTIONS
  // ==========================================
  function applyFilter() {    
    const groupEl = document.getElementById("state_id");
    const searchEl = document.getElementById("search");

    const groupVal = groupEl?.value || "";
    const searchVal = searchEl?.value.trim() || "";

    currentFilter = {};
    if (groupVal) currentFilter.filter_state_id = groupVal;
    if (searchVal) currentFilter.search = searchVal;

    // Save filter settings locally
    localStorage.setItem("state_filter", JSON.stringify(currentFilter));

    // Reload table with new filter applied
    if (table) table.setData();
  }
  
function clearFilter() {
 
  const groupEl = $('.state-select');
  const searchEl = document.getElementById("search");
 
  groupEl.val(null).trigger('change');

  if (searchEl) searchEl.value = "";

  currentFilter = {};
  localStorage.removeItem("state_filter");

  if (table) table.setData();
}
