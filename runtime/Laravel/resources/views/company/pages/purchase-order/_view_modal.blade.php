<!-- ================================
     PURCHASE ORDER VIEW MODAL
================================ -->
<div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
    <div class="modal-content">

        <!-- Header -->
        <div class="modal-header text-primary text-white py-3">
            <h4 class="modal-title fw-bold">
                <i class="fa-solid fa-cart-shopping me-2"></i>
                Purchase Order - View
            </h4>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
            <form id="purchase_order_form" autocomplete="off">
            
                <!-- 🧾 General Details -->
                <div class="module-form-section">
                    <div class="module-page-title">
                        <i class="fa-solid fa-file-pen me-2 text-primary"></i>
                        General Details
                    </div>
            
                    <div class="row mb-3 row-md-12 row-sm-12">
            
                        <div class="grid-item col-lg-1 col-md-12">
                            <label class="form-label">
                                <i class="fa-solid fa-hashtag me-1 text-secondary"></i> Order Number
                            </label>
                            <input type="text" name="order_number" id="order_number" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle"
                                placeholder="Order Number" value="" disabled>
                        </div>
            
                        <div class="grid-item col-lg-1 col-md-12">
                            <label class="form-label">
                                <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Order Date
                            </label>
                            <input type="text" name="order_date" id="order_date" class="form-control fw-bold date-format bg-light text-muted border-1 border-secondary-subtle" placeholder="DD-MM-YYYY" disabled>
                        </div>
            
                        <div class="grid-item col-lg-1 col-md-12">
                            <label class="form-label">
                                <i class="fa-regular fa-clock me-1 text-secondary"></i> Delivery Days
                            </label>
                            <input type="number" name="delivery_days" id="delivery_days" class="form-control fw-bold  bg-light text-muted  border-1 border-secondary-subtle" disabled>
                        </div>
            
                        <div class="grid-item col-lg-1 col-md-12">
                            <label class="form-label">
                                <i class="fa-solid fa-calendar-check me-1 text-secondary"></i> Due Date
                            </label>
                            <input type="text" name="due_date" id="due_date" class="form-control date-format fw-bold bg-light text-muted border-1 border-secondary-subtle" disabled placeholder="DD-MM-YYYY" disabled>
                        </div>
                        <div class="grid-item col-lg-3 col-md-12">
                            <label class="form-label">
                                <i class="fa-solid fa-user-tie me-1 text-secondary"></i> Broker
                            </label>
                            <input type="text" name="broker_id" id="broker_name" class="form-control bg-light fw-bold text-muted border-1 border-secondary-subtle" disabled>
                        </div>  
            
                        <div class="grid-item col-lg-3 col-md-12">
                            <label class="form-label">
                                <i class="fa-solid fa-file-contract me-1 text-secondary"></i> Contract Number
                            </label>
                            <input type="text" id="contract_number" name="contract_number" class="form-control fw-bold bg-light text-muted border-1 border-secondary-subtle" disabled>
                        </div>
            

                        {{-- Right side Extra field --}}
                        {{-- <div class="col-lg-4 col-md-6 flex-wrap d-flex bg-light p-2 border border-1 border-primary extraDav" style=" width:26%; border-radius: 6px;">
                            <div class="grid-item col-lg-4 col-md-12 me-2">
                                <label class="form-label">
                                    <i class="fa-solid fa-box text-secondary"></i> Received Quantity
                                </label>
                                <input type="text" name="received_qty" id="received_qty"
                                    class="form-control  fw-bold received_qty bg-light text-muted border-1 border-secondary-subtle" disabled>
                            </div>
                            <div class="grid-item col-lg-4 col-md-12 me-2">
                                <label class="form-label">
                                    <i class="fa-solid fa-box text-secondary"></i> Remaining Quantity
                                </label>
                                <input type="text" name="remaining_qty" id="remaining_qty"
                                    class="form-control fw-bold remaining_qty bg-light text-muted border-1 border-secondary-subtle" disabled>
                            </div>                                                
                        </div> --}}

                    </div>
            
                    <!-- Row 2 -->
                    <div class="row row-lg-1 row-md-12 row-sm-12">                                                                                            
                            <div class="grid-item col-lg-3 col-md-12 col-sm-12 me-2">
                                <label class="form-label">
                                    <i class="fa-solid fa-truck-field me-1 text-secondary"></i> Supplier Name
                                </label>
                                <input type="text" name="account_id" id="supplier_name"
                                    class="form-control fw-bold fs-3 bg-light text-muted border-1 border-secondary-subtle" disabled>
                            </div>

                            <div class="grid-item col-lg-2 col-md-12 col-sm-12 me-2">
                                <label class="form-label">
                                    <i class="fa-solid fa-city me-1 text-secondary"></i> City
                                </label>
                                <input type="text" name="order_date" id="city" class="form-control fw-bold f-3  bg-light text-muted border-1 border-secondary-subtle"
                                    disabled>
                            </div>

                            <div class="grid-item col-lg-1 col-md-12 col-sm-12 me-2" >
                                <label class="form-label ">
                                    <i class="fa-solid fa-percent text-secondary me-1"></i> Tax Type
                                </label>
                                <input type="text" name="delivery_days" id="supplier_type" class="form-control fw-bold bg-light text-muted border-1 border-secondary-subtle" disabled>
                            </div>
                
                            <div class="grid-item col-lg-2 col-md-12 me-2">
                                <label class="form-label">
                                    <i class="fa-solid fa-location-dot me-1 text-secondary"></i> Destination
                                </label>
                                <input type="text" name="destination_id" id="destination_name" class="form-control fw-bold bg-light text-muted border-1 border-secondary-subtle text-truncate" disabled>
                            </div>

                            <div class="grid-item col-lg-2 col-md-12 me-2">
                                <label class="form-label">
                                    Order Status
                                </label>
                                <span id="order_status_view" class="alert p-2 d-block text-center text-muted fw-bold fs-3 fw-bold" style=" width: 96px;">
                                    <!-- Status text inserted here via JavaScript -->
                                </span>
                            </div>                                                                                                
                    </div>
                </div>
            
                <!-- 📦 Item Details -->
                <div class="module-form-section">
                    <div class="module-page-title">
                        <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i>
                        Item Details
                    </div>
            
                    <div class="card-body">
                        <div class="table-responsive scrollable-area">
                            <table class="table table-sm align-middle border mb-0">
                                <thead class="table-light text-nowrap">
                                    <tr>
                                        <th width="200" class="fw-semibold">
                                            <i class="fa-solid fa-box text-secondary me-1"></i> Item Name
                                        </th>
                                        <th width="120" class="fw-semibold">
                                            <i class="fa-solid fa-scale-balanced text-secondary me-1"></i> Unit
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
                                        {{-- <td class="text-center text-muted small"><input type="text"
                                                class="form-control form-control-sm text-center" value="1" readonly>
                                        </td> --}}
                    
                                        <td>
                                            <span class="form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle item_id text-truncate" id="item_id" disabled></span>
                                        </td>
                    
                                        <td>                                           
                                            <span name="items[0][unit_name]" class="form-control fs-3  fw-bold text-muted bg-light border-1 border-secondary-subtle form-control-sm unit_name"
                                                id="unit_name" disabled>--</span>
                                        </td>
                    
                                        <td>                                            
                                            <span name="items[0][condition_id]"
                                                class="form-control text-muted form-control-sm fs-3 fw-bold text-muted bg-light border-1 border-secondary-subtle small condition_error condition_id text-truncate" disabled></span>
                                        </td>
                                        <td>
                                            <div class="input-icon text-end">                                               
                                                <span name="items[0][cgst_rate]" class="form-control fw-bold fs-3 form-control-sm text-muted bg-light border-1 border-secondary-subtle cgst_rate"
                                                    id="cgst_rate" disabled></span>
                                                <span class="input-icon-addon d-none cgst_loader">
                                                    <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="input-icon">                                               
                                                <span name="items[0][sgst_rate]" class="form-control fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle form-control-sm text-end sgst_rate"
                                                    value="--" disabled></span>
                                                <span class="input-icon-addon d-none sgst_loader">
                                                    <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="input-icon">                                               
                                                <span name="items[0][igst_rate]" class="form-control fs-3 fw-bold text-muted bg-light border-1 border-secondary-subtle form-control-sm text-end igst_rate"
                                                    value="--" disabled></span>
                                                <span class="input-icon-addon d-none igst_loader">
                                                    <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                                                </span>
                                            </div>
                                        </td>
                    
                                        <td>                                            
                                            <span name="items[0][quantity]" class="form-control fs-3 fw-bold text-muted bg-light border-1 border-secondary-subtle form-control-sm text-end quantity"
                                                id="quantity" disabled></span>
                                        </td>
                    
                                        <td>                                            
                                            <span name="items[0][inclusive_rate]"
                                                class="form-control text-muted form-control-sm fs-3 fw-bold text-muted bg-light border-1 border-secondary-subtle text-end inclusive_rate" id="inclusive_rate" disabled>0.00</span>
                                        </td>
                    
                                        <td>
                                           <span name="items[0][rate]" class="form-control text-muted fw-bold fs-3 bg-light border-1 border-secondary-subtle form-control-sm text-end rate"
                                                id="rate" disabled>0.00</span>
                    
                                        </td>
                    
                                        <td>                                            
                                            <span name="items[0][amount]"
                                                class="form-control text-muted bg-light fs-3 border-1 fw-bold  border-secondary-subtle form-control-sm text-end amount bg-light non-selectable"
                                                id="amount" disabled>0.00</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>                    
                        </div>
                    </div>
            
                    <div class="row g-3 mt-1">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-2">
                                    <div class="row g-3 align-items-center">
                                                               
                                        <!-- Narration -->
                                        <div class="col-lg-4 col-md-12">
                                            <label class="form-label small mb-1">
                                                <i class="fa-regular fa-note-sticky me-1 text-primary"></i>
                                                Narration
                                            </label>
                                            <textarea id="remarks" class="form-control form-control-sm fs-3 fw-bold bg-light text-muted" style="height: 100px" disabled>
                                                </textarea>
                                        </div>
            
                                        <!-- Totals -->
                                        <div class="col-lg-4 col-md-12">
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <div class="rounded p-2 text-center border border-secondary-lt">
                                                        <div class="text-muted fs-4 mb-1">
                                                            <i class="fa-solid fa-cubes me-1 text-secondary"></i>
                                                            Total Qty
                                                        </div>
                                                        <span class="fs-2 fw-bold text-muted" id="total_qty" disabled>0.000</span>
                                                    </div>
                                                </div>
            
                                                <div class="col-6">
                                                    <div class="rounded p-2 text-center border border-secondary-lt">
                                                        <div class="text-muted fs-4 small mb-1">
                                                            <i class="fa-solid fa-indian-rupee-sign me-1 text-secondary"></i>
                                                            Total Amount
                                                        </div>
                                                        <span class="fs-2 fw-bold text-primary" id="total_amount" disabled>₹0.00</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Right side Extra field --}}
                                        <div class="col-lg-4 col-md-6 flex-wrap d-flex p-2 extraDav"
                                            style=" width:32%; background-color: #e6f0fb; border:1px solid #e5e7eb;">
                                            <div class="grid-item col-lg-4 col-md-12 me-2">
                                                <label class="form-label">
                                                    <i class="fa-solid fa-box text-secondary"></i> Received Quantity
                                                </label>
                                                <input type="text" name="received_qty" id="received_qty"
                                                    class="form-control  fw-bold received_qty bg-light text-muted border-1 border-secondary-subtle" disabled>
                                            </div>
                                            <div class="grid-item col-lg-4 col-md-12 me-2">
                                                <label class="form-label">
                                                    <i class="fa-solid fa-box text-secondary"></i> Remaining Quantity
                                                </label>
                                                <input type="text" name="remaining_qty" id="remaining_qty"
                                                    class="form-control fw-bold remaining_qty bg-light text-muted border-1 border-secondary-subtle" disabled>
                                            </div>
                                        </div>
            
                                    </div>
                                </div>                                               
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <!-- Footer -->
        <div class="modal-footer">
            <button class="btn btn-secondary waves-effect" data-bs-dismiss="modal">
                <i class="fa-solid fa-xmark me-1"></i> Close
            </button>
        </div>

    </div>
</div>