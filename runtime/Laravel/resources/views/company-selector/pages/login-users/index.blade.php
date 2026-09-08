@extends('company.layout.app')
@section('title', 'Login User')

@section('css')
    <link rel="stylesheet"
        href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <link rel="stylesheet"
        href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    <style>
        #login_users_table thead tr th {
            font-family: monospace;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid rgb(66, 65, 65);
            background-color: #E3E7EB;
        }

        #login_users_table tbody tr td {
            font-family: monospace;
            font-size: 13px;
            border: 1px solid rgb(66, 65, 65);
        }
    </style>
@endsection
@section('content')
<div class="page-wrapper" style="width:1700px;">
    <div class="page-body pt-4">
        <div class="container-xl">
 {{-- ── HERO BANNER ── --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="position-relative"
                        style="height:60px;background:linear-gradient(120deg,#1a56db 0%,#4dabf7 60%,#74c0fc 100%);border-radius:12px 12px 0 0;">
                        <span
                            class="d-inline-flex align-items-center justify-content-center rounded-3 border border-4 border-white shadow position-absolute text-white"
                            style="background:linear-gradient(135deg,#1a56db,#4dabf7);width:76px;height:76px;bottom:-38px;left:24px;">
                            <i class="fa-solid fa-users" style="font-size:26px;"></i>
                        </span>
                    </div>
                    <div class="px-4 pb-3" style="padding-top:48px !important;">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <h3 class="mb-0 fw-bold">Login User Listing</h3>
                                <div class="text-muted small mt-1">Manage and monitor active user sessions</div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                {{-- <a href="{{ route('company-selection.index') }}"
                                    class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                                    @include('icons.prev', ['size' => 15])
                                    Dashboard
                                </a> --}}
                        <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn waves-effect">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                            </div>
                        </div>
                    </div>
                </div>

            {{-- Table Card --}}
            <div class="card border-0 shadow-sm rounded-3">
                {{-- Search --}}
                <div class="card-body border-bottom py-3 px-4">
                    <div class="input-icon" style="max-width:320px;">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search…" autocomplete="off">
                    </div>
                </div>

                {{-- Table --}}
                <div class="card-body p-0">
                    <div id="login_users_table"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
    <script>
        const loginUsersListUrl = "{{ route('login-users.list') }}";
        const loginUsersForceLogoutUrl = "{{ route('login-users.force-logout', ':id') }}";
    </script>
    <script src="{{ asset('js/modules/login-users/index.js') }}?v={{ hash_file('md5', public_path('js/modules/login-users/index.js')) }}"></script>
@endsection
