@extends('company-selector.layout.app')

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
                        <i class="fa-solid fa-building-user" style="font-size:28px;"></i>
                    </span>
                </div>
                <div class="px-4 pb-3" style="padding-top:48px !important;">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <h3 class="mb-0 fw-bold">Assign Company to User</h3>
                            <div class="text-muted small mt-1">Select a company and assign one or more users</div>
                        </div>
                        <a href="{{ route('company-users.index') }}"
                            class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                            @include('icons.prev', ['size' => 15])
                            Back to List
                        </a>
                    </div>
                </div>
            </div>

            {{-- ── FORM CARD ── --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-bottom px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary"
                            style="width:36px;height:36px;flex-shrink:0;">
                            <i class="fa-solid fa-link" style="font-size:16px;"></i>
                        </span>
                        <div>
                            <div class="fw-bold lh-1">Assignment Details</div>
                            <div class="text-muted small">Choose a company and the users to assign</div>
                        </div>
                    </div>
                </div>

                <form id="companyUserForm">
                    @csrf

                    <div class="card-body p-4">
                        <div class="row g-4">

                            {{-- Company --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Company <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent text-muted">
                                        <i class="fa-solid fa-building" style="font-size:14px;"></i>
                                    </span>
                                    <select name="company_id" id="company_id" class="form-select select2-company">
                                        <option value="">Select Company</option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}">
                                                {{ $company->name }}
                                                @if($company->code) ({{ $company->code }}) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="invalid-feedback" id="company_id_error"></div>
                            </div>

                            {{-- Users (multiple) --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Users <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent text-muted">
                                        <i class="fa-solid fa-users" style="font-size:14px;"></i>
                                    </span>
                                    <select name="user_ids[]" id="user_ids" class="form-select select2-users" multiple>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="text-muted small mt-1">You can select multiple users at once.</div>
                                <div class="invalid-feedback" id="user_ids_error"></div>
                            </div>

                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="card-footer bg-transparent border-top px-4 py-3">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('company-users.index') }}"
                                class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M15 6l-6 6l6 6"/>
                                </svg>
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary form-save-btn d-inline-flex align-items-center gap-1" id="submitBtn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12l5 5l10-10"/>
                                </svg>
                                Save Assignment
                            </button>
                        </div>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>
@include('company.partials._shortcuts-bar')
@endsection

@section('scripts')
<script>
    const storeCompanyUserUrl = "{{ route('company-users.store') }}";
    const companyUsersIndexUrl = "{{ route('company-users.index') }}";
</script>
<script src="{{ asset('js/company-users/form.js') }}?v={{ filemtime(public_path('js/company-users/form.js')) }}"></script>
@endsection
