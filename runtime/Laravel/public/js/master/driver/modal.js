(function () {
    let validator;
    
    new DateInput("#license_expiry_date_tr", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#date_of_joining", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#license_expiry_date_nt", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);   

    $(document).ready(function () {
        const formEl = document.getElementById("driver_form");

        // Prevent native form submission (Enter key redirect fix)
        $(formEl).on("submit", function (e) {
            e.preventDefault();
        });

        // validation
        validator = new JustValidate("#driver_form", {
            validateBeforeSubmitting: true,
            focusInvalidField: true,
        }).addField("#name", [
                { rule: "required", errorMessage: "Driver Name is required" },
                { rule: "maxLength", value: 255, errorMessage: "Driver Name cannot exceed 255 characters" }
            ])
            .addField("#adhara_number", [
                { rule: "minLength", value: 12, errorMessage: "Aadhaar Number must be exactly 12 digits" },
                { rule: "maxLength", value: 12, errorMessage: "Aadhaar Number cannot exceed 12 digits" },
                { rule: "customRegexp", value: /^[0-9]{12}$/, errorMessage: "Aadhaar Number must contain only digits (Example: 123456789012)"}
           ]).addField("#bank_account_number", [
                {
                    rule: "customRegexp",
                    value: /^[0-9]{9,18}$/,
                    errorMessage: "Please enter a valid bank account number",
                }
            ]).addField("#opening_type", [
                { rule: "required", errorMessage: "Enter Type" },
                {
                    rule: "customRegexp",
                    value: /^[DC]$/,
                    errorMessage: "Only D or C",
                }
            ]).addField("#postal_code", [
                { rule: "customRegexp", value: /^[0-9]\d{5}$/, errorMessage: "Enter 6 digit pin code" },
            ]).addField("#mobile_number", [
                {
                    rule: "customRegexp",
                    value: /^(\+91-)?[6-9]\d{9}$/,
                    errorMessage: "Enter a valid 10-digit mobile number",
                }
            ]).addField("#pan", [
                {
                    rule: "customRegexp",
                    value: /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/,
                    errorMessage: "Please Enter Valid Pan",
                },
            ]).addField("#opening_balance", [
                {
                    rule: "required",
                    errorMessage: "Please Enter Opening Balance",
                },
            ]).addField("#account_group_id", [
                {
                    rule: "required",
                    errorMessage: "Please Select Account Group",
                }
            ]).addField("#opening_type", [
                {
                    rule: "required",
                    errorMessage: "Please Select Opening Type",
                }
            ]).onSuccess((event) => {
                event.preventDefault(); 
                submitFormAjax(document.getElementById("driver_form"));
            });
        
        // Initialize select2
        if ($('.select2').length) {
            $('.select2').select2({
                dropdownParent: $('#driver_modal'),
                width: '100%',
                theme: 'bootstrap-5'
            });
            
        }

        // Custom Enter Key Navigation for the last field
        $("#bank_ifsc").on("keydown", function (e) {
            if (e.which === 13) {
                e.preventDefault();
                e.stopPropagation();
                $(".form-save-btn").focus();
            }
        });
       
    });

    // ===================================================================
    // SUBMIT HANDLER
    // ===================================================================
    let isSubmitting = false;

    function submitFormAjax(form) {
        if (isSubmitting) return;
        isSubmitting = true;

        const $form = $(form);
        const $btn = $("#driver_modal .form-save-btn");

        const method = ($form.attr("data-method") || $form.attr("method") || "POST").toUpperCase();
        const url = $form.attr("action");
        const isEdit = method === "PUT" || url.toLowerCase().includes("update");

        // Button loading state
        if ($btn.length) {
            $btn.prop("disabled", true).html(`
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                ${isEdit ? "Updating" : "Saving"} <span class="animated-dots"></span>
            `);
        }

        const formData = new FormData(form);

        // Convert dates to YMD format before sending
        const dateFields = [
            'license_expiry_date_tr', 'date_of_joining', 'license_expiry_date_nt',
        ];
        dateFields.forEach((field) => {
            if (formData.has(field) && formData.get(field)) {
                formData.set(field, formatDateToYMD(formData.get(field)));
            }
        });

        if (isEdit) {
            formData.append("_method", "PUT");
        }

        $.ajax({
            url: url,
            type: "POST", // Laravel handles PUT via _method
            data: formData,
            processData: false,
            contentType: false,
            success: function (data) {
                const success = data.success;
                const message = data.message;
                
                if (success) {
                    showToast("success", message || (isEdit ? "Driver updated successfully!" : "Driver saved successfully!"));
                    $('#driver_modal').modal('hide');
                    if (typeof table !== "undefined") table.setData();
                } else {
                    showToast("error", message || "Something went wrong.");
                }
            },
            error: function (xhr) {
                console.error("Submit Error:", xhr);
                const errMsg = xhr.responseJSON?.message || "Server error while submitting. Please try again.";
                showToast("error", errMsg);
            },
            complete: function () {
                if ($btn.length) {
                    $btn.prop("disabled", false).html(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-square-check">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                            <path d="M9 12l2 2l4 -4" />
                        </svg>
                        ${isEdit ? "Update Vehicle" : "Save Vehicle"}
                    `);
                }
                isSubmitting = false;               
            }
        });
    }
})();