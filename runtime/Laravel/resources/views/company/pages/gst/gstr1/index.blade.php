@extends('company.layout.app')
@section('title', 'GSTR-1 e-Return')
@section('css')
<link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
<link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
<style>
    .gstr1-section-row {
        cursor: pointer;
        transition: background 0.15s;
    }

    .gstr1-section-row:hover td {
        background: #e8f4fd !important;
    }

    .gstr1-drill-icon {
        opacity: 0.5;
        font-size: 0.75rem;
    }

    .gstr1-section-row:hover .gstr1-drill-icon {
        opacity: 1;
        color: #0d6efd;
    }

    /* Modal fullscreen */
    #gstr1DrillModal .modal-dialog {
        max-width: 100%;
        width: 100%;
        margin: 0;
    }

    #gstr1DrillModal .modal-content {
        border-radius: 0;
        min-height: 100vh;
    }

    #gstr1DrillModal .modal-body {
        padding: 0.75rem 1rem;
    }

    /* Tabulator overrides to match project font */
    .tabulator {
        font-family: inherit;
        font-size: 0.8125rem;
        border: 1px solid #dee2e6;
    }

    .tabulator .tabulator-header {
        background: #212529;
        color: #fff;
        border-bottom: 2px solid #dee2e6;
    }

    .tabulator .tabulator-col {
        background: #212529 !important;
        color: #fff;
        border-right: 1px solid #444;
    }

    .tabulator .tabulator-col-content {
        padding: 6px 8px;
    }

    .tabulator .tabulator-col.tabulator-sortable:hover {
        background: #343a40 !important;
    }

    .tabulator .tabulator-col .tabulator-col-sorter {
        color: #adb5bd;
    }

    .tabulator .tabulator-row {
        border-bottom: 1px solid #dee2e6;
    }

    .tabulator .tabulator-row:hover {
        background: #f0f7ff !important;
    }

    .tabulator .tabulator-row.tabulator-row-even {
        background: #fdfdfe;
    }

    .tabulator .tabulator-cell {
        padding: 5px 8px;
        border-right: 1px solid #e9ecef;
    }

    .tabulator .tabulator-footer {
        background: #f8f9fa;
        border-top: 2px solid #dee2e6;
    }

    .tabulator .tabulator-footer .tabulator-page {
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 2px 8px;
    }

    .tabulator .tabulator-footer .tabulator-page.active {
        background: #0d6efd;
        color: #fff;
        border-color: #0d6efd;
    }

    .tabulator-col-title {
        font-weight: 600;
        font-size: 0.75rem;
        letter-spacing: 0.3px;
    }

    .drill-badge {
        font-size: 0.65rem;
        padding: 2px 6px;
        vertical-align: middle;
    }

    .modal-section-title {
        font-size: 0.95rem;
        font-weight: 700;
        letter-spacing: 0.3px;
    }

    .modal-stat-pill {
        font-size: 0.75rem;
        background: #f0f0f0;
        border-radius: 20px;
        padding: 3px 10px;
    }

    .modal-stat-pill span {
        font-weight: 700;
        color: #0d6efd;
    }

    .tabulator .tabulator-loader {
        background-color: rgba(255, 255, 255, 0.9) !important;
        backdrop-filter: blur(2px);
    }

    .tabulator .tabulator-loader .tabulator-loader-msg {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
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
                            <i class="fa-solid fa-file-invoice-dollar"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                GSTR-1 Report
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-chart-line me-1"></i> Report
                            </span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
<button type="button" id="btn_export_offline_gstr1" class="btn btn-sm btn-outline-primary waves-effect d-none">
    <i class="fa-solid fa-file-excel me-1"></i> e-Return Export
</button>
                        <button type="button" id="btn_export_gstr1" class="btn btn-sm btn-outline-success waves-effect d-none">
                            <i class="fa-solid fa-file-excel me-1"></i> Export Excel
                        </button>
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

            {{-- Filters --}}
            <div class="card mb-3 d-print-none">
                <div class="card-body py-2">
                    <form id="gstr1_form" class="row g-2 align-items-end" autocomplete="off">
                        <div class="col-md-4 col-lg-1">
                            <label class="form-label fw-bold" for="from_date">From Date</label>
                            <input type="text" name="from_date" id="from_date"
                                class="form-control date-format" placeholder="DD-MM-YYYY">
                        </div>
                        <div class="col-md-4 col-lg-1">
                            <label class="form-label fw-bold" for="to_date">To Date</label>
                            <input type="text" name="to_date" id="to_date"
                                class="form-control date-format" placeholder="DD-MM-YYYY">
                        </div>
                        <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary flex-fill waves-effect">
                                @include('icons.filter', ['size' => 18])
                                Apply
                            </button>
                            <button type="button" id="filter_clear" class="btn btn-outline-secondary flex-fill waves-effect">
                                @include('icons.filter-clear', ['size' => 18])
                                Clear
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Report Output --}}
            <div id="report_output" class="d-none">

                {{-- Summary Table --}}
                <div class="card mb-3">
                    <div class="card-body p-0">
                        <div id="gstr1_tabulator" style="font-family: 'Inter', sans-serif;"></div>
                    </div>
                </div>

            </div>{{-- end #report_output --}}
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     Drill-Down Full-Screen Modal
═══════════════════════════════════════════════════════ --}}
<div class="modal fade" id="gstr1DrillModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">

            {{-- Header --}}
            <div class="modal-header py-2 border-bottom border-2" style="background: linear-gradient(145deg,#ffffff,#f8f9fa);">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width:34px;height:34px;flex-shrink:0;">
                        <i class="fa-solid fa-table-list fa-sm"></i>
                    </div>
                    <div>
                        <div class="modal-section-title text-primary" id="drill_modal_title">Section Detail</div>
                        <div class="text-muted" style="font-size:0.72rem;" id="drill_modal_subtitle"></div>
                    </div>
                    <div class="d-flex gap-2 ms-2 flex-wrap" id="drill_modal_stats"></div>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button" id="btn_export_drill_excel" class="btn btn-sm btn-outline-success waves-effect">
                        <i class="fa-solid fa-file-excel me-1"></i> Export Excel
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            {{-- Body --}}
            <div class="modal-body">
                <div id="drill_tabulator_wrap">
                    <div id="drill_tabulator"></div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
             HSN Summary Full-Screen Modal
        ═══════════════════════════════════════════════════════ --}}
<div class="modal fade" id="hsnSummaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">

            {{-- Header --}}
            <div class="modal-header py-2 border-bottom border-2"
                style="background: linear-gradient(145deg,#ffffff,#f8f9fa);">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                        style="width:34px;height:34px;flex-shrink:0;">
                        <i class="fa-solid fa-list-ol fa-sm"></i>
                    </div>
                    <div>
                        <div class="modal-section-title text-primary">HSN-wise Summary of Outward Supplies</div>
                        <div class="text-muted" style="font-size:0.72rem;" id="hsn_summary_modal_subtitle"></div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button" id="btn_export_hsn_summary_excel" class="btn btn-sm btn-outline-success waves-effect">
                        <i class="fa-solid fa-file-excel me-1"></i> Export Excel
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            {{-- Body --}}
            <div class="modal-body">
                <div id="hsn_summary_tabulator_wrap">
                    <div id="hsn_summary_tabulator"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    const GSTR1_SUMMARY_URL = "{{ route('gstr1-report.summary') }}";
    const GSTR1_DETAIL_URL = "{{ route('gstr1-report.detail') }}";
    const GSTR1_EXPORT_EXCEL_URL = "{{ route('gstr1-report.export.excel') }}";
const GSTR1_EXPORT_OFFLINE_EXCEL_URL = "{{ route('gstr1-report.export.offline.excel') }}";
    const GSTR1_EXPORT_DETAIL_EXCEL_URL = "{{ route('gstr1-report.export.detail.excel') }}";
    const COMPANY_NAME = @json($companyName ?? 'Company Name');
    const COMPANY_GSTIN = @json($companyGst ?? 'GSTIN');
</script>
<script src="{{ asset('js/libs/tabulator.min.js') }}?v={{ hash_file('md5', public_path('js/libs/tabulator.min.js')) }}"></script>
<script src="{{ asset('js/modules/gst/gstr1.js') }}?v={{ hash_file('md5', public_path('js/modules/gst/gstr1.js')) }}"></script>
@endsection