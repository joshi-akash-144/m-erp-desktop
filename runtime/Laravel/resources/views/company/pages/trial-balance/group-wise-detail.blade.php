{{-- Group Wise Trial Balance Detail Modal (Opening/Debit/Credit/Closing drill-down) --}}
<div class="modal-dialog modal-dialog-scrollable" style="max-width: 1400px;">
    <div class="modal-content">

        {{-- Header --}}
        <div class="modal-header py-2">
            <div class="d-flex align-items-center gap-3 w-100">
                <h4 class="modal-title fw-bold text-nowrap mb-0">
                    <i class="fa-solid fa-layer-group me-2 text-primary"></i>
                    <span id="gwd_modal_title">Group Wise Trial Balance — Detail</span>
                </h4>

                <div class="ms-auto d-flex align-items-center gap-2">
                    <span id="gwd_date_badge" class="badge bg-blue-lt text-nowrap px-2 py-1"></span>
                    @canany(['trial_balance.print', 'trial_balance.export'])
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle waves-effect"
                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            @include('icons.upload', ['size' => 19]) Export
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @can('trial_balance.print')
                            <li>
                                <a class="dropdown-item" href="#" id="gwd_print_btn">
                                    @include('icons.print')
                                    Print
                                </a>
                            </li>
                            @endcan
                            @can('trial_balance.export')
                            <li>
                                <a class="dropdown-item" href="#" id="gwd_excel_btn">
                                    @include('icons.xlsx')
                                    Excel
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                    @endcanany
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
        </div>

        {{-- Search bar --}}
        <div class="modal-body py-2 border-bottom">
            <div class="row g-2 align-items-center">
                <div class="input-group">
                <span class="input-group-text bg-transparent">
                    <i class="fa-solid fa-magnifying-glass text-muted"></i>
                </span>
                <input type="text" id="gwd_search" class="form-control" placeholder="Search account name…">
            </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="modal-body p-0" style="position:relative;">
            <div id="gwd_inner_loader" class="flex-column align-items-center justify-content-center py-5" style="display:none;">
                <div class="spinner-border text-primary mb-2" role="status">
                    <span class="visually-hidden">Loading…</span>
                </div>
                <span class="text-muted small">Loading accounts…</span>
            </div>
            <div id="group_wise_trial_balance_detail_table" class="w-100"></div>
        </div>

        <div class="modal-footer py-1">
            <button type="button" class="btn btn-sm btn-link waves-effect" data-bs-dismiss="modal">Close</button>
        </div>

    </div>
</div>
