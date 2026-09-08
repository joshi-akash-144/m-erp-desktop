@extends('company.layout.app')
@section('title', 'Opening Trial Balance')

@section('css')
<link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
<link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
@endsection

@section('content')
<div class="row col-lg-10">

    <div class="page-wrapper">
    
        {{-- Page Header --}}
<div class="page-header d-print-none">
    <div class="container-xl">  
    <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fa-solid fa-scale-balanced"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Opening Trial Balance
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-scale-unbalanced me-1"></i> Balance
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                                    @canany(['trial_balance.print', 'trial_balance.export'])
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-secondary dropdown-toggle waves-effect"
                                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            @include('icons.upload', ['size' => 19])
                                            Export
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @can('trial_balance.print')
                                           <li>
                                                <a class="dropdown-item" href="#" id="otb_print_btn">
                                                 @include('icons.print')
                                                    Print
                                                </a>
                                            </li>
                                            @endcan
                                            @can('trial_balance.export')
                                            <li>
                                                <a class="dropdown-item" href="#" id="otb_excel_btn">
                                                    @include('icons.xlsx')
                                                    Excel
                                                </a>
                                            </li>
                                            @endcan
                                        </ul>
                                    </div>
                                    @endcanany
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
    
                    <div class="col-12 col-sm-6 col-md-4 col-lg-4">
                        <div class="card border-0 border-start border-4 border-primary">
                            <div class="card-body shadow p-2">
                                <h3 class="page-title font-monospace">
                                    <i class="fa-solid fa-scale-balanced me-2 text-primary"></i>
                                    Opening Trial Balance
                                </h3>
                                <span class="ribbon ribbon-bookmark bg-indigo">
                                    <i class="fa-solid fa-chart-bar me-1"></i> REPORT
                                </span>
                            </div>
                        </div>
                    </div>
    
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                        <div class="card border-0 border-end border-4 border-primary">
                            <div class="card-body shadow-sm p-2">
                                <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
    
                                    @canany(['trial_balance.print', 'trial_balance.export'])
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-secondary dropdown-toggle waves-effect"
                                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-solid fa-file-export me-1"></i> Export
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @can('trial_balance.print')
                                            <li>
                                                <a class="dropdown-item" href="#" id="otb_print_btn">
                                                    @include('icons.print')
                                                    Print
                                                </a>
                                            </li>
                                            @endcan
                                            @can('trial_balance.export')
                                            <li>
                                                <a class="dropdown-item" href="#" id="otb_excel_btn">
                                                    @include('icons.xlsx')
                                                    Excel
                                                </a>
                                            </li>
                                            @endcan
                                        </ul>
                                    </div>
                                    @endcanany
    
                                    <a href="{{ route('back.to.previous') }}"
                                        class="btn btn-sm btn-outline-dark back-btn waves-effect">
                                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                                    </a>
    
                                </div>
                            </div>
                        </div>
                    </div>
    
                </div>
            </div>
        </div> --}}
    
        {{-- Page Body --}}
        <div class="page-body">
            <div class="container-xl">
    
                {{-- Toolbar: toggle + search + date --}}
                <div class="d-flex align-items-center gap-2 mb-2">

                    {{-- View mode toggle --}}
                    <div class="d-flex gap-1 flex-shrink-0">
                        <button id="otb_filter_group" class="btn btn-sm btn-primary waves-effect">
                            <i class="fa-solid fa-layer-group me-1"></i> Group Wise
                        </button>
                        <button id="otb_filter_account" class="btn btn-sm btn-outline-primary waves-effect">
                            <i class="fa-solid fa-list me-1"></i> Account Wise
                        </button>
                    </div>

                    {{-- Search --}}
                    <div class="input-group input-group-sm flex-grow-1">
                        <span class="input-group-text bg-white">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </span>
                        <input type="text" id="otb_search" class="form-control"
                            placeholder="Search…">
                        <button class="btn btn-outline-secondary" id="otb_search_clear" type="button" style="display:none;">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    {{-- As on date --}}
                    <div class="flex-shrink-0 text-end">
                        <span class="badge bg-blue-lt text-blue px-2 py-1" style="font-size:.8rem;">
                            <i class="fa-regular fa-calendar me-1"></i>
                            As on: {{ format_date(financial_year_start()) }}
                        </span>
                    </div>

                </div>
    
                {{-- Table Card --}}
                <div class="card sm-shadow rounded-0 bg-light">
                    <div class="card-body">
                        <div id="opening_trial_balance_table"></div>
                    </div>
                </div>
    
                {{-- Difference Bar --}}
                <div id="otb_diff_bar" class="card mt-2 border-warning" style="display:none;">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center justify-content-end gap-3">
                            <span class="text-muted small fw-semibold">Difference</span>
                            <span class="fw-bold fs-5 font-monospace" id="otb_diff_amount">0.00</span>
                            <span class="fw-bold fs-5 font-monospace" id="otb_diff_label"></span>
                        </div>
                    </div>
                </div>
    
            </div>
        </div>
    
        {{-- Group Wise Modal --}}
        <div class="modal fade" id="group_wise_modal" tabindex="-1" aria-hidden="true">
            @include('company.pages.trial-balance.group-wise')
        </div>
    
    </div>
</div>
@endsection

@section('script')
<script>
    const otbIndexUrl       = "{{ route('trial-balance.opening-list') }}";
    const otbPrintUrl       = "{{ route('trial-balance.opening-list.print') }}";
    const otbExcelUrl       = "{{ route('trial-balance.opening-list.export.excel') }}";
    const otbSelectGroupUrl = "{{ route('trial-balance.select-group-row') }}";
    const otbGroupWiseUrl   = "{{ route('trial-balance.group-wise-list') }}";
    const otbGroupPrintUrl  = "{{ route('trial-balance.group-wise.print') }}";
    const otbGroupExcelUrl  = "{{ route('trial-balance.group-wise.export.excel') }}";
</script>
<script src="{{ asset('js/modules/trial-balance/opening-list.js') }}?v={{ file_exists(public_path('js/modules/trial-balance/opening-list.js')) ? hash_file('md5', public_path('js/modules/trial-balance/opening-list.js')) : '1' }}"></script>
@endsection
