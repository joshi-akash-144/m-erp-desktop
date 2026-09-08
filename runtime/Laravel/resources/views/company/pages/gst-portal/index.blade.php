@extends('company.layout.app')
@section('title', 'GST Portal')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <style>
        :root {
            --gst-einv-color   : #066ed1;
            --gst-einv-bg      : #e8f2fc;
            --gst-ewb-color    : #2a9d56;
            --gst-ewb-bg       : #e6f5ec;
            --gst-cancel-color : #d63939;
        }

        /* Tab strip */
        .gst-tab-strip                     { border-bottom: 2px solid #e5e7eb; background: #fff; }
        .gst-tab-strip .nav-link           { border: none; border-bottom: 3px solid transparent;
                                             padding: .6rem 1.2rem; font-weight: 600; font-size: .85rem;
                                             color: #6b7280; border-radius: 0; margin-bottom: -2px; }
        .gst-tab-strip .nav-link:hover     { color: #1f2937; border-bottom-color: #d1d5db; background: transparent; }
        .gst-tab-strip .nav-link.active    { color: var(--gst-einv-color); border-bottom-color: var(--gst-einv-color); background: transparent; }
        #ewaybill-tab.active               { color: var(--gst-ewb-color) !important; border-bottom-color: var(--gst-ewb-color) !important; }

        /* Filter row grid */
        .m-gst-filter-row {
            grid-template-columns: 140px 140px 1fr 160px auto;
        }
        @media (max-width: 992px) {
            .m-gst-filter-row { grid-template-columns: 1fr; }
        }

        /* Status badges */
        .gst-badge-generated  { background: #dcfce7; color: #166534; }
        .gst-badge-pending    { background: #fef9c3; color: #854d0e; }
        .gst-badge-cancelled  { background: #fee2e2; color: #991b1b; }

        /* IRN mono cell */
        .irn-cell { font-family: monospace; font-size: .75rem; }

        /* Modal header accents */
        .modal-header-einv   { background: var(--gst-einv-bg); border-bottom: 3px solid var(--gst-einv-color); }
        .modal-header-ewb    { background: var(--gst-ewb-bg);  border-bottom: 3px solid var(--gst-ewb-color); }
        .modal-header-danger { background: #fef2f2; border-bottom: 3px solid var(--gst-cancel-color); }
        .modal-header-einv .modal-title   { color: var(--gst-einv-color); font-weight: 700; }
        .modal-header-ewb  .modal-title   { color: var(--gst-ewb-color);  font-weight: 700; }
        .modal-header-danger .modal-title { color: var(--gst-cancel-color); font-weight: 700; }
    </style>
@endsection

@section('content')
<div class="page-wrapper">

    {{-- Page Header --}}
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center justify-content-between">
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card border-0 border-start border-4 border-primary">
                        <div class="card-body shadow p-2">
                            <h3 class="page-title font-monospace mb-0">
                                <i class="fa-solid fa-file-contract text-primary"></i>&nbsp;GST Portal
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Page Body --}}
    <div class="page-body">
        <div class="container-xl">
            <div class="card">

                {{-- Tab strip --}}
                <div class="gst-tab-strip px-1">
                    <ul class="nav" id="gstTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="einvoice-tab"
                                data-bs-toggle="tab" data-bs-target="#einvoice-panel"
                                type="button" role="tab">
                                <i class="fa-solid fa-file-invoice me-1"></i>E-Invoice (IRN)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ewaybill-tab"
                                data-bs-toggle="tab" data-bs-target="#ewaybill-panel"
                                type="button" role="tab">
                                <i class="fa-solid fa-truck-fast me-1"></i>E-Way Bill
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="tab-content">

                    {{-- ── E-INVOICE TAB ── --}}
                    <div class="tab-pane fade show active" id="einvoice-panel" role="tabpanel">

                        <div class="p-3">
                            <div id="einv_filters" class="row g-2 m-gst-filter-row">

                                <div class="col-md-4 col-lg-1">
                                    <label for="einv_start_date" class="fs-4">From Date</label>
                                    <input type="text" id="einv_start_date" class="form-control" placeholder="DD-MM-YYYY">
                                </div>

                                <div class="col-md-4 col-lg-1">
                                    <label for="einv_end_date" class="fs-4">To Date</label>
                                    <input type="text" id="einv_end_date" class="form-control" placeholder="DD-MM-YYYY">
                                </div>

                                <div class="col-md-4 col-lg-3">
                                    <label for="einv_account_id" class="fs-4">Customer</label>
                                    <select id="einv_account_id" class="form-select select2-einv">
                                        <option value="">All Customers</option>
                                        @foreach($accounts as $a)
                                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 col-lg-2">
                                    <label for="einv_gst_status" class="fs-4">IRN Status</label>
                                    <select id="einv_gst_status" class="form-select">
                                        <option value="">All</option>
                                        <option value="generated">Generated</option>
                                        <option value="not_generated">Pending</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>

                                <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
                                    <button id="einv_filter_apply" class="btn btn-primary flex-fill waves-effect">
                                        @include('icons.filter', ['size' => 20])
                                        Apply
                                    </button>
                                    <button id="einv_filter_clear" class="btn btn-outline-secondary flex-fill waves-effect">
                                        @include('icons.filter-clear', ['size' => 20])
                                        Clear
                                    </button>
                                </div>

                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div id="einvoice_table"></div>
                        </div>

                    </div>{{-- /einvoice-panel --}}

                    {{-- ── E-WAY BILL TAB ── --}}
                    <div class="tab-pane fade" id="ewaybill-panel" role="tabpanel">

                        <div class="p-3">
                            <div id="ewb_filters" class="row g-2 m-gst-filter-row">

                                <div class="col-md-4 col-lg-1">
                                    <label for="ewb_start_date" class="fs-4">From Date</label>
                                    <input type="text" id="ewb_start_date" class="form-control" placeholder="DD-MM-YYYY">
                                </div>

                                <div class="col-md-4 col-lg-1">
                                    <label for="ewb_end_date" class="fs-4">To Date</label>
                                    <input type="text" id="ewb_end_date" class="form-control" placeholder="DD-MM-YYYY">
                                </div>

                                <div class="col-md-4 col-lg-3">
                                    <label for="ewb_account_id" class="fs-4">Customer</label>
                                    <select id="ewb_account_id" class="form-select select2-ewb">
                                        <option value="">All Customers</option>
                                        @foreach($accounts as $a)
                                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 col-lg-2">
                                    <label for="ewb_gst_status" class="fs-4">EWB Status</label>
                                    <select id="ewb_gst_status" class="form-select">
                                        <option value="">All</option>
                                        <option value="generated">Generated</option>
                                        <option value="not_generated">Pending</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>

                                <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
                                    <button id="ewb_filter_apply" class="btn btn-primary flex-fill waves-effect">
                                        @include('icons.filter', ['size' => 20])
                                        Apply
                                    </button>
                                    <button id="ewb_filter_clear" class="btn btn-outline-secondary flex-fill waves-effect">
                                        @include('icons.filter-clear', ['size' => 20])
                                        Clear
                                    </button>
                                </div>

                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div id="ewaybill_table"></div>
                        </div>

                    </div>{{-- /ewaybill-panel --}}

                </div>{{-- /tab-content --}}
            </div>{{-- /card --}}
        </div>
    </div>
</div>

{{-- ══════════════════════════ MODALS ══════════════════════════ --}}

{{-- Cancel IRN --}}
<div class="modal fade" id="cancelIrnModal" tabindex="-1" aria-labelledby="cancelIrnModalLabel">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header modal-header-danger py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-xmark fs-4" style="color:var(--gst-cancel-color)"></i>
                    <div>
                        <div class="modal-title" id="cancelIrnModalLabel">Cancel IRN</div>
                        <div class="text-muted" id="cancel_irn_bill_label" style="font-size:.72rem"></div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-3 py-3">
                <input type="hidden" id="cancel_irn_value">
                <div class="mb-3">
                    <label class="form-label fw-semibold fs-5">Cancellation Reason <span class="text-danger">*</span></label>
                    <select id="cancel_irn_reason" class="form-select form-select-sm">
                        <option value="1">1 – Duplicate</option>
                        <option value="2">2 – Data Entry Mistake</option>
                        <option value="3">3 – Order Cancelled</option>
                        <option value="4">4 – Other</option>
                    </select>
                </div>
                <div id="cancel_irn_remark_wrap" style="display:none">
                    <label class="form-label fw-semibold fs-5">Remark <span class="text-danger">*</span></label>
                    <input type="text" id="cancel_irn_remark" class="form-control form-control-sm"
                        maxlength="300" placeholder="Required for Other reason">
                </div>
                <div class="alert alert-warning d-flex align-items-center gap-2 py-2 px-2 mb-0 mt-2" style="font-size:.75rem">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    IRN can only be cancelled within <strong>24 hours</strong> of generation.
                </div>
            </div>
            <div class="modal-footer py-2 px-3 border-top bg-light">
                <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button id="cancel_irn_submit" class="btn btn-sm btn-danger d-flex align-items-center gap-1">
                    <span class="cancel-irn-spinner spinner-border spinner-border-sm d-none"></span>
                    <i class="fa-solid fa-ban cancel-irn-icon"></i> Cancel IRN
                </button>
            </div>
        </div>
    </div>
</div>

{{-- IRN Details --}}
<div class="modal fade" id="irnDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header modal-header-einv py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-file-invoice fs-4" style="color:var(--gst-einv-color)"></i>
                    <div class="modal-title">E-Invoice Details</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3" id="irn_details_body">
                <div class="text-center py-5">
                    <div class="spinner-border" style="color:var(--gst-einv-color)"></div>
                    <p class="text-muted mt-2 mb-0" style="font-size:.82rem">Fetching details…</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Generate EWB --}}
<div class="modal fade" id="generateEwbModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header modal-header-ewb py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-truck-fast fs-4" style="color:var(--gst-ewb-color)"></i>
                    <div>
                        <div class="modal-title" id="genEwbTitle">Generate E-Way Bill</div>
                        <div class="text-muted" id="gen_ewb_bill_label" style="font-size:.72rem"></div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <input type="hidden" id="gen_ewb_invoice_id">
                <p class="text-uppercase fw-bold mb-2" style="font-size:.7rem;color:#6b7280;letter-spacing:.06em">Transport Details</p>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fs-5 fw-semibold">Mode <span class="text-danger">*</span></label>
                        <select id="gen_ewb_trans_mode" class="form-select form-select-sm">
                            <option value="1">1 – Road</option>
                            <option value="2">2 – Rail</option>
                            <option value="3">3 – Air</option>
                            <option value="4">4 – Ship</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fs-5 fw-semibold">Vehicle Type</label>
                        <select id="gen_ewb_vehicle_type" class="form-select form-select-sm">
                            <option value="R">R – Regular</option>
                            <option value="O">O – ODC (Oversize)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fs-5 fw-semibold">Vehicle No <span class="text-danger">*</span></label>
                        <input type="text" id="gen_ewb_vehicle_no" class="form-control form-control-sm"
                            maxlength="15" placeholder="e.g. GJ01AB1234" style="text-transform:uppercase">
                    </div>
                    <div class="col-6">
                        <label class="form-label fs-5 fw-semibold">Distance (KM) <span class="text-danger">*</span></label>
                        <input type="number" id="gen_ewb_distance" class="form-control form-control-sm" min="1" placeholder="e.g. 250">
                    </div>
                </div>
                <hr class="my-2">
                <p class="text-uppercase fw-bold mb-2" style="font-size:.7rem;color:#6b7280;letter-spacing:.06em">
                    Transporter Details <span class="text-muted fw-normal">(optional)</span>
                </p>
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label fs-5 fw-semibold">Transporter GSTIN / TRANSIN</label>
                        <input type="text" id="gen_ewb_transporter_id" class="form-control form-control-sm"
                            maxlength="15" placeholder="15-char GSTIN or TRANSIN">
                    </div>
                    <div class="col-6">
                        <label class="form-label fs-5 fw-semibold">LR / Doc No</label>
                        <input type="text" id="gen_ewb_trans_doc_no" class="form-control form-control-sm" maxlength="15">
                    </div>
                    <div class="col-6">
                        <label class="form-label fs-5 fw-semibold">LR / Doc Date</label>
                        <input type="text" id="gen_ewb_trans_doc_date" class="form-control form-control-sm" placeholder="DD/MM/YYYY">
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 border-top bg-light">
                <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button id="gen_ewb_submit" class="btn btn-sm d-flex align-items-center gap-1"
                    style="background:var(--gst-ewb-color);color:#fff">
                    <span class="gen-ewb-spinner spinner-border spinner-border-sm d-none"></span>
                    <i class="fa-solid fa-truck-fast gen-ewb-icon"></i> Generate EWB
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Cancel EWB --}}
<div class="modal fade" id="cancelEwbModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header modal-header-danger py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-xmark fs-4" style="color:var(--gst-cancel-color)"></i>
                    <div>
                        <div class="modal-title">Cancel E-Way Bill</div>
                        <div class="text-muted" id="cancel_ewb_bill_label" style="font-size:.72rem"></div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-3 py-3">
                <input type="hidden" id="cancel_ewb_no_value">
                <div class="mb-3">
                    <label class="form-label fw-semibold fs-5">Cancellation Reason <span class="text-danger">*</span></label>
                    <select id="cancel_ewb_reason" class="form-select form-select-sm">
                        <option value="1">1 – Duplicate</option>
                        <option value="2">2 – Order Cancelled</option>
                        <option value="3">3 – Data Entry Mistake</option>
                        <option value="4">4 – Other</option>
                    </select>
                </div>
                <div id="cancel_ewb_remark_wrap" style="display:none">
                    <label class="form-label fw-semibold fs-5">Remark <span class="text-danger">*</span></label>
                    <input type="text" id="cancel_ewb_remark" class="form-control form-control-sm" maxlength="300">
                </div>
            </div>
            <div class="modal-footer py-2 px-3 border-top bg-light">
                <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button id="cancel_ewb_submit" class="btn btn-sm btn-danger d-flex align-items-center gap-1">
                    <span class="cancel-ewb-spinner spinner-border spinner-border-sm d-none"></span>
                    <i class="fa-solid fa-ban cancel-ewb-icon"></i> Cancel EWB
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
    const GST_PORTAL = {
        eInvoiceListUrl : "{{ route('gst-portal.e-invoice-list') }}",
        eWayBillListUrl : "{{ route('gst-portal.e-way-bill-list') }}",
        generateIrnUrl  : "{{ route('e-invoice.generate-irn') }}",
        cancelIrnUrl    : "{{ route('e-invoice.cancel-irn') }}",
        irnDetailsUrl   : "{{ route('e-invoice.details', ':irn') }}",
        generateEwbUrl  : "{{ route('eway-bill.generate') }}",
        cancelEwbUrl    : "{{ route('eway-bill.cancel') }}",
        csrfToken       : "{{ csrf_token() }}",
    };
</script>
<script src="{{ asset('js/modules/gst-portal/index.js') }}?v={{ filemtime(public_path('js/modules/gst-portal/index.js')) }}"></script>
@endsection
