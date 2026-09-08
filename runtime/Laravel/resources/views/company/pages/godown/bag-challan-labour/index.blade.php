@extends('company.layout.app')
@section('title', 'Bag Challan Labour - List')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <style>
        .m-erp-so-row-1 {
            grid-template-columns: 190px 190px auto;
        }

        @media (max-width: 992px) {
            .m-erp-so-row-1 {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    <div class="page-wrapper">

        {{-- ✅ Page Header --}}
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                    <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-godown rounded-0">

                        {{-- 🔹 Title Section --}}
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-godown-lt text-godown rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                                <i class="fs-3 fa-solid fa-bag-shopping"></i>
                            </div>
                            <div>
                                <h2 class="page-title text-godown fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                    Bag Challan Labour
                                </h2>
                            </div>
                            <div class="ms-md-3">
                                <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                    <i class="fa-solid fa-list"></i> list
                                </span>
                            </div>
                        </div>

                        {{-- 🔹 Action Buttons --}}
                        <div class="d-flex align-items-center gap-2">
                            @can('bag_challan_labour.create')
                                <a href="{{ route('bag-challan-labour.create') }}"
                                   class="btn btn-sm btn-outline-primary waves-effect rounded-0 fw-medium shadow-sm">
                                    <i class="fa-solid fa-plus me-1"></i> New Entry
                                </a>
                            @endcan
                            <a href="{{ route('back.to.previous') }}"
                               class="btn btn-sm btn-outline-dark back-btn waves-effect rounded-0 fw-medium shadow-sm">
                                <i class="fa-solid fa-arrow-left me-1"></i> Back
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ Page Body --}}
        <div class="page-body">
            <div class="container-xl">
                <div class="card">

                    {{-- 🔹 Filters --}}
                    <div class="card-body border-bottom p-3">
                        <div class="row g-2 m-erp-so-row-1">

                            {{-- Start Date --}}
                            <div class="col-md-4 col-lg-1">
                                <label for="start_date" class="fs-4 fw-bold">Start Date</label>
                                <input type="text" id="start_date" class="form-control" placeholder="DD-MM-YYYY">
                            </div>

                            {{-- End Date --}}
                            <div class="col-md-4 col-lg-1">
                                <label for="end_date" class="fs-4 fw-bold">End Date</label>
                                <input type="text" id="end_date" class="form-control" placeholder="DD-MM-YYYY">
                            </div>

                            {{-- Filter Buttons --}}
                            <div class="col-md-4 col-lg-auto d-flex align-items-end gap-2 text-nowrap">
                                <button id="filter_apply" class="btn btn-primary flex-fill waves-effect">
                                    @include('icons.filter', ['size' => 20])
                                    Apply
                                </button>
                                <button id="filter_clear" class="btn btn-outline-secondary flex-fill waves-effect">
                                    @include('icons.filter-clear', ['size' => 20])
                                    Clear
                                </button>
                            </div>

                        </div>
                    </div>

                    {{-- 🔹 List Table --}}
                    <div class="card-body p-0">
                        <div id="bag_challan_labour_list_table">
                            {{-- Tabulator will render here --}}
                        </div>
                    </div>

                    {{-- 🔹 Scroll Loader --}}
                    <div id="scrollLoader" class="card-footer text-center" style="display:none;">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span class="ms-2 text-muted">Loading more records...</span>
                    </div>

                </div>
            </div>
        </div>

    </div>
@endsection

@section('script')
    <script>
        const BagChallanLabourListUrl = "{{ route('bag-challan-labour.index') }}";
        const BagChallanLabourCreateUrl = "{{ route('bag-challan-labour.create') }}";
        const bagsChallanLabourPrintUrl = "{{ route('bag-challan-labour.print') }}";
    </script>
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
    <script src="{{ asset('js/modules/bag-challan-labour/index.js') }}?v={{ hash_file('md5', public_path('js/modules/bag-challan-labour/index.js')) }}"></script>
@endsection
