{{-- {{ dd($orderNumber, $suppliers, $brokers, $formMode) }} --}}
@php
    $route = $formMode === 'create' ? route('grns.store') : '';
@endphp

<form action="{{ $route }}" id="grn_form" autocomplete="off" data-form-mode="{{ $formMode }}" method="POST">

    @if ($formMode === 'create')
        <input type="hidden" name="uuid" value="{{ uuid() }}">
    @endif

    <!-- 🧾 General Details -->
    <div class="module-form-section">
        <div class="module-page-title">
            <i class="fa-solid fa-file-pen me-2 text-primary"></i>
            General Details
        </div>

        <div class="grid-row m-erp-po-row-1 row-body">
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-hashtag me-1 text-secondary"></i> GRN No.
                </label>
                @if ($formMode === 'edit')
                    <select name="" id="grn_id" class="form-select select2">
                        <option value="">--Select GRN --</option>
                        @foreach ($grnSerials as $serial)
                            <option value="{{ $serial->id }}" @if ($serial->id == $grnId) selected @endif>
                                {{ $serial->grn_serial }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="text" name="grn_number" id="grn_number" class="form-control bg-primary-lt fw-bold text-dark"
                        placeholder="GRN Number" value="{{ $grnSerial }}" disabled>
                @endif

            </div>

            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> GRN Date
                </label>
                <input type="text" name="grn_date" id="grn_date" class="form-control date-format"
                    placeholder="DD-MM-YYYY">
            </div>


            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> GRN In Date
                </label>
                <input type="text" name="grn_in_date" id="grn_in_date" class="form-control date-format"
                    placeholder="DD-MM-YYYY">
            </div>

            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> GRN Out Date
                </label>
                <input type="text" name="grn_out_date" id="grn_out_date" class="form-control date-format"
                    placeholder="DD-MM-YYYY">
            </div>

            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-truck-field me-1 text-secondary"></i> Supplier Name
                </label>
                <select name="account_id" id="account_id" class="form-select">
                    <option value="">Select Supplier</option>
                    @foreach ($accounts as $account)
                        @php
                        $city = $account->city ? $account->city : null;
                    @endphp
                        <option value="{{ $account->id }}">{{ $account->name }} @if($city) ({{ $city }}) @endif</option>
                    @endforeach
                </select>
            </div>

            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-city me-1 text-secondary"></i> City
                </label>
                <div class="input-icon mb-3">
                    <input type="text" name="order_date" id="account_id_city" class="form-control bg-light border-1 border-secondary-subtle" disabled>
                    <span class="input-icon-addon d-none" id="account_city_loader">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                    </span>
                </div>
            </div>

            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-percent me-1 text-secondary"></i> Tax Type
                </label>
                <div class="input-icon mb-3">
                    <input type="text" name="order_date" id="account_id_type" class="form-control bg-light border-1 border-secondary-subtle" disabled>
                    <span class="input-icon-addon d-none" id="account_type_loader">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                    </span>
                </div>
            </div>

           
        </div>

        <!-- 🧩 Row 2 -->
        <div class="grid-row m-erp-po-row-2 row-body">

                        <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-id-card me-1 text-secondary"></i> Party Bill No.
                </label>
                <div class="input-icon mb-3">
                    <input type="text" name="reference_number" id="reference_number" class="form-control">
                    <span class="input-icon-addon d-none" id="reference_number_loader">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                    </span>
                </div>
                
            </div>
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-id-card me-1 text-secondary"></i> Vehicle No.
                    <span class="form-help bg-primary text-white rounded-circle px-2" data-bs-toggle="popover"
                        data-bs-placement="top" data-bs-html="true"
                        data-bs-content="
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
                    </span>
                </label>

                <input type="text" name="vehicle_number" id="vehicle_number" class="form-control txtRegNo"
                    placeholder="e.g. GJ05AB1234" />
            </div>

              <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-user-tie me-1 text-secondary"></i> Broker
                </label>
                <select name="broker_id" id="broker_id" class="form-select">
                    <option value="">Select Broker</option>
                    @foreach ($brokers as $broker)
                        @php
                        $brokerCity = $broker->city ? $broker->city : null;
                    @endphp
                        <option value="{{ $broker->id }}">{{ $broker->name }} @if($brokerCity) ({{ $brokerCity }}) @endif</option>
                    @endforeach
                </select>
            </div>

             <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-file-contract me-1 text-secondary"></i> Contract Number
                </label>
                <input type="text" id="contract_number" name="contract_number" class="form-control" placeholder="">
            </div>

          
            {{-- <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-truck-field me-1 text-secondary"></i> Supplier Name
                </label>
                <select name="account_id" id="account_id" class="form-select">
                    <option value="">Select Supplier</option>
                    @foreach ($suppliers as $supplier)
                        @php
                        $city = $supplier->city ? $supplier->city : null;
                    @endphp
                        <option value="{{ $supplier->id }}">{{ $supplier->name }} @if($city) ({{ $city }}) @endif</option>
                    @endforeach
                </select>
            </div> --}}

            {{-- <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-city me-1 text-secondary"></i> City
                </label>
                <div class="input-icon mb-3">
                    <input type="text" name="order_date" id="account_id_city" class="form-control bg-light border-1 border-secondary-subtle" disabled>
                    <span class="input-icon-addon d-none" id="account_city_loader">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                    </span>
                </div>
            </div>

            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-percent me-1 text-secondary"></i> Tax Type
                </label>
                <div class="input-icon mb-3">
                    <input type="text" name="order_date" id="account_id_type" class="form-control bg-light border-1 border-secondary-subtle" disabled>
                    <span class="input-icon-addon d-none" id="account_type_loader">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                    </span>
                </div>
            </div> --}}





        </div>
    </div>

    <!-- 📦 Item Details -->
    <div class="module-form-section">
        <div class="d-flex justify-content-between">
            <div class="module-page-title">
                <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i>
                Item Details
            </div>
            <div>
                <button id="add_grn_item_row" type="button" class="btn btn-primary btn-sm fw-bold non-selectable waves-effect"
                    data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" tabindex="-1">
                    <i class="fa-solid fa-plus me-1"></i> Add Line
                </button>

            </div>
        </div>

        <div class="table-scroll-x" style="overflow-x:scroll">
            @include('company.pages.grn._item-table')
        </div>

        <div class="row g-3 mt-1">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="row g-4 align-items-start">

                            <!-- ===========================
                         STOCK INFO + WEIGHT DETAILS
                    ============================ -->
                            <div class="col-lg-4 col-md-6 col-12">

                                {{-- <!-- Current Stock Badge -->
                                <div
                                    class="alert alert-success bg-success bg-opacity-10 border-success text-center">
                                    <div class="text-muted small">
                                        <i class="fa-solid fa-warehouse me-1"></i>
                                        Current Stock
                                    </div>
                                    <div class="fw-bold text-success" style="font-size: 1rem;">0.00</div>
                                </div> --}}

                                <!-- Weight Details Section -->
                                <div class="module-form-section">
                                    <div class="module-page-title mb-3">
                                        <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i>
                                        <span class="fw-semibold">Weight Details</span>
                                    </div>

                                    <table class="table table-borderless align-middle weight-table">
                                        <tbody>
                                            <tr>
                                                <td class="fw-semibold" style="width: 60%;">
                                                    <i class="fa-solid fa-weight-hanging me-2 text-primary"></i>
                                                    Gross Weight
                                                </td>
                                                <td>
                                                    <input type="text" id="gross_weight" name="gross_weight"
                                                        class="form-control text-end" placeholder="0.00">
                                                </td>
                                            </tr>

                                            <tr>
                                                <td class="fw-semibold">
                                                    <i class="fa-solid fa-scale-balanced me-2 text-warning"></i>
                                                    Tare Weight
                                                </td>
                                                <td>
                                                    <input type="text" id="tare_weight" name="tare_weight"
                                                        class="form-control text-end" placeholder="0.00">
                                                </td>
                                            </tr>

                                            <tr>
                                                <td class="fw-semibold">
                                                    <i class="fa-solid fa-weight-scale me-2 text-success"></i>
                                                    Net Weight <small class="text-muted small">(Gross - Tare)</small>
                                                </td>
                                                <td>
                                                    <input type="text" id="net_weight" name="net_weight"
                                                        class="form-control bg-light text-end border-1 border-secondary-subtle" value="0.00"
                                                        readonly>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td colspan="2" class="p-0">
                                                    <hr class="my-2">
                                                </td>
                                            </tr>

                                            <tr>
                                                <td class="fw-semibold">
                                                    <i class="fa-solid fa-bag-shopping me-2"></i>
                                                    Bag Type
                                                </td>
                                                <td>
                                                    @php
                                                        $bagTypes = config('constants.bag_types');
                                                    @endphp
                                                    <select id="bag_type" name="bag_type"
                                                        class="form-select select2">
                                                        @foreach ($bagTypes as $bagTypeKey => $bagType)
                                                            <option value="{{ $bagTypeKey }}">{{ $bagType }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td class="fw-semibold">
                                                    <i class="fa-solid fa-hashtag me-2"></i>
                                                    Bag Count
                                                </td>
                                                <td>
                                                    <input type="text" id="bag_count" name="bag_count"
                                                        class="form-control text-end" placeholder="0">
                                                </td>
                                            </tr>

                                            <tr>
                                                <td class="fw-semibold bg-info bg-opacity-10 rounded-start">
                                                    <i class="fa-solid fa-weight me-2 text-info"></i>
                                                    Net Weight Without Bags
                                                </td>
                                                <td class="bg-info bg-opacity-10 rounded-end">
                                                    <input type="text" id="net_weight_without_bags" tabindex="-1"
                                                        name="net_without_bags" class="form-control text-end bg-light border-1 border-secondary-subtle"
                                                        readonly value="0.00">
                                                </td>
                                            </tr>

                                        </tbody>
                                    </table>

                                </div>
                            </div>

                            <!-- ===========================
                            NARRATION FIELD
                            ============================ -->
                            <div class="col-lg-4 col-md-6 col-12">
                                <label for="remarks" class="form-label fw-semibold mb-3">
                                    <i class="fa-regular fa-note-sticky me-2 text-primary"></i>
                                    Narration
                                </label>
                                {{-- remove class non-selectable in this field --}}
                                <textarea id="remarks" name="remarks" rows="10" class="form-control non-selectable"
                                    placeholder="Enter additional notes, remarks, or special instructions..."></textarea>
                                <small class="text-muted mt-2 d-block">
                                    <i class="fa-solid fa-circle-info me-1"></i>
                                    Optional: Add any additional information or notes
                                </small>
                            </div>

                            <!-- ===========================
                             TOTAL SUMMARY
                            ============================ -->
                            <div class="col-lg-4 col-md-12 col-12">
                                <div class="h-100 d-flex flex-column justify-content-between">

                                    <div class="mb-4">
                                        <h6 class="text-muted mb-3">
                                            <i class="fa-solid fa-chart-simple me-2"></i>
                                            Summary
                                        </h6>

                                        <div class="row g-2">
                                            <div class="col-6">
                                                <div class="rounded p-2 text-center border border-secondary-lt">
                                                    <div class="text-muted fs-4 mb-1">
                                                        <i class="fa-solid fa-cubes me-1 text-secondary"></i>
                                                        Total Qty
                                                    </div>
                                                    <div class="fw-bold fs-2" id="total_qty">0.000</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="rounded p-2 text-center border border-secondary-lt">
                                                    <div class="text-muted fs-4 small mb-1">
                                                        <i
                                                            class="fa-solid fa-indian-rupee-sign me-1 text-secondary"></i>
                                                        Total Amount
                                                    </div>
                                                    <div class="fw-bold fs-2 text-primary" id="total_amount">
                                                        ₹0.00
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Info Box -->
                                    {{-- <div class="alert alert-light border mb-0">
                                        <div class="small">
                                            <div class="mb-2">
                                                <i class="fa-solid fa-circle-check text-success me-2"></i>
                                                <strong>Quick Tips:</strong>
                                            </div>
                                            <ul class="mb-0 ps-3 small text-muted">
                                                <li>Net Weight = Gross - Tare</li>
                                                <li>Bag weight is auto-calculated</li>
                                                <li>Press F1 for keyboard shortcuts</li>
                                            </ul>
                                        </div>
                                    </div> --}}

                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- ===========================
                   FOOTER BUTTONS
            ============================ -->
                    <!-- Footer with Action Buttons -->
                    <div class="card-footer bg-light border-top p-3">
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
                                <button type="button" class="btn btn-outline-secondary waves-effect non-selectable"
                                    onclick="this.closest('form').reset()">
                                    <i class="fa-solid fa-rotate-left me-1"></i> Clear
                                </button>
                                <button type="submit" class="btn btn-primary px-4 form-save-btn waves-effect"
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
    </div>
</form>
