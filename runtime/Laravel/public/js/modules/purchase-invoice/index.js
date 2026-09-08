// ===========================================================
let table = null;
let currentFilter = {
  payment_status: "all",
};
let currentPermissions = {};
let savedColWidths = {};
let grandTotalRecords = 0;
let totalFilteredRecords = 0;

const FILTER_KEY = "purchase_invoice_filter";
const WIDTH_KEY = "purchase_invoice_col_widths";
const PAGE_SIZE_KEY = "purchase_invoice_page_size";
const PAGE_NUM_KEY = "purchase_invoice_page_num";

const filterFields = [
  "start_date",
  "end_date",
  "account_id",
  "item_id",
  "payment_status",
  "grn_serial",
  "voucher_serial",
  "sales_invoice_serial",
  "invoice_serial",
  "file_no",
  "reference_number",
];

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

/** Convert DD-MM-YYYY → YYYY-MM-DD */
function normalizeDate(d) {
    if (!d || d === "__-__-____") return "";
    const [day, month, year] = d.split("-");
    return year?.length === 4 ? `${day}-${month}-${year}` : "";
}

function bindSelect2() {
  const selectIdArray = [
    "#account_id",
    "#item_id",
    "#payment_status",
    "#grn_serial",
    "#voucher_serial",
  ];
  selectIdArray.forEach((element) => {
    $(element).select2({
      theme: "bootstrap-5",
      allowClear: true,
      placeholder:
        "Select " +
        element
          .replace("#", "")
          .replace("_", " ")
          .replace(".", " ")
          .replace("_id", "") +
        " ...",
      width: "100%",
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

function applyFilter() {
  currentFilter = {};

  $.each(filterFields, function (i, id) {
    var value = getFieldValue(id);
    if (value) currentFilter[id] = value;
  });

  // process date fields
  $.each(["start_date", "end_date"], function (i, k) {
    if (currentFilter[k]) {
      var formatted = normalizeDate(currentFilter[k]);
      if (formatted) {
        currentFilter[k] = formatted;
      } else {
        delete currentFilter[k];
      }
    }
  });

  // Default order_status to 'open' if not explicitly selected
  if (!currentFilter["payment_status"]) {
    currentFilter["payment_status"] = "unpaid";
    // Also sync the dropdown UI to reflect the default
    var $statusDropdown = $("#payment_status");
    if ($statusDropdown.data("select2")) {
      $statusDropdown.val("unpaid").trigger("change.select2");
    } else {
      $statusDropdown.val("unpaid");
    }
  }

  localStorage.setItem(FILTER_KEY, JSON.stringify(currentFilter));

  if (table) table.setData();
}

function clearFilter() {
  $.each(filterFields, function (i, id) {
    const defaultValue = id === "payment_status" ? "all" : "";
    setFieldValue(id, defaultValue);
  });

  currentFilter = { payment_status: "all" };
  localStorage.removeItem(FILTER_KEY);

  if (table) table.setData();
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
// ===========================================================
// COLUMN WIDTH SAVING
// ===========================================================
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

function renderDetailsList(
  details,
  key,
  isNumeric = false,
  showTotal = false,
  decimal = 2,
) {
  if (!Array.isArray(details) || details.length === 0) {
    return `<span class="text-muted">--</span>`;
  }

  const showBullet = details.length > 1; // show • only if multiple items
  let html = "";

  details.forEach((item) => {
    // ✅ Support nested keys like "condition.name"
    let val = key
      .split(".")
      .reduce((obj, k) => (obj ? obj[k] : undefined), item);

    // ✅ Format number if numeric
    if (isNumeric && val != null && val !== "") {
      const num = parseFloat(val);
      val = isNaN(num)
        ? 0
        : typeof formatIndianNumber === "function"
          ? formatIndianNumber(num, decimal)
          : num.toFixed(decimal);
    }

    html += `
      <div class="${isNumeric ? "text-end" : ""}">
        ${showBullet ? `<span class="me-1 text-muted">•</span>` : ""}
        ${val ?? "--"}
      </div>`;
  });

  return `<div class="d-block w-100">${html}</div>`;
}

// ===========================================================
// TABLE COLUMNS
// ===========================================================
function getColumns(saved) {
  return [
    {
      title: "Bill Date",
      field: "invoice_date",
      headerSort: false,
      width:  120,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      formatter: (c) => formatDateToDMY(c.getValue()),

    },

    {
      title: "Bill No.",
      field: "reference_number",
      headerHozAlign: "center",
      hozAlign: "center",
      width: 120,
      headerSort: false,
    },

    {
      title: "Vch. No.",
      field: "invoice_serial",
      headerHozAlign: "center",
      width:  130,
      hozAlign: "center",
      headerSort: false,
    },

    {
      title: "GRN",
      field: "grn_serial",
      headerHozAlign: "center",
      hozAlign: "center",
      width:  100,
      headerSort: false,
    },
    {
      title: "File No.",
      field: "file_number",
      hozAlign: "center",
      headerHozAlign: "center",
      width:  120,
    },
    {
      title: "Item Name",
      field: "details",
      width:  170,
      formatter: (cell) => {
        const details = cell.getValue() || [];

        const itemNames = details
          .map((d) => d.item?.name)
          .filter(Boolean)
          .join(", ");

        return `<div class="text-truncate" style="width: 100%;" title="${itemNames}">
                      ${itemNames || "--"}
                  </div>`;
      },
    },
    {
      title: "Supplier Name",
      field: "account.name",
      width: 380,
      formatter: (cell) => {
        const accountName = cell.getValue();
        // Access the full row data object
        const rowData = cell.getData();
        const city = rowData.account?.city || "";

        if (accountName == null) return "";

        const formattedName = String(accountName)
          .replace(/_/g, " ")
          .toUpperCase()
          .replace(/\b\w/g, (ch) => ch.toUpperCase());

        if (city) {
          return `<span class="fw-semibold text-truncate">${formattedName} (${city.toUpperCase()}) </span>`;
        } else {
          return `<span class="fw-semibold text-truncate">${formattedName} </span>`;
        }
      },
    },
    {
      title: "Sales Inv. No.",
      field: "sales_invoice_serial",
      headerHozAlign: "center",
      hozAlign: "center",
      headerSort: false,
      width: 110,
      bottomCalc: "total",
      bottomCalcFormatter: function (cell) {
        return `<div class="text-dark fw-bold" style="text-align: right; width: 100%; padding-right: 5px;">Total</div>`;
      },
    },

    {
      title: "Net Total",
      field: "net_amount",
      headerSort: false,
      width: saved.net_amount ?? 140,
      headerHozAlign: "right",
      hozAlign: "right",
      bottomCalc: function (values) {
        const total = values.reduce((sum, val) => {
          const num = parseFloat(val);
          return sum + (isNaN(num) ? 0 : num);
        }, 0);
      // bottomCalc: function (values, data, calcParams) {
      //   let total = 0;
      //   data.forEach((row) => {
      //     const details = row.details || [];
      //     details.forEach((item) => {
      //       const val = parseFloat(item.net_amount);
      //       if (!isNaN(val)) total += val;
      //     });
      //   });
        return total > 0
          ? `<span class="fw-bold text-dark">${formatIndianNumber(total, DECIMALS.AMOUNT)}</span>`
          : "";
      },
      bottomCalcFormatter: "html",
      // formatter: (cell) => {
      //   const details = cell.getData().details || [];
      //   return renderDetailsList(
      //     details,
      //     "net_amount",
      //     true,
      //     true,
      //     DECIMALS.AMOUNT
      //   );
      // },
    },

    {
      title: "Payment Status",
      field: "payment_status",
      headerHozAlign: "center",
      hozAlign: "center",
      headerSort: false,
      width: saved.payment_status ?? 150,
      formatter: (c) => {
        const row = c.getData();
        if(!row.reference) return `<span class="badge bg-secondary-lt fw-semibold">--</span>`;
        let key = "unpaid";
        if (row && row.reference) {
          let ref = row.reference;
          if (ref.is_closed) {
            key = "fully_paid";
          }
          if (ref.pending_amount > 0 && ref.settled_amount > 0) {
            key = "partially_paid";
          }
          if (ref.pending_amount < 0) {
            key = "overpaid";
          }
        }

        const cls =
          {
            fully_paid: "bg-success-lt",
            overpaid: "bg-cyan-lt",
            unpaid: "bg-danger-lt",
            partially_paid: "bg-orange-lt",
          }[key] || "bg-secondary-lt";

        const text = paymentStatusMap[key];

        return `<span class="badge ${cls} fw-semibold">${text}</span>`;
      },
    },

    // {
    //     title: "Invoice Status",
    //     field: "invoice_status",
    //     headerHozAlign: "center",
    //     hozAlign:'center',
    //     headerSort: false,
    //     width: saved.invoice_status ?? 150,
    //     formatter: (c) => {
    //         const val = c.getValue() ?? "--";
    //         const cls =
    //             { approved: "bg-success-lt", cancelled: "bg-danger-lt", draft: "bg-orange-lt" }[val] ||
    //             "bg-secondary-lt";

    //         return `<span class="badge ${cls} fw-semibold">${upperCase(val)}</span>`;
    //     },
    // },

    {
      title: "Actions",
      field: "actions",
      width: 100,
      hozAlign: "center",
      vertAlign: "middle",
      headerHozAlign: "center",
      headerSort: false,
      frozen: true,
      formatter: (cell) => {
        const row = cell.getData();
        const canView = currentPermissions.view ?? false;
        const canEdit = currentPermissions.update ?? false;

        const viewIcon = icons.view;
        const editIcon = icons.edit;

        let actions =
          '<div class="d-flex gap-2 align-items-center justify-content-center h-100 w-100">';

        // if (canView) {
        //     actions += `
        //         <span class="erp-btn-icon view" data-id="${row.id}" title="View Invoice">
        //             ${viewIcon}
        //         </span>`;
        // }

        if (canEdit) {
          actions += `
                        <span class="erp-btn-icon edit" data-id="${row.id}" title="Edit Invoice">
                            ${editIcon}
                        </span>`;
        }

        actions += "</div>";
        return actions;
      },
    },
  ];
}

// ===========================================================
// BOOTSTRAP
// ===========================================================
$(function () {
  // select2 binding
  bindSelect2();

  // date input with validation
  new DateInput("#start_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
  new DateInput("#end_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

  restoreFilters();

  const savedWidths = getSavedWidths();

  table = new Tabulator("#purchase_invoice_table", {
    layout: "fitColumns",
    dataTree: true,
    height: "550px",
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

    ajaxURL: purchaseInvoiceListUrl,
    ajaxParams: () => currentFilter,
    ajaxURLGenerator: (url, config, params) => {
      const qp = new URLSearchParams({
        ...currentFilter,
        page: params.page,
        size: params.size,
      });
      return `${url}?${qp}`;
    },
    ajaxResponse(url, params, response) {
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

  // ----- Filter buttons -----
  $("#filter_apply").on("click", function (e) {
    e.preventDefault();
    applyFilter();
  });

  $("#filter_clear").on("click", function (e) {
    e.preventDefault();

    // Reset GRN status dropdown to 'open'
    var $statusDropdown = $("#payment_status");
    if (!$statusDropdown.length) return;

    if ($statusDropdown.data("select2")) {
      $statusDropdown.val("open").trigger("change");
    } else {
      $statusDropdown.val("open");
    }

    clearFilter();
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

  function editPurchaseInvoice(id) {
    window.location.href = purchaseInvoiceEditUrl.replace(":id", id);
  }

  $(document).on("click", ".edit", function () {
    const id = $(this).data("id");
    editPurchaseInvoice(id);
  });

  // View Purchase Invoice
  $(document).on("click", ".view", function (event) {
    const purchaseInvoiceId = $(this).data("id");
    const url = purchaseInvoiceViewUrl.replace(":id", purchaseInvoiceId);
    $.ajax({
      url: url,
      beforeSend: function () {
        showLoader("Loading purchase Invoice...");
      },
      success: function (response) {
        formatPurchaseInvoiceData(response.data);
        const statusClasses = {
          fully_paid: "bg-success text-success-fg",
          overpaid: "bg-cyan text-cyan-fg",
          unpaid: "bg-danger text-danger-fg",
          partially_paid: "bg-orange text-orange-fg",
        };

        renderStatusBadge(
          "#invoice_status_view",
          response.data.payment_status,
          statusClasses,
        );
        $("#invoice_status_view").text(
          paymentStatusMap[response.data.payment_status],
        );

        table.replaceData();
        $("#purchase_invoice_modal").modal("show");
      },
      error: function (xhr) {
        showToast("error", "Oops! Something went wrong. Try again later.");
        console.error(xhr.responseText);
      },
      complete: function () {
        hideLoader();
      },
    });
  });

  function formatPurchaseInvoiceData(data) {
    // appStore.init("view", data);
    // console.log(data);

    // =====================================================
    // Set main Select2 fields
    // =====================================================
    if (data.account_id) {
      $("#supplier_name").val(data.account?.name ?? "");
    }

    if (data.broker_id) {
      $("#broker_id").val(data.broker?.name ?? "");
    }

    // =====================================================
    // Account Info
    // =====================================================
    //   console.log(data.account.city);
    $("#city").val(data.account?.city ?? "");
    // console.log(data.account.gst_type);

    $("#account_id_type").val(
      data.account.gst_type === GST_TYPE.LOCAL ? "LOCAL" : "INTERSTATE",
    );

    // =====================================================
    // Date Fields
    // =====================================================
    $("#grn_number").val(data.invoice_serial);

    $("#invoice_date").val(
      data.invoice_date ? formatDateToDMY(data.invoice_date) : "",
    );

    $("#grn_id").val(data.grn_serial ?? "");

    $("#file_number").val(data.file_number ?? "");

    $("#sales_inv_serial").val(data.sales_invoice_serial ?? "");

    $("#vehicle_number").val(data.vehicle_number ?? "");

    $("#remarks").text(data.remarks ?? "");

    $("#party_bill_date").val(
      data.party_bill_date ? formatDateToDMY(data.party_bill_date) : "",
    );

    $("#referenceNumber").val(data.reference_number ?? "");

    $("#purchase_type_id").val(data.purchase_type?.name ?? "");

    // Calculate Base Total from all details
    let baseTotal = 0;
    if (Array.isArray(data.details)) {
      data.details.forEach((d) => (baseTotal += parseFloat(d.amount || 0)));
    }
    $("#base_total_amount").val(formatIndianNumber(baseTotal));

    $("#net_amount").val(
      data.net_amount ? formatIndianNumber(data.net_amount) : "0.000",
    );

    $("#total_qty").val(
      data.total_quantity
        ? formatIndianNumber(data.total_quantity, DECIMALS.QTY)
        : "0.00",
    );

    // =====================================================
    // Items Table
    // =====================================================
    const tbody = document.querySelector("#item_table_body");
    if (!tbody) return;

    // Remove select2 from table
    $(tbody);
    // Remove cloned rows
    const rows = tbody.querySelectorAll("tr");
    rows.forEach((tr, i) => {
      if (i > 0) tr.remove();
    });

    // =====================================================
    // Populate Rows
    // =====================================================
    if (Array.isArray(data.details)) {
      data.details.forEach((detail, index) => {
        const taxType = data.gst_type;

        // Clone logic
        let row;
        if (index === 0) {
          row = tbody.querySelector("tr");
        } else {
          const firstRow = tbody.querySelector("tr");
          row = firstRow.cloneNode(true);

          $(row).find(".select2-container").remove();
          $(row)
            .find("select")
            .each(function () {
              $(this).removeClass("select2-hidden-accessible");
              $(this).removeAttr("data-select2-id");
            });

          tbody.appendChild(row);
        }

        // Set row data-row and rowId attributes
        row.dataset.row = index + 1;
        row.dataset.rowId = index + 1;
        const serialNumberSpan = row.querySelector("td:first-child span");

        // 2. Set the visible text content of that span to the current index + 1
        if (serialNumberSpan) {
          serialNumberSpan.textContent = index + 1;
        }

        // Map fields
        const selectors = [
          ["span.item_id", "item_id", "span"],
          ["span.unit_name", "unit_name", "span"],
          ["span.purchase_order_serial", "purchase_order_serial", "span"],
          ["span.purchase_order_id", "purchase_order_id", "span"],
          ["span.purchase_order_detail_id", "purchase_order_detail_id", "span"],
          ["span.condition_id", "condition_id", "span"],
          ["span.destination_id", "destination_id", "span"],
          ["span.cgst_rate", "cgst_rate", "span"],
          ["span.sgst_rate", "sgst_rate", "span"],
          ["span.igst_rate", "igst_rate", "span"],
          ["span.bag_count", "bag_count", "span"],
          ["span.party_quantity", "party_quantity", "span"],
          ["span.quantity", "quantity", "span"],
          ["span.inclusive_rate", "inclusive_rate", "span"],
          ["span.rate", "rate", "span"],
          ["span.amount", "amount", "span"],
        ];

        selectors.forEach(([selector, field, type]) => {
          const el = row.querySelector(selector);
          if (!el) return;

          el.name = `items[${index}][${field}]`;
          if (el.tagName === "SPAN") {
            // Handle span elements (view mode)
            if (field === "quantity") {
              const qty = Number(detail.quantity);
              el.textContent = isNaN(qty) ? "" : qty.toFixed(DECIMALS.QTY);
            } else if (field === "party_quantity") {
              const partyQty = Number(detail.party_quantity);
              el.textContent = isNaN(partyQty)
                ? ""
                : partyQty.toFixed(DECIMALS.QTY);
            } else if (field === "item_id") {
              el.textContent = detail.item?.name ?? "";
            } else if (field === "destination_id") {
              el.textContent = detail.destination?.name ?? "";
            } else if (field === "condition_id") {
              el.textContent = detail.condition?.name ?? "";
            } else if (field === "unit_name") {
              el.textContent = detail.item?.unit?.name ?? "";
            } else {
              el.textContent = detail[field] ?? "";
            }
          } else if (field === "quantity") {
            const qty = Number(detail.quantity);
            el.value = isNaN(qty) ? "" : qty.toFixed(DECIMALS.QTY);
          } else if (field === "party_quantity") {
            const partyQty = Number(detail.party_quantity);
            el.value = isNaN(partyQty) ? "" : partyQty.toFixed(DECIMALS.QTY);
          } else if (field === "unit_name") {
            el.value = detail.item?.unit?.name ?? "";
          } else {
            el.value = detail[field] ?? "";
          }
        });
      });
    }

    // add dynamically data  in particular data
    particularTableData(data);
  }

  /* add dynamically row in bill_sundry_table_body table */
  function particularTableData(data) {
    const bsTbody = document.querySelector("#bill_sundry_table_body");
    if (!bsTbody || !Array.isArray(data.bill_sundries)) return;
    // console.log("particularTableData", data.bill_sundries);
    bsTbody.innerHTML = data.bill_sundries
      .map((item) => {
        const rate = Number(item.rate_percent || 0).toFixed(2);
        const amount = Math.abs(Number(item.amount || 0)).toFixed(2);

        return `
        <tr>
          <td class="fw-bold text-muted bg-light border border-secondary-lt justify-content-between align-items-center">          
            <input type="text" class="form-control form-control-sm border-0 bg-transparent fw-bold" 
              value="${item.name ?? ""}" disabled />
          </td>
          <td class="fw-bold text-muted bg-light border border-secondary-lt justify-content-between align-items-center text-center">
           <input type="number" class="form-control form-control-sm border-0 bg-transparent text-center fw-bold" 
                   value="${Number(rate) === 0 ? "" : rate}" disabled />
          </td>
          <td class="text-end fw-bold bg-light text-muted border border-secondary-lt justify-content-between align-items-center">
           <input type="text" class="form-control form-control-sm border-0 bg-transparent text-end fw-bold" 
                   value="${Number(amount) === 0 ? "" : formatIndianNumber(amount)}" disabled />
          </td>
        </tr>`;
      })
      .join("");
  }
});
