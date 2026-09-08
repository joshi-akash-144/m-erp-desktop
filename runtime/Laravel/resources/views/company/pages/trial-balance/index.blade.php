@extends('company.layout.app')
@section('title', 'Trial Balance')

@section('css')
<link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
<link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
@endsection

@section('content')
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
                                Trial Balance
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
                                    <button class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        @include('icons.upload', ['size' => 19])
                                        Export
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @can('trial_balance.print')
                                        <li>
                                            <a class="dropdown-item" href="#" id="tb_print_btn">
                                                @include('icons.print')
                                                Print
                                            </a>
                                        </li>
                                        @endcan
                                        @can('trial_balance.export')
                                        <li>
                                            <a class="dropdown-item" href="#" id="tb_excel_btn">
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
</div>
   {{--  <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center justify-content-between">

                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card border-0 border-start border-4 border-primary">
                        <div class="card-body shadow p-2">
                            <h3 class="page-title font-monospace">
                                <i class="fa-solid fa-scale-balanced me-2 text-primary"></i>
                                Trial Balance
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

            {{-- Filter Card --}}
            <div class="card mb-2">
                <form id="tb_filter_form" autocomplete="off">
                    <div class="p-3">
                        <div class="row g-2 align-items-end">

                            <div class="col-md-3 col-lg-2">
                                <label class="fs-4 fw-bold">Report Type</label>
                                <select id="tb_report_type" class="form-select">
                                    <option value="group_wise">Group Wise Trial Balance</option>
                                    <option value="alphabetic">Alphabetic Trial Balance</option>
                                </select>
                            </div>

                            <div class="col-md-3 col-lg-2">
                                <label class="fs-4 fw-bold">View Type</label>
                                <select id="tb_view_type" class="form-select">
                                    <option value="balance_only">Balance Only</option>
                                    <option value="detail">Detail</option>
                                </select>
                            </div>

                            <div class="col-md-3 col-lg-2" id="tb_parent_group_wrap" style="display:none;">
                                <label class="fs-4 fw-bold">Parent Group</label>
                                <select id="tb_parent_group" class="form-select">
                                    <option value="no">No</option>
                                    <option value="yes">Yes</option>
                                </select>
                            </div>

                            <div class="col-md-2 col-lg-1" id="tb_single_date_group">
                                <label class="fs-4 fw-bold" id="tb_single_date_label">End Of Date</label>
                                <input type="text" id="tb_as_on_date" class="form-control" placeholder="DD-MM-YYYY">
                            </div>

                            <div class="col-md-2 col-lg-1" id="tb_range_date_group" style="display:none;">
                                <label class="fs-4 fw-bold">From Date</label>
                                <input type="text" id="tb_from_date" class="form-control" placeholder="DD-MM-YYYY">
                            </div>

                            <div class="col-md-2 col-lg-1" id="tb_range_date_group_to" style="display:none;">
                                <label class="fs-4 fw-bold">To Date</label>
                                <input type="text" id="tb_to_date" class="form-control" placeholder="DD-MM-YYYY">
                            </div>

                            <div class="col-lg-auto d-flex align-items-end gap-2">
                                <button id="tb_show_btn" type="button" class="btn btn-primary waves-effect">
                                    @include('icons.filter', ['size' => 18])
                                    Apply
                                </button>
                                <button id="tb_clear_btn" type="button" class="btn btn-outline-secondary waves-effect">
                                    @include('icons.filter-clear', ['size' => 18])
                                    Clear
                                </button>

                             {{--    @canany(['trial_balance.print', 'trial_balance.export'])
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle waves-effect"
                                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-file-export me-1"></i> Export
                                    </button>
                                    <ul class="dropdown-menu">
                                        @can('trial_balance.print')
                                        <li>
                                            <a class="dropdown-item" href="#" id="tb_print_btn">
                                                @include('icons.print')
                                                Print
                                            </a>
                                        </li>
                                        @endcan
                                        @can('trial_balance.export')
                                        <li>
                                            <a class="dropdown-item" href="#" id="tb_excel_btn">
                                                @include('icons.xlsx')
                                                Excel
                                            </a>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                                @endcanany
                            </div>  --}}

                        </div>
                    </div>
                </form>
            </div>

            {{-- Toolbar: search + date badge --}}
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="input-group input-group-sm flex-grow-1">
                    <span class="input-group-text bg-white">
                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                    </span>
                    <input type="text" id="tb_search" class="form-control" placeholder="Search…">
                    <button class="btn btn-outline-secondary" id="tb_search_clear" type="button" style="display:none;">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="flex-shrink-0">
                    <span class="badge bg-blue-lt text-blue px-2 py-1" style="font-size:.8rem;">
                        <i class="fa-regular fa-calendar me-1"></i>
                        <span id="tb_date_badge_text"></span>
                    </span>
                </div>
            </div>

            {{-- Table Card --}}
            <div class="card sm-shadow rounded-0 bg-light">
                <div class="card-body">
                    <div id="trial_balance_table"></div>
                </div>
            </div>

            {{-- Totals Bar --}}
            <div id="tb_totals_bar" class="card mt-2" style="display:none;">
                <div class="card-body p-2">
                    <div class="row g-2">
                        <div class="col-auto ms-auto d-flex align-items-center gap-4">
                            <div class="text-end" id="tb_total_opening_wrap" style="display:none;">
                                <div class="text-muted small fw-semibold">Total Opening</div>
                                <div class="fw-bold fs-4 font-monospace text-primary" id="tb_total_opening">0.00</div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted small fw-semibold">Total Debit</div>
                                <div class="fw-bold fs-4 font-monospace text-primary" id="tb_total_debit">0.00</div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted small fw-semibold">Total Credit</div>
                                <div class="fw-bold fs-4 font-monospace text-primary" id="tb_total_credit">0.00</div>
                            </div>
                            <div class="text-end" id="tb_total_closing_wrap" style="display:none;">
                                <div class="text-muted small fw-semibold">Total Closing</div>
                                <div class="fw-bold fs-4 font-monospace text-primary" id="tb_total_closing">0.00</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Difference Bar --}}
            <div id="tb_diff_bar" class="card mt-2 border-warning" style="display:none;">
                <div class="card-body p-2">
                    <div class="d-flex align-items-center justify-content-end gap-3">
                        <span class="text-muted small fw-semibold">Difference</span>
                        <span class="fw-bold fs-5 font-monospace" id="tb_diff_amount">0.00</span>
                        <span class="fw-bold fs-5 font-monospace" id="tb_diff_label"></span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Group Wise Modal (Balance Only drill-down) --}}
    <div class="modal fade" id="group_wise_modal" tabindex="-1" aria-hidden="true" data-bs-keyboard="false">
        @include('company.pages.trial-balance.group-wise')
    </div>

    {{-- Group Wise Detail Modal (Detail drill-down) --}}
    <div class="modal fade" id="group_wise_detail_modal" tabindex="-1" aria-hidden="true" data-bs-keyboard="false">
        @include('company.pages.trial-balance.group-wise-detail')
    </div>

    {{-- Month Wise Summary Modal --}}
    <div class="modal fade" id="month_wise_summary_modal" tabindex="-1" aria-hidden="true" data-bs-keyboard="false">
        @include('company.pages.trial-balance.month-wise-summary')
    </div>

    {{-- Account Ledger Detail Modal --}}
    <div class="modal fade" id="ledger_detail_modal" tabindex="-1" aria-hidden="true" data-bs-keyboard="false">
        @include('company.pages.trial-balance.ledger-detail')
    </div>

</div>
@endsection

@section('script')
<script>
    const tbIndexUrl            = "{{ route('trial-balance.index') }}";
    const tbPrintUrl            = "{{ route('trial-balance.print') }}";
    const tbExcelUrl            = "{{ route('trial-balance.export.excel') }}";
    const tbSelectGroupUrl      = "{{ route('trial-balance.select-group-row') }}";
    const tbGroupBalanceUrl     = "{{ route('trial-balance.group-wise-balance') }}";
    const tbGroupDetailUrl      = "{{ route('trial-balance.group-wise-detail') }}";
    const tbGroupDetailPrintUrl = "{{ route('trial-balance.group-wise-detail.print') }}";
    const tbGroupDetailExcelUrl = "{{ route('trial-balance.group-wise-detail.export.excel') }}";
    const tbGroupPrintUrl       = "{{ route('trial-balance.group-wise-balance.print') }}";
    const tbGroupExcelUrl       = "{{ route('trial-balance.group-wise-balance.export.excel') }}";
    const tbAccountMonthWiseUrl    = "{{ route('trial-balance.account-month-wise-summary') }}";
    const tbAccountMonthWisePrint  = "{{ route('trial-balance.account-month-wise.print') }}";
    const tbAccountMonthWiseExcel  = "{{ route('trial-balance.account-month-wise.export.excel') }}";
    const tbAccountLedgerUrl       = "{{ route('trial-balance.account-ledger') }}";
    const tbAccountLedgerPrint     = "{{ route('trial-balance.account-ledger.print') }}";
    const tbAccountLedgerExcel     = "{{ route('trial-balance.account-ledger.export.excel') }}";
</script>
<script src="{{ asset('js/modules/trial-balance/index.js') }}?v={{ file_exists(public_path('js/modules/trial-balance/index.js')) ? hash_file('md5', public_path('js/modules/trial-balance/index.js')) : '1' }}"></script>
@endsection
