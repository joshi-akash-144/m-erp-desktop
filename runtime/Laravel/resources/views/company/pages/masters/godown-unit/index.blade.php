@extends('company.layout.app')
@section('title', 'Godown Unit Location - List')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}"></link>
@endsection

@section('content')
    <div class="page-wrapper">
         <div class="col-lg-10">
                <!-- ✅ Page Header -->
                <div class="page-header d-print-none">
                    <div class="container-xl">
                        <div class="row g-2 align-items-center justify-content-between">

                            <!-- 🔹 Title & Subtitle -->
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="card border-0 border-start border-4 border-primary">
                                <div class="card-body shadow p-2">
                                    <h3 class="page-title font-monospace"><i class="fa-solid fa-scale-balanced text-primary"></i>&nbsp;Godown Unit Location List</h3>
                                </div>
                            </div>
                            </div>

                            <!-- 🔹 Action Buttons (Export & Add) -->
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                                <div class="card border-0 border-end border-4 border-primary">
                                    <div class="card-body shadow-sm p-2">
                                        <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                            {{-- Add Unit Button --}}
                                            <div class="waves-effect">
                                                <a href="#" class="btn btn-outline-primary btn-sm js-load-modal"
                                                    data-module="masters/godown-unit/modal">
                                                    @include('icons.plus', ['size' => 20])
                                                    Add Godown Unit Location
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ✅ Page Body -->
                <div class="page-body">
                        <div class="container-xl">
                            <div class="card">


                                <!-- 🔹 Collapsible Filter Form -->
                                <div id="filterCollapse">
                                    <div class="card-body border-bottom">
                                        <div class="row g-3">

                                            <!-- Search Input -->
                                            <div class="col-md-4 col-lg-3">
                                                <label class="form-label">Search</label>
                                                <div class="input-group input-group-flat">
                                                    <span class="input-group-text">
                                                        @include('icons.search', ['size' => 15])
                                                    </span>
                                                    <input type="text" id="search" class="form-control" placeholder="Search..."
                                                        autocomplete="off">
                                                </div>
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
                                    <div id="godown_unit_location_table">
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
@section('script')
<script>
    const godownUnitLocationListRoute = "{{ route('godown_unit_location.index') }}";
    const godownUnitLocationCreateUrl = "{{ route('godown_unit_location.create') }}";
    const godownUnitLocationStoreUrl = "{{ route('godown_unit_location.store') }}";
    const godownUnitLocationEditUrl = "{{ route('godown_unit_location.edit', ':id') }}";
    const godownUnitLocationUpdateUrl = "{{ route('godown_unit_location.update', ':id') }}";
</script>
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
    
    <script src="{{ asset('js/master/godown-unit-location/index.js') }}?v={{ hash_file('md5', public_path('js/master/godown-unit-location/index.js')) }}"></script>  
@endsection