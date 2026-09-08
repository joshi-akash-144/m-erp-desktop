{{-- Core CSS --}}
{{-- @vite(['resources/css/core/layout.css']) --}}
{{-- Bootstrap CSS --}}
{{-- Theme CSS --}}
{{-- @vite(['resources/css/core/theme.css']) --}}

{{-- Library CSS --}}
@include('lib.css')

{{-- Layout CSS --}}
<link rel="stylesheet" href="{{ asset('css/layout.css') }}?v={{ hash_file('md5', public_path('css/layout.css')) }}"></link>

<link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ hash_file('md5', public_path('css/theme.css')) }}"></link>

<link rel="stylesheet" href="{{ asset('css/custom.css') }}?v={{ hash_file('md5', public_path('css/custom.css')) }}"></link>

<link rel="stylesheet" href="{{ asset('css/select2.css') }}?v={{ hash_file('md5', public_path('css/select2.css')) }}"></link>


{{-- Page Css --}}
@yield('css')

{{-- Index CSS --}}
{{-- @vite(['resources/css/core/custom.css']) --}}


{{-- Page Css --}}
{{-- @yield('page-css') --}}

<!-- BEGIN: Theme CSS-->
<!-- Fonts -->
{{-- <link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link
  href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&ampdisplay=swap"
  rel="stylesheet" />

<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

<!-- Fonts Icons -->
@vite(['resources/assets/vendor/fonts/iconify/iconify.css'])

@vite(['resources/assets/vendor/scss/pages/page-icons.scss'])

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" /> --}}

<!-- BEGIN: Vendor CSS-->
{{-- @vite(['resources/assets/vendor/libs/node-waves/node-waves.scss']) --}}

{{-- @if ($configData['hasCustomizer'])
  @vite(['resources/assets/vendor/libs/pickr/pickr-themes.scss'])
@endif --}}

<!-- Core CSS -->
{{-- @vite(['resources/assets/vendor/scss/core.scss', 'resources/assets/css/demo.css',
'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss']) --}}

<!-- Vendor Styles -->
{{-- @vite(['resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss', 'resources/assets/vendor/libs/typeahead-js/typeahead.scss','resources/assets/vendor/libs/notyf/notyf.scss', 'resources/assets/vendor/libs/animate-css/animate.scss','resources/assets/vendor/libs/spinkit/spinkit.scss', 'resources/assets/vendor/libs/notiflix/notiflix.scss','resources/assets/vendor/libs/sweetalert2/sweetalert2.scss']) --}}
{{-- @yield('vendor-style') --}}

<!-- Page Styles -->
{{-- @yield('page-style') --}}

<!-- app CSS -->
{{-- @vite(['resources/css/app.css']) --}}
<!-- END: app CSS-->
