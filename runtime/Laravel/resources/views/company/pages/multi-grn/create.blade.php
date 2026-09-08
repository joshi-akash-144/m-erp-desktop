@extends('company.layout.app')
@section('title', 'Multi GRN - Import')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}"></link>
    <link rel="stylesheet" href="{{ asset('css/module/dropzone.css') }}?v={{ hash_file('md5', public_path('css/module/dropzone.css')) }}"></link>
    <style>
        .m-erp-multi-grn-row-1 {
            grid-template-columns: 1fr 190px 190px 190px 1fr 190px;
        }

        .m-erp-multi-grn-row-2 {
            grid-template-columns: 1fr 190px 190px 190px 1fr 190px;
        }

        .m-erp-multi-grn-row-3 {
            grid-template-columns: 190px 0.6fr 190px 190px 190px 1fr;
        }

        @media (max-width: 992px) {

            .m-erp-multi-grn-row-1,
            .m-erp-multi-grn-row-2 {
                grid-template-columns: 1fr;
            }
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

.dropzone .dz-preview .dz-details .dz-filename {
    margin-top: 8px !important;
    font-weight: 600 !important;
}

.dropzone .dz-preview .dz-details .dz-filename span {
    /* background-color: transparent !important; Removes default background bleed */
    color: #554633 !important; /* Clear dark color for the file name */
    font-size: 0.9rem !important;
}

.dropzone .dz-preview .dz-details .dz-size {
    font-size: 0.85rem !important;
    color: #64748b !important;
    margin-bottom: 4px !important;
}

.dropzone .dz-preview .dz-progress {
    display: none !important; 
}

.dropzone.custom-excel-dropzone {
    border: 3px dashed #e2e8f0 !important;  
    border-radius: 12px !important;
    background: #ffffff !important;
    padding: 30px 20px;
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
                                Multi GRN
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
                                    Multi GRN
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
                    @include('company.pages.multi-grn._form', ['formMode' => 'create'])
                </div>
            </div>
        </div>
    </div>
@include('company.partials._shortcuts-bar', [
    'barModuleLabel'     => 'Multi GRN - Import',
    'barModuleShortcuts' => [
        ['key' => 'Alt+O', 'label' => 'Sales Order List',       'type' => 'info', 'permission' => 'sales_order.list', 'url' => route('sales-orders.index')],
        ['key' => 'Alt+A', 'label' => 'Sales Invoice',          'type' => 'info', 'permission' => 'sales_invoice.create', 'url' => route('sales-invoices.create')],
        ['key' => 'Alt+L', 'label' => 'Sales Invoice List',     'type' => 'info', 'permission' => 'sales_invoice.list', 'url' => route('sales-invoices.index')],
    ],
])
@endsection

@section('script')
    <script src="{{ asset('js/libs/dropzone.min.js') }}"></script>
    <script src="{{ asset('js/modules/multi-grn/create.js') }}?v={{ hash_file('md5', public_path('js/modules/multi-grn/create.js')) }}"></script>    

@endsection
