<div class="modal fade" id="cheque_details_modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="chequeModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 780px;">
        <div class="modal-content">

            <div class="modal-header py-2">
                <h5 class="modal-title fw-bold text-primary" id="chequeModalLabel">
                    <i class="fa-solid fa-money-check-dollar me-2"></i>Cheque Details!
                </h5>
                <button type="button" class="btn-close" id="cheque_modal_x_btn" aria-label="Close"></button>
            </div>
            <form id="cheque_details_form">

                <div class="modal-body">

                    {{-- Row 1: A/C Pay | RTGS | Cheque Date --}}
                    <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                        <div class="d-flex align-items-center gap-1">
                            <label class="form-label mb-0 fw-semibold small">A/C Pay (Y/N)</label>
                            <select id="cheque_ac_pay" class="form-select form-select-sm" style="width:65px;">
                                <option value="Y" selected>Y</option>
                                <option value="N">N</option>
                            </select>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <label class="form-label mb-0 fw-semibold small">RTGS (Y/N)</label>
                            <select id="cheque_rtgs_yn" class="form-select form-select-sm" style="width:65px;">
                                <option value="N" selected>N</option>
                                <option value="Y">Y</option>
                            </select>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-grow-1">
                            <label class="form-label mb-0 fw-semibold small text-nowrap">Cheque Date:</label>
                            <input type="text" id="cheque_date_input" class="form-control form-control-sm date-format"
                                placeholder="DD-MM-YYYY" style="max-width:155px;">
                        </div>
                    </div>

                    {{-- Cheque Name --}}
                    <div class="row g-2 mb-2 align-items-center">
                        <div class="col-4">
                            <label class="form-label mb-0 fw-semibold small">Name Of Cheque:</label>
                        </div>
                        <div class="col-8">
                            <input type="text" id="cheque_payee_name" class="form-control form-control-sm">
                        </div>
                    </div>

                    {{-- Cheque Alpha Number --}}
                    <div class="row g-2 mb-2 align-items-center">
                        <div class="col-4">
                            <label class="form-label mb-0 fw-semibold small">Cheque Alpha No.</label>
                        </div>
                        <div class="col-8">
                            <input type="text" id="cheque_alpha_number" class="form-control form-control-sm"
                                placeholder="Enter Cheque Alpha No.">
                        </div>
                    </div>

                    {{-- Cheque No --}}
                    <div class="row g-2 mb-2 align-items-center">
                        <div class="col-4">
                            <label class="form-label mb-0 fw-semibold small">Cheque No.</label>
                        </div>
                        <div class="col-8">
                            <input type="text" id="cheque_number" class="form-control form-control-sm"
                                placeholder="Enter Cheque No.">
                        </div>
                    </div>

                    {{-- Cheque Time --}}
                    <div class="row g-2 mb-2 align-items-center">
                        <div class="col-4">
                            <label class="form-label mb-0 fw-semibold small">Cheque-Time:</label>
                        </div>
                        <div class="col-8">
                            <input type="text" id="cheque_time_input" class="form-control form-control-sm bg-light" readonly>
                        </div>
                    </div>

                    {{-- Bank Name --}}
                    <div class="row g-2 mb-3 align-items-center">
                        <div class="col-4">
                            <label class="form-label mb-0 fw-semibold small">Bank Name:</label>
                        </div>
                        <div class="col-8">
                            <input type="text" id="cheque_bank_name" class="form-control form-control-sm bg-light"
                                readonly>
                        </div>
                    </div>
                    <div class="row g-2 mb-3 align-items-center">
                        <div class="col-4">
                            <label class="form-label mb-0 fw-semibold small">Amount:</label>
                        </div>
                        <div class="col-8">
                            <input type="text" id="cheque_total_display" class="form-control form-control-sm bg-light"
                                readonly>
                        </div>
                    </div>

                    <hr class="my-2">

                    {{-- Amount totals --}}
                    {{-- <div class="row g-2">
                        <div class="col-6">
                            <input type="text" id="cheque_total_display"
                                class="form-control form-control-sm text-end bg-light fw-bold" readonly>
                        </div>
                    </div> --}}

                </div>

                <div class="modal-footer py-2 d-flex justify-content-between">
                    <button type="button" class="btn btn-primary  px-3 non-selectable" id="btn_cheque_voucher_only">
                        <i class="fa-solid fa-file-invoice me-1"></i> Voucher Only!
                    </button>
                    <div class="d-flex gap-2">
                        @can('cheque.print')
                        {{-- User CAN print cheque --}}
                        <button type="button" class="btn btn-success  px-3" id="btn_cheque_print">
                            <i class="fa-solid fa-print me-1"></i> Print-Cheque
                        </button>
                        @else
                        {{-- User CANNOT print cheque → show Send to Approval --}}
                        <button type="button" class="btn btn-warning  px-3" id="btn_send_approval">
                            <i class="fa-solid fa-check-circle me-1"></i> Pass to Approval
                        </button>
                        @endcan
                        @can('cheque.print')
                        <button type="button" class="btn btn-info  px-3 text-white" id="btn_cheque_pass_rtgs">
                            <i class="fa-solid fa-paper-plane me-1"></i> Pass-to-RTGS
                        </button>
                        @endcan
                        <button type="button" class="btn btn-danger  px-3" id="btn_cheque_cancel">
                            <i class="fa-solid fa-times me-1"></i> Cancel
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>