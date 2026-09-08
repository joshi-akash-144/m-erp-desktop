@php
$formMode = $modalData['form_mode'] ?? 'create'; // 'create' | 'edit' | 'view'
$title = $modalData['title'] ?? 'Add Sale Type';
$uuid = $modalData['uuid'] ?? '';
$data = $modalData['data'] ?? null;
$accounts = $modalData['accounts'] ?? null;
$isView = $formMode === 'view';
$gstSectionClass = 'd-none';
$cgstSectionClass = $sgstSectionClass = $igstSectionClass = '';
if ($formMode != 'create') {
  $taxable = (isset($data->taxation_type) && $data->taxation_type === 'taxable') ?? null;
  $region = $data->region ?? null;
  if (!$taxable) {
    $gstSectionClass = 'd-none';
    $cgstSectionClass = 'd-none';
    $sgstSectionClass = 'd-none';
    $igstSectionClass = 'd-none';
  } else {
    $gstSectionClass = '';
    if ($region === 'interstate') {
      $cgstSectionClass = 'd-none';
      $sgstSectionClass = 'd-none';
    } elseif ($region === 'local') {
      $igstSectionClass = 'd-none';
    }
  }
}

@endphp

<div class="modal fade master-modal" id="sale_type_modal" data-bs-backdrop="static" 
     data-bs-keyboard="false" tabindex="-1" aria-labelledby="sale_type_modal" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header master-header">
        <div class="d-flex align-items-center gap-3">
          <div class="master-badge bg-primary-lt rounded fs-2">
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
        <form id="sale_type_form"
              action="{{ $formMode === 'edit' ? route('sale-types.update', $data->id) : route('sale-types.store') }}"
              method="POST"
              class="needs-validation"
              novalidate>

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
          <!-- Sale Type Details -->
          <div class="master-form-section">
            <div class="master-section-title "><i class="fa-solid fa-bag-shopping me-2 text-primary"></i>Sale Type Details</div>
              <div class="row g-2">
                  <!-- Sale Type Name -->
                  <div class="col-lg-4 col-sm-3 col-md-12">
                      <label class="form-label required"><i class="fa-solid fa-user text-secondary"></i>Name</label>
                      <input type="text" name="name" id="name" class="form-control"
                          placeholder="{{ $isView ? '' : 'Enter sale type name' }}"
                          value="{{ old('name', $data->name ?? '') }}"
                          {{ $isView ? 'readonly' : 'required' }}>
                  </div>
                  <!-- Specific Accounts -->
                  <div class="col-lg-4" id="primary_group_div">
                      <label class="form-label required"><i class="fa-solid fa-boxes-stacked text-secondary"></i>&nbsp; Specific Account</label>
                      <select name="account_id" id="account_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                          <option value="">Select Specific Account...</option>
                          @foreach ($accounts as $account)
                          <option value="{{ $account->id }}" {{ ($data->account_id ?? '') == $account->id ? 'selected' : '' }}>
                              {{ $account->name }}
                          </option>
                          @endforeach
                      </select>
                  </div>                                    
              </div>
          </div>                        
      </div>
      <div class="col-md-12">
          <!-- Taxation Type Detail -->
          <div class="master-form-section">
            <div class="master-section-title "><i class="fa-solid fa-percent me-2 text-primary"></i>Taxation Type Detail</div>
            <div class="row g-2">  
                <!-- Taxation Type -->
                  @php
$taxationType = config('constants.sale_taxation_types');
                  @endphp
                <div class="col-lg-4" id="primary_group_div">
                    <label class="form-label required"><i class="fa-solid fa-percent text-secondary"></i>&nbsp; Taxation Type</label>
                    <select name="taxation_type" id="taxation_type" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select Taxation Type...</option>
                          @foreach ($taxationType as $groupTypeId => $groupType)
                            <option value="{{ $groupTypeId }}" {{ ($data->taxation_type ?? '') == $groupTypeId ? 'selected' : '' }}>
                              {{ $groupType }}
                            </option>
                          @endforeach
                    </select>
                </div>
                <!-- Region -->
                  @php
$regions = config('constants.sale_regions');
                  @endphp
                <div class="col-lg-4">
                    <label class="form-label required"><i class="fa-solid fa-earth-europe text-secondary"></i>&nbsp; Region</label>
                    <select name="region" id="region" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select Region...</option>
                          @foreach ($regions as $groupTypeId => $groupType)
                            <option value="{{ $groupTypeId }}" {{ ($data->region ?? '') == $groupTypeId ? 'selected' : '' }}>
                              {{ $groupType }}
                            </option>
                          @endforeach
                    </select>
                </div>    
                <!--  Transaction_type -->
                <div class="col-lg-4 col-sm-12 col-md-12">
                  @php $transaction = config('constants.sale_transaction_types'); @endphp
                  <label class="form-label required"><i class="fa-solid fa-right-left text-secondary"></i> Transaction Type</label>
                  <select name="transaction_type" id="transaction_type" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                    <option value="">Select transaction type...</option>
                    @foreach ($transaction as $groupTypeId => $groupType)
                      <option value="{{ $groupTypeId }}" {{ ($data->transaction_type ?? '') == $groupTypeId ? 'selected' : '' }}>
                        {{ $groupType }}
                      </option>
                    @endforeach
                  </select>
              </div>                                          
            </div>
          </div>                        
      </div>
      <div class="col-md-12">
          <!-- GST Detail -->
          <div class="master-form-section {{ $gstSectionClass }}" id="gst_detail_section">
            <div class="master-section-title "><i class="fa-solid fa-percent me-2 text-primary"></i>GST Detail</div>
            <div class="row g-2">  
                <!--CGST -->
                <div class="col-lg-4 {{$cgstSectionClass}}" id="cgst_section">
                    <label class="form-label required"><i class="fa-solid fa-percent text-secondary"></i>&nbsp; CGST</label>
                    <input type="number" name="cgst" id="cgst" class="form-control" placeholder="{{ $isView ? '' : 'Enter sale type cgst' }}"
                          value="{{ old('cgst', $data->cgst ?? '0.00') }}" {{ $isView ? 'readonly' : 'required' }}>
                </div>
                <!--SGST -->
                <div class="col-lg-4 {{$sgstSectionClass}}" id="sgst_section">
                    <label class="form-label required"><i class="fa-solid fa-percent text-secondary"></i>&nbsp; SGST</label>
                    <input type="number" name="sgst" id="sgst" class="form-control" placeholder="{{ $isView ? '' : 'Enter sale type sgst' }}"
                          value="{{ old('sgst', $data->sgst ?? '0.00') }}" {{ $isView ? 'readonly' : 'required' }}>
                </div>
                <!--IGST -->
                <div class="col-lg-4 {{$igstSectionClass}}" id="igst_section">
                    <label class="form-label required"><i class="fa-solid fa-percent text-secondary"></i>&nbsp; IGST</label>
                    <input type="number" name="igst" id="igst" class="form-control" placeholder="{{ $isView ? '' : 'Enter sale type igst' }}"
                          value="{{ old('igst', $data->igst ?? '0.00') }}" {{ $isView ? 'readonly' : 'required' }}>
                </div>                                         
            </div>
          </div>                        
      </div>
    </div>
</form>
</div>

        <div class="modal-footer">
            <button type="button" class="btn btn-link non-selectable waves-effect" data-bs-dismiss="modal">Close</button>
                @if(!$isView)
            <button type="submit" form="sale_type_form" class="btn btn-primary form-save-btn waves-effect">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-square-check"
                    width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                    <path d="M9 12l2 2l4 -4" />
                </svg>
                @if ($formMode == 'edit')
                    Update Type
                @else
                    Save Type
                @endif
            </button>
            @endif
        </div>
    </div>
  </div>
</div>

<script src="{{ asset('js/master/sale-type/modal.js') }}?v={{ hash_file('md5', public_path('js/master/sale-type/modal.js')) }}"></script>  
