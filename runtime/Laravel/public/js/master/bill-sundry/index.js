
/**
 * Generate and return the column configuration for the table.
 * @param {object} savedColWidths - Saved column width settings
 */

function getTableColumns(savedColWidths) {
  return [
    // =======================
    // Row Number Column
    // =======================
    {
      title: "No",
      field: "no",
      width: 80,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      resizable: false,
      frozen: true,
    },

    // =======================
    // Bill Sundry Name
    // =======================
    {
      title: "Name",
      field: "name",
      width: savedColWidths.name || 200,
      headerSort: true,
      sorter: "string",
      resizable: true,
      formatter: (cell) => {
        const val = cell.getValue();
        if (val == null) return "";
        // convert to lowercase then capitalize first letter of each word
        return String(val)
          .replace(/_/g, " ")
          .toLowerCase()
          .replace(/\b\w/g, (ch) => ch.toUpperCase());
      },
    },
    // =======================
    // Bill Sundry Type
    // =======================
    {
      title: "Bill Sundry Type",
      field: "bill_sundry_type",
      width: savedColWidths.bill_sundry_type || 200,
      headerSort: true,
      sorter: "string",
      resizable: true,
      formatter: (cell) => {
        const val = cell.getValue();
        if (val == null) return "";
        // convert to lowercase then capitalize first letter of each word
        return String(val)
          .replace(/_/g, " ")
          .toLowerCase()
          .replace(/\b\w/g, (ch) => ch.toUpperCase());
      },
    },

    // =======================
    // Bill Sundry Nature
    // =======================
    {
      title: "Bill Sundry Nature",
      field: "bill_sundry_nature",
      width: savedColWidths.bill_sundry_nature || 200,
      headerSort: true,
      sorter: "string",
      resizable: true,
      formatter: (cell) => {
        const val = cell.getValue();
        if (val == null) return "";
        // convert to lowercase then capitalize first letter of each word
        return String(val)
          .replace(/_/g, " ")
          .toLowerCase()
          .replace(/\b\w/g, (ch) => ch.toUpperCase());
      },
    },
    // =======================
    // Status (Active/Inactive)
    // =======================
    {
      title: "Status",
      field: "status",
      width: savedColWidths.status || 120,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      resizable: true,
      formatter: (cell) =>
        cell.getValue()
          ? `<span class="badge bg-teal-lt">Active</span>`
          : `<span class="badge bg-danger-lt">Inactive</span>`,
    },

    // =======================
    // Created By
    // =======================
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
          ? `<span class="badge bg-danger-lt ">${creatorName}</span>`
          : `<span class="badge bg-cyan-lt">System</span>`;
      },
    },

    // =======================
    // Updated By
    // =======================
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
        return updaterName
          ? `<span class="badge bg-danger-lt ">${updaterName}</span>`
          : `<span class="badge bg-cyan-lt">--</span>`;
      },
    },

    // =======================
    // Action Buttons
    // =======================
    {
      title: "Actions",
      field: "actions",
      width: 150,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      frozen: true,
      formatter: (cell) => {
        const rowData = cell.getData();
        const canView = currentPermissions.view || false;
        const canEdit = currentPermissions.update || false;
        const canDelete = currentPermissions.delete || false;

        const viewIcon = icons.view;
        const editIcon = icons.edit;
        const deleteIcon = icons.delete;

        let actions = '<div class="d-flex gap-2 justify-content-center">';

        if (canView) {
          actions += `
            <span 
              data-module="masters/bill-sundry/modal"
              data-init="openBillSundryViewModal"
              class="erp-btn-icon view js-load-modal"
              data-id="${rowData.id}"
              title="View Record">
              ${viewIcon}
            </span>`;
        }

        if (canEdit) {
          actions += `
            <span 
              data-module="masters/bill-sundry/modal"
              data-init="openBillSundryEditModal"
              class="erp-btn-icon edit js-load-modal"
              data-id="${rowData.id}"
              title="Edit Record">
              ${editIcon}
            </span>`;
        }

        if (canDelete) {
          actions += `
            <span 
              class="erp-btn-icon delete"
              data-id="${rowData.id}"
              title="Delete Record">
              ${deleteIcon}
            </span>`;
        }

        actions += "</div>";
        return actions || '<span class="text-muted">No actions</span>';
      },
    },
  ];
}

let currentFilter = {};
let currentPermissions = {};
let savedColWidths = {};
let table = null;
let totalFilteredRecords = 0;
let grandTotalRecords = 0;



// function bindModalEvents() {
//   $("#bill_sundry_modal").on("shown.bs.modal", function () {
//     $('#name').focus();

//     const select2Array  = ['bill_sundry_type', 'bill_sundry_nature', 'bill_sundry_amount_round_off', 'calculation_type', 'apply_on',

//     'purchase_adjust_in_amount',
//     'purchase_account_type',
//     'purchase_account_id',
//     'purchase_adjust_in_party_amount',
//     'purchase_party_account_type',
//     'purchase_party_account_id',
//     'purchase_post_over_and_above','sale_adjust_in_amount',
//     'sale_account_type',
//     'sale_account_id',
//     'sale_adjust_in_party_amount',
//     'sale_party_account_type',
//     'sale_party_account_id',
//     'sale_post_over_and_above'
//     ];

//     for (let index = 0; index < select2Array.length; index++) {
//       const element = select2Array[index];
//       $(`#${element}`).select2({
//         theme: "bootstrap-5",
//         dropdownParent: $("#bill_sundry_modal"),
//       });
//     }

//     $(document).on("select2:open", function (e) {
//         const selectElement = $(e.target);
//         const searchInput = selectElement
//         .data("select2")
//         .$dropdown.find(".select2-search__field");
//         searchInput
//         .off("keydown.select2Enter")
//         .on("keydown.select2Enter", (event) => {
//             if (event.which === 13) {
//             event.preventDefault();
//             selectElement.select2("close");
//             moveFocusToNextField(selectElement);
//             }
//         });
//     });
//   });
// }

function bindModalEvents() {
  $("#bill_sundry_modal").on("shown.bs.modal", function () {
    $('#name').focus();

    const select2Array = [
      'bill_sundry_type', 'bill_sundry_nature', 'bill_sundry_amount_round_off', 
      'calculation_type', 'apply_on',
      'purchase_adjust_in_amount', 'purchase_account_type', 'purchase_account_id',
      'purchase_adjust_in_party_amount', 'purchase_party_account_type', 'purchase_party_account_id',
      'purchase_post_over_and_above',
      'sale_adjust_in_amount', 'sale_account_type', 'sale_account_id',
      'sale_adjust_in_party_amount', 'sale_party_account_type', 'sale_party_account_id',
      'sale_post_over_and_above'
    ];

    for (let index = 0; index < select2Array.length; index++) {
      const element = select2Array[index];
      const $el = $(`#${element}`);
      if ($el.length && $el.is('select')) {
          $el.select2({
            theme: "bootstrap-5",
            dropdownParent: $("#bill_sundry_modal"),
          });
      }
    }

    // ✅ Apply form_mode visibility — works for BOTH create and edit
    let formMode = document.getElementById('form_mode').value;
    if (formMode === 'create') {
      document.getElementById('applyOn').style.display = 'none';
      document.getElementById('purchase_account_section').className = 'd-none';
      document.getElementById('purchase_account_type_section').className = 'd-none';
      document.getElementById('purchase_party_account_section').className = 'd-none';
      document.getElementById('purchase_party_account_type_section').className = 'd-none';
      document.getElementById('sale_account_section').className = 'd-none';
      document.getElementById('sale_account_type_section').className = 'd-none';
      document.getElementById('sale_party_account_section').className = 'd-none';
      document.getElementById('sale_party_account_type_section').className = 'd-none';
      document.getElementById('purchase_post_over_above_section').className = 'd-none';
      document.getElementById('sale_post_over_above_section').className = 'd-none';
      document.getElementById("purchase_adjust_in_amount").value = 1;
      document.getElementById("purchase_adjust_in_party_amount").value = 1;
      document.getElementById('purchase_post_over_and_above').value = 0;
      document.getElementById("sale_adjust_in_amount").value = 1;
      document.getElementById("sale_adjust_in_party_amount").value = 1;
      document.getElementById('sale_post_over_and_above').value = 1;
    } else if (formMode === 'edit') {
      // ✅ On edit, run selectBasedChange immediately to reflect saved values
      selectBasedChange();
    }

    // ✅ Name → Print Name sync
    $('#name').off('input.printSync').on('input.printSync', function () {
      const printEl = document.getElementById("print_name");
      if (printEl) printEl.value = this.value || "";
    });

    // ✅ Select2 change listeners — now inside bindModalEvents, runs for create AND edit
    // Use .off() first to prevent duplicate bindings on re-open
    $('#calculation_type').off('select2:select select2:unselect').on('select2:select select2:unselect', function () {
      if (this.value === 'percentage') {
        document.getElementById('applyOn').className = 'd-block';
      } else {
        document.getElementById('applyOn').className = 'd-none';
        clearSelect2('apply_on');
      }
    });

    const selectBasedChangeIds = [
      'purchase_adjust_in_amount',
      'purchase_account_type',
      'purchase_adjust_in_party_amount',
      'purchase_party_account_type',
      'sale_adjust_in_amount',
      'sale_account_type',
      'sale_adjust_in_party_amount',
      'sale_party_account_type'
    ];

    selectBasedChangeIds.forEach(function (id) {
      $(`#${id}`).off('select2:select select2:unselect').on('select2:select select2:unselect', function () {
        selectBasedChange();
      });
    });

    // select2 Enter key behavior
    $(document).off("select2:open.billSundry").on("select2:open.billSundry", function (e) {
      const selectElement = $(e.target);
      const searchInput = selectElement.data("select2").$dropdown.find(".select2-search__field");
      searchInput.off("keydown.select2Enter").on("keydown.select2Enter", (event) => {
        if (event.which === 13) {
          event.preventDefault();
          selectElement.select2("close");
          moveFocusToNextField(selectElement);
        }
      });
    });
  });
}

$(document).ready(function () {

  // Initialize select2 for filter
  $('#bill_sundry_type_id').select2({
      theme: 'bootstrap-5',
      placeholder: 'Select an option',      
      width: '100%',                 
  });

    // Get Modal for create new bill Sundry
  $(".js-load-modal").on("click", function (event) {
    $.ajax({
      url: billSundryCreateUrl,
      beforeSend: function () {
        showLoader("Loading Bill Sundry Form...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        bindModalEvents();
        $("#bill_sundry_modal").modal("show");
      },
      error: function (xhr) {
        showToast("error", 'Oops! Something went wrong. Try again later.');
        console.error(xhr.responseText);
      },
      complete: function () {
        hideLoader();
      },
    });


 document.addEventListener("input", function (e) {
    const target = e.target;
    if (!target || target.id !== "name") return;

    const value = target.value || "";
    const printEl = document.getElementById("print_name");
    if (printEl) printEl.value = value ? value : "";
  });
      
});

  // Account List
  table = new Tabulator("#bill_sundry_table", {
    dataTree: true,
    height: "550px", // dynamic height that fits most ERP screens
    layout: "fitColumns",
    dataTreeStartExpanded: true,  
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
    paginationSize: parseInt(localStorage.getItem("bill_sundry_page_size")) || 50,
    paginationInitialPage: parseInt(localStorage.getItem("bill_sundry_page_num")) || 1,
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

    ajaxURL: billSundryListUrl,
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
    paginationCounter: function(pageSize, currentRow, _currentPage, totalRows, _totalPages) {
      const total = totalFilteredRecords || totalRows;
      if (!total) return "";
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
    localStorage.setItem("bill_sundry_page_num", pageNo);
    localStorage.setItem("bill_sundry_page_size", table.getPageSize());
  });


    // View Account
  $(document).on("click", ".view", function (event) {
    const billSundryId = $(this).data("id");
    const url = billSundryViewUrl.replace(":id", billSundryId);
    $.ajax({
      url: url,
      beforeSend: function () {
        showLoader("Loading Bill Sundry Form...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        table.replaceData();
        bindModalEvents();
        $("#bill_sundry_modal").modal("show");
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

    // Edit Account
  $(document).on("click", ".edit", function (event) {
    const billSundryId = $(this).data("id");
    const url = billSundryEditUrl.replace(":id", billSundryId);
    $.ajax({
      url: url,
      beforeSend: function () {
        showLoader("Loading Bill Sundry Form...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        table.replaceData();
        bindModalEvents();
        $("#bill_sundry_modal").modal("show");
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

  // Delete Account
  $(document).on("click", ".delete", function (event) {
    const billSundryId = $(this).data("id");
    const url = billSundryDeleteUrl.replace(":id", billSundryId);

    Swal.fire({
      title: "Are you sure?",
      text: "You won't be able to revert this!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      confirmButtonText: "Yes, delete it!",
      cancelButtonText: "No, cancel!",
      reverseButtons: false,
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: url,
          type: "DELETE",
          beforeSend: function () {
            showLoader("Deleting Account...");
          },
          success: function (response) {
            showToast("success", response.message || "Account deleted successfully!");
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
        printReport(route, { format: format, currentFilter });
        break;

      case "excel":
        downloadExcel(route, { currentFilter });
        break;
    }
  });
  
});

document.addEventListener("DOMContentLoaded", function () {
 
  const searchEl = document.getElementById("search");
  const applyFilterBtn = document.getElementById("filter_apply");
  const clearFilterBtn = document.getElementById("filter_clear");

  // Apply Filter
  if (applyFilterBtn) applyFilterBtn.addEventListener("click", applyFilter);

  // Clear Filter
  if (clearFilterBtn) clearFilterBtn.addEventListener("click", clearFilter);

  // Apply filter on Enter key
  if (searchEl) {
    searchEl.addEventListener("keypress", (e) => {
      if (e.key === "Enter") applyFilter();
    });
  }

  // -----------------------------------
  // Load Saved Filters
  // -----------------------------------
  const savedFilter = localStorage.getItem("bill_sundry_filter");
  if (savedFilter) {
    currentFilter = JSON.parse(savedFilter);
    if (currentFilter.filter_type_id) {
      $('#group_id').val(currentFilter.filter_type_id).trigger('change');
    }
    if (currentFilter.filter_type_id) {
      $('#bill_sundry_type_id').val(currentFilter.filter_type_id).trigger('change');
    }
    if (currentFilter.search && searchEl) {
      searchEl.value = currentFilter.search;
    }
  }
  
  dynamicFilter("#search, #bill_sundry_type_id"); 
 
});


 /**
 * Apply current filter to the table.
 * Stores selected filters in localStorage for persistence.
 */
function applyFilter() {
  const groupEl = document.getElementById("bill_sundry_type_id");
  const searchEl = document.getElementById("search");

  const groupVal = groupEl?.value || "";
  const searchVal = searchEl?.value.trim() || "";

  currentFilter = {};
  if (groupVal) currentFilter.filter_type_id = groupVal;
  if (searchVal) currentFilter.search = searchVal;

  // Save filter settings locally
  localStorage.setItem("bill_sundry_filter", JSON.stringify(currentFilter));

  // Reload table with new filter applied
  if (table) table.setData();
}

/**
 * Clear all applied filters.
 * Resets filter UI elements and clears saved localStorage filters.
 */
function clearFilter() {
  const groupEl = document.getElementById("bill_sundry_type_id");
  const searchEl = document.getElementById("search");

  $('#bill_sundry_type_id').val("").trigger("change");
  if (searchEl) searchEl.value = "";

  currentFilter = {};
  localStorage.removeItem("bill_sundry_filter");

  if (table) table.setData();
}


function selectBasedChange(){
  // Purchase Detail tags
  const adjustInPurchase=document.getElementById('purchase_adjust_in_amount').value;
  const purchaseAccountType=document.getElementById('purchase_account_type').value;
  const purchaseAdjustInPartyAmount=document.getElementById('purchase_adjust_in_party_amount').value;
  const purchasePartyAccountType=document.getElementById('purchase_party_account_type').value;
  // Sale Detail tags
  const adjustInSale=document.getElementById('sale_adjust_in_amount').value;
  const saleAccountType=document.getElementById('sale_account_type').value;
  const saleAdjustInPartyAmount=document.getElementById('sale_adjust_in_party_amount').value;
  const salePartyAccountType=document.getElementById('sale_party_account_type').value;
  
  // --- Purchase Adjust in Amount ---
  let purchaseAccountSectionClass = '';
  let purchaseAccountTypeSectionClass = '';
  let purchasePartyAccountSectionClass = '';
  let purchasePartyAccountTypeSectionClass = '';
  let saleAccountSectionClass = '';
  let saleAccountTypeSectionClass = '';
  let salePartyAccountSectionClass = '';
  let salePartyAccountTypeSectionClass = '';
  let purchasePostOverAboveSectionClass = '';
  let salePostOverAboveSectionClass = '';

  // Purchase Adjust in Amount
  if (adjustInPurchase == false) {
    purchaseAccountTypeSectionClass = '';
    purchaseAccountSectionClass = 'd-none';
      if (purchaseAccountType === 'specify_account_in_voucher') {
        let acc_id= document.getElementById('purchase_account_id');
        acc_id.value="";
        // let tom_ins=acc_id.tomselect;
        // tom_ins.clear();
        // clearSelect2('purchase_account_id');
        purchaseAccountSectionClass = 'd-none';
      }else if (purchaseAccountType === 'specify_account') {
        purchaseAccountSectionClass = '';
      }
  } else {
      purchaseAccountSectionClass = 'd-none';
      purchaseAccountTypeSectionClass = 'd-none';
      let acc_type=document.getElementById('purchase_account_type');
      acc_type.value="";
      // let tom_ins=acc_type.tomselect;
      // tom_ins.clear();
    //   clearSelect2('purchase_account_type');
      let acc_id= document.getElementById('purchase_account_id');
      acc_id.value="";
      // let tom_ins_acc_id=acc_id.tomselect;
      // tom_ins_acc_id.clear();
    //   clearSelect2('purchase_account_id');
    }

  // Purchase Adjust in Party Amount
  if (purchaseAdjustInPartyAmount == false) {
      purchasePartyAccountTypeSectionClass = '';
      purchasePartyAccountSectionClass = 'd-none';
      if (purchasePartyAccountType === 'specify_account_in_voucher') {
        let acc_id= document.getElementById('purchase_party_account_id');
        acc_id.value="";
        // let tom_ins=acc_id.tomselect;
        // tom_ins.clear();
        // clearSelect2('purchase_party_account_id');
        purchasePartyAccountSectionClass = 'd-none';
      }else if(purchasePartyAccountType === 'specify_account'){
        purchasePartyAccountSectionClass = '';
      }
  } else {
      purchasePartyAccountSectionClass = 'd-none';
      purchasePartyAccountTypeSectionClass = 'd-none';
      let acc_type=document.getElementById('purchase_party_account_type');
      acc_type.value="";
      // let tom_ins=acc_type.tomselect;
      // tom_ins.clear();
    //   clearSelect2('purchase_party_account_type');
      let acc_id= document.getElementById('purchase_party_account_id');
      acc_id.value="";
      // let tom_ins_acc_id=acc_id.tomselect;
      // tom_ins_acc_id.clear();
    //   clearSelect2('purchase_party_account_id');
  }

  // Sale Adjust in Amount
  if (adjustInSale == false) {
      saleAccountTypeSectionClass = '';
      saleAccountSectionClass = 'd-none';
      if (saleAccountType === 'specify_account_in_voucher') {
        let acc_id= document.getElementById('sale_account_id');
        acc_id.value="";
        // let tom_ins=acc_id.tomselect;
        // tom_ins.clear();
        // clearSelect2('sale_account_id');
        saleAccountSectionClass = 'd-none';
      }else if(saleAccountType === 'specify_account'){
        saleAccountSectionClass = ''; 
      }
  } else {
      saleAccountSectionClass = 'd-none';
      saleAccountTypeSectionClass = 'd-none';
      let acc_type=document.getElementById('sale_account_type');
      acc_type.value="";
      // let tom_ins=acc_type.tomselect;
      // tom_ins.clear();
    //   clearSelect2('sale_account_type');
      let acc_id= document.getElementById('sale_account_id');
      acc_id.value="";
      // let tom_ins_acc_id=acc_id.tomselect;
      // tom_ins_acc_id.clear();
    //   clearSelect2('sale_account_id');
  }

  // Sale Adjust in Party Amount
  if (saleAdjustInPartyAmount == false) {
      salePartyAccountTypeSectionClass = '';
      salePartyAccountSectionClass = 'd-none';
      if (salePartyAccountType === 'specify_account_in_voucher') {
        let acc_id= document.getElementById('sale_party_account_id');
        acc_id.value="";
        // let tom_ins=acc_id.tomselect;
        // tom_ins.clear();
        // clearSelect2('sale_party_account_id');
        salePartyAccountSectionClass = 'd-none';
      }else if(salePartyAccountType === 'specify_account'){
        salePartyAccountSectionClass = '';
      }
  } else {
      salePartyAccountSectionClass = 'd-none';
      salePartyAccountTypeSectionClass = 'd-none';
      let acc_type=document.getElementById('sale_party_account_type');
      acc_type.value="";
      // let tom_ins=acc_type.tomselect;
      // tom_ins.clear();
    //   clearSelect2('sale_party_account_type');
      let acc_id= document.getElementById('sale_party_account_id');
      acc_id.value="";
      // let tom_ins_acc_id=acc_id.tomselect;
      // tom_ins_acc_id.clear();
    //   clearSelect2('sale_party_account_id');
  }

  // Purchase Post Over and Above Section
  if ((adjustInPurchase == false && purchaseAdjustInPartyAmount == false)) {
    purchasePostOverAboveSectionClass = 'd-none';
    document.getElementById('purchase_post_over_and_above').value=1;
  }else if((adjustInPurchase == true && purchaseAdjustInPartyAmount == true)){
    purchasePostOverAboveSectionClass = 'd-none';
    document.getElementById('purchase_post_over_and_above').value=0;
  }else {
    purchasePostOverAboveSectionClass = '';
    document.getElementById('purchase_post_over_and_above').value=0;
  }

  // Sale Post Over and Above Section
  if ((adjustInSale == false && saleAdjustInPartyAmount == false)){
    salePostOverAboveSectionClass = 'd-none';
    document.getElementById('sale_post_over_and_above').value=1;
  }else if((adjustInSale == true && saleAdjustInPartyAmount == true)){
    salePostOverAboveSectionClass = 'd-none';
    document.getElementById('sale_post_over_and_above').value=0;
  }else{
    salePostOverAboveSectionClass = '';
    document.getElementById('sale_post_over_and_above').value=1;
  }

  // ✅ Now apply these classes dynamically to the HTML elements if needed:
  document.getElementById('purchase_account_section').className=purchaseAccountSectionClass;
  document.getElementById('purchase_account_type_section').className=purchaseAccountTypeSectionClass;
  document.getElementById('purchase_party_account_section').className = purchasePartyAccountSectionClass;
  document.getElementById('purchase_party_account_type_section').className = purchasePartyAccountTypeSectionClass;
  document.getElementById('sale_account_section').className = saleAccountSectionClass;
  document.getElementById('sale_account_type_section').className = saleAccountTypeSectionClass;
  document.getElementById('sale_party_account_section').className = salePartyAccountSectionClass;
  document.getElementById('sale_party_account_type_section').className = salePartyAccountTypeSectionClass;
  document.getElementById('purchase_post_over_above_section').className = purchasePostOverAboveSectionClass;
  document.getElementById('sale_post_over_above_section').className = salePostOverAboveSectionClass;
}