let table;
let currentFilter = { order_status: "open" };
let currentPermissions = {};
let savedColWidths = {};
let grandTotalRecords = 0;
let totalFilteredRecords = 0;


const FILTER_KEY = "sales_order_filter";
const WIDTH_KEY = "sales_order_col_widths";
const PAGE_SIZE_KEY = "sales_order_page_size";
const PAGE_NUM_KEY  = "sales_order_page_num";

const filterFields = [
    "so_id",
    "start_date",
    "end_date",
    "account_id",
    "item_id",
    "broker_id",
    "condition_id",
    "destination_id",
    "order_status",
    "due_status"    
];


$(document).ready(function () {
    // select2 binding
    bindSelect2();

    // date input with validation
    new DateInput("#start_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#end_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    restoreFilters();
    // Tabulator
    // purchaser order List
  table = new Tabulator("#sales_order_table", {
    height: "550px",
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

    ajaxURL: salesOrderListUrl,
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
    columns: getTableColumns(savedColWidths),
  });

  table.on("pageLoaded", function(pageNo) {
      localStorage.setItem(PAGE_NUM_KEY, pageNo);
      localStorage.setItem(PAGE_SIZE_KEY, table.getPageSize());
  });

  // Handle Close Selected POs
  $("#closeSelectedBtn").on("click", function() {
      const selectedIds = table.getRows()
                          .map(r => r.getData())
                          .filter(data => data.selected)
                          .map(data => data.id);
      
      if(selectedIds.length === 0) return;

      Swal.fire({
          title: "Are you sure?",
          text: `You want to explicitly close ${selectedIds.length} selected sales order(s)?`,
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#d33",
          cancelButtonColor: "#3085d6",
          confirmButtonText: "Yes, close them!"
      }).then((result) => {
          if (result.isConfirmed) {
              $.ajax({
                  url: "/vouchers/sales-orders/close-multiple",
                  type: "POST",
                  data: { ids: selectedIds },
                  success: function(res) {
                      Swal.fire("Closed!", res.message || "Orders closed successfully.", "success");
                      table.setPage(table.getPage());
                      $("#closeSelectedBtn").addClass("d-none");
                      $(".form-check-input").prop("checked", false);
                  },
                  error: function(err) {
                      if (typeof handleAjaxError === "function") {
                          handleAjaxError(err);
                      } else {
                          Swal.fire("Error", "Failed to close sales orders.", "error");
                      }
                  }
              });
          }
      });
  });

  // Handle Edit PO
  $(document).on("click", ".edit", function() {
      const id = $(this).data("id");
      window.location.href = salesOrderEditUrl.replace(':id', id);
  });

   $("#filter_apply").on("click", function(e){
      e.preventDefault();
      applyFilter();
    });
   $("#filter_clear").on("click", function (e) {
        e.preventDefault();
        clearFilter();
 
        // Reset GRN status dropdown to 'open'
        var $statusDropdown = $("#order_status");
        if (!$statusDropdown.length) return;
 
        if ($statusDropdown.data("select2")) {
            $statusDropdown.val("all").trigger("change");
        } else {
            $statusDropdown.val("all");
        }
 
        applyFilter();
    });


});

function toggleCloseSelectedBtn() {
    const rows = table.getRows();
    const hasSelection = rows.some(r => r.getData().selected);
    if(hasSelection) {
        $("#closeSelectedBtn").removeClass("d-none");
    } else {
        $("#closeSelectedBtn").addClass("d-none");
    }
}


function getTableColumns(savedColWidths = {}) {
  return [
      
    {
        titleFormatter: function(cell) {
            const wrapper = document.createElement("div");
            wrapper.className = "d-flex align-items-center justify-content-center h-100 w-100";
            
            const checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.className = "form-check-input m-0 shadow-none"; 

            checkbox.addEventListener("change", function(e) {
                e.stopPropagation();
                const checked = this.checked;

                table.blockRedraw(); 
                table.getRows().forEach(row => {
                    row.update({ selected: checked }); 
                });
                table.restoreRedraw(); 
                toggleCloseSelectedBtn();
            });
            
            wrapper.appendChild(checkbox);
            return wrapper;
        },
        field: "selected",
        width: 50,
        hozAlign: "center",
        vertAlign: "middle",
        headerSort: false,
        formatter: function(cell) {
            // Render actual checkbox per row firmly centered
            const isChecked = cell.getValue() ? "checked" : "";
            return `<div class="d-flex align-items-center justify-content-center h-100 w-100"><input type="checkbox" class="form-check-input m-0 shadow-none" ${isChecked}></div>`;
        },
        cellClick: function(e, cell){
            e.stopPropagation();
            const newValue = !cell.getValue();
            cell.setValue(newValue);

            // Update header
            const rows = table.getRows();
            const allChecked = rows.length > 0 && rows.every(r => r.getData().selected);
            const headerCheckbox = cell.getTable().getColumn("selected").getElement().querySelector("input");
            if(headerCheckbox) headerCheckbox.checked = allChecked;
            
            toggleCloseSelectedBtn();
        }
    },
    
    // =======================
    // Sales Order No.
    // =======================
    {
      title: "So. No",
      field: "order_serial",
      width: savedColWidths.order_serial ?? 130,
      headerSort: true,
      hozAlign: "center",
      headerHozAlign: "center",
      sorter: "string",
      resizable: true,
    },
    // =======================
    // Purchase Order Number.
    // =======================
    {
      title: "PO. No",
      field: "purchase_order_number",
      width: savedColWidths.po_number ?? 100,
      headerSort: true,
      hozAlign: "center",
      headerHozAlign: "center",
      sorter: "string",
      resizable: true,
    },
    // =======================
    // Purchase Order Date.
    // =======================
    {
      title: "P.O. Date",
      field: "purchase_order_date",
      width: savedColWidths.po_date ?? 130,
      headerSort: false,
      hozAlign: "center",
      headerHozAlign: "center",
      sorter: "date",
      resizable: true,
      formatter: (cell) => formatDateToDMY(cell.getValue()),
    },

    // =======================
    // Delivery Date
    // =======================
    {
      title: "Delivery Date",
      field: "delivery_date",
      width: savedColWidths.delivery_date ?? 120,
      headerSort: false,
      hozAlign: "center",
      headerHozAlign: "center",
      sorter: "date",
      resizable: true,
      formatter: (cell) => formatDateToDMY(cell.getValue()),
    },

    // =======================
    // Supplier
    // =======================
    {
      title: "Customer",
      field: "account.name",
      width: savedColWidths.supplier ?? 400,
      sorter: "string",
      headerSort: false,
      resizable: true,
      formatter: (cell) => {
        const accountName = cell.getValue();
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
    // {
    //   title: "Customer City",
    //   field: "account.city",
    //   width: savedColWidths.supplier ?? 150,
    //   sorter: "string",
    //   headerSort: false,
    //   resizable: true,
    // },
    // {
    //   title: "Broker",
    //   field: "broker.name",
    //   width: savedColWidths.supplier ?? 150,
    //   sorter: "string",
    //   headerSort: false,
    //   resizable: true,
    // },
    // {
    //   title: "Broker City",
    //   field: "broker.city",
    //   width: savedColWidths.supplier ?? 150,
    //   sorter: "string",
    //   headerSort: false,
    //   resizable: true,
    //   formatter: (cell) => {
    //     const val = cell.getValue();
    //     return val ? val : `<span class="text-muted">--</span>`;
    //   },
    // },
    {
      title: "Items",
      field: "details",
      width: savedColWidths.items ?? 140,
      headerSort: false,
      resizable: true,
      formatter: (c) => {
        const htmlString = renderDetailsList(c.getValue(), "item.name");
        const tempDiv = document.createElement("div");
        tempDiv.innerHTML = htmlString;

        // 1. Get each item (only direct children to avoid duplicates)
        const itemElements = tempDiv.firstElementChild
          ? Array.from(tempDiv.firstElementChild.children)
          : [];

        const items = itemElements
          .map((el) => el.textContent.replace("•", "").trim())
          .filter((txt) => txt !== "" && txt !== "--");

        if (items.length === 0) return `<span class='text-muted'>--</span>`;

        // 2. Determine if we need bullets (only if more than 1 item)
        const showBullet = items.length > 1;

        // 3. Create the HTML: Each item gets a truncating div with an optional bullet
        const content = items
          .map((item) => {
            return `<div class="text-truncate" style="max-width: 100%;" title="${item.replace(
              /"/g,
              "&quot;"
            )}">
            ${showBullet ? `<span class="me-1 text-muted">•</span>` : ""}
                  ${item}
                </div>`;
          })
          .join("");

        return `<div class="d-block w-100">${content}</div>`;
      },
    },
    {
      title: "Destination",
      field: "details",
      width: savedColWidths.items ?? 140,
      headerSort: false,
      resizable: true,      
      formatter: (c) => {
        const htmlString = renderDetailsList(c.getValue(), "destination.name");
        const tempDiv = document.createElement("div");
        tempDiv.innerHTML = htmlString;

        // 1. Get each item (only direct children to avoid duplicates)
        const itemElements = tempDiv.firstElementChild
          ? Array.from(tempDiv.firstElementChild.children)
          : [];

        const items = itemElements
          .map((el) => el.textContent.replace("•", "").trim())
          .filter((txt) => txt !== "" && txt !== "--");

        if (items.length === 0) return `<span class='text-muted'>--</span>`;

        // 2. Determine if we need bullets (only if more than 1 item)
        const showBullet = items.length > 1;

        // 3. Create the HTML: Each item gets a truncating div with an optional bullet
        const content = items
          .map((item) => {
            return `<div class="text-truncate" style="max-width: 100%;" title="${item.replace(
              /"/g,
              "&quot;"
            )}">
                  ${showBullet ? `<span class="me-1 text-muted">•</span>` : ""}
                  ${item}
                </div>`;
          })
          .join("");

        return `<div class="d-block w-100">${content}</div>`;
      },
    },
    {
      title: "Condition",
      field: "details",
      width: savedColWidths.items ?? 140,
      headerSort: false,
      resizable: true,
      formatter: (c) => {
        const htmlString = renderDetailsList(c.getValue(), "condition.name");
        const tempDiv = document.createElement("div");
        tempDiv.innerHTML = htmlString;

        // 1. Get each item (only direct children to avoid duplicates)
        const itemElements = tempDiv.firstElementChild
          ? Array.from(tempDiv.firstElementChild.children)
          : [];

        const items = itemElements
          .map((el) => el.textContent.replace("•", "").trim())
          .filter((txt) => txt !== "" && txt !== "--");

        if (items.length === 0) return `<span class='text-muted'>--</span>`;

        // 2. Determine if we need bullets (only if more than 1 item)
        const showBullet = items.length > 1;

        // 3. Create the HTML: Each item gets a truncating div with an optional bullet
        const content = items
          .map((item) => {
            return `<div class="text-truncate" style="max-width: 100%;" title="${item.replace(
              /"/g,
              "&quot;"
            )}">
                  ${showBullet ? `<span class="me-1 text-muted">•</span>` : ""}
                  ${item}
                </div>`;
          })
          .join("");

        return `<div class="d-block w-100">${content}</div>`;
      },
      bottomCalcFormatter: function(cell) {                
        return `<div class="text-dark fw-bold" style="text-align: right; width: 100%; padding-right: 5px;">Total</div>`;
      }
      
    },

    // =======================
    // Quantity Summary (Ordered / Received / Pending)
    // =======================
    {
      title: "Qty",
      field: "details_ordered_qty",
      width: savedColWidths.items ?? 130,
      headerSort: false,
      resizable: true,
      hozAlign: "right",
      headerHozAlign: "right",
      sorter: "number",
      bottomCalc: function(values, data, calcParams) {
        let total = 0;
        data.forEach(row => {
          const details = row.details || [];
          details.forEach(item => {
            const val = parseFloat(item.ordered_qty);
            if (!isNaN(val)) total += val;
          });
        });
        return total > 0 ? `<span class="fw-bold text-dark">${formatIndianNumber(total, typeof DECIMALS !== 'undefined' ? DECIMALS.QTY : 3)}</span>` : "";
      },
      bottomCalcFormatter: "html",
      formatter: (cell) => {
        const details = cell.getData().details || [];
        return renderDetailsList(
          details,
          "ordered_qty",
          true,
          false,         
          DECIMALS.QTY
        );
      },
    },
    {
      title: "Del.Qty",
      field: "details_received_qty",
      width: savedColWidths.items ?? 130,
      headerSort: false,
      resizable: true,
      hozAlign: "right",
      headerHozAlign: "right",
      sorter: "number",
      bottomCalc: function(values, data, calcParams) {
        let total = 0;
        data.forEach(row => {
          const details = row.details || [];
          details.forEach(item => {
            const val = parseFloat(item.received_qty);
            if (!isNaN(val)) total += val;
          });
        });
        return total > 0 ? `<span class="fw-bold text-success">${formatIndianNumber(total, typeof DECIMALS !== 'undefined' ? DECIMALS.QTY : 3)}</span>` : "";
      },
      bottomCalcFormatter: "html",
      formatter: (cell) => {
        const details = cell.getData().details || [];
        return renderDetailsList(
          details,
          "received_qty",
          true,
          false,         
          DECIMALS.QTY
        );
      },
    },

    {
      title: "Rem.Qty",
      field: "details_remaining_qty",
      width: savedColWidths.items ?? 130,
      headerSort: false,
      resizable: true,
      hozAlign: "right",
      headerHozAlign: "right",
      sorter: "number",
      bottomCalc: function(values, data, calcParams) {
        let total = 0;
        data.forEach(row => {
          const details = row.details || [];
          details.forEach(item => {
            const val = parseFloat(item.remaining_qty);
            if (!isNaN(val)) total += val;
          });
        });
        return total > 0 ? `<span class="fw-bold text-danger">${formatIndianNumber(total, typeof DECIMALS !== 'undefined' ? DECIMALS.QTY : 3)}</span>` : "";
      },
      bottomCalcFormatter: "html",
      formatter: (cell) => {
        const details = cell.getData().details || [];
        return renderDetailsList(
          details,
          "remaining_qty",
          true,
          false,         
          DECIMALS.QTY
        );
      },
    },
    // =======================
    // Receiving Percentage
    // =======================
    {
      title: "Del. %",
      field: "details_rec_percentage",
      width: savedColWidths.percentage ?? 140,
      headerSort: false,
      resizable: true,
      hozAlign: "center",
      headerHozAlign: "center",
      formatter: (cell) => {
        const details = cell.getData().details || [];
        if (!Array.isArray(details) || details.length === 0) {
          return '<span class="text-muted">--</span>';
        }

        let html = "";
        details.forEach((item, index) => {
          let ordered = parseFloat(item.ordered_qty) || 0;
          let received = parseFloat(item.received_qty) || 0;
          let percentage = ordered > 0 ? (received / ordered) * 100 : 0;
          
          let color = "bg-primary";
          if (percentage >= 100) color = "bg-success";
          else if (percentage < 10 && percentage > 0) color = "bg-danger";

          let printPercent = percentage > 100 ? 100 : percentage;

          html += `
            <div class="d-flex align-items-center w-100" style="min-height: 22px;">
              <div class="progress flex-grow-1" style="height: 5px; max-width: 60px;">
                <div class="progress-bar ${color}" style="width: ${printPercent}%"></div>
              </div>
              <div class="ms-2 text-end text-muted" style="font-size: 0.75rem; min-width: 35px;">${percentage.toFixed(2)}%</div>
            </div>`;
        });

        return `<div class="d-block w-100">${html}</div>`;
      },
    },


    // =======================
    // Status
    // =======================
    {
      title: "Status",
      field: "order_status",
      width: savedColWidths.status ?? 130,
      hozAlign: "center",
      headerHozAlign: "center", 
      vertAlign: "middle",
      sorter: "string",
      resizable: true,
      formatter: (cell) => {
        let orderStatus = cell.getValue();

        let badgeClass =
          {
            open: "bg-success-lt",
            close: "bg-danger-lt",
            cancel: "bg-orange-lt",
            hold: "bg-blue-lt",
          }[orderStatus] ?? "bg-secondary-lt";
  
         if(orderStatus == 'due'){
          orderStatus = 'open';
          badgeClass = "bg-success-lt";
        }
    
        return `<span class="badge ${badgeClass} fw-semibold">${upperCase(
          orderStatus
        )}</span>`;
      },
    },

    // =======================
    // Actions
    // =======================
    {
      title: "Actions",
      field: "actions",
      width: 130,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      frozen: true,
      formatter: (cell) => {
        const row = cell.getData();
        const canView = currentPermissions.view ?? false;
        const canEdit = currentPermissions.update ?? false;
        // const canDelete = currentPermissions.delete ?? false;

        const viewIcon = icons.view;
        const editIcon = icons.edit;
        // const deleteIcon = icons.delete;

        let actions = '<div class="d-flex gap-2 justify-content-center">';

        // if (canView) {
        //   actions += `
        //       <span class="erp-btn-icon view" data-module="sales-order/view" data-id="${row.id}" title="View Order">
        //         ${viewIcon}
        //       </span>`;
        // }

        if (canEdit) {
          actions += `
              <span class="erp-btn-icon edit" data-id="${row.id}" title="Edit Order">
                ${editIcon}
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


function bindSelect2() {
    const selectIdArray = ['#so_id', '#account_id', '#item_id', '#destination_id', '#condition_id', '#order_status', '#due_status'];
    selectIdArray.forEach(element => {
        $(element).select2({
            theme: "bootstrap-5",
            allowClear: true,
            placeholder: "Select " + element.replace('#', '').replace('.', '').replace('_id', '') + " ...",
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

function applyFilter() {
    currentFilter = {};
 
    $.each(filterFields, function (i, id) {
        var el    = $("#" + id).get(0);
        var value = getFieldValue(el);
 
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
    if (!currentFilter['order_status']) {
        currentFilter['order_status'] = 'open';
        // Also sync the dropdown UI to reflect the default
        var $statusDropdown = $("#order_status");
        if ($statusDropdown.data("select2")) {
            $statusDropdown.val("open").trigger("change.select2");
        } else {
            $statusDropdown.val("open");
        }
    }
    localStorage.setItem(FILTER_KEY, JSON.stringify(currentFilter));
 
    if (table) table.setData();
}
 
 
function clearFilter() {
    $.each(filterFields, function (i, id) {
        var el = $("#" + id).get(0);
        setFieldValue(el, "");
    });
 
    currentFilter = {};
    localStorage.removeItem(FILTER_KEY);
 
    if (table) table.setData();
}
 
/** Restore filters on page load */
function restoreFilters() {
    var saved = localStorage.getItem(FILTER_KEY);
    if (!saved) return;
 
    currentFilter = JSON.parse(saved);
 
    $.each(currentFilter, function (id, value) {
        var el = $("#" + id).get(0);
        setFieldValue(el, value);
    });
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
      val = isNaN(num) ? 0 : (typeof formatIndianNumber === 'function' ? formatIndianNumber(num, decimal) : num.toFixed(decimal));
    }

    html += `
      <div class="${isNumeric ? "text-end" : ""}">
        ${showBullet ? `<span class="me-1 text-muted">•</span>` : ""}
        ${val ?? "--"}
      </div>`;
  });

  return `<div class="d-block w-100">${html}</div>`;
}

function editSalesOrder(id) {
    window.location.href = "/sales-order/" + id + "/edit";
} 
  // Print Report
  $(document).on("click", ".dropdown-item", function (e) {
    const action = e.target.closest(".dropdown-item");
    if (!action) return;
 
    const type = action.dataset.type; // print / export
    const route = action.dataset.route;
    const format = action.dataset.format;
 
    if (!type) return;
 
    switch (type) {
      case "print":
        printReport(route, { format: format , currentFilter: currentFilter });
        break;
 
      case "excel":
        downloadExcel(route , { currentFilter: currentFilter });
        break;
    }
  });
 

  // View Sales Order
  $(document).on("click", ".view", function (event) {
    const salesOrderId = $(this).data("id");
    const url = salesOrderViewUrl.replace(":id", salesOrderId);
    $.ajax({
      url: url,
      beforeSend: function () {
        showLoader("Loading Sales Order...");
      },
      success: function (response) {
        
        formatSalesOrderData(response.data);      
        const orderStatusMap = {
          'open':   'bg-green text-green-fg',
          'cancel': 'bg-orange text-orange-fg',
          'close':  'bg-red text-red-fg',
        };           
 
        renderStatusBadge("#sales_order_status", response.data.order_status, orderStatusMap);
        table.replaceData();      
 
       $("#sale_order_modal").modal("show");
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


function formatSalesOrderData(data) {  
  console.log("Sales Order View Data:", data);

  // =====================================================
  // Set main Select2 fields
  // =====================================================
  if (data.account_id) {
    $("#account_name").val(data.account?.name ?? "");
  }

  // =====================================================
  // Account Info
  // =====================================================

  $("#city").val(data.account?.city ?? "");

  $("#account_id_type").val(
    data.gst_type === GST_TYPE.INTERSTATE ? "INTERSTATE" : "LOCAL"
  );

  // =====================================================
  // Date Fields
  // =====================================================

  $("#purchase_order_number").val(data.purchase_order_number ?? "");

  $("#purchase_order_date").val(
    data.purchase_order_date ? formatDateToDMY(data.purchase_order_date) : ""
  );

  $("#delivery_date").val(
    data.delivery_date ? formatDateToDMY(data.delivery_date) : ""
  );

  $("#delivery_days").val(data.delivery_days ?? "");

  $("#due_date").val(data.due_date ? formatDateToDMY(data.due_date) : "");
  $("#sales_order_id").val(data.order_serial ?? "");

  $("#remarks").val(data.remarks ?? "");

  $("#sales_total_amount").text(formatIndianNumber(data.total_amount) ?? "");

  $("#sales_total_qty").text(
    data.total_quantity
      ? formatIndianNumber(data.total_quantity, DECIMALS.QTY)
      : "0.00"
  );

//   $("#sales_order_status").text(data.order_status.toUpperCase() ?? "");

  // =====================================================
  // Items Table
  // =====================================================
  let totalQty = 0;
  let remTotalQty = 0; 
    
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
// console.log("sales Order details",details)
  if (Array.isArray(data.details)) {
    data.details.forEach((detail, index) => {
     totalQty += detail.ordered_qty ? parseFloat(detail.ordered_qty) : 0;  
     remTotalQty += detail.remaining_qty ? parseFloat(detail.remaining_qty) : 0;  
      total= totalQty - remTotalQty;
        // console.log('sales Order details',detail)
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
            const qty = Number(detail.ordered_qty);
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
          const qty = Number(detail.ordered_qty);
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
      $("#received_qty").val(formatQty(total, 3));
      $("#remaining_qty").val(formatQty(remTotalQty, 3));
    });
  }
  
}

/**
 * Get value from DOM element
 * @param {HTMLElement} el 
 * @returns {string}
 */
function getFieldValue(el) {
    if (!el) return "";
    return $(el).val();
}

/**
 * Set value to DOM element and trigger change for Select2
 * @param {HTMLElement} el 
 * @param {any} value 
 */
function setFieldValue(el, value) {
    if (!el) return;
    var $el = $(el);
    $el.val(value);
    // If it's a select2 dropdown, trigger change to update UI
    if ($el.hasClass("select2-hidden-accessible")) {
        $el.trigger("change");
    }
}

/**
 * Normalize date string to YYYY-MM-DD
 * @param {string} dateStr 
 * @returns {string}
 */
function normalizeDate(dateStr) {
    if (!dateStr || dateStr === "-") return "";
  
     // If it matches DD-MM-YYYY, return as is
    if (/^\d{2}-\d{2}-\d{4}$/.test(dateStr)) return dateStr;    

    // If already YYYY-MM-DD, return as is
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr;

    if (typeof formatDateToYMD === "function") {
        return formatDateToYMD(dateStr);
    }
    return dateStr;
}