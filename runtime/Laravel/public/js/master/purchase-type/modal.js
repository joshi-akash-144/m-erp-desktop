(function () {
    let validator;

    $(document).ready(function () {
        const formEl = document.getElementById("purchase_type_form");

        // Prevent native form submission (Enter key redirect fix)
        $(formEl).on("submit", function (e) {
            e.preventDefault();
        });

        // validation
        validator = new JustValidate("#purchase_type_form", {
            validateBeforeSubmitting: true,
            focusInvalidField: true,
        });

        validator
            .addField("#name", [
                { rule: "required", errorMessage: "Purchase Type Name is required" },
                { rule: "maxLength", value: 100, errorMessage: "Name cannot exceed 100 characters" }
            ])
            .addField("#account_id", [
                { rule: "required", errorMessage: "Account is required" },
            ])
            .addField("#taxation_type", [
                { rule: "required", errorMessage: "Taxation Type is required" },
            ])
            .addField("#region", [
                { rule: "required", errorMessage: "Region is required" },
            ])
            .addField("#transaction_type", [
                { rule: "required", errorMessage: "Transaction Type is required" },
            ])
            .addField("#cgst", [
                { rule: "required", errorMessage: "CGST is required" },
            ])
            .addField("#sgst", [
                { rule: "required", errorMessage: "SGST is required" },
            ])
            .addField("#igst", [
                { rule: "required", errorMessage: "IGST is required" },
            ])
            .onSuccess((event) => {
                event.preventDefault();
                submitFormAjax(document.getElementById("purchase_type_form"));
            });
        // Custom Enter Key Navigation
        $("#sgst").on("keydown", function (e) {
            if (e.which === 13) {
                e.preventDefault();
                $(".form-save-btn").focus();
            }
        });
        // Custom Enter Key Navigation
        $("#igst").on("keydown", function (e) {
            if (e.which === 13) {
                e.preventDefault();
                $(".form-save-btn").focus();
            }
        });

        $("#taxation_type").on("change", toggleGSTSection);
        $("#region").on("change", regionBasedGST);

        // Initial state
        toggleGSTSection();
    });

    function regionBasedGST() {
        const region = document.getElementById('region').value;
        const cgst = document.getElementById('cgst');
        const sgst = document.getElementById('sgst');
        const igst = document.getElementById('igst');

        if (region === 'interstate') {
            document.getElementById('cgst_section')?.classList.add('d-none');
            document.getElementById('sgst_section')?.classList.add('d-none');
            document.getElementById('igst_section')?.classList.remove('d-none');
            if (cgst) cgst.value = 0;
            if (sgst) sgst.value = 0;
        } else if (region === 'local') {
            document.getElementById('cgst_section')?.classList.remove('d-none');
            document.getElementById('sgst_section')?.classList.remove('d-none');
            document.getElementById('igst_section')?.classList.add('d-none');
            if (igst) igst.value = 0;
        } else {
            document.getElementById('cgst_section')?.classList.remove('d-none');
            document.getElementById('sgst_section')?.classList.remove('d-none');
            document.getElementById('igst_section')?.classList.remove('d-none');
        }
    }

    function toggleGSTSection() {
        const taxationType = document.getElementById('taxation_type').value;
        const gstSection = document.getElementById('gst_detail_section');
        const cgst = document.getElementById('cgst');
        const sgst = document.getElementById('sgst');
        const igst = document.getElementById('igst');

        if (taxationType !== 'taxable') {
            document.getElementById('gst_detail_section')?.classList.add('d-none');
            if (cgst) cgst.value = 0;
            if (sgst) sgst.value = 0;
            if (igst) igst.value = 0;
        } else {
            document.getElementById('gst_detail_section')?.classList.remove('d-none');
            if (gstSection) gstSection.style.display = 'block';
            regionBasedGST();
        }
    }

    // ===================================================================
    // SUBMIT HANDLER
    // ===================================================================
    let isSubmitting = false;

    function submitFormAjax(form) {
        if (isSubmitting) return;
        isSubmitting = true;

        const $form = $(form);
        const $btn = $("#purchase_type_modal .form-save-btn");

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
                    showToast("success", message || (isEdit ? "Purchase Type updated successfully!" : "Purchase Type saved successfully!"));
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
                        ${isEdit ? "Update Purchase Type" : "Save Purchase Type"}
                    `);
                }
                isSubmitting = false;
                $('#purchase_type_modal').modal('hide');
            }
        });
    }
})();