@extends('company.layout.app')
@section('title', 'Expense Register')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    <style>
        .filter-label { font-size: 0.78rem; font-weight: 600; color: #555; margin-bottom: 2px; }

        .tabulator .tabulator-calcs-bottom .tabulator-cell {
            background: #2d3a4a !important;
            color: #fff !important;
            font-weight: 700;
        }
        .tabulator .tabulator-calcs-bottom .tabulator-cell span {
            font-weight: 700;
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
                            <i class="fs-3 fa-solid fa-receipt"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Expense Register
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-2 py-1"
                                style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-list me-1"></i> Register
                            </span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">                        
                        {{-- Export Dropdown (only for users with print/export permission) --}}
                        @canany(['expense_register.print', 'expense_register.export'])
                        <div class="dropdown">
                            <button
                                class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                @include('icons.upload', ['size' => 19])
                                Export
                            </button>

                            <ul class="dropdown-menu dropdown-menu-end">
                                @php
                                $actions = exportActions('expense-register', 'expense_register');
                                @endphp

                                @foreach ($actions as $action)
                                @can($action['permission'])
                                <li>
                                    <a class="dropdown-item" href="#" data-route="{{ $action['route'] }}"
                                        @foreach ($action['attrs'] ?? [] as $attr=> $value)
                                        {{ $attr }}="{{ $value }}" @endforeach>
                                        @include($action['icon'])
                                        {{ $action['label'] }}
                                    </a>
                                </li>
                                @endcan
                                @endforeach
                            </ul>
                        </div>
                        @endcanany

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
            <form>
                {{-- Filters --}}
                <div class="card mb-2">
                    <div class="card-body p-2">
                        <div class="row g-2 align-items-end">

                            <div class="col-md-2 col-lg-1">
                                <label class="filter-label">Start Date</label>
                                <input type="text" id="start_date" class="form-control form-control-sm date-format" placeholder="DD-MM-YYYY">
                            </div>

                            <div class="col-md-2 col-lg-1">
                                <label class="filter-label">End Date</label>
                                <input type="text" id="end_date" class="form-control form-control-sm date-format" placeholder="DD-MM-YYYY">
                            </div>

                                <div class="col-md-3 col-lg-2">
                                    <label class="filter-label">Party Name</label>
                                    <select id="filter_party_name" class="form-select">
                                        <option value="">-- Select Party Name --</option>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3 col-lg-2">
                                    <label class="filter-label">Reference Number</label>
                                    <select id="filter_reference_number" class="form-select">
                                        <option value="">-- Select Reference Number --</option>
                                        @foreach ($referenceNumbers as $referenceNumber)
                                            <option value="{{ $referenceNumber->reference_number }}">{{ $referenceNumber->reference_number }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3 col-lg-1">
                                    <label class="filter-label">Vehicle</label>
                                    <select id="filter_vehicle" class="form-select">
                                        <option value="">-- Select Vehicle --</option>
                                        @foreach ($vehicles as $vehicle)
                                            <option value="{{ $vehicle->id }}">{{ $vehicle->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3 col-lg-1">
                                    <label class="filter-label">Voucher Number</label>
                                    <select id="filter_voucher_number" class="form-select">
                                        <option value="">--Select Vouchers No --</option>
                                        @foreach ($voucherNumbers as $voucherNumber)
                                            <option value="{{ $voucherNumber->voucher_serial }}">{{ $voucherNumber->voucher_serial }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3 col-lg-2">
                                    <label class="filter-label">Expense Type</label>
                                    <select id="filter_expense_account" class="form-select">
                                        <option value="">--Select Expense Type --</option>
                                        @foreach ($expenseAccounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
                                    <button type="button" id="btn_apply" class="btn btn-primary waves-effect">
                                        @include('icons.filter', ['size' => 16])
                                        Apply
                                    </button>
                                    <button type="button" id="btn_clear" class="btn btn-outline-secondary waves-effect">
                                        @include('icons.filter-clear', ['size' => 16])
                                        Clear
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>
                </form>
                {{-- Tabulator Table --}}
                <div class="card">
                    <div class="card-body p-0">
                        <div id="expense_register_table"></div>
                    </div>
                </div>

            </div>
        </div>

    </div>
@endsection

@section('script')
<script>
    const expenseRegisterListUrl = "{{ route('expense-register.list') }}";
    const expenseRegisterPrintUrl = "{{ route('expense-register.print') }}";
    const expenseRegisterExportUrl = "{{ route('expense-register.export.excel') }}";
</script>
<script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
<script src="{{ asset('js/modules/expense-register/index.js') }}?v={{ time() }}"></script>
@endsection
