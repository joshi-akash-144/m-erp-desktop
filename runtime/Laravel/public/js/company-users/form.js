$(document).ready(function () {

    // -----------------------------------
    // Initialize Select2
    // -----------------------------------
    $(".select2-company").select2({
        theme: "bootstrap-5",
        allowClear: true,
        placeholder: "Select Company…",
        width: "100%",
    });

    $(".select2-users").select2({
        theme: "bootstrap-5",
        allowClear: true,
        placeholder: "Select one or more users…",
        width: "100%",
    });

    // -----------------------------------
    // Validation with JustValidate
    // -----------------------------------
    const validation = new JustValidate("#companyUserForm", {
        errorFieldCssClass: "is-invalid",
    });

    validation
        .addField('[name="company_id"]', [
            { rule: "required", errorMessage: "Please select a company" },
        ])
        .addField('[name="user_ids[]"]', [
            { rule: "required", errorMessage: "Please select at least one user" },
        ]);

    validation.onSuccess(function (event) {
        handleSubmit(event);
    });

    // -----------------------------------
    // Submit Handler
    // -----------------------------------
    function handleSubmit(event) {
        event.preventDefault();

        const form     = event.target;
        const formData = new FormData(form);

        showLoader("Saving assignment…");

        $.ajax({
            url: storeCompanyUserUrl,
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                hideLoader();
                if (res.success) {
                    showToast("success", res.message);
                    setTimeout(function () {
                        window.location.href = companyUsersIndexUrl;
                    }, 1200);
                } else {
                    showToast("error", res.message || "Failed to save assignment.");
                }
            },
            error: function (xhr) {
                hideLoader();
                if (typeof handleAjaxError === "function") {
                    handleAjaxError(xhr);
                } else {
                    showToast("error", "Something went wrong!");
                }
            },
        });
    }
});
