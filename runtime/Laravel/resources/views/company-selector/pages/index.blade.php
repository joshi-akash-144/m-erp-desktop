@extends('company-selector.layout.app')

@section('content')
<div class="page-body py-4">
    <div class="container-xl">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-xl-8">

                {{-- Card --}}
                <div class="card shadow-sm border-0 d-flex flex-column">

                    {{-- Card Header --}}
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="page-title mb-0 d-flex align-items-center gap-2">
                            <i class="fas fa-building"></i>
                            Available ERP Companies
                        </h2>

                        <a href="{{ route('companies.create') }}"
                            class="btn btn-animate-icon btn-animate-icon-rotate d-none d-sm-flex align-items-center gap-2 border border-primary waves-effect btn-sm">
                            <i class="fas fa-plus"></i>
                            Create Company
                        </a>
                    </div>

                    {{-- Search --}}
                    <div class="card-body border-bottom">
                        <div class="input-icon mb-3">
                            <input type="text" class="form-control text-uppercase fw-semibold" id="searchInput"
                                placeholder="Search company by Name or Code (e.g., COMP0101, 101)" autofocus
                                autocomplete="off">
                            <span class="input-icon-addon">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>

                        {{-- Company List --}}
                        <div class="list-group list-group-flush overflow-auto bg-white" style="max-height: 400px;">
                            @if ($companies->isEmpty())
                            <div
                                class="list-group-item text-center text-muted py-4 border border-dashed rounded-4 bg-primary-lt">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p class="mb-0 fw-bold">No companies available or no access rights.</p>
                                <small class="text-muted fw-bold">Try refining your search terms.</small>
                            </div>
                            @else
                            @foreach ($companies as $company)
                            <a href="#"
                                class="waves-effect list-group-item list-group-item-action d-flex flex-wrap align-items-center gap-2 company-item"
                                data-company-id="{{ $company->uuid }}" data-company-name="{{ $company->name }}"
                                data-company-code="{{ $company->code }}">
                                <div class="flex-grow-1 d-flex flex-wrap w-100">
                                    <div class="col-12 col-md-8 fw-semibold text-truncate" title="{{ $company->name }}">
                                        <i class="fas fa-briefcase me-2 text-muted"></i>
                                        {{ Str::limit($company->name, 55) }}
                                    </div>
                                    <div class="col-6 col-md-2 text-muted">
                                        <i class="fas fa-code me-1"></i>
                                        {{ $company->code }}
                                    </div>
                                    <div class="col-6 col-md-2 text-end">
                                        <span class="badge bg-cyan text-cyan-fg fs-5">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            {{ $company->financialYears->count() }}
                                            {{ Str::plural('Year', $company->financialYears->count()) }}
                                        </span>
                                    </div>
                                </div>
                            </a>
                            @endforeach
                            @endif
                        </div>
                    </div>

                    {{-- Card Footer --}}
                    <div
                        class="card-footer text-muted small d-flex justify-content-between align-items-center bg-light">
                        <!-- Desktop & tablet: single line with arrows -->
                        <div class="d-none d-sm-block mb-1">
                            <i class="fas fa-keyboard me-1"></i>
                            Use <strong>↑↓</strong> arrows to navigate • <strong>Enter</strong> to select
                        </div>

                        <!-- Mobile: stack lines -->
                        <div class="d-block d-sm-none mb-1">
                            <div>
                                <i class="fas fa-hand-pointer me-1"></i>
                                <strong>Tap</strong> to select Company
                            </div>
                        </div>

                        <div class="text-end">
                            <strong>
                                <span class="badge bg-primary text-primary-fg fs-5">
                                    <i class="fas fa-building me-1"></i>
                                    {{ $companies->count() }}
                                    {{ Str::plural('company', $companies->count()) }}
                                </span>
                            </strong>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
    const selectCompany = "{{ route('company-selection.store') }}";
</script>

<script src="{{ asset('js/company-selection.js') }}?v={{ hash_file('md5', public_path('js/company-selection.js')) }}"></script>
@endsection