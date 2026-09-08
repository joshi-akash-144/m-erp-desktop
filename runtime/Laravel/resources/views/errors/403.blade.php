@extends('errors.layout')

@section('title', 'Forbidden')

@section('content')
<div class="page page-center">
    <div class="container-tight py-5 text-center">
        
        <!-- Illustration -->
        <div class="mb-4">
            <img src="{{ asset('img/illustrations/light/403.png') }}" alt="Forbidden" class="img-fluid" style="max-width: 400px;">
        </div>

        <!-- Title -->
        <h1 class="display-6 mb-2">Access Denied</h1>

        <!-- Subtitle -->
        <p class="text-muted mb-4">
            You don’t have permission to view this page. Please contact your administrator if you believe this is a mistake.
        </p>

        <!-- Action Button -->
        <a href="{{ url()->previous() }}" class="btn btn-primary">
            <!-- Left arrow icon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12l14 0" />
                <path d="M5 12l6 6" />
                <path d="M5 12l6 -6" />
            </svg>
            Go Back
        </a>

    </div>
</div>
@endsection
