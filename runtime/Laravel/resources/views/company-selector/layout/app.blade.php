<!DOCTYPE html>
<html
  lang="{{ str_replace('_', '-', app()->getLocale()) }}"
  data-bs-theme="{{ config('erp_theme.theme') }}"
  data-bs-theme-base="{{ config('erp_theme.theme_base') }}"
  data-bs-theme-font="{{ config('erp_theme.theme_font') }}"
  data-bs-theme-primary="{{ config('erp_theme.theme_primary') }}"
  data-bs-theme-radius="{{ config('erp_theme.theme_radius') }}"
>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />

    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/favicon-rounded-32x32.png') }}">
    <title>Company - Selection</title>
    {{-- CSS --}}
    @include('company-selector.layout.styles')
    
</head>

<body>
    <div class="loader-content" style="display: none; z-index:12000">
        <div class="loader-spinner"></div>
        <div class="loader-text">⏳ Saving data, please wait...</div>
    </div>
    <div class="page">
        {{-- Navbar --}}
        @include('company-selector.layout.navbar')

        {{-- Content --}}
        <div class="page-wrapper">
            @yield('content')
        </div>
    </div>
    {{-- Script --}}
    @include('company-selector.layout.scripts')

    
    
    {{-- Modal --}}
    <div id="global_modal_container"></div>
    {{-- @include('_partial.alert') --}}
</body>
</html>
