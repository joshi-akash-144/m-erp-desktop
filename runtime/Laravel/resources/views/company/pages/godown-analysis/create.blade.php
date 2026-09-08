@extends('company.layout.app')
@section('title', 'Godown Analysis')

@section('css')   
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <style>

        /* Pale Yellow Header from Design */
        .analysis-top-nav {
            background-color: #fff8e1;
            border: 1px solid #ffe082;
            padding: 3px 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            margin-bottom: 8px;
        }
        .analysis-top-nav h2 {
            font-size: 1rem;
            color: #066fd1;
            margin: 0;
            font-weight: 700;
        }
        .analysis-top-nav .back-link {
            position: absolute;
            right: 15px;
            font-size: 0.8rem;
            color: #066fd1;
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        /* Section Specific */
        .section-header-title {
            font-size: 1rem;
            font-weight: 800;
            text-align: left;
            color: #1e293b;
            margin-bottom: 8px;           
            padding: 4px 12px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
        }

        .label-cell {
            font-weight: 700;
            color: #1e293b;
            font-size: 0.9rem;
            width: 110px;
            white-space: nowrap;
        }

        .value-cell {
            font-size: 1rem;
            color: #334155;
            min-width: 70px;
            padding-left: 5px;            
        }

        .compact-table td {
            padding: 1px 0 !important;
            border: none !important;
            vertical-align: top;
        }
            
        .text-truncate {
            min-width: 50px;          
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Element Table Styling */
        .element-table thead th {
            background-color: #e6f0fb !important;
            color: #0f172a !important;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid #cacaca !important;
            text-align: center;
            padding: 8px 4px !important;
        }

        .element-table tbody td {
            /* border: 1px solid #cbd5e1 !important; */
            padding: 1px !important;
            vertical-align: middle;
            font-size: 1rem !important;
        }

        .element-table .form-control {
            border: none !important;
            border-radius: 0 !important;
            background: transparent;
            font-size: 1rem !important;
            text-align: right;
            height: 36px;
            box-shadow: none !important;
        }

        .element-table .form-control[readonly] {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
        }

        .element-table .inputSelect:focus {
            background-color: #fffbea !important;
            outline: 2px solid #0d6efd !important;
            z-index: 10;
        }

        .total-summary-row td {
            padding: 2px 0px !important;
            vertical-align: middle;
            border: 1px solid #cbd5e1 !important;
        }

        .val-box-large {
            border: none !important;
            background: transparent;
            font-weight: 800;
            color: #198754;
            font-size: 1.1rem;
            text-align: right;
        }

        .select2-container--bootstrap-5 .select2-selection {            
            height: calc(1.1em + 0.95rem + 2px) !important;
            border-radius: 0 !important;
            font-size: 0.8rem;
        }
        
        #parameter_table thead{
            background-color: #203246 !important;
        }

        #parameter_table th{
            font-size: 0.8rem !important;
            font-weight: 700 !important;   
            color: #3f3f46 !important; 
            text-transform: uppercase;
            letter-spacing: 0.025em;
            border-bottom: 2px solid #dee2e6 !important;
            background-color: #e6e6e8 !important;
        }
        #parameter_table td{
            padding: 0px !important;  
            font-weight: 700 !important;    
            align-content: center;
            font-size: 1rem !important;
            border: 1px solid #e2e8f0 !important;
        }
        #parameter_table td span{           
            font-size: 1rem !important;      
        }
        #parameter_table td input{
            font-size: 1rem !important;                
        }

    </style>
@endsection

@section('content')
<div class="page-wrapper" style="max-width: 1700px">   
    <!-- Page Header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-dairy-analysis rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-dairy-analysis-lt text-dairy-analysis rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-flask"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-dairy-analysis fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Godown Analysis
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-green text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-chart-line"></i> Transaction
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                        {{-- Print Button --}}
                        <button type="button" id="print_btn" class="btn btn-outline btn-primary d-none btn-sm px-2 fw-bold shadow-sm">
                            <i class="fa-solid fa-print me-1"></i> Print
                        </button>
                        <button type="button" id="email_btn" class="btn btn-outline btn-orange d-none btn-sm px-2 fw-bold shadow-sm">
                            <i class="fa-solid fa-envelope me-1"></i> Send Mail
                        </button>
                        {{-- Back Button --}}
                        <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{--  <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center justify-content-between">
                <!-- 🔹 Title & Subtitle -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="card border-0 border-start border-4 border-dairy-analysis shadow-sm">
                            <div class="card-body p-2">
                                <h3 class="page-title font-monospace m-0 text-dairy-analysis">                                                
                                    <i class="fa fa-flask me-2"></i>Dairy Analysis
                                </h3>  
                            </div>
                        </div>
                </div>
                <!-- 🔹 Action Buttons (Export & Add) -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                    <div class="card border-0 border-end border-4 border-dairy-analysis">
                        <div class="card-body shadow-sm p-2">
                            <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                <button type="button" id="print_btn" class="btn btn-outline btn-primary d-none btn-sm px-2 fw-bold shadow-sm">
                                    <i class="fa-solid fa-print me-1"></i> Print
                                </button>
                                <button type="button" id="email_btn" class="btn btn-outline btn-orange d-none btn-sm px-2 fw-bold shadow-sm">
                                    <i class="fa-solid fa-envelope me-1"></i> Send Mail
                                </button>
                                <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
                                    <i class="fa-solid fa-arrow-left me-1"></i> Back
                                </a>
                            </div>  
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}
    <div class="page-body">
        <form id="godown_analysis_form" class="g-2">            
            <input type="hidden" name="uuid" id="uuid" value="{{ uuid() }}">
                   
            <div class="container-xl">
                <div class="card p-2 shadow-sm">
                    @csrf
                    <!-- GRN ROW -->
                    <div class="row g-3 mb-3">
                        <div class="col-lg-12">
                        <div class="module-form-section border-dairy-analysis section-dairy-analysis">
                            <div class="module-page-title text-dairy-analysis">
                                <i class="fa-solid fa-cart-shopping me-2"></i> Grn Details
                            </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <table class="compact-table w-100">
                                            <tr>
                                                <td class="label-cell">GRN No.</td>
                                                <td class="value-cell">
                                                <div class="input-group input-group-sm">
                                                    <select name="grn_id" id="grn_id" class="form-select">
                                                        <option value="">Select GRN No</option>
                                                        @foreach ($grnNumbers as $id => $serial)
                                                        <option value="{{ $serial }}">{{ $serial }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" id="btn_find_grn" class="btn btn-dairy-analysis btn-sm px-2 fw-bold fs-4" style="height: 35px; width: 80px">Find</button>
                                                </div>
                                                </td>
                                            </tr>
                                            <tr><td class="label-cell">Name</td><td class="value-cell"><span id="account_name" class="text-truncate d-inline-block" style="max-width: 400px;">--</span></td></tr>
                                            <tr><td class="label-cell">City</td><td class="value-cell"><span id="account_city">--</span></td></tr>
                                        </table>
                                    </div>
                                    <div class="col-md-3">
                                        <table class="compact-table w-100 ps-3">
                                            <tr><td class="label-cell">Date</td><td class="value-cell"><span id="date">--</span></td></tr>
                                            <tr><td class="label-cell">Party Bill No.</td><td class="value-cell"><span id="reference_no">--</span></td></tr>
                                            <tr><td class="label-cell">Vehicle No.</td><td class="value-cell"><span id="vehicle_no">--</span></td></tr>
                                            <tr><td class="label-cell">Condition</td><td class="value-cell"><span id="condition">--</span></td></tr>
                                        </table>
                                    </div>
                                    <div class="col-md-4">
                                        <table class="compact-table w-100 ps-3">
                                            <tr><td class="label-cell">Gross Qty.</td><td class="value-cell"><span id="qty">0.000</span></td></tr>
                                            <tr><td class="label-cell">Incl Tax Rate</td><td class="value-cell"><span id="rate">0.00</span></td></tr>
                                            <tr>
                                                <td class="label-cell">Net Amount</td>
                                                <td class="value-cell d-flex align-items-center gap-2">
                                                    <span id="amount">0.00</span>
                                                    <span class="detail-span d-none flex-grow-1 bg-light border px-2 py-0 small text-muted fw-bold shadow-sm" style=" max-height:100px;"></span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PARAMETER TABLE ROW -->
                    <div class="row g-3">
                        <div class="col-lg-12">
                            {{-- <div class="section-header-title border-start border-4 border-primary">
                                <i class="fa-solid fa-list-check me-2 text-primary"></i> Parameter Analysis
                            </div> --}}
                            <div class="overflow-hidden shadow-sm mt-0 border">
                                <table class="table table-bordered m-0" id="parameter_table" style="height: 200px">
                                    <thead>
                                        <tr>
                                            <th width="15%" class="text-left">Element</th>
                                            <th width="10%" class="text-end">Guarantee</th>
                                            <th width="12%" class="text-end">Actual</th>
                                            <th width="10%" class="text-end">Diff%</th>
                                            <th width="10%" class="text-end">Rebate Per%</th>
                                            <th width="13.25%" class="text-end">Rebate</th>
                                            <th width="13.25%" class="text-end">Premium</th>
                                        </tr>
                                    </thead>
                                    <tbody id="parameterTable">
                                        {{-- dynamically set --}}
                                    </tbody>
                                    <tfoot id="paraTotal" class="total-summary-row">
                                        <!-- Footer dynamically rendered by JS -->
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                        <!-- 🛠️ Form Actions -->
                        <div class="card-footer bg-light border-top p-3 mt-4">
                            <div class="d-flex justify-content-between align-items-center">
                                       <small class="text-muted d-flex align-items-center gap-2">
                                <i class="fa-solid fa-circle-info text-primary me-1"></i>
                                Review all details before saving
                                <span class="text-secondary">|</span>
                                <span><span class="text-danger">*</span> Fields are required</span>
                            </small>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary waves-effect non-selectable" onclick="location.reload()">
                                        <i class="fa-solid fa-rotate-left me-1"></i> Clear
                                    </button>
                                    <button type="submit" id="btn_submit_main" class="btn btn-dairy-analysis form-save-btn px-5 shadow-sm waves-effect">
                                        <i class="fa-solid fa-floppy-disk me-1"></i> Save
                                    </button>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </form>
</div>

@include('company.partials._shortcuts-bar')

@include('company.pages.godown-analysis._godown-analysis-email-modal')

@endsection
@section('script')
    <script>
        const getGrnDataUrl = "{{ route('godown-analysis.getGrnData', ':grnSerial') }}";        
        const storeAnalysisUrl = "{{ route('godown-analysis.store') }}";
        const updateAnalysisUrl = "{{ route('godown-analysis.update', ':id') }}";
        const printIndividualAnalysisUrl = "{{ route('godown-analysis.pds-print', ':id') }}";
        const emailPreviewUrl = "{{ route('mail.godown-analysis-email-preview') }}";
        const emailSendUrl = "{{ route('mail.godown-analysis-email-send') }}";
        const hugertePath = "{{ asset('js/libs/hugerte') }}";
    </script>
    <script src="{{ asset('js/libs/hugerte/hugerte.min.js') }}"></script>
    <script src="{{ asset('js/modules/godown-analysis/email-modal.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modules/godown-analysis/create.js') }}?v={{ hash_file('md5', public_path('js/modules/godown-analysis/create.js')) }}"></script>
@endsection
