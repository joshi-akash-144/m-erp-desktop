@extends('company.layout.app')
@section('title', 'E-WayBill / E-Invoice')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <style>
        /* ── Colour tokens ────────────────────────────────────── */
        :root {
            --ewb-color  : #2a9d56;
            --ewb-bg     : #e6f5ec;
            --irn-color  : #066ed1;
            --irn-bg     : #e8f2fc;
            --both-color : #7c3aed;
            --both-bg    : #ede9fe;
            --danger     : #d63939;
        }

        /* ── Filter card ─────────────────────────────────────── */
        .gen-filter-row { display: flex; flex-wrap: wrap; gap: .5rem; align-items: flex-end; }
        .gen-filter-row .fld       { display: flex; flex-direction: column; min-width: 110px; }
        .gen-filter-row .fld.wide  { flex: 1 1 180px; }
        .gen-filter-row .fld label { font-size: .72rem; margin-bottom: 2px; text-transform: uppercase; letter-spacing: .04em; }

        /* ── Type toggle buttons per row ─────────────────────── */
        .type-grp { display: flex; gap: 3px; }
        .type-btn {
            font-size: .68rem; padding: 2px 6px; border-radius: 4px; font-weight: 600;
            cursor: pointer; border: 1px solid transparent; line-height: 1.4;
            transition: background .15s, color .15s;
        }
        .type-btn.both-btn         { border-color: var(--both-color); color: var(--both-color); background: #fff; margin-top: 4px; }
        .type-btn.both-btn.active  { background: var(--both-color); color: #fff; }
        .type-btn.irn-btn          { border-color: var(--irn-color);  color: var(--irn-color);  background: #fff; }
        .type-btn.irn-btn.active   { background: var(--irn-color);  color: #fff; }
        .type-btn.ewb-btn          { border-color: var(--ewb-color);  color: var(--ewb-color);  background: #fff; }
        .type-btn.ewb-btn.active   { background: var(--ewb-color);  color: #fff; }

        /* ── Row action buttons ──────────────────────────────── */
        .act-btn {
            font-size: .68rem; padding: 2px 6px; border-radius: 4px; font-weight: 600;
            cursor: pointer; border: none; line-height: 1.4; display: inline-flex;
            align-items: center; gap: 2px;
        }
        .act-btn-green  { background: #166534; color: #fff; }
        .act-btn-teal   { background: #0e7490; color: #fff; }
        .act-btn-red    { background: var(--danger); color: #fff; }

        /* ── Status number highlights ────────────────────────── */
        .num-ewb { color: var(--ewb-color); font-weight: 700; font-size: .78rem; }
        .num-irn { color: var(--irn-color); font-weight: 700; font-size: .78rem; }

        /* ── Footer bar ──────────────────────────────────────── */
        .gen-footer {
            display: flex; align-items: center; gap: 1rem;
            padding: .75rem 1rem; border-top: 1px solid #e5e7eb; background: #f9fafb;
        }

        /* ── Results modal rows ──────────────────────────────── */
        .result-ok   { color: #166534; }
        .result-fail { color: var(--danger); }

        /* ── Cancel modals ───────────────────────────────────── */
        .modal-header-danger { background: #fef2f2; border-bottom: 3px solid var(--danger); }
        .modal-header-danger .modal-title { color: var(--danger); font-weight: 700; }
    </style>
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
                            <i class="fs-3 fa-solid fa-truck-fast"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                E-WayBill / E-Invoice
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
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="card border-0 border-start border-4 border-primary" style="max-width:350px">
                        <div class="card-body shadow p-2">
                            <h3 class="page-title font-monospace mb-0">
                                <i class="fa-solid fa-truck-fast text-primary me-1"></i>E-WayBill / E-Invoice
                            </h3>
                        </div>
                    </div>
                </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2 d-print-none">
                        <div class="card border-0 border-end border-4 border-primary">
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
    </div> --}}

    {{-- Page Body --}}
    <div class="page-body">
        <div class="container-xl">

            {{-- Filter row --}}
            <div class="card mb-2">
                <div class="card-body py-2 px-3">
                    <div class="gen-filter-row">

                        <div class="fld">
                            <label class="fw-bold">Date</label>
                            <input type="text" id="f_date" class="form-control form-control-sm" placeholder="DD-MM-YYYY">
                        </div>

                        <div class="fld">
                            <label class="fw-bold">Sales-Inv. No</label>
                            <input type="text" id="f_inv_no" class="form-control form-control-sm" placeholder="Sales Inv. No">
                        </div>

                        {{-- <div class="fld">
                            <label>GRN</label>
                            <input type="text" id="f_grn" class="form-control form-control-sm" placeholder="Enter GRN">
                        </div> --}}

                        <div class="fld wide">
                            <label class="fw-bold">Customer</label>
                            <select id="f_account" class="form-select form-select-sm sel2-account">
                                <option value="">--Select Customer--</option>
                                @foreach($accounts as $a)
                                    <option value="{{ $a->id }}">{{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="fld wide">
                            <label class="fw-bold">Product</label>
                            <select id="f_item" class="form-select form-select-sm sel2-item">
                                <option value="">--Select Product--</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="fld" style="min-width:160px">
                            <label class="fw-bold">Ewaybill | Einvoice</label>
                            <select id="f_status" class="form-select form-select-sm">
                                <option value="">--All--</option>
                                <option value="both_pending">Both Pending</option>
                                <option value="ewb_pending">EWB Pending</option>
                                <option value="irn_pending">IRN Pending</option>
                                <option value="ewb_generated">EWB Generated</option>
                                <option value="irn_generated">IRN Generated</option>
                            </select>
                        </div>
                   <!-- Filter Buttons -->
                        <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
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
            </div>

            {{-- Table card --}}
            <div class="card">
                <div class="card-body p-0">
                    <div id="gen_table"></div>
                </div>

                {{-- Footer: generate button --}}
                <div class="gen-footer">
                    <span id="sel_label" class="text-muted small fw-semibold">0 invoices selected</span>
                    <button id="btn_generate" class="btn btn-primary btn-sm" disabled>
                        <i class="fa-solid fa-file-invoice me-1"></i>
                        Generate E-Waybill | E-Invoice
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ══════════════ RESULTS MODAL ══════════════ --}}
<div class="modal fade" id="resultsModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">

            {{-- Header --}}
            <div class="modal-header px-4 py-3 border-bottom"
                 style="background: linear-gradient(135deg, #e8f5e9 0%, #fff 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-success d-flex align-items-center justify-content-center shadow-sm"
                         style="width: 44px; height: 44px; flex-shrink: 0;">
                        <i class="fa-solid fa-circle-check text-white fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold text-dark">Generation Results</h5>
                        <small class="text-muted">Review the outcome of the generation process</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body p-0">
                <div id="results_body"></div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer px-4 py-2 border-top" style="background:#fafbfc;">
                <button class="btn btn-sm btn-outline-secondary px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Close
                </button>
            </div>

        </div>
    </div>
</div>

{{-- ══════════════ CANCEL EWB MODAL ══════════════ --}}
<div class="modal fade" id="cancelEwbModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">

            {{-- Header --}}
            <div class="modal-header px-4 py-3 border-bottom"
                 style="background: linear-gradient(135deg, #fdecea 0%, #fff 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-danger d-flex align-items-center justify-content-center shadow-sm"
                         style="width: 44px; height: 44px; flex-shrink: 0;">
                        <i class="fa-solid fa-ban text-white fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold text-dark">Cancel E-Way Bill</h5>
                        <small class="text-muted" id="cewb_bill_label"></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body px-4 py-4">
                <input type="hidden" id="cewb_no_val">

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Reason <span class="text-danger">*</span>
                    </label>
                    <select id="cewb_reason" class="form-select">
                        <option value="1">1 – Duplicate</option>
                        <option value="2">2 – Order Cancelled</option>
                        <option value="3">3 – Data Entry Mistake</option>
                        <option value="4">4 – Other</option>
                    </select>
                </div>

                <div id="cewb_remark_wrap" style="display:none">
                    <label class="form-label fw-semibold">
                        Remark <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="cewb_remark" class="form-control" maxlength="300"
                           placeholder="Enter remark…">
                </div>

                <div class="alert alert-danger-lt border border-danger-subtle py-2 px-3 mb-0 mt-3 rounded-2"
                     style="font-size: .8rem;">
                    <i class="fa-solid fa-triangle-exclamation me-1 text-danger"></i>
                    This action <strong>cannot be undone</strong>. The E-Way Bill will be permanently cancelled.
                </div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer px-4 py-2 border-top d-flex justify-content-between align-items-center"
                 style="background:#fafbfc;">
                <button class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Close
                </button>
                <button id="cewb_submit" class="btn btn-danger btn-sm px-4 d-flex align-items-center gap-2">
                    <span class="cewb-spinner spinner-border spinner-border-sm d-none"></span>
                    <i class="fa-solid fa-ban cewb-icon"></i> Cancel EWB
                </button>
            </div>

        </div>
    </div>
</div>

{{-- ══════════════ CANCEL IRN MODAL ══════════════ --}}
<div class="modal fade" id="cancelIrnModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">

            {{-- Header --}}
            <div class="modal-header px-4 py-3 border-bottom"
                 style="background: linear-gradient(135deg, #fdecea 0%, #fff 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-danger d-flex align-items-center justify-content-center shadow-sm"
                         style="width: 44px; height: 44px; flex-shrink: 0;">
                        <i class="fa-solid fa-file-circle-xmark text-white fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold text-dark">Cancel IRN</h5>
                        <small class="text-muted" id="cirn_bill_label"></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body px-4 py-4">
                <input type="hidden" id="cirn_val">

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Reason <span class="text-danger">*</span>
                    </label>
                    <select id="cirn_reason" class="form-select">
                        <option value="1">1 – Duplicate</option>
                        <option value="2">2 – Data Entry Mistake</option>
                        <option value="3">3 – Order Cancelled</option>
                        <option value="4">4 – Other</option>
                    </select>
                </div>

                <div id="cirn_remark_wrap" style="display:none">
                    <label class="form-label fw-semibold">
                        Remark <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="cirn_remark" class="form-control" maxlength="300"
                           placeholder="Enter remark…">
                </div>

                <div class="alert alert-warning py-2 px-3 mb-0 mt-3 rounded-2 d-flex align-items-start gap-2"
                     style="font-size: .8rem;">
                    <i class="fa-solid fa-clock text-warning mt-1"></i>
                    <span>IRN can only be cancelled within <strong>24 hours</strong> of generation.</span>
                </div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer px-4 py-2 border-top d-flex justify-content-between align-items-center"
                 style="background:#fafbfc;">
                <button class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Close
                </button>
                <button id="cirn_submit" class="btn btn-danger btn-sm px-4 d-flex align-items-center gap-2">
                    <span class="cirn-spinner spinner-border spinner-border-sm d-none"></span>
                    <i class="fa-solid fa-ban cirn-icon"></i> Cancel IRN
                </button>
            </div>

        </div>
    </div>
</div>

{{-- ══════════════ CANCEL EWB MODAL ══════════════ --}}
<div class="modal fade" id="cancelEwbModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header modal-header-danger py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-xmark fs-4" style="color:var(--danger)"></i>
                    <div>
                        <div class="modal-title">Cancel E-Way Bill</div>
                        <div class="text-muted" id="cewb_bill_label" style="font-size:.72rem"></div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-3 py-3">
                <input type="hidden" id="cewb_no_val">
                <div class="mb-3">
                    <label class="form-label fw-semibold fs-5">Reason <span class="text-danger">*</span></label>
                    <select id="cewb_reason" class="form-select form-select-sm">
                        <option value="1">1 – Duplicate</option>
                        <option value="2">2 – Order Cancelled</option>
                        <option value="3">3 – Data Entry Mistake</option>
                        <option value="4">4 – Other</option>
                    </select>
                </div>
                <div id="cewb_remark_wrap" style="display:none">
                    <label class="form-label fw-semibold fs-5">Remark <span class="text-danger">*</span></label>
                    <input type="text" id="cewb_remark" class="form-control form-control-sm" maxlength="300">
                </div>
            </div>
            <div class="modal-footer py-2 px-3 border-top bg-light">
                <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button id="cewb_submit" class="btn btn-sm btn-danger d-flex align-items-center gap-1">
                    <span class="cewb-spinner spinner-border spinner-border-sm d-none"></span>
                    <i class="fa-solid fa-ban cewb-icon"></i> Cancel EWB
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════ CANCEL IRN MODAL ══════════════ --}}
<div class="modal fade" id="cancelIrnModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header modal-header-danger py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-xmark fs-4" style="color:var(--danger)"></i>
                    <div>
                        <div class="modal-title">Cancel IRN</div>
                        <div class="text-muted" id="cirn_bill_label" style="font-size:.72rem"></div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-3 py-3">
                <input type="hidden" id="cirn_val">
                <div class="mb-3">
                    <label class="form-label fw-semibold fs-5">Reason <span class="text-danger">*</span></label>
                    <select id="cirn_reason" class="form-select form-select-sm">
                        <option value="1">1 – Duplicate</option>
                        <option value="2">2 – Data Entry Mistake</option>
                        <option value="3">3 – Order Cancelled</option>
                        <option value="4">4 – Other</option>
                    </select>
                </div>
                <div id="cirn_remark_wrap" style="display:none">
                    <label class="form-label fw-semibold fs-5">Remark <span class="text-danger">*</span></label>
                    <input type="text" id="cirn_remark" class="form-control form-control-sm" maxlength="300">
                </div>
                <div class="alert alert-warning py-2 px-2 mb-0 mt-2" style="font-size:.75rem">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                    IRN can only be cancelled within <strong>24 hours</strong> of generation.
                </div>
            </div>
            <div class="modal-footer py-2 px-3 border-top bg-light">
                <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button id="cirn_submit" class="btn btn-sm btn-danger d-flex align-items-center gap-1">
                    <span class="cirn-spinner spinner-border spinner-border-sm d-none"></span>
                    <i class="fa-solid fa-ban cirn-icon"></i> Cancel IRN
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
    const GST_GEN = {
        salesBillsJsonUrl : "{{ route('gst-portal.sales-bills-json') }}",
        bulkGenerateUrl   : "{{ route('gst-portal.bulk-generate') }}",
        refetchIrnUrl     : "{{ route('gst-portal.refetch-irn') }}",
        cancelEwbUrl      : "{{ route('eway-bill.cancel') }}",
        cancelIrnUrl      : "{{ route('e-invoice.cancel-irn') }}",
        salesBillPrintUrl : "{{ route('sales-invoices.sales-print-report', '') }}",
        printEwbUrl       : "{{ route('gst-portal.print-ewb', '__EWB__') }}".replace('__EWB__', ''),
        csrfToken         : "{{ csrf_token() }}",
    };
</script>
<script src="{{ asset('js/modules/gst-portal/generate.js') }}?v={{ filemtime(public_path('js/modules/gst-portal/generate.js')) }}"></script>
@endsection
