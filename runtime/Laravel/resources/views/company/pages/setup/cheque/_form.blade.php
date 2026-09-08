@php
    $route = $formMode === 'create' ? route('cheque.store') : '';
@endphp

<form action="{{ $route }}" id="chequeFormatForm" data-form-mode="{{ $formMode }}" method="POST" autocomplete="off">

    @if ($formMode === 'create')
        <input type="hidden" name="uuid" id="unique_token" value="{{ $cheque->uuid ?? Str::uuid() }}">
    @endif

    <div class="row g-3">
        <!-- Sidebar Settings -->
        <div class="col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label required">Format Name</label>
                        <input type="text" class="form-control" id="formateName" placeholder="e.g. Axis Bank Format"
                            value="{{ $cheque->formate_name ?? '' }}" required>
                    </div>
                    <hr class="my-3">

                    <h4 class="mb-3 text-muted">Print Settings</h4>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label required">Top Margin (px)</label>
                            <input type="number" class="form-control" id="top_margin"
                                value="{{ $cheque->top_margin ?? 0 }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label required">Left Margin (px)</label>
                            <input type="number" class="form-control" id="left_margin"
                                value="{{ $cheque->left_margin ?? 0 }}">
                        </div>
                    </div>

                    <hr class="my-3">

                    <h4 class="mb-3 text-muted">Cheque Settings</h4>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label required">Height (px)</label>
                            <input type="number" class="form-control" id="cheque_height"
                                value="{{ $cheque->cheque_height ?? 300 }}" oninput="setChequeContainerSize()">
                        </div>
                        <div class="col-6">
                            <label class="form-label required">Width (px)</label>
                            <input type="number" class="form-control" id="cheque_width"
                                value="{{ $cheque->cheque_width ?? 1000 }}" oninput="setChequeContainerSize()">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visual Designer -->
        <div class="col-lg-9">
            <div class="card shadow-sm mb-3">
                <div class="card-body p-0">
                    <div class="cheque-canvas-wrapper">
                        <div id="chequeContainer" class="cheque-canvas" style="width: 1000px; height: 300px;">
                            {{-- Draggable items will be injected here --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Properties Table -->
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title text-muted fw-bold">Cheque Properties</h3>
                    <div class="btn-list">
                        <button type="button" class="btn btn-sm btn-outline-primary non-selectable"
                            onclick="addChequePropertiesTableRow()">
                            <i class="fas fa-plus me-1"></i> Add Row
                        </button>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-bordered table-vcenter property-table" id="chequePropertiesTable">
                        <thead>
                            <tr>
                                <th style="width: 120px">Value</th>
                                <th style="width: 80px">Top</th>
                                <th style="width: 80px">Left</th>
                                <th style="width: 80px">Width</th>
                                <th style="width: 80px">Height</th>
                                <th style="width: 100px">Align</th>
                                <th style="width: 150px">Font</th>
                                <th style="width: 80px">Size</th>
                                <th style="width: 100px">Style</th>
                                <th style="width: 100px" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Table rows will be injected here --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ===========================
                       FOOTER BUTTONS
                ============================ -->
    <!-- Footer with Action Buttons -->
    <div class="card-footer bg-light border-top p-3 mt-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted d-flex align-items-center gap-2">
                <i class="fa-solid fa-circle-info text-primary me-1"></i>
                Review all details before saving
                <span class="text-secondary">|</span>
                <span><span class="text-danger">*</span> Fields are required</span>
            </small>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4 form-save-btn waves-effect" data-bs-toggle="tooltip"
                    data-bs-placement="bottom" title="Save (Alt + S)">
                    @if ($formMode === 'edit')
                        <i class="fa-solid fa-pencil me-1"></i>
                    @else
                        <i class="fa-solid fa-floppy-disk me-1"></i>
                    @endif
                    {{ $formMode === 'edit' ? 'Update' : 'Save' }}
                </button>
            </div>
        </div>
    </div>
</form>