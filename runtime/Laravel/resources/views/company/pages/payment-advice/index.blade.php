@extends('company.layout.app')
@section('title', 'Payment Online / RTGS')

@section('css')
<link rel="stylesheet"
    href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
<link rel="stylesheet"
    href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
<style>
    #rtgs_table_wrapper {
        min-height: 340px;
    }

    .bottom-bar {
        background: var(--tblr-bg-surface);
        border-top: 1px solid var(--tblr-border-color);
    }

    .bottom-bar .form-label {
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 2px;
    }

    .bottom-bar .form-control,
    .bottom-bar .form-select {
        font-size: 0.8rem;
    }

    .total-display {
        font-size: 1rem;
        font-weight: 700;
        color: var(--tblr-primary);
    }

    .status-badge-not-paid {
        background: #d63939;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 4px;
        letter-spacing: 0.5px;
    }

    .status-badge-paid {
        background: #2fb344;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 4px;
        letter-spacing: 0.5px;
    }

    #rtgs_table th,
    #rtgs_table td {
        white-space: nowrap;
        padding: 5px 10px;
        vertical-align: middle;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 1rem;
    }
</style>
@endsection

@section('content')
@include('company.pages.payment-online-rtgs._rtgs-email-modal')
@include('company.pages.payment-online-rtgs._payment-advice-email-modal')
@include('company.pages.payment-online-rtgs._payment-advice-bulk-modal')
<div class="page-wrapper">

    {{-- Page Header --}}
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-building-columns"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Payment Advice
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-red-lt fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-money-check"></i> ADVICE
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn waves-effect">
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
            <form action="" onsubmit="return false;">
                {{-- Filter Bar --}}
                <div class="card mb-2">
                    <div class="card-body p-2">
                        <div class="row g-2 align-items-end">

                            <div class="col-md-2 col-lg-auto">
                                <label class="form-label required">Date</label>
                                <input type="text" id="rtgs_date" class="form-control" value=""
                                    placeholder="DD-MM-YYYY">
                            </div>

                            <div class="col-md-4 col-lg-3">
                                <label class="form-label required">Bank Name</label>
                                <select id="bank_id" class="form-select select2" style="min-width:180px;">
                                    <option value="">--Select Bank--</option>
                                    @isset($banks)
                                    @foreach ($banks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                    @endforeach
                                    @endisset
                                </select>
                            </div>

                            <div class="col-md-2 col-lg-auto">
                                <label class="form-label">Cheque No</label>
                                <input type="number" id="cheque_number" class="form-control" placeholder="Enter Chq. No"
                                    style="min-width:150px;">
                            </div>

                            <div class="col-lg-auto d-flex align-items-end">
                                <button id="rtgs_get_chq_btn" type="button" class="btn btn-primary waves-effect">
                                    <i class="fa-solid fa-database me-1"></i> Get Chq. Data
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            </form>
            {{-- Data Table --}}
            <div class="card mb-0 rounded-0">
                <div class="card-body p-0">
                    <div id="rtgs_table_wrapper"style="overflow-x:auto; overflow-y:auto; max-height:420px; position:relative;">

                        {{-- Loader overlay --}}
                        <div id="rtgs_loader" style="display:none; position:absolute; inset:0; background:rgba(255,255,255,0.75); z-index:10;">
                            <div class="d-flex justify-content-center align-items-center h-100" style="min-height:400px;">
                                <div class="spinner-border text-primary me-3" role="status">
                                    <span class="visually-hidden">Loading…</span>
                                </div>
                                <span class="fw-semibold text-muted fs-4">Loading RTGS data…</span>
                            </div>
                        </div>

                        <table id="rtgs_table" class="table table-sm table-bordered table-hover mb-0 fs-5">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width:50px;">
                                        {{-- <input type="checkbox" id="chk_all_head" class="form-check-input mt-0"> --}}
                                        #
                                    </th>
                                    <th style="min-width:70px;">Sr.No</th>
                                    <th style="min-width:100px;">Ref No</th>
                                    <th style="min-width:70px;">File No</th>
                                    <th style="min-width:500px;">Particular</th>
                                    <th style="min-width:100px;">City</th>
                                    <th class="text-end" style="min-width:150px;">Amount</th>
                                    <th style="min-width:120px;">Cheq. No</th>
                                    <th style="min-width:90px;">Payment Date</th>
                                    <th style="min-width:120px;">Bank A/c No</th>
                                    <th style="min-width:120px;">Bank Name</th>
                                    <th style="min-width:120px;">IFSC</th>
                                    <th style="min-width:120px;">Bank Branch</th>
                                    <th style="min-width:120px;">Email</th>
                                    <th style="min-width:110px;">Mobile</th>
                                </tr>
                            </thead>
                            <tbody id="rtgs_table_body">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Bottom Bar --}}
            <div class="card rounded-0 bottom-bar">
                <div class="card-body p-2">
                    <div class="d-flex align-items-start gap-3 flex-wrap">

                        {{-- ── Content Section (stays together as one flex item) ── --}}
                        <div class="d-flex align-items-start gap-3">

                            {{-- Group 1: Checkboxes + Reference --}}
                            <div class="d-flex flex-column gap-1">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="checkbox" id="chk_all" class="form-check-input mt-0">
                                    <label for="chk_all" class="form-label mb-0">All</label>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <input type="text" id="rtgs_ref_no" class="form-control form-control-sm"
                                        placeholder="Reference No" style="min-width:100px;">
                                    {{-- <button id="rtgs_find_btn" type="button"
                                        class="btn btn-sm btn-secondary waves-effect">Find</button> --}}
                                </div>
                            </div>

                            <div class="vr align-self-stretch"></div>

                            {{-- Group 2: Totals --}}
                            <div class="d-flex flex-column gap-1">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="form-label mb-0" style="min-width:140px;">Total</span>
                                    <input type="text" id="rtgs_total"
                                        class="form-control form-control-sm total-display text-end" value="0.00"
                                        readonly style="min-width:130px; background:var(--tblr-light);">
                                </div>
                            </div>


                        </div>
                        {{-- ── End Content Section ── --}}

                        {{-- ── Button Section (drops below content as a whole unit on wrap) ── --}}
                        <div class="d-flex align-items-start gap-2 flex-wrap border-start ps-3">

                            {{-- Col 1 --}}
                            <div class="d-flex flex-column gap-1">
                                <button id="rtgs_cheque_print_btn" type="button"
                                    class="btn btn-sm btn-primary waves-effect">
                                    <i class="fa-solid fa-print me-1"></i> Cheque-Print
                                </button>
                                <button id="rtgs_send_bank_email_btn" type="button"
                                    class="btn btn-sm btn-primary waves-effect">
                                    <i class="fa-solid fa-landmark me-1"></i> Send-to-Bank-Email
                                </button>
                            </div>

                            {{-- Col 2 --}}
                            <div class="d-flex flex-column gap-1">
                                <button id="rtgs_register_print_btn" type="button"
                                    class="btn btn-sm btn-primary waves-effect">
                                    <i class="fa-solid fa-print me-1"></i> Register-Print
                                </button>
                                <button id="rtgs_payment_advice_all_btn" type="button"
                                    class="btn btn-sm btn-primary waves-effect">
                                    <i class="fa-solid fa-list me-1"></i> Payment-Advice-All
                                </button>
                            </div>

                            {{-- Col 3 --}}
                            <div class="d-flex flex-column gap-1">
                                <button id="rtgs_print_btn" type="button"
                                    class="btn btn-sm btn-primary waves-effect">
                                    <i class="fa-solid fa-print me-1"></i> RTGS-Print
                                </button>
                                <button id="rtgs_payment_advice_email_btn" type="button"
                                    class="btn btn-sm btn-primary waves-effect">
                                    <i class="fa-regular fa-envelope me-1"></i> Payment-Advice-Email
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection


@section('script')
<script>
    const rtgsIndexUrl            = "{{ route('payment-advice.index') }}";
    const rtgsPendingListUrl      = "{{ route('payment-advice.fetch_pending') }}";
    const rtgsChequePrintUrl      = "{{ route('payment-advice.cheque_print') }}";
    const rtgsRegisterPrintUrl    = "{{ route('payment-advice.register_print') }}";
    const rtgsPrintUrl            = "{{ route('payment-advice.rtgs.print') }}";
    const rtgsFetchByChequeUrl    = "{{ route('payment-advice.fetch_by_cheque') }}";
    const rtgsSendToBankUrl         = "{{ route('mail.rtgs_send_to_bank') }}";
    const rtgsEmailPreviewUrl       = "{{ route('mail.rtgs_email_preview') }}";
    const rtgsPaymentAdvicePrintUrl = "{{ route('payment-advice.payment_advice_print') }}";
    const paEmailPreviewUrl         = "{{ route('mail.payment_advice_email_preview') }}";
    const paEmailSendUrl            = "{{ route('mail.payment_advice_email_send') }}";
    const paEmailBulkSendUrl        = "{{ route('mail.payment_advice_email_bulk_send') }}";
    const hugertePath               = "{{ asset('js/libs/hugerte') }}";
</script>
{{-- hugerte.init() is deferred to shown.bs.modal in index.js
     so the textarea is VISIBLE when the editor initialises.
     Calling init on a hidden element inside a Bootstrap modal
     triggers "document is not in standards mode" in HugeRTE. --}}
<script src="{{ asset('js/libs/hugerte/hugerte.min.js') }}"></script>
<script
    src="{{ asset('js/modules/payment-advice/index.js') }}?v={{ hash_file('md5', public_path('js/modules/payment-advice/index.js')) }}">
</script>
@endsection