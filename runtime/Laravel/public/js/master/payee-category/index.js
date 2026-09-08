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
      width: 80,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      resizable: false,
      frozen: true,
    },

    // =======================
    // Payee Category
    // =======================
    {
      title: "Payee Category",
      field: "payee_category",
      width: savedColWidths.name || 400,
      headerSort: true,
      sorter: "string",
      resizable: true,
      formatter: (cell) => {
        const val = cell.getValue();
        if (val == null) return "";
        // convert to lowercase then capitalize first letter of each word
        return String(val)
          .replace(/_/g, " ")
          .toLowerCase()
          .replace(/\b\w/g, (ch) => ch.toUpperCase());
      },
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
              data-module="masters/payee-category/modal"
              data-init="openPayeeCategoryViewModal"
              class="erp-btn-icon view js-load-modal"
              data-id="${rowData.id}"
              title="View Record">
              ${viewIcon}
            </span>`;
        }

        if (canEdit) {
          actions += `
            <span 
              data-module="masters/payee-category/modal"
              data-init="openPayeeCategoryEditModal"
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
  $("#payee_category_modal").on("shown.bs.modal", function () {
    $('#payee_category').focus();

    const select2Array  = ['account_group_id', 'type', 'parent_id', 'is_primary'];

    for (let index = 0; index < select2Array.length; index++) {
      const element = select2Array[index];
      $(`#${element}`).select2({
        theme: "bootstrap-5",
        dropdownParent: $("#payee_category_modal"),
      });
    }

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

  // Initialize select2 for filter
  $('#group_id').select2({
      theme: 'bootstrap-5',
      placeholder: 'Select an option',      
      width: '100%',                 
  });

    // Get Modal for create new Payee Category
  $(".js-load-modal").on("click", function (event) {
    $.ajax({
      url: payeeCategoryCreateUrl,
      beforeSend: function () {
        showLoader("Loading Payee Category Form...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        bindModalEvents();
        $("#payee_category_modal").modal("show");
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

  // Account List
  table = new Tabulator("#payee_category_table", {
    dataTree: true,
    height: "550px", // dynamic height that fits most ERP screens
    layout: "fitColumns",
    dataTreeStartExpanded: true,  
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
    paginationSize: parseInt(localStorage.getItem("payee_category_page_size")) || 50,
    paginationInitialPage: parseInt(localStorage.getItem("payee_category_page_num")) || 1,
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

    ajaxURL: payeeCategoryListUrl,
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
    localStorage.setItem("payee_category_page_num", pageNo);
    localStorage.setItem("payee_category_page_size", table.getPageSize());
  });


    // View Payee Category
  $(document).on("click", ".view", function (event) {
    const payeeCategoryId = $(this).data("id");
    const url = payeeCategoryViewUrl.replace(":id", payeeCategoryId);
    $.ajax({
      url: url,
      beforeSend: function () {
        showLoader("Loading Payee Category Form...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        table.replaceData();
        bindModalEvents();
        $("#payee_category_modal").modal("show");
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

    // Edit Payee Category
  $(document).on("click", ".edit", function (event) {
    const payeeCategoryId = $(this).data("id");
    const url = payeeCategoryEditUrl.replace(":id", payeeCategoryId);
    $.ajax({
      url: url,
      beforeSend: function () {
        showLoader("Loading Payee Category Form...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        table.replaceData();
        bindModalEvents();
        $("#payee_category_modal").modal("show");
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

  // Delete Payee category
  $(document).on("click", ".delete", function (event) {
    const payeeCategoryId = $(this).data("id");
    const url = payeeCategoryDeleteUrl.replace(":id", payeeCategoryId);

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
            showLoader("Deleting Payee Category...");
          },
          success: function (response) {
            showToast("success", response.message || "Payee Category deleted successfully!");
            table.replaceData();
          },
          error: function (xhr) {
            let msg = 'Oops! Something went wrong. Try again later.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            Swal.fire({
                title: "Error",
                text: msg,
                icon: "error",
                confirmButtonColor: "#d33"
            });
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
        printReport(route, { format: format, currentFilter });
        break;

      case "excel":
        downloadExcel(route, { currentFilter});
        break;
    }
  });
  
});

document.addEventListener("DOMContentLoaded", function () {
 
  const searchEl = document.getElementById("search");
  const applyFilterBtn = document.getElementById("filter_apply");
  const clearFilterBtn = document.getElementById("filter_clear");

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
  const savedFilter = localStorage.getItem("payee_category_filter");
  if (savedFilter) {
    currentFilter = JSON.parse(savedFilter);
    if (currentFilter.filter_group_id) {
      $('#group_id').val(currentFilter.filter_group_id).trigger('change');
    }
    if (currentFilter.search && searchEl) {
      searchEl.value = currentFilter.search;
    }
  }
  
  dynamicFilter("#search, #group_id"); 
});


 /**
 * Apply current filter to the table.
 * Stores selected filters in localStorage for persistence.
 */
function applyFilter() {
  const groupEl = document.getElementById("group_id");
  const searchEl = document.getElementById("search");

  const groupVal = groupEl?.value || "";
  const searchVal = searchEl?.value.trim() || "";

  currentFilter = {};
  if (groupVal) currentFilter.filter_group_id = groupVal;
  if (searchVal) currentFilter.search = searchVal;

  // Save filter settings locally
  localStorage.setItem("payee_category_filter", JSON.stringify(currentFilter));

  // Reload table with new filter applied
  if (table) table.setData();
}

/**
 * Clear all applied filters.
 * Resets filter UI elements and clears saved localStorage filters.
 */
function clearFilter() {
  const groupEl = document.getElementById("group_id");
  const searchEl = document.getElementById("search");

  $('#group_id').val("").trigger("change");
  if (searchEl) searchEl.value = "";

  currentFilter = {};
  localStorage.removeItem("payee_category_filter");

  if (table) table.setData();
}