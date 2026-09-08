@extends('company.layout.app')
@section('title', 'Delivery Challan – Create')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}"></link>
    <style>
        .m-erp-po-row-1 {
            grid-template-columns: 170px 140px  0.7fr 1fr 0.5fr 140px 150px;
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
                        <div class="card border-0 border-start border-4 border-sales">
                            <div class="card-body shadow p-2">
                                <h3 class="page-title font-monospace text-sales">
                                    <i class="fa-solid fa-truck-field me-2"></i>
                                    Delivery Challan
                                </h3>
                                <span class="ribbon ribbon-bookmark bg-lime text-uppercase">
                                    <i class="fa-solid fa-plus me-1"></i> New Entry
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                        <div class="card border-0 border-end border-4 border-sales">
                            <div class="card-body shadow-sm p-2">
                                <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                    <a href="{{ route('delivery-challans.edit') }}" class="btn btn-sm btn-outline-primary waves-effect"
                                        data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit Challan">
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
        </div>


        <!-- ✅ Page Body -->
        <div class="page-body">
            <div class="container-xl">
                <div class="card p-2 shadow-sm">
                    @include('company.pages.delivery-challan._form', ['formMode' => 'create'])
                </div>
            </div>
        </div>
    </div>
@include('company.partials._shortcuts-bar', [
    'barModuleLabel'     => 'Delivery Challan Create',
    'barModuleShortcuts' => [
        ['key' => 'Alt+O', 'label' => 'Sales Order',     'type' => 'info', 'permission' => 'sales_order.create', 'url' => route('sales-orders.create')],
        ['key' => 'Alt+I', 'label' => 'Sales Invoice','type' => 'info', 'permission' => 'sales_invoice.create', 'url' => route('sales-invoices.create')],
        ['key' => 'Alt+L', 'label' => 'Ledger',          'type' => 'info', 'permission' => 'ledger_report.list', 'url' => route('ledger-report.index')],
        ['key' => 'Alt+V', 'label' => 'Debit Note',      'type' => 'info', 'permission' => 'debit_note.create']
    ],
])
@endsection
@section('script')
    <script>
        const pendingSalesOrders = "{{ route('delivery-challans.pending_sales_orders') }}";
        const storeDeliveryChallanRouteUrl = "{{ route('delivery-challans.store') }}";
    </script>
    <script src="{{ asset('js/modules/sales-purchase-common.js') }}?v={{ hash_file('md5', public_path('js/modules/sales-purchase-common.js')) }}"></script>
    <script src="{{ asset('js/modules/delivery-challan/create.js') }}?v={{ hash_file('md5', public_path('js/modules/delivery-challan/create.js')) }}"></script>
@endsection