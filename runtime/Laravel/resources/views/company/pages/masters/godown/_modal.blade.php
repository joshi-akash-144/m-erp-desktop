@php
$formMode = $modalData['form_mode'] ?? 'create'; // 'create' | 'edit' | 'view'
$title = $modalData['title'] ?? 'Add Godown';
$uuid = $modalData['uuid'] ?? '';
$data = $modalData['data'] ?? null;
$countries = $modalData['countries'] ?? [];
$states = $modalData['states'] ?? [];
$destination_ids = $modalData['destination_id'] ?? [];

$isPrimary = is_null($data->parent_id ?? null) ? 1 : 0;


$isView = $formMode === 'view';
@endphp

<div class="modal fade master-modal" id="godown_modal" tabindex="-1" data-bs-backdrop="static" 
     data-bs-keyboard="false" aria-labelledby="godown_modal" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content master-modal-content">
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
                <form id="godown_form"
                  action="{{ $formMode === 'edit' ? route('godowns.update', $data->id) : route('godowns.store') }}"
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
                          <!-- Destination Name -->
                          <div class="col-lg-6 col-sm-12 col-md-12" id="primary_group_div">
                              <label class="form-label form-label required"><i class="fa-solid fa-location-dot text-secondary"></i> Destination Name</label>
                              <select name="destination_id" id="destination_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                                <option value="">Select Destination...</option>
                                @foreach ($destination_ids as $destination_id)
                                  <option value="{{ $destination_id->id }}" {{ ($data->destination_id ?? '') == $destination_id->id ? 'selected' : '' }}>
                                    {{ $destination_id->name }}
                                  </option>
                                @endforeach
                              </select>
                          </div>

                          <!-- Godown Name -->
                          <div class="col-lg-6 col-sm-12 col-md-12">
                            <label class="form-label form-label required"><i class="fa-solid fa-warehouse text-secondary"></i> Godown Name</label>
                            <input type="text" name="godown_name" id="godown_name" class="form-control" placeholder="{{ $isView ? '' : 'Enter Godown Name' }}"
                              value="{{ old('godown_name', $data->godown_name ?? '') }}" {{ $isView ? 'readonly' : 'required' }}>
                          </div>

                          <!-- Distance -->
                          <div class="col-lg-12 col-sm-12 col-md-12">
                            <label class="form-label"><i class="fa-solid fa-pen text-secondary"></i> Remark</label>
                            <input type="text" name="remark" id="remark" class="form-control" placeholder="{{ $isView ? '' : 'Enter Remark' }}"
                              value="{{ old('remark', $data->remark ?? '') }}" {{ $isView ? 'readonly' : 'required' }}>
                          </div>  
                        </div>
                      </div>                        
                    </div>
                  </div>
                  <div class="modal-footer master-modal-footer">
                    <button type="button" class="btn btn-link non-selectable waves-effect" data-bs-dismiss="modal">Close</button>

                    @if(!$isView)
                      <button type="submit" form="godown_form" class="btn btn-primary form-save-btn waves-effect">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-square-check"
                            width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                          <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                          <path d="M9 12l2 2l4 -4" />
                        </svg>
                        @if ($formMode == 'edit')
                          Update Godown
                          @else
                            Save Godown
                          @endif
                      </button>
                    @endif
                  </div>
            </div>
        </div>
      </form>
</div>
</div>

<script src="{{ asset('js/master/godown/modal.js') }}?v={{ hash_file('md5', public_path('js/master/godown/modal.js')) }}"></script>  