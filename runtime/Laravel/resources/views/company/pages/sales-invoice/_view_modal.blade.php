<!-- ================================
     SALES INVOICE VIEW MODAL
================================ -->
<div class="modal-dialog modal-fullscreen modal-dialog-scrollable ">
    <div class="modal-content">

        <!-- Header -->
        <div class="modal-header py-3">
            <h4 class="modal-title fw-bold text-primary ">
                <i class="fa-solid fa-cart-shopping me-2"></i>
                Sales Invoice - View
            </h4>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
            <form id="sales_invoice_form" autocomplete="off">

                <!-- 🧾 General Details -->
                <div class="module-form-section">
                    <div class="module-page-title">
                        <i class="fa-solid fa-file-pen me-2 text-primary"></i>
                        General Details
                    </div>

                    <div class="row mb-3 row-md-12 row-sm-12">

                        <div class="grid-item col-lg-1 col-md-12">
                            <label class="form-label">
                                <i class="fa-solid fa-hashtag me-1 text-secondary"></i>Bill No.
                            </label>
                            <input type="text" name="invoice_serial" id="invoice_serial"
                                class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle"
                                value="" disabled>
                        </div>

                        <div class="grid-item col-lg-1 col-md-12">
                            <label class="form-label">
                                <i class="fa-regular fa-calendar-days me-1 text-secondary"></i>Date
                            </label>
                            <input type="text" name="invoice_date" id="invoice_date"
                                class="form-control fw-bold date-format bg-light text-muted border-1 border-secondary-subtle"
                                placeholder="DD-MM-YYYY" disabled>
                        </div>

                        <div class="grid-item col-lg-1 col-md-12">
                            <label class="form-label">
                                <i class="fa-regular fa-clock me-1 text-secondary"></i>D.C. No
                            </label>
                            <input type="number" name="delivery_challan_number" id="delivery_challan_number"
                                class="form-control fw-bold  bg-light text-muted  border-1 border-secondary-subtle"
                                disabled>
                        </div>

                        <div class="grid-item col-lg-1 col-md-12">
                            <label class="form-label">
                                <i class="fa-solid fa-calendar-check me-1 text-secondary"></i>P.O Number
                            </label>
                            <input type="text" name="po_number" id="po_number"
                                class="form-control date-format fw-bold bg-light text-muted border-1 border-secondary-subtle"
                                disabled>
                        </div>

                        <div class="grid-item col-lg-1 col-md-12">
                            <label class="form-label">
                                <i class="fa-solid fa-file-contract me-1 text-secondary"></i>GRN 
                            </label>
                            <input type="text" id="sales_grn_number" name="grn_number"
                                class="form-control fw-bold bg-light text-muted border-1 border-secondary-subtle"
                                disabled>
                        </div>

                        <div class="grid-item col-lg-3 col-md-12">
                            <label class="form-label">
                                <i class="fa-solid fa-user-tie me-1 text-secondary"></i>Customer Name 
                            </label>
                            <input type="text" name="account_id" id="sales_account_id"
                                class="form-control bg-light fw-bold text-muted border-1 border-secondary-subtle text-truncate"
                                disabled>
                        </div>
                        <div class="grid-item col-lg-2 col-md-6 col-sm-12 me-2">
                                <label class="form-label">
                                     <i class="fa-solid fa-city me-1 text-secondary"></i>City
                                </label>
                                <input type="text" name="account_id_city" id="account_id_city"
                                    class="form-control fw-bold fs-3 bg-light text-muted border-1 border-secondary-subtle"
                                    disabled>
                        </div>  
                        <div class="grid-item col-lg-1 col-md-12 me-2">
                            <label class="form-label">
                                <i class="fa-solid fa-percent me-1 text-secondary"></i>Tax Type 
                            </label>
                            <input type="text" name="tax_type" id="tax_type"
                                class="form-control fw-bold bg-light text-muted border-1 border-secondary-subtle text-truncate"
                                disabled>
                        </div>                                    
                    </div>

                    <!-- Row 2 -->
                    <div class="row mb-3 row-md-12 row-sm-12">
                        {{-- <div class="col-12 d-flex"> --}}                                                                                      
                            <div class="grid-item col-lg-1 col-md-6 col-sm-12 me-2">
                                <label class="form-label ">
                                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i>Last Inv Date
                                </label>
                                <input type="text" name="last_invoice_date" id="last_invoice_date"
                                    class="form-control fw-bold bg-light text-muted border-1 border-secondary-subtle"
                                    disabled>
                            </div>

                            {{-- <div class="grid-item col-lg-2 col-md-12 me-2">
                                <label class="form-label">
                                    <i class="fa-solid fa-user-tie me-1 text-secondary"></i>Broker 
                                </label>
                                <input type="text" name="broker_id" id="broker_id"
                                    class="form-control fw-bold bg-light text-muted border-1 border-secondary-subtle text-truncate"
                                    disabled>
                            </div> --}}
                           
                             <div class="grid-item col-lg-1 col-md-12 me-2">
                                <label class="form-label">
                                    <i class="fa-solid fa-location-dot me-1 text-secondary"></i>KMS 
                                </label>
                                <input type="text" name="kms" id="kms"
                                    class="form-control fw-bold bg-light text-muted border-1 border-secondary-subtle"
                                    disabled>
                            </div>

                            <div class="grid-item col-lg-1 col-md-12 me-2">
                                <label class="form-label">
                                    <i class="fa-solid fa-id-card me-1 text-secondary"></i>Vehicle No. 
                                </label>
                                <input type="text" name="vehicle_number" id="sales_vehicle_number"
                                    class="form-control fw-bold bg-light text-muted border-1 border-secondary-subtle"
                                    disabled>
                            </div>

                             <div class="grid-item col-lg-1 col-md-12 me-2">
                                <label class="form-label">
                                    <i class="fa-solid fa-location-dot me-1 text-secondary"></i>Delivery Date 
                                </label>
                                <input type="text" name="delivery_date" id="delivery_date"
                                    class="form-control fw-bold bg-light text-muted border-1 border-secondary-subtle"
                                    disabled>
                            </div>

                            <div class="grid-item col-lg-2 col-md-12 me-2">
                                <label class="form-label">
                                    <i class="fa-solid fa-user-tie me-1 text-secondary"></i>Invoice Type 
                                </label>
                                <input type="text" name="sale_type_id" id="sale_type_id"
                                    class="form-control fw-bold bg-light text-muted border-1 border-secondary-subtle text-truncate"
                                    disabled>
                            </div>

                            {{-- <div class="grid-item col-lg-2 col-md-12 me-2 mt-1">
                                <label class="form-label">
                                    Invoice Status
                                </label>
                                <span id="invoice_status"
                                    class="alert p-2 d-block text-center text-muted fw-bold fs-3 fw-bold"
                                    style="width:170px; height: 52%;">
                                    <!-- Status text inserted here via JavaScript -->
                                </span>
                            </div> --}}

                        {{-- </div> --}}
                    </div>
                </div>

                <!-- 📦 Item Details -->
                <div class="module-form-section">
                   <div class="d-flex justify-content-between">
                        <div class="module-page-title">
                            <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i>
                            Item Details
                        </div>
                    </div>

                    <div class="table-scroll-x scrollable-area" style="overflow-x:scroll">
                        <table class="table table-sm align-middle border-0 mt-0" id="item_table">
                            <thead class="table-light text-nowrap">
                                    <tr>
                                        <th width="40" class="fw-semibold">
                                            <i class="fa-solid fa-hashtag text-secondary me-2"></i>
                                        </th>
                                        <th width="200" class="fw-semibold">
                                            <i class="fa-solid fa-box text-secondary me-1"></i> Item Name
                                        </th>
                                        <th width="120" class="fw-semibold">
                                            <i class="fa-solid fa-scale-balanced text-secondary me-1"></i> Unit
                                        </th>
                                        {{-- <th width="200" class="fw-semibold">
                                            <i class="fa-solid fa-file-invoice text-secondary me-2"></i> Order No.
                                        </th> --}}
                                        <th width="200" class="fw-semibold">
                                            <i class="fa-solid fa-location-dot text-secondary me-2"></i> Destination
                                        </th>
                                        <th width="200" class="fw-semibold">
                                            <i class="fa-solid fa-clipboard-list text-secondary me-1"></i> Condition
                                            Name
                                        </th>
                                        <th class="text-center fw-semibold" style="width:75px;">
                                            <i class="fa-solid fa-percent text-secondary"></i> CGST
                                        </th>
                                        <th class="text-center fw-semibold" style="width:75px;">
                                            <i class="fa-solid fa-percent text-secondary"></i> SGST
                                        </th>
                                        <th class="text-center fw-semibold" style="width:75px;">
                                            <i class="fa-solid fa-percent text-secondary"></i> IGST
                                        </th>

                                        <th class="text-center fw-semibold" style="width:100px;">
                                            <i class="text-center fa-solid fa-boxes-stacked text-secondary"></i> Bags
                                        </th>
                        
                                        <th class="text-end fw-semibold" style="width:140px;">
                                            <i class="text-end fa-solid fa-weight-hanging text-secondary me-2"></i> P.Qty
                                        </th>

                                        <th class="text-end fw-semibold" width="120">
                                            <i class="fa-solid fa-cubes text-secondary me-1"></i> Qty
                                        </th>
                                        <th class="text-end fw-semibold" width="120">
                                            <i class="fa-solid fa-percent text-secondary me-1"></i> Incl. Rate
                                        </th>
                                        <th class="text-end fw-semibold" width="120">
                                            <i class="fa-solid fa-tags text-secondary me-1"></i> Rate
                                        </th>
                                        <th class="text-end fw-semibold" width="140">
                                            <i class="fa-solid fa-indian-rupee-sign text-secondary me-1"></i> Amount
                                        </th>
                                    </tr>

                                </thead>

                                <tbody id="item_table_body">
                                    <tr data-row-id="1" class="item-row">
                                        <td class="p-2 text-center fw-bolder fs-3">
                                            <span class=" bg-opacity-20 fw-bold text-muted">1</span>
                                        </td>

                                        <td>
                                            <span class="form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle item_id text-truncate"
                                                id="item_id" disabled></span>
                                        </td>

                                        <td>
                                            <span name="items[0][unit_name]" class="form-control fs-3  fw-bold text-muted bg-light border-1 border-secondary-subtle form-control-sm unit_name"
                                            disabled></span>
                                        </td>

                                        {{-- <td class="fw-bolder">
                                            <span
                                                class="purchase_order_serial form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle text-center"
                                                name="items[0][purchase_order_serial]" disabled></span>
                                            <input type="hidden" name="items[0][purchase_order_id]" class="purchase_order_id">
                                            <input type="hidden" name="items[0][purchase_order_detail_id]" class="purchase_order_detail_id">
                                        </td> --}}

                                        <td class="fw-bolder">
                                            <span
                                                class="destination_id form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle text-truncate"
                                                name="items[0][destination_id]" disabled></span>
                                        </td>

                                        <td>
                                            <span name="items[0][condition_id]"
                                                class="form-control text-muted form-control-sm fs-3 fw-bold text-muted bg-light border-1 border-secondary-subtle small condition_error condition_id text-truncate"
                                                disabled></span>
                                        </td>
                                        <td>
                                            <span name="items[0][cgst_rate]"
                                                    class="form-control fw-bold fs-3 form-control-sm text-muted bg-light border-1 border-secondary-subtle cgst_rate text-end"
                                                    id="cgst_rate" disabled></span>
                                                <span class="input-icon-addon d-none cgst_loader">
                                                    <div class="spinner-border spinner-border-sm text-secondary"
                                                        role="status"></div>
                                            </span>                                           
                                        </td>
                                        <td>                                            
                                            <span name="items[0][sgst_rate]"
                                                class="form-control fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle form-control-sm text-end sgst_rate text-end"
                                                value="--" disabled></span>
                                            <span class="input-icon-addon d-none sgst_loader">
                                                <div class="spinner-border spinner-border-sm text-secondary"
                                                    role="status"></div>
                                            </span>                                           
                                        </td>
                                        <td>                                            
                                            <span name="items[0][igst_rate]"
                                                class="form-control fs-3 fw-bold text-muted bg-light border-1 border-secondary-subtle form-control-sm text-end igst_rate text-end"
                                                value="--" disabled></span>
                                            <span class="input-icon-addon d-none igst_loader">
                                                <div class="spinner-border spinner-border-sm text-secondary"
                                                    role="status"></div>
                                            </span>                                            
                                        </td>
                                        <td class="text-end fw-bolder">
                                            <span name="items[0][bag_count]"
                                                class="bag_count form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle"
                                                disabled></span>
                                        </td>
                                        <td class="text-end fw-bolder" style="padding: 0.7rem; font-size: 0.85rem;">
                                            <span name="items[0][party_quantity]"
                                                class="party_quantity form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle"
                                                disabled></span>
                                        </td>

                                        <td>
                                            <span name="items[0][quantity]"
                                                class="form-control fs-3 fw-bold text-muted bg-light border-1 border-secondary-subtle form-control-sm text-end quantity"
                                                id="quantity" disabled></span>
                                        </td>

                                        <td>
                                            <span name="items[0][inclusive_rate]"
                                                class="form-control text-muted form-control-sm fs-3 fw-bold text-muted bg-light border-1 border-secondary-subtle text-end inclusive_rate"
                                                id="inclusive_rate" disabled>0.00</span>
                                        </td>

                                        <td>
                                            <span name="items[0][rate]"
                                                class="form-control text-muted fw-bold fs-3 bg-light border-1 border-secondary-subtle form-control-sm text-end rate"
                                                id="rate" disabled>0.00</span>

                                        </td>

                                        <td>
                                            <span name="items[0][amount]"
                                                class="form-control text-muted bg-light fs-3 border-1 fw-bold  border-secondary-subtle form-control-sm text-end amount bg-light non-selectable"
                                                id="amount" disabled>0.00</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </thead>
                        </table>
                    </div>                

                    <div class="row g-3">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="row g-4 align-items-start">        
                                        <!-- ===========================
                                            STOCK INFO + WEIGHT DETAILS
                                        ============================ -->
                                        <div class="col-lg-3 col-md-6 col-12">
            
                                        </div>
                                        <div class="col-lg-1">
            
                                        </div>
                                        <!-- ===========================
                                        Particular Table
                                        ============================ -->
                                        <div class="col-lg-4 col-md-6 col-12">
                                            <div class="row">
                                                <div class="col-5">
                                                    <div
                                                        class="rounded p-2 border border-secondary-lt d-flex justify-content-between align-items-center">
            
                                                        <!-- Label -->
                                                        <div class="small d-flex align-items-center">
                                                            <i class="fa-solid fa-cube me-1 text-secondary"></i>
                                                            Total Qty
                                                        </div>
            
                                                        <!-- Value -->
                                                        <div class="fw-bold">
                                                            <input type="text" name="total_qty" id="total_qty"
                                                                class="form-control  border-1 border-secondary-subtle text-end fw-bold"
                                                                style="font-size:1rem !important" disabled >
                                                        </div>
            
                                                    </div>
                                                </div>
                                                <div class="col-7">
                                                    <div
                                                        class="rounded p-2 border border-secondary-lt d-flex justify-content-between align-items-center">
            
                                                        <!-- Label -->
                                                        <div class="small d-flex align-items-center">
                                                            <i class="fa-solid fa-indian-rupee-sign me-1 text-secondary"></i>
                                                            Total Amount
                                                        </div>
            
                                                        <!-- Value -->
                                                        <div class="fw-bold" id="total_amount">                                                            
                                                            <input type="text" name="base_total_amount" id="base_total_amount"
                                                                data-amount=""
                                                                class="form-control  border-1 border-secondary-subtle text-end fw-bold text-secondary"
                                                                style="font-size:1rem !important"  disabled>
                                                        </div>
            
                                                    </div>
                                                </div>
            
                                            </div>
                                            <table class="table table-sm align-middle" id="particular_modal">
                                                <thead>
                                                    <tr>
                                                        {{-- <th width="30px" class="text-center">
                                                            <i class="fa-solid fa-hashtag text-secondary"></i>
                                                        </th> --}}
                                                        <th width="230px">Particular</th>
                                                        <th width="70px" class="text-center">%</th>
                                                        <th width="200px" class="text-end">Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="bill_sundry_table_body">
                                                    {{-- Particular rows will be dynamically added here --}}
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        {{-- <td>
                                                            <span href="#" id="add_particular_row" class="btn btn-action">
                                                                <i class="fa-solid fa-plus" data-bs-toggle="tooltip"
                                                                    data-bs-placement="right" data-bs-html="true"
                                                                    data-bs-original-title="Add Particular"></i>
                                                            </span>
                                                        </td> --}}
                                                        <td colspan="2" class="text-end fw-semibold">
                                                            <div class="d-flex justify-content-between">
                                                                <div>
                                                                    {{-- <span href="#" id="reload_bill_sundry_table"
                                                                        data-bs-toggle="tooltip" data-bs-placement="right"
                                                                        data-bs-html="true"
                                                                        data-bs-original-title="Reload Particular"
                                                                        class="btn btn-action">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                                            height="24" viewBox="0 0 24 24" fill="none"
                                                                            stroke="currentColor" stroke-width="2"
                                                                            stroke-linecap="round" stroke-linejoin="round"
                                                                            class="icon icon-1">
                                                                            <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" />
                                                                            <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" />
                                                                        </svg>
                                                                    </span> --}}
            
                                                                    {{-- <span href="#" id="add_custom_particulars"
                                                                        data-bs-toggle="tooltip" data-bs-placement="right"
                                                                        data-bs-html="true"
                                                                        data-bs-original-title="Add Custom Particular"
                                                                        class="btn btn-action ms-1">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                                            height="24" viewBox="0 0 24 24" fill="none"
                                                                            stroke="currentColor" stroke-width="2"
                                                                            stroke-linecap="round" stroke-linejoin="round"
                                                                            class="icon icon-1">
                                                                            <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                                                            <path
                                                                                d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2z" />
                                                                            <path d="M12 11v6" />
                                                                            <path d="M9 14h6" />
                                                                        </svg>
                                                                    </span> --}}
                                                                </div>
                                                                <div class="my-auto">
                                                                    <span class="small text-muted fs-5">(Payable to Party)</span>
                                                                    <span class="ms-2">Net Total</span>
                                                                </div>
                                                            </div>
                                                        </td>
            
                                                        <td>
                                                            <input type="text" name="net_total" id="net_amount" tabindex="-1"
                                                                class="form-control  border-1 border-secondary-subtle text-end fw-bold text-danger"
                                                                style="font-size:1.1rem !important" disabled>
                                                        </td>
                                                    </tr>
                                                    <tr class="d-none" id="gross_total_row">
                                                        <td colspan="3" class="text-end">Gross Total</td>
                                                        <td>
                                                            <input type="text" name="gross_total" id="gross_total" tabindex="-1"
                                                                class="form-control  border-1 border-secondary-subtle text-end fw-bold text-secondary"
                                                                style="font-size:1rem !important" readonly>
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
            
                                                                             
                                        {{-- TOTAL SUMMARY --}}
                                        <div class="col-lg-4 col-md-12 col-12">
                                            <label for="remarks" class="form-label fw-semibold mb-3">
                                                <i class="fa-regular fa-note-sticky me-2 text-primary"></i>
                                                Narration
                                            </label>
                                            <textarea id="remarks" name="remarks" rows="5" class="form-control non-selectable form-control  border-1 border-secondary-subtle fw-bold text-secondary"
                                                 disabled></textarea>                                            
                                            <div class="mt-2 col-lg-5">
                                                <label for="ewaybill_number" class="form-label fw-semibold">
                                                    <i class="fa-solid fa-file-invoice me-2 text-primary"></i>Ewaybill-Number
                                                </label>
                                                <input type="text" name="ewaybill_number" id="ewaybill_number"
                                                    class="form-control non-selectable form-control  border-1 border-secondary-subtle fw-bold text-secondary" disabled>
                                            </div>
                                        </div>
            
                                    </div>
                                </div>
            
                                <!-- ===========================
                               FOOTER BUTTONS
                        ============================ -->
                                <!-- Footer with Action Buttons -->
                                {{-- <div class="card-footer bg-light border-top p-3">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <small class="text-muted d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-circle-info text-primary me-1"></i>
                                            Review all details before saving
                                            <span class="text-secondary">|</span>
                                            <span><span class="text-danger">*</span> Fields are required</span>
                                            <!-- ✅ ADD THIS -->
                                            <span class="text-secondary">|</span>
                                            <span><i class="fa-solid fa-keyboard me-1"></i> Press <kbd>F1</kbd> for
                                                shortcuts</span>
                                        </small>
            
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-outline-secondary" tabindex="-1"
                                                onclick="this.closest('form').reset()">
                                                <i class="fa-solid fa-rotate-left me-1"></i> Clear
                                            </button>
                                            <button type="submit" class="btn btn-primary px-4 form-save-btn"
                                                data-bs-toggle="tooltip" data-bs-placement="bottom" title="Save (Alt + S)">
                                                @if ($formMode === 'edit')
                                                    <i class="fa-solid fa-pencil me-1"></i>
                                                @else
                                                    <i class="fa-solid fa-floppy-disk me-1"></i>
                                                @endif
                                                {{ $formMode === 'edit' ? 'Update' : 'Save' }}
                                            </button>
                                        </div>
                                    </div>
                                </div> --}}
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <!-- Footer -->
        <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">
                <i class="fa-solid fa-xmark me-1"></i> Close
            </button>
        </div>

    </div>
</div>
