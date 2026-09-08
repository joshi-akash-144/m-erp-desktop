@php
    $formMode = $modalData['form_mode'] ?? 'create'; // 'create' | 'edit' | 'view'
    $title = $modalData['title'] ?? 'Transport Party';
    $uuid = $modalData['uuid'] ?? '';
    $data = $modalData['data'] ?? null;
    $states = $modalData['states'] ?? [];
    
    $isView = $formMode === 'view';
    $isEdit = $formMode === 'edit';
@endphp

<div class="modal modal-blur fade" id="transport_parties_modal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content master-modal-content">
            <div class="modal-header master-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="master-badge bg-primary-lt rounded fs-2">
                        <i class="fa-solid fa-truck-ramp-box text-primary"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0 font-monospace">{{ $title }}</h5>
                        <small class="badge bg-teal text-teal-fg">Master Data Management</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" tabindex="-1"></button>
            </div>

            <div class="modal-body p-4">
                <form id="transport_parties_form" action="{{ $isEdit ? route('transport_parties.update', $data->id) : route('transport_parties.store') }}" 
                      method="POST" 
                      data-method="{{ $isEdit ? 'PUT' : 'POST' }}"
                      class="needs-validation ajax-form" novalidate>
                    @csrf
                    @if ($isEdit)
                        @method('PUT')
                    @else
                        <input type="hidden" name="uuid" value="{{ $uuid }}">
                    @endif
                    
                    <input type="hidden" id="form_mode" value="{{ $formMode }}">

                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="master-form-section h-100">
                                <div class="master-section-title mb-3">
                                    <i class="fa-solid fa-circle-info text-primary"></i> &nbsp; General Details
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label required"> <i class="fa-solid fa-user text-secondary"></i>&nbsp;Name</label>
                                        <input type="text" name="name" id="name" class="form-control"
                                            value="{{ $data->name ?? '' }}" 
                                            placeholder="Enter Transport Party Name"
                                            {{ $isView ? 'readonly' : '' }}>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"> <i class="fa-solid fa-envelope text-secondary"></i>&nbsp;Email</label>
                                        <input type="text" name="email" id="email" class="form-control"
                                            value="{{ $data->email ?? '' }}" 
                                            placeholder="Enter Email Address"
                                            {{ $isView ? 'readonly' : '' }}>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label"> <i class="fa-solid fa-phone text-secondary"></i>&nbsp;Mobile Number</label>
                                        <input type="text" name="mobile_number" id="mobile_number" class="form-control"
                                            value="{{ $data->mobile_number ?? '' }}" 
                                            placeholder="Enter Mobile Number"
                                            {{ $isView ? 'readonly' : '' }}>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label"> <i class="fa-solid fa-id-card text-secondary"></i>&nbsp;GST Number</label>
                                        <input type="text" name="gst_number" id="gst_number" class="form-control"
                                            value="{{ $data->gst_number ?? '' }}" 
                                            placeholder="Enter GST Number"
                                            {{ $isView ? 'readonly' : '' }}>
                                    </div>                                    

                                    <div class="col-md-6">
                                        <label class="form-label required"><i class="fa-solid fa-map-location-dot text-secondary"></i>&nbsp; State </label>
                                        <select name="state_id" id="state_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                                            <option value="">Select State...</option>
                                            @foreach ($states as $state)
                                                <option value="{{ $state->id }}" {{ ($data->state_id ?? company_state_id()) == $state->id ? 'selected' : '' }}>
                                                    {{ $state->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label required"> <i class="fa-solid fa-city text-secondary"></i>&nbsp;City</label>
                                        <input type="text" name="city" id="city" class="form-control"
                                            value="{{ $data->city ?? '' }}" 
                                            placeholder="Enter City"
                                            {{ $isView ? 'readonly' : '' }}>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label"> <i class="fa-solid fa-map-pin text-secondary"></i>&nbsp;Postal Code</label>
                                        <input type="text" name="postal_code" id="postal_code" class="form-control"
                                            value="{{ $data->postal_code ?? '' }}" 
                                            placeholder="Enter Postal Code"
                                            {{ $isView ? 'readonly' : '' }}>
                                    </div>

                                    <div class="col-md-8">
                                        <label class="form-label"> <i class="fa-solid fa-location-dot text-secondary"></i>&nbsp;Address Line 1</label>
                                        <input type="text" class="form-control" name="address_one" id="address_one" 
                                            placeholder="Enter Address Line 1"
                                            {{ $isView ? 'readonly' : '' }} value="{{ $data->address_one ?? '' }}">
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label"> <i class="fa-solid fa-location-dot text-secondary"></i>&nbsp;Address Line 2</label>
                                        <input type="text" class="form-control" name="address_two" id="address_two" 
                                            placeholder="Enter Address Line 2"
                                            {{ $isView ? 'readonly' : '' }} value="{{ $data->address_two ?? '' }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer master-modal-footer">
                <button type="button" class="btn btn-link non-selectable waves-effect" data-bs-dismiss="modal" tabindex="-1">Close</button>

                @if(!$isView)
                    <button type="submit" form="transport_parties_form" class="btn btn-primary form-save-btn waves-effect">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-square-check"
                             width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                             fill="none" stroke-linecap="round" stroke-linejoin="round">
                             <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                             <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                             <path d="M9 12l2 2l4 -4" />
                        </svg>
                        @if ($isEdit)
                            Update Transport Party
                        @else
                            Save Transport Party
                        @endif
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/master/transport-party/modal.js') }}?v={{ file_exists(public_path('js/master/transport-party/modal.js')) ? hash_file('md5', public_path('js/master/transport-party/modal.js')) : time() }}"></script>
