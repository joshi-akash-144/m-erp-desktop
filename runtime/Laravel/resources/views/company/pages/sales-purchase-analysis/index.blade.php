@extends('company.layout.app')
@section('title', 'Sales Purchase Analysis')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <style>        
        .form-control-sm, .form-select-sm { padding: 0.25rem 0.5rem; font-size: 0.85rem; }
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
                                <i class="fs-3 fa-solid fa-balance-scale"></i>
                            </div>
                            <div>
                                <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                    Sales Purchase Analysis
                                </h2>
                            </div>
                            <div class="ms-md-3">
                                <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                    <i class="fa-solid fa-list"></i> Report
                                </span>
                            </div>
                        </div>

                        <!-- 🔹 Action Buttons -->
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-outline-success" id="export-excel-btn">
                                <i class="fa-solid fa-file-excel me-1"></i> Export Excel
                            </button>
                            <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
                                <i class="fa-solid fa-arrow-left me-1"></i> Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Body -->
        <div class="page-body">
            <div class="container-xl">
                <div class="card">
                    <!-- 🔹 Filters Section -->
                    <form>
                        <div class="p-3">
                            <div class="row g-2" id="filters">
                                <div class="col-md-2 col-lg-1">
                                    <label class="filter-label fw-bold">Start Date (Sale)</label>
                                    <input type="text" id="start_date" class="form-control" placeholder="DD-MM-YYYY">
                                </div>
                                <div class="col-md-2 col-lg-1">
                                    <label class="filter-label fw-bold">End Date (Sale)</label>
                                    <input type="text" id="end_date" class="form-control" placeholder="DD-MM-YYYY">
                                </div>
                                <div class="col-md-2 col-lg-2">
                                    <label class="filter-label fw-bold">Customer</label>
                                    <select id="account_id" class="form-control">
                                        <option value="">All Customers</option>
                                        @foreach($accounts as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 col-lg-2">
                                    <label class="filter-label fw-bold">Product</label>
                                    <select id="item_id" class="form-control">
                                        <option value="">All Products</option>
                                        @foreach($items as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 col-lg-1">
                                    <label class="filter-label fw-bold">Dairy PO</label>
                                    <input type="text" id="so_serial" class="form-control" placeholder="PO Number">
                                </div>
                                {{-- <div class="col-md-2 col-lg-1">
                                    <label class="filter-label fw-bold">Purchase Order</label>
                                    <input type="text" id="po_serial" class="form-control" placeholder="PO Number">
                                </div> --}}

                                <!-- Filter Buttons -->
                                <div class="col-md-4 col-lg-auto d-flex align-items-end gap-2 text-nowrap">
                                    <button id="filter_apply" class="btn btn-primary flex-fill waves-effect">
                                        @include('icons.filter')
                                        Apply
                                    </button>
                                    <button id="filter_clear" class="btn btn-outline-secondary flex-fill waves-effect">
                                        @include('icons.filter-clear')
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <!-- 🔹 Table Section -->
                    <div class="card-body p-0">
                        <div id="analysis_table"></div>
                    </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('company.pages.sales-purchase-analysis._modal')

@endsection

@section('script')
    <script>
        const getRegisterDataUrl = "{{ route('sales-purchase-analysis.index') }}";
        const exportExcelUrl = "{{ route('sales-purchase-analysis.export.excel') }}";
        const exportDetailedExcelUrl = "{{ route('sales-purchase-analysis.export.detailed.excel') }}";
    </script>
    <script src="{{ asset('js/modules/sales-purchase-analysis/index.js') }}?v={{ hash_file('md5', public_path('js/modules/sales-purchase-analysis/index.js')) }}"></script>
    <script src="{{ asset('js/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
@endsection
