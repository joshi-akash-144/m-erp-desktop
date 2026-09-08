@extends('company.layout.app')
@section('title', 'Daybook - List')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    <link rel="stylesheet"
        href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
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
                                Daybook
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-2 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-list me-1"></i> report
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                          {{-- Export Dropdown (only for users with print/export permission) --}}
                            @canany(['daybook_report.print', 'daybook_report.export'])
                            <div class="dropdown">
                                <button
                                    class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    @include('icons.upload', ['size' => 19])
                                    Export
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end">
                                    @php
                                    $actions = exportActions('daybook-report', 'daybook_report');
                                    @endphp

                                    @foreach ($actions as $action)
                                    @can($action['permission'])
                                    <li>
                                        <a class="dropdown-item" href="#" data-route="{{ $action['route']  }}"
                                            @foreach ($action['attrs'] ?? [] as $attr => $value)
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

                    <!-- 🔹 Title & Subtitle -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="card border-0 border-start border-4 border-primary">
                            <div class="card-body shadow p-2">
                                <h3 class="page-title font-monospace">
                                    <i class="fa-solid fa-file-invoice me-2 text-primary"></i>
                                    Daybook
                                </h3>

                            </div>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                        <div class="card border-0 border-end border-4 border-primary">
                            <div class="card-body shadow-sm p-2">
                                <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                    @canany(['daybook_report.print', 'daybook_report.export'])
                                    <div class="dropdown">
                                        <button
                                            class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            @include('icons.upload', ['size' => 19])
                                            Export
                                        </button>

                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @php
                                            $actions = exportActions('daybook-report', 'daybook_report');
                                            @endphp

                                            @foreach ($actions as $action)
                                            @can($action['permission'])
                                            <li>
                                                <a class="dropdown-item" href="#" data-route="{{ $action['route']  }}"
                                                    @foreach ($action['attrs'] ?? [] as $attr => $value)
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
            </div>
        </div> --}}
        <!-- ✅ Page Body -->
        <div class="page-body">
            <div class="container-xl">
                <div class="card">
                    <form id="ledger_form" autocomplete="off">
                        <!-- 🔹 Filters Section -->
                        <div class="p-3">
                            <div id='filters' class="row g-2">
                                <!-- Start Date -->
                                <div class="col-md-4 col-lg-1"> 
                                    <label for="start_date" class="form-label fw-bold">Start Date</label>
                                    <input type="text" name="start_date" id="start_date" class="form-control date-format"
                                        placeholder="DD-MM-YYYY">
                                </div>

                                <!-- End Date -->
                                <div class="col-md-4 col-lg-1">
                                    <label for="end_date" class="form-label fw-bold">End Date</label>
                                    <input type="text" name="end_date" id="end_date" class="form-control date-format"
                                        placeholder="DD-MM-YYYY">
                                </div>

                                <!-- Account Name -->
                                <div class="col-md-4 col-lg-3">
                                    <label for="account_id" class="form-label fw-bold">Account Name</label>
                                    <select id="account_id" name="account_id" class="form-select select2">
                                        <option value="">Account Name</option>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 col-lg-1">
                                    <label for="narration" class="form-label fw-bold">Narration</label>
                                    <select id="narration" name="narration" class="form-select custom-select2">
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    </select>

                                </div>
                                <div class="col-md-4 col-lg-2">
                                    <label for="voucher_type_id" class="form-label fw-bold">Voucher Type</label>
                                    <select id="voucher_type_id" name="voucher_type_id" class="form-select">
                                        <option value="">All Voucher Types</option>
                                        @foreach (config('constants.voucher_type') as $key => $type)
                                            <option value="{{ $key }}">{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </div>


                                <!-- Filter Buttons -->
                                <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
                                    <button id="filter_apply" type="button" class="btn btn-primary flex-fill waves-effect">
                                        @include('icons.filter', ['size' => 20])
                                        Apply
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
                    <!-- 🔹 PURCHASE INVOICE Table -->
                    <div class="card-body p-3 pt-0">
                        <div id="daybook_table">
                            <!-- Table content will be loaded dynamically via JS -->
                        </div>
                    </div>

                    <!-- 🔹 Infinite Scroll Loader -->
                    <div id="scrollLoader" class="card-footer text-center" style="display:none;">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span class="ms-2 text-muted">Loading more records...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        const daybookListUrl = "{{ route('daybook-report.list') }}";
    </script>
    <script
        src="{{ asset('js/modules/report/daybook.js') }}?v={{ hash_file('md5', public_path('js/modules/report/daybook.js')) }}">
    </script>
@endsection
