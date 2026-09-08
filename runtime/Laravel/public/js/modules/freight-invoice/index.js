// ===========================================================
// GLOBALS
// ===========================================================
let table;
let currentPermissions = {};
let grandTotalRecords = 0;
let totalFilteredRecords = 0;

const FILTER_KEY = "freight_invoice_filter";
const WIDTH_KEY = "freight_invoice_col_widths";
const PAGE_SIZE_KEY = "freight_invoice_page_size";
const PAGE_NUM_KEY = "freight_invoice_page_num";

const filterFields = [
  "start_date",
  "end_date",
  "account_id",
  "invoice_serial",
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
      title: "Bill No",
      field: "invoice_serial",
      width: saved.invoice_serial ?? 150,
      headerHozAlign: "center",
      hozAlign: "center",
      headerSort: false,
    },

    {
      title: "Bill Date",
      field: "invoice_date",
      width: saved.invoice_date ?? 150,
      headerHozAlign: "center",
      hozAlign: "center",
      headerSort: false,
    },

    {
      title: "Customer",
      field: "account_name",
      headerSort: false,
      headerHozAlign: "left",
      hozAlign: "left",
      width: saved.account_name ?? 350,
      formatter: function (cell) {
        var value = cell.getValue() || "";
        var row = cell.getData();
        if (row.is_total) return `<div class="text-center fw-bold w-100">${value}</div>`;
        return `<span title="${value}">${value}</span>`;
      },
    },

    {
      title: "Item",
      field: "item_name",
      width: saved.item_name ?? 350,
      headerHozAlign: "left",
      hozAlign: "left",
      headerSort: false,
      formatter: function (cell) {
        var value = cell.getValue() || "";
        return `<span title="${value}">${value}</span>`;
      },
    },
    {
      title: "Zone",
      field: "zone",
      width: saved.zone ?? 200,
      headerHozAlign: "left",
      hozAlign: "left",
      headerSort: false,
      bottomCalc: function () {
        return `Total : `;
      },
    },
    {
      title: "Qty",
      field: "quantity",
      width: saved.quantity ?? 150,
      headerHozAlign: "right",
      hozAlign: "right",
      headerSort: false,

      bottomCalc: function (values, data, calcParams) {
        let sum = 0;

        data.forEach(function (row, index) {
          if (!row.is_total && values[index]) {
            let num = parseFloat(values[index].toString().replace(/,/g, ""));
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
      title: "Amount",
      field: "total_amount",
      width: saved.total_amount ?? 200,
      headerHozAlign: "right",
      hozAlign: "right",
      headerSort: false,

      bottomCalc: function (values, data, calcParams) {
        let sum = 0;

        data.forEach(function (row, index) {
          if (!row.is_total && values[index]) {
            let num = parseFloat(values[index].toString().replace(/,/g, ""));
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
        if (row.is_total || !row.invoice_serial) return ''; // Only show on first row

        const canView = currentPermissions.view ?? false;
        const canEdit = currentPermissions.update ?? false;
        const canPrint = currentPermissions.print ?? false;
        const canPrintZone = currentPermissions.printZone ?? false;
        const canExportZone = currentPermissions.exportZone ?? false;
      
        const editIcon = icons.edit;
        const printIcon = icons.print;
        const exportIcon = icons.export;
        const pdfIcon = icons.pdf;

        let actions =
          '<div class="d-flex gap-2 align-items-center justify-content-center h-100 w-100">';

        // if (canEdit) {
        //   actions += `
        //     <span class="erp-btn-icon edit" data-id="${row.id}" title="Edit Freight">
        //         ${editIcon}
        //     </span>`;
        // }

        if (canPrint) {
          actions += `
            <span class="erp-btn-icon print" data-id="${row.id}" style="stroke-width:1px !important" title="Print Freight">
                ${printIcon}
            </span>`;
        }
        if (canPrintZone) {
          actions += `
            <span class="erp-btn-icon print-zonewise ms-1" data-id="${row.id}" style="stroke-width:1px !important; color:#0d6efd;" title="Print Zone-wise">
            ${pdfIcon}
            </span>`;
        }
        if (canExportZone) {
          actions += `
            <span class="erp-btn-icon export-zonewise ms-1" data-id="${row.id}" style="stroke-width:1px !important; color:#198754;" title="Export Zone-wise">
                ${exportIcon}
            </span>`;
        }

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
  var selectIdArray = [
    "#account_id",
    "#bill_no",
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

  table = new Tabulator("#freight_invoice_table", {
    height: "550px",
    layout: "fitColumns",

    rowFormatter: function(row) {
      var data = row.getData();
      if(data.is_total) {
          row.getElement().style.backgroundColor = "#e0e0e0";
          row.getElement().style.fontWeight = "bold";
      }
    },

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

    ajaxURL: freightInvoiceListUrl,

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
    // ----- Print / Excel dropdown actions -----
    $(document).on("click", ".dropdown-item", function () {
        let $action = $(this);
        let type    = $action.data("type");
        let route   = $action.data("route");
        let format  = $action.data("format");

        if (!type) return;

        var safeFilter = (currentFilter && Object.keys(currentFilter).length > 0)
            ? currentFilter
            : [];

        switch (type) {
            case "print":
                printReport(route, { format: format, currentFilter: currentFilter });
                break;

            case "excel":
                downloadExcel(route, { currentFilter: currentFilter });
                break;
        }
    });

  // ----- Print Zone-wise Action -----
  $(document).on("click", ".print-zonewise", function (e) {
    e.preventDefault();
    const id = $(this).data("id");
    if (id) { 
      printReport(freightZonePrintUrl.replace(":id", id));
    }
  });

  // ----- Export Zone-wise Action -----
  $(document).on("click", ".export-zonewise", function (e) {
    e.preventDefault();
    const id = $(this).data("id");
    if (id) {
        downloadExcel(freightZoneExportUrl.replace(":id", id));
    }
  });
});
