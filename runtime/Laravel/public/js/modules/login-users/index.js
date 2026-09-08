function getTableColumns(savedColWidths = {}) {
    return [
        {
            title: "No.",
            field: "no",
            width: savedColWidths.no ?? 80,
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
            formatter: "rownum"
        },
        {
            title: "Name",
            field: "name",
            width: savedColWidths.name ?? 200,
            headerHozAlign: "left",
            hozAlign: "left",
            headerSort: false,
        },
        {
            title: "Email",
            field: "email",
            width: savedColWidths.email ?? 250,
            headerHozAlign: "left",
            hozAlign: "left",
            headerSort: false,
            formatter: (cell) => {
                const val = cell.getValue();
                return `<span class="text-muted">${val}</span>`;
            }
        },
        {
            title: "Login Time",
            field: "login_at",
            width: savedColWidths.login_at ?? 200,
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
        },
        {
            title: "Log in Company",
            field: "company_name",
            width: savedColWidths.company_name ?? 400,
            hozAlign: "left",
            headerHozAlign: "left",
            headerSort: false,
            formatter: (cell) => {
                const val = cell.getValue();
                if (!val || val === '—') return `<span class="text-muted">—</span>`;
                return `<span title="${val}">${val}</span>`;
            }
        },
        {
            title: "Financial Year",
            field: "financial_year_name",
            width: savedColWidths.financial_year_name ?? 180,
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
        },
        {
            title: "Action",
            field: "actions",
            width: 125,
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
            frozen: true,
            formatter: (cell) => {
                const row = cell.getData();
                if (!currentPermissions.force_logout) return "";

                return `
                    <button type="button" 
                        class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1 force-logout-btn" 
                        data-id="${row.id}" 
                        data-name="${row.name}" 
                        title="Force Logout">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </button>
                `;
            },
        },
    ];
}

let loginUsersTable;
let currentPermissions = {};
let searchTimeout;

document.addEventListener("DOMContentLoaded", function() {
    const tableEl = document.getElementById("login_users_table");

    if (tableEl) {
        loginUsersTable = new Tabulator("#login_users_table", {
            height: "calc(90vh - 280px)", 
            ajaxURL: loginUsersListUrl,
            ajaxConfig: "GET",
            layout: "fitDataFill",
            pagination: true,
            paginationMode: "remote",
            paginationSize: 50,
            placeholder: "No active login sessions found",
            columns: getTableColumns(),
            ajaxResponse: function(url, params, response) {
                if (response.permissions) {
                    currentPermissions = response.permissions;
                }
                return {
                    data: response.data,
                    last_page: response.last_page,
                };
            },
            ajaxError: function(error) {
                console.error("AJAX error:", error);
            },
        });
        
        setupTableSearch();
        setupForceLogoutHandler();
    }
});

function setupTableSearch() {
    const searchInput = document.getElementById("searchInput");
    if (searchInput) {
        searchInput.addEventListener("input", function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = searchInput.value;
                loginUsersTable.setData(loginUsersListUrl, { search: searchTerm, page: 1 });
            }, 300);
        });
    }
}

function setupForceLogoutHandler() {
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.force-logout-btn');
        if (btn) {
            e.preventDefault();
            const id = btn.getAttribute('data-id');
            const name = btn.getAttribute('data-name');
            const url = loginUsersForceLogoutUrl.replace(':id', id);

            Swal.fire({
                title: "Force Logout User?",
                html: `Are you sure you want to log out <b>${name}</b>?<br>Any unsaved work may be lost.`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Yes, logout!"
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoader();

                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            hideLoader();
                            if (response.success) {
                                Swal.fire("Logged Out!", response.message, "success");
                                loginUsersTable.setData();
                            } else {
                                Swal.fire("Error!", response.message, "error");
                            }
                        },
                        error: function(xhr) {
                            hideLoader();
                            let message = "Something went wrong.";
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            }
                            Swal.fire("Error!", message, "error");
                        }
                    });
                }
            });
        }
    });
}
