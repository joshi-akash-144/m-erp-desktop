@extends('company.layout.app')

@section('title', 'Transport Payment Register')

@section('css')
<link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
<link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
<style>
    .filter-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: #555;
        margin-bottom: 2px;
    }

    /* Release (first-driver) row — dark header style */
    #trp_register_table .tabulator-row.row-release {
        background-color: #1e3a5f !important;
        color: #fff !important;
        font-weight: 600;
    }

    #trp_register_table .tabulator-row.row-release:hover {
        background-color: #24467a !important;
    }

    #trp_register_table .tabulator-row.row-release .tabulator-cell {
        color: #fff !important;
        border-right-color: #2d4f70 !important;
    }

    /* Additional driver rows */
    #trp_register_table .tabulator-row.row-driver {
        background-color: #fff;
    }

    #trp_register_table .tabulator-row.row-driver:hover {
        background-color: #f0f4f8 !important;
    }

    .tabulator-frozen.tabulator-frozen-right {
        box-shadow: -2px 0 4px rgba(0, 0, 0, .1);
    }

    .voucher-block {
        border: 1px solid #dee2e6;
        border-radius: 6px;
        margin-bottom: 10px;
    }

    .voucher-block-header {
        background: #1e3a5f;
        color: #fff;
        padding: 5px 10px;
        border-radius: 5px 5px 0 0;
        font-weight: 700;
        font-size: 0.87rem;
    }

    #detail_ref_table td,
    #detail_ref_table th {
        padding: 3px 8px !important;
        font-size: 0.84rem !important;
        border: #dee2e6 solid 1px !important;
    }

    #detail_vouchers_table .tabulator-cell {
        vertical-align: top !important;
        align-items: flex-start !important;
        padding-top: 12px !important;
    }
</style>
@endsection

@section('content')
<div class="page-wrapper">

    {{-- Page Header --}}
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background:linear-gradient(145deg,#fff,#f8f9fa);">
                <div class="card-body p-2 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-success rounded-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-success-lt text-success rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width:38px;height:38px;">
                            <i class="fs-3 fa-solid fa-file-invoice-dollar"></i>
                        </div>
                        <h2 class="page-title text-success fw-bolder mb-0" style="font-size:1.2rem;">Transport Payment Register</h2>
                        <span class="badge badge-pill bg-success-lt text-success fw-bold px-3 py-1" style="font-size:0.75rem;">
                            <i class="fa-solid fa-truck-fast me-1"></i> Released Payments
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

            {{-- Filters --}}
            <form action="">
                <div class="card mb-2">
                    <div class="card-body p-2">
                        <div class="row g-2 align-items-end">


                            <div class="col-md-2">
                                <label class="filter-label">From Date</label>
                                <input type="text" id="from_date" class="form-control form-control-sm" placeholder="DD-MM-YYYY">
                            </div>

                            <div class="col-md-2">
                                <label class="filter-label">To Date</label>
                                <input type="text" id="to_date" class="form-control form-control-sm" placeholder="DD-MM-YYYY">
                            </div>

                            <div class="col-md-3">
                                <label class="filter-label">Bank</label>
                                <select id="filter_bank_id" class="form-select form-select-sm" data-placeholder="All Banks">
                                    <option value="">All Banks</option>
                                    @foreach ($banks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
                                <button type="button" id="btn_apply" class="btn btn-primary waves-effect">
                                    @include('icons.filter',['size'=>16])
                                    Apply
                                </button>
                                <button type="button" id="btn_clear" class="btn btn-outline-secondary waves-effect">
                                    @include('icons.filter-clear',['size'=>16])
                                    Clear
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            </form>

            {{-- Tabulator --}}
            <div class="card">
                <div class="card-body p-0">
                    <div id="trp_register_table"></div>
                </div>
            </div>

        </div>
    </div>

</div>

{{-- ── Detail Modal ──────────────────────────────────────────── --}}
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 95%;">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-file-invoice-dollar me-2"></i> Payment Release Detail
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
                                <div class="col-md-4">
                                    <span class="text-muted small d-block mb-1">Payment Date</span>
                                    <strong id="d_payment_date" class="text-primary">-</strong>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted small d-block mb-1">Bank Name</span>
                                    <strong id="d_bank_name">-</strong>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted small d-block mb-1">Total Amount</span>
                                    <strong id="d_total_amount" class="text-success">-</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <h6 class="fw-bold m-0 text-nowrap"><i class="fa-solid fa-list-check me-1 text-secondary"></i> Settlement Vouchers</h6>
                        <div class="input-icon flex-grow-1 ms-md-3" style="max-width: 850px;">
                            <span class="input-icon-addon"><i class="fa-solid fa-search"></i></span>
                            <input type="text" id="detail_search" class="form-control ps-4" style="padding-left: 2.5rem !important;" placeholder="Search vouchers...">
                        </div>
                    </div>
                    
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            <div id="detail_vouchers_table"></div>
                        </div>
                    </div>
                </div> <!-- End Modal Content -->
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    const trpRegisterListUrl = "{{ route('transport-reference-payment.register.list') }}";
    const trpRegisterDetailUrl = "{{ route('transport-reference-payment.register.detail', ['id' => '__ID__']) }}";
    const trpRegisterPrintUrl = "{{ route('transport-reference-payment.register.print', ['id' => '__ID__']) }}";
    const trpVoucherPrintUrl = "{{ route('transport-reference-payment.voucher.print', ['id' => '__ID__']) }}";
</script>
<script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
<script src="{{ asset('js/modules/transport-reference-payment/register.js') }}?v={{ filemtime(public_path('js/modules/transport-reference-payment/register.js')) }}"></script>
@endsection