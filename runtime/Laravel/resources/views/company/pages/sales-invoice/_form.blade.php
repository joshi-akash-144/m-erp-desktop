@php
    $route = $formMode === 'create' ? route('sales-invoices.store') : '';
@endphp

<form action="{{ $route }}" id="sales_invoice_form" autocomplete="off" data-form-mode="{{ $formMode }}">
    @if ($formMode === 'create')
        <input type="hidden" name="uuid" value="{{ uuid() }}">
    @endif

    <!-- 🧾 General Details -->
    <div class="module-form-section section-sales border-sales">
        <div class="module-page-title text-sales">
            <i class="fa-solid fa-file-pen me-2"></i>
            General Details
        </div>

        <!-- 🧩 Row 1 -->
        <div class="grid-row m-erp-so-row-1 row-body">
            @if ($formMode === 'edit')
                {{-- P.O. No --}}
                <div class="grid-item">
                    <label class="form-label">
                        <i class="fa-regular fa-calendar-days me-1 text-secondary"></i>P.O. Number
                    </label>
                    <input type="text" name="purchase_order_number" id="purchase_order_number" class="form-control" placeholder="P.O. Number">
                    <input type="hidden" name="sales_order_id" id="sales_order_id" class="form-control" value="">
                </div>
            @endif

            {{-- Bill Number --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-hashtag me-1 text-secondary"></i> Bill No.
                </label>
                @if ($formMode === 'edit')
                    <select name="sales_invoice_id" id="sales_invoice_id" class="form-select select2">
                        <option value="">--Select SI --</option>
                        @foreach ($invoiceSerials as $invoiceSerial)
                            <option value="{{ $invoiceSerial->id }}" @if ($invoiceSerial->id == $salesInvoiceId) selected @endif>
                                {{ $invoiceSerial->invoice_serial }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="text" name="order_number" id="order_number"
                        class="form-control bg-primary-lt fw-bold text-dark" placeholder="Order Number"
                        value="{{ $invoiceSerial }}" disabled>
                @endif

            </div>

            {{-- Date  --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Date
                </label>
                <input type="text" name="invoice_date" id="invoice_date" class="form-control date-format"
                    placeholder="DD-MM-YYYY" value="">
            </div>

            {{-- D.C. No --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> D.C. No.
                </label>
                <input type="text" name="delivery_challan_number" id="delivery_challan_number" class="form-control"
                    placeholder="D.C. Number">
            </div>

            @if ($formMode === 'create')
                {{-- P.O. No --}}
                <div class="grid-item">
                    <label class="form-label">
                        <i class="fa-regular fa-calendar-days me-1 text-secondary"></i>P.O. Number
                    </label>
                    <input type="text" name="purchase_order_number" id="purchase_order_number" class="form-control" placeholder="P.O. Number">
                    <input type="hidden" name="sales_order_id" id="sales_order_id" class="form-control" value="">
                </div>
            @endif

            {{-- GRN No. --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> GRN
                </label>
                <input type="text" name="grn_number" id="grn_number" class="form-control" placeholder="GRN">
            </div>

        </div>

        <!-- 🧩 Row 2 -->
        <div class="grid-row m-erp-so-row-2 row-body">

            {{-- Customer --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-truck-field me-1 text-secondary"></i> Customer Name
                </label>
                <select name="account_id" id="account_id" class="form-select">
                    <option value="">--Select Customer --</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer['id'] }}">{{ $customer['name'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- City --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-city me-1 text-secondary"></i> City
                </label>
                <input type="text" id="account_id_city" class="form-control" disabled placeholder="City">
            </div>

            {{-- SO Type --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-id-card me-1 text-secondary"></i> S.O. Type
                </label>
                <input type="text" name="account_id_type" id="account_id_type" class="form-control" disabled>
            </div>

            {{-- Last Inv Date --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Last Inv Date
                </label>
                <input type="text" name="last_invoice_date" id="last_invoice_date" class="form-control date-format"
                    placeholder="DD-MM-YYYY" value="{{ $billDate }}">
            </div>

            {{-- Broker Name --}}
            {{-- <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-user-tie me-1 text-secondary"></i> Broker
                </label>
                <select name="broker_id" id="broker_id" class="form-select">
                </select>
            </div> --}}

            {{-- Tax Type --}}
            {{-- <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-city me-1 text-secondary"></i> Tax Type
                </label>
                <input type="text" id="tax_type" name="tax_type" class="form-control" placeholder="Tax Type"
                    disabled>
            </div> --}}


        </div>

        <!-- 🧩 Row 3 -->
        <div class="grid-row m-erp-so-row-3 row-body">
            {{-- KMS --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-city me-1 text-secondary"></i> kms
                </label>
                <input type="text" id="kms" name="kms" class="form-control" placeholder="e.g. 100" readonly>
            </div>

            {{-- Vehicle --}}
            <div class="grid-item">
                <label class="form-label required">
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

            {{-- Delivery Date --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Delivery Date
                </label>
                <input type="text" name="delivery_date" id="delivery_date"
                    class="form-control custom-date-format" placeholder="DD-MM-YYYY">
            </div>

            {{-- Invoice Type --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-user-tie me-1 text-secondary"></i> Invoice Type
                </label>

                <select name="sale_type_id" id="sale_type_id" class="form-select">
                    <option value="">Select</option>
                    @foreach ($saleTypes as $saleType)
                        <option value="{{ $saleType->id }}">{{ $saleType->name }}</option>
                    @endforeach
                </select>

                <div id="sale_type_loader" class="mt-1 d-none">
                    <span class="text-primary small">
                        <i class="fa fa-spinner fa-spin me-1"></i> Loading Type Details...
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 📦 Item Details -->
    <div class="module-form-section section-sales border-sales">
        <div class="d-flex justify-content-between">
            <div class="module-page-title text-sales">
                <i class="fa-solid fa-boxes-stacked me-2"></i>
                Item Details
            </div>
        </div>

        <div class="table-scroll-x" style="overflow-x:scroll">
            @include('company.pages.sales-invoice._item-table')
        </div>

        <div class="row g-3">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="row g-4 align-items-start">


                            {{-- STOCK INFO + WEIGHT DETAILS --}}
                            <div class="col-lg-3 col-md-6 col-12"></div>
                            <div class="col-lg-1"></div>

                            {{-- Particular Table --}}
                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="row">
                                    <div class="col-5">
                                        <div
                                            class="rounded p-2 border border-secondary-lt d-flex justify-content-between align-items-center">

                                            <!-- Label -->
                                            <div class="text-muted small d-flex align-items-center">
                                                <i class="fa-solid fa-cube me-1 text-secondary"></i>
                                                Total Qty
                                            </div>

                                            <!-- Value -->
                                            <div class="fw-bold">
                                                <input type="text" name="total_qty" id="total_qty"
                                                    class="form-control  border-1 border-secondary-subtle text-end fw-bold"
                                                    style="font-size:1rem !important" disabled value="0.000">
                                            </div>

                                        </div>
                                    </div>
                                    <div class="col-7">
                                        <div
                                            class="rounded p-2 border border-secondary-lt d-flex justify-content-between align-items-center">

                                            <!-- Label -->
                                            <div class="text-muted small d-flex align-items-center">
                                                <i class="fa-solid fa-indian-rupee-sign me-1 text-secondary"></i>
                                                Total Amount
                                            </div>

                                            <!-- Value -->
                                            <div class="fw-bold" id="total_amount">
                                                <input type="text" name="base_total_amount" id="base_total_amount"
                                                    data-amount=""
                                                    class="form-control  border-1 border-secondary-subtle text-end fw-bold text-primary"
                                                    style="font-size:1rem !important" value="₹0.00" disabled>
                                            </div>

                                        </div>
                                    </div>

                                </div>
                                <table class="table table-sm align-middle" id="particular_table">
                                    <thead>
                                        <tr>
                                            <th width="30px" class="text-center">
                                                <i class="fa-solid fa-hashtag text-secondary"></i>
                                            </th>
                                            <th width="230px">Particular</th>
                                            <th width="70px" class="text-center">%</th>
                                            <th width="200px" class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody id="particular_table_body"></tbody>
                                    <tfoot>
                                        <tr>
                                            <td>
                                                <span href="#" id="add_particular_row" class="btn btn-action">
                                                    <i class="fa-solid fa-plus" data-bs-toggle="tooltip"
                                                        data-bs-placement="right" data-bs-html="true"
                                                        data-bs-original-title="Add Particular"></i>
                                                </span>
                                            </td>
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
                                                            </svg></span> --}}

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
                                                        {{-- <span class="small text-muted fs-5">(Payable to Party)</span> --}}
                                                        <span class="ms-2">Net Total</span>
                                                    </div>
                                                </div>
                                            </td>

                                            <td>
                                                <input type="text" name="net_total" id="net_total" tabindex="-1"
                                                    class="form-control  border-1 border-secondary-subtle text-end fw-bold text-danger"
                                                    style="font-size:1.1rem !important" readonly value="0.00">
                                            </td>
                                        </tr>
                                        <tr class="d-none" id="gross_total_row">
                                            <td colspan="3" class="text-end">Gross Total</td>
                                            <td>
                                                <input type="text" name="gross_total" id="gross_total"
                                                    tabindex="-1"
                                                    class="form-control  border-1 border-secondary-subtle text-end fw-bold text-primary"
                                                    style="font-size:1rem !important" readonly value="0.00">
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
                                <textarea id="remarks" name="remarks" rows="5" class="form-control non-selectable"
                                    placeholder="Enter additional notes, remarks, or special instructions..."></textarea>
                                <small class="text-muted mt-2 d-block">
                                    <i class="fa-solid fa-circle-info me-1"></i>
                                    Optional: Add any additional information or notes
                                </small>
                                <div class="mt-2 col-lg-5">
                                    <label for="ewaybill_number" class="form-label fw-semibold">
                                        <i class="fa-solid fa-file-invoice me-2 text-primary"></i>
                                        Ewaybill-Number
                                    </label>
                                    <input type="text" name="ewaybill_number" id="ewaybill_number"
                                        class="form-control" placeholder="Ewaybill Number">
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- FOOTER BUTTONS --}}
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
                                <button type="button" class="btn btn-outline-secondary non-selectable" tabindex="-1"
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
