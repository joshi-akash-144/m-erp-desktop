{{-- Month Wise Account Summary Modal --}}
<div class="modal-dialog modal-dialog-scrollable" style="max-width: 1400px;">
    <div class="modal-content">

        {{-- Header --}}
        <div class="modal-header py-2">
            <div class="d-flex align-items-center gap-3 w-100">
                <h4 class="modal-title fw-bold text-nowrap mb-0">
                    <i class="fa-solid fa-calendar-days me-2 text-primary"></i>
                    <span id="mws_modal_title">Month Wise Summary</span>
                </h4>
                <div class="ms-auto d-flex align-items-center gap-2">
                    <span id="mws_date_badge" class="badge bg-blue-lt text-blue text-nowrap px-2 py-1" style="display:none; font-size:.8rem;">
                        <i class="fa-regular fa-calendar me-1"></i>
                        <span id="mws_date_text"></span>
                    </span>
                        <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle waves-effect" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            @include('icons.upload', ['size' => 19])Export
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="#" id="mws_print_btn">
                                    @include('icons.print') Print
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" id="mws_excel_btn">
                                    @include('icons.xlsx')Excel
                                </a>
                            </li>
                        </ul>
                    </div>
                    {{-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> --}}
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="modal-body p-0" style="position:relative; min-height:220px;">
            <div id="mws_inner_loader" style="display:none; position:absolute; inset:0; min-height:220px; background:rgba(255,255,255,0.88); z-index:10; flex-direction:column; align-items:center; justify-content:center;">
                <div class="spinner-border text-primary mb-2" role="status">
                    <span class="visually-hidden">Loading…</span>
                </div>
                <span class="text-muted small">Loading month-wise data…</span>
            </div>
            <div id="month_wise_summary_table" class="w-100"></div>
        </div>

        {{-- Footer --}}
        <div class="modal-footer py-2 border-top justify-content-between">

            <div id="mws_totals_bar" style="display:none;">
                <div class="d-flex align-items-stretch gap-0 flex-wrap">

                    <div class="px-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Opening Balance</div>
                        <div class="fw-bold fs-5 font-monospace" id="mws_opening_balance">0.00</div>
                    </div>

                    <div class="vr"></div>

                    <div class="px-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Total Debit</div>
                        <div class="fw-bold fs-5 font-monospace" id="mws_total_debit">0.00</div>
                    </div>

                    <div class="vr"></div>

                    <div class="px-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Total Credit</div>
                        <div class="fw-bold fs-5 font-monospace" id="mws_total_credit">0.00</div>
                    </div>

                    <div class="vr"></div>

                    <div class="px-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Closing Balance</div>
                        <div class="fw-bold fs-5 font-monospace" id="mws_closing_balance">0.00</div>
                    </div>

                </div>
            </div>

            <div class="d-flex gap-2 ms-auto">
                
                <button type="button" class="btn btn-sm btn-outline-secondary waves-effect" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> Close
                </button>
            </div>

        </div>

    </div>
</div>
