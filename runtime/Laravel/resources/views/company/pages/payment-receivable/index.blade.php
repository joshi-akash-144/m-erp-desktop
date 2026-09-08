@extends('company.layout.app')

@section('title', 'Payment Receivable Ledger')

@section('css')
<link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
<style>
    /* * {
        border-radius: 0px !important;
    } */

    table tr td {
        /* padding: 4px 6px !important; */
        /* border: #dee2e6 solid 1px !important; */
    }

    table tr td input[type="text"],
    table tr td input[type="number"],
    table tr td input[type="text"]:focus,
    table tr td input[type="number"]:focus,
    table tr td input[type="text"]:focus-visible,
    table tr td input[type="number"]:focus-visible {
        padding: 0.15rem 0.5rem !important;
        margin: 0 !important;
        box-shadow: none !important;
        outline: none !important;
    }

    table tr td input[type="checkbox"] {
        width: 18px !important;
        height: 18px !important;
    }

    .table-wrapper {
        max-height: 320px;
        max-width: 1700px;
        border: #e8e8e8 solid 1px;
        overflow-y: auto;
        overflow-x: auto;
        border: 1px solid #dee2e6 !important;
        margin-bottom: 10px !important;
    }

    /* Fix: border-collapse: collapse breaks position: sticky */
    /* Override global table.table styles from index.css */
    .table-wrapper #payment_receivable_table {
        border-collapse: separate !important;
        border-spacing: 0;
        margin-bottom: 0 !important;
        margin-top: 0 !important;
    }

    /* Sticky header */
    #payment_receivable_table thead th {
        position: sticky;
        top: -1px;
        z-index: 10;
        border-bottom: 2px solid #dee2e6;
        box-shadow: 0 1px 0 #dee2e6;
        background-color: #e6f0fb !important;
        color: #333333;
    }

    #payment_receivable_table td,
    #payment_receivable_table th {
        white-space: nowrap;
        vertical-align: middle;
    }

    /* Fixed min-widths per column so header never shrinks on empty data */
    #payment_receivable_table thead th:nth-child(1) {
        min-width: 50px;
        width: 50px;
    }

    /* Yes/No    */
    #payment_receivable_table thead th:nth-child(2) {
        min-width: 50px;
    }

    /* Sr No     */
    #payment_receivable_table thead th:nth-child(3) {
        min-width: 90px;
    }

    /* Bill No   */
    #payment_receivable_table thead th:nth-child(4) {
        min-width: 95px;
    }

    /* Date      */
    #payment_receivable_table thead th:nth-child(5) {
        min-width: 120px;
    }

    /* Bill Amt  */
    #payment_receivable_table thead th:nth-child(6) {
        min-width: 130px;
    }

    /* Recv Amt  */
    #payment_receivable_table thead th:nth-child(7) {
        min-width: 60px;
    }

    /* DrCr      */
    #payment_receivable_table thead th:nth-child(8) {
        min-width: 60px;
    }

    /* Days      */
    #payment_receivable_table thead th:nth-child(9) {
        min-width: 80px;
    }

    /* Qty       */
    #payment_receivable_table thead th:nth-child(10) {
        min-width: 90px;
    }

    /* Rate      */
    #payment_receivable_table thead th:nth-child(11) {
        min-width: 130px;
    }

    /* GRN       */
    #payment_receivable_table thead th:nth-child(12) {
        min-width: 130px;
    }

    /* Product   */

    .active-row {
        background-color: #e1ebf9 !important;
    }

    .legend-chip {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 0px !important;
        background: #f8f9fa;
        font-size: 13px;
        border: 1px solid #ced4da;
    }

    .no-border,
    .no-border td,
    .no-border tr {
        border: 0 !important;
    }

    .label-cell-bg {
        background: #ededed;
        color: #334155;
        font-size: 13px;
        white-space: nowrap;
    }

    /* Page Header Design Enhancement */
    .page-header {
        display: flex;
        flex-wrap: wrap;
        min-height: 2.25rem;
        flex-direction: column;
        justify-content: center;
        max-width: 100%;
    }

    .page-wrapper .page-header {
        margin: var(--tblr-page-padding-y) 0 0;
    }

    .page-header-border {
        border-bottom: var(--tblr-border-width) var(--tblr-border-style) var(--tblr-border-color);
        padding: var(--tblr-page-padding-y) 0;
        margin: 0 !important;
        background-color: var(--tblr-bg-surface);
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
                            <i class="fs-3 fa-solid fa-file-invoice-dollar"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Payment Receivable
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
                        <!-- OPEN MODAL BUTTON -->
                        <div class="waves-effect d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-primary waves-effect" onclick="openLedgerSettingModal()">
                                <i class="fa-solid fa-gear me-1"></i>
                                Module Setting
                            </button>
                        </div>

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

                <!-- Title -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card border-0 border-start border-4 border-primary">
                        <div class="card-body shadow p-2">
                            <h3 class="page-title font-monospace">
                                <i class="fa-solid fa-file-invoice-dollar me-2 text-primary"></i>
                                Payment Receivable
                            </h3>
                            <span class="ribbon ribbon-bookmark bg-blue">
                                <i class="fa-solid fa-eye me-1"></i> LIST DATA
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Back Action -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                    <div class="card border-0 border-end border-4 border-primary">
                        <div class="card-body shadow-sm p-2">
                            <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                <!-- OPEN MODAL BUTTON -->
                                <div class="waves-effect d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-primary waves-effect" onclick="openLedgerSettingModal()">
                                        <i class="fa-solid fa-gear me-1"></i>
                                        Module Setting
                                    </button>
                                </div>
                                <!-- BACK BUTTON -->
                                <div class="waves-effect d-flex gap-2">
                                    <a href="{{ route('back.to.previous') }}"
                                        class="btn btn-sm btn-outline-dark back-btn waves-effect">
                                        <i class="fa-solid fa-arrow-left me-1"></i>
                                        Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div> --}}

    <!-- Page Body -->
    <div class="page-body">
        <div class="container-xl">
            <div class="card p-3 mb-3">
                <!-- 🔹 TOP HEADER FILTER PANEL -->
                <form>
                    <div class="row g-2 align-items-end mb-3">
                        <!-- Vch. No -->
                        <div class="col-md-2 col-lg-1">
                            <label class="fs-4 fw-bold">Vch. No:</label>
                            <input type="text" id="voucher_number" value="{{ $receiptVoucherSerial }}" class="form-control fw-bold bg-yellow-lt text-danger" disabled>

                            <input type="hidden" id="unique_request_id" value="{{ uuid() }}">
                        </div>

                        <!-- Date -->
                        <div class="col-md-3 col-lg-2">
                            <label class="fs-4 fw-bold">Date</label>
                            <input type="text" placeholder="DD-MM-YYYY" class="form-control text-danger fw-bold date-format" id="receipt_date">
                        </div>

                        <!-- Account Name -->
                        <div class="col-md-4 col-lg-3">
                            <label class="fs-4 fw-bold">Account Name</label>
                            <select id="account_id" name="account_id" class="form-select custom-select2">
                                <option value="">--Account Name--</option>
                                @foreach ($customers as $customer)
                                <option value="{{ $customer['id'] }}">{{ $customer['name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 col-lg-1">
                            <button id="filter_apply" type="submit" class="btn btn-primary flex-fill waves-effect">
                                <svg xmlns="http://www.w3.org/2000/svg" style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-filter">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M4 4h16v2.172a2 2 0 0 1 -.586 1.414l-4.414 4.414v7l-6 2v-8.5l-4.48 -4.928a2 2 0 0 1 -.52 -1.345v-2.227z"></path>
                                </svg> Show
                            </button>
                        </div>

                        <!-- Outstanding -->
                        <div class="col-md-3 col-lg-2">
                            <label class="fs-4 fw-bold">Outstanding</label>
                            <input type="text" id="ledger_balance" class="form-control fw-bold text-success text-center"
                                style="font-size: 20px !important;" value="0.00 Dr" readonly>
                        </div>

                        <!-- Reset Filter Button -->
                        <div class="col-md-2 col-lg-1">
                            <button id="filter_reset" type="reset" class="btn btn-outline-danger flex-fill waves-effect">
                                <i class="fa-solid fa-rotate-left me-1"></i> Reset Filter
                            </button>
                        </div>

                        <!-- Days Filter -->
                        {{-- <div class="col-md-3 col-lg-2 ">
                            <label class="fs-4 fw-bold">Days Filter</label>
                            <input type="text" class="form-control" placeholder="Enter Days">
                        </div> --}}
                    </div>

                    <!-- 🔹 MAIN DATA TABLE -->
                    <div class="table-wrapper">
                        <table class="table table-bordered table-hover table-sm" id="payment_receivable_table">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px;">Yes/No</th>
                                    <th>Sr No</th>
                                    <th class="text-center">Bill No</th>
                                    <th class="text-center">Date</th>
                                    <th class="text-end">Bill Amount</th>
                                    <th class="text-end">Receive-Amount</th>
                                    <th class="text-center">DrCr</th>
                                    <th class="text-center">Days</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-center">Rate</th>
                                    <th>GRN</th>
                                    <th>Product</th>
                                </tr>
                            </thead>
                            <tbody id="payment_receivable_main_body">
                                @for ($i = 0; $i <= 15; $i++)
                                    <tr class="bg-table">
                                    @for ($k = 0; $k < 12; $k++)
                                        <td class="border-0">&nbsp;</td>
                                        @endfor
                                        </tr>
                                        @endfor
                            </tbody>
                        </table>
                    </div>

                    <!-- 🔹 BOTTOM DASHBOARD PANEL -->
                    <div class="row g-3">
                        <!-- 1. LEFT PANEL -->
                        <div class="col-lg-2">
                            <div class="d-flex flex-column gap-2 p-2 bg-light border">
                                <!-- All checkbox -->
                                <div class="d-flex align-items-center gap-2 p-1 bg-white border">
                                    <input type="checkbox" id="all_check" style="width: 18px; height: 18px;">
                                    <label for="all_check" class="fw-bold fs-4 mb-0">All</label>
                                </div>

                                <!-- Filter Bill-No -->
                                <div class="d-flex align-items-center gap-2">
                                    <span class="label-cell-bg p-2 fs-4" style="width: 110px;">Filter Bill-No</span>
                                    <input type="text" id="filter_bill_no" class="form-control" placeholder="Enter Bill No.">
                                </div>

                                <!-- Find -->
                                <div class="d-flex align-items-center gap-2">
                                    <span class="label-cell-bg p-2 fs-4" style="width: 110px;">Find</span>
                                    <input type="text" id="find_bill_no" class="form-control" placeholder="Find Bill No.">
                                </div>

                                <!-- Only Selected Button -->
                                <button class="btn btn-secondary text-white fw-bold fs-4 w-100" id="only_selected">
                                    Only Selected
                                </button>

                                <!-- Last Bill -->
                                <div class="d-flex align-items-center gap-2 p-1 bg-white border">
                                    <span class="fw-bold fs-4 text-secondary">Last Bill:</span>
                                </div>

                                <!-- Total Bill: 0 -->
                                <div class="d-flex align-items-center gap-2 p-1 bg-white border">
                                    <span class="fw-bold fs-4 text-dark">Total Bill: <span id="total_bill_count">0</span></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card p-2 shadow-sm border">
                                <table class="no-border w-100">
                                    <!-- BANK -->
                                    <tr>
                                        <td style="width: 15%;">
                                            <span class="label-cell-bg p-2 fs-4 d-block text-center fw-bold">BANK:</span>
                                        </td>
                                        <td style="width: 50%;">
                                            <select class="form-select w-100 non-selectable" id="bank" name="bank">
                                                <option value="">Select Bank</option>
                                            </select>
                                        </td>
                                        <td style="width: 25%;">
                                            <input type="text" id="bank_amount" class="form-control text-end fw-bold" style="padding:0.35rem !important" value="">
                                        </td>
                                        <td style="width: 10%;" class="text-center fw-bold fs-4">Dr</td>
                                    </tr>
                                    <!-- TDS -->
                                    <tr>
                                        <td style="width: 15%;">
                                            <span class="label-cell-bg p-2 fs-4 d-block text-center fw-bold">TDS:</span>
                                        </td>
                                        <td style="width: 50%;">
                                            <select class="form-select w-100 non-selectable" id="tds" name="tds">
                                                <option value="">Select TDS</option>
                                            </select>
                                        </td>
                                        <td style="width: 25%;">
                                            <input type="text" id="tds_amount" class="form-control text-end fw-bold" style="padding:0.35rem !important" value="">
                                        </td>
                                        <td style="width: 10%;" class="text-center fw-bold fs-4">Dr</td>
                                    </tr>
                                    <!-- REBATE -->
                                    <tr>
                                        <td style="width: 15%;">
                                            <span class="label-cell-bg p-2 fs-4 d-block text-center fw-bold">REBATE:</span>
                                        </td>
                                        <td style="width: 50%;">
                                            <select class="form-select w-100 non-selectable" id="rebate" style="padding:0.35rem !important" name="rebate">
                                                <option value="">REBATE</option>
                                            </select>
                                        </td>
                                        <td style="width: 25%;">
                                            <input type="text" id="rebate_amount" class="form-control text-end fw-bold" style="padding:0.35rem !important" value="">
                                        </td>
                                        <td style="width: 10%;" class="text-center fw-bold fs-4">Dr</td>
                                    </tr>
                                    <!-- PREMIUM -->
                                    <tr>
                                        <td style="width: 15%;">
                                            <span class="label-cell-bg p-2 fs-4 d-block text-center fw-bold">PREMIUM:</span>
                                        </td>
                                        <td style="width: 50%;">
                                            <select class="form-select w-100 non-selectable" id="premium" name="premium">
                                                <option value="">Select Premium</option>
                                            </select>
                                        </td>
                                        <td style="width: 25%;">
                                            <input type="text" id="premium_amount" class="form-control text-end fw-bold" style="padding:0.35rem !important" value="">
                                        </td>
                                        <td style="width: 10%;" class="text-center fw-bold fs-4">Cr</td>
                                    </tr>
                                    <!-- PENALTY -->
                                    <tr>
                                        <td style="width: 15%;">
                                            <span class="label-cell-bg p-2 fs-4 d-block text-center fw-bold">PENALTY:</span>
                                        </td>
                                        <td style="width: 50%;">
                                            <select class="form-select w-100 non-selectable" id="penalty" name="penalty">
                                                <option value="">Select Penalty</option>
                                            </select>
                                        </td>
                                        <td style="width: 25%;">
                                            <input type="text" id="penalty_amount" class="form-control text-end fw-bold" style="padding:0.35rem !important" value="">
                                        </td>
                                        <td style="width: 10%;" class="text-center fw-bold fs-4">Dr</td>
                                    </tr>
                                    <!-- OTHER -->
                                    <tr>
                                        <td style="width: 15%;">
                                            <span class="label-cell-bg p-2 fs-4 d-block text-center fw-bold">OTHER:</span>
                                        </td>
                                        <td style="width: 50%;">
                                            <select class="form-select w-100 non-selectable" id="other" name="other">
                                                <option value="">Select Other</option>
                                            </select>
                                        </td>
                                        <td style="width: 25%;">
                                            <input type="text" id="other_amount" class="form-control text-end fw-bold" style="padding:0.35rem !important" value="">
                                        </td>
                                        <td style="width: 10%;">
                                            <select class="form-select non-selectable" style="padding: 0.15rem 0.35rem;" id="other_transaction_type" name="other_transaction_type">
                                                <option value="cr" selected>Cr</option>
                                                <option value="dr">Dr</option>
                                            </select>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Total of Account row -->
                                <div class="d-flex align-items-center justify-content-end gap-2 mt-2 pt-2 border-top">
                                    <span class="fw-bold fs-4">Total of Account:</span>
                                    <input type="text" id="total_of_account" class="form-control fw-bold text-end text-primary bg-primary bg-opacity-10" style="font-size: 1.1rem; width: 190px;" value="0.00" readonly>
                                    <button class="btn btn-warning fw-bold fs-3 non-selectable reset-ledger-btn" type="button" style="border: none;">
                                        Reset
                                    </button>
                                </div>

                                <!-- ── COMPACT DR / CR SUMMARY (2-column side-by-side) ── -->
                                <div class="mt-2 pt-2 border-top" id="dr_cr_summary_table">
                                    <table class="table table-sm table-bordered mb-1 w-100" style="font-size: 12px;">
                                        <thead>
                                            <tr class="table-light">
                                                <th class="text-center text-success" style="width:50%">
                                                    <i class="fa-solid fa-arrow-down me-1"></i> Credit Side
                                                </th>
                                                <th class="text-center text-danger" style="width:50%">
                                                    <i class="fa-solid fa-arrow-up me-1"></i> Debit Side
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="text-success">
                                                    Base (Bills)<span class="float-end fw-bold" id="summary_base_cr">0.00</span>
                                                </td>
                                                <td class="text-danger">
                                                    Bank<span class="float-end fw-bold" id="summary_bank_dr">0.00</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-success">
                                                    Premium<span class="float-end" id="summary_premium_cr">0.00</span>
                                                </td>
                                                <td class="text-danger">
                                                    TDS<span class="float-end" id="summary_tds_dr">0.00</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-success">
                                                    Other (Cr)<span class="float-end" id="summary_other_cr">0.00</span>
                                                </td>
                                                <td class="text-danger">
                                                    Rebate<span class="float-end" id="summary_rebate_dr">0.00</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-success">&nbsp;</td>
                                                <td class="text-danger">
                                                    Penalty<span class="float-end" id="summary_penalty_dr">0.00</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-success">&nbsp;</td>
                                                <td class="text-danger">
                                                    Other (Dr)<span class="float-end" id="summary_other_dr">0.00</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold">
                                                <td class="bg-success bg-opacity-10 text-success">
                                                    Total Cr<span class="float-end" id="summary_total_cr">0.00</span>
                                                </td>
                                                <td class="bg-danger bg-opacity-10 text-danger">
                                                    Total Dr<span class="float-end" id="summary_total_dr">0.00</span>
                                                </td>
                                            </tr>
                                            <tr id="summary_diff_row">
                                                <td colspan="2" class="text-center fw-bold">
                                                    Difference: <span id="summary_difference">0.00</span>
                                                    &nbsp;&nbsp;
                                                    <span id="balance_status_badge" class="badge bg-secondary px-2 text-white">
                                                        <i class="fa-solid fa-circle-info me-1"></i> Enter amounts
                                                    </span>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                            </div>
                        </div>

                        <!-- 3. RIGHT PANEL -->
                        <div class="col-lg-3">
                            <div class="card p-2 shadow-sm border d-flex flex-column gap-2">

                                <!-- Total Box -->
                                <div class="rounded p-2 text-center border border-secondary-lt">
                                    <div class="text-muted fs-4 small mb-1">
                                        <i class="fa-solid fa-indian-rupee-sign me-1 text-secondary"></i>
                                        Total (Base)
                                    </div>
                                    <input type="text" class="form-control fw-bold text-center text-primary bg-primary bg-opacity-10 w-100" style="font-size: 1.5rem !important;" value="0.00" readonly id="total_amount">
                                </div>

                                <!-- Narration -->
                                <div>
                                    <label for="remarks" class="form-label fw-semibold mb-1">
                                        <i class="fa-regular fa-note-sticky me-2 text-primary"></i> Narration
                                    </label>
                                    <textarea id="remarks" name="remarks" rows="6" class="form-control non-selectable" placeholder="Enter notes or remarks..."></textarea>
                                </div>

                                <!-- Save Button -->
                                <button class="btn btn-primary form-save-btn w-100 fw-bold fs-4" id="create_payment_voucher">
                                    <i class="fa-solid fa-circle-check me-1"></i> Save
                                </button>

                            </div>
                        </div>
                    </div>
                </form>

                {{-- Payment Receivable Modal --}}
                <div class="modal fade" id="payment_receivable_modal" tabindex="-1" aria-hidden="true">
                    @include('company.pages.payment-receivable.payment_receivable_modal')
                </div>

            </div>
        </div>
    </div>
</div>
@include('company.pages.payment-receivable._voucher_modal')
@include('company.partials._shortcuts-bar');
@endsection
@section('script')
<script>
    const paymentReceivablesUrl = "{{ route('references.payment_receivables') }}";
    const closingBalanceUrl = "{{ route('account-balance.closing') }}";
    const allLedgerAccounts = @json($ledgerAccounts);
    const receivableStoreUrl = "{{ route('payments.receivable.store') }}";

    //Routes for receivable setting
    const settingVoucherStoreUrl = "{{ route('setting.store', ['module' => 'receivable', 'key' => 'account_voucher']) }}";
    const settingVoucherShowUrl = "{{ route('setting.show', ['module' => 'receivable', 'key' => 'account_voucher']) }}";
</script>
<script src="{{ asset('js/account-helper.js') }}?v={{ hash_file('md5', public_path('js/account-helper.js')) }}"></script>
<script src="{{ asset('js/modules/payment/receivable.js') }}?v={{ hash_file('md5', public_path('js/modules/payment/receivable.js')) }}"></script>
<script src="{{ asset('js/modules/payment/setting.js') }}?v={{ hash_file('md5', public_path('js/modules/payment/setting.js')) }}"></script>
@endsection