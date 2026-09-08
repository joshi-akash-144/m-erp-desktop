{{-- @php
    $route = $formMode === 'create' ? route('delivery-challan.store') : '';
@endphp

<form action="{{ $route }}" id="delivery_challan_form" autocomplete="off" data-form-mode="{{ $formMode }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($formMode === 'edit')
        @method('PUT')
    @endif
    
    @if ($formMode === 'create')
        <input type="hidden" name="uuid" value="{{ Str::uuid() }}">
    @endif --}}
    <div class="module-form-section section-sales border-sales">
        <div class="module-page-title text-sales">
            <i class="fa-solid fa-file-invoice me-2"></i>
            General Information
        </div>

        <div class="grid-row m-erp-so-row-1 row-body">
            {{-- GRN No --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-hashtag me-1 text-secondary"></i> GRN No
                </label>
                <input type="text" name="grn_no" id="grn_no" class="form-control">
            </div>

            {{-- Challan No --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-hashtag me-1 text-secondary"></i> Challan No
                </label>
                <input type="text" name="challan_number" id="challan_number" class="form-control">
            </div>

            {{-- Challan Date --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Challan Date
                </label>
                <input type="text" name="challan_date" id="challan_date" class="form-control date-format" value="{{ date('d-m-Y') }}">
            </div>

            {{-- Supplier --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-truck-field me-1 text-secondary"></i> Supplier
                </label>
                <select name="supplier_id" id="supplier_id" class="form-select select2">
                    <option value="">--Select Supplier--</option>
                </select>
            </div>

            {{-- City --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-city me-1 text-secondary"></i> City
                </label>
                <input type="text" name="city" id="city" class="form-control">
            </div>

            {{-- Vehicle No --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-truck-moving me-1 text-secondary"></i> Vehicle No
                </label>
                <input type="text" name="vehicle_no" id="vehicle_no" class="form-control">
            </div>
        </div>

        <div class="grid-row m-erp-so-row-2 row-body">
            {{-- Product --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-boxes-stacked me-1 text-secondary"></i> Product
                </label>
                <select name="product_id" id="product_id" class="form-select select2">
                    <option value="">--Select Product--</option>
                </select>
            </div>

            {{-- Customer --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-user-tie me-1 text-secondary"></i> Customer
                </label>
                <select name="customer_id" id="customer_id" class="form-select select2">
                    <option value="">--Select Customer--</option>
                </select>
            </div>

            {{-- Destination --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-location-dot me-1 text-secondary"></i> Destination
                </label>
                <select name="destination" id="destination" class="form-select select2">
                    <option value="">--Select Destination--</option>
                    <option value="HIMATNAGAR" selected>HIMATNAGAR</option>
                </select>
            </div>

            {{-- Party Order No --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-clipboard-list me-1 text-secondary"></i> Party Order No
                </label>
                <select name="party_order_no" id="party_order_no" class="form-select select2">
                    <option value="">--Select party order--</option>
                </select>
            </div>

            {{-- Dairy GRN No --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-hashtag me-1 text-secondary"></i> Dairy GRN No
                </label>
                <input type="text" name="dairy_grn_no" id="dairy_grn_no" class="form-control">
            </div>

            {{-- Dairy GRN Date --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Dairy GRN Date
                </label>
                <input type="text" name="dairy_grn_date" id="dairy_grn_date" class="form-control date-format" placeholder="DD-MM-YYYY">
            </div>
        </div>

        <div class="grid-row m-erp-so-row-3 row-body">
            {{-- Party Qty --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-scale-balanced me-1 text-secondary"></i> Party Qty
                </label>
                <input type="number" name="party_qty" id="party_qty" class="form-control" value="0">
            </div>

            {{-- Dairy Qty --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-cubes me-1 text-secondary"></i> Dairy Qty
                </label>
                <input type="number" step="0.001" name="dairy_qty" id="dairy_qty" class="form-control" value="0.000">
            </div>

            {{-- DC Date In --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-regular fa-calendar-check me-1 text-secondary"></i> DC Date In
                </label>
                <input type="text" name="dc_date_in" id="dc_date_in" class="form-control date-format" placeholder="DD-MM-YYYY">
            </div>

            {{-- Date Out --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-regular fa-calendar-xmark me-1 text-secondary"></i> Date Out
                </label>
                <input type="text" name="date_out" id="date_out" class="form-control date-format" placeholder="DD-MM-YYYY">
            </div>
        </div>
    </div>

    <!-- ⚖️ Weight & Bag Details -->
    <div class="module-form-section section-sales border-sales mt-3">
        <div class="module-page-title text-sales">
            <i class="fa-solid fa-weight-hanging me-2"></i>
            Weight & Bag Details
        </div>

        <div class="grid-row m-erp-so-row-4 row-body">
            {{-- Gross Weight --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-weight-scale me-1 text-secondary"></i> Gross Weight
                </label>
                <input type="number" step="0.001" name="gross_weight" id="gross_weight" class="form-control" value="0.000">
            </div>

            {{-- Tare Weight --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-scale-unbalanced me-1 text-secondary"></i> Tare Weight
                </label>
                <input type="number" step="0.001" name="tare_weight" id="tare_weight" class="form-control" value="0.000">
            </div>

            {{-- Net Weight --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-scale-balanced me-1 text-secondary"></i> Net Weight
                </label>
                <input type="number" step="0.001" name="net_weight" id="net_weight" class="form-control bg-light" value="0.000" readonly>
            </div>

            {{-- Net Weight Without Bags --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-bag-shopping me-1 text-secondary"></i> Net Weight Without Bags
                </label>
                <input type="number" step="0.001" name="net_weight_without_bags" id="net_weight_without_bags" class="form-control bg-light" value="0.000" readonly>
            </div>
     
            {{-- Gunny Bags Type --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-tags me-1 text-secondary"></i> Bag Type
                </label>
                <select name="bag_type" id="bag_type" class="form-select">
                    <option value="Gunny Bags">Gunny Bags</option>
                </select>
            </div>

            {{-- Bag Count --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-list-ol me-1 text-secondary"></i> Bag Count
                </label>
                <input type="number" name="gunny_bags" id="gunny_bags" class="form-control" value="0">
            </div>
            {{-- Status --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-circle-dot me-1 text-secondary"></i> Status
                </label>
                <select name="status" id="status" class="form-select">
                    <option value="">--Select Status--</option>
                    <option value="Pending">Pending</option>
                    <option value="Completed">Completed</option>
                </select>
            </div>
   </div>

    <div class="grid-row m-erp-so-row-5 row-body mt-2">
            {{-- Dairy Grn File --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-file-arrow-up me-1 text-secondary"></i> Dairy Grn File
                </label>
                <input type="file" name="dairy_grn_file" id="dairy_grn_file" class="form-control">
            </div>
        </div>


        {{-- Remarks --}}

    <div class="row g-3 mt-1">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-1">
                        <div class="col-12">
                <label class="form-label">
                    <i class="fa-regular fa-note-sticky me-1 text-secondary"></i> Additional Remarks
                </label>
                <textarea name="remarks" id="remarks" class="form-control" rows="3" placeholder="Enter any additional information here..."></textarea>
            </div>
                    </div>

                    <!-- Footer with Action Buttons -->
                    <div class="card-footer bg-light border-top p-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <small class="text-muted d-flex align-items-center gap-2">
                                <i class="fa-solid fa-circle-info text-primary me-1"></i>
                                Review all details before saving
                                <span class="text-secondary">|</span>
                                <span><span class="text-danger">*</span> Fields are required</span>
                            </small>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="this.closest('form').reset()">
                                    <i class="fa-solid fa-rotate-left me-1"></i> Clear
                                </button>
                                <button type="submit" class="btn btn-sales px-4 form-save-btn"
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
                    </div>
                </div>
            </div>
        </div>

</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const grossWeight = document.getElementById('gross_weight');
        const tareWeight = document.getElementById('tare_weight');
        const netWeight = document.getElementById('net_weight');
        const netWeightWithoutBags = document.getElementById('net_weight_without_bags');

        function calculateNetWeight() {
            const gross = parseFloat(grossWeight.value) || 0;
            const tare = parseFloat(tareWeight.value) || 0;
            const net = gross - tare;
            netWeight.value = net.toFixed(3);
            netWeightWithoutBags.value = net.toFixed(3);
        }

        if (grossWeight && tareWeight) {
            grossWeight.addEventListener('input', calculateNetWeight);
            tareWeight.addEventListener('input', calculateNetWeight);
        }
    });
</script>