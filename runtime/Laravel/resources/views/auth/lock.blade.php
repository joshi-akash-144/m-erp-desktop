<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="{{ config('erp_theme.theme') }}"
    data-bs-theme-base="{{ config('erp_theme.theme_base') }}" data-bs-theme-font="{{ config('erp_theme.theme_font') }}"
    data-bs-theme-primary="{{ config('erp_theme.theme_primary') }}"
    data-bs-theme-radius="{{ config('erp_theme.theme_radius') }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Screen Locked</title>
    @include('company-selector.layout.styles')
</head>

<body class="d-flex flex-column">
    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="text-center mb-4">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('img/brand-logo.png') }}" width="80" alt="Brand Logo">
                </a>
            </div>

            {{-- 🔐 UNLOCK SCREEN FORM --}}
            <form id="screen_unlock_form" class="card card-md" method="POST" action="{{ route('unlock.screen') }}"
                autocomplete="off">
                @csrf
                <div class="card-body text-center">
                    @if (session('error') || $errors->any())
                        @foreach ($errors->all() as $error)
                            <div class="alert alert-danger" role="alert">
                                <div class="alert-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" class="icon alert-icon icon-2">
                                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                                        <path d="M12 8v4" />
                                        <path d="M12 16h.01" />
                                    </svg>
                                </div>
                                {{ $error }}
                            </div>
                        @endforeach
                    @endif
                    <div class="mb-3">
                        <h2 class="card-title">🔒 Screen Locked</h2>
                        <p class="text-secondary">Welcome back, {{ current_user()->name }}</p>
                    </div>

                    <div class="mb-4">
                        <span class="avatar avatar-xl mb-3 text-primary bg-primary-subtle fs-1 rounded-circle">
                            {{ user_initials() }}
                        </span>
                        <div class="small text-muted">{{ current_user()->email }}</div>
                    </div>

                    <div class="mb-4">
                        <div class="input-icon mb-2">
                            <span class="input-icon-addon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <circle cx="12" cy="16" r="1" />
                                    <rect x="5" y="11" width="14" height="10" rx="2" />
                                    <path d="M8 11v-5a4 4 0 0 1 8 0v5" />
                                </svg>
                            </span>
                            <input type="password" name="password" autofocus required class="form-control text-center"
                                placeholder="Enter password to unlock">
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        {{-- Unlock --}}
                        <button type="submit" class="btn btn-primary w-100 waves-effect">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-lock-open"
                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M5 11a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z" />
                                <path d="M8 11v-5a4 4 0 0 1 8 0" />
                            </svg>
                            Unlock
                        </button>

                        {{-- Logout --}}
                        <a href="#"
                            onclick="event.preventDefault(); document.getElementById('logout_form').submit();"
                            class="btn btn-outline-danger w-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-logout waves-effect"
                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path
                                    d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" />
                                <path d="M9 12h12l-3 -3" />
                                <path d="M18 15l3 -3" />
                            </svg>
                            Logout
                        </a>
                    </div>
                </div>
            </form>

            {{-- 🔴 Hidden Logout Form --}}
            <form id="logout_form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>

        </div>
    </div>
</body>

</html>
