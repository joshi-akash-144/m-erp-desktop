@extends('company.layout.app')
@section('title', 'Transporters - List')
@section('css')
    <link rel="stylesheet"
        href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    </link>
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}"></link>
@endsection
@section('content')
    <div class="page-wrapper">
        <div class="col-lg-12">
            <!-- ✅ Page Header -->
 <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-truck"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Transporter
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-list"></i> report
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                        {{-- Export Dropdown (only for users with print/export permission) --}}
                     @canany(['godown_module.transporter_print', 'godown_module.transporter_export'])
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                @include('icons.upload', ['size' => 19])
                                                Export
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @php
                                                $actions = exportActions('godown-module.print-transporter', 'godown_module');
                                                @endphp
 
                                                @foreach ($actions as $action)
                                                @php
                                                    $permission = ($action['key'] === 'print') ? 'godown_module.transporter_print' : 'godown_module.transporter_export';
                                                @endphp
                                                @can($permission)
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

                        <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn waves-effect rounded-0 fw-medium shadow-sm">
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
                                    <h3 class="page-title font-monospace"><i
                                            class="fa-solid fa-truck text-primary"></i>&nbsp;Transporter List</h3>
                                </div>
                            </div>  
                        </div>

                        <!-- 🔹 Action Buttons (Export & Add) -->
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                            <div class="card border-0 border-end border-4 border-primary">
                                <div class="card-body shadow-sm p-2">
                                    <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">

                                        @canany(['godown_module.transporter_print', 'godown_module.transporter_export'])
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                @include('icons.upload', ['size' => 19])
                                                Export
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @php
                                                $actions = exportActions('godown-module.print-transporter', 'godown_module');
                                                @endphp
 
                                                @foreach ($actions as $action)
                                                @php
                                                    $permission = ($action['key'] === 'print') ? 'godown_module.transporter_print' : 'godown_module.transporter_export';
                                                @endphp
                                                @can($permission)
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
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>  --}}

            <!-- ✅ Page Body -->
            <div class="page-body">
                <div class="container-xl">
                    <div class="card">

                        <!-- 🔹 Collapsible Filter Form -->
                        <form action="">
                            <div class="card-body border-bottom">
                                <div class="row g-2 m-erp-so-row-1">

                                 <!-- Start Date -->
                                    <div class="col-md-4 col-lg-1">
                                        <label for="start_date" class="fs-4 fw-bold">Start Date</label>
                                        <input type="text" name="start_date" id="start_date" class="form-control"
                                                value="" placeholder="DD-MM-YYYY">
                                    </div>

                                    <!-- End Date -->
                                    <div class="col-md-4 col-lg-1">
                                        <label for="end_date" class="fs-4 fw-bold">End Date</label>
                                        <input type="text" name="end_date" id="end_date" class="form-control"
                                                value="" placeholder="DD-MM-YYYY">
                                    </div>

                                    <!-- Transporter Name -->
                                    <div class="col-md-4 col-lg-3">
                                        <label for="transporter_id" class="fs-4 fw-bold">Transporter Name</label>
                                        <select id="transporter_id" class="form-select select2">
                                            <option value="">Transporter Name</option>
                                            @foreach ($transporters as $transporter)
                                                <option value="{{ $transporter->id }}">{{ $transporter->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <!-- Lr Number -->
                                    <div class="col-md-4 col-lg-1">
                                        <label for="lr_number" class="fs-4 fw-bold">Lr Number</label>
                                        <input type="text" id="lr_number" class="form-control" placeholder="Enter Lr Number">
                                    </div>

                                    <!-- Filter Buttons -->
                                    <div class="col-md-4 col-lg-auto d-flex align-items-end gap-2 text-nowrap">
                                        <button id="filter_apply" class="btn btn-primary flex-fill waves-effect">
                                            @include('icons.filter', ['size' => 20])
                                            Apply
                                        </button>
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
                        </form>
                        <!-- 🔹 Item Table -->
                        <div class="card-body p-0">
                            <div id="transporter_list_table">
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
@endsection

@section('script')
    <script>
        const transporterListUrl = "{{ route('godown-module.transporter-list') }}";
    </script>
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>

    <script
        src="{{ asset('js/modules/godown/transporter-list.js') }}?v={{ hash_file('md5', public_path('js/modules/godown/transporter-list.js')) }}"></script>
@endsection