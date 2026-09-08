/**
 * Generate and return the column configuration for the table.
 * @param {object} savedColWidths - Saved column width settings
 */
function getTableColumns(savedColWidths) {
  return [
    // =======================
    // Row Number Column
    // =======================
    {
      title: "No",
      field: "no",
      width: 50,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      resizable: false,
      formatter: "rownum",
      frozen: true,
    },

    // =======================
    // Bank Configuration Bank Name
    // =======================
    {
        title: "Bank Name",
        field: "bank_id",
        width: savedColWidths.name || 200,
        headerSort: true,
        sorter: "string",
        resizable: true,
        formatter: (cell) => {
            const bankNames = {
                "1": "HDFC Bank",
                "2": "AXIS Bank",
                "3": "Bank Of Baroda",
                "4": "State Bank Of India"
            };
            const val = String(cell.getValue() ?? "").trim();
            return bankNames[val] || val || "--";
        },
    },

    // =======================
    // Bank Configuration RTGS Name
    // =======================
    {
      title: "RTGS Name",
      field: "rtgs_id",
      width: savedColWidths.name || 200,
      headerSort: true,
      sorter: "string",
      resizable: true,
      formatter: (cell) => {
            const rtgsForm = {
                "1": "HDFC RTGS Form",
                "2": "AXIS RTGS Form",
                "3": "BOB RTGS Form",
                "4": "SBI RTGS Form"
            };
            const val = String(cell.getValue() ?? "").trim();
            return rtgsForm[val] || val || "--";
        },
    },

    // =======================
    // Bank Configuration Cheque Name
    // =======================
    {
      title: "Cheque Name",
      field: "cheque_id",
      width: savedColWidths.name || 200,
      headerSort: true,
      sorter: "string",
      resizable: true,
      formatter: (cell) => {
            const chequeName = {
                "1": "HDFC Cheque",
                "2": "AXIS Cheque",
                "3": "BOB Cheque",
                "4": "SBI Cheque"
            };
            const val = String(cell.getValue() ?? "").trim();
            return chequeName[val] || val || "--";
        },
    },

    // =======================
    // Status (Active/Inactive)
    // =======================
    {
      title: "Status",
      field: "status",
      width: 100,
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
          ? `<span class="badge bg-danger-lt ">${creatorName}</span>`
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
        return updaterName        
          ? `<span class="badge bg-danger-lt ">${updaterName}</span>`
          : `<span class="badge bg-cyan-lt">--</span>`;
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
            <span 
              data-module="masters/bank-configuration/modal"
              data-init="openBankConfigurationViewModal"
              class="erp-btn-icon view js-load-modal"
              data-id="${rowData.id}"
              title="View Record">
              ${viewIcon}
            </span>`;
        }

        if (canEdit) {
          actions += `
            <span 
              data-module="masters/bank-configuration/modal"
              data-init="openBankConfigurationEditModal"
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
  $("#bank_configuration_modal").on("shown.bs.modal", function () {
  
    const select2Array  = ['bank_id', 'rtgs_id', 'cheque_id'];

    for (let index = 0; index < select2Array.length; index++) {
      const element = select2Array[index];
      $(`#${element}`).select2({
        theme: "bootstrap-5",
        dropdownParent: $("#bank_configuration_modal"),
      });
    }
    $('#bank_id').focus();

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
  });
}


$(document).ready(function () {
  // Get Modal for create new bank configuration
  $(".js-load-modal").on("click", function (event) {
    $.ajax({
      url: bankConfigurationCreateUrl,
      beforeSend: function () {
        showLoader("Loading Bank Configuration Form...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        bindModalEvents();
        $("#bank_configuration_modal").modal("show");        
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

  // Bank Configuration List
  table = new Tabulator("#bank_configuration_table", {
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
    paginationSize: parseInt(localStorage.getItem("bank_config_page_size")) || 50,
    paginationInitialPage: parseInt(localStorage.getItem("bank_config_page_num")) || 1,
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

    ajaxURL: bankConfigurationListUrl,
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

  table.on("pageLoaded", function(pageNo) {
    localStorage.setItem("bank_config_page_num", pageNo);
    localStorage.setItem("bank_config_page_size", table.getPageSize());
  });

  // Edit Bank Configuration
  $(document).on("click", ".edit", function (event) {
    const bankConfigurationId = $(this).data("id");
    const url = bankConfigurationEditUrl.replace(":id", bankConfigurationId);
    $.ajax({
      url: url,
      beforeSend: function () {
        showLoader("Loading Bank Configuration Form...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        table.replaceData();
        bindModalEvents();
        $("#bank_configuration_modal").modal("show");
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

   // View Bank Configuration
  $(document).on("click", ".view", function (event) {
    const bankConfigurationId = $(this).data("id");
    const url = bankConfigurationViewUrl.replace(":id", bankConfigurationId);
    $.ajax({
      url: url,
      beforeSend: function () {
        showLoader("Loading Bank Configuration Form...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        table.replaceData();
        bindModalEvents();
        $("#bank_configuration_modal").modal("show");
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


  // Delete Bank Configuration
  $(document).on("click", ".delete", function (event) {
    const bankConfigurationId = $(this).data("id");
    const url = bankConfigurationDeleteUrl.replace(":id", bankConfigurationId);

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
            showLoader("Deleting Bank Configuration...");
          },
          success: function (response) {
            showToast("success", response.message || "Bank Configuration deleted successfully!");
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
        printReport(route, { format: format });
        break;

      case "excel":
        downloadExcel(route);
        break;
    }
  });
});

document.addEventListener("DOMContentLoaded", function () {
 
  const searchEl = document.getElementById("search");
  const applyFilterBtn = document.getElementById("filter_apply");
  const clearFilterBtn = document.getElementById("filter_clear");
  
  dynamicFilter("#search"); 
  
  // Apply Filter
  if (applyFilterBtn) applyFilterBtn.addEventListener("click", applyFilter);

  // Clear Filter
  if (clearFilterBtn) clearFilterBtn.addEventListener("click", clearFilter);

  // Apply filter on Enter key
  if (searchEl) {
    searchEl.addEventListener("keypress", (e) => {
      if (e.key === "Enter") applyFilter();
    });
  }

  // -----------------------------------
  // Load Saved Filters
  // -----------------------------------
  const savedFilter = localStorage.getItem("bank_configuration_filter");
  if (savedFilter) {
    currentFilter = JSON.parse(savedFilter);
    if (currentFilter.filter_group_id) {
      $('#group_id').val(currentFilter.filter_group_id).trigger('change');
    }
    if (currentFilter.search && searchEl) {
      searchEl.value = currentFilter.search;
    }
  }

 
});


function applyFilter() {  
  const searchEl = document.getElementById("search");

  const searchVal = searchEl?.value.trim() || "";

  currentFilter = {};
  if (searchVal) currentFilter.search = searchVal;

  // Save filter settings locally
  localStorage.setItem("bank_configuration_filter", JSON.stringify(currentFilter));

  // Reload table with new filter applied
  if (table) table.setData();
}

/**
 * Clear all applied filters.
 * Resets filter UI elements and clears saved localStorage filters.
 */
function clearFilter() {  
  const searchEl = document.getElementById("search");

  // if (groupEl?.tomselect) groupEl.tomselect.setValue("", false);
  if (searchEl) searchEl.value = "";

  currentFilter = {};
  localStorage.removeItem("bank_configuration_filter");

  if (table) table.setData();
}