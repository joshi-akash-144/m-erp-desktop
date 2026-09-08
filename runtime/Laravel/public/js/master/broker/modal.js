(function () {
    let validator;

    $(document).ready(function () {
        const formEl = document.getElementById("broker_form");

        // Prevent native form submission (Enter key redirect fix)
        $(formEl).on("submit", function (e) {
            e.preventDefault();
        });
        // validation
        validator = new JustValidate("#broker_form", {
            validateBeforeSubmitting: true,
            focusInvalidField: true,
        });

        validator
            .addField("#name", [
                { rule: "required", errorMessage: "Broker name is required" }
            ])
            .addField("#print_name", [
                { rule: "required", errorMessage: "Print name is required" }
            ])
            .addField("#mobile_number", [
                {
                    rule: "customRegexp",
                    value: /^(?:\+?91[\s-]?)?[6-9]\d{4}[\s-]?\d{5}$/,
                    errorMessage: "Enter a valid 10-digit mobile number",
                }
            ])
            .addField("#email", [
                {
                    rule: "email",
                    errorMessage: "Enter valid email Address",
                },
                {
                    rule: "customRegexp",
                    value: /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/,
                    errorMessage: "Email Format is not valid",
                },
            ])
            .addField("#postal_code", [
                {
                    rule: "customRegexp",
                    value: /^[0-9]\d{5}$/,
                    errorMessage: "Enter 6 digit pin code",
                },
            ])   
            .onSuccess((event) => {
                event.preventDefault();
                submitFormAjax(document.getElementById("broker_form"));
            });
            // Custom Enter Key Navigation
            $("#bank_ifsc").on("keydown", function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $(".form-save-btn").focus();
                }
            });

            // Initialize mobile number validation and formatting
            initMobileNumberValidation('.txtMobile');
    });

    // ===================================================================
    // SUBMIT HANDLER
    // ===================================================================
    let isSubmitting = false;

    function submitFormAjax(form) {
        if (isSubmitting) return;
        isSubmitting = true;
        
        const $form = $(form);
        const $btn = $("#broker_modal .form-save-btn");

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

        if (isEdit) {
            formData.append("_method", "PUT");
        }

        // jQuery Ajax
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
                    showToast("success", message || (isEdit ? "Broker updated successfully!" : "Broker saved successfully!"));
                    $('#broker_modal').modal('hide');
                    if (typeof refreshTable === "function") {
                        refreshTable("broker_table");
                    } else if (typeof table !== "undefined") {
                        table.replaceData();
                    }
                } else {
                    showToast("error", message || "Something went wrong.");
                }
            },
            error: function (xhr) {
                console.error(xhr);
                const errMsg = xhr.responseJSON?.message || "Server error while submitting. Please try again.";
                showToast("error", errMsg);
            },
            complete: function () {
                // Reset button
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
                        ${isEdit ? "Update Broker" : "Save Broker"}
                    `);
                }

                isSubmitting = false;
                if (typeof hideLoader === "function") hideLoader();                
            }
        });
    }
})();