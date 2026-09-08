@php
  $formMode = $modalData['form_mode'] ?? 'create'; // 'create' | 'edit' | 'view'
  $title = $modalData['title'] ?? 'Add Expense Type';
  $uuid = $modalData['uuid'] ?? '';
  $data = $modalData['data'] ?? null;
  $expenseTypeGroups = $modalData['expenseTypeGroups'] ?? collect();

  $isView = $formMode === 'view';
@endphp

<div class="modal fade master-modal" id="expense_type_modal" tabindex="-1" data-bs-backdrop="static"
  data-bs-keyboard="false" aria-labelledby="expense_type_modal" aria-hidden="true">
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
        <form id="expense_type_form"
          action="{{ $formMode === 'edit' ? route('expense-types.update', $data->id) : route('expense-types.store') }}"
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
              <!-- Expense Type Details -->
              <div class="master-form-section">
                <div class="master-section-title "><i class="fa-solid fa-users me-2 text-primary"></i>Expense Type Details</div>
                <div class="row g-2">
                  <!-- Expense Type Name -->
                  <div class="col-lg-12 col-sm-12 col-md-12">
                    <label class="form-label required"><i class="fa-solid fa-city text-secondary me-2"></i>Name</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="Enter name"
                      value="{{ old('name', $data->name ?? '') }}" {{ $isView ? 'readonly' : 'required' }}>
                  </div>
                  <!-- Expense Type Group -->
                  <div class="col-lg-12 col-sm-12 col-md-12">
                    <label class="form-label required"><i class="fa-solid fa-city text-secondary me-2"></i>Expense Type Group</label>
                    <select name="expense_type_group_id" id="expense_type_group_id" class="form-control" placeholder="Enter name"  {{ $isView ? 'readonly' : 'required' }}>
                      <option value="">Select Expense Type Group</option>
                      @foreach ($expenseTypeGroups as $expenseGroup)
                        <option value="{{ $expenseGroup->id }}" {{ old('expense_type_group_id', $data->expense_type_group_id ?? '') == $expenseGroup->id ? 'selected' : '' }}>{{ $expenseGroup->name }}</option>           
                      @endforeach 
                    </select>
                  </div>
                  <!-- Expense Type Remarks -->
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
          <button type="submit" form="expense_type_form" class="btn btn-primary form-save-btn waves-effect">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-square-check"
              width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
              stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
              <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
              <path d="M9 12l2 2l4 -4" />
            </svg>
            @if ($formMode == 'edit')
              Update Expense Type
            @else
              Save Expense Type
            @endif
          </button>
        @endif
      </div>
    </div>
  </div>
</div>

<script src="{{ asset('js/master/expense-type/modal.js') }}?v={{ hash_file('md5', public_path('js/master/expense-type/modal.js')) }}"></script>  
