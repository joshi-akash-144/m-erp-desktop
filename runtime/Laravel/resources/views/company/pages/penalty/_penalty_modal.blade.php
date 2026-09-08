@php
$formMode = $modalData['form_mode'] ?? 'create';
$penalty = $modalData['data'] ?? null;
$items = $modalData['items'] ?? collect();
$title = $modalData['title'] ?? 'Penalty Details';

$actionUrl = ($formMode === 'create') ? route('penalty.store') : route('penalty.update', $penalty->id ?? 0);
$method = ($formMode === 'create') ? 'POST' : 'PUT';
@endphp

<div class="modal fade master-modal" id="penalty_modal" tabindex="-1"
    data-bs-backdrop="static" data-bs-keyboard="false"
    aria-labelledby="penalty_modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header master-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="master-badge bg-primary-lt rounded fs-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-receipt"
                            width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2m4 -14h6m-6 4h6m-2 4h2" />
                        </svg>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0 font-monospace" id="penalty_modalLabel">{{ $title }}</h5>
                        <small class="badge bg-teal text-teal-fg">Master Data Management</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="addPenaltyForm" action="{{ $actionUrl }}" method="POST" data-method="{{ $method }}">
                    @csrf
                    <input type="hidden" id="form_mode" value="{{ $formMode }}">

                    <div class="row g-2">
                        <div class="col-md-12">
                            <div class="master-form-section">
                                <div class="master-section-title">
                                    <i class="fa-solid fa-circle-exclamation me-2 text-primary"></i>Penalty Details
                                </div>
                                <div class="row g-2">

                                    {{-- Item --}}
                                    <div class="col-12">
                                        <label for="penalty_item_id" class="form-label required">
                                            <i class="fa-solid fa-box text-secondary"></i> Item
                                        </label>
                                        <select id="penalty_item_id" name="item_id"
                                            class="form-select select2" style="width: 100%;"
                                            {{ $formMode === 'view' ? 'disabled' : 'required' }}>
                                            <option value="">Select Item</option>
                                            @foreach ($items as $item)
                                            <option value="{{ $item->id }}"
                                                {{ isset($penalty) && $penalty->item_id == $item->id ? 'selected' : '' }}>
                                                {{ $item->name }}{{ $item->unit ? ' [' . $item->unit->name . ']' : '' }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Amount --}}
                                    <div class="col-lg-6 col-sm-12">
                                        <label for="penalty_amount" class="form-label required">
                                            <i class="fa-solid fa-calculator text-secondary"></i> Amount
                                        </label>
                                        <input type="number" step="0.01" id="penalty_amount" name="amount"
                                            class="form-control" placeholder="Enter Amount"
                                            value="{{ $penalty->amount ?? '' }}"
                                            {{ $formMode === 'view' ? 'readonly' : 'required' }}>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-link non-selectable waves-effect"
                    data-bs-dismiss="modal">Close</button>
                @if ($formMode !== 'view')
                <button type="submit" form="addPenaltyForm"
                    class="btn btn-primary form-save-btn waves-effect" id="savePenaltyBtn">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="icon icon-tabler icons-tabler-outline icon-tabler-square-check"
                        width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                        <path d="M9 12l2 2l4 -4" />
                    </svg>
                    {{ $formMode === 'edit' ? 'Update Penalty' : 'Save Penalty' }}
                </button>
                @endif
            </div>

        </div>
    </div>
</div>

<script id="penalty-modal-script">
    (function() {
        let validator;

        $(document).ready(function() {
            const formEl = document.getElementById('addPenaltyForm');
            const formMode = document.getElementById('form_mode')?.value;

            // Prevent native form submission on Enter
            $(formEl).on('submit', function(e) {
                e.preventDefault();
            });

            // ── VALIDATION (skip in view mode) ─────────────────────────────
            if (formMode !== 'view') {
                validator = new JustValidate('#addPenaltyForm', {
                    validateBeforeSubmitting: true,
                    focusInvalidField: true,
                });

                validator
                    .addField('#penalty_item_id', [{
                        rule: 'required',
                        errorMessage: 'Item is a required field'
                    }, ])
                    .addField('#penalty_amount', [{
                            rule: 'required',
                            errorMessage: 'Amount is a required field'
                        },
                        {
                            rule: 'minNumber',
                            value: 0,
                            errorMessage: 'Amount cannot be negative'
                        },
                    ])
                    .onSuccess((event) => {
                        event.preventDefault();
                        submitFormAjax(formEl);
                    });
            }

            // ── ENTER KEY NAVIGATION ────────────────────────────────────────
            // Item (select2): on close → focus Amount
            $('#penalty_item_id').on('select2:close', function() {
                setTimeout(() => $('#penalty_amount').focus(), 50);
            });

            // Amount → Save button on Enter
            $('#penalty_amount').on('keydown', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#savePenaltyBtn').focus();
                }
            });

            // ── MODAL SHOWN: focus first field or lock view mode ───────────
            const modalEl = document.getElementById('penalty_modal');
            if (modalEl) {
                modalEl.addEventListener('shown.bs.modal', function() {
                    if (formMode === 'view') {
                        modalEl.classList.add('view-mode');
                        $(modalEl).find('input, select').prop('disabled', true);
                        $(modalEl).find('.select2').trigger('change.select2');
                    } else {
                        // Open select2 dropdown so user can type immediately
                        setTimeout(() => $('#penalty_item_id').select2('focus'), 100);
                    }
                }, {
                    once: true
                });
            }
        });

        // ── SUBMIT HANDLER ──────────────────────────────────────────────────
        let isSubmitting = false;

        function submitFormAjax(form) {
            if (isSubmitting) return;
            isSubmitting = true;

            const $form = $(form);
            const $btn = $('#penalty_modal .form-save-btn');

            const method = ($form.data('method') || $form.attr('method') || 'POST').toUpperCase();
            const url = $form.attr('action');
            const isEdit = method === 'PUT' || url.toLowerCase().includes('update');

            if ($btn.length) {
                $btn.prop('disabled', true).html(`
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                ${isEdit ? 'Updating' : 'Saving'} <span class="animated-dots"></span>
            `);
            }

            const formData = new FormData(form);
            if (isEdit) formData.append('_method', 'PUT');

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(data) {
                    if (data.success) {
                        showToast('success', data.message || (isEdit ? 'Penalty updated successfully!' : 'Penalty saved successfully!'));
                        if (typeof table !== 'undefined' && table) {
                            table.setData(penaltyListUrl);
                        }
                    } else {
                        showToast('error', data.message || 'Something went wrong.');
                    }
                },
                error: function(xhr) {
                    console.error(xhr);
                    showToast('error', xhr.responseJSON?.message || 'Server error. Please try again.');
                },
                complete: function() {
                    if ($btn.length) {
                        $btn.prop('disabled', false).html(`
                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="icon icon-tabler icons-tabler-outline icon-tabler-square-check"
                             width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                             stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                            <path d="M9 12l2 2l4 -4" />
                        </svg>
                        ${isEdit ? 'Update Penalty' : 'Save Penalty'}
                    `);
                    }
                    isSubmitting = false;
                    if (typeof hideLoader === 'function') hideLoader();
                    $('#penalty_modal').modal('hide');
                }
            });
        }

    })();
</script>