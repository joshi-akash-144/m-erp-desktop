let table;
let currentFilter = { is_used: "0" }; // default: show not-used records
let totalFilteredRecords = 0;
// let currentPermissions = {};

const FILTER_KEY = "day_to_day_filter";

const filterFields = [
  "import_date",
  "start_date",
  "end_date",
  "zone_id",
  "vehicle_id",
  "product_id",
  "is_used",
];

$(document).ready(function () {
  bindSelect2();

  new DateInput("#import_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
  new DateInput("#start_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
  new DateInput("#end_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

  restoreFilters();
  // ── Tabulator ──────────────────────────────────────────────────────────
  table = new Tabulator("#day_to_day_register_table", {
    height: "600px",
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
                <p class="text-muted">Try adjusting your filters</p>
            </div>`,

    pagination: true,
    paginationMode: "remote",
    paginationSize: 50,
    paginationSizeSelector: [10, 20, 50, 100, 300],
    paginationDataSent: { page: "page", size: "size" },
    paginationDataReceived: {
      last_page: "last_page",
      data: "data",
      total: "total",
    },

    ajaxURL: dayToDayRegisterListUrl,
    ajaxParams: () => currentFilter,
    ajaxURLGenerator(url, _config, params) {
      const qs = new URLSearchParams({
        ...currentFilter,
        page: params.page ?? 1,
        size: params.size ?? 50,
      });
      return `${url}?${qs}`;
    },
    ajaxResponse(_url, _params, response) {
      totalFilteredRecords = response.total || 0;
      return response;
    },

    paginationCounter(pageSize, currentRow, _page, _totalRows, _totalPages) {
      if (!totalFilteredRecords) return "No entries found";
      const end = Math.min(currentRow + pageSize - 1, totalFilteredRecords);
      return `Showing ${currentRow} to ${end} of ${totalFilteredRecords} entries`;
    },

    columns: [
      {
        title: "#",
        formatter: "rownum",
        hozAlign: "center",
        headerHozAlign: "center",
        vertAlign: "middle",
        headerSort: false,
        width: 55,
      },
      {
        title: "Import Date",
        field: "import_date",
        hozAlign: "center",
        headerHozAlign: "center",
        vertAlign: "middle",
        headerSort: false,
        width: 120,
      },
      {
        title: "Billing Date",
        field: "billing_date",
        hozAlign: "center",
        headerHozAlign: "center",
        vertAlign: "middle",
        headerSort: false,
        width: 120,
      },
      {
        title: "Customer P.O. No",
        field: "customer_po_no",
        vertAlign: "middle",
        headerSort: false,
        width: 150,
      },
      {
        title: "Sold to Party",
        field: "sold_to_party",
        vertAlign: "middle",
        headerSort: false,
        width: 130,
      },
      {
        title: "Destination",
        field: "destination",
        vertAlign: "middle",
        headerSort: false,
        minWidth: 160,
      },
      {
        title: "Product",
        field: "product",
        vertAlign: "middle",
        headerSort: false,
        minWidth: 140,
      },
      {
        title: "Qty",
        field: "quantity",
        hozAlign: "right",
        headerHozAlign: "right",
        vertAlign: "middle",
        headerSort: false,
        width: 90,
      },
      {
        title: "Vehicle No",
        field: "vehicle_no",
        hozAlign: "center",
        headerHozAlign: "center",
        vertAlign: "middle",
        headerSort: false,
        width: 130,
      },
      {
        title: "Zone",
        field: "zone",
        vertAlign: "middle",
        headerSort: false,
        minWidth: 120,
      },
      {
        title: "Status",
        field: "is_used",
        hozAlign: "center",
        headerHozAlign: "center",
        vertAlign: "middle",
        headerSort: false,
        width: 110,
        formatter(cell) {
          return cell.getValue()
            ? '<span class="badge bg-success-lt text-success fw-semibold">Used</span>'
            : '<span class="badge bg-warning-lt text-warning fw-semibold">Not Used</span>';
        },
      },
      {
        title: "Created By",
        field: "created_by",
        hozAlign: "center",
        headerHozAlign: "center",
        headerSort: false,
        resizable: true,
        width: 100,
        formatter: (cell) => {
          const creatorName = cell.getRow().getData().creator?.name || null;
          return creatorName
            ? `<span class="badge bg-danger-lt ">${creatorName}</span>`
            : `<span class="badge bg-cyan-lt">System</span>`;
        },
      },
      {
        title: "Action",
        field: "id",
        hozAlign: "center",
        headerHozAlign: "center",
        vertAlign: "middle",
        headerSort: false,
        width: 80,
        formatter(cell) {
          const row = cell.getRow().getData();
          // const canDelete = currentPermissions.delete ?? true;

          const deleteIcon = icons.delete;

          let actions =
            '<div class="d-flex gap-2 align-items-center justify-content-center h-100 w-100">';
          // if (canDelete) {
          actions += `
                        <button class="erp-btn-icon delete delete-row-btn" data-id="${row.id}" title="Delete Batch">
                            ${deleteIcon}
                        </button>`;
          // }
          actions += "</div>";
          return actions;
        },
      },
    ],
  });

  // ── Filter apply ───────────────────────────────────────────────────────
  $("#filter_apply").on("click", function (e) {
    e.preventDefault();
    applyFilter();
  });

  $("#filter_clear").on("click", function () {
    $.each(filterFields, function (i, id) {
      const defaultValue = id === "is_used" ? "0" : "";
      setFieldValue(id, defaultValue);
    });

    // $('#import_date').val('');
    // $('#start_date').val('');
    // $('#end_date').val('');
    // $('#zone_id').val('').trigger('change');
    // $('#vehicle_id').val('').trigger('change');
    // $('#product_id').val('').trigger('change');
    // $('#is_used').val('0');          // reset to "Not Used"
    currentFilter = { is_used: "0" };
    localStorage.removeItem(FILTER_KEY);
    table.setPage(1);
  });

  function applyFilter() {
    currentFilter = {
      import_date: $("#import_date").val() || "",
      start_date: $("#start_date").val() || "",
      end_date: $("#end_date").val() || "",
      zone_id: $("#zone_id").val() || "",
      vehicle_id: $("#vehicle_id").val() || "",
      product_id: $("#product_id").val() || "",
      is_used: $("#is_used").val(), // '' = all, '0' = not used, '1' = used
    };

    $.each(filterFields, function (i, id) {
      var value = getFieldValue(id);
      if (value) currentFilter[id] = value;
    });

    // Strip keys that are empty string (but keep is_used even if '')
    Object.keys(currentFilter).forEach((k) => {
      if (k !== "is_used" && !currentFilter[k]) delete currentFilter[k];
    });

    if (currentFilter.is_used === "") delete currentFilter.is_used;
    localStorage.setItem(FILTER_KEY, JSON.stringify(currentFilter));
    table.setPage(1);
  }

  // ── Per-row delete ─────────────────────────────────────────────────────
  $(document).on("click", ".delete-row-btn", function () {
    const id = $(this).data("id");
    const url = dayToDayRegisterDestroyUrl.replace(":id", id);

    Swal.fire({
      title: "Delete Record?",
      text: "This action cannot be undone.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Yes, Delete",
      cancelButtonText: "Cancel",
    }).then((result) => {
      if (!result.isConfirmed) return;

      $.ajax({
        url,
        method: "DELETE",
        headers: {
          "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        success(res) {
          showToast("success", res.message ?? "Deleted.");
          table.replaceData();
        },
        error(xhr) {
          showToast("error", xhr.responseJSON?.message ?? "Delete failed.");
        },
      });
    });
  });

  // ── Bulk delete (all matching current filter) ──────────────────────────
  $("#bulk_delete_btn").on("click", function () {
    Swal.fire({
      title: "Delete All Filtered Records?",
      text: "All records matching the current filter will be permanently deleted.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Yes, Delete All",
      cancelButtonText: "Cancel",
    }).then((result) => {
      if (!result.isConfirmed) return;

      $.ajax({
        url: dayToDayRegisterBulkDeleteUrl,
        method: "DELETE",
        data: currentFilter,
        headers: {
          "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        success(res) {
          showToast("success", res.message ?? "Records deleted.");
          table.replaceData();
        },
        error(xhr) {
          showToast(
            "error",
            xhr.responseJSON?.message ?? "Bulk delete failed.",
          );
        },
      });
    });
  });

  function bindSelect2() {
    ["#zone_id", "#vehicle_id", "#product_id", "#is_used"].forEach(
      function (sel) {
        $(sel).select2({ theme: "bootstrap-5", allowClear: true });
      },
    );
  }
});

// ===========================================================
// UTILITY FUNCTIONS
// ===========================================================

/** Set field value safely (Select2 / normal input) */
function setFieldValue(id, value) {
  const $el = $(`#${id}`);
  if (!$el.length) return;

  if ($el.hasClass("select2-hidden-accessible")) {
    $el.val(value).trigger("change");
  } else {
    $el.val(value);
  }
}

/** Read field value safely */
function getFieldValue(id) {
  const $el = $(`#${id}`);
  if (!$el.length) return "";

  if ($el.hasClass("select2-hidden-accessible")) {
    return $el.val() || "";
  }
  return $el.val()?.trim() || "";
}

/** Restore filters on page load */
function restoreFilters() {
  var saved = localStorage.getItem(FILTER_KEY);
  if (!saved) return;

  currentFilter = JSON.parse(saved);

  $.each(currentFilter, function (id, value) {
    setFieldValue(id, value);
  });
}
