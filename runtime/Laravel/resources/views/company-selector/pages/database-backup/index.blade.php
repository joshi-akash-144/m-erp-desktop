@extends('company-selector.layout.app')

@section('title', 'Database Backup')

@section('css')
    <link rel="stylesheet"
        href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <link rel="stylesheet"
        href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
    <style>
        #database_backup_register_table thead tr th {
            font-family: monospace;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid rgb(66, 65, 65);
            background-color: #E3E7EB;
        }

        #database_backup_register_table tbody tr td {
            font-family: monospace;
            font-size: 13px;
            border: 1px solid rgb(66, 65, 65);
        }
    </style>
@endsection
@section('content')
    <div class="page-wrapper">
        <div class="page-body pt-4">
            <div class="container-xl">
                {{-- ── HERO BANNER ── --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="position-relative"
                        style="height:60px;background:linear-gradient(120deg,#1a56db 0%,#4dabf7 60%,#74c0fc 100%);border-radius:12px 12px 0 0;">
                        <span
                            class="d-inline-flex align-items-center justify-content-center rounded-3 border border-4 border-white shadow position-absolute text-white"
                            style="background:linear-gradient(135deg,#1a56db,#4dabf7);width:76px;height:76px;bottom:-38px;left:24px;">
                            <i class="fa-solid fa-database" style="font-size:26px;"></i>
                        </span>
                    </div>
                    <div class="px-4 pb-3" style="padding-top:48px !important;">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <h3 class="mb-0 fw-bold">Database Backup</h3>
                                <div class="text-muted small mt-1">Generate and manage database backups</div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="{{ route('company-selection.index') }}"
                                    class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                                    @include('icons.prev', ['size' => 15])
                                    Dashboard
                                </a>
                                @can('database_backup.generate')
                                    <a href="javascript:void(0)" id="generate_backup_btn"
                                        class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                                        @include('icons.cloud-arrow-down', ['size' => 16])
                                        Generate Backup
                                    </a>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── FILTER + TABLE CARD ── --}}
                <div class="card border-0 shadow-sm rounded-4">

                    {{-- Tabulator table --}}
                    <div class="card-body p-0">
                        <div id="database_backup_register_table"></div>
                    </div>

                    {{-- Scroll loader --}}
                    <div id="scrollLoader" class="card-footer text-center border-top" style="display:none;">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading…</span>
                        </div>
                        <span class="ms-2 text-muted small">Loading more records…</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('scripts')
    <script>
        const databaseBackupListUrl = "{{ route('database-backup.list') }}";
        const databaseBackupStoreUrl = "{{ route('database-backup.store') }}";
        const databaseBackupDownloadUrl = "{{ route('database-backup.download', ':file') }}";
        const databaseBackupDeleteUrl = "{{ route('database-backup.destroy', ':file') }}";
    </script>
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
    <script
        src="{{ asset('js/modules/database-backup/index.js') }}?v={{ hash_file('md5', public_path('js/modules/database-backup/index.js')) }}"></script>
@endsection