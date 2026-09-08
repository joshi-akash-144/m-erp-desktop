@extends('company.layout.app')
@section('title', 'Challan Bags Entry - List')

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
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-godown rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-godown-lt text-godown rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-bag-shopping"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-godown fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Challan Bags Entry
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
            <form id="store-form">
                <input type="hidden" name="uuid" id="uuid" value="{{ $uuid }}">
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
                                            value="{{ date('d-m-Y') }}">
                                    </div>
                                    <!-- From Destnation -->
                                    <div class="col-md-4 col-lg-2">
                                        <label for="item_id" class="fs-4 fw-bold">Item</label>
                                        <select name="item_id" id="item_id" class="form-control">
                                            <option value="">Select Item</option>
                                            @foreach ($items as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        </select>
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

                        <!-- 🔹 Challan Bags Entry Table -->
                        <div class="card-body p-0">
                            <div id="challan_bags_entry_table">
                                <!-- Table content will be loaded dynamically via JS -->
                            </div>
                        </div>

                        <div class="card-footer p-3 bg-light">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-2">
                                    <label class="form-label fw-bold" for="bags">Total Bags</label>
                                    <input type="text" name="bags" id="bags" class="form-control bg-light" readonly>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold" for="rate">Rate</label>
                                    <input type="text" name="rate" id="rate" class="form-control">
                                </div>   
                                <div class="col-md-2">
                                    <label class="form-label fw-bold" for="amount">Amount</label>
                                    <input type="text" name="amount" id="amount" class="form-control bg-white" readonly>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold" for="loading_charges">Loading Charges</label>
                                    <input type="text" name="loading_charges" id="loading_charges" class="form-control bg-white">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold" for="total_amount">Total Amount</label>
                                    <input type="text" name="total_amount" id="total_amount" class="form-control bg-light" readonly>
                                </div>
                                <div class="col-md-1">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fa-solid fa-floppy-disk me-2"></i> Save
                                    </button>
                                </div>
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
        const BagChallanLabourUrl = "{{ route('bag-challan-labour.create') }}";
        const BagChallanLabourStoreUrl = "{{ route('bag-challan-labour.store') }}";
    </script>
    
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>    
    <script src="{{ asset('js/modules/bag-challan-labour/create.js') }}?v={{ hash_file('md5', public_path('js/modules/bag-challan-labour/create.js')) }}"></script>
@endsection