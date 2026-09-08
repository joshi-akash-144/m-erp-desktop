<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-bs-theme="{{ config('erp_theme.theme') }}"
    data-bs-theme-base="{{ config('erp_theme.theme_base') }}"
    data-bs-theme-font="{{ config('erp_theme.theme_font') }}"
    data-bs-theme-primary="{{ config('erp_theme.theme_primary') }}"
    data-bs-theme-radius="{{ config('erp_theme.theme_radius') }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/favicon-rounded-32x32.png') }}">

    <title>ERP - Two-Factor Authentication</title>

    <link rel="stylesheet" href="{{ asset('css/layout.css') }}?v={{ hash_file('md5', public_path('css/layout.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/auth/login.css') }}?v={{ hash_file('md5', public_path('css/auth/login.css')) }}">

    <style>
        /* ── Tabs ── */
        .tab-btn {
            background: none;
            border: none;
            padding: 8px 20px;
            font-weight: 600;
            font-size: 0.88rem;
            color: #6b7280;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            transition: all .2s ease;
        }
        .tab-btn.active {
            color: #0061f2;
            border-bottom-color: #0061f2;
        }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* ── 2FA Icon (form side) ── */
        .two-fa-icon {
            width: 64px;
            height: 64px;
            background: #e8f3ff;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        /* ════════════════════════════════════════
           SECURITY ANIMATION BOARD
           ════════════════════════════════════════ */
        .sec-board {
            display: flex;
            flex-direction: column;
            gap: 10px;
            flex: 1;
        }


        /* ── TOTP Timer Panel ── */
        .sec-totp-panel {
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.09);
            border-radius: 12px;
            padding: 11px 14px 10px;
            animation: accFadeUp .5s ease both;
            animation-delay: .4s;
        }
        .sec-totp-hdr {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .sec-totp-hdr-title {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: .72rem;
            font-weight: 600;
            color: rgba(255,255,255,.75);
        }
        .sec-totp-expires {
            font-size: .64rem;
            color: rgba(255,255,255,.38);
            font-weight: 400;
        }
        .sec-totp-expires strong { color: #fbbf24; font-weight: 700; }

        .sec-totp-body {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        /* SVG ring */
        .sec-timer-ring { width: 58px; height: 58px; flex-shrink: 0; }
        .sec-ring-bg    { fill: none; stroke: rgba(255,255,255,.10); stroke-width: 4; }
        .sec-ring-fill  { fill: none; stroke: #fbbf24; stroke-width: 4; stroke-linecap: round; transition: stroke-dashoffset .9s linear; }
        .sec-ring-label { font-size: 10px; font-weight: 800; fill: rgba(255,255,255,.85); font-family: system-ui,sans-serif; }

        /* 6-digit code display */
        .sec-totp-code {
            display: flex;
            align-items: center;
            gap: 4px;
            flex: 1;
            justify-content: center;
        }
        .sec-digit {
            width: 24px;
            height: 32px;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.13);
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-variant-numeric: tabular-nums;
            perspective: 200px;
        }
        .sec-digit.flipping { animation: digitFlip .28s ease; }
        @keyframes digitFlip {
            0%   { transform: rotateX(0deg);   opacity: 1; }
            45%  { transform: rotateX(90deg);  opacity: 0; }
            55%  { transform: rotateX(-90deg); opacity: 0; }
            100% { transform: rotateX(0deg);   opacity: 1; }
        }
        .sec-digit-sep {
            color: rgba(255,255,255,.25);
            font-size: .8rem;
            margin: 0 1px;
            line-height: 1;
        }
        .sec-totp-info {
            font-size: .63rem;
            color: rgba(255,255,255,.32);
            text-align: center;
            margin-top: 8px;
            line-height: 1.4;
        }

        /* ── Security Event Feed ── */
        .sec-event-feed {
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.09);
            border-radius: 12px;
            padding: 10px 13px;
            animation: accFadeUp .5s ease both;
            animation-delay: .55s;
            overflow: hidden;
            flex: 1;
        }
        .sec-event-hdr {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .sec-event-hdr-title {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: .72rem;
            font-weight: 600;
            color: rgba(255,255,255,.75);
        }
        .sec-event-list { display: flex; flex-direction: column; gap: 5px; min-height: 96px; }

        .sec-event-item {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 6px 9px;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 8px;
            font-size: .71rem;
            overflow: hidden;
        }
        .sec-event-item.txn-enter { animation: txnSlideIn .38s cubic-bezier(.22,1,.36,1) both; }
        .sec-event-item.txn-exit  { animation: txnSlideOut .32s ease forwards; }

        .sec-event-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .sec-event-dot.ok     { background: #4ade80; box-shadow: 0 0 4px rgba(74,222,128,.6); }
        .sec-event-dot.warn   { background: #fbbf24; box-shadow: 0 0 4px rgba(251,191,36,.6); }
        .sec-event-dot.danger { background: #f87171; box-shadow: 0 0 4px rgba(248,113,113,.6); }

        .sec-event-desc { flex: 1; color: rgba(255,255,255,.72); font-weight: 500; }
        .sec-event-time { font-size: .61rem; color: rgba(255,255,255,.28); flex-shrink: 0; }
    </style>
</head>

<body>
<div class="erp-wrapper">

    {{-- ════════════════════════════════════════
         LEFT — BRAND PANEL
    ════════════════════════════════════════ --}}
    <aside class="brand-panel">
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
            <h2 class="bp-headline">Secure access,<br>every single time.</h2>

            {{-- ── Security Animation Board ── --}}
            <div class="sec-board">

                {{-- Security Feature Strip --}}
                <div class="acc-feat-row">
                    <div class="acc-feat-card" style="--i:0">
                        <div class="acc-feat-ico acc-feat-blue">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </div>
                        <div class="acc-feat-body">
                            <div class="acc-feat-title">TLS Encrypted</div>
                            <div class="acc-feat-sub">256-bit end-to-end</div>
                        </div>
                        <span class="acc-feat-status s-active"><span class="acc-feat-dot"></span>Active</span>
                    </div>
                    <div class="acc-feat-sep"></div>
                    <div class="acc-feat-card" style="--i:1">
                        <div class="acc-feat-ico acc-feat-amber">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <div class="acc-feat-body">
                            <div class="acc-feat-title">TOTP Standard</div>
                            <div class="acc-feat-sub">RFC 6238 codes</div>
                        </div>
                        <span class="acc-feat-status s-live"><span class="acc-feat-dot"></span>Live</span>
                    </div>
                    <div class="acc-feat-sep"></div>
                    <div class="acc-feat-card" style="--i:2">
                        <div class="acc-feat-ico acc-feat-emerald">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </div>
                        <div class="acc-feat-body">
                            <div class="acc-feat-title">Access Control</div>
                            <div class="acc-feat-sub">Role-based access</div>
                        </div>
                        <span class="acc-feat-status s-sync"><span class="acc-feat-dot"></span>Sync</span>
                    </div>
                </div>

                {{-- TOTP Ring Timer --}}
                <div class="sec-totp-panel">
                    <div class="sec-totp-hdr">
                        <span class="sec-totp-hdr-title">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            TOTP Code Window
                        </span>
                        <span class="sec-totp-expires">Refreshes in <strong id="totpTimer">30s</strong></span>
                    </div>
                    <div class="sec-totp-body">
                        <svg class="sec-timer-ring" viewBox="0 0 58 58">
                            <circle class="sec-ring-bg"   cx="29" cy="29" r="24"/>
                            <circle class="sec-ring-fill" id="totpRingFill" cx="29" cy="29" r="24"
                                    transform="rotate(-90 29 29)"/>
                            <text class="sec-ring-label" x="29" y="33" text-anchor="middle" id="totpCountText">30</text>
                        </svg>
                        <div class="sec-totp-code">
                            <span id="sd1" class="sec-digit">•</span>
                            <span id="sd2" class="sec-digit">•</span>
                            <span id="sd3" class="sec-digit">•</span>
                            <span class="sec-digit-sep">–</span>
                            <span id="sd4" class="sec-digit">•</span>
                            <span id="sd5" class="sec-digit">•</span>
                            <span id="sd6" class="sec-digit">•</span>
                        </div>
                    </div>
                    <div class="sec-totp-info">Your authenticator app generates a new code every 30 seconds</div>
                </div>

                {{-- Live Security Event Feed --}}
                <div class="sec-event-feed">
                    <div class="sec-event-hdr">
                        <span class="sec-event-hdr-title">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            Security Log
                        </span>
                        <span class="acc-live-pill"><span class="acc-live-dot"></span>Live</span>
                    </div>
                    <div id="secEventList" class="sec-event-list"></div>
                </div>

            </div>

        </div>

        <div class="bp-stats-bar">
            <div class="bp-stat-item">
                <strong>256-bit</strong>
                <span>TLS Encrypted</span>
            </div>
            <div class="bp-stat-divider"></div>
            <div class="bp-stat-item">
                <strong>TOTP</strong>
                <span>RFC 6238</span>
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

        {{-- Mobile brand --}}
        <div class="fp-mobile-brand">
            <img src="{{ asset('img/brand-logo.png') }}" alt="Logo" class="fp-mobile-logo">
            <span>Mahakali ERP</span>
        </div>

        <div class="login-card">

            {{-- Icon --}}
            <div class="two-fa-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                     fill="none" stroke="#0061f2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 13a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-6z"/>
                    <path d="M11 16a1 1 0 1 0 2 0 1 1 0 0 0-2 0"/>
                    <path d="M8 11V7a4 4 0 1 1 8 0v4"/>
                </svg>
            </div>

            <div class="lc-header" style="text-align:center;">
                <h1 class="lc-title" style="font-size:1.3rem;">Two-Factor Authentication</h1>
                <p class="lc-sub">Confirm access using your authenticator app or a recovery code.</p>
            </div>

            {{-- Tabs --}}
            <div class="d-flex border-bottom mb-4">
                <button class="tab-btn active" onclick="switchTab('code', this)">Authenticator Code</button>
                <button class="tab-btn" onclick="switchTab('recovery', this)">Recovery Code</button>
            </div>

            {{-- Errors --}}
            @if ($errors->any())
                <div class="lc-alert" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0-18 0"/>
                        <path d="M12 8v4"/><path d="M12 16h.01"/>
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            {{-- Tab: Authenticator Code --}}
            <div id="tab-code" class="tab-panel active">
                <form method="POST" action="{{ route('two-factor.store') }}">
                    @csrf
                    <div class="lc-field">
                        <label class="lc-label" for="code">6-digit code</label>
                        <input type="text" id="code" name="code"
                               class="form-control form-control-lg text-center @error('code') is-invalid @enderror"
                               style="letter-spacing:.2em;font-size:1.1rem;"
                               placeholder="000000"
                               maxlength="8"
                               inputmode="numeric"
                               autocomplete="one-time-code"
                               autofocus>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="lc-btn-submit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12l5 5l10-10"/>
                        </svg>
                        Verify Code
                    </button>
                </form>
            </div>

            {{-- Tab: Recovery Code --}}
            <div id="tab-recovery" class="tab-panel">
                <form method="POST" action="{{ route('two-factor.store') }}">
                    @csrf
                    <div class="lc-field">
                        <label class="lc-label" for="recovery_code">Recovery code</label>
                        <input type="text" id="recovery_code" name="recovery_code"
                               class="form-control form-control-lg @error('recovery_code') is-invalid @enderror"
                               placeholder="xxxxxxxxxx-xxxxxxxxxx"
                               autocomplete="one-time-code">
                        @error('recovery_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text text-muted mt-1">Each recovery code can only be used once.</div>
                    </div>

                    <button type="submit" class="lc-btn-submit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12l5 5l10-10"/>
                        </svg>
                        Verify Recovery Code
                    </button>
                </form>
            </div>

            <div class="lc-footer mt-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5"/><path d="M12 5l-7 7 7 7"/>
                </svg>
                <a href="{{ route('login') }}" style="color:inherit;text-decoration:none;">Back to Login</a>
            </div>

        </div>

        <div class="fp-copyright">
            &copy; {{ date('Y') }} Mahakali ERP &nbsp;&middot;&nbsp; v2.0
        </div>

    </main>

</div>

<script>
    // ── Tab switcher ──────────────────────────────────────────────
    function switchTab(tab, btn) {
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        btn.classList.add('active');
        const input = document.getElementById(tab === 'code' ? 'code' : 'recovery_code');
        if (input) setTimeout(() => input.focus(), 50);
    }

    @error('recovery_code')
        document.addEventListener('DOMContentLoaded', () => {
            switchTab('recovery', document.querySelectorAll('.tab-btn')[1]);
        });
    @enderror


    // ── Security Animation Board ──────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {

        // 1. TOTP ring countdown
        const CIRC     = 2 * Math.PI * 24;   // r = 24
        const ringFill = document.getElementById('totpRingFill');
        const timerLbl = document.getElementById('totpTimer');
        const countTxt = document.getElementById('totpCountText');
        const digits   = [1,2,3,4,5,6].map(n => document.getElementById('sd' + n));

        if (ringFill) {
            ringFill.style.strokeDasharray = CIRC;

            let sec = 30;
            let code = genCode();
            showCode(code, false);

            function tick() {
                const frac = sec / 30;
                ringFill.style.strokeDashoffset = CIRC * (1 - frac);
                if (timerLbl) timerLbl.textContent = sec + 's';
                if (countTxt) countTxt.textContent = sec;

                // Colour shifts to red in final 10s
                const stroke = sec <= 10 ? '#f87171' : '#fbbf24';
                ringFill.style.stroke = stroke;
                if (timerLbl) timerLbl.style.color = sec <= 10 ? '#fca5a5' : '';

                sec--;
                if (sec < 0) {
                    sec = 29;
                    code = genCode();
                    showCode(code, true);
                }
            }

            tick();
            setInterval(tick, 1000);
        }

        function genCode() {
            return Array.from({length: 6}, () => Math.floor(Math.random() * 10));
        }
        function showCode(code, animate) {
            digits.forEach((el, i) => {
                if (!el) return;
                if (animate) {
                    el.classList.remove('flipping');
                    void el.offsetWidth;
                    el.classList.add('flipping');
                    setTimeout(() => { el.textContent = code[i]; }, 140);
                } else {
                    el.textContent = code[i];
                }
            });
        }

        // 2. Security event feed
        const EVENTS = [
            { status: 'ok',     desc: '2FA verified — Rajan M.',         time: 'Just now'   },
            { status: 'ok',     desc: 'Login success — Admin panel',      time: '1 min ago'  },
            { status: 'warn',   desc: 'New device detected — Priya S.',   time: '3 min ago'  },
            { status: 'ok',     desc: '2FA verified — Purchase manager',  time: '5 min ago'  },
            { status: 'danger', desc: 'Invalid code — 2 failed attempts', time: '7 min ago'  },
            { status: 'ok',     desc: 'Session started — Accounts dept.', time: '9 min ago'  },
            { status: 'warn',   desc: 'Recovery code used — Anand K.',    time: '11 min ago' },
            { status: 'ok',     desc: '2FA verified — Sales team',        time: '13 min ago' },
            { status: 'danger', desc: 'Brute-force blocked — IP flagged', time: '15 min ago' },
            { status: 'ok',     desc: '2FA re-enrolled — Neha T.',        time: '18 min ago' },
        ];

        const list = document.getElementById('secEventList');
        if (!list) return;

        let cursor = 0;
        [0, 1, 2].forEach(i => { list.appendChild(makeEvent(EVENTS[i], false)); cursor++; });

        function makeEvent(ev, animate) {
            const el = document.createElement('div');
            el.className = 'sec-event-item' + (animate ? ' txn-enter' : '');
            el.innerHTML =
                `<span class="sec-event-dot ${ev.status}"></span>` +
                `<span class="sec-event-desc">${ev.desc}</span>` +
                `<span class="sec-event-time">${ev.time}</span>`;
            return el;
        }

        setInterval(() => {
            const items = list.querySelectorAll('.sec-event-item');
            const last  = items[items.length - 1];
            last.classList.add('txn-exit');
            setTimeout(() => { if (last.parentNode) last.remove(); }, 320);

            const next = makeEvent(EVENTS[cursor % EVENTS.length], true);
            list.insertBefore(next, list.firstChild);
            cursor++;
        }, 2400);
    });
</script>
</body>
</html>
