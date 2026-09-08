@php
$formMode = $modalData['form_mode'] ?? 'create'; // 'create' | 'edit' | 'view'
$title = $modalData['title'] ?? 'Add Account';
$uuid = $modalData['uuid'] ?? '';
$data = $modalData['data'] ?? null;
$accountGroups = $modalData['accountGroups'] ?? null;
$countries = $modalData['countries'] ?? null;
$states = $modalData['states'] ?? null;
$taxCategories = $modalData['taxCategories'] ?? null;
$tdsCategories = $modalData['tdsCategories'] ?? [];
$payeeCategories = $modalData['payeeCategories'] ?? [];
$isTdsApplicable = $modalData['is_tds_applicable'] ?? 0;
$cheques = $modalData['cheques'] ?? [];
$rtgs = $modalData['rtgs'] ?? [];
$isView = $formMode === 'view';
$accountGroupsFV = isset($modalData['accountGroupsFV']) && !empty($modalData['accountGroupsFV']) ? $modalData['accountGroupsFV']->toArray() : null;
$accountGroupId = !empty($data) ? $data->account_group_id : null;
$sectionValues = [];

$sections = [
    'tax_type_section',
    'gst_type_section',
    'tax_category_id_section',
    'hsn_sac_code_section',
    'itc_eligibility_section',
    'rcm_nature_section',
    'voucher_info',
    'country_id_section',
    'state_id_section',
    'city_section',
    'type_of_dealer_section',
    'filing_frequency_section',
    'gst_number_section',
    'bank_beneficiary_name_section',
];


if (!empty($accountGroupsFV) && $accountGroupId && in_array($formMode, ['edit', 'view'])) {

    foreach ($sections as $section) {
        $$section = false;
    }
    $fv = $accountGroupsFV[$accountGroupId] ?? null;

    foreach ($sections as $section) {
        $$section = false;
    }

    // Define which sections should be true for each case
    $map = [
        'f_v_1' => ['tax_type_section', 'gst_number_section'],
        'f_v_2' => [
            'country_id_section',
            'state_id_section',
            'city_section',
            'type_of_dealer_section',
            'filing_frequency_section',
            'voucher_info',
            'gst_number_section',
            'bank_beneficiary_name_section'
        ],
        'f_v_3' => [
            'gst_type_section',
            'tax_category_id_section',
            'hsn_sac_code_section',
            'itc_eligibility_section',
            'rcm_nature_section'
        ],
        'default' => [
            'country_id_section',
            'state_id_section',
            'city_section',
            'type_of_dealer_section',
            'filing_frequency_section',
            'gst_number_section'
        ]
    ];

    $selectedSections = $map[$fv] ?? $map['default'];

    foreach ($selectedSections as $section) {
        $$section = true;
    }


    foreach ($sections as $section) {
        $sectionValues[$section] = $$section;
    }
}
@endphp

<div class="modal fade master-modal" id="account_modal" tabindex="-1" aria-labelledby="account_modal" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-fullscreen modal-dialog-centered modal-dialog-scrollable">
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
        <form id="account_form"
          action="{{ $formMode === 'edit' ? route('accounts.update', $data->id) : route('accounts.store') }}"
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
            <div class="col-md-6">
                <!-- Account Details -->
                <div class="master-form-section">
                    <div class="master-section-title "><i class="fa-solid fa-file-pen me-2 text-primary"></i>General Details</div>
                    <div class="row g-2">
                    <!-- Account Name -->
                    <div class="col-lg-6">
                        <label class="form-label required"><i class="fa-solid fa-user text-secondary me-2"></i>Name</label>
                        <input type="text" name="name" id="name" class="form-control"
                        placeholder="Enter account name"
                        value="{{ old('name', $data->name ?? '') }}"
                        {{ $isView ? 'readonly' : 'required' }}>
                    </div>
                    <!-- Account Print Name -->
                    <div class="col-lg-6">
                        <label class="form-label required"><i class="fa-solid fa-print text-secondary me-2"></i>Print Name</label>
                        <input type="text" name="print_name" id="print_name" class="form-control"
                        placeholder="Enter account print name"
                        value="{{ old('print_name', $data->print_name ?? '') }}"
                        {{ $isView ? 'readonly' : 'required' }}>
                    </div>
                    <!-- Account Group -->
                    <div class="col-lg-3">
                        <label class="form-label required"><i class="fa-solid fa-user-group text-secondary"></i>&nbsp; Account Group</label>
                        <select name="account_group_id" id="account_group_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select Account Group...</option>
                        @foreach ($accountGroups as $accountGroup)
<option value="{{ $accountGroup->id }}" data-is-party-group="{{ $accountGroup->is_party_group }}" {{ ($data->
    account_group_id ?? '') == $accountGroup->id ? 'selected' : '' }}>
                                {{ $accountGroup->name }}
                            </option>
                        @endforeach
                        </select>
                    </div>
                    <!-- Account Party Type -->
                    @php
$accountPartyType = config('constants.party_type');
                    @endphp
                    <div class="col-lg-3">
                        <label class="form-label required"><i class="fa-solid fa-group-arrows-rotate text-secondary"></i>&nbsp; Party Type</label>
                        <select name="party_type" id="party_type" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select Party Type...</option>
                        @foreach ($accountPartyType as $partyTypeId => $partyType)
                        <option value="{{ $partyTypeId }}" {{ ($data->party_type ?? 'account') == $partyTypeId ? 'selected' : '' }}>
                            {{ $partyType }}
                        </option>
                        @endforeach
                        </select>
                    </div>
                    <!-- Account Balance -->
                    <div class="col-lg-3">
                        <label class="form-label required"><i class="fa-solid fa-rupee-sign text-secondary"></i>&nbsp; Op. Bal.<span class="small text-muted">(Rs.)</span></label>
                        <input type="text" name='opening_balance' id='opening_balance' class="form-control" placeholder="Enter OP. Balance"
                        value="{{old('opening_balance', $data?->opening_balance ?? '0.00')}}" {{ $isView ? 'readonly' : 'required' }}>
                    </div>
                    <!-- Account Type -->
                    <div class="col-lg-3">
                        <label class="form-label required"><i class="fa-solid fa-receipt text-secondary"></i>&nbsp; Dr/Cr</label>
                        
                        <select name="opening_type" id="opening_type" class="form-control select2" {{ $isView ? 'disabled' : 'required' }}>
                            <option value="">Select Opening Type</option>
                            <option value="D" {{ ($data?->opening_type ?? 'D') == 'D' ? 'selected' : '' }}>DR</option>
                            <option value="C" {{ ($data?->opening_type ?? 'C') == 'C' ? 'selected' : '' }}>CR</option>
                        </select>
                    </div>
                    <!-- Address one -->
                    <div class="col-lg-6">
                        <label class="form-label"><i class="fa-solid fa-city text-secondary"></i>&nbsp; Address Line 1</label>
                        <input type="text" name='address_one' id='address_one' class="form-control" placeholder="{{ $isView ? '' : 'Enter Address Line 1' }}"
                        value="{{old('address_one', $data->address_one ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                    </div>
                    <!-- Address two -->
                    <div class="col-lg-6">
                        <label class="form-label"><i class="fa-solid fa-city text-secondary"></i>&nbsp; Address Line 2</label>
                        <input type="text" name='address_two' id='address_two' class="form-control" placeholder="{{ $isView ? '' : 'Enter Address Line 2' }}"
                        value="{{old('address_two', $data->address_two ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                    </div>
                    </div>
                </div>
                <!-- GST Details -->
                <div class="master-form-section">
                    <div class="master-section-title "><i class="fa-solid fa-money-check me-2 text-primary"></i>GST Info</div>
                    <div class="row g-2">
                        <!-- Tax Type -->
                        @php
$taxType = config('constants.tax_type');
                        @endphp
                        <div class="col-lg-3" id="tax_type_section">
                            <label class="form-label"><i class="fa-solid fa-percent text-secondary"></i>&nbsp; Tax Type</label>
                            <select name="tax_type" id="tax_type" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                                <option value="">Select Tax Type...</option>
                                @foreach ($taxType as $taxTypeId => $taxType)
                                <option value="{{ $taxTypeId }}" {{ ($data->taxDetail->tax_type ?? '') == $taxTypeId ? 'selected' : '' }}>
                                    {{ $taxType }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <!-- Country -->
                        <div class="col-lg-3" id="country_id_section">
                            <label class="form-label"><i class="fa-solid fa-earth-europe text-secondary"></i>&nbsp; Country </label>
                            <select name="country_id" id="country_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                                <option value="">Select Country...</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}" {{ ($data->country_id ?? '1') == $country->id ? 'selected' : '' }}>
                                        {{ $country->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <!-- State -->
                        <div class="col-lg-3" id="state_id_section">
                            <label class="form-label"><i class="fa-solid fa-map-location-dot text-secondary"></i>&nbsp; State </label>
                            <select name="state_id" id="state_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                                <option value="">Select State...</option>
                                @foreach ($states as $state)
                                    <option value="{{ $state->id }}" {{ ($data->state_id ?? company_state_id()) == $state->id ? 'selected' : '' }}>
                                        {{ $state->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <!-- City -->
                        <div class="col-lg-3" id="city_section">
                            <label class="form-label"><i class="fa-solid fa-city text-secondary"></i>&nbsp; City</label>
                            <input type="text" name='city' id='city' class="form-control" placeholder="{{ $isView ? '' : 'Enter City' }}"
                            value="{{old('city', $data->city ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                        </div>
                        <!-- Type of Dealer -->
                        @php
                            $typeOfDealer = config('constants.type_of_dealer');
                        @endphp
                        <div class="col-lg-3" id="type_of_dealer_section">
                            <label class="form-label"><i class="fa-solid fa-handshake text-secondary"></i>&nbsp; Dealer Type</label>
                            <select name="type_of_dealer" id="type_of_dealer" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                                <option value="">Select Dealer Type...</option>
                                @foreach ($typeOfDealer as $taxTypeId => $taxType)
                                <option value="{{ $taxTypeId }}" {{ ($data?->taxDetail->type_of_dealer ?? '') == $taxTypeId ? 'selected' : '' }}>
                                    {{ $taxType }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <!-- Filing Frequency -->
                        @php
                            $filingFrequency = config('constants.filing_frequency');
                        @endphp
                        <div class="col-lg-3" id="filing_frequency_section">
                            <label class="form-label"><i class="fa-solid fa-wave-square text-secondary"></i>&nbsp; Fill. Frq.</label>
                            <select name="filing_frequency" id="filing_frequency" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                                <option value="">Select Frequency...</option>
                                @foreach ($filingFrequency as $taxTypeId => $taxType)
                                <option value="{{ $taxTypeId }}" {{ ($data?->taxDetail?->filing_frequency ?? 'not_known') == $taxTypeId ? 'selected' : '' }}>
                                    {{ $taxType }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <!-- Gst No -->
                        <div class="col-lg-6" id="gst_number_section">
                            <label class="form-label"><i class="fa-solid fa-file-invoice text-secondary"></i>&nbsp; GSTIN</label>
                            <input type="text" name='gst_number' id='gst_number' class="form-control" placeholder="{{ $isView ? '' : 'Enter GST Number' }}"
                            value="{{old('gst_number', $data->taxDetail->gst_number ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                        </div>
                        <!-- GST Type -->
                        @php
                            $gstType = config('constants.gst_type');
                        @endphp
                        <div class="col-lg-3" id="gst_type_section">
                            <label class="form-label"><i class="fa-solid fa-receipt text-secondary"></i>&nbsp; GST Type</label>
                            <select name="gst_type" id="gst_type" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                                <option value="">Select GST Type...</option>
                                @foreach ($gstType as $taxTypeId => $taxType)
                                <option value="{{ $taxTypeId }}" {{ ($data->taxDetail->gst_type ?? 'gst_not_applicable') == $taxTypeId ? 'selected' : '' }}>
                                    {{ $taxType }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <!-- Tax Category -->
                        <div class="col-lg-3" id="tax_category_id_section">
                            <label class="form-label"><i class="fa-solid fa-file-invoice-dollar text-secondary"></i>&nbsp; Tax Category </label>
                            <select name="tax_category_id" id="tax_category_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                                <option value="">Select Tax Category...</option>
                                @foreach ($taxCategories as $taxCategory)
                                    <option value="{{ $taxCategory->id }}" {{ ($data->taxDetail->tax_category_id ?? '') == $taxCategory->id ? 'selected' : '' }}>
                                        {{ $taxCategory->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
@if($isTdsApplicable)
<!-- TDS Category -->
<div class="col-lg-5" id="tds_category_id_section">
    <label class="form-label"><i class="fa-solid fa-file-invoice-dollar text-secondary"></i>&nbsp; TDS Category </label>
    <select name="tds_category_id" id="tds_category_id" class="form-select select2" {{ $isView ? 'disabled' : '' }}>
        <option value="">Select TDS Category...</option>
        @foreach ($tdsCategories as $tdsCategory)
        <option value="{{ $tdsCategory->id }}" {{ ($data->taxDetail->tds_category_id ?? '') == $tdsCategory->id ?
            'selected' : '' }}>
            {{ $tdsCategory->category_name }}
        </option>
        @endforeach
    </select>
</div>

<!-- Payee Category -->
<div class="col-lg-5" id="payee_category_id_section">
    <label class="form-label"><i class="fa-solid fa-user-tag text-secondary"></i>&nbsp; Payee Category </label>
    <select name="payee_category_id" id="payee_category_id" class="form-select select2" {{ $isView ? 'disabled' : '' }}>
        <option value="">Select Payee Category...</option>
        @foreach ($payeeCategories as $payeeCategory)
        <option value="{{ $payeeCategory->id }}" {{ ($data->taxDetail->payee_category_id ?? '') == $payeeCategory->id ?
            'selected' : '' }}>
            {{ $payeeCategory->payee_category }}
        </option>
        @endforeach
    </select>
</div>
@endif
                        <!-- HSN Sac Code -->
                        <div class="col-lg-6" id="hsn_sac_code_section">
                            <label class="form-label"><i class="fa-solid fa-clipboard-list text-secondary"></i>&nbsp; HSN SAC CODE</label>
                            <input type="text" name='hsn_sac_code' id='hsn_sac_code' class="form-control" placeholder="{{ $isView ? '' : 'Enter hsn_sac_code Number' }}"
                            value="{{old('hsn_sac_code', $data->taxDetail->hsn_sac_code ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                        </div>
                        <!-- ITC Eligibility -->
                        @php
                            $itcEligibility = config('constants.itc_eligibility');
                        @endphp
                        <div class="col-lg-3" id="itc_eligibility_section">
                            <label class="form-label"><i class="fa-solid fa-money-check text-secondary"></i>&nbsp; ITC Eligibility</label>
                            <select name="itc_eligibility" id="itc_eligibility" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                                <option value="">Select itc eligibility...</option>
                                @foreach ($itcEligibility as $taxTypeId => $taxType)
                                <option value="{{ $taxTypeId }}" {{ ($data->taxDetail->itc_eligibility ?? '') == $taxTypeId ? 'selected' : '' }}>
                                    {{ $taxType }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <!-- RCM Nature -->
                        @php
                            $rcmNature = config('constants.rcm_nature');
                        @endphp
                        <div class="col-lg-3" id="rcm_nature_section">
                            <label class="form-label"><i class="fa-solid fa-leaf text-secondary"></i>&nbsp; RCM Nature</label>
                            <select name="rcm_nature" id="rcm_nature" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                                <option value="">Select itc eligibility...</option>
                                @foreach ($rcmNature as $taxTypeId => $taxType)
                                <option value="{{ $taxTypeId }}" {{ ($data->taxDetail->rcm_nature ?? '') == $taxTypeId ? 'selected' : '' }}>
                                    {{ $taxType }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <!-- Communication -->
                <div class="master-form-section">
                    <div class="master-section-title "><i class="fa-solid fa-tower-cell me-2 text-primary"></i>Communication</div>
                    <div class="row g-2">
                        <!-- Mobile Number -->
                        <div class="col-lg-4">
                            <label class="form-label required"><i class="fa-solid fa-mobile-screen-button text-secondary"></i>&nbsp; Mobile Number</label>
                            <input class="form-control" type='text' maxlength="10" name='mobile_number' id='mobile_number' placeholder="{{ $isView ? '' : 'Enter Mobile Number' }}"
                            value="{{old('mobile_number', $data->mobile_number ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                        </div>
                        <!-- PAN Number -->
                        <div class="col-lg-4">
                            <label class="form-label"><i class="fa-solid fa-file-zipper text-secondary"></i>&nbsp; IT PAN </label>
                            <input type="text" name='pan' id='pan'  class="form-control" placeholder="{{ $isView ? '' : 'Enter Pan' }}"
                            value="{{old('pan', $data->taxDetail->pan ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                        </div>
                        <!-- TIN Number -->
                        <div class="col-lg-4">
                            <label class="form-label"><i class="fa-solid fa-file-zipper text-secondary"></i>&nbsp; TIN </label>
                            <input class="form-control" type='text' name='tin' id='tin' placeholder="{{ $isView ? '' : 'Enter TIN' }}"
                            value="{{old('tin', $data->taxDetail->tin ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                        </div>
                        <!-- Whatsapp Number -->
                        <div class="col-lg-4">
                            <label class="form-label"><i class="fa-brands fa-square-whatsapp text-secondary"></i>&nbsp; WhatsApp Number</label>
                            <input type="number" name='whatsapp_number' id='whatsapp_number' class="form-control" placeholder="{{ $isView ? '' : 'Enter WhatsApp Number' }}"
                            value="{{old('whatsapp_number', $data->whatsapp_number ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                        </div>
                        <!-- Email -->
                        <div class="col-lg-6">
                            <label class="form-label required"><i class="fa-solid fa-envelope text-secondary"></i>&nbsp; Email</label>
                            <input type="text" name='email' id='email' class="form-control" placeholder="{{ $isView ? '' : 'Enter Email' }}"
                            value="{{old('email', $data->email ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <!-- Transportation -->
                <div class="master-form-section">
                    <div class="master-section-title "><i class="fa-solid fa-truck-arrow-right me-2 text-primary"></i>Transport</div>
                    <div class="row g-2">
                        <!-- Station -->
                        <div class="col-lg-4">
                            <label class="form-label"><i class="fa-solid fa-arrow-right-to-city text-secondary"></i>&nbsp; Station</label>
                            <input class="form-control" type='text' name='station' id='station' placeholder="{{ $isView ? '' : 'Enter Station' }}"
                            value="{{old('station', $data->preference->station ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                        </div>
                        <!-- Postal Code -->
                        <div class="col-lg-4">
                            <label class="form-label"><i class="fa-solid fa-file-zipper text-secondary"></i>&nbsp; PIN</label>
                            <input type="text" name='postal_code' id='postal_code' class="form-control" placeholder="{{ $isView ? '' : 'Enter PIN' }}"
                            value="{{old('postal_code', $data->postal_code ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                        </div>
                        <!-- Distance -->
                        <div class="col-lg-4">
                            <label class="form-label"><i class="fa-solid fa-arrows-left-right text-secondary"></i>&nbsp; Distance (in Km) </label>
                            <input class="form-control" type='text' name='distance' id='distance' placeholder="{{ $isView ? '' : 'Enter Distance' }}"
                            value="{{old('distance', $data?->preference?->distance ?? '0')}}" {{ $isView ? 'readonly' : 'required' }}>
                        </div>
                        <!-- Contact Person -->
                        <div class="col-lg-4">
                            <label class="form-label"><i class="fa-solid fa-address-book text-secondary"></i>&nbsp; Contact Person</label>
                            <input type="number" name='contact_person' id='contact_person' class="form-control" placeholder=" "
                            value="{{old('contact_person', $data->preference->contact_person ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                        </div>
                        <!-- Transporter -->
                        <div class="col-lg-4">
                            <label class="form-label"><i class="fa-solid fa-truck-arrow-right text-secondary"></i>&nbsp; Transport</label>
                            <input type="text" name='transport' id='transport' class="form-control" placeholder="{{ $isView ? '' : 'Enter Transporter' }}"
                            value="{{old('transport', $data->preference->transport ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                        </div>
                        <!-- Transport Mode -->
                        @php
                            $transportMode = config('constants.transport_modes');
                        @endphp
                        <div class="col-lg-4">
                            <label class="form-label"><i class="fa-solid fa-truck-plane text-secondary"></i>&nbsp; Transport Mode</label>
                            <select name="transport_mode" id="transport_mode" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                                <option value="">Select transport mode...</option>
                                @foreach ($transportMode as $taxTypeId => $taxType)
                                <option value="{{ $taxTypeId }}" {{ ($data->preference->transport_mode ?? 'road') == $taxTypeId ? 'selected' : '' }}>
                                    {{ $taxType }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <!-- Bank Information -->
                <div class="master-form-section">
                    <div class="master-section-title "><i class="fa-solid fa-building-columns me-2 text-primary"></i>Bank Info</div>
                    <div class="row g-2">
                        <!-- Bank Name -->
                        <div class="col-lg-4">
                            <label class="form-label"><i class="fa-solid fa-user text-secondary"></i>&nbsp; Bank Name</label>
                            <input class="form-control" type='text' name='bank_name' id='bank_name' placeholder=" {{ $isView ? '' : 'Enter Bank Name' }}"
                            value="{{old('bank_name', $data->bankDetail->bank_name ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                        </div>
                        <!-- Bank Branch Name -->
                        <div class="col-lg-4">
                            <label class="form-label"><i class="fa-solid fa-map-pin text-secondary"></i>&nbsp; Branch Name</label>
                            <input type="text" name='bank_branch_name' id='bank_branch_name' class="form-control" placeholder="{{ $isView ? '' : 'Enter Branch Name' }}"
                            value="{{old('bank_branch_name', $data->bankDetail->bank_branch_name ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                        </div>
                        <!-- Bank A/C Number -->
                        <div class="col-lg-4">
                            <label class="form-label"><i class="fa-solid fa-building-columns text-secondary"></i>&nbsp; Account No. </label>
                            <input class="form-control" type='text' name='bank_account_number' id='bank_account_number' placeholder="{{ $isView ? '' : 'Enter bank account number' }}"
                            value="{{old('bank_account_number', $data?->bankDetail?->bank_account_number ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                        </div>
                        <!-- Bank IFSC -->
                        <div class="col-lg-4">
                            <label class="form-label"><i class="fa-solid fa-barcode text-secondary"></i>&nbsp; IFSC Code</label>
                            <input type="text" name='bank_ifsc' maxlength="11" id='bank_ifsc' class="form-control" placeholder="{{ $isView ? '' : 'Enter Bank Ifsc' }}"
                            value="{{old('bank_ifsc', $data->bankDetail->bank_ifsc ?? '')}}" {{ $isView ? 'readonly' : 'required' }}>
                        </div>
                        <!-- Cheque -->
                        <div class="col-lg-4">
                            <label class="form-label"><i class="fa-solid fa-barcode text-secondary"></i>&nbsp; Cheque</label>
                            <select name="cheque_id" id="cheque_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                                <option value="">Select Cheque...</option>
                                @foreach ($cheques as $cheque)
                                    <option value="{{ $cheque->id }}" {{ ($data?->cheque_master_id == $cheque->id) || (!$data?->cheque_master_id && $cheque->is_default) ? 'selected' : '' }}>
                                        {{ $cheque->formate_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <!-- Rtgs -->
                        <div class="col-lg-4">
                            <label class="form-label"><i class="fa-solid fa-barcode text-secondary"></i>&nbsp; RTGS</label>
                            <select name="rtgs_id" id="rtgs_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                                <option value="">Select RTGS...</option>
                                @foreach ($rtgs as $rtgsItem)
                                    <option value="{{ $rtgsItem->id }}" {{ ($data?->rtgs_form_id == $rtgsItem->id) || (!$data?->rtgs_form_id && $rtgsItem->is_default) ? 'selected' : '' }}>
                                        {{ $rtgsItem->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <!-- Voucher Information -->
                <div class="master-form-section" id="voucher_info">
                    <div class="master-section-title "><i class="fa-solid fa-ticket me-2 text-primary"></i>Voucher Info</div>
                    <div class="row g-2">
                        <!-- Bill By Bill -->
                        @php
$isBillWise = config('constants.is_bill_wise');
                        @endphp
                      <div class="col-lg-4">
                        <label class="form-label">
                            <i class="fa-solid fa-money-bills text-secondary"></i>&nbsp; Bill By Bill
                        </label>
                    
                        <select name="is_billwise" id="is_billwise"
                            class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                            
                            <option value="">Select Yes/No...</option>
                    
                            @foreach ($isBillWise as $groupTypeId => $groupType)
                                <option value="{{ $groupTypeId }}" {{ ($data->is_billwise ?? '') == $groupTypeId ? 'selected' : '' }}>
                                    {{ $groupType }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                        {{-- <div class="col-lg-4">
                            <label class="form-label"><i class="fa-solid fa-file-arrow-down text-secondary"></i>&nbsp; Default Sale Type </label>
                            <select name="sale_type_id" id="sale_type_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                                <option value="">Select Yes/No...</option>
                                @foreach ($isBillWise as $taxTypeId => $taxType)
                                <option value="{{ $taxTypeId }}" {{ ($data->sale_type_id ?? '') == $taxTypeId ? 'selected' : '' }}>
                                    {{ $taxType }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label"><i class="fa-solid fa-file-arrow-up text-secondary"></i>&nbsp; Default Purchase Type </label>
                            <select name="purchase_type_id" id="purchase_type_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                                <option value="">Select Yes/No...</option>
                                @foreach ($isBillWise as $taxTypeId => $taxType)
                                <option value="{{ $taxTypeId }}" {{ ($data->purchase_type_id ?? '') == $taxTypeId ? 'selected' : '' }}>
                                    {{ $taxType }}
                                </option>
                                @endforeach
                            </select>
                        </div> --}}
                    </div>
                </div>
            </div>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-link non-selectable waves-effect" id='close' data-bs-dismiss="modal">Close</button>
        @if(!$isView)
        <button type="submit" form="account_form" class="btn btn-primary form-save-btn waves-effect">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-square-check"
            width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
            fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
            <path d="M9 12l2 2l4 -4" />
          </svg>
          @if ($formMode == 'edit')
            Update Account
          @else
            Save Account
          @endif
        </button>
        @endif
      </div>
    </div>
  </div>
</div>
<script id="account-modal-script">
(function () {
        const accountStoreUrl = "{{ route('accounts.store') }}";
        let validator;

    $(document).ready(function () {

    // validation

    validator = new JustValidate("#account_form", {
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
        .addField("#account_group_id", [
        { rule: "required", errorMessage: "Please Select Group" },
        ])
        .addField("#opening_type", [
        { rule: "required", errorMessage: "Enter Type" },
        {
            rule: "customRegexp",
            value: /^[DC]$/,
            errorMessage: "Only D or C",
        },
        ])
        .addField("#mobile_number", [
        {
            rule: "customRegexp",
            value: /^(\+91-)?[6-9]\d{9}$/,
            errorMessage: "Enter a valid 10-digit mobile number",
        },
        ])
        .addField("#postal_code", [
        {
            rule: "customRegexp",
            value: /^[0-9]\d{5}$/,
            errorMessage: "Enter 6 digit pin code",
        },
        ])
        .addField("#email", [
        {
            rule: "email",
            errorMessage: "Enter valid email Address",
        },
        {
            rule: "customRegexp",
            value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            errorMessage: "Email Format is not valid",
        },
        ])
        .addField("#gst_number", [
        {
            validator: (value) => {
            const dealer_type =
                document.getElementById("type_of_dealer").value;
            if (
                dealer_type === "registered" ||
                dealer_type === "composition" ||
                dealer_type === "uni_holder"
            ) {
                return value.trim() !== "";
            }
            return true;
            },
            errorMessage: "Enter GST Number of selected dealer type",
        },
        ])
        .addField("#party_type", [
        {
            rule: "required",
            errorMessage: "Please Select Party Type",
        },
        ])
        .addField("#pan", [
        {
            rule: "customRegexp",
            value: /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/,
            errorMessage: "Please Enter Valid Pan",
        },
        ])
        .onSuccess((event) => {
        event.preventDefault();
        submitFormAjax(document.getElementById("account_form"));
        });

        // ===================================================================
        // DYNAMIC FIELD FORMATTING & EXTRACTION
        // ===================================================================

        // Force Email to Lowercase
        $('#email').on('input', function () {
            $(this).val($(this).val().toLowerCase());
        });

        // Auto-set PAN from GST and force uppercase
        $('#gst_number').on('input', function () {
            let gst = $(this).val().toUpperCase();
            $(this).val(gst);
            
            // Extract PAN (characters 3 to 12 in GSTIN)
            if (gst.length >= 12) {
                let extractedPan = gst.substring(2, 12);
                $('#pan').val(extractedPan);
            }
        });

        // Force PAN to Uppercase
        $('#pan').on('input', function () {
            $(this).val($(this).val().toUpperCase());
        });

// Handle Payee and TDS Category Visibility based on Account Group (Party Group)
$('#account_group_id').on('change', function() {
let selectedOption = $(this).find('option:selected');
let isPartyGroup = selectedOption.data('is-party-group');

if (isPartyGroup == 1) {
// It is a party group (Creditor/Debtor)
$('#payee_category_id_section').show();
$('#tds_category_id_section').hide();
$('#tds_category_id').val('').trigger('change'); // Clear TDS category
} else {
// It is NOT a party group (Expense/Income)
$('#payee_category_id_section').hide();
$('#payee_category_id').val('').trigger('change'); // Clear Payee category
$('#tds_category_id_section').show();
}
});

// Trigger on initial load to set the correct visibility
setTimeout(() => {
$('#account_group_id').trigger('change');
}, 100);

if ($.fn.select2) {
$('#tds_category_id, #payee_category_id').select2({
dropdownParent: $('#account_modal'),
width: '100%',
theme: 'bootstrap-5'
});
}
});


    // ===================================================================
    // SUBMIT HANDLER
    // ===================================================================
    let isSubmitting = false;

    function submitFormAjax(form) {
        if (isSubmitting) return;
        isSubmitting = true;
    
        const $form = $(form);
        const $btn = $("#account_modal .form-save-btn");
        // const modalEl = document.querySelector("#account_modal");
        // const modal = bootstrap.Modal.getInstance(modalEl);

        const method = ($form.data("method") || $form.attr("method") || "POST").toUpperCase();
        const url = $form.attr("action");


        const isEdit = method === "PUT" || url.toLowerCase().includes("update");

        // Button loading state
        if ($btn.length) {
            $btn.prop("disabled", true).html(`
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                ${isEdit ? "Updating" : "Saving"} <span class="animated-dots"></span>
            `);
        }

        const formData = new FormData(form);

        // Handle billwise
        const isBillwiseValue = formData.get("is_billwise");
        if (!isBillwiseValue || isBillwiseValue == false || isBillwiseValue === '0') {
            formData.set("is_billwise", '0');
        } else {
            formData.set("is_billwise", '1');
        }

        if (isEdit) {
            formData.append("_method", "PUT");
        }
    console.log("method, url", method, url);

        // jQuery Ajax
        $.ajax({
            url: url,
            type: method,
            data: formData,
            processData: false,
            contentType: false,
            success: function (data) {
                const success = data.success;
                const message = data.message;
            console.log("data");


                if (success) {
                    showToast("success", message || (isEdit ? "Account updated successfully!" : "Account saved successfully!"));
                    if (typeof table !== "undefined" && table) {
                        table.replaceData();
                    }
                    $('#account_modal').modal('hide');
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
                // Reset button
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
                        ${isEdit ? "Update Account" : "Save Account"}
                    `);
                }

                isSubmitting = false;
                if (typeof hideLoader === "function") hideLoader();
            }
        });
    }

    })();
</script>