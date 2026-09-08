@php
    $formMode = $modalData['form_mode'] ?? 'create'; // 'create' | 'edit' | 'view'
    $title = $modalData['title'] ?? 'Add Bags Rate';
    $uuid = $modalData['uuid'] ?? '';
    $data = $modalData['data'] ?? null;

    $isView = $formMode === 'view';
    $isEdit = $formMode === 'edit';
@endphp

<div class="modal modal-blur fade" id="bags_rate_modal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content master-modal-content">
            <div class="modal-header master-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="master-badge bg-primary-lt rounded fs-2">
                        <i class="fa-solid fa-box text-primary"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0 font-monospace">{{ $title }}</h5>
                        <small class="badge bg-teal text-teal-fg">Master Data Management</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" tabindex="-1"></button>
            </div>

            <div class="modal-body p-4">
                <form id="bags_rate_form" 
                      action="{{ $isEdit ? route('bags-rates.update', $data->id) : route('bags-rates.store') }}" 
                      method="POST" class="needs-validation" novalidate>
                    @csrf
                    @if ($isEdit)
                        @method('PUT')
                    @else
                        <input type="hidden" name="uuid" value="{{ $uuid }}">
                    @endif
                    
                    <input type="hidden" id="form_mode" value="{{ $formMode }}">

                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="master-form-section h-100">
                                <div class="master-section-title">
                                    <i class="fa-solid fa-circle-info text-primary"></i> &nbsp; General Details
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label required"> <i class="fa-solid fa-tag text-secondary"></i>&nbsp;Bags Type</label>
                                        @use('App\Models\BagsRate')   
                                        @php                                            
                                            $bagsType = [
                                                BagsRate::BAG_GUNNY   => 'Gunny',
                                                BagsRate::BAG_PLASTIC => 'Plastic',                                                     
                                            ];
                                         @endphp                                                                               
                                        <select name="bags_type" id="bags_type" class="form-select select2" {{ $isView ? 'disabled' : '' }}>
                                            <option value="">Select Bags Type...</option>
                                            @foreach ($bagsType as $val => $label)
                                                <option value="{{ $val }}" {{ ($data->bags_type ?? '') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach                  
                                        </select>  
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label required"> <i class="fa-solid fa-indian-rupee-sign text-secondary"></i>&nbsp;Bags Rate</label>
                                        <input type="number" step="0.01" name="bags_rate" id="bags_rate" class="form-control "
                                            value="{{ $data->bags_rate ?? '' }}" 
                                            placeholder="Enter Bags Rate"
                                            {{ $isView ? 'readonly' : '' }}>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer master-modal-footer">
                <button type="button" class="btn btn-link non-selectable waves-effect" data-bs-dismiss="modal" tabindex="-1">Close</button>

                @if(!$isView)
                    <button type="submit" form="bags_rate_form" class="btn btn-primary form-save-btn waves-effect">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-square-check"
                             width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                             fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                            <path d="M9 12l2 2l4 -4" />
                        </svg>
                        @if ($isEdit)
                            Update Bags Rate
                        @else
                            Save Bags Rate
                        @endif
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('js/master/bags-rate/modal.js') }}?v={{ file_exists(public_path('js/master/bags-rate/modal.js')) ? hash_file('md5', public_path('js/master/bags-rate/modal.js')) : time() }}"></script>
