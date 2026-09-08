<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="{{ config('erp_theme.theme') }}"
    data-bs-theme-base="{{ config('erp_theme.theme_base') }}" data-bs-theme-font="{{ config('erp_theme.theme_font') }}"
    data-bs-theme-primary="{{ config('erp_theme.theme_primary') }}"
    data-bs-theme-radius="{{ config('erp_theme.theme_radius') }}">


<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    {{-- link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/favicon-rounded-32x32.png') }}"> --}}
    {{-- <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap"
    rel="stylesheet" /> --}}
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/favicon-rounded-32x32.png') }}">
    <title>
        @yield('title', 'ERP') {{ app()->environment('local') ? '[LOCAL]' : '' }}
    </title>
    @include('company.layout.styles')
    {{-- Global JS Routes --}}
    {{-- @include('_partial.routes.global-route') --}}
</head>


<body class="layout-fluid">
    <div class="page page-center d-none" id="page_loader">
        <div class="container container-slim py-4">
            <div class="text-center">
                <!-- Spinning logo container -->
                <div class="mb-4" style="animation: pulse 2s ease-in-out infinite;">
                    <a href="." class="navbar-brand navbar-brand-autodark">
                        <img src="{{ asset('img/brand-logo.png') }}" width="70" height="23" alt="Company Logo">
                    </a>
                </div>

                <h3 class="text-muted mb-2">Signing out...</h3>
                <p class="text-muted small font-monospace">Securely ending your {{ company_name() }} session</p>

                <!-- Elegant spinner -->
                <div class="spinner-border text-pink mt-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>

    <div class="loader-content" style="display: none;z-index:12000">
        <div class="loader-spinner"></div>
        <div class="loader-text">⏳ Saving data, please wait...</div>
    </div>
    {{-- <!-- BEGIN GLOBAL THEME SCRIPT -->
    
    <!-- END GLOBAL THEME SCRIPT --> --}}
    <div class="page">
        @include('company.layout.navbar')

        @yield('content')

    </div>

    {{-- <script>
        exitCompany = "{{ route('company-selection.exit') }}"
    </script> --}}

    {{-- @vite(['resources/js/core/company-init.js', 'resources/js/core/modal-loader.js']) --}}



    {{-- Script --}}
    @include('company.layout.scripts')

    <div id="global_modal_container"></div>

    {{-- <script>
        document.querySelector('.js-load-modal')?.addEventListener('click', async (e) => {
            

        })
    </script> --}}

    {{-- Modal --}}
    @include('_partial.alert')
</body>

</html>
