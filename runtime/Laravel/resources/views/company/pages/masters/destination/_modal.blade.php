@php
$formMode = $modalData['form_mode'] ?? 'create'; // 'create' | 'edit' | 'view'
$title = $modalData['title'] ?? 'Add Destination';
$uuid = $modalData['uuid'] ?? '';
$data = $modalData['data'] ?? null;
$countries = $modalData['countries'] ?? [];
$states = $modalData['states'] ?? [];

$isPrimary = is_null($data->parent_id ?? null) ? 1 : 0;


$isView = $formMode === 'view';
@endphp

<div class="modal fade master-modal" id="destination_modal" data-bs-backdrop="static" 
     data-bs-keyboard="false" tabindex="-1" aria-labelledby="destination_modal" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content master-modal-content">
      <div class="modal-header master-header">
        <div class="d-flex align-items-center gap-3">
          <div class="master-badge bg-primary-lt rounded fs-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-database"
              width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
              fill="none" stroke-linecap="round" stroke-linejoin="round">
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
          <form id="destination_form"
            action="{{ $formMode === 'edit' ? route('destinations.update', $data->id) : route('destinations.store') }}"
            method="POST" class="needs-validation" novalidate>
          
            @csrf
            @if($formMode === 'edit')
              @method('PUT')
            @else
              <input type="hidden" name="uuid" value="{{ $uuid }}">
            @endif
          
            <input type="hidden" id="form_mode" value="{{ $formMode }}">
            <div class="row g-2">
              <!-- Left Column -->
              <div class="col-md-12">
                <!-- Account Details -->
                <div class="master-form-section">
                  <div class="master-section-title "><i class="fa-solid fa-file-pen me-2 text-primary"></i>
                  General Details</div>
                  <div class="row g-2">
                    <!-- Godown Name -->
                    <div class="col-lg-3 ">
                      <label class="form-label required"><i class="fa-solid fa-warehouse text-secondary"></i> Godown Name</label>
                      <input type="text" name="name" id="name" class="form-control" placeholder="{{ $isView ? '' : 'Enter Godown Name' }}"
                        value="{{ old('name', $data->name ?? '') }}" {{ $isView ? 'readonly' : 'required' }}>
                    </div>

                    <!-- Contact Person Name -->
                    <div class="col-lg-3 col-sm-12 col-md-12">
                      <label class="form-label"><i class="fa-solid fa-person text-secondary"></i> Contact Person Name</label>
                      <input type="text" name="contact_person_name" id="contact_person_name" class="form-control"
                        placeholder="{{ $isView ? '' : 'Enter Contact Person Name' }}" value="{{ old('contact_person_name', $data->contact_person_name ?? '') }}"
                        {{ $isView ? 'readonly' : 'required' }}>
                    </div>

                    <!-- Email -->
                    <div class="col-lg-5 col-sm-12 col-md-12">
                      <label class="form-label"><i class="fa-solid fa-envelope text-secondary"></i> Email</label>
                      <input type="text" name="email" id="email" class="form-control" placeholder="{{ $isView ? '' : 'Enter Email Address' }}"
                        value="{{ old('email', $data->email ?? '') }}" {{ $isView ? 'readonly' : 'required' }}>
                    </div>
                    <!-- Distance -->
                    <div class="col-lg-1 col-sm-12 col-md-12">
                      <label class="form-label"><i class="fa-solid fa-route text-secondary"></i> Distance</label>
                      <input type="text" name="kms" id="kms" class="form-control" placeholder="{{ $isView ? '' : 'Enter Distance(in Km)' }}"
                        value="{{ old('kms', $data->kms ?? 0) }}" {{ $isView ? 'readonly' : 'required' }}>
                    </div>                                                            
                  </div>
                </div>
          
                <!-- Address Communication Details -->
                <div class="master-form-section">
                  <div class="master-section-title "><i class="fa-solid fa-location-dot text-primary"></i>Address Communication Details</div>
                  <div class="row g-2">
                    <!-- Countrie -->
                    <div class="col-lg-4 col-sm-12 col-md-12" id="primary_group_div">
                      <label class="master-form-label form-label required"><i class="fa-solid fa-earth-americas text-secondary"></i> Country</label>
                      <select name="country_id" id="country" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select Country...</option>
                        @foreach ($countries as $countrie)
                          <option value="{{ $countrie->id }}" {{ ($data->country_id ?? '') == $countrie->id ? 'selected' : '' }}>
                            {{ $countrie->name }}
                          </option>
                        @endforeach
                      </select>
                    </div>
                    <!-- State -->
                    <div class="col-lg-4 col-sm-12 col-md-12" id="primary_group_div">
                      <label class="form-label form-label required"><i class="fa-solid fa-map-location-dot text-secondary"></i> State</label>
                      <select name="state_id" id="state" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">State...</option>
                        @foreach ($states as $state)
                          <option value="{{ $state->id }}" {{ ($data->state_id ?? '') == $state->id ? 'selected' : '' }}>
                            {{ $state->name }}
                          </option>
                        @endforeach
                      </select>
                    </div>

                    <!-- City -->
                    <div class="col-lg-4 col-sm-12 col-md-12">
                      <label class="form-label"><i class="fa-solid fa-city text-secondary"></i> City</label>
                      <input type="text" name="city" id="city" class="form-control" placeholder="{{ $isView ? '' : 'Enter City' }}"
                        value="{{ old('city', $data->city ?? '') }}" {{ $isView ? 'readonly' : 'required' }}>
                    </div>
                    <!-- Address One -->
                    <div class="col-lg-6 col-sm-12 col-md-12">
                      <label class="form-label"><i class="fa-solid fa-city text-secondary"></i> Address One</label>
                      <input type="text" name="address_one" id="address_one" class="form-control" placeholder="{{ $isView ? '' : 'Enter Address One' }}"
                        value="{{ old('address_one', $data->address_one ?? '') }}" {{ $isView ? 'readonly' : 'required' }}>
                    </div>
                    <!-- Address Two -->
                    <div class="col-lg-6 col-sm-12 col-md-12">
                      <label class="form-label"><i class="fa-solid fa-city text-secondary"></i> Address Two</label>
                      <input type="text" name="address_two" id="address_two" class="form-control" placeholder="{{ $isView ? '' : 'Enter Address Two' }}"
                        value="{{ old('address_two', $data->address_two ?? '') }}" {{ $isView ? 'readonly' : 'required' }}>
                    </div>
                    <!-- District -->
                    <div class="col-lg-3 col-sm-12 col-md-12">
                      <label class="form-label"><i class="fa-solid fa-location-dot text-secondary"></i> District</label>
                      <input type="text" name="district" id="district" class="form-control" placeholder="{{ $isView ? '' : 'Enter District' }}"
                        value="{{ old('district', $data->district ?? '') }}" {{ $isView ? 'readonly' : 'required' }}>
                    </div>
                    <!-- Taluka -->
                    <div class="col-lg-2 col-sm-12 col-md-12">
                      <label class="form-label"><i class="fa-solid fa-location-dot text-secondary"></i> Taluka</label>
                      <input type="text" name="taluka" id="taluka" class="form-control" placeholder="{{ $isView ? '' : 'Enter Taluka' }}"
                        value="{{ old('taluka', $data->taluka ?? '') }}" {{ $isView ? 'readonly' : 'required' }}>
                    </div>
                    <!-- PIN -->
                    <div class="col-lg-1 col-sm-12 col-md-12">
                      <label for="postal_code" class="form-label"><i class="fa-solid fa-file-zipper text-secondary"></i> PIN                        
                        <span class="form-help" data-bs-toggle="popover" data-bs-placement="top" data-bs-html="true"
                          data-bs-content="<p>PIN Code must be 6 digits, numeric only.</p>">
                          ?
                        </span>
                    </label>
                      <input type="text" name="postal_code" id="postal_code" class="form-control" placeholder="{{ $isView ? '' : 'Enter Postal Code' }}"
                        value="{{ old('postal_code', $data->postal_code ?? '') }}" {{ $isView ? 'readonly' : 'required' }}>
                    </div>
                    <!-- Mobile -->
                    <div class="col-lg-3 col-sm-12 col-md-12">
                      <label class="form-label"><i class="fa-solid fa-mobile text-secondary"></i> Mobile</label>
                      <input type="text" name="mobile_number" id="mobile_number" class="form-control" placeholder="{{ $isView ? '' : '+91 0000 000 000' }}"
                        value="{{ old('mobile_number', $data->mobile_number ?? '') }}" {{ $isView ? 'readonly' : 'required' }}>
                    </div>
                    <!-- Phone -->
                    <div class="col-lg-3 col-sm-12 col-md-12">
                      <label class="form-label"><i class="fa-solid fa-phone text-secondary"></i> Phone</label>
                      <input type="text" name="phone_number" id="phone_number" class="form-control" placeholder="{{ $isView ? '' : 'Enter Phone Number' }}"
                        value="{{ old('phone_number', $data->phone_number ?? '') }}" {{ $isView ? 'readonly' : 'required' }}>
                    </div>
                  </div>
                </div>              
            </div>
          </div>

        <div class="modal-footer master-modal-footer">
          <button type="button" class="btn btn-link non-selectable waves-effect" data-bs-dismiss="modal">Close</button>

          @if(!$isView)
            <button type="submit" form="destination_form" class="btn btn-primary form-save-btn waves-effect">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-square-check"
                  width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                  fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                <path d="M9 12l2 2l4 -4" />
              </svg>
              @if ($formMode == 'edit')
                Update Destination
                @else
                  Save Destination
                @endif
            </button>
          @endif
        </div>
    </div>
  </div>
</form>
</div>
</div>

<script src="{{ asset('js/master/destination/modal.js') }}?v={{ hash_file('md5', public_path('js/master/destination/modal.js')) }}"></script>  
