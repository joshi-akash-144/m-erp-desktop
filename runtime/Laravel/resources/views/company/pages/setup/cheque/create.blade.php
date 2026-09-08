@extends('company.layout.app')

@section('title', 'Cheque Print Format')

@section('css')
    <link rel="stylesheet"
        href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <style>
        .cheque-container {
            background: #f4f7f6;
            padding: 20px;
        }

        .cheque-canvas-wrapper {
            background: #f0f2f5;
            padding: 60px;
            overflow: auto;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 400px;
            box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.05);
        }

        .cheque-canvas {
            background-color: #fff;
            background-image:
                linear-gradient(to right, #e2e8f0 1px, transparent 1px),
                linear-gradient(to bottom, #e2e8f0 1px, transparent 1px);
            background-size: 20px 20px;
            position: relative;
            border: 2px solid #2c3e50;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            transform-origin: center;
            transition: box-shadow 0.3s ease;
        }

        .cheque-canvas:hover {
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.2);
        }

        .draggable {
            position: absolute;
            border: 1px dashed #066fd1;
            background-color: rgba(6, 111, 209, 0.08);
            cursor: move;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Times New Roman', Times, serif;
            font-size: 14px;
            user-select: none;
            touch-action: none;
            box-sizing: border-box;
            border-radius: 0px;
            transition: background-color 0.2s;
        }

        .draggable:hover {
            background-color: rgba(240, 240, 240, 1);
        }

        .draggable.selected {
            border: 2px solid #066fd1;
            background-color: rgba(6, 111, 209, 0.05);
            z-index: 100;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .resize-handle {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #066fd1;
            bottom: -5px;
            right: -5px;
            border-radius: 50%;
            cursor: nwse-resize;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .property-table thead th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.025em;
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .property-table td {
            padding: 6px 10px !important;
            vertical-align: middle;
        }

        .property-table input,
        .property-table select {
            border: 1px solid #e2e8f0;
        }

        .property-table input:focus,
        .property-table select:focus {
            border-color: #066fd1;
            box-shadow: 0 0 0 2px rgba(6, 111, 209, 0.1);
        }

        /* Table Readonly Styles */
        #chequePropertiesTable input[readonly],
        #chequePropertiesTable select[readonly] {
            background-color: #f4f6fa !important;
            border-color: #e6e7e9 !important;
            color: #616876 !important;
            cursor: not-allowed;
            pointer-events: none;
        }

        #chequePropertiesTable input:not([readonly]),
        #chequePropertiesTable select:not([readonly]) {
            background-color: #ffffff !important;
            border-color: var(--tblr-border-color) !important;
            color: inherit !important;
            cursor: text;
            pointer-events: auto;
        }

        #chequePropertiesTable select:not([readonly]) {
            cursor: pointer;
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
                            <i class="fs-3 fa-solid fa-money-check"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Cheque Print Format
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-green text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-list"></i>  New Entry
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
    {{--    <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center justify-content-between">

                    <!-- 🔹 Title & Subtitle -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="card border-0 border-start border-4 border-primary">
                            <div class="card-body shadow p-2">
                                <h3 class="page-title font-monospace text-primary">
                                    <i class="fa-solid fa-money-check me-2"></i>
                                    Cheque Print Format
                                </h3>
                                <span class="ribbon ribbon-bookmark bg-lime">
                                    <i class="fa-solid fa-plus me-1"></i> NEW ENTRY
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                        <div class="card border-0 border-end border-4 border-primary">
                            <div class="card-body shadow-sm p-2">
                                <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                    <a href="{{ route('back.to.previous') }}"
                                        class="btn btn-sm btn-outline-dark back-btn">
                                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>  --}}
        <!-- Page Header -->
        {{-- <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="position-relative"
                        style="height:60px;background:linear-gradient(120deg,#1a56db 0%,#4dabf7 60%,#74c0fc 100%);border-radius:12px 12px 0 0;">
                        <span
                            class="d-inline-flex align-items-center justify-content-center rounded-3 border border-4 border-white shadow position-absolute text-white"
                            style="background:linear-gradient(135deg,#1a56db,#4dabf7);width:76px;height:76px;bottom:-38px;left:24px;">
                            <i class="fa-solid fa-money-check" style="font-size:26px;"></i>
                        </span>
                    </div>
                    <div class="px-4 pb-3" style="padding-top:48px !important;">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <h3 class="mb-0 fw-bold">Cheque Print Format</h3>
                                <div class="text-muted small mt-1">Create cheque print formats with their details</div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="{{ route('cheque.index') }}"
                                    class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                                    @include('icons.prev', ['size' => 15])
                                    Back
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}

        <div class="page-body">
            <div class="container-xl">
                <div class="card p-2 shadow-sm">
                    @include('company.pages.setup.cheque._form', ['formMode' => 'create'])
                </div>
            </div>
        </div>
    </div>
@include('company.partials._shortcuts-bar')
@endsection

@section('script')
    <script>
        const storeChequeFormat = "{{ route('cheque.store') }}";
        const chequeListUrl = "{{ route('cheque.index') }}";
        const editChequeProperties = @json($cheque->properties ?? []);
    </script>
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
    <script src="{{ asset('js/cheque/create.js') }}?v={{ hash_file('md5', public_path('js/cheque/create.js')) }}"></script>
@endsection