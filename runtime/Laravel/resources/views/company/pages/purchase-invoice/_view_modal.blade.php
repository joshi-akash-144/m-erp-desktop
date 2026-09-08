
<div class="modal fade" id="purchase_invoice_modal" tabindex="-1" aria-labelledby="purchase_invoice_modal_label" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg"
            style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">

            <!-- Modal header -->
            <div class="modal-header text-primary text-white py-3">
                <h4 class="modal-title fw-bold">                    
                    <i class="fa-solid fa-file-invoice text-primary"></i>
                    Purchase invoice - View
                </h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Modal Body - Modern Design -->
            <div class="modal-body pt-1">

            {{-- 💡 FORM START --}}
            
            <form id="purchase_invoice_form" autocomplete="off" data-form-mode="{{ $formMode }}">
            
                @if ($formMode === 'create')
                    <input type="hidden" name="uuid" value="{{ uuid() }}">
                @endif
            
                <!-- 🧾 General Details -->
                <div class="module-form-section">
                    <div class="d-flex align-items-center">
                        <div class="module-page-title">
                            <i class="fa-solid fa-file-pen me-2 text-primary"></i>
                            General Details
                        </div>
                    </div>
            
                    <div class="row g-2">
                        <div class="col-lg-1 col-md-6 col-12">
                            <label class="form-label required">
                                <i class="fa-solid fa-hashtag me-1 text-secondary"></i> Invoice No.
                            </label>
                            @if ($formMode === 'edit')
                                <select name="grn_id" id="grn_id" class="form-select select2">
                                    <option value="">--Select GRN --</option>
                                    @foreach ($grnSerials as $serial)
                                        <option value="{{ $serial->id }}" @if ($serial->id == $grnId) selected @endif>
                                            {{ $serial->grn_serial }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" name="grn_number" id="grn_number" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle"
                                    placeholder="" disabled>
                            @endif
            
                        </div>
            
                        <div class="col-lg-1 col-md-6 col-12">
                            <label class="form-label required">
                                <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Invoice Date
                            </label>
                            <input type="text" name="invoice_date" id="invoice_date" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle"
                                 disabled>
                        </div>
            
            
                        <div class="col-lg-1 col-md-6 col-12">
                            <label class="form-label required">
                                <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> GRN No.
                            </label>                            
                            <input type="text" name="grn_id" id="grn_id" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle" placeholder="" disabled>
                        </div>
            
            
                        <div class="col-lg-1 col-md-6 col-12">
                            <label class="form-label">
                                <i class="fa-solid fa-file-contract me-1 text-secondary"></i> File No.
                            </label>
                            <input type="text" id="file_number" name="file_number" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle" placeholder="" disabled>
                        </div>
            
                        <div class="col-lg-1 col-md-6 col-12">
                            <label class="form-label">
                                <i class="fa-solid fa-user-tie me-1 text-secondary"></i> Sales Inv. No
                            </label>
                            <input type="text" id="sales_inv_serial" name="sales_invoice_serial" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle" placeholder="" disabled>
                        </div>
                        <div class="col-lg-3 col-md-6 col-12">
                            <label class="form-label">
                                <i class="fa-solid fa-user-tie me-1 text-secondary"></i> Supplier Name
                            </label>
                                    
                            <input type="text" name="account_id" id="supplier_name" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle text-truncate"
                                placeholder="" disabled>
            
                            <div id="account_id_loader" class="mt-1 d-none">
                                <span class="text-primary small">
                                    <i class="fa fa-spinner fa-spin me-1"></i> Loading Account Details...
                                </span>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-6 col-12">
                            <label class="form-label">
                                <i class="fa-solid fa-city me-1 text-secondary"></i> City
                            </label>
                            <div class="input-icon mb-3">
                                <input type="text" name="order_date" id="city"
                                    class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle" disabled>
                                <span class="input-icon-addon d-none" id="account_city_loader">
                                    <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                                </span>
                            </div>
                        </div>
            
                        <div class="col-lg-1 col-md-6 col-12">
                            <label class="form-label">
                                <i class="fa-solid fa-percent me-1 text-secondary"></i> Tax Type
                            </label>
                            <div class="input-icon mb-3">
                                <input type="text" name="order_date" id="account_id_type"
                                    class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle" disabled>
                                <span class="input-icon-addon d-none" id="account_type_loader">
                                    <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                                </span>
                            </div>
                        </div>
                    </div>
            
                    <!-- 🧩 Row 2 -->
                    <div class="row row-lg-1 row-md-12 row-sm-12">                       
                        {{-- remove class in this div -> input-icon mb-3 --}}
                        <div class="grid-item col-lg-2 col-md-6 col-sm-12 me-2">
                            <label class="form-label">
                                <i class="fa-solid fa-id-card me-1 text-secondary"></i> Party Bill No.
                            </label>
                            {{-- <input type="text" name="reference_number" id="reference_number" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle" disabled> --}}
                            <input type="text" name="reference_number" id="referenceNumber" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle" disabled>
                            <span class="input-icon-addon d-none" id="reference_number_loader">
                                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                            </span>
                        </div>
                                                
                        <div class="grid-item col-lg-2 col-md-6 col-sm-12">
                            <label class="form-label">
                                <i class="fa-solid fa-user-tie me-1 text-secondary"></i> Broker
                            </label>                           
                            <input type="text" name="broker_id" id="broker_id" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle" disabled>
                        </div>
            
                        <div class="grid-item col-lg-2 col-md-6 col-sm-12 me-2">
                            <label class="form-label">
                                <i class="fa-solid fa-id-card me-1 text-secondary"></i> Vehicle No.
                                {{-- <span class="form-help bg-primary text-white rounded-circle px-2" data-bs-toggle="popover"
                                    data-bs-placement="top" data-bs-html="true" data-bs-content="
                                  <strong>Format:</strong> <code>AA00AA0000</code> <br>
                                  <strong>Rules:</strong><br>
                                  <ul class='list-unstyled mb-0'>
                                    <li><i class='fa-solid fa-1 text-primary me-1'></i> First 2 letters – <b>State code</b> (e.g. <code>GJ</code>, <code>MH</code>)</li>
                                    <li><i class='fa-solid fa-2 text-primary me-1'></i> Next 2 digits – <b>RTO code</b> (e.g. <code>05</code>)</li>
                                    <li><i class='fa-solid fa-3 text-primary me-1'></i> Next 1–2 letters – <b>Series</b> (e.g. <code>AB</code>)</li>
                                    <li><i class='fa-solid fa-4 text-primary me-1'></i> Last 3–4 digits – <b>Vehicle number</b> (e.g. <code>1234</code>)</li>
                                  </ul>
                                  <strong>Example:</strong> <code>GJ05AB1234</code>
                                ">
                                    <i class="fa-solid fa-circle-question"></i>
                                </span> --}}
                            </label>
            
                            <input type="text" name="vehicle_number" id="vehicle_number" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle txtRegNo"
                                disabled />
                        </div>
            
            
                        <div class="grid-item col-lg-2 col-md-6 col-sm-12 me-2">
                            <label class="form-label">
                                <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Party Bill Date
                            </label>
                            <input type="text" name="party_bill_date" id="party_bill_date" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle"
                                placeholder="" disabled>
            
                        </div>
            
                        <div class="grid-item col-lg-2 col-md-6 col-sm-12 me-2">
                            <label class="form-label">
                                <i class="fa-solid fa-user-tie me-1 text-secondary"></i> Invoice Type
                            </label>
                                  
                            <input type="text" name="purchase_type_id" id="purchase_type_id" class="form-control fs-1 fw-bold text-muted bg-light border-1 border-secondary-subtle" disabled>
            
                            {{-- <div id="purchase_type_loader" class="mt-1 d-none">
                                <span class="text-primary small">
                                    <i class="fa fa-spinner fa-spin me-1"></i> Loading Type Details...
                                </span>
                            </div> --}}
                        </div>
                        <div class="grid-item col-lg-1 col-md-12 me-2">
                            <label class="form-label">
                                Invoice Status
                            </label>
                            <span id="invoice_status_view" class="alert p-2 d-block text-center text-muted fw-bold fs-3 fw-bold"
                                style="width:160px; height: 53%;">
                                <!-- Status text inserted here via JavaScript -->
                            </span>
                        </div>
            
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
                                        <span name="items[0][item_id]"
                                            class="form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle item_id text-truncate"
                                            disabled></span>
                                    </td>
                        
                                    <td class="fw-bolder">
                                        <span name="items[0][unit_name]"
                                            class="unit_name form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle"
                                            disabled></span>
                                    </td>
                        
                                    <td class="fw-bolder">
                                        <span
                                            class="purchase_order_serial form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle text-center"
                                            name="items[0][purchase_order_serial]" disabled></span>
                                        <input type="hidden" name="items[0][purchase_order_id]" class="purchase_order_id">
                                        <input type="hidden" name="items[0][purchase_order_detail_id]" class="purchase_order_detail_id">
                                    </td>
                        
                        
                                    <td class="fw-bolder">
                                        <span
                                            class="destination_id form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle text-truncate"
                                            name="items[0][destination_id]" disabled></span>
                                    </td>
                        
                                    <td class="fw-bolder">
                                        <span
                                            class="condition_id form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle text-truncate"
                                            name="items[0][condition_id]" disabled></span>
                                    </td>
                        
                                    <td class="fw-bolder">
                                        <div class="input-icon text-end">
                                            <span name="items[0][cgst_rate]"
                                                class="cgst_rate form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle" id="cgst_rate"
                                                disabled>--</span>
                                        </div>
                                    </td>
                                    <td class="fw-bolder">
                                        <div class="input-icon text-end">
                                            <span name="items[0][sgst_rate]"
                                                class="sgst_rate form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle" id="sgst_rate"
                                                disabled>--</span>
                                        </div>
                                    </td>
                                    <td class="fw-bolder">
                                        <div class="input-icon text-end">
                                            <span name="items[0][igst_rate]"
                                                class="igst_rate form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle" id="igst_rate"
                                                disabled>--</span>
                                        </div>
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
                        
                                    <td class="text-end fw-bolder" style="padding: 0.7rem; font-size: 0.85rem;">
                                        <span name="items[0][quantity]"
                                            class="quantity form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle"
                                            disabled></span>
                                    </td>
                        
                                    <td class="text-end fw-bolder" style="padding: 0.7rem; font-size: 0.85rem;">
                                        <span name="items[0][inclusive_rate]"
                                            class="inclusive_rate form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle"
                                            disabled></span>
                                    </td>
                        
                                    <td class="text-end fw-bolder" style="padding: 0.7rem; font-size: 0.85rem;">
                                        <span name="items[0][rate]"
                                            class="rate form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle"
                                            disabled></span>
                                    </td>
                        
                                    <td class="text-end fw-bolder" style="padding: 0.7rem; font-size: 0.85rem;">
                                        <span name="items[0][amount]"
                                            class="amount form-control form-control-sm fw-bold fs-3 text-muted bg-light border-1 border-secondary-subtle"
                                            disabled></span>
                                    </td>
                        
                                </tr>
                            </tbody>
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
            
                                        <!-- ===========================
                                         TOTAL SUMMARY
                                        ============================ -->
                                        <div class="col-lg-4 col-md-12 col-12">
                                            <label for="remarks" class="form-label fw-semibold mb-3">
                                                <i class="fa-regular fa-note-sticky me-2 text-primary"></i>
                                                Narration
                                            </label>
                                            <textarea id="remarks" name="remarks" rows="5" class="form-control non-selectable form-control  border-1 border-secondary-subtle fw-bold text-secondary "
                                                placeholder="" disabled></textarea>                                                                                   
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

            <!-- Modal Footer - Modern Design -->
            <div class="modal-footer">
                <!-- Footer -->
                <button class="btn btn-secondary waves-effect" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> Close
                </button>
                {{-- {{ $formMode === 'edit' ? 'Update' : 'Save' }} --}}
            </div>

        </div>
    </div>
</div>