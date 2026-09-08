(function () {
    let validator;

    $(document).ready(function () {
        // validation
        validator = new JustValidate("#condition_form", {
            validateBeforeSubmitting: true,
            focusInvalidField: true,
        });

        validator
            .addField("#name", [
                { rule: "required", errorMessage: "Condition Name is required" },
                { rule: "maxLength", value: 100, errorMessage: "Name cannot exceed 100 characters" }
            ])
            .addField("#print_name", [
                { rule: "required", errorMessage: "Print Name is required" },
                { rule: "maxLength", value: 100, errorMessage: "Print Name cannot exceed 100 characters" }
            ])
            .onSuccess((event) => {
                event.preventDefault();
                submitFormAjax(document.getElementById("condition_form"));
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
        const $btn = $("#condition_modal .form-save-btn");

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
                    showToast("success", message || (isEdit ? "Condition updated successfully!" : "Condition saved successfully!"));
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
                        ${isEdit ? "Update Condition" : "Save Condition"}
                    `);
                }
                isSubmitting = false;
                $('#condition_modal').modal('hide');
            }
        });
    }
})();