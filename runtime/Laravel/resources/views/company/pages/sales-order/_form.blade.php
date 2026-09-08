@php
    $route = $formMode === 'create' ? route('sales-orders.store') : '';
@endphp

<form action="{{ $route }}" id="sales_order_form" autocomplete="off" data-form-mode="{{ $formMode }}">
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
                    <i class="fa-solid fa-hashtag me-1 text-secondary"></i> Order Number
                </label>
                @if ($formMode === 'edit')
                    <select name="" id="sales_order_id" class= "form-select select2">
                        <option value="">--Select SO --</option>
                        @foreach ($orderSerials as $orderSerial)
                            <option value="{{ $orderSerial->id }}" @if ($orderSerial->id == $salesOrderId) selected @endif>
                                {{ $orderSerial->order_serial }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="text" name="order_number" id="order_number"
                        class="form-control bg-primary-lt fw-bold text-dark" placeholder="Order Number"
                        value="{{ $orderSerial }}" disabled>
                @endif

            </div>

            {{-- <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-user-tie me-1 text-secondary"></i> Broker
                </label>
                <select name="broker_id" id="broker_id" class="form-select">
                </select>
            </div> --}}

            {{-- PO Number --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> P.O. Number
                </label>
                <input type="text" name="purchase_order_number" id="purchase_order_number" class="form-control"
                    placeholder="P.O. Number" autofocus>


            </div>

            {{-- po Date --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> P.O. Date
                </label>
                <input type="text" name="purchase_order_date" id="purchase_order_date"
                    class="form-control date-format" placeholder="DD-MM-YYYY">
            </div>

            {{-- Customer --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-truck-field me-1 text-secondary"></i> Customer Name
                </label>
                <select name="account_id" id="account_id" class="form-select">
                    <option value="">Select account ...</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- City --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-city me-1 text-secondary"></i> City
                </label>
                <input type="text" id="account_id_city" class="form-control" disabled>
            </div>

            {{-- SO Type --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-id-card me-1 text-secondary"></i> S.O. Type
                </label>
                <input type="text" name="sales_order_type" id="account_id_type" class="form-control" disabled>
            </div>

        </div>

        <!-- 🧩 Row 2 -->
        <div class="grid-row m-erp-so-row-2 row-body">

            {{-- Delivery Date --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Delivery Date
                </label>
                <input type="text" name="delivery_date" id="delivery_date" class="form-control custom-date-format"
                    placeholder="DD-MM-YYYY">
            </div>

            {{-- Delivery Days --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-regular fa-clock me-1 text-secondary"></i> Delivery Days
                </label>
                <input type="number" name="delivery_days" id="delivery_days" class="form-control" readonly
                    value="{{ $formMode === 'create' ? $defaultDeliveryDays : '' }}">
            </div>
        </div>
    </div>

    <!-- 📦 Item Details -->
    <div class="module-form-section section-sales border-sales">
        <div class="module-page-title text-sales">
            <i class="fa-solid fa-boxes-stacked me-2"></i>
            Item Details
        </div>
        <div class="table-responsive">
            @include('company.pages.sales-order._item-table')
        </div>

        <div class="row g-3 mt-1">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-1">
                        <div class="row g-3 align-items-center">

                            <!-- Stock Info -->
                            <div class="col-lg-2 col-md-6 col-sm-6">
                                <div class="text-center">
                                    <div class="form-label text-muted small mb-1">
                                        <i class="fa-solid fa-warehouse me-1"></i> Current Stock
                                    </div>
                                    <div class="fw-bold text-success fs-4">0.00</div>
                                </div>
                            </div>

                            <!-- Narration -->
                            <div class="col-lg-6 col-md-12">
                                <label for="remarks" class="form-label small mb-1">
                                    <i class="fa-regular fa-note-sticky me-1 text-primary"></i>
                                    Narration
                                </label>
                                <input type="text" id="remarks" name="remarks" class="form-control non-selectable"
                                    placeholder="Enter additional notes...">
                            </div>

                            <!-- Totals Summary -->
                            <div class="col-lg-4 col-md-12">
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
                                                <i class="fa-solid fa-indian-rupee-sign me-1 text-secondary"></i>
                                                Total Amount
                                            </div>
                                            <div class="fw-bold fs-2 text-primary" id="total_amount">
                                                ₹0.00
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

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

    </div>

</form>
