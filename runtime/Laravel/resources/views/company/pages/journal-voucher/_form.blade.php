<form id="journal_voucher_form" autocomplete="off" data-form-mode="create"
    action="{{ route('journal-vouchers.store') }}">
    <input type="hidden" name="uuid" id="uuid" value="{{ uuid() }}">
    <input type="hidden" name="form_mode" id="form_mode" value="{{ $formMode }}">
    <div class="grid-row m-erp-po-row-1">
        <div class="grid-item">
            <label class="form-label required fw-bold">
                <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Voucher No.
            </label>
            @if ($formMode === 'edit')
            <select name="" id="voucher_id" class="form-select select2">
                <option value="">Select Journal</option>
                @foreach ($voucherSerials as $serial)
                <option value="{{ $serial->id }}" @if ($serial->id == $voucherId) selected @endif>
                    {{ $serial->voucher_serial }}
                </option>
                @endforeach
            </select>
            @else
            <input type="text" name="voucher_serial" value="{{ $serial }}" id="voucher_serial"
                class="form-control date-format  fw-bold" disabled>
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
        <div class="grid-item">
            <label class="form-label fw-bold">
                <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> GST Nature
            </label>
            <select name="" id="gst_nature" class="custom-select2">
                <option value="gst_not_applicable">None Applicable</option>
                {{-- <option value="registered_expense_b2b">Registered Expense(B2B)</option>
                <option value="gst_tax_adjustment">GST Tax Adjustment</option> --}}
            </select>
        </div>
        @php
        use App\Models\Company;
        @endphp
        
        @if(company_type() == Company::TRANSPORT)
            <div class="grid-item">
            <label class="form-label fw-bold">
                <i class="fa-solid fa-truck-fast me-1 text-secondary"></i> Vehicle
            </label>
            <select name="vehicle_id" id="vehicle_id" class="custom-select2">
                <option value="">Select Vehicle</option>
                @foreach ($vehicles as $vehicle)
                <option value="{{ $vehicle->id }}">
                    {{ $vehicle->name }}
                </option>
                @endforeach
                
            </select>
        </div>

        <div class="grid-item">
            <div class="grid-item">
            <label class="form-label fw-bold">
                <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Bill Date
            </label>
            <input type="text" name="bill_date" id="bill_date" class="form-control date-format"
                placeholder="DD-MM-YYYY" value="{{ current_date_dmy() }}">
        </div>
        </div>

        @endif
        <div class="grid-item">
            <div class="grid-item">
                <label class="form-label fw-bold">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Bill No
                </label>
                <input type="text" name="reference_number" id="reference_number" class="form-control" placeholder="Enter Bill No" value="">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-9" style="max-height: 400px; overflow-y: auto; position: relative;">
            <table class="table border border-1 border-dark-subtle table-bordered" id="journal_voucher_table"
                style="table-layout: fixed; width:100%;">
                <colgroup>
                    <col style="width:70px"> <!-- Yes/No -->
                    <col style="width:50px"> <!-- File No -->
                    <col style="width:450px"> <!-- Bill No -->
                    <col style="width:160px"> <!-- Date -->
                    <col style="width:160px"> <!-- ShowDate -->
                    <col style="width:150px"> <!-- ShowDate -->
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
                <tbody id="journal_voucher_table_body">
                    @for ($i = 0; $i < 50; $i++) <tr data-is-ref-modal-open="false" data-references="[]"
                        data-index="{{ $i }}">
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
                            <input type="text" class="form-control  text-end debit-amount only-number show-ref-details"
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
                        <td> <input type="text" style="font-size:1.1rem !important" id="total_debit"
                                class="form-control text-end fw-bold" data-value="0.00" disabled value="0.00"></td>
                        <td> <input type="text" style="font-size:1.2rem !important" id="total_credit"
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
<!-- TDS Modal -->
<div class="modal fade" id="tds_modal" tabindex="-1" aria-labelledby="tdsModalLabel" aria-hidden="true"
    data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fs-5 fw-semibold" id="tdsModalLabel"><i
                        class="fa-solid fa-calculator me-2"></i>Tax Deducted at Source (TDS) Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="tds_computation_form">
                <div class="modal-body bg-light p-4">

                    <!-- Subheader -->
                    <div class="text-center mb-4 pb-2 border-bottom">
                        <h6 class="fw-bold text-primary mb-1">
                            TDS Deduction Details for Account: <span id="tds_sub_party_name"></span>
                            <span class="text-muted">(PAN - <span id="tds_sub_party_pan"></span>)</span>
                        </h6>
                    </div>

                    <!-- Top Info Grid in Cards -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-2">
                                    <label class="form-label mb-0 fw-bold text-muted small">TDS Category</label>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select form-select-sm fw-bold bg-white text-primary"
                                        id="tds_category_id">
                                        @foreach($tdsCategories as $category)
                                        <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                        @endforeach
                                    </select>

                                    <!-- Hidden inputs required by JS logic -->
                                    <input type="hidden" id="tds_party_name"><input type="hidden"
                                        id="tds_party_pan"><input type="hidden" id="tds_party_id">
                                    <input type="hidden" id="tds_expense_name"><input type="hidden" id="tds_expense_id">
                                    <input type="hidden" id="tds_payee_name"><input type="hidden" id="tds_payee_id">
                                </div>

                                <div class="col-md-3 text-md-end">
                                    <label class="form-label mb-0 fw-bold text-muted small">Previous Turnover</label>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light fw-bold">₹</span>
                                        <input type="text" class="form-control fw-bold bg-white text-dark"
                                            id="tds_previous_turnover" readonly value="0.00">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Table -->
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover mb-0 align-middle text-center">
                                    <thead class="table-light text-muted small">
                                        <tr>
                                            <th style="width: 50px;">S No.</th>
                                            <th>Ref. No.</th>
                                            <th>Expense Amt.</th>
                                            <th>TDS Ded. On</th>
                                            <th>Expense Date</th>
                                            <th>TDS %</th>
                                            <th>TDS Amt.</th>
                                            <th>Sur. %</th>
                                            <th>Sur. Amt.</th>
                                            <th>E. Cess %</th>
                                            <th>E. Cess Amt.</th>
                                            <th>S. Cess %</th>
                                            <th>S. Cess Amt.</th>
                                            <th>Total Tax</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white">
                                        <tr>
                                            <td class="text-muted fw-bold">1</td>
                                            <td><input type="text" class="form-control form-control-sm text-center"
                                                    id="tds_ref_no" value=""></td>
                                            <td><input type="number"
                                                    class="form-control form-control-sm text-end fw-bold text-primary bg-white"
                                                    id="tds_assessable_value" step="0.01"></td>
                                            <td><input type="text"
                                                    class="form-control form-control-sm text-center bg-light"
                                                    id="tds_ded_on" readonly></td>
                                            <td><input type="text"
                                                    class="form-control form-control-sm text-center bg-light"
                                                    id="tds_exp_date" readonly></td>
                                            <td><input type="number"
                                                    class="form-control form-control-sm text-center fw-bold bg-white"
                                                    id="tds_percentage" step="0.01"></td>
                                            <td><input type="number"
                                                    class="form-control form-control-sm text-end fw-bold text-danger bg-white"
                                                    id="tds_amount" step="0.01"></td>
                                            <td class="text-muted small">0.00</td>
                                            <td class="text-muted small">0.00</td>
                                            <td class="text-muted small">0.00</td>
                                            <td class="text-muted small">0.00</td>
                                            <td class="text-muted small">0.00</td>
                                            <td class="text-muted small">0.00</td>
                                            <td class="fw-bold text-danger"><span id="tds_total_tax_cell">0.00</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="table-light fw-bold text-dark">
                                        <tr>
                                            <td colspan="2" class="text-end text-muted small">Totals:</td>
                                            <td class="text-end text-primary fs-6"><span
                                                    id="tds_foot_expense">0.00</span></td>
                                            <td colspan="3"></td>
                                            <td class="text-end text-danger fs-6"><span id="tds_foot_tds">0.00</span>
                                            </td>
                                            <td></td>
                                            <td class="text-end">0.00</td>
                                            <td></td>
                                            <td class="text-end">0.00</td>
                                            <td></td>
                                            <td class="text-end">0.00</td>
                                            <td class="text-end text-danger fs-6"><span id="tds_foot_total">0.00</span>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="text-center text-muted mt-3 small">
                        <i class="fa-solid fa-circle-info me-1"></i> All Values must be Rounded off as required in TDS
                        return.
                    </div>
                </div>

                <div class="modal-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
                    <div>
                        {{-- <span class="badge bg-secondary me-2">[ Esc - Quit ]</span>
                        <span class="badge bg-primary">[ F2 - Done ]</span> --}}
                    </div>
                    <div>
                        <button type="button" class="btn btn-light border px-4 me-2 non-selectable"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success px-5 fw-bold shadow-sm" id="btn_confirm_tds">
                            <i class="fa-solid fa-check me-1"></i> Post with TDS
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>@include('_partial.calculator')
