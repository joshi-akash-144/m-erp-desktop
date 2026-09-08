@extends('company-selector.layout.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/select2.css') }}?v={{ hash_file('md5', public_path('css/select2.css')) }}">
<style>
/* ── Select2 overrides ───────────────────────────────── */
.select2-container--bootstrap-5.select2-container--focus .select2-selection,
.select2-container--bootstrap-5.select2-container--open .select2-selection {
    box-shadow: none !important;
    border-color: #ced4da !important;
}
.select2-container--bootstrap-5 .select2-selection { min-height: 36px !important; }
.select2-container--bootstrap-5 .select2-search__field:focus { box-shadow: none !important; }
.select2-container--bootstrap-5 .select2-dropdown .select2-results__options
    .select2-results__option.select2-results__option--selected {
    color: #fff !important;
    background-color: #206bc4 !important;
}
/* GST field read-only tint */
#gst_number.readonly { background: #f8f9fa; }

/* Fix JustValidate error positioning inside Bootstrap input groups */
.input-group { flex-wrap: wrap; }
.input-group > .just-validate-error-label { width: 100%; margin-top: 0.25rem; }
.just-validate-error-label { font-size: 0.875em; color: #dc3545; font-weight: 500; }
</style>
@endsection

@section('content')
<div class="page-wrapper">
    <div class="page-body pt-4">
        <div class="container-xl">

            {{-- ── HERO BANNER ── --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="position-relative"
                    style="height:60px;background:linear-gradient(120deg,#1a56db 0%,#4dabf7 60%,#74c0fc 100%);border-radius:12px 12px 0 0;">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 border border-4 border-white shadow position-absolute text-white"
                        style="background:linear-gradient(135deg,#1a56db,#4dabf7);width:76px;height:76px;bottom:-38px;left:24px;">
                        <i class="fa-solid fa-building" style="font-size:28px;"></i>
                    </span>
                </div>
                <div class="px-4 pb-3" style="padding-top:48px !important;">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <h3 class="mb-0 fw-bold">Create New Company</h3>
                            <div class="text-muted small mt-1">Fill in the details to register a new company</div>
                        </div>
                        <a href="{{ route('back.to.previous') }}"
                            class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                            @include('icons.prev', ['size' => 15])
                            Back
                        </a>
                    </div>
                </div>
            </div>

            {{-- ── FORM ── --}}
            <form method="POST" autocomplete="off" id="company_form" action="{{ route('companies.store') }}">
                @csrf
                <input type="hidden" name="uuid" id="uuid" value="{{ uuid() }}">

                {{-- ── SECTION 1: COMPANY IDENTITY ── --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-transparent border-bottom px-4 py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary"
                                style="width:36px;height:36px;flex-shrink:0;">
                                <i class="fa-solid fa-building fa-lg"></i>
                            </span>
                            <div>
                                <div class="fw-bold lh-1">Company Identity</div>
                                <div class="text-muted small">Official names used in records and printing</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">

                            {{-- Name --}}
                            <div class="col-md-4">
                                <label for="name" class="form-label fw-semibold">
                                    Company Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent">
                                        <i class="fa-solid fa-building text-muted"></i>
                                    </span>
                                    <input type="text" name="name" id="name" class="form-control"
                                        placeholder="Enter company name">
                                </div>
                            </div>

                            {{-- Print Name --}}
                            <div class="col-md-4">
                                <label for="print_name" class="form-label fw-semibold">
                                    Print Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent">
                                        <i class="fa-regular fa-file-lines text-muted"></i>
                                    </span>
                                    <input type="text" name="print_name" id="print_name" class="form-control"
                                        placeholder="Name on invoices / documents">
                                </div>
                            </div>

                            {{-- Legal Name --}}
                            <div class="col-md-4">
                                <label for="legal_name" class="form-label fw-semibold">
                                    Legal Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent">
                                        <i class="fa-solid fa-address-card text-muted"></i>
                                    </span>
                                    <input type="text" name="legal_name" id="legal_name" class="form-control"
                                        placeholder="Registered legal name">
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- ── SECTION 2: ADDRESS & CONTACT ── --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-transparent border-bottom px-4 py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-flex align-items-center justify-content-center rounded-3 bg-success-subtle text-success"
                                style="width:36px;height:36px;flex-shrink:0;">
                                <i class="fa-solid fa-location-dot fa-lg"></i>
                            </span>
                            <div>
                                <div class="fw-bold lh-1">Address &amp; Contact</div>
                                <div class="text-muted small">Location details and communication information</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">

                            {{-- Country --}}
                            <div class="col-md-2">
                                <label for="country_id" class="form-label fw-semibold">
                                    Country <span class="text-danger">*</span>
                                </label>
                                <select name="country_id" id="country_id" class="form-select select2">
                                    <option value="">-- Select --</option>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->id }}">{{ $country->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- State --}}
                            <div class="col-md-3">
                                <label for="state_id" class="form-label fw-semibold">
                                    State <span class="text-danger">*</span>
                                </label>
                                <select name="state_id" id="state_id" class="form-select select2">
                                    <option value="">-- Select --</option>
                                    @foreach ($states as $state)
                                        <option value="{{ $state->id }}">{{ $state->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- PIN --}}
                            <div class="col-md-2">
                                <label for="postal_code" class="form-label fw-semibold">
                                    PIN Code
                                    <span class="form-help ms-1" data-bs-toggle="popover"
                                        data-bs-placement="top" data-bs-html="true"
                                        data-bs-content="<p>PIN Code must be 6 digits, numeric only.</p>"
                                        style="border:0.5px solid lightgray;">?</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent">
                                        <i class="fa-solid fa-location-dot text-muted"></i>
                                    </span>
                                    <input type="text" name="postal_code" id="postal_code" class="form-control"
                                        placeholder="e.g. 400001" maxlength="6">
                                </div>
                            </div>

                            {{-- Address One --}}
                            <div class="col-md-5">
                                <label for="address_one" class="form-label fw-semibold">Address Line 1</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent">
                                        <i class="fa-solid fa-map-pin text-muted"></i>
                                    </span>
                                    <input type="text" name="address_one" id="address_one" class="form-control"
                                        placeholder="Street, Building, Area">
                                </div>
                            </div>

                            {{-- Address Two --}}
                            <div class="col-md-4">
                                <label for="address_two" class="form-label fw-semibold">Address Line 2</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent">
                                        <i class="fa-solid fa-map-pin text-muted"></i>
                                    </span>
                                    <input type="text" name="address_two" id="address_two" class="form-control"
                                        placeholder="Landmark, Locality">
                                </div>
                            </div>

                            {{-- Mobile --}}
                            <div class="col-md-4">
                                <label for="mobile_number" class="form-label fw-semibold">Mobile Number</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent">
                                        <i class="fa-solid fa-mobile text-muted"></i>
                                    </span>
                                    <input type="text" name="mobile_number" id="mobile_number" class="form-control"
                                        placeholder="+91 00000 00000">
                                </div>
                            </div>

                            {{-- Email --}}
                            <div class="col-md-4">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent">
                                        <i class="fa-solid fa-envelope text-muted"></i>
                                    </span>
                                    <input type="text" name="email" id="email" class="form-control"
                                        placeholder="company@example.com">
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- ── SECTION 3: BUSINESS & TAX ── --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-transparent border-bottom px-4 py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-flex align-items-center justify-content-center rounded-3 bg-warning-subtle text-warning"
                                style="width:36px;height:36px;flex-shrink:0;">
                                <i class="fa-solid fa-file-invoice fa-lg"></i>
                            </span>
                            <div>
                                <div class="fw-bold lh-1">Business &amp; Tax Details</div>
                                <div class="text-muted small">Dealer type, tax registration and financial year</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">

                            {{-- Type of Dealer --}}
                            <div class="col-md-3">
                                @php $typeOfDealer = config('constants.type_of_dealer'); @endphp
                                <label for="type_of_dealer" class="form-label fw-semibold">
                                    Type of Dealer <span class="text-danger">*</span>
                                </label>
                                <select name="type_of_dealer" id="type_of_dealer" class="form-select select2">
                                    <option value="">-- Select --</option>
                                    @foreach ($typeOfDealer as $id => $dealer)
                                        <option value="{{ $id }}">{{ $dealer }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- GST Number --}}
                            <div class="col-md-3">
                                <label for="gst_number" class="form-label fw-semibold">GST Number</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent">
                                        <i class="fa-solid fa-file-invoice text-muted"></i>
                                    </span>
                                    <input type="text" name="gst_number" id="gst_number" class="form-control readonly"
                                        placeholder="15-digit GSTIN" maxlength="15" readonly>
                                </div>
                                <div class="form-hint">Enabled when dealer type is <em>Registered</em>.</div>
                            </div>

                            {{-- CIN --}}
                            <div class="col-md-3">
                                <label for="cin" class="form-label fw-semibold">CIN</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent">
                                        <i class="fa-solid fa-id-card text-muted"></i>
                                    </span>
                                    <input type="text" name="cin" id="cin" class="form-control"
                                        placeholder="Corporate Identity Number">
                                </div>
                            </div>

                            {{-- PAN --}}
                            <div class="col-md-3">
                                <label for="pan" class="form-label fw-semibold">PAN No</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent">
                                        <i class="fa-solid fa-id-badge text-muted"></i>
                                    </span>
                                    <input type="text" name="pan" id="pan" class="form-control"
                                        placeholder="e.g. ABCDE1234F" maxlength="10" style="text-transform:uppercase">
                                </div>
                            </div>

                            {{-- Financial Year Start --}}
                            <div class="col-md-3">
                                <label for="financial_year_start" class="form-label fw-semibold">
                                    Year Begin From <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent">
                                        <i class="fa-solid fa-calendar-days text-muted"></i>
                                    </span>
                                    <input type="text" name="financial_year_start" id="financial_year_start"
                                        class="form-control" placeholder="DD-MM-YYYY">
                                </div>
                            </div>

{{-- TDS Applicable --}}
                            <div class="col-md-3 d-flex align-items-center mt-4">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="tds_applicable" id="tds_applicable" value="1">
                                    <label class="form-check-label fw-semibold" for="tds_applicable">
                                        TDS Applicable
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="card-footer bg-transparent border-top px-4 py-3">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('back.to.previous') }}"
                                class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M15 6l-6 6l6 6"/>
                                </svg>
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary form-save-btn d-inline-flex align-items-center gap-1 form-save-btn"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="Save (Alt + S)">
                                <i class="fa-solid fa-floppy-disk"></i>
                                Save Company
                            </button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>
@include('company.partials._shortcuts-bar')
@endsection

@section('scripts')
<script>
    const createCompanyUrl   = "{{ route('companies.store') }}";
    const FINANCIAL_YEAR_START = "{{ date('Y-04-01') }}";
    const FINANCIAL_YEAR_END   = "{{ date('Y-03-31', strtotime('+1 year')) }}";
</script>
<script src="{{ asset('js/company.js') }}?v={{ hash_file('md5', public_path('js/company.js')) }}"></script>
@endsection
