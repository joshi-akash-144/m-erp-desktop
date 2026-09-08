@extends('company.layout.app')
@section('title', 'Receipt Voucher Register')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    <style>
        #ref_preview_table thead tr th {
            font-family: monospace;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid rgb(66, 65, 65);
            background-color: #E3E7EB;
        }
        #ref_preview_table tbody tr td {
            font-family: monospace;
            font-size: 13px;
            border: 1px solid rgb(66, 65, 65);
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
                            <i class="fs-3 fa-solid fa-receipt"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Receipt Voucher Register
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-list"></i> report
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                           @canany(['receipt_voucher.print', 'receipt_voucher.export'])
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                @include('icons.upload', ['size' => 19])
                                                Export
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @php
                                                    $actions = exportActions('receipt-vouchers', 'receipt_voucher');
                                                @endphp
                                                @foreach ($actions as $action)
                                                    @can($action['permission'])
                                                        <li>
                                                            <a class="dropdown-item" href="#"
                                                                data-route="{{ $action['route'] }}"
                                                                @foreach(($action['attrs'] ?? []) as $attr => $value)
                                                                    {{ $attr }}="{{ $value }}"
                                                                @endforeach>
                                                                @include($action['icon'])
                                                                {{ $action['label'] }}
                                                            </a>
                                                        </li>
                                                    @endcan
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endcanany

                                    @can('receipt_voucher.create')
                                        <a href="{{ route('receipt-vouchers.create') }}"
                                            class="btn btn-sm btn-outline-primary waves-effect">
                                            <i class="fa-solid fa-plus me-1"></i> Add New
                                        </a>
                                    @endcan

                                    <a href="{{ route('back.to.previous') }}"
                                        class="btn btn-sm btn-outline-dark back-btn waves-effect">
                                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                                    </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
  {{--   <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center justify-content-between">

                <!-- Title -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card border-0 border-start border-4 border-primary">
                        <div class="card-body shadow p-2">
                            <h3 class="page-title font-monospace">
                                <i class="fa-solid fa-receipt me-2 text-primary"></i>
                                Receipt Voucher Register
                            </h3>
                            <span class="ribbon ribbon-bookmark bg-blue">
                                <i class="fa-solid fa-eye me-1"></i> LIST DATA
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                    <div class="card border-0 border-end border-4 border-primary">
                        <div class="card-body shadow-sm p-2">
                            <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                <div class="waves-effect d-flex gap-2">
                                    @canany(['receipt_voucher.print', 'receipt_voucher.export'])
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                @include('icons.upload', ['size' => 19])
                                                Export
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @php
                                                    $actions = exportActions('receipt-vouchers', 'receipt_voucher');
                                                @endphp
                                                @foreach ($actions as $action)
                                                    @can($action['permission'])
                                                        <li>
                                                            <a class="dropdown-item" href="#"
                                                                data-route="{{ $action['route'] }}"
                                                                @foreach(($action['attrs'] ?? []) as $attr => $value)
                                                                    {{ $attr }}="{{ $value }}"
                                                                @endforeach>
                                                                @include($action['icon'])
                                                                {{ $action['label'] }}
                                                            </a>
                                                        </li>
                                                    @endcan
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endcanany

                                    @can('receipt_voucher.create')
                                        <a href="{{ route('receipt-vouchers.create') }}"
                                            class="btn btn-sm btn-outline-primary waves-effect">
                                            <i class="fa-solid fa-plus me-1"></i> Add New
                                        </a>
                                    @endcan

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
        </div>
    </div>  --}}

    <!-- Page Body -->
    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <form id="receipt_voucher_register_form" autocomplete="off">
                    <!-- Filters -->
                    <div class="p-3">
                        <div id="filters" class="row g-2">

                            <!-- Start Date -->
                            <div class="col-md-4 col-lg-1">
                                <label for="start_date" class="fs-4 fw-bold">Start Date</label>
                                <input type="text" name="start_date" id="start_date"
                                    class="form-control" placeholder="DD-MM-YYYY">
                            </div>

                            <!-- End Date -->
                            <div class="col-md-4 col-lg-1">
                                <label for="end_date" class="fs-4 fw-bold">End Date</label>
                                <input type="text" name="end_date" id="end_date"
                                    class="form-control" placeholder="DD-MM-YYYY">
                            </div>

                            <!-- Account Name -->
                            <div class="col-md-4 col-lg-3">
                                <label for="account_id" class="fs-4 fw-bold">Account Name</label>
                                <select id="account_id" name="account_id" class="form-select select2" data-placeholder="Select Account">
                                    <option value="">All Accounts</option>
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Voucher No -->
                            <div class="col-md-4 col-lg-1">
                                <label for="voucher_no" class="fs-4 fw-bold">Voucher No</label>
                                <select id="voucher_no" name="voucher_no" class="form-select select2"
                                    data-placeholder="Select Voucher No">
                                    <option value="">All Voucher No</option>
                                    @foreach ($vouchers as $vouch)
                                        <option value="{{ $vouch->id }}">{{ $vouch->voucher_serial }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Narration -->
                            <div class="col-md-4 col-lg-2">
                                <label for="narration" class="fs-4 fw-bold">Narration</label>
                                <select id="narration" name="narration" class="form-select select2">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>

                            <!-- Filter Buttons -->
                            <div class="col-md-4 col-lg-auto d-flex align-items-end gap-2">
                                <button id="filter_apply" type="button"
                                    class="btn btn-primary flex-fill waves-effect">
                                    @include('icons.filter', ['size' => 20])
                                    Show
                                </button>
                                <button id="filter_clear" type="button"
                                    class="btn btn-outline-secondary flex-fill waves-effect">
                                    @include('icons.filter-clear', ['size' => 20])
                                    Clear
                                </button>
                            </div>

                        </div>
                    </div>
                </form>
            </div>

            <!-- Table + Reference Preview -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card sm-shadow rounded-0 bg-light">
                        <div class="card-body">
                            <div id="receipt_voucher_register_table">
                                <!-- Table content will be loaded dynamically via JS -->
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card sm-shadow rounded-0 bg-light">
                        <div class="card-body p-2">
                            <div class="mb-3">
                                <h4 class="font-monospace d-inline border-bottom border-2 border-primary pb-1">
                                    Reference Preview
                                </h4>
                            </div>

                            <!-- Scrollable table wrapper -->
                            <div class="table-responsive" style="max-height: 480px; min-height: 480px; overflow-y: auto;">
                                <table class="table table-sm table-hover border border-dark-subtle mb-0" id="ref_preview_table">
                                    <colgroup>
                                        <col width="20%">
                                        <col width="20%">
                                        <col width="30%">
                                        <col width="20%">
                                        <col width="10%">
                                    </colgroup>
                                    <thead class="bg-light sticky-top">
                                        <tr>
                                            <th class="font-monospace text-center">Date</th>
                                            <th class="font-monospace text-center">Type</th>
                                            <th class="font-monospace text-center">Ref. No.</th>
                                            <th class="font-monospace text-end">Amount</th>
                                            <th class="font-monospace text-center">D/C</th>
                                        </tr>
                                    </thead>
                                    <tbody id="voucher_reference">
                                        <tr>
                                            <td colspan="5" class="text-center text-muted fw-semibold py-3">
                                                Click on any row to view <span class="text-primary">reference details</span>
                                            </td>
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
    @include('company.pages.receipt-voucher._view_modal')
@include('company.partials._shortcuts-bar', [
    'barModuleLabel'     => 'Receipt Voucher List',
    'barModuleShortcuts' => [
        ['key' => 'Alt+L', 'label' => 'Ledger', 'type' => 'info', 'permission' => 'ledger_report.list', 'url' => route('ledger-report.index')],
    ],
]) 
@endsection

<script>
    const receiptVoucherListUrl            = "{{ route('receipt-vouchers.index') }}";
    const receiptVoucherReferenceUrl       = "{{ route('receipt-vouchers.voucher_reference', ['receiptVoucher' => '__ID__']) }}";
    const receiptVoucherPrintUrl           = "{{ route('receipt-vouchers.print') }}";
    const receiptVoucherIndividualPrintUrl = "{{ route('receipt-vouchers.print-voucher', '') }}";
    const receiptVoucherPrintReceiptUrl    = "{{ route('receipt-vouchers.print-receipt', '') }}";
    const receiptVoucherViewUrl            = "{{ route('receipt-vouchers.view') }}";
    const receiptVoucherExcelUrl           = "{{ route('receipt-vouchers.export.excel') }}";
    const receiptVoucherDeleteUrl          = "{{ route('receipt-vouchers.destroy', ':id') }}";
</script>

@section('script')
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
    <script src="{{ asset('js/modules/receipt-voucher/index.js') }}?v={{ hash_file('md5', public_path('js/modules/receipt-voucher/index.js')) }}"></script>
@endsection
