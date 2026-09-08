@extends('company.layout.app')
@section('title', 'Dairy Analysis')

@section('css')
<link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
<style>

    /* ── Top bar ──────────────────────────────────────────── */
    .da-topbar {
        border-bottom: 2px solid #4d7cc8;
        border-radius: 0;
    }

    /* ── Section cards ────────────────────────────────────── */
    .da-section {
        border-radius: 5px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }

    .da-section-header {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 7px 14px;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    /* Numbered step circle */
    .da-step-num {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 900;
        color: #fff;
        flex-shrink: 0;
    }

    /* Sales — blue */
    .da-section-sales {
        border-left: 4px solid #2563eb;
    }
    .da-section-sales .da-section-header {
        background: #dbeafe;
        color: #1e40af;
        border-bottom: 1px solid #bfdbfe;
    }
    .da-section-sales .da-step-num   { background: #2563eb; }
    .da-section-sales .da-amt-val    { color: #1d4ed8; }

    /* Purchase — green */
    .da-section-purchase {
        border-left: 4px solid #16a34a;
    }
    .da-section-purchase .da-section-header {
        background: #dcfce7;
        color: #14532d;
        border-bottom: 1px solid #bbf7d0;
    }
    .da-section-purchase .da-step-num { background: #16a34a; }
    .da-section-purchase .da-amt-val  { color: #15803d; }

    /* Analysis — violet */
    .da-section-analysis {
        border-left: 4px solid #7c3aed;
    }
    .da-section-analysis .da-section-header {
        background: #ede9fe;
        color: #4c1d95;
        border-bottom: 1px solid #ddd6fe;
    }
    .da-section-analysis .da-step-num { background: #7c3aed; }

    /* File number badge in purchase header */
    .da-file-badge {
        font-weight: 900;
        font-size: 1rem;
        color: #166534;
        background: #f0fdf4;
        border: 2px solid #86efac;
        border-radius: 4px;
        padding: 0 10px;
        min-width: 38px;
        text-align: center;
        line-height: 1.7;
    }

    /* ── Field grid inside each section ───────────────────── */
    .da-field-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
        gap: 8px 28px;
        padding: 12px 16px;
    }

    .da-field-item {
        display: flex;
        flex-direction: column;
        gap: 1px;
        min-width: 0;
    }

    .da-field-lbl {
        font-size: 0.9rem;
        text-transform: uppercase;
        color: #6a1a1a;
        font-weight: 600;
        letter-spacing: 0.05em;
    }

    .da-field-val {
        font-weight: 600;
        color: #1e293b;
        font-size: 0.88rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* ── Financial strip at bottom of section ─────────────── */
    .da-fin-strip {
        display: flex;
        border-top: 1px solid #e8edf5;
        background: #f8fafc;
    }

    .da-stat {
        flex: 1;
        padding: 8px 16px;
        text-align: center;
        border-right: 1px solid #e8edf5;
    }
    .da-stat:last-child { border-right: none; }

    .da-stat-lbl {
        display: block;
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #081c38;
        letter-spacing: 0.05em;
        margin-bottom: 1px;
    }

    .da-stat-val {
        display: block;
        font-size: 1rem;
        font-weight: 800;
        color: #334155;
    }

    /* ── Flow connectors between steps ───────────────────── */
    .da-connector {
        display: flex;
        align-items: center;
        gap: 0;
        padding: 5px 16px;
    }

    .da-connector-line {
        flex: 1;
        height: 1px;
        background: #e2e8f0;
    }

    .da-connector-chip {
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 4px 16px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        font-size: 0.67rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        white-space: nowrap;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }

    /* ── Parameter table ──────────────────────────────────── */
    #parameter_table th {
        font-size: 0.78rem !important;
        font-weight: 700 !important;
        color: #4c1d95 !important;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        background-color: #f3f0ff !important;
        border-bottom: 2px solid #ddd6fe !important;
        padding: 8px 6px !important;
        vertical-align: middle;
    }

    #parameter_table td {
        padding: 0 !important;
        font-weight: 600;
        align-content: center;
        font-size: 1rem !important;
        border: 1px solid #ede9fe !important;
    }

    #parameter_table td span,
    #parameter_table td input {
        font-size: 1rem !important;
    }

    /* ── Totals footer ────────────────────────────────────── */
    .total-summary-row td {
        padding: 2px 0 !important;
        vertical-align: middle;
        border: 1px solid #cbd5e1 !important;
    }

    .val-box-large {
        border: none !important;
        background: transparent;
        font-weight: 800;
        color: #198754;
        font-size: 1.1rem;
        text-align: right;
    }

    /* ── Select2 (email modal CC) ─────────────────────────── */
    .select2-container--bootstrap-5 .select2-selection {
        height: calc(1.1em + 0.95rem + 2px) !important;
        border-radius: 0 !important;
        font-size: 0.8rem;
    }

    /* ── Placeholder rows shown before bill is loaded ─────── */
    .da-placeholder-row td {
        opacity: 0.38;
        pointer-events: none;
    }

</style>
@endsection


@section('content')
<div class="page-wrapper" style="max-width: 1700px">

    <form id="dairy_analysis_form" data-mode="save">
        <input type="hidden" name="uuid" id="uuid" value="{{ uuid() }}">
        @csrf

        {{-- ══════════════════════════════════════════════════
             TOP BAR — title · bill search · action buttons
        ══════════════════════════════════════════════════ --}}
        <div class="da-topbar card border-0 shadow-sm mb-0 d-print-none">
            <div class="card-body p-1 d-flex flex-wrap align-items-center gap-3 border-start border-4 border-dairy-analysis">

                {{-- Title --}}
                <div class="d-flex align-items-center gap-2 pe-3 border-end flex-shrink-0" style="width: 400px;">
                    <div class="bg-dairy-analysis-lt text-dairy-analysis rounded d-flex align-items-center justify-content-center"
                         style="width: 40px; height: 40px;">
                        <i class="fa-solid fa-flask fs-3"></i>
                    </div>
                    <div class="lh-sm d-flex flex-row gap-2">
                        <div class="fw-bolder text-dairy-analysis" style="font-size: 1.25rem;">
                            Dairy Analysis
                        </div>
                         <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-2 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            <i class="fa-solid fa-chart-line me-1"></i>Transaction
                        </span>
                    </div>
                </div>

                <div class="flex-grow-1"></div>

                {{-- Action buttons --}}
                <div class="d-flex align-items-center gap-2">
                    <button type="button" id="btn_print" data-record-id=""
                            class="btn btn-sm btn-outline-primary d-none fw-bold">
                        <i class="fa-solid fa-print me-1"></i> Print
                    </button>
                    <button type="button" id="btn_email"
                            class="btn btn-sm btn-outline btn-orange d-none fw-bold">
                        <i class="fa-solid fa-envelope me-1"></i> Send Mail
                    </button>
                    {{-- Edit Dairy Analysis Button --}}
                        @can('dairy_analysis.edit')
                        <a href="{{ route('dairy-analysis.edit') }}"
                            class="btn btn-outline-primary btn-sm fw-bold">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                        </a>
                        @endcan
                    <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>


        {{-- ══════════════════════════════════════════════════
             FLOW PIPELINE + FOOTER
        ══════════════════════════════════════════════════ --}}
        <div class="card shadow-sm border-0 rounded-0 mt-1">
        <div class="card-body d-flex flex-column gap-0 p-2 pt-3">

            {{-- ── STEP 1: Sales ─────────────────────────── --}}
            <div class="da-section da-section-sales shadow-sm">

                <div class="da-section-header">
                    <span class="da-step-num">1</span>
                    <i class="fa-solid fa-tags"></i> Sales Details
                </div>

                <div class="da-field-grid">
                {{-- Bill search --}}
                <div class="da-field-item" style="grid-column: span 1;">
                    <span class="da-field-lbl">Bill No.</span>
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" name="invoice_serial" id="invoice_serial"
                               class="form-control form-control-sm da-field-val"
                               style="max-width: 180px;"
                               placeholder="e.g. INV-001" autocomplete="off">
                        <button type="button" id="btn_find_sales"
                                class="btn btn-dairy-analysis btn-sm fw-bold px-3">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Find
                        </button>
                    </div>
                </div>
                    <div class="da-field-item">
                        <span class="da-field-lbl">Customer Name</span>
                        <span id="s_customer_name" class="da-field-val">--</span>
                    </div>
                    <div class="da-field-item">
                        <span class="da-field-lbl">City</span>
                        <span id="s_customer_city" class="da-field-val">--</span>
                    </div>
                    <div class="da-field-item">
                        <span class="da-field-lbl">Bill Date</span>
                        <span id="s_bill_date" class="da-field-val">--</span>
                    </div>
                    <div class="da-field-item">
                        <span class="da-field-lbl">GRN No.</span>
                        <span id="s_grn_no" class="da-field-val">--</span>
                    </div>
                    <div class="da-field-item">
                        <span class="da-field-lbl">Vehicle No.</span>
                        <span id="s_vehicle_no" class="da-field-val">--</span>
                    </div>
                </div>

                <div class="da-fin-strip">
                    <div class="da-stat">
                        <span class="da-stat-lbl">Gross Qty</span>
                        <span id="s_gross_qty" class="da-stat-val">0.000</span>
                    </div>
                    <div class="da-stat">
                        <span class="da-stat-lbl">Incl. Tax Rate</span>
                        <span id="s_inclusive_rate" class="da-stat-val">0.00</span>
                    </div>
                    <div class="da-stat">
                        <span class="da-stat-lbl">Bill Amount</span>
                        <span id="s_bill_amount" class="da-stat-val da-amt-val">0.00</span>
                    </div>
                </div>
            </div>


            {{-- ── Connector: Sales → Purchase ────────────── --}}
            <div class="da-connector">
                <div class="da-connector-line"></div>
                <div class="da-connector-chip">
                    <i class="fa-solid fa-arrow-down"></i>
                    <span>Purchase Bill</span>
                </div>
                <div class="da-connector-line"></div>
            </div>


            {{-- ── STEP 2: Purchase ───────────────────────── --}}
            <div class="da-section da-section-purchase shadow-sm">

                <div class="da-section-header">
                    <span class="da-step-num">2</span>
                    <i class="fa-solid fa-cart-shopping"></i> Purchase Details
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <span class="da-field-lbl" style="color: #166534;">File No.</span>
                        <span id="p_file_no" class="da-file-badge">00</span>
                    </div>
                </div>

                <div class="da-field-grid">
                    <div class="da-field-item">
                        <span class="da-field-lbl">Voucher / Ref No.</span>
                        <span id="p_reference_no" class="da-field-val">--</span>
                    </div>
                    <div class="da-field-item">
                        <span class="da-field-lbl">Supplier Name</span>
                        <span id="p_supplier_name" class="da-field-val">--</span>
                    </div>
                    <div class="da-field-item">
                        <span class="da-field-lbl">City</span>
                        <span id="p_supplier_city" class="da-field-val">--</span>
                    </div>
                    <div class="da-field-item">
                        <span class="da-field-lbl">Bill Date</span>
                        <span id="p_bill_date" class="da-field-val">--</span>
                    </div>
                    <div class="da-field-item">
                        <span class="da-field-lbl">GRN No.</span>
                        <span id="p_grn_no" class="da-field-val">--</span>
                    </div>
                    <div class="da-field-item">
                        <span class="da-field-lbl">Vehicle No.</span>
                        <span id="p_vehicle_no" class="da-field-val">--</span>
                    </div>
                    <div class="da-field-item">
                        <span class="da-field-lbl">Destination</span>
                        <span id="p_destination" class="da-field-val">--</span>
                    </div>
                    <div class="da-field-item">
                        <span class="da-field-lbl">Product</span>
                        <span id="p_product_name" class="da-field-val">--</span>
                    </div>
                    <div class="da-field-item">
                        <span class="da-field-lbl">Condition</span>
                        <span id="p_condition" class="da-field-val">--</span>
                    </div>
                </div>

                {{-- Per-PO line breakdown (qty × rate = amount) — JS populates when >1 detail --}}
                <div id="p_po_lines" class="d-none px-3 py-2"
                     style="border-top: 1px solid #bbf7d0; background: #f0fdf4; font-size: 0.82rem;">
                </div>

                <div class="da-fin-strip">
                    <div class="da-stat">
                        <span class="da-stat-lbl">Gross Qty</span>
                        <span id="p_gross_qty" class="da-stat-val">0.000</span>
                    </div>
                    <div class="da-stat">
                        <span class="da-stat-lbl">Incl. Rate(s)</span>
                        <span id="p_inclusive_rate" class="da-stat-val" style="font-size: 0.9rem;">0.00</span>
                    </div>
                    <div class="da-stat">
                        <span class="da-stat-lbl">Net Amount</span>
                        <span id="p_net_amount" class="da-stat-val da-amt-val">0.00</span>
                    </div>
                </div>
            </div>


            {{-- ── Connector: Purchase → Analysis ─────────── --}}
            <div class="da-connector">
                <div class="da-connector-line"></div>
                <div class="da-connector-chip">
                    <i class="fa-solid fa-arrow-down"></i>
                    <span>Parameter Analysis</span>
                </div>
                <div class="da-connector-line"></div>
            </div>


            {{-- ── STEP 3: Parameter Analysis Table ──────── --}}
            <div class="da-section da-section-analysis shadow-sm">

                <div class="da-section-header">
                    <span class="da-step-num">3</span>
                    <i class="fa-solid fa-list-check"></i> Parameter Analysis
                </div>

                <table class="table table-bordered m-0" id="parameter_table">
                    <thead>
                        <tr>
                            <th width="13%" class="text-start">Element</th>
                            <th width="9%" class="text-end">Guarantee</th>
                            <th width="10%" class="text-end">Actual</th>
                            <th width="8%" class="text-end">Diff%</th>
                            <th width="8%" class="text-end">Rebate Per%</th>
                            <th width="13%" class="text-end">Sales Rebate</th>
                            <th width="13%" class="text-end">Sales Premium</th>
                            <th width="13%" class="text-end">Purchase Rebate</th>
                            <th width="13%" class="text-end">Purchase Premium</th>
                        </tr>
                    </thead>
                    <tbody id="parameter_table_body">
                        @for ($i = 0; $i < 5; $i++)
                        <tr class="da-placeholder-row">
                            <td><span class="fw-bold p-2">--</span></td>
                            <td><span class="form-control border-0 bg-light text-end fw-bold">--</span></td>
                            <td><input type="number" class="form-control fw-bold text-end" disabled placeholder="0.0000"></td>
                            <td><span class="form-control border-0 bg-light text-end fw-bold">--</span></td>
                            <td><span class="form-control border-0 bg-light text-end fw-bold">--</span></td>
                            <td><span class="form-control border-0 bg-light text-end fw-bold">--</span></td>
                            <td><span class="form-control border-0 bg-light text-end fw-bold">--</span></td>
                            <td><span class="form-control border-0 bg-light text-end fw-bold">--</span></td>
                            <td><span class="form-control border-0 bg-light text-end fw-bold">--</span></td>
                        </tr>
                        @endfor
                    </tbody>
                    <tfoot id="parameter_table_foot" class="total-summary-row">
                        {{-- Totals and pay amount populated by JS --}}
                    </tfoot>
                </table>
            </div>

        </div>{{-- end card-body --}}

            {{-- Standard footer — matches other ERP create pages --}}
            <div class="card-footer bg-light border-top p-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted d-flex align-items-center gap-2">
                        <i class="fa-solid fa-circle-info text-primary me-1"></i>
                        Review all details before saving
                        <span class="text-secondary">|</span>
                        <span><span class="text-danger">*</span> Fields are required</span>
                    </small>
                    <div class="d-flex gap-2">
                        <button type="button"
                                class="btn btn-outline-secondary waves-effect non-selectable"
                                onclick="location.reload()">
                            <i class="fa-solid fa-rotate-left me-1"></i> Clear
                        </button>
                        <button type="submit" id="btn_submit"
                                class="btn btn-dairy-analysis px-4 form-save-btn waves-effect"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="Save (Alt + S)">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save
                        </button>
                    </div>
                </div>
            </div>

        </div>{{-- end card --}}

    </form>
</div>

@include('company.pages.dairy-analysis._dairy-analysis-email-modal')
@include('company.partials._shortcuts-bar')

@endsection


@section('script')
<script>
    const getSalesInvUrl    = "{{ route('dairy-analysis.get_sales_inv') }}";
    const storeAnalysisUrl  = "{{ route('dairy-analysis.store') }}";
    const updateAnalysisUrl = "{{ route('dairy-analysis.update', ':id') }}";
    const editAnalysisUrl   = "{{ route('dairy-analysis.edit') }}";
    const printAnalysisUrl  = "{{ route('dairy-analysis.print-analysis-report', ':id') }}";
    const emailPreviewUrl   = "{{ route('mail.preview_dairy_analysis_email') }}";
    const emailSendUrl      = "{{ route('mail.send_dairy_analysis_email') }}";
    const hugertePath       = "{{ asset('js/libs/hugerte') }}";
</script>
<script src="{{ asset('js/libs/hugerte/hugerte.min.js') }}"></script>
<script src="{{ asset('js/modules/dairy-analysis/pds-email-modal-2.js') }}?v={{ hash_file('md5', public_path('js/modules/dairy-analysis/pds-email-modal-2.js')) }}"></script>
<script src="{{ asset('js/modules/dairy-analysis/create.js') }}?v={{ hash_file('md5', public_path('js/modules/dairy-analysis/create.js')) }}"></script>
@endsection
