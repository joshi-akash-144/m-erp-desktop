// ===========================================================
let table = null;
let currentFilter = {};
let currentPermissions = {};
let savedColWidths = {};
let grandTotalRecords = 0;
let totalFilteredRecords = 0;
const PRINT_MAX_RECORDS = 100;

const FILTER_KEY = "sales_invoice_filter";
const WIDTH_KEY = "sales_invoice_col_widths";
const PAGE_SIZE_KEY = "sales_invoice_page_size";
const PAGE_NUM_KEY  = "sales_invoice_page_num";

const filterFields = [
    "start_date",
    "end_date",
    "account_id",
    "item_id",
    "payment_status",
    "grn_number",
    "vehicle_number",
    "sales_invoice_serial",
    "invoice_serial",
    "file_no",
    "reference_number",
    "op_numbers",
    "bill_from",
    "bill_to",
];

    // Initialize Vehicle Registration Validation
    if (typeof initVehicleRegValidation === 'function') {
        initVehicleRegValidation('.txtRegNo');
    }
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
    const selectIdArray = ['#account_id', '#item_id', '#payment_status', '#grn_serial', '#voucher_serial', '#op_numbers', '#grn_number', '#bill_from', '#bill_to'];
    selectIdArray.forEach(element => {
        $(element).select2({
            theme: "bootstrap-5",
            allowClear: true,
            placeholder: "Select " + element.replace('#', '').replace('.', '').replace(/_/g, ' ').replace('_id', '').replace('id', '') + " ...",
            width: '100%',
        });
    });

    $(document).on("select2:open", function (e) {
        const selectElement = $(e.target);
        const searchInput = selectElement
            .data("select2")
            .$dropdown.find(".select2-search__field");

        // Fix for Select2 focus jump in jQuery 3.6+
        if (searchInput.length > 0) {
            setTimeout(() => {
                searchInput[0].focus();
            }, 0);
        }

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
 
    localStorage.setItem(FILTER_KEY, JSON.stringify(currentFilter));
 
    if (table) table.setData();
}
 
function clearFilter() {
    $.each(filterFields, function (i, id) {
        const defaultValue = (id === "payment_status") ? "unpaid" : "";
        setFieldValue(id, defaultValue);
    });
 
    currentFilter = { payment_status: "unpaid" };
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
  decimal = 2
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
      val = isNaN(num) ? 0 : num.toFixed(decimal);
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
function getColumns(savedColWidths) {
    return [

        // =======================
        // Row Number
        // =======================
        {
          title: "Select",
          field: "selected",
          width: savedColWidths.row_number ?? 80,
          headerSort: false,
          hozAlign: "center",
          vertAlign: "middle",
          headerHozAlign: "center",
          headerVerAlign: "middle",
          verAlign: "middle",
          resizable: true,          
          titleFormatter: "rowSelection",          
          formatter: "rowSelection", 
          cellClick: function(e, cell) {
            cell.getRow().toggleSelect();
          }
        },
        // =======================
        // Bill Number No.
        // =======================
        {
          title: "Bill No",
          field: "reference_number",
          width:  120,
          headerSort: false,
          hozAlign: "center",
          headerHozAlign: "center",
          sorter: "string",
          resizable: true,
        },
        // =======================
        // Purchase Order Number.
        // =======================
        {
          title: "P.O. No",
          field: "sales_order.purchase_order_number",
          width: savedColWidths.sales_order_id ?? 100,
          headerSort: false,
          hozAlign: "center",
          headerHozAlign: "center",
          sorter: "string",
          resizable: true,
        },
    
        // =======================
        // Bill Date
        // =======================
        {
          title: "Bill Date",
          field: "invoice_date",
          width:  115,
          headerSort: false,
          hozAlign: "center",
          headerHozAlign: "center",
          sorter: "date",
          resizable: true,
          formatter: (cell) => formatDateToDMY(cell.getValue()),
        },
        
         {
          title: "GRN",
          field: "grn_number",
          width: 150,
          headerSort: false,
          hozAlign: "center",
          headerHozAlign: "center ",
          sorter: "date",
          resizable: true,
          formatter: (cell) => cell.getValue(),
        },
        
    
        // =======================
        // Due Date
        // =======================
        {
          title: "Customer Name",
          field: "account.name",
          width: 350,
          headerSort: false,
          hozAlign: "left",
          headerHozAlign: "left",
          sorter: "date",
          resizable: true,
          formatter: (cell) => {
              const CustomerName = cell.getValue();
              // Access the full row data object
              const rowData = cell.getData();
              const city = rowData.account?.city || "";
    
              if (CustomerName == null) return "";
    
              const formattedName = String(CustomerName)
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
        
        // {
        //   title: "City",
        //   field: "account.city",
        //   width: savedColWidths.supplier ?? 150,
        //   sorter: "string",
        //   headerSort: false,
        //   resizable: true,
        // },
        
        {
          title: "Item Name",
          field: "details",
          width: 180,
          headerSort: false,
          resizable: true,
          formatter: (cell) => {
            const details = cell.getValue() || [];
            
            const itemNames = details.map(d => d.item?.name).filter(Boolean).join(", ");
            
            return `<div class="text-truncate" style="width: 100%;" title="${itemNames}">
                        ${itemNames || "--"}
                    </div>`;
          },
        },
        
        {
          title: "Vehicle Number",
          field: "vehicle_number",
          width: savedColWidths.vehicle_number ?? 130,
          headerSort: false,
          resizable: true,        
          bottomCalc: "total",           
          formatter: "money", 
          headerHozAlign: "center",
          hozAlign: "center",
          bottomCalcFormatter: function(cell) {                
              return `<div style="text-align: right;" class="text-dark fw-bold">Total</div>`;
          }     
        },
        
        // =======================
        // Net Total
        // =======================
        {
          title: "Net Total",
          field: "net_amount",
          width: savedColWidths.net_amount ?? 180,
          sorter: "string",
          headerHozAlign: "right",
          hozAlign: "right",
          headerSort: false,
          resizable: true,
          bottomCalc: "sum",              
          formatter: (cell) => {
            const val = cell.getValue();
            return `${formatIndianNumber(val)}`;          
          },        
          bottomCalcFormatter: function(cell) {
              const val = cell.getValue();            
              return `<span class="text-dark fw-bold">${formatIndianNumber(val)}</span>`;
          }          
        },
       
            
        // =======================
        // Status
        // =======================
        // {
        //   title: "Payment Status",
        //   field: "payment_received_status",
        //   width: saved.payment_received_status ?? 250,
        //   hozAlign: "center",
        //   headerHozAlign: "center",
        //   sorter: "string",
        //   resizable: true,
        //   formatter: (cell) => {
        //     const paymentReceivedStatus = cell.getValue();
    
        //     const badgeClass =
        //       {
        //           fully_paid: "bg-success-lt",
        //           overpaid: "bg-cyan-lt",
        //           unpaid: "bg-danger-lt",
        //           partially_paid: "bg-orange-lt",
        //       }[paymentReceivedStatus] ?? "bg-secondary-lt";
        //     return `<span class="badge ${badgeClass} fw-semibold">${upperCase(
        //       paymentReceivedStatus
        //     )}</span>`;
        //   },
        // },
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
                const canPrintInvoice = currentPermissions.view ?? false;
                const viewIcon = icons.view;
                const editIcon = icons.edit;
                const PrintInvoiceIcon = icons.print;

                let actions = '<div class="d-flex gap-2 align-items-center justify-content-center h-100 w-100">';
                
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

                if (canPrintInvoice)
                    actions += `
                        <span class="erp-btn-icon print" data-id="${row.id}" title="Print Invoice">
                            ${PrintInvoiceIcon}
                        </span>`;          

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

    table = new Tabulator("#sales_invoice_table", {
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

        ajaxURL: salesInvoiceListUrl,
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
            window.recordsInCurrentPage = response.count || 0;
            totalFilteredRecords = response.total || 0;
            grandTotalRecords    = response.grand_total || 0;
            return response;
        },
        paginationCounter: function(pageSize, currentRow, _currentPage, _totalRows, _totalPages) {
            const total = totalFilteredRecords;
            if (!total) return "No entries found";
            const start = currentRow;
            const end   = Math.min(currentRow + pageSize - 1, total);
            let text = `Showing ${start} to ${end} of ${total} entries`;
            if (grandTotalRecords > 0 && grandTotalRecords !== total) {
                text += ` (filtered from ${grandTotalRecords} total entries)`;
            }
            return text;
        },
        columns: getColumns(savedWidths),
    });

    table.on("columnResized", saveColWidths);

    table.on("pageLoaded", function(pageNo) {
        localStorage.setItem(PAGE_NUM_KEY, pageNo);
        localStorage.setItem(PAGE_SIZE_KEY, table.getPageSize());
    });

    $("#filter_apply").on("click", function(e){
      e.preventDefault();
      applyFilter();
    });
 
    $("#filter_clear").on("click", function(e){
      e.preventDefault();
      clearFilter();
    });

    // Event delegation for dropdown report actions
    $(document).on("click", ".dropdown-item", function (e) {
        // console.log(totalRecordsCount);
        const $action = $(this);
        const type = $action.data("type");
        const route = $action.data("route");
        const format = $action.data("format");

        if (!type) return;

        // Normalize currentFilter (ensure it's not null/empty for the report helpers if needed)
        const safeFilter = currentFilter && Object.keys(currentFilter).length > 0 ? currentFilter : {};

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

function editSalesInvoice(id) {
    window.location.href = salesInvoiceEditUrl.replace(":id", id);
}

$(document).on("click", ".edit", function() {
    const id = $(this).data("id");
    editSalesInvoice(id);
});


// bulk rows Print Report
$(document).on("click", "#bulk_print", function (e) {
    e.preventDefault();
    const selectedRows = table.getSelectedData();
    let ids = "";


    // if user select more then 150 record then show error.
    if (selectedRows.length > 0) {
        if (selectedRows.length > PRINT_MAX_RECORDS) {
            Swal.fire({
                title: "Error",
                text: `Printing more than ${PRINT_MAX_RECORDS} records is not allowed!`,
                icon: "error",
                confirmButtonText: "OK",
            });
            return;
        }
        ids = selectedRows.map(row => row.id).join(',');
    } else {
        // if filtered records are more than PRINT_MAX_RECORDS then show error
        if (totalFilteredRecords > PRINT_MAX_RECORDS) {
            Swal.fire({
                title: "Error",
                text: `Printing more than ${PRINT_MAX_RECORDS} records is not allowed!`,
                icon: "error",
                confirmButtonText: "OK",
            });
            return;
        }
        ids = "";
    }
  
    const headerEnable = $('#withHeaderCheck:checked').val() || 0;
    SalesInvoicePrintReport(ids, headerEnable);
});

// single row Print Report
$(document).on("click", ".print", function () {
    const id = $(this).data("id");
    const headerEnable = $('#withHeaderCheck:checked').val() || 0;
    SalesInvoicePrintReport(id, headerEnable);
});

function SalesInvoicePrintReport(salesInvoiceId, headerEnable) {
    const data = {
        salesInvoiceId: salesInvoiceId,
        headerEnable: headerEnable,
        format: "print",
        currentFilter: typeof currentFilter !== 'undefined' ? currentFilter : {}
    };
    
    printReport(salesInvoicePrintUrl, data);
}

  // View Sales Invoice
  $(document).on("click", ".view", function (event) {
    const salesInvoiceId = $(this).data("id");
    const url = salesInvoiceViewUrl.replace(":id", salesInvoiceId);
    $.ajax({
      url: url,
      beforeSend: function () {
        showLoader("Loading Sales Invoice...");
      },
      success: function (response) {
        console.log("response", response);
        
        formatSalesInvoiceData(response.data);      
        // const statusClasses = {
        //   'fully_paid':     'bg-success text-success-fg',
        //   'overpaid':       'bg-cyan text-cyan-fg',
        //   'unpaid':         'bg-danger text-danger-fg',
        //   'partially_paid': 'bg-orange text-orange-fg',
        // };           
 
        // renderStatusBadge("#invoice_status_view", response.data.payment_status, statusClasses);
        // $("#invoice_status_view").text(paymentStatusMap[response.data.payment_status]);

        table.replaceData();      
        $("#sales_invoice_modal").modal("show");
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




function formatSalesInvoiceData(data) {
  // console.log("sales invoice data", data);
  // =====================================================
  // Set main fields
  // =====================================================
  $("#invoice_serial").val(data.invoice_serial ?? "");
  $("#invoice_date").val(data.invoice_date ? formatDateToDMY(data.invoice_date) : "");
  $("#delivery_challan_number").val(data.delivery_challan_number ?? "");
  $("#po_number").val(data.sales_order?.purchase_order_number ?? "");
  $("#sales_grn_number").val(data.grn_number ?? "");
  $("#sales_account_id").val(data.account?.name ?? "");
  $("#account_id_city").val(data.account?.city ?? "");
  $("#tax_type").val(data.gst_type === GST_TYPE.INTERSTATE ? "INTERSTATE" : "LOCAL");

  $("#last_invoice_date").val(data.last_invoice_date ? formatDateToDMY(data.last_invoice_date) : "");
  $("#broker_id").val(data.broker?.name ?? "");
  $("#kms").val(data.kms ?? "");
  $("#sales_vehicle_number").val(data.vehicle_number ?? "");
  $("#delivery_date").val(data.delivery_date ? formatDateToDMY(data.delivery_date) : "");
  $("#sale_type_id").val(data.sale_type?.name ?? "");

  $("#remarks").val(data.remarks ?? "");
  $("#ewaybill_number").val(data.ewaybill_number ?? "");

  // Calculate Totals
  let baseTotal = 0;
  if(Array.isArray(data.details)){
      data.details.forEach(d => baseTotal += parseFloat(d.amount || 0));
  }
  $("#base_total_amount").val(formatIndianNumber(baseTotal));
  $("#net_amount").val(data.total_amount ? formatIndianNumber(data.total_amount, DECIMALS.AMOUNT) : "0.00");
  $("#total_qty").val(data.total_quantity ? formatIndianNumber(data.total_quantity, DECIMALS.QTY) : "0.00");

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
