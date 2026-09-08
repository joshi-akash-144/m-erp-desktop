@extends('company.layout.app')
@section('title', 'Purchase Invoice – Create')

@section('css')
<link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}"></link>
<style>
    .m-erp-po-row-1 {
        grid-template-columns: 0.8fr 1fr 1fr 1fr 1fr 2fr 1fr 0.8fr;
    }

    .m-erp-po-row-2 {
        grid-template-columns: 1fr 1.5fr 1fr 1fr 1.5fr 1fr;
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
        background-color: #dfefff !important;
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
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-cart-shopping"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Purchase Invoice
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-green text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-plus me-1"></i> NEW ENTRY
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                        @can('purchase_invoice.update')
                            <a href="{{ route('purchase-invoices.edit') }}" class="btn btn-sm btn-outline-primary waves-effect rounded-0 fw-medium shadow-sm"
                                data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit Invoice">
                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                            </a>
                        @endcan
                        <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn waves-effect rounded-0 fw-medium shadow-sm">
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
                                <i class="fa-solid fa-cart-shopping me-2 text-primary"></i>
                                Purchase Invoice
                            </h3>
                            <span class="ribbon ribbon-bookmark bg-lime text-uppercase">
                                <i class="fa-solid fa-plus me-1"></i> New Entry
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 🔹 Action Buttons -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                    <div class="card border-0 border-end border-4 border-primary">
                        <div class="card-body shadow-sm p-2">
                            <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                <a href="{{ route('purchase-invoices.edit') }}" class="btn btn-sm btn-outline-primary waves-effect"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit Order">
                                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                </a>
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
        <div class="container-xl">
            <div class="card p-2 shadow-sm">
                @include('company.pages.purchase-invoice._form', ['formMode' => 'create'])
            </div>
        </div>
    </div>
</div>

@include('company.pages.purchase-invoice._particular-modal')
@include('company.partials._shortcuts-bar', [
    'barModuleLabel'     => 'Purchase Invoice Create',
    'barModuleShortcuts' => [
        ['key' => 'Alt+O', 'label' => 'Purchase Order',     'type' => 'info', 'permission' => 'purchase_order.create', 'url' => route('purchase-orders.create')],
        ['key' => 'Alt+U', 'label' => 'Purchase Order List',  'type' => 'info', 'permission' => 'purchase_order.list', 'url' => route('purchase-orders.index')],
        ['key' => 'Alt+G', 'label' => 'GRN',                'type' => 'info', 'permission' => 'grn.create', 'url' => route('grns.create')],
        ['key' => 'Alt+R', 'label' => 'GRN List',           'type' => 'info', 'permission' => 'grn.list', 'url' => route('grns.index')],
        ['key' => 'Alt+L', 'label' => 'Ledger',             'type' => 'info', 'permission' => 'ledger_report.list', 'url' => route('ledger-report.index')],
        ['key' => 'Alt+P', 'label' => 'Purchase Invoice List', 'type' => 'info', 'permission' => 'purchase_invoice.list', 'url' => route('purchase-invoices.index')],
    ],
]) 

@endsection

@section('script')
<script>
    const checkDuplicateReferenceFromPI = "{{ route('purchase-invoices.check_duplicate_reference') }}";
    const grnDetailsRoute               = "{{ route('purchase-invoices.get_grn_details') }}";
    const billSundryData = @json($billSundry);
    const allLedgers = @json($allLedgers);
    const getSupplierTurnOverRoute = "{{ route('purchase-invoices.get_supplier_turn_over') }}";
    
</script>
<script src="{{ asset('js/modules/sales-purchase-common.js') }}?v={{ hash_file('md5', public_path('js/modules/sales-purchase-common.js')) }}"></script>
<script src="{{ asset('js/modules/purchase-invoice/bill-sundry.js') }}?v={{ hash_file('md5', public_path('js/modules/purchase-invoice/bill-sundry.js')) }}"></script>
<script src="{{ asset('js/modules/purchase-invoice/get-grn.js') }}?v={{ hash_file('md5', public_path('js/modules/purchase-invoice/get-grn.js')) }}"></script>
<script src="{{ asset('js/modules/purchase-invoice/create.js') }}?v={{ hash_file('md5', public_path('js/modules/purchase-invoice/create.js')) }}"></script>
<script src="{{ asset('js/modules/purchase-invoice/check-duplicate-ref.js') }}?v={{ hash_file('md5', public_path('js/modules/purchase-invoice/check-duplicate-ref.js')) }}"></script>
@endsection