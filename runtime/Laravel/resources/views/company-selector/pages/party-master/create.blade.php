@extends('company-selector.layout.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/select2.css') }}?v={{ hash_file('md5', public_path('css/select2.css')) }}">
<style>
    .et-wrapper {
        display: flex;
        gap: 0;
        align-items: stretch;
    }

    .et-main-card {
        border: 1px solid #e2e8f0;
        border-radius: 0 0 0 6px;
        border-right: none;
        background: #fff;
        padding: 1.25rem;
        flex: 1 1 0;
        min-width: 0;
    }

    .fields-row {
        background: #f8fafc;
        border: 1px solid #e9ecef;
        border-radius: 5px;
        padding: 0.9rem 1rem;
        margin-bottom: 1rem;
    }

    /* ── Company-Account Mapper ─────────────────────── */
    .mapper-wrap {
        display: flex;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
        min-height: 320px;
    }

    .mapper-company-list {
        width: 240px;
        flex-shrink: 0;
        background: #f8fafc;
        border-right: 1px solid #e2e8f0;
        overflow-y: auto;
    }

    .mapper-company-list .list-header {
        padding: 10px 14px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6b7280;
        border-bottom: 1px solid #e2e8f0;
        background: #fff;
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .company-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s;
        user-select: none;
    }

    .company-item:hover {
        background: #eef2ff;
    }

    .company-item.active {
        background: #1a56db;
        color: #fff;
    }

    .company-item.active .company-badge {
        background: rgba(255,255,255,0.25);
        color: #fff;
    }

    .company-item.active .mapped-indicator {
        background: rgba(255,255,255,0.3);
        color: #fff;
    }

    .company-badge {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        background: #e0e7ff;
        color: #3730a3;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .company-name-text {
        font-size: 0.82rem;
        font-weight: 600;
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .mapped-indicator {
        font-size: 0.65rem;
        padding: 1px 6px;
        border-radius: 10px;
        background: #dcfce7;
        color: #166534;
        font-weight: 600;
        white-space: nowrap;
    }

    .mapper-account-panel {
        flex: 1;
        padding: 20px 24px;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
    }

    .account-panel-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #9ca3af;
        gap: 10px;
        text-align: center;
    }

    .account-panel-placeholder i {
        font-size: 2.5rem;
        color: #d1d5db;
    }

    .account-panel-placeholder p {
        font-size: 0.85rem;
        margin: 0;
    }

    .select2-container--bootstrap-5.select2-container--focus .select2-selection,
    .select2-container--bootstrap-5.select2-container--open .select2-selection {
        box-shadow: none;
        border-color: #1a56db;
    }
    .select2-container--bootstrap-5 .select2-selection { min-height: 36px !important; }
    .select2-container--bootstrap-5 .select2-search__field:focus { box-shadow: none !important; }
    .select2-container--bootstrap-5 .select2-dropdown .select2-results__options .select2-results__option.select2-results__option--selected {
        background-color: #1a56db;
    }

    @media (max-width: 767px) {
        .et-wrapper { flex-direction: column; }
        .et-main-card { border-right: 1px solid #e2e8f0; border-radius: 0; }
        .mapper-wrap { flex-direction: column; }
        .mapper-company-list { width: 100%; border-right: none; border-bottom: 1px solid #e2e8f0; min-height: auto; }
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
                <span
                    class="d-inline-flex align-items-center justify-content-center rounded-3 border border-4 border-white shadow position-absolute text-white"
                    style="background:linear-gradient(135deg,#1a56db,#4dabf7);width:76px;height:76px;bottom:-38px;left:24px;">
                    <i class="fa-solid fa-envelope-open-text" style="font-size:26px;"></i>
                </span>
            </div>
            <div class="px-4 pb-3" style="padding-top:48px !important;">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h3 class="mb-0 fw-bold">Party Master</h3>
                        <div class="text-muted small mt-1">
                            Map accounts from different companies to a single party
                        </div>
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
                                <label class="form-label fw-semibold small mb-1">Name <spans
                                        class="text-danger">*</spans></label>
                                <input type="text" class="form-control form-control-sm" id="name" name="name"
                                    placeholder="Account Name" autocomplete="off">
                            </div>
                        </div>
                    </div>

                    {{-- Company-wise Account Mapper --}}
                    <div class="fields-row mt-3">
                        <h6 class="fw-bold mb-3">Map Company Accounts</h6>

                        <div class="mapper-wrap">

                            {{-- LEFT: Company List --}}
                            <div class="mapper-company-list">
                                <div class="list-header">Companies</div>
                                @foreach($companies as $company)
                                <div class="company-item"
                                    data-company-id="{{ $company->id }}"
                                    data-company-name="{{ $company->name }}"
                                    onclick="selectCompany(this)">
                                    <div class="company-badge">{{ strtoupper(substr($company->name, 0, 2)) }}</div>
                                    <span class="company-name-text">{{ $company->name }}</span>
                                    <span class="mapped-indicator d-none" id="mapped_badge_{{ $company->id }}">✓</span>
                                </div>
                                @endforeach
                            </div>

                            {{-- RIGHT: Account Selector Panel --}}
                            <div class="mapper-account-panel" id="account_panel">
                                <div class="account-panel-placeholder" id="panel_placeholder">
                                    <i class="fa-solid fa-building"></i>
                                    <p>Select a company from the left to map its account</p>
                                </div>

                                <div id="panel_selector" class="d-none">
                                    <div class="mb-3">
                                        <span class="fw-bold" style="font-size:0.95rem;" id="panel_company_title"></span>
                                        <div class="text-muted small mt-1">Select the account to link with this company</div>
                                    </div>
                                    <select id="account_select" class="form-select form-select-sm" style="width:100%;" data-placeholder="— Select Account —">
                                        <option value=""></option>
                                    </select>
                                </div>
                            </div>

                        </div>{{-- /.mapper-wrap --}}

                        {{-- Hidden inputs to store selections (submitted with form) --}}
                        <div id="hidden_inputs"></div>
                    </div>

                    {{-- Embed account data as JSON for JS --}}
                    @php
                        $accountsJson = [];
                        foreach($companies as $company) {
                            $accountsJson[$company->id] = isset($accounts[$company->id])
                                ? $accounts[$company->id]->map(fn($a) => ['id' => $a->id, 'name' => $a->name])->values()->toArray()
                                : [];
                        }
                    @endphp
                    <script id="accounts_data" type="application/json">{!! json_encode($accountsJson) !!}</script>

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



        </div>{{-- /.et-wrapper --}}

    </div>
</div>
{{-- @include('company.partials._shortcuts-bar') --}}
@endsection

@section('scripts')
{{-- <script src="{{ asset('js/libs/hugerte/hugerte.min.js') }}"></script>
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
</script> --}}
<script>
(function() {
    const accountsData = JSON.parse(document.getElementById('accounts_data').textContent);
    // Track selections: companyId -> {accountId, accountName}
    const selections = {};

    let activeCompanyId = null;
    let $accountSelect = null;

    $(document).ready(function() {
        $accountSelect = $('#account_select').select2({
            theme: 'bootstrap-5',
            allowClear: true,
            placeholder: '— Select Account —',
            width: '100%'
        });

        // When account is selected/cleared, store the value
        $accountSelect.on('change', function() {
            if (!activeCompanyId) return;
            const val = $(this).val();
            const text = $(this).find('option:selected').text().trim();

            if (val) {
                selections[activeCompanyId] = { accountId: val, accountName: text };
                // Show mapped badge
                $('#mapped_badge_' + activeCompanyId).removeClass('d-none');
            } else {
                delete selections[activeCompanyId];
                $('#mapped_badge_' + activeCompanyId).addClass('d-none');
            }
            rebuildHiddenInputs();
        });
    });

    window.selectCompany = function(el) {
        // Highlight active
        document.querySelectorAll('.company-item').forEach(i => i.classList.remove('active'));
        el.classList.add('active');

        activeCompanyId = el.dataset.companyId;
        const companyName = el.dataset.companyName;
        const accounts = accountsData[activeCompanyId] || [];

        // Show panel
        document.getElementById('panel_placeholder').classList.add('d-none');
        document.getElementById('panel_selector').classList.remove('d-none');
        document.getElementById('panel_company_title').textContent = companyName;

        // Rebuild options
        $accountSelect.empty().append('<option value=""></option>');
        accounts.forEach(function(acc) {
            $accountSelect.append(new Option(acc.name, acc.id, false, false));
        });
        $accountSelect.trigger('change.select2'); // Refresh select2 display

        // Restore previously selected value if any
        if (selections[activeCompanyId]) {
            $accountSelect.val(selections[activeCompanyId].accountId).trigger('change.select2');
        } else {
            $accountSelect.val(null).trigger('change.select2');
        }
    };

    function rebuildHiddenInputs() {
        const container = document.getElementById('hidden_inputs');
        container.innerHTML = '';
        Object.entries(selections).forEach(function([companyId, sel]) {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'company_accounts[' + companyId + ']';
            inp.value = sel.accountId;
            container.appendChild(inp);
        });
    }

    // ── Save button ──────────────────────────────────────────────
    document.getElementById('btn_save').addEventListener('click', function () {
        const btn = this;
        const data = new FormData(document.getElementById('form_create_template'));

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

        fetch("{{ route('party-masters.store') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: data,
        })
        .then(async r => {
            const json = await r.json();
            if (r.ok && json.status) {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: json.message,
                    timer: 1800,
                    showConfirmButton: false
                }).then(() => window.location.href = "{{ route('party-masters.index') }}");
            } else if (r.status === 422) {
                // Laravel validation errors (field-level)
                if (json.errors) {
                    const msgs = Object.values(json.errors).flat().join('<br>');
                    Swal.fire({ icon: 'warning', title: 'Validation Error', html: msgs, confirmButtonText: 'OK' });
                } else {
                    Swal.fire({ icon: 'warning', title: 'Validation Error', text: json.message, confirmButtonText: 'OK' });
                }
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: json.message ?? 'Something went wrong.' });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not reach server.' }))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Save';
        });
    });
})();
</script>
@endsection