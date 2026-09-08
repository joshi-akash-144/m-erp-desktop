<div class="modal fade" id="grn_modal" tabindex="-1" aria-labelledby="grn_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">

            <!-- Modal Header - Modern Design -->
            {{-- <div class="modal-header border-0 bg-gradient" style="background: linear-gradient(135deg, #f8f9fa 0%, #f0f2f5 100%); padding: 1rem;">
                <div class="d-flex align-items-center flex-grow-1">
                    {{-- <div class="p-2 bg-primary text-primary bg-opacity-10 rounded-lg me-2">
                        @include('icons.eye')
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold mb-0 text-prima" id="grn_modal_label" style="font-size: 1.2rem; color: #1a1a2e;">
                            View GRN
                        </h4>
                        <small class="text-muted d-block mt-1" style="font-size: 0.8rem;">
                            View Goods Receipt Note Details
                        </small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="opacity: 0.6; transition: opacity 0.2s ease;"></button>
            </div> --}}
            <div class="modal-header text-primary text-white py-3">
                <h4 class="modal-title fw-bold">
                    <i class="fa-solid fa-receipt text-primary"></i>
                    GRN - View
                </h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Modal Body - Modern Design -->
            <div class="modal-body" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); padding: 0.5rem;">

                {{-- 💡 FORM START --}}
                
                <form action="" id="grn_form" autocomplete="off" data-form-mode="{{ $formMode }}">

                    @if ($formMode === 'create')
                        <input type="hidden" name="uuid" value="{{ uuid() }}">
                    @endif

                    <!-- 🔹 GENERAL DETAILS SECTION - Modern Design -->
                    <div class="module-form-section">
                        <div class="d-flex align-items-center">
                            <div class="module-page-title">
                                <i class="fa-solid fa-file-pen me-2 text-primary"></i>
                                General Details
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-lg-1 col-md-6 col-12 ">
                                <label class="form-label" >
                                    <i class="fa-solid fa-hashtag me-2 text-secondary"></i>GRN No
                                </label>
                                    @if ($formMode === 'view')
                                        <input type="text" id="grn_id" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle" disabled>
                                    @else
                                        <input type="text" name="grn_number" id="grn_number" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle">{{ $grnNumber }}
                                    @endif
                            </div>

                            <div class="col-lg-1 col-md-6 col-12">
                                <label class="form-label">
                                    <i class="fa-regular fa-calendar-days me-2 text-secondary"></i>GRN Date
                                </label>
                                <input type="text" id="grn_date" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle" disabled>
                            </div>

                            <div class="col-lg-1 col-md-6 col-12">
                                <label class="form-label">
                                    <i class="fa-regular fa-calendar-days me-2 text-secondary"></i>GRN In Date
                                </label>
                                <input type="text" id="grn_in_date" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle" disabled>
                            </div>

                            <div class="col-lg-1 col-md-6 col-12">
                                <label class="form-label">
                                    <i class="fa-regular fa-calendar-days me-2 text-secondary"></i>GRN Out Date
                                </label>
                                <input type="text" id="grn_out_date" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle" disabled></input>
                            </div>

                            <div class="col-lg-3 col-md-6 col-12">
                                <label class="form-label">
                                    <i class="fa-solid fa-file-contract me-2 text-secondary" style="font-size: 0.8rem;"></i>Contract Number
                                </label>
                                <input type="text" id="contract_number" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle" disabled>
                            </div>

                            <div class="col-lg-3 col-md-6 col-12">
                                <label class="form-label">
                                    <i class="fa-solid fa-user-tie me-2 text-secondary"></i>Broker
                                </label>
                                <input type="text" id="broker_name" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle" disabled>
                            </div>
                        </div>                        
                    </div>                    
                     <!-- Supplier Information section -->
                    <div class="module-form-section">                                                
                        <div class="module-page-title">                            
                                <i class="fa-solid fa-warehouse text-primary"></i>&nbsp;
                                Supplier Information                            
                        </div>
                        <div class="row g-2">
                            <div class="col-lg-3 col-md-6 col-12">
                                <label class="form-label">
                                    <i class="fa-solid fa-truck-field me-2 text-secondary"></i>Supplier Name 
                                </label>
                                <input type="text" id="account_name" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle text-truncate" disabled>
                            </div>
                    
                            <div class="col-lg-2 col-md-6 col-12">
                                <label class="form-label">
                                    <i class="fa-solid fa-city me-2 text-secondary"></i>City
                                </label>
                                <input type="text" id="account_id_city" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle" disabled>
                            </div>
                    
                            <div class="col-lg-1 col-md-6 col-12">
                                <label class="form-label">
                                    <i class="fa-solid fa-percent text-secondary"></i> Tax Type                                                                 
                                </label>
                                <input type="text" id="account_id_type" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle" disabled>
                            </div>
                    
                            <div class="col-lg-1 col-md-6 col-12">
                                <label class="form-label">
                                    <i class="fa-solid fa-id-card me-2 text-secondary"></i>Party Bill No
                                </label>
                                <input type="text" id="reference_number" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle" disabled>
                            </div>
                    
                            <div class="col-lg-1 col-md-6 col-12">
                                <label class="form-label">
                                    <i class="fa-solid fa-car me-2 text-secondary"></i>Vehicle No
                                </label>
                                <input type="text" id="vehicle_number" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle" disabled>
                            </div>
                            <div class="col-lg-1 col-md-6 col-12">
                                <label class="form-label">
                                    Grn Status
                                </label>                                                            
                                <span id="grn_status_view" class="alert p-2 d-block text-center text-muted fw-bold fs-3 fw-bold w-51 h-51">
                                    <!-- Status text inserted here via JavaScript -->
                                </span>
                            </div>
                        </div>                        
                    </div>

                    <!-- 📦 ITEM TABLE - Modern Design -->
                    <div class="module-form-section mb-1 ">                        
                        <div class="module-page-title">
                            <i class="fa-solid fa-boxes-stacked text-primary"></i>&nbsp;Item Details
                        </div>

                        <div class="table-responsive scrollable-area  border border-secondary-lt shadow-sm" style=" background: #ffffff;">
                            <table class="table  table-sm align-middle border-0 mt-0" id="item_table">
                                <thead class="table-light text-nowrap" style="background: linear-gradient(135deg, #f0f2f5 0%, #e9ecef 100%); border-bottom: 2px solid #dee2e6;">
                                    <tr>
                                        <th width="40" class="fw-semibold">                                           
                                            <i class="fa-solid fa-hashtag text-secondary me-2"></i>
                                        </th>

                                        <th width="200" class="fw-semibold">
                                            <i class="fa-solid fa-box text-secondary me-2"></i> Item Name
                                        </th>

                                        <th width="200" class="fw-semibold">
                                            <i class="fa-solid fa-ruler-combined text-secondary me-2"></i> Unit
                                        </th>

                                        <th width="200" class="fw-semibold">
                                            <i class="fa-solid fa-file-invoice text-secondary me-2"></i> Order No.
                                        </th>

                                        <th width="200" class="fw-semibold">
                                            <i class="fa-solid fa-location-dot text-secondary me-2"></i> Destination
                                        </th>

                                        <th width="200" class="fw-semibold">
                                            <i class="fa-solid fa-clipboard-check text-secondary me-2"></i> Condition
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
                                            <i class="fa-solid fa-boxes-stacked text-secondary"></i> Bags
                                        </th>

                                        <th class="text-center fw-semibold" style="width:140px;">
                                            <i class="fa-solid fa-weight-hanging text-secondary me-2"></i> P.Qty
                                        </th>

                                        <th class="text-center fw-semibold" style="width:140px;">
                                            <i class="fa-solid fa-weight-hanging text-secondary me-2"></i> Qty
                                        </th>

                                        <th class="text-center fw-semibold" style="width:160px;">
                                            <i class="fa-solid fa-money-bill-wave text-secondary me-2"></i> Incl. Rate
                                        </th>

                                        <th class="text-center fw-bold text-dark" style="width:160px; font-size: 0.9rem;">
                                            <i class="fa-solid fa-tag text-secondary me-2"></i> Rate
                                        </th>

                                        <th class="text-center fw-bold text-dark" style="width:190px; font-size: 0.9rem;">
                                            <i class="fa-solid fa-indian-rupee-sign text-secondary me-2"></i> Amount
                                        </th>
                                    </tr>


                                </thead>

                                <tbody id="item_table_body">
                                    <tr data-row-id="1" class="item-row">
                                        <td class="p-2 text-center fw-bolder fs-3">
                                            <span class=" bg-opacity-20 fw-bold text-muted">1</span>
                                        </td>

                                        <td class="fw-bolder">                                            
                                            <span name="items[0][item_id]" class="form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle item_id text-truncate" disabled></span>
                                        </td>

                                        <td class="fw-bolder">                                           
                                                <span name="items[0][unit_name]" class="unit_name form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle" disabled></span>                                            
                                        </td>

                                        <td class="fw-bolder">
                                            <span class="purchase_order_serial form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle text-center" name="items[0][purchase_order_serial]" disabled></span>                                                
                                            <input type="hidden" name="items[0][purchase_order_id]" class="purchase_order_id">
                                            <input type="hidden" name="items[0][purchase_order_item_id]" class="purchase_order_item_id">
                                        </td>


                                        <td class="fw-bolder">
                                            <span class="destination_id form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle text-truncate" name="items[0][destination_id]" disabled></span>
                                        </td>

                                        <td class="fw-bolder">
                                            <span class="condition_id form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle text-truncate" name="items[0][condition_id]" disabled></span>
                                        </td>

                                        <td class="fw-bolder">
                                            <div class="input-icon text-end">
                                                <span name="items[0][cgst_rate]" class="cgst_rate form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle" disabled>--</span>
                                            </div>
                                        </td>
                                        <td class="fw-bolder">
                                            <div class="input-icon text-end">
                                                <span name="items[0][sgst_rate]" class="sgst_rate form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle" disabled>--</span>
                                            </div>
                                        </td>
                                        <td class="fw-bolder">
                                            <div class="input-icon text-end">
                                                <span name="items[0][igst_rate]" class="igst_rate form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle" disabled>--</span>
                                            </div>
                                        </td>
                                        <td class="text-end fw-bolder">
                                            <span name="items[0][bag_count]" class="bag_count form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle" disabled></span>
                                        </td>
                                        <td class="text-end fw-bolder" style="padding: 0.7rem; font-size: 0.85rem;">
                                            <span name="items[0][party_quantity]" class="party_quantity form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle" disabled></span>
                                        </td>

                                        <td class="text-end fw-bolder" style="padding: 0.7rem; font-size: 0.85rem;">
                                            <span name="items[0][quantity]" class="quantity form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle" disabled></span>
                                        </td>

                                        <td class="text-end fw-bolder" style="padding: 0.7rem; font-size: 0.85rem;">
                                            <span name="items[0][inclusive_rate]" class="inclusive_rate form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle" disabled></span>
                                        </td>

                                        <td class="text-end fw-bolder" style="padding: 0.7rem; font-size: 0.85rem;">
                                            <span name="items[0][rate]" class="rate form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle" disabled></span>
                                        </td>

                                        <td class="text-end fw-bolder" style="padding: 0.7rem; font-size: 0.85rem;">
                                            <span name="items[0][amount]" class="amount form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle" disabled></span>
                                        </td>

                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 📊 WEIGHT & SUMMARY SECTION - Modern Design -->
                    <div class="row g-2 mb-3">
                        <!-- Left Column: Weight Details -->
                        <div class="col-lg-4 col-md-4 col-12">
                            <div class="border-2 shadow-sm overflow-hidden">
                                <div class="card-header border-0 bg-gradient p-2 d-flex align-items-center" style="background: linear-gradient(135deg, #f0f2f5 0%, #e9ecef 100%);">
                                    <div class="p-2 bg-opacity-10 rounded-lg">
                                        <i class="fa-solid fa-weight-hanging text-primary" style="font-size: 1rem;"></i>
                                    </div>
                                    <span class="mb-0 fw-bold" style="color: #1a1a2e; font-size: 0.95rem;">Weight Details</span>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-border align-middle m-0">
                                        <tbody>
                                            <tr class="border-bottom-light" style="border-bottom: 1px solid #f0f2f5 !important;">
                                                <td class="ps-4 p-1 rounded-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="p-2 bg-danger bg-opacity-10 rounded-lg me-2" style="font-size: 0.8rem;">
                                                            <i class="fa-solid fa-weight-hanging text-danger" style="font-size: 0.9rem;"></i>
                                                        </div>
                                                        <span class="fw-semibold text-dark" style="font-size: 0.90rem;">Gross Weight</span>
                                                    </div>
                                                </td>
                                                <td class="text-end" style="padding: 0.7rem;">
                                                    <span id="gross_weight" class="fw-bold text-dark text-muted fs-3"></span>
                                                </td>
                                            </tr>
                                            <tr class="border-bottom-light" style="border-bottom: 1px solid #f0f2f5 !important;">
                                                <td class="ps-4 p-1 rounded-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="p-2 bg-warning bg-opacity-10 rounded-lg me-2" style="font-size: 0.8rem;">
                                                            <i class="fa-solid fa-scale-balanced text-warning" style="font-size: 0.9rem;"></i>
                                                        </div>
                                                        <span class="fw-semibold text-dark" style="font-size: 0.85rem;">Tare Weight</span>
                                                    </div>
                                                </td>
                                                <td class="text-end" style="padding: 0.7rem;">
                                                    <span id="tare_weight" class="fw-bold text-dark text-muted fs-3"></span>
                                                </td>
                                            </tr>
                                            <tr class="border-bottom-light" style="border-bottom: 1px solid #f0f2f5 !important;">
                                                <td class="ps-4 p-1 rounded-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="p-2 bg-success bg-opacity-10 rounded-lg me-2" style="font-size: 0.8rem;">
                                                            <i class="fa-solid fa-weight-scale text-success" style="font-size: 0.9rem;"></i>
                                                        </div>
                                                        <span class="fw-semibold text-dark" style="font-size: 0.85rem;">Net Weight <small class="text-muted">(Gross - Tare)</small></span>
                                                    </div>
                                                </td>
                                                <td class="text-end" style="padding: 0.7rem;">
                                                    <span id="net_weight" class="fw-bold text-success text-muted fs-3"></span>
                                                </td>
                                            </tr>
                                            <tr style="background: linear-gradient(135deg, #f0f2f5 0%, #ffffff 100%); border-top: 2px dashed #dee2e6;">
                                                <td class="ps-4 p-1 rounded-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="p-2 bg-info bg-opacity-10 rounded-lg me-2" style="font-size: 0.8rem;">
                                                            <i class="fa-solid fa-bag-shopping text-info" style="font-size: 0.9rem;"></i>
                                                        </div>
                                                        <span class="fw-semibold text-dark" style="font-size: 0.85rem;">Bag Type</span>
                                                    </div>
                                                </td>
                                                <td class="text-end" style="padding: 0.5rem;">
                                                    <span id="bag_type" class="bg-opacity-20 text-info fw-semibold text-muted fs-3"></span>
                                                </td>
                                            </tr>
                                            <tr style="border-bottom: 1px solid #f0f2f5;">
                                                <td class="ps-4 p-1 rounded-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="p-2 bg-secondary bg-opacity-10 rounded-lg me-2" style="font-size: 0.8rem;">
                                                            <i class="fa-solid fa-hashtag text-secondary" style="font-size: 0.9rem;"></i>
                                                        </div>
                                                        <span class="fw-semibold text-dark" style="font-size: 0.85rem;">Bag Count</span>
                                                    </div>
                                                </td>
                                                <td class="text-end" style="padding: 0.7rem;">
                                                    <span id="bag_count" class="fw-bold text-dark bag_count text-muted fs-3"></span>
                                                </td>
                                            </tr>
                                            <tr style="border-bottom: 1px solid #f0f2f5;">
                                                <td class="ps-4 p-1 rounded-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="p-2 bg-primary bg-opacity-10 rounded-lg me-2" style="font-size: 0.8rem;">
                                                            <i class="fa-solid fa-weight text-primary" style="font-size: 0.9rem;"></i>
                                                        </div>
                                                        <span class="fw-semibold text-dark" style="font-size: 0.85rem;">Net Weight Without Bags</span>
                                                    </div>
                                                </td>
                                                <td class="text-end" style="padding: 0.7rem;">
                                                    <span id="net_weight_bag" class="fw-bold text-primary text-muted fs-3"></span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Narration & Summary -->
                        <div class="col-lg-6 col-md-6 col-12">
                            <div class="border-2 shadow-sm overflow-hidden mb-2">
                                <div class="card-header border-0 bg-gradient p-2 d-flex align-items-center">
                                    <div class="p-1 bg-opacity-10 rounded-lg">
                                        <i class="fa-regular fa-note-sticky text-primary" style="font-size: 1rem;"></i>
                                    </div>
                                    <span class="mb-0 fw-bold" style="color: #1a1a2e; font-size: 0.95rem;">Narration</span>
                                </div>
                                
                                <div class="border-secondary-lt p-2 pt-0">
                                    <span id="remarks" name="remarks" class="form-control fs-3 fw-bold bg-light text-muted h-50"></span>
                                </div>                                                                   
                                <div class="card-header border-0 bg-gradient p-2 d-flex align-items-center">
                                        <div class="col-lg-12 col-md-12">
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
                                </div>
                            </div>

                            <!-- Summary Cards -->
                            
                        </div>
                    </div>

                </form>

            </div>

            <!-- Modal Footer - Modern Design -->
            <div class="modal-footer">
                <!-- Footer -->                
                    <button class="btn btn-secondary waves-effect" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Close
                    </button>               
                    {{-- {{ $formMode === 'edit' ? 'Update' : 'Save' }}                --}}
            </div>

        </div>
    </div>
</div>
