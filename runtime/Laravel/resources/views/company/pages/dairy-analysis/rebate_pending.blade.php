@extends('company.layout.app')
@section('title', 'Dairy Analysis Rebate Pending List')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <style>        
        .form-control-sm, .form-select-sm { padding: 0.25rem 0.5rem; font-size: 0.85rem; }        */
    </style>
@endsection

@section('content')
<div class="page-wrapper">
    <!-- Page Header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-flask"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Dairy Analysis - Rebate Pending
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-warning-lt text-warning fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-chart-line"></i> Pending
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                              @canany(['dairy_analysis.print', 'dairy_analysis.export'])
                                            <div class="dropdown">
                                                <button
                                                    class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    @include('icons.upload', ['size' => 19])
                                                    Export
                                                </button>

                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @php
                                                        $actions = exportActions('dairy-analysis.rebate-pending', 'dairy_analysis');
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
                        {{-- Back Button --}}
                        <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
      <!-- Page Header -->
           {{--  <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center justify-content-between">

                        <!-- 🔹 Title & Subtitle -->
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="card border-0 border-start border-4 border-primary">
                                <div class="card-body shadow p-2">
                                    <h3 class="page-title font-monospace">                                        
                                        <i class="fa-solid fa-clock-rotate-left me-2 text-primary" ></i>
                                        </i>Dairy Anal. Rebate Pending</h3>  
                                </div>
                            </div>
                        </div>

                        <!-- 🔹 Action Buttons (Export & Add) -->
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                            <div class="card border-0 border-end border-4 border-primary">
                                <div class="card-body shadow-sm p-2">
                                    <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">

                                        @canany(['dairy_analysis.print', 'dairy_analysis.export'])
                                            <div class="dropdown">
                                                <button
                                                    class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    @include('icons.upload', ['size' => 19])
                                                    Export
                                                </button>

                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @php
                                                        $actions = exportActions('dairy-analysis.rebate-pending', 'dairy_analysis');
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

                                        <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
                                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                                    </a>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
            </div  --}}

    <!-- Page Body -->
    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <!-- 🔹 Filters Section -->
                <form>
                    <div class="p-3">
                        <div class="row g-2" id="filters">
                            <div class="col-md-2 col-lg-1">
                                <label class="filter-label">Start Date</label>
                                <input type="text" id="start_date" class="form-control" placeholder="DD-MM-YYYY">
                            </div>
                            <div class="col-md-2 col-lg-1">
                                <label class="filter-label">End Date</label>
                                <input type="text" id="end_date" class="form-control" placeholder="DD-MM-YYYY">
                            </div>

                            {{-- @dd($suppliers); --}}
                            <div class="col-md-4 col-lg-2">
                                <label class="filter-label">Supplier Name</label>
                                <select id="supplier_id" class="form-select select2" data-placeholder="All Suppliers">
                                    <option value="">All Suppliers</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 col-lg-2">
                                <label class="filter-label">Customer Name</label>
                                <select id="account_id" class="form-select select2" data-placeholder="All Customers">
                                    <option value="">All Customers</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 col-lg-1">
                                <label class="filter-label">Item</label>
                                <select id="item_id" class="form-select select2">
                                    <option value="">All Items</option>
                                    @foreach($items as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 col-lg-1">
                                <label class="filter-label">Destination</label>
                                <select id="destination_id" class="form-select select2" data-placeholder="All Destinations">
                                    <option value="">All Destinations</option>
                                    @foreach($destinations as $dest)
                                        <option value="{{ $dest->id }}">{{ $dest->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <label class="filter-label">Rebate Status</label>
                                <select id="rebate_status" class="form-select select2">
                                    <option value="">All Status</option>
                                    @foreach(config('constants.rebate_status') as $key => $status)
                                        <option value="{{ $key }}" class="text-center">{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filter Buttons -->
                            <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2 text-nowrap">
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
                    </div>
                </form>
                <!-- 🔹 Table Section -->
                <div class="card-body p-0">
                    <div id="dairy_analysis_rebate_pending_table"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
     <script>
        const dairyAnalysisRebatePendingUrl = "{{ route('dairy-analysis.rebate-pending.index') }}";            
    </script>
    <script src="{{ asset('js/modules/dairy-analysis/rebate-pending.js') }}?v={{ hash_file('md5', public_path('js/modules/dairy-analysis/rebate-pending.js')) }}"></script>
@endsection
