@php
$formMode  = $modalData['form_mode'] ?? 'create';
$title     = $modalData['title'] ?? 'Add Broker';
$uuid      = $modalData['uuid'] ?? '';
$data      = $modalData['data'] ?? null;
$countries = $modalData['countries'] ?? [];
$states    = $modalData['states'] ?? [];
$isView    = $formMode === 'view';
@endphp

<div class="modal fade master-modal" id="broker_modal" data-bs-backdrop="static"
     data-bs-keyboard="false" tabindex="-1" aria-labelledby="broker_modal" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header master-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="master-badge bg-primary-lt rounded fs-2">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-database" width="24" height="24"
                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 6m-8 0a8 3 0 1 0 16 0a8 3 0 1 0 -16 0" />
                            <path d="M4 6v6a8 3 0 0 0 16 0v-6" />
                            <path d="M4 12v6a8 3 0 0 0 16 0v-6" />
                        </svg>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0 font-monospace">{{ $title }}</h5>
                        <small class="badge bg-teal text-teal-fg">Master Data Management</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="broker_form"
                    action="{{ $formMode === 'edit' ? route('brokers.update', $data->id) : route('brokers.store') }}"
                    method="POST" class="needs-validation" novalidate>

                    @csrf
                    @if($formMode === 'edit')
                        @method('PUT')
                    @else
                        <input type="hidden" name="uuid" value="{{ $uuid }}">
                    @endif

                    <input type="hidden" id="form_mode" value="{{ $formMode }}">

                    <div class="row g-2">
                        <div class="col-md-12">

                            {{-- General Details --}}
                            <div class="master-form-section">
                                <div class="master-section-title">
                                    <i class="fa-solid fa-file-pen me-2 text-primary"></i>General Details
                                </div>
                                <div class="row g-2">

                                    <!-- Name -->
                                    <div class="col-lg-4 col-sm-12 col-md-12">
                                        <label class="form-label required">
                                            <i class="fa-solid fa-people-group text-secondary"></i> Name
                                        </label>
                                        <input type="text" name="name" id="name" class="form-control"
                                            placeholder="{{ $isView ? '' : 'Enter Broker Name' }}"
                                            value="{{ old('name', $data->name ?? '') }}"
                                            {{ $isView ? 'readonly' : 'required' }}>
                                    </div>

                                    <!-- Print Name -->
                                    <div class="col-lg-4 col-sm-12 col-md-12">
                                        <label class="form-label required">
                                            <i class="fa-solid fa-print text-secondary"></i> Print Name
                                        </label>
                                        <input type="text" name="print_name" id="print_name" class="form-control"
                                            placeholder="{{ $isView ? '' : 'Enter Print Name' }}"
                                            value="{{ old('print_name', $data->print_name ?? '') }}"
                                            {{ $isView ? 'readonly' : 'required' }}>
                                    </div>

                                    <!-- PAN -->
                                    <div class="col-lg-2 col-sm-12 col-md-12">
                                        <label class="form-label">
                                            <i class="fa-solid fa-id-card text-secondary"></i> PAN
                                        </label>
                                        <input type="text" name="pan" id="pan" class="form-control"
                                            placeholder="{{ $isView ? '' : 'Enter PAN' }}"
                                            value="{{ old('pan', $data->pan ?? '') }}"
                                            {{ $isView ? 'readonly' : '' }}>
                                    </div>

                                    <!-- Mobile -->
                                    <div class="col-lg-2 col-sm-12 col-md-12">
                                        <label class="form-label">
                                            <i class="fa-solid fa-mobile text-secondary"></i> Mobile
                                        </label>
                                        <input type="text" name="mobile_number" id="mobile_number"
                                            class="form-control"
                                            placeholder="{{ $isView ? '' : 'Enter Mobile Number' }}"
                                            value="{{ old('mobile_number', $data->mobile_number ?? '') }}"
                                            {{ $isView ? 'readonly' : '' }}>
                                    </div>

                                    <!-- Email -->
                                    <div class="col-lg-4 col-sm-12 col-md-12">
                                        <label class="form-label">
                                            <i class="fa-solid fa-envelope text-secondary"></i> Email
                                        </label>
                                        <input type="text" name="email" id="email" class="form-control"
                                            placeholder="{{ $isView ? '' : 'Enter Email' }}"
                                            value="{{ old('email', $data->email ?? '') }}"
                                            {{ $isView ? 'readonly' : '' }}>
                                    </div>

                                    <!-- Country -->
                                    <div class="col-lg-2 col-sm-12 col-md-12">
                                        <label class="form-label">
                                            <i class="fa-solid fa-earth-americas text-secondary"></i> Country
                                        </label>
                                        <select name="country_id" id="country" class="form-select select2"
                                            {{ $isView ? 'disabled' : '' }}>
                                            <option value="">Select Country...</option>
                                            @foreach ($countries as $country)
                                                <option value="{{ $country->id }}"
                                                    {{ ($data->country_id ?? 1) == $country->id ? 'selected' : '' }}>
                                                    {{ $country->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- State -->
                                    <div class="col-lg-2 col-sm-12 col-md-12">
                                        <label class="form-label">
                                            <i class="fa-solid fa-map-location-dot text-secondary"></i> State
                                        </label>
                                        <select name="state_id" id="state" class="form-select select2"
                                            {{ $isView ? 'disabled' : '' }}>
                                            <option value="">State...</option>
                                            @foreach ($states as $state)
                                                <option value="{{ $state->id }}"
                                                    {{ ($data->state_id ?? company_state_id()) == $state->id ? 'selected' : '' }}>
                                                    {{ $state->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- City -->
                                    <div class="col-lg-2 col-sm-12 col-md-12">
                                        <label class="form-label">
                                            <i class="fa-solid fa-city text-secondary"></i> City
                                        </label>
                                        <input type="text" name="city" id="city" class="form-control"
                                            placeholder="{{ $isView ? '' : 'Enter City' }}"
                                            value="{{ old('city', $data->city ?? '') }}"
                                            {{ $isView ? 'readonly' : '' }}>
                                    </div>

                                    <!-- PIN -->
                                    <div class="col-lg-2 col-sm-12 col-md-12">
                                        <label class="form-label">
                                            <i class="fa-solid fa-file-zipper text-secondary"></i> PIN
                                        </label>
                                        <input type="text" name="postal_code" id="postal_code" class="form-control"
                                            placeholder="{{ $isView ? '' : 'Postal Code' }}"
                                            value="{{ old('postal_code', $data->postal_code ?? '') }}"
                                            {{ $isView ? 'readonly' : '' }}>
                                    </div>

                                    <!-- Address One -->
                                    <div class="col-lg-4 col-sm-12 col-md-12">
                                        <label class="form-label">
                                            <i class="fa-solid fa-city text-secondary"></i> Address One
                                        </label>
                                        <input type="text" name="address_one" id="address_one" class="form-control"
                                            placeholder="{{ $isView ? '' : 'Enter Address One' }}"
                                            value="{{ old('address_one', $data->address_one ?? '') }}"
                                            {{ $isView ? 'readonly' : '' }}>
                                    </div>

                                    <!-- Address Two -->
                                    <div class="col-lg-4 col-sm-12 col-md-12">
                                        <label class="form-label">
                                            <i class="fa-solid fa-city text-secondary"></i> Address Two
                                        </label>
                                        <input type="text" name="address_two" id="address_two" class="form-control"
                                            placeholder="{{ $isView ? '' : 'Enter Address Two' }}"
                                            value="{{ old('address_two', $data->address_two ?? '') }}"
                                            {{ $isView ? 'readonly' : '' }}>
                                    </div>

                                </div>
                            </div>

                            {{-- Commission Details --}}
                            <div class="master-form-section">
                                <div class="master-section-title">
                                    <i class="fa-solid fa-percent text-primary"></i>&nbsp;Commission Details
                                </div>
                                <div class="row g-2">

                                    <!-- Sale Commission Rate -->
                                    <div class="col-lg-3 col-sm-12 col-md-12">
                                        <label class="form-label">
                                            <i class="fa-solid fa-percent text-secondary"></i> Sale Commission Rate (%)
                                        </label>
                                        <input type="text" name="sale_commission_rate" id="sale_commission_rate"
                                            class="form-control"
                                            placeholder="{{ $isView ? '' : 'Enter Sale Commission Rate' }}"
                                            value="{{ old('sale_commission_rate', $data->sale_commission_rate ?? '0.00') }}"
                                            {{ $isView ? 'readonly' : '' }}>
                                    </div>

                                    <!-- Purchase Commission Rate -->
                                    <div class="col-lg-3 col-sm-12 col-md-12">
                                        <label class="form-label">
                                            <i class="fa-solid fa-percent text-secondary"></i> Purchase Commission Rate (%)
                                        </label>
                                        <input type="number" name="purchase_commission_rate" id="purchase_commission_rate"
                                            class="form-control"
                                            placeholder="{{ $isView ? '' : 'Enter Purchase Commission Rate' }}"
                                            value="{{ old('purchase_commission_rate', $data->purchase_commission_rate ?? '0.00') }}"
                                            {{ $isView ? 'readonly' : '' }}>
                                    </div>

                                </div>
                            </div>

                            {{-- Bank Details --}}
                            <div class="master-form-section">
                                <div class="master-section-title">
                                    <i class="fa-solid fa-building-columns text-primary"></i>&nbsp;Bank Details
                                </div>
                                <div class="row g-2">

                                    <!-- Bank Name -->
                                    <div class="col-lg-3 col-sm-12 col-md-12">
                                        <label class="form-label">
                                            <i class="fa-solid fa-user text-secondary"></i> Bank Name
                                        </label>
                                        <input type="text" name="bank_name" id="bank_name" class="form-control"
                                            placeholder="{{ $isView ? '' : 'Enter Bank Name' }}"
                                            value="{{ old('bank_name', $data->bank_name ?? '') }}"
                                            {{ $isView ? 'readonly' : '' }}>
                                    </div>

                                    <!-- Branch Name -->
                                    <div class="col-lg-3 col-sm-12 col-md-12">
                                        <label class="form-label">
                                            <i class="fa-solid fa-map-pin text-secondary"></i> Branch Name
                                        </label>
                                        <input type="text" name="bank_branch_name" id="bank_branch_name" class="form-control"
                                            placeholder="{{ $isView ? '' : 'Enter Branch Name' }}"
                                            value="{{ old('bank_branch_name', $data->bank_branch_name ?? '') }}"
                                            {{ $isView ? 'readonly' : '' }}>
                                    </div>

                                    <!-- Account No. -->
                                    <div class="col-lg-3 col-sm-12 col-md-12">
                                        <label class="form-label">
                                            <i class="fa-solid fa-building-columns text-secondary"></i> Account No.
                                        </label>
                                        <input type="text" name="bank_account_number" id="bank_account_number"
                                            class="form-control"
                                            placeholder="{{ $isView ? '' : 'Enter Account Number' }}"
                                            value="{{ old('bank_account_number', $data->bank_account_number ?? '') }}"
                                            {{ $isView ? 'readonly' : '' }}>
                                    </div>

                                    <!-- IFSC Code -->
                                    <div class="col-lg-3 col-sm-12 col-md-12">
                                        <label class="form-label">
                                            <i class="fa-solid fa-building-columns text-secondary"></i> IFSC Code
                                        </label>
                                        <input type="text" name="bank_ifsc" id="bank_ifsc" class="form-control"
                                            placeholder="{{ $isView ? '' : 'Enter IFSC Code' }}"
                                            value="{{ old('bank_ifsc', $data->bank_ifsc ?? '') }}"
                                            {{ $isView ? 'readonly' : '' }}>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-link non-selectable waves-effect"
                    data-bs-dismiss="modal">Close</button>
                @if(!$isView)
                    <button type="submit" form="broker_form" class="btn btn-primary form-save-btn waves-effect">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-square-check" width="24" height="24"
                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                            <path d="M9 12l2 2l4 -4" />
                        </svg>
                        {{ $formMode === 'edit' ? 'Update Broker' : 'Save Broker' }}
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('js/master/broker/modal.js') }}?v={{ hash_file('md5', public_path('js/master/broker/modal.js')) }}"></script>
