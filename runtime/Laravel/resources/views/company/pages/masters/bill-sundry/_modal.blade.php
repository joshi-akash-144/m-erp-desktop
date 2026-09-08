@php
$formMode = $modalData['form_mode'] ?? 'create'; // 'create' | 'edit' | 'view'
$title = $modalData['title'] ?? 'Add Bill Sundry';
$uuid = $modalData['uuid'] ?? '';
$data = $modalData['data'] ?? null;
$accounts = $modalData['accounts'] ?? null;
$isView = $formMode === 'view';
$usedInPurchase =$modalData['usedInPurchase'] ?? null;
$purchaseAccountSectionClass = $purchaseAccountTypeSectionClass = $applyOnClass = '';
$purchasePartyAccountSectionClass = $purchasePartyAccountTypeSectionClass = '';
$saleAccountSectionClass = $saleAccountTypeSectionClass = '';
$salePartyAccountSectionClass = $salePartyAccountTypeSectionClass = '';
$purchasePostOverAboveSectionClass = $salePostOverAboveSectionClass = '';

if ($formMode == 'edit' || $formMode == 'view') {

  // Bill Sundry Calculation Type
  if ($data?->calculation_type === 'fixed') {
    $applyOnClass='d-none';
  }else{
    $applyOnClass='';
  }
  // Purchase Adjust in Amount
  if ($data?->purchase_adjust_in_amount == false) {
    $purchaseAccountSectionClass = '';
    $purchaseAccountTypeSectionClass = '';
    if ($data?->purchase_account_type === 'specify_account_in_voucher') {
      $purchaseAccountSectionClass = 'd-none';
    }
  } else {
    $purchaseAccountSectionClass = 'd-none';
    $purchaseAccountTypeSectionClass = 'd-none';

  }

  // Purchase Adjust in Party Amount
  if ($data?->purchase_adjust_in_party_amount == false) {
    $purchasePartyAccountSectionClass = '';
    $purchasePartyAccountTypeSectionClass = '';
    if ($data?->purchase_party_account_type === 'specify_account_in_voucher') {
      $purchasePartyAccountSectionClass = 'd-none';
    }
  } else {
    $purchasePartyAccountSectionClass = 'd-none';
    $purchasePartyAccountTypeSectionClass = 'd-none';
  }

  // Sale Adjust in Amount
  if ($data?->sale_adjust_in_amount == false) {
    $saleAccountSectionClass = '';
    $saleAccountTypeSectionClass = '';
    if ($data?->sale_account_type === 'specify_account_in_voucher') {
      $saleAccountSectionClass = 'd-none';
    }
  } else {
    $saleAccountSectionClass = 'd-none';
    $saleAccountTypeSectionClass = 'd-none';
  }

  // Sale Adjust in Party Amount
  if ($data?->sale_adjust_in_party_amount == false) {
    $salePartyAccountSectionClass = '';
    $salePartyAccountTypeSectionClass = '';
    if ($data?->sale_party_account_type === 'specify_account_in_voucher') {
      $salePartyAccountSectionClass = 'd-none';
    }
  } else {
    $salePartyAccountSectionClass = 'd-none';
    $salePartyAccountTypeSectionClass = 'd-none';
  }

  // Purchase Post Over and Above Section
  if (
    ($data->purchase_adjust_in_amount == false && $data->purchase_adjust_in_party_amount == false) ||
    ($data->purchase_adjust_in_amount == true && $data->purchase_adjust_in_party_amount == true)
  ) {
    $purchasePostOverAboveSectionClass = 'd-none';
  } else {
    $purchasePostOverAboveSectionClass = '';
  }

  // Sale Post Over and Above Section
  if (
    ($data?->sale_adjust_in_amount == false && $data?->sale_adjust_in_party_amount == false) ||
    ($data?->sale_adjust_in_amount == true && $data?->sale_adjust_in_party_amount == true)
  ) {
    $salePostOverAboveSectionClass = 'd-none';
  } else {
    $salePostOverAboveSectionClass = '';
  }
}


@endphp

<div class="modal fade master-modal" id="bill_sundry_modal" tabindex="-1"  data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="bill_sundry_modal" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
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
        <form id="bill_sundry_form"
          action="{{ $formMode === 'edit' ? route('bill-sundries.update', $data->id) : route('bill-sundries.store') }}"
          method="POST"
          class="needs-validation"
          novalidate>

          @csrf
          @if($formMode === 'edit')
          @method('PUT')
          @else
          <input type="hidden" name="uuid" value="{{ $uuid }}">
          @endif
          <input type="hidden" name="uuid" value="{{ $uuid }}">

          <input type="hidden" id="form_mode" value="{{ $formMode }}">

          <div class="row g-2">
            <!-- Left Column -->
            <div class="col-md-12">
              <!-- Bill Sundry Details -->
              <div class="master-form-section">
                <div class="master-section-title "><i class="fa-solid fa-file-pen me-2 text-primary"></i>General Details</div>
                <div class="row g-2">
                  <!-- Bill Sundry Name -->
                  <div class="col-lg-3 col-sm-3 col-md-12">
                    <label class="form-label required"><i class="fa-solid fa-user text-secondary"></i> Name</label>
                    <input type="text" name="name" id="name" class="form-control"
                      placeholder="Enter bill sundry name"
                      value="{{ old('name', $data->name ?? '') }}"
                      {{ $isView ? 'readonly' : 'required' }}>
                  </div>
                  <!-- Bill Sundry Print Name -->
                  <div class="col-lg-3 col-sm-3 col-md-12">
                    <label class="form-label required"><i class="fa-solid fa-print text-secondary"></i> Print Name</label>
                    <input type="text" name="print_name" id="print_name" class="form-control"
                      placeholder="Enter bill sundry print name"
                      value="{{ old('print_name', $data->print_name ?? '') }}"
                      {{ $isView ? 'readonly' : 'required' }}>
                  </div>
                  <!-- Bill Sundry Type -->
                  @php
                    $billSundryType = config('constants.bill_sundry_type');
                  @endphp
                  <div class="col-lg-2" id="primary_group_div">
                    <label class="form-label required"><i class="fa-solid fa-group-arrows-rotate text-secondary"></i> Bill Sundry Type</label>
                    {{-- <select name="bill_sundry_type" id="bill_sundry_type" class="form-select select2" {{ $isView ? 'disabled' : 'required' }} {{ $usedInPurchase ? 'readonly' : 'required' }} >                     
                      <option value="">Select Bill Sundry Type...</option>
                      @foreach ($billSundryType as $groupTypeId => $groupType)
                      <option value="{{ $groupTypeId }}" {{ ($data->bill_sundry_type ?? '') == $groupTypeId ? 'selected' : '' }}>
                        {{ $groupType }}
                      </option>
                      @endforeach                                     
                    </select>  --}}
                     @if($usedInPurchase)                      
                          <input type="text" class="form-control" id="bill_sundry_type" value="{{ $billSundryType[$data->bill_sundry_type] ?? 'N/A' }}" readonly>                                          
                          <input type="hidden" name="bill_sundry_type" id="bill_sundry_type" value="{{ $data->bill_sundry_type }}" readonly>
                      @else
                          <!-- SHOW SELECT BOX when ID is not matched (Not in Use) -->
                          <select name="bill_sundry_type" id="bill_sundry_type" class="form-select select2"  {{ $isView ? 'disabled' : 'required' }}>                     
                              <option value="">Select Bill Sundry Type...</option>
                              @foreach ($billSundryType as $groupTypeId => $groupType)
                                  <option value="{{ $groupTypeId }}" {{ ($data->bill_sundry_type ?? '') == $groupTypeId ? 'selected' : '' }}>
                                      {{ $groupType }}
                                  </option>
                              @endforeach
                          </select> 
                      @endif
                  </div>
                  <!-- Bill Sundry Default Value -->
                  <div class="col-lg-2 col-sm-3 col-md-12">
                    <label class="form-label required"><i class="fa-solid fa-rotate-left text-secondary"></i>   Default Value</label>
                    <input type="number" name="default_value" id="default_value" class="form-control decimal"
                      placeholder="Enter bill sundry default value"
                      value="{{ old('default_value', $data->default_value ?? '') }}"
                      {{ $isView ? 'readonly' : 'required' }}>
                  </div>
                  <!-- Bill Sundry Round off -->
                  @php
                  $roundOff = config('constants.bill_sundry_amount_round_off');
                  @endphp
                  <div class="col-lg-2" id="primary_group_div">
                    <label class="form-label required"><i class="fa-solid fa-circle-notch text-secondary"></i> Round Off</label>
                      @if($usedInPurchase)                      
                            <input type="text" class="form-control" id="bill_sundry_amount_round_off" value="{{ isset($roundOff[(int)$data->bill_sundry_amount_round_off]) ? $roundOff[(int)$data->bill_sundry_amount_round_off] : 'N/A' }}" readonly>                                          
                            <input type="hidden" name="bill_sundry_amount_round_off" id="bill_sundry_amount_round_off" value="{{ $data->bill_sundry_amount_round_off ? '1' : '0' }}" readonly>
                      @else
                        <select name="bill_sundry_amount_round_off" id="bill_sundry_amount_round_off" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                          <option value="">Select Bill Sundry Round off...</option>
                          @foreach ($roundOff as $groupTypeId => $groupType)
                            <option value="{{ $groupTypeId }}" {{ ($data->bill_sundry_amount_round_off ?? '') == $groupTypeId ? 'selected' : '' }}>
                              {{ $groupType }}
                            </option>
                          @endforeach
                        </select>
                      @endif
                  </div>
                  <!-- Bill Sundry Nature -->
                  @php
                    $nature = config('constants.bill_sundry_nature');
                  @endphp
                  <div class="col-lg-2" id="primary_group_div">
                    <label class="form-label required"><i class="fa-solid fa-leaf text-secondary"></i> Nature</label>
                      @if($usedInPurchase)                      
                            <input type="text" class="form-control" id="bill_sundry_nature" value="{{ $nature[$data->bill_sundry_nature] ?? 'N/A' }}" readonly>                                          
                            <input type="hidden" name="bill_sundry_nature" id="bill_sundry_nature" value="{{ $data->bill_sundry_nature }}" readonly>
                      @else
                        <select name="bill_sundry_nature" id="bill_sundry_nature" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                            <option value="">Select Bill Sundry Nature...</option>
                            @foreach ($nature as $groupTypeId => $groupType)
                              <option value="{{ $groupTypeId }}" {{ ($data->bill_sundry_nature ?? '') == $groupTypeId ? 'selected' : '' }}>
                                {{ $groupType }}
                              </option>
                            @endforeach
                        </select>
                      @endif
                  </div>
                  <!-- Bill Sundry Calculation Type -->
                  @php
                    $calculationType = config('constants.calculation_type');
                  @endphp
                  <div class="col-lg-2" id="primary_group_div">
                    <label class="form-label required"><i class="fa-solid fa-calculator text-secondary"></i> Calculation Type</label>
                    @if($usedInPurchase)                      
                            <input type="text" class="form-control" id="calculation_type" value="{{ $calculationType[$data->calculation_type] ?? 'N/A' }}" readonly>                                          
                            <input type="hidden" name="calculation_type" id="calculation_type" value="{{ $data->calculation_type }}" readonly>
                    @else
                      <select name="calculation_type" id="calculation_type" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select Calculation Type...</option>
                        @foreach ($calculationType as $groupTypeId => $groupType)
                            <option value="{{ $groupTypeId }}" {{ ($data->calculation_type ?? '') == $groupTypeId ? 'selected' : '' }}>
                              {{ $groupType }}
                            </option>
                        @endforeach
                      </select>
                    @endif
                  </div>
                  <!-- Bill Sundry Apply On -->
                  @php
                      $applyOn = config('constants.apply_on');
                  @endphp
                  <div class="col-lg-2">
                    <div class="{{$applyOnClass}}" id="applyOn">
                      <label class="form-label required"><i class="fa-solid fa-clone text-secondary"></i> Apply On</label>
                       @if($usedInPurchase)                      
                            <input type="text" class="form-control" id="apply_on" value="{{ $applyOn[$data->apply_on] ?? 'N/A' }}" readonly>                                          
                            <input type="hidden" name="apply_on" id="apply_on" value="{{ $data->apply_on }}" readonly>
                      @else
                      <select name="apply_on" id="apply_on" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select Apply On...</option>
                        @foreach ($applyOn as $groupTypeId => $groupType)
                          <option value="{{ $groupTypeId }}" {{ ($data->apply_on ?? '') == $groupTypeId ? 'selected' : '' }}>
                            {{ $groupType }}
                          </option>
                        @endforeach
                      </select>
                      @endif
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-12">
              <!-- In Purchase Detail -->
              <div class="master-form-section">
                <div class="master-section-title "><i class="fa-solid fa-file-invoice text-primary"></i>&nbsp;In Purchase Detail</div>
                <div class="row g-2">
                  <!-- Adjust in Purchase Amount -->
                  @php
$adjustInAmmount = config('constants.purchase_adjust_in_amount');
                  @endphp
                  <div class="col-lg-4" id="primary_group_div">
                    <label class="form-label required"><i class="fa-solid fa-pencil text-secondary"></i>&nbsp; Adjust in Purchase Amount</label>
                    <select name="purchase_adjust_in_amount" id="purchase_adjust_in_amount" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                      <option value="">Select Yes/No...</option>
                      @foreach ($adjustInAmmount as $groupTypeId => $groupType)
                      <option value="{{ $groupTypeId }}" {{ ($data->purchase_adjust_in_amount ?? '') == $groupTypeId ? 'selected' : '' }}>
                        {{ $groupType }}
                      </option>
                      @endforeach
                    </select>
                  </div>
                  <!-- Specify Account Type  -->
                  @php
$regions = config('constants.purchase_account_type');
                  @endphp
                  <div class="col-lg-4">
                    <div class=" {{$purchaseAccountTypeSectionClass}}" id="purchase_account_type_section">
                      <label class="form-label required"><i class="fa-solid fa-user text-secondary"></i>&nbsp; Specify Account Type </label>
                      <select name="purchase_account_type" id="purchase_account_type" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select Account Type...</option>
                        @foreach ($regions as $groupTypeId => $groupType)
                        <option value="{{ $groupTypeId }}" {{ ($data->purchase_account_type ?? '') == $groupTypeId ? 'selected' : '' }}>
                          {{ $groupType }}
                        </option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  <!-- Specific Accounts -->
                  <div class="col-lg-4" id="primary_group_div">
                    <div class=" {{$purchaseAccountSectionClass}}" id="purchase_account_section">
                      <label class="form-label required"><i class="fa-solid fa-user text-secondary"></i>&nbsp; Specific Account</label>
                      <select name="purchase_account_id" id="purchase_account_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select Specific Account...</option>
                        @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" {{ ($data->purchase_account_id ?? '') == $account->id ? 'selected' : '' }}>
                          {{ $account->name }}
                        </option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  <!-- Adjust in Party Amount -->
                  @php
$adjustInPartyAmount = config('constants.purchase_adjust_in_party_amount');
                  @endphp
                  <div class="col-lg-4" id="primary_group_div">
                    <label class="form-label required"><i class="fa-solid fa-pencil text-secondary"></i>&nbsp; Adjust in Party Amount</label>
                    <select name="purchase_adjust_in_party_amount" id="purchase_adjust_in_party_amount" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                      <option value="">Select Yes/No...</option>
                      @foreach ($adjustInPartyAmount as $groupTypeId => $groupType)
                      <option value="{{ $groupTypeId }}" {{ ($data->purchase_adjust_in_party_amount ?? '') == $groupTypeId ? 'selected' : '' }}>
                        {{ $groupType }}
                      </option>
                      @endforeach
                    </select>
                  </div>
                  <!-- Specify Account Type  -->
                  @php
$regions = config('constants.purchase_party_account_type');
                  @endphp
                  <div class="col-lg-4">
                    <div class="{{$purchasePartyAccountTypeSectionClass}}" id="purchase_party_account_type_section">
                      <label class="form-label required"><i class="fa-solid fa-user text-secondary"></i></i>&nbsp; Specify Account Type </label>
                      <select name="purchase_party_account_type" id="purchase_party_account_type" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select Account Type...</option>
                        @foreach ($regions as $groupTypeId => $groupType)
                        <option value="{{ $groupTypeId }}" {{ ($data->purchase_party_account_type ?? '') == $groupTypeId ? 'selected' : '' }}>
                          {{ $groupType }}
                        </option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  <!-- Specific Accounts -->
                  <div class="col-lg-4" id="primary_group_div">
                    <div class="{{$purchasePartyAccountSectionClass}}" id="purchase_party_account_section">
                      <label class="form-label required"><i class="fa-solid fa-user text-secondary"></i>&nbsp; Specific Account</label>
                      <select name="purchase_party_account_id" id="purchase_party_account_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select Specific Account...</option>
                        @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" {{ ($data->purchase_party_account_id ?? '') == $account->id ? 'selected' : '' }}>
                          {{ $account->name }}
                        </option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  <!-- Post Over & Above -->
                  @php
$regions = config('constants.purchase_post_over_and_above');
                  @endphp
                  <div class="col-lg-4">
                    <div class="{{$purchasePostOverAboveSectionClass}}" id="purchase_post_over_above_section">
                      <label class="form-label required"><i class="fa-solid fa-arrow-up text-secondary"></i>&nbsp; Post Over & Above </label>
                      <select name="purchase_post_over_and_above" id="purchase_post_over_and_above" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select yes/no...</option>
                        @foreach ($regions as $groupTypeId => $groupType)
                        <option value="{{ $groupTypeId }}" {{ ($data->purchase_post_over_and_above ?? '') == $groupTypeId ? 'selected' : '' }}>
                          {{ $groupType }}
                        </option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-12">
              <!-- In Sale Detail -->
              <div class="master-form-section">
                <div class="master-section-title "><i class="fa-solid fa-file-invoice-dollar"></i>&nbsp;In Sale Detail</div>
                <div class="row g-2">
                  <!-- Adjust in Sale Amount -->
                  @php
$adjustInAmmount = config('constants.sale_adjust_in_amount');
                  @endphp
                  <div class="col-lg-4" id="primary_group_div">
                    <label class="form-label required"><i class="fa-solid fa-pencil text-secondary"></i>&nbsp; Adjust in Sale Amount</label>
                    <select name="sale_adjust_in_amount" id="sale_adjust_in_amount" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                      <option value="">Select Yes/No...</option>
                      @foreach ($adjustInAmmount as $groupTypeId => $groupType)
                      <option value="{{ $groupTypeId }}" {{ ($data->sale_adjust_in_amount ?? '') == $groupTypeId ? 'selected' : '' }}>
                        {{ $groupType }}
                      </option>
                      @endforeach
                    </select>
                  </div>
                  <!-- Specify Account Type  -->
                  @php
$regions = config('constants.sale_account_type');
                  @endphp
                  <div class="col-lg-4">
                    <div class=" {{$saleAccountTypeSectionClass}}" id="sale_account_type_section">
                      <label class="form-label required"><i class="fa-solid fa-user text-secondary"></i>&nbsp; Specify Account Type </label>
                      <select name="sale_account_type" id="sale_account_type" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select Account Type...</option>
                        @foreach ($regions as $groupTypeId => $groupType)
                        <option value="{{ $groupTypeId }}" {{ ($data->sale_account_type ?? '') == $groupTypeId ? 'selected' : '' }}>
                          {{ $groupType }}
                        </option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  <!-- Specific Accounts -->
                  <div class="col-lg-4" id="primary_group_div">
                    <div class="{{$saleAccountSectionClass }}" id="sale_account_section">
                      <label class="form-label required"><i class="fa-solid fa-user text-secondary"></i>&nbsp; Specific Account</label>
                      <select name="sale_account_id" id="sale_account_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select Specific Account...</option>
                        @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" {{ ($data->sale_account_id ?? '') == $account->id ? 'selected' : '' }}>
                          {{ $account->name }}
                        </option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  <!-- Adjust in Party Amount -->
                  @php
$adjustInPartyAmount = config('constants.sale_adjust_in_party_amount');
                  @endphp
                  <div class="col-lg-4" id="primary_group_div">
                    <label class="form-label required"><i class="fa-solid fa-pencil text-secondary"></i>&nbsp; Adjust in Party Amount</label>
                    <select name="sale_adjust_in_party_amount" id="sale_adjust_in_party_amount" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                      <option value="">Select Yes/No...</option>
                      @foreach ($adjustInPartyAmount as $groupTypeId => $groupType)
                      <option value="{{ $groupTypeId }}" {{ ($data->sale_adjust_in_party_amount ?? '') == $groupTypeId ? 'selected' : '' }}>
                        {{ $groupType }}
                      </option>
                      @endforeach
                    </select>
                  </div>
                  <!-- Specify Account Type  -->
                  @php
$regions = config('constants.sale_party_account_type');
                  @endphp
                  <div class="col-lg-4">
                    <div class="{{$salePartyAccountTypeSectionClass }}" id="sale_party_account_type_section">
                      <label class="form-label required"><i class="fa-solid fa-user text-secondary"></i>&nbsp; Specify Account Type </label>
                      <select name="sale_party_account_type" id="sale_party_account_type" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select Account Type...</option>
                        @foreach ($regions as $groupTypeId => $groupType)
                        <option value="{{ $groupTypeId }}" {{ ($data->sale_party_account_type ?? '') == $groupTypeId ? 'selected' : '' }}>
                          {{ $groupType }}
                        </option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  <!-- Specific Accounts -->
                  <div class="col-lg-4" id="primary_group_div">
                    <div class="{{$salePartyAccountSectionClass}}" id="sale_party_account_section">
                      <label class="form-label required"><i class="fa-solid fa-user text-secondary"></i></i>&nbsp; Specific Account</label>
                      <select name="sale_party_account_id" id="sale_party_account_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select Specific Account...</option>
                        @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" {{ ($data->sale_party_account_id ?? '') == $account->id ? 'selected' : '' }}>
                          {{ $account->name }}
                        </option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  <!-- Post Over & Above -->
                  @php
                    $regions = config('constants.sale_post_over_and_above');
                  @endphp
                  <div class="col-lg-4">
                    <div class="{{$salePostOverAboveSectionClass}}" id="sale_post_over_above_section">
                      <label class="form-label required"><i class="fa-solid fa-arrow-up text-secondary"></i>&nbsp; Post Over & Above </label>
                      <select name="sale_post_over_and_above" id="sale_post_over_and_above" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select Yes/No...</option>
                        @foreach ($regions as $groupTypeId => $groupType)
                        <option value="{{ $groupTypeId }}" {{ ($data->sale_post_over_and_above ?? '') == $groupTypeId ? 'selected' : '' }}>
                          {{ $groupType }}
                        </option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-link non-selectable waves-effect" id='close' data-bs-dismiss="modal">Close</button>
        @if(!$isView)
        <button type="submit" form="bill_sundry_form" class="btn btn-primary form-save-btn waves-effect">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-square-check"
            width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
            fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
            <path d="M9 12l2 2l4 -4" />
          </svg>
          @if ($formMode == 'edit')
          Update Sundry
          @else
          Save Sundry
          @endif
        </button>
        @endif
      </div>
    </div>
  </div>
</div>

<script id="bill-sundry-modal-script">
(function () {    
  let validator;

  $(document).ready(function () {
    // validation
    validator = new JustValidate("#bill_sundry_form", {
        validateBeforeSubmitting: true,
        focusInvalidField: true,
    });

    validator
      .addField("#name", [
        { rule: "required", errorMessage: "Name is a required field" },
      ])
      .addField("#print_name", [
        { rule: "required", errorMessage: "Print Name is a required field" },
      ])
      .addField("#bill_sundry_type", [
        { rule: "required", errorMessage: "Bill Sundry Type is required" },
      ])
      .addField("#bill_sundry_nature", [
        { rule: "required", errorMessage: "Bill Sundry Nature is required" },
      ])
      .addField("#bill_sundry_amount_round_off", [
        { rule: "required", errorMessage: "Round Off is required" },
      ])
      // .addField("#default_value", [
      //   { rule: "required", errorMessage: "Default Value is required" },
      // ])   
      // .addField("#apply_on", [
      //   { rule: "required", errorMessage: "Apply On is required" },
      // ])
      .addField("#calculation_type", [
        { rule: "required", errorMessage: "Calculation Type is required" },
      ])   
      .onSuccess((event) => {
        event.preventDefault();
        submitFormAjax(document.getElementById("bill_sundry_form"));
      });
  });

  let isSubmitting = false;

  function submitFormAjax(form) {
      if (isSubmitting) return;
      isSubmitting = true;
    
      const $form = $(form);
      const $btn = $("#bill_sundry_modal .form-save-btn");
      const method = ($form.data("method") || $form.attr("method") || "POST").toUpperCase();
      const url = $form.attr("action");
      const isEdit = method === "PUT" || url.toLowerCase().includes("update");

      if ($btn.length) {
          $btn.prop("disabled", true).html(`
              <span class="spinner-border spinner-border-sm me-2" role="status"></span>
              ${isEdit ? "Updating" : "Saving"} <span class="animated-dots"></span>
          `);
      }

      const formData = new FormData(form);
      if (isEdit) {
          formData.append("_method", "PUT");
      }

      $.ajax({
          url: url,
          type: method,
          data: formData,
          processData: false,
          contentType: false,
          success: function (data) {
              const success = data.success;
              const message = data.message;
              if (success) {
                  showToast("success", message || (isEdit ? "Bill Sundry updated successfully!" : "Bill Sundry saved successfully!"));
                  $('#bill_sundry_modal').modal('hide');
                  if (typeof table !== "undefined" && table) {
                      table.replaceData();
                  } else if (window.table) {
                      window.table.replaceData();
                  }
                  
              } else {
                  showToast("error", message || "Something went wrong.");
              }
          },
          error: function (xhr) {
              console.error(xhr);
              const errMsg = xhr.responseJSON?.message || "Server error while submitting. Please try again.";
              showToast("error", errMsg);
          },
          complete: function () {
              if ($btn.length) {
                  $btn.prop("disabled", false).html(`
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                          viewBox="0 0 24 24" fill="none"
                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                          class="icon icon-tabler icons-tabler-outline icon-tabler-square-check">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                          <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                          <path d="M9 12l2 2l4 -4" />
                      </svg>
                      ${isEdit ? "Update Sundry" : "Save Sundry"}
                  `);
              }
              isSubmitting = false;
              if (typeof hideLoader === "function") hideLoader();
          }
      });
  }
})();
</script>