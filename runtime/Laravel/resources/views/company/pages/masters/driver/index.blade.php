@extends('company.layout.app')
@section('title', 'Driver - List')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}"></link>
@endsection

@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-user-group"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Driver
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-green text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-list"></i>  list
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                        {{-- Export Dropdown (only for users with print/export permission) --}}
                        @canany(['driver.print', 'driver.export'])
                        <div class="dropdown">
                            <button
                                class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                @include('icons.upload', ['size' => 19])
                                Export
                            </button>

                            <ul class="dropdown-menu dropdown-menu-end">
                                @php
                                $actions = exportActions('drivers', 'driver');
                                @endphp

                                @foreach ($actions as $action)
                                @can($action['permission'])
                                <li>
                                    <a class="dropdown-item" href="#" data-route="{{ $action['route']  }}"
                                        @foreach(($action['attrs'] ?? []) as $attr=> $value)
                                        {{ $attr }}="{{ $value }}"
                                        @endforeach>
                                        @include($action['icon'])
                                        {{ $action['label'] }}
                                    </a>
                                </li>
                                @endcan
                                @endforeach
                            </ul>
                        </div>
                        @endcanany
                        <div class="waves-effect">
                            <a href="#" class="btn btn-outline-primary btn-sm js-load-modal"
                                data-module="masters/driver/modal" data-init="openDriverCreateModal">
                                @include('icons.plus', ['size' => 20])
                                Add Driver
                            </a>
                        </div>
                        <a href="{{ route('back.to.previous') }}"
                            class="btn btn-sm btn-outline-dark back-btn waves-effect rounded-0 fw-medium shadow-sm">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card">

                <div id="filterCollapse">
                    <div class="card-body border-bottom">
                        <div class="row g-3">
                            <div class="col-md-4 col-lg-3">
                                <label class="form-label fw-bold">Search</label>
                                <div class="input-group input-group-flat">
                                    <span class="input-group-text">
                                        @include('icons.search', ['size' => 15])
                                    </span>
                                    <input type="text" id="search" class="form-control" placeholder="Search..."
                                        autocomplete="off">
                                </div>
                            </div>                           

                            <div class="col-md-4 col-lg-1 d-flex align-items-end gap-2">
                                {{-- <button id="filter_apply" class="btn btn-primary flex-fill waves-effect">
                                    @include('icons.filter', ['size' => 20])
                                    Apply
                                </button> --}}
                                <button id="filter_clear" class="btn btn-outline-secondary flex-fill waves-effect">
                                    @include('icons.filter-clear', ['size' => 20])
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

                <div class="card-body p-0">
                    <div id="driver_table"></div>
                </div>

                <div id="scrollLoader" class="card-footer text-center" style="display:none;">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="ms-2 text-muted">Loading more records...</span>
                </div>
            </div>
        </div>
    </div>
</div>
@include('company.partials._shortcuts-bar')
@endsection

@section('script')
    <script>
        const driverCreateUrl = "{{ route('drivers.create') }}";
        const driverListUrl = "{{ route('drivers.index') }}";
        const driverEditUrl = "{{ route('drivers.edit', ':id') }}";
        const driverViewUrl = "{{ route('drivers.show', ':id') }}";
        const driverDeleteUrl = "{{ route('drivers.destroy', ':id') }}";
    </script>
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
    <script src="{{ asset('js/master/driver/index.js') }}?v={{ hash_file('md5', public_path('js/master/driver/index.js')) }}"></script>
@endsection
