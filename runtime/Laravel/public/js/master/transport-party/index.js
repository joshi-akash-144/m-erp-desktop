let table;
let currentFilter = {};
let currentPermissions = {};

function getTableColumns() {
  return [
    {
      title: "#SR",
      field: "no",
      width: 80,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      frozen: true,
    },
    {
      title: "Name",
      field: "name",
      width: 200,
      headerSort: false,
      sorter: "string",
    },
    {
      title: "State",
      field: "state.name",
      width: 150,
      headerSort: false,
      sorter: "string",
    },
    {
      title: "City",
      field: "city",
      width: 150,
      headerSort: false,
      sorter: "string",
    },
    {
      title: "Mobile Number",
      field: "mobile_number",
      width: 150,
      headerSort: false,
      sorter: "string",
    },
    {
      title: "GST Number",
      field: "gst_number",
      width: 150,
      headerSort: false,
      sorter: "string",
    },
    {
      title: "Email",
      field: "email",
      width: 200,
      headerSort: false,
      sorter: "string",
    },
    // =======================
    // Created By
    // =======================
    {
      title: "Created By",
      field: "created_by",
      width: 150,
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
      width: 150,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      resizable: true,
      formatter: (cell) => {
        const updaterName = cell.getRow().getData().updater?.name || null;
        return updaterName
          ? `<span class="badge bg-danger-lt ">${updaterName}</span>`
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

        let actions = '<div class="d-flex gap-2 justify-content-center">';

        if (canView) {
          actions += `
            <span class="erp-btn-icon view js-view-item" data-id="${rowData.id}" title="View Record">
              ${icons.view}
            </span>`;
        }

        if (canEdit) {
          actions += `
            <span class="erp-btn-icon edit js-edit-item" data-id="${rowData.id}" title="Edit Record">
              ${icons.edit}
            </span>`;
        }

        if (canDelete) {
          actions += `
            <span class="erp-btn-icon delete" data-id="${rowData.id}" title="Delete Record">
              ${icons.delete}
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
  $("#transport_parties_modal").on("shown.bs.modal", function () {
    $('#name').focus();
  });

  // Remove focus right before the modal hides to prevent aria-hidden console warnings
  $("#transport_parties_modal").on('hide.bs.modal', function () {
    if (document.activeElement) {
        document.activeElement.blur();
    }
  });
}

function applyFilter() {  
  const searchVal = document.getElementById("search")?.value.trim() || "";
  currentFilter = {};
  if (searchVal) currentFilter.search = searchVal;
  if (table) table.setData();
  // Save filter settings locally
  localStorage.setItem("transport_party_filter", JSON.stringify(currentFilter));
}

function clearFilter() {
  const searchEl = document.getElementById("search");
  if (searchEl) searchEl.value = "";
  currentFilter = {};
  if (table) table.setData();
  localStorage.removeItem("transport_party_filter");
}

$(function () {
  const $searchEl = $("#search");
  const $applyFilterBtn = $("#filter_apply");
  const $clearFilterBtn = $("#filter_clear");

  table = new Tabulator("#transport_parties_table", {
    height: "calc(100vh - 280px)",
    layout: "fitColumns",
    pagination: true,
    paginationMode: "remote",
    paginationSize: 50,
    paginationSizeSelector: [10, 20, 50, 100, 300, true],
    ajaxURL: transportPartyListUrl,
    paginationDataSent: {
      page: "page",
      size: "size",
    },
    paginationDataReceived: {
      last_page: "last_page",
      data: "data",
      total: "total",
    },
    ajaxParams: () => currentFilter,
    ajaxURLGenerator: (url, config, params) => {
      const page = params.page || 1;
      const size = params.size || 50;
      const queryParams = new URLSearchParams({ ...currentFilter, page, size });
      return `${url}?${queryParams.toString()}`;
    },
    ajaxResponse: function(url, params, response) {
      if (response.permissions) currentPermissions = response.permissions;
      return response;
    },
    columns: getTableColumns(),
  });

  if (typeof registerTable === "function") {
    registerTable("transport_parties_table", table);
  }

  let debounceTimeout;
  $searchEl.on("input", function () {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(applyFilter, 300);
  });
  $applyFilterBtn.on("click", applyFilter);
  $clearFilterBtn.on("click", clearFilter);

  const savedFilter = localStorage.getItem("transport_party_filter");
  if (savedFilter) {
    currentFilter = JSON.parse(savedFilter);    
    if (currentFilter.search && $searchEl) {
      $searchEl.val(currentFilter.search);
    }
  }


  // Select2 enter-key navigation
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

  $(document).on("click", ".dropdown-item", function (e) {
    const action = $(this).closest(".dropdown-item");
    const type = action.data("type");
    const route = action.data("route");
    const format = action.data("format");

    if (!type) return;
    
    e.preventDefault();

    switch (type) {
      case "print":
        printReport(route, { format: format, currentFilter });
        break;

      case "excel":
        downloadExcel(route, { currentFilter });
        break;
    }
  });

  $(document).on("click", ".js-load-modal", function (event) {
    event.preventDefault();
    $.ajax({
      url: transportPartyCreateUrl,
      beforeSend: () => showLoader("Loading Form..."),
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        bindModalEvents();
        $("#transport_parties_modal").modal("show");
      },
      error: () => showToast("error", 'Oops! Something went wrong.'),
      complete: () => hideLoader(),
    });
  });

  $(document).on("click", ".js-edit-item", function (event) {
    const id = $(this).data("id");
    const url = transportPartyEditUrl.replace(":id", id);
    $.ajax({
      url: url,
      beforeSend: () => showLoader("Loading Form..."),
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        bindModalEvents();
        $("#transport_parties_modal").modal("show");
      },
      error: () => showToast("error", 'Oops! Something went wrong.'),
      complete: () => hideLoader(),
    });
  });

  $(document).on("click", ".js-view-item", function (event) {
    const id = $(this).data("id");
    const url = transportPartyViewUrl.replace(":id", id);
    $.ajax({
      url: url,
      beforeSend: () => showLoader("Loading Details..."),
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        bindModalEvents();
        $("#transport_parties_modal").modal("show");
      },
      error: () => showToast("error", 'Oops! Something went wrong.'),
      complete: () => hideLoader(),
    });
  });

  $(document).on("click", ".delete", function (event) {
    const id = $(this).data("id");
    const url = transportPartyDeleteUrl.replace(":id", id);

    Swal.fire({
      title: "Are you sure?",
      text: "You won't be able to revert this!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      confirmButtonText: "Yes, delete it!",
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: url,
          type: "DELETE",
          beforeSend: () => showLoader("Deleting..."),
          success: function (response) {
            showToast("success", response.message || "Deleted successfully!");
            table.replaceData();
          },
          error: () => showToast("error", 'Oops! Something went wrong.'),
          complete: () => hideLoader(),
        });
      }
    });
  });
});
