@extends('company.layout.app')
@section('title', 'Sales Order - List')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    <style>
        .m-erp-so-row-1 {
            grid-template-columns: 1fr 190px 190px 1fr 1fr 1fr 1fr;
        }

        .m-erp-so-row-2 {
            grid-template-columns: 0.8fr 0.6fr 170px 0.7fr 1fr;
        }

        @media (max-width: 992px) {

            .m-erp-so-row-1,
            .m-erp-so-row-2 {
                grid-template-columns: 1fr;
            }
        }

        .m-erp {
            width: 170px;
        }
        /* Red border on invalid input */
        /* Invalid field styling */
    </style>
@endsection

@section('content')
    <div class="page-wrapper">
        <!-- Page Header -->
        <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-sales rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-sales text-sales rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-cart-plus"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-sales fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Sales Order
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-list me-1"></i> report
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                        {{-- Export Dropdown (only for users with print/export permission) --}}
                        @canany(['sales_order.print', 'sales_order.export'])
                            <div class="dropdown">
                                <button
                                    class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    @include('icons.upload', ['size' => 19])
                                    Export
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end">
                                    @php
                                        $actions = exportActions('sales-orders', 'sales_order');
                                    @endphp

                                    @foreach ($actions as $action)
                                        @can($action['permission'])
                                            <li>
                                                <a class="dropdown-item" href="#"
                                                    data-route="{{ $action['route'] }}"
                                                    @foreach ($action['attrs'] ?? [] as $attr => $value)
                                                            {{ $attr }}="{{ $value }}" @endforeach>
                                                    @include($action['icon'])
                                                    {{ $action['label'] }}
                                                </a>
                                            </li>
                                        @endcan
                                    @endforeach
                                </ul>
                            </div>
                        @endcanany

                        {{-- Add Sales Order Button --}}
                        @can('sales_order.create')
                            <a href="{{ route('sales-orders.create') }}" class="btn btn-outline-primary btn-sm" id="getSelectedRowsBtn">
                                @include('icons.plus', ['size' => 20])
                                Add New
                            </a>
                        @endcan
                        <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
        </div>
       {{--  <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center justify-content-between">

                    <!-- 🔹 Title & Subtitle -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="card border-0 border-start border-4 border-primary">
                            <div class="card-body shadow p-2">
                                <h3 class="page-title font-monospace"><i
                                        class="fa-solid fa-user-group text-primary"></i>&nbsp;Sales Order List</h3>
                            </div>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons (Export & Add) -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                        <div class="card border-0 border-end border-4 border-primary">
                            <div class="card-body shadow-sm p-2">
                                <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">

                                    @canany(['sales_order.print', 'sales_order.export'])
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse"
                                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                @include('icons.upload', ['size' => 19])
                                                Export
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @php
                                                    $actions = exportActions('sales-orders', 'sales_order');
                                                @endphp

                                                @foreach ($actions as $action)
                                                    @can($action['permission'])
                                                        <li>
                                                            <a class="dropdown-item" href="#"
                                                                data-route="{{ $action['route'] }}"
                                                                @foreach ($action['attrs'] ?? [] as $attr => $value)
                                                                        {{ $attr }}="{{ $value }}" @endforeach>
                                                                @include($action['icon'])
                                                                {{ $action['label'] }}
                                                            </a>
                                                        </li>
                                                    @endcan
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endcanany

                                    @can('sales_order.create')
                                        <a href="{{ route('sales-orders.create') }}" class="btn btn-outline-primary btn-sm" id="getSelectedRowsBtn">
                                            @include('icons.plus', ['size' => 20])
                                            Add New
                                        </a>
                                    @endcan
                                    <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
                                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </div> --}}

        <!-- Page Body -->
        <div class="page-body">
            <form  method="GET">
                <div class="container-xl">
                    <div class="card">

                        <!-- 🔹 Filters Section -->
                        <div class="p-3" id="filters">
                            
                                <div class="row g-2 m-erp-so-row-1 section-brown">
                                    <!-- SO No -->
                                    <div class="col-md-4 col-lg-1">
                                        <label for="so_id" class="fs-4 fw-bold">So No</label>
                                        <select id="so_id" class="form-select select2">
                                            <option value="">Select SO No</option>
                                            @foreach ($orderSerials as $orderSerial)
                                                <option value="{{ $orderSerial->id }}">{{ $orderSerial->order_serial }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Start Date -->
                                    <div class="col-md-4 col-lg-1">
                                        <label for="start_date" class="fs-4 fw-bold">Start Date</label>
                                        <input type="text" name="order_date" id="start_date" class="form-control" placeholder="DD-MM-YYYY"
                                            value="{{ isset($filters['order_date']) }}">
                                    </div>

                                    <!-- End Date -->
                                    <div class="col-md-4 col-lg-1">
                                        <label for="end_date" class="fs-4 fw-bold">End Date</label>
                                        <input type="text" name="due_date" id="end_date" class="form-control" placeholder="DD-MM-YYYY"
                                            value="{{ isset($filters['due_date']) }}">
                                    </div>

                                    <!-- Supplier Name -->
                                    <div class="col-md-4 col-lg-3 ">
                                        <label for="account_id" class="fs-4 fw-bold">Customer Name</label>
                                        <select id="account_id" class="form-select select2" data-placeholder="Select Customer">
                                            <option value="">Customer Name</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Item -->
                                    <div class="col-md-4 col-lg-2">
                                        <label for="item_id" class="fs-4 fw-bold">Item Name</label>
                                        <select id="item_id" class="form-select select2" data-placeholder="Select Items">
                                            <option value="">Select Item</option>
                                            @foreach ($items as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Todo: Add Destination list  hear  --}}
                                    <!-- Condition -->
                                    {{-- <div class="col-md-4  col-lg-2">
                                        <label for="condition_id" class="fs-4" value="">Condition</label>
                                        <select id="condition_id" class="form-select select2">
                                            <option value="">Select Condition</option>
                                            @foreach ($conditions as $condition)
                                                <option value="{{ $condition->id }}">{{ $condition->name }}</option>
                                            @endforeach
                                        </select>
                                    </div> --}}

                                    @php
                                            $orderStatus = [
                                            'all' => 'All',
                                            'open' => 'Open',
                                            'close' =>'Close',                                                                                           
                                            ];                              
                                    @endphp
                                    <!-- Dynamic Status Dropdown -->
                                    <div class="col-md-4 col-lg-1">                                                        
                                        <label for="order_status" class="fs-4 fw-bold" value="">Order Status</label>
                                        <select id="order_status" class="form-select select2">
                                            <option value="">Select Status</option> 
                                            @foreach ($orderStatus as $statuses => $status)
                                                <option value="{{ $statuses }}" {{ $statuses === 'open' ? 'selected' : '' }}>
                                                    {{ $status }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
        
                                     <!-- Due Status -->
                                    {{-- <div class="col-md-4 col-lg-1">                                                        
                                        <label for="due_status" class="fs-4" value="">Due Status</label>
                                        <select id="due_status" name="due_status" class="form-select select2">
                                            <option value=""></option>                                             
                                            <option value="0">No</option>                                             
                                            <option value="1">Yes</option>                                             
                                        </select>
                                    </div> --}}

                                    <!-- Filter Buttons -->
                                    <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
                                        <button id="filter_apply" class="btn btn-primary flex-fill">
                                            @include('icons.filter', ['size' => 20])
                                            Apply
                                        </button>
                                        <button id="filter_clear" class="btn btn-outline-secondary flex-fill">
                                            @include('icons.filter-clear', ['size' => 20])
                                            Clear
                                        </button>
                                        <button type="button" class="btn btn-outline-danger flex-fill waves-effect d-none" id="closeSelectedBtn">
                                            Close Selected
                                        </button>
                                    </div>

                                </div>                        
                            
                            {{-- <span id="active-filter-badge"
                                class="badge rounded-pill bg-primary-subtle text-primary fw-semibold px-3 py-2 d-none">
                                <span id="filter-count">0</span> Active
                            </span> --}}
                        </div>

                        <!-- 🔹 Collapsible Filter Form -->
                        <div class="collapse" id="filterCollapse">
                            <div class="card-body border-bottom">
                                <!-- Active Filters Display -->
                                <div id="active-filters-display" class="mt-3 d-none">
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <small class="text-muted"></small>
                                        <div id="filter-tags" class="d-flex flex-wrap gap-2"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 🔹 Account Group Table -->
                        <div class="card-body p-0">
                            <div id="sales_order_table">
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
            </form>
        </div>
    </div>

    {{-- View Modal --}}
    <div class="modal fade" id="sale_order_modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        @include('company.pages.sales-order._view_modal')
    </div>
@endsection

@section('script')
   
    <script>
        const salesOrderListUrl = "{{ route('sales-orders.index') }}";
        const salesOrderViewUrl = "{{ route('sales-orders.show', ':id') }}";
        const salesOrderEditUrl = "{{ route('sales-orders.edit', ':id') }}";      
    </script>
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>    
    <script src="{{ asset('js/modules/sales-order/index.js') }}?v={{ hash_file('md5', public_path('js/modules/sales-order/index.js')) }}"></script>
@endsection