@php
$formMode = $modalData['form_mode'] ?? 'create'; // 'create' | 'edit' | 'view'
$title = $modalData['title'] ?? 'Add Unit Conversion';
$uuid = $modalData['uuid'] ?? '';
$data = $modalData['data'] ?? null;
$unitConversions = $modalData['unitConversions'] ?? [];


$isPrimary = is_null($data->parent_id ?? null) ? 1 : 0;

$isView = $formMode === 'view';
@endphp

<div class="modal fade master-modal" id="unit_conversion_modal" tabindex="-1" data-bs-backdrop="static" 
     data-bs-keyboard="false" aria-labelledby="unit_conversion_modal" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header master-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="master-badge bg-primary-lt rounded fs-2">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-database" width="24"
                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
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
                <form id="unit_conversion_form"
                    action="{{ $formMode === 'edit' ? route('unit-conversions.update', $data->id) : route('unit-conversions.store') }}"
                    method="POST" class="needs-validation" novalidate>

                    @csrf

                    @if ($formMode === 'edit')
                        @method('PUT')
                    @else
                        <input type="hidden" name="uuid" value="{{ $uuid }}">
                    @endif

                    <input type="hidden" id="form_mode" value="{{ $formMode }}">

                    <div class="row g-3">
                        <div class="master-form-section">
                            <div class="master-section-title "><i class="fa-solid fa-scale-unbalanced-flip"></i>&nbsp;
                                Unit Conversion Details
                            </div>
                                <div class="row g-2">
                                    <!-- Main Unit Name -->
                                    <div class="col-lg-6">                                       
                                            <label class="master-form-label form-label required"><i class="fa-solid fa-scale-unbalanced-flip"></i> Main Unit(from)</label>
                                            <select name="main_unit_id" id="main_unit_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                                                <option value="">Select Main Unit </option>
                                                @foreach ($unitConversions as $unitConversion)
                                                    <option value="{{ $unitConversion->id }}" {{ ($data->main_unit_id ?? '') == $unitConversion->id ? 'selected' : '' }}>
                                                        {{ $unitConversion->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                    </div>
                                    <!-- Sub Unit -->
                                    <div class="col-lg-6">
                                        <label class="master-form-label form-label required"><i class="fa-solid fa-scale-unbalanced"></i> Sub Unit(to)</label>
                                        <select name="sub_unit_id" id="sub_unit_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                                            <option value="">Select Sub Uni</option>
                                            @foreach ($unitConversions as $unitConversion)
                                                <option value="{{ $unitConversion->id }}" {{ ($data->sub_unit_id ?? '') == $unitConversion->id ? 'selected' : '' }}>
                                                    {{ $unitConversion->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <!-- Conversion Factor -->
                                    <div class="col-lg-6">
                                        <label class="form-label required"><i class="fa-solid fa-left-right"></i> Conversion Factor</label>
                                        <input type="text" name="conversion_factor" id="conversion_factor" class="form-control"
                                            placeholder="{{ $isView ? '' : 'Enter Conversion Factor' }}" value="{{ old('conversion_factor', $data->conversion_factor ?? '') }}"
                                            {{ $isView ? 'readonly' : 'required' }}>
                                            <div class="form-text">&nbsp;Con. Factor = Number of Sub Unit in one Main Unit</div>
                                    </div>
                                </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-link non-selectable waves-effect" data-bs-dismiss="modal">Close</button>

                @if (!$isView)
                    <button type="submit" form="unit_conversion_form" class="btn btn-primary form-save-btn waves-effect">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-square-check" width="24"
                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                            <path d="M9 12l2 2l4 -4" />
                        </svg>

                        @if ($formMode == 'edit')
                            Update Conversion
                        @else
                            Save Conversion
                        @endif
                    </button>

                @endif
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('js/master/unit-conversion/modal.js') }}?v={{ hash_file('md5', public_path('js/master/unit-conversion/modal.js')) }}"></script>  
