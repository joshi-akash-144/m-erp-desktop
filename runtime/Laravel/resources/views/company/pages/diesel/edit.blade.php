@extends('company.layout.app')
@section('title', 'Diesel Expense – Edit')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <style>
        .diesel-row-1 {
            grid-template-columns: 160px 1fr 200px 1fr;
        }

        @media (max-width: 992px) {
            .diesel-row-1 {
                grid-template-columns: 1fr 1fr;
            }
        }

        #diesel_table thead th {
            background-color: #2d3a4a;
            color: #fff;
            font-size: 0.82rem;
            padding: 8px 6px;
            white-space: nowrap;
        }

        #diesel_table tfoot td {
            background-color: #2d3a4a;
            color: #fff;
            font-weight: 600;
            padding: 8px 6px;
        }

        #diesel_table tfoot input {
            background-color: transparent;
            color: #fff;
            border: none;
            font-weight: 700;
            font-size: 1rem;
        }

        #diesel_table tbody td {
            padding: 2px 3px;
        }

        #diesel_table tbody td input,
        #diesel_table tbody td select {
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
                                Diesel Entry
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-orange text-white fw-bold text-uppercase shadow-sm px-3 py-1"
                                style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-pen me-1"></i> EDIT ENTRY
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

                <form id="diesel_form" autocomplete="off">
                    @csrf

                    {{-- Row 1: Header Fields --}}
                    <div class="grid-row diesel-row-1">
                       
                        <div class="grid-item">
                            <label class="form-label required fw-bold">Voucher Date</label>
                            <input type="text" name="voucher_date" id="voucher_date"
                                class="form-control date-format fw-bold"
                                placeholder="DD-MM-YYYY"
                                value="{{ \Carbon\Carbon::parse($diesel->voucher_date)->format('d-m-Y') }}">
                        </div>
                        <div class="grid-item">
                            <label class="form-label required fw-bold">Diesel Account</label>
                            <select name="account_id" id="account_id" class="form-select">
                                <option value="">--Select Account--</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account['id'] ?? '' }}" {{ $diesel->account_id == $account['id'] ? 'selected' : '' }}>{{ $account['name'] ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- <div class="grid-item">
                            <label class="form-label fw-bold required">Rate</label>
                            <input type="text" id="diesel_rate" name="diesel_rate"
                                class="form-control"
                                placeholder="Diesel Rate">
                        </div> --}}
                        <div class="grid-item">
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
                                    id="diesel_table" style="table-layout: fixed; min-width: 1800px;">
                                    <colgroup>
                                        <col style="width: 40px">     {{-- Sr. --}}
                                        <col style="width: 36px">     {{-- Del --}}
                                        <col style="width: 130px">    {{-- Challan No. --}}
                                        <col style="width: 250px">    {{-- Vehicle --}}
                                        <col style="width: 250px">    {{-- Driver --}}
                                        <col style="width: 150px">    {{-- Last Date --}}
                                        <col style="width: 150px">     {{-- Today --}}
                                        <col style="width: 150px">    {{-- Rate --}}
                                        <col style="width: 150px">    {{-- Diesel --}}
                                        <col style="width: 150px">    {{-- Old K.M --}}
                                        <col style="width: 150px">    {{-- New K.M --}}
                                        <col style="width: 150px">    {{-- Amount --}}
                                        <col style="width: 150px">     {{-- Diff --}}
                                        <col style="width: 150px">    {{-- Average --}}
                                        <col style="width: 200px">    {{-- Remark --}}
                                    </colgroup>
                                    <thead style="position: sticky; top: 0; z-index: 9;">
                                        <tr>
                                            <th class="text-center">Sr.</th>
                                            <th class="text-center"></th>
                                            <th class="text-center">Challan No.</th>
                                            <th class="text-center">Vehicle</th>
                                            <th class="text-center">Driver</th>
                                            <th class="text-center">Last Date</th>
                                            <th class="text-center">Today</th>
                                            <th class="text-center">Rate</th>
                                            <th class="text-center">Diesel</th>
                                            <th class="text-end">Old K.M</th>
                                            <th class="text-end">New K.M</th>
                                            <th class="text-end">Amount</th>
                                            <th class="text-end">Diff</th>
                                            <th class="text-end">Average</th>
                                            <th class="text-start">Remark</th>
                                        </tr>
                                    </thead>
                                    <tbody id="diesel_table_body">
                                        @foreach($items as $i => $item)
<tr data-index="{{ $i }}" data-detail-id="{{ $item->id }}" data-is-closed="{{ $item->is_closed ? '1' : '0' }}">
                                            <td class="text-center align-middle fw-bold text-secondary" style="font-size:12px;">
                                                <span class="row-serial">{{ $i + 1 }}</span>
                                            </td>
                                            <td class="text-center p-1 align-middle">
                                                @if($item->is_closed)
                                                    <i class="fa-solid fa-lock text-danger" style="font-size:12px;" title="Closed by Voucher"></i>
                                                @else
                                                    <button type="button" class="delete-row-btn delete-row non-selectable" title="Delete row">
                                                        <i class="fa-solid fa-trash-can" style="font-size:11px;"></i>
                                                    </button>
                                                @endif
                                            </td>
                                            <td>
                                                <input type="text" class="form-control row-challan_number" placeholder="" value="{{ $item->challan_number }}" {{ $item->is_closed ? 'readonly tabindex="-1"' : '' }}>
                                            </td>
                                            <td><input type="text" class="form-control row-picker row-vehicle" data-picker="vehicles" placeholder="Enter Vehicle" data-picked-id="{{ $item->vehicle_id }}" value="{{ $item->vehicle ? $item->vehicle->name : '' }}" {{ $item->is_closed ? 'readonly tabindex="-1" disabled' : '' }}></td>
                                            <td><input type="text" class="form-control row-picker row-driver" data-picker="drivers" placeholder="Enter Driver" data-picked-id="{{ $item->driver_id }}" value="{{ $item->driver ? $item->driver->account->name : '' }}" {{ $item->is_closed ? 'readonly tabindex="-1" disabled' : '' }}></td>
                                            <td><input type="text" class="form-control date-format row-last-date" placeholder="DD-MM-YYYY" value="{{ $item->last_date ? \Carbon\Carbon::parse($item->last_date)->format('d-m-Y') : '' }}" {{ $item->is_closed ? 'readonly tabindex="-1" disabled' : '' }}></td>
                                            <td><input type="text" class="form-control date-format row-today" placeholder="DD-MM-YYYY" value="{{ $item->today_date ? \Carbon\Carbon::parse($item->today_date)->format('d-m-Y') : '' }}" {{ $item->is_closed ? 'readonly tabindex="-1" disabled' : '' }}></td>
                                            <td>
                                                <input type="text" class="form-control text-end row-rate only-number" placeholder="0.00" value="{{ $item->rate == 0 ?  $diesel->diesel_rate : $item->rate }}" {{ $item->is_closed ? 'readonly tabindex="-1"' : '' }}>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control text-end row-diesel only-number" placeholder="0.00" value="{{ $item->diesel }}" {{ $item->is_closed ? 'readonly tabindex="-1"' : '' }}>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control text-end row-old-km only-number"
                                                    value="{{ $item->old_km }}" placeholder="0" {{ $item->is_closed ? 'readonly tabindex="-1"' : '' }}>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control text-end row-new-km only-number"
                                                    value="{{ $item->new_km }}" placeholder="0" {{ $item->is_closed ? 'readonly tabindex="-1"' : '' }}>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control text-end row-amount only-number"
                                                    placeholder="0.00" value="{{ $item->amount }}" {{ $item->is_closed ? 'readonly tabindex="-1"' : '' }}>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control text-end row-diff only-number"
                                                    placeholder="0" readonly tabindex="-1" value="{{ $item->diff }}">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control text-end row-average only-number"
                                                    placeholder="0.00" readonly tabindex="-1" value="{{ $item->average }}">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control row-remark" placeholder="" value="{{ $item->remark }}" {{ $item->is_closed ? 'readonly tabindex="-1"' : '' }}>
                                            </td>
                                        </tr>
                                        @endforeach

                                        @for ($i = count($items); $i < 250; $i++)
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
                                                <input type="text" class="form-control row-challan_number" placeholder="">
                                            </td>
                                            <td><input type="text" class="form-control row-picker row-vehicle" data-picker="vehicles" placeholder="Enter Vehicle"></td>
                                            <td><input type="text" class="form-control row-picker row-driver" data-picker="drivers" placeholder="Enter Driver"></td>
                                            <td><input type="text" class="form-control date-format row-last-date" placeholder="DD-MM-YYYY"></td>
                                            <td><input type="text" class="form-control date-format row-today" placeholder="DD-MM-YYYY"></td>
                                            <td>
                                                <input type="text" class="form-control text-end row-rate only-number" placeholder="0.00">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control text-end row-diesel only-number" placeholder="0.00">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control text-end row-old-km only-number"
                                                    value="0" placeholder="0">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control text-end row-new-km only-number"
                                                    value="0" placeholder="0">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control text-end row-amount only-number"
                                                    placeholder="0.00">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control text-end row-diff only-number"
                                                    placeholder="0" readonly tabindex="-1">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control text-end row-average only-number"
                                                    placeholder="0.00" readonly tabindex="-1">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control row-remark" placeholder="">
                                            </td>
                                        </tr>
                                        @endfor
                                    </tbody>
                                    <tfoot style="position: sticky; bottom: 0; z-index: 9;">
                                        <tr>
                                            <td colspan="8" class="text-end pe-3 fw-bold">Total:</td>
                                            <td>
                                                <input type="text" id="total_diesel" class="form-control text-end fw-bold"
                                                    value="0.00" disabled>
                                            </td>
                                            <td colspan="2"></td>
                                            <td>
                                                <input type="text" id="total_amount_footer" class="form-control text-end fw-bold"
                                                    value="0.00" disabled>
                                            </td>
                                            <td colspan="3"></td>
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
                                 placeholder="narration" value="{{ $diesel->narration }}">
                        </div>

                        {{-- Expense Total & Save --}}
                        <div class="col-lg-4">
                            <div class="d-flex gap-2">
                                 <div>
                                    <label class="summary-label fw-bold">No of Vehicles</label>
                                    <input type="text" id="no_of_vehicles" name="no_of_vehicles"
                                        class="form-control text-end remaining-balance-box fw-bold"
                                        value="0.00" readonly>
                                </div>
                                <div>
                                    <label class="summary-label fw-bold">Total Amount</label>
                                    <input type="text" id="total_amount" name="total_amount"
                                        class="form-control text-end expense-total-box fw-bold fs-5"
                                        value="0.00" readonly>
                                </div>
                                
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary waves-effect" id="save_btn">
                                        <i class="fa-solid fa-pencil me-1"></i> Update
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
    const dieselUpdateUrl = "{{ route('diesel.update', $diesel->id) }}";
    const getVehicleLatestDataUrl = "{{ route('diesel.get-vehicle-latest-data') }}";

    window._pickerMasterData = {
        vehicles: {!! collect($vehicles)->map(fn($v) => ['id' => is_array($v) ? $v['id'] : $v->id, 'text' => is_array($v) ? ($v['vehicle_number'] ?? $v['name']) : ($v->vehicle_number ?? $v->name)])->toJson() !!},
        drivers: {!! collect($drivers)->map(fn($d) => ['id' => is_array($d) ? $d['id'] : $d->id, 'text' => is_array($d) ? ($d['account']['name'] ?? '') : ($d->account->name ?? '')])->toJson() !!}
    };
    
</script>
<script src="{{ asset('js/modules/diesel/edit.js') }}?v={{ time() }}"></script>
@endsection
