let table; 
let currentFilter = {};
let currentPermissions = {};
let savedColWidths = {};
const MAX_RECORDS = 150;

const FILTER_KEY = "challan_bags_entry_filter";
const WIDTH_KEY = "challan_bags_entry_col_widths";

const filterFields = [
    "start_date",
    "end_date",
    "item_id"
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
                calculateTotals();
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
            
            calculateTotals();
        }
    },
    {
      title: "Date",
      field: "date",
      width: savedColWidths.date ?? 130,
      headerSort: false,
      headerHozAlign: "center",
      hozAlign: "center",
    },
    {
      title: "Vehicle No",
      field: "vehicle_number",
      width: savedColWidths.vehicle_number ?? 180,
      headerSort: false,
      headerHozAlign: "center",
      hozAlign: "center",
      formatter: function(cell) {
          return `<span class="fw-bold">${cell.getValue() || '-'}</span>`;
      }
    },
    {
      title: "From Destination",
      field: "from_destination",
      width: savedColWidths.from_destination ?? 350,
      headerSort: false,
      headerHozAlign: "left",
      hozAlign: "left",
      formatter: function(cell) {
          return `<span class="fw-bold">${cell.getValue() || '-'}</span>`;
      }
    },
    {
      title: "To Destination",
      field: "to_destination",
      width: savedColWidths.to_destination ?? 350,
      headerSort: false,
      headerHozAlign: "left",
      hozAlign: "left",
      formatter: function(cell) {
          return `<span class="fw-bold">${cell.getValue() || '-'}</span>`;
      }
    },
    {
      title: "Item",
      field: "item.name",
      width: savedColWidths.item_name ?? 250,
      headerSort: false,
    },
    {
      title: "Bags",
      field: "bag_count",
      width: savedColWidths.bags ?? 80,
      headerSort: false,
      headerHozAlign: "right",
      hozAlign: "right",
    }
  ];    
}

function bindSelect2() {
    const selectIdArray = ['#item_id'];
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
        if (id === "start_date" || id === "end_date") defaultValue = todayDMY;

        setFieldValue(el, defaultValue);
    });
 
    currentFilter = { start_date: todayDMY, end_date: todayDMY };
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

function calculateTotals() {
    let totalBags = 0;
    if (table) {
        const rows = table.getRows();
        rows.forEach(row => {
            const data = row.getData();
            if (data.selected) {
                totalBags += parseFloat(data.bag_count) || 0;
            }
        });
    }

    $("#bags").val(totalBags);
    calculateAmount();
}

function calculateAmount() {
    const totalBags = parseFloat($("#bags").val()) || 0;
    const rate = parseFloat($("#rate").val()) || 0;
    const amount = totalBags * rate;
    
    $("#amount").val(amount > 0 ? amount.toFixed(2) : "");
    
    const loadingCharges = parseFloat($("#loading_charges").val()) || 0;
    const totalAmount = amount + loadingCharges;
    
    $("#total_amount").val(totalAmount > 0 ? totalAmount.toFixed(2) : "");
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
    table = new Tabulator("#challan_bags_entry_table", {
        height: "350px",
        layout: "fitColumns",
        placeholder: "No Data Found",
        ajaxURL: BagChallanLabourUrl,
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
        applyFilter();
    });

    $("#rate, #loading_charges").on("input", function() {
        calculateAmount();
    });

    // Move on Enter for Rate field
    $("#rate").on("keydown", function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            $(this).closest('.card-footer').find("button[type='submit']").focus();
        }
    });

    $("#store-form").on("submit", function(e) {
        e.preventDefault();

        if (!table) return;

        const selectedRows = table.getRows().filter(row => row.getData().selected);
        if (selectedRows.length === 0) {
            toastr.error("Please select at least one GRN.");
            return;
        }

        const items = selectedRows.map(row => ({
            grn_id: row.getData().id,
            bags: row.getData().bag_count
        }));

        const payload = {
            _token: $('meta[name="csrf-token"]').attr("content"),
            uuid: $("#uuid").val(),
            rate: $("#rate").val() || 0,
            loading_amount: $("#loading_charges").val() || 0,
            total_amount: $("#total_amount").val() || 0,
            bags: $("#bags").val() || 0,
            items: items
        };

        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

        $.ajax({
            url: BagChallanLabourStoreUrl,
            type: "POST",
            data: payload,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || "Saved successfully",
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#206bc4',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                    }).then((result) => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message || "Failed to save",
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#d62d20'
                    });
                }
            },
            error: function(xhr) {
                let msg = "An error occurred";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                toastr.error(msg);
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-2"></i> Save');
            }
        });
    });

});