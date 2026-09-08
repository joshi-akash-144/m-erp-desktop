(function () {
    let validator;

    $(document).ready(function () {

       const formEl = document.getElementById("transport_parties_form");
        // Prevent native form submission
        $(formEl).on("submit", function (e) {
            e.preventDefault();
        });

        // validation
        validator = new JustValidate("#transport_parties_form", {
            validateBeforeSubmitting: true,
            focusInvalidField: true,
        }).addField("#name", [
          {
            rule: "required",
            errorMessage: "Name is required",
          },
          ])
          .addField("#state_id", [
            {
              rule: "required", 
              errorMessage: "State is required",
            },
          ])
          .addField("#city", [
            {
              rule: "required",
              errorMessage: "City is required",
            },
          ])
          .addField("#email", [
            {
              rule: "email",
              errorMessage: "Enter valid email address",
            },
            {
              rule: "customRegexp",
              value: /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/,
              errorMessage: "Email format is not valid",
            },
          ])
          .addField("#mobile_number", [
              {
                rule: "customRegexp",
                value: /^(\+91-)?[6-9]\d{9}$/,
                errorMessage: "Enter a valid 10-digit mobile number",
              },
          ])
          .addField("#postal_code", [
              {
                  rule: "customRegexp",
                  value: /^[0-9]\d{5}$/,
                  errorMessage: "Enter 6 digit postal code",
              },
          ])
          .onSuccess((event) => {
            event.preventDefault(); 
            submitFormAjax(document.getElementById("transport_parties_form"));
          });
            
            
        // Initialize select2
        if ($('.select2').length) {
            $('.select2').select2({
                dropdownParent: $('#transport_parties_modal'),
                width: '100%',
                theme: 'bootstrap-5'
            }).on('select2:select', function () {
                $('#city').focus();
            });

        }

        // Custom Enter Key Navigation for the last field
        $("#address_two").on("keydown", function (e) {
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
        const $btn = $("#transport_parties_modal .form-save-btn");

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
                    showToast("success", message || (isEdit ? "Consignor/Consignee updated successfully!" : "Consignor/Consignee saved successfully!"));
                     $('#transport_parties_modal').modal('hide');
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
                        ${isEdit ? "Update Consignor/Consignee" : "Save Consignor/Consignee"}
                    `);
                }
                isSubmitting = false;               
            }
        });
    }
})();