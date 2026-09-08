{{-- Account Ledger Detail Modal --}}
<div class="modal-dialog modal-dialog-scrollable modal-fullscreen-xl-down" style="max-width: 98vw;">
    <div class="modal-content">

        {{-- Header --}}
        <div class="modal-header py-2">
            <div class="d-flex align-items-center gap-3 w-100 flex-wrap">
                <h4 class="modal-title fw-bold mb-0">
                    <i class="fa-solid fa-book-open me-2 text-primary"></i>
                    <span id="ldr_modal_title">Ledger</span>
                </h4>
                <div class="ms-auto d-flex align-items-center gap-2">
                    <span id="ldr_date_badge" class="badge bg-blue-lt text-blue text-nowrap px-2 py-1" style="display:none; font-size:.8rem;">
                        <i class="fa-regular fa-calendar me-1"></i>
                        <span id="ldr_date_text"></span>
                    </span>
                    <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle waves-effect" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        @include('icons.upload', ['size' => 19])Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="#" id="ldr_print_btn">
                                @include('icons.print')Print
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" id="ldr_excel_btn">
                                @include('icons.xlsx')Excel
                            </a>
                        </li>
                    </ul>
                </div>
                    {{-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> --}}
                </div>
            </div>
        </div>

        {{-- Date filter bar --}}
        <div class="modal-body py-2 border-bottom">
            <div class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label fw-semibold mb-1 small">From Date</label>
                    <input type="text" id="ldr_from_date" class="form-control form-control-sm" placeholder="DD-MM-YYYY" style="width:130px;">
                </div>
                <div class="col-auto">
                    <label class="form-label fw-semibold mb-1 small">To Date</label>
                    <input type="text" id="ldr_to_date" class="form-control form-control-sm" placeholder="DD-MM-YYYY" style="width:130px;">
                </div>
                <div class="col-auto">
                    <button id="ldr_show_btn" class="btn btn-sm btn-primary waves-effect">
                        <i class="fa-solid fa-rotate me-1"></i> Show
                    </button>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="modal-body p-0" style="position:relative; min-height:300px;">
            <div id="ldr_inner_loader" style="display:none; position:absolute; inset:0; min-height:220px; background:rgba(255,255,255,0.88); z-index:10; flex-direction:column; align-items:center; justify-content:center;">
                <div class="spinner-border text-primary mb-2" role="status">
                    <span class="visually-hidden">Loading…</span>
                </div>
                <span class="text-muted small">Loading ledger…</span>
            </div>

            {{-- Opening balance (above table) --}}
            <div id="ldr_opening_bar" class="px-3 py-1 border-bottom bg-light text-end" style="display:none;">
                <span class="text-muted fw-semibold me-1">Opening Balance :</span>
                <span class="fw-bold font-monospace" id="ldr_opening_balance">0.00</span>
            </div>

            <div id="ledger_detail_table" class="w-100"></div>

            {{-- Closing balance (below table) --}}
            <div id="ldr_closing_bar" class="px-3 py-1 border-top bg-light text-end" style="display:none;">
                <span class="text-muted fw-semibold me-1">Closing Balance :</span>
                <span class="fw-bold font-monospace" id="ldr_closing_balance">0.00</span>
            </div>
        </div>

        {{-- Footer --}}
        <div class="modal-footer py-2 border-top justify-content-between">

            <div id="ldr_totals_bar" style="display:none;">
                <div class="d-flex align-items-stretch gap-0 flex-wrap">

                    <div class="px-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Total Debit</div>
                        <div class="fw-bold fs-5 font-monospace" id="ldr_total_debit">0.00</div>
                    </div>

                    <div class="vr"></div>

                    <div class="px-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Total Credit</div>
                        <div class="fw-bold fs-5 font-monospace" id="ldr_total_credit">0.00</div>
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
