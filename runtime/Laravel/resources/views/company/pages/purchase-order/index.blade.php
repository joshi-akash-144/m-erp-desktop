@extends('company.layout.app')
@section('title', 'Purchase Order - List')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}"></link>
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}"></link>
@endsection

@section('content')
        <div class="page-wrapper">
 <!-- ✅ Page Header -->
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                    <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">
                        
                        <!-- 🔹 Title Section -->
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                                <i class="fs-3 fa-solid fa-cart-shopping"></i>
                            </div>
                            <div>
                                <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                    Purchase Order
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
                            @canany(['purchase_order.print', 'purchase_order.export'])
                                <div class="dropdown">
                                    <button
                                        class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        @include('icons.upload', ['size' => 19])
                                        Export
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @php
                                            $actions = exportActions('purchase-orders', 'purchase_order');
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

                            {{-- Column Visibility Dropdown --}}
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle btn-sm waves-effect"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                                    <i class="fa-solid fa-table-columns me-1"></i> Columns
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow" id="column-visibility-menu" style="max-height: 300px; overflow-y: auto;">
                                    <!-- Checkboxes will be populated here via JS -->
                                </ul>
                            </div>

                            {{-- Add Purchase Order Button --}}
                            @can('purchase_order.create')
                                <div class="waves-effect">
                                    <a href="{{ route('purchase-orders.create') }}"
                                        class="btn btn-outline-primary btn-sm"
                                        id="addPurchaseOrderBtn">
                                        @include('icons.plus', ['size' => 20])
                                            Add New
                                    </a>
                                </div>
                            @endcan

                            <a href="{{ route('back.to.previous') }}"
                                class="btn btn-sm btn-outline-dark back-btn waves-effect">
                                <i class="fa-solid fa-arrow-left me-1"></i> Back
                            </a>

                        </div>
                    </div>
                </div>

            </div>
        </div>
            <!-- Page Header -->
          {{--   <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center justify-content-between">

                        <!-- 🔹 Title & Subtitle -->
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="card border-0 border-start border-4 border-primary">
                                <div class="card-body shadow p-2">
                                    <h3 class="page-title font-monospace">                                                
                                        <i class="fa-solid fa-file-invoice me-2 text-primary"></i>
                                        </i>Purchase Order List</h3>  
                                </div>
                            </div>
                        </div>

                        <!-- 🔹 Action Buttons (Export & Add) -->
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                            <div class="card border-0 border-end border-4 border-primary">
                                <div class="card-body shadow-sm p-2">
                                    <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">

                                        @canany(['purchase_order.print', 'purchase_order.export'])
                                            <div class="dropdown">
                                                <button
                                                    class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    @include('icons.upload', ['size' => 19])
                                                    Export
                                                </button>

                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @php
                                                        $actions = exportActions('purchase-orders', 'purchase_order');
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

                                        @can('purchase_order.create')
                                            <div class="waves-effect">
                                                <a href="{{ route('purchase-orders.create') }}"
                                                    class="btn btn-outline-primary btn-sm"
                                                    id="addPurchaseOrderBtn">
                                                    @include('icons.plus', ['size' => 20])
                                                        Add New
                                                </a>
                                            </div>
                                        @endcan

                                        <a href="{{ route('back.to.previous') }}"
                                            class="btn btn-sm btn-outline-dark back-btn waves-effect">
                                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                                        </a>

                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
            </div>--}}

            <!-- Page Body -->
            <div class="page-body">
                <form  method="GET">
                    <div class="container-xl">
                        <div class="card">
                            <!-- 🔹 Filters Section -->
                            <div class="p-3">
                                <div class="row g-2 m-erp-po-row-1" id="filters">
                                    <!-- PO No -->
                                    <div class="col-md-4 col-lg-1">
                                        <label for="po_id" class="fs-4 fw-bold">Po No</label>                                  
                                        <select id="po_id" class="form-select select2">
                                            <option value="">Select PO No</option>                                        
                                            @foreach($orderSerials as $orderSerial)                                        
                                                <option value="{{ $orderSerial->id }}">{{ $orderSerial->order_serial }}</option>
                                            @endforeach
                                        </select>
                                    </div>

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

                                    <!-- Supplier Name -->
                                    <div class="col-md-4 col-lg-3">
                                        <label for="account_id" class="fs-4 fw-bold">Supplier Name</label>
                                        <select id="account_id" class="form-select select2" data-placeholder="Select Supplier">
                                            <option value="">Supplier Name</option>
                                            @foreach ($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }} @if($account->city) ({{ $account->city }}) @endif</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Item -->
                                    <div class="col-md-4 col-lg-2">
                                        <label for="item_id" class="fs-4 fw-bold">Item Name</label>
                                        <select id="item_id" class="form-select select2" data-placeholder="Select Item">  
                                                <option value="">Select Item</option>                                                       
                                            @foreach ($items as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Broker Name -->
                                    <div class="col-md-4 col-lg-2">
                                        <label for="broker_id" class="fs-4 fw-bold">Broker Name</label>
                                        <select id="broker_id" class="form-select select2" data-placeholder="Select Broker">
                                            <option value="">Select Broker</option>    
                                            @foreach ($brokers as $broker)
                                                <option value="{{ $broker->id }}">{{ $broker->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Condition --> 
                                    <div class="col-md-4 col-lg-2">
                                        <label for="condition_id" class="fs-4 fw-bold">Condition</label>
                                        <select id="condition_id" class="form-select select2" data-placeholder="Select Condition">
                                            <option value="">Select Condition</option> 
                                            @foreach ($conditions as $condition)                                        
                                                <option value="{{ $condition->id }}">{{ $condition->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Destination -->
                                    <div class="col-md-4 col-lg-2">
                                        <label for="destination_id" class="fs-4 fw-bold" value="">Destination</label>
                                        <select id="destination_id" class="form-select select2" data-placeholder="Select Destination">
                                            <option value="">Destination</option>
                                            @foreach ($destinations as $destination)
                                                <option value="{{ $destination->id }}">{{ $destination->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>


                                    @php
                                            $orderStatus = [
                                            'all' => 'All',
                                            'open' => 'Open',
                                            'close' =>'Close',                                            
                                            // 'hold'=>'Hold',                                                
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
                                    <div class="col-md-4 col-lg-1">                                                        
                                        <label for="due_status" class="fs-4 fw-bold" value="">Due Status</label>
                                        <select id="due_status" name="due_status" class="form-select select2" data-placeholder="Due Status">
                                            <option value=""></option>                                             
                                            <option value="0">No</option>                                             
                                            <option value="1">Yes</option>                                             
                                        </select>
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
                                        <button id="urgent_email" class="btn btn-info flex-fill waves-effect">
                                            @include('icons.mail', ['size' => 20])
                                            Urgent Email  
                                        </button>
                                        <button type="button" class="btn btn-outline-danger flex-fill waves-effect d-none" id="closeSelectedBtn">
                                            Close Selected
                                        </button>
                                    </div>
                                </div>                                
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

                            <!-- 🔹 Purchase Order Table -->
                            <div class="card-body p-0">
                                <div id="purchase_order_table">
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
                {{-- View Modal --}}
                <div class="modal fade" id="purchase_order_modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
                    @include('company.pages.purchase-order._view_modal')
                </div>
                
                {{-- Urgent Email Modal --}}
                @include('company.pages.purchase-order._urgent-email-modal')
            </div>
        </div>           
@endsection
@section('script')
    <script>
        const purchaseOrderListUrl = "{{ route('purchase-orders.index') }}";
        const purchaseOrderEditUrl = "{{ route('purchase-orders.edit', ':id') }}";
        const purchaseOrderViewUrl = "{{ route('purchase-orders.show', ':id') }}";
        const urgentEmailPreviewUrl = "{{ route('mail.preview_urgent_purchase_order_email') }}";
        const urgentEmailSendUrl = "{{ route('mail.send_urgent_purchase_order_email') }}";
        const hugertePath = "{{ asset('js/libs/hugerte') }}";
    </script>
    <script src="{{ asset('js/libs/hugerte/hugerte.min.js') }}"></script>
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
    <script src="{{ asset('js/modules/purchase-order/index.js') }}?v={{ hash_file('md5', public_path('js/modules/purchase-order/index.js')) }}"></script>    
@endsection