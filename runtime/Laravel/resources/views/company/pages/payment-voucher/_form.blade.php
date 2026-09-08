<form id="payment_voucher_form" autocomplete="off" data-form-mode="{{ $formMode }}" action="{{ route('payment-vouchers.store') }}">
    <input type="hidden" name="uuid" id="uuid" value="{{ uuid() }}">
    <input type="hidden" name="form_mode" id="form_mode" value="{{ $formMode }}">
    <div class="grid-row m-erp-po-row-1">
        <div class="grid-item">
            <label class="form-label required fw-bold">
                <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Voucher No.
            </label>
            @if ($formMode === 'edit')
                <select name="" id="voucher_id" class="form-select select2">
                    <option value="">-- Select Payment --</option>
                    @foreach ($voucherSerials as $serial)
                        <option value="{{ $serial->id }}" @if ($serial->id == $voucherId) selected @endif>
                            {{ $serial->voucher_serial }}
                        </option>
                    @endforeach
                </select>
            @else
                <input type="text" name="voucher_serial" value="{{ $serial }}" id="voucher_serial"
                    class="form-control fw-bold" disabled>
            @endif
        </div>
        <div class="grid-item">
            <label class="form-label required fw-bold">
                <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Voucher Date
            </label>
            <input type="text" name="voucher_date" id="voucher_date" class="form-control date-format"
                placeholder="DD-MM-YYYY" value="{{ current_date_dmy() }}">
        </div>
        <div class="grid-item">
            <label class="form-label fw-bold">
                <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Day
            </label>
            <input type="text" name="weekday" id="weekday" class="form-control fw-bold text-dark"
                value="{{ date('l') }}" disabled>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-9" style="max-height: 400px; overflow-y: auto; position: relative;">
            <table class="table border border-1 border-dark-subtle table-bordered" id="payment_voucher_table"
                style="table-layout: fixed; width:100%;">
                <colgroup>
                    <col style="width:70px">
                    <col style="width:50px">
                    <col style="width:450px">
                    <col style="width:160px">
                    <col style="width:160px">
                    <col style="width:150px">
                </colgroup>

                <thead style="position: sticky; top: 0; z-index: 999;">
                    <tr>
                        <th class="text-center">Sr.No.</th>
                        <th class="text-center">D/C</th>
                        <th class="text-start">Account Name</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Credit</th>
                        <th class="text-end">Balance</th>
                    </tr>
                </thead>
                <tbody id="payment_voucher_table_body">
                    @for ($i = 0; $i < 50; $i++)
                    <tr data-is-ref-modal-open="false" data-references="[]" data-index="{{ $i }}">
                        <td class="text-center">
                            <input type="text" class="form-control text-center" value="{{ $i + 1 }}" disabled="">
                        </td>
                        <td>
                            <input type="text" class="form-control text-center dr-cr show-ref-details" maxdepth="1">
                        </td>
                        <td class="text-start">
                            <select class="form-select account-id"></select>
                        </td>
                        <td>
                            <input type="text" class="form-control text-end debit-amount only-number show-ref-details"
                                style="font-size:1.1rem !important">
                        </td>
                        <td>
                            <input type="text" class="form-control text-end credit-amount only-number show-ref-details"
                                style="font-size:1.1rem !important">
                        </td>
                        <td class="position-relative">
                            <input type="text" class="form-control text-end ledger-balance show-ref-details"
                                style="font-size:1.1rem !important" disabled>

                            <div class="d-none position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75
                                d-flex align-items-center justify-content-center ledger-balance-loader"
                                style="z-index: 3;">Fetching...
                                <div class="spinner-border spinner-border-sm text-primary ms-3"></div>
                            </div>
                        </td>
                    </tr>
                    @endfor
                </tbody>
                <tfoot style="position: sticky; bottom: 0; z-index: 999;">
                    <tr>
                        <td colspan="3" class="text-end pe-2 fw-bold">Total</td>
                        <td><input type="text" style="font-size:1.1rem !important" id="total_debit"
                                class="form-control text-end fw-bold" data-value="0.00" disabled value="0.00"></td>
                        <td><input type="text" style="font-size:1.2rem !important" id="total_credit"
                                class="form-control text-end fw-bold" data-value="0.00" disabled value="0.00"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="col-lg-3">
            <div class="card shadow-sm">
                <div class="card-header py-2 fw-bold">
                    Reference Preview
                </div>
                <div class="card-body p-2 bg-light" id="ref_current_row_preview"
                    style="min-height: 350px; max-height: 400px; overflow-y: auto;">
                </div>
            </div>
        </div>
    </div>
    <div>
        <div class="row">
            <div class="col-lg-9">
                <div class="form-group mt-2">
                    <label for="voucher_narration" class="fw-bold">Narration</label>
                    <input type="text" class="fw-bold form-control border-1" id="voucher_narration" height="30px"
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
                    <button type="submit" class="btn btn-primary px-4 form-save-btn waves-effect"
                        data-bs-toggle="tooltip" data-bs-placement="bottom" id="save_btn"
                        data-bs-original-title="{{ ($formMode === 'edit') ? 'Update (Alt + S)' : 'Save (Alt + S)' }}">
                        <i class="fa-solid fa-floppy-disk me-1"></i>
                        {{ ($formMode === 'edit') ? 'Update' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@include('_partial.calculator')
