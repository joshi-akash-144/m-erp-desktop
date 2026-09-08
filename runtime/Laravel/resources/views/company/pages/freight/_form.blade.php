@php
    $route = $formMode === 'create' ? route('freight.store') : route('freight.update', $freight->id);
@endphp

<form action="{{ $route }}" id="freight_form" autocomplete="off" data-form-mode="{{ $formMode }}">
    @if ($formMode === 'create')
        <input type="hidden" name="uuid" value="{{ uuid() }}">
    @endif

    <!-- 🧾 General Details -->
    <div class="module-form-section section-sales border-sales">
        <div class="module-page-title text-sales">
            <i class="fa-solid fa-file-pen me-2"></i>
            General Details
        </div>

        <div class="grid-row m-erp-so-row-1 row-body">
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-hashtag me-1 text-secondary"></i> Voucher Number
                </label>
                <input type="text" name="voucher_number" id="voucher_number" class="form-control bg-primary-lt fw-bold text-dark" value="{{ $serialInfo->serial }}" readonly>

            </div>
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-user-tie me-1 text-secondary"></i> Bill To
                </label>
                    <select name="account_id" id="account_id" class="form-select select2" {{ $formMode === 'edit' ? 'disabled' : '' }}>
                        <option value="">--Select Bill To --</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </select>
                    @if ($formMode === 'edit')
                        <input type="hidden" name="account_id" value="{{ $freight->account_id ?? '' }}">
                    @endif
            </div>


            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-hashtag me-1 text-secondary"></i> Bill Number
                </label>
                <input type="text" name="bill_number" id="bill_number" class="form-control bg-primary-lt fw-bold text-dark" readonly>

            </div>

            {{-- Date --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i>Invoice Date
                </label>
                <input type="text" name="invoice_date" id="invoice_date" class="form-control date-format" placeholder="DD-MM-YYYY">
            </div>
            {{-- GRN Number --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-hashtag me-1 text-secondary"></i> GRN No
                </label>
                <input type="text" name="grn_serial" id="grn_serial" class="form-control">
            </div>

            {{-- LR Number --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-hashtag me-1 text-secondary"></i> LR Number
                </label>
                <input type="text" name="lr_number" id="lr_number" class="form-control">
            </div>
</div>
        <div class="grid-row m-erp-so-row-2 row-body">
            {{-- Vehicle No --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-truck-field me-1 text-secondary"></i> Vehicle No
                </label>
                <select name="vehicle_id" id="vehicle_id" class="form-select">
                    <option value="">Select Vehicle No ...</option>
                    @foreach ($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}">{{ $vehicle->vehicle_number }}</option>
                    @endforeach 
                </select>
            </div>

            {{-- Product --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-box me-1 text-secondary"></i> Item Name
                </label>
                <select name="item_id" id="item_id" class="form-select">
                    <option value="">Select Item Name ...</option>
                </select>
            </div>

            {{-- Consignor Name(Sender) --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-id-card me-1 text-secondary"></i> Consignor Name(Sender)
                </label>
                <select name="consignor_id" id="consignor_id" class="form-select">
                    <option value="">Select Consignor</option>  
                </select>
            </div>

            {{-- Consignee Name(Receiver) --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-id-card me-1 text-secondary"></i> Consignee Name(Receiver)
                </label>
                <select name="consignee_id" id="consignee_id" class="form-select">
                    <option value="">Select Consignee</option>
                </select>
            </div>
            </div>

        <!-- 🧩 Row 2 -->
        <div class="grid-row m-erp-so-row-3 row-body">

            {{-- From Destination --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-location-dot me-1 text-secondary"></i> From Destination
                </label>
                <select name="from_destination_id" id="from_destination_id" class="form-select">
                    <option value="">Select From Destination</option>
                </select>
            </div>

            {{-- To Destination --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-location-dot me-1 text-secondary"></i> To Destination
                </label>
                <select name="to_destination_id" id="to_destination_id" class="form-select">
                    <option value="">Select To Destination</option>
                </select>
            </div>
        </div>
    </div>

    <!-- 📦 Item Details -->
    <div class="module-form-section section-sales border-sales">
        <div class="row g-3 mt-1">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-2">
                        <div class="row g-4 align-items-start">

                    <!-- ===========================
                         STOCK INFO + WEIGHT DETAILS
                    ============================ -->
                            <div class="col-lg-5 col-md-6 col-12">
                                <!-- Weight Details Section -->
                                <div class="module-form-section border-sales">
                                    <div class="module-page-title mb-3  text-sales">
                                        <i class="fa-solid fa-boxes-stacked me-2"></i>
                                        <span class="fw-semibold">Weight Details</span>
                                    </div>

                                    <table class="table table-borderless align-middle weight-table">
                                        <tbody>
                                            <tr>
                                                <td class="fw-semibold">
                                                    <i class="fa-solid fa-bag-shopping me-2 text-sales"></i>
                                                    Bag Type
                                                </td>
                                                <td>
                                                    @php
                                                        $bagTypes = config('constants.bag_types');
                                                    @endphp
                                                    <div class="d-flex gap-1">
                                                        <div class="flex-grow-1">
                                                            <select id="bag_type" name="bag_type"
                                                                class="form-select select2" style="width: 100%;">
                                                                @foreach ($bagTypes as $bagTypeKey => $bagType)
                                                                    <option value="{{ $bagTypeKey }}">{{ $bagType }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div style="width: 200px;">
                                                            <input type="number" id="bag_count" name="bag_count" class="form-control text-end" placeholder="Qty" value="0">
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td class="fw-semibold">
                                                    <i class="fa-solid fa-weight-scale me-2 text-sales"></i>
                                                    Net Weight <small class="text-muted small">(Gross - Tare)</small>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <input type="text" id="net_weight" name="net_weight"
                                                            class="form-control text-end border-1 border-secondary-subtle" value="0.00">
                                                        <span class="d-flex align-items-center gap-1 text-dark fw-bold">
                                                            <i class="fas fa-tachometer-alt text-sales"></i>KMs</span>
                                                        <input type="text" id="kms" name="kms"
                                                            class="form-control text-end border-1 border-secondary-subtle" value="0.00">
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">
                                                    <i class="fa-solid fa-scale-balanced me-2 text-sales"></i></i>
                                                    Freight Rate
                                                </td>
                                                <td>
                                                    <input type="text" id="freight_rate" name="freight_rate"
                                                        class="form-control text-end border-1 border-secondary-subtle" value="0.00">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">
                                                    <i class="fa-solid fa-weight-hanging me-2 text-sales"></i>
                                                    Freight
                                                </td>
                                                <td>
                                                    <input type="text" id="total_amount" name="total_amount"
                                                        class="form-control text-end border-1 border-secondary-subtle" value="0.00">
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
                                <textarea id="remarks" name="remarks" rows="5" class="form-control non-selectable"
                                    placeholder="Enter additional notes, remarks, or special instructions..."></textarea>
                                <small class="text-muted mt-2 d-block">
                                    <i class="fa-solid fa-circle-info me-1"></i>
                                    Optional: Add any additional information or notes
                                </small>
                            </div>

                        </div>
                    </div>

                    <!-- ===========================
                   FOOTER BUTTONS
            ============================ -->
                    <!-- Footer with Action Buttons -->
                    <div class="card-footer bg-light border-top p-2">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <small class="text-muted d-flex align-items-center gap-2">
                                <i class="fa-solid fa-circle-info text-primary me-1"></i>
                                Review all details before saving
                                <span class="text-secondary">|</span>
                                <span><span class="text-danger">*</span> Fields are required</span>
                            </small>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary waves-effect non-selectable"
                                    onclick="this.closest('form').reset()">
                                    <i class="fa-solid fa-rotate-left me-1"></i> Clear
                                </button>
                                <button type="submit" class="btn btn-sales px-4 form-save-btn waves-effect"
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
