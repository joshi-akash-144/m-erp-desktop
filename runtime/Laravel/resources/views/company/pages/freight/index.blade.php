@extends('company.layout.app')
@section('title', 'Freight – List')

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

    /* Fix Select2 Multiple UI height */
    .select2-container--bootstrap-5 .select2-selection--multiple {
        max-height: 80px;
        overflow-y: auto;
    }
    
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
        font-size: 0.85rem;
        padding: 0.15rem 0.5rem;
    }
    </style>
@endsection
@section('content')
            <div class="page-wrapper">
 <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-sales rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-sales-lt text-sales rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-cart-plus"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-sales fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Freight
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
                        @canany(['freight.print', 'freight.export'])
                            <div class="dropdown">
                                <button
                                    class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    @include('icons.upload', ['size' => 19])
                                    Export
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end">
                                    @php
                                        $actions = exportActions('freight', 'freight');
                                    @endphp
                                    @foreach ($actions as $action)
                                        @can($action['permission'])
                                            <li class="{{ ($action['key'] ?? '') === 'print' ? 'd-none' : '' }}">
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

                        @can('freight.create')
                            <a href="{{ route('freight.create') }}"
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
                <!-- ✅ Page Body -->
                <div class="page-body"> 
                    <form method="GET">
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
                                            <input type="text" name="end_date" id="end_date" placeholder="DD-MM-YYYY" class="form-control"
                                                 value="{{ isset($filters['end_date']) }}">
                                        </div>
    
                                        <!-- Bill To Party -->
                                        <div class="col-md-4 col-lg-2 m-erp">
                                        <label for="end_date" class="fs-4 fw-bold" value="">Bill To </label> 
                                            <select id="account_id" class="form-select select2" data-placeholder="Select Bill To">
                                                <option value="">Bill To Party</option>
                                                    @foreach ($companies as $company)
                                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                                    @endforeach
                                            </select>
                                        </div>

                                        <!-- Bill No -->
                                        <div class="col-md-4 col-lg-2 m-erp"> 
                                        <label for="bill_id" class="fs-4 fw-bold" value="">Bill No</label>
                                            <select name="bill_id" id="bill_id" class="form-select select2" data-placeholder="Select Bill No">
                                                <option value="">Bill No</option>
                                                @foreach ($bills as $bill)
                                                    <option value="{{ $bill->id }}">{{ $bill->bill_number }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Lr Number -->
                                        <div class="col-md-4 col-lg-2 m-erp"> 
                                        <label for="lr_number_id" class="fs-4 fw-bold" value="">LR Number</label>
                                            <input type="number" name="lr_number_id" id="lr_number_id" placeholder="LR Number" class="form-control">
                                        </div>
    
                                        <!-- Item -->
                                        <div class="col-md-4 col-lg-4 m-erp"> 
                                        <label for="item_id" class="fs-4 fw-bold" value="">Item Name</label>
                                            <select id="item_id" name="item_id[]" class="form-select select2" data-placeholder="Select Item" multiple="multiple">
                                                @foreach ($items as $item)
                                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                                @endforeach 
                                            </select>
                                        </div>
                                    </div>
                                    <div id='filters' class="row g-2 m-erp-po-row-1">

                                        <!-- Sender Name -->
                                        <div class="col-md-4 col-lg-2 m-erp">
                                        <label for="consignor_id" class="fs-4 fw-bold" value="">Sender Name</label> 
                                            <select id="consignor_id" class="form-select select2" data-placeholder="Select Sender">
                                                <option value="">Sender Name</option>
                                                @foreach ($consignors as $consignor)
                                                    <option value="{{ $consignor->id }}">{{ $consignor->name }} ({{ $consignor->city }})</option>
                                                @endforeach 
                                            </select>
                                        </div>

                                        <!-- Receiver Name -->
                                        <div class="col-md-4 col-lg-2 m-erp">
                                        <label for="consignee_id" class="fs-4 fw-bold" value="">Receiver Name</label> 
                                            <select id="consignee_id" class="form-select select2" data-placeholder="Select Receiver">
                                                <option value="">Receiver Name</option>
                                                @foreach ($consignees as $consignee)
                                                    <option value="{{ $consignee->id }}">{{ $consignee->name }} ({{ $consignee->city }})</option>
                                                @endforeach 
                                            </select>
                                        </div>

                                        <!-- Vehicle Name -->   
                                        <div class="col-md-4 col-lg-2 m-erp">
                                        <label for="vehicle_id" class="fs-4 fw-bold" value="">Vehicle No</label> 
                                            <select id="vehicle_id" class="form-select select2" data-placeholder="Select Vehicle">
                                                <option value="">Vehicle No</option>
                                                @foreach ($vehicles as $vehicle)
                                                    <option value="{{ $vehicle->id }}">{{ $vehicle->vehicle_number }}</option>
                                                @endforeach 
                                            </select>
                                        </div>
    
                                        <!-- From Destination --> 
                                        <div class="col-md-4 col-lg-2 m-erp">
                                        <label for="from_destination_id" class="fs-4 fw-bold" value="">From Destination</label> 
                                            <select id="from_destination_id" class="form-select select2" data-placeholder="Select From Destination">
                                                <option value="">From Destination</option>
                                                @foreach ($destinations as $destination)
                                                    <option value="{{ $destination->id }}">{{ $destination->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <!-- To Destination -->
                                        <div class="col-md-4 col-lg-2 m-erp">
                                        <label for="to_destination_id" class="fs-4 fw-bold" value="">To Destination</label> 
                                            <select id="to_destination_id" class="form-select select2" data-placeholder="Select To Destination">
                                                <option value="">To Destination</option>
                                                @foreach ($destinations as $destination)
                                                    <option value="{{ $destination->id }}">{{ $destination->name }}</option>
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
    
                                <!-- 🔹 Freight Table -->
                                <div class="card-body p-0">
                                    <div id="freight_table">
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
                        </div>
                    </div>
                </div>
            </div>

@endsection

@section('script')

    <script>
        const freightListUrl = "{{ route('freight.index') }}";
        const freightPrintUrl = "{{ route('freight.freight-print', ':id') }}";
        const freightEditUrl = "{{ route('freight.edit', ':id') }}";
    </script>
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
    <script src="{{ asset('js/modules/freight/index.js') }}?v={{ hash_file('md5', public_path('js/modules/freight/index.js')) }}"></script>    
  
@endsection
