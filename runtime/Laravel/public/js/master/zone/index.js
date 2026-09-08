/**
 * Generate and return the column configuration for the table.
 * @param {object} savedColWidths - Saved column width settings
 */

function getTableColumns(savedColWidths) {
  return [
    {
      title: "No",
      field: "no",
      width: 80,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      resizable: false,
      frozen: true,
    },
    {
      title: "Name",
      field: "name",
      width: savedColWidths.name || 250,
      headerSort: true,
      sorter: "string",
      resizable: true,
    },
    {
      title: "Rate",
      field: "rate",
      width: savedColWidths.rate || 150,
      headerSort: true,
      sorter: "number",
      resizable: true,
    },
    {
      title: "Remarks",
      field: "remarks",
      width: savedColWidths.remarks || 300,
      headerSort: true,
      sorter: "string",
      resizable: true,
    },
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
          ? `<span class="badge bg-teal-lt ">Active</span>`
          : `<span class="badge bg-danger-lt ">Inactive</span>`,
    },
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
          ? `<span class="badge bg-danger-lt ">${creatorName}</span>`
          : `<span class="badge bg-cyan-lt">System</span>`;
      },
    },
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
        return updaterName ? 
          `<span class="badge bg-danger-lt ">${updaterName}</span>`
          : `<span class="badge bg-cyan-lt">System</span>`;
      },
    },
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
            <span 
              data-module="masters/zone/modal"
              data-init="openZoneViewModal"
              class="erp-btn-icon view js-load-modal"
              data-id="${rowData.id}"
              title="View Record">
              ${viewIcon}
            </span>`;
        }

        if (canEdit) {
          actions += `
            <span 
              data-module="masters/zone/modal"
              data-init="openZoneEditModal"
              class="erp-btn-icon edit js-load-modal"
              data-id="${rowData.id}"
              title="Edit Record">
              ${editIcon}
            </span>`;
        }

        if (canDelete) {
          actions += `
            <span 
              class="erp-btn-icon delete"
              data-id="${rowData.id}"
              title="Delete Record">
              ${deleteIcon}
            </span>`;
        }

        actions += "</div>";
        return actions || '<span class="text-muted">No actions</span>';
      },
    },
  ];
}

let currentFilter = {};
let currentPermissions = {};
let savedColWidths = {};
let table = null;
let totalFilteredRecords = 0;
let grandTotalRecords = 0;

function bindModalEvents() {
  $("#zone_modal").on("shown.bs.modal", function () {
    $('#name').focus();
  });
}

$(document).ready(function () {

  // Initialize select2 for filter
  $('#zone_id').select2({
      theme: 'bootstrap-5',
      placeholder: 'Select an option',      
      width: '100%',                 
  });

  $(".js-load-modal").on("click", function (event) {   
    $.ajax({
      url: zoneCreateUrl,
      beforeSend: function () {
        showLoader("Loading Zone Form...");
      },
      success: function (response) {       
        $("#global_modal_container").html(response.data.html);                
        bindModalEvents();
        $("#zone_modal").modal("show");
      },
      error: function (xhr) {
        showToast("error", 'Oops! Something went wrong. Try again later.');
        console.error(xhr.responseText);
      },
      complete: function () {
        hideLoader();
      },
    });
  });

  table = new Tabulator("#zone_table", {  
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

    ajaxURL: zoneListUrl,
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

  $(document).on("click", ".view", function (event) {
    const zoneId = $(this).data("id");
    const url = zoneViewUrl.replace(":id", zoneId);
    $.ajax({
      url: url,
      beforeSend: function () {
        showLoader("Loading Zone Form...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        table.replaceData();
        bindModalEvents();
        $("#zone_modal").modal("show");
      },
      error: function (xhr) {
        showToast("error", 'Oops! Something went wrong. Try again later.');
        console.error(xhr.responseText);
      },
      complete: function () {
        hideLoader();
      },
    });
  });

  $(document).on("click", ".edit", function (event) {
    const zoneId = $(this).data("id");
    const url = zoneEditUrl.replace(":id", zoneId);
    $.ajax({
      url: url,
      beforeSend: function () {
        showLoader("Loading Zone Form...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        table.replaceData();
        bindModalEvents();
        $("#zone_modal").modal("show");
      },
      error: function (xhr) {
        showToast("error", 'Oops! Something went wrong. Try again later.');
        console.error(xhr.responseText);
      },
      complete: function () {
        hideLoader();
      },
    });
  });

  $(document).on("click", ".delete", function (event) {
    const zoneId = $(this).data("id");
    const url = zoneDeleteUrl.replace(":id", zoneId);

    Swal.fire({
      title: "Are you sure?",
      text: "You won't be able to revert this!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      confirmButtonText: "Yes, delete it!",
      cancelButtonText: "No, cancel!",
      reverseButtons: false,
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: url,
          type: "DELETE",
          beforeSend: function () {
            showLoader("Deleting Zone...");
          },
          success: function (response) {
            showToast("success", response.message || "Zone deleted successfully!");
            table.replaceData();
          },
          error: function (xhr) {
            showToast("error", 'Oops! Something went wrong. Try again later.');
            console.error(xhr.responseText);
          },
          complete: function () {
            hideLoader();
          },
        });
      }
    });
  });

});

document.addEventListener("DOMContentLoaded", function () {
  const searchEl = document.getElementById("search");
  const applyFilterBtn = document.getElementById("filter_apply");
  const clearFilterBtn = document.getElementById("filter_clear");

  if (applyFilterBtn) applyFilterBtn.addEventListener("click", applyFilter);
  if (clearFilterBtn) clearFilterBtn.addEventListener("click", clearFilter);

  if (searchEl) {
    searchEl.addEventListener("keypress", (e) => {
      if (e.key === "Enter") applyFilter();
    });
  }

  const savedFilter = localStorage.getItem("zone_filter");
  if (savedFilter) {
    currentFilter = JSON.parse(savedFilter);
    if (currentFilter.zone_id) {
      $('#zone_id').val(currentFilter.zone_id).trigger('change');
    }
    if (currentFilter.search && searchEl) {
      searchEl.value = currentFilter.search;
    }
  }    

  dynamicFilter("#search, #zone_id");
});

function applyFilter() {
  const groupEl = document.getElementById("zone_id");
  const searchEl = document.getElementById("search");

  const groupVal = groupEl?.value || "";
  const searchVal = searchEl?.value.trim() || "";

  currentFilter = {};
  if (groupVal) currentFilter.zone_id = groupVal;
  if (searchVal) currentFilter.search = searchVal;

  localStorage.setItem("zone_filter", JSON.stringify(currentFilter));

  if (table) table.setData();
}

function clearFilter() {
  const groupEl = document.getElementById("zone_id");
  const searchEl = document.getElementById("search");

  $('#zone_id').val("").trigger("change");
  if (searchEl) searchEl.value = "";

  currentFilter = {};
  localStorage.removeItem("zone_filter");

  if (table) table.setData();
}
