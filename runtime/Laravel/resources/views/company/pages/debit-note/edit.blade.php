@extends('company.layout.app')
@section('title', 'Debit Note – Edit')

@section('css')
<link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
<style>
    .dn-row-1 {
        grid-template-columns: 0.8fr 1fr 1fr 2fr 1.5fr 1fr;
    }

    @media (max-width: 992px) {
        .dn-row-1 { grid-template-columns: 1fr; }
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
                            Debit Note (Purchase Return)
                        </h2>
                        <span class="badge badge-pill bg-warning text-white fw-bold text-uppercase px-3 py-1" style="font-size:0.75rem;">
                            <i class="fa-solid fa-pen me-1"></i> Edit Entry
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('debit-notes.create') }}" class="btn btn-sm btn-outline-success">
                            <i class="fa-solid fa-plus me-1"></i> New
                        </a>
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
                @include('company.pages.debit-note._form', ['formMode' => 'edit'])
            </div>
        </div>
    </div>

</div>

@include('company.partials._shortcuts-bar', [
    'barModuleLabel'     => 'Debit Note Edit',
    'barModuleShortcuts' => [
        ['key' => 'Alt+L', 'label' => 'Debit Note List', 'type' => 'info', 'permission' => 'debit_note.list', 'url' => route('debit-notes.index')],
        ['key' => 'Alt+G', 'label' => 'Ledger',           'type' => 'info', 'permission' => 'ledger_report.list', 'url' => route('ledger-report.index')],
    ],
])
@endsection

@section('script')
<script>
    const dnUpdateUrl        = "{{ route('debit-notes.update', '__ID__') }}";
    const dnIndexUrl         = "{{ route('debit-notes.index') }}";
    const dnEditFetchUrl     = "{{ route('debit-notes.edit') }}";
    const purchaseInvoicesRoute = "{{ route('debit-notes.purchaseInvoices') }}";
    
    const billSundryData     = @json($billSundry);
    const allLedgers         = @json($allLedgers);
    window.masterItems       = @json($items);
    const editDebitNoteId   = {{ $debitNoteId ?? 'null' }};
    const billDate           = "{{ $billDate }}";
</script>
<script src="{{ asset('js/modules/sales-purchase-common.js') }}?v={{ hash_file('md5', public_path('js/modules/sales-purchase-common.js')) }}"></script>
<script src="{{ asset('js/modules/purchase-invoice/bill-sundry.js') }}?v={{ hash_file('md5', public_path('js/modules/purchase-invoice/bill-sundry.js')) }}"></script>
<script src="{{ asset('js/modules/debit-note/create.js') }}?v={{ filemtime(public_path('js/modules/debit-note/create.js')) }}"></script>
@endsection
