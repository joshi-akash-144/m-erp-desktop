let table; 
let currentFilter = { product_status: "all" };
let currentPermissions = {};
let savedColWidths = {};
const MAX_RECORDS = 150;

const FILTER_KEY = "moisture_filter";
const WIDTH_KEY = "moisture_col_widths";

const filterFields = [
    "start_date",
    "end_date",
    "account_id",
    "item_id",
    "grn_number",
];

/** Set field value safely (Select2 / normal input) */
function setFieldValue(el, value) {
    if (!el) return;
    var $el = $(el);
    $el.val(value);
    // If it's a select2 dropdown, trigger change to update UI
    if ($el.hasClass("select2-hidden-accessible")) {
        $el.trigger("change");
    }
}

/** Read field value safely */
function getFieldValue(el) {
    if (!el) return "";
    return $(el).val();
}

function getTableColumns(savedColWidths = {}) {
  return [
    {
        title: "Sr No.<br>GRN No.",
        field: "grn_serial",
        width: savedColWidths.grn_number ?? 90,
        headerSort: false,
        hozAlign: "center",
        headerHozAlign: "center",
        formatter: function(cell) {
            let row = cell.getRow();
            let position = row.getPosition(true);
            let data = row.getData();
            let displayVal = `${data.grn_serial ?? "-"}`;
            return `
                <div class="text-center lh-1 p-2">
                    <div class="text-dark">${position}</div>
                    <div class="fw-bold" style="color: #a14e1eff">${displayVal}</div>
                </div>
            `;
        },
        bottomCalc: "count",
        bottomCalcFormatter: function(cell) {                
            return `<span class="text-dark fw-bold">Entries</span>`;
        }
    },
    {
      title: "Product <br>In / Out",
      field: "in_out_status",
      width: savedColWidths.item_in_out ?? 80,
      headerSort: false,
      hozAlign: "center",
      headerHozAlign: "center",
      formatter: function(cell) {
          const val = cell.getValue();
          return `<div class="d-flex justify-content-center align-items-center h-100">${
            val == "in" ?
            `<span class="badge bg-success-subtle text-success fw-bold">In</span>` :
            val == "out" ?
            `<span class="badge bg-danger-subtle text-danger fw-bold">Out</span>` :
            "-"
          }</div>`;
      },
      bottomCalc: "count",
      bottomCalcFormatter: function(cell) {                
            return `<span class="text-dark fw-bold">${cell.getValue()}</span>`;
        }
    },
    {
      title: "Vehicle No",
      field: "vehicle_number",
      width:  130,
      headerSort: false,
      hozAlign: "center",
      formatter: function(cell) {
          return `<span class="fw-bold">${cell.getValue() || '-'}</span>`;
      }
    },
    {
      title: "Particular / Product",
      field: "account.name",
      width: savedColWidths.party_name_and_item_name ?? 320,
      headerSort: false,
      formatter: function(cell) {
            let data = cell.getRow().getData();
            let party_name = data.account?.name || '-'; 
            let item_name = data.item?.name || '-';
            return `
                <div class="lh-1 p-2 overflow-hidden">
                    <div class="fw-bold text-truncate" title="${party_name}">${party_name}</div>
                    <small class="d-block text-truncate mt-1" title="${item_name}">${item_name}</small>
                </div>
            `;
        }
    },
    {
      title: "Party Destination",
      field: "party_destination.name",
      width: savedColWidths.party_destination ?? 140,
      headerSort: false,
      hozAlign: "left",
      headerHozAlign: "left",
      formatter: function(cell) {
          let data = cell.getRow().getData();
          return `<div class="lh-1 p-2">${data.party_destination?.name || '-'}</div>`;
      }
    },
    {
      title: "Cha.No.<br>Cha.Weight",
      field: "challan_weight", 
      width: savedColWidths.challan_no_and_challan_weight ?? 150,
      headerSort: false,
      hozAlign: "right",
      headerHozAlign: "right",
      formatter: function(cell) {
            let data = cell.getRow().getData();
            let challan_no = '-';
            if (data.in_out_status === 'in') {
                challan_no = data.reference_number || '-';
            } else if (data.in_out_status === 'out') {
                challan_no = data.grn_serial || '-';
            }
            let m = data.moisture;
            let oldChallan = m && m.challan_weight ? parseFloat(m.challan_weight).toFixed(3) : '0.000';
            let newChallan = m && m.new_challan_weight ? parseFloat(m.new_challan_weight).toFixed(3) : '0.000';
            return `
                <div class="lh-1 p-2">
                    <div class="fw-bold">${challan_no}</div>
                    <div class="mt-1">Old: <span class="fw-bold">${oldChallan}</span></div>
                    <div class="mt-1">New: <span class="fw-bold">${newChallan}</span></div>
                </div>
            `;
        },
        bottomCalcFormatter: function(cell) {       
            return `<span class="text-dark fw-bold">Total</span>`;
        }
    },
    {
      title: "Bags",
      field: "bag_count",
      width: savedColWidths.bags ?? 80,
      headerSort: false,
      hozAlign: "right",
      headerHozAlign: "right",
      formatter: function(cell) {
            let bags = cell.getValue() || 0; 
            return `<div class="lh-1 p-2">${bags}</div>`;
        },
        bottomCalc:"sum",
        bottomCalcFormatter: function(cell) {       
            let total = cell.getValue();         
            return `<span class="text-dark fw-bold">${total ? total : '0'}</span>`;
        }
    },
    {
      title: "Godown",
      field: "destination.name",
      width: savedColWidths.party_destination ?? 140,
      headerSort: false,
      hozAlign: "center",
      headerHozAlign: "center",
      formatter: function(cell) {
          let data = cell.getRow().getData();
          return `<div class="lh-1 p-2">${data.destination?.name || '-'}</div>`;
      }
    },
    {
      title: "Unit",
      field: "godowns.godown_name",
      width: savedColWidths.godown ?? 100,
      headerSort: false,
      hozAlign: "center",
      headerHozAlign: "center",
      formatter: function(cell) {
          let data = cell.getRow().getData();
          return `<div class="lh-1 p-2">${data.godowns?.godown_name || '-'}</div>`;
      }
    },
    {
      title: "Date",
      field: "grn_date",
      width: savedColWidths.grn_date ?? 110,
      headerSort: false,
      hozAlign: "center",
      headerHozAlign: "center",
      formatter: function (cell) {
          const grnDate = cell.getRow().getData().grn_date;
          const dcDate = cell.getRow().getData().dc_date;
            if (grnDate) {
                return `<div class="lh-1 p-2">${normalizeDate(grnDate)}</div>`;
            } else if (dcDate) {
                return `<div class="lh-1 p-2">${normalizeDate(dcDate)}</div>`;
            } else {
                return '-';
            }
      }
    },
    {
      title: "Date IN<br>Date OUT",
      field: "date_in",
      width: 120,
      headerHozAlign: "center",
      hozAlign: "center",
      headerSort: false,
      formatter: function(cell) {
            let data = cell.getRow().getData();
            let date_in = data.date_in ? normalizeDate(data.date_in) : '-'; 
            let date_out = data.date_out ? normalizeDate(data.date_out) : '-';
            return `
                <div class="align-items-center lh-1 p-2">
                    <div>${date_in}</div>
                    <div class="mt-1">${date_out}</div>
                </div>
            `;
        }
    },
    {
      title: "Time <br> IN/OUT",
      field: "time_in",
      width: 100,
      headerSort: false,
      headerHozAlign: "center",
      hozAlign: "center",
      formatter: function(cell) {
            let data = cell.getRow().getData();
            let time_in = data.time_in || '-'; 
            let time_out = data.time_out || '-';
            return `
                <div class="align-items-center lh-1 p-2">
                    <div>${time_in}</div>
                    <div class="mt-1">${time_out}</div>
                </div>
            `;
        }
    },

    {
      title: "P.Qty",
      field: "p_qty",
      width: savedColWidths.p_qty ?? 150,
      hozAlign: "right",
      headerHozAlign: "right",
      headerSort: false,
      formatter: function(cell) {
          let qty = cell.getValue() ? parseFloat(cell.getValue()).toFixed(3) : '0.000';
          return `
            <div class="lh-1 p-2">
                <div>Old: <span class="fw-bold">${qty}</span></div>
                <div class="mt-1">New: <span class="fw-bold">${qty}</span></div>
            </div>
          `;
      }
    },
    {
      title: "Rate",
      field: "rate",
      width: savedColWidths.rate ?? 100,
      hozAlign: "right",
      headerHozAlign: "right",
      headerSort: false,
      formatter: (cell) => `<div class="lh-1 p-2">${cell.getValue() ? parseFloat(cell.getValue()).toFixed(2) : '0.00'}</div>`
    },
    {
      title: "Weight",
      field: "gross_weight",
      width: savedColWidths.weight ?? 190,
      headerSort: false,
      hozAlign: "right",
      headerHozAlign: "right",
      formatter: function(cell) {
            let m = cell.getRow().getData().moisture;
            let oldGross = m && m.gross_weight ? parseFloat(m.gross_weight).toFixed(3) : '0.000';
            let oldTare = m && m.tare_weight ? parseFloat(m.tare_weight).toFixed(3) : '0.000';
            let newGross = m && m.new_gross_weight ? parseFloat(m.new_gross_weight).toFixed(3) : '0.000';
            let newTare = m && m.new_tare_weight ? parseFloat(m.new_tare_weight).toFixed(3) : '0.000';
            return `
                <div class="text-end lh-1 p-2">
                    <div>Old Gross: <span class="fw-bold">${oldGross}</span></div>
                    <div class="mt-1">Old Tare: <span class="fw-bold">${oldTare}</span></div>
                    <div class="mt-2">New Gross: <span class="fw-bold">${newGross}</span></div>
                    <div class="mt-1">New Tare: <span class="fw-bold">${newTare}</span></div>
                </div>
            `;
        }
    },
    {
      title: "Net-Weight",
      field: "net_weight",
      width: savedColWidths.net_weight ?? 120,
      hozAlign: "right",
      headerHozAlign: "right",
      headerSort: false,
      formatter: function(cell) {
            let m = cell.getRow().getData().moisture;
            let oldNet = m && m.net_weight ? parseFloat(m.net_weight) : 0;
            let newNet = m && m.new_net_weight ? parseFloat(m.new_net_weight) : 0;
            return `
                <div class="text-end lh-1 p-2 h-100 d-flex flex-column justify-content-center">
                    <div class="fw-bold mb-3">Old: ${oldNet}</div>
                    <div class="fw-bold mt-2">New: ${newNet}</div>
                </div>
            `;
      }
    },
    {
      title: "Remaining",
      field: "remaining",
      width: 170,
      hozAlign: "right",
      headerHozAlign: "right",
      headerSort: false,
      formatter: function(cell) {
            let m = cell.getRow().getData().moisture;
            let oldNet = m && m.net_weight ? parseFloat(m.net_weight) : 0;
            let newNet = m && m.new_net_weight ? parseFloat(m.new_net_weight) : 0;
            let diff = oldNet - newNet;
            return `
                <div class="text-end lh-1 p-2 h-100 d-flex flex-column justify-content-end pb-2">
                    <div class="fw-bold">Remaining:${diff}</div>
                </div>
            `;
      },
      bottomCalc: function(values, data, calcParams) {
          let sum = 0;
          data.forEach(row => {
              let m = row.moisture;
              let oldNet = m && m.net_weight ? parseFloat(m.net_weight) : 0;
              let newNet = m && m.new_net_weight ? parseFloat(m.new_net_weight) : 0;
              sum += (oldNet - newNet);
          });
          return sum;
      },
      bottomCalcFormatter: function(cell) {
            let total = cell.getValue();
            return `<span class="text-dark fw-bold">${total ? parseFloat(total).toFixed(2) : '0.00'}</span>`;
      }
    },
    {
      title: "Without Bag Weight",
      field: "net_weight_wt_bag",
      width: savedColWidths.without_bag_weight ?? 160,
      hozAlign: "right",
      headerHozAlign: "right",
      headerSort:false,
      formatter: function(cell) {
            let m = cell.getRow().getData().moisture;
            let oldBag = m && m.net_weight_wt_bag ? parseFloat(m.net_weight_wt_bag) : 0;
            let newBag = m && m.new_net_weight_wt_bag ? parseFloat(m.new_net_weight_wt_bag) : 0;
            return `
                <div class="text-end lh-1 p-2 h-100 d-flex flex-column justify-content-center">
                    <div class="fw-bold mb-3">Old: ${oldBag}</div>
                    <div class="fw-bold mt-2">New: ${newBag}</div>
                </div>
            `;
      }
    },
    {
      title: "Created By",
      field: "created_by",
      width: savedColWidths.created_by || 150,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      resizable: true,
      formatter: (cell) => {
        const creatorName = cell.getRow().getData().moisture?.creator?.name || null;
        return creatorName
          ? `<span class="badge bg-cyan-lt ">${creatorName}</span>`
          : `<span class="badge bg-danger-lt">--</span>`;
      },
    },
  ];

}

function bindSelect2() {
    const selectIdArray = ['#account_id', '#item_id', '#godown_id', '#godown_unit_id', '#product_status','#print_type','#challan_number'];
    selectIdArray.forEach(element => {
        $(element).select2({
            theme: "bootstrap-5",
            allowClear: true,
            placeholder: "Select ...",
            width: '100%',
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
        var value = getFieldValue("#" + id);
        if (value) currentFilter[id] = value;
    });
  
    // process date fields
    $.each(["start_date", "end_date"], function (i, k) {
        if (currentFilter[k]) {
            var formatted = normalizeDate(currentFilter[k]);
            if (formatted) currentFilter[k] = formatted;
            else delete currentFilter[k];
        }
    });
 
    localStorage.setItem(FILTER_KEY, JSON.stringify(currentFilter));
    if (table) table.setData();
}
 
function clearFilter() {
    var d = new Date();
    var todayDMY = String(d.getDate()).padStart(2, '0') + "-" + String(d.getMonth() + 1).padStart(2, '0') + "-" + d.getFullYear();

    $.each(filterFields, function (i, id) {
        const el = $("#" + id).get(0);
        let defaultValue = "";
        if (id === "product_status") defaultValue = "all";
        if (id === "start_date") defaultValue = todayDMY;

        setFieldValue(el, defaultValue);
    });
 
    currentFilter = { product_status: "all", start_date: todayDMY };
    localStorage.removeItem(FILTER_KEY);
 
    if (table) table.setData();
}
 
function restoreFilters() {
    var saved = localStorage.getItem(FILTER_KEY);
    if (!saved) return;
    currentFilter = JSON.parse(saved);
    $.each(currentFilter, function (id, value) {
        setFieldValue("#" + id, value);
    });
}

function normalizeDate(dateStr) {
    if (!dateStr || dateStr === "-") return "";
    
    // Handle ISO strings like 2026-03-31T18:30:00.000000Z
    if (typeof dateStr === "string" && dateStr.includes("T")) {
        dateStr = dateStr.split("T")[0];
    }

    // If it matches DD-MM-YYYY, return as is (already in display format)
    if (/^\d{2}-\d{2}-\d{4}$/.test(dateStr)) return dateStr;
    
    // If it matches YYYY-MM-DD, convert to DD-MM-YYYY
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
        if (typeof formatDateToDMY === "function") {
            return formatDateToDMY(dateStr);
        }
    }

    return dateStr;
}

$(document).ready(function () {
    bindSelect2();

    if (typeof DateInput !== 'undefined') {
        new DateInput("#start_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
        new DateInput("#end_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    }
    // replicate grn_date into in/out dates whenever user changes it
    $(document).on('change', '#start_date', function () {
        const val = $(this).val();
        $('#end_date').val(val);
    });
    restoreFilters();
    // Initialize missing filters from DOM defaults (e.g., start_date set by Blade)
    $.each(filterFields, function (i, id) {
        if (currentFilter[id] === undefined) {
            var value = getFieldValue("#" + id);
            if (value) currentFilter[id] = value;
        }
    });
    
    // Process date fields to ensure correct format
    $.each(["start_date", "end_date"], function (i, k) {
        if (currentFilter[k]) {
            var formatted = normalizeDate(currentFilter[k]);
            if (formatted) currentFilter[k] = formatted;
            else delete currentFilter[k];
        }
    });
    table = new Tabulator("#moisture_table", {
        height: "550px",
        layout: "fitColumns",
        placeholder: "No Data Found",
        ajaxURL: moistureListUrl,
        ajaxParams: () => {
            return {
                ...currentFilter,
                is_moisture: 1
            };
        },
        ajaxResponse(url, params, response) {
            if (response.permissions) currentPermissions = response.permissions;
            return response.data || response;
        },
        columns: getTableColumns(savedColWidths),
    });

  
    $("#filter_apply").on("click", function (e) {
        e.preventDefault();
        applyFilter();
    });
    $("#filter_clear").on("click", function (e) {
        e.preventDefault();
        clearFilter();
        var $statusDropdown = $("#product_status"); 
        if (!$statusDropdown.length) return;
 
        if ($statusDropdown.data("select2")) {
            $statusDropdown.val("all").trigger("change");
        } else {
            $statusDropdown.val("all");
        }
        applyFilter();
    });

});