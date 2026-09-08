let table;
let currentFilter = { order_status: "open" };
let currentPermissions = {};
let savedColWidths = {};
let totalFilteredRecords = 0;
let grandTotalRecords = 0;


const FILTER_KEY = "purchase_order_filter";
const WIDTH_KEY = "purchase_order_col_widths";
const PAGE_SIZE_KEY = "purchase_order_page_size";
const PAGE_NUM_KEY  = "purchase_order_page_num";

const filterFields = [
    "po_id",
    "start_date",
    "end_date",
    "account_id",
    "item_id",
    "broker_id",
    "condition_id",
    "destination_id",
    "order_status",
    "due_status",    
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
  table = new Tabulator("#purchase_order_table", {
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

    ajaxURL: purchaseOrderListUrl,
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

  // Setup Column Visibility Dropdown
  let colVisInitialized = false;
  const initColVisMenu = function () {
    if (colVisInitialized) return;
    const menu = document.getElementById("column-visibility-menu");
    if (menu) {
      menu.innerHTML = '';
      table.getColumns().forEach(function (col) {
        const colDef = col.getDefinition();
        if (colDef.field && colDef.title) {
          const isChecked = col.isVisible() ? 'checked' : '';
          const li = document.createElement("li");
          li.innerHTML = `
            <div class="dropdown-item py-1">
              <div class="form-check m-0">
                <input class="form-check-input col-vis-cb" type="checkbox" id="col_vis_${colDef.field}" data-field="${colDef.field}" ${isChecked}>
                <label class="form-check-label w-100" style="cursor: pointer; font-size: 13px;" for="col_vis_${colDef.field}">
                  ${colDef.title}
                </label>
              </div>
            </div>
          `;
          menu.appendChild(li);
        }
      });

      menu.querySelectorAll('.col-vis-cb').forEach(function (cb) {
        cb.addEventListener('change', function () {
          const field = this.dataset.field;
          const column = table.getColumn(field);
          if (column) {
            if (this.checked) {
              column.show();
            } else {
              column.hide();
            }
          }
        });
      });
      colVisInitialized = true;
    }
  };

  table.on("tableBuilt", initColVisMenu);

  // Fallback if tableBuilt already fired (Tabulator 5+ often finishes sync build before on() binds)
  if (table.getColumns().length > 0) {
    initColVisMenu();
  }

  // Handle Close Selected POs
  $("#closeSelectedBtn").on("click", function() {
      const selectedIds = table.getRows()
                          .map(r => r.getData())
                          .filter(data => data.selected)
                          .map(data => data.id);
      
      if(selectedIds.length === 0) return;

      Swal.fire({
          title: "Are you sure?",
          text: `You want to explicitly close ${selectedIds.length} selected purchase order(s)?`,
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#d33",
          cancelButtonColor: "#3085d6",
          confirmButtonText: "Yes, close them!"
      }).then((result) => {
          if (result.isConfirmed) {
              $.ajax({
                  url: "/vouchers/purchase-orders/close-multiple",
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
                          Swal.fire("Error", "Failed to close purchase orders.", "error");
                      }
                  }
              });
          }
      });
  });

  // Handle Edit PO
  $(document).on("click", ".edit", function() {
      const id = $(this).data("id");
      window.location.href = purchaseOrderEditUrl.replace(':id', id);
  });

   $("#filter_apply").on("click", function (e) {
        e.preventDefault();
        applyFilter();
    });
   $("#filter_clear").on("click", function (e) {
        e.preventDefault();
        clearFilter();
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
    }
    ,

    // =======================
    // Order No.
    // =======================
    {
      title: "P.O. No",
      field: "order_serial",
      width: 100,
      headerSort: true,
      hozAlign: "center",
      headerHozAlign: "center",
      sorter: "string",
      resizable: true,
    },

    // =======================
    // Order Date
    // =======================
    {
      title: "Order Date",
      field: "order_date",
      width: 120,
      headerSort: false,
      hozAlign: "center",
      headerHozAlign: "center",
      sorter: "date",
      resizable: true,
      formatter: (cell) => formatDateToDMY(cell.getValue()),
    },

    // =======================
    // Due Date
    // =======================
    {
      title: "Last Date",
      field: "due_date",
      width: 120,
      headerSort: false,
      hozAlign: "center",
      headerHozAlign: "center",
      sorter: "date",
      resizable: true,
      formatter: (cell) => {
        const dueDate = cell.getValue();
        if (!dueDate) return "";

        const today = new Date();
        const parsedDueDate = new Date(dueDate);

        const formatted = formatDateToDMY(dueDate);

        // Check if overdue (ignore time part)
        const isOverdue =
          parsedDueDate.setHours(0, 0, 0, 0) < today.setHours(0, 0, 0, 0);

        return `<span style="color: ${isOverdue ? "red" : "inherit"};">
      ${formatted}
    </span>`;
      },
    },
    // =======================
    // Supplier
    // =======================
    {
      title: "Supplier",
      field: "account.name",
      width: 300,
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
    //   title: "Supplier City",
    //   field: "account.city",
    //   width: savedColWidths.supplier ?? 150,
    //   sorter: "string",
    //   headerSort: false,
    //   resizable: true,
    // },
    {
      title: "Broker",
      field: "broker.name",
      width: 200,
      sorter: "string",
      headerSort: false,
      resizable: true,
      formatter: (cell) => {
        const brokerName = cell.getValue();
        // Access the full row data object
        const rowData = cell.getData();
        const city = rowData.broker?.city || "";

        if (brokerName == null) return "";

        const formattedName = String(brokerName)
          .replace(/_/g, " ")
          .toLowerCase()
          .replace(/\b\w/g, (ch) => ch.toUpperCase());

        if (city) {
          return `<span class="text-truncate">${formattedName} (${city.toUpperCase()}) </span>`;
        } else {
          return `<span class="text-truncate">${formattedName} </span>`;
        }
      },
    },
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
      title: "Contract No",
      field: "contract_number",
      width: 70,
      sorter: "string",
      headerSort: false,
      resizable: true,
      formatter: (cell) => {
        const val = cell.getValue();
        return val ? val : `<span class="text-muted">--</span>`;
      },
    },

    {
      title: "Items",
      field: "details_items",
      width: 120,
      headerSort: false,
      resizable: true,
      formatter: (c) => {
        const htmlString = renderDetailsList(c.getData().details || [], "item.name");
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
            return `<div class="text-truncate" style="max-width: 100%;" title="${item.replace(/"/g, "&quot;")}">
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
      field: "destination.name",
      width: 110,
      sorter: "string",
      headerSort: false,
      resizable: true,     
      formatter: function(cell) {
          const value = cell.getValue() || "";
          // d-inline-block is often needed for span truncation
          return `<span class="d-inline-block text-truncate" style="max-width: 100%;">${value}</span>`;
      }

    },

    
    {
      title: "Condition",
      field: "details_condition",
      width: 120,
      headerSort: false,
      resizable: true,
      // formatter: (cell) => {
      //   const details = cell.getValue() || [];
      //   return renderDetailsList(details, "condition.name").toUpperCase();
      // },
      formatter: (c) => {
        const htmlString = renderDetailsList(c.getData().details || [], "condition.name");
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
    // Inclusive Rate
    // =======================
    {
      title: "Incl. Rate",
      field: "details_inclusive_rate",
      width: 100,
      headerSort: false,
      resizable: true,
      hozAlign: "right",
      headerHozAlign: "right",
      formatter: (cell) => {
        const details = cell.getData().details || [];
        return renderDetailsList(
          details,
          "inclusive_rate",
          true,
          false,
          typeof DECIMALS !== "undefined" ? DECIMALS.INCLUSIVE_RATE : 2
        );
      },
    },

    // =======================
    // Rate
    // =======================
    {
      title: "Rate",
      field: "details_rate",
      width: 100,
      headerSort: false,
      resizable: true,
      hozAlign: "right",
      headerHozAlign: "right",
      formatter: (cell) => {
        const details = cell.getData().details || [];
        return renderDetailsList(
          details,
          "rate",
          true,
          false,
          typeof DECIMALS !== "undefined" ? DECIMALS.RATE : 2
        );
      },
    },

   

    // =======================
    // Quantity Summary (Ordered / Received / Pending)
    // =======================
    {
      title: "Qty",
      field: "details_ordered_qty",
      width: 120,
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
      title: "Rec.Qty",
      field: "details_received_qty",
      width: 120,
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
        return total > 0 ? `<span class="fw-bold text-danger">${total.toFixed(typeof DECIMALS !== 'undefined' ? DECIMALS.QTY : 3)}</span>` : "";
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
      title: "Rec. %",
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
              <div class="ms-2 text-end text-muted" style="font-size: 0.75rem; min-width: 35px;">${formatNumber(printPercent, 2)}%</div>
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
      vertAlign: "middle",
      headerHozAlign: "center",
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
      
        return `<div class="d-flex align-items-center justify-content-center h-100 w-100">
                  <span class="badge ${badgeClass} fw-semibold">${upperCase(orderStatus)}</span>
                </div>`;
      },
    },

    // =======================
    // Actions
    // =======================
    {
      title: "Actions",
      field: "actions",
      width: 70,
      hozAlign: "center",
      vertAlign: "middle",
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
        // const deleteIcon = getIcon("delete");

        let actions = '<div class="d-flex gap-2 align-items-center justify-content-center h-100 w-100">';

        // if (canView) {
        //   actions += `
        //       <span class="erp-btn-icon view" data-id="${row.id}" title="View Order">
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
    const selectIdArray = ['#po_id', '#account_id', '#item_id', '#broker_id', '#destination_id', '#condition_id', '#order_status', '#due_status'];
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
        if (id === "order_status") {
            setFieldValue(el, "open");
        } else {
            setFieldValue(el, "");
        }
    });
 
    currentFilter = { order_status: "open" };
    localStorage.setItem(FILTER_KEY, JSON.stringify(currentFilter));
 
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

function editPurchaseOrder(id) {
    window.location.href = "/purchase-order/" + id + "/edit";
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
        printReport(route, { format: format, currentFilter: currentFilter });
        break;

      case "excel":
        downloadExcel(route, { currentFilter: currentFilter });
        break;
    }
  });
 

  // View Purchase Order
  $(document).on("click", ".view", function (event) {
    const purchaseOrderId = $(this).data("id");
    const url = purchaseOrderViewUrl.replace(":id", purchaseOrderId);
    $.ajax({
      url: url,
      beforeSend: function () {
        showLoader("Loading purchase Order...");
      },
      success: function (response) {
        
        formatPurchaseOrderData(response);      
        const orderStatusMap = {
          'open':   'bg-green text-green-fg',
          'cancel': 'bg-orange text-orange-fg',
          'close':  'bg-red text-red-fg',
        };           
 
        if(response.data.order_status == 'due'){
           renderStatusBadge("#order_status_view", "open", orderStatusMap);
        }else{
          renderStatusBadge("#order_status_view", response.data.order_status, orderStatusMap);
        }   

        table.replaceData();      
        $("#purchase_order_modal").modal("show");
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


 function formatPurchaseOrderData(data) {  
    // =====================================================
    // Date Fields
    // =====================================================
    $("#order_number").val(data.data.order_serial);

    $("#order_date").val(data.data.order_date ? formatDateToDMY(data.data.order_date) : "");

    $("#due_date").val(data.data.due_date ? formatDateToDMY(data.data.due_date) : "");

    $("#contract_number").val(data.data.contract_number ?? "");

    $("#delivery_days").val(data.data.delivery_days ?? "");

    $("#broker_name").val(data.data.broker.name ?? "");

    $("#supplier_name").val(data.data.account.name ?? "");

    $("#city").val(data.data.account.city ?? " ");
  
    $("#supplier_type").val(data.data.account.gst_type === GST_TYPE.LOCAL ? "LOCAL" : "INTERSTATE");
    
    $("#destination_name").val(data.data.destination.name ?? "");
    
  
    $("#total_qty").text(formatQty(data.data.total_quantity, 3) ?? "");

    $("#total_amount").text(data.data.total_amount ? formatIndianNumber(data.data.total_amount, DECIMALS.AMOUNT): "0.00" );
    $("#remarks").val(data.data.remarks ?? "");

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
    if (Array.isArray(data.data.details)) {
      data.data.details.forEach((detail, index) => {  
        totalQty += detail.ordered_qty ? parseFloat(detail.ordered_qty) : 0;  
        remTotalQty += detail.remaining_qty ? parseFloat(detail.remaining_qty) : 0;  
        totalQty = totalQty - remTotalQty;
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

        row.dataset.row = index + 1;
        row.dataset.rowId = index + 1;

        // Map fields
        const selectors = [
          ["span.item_id", "item_id", "span"],
          ["span.unit_name", "unit_name", "span"],
          ["span.purchase_order_serial", "purchase_order_serial", "span"],
          ["span.purchase_order_id", "purchase_order_id", "span"],
          ["span.purchase_order_item_id", "purchase_order_item_id", "span"],
          ["span.condition_id", "condition_id", "span"],
          ["span.destination_id", "destination_id", "span"],
          ["span.cgst_rate", "cgst_rate", "span"],
          ["span.sgst_rate", "sgst_rate", "span"],
          ["span.igst_rate", "igst_rate", "span"],
          ["span.bag_count", "bag_count", "span"],
          ["span.bag_count", "bag_count", "span"],
          ["span.party_quantity", "party_quantity", "span"],
          ["span.quantity", "quantity", "span"],
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
              el.textContent = detail.item.name ?? "";
            } else if (field === "destination_id") {
              el.textContent = detail.destination?.name ?? "";
            } else if (field === "condition_id") {
              el.textContent = detail.condition?.name ?? "";
            } else if (field === "unit_name") {
              el.textContent = detail.item.unit.name ?? "";
            } else {
              el.textContent = detail[field] ?? "";
            }
          } else if (field === "quantity") {
            const qty = Number(detail.ordered_qty);
            el.value = isNaN(qty) ? "" : qty.toFixed(DECIMALS.QTY);
          } else if (field === "party_quantity") {
            const partyQty = Number(detail.party_quantity);
            el.value = isNaN(partyQty) ? "" : partyQty.toFixed(DECIMALS.QTY);
          } else {
            el.value = detail[field] ?? "";
          }
        });
      });    
    $("#received_qty").val(formatQty(totalQty, 3));
    $("#remaining_qty").val(formatQty(remTotalQty, 3));
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
document.addEventListener('DOMContentLoaded', function () {
    $('#urgent_email').on('click', function(e) {
        e.preventDefault();
        
        let selectedRows = [];
        if (typeof table !== 'undefined' && table.getRows) {
            selectedRows = table.getRows()
                            .map(r => r.getData())
                            .filter(data => data.selected);
        }

        if (!selectedRows.length) {
            return Swal.fire({
                icon: 'warning',
                title: 'No Selection',
                text: 'Please select at least one purchase order.',
                timer: 2000,
                showConfirmButton: false
            });
        }

        let selectedIds = selectedRows.map(data => data.id);
        let accountIds = [...new Set(selectedRows.map(data => data.account_id))];

        if (accountIds.length === 1) {
            const $btn = $(this);
            const originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Loading...');

            // Open Modal for single supplier
            $('#urgent_email_to').val('');
            $('#urgent_email_subject').val('');
            $('#urgent_email_cc').empty().trigger('change');
            if (window.urgentEditorReady && hugerte.get('urgent_email_message')) {
                hugerte.get('urgent_email_message').setContent('');
            }
            $('#urgent_editor_loader').css('display', 'flex');
            $('#urgentEmailModal').modal('show');

            $.ajax({
                url: urgentEmailPreviewUrl,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    purchase_order_ids: selectedIds
                },
                success: function(response) {
                    if (response.success && response.data) {
                        const data = response.data;
                        $('#urgent_email_to').val(data.to || '');
                        $('#urgent_email_subject').val(data.subject || '');
                        
                        // CC — rebuild options from accounts list
                        const ccSelect = $('#urgent_email_cc');
                        ccSelect.empty();
                        if (data.cc_accounts && data.cc_accounts.length) {
                            data.cc_accounts.forEach(function (acc) {
                                const label = acc.name + ' <' + acc.email + '>';
                                ccSelect.append(new Option(label, acc.email, false, false));
                            });
                        }
                        ccSelect.val(null).trigger('change');

                        urgentSetEditorContent(data.body || '');

                        $('#urgent_modal_po_ids').val(selectedIds.join(','));
                    } else {
                        $('#urgent_editor_loader').hide();
                        $('#urgentEmailModal').modal('hide');
                        Swal.fire('Error', response.message || 'Failed to generate preview.', 'error');
                    }
                },
                error: function(xhr) {
                    $('#urgent_editor_loader').hide();
                    $('#urgentEmailModal').modal('hide');
                    Swal.fire('Error', 'An error occurred while generating the preview.', 'error');
                },
                complete: function() {
                    $btn.html(originalHtml).prop('disabled', false);
                }
            });
        } else {
            // Bulk send for multiple suppliers
            Swal.fire({
                title: 'Send Bulk Urgent Email?',
                text: 'You have selected purchase orders from multiple suppliers. This will send separate emails to each supplier. Do you want to proceed?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Send it!',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    const $btn = $('#urgent_email');
                    const originalHtml = $btn.html();
                    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Sending...');

                    $.ajax({
                        url: urgentEmailSendUrl,
                        type: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            purchase_order_ids: selectedIds
                        },
                        success: function(response) {
                            if(response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sent!',
                                    text: response.message || 'Email sent successfully.',
                                    timer: 2000,
                                    confirmButtonText: "Ok",
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                });
                                if(typeof table !== 'undefined') table.deselectRow();
                            } else {
                                Swal.fire('Error', response.message || 'Failed to send email.', 'error');
                            }
                        },
                        error: function(xhr) {
                            let msg = 'An error occurred while sending the emails.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            Swal.fire('Error', msg, 'error');
                        },
                        complete: function() {
                            $btn.html(originalHtml).prop('disabled', false);
                        }
                    });
                }
            });
        }
    });

    // Handle the send button inside the preview modal
    $('#urgent_email_send_btn').on('click', function(e) {
        e.preventDefault();

        let poIds = $('#urgent_modal_po_ids').val();
        if (poIds) {
            poIds = poIds.split(',');
        } else {
            poIds = [];
        }
        
        if (!poIds.length) {
            Swal.fire({ icon: "warning", title: "Warning", text: "No purchase order selected.", confirmButtonText: "OK" });
            return;
        }

        const toEmail = $('#urgent_email_to').val();
        const ccEmails = $('#urgent_email_cc').val() || [];
        const subject = $('#urgent_email_subject').val();
        
        let bodyHtml = '';
        if (window.urgentEditorReady && hugerte.get('urgent_email_message')) {
            bodyHtml = hugerte.get('urgent_email_message').getContent();
        } else {
            bodyHtml = $('#urgent_email_message').val();
        }

        if (!toEmail) {
            Swal.fire({ icon: "warning", title: "Warning", text: "Recipient email is required.", confirmButtonText: "OK" });
            return;
        }

        if (!subject) {
            Swal.fire({ icon: "warning", title: "Warning", text: "Subject is required.", confirmButtonText: "OK" });
            return;
        }

        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Sending...');

        $.ajax({
            url: urgentEmailSendUrl,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                purchase_order_ids: poIds,
                to: toEmail,
                cc: ccEmails,
                subject: subject,
                body: bodyHtml,
                is_custom: 1
            },
            success: function(response) {
                if (response.success) {
                    $('#urgentEmailModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Sent!',
                        text: response.message || 'Email sent successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    if(typeof table !== 'undefined') table.deselectRow();
                } else {
                    Swal.fire('Error', response.message || 'Failed to send email.', 'error');
                }
            },
            error: function(xhr) {
                let msg = 'An error occurred while sending the email.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire('Error', msg, 'error');
            },
            complete: function() {
                $btn.html(originalHtml).prop('disabled', false);
            }
        });
    });
});

// HugeRTE for single-party compose modal
var urgentHugerteInitialized      = false;
window.urgentEditorReady          = false;
window.urgentEditorPendingContent = null;

function urgentSetEditorContent(html) {
  if (window.urgentEditorReady) {
    var ed = hugerte.get('urgent_email_message');
    if (ed) ed.setContent(html);
    $('#urgent_editor_loader').hide();
  } else {
    window.urgentEditorPendingContent = html;
  }
}

$('#urgentEmailModal').on('shown.bs.modal', function () {
  if (urgentHugerteInitialized) return;
  urgentHugerteInitialized = true;

  hugerte.init({
    selector    : '#urgent_email_message',
    height      : 320,
    menubar     : false,
    base_url    : typeof hugertePath !== 'undefined' ? hugertePath : '',
    suffix      : '.min',
    plugins     : 'lists link code',
    toolbar     : 'bold italic underline | bullist numlist | alignleft aligncenter alignright | link | code',
    content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
    setup: function (editor) {
      editor.on('init', function () {
        window.urgentEditorReady = true;
        if (window.urgentEditorPendingContent !== null) {
          editor.setContent(window.urgentEditorPendingContent);
          window.urgentEditorPendingContent = null;
          $('#urgent_editor_loader').hide();
        }
      });
    },
  });
});

// Init Select2 for CC on page load
(function initUrgentEmailModal() {
  $('#urgent_email_cc').select2({
    dropdownParent: $('#urgentEmailModal'),
    theme: 'bootstrap-5',
    placeholder: 'Please select',
    allowClear: true,
    tags: true,
    tokenSeparators: [','],
    createTag: function (params) {
      var term = $.trim(params.term);
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(term)) return null;
      return { id: term, text: term, newTag: true };
    },
  });
})();

