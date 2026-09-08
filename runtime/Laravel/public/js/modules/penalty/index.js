let table; 
let currentFilter = {};
let currentPermissions = {};
let savedColWidths = {};

const FILTER_KEY = "penalty_filter";
const WIDTH_KEY = "penalty_col_widths";

const filterFields = [
    "item_id",    
];

function bindModalEvents() {
  $("#penalty_modal").on("shown.bs.modal", function () {
   
    const select2Array = ['penalty_item_id'];

    for (let index = 0; index < select2Array.length; index++) {
      const element = select2Array[index];
      $(`#${element}`).select2({
        theme: "bootstrap-5",
        dropdownParent: $("#penalty_modal"),
      });
    }

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
  });
}

$(document).ready(function() {       
    bindSelect2();
    restoreFilters();

  $("#addPenaltyBtn").on("click", function (event) {
    $.ajax({
      url: penaltyCreateUrl,
      beforeSend: function () {
        showLoader("Loading Penalty Form...");
      },
      success: function (response) {
        $("#global_modal_container").html(response.data.html);
        bindModalEvents();
        $("#penalty_modal").modal("show");
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

    table = new Tabulator("#penalty_table", {
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
        paginationSize: 50,
        paginationSizeSelector: [10, 20, 50, 100, 300, true],
        paginationDataSent: {
            page: "page",
            size: "size",
        },
        paginationDataReceived: {
            last_page: "last_page",
            data: "data",
            total: "total",
        },
        ajaxURL: penaltyListUrl,
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
            console.log('Ajax response received:', response);
            if (response.permissions) currentPermissions = response.permissions;           
            window.recordsInCurrentPage = response.data ? response.data.length : 0;
            return response;
        },
        columns: getTableColumns(savedColWidths),
    });

    // Trigger filter application on form elements change or apply button click
    $("#filter_apply").on("click", function(e){
        e.preventDefault();             
        applyFilter();
    });

    $("#filter_clear").on("click", function (e) {
        e.preventDefault();
        clearFilter();
    }); 

    // View Penalty
    $(document).on("click", ".view", function (event) {
      const penaltyId = $(this).data("id");
      const url = penaltyViewUrl.replace(":id", penaltyId);
      $.ajax({
        url: url,
        beforeSend: function () {
          showLoader("Loading Penalty Details...");
        },
        success: function (response) {
          console.log(response.data.html);
          $("#global_modal_container").html(response.data.html);
          bindModalEvents();
          $("#penalty_modal").modal("show");
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

    // Edit Penalty
    $(document).on("click", ".edit", function (event) {
      const penaltyId = $(this).data("id");
      const url = penaltyEditUrl.replace(":id", penaltyId);
      $.ajax({
        url: url,
        beforeSend: function () {
          showLoader("Loading Penalty Form...");
        },
        success: function (response) {
          $("#global_modal_container").html(response.data.html);
          bindModalEvents();
          $("#penalty_modal").modal("show");
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

    // Delete Penalty
    $(document).on("click", ".delete", function (event) {
      const penaltyId = $(this).data("id");
      const url = penaltyDeleteUrl.replace(":id", penaltyId);

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
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function () {
              showLoader("Deleting Penalty...");
            },
            success: function (response) {
              showToast("success", response.message || "Penalty deleted successfully!");
              if (table) table.replaceData();
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
});

function bindSelect2() {
        const selectIdArray = ['#item_id'];
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

function getTableColumns(savedColWidths) {
    return [
        {
          title: "Sr No",
          field: "sr_no",
          width: savedColWidths.sr_no ?? 130,
          headerSort: false,
          hozAlign: "center",
          headerHozAlign: "center",
          sorter: "number",
          resizable: true,
        },
        {
          title: "Item Name",
          field: "item_name",
          width: savedColWidths.item_name ?? 450,
          headerSort: false,
          hozAlign: "left",
          headerHozAlign: "left",
          sorter: "string",
          resizable: true,
        },
        {
          title: "Amount",
          field: "amount",
          width: savedColWidths.amount ?? 150,
          headerSort: false,
          hozAlign: "right",
          headerHozAlign: "right",
          sorter: "number",
          resizable: true,
          formatter: (cell) => cell.getValue() ? parseFloat(cell.getValue()).toFixed(2) : "0.00"
        },
        {
          title: "Actions",
          field: "actions",
          width: 160,
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
                  class="erp-btn-icon view"
                  data-id="${rowData.id}"
                  title="View Record">
                  ${viewIcon}
                </span>`;
            }

            if (canEdit) {
              actions += `
                <span
                  class="erp-btn-icon edit"
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


function applyFilter() {
    currentFilter = {};
 
    $.each(filterFields, function (i, id) {
        const el = $("#" + id);
        const value = el.val();
        if (value) currentFilter[id] = value;
    });
 
    localStorage.setItem(FILTER_KEY, JSON.stringify(currentFilter));
    if (table) table.setData(penaltyListUrl);
}

function clearFilter() {
    $.each(filterFields, function (i, id) {
        const el = $("#" + id);
        el.val("").trigger("change");
    });
 
    currentFilter = {};
    localStorage.removeItem(FILTER_KEY);
    if (table) table.setData(penaltyListUrl);
}

function restoreFilters() {
    const saved = localStorage.getItem(FILTER_KEY);
    if (!saved) return;
 
    currentFilter = JSON.parse(saved);
    $.each(currentFilter, function (id, value) {
        const el = $("#" + id);
        if (el.length) {
            el.val(value).trigger("change");
        }
    });    
}