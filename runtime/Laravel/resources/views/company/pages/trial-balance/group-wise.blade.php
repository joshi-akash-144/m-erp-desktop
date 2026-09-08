{{-- Group Wise Trial Balance Modal --}}
<div class="modal-dialog modal-dialog-scrollable" style="max-width: 1400px;">
    <div class="modal-content">

        {{-- Header --}}
        <div class="modal-header">
            <div class="w-100">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div>
                        <h4 class="modal-title fw-bold mb-1">
                            <i class="fa-solid fa-layer-group me-2 text-primary"></i>
                            <span id="gw_modal_title">Group Wise Trial Balance</span>
                        </h4>
                        <span id="gw_date_badge" class="badge bg-blue-lt text-blue px-2 py-1" style="display:none; font-size:.8rem;">
                            <i class="fa-regular fa-calendar me-1"></i>
                            <span id="gw_date_text"></span>
                        </span>
                    </div>

                    <div class="d-flex align-items-center flex-shrink-0 mt-3 me-0">
                        @canany(['trial_balance.print', 'trial_balance.export'])
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle waves-effect"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                @include('icons.upload', ['size' => 19]) Export
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @can('trial_balance.print')
                                <li>
                                    <a class="dropdown-item" href="#" id="gw_print_btn">
                                        @include('icons.print')
                                        Print
                                    </a>
                                </li>
                                @endcan
                                @can('trial_balance.export')
                                <li>
                                    <a class="dropdown-item" href="#" id="gw_excel_btn">
                                        @include('icons.xlsx')
                                        Excel
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
        </div>

        {{-- Search bar --}}
        <div class="modal-body py-2 border-bottom">
            <div class="input-group">
                <span class="input-group-text bg-transparent">
                    <i class="fa-solid fa-magnifying-glass text-muted"></i>
                </span>
                <input type="text" id="gw_search" class="form-control"
                    placeholder="Search account name…">
            </div>
        </div>

        {{-- Table --}}
        <div class="modal-body p-0">
            <div id="group_wise_trial_balance_table" class="w-100"></div>
        </div>

        {{-- Footer: summary + close --}}
        <div class="modal-footer py-2 border-top justify-content-between">

            <div id="gw_totals_bar" style="display:none;">
                <div class="d-flex align-items-stretch gap-0">

                    <div class="px-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Group Name</div>
                        <div class="fw-semibold fs-6 text-dark" id="gw_footer_group_name">—</div>
                    </div>

                    <div class="vr"></div>

                    <div class="px-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Total Debit</div>
                        <div class="fw-bold fs-5 font-monospace text-primary" id="gw_total_debit">—</div>
                    </div>

                    <div class="vr"></div>

                    <div class="px-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Total Credit</div>
                        <div class="fw-bold fs-5 font-monospace text-primary" id="gw_total_credit">—</div>
                    </div>

                    <div class="vr"></div>

                    <div class="px-3 text-center">
                        <div class="text-muted small fw-semibold mb-1">Group Balance</div>
                        <div class="fw-bold fs-5 font-monospace" id="gw_net_balance">—</div>
                    </div>

                </div>
            </div>

            <button type="button" class="btn btn-sm btn-outline-secondary waves-effect ms-auto" data-bs-dismiss="modal">
                <i class="fa-solid fa-xmark me-1"></i> Close
            </button>

        </div>

    </div>
</div>
