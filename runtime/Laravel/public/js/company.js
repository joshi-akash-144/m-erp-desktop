$(document).ready(function () {

    // ===== Initialize Select2 =====
    bindSelect2();

    // ===== Date Input =====
    if ($('#financial_year_start').length) {
        new DateInput('#financial_year_start', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    }



    // ===== Form Validation =====
    validateForm();

    // ===== Type of Dealer → GST toggle =====
    $('#type_of_dealer').on('change', function () {
        var selectedValue = $(this).val();
        var $gstNumber = $('#gst_number');

        if (selectedValue === 'registered') {
            $gstNumber.prop('readonly', false).removeClass('readonly');
        } else {
            $gstNumber.val('').prop('readonly', true).addClass('readonly');
        }
    });

    // ===== Company Name → auto-fill Print Name & Legal Name =====
    $('#name').on('input', function () {
        var val = $(this).val();
        $('#print_name').val(val);
        $('#legal_name').val(val);
    });

    // Mobile Number
    initMobileNumberValidation('#mobile_number');


    // ===== Email to lowercase =====
    $('#email').on('input', function () {
        $(this).val($(this).val().toLowerCase());
    });

    // ===== Bootstrap Popovers =====
    initBootstrapUI();

    // ===== Initial Focus =====
    $('#name').focus();
});


/**
 * Initialize Bootstrap Tooltips and Popovers
 */
function initBootstrapUI() {
    if (typeof bootstrap === 'undefined') return;

    $('[data-bs-toggle="tooltip"]').each(function () {
        new bootstrap.Tooltip(this);
    });

    $('[data-bs-toggle="popover"]').each(function () {
        new bootstrap.Popover(this, {
            delay: { show: 50, hide: 50 },
            html: $(this).attr('data-bs-html') === 'true',
            placement: $(this).attr('data-bs-placement') || 'auto',
            trigger: $(this).attr('data-bs-trigger') || 'click',
        });
    });
}


/**
 * Initialize Select2 on form selects + Enter key navigation
 */
function bindSelect2() {
    var selectIdArray = ['#country_id', '#state_id', '#type_of_dealer'];

    selectIdArray.forEach(function (element) {
        $(element).select2({
            theme: 'bootstrap-5',
            allowClear: true,
            placeholder: 'Select ...',
        });
    });

    $(document).on('select2:open', function (e) {
        var selectElement = $(e.target);
        var searchInput = selectElement.data('select2').$dropdown.find('.select2-search__field');

        searchInput.off('keydown.select2Enter').on('keydown.select2Enter', function (event) {
            if (event.which === 13) {
                event.preventDefault();
                selectElement.select2('close');
                if (typeof moveFocusToNextField === 'function') {
                    moveFocusToNextField(selectElement);
                }
            }
        });
    });
}


/**
 * JustValidate form validation
 */
function validateForm() {
    var form = document.getElementById('company_form');
    if (!form) return;

    var validator = new JustValidate('#company_form', {
        validateBeforeSubmitting: true,
        focusInvalidField: true,
        errorLabelCssClass: 'text-danger',
    });

    validator
        .addField('#name', [
            { rule: 'required', errorMessage: 'Company Name is required' },
        ])
        .addField('#print_name', [
            { rule: 'required', errorMessage: 'Company Print Name is required' },
        ])
        .addField('#legal_name', [
            { rule: 'required', errorMessage: 'Company Legal Name is required' },
        ])
        .addField('#country_id', [
            { rule: 'required', errorMessage: 'Country is required' },
        ])
        .addField('#state_id', [
            { rule: 'required', errorMessage: 'State is required' },
        ])
        .addField('#email', [
            { rule: 'email', errorMessage: 'Email is not valid' },
        ])
        .addField('#mobile_number', [
            {
                validator: (value) => {
                    const digits = value.replace(/\D/g, "");
                    if (digits === '91' || digits.length === 0) return true;
                    return digits.length >= 12;
                },
                errorMessage: 'Mobile Number must be 10 digits',
            },
        ])
        .addField('#financial_year_start', [
            { rule: 'required', errorMessage: 'Year Begin Date is required' },
            {
                validator: function (value) {
                    return typeof isValidDateDMY === 'function' ? isValidDateDMY(value) : value.trim() !== '';
                },
                errorMessage: 'Enter a valid date in DD-MM-YYYY format',
            },
        ])
        .addField('#type_of_dealer', [
            { rule: 'required', errorMessage: 'Dealer Type is required' },
        ])
        .onSuccess(function (event) {
            event.preventDefault();
            submitFormAjax(form);
        });
}


/**
 * Ajax Form Submit using jQuery $.ajax
 * @param {HTMLFormElement} form
 */
function submitFormAjax(form) {
    var url = createCompanyUrl;
    var formData = new FormData(form);

    // Convert date from DD-MM-YYYY → YYYY-MM-DD
    var dateValue = formData.get('financial_year_start');
    if (dateValue && typeof formatDateToYMD === 'function') {
        formData.set('financial_year_start', formatDateToYMD(dateValue));
    }

    showLoader('Saving company details...');

    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            hideLoader();

            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: 'Company created successfully!',
                    confirmButtonText: 'Ok',
                    allowOutsideClick: false,
                }).then(function () {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'There was a problem saving the company. Please check the details.',
                    showCancelButton: true,
                    confirmButtonText: 'Retry',
                    cancelButtonText: 'Close',
                }).then(function (result) {
                    if (result.isConfirmed) {
                        submitFormAjax(form);
                    }
                });
            }
        },
        error: function (xhr) {
            hideLoader();

            if (typeof handleAjaxError === 'function') {
                handleAjaxError(xhr);
            } else {
                var errorMessage = 'Oops! Something went wrong. Try again later.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    var firstError = Object.values(xhr.responseJSON.errors)[0];
                    errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage,
                    confirmButtonText: 'Close',
                });
            }
        }
    });
}
