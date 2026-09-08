let table;
let currentPermissions = {};

function getTableColumns(savedColWidths = {}) {
    return [
        {
            title: "File Name",
            field: "file_name",
            width: savedColWidths.file_name ?? 420,
            headerHozAlign: "left",
            hozAlign: "left",
            headerSort: false,
        },
        {
            title: "Date",
            field: "date",
            width: savedColWidths.date ?? 200,
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
            formatter: function(cell) {
                const val = cell.getValue() || "";
                return formatDateToDMY(val);
            },
        },
        {
            title: "Time",
            field: "time",
            headerSort: false,
            width: savedColWidths.time ?? 200,
        },
        {
            title: "Days",
            field: "days",
            width: savedColWidths.days ?? 200,
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
            formatter: function(cell) {
                const val = cell.getValue();
                if (val === null || val === undefined) return "";
                const n = parseInt(val);
                if (n === 0) return `<span class="badge bg-success-lt">Today</span>`;
                if (n === 1) return `<span class="badge bg-info-lt">Yesterday</span>`;
                return `<span class="badge bg-secondary-lt">${n} days ago</span>`;
            },
        },
        {
            title: "Actions",
            field: "actions",
            width: 225,
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
            frozen: true,
            formatter: (cell) => {
                const row = cell.getData();
                if (!row.file_name) return "";
                
                const canDownload = currentPermissions.download ?? true;
                const canDelete = currentPermissions.delete ?? true;


                const downloadIcon = icons.download;
                const deleteIcon = icons.delete;

                let actions = '<div class="d-flex gap-2 justify-content-center">';
                if (canDownload) {
                    actions += `
                        <a href="${databaseBackupDownloadUrl.replace(':file', row.file_name)}" target="_blank" class="erp-btn-icon download" title="Download Database">
                            ${downloadIcon}
                        </a>`;
                }
                if (canDelete) {
                    actions += `
                        <span class="erp-btn-icon delete text-danger" data-id="${row.file_name}" title="Delete Database" style="cursor:pointer;">
                            ${deleteIcon}
                        </span>`;
                }
                actions += "</div>";
                return actions;
            },
        },
    ];
}

$(document).ready(function () {
    table = new Tabulator("#database_backup_register_table", {
        height: "550px",
        layout: "fitColumns",
        placeholder: "No backups found.",
        pagination: true,
        paginationMode: "remote",
        paginationSize: 50,
        paginationSizeSelector: [10, 20, 50, 100, 300, true],
        ajaxURL: databaseBackupListUrl,
        ajaxResponse: (url, params, res) => {
            if (res.permissions) currentPermissions = res.permissions;
            return res;
        },
        columns: getTableColumns(),
    });

    // Generate Backup
    $("#generate_backup_btn").on("click", function() {
        Swal.fire({
            title: "Generate Backup?",
            text: "This will create a new SQL dump of the database.",
            icon: "info",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            confirmButtonText: "Yes, proceed!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: databaseBackupStoreUrl,
                    type: "POST",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function () {
                        showLoader("Generating Backup...");
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            showToast("success", response.message);
                            table.replaceData();
                        } else {
                            showToast("error", response.message);
                        }
                    },
                    error: function (xhr) {
                        showToast("error", 'Oops! Something went wrong.');
                        console.error(xhr.responseText);
                    },
                    complete: function () {
                        hideLoader();
                    },
                });
            }
        });
    });

    // Delete Backup
    $(document).on("click", ".delete", function () {
        const file = $(this).data("id");
        const url = databaseBackupDeleteUrl.replace(':file', file);

        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: "DELETE",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function () {
                        showLoader("Deleting Backup...");
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            showToast("success", response.message);
                            table.replaceData();
                        } else {
                            showToast("error", response.message);
                        }
                    },
                    error: function (xhr) {
                        showToast("error", 'Oops! Something went wrong.');
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
