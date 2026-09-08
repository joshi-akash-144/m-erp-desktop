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

// 
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
      width: savedColWidths.name || 200,
      headerSort: true,
      sorter: "string",
      resizable: true,
      formatter: capitalizeFormatter,
    },
    {
      title: "Item Group",
      field: "item_group.name",
      width: savedColWidths["item_group.name"] || 200,
      headerSort: true,
      sorter: "string",
      resizable: true,
      formatter: capitalizeFormatter,
    },
    {
      title: "Unit Name",
      field: "unit.name",
      width: savedColWidths["unit.name"] || 150,
      headerSort: true,
      sorter: "string",
      resizable: true,
      formatter: capitalizeFormatter,
    },
    {
      title: "Op. Qty",
      field: "current_opening_stock.in_qty",
      width: savedColWidths["current_opening_stock.in_qty"] || 110,
      hozAlign: "right",
      headerHozAlign: "right",
      headerSort: false,
      resizable: true,
      formatter: (cell) => {
        const val = cell.getValue();
        return val != null ? parseFloat(val).toFixed(2) : "0.00";
      },
    },
    {
      title: "Op. Value",
      field: "current_opening_stock.amount",
      width: savedColWidths["current_opening_stock.amount"] || 120,
      hozAlign: "right",
      headerHozAlign: "right",
      headerSort: false,
      resizable: true,
      formatter: (cell) => {
        const val = cell.getValue();
        return val != null ? parseFloat(val).toFixed(2) : "0.00";
      },
    },
    {
      title: "Tax Category",
      field: "tax_category.name",
      width: savedColWidths["tax_category.name"] || 200,
      headerSort: true,
      sorter: "string",
      resizable: true,
      formatter: capitalizeFormatter,
    },
    {
      title: "HSN/SAC CODE",
      field: "hsn_sac_code",
      width: savedColWidths.hsn_sac_code || 150,
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

/**
 * Bind events inside dynamically loaded modals
 */
function bindModalEvents() {
  $("#item_modal").on("shown.bs.modal", function () {
    $('#name').focus();

    const select2Array  = ['item_group_id', 'unit_id', 'tax_category_id', 'is_maintain_stock_balance','purchase_type_local_id','purchase_type_interstate_id','sale_type_local_id','sale_type_interstate_id'];

    for (let index = 0; index < select2Array.length; index++) {
      const element = select2Array[index];  
      $(`#${element}`).select2({
        theme: "bootstrap-5",
        dropdownParent: $("#item_modal"),
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

    // Toggle Primary Section logic
    const $isPrimary = $("#is_primary");
    const $primaryGroupDiv = $("#primary_group_div");

    if ($isPrimary.length && $primaryGroupDiv.length) {
      const togglePrimaryGroupDiv = () => {
        const value = $isPrimary.val();
        const shouldShow = value === "0" || value === "";
        $primaryGroupDiv.toggle(shouldShow);
        togglePrimarySection($primaryGroupDiv, shouldShow);
      };

      togglePrimaryGroupDiv();
      $isPrimary.off("change").on("change", togglePrimaryGroupDiv);
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
  const groupVal = $("#group_id").val() || "";
  const searchVal = ($("#search").val() || "").trim();

  currentFilter = {};
  if (groupVal) currentFilter.filter_group_id = groupVal;
  if (searchVal) currentFilter.search = searchVal;

  // Save filter settings locally
  localStorage.setItem("item_filter", JSON.stringify(currentFilter));

  // Reload table with new filter applied
  if (table) table.setData();
}

/**
 * Clear all applied filters.
 * Resets filter UI elements and clears saved localStorage filters.
 */
function clearFilter() {
  $("#group_id").val("").trigger("change");
  $("#search").val("");

  currentFilter = {};
  localStorage.removeItem("item_filter");

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
  localStorage.setItem("item_col_widths", JSON.stringify(widths));
}

// ===========================================================
// DOM INITIALIZATION
// ===========================================================
$(function () {
  // -----------------------------------
  // Initialize Select2
  // -----------------------------------
  $("#group_id").select2({
    placeholder: "Select Item Group",
    allowClear: true,
    theme: "bootstrap-5",
    width: "100%",
  });

  const $groupEl = $("#group_id");
  const $searchEl = $("#search");
  const $applyFilterBtn = $("#filter_apply");
  const $clearFilterBtn = $("#filter_clear");

  // -----------------------------------
  // Load Saved Filters
  // -----------------------------------
  const savedFilter = localStorage.getItem("item_group_filter");
  if (savedFilter) {
    try {
      currentFilter = JSON.parse(savedFilter);
      if (currentFilter.filter_group_id) {
        $groupEl.val(currentFilter.filter_group_id).trigger("change");
      }
      if (currentFilter.search) {
        $searchEl.val(currentFilter.search);
      }
    } catch (e) {
      console.error("Failed to parse saved filter:", e);
    }
  }

  dynamicFilter("#search, #group_id");

  // -----------------------------------
  // Load Saved Column Widths
  // -----------------------------------
  const savedWidths = localStorage.getItem("item_col_widths");
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
  table = new Tabulator("#item_table", {
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

    ajaxURL: itemListUrl,
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
    registerTable("item_table", table);
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
        downloadExcel(route, { currentFilter });
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
      url: createRouteUrl,
      beforeSend: function () {
        showLoader("Loading Item Form...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        bindModalEvents();
        $("#item_modal").modal("show");
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

  // Edit Item
  $(document).on("click", ".js-edit-item", function (event) {
    const itemId = $(this).data("id");
    const url = editRouteUrl.replace(":id", itemId);
    $.ajax({
      url: url,
      beforeSend: function () {
        showLoader("Loading Item Form...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        bindModalEvents();
        setTimeout(function () {
          $("#item_form").attr("data-method", "PUT");
        }, 200);
        $("#item_modal").modal("show");
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

  // View Item
  $(document).on("click", ".js-view-item", function (event) {
    const itemId = $(this).data("id");
    const url = viewRouteUrl.replace(":id", itemId);
    $.ajax({
      url: url,
      beforeSend: function () {
        showLoader("Loading Item Details...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        bindModalEvents();
        
        // Disable fields for View Mode
        const $modalEl = $("#item_modal");
        $modalEl.addClass("view-mode");
        $modalEl.find("input, select, textarea").prop("disabled", true);
        $modalEl.find(".select2").each(function () {
          $(this).prop("disabled", true).trigger("change.select2");
        });

        setTimeout(function () {
          $("#item_form").attr("data-method", "PUT");
        }, 200);

        $("#item_modal").modal("show");
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

  // Delete Item
  $(document).on("click", ".delete", function (event) {
    const itemId = $(this).data("id");
    const url = deleteRouteUrl.replace(":id", itemId);

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
            showLoader("Deleting Item...");
          },
          success: function (response) {
            showToast("success", response.message || "Item deleted successfully!");
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
