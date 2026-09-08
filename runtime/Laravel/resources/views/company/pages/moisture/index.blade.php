@extends('company.layout.app')
@section('title', 'Moisture - List')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    <style>
        .m-erp-so-row-1 {
            grid-template-columns: 1fr 190px 190px 1fr 1fr 1fr 1fr;
        }

        .m-erp-so-row-2 {
            grid-template-columns: 0.8fr 0.5fr 170px 0.7fr 1fr;
        }

        @media (max-width: 992px) {

            .m-erp-so-row-1,
            .m-erp-so-row-2 {
                grid-template-columns: 1fr;
            }
        }

        .m-erp {
            width: 170px;
        }
        /* Red border on invalid input */
        /* Invalid field styling */
        .tabulator-col, .tabulator-header {
            background-color: #f6f8fb !important;
        }
        .tabulator-col-content {
            height: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
        }
    </style>
@endsection

@section('content')
    <div class="page-wrapper">
        <!-- Page Header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-droplet"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Moisture
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-list"></i> report
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn waves-effect rounded-0 fw-medium shadow-sm">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <!-- Page Body -->
        <div class="page-body">
            <form  method="GET">
                <div class="container-xl">
                    <div class="card">

                        <!-- 🔹 Filters Section -->
                        <div class="p-3" id="filters">
                            
                                <div class="row g-2 m-erp-so-row-1">
                             
                                    <!-- Start Date -->
                                    <div class="col-md-4 col-lg-1">
                                        <label for="start_date" class="fs-4 fw-bold">Start Date</label>
                                        <input type="text" name="date_in" id="start_date" class="form-control" placeholder="DD-MM-YYYY"
                                            value="{{ date('d-m-Y') }}">
                                    </div>

                                    <!-- End Date -->
                                    <div class="col-md-4 col-lg-1">
                                        <label for="end_date" class="fs-4 fw-bold">End Date</label>
                                        <input type="text" name="date_out" id="end_date" class="form-control" placeholder="DD-MM-YYYY"
                                            value="{{ $filters['date_out'] ?? '' }}">
                                    </div>

                                    <!-- Supplier Name -->
                                    <div class="col-md-4 col-lg-3 ">
                                        <label for="account_id" class="fs-4 fw-bold">Name Of Party</label>
                                        <select id="account_id" class="form-select select2">
                                            <option value="">Name Of Party</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}{{ !empty($account->city) ? ' (' . $account->city . ')' : '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Item -->
                                    <div class="col-md-4 col-lg-2">
                                        <label for="item_id" class="fs-4 fw-bold">Item Name</label>
                                        <select id="item_id" class="form-select select2">
                                            <option value="">Select Item</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- GRN Number -->
                                     <div class="col-md-4 col-lg-2 m-erp">
                                        <label for="grn_number" class="fs-4 fw-bold">GRN Number</label>
                                        <input type="number" id="grn_number" name="grn_number" class="form-control" placeholder="Enter GRN Number">
                                    </div>


                                    <!-- Filter Buttons -->
                                    <div class="col-md-4 col-lg-auto d-flex align-items-end gap-2">
                                        <!-- Dyanmic button name for print  -->
                                        <button id="filter_apply" class="btn btn-primary flex-fill">
                                            @include('icons.filter', ['size' => 20])
                                            Apply
                                        </button>
                                        <button id="filter_clear" class="btn btn-outline-secondary flex-fill">
                                            @include('icons.filter-clear', ['size' => 20])
                                            Clear
                                        </button>
                                    </div>

                                </div>                        
                            </div>

                        <!-- 🔹 Collapsible Filter Form -->
                        <div class="collapse" id="filterCollapse">
                            <div class="card-body border-bottom">
                                <!-- Active Filters Display -->
                                <div id="active-filters-display" class="mt-3 d-none">
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <small class="text-muted"></small>
                                        <div id="filter-tags" class="d-flex flex-wrap gap-2"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 🔹 Moisture Table -->
                        <div class="card-body p-0">
                            <div id="moisture_table">
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
            </form>
        </div>
    </div>
@endsection

@section('script')
   
    <script>
        const moistureListUrl = "{{ route('moisture.index') }}";
    </script>
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>    
    <script src="{{ asset('js/modules/moisture/index.js') }}?v={{ hash_file('md5', public_path('js/modules/moisture/index.js')) }}"></script>
@endsection