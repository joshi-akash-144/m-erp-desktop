@extends('company.layout.app')
@section('title', 'Sales Dairy Hisab Report')

@section('css')
<link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}"></link>
<link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}"></link>
@endsection

@section('content')
<div class="page-wrapper">
    <!-- ✅ Page Header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-receipt"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Sales Dairy Hisab Report
                            </h2>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        @can('sales_invoice_receipt.print')
                        <button id="btn_print" class="btn btn-sm btn-teal waves-effect">
                            @include('icons.print', ['size' => 18])
                            Print
                        </button>
                        @endcan
                        <a href="{{ route('sales-invoice-receipts.index') }}" class="btn btn-sm btn-outline-dark back-btn waves-effect">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ Page Body -->
    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <!-- 🔹 Filters Section -->
                <div class="p-3">
                    <form id="filter_form">
                        <div id="filters" class="row g-2 align-items-end">
                            <div class="col-md-4 col-lg-2">
                                <label for="start_date" class="fs-4 fw-bold">From Date</label>
                                <input type="text" id="start_date" placeholder="DD-MM-YYYY" class="form-control date-input">
                            </div>

                            <div class="col-md-4 col-lg-2">
                                <label for="end_date" class="fs-4 fw-bold">To Date</label>
                                <input type="text" id="end_date" placeholder="DD-MM-YYYY" class="form-control date-input">
                            </div>

                            <div class="col-md-4 col-lg-3">
                                <label for="customer_id" class="fs-4 fw-bold">Customer</label>
                                <select id="customer_id" class="form-select select2" data-placeholder="Select Customer">
                                    <option value="">All Customers</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 col-lg-2 d-flex gap-2">
                                <button id="filter_apply" type="submit" class="btn btn-primary flex-fill waves-effect">
                                    @include('icons.search', ['size' => 18])
                                    Search
                                </button>
                                <button id="filter_clear" type="button" class="btn btn-outline-secondary flex-fill waves-effect">
                                    @include('icons.filter-clear', ['size' => 18])
                                    Clear
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- 🔹 Report Table -->
                <div class="card-body p-0">
                    <div id="sales_invoice_receipt_report_table"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    const salesInvoiceReceiptReportDataUrl = "{{ route('sales-invoice-receipts.report.data') }}";
    const salesInvoiceReceiptReportPrintUrl = "{{ route('sales-invoice-receipts.report.print') }}";
</script>
<script src="{{ asset('js/modules/sales-invoice-receipt/report.js') }}?v={{ hash_file('md5', public_path('js/modules/sales-invoice-receipt/report.js')) }}"></script>
@endsection
