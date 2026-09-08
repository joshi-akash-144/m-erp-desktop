@extends('errors.layout')

@section('title', 'Under Maintenance — Mahakali ERP')

@section('content')
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    min-height: 100vh;
    background: #f0f4ff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    position: relative;
    overflow: hidden;
  }

  /* Subtle gradient blobs */
  body::before {
    content: '';
    position: fixed;
    top: -200px; left: -200px;
    width: 600px; height: 600px;
    background: radial-gradient(circle, rgba(59,130,246,0.12), transparent 70%);
    pointer-events: none;
  }
  body::after {
    content: '';
    position: fixed;
    bottom: -150px; right: -150px;
    width: 500px; height: 500px;
    background: radial-gradient(circle, rgba(99,102,241,0.1), transparent 70%);
    pointer-events: none;
  }

  /* Card */
  .maint-card {
    position: relative;
    z-index: 10;
    background: #ffffff;
    border-radius: 24px;
    padding: 48px 44px 40px;
    max-width: 500px;
    width: 90%;
    text-align: center;
    box-shadow:
      0 1px 3px rgba(0,0,0,0.06),
      0 8px 24px rgba(0,0,0,0.08),
      0 32px 64px rgba(0,0,0,0.07);
    border: 1px solid rgba(226,232,240,0.8);
  }

  /* Top accent bar */
  .maint-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, #1d4ed8, #3b82f6, #6366f1);
    border-radius: 24px 24px 0 0;
  }

  /* Logo row */
  .logo-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    margin-bottom: 32px;
    padding-bottom: 28px;
    border-bottom: 1px solid #f1f5f9;
  }
  .logo-box {
    width: 52px; height: 52px;
    background: #ffffff;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 2px 8px rgba(15,23,42,0.12), 0 0 0 1px #e2e8f0;
    flex-shrink: 0;
    overflow: hidden;
  }
  .logo-box img {
    width: 40px; height: 40px;
    object-fit: contain;
  }
  .logo-text { text-align: left; }
  .logo-name {
    font-size: 17px;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.3px;
    line-height: 1.2;
  }
  .logo-sub {
    font-size: 10.5px;
    color: #94a3b8;
    letter-spacing: 0.9px;
    text-transform: uppercase;
    margin-top: 2px;
  }

  /* Gear animation */
  .gear-wrap {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 28px;
    width: 110px; height: 110px;
  }
  .gear-bg {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: linear-gradient(135deg, #eff6ff, #eef2ff);
    border: 1.5px solid #dbeafe;
  }
  .gear-ring {
    position: absolute;
    inset: 10px;
    border-radius: 50%;
    border: 1.5px dashed #bfdbfe;
    animation: ringRotate 10s linear infinite;
  }
  @keyframes ringRotate {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
  }

  .gear-main {
    width: 52px; height: 52px;
    color: #3b82f6;
    animation: gearSpin 5s linear infinite;
    position: relative; z-index: 2;
    filter: drop-shadow(0 2px 6px rgba(59,130,246,0.3));
  }
  @keyframes gearSpin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
  }

  /* Badge */
  .maint-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1d4ed8;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    padding: 5px 14px;
    border-radius: 100px;
    margin-bottom: 16px;
  }
  .badge-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #3b82f6;
    animation: blink 1.4s ease-in-out infinite;
  }
  @keyframes blink {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.25; }
  }

  /* Title */
  .maint-title {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.5px;
    line-height: 1.3;
    margin-bottom: 12px;
  }
  .maint-desc {
    font-size: 14px;
    color: #64748b;
    line-height: 1.75;
    margin-bottom: 28px;
    max-width: 360px;
    margin-left: auto;
    margin-right: auto;
  }

  /* Divider */
  .maint-divider {
    height: 1px;
    background: #f1f5f9;
    margin-bottom: 24px;
  }

  /* Status row */
  .status-row {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-bottom: 28px;
    flex-wrap: wrap;
  }
  .status-chip {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 7px 13px;
    font-size: 12px;
    color: #475569;
    font-weight: 500;
  }
  .status-chip .s-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: #22c55e;
    flex-shrink: 0;
  }
  .status-chip.warn .s-dot { background: #f59e0b; }

  /* Button */
  .maint-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #1d4ed8, #3b82f6);
    color: #fff !important;
    font-size: 14px;
    font-weight: 600;
    padding: 12px 32px;
    border-radius: 10px;
    text-decoration: none !important;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(59,130,246,0.4);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    letter-spacing: 0.2px;
    margin-bottom: 24px;
  }
  .maint-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(59,130,246,0.5);
  }
  .maint-btn:active { transform: translateY(0); }

  /* Footer */
  .maint-footer {
    font-size: 12px;
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
  }
</style>

<div class="maint-card">

  {{-- Logo --}}
  <div class="logo-row">
    <div class="logo-box">
      <img src="{{ asset('img/brand-logo.png') }}" alt="Mahakali ERP Logo">
    </div>
    <div class="logo-text">
      <div class="logo-name">Mahakali ERP</div>
      <div class="logo-sub">Enterprise Resource Planning</div>
    </div>
  </div>

  {{-- Animated gear --}}
  <div class="gear-wrap">
    <div class="gear-bg"></div>
    <div class="gear-ring"></div>
    <svg class="gear-main" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
      <path d="M12 15.5A3.5 3.5 0 0 1 8.5 12 3.5 3.5 0 0 1 12 8.5a3.5 3.5 0 0 1 3.5 3.5 3.5 3.5 0 0 1-3.5 3.5m7.43-2.92c.04-.32.07-.64.07-.96 0-.32-.03-.65-.07-.97l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65A.49.49 0 0 0 14 2h-4c-.25 0-.46.18-.49.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.23-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64L4.57 11c-.04.32-.07.65-.07.97 0 .32.03.64.07.96l-2.11 1.66c-.19.15-.25.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1.01c.52.4 1.08.73 1.69.98l.38 2.65c.03.24.24.42.49.42h4c.25 0 .46-.18.49-.42l.38-2.65c.61-.25 1.17-.58 1.69-.98l2.49 1.01c.22.08.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.66z"/>
    </svg>
  </div>

  {{-- Badge + Title --}}
  <div class="maint-badge">
    <span class="badge-dot"></span>
    Scheduled Maintenance
  </div>
  <h1 class="maint-title">We'll be back shortly</h1>
  <p class="maint-desc">
    We're performing scheduled maintenance to improve your experience.
    The system will be back online soon. Thank you for your patience.
  </p>

  <div class="maint-divider"></div>

  {{-- Status chips --}}
  <div class="status-row">
    <div class="status-chip">
      <span class="s-dot"></span>
      Database Online
    </div>
    <div class="status-chip warn">
      <span class="s-dot"></span>
      App Updating
    </div>
    <div class="status-chip">
      <span class="s-dot"></span>
      Data Secure
    </div>
  </div>

  {{-- Retry button --}}
  <a href="{{ url()->previous() ?: url('/') }}" class="maint-btn">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
      <polyline points="23 4 23 10 17 10"/>
      <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
    </svg>
    Try Again
  </a>

  {{-- Footer --}}
  <div class="maint-footer">
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
    </svg>
    &copy; {{ date('Y') }} Mahakali ERP &nbsp;&middot;&nbsp; All data encrypted &amp; secure
  </div>

</div>

<script>
  // Auto-reload every 30 seconds
  setTimeout(() => location.reload(), 30000);
</script>
@endsection
