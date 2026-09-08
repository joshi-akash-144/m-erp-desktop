@include('lib.js')

{{-- Main JS --}}
<script src="{{ asset('js/main.js') }}?v={{ hash_file('md5', public_path('js/main.js')) }}"></script>

<script src="{{ asset('js/helper.js') }}?v={{ hash_file('md5', public_path('js/helper.js')) }}"></script>

<script src="{{ asset('js/theme.js') }}?v={{ hash_file('md5', public_path('js/theme.js')) }}"></script>

<script src="{{ asset('js/tabler/tabler.js') }}?v={{ hash_file('md5', public_path('js/tabler/tabler.js')) }}"></script>

{{-- @vite(['resources/js/core/app.js']) --}}

{{-- Page Script --}}
@yield('scripts')