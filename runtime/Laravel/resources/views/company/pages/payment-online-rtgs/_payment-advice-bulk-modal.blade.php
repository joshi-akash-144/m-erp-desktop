{{-- Payment Advice — bulk confirm & results modal --}}
<div class="modal fade" id="paBulkModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="paBulkModalLabel">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:6px; overflow:hidden;">

            {{-- Header (colour updated via JS on result) --}}
            <div class="modal-header py-2 px-3" id="pa_bulk_modal_header" style="background:#1a3c5e;">
                <h5 class="modal-title mb-0 text-white fw-bold" id="paBulkModalLabel">
                    <i class="fa-solid fa-envelopes-bulk me-2"></i>
                    <span id="pa_bulk_modal_title">Send Payment Advice Emails</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body">

                {{-- ── CONFIRM PANEL ── --}}
                <div id="pa_bulk_confirm_panel">
                    <p class="text-muted mb-3" style="font-size:13px;">
                        <i class="fa-solid fa-circle-info text-primary me-1"></i>
                        Payment advice PDFs will be auto-generated and sent individually to each party below.
                    </p>
                    <div id="pa_bulk_party_list" style="font-size:13px;"></div>
                </div>

                {{-- ── RESULT PANEL (hidden until send completes) ── --}}
                <div id="pa_bulk_result_panel" style="display:none;">
                    <div id="pa_bulk_result_content" style="font-size:13px;"></div>
                </div>

            </div>

            {{-- Footer --}}
            <div class="modal-footer py-2 px-3 gap-2" id="pa_bulk_footer">

                {{-- Confirm-state buttons --}}
                <div id="pa_bulk_confirm_actions" class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cancel
                    </button>
                    <button type="button" id="pa_bulk_send_btn" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-paper-plane me-1"></i> Send All
                    </button>
                </div>

                {{-- Result-state close button (hidden until done) --}}
                <button type="button" id="pa_bulk_close_btn" class="btn btn-sm btn-secondary d-none"
                    data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> Close
                </button>

            </div>

        </div>
    </div>
</div>

{{-- Hidden store for voucher ids sent in bulk --}}
<input type="hidden" id="pa_bulk_modal_voucher_ids">
