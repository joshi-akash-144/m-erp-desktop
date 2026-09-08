{{-- ================================
     PAYMENT RECEIVABLE MODAL
================================ --}}
<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">

        {{-- HEADER --}}
        <div class="modal-header border-bottom px-4 py-3" style="background: linear-gradient(135deg, var(--tblr-primary-lt) 0%, #fff 100%);">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center shadow-sm"
                     style="width: 44px; height: 44px; flex-shrink: 0;">
                    <i class="fa-solid fa-receipt text-white fs-5"></i>
                </div>
                <div>
                    <h5 class="modal-title mb-0 fw-bold text-dark">Selected Payment List</h5>
                    <small class="text-success fw-semibold d-flex align-items-center gap-1 mt-1">
                        <i class="fa-solid fa-circle-check"></i> Ready for processing
                    </small>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        {{-- BODY --}}
        <div class="modal-body p-0" style="max-height: 460px; overflow-y: auto;">
            <table class="table table-hover align-middle mb-0" id="payment_receivable_table" autocomplete="off"
                   style="border-collapse: separate; border-spacing: 0;">

                <thead class="sticky-top" style="background: #e8f0fe; z-index: 2;">
                    <tr>
                        <th width="70"  class="text-center fw-semibold text-secondary py-3 px-4 border-bottom-2"
                            style="font-size: 0.78rem; letter-spacing: 0.04em; text-transform: uppercase;">
                            # Sr No
                        </th>
                        <th width="120" class="text-center fw-semibold text-secondary py-3 px-4 border-bottom-2"
                            style="font-size: 0.78rem; letter-spacing: 0.04em; text-transform: uppercase;">
                            Bill No
                        </th>
                        <th width="140" class="text-center fw-semibold text-secondary py-3 px-4 border-bottom-2"
                            style="font-size: 0.78rem; letter-spacing: 0.04em; text-transform: uppercase;">
                            Date
                        </th>
                        <th width="200" class="text-end fw-semibold text-secondary py-3 px-4 border-bottom-2"
                            style="font-size: 0.78rem; letter-spacing: 0.04em; text-transform: uppercase;">
                            Bill Amount
                        </th>
                        <th width="200" class="text-end fw-semibold text-secondary py-3 px-4 border-bottom-2"
                            style="font-size: 0.78rem; letter-spacing: 0.04em; text-transform: uppercase;">
                            Receive Amount
                        </th>
                        <th width="90"  class="text-center fw-semibold text-secondary py-3 px-4 border-bottom-2"
                            style="font-size: 0.78rem; letter-spacing: 0.04em; text-transform: uppercase;">
                            Dr/Cr
                        </th>
                    </tr>
                </thead>

                <tbody id="payment_receivable_table_body">
                    {{-- rows injected by JS --}}
                </tbody>

                <tfoot class="sticky-bottom" style="z-index: 2;">
                    <tr style="background: #f0f4ff; border-top: 2px solid #c5d4fb;">
                        <td colspan="4" class="text-end fw-bold py-3 px-4" style="font-size: 0.95rem; color: #444;">
                            <i class="fas fa-calculator me-2 text-secondary"></i>Total Receive Amount
                        </td>
                        <td class="text-end fw-bold py-3 px-4" id="modal_total_receive"
                            style="font-size: 1.05rem; color: var(--tblr-primary); letter-spacing: 0.01em;">
                            0.00
                        </td>
                        <td class="py-3 px-4"></td>
                    </tr>
                </tfoot>

            </table>
        </div>

        {{-- FOOTER --}}
        <div class="modal-footer border-top px-4 py-2" style="background: #fafbfc;">
            <small class="text-muted me-auto">
                <i class="fas fa-sort-numeric-up me-1"></i>
                Bills are listed in your selection order.
            </small>
            <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">
                <i class="fas fa-times me-1"></i> Close
            </button>
        </div>

    </div>
</div>
