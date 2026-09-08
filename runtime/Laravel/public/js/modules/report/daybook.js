// ============================================================
// DAYBOOK REPORT
// ============================================================

let table = null;
let currentFilter = { voucher_type_id: "all" };

const FILTER_KEY = "daybook_filter";
const WIDTH_KEY = "daybook_col_widths";

const filterFields = [
  "start_date",
  "end_date",
  "account_id",
  "voucher_type_id",
  "narration",
];

// ============================================================
// UTILS
// ============================================================
function getFieldValue(el) {
  if (!el) return "";
  const $el = typeof el === "string" ? $("#" + el) : $(el);
  if (!$el.length) return "";
  if ($el.hasClass("select2-hidden-accessible")) return $el.val() || "";
  return ($el.val() || "").trim();
}

function setFieldValue(el, value) {
  if (!el) return;
  const $el = typeof el === "string" ? $("#" + el) : $(el);
  if (!$el.length) return;
  if ($el.hasClass("select2-hidden-accessible")) {
    $el.val(value).trigger("change");
  } else {
    $el.val(value);
  }
}

function normalizeDate(dateStr) {
  if (!dateStr || dateStr === "-") return "";
  if (/^\d{2}-\d{2}-\d{4}$/.test(dateStr)) return dateStr;
  if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr;
  if (typeof formatDateToYMD === "function") {
    return formatDateToYMD(dateStr);
  }
  return dateStr;
}

function formatBalance(amount) {
  amount = parseFloat(amount || 0);
  if (amount === 0) return "0.00";
  const label = amount > 0 ? "Dr" : "Cr";
  return formatIndianNumber(Math.abs(amount)) + " " + label;
}

// ============================================================
// FILTER
// ============================================================
function validateDateRange() {
  const start = getFieldValue("start_date");
  const end = getFieldValue("end_date");

  if (!isValidDateDMY(start) || !isValidDateDMY(end)) {
    showToast(
      "error",
      "Start Date and End Date are required in DD-MM-YYYY format",
    );
    return false;
  }

  const [sd, sm, sy] = start.split("-").map(Number);
  const [ed, em, ey] = end.split("-").map(Number);

  if (new Date(sy, sm - 1, sd) > new Date(ey, em - 1, ed)) {
    showToast("error", "End Date must be greater than Start Date");
    return false;
  }

  return true;
}

function buildCurrentFilter() {
  const filter = {};
  $.each(filterFields, function (i, id) {
    var el = $("#" + id).get(0);
    var value = getFieldValue(el);
    // Include account_id and narration even if empty to properly handle filters
    if (id === "account_id" || id === "narration") {
      filter[id] = value;
    } else if (value) {
      filter[id] = value;
    }
  });

  // process date fields
  $.each(["start_date", "end_date"], function (i, k) {
    if (filter[k]) {
      var formatted = normalizeDate(filter[k]);
      if (formatted) {
        filter[k] = formatted;
      } else {
        delete filter[k];
      }
    }
  });

  return filter;
}

function applyFilter() {
  if (!validateDateRange()) return;

  currentFilter = buildCurrentFilter();

  localStorage.setItem(FILTER_KEY, JSON.stringify(currentFilter));

  if (table) table.setData(daybookListUrl);
}

function clearFilter() {
  $.each(filterFields, function (i, id) {
    var el = $("#" + id).get(0);
   if (id === "narration") {
      setFieldValue(el, "0");
    } else if (id === "voucher_type_id") {
      setFieldValue(el, "all");
    } else {
      setFieldValue(el, "");
    }
  });

  currentFilter = { voucher_type_id: "all" };
  localStorage.removeItem(FILTER_KEY);
  localStorage.removeItem(WIDTH_KEY);

  // Reset balance displays
  $("#opening_balance").text("0.00");
  $("#closing_balance").text("0.00");

  if (table) table.clearData();
}

/** Restore filters on page load */
function restoreFilters() {
  var saved = localStorage.getItem(FILTER_KEY);
  if (saved) {
    currentFilter = JSON.parse(saved);
    $.each(currentFilter, function (id, value) {
      var el = $("#" + id).get(0);
      setFieldValue(el, value);
    });
  } else {
    // Leave dates blank on first page load - user must enter them
    setFieldValue("start_date", currentDate()); // Add current date
    setFieldValue("end_date", currentDate()); // Add current date
    setFieldValue("narration", "0");
    setFieldValue("voucher_type_id", "all");
  }
}

// ============================================================
// COLUMN WIDTHS
// ============================================================
function saveColWidths() {
  const widths = {};
  table.getColumns().forEach((col) => {
    const field = col.getField();
    if (field) widths[field] = col.getWidth();
  });
  localStorage.setItem(WIDTH_KEY, JSON.stringify(widths));
}

function getSavedWidths() {
  try {
    return JSON.parse(localStorage.getItem(WIDTH_KEY)) || {};
  } catch {
    return {};
  }
}

// ============================================================
// COLUMNS
// ============================================================
function getColumns(saved) {
  return [
    {
      title: "Date",
      field: "voucher_date",
      width: saved.voucher_date ?? 120,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      formatter: (cell) => {
        const d = cell.getValue();
        return d ? formatDateToDMY(d) : "";
      },
    },
    {
      title: "Voucher Type",
      field: "voucher_type",
      width: saved.voucher_type ?? 140,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      formatter: (cell) => {
        const v = cell.getValue();
        return v === "Narration" ? "" : v || "";
      },
    },
    {
      title: "Voucher No.",
      field: "voucher_serial",
      width: saved.voucher_serial ?? 180,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
    },
    {
      title: "Particulars",
      field: "account_name",
      width: saved.account_name ?? 450,
      hozAlign: "left",
      headerSort: false,
      formatter: (cell) => {
        const data = cell.getData();
        if (data.voucher_type === "Narration") {
          return `<span style="color:#653818;font-style:italic">Narration: ${data.narration
 || ""}</span>`;
        }
        return cell.getValue() || "";
      },
    },
    {
      title: "Debit",
      field: "debit",
      width: saved.debit ?? 160,
      hozAlign: "right",
      headerHozAlign: "right",
      headerSort: false,
      formatter: (cell) => {
        const v = parseFloat(cell.getValue());
        return !v || v === 0 ? "" : formatIndianNumber(v);
      },
      bottomCalc: (values) =>
        values.reduce((sum, v) => sum + (parseFloat(v) || 0), 0),
      bottomCalcFormatter: (cell) => {
        const v = cell.getValue() || 0;
        return `<div style="text-align:right;font-weight:600">${formatIndianNumber(v.toFixed(2))}</div>`;
      },
    },
    {
      title: "Credit",
      field: "credit",
      width: saved.credit ?? 160,
      hozAlign: "right",
      headerHozAlign: "right",
      headerSort: false,
      formatter: (cell) => {
        const v = parseFloat(cell.getValue());
        return !v || v === 0 ? "" : formatIndianNumber(v);
      },
      bottomCalc: (values) =>
        values.reduce((sum, v) => sum + (parseFloat(v) || 0), 0),
      bottomCalcFormatter: (cell) => {
        const v = cell.getValue() || 0;
        return `<div style="text-align:right;font-weight:600">${formatIndianNumber(v.toFixed(2))}</div>`;
      },
    },
  ];
}

// ============================================================
// TABLE
// ============================================================
function initTable(savedWidths) {
  table = new Tabulator("#daybook_table", {
    ajaxURL: daybookListUrl,
    ajaxParams: () => {
      const params = { ...currentFilter };
      // Format dates for API
      if (params.start_date)
        params.start_date = formatDateToYMD(params.start_date);
      if (params.end_date) params.end_date = formatDateToYMD(params.end_date);
      // Remove empty account_id if not selected
      if (!params.account_id || params.account_id === "")
        delete params.account_id;
      return params;
    },
    ajaxConfig: "GET",
    ajaxResponse: (_url, _params, response) => {
      $("#opening_balance").text(formatBalance(response.opening_balance));
      $("#closing_balance").text(formatBalance(response.closing_balance));
      return response.data || [];
    },

    height: "calc(550px)",
    layout: "fitColumns",
    virtualDom: true,

    placeholder: `<div class="text-center py-5">
            <i class="fa-solid fa-file-invoice fa-3x text-muted mb-3 d-block"></i>
            <h4 class="text-muted">No records found</h4>
            <p class="text-muted small">Enter dates and click Apply to load daybook data.</p>
        </div>`,

    pagination: false,
    columns: getColumns(savedWidths),
  });

  table.on("columnResized", saveColWidths);

  table.on("dataLoading", () => $("#scrollLoader").show());
  table.on("dataLoaded", () => $("#scrollLoader").hide());
  table.on("dataLoadError", () => {
    $("#scrollLoader").hide();
    showToast("error", "Failed to load daybook data. Please try again.");
  });
}

// ============================================================
// SELECT2
// ============================================================
function bindSelect2() {
  const selectIdArray = ["#account_id", "#voucher_type_id", "#narration"];
  selectIdArray.forEach((element) => {
    $(element).select2({
      theme: "bootstrap-5",
      allowClear: true,
      placeholder:
        "Select " +
        element
          .replace("#", "")
          .replace(".", "")
          .replace("_id", "")
          .replace("_type", " ") +
        " ...",
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

// ============================================================
// BOOTSTRAP
// ============================================================
$(function () {
  new DateInput("#start_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
  new DateInput("#end_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

  bindSelect2();
  restoreFilters();

  // Start with empty filter - don't fetch data on page load
  currentFilter = buildCurrentFilter(); // Ensure currentFilter has the restored/default values

  const savedWidths = getSavedWidths();
  initTable(savedWidths);

    // ----- Filter buttons -----
    $("#filter_apply").on("click", function(e) {
        e.preventDefault();
        applyFilter();
    });

    $("#filter_clear").on("click", function (e) {
        e.preventDefault();
        clearFilter();
    });

  // Export / Print
  $(document).on("click", ".dropdown-item[data-type]", function () {
    const type = $(this).data("type");
    const route = $(this).data("route");
    const format = $(this).data("format");

    // Pass copy of currentFilter with YMD formatted dates to print/export functions
    const sendFilter = { ...currentFilter };
    if (sendFilter.start_date)
      sendFilter.start_date = formatDateToYMD(sendFilter.start_date);
    if (sendFilter.end_date)
      sendFilter.end_date = formatDateToYMD(sendFilter.end_date);

    const safeFilter = Object.keys(sendFilter).length ? sendFilter : {};

    if (type === "print" && typeof printReport === "function")
      printReport(route, { format, currentFilter: safeFilter });
    if (type === "excel" && typeof downloadExcel === "function")
      downloadExcel(route, { currentFilter: safeFilter });
  });
});
