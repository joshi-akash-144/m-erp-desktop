
let table;
let currentFilter = {};
let currentPermissions = {};

$(document).ready(function () {
    // Initialize Tabulator
    table = new Tabulator("#permission_table", {
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
        paginationSizeSelector: [10, 20, 50, 100, 300],
        paginationDataSent: {
            page: "page",
            size: "size",
        },
        paginationDataReceived: {
            last_page: "last_page",
            data: "data",
            total: "total",
        },
        ajaxURL: permissionsListUrl,
        ajaxParams: () => currentFilter,
        ajaxURLGenerator: (url, config, params) => {
            const page = params.page || 1;
            const size = params.size || 50;
            const queryParams = new URLSearchParams({ ...currentFilter, page, size });
            return `${url}?${queryParams.toString()}`;
        },
        ajaxResponse: function (url, params, response) {
            if (response.permissions) {
                currentPermissions = response.permissions;
            }
            return response;
        },
        columns: [
            {
                title: "No",
                field: "no",
                width: 80,
                hozAlign: "center",
                headerHozAlign: "center",
                headerSort: false,
                resizable: false,
                formatter: "rownum",
                frozen: true,
            },
            {
                title: "Module",
                field: "module",
                width: 300,
                headerSort: true,
            },
            {
                title: "Permission Name",
                field: "name",
                width: 300,
                headerSort: true,
                // formatter: (cell) => {
                //     const value = cell.getValue();
                //     const moduleName = cell.getData().module;
                //     // console.log(moduleName);
                //     if (value && moduleName && value.startsWith(moduleName + '.')) {
                //         return value.substring(moduleName.length + 1);
                //     }
                //     return value;
                // }
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

                    // const canView = currentPermissions.view || false;
                    // const canEdit = currentPermissions.update || false; 
                    // const canDelete = currentPermissions.delete || false;

                    const viewIcon = icons.view;
                    const editIcon = icons.edit;
                    const deleteIcon = icons.delete;

                    let actions = '<div class="d-flex gap-2 justify-content-center">';
                    // if (canView) {
                        actions += `
                            <button type="button" class="erp-btn-icon erp-btn-icon view" data-id="${rowData.id}" title="View Permission">
                                ${viewIcon}
                            </button>
                        `;
                    // }
                    // if(canEdit){
                    actions += `
                        <button type="button" class="erp-btn-icon erp-btn-icon edit" data-id="${rowData.id}" title="Edit Permission">
                            ${editIcon}
                        </button>
                    `;
                    // }
                    // if(canDelete){
                    actions += `
                        <button type="button" class="erp-btn-icon erp-btn-icon delete" data-id="${rowData.id}" title="Delete Permission">
                            ${deleteIcon}
                        </button>
                    `;
                    // }
                    actions += "</div>";
                    return actions;
                },
            },
        ],
    });

    // Filtering
    $('#filter_apply').on('click', applyFilter);
    $('#filter_clear').on('click', clearFilter);
    $('#search').on('keypress', function (e) {
        if (e.key === "Enter") applyFilter();
    });

    // Add Permission Modal Open
    $('#add_permission_btn').on('click', function () {        
        $.ajax({
            url: permissionCreateUrl,
            type: 'GET',
            success: function (res) {
                $('#global_modal_container').html(res.html);
                $('#permissionModal').on('shown.bs.modal', function () {
                    $(this).find('input[name="module_name"]').focus();
                    if (typeof window.initPermissionValidation === 'function') {
                        window.initPermissionValidation();
                    }
                });
                $('#permissionModal').modal('show');
            }
        });
    });

    // Edit Permission Modal Open
    $(document).on('click', '.edit', function () {
        const id = $(this).data('id');
        const url = permissionEditUrl.replace(':id', id);

        $.ajax({
            url: url,
            type: 'GET',
            success: function (res) {
                if (res.success === false) {
                    Swal.fire('Error!', res.message, 'error');
                    return;
                }
                $('#global_modal_container').html(res.html);
                $('#permissionModal').on('shown.bs.modal', function () {
                    $(this).find('input[name="module"]').focus();
                    if (typeof window.initPermissionValidation === 'function') {
                        window.initPermissionValidation();
                    }
                });
                $('#permissionModal').modal('show');
            }
        });
    });

    // View Permission Modal Open
    $(document).on('click', '.view', function () {
        const id = $(this).data('id');
        const url = permissionViewUrl.replace(':id', id);

        $.ajax({
            url: url,
            type: 'GET',
            success: function (res) {
                $('#global_modal_container').html(res.html);
                $('#permissionModal').modal('show');
            }
        });
    });

    // Dynamic Input Handling
    $(document).on('click', '#add_permission_input_btn', function() {
        const container = $('#dynamic_permissions_container');
        const firstRow = container.find('.permission-row').first();
        const newRow = firstRow.clone();
        
        newRow.find('input').val(''); // Clear the value
        newRow.find('.remove-permission-btn').show(); // Show remove button
        
        container.append(newRow);
        
        if (container.find('.permission-row').length > 1) {
            container.find('.permission-row').first().find('.remove-permission-btn').show();
        }
    });

    $(document).on('click', '.remove-permission-btn', function() {
        const container = $('#dynamic_permissions_container');
        if (container.find('.permission-row').length > 1) {
            $(this).closest('.permission-row').remove();
        }
        
        if (container.find('.permission-row').length === 1) {
            container.find('.permission-row').find('.remove-permission-btn').hide();
        }
    });

    // Delete Permission
    $(document).on('click', '.delete', function () {
        const id = $(this).data('id');
        const url = permissionDeleteUrl.replace(':id', id);

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (res) {
                        if (res.success) {
                            Swal.fire('Deleted!', res.message, 'success');
                            table.setData();
                        } else {
                            Swal.fire('Error!', res.message, 'error');
                        }
                    },
                    error: function (xhr) {
                        const msg = xhr.responseJSON?.message || 'Something went wrong.';
                        Swal.fire('Error!', msg, 'error');
                    }
                });
            }
        });
    });

});

function applyFilter() {
    const searchVal = $('#search').val().trim();
    currentFilter = {};
    if (searchVal) currentFilter.search = searchVal;
    
    if (table) table.setData();
}

function clearFilter() {
    $('#search').val('');
    currentFilter = {};
    if (table) table.setData();
}


