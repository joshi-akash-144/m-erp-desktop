@extends('company.layout.app')
@section('title', 'Items - List')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}"></link>
@endsection
@section('content')
    <div class="page-wrapper">
        <div class="col-lg-10">
            <!-- ✅ Page Header -->
<div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-box"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Items
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
                        @canany(['item.print', 'item.export'])
                            <div class="dropdown">
                                <button
                                    class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    @include('icons.upload', ['size' => 19])
                                    Export
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end">
                                    @php
    $actions = exportActions('items', 'item');
                                    @endphp

                                    @foreach ($actions as $action)
                                        @can($action['permission'])
                                            <li>
                                                <a class="dropdown-item"
                                                    href="#"
                                                    data-route="{{ $action['route']  }}"
                                                    @foreach(($action['attrs'] ?? []) as $attr => $value)
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

                        {{-- Add Item Button --}}
                        @can('item.create')
                            <a href="#"
                                class="btn btn-outline-primary btn-sm js-load-modal"
                                data-module="masters/item/modal"
                                data-init="openItemCreateModal">
                                @include('icons.plus', ['size' => 20])
                                Add Item
                            </a>
                        @endcan
                        <a href="{{ route('back.to.previous') }}"
                            class="btn btn-sm btn-outline-dark back-btn waves-effect rounded-0 fw-medium shadow-sm">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
          {{--   <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center justify-content-between">

                        <!-- 🔹 Title & Subtitle -->
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="card border-0 border-start border-4 border-primary">
                                <div class="card-body shadow p-2">
                                    <h3 class="page-title font-monospace"><i class="fa-solid fa-box text-primary"></i>&nbsp;Item List</h3>  
                                </div>
                            </div>
                        </div>

                        <!-- 🔹 Action Buttons (Export & Add) -->
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                            <div class="card border-0 border-end border-4 border-primary">
                                <div class="card-body shadow-sm p-2">
                                    <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">

                                        @canany(['item.print', 'item.export'])
                                            <div class="dropdown">
                                                <button
                                                    class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    @include('icons.upload', ['size' => 19])
                                                    Export
                                                </button>

                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @php
    $actions = exportActions('items', 'item');
                                                    @endphp

                                                    @foreach ($actions as $action)
                                                        @can($action['permission'])
                                                            <li>
                                                                <a class="dropdown-item"
                                                                   href="#"
                                                                   data-route="{{ $action['route']  }}"
                                                                   @foreach(($action['attrs'] ?? []) as $attr => $value)
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
                                            <a href="#"
                                               class="btn btn-outline-primary btn-sm js-load-modal"
                                               data-module="masters/item/modal"
                                               data-init="openItemCreateModal">
                                                @include('icons.plus', ['size' => 20])
                                                Add Item
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div> --}}

            <!-- ✅ Page Body -->
            <div class="page-body">
                <div class="container-xl">
                    <div class="card">


                        <!-- 🔹 Collapsible Filter Form -->
                        <div id="filterCollapse">
                            <div class="card-body border-bottom">
                                <div class="row g-3">

                                    <!-- Search Input -->
                                    <div class="col-md-4 col-lg-3">
                                        <label class="form-label fw-bold">Search</label>
                                        <div class="input-group input-group-flat">
                                            <span class="input-group-text">
                                                @include('icons.search', ['size' => 15])
                                            </span>
                                            <input type="text"
                                                id="search"
                                                class="form-control"
                                                placeholder="Search..."
                                                autocomplete="off">
                                        </div>
                                    </div>

                                    <!-- Item Group Dropdown -->
                                    <div class="col-md-4 col-lg-3">
                                        <label class="form-label fw-bold">Select Group</label>
                                        <select id="group_id" class="form-select select2">
                                            <option value="">All Groups</option>
                                            @foreach ($itemGroups as $group)
                                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Filter Buttons -->
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

                                <!-- Active Filters Display -->
                                <div id="active-filters-display" class="mt-3 d-none">
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <small class="text-muted">Active filters:</small>
                                        <div id="filter-tags" class="d-flex flex-wrap gap-2"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 🔹 Item Table -->
                        <div class="card-body p-0">
                            <div id="item_table">
                                <!-- Table content will be loaded dynamically via JS -->
                            </div>
                        </div>

                        <!-- 🔹 Infinite Scroll Loader -->
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
    </div>
@include('company.partials._shortcuts-bar')
@endsection

@section('script')
    <script>
        const itemListUrl = "{{ route('items.index') }}";
        const createRouteUrl = "{{ route('items.create') }}";
        const editRouteUrl = "{{ route('items.edit', ':id') }}";
        const viewRouteUrl = "{{ route('items.show', ':id') }}";
        const deleteRouteUrl = "{{ route('items.destroy', ':id') }}";
    </script>
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
    
    <script src="{{ asset('js/master/item/index.js') }}?v={{ hash_file('md5', public_path('js/master/item/index.js')) }}"></script>  
@endsection
