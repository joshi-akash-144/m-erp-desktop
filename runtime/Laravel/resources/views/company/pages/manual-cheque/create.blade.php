@extends('company.layout.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/select2.css') }}?v={{ hash_file('md5', public_path('css/select2.css')) }}">
<style>
    .cheque-hero {
        background: linear-gradient(120deg, #1a56db 0%, #4dabf7 60%, #74c0fc 100%);
        border-radius: 12px 12px 0 0;
        height: 64px;
        position: relative;
    }

    .cheque-hero-icon {
        background: linear-gradient(135deg, #1a56db, #4dabf7);
        width: 76px;
        height: 76px;
        border-radius: 12px;
        border: 4px solid #fff;
        box-shadow: 0 4px 16px rgba(26, 86, 219, .25);
        position: absolute;
        bottom: -38px;
        left: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
    }

    .section-title {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #6b7280;
        padding-bottom: .5rem;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 1rem;
    }

    .form-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1.25rem;
    }

    .form-check-toggle {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        flex-wrap: wrap;
        padding: .75rem 1rem;
        background: #f8fafc;
        border: 1px solid #e9ecef;
        border-radius: 8px;
    }

    .form-check-toggle .form-check {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin: 0;
        cursor: pointer;
    }

    .form-check-toggle .form-check-input {
        width: 1.15em;
        height: 1.15em;
        cursor: pointer;
        margin: 0;
    }

    .form-check-toggle .form-check-label {
        font-size: .875rem;
        font-weight: 500;
        cursor: pointer;
    }
</style>
@endsection

@section('content')
<div class="page-body pt-4">
    <div class="container-xl">

        {{-- Hero Banner --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="cheque-hero">
                <div class="cheque-hero-icon">
                    <i class="fa-solid fa-money-check" style="font-size:26px;"></i>
                </div>
            </div>
            <div class="px-4 pb-3" style="padding-top:50px !important;">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h3 class="mb-0 fw-bold">Add Manual Cheque</h3>
                        <div class="text-muted small mt-1">Fill in the details to create a new cheque entry</div>
                    </div>
                    <div>
                        <a href="{{ route('manual-cheques.index') }}"
                            class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                            @include('icons.prev', ['size' => 15])
                            Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form --}}
        <form id="form_manual_cheque" novalidate>
            @csrf
            <input type="hidden" name="uuid" id="uuid" class="form-control" value="{{ (string) Str::uuid() }}" readonly>
            {{-- Section: Payee Information --}}
            <div class="form-card">
                <div class="section-title d-flex justify-content-between align-items-center">
                    <div><i class="fa-solid fa-user me-1"></i> Payee Information</div>
                    <div class="btn-group" role="group" aria-label="Payee Type Toggle">
<input type="radio" class="btn-check" name="payee_type" id="type_manual" value="manual" autocomplete="off" checked>
                        <label class="btn btn-outline-primary btn-sm" for="type_manual">Manual Entry</label>

<input type="radio" class="btn-check" name="payee_type" id="type_account" value="account" autocomplete="off">
                        <label class="btn btn-outline-primary btn-sm" for="type_account">From Account Master</label>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-12" id="div_name">
                        <label class="form-label fw-semibold small" for="name">
                            Payee Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="name" name="name" class="form-control"
                            placeholder="Enter name to print on cheque" autocomplete="off">
                        <div class="invalid-feedback" id="err_name"></div>
                    </div>
                    <div class="col-md-12 d-none" id="div_account_id">
<div class="row align-items-center">
    <div class="col-md-8">
<label class="form-label fw-semibold small" for="account_id">
    Account (Name By Master) <span class="text-danger">*</span>
</label>
<select id="account_id" name="account_id" class="form-select" style="width:100%;" data-placeholder="— Search Account —">
    <option value=""></option>
</select>
<div class="invalid-feedback" id="err_account_id"></div>
</div>
<div class="col-md-4">
    <span class="fw-bold d-none mt-4 text-muted" id="account_total_badge"
        style="font-size: 1.15rem; display: inline-block;">
        Total Till Date ₹<span class="text-dark" id="account_total_val">0.00</span>
    </span>
</div>
</div>
</div>
                </div>
            </div>

            {{-- Section: Payment & Cheque Details --}}
            <div class="form-card">
                <div class="row g-4">
                    {{-- Left Column: Amount --}}
                    <div class="col-md-6">
                        <div class="section-title">
                            <i class="fa-solid fa-money-bill me-1"></i> Payment Details
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small" for="cheque_date">
                                Cheque Date <span class="text-danger">*</span>
                            </label>
<div class="row align-items-center">
    <div class="col-md-8">
        <input type="text" id="cheque_date" name="cheque_date" class="form-control" value="{{ date('d-m-Y') }}"
            autocomplete="off" placeholder="DD-MM-YYYY">
        <div class="invalid-feedback" id="err_cheque_date"></div>
    </div>
<div class="col-md-4">
    <span id="cheque_day_name" class="fw-bold {{ date('l') === 'Sunday' ? 'text-danger' : 'text-muted' }}"
        style="font-size: 1.15rem;">{{ date('l') }}</span>
</div>
</div>
</div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small" for="cheque_no">
                                Cheque No.
                            </label>
                            <input type="text" id="cheque_no" name="cheque_no" class="form-control"
                                placeholder="Enter cheque number" autocomplete="off">
                            <div class="invalid-feedback" id="err_cheque_no"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small" for="amount">
                                Amount (₹) <span class="text-danger">*</span>
                            </label>
                            {{-- <div class="input-group"> --}}
                            {{-- <span class="input-group-text fw-semibold">₹</span> --}}
<input type="number" id="amount" name="amount" class="form-control" placeholder="0.00" min="0" step="0.01"
    autocomplete="off">
                            {{-- </div> --}}
                            <div class="invalid-feedback" id="err_amount"></div>
                        </div>
                    </div>

                    {{-- Right Column: Cheque Options --}}
                    <div class="col-md-6">
                        <div class="section-title">
                            <i class="fa-solid fa-sliders me-1"></i> Cheque Options
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small" for="cheque_format_id">
                                Cheque Format <span class="text-danger">*</span>
                            </label>
                            <select id="cheque_format_id" name="cheque_format_id" class="form-select select2-basic">
                                <option value=""></option>
                                @foreach($chequeFormats as $format)
                                <option value="{{ $format->id }}">{{ $format->formate_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="err_cheque_format_id"></div>
                        </div>

                        <div class="form-check-toggle mt-3">
                            <div class="form-check">
<input class="form-check-input" type="checkbox" id="payee_pay" checked name="payee_pay" value="1">
                                <label class="form-check-label" for="payee_pay">
<i class="fa-solid fa-user-check text-primary me-1"></i> Account Payee <small>(Press
    Space to check)</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Full Width: Narration --}}
                    <div class="col-md-12 mt-4 pt-2 border-top">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small" for="narration">
                                    Narration <span class="text-muted fw-normal">(Optional)</span>
                                </label>
                                <input type="text" id="narration" name="narration" class="form-control"
                                    placeholder="Enter cheque narration/remarks" autocomplete="off">
                                <div class="invalid-feedback" id="err_narration"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small" for="second_narration">
                                    Second Narration <span class="text-muted fw-normal">(Optional)</span>
                                </label>
                                <input type="text" id="second_narration" name="second_narration" class="form-control"
                                    placeholder="Enter second narration" autocomplete="off">
                                <div class="invalid-feedback" id="err_second_narration"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="d-flex justify-content-end gap-2 pb-4">
                {{-- <a href="javascript:void(0)" class="btn btn-outline-secondary" onclick="document.getElementById('form_manual_cheque').reset();">
                    <i class="fa-solid fa-xmark me-1"></i> Cancel
                </a> --}}
                <button type="button" id="btn_save" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i> Save Cheque & Print
                </button>
            </div>

        </form>

    </div>
</div>
@endsection

@section('script')
<script>
    const today = "{{ date('Y-m-d') }}";

    $(document).ready(function() {
        bindSelect2();

        if (typeof DateInput !== 'undefined') {
            new DateInput("#cheque_date", today, today);
        }

$('#cheque_date').on('change', function() {
const dateVal = $(this).val();
if (dateVal) {
const parts = dateVal.split('-');
if (parts.length === 3) {
const d = new Date(parts[2], parts[1] - 1, parts[0]);
const dayName = d.toLocaleDateString('en-US', { weekday: 'long' });
$('#cheque_day_name').text(dayName);
if (dayName === 'Sunday') {
$('#cheque_day_name').removeClass('text-muted').addClass('text-danger');
Swal.fire({
icon: 'warning',
title: 'Warning!',
html: 'Payment is not permitted on <span class="text-danger fw-bold">Sunday</span>. Please choose another date.',
confirmButtonText: 'OK',
confirmButtonColor: '#6f42c1',
focusConfirm: true,
didOpen: () => {
setTimeout(() => {
Swal.getConfirmButton().focus();
}, 50);
}
}).then(() => {
$('#cheque_date').val('');
$('#cheque_day_name').text('');
setTimeout(() => $('#cheque_date').focus(), 100);
});
} else {
$('#cheque_day_name').removeClass('text-danger').addClass('text-muted');
}
}
} else {
$('#cheque_day_name').text('');
}
});
        // Handle Payee Type Toggle
        $('input[name="payee_type"]').on('change', function() {
            var type = $(this).val();

            if (type === 'manual') {
                $('#div_name').removeClass('d-none');
                $('#div_account_id').addClass('d-none');
                $('#account_id').val(null).trigger('change'); // Clear account selection
            } else {
                $('#div_account_id').removeClass('d-none');
                $('#div_name').addClass('d-none');
                $('#name').val(''); // Clear manual name
            }
        });

$('#account_id').on('change', function() {
var accountId = $(this).val();
if (accountId) {
$('#account_total_val').html('<i class="fa-solid fa-spinner fa-spin text-primary"></i>');
$('#account_total_badge').removeClass('d-none');
$.ajax({
url: "{{ route('manual-cheques.get_payee_total') }}",
type: 'GET',
data: { account_id: accountId },
success: function(response) {
const total = parseFloat(response.total || 0).toLocaleString('en-IN', {
minimumFractionDigits: 2,
maximumFractionDigits: 2
});
$('#account_total_val').text(total);
$('#account_total_badge').removeClass('d-none');
},
error: function() {
$('#account_total_badge').addClass('d-none');
}
});
} else {
$('#account_total_badge').addClass('d-none');
$('#account_total_val').text('0.00');
}
});
        $('#btn_save').on('click', function(e) {
            e.preventDefault();

            let valid = true;
            $('.invalid-feedback').text('');
            $('.is-invalid').removeClass('is-invalid');

            const type = $('input[name="payee_type"]:checked').val();
            const amount = $('#amount').val();
            const chequeFormatId = $('#cheque_format_id').val();
            const chequeDate = $('#cheque_date').val();

            if (type === 'manual' && !$('#name').val().trim()) {
                $('#name').addClass('is-invalid');
                $('#err_name').text('Payee Name is required.');
                valid = false;
            }
            if (type === 'account' && !$('#account_id').val()) {
                $('#account_id').addClass('is-invalid');
                $('#err_account_id').text('Account is required.');
                valid = false;
            }
            if (!amount || parseFloat(amount) <= 0) {
                $('#amount').addClass('is-invalid');
                $('#err_amount').text('Amount is required.');
                valid = false;
            }
            if (!chequeDate) {
                $('#cheque_date').addClass('is-invalid');
                $('#err_cheque_date').text('Date is required.');
                valid = false;
            }
            if (!chequeFormatId) {
                $('#cheque_format_id').addClass('is-invalid');
                $('#err_cheque_format_id').text('Format is required.');
                valid = false;
            }

            if (!valid) {
                showToast('error', 'Please fill out all required fields.');
                return;
            }

            Swal.fire({
                title: "Save & Print?",
                text: "Are you sure you want to save and print this cheque?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, save & print!"
            }).then((result) => {
                if (result.isConfirmed) {
                    saveCheque();
                }
            });
        });
    })

    function saveCheque() {
        const form = document.getElementById('form_manual_cheque');
        const formData = new FormData(form);

        // Show loader
        $('.loader-content').show();

        $.ajax({
            url: "{{ route('manual-cheques.store') }}",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('.loader-content').hide();
                if (response.status) {
                    showToast('success', response.message);

                    if (response.html) {
                        const printWindow = window.open('', '_blank');
                        printWindow.document.write(response.html);
                        printWindow.document.close();
                        printWindow.focus();
                    }
                }
            },
            error: function(xhr) {
                $('.loader-content').hide();
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    for (const key in errors) {
                        $(`#${key}`).addClass('is-invalid');
                        $(`#err_${key}`).text(errors[key][0]);
                    }
                } else {
                    showToast('error', xhr.responseJSON?.message || 'Something went wrong.');
                }
            }
        });
    }

    function bindSelect2() {
        $('#cheque_format_id').select2({
            theme: 'bootstrap-5',
            allowClear: true,
            placeholder: '— Select Cheque Format —',
            width: '100%'
        });

        const partyLookupUrl = "{{ route('manual-cheques.accounts') }}";
        $('#account_id').select2({
            theme: 'bootstrap-5',
            allowClear: true,
            placeholder: '— Search account by name —',
            minimumInputLength: 0,
            width: '100%',
            ajax: {
                url: partyLookupUrl,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term ?? ''
                    };
                },
                processResults: function(response) {
                    const items = response.data ?? [];
                    return {
                        results: items.map(function(item) {
                            return {
                                id: item.id,
                                text: item.name,
                                party_type: item.party_type ?? '',
                            };
                        }),
                    };
                },
                cache: true,
            },
            templateResult: function(item) {
                if (item.loading) return item.text;
                const typeColors = {
                    customer: 'bg-success-subtle text-success',
                    supplier: 'bg-warning-subtle text-warning',
                    broker: 'bg-info-subtle text-info',
                    account: 'bg-secondary-subtle text-secondary',
                };
                const cls = typeColors[item.party_type] ?? 'bg-secondary-subtle text-secondary';
                const badge = item.party_type ?
                    `<span class="badge rounded-pill ${cls} ms-1" style="font-size:.62rem;">${item.party_type}</span>` :
                    '';
                return `<span>${item.text}${badge}</span>`;
            },
            templateSelection: function(item) {
                return item.text || item.id;
            },
            escapeMarkup: function(markup) {
                return markup;
            }
        });

        $(document).on('select2:open', function(e) {
            var $select = $(e.target);
            var $search = $select.data('select2').$dropdown.find('.select2-search__field');
            $search.off('keydown.select2Enter').on('keydown.select2Enter', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    $select.select2('close');
                    moveFocusToNextField($select);
                }
            });
        });

    }
</script>
@endsection