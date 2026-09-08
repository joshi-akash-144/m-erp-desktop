@extends('company-selector.layout.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/select2.css') }}?v={{ hash_file('md5', public_path('css/select2.css')) }}">
<style>
.select2-container--bootstrap-5.select2-container--focus .select2-selection,
.select2-container--bootstrap-5.select2-container--open .select2-selection {
    box-shadow: none !important; border-color: #ced4da !important;
}
.select2-container--bootstrap-5 .select2-selection { min-height: 36px !important; }
.select2-container--bootstrap-5 .select2-search__field:focus { box-shadow: none !important; }
.select2-container--bootstrap-5 .select2-dropdown .select2-results__options .select2-results__option.select2-results__option--selected {
    color: #fff !important; background-color: #6610f2 !important;
}

/* Module toggle grid */
.module-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
.module-card {
    border: 2px solid #e9ecef; border-radius: 10px; padding: 14px 16px;
    cursor: pointer; transition: all .18s ease; user-select: none; position: relative;
}
.module-card:hover { border-color: #a855f7; background: #faf5ff; }
.module-card.selected { border-color: #6610f2; background: #f3e8ff; }
.module-card .module-icon { font-size: 22px; margin-bottom: 6px; }
.module-card .module-title { font-weight: 600; font-size: 14px; }
.module-card .check-mark {
    position: absolute; top: 8px; right: 10px;
    width: 20px; height: 20px; border-radius: 50%;
    background: #6610f2; color: #fff;
    display: none; align-items: center; justify-content: center; font-size: 11px;
}
.module-card.selected .check-mark { display: flex; }
.module-card input[type="checkbox"] { display: none; }

/* Select all / none bar */
.select-actions { display: flex; gap: 8px; margin-bottom: 12px; }
</style>
@endsection

@section('content')
<div class="page-wrapper">
    <div class="page-body pt-4">
        <div class="container-xl">

            {{-- ── HERO BANNER ── --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="position-relative"
                    style="height:60px;background:linear-gradient(120deg,#6610f2 0%,#a855f7 60%,#c084fc 100%);border-radius:12px 12px 0 0;">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 border border-4 border-white shadow position-absolute text-white"
                        style="background:linear-gradient(135deg,#6610f2,#a855f7);width:76px;height:76px;bottom:-38px;left:24px;">
                        <i class="fa-solid fa-puzzle-piece" style="font-size:28px;"></i>
                    </span>
                </div>
                <div class="px-4 pb-3" style="padding-top:48px !important;">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <h3 class="mb-0 fw-bold">Assign Modules to Company</h3>
                            <div class="text-muted small mt-1">Select a company and choose which modules to enable</div>
                        </div>
                        <a href="{{ route('company-modules.index') }}"
                            class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                            @include('icons.prev', ['size' => 15])
                            Back to List
                        </a>
                    </div>
                </div>
            </div>

            <form id="companyModuleForm">
                @csrf

                {{-- ── SECTION 1: COMPANY SELECT ── --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-transparent border-bottom px-4 py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary"
                                style="width:36px;height:36px;flex-shrink:0;">
                                <i class="fa-solid fa-building fa-lg"></i>
                            </span>
                            <div>
                                <div class="fw-bold lh-1">Select Company</div>
                                <div class="text-muted small">Choose the company to configure modules for</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Company <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent text-muted">
                                        <i class="fa-solid fa-building" style="font-size:14px;"></i>
                                    </span>
                                    <select name="company_id" id="company_id" class="form-select select2-company">
                                        <option value="">Select Company</option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}">
                                                {{ $company->name }}
                                                @if($company->code) ({{ $company->code }}) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="invalid-feedback" id="company_id_error"></div>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="text-muted small">
                                    <i class="fa-solid fa-circle-info me-1 text-primary"></i>
                                    Selecting a company will highlight its currently assigned modules.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── SECTION 2: MODULE SELECTION ── --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-transparent border-bottom px-4 py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-flex align-items-center justify-content-center rounded-3 bg-purple-subtle"
                                style="width:36px;height:36px;flex-shrink:0;background:#f3e8ff;">
                                <i class="fa-solid fa-puzzle-piece" style="color:#6610f2;font-size:16px;"></i>
                            </span>
                            <div>
                                <div class="fw-bold lh-1">Select Modules</div>
                                <div class="text-muted small">Toggle the modules you want to enable for this company</div>
                            </div>
                        </div>
                        <div class="card-actions">
                            <span id="selected-count" class="badge bg-purple text-white" style="background:#6610f2 !important;">0 selected</span>
                        </div>
                    </div>
                    <div class="card-body p-4">

                        <div class="select-actions">
                            <button type="button" id="selectAll" class="btn btn-sm btn-outline-secondary">
                                <i class="fa-solid fa-check-double me-1"></i> Select All
                            </button>
                            <button type="button" id="deselectAll" class="btn btn-sm btn-outline-secondary">
                                <i class="fa-solid fa-xmark me-1"></i> Deselect All
                            </button>
                        </div>

                        <div class="module-grid" id="moduleGrid">
                            @foreach ($modules as $module)
                                <label class="module-card" data-module-id="{{ $module->id }}">
                                    <input type="checkbox" name="module_ids[]" value="{{ $module->id }}">
                                    <div class="check-mark"><i class="fa-solid fa-check"></i></div>
                                    <div class="module-icon">
                                        @if($module->icon)
                                            @if(view()->exists('icons.' . $module->icon))
                                                @include('icons.' . $module->icon, ['size' => 22, 'color' => $module->color ?? '#6610f2'])
                                            @else
                                                <i class="{{ $module->icon }}" style="color:{{ $module->color ?? '#6610f2' }};"></i>
                                            @endif
                                        @else
                                            <i class="fa-solid fa-cube" style="color:#6610f2;"></i>
                                        @endif
                                    </div>
                                    <div class="module-title" style="{{ $module->color ? 'color:'.$module->color : '' }}">
                                        {{ $module->title }}
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <div class="invalid-feedback d-block mt-2" id="module_ids_error"></div>
                    </div>

                    <div class="card-footer bg-transparent border-top px-4 py-3">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('company-modules.index') }}"
                                class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M15 6l-6 6l6 6"/>
                                </svg>
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary form-save-btn d-inline-flex align-items-center gap-1" id="submitBtn">
                                <i class="fa-solid fa-floppy-disk me-1"></i>
                                Save Assignment
                            </button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>
@include('company.partials._shortcuts-bar')
@endsection

@section('scripts')
<script>
    const storeCompanyModuleUrl     = "{{ route('company-modules.store') }}";
    const companyModulesIndexUrl    = "{{ route('company-modules.index') }}";
    const getCompanyModulesUrl      = "{{ route('company-modules.index') }}";
</script>
<script src="{{ asset('js/company-modules/form.js') }}?v={{ filemtime(public_path('js/company-modules/form.js')) }}"></script>
@endsection
