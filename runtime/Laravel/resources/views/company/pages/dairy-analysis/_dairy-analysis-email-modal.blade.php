{{-- Payment Advice — single-party compose modal --}}
<div class="modal fade" id="paEmailModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="paEmailModalLabel">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:1500px;">
        <div class="modal-content" style="border-radius:6px; overflow:hidden;">

            {{-- Header --}}
            <div class="modal-header py-2 px-3" style="background:#1a3c5e;">
                <h5 class="modal-title mb-0 text-white fw-bold" id="paEmailModalLabel">
                    <i class="fa-solid fa-file-invoice me-2"></i> Email Compose
                </h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body p-0">
                <table class="table table-bordered mb-0" style="font-size:13px; table-layout:fixed;">
                    <colgroup>
                        <col style="width:130px;">
                        <col>
                    </colgroup>
                    <tbody>

                        {{-- To --}}
                        <tr>
                            <td class="align-middle fw-semibold" style="background:#f5f5f5; padding:9px 14px;">To:</td>
                            <td style="padding:5px 8px;">
                                <input type="text" id="pa_email_to"
                                    class="form-control form-control-sm border-0 shadow-none"
                                    placeholder="Recipient email address">
                            </td>
                        </tr>

                        {{-- CC --}}
                        <tr>
                            <td class="align-middle fw-semibold" style="background:#f5f5f5; padding:9px 14px;">CC:</td>
                            <td style="padding:5px 8px;">
                                <select id="pa_email_cc" multiple style="width:100%;"></select>
                            </td>
                        </tr>

                        {{-- Subject --}}
                        <tr>
                            <td class="align-middle fw-semibold" style="background:#f5f5f5; padding:9px 14px;">Subject:</td>
                            <td style="padding:5px 8px;">
                                <input type="text" id="pa_email_subject"
                                    class="form-control form-control-sm border-0 shadow-none"
                                    placeholder="Email subject">
                            </td>
                        </tr>

                        {{-- Attachment (auto-generated PDF) --}}
                        <!-- <tr>
                            <td class="align-middle fw-semibold" style="background:#f5f5f5; padding:9px 14px;">Attachment:</td>
                            <td style="padding:8px 10px;">
                                <input type="file" id="pa_email_attachment" class="form-control form-control-sm">
                            </td>
                        </tr> -->

                        {{-- Message --}}
                        <tr>
                            <td class="align-top fw-semibold" style="background:#f5f5f5; padding:9px 14px; vertical-align:top;">Message:</td>
                            <td style="padding:0; position:relative; min-height:320px;">
                                <div id="pa_editor_loader" style="position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.92); z-index:10; display:none; flex-direction:column; justify-content:center; align-items:center; gap:8px;">
                                    <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
                                    <span class="fw-semibold text-primary" style="font-size:13px; letter-spacing:0.3px;">Loading...</span>
                                </div>
                                <textarea id="pa_email_message" name="pa_email_message"></textarea>
                            </td>
                        </tr>

                    </tbody>
                </table>

                {{-- Hidden state --}}
                <input type="hidden" id="pa_modal_voucher_ids">
            </div>

            {{-- Footer --}}
            <div class="modal-footer py-2 px-3 justify-content-end gap-2">
                <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> Cancel
                </button>
                <button type="button" id="pa_email_send_btn" class="btn btn-primary waves-effect">
                    <i class="fa-solid fa-paper-plane me-1"></i> Send Email
                </button>
            </div>

        </div>
    </div>
</div>
