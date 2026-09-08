@extends('company-selector.layout.app')

@section('content')
<style>
    .input-group .select2-container {
        flex: 1 1 auto;
        width: 1% !important;
        border-radius: 0% !important;
    }

/* Ensure error label is on a new line inside input groups */
.input-group {
  flex-wrap: wrap !important;
}

.input-group > .just-validate-error-label {
  width: 100% !important;
  order: 99 !important;
}
</style>
<div class="page-wrapper">
    <div class="page-body pt-4">
        <div class="container-xl">

            {{-- ── HERO BANNER ── --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="position-relative"
                    style="height:60px;background:linear-gradient(120deg,#1a56db 0%,#4dabf7 60%,#74c0fc 100%);border-radius:12px 12px 0 0;">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 border border-4 border-white shadow position-absolute text-white"
                        style="background:linear-gradient(135deg,#1a56db,#4dabf7);width:76px;height:76px;bottom:-38px;left:24px;">
                        <i class="fa-solid fa-user" style="font-size:28px;"></i>
                    </span>
                </div>
                <div class="px-4 pb-3" style="padding-top:48px !important;">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <h3 class="mb-0 fw-bold">
                                    @if($mode === 'edit') Edit User
                                    @elseif($mode === 'view') View User
                                    @else New User
                                    @endif
                                </h3>
                                @if(in_array($mode, ['edit', 'view']) && isset($user))
                                    @if($user->status)
                                        <span class="badge bg-success-lt text-success rounded-pill px-2">Active</span>
                                    @else
                                        <span class="badge bg-danger-lt text-danger rounded-pill px-2">Inactive</span>
                                    @endif
                                @endif
                            </div>
                            <div class="text-muted small mt-1">
                                @if($mode === 'create')
                                    Fill in the details to create a new system user
                                @else
                                    {{ $user->username ?? '' }} &nbsp;·&nbsp; {{ $user->email ?? '' }}
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('users.index') }}"
                            class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                            @include('icons.prev', ['size' => 15])
                            Back to Users
                        </a>
                    </div>
                </div>
            </div>

            {{-- ── FORM CARD ── --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-bottom px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary"
                            style="width:36px;height:36px;flex-shrink:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2a5 5 0 1 1-5 5 5 5 0 0 1 5-5z"/>
                                <path d="M14 14a5 5 0 0 1 5 5v1a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-1a5 5 0 0 1 5-5h4z"/>
                            </svg>
                        </span>
                        <div>
                            <div class="fw-bold lh-1">User Information</div>
                            <div class="text-muted small">
                                @if($mode === 'edit') Update user account details
                                @elseif($mode === 'view') Viewing user account details
                                @else Enter details for the new user account
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <form id="userForm" data-mode="{{ $mode ?? 'create' }}">
                    @csrf

                    @if(isset($user))
                        <input type="hidden" name="uuid" id="uuid" value="{{ $user->uuid ?? (string) Str::uuid() }}">
                    @endif

                    <div class="card-body p-4">
                        <div class="row g-4">

                            {{-- Full Name --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                            fill="currentColor" class="text-muted">
                                            <path d="M12 2a5 5 0 1 1-5 5 5 5 0 0 1 5-5z"/>
                                            <path d="M14 14a5 5 0 0 1 5 5v1a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-1a5 5 0 0 1 5-5h4z"/>
                                        </svg>
                                    </span>
                                    <input type="text" name="name" id="name" class="form-control"
                                        value="{{ $user->name ?? '' }}"
                                        placeholder="Enter name"
                                        {{ $mode === 'view' ? 'readonly' : '' }}>
                                </div>
                            </div>

                            {{-- Username --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent text-muted fw-bold">@</span>
                                    <input type="text" name="username" id="username" class="form-control"
                                        value="{{ $user->username ?? '' }}"
                                        placeholder="Enter username"
                                        {{ ($mode === 'view' ) ? 'readonly' : '' }}>
                                </div>
                            </div>

                            {{-- Email --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Email Address
                                    @if($mode === 'create') <span class="text-danger">*</span> @endif
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="text-muted">
                                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                                            <polyline points="3 7 12 13 21 7"/>
                                        </svg>
                                    </span>
                                    <input type="email" name="email" class="form-control"
                                        value="{{ $user->email ?? '' }}"
                                        placeholder="Enter email address"
                                        {{ $mode === 'view' ? 'readonly' : '' }}>
                                </div>
                            </div>

                            {{-- Password (create only) --}}
                            @if($mode === 'create')
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="text-muted">
                                            <rect x="5" y="11" width="14" height="10" rx="2"/>
                                            <circle cx="12" cy="16" r="1"/>
                                            <path d="M8 11v-4a4 4 0 0 1 8 0v4"/>
                                        </svg>
                                    </span>
                                    <input type="password" name="password" class="form-control"
                                        placeholder="Enter password">
                                </div>
                            </div>
                            @endif

                        {{--Role --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent text-muted">
                                        @include('icons.user-cog', ['size' => 18])
                                    </span>
                                    <select name="role_id" id="role_id" class="form-select select2" {{ $mode === 'view' ? 'disabled' : '' }}>
                                        <option value="">Select Role</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}" {{ (isset($user) && $user->roles->first()?->id == $role->id) ? 'selected' : '' }}>{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Status (edit / view only) --}}
                            @if(in_array($mode, ['edit', 'view']))
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="w-100">
                                    <label class="form-label fw-semibold">Status</label>
                                    <label class="form-check form-switch form-switch-lg d-flex align-items-center gap-2 mb-0">
                                        <input type="hidden" name="status" value="0">
                                        <input class="form-check-input" type="checkbox" name="status" id="userStatus"
                                            value="1"
                                            {{ isset($user) && $user->status ? 'checked' : '' }}
                                            {{ $mode === 'view' ? 'disabled' : '' }}>
                                        <span id="statusLabel" class="form-check-label fw-semibold">
                                            {{ isset($user) && $user->status ? 'Active' : 'Inactive' }}
                                        </span>
                                    </label>
                                </div>
                            </div>
                            @endif

                        </div>
                    </div>

                    {{-- Footer buttons --}}
                    @if($mode !== 'view')
                    <div class="card-footer bg-transparent border-top px-4 py-3">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('users.index') }}"
                                class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M15 6l-6 6l6 6"/>
                                </svg>
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary form-save-btn d-inline-flex align-items-center gap-1" id="submitBtn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12l5 5l10-10"/>
                                </svg>
                                {{ $mode === 'edit' ? 'Update User' : 'Save User' }}
                            </button>
                        </div>
                    </div>
                    @endif

                </form>
            </div>

        </div>
    </div>
</div>
@include('company.partials._shortcuts-bar')
@endsection

@section('scripts')
<script>
    const createUserUrl = "{{ route('users.store') }}";
    const updateUserUrl = "{{ isset($user) ? route('users.update', $user->uuid) : '' }}";
    const usersIndexUrl = "{{ route('users.index') }}";

    document.addEventListener('input', function (e) {
        if (e.target.id === 'name') {
            var u = document.getElementById('username');
            if (u) u.value = e.target.value;
        }
    });

    setTimeout(function () {
        var n = document.getElementById('name');
        if (n) n.focus();
    }, 400);

    // Status label sync
    var statusSwitch = document.getElementById('userStatus');
    var statusLabel  = document.getElementById('statusLabel');
    if (statusSwitch && statusLabel) {
        function syncLabel() {
            statusLabel.textContent = statusSwitch.checked ? 'Active' : 'Inactive';
            statusLabel.style.color = statusSwitch.checked ? '#2fb344' : '#d63939';
        }
        syncLabel();
        statusSwitch.addEventListener('change', syncLabel);
    }
</script>
<script src="{{ asset('js/users/form.js') }}?v={{ hash_file('md5', public_path('js/users/form.js')) }}"></script>
@endsection
