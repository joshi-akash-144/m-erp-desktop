@extends('company.layout.app')
@section('title', 'Delivery Challan – List')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}"></link>   
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}"></link>
 
    <style>
        .m-erp-po-row-1 {
            grid-template-columns: 190px 140px 140px 140px 1fr 1fr 1fr;
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
                        <div class="row g-2 align-items-center justify-content-between">

                            <!-- 🔹 Title & Subtitle -->
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                <div class="card border-0 border-start border-4 border-primary">
                                    <div class="card-body shadow p-2">
                                        <h3 class="page-title font-monospace">                                            
                                            <i class="fa-solid fa-truck-moving me-2 text-primary"></i>
                                            Delivery Challan
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
                                            {{-- Export Dropdown (only for users with print/export permission) --}}
                                            @canany(['delivery_challan.print', 'delivery_challan.export'])
                                                <div class="dropdown">
                                                    <button
                                                        class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        @include('icons.upload', ['size' => 19])
                                                        Export
                                                    </button>

                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        @php
                                                            $actions = exportActions('delivery-challans', 'delivery_challan');
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

                                            {{-- Add Delivery Challan Button --}}
                                            <div class="waves-effect">
                                                @can('delivery_challan.create')
                                                    <a href="{{ route('delivery-challans.create') }}" class="btn btn-outline-primary btn-sm" id="getSelectedRowsBtn">
                                                        @include('icons.plus', ['size' => 20])
                                                        Add New
                                                    </a>
                                                @endcan
                                            </div>
                                            <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
                                                <i class="fa-solid fa-arrow-left me-1"></i> Back
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <!-- ✅ Page Body -->
                <div class="page-body"> 
                    <form method="GET">
                        <div class="container-xl">
                            <div class="card">
                                <!-- 🔹 Filters Section -->
                                <div class="p-3">
                                    <div id='filters' class="row g-2 m-erp-po-row-1">
                                        <!-- Delivery Challan Serial -->
                                        <div class="col-md-4 col-lg-2 m-erp"> D.C. Number
                                            <select id="dc_number" class="form-select select2">
                                                <option value="">D.C. No.</option>
                                                @foreach($challanSerials as $orderSerial)
                                                    <option value="{{ $orderSerial->challan_number }}">{{ $orderSerial->challan_number }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <!-- GRN Number -->
                                        {{-- <div class="col-md-4 col-lg-1 m-erp"> Grn Number
                                            <select id="grn_no" name="grn_no" class="form-select select2">
                                                <option value="">Grn. Id.</option>
                                                @foreach($grnSerials as $orderSerial)
                                                    <option value="{{ $orderSerial->id }}">{{ $orderSerial->id }}</option>
                                                @endforeach
                                            </select>
                                        </div> --}}
    
                                        <!-- Start Date -->
                                        <div class="col-md-4 col-lg-1 m-erp"> Start Date
                                            <input type="text" name="start_date" id="start_date" placeholder="DD-MM-YYYY" class="form-control date-input"
                                                 value="{{ isset($filters['order_date']) }}">
                                        </div>
    
                                        <!-- End Date -->
                                        <div class="col-md-4 col-lg-1 m-erp"> End Date
                                            <input type="text" name="due_date" id="end_date" placeholder="DD-MM-YYYY" class="form-control"
                                                 value="{{ isset($filters['due_date']) }}">
                                        </div>
    
                                        <!-- Customer Name -->
                                        <div class="col-md-4 col-lg-2 m-erp"> Customer Name
                                            <select id="account_id" class="form-select select2">
                                                <option value="">Customer Name</option>
                                                @foreach ($customers as $customer)
                                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
    
                                        <!-- Item -->
                                        <div class="col-md-4 col-lg-2 m-erp"> Items Name
                                            <select id="item_id" class="form-select select2">
                                                <option value="">Item</option>
                                                @foreach ($items as $item)
                                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
    
                                        <!-- Broker Name -->
                                        <div class="col-md-4 col-lg-2 m-erp"> Broker Name
                                            <select id="broker_id" class="form-select select2">
                                                <option value="">Broker Name</option>
                                                @foreach ($brokers as $broker)
                                                    <option value="{{ $broker->id }}">{{ $broker->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
    
                                        <!-- Condition --> 
                                        <div class="col-md-4 col-lg-2 m-erp"> Condition
                                            <select id="condition_id" class="form-select select2">
                                                <option value="">Condition</option>
                                                @foreach ($conditions as $condition)
                                                    <option value="{{ $condition->id }}">{{ $condition->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <!-- Destination -->
                                        <div class="col-md-4 col-lg-2 m-erp"> Destination
                                            <select id="destination_id" class="form-select select2">
                                                <option value="">Destination</option>
                                                @foreach ($destinations as $destination)
                                                    <option value="{{ $destination->id }}">{{ $destination->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <!-- Dynamic Status Dropdown -->
                                        @php
                                            $deliveryChallanStatus = config('constants.delivery_challan_status');
                                        @endphp                                    
                                        <div class="col-md-3 col-lg-1"> Delivery Status
                                            <select id="delivery_status" class="form-select select2">
                                                <option value="">Status</option>
                                                @foreach ($deliveryChallanStatus as $statuses => $status)
                                                    <option value="{{ $statuses }}" {{ $statuses === 'open' ? 'selected' : '' }}>
                                                        {{ $status }}  
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <!-- Dynamic Status Dropdown -->
                                        @php
                                            $qc_status = config('constants.qc_status');
                                        @endphp
                                        {{-- <div class="col-md-3 col-lg-1"> QC Status
                                            <select id="qc_status" class="form-select select2">
                                                <option value="">Status</option>
                                                @foreach ($qc_status as $statuses => $status)
                                                    <option value="{{ $status }}">
                                                        {{ $status }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div> --}}
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
    
                                <!-- 🔹 Delivery Challan Table -->
                                <div class="card-body p-0">
                                    <div id="delivery_challan_table">
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
                    <div class="container-xl">
                        <div class="card p-2 shadow-sm">
                            @include('company.pages.delivery-challan._view_modal', ['formMode' => 'view'])
                        </div>
                    </div>
                </div>
            </div>

@endsection

@section('script')
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
    <script src="{{ asset('js/modules/delivery-challan/index.js') }}?v={{ hash_file('md5', public_path('js/modules/delivery-challan/index.js')) }}"></script>    
    <script>
        const deliveryChallanListUrl = "{{ route('delivery-challans.index') }}";
        const deliveryChallanViewUrl = "{{ route('delivery-challans.show', ':id') }}";
        const deliveryChallanEditUrl = "{{ route('delivery-challans.edit', ':id') }}";
        const deliveryChallanPrintLetterUrl = "{{ route('delivery-challans.print-letter') }}";
    </script> 
  
@endsection
