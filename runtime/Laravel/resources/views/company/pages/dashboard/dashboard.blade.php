@extends('company.layout.app')

@section('title', 'ERP - Dashboard')
@section('css')
<link rel="stylesheet"
    href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
<style>
    .hover-underline-link {
        text-decoration: none !important;
    }

    .hover-underline-link:hover {
        text-decoration: underline !important;
    }

    /* Tabulator custom loader overlay for Dashboard */
    .tabulator .tabulator-loader {
        background-color: rgba(255, 255, 255, 0.9) !important;
        backdrop-filter: blur(2px);
        z-index: 100;
    }

    .tabulator .tabulator-loader .tabulator-loader-msg {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }
</style>
@endsection
@section('content')
@php
$filterLabel = $currentDays === 'today' ? 'Today'
: ($currentDays === 'all' ? 'All'
: ($currentDays == 90 ? 'Last 3 months'
: 'Last ' . $currentDays . ' days'));
@endphp
<div class="page-wrapper">

    <!-- Global Dashboard Loader -->
    <!-- <div id="dashboard-loader" class="position-fixed top-0 start-0 w-100 h-100 d-none align-items-center justify-content-center" 
             style="background: #ffffffb3; z-index: 9999;">
            <div class="spinner-border text-primary" style="width: 50px; height: 50px;" role="status"></div>
        </div> -->

    <div class="page-body">

        <div class="container-xl">

            @canany(['payment_approval.list', 'dashboard.sales_dairy_hisab', 'manual_cheque.list', 'dashboard.creditor', 'dashboard.debtor'])
            <div class="card mb-3">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <div class="subheader fw-bold text-uppercase text-primary me-3">
                            <i class="fas fa-bolt me-1"></i> Quick Links
                        </div>
                        @can('payment_approval.list')
                        <a class="btn btn-sm btn-purple form-save-btn fw-bold fs-5" href="{{ route('payments.approved.index') }}">
                            <i class="fas fa-check-circle me-1"></i> Payment Approval
                        </a>
                        @endcan
                       @can('dashboard.sales_dairy_hisab')
                        <a class="btn btn-sm btn-teal form-save-btn fw-bold fs-5" href="{{ route('sales-invoice-receipts.index') }}">
                            <i class="fas fa-receipt me-1"></i> Sales Dairy Hisab
                        </a>
                        @endcan
                        @can('manual_cheque.list')
                        <a class="btn btn-sm btn-indigo form-save-btn fw-bold fs-5 ms-auto" href="{{ route('manual-cheques.index') }}">
                            <i class="fas fa-money-check me-1"></i> Manual Cheque
                        </a>
                        @endcan
                        @can('dashboard.creditor')
                        <button class="btn btn-sm btn-danger form-save-btn fw-bold fs-5 ms-2" onclick="openCreditorModal()">
                            <i class="fas fa-users me-1"></i> Creditor
                        </button>
                        @endcan
                        @can('dashboard.debtor')
                        <button class="btn btn-sm btn-success form-save-btn fw-bold fs-5 ms-2" onclick="openDebtorModal()">
                            <i class="fas fa-users me-1"></i> Debtor
                        </button>
                        @endcan
                        @can('sales_purchase_analysis.list')
                         <a class="btn btn-sm btn-success form-save-btn fw-bold fs-5 ms-2" href="{{ route('sales-purchase-analysis.index') }}">
                            <i class="fas fa-money-check me-1"></i> Dairy PO Analysis
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
            @endcan
            <div class="row row-deck row-cards">
                <div class="col-lg-9">
                    <div class="row row-deck row-cards">
                        <div class="col-sm-12 col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <div class="row gy-3">
                                    <div class="col-12 col-sm d-flex flex-column">
                                        <div class="mb-3">
                                            <h3 class="h2">Welcome back, <span class="text-red">{{ Auth::user()->name ?? 'User' }}</span></h3>
                                        </div>
                                        {{-- <div class="row g-2 g-md-4 mt-auto">
                                            <div class="col-6 col-md-auto">
                                                <div class="subheader fw-bold text-uppercase text-primary">Today's Sales
                                                </div>
                                                <div class="d-flex align-items-baseline">
                                                    <div class="h3 mb-0 me-2" id="stat-today-sales">0</div>
                                                    <div class="me-auto">
                                                        <!-- <span id="stat-sales-growth-container" class="text-green d-inline-flex align-items-center lh-1">
                                                                <span id="stat-sales-growth-abs">0</span>%
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon ms-1 icon-2">
                                                                    <path id="stat-sales-growth-path-1" d="M3 17l6 -6l4 4l8 -8" />
                                                                    <path id="stat-sales-growth-path-2" d="M14 7l7 0l0 7" />
                                                                </svg>
                                                            </span> -->
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-auto">
                                                <div class="subheader fw-bold text-uppercase text-primary">Sales Growth
                                                </div>
                                                <div class="d-flex align-items-baseline">
                                                    <div class="h3 mb-0 me-2" id="stat-sales-growth-perc">0%</div>
                                                    <div class="me-auto">
                                                        <!-- <span id="stat-sales-growth-alt-container" class="text-green d-inline-flex align-items-center lh-1">
                                                                <span id="stat-sales-growth-alt">0%</span>
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon ms-1 icon-2">
                                                                    <path id="stat-sales-growth-alt-path-1" d="M3 17l6 -6l4 4l8 -8" />
                                                                    <path id="stat-sales-growth-alt-path-2" d="M14 7l7 0l0 7" />
                                                                </svg>
                                                            </span> -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div> --}}
                                    </div>
                                    <div class="col-12 col-sm-auto d-flex justify-content-center p-0">
                                        <a href="#" class="p-3">
                                            @include('company.pages.dashboard.svg.illustrations')
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>               
                @can('dashboard.total_users')
                    <div class="col-sm-6 col-lg-4">
                        <div class="card">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-center">
                                    <div class="subheader fw-bold text-uppercase text-primary">Total Users<i
                                            class="fa-solid fa-user ms-2"></i></div>
                                    <div class="ms-auto lh-1">
                                        <div class="dropdown">
                                            <a class="dropdown-toggle text-secondary" id="users-dropdown" href="#"
                                                data-days="{{ $currentDays }}" data-bs-toggle="dropdown"
                                                aria-haspopup="true" aria-expanded="false">{{ $filterLabel }}</a>
                                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="users-dropdown">
                                                <a class="dropdown-item filter-days {{ $currentDays == 'today' ? 'active' : '' }}"
                                                    href="#" data-days="today" data-filter="users">Today</a>
                                                <a class="dropdown-item filter-days {{ $currentDays == 7 ? 'active' : '' }}"
                                                    href="#" data-days="7" data-filter="users">Last 7 days</a>
                                                <a class="dropdown-item filter-days {{ $currentDays == 30 ? 'active' : '' }}"
                                                    href="#" data-days="30" data-filter="users">Last 30 days</a>
                                                <a class="dropdown-item filter-days {{ $currentDays == 90 ? 'active' : '' }}"
                                                    href="#" data-days="90" data-filter="users">Last 3 months</a>
                                                <a class="dropdown-item filter-days {{ $currentDays == 'all' ? 'active' : '' }}"
                                                    href="#" data-days="all" data-filter="users">All</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-baseline">
                                    <div class="h1 mb-0 me-2" id="stat-total-users">0</div>
                                    <div class="me-auto">
                                        <!-- <span id="stat-new-clients-growth-container" class="text-green d-inline-flex align-items-center lh-1">
                                                <span id="stat-new-clients-growth-abs">0</span>%
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon ms-1 icon-2">
                                                    <path id="stat-new-clients-growth-path-1" d="M3 17l6 -6l4 4l8 -8" />
                                                    <path id="stat-new-clients-growth-path-2" d="M14 7l7 0l0 7" />
                                                </svg>
                                            </span> -->
                                    </div>
                                </div>
                            </div>
                            <div id="chart-visitors" class="chart-sm mt-auto"></div>
                        </div>
                    </div>
                @endcan


                <!-- Sales Revenue Row -->
                @can('dashboard.sales_revenue') 
                    <div class="col-sm-6 col-lg-4">
                        <div class="card">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-center">
                                    <div class="subheader fw-bold text-uppercase text-primary">Sales Revenue <i
                                            class="fa-solid fa-tags ms-2"></i></div>
                                    <div class="ms-auto lh-1">
                                        <div class="dropdown">
                                            <a class="dropdown-toggle text-secondary" id="revenue-dropdown" href="#"
                                                data-days="{{ $currentDays }}" data-bs-toggle="dropdown"
                                                aria-haspopup="true" aria-expanded="false"
                                                aria-label="Select time range for revenue">{{ $filterLabel }}</a>
                                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="revenue-dropdown">
                                                <a class="dropdown-item filter-days {{ $currentDays == 'today' ? 'active' : '' }}"
                                                    href="#" data-days="today" data-filter="sales">Today</a>
                                                <a class="dropdown-item filter-days {{ $currentDays == 7 ? 'active' : '' }}"
                                                    href="#" data-days="7" data-filter="sales">Last 7 days</a>
                                                <a class="dropdown-item filter-days {{ $currentDays == 30 ? 'active' : '' }}"
                                                    href="#" data-days="30" data-filter="sales">Last 30 days</a>
                                                <a class="dropdown-item filter-days {{ $currentDays == 90 ? 'active' : '' }}"
                                                    href="#" data-days="90" data-filter="sales">Last 3 months</a>
                                                <a class="dropdown-item filter-days {{ $currentDays == 'all' ? 'active' : '' }}"
                                                    href="#" data-days="all" data-filter="sales">All</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-baseline">
                                    <div class="h1 mb-0 me-2" id="stat-total-revenue">₹0</div>
                                    <div class="me-auto">
                                        <!-- <span id="stat-revenue-growth-container" class="text-green d-inline-flex align-items-center lh-1">
                                                <span id="stat-revenue-growth-perc">0%</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon ms-1 icon-2">
                                                    <path id="stat-revenue-growth-path-1" d="M3 17l6 -6l4 4l8 -8" />
                                                    <path id="stat-revenue-growth-path-2" d="M14 7l7 0l0 7" />
                                                </svg>
                                            </span> -->
                                    </div>
                                </div>
                            </div>
                            <div id="chart-revenue-bg" class="chart-sm mt-auto"></div>
                        </div>
                    </div>
                @endcan
                <!-- Purchase Revenue Row -->
                @can('dashboard.purchase_revenue') 
                    <div class="col-sm-6 col-lg-4">
                        <div class="card">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-center">
                                    <div class="subheader fw-bold text-uppercase text-primary">Purchase Revenue <i
                                            class="fa-solid fa-shopping-cart ms-2"></i></div>
                                    <div class="ms-auto lh-1">
                                        <div class="dropdown">
                                            <a class="dropdown-toggle text-secondary" id="purchase-dropdown" href="#"
                                                data-days="{{ $currentDays }}" data-bs-toggle="dropdown"
                                                aria-haspopup="true" aria-expanded="false">{{ $filterLabel }}</a>
                                            <div class="dropdown-menu dropdown-menu-end"
                                                aria-labelledby="purchase-dropdown">
                                                <a class="dropdown-item filter-days {{ $currentDays == 'today' ? 'active' : '' }}"
                                                    href="#" data-days="today" data-filter="purchase">Today</a>
                                                <a class="dropdown-item filter-days {{ $currentDays == 7 ? 'active' : '' }}"
                                                    href="#" data-days="7" data-filter="purchase">Last 7 days</a>
                                                <a class="dropdown-item filter-days {{ $currentDays == 30 ? 'active' : '' }}"
                                                    href="#" data-days="30" data-filter="purchase">Last 30 days</a>
                                                <a class="dropdown-item filter-days {{ $currentDays == 90 ? 'active' : '' }}"
                                                    href="#" data-days="90" data-filter="purchase">Last 3 months</a>
                                                <a class="dropdown-item filter-days {{ $currentDays == 'all' ? 'active' : '' }}"
                                                    href="#" data-days="all" data-filter="purchase">All</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-baseline">
                                    <div class="h1 mb-0 me-2" id="stat-total-expense">₹0</div>
                                    <div class="me-auto">
                                        <!-- <span class="text-green d-inline-flex align-items-center lh-1">
                                                0%
                                            </span> -->
                                    </div>
                                </div>
                            </div>
                            <div id="chart-purchase-revenue-bg" class="chart-sm mt-auto"></div>
                        </div>
                    </div>
                @endcan
                <!-- GRN Revenue Row -->
                @can('dashboard.grn_revenue')                 
                    <div class="col-sm-6 col-lg-4">  
                        <div class="card">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex align-items-center">
                                        <div class="subheader fw-bold text-uppercase text-primary">GRN Revenue <i
                                                class="fa-solid fa-truck-loading ms-2"></i></div>
                                        <div class="ms-auto lh-1">
                                            <div class="dropdown">
                                                <a class="dropdown-toggle text-secondary" id="grn-dropdown" href="#"
                                                    data-days="{{ $currentDays }}" data-bs-toggle="dropdown"
                                                    aria-haspopup="true" aria-expanded="false">{{ $filterLabel }}</a>
                                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="grn-dropdown">
                                                    <a class="dropdown-item filter-days {{ $currentDays == 'today' ? 'active' : '' }}"
                                                        href="#" data-days="today" data-filter="grn">Today</a>
                                                    <a class="dropdown-item filter-days {{ $currentDays == 7 ? 'active' : '' }}"
                                                        href="#" data-days="7" data-filter="grn">Last 7 days</a>
                                                    <a class="dropdown-item filter-days {{ $currentDays == 30 ? 'active' : '' }}"
                                                        href="#" data-days="30" data-filter="grn">Last 30 days</a>
                                                    <a class="dropdown-item filter-days {{ $currentDays == 90 ? 'active' : '' }}"
                                                        href="#" data-days="90" data-filter="grn">Last 3 months</a>
                                                    <a class="dropdown-item filter-days {{ $currentDays == 'all' ? 'active' : '' }}"
                                                        href="#" data-days="all" data-filter="grn">All</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-baseline">
                                        <div class="h1 mb-0 me-2" id="stat-total-grn-revenue">₹0</div>
                                        <div class="me-auto">
                                            <!-- <span id="stat-grn-growth-container" class="text-green d-inline-flex align-items-center lh-1">
                                                    <span id="stat-grn-growth-perc">0%</span>
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon ms-1 icon-2">
                                                        <path id="stat-grn-growth-path-1" d="M3 17l6 -6l4 4l8 -8" />
                                                        <path id="stat-grn-growth-path-2" d="M14 7l7 0l0 7" />
                                                    </svg>
                                                </span> -->
                                        </div>
                                    </div>
                                </div>
                                <div id="chart-grn-revenue-bg" class="chart-sm mt-auto"></div>
                        </div>
                    </div>
                @endcan
            </div>
         </div>
                
            <div class="col-lg-3">
                @can('dashboard.location_wise_grn') 
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">                               
                        <div class=" border-0 border-start border-4 border-primary ps-2 d-flex justify-content-between align-items-center">
                            <h3 class="card-title fw-bold m-0">Location-Wise GRN <i class="fa-solid fa-map-location ms-2"></i></h3>
                            <span class="text-secondary">Today</span>
                        </div>
                        <div class="card mt-3 flex-fill mb-0" style="min-height: 0;">
                            <div class="card-table table-responsive">
                                <div id="location-grn-table" style="max-height: 350px; overflow-y: auto;"></div>
                            </div>
                        </div>                                    
                    </div>
                </div>
                @endcan 
            </div>
            </div>
            @canany(['dashboard.product_in', 'dashboard.product_out', 'dashboard.pending_in','dashboard.pending_out'])
                <div class="row row-deck row-cards mt-3">
                    <div class="col-12">
                        <div class="d-flex align-items-center mb-3">
                            <h3 class="card-title fw-bold mb-0">Godown Details <i
                                    class="fa-solid fa-warehouse ms-2 me-2"></i></h3>
                            <div class="ms-auto lh-1">
                                <div class="dropdown">
                                    <a class="dropdown-toggle text-secondary" id="godown-dropdown" href="#"
                                        data-days="{{ $currentDays }}" data-bs-toggle="dropdown" aria-haspopup="true"
                                        aria-expanded="false">{{ $filterLabel }}</a>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="godown-dropdown">
                                        <a class="dropdown-item filter-days {{ $currentDays == 'today' ? 'active' : '' }}"
                                            href="#" data-days="today" data-filter="godown">Today</a>
                                        <a class="dropdown-item filter-days {{ $currentDays == 7 ? 'active' : '' }}"
                                            href="#" data-days="7" data-filter="godown">Last 7 days</a>
                                        <a class="dropdown-item filter-days {{ $currentDays == 30 ? 'active' : '' }}"
                                            href="#" data-days="30" data-filter="godown">Last 30 days</a>
                                        <a class="dropdown-item filter-days {{ $currentDays == 90 ? 'active' : '' }}"
                                            href="#" data-days="90" data-filter="godown">Last 3 months</a>
                                        <a class="dropdown-item filter-days {{ $currentDays == 'all' ? 'active' : '' }}"
                                            href="#" data-days="all" data-filter="godown">All</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Product In -->
                    @can('dashboard.product_in')
                        <div class="col-sm-6 col-lg-3">
                            <div class="card">
                                <div class="card-body d-flex flex-column">
                                    <div class="subheader fw-bold text-success text-uppercase">Product In <i
                                            class="fa-solid fa-arrow-right-to-bracket ms-1"></i></div>
                                    <div class="d-flex align-items-baseline mb-3">
                                        <div class="h1 mb-0 me-2" id="stat-godown-product-in">0</div>
                                    </div>
                                    <div class="mt-auto text-end">
                                        <a href="javascript:void(0);" class="text-primary fw-bold hover-underline-link"
                                            onclick="openGodownDetails('product_in', 'Product In')">View Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endcan
                    <!-- Product Out -->
                    @can('dashboard.product_out')
                        <div class="col-sm-6 col-lg-3">
                            <div class="card">
                                <div class="card-body d-flex flex-column">
                                    <div class="subheader fw-bold text-danger text-uppercase">Product Out <i
                                            class="fa-solid fa-arrow-right-from-bracket ms-1"></i></div>
                                    <div class="d-flex align-items-baseline mb-3">
                                        <div class="h1 mb-0 me-2" id="stat-godown-product-out">0</div>
                                    </div>
                                    <div class="mt-auto text-end">
                                        <a href="javascript:void(0);" class="text-primary fw-bold hover-underline-link"
                                            onclick="openGodownDetails('product_out', 'Product Out')">View Details </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endcan
                    <!-- Pending In -->
                    @can('dashboard.pending_in')
                        <div class="col-sm-6 col-lg-3">
                            <div class="card">
                                <div class="card-body d-flex flex-column">
                                    <div class="subheader fw-bold text-warning text-uppercase">Pending In <i
                                            class="fa-solid fa-clock-rotate-left ms-1"></i></div>
                                    <div class="d-flex align-items-baseline mb-3">
                                        <div class="h1 mb-0 me-2" id="stat-godown-pending-in">0</div>
                                    </div>
                                    <div class="mt-auto text-end">
                                        <a href="javascript:void(0);" class="text-primary fw-bold hover-underline-link"
                                            onclick="openGodownDetails('pending_in', 'Pending In')">View Details </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endcan
                    <!-- Pending Out -->
                    @can('dashboard.pending_out')
                        <div class="col-sm-6 col-lg-3">
                            <div class="card">
                                <div class="card-body d-flex flex-column">
                                    <div class="subheader fw-bold text-warning text-uppercase">Pending Out <i
                                            class="fa-solid fa-clock ms-1"></i></div>
                                    <div class="d-flex align-items-baseline mb-3">
                                        <div class="h1 mb-0 me-2" id="stat-godown-pending-out">0</div>
                                    </div>
                                    <div class="mt-auto text-end">
                                        <a href="javascript:void(0);" class="text-primary fw-bold hover-underline-link"
                                            onclick="openGodownDetails('pending_out', 'Pending Out')">View Details </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endcan
                </div>
            @endcanAny
           
            <div class="row row-deck row-cards mt-3">
                @can('dashboard.sales_and_purchase_overview')
                    <div class="col-lg-8 d-flex flex-column">
                        <div class="card-header border-0 border-start border-4 border-primary ps-2">
                            <h3 class="card-title fw-bold">Sales & Purchase Overview<i class="fa-solid fa-tags ms-2"></i>
                            </h3>
                        </div>
                        <div class="card mt-3">
                            <div class="card-body">
                                <div id="overview-skeleton" class="placeholder-glow">
                                    <div class="placeholder w-100" style="height: 350px;"></div>
                                </div>
                                <div id="sales-purchase-overview" class="position-relative d-none"></div>
                            </div>
                        </div>
                    </div>
                @endcan
                @can('dashboard.dairy_outstanding')
                    <div class="col-lg-4 d-flex flex-column">
                        <div class="card-header border-0 border-start border-4 border-primary ps-2">
                            <h3 class="card-title fw-bold">Dairy Outstanding <i class="fa-solid fa-warehouse ms-2"></i></h3>
                        </div>
                        <div class="card mt-3">
                            <div class="card-body">
                                <div id="dairy-skeleton" class="placeholder-glow">
                                    <div class="placeholder w-100" style="height: 350px;"></div>
                                </div>
                                <div id="dairy-outstanding" class="position-relative d-none"></div>
                            </div>
                        </div>
                    </div>
                @endcan
            </div>           
            <div class="row row-deck row-cards mt-1">
                @can('dashboard.recent_sales_invoices')
                    <div class="col-lg-8 d-flex flex-column">
                        <div class="card-header border-0 border-start border-4 border-primary ps-2">
                            <h3 class="card-title fw-bold">Recent Sales Invoices <i
                                    class="fa-solid fa-file-invoice ms-2"></i></h3>
                        </div>
                        <div class="card mt-3">
                            <div class="table-responsive">
                                <div id="recent-invoices-table"></div>
                            </div>
                        </div>
                    </div>
                @endcan
                <div class="col-lg-4 d-flex flex-column">
                    @can('dashboard.top_selling_products')
                        <div class="card-header border-0 border-start border-4 border-primary ps-2">
                            <h3 class="card-title fw-bold">Top Selling Products <i class="fa-solid fa-tags ms-2"></i></h3>
                        </div>
                        <div class="card mt-3">
                            <div class="card-table table-responsive">
                                <div id="top-products-table"></div>
                            </div>
                        </div>
                    @endcan
                </div>
            </div>                       
        </div>
    </div>
</div>

@include('company.pages.dashboard.modal')
@endsection

@section('script')
<script>
    const recentSalesInvoice = "{{ route('dashboard.recent-invoices') }}"
        const topProductsRoute = "{{ route('dashboard.top-products') }}"
        const filterRoute = "{{ route('dashboard.filters') }}"
        const godownDetailsRoute = "{{ route('dashboard.godown-details') }}"
        const locationGrnRoute = "{{ route('dashboard.location-grn') }}";
        const locationGrnDetailsRoute = "{{ route('dashboard.location-grn-details') }}";

        window.dashboardSettings = {
            initialDays: "{{ $currentDays }}"
        };
</script>
<script src="{{ asset('js/dashboard/chart.js') }}?v={{ hash_file('md5', public_path('js/dashboard/chart.js')) }}">
</script>
<script src="{{ asset('js/dashboard/index.js') }}?v={{ hash_file('md5', public_path('js/dashboard/index.js')) }}">
</script>
@endsection