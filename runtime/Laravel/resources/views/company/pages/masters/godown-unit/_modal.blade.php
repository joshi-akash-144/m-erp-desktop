<?php
$formMode = $modalData['form_mode'] ?? 'create';
$title = $modalData['title'] ?? 'Add Godown Unit Location';
$data = $modalData['data'] ?? null;
$destinations = $modalData['destinations'] ?? collect();
$users = $modalData['users'] ?? collect();
$isView = $formMode === 'view';
?>

<div class="modal fade master-modal" id="godown_unit_location_modal" tabindex="-1" data-bs-backdrop="static" 
     data-bs-keyboard="false" aria-labelledby="godown_unit_location_modal" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header master-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="master-badge bg-primary-lt rounded fs-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-database" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
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
                <form id="godown_unit_location_form"
                    action="{{ $formMode === 'edit' ? route('godown_unit_location.update', $data->id) : route('godown_unit_location.store') }}"
                    method="POST" class="needs-validation" novalidate>

                    @csrf
                    @if ($formMode === 'edit') @method('PUT') @endif

                    <div class="row g-3">
                        <div class="master-form-section">
                            <div class="master-section-title"><i class="fa-solid fa-warehouse"></i>&nbsp; Godown Unit Details</div>
                            <div class="row g-2">
                                <!-- Destination -->
                                <div class="col-lg-6">
                                    <label class="master-form-label form-label required">Destination</label>
                                    <select name="destination_id" id="destination_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                                        <option value="">Select Destination</option>
                                        @foreach($destinations as $dest)
                                            <option value="{{ $dest->id }}" {{ (isset($data) && $data->destination_id == $dest->id) ? 'selected' : '' }}>{{ $dest->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <!-- Godown Name -->
                                <div class="col-lg-6">
                                    <label class="master-form-label form-label required">Godown Name</label>
                                    <input type="text" name="godown_name" id="godown_name" class="form-control" value="{{ $data->godown_name ?? '' }}" {{ $isView ? 'readonly' : 'required' }}>
                                </div>
                                <!-- Remarks -->
                                <div class="col-lg-12">
                                    <label class="master-form-label form-label">Remark</label>
                                    <textarea name="godown_remark" id="godown_remark" class="form-control" rows="2" {{ $isView ? 'readonly' : '' }}>{{ $data->godown_remark ?? '' }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-link non-selectable waves-effect" data-bs-dismiss="modal">Close</button>
                @if (!$isView)
                    <button type="submit" form="godown_unit_location_form" class="btn btn-primary form-save-btn waves-effect">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-square-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                            <path d="M9 12l2 2l4 -4" />
                        </svg>
                        {{ $formMode == 'edit' ? 'Update' : 'Save' }}
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('js/master/godown-unit-location/modal.js') }}?v={{ hash_file('md5', public_path('js/master/godown-unit-location/modal.js')) }}"></script>
