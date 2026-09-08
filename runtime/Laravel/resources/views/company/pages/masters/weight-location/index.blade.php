@extends('company-selector.layout.app')
@section('title', 'Weight Location - List')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}"></link>
<style>
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #fa6969 !important;
    }
    .select2-container--bootstrap-5.select2-container--focus .select2-selection,
    .select2-container--bootstrap-5.select2-container--open .select2-selection {
        box-shadow: none !important;
        border-color: #ced4da !important;
        border-radius: 0 !important;
    }
    .select2-container--bootstrap-5 .select2-selection {
        border-radius: 0 !important;
        min-height: calc(1.5em + 0.75rem + -1px) !important;
    }
    .select2-container .select2-selection--single { height: 10px !important; }
    .select2-container--bootstrap-5 .select2-search__field:focus { box-shadow: none !important; border-radius: 0 !important; }
    .select2-container--bootstrap-5 .select2-dropdown .select2-results__options .select2-results__option.select2-results__option--selected {
        color: #fff !important;
        background-color: #066fd1 !important;
    }
</style>
@endsection

@section('content')
    <div class="page-wrapper">
         <div class="page-body pt-4">
            <div class="container-xl">
                <!-- ✅ Page Header -->
                {{-- <div class="page-header d-print-none">
                    <div class="container-xl">
                        <div class="row g-2 align-items-center justify-content-between">

                            <!-- 🔹 Title & Subtitle -->
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="card border-0 border-start border-4 border-primary">
                                <div class="card-body shadow p-2">
                                    <h3 class="page-title font-monospace"><i class="fa-solid fa-scale-balanced text-primary"></i>&nbsp;Weight Location List</h3>
                                </div>
                            </div>
                            </div>

                            <!-- 🔹 Action Buttons (Export & Add) -->
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                                <div class="card border-0 border-end border-4 border-primary">
                                    <div class="card-body shadow-sm p-2">
                                        <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                            
                                            <div class="waves-effect">
                                                <a href="#" class="btn btn-outline-primary btn-sm js-load-modal"
                                                    data-module="masters/weight-location/modal" data-init="openWeightLocationCreateModal">
                                                    @include('icons.plus', ['size' => 20])
                                                    Add Weight Location
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> --}}

                 {{-- ── HERO BANNER ── --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="position-relative"
                        style="height:60px;background:linear-gradient(120deg,#1a56db 0%,#4dabf7 60%,#74c0fc 100%);border-radius:12px 12px 0 0;">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-3 border border-4 border-white shadow position-absolute text-white"
                            style="background:linear-gradient(135deg,#1a56db,#4dabf7);width:76px;height:76px;bottom:-38px;left:24px;">
                            <i class="fa-solid fa-weight-hanging" style="font-size:26px;"></i>
                        </span>
                    </div>
                    <div class="px-4 pb-3" style="padding-top:48px !important;">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <h3 class="mb-0 fw-bold">Weight Location List</h3>
                                <div class="text-muted small mt-1">Manage Weight Location</div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="{{ route('company-selection.index') }}"
                                    class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                                    @include('icons.prev', ['size' => 15])
                                    Dashboard
                                </a>
                                <a href="#" class="btn btn-outline-primary btn-sm js-load-modal"
                                    data-module="masters/weight-location/modal" data-init="openWeightLocationCreateModal">
                                    @include('icons.plus', ['size' => 20])
                                    Add Weight Location
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ✅ Page Body -->
                <div class="card border-0 shadow-sm rounded-4">                                           
        
                    {{-- Filters header --}}
                    <div class="card-header bg-transparent border-bottom px-4 py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-flex align-items-center justify-content-center rounded-3 bg-secondary-subtle text-secondary"
                                style="width:32px;height:32px;flex-shrink:0;">
                                @include('icons.filter', ['size' => 16])
                            </span>
                            <div class="fw-semibold lh-1">
                                Filters
                                <span id="active-filter-badge" class="badge bg-primary ms-1 d-none" style="font-size:11px;">
                                    <span id="filter-count">0</span> Active
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 🔹 Collapsible Filter Form -->
                    <div id="filterCollapse">
                        <div class="card-body border-bottom">
                            <div class="row g-3">

                                <!-- Search Input -->
                                <div class="col-md-4 col-lg-3">
                                    <label class="form-label fw-bold">Search</label>
                                    <div class="input-group input-group-flat">
                                        <span class="input-group-text">
                                            @include('icons.search', ['size' => 15])
                                        </span>
                                        <input type="text" id="search" class="form-control" placeholder="Search..."
                                            autocomplete="off">
                                    </div>
                                </div>

                                <!-- Weight Location Dropdown -->
                                <div class="col-md-4 col-lg-3">
                                    <label class="form-label fw-bold">Select Weight Location</label>
                                    <select id="weight_location_id" class="form-select select2">
                                        <option value="">All Weight Locations</option>
                                        @foreach ($weight_locations as $weight_location)
                                            <option value="{{ $weight_location->id }}">{{ $weight_location->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Filter Buttons -->
                                <div class="col-md-4 col-lg-3 d-flex align-items-end gap-2">
                                    <button id="filter_apply" class="btn btn-primary flex-fill waves-effect">
                                        @include('icons.filter', ['size' => 20])
                                        Apply
                                    </button>
                                    <button id="filter_clear" class="btn btn-outline-secondary flex-fill waves-effect">
                                        @include('icons.filter-clear', ['size' => 20])
                                        Clear
                                    </button>
                                </div>
                            </div>

                            <!-- Active Filters Display -->
                            <div id="active-filters-display" class="mt-3 d-none">
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <small class="text-muted">Active filters:</small>
                                    <div id="filter-tags" class="d-flex flex-wrap gap-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 🔹 Weight Location Table -->
                    <div class="card-body p-0">
                        <div id="weight_location_table">
                            <!-- Table content will be loaded dynamically via JS -->
                        </div>
                    </div>

                    <!-- 🔹 Infinite Scroll Loader -->
                    <div id="scrollLoader" class="card-footer text-center" style="display:none;">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span class="ms-2 text-muted">Loading more records...</span>
                    </div>                    
                </div>
            </div>
        </div>
    </div>
@include('company.partials._shortcuts-bar')
@endsection
@section('scripts')
<script>
    const weightLocationListRoute = "{{ route('weight_location.index') }}";
    const weightLocationCreateUrl = "{{ route('weight_location.create') }}";
    const weightLocationStoreUrl = "{{ route('weight_location.store') }}";
    const weightLocationEditUrl = "{{ route('weight_location.edit', ':id') }}";
    const weightLocationUpdateUrl = "{{ route('weight_location.update', ':id') }}";
</script>
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
    
    <script src="{{ asset('js/master/weight-location/index.js') }}?v={{ hash_file('md5', public_path('js/master/weight-location/index.js')) }}"></script>  
@endsection