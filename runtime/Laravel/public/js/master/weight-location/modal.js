(function () {
    let validator;

    $(document).ready(function () {
        const formEl = document.getElementById("weight_location_form");

        if (!formEl) return;

        // Prevent native form submission
        $(formEl).on("submit", function (e) {
            e.preventDefault();
        });

        // validation
        validator = new JustValidate("#weight_location_form", {
            validateBeforeSubmitting: true,
            focusInvalidField: true,
        });

        validator
            .addField("#godown_name", [
                { rule: "required", errorMessage: "Godown Name is required" }
            ])
            .addField("#ip_address", [
                { rule: "required", errorMessage: "IP Address is required" }
            ])
            .onSuccess((event) => {
                event.preventDefault();
                submitFormAjax(formEl);
            });

        // Custom Enter Key Navigation
        $("#url").on("keydown", function (e) {
            if (e.which === 13) {
                e.preventDefault();
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
        const $btn = $("#weight_location_modal .form-save-btn");

        const method = ($form.attr("data-method") || $form.attr("method") || "POST").toUpperCase();
        const url = $form.attr("action");

        const isEdit = method === "PUT" || url.toLowerCase().includes("update");

        // Button loading state
        if ($btn.length) {
            $btn.prop("disabled", true).html(`
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                ${isEdit ? "Updating" : "Saving"}...
            `);
        }

        const formData = new FormData(form);

        // jQuery Ajax
        $.ajax({
            url: url,
            type: "POST", // Laravel handles PUT via @method in form
            data: formData,
            processData: false,
            contentType: false,
            success: function (data) {
                if (data.success) {
                    showToast("success", data.message || "Saved successfully!");
                    if (typeof weightLocationTable !== "undefined") {
                        weightLocationTable.replaceData();
                    }
                    $('#weight_location_modal').modal('hide');
                } else {
                    showToast("error", data.message || "Something went wrong.");
                }
            },
            error: function (xhr) {
                const errMsg = xhr.responseJSON?.message || "Server error while submitting.";
                showToast("error", errMsg);
                
                // Show validation errors if any
                if (xhr.status === 422 && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(key => {
                        showToast("error", errors[key][0]);
                    });
                }
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
                        ${isEdit ? "Update" : "Save"}
                    `);
                }
                isSubmitting = false;
            }
        });
    }
})();