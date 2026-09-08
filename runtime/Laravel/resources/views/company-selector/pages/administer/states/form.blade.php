@extends('company-selector.layout.app')

@section('content')
<div class="page-wrapper">
    <div class="page-body pt-4">
        <div class="container-xl">

            {{-- ── HERO BANNER ── --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="position-relative"
                    style="height:60px;background:linear-gradient(120deg,#1a56db 0%,#4dabf7 60%,#74c0fc 100%);border-radius:12px 12px 0 0;">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 border border-4 border-white shadow position-absolute text-white"
                        style="background:linear-gradient(135deg,#1a56db,#4dabf7);width:76px;height:76px;bottom:-38px;left:24px;">
                        <i class="fa-solid fa-city" style="font-size:28px;"></i>
                    </span>
                </div>
                <div class="px-4 pb-3" style="padding-top:48px !important;">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <h3 class="mb-0 fw-bold">
                                    @if($mode === 'edit') Edit State
                                    @elseif($mode === 'view') View State
                                    @else New State
                                    @endif
                                </h3>
                                @if(in_array($mode, ['edit', 'view']) && isset($state))
                                    @if($state->status)
                                        <span class="badge bg-success-lt text-success rounded-pill px-2">Active</span>
                                    @else
                                        <span class="badge bg-danger-lt text-danger rounded-pill px-2">Inactive</span>
                                    @endif
                                @endif
                            </div>
                            <div class="text-muted small mt-1">
                                @if($mode === 'create')
                                    Add a new state with its code and GST details
                                @else
                                    {{ $state->name ?? '' }}
                                    @if(isset($state->code))
                                        &nbsp;·&nbsp; Code: {{ $state->code }}
                                    @endif
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('states.index') }}"
                            class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                            @include('icons.prev', ['size' => 15])
                            Back to States
                        </a>
                    </div>
                </div>
            </div>

            {{-- ── FORM CARD ── --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-bottom px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary"
                            style="width:36px;height:36px;flex-shrink:0;">
                            <i class="fa-solid fa-city fa-lg"></i>
                        </span>
                        <div>
                            <div class="fw-bold lh-1">State Information</div>
                            <div class="text-muted small">
                                @if($mode === 'edit') Update state details
                                @elseif($mode === 'view') Viewing state details
                                @else Enter details for the new state
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <form id="stateForm" data-mode="{{ $mode ?? 'create' }}">
                    @csrf

                    @if(isset($state))
                        <input type="hidden" name="id" id="id" value="{{ $state->id }}">
                    @endif

                    <div class="card-body p-4">
                        <div class="row g-4">

                            {{-- Name --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">State Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent">
                                        <i class="fa-solid fa-location-dot text-muted"></i>
                                    </span>
                                    <input type="text" name="name" id="name" class="form-control"
                                        value="{{ $state->name ?? '' }}"
                                        placeholder="Enter state name"
                                        {{ ($mode ?? '') === 'view' ? 'readonly' : '' }}>
                                </div>
                                <div id="name-error"></div>
                            </div>

                            {{-- Code --}}
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">State Code <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent">
                                        <i class="fa-solid fa-hashtag text-muted"></i>
                                    </span>
                                    <input type="text" name="code" id="code" class="form-control"
                                        value="{{ $state->code ?? '' }}"
                                        placeholder="e.g. MH"
                                        {{ ($mode ?? '') === 'view' ? 'readonly' : '' }}>
                                </div>
                                <div id="code-error"></div>
                            </div>

                            {{-- GST Code --}}
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">GST Code <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent">
                                        <i class="fa-solid fa-percent text-muted"></i>
                                    </span>
                                    <input type="text" name="gst_code" id="gst_code" class="form-control"
                                        value="{{ $state->gst_code ?? '' }}"
                                        placeholder="e.g. 27"
                                        {{ ($mode ?? '') === 'view' ? 'readonly' : '' }}>
                                </div>
                                <div id="gst_code-error"></div>
                            </div>

                            {{-- Status (edit / view only) --}}
                            @if(in_array($mode ?? '', ['edit', 'view']))
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="w-100">
                                    <label class="form-label fw-semibold">Status</label>
                                    <label class="form-check form-switch form-switch-lg d-flex align-items-center gap-2 mb-0">
                                        <input type="hidden" name="status" value="0">
                                        <input class="form-check-input" type="checkbox" name="status" id="stateStatus"
                                            value="1"
                                            {{ isset($state) && $state->status ? 'checked' : '' }}
                                            {{ ($mode ?? '') === 'view' ? 'disabled' : '' }}>
                                        <span id="statusLabel" class="form-check-label fw-semibold">
                                            {{ isset($state) && $state->status ? 'Active' : 'Inactive' }}
                                        </span>
                                    </label>
                                </div>
                            </div>
                            @endif

                        </div>
                    </div>

                    {{-- Footer --}}
                    @if(($mode ?? '') !== 'view')
                    <div class="card-footer bg-transparent border-top px-4 py-3">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('states.index') }}"
                                class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M15 6l-6 6l6 6"/>
                                </svg>
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary form-save-btn d-inline-flex align-items-center gap-1" id="submitBtn">
                                <i class="fa-solid fa-circle-check"></i>
                                {{ ($mode ?? '') === 'edit' ? 'Update State' : 'Save State' }}
                            </button>
                        </div>
                    </div>
                    @endif

                </form>
            </div>

        </div>
    </div>
</div>
@include('company.partials._shortcuts-bar')
@endsection

@section('scripts')
<script>
    const createStateUrl = "{{ route('states.store') }}";
    const updateStateUrl = "{{ route('states.update', ':id') }}";
    const statesIndexUrl = "{{ route('states.index') }}";
</script>
<script src="{{ asset('js/master/state/form.js') }}?v={{ hash_file('md5', public_path('js/master/state/form.js')) }}"></script>
@endsection
