@extends('company.layout.app')
@section('title', 'Stock Status – List')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}"></link>
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}"></link>
    <style>
    /* ── Compact Tabulator for stock table ── */
    #stock_status_table .tabulator-header .tabulator-col .tabulator-col-content {
        padding: 5px 8px !important;
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #495057;
    }
    #stock_status_table .tabulator-row {
        min-height: 30px !important;
        border-bottom: 1px solid #e9ecef;
    }
    #stock_status_table .tabulator-row .tabulator-cell {
        padding: 4px 8px !important;
        font-size: 0.82rem;
    }
    #stock_status_table .tabulator-row.tabulator-row-even {
        background-color: #f8f9fa;
    }
    #stock_status_table .tabulator-row:hover .tabulator-cell {
        background-color: #e8f0fe !important;
    }
    #stock_status_table .tabulator-footer .tabulator-calcs-holder .tabulator-row .tabulator-cell {
        padding: 5px 8px !important;
        font-size: 0.82rem;
        background: #f1f3f5 !important;
        font-weight: 700;
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
                                Stock Status
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-boxes-stacked me-1"></i> stock
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                        @canany(['stock_status.print', 'stock_status.export'])
                            <div class="dropdown">
                                <button
                                    class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    @include('icons.upload', ['size' => 19])
                                    Export
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end">
                                    @php
                                        $actions = [
                                                [
                                                    'key' => 'print',
                                                    'label' => 'Print',
                                                    'permission' => "stock_status.print",
                                                    'icon' => 'icons.print',
                                                    'route' => route("stock-status.print-item-wise"),
                                                    'attrs' => [
                                                        'id' => 'export_print_btn',
                                                        'data-format' => 'print',
                                                        'data-type' => 'print',
                                                    ],
                                                ],
                                                [
                                                    'key' => 'excel',
                                                    'label' => 'Excel',
                                                    'permission' => "stock_status.export",
                                                    'icon' => 'icons.xlsx',
                                                    'route' => route("stock-status.export.item-wise"),
                                                    'attrs' => [
                                                        'id' => 'export_xlsx_item_wise_btn',
                                                        'data-format' => 'xlsx',
                                                        'data-type' => 'excel',
                                                    ],
                                                ]
                                            ];
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
              {{--   <div class="page-header d-print-none">
                    <div class="container-fluid">
                        <div class="row g-2 align-items-center justify-content-between">

                            <!-- 🔹 Title & Subtitle -->
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                <div class="card border-0 border-start border-4 border-primary">
                                    <div class="card-body shadow p-2">
                                        <h3 class="page-title font-monospace">                                            
                                            <i class="fa-solid fa-file-invoice me-2 text-primary"></i>
                                            Stock Status
                                        </h3>
                                    </div>
                                </div>
                            </div>

                            <!-- 🔹 Action Buttons -->
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                                <div class="card border-0 border-end border-4 border-primary">
                                    <div class="card-body shadow-sm p-2">
                                        <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                                @canany(['stock_status.print', 'stock_status.export'])
                                                    <div class="dropdown">
                                                        <button
                                                            class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            @include('icons.upload', ['size' => 19])
                                                            Export
                                                        </button>

                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            @php
                                                                $actions = [
                                                                        [
                                                                            'key' => 'print',
                                                                            'label' => 'Print',
                                                                            'permission' => "stock_status.print",
                                                                            'icon' => 'icons.print',
                                                                            'route' => route("stock-status.print-item-wise"),
                                                                            'attrs' => [
                                                                                'id' => 'export_print_btn',
                                                                                'data-format' => 'print',
                                                                                'data-type' => 'print',
                                                                            ],
                                                                        ],
                                                                        [
                                                                            'key' => 'excel',
                                                                            'label' => 'Excel',
                                                                            'permission' => "stock_status.export",
                                                                            'icon' => 'icons.xlsx',
                                                                            'route' => route("stock-status.export.item-wise"),
                                                                            'attrs' => [
                                                                                'id' => 'export_xlsx_item_wise_btn',
                                                                                'data-format' => 'xlsx',
                                                                                'data-type' => 'excel',
                                                                            ],
                                                                        ]
                                                                    ];
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

                                            <!-- <a href="#" class="btn btn-sm btn-outline-dark back-btn">
                                                <i class="fa-solid fa-arrow-left me-1"></i> Back
                                            </a> -->
                                            <button type="button" class="btn btn-sm btn-outline-info waves-effect" onclick="showShortcutsHelp()"
                                                title="Keyboard Shortcuts (F1)" data-bs-toggle="tooltip" data-bs-placement="bottom">
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
                    <div class="container-fluid">
                        <div class="card">
                            <!-- 🔹 Filters Section -->
                            <div class="p-3 border-bottom">
                                <div class="d-flex flex-wrap gap-3 align-items-end">

                                    {{-- Filter Type Toggle --}}
                                    <div>
                                        <div class="fw-semibold small mb-1 text-muted">Filter Type</div>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <input type="radio" class="btn-check" name="filter_mode" id="mode_as_at" value="as_at" checked>
                                            <label class="btn btn-outline-primary" for="mode_as_at">
                                                <i class="fa-solid fa-calendar-check me-1"></i> As At Date
                                            </label>
                                            <input type="radio" class="btn-check" name="filter_mode" id="mode_range" value="range">
                                            <label class="btn btn-outline-primary" for="mode_range">
                                                <i class="fa-solid fa-calendar-range me-1"></i> Date Range
                                            </label>
                                        </div>
                                    </div>

                                    {{-- As At Date field (default visible — Tally/Busy style) --}}
                                    <div id="as_at_fields">
                                        <div class="fw-semibold small mb-1 text-muted">As At Date</div>
                                        <input type="text" id="as_at_date" placeholder="DD-MM-YYYY"
                                            class="form-control form-control-sm date-input" style="width:150px;"
                                            value="{{ \Carbon\Carbon::now()->format('d-m-Y') }}">
                                    </div>

                                    {{-- Date Range fields (hidden on load) --}}
                                    <div id="range_fields" class="gap-2 align-items-end" style="display:none;">
                                        <div>
                                            <div class="fw-semibold small mb-1 text-muted">Start Date</div>
                                            <input type="text" name="start_date" id="start_date" placeholder="DD-MM-YYYY"
                                                class="form-control form-control-sm date-input" style="width:135px;">
                                        </div>
                                        <div>
                                            <div class="fw-semibold small mb-1 text-muted">End Date</div>
                                            <input type="text" name="end_date" id="end_date" placeholder="DD-MM-YYYY"
                                                class="form-control form-control-sm date-input" style="width:135px;">
                                        </div>
                                    </div>

                                    {{-- Apply / Clear --}}
                                    <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
                                        <button type="submit" id="filter_apply" class="btn btn-primary waves-effect">
                                            @include('icons.filter', ['size' => 20])
                                            Apply
                                        </button>
                                        <button type="button" id="filter_clear" class="btn btn-outline-secondary waves-effect">
                                            @include('icons.filter-clear', ['size' => 20])
                                            Clear
                                        </button>
                                    </div>

                                </div>
                            </div>

                            {{-- 🔹 Toolbar: search + selected-date badge --}}
                            <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
                                <div class="input-group input-group-sm" style="max-width:320px;">
                                    <span class="input-group-text bg-white">
                                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                                    </span>
                                    <input type="text" id="stock_search" class="form-control" placeholder="Search item…">
                                    <button class="btn btn-outline-secondary" id="stock_search_clear" type="button" style="display:none;">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                                <div class="ms-auto flex-shrink-0">
                                    <span class="badge bg-blue-lt text-blue px-2 py-1" style="font-size:.8rem;">
                                        <i class="fa-regular fa-calendar me-1"></i>
                                        <span id="stock_date_badge_text"></span>
                                    </span>
                                </div>
                            </div>

                            <!-- 🔹 Stock Status Table -->
                            <div class="card-body p-0" style="position:relative; min-height:200px;">
                                {{-- Overlay loader (Tally/Busy style) --}}
                                <div id="stock_inner_loader"
                                     style="display:none; position:absolute; inset:0; min-height:200px; background:rgba(255,255,255,0.88); z-index:10; flex-direction:column; align-items:center; justify-content:center;">
                                    <div class="spinner-border text-primary mb-2" role="status">
                                        <span class="visually-hidden">Loading…</span>
                                    </div>
                                    <span class="text-muted small">Loading stock data…</span>
                                </div>
                                <div id="stock_status_table" style="width:100%;"></div>
                            </div>
                        </div>
                    {{-- Hidden modal trigger buttons — avoids bootstrap global dependency --}}
                    <button id="trigger_month_wise_modal" class="d-none"
                            data-bs-toggle="modal" data-bs-target="#stock_view_modal"></button>
                    <button id="trigger_date_wise_modal" class="d-none"
                            data-bs-toggle="modal" data-bs-target="#date_wise_stock_view_modal"></button>
                </div>
            </div>

            {{-- ✅ Month-Wise Modal --}}
            <div class="modal fade" id="stock_view_modal" tabindex="-1" aria-hidden="true">
                @include('company.pages.stock.month-wise')
            </div>

            {{-- ✅ Date-Wise Modal (sibling – NOT nested inside month-wise) --}}
            <div class="modal fade" id="date_wise_stock_view_modal" tabindex="-1" aria-hidden="true">
                @include('company.pages.stock.date-wise-stock')
            </div>
        </div>
    </div>

@endsection

@section('script')
<script>
    const stockStatusListUrl         = "{{ route('stock-status.index') }}";
    const selectItemForMonthWiseUrl  = "{{ route('stock-status.month-wise-select') }}";
    const monthWiseStockStatusUrl    = "{{ route('stock-status.month-wise') }}";
    const selectItemForDateWiseUrl   = "{{ route('stock-status.date-wise-select') }}";
    const dateWiseStockStatusUrl     = "{{ route('stock-status.date-wise') }}";
    const stockPrintItemWiseUrl      = "{{ route('stock-status.print-item-wise') }}";
    const stockExcelItemWiseUrl      = "{{ route('stock-status.export.item-wise') }}";
    const stockPrintMonthWiseUrl     = "{{ route('stock-status.print-month-wise') }}";
    const stockExcelMonthWiseUrl     = "{{ route('stock-status.export.month-wise') }}";
    const stockPrintDateWiseUrl      = "{{ route('stock-status.print-date-wise') }}";
    const stockExcelDateWiseUrl      = "{{ route('stock-status.export.date-wise') }}";
</script>
<script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
<script src="{{ asset('js/modules/stock/index.js') }}?v={{ hash_file('md5', public_path('js/modules/stock/index.js')) }}"></script>
@endsection
