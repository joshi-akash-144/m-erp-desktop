@extends('company.layout.app')
@section('title', 'Delivery Challan Mobile – Create')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}"></link>
    <style>
        .m-erp-so-row-1 {
            grid-template-columns: repeat(6, 1fr);
        }

        .m-erp-so-row-2 {
            grid-template-columns: repeat(6, 1fr);
        }

        .m-erp-so-row-3 {
            grid-template-columns: repeat(4, 1fr);
        }

        .m-erp-so-row-4 {
            grid-template-columns: repeat(7, 1fr);
        }

        .m-erp-so-row-5 {
            grid-template-columns: repeat(3, 1fr);
        }

        /* Ensure Select2 fits container */
        .select2-container {
            width: 100% !important;
        }

        /* 🖥️ For X-Large Screens (1400px - 1600px) */
        @media (max-width: 1600px) {
            .m-erp-so-row-4 { grid-template-columns: repeat(4, 1fr); }
            .m-erp-so-row-1, .m-erp-so-row-2 { grid-template-columns: repeat(4, 1fr); }
        }

        /* 💻 For Large Screens (Desktop/Laptop) */
        @media (max-width: 1200px) {
            .m-erp-so-row-1, .m-erp-so-row-2 { grid-template-columns: repeat(3, 1fr); }
            .m-erp-so-row-4 { grid-template-columns: repeat(3, 1fr); }
            .m-erp-so-row-3 { grid-template-columns: repeat(2, 1fr); }
            .m-erp-so-row-5 { grid-template-columns: repeat(2, 1fr); }
        }

        /* 📱 For Medium Screens (Tablets) */
        @media (max-width: 992px) {
            .m-erp-so-row-1, .m-erp-so-row-2, .m-erp-so-row-3, .m-erp-so-row-4, .m-erp-so-row-5 {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        /* .page */
        }

        /* 📲 For Small Screens (Phones) */ 
        @media (max-width: 576px) {
            .m-erp-so-row-1, .m-erp-so-row-2, .m-erp-so-row-3, .m-erp-so-row-4, .m-erp-so-row-5 {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
@endsection

@section('content')
    <div class="page-wrapper" style="max-width: 1700px">
        <!-- ✅ Page Header -->
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center justify-content-between">

                    <!-- 🔹 Title & Subtitle -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="card border-0 border-start border-4 border-sales">
                            <div class="card-body shadow p-2">
                                <h3 class="page-title font-monospace text-sales">
                                    <i class="fa-solid fa-cart-shopping me-2"></i>
                                    Delivery Challan Mobile
                                </h3>
                                <span class="ribbon ribbon-bookmark bg-lime">
                                    <i class="fa-solid fa-plus me-1"></i> NEW ENTRY
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                        <div class="card border-0 border-end border-4 border-sales">
                            <div class="card-body shadow-sm p-2">
                                <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                    <a href="#" class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit Order">
                                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                    </a>
                                    <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
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
                    @include('company.pages.delivery-challan-mobile._form', ['formMode' => 'create'])
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
