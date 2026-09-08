@extends('company.layout.app')
@section('title', 'Credit Note – Create')

@section('css')
<link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
<style>
    .cn-row-1 {
        grid-template-columns: 0.8fr 1fr 1fr 2fr 1.5fr 1fr;
    }

    @media (max-width: 992px) {
        .cn-row-1 { grid-template-columns: 1fr; }
    }

    #item_table { table-layout: fixed; width: 100%; }
    #item_table th, #item_table td { overflow: hidden; white-space: nowrap; }
    #item_table input, #item_table select { width: 100%; box-sizing: border-box; }

    .section-danger  { border-color: var(--tblr-danger) !important; }
    .border-danger   { border-left-color: var(--tblr-danger) !important; }
</style>
@endsection

@section('content')
<div class="page-wrapper">

    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-danger rounded-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width:38px;height:38px;">
                            <i class="fa-solid fa-rotate-left fs-5"></i>
                        </div>
                        <h2 class="page-title text-danger fw-bolder mb-0" style="font-size:1.25rem;">
                            Credit Note (Sale Return)
                        </h2>
                        <span class="badge badge-pill bg-success text-white fw-bold text-uppercase px-3 py-1" style="font-size:0.75rem;">
                            <i class="fa-solid fa-plus me-1"></i> New Entry
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        {{-- @can('credit_note.update')
                            <a href="{{ route('credit-notes.edit') }}" class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                            </a>
                        @endcan --}}
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
            <div class="card p-3 shadow-sm">
                @include('company.pages.credit-note._form', ['formMode' => 'create'])
            </div>
        </div>
    </div>

</div>

@include('company.partials._shortcuts-bar', [
    'barModuleLabel'     => 'Credit Note Create',
    'barModuleShortcuts' => [
        ['key' => 'Alt+L', 'label' => 'Credit Note List', 'type' => 'info', 'permission' => 'credit_note.list', 'url' => route('credit-notes.index')],
        ['key' => 'Alt+G', 'label' => 'Ledger',           'type' => 'info', 'permission' => 'ledger_report.list', 'url' => route('ledger-report.index')],
    ],
])
@endsection

@section('script')
<script>
    const cnStoreUrl         = "{{ route('credit-notes.store') }}";
    const cnIndexUrl         = "{{ route('credit-notes.index') }}";
    const salesInvoicesRoute = "{{ route('credit-notes.salesInvoices') }}";
    const billSundryData     = @json($billSundry);
    const allLedgers         = @json($allLedgers);
    window.masterItems       = @json($items);
    const billDate           = "{{ $billDate }}";
</script>
<script src="{{ asset('js/modules/sales-purchase-common.js') }}?v={{ hash_file('md5', public_path('js/modules/sales-purchase-common.js')) }}"></script>
<script src="{{ asset('js/modules/sales-invoice/bill-sundry.js') }}?v={{ hash_file('md5', public_path('js/modules/sales-invoice/bill-sundry.js')) }}"></script>
<script src="{{ asset('js/modules/credit-note/create.js') }}?v={{ filemtime(public_path('js/modules/credit-note/create.js')) }}"></script>
@endsection
