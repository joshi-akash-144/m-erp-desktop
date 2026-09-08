{{-- ================================
    DATE WISE STOCK STATUS MODAL
================================ --}}
<div class="modal-dialog modal-fullscreen">
    <div class="modal-content">

        {{-- ── Header ── --}}
        <div class="modal-header py-2">
            <div class="d-flex align-items-center gap-3 w-100">
                <h4 class="modal-title fw-bold text-nowrap mb-0" id="dw_stock_modal_title">
                    <i class="fa-solid fa-calendar-day me-2 text-primary"></i>
                    Date Wise Stock
                </h4>
                <div class="ms-auto d-flex align-items-center gap-2">
                    <span id="dw_stock_date_badge"
                          class="badge bg-blue-lt text-blue text-nowrap px-2 py-1"
                          style="display:none; font-size:.8rem;">
                        <i class="fa-regular fa-calendar me-1"></i>
                        <span id="dw_stock_date_text"></span>
                    </span>
                    @canany(['stock_status.print', 'stock_status.export'])
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle waves-effect"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                @include('icons.upload', ['size' => 19]) Export
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @php
                                    $dwActions = [
                                        ['key' => 'print', 'label' => 'Print',
                                         'permission' => 'stock_status.print',
                                         'icon' => 'icons.print',
                                         'route' => route('stock-status.print-date-wise'),
                                         'attrs' => ['id' => 'export_print_date_wise_btn',
                                                     'data-format' => 'print', 'data-type' => 'print']],
                                        ['key' => 'excel', 'label' => 'Excel',
                                         'permission' => 'stock_status.export',
                                         'icon' => 'icons.xlsx',
                                         'route' => route('stock-status.export.date-wise'),
                                         'attrs' => ['id' => 'export_xlsx_date_wise_btn',
                                                     'data-format' => 'xlsx', 'data-type' => 'excel']],
                                    ];
                                @endphp
                                @foreach ($dwActions as $action)
                                    @can($action['permission'])
                                        <li>
                                            <a class="dropdown-item" href="#"
                                               data-route="{{ $action['route'] }}"
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
                    {{-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> --}}
                </div>
            </div>
        </div>

        {{-- ── Filter Bar ── --}}
        <div class="border-bottom px-3 py-2 bg-light">
            <div class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <div class="fw-semibold small mb-1 text-muted">From Date</div>
                    <input type="text" id="dw_start_date" placeholder="DD-MM-YYYY"
                           class="form-control form-control-sm date-input" style="width:145px;">
                </div>
                <div>
                    <div class="fw-semibold small mb-1 text-muted">To Date</div>
                    <input type="text" id="dw_end_date" placeholder="DD-MM-YYYY"
                           class="form-control form-control-sm date-input" style="width:145px;">
                </div>
                <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
                    <button id="dw_filter_apply" class="btn btn-primary waves-effect">
                        @include('icons.filter', ['size' => 20]) Apply
                    </button>
                    <button id="dw_filter_clear" class="btn btn-outline-secondary waves-effect">
                        @include('icons.filter-clear', ['size' => 20]) Clear
                    </button>
                </div>
            </div>
        </div>

        {{-- ── Opening Balance bar (above table) ── --}}
        <div id="dw_opening_bar" style="display:none; background:#f8f9fa;"
             class="border-bottom px-4 py-2 d-flex align-items-center justify-content-end gap-3">
            <div>
                <span class="text-muted small fw-semibold">
                    <i class="fa-solid fa-box-open me-1"></i> Opening Qty:
                </span>
                <span class="fw-bold font-monospace ms-1 text-primary" style="font-size:1rem;" id="dw_opening_qty">0.000</span>
            </div>
            <div class="vr"></div>
            <div>
                <span class="text-muted small fw-semibold">Opening Amount:</span>
                <span class="fw-bold font-monospace ms-1 text-primary" style="font-size:1rem;" id="dw_opening_amount">0.000</span>
            </div>
        </div>

        {{-- ── Body: Tabulator table ── --}}
        <div class="modal-body p-0" style="position:relative; min-height:300px;">
            {{-- Overlay loader --}}
            <div id="dw_inner_loader"
                 style="display:none; position:absolute; inset:0; min-height:300px; background:rgba(255,255,255,0.88); z-index:10; flex-direction:column; align-items:center; justify-content:center;">
                <div class="spinner-border text-primary mb-2" role="status">
                    <span class="visually-hidden">Loading…</span>
                </div>
                <span class="text-muted small">Loading date-wise data…</span>
            </div>
            <div id="date_wise_stock_status_table" class="w-100"></div>
        </div>

        {{-- ── Footer: totals + close ── --}}
        <div class="modal-footer py-2 border-top justify-content-between">

            {{-- Totals bar: Total In | Total Out | Closing Qty (hidden until data loads) --}}
            <div id="dw_stock_totals_bar" style="display:none;">
                <div class="d-flex align-items-stretch gap-0 flex-wrap">

                    <div class="px-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Total In</div>
                        <div class="fw-bold fs-5 font-monospace text-success" id="dw_total_in">0.000</div>
                    </div>

                    <div class="vr"></div>

                    <div class="px-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Total Out</div>
                        <div class="fw-bold fs-5 font-monospace text-danger" id="dw_total_out">0.000</div>
                    </div>

                    <div class="vr"></div>

                    <div class="px-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">
                            <i class="fa-solid fa-boxes-stacked me-1 text-primary"></i> Closing Balance
                        </div>
                        <div class="fw-bold fs-5 font-monospace text-primary" id="dw_closing_qty">0.000</div>
                    </div>

                </div>
            </div>

            <div class="d-flex gap-2 ms-auto">
                <button type="button" class="btn btn-sm btn-outline-secondary waves-effect"
                        data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> Close
                </button>
            </div>
        </div>

    </div>

    {{-- Purchase Invoice view modal (for drill-through from date-wise) --}}
    @include('company.pages.purchase-invoice._view_modal', ['formMode' => 'view'])
    <div class="modal fade" id="sales_invoice_modal" tabindex="-1" aria-hidden="true">
        @include('company.pages.sales-invoice._view_modal')
    </div>
</div>
