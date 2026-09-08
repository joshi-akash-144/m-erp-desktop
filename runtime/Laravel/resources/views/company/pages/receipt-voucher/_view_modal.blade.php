<style>
    table.table td {
        padding: 5px;
    }
</style>

<div class="modal fade" id="receipt_voucher_modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content shadow border-0">
            <!-- Header -->
            <div class="modal-header text-primary text-white">
                <h4 class="modal-title fw-bold">
                    <i class="fa-solid fa-receipt me-2"></i>
                    Receipt Voucher - View
                </h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <div class="module-form-section">

                    <div class="module-page-title">
                        <i class="fa-solid fa-file-pen me-2 text-primary"></i>
                        General Details
                    </div>
                    <!-- Voucher Date and Details Section -->
                    <div class="card border mb-1">
                        <div class="card-body p-2">
                            <div class="row">
                                <span class="fs-4 fw-bold text-primary">
                                    Voucher Date: <span id="view_voucher_date" class="text-dark">--</span>
                                </span>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm align-middle text-nowrap mb-0 mt-0"
                                        id="view_voucher_details_table">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center" style="width: 8%">Sr</th>
                                                <th class="">Particular</th>
                                                <th class="text-end" style="width: 20%">Debit</th>
                                                <th class="text-end" style="width: 20%">Credit</th>
                                            </tr>
                                        </thead>
                                        <tbody id="view_voucher_details_tbody">
                                            <!-- Populated dynamically via JS -->
                                        </tbody>
                                        <tfoot class="bg-light">
                                            <tr class="fw-bold">
                                                <td colspan="2" class="text-end">Total</td>
                                                <td id="view_total_debit" class="text-end font-monospace">0.00</td>
                                                <td id="view_total_credit" class="text-end font-monospace">0.00</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="module-page-footer mt-0">
                        <span class="fw-bold">Narration:</span> <span id="view_narration" class="text-dark">--</span>
                    </div>
                </div>
                <!-- Bill Break-Up Section -->
                <div class="module-form-section overflow-x-auto" id="view_bill_break_up_section">
                    <div class="module-page-title">
                        <i class="fa-solid fa-file-invoice-dollar me-2 text-primary"></i>
                        Bill Break-Up
                    </div>
                    <div class="card border mb-1">
                        <div class="card-body p-1">
                            <div class="mb-1">
                                <span class="fs-4 fw-semibold text-secondary">
                                    <i class="fa-solid fa-user-tie me-1 text-secondary"></i>
                                    Customer Name: <span id="view_customer_name" class="text-dark fw-bold">--</span>
                                </span>
                            </div>

                            <!-- Bill Break-Up Table -->
                            <div class="table-responsive" style="max-height: 290px; overflow-y: auto;">
                                <table class="table table-bordered table-sm align-middle text-nowrap mb-0 mt-0"
                                    id="view_bill_break_up_table">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th class="text-center">Sr.</th>
                                            <th class="">Method</th>
                                            <th class="">Po Num.</th>
                                            <th class="">Ref No.</th>
                                            <th class="">Ref Date</th>
                                            <th class="">Delivery Date</th>
                                            <th class="text-center">Dr/Cr</th>
                                            <th class="">Product</th>
                                            <th class="">Destination</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody id="view_bill_break_up_tbody">
                                        <!-- Populated dynamically via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Footer -->
            <div class="modal-footer">
                {{-- @can('receipt_voucher.delete')
                <button type="button" class="btn btn-danger waves-effect" id="btn_entry_reverse">
                    <i class="fa-solid fa-arrows-spin me-1"></i> Entry-Revers
                </button>
                @endcan --}}

                @can('receipt_voucher.print-voucher')
                <a href="javascript:void(0)" type="button" class="btn btn-primary waves-effect" id="btn_print_receipt">
                    <i class="fa-solid fa-print me-1"></i> Print Receipt
                </a>
                @endcan
                
                <button class="btn btn-secondary waves-effect" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>
</div>
</div>