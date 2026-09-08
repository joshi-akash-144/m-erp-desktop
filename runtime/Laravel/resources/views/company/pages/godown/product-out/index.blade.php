@extends('company.layout.app')
@section('title', 'Godown – Product Out')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <style>

        .net-weight-display-box {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            padding: 15px;
            text-align: center;
        }
        .net-weight-value {
            font-size: 3.5rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1;
        }
    .m-erp-po-row-1 {
        grid-template-columns: 0.8fr 0.9fr 1.5fr 1.5fr 2fr 1.5fr;
    }
        .m-erp-godown-row-2 { grid-template-columns: 1fr 1fr 1fr 1fr 1fr 1.5fr 1.5fr; }
        .m-erp-godown-row-3 { grid-template-columns: 1fr 2fr 1fr 1fr 1fr 1.5fr 1.5fr; }
        .m-erp-godown-row-weight { grid-template-columns: 0.5fr 0.5fr 0.75fr 0.5fr; }

        .just-validate-error-label {
            position: absolute;
            bottom: -5px;
            left: 0;
            font-size: 11px !important;
            margin-top: 0 !important;
            white-space: nowrap;
        }

        /* Vehicle input-group: keep button top-aligned when error appears below input */
        .vehicle-input-group {
            flex-wrap: nowrap;
            align-items: flex-start;
        }
        .vehicle-input-group .flex-grow-1 {
            min-width: 0;
        }
        .vehicle-input-group .btn {
            align-self: flex-start;
            flex-shrink: 0;
        }


        .grid-item, .grid-item .flex-grow-1 {
            min-width: 0;
        }

        /* ✅ Form Element Height & Alignment (35px) */
        .page-body .btn,
        .page-body .select2-container--bootstrap-5 .select2-selection {
            height: 35px !important;
            min-height: 35px !important;
            display: flex;
        }

        .page-body .select2-container--bootstrap-5 .select2-selection__rendered {
            line-height: normal !important;
            padding-left: 8px !important;
        }

        /* ✅ Input Group Border Radius & Flex Fixes */
        .input-group > .select2-container--bootstrap-5 {
            flex: 1 1 auto;
            width: auto; /* Removed 1% !important to allow inline width */
        }

        /* Allow inline style width to take precedence if provided */
        .input-group > .select2-container--bootstrap-5[style*="width"] {
            flex: none !important;
        }

        .input-group > .select2-container--bootstrap-5 .select2-selection {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        .input-group > .btn:first-child,
        .input-group > .form-control:first-child {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        .input-group > .btn:last-child,
        .input-group > .form-control:last-child {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .disabled {
            background-color: #f8fafc !important;
        }

        @media (max-width: 992px) {
            .m-erp-godown-row-1, .m-erp-godown-row-2, .m-erp-godown-row-3,.m-erp-godown-row-weight {
                grid-template-columns: 1fr;
            }
        }

        /* ✅ Table-Based Weight Section Styles */
        .weight-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px; /* Vertical gap between rows */
        }

        .weight-table td {
            padding: 0;
            vertical-align: middle;
        }

        .weight-table .label-cell {
            background: #ededed;
            color: #334155;
            font-weight: 600;
            padding: 0 12px;
            height: 35px;
            width: 150px;
            font-size: 13px;
            white-space: nowrap;
        }

        .weight-table .input-cell {
            padding-left: 8px;
        }

        .weight-table .spacer-cell {
            width: 30px;
        }

        .btn-weight-action {
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            border-radius: 0px;
            border: none;
            cursor: pointer;
        }
        .h-100{
            height: 97% !important;
        }
    </style>
@endsection

@section('content')
    <div class="page-wrapper">
        <!-- ✅ Page Header -->
  <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-godown rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-godown-lt text-godown rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-warehouse"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-godown fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Product Out
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
                        @can('godown_module.product_in')
                            <a href="{{ route('godown-module.product-in') }}" class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="tooltip" data-bs-placement="bottom" title="Product In">
                                <i class="fa-solid fa-door-open me-1"></i> Product In
                            </a>
                        @endcan
                        <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
      {{--   <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center justify-content-between">
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="card border-0 border-start border-4 border-godown">
                            <div class="card-body shadow p-2">
                                <h3 class="page-title font-monospace text-godown">
                                    <i class="fa-solid fa-warehouse me-2"></i>
                                    Product Out
                                </h3>
                                <span class="ribbon ribbon-bookmark bg-lime">
                                    <i class="fa-solid fa-plus me-1"></i> NEW ENTRY
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                        <div class="card border-0 border-end border-4 border-godown">
                            <div class="card-body shadow-sm p-2">
                                <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                    @can('godown_module.product_in')
                                        <a href="{{ route('godown-module.product-in') }}" class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="tooltip" data-bs-placement="bottom" title="Product In">
                                            <i class="fa-solid fa-door-open me-1"></i> Product In
                                        </a>
                                    @endcan
                                    <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
                                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div> 
                </div>
            </div>
        </div>  --}}

        <!-- ✅ Page Body -->
        <div class="page-body">
            <div class="container-xl">
                <form id="product_out_form" autocomplete="off" class="card shadow-sm">
                    @csrf
                    <!-- Hidden Core Fields -->
                    <input type="hidden" name="id" id="id" value="">
                    <input type="hidden" name="uuid" value="{{ (string) Str::uuid() }}">
                    <input type="hidden" name="in_out_status" value="out">
                    <input type="hidden" name="is_manual" value="0">
                    <input type="hidden" name="delivery_challan_id" id="delivery_challan_id" value="">
                    <input type="hidden" name="is_set_vehicle" id="is_set_vehicle" value="0">
                    <input type="hidden" name="time_in" id="time_in" value="{{ date('H:i') }}">
                    <input type="hidden" name="time_out" id="time_out" value="{{ date('H:i') }}">
                    <input type="hidden" name="last_godown_entry_id" id="last_godown_entry_id" value="{{ $last_godown_entry_id ?? '' }}">

                    <div class="card-body p-3">

                        <!-- 📝 Section 1: General & Vehicle Details -->
                        <div class="module-form-section border-godown section-godown">
                            <div class="module-page-title text-godown">
                                <i class="fa-solid fa-circle-info me-2"></i>
                                General & Vehicle Details
                            </div>
                            <div class="grid-row m-erp-po-row-1 row-body">
                                <div class="grid-item">
                                    <label class="form-label required">Challan No</label>
                                    <input type="text" name="dc_serial" id="dc_serial" class="form-control bg-primary-lt text-dark" value="{{ $last_grn_serial }}" readonly>
                                </div>
                                <div class="grid-item">
                                    <label class="form-label required">Challan Date</label>
                                    <input type="text" name="dc_in_date" id="dc_in_date"  class="form-control date-format" value="{{ date('d-m-Y') }}">
                                </div>
                                <div class="grid-item">
                                    <label class="form-label required">Vehicle No</label>
                                    <div class="input-group vehicle-input-group">
                                        <div class="flex-grow-1 position-relative">
                                            <input type="text" name="vehicle_number" id="vehicle_number" class="form-control txtRegNo rounded-end-0" placeholder="ENTER VEHICLE NO.">
                                        </div>
                                        <button class="btn text-white fw-bold px-2 btn-godown non-selectable" type="button" id="btn_get_vehicles" data-bs-toggle="modal" data-bs-target="#vehicle_list_modal">
                                            <i class="fa-solid fa-truck-moving me-1"></i> GET
                                        </button>
                                    </div>
                                    <div id="vehicle_number_error"></div>
                                </div>
                                <!-- <div class="grid-item">
                                    <label class="form-label">Broker</label>
                                    <select name="broker_id" id="broker_id" class="form-select select2">
                                        <option value="">--Select Broker--</option>
                                        @foreach($brokers as $broker)
                                            <option value="{{ $broker->id }}">{{ $broker->name }}</option>
                                        @endforeach
                                    </select>
                                </div> -->
                                <div class="grid-item">
                                    <label class="form-label required">Product</label>
                                    <select name="item_id" id="item_id" class="form-select select2">
                                        <option value="">--Select Product--</option>
                                        @foreach($items as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="grid-item">
                                    <label class="form-label required">Name Of Party</label>
                                    <select name="party_id" id="party_id" class="form-select select2">
                                        <option value="">--Select Customer--</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}{{ !empty($customer->city) ? ' (' . $customer->city . ')' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                {{-- <div class="grid-item">
                                    <label class="form-label required">Challan-Type</label>
                                    <input type="text" name="challan_type" id="challan_type" class="form-control bg-primary-lt text-dark" value="" readonly>
                                </div> --}}
                                <div class="grid-item">
                                    <label class="form-label required">Party Destination</label>
                                    <select name="party_destination" id="party_destination" class="form-select select2">
                                        <option value="">--Select Party Destination--</option>
                                        @foreach($destinations as $destination)
                                            <option value="{{ $destination->id }}">{{ $destination->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- 📑 Section 2: Transaction Details -->
                        <div class="module-form-section border-godown section-godown">
                            <div class="module-page-title text-godown">
                                <i class="fa-solid fa-file-invoice me-2"></i>
                                Transaction Details
                            </div>
                            <div class="grid-row m-erp-godown-row-2 row-body">
                                {{-- <div class="grid-item">
                                    <label class="form-label required">Party Bill No</label>
                                    <div class="input-icon mb-3">
                                        <input type="text" name="reference_number" id="reference_number" class="form-control">
                                        <span class="input-icon-addon d-none" id="reference_number_loader">
                                            <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                                        </span>
                                    </div>
                                </div> --}}
                                <div class="grid-item">
                                    <label class="form-label">Bags</label>
                                    <select name="bag_type" id="bag_type" class="form-select select2">
                                        @php
                                            $bagTypes = config('constants.bag_types');
                                        @endphp
                                        @foreach ($bagTypes as $bagTypeKey => $bagType)
                                            <option value="{{ $bagTypeKey }}">{{ $bagType }}</option>
                                        @endforeach
                                    </select>   
                                </div>
                                <div class="grid-item">
                                    <label class="form-label">Bag Qty</label>
                                    <div class="input-group">
                                        <input type="number" name="bag_qty" id="bag_qty" class="form-control text-end" value="0.00">
                                    </div>
                                </div>
                                <div class="grid-item">
                                    <label class="form-label">Rate</label>
                                    <input type="number" step="0.001" name="rate" class="form-control text-end" value="0.000">
                                </div>
                                {{-- <div class="grid-item">
                                    <label class="form-label">Challan Date</label>
                                    <input type="text" name="dc_date" id="dc_date" class="form-control date-format" placeholder="DD-MM-YYYY">
                                </div> --}}
                                <div class="grid-item">
                                    <label class="form-label">Challan Weight</label>
                                    <input type="number" step="0.001" name="challan_weight" class="form-control text-end" value="0.000">
                                </div>
                                {{-- <div class="grid-item">
                                    <label class="form-label">Dairy P.O</label>
                                    <select name="so_no" id="so_no" class="form-select select2">
                                        <option value="">--Select Sales Order--</option>
                                    </select>
                                </div>  --}}
                                <div class="grid-item">
                                    <label class="form-label">Dairy P.O</label>
                                    <input type="text" name="dairy_po" id="dairy_po" class="form-control">
                                </div> 

                                <div class="grid-item">
                                    <label class="form-label">Godown </label>
                                    <select name="godown_id" id="godown_id" class="form-select select2">
                                        <option value="0">--Select Godown --</option>
                                        @foreach($destinations as $destination)
                                            <option value="{{ $destination->id }}">{{ $destination->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="grid-item" id="godown_unit_container" style="display: none;">
                                    <label class="form-label">Godown Unit</label>
                                    <select name="godown_unit_id" id="godown_unit_id" class="form-select select2">
                                        <option value="">--Select Godown Unit--</option>
                                    </select>
                                </div>
                                </div>
                                <div class="grid-row m-erp-godown-row-3 row-body">
                                <div class="grid-item">
                                    <label class="form-label">Is Crossing ?</label>
                                    <select name="is_crossing" id="is_crossing" class="form-select select2">
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    </select>
                                </div>
                                <div class="grid-item">
                                    <div class="d-flex align-items-end gap-2">
                                        <div class="flex-grow-1">
                                            <label class="form-label required">Transporter</label>
                                            <select name="transporter_id" id="transporter_id" class="form-select select2">
                                                <option value="">--Select Transporter--</option>
                                                @isset($transporters)
                                                    @foreach($transporters as $transporter)
                                                        <option value="{{ $transporter->id }}">{{ $transporter->name }}</option>
                                                    @endforeach
                                                @endisset
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid-item">
                                    <label class="form-label required">L.R. Number</label>
                                    <div class="input-icon mb-3">
                                        <input type="text" name="lr_number" id="lr_number" class="form-control">
                                        <span class="input-icon-addon d-none" id="lr_number_loader">
                                            <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                                        </span>
                                    </div>
                                </div>
                                <!-- <div class="grid-item">
                                    <label class="form-label required">Challan Bags</label>
                                    <input type="number" name="challan_bags" class="form-control">
                                </div> -->
                            </div>
                        </div>

                        <!-- ⚖️ Section 3: Weights & Timestamps -->
                        <div class="row g-3">
                            <div class="col-lg-8">
                                <div class="module-form-section border-godown section-godown">
                                    <div class="module-page-title text-godown">
                                        <i class="fa-solid fa-scale-balanced me-2   "></i>
                                        Weights & Times
                                    </div>
                                    <table class="weight-table">
                                        <tbody>
                                            <!-- Hidden Inputs for Moisture Calculation -->
                                            <input type="hidden" name="old_challan_weight" id="old_challan_weight" value="0">
                                            <input type="hidden" name="new_challan_weight" id="new_challan_weight" value="0">
                                            <input type="hidden" name="old_gross_weight" id="old_gross_weight" value="0">
                                            <input type="hidden" name="new_gross_weight" id="new_gross_weight" value="0">
                                            <input type="hidden" name="old_tare_weight" id="old_tare_weight" value="0">
                                            <input type="hidden" name="new_tare_weight" id="new_tare_weight" value="0">
                                            <input type="hidden" name="old_net_weight" id="old_net_weight" value="0">
                                            <input type="hidden" name="new_net_weight" id="new_net_weight" value="0">
                                            <input type="hidden" name="old_net_weight_wt_bag" id="old_net_weight_wt_bag" value="0">
                                            <input type="hidden" name="new_net_weight_wt_bag" id="new_net_weight_wt_bag" value="0">

                                            <!-- Row 1: Date In & Weight Location -->
                                            <tr>
                                                <td class="label-cell">Date In:</td>
                                                <td class="input-cell">
                                                    <input type="text" name="date_in" id="date_in" class="form-control text-dark date-format disabled" value="{{ date('d-m-Y') }}" readonly>
                                                </td>
                                                <td class="spacer-cell"></td>
                                                <td class="label-cell">Weight Location:</td>
                                                <td class="input-cell">
                                                    <select name="weight_location" id="weight_location" class="form-select select2">
                                                    <option value="">--Select Weight Location--</option>
                                                        @foreach ($weight_locations as $weight_location)
                                                            <option value="{{ $weight_location->id }}">{{ $weight_location->godown_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            </tr>

                                            <!-- Row 2: Time In & Get-Weight -->
                                            <tr>
                                                <td class="label-cell">Time In:</td>
                                                <td class="input-cell">
                                                    <input type="text" name="time_in_display" class="form-control text-dark disabled" value="{{ date('h:i A') }}" readonly>
                                                </td>
                                                <td class="spacer-cell"></td>
                                                <td colspan="1" class="input-cell">
                                                    <button type="button" class="btn-weight-action btn-godown non-selectable" id="btn_get_weight">
                                                        <i class="fa-solid fa-weight-hanging me-2"></i> GET-WEIGHT
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Row 3: Date Out & Gross Weight -->
                                            <tr>
                                                <td class="label-cell">Date Out:</td>
                                                <td class="input-cell">
                                                    <input type="text" name="date_out" id="date_out" class="form-control text-dark date-format disabled" value="{{ date('d-m-Y') }}" readonly>
                                                </td>
                                                <td class="spacer-cell"></td>
                                                <td class="label-cell">Tare-Weight:</td>
                                                <td class="input-cell">
                                                    <input type="number" step="0.001" id="tare_weight" name="tare_weight" class="form-control text-end weight-input disabled" value="0.000" readonly>
                                                </td>
                                            </tr>

                                            <!-- Row 4: Time Out & Tare Weight -->
                                            <tr>
                                                <td class="label-cell">Time Out:</td>
                                                <td class="input-cell">
                                                    <input type="text" name="time_out_display" class="form-control text-dark disabled" value="{{ date('h:i A') }}" readonly>
                                                </td>
                                                <td class="spacer-cell"></td>
                                                <td class="label-cell">Gross-Weight:</td>
                                                <td class="input-cell">
                                                    <input type="number" step="0.001" id="gross_weight" name="gross_weight" class="form-control text-end weight-input" value="0.000">
                                                </td>
                                            </tr>

                                            <!-- Row 5: Moisture & Net Weight -->
                                            <tr>
                                                <td class="p-0">
                                                    <button type="button" class="btn-weight-action btn-godown non-selectable" id="btn_moisture">
                                                        <i class="fa-solid fa-file-lines me-2"></i> MOISTURE
                                                    </button> 
                                                </td>
                                                <td class="input-cell">
                                                    <input type="number" name="moisture" class="form-control non-selectable" value="0">
                                                </td>
                                                <td class="spacer-cell"></td>
                                                <td class="label-cell">Net-Weight:</td>
                                                <td class="input-cell">
                                                    <input type="number" step="0.001" id="net_weight" name="net_weight" class="form-control text-end disabled" value="0.000" readonly>
                                                </td>
                                            </tr>

                                            <!-- Row 6: Empty & Net Weight (No Bags) -->
                                            <tr>
                                                <td colspan="2"></td>
                                                <td class="spacer-cell"></td>
                                                <td class="label-cell">Net-Weight (No Bags)</td>
                                                <td class="input-cell">
                                                    <input type="number" step="0.001" id="net_weight_wt_bag" name="net_weight_wt_bag" class="form-control text-end disabled" value="0.000" readonly>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="module-form-section h-100 border-godown section-godown">
                                    <div class="module-page-title text-godown">
                                        <i class="fa-solid fa-eye me-2"></i>
                                        Live Summary
                                    </div>
                                    <div class="card border-0 shadow-none bg-transparent">
                                        <div class="card-body p-0">
                                            <div class="net-weight-display-box rounded">
                                                <div class="text-muted small text-uppercase fw-bold mb-1">Total Net Weight</div>
                                                <div class="net-weight-value net-weight-display">0.00</div>
                                            </div>
                                            <div class="mt-3">
                                                <label class="form-label">Remarks</label>
                                                <textarea name="remarks" id="remarks" class="form-control non-selectable" rows="3" placeholder="Enter any additional notes here..."></textarea>
                                            </div>
                                        </div>
                                    </div>
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
                                    <button type="submit" class="btn btn-godown px-5 form-save-btn shadow-sm waves-effect">
                                        <i class="fa-solid fa-floppy-disk me-1"></i> Save
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>
@include('company.pages.godown.product-out._modal')
@include('company.partials._shortcuts-bar', [
    'barModuleLabel'     => 'Product Out',
    'barModuleShortcuts' => [
        ['key' => 'Alt+I', 'label' => 'Product In', 'type' => 'info', 'permission' => 'godown_module.product_in', 'url' => route('godown-module.product-in')],
        ['key' => 'Alt+T', 'label' => 'Ticket',       'type' => 'info', 'permission' => 'godown_module.list', 'id' => 'shortcut_print_ticket'],
        ['key' => 'Alt+P', 'label' => 'GatePass',     'type' => 'info', 'permission' => 'godown_module.list', 'id' => 'shortcut_print_gatepass'],
        ['key' => 'Alt+G', 'label' => 'Print Grn',    'type' => 'info', 'permission' => 'godown_module.list', 'id' => 'shortcut_print_grn'],
    ],
]) 
@endsection


@section('script')
<script>
    const productOutStoreUrl = "{{ route('godown-module.store-product-out') }}";
    const productOutUpdateUrl = "{{ route('godown-module.update-product-out', ':id') }}"; 

    const getGodownsUrl = "{{ route('godown-module.get-godowns') }}";
    const fetchChallanDetailsUrl = "{{ route('godown-module.fetch-grn-details') }}";
    const fetchWeightUrl = "{{ route('weight_location.fetch-weight', ':id') }}";
    const fetchPendingSalesOrdersUrl = "{{ route('delivery-challans.pending_sales_orders') }}";
    const checkDuplicateReferenceFromGodownModule ="{{ route('delivery-challans.check_duplicate_reference') }}";
    const checkDuplicateLrFromGodownModule = "{{ route('godown-module.check_duplicate_lr_no') }}";

    $(document).ready(function() {
        function printLastGodownEntry(type) {
            const lastId = $('#last_godown_entry_id').val();
            if (!lastId) {
                showToast('warning', 'No product out entry found to print.');
                return;
            }

            let route = '';
            if (type === 'ticket') {
                route = "{{ route('godown-module.print-ticket') }}";
            } else if (type === 'gatepass') {
                route = "{{ route('godown-module.print-gatepass') }}";
            } else if (type === 'grn') {
                route = "{{ route('godown-module.print-grn') }}";
            }

            if (route && typeof printReport === 'function') {
                printReport(route, {
                    format: 'print',
                    currentFilter: {
                        selected_ids: [lastId]
                    }
                });
            }
        }

        $(document).on('click', '#shortcut_print_ticket', function(e) {
            e.preventDefault();
            printLastGodownEntry('ticket');
        });

        $(document).on('click', '#shortcut_print_gatepass', function(e) {
            e.preventDefault();
            printLastGodownEntry('gatepass');
        });

        $(document).on('click', '#shortcut_print_grn', function(e) {
            e.preventDefault();
            printLastGodownEntry('grn');
        });
    });
</script>
    <script src="{{ asset('js/modules/godown/product-out.js') }}?v={{ hash_file('md5', public_path('js/modules/godown/product-out.js')) }}"></script>
    <script src="{{ asset('js/modules/godown/check-duplicate-ref.js') }}?v={{ hash_file('md5', public_path('js/modules/godown/check-duplicate-ref.js')) }}"></script>
    <script src="{{ asset('js/modules/godown/check-duplicate-lr.js') }}?v={{ hash_file('md5', public_path('js/modules/godown/check-duplicate-lr.js')) }}"></script>
@endsection
