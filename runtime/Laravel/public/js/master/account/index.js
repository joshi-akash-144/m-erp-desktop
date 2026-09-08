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
    // Account Name
    // =======================
    {
      title: "Name",
      field: "name",
      width: savedColWidths.name || 370,
      headerSort: false,
      sorter: "string",
      resizable: true,
      formatter: (cell) => {
        const val = cell.getValue();
        if (val == null) return "";
        // convert to lowercase then capitalize first letter of each word
        return String(val);
      },
    },
    // =======================
    // City
    // =======================
    {
      title: "City",
      field: "city",
      width: savedColWidths.city || 200,
      headerSort: false,
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
    // Parent Group
    // =======================
    {
      title: "Parent Group",
      field: "account_group.name",
      width: 200,
      headerSort: false,
      sorter: "string",
      resizable: true,
      formatter: (cell) => {
        const val = cell.getValue();
        if (val == null) return "";

        const formatted = String(val)
          .replace(/_/g, " ")
          .toLowerCase()
          .replace(/\b\w/g, (ch) => ch.toUpperCase());

        // Apply the class here
        return `<div class="text-truncate" title="${formatted}"> ${formatted}</div>`;
      },
    },

    // =======================
    // Opening Balance (Dr)
    // =======================
    {
      title: "Opening Balance (Dr)",
      field: "current_year_balance.opening_balance",
      width: 180,
      headerHozAlign: "right",
      headerSort: false,
      sorter: "string",
      resizable: true,
      hozAlign: "right",
      formatter: (cell) => {
        const row = cell.getData();
        let balance = " ";
        if (row?.current_year_balance?.opening_type === "D") {
          balance = row?.current_year_balance?.opening_balance ?? " ";
        }
        return balance == 0 ? "--" : balance;
      },
    },
    // =======================
    // Opening Balance (Cr)
    // =======================
    {
      title: "Opening Balance (Cr)",
      field: "balance_in_cr",
      width: 180,
      headerSort: false,
      headerHozAlign: "right",
      sorter: "string",
      resizable: true,
      hozAlign: "right",
      formatter: (cell) => {
        const row = cell.getData();
        let balance = " ";
        if (row?.current_year_balance?.opening_type === "C") {
          balance = row?.current_year_balance?.opening_balance ?? " ";
        }
        return balance == 0 ? "--" : balance;
      },
    },
    // =======================
    // Type of Dealer
    // =======================
    {
      title: "Type Of Dealer",
      field: "tax_detail.type_of_dealer",
      width: 150,
      headerSort: false,
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
      width: savedColWidths.created_by || 120,
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
      width: savedColWidths.updated_by || 120,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      resizable: true,
      formatter: (cell) => {
        const updaterName = cell.getRow().getData().updater?.name || null;
        return updaterName ? `<span class="badge bg-danger-lt ">${updaterName}</span>`
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
              data-module="masters/account/modal"
              data-init="openAccountViewModal"
              class="erp-btn-icon view js-load-modal"
              data-id="${rowData.id}"
              title="View Record">
              ${viewIcon}
            </span>`;
        }

        if (canEdit) {
          actions += `
            <span 
              class="erp-btn-icon edit js-edit-account"
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
  $("#account_modal").on("shown.bs.modal", function () {
    $('#name').focus();

    const select2Array  = ['account_group_id', 'party_type', 'tax_type', 'country_id', 'state_id', 'type_of_dealer', 'filing_frequency', 'gst_type', 'tax_category_id', 'itc_eligibility', 'rcm_nature', 'transport_mode', 'is_billwise', 'opening_type', 'cheque_id','rtgs_id'];

    for (let index = 0; index < select2Array.length; index++) {
      const element = select2Array[index];
      $(`#${element}`).select2({
        theme: "bootstrap-5",
        dropdownParent: $("#account_modal"),
      });
    }

    $(document).on("input", "#name", function () {
      const value = $(this).val() || "";
      $("#print_name").val(value);
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
  });
}


$(document).ready(function () {

  // Initialize select2 for filter
  $('#party_type_id, #account_group_id_index').select2({
    theme: 'bootstrap-5',
    placeholder: 'Select an option',
    width: '100%',
  });

  // Get Modal for create new account
  $(".js-load-modal").on("click", function (event) {
    $.ajax({
      url: accountCreateUrl,
      beforeSend: function () {
        showLoader("Loading Account Form...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        bindModalEvents();
        $("#account_modal").modal("show");
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
  table = new Tabulator("#account_table", {
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
    paginationSize: parseInt(localStorage.getItem("account_page_size")) || 50,
    paginationInitialPage: parseInt(localStorage.getItem("account_page_num")) || 1,
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

    ajaxURL: accountListUrl,
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
      localStorage.setItem("account_page_num", pageNo);
      localStorage.setItem("account_page_size", table.getPageSize());
  });

    // View Account
  $(document).on("click", ".view", function (event) {
    const accountId = $(this).data("id");
    const url = accountViewUrl.replace(":id", accountId);
    $.ajax({
      url: url,
      beforeSend: function () {
        showLoader("Loading Account Form...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        table.replaceData();
        bindModalEvents();
        $("#account_modal").modal("show");
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

  // Edit Account
  $(document).on("click", ".edit", function (event) {
    const accountId = $(this).data("id");
    const url = accountEditUrl.replace(":id", accountId);
    $.ajax({
      url: url,
      beforeSend: function () {
        showLoader("Loading Account Form...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        table.replaceData();
        bindModalEvents();
        $("#account_modal").modal("show");
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

  // Delete Account
  $(document).on("click", ".delete", function (event) {
    const accountId = $(this).data("id");
    const url = accountDeleteUrl.replace(":id", accountId);

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
            showLoader("Deleting Account...");
          },
          success: function (response) {
            showToast("success", response.message || "Account deleted successfully!");
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
        printReport(route, { format: format, currentFilter });
        break;

      case "excel":
        downloadExcel(route, { currentFilter });
        break;
    }
  });

  const searchEl = document.getElementById("search");
  const applyFilterBtn = document.getElementById("filter_apply");
  const clearFilterBtn = document.getElementById("filter_clear");

  // -----------------------------------
  // Load Saved Filters
  // -----------------------------------
  const savedFilter = localStorage.getItem("account_filter");
  if (savedFilter) {
    currentFilter = JSON.parse(savedFilter);
    if (currentFilter.filter_type_id) {
      $('#party_type_id').val(currentFilter.filter_type_id).trigger('change');
    }
    if (currentFilter.account_group_id) {
      $('#account_group_id_index').val(currentFilter.account_group_id).trigger('change');
    }
    if (currentFilter.search && searchEl) {
      searchEl.value = currentFilter.search;
    }
  }

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

  dynamicFilter("#search, #party_type_id, #account_group_id_index"); 


});


/**
* Apply current filter to the table.
* Stores selected filters in localStorage for persistence.
*/
function applyFilter() {
  const groupEl = document.getElementById("party_type_id");
  const accountGroupEl = document.getElementById("account_group_id_index");
  const searchEl = document.getElementById("search");

  const groupVal = groupEl?.value || "";
  const accountGroupVal = accountGroupEl?.value || "";
  const searchVal = searchEl?.value.trim() || "";

  currentFilter = {};
  if (groupVal) currentFilter.filter_type_id = groupVal;
  if (accountGroupVal) currentFilter.account_group_id = accountGroupVal;
  if (searchVal) currentFilter.search = searchVal;

  // Save filter settings locally
  localStorage.setItem("account_filter", JSON.stringify(currentFilter));

  // Reload table with new filter applied
  if (table) table.setData();
}

/**
 * Clear all applied filters.
 * Resets filter UI elements and clears saved localStorage filters.
 */
function clearFilter() {
  const groupEl = document.getElementById("party_type_id");
  const searchEl = document.getElementById("search");

  $('#party_type_id').val("").trigger("change");
  $('#account_group_id_index').val("").trigger("change");
  if (searchEl) searchEl.value = "";

  currentFilter = {};
  localStorage.removeItem("account_filter");

  if (table) table.setData();
}