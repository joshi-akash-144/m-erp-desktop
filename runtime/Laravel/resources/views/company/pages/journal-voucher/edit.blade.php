@extends('company.layout.app')
@section('title', 'Journal Voucher Edit')
@section('css')
<link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}"></link>
<style>
    .m-erp-po-row-1 {
        grid-template-columns: 170px 140px 140px 0.3fr 0.3fr 0.3fr 0.4fr 1fr;
    }

    .m-erp-po-row-2 {
        grid-template-columns: 0.4fr 0.6fr 190px 170px 1fr 1fr;
    }


    @media (max-width: 992px) {

        .m-erp-po-row-1,
        .m-erp-po-row-2 {
            grid-template-columns: 1fr;
        }
    }


    table tfoot {
        padding: 36px 5px !important;
        border: #dee2e6 solid 1px !important;
        background-color: #e6f0fb !important;
    }

    #ref_current_row_preview tbody tr td {
        padding: 0.25rem 0.5rem !important;
        font-family: monospace !important;
        font-size: 0.875rem !important;
    }

    #pending_ref_table_body tr td {
        padding: 0.5rem 0.5rem !important;
        /* font-family: monospace !important; */
        font-size: 1rem !important;
    }
</style>
@endsection


@section('content')
<div class="page-wrapper">
    <!-- ✅ Page Header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-file-invoice"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Journal Voucher
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-orange text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-pen me-1"></i> EDIT ENTRY
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('back.to.previous') }}"
                            class="btn btn-sm btn-outline-dark back-btn">
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

                <!-- 🔹 Title & Subtitle -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card border-0 border-start border-4 border-primary">
                        <div class="card-body shadow p-2">
                            <h3 class="page-title font-monospace">
                                <i class="fa-solid fa-file-invoice me-2 text-primary"></i>
                                Journal Voucher
                            </h3>
                            <span class="ribbon ribbon-bookmark bg-lime text-uppercase">
                                <i class="fa-solid fa-plus me-1"></i> Edit Entry
                            </span>

                        </div>
                    </div>
                </div>

                <!-- 🔹 Action Buttons -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                    <div class="card border-0 border-end border-4 border-primary">
                        <div class="card-body shadow-sm p-2">
                            <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">

                                <a href="{{ route('back.to.previous') }}"
                                    class="btn btn-sm btn-outline-dark back-btn">
                                    <i class="fa-solid fa-arrow-left me-1"></i> Back
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-info waves-effect"
                                    onclick="showShortcutsHelp()" title="Keyboard Shortcuts (F1)"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom">
                                    <i class="fa-solid fa-keyboard"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>  --}}
    <!-- ✅ Page Body -->
    <div class="page-body">
        <div class="container-xl">
            <div class="card p-2 shadow-sm">
                @include('company.pages.journal-voucher._form', ['formMode' => 'edit'])
            </div>
        </div>
    </div>
</div>
@include('company.pages.journal-voucher._ref_modal')
@include('company.pages.journal-voucher._pending_ref_modal')
@endsection
<script>
    // const allLedger = @json($ledgerAccounts);
    const ledgerAccountsUrl = "{{ route('lookup.preload.ledger.accounts') }}";
    const closingBalanceUrl = "{{ route('account-balance.closing') }}";
    const pendingRefUrl = "{{ route('references.pending') }}";
    const storeJournalVoucher = "{{ route('journal-vouchers.store') }}";
    const indexJournalVoucher = "{{ route('journal-vouchers.index') }}";
    const editJournalVoucher = "{{ route('journal-vouchers.edit') }}";
    const updateJournalVoucher = "{{ url('/vouchers/journal-vouchers') }}";
</script>

@section('script')
<script src="{{ asset('js/modules/journal-voucher/create.js') }}?v={{ hash_file('md5', public_path('js/modules/journal-voucher/create.js')) }}"></script>
<script src="{{ asset('js/modules/journal-voucher/edit.js') }}?v={{ hash_file('md5', public_path('js/modules/journal-voucher/edit.js')) }}"></script>
@endsection