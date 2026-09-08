@extends('company.layout.app')

@section('title', 'Payment Approval – List')
@section('css')
<style>
    .m-erp-so-row-1 {
        grid-template-columns: 190px 190px 190px 0.5fr 190px;
    }
</style>
    <link rel="stylesheet"
        href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <link rel="stylesheet"
        href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
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
                            <i class="fs-3 fa-solid fa-file-invoice"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Payment Approve
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-red-lt fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-chart-line"></i> Bank Transaction
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                    @can('payment_online_rtgs.create')
                        <a href="{{ route('payment-online-rtgs.index') }}" class="btn btn-outline-info btn-sm fw-bold">
                            <i class="fas fa-building-columns me-1"></i>
                            Online RTGS
                        </a>
                        @endcan
                        <a href="{{ route('back.to.previous') }}"
                            class="btn btn-sm btn-outline-dark back-btn waves-effect">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
    {{-- <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center justify-content-between">

                    <!-- 🔹 Title & Subtitle -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="card border-0 border-start border-4 border-primary">
                            <div class="card-body shadow p-2">
                                <h3 class="page-title font-monospace">
                                    <i class="fa-solid fa-file-invoice me-2 text-primary"></i>
                                    Payment Approve
                                </h3>
                                <span class="ribbon ribbon-bookmark bg-blue">
                                    <i class="fa-solid fa-eye me-1"></i> LIST DATA
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons (Export & Add) -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                        <div class="card border-0 border-end border-4 border-primary">
                            <div class="card-body shadow-sm p-2">
                                <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">

                                    <div class="waves-effect d-flex gap-2">
                                        @can('payment_online_rtgs.create')
                                        <a href="{{ route('payment-online-rtgs.index') }}" class="btn btn-outline-info btn-sm fw-bold">
                                            <i class="fas fa-building-columns me-1"></i>
                                            Online RTGS
                                        </a>
                                        @endcan
                                        <a href="{{ route('back.to.previous') }}"
                                            class="btn btn-sm btn-outline-dark back-btn waves-effect">
                                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </div> --}}
        <!-- Page Body -->
        <div class="page-body">
            <form method="GET">
                <div class="container-fluid">
                    <div class="card">

                        <!-- 🔹 Filters Section -->
                        <div class="p-3" id="filters">

                            <div class="row g-2 m-erp-so-row-1">
                                <!-- Payment Date -->
                                <div class="col-md-4 col-lg-1">
                                    <label for="payment_date" class="fs-4 fw-bold">Payment Date</label>
                                    <input type="text" name="payment_date" id="payment_date" class="form-control" placeholder="DD-MM-YYYY">
                                </div>

                                <!-- File number -->
                                <div class="col-md-4 col-lg-1">
                                    <label for="file_no" class="fs-4 fw-bold">File No</label>
                                    <input type="text" name="file_no" id="file_no" class="form-control">
                                </div>

                                <!-- Filter Buttons -->
                                <div class="col-md-4 col-lg-auto d-flex align-items-end gap-2">
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

                        <!-- 🔹 Payment Approval Table -->
                        <div class="card-body p-0">
                            <div id="payment_approval_table">
                                <!-- Table content will be loaded dynamically via JS -->
                            </div>
                        </div>

                        <!-- 🔹 Bottom Bulk Actions Bar -->
                        <div class="card-footer d-flex align-items-center gap-3 py-2 border-top bg-white">
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="checkbox" id="selectAll" class="form-check-input m-0 shadow-none" />
                                    <span class="fw-bold text-dark fs-3 ms-1">All</span>
                                    <span class="text-muted mx-1">|</span>
                                    <span class="fw-bold text-dark fs-3">Total</span>
                                    <div class="badge bg-blue-lt px-3 fs-2 fw-bold text-center" id="selectedTotalAmount"
                                        style="min-width: 250px;">
                                        0.00
                                    </div>
                                </div>
                                @can('payment_approval.update')
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" id="bulkApproveBtn"
                                            class="btn btn-sm btn-primary fw-bold fs-5">
                                           <i class="fas fa-check-circle text-white me-2"></i>
                                            <span class="fw-bold">Approve</span>
                                        </button>
                                    </div>
                                @endcan
                            </div>
                            {{-- <div class="col-9 d-flex align-items-center gap-2">
                                <label for="remarks" class="form-label fw-semibold mb-0 text-nowrap">
                                    <i class="fa-regular fa-note-sticky me-1 text-primary"></i>
                                    Narration
                                </label>
                                <input type="text" placeholder="Click on any row to view narration" id="remarks" name="remarks" class="form-control non-selectable flex-grow-1" readonly />
                            </div> --}}
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
        <div id="billDetailContainer"></div>
    </div>
@endsection
@section('script')
    <script>
        const paymentApprovalListUrl = "{{ route('payments.approved.index') }}";
        const paymentApprove = "{{ route('payments.approved.approve') }}"
        const paymentApprovalDeleteUrl = "{{ route('payments.approved.destroy', ':id') }}";
        const paymentBillDetailUrl = "{{ route('payments.bill-detail.show', ':id') }}";
    </script>
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>

    <script
        src="{{ asset('js/modules/payment/approve.js') }}?v={{ hash_file('md5', public_path('js/modules/payment/approve.js')) }}"></script>
@endsection