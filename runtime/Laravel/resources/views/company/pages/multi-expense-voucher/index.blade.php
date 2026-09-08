@extends('company.layout.app')
@section('title', 'Multi Expense Voucher Register')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    <style>
        .filter-label { font-size: 0.78rem; font-weight: 600; color: #555; margin-bottom: 2px; }

        /* Parent voucher row */
        #multi_expense_register_table .tabulator-row.row-parent {
            background-color: #1e3a5f !important;
            color: #fff !important;
            font-weight: 600;
        }
        #multi_expense_register_table .tabulator-row.row-parent:hover {
            background-color: #24467a !important;
        }
        #multi_expense_register_table .tabulator-row.row-parent .tabulator-cell {
            color: #fff !important;
            border-right-color: #2d4f70 !important;
        }

        /* Total row */
        #multi_expense_register_table .tabulator-row.row-total {
            background-color: #e3eaf2 !important;
            font-weight: 700;
        }
        #multi_expense_register_table .tabulator-row.row-total .tabulator-cell {
            border-right-color: #9aa5b1 !important;
        }

        /* Item row */
        #multi_expense_register_table .tabulator-row.row-item {
            background-color: #fff;
        }
        #multi_expense_register_table .tabulator-row.row-item:hover {
            background-color: #f0f4f8 !important;
        }

        /* Keep Amount frozen-right styling visible */
        .tabulator-frozen.tabulator-frozen-right {
            box-shadow: -2px 0 4px rgba(0,0,0,.1);
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
                            <i class="fs-3 fa-solid fa-table-list"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Multi Expense Voucher Register
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-3 py-1"
                                style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-list me-1"></i> Register
                            </span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @can('multi_expense_voucher.create')
                        <a href="{{ route('multi-expense.create') }}"
                            class="btn btn-sm btn-outline-primary waves-effect">
                            <i class="fa-solid fa-plus me-1"></i> New Entry
                        </a>
                        @endcan
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

            {{-- Filters --}}
            <div class="card mb-2">
                <div class="card-body p-2">
                    <form>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2 col-lg-1">
                            <label class="filter-label">From Date</label>
                            <input type="text" id="from_date" class="form-control form-control-sm" placeholder="DD-MM-YYYY">
                        </div>

                        <div class="col-md-2 col-lg-1">
                            <label class="filter-label">To Date</label>
                            <input type="text" id="to_date" class="form-control form-control-sm" placeholder="DD-MM-YYYY">
                        </div>

                        <div class="col-md-2 col-lg-1">
                            <label class="filter-label">Voucher No.</label>
                            <select id="filter_voucher" class="form-select form-select-sm select2" data-placeholder="All">
                                <option value="">All</option>
                                @foreach ($vouchers as $v)
                                    <option value="{{ $v }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 col-lg-2">
                            <label class="filter-label">Party (Cr)</label>
                            <select id="filter_account" class="form-select">
                                <option value="">All Parties</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account['id'] }}">{{ $account['name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 col-lg-2">
                            <label class="filter-label">Expense Type (Dr)</label>
                            <select id="filter_expense_account" class="form-select">
                                <option value="">All Expense Types</option>
                                @foreach ($expenseAccounts as $expenseAccount)
                                    <option value="{{ $expenseAccount->id }}">{{ $expenseAccount->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 col-lg-3 align-items-end gap-2">
                            <button type="button" id="btn_apply" class="btn btn-primary waves-effect">
                                @include('icons.filter', ['size' => 16])
                                Apply
                            </button>
                            <button type="button" id="btn_clear" class="btn btn-outline-secondary waves-effect">
                                @include('icons.filter-clear', ['size' => 16])
                                Clear
                            </button>
                            <button type="button" id="btn_print_register" class="btn btn-outline-dark waves-effect">
                                <i class="fa-solid fa-print me-1"></i> Print Register
                            </button>
                        </div>
                    </form>
                    </div>
                </div>
            </div>

            {{-- Tabulator --}}
            <div class="card">
                <div class="card-body p-0">
                    <div id="multi_expense_register_table"></div>
                </div>
            </div>

        </div>
    </div>

    <!-- View Modal -->
    <div class="modal fade" id="viewExpenseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 95%;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-regular fa-file-lines me-2"></i> Multi Expense Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light p-3 position-relative" style="min-height: 400px;">
                    
                    <!-- Loading Spinner -->
                    <div id="view_loading_spinner" class="position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center d-none" style="background: rgba(255,255,255,0.8); z-index: 10;">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="mt-2 text-muted fw-bold">Fetching details...</div>
                        </div>
                    </div>

                    <!-- Modal Content -->
                    <div id="view_modal_content" class="d-none">
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body p-3">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <span class="text-muted small d-block mb-1">Voucher No</span>
                                        <strong id="view_voucher_no" class="text-primary">-</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="text-muted small d-block mb-1">Date</span>
                                        <strong id="view_date">-</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="text-muted small d-block mb-1">Party Name (Cr)</span>
                                        <strong id="view_party_cr">-</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="text-muted small d-block mb-1">Expense Type (Dr)</span>
                                        <strong id="view_expense_dr">-</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <h6 class="fw-bold m-0 text-nowrap"><i class="fa-solid fa-list-check me-1 text-secondary"></i> Expense Items</h6>
                            <div class="input-icon flex-grow-1 ms-md-3" style="max-width: 850px;">
                                <span class="input-icon-addon"><i class="fa-solid fa-search"></i></span>
                                <input type="text" id="view_item_search" class="form-control ps-4" style="padding-left: 2.5rem !important;" placeholder="Search items...">
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-0">
                                <div id="view_items_table"></div>
                            </div>
                        </div>
                        
                        <div class="mt-3 text-end">
                            <h4 class="mb-0 fw-bold">Total Amount: <span id="view_total_amount" class="text-primary ms-2">0.00</span></h4>
                        </div>
                    </div> <!-- End Modal Content -->
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('script')
<script>
    const multiExpenseListUrl          = "{{ route('multi-expense.list') }}";
    const multiExpensePrintUrl         = "{{ route('multi-expense.print', ':id') }}";
    const multiExpenseEditUrl          = "{{ route('multi-expense.edit', ':id') }}";
    const multiExpenseDataUrl          = "{{ route('multi-expense.data', ':id') }}";
    const multiExpenseRegisterPrintUrl = "{{ route('multi-expense.register-print') }}";
</script>
<script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
<script src="{{ asset('js/modules/multi-expense/index.js') }}?v={{ hash_file('md5', public_path('js/modules/multi-expense/index.js')) }}"></script>
@endsection
