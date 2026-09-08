// ===========================================================
// GLOBALS
// ===========================================================
let table;
let currentPermissions = {};
let grandTotalRecords = 0;
let totalFilteredRecords = 0;

const FILTER_KEY = "freight_filter";
const WIDTH_KEY = "freight_col_widths";
const PAGE_SIZE_KEY = "freight_page_size";
const PAGE_NUM_KEY = "freight_page_num";

const filterFields = [
  "start_date",
  "end_date",
  "account_id",
  "item_id",
  "vehicle_id",
  "from_destination_id",
  "to_destination_id",
  "grn_id",
  "lr_number_id",
  "consignor_id",
  "consignee_id",
  "bill_id",
];

let currentFilter = {};

// ===========================================================
// COLUMN WIDTH SAVING
// ===========================================================
function saveColWidths() {
  var widths = {};
  table.getColumns().forEach(function (col) {
    var field = col.getField();
    if (field) widths[field] = col.getWidth();
  });
  // localStorage.setItem(WIDTH_KEY, JSON.stringify(widths));
}

function getSavedWidths() {
  try {
    return JSON.parse(localStorage.getItem(WIDTH_KEY)) || {};
  } catch (e) {
    return {};
  }
}

// ===========================================================
// TABLE COLUMNS
// ===========================================================
function getColumns(saved) {
  return [
    {
      title: "Voucher No.",
      field: "voucher_number",
      width: saved.voucher_number ?? 150,
      headerHozAlign: "center",
      hozAlign: "center",
      headerSort: false,
    },

    {
      title: "Bill No.",
      field: "bill_number",
      width: saved.bill_number ?? 150,
      headerHozAlign: "center",
      hozAlign: "center",
      headerSort: false,
    },

    {
      title: "Bill Date",
      field: "invoice_date",
      headerSort: false,
      headerHozAlign: "center",
      hozAlign: "center",
      width: 100,
    },

    {
      title: "Bill To.",
      field: "account_name",
      headerHozAlign: "left",
      hozAlign: "left",
      headerSort: false,
      width: saved.account_name ?? 350,
      formatter: function (cell) {
        var value = cell.getValue() || "";
        return `<span title="${value}">${value}</span>`;
      },
    },

    {
      title: "GRN No.",
      field: "grn_number",
      width: saved.grn_number ?? 150,
      headerHozAlign: "left",
      hozAlign: "left",
      headerSort: false,
    },

    {
      title: "LR No.",
      field: "lr_number",
      width: saved.lr_number ?? 120,
      headerHozAlign: "left",
      hozAlign: "left",
      headerSort: false,
    },
    {
      title: "Consignor",
      field: "consignor",
      width: saved.consignor ?? 250,
      headerHozAlign: "left",
      hozAlign: "left",
      headerSort: false,
      formatter: function (cell) {
        var value = cell.getValue() || "";
        return `<span title="${value}">${value}</span>`;
      },
    },
    {
      title: "Consignee",
      field: "consignee",
      width: saved.consignee ?? 250,
      headerHozAlign: "left",
      hozAlign: "left",
      headerSort: false,
      formatter: function (cell) {
        var value = cell.getValue() || "";
        return `<span title="${value}">${value}</span>`;
      },
    },

    {
      title: "Vehicle No.",
      field: "vehicle_number",
      width: saved.vehicle_number ?? 200,
      headerHozAlign: "left",
      hozAlign: "left",
      headerSort: false,
    },

    {
      title: "From Destination",
      field: "from_destination",
      width: saved.from_destination ?? 250,
      headerHozAlign: "left",
      hozAlign: "left",
      headerSort: false,
      formatter: function (cell) {
        var value = cell.getValue() || "";
        return `<span title="${value}">${value}</span>`;
      },
    },

    {
      title: "To Destination",
      field: "to_destination",
      width: saved.to_destination ?? 250,
      headerHozAlign: "left",
      hozAlign: "left",
      headerSort: false,
      formatter: function (cell) {
        var value = cell.getValue() || "";
        return `<span title="${value}">${value}</span>`;
      },
    },
    {
      title: "Item",
      field: "item_name",
      width: saved.item_name ?? 250,
      headerHozAlign: "left",
      hozAlign: "left",
      headerSort: false,
      formatter: function (cell) {
        var value = cell.getValue() || "";
        return `<span title="${value}">${value}</span>`;
      },
    },

    {
      title: "Rate",
      field: "rate",
      width: saved.rate ?? 100,
      headerHozAlign: "right",
      hozAlign: "right",
      headerSort: false,
      bottomCalc: function () {
        return `Total : `;
      },
    },
    {
      title: "Freight Amount",
      field: "total_amount",
      width: saved.total_amount ?? 200,
      headerHozAlign: "right",
      hozAlign: "right",
      headerSort: false,

      bottomCalc: function (values, data, calcParams) {
        let sum = 0;

        values.forEach(function (val) {
          if (val) {
            let num = parseFloat(val.toString().replace(/,/g, ""));
            if (!isNaN(num)) {
              sum += num;
            }
          }
        });

        return sum;
      },

      bottomCalcFormatter: function (cell) {
        return `<span class="fw-bold">${formatIndianNumber(cell.getValue(), 2)}</span>`;
      },
    },
    {
      title: "Actions",
      field: "actions",
      width: 130,
      hozAlign: "center",
      vertAlign: "middle",
      headerHozAlign: "center",
      headerSort: false,
      frozen: true,
      formatter: (cell) => {
        const row = cell.getData();
        const canView = currentPermissions.view ?? false;
        const canEdit = currentPermissions.update ?? false;
        const canPrint = currentPermissions.print ?? false;

        // const canDelete = currentPermissions.delete ?? false;

        const viewIcon = icons.view;
        const editIcon = icons.edit;
        const printIcon = icons.print;
        // const deleteIcon = getIcon("delete");

        let actions =
          '<div class="d-flex gap-2 align-items-center justify-content-center h-100 w-100">';

        // if (canView) {
        //   actions += `
        //       <span class="erp-btn-icon view" data-id="${row.id}" title="View GRN">
        //         ${viewIcon}
        //       </span>`;
        // }

        if (canEdit) {
          actions += `
                    <span class="erp-btn-icon edit" data-id="${row.id}" title="Edit Freight">
                        ${editIcon}
                    </span>`;
        }

        if (canPrint) {
          actions += `
                    <span class="erp-btn-icon print" data-id="${row.id}" style="stroke-width:1px !important" title="Print Freight">
                        ${printIcon}
                    </span>`;
        }

        // if (canDelete) {
        //   actions += `
        //       <span class="erp-btn-icon delete" data-id="${row.id}" title="Delete Order">
        //         ${deleteIcon}
        //       </span>`;
        // }

        actions += "</div>";
        return actions;
      },
    },
  ];
}
// ===========================================================
// SELECT2 INIT
// ===========================================================
function bindSelect2() {
  $("#from_destination_id").select2({
    theme: "bootstrap-5",
    placeholder: "Select From Destination...",
    allowClear: true,
    width: "100%",
    ajax: {
      url: masterRoutes.destinations,
      dataType: "json",
      delay: 250,
      data: (params) => ({ q: params.term || "" }),
      processResults: (response) => ({
        results: (response.data || []).map((item) => ({
          id: item.id,
          text: item.name,
        })),
      }),
      cache: true,
    },
    minimumInputLength: 0,
  });

  $("#to_destination_id").select2({
    theme: "bootstrap-5",
    placeholder: "Select To Destination...",
    allowClear: true,
    width: "100%",
    ajax: {
      url: masterRoutes.destinations,
      dataType: "json",
      delay: 250,
      data: (params) => ({ q: params.term || "" }),
      processResults: (response) => ({
        results: (response.data || []).map((item) => ({
          id: item.id,
          text: item.name,
        })),
      }),
      cache: true,
    },
    minimumInputLength: 0,
  });
  $("#item_id").select2({
    theme: "bootstrap-5",
    placeholder: "Select Item...",
    allowClear: true,
    width: "100%",
    ajax: {
      url: masterRoutes.items,
      dataType: "json",
      delay: 250,
      data: (params) => ({ q: params.term || "" }),
      processResults: (response) => ({
        results: (response.data || []).map((item) => ({
          id: item.id,
          text: item.name,
        })),
      }),
      cache: true,
    },
    minimumInputLength: 0,
  });
  var selectIdArray = [
    "#account_id",
    "#vehicle_id",
    "#consignor_id",
    "#consignee_id",
    "#bill_id",
  ];

  $.each(selectIdArray, function (i, element) {
    $(element).select2({
      theme: "bootstrap-5",
      allowClear: true,
      placeholder: "Select ...",
      width: null,
    });
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
}

$(document).ready(function () {
  // ----- Select2 -----
  bindSelect2();

  // ----- Date inputs -----
  new DateInput("#start_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
  new DateInput("#end_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

  // ----- Restore saved filters -----
  // restoreFilters();

  // ----- Tabulator table -----
  var savedWidths = {};

  table = new Tabulator("#freight_table", {
    height: "550px",
    layout: "fitColumns",

    placeholder:
      '<div class="text-center py-5"><h3 class="text-muted">No data found</h3><p class="text-muted">Try adjusting your filters or search criteria</p></div>',

    pagination: true,
    paginationMode: "remote",
    paginationSize: parseInt(localStorage.getItem(PAGE_SIZE_KEY)) || 50,
    paginationInitialPage: parseInt(localStorage.getItem(PAGE_NUM_KEY)) || 1,
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

    ajaxURL: freightListUrl,

    ajaxParams: function () {
      return currentFilter;
    },

    ajaxURLGenerator: function (url, config, params) {
      var page = params.page || 1;
      var size = params.size || 50;
      var qp = new URLSearchParams(
        $.extend({}, currentFilter, { page: page, size: size }),
      );
      return url + "?" + qp.toString();
    },

    ajaxResponse: function (url, params, response) {
      if (response.permissions) currentPermissions = response.permissions;
      totalFilteredRecords = response.total || 0;
      grandTotalRecords = response.grand_total || 0;
      return response;
    },

    paginationCounter: function (
      pageSize,
      currentRow,
      _currentPage,
      _totalRows,
      _totalPages,
    ) {
      const total = totalFilteredRecords;
      if (!total) return "No entries found";
      const start = currentRow;
      const end = Math.min(currentRow + pageSize - 1, total);
      let text = `Showing ${start} to ${end} of ${total} entries`;
      if (grandTotalRecords > 0 && grandTotalRecords !== total) {
        text += ` (filtered from ${grandTotalRecords} total entries)`;
      }
      return text;
    },

    columns: getColumns(savedWidths),
  });

  table.on("columnResized", saveColWidths);

  table.on("pageLoaded", function (pageNo) {
    localStorage.setItem(PAGE_NUM_KEY, pageNo);
    localStorage.setItem(PAGE_SIZE_KEY, table.getPageSize());
  });

  // ----- Apply Filters -----
  $("#filter_apply").on("click", function (e) {
    e.preventDefault();
    currentFilter = {};
    filterFields.forEach(function (field) {
      var val = $("#" + field).val();
      if (val) {
        currentFilter[field] = val;
      }
    });
    localStorage.setItem(FILTER_KEY, JSON.stringify(currentFilter));
    table.setData();
  });

  // ----- Clear Filters -----
  $("#filter_clear").on("click", function (e) {
    e.preventDefault();
    currentFilter = {};
    filterFields.forEach(function (field) {
      var el = $("#" + field);
      el.val(null);
      if (el.hasClass("select2-hidden-accessible")) {
        el.trigger("change");
      }
    });
    localStorage.removeItem(FILTER_KEY);
    table.setData();
  });

  // ----- Edit Action -----
  $(document).on("click", ".edit", function (e) {
    e.preventDefault();
    const id = $(this).data("id");
    if (id) {
      window.location.href = freightEditUrl.replace(":id", id);
    }
  });

  // ----- Print Action -----
  $(document).on("click", ".print", function (e) {
    e.preventDefault();
    const id = $(this).data("id");
    if (id) {
      printReport(freightPrintUrl.replace(":id", id));
    }
  });

  // Event delegation for dropdown report actions
  $(document).on("click", ".dropdown-item", function (e) {
    const $action = $(this);
    const type = $action.data("type");
    const route = $action.data("route");
    const format = $action.data("format");

    if (!type) return;

    // Normalize currentFilter (ensure it's not null/empty for the report helpers if needed)
    const safeFilter =
      currentFilter && Object.keys(currentFilter).length > 0
        ? currentFilter
        : {};

    switch (type) {
      case "print":
        if (typeof printReport === "function") {
          printReport(route, { format, currentFilter: safeFilter });
        }
        break;

      case "excel":
        if (typeof downloadExcel === "function") {
          downloadExcel(route, { currentFilter: safeFilter });
        }
        break;
    }
  });

});
