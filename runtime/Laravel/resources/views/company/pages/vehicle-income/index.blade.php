@extends('company.layout.app')
@section('title', 'Vehicle Income Report')

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

        .tabulator .tabulator-calcs-bottom .tabulator-cell {
            background: #2d3a4a !important;
            color: #fff !important;
            font-weight: 700;
        }
        .tabulator .tabulator-calcs-bottom .tabulator-cell span {
            font-weight: 700;
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
        
        /* Fix Select2 Responsiveness (forces 100% width on window resize) */
        .select2-container {
            width: 100% !important;
        }

    </style>
@endsection

@section('content')
<div class="page-wrapper">

    {{-- ✅ Page Header --}}
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">

                    {{-- 🔹 Title --}}
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                            style="width: 38px; height: 38px;">
                            <i class="fs-3 fas fa-money-check-alt"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Vehicle Income Report
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-2 py-1"
                                style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-chart-bar me-1"></i> Report
                            </span>
                        </div>
                    </div>

                    {{-- 🔹 Action Buttons --}}
                    <div class="d-flex align-items-center gap-2">
                        <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                            @canany(['vehicle_income_report.print', 'vehicle_income_report  .export'])
                                <div class="dropdown">
                                    <button
                                        class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        @include('icons.upload', ['size' => 19])
                                        Export
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @php
                                            $actions = exportActions('vehicle-income', 'vehicle_income_report');
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

    {{-- ✅ Page Body --}}
    <div class="page-body">
        <form id="filter_form">
            <div class="container-xl">
                <div class="card">
                    {{-- 🔹 Filters --}}
                        <div class="p-3">
                            <div id="filters" class="row g-2 m-erp-po-row-1">

                                <div class="col-md-4 col-lg-1 m-erp">
                                    <label for="from_date" class="fs-4 fw-bold">From Date</label>
                                    <input type="text" id="from_date" class="form-control date-input" placeholder="DD-MM-YYYY">
                                </div>

                                <div class="col-md-4 col-lg-1 m-erp">
                                    <label for="to_date" class="fs-4 fw-bold">To Date</label>
                                    <input type="text" id="to_date" class="form-control date-input" placeholder="DD-MM-YYYY">
                                </div>

                                <div class="col-md-4 col-lg-1 m-erp">
                                    <label for="filter_vehicle" class="fs-4 fw-bold">Vehicle</label>
                                    <select id="filter_vehicle" class="form-select select2" data-placeholder="Select Vehicle...">
                                        <option value="">All Vehicles</option>
                                        @foreach($vehicles as $v)
                                            <option value="{{ $v->id }}">{{ $v->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 col-lg-3 m-erp">
                                    <label for="filter_account" class="fs-4 fw-bold">Party Name</label>
                                    <select id="filter_account" class="form-select select2" data-placeholder="Select Account...">
                                        <option value="">All Accounts</option>
                                        @foreach($accounts as $a)
                                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 col-lg-1 m-erp">
                                    <label for="filter_voucher_no" class="fs-4 fw-bold">Voucher No</label>
                                    <select id="filter_voucher_no" class="form-select select2" data-placeholder="Select Voucher No...">
                                        <option value="">All Vouchers</option>
                                        @foreach($voucherNumbers as $v)
                                            <option value="{{ $v->voucher_serial }}">{{ $v->voucher_serial }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 col-lg-1 m-erp">
                                    <label for="filter_type" class="fs-4 fw-bold">Type</label>
                                    <select id="filter_type" class="form-select select2" data-placeholder="Select Type...">
                                        <option value="all">All</option>
                                        <option value="freight">Freight</option>
                                        <option value="freight_invoice">Freight Invoice</option>
                                        <option value="freight_invoice2">Freight Invoice 2</option>
                                    </select>
                                </div>

                                {{-- Filter Buttons --}}
                                <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
                                    <button id="btn_apply" type="button" class="btn btn-primary flex-fill waves-effect">
                                        <i class="fa-solid fa-filter me-1"></i> Apply
                                    </button>
                                    <button id="btn_clear" type="button" class="btn btn-outline-secondary flex-fill waves-effect">
                                        <i class="fa-solid fa-times me-1"></i> Clear
                                    </button>
                                </div>

                            </div>
                        </div>
                    {{-- 🔹 Table --}}
                    <div class="card-body p-0">
                        <div id="vehicle_income_table"></div>
                    </div>
                </div>
            </div>
        </form>
    </div>

</div>
@endsection

@section('script')
<script>
    const vehicleIncomeDataUrl = "{{ route('vehicle-income.report-data') }}";
    const printUrl = "{{ route('vehicle-income.print') }}";
    const exportExcelUrl = "{{ route('vehicle-income.export.excel') }}";
</script>
<script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
<script src="{{ asset('js/modules/vehicle-income/index.js') }}?v={{ hash_file('md5', public_path('js/modules/vehicle-income/index.js')) }}"></script>
@endsection
