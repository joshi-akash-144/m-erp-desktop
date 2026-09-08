@php
    $route = $formMode === 'create' ? route('multi-grns.import.process') : '';
@endphp

<form action="{{ $route }}" id="multi_grn_form" method="POST" autocomplete="off" data-form-mode="{{ $formMode }}"  enctype="multipart/form-data">
    @csrf
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
        <div class="grid-row m-erp-multi-grn-row-1 row-body">
            {{-- Customer --}}
            <div class="grid-item">
                <label class="form-label required">
                    <i class="fa-solid fa-truck-field me-1 text-secondary"></i> Customer Name
                </label>
                <select name="account_id" id="account_id" class="form-select">
                    <option value="">-- Select Customer --</option>
                    @foreach ($customers as $customerId => $customer)
                        <option value="{{ $customerId }}">{{ $customer }}</option>
                    @endforeach
                </select>
                <div id="account_id_loader" class="mt-1 d-none">
                    <span class="text-primary small">
                        <i class="fa fa-spinner fa-spin me-1"></i> Loading Account Details...
                    </span>
                </div>
            </div>

            {{-- City --}}
            {{-- <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-city me-1 text-secondary"></i> City
                </label>
                <input type="text" id="account_id_city" class="form-control" disabled placeholder="City">
            </div> --}}

            {{-- SO Type --}}
            {{-- <div class="grid-item">
                <label class="form-label">
                    <i class="fa-solid fa-id-card me-1 text-secondary"></i> S.O. Type
                </label>
                <input type="text" name="account_id_type" id="account_id_type" class="form-control" disabled>
            </div> --}}

        </div>

        <!-- 🧩 Row 2 -->
        <div class="grid-row m-erp-multi-grn-row-2 row-body">

            {{-- SO Type --}}
            <div class="grid-item">
                <div class="card">
                    <div class="card-body">
                        <div class="row ">
                            <div class="col-md-12 d-flex justify-content-between">
                                <h3 class="card-title form-label required d-flex align-items-center">
                                    <i class="fa-solid fa-file me-1 text-secondary"></i> Import File
                                </h3>
                                <a href="{{ asset('multi-grn-sample-file.XLSX') }}" download="" class="btn btn-sm btn-secondary d-flex align-items-center">
                                    <i class="fa-solid fa-download p-1"></i>
                                    Download Sample File
                                </a>
                            </div>                            
                        </div>
                        <div id="dropzone-default" class="dropzone mt-2 d-flex justify-content-center">
                            <div class="fallback">
                                <input name="file" type="file" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="row g-3">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-footer bg-light border-top p-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <small class="text-muted d-flex align-items-center gap-2">
                                <i class="fa-solid fa-circle-info text-primary me-1"></i>
                                Review all details before saving
                                <span class="text-secondary">|</span>
                                <span><span class="text-danger">*</span> Fields are required</span>
                            </small>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary" tabindex="-1" id="CancelImport">
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
