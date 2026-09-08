let table; 
let currentFilter = { product_status: "all" };
let currentPermissions = {};
let savedColWidths = {};
const MAX_RECORDS = 150;

const FILTER_KEY = "godown_filter";
const WIDTH_KEY = "godown_col_widths";

const filterFields = [
    "start_date",
    "end_date",
    "account_id",
    "item_id",
    "godown_id",
    "godown_unit_id",
    "grn_number",
    "challan_number",
    "product_status",    
    "lr_number",
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
            const isChecked = cell.getValue() ? "checked" : "";
            return `<div class="d-flex align-items-center justify-content-center h-100 w-100"><input type="checkbox" class="form-check-input m-0 shadow-none" ${isChecked}></div>`;
        },
        cellClick: function(e, cell){
            e.stopPropagation();
            const newValue = !cell.getValue();
            cell.setValue(newValue);

            const rows = table.getRows();
            const allChecked = rows.length > 0 && rows.every(r => r.getData().selected);
            const headerCheckbox = cell.getTable().getColumn("selected").getElement().querySelector("input");
            if(headerCheckbox) headerCheckbox.checked = allChecked;
        }
    },
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
            let status = (data.in_out_status || "").toLowerCase();
            let displayVal = `${data.grn_serial ?? "-"}`;
            
            // if (status === 'in') {
            //     let grn = data.grn_serial || "-";
            //     displayVal = grn !== "-" ? `<span class="fw-bold text-dark">GRN:</span> ${grn}` : "-";
            // } else if (status === 'out') {
            //     let dc = data.dc_serial || "-";
            //     displayVal = dc !== "-" ? `<span class="fw-bold text-dark">DC:</span> ${dc}` : "-";
            // }
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
      title: "Party Name & Item",
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
                    <small class="d-block text-truncate" title="${item_name}">${item_name}</small>
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
      formatter: (cell) => cell.getValue() || '-'
    },
    {
      title: "Challan No <br> Challan Weight",
      field: "challan_weight", // Use numeric field for bottomCalc to work
      width: savedColWidths.challan_no_and_challan_weight ?? 130,
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
            let challan_weight = data.challan_weight ? parseFloat(data.challan_weight).toFixed(3) : '0.000';
            return `
                <div class="lh-1 p-2">
                    <div>${challan_no}</div>
                    <div style="margin-top: -2px;">${challan_weight}</div>
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
            let data = cell.getRow().getData();
            let bags = data.bag_count || 0; 
            return `
                <div>
                    <div>${bags}</div>
                </div>
            `;
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
      width: savedColWidths.destination ?? 150,
      headerSort: false,
      formatter: (cell) => cell.getValue() || '-'
    },
    {
      title: "Unit",
      field: "godowns.godown_name",
      width: savedColWidths.godown ?? 120,
      headerSort: false,
      hozAlign: "left",
      headerHozAlign: "left",
      formatter: (cell) => cell.getValue() || '-'
    },
    {
      title: "Date",
      field: "grn_date",
      width: savedColWidths.grn_date ?? 120,
      headerSort: false,
      hozAlign: "left",
      headerHozAlign: "left",
      formatter: function (cell) {
          const grnDate = cell.getRow().getData().grn_date;
          const dcDate = cell.getRow().getData().dc_date;
            if (grnDate) {
                return normalizeDate(grnDate);
            } else if (dcDate) {
                return normalizeDate(dcDate);
            } else {
                return '-';
            }
      }
    },
    {
      title: "Date IN <br> Date Out",
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
                    <div>${date_out}</div>
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
                    <div>${time_out}</div>
                </div>
            `;
        }
    },
    {
      title: "P.Qty",
      field: "p_qty",
      width: savedColWidths.p_qty ?? 100,
      hozAlign: "right",
      headerHozAlign: "right",
      headerSort: false,
      formatter: (cell) => cell.getValue() ? parseFloat(cell.getValue()).toFixed(3) : '0.000'
    },
    {
      title: "Rate",
      field: "rate",
      width: savedColWidths.rate ?? 100,
      hozAlign: "right",
      headerHozAlign: "right",
      headerSort: false,
      formatter: (cell) => cell.getValue() ? parseFloat(cell.getValue()).toFixed(2) : '0.00'
    },
    {
      title: "Weight",
      field: "gross_weight",
      width: savedColWidths.weight ?? 180,
      headerSort: false,
      hozAlign: "right",
      headerHozAlign: "right",
      formatter: function(cell) {
            let data = cell.getRow().getData();
            let gross = data.gross_weight ? parseFloat(data.gross_weight).toFixed(3) : '0.000'; 
            let tare = data.tare_weight ? parseFloat(data.tare_weight).toFixed(3) : '0.000';
            return `
                <div class="text-end lh-1 p-2">
                    <div>Gross: <span class="fw-bold">${gross}</span></div>
                    <div>Tare: <span class="fw-bold">${tare}</span></div>
                </div>
            `;
        }
    },
    {
      title: "Net Weight",
      field: "net_weight",
      width: savedColWidths.net_weight ?? 120,
      hozAlign: "right",
      headerHozAlign: "right",
      headerSort: false,
      formatter: (cell) => `<span class="fw-bold">${cell.getValue() ? parseFloat(cell.getValue()).toFixed(3) : '0.000'}</span>`,
      bottomCalc: "sum",
      bottomCalcFormatter: function(cell) {       
            let net_weight = cell.getValue();         
          return `<span class="text-dark fw-bold">${net_weight ? parseFloat(net_weight).toFixed(3) : '0.000'}</span>`;
      }
    },   
    {
      title: "Without Bag Weight",
      field: "net_weight_wt_bag",
      width: savedColWidths.without_bag_weight ?? 160,
      hozAlign: "right",
      headerHozAlign: "right",
      headerSort:false,
      formatter: (cell) => `<span class="fw-bold">${cell.getValue() ? parseFloat(cell.getValue()).toFixed(3) : '0.000'}</span>`,
      bottomCalc: "sum",
      bottomCalcFormatter: function(cell) {       
            let net_weight = cell.getValue();         
          return `<span class="text-dark fw-bold">${net_weight ? parseFloat(net_weight).toFixed(3) : '0.000'}</span>`;
      }
    },
    {
      title: "Lr Number",
      field: "lr_number",
      width: savedColWidths.lr_number ?? 160,
      hozAlign: "right",
      headerHozAlign: "right",
      headerSort:false,
    },
    {
      title: "Actions",
      field: "actions",
      width: 100,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
    //   frozen: true,
      formatter: (cell) => {
        const row = cell.getData();
        const canDelete = currentPermissions.delete ?? false;
        const canEdit = currentPermissions.update ?? false;

        // Calculate days since grn_date
        const referenceDateStr = row.grn_date;
        const referenceDate = new Date(referenceDateStr);
        const today = new Date();   
        const diffDays = Math.ceil(Math.abs(today - referenceDate) / (1000 * 60 * 60 * 24));

        // Logic: 
        // Must have item, cycle must be 'open', within 5 days.
        // For 'out': tare must be recorded.
        // For 'in': gross must be recorded.
        const hasItem = row.item && row.item.id && row.item.id !== 0;
        const isEligible = 
            (row.in_out_status === 'out') && 
            hasItem &&
            (row.is_cycle === 'open') &&
            (parseFloat(row.tare_weight) > 0) &&
            (diffDays <= 5);

        const isClosed = (row.in_out_status === 'out') && (row.grn_status === 'close');
        const isBilled = row.grn_status === 'billed';

        const deleteIcon = icons.delete;
        const editIcon = icons.edit;

        let actions = '<div class="d-flex gap-2 justify-content-center">';
        if (canEdit){
            if (isBilled) {
                const lockIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler-lock text-muted">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M5 11m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z" />
                <path d="M12 16m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                <path d="M8 11v-4a4 4 0 0 1 8 0v4" />
                </svg>`;
                actions += `<span class="erp-btn-icon disabled text-muted" style="cursor: not-allowed !important; opacity: 0.5;" title="Record is billed and locked">${lockIcon}</span>`;
            } else {
                actions += `<span class="erp-btn-icon edit" data-id="${row.id}" title="Edit Record">${editIcon}</span>`;
            }
        }
        if (canDelete){
          if (isEligible || isClosed) {
            actions += `<span class="erp-btn-icon delete" data-id="${row.id}" data-closed="${isClosed}" title="Delete Record">${deleteIcon}</span>`;
          } else {
            actions += `<span class="erp-btn-icon disabled text-muted" style="cursor: not-allowed !important; opacity: 0.5;" title="Cannot delete this record">${deleteIcon}</span>`;
          }
        }
        actions += "</div>";
        return actions;
      },
    },
    {
      title: "Entry Mode",
      field: "is_manual",
      width: savedColWidths.is_manual  || 120,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      resizable: true,
      formatter: (cell) =>
        cell.getValue() === '1' || cell.getValue() === 1 || cell.getValue() === true
          ? `<span class="badge bg-danger-lt ">Manual</span>`
          : `<span class="badge bg-teal-lt ">Machine</span>`,
    },
    {
      title: "Cycle",
      field: "is_cycle",
      width: savedColWidths.cycle  || 120,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      resizable: true,
      formatter: (cell) =>
        cell.getValue() === 'open'
          ? `<span class="badge bg-teal-lt ">Open</span>`
          : `<span class="badge bg-danger-lt ">Close</span>`,
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
        const creatorName = cell.getRow().getData().creator?.name || null;
        return creatorName
          ? `<span class="badge bg-cyan-lt ">${creatorName}</span>`
          : `<span class="badge bg-danger-lt">--</span>`;
      },
    },
    {
      title: "Updated By",
      field: "updated_by",
      width: savedColWidths.updated_by || 150,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      resizable: true,
      formatter: (cell) => {
        const updaterName = cell.getRow().getData().updater?.name || null;
        return updaterName ? 
          `<span class="badge bg-danger-lt ">${updaterName}</span>`
          : `<span class="badge bg-cyan-lt">--</span>`;
      },
    },
  ];
}

function bindSelect2() {
    const selectIdArray = ['#account_id', '#item_id', '#godown_id', '#godown_unit_id', '#product_status','#print_type','#grn_number','#challan_number'];
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
    table = new Tabulator("#godown_table", {
        height: "550px",
        layout: "fitColumns",
        placeholder: "No Data Found",
        ajaxURL: godownListUrl,
        ajaxParams: () => currentFilter,
        ajaxResponse(url, params, response) {
            if (response.permissions) currentPermissions = response.permissions;
            return response.data || response;
        },
        columns: getTableColumns(savedColWidths),
    });
    
    table.on("dataLoaded", function(data){
        const col = table.getColumn("selected");
        if (col) {
            const headerCheckbox = col.getElement().querySelector("input");
            if (headerCheckbox) headerCheckbox.checked = false;
        }
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

    // Handle Print Button Click
    $("#filter_print").on("click", function (e) {
        e.preventDefault();
        const printType = $("#print_type").val();
        
        // Get selected row IDs
        const selectedData = table.getData().filter(row => row.selected);
        const selectedIds = selectedData.map(row => row.id);

        // Validation for Ticket type
        if ((printType === "ticket" || printType === "letter" || printType === "gatepass" || printType === "grn") && selectedIds.length === 0) {
            Swal.fire({
                title: "Wait!",
                text: "Please Select at least one checkbox!",
                icon: "warning",
                confirmButtonColor: "#3085d6",
                confirmButtonText: "OK",
                allowEscapeKey: false,
                allowOutsideClick: false
            });
            return;
        }
        
        if (selectedIds.length > MAX_RECORDS) {
            Swal.fire({
                title: "Limit Exceeded!",
                text: `You can only print up to ${MAX_RECORDS} items at a time.`,
                icon: "warning",
                confirmButtonColor: "#3085d6",
                confirmButtonText: "OK",
                allowEscapeKey: false,
                allowOutsideClick: false
            });
            return;
        }

        // Choose route based on print_type
        const route = (printType === "ticket") ? godownPrintTicketUrl : (printType === "letter") ? godownPrintLetterUrl : (printType === "gatepass") ? godownPrintGatepassUrl : (printType === "grn") ? godownPrintGRNUrl : '';
        
        // Prepare filters (include selected IDs if any)
        const filters = { ...currentFilter };
        if (selectedIds.length > 0) {
            filters.selected_ids = selectedIds;
        }

        // Use the global printReport helper
        printReport(route, { 
            format: "print", 
            currentFilter: filters 
        });
    });

    // Dynamic Print Button Name based on Print Type selection
    $("#print_type").on("change", function () {
        const selectedOption = $(this).find("option:selected");
        const selectedText = selectedOption.val() ? selectedOption.text() : "Ticket";
        const iconHtml = '<i class="fa-solid fa-print me-1"></i>';
        $("#filter_print").html(`${iconHtml} ${selectedText} Print`);
    });

    // Edit item in out
    $(document).on("click", ".edit", function (event) {
        event.preventDefault();
        const itemId = $(this).data("id");
        const row = table.getRow(itemId);
        
        if (row) {
            const data = row.getData();
            if (data.in_out_status === 'in') {
                window.location.href = `${godownProductInSelfUrl}?id=${itemId}&grn_serial=${data.grn_serial}`;
            } else if (data.in_out_status === 'out') {
                window.location.href = `${godownProductOutSelfUrl}?id=${itemId}&grn_serial=${data.grn_serial}`;
            }
        }
    });

  // Delete item in out
  $(document).on("click", ".delete", function (event) {
    const itemId = $(this).data("id");
    const isClosed = $(this).data("closed");
    
    if (isClosed === true || isClosed === 'true') {
        Swal.fire({
            title: "Wait!",
            text: "GRN is in use and cannot be deleted.",
            icon: "warning",
            confirmButtonColor: "#3085d6",
            confirmButtonText: "OK",
            allowEscapeKey: false,
            allowOutsideClick: false
        });
        return;
    }
    const url = deleteGodownUrl.replace(":id", itemId);

    Swal.fire({
      title: "Are you sure?",
      text: "You won't be able to revert this!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      confirmButtonText: "Yes, delete it!",
      cancelButtonText: "No, cancel!",
      reverseButtons: false,
      allowEscapeKey: false,
      allowOutsideClick: false
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: url,
          type: "DELETE",
          beforeSend: function () {
            showLoader("Deleting Entry...");
          },
          success: function (response) {
            showToast("success", response.message || "Entry deleted successfully!");
            table.replaceData();
          },
          error: function (xhr) {
            showToast("error", 'Oops! Something went wrong. Try again later.');
            console.error(xhr.responseText);
          },
          complete: function () {
            hideLoader();
          },
        });
      }
    });
  });


    $(document).on("click", ".dropdown-item", function (e) {
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
});