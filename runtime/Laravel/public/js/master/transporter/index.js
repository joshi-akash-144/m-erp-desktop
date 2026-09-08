let table;
let currentFilter = {};
let currentPermissions = {};
let savedColWidths = {};
let totalFilteredRecords = 0;
let grandTotalRecords = 0;

// ===========================================================
// HELPER FUNCTIONS
// ===========================================================

function capitalizeFormatter(cell) {
  const val = cell.getValue();
  if (val == null) return "";
  return String(val)
    .replace(/_/g, " ")
    .toLowerCase()
    .replace(/\b\w/g, (ch) => ch.toUpperCase());
}

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
      formatter: "rownum",
    },
    {
      title: "Name",
      field: "name",
      width: savedColWidths.name || 250,
      headerSort: true,
      sorter: "string",
      resizable: true,
      formatter: capitalizeFormatter,
    },
    {
      title: "GSTIN",
      field: "gstin",
      width: savedColWidths.gstin || 150,
      headerSort: true,
      sorter: "string",
      resizable: true,
    },
    {
      title: "PAN No",
      field: "pan_no",
      width: savedColWidths.pan_no || 150,
      headerSort: true,
      sorter: "string",
      resizable: true,
    },
    {
      title: "Mobile",
      field: "mobile",
      width: savedColWidths.mobile || 150,
      headerSort: true,
      sorter: "string",
      resizable: true,
    },
    {
      title: "City",
      field: "city",
      width: savedColWidths.city || 150,
      headerSort: true,
      sorter: "string",
      resizable: true,
      formatter: capitalizeFormatter,
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
        cell.getValue() !== false
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
        return updaterName
          ? `<span class="badge bg-danger-lt ">${updaterName}</span>`
          : `<span class="badge bg-cyan-lt">--</span>`;
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
              class="erp-btn-icon view js-view-transporter"
              data-id="${rowData.id}"
              title="View Record">
              ${viewIcon}
            </span>`;
        }

        if (canEdit) {
          actions += `
            <span 
              class="erp-btn-icon edit js-edit-transporter"
              data-id="${rowData.id}"
              title="Edit Record">
              ${editIcon}
            </span>`;
        }

        if (canDelete) {
          actions += `
            <span 
              class="erp-btn-icon delete js-delete-transporter"
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

/**
 * Bind events inside dynamically loaded modals
 */
function bindModalEvents() {
  $("#transporter_modal").on("shown.bs.modal", function () {
    $('#name').focus();
    
    // Initialize Select2 for State
    $('#state').select2({
      theme: "bootstrap-5",
        dropdownParent: $("#transporter_modal"),
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
    
    // Initialize Mobile Number Validation
    if (typeof initMobileNumberValidation === "function") {
        initMobileNumberValidation('.txtMobile');
    }
  });
}

/**
 * Toggle helper for primary section fields
 */
function togglePrimarySection($sectionEl, shouldShow) {
  $sectionEl.toggle(shouldShow);

  $sectionEl.find("input, select, textarea").each(function () {
    var $el = $(this);

    if (!shouldShow) {
      if ($el.hasClass("select2")) {
        $el.val("").trigger("change");
      } else {
        $el.val("");
      }
    }

    $el.prop("disabled", !shouldShow);
  });
}

// ===========================================================
// FILTER & STORAGE FUNCTIONS
// ===========================================================

/**
 * Apply current filter to the table.
 * Stores selected filters in localStorage for persistence.
 */
function applyFilter() {
  const searchVal = ($("#search").val() || "").trim();
  const transporterId = $("#transporter_id").val();

  currentFilter = {};
  if (searchVal) currentFilter.search = searchVal;
  if (transporterId) currentFilter.transporter_id = transporterId;

  localStorage.setItem("transporter_filter", JSON.stringify(currentFilter));

  // Reload table with new filter applied
  if (table) table.setData();
}

/**
 * Clear all applied filters.
 * Resets filter UI elements and clears saved localStorage filters.
 */
function clearFilter() {
  $("#search").val("");
  $("#transporter_id").val("").trigger("change");

  currentFilter = {};
  localStorage.removeItem("transporter_filter");
  if (table) table.setData();
}

/**
 * Save column widths to localStorage when resized.
 * This ensures consistent column sizing between reloads.
 */
function handleColumnResize(column) {
  const widths = {};
  table.getColumns().forEach((col) => {
    const field = col.getField();
    if (field) widths[field] = col.getWidth();
  });
  localStorage.setItem("transporter_col_widths", JSON.stringify(widths));
}

// ===========================================================
// DOM INITIALIZATION
// ===========================================================
$(function () {
  const $searchEl = $("#search");
  const $applyFilterBtn = $("#filter_apply");
  const $clearFilterBtn = $("#filter_clear");


    // Apply Filter
  $applyFilterBtn.on("click", applyFilter);

  // Clear Filter
  $clearFilterBtn.on("click", clearFilter);
  // -----------------------------------
  // Initialize Select2
  // -----------------------------------
  $("#transporter_id").select2({
    theme: "bootstrap-5",
    placeholder: "Select Transporter",
    allowClear: true,
  });

  // -----------------------------------
  // Load Saved Filters
  // -----------------------------------
  const savedFilter = localStorage.getItem("transporter_filter");
  if (savedFilter) {
    try {
      currentFilter = JSON.parse(savedFilter);
      if (currentFilter.search) {
        $searchEl.val(currentFilter.search);
      }
      if (currentFilter.transporter_id) {
        $("#transporter_id").val(currentFilter.transporter_id).trigger("change");
      }
    } catch (e) {
      console.error("Failed to parse saved filter:", e);
    }
  }

  dynamicFilter("#search, #transporter_id"); 

  // -----------------------------------
  // Load Saved Column Widths
  // -----------------------------------
  const savedWidths = localStorage.getItem("transporter_col_widths");
  if (savedWidths) {
    try {
      savedColWidths = JSON.parse(savedWidths);
    } catch (e) {
      console.error("Failed to parse saved column widths:", e);
    }
  }

  // -----------------------------------
  // Create Tabulator Table
  // -----------------------------------
  table = new Tabulator("#transporter_table", {
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
    
    ajaxURL: transporterListUrl,
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

  // Register table globally for refresh usage
  if (typeof registerTable === "function") {
    registerTable("transporter_table", table);
  }

  // -----------------------------------
  // Register Generic Event Listeners
  // -----------------------------------


  // Apply filter on Enter key
  $searchEl.on("keypress", (e) => {
    if (e.key === "Enter") applyFilter();
  });

  // Save column width changes
  table.on("columnResized", handleColumnResize);

  // Sync item name with print name
  $(document).on("input", "#name", function () {
    const value = $(this).val() || "";
    $("#print_name").val(value);
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
        printReport(route, { format: format, currentFilter });
        break;

      case "excel":
        downloadExcel(route, {currentFilter });
        break;
    }
  });

  // -----------------------------------
  // Action Button Listeners
  // -----------------------------------

  // Get Modal for create
  $(document).on("click", ".js-load-modal", function (event) {
    event.preventDefault();
    $.ajax({
      url: createRouteUrl,
      beforeSend: function () { showLoader("Loading Transporter Form..."); },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        bindModalEvents();
        $("#transporter_modal").modal("show");
      },
      error: function (xhr) {
        showToast("error", 'Oops! Something went wrong. Try again later.');
      },
      complete: function () { hideLoader(); },
    });
  });

  // Edit Transporter
  $(document).on("click", ".js-edit-transporter", function (event) {
    const id = $(this).data("id");
    const url = editRouteUrl.replace(":id", id);
    $.ajax({
      url: url,
      beforeSend: function () {
        showLoader("Loading Transporter Form...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        bindModalEvents();
        setTimeout(function () {
          $("#transporter_form").attr("data-method", "PUT");
        }, 200);
        $("#transporter_modal").modal("show");
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

  // View Transporter
  $(document).on("click", ".js-view-transporter", function (event) {
    const id = $(this).data("id");
    const url = viewRouteUrl.replace(":id", id);
    $.ajax({
      url: url,
      beforeSend: function () { showLoader("Loading Transporter Details..."); },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        bindModalEvents();
        
        // Disable fields for View Mode
        const $modalEl = $("#transporter_modal");
        $modalEl.addClass("view-mode");
        $modalEl.find("input, select, textarea").prop("disabled", true);
        $modalEl.find(".select2").each(function () {
          $(this).prop("disabled", true).trigger("change.select2");
        });

        setTimeout(function () {
          $("#transporter_form").attr("data-method", "PUT");
        }, 200);

        $("#transporter_modal").modal("show");
      },
      error: function (xhr) {
        showToast("error", 'Oops! Something went wrong. Try again later.');
      },
      complete: function () { hideLoader(); },
    });
  });

  // Delete Transporter
  $(document).on("click", ".js-delete-transporter", function (event) {
    const id = $(this).data("id");
    const url = deleteRouteUrl.replace(":id", id);

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
          beforeSend: function () { showLoader("Deleting Transporter..."); },
          success: function (response) {
            showToast("success", response.message || "Transporter deleted successfully!");
            table.replaceData();
          },
          error: function (xhr) {
            showToast("error", 'Oops! Something went wrong. Try again later.');
          },
          complete: function () { hideLoader(); },
        });
      }
    });
  });

});
