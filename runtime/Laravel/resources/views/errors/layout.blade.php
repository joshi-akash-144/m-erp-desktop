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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Error')</title>
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}?v={{ hash_file('md5', public_path('css/layout.css')) }}"></link>
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ hash_file('md5', public_path('css/theme.css')) }}"></link>

    {{-- Core CSS --}}
    {{-- Bootstrap CSS --}}
    {{-- Theme CSS --}}
</head>

<body class="border-top-wide border-primary">
    @yield('content')
</body>

</html>
