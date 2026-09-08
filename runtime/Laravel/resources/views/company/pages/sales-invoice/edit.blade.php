@extends('company.layout.app')
@section('title', 'Sales Invoice – Edit')

@section('css')
<link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}"></link>
    <style>
        .m-erp-so-row-1 {
            grid-template-columns: 190px 190px 190px 0.5fr 190px;
        }

        .m-erp-so-row-2 {
            grid-template-columns: 1fr 190px 190px 190px 1fr 190px;
        }

        .m-erp-so-row-3 {
            grid-template-columns: 190px 0.3fr 190px 0.6fr 1fr;
        }

        @media (max-width: 992px) {

            .m-erp-so-row-1,
            .m-erp-so-row-2 {
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

        /* Apply only inside this table */
        #particular_table_body .select2-small+.select2-container .select2-selection--single {
            min-height: 28px !important;
            height: 28px !important;
            line-height: 26px !important;
            padding: 0 0.25rem !important;
            font-size: 0.875rem !important;
            /* small text */
            border-radius: 0 !important;
            display: flex;
            align-items: center;
        }

        /* Rendered text */
        #particular_table_body .select2-small+.select2-container .select2-selection__rendered {
            padding-left: 0 !important;
            padding-right: 1.2em !important;
            font-size: 0.920rem !important;
            line-height: normal !important;
        }

        /* Arrow */
        #particular_table_body .select2-small+.select2-container .select2-selection__arrow {
            height: 28px !important;
            width: 24px !important;
        }

        #particular_table_body .select2-small+.select2-container .select2-selection__arrow b {
            border-width: 4px 4px 0 4px !important;
        }

        /* Dropdown options small */
        #particular_table_body .select2-container--default .select2-results__option {
            font-size: 0.875rem !important;
            padding: 4px 8px !important;
        }


        #particular_table_body input.form-control,
        #particular_table_body input[type="text"],
        #particular_table_body input[type="number"],
        #particular_table_body input[type="date"] {
            height: 28px !important;
            min-height: 28px !important;
            padding: 2px 6px !important;
            font-size: 1rem !important;
            line-height: 24px !important;
            border-radius: 0 !important;
        }

        /* Row highlight */
        .highlight-row .select2-container .select2-selection--single {
            background-color: #dfefff !important;
            transition: background-color 0.2s ease;
        }

        .highlight-row .select2-container {
            background-color: transparent !important;
        }

        .erp-btn-icon {
            width: 24px;
            height: 24%;
        }
    </style>
@endsection

@section('content')
    <div class="page-wrapper">
        <!-- ✅ Page Header -->
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
                                Sales Invoice
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-orange text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-plus me-1"></i> EDIT ENTRY
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
        {{-- <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center justify-content-between">

                    <!-- 🔹 Title & Subtitle -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="card border-0 border-start border-4 border-sales">
                            <div class="card-body shadow p-2">
                                <h3 class="page-title font-monospace text-sales">
                                    <i class="fa-solid fa-cart-shopping me-2"></i>
                                    Sales Invoice
                                </h3>
                                <span class="ribbon ribbon-bookmark bg-orange">
                                    <i class="fa-solid fa-pencil me-1"></i> EDIT ENTRY
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                        <div class="card border-0 border-end border-4 border-sales">
                            <div class="card-body shadow-sm p-2">
                                <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                      <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
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
            <div class="container-xl"> 
                <div class="card p-2 shadow-sm">
                    @include('company.pages.sales-invoice._form', ['formMode' => 'edit'])
                </div>
            </div>
        </div>
    </div>

    @include('company.pages.sales-invoice._particular-modal')
    @include('company.pages.sales-invoice._po-selection-modal')
@include('company.partials._shortcuts-bar', [
    'barModuleLabel'     => 'Sales Invoice Edit',
    'barModuleShortcuts' => [
       ['key' => 'Alt+L', 'label' => 'Sales Invoice List',   'type' => 'info', 'permission' => 'sales_invoice.list', 'url' => route('sales-invoices.index')],
       ['key' => 'Alt+O', 'label' => 'Sales Order',          'type' => 'info', 'permission' => 'sales_order.create', 'url' => route('sales-orders.create')],
       ['key' => 'Alt+R', 'label' => 'Sales Order List',     'type' => 'info', 'permission' => 'sales_order.list', 'url' => route('sales-orders.index')],
       ['key' => 'Alt+G', 'label' => 'Ledger',               'type' => 'info', 'permission' => 'ledger_report.list', 'url' => route('ledger-report.index')],
    ],
])
@endsection

@section('script')
    <script>
        const validateGrnUrl         = "{{ route('sales-invoices.validate_grn') }}";
        const salesOrderDetailsRoute = "{{ route('sales-invoices.salesOrder.details', ':id') }}";
        const editSalesInvoiceRoute  = "{{ route('sales-invoices.edit') }}";
        const updateSalesInvoiceUrl  = "{{ route('sales-invoices.update', ':id') }}";
        const billSundryData         = @json($billSundry);
        const allLedgers             = @json($allLedgers);
    </script>
    <script src="{{ asset('js/modules/sales-purchase-common.js') }}?v={{ hash_file('md5', public_path('js/modules/sales-purchase-common.js')) }}"></script>
    <script src="{{ asset('js/modules/sales-invoice/bill-sundry.js') }}?v={{ hash_file('md5', public_path('js/modules/sales-invoice/bill-sundry.js')) }}"></script>
    <script src="{{ asset('js/modules/sales-invoice/get-sale-order.js') }}?v={{ hash_file('md5', public_path('js/modules/sales-invoice/get-sale-order.js')) }}"></script>
    <script src="{{ asset('js/modules/sales-invoice/edit.js') }}?v={{ hash_file('md5', public_path('js/modules/sales-invoice/edit.js')) }}"></script>
    <script src="{{ asset('js/modules/sales-invoice/create.js') }}?v={{ hash_file('md5', public_path('js/modules/sales-invoice/create.js')) }}"></script>
@endsection
