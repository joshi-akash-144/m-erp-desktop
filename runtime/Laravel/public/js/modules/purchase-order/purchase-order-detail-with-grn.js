let savedColWidths = {};
let table = null;
let footerData = {};
let currentFilter = { order_status: "open" };

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
];

const FILTER_KEY = "purchase_order_detail_filter";
const WIDTH_KEY = "purchase_order_detail_col_widths";

$(document).ready(function () {
    // select2 binding
    bindSelect2();

    // date input with validation
    new DateInput("#start_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#end_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    // Restore saved filters
    restoreFilters();

    // Tabulator
    // Account List
  table = new Tabulator("#purchase_order_detail_table", {
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

    ajaxURL: purchaseOrderDetailListUrl,
    ajaxParams: () => currentFilter,
    ajaxURLGenerator: (url, config, params) => {
            const normalizedFilter = { ...currentFilter };
            if (normalizedFilter.start_date) normalizedFilter.start_date = normalizeDate(normalizedFilter.start_date);
            if (normalizedFilter.end_date) normalizedFilter.end_date = normalizeDate(normalizedFilter.end_date);
            
            const qp = new URLSearchParams({ ...normalizedFilter });
            return `${url}?${qp}`;
        },
        ajaxResponse: (url,params, response) => {
            footerData = response.footerData || {};
            // If response is the standard {data: [...], ...} return just data if not paginated
            return response.data || response;
        },
    columns: getTableColumns(savedColWidths),
    rowFormatter: function(row) {
            var data = row.getData();
            var $rowElement = $(row.getElement()); // Wrap the DOM element with jQuery
            $rowElement.addClass('text-ellipsis');
            $rowElement.css('white-space', 'nowrap');
            $rowElement.css('overflow', 'hidden');
            $rowElement.css('text-overflow', 'ellipsis');
            if (data.rate === 'Total') {
                // Add Bootstrap classes using jQuery
                $rowElement.addClass("bg-secondary text-dark fw-bold border-top border-bottom border-dark");
            } else {
                // Remove classes for other rows (required for Virtual DOM stability)
                $rowElement.removeClass("bg-secondary text-white border-top border-bottom border-dark");
            }
        },

  });
   
  
    // Reload table with filter data natively
//       table.setPage(1);
//   });

    $("#filter_apply").on("click", function(e) {
        e.preventDefault();
        applyFilter();
    });
 
    $("#filter_clear").on("click", function (e) {
        e.preventDefault();
        clearFilter();
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
          text: `You want to explicitly close ${selectedIds.length} selected purchase order Details (s)?`,
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
                          Swal.fire("Error", "Failed to close purchase orders details.", "error");
                      }
                  }
              });
          }
      });
  });


function getTableColumns(savedColWidths) {
    return [
        {   title: "P. O. No.",
            field: "po_no", 
            width: savedColWidths.po_no ?? 80,
            headerSort:false,
            hozAlign: "center" 
            
        },
        {
            title: "P. O. Date.",
            field: "po_date",
            width:  110,
            headerSort:false,
            headerHozAlign: "right",
            hozAlign: "right",
            formatter: (cell) => formatDateToDMY(cell.getValue()),
        },
        {
            title: "Supplier Name",
            field: "supplier_name",
            width:  350,
            tooltip: true,
            formatter: function(cell) {
                const value = cell.getValue() || "";
                return `<span class="d-inline-block text-truncate" style="max-width: 100%;" title="${value}">${value}</span>`;
            }
        },
        {
            title: "Broker Name",
            field: "broker_name",
            width: savedColWidths.broker_name ?? 200,
            formatter: function(cell) {
                const value = cell.getValue() || "";
                // d-inline-block is often needed for span truncation
                return `<span class="d-inline-block text-truncate" style="max-width: 100%;">${value}</span>`;
            }
            //   formatter: (c) => formatDateToDMY(c.getValue()),
        },
        {
            title: "Items Name",
            field: "product_name",
            width: savedColWidths.product_name ?? 150,
            formatter: function(cell) {
                const value = cell.getValue() || "";
                // d-inline-block is often needed for span truncation
                return `<span class="d-inline-block text-truncate" style="max-width: 100%;">${value}</span>`;
            }
            //   formatter: (c) => renderDetailsList(c.getValue(), "item.name"),
        },

        {
            title: "Destination",
            field: "destination_name",
            width: 125,
            formatter: function(cell) {
                const value = cell.getValue() || "";
                // d-inline-block is often needed for span truncation
                return `<span class="d-inline-block text-truncate" style="max-width: 100%;">${value}</span>`;
            }
            // formatter: (c) => renderDetailsList(c.getValue(), "destination.name"),
        },
        {
            title: "Rate",
            field: "rate",
            width: savedColWidths.rate ?? 110,
            headerHozAlign: "right",
            headerSort: false,
            hozAlign: "right",
            // bottomCalc: () => "GrandTotal",
            // formatter: (c) => renderDetailsList(c.getValue(), "rate", true, DECIMALS.QTY),
            bottomCalcFormatter: function(cell) {                
                return `<span class="text-dark fw-bold">GrandTotal</span>`;
            } 
        },
        {
            title: "Order Qty",
            field: "qty",
            width: 130,
            headerHozAlign: "right",
            headerSort: false,
            hozAlign: "right",
            bottomCalc: () => footerData.order_qty_total || 0,
            bottomCalcFormatter: function(cell) {
                let val = cell.getValue();
                let formatted = formatIndianNumber(val);
                return `<span class="fw-bold text-dark">${formatted}</span>`;
            }
            // bottomCalc: function(values, data, calcParams) {        
            //     let total = values.reduce((acc, val) => acc + (parseFloat(val) || 0), 0);                
            //     const decimals = typeof DECIMALS !== 'undefined' ? DECIMALS.QTY : 3;                
            //     return total > 0 ? `<span class="fw-bold text-dark">${total.toFixed(decimals)}</span>` : "";
            // },
            // bottomCalcFormatter: "html"  
        },
        {
            title: "GRN Date",
            field: "grn_date",
            width:  115,
            headerSort: false,
            formatter: (cell) => formatDateToDMY(cell.getValue()),
        },

        {
            title: "Bill No.",
            field: "bill_no",
            width: savedColWidths.bill_no ?? 80,
            headerHozAlign: "center",
            headerSort: false,
            hozAlign: "center",
            // formatter: (c) => renderDetailsList(c.getValue(), "grn.reference_number"),
        },
        {
            title: "GRN No.",
            field: "grn_no",
            width: savedColWidths.grn_no ?? 90,
            headerHozAlign: "center",
            headerSort: false,
            hozAlign: "center",
            // formatter: (c) => renderDetailsList(c.getValue(), "grn.grn_serial", true, DECIMALS.QTY),
        },
        {
            title: "Vehicle No.",
            field: "vehicle_no",
            width: savedColWidths.vehicle_no ?? 120,
            headerHozAlign: "center",
            headerSort: false,
            hozAlign: "center",//vehicle_number
            // formatter: (c) => renderDetailsList(c.getValue(), "grn.vehicle_number"),
        },
        {
            title: "Bags",
            field: "bags",
            width: savedColWidths.bags ?? 60,
            headerHozAlign: "right",
            hozAlign: "right",
            headerSort: false,
            // formatter: (c) => renderDetailsList(c.getValue(), "bag_count", true, DECIMALS.QTY),
        },
        {
            title: "P. Qty.",
            field: "p_qty",
            width: savedColWidths.p_qty ?? 80,
            headerHozAlign: "right",
            hozAlign: "right",
            headerSort: false,
            // formatter: (c) => renderDetailsList(c.getValue(), "party_quantity", true, DECIMALS.QTY),
        },
        {
            title:"Rec. Qty.",
            field: "rec_qty",
            width: 130,
            headerHozAlign: "right",
            hozAlign: "right",
            headerSort: false,            
            bottomCalc: () => footerData.rec_qty_total || 0,    
            bottomCalcFormatter: function(cell) {               
                let val = cell.getValue();
                let formatted = formatIndianNumber(val);
                return `<span class="fw-bold text-success" style="font-size:1.1em;">${formatted}</span>`;
            } 
        },
        {
            title:"Rem. Qty.",
            field: "remaining_qty",
            width: 130,
            headerHozAlign: "right",
            headerSort: false,
            hozAlign: "right",          
            bottomCalc: () => footerData.rem_qty_total || 0.00,
            bottomCalcFormatter: function(cell) {
                let val = cell.getValue();
                let formatted = formatIndianNumber(val);
                
                // Use 'text-danger' for red, 'text-success' for green, or 'text-primary' for blue
                return `<span class="fw-bold text-danger">${formatted}</span>`;
            }
        },
        
        // Showing just before of Footer in table
        // {
        //     title: "Ordered Qty",
        //     field: "total_ordered_qty",
        //     width: saved?.total_ordered_qty || 120,
        //     hozAlign: "right",
        //     formatter: "money",
        //     formatterParams: { precision: 2 },
        //     bottomCalc: "sum",
        //     bottomCalcFormatter: "money",
        //     bottomCalcFormatterParams: { precision: 2 }
        // },
        // {
        //     title: "Received Qty",
        //     field: "total_received_qty",
        //     width: saved?.total_received_qty || 120,
        //     hozAlign: "right",
        //     formatter: "money",
        //     formatterParams: { precision: 2 },
        //     bottomCalc: "sum",
        //     bottomCalcFormatter: "money",
        //     bottomCalcFormatterParams: { precision: 2 }
        // },
        // {
        //     title: "Remaining Qty",
        //     field: "total_remaining_qty",
        //     width: saved?.total_remaining_qty || 120,
        //     hozAlign: "right",
        //     formatter: "money",
        //     formatterParams: { precision: 2 },
        //     bottomCalc: "sum",
        //     bottomCalcFormatter: "money",
        //     bottomCalcFormatterParams: { precision: 2 }
        // },
        
    ];
}

function bindSelect2() {
    const selectIdArray = ['#po_id', '#account_id', '#item_id', '#broker_id', '#destination_id', '#condition_id', '#order_status'];
    selectIdArray.forEach(element => {
        $(element).select2({
            theme: "bootstrap-5",
            allowClear: true,
            placeholder: "Select " + element.replace('#', '').replace('.', '').replace('_id', '') + " ...",
            width:null,
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


});


function applyFilter() {
    currentFilter = {};
 
    $.each(filterFields, function (i, id) {
        var el    = $("#" + id).get(0);
        var value = getFieldValue(el);
 
        if (value) currentFilter[id] = value;
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
 
    currentFilter = { order_status: 'open' };
    localStorage.removeItem(FILTER_KEY);
 
    var $statusDropdown = $("#order_status");
    if ($statusDropdown.length) {
        if ($statusDropdown.data("select2")) {
            $statusDropdown.val("open").trigger("change.select2");
        } else {
            $statusDropdown.val("open");
        }
    }
 
    if (table) table.setData();
}
 
/** Restore filters on page load */
function restoreFilters() {
    var saved = localStorage.getItem(FILTER_KEY);
    if (saved) {
        currentFilter = JSON.parse(saved);
    }
 
    // Always default to 'open' if not explicitly selected/saved
    if (!currentFilter['order_status']) {
        currentFilter['order_status'] = 'open';
    }
 
    $.each(currentFilter, function (id, value) {
        var el = $("#" + id).get(0);
        setFieldValue(el, value);
    });
}

function getFieldValue(el) {
    if (!el) return "";
    return $(el).val();
}

function setFieldValue(el, value) {
    if (!el) return;
    var $el = $(el);
    $el.val(value);
    // If it's a select2 dropdown, trigger change to update UI
    if ($el.hasClass("select2-hidden-accessible")) {
        $el.trigger("change");
    }
}

function normalizeDate(dateStr) {
    if (!dateStr || dateStr === "-") return "";
    
    // If already YYYY-MM-DD, return as is
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr;

    if (typeof formatDateToYMD === "function") {
        return formatDateToYMD(dateStr);
    }
    return dateStr;
}

  document.addEventListener("click", function (e) {
    const action = e.target.closest(".dropdown-item");
    if (!action) return;
    console.log(action);

    const type = action.dataset.type; // print / export
    const route = action.dataset.route;
    const format = action.dataset.format;
    // console.log("type", type);

    if (!type) return;
    // const opID = document.getElementById("po_id-ts-control").value();
    switch (type) {
      case "print":
        console.log(currentFilter);
        printReport(route, { format: format, currentFilter });
        break;

      case "excel":
        downloadExcel(route, { currentFilter });
        break;
    }
  });