@extends('company.layout.app')

@section('title', 'Payment Payable – List')
@section('css')
<link rel="stylesheet"
    href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
</link>
{{-- <link rel="stylesheet"
    href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
</link> --}}
<style>
    table tr td {
        padding: 3px 5px !important;
        border: #dadada solid 1px !important;
        font-size: 0.970rem !important;
        font-family: var(--tblr-body-font-family) !important;
    }

    table tr td input[type="text"],
    table tr td input[type="number"],
    table tr td input[type="text"]:focus,
    table tr td input[type="number"]:focus,
    table tr td input[type="text"]:focus-visible,
    table tr td input[type="number"]:focus-visible {
        padding: 0.1rem 0.5rem !important;
        margin: 0 !important;
        0 box-shadow: none !important;
        outline: none !important;
    }

    table tr td input[type="checkbox"] {
        width: 18px !important;
        height: 18px !important;
    }

    .table-wrapper {
        max-height: 520px;
        /* fixed height */
        overflow-y: auto;
        overflow-x: auto;
        /* needed for wide table */
    }

    /* Sticky header */
    #payment_payable_table thead th {
        position: sticky;
        top: 0;
        /* must be set */
        z-index: 10;
        border-bottom: 2px solid #dee2e6;
        box-shadow: inset 0 2px 0 #dee2e6;
    }

    /* Optional polish */
    #payment_payable_table td,
    #payment_payable_table th {
        white-space: nowrap;
        vertical-align: middle;
    }

    .active-row {
        background-color: #e1ebf9 !important;
        /* light blue */
    }

    .over-days {
        background-color: #f9f9f9 !important;
    }

    .rebate-from-analysis {
        background-color: #0DCAF080 !important;
    }

    .over-payment-rebate {
        background-color: #DC354580 !important;
        font-size: 16px !important;
        color: black !important;
        font-weight: bold !important;
    }

    .legend-chip {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 20px;
        background: #f8f9fa;
        font-size: 13px;
    }

    .legend-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
    }

    /* optional: indicate row vs cell */
    .legend-type {
        font-size: 10px;
        color: #555;
        background: #eee;
        padding: 1px 4px;
        border-radius: 4px;
    }

    .no-border,
    .no-border td,
    .no-border tr {
        border: 0 !important;
    }

    #create_payment_voucher {
        transition: all 0.1s ease-in-out;
    }

    #create_payment_voucher:focus {
        outline: none;
        border: 1px solid #0360ec;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.5);
    }

    .summary-highlight {
        background-color: #ffeeba !important;
        transition: background-color 0.3s ease;
    }

    #summary_table thead {
        position: sticky;
        top: 0;
        /* stick to top of the container */
        z-index: 10;
        background-color: #f8f9fa;
        /* same as table-light */
    }

    .change-pending-amount {
        background-color: #0D6EFD40 !important;
        transition: background-color 0.3s ease;
        color: black !important;
    }

    #payment_payable_table_body tr.nav-hover td {
        background-color: rgb(221, 221, 221);
    }

    table tr td {
        color: black !important;
    }

    input[readonly] {
        background-color: #e9ecef !important;
        cursor: not-allowed;
    }

    /* Contenteditable cell styling */
    #payment_payable_table td[contenteditable] {
        padding: 0.1rem 0.4rem !important;
        outline: none !important;
        min-height: 1em;
    }

    #payment_payable_table td[contenteditable="true"] {
        cursor: text;
    }

    #payment_payable_table td[contenteditable="true"]:focus {
        background-color: #fff;
        outline: none !important;
        box-shadow: inset 0 0 0 2px #86b7fe, 0 0 0 0.2rem rgba(13, 110, 253, 0.2) !important;
    }

    #payment_payable_table td[contenteditable="false"] {
        background-color: #e9ecef !important;
        cursor: not-allowed;
    }
</style>
@endsection

@section('content')
<div class="page-wrapper">
    <!-- Page Header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-file-invoice"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Payment Payable
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-red-lt fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-chart-line"></i> Bank Transaction
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                        @can('payment_online_rtgs.list')
                        <a href="{{ route('payment-online-rtgs.index') }}" class="btn btn-outline-info btn-sm fw-bold">
                            <i class="fas fa-building-columns me-1"></i>
                            Online RTGS
                        </a>
                        @endcan
                        @can('payment_approval.list')
                        <a href="{{ route('payments.approved.index') }}" class="btn btn-outline-success btn-sm fw-bold">
                            <i class="fas fa-check-circle me-1"></i>
                            Payment Approval
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
    {{-- <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center justify-content-between">

                <!-- 🔹 Title & Subtitle -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card border-0 border-start border-4 border-primary">
                        <div class="card-body shadow p-2">
                            <h3 class="page-title font-monospace">
                                <i class="fa-solid fa-file-invoice me-2 text-primary"></i>
                                Payment Payable
                            </h3>
                            <span class="ribbon ribbon-bookmark bg-blue">
                                <i class="fa-solid fa-eye me-1"></i> LIST DATA
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 🔹 Action Buttons (Export & Add) -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                    <div class="card border-0 border-end border-4 border-primary">
                        <div class="card-body shadow-sm p-2">
                            <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">

                                <div class="waves-effect d-flex gap-2">
                                    <a href="{{ route('payment-online-rtgs.index') }}" class="btn btn-outline-info btn-sm fw-bold">
                                        <i class="fas fa-building-columns me-1"></i>
                                        Online RTGS
                                    </a>

                                    <a href="{{ route('payments.approved.index') }}" class="btn btn-outline-success btn-sm fw-bold">
                                        <i class="fas fa-check-circle me-1"></i>
                                        Payment Approval
                                    </a>

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
    </div> --}}
    <form action="" id="payment_payable_form" autocomplete="off">
        <input type="hidden" id="unique_request_id">
        <div class="page-body">
            <div class="container-xl">
                <div class="card">
                    <!-- 🔹 Filters Section -->
                    <div class="p-3">

                        <div class="row g-2">
                            <!-- Voucher No -->
                            <div class="col-md-4 col-lg-1">
                                <label for="" class="fs-4 fw-bold">Voucher No</label>
                                <input type="text" id="voucher_number" value="{{ $paymentVoucherSerial }}" class="form-control fw-bold bg-yellow-lt text-danger" disabled>
                            </div>
                            <!-- Account Name -->
                            <div class="col-md-4 col-lg-3">
                                <label for="account_id" class="fs-4 fw-bold">Account Name</label>
                                <select id="account_id" name="account_id" class="form-select">
                                    <option value="">Select Account</option>
                                    @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }} @if($account->city) ({{ $account->city }}) @endif </option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- File Number -->
                            <div class="col-md-4 col-lg-1">
                                <label for="file_number" class="fs-4 fw-bold">File No.</label>
                                <input type="text" name="file_number" id="file_number" class="form-control" focus>
                            </div>
                            <!-- Date -->
                            <div class="col-md-4 col-lg-1">
                                <label for="payment_date" class="fs-4 fw-bold">Payment Date</label>
                                <input type="text" id="payment_date" name="payment_date"
                                    class="form-control text-danger fw-bold date-format" placeholder="DD-MM-YYYY">
                            </div>
                            <div class="col-md-4 col-lg-1">
                                <label for="day" class="fs-4 fw-bold">Day</label>
                                <input type="text" id="day" class="form-control bg-white fw-bold" disabled>
                            </div>

                            <!-- Filter Buttons -->
                            <div class="col-md-4 col-lg-1 d-flex align-items-end gap-2">
                                <button id="filter_apply" type="submit" class="btn btn-primary flex-fill waves-effect">
                                    @include('icons.filter', ['size' => 20])
                                    Show
                                </button>
                            </div>

                            <div class="col-md-4 col-lg-2">
                                <label for="filter_by" class="fs-4 fw-bold">Filter By</label>
                                <select name="filter_by" id="filter_by"
                                    class="form-select custom-select2 non-selectable">
                                    <option value="cr"> Cr </option>
                                    <option value="dr">With Advance (Dr)</option>
                                    <option value="partial">With 20% (Partial)</option>
                                    <option value="all">All Data</option>
                                </select>
                            </div>
                        </div>

                    </div>

                    <div class="card-body pt-0">
                        <div class="d-flex flex-wrap gap-2 mb-2">

                            <!-- Row highlight -->
                            <div class="legend-chip fw-bold">
                                <span class="legend-dot bg-warning bg-opacity-50"></span> Over Days
                                <span class="legend-type">Cell</span>
                            </div>

                            <!-- Row highlight -->
                            <div class="legend-chip fw-bold">
                                <span class="legend-dot bg-secondary bg-opacity-50"></span> Partially Paid
                                <span class="legend-type">Cell</span>
                            </div>

                            <!-- Cell highlight -->
                            <div class="legend-chip fw-bold">
                                <span class="legend-dot bg-info bg-opacity-50"></span> Dairy Rebate
                                <span class="legend-type">Cell</span>
                            </div>

                            <!-- Cell highlight -->
                            <div class="legend-chip fw-bold">
                                <span class="legend-dot bg-danger"></span> Rebate Bigger Than Pay Amount
                                <span class="legend-type">Cell</span>
                            </div>


                            <!-- Cell highlight -->
                            <div class="legend-chip fw-bold">
                                <span class="legend-dot bg-primary bg-opacity-50"></span> Pay Amount Change
                                <span class="legend-type">Cell</span>
                            </div>

                            <!-- Row highlight -->
                            <div class="legend-chip fw-bold">
                                <span class="legend-dot bg-danger bg-opacity-50"></span> On Account
                                <span class="legend-type">Row</span>
                            </div>

                            <!-- Row highlight -->
                            <div class="legend-chip fw-bold">
                                <span class="legend-dot bg-success"></span> Old Bill
                                <span class="legend-type">Row</span>
                            </div>

                        </div>
                        <input type="hidden" name="uuid" id="uuid" value="">

                        <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">

                            <table class="table table-hover table-bordered mb-0" id="payment_payable_table"
                                style="width:100%;">
                                <colgroup>
                                    <col style="width:60px"> <!-- Yes/No -->
                                    <col style="width:60px"> <!-- File No -->
                                    <col style="width:100px"> <!-- Bill No -->
                                    <col style="width:105px"> <!-- Date -->
                                    <col style="width:105px"> <!-- ShowDate -->
                                    <col style="width:120px"><!-- Pay Amt -->
                                    <col style="width:80px"> <!-- Percentage -->
                                    <col style="width:80px"> <!-- CD -->
                                    <col style="width:60px"> <!-- Days -->
                                    <col style="width:120px"> <!-- Balance -->
                                    <col style="width:350px"> <!-- Particular -->
                                    <col style="width:140px"> <!-- City -->
                                    <col style="width:100px"> <!-- Rebate -->
                                    <col style="width:160px"> <!-- Destination -->
                                    <col style="width:220px"><!-- Product -->
                                    <col style="width:100px"> <!-- Qty -->
                                    <col style="width:100px"> <!-- Rate -->
                                    <col style="width:120px"> <!-- Bill Amt -->
                                    <col style="width:110px"><!-- Gross Qty -->
                                    <col style="width:110px"><!-- TDS Amt -->
                                    <col style="width:110px"> <!-- Freight -->
                                    <col style="width:90px"> <!-- SGST -->
                                    <col style="width:90px"> <!-- CGST -->
                                    <col style="width:90px"> <!-- IGST -->
                                    {{-- <col style="width:110px"> <!-- Premium --> --}}
                                    <col style="width:110px"> <!-- Labour -->
                                    <col style="width:110px"> <!-- Penalty -->
                                    <col style="width:90px"> <!-- Round -->
                                    <col style="width:110px"> <!-- Other -->
                                    <col style="width:110px"> <!-- Dr/Cr -->
                                    <col style="width:150px"> <!-- Source Type -->
                                </colgroup>

                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center">Yes/No</th>
                                        <th>File</th>
                                        <th class="text-center">Bill No</th>
                                        <th class="text-center">Date</th>
                                        <th class="text-center">ShowDate</th>
                                        <th class="text-end">Pay Amt.</th>
                                        <th class="text-center">CD (%)</th>
                                        <th class="text-center">CD(₹)</th>
                                        <th class="text-center">Days</th>
                                        <th class="text-end">Balance</th>
                                        <th>Particular</th>
                                        <th>City</th>
                                        <th>Rebate</th>
                                        <th>Destination</th>
                                        <th>Product</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-center">Rate</th>
                                        <th class="text-end">Bill.Amt</th>
                                        <th class="text-end">Gross Qty.</th>
                                        <th class="text-end">Tds Amt.</th>
                                        <th class="text-end">Freight</th>
                                        <th class="text-end">SGST</th>
                                        <th class="text-end">CGST</th>
                                        <th class="text-end">IGST</th>
                                        {{-- <th class="text-end">Premium</th> --}}
                                        <th class="text-end">Labour</th>
                                        <th class="text-end">Penalty</th>
                                        <th class="text-end">Round</th>
                                        <th class="text-end">Other</th>
                                        <th class="text-center">Dr/Cr</th>
                                        <th class="text-center">Source Type</th>
                                    </tr>
                                </thead>
                                <tbody id="payment_payable_table_body">
                                    @for ($i = 0; $i <= 10; $i++) <tr class="bg-table">
                                        @for ($k = 0; $k < 30; $k++) <td class="border-0"> &nbsp;</td>
                                            @endfor
                                            </tr>
                                            @endfor
                                </tbody>
                            </table>
                        </div>
    </form>
    <div class="container-fluid py-2">
        <div class="row g-3">
            <div class="col-lg-6 card shadow-sm p-2">
                <div class="mb-1">
                    <table class="no-border w-100">
                        <tr>
                            <td style="width: 2%;">
                                <input type="checkbox" id="all_check" class="non-selectable"
                                    style="width: 20px !important; height: 20px !important">
                            </td>
                            <td style="width: 6%;">
                                <label for="all_check"
                                    class="form-check-label  fw-bold legend-chip fw-bold fs-4">All</label>
                            </td>
                            <td style="width: 1%;">|</td>
                            <td style="width: 5%;">
                                <label class="fw-bold mb-0 legend-chip  fw-bold fs-4"
                                    for="payment_total">Total</label>
                            </td>
                            <td style="width: 40%;">
                                <input type="text" id="payment_total" data-value="0.00"
                                    class="form-control fw-bold text-end text-primary cursor-not-allowed border border-1 border-dark bg-primary bg-opacity-10"
                                    style="font-size: 1.2rem !important; padding: 8px !important;"
                                    value="0.00" readonly>
                            </td>
                            <td style="width: 17%;">
                                <label class="mb-0 legend-chip fw-bold fs-4  text-center"
                                    for="payment_total">Bank Name</label>
                            </td>
                            <td style="width: 42%;">
                                <select name="bank_id" id="bank_id" class="form-select w-100">
                                    <option value="">Select Bank</option>
                                    @foreach ($banks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="mb-1">
                    <table class="no-border w-100">
                        <tr>
                            <td style="width: 15%;">
                                <label class="mb-0 legend-chip fw-bold fs-4 " for="payment_total">Find
                                    Bill No</label>
                            </td>
                            <td style="width: 25%;">
                                <input type="text" id="find_bill_no" data-value="0.00"
                                    autocomplete="off"
                                    class="form-control fw-bold text-end non-selectable border border-1 border-secondary-subtle"
                                    style="font-size: 1rem !important; padding: 5px !important;"
                                    placeholder="Enter Bill No">
                            </td>
                            <td style="width: 19%;">
                                <label class="mb-0 legend-chip fw-bold fs-4 "
                                    for="ledger_balance">Ledger Balance</label>
                            </td>

                            <td style="width: 30%;" class="text-end">
                                <input type="text" id="ledger_balance" data-value="0.00"
                                    class="form-control fw-bold text-end  cursor-not-allowed bg-info bg-opacity-10  border border-1 border-secondary-subtle text-secondary"
                                    style="font-size: 1rem !important; padding: 8px !important;"
                                    value="0.00 Dr" disabled>
                            </td>
                            <td style="width: 20%"></td>
                        </tr>
                    </table>
                </div>
                <div class="mb-1">
                    <table class="no-border w-100" id="extra_ledger_add_row">
                        <tr>
                            <td style="width: 10%;">
                                <select name="ledger_transaction_type" id="ledger_transaction_type"
                                    class="form-select custom-select2">
                                    {{-- <option value="dr">Dr</option> If Open than change calculation of voucher   --}}
                                    <option value="cr">Cr</option>
                                </select>
                            </td>
                            <td style="width: 30%;">
                                <select name="ledger_id" id="ledger_id" class="form-select">
                                    <option value="">Select Account</option>
                                    @foreach ($ledgerAccounts as $ledgerAccount)
                                    <option value="{{ $ledgerAccount->id }}">{{ $ledgerAccount->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td style="width: 25%;">
                                <input type="text" id="ledger_amount" data-value="0.00"
                                    class="form-control fw-bold text-end"
                                    style="font-size: 1.1rem !important; padding: 7px !important;"
                                    value="0.00" disabled>
                            </td>
                            @can('payment_payable.create')
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-primary form-save-btn fw-bold fs-5"
                                    name="create_payment_voucher" id="create_payment_voucher"
                                    title="Create Payment Voucher" style="cursor: pointer;">
                                    <i class="fas fa-check-circle text-white me-2"></i>
                                    Save
                                </button>
                            </td>
                            @endcan
                            <td>
                                {{-- <button class="btn btn-sm btn-success from-prevent-multiple-submits" type="submit" aspect="default" name="ProcessOrder" id="billPay" title="Create Payment Voucher" autocomplete="off" style="cursor: pointer;">
                                                    <i class="fas fa-check-circle text-white me-1"></i><span> Update Purch.</span>
                                                </button> --}}
                            </td>
                            <td class="text-center">
                                <button
                                    class="btn btn-sm btn-secondary from-prevent-multiple-submits fw-bold fs-5"
                                    type="button" aspect="default" name="ProcessOrder" id="printPendingPayment"
                                    title="Print Approval" autocomplete="off"
                                    style="cursor: pointer;">
                                    <i class="fas fa-print text-white me-2"></i><span> Print</span>
                                </button>
                            </td>
                        </tr>
                    </table>
                </div>
                <div>
                    <table class="no-border w-100">
                        <tr>
                            <td style="width: 2%;">
                                <input type="checkbox" id="on_advance_check"
                                    style="width: 18px !important; height: 18px !important">
                            </td>
                            <td style="width: 15%;">
                                <label for="on_advance_check"
                                    class="form-check-label fw-bold legend-chip bg-success-subtle fw-bold fs-4">On
                                    Advance</label>
                            </td>
                            <td style="width: 52%;">

                            </td>
                            @can('payment_payable.hold')
                            <td style="width: 20%;">
                                <button class="btn btn-sm btn-info fw-bold fs-5" id="hold_bill"
                                    aspect="default" autocomplete="off" style="cursor: pointer;">
                                    <i class="fas fa-check-circle text-white me-2"></i><span> Hold Bill
                                    </span>
                                </button>
                            </td>
                            @endcan
                        </tr>
                    </table>
                </div>
            </div>

            <!-- RIGHT SIDE GROUP TABLE -->
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body p-2">
                        <div style="max-height: 200px; overflow-y: auto;">
                            <table class="table table-bordered table-hover table-sm mt-0 pt-0"
                                id="summary_table">
                                <thead class="table-light sticky-header">
                                    <tr>
                                        <th class="p-2" style="width: 5%;">Sr No.</th>
                                        <th class="p-2" style="width: 50%;">Particular</th>
                                        <th class="p-2" style="width: 25%;">City</th>
                                        <th class="p-2 text-end" style="width: 20%;">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="summary_table_body">
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">-- No Data Found -- </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>


                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
</div>
</div>
</div>
</div>
@include('company.partials._shortcuts-bar');
@endsection
@section('script')
<script>
    const getPaymentPayableDataUrl = "{{ route('references.payment_payables') }}";
    const holdBillUrl = "{{ route('payments.payable.hold') }}";
    const closingBalanceUrl = "{{ route('account-balance.closing') }}";
    const savePaymentPayableUrl = "{{ route('payments.payable.store') }}";
    const pendingPaymentUrl = "{{ route('payments.payable.print-pending-payment') }}";
</script>
<script src="{{ asset('js/account-helper.js') }}?v={{ hash_file('md5', public_path('js/account-helper.js')) }}"></script>
<script src="{{ asset('js/modules/payment/payable.js') }}?v={{ hash_file('md5', public_path('js/modules/payment/payable.js')) }}"></script>
@endsection