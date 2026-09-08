@php
$formMode = $modalData['form_mode'] ?? 'create'; // 'create' | 'edit' | 'view'
$title = $modalData['title'] ?? 'Add Item Group';
$uuid = $modalData['uuid'] ?? '';
$data = $modalData['data'] ?? null;

$isPrimary = is_null($data->parent_id ?? null) ? 1 : 0;

$isView = $formMode === 'view';
@endphp

<div class="modal fade master-modal" id="item_group_modal" data-bs-backdrop="static" 
     data-bs-keyboard="false" tabindex="-1" aria-labelledby="item_group_modal" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header master-header">
        <div class="d-flex align-items-center gap-3">
          <div class="master-badge bg-primary-lt rounded fs-2">
            @include('icons.plus')
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
        <form id="item_group_form"
              action="{{ $formMode === 'edit' ? route('item-groups.update', $data->id) : route('item-groups.store') }}"
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
            <div class="master-section-title "><i class="fa-solid fa-layer-group text-primary"></i>&nbsp;Group Details</div>
                <div class="row g-2">
                    <!-- Group Name -->
                    <div class="col-lg-12 col-sm-12 col-md-12">
                        <label class="form-label required"><i class="fa-solid fa-people-group text-secondary"></i> Group Name</label>
                        <input type="text" name="name" id="name" class="form-control"
                                placeholder="{{ $isView ? '' : 'Enter group name' }}"
                                value="{{ old('name', $data->name ?? '') }}"
                                {{ $isView ? 'readonly' : 'required' }}>
                    </div>                                                            
                </div>
          </div>                        
      </div>
    </div>
</form>
</div>

<div class="modal-footer">
       <button type="button" class="btn btn-link item-groups waves-effect" data-bs-dismiss="modal">Close</button>
        @if(!$isView)
        <button type="submit" form="item_group_form" class="btn btn-primary form-save-btn waves-effect">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-square-check"
               width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
               fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
            <path d="M9 12l2 2l4 -4" />
          </svg>
          @if ($formMode == 'edit')
            Update Group
            @else
            Save Group
          @endif
        </button>
        @endif
      </div>
    </div>k
  </div>
</div>

<script src="{{ asset('js/master/item-group/modal.js') }}?v={{ hash_file('md5', public_path('js/master/item-group/modal.js')) }}"></script>  