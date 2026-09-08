@extends('company.layout.app')
@section('title', 'Dairy Analysis Register')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <style>        
        .form-control-sm, .form-select-sm { padding: 0.25rem 0.5rem; font-size: 0.85rem; }        */
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
                            <i class="fs-3 fa-solid fa-flask"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Dairy Analysis Register
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-list"></i> Report
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                        {{-- Export Dropdown (only for users with print/export permission) --}}
                        @canany(['dairy_analysis.print', 'dairy_analysis.export'])
                            <div class="dropdown">
                                <button
                                    class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    @include('icons.upload', ['size' => 19])
                                    Export
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end">
                                    @php
                                        $actions = exportActions('dairy-analysis', 'dairy_analysis');
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

                        {{-- Add Dairy Analysis Button --}}
                        @can('dairy_analysis.create')
                        <a href="{{ route('dairy-analysis.create') }}"
                            class="btn btn-outline-primary btn-sm"
                            id="addPurchaseOrderBtn">
                            @include('icons.plus', ['size' => 20])
                            Add New
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
           {{--  <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center justify-content-between">

                        <!-- 🔹 Title & Subtitle -->
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="card border-0 border-start border-4 border-primary">
                                <div class="card-body shadow p-2">
                                    <h3 class="page-title font-monospace">                                                
                                        <i class="fa-solid fa-flask me-2 text-primary"></i>
                                        Dairy Analysis Register</h3>  
                                </div>
                            </div>
                        </div>

                        <!-- 🔹 Action Buttons (Export & Add) -->
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                            <div class="card border-0 border-end border-4 border-primary">
                                <div class="card-body shadow-sm p-2">
                                    <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">

                                        @canany(['dairy_analysis.print', 'dairy_analysis.export'])
                                            <div class="dropdown">
                                                <button
                                                    class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    @include('icons.upload', ['size' => 19])
                                                    Export
                                                </button>

                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @php
                                                        $actions = exportActions('dairy-analysis', 'dairy_analysis');
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

                                        <div class="waves-effect d-flex gap-2">
                                            <a href="{{ route('dairy-analysis.create') }}"
                                                class="btn btn-outline-primary btn-sm"
                                                id="addPurchaseOrderBtn">
                                                @include('icons.plus', ['size' => 20])
                                                    Add New
                                            </a>
                                            <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
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
                <!-- 🔹 Filters Section -->
                <form>
                    <div class="p-3">
                        <div class="row g-2" id="filters">
                            <div class="col-md-2 col-lg-1">
                                <label class="filter-label fw-bold">Start Date</label>
                                <input type="text" id="start_date" class="form-control" placeholder="DD-MM-YYYY">
                            </div>
                            <div class="col-md-2 col-lg-1">
                                <label class="filter-label fw-bold">End Date</label>
                                <input type="text" id="end_date" class="form-control" placeholder="DD-MM-YYYY">
                            </div>

                            {{-- @dd($suppliers); --}}
                            <div class="col-md-4 col-lg-2">
                                <label class="filter-label fw-bold">Supplier Name</label>
                                <select id="supplier_id" class="form-select select2" data-placeholder="Select Supplier">
                                    <option value="">Select Supplier</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name . ' ' . '('. $supplier->city . ')' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        
                        <div class="col-md-4 col-lg-1">
                            <label class="filter-label fw-bold">Payment Status</label>
                            <select id="payment_status" class="form-select select2">
                                <option value="">All</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>

                            <!-- Filter Buttons -->
                            <div class="col-md-4 col-lg-auto d-flex align-items-end gap-2 text-nowrap">
                                <button id="filter_apply" class="btn btn-primary flex-fill waves-effect">
                                    @include('icons.filter', ['size' => 20])
                                    Apply
                                </button>
                                <button id="filter_clear" class="btn btn-outline-secondary flex-fill waves-effect">
                                    @include('icons.filter-clear', ['size' => 20])
                                    Clear
                                </button>
                                <button type="button" class="btn btn-outline-orange flex-fill waves-effect" id="mailPdsBtn">
                                    @include('icons.mail', ['size' => 20])
                                    MAIL PDS REPORT
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <!-- 🔹 Table Section -->
                <div class="card-body p-0">
                    <div id="dairy_analysis_table"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('company.pages.dairy-analysis._dairy-analysis-email-modal')

@endsection

@section('script')
    <script>
        const getRegisterDataUrl = "{{ route('dairy-analysis.list') }}";      
        const printAnalysisUrl = "{{ route('dairy-analysis.print', ':id') }}";
        const editAnalysisUrl = "{{ route('dairy-analysis.edit') }}"; 
        const emailPreviewUrl = "{{ route('mail.preview_dairy_analysis_email') }}";
        const emailSendUrl = "{{ route('mail.send_dairy_analysis_email') }}";
        const hugertePath = "{{ asset('js/libs/hugerte') }}";
    </script>
    <script src="{{ asset('js/libs/hugerte/hugerte.min.js') }}"></script>
    <script src="{{ asset('js/modules/dairy-analysis/pds-email-modal.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modules/dairy-analysis/index.js') }}?v={{ hash_file('md5', public_path('js/modules/dairy-analysis/index.js')) }}"></script>
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
@endsection
