@extends('company.layout.app')
@section('title', 'Freight Invoice – Create')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}"></link>
<style>
    /* ── Inter font across the whole page ── */
    #freight_invoice_form,
    #dairyFileModal .modal-content {
        font-family: var(--tblr-font-sans-serif);
    }

    #freight_invoice_form label,
    #freight_invoice_form th,
    #freight_invoice_form td,
    #freight_invoice_form .btn,
    #freight_invoice_form .form-label,
    #dairyFileModal th,
    #dairyFileModal td,
    #dairyFileModal label,
    #dairyFileModal .btn,
    #dairyFileModal .modal-title,
    #dairyFileModal small {
        font-family: var(--tblr-font-sans-serif) !important;
    }

    /* ── Grid layouts ── */
    .m-erp-po-row-1 {
        grid-template-columns: 170px 140px 0.8fr 190px 140px 190px 0.3fr;
    }

    .m-erp-po-row-2 {
        grid-template-columns: 170px 140px max-content 1fr;
    }

    @media (max-width: 992px) {
        .m-erp-po-row-1,
        .m-erp-po-row-2 {
            grid-template-columns: 1fr;
        }
    }

    table tfoot {
        padding: 36px 5px !important;
        border: #dee2e6 solid 1px !important;
        background-color: #e6f0fb !important;
    }
    .cursor-move { cursor: grab; }
    .cursor-move:active { cursor: grabbing; }
    .sortable-ghost { opacity: 0.4; background-color: #f8f9fa; }
    .highlight-row { background-color: #e3f2fd !important; transition: background-color 0.5s ease; }

    /* Picker Modal CSS */
    #picker_select { display: none !important; }
    #pickerModal { overflow: visible !important; }
    #pickerModal .modal-dialog,
    #pickerModal .modal-content { overflow: visible; }
    #pickerModal .modal-content { min-height: 400px; }
    .select2-container--open { z-index: 9999 !important; }
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
                                Freight Invoice2
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

        <!-- ✅ Page Body -->
        <div class="page-body">
            <div class="container-xl">
                <div class="card p-2 shadow-sm">
                    @include('company.pages.freight-invoice2._form',['formMode' => 'create'])
                </div>
            </div>
        </div>
    </div>

{{-- Picker Modal (shared for Destination, Vehicle, Contractor) --}}
<div class="modal fade" id="pickerModal" tabindex="-1" aria-hidden="true" data-bs-keyboard="false" data-bs-focus="false">
    <div class="modal-dialog" style="max-width: 420px;">
        <div class="modal-content">
            <div class="modal-header py-2 px-3">
                <h6 class="modal-title fw-bold" id="picker_title">Select</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-3 py-2">
                <div id="picker_loader" class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <div class="small text-muted mt-1">Loading…</div>
                </div>
                <select id="picker_select" style="width: 100%;"></select>
            </div>
        </div>
    </div>
</div>

@include('company.partials._shortcuts-bar', [
    'barModuleLabel'     => 'Freight Invoice Create',
    'barModuleShortcuts' => [
        ['key' => 'Alt+L', 'label' => 'Freight Invoice List',         'type' => 'info', 'permission' => 'freight_invoice.list', 'url' => route('freight-invoice.index')],
    ],
])
@endsection

@section('script')
    <script>
        const storeFreightInvoice2Url = "{{ route('freight-invoice2.store') }}";
    </script>
    <script src="{{ asset('js/modules/freight-invoice2/create.js') }}?v={{ hash_file('md5', public_path('js/modules/freight-invoice2/create.js')) }}"></script>
@endsection 
