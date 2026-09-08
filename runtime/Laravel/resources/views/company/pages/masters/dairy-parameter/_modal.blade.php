@php
$formMode = $modalData['form_mode'] ?? 'create'; // 'create' | 'edit' | 'view'
$title = $modalData['title'] ?? 'Add Dairy Parameter';
$uuid = $modalData['uuid'] ?? '';
$data = $modalData['data'] ?? null;
$element = $modalData['element'] ?? [];
$condition = $modalData['condition'] ?? [];
$parameter_details_length = $modalData['dairy_parameter_length'];

$isPrimary = is_null($data->parent_id ?? null) ? 1 : 0;

$isView = $formMode === 'view';
@endphp

<div class="modal fade master-modal" id="dairy_parameter_modal" data-bs-backdrop="static" 
     data-bs-keyboard="false" tabindex="-1" aria-labelledby="dairy_parameter_modal" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content master-modal-content">
            <div class="modal-header master-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="master-badge bg-primary-lt rounded fs-2">
                        @include('icons.database')
                    </div>
                    <div>
                        <h5 class="modal-title mb-0 font-monospace">{{ $title }}</h5>
                        <small class="badge bg-teal text-teal-fg">Master Data Management</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="dairy_parameter_form" action="{{ $formMode === 'edit' ? route('dairy-parameters.update', $data->id) : route('dairy-parameters.store') }}"
                method="POST" class="needs-validation" novalidate>
                <div class="modal-body">

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
                            <!-- Dariy Parameters -->
                            <div class="master-form-section">
                                <div class="master-section-title "><i class="fas fa-cow"></i>&nbsp;
                                    Dairy Parameter
                                </div>
                                <div class="row g-2">
                                    <!-- Condition -->
                                    <div class="col-4" id="primary_group_div">
                                        <label class="form-label required"><i class="fa-solid fa-list-check text-secondary"></i>&nbsp;Condition</label>
                                        <select name="condition_id" id="condition_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                                            <option value="">Select Condition...</option>
                                            @foreach ($condition as $conditions)
                                            <option value="{{ $conditions->id }}" {{ ($data->condition_id ?? '') == $conditions->id ? 'selected' : '' }}>
                                                {{ $conditions->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <!-- Element -->
                                    <div class="col-4" id="primary_group_div">
                                        <label class="form-label required"><i class="fa-solid fa-atom text-secondary"></i>&nbsp; Element</label>
                                        <select name="element_id" id="element_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                                            <option value="">Select Element...</option>
                                            @foreach ($element as $elements)
                                            <option value="{{ $elements->id }}" {{ ($data->element_id ?? '') == $elements->id ? 'selected' : '' }}>
                                                {{ $elements->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Parameter Details -->
                            <div class="master-form-section">
                                <div class="master-section-title "><i class="fas fa-cog"></i>&nbsp;Dairy Parameter Details
                                </div>
                                <div class="row p-2">
                                    <div class="col-lg-12">
                                        <div class="row mb-3">
                                            <div class="col-lg-2 col-6">
                                                <div class="mb-3">
                                                    <label for="guarantee" class="form-label required"><i class="fas fa-shield-alt text-secondary"></i>&nbsp;Guarantee</label>
                                                    <input
                                                        type="number"
                                                        class="form-control"    
                                                        id="guarantee"
                                                        name="guarantee"
                                                        placeholder="Enter guarantee"
                                                        step="0.01"
                                                        value="{{ old('guarantee', $data->guarantee ?? '') }}" {{ $isView ? 'readonly' : 'required' }}
                                                        required />
                                                </div>
                                            </div>

                                            <div class="col-lg-10 col-6 d-flex justify-content-end align-items-start">
                                                <div class="mb-3">
                                                    <label class="form-label d-none d-lg-block">&nbsp;</label>
                                                    <button type="button" id="add_line" class="btn btn-primary btn-sm fw-bold waves-effect">
                                                        <i class="fa fa-plus" aria-hidden="true"></i> Add Line
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card-table table-responsive pt-0">
                                            <table id="parameter_table" class="table table-sm align-middle border border-dark mb-0">
                                                <thead class="table-light text-nowrap border border-dark">
                                                    <tr>
                                                        <th class="bg-primary bg-opacity-10 text-dark"><i class="fa-solid fa-right-long text-secondary"></i>&nbsp;From</th>
                                                        <th class="bg-primary bg-opacity-10 text-dark"><i class="fa-solid fa-left-long text-secondary"></i>&nbsp;To</th>
                                                        <th class="bg-primary bg-opacity-10 text-dark"><i class="fa-solid fa-minus text-secondary"></i>&nbsp;Difference</th>
                                                        <th class="bg-primary bg-opacity-10 text-dark"><i class="fa-solid fa-percent text-secondary"></i>&nbsp;Rebate</th>
                                                        <th class="bg-primary bg-opacity-10 text-dark"><i class="fa-solid fa-percent text-secondary"></i>&nbsp;Premium</th>
                                                        <th class="bg-primary bg-opacity-10 text-dark">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Example row (can be duplicated dynamically using JS) -->
                                                    @for ($i = 0; $i < $parameter_details_length; $i++)
                                                    <tr>
                                                        <td>
                                                            <input type="hidden" name="id[]" value="{{ old('id', $data?->parameterDetails[$i]->id ?? '') }}">
                                                            <div class="mb-2">
                                                                <input
                                                                    type="number"
                                                                    class="form-control decimal"
                                                                    name="from[]"
                                                                    placeholder="0.00"
                                                                    step="0.01"
                                                                    value="{{ old('from', $data?->parameterDetails[$i]->from ?? '') }}" {{ $isView ? 'readonly' : 'required' }}
                                                                    required />
                                                            </div>
                                                            <span class="text-danger small error-msg"></span>
                                                        </td>
                                                        <td>
                                                            <div class="mb-2">
                                                                <input
                                                                    type="number"
                                                                    class="form-control"
                                                                    name="to[]"
                                                                    placeholder="0.00"
                                                                    step="0.01"
                                                                    value="{{ old('to', $data?->parameterDetails[$i]->to ?? '') }}" {{ $isView ? 'readonly' : 'required' }}
                                                                    required />
                                                            </div>
                                                            <span class="text-danger small error-msg"></span>
                                                        </td>
                                                        <td>
                                                            <div class="mb-2">
                                                                <input
                                                                    type="number"
                                                                    class="form-control"
                                                                    name="difference[]"
                                                                    placeholder="0.00"
                                                                    step="0.01"
                                                                    value="{{ old('difference', $data?->parameterDetails[$i]->difference ?? '') }}"
                                                                    readonly />
                                                            </div>
                                                            <span class="text-danger small error-msg"></span>
                                                        </td>
                                                        <td>
                                                            <div class="mb-2">
                                                                <input
                                                                    type="number"
                                                                    class="form-control"
                                                                    name="rebate[]"
                                                                    placeholder="0.00"
                                                                    step="0.01"
                                                                    value="{{ old('rebate', $data?->parameterDetails[$i]->rebate ?? '') }}" {{ $isView ? 'readonly' : 'required' }}

                                                                    required />
                                                            </div>
                                                            <span class="text-danger small error-msg"></span>
                                                        </td>
                                                        <td>
                                                            <div class="mb-2">
                                                                <input
                                                                    type="number"
                                                                    class="form-control"
                                                                    name="premium[]"
                                                                    placeholder="0.00"
                                                                    step="0.01"
                                                                    value="{{ old('premium', $data?->parameterDetails[$i]->premium ?? '0') }}" {{ $isView ? 'readonly' : '' }} />
                                                            </div>
                                                            <span class="text-danger small error-msg"></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="erp-btn-icon text-danger remove-line" >
                                                                @include('icons.trash', ['size' => 20])
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    @endfor
                                                </tbody>
                                            </table>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer master-modal-footer">
                    <button type="button" class="btn btn-link non-selectable waves-effect" data-bs-dismiss="modal">Close</button>
                    @if(!$isView)
                    <button type="submit" form="dairy_parameter_form" class="btn btn-primary form-save-btn waves-effect">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-square-check"
                            width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                            <path d="M9 12l2 2l4 -4" />
                        </svg>
                        @if ($formMode == 'edit')
                            Update Parameter
                        @else
                            Save Parameter
                        @endif
                    </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
</div>
<script src="{{ asset('js/master/dairy-parameter/modal.js') }}?v={{ hash_file('md5', public_path('js/master/dairy-parameter/modal.js')) }}"></script>  
