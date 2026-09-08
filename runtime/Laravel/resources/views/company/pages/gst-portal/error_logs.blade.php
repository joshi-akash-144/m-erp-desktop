@extends('company.layout.app')
@section('title', 'GST Errors & Logs')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <style>
        .tab-strip { border-bottom: 2px solid #e5e7eb; background: #fff; }
        .tab-strip .nav-link {
            border: none; border-bottom: 3px solid transparent;
            padding: .6rem 1.4rem; font-weight: 600; font-size: .85rem;
            color: #6b7280; border-radius: 0; margin-bottom: -2px;
        }
        .tab-strip .nav-link:hover     { color: #1f2937; border-bottom-color: #d1d5db; background: transparent; }
        .tab-strip .nav-link.active    { color: #d63939; border-bottom-color: #d63939; background: transparent; }

        .err-code {
            display: inline-block; padding: 1px 8px; border-radius: 4px;
            background: #fee2e2; color: #d63939; font-weight: 700; font-size: .78rem;
            font-family: monospace;
        }
        .err-code.none { background: #f3f4f6; color: #6b7280; }

        /* Detail modal payload */
        .payload-box {
            background: #1e1e2e; color: #cdd6f4; border-radius: 6px;
            padding: 12px; font-family: monospace; font-size: .75rem;
            max-height: 300px; overflow-y: auto; white-space: pre-wrap; word-break: break-all;
        }
        .payload-label { font-size: .7rem; font-weight: 700; text-transform: uppercase;
            color: #6b7280; letter-spacing: .06em; margin-bottom: 4px; }
    </style>
@endsection

@section('content')
<div class="page-wrapper">

    {{-- Page Header --}}
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-danger rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-danger-lt text-danger rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-triangle-exclamation text-danger"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-danger fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                E-Invoice &amp; EWB Errors &amp; Logs
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-list me-1"></i> report
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
    {{-- <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center justify-content-between">
                <div class="col-auto">
                    <div class="card border-0 border-start border-4 border-danger">
                        <div class="card-body shadow p-2">
                            <h3 class="page-title font-monospace mb-0">
                                <i class="fa-solid fa-triangle-exclamation text-danger me-1"></i>
                                E-Invoice &amp; EWB Errors &amp; Logs
                            </h3>
                        </div>
                    </div>
                </div>
                        <!-- 🔹 Action Buttons -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2 d-print-none">
                        <div class="card border-0 border-end border-4 border-danger">
                            <div class="card-body shadow-sm p-2">
                                <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                    <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn waves-effect">
                                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>--}}

    {{-- Page Body --}}
    <div class="page-body">
        <div class="container-xl">
            <div class="card">

                {{-- Tab strip --}}
                <div class="tab-strip px-1">
                    <ul class="nav" id="errTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-einv"
                                data-bs-toggle="tab" data-bs-target="#panel-einv"
                                type="button" role="tab">
                                <i class="fa-solid fa-file-invoice me-1"></i>E-Invoice Errors
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-ewb"
                                data-bs-toggle="tab" data-bs-target="#panel-ewb"
                                type="button" role="tab">
                                <i class="fa-solid fa-truck-fast me-1"></i>E-Way Bill Errors
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="tab-content">

                    {{-- ── E-INVOICE ERRORS ── --}}
                    <div class="tab-pane fade show active" id="panel-einv" role="tabpanel">
                        {{-- Filter row --}}
                        <div class="p-3 border-bottom">
                            <div class="d-flex flex-wrap gap-2 align-items-end">
                                <div class="col-md-4 col-lg-1 m-erp">
                                    <label class="fw-bold">Bill No.</label>
                                    <input type="text" id="einv_ref" class="form-control form-control-sm" placeholder="Search bill no." style="width:160px">
                                </div>
                                <!-- Start Date -->
                                <div class="col-md-4 col-lg-1 m-erp">
                                    <label class="fw-bold">Start Date</label>
                                    <input type="text" id="einv_from" placeholder="DD-MM-YYYY" class="form-control date-input">
                                </div>

                                <!-- End Date -->
                                <div class="col-md-4 col-lg-1 m-erp">
                                    <label class="fw-bold">End Date</label>
                                    <input type="text" id="einv_to" placeholder="DD-MM-YYYY" class="form-control date-input">
                                </div>
                                <!-- <div>
                                    <label class="form-label fs-5 mb-1">From Date</label>
                                    <input type="date" id="einv_from" class="form-control form-control-sm" style="width:150px">
                                </div>
                                <div>
                                    <label class="form-label fs-5 mb-1">To Date</label>
                                    <input type="date" id="einv_to" class="form-control form-control-sm" style="width:150px">
                                </div> -->
                           <!-- Filter Buttons -->
                                <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
                                    <button id="einv_apply" class="btn btn-primary flex-fill waves-effect">
                                        @include('icons.filter', ['size' => 20])
                                        Apply
                                    </button>
                                    <button id="einv_clear" class="btn btn-outline-secondary flex-fill waves-effect">
                                        @include('icons.filter-clear', ['size' => 20])
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div id="einv_table"></div>
                    </div>

                    {{-- ── EWB ERRORS ── --}}
                    <div class="tab-pane fade" id="panel-ewb" role="tabpanel">
                        {{-- Filter row --}}
                        <div class="p-3 border-bottom">
                            <div class="d-flex flex-wrap gap-2 align-items-end">
                                <div class="col-md-4 col-lg-1 m-erp">
                                    <label class="fw-bold">Bill No.</label>
                                    <input type="text" id="ewb_ref" class="form-control form-control-sm" placeholder="Search bill no." style="width:160px">
                                </div>
                                <!-- Start Date -->
                                <div class="col-md-4 col-lg-1 m-erp">
                                    <label class="fw-bold">Start Date</label>
                                    <input type="text" id="ewb_from" placeholder="DD-MM-YYYY" class="form-control date-input">
                                </div>

                                <!-- End Date -->
                                <div class="col-md-4 col-lg-1 m-erp">
                                    <label class="fw-bold">End Date</label>
                                    <input type="text" id="ewb_to" placeholder="DD-MM-YYYY" class="form-control date-input">
                                </div>
                                <!-- <div>
                                    <label class="form-label fs-5 mb-1">From Date</label>
                                    <input type="date" id="ewb_from" class="form-control form-control-sm" style="width:150px">
                                </div>
                                <div>
                                    <label class="form-label fs-5 mb-1">To Date</label>
                                    <input type="date" id="ewb_to" class="form-control form-control-sm" style="width:150px">
                                </div> -->
                                <!-- Filter Buttons -->
                                <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
                                    <button id="ewb_apply" class="btn btn-primary flex-fill waves-effect">
                                        @include('icons.filter', ['size' => 20])
                                        Apply
                                    </button>
                                    <button id="ewb_clear" class="btn btn-outline-secondary flex-fill waves-effect">
                                        @include('icons.filter-clear', ['size' => 20])
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div id="ewb_table"></div>
                    </div>

                </div>{{-- /tab-content --}}
            </div>
        </div>
    </div>  
</div>

{{-- ══════════════ DETAIL MODAL ══════════════ --}}
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-2 px-3" style="background:#fef2f2; border-bottom:3px solid #d63939;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-xmark text-danger fs-4"></i>
                    <div>
                        <div class="modal-title fw-bold text-danger">Error Detail</div>
                        <div class="text-muted small" id="detail_subtitle"></div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-3">
                    <div class="payload-label">Error Description</div>
                    <div class="alert alert-danger py-2 px-3 mb-0" id="detail_error" style="font-size:.82rem; font-family:monospace;"></div>
                </div>
                <div class="mb-3" id="detail_req_wrap">
                    <div class="payload-label">Request Payload</div>
                    <div class="payload-box" id="detail_request"></div>
                </div>
                <div id="detail_res_wrap">
                    <div class="payload-label">Response Payload</div>
                    <div class="payload-box" id="detail_response"></div>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light border-top">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    const ERR_LOG = {
        jsonUrl   : "{{ route('gst-portal.error-logs-json') }}",
    };
</script>
<script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
<script src="{{ asset('js/modules/gst-portal/error-logs.js') }}?v={{ hash_file('md5', public_path('js/modules/gst-portal/error-logs.js')) }}"></script>    
@endsection
