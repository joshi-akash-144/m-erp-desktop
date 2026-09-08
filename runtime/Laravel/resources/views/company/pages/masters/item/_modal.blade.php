@php
$formMode = $modalData['form_mode'] ?? 'create'; // 'create' | 'edit' | 'view'
$title = $modalData['title'] ?? 'Add Item';
$uuid = $modalData['uuid'] ?? '';
$data = $modalData['data'] ?? null;
$itemGroups = $modalData['itemGroups'] ?? null;
$units = $modalData['units'] ?? null;
$taxCategorys = $modalData['taxCategories'] ?? null;
$isView = $formMode === 'view';
@endphp

<div class="modal fade master-modal" id="item_modal" tabindex="-1" data-bs-backdrop="static" 
     data-bs-keyboard="false"  aria-labelledby="item_modal" aria-hidden="true">
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
        <form id="item_form"
              action="{{ $formMode === 'edit' ? route('items.update', $data->id) : route('items.store') }}"
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
          <!-- Item Details -->
          <div class="master-form-section">
            <div class="master-section-title "><i class="fa-solid fa-box me-2 text-primary"></i>Item Details</div>
            <div class="row g-2">
                <!-- Item Name -->
                <div class="col-lg-4 col-sm-3 col-md-12">
                    <label class="form-label required"><i class="fa-solid fa-user text-secondary"></i>Name</label>
                    <input type="text" name="name" id="name" class="form-control"
                        placeholder="{{ $isView ? '' : 'Enter group name' }}"
                        value="{{ old('name', $data->name ?? '') }}"
                        {{ $isView ? 'readonly' : 'required' }}>
                </div>
                <!-- Print Name -->
                <div class="col-lg-4">
                    <label class="form-label required"><i class="fa-solid fa-print text-secondary"></i>&nbsp;Print Name</label>
                    <input type="text" name="print_name" id="print_name" class="form-control"
                    placeholder="{{ $isView ? '' : 'Enter Print Name' }}" value="{{ old('print_name', $data->print_name ?? '') }}"
                    {{ $isView ? 'readonly' : 'required' }}>
                </div>     
                <!-- Item Group -->
                <div class="col-lg-4" id="primary_group_div">
                    <label class="form-label required"><i class="fa-solid fa-boxes-stacked text-secondary"></i>&nbsp; Item Group</label>
                    <select name="item_group_id" id="item_group_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select Item Group...</option>
                        @foreach ($itemGroups as $itemGroup)
                        <option value="{{ $itemGroup->id }}" {{ ($data->item_group_id ?? '') == $itemGroup->id ? 'selected' : '' }}>
                            {{ $itemGroup->name }}
                        </option>
                        @endforeach
                    </select>
                </div>     
                <!-- Unit -->
                <div class="col-lg-4" id="primary_group_div">
                    <label class="form-label required"><i class="fa-solid fa-scale-balanced text-secondary"></i>&nbsp; Unit</label>
                    <select name="unit_id" id="unit_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select Unit...</option>
                        @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" {{ ($data->unit_id ?? '') == $unit->id ? 'selected' : '' }}>
                            {{ $unit->name }}
                        </option>
                        @endforeach
                    </select>
                </div>  
                <!-- Op. Stock Quantity -->
                <div class="col-lg-4">
                    <label class="form-label"><i class="fa-solid fa-arrow-up-1-9 text-secondary"></i>&nbsp;Op. Stock(Qty.)</label>
                    <input type="number" name="opening_qty" id="opening_qty" class="form-control"
                    placeholder="{{ $isView ? '' : 'Enter Opening Quantity' }}" value="{{ old('opening_qty', $data?->currentOpeningStock?->in_qty ?? '0.00') }}"
                    {{ $isView ? 'readonly' : 'required' }}>
                </div>    
                <!-- Op. Stock Value -->
                <div class="col-lg-4">
                    <label class="form-label"><i class="fa-solid fa-indian-rupee-sign text-secondary"></i>&nbsp;Op. Value (Rs.)</label>
                    <input type="number" name="opening_value" id="opening_value" class="form-control"
                    placeholder="{{ $isView ? '' : 'Enter Opening Value' }}" value="{{ old('opening_value', $data?->currentOpeningStock?->amount ?? '0.00') }}"
                    {{ $isView ? 'readonly' : 'required' }}>
                </div>                                           
            </div>
          </div>                        
      </div>
      <div class="col-md-12">
          <!-- Tax Details -->
          <div class="master-form-section">
            <div class="master-section-title "><i class="fa-solid fa-percent me-2 text-primary"></i>Tax Info</div>
            <div class="row g-2">  
                <!-- Tax Category -->
                <div class="col-lg-4" id="primary_group_div">
                    <label class="form-label required"><i class="fa-solid fa-percent text-secondary"></i>&nbsp; Tax Category</label>
                    <select name="tax_category_id" id="tax_category_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select Item Group...</option>
                        @foreach ($taxCategorys as $taxCategory)
                        <option value="{{ $taxCategory->id }}" {{ ($data->tax_category_id ?? '') == $taxCategory->id ? 'selected' : '' }}>
                            {{ $taxCategory->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <!-- HSN/SAC Code -->
                <div class="col-lg-4">
                    <label class="form-label"><i class="fa-solid fa-code text-secondary"></i>&nbsp;HSN/SAC Code</label>
                    <input type="text" name="hsn_sac_code" id="hsn_sac_code" class="form-control"
                    placeholder="{{ $isView ? '' : 'Enter hsn sac code' }}" value="{{ old('hsn_sac_code', $data?->hsn_sac_code ?? '') }}"
                    {{ $isView ? 'readonly' : 'required' }}>
                </div>    
                <!--  Is Bill Wise -->
                <div class="col-lg-4 col-sm-12 col-md-12">
                  @php $isBillWise = config('constants.is_maintain_stock_balance'); @endphp
                  <label class="form-label required"><i class="fa-solid fa-scale-unbalanced text-secondary"></i> Maintain Stock Bal.</label>
                  <select name="is_maintain_stock_balance" id="is_maintain_stock_balance" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                    <option value="">Select type...</option>
                    @foreach ($isBillWise as $groupTypeId => $groupType)
                    <option value="{{ $groupTypeId }}" {{ ($data->is_maintain_stock_balance ?? '') == $groupTypeId ? 'selected' : '' }}>
                      {{ $groupType }}
                    </option>
                    @endforeach
                  </select>
                </div>      
                <!-- Purchase Type (Local) -->
                <div class="col-lg-4 col-sm-12 col-md-12">
                  <label class="form-label"><i class="fa-solid fa-cart-shopping text-secondary"></i> Purchase Type (Local)</label>
                  <select name="purchase_type_local_id" id="purchase_type_local_id" class="form-select select2" {{ $isView ? 'disabled' : '' }}>
                    <option value="">Select purchase type(local)...</option>
                    @foreach ($modalData['purchaseTypes']->where('region', 'local') as $type)
                    <option value="{{ $type->id }}" {{ ($data->purchase_type_local_id ?? '') == $type->id ? 'selected' : '' }}>
                      {{ $type->name }}
                    </option>
                    @endforeach
                  </select>
                </div> 

                <!-- Purchase Type (Interstate) -->
                <div class="col-lg-4 col-sm-12 col-md-12">
                  <label class="form-label"><i class="fa-solid fa-cart-shopping text-secondary"></i> Purchase Type (Interstate)</label>
                  <select name="purchase_type_interstate_id" id="purchase_type_interstate_id" class="form-select select2" {{ $isView ? 'disabled' : '' }}>
                    <option value="">Select purchase type(interstate)...</option>
                    @foreach ($modalData['purchaseTypes']->where('region', 'interstate') as $type)
                    <option value="{{ $type->id }}" {{ ($data->purchase_type_interstate_id ?? '') == $type->id ? 'selected' : '' }}>
                      {{ $type->name }}
                    </option>
                    @endforeach
                  </select>
                </div>

                <!-- Sale Type (Local) -->
                <div class="col-lg-4 col-sm-12 col-md-12">
                  <label class="form-label"><i class="fa-solid fa-sack-dollar text-secondary"></i> Sale Type (Local)</label>
                  <select name="sale_type_local_id" id="sale_type_local_id" class="form-select select2" {{ $isView ? 'disabled' : '' }}>
                    <option value="">Select sale type(local)...</option>
                    @foreach ($modalData['saleTypes']->where('region', 'local') as $type)
                    <option value="{{ $type->id }}" {{ ($data->sale_type_local_id ?? '') == $type->id ? 'selected' : '' }}>
                      {{ $type->name }}
                    </option>
                    @endforeach
                  </select>
                </div> 

                <!-- Sale Type (Interstate) -->
                <div class="col-lg-4 col-sm-12 col-md-12">
                  <label class="form-label"><i class="fa-solid fa-sack-dollar text-secondary"></i> Sale Type (Interstate)</label>
                  <select name="sale_type_interstate_id" id="sale_type_interstate_id" class="form-select select2" {{ $isView ? 'disabled' : '' }}>
                    <option value="">Select sale type(interstate)...</option>
                    @foreach ($modalData['saleTypes']->where('region', 'interstate') as $type)
                    <option value="{{ $type->id }}" {{ ($data->sale_type_interstate_id ?? '') == $type->id ? 'selected' : '' }}>
                      {{ $type->name }}
                    </option>
                    @endforeach
                  </select>
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
        <button type="submit" form="item_form" class="btn btn-primary form-save-btn waves-effect">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-square-check"
               width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
               fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
            <path d="M9 12l2 2l4 -4" />
          </svg>
          @if ($formMode == 'edit')
            Update Item
            @else
            Save Item
          @endif
        </button>
        @endif
      </div>
    </div>
  </div>
</div>

<script src="{{ asset('js/master/item/modal.js') }}?v={{ hash_file('md5', public_path('js/master/item/modal.js')) }}"></script>  
