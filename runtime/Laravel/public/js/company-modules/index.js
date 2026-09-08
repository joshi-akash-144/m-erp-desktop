let table;
let currentFilter = {};

$(document).ready(function () {

    bindSelect2();

    // ── Tabulator table ───────────────────────────────────────
    table = new Tabulator("#company_module_table", {
        height: "550px",
        layout: "fitColumns",
        placeholder: `
            <div class="text-center py-5">
                <i class="fa-solid fa-puzzle-piece text-muted mb-3" style="font-size:48px;opacity:0.3;"></i>
                <h3 class="text-muted">No module assignments found</h3>
                <p class="text-muted">Try adjusting your filters or assign modules to a company</p>
            </div>
        `,
        pagination: true,
        paginationMode: "remote",
        paginationSize: 50,
        paginationSizeSelector: [10, 20, 50, 100],
        paginationDataSent: { page: "page", size: "size" },
        paginationDataReceived: { last_page: "last_page", data: "data", total: "total" },

        ajaxURL: getCompanyModulesUrl,
        ajaxParams: () => currentFilter,
        ajaxURLGenerator: function (url, config, params) {
            const page = params.page || 1;
            const size = params.size || 50;
            const queryParams = new URLSearchParams({ ...currentFilter, page, size });
            return `${url}?${queryParams.toString()}`;
        },

        columns: getTableColumns(),
    });

    // ── Filters ───────────────────────────────────────────────
    document.getElementById("filter_apply")?.addEventListener("click", applyFilter);
    document.getElementById("filter_clear")?.addEventListener("click", clearFilter);
    document.getElementById("search")?.addEventListener("keypress", function (e) {
        if (e.key === "Enter") applyFilter();
    });

    const saved = localStorage.getItem("company_module_filter");
    if (saved) {
        try {
            currentFilter = JSON.parse(saved);
            restoreFilterUI();
        } catch (e) {}
    }
});

// ── Columns ──────────────────────────────────────────────────
function getTableColumns() {
    return [
        {
            title: "#", formatter: "rownum", hozAlign: "center",
            width: 50, headerSort: false, resizable: false,
        },
        {
            title: "Company", field: "company_name", minWidth: 180,
            formatter: function (cell) {
                const code = cell.getRow().getData().company_code;
                return `<span class="fw-semibold">${cell.getValue()}</span>` +
                    (code ? ` <span class="badge bg-secondary-subtle text-secondary ms-1">${escapeHtml(code)}</span>` : "");
            },
        },
        {
            title: "Module", field: "module_title", minWidth: 160,
            formatter: function (cell) {
                const row   = cell.getRow().getData();
                const color = row.module_color || "#6610f2";
                return `<span class="fw-semibold" style="color:${escapeHtml(color)};">${escapeHtml(cell.getValue())}</span>`;
            },
        },
        {
            title: "Status", field: "is_active", hozAlign: "center", width: 110,
            formatter: function (cell) {
                const active = cell.getValue();
                return active
                    ? '<span class="badge bg-success-subtle text-success">Active</span>'
                    : '<span class="badge bg-danger-subtle text-danger">Inactive</span>';
            },
        },
        {
            title: "Assigned At", field: "assigned_at", hozAlign: "center", minWidth: 140,
            formatter: function (cell) {
                const v = cell.getValue();
                if (!v) return '<span class="text-muted">—</span>';
                return `<span class="text-muted small">${escapeHtml(v.substring(0, 10))}</span>`;
            },
        },
        {
            title: "Actions", hozAlign: "center", width: 120, headerSort: false, resizable: false,
            formatter: function (cell) {
                const row     = cell.getRow().getData();
                const active  = row.is_active;
                const toggle  = active
                    ? `<button class="btn btn-sm btn-warning btn-toggle-status" data-company="${row.company_id}" data-module="${row.module_id}" data-active="0" title="Deactivate"><i class="fa-solid fa-toggle-on"></i></button>`
                    : `<button class="btn btn-sm btn-success btn-toggle-status" data-company="${row.company_id}" data-module="${row.module_id}" data-active="1" title="Activate"><i class="fa-solid fa-toggle-off"></i></button>`;
                const del = `<button class="btn btn-sm btn-danger btn-delete ms-1" data-company="${row.company_id}" data-module="${row.module_id}" title="Remove"><i class="fa-solid fa-trash"></i></button>`;
                return toggle + del;
            },
            cellClick: function (e, cell) {
                const btn = e.target.closest("button");
                if (!btn) return;
                const companyId = btn.dataset.company;
                const moduleId  = btn.dataset.module;

                if (btn.classList.contains("btn-toggle-status")) {
                    toggleStatus(companyId, moduleId, btn.dataset.active);
                } else if (btn.classList.contains("btn-delete")) {
                    deleteAssignment(companyId, moduleId);
                }
            },
        },
    ];
}

// ── Toggle status ─────────────────────────────────────────────
function toggleStatus(companyId, moduleId, isActive) {
    $.ajax({
        url: updateCompanyModuleUrl,
        method: "PUT",
        data: { company_id: companyId, module_id: moduleId, is_active: isActive, _token: $('meta[name="csrf-token"]').attr("content") },
        success: function (res) {
            if (res.success) {
                showToast("success", res.message);
                table.replaceData();
            } else {
                showToast("error", res.message);
            }
        },
        error: function () { showToast("error", "Failed to update status."); },
    });
}

// ── Delete ────────────────────────────────────────────────────
function deleteAssignment(companyId, moduleId) {
    if (!confirm("Remove this module assignment?")) return;
    $.ajax({
        url: deleteCompanyModuleUrl,
        method: "DELETE",
        data: { company_id: companyId, module_id: moduleId, _token: $('meta[name="csrf-token"]').attr("content") },
        success: function (res) {
            if (res.success) {
                showToast("success", res.message);
                table.replaceData();
            } else {
                showToast("error", res.message);
            }
        },
        error: function () { showToast("error", "Failed to remove assignment."); },
    });
}

// ── Filter helpers ────────────────────────────────────────────
function applyFilter() {
    currentFilter = {};
    const companyEl = document.getElementById("filter_company_id");
    const moduleEl  = document.getElementById("filter_module_id");
    const statusEl  = document.getElementById("filter_status");
    const searchEl  = document.getElementById("search");

    const company = companyEl?.value;
    const module  = moduleEl?.value;

    if (company)                currentFilter.filter_company_id = company;
    if (module)                 currentFilter.filter_module_id  = module;
    if (statusEl?.value !== "") currentFilter.filter_status     = statusEl.value;
    if (searchEl?.value.trim()) currentFilter.search            = searchEl.value.trim();

    localStorage.setItem("company_module_filter", JSON.stringify(currentFilter));
    updateFilterBadge();
    table.setData();
}

function clearFilter() {
    $('#filter_company_id').val(null).trigger('change');
    $('#filter_module_id').val(null).trigger('change');
    document.getElementById("filter_status").value = "";
    document.getElementById("search").value = "";
    currentFilter = {};
    localStorage.removeItem("company_module_filter");
    updateFilterBadge();
    table.setData();
}

function restoreFilterUI() {
    if (currentFilter.filter_company_id) $('#filter_company_id').val(currentFilter.filter_company_id).trigger('change');
    if (currentFilter.filter_module_id)  $('#filter_module_id').val(currentFilter.filter_module_id).trigger('change');
    if (currentFilter.filter_status !== undefined) document.getElementById("filter_status").value = currentFilter.filter_status;
    if (currentFilter.search) document.getElementById("search").value = currentFilter.search;
    updateFilterBadge();
}

function updateFilterBadge() {
    const count  = Object.keys(currentFilter).length;
    const badge  = document.getElementById("active-filter-badge");
    const countEl = document.getElementById("filter-count");
    if (badge && countEl) {
        countEl.textContent = count;
        badge.classList.toggle("d-none", count === 0);
    }
}

function bindSelect2() {
    $(".company-select2").select2({ theme: "bootstrap-5", allowClear: true, placeholder: "All Companies", width: "100%" });
    $(".module-select2").select2({ theme: "bootstrap-5", allowClear: true, placeholder: "All Modules", width: "100%" });
}

function escapeHtml(str) {
    if (str == null) return "";
    return String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}
