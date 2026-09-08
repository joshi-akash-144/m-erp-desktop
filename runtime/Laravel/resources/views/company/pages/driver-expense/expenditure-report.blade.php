@extends('company.layout.app')
@section('title', 'Vehicle Expenditure Report')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <style>
        /* Profit / Loss column coloring (applied via JS inline style) */
        .tabulator .tabulator-calcs-bottom .tabulator-cell {
            background: #2d3a4a !important;
            color: #fff !important;
            font-weight: 700;
        }
        .tabulator .tabulator-calcs-bottom .tabulator-cell span {
            font-weight: 700;
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
                            <i class="fs-3 fa-solid fa-truck-fast"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Vehicle Expenditure Report
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
                        @can('vehicle_expenditure.export')
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                @include('icons.upload', ['size' => 19])
                                Export
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="#" id="export_xlsx_btn" data-route="{{ route('vehicle-expenditure.export.excel') }}">
                                        @include('icons.xlsx')
                                        Excel
                                    </a>
                                </li>
                            </ul>
                        </div>
                        @endcan
                        <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ✅ Page Body --}}
    <div class="page-body">
        <div class="container-xl">
            <div class="card">

                {{-- 🔹 Filters --}}
                <form id="filter_form" autocomplete="off">
                    <div class="p-3">
                        <div id="filters" class="row g-2">

                            <div class="col-md-4 col-lg-1">
                                <label for="from_date" class="form-label fw-bold">From Date</label>
                                <input type="text" id="from_date" class="form-control date-format" placeholder="DD-MM-YYYY">
                            </div>

                            <div class="col-md-4 col-lg-1">
                                <label for="to_date" class="form-label fw-bold">To Date</label>
                                <input type="text" id="to_date" class="form-control date-format" placeholder="DD-MM-YYYY">
                            </div>

                            <div class="col-md-4 col-lg-2">
                                <label for="filter_vehicle" class="form-label fw-bold">Vehicle</label>
                                <select id="filter_vehicle" class="form-select">
                                    <option value="">All Vehicles</option>
                                    @foreach($vehicles as $v)
                                        <option value="{{ $v->id }}">{{ $v->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 col-lg-2">
                                <label for="view_type" class="form-label fw-bold">View Type</label>
                                <select id="view_type" class="form-select">
                                    <option value="summary">Summary</option>
                                    <option value="detail">Detail</option>
                                </select>
                            </div>

                            {{-- Filter Buttons --}}
                            <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
                                <button id="btn_apply" type="button" class="btn btn-primary flex-fill waves-effect">
                                    @include('icons.filter', ['size' => 20])
                                    Apply
                                </button>
                                <button id="btn_clear" type="button" class="btn btn-outline-secondary flex-fill waves-effect">
                                    @include('icons.filter-clear', ['size' => 20])
                                    Clear
                                </button>
                            </div>

                        </div>
                    </div>
                </form>

                {{-- 🔹 Table --}}
                <div class="card-body p-3 pt-0">
                    <div id="vehicle_expenditure_table"></div>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection

@section('script')
<script>
    const vehicleExpenditureDataUrl = "{{ route('vehicle-expenditure.report-data') }}";
</script>
<script src="{{ asset('js/modules/driver-expense/expenditure-report.js') }}?v={{ time() }}"></script>
@endsection
