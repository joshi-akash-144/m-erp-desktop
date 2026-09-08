{{-- {{ dd($orderNumber, $suppliers, $brokers, $formMode) }} --}}
@php
$route = $formMode === 'create' ? route('purchase-orders.store') : "";
@endphp

<form action="{{ $route }}" id="purchase_order_form" autocomplete="off"
    data-form-mode="{{ $formMode }}">

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
                    <i class="fa-solid fa-hashtag me-1 text-secondary"></i> Order Number
                </label>
                @if ($formMode === 'edit')
                <select name="" id="purchase_order_id" class= "form-select select2">
                    <option value="">--Select PO --</option>
                    @foreach ($orderSerials as $orderSerial)
                        <option value="{{ $orderSerial->id }}" @if ($orderSerial->id == $purchaseOrderId) selected @endif>
                            {{ $orderSerial->order_serial }}
                        </option>
                    @endforeach
                </select>

                @else
                <input type="text" name="order_number" id="order_number" class="form-control bg-primary-lt fw-bold text-dark"
                    placeholder="Order Number" value="{{ $orderSerial }}" disabled>
                @endif

            </div>

            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Order Date
                </label>
                <input type="text" name="order_date" id="order_date" class="form-control date-format"
                    placeholder="DD-MM-YYYY">
            </div>

            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-regular fa-clock me-1 text-secondary"></i> Delivery Days 
                </label>
                <input type="number" name="delivery_days" id="delivery_days" class="form-control"
                    value="{{  $formMode === 'create' ? $defaultDeliveryDays : ''}}">
            </div>

            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-calendar-check me-1 text-secondary"></i> Due Date
                </label>
                <input type="text" name="due_date" id="due_date" class="form-control date-format" disabled
                    placeholder="DD-MM-YYYY">
            </div>

            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-user-tie me-1 text-secondary"></i> Broker
                </label>
                <select name="broker_id" id="broker_id" class="form-select">
                    <option value="">Select Broker ...</option>
                    @foreach ($brokers as $broker)
                     @php
                        $brokerCity = $broker->city ? $broker->city : null;
                    @endphp
                        <option value="{{ $broker->id }}"  @if ($formMode === 'create' && session('broker_id') === $broker->id .'_'. company_id()) selected @endif>{{ $broker->name }} @if($brokerCity) ({{ $brokerCity }}) @endif</option>
                    @endforeach
                </select>
            </div>
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-file-contract me-1 text-secondary"></i> Contract Number
                </label>
                <input type="text" id="contract_number" name="contract_number" class="form-control" placeholder="">
            </div>
        </div>

        <!-- 🧩 Row 2 -->
        <div class="grid-row m-erp-po-row-2 row-body">
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-truck-field me-1 text-secondary"></i> Supplier Name
                </label>
                <select name="account_id" id="account_id" class="form-select">
                    <option value="">Select Supplier ...</option>
                    @foreach ($accounts as $account)
                    @php
    $city = $account->city ? $account->city : null;
                    @endphp
                        <option value="{{ $account->id }}">{{ $account->name }} @if($city) ({{ $city }}) @endif</option>
                    @endforeach
                </select>
                <div id="account_id_loader" class="mt-1 d-none">
                    <span class="text-primary small">
                        <i class="fa fa-spinner fa-spin me-1"></i> Loading Account Details...
                    </span>
                </div>
            </div>

            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-city me-1 text-secondary"></i> City
                </label>
                <input type="text" name="order_date" id="account_id_city" class="form-control" disabled>
            </div>

            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-id-card me-1 text-secondary"></i> Supplier Type
                </label>
                <input type="text" name="delivery_days" id="account_id_type" class="form-control" disabled>
            </div>

            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-location-dot me-1 text-secondary"></i> Destination
                </label>
                <select name="destination_id" id="destination_id" class="form-select">
                    <option value="">Select Destination</option>
                    @foreach ($destinations as $destination)
                    <option value="{{ $destination->id }}">{{ $destination->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- 📦 Item Details -->
    <div class="module-form-section">
        <div class="module-page-title">
            <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i>
            Item Details
        </div>
        <div class="table-responsive">
            @include('company.pages.purchase-order._item-table')
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
                                    {{ $formMode === "edit" ? 'Update' : 'Save' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>