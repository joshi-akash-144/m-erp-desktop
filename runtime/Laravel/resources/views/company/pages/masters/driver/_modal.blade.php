@php
$formMode = $modalData['form_mode'] ?? 'create'; // 'create' | 'edit' | 'view'
$title = $modalData['title'] ?? 'Add Driver';
$uuid = $modalData['uuid'] ?? '';
$data = $modalData['data'] ?? null;
$vehicles = $modalData['vehicles'] ?? [];
$accounts = $modalData['accounts'] ?? [];
$cheques = $modalData['cheques'] ?? [];
$accountGroups = $modalData['accountGroups'] ?? null;
$isView = $formMode === 'view';
@endphp

<div class="modal modal-blur fade" id="driver_modal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content master-modal-content">
      <div class="modal-header master-header">
        <div class="d-flex align-items-center gap-3">
          <div class="master-badge bg-primary-lt rounded fs-2">
            <i class="fa-solid fa-users text-primary"></i>
          </div>
          <div>
            <h5 class="modal-title mb-0 font-monospace">{{ $modalData['title'] }}</h5>
            <small class="badge bg-teal text-teal-fg">Master Data Management</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" tabindex="-1"></button>
      </div>

    <div class="modal-body p-4">
    <form id="driver_form"
      action="{{ $modalData['form_mode'] === 'edit' ? route('drivers.update', $modalData['data']->id) : route('drivers.store') }}"
      method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
      
      @csrf
      @if($modalData['form_mode'] === 'edit')
        @method('PUT')
      @else
        <input type="hidden" name="uuid" value="{{ $modalData['uuid'] }}">
      @endif
    
      <input type="hidden" id="form_mode" value="{{ $modalData['form_mode'] }}">      
      <input type="hidden" name="account_id" value="{{ old('account_id', $data->account_id ?? '') }}">
      <div class="row g-3">
        <!-- Left Column -->
        <div class="col-md-12">
          <!-- General Details -->
          <div class="master-form-section h-100">
            <div class="master-section-title">
              <i class="fa-solid fa-user text-primary"></i> &nbsp; General Details
            </div>
            <div class="row g-2">
              <div class="col-md-5">
                  <label class="form-label required"> <i class="fa-solid fa-user text-secondary"></i>&nbsp;Driver Name</label>                 
                  <input type="text" name="name" id="name" class="form-control" placeholder="Enter Driver Name" value="{{ old('name', $data?->account?->name ?? '') }}" {{ $isView ? 'readonly' : 'required' }}>
              </div>
              <div class="col-lg-3">
                  <label class="form-label required"><i class="fa-solid fa-user-group text-secondary"></i>&nbsp;Account Group</label>
                  <select name="account_group_id" id="account_group_id" class="form-select select2" {{ $isView ? 'readonly' : '' }}>
                  <option value="">Select Account Group...</option>
                  @foreach ($accountGroups as $accountGroup)
                      <option value="{{ $accountGroup->id }}" {{ ($data?->account?->account_group_id ?? '') == $accountGroup->id ? 'selected' : '' }}>
                          {{ $accountGroup->name }}
                      </option>
                  @endforeach
                  </select>
              </div>             
              <div class="col-md-2">
                  <label class="form-label required"><i class="fa-solid fa-rupee-sign text-secondary"></i>&nbsp; Op. Bal.<span class="small text-muted">(Rs.)</span></label>
                  <input type="text" name='opening_balance' id='opening_balance' class="form-control" placeholder="Enter OP. Balance"
                  value="{{old('opening_balance', $data?->account?->opening_balance ?? '0.00')}}" {{ $isView ? 'readonly' : '' }}>
              </div>
              <div class="col-md-2">
                  <label class="form-label required"><i class="fa-solid fa-receipt text-secondary"></i>&nbsp; Dr/Cr</label>
                  <select name="opening_type" id="opening_type" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                      <option value="">Select Opening Type</option>
                      <option value="D" {{ ($data?->account?->opening_type ?? 'D') == 'D' ? 'selected' : '' }}>DR</option>
                      <option value="C" {{ ($data?->account?->opening_type ?? 'C') == 'C' ? 'selected' : '' }}>CR</option>
                  </select>
              </div> 
              <div class="col-md-3">
                <label class="form-label"> <i class="fa-solid fa-indian-rupee-sign text-secondary"></i>&nbsp;Salary</label>
                <input type="number" name="salary" id="salary" class="form-control" placeholder="Enter Driver Salary" value="{{ $modalData['data']->salary ?? '' }}" {{ $modalData['form_mode'] === 'view' ? 'readonly' : '' }}>
              </div>                                  
              <div class="col-md-3">
                <label class="form-label"> <i class="fa-solid fa-hands-praying text-secondary"></i>&nbsp;Religion</label>
                <input type="text" name="religion" id="religion" class="form-control" placeholder="Enter Religion" value="{{ $modalData['data']->religion ?? '' }}" {{ $modalData['form_mode'] === 'view' ? 'readonly' : '' }}>
              </div>
              <div class="col-md-2">
                <label class="form-label"> <i class="fa-solid fa-ring text-secondary"></i>&nbsp;Marital Status</label>
                <select name="marital_status" id="marital_status" class="form-select select2" {{ $modalData['form_mode'] === 'view' ? 'disabled' : '' }}>
                    <option value="">--Select Marital Status--</option>
                    @foreach(config('constants.marital_status', []) as $key => $value)
                        <option value="{{ $key }}" {{ ($modalData['data']->marital_status ?? '') == $key ? 'selected' : '' }}>{{ $value }}</option>
                    @endforeach
                </select>
              </div>  
              {{-- <div class="col-md-2">
                <label class="form-label"> <i class="fa-solid fa-droplet text-secondary"></i>&nbsp;Blood Group</label>           
                <input type="text" name="blood_group" id="blood_group" class="form-control" placeholder="Enter Blood Group" value="{{ $modalData['data']->blood_group ?? '' }}" {{ $modalData['form_mode'] === 'view' ? 'readonly' : '' }}>
              </div> --}}         
            </div>             
          </div>
        </div>
        <!-- License Column -->
        <div class="col-md-12">
          <div class="master-form-section h-100">
            <div class="master-section-title">
              <i class="fa-solid fa-address-card text-primary"></i> &nbsp; License Details
            </div>
            <div class="row g-2">
              <div class="col-md-3">
                <label class="form-label"> <i class="fa-solid fa-list text-secondary"></i>&nbsp;License Category</label>
                <select name="license_category" id="license_category" class="form-select select2" {{ $isView ? 'disabled' : '' }}>
                    <option value="">--Select License Category--</option>
                    @foreach(config('constants.license_category', []) as $key => $value)
                        <option value="{{ $key }}" {{ ($data->license_category ?? '') == $key ? 'selected' : '' }}>{{ $value }}</option>
                    @endforeach
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label"> <i class="fa-solid fa-id-card-clip text-secondary"></i>&nbsp;License Number</label>
                <input type="text" name="license_number" id="license_number" class="form-control" placeholder="Enter License Number" value="{{ $data->license_number ?? '' }}" {{ $isView ? 'readonly' : '' }}>
              </div>
              <div class="col-md-3">
                <label class="form-label"> <i class="fa-regular fa-calendar-xmark text-secondary"></i>&nbsp;License Expiry Date(TR)</label>
                <input type="text" name="license_expiry_date_tr" id="license_expiry_date_tr" class="form-control js-flatpickr" placeholder="DD-MM-YYYY" value="{{ isset($data->license_expiry_date_tr) ? \Carbon\Carbon::parse($data->license_expiry_date_tr)->format('d-m-Y') : '' }}" {{ $isView ? 'readonly' : '' }}>
              </div>    
              <div class="col-md-3">
                <label class="form-label"> <i class="fa-regular fa-calendar-xmark text-secondary"></i>&nbsp;License Expiry Date(NT)</label>
                <input type="text" name="license_expiry_date_nt" id="license_expiry_date_nt" class="form-control js-flatpickr" placeholder="DD-MM-YYYY" value="{{ isset($data->license_expiry_date_nt) ? \Carbon\Carbon::parse($data->license_expiry_date_nt)->format('d-m-Y') : '' }}" {{ $isView ? 'readonly' : '' }}>
              </div>     
              <div class="col-md-3">
                  <label class="form-label"><i class="fa-solid fa-phone text-secondary"></i>&nbsp; Mobile</label>
                  <input type="text" name="mobile_number" id="mobile_number" class="form-control" placeholder="Enter Mobile Number" value="{{ old('mobile_number', $data?->account?->mobile_number ?? '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>              
              <div class="col-md-2">
                  <label class="form-label"><i class="fa-solid fa-map-pin text-secondary"></i>&nbsp; Postal Code</label>
                  <input type="text" name="postal_code" id="postal_code" class="form-control" placeholder="Enter Postal Code" value="{{ old('postal_code', $data?->account?->postal_code ?? '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>             
              <div class="col-md-2">
                  <label class="form-label"><i class="fa-solid fa-file-zipper text-secondary"></i>&nbsp; PAN</label>
                  <input type="text" name='pan' id='pan' class="form-control" placeholder="{{ $isView ? '' : 'Enter Pan' }}"
                  value="{{old('pan', $data?->account?->taxDetail?->pan ?? '')}}" {{ $isView ? 'readonly' : '' }}>
              </div>
              <div class="col-md-5">
                  <label class="form-label"><i class="fa-solid fa-location-dot text-secondary"></i>&nbsp; Address</label>
                  <input type="text" name="address_one" id="address_one" class="form-control" placeholder="Enter Address" value="{{ old('address_one', $data?->account?->address_one ?? '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
            </div>
          </div>
        </div>   

        <!-- Middle Column -->
        <div class="col-md-12">
          <div class="master-form-section h-100">
            <div class="master-section-title">
              <i class="fa-solid fa-address-card text-primary"></i> &nbsp; Additional Details
            </div>

            <div class="row g-2">
              <div class="col-md-3">
                <label class="form-label"> <i class="fa-solid fa-truck text-secondary"></i>&nbsp;Vehicle</label>
                <select name="vehicle_id" id="vehicle_id" class="form-select select2" {{ $isView ? 'readonly' : '' }}>
                  <option value="">Select Vehicle...</option>
                  @foreach($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}" {{ ($data->vehicle_id ?? '') == $vehicle->id ? 'selected' : '' }}>{{ $vehicle->name }}</option>
                  @endforeach
                </select> 
              </div>             
              <div class="col-md-2">
                <label class="form-label"> <i class="fa-solid fa-calendar-days text-secondary"></i>&nbsp;Date of Joining</label>
                <input type="text" name="date_of_joining" id="date_of_joining" class="form-control js-flatpickr" placeholder="DD-MM-YYYY" value="{{ isset($modalData['data']->date_of_joining) ? \Carbon\Carbon::parse($modalData['data']->date_of_joining)->format('d-m-Y') : '' }}" {{ $modalData['form_mode'] === 'view' ? 'readonly' : '' }}>
              </div>
              <div class="col-md-4">
                <label class="form-label"> <i class="fa-solid fa-building text-secondary"></i>&nbsp;License Issuing Authority</label>
                <input type="text" name="license_issuing_authority" id="license_issuing_authority" class="form-control" placeholder="Enter License Issuing Authority" value="{{ $modalData['data']->license_issuing_authority ?? '' }}" {{ $modalData['form_mode'] === 'view' ? 'readonly' : '' }}>
              </div>
              <div class="col-md-3">
                <label class="form-label"> <i class="fa-solid fa-id-badge text-secondary"></i>&nbsp;Aadhaar Number</label>
                <input type="text" name="adhara_number" id="adhara_number" class="form-control" placeholder="Enter Aadhaar Number" value="{{ $modalData['data']->adhara_number ?? '' }}" {{ $modalData['form_mode'] === 'view' ? 'readonly' : '' }}>
              </div>         
               <div class="col-md-2">
                <label class="form-label"> <i class="fa-solid fa-graduation-cap text-secondary"></i>&nbsp;Qualification</label>
                <input type="text" name="qualification" id="qualification" class="form-control" placeholder="Enter Qualification" value="{{ $modalData['data']->qualification ?? '' }}" {{ $modalData['form_mode'] === 'view' ? 'readonly' : '' }}>
              </div>             
               <div class="col-md-6">
                <label class="form-label"> <i class="fa-solid fa-comment-dots text-secondary"></i> &nbsp; Remarks</label>
                <input type="text" class="form-control" name="remarks" id="remarks" placeholder="Enter Remarks" value="{{ $modalData['data']->remarks ?? '' }}" {{ $modalData['form_mode'] === 'view' ? 'readonly' : '' }}>
              </div>  
            </div>
          </div>
        </div>    
        <div class="col-md-12">
          <div class="master-form-section h-100">
            <div class="master-section-title">
              <i class="fa-solid fa-address-card text-primary"></i> &nbsp; Bank Info
            </div>
            <div class="row g-2">
              <!-- Bank Name -->
                  <div class="col-lg-6">
                      <label class="form-label"><i class="fa-solid fa-user text-secondary"></i>&nbsp; Bank Name</label>
                      <input class="form-control" type='text' name='bank_name' id='bank_name' placeholder=" {{ $isView ? '' : 'Enter Bank Name' }}"
                      value="{{old('bank_name', $data?->account?->bankDetail?->bank_name ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                  </div>
                  <!-- Bank Branch Name -->
                  <div class="col-lg-6">
                      <label class="form-label"><i class="fa-solid fa-map-pin text-secondary"></i>&nbsp; Branch Name</label>
                      <input type="text" name='bank_branch_name' id='bank_branch_name' class="form-control" placeholder="{{ $isView ? '' : 'Enter Branch Name' }}"
                      value="{{old('bank_branch_name', $data?->account?->bankDetail?->bank_branch_name ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                  </div>
                  <!-- Bank A/C Number -->
                  <div class="col-lg-6">
                      <label class="form-label"><i class="fa-solid fa-building-columns text-secondary"></i>&nbsp; Account No. </label>
                      <input class="form-control" type='text' name='bank_account_number' id='bank_account_number' placeholder="{{ $isView ? '' : 'Enter bank account number' }}"
                      value="{{old('bank_account_number', $data?->account?->bankDetail?->bank_account_number ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                  </div>
                  <!-- Bank IFSC -->
                  <div class="col-lg-6">
                      <label class="form-label"><i class="fa-solid fa-barcode text-secondary"></i>&nbsp; IFSC Code</label>
                      <input type="text" name='bank_ifsc' maxlength="11" id='bank_ifsc' class="form-control" placeholder="{{ $isView ? '' : 'Enter Bank Ifsc' }}"
                      value="{{old('bank_ifsc', $data?->account?->bankDetail?->bank_ifsc ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                  </div>                  
                  <!-- Cheque -->
                  {{-- <div class="col-lg-4">
                      <label class="form-label"><i class="fa-solid fa-barcode text-secondary"></i>&nbsp; Cheque</label>
                      <select name="cheque_id" id="cheque_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                          <option value="">Select Cheque...</option>
                          @foreach ($cheques as $cheque)
                              <option value="{{ $cheque->id }}" {{ ($data?->account?->bankDetail?->cheque_id ?? '') == $cheque->id ? 'selected' : '' }}>
                                  {{ $cheque->formate_name }}
                              </option>
                          @endforeach
                      </select>
                  </div> --}}
            </div>
          </div>
        </div>      
      </div>      
    </div>

      <div class="modal-footer master-modal-footer">
        <button type="button" class="btn btn-link non-selectable waves-effect" data-bs-dismiss="modal" tabindex="-1">Close</button>

        @if($modalData['form_mode'] !== 'view')
          <button type="submit" form="driver_form" class="btn btn-primary form-save-btn waves-effect">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-square-check"
                 width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                 fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
              <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
              <path d="M9 12l2 2l4 -4" />
            </svg>
            @if ($modalData['form_mode'] == 'edit')
              Update Driver
            @else
              Save Driver
            @endif
          </button>
        @endif
      </div>
    </div>
  </div>
</form>
</div>
<script src="{{ asset('js/master/driver/modal.js') }}?v={{ hash_file('md5', public_path('js/master/driver/modal.js')) }}"></script>  
