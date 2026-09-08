@extends('company.layout.app')
@section('title', 'Profit & Loss Report')

@section('css')
<link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
<link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
<style>
    /* ═══════════════════════════════════════════════════════
   Profit & Loss — Tabulator-based Report UI
   ═══════════════════════════════════════════════════════ */

    /* ── Layout ──────────────────────────────────────────── */
    .pl-section-wrap {
        border: 1px solid #d1d9e6;
        border-radius: 6px;
        overflow: clip;
        margin-bottom: 16px;
        box-shadow: 0 2px 8px rgba(30, 40, 80, .07);
    }

    /* ── Debit / Credit column header row ───────────────── */
    .pl-col-header-row {
        display: flex;
        background: #e6f0fb;
        border-bottom: 2px solid #1a3a6b;
    }

    .pl-col-hdr {
        flex: 1;
        text-align: center;
        font-weight: 700;
        font-size: 11px;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: #1a3a6b;
        padding: 5px 10px;
    }

    .pl-col-hdr-dr {
        border-right: 2px solid #1a3a6b;
    }

    /* ── Date badge row ───────────────────────────────── */
    .pl-date-badge-row {
        display: flex;
        background: #f8fafc;
        border-bottom: 1px solid #d1d9e6;
    }

    .pl-half-badge {
        flex: 1;
        font-size: 10.5px;
        color: #6b7280;
        font-style: italic;
        text-align: right;
        padding: 3px 10px;
    }

    .pl-half-badge:first-child {
        border-right: 2px solid #1a3a6b;
    }


    /* ── Section / panel header bar ─────────────────────── */
    .pl-section-header {
        background: linear-gradient(135deg, #1a3a6b 0%, #2563eb 100%);
        color: #fff;
        text-align: center;
        padding: 8px 14px;
        font-weight: 700;
        font-size: 11.5px;
        letter-spacing: 1.8px;
        text-transform: uppercase;
        border-bottom: 2px solid #1a3a6b;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }

    .pl-section-date {
        font-size: 10px;
        font-weight: 400;
        letter-spacing: 0.4px;
        text-transform: none;
        opacity: .80;
        font-style: italic;
    }

    /* ── Tabulator column group headers (Debit / Credit) ── */
    .pl-table-wrap .tabulator-col.tabulator-col-group {
        background: #e6f0fb !important;
        border-bottom: 2px solid #1a3a6b !important;
    }

    .pl-table-wrap .tabulator-col.tabulator-col-group .tabulator-col-content {
        justify-content: center;
    }

    .pl-table-wrap .tabulator-col.tabulator-col-group .tabulator-col-title {
        font-weight: 700 !important;
        font-size: 11px !important;
        letter-spacing: 2px !important;
        text-transform: uppercase !important;
        color: #1a3a6b !important;
    }

    /* Separator group header — same dark blue as the column */
    .pl-table-wrap .tabulator-col.tabulator-col-group.pl-sep-grp,
    .pl-table-wrap .tabulator-col.pl-sep-col {
        background: #1a3a6b !important;
        border: none !important;
        padding: 0 !important;
        min-width: 3px !important;
        width: 3px !important;
    }

    /* ── Tabulator overrides ─────────────────────────────── */
    .pl-table-wrap {
        min-width: 0;
    }

    /* Remove inner scroll — table grows to full content height */
    .pl-table-wrap .tabulator {
        height: auto !important;
    }

    .pl-table-wrap .tabulator-tableholder {
        height: auto !important;
        max-height: none !important;
        overflow: visible !important;
    }

    /* Amount column monospace */
    .tabulator .pl-amt-cell {
        font-family: 'Courier New', Courier, monospace;
        text-align: right;
    }


    /* Suppress header sort icons */
    .pl-table-wrap .tabulator-header .tabulator-col.tabulator-sortable .tabulator-col-content::after {
        display: none;
    }

    .pl-table-wrap .tabulator-header {
        cursor: default;
        user-select: none;
    }


    /* Summary cards below report */
    .pl-summary-strip {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        padding: 10px 16px;
        background: #f8fafc;
        border: 1px solid #d1d9e6;
        border-top: 3px solid #1a3a6b;
        border-radius: 6px;
        margin-bottom: 12px;
        box-shadow: 0 2px 6px rgba(30, 40, 80, .05);
    }

    .pl-sum-item {
        display: flex;
        flex-direction: column;
    }

    .pl-sum-label {
        font-size: 10px;
        color: #6b7280;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .pl-sum-value {
        font-family: 'Courier New', Courier, monospace;
        font-size: 15px;
        font-weight: 700;
        color: #1a3a6b;
    }

    .pl-sum-value.profit {
        color: #059669;
    }

    .pl-sum-value.loss {
        color: #dc2626;
    }

    /* Responsive */
    @media (max-width: 767px) {
        .pl-section-wrap {
            grid-template-columns: 1fr;
        }

        .pl-panel-dr {
            border-right: none;
            border-bottom: 2px solid #1a3a6b;
        }
    }
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
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Profit &amp; Loss
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-chart-line me-1"></i> Report
                            </span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        @canany(['profit_loss.print', 'profit_loss.export'])
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle waves-effect"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                @include('icons.upload', ['size' => 19])
                                Export
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @can('profit_loss.print')
                                <li>
                                    <a class="dropdown-item" href="#" id="pl_print_btn">
                                        @include('icons.print')
                                        Print
                                    </a>
                                </li>
                                @endcan
                                @can('profit_loss.export')
                                <li>
                                    <a class="dropdown-item" href="#" id="pl_excel_btn">
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

    {{-- Page Body --}}
    <div class="page-body">
        <div class="container-xl">

            {{-- Filter Card --}}
            <div class="card mb-2">
                <form id="pl_filter_form" autocomplete="off">
                    <div class="p-3">
                        <div class="row g-2 align-items-end">

                            <div class="col-md-3 col-lg-2">
                                <label class="fs-4 fw-bold">View Type</label>
                                <select id="pl_view_type" class="form-select">
                                    <option value="group_wise">Group Wise</option>
                                    <option value="account_wise">Account Wise</option>
                                </select>
                            </div>

                            <div class="col-md-2 col-lg-2">
                                <label class="fs-4 fw-bold">From Date</label>
                                <input type="text" id="pl_from_date" class="form-control" placeholder="DD-MM-YYYY">
                            </div>

                            <div class="col-md-2 col-lg-2">
                                <label class="fs-4 fw-bold">To Date</label>
                                <input type="text" id="pl_to_date" class="form-control" placeholder="DD-MM-YYYY">
                            </div>

                            <div class="col-lg-auto d-flex align-items-end gap-2">
                                <button id="pl_show_btn" type="button" class="btn btn-primary waves-effect">
                                    @include('icons.filter', ['size' => 18])
                                    Apply
                                </button>
                                <button id="pl_clear_btn" type="button" class="btn btn-outline-secondary waves-effect">
                                    @include('icons.filter-clear', ['size' => 18])
                                    Clear
                                </button>
                            </div>

                        </div>
                    </div>
                </form>
            </div>

            {{-- Date Badge --}}
            <div class="d-flex align-items-center justify-content-end mb-2">
                <span class="badge bg-blue-lt text-blue px-2 py-1" id="pl_date_badge" style="font-size:.8rem;display:none;">
                    <i class="fa-regular fa-calendar me-1"></i>
                    <span id="pl_date_badge_text"></span>
                </span>
            </div>

            {{-- Report Container --}}
            <div id="pl_report_container">
                <div id="pl_empty_state" class="text-center text-muted py-5">
                    <i class="fa-solid fa-chart-line fa-3x mb-3 opacity-25"></i>
                    <p class="mb-0">Select filters and click <strong>Apply</strong> to view the Trading Account &amp; Profit &amp; Loss Statement</p>
                </div>
            </div>

        </div>
    </div>

</div>

    {{-- Group Wise Modal (Balance Only drill-down) --}}
    <div class="modal fade" id="group_wise_modal" tabindex="-1" aria-hidden="true" data-bs-keyboard="false">
        @include('company.pages.trial-balance.group-wise')
    </div>

    {{-- Account Ledger Detail Modal --}}
    <div class="modal fade" id="ledger_detail_modal" tabindex="-1" aria-hidden="true" data-bs-keyboard="false">
        @include('company.pages.trial-balance.ledger-detail')
    </div>

@endsection

@section('script')
<script>
    const plListUrl = "{{ route('profit-loss.list') }}";
    const plPrintUrl = "{{ route('profit-loss.print') }}";
    const plExcelUrl = "{{ route('profit-loss.export.excel') }}";
    const tbSelectGroupUrl = "{{ route('trial-balance.select-group-row') }}";
    const tbGroupBalanceUrl = "{{ route('trial-balance.group-wise-balance') }}";
    const tbGroupPrintUrl = "{{ route('trial-balance.group-wise-balance.print') }}";
    const tbGroupExcelUrl = "{{ route('trial-balance.group-wise-balance.export.excel') }}";
    const tbAccountLedgerUrl = "{{ route('trial-balance.account-ledger') }}";
    const tbAccountLedgerPrint = "{{ route('trial-balance.account-ledger.print') }}";
    const tbAccountLedgerExcel = "{{ route('trial-balance.account-ledger.export.excel') }}";
</script>
<script src="{{ asset('js/modules/profit-loss/index.js') }}?v={{ file_exists(public_path('js/modules/profit-loss/index.js')) ? hash_file('md5', public_path('js/modules/profit-loss/index.js')) : '1' }}"></script>
@endsection