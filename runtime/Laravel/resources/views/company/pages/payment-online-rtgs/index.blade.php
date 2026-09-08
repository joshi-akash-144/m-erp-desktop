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
                                Payment Online / RTGS
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-red-lt fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-money-check"></i> RTGS
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                        @can('payment_payable.list')
                        <a href="{{ route('payments.payable.create') }}" class="btn btn-outline-info btn-sm fw-bold">
                            <i class="fas fa-file-invoice me-1"></i>
                            Payment Payable
                        </a>
                        @endcan

                        <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn waves-effect">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
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
                                <i class="fa-solid fa-building-columns me-2 text-primary"></i>
                                Payment Online / RTGS
                            </h3>
                            <span class="ribbon ribbon-bookmark bg-blue">
                                <i class="fa-solid fa-eye me-1"></i> RTGS REGISTER
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                    <div class="card border-0 border-end border-4 border-primary">
                        <div class="card-body shadow-sm p-2">
                            <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                <div class="waves-effect d-flex gap-2">
                                    @can('payment_payable.create')
                                    <a href="{{ route('payments.payable.create') }}" class="btn btn-outline-info btn-sm fw-bold">
                                        <i class="fas fa-file-invoice me-1"></i>
                                        Payment Payable
                                    </a>
                                    @endcan

                                    <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn waves-effect">
                                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>  --}}

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
                                <label class="form-label">File No</label>
                                <input type="text" id="file_number" class="form-control" placeholder="Enter File-No."
                                    style="min-width:110px;">
                            </div>

                            <div class="col-lg-auto d-flex align-items-end">
                                <button id="rtgs_show_btn" type="button" class="btn btn-primary waves-effect">
                                    <i class="fa-solid fa-magnifying-glass me-1"></i> Show
                                </button>
                            </div>

                            <div class="col-md-2 col-lg-auto">
                                <label class="form-label">Cheque No</label>
                                <input type="text" id="cheque_number" class="form-control" placeholder="Enter Chq. No"
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
                    <div id="rtgs_table_wrapper" style="overflow-x:auto; overflow-y:auto; max-height:420px; position:relative;">

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
                    <div class="d-flex align-items-start gap-2 flex-nowrap">

                        {{-- ── Content Section (stays together as one flex item) ── --}}
                        <div class="d-flex align-items-start gap-1">

                            {{-- Group 1: Checkboxes + Reference + A/c Payee --}}
                            <div class="d-flex flex-column gap-1">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="checkbox" id="chk_all" class="form-check-input mt-0">
                                    <label for="chk_all" class="form-label mb-0">All</label>
                                </div>
                                {{-- <div class="d-flex align-items-center gap-2">
                                    <input type="checkbox" id="chk_empty_chq" class="form-check-input mt-0">
                                    <label for="chk_empty_chq" class="form-label mb-0">Empty Cheque No.</label>
                                </div> --}}
                                <div class="d-flex align-items-center gap-1">
                                    <input type="text" id="rtgs_ref_no" class="form-control form-control-sm"
                                        placeholder="Reference No" style="min-width:30px;">
                                    {{-- <button id="rtgs_find_btn" type="button"
                                        class="btn btn-sm btn-secondary waves-effect">Find</button> --}}
                                </div>
                                <div class="d-flex align-items-center gap-5">
                                    <label class="form-label mb-0">A/c Payee</label>
                                    <select id="rtgs_ac_payee" class="form-select form-select-sm" style="min-width:50px;">
                                        <option value="yes">Yes</option>
                                        <option value="no">No</option>
                                    </select>
                                </div>
                            </div>

                            <div class="vr align-self-stretch"></div>

                            {{-- Group 2: Totals + Payment Status --}}
                            <div class="d-flex flex-column gap-1">
                                <div class="d-flex align-items-center gap-1">
                                    <span class="form-label mb-0" style="min-width:140px;">Total</span>
                                    <input type="text" id="rtgs_total"
                                        class="form-control form-control-sm total-display text-end" value="0.00"
                                        readonly style="min-width:130px; background:var(--tblr-light);">
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="form-label mb-0" style="min-width:140px;">Payment Payable Amt.</span>
                                    <input type="text" id="rtgs_payable_amt"
                                        class="form-control form-control-sm total-display text-end text-danger fw-bold" value="{{ formatIndianNumber(session('rtgs_payable_amt_' . company_id(), 0), 2, '.', '') }}"
                                        readonly style="min-width:130px; background:var(--tblr-light);">
                                </div>
                                <div class="mt-3 mx-auto">
                                    @can('payment_voucher.delete')
                                   <button type="button" class="btn btn-danger waves-effect btn-sm" id="btn_entry_reverse">
                                        <i class="fa-solid fa-trash-can me-1"></i> Not Paid
                                    </button>
                                    @endcan
                                </div>
                            </div>

                            <div class="vr align-self-stretch"></div>

                            {{-- Group 3: Cheque Name & RTGS No --}}
                            <div class="d-flex flex-column gap-1 justify-content-center">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="form-label mb-0" style="min-width:90px;">Chq Name</span>
                                    <input type="text" id="rtgs_chq_name" class="form-control form-control-sm"
                                        value=", FOR RTGS" style="min-width:220px;">
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="form-label mb-0" style="min-width:90px;">Cheque Alpha No</span>
                                    <input type="text" id="rtgs_chq_alpha_number" class="form-control form-control-sm"
                                        placeholder="Enter Chq Alpha No" style="min-width:220px;">
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="form-label mb-0" style="min-width:90px;">RTGS / Chq. No.</span>
                                    <input type="text" id="rtgs_chq_number" class="form-control form-control-sm"
                                        placeholder="Enter Chq No" style="min-width:220px;">
                                </div>
                            </div>

                        </div>
                        {{-- ── End Content Section ── --}}

                        {{-- ── Button Section (drops below content as a whole unit on wrap) ── --}}
                        <div class="d-flex align-items-start gap-2 flex-wrap border-start ps-3">

                            {{-- Col 1 --}}
                            <div class="d-flex flex-column gap-1">
                                <button id="rtgs_save_update_btn" type="button"
                                    class="btn btn-sm btn-primary waves-effect">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> Save-Update Chq No.
                                </button>
                                <button id="rtgs_send_bank_email_btn" type="button"
                                    class="btn btn-sm btn-primary waves-effect">
                                    <i class="fa-solid fa-landmark me-1"></i> Send-to-Bank-Email
                                </button>
                            </div>

                            {{-- Col 2 --}}
                            <div class="d-flex flex-column gap-1">
                                <button id="rtgs_cheque_print_btn" type="button"
                                    class="btn btn-sm btn-primary waves-effect">
                                    <i class="fa-solid fa-print me-1"></i> Cheque-Print
                                </button>
                                <button id="rtgs_payment_advice_all_btn" type="button"
                                    class="btn btn-sm btn-primary waves-effect">
                                    <i class="fa-solid fa-list me-1"></i> Payment-Advice-All
                                </button>
                            </div>

                            {{-- Col 3 --}}
                            <div class="d-flex flex-column gap-1">
                                <button id="rtgs_register_print_btn" type="button"
                                    class="btn btn-sm btn-primary waves-effect">
                                    <i class="fa-solid fa-print me-1"></i> Register-Print
                                </button>
                                <button id="rtgs_payment_advice_email_btn" type="button"
                                    class="btn btn-sm btn-primary waves-effect">
                                    <i class="fa-regular fa-envelope me-1"></i> Payment-Advice-Email
                                </button>
                            </div>
                            
                            {{-- Col 4 --}}
                            {{-- <div class="d-flex flex-column gap-1">
                                <button id="rtgs_print_btn" type="button"
                                    class="btn btn-sm btn-primary waves-effect">
                                    <i class="fa-solid fa-print me-1"></i> RTGS-Print
                                </button>
                            </div> --}}
                        </div>
                    
                        {{-- ── End Button Section ── --}}

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection


@section('script')
<script>
    const rtgsIndexUrl            = "{{ route('payment-online-rtgs.index') }}";
    const rtgsPendingListUrl      = "{{ route('payment-online-rtgs.fetch_pending') }}";
    const rtgsSaveUpdateChequeUrl = "{{ route('payment-online-rtgs.save_update_cheque_no') }}";
    const rtgsChequePrintUrl      = "{{ route('payment-online-rtgs.cheque_print') }}";
    const rtgsRegisterPrintUrl    = "{{ route('payment-online-rtgs.register_print') }}";
    const rtgsPrintUrl            = "{{ route('payment-online-rtgs.rtgs.print') }}";
    const rtgsFetchByChequeUrl    = "{{ route('payment-online-rtgs.fetch_by_cheque') }}";
    const rtgsSendToBankUrl         = "{{ route('mail.rtgs_send_to_bank') }}";
    const rtgsEmailPreviewUrl       = "{{ route('mail.rtgs_email_preview') }}";
    const rtgsPaymentAdvicePrintUrl = "{{ route('payment-online-rtgs.payment_advice_print') }}";
    const rtgsDeleteSelectedUrl     = "{{ route('payment-online-rtgs.delete_selected') }}";
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
    src="{{ asset('js/modules/payment-online-rtgs/index.js') }}?v={{ hash_file('md5', public_path('js/modules/payment-online-rtgs/index.js')) }}">
</script>
@endsection