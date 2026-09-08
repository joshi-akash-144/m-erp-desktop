<form id="freight_invoice_form" autocomplete="off" data-form-mode="create"
    action="{{-- route('journal-vouchers.store') --}}">
    <input type="hidden" name="uuid" id="uuid" value="{{ uuid() }}">
    <input type="hidden" name="form_mode" id="form_mode" value="{{-- $formMode --}}">
    <div class="grid-row m-erp-po-row-1">
        <div class="grid-item">
            <label class="form-label required fw-bold">
                <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Bill No.
            </label>
            {{--@if ($formMode === 'edit') 
                <select name="" id="voucher_id" class="form-select select2">
                    <option value="">Select Journal</option>
                    {{-- @foreach ($voucherSerials as $serial)
                        <option value="{{ $serial->id }}" @if ($serial->id == $voucherId) selected @endif>
            {{ $serial->voucher_serial }}
            </option>
            @endforeach
            </select>--}}

            <input type="text" name="bill_no" value="{{ $ref }}" id="bill_no" class="form-control text-dark fw-bold" disabled>

        </div>
        <div class="grid-item">
            <label class="form-label required fw-bold">
                <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Bill Date
            </label>
            <input type="text" name="invoice_date" id="invoice_date" class="form-control date-format"
                placeholder="DD-MM-YYYY" value="{{ current_date_dmy() }}">
        </div>
        {{-- <div class="grid-item">
            <label class="form-label fw-bold">
                <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Cash / Debit Bill
            </label>
            <select name="cash_debit_bill" id="cash_debit_bill" class="custom-select2">
                <option value="debit_bill">Debit</option>
                <option value="cash_bill">Cash</option>
                {{-- <option value="registered_expense_b2b">Registered Expense(B2B)</option>
                <option value="gst_tax_adjustment">GST Tax Adjustment</option> 
            </select>
        </div> --}}
        <div class="grid-item">
            <label class="form-label fw-bold">
                <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> To Bill
            </label>
            <select name="account_id" id="account_id" class="form-select">
                <option value="">Select Name</option>
                @foreach ($customers as $customer)
                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid-item">
            <label class="form-label fw-bold">
                <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> City
            </label>
            <div class="input-icon">
                <input type="text" name="order_date" id="account_id_city"
                    class="form-control bg-light border-1 border-secondary-subtle" disabled>
                <span class="input-icon-addon d-none" id="account_city_loader">
                    <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                </span>
            </div>
            {{-- <input type="text" name="city" id="city" class="form-control bg-light text-dark" readonly> --}}
        </div>
        <div class="grid-item">
            <label class="form-label required fw-bold">
                <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> From Date
            </label>
            <input type="text" name="from_date" id="from_date" class="form-control date-format"
                placeholder="DD-MM-YYYY">
        </div>
        <div class="grid-item">
            <label class="form-label required fw-bold">
                <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> To Date
            </label>
            <input type="text" name="to_date" id="to_date" class="form-control date-format" placeholder="DD-MM-YYYY">
        </div>
        <!-- Buttons -->
        <div class="grid-item d-flex flex-row flex-nowrap align-items-end gap-2 text-nowrap">
            <button id="get_import_file_data" type="button" class="btn btn-primary waves-effect non-selectable">
                @include('icons.filter-clear', ['size' => 18])
                Get Import Item
            </button>
            <button type="button" id="clear_all_rows_btn" class="btn btn-outline-danger waves-effect non-selectable">
                <i class="fa-solid fa-trash-can me-1"></i> Clear All Rows
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div style="max-height: 400px; overflow-y: auto; position: relative;">
                <table class="table border border-1 border-dark-subtle table-bordered" id="freight_invoice_table"
                    style="table-layout: fixed; width:100%;">
                    <colgroup>
                        <col style="width:70px">
                        <col style="width:300px">
                        <col style="width:200px">
                        <col style="width:160px">
                        <col style="width:160px">
                        <col style="width:150px">
                        <col style="width:46px">
                    </colgroup>

                    <thead style="position: sticky; top: 0; z-index: 999;">
                        <tr>
                            <th class="text-center">Sr.No.</th>
                            <th class="text-center">Item</th>
                            <th class="text-start">Zone</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Rate</th>
                            <th class="text-end">Amount</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="freight_invoice_table_body">
                        @for ($i = 0; $i < 150; $i++) <tr data-is-ref-modal-open="false" data-references="[]"
                            data-index="{{ $i }}">
                            <td class="text-center">
                                <input type="text" class="form-control text-center" value="{{ $i + 1 }}" disabled="">
                            </td>
                            <td>
                                <select class="form-select item-id"></select>
                            </td>
                            <td class="text-start">
                                <select class="form-select zone-id"></select>
                            </td>
                            <td>
                                <input type="text" class="form-control text-end quantity only-number show-ref-details"
                                    style="font-size:1.1rem !important">
                            </td>
                            <td>
                                <input type="text" class="form-control text-end rate only-number show-ref-details"
                                    style="font-size:1.1rem !important">
                            </td>
                            <td class="position-relative">
                                <input type="text" class="form-control text-end amount only-number show-ref-details"
                                    style="font-size:1.1rem !important">
                                <div class="d-none position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75
                                         d-flex align-items-center justify-content-center ledger-balance-loader"
                                    style="z-index: 3;">Fetching...
                                    <div class="spinner-border spinner-border-sm text-primary ms-3"></div>
                                </div>
                            </td>
                            <td class="text-center p-1">
                                <button type="button" class="btn btn-sm btn-ghost-danger clear-row-btn p-1 non-selectable"
                                    title="Clear row" tabindex="-1">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </td>
                            </tr>
                            @endfor
                    </tbody>
                    <tfoot style="position: sticky; bottom: 0; z-index: 999;">
                        <tr>
                            <td colspan="3" class="text-end pe-2 fw-bold">Total</td>
                            <td><input type="text" style="font-size:1.1rem !important" id="total_debit"
                                    class="form-control text-end fw-bold" data-value="0.00" disabled value="0.00"></td>
                            <td></td>
                            <td><input type="text" style="font-size:1.2rem !important" id="total_credit"
                                    class="form-control text-end fw-bold" data-value="0.00" disabled value="0.00"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>
    <div>
        <div class="row">
            <div class="col-lg-9">
                <div class="form-group mt-2">
                    <label for="name" class="fw-bold">Narration</label>
                    <input type="text" class="fw-bold  form-control  border-1" id="voucher_narration" height="30px"
                        autocomplete="off">
                </div>
            </div>

        </div>
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
                        onclick="location.reload()">
                        <i class="fa-solid fa-rotate-left me-1"></i> Clear
                    </button>
                    {{-- save button based on condition if form mode is edit then save and if form mode is create then save and print --}}
                    <button type="submit" class="btn btn-sales px-4 form-save-btn waves-effect" data-bs-toggle="tooltip"
                        data-bs-placement="bottom" id="save_btn"
                        data-bs-original-title="{{ ($formMode === 'edit') ? 'Update (Alt + S)' : 'Save (Alt + S)' }}">
                        <i class="fa-solid fa-floppy-disk me-1"></i>
                        {{ ($formMode === 'edit') ? 'Update' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Hidden trigger for dairyFileModal (avoids bootstrap global) -->
<button id="dairyFileModalTrigger" data-bs-toggle="modal" data-bs-target="#dairyFileModal" class="d-none"
    type="button"></button>

<!-- Dairy File Import Modal -->
<div class="modal fade" id="dairyFileModal" tabindex="-1" aria-labelledby="dairyFileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="dairyFileModalLabel">
                    <i class="fa-solid fa-file-import me-2 text-primary"></i>
                    Select Dairy File Items
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="dairyFileModalLoader" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted">Fetching data...</div>
                </div>
                <div id="dairyFileModalNoData" class="text-center py-5 d-none">
                    <i class="fa-solid fa-inbox fa-3x text-muted mb-3 d-block"></i>
                    <div class="text-muted">No unused dairy file entries found.</div>
                </div>
                <div id="dairyFileModalTableWrapper" class="d-none">
                    <div style="max-height: 460px; overflow-y: auto;">
                        <table class="table table-bordered table-sm table-hover mb-0">
                            <thead class="table-light" style="position: sticky; top: 0; z-index: 5;">
                                <tr>
                                    <th style="width:50px;" class="text-center">Sr.</th>
                                    <th style="width:130px;">Import Date</th>
                                    <th>Item Name</th>
                                    <th style="width:60px;" class="text-center">
                                        <input type="checkbox" class="form-check-input" id="selectAllDairyItems"
                                            title="Select All">
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="dairyFileModalBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-none" id="dairyFileModalFooter">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="importDairyItemsBtn">
                    <i class="fa-solid fa-file-import me-1"></i> Import Selected
                </button>
            </div>
        </div>
    </div>
</div>