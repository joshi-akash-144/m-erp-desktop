<div class="modal fade" id="permissionModal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $modalData['title'] }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="permissionForm" method="POST" action="{{ $modalData['form_mode'] === 'edit' ? route('permissions.update', $modalData['data']->id) : route('permissions.store') }}">
                @csrf
                @if($modalData['form_mode'] === 'edit')
                    @method('PUT')
                @endif
                
                <div class="modal-body">
                    @if($modalData['form_mode'] === 'create')
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <label class="form-label required fw-bold text-muted text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 0.5px;">Module Name</label>
                                <div class="input-icon">                                    
                                    <input type="text" name="module_name" class="form-control form-control-lg bg-light" placeholder="e.g., product, sales_order" required>
                                </div>
                                <small class="form-hint mt-2"><i class="fa-solid fa-info-circle me-1"></i>Spaces will be converted to underscores automatically.</small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label required fw-bold text-muted text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 0.5px;">Permission Names</label>
                                <div class="card shadow-none border bg-light rounded-4 p-3 mb-2">
                                    <div id="dynamic_permissions_container" class="d-flex flex-column gap-2">
                                        <div class="input-group input-group-flat bg-white rounded-3 overflow-hidden border shadow-sm permission-row" style="transition: all 0.2s ease;">
                                            <span class="input-group-text bg-transparent border-0 text-muted ps-3 pe-2">
                                                <i class="fa-solid fa-key"></i>
                                            </span>
                                            <input type="text" name="permissions[]" class="form-control border-0 px-2 py-2 shadow-none" placeholder="e.g., list " required>
                                            <button type="button" class="btn btn-light text-danger bg-transparent remove-permission-btn border-0 rounded-0 px-3" style="display: none;" title="Remove">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <button type="button" id="add_permission_input_btn" class="btn w-100 mt-3 rounded-3 text-muted hover-shadow-sm" style="border: 2px dashed #dce1e7; background: white; padding: 10px; transition: 0.2s;">
                                        <i class="fa-solid fa-plus me-2"></i> Add Another Permission
                                    </button>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <label class="form-label required fw-bold text-muted text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 0.5px;">Module Name</label>
                                <div class="input-icon">                                    
                                    <input type="text" name="module" class="form-control form-control-lg {{ $modalData['form_mode'] === 'view' ? 'bg-white' : 'bg-light' }}" value="{{ $modalData['data']->module }}" required {{ $modalData['form_mode'] === 'view' ? 'readonly' : '' }}>
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label required fw-bold text-muted text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 0.5px;">Permission Name</label>
                                <div class="card shadow-none border bg-light rounded-4 p-3 mb-2">
                                    <div class="input-group input-group-flat bg-white rounded-3 overflow-hidden border shadow-sm">
                                        @php
                                            $displayName = $modalData['data']->name;
                                            $prefix = $modalData['data']->module . '.';
                                            if (str_starts_with($displayName, $prefix)) {
                                                $displayName = substr($displayName, strlen($prefix));
                                            }
                                        @endphp
                                        <input type="text" name="name" class="form-control border-0 px-2 py-2 shadow-none {{ $modalData['form_mode'] === 'view' ? 'bg-white' : '' }}" value="{{ $displayName }}" required {{ $modalData['form_mode'] === 'view' ? 'readonly' : '' }}>
                                    </div>
                                </div>
                                <small class="form-hint mt-2"><i class="fa-solid fa-info-circle me-1"></i>Must be fully qualified, e.g., module.action</small>
                            </div>
                        </div>
                    @endif
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary non-selectable" data-bs-dismiss="modal">Close</button>
                    @if($modalData['form_mode'] !== 'view')
                        <button type="submit" class="btn btn-primary">
                            @if($modalData['form_mode'] === 'edit')
                                Update Permission
                            @else
                                Create Permissions
                            @endif
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

