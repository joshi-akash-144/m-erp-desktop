let currentFilter = {};

$(document).ready(function () {

    bindSelect2()

    // -----------------------------------
    // Initialize Table
    // -----------------------------------
    table = new Tabulator("#company_user_table", {
        height: "550px",
        layout: "fitColumns",
        placeholder: `
            <div class="text-center py-5">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="icon text-muted mb-3">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M3 21l18 0" /><path d="M9 8l1 0" />
                    <path d="M9 12l1 0" /><path d="M9 16l1 0" />
                    <path d="M14 8l1 0" /><path d="M14 12l1 0" />
                    <path d="M14 16l1 0" />
                    <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16" />
                </svg>
                <h3 class="text-muted">No assignments found</h3>
                <p class="text-muted">Try adjusting your filters or add a new assignment</p>
            </div>
        `,

        pagination: true,
        paginationMode: "remote",
        paginationSize: 50,
        paginationSizeSelector: [10, 20, 50, 100],
        paginationDataSent: { page: "page", size: "size" },
        paginationDataReceived: { last_page: "last_page", data: "data", total: "total" },

        ajaxURL: getCompanyUsersUrl,
        ajaxParams: () => currentFilter,
        ajaxURLGenerator: (url, config, params) => {
            const page = params.page || 1;
            const size = params.size || 50;
            const queryParams = new URLSearchParams({ ...currentFilter, page, size });
            return `${url}?${queryParams.toString()}`;
        },

        columns: getTableColumns(),
    });

    // -----------------------------------
    // Filter listeners
    // -----------------------------------
    document.getElementById("filter_apply")?.addEventListener("click", applyFilter);
    document.getElementById("filter_clear")?.addEventListener("click", clearFilter);
    document.getElementById("search")?.addEventListener("keypress", function (e) {
        if (e.key === "Enter") applyFilter();
    });

    // Load saved filters
    const saved = localStorage.getItem("company_user_filter");
    if (saved) {
        try {
            currentFilter = JSON.parse(saved);
            restoreFilterUI();
        } catch (e) {
            console.error("Failed to parse saved filters", e);
        }
    }
});

// ==========================================
// FILTER FUNCTIONS
// ==========================================
function applyFilter() {
    const companyEl = document.getElementById("filter_company_id");
    const userEl    = document.getElementById("filter_user_id");
    const statusEl  = document.getElementById("filter_status");
    const searchEl  = document.getElementById("search");

    currentFilter = {};

    const company = companyEl?.tomselect ? companyEl.tomselect.getValue() : companyEl?.value;
    const user    = userEl?.tomselect ? userEl.tomselect.getValue() : userEl?.value;

    if (company)                currentFilter.filter_company_id = company;
    if (user)                   currentFilter.filter_user_id = user;
    if (statusEl?.value !== "") currentFilter.filter_status = statusEl.value;
    if (searchEl?.value.trim()) currentFilter.search = searchEl.value.trim();

    localStorage.setItem("company_user_filter", JSON.stringify(currentFilter));
    table.setData();
}

function clearFilter() {
    $('#filter_company_id').val(null).trigger('change');
    $('#filter_user_id').val(null).trigger('change');
    $('#filter_status').val(null).trigger('change');
    
    const searchEl  = document.getElementById("search");
    if (searchEl) searchEl.value = "";

    currentFilter = {};
    localStorage.removeItem("company_user_filter");
    table.setData();
}

function restoreFilterUI() {
    if (currentFilter.filter_company_id) {
        $('#filter_company_id').val(currentFilter.filter_company_id).trigger('change');
    }
    if (currentFilter.filter_user_id) {
        $('#filter_user_id').val(currentFilter.filter_user_id).trigger('change');
    }
    if (currentFilter.filter_status !== undefined) {
        $('#filter_status').val(currentFilter.filter_status).trigger('change');
    }
    
    const searchEl  = document.getElementById("search");
    if (currentFilter.search && searchEl) {
        searchEl.value = currentFilter.search;
    }
}

// ==========================================
// TABLE COLUMNS
// ==========================================
function getTableColumns() {
    return [
        {
            title: "No",
            field: "no",
            width: 70,
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
            resizable: false,
            formatter: "rownum",
            frozen: true,
        },
        {
            title: "Company",
            field: "company_name",
            minWidth: 180,
            headerSort: true,
            sorter: "string",
            resizable: true,
            formatter: (cell) => {
                const code = cell.getRow().getData().company_code;
                const name = cell.getValue() || "--";
                return code
                    ? `<span>${name}</span> <span class="badge bg-blue-lt ms-1">${code}</span>`
                    : name;
            },
        },
        {
            title: "User",
            field: "user_name",
            minWidth: 160,
            headerSort: true,
            sorter: "string",
            resizable: true,
            formatter: (cell) => {
                const email = cell.getRow().getData().user_email;
                const name  = cell.getValue() || "--";
                return email
                    ? `<div>${name}</div><div class="text-muted small">${email}</div>`
                    : name;
            },
        },
        {
            title: "Status",
            field: "status",
            width: 120,
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
            resizable: false,
            formatter: (cell) =>
                cell.getValue()
                    ? `<span class="badge bg-success-lt">Active</span>`
                    : `<span class="badge bg-danger-lt">Inactive</span>`,
        },
        {
            title: "Assigned By",
            field: "assigned_by_name",
            minWidth: 140,
            headerSort: false,
            resizable: true,
            formatter: (cell) => cell.getValue() || `<span class="badge bg-info-lt">System</span>`,
        },
        {
            title: "Assigned At",
            field: "assigned_at",
            minWidth: 150,
            headerSort: true,
            sorter: "datetime",
            resizable: true,
            formatter: (cell) => {
                const val = cell.getValue();
                if (!val) return "--";
                const d = new Date(val);
                return d.toLocaleDateString("en-IN", { day: "2-digit", month: "short", year: "numeric" });
            },
        },
        {
            title: "Actions",
            field: "actions",
            width: 120,
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
            frozen: true,
            formatter: (cell) => {
                const row       = cell.getRow().getData();
                const companyId = row.company_id;
                const userId    = row.user_id;
                const status    = row.status;

                const toggleIcon  = status
                    ? `<i class="fa-solid fa-toggle-on text-success" style="font-size:16px;"></i>`
                    : `<i class="fa-solid fa-toggle-off text-danger" style="font-size:16px;"></i>`;
                const deleteIcon  = icons?.delete || `<i class="fa-solid fa-trash-can"></i>`;

                return `<div class="d-flex gap-2 justify-content-center">
                    <button class="erp-btn-icon toggle-status"
                        data-company-id="${companyId}"
                        data-user-id="${userId}"
                        data-status="${status ? 1 : 0}"
                        title="${status ? 'Deactivate' : 'Activate'}">
                        ${toggleIcon}
                    </button>
                    <button class="erp-btn-icon delete delete-assignment"
                        data-company-id="${companyId}"
                        data-user-id="${userId}"
                        title="Remove Assignment">
                        ${deleteIcon}
                    </button>
                </div>`;
            },
            cellClick: function (e, cell) {
                const target = e.target.closest("button");
                if (!target) return;

                const companyId = target.dataset.companyId;
                const userId    = target.dataset.userId;

                if (target.classList.contains("toggle-status")) {
                    const currentStatus = parseInt(target.dataset.status);
                    const newStatus     = currentStatus === 1 ? 0 : 1;
                    toggleStatus(companyId, userId, newStatus);
                }

                if (target.classList.contains("delete-assignment")) {
                    deleteAssignment(companyId, userId);
                }
            },
        },
    ];
}

// ==========================================
// ACTIONS
// ==========================================
function toggleStatus(companyId, userId, newStatus) {
    Swal.fire({
        title: newStatus ? "Activate Assignment?" : "Deactivate Assignment?",
        text: newStatus
            ? "The user will regain access to this company."
            : "The user will lose access to this company.",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Yes, proceed",
        cancelButtonText: "Cancel",
    }).then((result) => {
        if (!result.isConfirmed) return;

        showLoader(newStatus ? "Activating…" : "Deactivating…");

        $.ajax({
            url: updateCompanyUserUrl,
            method: "PATCH",
            data: {
                _token: $('meta[name="csrf-token"]').attr("content"),
                company_id: companyId,
                user_id: userId,
                status: newStatus,
            },
            success: function (res) {
                hideLoader();
                if (res.success) {
                    showToast("success", res.message);
                    table.setData();
                } else {
                    showToast("error", res.message || "Failed to update status.");
                }
            },
            error: function (xhr) {
                hideLoader();
                if (typeof handleAjaxError === "function") handleAjaxError(xhr);
                else showToast("error", "Something went wrong!");
            },
        });
    });
}

function deleteAssignment(companyId, userId) {
    Swal.fire({
        title: "Remove Assignment?",
        text: "This will revoke the user's access to this company.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d63939",
        confirmButtonText: "Yes, remove",
        cancelButtonText: "Cancel",
    }).then((result) => {
        if (!result.isConfirmed) return;

        showLoader("Removing…");

        $.ajax({
            url: deleteCompanyUserUrl,
            method: "DELETE",
            data: {
                _token: $('meta[name="csrf-token"]').attr("content"),
                company_id: companyId,
                user_id: userId,
            },
            success: function (res) {
                hideLoader();
                if (res.success) {
                    showToast("success", res.message);
                    table.setData();
                } else {
                    showToast("error", res.message || "Failed to remove assignment.");
                }
            },
            error: function (xhr) {
                hideLoader();
                if (typeof handleAjaxError === "function") handleAjaxError(xhr);
                else showToast("error", "Something went wrong!");
            },
        });
    });
}


/**
 * Initialize Select2 on form selects + Enter key navigation
 */
function bindSelect2() {
    var selectIdArray = ['#filter_company_id', '#filter_user_id', '#filter_status'];

    selectIdArray.forEach(function (element) {
        $(element).select2({
            theme: 'bootstrap-5',
            allowClear: true,
            placeholder: 'Select ...',
        });
    });

    $(document).on('select2:open', function (e) {
        var selectElement = $(e.target);
        var searchInput = selectElement.data('select2').$dropdown.find('.select2-search__field');

        searchInput.off('keydown.select2Enter').on('keydown.select2Enter', function (event) {
            if (event.which === 13) {
                event.preventDefault();
                selectElement.select2('close');
                if (typeof moveFocusToNextField === 'function') {
                    moveFocusToNextField(selectElement);
                }
            }
        });
    });
}