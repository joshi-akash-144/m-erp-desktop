(function () {
    let validator;

    $(document).ready(function () {
        // validation
        validator = new JustValidate("#item_form", {
            validateBeforeSubmitting: true,
            focusInvalidField: true,
        });

        validator
            .addField("#name", [
                { rule: "required", errorMessage: "Item Name is required" }
            ])
            .addField("#print_name", [
                { rule: "required", errorMessage: "Print Name is required" }
            ])
            .addField("#item_group_id", [
                { rule: "required", errorMessage: "Item Group is required" }
            ])
            .addField("#unit_id", [
                { rule: "required", errorMessage: "Unit is required" }
            ])
            .addField("#tax_category_id", [
                { rule: "required", errorMessage: "Tax Category is required" }
            ])
            .addField("#is_maintain_stock_balance", [
                { rule: "required", errorMessage: "Maintain Stock Balance is required" }
            ])
            .onSuccess((event) => {
                event.preventDefault();
                submitFormAjax(document.getElementById("item_form"));
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
        const $btn = $("#item_modal .form-save-btn");

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
                    showToast("success", message || (isEdit ? "Item updated successfully!" : "Item saved successfully!"));
                    $('#item_modal').modal('hide');
                    if (typeof refreshTable === "function") {
                        refreshTable("item_table");
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
                        ${isEdit ? "Update Item" : "Save Item"}
                    `);
                }

                isSubmitting = false;
                if (typeof hideLoader === "function") hideLoader();
            }
        });
    }
})();