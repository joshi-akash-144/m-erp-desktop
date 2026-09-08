@extends('company.layout.app')
@section('title', 'Purchase Order – Create')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}"></link>
    <style>
    .m-erp-po-row-1 {
        grid-template-columns: 150px 140px 150px 140px 1fr 1fr;
    }

    .m-erp-po-row-2 {
        grid-template-columns: 0.8fr 0.6fr 170px 0.7fr 1fr;
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
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-cart-shopping"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Purchase Order
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
                        @can('purchase_order.update')
                            <a href="{{ route('purchase-orders.edit') }}" class="btn btn-sm btn-outline-primary waves-effect rounded-0 fw-medium shadow-sm"
                                data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit Order">
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


    <!-- ✅ Page Body -->
    <div class="page-body">
        <div class="container-xl">
            <div class="card p-2 shadow-sm">
                @include('company.pages.purchase-order._form', ['formMode' => 'create'])
            </div>
        </div>
    </div>

@include('company.partials._shortcuts-bar', [
    'barModuleLabel'     => 'Purchase Order Create',
    'barModuleShortcuts' => [
        ['key' => 'Alt+O', 'label' => 'Purchase Order List', 'type' => 'info', 'permission' => 'purchase_order.list', 'url' => route('purchase-orders.index')],
        ['key' => 'Alt+G', 'label' => 'GRN',             'type' => 'info', 'permission' => 'grn.create', 'url' => route('grns.create')],
        ['key' => 'Alt+R', 'label' => 'GRN List',        'type' => 'info', 'permission' => 'grn.list',   'url' => route('grns.index')],
        ['key' => 'Alt+P', 'label' => 'Purchase Invoice', 'type' => 'info', 'permission' => 'purchase_invoice.create', 'url' => route('purchase-invoices.create')],
        ['key' => 'Alt+L', 'label' => 'Purchase Invoice List', 'type' => 'info', 'permission' => 'purchase_invoice.list', 'url' => route('purchase-invoices.index')],
    ],
]) 
    @endsection

    @section('script')
    <script>
        const checkContractUniqueUrl = "{{ route('purchase-orders.check_contract_unique') }}";
    </script>
    <script src="{{ asset('js/modules/sales-purchase-common.js') }}?v={{ hash_file('md5', public_path('js/modules/sales-purchase-common.js')) }}"></script>
    <script src="{{ asset('js/modules/purchase-order/create.js') }}?v={{ hash_file('md5', public_path('js/modules/purchase-order/create.js')) }}"></script>
    @endsection