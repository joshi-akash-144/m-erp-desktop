@extends('company.layout.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
<style>
    :root {
        --cheque-primary: #1a56db;
        --cheque-gradient: linear-gradient(120deg, #1a56db 0%, #4dabf7 60%, #74c0fc 100%);
    }

    /* Hero Banner */
    .cheque-hero {
        background: var(--cheque-gradient);
        border-radius: 12px 12px 0 0;
        height: 64px;
        position: relative;
    }
    .cheque-hero-icon {
        background: var(--cheque-gradient);
        width: 76px; height: 76px;
        border-radius: 12px;
        border: 4px solid #fff;
        box-shadow: 0 4px 16px rgba(26,86,219,.25);
        position: absolute;
        bottom: -38px; left: 24px;
        display: flex; align-items: center; justify-content: center;
        color: #fff;
    }

    /* Modal Styles */
    .modal-header.cheque-modal-header {
        background: var(--cheque-gradient);
        color: #fff;
        border-radius: 12px 12px 0 0;
        padding: 1rem 1.5rem;
    }
    .modal-header.cheque-modal-header .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.85;
    }
    .modal-content {
        border: none;
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0,0,0,.18);
    }
    .cheque-section-title {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #6b7280;
        margin-bottom: .5rem;
        padding-bottom: .25rem;
        border-bottom: 1px solid #e5e7eb;
    }
    .form-check-group {
        display: flex;
        gap: 1.5rem;
        flex-wrap: wrap;
    }
    .form-check-group .form-check {
        display: flex;
        align-items: center;
        gap: .45rem;
        margin: 0;
    }
    .form-check-group .form-check-input {
        width: 1.15em;
        height: 1.15em;
        cursor: pointer;
        margin: 0;
    }
    .form-check-group .form-check-label {
        font-size: .875rem;
        cursor: pointer;
    }

    /* Table action buttons */
    .btn-icon-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px; height: 30px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        transition: background .15s;
    }
    .btn-icon-action.edit  { background: #e0f2fe; color: #0369a1; }
    .btn-icon-action.edit:hover  { background: #0369a1; color: #fff; }
    .btn-icon-action.del   { background: #fee2e2; color: #dc2626; }
    .btn-icon-action.del:hover   { background: #dc2626; color: #fff; }

    /* Status badges */
    .badge-approved   { background:#d1fae5; color:#065f46; }
    .badge-pending    { background:#fef3c7; color:#92400e; }
    .badge-bool-yes   { background:#dcfce7; color:#166534; }
    .badge-bool-no    { background:#f3f4f6; color:#6b7280; }
</style>
@endsection

@section('content')
<div class="page-body pt-4">
    <div class="container-xl">

        {{-- Hero Banner --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="cheque-hero">
                <div class="cheque-hero-icon">
                    <i class="fa-solid fa-money-check" style="font-size:26px;"></i>
                </div>
            </div>
            <div class="px-4 pb-3" style="padding-top:50px !important;">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h3 class="mb-0 fw-bold">Manual Cheque Register</h3>
                        <div class="text-muted small mt-1">Create and manage manual cheque entries</div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('dashboard') }}"
                            class="btn btn-outline-secondary btn d-inline-flex align-items-center gap-1">
                            @include('icons.prev', ['size' => 15])
                            Dashboard
                        </a>
                        <a href="{{ route('manual-cheques.create') }}"
                            class="btn btn-primary btn d-inline-flex align-items-center gap-1">
                            @include('icons.plus', ['size' => 16])
                            Print Cheque
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter + Table --}}
        <div class="card border-0 shadow-sm rounded-4">

            {{-- Filter header --}}
            <div class="card-header bg-transparent border-bottom px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="d-flex align-items-center justify-content-center rounded-3 bg-secondary-subtle text-secondary"
                        style="width:32px;height:32px;flex-shrink:0;">
                        @include('icons.filter', ['size' => 16])
                    </span>
                    <div class="fw-semibold lh-1">Filters</div>
                </div>
                <div class="card-actions">
                    <button type="button" class="btn-action border-0 bg-transparent"
                        data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                        @include('icons.chevron-down')
                    </button>
                </div>
            </div>

            <div class="collapse show" id="filterCollapse">
                <div class="card-body border-bottom px-4 py-3">
                    <div class="row g-3">
                        <div class="col-md-4 col-lg-3">
                            <label class="form-label fw-semibold small">Search</label>
                            <div class="input-group input-group-flat">
                                <span class="input-group-text">@include('icons.search', ['size' => 15])</span>
                                <input type="text" id="filter_search" class="form-control" placeholder="Name, account…" autocomplete="off">
                            </div>
                        </div>                        
                        <div class="col-md-4 col-lg-3 d-flex align-items-end gap-2">
                            <button id="filter_apply" class="btn btn-primary flex-fill">
                                @include('icons.filter', ['size' => 16]) Apply
                            </button>
                            <button id="filter_clear" class="btn btn-outline-secondary flex-fill">
                                Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body p-0">
                <div id="manual_cheque_table"></div>
            </div>
        </div>  

    </div>
</div>

 
<div class="modal fade" id="manualChequeModal" tabindex="-1" aria-labelledby="manualChequeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header cheque-modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3 bg-white bg-opacity-25"
                        style="width:40px;height:40px;">
                        <i class="fa-solid fa-money-check-dollar text-white" style="font-size:18px;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold" id="manualChequeModalLabel">Add Manual Cheque</h5>
                        <div class="small opacity-75" id="modal_subtitle">Fill in the details below</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body px-4 py-4">
                <form id="manual_cheque_form" novalidate>
                    <input type="hidden" id="cheque_id" name="id">

                    {{-- Section: Basic Info --}}
                    <div class="cheque-section-title mb-2">
                        <i class="fa-solid fa-circle-info me-1"></i> Basic Information
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small required" for="cheque_name">name</label>
                            <input type="text" id="cheque_name" name="name" class="form-control"
                                placeholder="Enter payee name" autocomplete="off">
                            <div class="invalid-feedback" id="err_name"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small required" for="cheque_account_id">Account</label>
                            <select id="cheque_account_id" name="account_id" class="form-select">
                                <option value="">— Select Account —</option>
                            </select>
                            <div class="invalid-feedback" id="err_account_id"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small required" for="cheque_amount">amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" id="cheque_amount" name="amount" class="form-control"
                                    placeholder="0.00" min="0" step="0.01" autocomplete="off">
                            </div>
                            <div class="invalid-feedback" id="err_amount"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small required" for="cheque_date_modal">cheque date</label>
                            <input type="text" id="cheque_date_modal" name="cheque_date" class="form-control"
                                value="{{ date('d-m-Y') }}" autocomplete="off">
                            <div class="invalid-feedback" id="err_cheque_date"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small" for="cheque_no_modal">cheque no</label>
                            <input type="text" id="cheque_no_modal" name="cheque_no" class="form-control"
                                placeholder="Enter cheque number" autocomplete="off">
                            <div class="invalid-feedback" id="err_cheque_no"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small required" for="cheque_format_id_modal">Cheque Format</label>
                            <select id="cheque_format_id_modal" name="cheque_format_id" class="form-select">
                                <option value="">— Select Cheque Format —</option>
                                {{-- Options can be populated via JS or passing chequeFormats from controller --}}
                            </select>
                            <div class="invalid-feedback" id="err_cheque_format_id"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small" for="narration_modal">Narration</label>
                            <input type="text" id="narration_modal" name="narration" class="form-control"
                                placeholder="Enter narration" autocomplete="off">
                            <div class="invalid-feedback" id="err_narration"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small" for="second_narration_modal">Second Narration</label>
                            <input type="text" id="second_narration_modal" name="second_narration" class="form-control"
                                placeholder="Enter second narration" autocomplete="off">
                            <div class="invalid-feedback" id="err_second_narration"></div>
                        </div>
                    </div>

                    {{-- Section: Cheque Options --}}
                    <div class="cheque-section-title mb-2">
                        <i class="fa-solid fa-sliders me-1"></i> Cheque Options
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <div class="form-check-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="cheque_payee_pay" name="payee_pay" value="1">
                                    <label class="form-check-label" for="cheque_payee_pay">
                                        <i class="fa-solid fa-user-check text-primary me-1"></i> Payee Pay
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="cheque_rtgs" name="rtgs" value="1">
                                    <label class="form-check-label" for="cheque_rtgs">
                                        <i class="fa-solid fa-bolt text-warning me-1"></i> RTGS
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="cheque_is_approved" name="is_approved" value="1">
                                    <label class="form-check-label" for="cheque_is_approved">
                                        <i class="fa-solid fa-circle-check text-success me-1"></i> Mark as Approved
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>                    
                </form>
            </div>

            <div class="modal-footer px-4 py-3 border-top bg-light rounded-bottom">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> Cancel
                </button>
                <button type="button" id="btn_save_cheque" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span id="btn_save_label">Save Cheque</span>
                </button>
            </div>

        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
<script src="{{ asset('js/libs/tabulator.min.js') }}"></script>
<script>
    const manualChequeConfig = {
        routes: {
            index: "{{ route('manual-cheques.index') }}",
            store: "{{ route('manual-cheques.store') }}",
            accounts: "{{ route('manual-cheques.accounts') }}",
            base: "{{ url('manual-cheques') }}"
        }
    };
</script>
<script src="{{ asset('js/modules/manual-cheque/index.js') }}?v={{ filemtime(public_path('js/modules/manual-cheque/index.js')) }}"></script>
@endsection
