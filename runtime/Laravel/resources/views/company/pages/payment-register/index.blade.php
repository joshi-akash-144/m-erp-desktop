@extends('company.layout.app')
@section('title', 'Payment Register')

@section('css')
<link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
<link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
@endsection

@section('content')
<div class="page-wrapper">

    {{-- Page Header --}}
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
                                Payment Register
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-list"></i> Register
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
    {{--  <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center justify-content-between">

                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card border-0 border-start border-4 border-primary">
                        <div class="card-body shadow p-2">
                            <h3 class="page-title font-monospace">
                                <i class="fa-solid fa-file-invoice-dollar me-2 text-primary"></i>
                                Payment Register
                            </h3>
                            <span class="ribbon ribbon-bookmark bg-blue">
                                <i class="fa-solid fa-eye me-1"></i> REGISTER
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                    <div class="card border-0 border-end border-4 border-primary">
                        <div class="card-body shadow-sm p-2">
                            <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                <a href="{{ route('back.to.previous') }}"
                                    class="btn btn-sm btn-outline-dark back-btn waves-effect">
                                    <i class="fa-solid fa-arrow-left me-1"></i> Back
                                </a>
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

            {{-- Filter Card --}}
            <div class="card mb-2">
                <form id="pr_filter_form" autocomplete="off">
                    <div class="p-3">
                        <div class="row g-2 align-items-end">

                            <div class="col-md-2 col-lg-1">
                                <label class="fs-4">Start-Date</label>
                                <input type="text" id="pr_start_date" class="form-control" placeholder="DD-MM-YYYY">
                            </div>

                            <div class="col-md-2 col-lg-1">
                                <label class="fs-4">End-Date</label>
                                <input type="text" id="pr_end_date" class="form-control" placeholder="DD-MM-YYYY">
                            </div>

                            <div class="col-md-4 col-lg-3">
                                <label class="fs-4">Ledger Account</label>
                                <select id="pr_account_id" class="form-select select2">
                                    <option value="">--Select Payment To--</option>
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}">
                                            {{ $acc->name }}
                                            @if (!empty($acc->city))
                                                {{ '('.$acc->city.')' }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 col-lg-1">
                                <label class="fs-4">Voucher No</label>
                                <select id="pr_voucher_no" class="form-select select2">
                                    <option value="">--Select Voucher No--</option>
                                    @foreach($vouchers as $vouch)
                                        <option value="{{ $vouch->id }}">
                                            {{ $vouch->voucher_serial }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2 col-lg-auto">
                                <label class="fs-4">File No.</label>
                                <input type="text" id="pr_file_number" class="form-control" placeholder="File No." style="min-width:100px;">
                            </div>

                            <div class="col-md-2 col-lg-1">
                                <label class="fs-4">Cheque No.</label>
                                <input type="text" id="pr_cheque_number" class="form-control" placeholder="Enter Cheque No." style="min-width:140px;">
                            </div>

                            <div class="col-lg-auto d-flex align-items-end gap-2">
                                <button id="pr_show_btn" type="button" class="btn btn-primary waves-effect">
                                    @include('icons.filter', ['size' => 18])
                                    Show
                                </button>
                                <button id="pr_clear_btn" type="button" class="btn btn-outline-secondary waves-effect">
                                    @include('icons.filter-clear', ['size' => 18])
                                    Clear
                                </button>
                                <button id="pr_register_print_btn" type="button" class="btn btn-secondary waves-effect">
                                    <i class="fa-solid fa-print me-1"></i> Register Print
                                </button>
                            </div>

                            <!-- <div class="col-lg-auto d-flex align-items-end gap-1 pb-1">
                                <input type="checkbox" id="pr_with_deleted" class="form-check-input mt-0" value="1">
                                <label for="pr_with_deleted" class="form-label mb-0">Deleted</label>
                            </div> -->

                        </div>
                    </div>
                </form>
            </div>

            {{-- Tabulator Table --}}
            <div class="card sm-shadow rounded-0 bg-light">
                <div class="card-body">
                    <div id="payment_register_table"></div>
                </div>
            </div>

        </div>
    </div>
</div>
@include('company.pages.payment-voucher._view_modal')
@include('company.partials._shortcuts-bar', [
'barModuleLabel' => 'Payment Register',
'barModuleShortcuts' => [
['key' => 'Alt+L', 'label' => 'Ledger', 'type' => 'info', 'permission' => 'ledger_report.list', 'url' => route('ledger-report.index')],
],
])
@endsection

<script>
    const prIndexUrl = "{{ route('payment-register.index') }}";
    const prPrintUrl = "{{ route('payment-register.print') }}";
    const prDeleteUrl = "{{ route('payment-vouchers.destroy', ':id') }}";
    const prViewUrl = "{{ route('payment-vouchers.view', ':id') }}";
    const paymentVoucherAdvicePrintUrl = "{{ route('payment-vouchers.payment_advice_print', ':id') }}";
</script>

@section('script')
<script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
<script src="{{ asset('js/modules/payment-register/index.js') }}?v={{ hash_file('md5', public_path('js/modules/payment-register/index.js')) }}"></script>
@endsection