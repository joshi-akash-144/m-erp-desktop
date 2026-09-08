<div class="modal fade" id="onac_payment_list_modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="min-width: 1400px;">
        <div class="modal-content">
            <!-- HEADER -->
            <div class="modal-header border-bottom py-2">
                <div class="d-flex align-items-center gap-3">
                    <div>
                        <h4 class="modal-title mb-0 text-primary fw-bold">On Account Payment List</h4>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body p-0" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-sm table-bordered align-middle mb-0" id="onac_advance_table" style="font-size: 0.8rem;">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th style="width:50px" class="text-center">Yes/No</th>
                            <th style="width:120px">Purchase Order</th>
                            <th>Particular</th>
                            <th style="width:110px">Date</th>
                            <th style="width:130px" class="text-end">Amount</th>
                            <th style="width:60px" class="text-center">DrCr</th>
                            <th style="width:110px" class="text-end">Qty</th>
                            <th style="width:110px" class="text-end">Rec. Qty</th>
                            <th style="width:110px" class="text-end">Rem. Qty</th>
                            <th style="width:110px" class="text-center">Rec. (%)</th>
                        </tr>
                    </thead>
                    <tbody id="onac_advance_table_body">
                        <!-- Content dynamically loaded via AJAX -->
                        <tr id="onac_loader_row" class="text-center d-none">
                            <td colspan="10" class="py-4">
                                <i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i>
                            </td>
                        </tr>
                        <tr id="onac_no_data_row" class="text-center">
                            <td colspan="10" class="py-4 text-muted">
                                Click "Get On Account Records" to load.
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light sticky-bottom d-none" id="onac_advance_table_foot">
                        <tr>                          
                            <th style="width:50px" class="text-center">
                                <input type="checkbox" class="form-check-input" id="onac_select_all" style="width: 18px; height: 18px; cursor: pointer;">
                            </th>
                            <th colspan="3" class="text-end">Total</th>
                            <th class="text-end fw-bold" id="onac_advance_total">0.00</th>
                            <th colspan="5"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- FOOTER -->
            <div class="modal-footer py-2 justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <label for="onac_advance_amount" class="mb-0 fw-bold text-muted" style="white-space: nowrap;">Selected Amount</label>
                    <span class="form-control fw-bold text-end text-primary cursor-not-allowed border border-1 border-dark bg-secondary bg-opacity-10" style="width: 150px; font-size: 1rem !important;" id="onac_advance_amount">0.00</span>
                </div>
                <button type="button" class="btn btn-primary" id="btn_onac_submit">
                    Submit
                </button>
            </div>
        </div>
    </div>
</div>
