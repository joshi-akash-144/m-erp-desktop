@extends('company.layout.app')
@section('title', 'GRN – Edit')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}"></link>
    <style>
        .m-erp-po-row-1 {
            grid-template-columns: 1fr 0.8fr 0.8fr 0.8fr 2fr 1fr 0.7fr;
        }

        .m-erp-po-row-2 {
            grid-template-columns: 0.5fr 1fr 1fr 1fr;
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
                            <span class="badge badge-pill bg-orange text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-plus me-1"></i> Edit ENTRY
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                       @can('grn.print')
                            <a class="btn btn-sm btn-outline-primary waves-effect" onclick="printGrn($('#grn_id').val())" id="print_grn_btn">
                                <i class="fa-solid fa-print hover:text-white"></i>&nbsp;Print
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
                                    Goods Receipt Note
                                </h3>
                                <span class="ribbon ribbon-bookmark bg-orange">
                                    <i class="fa-solid fa-pencil me-1"></i> EDIT ENTRY
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                        <div class="card border-0 border-end border-4 border-primary">
                            <div class="card-body shadow-sm p-2">
                                <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                    @can('grn.print')
                                        <a class="btn btn-sm btn-outline-primary waves-effect" onclick="printGrn($('#grn_id').val())" id="print_grn_btn">
                                            <i class="fa-solid fa-print hover:text-white"></i>&nbsp;Print
                                        </a>
                                    @endcan
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
                    @include('company.pages.grn._form', ['formMode' => 'edit'])
                </div>
            </div>
        </div>
    </div>

    @include('company.pages.grn._po_modal')
@include('company.partials._shortcuts-bar', [
    'barModuleLabel'     => 'Goods Receipt Note Edit',
    'barModuleShortcuts' => [
        ['key' => 'Alt+P', 'label' => 'Purchase Order', 'type' => 'info', 'permission' => 'purchase_order.create', 'url' => route('purchase-orders.create')],
        ['key' => 'Alt+I', 'label' => 'Purchase Invoice','type' => 'info', 'permission' => 'purchase_invoice.create', 'url' => route('purchase-invoices.create')],
        ['key' => 'Alt+L', 'label' => 'Ledger',         'type' => 'info', 'permission' => 'ledger_report.list', 'url' => route('ledger-report.index')],
        ['key' => 'Alt+V', 'label' => 'Debit Note',     'type' => 'info', 'permission' => 'debit_note.create'],
        ['key' => 'Alt+G', 'label' => 'Print GRN', 'type' => 'info', 'permission' => 'grn.print', 'click' => '#print_grn_btn'],
    ],
]) 
@endsection

@section('script')
    <script>
        const checkDuplicateReferenceFromGrn = "{{ route('grns.check_duplicate_reference') }}";
        const pendingPurchaseOrders = "{{ route('grns.pending_purchase_orders') }}";
        const getGrnUrl = "{{ route('grns.show', ':id') }}";
        const updateGrnUrl = "{{ route('grns.update', ':id') }}";
        const grnPrintRecieptUrl = "{{ route('grns.grnPrint', ':id') }}";

    </script>
    <script src="{{ asset('js/modules/grn/edit.js') }}?v={{ hash_file('md5', public_path('js/modules/grn/edit.js')) }}"></script>
    <script src="{{ asset('js/modules/sales-purchase-common.js') }}?v={{ hash_file('md5', public_path('js/modules/sales-purchase-common.js')) }}"></script>
    <script src="{{ asset('js/modules/grn/create.js') }}?v={{ hash_file('md5', public_path('js/modules/grn/create.js')) }}"></script>
    <script src="{{ asset('js/modules/grn/check-duplicate-ref.js') }}?v={{ hash_file('md5', public_path('js/modules/grn/check-duplicate-ref.js')) }}"></script>
    <script src="{{ asset('js/modules/grn/po-modal.js') }}?v={{ hash_file('md5', public_path('js/modules/grn/po-modal.js')) }}"></script>
@endsection
