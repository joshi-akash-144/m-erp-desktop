let table;
let currentFilter = {};
let currentPermissions = {};
let savedColWidths = {};

const filterFields = [
    "payment_date",
    "file_no",
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
      title: "Ref. No",
      field: "ref_no",
      width: 100,
      headerSort: true,
      hozAlign: "center",
      headerHozAlign: "center",
      sorter: "string",
      resizable: true,
    },
    // =======================
    // File number
    // =======================
    {
      title: "File No.",
      field: "file_no",
      width: 100,
      headerSort: false,
      hozAlign: "center",
      headerHozAlign: "center",
      resizable: true,
    },

    // =======================
    // Particulars
    // =======================
    {
      title: "Particulars",
      field: "account.name",
      width: 350,
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
      width: 170,
      sorter: "string",
      headerSort: false,
      resizable: true,
    },

    // =======================
    // Payment Date.
    // =======================
    {
      title: "Payment Date",
      field: "payment_date",
      width: 140,
      headerSort: false,
      hozAlign: "center",
      headerHozAlign: "center",
      resizable: true,
      formatter: function (c) {
        return formatDateToDMY(c.getValue());
      },
    },
    // =======================
    // Amount
    // =======================
    {
      title: "Amount (₹)",
      field: "amount",
      width: 150,
      headerSort: false,
      hozAlign: "right",
      headerHozAlign: "right",
      formatter: function (c) {
        return formatIndianNumber(c.getValue(), DECIMALS.AMOUNT); 
      },
      resizable: true,
    },
    
    // =======================
    // Created By
    // =======================
    {
      title: "Created By",
      field: "created_by",
      width:  150,
      hozAlign: "center",
      headerHozAlign: "center", 
      headerSort: false,
      resizable: true,
      formatter: (cell) => {
        const creatorName = cell.getRow().getData().created_by;
        return creatorName
          ? `<span class="badge bg-danger-lt ">${creatorName}</span>`
          : '';
      },
    },
    // =======================
    // Narration
    // =======================
    {
      title: "Narration",
      field: "narration",
      minWidth: 450,
      headerSort: false,
      resizable: true,
      formatter: (cell) => {
        const val = cell.getValue() || '';
        cell.getElement().setAttribute('title', val);
        return val;
      },
    },
    // =======================
    // Actions
    // =======================
    {
      title: "Actions",
      field: "actions",
      width: 300,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      frozen: true,
      formatter: (cell) => {
        const row = cell.getData();
        const canUpdate = currentPermissions.update ?? false;
        const canDelete = currentPermissions.delete ?? false;
        const checkIcon = icons.check;
        const deleteIcon = icons.delete;
        let actions = '<div class="d-flex gap-2 align-items-center justify-content-center">';

        // 2. Approve button
        if(canUpdate){
            actions += `<button type="button" class="btn btn-sm btn-primary fw-semibold align-items-center" style="font-size: 0.75rem; height: 24px;margin-top:3px;" id="approve" data-id="${row.id}">
                           <i class="fas fa-check-circle text-white me-2"></i>
                            <span>Approve</span>
                        </button>`;
        }

        // 3. Delete (Not Paid) button
        if(canDelete){
            actions += `<button type="button" id="delete-payment" class="btn btn-sm btn-danger fw-semibold align-items-center" style="font-size: 0.75rem; height: 24px;margin-top:3px;" id="approve" data-id="${row.id}">
                           <i class="fas fa-trash text-white me-2"></i>
                            <span>Not Paid</span>
                        </button>`;
        }

        
            actions += `<button type="button" id="view-bill" class="btn btn-sm btn-success fw-semibold align-items-center" style="font-size: 0.75rem; height: 24px;margin-top:3px;" id="approve" data-id="${row.id}">
                           <i class="fas fa-eye text-white me-2"></i>
                            <span>Bill Detail</span>
                        </button>`;
        

        actions += "</div>";
        return actions;
      },
    },
  ];
}

$(document).ready(function () {

    $('#payment_date').val(currentDate).focus();

    // date input with validation
    new DateInput("#payment_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    restoreFilters();
    // Tabulator
    // purchaser order List
  table = new Tabulator("#payment_approval_table", {
    height: "550px",
    layout: "fitDataFill",
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

    ajaxURL: paymentApprovalListUrl,
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

  
  $(`<style>#payment_approval_table .tabulator-row { cursor: pointer !important; }</style>`).appendTo("head");

  // Handle Delete (Not Paid) Action
  $(document).on("click", "#delete-payment", function(e) {
      e.stopPropagation();
      const id = $(this).data("id");
      if (!id) return;

      Swal.fire({
          title: "Delete Payment?",
          html: `This will permanently delete the payment and:<br>
                 <ul class="mt-2 mb-0" style="display:inline-block;text-align:left;">
                   <li>Cancel any associated cheque</li>
                   <li>Reverse all reference allocations</li>
                   <li>Reopen any settled references</li>
                 </ul><br>
                 This action <strong>cannot be undone</strong>.`,
          icon: "warning",
          showCancelButton: true,
          allowOutsideClick: false,
          allowEscapeKey: false,
          confirmButtonColor: "#d63939",
          cancelButtonColor: "#6c757d",
          confirmButtonText: "Yes, Delete",
          cancelButtonText: "Cancel",
      }).then((result) => {
          if (result.isConfirmed) {
              deletePayment(id);
          }
      });
  });

   $(document).on("click", "#view-bill", function(e) {
        e.stopPropagation();
        const id = $(this).data("id");
        if (!id) return;

        viewBillDetail(id);
   });
  // Handle Single and Bulk Approve Action
  $(document).on("click", "#approve, #bulkApproveBtn", function() {
      let ids = [];
      let isSingle = $(this).attr("id") === "approve";

      if (isSingle) {
          ids = [$(this).data("id")];
      } else {
          if (typeof table === 'undefined' || !table) return;
          ids = table.getRows()
              .map(r => r.getData())
              .filter(data => data.selected)
              .map(data => data.id);
      }

      if (ids.length === 0) {
          Swal.fire("Info", "Please select at least one payment.", "info");
          return;
      }

      const textMsg = isSingle 
          ? "Do you want to approve this payment?" 
          : `Do you want to approve ${ids.length} selected payments?`;

      Swal.fire({
          title: "Are you sure?",
          text: textMsg,
          icon: "warning",
          showCancelButton: true,
          allowOutsideClick: false,
          allowEscapeKey: false,
          confirmButtonColor: "#198754",
          confirmButtonText: "Yes, Approve!"
      }).then((result) => {
          if (result.isConfirmed) {
              approvePayments(ids);
          }
      });
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
  
    // process date fields
    $.each(["payment_date"], function (i, k) {
        if (currentFilter[k]) {
            var formatted = normalizeDate(currentFilter[k]);
            if (formatted) {
                currentFilter[k] = formatted;
            } else {
                delete currentFilter[k];
            }
        }
    });
 
    if (table) table.setData(paymentApprovalListUrl);
}
 
 
function clearFilter() {
    $.each(filterFields, function (i, id) {
        var el = $("#" + id).get(0);
        setFieldValue(el, "");
    });
 
    // Set default payment date
    setFieldValue($("#payment_date").get(0), currentDate);

    currentFilter = {
        payment_date: currentDate
    };

    if (table) table.setData();
}

/** Build initial filter from the payment date input on page load */
function restoreFilters() {
    var value = getFieldValue($("#payment_date").get(0));
    var formatted = normalizeDate(value);
    currentFilter = formatted ? { payment_date: formatted } : {};
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

/** Convert DD-MM-YYYY → YYYY-MM-DD */
function normalizeDate(d) {
    if (!d || d === "__-__-____") return "";
    var parts = d.split("-");
    var day = parts[0], month = parts[1], year = parts[2];
    return year && year.length === 4 ? (day + "-" + month + "-" + year) : "";
}

function onSelectionChanged() {
    if (typeof table === 'undefined' || !table) return;
    
    const rows = table.getRows();
    const selectedRows = rows.filter(r => r.getData().selected);
    
    // 1. Calculate sum of 'amount' field
    let total = 0;
    selectedRows.forEach(row => {
        const val = parseFloat(row.getData().amount);
        if (!isNaN(val)) {
            total += val;
        }
    });
    
    // 2. Format with 2 decimals
    const formattedTotal = formatIndianNumber(total);
    $("#selectedTotalAmount").text(formattedTotal);
    
    // 3. Update Tabulator column header checkbox and bottom selectAll checkbox as well
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

function approvePayments(ids) {
    $.ajax({
        url: paymentApprove,
        type: "POST",
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            ids: ids
        },
        success: function(res) {
            Swal.fire("Success", res.message || "Approved successfully!", "success");
            if (table) table.setData();
        },
        error: function(xhr) {
            Swal.fire("Error", xhr.responseJSON?.message || "Something went wrong", "error");
        }
    });
}

function deletePayment(id) {
    $.ajax({
        url: paymentApprovalDeleteUrl.replace(":id", id),
        type: "DELETE",
        headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
        success: function(res) {
            if (res.success) {
                Swal.fire("Deleted!", res.message || "Payment deleted successfully.", "success");
                if (table) table.setData();
            } else {
                Swal.fire("Cannot Delete", res.message || "Failed to delete.", "error");
            }
        },
        error: function(xhr) {
            Swal.fire("Error", xhr.responseJSON?.message || "Something went wrong", "error");
        }
    });
}


function viewBillDetail(id) {
    if (typeof showLoader === "function") showLoader('Please wait...');
    
    $.ajax({
        url: paymentBillDetailUrl.replace(":id", id),
        type: "GET",
        headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
        success: function(res) {
           if (res.html) {
               // Remove any lingering old modal from DOM
               $("#billDetailModal").remove();
               $(".modal-backdrop").remove();
               $("body").removeClass("modal-open");
               
               $("#billDetailContainer").html(res.html);
               $("#billDetailModal").modal("show");
           }
        },
        error: function(xhr) {
           Swal.fire("Error", "Failed to fetch bill details.", "error");
        },
        complete: function() {
            if (typeof hideLoader === "function") hideLoader();
        }
    });
}