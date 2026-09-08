@php
    $route = $formMode === 'create' ? route('mobile-grns.store') : '';
@endphp

<form action="{{ $route }}" id="mobile_grn_form" autocomplete="off" data-form-mode="{{ $formMode }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($formMode === 'edit')
        @method('PUT')
    @endif
    
    @if ($formMode === 'create')
        <input type="hidden" name="uuid" value="{{ Str::uuid() }}">
    @endif 
    <div class="module-form-section section">
        <div class="module-page-title">
            <i class="fa-solid fa-file-invoice me-2 text-primary"></i>
            General Information
        </div>

        <div class="grid-row m-erp-so-row-1 row-body">
            {{-- GRN No --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-hashtag me-1 text-secondary"></i> GRN No
                </label>
                @if ($formMode === 'edit')
                    <select name="" id="grn_id" class="form-select select2">
                        <option value="">--Select GRN --</option>
                        @foreach ($grnSerials as $serial)
                            <option value="{{ $serial->id }}" @if ($serial->id == $grnId) selected @endif>
                                {{ $serial->grn_serial }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="text" name="grn_number" id="grn_number" class="form-control bg-primary-lt fw-bold text-dark"
                        placeholder="GRN Number" value="{{ $grnSerial }}" readonly>
                @endif
            </div>

            {{-- GRN Date --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> GRN Date
                </label>
                <input type="text" name="grn_date" id="grn_date" class="form-control date-format" value="{{ date('d-m-Y') }}">
            </div>

            {{-- Date In --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Date In
                </label>
                <input type="text" name="grn_in_date" id="grn_in_date" class="form-control date-format" value="{{ date('d-m-Y') }}">
            </div>

            {{-- Token No --}}
            {{-- <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Token No
                </label>
                <input type="text" name="token_no" id="token_no" class="form-control">
            </div> --}}

            {{-- Supplier --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-truck-field me-1 text-secondary"></i> Supplier
                </label>
                <select name="account_id" id="account_id" class="form-select select2">
                    <option value="">--Select Supplier--</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- City --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-city me-1 text-secondary"></i> City
                </label>
                <input type="text" name="city" id="city" class="form-control" readonly>
            </div>
            {{-- Party Bill No --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-id-card me-1 text-secondary"></i> Party Bill No.
                </label>
                <div class="input-icon mb-3">
                    <input type="text" name="reference_number" id="reference_number" class="form-control">
                    <span class="input-icon-addon d-none" id="reference_number_loader">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                    </span>
                </div>
            </div>
        </div>

        <div class="grid-row m-erp-so-row-1 row-body">

            {{-- Bill Date --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-boxes-stacked me-1 text-secondary"></i> Party Bill Date
                </label>
                <input type="text" name="party_bill_date" id="party_bill_date" class="form-control date-format">
            </div>

            {{-- Party Quantity --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-scale-balanced me-1 text-secondary"></i> Party Qty
                </label>
                <input type="number" name="party_qty" id="p_qty" class="form-control">
            </div>

            {{-- Product --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-boxes-stacked me-1 text-secondary"></i> Product
                </label>
                <select name="item_id" id="item_id" class="form-select select2">
                    <option value="">--Select Product--</option>
                    @foreach ($items as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Destination --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-clipboard-list me-1 text-secondary"></i> Destination
                </label>
                <select name="destination_id" id="destination_id" class="form-select select2">
                    <option value="">--Select Destination--</option>
                    @foreach ($destinations as $destination)
                        <option value="{{ $destination->id }}">{{ $destination->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Rate --}}
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-hashtag me-1 text-secondary"></i> Rate
                </label>
                <input type="text" name="rate" id="rate" class="form-control">
            </div>

            {{-- Token No --}}
            {{-- <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-regular fa-calendar-days me-1 text-secondary"></i> Token No
                </label>
                <input type="text" name="token_no" id="token_no" class="form-control">
            </div> --}}

            {{-- Vehicle No --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-truck me-1 text-secondary"></i> Vehicle No
                </label>
                <input type="text" name="vehicle_number" id="vehicle_number" class="form-control txtRegNo">
            </div>
        </div>

    <!-- ⚖️ Weight & Bag Details -->
    <div class="module-form-section section mt-3">
        <div class="module-page-title">
            <i class="fa-solid fa-weight-hanging me-2"></i>
            Weight & Bag Details
        </div>

    {{--<div class="grid-row m-erp-so-row-5 row-body mt-2">
            {{-- Party Bill File 
            <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-file-arrow-up me-1 text-secondary"></i> Party Bill
                </label>
                <input type="file" name="url_path" id="url_path" class="form-control" accept="image/*">
            </div>
        </div> --}}


        {{-- Remarks --}}

    <div class="row g-3 mt-1">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-1">
                        <div class="col-12">
                <label class="form-label">
                    <i class="fa-regular fa-note-sticky me-1 text-secondary"></i> Additional Remarks
                </label>
                <textarea name="remarks" id="remarks" class="form-control" rows="3" placeholder="Enter any additional information here..."></textarea>
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
                                <button type="submit" class="btn btn-primary px-4 form-save-btn waves-effect"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom">
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

</form>


