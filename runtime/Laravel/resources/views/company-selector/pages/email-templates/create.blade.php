@extends('company-selector.layout.app')

@section('css')
<style>
    .et-wrapper { display: flex; gap: 0; align-items: stretch; }

    .et-banner {
        background: linear-gradient(90deg, #b7791f 0%, #d4a23a 100%);
        color: #fff;
        padding: 0.7rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.95rem;
        font-weight: 600;
        border-radius: 6px 6px 0 0;
    }
    .et-banner .back-btn {
        font-size: 0.8rem;
        font-weight: 600;
        color: #fff;
        text-decoration: none;
        border: 1.5px solid rgba(255,255,255,0.7);
        border-radius: 4px;
        padding: 2px 12px;
        transition: background 0.15s;
    }
    .et-banner .back-btn:hover { background: rgba(255,255,255,0.18); }

    .et-main-card {
        border: 1px solid #e2e8f0;
        border-radius: 0 0 0 6px;
        border-right: none;
        background: #fff;
        padding: 1.25rem;
        flex: 1 1 0;
        min-width: 0;
    }

    .et-prefix-card {
        border: 1px solid #e2e8f0;
        border-radius: 0 0 6px 0;
        background: #fff;
        width: 220px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
    }
    .et-prefix-card .ph {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        font-weight: 700;
        font-size: 0.82rem;
        padding: 0.55rem 1rem;
    }
    .et-prefix-card .pb { padding: 0.6rem 1rem; overflow-y: auto; flex: 1; }
    .prefix-list { list-style: none; padding: 0; margin: 0; }
    .prefix-list li {
        font-size: 0.8rem;
        color: #374151;
        cursor: pointer;
        padding: 2px 0;
        user-select: none;
    }
    .prefix-list li:hover { color: #1a56db; }

    .fields-row { background: #f8fafc; border: 1px solid #e9ecef; border-radius: 5px; padding: 0.9rem 1rem; margin-bottom: 1rem; }

    @media (max-width: 767px) {
        .et-wrapper { flex-direction: column; }
        .et-main-card { border-right: 1px solid #e2e8f0; border-radius: 0; }
        .et-prefix-card { width: 100%; border-radius: 0 0 6px 6px; border-top: none; }
    }
</style>
@endsection

@section('content')
<div class="page-body pt-4">
    <div class="container-xl">
        {{-- Hero Banner --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="position-relative"
                style="height:60px;background:linear-gradient(120deg,#1a56db 0%,#4dabf7 60%,#74c0fc 100%);border-radius:12px 12px 0 0;">
                <span class="d-inline-flex align-items-center justify-content-center rounded-3 border border-4 border-white shadow position-absolute text-white"
                    style="background:linear-gradient(135deg,#1a56db,#4dabf7);width:76px;height:76px;bottom:-38px;left:24px;">
                    <i class="fa-solid fa-envelope-open-text" style="font-size:26px;"></i>
                </span>
            </div>
            <div class="px-4 pb-3" style="padding-top:48px !important;">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h3 class="mb-0 fw-bold">Email Templates</h3>
                        <div class="text-muted small mt-1">Create new email templates used across the system.</div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('back.to.previous') }}"
                            class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                            @include('icons.prev', ['size' => 15])
                            Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Body: Form + Prefix side by side --}}
        <div class="et-wrapper">

            {{-- Main Form --}}
            <div class="et-main-card">
                <form id="form_create_template">
                    @csrf

                    {{-- Top fields --}}
                    <div class="fields-row">
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold small mb-1">Template Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="name" name="name"
                                    placeholder="Template Name" autocomplete="off">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold small mb-1">Slug <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm bg-light" id="slug" name="slug"
                                    placeholder="template-slug" autocomplete="off">
                                <div class="form-text" style="font-size:0.72rem;">Auto-generated from name</div>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold small mb-1">Subject <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="subject" name="subject"
                                    placeholder="Email Subject">
                            </div>
                        </div>
                    </div>

                    {{-- Message --}}
                    <div class="mb-1">
                        <label class="form-label fw-semibold small mb-1">Message <span class="text-danger">*</span></label>
                        <textarea id="message_editor" name="message"></textarea>
                    </div>

                    {{-- Buttons --}}
                    <div class="d-flex justify-content-end gap-3 mt-4">
                        <a href="{{ route('back.to.previous') }}" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-xmark me-1"></i> Cancel
                        </a>
                        <button type="button" class="btn btn-primary form-save-btn waves-effect" id="btn_save">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save
                        </button>
                    </div>

                </form>
            </div>

            {{-- Prefix Panel --}}
            <div class="et-prefix-card">
                <div class="ph">Available Prefixes:</div>
                <div class="pb">
                    <ul class="prefix-list">
                        @foreach([
                            '{NAME}', '{RESET_LINK}', '{USERNAME}', '{USER_EMAIL}',
                            '{ORDER-ID}', '{ORDER-DATE}', '{CUSTOMER-NAME}', '{CUSTOMER-EMAIL}',
                            '{CUSTOMER-PHONE}', '{CUSTOMER-ADDRESS}', '{SUPPLIER-NAME}', '{SUPPLIER-EMAIL}',
                            '{SUPPLIER-PHONE}', '{SUPPLIER-ADDRESS}', '{BROKER-NAME}', '{BROKER-EMAIL}',
                            '{BROKER-PHONE}', '{BROKER-ADDRESS}', '{ORDER-SHIP-ADDRESS}', '{BILLING-ADDRESS}',
                            '{ORDER-SHIP-CITY}', '{ORDER-SHIP-POSTAL-CODE}', '{COMPANY-NAME}', '{PRODUCT-NAME}',
                            '{PRODUCT-PRICE}', '{PRODUCT-QTY}', '{SUBTOTAL}', '{TOTAL-AMOUNT}',
                            '{PAYMENT-METHOD}', '{CUSTOMER-SUPPORT-EMAIL}', '{CUSTOMER-SUPPORT-PHONE-NUMBER}',
                            '{COMPANY-ADDRESS}', '{COMPANY-CONTACT-NUMBER}', '{DELIVERY-DAYS}',
                            '{VERIFICATION_LINK}', '{CHEQUE-NO}', '{DATE}', '{DESTINATION-NAME}',
                            '{QR-CODE-FOR-LOCATION}', '{VEHICLE-NUMBER}', '{PRODUCT-TABLE}', '{CLOSE-YEAR-OTP}',
                        ] as $prefix)
                            <li onclick="insertPrefix('{{ $prefix }}')">{{ $prefix }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>

        </div>{{-- /.et-wrapper --}}

    </div>
</div>
@include('company.partials._shortcuts-bar')
@endsection

@section('scripts')
<script src="{{ asset('js/libs/hugerte/hugerte.min.js') }}"></script>
<script>
    hugerte.init({
        selector: '#message_editor',
        height: 340,
        menubar: false,
        base_url: '{{ asset('js/libs/hugerte') }}',
        suffix: '.min',
        plugins: 'lists link image table code',
        toolbar: 'bold italic underline strikethrough removeformat | bullist numlist outdent indent | alignleft aligncenter alignright alignjustify | link image table hr | code',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; font-size: 14px; }',
    });

    document.getElementById('name').addEventListener('input', function () {
        document.getElementById('slug').value = this.value
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    });

    function insertPrefix(prefix) {
        hugerte.activeEditor?.insertContent(prefix);
        hugerte.activeEditor?.focus();
    }

    document.getElementById('btn_save').addEventListener('click', function () {
        const btn = this;
        hugerte.triggerSave();

        const data = new FormData(document.getElementById('form_create_template'));
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

        fetch("{{ route('email-templates.store') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            body: data,
        })
        .then(async r => {
            const json = await r.json();
            if (r.ok && json.status) {
                Swal.fire({ icon: 'success', title: 'Created!', text: json.message, timer: 1800, showConfirmButton: false })
                    .then(() => window.location.href = "{{ route('email-templates.index') }}");
            } else if (r.status === 422 && json.errors) {
                Swal.fire({ icon: 'warning', title: 'Validation Error', html: Object.values(json.errors).flat().join('<br>'), confirmButtonText: 'OK' });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: json.message ?? 'Something went wrong.' });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not reach server.' }))
        .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Save'; });
    });
</script>
@endsection
