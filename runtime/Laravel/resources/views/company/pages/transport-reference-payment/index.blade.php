@extends('company.layout.app')

@section('title', 'Transport Reference Payment')

@section('css')
<link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
<style>
    table tr td {
        padding: 3px 6px !important;
        border: #dadada solid 1px !important;
        font-size: 0.97rem !important;
    }

    table tr td input[type="checkbox"] {
        width: 18px !important;
        height: 18px !important;
        cursor: pointer;
    }

    #ref_table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        box-shadow: inset 0 2px 0 #dee2e6;
        white-space: nowrap;
    }

    #ref_table td,
    #ref_table th {
        vertical-align: middle;
        white-space: nowrap;
    }

    .active-row {
        background-color: #e1ebf9 !important;
    }

    .no-border,
    .no-border td,
    .no-border tr {
        border: 0 !important;
    }

    #summary_table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #f8f9fa;
    }

    .bottom-panel {
        border-top: 2px solid #dee2e6;
        padding-top: 10px;
    }

    #modal_entries_table td,
    #modal_entries_table th {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 140px;
    }

    .total-box {
        font-size: 0.9rem !important;
        font-weight: bold;
        padding: 3px 8px !important;
        max-width: 150px;
    }
</style>
@endsection

@section('content')
<div class="page-wrapper">

    {{-- Page Header --}}
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg,#ffffff,#f8f9fa);">
                <div class="card-body p-2 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-warning rounded-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-warning-lt text-warning rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width:38px;height:38px;">
                            <i class="fs-3 fa-solid fa-truck-fast"></i>
                        </div>
                        <h2 class="page-title text-warning fw-bolder mb-0" style="font-size:1.2rem;">Transport Reference Payment</h2>
                        <span class="badge badge-pill bg-yellow-lt fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size:0.75rem;">
                            <i class="fa-solid fa-receipt me-1"></i> Journal References
                        </span>
                    </div>
                    <div>
                        <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card shadow-sm">

                {{-- ── Filters ──────────────────────────────────────────── --}}
                <form onsubmit="return false;">

                    <div class="p-3 border-bottom">
                        <div class="row g-2 align-items-end">

                            <div class="col-md-5 col-lg-3">
                                <label class="fs-4 fw-bold">Account</label>
                                <select id="account_id" class="form-select">
                                    <option value="">All Accounts</option>
                                    @foreach ($accounts as $account)
                                    <option value="{{ $account['id'] }}">{{ $account['name'] }}@if(!empty($account['city'])) ({{ $account['city'] }})@endif</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 col-lg-2">
                                <label class="fs-4 fw-bold">Payment Date</label>
                                <input type="text" id="payment_date" class="form-control text-danger fw-bold" placeholder="DD-MM-YYYY">
                            </div>

                            <div class="col-auto">
                                <button id="btn_show" type="button" class="btn btn-primary waves-effect">
                                    <i class="fa-solid fa-filter me-1"></i> Show
                                </button>
                            </div>

                            <div class="col-md-4 col-lg-4 ms-auto">
                                <div class="input-group">
                                    <select class="form-select text-muted" id="search_type" style="max-width: 110px;">
                                        <option value="like">Like</option>
                                        <option value="equal">Equal</option>
                                    </select>
                                    <div class="input-icon flex-grow-1 ms-md-3" style="max-width: 850px;">
                                        <span class="input-icon-addon"><i class="fa-solid fa-search"></i></span>
                                        <input type="text" id="general_search" class="form-control ps-4" style="padding-left: 2.5rem !important;" placeholder="Search items...">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </form>

                {{-- ── Reference Table ───────────────────────────────────── --}}
                <div class="card-body pt-0 pb-0">
                    <div class="table-responsive" style="height: 450px; overflow-y: auto;">
                        <table class="table table-hover table-bordered mb-0" id="ref_table" style="table-layout:fixed;width:100%;">
                            <colgroup>
                                <col style="width:42px;">
                                <col style="width:150px;">
                                <col style="width:110px;">
                                <col style="width:140px;">
                                <col style="width:140px;">
                                <col style="width:60px;">
                                <col style="width:230px;">
                                <col style="width:130px;">
                                <col style="width:130px;">
                                <col style="width:90px;">
                            </colgroup>
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center"></th>
                                    <th>Ref No</th>
                                    <th>Ref Date</th>
                                    <th class="text-end">Pending Amount</th>
                                    <th class="text-end">Org. Amount</th>
                                    <th class="text-center">Dr/Cr</th>
                                    <th>Particular</th>
                                    <th>City</th>
                                    <th>Created By</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="ref_table_body">
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        <i class="fa-solid fa-magnifying-glass me-2"></i>Select filters and click Show
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ── Bottom Panel ──────────────────────────────────────── --}}
                <div class="bottom-panel px-3 pb-3">
                    <div class="row g-2 align-items-stretch">

                        {{-- LEFT: Payment Controls --}}
                        <div class="col-lg-6">
                            <div class="card shadow-sm h-100 p-2">

                                {{-- Row 1: All / Total / Bank --}}
                                <div class="mb-1">
                                    <table class="no-border w-100">
                                        <tr>
                                            <td style="width: 2%;">
                                                <input type="checkbox" id="all_check" class="non-selectable"
                                                    style="width: 20px !important; height: 20px !important">
                                            </td>
                                            <td style="width: 6%;">
                                                <label for="all_check" class="form-check-label fw-bold legend-chip fs-4">All</label>
                                            </td>
                                            <td style="width: 1%;">|</td>
                                            <td style="width: 5%;">
                                                <label class="fw-bold mb-0 legend-chip fs-4" for="payment_total">Total</label>
                                            </td>
                                            <td style="width: 38%;">
                                                <input type="text" id="payment_total" data-value="0.00"
                                                    class="form-control fw-bold text-end text-primary cursor-not-allowed border border-1 border-dark bg-primary bg-opacity-10"
                                                    style="font-size: 1.2rem !important; padding: 8px !important;" value="0.00" readonly>
                                            </td>
                                            <td style="width: 15%;">
                                                <label class="mb-0 legend-chip fw-bold fs-4 text-center" for="bank_id">Bank Name</label>
                                            </td>
                                            <td style="width: 33%;">
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

                                {{-- Row 2: Ledger / Amount / Buttons --}}
                                <div class="mb-1" style="margin-top: 6px;">
                                    <table class="no-border w-100" id="extra_ledger_add_row">
                                        <tr>
                                            <td style="width: 10%;">
                                                <select name="ledger_transaction_type" id="ledger_transaction_type" class="form-select custom-select2">
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
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-primary form-save-btn fw-bold fs-5"
                                                    name="btn_pay" id="btn_pay" title="Create Payment Voucher" style="cursor: pointer;">
                                                    <i class="fas fa-check-circle text-white me-2"></i>Save
                                                </button>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-warning fw-bold fs-5"
                                                    type="button" name="hold_bill" id="hold_bill"
                                                    title="Hold Bill" autocomplete="off" style="cursor: pointer;">
                                                    <i class="fas fa-hand-paper text-dark me-2"></i>Hold
                                                </button>
                                            </td>
                                        </tr>
                                    </table>
                                </div>

                                {{-- Row 3: Narration --}}
                                <div class="mb-1" style="margin-top: 6px;">
                                    <table class="no-border w-100">
                                        <tr>
                                            <td style="width: 10%;">
                                                <label class="fw-bold fs-4 mb-0">Narration</label>
                                            </td>
                                            <td>
                                                <input type="text" id="narration" class="form-control"
                                                    placeholder="Enter narration..." autocomplete="off">
                                            </td>
                                        </tr>
                                    </table>
                                </div>

                                <hr class="my-2 border-secondary border-opacity-25">

                            </div>
                        </div>

                        {{-- RIGHT: Summary --}}
                        <div class="col-lg-6">
                            <div class="card border shadow-sm h-100">
                                <div class="card-body p-2">
                                    <div style="height:200px; overflow-y:auto;">
                                        <table class="table table-bordered table-hover table-sm mb-0" id="summary_table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width:5%;">#</th>
                                                    <th style="width:50%;">Particular</th>
                                                    <th style="width:25%;">City</th>
                                                    <th style="width:20%;" class="text-end">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody id="summary_table_body">
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">-- No selection --</td>
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

{{-- ── Detail Modal ──────────────────────────────────────────── --}}
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width:92vw;">
        <div class="modal-content">
            <div class="modal-header py-2 bg-primary-lt">
                <h5 class="modal-title fw-bold" id="detailModalLabel">
                    <i class="fa-solid fa-truck-fast me-2 text-primary"></i>
                    Driver Expense Detail
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">

                {{-- Info strip --}}
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-2">
                        <div class="card border p-2">
                            <div class="text-muted" style="font-size:0.75rem;">Reference No</div>
                            <div class="fw-bold" id="m_voucher_no">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card border p-2">
                            <div class="text-muted" style="font-size:0.75rem;">Date</div>
                            <div class="fw-bold" id="m_voucher_date">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border p-2">
                            <div class="text-muted" style="font-size:0.75rem;">Driver</div>
                            <div class="fw-bold" id="m_driver">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card border p-2">
                            <div class="text-muted" style="font-size:0.75rem;">Vehicle</div>
                            <div class="fw-bold" id="m_vehicle">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card border p-2">
                            <div class="text-muted" style="font-size:0.75rem;">Pending Amt</div>
                            <div class="fw-bold text-danger" id="m_pending">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-1">
                        <div class="card border p-2">
                            <div class="text-muted" style="font-size:0.75rem;">Total</div>
                            <div class="fw-bold text-primary" id="m_expense_total">—</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-5">
                        <div class="card border p-2">
                            <div class="text-muted" style="font-size:0.75rem;">Narration</div>
                            <div class="fw-bold" id="m_narration">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border p-2">
                            <div class="text-muted" style="font-size:0.75rem;">Created By</div>
                            <div class="fw-bold" id="m_created_by">—</div>
                        </div>
                    </div>
                </div>

                {{-- Items table --}}
                <div class="fw-bold mb-1 text-primary" style="font-size:0.88rem;">
                    <i class="fa-solid fa-list me-1"></i> Expense Items
                </div>
                <div style="max-height:320px;overflow-y:auto;">
                    <table class="table table-bordered table-sm table-hover mb-0" id="modal_entries_table" style="font-size:0.85rem;">
                        <thead class="table-dark" style="position:sticky;top:0;z-index:5;">
                            <tr>
                                <th>#</th>
                                <th>Exp. Date</th>
                                <th>Expense Name</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Product</th>
                                <th>DC/LR</th>
                                <th class="text-end">Bags</th>
                                <th class="text-end">Weight</th>
                                <th class="text-center">Trips</th>
                                <th class="text-end">Amount</th>
                                <th>Remark</th>
                            </tr>
                        </thead>
                        <tbody id="modal_entries_body">
                            <tr>
                                <td colspan="12" class="text-center text-muted">—</td>
                            </tr>
                        </tbody>
                        <tfoot id="modal_entries_foot" class="table-light fw-bold">
                            <tr>
                                <td colspan="7" class="text-end">Total</td>
                                <td class="text-end" id="m_total_bags">0.00</td>
                                <td class="text-end" id="m_total_weight">0.00</td>
                                <td class="text-center" id="m_total_trips">0</td>
                                <td class="text-end" id="m_total_amount">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

{{-- Hidden UUID for idempotency --}}
<input type="hidden" id="uuid" value="{{ uuid() }}">

@section('script')
<script>
    const listUrl = "{{ route('transport-reference-payment.list') }}";
    const detailUrl = "{{ route('transport-reference-payment.detail', ['id' => '__ID__']) }}";
    const storeUrl = "{{ route('transport-reference-payment.store') }}";
    const holdBillUrl = "{{ route('transport-reference-payment.hold') }}";
    const deleteUrl = "{{ route('transport-reference-payment.destroy', ['id' => '__ID__']) }}";
</script>
<script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
<script src="{{ asset('js/modules/transport-reference-payment/index.js') }}?v={{ filemtime(public_path('js/modules/transport-reference-payment/index.js')) }}"></script>
@endsection