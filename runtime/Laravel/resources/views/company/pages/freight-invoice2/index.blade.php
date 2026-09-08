@extends('company.layout.app')
@section('title', 'Freight Invoice2 – List')

@section('css')
    <link rel="stylesheet"
        href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    </link>
    <link rel="stylesheet"
        href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    </link>

    <style>
        .m-erp-po-row-1 {
            grid-template-columns: 170px 140px 140px 140px 1fr 1fr 1fr;
        }

        .m-erp-po-row-2 {
            grid-template-columns: 0.8fr 0.6fr 170px 0.7fr 0.7fr 1fr;
        }



        @media (max-width: 992px) {

            .m-erp-po-row-1,
            .m-erp-po-row-2 {
                grid-template-columns: 1fr;
            }
        }

        #item_table {
            table-layout: fixed;
            width: 100%;
        }

        #item_table {
            table-layout: fixed;
            width: 100%;
        }

        #item_table th,
        #item_table td {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #item_table input,
        #item_table select {
            width: 100%;
            box-sizing: border-box;
        }

        .input-icon-addon {
            font-size: 16px;
            color: #dc3545;

        }

        .input-icon-addon:hover {
            color: #bb2d3b;
        }

        .date-input {
            color: black;
            /* Color of text as the user types */
        }

        /* Target the placeholder text specifically across browsers */
        .date-input::placeholder {
            color: #aaaaaa;
            /* Lighter gray color for the 'dd-mm-yyyy' text */
            opacity: 1;
            /* Firefox default is lower opacity, this ensures consistency */
        }

        /* Fix Select2 Multiple UI height */
        .select2-container--bootstrap-5 .select2-selection--multiple {
            max-height: 80px;
            overflow-y: auto;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
            font-size: 0.85rem;
            padding: 0.15rem 0.5rem;
        }
    </style>
@endsection
@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="card border-0 shadow-sm rounded-0"
                    style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                    <div
                        class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-sales rounded-0">

                        <!-- 🔹 Title Section -->
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-sales-lt text-sales rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                                style="width: 38px; height: 38px;">
                                <i class="fs-3 fa-solid fa-cart-plus"></i>
                            </div>
                            <div>
                                <h2 class="page-title text-sales fw-bolder mb-1"
                                    style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                    Freight Invoice2
                                </h2>
                            </div>
                            <div class="ms-md-3">
                                <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-3 py-1"
                                    style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                    <i class="fa-solid fa-list me-1"></i> report
                                </span>
                            </div>
                        </div>

                        <!-- 🔹 Action Buttons -->
                        <div class="d-flex align-items-center gap-2">
                            @canany(['freight_invoice2.print', 'freight_invoice2.export'])
                            <div class="dropdown">
                                <button
                                    class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    @include('icons.upload', ['size' => 19])
                                    Export
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end">
                                    @php
                                    $actions = exportActions('freight-invoice2', 'freight_invoice2');
                                    @endphp

                                    @foreach ($actions as $action)
                                    @can($action['permission'])
                                    <li>
                                        <a class="dropdown-item" href="#" data-route="{{ $action['route']  }}"
                                            @foreach(($action['attrs'] ?? []) as $attr=> $value)
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
                            <div class="d-flex gap-2">
                                @can('freight_invoice2.create')
                                    <a href="{{ route('freight-invoice2.create') }}"
                                        class="btn btn-sm btn-outline-primary waves-effect">
                                        <i class="fa-solid fa-plus me-1"></i> Add New
                                    </a>
                                @endcan

                                <a href="{{ route('back.to.previous') }}"
                                    class="btn btn-sm btn-outline-dark back-btn waves-effect">
                                    <i class="fa-solid fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ✅ Page Body -->
        <div class="page-body">
            <form method="GET">
                <div class="container-xl">
                    <div class="card">
                        <!-- 🔹 Filters Section -->
                        <div class="p-3">
                            <div id='filters' class="row g-2 m-erp-po-row-1">

                                <!-- Start Date -->
                                <div class="col-md-4 col-lg-1 m-erp">
                                    <label for="start_date" class="fs-4 fw-bold" value="">Start Date</label>
                                    <input type="text" name="start_date" id="start_date" placeholder="DD-MM-YYYY"
                                        class="form-control date-input" value="{{ isset($filters['order_date']) }}">
                                </div>

                                <!-- End Date -->
                                <div class="col-md-4 col-lg-1 m-erp">
                                    <label for="end_date" class="fs-4 fw-bold" value="">End Date</label>
                                    <input type="text" name="due_date" id="end_date" placeholder="DD-MM-YYYY"
                                        class="form-control" value="{{ isset($filters['due_date']) }}">
                                </div>

                                <!-- Bill To Party -->
                                <div class="col-md-4 col-lg-2 m-erp">
                                    <label for="account_id" class="fs-4 fw-bold" value="">Bill To </label>
                                    <select name="account_id" id="account_id" class="form-select">
                                        <option value="">Select Name</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <!-- Bill No -->
                                <div class="col-md-4 col-lg-1 m-erp">
                                    <label for="invoice_serial" class="fs-4 fw-bold" value="">Bill No </label>
                                    <input type="number" name="invoice_serial" id="invoice_serial" class="form-control" placeholder="Enter Bill No">
                                </div>

                                <!-- Filter Buttons -->
                                <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
                                    <button id="filter_apply" class="btn btn-primary flex-fill waves-effect">
                                        @include('icons.filter', ['size' => 20])
                                        Apply
                                    </button>
                                    <button id="filter_clear" class="btn btn-outline-secondary flex-fill waves-effect">
                                        @include('icons.filter-clear', ['size' => 20])
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 🔹 Freight Table -->
                        <div class="card-body p-0">
                            <div id="freight_invoice_2_table">
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
            </form>
            <div class="container-xl">
                <div class="card p-2 shadow-sm">
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')

    <script>
        const freightInvoice2ListUrl = "{{ route('freight-invoice2.index') }}";
        const freightPrintUrl = "{{ route('freight-invoice2.print-invoice', ':id') }}";
        const freightEditUrl = "{{ route('freight-invoice2.edit', ':id') }}";
    </script>
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
    <script
        src="{{ asset('js/modules/freight-invoice2/index.js') }}?v={{ hash_file('md5', public_path('js/modules/freight-invoice2/index.js')) }}"></script>

@endsection