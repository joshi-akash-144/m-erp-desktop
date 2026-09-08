@extends('company.layout.app')
@section('title', 'TDS Entries')
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
                            <i class="fa-solid fa-file-invoice-dollar"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                TDS Entries
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-chart-line me-1"></i> Report
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                        @canany(['tds_entry.print', 'tds_entry.export'])
                                    <div class="dropdown">
                                        <button
                                            class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            @include('icons.upload', ['size' => 19])
                                            Export
                                        </button>

                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @php
                                                $actions = exportActions('fas.tds-entries', 'tds_entry');
                                            @endphp

                                            @foreach ($actions as $action)
                                                @can($action['permission'])
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="#"
                                                            data-route="{{ $action['route']  }}"
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
                        <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
 {{--    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center justify-content-between">
                <div class="col-12 col-sm-6 col-md-4">
                    <div class="card border-0 border-start border-4 border-primary">
                        <div class="card-body shadow p-2">
                            <h3 class="page-title mb-0">
                                <i class="fas fa-file-invoice-dollar me-2 text-primary"></i>
                                TDS Entries
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4">
                    <div class="card border-0 border-end border-4 border-primary">
                        <div class="card-body shadow-sm p-2 d-flex justify-content-end gap-2">
                            <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark">
                                <i class="fa-solid fa-arrow-left me-1"></i> Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}

    {{-- Page Body --}}
    <div class="page-body">
        <div class="container-xl">

            {{-- Filters --}}
            <div class="card mb-3">
                <div class="card-body py-2">
                    <form id="tds_filter_form" class="row g-2 align-items-end" autocomplete="off">

                        <div class="col-md-2 col-lg-2">
                            <label class="form-label fw-bold">TDS Category</label>
                            <select name="tds_category_id" id="tds_category_id" class="form-select form-select-sm select2">
                                <option value="">All Categories</option>
                                @foreach($tdsCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->section }} — {{ $cat->category_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 col-lg-2">
                            <label class="form-label fw-bold">Voucher Type</label>
                            <select name="voucher_type_id" id="voucher_type_id" class="form-select form-select-sm select2">
                                <option value="">All Types</option>
                                @foreach($voucherTypes as $vt)
                                    <option value="{{ $vt->id }}">{{ $vt->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 col-lg-1">
                            <label class="form-label fw-bold">From Date</label>
                            <input type="text" name="from_date" id="from_date"
                                class="form-control date-format" placeholder="DD-MM-YYYY">
                        </div>

                        <div class="col-md-2 col-lg-1">
                            <label class="form-label fw-bold">To Date</label>
                            <input type="text" name="to_date" id="to_date"
                                class="form-control date-format" placeholder="DD-MM-YYYY">
                        </div>

                        <div class="col-md-2 col-lg-2">
                            <label class="form-label fw-bold">PAN No.</label>
                            <input type="text" name="pan_no" id="pan_no"
                                class="form-control form-control-sm" placeholder="Search PAN...">
                        </div>

                        <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary waves-effect">
                                @include('icons.filter', ['size' => 18])
                                Apply
                            </button>
                            <button type="button" id="filter_clear" class="btn btn-outline-secondary waves-effect">
                                @include('icons.filter-clear', ['size' => 18])
                                Clear
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            {{-- Summary Cards --}}
            {{-- <div class="row g-3 mb-3 d-none" id="summary_cards">
                <div class="col-12 col-md-4">
                    <div class="card text-center border-0 shadow-sm">
                        <div class="card-body py-2">
                            <div class="text-muted small">Total Payment Amount</div>
                            <div class="fs-5 fw-bold text-primary" id="sum_payment_amount">—</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card text-center border-0 shadow-sm">
                        <div class="card-body py-2">
                            <div class="text-muted small">Total TDS Deducted</div>
                            <div class="fs-5 fw-bold text-danger" id="sum_tds_amount">—</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card text-center border-0 shadow-sm">
                        <div class="card-body py-2">
                            <div class="text-muted small">Total Records</div>
                            <div class="fs-5 fw-bold text-success" id="sum_total_records">—</div>
                        </div>
                    </div>
                </div>
            </div> --}}

            {{-- Category-wise Tables --}}
            <div id="tds_output"></div>

        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    const TDS_LIST_URL = "{{ route('fas.tds-entries.list') }}";
</script>
<script src="{{ asset('js/modules/fas/tds-entries.js') }}?v={{ hash_file('md5', public_path('js/modules/fas/tds-entries.js')) }}"></script>
@endsection
