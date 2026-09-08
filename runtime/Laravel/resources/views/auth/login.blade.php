<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="{{ config('erp_theme.theme') }}"
    data-bs-theme-base="{{ config('erp_theme.theme_base') }}" data-bs-theme-font="{{ config('erp_theme.theme_font') }}"
    data-bs-theme-primary="{{ config('erp_theme.theme_primary') }}"
    data-bs-theme-radius="{{ config('erp_theme.theme_radius') }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/favicon-rounded-32x32.png') }}">
    <title>Sign In — Mahakali ERP</title>
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}?v={{ hash_file('md5', public_path('css/layout.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/auth/login.css') }}?v={{ hash_file('md5', public_path('css/auth/login.css')) }}">
</head>

<body>
<div class="erp-wrapper">

    {{-- ════════════════════════════════════════
         LEFT — BRAND PANEL
    ════════════════════════════════════════ --}}
    <aside class="brand-panel">

        {{-- Dot-grid + gradient shapes --}}
        <div class="bp-bg"></div>
        <div class="bp-orb bp-orb1"></div>
        <div class="bp-orb bp-orb2"></div>

        <div class="bp-inner">

            {{-- Logo + name --}}
            <div class="bp-logo-row">
                <div class="bp-logo-box">
                    <img src="{{ asset('img/brand-logo.png') }}" alt="Mahakali ERP Logo" class="bp-logo-img">
                </div>
                <div>
                    <div class="bp-brand-name">Mahakali ERP</div>
                    <div class="bp-brand-tag">Enterprise Resource Planning</div>
                </div>
            </div>

            {{-- Headline --}}
            <h2 class="bp-headline">
                Everything your business<br>needs, in one platform.
            </h2>

            {{-- ── Accounting Animation Board ── --}}
            <div class="acc-board">

                {{-- Feature Strip --}}
                <div class="acc-feat-row">
                    <div class="acc-feat-card" style="--i:0">
                        <div class="acc-feat-ico acc-feat-blue">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                            </svg>
                        </div>
                        <div class="acc-feat-body">
                            <div class="acc-feat-title">Smart Billing</div>
                            <div class="acc-feat-sub">GST-ready invoices</div>
                        </div>
                        <span class="acc-feat-status s-active"><span class="acc-feat-dot"></span>Active</span>
                    </div>
                    <div class="acc-feat-sep"></div>
                    <div class="acc-feat-card" style="--i:1">
                        <div class="acc-feat-ico acc-feat-emerald">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 0 3-3h7z"/>
                            </svg>
                        </div>
                        <div class="acc-feat-body">
                            <div class="acc-feat-title">Live Ledger</div>
                            <div class="acc-feat-sub">Real-time entries</div>
                        </div>
                        <span class="acc-feat-status s-sync"><span class="acc-feat-dot"></span>Sync</span>
                    </div>
                    <div class="acc-feat-sep"></div>
                    <div class="acc-feat-card" style="--i:2">
                        <div class="acc-feat-ico acc-feat-amber">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                            </svg>
                        </div>
                        <div class="acc-feat-body">
                            <div class="acc-feat-title">Analytics</div>
                            <div class="acc-feat-sub">Multi-branch reports</div>
                        </div>
                        <span class="acc-feat-status s-live"><span class="acc-feat-dot"></span>Live</span>
                    </div>
                </div>

                {{-- Revenue Line Chart --}}
                <div class="acc-chart-panel">
                    <div class="acc-chart-hdr">
                        <span class="acc-chart-title">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                            Business Analytics
                        </span>
                        <span class="acc-chart-period">Overview</span>
                    </div>
                    <div class="acc-chart-wrap">
                        <svg id="accChartSvg" viewBox="0 0 260 70" preserveAspectRatio="none" class="acc-svg">
                            <defs>
                                <linearGradient id="areaGrad" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="rgba(96,165,250,0.4)"/>
                                    <stop offset="100%" stop-color="rgba(96,165,250,0.01)"/>
                                </linearGradient>
                            </defs>
                            <line x1="0" y1="17" x2="260" y2="17" stroke="rgba(255,255,255,.07)" stroke-width="1"/>
                            <line x1="0" y1="35" x2="260" y2="35" stroke="rgba(255,255,255,.07)" stroke-width="1"/>
                            <line x1="0" y1="53" x2="260" y2="53" stroke="rgba(255,255,255,.07)" stroke-width="1"/>
                            <path id="accArea"
                                d="M0,66 C8,62 15,56 23,56 C31,56 38,48 46,46 C54,44 61,56 69,54 C77,52 84,42 92,42 C100,42 107,36 115,34 C123,32 130,44 138,42 C146,40 153,26 161,24 C169,22 176,30 184,28 C192,26 199,18 207,16 C215,14 222,12 230,10 C238,8 245,6 253,4 L253,70 L0,70 Z"
                                fill="url(#areaGrad)" opacity="0"/>
                            <path id="accLine"
                                d="M0,66 C8,62 15,56 23,56 C31,56 38,48 46,46 C54,44 61,56 69,54 C77,52 84,42 92,42 C100,42 107,36 115,34 C123,32 130,44 138,42 C146,40 153,26 161,24 C169,22 176,30 184,28 C192,26 199,18 207,16 C215,14 222,12 230,10 C238,8 245,6 253,4"
                                fill="none" stroke="#60a5fa" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle id="accDot" cx="253" cy="4" r="3.5" fill="#60a5fa" opacity="0"/>
                            <circle id="accDotRing" cx="253" cy="4" r="3.5" fill="none" stroke="#60a5fa" stroke-width="2" opacity="0"/>
                        </svg>
                    </div>
                    <div class="acc-months">
                        <span>Apr</span><span>May</span><span>Jun</span><span>Jul</span>
                        <span>Aug</span><span>Sep</span><span>Oct</span><span>Nov</span>
                        <span>Dec</span><span>Jan</span><span>Feb</span><span>Mar</span>
                    </div>
                </div>

                {{-- Live Transaction Feed --}}
                <div class="acc-ledger">
                    <div class="acc-ledger-hdr">
                        <span class="acc-ledger-title">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                            Live Transactions
                        </span>
                        <span class="acc-live-pill"><span class="acc-live-dot"></span>Live</span>
                    </div>
                    <div id="txnFeed" class="acc-txn-feed"></div>
                </div>

            </div>

            {{-- Feature checklist --}}
            <ul class="bp-features">
                <li>
                    <span class="bp-check"><svg width="11" height="11" viewBox="0 0 12 12" fill="none"><polyline points="2,6 5,9 10,3" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    Purchase &amp; Sales with GST-ready billing
                </li>
                <li>
                    <span class="bp-check"><svg width="11" height="11" viewBox="0 0 12 12" fill="none"><polyline points="2,6 5,9 10,3" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    Godown-wise multi-location inventory management
                </li>
                <li>
                    <span class="bp-check"><svg width="11" height="11" viewBox="0 0 12 12" fill="none"><polyline points="2,6 5,9 10,3" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    Multi-reference clearing in Payables &amp; Receivables
                </li>
                <li>
                    <span class="bp-check"><svg width="11" height="11" viewBox="0 0 12 12" fill="none"><polyline points="2,6 5,9 10,3" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    Multi-user login with role-based access &amp; 2FA
                </li>
            </ul>

        </div>

        {{-- Stats bar --}}
        <div class="bp-stats-bar">
            <div class="bp-stat-item">
                <strong>500+</strong>
                <span>Companies</span>
            </div>
            <div class="bp-stat-divider"></div>
            <div class="bp-stat-item">
                <strong>50K+</strong>
                <span>Active Users</span>
            </div>
            <div class="bp-stat-divider"></div>
            <div class="bp-stat-item">
                <strong>99.9%</strong>
                <span>Uptime SLA</span>
            </div>
        </div>

    </aside>


    {{-- ════════════════════════════════════════
         RIGHT — FORM PANEL
    ════════════════════════════════════════ --}}
    <main class="form-panel">

        {{-- Top-right brand (mobile) --}}
        <div class="fp-mobile-brand">
            <img src="{{ asset('img/brand-logo.png') }}" alt="Logo" class="fp-mobile-logo">
            <span>Mahakali ERP</span>
        </div>

        <div class="login-card">

            {{-- Card header --}}
            <div class="lc-header">
                <div class="lc-brand-row">
                    <div class="lc-brand-logo-wrap">
                        <img src="{{ asset('img/brand-logo.png') }}" alt="Mahakali ERP" class="lc-brand-logo">
                    </div>
                    <div>
                        <div class="lc-brand-name">Mahakali ERP</div>
                        <div class="lc-brand-ver">Enterprise Resource Planning</div>
                    </div>
                </div>
                <h1 class="lc-title">Sign in to your account</h1>
                <p class="lc-sub">Enter your credentials below to continue</p>
            </div>

            {{-- Errors --}}
            @if (session('error') || $errors->any())
                @foreach ($errors->all() as $error)
                    <div class="lc-alert" role="alert">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        {{ $error }}
                    </div>
                @endforeach
            @endif

            {{-- Form --}}
            <form id="loginForm" method="POST" action="{{ route('login') }}" novalidate>
                @csrf

                {{-- Email --}}
                <div class="lc-field">
                    <label class="lc-label" for="email">Email address</label>
                    <div class="lc-input-group">
                        <span class="lc-ico">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </span>
                        <input type="email" id="email" name="email"
                               class="lc-input"
                               placeholder="you@company.com"
                               value="{{ old('email') }}"
                               required autofocus autocomplete="email">
                    </div>
                </div>

                {{-- Password --}}
                <div class="lc-field">
                    <div class="lc-label-row">
                        <label class="lc-label" for="password">Password</label>
                    </div>
                    <div class="lc-input-group">
                        <span class="lc-ico">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <input type="password" id="password" name="password"
                               class="lc-input"
                               placeholder="Enter your password"
                               required autocomplete="current-password">
                        <button type="button" id="togglePwd" class="lc-eye" tabindex="-1" title="Toggle password">
                            <svg id="eyeShow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg id="eyeHide" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Remember + Forgot --}}
                {{-- <div class="lc-options-row">
                    <label class="lc-remember">
                        <input type="checkbox" name="remember" id="remember">
                        <span class="lc-cb-box"></span>
                        Keep me signed in
                    </label>
                </div> --}}

                {{-- Submit --}}
                <button type="submit" class="lc-btn-submit" id="submitBtn">
                    <span class="lc-btn-default">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
                        </svg>
                        Sign In
                    </span>
                    <span class="lc-btn-loading" style="display:none">
                        <svg class="lc-spin" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                        </svg>
                        Verifying…
                    </span>
                </button>

            </form>

            {{-- Card footer --}}
            <div class="lc-footer">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                Protected by 256-bit TLS encryption
            </div>

        </div>

        <div class="fp-copyright">
            &copy; {{ date('Y') }} Mahakali ERP &nbsp;&middot;&nbsp; v2.0
        </div>

    </main>

</div>

<script src="{{ asset('js/auth/login.js') }}?v={{ hash_file('md5', public_path('js/auth/login.js')) }}"></script>
</body>
</html>
