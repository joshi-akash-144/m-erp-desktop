@php
    $storeRoute  = route('credit-notes.store');
@endphp

<form action="{{ $storeRoute }}" id="credit_note_form" autocomplete="off" data-form-mode="{{ $formMode }}">
    @if ($formMode === 'create')
        <input type="hidden" name="uuid" value="{{ uuid() }}">
    @endif

    {{-- =============================================
         GENERAL DETAILS
         ============================================= --}}
    <div class="module-form-section section-danger border-danger">
        <div class="module-page-title text-danger">
            <i class="fa-solid fa-rotate-left me-2"></i>
            Credit Note (Sale Return)
        </div>

        <div class="grid-row cn-row-1 row-body">

            {{-- Voucher No. --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-hashtag me-1 text-secondary"></i> Voucher No.
                </label>
                @if ($formMode === 'edit')
                    <select name="credit_note_id" id="credit_note_id" class="form-select select2">
                        <option value="">--Select CN--</option>
                        @foreach ($cnSerials as $cn)
                            <option value="{{ $cn->id }}" @selected($cn->id == ($creditNoteId ?? null))>
                                {{ $cn->credit_note_serial }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="text" class="form-control bg-primary-lt fw-bold text-dark"
                        value="{{ $cnInfo->serial ?? '' }}" disabled>
                @endif
            </div>

            {{-- Date --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Date
                </label>
                <input type="text" name="credit_note_date" id="credit_note_date"
                    class="form-control date-format" placeholder="DD-MM-YYYY" value="">
            </div>

            {{-- Ref No --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-tag me-1 text-secondary"></i> Ref No.
                </label>
                <input type="text" name="reference_number" id="reference_number"
                    class="form-control" placeholder="Ref No." value="{{ isset($cnInfo) ? 'CRN-' . $cnInfo->serial : '' }}">
            </div>

            {{-- Party Name --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-user me-1 text-secondary"></i> Party Name
                </label>
                <select name="account_id" id="account_id" class="form-select">
                    <option value="">--Select Customer--</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer['id'] }}">{{ $customer['name'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Bill No. (Sales Invoice reference) --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-file-invoice me-1 text-secondary"></i> Bill No.
                </label>
                <select name="sales_invoice_id" id="sales_invoice_id" class="form-select">
                    <option value="">--Select Invoice--</option>
                </select>
                <input type="hidden" name="sales_invoice_serial" id="sales_invoice_serial">
            </div>

            {{-- Invoice Type (Sale Type) --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-tag me-1 text-secondary"></i> Invoice Type
                </label>
                <select name="sale_type_id" id="sale_type_id" class="form-select">
                    <option value="">Select</option>
                    @foreach ($saleTypes as $saleType)
                        <option value="{{ $saleType->id }}">{{ $saleType->name }}</option>
                    @endforeach
                </select>
            </div>

        </div>

        {{-- GST type badge --}}
        {{-- <div class="row-body mt-1">
            <span class="text-muted small me-2">GST Type:</span>
            <span id="gst_type_badge" class="badge bg-secondary text-white">—</span>
        </div> --}}
    </div>

    {{-- =============================================
         ITEM DETAILS
         ============================================= --}}
    <div class="module-form-section section-danger border-danger mt-2">
        <div class="module-page-title text-danger">
            <i class="fa-solid fa-boxes-stacked me-2"></i>
            Item Details
        </div>

        <div class="table-scroll-x" style="overflow-x:auto">
            <table class="table table-sm align-middle border mb-0" id="item_table">
                <thead class="table-light text-nowrap">
                    <tr>
                        {{-- <th class="text-center" style="width:40px;">
                            <i class="fa-solid fa-list-ol text-secondary"></i>
                        </th> --}}
                        <th style="width:25%">
                            <i class="fa-solid fa-box text-secondary me-1"></i> Item Name
                        </th>
                        <th style="width:10%">
                            <i class="fa-solid fa-ruler-combined text-secondary me-1"></i> Unit
                        </th>
                        <th class="text-end" style="width:12%">
                            <i class="fa-solid fa-weight-hanging text-secondary me-1"></i> Qty
                        </th>
                        <th class="text-end" style="width:14%">
                            <i class="fa-solid fa-tag text-secondary me-1"></i> Rate
                        </th>
                        <th class="text-end" style="width:16%">
                            <i class="fa-solid fa-indian-rupee-sign text-secondary me-1"></i> Amount
                        </th>
                    </tr>
                </thead>
                <tbody id="item_table_body">
                    <tr class="item-row">
                        {{-- <td class="text-center text-muted small">1</td> --}}
                        <td>
                            <select name="items[0][item_id]" class="form-select form-select-sm item_id select2">
                                <option value="">--Select Product--</option>
                                @foreach ($items as $item)
                                    <option value="{{ $item['id'] }}">{{ $item['name'] }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="items[0][unit_name]" class="unit_name_hidden">
                        </td>
                        <td><input type="text" name="items[0][unit_name_display]" class="form-control form-control-sm unit_name" readonly placeholder="Unit"></td>
                        <td><input type="number" name="items[0][quantity]" class="form-control form-control-sm qty text-end" min="0" step="0.001" value="0"></td>
                        <td><input type="number" name="items[0][rate]"     class="form-control form-control-sm rate text-end" min="0" step="0.01" value="0"></td>
                        <td><input type="number" name="items[0][amount]"   class="form-control form-control-sm amount text-end" readonly value="0"></td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Summary card: particulars + narration --}}
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-body p-4">
                <div class="row g-4 align-items-start">

                    {{-- Particulars --}}
                    <div class="col-lg-7 col-md-12">

                        {{-- Total Qty / Amount --}}
                        <div class="row g-2 mb-2">
                            <div class="col-5">
                                <div class="rounded p-2 border border-secondary-lt d-flex justify-content-between align-items-center">
                                    <div class="text-muted small d-flex align-items-center">
                                        <i class="fa-solid fa-cube me-1 text-secondary"></i> Total Qty
                                    </div>
                                    <input type="text" name="total_qty" id="total_qty"
                                        class="form-control border-1 border-secondary-subtle text-end fw-bold w-auto"
                                        style="max-width:300px;font-size:1rem" disabled value="0.000">
                                </div>
                            </div>
                            <div class="col-7">
                                <div class="rounded p-2 border border-secondary-lt d-flex justify-content-between align-items-center">
                                    <div class="text-muted small d-flex align-items-center">
                                        <i class="fa-solid fa-indian-rupee-sign me-1 text-secondary"></i> Total Amount
                                    </div>
                                    <input type="text" name="base_total_amount" id="base_total_amount"
                                        data-amount=""
                                        class="form-control border-1 border-secondary-subtle text-end fw-bold text-primary w-auto"
                                        style="max-width:500px;font-size:1rem" value="₹0.00" disabled>
                                </div>
                            </div>
                        </div>

                        {{-- Bill Sundry / GST Particulars --}}
                        <table class="table table-sm align-middle" id="particular_table">
                            <thead>
                                <tr>
                                    <th width="30" class="text-center">
                                        <i class="fa-solid fa-hashtag text-secondary"></i>
                                    </th>
                                    <th>Particular</th>
                                    <th width="100" class="text-center">%</th>
                                    <th width="200" class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody id="particular_table_body"></tbody>
                            <tfoot>
                                <tr>
                                    <td>
                                        {{-- <span id="add_particular_row" class="btn btn-action btn-sm"
                                            data-bs-toggle="tooltip" data-bs-placement="right"
                                            data-bs-original-title="Add Particular">
                                            <i class="fa-solid fa-plus"></i>
                                        </span> --}}
                                    </td>
                                    <td colspan="2" class="text-end fw-semibold">Net Total</td>
                                    <td>
                                        <input type="text" id="net_total" name="net_total" tabindex="-1"
                                            class="form-control border-1 border-secondary-subtle text-end fw-bold text-danger"
                                            style="font-size:1.1rem" readonly value="0.00">
                                    </td>
                                </tr>
                                <tr class="d-none" id="gross_total_row">
                                    <td colspan="3" class="text-end text-muted small">Gross Total</td>
                                    <td>
                                        <input type="text" id="gross_total" tabindex="-1"
                                            class="form-control border-1 border-secondary-subtle text-end fw-bold text-primary"
                                            style="font-size:1rem" readonly value="0.00">
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Narration --}}
                    <div class="col-lg-5 col-md-12">
                        <label class="form-label fw-semibold mb-2">
                            <i class="fa-regular fa-note-sticky me-2 text-primary"></i> Narration
                        </label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="5"
                            placeholder="Enter additional notes or remarks..."></textarea>
                        <small class="text-muted mt-1 d-block">
                            <i class="fa-solid fa-circle-info me-1"></i> Optional
                        </small>
                    </div>
                </div>
            </div>

            {{-- Footer buttons --}}
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
                        <button type="submit" class="btn btn-danger px-4" id="save_btn">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
</form>
