(function () {
    let validator;

    $(document).ready(function () {
        const formEl = document.getElementById("expense_type_form");

        // Prevent native form submission (Enter key redirect fix)
        $(formEl).on("submit", function (e) {
            e.preventDefault();
        });

        // validation
        validator = new JustValidate("#expense_type_form", {
            validateBeforeSubmitting: true,
            focusInvalidField: true,
        });

        validator
            .addField("#name", [
                { rule: "required", errorMessage: "Expense Type name is required" }
            ])
            .addField("#expense_type_group_id", [
                { rule: "required", errorMessage: "Expense Type Group is required" }
            ])
            .onSuccess((event) => {
                event.preventDefault();
                submitFormAjax(document.getElementById("expense_type_form"));
            });
            
            // Custom Enter Key Navigation
            $("#name").on("keydown", function (e) {
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
        const $btn = $("#expense_type_modal .form-save-btn");

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
                    showToast("success", message || (isEdit ? "Expense Type updated successfully!" : "Expense Type saved successfully!"));
                    if (typeof refreshTable === "function") {
                        refreshTable("expense_type_table");
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
                        ${isEdit ? "Update Expense Type" : "Save Expense Type"}
                    `);
                }

                isSubmitting = false;
                if (typeof hideLoader === "function") hideLoader();
                $('#expense_type_modal').modal('hide');
            }
        });
    }
})();