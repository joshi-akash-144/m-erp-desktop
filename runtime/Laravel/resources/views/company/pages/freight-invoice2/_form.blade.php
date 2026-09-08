<form id="freight_invoice_2_form" autocomplete="off" data-form-mode="create"
    action="{{-- route('journal-vouchers.store') --}}">
    <input type="hidden" name="uuid" id="uuid" value="{{ uuid() }}">
    <input type="hidden" name="form_mode" id="form_mode" value="{{-- $formMode --}}">
    <div class="grid-row m-erp-po-row-1">
        <div class="grid-item">
            <label class="form-label required fw-bold">
                <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Bill No.
            </label>

            <input type="text" name="bill_no" value="{{ $ref }}" id="bill_no" class="form-control text-dark fw-bold" disabled>

        </div>
        <div class="grid-item">
            <label class="form-label required fw-bold">
                <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Bill Date
            </label>
            <input type="text" name="invoice_date" id="invoice_date" class="form-control date-format"
                placeholder="DD-MM-YYYY" value="{{ isset($freight) && $freight->invoice_date ? date('d-m-Y', strtotime($freight->invoice_date)) : current_date_dmy() }}">
        </div>
        <div class="grid-item">
            <label class="form-label required fw-bold">
                <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> To Bill
            </label>
            <select name="account_id" id="account_id" class="form-select">
                <option value="">Select Name</option>
                @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" {{ (isset($freight) && $freight->account_id == $customer->id) ? 'selected' : '' }}>{{ $customer->name }}</option>
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
        </div>
        <!-- Buttons -->
        <div class="grid-item d-flex flex-row flex-nowrap align-items-end gap-2 text-nowrap">
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
                        <col style="width:60px">
                        <col style="width:130px">
                        <col style="width:100px">
                        <col style="width:250px">
                        <col style="width:100px">
                        <col style="width:100px">
                        <col style="width:150px">
                        <col style="width:150px">
                        <col style="width:100px">
                        <col style="width:120px">
                        <col style="width:150px">
                        <col style="width:200px">
                        <col style="width:46px">
                    </colgroup>

                    <thead style="position: sticky; top: 0; z-index: 999;">
                        <tr>
                            <th class="text-center">Sr.No.</th>
                            <th class="text-center required">Date</th>
                            <th class="text-center">Code</th>
                            <th class="text-center required">Society Name</th>
                            <th class="text-start">Route</th>
                            <th class="text-end">Bag</th>
                            <th class="text-center required">Vehicle No.</th>
                            <th class="text-end">Vendor</th>
                            <th class="text-end required">KM</th>
                            <th class="text-end required">Rate</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Contractor</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="freight_invoice_table_body">
                        @php
                            $items = isset($freight) ? $freight->contractorItems : collect();
                        @endphp
                        @for ($i = 0; $i < 150; $i++) 
                            @php
                                $item = $items->get($i);
                            @endphp
                            <tr data-is-ref-modal-open="false" data-references="[]"
                            data-index="{{ $i }}">
                            <td class="text-center">
                                <input type="text" class="form-control text-center" value="{{ $i + 1 }}" disabled="">
                            </td>
                            <td>
                                <input type="text" name="bill_date[]" id="bill_date" class="form-control date-format"
                                    placeholder="DD-MM-YYYY" value="{{ $item && $item->date ? date('d-m-Y', strtotime($item->date)) : '' }}">
                            </td>
                            <td>
                                <input type="text" name="code[]" class="form-control" value="{{ $item->code ?? '' }}">
                            </td>
                            <td>
                                <input type="text" class="form-control row-picker destination-id" data-picker="destination" placeholder="" id="destination_id" value="{{ $item->destination->name ?? '' }}">
                                <input type="hidden" name="destination_id[]" class="picked-id-hidden" value="{{ $item->destination_id ?? '' }}">
                            </td>
                            <td>
                                <input type="text" name="route[]" class="form-control" value="{{ $item->route ?? '' }}">
                            </td>
                            <td>
                                <input type="text" name="bag[]" class="form-control text-end quantity only-number" value="{{ $item && $item->bag_count > 0 ? (int)$item->bag_count : '' }}">
                            </td>
                            <td>
                                <input type="text" class="form-control row-picker vehicle-id" data-picker="vehicle" placeholder="" id="vehicle_id" value="{{ $item->vehicle->name ?? '' }}">
                                <input type="hidden" name="vehicle_id[]" class="picked-id-hidden" value="{{ $item->vehicle_id ?? '' }}">
                            </td>
                            <td>
                                <input type="text" name="vendor[]" class="form-control" value="{{ $item->vendor ?? '' }}">
                            </td>
                            <td>
                                <input type="text" name="km[]" id="km" class="form-control text-end only-number" value="{{ $item && $item->kms > 0 ? (float)$item->kms : '' }}">
                            </td>
                            <td>
                                <input type="text" name="rate[]" id="rate" class="form-control text-end rate only-number" value="{{ $item && $item->rate > 0 ? (float)$item->rate : '' }}">
                            </td>
                            <td class="position-relative">
                                <input type="text" name="amount[]" class="form-control text-end amount only-number show-ref-details"
                                    style="font-size:1.1rem !important" value="{{ $item && $item->amount > 0 ? number_format($item->amount, 2, '.', '') : '' }}">
                                <div class="d-none position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75
                                         d-flex align-items-center justify-content-center ledger-balance-loader"
                                    style="z-index: 3;">Fetching...
                                    <div class="spinner-border spinner-border-sm text-primary ms-3"></div>
                                </div>
                            </td>
                            <td>
                                <input type="text" class="form-control row-picker contractor-id" data-picker="contractor" placeholder="" value="{{ $item->contractor->name ?? '' }}">
                                <input type="hidden" name="contractor_id[]" class="picked-id-hidden" value="{{ $item->contractor_id ?? '' }}">
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
                            <td colspan="5" class="text-end pe-2 fw-bold">Total</td>
                            <td><input type="text" style="font-size:1.1rem !important" id="total_bags"
                                    class="form-control text-end fw-bold" data-value="0.00" disabled value="{{ isset($freight) ? (int)$freight->contractorItems->sum('bag_count') : '0.00' }}"></td>
                            <td colspan="4"></td>
                            <td><input type="text" style="font-size:1.2rem !important" id="total_amount"
                                    class="form-control text-end fw-bold" data-value="0.00" disabled value="{{ isset($freight) ? number_format($freight->total_amount, 2, '.', '') : '0.00' }}"></td>
                            <td colspan="2"></td>
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
                    <input type="text" class="fw-bold  form-control  border-1" id="voucher_narration" name="narration" height="30px"
                        autocomplete="off" value="{{ $freight->remarks ?? '' }}">
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
