@php
$formMode = $modalData['form_mode'] ?? 'create'; // 'create' | 'edit' | 'view'
$title = $modalData['title'] ?? 'Add Vehicle';
$uuid = $modalData['uuid'] ?? '';
$data = $modalData['data'] ?? null;
$vehicleOwners = $modalData['vehicleOwners'] ?? [];
$drivers = $modalData['drivers'] ?? [];

$isView = $formMode === 'view';
@endphp

<div class="modal fade master-modal" id="vehicle_modal" data-bs-backdrop="static" 
     data-bs-keyboard="false" tabindex="-1" aria-labelledby="vehicle_modal" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content master-modal-content">
      <div class="modal-header master-header">
        <div class="d-flex align-items-center gap-3">
          <div class="master-badge bg-primary-lt rounded fs-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-truck"
              width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
              fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
              <circle cx="7" cy="17" r="2" />
              <circle cx="17" cy="17" r="2" />
              <path d="M5 17h-2v-11a1 1 0 0 1 1 -1h9v12m-4 0h6m4 0h2v-6h-8m0 -5h5l3 5" />
            </svg>
          </div>
          <div>
            <h5 class="modal-title mb-0 font-monospace">{{ $title }}</h5>
            <small class="badge bg-teal text-teal-fg">Master Data Management</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

    <div class="modal-body p-4">
    <form id="vehicle_form"
      action="{{ $formMode === 'edit' ? route('vehicles.update', $data->id) : route('vehicles.store') }}"
      method="POST" class="needs-validation" novalidate>
    
      @csrf
      @if($formMode === 'edit')
        @method('PUT')
      @else
        <input type="hidden" name="uuid" value="{{ $uuid }}">
      @endif
    
      <input type="hidden" id="form_mode" value="{{ $formMode }}">      
      <input type="hidden" name="account_id" value="{{ old('account_id', $data->account_id ?? 1) }}">
      
      <div class="row g-3">
        <!-- Left Column -->
        <div class="col-md-6">
          <!-- General Details -->
          <div class="master-form-section">
            <div class="master-section-title">
              <i class="fa-solid fa-truck text-primary"></i> &nbsp; General Details
            </div>
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label required"> <i class="fa-solid fa-hashtag text-secondary"></i>&nbsp;Vehicle Number</label>
                <input type="text" name="name" id="name" class="form-control text-uppercase txtRegNo" placeholder="Enter Vehicle Number" value="{{ old('name', $data->name ?? '') }}" {{ $isView ? 'readonly' : '' }} required>
                <div class="invalid-feedback">Please enter Vehicle Number.</div>
              </div>

              <div class="col-md-6">
                <label class="form-label"> <i class="fa-solid fa-user-tie text-secondary"></i>&nbsp;Vehicle Owner Name</label>
                <select name="vehicle_owner_id" id="vehicle_owner_id" class="form-select select2" {{ $isView ? 'disabled' : '' }}>
                  <option value="">Select Vehicle Owner...</option>
                  @foreach($vehicleOwners as $owner)
                    <option value="{{ $owner->id }}" {{ ($data->vehicle_owner_id ?? '') == $owner->id ? 'selected' : '' }}>{{ $owner->name }}</option>
                  @endforeach
                </select> 
              </div>
              {{-- <div class="col-md-6">
                <label class="form-label"> <i class="fa-solid fa-id-card-clip text-secondary"></i>&nbsp;Driver Name</label>
                <select name="driver_id" id="driver_id" class="form-select select2" {{ $isView ? 'disabled' : '' }}>
                  <option value="">Select Driver...</option>
                  @foreach($drivers as $driver)
                    <option value="{{ $driver->id }}" {{ ($data->driver_id ?? '') == $driver->id ? 'selected' : '' }}>{{ $driver->account->name }}</option>
                  @endforeach
                </select>
              </div> --}}

              <div class="col-md-6">
                <label class="form-label"> <i class="fa-solid fa-comment-dots text-secondary"></i>&nbsp;Remarks:</label>
                <input type="text" name="remarks" id="remarks" class="form-control" placeholder="Enter Remark"
                value="{{ old('remarks', $data->remarks ?? '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
            </div>
          </div>             
          
          <!-- Technical Details -->
          <div class="master-form-section mt-3">
            <div class="master-section-title">
              <i class="fa-solid fa-cogs text-primary"></i> &nbsp; Technical Details
            </div>
            <div class="row g-2">

              <div class="col-md-6">
                <label class="form-label"> <i class="fa-solid fa-car text-secondary"></i>&nbsp;Vehicle Model</label>
                <input type="text" name="model" id="model" class="form-control" placeholder="Vehicle Model"
                  value="{{ old('model', $data->model ?? '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
              <div class="col-md-6">
                <label class="form-label"> <i class="fa-regular fa-calendar-days text-secondary"></i>&nbsp;Manufacturing Year</label>
                <select name="mfg_year" id="mfg_year" class="form-select select2" {{ $isView ? 'disabled' : '' }}>
                  <option value="">Select Year...</option>
                  @for($i = date('Y'); $i >= 1960; $i--)
                    <option value="{{ $i }}" {{ ($data->mfg_year ?? '') == $i ? 'selected' : '' }}>{{ $i }}</option>
                  @endfor
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label"> <i class="fa-solid fa-industry text-secondary"></i>&nbsp;Manufacturer</label>
                <input type="text" name="manufacturer" id="manufacturer" class="form-control" placeholder="Manufacturer"
                  value="{{ old('manufacturer', $data->manufacturer ?? '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
              <div class="col-md-6">
                <label class="form-label"> <i class="fa-solid fa-barcode text-secondary"></i>&nbsp;Chassis No.</label>
                <input type="text" name="chassis_no" id="chassis_no" class="form-control" placeholder="Chassis No."
                  value="{{ old('chassis_no', $data->chassis_no ?? '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
              <div class="col-md-6">
                <label class="form-label"> <i class="fa-solid fa-microchip text-secondary"></i>&nbsp;Engine No.</label>
                <input type="text" name="engine_no" id="engine_no" class="form-control" placeholder="Engine No."
                  value="{{ old('engine_no', $data->engine_no ?? '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
              <div class="col-md-6">
                <label class="form-label"> <i class="fa-solid fa-gas-pump text-secondary"></i>&nbsp;Fuel Type</label>
                @php $fuelType = config('constants.fuel_type'); @endphp
                <select name="fuel_type" id="fuel_type" class="form-select select2" {{ $isView ? 'disabled' : '' }}>
                  <option value="">Select Fuel...</option>
                  @foreach ($fuelType as $val => $label)
                    <option value="{{ $val }}" {{ ($data->fuel_type ?? 0) == $val ? 'selected' : '' }}>{{ $label }}</option>
                  @endforeach                  
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label"> <i class="fa-solid fa-fill-drip text-secondary"></i>&nbsp;Fuel Tank Capacity</label>
                <input type="text" name="fuel_tank_capacity" id="fuel_tank_capacity" class="form-control" placeholder="Capacity"
                  value="{{ old('fuel_tank_capacity', $data->fuel_tank_capacity ?? '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
              <div class="col-md-6">
                <label class="form-label"> <i class="fa-solid fa-bolt text-secondary"></i>&nbsp;Vehicle Power cc</label>
                <input type="text" name="power_cc" id="power_cc" class="form-control" placeholder="Power CC"
                  value="{{ old('power_cc', $data->power_cc ?? '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
            </div>
          </div>
          
          <!-- Weight Details -->
          <div class="master-form-section mt-3">
            <div class="master-section-title">
              <i class="fa-solid fa-weight-hanging text-primary"></i> &nbsp; Weight Details
            </div>
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label"> Vehicle Gross Weight</label>
                <input type="number" step="0.01" name="gross_weight" id="gross_weight" class="form-control" placeholder="0.00"
                  value="{{ old('gross_weight', $data->gross_weight ?? '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
              <div class="col-md-4">
                <label class="form-label"> Vehicle Unladen Weight</label>
                <input type="number" step="0.01" name="unladen_weight" id="unladen_weight" class="form-control" placeholder="0.00"
                  value="{{ old('unladen_weight', $data->unladen_weight ?? '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
              <div class="col-md-4">
                <label class="form-label"> Vehicle Weight Capacity(Kgs)</label>
                <input type="number" step="0.01" name="weight_capacity" id="weight_capacity" class="form-control" placeholder="0.00"
                  value="{{ old('weight_capacity', $data->weight_capacity ?? '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Right Column -->
        <div class="col-md-6">
          <div class="master-form-section h-100">
            <div class="master-section-title">
              <i class="fa-solid fa-id-card text-primary"></i> &nbsp; Licenses & Permits
            </div>
            <div class="row g-2">
              {{-- <div class="col-md-4">
                <label class="form-label"> <i class="fa-regular fa-id-badge text-secondary"></i>&nbsp;License Number</label>
                <input type="text" name="license_number" id="license_number" class="form-control" placeholder="License No."
                  value="{{ old('license_number', $data->license_number ?? '') }}" {{ $isView ? 'readonly' : '' }}>
              </div> --}}
               <div class="col-md-6">
                <label class="form-label"> <i class="fa-solid fa-building text-secondary"></i>&nbsp;Insurance Company Name</label>
                <input type="text" name="insurance_company_name" id="insurance_company_name" class="form-control" placeholder="Company Name"
                  value="{{ old('insurance_company_name', $data->insurance_company_name ?? '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
              <div class="col-md-6">
                <label class="form-label"> <i class="fa-solid fa-file-shield text-secondary"></i>&nbsp;Insurance Policy No</label>
                <input type="text" name="insurance_policy_no" id="insurance_policy_no" class="form-control" placeholder="Policy No"
                  value="{{ old('insurance_policy_no', $data->insurance_policy_no ?? '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
              <div class="col-md-3">
                <label class="form-label"> <i class="fa-solid fa-clock-rotate-left text-secondary"></i>&nbsp;Renewal Date</label>
                <input type="text" name="renewal_date" id="renewal_date" class="form-control renewal_date" placeholder="DD-MM-YYYY"
                  value="{{ old('renewal_date', isset($data->renewal_date) && $data->renewal_date ? \Carbon\Carbon::parse($data->renewal_date)->format('d-m-Y') : '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
               <div class="col-md-3">
                <label class="form-label"> <i class="fa-solid fa-file-contract text-secondary"></i>&nbsp;National Permit Due Date</label>
                <input type="text" name="national_permit_due_date" id="national_permit_due_date" class="form-control npd_date" placeholder="DD-MM-YYYY"
                  value="{{ old('national_permit_due_date', isset($data->national_permit_due_date) && $data->national_permit_due_date ? \Carbon\Carbon::parse($data->national_permit_due_date)->format('d-m-Y') : '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
              <div class="col-md-3">
                <label class="form-label"> <i class="fa-solid fa-heart-pulse text-secondary"></i>&nbsp;Fitness Due Date</label>
                <input type="text" name="fitness_due_date" id="fitness_due_date" class="form-control fitness_due_date" placeholder="DD-MM-YYYY"
                  value="{{ old('fitness_due_date', isset($data->fitness_due_date) && $data->fitness_due_date ? \Carbon\Carbon::parse($data->fitness_due_date)->format('d-m-Y') : '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
              {{-- <div class="col-md-6">
                <label class="form-label"> <i class="fa-solid fa-building text-secondary"></i>&nbsp;Insurance Company Name</label>
                <input type="text" name="insurance_company_name" id="insurance_company_name" class="form-control" placeholder="Company Name"
                  value="{{ old('insurance_company_name', $data->insurance_company_name ?? '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
              <div class="col-md-6">
                <label class="form-label"> <i class="fa-solid fa-file-shield text-secondary"></i>&nbsp;Insurance Policy No</label>
                <input type="text" name="insurance_policy_no" id="insurance_policy_no" class="form-control" placeholder="Policy No"
                  value="{{ old('insurance_policy_no', $data->insurance_policy_no ?? '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>              --}}
                            
              <div class="col-md-3">
                <label class="form-label"> <i class="fa-solid fa-shield-halved text-secondary"></i>&nbsp;Policy Due Date</label>
                <input type="text" name="policy_due_date" id="policy_due_date" class="form-control pd_date" placeholder="DD-MM-YYYY"
                  value="{{ old('policy_due_date', isset($data->policy_due_date) && $data->policy_due_date ? \Carbon\Carbon::parse($data->policy_due_date)->format('d-m-Y') : '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
              <div class="col-md-3">
                <label class="form-label"> <i class="fa-solid fa-calendar-check text-secondary"></i>&nbsp;Passing Due Date</label>
                <input type="text" name="passing_due_date" id="passing_due_date" class="form-control pd_date" placeholder="DD-MM-YYYY"
                  value="{{ old('passing_due_date', isset($data->passing_due_date) && $data->passing_due_date ? \Carbon\Carbon::parse($data->passing_due_date)->format('d-m-Y') : '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
              <div class="col-md-3">
                <label class="form-label"> <i class="fa-solid fa-file-invoice-dollar text-secondary"></i>&nbsp;Tax Due Date</label>
                <input type="text" name="tax_due_date" id="tax_due_date" class="form-control tax_due_date" placeholder="DD-MM-YYYY"
                  value="{{ old('tax_due_date', isset($data->tax_due_date) && $data->tax_due_date ? \Carbon\Carbon::parse($data->tax_due_date)->format('d-m-Y') : '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
              <div class="col-md-3">
                <label class="form-label"> <i class="fa-solid fa-stamp text-secondary"></i>&nbsp;Permit Due Date</label>
                <input type="text" name="permit_due_date" id="permit_due_date" class="form-control pd_date" placeholder="DD-MM-YYYY"
                  value="{{ old('permit_due_date', isset($data->permit_due_date) && $data->permit_due_date ? \Carbon\Carbon::parse($data->permit_due_date)->format('d-m-Y') : '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
              <div class="col-md-3">
                <label class="form-label"> <i class="fa-solid fa-smog text-secondary"></i>&nbsp;PUC No.</label>
                <input type="text" name="puc_no" id="puc_no" class="form-control" placeholder="PUC Number"
                  value="{{ old('puc_no', $data->puc_no ?? '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
              <div class="col-md-3">    
                <label class="form-label"> <i class="fa-regular fa-calendar-xmark text-secondary"></i>&nbsp;PUC Due Date</label>
                <input type="text" name="puc_due_date" id="puc_due_date" class="form-control puc_due_date" placeholder="DD-MM-YYYY"
                  value="{{ old('puc_due_date', isset($data->puc_due_date) && $data->puc_due_date ? \Carbon\Carbon::parse($data->puc_due_date)->format('d-m-Y') : '') }}" {{ $isView ? 'readonly' : '' }}>
              </div>
            </div>
          </div>
        </div>    
      </div>
    </div>

      <div class="modal-footer master-modal-footer">
        <button type="button" class="btn btn-link non-selectable waves-effect" data-bs-dismiss="modal" tabindex="-1">Close</button>

        @if(!$isView)
          <button type="submit" form="vehicle_form" class="btn btn-primary form-save-btn waves-effect">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-square-check"
                 width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                 fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
              <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
              <path d="M9 12l2 2l4 -4" />
            </svg>
            @if ($formMode == 'edit')
              Update Vehicle
            @else
              Save Vehicle
            @endif
          </button>
        @endif
      </div>
    </div>
  </div>
</form>
</div>
<script src="{{ asset('js/master/vehicle/modal.js') }}?v={{ hash_file('md5', public_path('js/master/vehicle/modal.js')) }}"></script>  
