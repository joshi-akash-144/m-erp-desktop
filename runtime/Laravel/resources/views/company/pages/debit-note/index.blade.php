@extends('company.layout.app')
@section('title', 'Debit Note – List')

@section('css')
<link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
<link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
<style>
    .dn-filter-row { grid-template-columns: 160px 160px 1fr 2fr; }
    @media (max-width: 992px) { .dn-filter-row { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
<div class="page-wrapper" style="width: 1700px">

    {{-- Page Header --}}
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
                        <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase px-3 py-1" style="font-size:0.75rem;">
                            <i class="fa-solid fa-list me-1"></i> Report
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @can('debit_note.create')
                            <a href="{{ route('debit-notes.create') }}" class="btn btn-outline-danger btn-sm">
                                <i class="fa-solid fa-plus me-1"></i> New Debit Note
                            </a>
                        @endcan
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
            <div class="card shadow-sm mb-2 p-2">
                <div class="grid-row dn-filter-row row-body">
                    <div class="grid-item">
                        <label class="form-label">From Date</label>
                        <input type="text" id="filter_start_date" class="form-control date-format" placeholder="DD-MM-YYYY">
                    </div>
                    <div class="grid-item">
                        <label class="form-label">To Date</label>
                        <input type="text" id="filter_end_date" class="form-control date-format" placeholder="DD-MM-YYYY">
                    </div>
                    <div class="grid-item">
                        <label class="form-label">Supplier</label>
                        <select id="filter_account_id" class="form-select select2">
                            <option value="">All Suppliers</option>
                            @foreach ($suppliers as $c)
                                <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{--  <div class="grid-item">
                        <label class="form-label">From No.</label>
                        <select id="filter_bill_from" class="form-select select2">
                            <option value="">All</option>
                            @foreach ($dnSerials as $dn)
                                <option value="{{ $dn->debit_note_serial }}">{{ $dn->debit_note_serial }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid-item">
                        <label class="form-label">To No.</label>
                        <select id="filter_bill_to" class="form-select select2">
                            <option value="">All</option>
                            @foreach ($dnSerials as $dn)
                                <option value="{{ $dn->debit_note_serial }}">{{ $dn->debit_note_serial }}</option>
                            @endforeach
                        </select>
                    </div>  --}}
                <div class="col-md-4 col-lg-3 d-flex align-items-end gap-2">
                    <button class="btn btn-primary flex-fill waves-effect" id="apply_filter">
                        @include('icons.filter', ['size' => 18]) Apply
                    </button>
                    <button class="btn btn-outline-secondary flex-fill waves-effect" id="clear_filter">
                        @include('icons.filter-clear', ['size' => 18]) Clear
                    </button>
                </div>
            </div>

            {{-- Summary badges --}}
           {{--  <div class="d-flex gap-2 mb-2 flex-wrap">
                <div class="card border-0 shadow-sm p-2 text-center" style="min-width:130px">
                    <div class="text-muted small">Total Records</div>
                    <div class="fw-bold fs-5" id="badge_total">—</div>
                </div>
                <div class="card border-0 shadow-sm p-2 text-center" style="min-width:130px">
                    <div class="text-muted small">Net Amount</div>
                    <div class="fw-bold fs-5 text-danger" id="badge_net_amount">—</div>
                </div>
            </div> --}}

            {{-- Tabulator Grid --}}
            <div class="card shadow-sm">
                <div id="debit_note_table"></div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('script')
{{-- <script src="{{ asset('js/tabulator.min.js') }}?v={{ hash_file('md5', public_path('js/tabulator.min.js')) }}"></script> --}}
<script>
    const dnListUrl    = "{{ route('debit-notes.index') }}";
    const dnCreateUrl  = "{{ route('debit-notes.create') }}";
    const dnEditUrl    = "{{ route('debit-notes.edit') }}";
</script>
<script src="{{ asset('js/utils/icons.js') }}?v={{ filemtime(public_path('js/utils/icons.js')) }}"></script>
<script src="{{ asset('js/modules/debit-note/index.js') }}?v={{ filemtime(public_path('js/modules/debit-note/index.js')) }}"></script>
@endsection
