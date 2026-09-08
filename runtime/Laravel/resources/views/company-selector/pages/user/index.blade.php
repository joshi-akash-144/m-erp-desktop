@extends('company-selector.layout.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
<style>
    .select2-container--bootstrap-5.select2-container--focus .select2-selection,
    .select2-container--bootstrap-5.select2-container--open .select2-selection {
        box-shadow: none !important;
        border-color: #ced4da !important;
        border-radius: 0 !important;
    }
    .select2-container--bootstrap-5 .select2-selection {
        border-radius: 0 !important;
        min-height: calc(1.5em + 0.75rem + -1px) !important;
    }
    .select2-container .select2-selection--single { height: 10px !important; }
    .select2-container--bootstrap-5 .select2-search__field:focus { box-shadow: none !important; border-radius: 0 !important; }
    .select2-container--bootstrap-5 .select2-dropdown .select2-results__options .select2-results__option.select2-results__option--selected {
        color: #fff !important;
        background-color: #066fd1 !important;
    }
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
                        <i class="fa-solid fa-user-group" style="font-size:26px;"></i>
                    </span>
                </div>
                <div class="px-4 pb-3" style="padding-top:48px !important;">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <h3 class="mb-0 fw-bold">Users</h3>
                            <div class="text-muted small mt-1">Manage system users and their account details</div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('company-selection.index') }}"
                                class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                                @include('icons.prev', ['size' => 15])
                                Dashboard
                            </a>
                            <a href="{{ route('users.create') }}"
                                class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                                @include('icons.plus', ['size' => 16])
                                Add User
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── FILTER + TABLE CARD ── --}}
            <div class="card border-0 shadow-sm rounded-4">

                {{-- Filters header --}}
                <div class="card-header bg-transparent border-bottom px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-flex align-items-center justify-content-center rounded-3 bg-secondary-subtle text-secondary"
                            style="width:32px;height:32px;flex-shrink:0;">
                            @include('icons.filter', ['size' => 16])
                        </span>
                        <div class="fw-semibold lh-1">
                            Filters
                            <span id="active-filter-badge" class="badge bg-primary ms-1 d-none" style="font-size:11px;">
                                <span id="filter-count">0</span> Active
                            </span>
                        </div>
                    </div>
                    <div class="card-actions">
                        <button type="button" class="btn-action border-0 bg-transparent"
                            data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="false">
                            @include('icons.chevron-down')
                        </button>
                    </div>
                </div>

                {{-- Collapsible filter form --}}
                <div class="collapse" id="filterCollapse">
                    <div class="card-body border-bottom px-4 py-3">
                        <div class="row g-3">
                            <div class="col-md-4 col-lg-3">
                                <label class="form-label fw-semibold small">Search</label>
                                <div class="input-group input-group-flat">
                                    <span class="input-group-text">
                                        @include('icons.search', ['size' => 15])
                                    </span>
                                    <input type="text" id="search" class="form-control"
                                        placeholder="Search…" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-4 col-lg-3">
                                <label class="form-label fw-semibold small">User Name</label>
                                <select id="username_id" class="form-select users-select">
                                    <option value="">All Users</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 col-lg-3 d-flex align-items-end gap-2">
                                <button id="filter_apply" class="btn btn-primary flex-fill">
                                    @include('icons.filter', ['size' => 16])
                                    Apply
                                </button>
                                <button id="filter_clear" class="btn btn-outline-secondary flex-fill">
                                    @include('icons.filter-clear', ['size' => 16])
                                    Clear
                                </button>
                            </div>
                        </div>

                        <div id="active-filters-display" class="mt-3 d-none">
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <small class="text-muted">Active filters:</small>
                                <div id="filter-tags" class="d-flex flex-wrap gap-2"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tabulator table --}}
                <div class="card-body p-0">
                    <div id="user_table"></div>
                </div>

                {{-- Scroll loader --}}
                <div id="scrollLoader" class="card-footer text-center border-top" style="display:none;">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading…</span>
                    </div>
                    <span class="ms-2 text-muted small">Loading more records…</span>
                </div>

            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const getUsersUrl   = "{{ route('users.index') }}";
    const userViewUrl   = "{{ route('users.show', ':id') }}";
    const userEditUrl   = "{{ route('users.edit', ':id') }}";
    const userDeleteUrl = "{{ route('users.destroy', ':id') }}";
</script>
<script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
<script src="{{ asset('js/users/index.js') }}?v={{ hash_file('md5', public_path('js/users/index.js')) }}"></script>
@endsection
