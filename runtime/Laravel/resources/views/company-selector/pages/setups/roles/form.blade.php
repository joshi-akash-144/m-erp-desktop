@extends('company-selector.layout.app')

@section('css')
<style>
/* ── Permission Tree ─────────────────────────────────── */
.perm-module-header {
    cursor: pointer;
    user-select: none;
    transition: background .15s, border-color .15s;
    background: #f8f9fa;
}
.perm-module-header:hover {
    background: #eef4ff !important;
    border-color: #b8d0f8 !important;
}
.perm-chevron {
    display: inline-flex;
    align-items: center;
    width: 16px;
    flex-shrink: 0;
    color: #6c757d;
}
.perm-chevron svg {
    transition: transform .2s ease;
}
.perm-module-header:not(.collapsed) .perm-chevron svg {
    transform: rotate(90deg);
}
.perm-children {
    border-left: 2px solid #dee2e6;
    margin-left: 22px;
}
.perm-item {
    transition: border-color .15s, background .15s;
    cursor: pointer;
}
.perm-item:has(.permission-checkbox:checked) {
    border-color: #206bc4 !important;
    background: #eef4ff !important;
}
.perm-item label { cursor: pointer; }
.perm-module[style*="display: none"] { display: none !important; }
</style>
@endsection

@section('content')
<div class="page-wrapper">
    <div class="page-body pt-4">
        <div class="container-xl">

            {{-- ── HERO BANNER ── --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="position-relative"
                    style="height:60px;background:linear-gradient(120deg,#1a56db 0%,#4dabf7 60%,#74c0fc 100%);border-radius:12px 12px 0 0;">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 border border-4 border-white shadow position-absolute text-white"
                        style="background:linear-gradient(135deg,#1a56db,#4dabf7);width:76px;height:76px;bottom:-38px;left:24px;">
                        <i class="fa-solid fa-shield-halved" style="font-size:28px;"></i>
                    </span>
                </div>
                <div class="px-4 pb-3" style="padding-top:48px !important;">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <h3 class="mb-0 fw-bold">
                                    @if(($mode ?? '') === 'edit') Edit Role
                                    @elseif(($mode ?? '') === 'view') View Role
                                    @else New Role
                                    @endif
                                </h3>
                                @if(in_array($mode ?? '', ['edit', 'view']) && isset($modalData['data']))
                                    @if($modalData['data']->status ?? false)
                                        <span class="badge bg-success-lt text-success rounded-pill px-2">Active</span>
                                    @else
                                        <span class="badge bg-danger-lt text-danger rounded-pill px-2">Inactive</span>
                                    @endif
                                @endif
                            </div>
                            <div class="text-muted small mt-1">
                                @if(($mode ?? '') === 'create')
                                    Configure a new role and assign module permissions
                                @else
                                    {{ $modalData['data']->name ?? '' }}
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('roles.index') }}"
                            class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                            @include('icons.prev', ['size' => 15])
                            Back to Roles
                        </a>
                    </div>
                </div>
            </div>

            {{-- ── FORM ── --}}
            <form id="roleForm" data-mode="{{ $mode ?? 'create' }}">
                @csrf
                <input type="hidden" name="id" value="{{ $modalData['data']->id ?? '' }}">

                {{-- ── ROLE DETAILS CARD ── --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-transparent border-bottom px-4 py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary"
                                style="width:36px;height:36px;flex-shrink:0;">
                                <i class="fa-solid fa-shield-halved fa-lg"></i>
                            </span>
                            <div>
                                <div class="fw-bold lh-1">Role Information</div>
                                <div class="text-muted small">Give this role a unique name</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            {{-- Role Name --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent">
                                        <i class="fa-solid fa-tag text-muted"></i>
                                    </span>
                                    <input type="text" name="name" id="name"
                                        class="form-control"
                                        value="{{ $modalData['data']->name ?? '' }}"
                                        placeholder="e.g. Sales Manager, Warehouse Staff"
                                        {{ ($mode ?? '') === 'view' ? 'disabled' : '' }}>
                                </div>
                                <div id="name-error"></div>
                            </div>
                            {{-- Status (edit / view only) --}}
                            <!-- @if(in_array($mode ?? '', ['edit', 'view']))
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="w-100">
                                    <label class="form-label fw-semibold">Status</label>
                                    <label class="form-check form-switch form-switch-lg d-flex align-items-center gap-2 mb-0">
                                        <input type="hidden" name="status" value="0">
                                        <input class="form-check-input" type="checkbox" name="status" id="roleStatus"
                                            value="1"
                                            {{ isset($modalData['data']) && $modalData['data']->status ? 'checked' : '' }}
                                            {{ ($mode ?? '') === 'view' ? 'disabled' : '' }}>
                                        <span id="statusLabel" class="form-check-label fw-semibold">
                                            {{ isset($modalData['data']) && $modalData['data']->status ? 'Active' : 'Inactive' }}
                                        </span>
                                    </label>
                                </div>
                            </div>
                            @endif -->
                        </div>
                    </div>
                </div>

                {{-- ── PERMISSIONS TREE CARD ── --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-transparent border-bottom px-4 py-3">
                        <div class="d-flex align-items-center gap-2 w-100 flex-wrap row-gap-2">
                            <span class="d-flex align-items-center justify-content-center rounded-3 bg-warning-subtle text-warning"
                                style="width:36px;height:36px;flex-shrink:0;">
                                <i class="fa-solid fa-key fa-lg"></i>
                            </span>
                            <div>
                                <div class="fw-bold lh-1">Module Permissions</div>
                                <div class="text-muted small">Select which actions this role can perform</div>
                            </div>
                            <div class="ms-auto d-flex align-items-center gap-3 flex-wrap">
                                {{-- Search --}}
                                <div class="input-group input-group-sm" style="width:200px;">
                                    <span class="input-group-text bg-transparent border-end-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2.5"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                        </svg>
                                    </span>
                                    <input type="text" id="permSearch"
                                        class="form-control border-start-0 ps-0"
                                        placeholder="Search modules…" autocomplete="off">
                                </div>
                                {{-- Select All --}}
                                @if(($mode ?? '') !== 'view')
                                <div class="form-check d-flex align-items-center gap-2 mb-0">
                                    <input class="form-check-input mt-0" type="checkbox" id="selectAll">
                                    <label class="form-check-label fw-semibold small text-primary mb-0" for="selectAll">
                                        Select All
                                    </label>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Tree body --}}
                    <div class="card-body p-3" style="max-height:540px;overflow-y:auto;">
                        <div id="permTree">
                            @foreach ($modalData['permissions'] as $module => $permissions)
                                @php
                                    $moduleLabel  = str_replace('_', ' ', ucwords($module));
                                    $permFiltered = collect($permissions)->filter(fn($p) => !str_contains($p, 'forceDelete'));
                                    $permCount    = $permFiltered->count();
                                    $checkedCount = 0;
                                    if ($modalData['form_mode'] !== 'create' && isset($modalData['data']->permissions)) {
                                        $checkedCount = $permFiltered->filter(
                                            fn($p) => $modalData['data']->permissions->contains('name', $p)
                                        )->count();
                                    }
                                    $moduleId = 'mod_' . preg_replace('/[^a-z0-9]/i', '_', $module);
                                    $isOpen   = $checkedCount > 0;
                                @endphp

                                <div class="perm-module mb-2"
                                    data-module="{{ $module }}"
                                    data-search="{{ strtolower($moduleLabel) }}">

                                    {{-- Module header row --}}
                                    <div class="perm-module-header d-flex align-items-center gap-2 px-3 py-2 rounded-3 border {{ $isOpen ? '' : 'collapsed' }}"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#{{ $moduleId }}"
                                        aria-expanded="{{ $isOpen ? 'true' : 'false' }}">

                                        <span class="perm-chevron">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M9 18l6-6-6-6"/>
                                            </svg>
                                        </span>

                                        @if(($mode ?? '') !== 'view')
                                        <input type="checkbox"
                                            class="form-check-input row-checkbox mt-0"
                                            id="module-{{ $module }}"
                                            data-module="{{ $module }}"
                                            style="cursor:pointer;flex-shrink:0;"
                                            onclick="event.stopPropagation()">
                                        @endif

                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-2 px-2 py-1"
                                            style="font-size:12px;font-weight:600;letter-spacing:.3px;">
                                            {{ $moduleLabel }}
                                        </span>

                                        <span class="ms-auto badge rounded-pill"
                                            style="background:#e9ecef;color:#495057;font-size:11px;min-width:42px;text-align:center;">
                                            <span class="checked-count">{{ $checkedCount }}</span>/{{ $permCount }}
                                        </span>
                                    </div>

                                    {{-- Permissions (collapsed or shown) --}}
                                    <div class="collapse {{ $isOpen ? 'show' : '' }}" id="{{ $moduleId }}">
                                        <div class="perm-children ps-4 pt-2 pb-2">
                                            <div class="d-flex flex-wrap gap-2">
                                                @foreach ($permissions as $permKey => $permValue)
                                                    @php
                                                        if (str_contains($permValue, 'forceDelete')) continue;
                                                        $parts       = explode('.', $permValue);
                                                        $actionLabel = ucfirst(str_replace('_', ' ', $parts[1] ?? $permKey));
                                                        $checked     = false;
                                                        if ($modalData['form_mode'] !== 'create' && isset($modalData['data']->permissions)) {
                                                            $checked = $modalData['data']->permissions->contains('name', $permValue);
                                                        }
                                                        $permId = 'perm_' . ($modalData['form_mode'] !== 'create' ? $modalData['data']->id : 'new') . '_' . str_replace(['.', ' '], '_', $permValue);
                                                    @endphp
                                                    <div class="perm-item d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border {{ $checked ? 'border-primary' : '' }}"
                                                        style="{{ $checked ? 'background:#eef4ff;' : 'background:#fff;' }}">
                                                        <input class="form-check-input permission-checkbox mt-0"
                                                            type="checkbox"
                                                            data-module="{{ $module }}"
                                                            id="{{ $permId }}"
                                                            name="permissions[{{ $modalData['form_mode'] !== 'create' ? $modalData['data']->id : 'new' }}][]"
                                                            value="{{ $permValue }}"
                                                            @if($checked) checked @endif
                                                            @if($modalData['form_mode'] === 'view') disabled @endif
                                                            style="cursor:pointer;">
                                                        <label class="form-check-label small fw-medium mb-0"
                                                            for="{{ $permId }}" style="cursor:pointer;">
                                                            {{ $actionLabel }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div id="permNoResults" class="text-center py-5 text-muted d-none">
                            <i class="fa-solid fa-magnifying-glass fa-2x mb-2 d-block opacity-50"></i>
                            No modules match your search
                        </div>
                    </div>

                    {{-- Submit footer --}}
                    @if(($mode ?? '') !== 'view')
                    <div class="card-footer bg-transparent border-top px-4 py-3">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('roles.index') }}"
                                class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M15 6l-6 6l6 6"/>
                                </svg>
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary form-save-btn d-inline-flex align-items-center gap-1" id="submitBtn">
                                <i class="fa-solid fa-circle-check"></i>
                                {{ ($mode ?? '') === 'edit' ? 'Update Role' : 'Save Role' }}
                            </button>
                        </div>
                    </div>
                    @endif
                </div>

            </form>
        </div>
    </div>
</div>
@include('company.partials._shortcuts-bar')
@endsection

@section('scripts')
<script>
    const createRole   = "{{ route('roles.store') }}";
    const updateRole   = "{{ route('roles.update', ':id') }}";
    const rolesListUrl = "{{ route('roles.index') }}";
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Permission item highlight on check/uncheck ──────────────────────────
    document.querySelectorAll('.permission-checkbox').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var item = this.closest('.perm-item');
            if (item) {
                if (this.checked) {
                    item.style.background = '#eef4ff';
                    item.classList.add('border-primary');
                } else {
                    item.style.background = '#fff';
                    item.classList.remove('border-primary');
                }
            }
            updateCheckedCount(this.dataset.module);
        });
    });

    // ── Update badge count for a given module ───────────────────────────────
    function updateCheckedCount(module) {
        var moduleEl = document.querySelector('.perm-module[data-module="' + module + '"]');
        if (!moduleEl) return;
        var checked  = moduleEl.querySelectorAll('.permission-checkbox:checked').length;
        var countEl  = moduleEl.querySelector('.checked-count');
        if (countEl) countEl.textContent = checked;
    }

    // ── Module search ────────────────────────────────────────────────────────
    var permSearch = document.getElementById('permSearch');
    if (permSearch) {
        permSearch.addEventListener('input', function () {
            var q = this.value.toLowerCase().trim();
            var modules    = document.querySelectorAll('.perm-module');
            var anyVisible = false;
            modules.forEach(function (m) {
                var label = m.dataset.search || '';
                var show  = !q || label.includes(q);
                m.style.display = show ? '' : 'none';
                if (show) anyVisible = true;
            });
            document.getElementById('permNoResults').classList.toggle('d-none', anyVisible);
        });
    }

    // ── Status label sync ────────────────────────────────────────────────────
    var statusSwitch = document.getElementById('roleStatus');
    var statusLabel  = document.getElementById('statusLabel');
    if (statusSwitch && statusLabel) {
        function syncLabel() {
            statusLabel.textContent = statusSwitch.checked ? 'Active' : 'Inactive';
            statusLabel.style.color = statusSwitch.checked ? '#2fb344' : '#d63939';
        }
        syncLabel();
        statusSwitch.addEventListener('change', syncLabel);
    }

});
</script>

<script src="{{ asset('js/role/create.js') }}?v={{ hash_file('md5', public_path('js/role/create.js')) }}"></script>
@endsection
