@extends('company.layout.app')
@section('title', 'Multi Expense Voucher – Edit #' . ($multiExpenseVoucher->voucher?->voucher_serial ?? $multiExpenseVoucher->id))

@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <style>
        .multi-expense-row-1 {
            grid-template-columns: 160px 160px  2fr 1fr 220px 1fr;
        }

        @media (max-width: 992px) {
            .multi-expense-row-1 {
                grid-template-columns: 1fr 1fr;
            }
        }

        #multi_expense_table thead th {
            background-color: #2d3a4a;
            color: #fff;
            font-size: 0.82rem;
            padding: 8px 6px;
            white-space: nowrap;
        }

        #multi_expense_table tfoot td {
            background-color: #2d3a4a;
            color: #fff;
            font-weight: 600;
            padding: 8px 6px;
        }

        #multi_expense_table tfoot input {
            background-color: transparent;
            color: #fff;
            border: none;
            font-weight: 700;
            font-size: 1rem;
        }

        #multi_expense_table tbody td {
            padding: 2px 3px;
        }

        #multi_expense_table tbody td input,
        #multi_expense_table tbody td select {
            font-size: 0.82rem;
            padding: 3px 5px;
            height: 30px;
        }

        #multi_expense_table tbody td input[readonly] {
            background-color: #e9ecef;
            color: #495057;
        }

        /* Raw <select> is never shown — Select2 renders its own container */
        #picker_select {
            display: none !important;
        }

        /* Allow Select2 dropdown to overflow the modal container */
        #pickerModal {
            overflow: visible !important;
        }
        #pickerModal .modal-dialog,
        #pickerModal .modal-content {
            overflow: visible;
        }
        #pickerModal .modal-content {
            min-height: 400px;
        }

        /* Keep Select2 dropdown above Bootstrap modal backdrop (z-index 1050) */
        .select2-container--open {
            z-index: 9999 !important;
        }

        .voucher-no-box {
            background-color: #e74c3c;
            color: #fff;
            border: none;
            font-weight: 700;
            font-size: 1rem;
            text-align: left;
        }

        .voucher-no-box:disabled {
            opacity: 1;
            background-color: #e74c3c;
            color: #fff;
        }

        .summary-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #555;
            margin-bottom: 2px;
        }

        .delete-row-btn {
            background-color: #e74c3c;
            border: none;
            color: #fff;
            width: 26px;
            height: 26px;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0;
        }

        .delete-row-btn:hover {
            background-color: #c0392b;
        }
    </style>
@endsection

@section('content')
<div class="page-wrapper" style="max-width: 1800px">

    {{-- Page Header --}}
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-warning rounded-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-warning-lt text-warning rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                            style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-truck"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-warning fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Multi Expense Voucher
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-warning text-white fw-bold text-uppercase shadow-sm px-3 py-1"
                                style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-pen me-1"></i> EDIT
                            </span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('multi-expense.index') }}"
                            class="btn btn-sm btn-outline-secondary waves-effect">
                            <i class="fa-solid fa-list me-1"></i> Register
                        </a>
                        <a href="{{ route('back.to.previous') }}"
                            class="btn btn-sm btn-outline-dark back-btn waves-effect rounded-0 fw-medium shadow-sm">
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
            <div class="card p-2 shadow-sm" style="position: relative;">

                {{-- Master data loader overlay --}}
                <div id="master_loader" style="
                    position: absolute; inset: 0; z-index: 999;
                    background: rgba(255,255,255,0.88);
                    display: flex; flex-direction: column;
                    align-items: center; justify-content: center; gap: 14px;
                    border-radius: inherit;">
                    <div class="spinner-border text-warning" style="width:3rem; height:3rem;" role="status"></div>
                    <div class="fw-semibold text-secondary" id="master_loader_text">Loading voucher data…</div>
                </div>

                <form id="multi_expense_form" autocomplete="off">
                    @csrf
                    @method('PUT')

                    {{-- Row 1: Header Fields --}}
                    <div class="grid-row multi-expense-row-1">
                        {{-- <div class="grid-item">
                            <label class="form-label fw-bold">Voucher No.</label>
                            <input type="text" name="voucher_no" id="voucher_no"
                                class="form-control voucher-no-box"
                                value="{{ $multiExpenseVoucher->voucher?->voucher_serial ?? '-' }}" disabled>
                        </div> --}}
                        <div class="grid-item">
                            <label class="form-label required fw-bold">Voucher Date</label>
                            <input type="text" name="voucher_date" id="voucher_date"
                                class="form-control date-format fw-bold"
                                placeholder="DD-MM-YYYY"
                                value="{{ $multiExpenseVoucher->voucher_date?->format('d-m-Y') ?? current_date_dmy() }}">
                        </div>
                        <div class="grid-item">
                            <label class="form-label fw-bold">Day</label>
                            <input type="text" name="day_for" id="day_for"
                                class="form-control voucher-no-box"
                                value="{{ $multiExpenseVoucher->day_for ?? '' }}" disabled>
                        </div>
                        <div class="grid-item">
                            <label class="form-label required fw-bold">Account Name(Cr)</label>
                            <select name="account_id" id="account_id" class="form-select">
                                <option value="">--Select Account--</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account['id'] }}"
                                        {{ $multiExpenseVoucher->account_id == $account['id'] ? 'selected' : '' }}>
                                        {{ $account['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid-item">
                            <label class="form-label fw-bold">Expense Account(Dr)</label>
                            <select name="expense_account_id" id="expense_account_id" class="form-select">
                                <option value="">--Select Account--</option>
                                @foreach ($expenseAccounts as $account)
                                    <option value="{{ $account->id }}"
                                        {{ $multiExpenseVoucher->expense_account_id == $account->id ? 'selected' : '' }}>
                                        {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
                            <button type="button" id="add_row_btn" class="btn btn-sm btn-outline-primary waves-effect non-selectable"
                                data-bs-toggle="modal" data-bs-target="#addRowModal">
                                <i class="fa-solid fa-plus me-1"></i> Add Row
                            </button>
                        </div>
                    </div>

                    {{-- Item Table --}}
                    <div class="row mt-1">
                        <div class="col-12">

                            <div style="max-height: 500px; overflow-y: auto; overflow-x: hidden; position: relative;">
                                <table class="table border border-1 border-dark-subtle table-bordered mb-0"
                                    id="multi_expense_table" style="table-layout: fixed; width: 100%;">
                                    <colgroup>
                                        <col style="width: 50px">     {{-- Sr. --}}
                                        <col style="width: 40px">     {{-- Del --}}
                                        <col style="width: 12%">      {{-- Challan No --}}
                                        <col style="width: 12%">      {{-- Bill No --}}
                                        <col style="width: 12%">      {{-- Date --}}
                                        <col style="width: 25%">      {{-- Particular --}}
                                        <col style="width: 12%">      {{-- Amount --}}
                                        <col>                         {{-- Remark --}}
                                    </colgroup>
                                    <thead style="position: sticky; top: 0; z-index: 9;">
                                        <tr>
                                            <th class="text-center">Sr.</th>
                                            <th class="text-center"></th>
                                            <th class="text-center">Challan No</th>
                                            <th class="text-center">Bill No</th>
                                            <th class="text-center">Date</th>
                                            <th class="text-left">vehicle</th>
                                            <th class="text-end">Amount</th>
                                            <th class="text-start">Remark</th>
                                        </tr>
                                    </thead>
                                    <tbody id="multi_expense_table_body">
                                        {{-- rows injected by edit.js after voucher data loads --}}
                                    </tbody>
                                    <tfoot style="position: sticky; bottom: 0; z-index: 9;">
                                        <tr>
                                            <td colspan="6" class="text-end pe-3 fw-bold">Total:</td>
                                            <td>
                                                <input type="text" id="total_amount" class="form-control text-end fw-bold"
                                                    value="0.00" disabled>
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Bottom Section --}}
                    <div class="row mt-3 g-2 align-items-start">

                        {{-- Narration --}}
                        <div class="col-lg-7">
                            <label class="summary-label">Narration</label>
                            <input type="text" name="narration" id="narration" class="form-control"
                                 placeholder="Narration" value="{{ $multiExpenseVoucher->narration ?? '' }}">
                        </div>

                        {{-- Save Buttons --}}
                        <div class="col-lg-5 d-flex align-items-end justify-content-end gap-2">
                            <div class="mt-3">
                                <button type="submit" class="btn btn-warning waves-effect text-white" id="save_btn">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> Update
                                </button>
                            </div>
                        </div>

                    </div>

                </form>
            </div>
        </div>
    </div>

</div>

{{-- Add Row Modal --}}
<div class="modal fade" id="addRowModal" tabindex="-1" aria-labelledby="addRowModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold" id="addRowModalLabel">
                    <i class="fa-solid fa-plus me-1 text-primary"></i> Add Rows
                </h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Row Add After Sr. No.</label>
                    <input type="number" id="add_row_after" class="form-control"
                        min="0" placeholder="0 = add at end" value="0">
                    <div class="form-text">Enter 0 to append at the end.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold required">How Many Rows</label>
                    <input type="number" id="add_row_count" class="form-control"
                        min="1" max="100" value="5">
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="add_row_confirm_btn">
                    <i class="fa-solid fa-plus me-1"></i> Add
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Picker Modal --}}
<div class="modal fade" id="pickerModal" tabindex="-1" aria-hidden="true"
     data-bs-keyboard="false" data-bs-focus="false">
    <div class="modal-dialog" style="max-width: 420px;">
        <div class="modal-content">
            <div class="modal-header py-2 px-3">
                <h6 class="modal-title fw-bold" id="picker_title">Select</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-3 py-2">
                <div id="picker_loader" class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <div class="small text-muted mt-1">Loading…</div>
                </div>
                <select id="picker_select" style="width: 100%;"></select>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
    const updateMultiExpenseVoucherUrl = "{{ route('multi-expense.update', $multiExpenseVoucher->id) }}";
    const getMultiExpenseVoucherDataUrl = "{{ route('multi-expense.data', $multiExpenseVoucher->id) }}";
    const pendingChallansUrl = "{{ route('multi-expense.pending-challans') }}";
</script>
<script src="{{ asset('js/modules/multi-expense/edit.js') }}?v={{ time() }}"></script>
@endsection
