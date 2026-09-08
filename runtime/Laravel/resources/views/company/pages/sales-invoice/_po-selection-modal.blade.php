<!-- PO Selection Modal -->
<style>
  /* ── PO Selection Row Highlight ──────────────────────────── */
  .po-select-row {
    transition: background-color 0.15s ease, color 0.15s ease;
    cursor: pointer;
  }
  .po-select-row:hover {
    background-color: rgba(32, 107, 196, 0.10) !important;
  }
  .po-select-row.po-row-selected {
    background-color: #1a73e8 !important;
    color: #ffffff !important;
    font-weight: 600;
    box-shadow: inset 4px 0 0 0 #0d47a1;
  }
  .po-select-row.po-row-selected td {
    color: #ffffff !important;
  }
  .po-row-indicator {
    width: 28px;
    text-align: center;
    font-size: 1rem;
    color: transparent;
  }
  .po-select-row.po-row-selected .po-row-indicator {
    color: #ffffff;
  }
</style>
<div class="modal modal-blur fade" id="poSelectionModal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0 rounded-3">
            <div class="modal-header bg-primary text-white border-0 py-3">
                <h5 class="modal-title fw-semibold">
                    <i class="fa-solid fa-list-check me-2"></i> Select Purchase Order
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover mb-0" id="poSelectionTable">
                        <thead class="bg-light sticky-top" style="z-index: 1;">
                            <tr>
                                <th style="width:36px;"></th>
                                <th class="text-uppercase text-secondary fs-4 fw-bold ps-2">#</th>
                                <th class="text-uppercase text-secondary fs-4 fw-bold">Ordno</th>
                                <th class="text-uppercase text-secondary fs-4 fw-bold">Orddate</th>
                                <th class="text-uppercase text-secondary fs-4 fw-bold">ProductName</th>
                                <th class="text-uppercase text-secondary fs-4 fw-bold text-end">Qty</th>
                                <th class="text-uppercase text-secondary fs-4 fw-bold">Destination</th>
                                <th class="text-uppercase text-secondary fs-4 fw-bold text-end">Bal.qty</th>
                                <th class="text-uppercase text-secondary fs-4 fw-bold text-end">Incl Rate</th>
                                <th class="text-uppercase text-secondary fs-4 fw-bold text-end pe-3">Del.days</th>
                            </tr>
                        </thead>
                        <tbody id="poSelectionBody" style="cursor: pointer;">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                {{-- Left: keyboard hint --}}
                <small class="text-muted">
                    <i class="fa-solid fa-keyboard me-1 text-primary"></i>
                    Use <strong>Up/Down</strong> arrows to navigate, <strong>Enter</strong> to select.
                </small>

                {{-- Center: color legend --}}
                <div class="d-flex align-items-center gap-3">
                    <span class="d-flex align-items-center gap-1">
                        <span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:#1a73e8;flex-shrink:0;"></span>
                        <small class="text-muted fw-medium">Selected Row</small>
                    </span>
                    <span class="d-flex align-items-center gap-1">
                        <span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:rgba(32,107,196,0.10);border:1px solid #b6d0f5;flex-shrink:0;"></span>
                        <small class="text-muted fw-medium">Hover Row</small>
                    </span>
                </div>

                {{-- Right: cancel --}}
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-2" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
