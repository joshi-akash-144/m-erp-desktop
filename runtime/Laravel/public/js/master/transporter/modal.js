(function () {
    let validator;

    $(document).ready(function () {
        // Initialize JustValidate for the transporter form
        validator = new JustValidate("#transporter_form", {
            validateBeforeSubmitting: true,
            focusInvalidField: true,
            errorLabelCssClass: 'text-danger small mt-1',
        });

        validator
            .addField("#name", [
                { rule: "required", errorMessage: "Transporter Name is required" }
            ])
            .addField("#pan_no", [
                {
                    rule: "customRegexp",
                    value: /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/,
                    errorMessage: "Please Enter Valid Pan",
                }
            ])
            .addField("#postal_code", [
                {
                    rule: "customRegexp",
                    value: /^[0-9]{6}$/,
                    errorMessage: "Please Enter Valid postal code",
                }
            ])   
            .addField("#mobile", [
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
            .onSuccess((event) => {
                event.preventDefault();
                submitFormAjax(document.getElementById("transporter_form"));
            });

        // Initialize Select2 inside the modal
        $('.select2').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#transporter_modal')
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
        const $btn = $form.find('button[type="submit"]');
        const originalHtml = $btn.html();

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
                    showToast("success", message || (isEdit ? "Transporter updated successfully!" : "Transporter saved successfully!"));

                    // Refresh the table
                    if (typeof table !== "undefined") {
                        table.replaceData();
                         $('#transporter_modal').modal('hide');
                    } else if (typeof refreshTable === "function") {
                        refreshTable("transporter_table");
                    }

                    $('#transporter_modal').modal('hide');
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
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-square-check" width="24" height="24"
                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                            <path d="M9 12l2 2l4 -4" />
                        </svg>
                        ${isEdit ? "Update Transporter" : "Save Transporter"}
                    `);
                }

                isSubmitting = false;
                if (typeof hideLoader === "function") hideLoader();               
            }
        });
    }
})();