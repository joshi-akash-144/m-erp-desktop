@extends('company.layout.app')
@section('title', 'Dairy File Import Register')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <style>
        /* Modal Tabulator height */
        #modal_detail_table .tabulator { font-size: .82rem; }
    </style>
@endsection

@section('content')
<div class="page-wrapper">

    {{-- Page Header --}}
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                            style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-table-list"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Dairy File Import Register
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-3 py-1"
                                style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-list me-1"></i> Register
                            </span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('back.to.previous') }}"
                            class="btn btn-sm btn-outline-dark back-btn waves-effect rounded-0 fw-medium shadow-sm">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Page Body --}}
    <div class="page-body">
        <div class="container-xl">
            <div class="card">

                {{-- Filters --}}
                <form id="filter_form g-2">
                    <div class="p-3">
                        <div class="row">

                            <div class="col-md-2 col-lg-2">
                                <label class="filter-label fw-bold">Import Date</label>
                                <input type="text" id="import_date" class="form-control form-control-sm" placeholder="DD-MM-YYYY">
                            </div>

                            <div class="col-md-3 col-lg-3">
                                <label class="filter-label fw-bold">Product</label>
                                <select id="product_id" class="form-select form-select-sm select2" data-placeholder="All Products">
                                    <option value="">All Products</option>
                                    @foreach ($items as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2 col-lg-2">
                                <label class="filter-label fw-bold">Status</label>
                                <select id="is_used" class="form-select form-select-sm select2" data-placeholder="All Status">
                                    <option value="">All</option>
                                    <option value="0" selected>Not Used</option>
                                    <option value="1">Used</option>
                                </select>
                            </div>

                            <div class="col-md-4 col-lg-3 d-flex align-items-end gap-2">
                                <button type="button" id="filter_apply" class="btn btn-primary waves-effect">
                                    @include('icons.filter', ['size' => 16])
                                    Apply
                                </button>
                                <button type="button" id="filter_clear" class="btn btn-outline-secondary waves-effect">
                                    @include('icons.filter-clear', ['size' => 16])
                                    Clear
                                </button>
                                <button type="button" id="bulk_delete_btn" class="btn btn-outline-danger waves-effect">
                                    <i class="fa-solid fa-trash-can me-1"></i> Delete All Filtered
                                </button>
                            </div>

                        </div>
                    </div>
                </form>

                {{-- Main Tabulator --}}
                <div class="card-body p-0">
                    <div id="dairy_file_register_table"></div>
                </div>

            </div>
        </div>
    </div>

</div>

{{-- ── Detail Modal ──────────────────────────────────────────────────────── --}}
<div class="modal fade" id="importDetailModal" tabindex="-1" aria-labelledby="importDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header py-2 bg-primary text-white">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="importDetailModalLabel">
                        <i class="fa-solid fa-table-list me-2"></i>Import Details
                    </h5>
                    <small id="modal_subtitle" class="opacity-75"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-0">
                {{-- Loading state --}}
                <div id="modal_loading" class="text-center py-5 text-muted">
                    <i class="fa-solid fa-spinner fa-spin fa-2x mb-2 d-block"></i>
                    Loading...
                </div>
                {{-- Tabulator in modal --}}
                <div id="modal_detail_table" style="display:none;"></div>
            </div>

            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> Close
                </button>
            </div>

        </div>
    </div>
</div>
@endsection

@section('script')
    <script>
        const dairyFileRegisterListUrl       = "{{ route('dairy-file-import.dairy-file.register.list') }}";
        const dairyFileImportItemsUrl        = "{{ route('dairy-file-import.dairy-file.import.items', ':id') }}";
        const dairyFileImportDestroyUrl      = "{{ route('dairy-file-import.dairy-file.import.destroy', ':id') }}";
        const dairyFileRegisterBulkDeleteUrl = "{{ route('dairy-file-import.dairy-file.register.bulk-delete') }}";
    </script>
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>    
    <script src="{{ asset('js/modules/dairy-file-import/dairy-file-register.js') }}?v={{ time() }}"></script>
@endsection
