@extends('company.layout.app')
@section('title', 'Purchase Order Detail with GRN – List')

@section('css')
<link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}"></link>
<link rel="stylesheet" href="{{ asset('css/module/view.css') }}?v={{ hash_file('md5', public_path('css/module/view.css')) }}"></link>
<link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}"></link>
    
    <style>
        .m-erp-po-row-1 {
            grid-template-columns: 170px 140px 140px 140px 1fr 1fr 1fr;
        }

        .m-erp-po-row-2 {
            grid-template-columns: 0.8fr 0.6fr 170px 0.7fr 0.7fr 1fr;
        }



        @media (max-width: 992px) {

            .m-erp-po-row-1,
            .m-erp-po-row-2 {
                grid-template-columns: 1fr;
            }
        }
           #item_table {
            table-layout: fixed;
            width: 100%;
        }
        #item_table {
            table-layout: fixed;
            width: 100%;
        }

        #item_table th,
        #item_table td {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #item_table input,
        #item_table select {
            width: 100%;
            box-sizing: border-box;
        }

        .input-icon-addon {
            font-size: 16px;
            color: #dc3545;

        }

        .input-icon-addon:hover {
            color: #bb2d3b;
        }

        .date-input {
        color: black; /* Color of text as the user types */
    }

    /* Target the placeholder text specifically across browsers */
    .date-input::placeholder {
        color: #aaaaaa; /* Lighter gray color for the 'dd-mm-yyyy' text */
        opacity: 1; /* Firefox default is lower opacity, this ensures consistency */
    }
    </style>
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
                                Purchase Order - GRN Details
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-list me-1"></i> REPORT
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                        @canany(['purchase_order_with_grn.print', 'purchase_order_with_grn.export'])
                        <div class="dropdown">
                            <button
                                class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                @include('icons.upload', ['size' => 19])
                                Export
                            </button>

                            <ul class="dropdown-menu dropdown-menu-end">
                                @php
                                    $actions = exportActions('purchase-order-with-grn', 'purchase_order_with_grn');
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

                    <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn waves-effect">
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
                                        <h3 class="page-title font-monospace">                                            
                                            <i class="fa-solid fa-file-invoice me-2 text-primary"></i>
                                            Purchase Order - GRN Details 
                                        </h3>
                                        <!-- <span class="ribbon ribbon-bookmark bg-blue">
                                            <i class="fa-solid fa-eye me-1"></i> LIST DATA
                                        </span> -->
                                    </div>
                                </div>
                            </div>

                            <!-- 🔹 Action Buttons -->
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                                <div class="card border-0 border-end border-4 border-primary">
                                    <div class="card-body shadow-sm p-2">
                                        <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                            @canany(['purchase_order_with_grn.print', 'purchase_order_with_grn.export'])
                                                <div class="dropdown">
                                                    <button
                                                        class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        @include('icons.upload', ['size' => 19])
                                                        Export
                                                    </button>

                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        @php
                                                            $actions = exportActions('purchase-order-with-grn', 'purchase_order_with_grn');
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

                                            <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn waves-effect">
                                                <i class="fa-solid fa-arrow-left me-1"></i> Back
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>--}}
                <!-- ✅ Page Body -->
                <div class="page-body">
                     <form  method="GET">
                        <div class="container-xl">
                            <div class="card">
                                <!-- 🔹 Filters Section -->
                                <div class="p-3">
                                    <div id='filters' class="row g-2 m-erp-po-row-1">
                                        <!-- Start Date -->
                                        <div class="col-md-4 col-lg-1 m-erp"> 
                                        <label for="start_date" class="fs-4 fw-bold" value="">Start Date</label>
                                            <input type="text" name="start_date" id="start_date" placeholder="DD-MM-YYYY" class="form-control date-input"
                                                value="{{ isset($filters['order_date']) }}">
                                        </div>

                                        <!-- End Date -->
                                        <div class="col-md-4 col-lg-1 m-erp"> 
                                        <label for="end_date" class="fs-4 fw-bold" value="">End Date</label>
                                            <input type="text" name="due_date" id="end_date" placeholder="DD-MM-YYYY" class="form-control date-input"
                                                value="{{ isset($filters['due_date']) }}">
                                        </div>

                                        <!-- Supplier Name -->
                                        <div class="col-md-4 col-lg-3 m-erp"> 
                                        <label for="account_id" class="fs-4 fw-bold" value="">Supplier Name</label>
                                            <select id="account_id" class="form-select select2" data-placeholder="Select Supplier">
                                                <option value="">Supplier Name</option>
                                                @foreach ($accounts as $account)
                                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Item -->
                                        <div class="col-md-4 col-lg-2 m-erp"> 
                                        <label for="item_id" class="fs-4 fw-bold" value="">Items Name</label>
                                            <select id="item_id" class="form-select select2" data-placeholder="Select Item">
                                                <option value="">Item</option>
                                                @foreach ($items as $item)
                                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        
                                        <!-- Destination -->
                                        <div class="col-md-4 col-lg-2 m-erp"> 
                                        <label for="destination_id" class="fs-4 fw-bold" value="">Destination</label>
                                            <select id="destination_id" class="form-select select2" data-placeholder="Select Destination">
                                                <option value="">Destination</option>
                                                @foreach ($destinations as $destination)
                                                <option value="{{ $destination->id }}">{{ $destination->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        
                                        <!-- Broker Name -->
                                        <div class="col-md-4 col-lg-2 m-erp"> 
                                        <label for="broker_id" class="fs-4 fw-bold" value="">Broker Name</label>
                                            <select id="broker_id" class="form-select select2" data-placeholder="Select Broker">
                                                <option value="">Broker Name</option>
                                                @foreach ($brokers as $broker)
                                                    <option value="{{ $broker->id }}">{{ $broker->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Order Status --> 
                                        @php
                                            $regions = [
                                                'all'=>'All',
                                                'open' => 'Open',
                                                'close' =>'Close',
                                            ];
                                        @endphp
                                        <!-- Dynamic Status Dropdown -->
                                        <div class="col-md-4 col-lg-1 m-erp">                                                        
                                            <label for="order_status" class="fs-4 fw-bold" value="">Order Status</label>
                                            <select id="order_status" class="form-select select2" data-placeholder="Select Status">
                                                <option value="">Select Status</option> 
                                                @foreach ($regions as $key => $label)
                                                    <option value="{{ $key }}">
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <!-- Filter Buttons -->
                                        <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
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

                                <!-- 🔹 Purchase Order Grn Details Table -->
                                <div class="card-body p-0">
                                    <div id="purchase_order_detail_table">
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
                        <div class="container-xl">
                            <div class="card p-2 shadow-sm">
                                <!-- for modal -->
                            </div>
                        </div>
                    </form>
                </div>
            </div>

@endsection

@section('script')
    <script>          
            const purchaseOrderDetailListUrl = "{{ route('purchase-orders.purchase_order_with_grn') }}";
    </script>
    <script src="{{ asset('js/modules/purchase-order/purchase-order-detail-with-grn.js') }}?v={{ hash_file('md5', public_path('js/modules/purchase-order/purchase-order-detail-with-grn.js')) }}"></script>     
@endsection
