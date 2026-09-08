{{-- {{ dd($orderNumber, $suppliers, $brokers, $formMode) }} --}}
@php
$route = $formMode === 'create' ? route('purchase-invoices.store') : '';
@endphp

<form action="{{ $route }}" id="purchase_invoice_form" autocomplete="off" data-form-mode="{{ $formMode }}">
    @if ($formMode === 'edit')
        @method('PUT')
    @endif

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
            {{-- col-lg-1 → col-md-4 col-sm-6 | col-lg-2 → col-md-4 col-sm-6 | col-lg-3 → col-md-6 col-sm-12 --}}

            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-hashtag me-1 text-secondary"></i> Vch. No.
                </label>
                @if ($formMode === 'edit')
                    <select name="purchase_invoice_id" id="purchase_invoice_id" class="form-select custom-select2">
                        <option value="">--Select Invoice--</option>
                        @foreach ($invoiceSerials as $serial)
                            <option value="{{ $serial->id }}" @if ($serial->id == $purchaseInvoiceId) selected @endif>
                                {{ $serial->invoice_serial }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="text" name="invoice_number" id="invoice_number" class="form-control bg-primary-lt fw-bold text-dark"
                        placeholder="Invoice Number" value="{{ $invoiceSerial }}" disabled>
                @endif
            </div>

            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Invoice Date
                </label>
                <input type="text" name="invoice_date" id="invoice_date" class="form-control date-format"
                    placeholder="DD-MM-YYYY">
            </div>

            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-receipt me-1 text-secondary"></i> GRN No.
                </label>
                <select name="grn_id" id="grn_id" class="form-select">
                    <option value="" selected>Select GRN</option>
                    @foreach ($pendingGrns as $grn)
                        <option value="{{ $grn->id }}">{{ $grn->grn_serial }}</option>
                    @endforeach
                </select>
                <div id="grn_details_loader" class="mt-1 d-none">
                    <span class="text-primary small">
                        <i class="fa fa-spinner fa-spin me-1"></i> Loading GRN Details…
                    </span>
                </div>
            </div>

            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-file-contract me-1 text-secondary"></i> File No.
                </label>
                <input type="text" id="file_number" name="file_number" class="form-control" placeholder=""
                    value="">
            </div>

            <div class="grid-item">
                <label class="form-label text-nowrap">
                    <i class="fa-solid fa-user-tie me-1 text-secondary"></i> Sales Inv No
                </label>
                <input type="text" id="sales_invoice_serial" name="sales_invoice_serial" class="form-control"
                    placeholder="">
            </div>

            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-user-tie me-1 text-secondary"></i> Supplier Name
                </label>
                <select name="account_id" id="account_id" class="form-select">
                    <option value="" selected>-- Select Supplier --</option>
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
                <div class="input-icon">
                    <input type="text" name="order_date" id="account_id_city"
                        class="form-control bg-light border-1 border-secondary-subtle" disabled>
                    <span class="input-icon-addon d-none" id="account_city_loader">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                    </span>
                </div>
            </div>

            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-percent me-1 text-secondary"></i> Tax Type
                </label>
                <div class="input-icon">
                    <input type="text" name="order_date" id="account_id_type"
                        class="form-control bg-light border-1 border-secondary-subtle" disabled>
                    <span class="input-icon-addon d-none" id="account_type_loader">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                    </span>
                </div>
            </div>

        </div>

        <div class="grid-row m-erp-po-row-2 row-body">
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-id-card me-1 text-secondary"></i> Party Bill No.
                </label>
                <div class="input-icon">
                    <input type="text" name="reference_number" id="reference_number" class="form-control">
                    <span class="input-icon-addon d-none" id="reference_number_loader">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                    </span>
                </div>
            </div>

            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-user-tie me-1 text-secondary"></i> Broker
                </label>
                <select name="broker_id" id="broker_id" class="form-select">
                    <option value="" selected>-- Select Broker --</option>
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
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Party Bill Date
                </label>
                <input type="text" name="party_bill_date" id="party_bill_date" class="form-control date-format"
                    placeholder="DD-MM-YYYY">
            </div>

            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-user-tie me-1 text-secondary"></i> Invoice Type
                </label>
                <select name="purchase_type_id" id="purchase_type_id" class="form-select">
                    <option value="" selected>-- Select Type --</option>
                    @foreach ($purchaseTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
                <div id="purchase_type_loader" class="mt-1 d-none">
                    <span class="text-primary small">
                        <i class="fa fa-spinner fa-spin me-1"></i> Loading Type Details...
                    </span>
                </div>
            </div>

            @if ($formMode === 'edit')
            <div class="grid-item d-flex align-items-end">
                <button type="button" id="btn_refetch_grn"
                    class="btn btn-outline-info btn-sm px-2 d-none non-selectable"
                    onclick="fetchGrn()"
                    data-bs-toggle="tooltip" data-bs-placement="top"
                    title="Refetch GRN — reload latest GRN data into item rows">
                    <i class="fa-solid fa-rotate me-2"></i>Refetch GRN
                </button>
            </div>
            @endif
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

        <div class="table-scroll-x" style="overflow-x:scroll">
            @include('company.pages.purchase-invoice._item-table')
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
                                            <div class="text-muted small d-flex align-items-center">
                                                <i class="fa-solid fa-cube me-1 text-secondary"></i>
                                                Total Qty
                                            </div>

                                            <!-- Value -->
                                            <div class="fw-bold">
                                                <input type="text" name="total_qty" id="total_qty"
                                                    class="form-control  border-1 border-secondary-subtle text-end fw-bold"
                                                    style="font-size:1rem !important" readonly  value="">
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
                                                {{-- <span href="#" id="add_particular_row" class="btn btn-action">
                                                    <i class="fa-solid fa-plus" data-bs-toggle="tooltip"
                                                        data-bs-placement="right" data-bs-html="true"
                                                        data-bs-original-title="Add Particular"></i>
                                                </span> --}}
                                            </td>
                                            <td colspan="2" class="text-end fw-semibold">
                                                <div class="d-flex justify-content-between">
                                                    <div class="d-flex justify-content-between">
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
                                                        <span class="small text-muted fs-5">(Payable to Party)</span>
                                                        <span class="ms-2">Net Total</span>
                                                    </div>
                                                </div>
                                            </td>

                                            <td>
                                                <input type="text" name="net_total" id="net_total" tabindex="-1" data-value="0.00"
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

                            <!-- ===========================
                             TOTAL SUMMARY
                            ============================ -->
                            <div class="col-lg-4 col-md-12 col-12">
                                <label for="remarks" class="form-label fw-semibold mb-3">
                                    <i class="fa-regular fa-note-sticky me-2 text-primary"></i>
                                    Narration
                                </label>
                                 {{-- remove class non-selectable in this field --}}
                                <textarea id="remarks" name="remarks" rows="5" class="form-control "
                                    placeholder="Enter additional notes, remarks, or special instructions..."></textarea>
                                <small class="text-muted mt-2 d-block">
                                    <i class="fa-solid fa-circle-info me-1"></i>
                                    Optional: Add any additional information or notes
                                </small>
                                <div class="mt-2 col-lg-5">
                                    <label for="turn_over" class="form-label fw-semibold">
                                        <i class="fa-solid fa-chart-line me-2 text-primary"></i>
                                        Turn Over
                                    </label>
                                    <div class="input-icon">
                                        <input type="text" name="turn_over" id="turn_over" class="form-control fw-bold"
                                            disabled>
                                        <span class="input-icon-addon d-none" id="turn_over_loader">
                                            <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                                        </span>
                                    </div>
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
                            </small>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary waves-effect non-selectable" tabindex="-1"
                                    onclick="this.closest('form').reset()">
                                    <i class="fa-solid fa-rotate-left me-1"></i> Clear
                                </button>
                                <button type="submit" class="btn btn-primary px-4 form-save-btn waves-effect"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Save (Alt + S)" id="save_btn">
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
