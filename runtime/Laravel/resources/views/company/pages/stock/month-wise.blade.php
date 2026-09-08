{{-- ================================
    MONTH WISE STOCK STATUS MODAL
================================ --}}
<div class="modal-dialog modal-dialog-scrollable" style="max-width: 1300px;">
    <div class="modal-content">

        {{-- ── Header ── --}}
        <div class="modal-header py-2">
            <div class="d-flex align-items-center gap-3 w-100">
                <h4 class="modal-title fw-bold text-nowrap mb-0" id="mw_stock_modal_title">
                    <i class="fa-solid fa-calendar-days me-2 text-primary"></i>
                    Month Wise Stock
                </h4>
                <div class="ms-auto d-flex align-items-center gap-2">
                    <span id="mw_stock_date_badge"
                          class="badge bg-blue-lt text-blue text-nowrap px-2 py-1"
                          style="display:none; font-size:.8rem;">
                        <i class="fa-regular fa-calendar me-1"></i>
                        <span id="mw_stock_date_text"></span>
                    </span>
                    @canany(['stock_status.print', 'stock_status.export'])
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle waves-effect"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                               @include('icons.upload', ['size' => 19]) Export
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @can('stock_status.print')
                                    <li>
                                        <a class="dropdown-item" href="#"
                                           id="mw_stock_print_btn"
                                           data-route="{{ route('stock-status.print-month-wise') }}"
                                           data-format="print" data-type="print">
                                            @include('icons.print') Print
                                        </a>
                                    </li>
                                @endcan
                                @can('stock_status.export')
                                    <li>
                                        <a class="dropdown-item" href="#"
                                           id="mw_stock_excel_btn"
                                           data-route="{{ route('stock-status.export.month-wise') }}"
                                           data-format="xlsx" data-type="excel">
                                            @include('icons.xlsx') Excel
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    @endcanany
                    {{-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> --}}
                </div>
            </div>
        </div>

        {{-- ── Body ── --}}
        <div class="modal-body p-0" style="position:relative; min-height:300px;">
            {{-- Overlay loader (same as trial balance mws_inner_loader) --}}
            <div id="mw_inner_loader"
                 style="display:none; position:absolute; inset:0; min-height:300px; background:rgba(255,255,255,0.88); z-index:10; flex-direction:column; align-items:center; justify-content:center;">
                <div class="spinner-border text-primary mb-2" role="status">
                    <span class="visually-hidden">Loading…</span>
                </div>
                <span class="text-muted small">Loading month-wise data…</span>
            </div>
            <div id="month_wise_stock_status_table" class="w-100"></div>
        </div>

        {{-- ── Footer ── --}}
        <div class="modal-footer py-2 border-top justify-content-between">

            {{-- Totals bar: only Total In / Total Out (opening & closing shown as table rows) --}}
            <div id="mw_stock_totals_bar" style="display:none;">
                <div class="d-flex align-items-stretch gap-0 flex-wrap">

<div class="px-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Opening Qty</div>
                        <div class="fw-bold fs-5 font-monospace text-primary" id="mw_opening_qty">0.000</div>
                    </div>
                    
                    <div class="vr"></div>
                    
                    <div class="px-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Opening Amount</div>
                        <div class="fw-bold fs-5 font-monospace text-primary" id="mw_opening_amount">0.000</div>
                    </div>
                    
                    <div class="vr"></div>
                    <div class="px-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Total In</div>
                        <div class="fw-bold fs-5 font-monospace text-success" id="mw_total_in">0.000</div>
                    </div>

                    <div class="vr"></div>

                    <div class="px-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Total Out</div>
                        <div class="fw-bold fs-5 font-monospace text-danger" id="mw_total_out">0.000</div>
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
</div>
