@extends('company.layout.app')

@section('title', 'GST Credentials')

@section('css')
<style>
    .credential-card .card-header {
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .credential-table th {
        background: #f1f3f5;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #495057;
        vertical-align: middle;
        white-space: nowrap;
    }

    .credential-table td:first-child {
        font-weight: 600;
        font-size: 0.85rem;
        color: #343a40;
        white-space: nowrap;
        width: 160px;
    }

    .credential-table td {
        vertical-align: middle;
        padding: 0.6rem 0.75rem;
    }

    .credential-table input.form-control {
        font-size: 0.85rem;
        background: #fff;
    }

    .section-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 4px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .badge-sandbox {
        background: #e3f2fd;
        color: #1565c0;
    }

    .badge-production {
        background: #e8f5e9;
        color: #2e7d32;
    }
</style>
@endsection

@section('content')
<div class="page-wrapper">
    <!-- Page Header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-key"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                GST Credentials
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-danger-lt text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-gear me-1"></i> Credential
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
 {{--    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center justify-content-between">
                <div class="col-12 col-sm-6 col-md-4 col-lg-4">
                    <div class="card border-0 border-start border-4 border-primary">
                        <div class="card-body shadow p-2">
                            <h3 class="page-title font-monospace">
                                <i class="fa-solid fa-key text-primary me-2"></i>
                                GST Credentials
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
            <div class="row g-4">

                {{-- ==================== EWAY BILL ==================== --}}
                <div class="col-12">
                    <div class="card credential-card shadow-sm">
                        <div class="card-header d-flex align-items-center gap-2 py-3">
                            <i class="fa-solid fa-road text-primary"></i>
                            <span>Eway Bill Information</span>
                        </div>
                        <div class="card-body p-0">
                            <form id="form_eway_bill">
                                @csrf
                                <input type="hidden" name="type" value="eway_bill">
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0 credential-table">
                                        <thead>
                                            <tr>
                                                <th style="width:160px"></th>
                                                <th>
                                                    <span class="section-badge badge-sandbox">Sandbox</span>
                                                </th>
                                                <th>
                                                    <span class="section-badge badge-production">Production</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Client ID</td>
                                                <td>
                                                    <input type="text" class="form-control" name="sandbox_client_id"
                                                        value="{{ $ewayBill?->sandbox_client_id }}"
                                                        placeholder="Sandbox Client ID">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="production_client_id"
                                                        value="{{ $ewayBill?->production_client_id }}"
                                                        placeholder="Production Client ID">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Base URL</td>
                                                <td>
                                                    <input type="text" class="form-control" name="sandbox_base_url"
                                                        value="{{ $ewayBill?->sandbox_base_url }}"
                                                        placeholder="https://sandbox.example.com/api">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="production_base_url"
                                                        value="{{ $ewayBill?->production_base_url }}"
                                                        placeholder="https://api.example.com/api">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Secret ID</td>
                                                <td>
                                                    <input type="text" class="form-control" name="sandbox_secret_id"
                                                        value="{{ $ewayBill?->sandbox_secret_id }}"
                                                        placeholder="Sandbox Secret ID">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="production_secret_id"
                                                        value="{{ $ewayBill?->production_secret_id }}"
                                                        placeholder="Production Secret ID">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>GSTIN</td>
                                                <td>
                                                    <input type="text" class="form-control" name="sandbox_gstin"
                                                        value="{{ $ewayBill?->sandbox_gstin }}"
                                                        placeholder="Sandbox GSTIN" maxlength="20">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="production_gstin"
                                                        value="{{ $ewayBill?->production_gstin }}"
                                                        placeholder="Production GSTIN" maxlength="20">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Email</td>
                                                <td>
                                                    <input type="email" class="form-control" name="sandbox_email"
                                                        value="{{ $ewayBill?->sandbox_email }}"
                                                        placeholder="Sandbox Email">
                                                </td>
                                                <td>
                                                    <input type="email" class="form-control" name="production_email"
                                                        value="{{ $ewayBill?->production_email }}"
                                                        placeholder="Production Email">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Username</td>
                                                <td>
                                                    <input type="text" class="form-control" name="sandbox_username"
                                                        value="{{ $ewayBill?->sandbox_username }}"
                                                        placeholder="Sandbox Username">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="production_username"
                                                        value="{{ $ewayBill?->production_username }}"
                                                        placeholder="Production Username">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Password</td>
                                                <td>
                                                    <input type="password" class="form-control" name="sandbox_password"
                                                        value="{{ $ewayBill?->sandbox_password }}"
                                                        placeholder="Sandbox Password">
                                                </td>
                                                <td>
                                                    <input type="password" class="form-control" name="production_password"
                                                        value="{{ $ewayBill?->production_password }}"
                                                        placeholder="Production Password">
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="card-footer d-flex justify-content-end py-3">
                                    <button type="button" class="btn btn-primary btn-save waves-effect"
                                        data-form="form_eway_bill">
                                        <i class="fa-solid fa-floppy-disk me-1"></i> Update
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- ==================== E-INVOICE ==================== --}}
                <div class="col-12">
                    <div class="card credential-card shadow-sm">
                        <div class="card-header d-flex align-items-center gap-2 py-3">
                            <i class="fa-solid fa-file-invoice text-success"></i>
                            <span>E-Invoice Information</span>
                        </div>
                        <div class="card-body p-0">
                            <form id="form_e_invoice">
                                @csrf
                                <input type="hidden" name="type" value="e_invoice">
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0 credential-table">
                                        <thead>
                                            <tr>
                                                <th style="width:160px"></th>
                                                <th>
                                                    <span class="section-badge badge-sandbox">Sandbox</span>
                                                </th>
                                                <th>
                                                    <span class="section-badge badge-production">Production</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Client ID</td>
                                                <td>
                                                    <input type="text" class="form-control" name="sandbox_client_id"
                                                        value="{{ $eInvoice?->sandbox_client_id }}"
                                                        placeholder="Sandbox Client ID">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="production_client_id"
                                                        value="{{ $eInvoice?->production_client_id }}"
                                                        placeholder="Production Client ID">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Base URL</td>
                                                <td>
                                                    <input type="url" class="form-control" name="sandbox_base_url"
                                                        value="{{ $eInvoice?->sandbox_base_url }}"
                                                        placeholder="https://sandbox.example.com/api">
                                                </td>
                                                <td>
                                                    <input type="url" class="form-control" name="production_base_url"
                                                        value="{{ $eInvoice?->production_base_url }}"
                                                        placeholder="https://api.example.com/api">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Secret ID</td>
                                                <td>
                                                    <input type="text" class="form-control" name="sandbox_secret_id"
                                                        value="{{ $eInvoice?->sandbox_secret_id }}"
                                                        placeholder="Sandbox Secret ID">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="production_secret_id"
                                                        value="{{ $eInvoice?->production_secret_id }}"
                                                        placeholder="Production Secret ID">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>GSTIN</td>
                                                <td>
                                                    <input type="text" class="form-control" name="sandbox_gstin"
                                                        value="{{ $eInvoice?->sandbox_gstin }}"
                                                        placeholder="Sandbox GSTIN" maxlength="20">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="production_gstin"
                                                        value="{{ $eInvoice?->production_gstin }}"
                                                        placeholder="Production GSTIN" maxlength="20">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Email</td>
                                                <td>
                                                    <input type="email" class="form-control" name="sandbox_email"
                                                        value="{{ $eInvoice?->sandbox_email }}"
                                                        placeholder="Sandbox Email">
                                                </td>
                                                <td>
                                                    <input type="email" class="form-control" name="production_email"
                                                        value="{{ $eInvoice?->production_email }}"
                                                        placeholder="Production Email">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Username</td>
                                                <td>
                                                    <input type="text" class="form-control" name="sandbox_username"
                                                        value="{{ $eInvoice?->sandbox_username }}"
                                                        placeholder="Sandbox Username">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="production_username"
                                                        value="{{ $eInvoice?->production_username }}"
                                                        placeholder="Production Username">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Password</td>
                                                <td>
                                                    <input type="password" class="form-control" name="sandbox_password"
                                                        value="{{ $eInvoice?->sandbox_password }}"
                                                        placeholder="Sandbox Password">
                                                </td>
                                                <td>
                                                    <input type="password" class="form-control" name="production_password"
                                                        value="{{ $eInvoice?->production_password }}"
                                                        placeholder="Production Password">
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="card-footer d-flex justify-content-end py-3">
                                    <button type="button" class="btn btn-primary btn-save waves-effect"
                                        data-form="form_e_invoice">
                                        <i class="fa-solid fa-floppy-disk me-1"></i> Update
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    const updateUrl = "{{ route('gst-credentials.update') }}";

    document.querySelectorAll('.btn-save').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const formId = this.getAttribute('data-form');
            const form   = document.getElementById(formId);

            Swal.fire({
                title: 'Update Credentials?',
                text: 'Are you sure you want to update these GST credentials?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Update',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#0054a6',
            }).then(function (result) {
                if (!result.isConfirmed) return;

                const data = new FormData(form);

                // Laravel method spoofing — POST with _method=PUT in body
                data.append('_method', 'PUT');

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
                    btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Update';
                });
            });
        });
    });
</script>
@endsection
