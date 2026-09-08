@extends('company.layout.app')
@section('title', 'Mail Logs – List')

@section('css')
<link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
<link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
@endsection

@section('content')
<div class="page-wrapper" style="width: 1700px">

    {{-- Page Header --}}
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-info rounded-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width:38px;height:38px;">
                            <i class="fa-solid fa-envelope-open-text fs-5"></i>
                        </div>
                        <h2 class="page-title text-info fw-bolder mb-0" style="font-size:1.25rem;">
                            Mail Logs
                        </h2>
                        <span class="badge badge-pill bg-teal text-white fw-bold text-uppercase px-3 py-1" style="font-size:0.75rem;">
                            <i class="fa-solid fa-list me-1"></i> List
                        </span>
                    </div>
                    
                    @php
                        $senderEmail = \App\Models\CompanyMailConfig::where('company_id', company_id())->value('from_address') ?? 'Not Configured';
                    @endphp
                    <div class="mx-auto text-center d-none d-md-block">
                        <span class="badge badge-pill bg-secondary text-white fw-bold px-3 py-1" style="font-size:0.8rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <i class="fa-solid fa-paper-plane me-1"></i> Sender: {{ $senderEmail }}
                        </span>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            {{-- Tabulator Grid --}}
            <div class="card shadow-sm">
                <div id="mail-logs-table"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    const mailLogsDataUrl = "{{ route('mail-logs.data') }}";
</script>
<script src="{{ asset('assets/js/maillog.js') }}?v={{ filemtime(public_path('assets/js/maillog.js')) }}"></script>
@endsection
