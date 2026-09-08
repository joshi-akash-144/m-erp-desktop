@extends('company-selector.layout.app')

@section('scripts')
<script>
function confirmDisable2FA() {
    Swal.fire({
        title: 'Disable Two-Factor Authentication?',
        text: 'This will remove the extra security from your account. You can re-enable it at any time.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, disable it',
        cancelButtonText: 'Keep enabled',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('disable-2fa-form').submit();
        }
    });
}
function toggleCodes(btn) {
    const panel = document.getElementById('recovery-codes-panel');
    const hidden = panel.style.display === 'none';
    panel.style.display = hidden ? 'block' : 'none';
    btn.innerHTML = hidden
        ? `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg> Hide Codes`
        : `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Show Codes`;
}
</script>
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
                                <h3 class="mb-0 fw-bold">{{ auth()->user()->name }}</h3>
                                @if (auth()->user()->status == 1)
                                    <span class="badge bg-success-lt text-success rounded-pill px-2">Active</span>
                                @else
                                    <span class="badge bg-danger-lt text-danger rounded-pill px-2">Inactive</span>
                                @endif
                                @if ($isConfirmed)
                                    <span class="badge bg-success text-white rounded-pill px-2">2FA On</span>
                                @endif
                            </div>
                            <div class="text-muted small mt-1">{{ '@'.auth()->user()->username }} · {{ auth()->user()->email }}</div>
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
                                class="nav-link d-inline-flex align-items-center gap-2 py-3">
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
                                class="nav-link d-inline-flex align-items-center gap-2 py-3 active">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1-8.5 15a12 12 0 0 1-8.5-15a12 12 0 0 0 8.5-3"/>
                                    <path d="M9 12l2 2l4-4"/>
                                </svg>
                                Two-Factor Auth
                                @if ($isConfirmed)
                                    <span class="badge bg-success text-white ms-1" style="font-size:10px;">ON</span>
                                @endif
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- ── ALERTS ── --}}
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
            @if (session('status'))
                <div class="alert alert-info alert-dismissible mb-4">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible mb-4">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- ── MAIN CARD ── --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-bottom px-4 py-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary"
                                style="width:36px;height:36px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1-8.5 15a12 12 0 0 1-8.5-15a12 12 0 0 0 8.5-3"/>
                                    <path d="M9 12l2 2l4-4"/>
                                </svg>
                            </span>
                            <div>
                                <div class="fw-bold lh-1">Two-Factor Authentication</div>
                                <div class="text-muted small">Add an extra layer of security at login</div>
                            </div>
                        </div>
                        {{-- Status badge --}}
                        @if ($isConfirmed)
                            <span class="badge bg-success-lt text-success d-inline-flex align-items-center gap-1 px-3 py-2 rounded-pill">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12l5 5l10-10"/>
                                </svg>
                                Enabled
                            </span>
                        @elseif ($isPending)
                            <span class="badge bg-warning-lt text-warning d-inline-flex align-items-center gap-1 px-3 py-2 rounded-pill">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>
                                </svg>
                                Pending Confirmation
                            </span>
                        @else
                            <span class="badge bg-secondary-lt text-secondary d-inline-flex align-items-center gap-1 px-3 py-2 rounded-pill">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 6l-12 12"/><path d="M6 6l12 12"/>
                                </svg>
                                Disabled
                            </span>
                        @endif
                    </div>
                </div>

                <div class="card-body p-4">

                    {{-- ══════════════════════════════════════
                         STATE 1 — Not enabled
                    ══════════════════════════════════════ --}}
                    @if (!$isConfirmed && !$isPending)

                        <div class="row justify-content-center">
                            <div class="col-lg-7 text-center py-4">
                                <div class="d-flex align-items-center justify-content-center rounded-circle mx-auto mb-4"
                                    style="width:72px;height:72px;background:#fff3cd;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" viewBox="0 0 24 24"
                                        fill="none" stroke="#b45309" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1-8.5 15a12 12 0 0 1-8.5-15a12 12 0 0 0 8.5-3"/>
                                        <path d="M9 12l2 2l4-4"/>
                                    </svg>
                                </div>
                                <h4 class="fw-bold mb-2">Two-factor authentication is not enabled</h4>
                                <p class="text-muted mb-4">
                                    When enabled, you'll be prompted for a secure 6-digit code from your
                                    authenticator app each time you sign in — protecting your account even
                                    if your password is compromised.
                                </p>
                                <form method="POST" action="{{ route('profiles.two-factor.enable') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1-8.5 15a12 12 0 0 1-8.5-15a12 12 0 0 0 8.5-3"/>
                                            <path d="M9 12l2 2l4-4"/>
                                        </svg>
                                        Enable Two-Factor Authentication
                                    </button>
                                </form>
                            </div>
                        </div>

                    {{-- ══════════════════════════════════════
                         STATE 2 — Pending confirmation
                    ══════════════════════════════════════ --}}
                    @elseif ($isPending)

                        <div class="row g-4">
                            {{-- QR + instructions --}}
                            <div class="col-12 col-lg-6">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary"
                                        style="width:30px;height:30px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M2 12a5 5 0 0 0 5 5 8 8 0 0 1 5 2 8 8 0 0 1 5-2 5 5 0 0 0 5-5V7h-5a8 8 0 0 0-5 2 8 8 0 0 0-5-2H2z"/>
                                        </svg>
                                    </span>
                                    <span class="fw-semibold">Setup your authenticator app</span>
                                </div>

                                {{-- Steps --}}
                                <div class="d-flex flex-column gap-3 mb-4">
                                    @foreach ([
                                        'Install an authenticator app such as <strong>Google Authenticator</strong>, <strong>Authy</strong>, or <strong>Microsoft Authenticator</strong>.',
                                        'Open the app and scan the QR code below, or manually enter the secret key.',
                                        'Enter the 6-digit code shown in your app to confirm setup.',
                                    ] as $i => $step)
                                    <div class="d-flex align-items-start gap-3">
                                        <span class="d-flex align-items-center justify-content-center rounded-circle fw-bold text-white flex-shrink-0"
                                            style="width:26px;height:26px;background:#1a56db;font-size:.72rem;">{{ $i+1 }}</span>
                                        <span class="text-muted" style="font-size:.9rem;">{!! $step !!}</span>
                                    </div>
                                    @endforeach
                                </div>

                                {{-- QR code --}}
                                <div class="d-inline-flex flex-column align-items-center border rounded-3 p-3 bg-light-subtle mb-3">
                                    {!! $qrCodeSvg !!}
                                    <div class="text-muted mt-2" style="font-size:.78rem;">Scan with your authenticator app</div>
                                </div>

                                {{-- Secret key --}}
                                <div>
                                    <label class="form-label text-muted mb-1" style="font-size:.8rem;">Or enter this key manually:</label>
                                    <div class="d-flex align-items-center gap-2 p-2 border rounded-3 bg-light-subtle">
                                        <code class="flex-fill text-break" style="font-size:.88rem;letter-spacing:.04em;">{{ $secretKey }}</code>
                                    </div>
                                </div>
                            </div>

                            {{-- Confirm form --}}
                            <div class="col-12 col-lg-6">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span class="d-flex align-items-center justify-content-center rounded-3 bg-success-subtle text-success"
                                        style="width:30px;height:30px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M5 12l5 5l10-10"/>
                                        </svg>
                                    </span>
                                    <span class="fw-semibold">Confirm setup</span>
                                </div>
                                <p class="text-muted mb-4" style="font-size:.9rem;">
                                    Enter the 6-digit code from your authenticator app to activate two-factor authentication.
                                </p>

                                <form id="confirm-2fa-form" method="POST" action="{{ route('profiles.two-factor.confirm') }}">
                                    @csrf
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Authentication Code</label>
                                        <input type="text" name="code"
                                            class="form-control form-control-lg text-center @error('code') is-invalid @enderror"
                                            placeholder="000 000" maxlength="8"
                                            inputmode="numeric" autocomplete="one-time-code" autofocus>
                                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M5 12l5 5l10-10"/>
                                            </svg>
                                            Confirm &amp; Enable
                                        </button>
                                        {{-- Cancel button linked to the separate form below via the `form` attribute --}}
                                        <button type="submit" form="cancel-2fa-setup-form"
                                            class="btn btn-outline-secondary">Cancel</button>
                                    </div>
                                </form>

                                {{-- Standalone cancel form — must NOT be nested inside confirm form --}}
                                <form id="cancel-2fa-setup-form" method="POST"
                                    action="{{ route('profiles.two-factor.disable') }}" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </div>

                    {{-- ══════════════════════════════════════
                         STATE 3 — Enabled & confirmed
                    ══════════════════════════════════════ --}}
                    @else

                        {{-- Recovery Codes --}}
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="d-flex align-items-center justify-content-center rounded-3 bg-success-subtle text-success"
                                        style="width:30px;height:30px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="5" y="2" width="14" height="20" rx="2"/><path d="M9 7h6"/><path d="M9 11h6"/><path d="M9 15h4"/>
                                        </svg>
                                    </span>
                                    <div>
                                        <div class="fw-semibold lh-1">Recovery Codes</div>
                                        <div class="text-muted" style="font-size:.82rem;">Each code can be used once if you lose your device</div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
                                    onclick="toggleCodes(this)">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    Show Codes
                                </button>
                            </div>

                            <div id="recovery-codes-panel" style="display:none;">
                                <div class="row row-cols-1 row-cols-sm-2 g-2 mb-3">
                                    @foreach ($recoveryCodes as $code)
                                        <div class="col">
                                            <div class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 bg-light-subtle">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="text-success flex-shrink-0">
                                                    <circle cx="12" cy="12" r="10"/><path d="M9 12l2 2l4-4"/>
                                                </svg>
                                                <code style="font-size:.88rem;letter-spacing:.05em;">{{ $code }}</code>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" style="font-size:.87rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        class="flex-shrink-0 mt-1">
                                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                        <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                                    </svg>
                                    <span>These codes will not be shown again after you leave this page. Save them in a password manager.</span>
                                </div>

                                <form method="POST" action="{{ route('profiles.two-factor.recovery-codes') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 11A8.1 8.1 0 0 0 4.5 9M4 5v4h4"/>
                                            <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                                        </svg>
                                        Regenerate Recovery Codes
                                    </button>
                                </form>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Disable section --}}
                        <div class="d-flex align-items-start gap-3 p-4 border border-danger-subtle rounded-3 bg-danger-lt">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="text-danger flex-shrink-0 mt-1">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            <div class="flex-fill">
                                <div class="fw-semibold text-danger mb-1">Disable Two-Factor Authentication</div>
                                <p class="text-muted mb-3" style="font-size:.88rem;">
                                    This removes the extra security layer from your account. You can re-enable it at any time.
                                </p>
                                <form method="POST" action="{{ route('profiles.two-factor.disable') }}" id="disable-2fa-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-danger d-inline-flex align-items-center gap-1"
                                        onclick="confirmDisable2FA()">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 6l-12 12"/><path d="M6 6l12 12"/>
                                        </svg>
                                        Disable Two-Factor Authentication
                                    </button>
                                </form>
                            </div>
                        </div>

                    @endif

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
