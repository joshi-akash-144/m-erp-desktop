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
                                Freight Invoice
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
                        @can('freight_invoice.reorder')
                        <button type="button" class="btn btn-sm btn-primary waves-effect" data-bs-toggle="modal" data-bs-target="#itemOrderModal">
                            <i class="fa-solid fa-sort me-1"></i> Item Order Config
                        </button>
                        @endcan
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
                    @include('company.pages.freight-invoice._form',['formMode' => 'create'])
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ Item Order Config Modal -->
    <div class="modal modal-blur fade" id="itemOrderModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fa-solid fa-sort me-2"></i> Freight Invoice Item Order</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <p class="text-muted mb-2">Drag and drop items below to change their sequence. Unconfigured items appear at the end.</p>
                        <div class="input-icon">
                            <input type="text" id="search-item-input" class="form-control" placeholder="Search item...">
                            <span class="input-icon-addon">
                                <i class="fa-solid fa-search"></i>
                            </span>
                        </div>
                    </div>
                    <div class="list-group" id="reorder-items-list" style="max-height: 50vh; overflow-y: auto; overflow-x: hidden; border: 1px solid #dee2e6; border-radius: 4px;">
                        @foreach($items as $item)
                            <div class="list-group-item px-2 py-2 d-flex align-items-center cursor-move" data-id="{{ is_array($item) ? $item['id'] : $item->id }}">
                                <i class="fa-solid fa-grip-vertical text-muted me-3"></i>
                                <div class="flex-grow-1">
                                    <strong>{{ is_array($item) ? $item['name'] : $item->name }}</strong>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-danger" id="btn-reset-order">
                        <i class="fa-solid fa-rotate-left me-1"></i> Reset
                    </button>
                    <div>
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="btn-save-order">
                            <i class="fa-solid fa-save me-1"></i> Save
                        </button>
                    </div>
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
        const itemMasterData = @json($items);
        const zoneMasterData = @json($zones);
        const getDairyFileDataUrl     = "{{ route('freight-invoice.get-dairy-file-data') }}";
        const getImportItemsDataUrl   = "{{ route('freight-invoice.get-import-items-data') }}";
        const storeFreightInvoiceUrl  = "{{ route('freight-invoice.store') }}";
        const saveOrderUrl = "{{ route('freight-invoice.item-order.save') }}";
        const resetOrderUrl = "{{ route('freight-invoice.item-order.reset') }}";

    </script>
    <script src="{{ asset('js/modules/freight-invoice/create.js') }}?v={{ hash_file('md5', public_path('js/modules/freight-invoice/create.js')) }}"></script>
@endsection
