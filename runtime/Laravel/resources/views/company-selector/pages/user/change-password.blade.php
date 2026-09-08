@extends('company-selector.layout.app')

@section('scripts')
    <script src="{{ asset('js/users/change-password.js') }}"></script>
@endsection

@section('content')
<div class="page-wrapper">
    <div class="page-body pt-4">
        <div class="container-xl">

            {{-- ── PROFILE HERO ── --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="position-relative" style="height:60px; background: linear-gradient(120deg,#1a56db 0%,#4dabf7 60%,#74c0fc 100%); border-radius: 12px 12px 0 0;">
                    <span class="avatar rounded-3 fw-bold text-white d-inline-flex align-items-center justify-content-center border border-4 border-white shadow position-absolute"
                        style="background:linear-gradient(135deg,#1a56db,#4dabf7);font-size:26px;width:76px;height:76px;bottom:-38px;left:24px;">
                        {{ user_initials() }}
                    </span>
                </div>
                <div class="px-4 pb-3" style="padding-top:48px !important;">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <h3 class="mb-0 fw-bold">{{ $user->name }}</h3>
                                @if ($user->status == 1)
                                    <span class="badge bg-success-lt text-success rounded-pill px-2">Active</span>
                                @else
                                    <span class="badge bg-danger-lt text-danger rounded-pill px-2">Inactive</span>
                                @endif
                            </div>
                            <div class="text-muted small mt-1">{{ '@'.$user->username }} · {{ $user->email }}</div>
                        </div>
                        <a href="{{ route('company-selection.index') }}"
                            class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                            @include('icons.prev', ['size' => 15])
                            Dashboard
                        </a>
                    </div>
                </div>

                {{-- ── TAB NAVIGATION ── --}}
                <div class="border-top px-4">
                    <ul class="nav nav-underline nav-fill" role="tablist">
                        <li class="nav-item">
                            <a href="{{ route('profiles.profile') }}"
                                class="nav-link d-inline-flex align-items-center gap-2 py-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2a5 5 0 1 1-5 5 5 5 0 0 1 5-5z"/>
                                    <path d="M14 14a5 5 0 0 1 5 5v1a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-1a5 5 0 0 1 5-5h4z"/>
                                </svg>
                                My Account
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('profiles.changepassword') }}"
                                class="nav-link d-inline-flex align-items-center gap-2 py-3 active">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="5" y="11" width="14" height="10" rx="2"/>
                                    <circle cx="12" cy="16" r="1"/>
                                    <path d="M8 11v-4a4 4 0 0 1 8 0v4"/>
                                </svg>
                                Change Password
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('profiles.two-factor.index') }}"
                                class="nav-link d-inline-flex align-items-center gap-2 py-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1-8.5 15a12 12 0 0 1-8.5-15a12 12 0 0 0 8.5-3"/>
                                    <path d="M9 12l2 2l4-4"/>
                                </svg>
                                Two-Factor Auth
                                @if (auth()->user()->two_factor_confirmed_at)
                                    <span class="badge bg-success text-white ms-1" style="font-size:10px;">ON</span>
                                @endif
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- ── ALERTS ── --}}
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible mb-4">
                    <div class="d-flex align-items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-1">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <ul class="mb-0 ps-2">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session('success'))
                <div class="alert alert-success alert-dismissible mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><path d="M9 12l2 2l4-4"/>
                        </svg>
                        {{ session('success') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        {{ session('error') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- ── FORM CARD ── --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-bottom px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary"
                            style="width:36px;height:36px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="5" y="11" width="14" height="10" rx="2"/>
                                <circle cx="12" cy="16" r="1"/>
                                <path d="M8 11v-4a4 4 0 0 1 8 0v4"/>
                            </svg>
                        </span>
                        <div>
                            <div class="fw-bold lh-1">Change Password</div>
                            <div class="text-muted small">Choose a strong password to keep your account secure</div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">

                    {{-- Tip --}}
                    <div class="alert alert-info border-0 bg-info-lt d-flex align-items-start gap-2 mb-4" style="font-size:.875rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="text-info flex-shrink-0 mt-1">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <span>Use at least <strong>8 characters</strong> with a mix of uppercase, lowercase, numbers &amp; symbols.</span>
                    </div>

                    <form method="POST" action="{{ route('profiles.updatepassword') }}">
                        @csrf
                        @method('PUT')
                        <div class="row g-4">

                            {{-- New Password --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted">
                                            <rect x="5" y="11" width="14" height="10" rx="2"/><circle cx="12" cy="16" r="1"/><path d="M8 11v-4a4 4 0 0 1 8 0v4"/>
                                        </svg>
                                    </span>
                                    <input type="password" name="password" id="pw_new"
                                        class="form-control @error('password') is-invalid @enderror"
                                        placeholder="Enter new password">
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePw('pw_new',this)" tabindex="-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            {{-- Confirm Password --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted">
                                            <path d="M11.5 21h-4.5a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v.5"/>
                                            <path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0-2 0"/>
                                            <path d="M8 11v-4a4 4 0 1 1 8 0v4"/>
                                            <path d="M15 19l2 2l4-4"/>
                                        </svg>
                                    </span>
                                    <input type="password" name="password_confirmation" id="pw_confirm"
                                        class="form-control @error('password_confirmation') is-invalid @enderror"
                                        placeholder="Confirm new password">
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePw('pw_confirm',this)" tabindex="-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                    @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('profiles.profile') }}"
                                class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M15 6l-6 6l6 6"/>
                                </svg>
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary form-save-btn d-inline-flex align-items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12l5 5l10-10"/>
                                </svg>
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@include('company.partials._shortcuts-bar')
<script>
function togglePw(id, btn) {
    const el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
    btn.querySelector('svg').style.opacity = el.type === 'text' ? '0.45' : '1';
}
</script>
@endsection
