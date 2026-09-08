{{-- Library CSS --}}
@include('lib.css')

{{-- Layout CSS --}}
<link rel="stylesheet" href="{{ asset('css/layout.css') }}?v={{ hash_file('md5', public_path('css/layout.css')) }}"></link>

<link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ hash_file('md5', public_path('css/theme.css')) }}"></link>

<link rel="stylesheet" href="{{ asset('css/custom.css') }}?v={{ hash_file('md5', public_path('css/custom.css')) }}"></link>


{{-- Page Css --}}
@yield('css')
{{-- @yield('page-css') --}}