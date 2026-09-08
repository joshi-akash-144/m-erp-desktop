let table;
let currentFilter = {};
let currentPermissions = {};
let totalFilteredRecords = 0;
let grandTotalRecords = 0;

/**
 * Generate and return the column configuration for the table.
 */
function getTableColumns() {
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
    // Main Unit
    // =======================
    {
      title: "Main Unit",
      field: "main_unit.name",
      width: 150,
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
    // Sub Unit
    // =======================
    {
      title: "Sub Unit",
      field: "sub_unit.name",
      width: 150,
      headerSort: false,
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
    // Conversion Factor
    // =======================
    {
      title: "Conversion Factor",
      field: "conversion_factor",
      width: 200,
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
      width: 200,
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
      width: 200,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      resizable: true,
      formatter: (cell) => {
        const creatorName = cell.getRow().getData().creator?.name || null;
        return creatorName
          ? `<span class="badge bg-danger-lt">${creatorName}</span>`
          : `<span class="badge bg-cyan-lt">System</span>`;
      },
    },

    // =======================
    // Updated By
    // =======================
    {
      title: "Updated By",
      field: "updated_by",
      width: 200,
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
              class="erp-btn-icon view js-view-item"
              data-id="${rowData.id}"
              title="View Record">
              ${viewIcon}
            </span>`;
        }

        if (canEdit) {
          actions += `
            <span 
              class="erp-btn-icon edit js-edit-item"
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

// ===========================================================
// FUNCTION DEFINITIONS
// ===========================================================

/**
 * Apply current filter to the table.
 * Stores selected filters in localStorage for persistence.
 */
function applyFilter() {
  const unitConversionEl = document.getElementById("unit_conversion_id");
  const searchEl = document.getElementById("search");

  const unitConversionVal = unitConversionEl?.value || "";
  const searchVal = searchEl?.value.trim() || "";

  currentFilter = {};
  if (unitConversionVal) currentFilter.filter_unit_conversion_id = unitConversionVal;
  if (searchVal) currentFilter.search = searchVal;

  // Save filter settings locally
  localStorage.setItem("unit_conversion_filter", JSON.stringify(currentFilter));

  // Reload table with new filter applied
  if (table) table.setData();
}

/**
 * Clear all applied filters.
 * Resets filter UI elements and clears saved localStorage filters.
 */
function clearFilter() {
  const unitConversionEl = document.getElementById("unit_conversion_id");
  const searchEl = document.getElementById("search");

  if(unitConversionEl) $("#unit_conversion_id").val("").trigger("change"); 
  if (searchEl) searchEl.value = "";

  currentFilter = {};
  localStorage.removeItem("unit_conversion_filter");

  if (table) table.setData();
}

/**
 * Bind events inside dynamically loaded modals
 */
function bindModalEvents() {
  $("#unit_conversion_modal").on("shown.bs.modal", function () {
    const select2Array  = ['main_unit_id', 'sub_unit_id'];

    for (let index = 0; index < select2Array.length; index++) {
      const element = select2Array[index];  
      $(`#${element}`).select2({
        theme: "bootstrap-5",
        dropdownParent: $("#unit_conversion_modal"),
      });
    }

    // Auto-focus the Select2 element correctly
    $('#main_unit_id').select2('focus');

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


// ===========================================================
// DOM INITIALIZATION
// ===========================================================
$(function () {
  // -----------------------------------
  // Initialize Select2
  // -----------------------------------
  $("#unit_conversion_id").select2({
    placeholder: "Select Unit Conversion",
    allowClear: true,
    theme: "bootstrap-5",
    width: "100%",
  });

  const $unitConversionEl = $("#unit_conversion_id");
  const $searchEl = $("#search");
  const $applyFilterBtn = $("#filter_apply");
  const $clearFilterBtn = $("#filter_clear");

  // -----------------------------------
  // Load Saved Filters
  // -----------------------------------
  const savedFilter = localStorage.getItem("unit_conversion_filter");
  if (savedFilter) {
    try {
      currentFilter = JSON.parse(savedFilter);
      if (currentFilter.filter_unit_conversion_id) {
        $unitConversionEl.val(currentFilter.filter_unit_conversion_id).trigger("change");
      }
      if (currentFilter.search) {
        $searchEl.val(currentFilter.search);
      }
    } catch (e) {
      console.error("Failed to parse saved filter:", e);
    }
  }

  dynamicFilter("#search, #unit_conversion_id");
  // -----------------------------------
  // Create Tabulator Table
  // -----------------------------------
  table = new Tabulator("#unit_conversion_table", {
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

    ajaxURL: unitConversionListUrl,
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
    columns: getTableColumns(),
  });

  // Register table globally for refresh usage
  if (typeof registerTable === "function") {
    registerTable("unit_conversion_table", table);
  }

  // -----------------------------------
  // Register Generic Event Listeners
  // -----------------------------------

  // Apply Filter
  $applyFilterBtn.on("click", applyFilter);

  // Clear Filter
  $clearFilterBtn.on("click", clearFilter);

  // Apply filter on Enter key
  $searchEl.on("keypress", (e) => {
    if (e.key === "Enter") {
        e.preventDefault();
        applyFilter();
    }
  });



  // Select2 enter-key navigation (Generic handled in main.js or here if specific)
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
          if (typeof moveFocusToNextField === "function") {
            moveFocusToNextField(selectElement);
          }
        }
      });
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
        printReport(route, { format: format });
        break;

      case "excel":
        downloadExcel(route);
        break;
    }
  });

  // -----------------------------------
  // Action Button Listeners
  // -----------------------------------

  // Get Modal for create new Item
  $(document).on("click", ".js-load-modal", function (event) {
    event.preventDefault();
    $.ajax({
      url: unitConversionCreateUrl,
      beforeSend: function () {
        showLoader("Loading Unit Conversion Form...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        bindModalEvents();
        $("#unit_conversion_modal").modal("show");
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

  // Edit Unit Conversion
  $(document).on("click", ".js-edit-item", function (event) {
    const itemId = $(this).data("id");
    const url = unitConversionEditUrl.replace(":id", itemId);
    $.ajax({
      url: url,
      beforeSend: function () {
        showLoader("Loading Unit Conversion Form...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        bindModalEvents();
        setTimeout(function () {
          $("#unit_conversion_form").attr("data-method", "PUT");
        }, 200);
        $("#unit_conversion_modal").modal("show");
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

  // View Unit Conversion
  $(document).on("click", ".js-view-item", function (event) {
    const itemId = $(this).data("id");
    const url = unitConversionViewUrl.replace(":id", itemId);
    $.ajax({
      url: url,
      beforeSend: function () {
        showLoader("Loading Unit Conversion Details...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        bindModalEvents();
        
        // Disable fields for View Mode
        const $modalEl = $("#unit_conversion_modal");
        $modalEl.addClass("view-mode");
        $modalEl.find("input, select, textarea").prop("disabled", true);
        $modalEl.find(".select2").each(function () {
          $(this).prop("disabled", true).trigger("change.select2");
        });

        setTimeout(function () {
          $("#unit_conversion_form").attr("data-method", "PUT");
        }, 200);

        $("#unit_conversion_modal").modal("show");
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

  // Delete Unit Conversion
  $(document).on("click", ".delete", function (event) {
    const itemId = $(this).data("id");
    const url = unitConversionDeleteUrl.replace(":id", itemId);

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
            showLoader("Deleting Unit Conversion...");
          },
          success: function (response) {
            showToast("success", response.message || "Unit Conversion deleted successfully!");
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