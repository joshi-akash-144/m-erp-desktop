(function () {
    let validator;
    
    new DateInput("#renewal_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#national_permit_due_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#fitness_due_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#policy_due_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#passing_due_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#tax_due_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#permit_due_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#puc_due_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#insurance_due_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);   

    $(document).ready(function () {
        const formEl = document.getElementById("vehicle_form");
         // Prevent native form submission (Enter key redirect fix)
        $(formEl).on("submit", function (e) {
            e.preventDefault();
        });
   initVehicleRegValidation(".txtRegNo");
        // validation
        validator = new JustValidate("#vehicle_form", {
            validateBeforeSubmitting: true,
            focusInvalidField: true,
        }).addField("#name", [
                { rule: "required", errorMessage: "Vehicle Number is required" }
        ]).addField("#puc_no", [
                { rule: "customRegexp", value: /^[A-Za-z0-9\/-]{8,25}$/, errorMessage: "Please enter a valid PUC Number" }
        ]).onSuccess((event) => {
            event.preventDefault(); 
            submitFormAjax(document.getElementById("vehicle_form"));
        });
            
        // Initialize select2
        if ($('.select2').length) {
            $('.select2').select2({
                dropdownParent: $('#vehicle_modal'),
                width: '100%',
                theme: 'bootstrap-5'
            });


            // Custom Enter Key Navigation for the last field
            $("#puc_due_date").on("keydown", function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    e.stopPropagation();
                    $(".form-save-btn").focus();
                }
            });
        }
       
    });

    // ===================================================================
    // SUBMIT HANDLER
    // ===================================================================
    let isSubmitting = false;

    function submitFormAjax(form) {
        if (isSubmitting) return;
        isSubmitting = true;
        
        const $form = $(form);
        const $btn = $("#vehicle_modal .form-save-btn");

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
            "renewal_date", "national_permit_due_date", "fitness_due_date", 
            "policy_due_date", "passing_due_date", "tax_due_date", 
            "permit_due_date", "puc_due_date"
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
                    showToast("success", message || (isEdit ? "Vehicle updated successfully!" : "Vehicle saved successfully!"));
                     $('#vehicle_modal').modal('hide');
                    if (typeof table !== "undefined") table.setData(); // Using generic table pointer
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