let godownUnitLocationTable;
let currentPermissions = {};
let currentFilter = {};
let totalFilteredRecords = 0;
let grandTotalRecords = 0;

/**
 * Generate and return the column configuration for the table.
 */
function getTableColumns() {
    return [
       { 
            title: "No",
            field: "no",
            width: 80, 
            headerSort: true, 
            resizable: true,
        },
        { 
            title: "Destination Name",
            field: "destination_name", 
            width: 250, 
            headerSort: true, 
            sorter: "string", 
            resizable: true 
        },
        { 
            title: "Godown Name", 
            field: "godown_name", 
            width: 250, 
            headerSort: true, 
            sorter: "string", 
            resizable: true 
        },
        { 
            title: "Username", 
            field: "username", 
            width: 200, 
            headerSort: false, 
            resizable: true ,
        },
        {
            title: "Status",
            field: "status",
            width: 120,
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
            resizable: true,
            formatter: (cell) =>
                cell.getValue() == 1
                    ? `<span class="badge bg-teal-lt">Active</span>`
                    : `<span class="badge bg-danger-lt">Inactive</span>`,
        },
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
                const canView = currentPermissions.view || true; // Set to true if permissions not yet implemented
                const canEdit = currentPermissions.update || true;
                const canDelete = currentPermissions.delete || true;

                const viewIcon = icons.view;
                const editIcon = icons.edit;
                const deleteIcon = icons.delete;

                let actions = '<div class="d-flex gap-2 justify-content-center">';

                // if (canView) {
                //     actions += `
                //         <span 
                //             class="erp-btn-icon view js-view-item"
                //             data-id="${rowData.id}"
                //             title="View Record">
                //             ${viewIcon}
                //         </span>`;
                // }

                if (canEdit) {
                    actions += `
                        <span 
                            class="erp-btn-icon edit js-edit-item"
                            data-id="${rowData.id}"
                            title="Edit Record">
                            ${editIcon}
                        </span>`;
                }

                if (canDelete) {
                    actions += `
                        <span 
                            class="erp-btn-icon delete js-delete-item"
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
    const searchEl = document.getElementById("search");
    const godownUnitLocationEl = document.getElementById("godown_unit_location_id");

    const searchVal = searchEl?.value.trim() || "";
    const godownUnitLocationVal = godownUnitLocationEl?.value || "";

    currentFilter = {};
    if (searchVal) currentFilter.search = searchVal;
    if (godownUnitLocationVal) currentFilter.godown_unit_location_id = godownUnitLocationVal;

    if (godownUnitLocationTable) godownUnitLocationTable.setData();
}

function clearFilter() {
    const searchEl = document.getElementById("search");
    const godownUnitLocationEl = document.getElementById("godown_unit_location_id");

    if (searchEl) searchEl.value = "";
    if (godownUnitLocationEl) $("#godown_unit_location_id").val("").trigger("change");

    currentFilter = {};
    if (godownUnitLocationTable) godownUnitLocationTable.setData();
}

/**
 * Bind events inside dynamically loaded modals
 */
function bindModalEvents() {
    $("#godown_unit_location_modal").on("shown.bs.modal", function () {
        const select2Array = ['destination_id', 'user_id', 'status'];

        for (let index = 0; index < select2Array.length; index++) {
            const element = select2Array[index];
            $(`#${element}`).select2({
                theme: "bootstrap-5",
                dropdownParent: $("#godown_unit_location_modal"),
            });
        }
        $('#destination_id').focus();
    });
}

$(function () {
    // Initialize Tabulator
    godownUnitLocationTable = new Tabulator("#godown_unit_location_table", {
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
        paginationSize: 50,
        ajaxURL: godownUnitLocationListRoute,
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
        columns: getTableColumns(),
    });

    // Register table globally if needed
    if (typeof registerTable === "function") {
        registerTable("godown_unit_location_table", godownUnitLocationTable);
    }

    // Filter Listeners
    $("#filter_apply").on("click", applyFilter);
    $("#filter_clear").on("click", clearFilter);
    $("#search").on("keypress", (e) => {
        if (e.key === "Enter") {
            e.preventDefault();
            applyFilter();
        }
    });

  // Select2 enter-key navigation (Generic handled in main.js or here if specific)
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
          if (typeof moveFocusToNextField === "function") {
            moveFocusToNextField(selectElement);
          }
        }
      });
  });
    // Load Create Modal
    $(document).on("click", ".js-load-modal", function (event) {
        event.preventDefault();
        const initFunc = $(this).data('init');
        $.ajax({
            url: godownUnitLocationCreateUrl, 
            beforeSend: function () {
                showLoader("Loading Weight Location Form...");
            },
            success: function (response) {
                $("#global_modal_container").html(response.data.html);
                bindModalEvents();
                $("#godown_unit_location_modal").modal("show");
            },
            error: function (xhr) {
                showToast("error", 'Oops! Something went wrong.');
            },
            complete: function () {
                hideLoader();
            },
        });
    });

    // Edit Item
    $(document).on("click", ".js-edit-item", function (event) {
        const id = $(this).data("id");
        const url = godownUnitLocationEditUrl.replace(':id', id);
        $.ajax({
            url: url,
            beforeSend: function () {
                showLoader("Loading Weight Location Form...");
            },
            success: function (response) {
                $("#global_modal_container").html(response.data.html);
                bindModalEvents();
                $("#godown_unit_location_modal").modal("show");
            },
            error: function (xhr) {
                showToast("error", 'Oops! Something went wrong.');
            },
            complete: function () {
                hideLoader();
            },
        });
    });

    // // View Item
    // $(document).on("click", ".js-view-item", function (event) {
    //     const id = $(this).data("id");
    //     const url = weightLocationShowUrl.replace(':id', id);
    //     $.ajax({
    //         url: url,
    //         beforeSend: function () {
    //             showLoader("Loading Weight Location Details...");
    //         },
    //         success: function (response) {
    //             $("#global_modal_container").html(response.data.html);
    //             bindModalEvents();
    //             $("#weight_location_modal").modal("show");
    //         },
    //         error: function (xhr) {
    //             showToast("error", 'Oops! Something went wrong.');
    //         },
    //         complete: function () {
    //             hideLoader();
    //         },
    //     });
    // });

    // Delete Item
    $(document).on("click", ".js-delete-item", function (event) {
        const id = $(this).data("id");
        const url = weightLocationUpdateUrl.replace(':id', id).replace('/update', '/destroy');

        Swal.fire({
            title: "Are you sure?",
            text: "This record will be deleted!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: "DELETE",
                    data: { _token: csrfToken },
                    success: function (response) {
                        showToast("success", response.message || "Deleted successfully!");
                        weightLocationTable.replaceData();
                    },
                    error: function (xhr) {
                        showToast("error", 'Failed to delete record.');
                    }
                });
            }
        });
    });
});



