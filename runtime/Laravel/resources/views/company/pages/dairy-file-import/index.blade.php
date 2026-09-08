@extends('company.layout.app')
@section('title', 'Dairy File Import')

@section('css')
    <style>
        /* ── Drop zone ─────────────────────────────────────────── */
        .import-drop-zone {
            border: 2px dashed #d0d7de;
            border-radius: 8px;
            background: #fafbfc;
            min-height: 140px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: border-color .2s, background .2s;
        }
        .import-drop-zone:hover,
        .import-drop-zone.dragover {
            border-color: #0054a6;
            background: #eef4ff;
        }
        .import-drop-zone .drop-icon { color: #c0c8d0; margin-bottom: 10px; }
        .import-drop-zone .drop-text { font-size: 1.2rem; color: #b0b8c4; font-weight: 400; }
        .import-drop-zone .file-selected-name {
            font-size: 0.9rem; color: #0054a6; font-weight: 600; margin-top: 6px;
        }

        /* ── Form rows ─────────────────────────────────────────── */
        .form-label-col { min-width: 110px; font-weight: 600; color: #3d4554; }
        .import-form-row {
            display: flex; align-items: center; gap: 12px;
            padding: 9px 0; border-bottom: 1px solid #f0f2f5;
        }
        .import-form-row:last-child { border-bottom: none; }
        .import-form-control-wrap { flex: 1; }

        /* ── Column reference cards ────────────────────────────── */
        .col-ref-card {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
            transition: border-color .25s, box-shadow .25s, opacity .25s;
            opacity: 1;
        }
        .col-ref-card.active {
            border-color: #0054a6;
            box-shadow: 0 0 0 3px rgba(0,84,166,.1);
            opacity: 1;
        }
        .col-ref-card.inactive {
            opacity: .45;
        }
        .col-ref-card .ref-header {
            padding: 8px 14px;
            font-weight: 700;
            font-size: .82rem;
            letter-spacing: .5px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .col-ref-card.dairy-file  .ref-header { background: #e8f4fd; color: #0d6efd; }
        .col-ref-card.day-to-day  .ref-header { background: #e8f8f0; color: #198754; }
        .col-ref-card .ref-body { padding: 10px 14px; }
        .col-ref-card .ref-body ol {
            margin: 0; padding-left: 1.2rem;
            font-size: .83rem; color: #495057; line-height: 1.9;
        }
        .col-ref-card .ref-note {
            margin-top: 8px;
            font-size: .78rem;
            color: #6c757d;
            background: #f8f9fa;
            border-left: 3px solid #dee2e6;
            padding: 5px 8px;
            border-radius: 0 4px 4px 0;
        }
        .col-ref-card.active .ref-note { border-left-color: #0054a6; }

        /* no-selection: both cards fully visible, no highlight */
    </style>
@endsection

@section('content')
<div class="page-wrapper">

    {{-- Page Header --}}
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0"
                style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3
                            border-start border-4 border-primary rounded-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center
                                    justify-content-center shadow-sm" style="width:38px;height:38px;">
                            <i class="fs-3 fa-solid fa-file-import"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1"
                                style="letter-spacing:.5px;font-size:1.25rem;">
                                Dairy File Import
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase
                                         shadow-sm px-3 py-1" style="font-size:.75rem;letter-spacing:.5px;">
                                <i class="fa-solid fa-upload me-1"></i> Import
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
            <div class="row g-3 align-items-stretch">

                {{-- ── LEFT: Import form ───────────────────────────────── --}}
                <div class="col-lg-6 col-md-8 col-12">
                    <div class="card shadow-sm h-100">
                        <div class="card-body p-0">
                            <form id="dairy_import_form" enctype="multipart/form-data" autocomplete="off">
                                @csrf
                                <input type="hidden" name="file_path" id="file_path_hidden">

                                <div class="p-3">

                                    {{-- File Type --}}
                                    <div class="import-form-row">
                                        <label class="form-label-col mb-0">File Type:</label>
                                        <div class="import-form-control-wrap">
                                            <select name="file_type" id="file_type" class="form-select select2">
                                                <option value="">Select File Type</option>
                                                <option value="day_to_day">Day To Day Import</option>
                                                <option value="dairy_file">Dairy File</option>
                                            </select>
                                        </div>
                                    </div>

                                    {{-- Date --}}
                                    <div class="import-form-row">
                                        <label class="form-label-col mb-0">Date:</label>
                                        <div class="import-form-control-wrap">
                                            <input type="text" name="date" id="import_date"
                                                class="form-control date-format"
                                                placeholder="DD / MM / YYYY"
                                                value="{{ current_date_dmy() }}">
                                        </div>
                                    </div>

                                    {{-- Select Zone --}}
                                    <div class="import-form-row">
                                        <label class="form-label-col mb-0">Select Zone:</label>
                                        <div class="import-form-control-wrap">
                                            <select name="zone_ids[]" id="zone_ids"
                                                class="form-select select2" multiple>
                                                @isset($zones)
                                                    @foreach ($zones as $zone)
                                                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                                    @endforeach
                                                @endisset
                                            </select>
                                        </div>
                                    </div>

                                    {{-- Import File --}}
                                    <div class="import-form-row align-items-start">
                                        <label class="form-label-col mb-0 pt-2">Import File:</label>
                                        <div class="import-form-control-wrap">
                                            <div class="import-drop-zone" id="drop_zone">
                                                <div class="drop-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="16 16 12 12 8 16"></polyline>
                                                        <line x1="12" y1="12" x2="12" y2="21"></line>
                                                        <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path>
                                                    </svg>
                                                </div>
                                                <span class="drop-text">Drag & drop or click to choose</span>
                                                <span class="file-selected-name d-none" id="selected_file_name"></span>
                                            </div>
                                            <input type="file" name="import_file" id="import_file"
                                                class="d-none" accept=".xlsx,.xls">
                                        </div>
                                    </div>

                                    {{-- Sample File --}}
                                    <div class="import-form-row">
                                        <label class="form-label-col mb-0">Sample File:</label>
                                        <div class="import-form-control-wrap">
                                            <button type="button" id="download_sample_btn"
                                                class="btn btn-info waves-effect btn-sm">
                                                <i class="fa-solid fa-download me-1"></i> Download Sample
                                            </button>
                                        </div>
                                    </div>

                                </div>

                                {{-- Footer Buttons --}}
                                <div class="card-footer bg-light border-top p-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="submit" id="import_btn" class="btn btn-primary waves-effect">
                                            <i class="fa-solid fa-file-import me-1"></i> Import
                                        </button>
                                        <button type="button" id="cancel_btn" class="btn btn-danger waves-effect">
                                            <i class="fa-solid fa-xmark me-1"></i> Cancel
                                        </button>
                                        <button type="button" id="refresh_btn" class="btn btn-secondary waves-effect">
                                            <i class="fa-solid fa-rotate me-1"></i> Refresh
                                        </button>
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>

                {{-- ── RIGHT: Column reference panel ───────────────────── --}}
                <div class="col-lg-6 col-md-4 col-12">
                    <div class="d-flex flex-column gap-3 h-100">

                        {{-- Heading --}}
                        <div class="text-muted fw-semibold" style="font-size:.82rem;letter-spacing:.4px;text-transform:uppercase;">
                            <i class="fa-solid fa-circle-info me-1 text-primary"></i>
                            File Column Order Reference
                        </div>

                        {{-- Dairy File card --}}
                        <div class="col-ref-card dairy-file no-selection" id="ref_dairy_file">
                            <div class="ref-header">
                                <i class="fa-solid fa-file-excel"></i>
                                Dairy File
                            </div>
                            <div class="ref-body">
                                <ol>
                                    <li>Billing Date</li>
                                    <li>Customer P.O. No</li>
                                    <li>Sold to Party</li>
                                    <li>Name of Sold to Party</li>
                                    <li>Material Quantity</li>
                                    <li>Vehicle Number</li>
                                    <li>Transport Zone</li>
                                </ol>
                                <div class="ref-note">
                                    <i class="fa-solid fa-circle-exclamation me-1"></i>
                                    <strong>File name</strong> must exactly match a <strong>Product</strong> name in the Item Master.<br>
                                    Rows are skipped (not errored) when: billing date is missing, vehicle is not in master, or zone is not in master.
                                    Destination is auto-created if not found.
                                </div>
                            </div>
                        </div>

                        {{-- Day To Day card --}}
                        <div class="col-ref-card day-to-day no-selection" id="ref_day_to_day">
                            <div class="ref-header">
                                <i class="fa-solid fa-calendar-days"></i>
                                Day To Day Import
                            </div>
                            <div class="ref-body">
                                <ol>
                                    <li>Billing Date</li>
                                    <li>Customer P.O. No</li>
                                    <li>Sold to Party</li>
                                    <li>Name of Sold to Party</li>
                                    <li>Material Quantity</li>
                                    <li>Vehicle Number</li>
                                    <li>Material Description</li>
                                    <li>Transporter Name</li>
                                    <li>Transport Zone</li>
                                </ol>
                                <div class="ref-note">
                                    <i class="fa-solid fa-circle-exclamation me-1"></i>
                                    <strong>Material Description</strong> must match a product in the Item Master (error if not found).<br>
                                    Rows are skipped when: billing date is missing, vehicle is not in master, or zone is not in master.
                                    Destination is auto-created if not found.
                                    Import is <strong>all-or-nothing</strong> — if any row has an error, nothing is saved.
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                {{-- ─────────────────────────────────────────────────────── --}}

            </div>
        </div>
    </div>

    {{-- Error rows panel --}}
    <div class="page-body pt-0" id="import_error_section" style="display:none;">
        <div class="container-xl">
            <div class="card border-danger shadow-sm">
                <div class="card-header bg-danger text-white d-flex align-items-center justify-content-between py-2">
                    <span class="fw-bold">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        Rows with Errors — <span id="error_row_count">0</span> row(s) skipped
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-light" id="close_error_section">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="card-body p-0" id="import_error_table"></div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('script')
    <script>
        const dairyFileImportRoutes = {
            store:          "{{ route('dairy-file-import.store') }}",
            downloadSample: "{{ route('dairy-file-import.download-sample') }}",
        };
    </script>
    <script src="{{ asset('js/modules/dairy-file-import/index.js') }}?v={{ hash_file('md5', public_path('js/modules/dairy-file-import/index.js')) }}"></script>
    <script>
        // Highlight the active reference card when file type changes
        (function () {
            const fileTypeSelect = document.getElementById('file_type');
            const refDairy  = document.getElementById('ref_dairy_file');
            const refDay    = document.getElementById('ref_day_to_day');

            function updateRefCards() {
                const val = fileTypeSelect.value;
                refDairy.classList.remove('active', 'inactive');
                refDay.classList.remove('active', 'inactive');

                if (val === 'dairy_file') {
                    refDairy.classList.add('active');
                    refDay.classList.add('inactive');
                } else if (val === 'day_to_day') {
                    refDay.classList.add('active');
                    refDairy.classList.add('inactive');
                }
                // no selection → both cards remain fully visible, no extra class
            }

            fileTypeSelect.addEventListener('change', updateRefCards);

            // Also listen for Select2 change events
            $(fileTypeSelect).on('select2:select select2:unselect', updateRefCards);

            updateRefCards();
        })();
    </script>
@endsection
