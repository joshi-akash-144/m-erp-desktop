@extends('company.layout.app')
@section('title', 'Driver Silak Expense – Create')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <style>
        .driver-expense-row-1 {
            grid-template-columns: 160px 160px 1fr 1fr 200px 220px;
        }

        @media (max-width: 992px) {
            .driver-expense-row-1 {
                grid-template-columns: 1fr 1fr;
            }
        }

        #driver_expense_table thead th {
            background-color: #2d3a4a;
            color: #fff;
            font-size: 0.82rem;
            padding: 8px 6px;
            white-space: nowrap;
        }

        #driver_expense_table tfoot td {
            background-color: #2d3a4a;
            color: #fff;
            font-weight: 600;
            padding: 8px 6px;
        }

        #driver_expense_table tfoot input {
            background-color: transparent;
            color: #fff;
            border: none;
            font-weight: 700;
            font-size: 1rem;
        }

        #driver_expense_table tbody td {
            padding: 2px 3px;
        }

        #driver_expense_table tbody td input,
        #driver_expense_table tbody td select {
            font-size: 0.82rem;
            padding: 3px 5px;
            height: 30px;
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
            text-align: center;
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

        .summary-value {
            font-size: 0.9rem;
            font-weight: 700;
        }

        .idle-amount-red {
            color: #e74c3c;
            font-weight: 700;
        }

        .expense-total-box {
            background-color: #eaf2ff;
            border: 1px solid #b8d0f0;
            border-radius: 4px;
            padding: 6px 10px;
            font-size: 1.05rem;
            font-weight: 700;
        }

        .remaining-balance-box {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 6px 10px;
            font-size: 1rem;
            font-weight: 700;
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
<div class="page-wrapper">

    {{-- Page Header --}}
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                            style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-truck"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Driver Expenses
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-green text-white fw-bold text-uppercase shadow-sm px-3 py-1"
                                style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-plus me-1"></i> NEW ENTRY
                            </span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
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
                <input type="hidden" id="uuid" name="uuid" value="{{ $uuid }}">

                {{-- Master data loader overlay --}}
                <div id="master_loader" style="
                    position: absolute; inset: 0; z-index: 999;
                    background: rgba(255,255,255,0.88);
                    display: flex; flex-direction: column;
                    align-items: center; justify-content: center; gap: 14px;
                    border-radius: inherit;">
                    <div class="spinner-border text-primary" style="width:3rem; height:3rem;" role="status"></div>
                    <div class="fw-semibold text-secondary" id="master_loader_text">Loading master data…</div>
                </div>

                <form id="driver_expense_form" autocomplete="off">
                    @csrf

                    {{-- Row 1: Header Fields --}}
                    <div class="grid-row driver-expense-row-1">
                        <div class="grid-item">
                            <label class="form-label fw-bold">Voucher No.</label>
                            <input type="text" name="voucher_no" id="voucher_no"
                                class="form-control voucher-no-box"
                                value="{{ $voucherNo }}" disabled>
                        </div>
                        <div class="grid-item">
                            <label class="form-label required fw-bold">Voucher Date</label>
                            <input type="text" name="voucher_date" id="voucher_date"
                                class="form-control date-format fw-bold"
                                placeholder="DD-MM-YYYY"
                                value="{{ current_date_dmy() }}">
                        </div>
                        <div class="grid-item">
                            <label class="form-label required fw-bold">Vehicle Name</label>
                            <select name="vehicle_id" id="vehicle_id" class="form-select">
                                <option value="">--Select Vehicle--</option>
                                @foreach ($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}">{{ $vehicle->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid-item">
                            <label class="form-label required fw-bold">Driver Name</label>
                            <select name="driver_id" id="driver_id" class="form-select">
                                <option value="">--Select Driver--</option>
                                @foreach ($drivers as $driver)
                                    <option value="{{ $driver->account_id }}" data-account-id="{{ $driver->account_id }}">
                                        {{ $driver->account->name ?? 'Driver #' . $driver->account_id }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid-item">
                            <label class="form-label fw-bold">Driver Balance</label>
                            <input type="text" id="driver_silak_balance" name="driver_silak_balance"
                                class="form-control bg-light fw-bold"
                                placeholder="Driver Balance" readonly>
                        </div>
                        <div class="grid-item">
                            {{-- <label class="form-label fw-bold">Expense Account</label> --}}
                            {{-- <select name="expense_account_id" id="expense_account_id" class="form-select">
                                <option value="">--Select Account--</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select> --}}

                            <div class="d-flex justify-content-end mb-1">
                                <button type="button" id="add_row_btn" class="btn btn-sm btn-outline-primary waves-effect non-selectable"
                                    data-bs-toggle="modal" data-bs-target="#addRowModal">
                                    <i class="fa-solid fa-plus me-1"></i> Add Row
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Item Table --}}
                    <div class="row mt-1">
                        <div class="col-12">
                            
                            <div style="max-height: 500px; overflow-x: auto; overflow-y: auto; position: relative;">
                                <table class="table border border-1 border-dark-subtle table-bordered mb-0"
                                    id="driver_expense_table" style="table-layout: fixed; min-width: 1800px;">
                                    <colgroup>
                                        <col style="width: 40px">     {{-- Sr. --}}
                                        <col style="width: 36px">     {{-- Del --}}
                                        <col style="width: 130px">    {{-- Date --}}
                                        <col style="width: 150px">    {{-- Expense Account --}}
                                        <col style="width: 250px">    {{-- From --}}
                                        <col style="width: 250px">    {{-- To --}}
                                        <col style="width: 90px">     {{-- DC/LR --}}
                                        <col style="width: 250px">    {{-- Product --}}
                                        <col style="width: 100px">    {{-- Rate --}}
                                        <col style="width: 100px">    {{-- Bags --}}
                                        <col style="width: 100px">    {{-- Weight --}}
                                        <col style="width: 65px">     {{-- Trips --}}
                                        <col style="width: 150px">    {{-- Amount --}}
                                        <col style="width: 200px">    {{-- Remark --}}
                                    </colgroup>
                                    <thead style="position: sticky; top: 0; z-index: 9;">
                                        <tr>
                                            <th class="text-center">Sr.</th>
                                            <th class="text-center"></th>
                                            <th class="text-center">Date</th>
                                            <th class="text-center">Expense Account</th>
                                            <th class="text-center">From</th>
                                            <th class="text-center">To</th>
                                            <th class="text-center">DC/LR</th>
                                            <th class="text-center">Product</th>
                                            <th class="text-end">Rate</th>
                                            <th class="text-end">Bags</th>
                                            <th class="text-end">Weight</th>
                                            <th class="text-end">Trips</th>
                                            <th class="text-end">Amount</th>
                                            <th class="text-start">Remark</th>
                                        </tr>
                                    </thead>
                                    <tbody id="driver_expense_table_body">
                                        @for ($i = 0; $i < 250; $i++)
                                        <tr data-index="{{ $i }}">
                                            <td class="text-center align-middle fw-bold text-secondary" style="font-size:12px;">
                                                <span class="row-serial">{{ $i + 1 }}</span>
                                            </td>
                                            <td class="text-center p-1 align-middle">
                                                <button type="button" class="delete-row-btn delete-row non-selectable" title="Delete row">
                                                    <i class="fa-solid fa-trash-can" style="font-size:11px;"></i>
                                                </button>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control date-format row-date"
                                                    placeholder="DD-MM-YYYY">
                                            </td>
                                            <td><input type="text" class="form-control row-picker row-expense-account" data-picker="expenseAccount" placeholder=""></td>
                                            <td><input type="text" class="form-control row-picker row-from" data-picker="destination" placeholder=""></td>
                                            <td><input type="text" class="form-control row-picker row-to" data-picker="destination" placeholder=""></td>
                                            <td>
                                                <input type="text" class="form-control row-dc-lr" placeholder="">
                                            </td>
                                            <td><input type="text" class="form-control row-picker row-product" data-picker="product" placeholder=""></td>
                                            <td>
                                                <input type="text" class="form-control text-end row-rate only-number"
                                                    placeholder="0.00">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control text-end row-bags only-number"
                                                    value="0" placeholder="0">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control text-end row-weight only-number"
                                                    value="0" placeholder="0">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control text-end row-trips only-number"
                                                    placeholder="">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control text-end row-amount only-number"
                                                    placeholder="0.00">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control row-remark" placeholder="">
                                            </td>
                                        </tr>
                                        @endfor
                                    </tbody>
                                    <tfoot style="position: sticky; bottom: 0; z-index: 9;">
                                        <tr>
                                            <td colspan="9" class="text-end pe-3 fw-bold">Total:</td>
                                            <td>
                                                <input type="text" id="total_bags" class="form-control text-end fw-bold"
                                                    value="0.00" disabled>
                                            </td>
                                            <td>
                                                <input type="text" id="total_weight" class="form-control text-end fw-bold"
                                                    value="0.00" disabled>
                                            </td>
                                            <td></td>
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
                        <div class="col-lg-5">
                            <label class="summary-label">Narration</label>
                            <input type="text" name="narration" id="narration" class="form-control"
                                 placeholder="narration" ></input>
                        </div>

                        {{-- KM & Diesel --}}
                        <div class="col-lg-3">
                            <div class="row g-2">
                                <div class="col-4">
                                    <label class="summary-label">Start kms</label>
                                    <input type="text" name="start_kms" id="start_kms"
                                        class="form-control text-end only-number" value="0.00">
                                </div>
                                <div class="col-4">
                                    <label class="summary-label">End kms</label>
                                    <input type="text" name="end_kms" id="end_kms"
                                        class="form-control text-end only-number" value="0.00">
                                </div>
                                <div class="col-4">
                                    <label class="summary-label">Total kms</label>
                                    <input type="text" name="total_kms" id="total_kms"
                                        class="form-control text-end bg-light fw-bold" value="0.00" readonly>
                                </div>
                                {{-- <div class="col-4">
                                    <label class="summary-label">Start Diesel</label>
                                    <input type="text" name="start_diesel" id="start_diesel"
                                        class="form-control text-end only-number" value="0.00">
                                </div>
                                <div class="col-4">
                                    <label class="summary-label">End Diesel</label>
                                    <input type="text" name="end_diesel" id="end_diesel"
                                        class="form-control text-end only-number" value="0.00">
                                </div>
                                <div class="col-4">
                                    <label class="summary-label">Average(%)</label>
                                    <input type="text" name="diesel_average" id="diesel_average"
                                        class="form-control text-end bg-light fw-bold" value="0.00" readonly>
                                </div> --}}
                            </div>
                        </div>

                        {{-- Idle Days --}}
                        {{-- <div class="col-lg-3">
                            <div class="row g-2">
                                <div class="col-4">
                                    <label class="summary-label">Idle Days</label>
                                    <input type="text" name="idle_days" id="idle_days"
                                        class="form-control text-end only-number" value="0">
                                </div>
                                <div class="col-4">
                                    <label class="summary-label">Idle Day wage</label>
                                    <input type="text" name="idle_day_wage" id="idle_day_wage"
                                        class="form-control text-end only-number" value="0.00">
                                </div>
                                <div class="col-4">
                                    <label class="summary-label">Idle Day wage<br>Amount</label>
                                    <input type="text" name="idle_day_wage_amount" id="idle_day_wage_amount"
                                        class="form-control text-end bg-light idle-amount-red fw-bold"
                                        value="0.00" readonly>
                                </div>
                            </div>
                        </div> --}}

                        {{-- Expense Total & Save --}}
                        <div class="col-lg-4">
                            <div class="d-flex gap-2">
                                 <div>
                                    <label class="summary-label fw-bold">Driver Silak Remaining Balance</label>
                                    <input type="text" id="remaining_balance" name="remaining_balance"
                                        class="form-control text-end remaining-balance-box fw-bold"
                                        value="0.00" readonly>
                                </div>
                                <div>
                                    <label class="summary-label fw-bold">Expense Total</label>
                                    <input type="text" id="expense_total" name="expense_total"
                                        class="form-control text-end expense-total-box fw-bold fs-5"
                                        value="0.00" readonly>
                                </div>
                                
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary waves-effect" id="save_btn">
                                        <i class="fa-solid fa-floppy-disk me-1"></i> Save
                                    </button>
                                </div>
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
                {{-- <div class="mb-1">
                    <label class="form-label fw-semibold">Default Date <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" id="add_row_default_date" class="form-control date-format"
                        placeholder="DD-MM-YYYY">
                </div> --}}
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

{{-- Picker Modal (shared for Expense Account, Destination, Product) --}}
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
    const storeDriverExpenseUrl      = "{{ route('driver-expense.store') }}";
    const getDairyImportDataUrl      = "{{ route('driver-expense.get-dairy-import-data') }}";
    const closingBalanceUrl          = "{{ route('account-balance.closing') }}";
</script>
<script src="{{ asset('js/modules/driver-expense/create.js') }}?v={{ time() }}"></script>
@endsection
