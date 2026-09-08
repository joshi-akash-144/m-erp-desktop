@extends('company.layout.app')
@section('title', 'Audit Trails')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    <style>
        
        .first-row-highlight { background-color: #f1f8ff !important; font-weight: bold; }
        .tab-pane-container { margin-top: 15px; }
        
        /* Modal Version Comparison Styling - Enterprise Grade */
        #versionComparisonModal { font-family: 'Inter', sans-serif; }
        #versionComparisonModal .modal-title { font-weight: 700; font-size: 1.25rem; letter-spacing: -0.02em; }
        #versionComparisonModal .modal-content { border: none; border-radius: 16px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
        #versionComparisonModal .modal-header { border-bottom: 1px solid rgba(255,255,255,0.1); padding: 1.5rem; background: #0f172a; color: white; }
        
        .version-val { font-size: 14px; color: #1e293b; }
        .cell-changed { background-color: #fefce8 !important; box-shadow: inset 3px 0 0 0 #eab308 !important; }
        .cell-changed .version-val { font-weight: 700; color: #b45309; }
        .change-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #ca8a04; margin-bottom: 4px; display: block; letter-spacing: 0.5px; }
        /* Tabulator overrides for version comparison */
        #version_comparison_tabulator { font-family: 'Inter', sans-serif; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; font-size: 14px; }
        #version_comparison_tabulator .tabulator-header { background-color: #f1f5f9; border-bottom: 2px solid #cbd5e1; }
        #version_comparison_tabulator .tabulator-header .tabulator-col { background-color: transparent !important; border-right: 1px solid #e2e8f0; }
        #version_comparison_tabulator .tabulator-header .tabulator-col .tabulator-col-content { padding: 12px 16px !important; text-transform: uppercase; font-weight: 700; font-size: 12px; letter-spacing: 0.05em; color: #334155; }
        #version_comparison_tabulator .tabulator-row { background-color: #ffffff !important; border-bottom: 1px solid #e2e8f0; }
        #version_comparison_tabulator .tabulator-row.tabulator-row-even { background-color: #ffffff !important; }
        #version_comparison_tabulator .tabulator-row:hover { background-color: #f8fafc !important; }
        
        #version_comparison_tabulator .tabulator-row .tabulator-cell { padding: 14px 16px; border-right: 1px solid #e2e8f0; vertical-align: middle; white-space: normal; line-height: 1.5; color: #1e293b; }
        #version_comparison_tabulator .tabulator-row .tabulator-cell:first-child { background-color: #f8fafc !important; font-weight: 400; color: #1e293b; border-right: 2px solid #cbd5e1; }

    </style>
@endsection

@section('content')
<div class="page-wrapper">

    {{-- Page Header --}}
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">
                    
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fa-solid fa-history"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Audit Trails
                            </h2>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
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
            <ul class="nav nav-tabs nav-fill mb-3 shadow-sm bg-white rounded" data-bs-toggle="tabs">
                <li class="nav-item">
                    <a href="#tab-summary" class="nav-link active fw-bold text-danger" data-bs-toggle="tab">Audit Trial Summary</a>
                </li>
                <li class="nav-item">
                    <a href="#tab-detail" class="nav-link fw-bold text-danger" data-bs-toggle="tab">Audit Trial Detail</a>
                </li>
            </ul>

            <div class="tab-content">
                {{-- SUMMARY TAB --}}
                <div class="tab-pane active show" id="tab-summary">
                    <div class="card mb-3">
                        <div class="card-body py-2">
                            <form id="audit_summary_filter_form" class="row g-2 align-items-end" autocomplete="off">
                                <div class="col-md-2 col-lg-2">
                                    <label class="form-label fw-bold">From Date</label>
                                    <input type="text" name="from_date" id="summary_from_date" class="form-control date-format" placeholder="DD-MM-YYYY">
                                </div>
                                <div class="col-md-2 col-lg-2">
                                    <label class="form-label fw-bold">To Date</label>
                                    <input type="text" name="to_date" id="summary_to_date" class="form-control date-format" placeholder="DD-MM-YYYY">
                                </div>
                                <div class="col-md-2 col-lg-2">
                                    <label class="form-label fw-bold">Voucher Type</label>
                                    <select name="module" id="summary_module" class="form-select form-select-sm select2">
                                        <option value="">All Modules</option>
                                        <option value="journal">Journal</option>
                                        <option value="payment">Payment</option>
                                        <option value="receipt">Receipt</option>
                                        <option value="purchase_invoice">Purchase Invoice</option>
                                        <option value="sales_invoice">Sales Invoice</option>
                                        <option value="freight_invoice">Freight Invoice</option>
                                    </select>
                                </div>
                                <div class="col-md-2 col-lg-2">
                                    <label class="form-label fw-bold">Action</label>
                                    <select name="action" id="summary_action" class="form-select form-select-sm select2">
                                        <option value="">All Actions</option>
                                        <option value="CREATE">Created</option>
                                        <option value="UPDATE">Updated</option>
                                        <option value="DELETE">Deleted</option>
                                    </select>
                                </div>
                                <div class="col-md-2 col-lg-2">
                                    <label class="form-label fw-bold">Search</label>
                                    <input type="text" name="search" id="summary_search" class="form-control form-control-sm" placeholder="User or Voucher...">
                                </div>
                                <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn btn-primary waves-effect">
                                        <i class="fa-solid fa-filter me-1"></i> Show
                                    </button>
                                    <button type="button" id="summary_filter_clear" class="btn btn-outline-secondary waves-effect">
                                        <i class="fa-solid fa-rotate-left me-1"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div id="summary_output"></div>
                </div>

                {{-- DETAIL TAB --}}
                <div class="tab-pane" id="tab-detail">
                    <div class="card mb-3">
                        <div class="card-body py-2">
                            <form id="audit_detail_filter_form" class="row g-2 align-items-end" autocomplete="off">
                                <div class="col-md-3 d-flex align-items-center gap-2">
                                    <label class="form-label fw-bold mb-0">Select Type:</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="type" id="type_voucher" value="voucher" checked>
                                        <label class="btn btn-outline-success btn-sm" for="type_voucher">
                                            <i class="fa-solid fa-file-invoice me-1"></i> Voucher
                                        </label>
                                        <input type="radio" class="btn-check" name="type" id="type_master" value="master">
                                        <label class="btn btn-outline-primary btn-sm" for="type_master">
                                            <i class="fa-solid fa-database me-1"></i> Master
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-2 col-lg-2">
                                    <label class="form-label fw-bold">Voucher Type</label>
                                    <select name="module" id="detail_module" class="form-select form-select-sm select2">
                                        <option value="">All Modules</option>
                                        <option value="journal">Journal</option>
                                        <option value="payment">Payment</option>
                                        <option value="receipt">Receipt</option>
                                        <option value="purchase_invoice">Purchase Invoice</option>
                                        <option value="sales_invoice">Sales Invoice</option>
                                        <option value="freight_invoice">Freight Invoice</option>
                                    </select>
                                </div>
                                <div class="col-md-2 col-lg-2">
                                    <label class="form-label fw-bold">Action</label>
                                    <select name="action" id="detail_action" class="form-select form-select-sm select2">
                                        <option value="">All Actions</option>
                                        <option value="CREATE">Created</option>
                                        <option value="UPDATE">Updated</option>
                                        <option value="DELETE">Deleted</option>
                                    </select>
                                </div>
                                <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2 ms-auto">
                                    <button type="submit" class="btn btn-primary waves-effect w-100">
                                        <i class="fa-solid fa-filter me-1"></i> Show
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div id="detail_output"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="versionComparisonModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document" style="max-width: 96%;">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Version Comparison</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 bg-white">
                <div id="version_comparison_tabulator"></div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
    const AUDIT_SUMMARY_URL = "{{ route('fas.audit-trails.list-summary') }}";
    const AUDIT_DETAIL_URL = "{{ route('fas.audit-trails.list') }}";
    const AUDIT_SHOW_URL = "{{ url('fas/audit-trails') }}";
</script>
<script src="{{ asset('js/modules/fas/audit-trails.js') }}?v={{ hash_file('md5', public_path('js/modules/fas/audit-trails.js')) }}"></script>
@endsection
