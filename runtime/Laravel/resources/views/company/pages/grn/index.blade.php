@extends('company.layout.app')
@section('title', 'GRN – List')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}"></link>   
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
                                Goods Receipt Note
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
                        @canany(['grn.print', 'grn.export'])
                            <div class="dropdown">
                                <button
                                    class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    @include('icons.upload', ['size' => 19])
                                    Export
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end">
                                    @php
                                        $actions = exportActions('grns', 'grn');
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

                        @can('grn.create')
                            <a href="{{ route('grns.create') }}"
                                class="btn btn-sm btn-outline-primary waves-effect">
                                <i class="fa-solid fa-plus me-1"></i> Add New
                            </a>
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
                <!-- ✅ Page Header -->
                {{-- <div class="page-header d-print-none">
                    <div class="container-xl">
                        <div class="row g-2 align-items-center justify-content-between">

                            <!-- 🔹 Title & Subtitle -->
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                <div class="card border-0 border-start border-4 border-primary">
                                    <div class="card-body shadow p-2">
                                        <h3 class="page-title font-monospace">                                            
                                            <i class="fa-solid fa-file-invoice me-2 text-primary"></i>
                                            Goods Receipt Note
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
                                            @canany(['grn.print', 'grn.export'])
                                                <div class="dropdown">
                                                    <button
                                                        class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        @include('icons.upload', ['size' => 19])
                                                        Export
                                                    </button>

                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        @php
        $actions = exportActions('grns', 'grn');
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

                                            @can('grn.create')
                                                <a href="{{ route('grns.create') }}"
                                                    class="btn btn-sm btn-outline-primary waves-effect">
                                                    <i class="fa-solid fa-plus me-1"></i> Add New
                                                </a>
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
                </div> --}}
                <!-- ✅ Page Body -->
                <div class="page-body"> 
                    <form method="GET">
                        <div class="container-xl">
                            <div class="card">
                                <!-- 🔹 Filters Section -->
                                <div class="p-3">
                                    <div id='filters' class="row g-2 m-erp-po-row-1">
                                        <!-- GRN Serial -->
                                        <div class="col-md-4 col-lg-1 m-erp">
                                        <label for="grn_serial" class="fs-4 fw-bold" value="">Grn Number</label>
                                            <select id="grn_serial" class="form-select select2">
                                                <option value="">Grn No.</option>
                                                @foreach($grnSerials as $grnSerial)
                                                    <option value="{{ $grnSerial->grn_serial }}">{{ $grnSerial->grn_serial }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Reference Number -->
                                        <div class="col-md-4 col-lg-1 m-erp">
                                        <label for="reference_number" class="fs-4 fw-bold" value="">Bill Number</label>
                                            <input type="number" placeholder="Enter Bill No" id="reference_number" class="form-control">
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
                                        <div class="col-md-4 col-lg-1 m-erp">
                                        <label for="start_date" class="fs-4 fw-bold" value="">Start Date</label>
                                            <input type="text" name="start_date" id="start_date" placeholder="DD-MM-YYYY" class="form-control date-input"
                                                 value="{{ isset($filters['order_date']) }}">
                                        </div>
    
                                        <!-- End Date -->
                                        <div class="col-md-4 col-lg-1 m-erp"> 
                                        <label for="end_date" class="fs-4 fw-bold" value="">End Date</label>
                                            <input type="text" name="due_date" id="end_date" placeholder="DD-MM-YYYY" class="form-control"
                                                 value="{{ isset($filters['due_date']) }}">
                                        </div>
    
                                        <!-- Supplier Name -->
                                        <div class="col-md-4 col-lg-2 m-erp">
                                        <label for="end_date" class="fs-4 fw-bold" value="">Supplier Name</label> 
                                            <select id="account_id" class="form-select select2" data-placeholder="Select Supplier">
                                                <option value="">Supplier Name</option>
                                                @foreach ($accounts as $account)
                                                    <option value="{{ $account->id }}">{{ $account->name }} @if($account->city) ({{ $account->city }}) @endif</option>
                                                @endforeach
                                            </select>
                                        </div>
    
                                        <!-- Item -->
                                        <div class="col-md-4 col-lg-2 m-erp"> 
                                        <label for="item_id" class="fs-4 fw-bold" value="">Item Name</label>
                                            <select id="item_id" class="form-select select2" data-placeholder="Select Item">
                                                <option value="">Item</option>
                                                @foreach ($items as $item)
                                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
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
    
                                        <!-- Condition --> 
                                        <div class="col-md-4 col-lg-2 m-erp">
                                        <label for="condition_id" class="fs-4 fw-bold" value="">Condition</label> 
                                            <select id="condition_id" class="form-select select2" data-placeholder="Select Condition">
                                                <option value="">Condition</option>
                                                @foreach ($conditions as $condition)
                                                    <option value="{{ $condition->id }}">{{ $condition->name }}</option>
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
                                        <!-- Dynamic Status Dropdown -->
                                        @php
                                            
                                            $grnStatus = [
                                                'all' => 'All',
                                                'open' => 'Open',
                                                'close' =>'Close',
                                                'billed'=>'Billed',
                                                // 'hold'=>'Hold',                                                
                                            ];       
                                        @endphp
                                        <div class="col-md-3 col-lg-1">
                                        <label for="grn_status" class="fs-4 fw-bold" value="">GRN Status</label> 
                                            <select id="grn_status" class="form-select select2">
                                                <option value="">Status</option>
                                                @foreach ($grnStatus as $statuses => $status)
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
                                        <div class="col-md-4 col-lg-auto d-flex align-items-end gap-2 text-nowrap">
                                            <button id="filter_apply" class="btn btn-primary flex-fill waves-effect">
                                                @include('icons.filter', ['size' => 20])
                                                Apply
                                            </button>
                                            <button id="filter_clear" class="btn btn-outline-secondary flex-fill waves-effect">
                                                @include('icons.filter-clear', ['size' => 20])
                                                Clear
                                            </button>
                                            @can('grn.delete')
                                                <button type="button" id="btn_delete_selected" class="btn btn-outline-danger flex-fill waves-effect d-none">
                                                    <i class="fa-solid fa-trash me-1"></i> Delete Selected
                                                </button>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
    
                                <!-- 🔹 GRN Table -->
                                <div class="card-body p-0">
                                    <div id="grn_table">
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
                            @include('company.pages.grn._view_modal', ['formMode' => 'view'])
                        </div>
                    </div>
                </div>
            </div>

@endsection

@section('script')
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
    <script src="{{ asset('js/modules/grn/index.js') }}?v={{ hash_file('md5', public_path('js/modules/grn/index.js')) }}"></script>    
    <script>
        const grnListUrl = "{{ route('grns.index') }}";
        const grnViewUrl = "{{ route('grns.show', ':id') }}";
        const grnEditUrl = "{{ route('grns.edit', ':id') }}";
        const grnPrintRecieptUrl = "{{ route('grns.grnPrint', ':id') }}";
        const grnDeleteSelectedUrl = "{{ route('grns.delete_selected') }}";
    </script> 
  
@endsection
