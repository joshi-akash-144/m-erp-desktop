@extends('company.layout.app')
@section('title', 'Day To Day Import Register')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
@endsection

@section('content')
<div class="page-wrapper">

    {{-- Page Header --}}
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                            style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-table-list"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Day To Day Import Register
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-3 py-1"
                                style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-list me-1"></i> Register
                            </span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('back.to.previous') }}"
                            class="btn btn-sm btn-outline-dark back-btn waves-effect rounded-0 fw-medium shadow-sm">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Page Body --}}
    <div class="page-body">
        <div class="container-xl">
            <div class="card">

                {{-- Filters --}}
                <form id="filter_form">
                    <div class="p-3">
                        <div class="row g-2">

                            <div class="col-md-2 col-lg-1">
                                <label class="filter-label fw-bold">Import Date</label>
                                <input type="text" id="import_date" class="form-control form-control-sm" placeholder="DD-MM-YYYY">
                            </div>

                            <div class="col-md-2 col-lg-1">
                                <label class="filter-label fw-bold">Billing Start Date</label>
                                <input type="text" id="start_date" class="form-control form-control-sm" placeholder="DD-MM-YYYY">
                            </div>

                            <div class="col-md-2 col-lg-1">
                                <label class="filter-label fw-bold">Billing End Date</label>
                                <input type="text" id="end_date" class="form-control form-control-sm" placeholder="DD-MM-YYYY">
                            </div>

                            <div class="col-md-3 col-lg-2">
                                <label class="filter-label fw-bold">Zone</label>
                                <select id="zone_id" class="form-select form-select-sm select2" data-placeholder="All Zones">
                                    <option value="">All Zones</option>
                                    @foreach ($zones as $zone)
                                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 col-lg-2">
                                <label class="filter-label fw-bold">Vehicle</label>
                                <select id="vehicle_id" class="form-select form-select-sm select2" data-placeholder="All Vehicles">
                                    <option value="">All Vehicles</option>
                                    @foreach ($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}">{{ $vehicle->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 col-lg-2">
                                <label class="filter-label fw-bold">Product</label>
                                <select id="product_id" class="form-select form-select-sm select2" data-placeholder="All Products">
                                    <option value="">All Products</option>
                                    @foreach ($items as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2 col-lg-1">
                                <label class="filter-label fw-bold">Status</label>
                                <select id="is_used" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    <option value="0" selected>Not Used</option>
                                    <option value="1">Used</option>
                                </select>
                            </div>

                            <div class="col-md-auto d-flex align-items-end gap-2">
                                <button type="submit" id="filter_apply" class="btn btn-primary btn-sm waves-effect">
                                    @include('icons.filter', ['size' => 16])
                                    Apply
                                </button>
                                <button type="button" id="filter_clear" class="btn btn-outline-secondary btn-sm waves-effect">
                                    @include('icons.filter-clear', ['size' => 16])
                                    Clear
                                </button>
                                <button type="button" id="bulk_delete_btn" class="btn btn-danger btn-sm waves-effect">
                                    <i class="fa-solid fa-trash-can me-1"></i> Delete All Filtered
                                </button>
                            </div>

                        </div>
                    </div>
                </form>

                {{-- Tabulator --}}
                <div class="card-body p-0">
                    <div id="day_to_day_register_table"></div>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection

@section('script')
    <script>
        const registerListUrl       = "{{ route('dairy-file-import.register.list') }}";
        const registerDestroyUrl    = "{{ route('dairy-file-import.register.destroy', ':id') }}";
        const registerBulkDeleteUrl = "{{ route('dairy-file-import.register.bulk-delete') }}";
    </script>
    <script src="{{ asset('js/modules/dairy-file-import/register.js') }}?v={{ time() }}"></script>
@endsection
