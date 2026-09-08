@php
  $formMode = $modalData['form_mode'] ?? 'create'; // 'create' | 'edit' | 'view'
  $title = $modalData['title'] ?? 'Add Transporter';
  $uuid = $modalData['uuid'] ?? '';
  $data = $modalData['data'] ?? null;
  $states = $modalData['states'] ?? collect();
  $isView = $formMode === 'view';
@endphp

<div class="modal fade master-modal" id="transporter_modal" tabindex="-1" data-bs-backdrop="static"
  data-bs-keyboard="false" aria-labelledby="transporter_modal" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header master-header">
        <div class="d-flex align-items-center gap-3">
          <div class="master-badge bg-primary-lt rounded fs-2">
            <i class="fa-solid fa-truck"></i>
          </div>
          <div>
            <h5 class="modal-title mb-0 font-monospace">{{ $title }}</h5>
            <small class="badge bg-teal text-teal-fg">Master Data Management</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <form id="transporter_form"
          action="{{ $formMode === 'edit' ? route('transporters.update', $data->id) : route('transporters.store') }}"
          method="POST" class="needs-validation" novalidate>

          @csrf
          @if($formMode === 'edit')
            @method('PUT')
          @else
            <input type="hidden" name="uuid" value="{{ $uuid }}">
          @endif

          <input type="hidden" id="form_mode" value="{{ $formMode }}">

          <div class="row g-3">
            <!-- General Details -->
            <div class="col-md-12">
              <div class="master-form-section">
                <div class="master-section-title"><i class="fa-solid fa-circle-info me-2 text-primary"></i>General Details</div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label required">Name</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="Transporter Name" value="{{ old('name', $data->name ?? '') }}" {{ $isView ? 'readonly' : 'required' }}>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">GSTIN</label>
                    <input type="text" name="gstin" id="gstin" class="form-control" placeholder="GSTIN" value="{{ old('gstin', $data->gstin ?? '') }}" {{ $isView ? 'readonly' : '' }}>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">IT PAN</label>
                    <input type="text" name="pan_no" id="pan_no" class="form-control" placeholder="PAN No" value="{{ old('pan_no', $data->pan_no ?? '') }}" {{ $isView ? 'readonly' : '' }}>
                  </div>
                </div>
              </div>
            </div>

            <!-- Contact Details -->
            <div class="col-md-12">
              <div class="master-form-section">
                <div class="master-section-title"><i class="fa-solid fa-phone me-2 text-primary"></i>Contact Details</div>
                <div class="row g-3">
                  <div class="col-md-3">
                    <label class="form-label">Contact Person</label>
                    <input type="text" name="contact_person" id="contact_person" class="form-control" placeholder="Contact Person" value="{{ old('contact_person', $data->contact_person ?? '') }}" {{ $isView ? 'readonly' : '' }}>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Email</label>
                    <input type="text" name="email" id="email" class="form-control" placeholder="Email" value="{{ old('email', $data->email ?? '') }}" {{ $isView ? 'readonly' : '' }}>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Mobile No</label>
                    <input type="text" name="mobile" id="mobile" class="form-control" placeholder="Mobile No" value="{{ old('mobile', $data->mobile ?? '') }}" {{ $isView ? 'readonly' : '' }}>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Phone No</label>
                    <input type="text" name="phone" id="phone" class="form-control" placeholder="Phone No" value="{{ old('phone', $data->phone ?? '') }}" {{ $isView ? 'readonly' : '' }}>
                  </div>
                </div>
              </div>
            </div>

            <!-- Address Details -->
            <div class="col-md-12">
              <div class="master-form-section">
                <div class="master-section-title"><i class="fa-solid fa-location-dot me-2 text-primary"></i>Address Details</div>
                <div class="row g-3">
                  <div class="col-md-5">
                    <label class="form-label">Address Line One</label>
                    <input type="text" name="address_line1" id="address_line1" class="form-control" placeholder="Address Line 1" value="{{ old('address_line1', $data->address_line1 ?? '') }}" {{ $isView ? 'readonly' : '' }}>
                  </div>
                  <div class="col-md-5">
                    <label class="form-label">Address Line Two</label>
                    <input type="text" name="address_line2" id="address_line2" class="form-control" placeholder="Address Line 2" value="{{ old('address_line2', $data->address_line2 ?? '') }}" {{ $isView ? 'readonly' : '' }}>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">City</label>
                    <input type="text" name="city" id="city" class="form-control" placeholder="City" value="{{ old('city', $data->city ?? '') }}" {{ $isView ? 'readonly' : '' }}>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">State</label>
                    <select name="state" id="state" class="form-select select2" {{ $isView ? 'disabled' : '' }}>
                      <option value="">-- Select State --</option>
                      @foreach ($states as $state)
                        <option value="{{ $state->name }}" {{ (old('state', $data->state ?? '') == $state->name) ? 'selected' : '' }}>{{ $state->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">PIN Code</label>
                    <input type="text" name="postal_code" id="postal_code" class="form-control" placeholder="PIN Code" value="{{ old('postal_code', $data->postal_code ?? '') }}" {{ $isView ? 'readonly' : '' }}>
                  </div>                  
                </div>
              </div>
            </div>
           <!-- Bank Details -->
            <div class="col-md-12">
              <div class="master-form-section">
                <div class="master-section-title"><i class="fa-solid fa-building-columns me-2 text-primary"></i>Bank Details</div>
                <div class="row g-3">                  
                  <div class="col-md-4">
                    <label class="form-label">Bank Name</label>
                    <input type="text" name="bank_name" id="bank_name" class="form-control" placeholder="Bank Name" value="{{ old('bank_name', $data->bank_name ?? '') }}" {{ $isView ? 'readonly' : '' }}>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Account Number</label>
                    <input type="text" name="bank_account_number" id="bank_account_number" class="form-control" placeholder="Account Number" value="{{ old('bank_account_number', $data->bank_account_number ?? '') }}" {{ $isView ? 'readonly' : '' }}>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">IFSC Code</label>
                    <input type="text" name="bank_ifsc" id="bank_ifsc" class="form-control" placeholder="IFSC Code" value="{{ old('bank_ifsc', $data->bank_ifsc ?? '') }}" {{ $isView ? 'readonly' : '' }}>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-link non-selectable waves-effect"
              data-bs-dismiss="modal">Close</button>
            @if(!$isView)
              <button type="submit" form="transporter_form" class="btn btn-primary form-save-btn waves-effect">
                <svg xmlns="http://www.w3.org/2000/svg"
                  class="icon icon-tabler icons-tabler-outline icon-tabler-square-check" width="24" height="24"
                  viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                  stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                  <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                  <path d="M9 12l2 2l4 -4" />
                </svg>
                @if ($formMode == 'edit')
                  Update Transporter
                @else
                  Save Transporter
                @endif
              </button>
            @endif
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script
  src="{{ asset('js/master/transporter/modal.js') }}?v={{ hash_file('md5', public_path('js/master/transporter/modal.js')) }}"></script>