@extends('company.layout.app')

@section('title', 'Cheque Print Format Listing')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    </link>
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    </link>
@endsection

@section('content')
    <div class="page-wrapper" style="width: 1700px;">
        <!-- Page Header -->
<div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-money-check"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Cheque Print Format
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-list"></i>  report
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                        @can('cheque.create')
                            <a href="{{ route('cheque.create') }}"
                                class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
                                @include('icons.plus', ['size' => 16])
                                Add Cheque
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
  {{-- <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center justify-content-between">

                    <!-- 🔹 Title & Subtitle -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="card border-0 border-start border-4 border-primary">
                            <div class="card-body shadow p-2">
                                <h3 class="page-title font-monospace text-primary">
                                    <i class="fa-solid fa-money-check me-2"></i>
                                    Cheque Print Format
                                </h3>
                                <!-- <span class="ribbon ribbon-bookmark bg-lime">
                                    <i class="fa-solid fa-plus me-1"></i> NEW ENTRY
                                </span> -->
                            </div>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                        <div class="card border-0 border-end border-4 border-primary">
                            <div class="card-body shadow-sm p-2">
                                <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                    @can('cheque.create')
                                        <a href="{{ route('cheque.create') }}"
                                            class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
                                            @include('icons.plus', ['size' => 16])
                                            Add Cheque
                                        </a>
                                    @endcan
                                    <a href="{{ route('back.to.previous') }}"
                                        class="btn btn-sm btn-outline-dark back-btn">
                                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div> --}}
        {{-- <div class="container-xl ">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="position-relative"
                    style="height:60px;background:linear-gradient(120deg,#1a56db 0%,#4dabf7 60%,#74c0fc 100%);border-radius:12px 12px 0 0;">
                    <span
                        class="d-inline-flex align-items-center justify-content-center rounded-3 border border-4 border-white shadow position-absolute text-white"
                        style="background:linear-gradient(135deg,#1a56db,#4dabf7);width:76px;height:76px;bottom:-38px;left:24px;">
                        <i class="fa-solid fa-money-check" style="font-size:26px;"></i>
                    </span>
                </div>
                <div class="px-4 pb-3" style="padding-top:48px !important;">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <h3 class="mb-0 fw-bold">Cheque Print Format</h3>
                            <div class="text-muted small mt-1">Manage cheque print formats with their details</div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('company-selection.index') }}"
                                class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                                @include('icons.prev', ['size' => 15])
                                Dashboard
                            </a>
                            <a href="{{ route('cheque.create') }}"
                                class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                                @include('icons.plus', ['size' => 16])
                                Add Cheque
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}

        <!-- ✅ Page Body -->
        <div class="page-body">
            <form method="GET">
                <div class="container-xl">
                    <div class="card">

                        <!-- 🔹 Cheque Table -->
                        <div class="card-body p-0">
                            <div id="cheque_table">
                                <!-- Table content will be loaded dynamically via JS -->
                            </div>
                        </div>

                        <!-- 🔹 Infinite Scroll Loader -->
                        <div id="scrollLoader" class="card-footer text-center" style="display:none;">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <span class="ms-2 text-muted">Loading more records...</span>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script>
        const chequeListUrl = "{{ route('cheque.index') }}";
        const chequeEditUrl = "{{ route('cheque.edit', ':id') }}";
        const chequeDeleteUrl = "{{ route('cheque.destroy', ':id') }}";
    </script>
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
    <script src="{{ asset('js/cheque/index.js') }}?v={{ hash_file('md5', public_path('js/cheque/index.js')) }}"></script>
@endsection