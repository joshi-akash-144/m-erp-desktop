@extends('company.layout.app')

@section('title', 'Mail Configuration')

@section('css')
<style>
    .config-card .card-header {
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .config-label {
        font-weight: 600;
        font-size: 0.85rem;
        color: #343a40;
    }

    .form-control, .form-select {
        font-size: 0.875rem;
    }

    .password-wrapper {
        position: relative;
    }

    .password-wrapper .toggle-password {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: #6c757d;
        padding: 0;
        line-height: 1;
    }

    .password-wrapper .toggle-password:hover {
        color: #343a40;
    }
</style>
@endsection

@section('content')
<div class="page-wrapper">
    <!-- Page Header -->
 <div class="page-header d-print-none">
        <div class="container-xl" style="width: 1600px;">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-envelope"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Mail Configuration
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-danger-lt text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-envelope"></i> Mail
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn waves-effect rounded-0 fw-medium shadow-sm">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
  {{--   <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center justify-content-between">
                <div class="col-12 col-sm-6 col-md-5 col-lg-4">
                    <div class="card border-0 border-start border-4 border-primary">
                        <div class="card-body shadow p-2">
                            <h3 class="page-title font-monospace">
                                <i class="fa-solid fa-envelope text-primary me-2"></i>
                                Mail Configuration
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>  --}}

    <!-- Page Body -->
    <div class="page-body">
        <div class="container-xl">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10 col-xl-10">
                    <div class="card config-card shadow-sm">
                        <div class="card-header d-flex align-items-center gap-2 py-3">
                            <i class="fa-solid fa-server text-primary"></i>
                            <span>SMTP Mail Settings</span>
                        </div>
                        <div class="card-body">
                            <form id="form_mail_config">
                                @csrf

                                <div class="row g-3">

                                    {{-- Host --}}
                                    <div class="col-12 col-md-6">
                                        <label class="form-label config-label">
                                            Host <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" name="host"
                                            value="{{ $config?->host }}"
                                            placeholder="e.g. mail.example.com">
                                    </div>

                                    {{-- Port --}}
                                    <div class="col-6 col-md-3">
                                        <label class="form-label config-label">
                                            Port <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control" name="port"
                                            value="{{ $config?->port ?? 587 }}"
                                            placeholder="587" min="1" max="65535">
                                    </div>

                                    {{-- Encryption --}}
                                    <div class="col-6 col-md-3">
                                        <label class="form-label config-label">
                                            Encryption <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" name="encryption">
                                            <option value="tls"  {{ ($config?->encryption ?? 'tls') === 'tls'  ? 'selected' : '' }}>TLS</option>
                                            <option value="ssl"  {{ ($config?->encryption) === 'ssl'           ? 'selected' : '' }}>SSL</option>
                                            <option value="none" {{ ($config?->encryption) === 'none'          ? 'selected' : '' }}>None</option>
                                        </select>
                                    </div>

                                    {{-- Username --}}
                                    <div class="col-12 col-md-6">
                                        <label class="form-label config-label">
                                            Username <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" name="username"
                                            value="{{ $config?->username }}"
                                            placeholder="e.g. noreply@example.com">
                                    </div>

                                    {{-- Password --}}
                                    <div class="col-12 col-md-6">
                                        <label class="form-label config-label">
                                            Password
                                            @if($config?->password)
                                                <small class="text-muted fw-normal">(leave blank to keep current)</small>
                                            @else
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>
                                        <div class="password-wrapper">
                                            <input type="password" class="form-control pe-5" id="mail_password" name="password"
                                                placeholder="{{ $config?->password ? '••••••••••••' : 'Enter SMTP password' }}">
                                            <button type="button" class="toggle-password" data-target="mail_password">
                                                <i class="fa-regular fa-eye" id="eye_mail_password"></i>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- From Address --}}
                                    <div class="col-12 col-md-6">
                                        <label class="form-label config-label">
                                            From Address <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" class="form-control" name="from_address"
                                            value="{{ $config?->from_address }}"
                                            placeholder="e.g. noreply@example.com">
                                    </div>

                                    {{-- From Name --}}
                                    <div class="col-12 col-md-6">
                                        <label class="form-label config-label">
                                            From Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" name="from_name"
                                            value="{{ $config?->from_name ?? 'ERP-SYSTEM' }}"
                                            placeholder="e.g. ERP-SYSTEM">
                                    </div>

                                    {{-- Active Toggle --}}
                                    <div class="col-12">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                                value="1" {{ ($config === null || $config->is_active) ? 'checked' : '' }}>
                                            <label class="form-check-label config-label" for="is_active">
                                                Active
                                            </label>
                                        </div>
                                    </div>

                                </div>
                            </form>
                        </div>
                        <div class="card-footer d-flex justify-content-end py-3">
                            <button type="button" class="btn btn-primary form-save-btn waves-effect" id="btn_save_mail_config">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Save Mail Config
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@include('company.partials._shortcuts-bar')
@endsection

@section('script')
<script>
    const updateUrl = "{{ route('mail-config.update') }}";

    // Password visibility toggle
    document.querySelectorAll('.toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.getElementById(this.dataset.target);
            const icon  = document.getElementById('eye_' + this.dataset.target);

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });

    // Save
    document.getElementById('btn_save_mail_config').addEventListener('click', function () {
        const btn  = this;
        const form = document.getElementById('form_mail_config');

        Swal.fire({
            title: 'Save Mail Configuration?',
            text: 'Are you sure you want to update the mail settings?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Save',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#0054a6',
        }).then(function (result) {
            if (!result.isConfirmed) return;

            const data = new FormData(form);
            data.append('_method', 'PUT');

            // Send is_active = 0 when unchecked (checkbox not included in FormData when unchecked)
            if (!document.getElementById('is_active').checked) {
                data.set('is_active', '0');
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

            fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: data,
            })
            .then(async function (res) {
                const json = await res.json();

                if (res.ok && json.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: json.message,
                        timer: 2000,
                        showConfirmButton: false,
                    });
                } else if (res.status === 422 && json.errors) {
                    const messages = Object.values(json.errors).flat().join('<br>');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation Error',
                        html: messages,
                        confirmButtonText: 'OK',
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: json.message ?? 'Something went wrong.',
                        confirmButtonText: 'OK',
                    });
                }
            })
            .catch(function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Could not reach the server. Please check your connection.',
                    confirmButtonText: 'OK',
                });
            })
            .finally(function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Save Mail Config';
            });
        });
    });
</script>
@endsection
