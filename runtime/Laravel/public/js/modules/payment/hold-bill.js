let table;
let currentFilter = {};
let currentPermissions = {};
let savedColWidths = {};

const FILTER_KEY = "payment_hold_filter";
const WIDTH_KEY = "payment_hold_col_widths";

const filterFields = [
    "account_id",
];

function getTableColumns(savedColWidths = {}) {
  return [
      
    {
        title:"Yes/No",
        field: "selected",
        width: 100,
        headerHozAlign: "center",
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

            onSelectionChanged();
        }
    },
        
    // =======================
    // Ref Number.
    // =======================
    {
      title: "Bill No",
      field: "ref_no",
      width: savedColWidths.ref_no ?? 100,
      headerSort: false,
      hozAlign: "center",
      headerHozAlign: "center",
      resizable: true,
    },
    // =======================
    // Bill Date.
    // =======================
    {
      title: "Bill Date",
      field: "bill_date",
      width: savedColWidths.bill_date ?? 150,
      headerSort: false,
      hozAlign: "center",
      headerHozAlign: "center",
      resizable: true,
      formatter: function (c) {
        const val = c.getValue();
        return val ? formatDateToDMY(val) : "";
      },
    },
    // =======================
    // Particulars
    // =======================
    {
      title: "Particulars",
      field: "account.name",
      width: savedColWidths.name ?? 400,
      headerSort: false,
      hozAlign: "left",
      headerHozAlign: "left",
      resizable: true,
    },
    // =======================
    // City
    // =======================
    {
      title: "City",
      field: "account.city",
      width: savedColWidths.city ?? 200,
      sorter: "string",
      headerSort: false,
      resizable: true,
    },
    // =======================
    // Amount
    // =======================
    {
      title: "Amount (₹)",
      field: "amount",
      width: savedColWidths.amount ?? 120,
      headerSort: false,
      hozAlign: "right",
      headerHozAlign: "right",
      formatter: function (c) {
        return formatIndianNumber(c.getValue(), DECIMALS.AMOUNT); 
      },
      resizable: true,
    },
  ];
}

// ===========================================================
// SELECT2 INIT
// ===========================================================
function bindSelect2() {
    var selectIdArray = [
        "#account_id"
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

    $('#account_id').focus();

    bindSelect2();
    restoreFilters();
    // Tabulator
    // purchaser order List
    table = new Tabulator("#hold_bill_for_payment_table", {
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

    ajaxURL: paymentHoldListUrl,
    ajaxParams: () => currentFilter,
    ajaxURLGenerator: (url, config, params) => {
      const queryParams = new URLSearchParams(currentFilter);
      return `${url}?${queryParams.toString()}`;
    },
    ajaxResponse(url, params, response) {
      if (response.permissions) currentPermissions = response.permissions;
      return response.data || [];
    },
    columns: getTableColumns(savedColWidths),
    dataLoaded: function(data) {
      onSelectionChanged();
    },
  });

   $("#filter_apply").on("click", function(e){
      e.preventDefault();
      applyFilter();
    });
   $("#filter_clear").on("click", function (e) {
        e.preventDefault();
        clearFilter();
        applyFilter();
    });

    $(document).on("change", "#selectAll", function(e) {
        if (typeof table === 'undefined' || !table) return;
        const checked = $(this).is(":checked");
        
        table.blockRedraw(); 
        table.getRows().forEach(row => {
            row.update({ selected: checked }); 
        });
        table.restoreRedraw(); 
        onSelectionChanged();
    });

});

function applyFilter() {
    currentFilter = {};
 
    $.each(filterFields, function (i, id) {
        var el    = $("#" + id).get(0);
        var value = getFieldValue(el);
 
        if (value) currentFilter[id] = value;
    });
 
    localStorage.setItem(FILTER_KEY, JSON.stringify(currentFilter));
 
    if (table) table.setData(paymentHoldListUrl);
}
 
 
function clearFilter() {
    $.each(filterFields, function (i, id) {
        var el = $("#" + id).get(0);
        setFieldValue(el, "");
    });
 
    currentFilter = {};
    localStorage.removeItem(FILTER_KEY);
 
    if (table) table.setData(paymentHoldListUrl);
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
    if ($el.hasClass("select2-hidden-accessible")) {
        $el.trigger("change");
    }
}

function onSelectionChanged() {
    if (typeof table === 'undefined' || !table) return;
    
    const rows = table.getRows();
    const selectedRows = rows.filter(r => r.getData().selected);
        
    // Update Tabulator column header checkbox and bottom selectAll checkbox as well
    const isAllSelected = rows.length > 0 && rows.every(r => r.getData().selected);
    const isSelected = rows.length > 0 && selectedRows.length > 0 && selectedRows.length < rows.length;
    const headerCheckbox = document.getElementById("headerSelectAllCheckbox");
    if (headerCheckbox) {
        headerCheckbox.checked = isAllSelected;
        headerCheckbox.indeterminate = isSelected;
    }
    const bottomCheckbox = document.getElementById("selectAll");
    if (bottomCheckbox) {
        bottomCheckbox.checked = isAllSelected;
        bottomCheckbox.indeterminate = isSelected;
    }
}

// Bulk Approve button click
$(document).on("click", "#bulkApproveBtn", function() {
    if (typeof table === 'undefined' || !table) return;
    const selectedIds = table.getRows()
                        .map(r => r.getData())
                        .filter(data => data.selected)
                        .map(data => data.id);
    
    if (selectedIds.length === 0) {
        Swal.fire("Info", "Please select at least one record.", "info");
        return;
    }
    
    Swal.fire({
        title: "Are you sure?",
        text: `Do you want to move ${selectedIds.length} selected record(s) to Payment Payables?`,
        icon: "warning",
        showCancelButton: true,
        allowOutsideClick: false, 
        allowEscapeKey: false,
        confirmButtonColor: "#198754",
        confirmButtonText: "Yes, Move!"
    }).then((result) => {
        if (result.isConfirmed) {
            holdPayments(selectedIds);
        }
    });
});

function holdPayments(ids) {
    if (typeof paymentHold === 'undefined' || !paymentHold) {
        Swal.fire("Success", "Moved to Payment Payables successfully!", "success");
        if (table) table.setData();
        return;
    }
    $.ajax({
        url: paymentHold,
        type: "POST",
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            ids: ids
        },
        success: function(res) {
            Swal.fire("Success", res.message || "Moved to Payment Payables successfully!", "success");
            if (table) table.setData();
        },
        error: function(xhr) {
            Swal.fire("Error", xhr.responseJSON?.message || "Something went wrong", "error");
        }
    });
}