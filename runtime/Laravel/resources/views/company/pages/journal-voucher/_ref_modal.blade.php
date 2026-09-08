<div class="modal" id="reference_modal">
    <div class="modal-dialog modal-xl" style="max-width: 45% !important;">

        <div class="modal-content">

            <div class="modal-header module-form-section rounded-0">
                <h5 class="modal-title">Bill-by-bill Adjustment of Amount
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="ref-error text-danger fw-bold mb-1"></div>
                <div class="">
                    <p class="p-0 m-0 fw-bold text-center text-primary" style="font-size: 1.0rem">
                        Account Name : <span id="ref_account_name" data-ref-account-id=""></span></p>
                    <p class="p-0 m-0 fw-bold text-center text-primary" style="font-size: 1.0rem">
                        Amount : <span id="ref_amount"></span></p>
                </div>
                <form id="ref_form">
                    <div style="max-height:300px; overflow-y:auto; border:1px solid #dee2e6;">
                        <table class="table table-bordered" style="table-layout: fixed; width:100%;" id="ref_table">
                            <colgroup>
                                <col style="width:20px">
                                <col style="width:80px">
                                <col style="width:100px">
                                <col style="width:100px">
                                <col style="width:30px">
                                <col style="width:40px">
                            </colgroup>
                    
                            <thead style="position:sticky; top:0; background:#f8f9fa; z-index:10;">
                                <tr>
                                    <th class="text-center">S No.</th>
                                    <th>Method</th>
                                    <th>Ref.</th>
                                    <th class="text-end">Amount (Rs.)</th>
                                    <th class="text-center">D/C</th>
                                    <th class="text-center">File No</th>
                                </tr>
                            </thead>
                    
                            <tbody id="ref_table_body" class="ref-table-body" data-pending-refs="[]">
                                <tr class="ref-row">
                                    <td class="ref-sno text-center">1</td>
                                    <td>
                                        <input type="hidden" class="ref-id" value="">
                                        <select class="ref-method form-select custom-select2">
                                            <option value="new_ref">New Ref</option>
                                            <option value="against_ref">Adjustment</option>
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control ref-number"></td>
                                    <td><input type="text" class="form-control ref-amount text-end only-number"></td>
                                    <td><input type="text" class="form-control transaction-type text-center" disabled></td>
                                    <td><input type="text" class="form-control ref-file-no text-center"></td>
                                </tr>
                            </tbody>
                    
                            <tfoot style="position:sticky; bottom:0; background:#ffffff; z-index:10;">
                                <tr>
                                    <td colspan="3" class="text-end pe-2 fw-bold">Total</td>
                                    <td>
                                        <input type="text" class="form-control text-end fw-bold" id="total-ref-amount" disabled>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control text-end fw-bold text-center" id="total-transaction-type" disabled>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                
            </div>

            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-primary non-selectable" id="pending_ref">
                    <i class="fa-regular fa-clock me-2"></i>
                    Get Pending Ref</button>
                <button type="button" class="btn btn-danger non-selectable" id="all_ref_clear" title="Clear all save reference">
                    <i class="fa-solid fa-trash me-2"></i>
                    All Ref Clear</button>
                <button type="submit" class="btn btn-primary btn-ref-save" id="btn_ref_save">
                    <i class="fa-solid fa-floppy-disk me-2"></i> Save</button>
            </div>
        </form>
        </div>
    </div>
</div>
