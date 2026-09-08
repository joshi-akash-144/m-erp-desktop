@extends('company.layout.app')
@section('title', 'Sales Order – Create')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}"></link>
    <style>
        .m-erp-so-row-1 {
            grid-template-columns: 1fr 1fr 1fr 2.5fr 1fr 1fr;
        }

        .m-erp-so-row-2 {
            grid-template-columns: 1fr 1fr 1fr 1fr 1fr 1fr;
        }

        @media (max-width: 992px) {

            .m-erp-po-row-1,
            .m-erp-po-row-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    <div class="page-wrapper" style="max-width: 1700px">
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
                                Sales Order
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-orange text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-pen me-1"></i> EDIT ENTRY
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
                                    Sales Order
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
        </div> --}}
        <!-- ✅ Page Body -->
        <div class="page-body">
            <div class="container-xl">
                <div class="card p-2 shadow-sm">
                    @include('company.pages.sales-order._form', ['formMode' => 'edit'])
                </div>
            </div>
        </div>
    </div>
@include('company.partials._shortcuts-bar', [
    'barModuleLabel'     => 'Sales Order Edit',
    'barModuleShortcuts' => [
        ['key' => 'Alt+O', 'label' => 'Sales Order List',         'type' => 'info', 'permission' => 'sales_order.list', 'url' => route('sales-orders.index')],
        ['key' => 'Alt+A', 'label' => 'Sales Invoice',           'type' => 'info', 'permission' => 'sales_invoice.create', 'url' => route('sales-invoices.create')],
        ['key' => 'Alt+L', 'label' => 'Sales Invoice List', 'type' => 'info', 'permission' => 'sales_invoice.list', 'url' => route('sales-invoices.index')],
    ],
])
@endsection

@section('script')
    <script>
        const getSalesOrderUrl = "{{ route('sales-orders.show', ':id') }}";
        const updateSalesOrderUrl = "{{ route('sales-orders.update', ':id') }}";
    </script>
    <script src="{{ asset('js/modules/sales-purchase-common.js') }}?v={{ hash_file('md5', public_path('js/modules/sales-purchase-common.js')) }}"></script>
    <script src="{{ asset('js/modules/sales-order/edit.js') }}?v={{ hash_file('md5', public_path('js/modules/sales-order/edit.js')) }}"></script>    
    <script src="{{ asset('js/modules/sales-order/create.js') }}?v={{ hash_file('md5', public_path('js/modules/sales-order/create.js')) }}"></script>
@endsection
