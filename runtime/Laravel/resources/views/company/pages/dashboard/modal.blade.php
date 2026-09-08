    <!-- Godown Details Modal -->
    <div class="modal fade master-modal" id="godownDetailsModal" tabindex="-1" aria-labelledby="godownDetailsModal" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 95% !important; margin: 1.75rem auto;">
            <div class="modal-content">
                <div class="modal-header master-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="master-badge bg-primary-lt rounded fs-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-building-warehouse" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M3 21v-13l9 -4l9 4v13" />
                                <path d="M13 13h4v8h-10v-6h6" />
                                <path d="M13 21v-9a1 1 0 0 0 -1 -1h-2a1 1 0 0 0 -1 1v3" />
                            </svg>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0 font-monospace" id="godownDetailsModalTitle">Godown Details</h5>
                            <small class="badge bg-teal text-teal-fg">Dashboard Data</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="godown-details-table" style="height: 100%; width: 100%;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Creditor Modal -->
    <div class="modal fade master-modal" id="creditorModal" tabindex="-1" aria-labelledby="creditorModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 95% !important; margin: 1.75rem auto;">
            <div class="modal-content">
                <div class="modal-header master-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="master-badge bg-danger-lt rounded fs-2">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0 font-monospace" id="creditorModalLabel">Creditor List</h5>
                            <small class="badge bg-teal text-teal-fg">Dashboard Data</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center ms-auto gap-3">
                        <div class="input-group" style="min-width: 500px;">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="creditor-search" class="form-control border-start-0 ps-0" placeholder="Search Creditors...">
                        </div>
                        <button type="button" class="btn btn-primary" onclick="printTabulator('creditor-table', 'Creditor List')">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-0">
                    <div id="creditor-table" style="height: 100%; width: 100%;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Debtor Modal -->
    <div class="modal fade master-modal" id="debtorModal" tabindex="-1" aria-labelledby="debtorModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 95% !important; margin: 1.75rem auto;">
            <div class="modal-content">
                <div class="modal-header master-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="master-badge bg-success-lt rounded fs-2">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0 font-monospace" id="debtorModalLabel">Debtor List</h5>
                            <small class="badge bg-teal text-teal-fg">Dashboard Data</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center ms-auto gap-3">
                        <div class="input-group" style="min-width: 500px;">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="debtor-search" class="form-control border-start-0 ps-0" placeholder="Search Debtors...">
                        </div>
                        <button type="button" class="btn btn-primary" onclick="printTabulator('debtor-table', 'Debtor List')">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-0">
                    <div id="debtor-table" style="height: 100%; width: 100%;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Location-Wise GRN Details Modal -->
    <div class="modal fade master-modal" id="locationGrnDetailsModal" tabindex="-1" aria-labelledby="locationGrnDetailsModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 95% !important; margin: 1.75rem auto;">
            <div class="modal-content">
                <div class="modal-header master-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="master-badge bg-primary-lt rounded fs-2">
                            <i class="fa-solid fa-map-location"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0 font-monospace" id="locationGrnDetailsModalTitle">Location-Wise GRN Details</h5>
                            <small class="badge bg-teal text-teal-fg">Dashboard Data</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center ms-auto gap-3">
                        <div class="input-group" style="min-width: 500px;">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="location-grn-details-search" class="form-control border-start-0 ps-0" placeholder="Search GRN Details...">
                        </div>
                        {{-- <button type="button" class="btn btn-primary" onclick="printTabulator('location-grn-details-table', 'Location-Wise GRN Details')">
                            <i class="fas fa-print me-1"></i> Print
                        </button> --}}
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-0">
                    <div id="location-grn-details-table" style="width: 100%;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
