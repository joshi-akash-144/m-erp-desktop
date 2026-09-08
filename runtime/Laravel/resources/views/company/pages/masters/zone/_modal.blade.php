@php
  $formMode = $modalData['form_mode'] ?? 'create'; // 'create' | 'edit' | 'view'
  $title = $modalData['title'] ?? 'Add Zone';
  $uuid = $modalData['uuid'] ?? '';
  $data = $modalData['data'] ?? null;

  $isView = $formMode === 'view';
@endphp

<div class="modal fade master-modal" id="zone_modal" tabindex="-1" data-bs-backdrop="static"
  data-bs-keyboard="false" aria-labelledby="zone_modal" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header master-header">
        <div class="d-flex align-items-center gap-3">
          <div class="master-badge bg-primary-lt rounded fs-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-database"
              width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
              stroke-linecap="round" stroke-linejoin="round">
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
        <form id="zone_form"
          action="{{ $formMode === 'edit' ? route('zones.update', $data->id) : route('zones.store') }}"
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
              <!-- Zone Details -->
              <div class="master-form-section">
                <div class="master-section-title "><i class="fa-solid fa-users me-2 text-primary"></i>Zone Details</div>
                <div class="row g-2">
                  <!-- Zone Name -->
                  <div class="col-lg-12 col-sm-12 col-md-12">
                    <label class="form-label required"><i class="fa-solid fa-city text-secondary me-2"></i>Name</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="Enter name"
                      value="{{ old('name', $data->name ?? '') }}" {{ $isView ? 'readonly' : 'required' }}>
                    
                  </div>
                  <!-- Zone Rate -->
                  <div class="col-lg-12 col-sm-12 col-md-12">
                    <label class="form-label required"><i class="fa-solid fa-percentage text-secondary me-2"></i>Rate</label>
                    <input type="text" name="rate" id="rate" class="form-control" placeholder="Enter rate"
                      value="{{ old('rate', $data->rate ?? '') }}" {{ $isView ? 'readonly' : 'required' }}>
                  </div>
                  <!-- Zone Remarks -->
                  <div class="col-lg-12 col-sm-12 col-md-12">
                    <label class="form-label"><i class="fa-solid fa-sticky-note text-secondary me-2"></i>Remarks</label>
                    <textarea name="remarks" id="remarks" class="form-control" placeholder="Enter remarks" rows="3" {{ $isView ? 'readonly' : '' }}>{{ old('remarks', $data->remarks ?? '') }}</textarea>
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
          <button type="submit" form="zone_form" class="btn btn-primary form-save-btn waves-effect">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-square-check"
              width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
              stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
              <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
              <path d="M9 12l2 2l4 -4" />
            </svg>
            @if ($formMode == 'edit')
              Update Zone
            @else
              Save Zone
            @endif
          </button>
        @endif
      </div>
    </div>
  </div>
</div>

<script src="{{ asset('js/master/zone/modal.js') }}?v={{ hash_file('md5', public_path('js/master/zone/modal.js')) }}"></script>  
