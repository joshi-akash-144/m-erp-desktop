@extends('company-selector.layout.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
@endsection

@section('content')
<div class="page-body pt-4">
    <div class="container-xl">

        {{-- Hero Banner --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="position-relative"
                style="height:60px;background:linear-gradient(120deg,#1a56db 0%,#4dabf7 60%,#74c0fc 100%);border-radius:12px 12px 0 0;">
                <span class="d-inline-flex align-items-center justify-content-center rounded-3 border border-4 border-white shadow position-absolute text-white"
                    style="background:linear-gradient(135deg,#1a56db,#4dabf7);width:76px;height:76px;bottom:-38px;left:24px;">
                    <i class="fa-solid fa-envelope-open-text" style="font-size:26px;"></i>
                </span>
            </div>
            <div class="px-4 pb-3" style="padding-top:48px !important;">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h3 class="mb-0 fw-bold">Email Templates</h3>
                        <div class="text-muted small mt-1">Manage email templates used across the system</div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('company-selection.index') }}"
                            class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                            @include('icons.prev', ['size' => 15])
                            Dashboard
                        </a>
                        @if(has_permission('email_template.create'))
                        <a href="{{ route('email-templates.create') }}"
                            class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                            @include('icons.plus', ['size' => 16])
                            Add Template
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter + Table --}}
        <div class="card border-0 shadow-sm rounded-4">

            {{-- Filter header --}}
            <div class="card-header bg-transparent border-bottom px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="d-flex align-items-center justify-content-center rounded-3 bg-secondary-subtle text-secondary"
                        style="width:32px;height:32px;flex-shrink:0;">
                        @include('icons.filter', ['size' => 16])
                    </span>
                    <div class="fw-semibold lh-1">Filters</div>
                </div>
                <div class="card-actions">
                    <button type="button" class="btn-action border-0 bg-transparent"
                        data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                        @include('icons.chevron-down')
                    </button>
                </div>
            </div>

            <div class="collapse" id="filterCollapse">
                <div class="card-body border-bottom px-4 py-3">
                    <div class="row g-3">
                        <div class="col-md-4 col-lg-3">
                            <label class="form-label fw-semibold small">Search</label>
                            <div class="input-group input-group-flat">
                                <span class="input-group-text">@include('icons.search', ['size' => 15])</span>
                                <input type="text" id="search" class="form-control" placeholder="Search…" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-4 col-lg-3 d-flex align-items-end gap-2">
                            <button id="filter_apply" class="btn btn-primary flex-fill">
                                @include('icons.filter', ['size' => 16]) Apply
                            </button>
                            <button id="filter_clear" class="btn btn-outline-secondary flex-fill">
                                Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body p-0">
                <div id="email_template_table"></div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
<script src="{{ asset('js/libs/tabulator.min.js') }}"></script>
<script>
const listUrl   = "{{ route('email-templates.list') }}";
const editUrl   = "{{ url('email-templates') }}";
const deleteUrl = "{{ url('email-templates') }}";
const canEdit   = {{ has_permission('email_template.update') ? 'true' : 'false' }};
const canDelete = {{ has_permission('email_template.delete') ? 'true' : 'false' }};

let filterParams = {};

const table = new Tabulator('#email_template_table', {
    height: "calc(90vh - 280px)",
    ajaxURL: listUrl,
    ajaxParams: () => ({ ...filterParams }),
    ajaxConfig: {
        method: 'GET',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    },
    ajaxResponse(url, params, response) {
        return { data: response.data, last_page: response.last_page };
    },
    pagination: true,
    paginationMode: 'remote',
    paginationSize: 15,
    paginationSizeSelector: [15, 25, 50],
    layout: 'fitColumns',
    responsiveLayout: 'collapse',
    placeholder: 'No email templates found',
    columns: [
        { title: '#', formatter: 'rownum', width: 55, hozAlign: 'center', headerHozAlign: 'center', headerSort: false },
        { title: 'Template Name', field: 'name', minWidth: 180 },
        { title: 'Slug', field: 'slug', minWidth: 180,
            formatter: (cell) => `<code class="text-muted small">${cell.getValue() ?? ''}</code>` },
        { title: 'Subject', field: 'subject', minWidth: 220 },
        { title: 'Status', field: 'status', width: 100, hozAlign: 'center', headerHozAlign: 'center',
            formatter: (cell) => cell.getValue() === 0
                ? '<span class="badge bg-success-lt text-success">Active</span>'
                : '<span class="badge bg-danger-lt text-danger">Inactive</span>' },
        { title: 'Actions', field: 'id', width: 110, hozAlign: 'center', headerHozAlign: 'center', headerSort: false,
            formatter(cell) {
                const id = cell.getValue();
                const editIcon = icons.edit;
                const deleteIcon = icons.delete;
                let html = '';
                if (canEdit)
                    html += `<a href="${editUrl}/${id}/edit" class="erp-btn-icon edit" title="Edit">${editIcon}</a>`;
                if (canDelete)
                    html += `<a class="erp-btn-icon delete btn-del" data-id="${id}" title="Delete">${deleteIcon}</a>`;
                return html;
            },
            cellClick(e, cell) {
                const btn = e.target.closest('.btn-del');
                if (!btn) return;
                const id = btn.dataset.id;
                Swal.fire({
                    title: 'Delete Template?',
                    text: 'This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Delete',
                    confirmButtonColor: '#d63939',
                }).then(res => {
                    if (!res.isConfirmed) return;
                    fetch(`${deleteUrl}/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    })
                    .then(r => r.json())
                    .then(json => {
                        if (json.status) {
                            Swal.fire({ icon: 'success', title: 'Deleted!', text: json.message, timer: 1500, showConfirmButton: false });
                            table.replaceData();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: json.message });
                        }
                    });
                });
            },
        },
    ],
});

document.getElementById('filter_apply').addEventListener('click', () => {
    filterParams = { search: document.getElementById('search').value };
    table.replaceData();
});

document.getElementById('filter_clear').addEventListener('click', () => {
    document.getElementById('search').value = '';
    filterParams = {};
    table.replaceData();
});
</script>
@endsection
