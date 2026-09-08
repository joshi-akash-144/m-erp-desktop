@extends('company.layout.app')

@section('title', 'Payment Voucher Register')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">  
<style>
        #ref_preview_table thead tr th {
            font-family: monospace;
            font-size: 13px;
            font-weight: 600;
            border : 1px solid rgb(66, 65, 65);
            background-color: #E3E7EB;
        
    }

    #ref_preview_table tbody tr td {
        font-family: monospace;
        font-size: 13px;
        border : 1px solid rgb(66, 65, 65);
    }   
</style>
@endsection

@section('content')
<div class="page-wrapper">
    <!-- Page Header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center justify-content-between">

                <!-- Title -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card border-0 border-start border-4 border-primary">
                        <div class="card-body shadow p-2">
                            <h3 class="page-title font-monospace">
                                <i class="fa-solid fa-receipt me-2 text-primary"></i>
                                Payment Voucher Register
                            </h3>
                            <span class="ribbon ribbon-bookmark bg-blue">
                                <i class="fa-solid fa-eye me-1"></i> LIST DATA
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Back Action -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                    <div class="card border-0 border-end border-4 border-primary">
                        <div class="card-body shadow-sm p-2">
                            <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                <div class="waves-effect d-flex gap-2">
                                    @canany(['payment_voucher.print', 'payment_voucher.export'])
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                @include('icons.upload', ['size' => 19])
                                                Export
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @php
                                                    $actions = exportActions('payment-voucher', 'payment_voucher');
                                                @endphp

                                                @foreach ($actions as $action)
                                                    @can($action['permission'])
                                                        <li>
                                                            <a class="dropdown-item"
                                                                href="#"
                                                                data-route="{{ $action['route'] }}"
                                                                @foreach(($action['attrs'] ?? []) as $attr => $value)
                                                                    {{ $attr }}="{{ $value }}"
                                                                @endforeach>
                                                                @include($action['icon'])
                                                                {{ $action['label'] }}
                                                            </a>
                                                        </li>
                                                    @endcan
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endcanany
                                    <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn waves-effect">
                                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Page Body -->
    <div class="page-body">
        <div class="container-xl">
            <div class="card">               
                 <!-- 🔹 Filters Section -->
                <form>
                    <div class="p-3" id="filters">                            
                            <div class="row g-2 m-erp-so-row-1 section-brown">                              
                                <!-- Start Date -->
                                <div class="col-md-4 col-lg-1">
                                    <label for="start_date" class="fs-4">Start Date</label>
                                    <input type="text" name="order_date" id="start_date" class="form-control" placeholder="DD-MM-YYYY"
                                        value="{{ isset($filters['order_date']) }}">
                                </div>

                                <!-- End Date -->
                                <div class="col-md-4 col-lg-1">
                                    <label for="end_date" class="fs-4">End Date</label>
                                    <input type="text" name="due_date" id="end_date" class="form-control" placeholder="DD-MM-YYYY"
                                        value="{{ isset($filters['due_date']) }}">
                                </div>

                                <!-- Supplier Name -->
                                <div class="col-md-4 col-lg-3 ">
                                    <label for="account_id" class="fs-4">Account Name</label>
                                    <select id="account_id" class="form-select select2">
                                        <option value="">Select Account Name</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Item -->
                                <div class="col-md-4 col-lg-2">
                                    <label for="narration" class="fs-4">Narration</label>
                                    <select id="narration" class="form-select select2">
                                        <option value="0">NO</option>                                            
                                        <option value="1">Yes</option>                                            
                                    </select>
                                </div>                                   

                                <!-- Filter Buttons -->
                                <div class="col-md-4 col-lg-auto d-flex align-items-end gap-2">
                                    <button id="filter_apply" type="button" class="btn btn-primary flex-fill waves-effect">
                                        @include('icons.filter', ['size' => 20])
                                        Show
                                    </button>
                                    <button id="filter_clear" type="button" class="btn btn-outline-secondary flex-fill waves-effect">
                                        @include('icons.filter-clear', ['size' => 20])
                                        Clear
                                    </button>
                                    
                                    <div class="d-flex align-items-center ms-2 mb-1">
                                        <label class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="checkbox" id="show_deleted" name="show_deleted" value="1">
                                            <span class="form-check-label fw-bold text-danger">Show Deleted</span>
                                        </label>
                                    </div>
                                </div>

                            </div>                                                                               
                    </div>
                </form>
            </div>
            <div class="row">
                <div class="col-lg-9">
                    <div class="card sm-shadow rounded-0 bg-light">
                        <div class="card-body">
                            <div class="row">
                                <div id="payment_voucher_register_table">
                                    <!-- Table content will be loaded dynamically via JS -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card sm-shadow rounded-0 bg-light">
                        <div class="card-body p-2">
                            <div class="mb-3">
                                <h4 class="font-monospace d-inline border-bottom border-2 border-primary pb-1">
                                    Reference Preview
                                </h4>
                            </div>
                
                            <!-- Scrollable table wrapper -->
                            <div class="table-responsive" style="max-height: 480px; min-height: 480px; overflow-y: auto;">
                                <table class="table table-sm table-hover border border-dark-subtle mb-0" id="ref_preview_table">
                                    <colgroup>
                                        <col width="30%">
                                        <col width="25%">
                                        <col width="30%">
                                        <col width="15%">
                                    </colgroup>
                                    <thead class="bg-light sticky-top">
                                        <tr>
                                            <th class="font-monospace text-center">Vch. No.</th>
                                            <th class="font-monospace text-center">Ref. No.</th>
                                            <th class="font-monospace text-center">Ref. Date</th>
                                            <th class="font-monospace text-end">Amount</th>
                                            <th class="font-monospace text-center">D/C</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ref_preview_table_body" class="border border-dark-subtle">
                                        <tr>
                                            <td colspan="5" class="text-center text-muted fw-semibold py-3">
                                                Click on any row to view <span class="text-primary">Payment Voucher Reference</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                
                        </div>
                    </div>
                </div>    
            </div>
        </div>
    </div>
</div>
@endsection
<script>
    const paymentVoucherRegisterListUrl = "{{ route('payment-vouchers.index') }}";
    const getReferences = "{{ route('payment-vouchers.references') }}";
</script>

@section('script')
    <script src="{{ asset('js/modules/payment-voucher-register/index.js') }}?v={{ hash_file('md5', public_path('js/modules/payment-voucher-register/index.js')) }}"></script>
@endsection
