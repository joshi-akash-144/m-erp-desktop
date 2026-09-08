let validator;

$(document).ready(function () {

  // validation

  validator = new JustValidate("#account_form", {
    validateBeforeSubmitting: true,
    focusInvalidField: true,
  });

  validator
    .addField("#name", [
      { rule: "required", errorMessage: "Name is a required field" },
    ])
    .addField("#print_name", [
      { rule: "required", errorMessage: "Print Name is a required field" },
    ])
    .addField("#account_group_id", [
      { rule: "required", errorMessage: "Please Select Group" },
    ])
    .addField("#opening_type", [
      { rule: "required", errorMessage: "Enter Type" },
      {
        rule: "customRegexp",
        value: /^[DC]$/,
        errorMessage: "Only D or C",
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
        errorMessage: "Enter 6 digit pin code",
      },
    ])
    .addField("#email", [
      {
        rule: "email",
        errorMessage: "Enter valid email Address",
      },
      {
        rule: "customRegexp",
        value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        errorMessage: "Email Format is not valid",
      },
    ])
    .addField("#gst_number", [
      {
        validator: (value) => {
          const dealer_type =
            document.getElementById("type_of_dealer").value;
          if (
            dealer_type === "registered" ||
            dealer_type === "composition" ||
            dealer_type === "uni_holder"
          ) {
            return value.trim() !== "";
          }
          return true;
        },
        errorMessage: "Enter GST Number of selected dealer type",
      },
    ])
    .addField("#party_type", [
      {
        rule: "required",
        errorMessage: "Please Select Party Type",
      },
    ])
    .addField("#pan", [
      {
        rule: "customRegexp",
        value: /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/,
        errorMessage: "Please Enter Valid Pan",
      },
    ])
    .onSuccess((event) => {
      event.preventDefault();
      submitFormAjax(document.getElementById("account_form"));
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
    const $btn = $("#account_modal .form-save-btn");
    // const modalEl = document.querySelector("#account_modal");
    // const modal = bootstrap.Modal.getInstance(modalEl);

    const method = ($form.data("method") || $form.attr("method") || "POST").toUpperCase();
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

    // Handle billwise
    const isBillwiseValue = formData.get("is_billwise");
    if (!isBillwiseValue || isBillwiseValue == false || isBillwiseValue === '0') {
        formData.set("is_billwise", '0');
    } else {
        formData.set("is_billwise", '1');
    }

    if (isEdit) {
        formData.append("_method", "PUT");
    }
  console.log("method, url", method, url);

    // jQuery Ajax
    $.ajax({
        url: url,
      type: method,
        data: formData,
        processData: false,
        contentType: false,
        success: function (data) {
            const success = data.success;
            const message = data.message;
          console.log("data");


            if (success) {
                showToast("success", message || (isEdit ? "Account updated successfully!" : "Account saved successfully!"));
                $('#account_modal').modal('hide');

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
                    ${isEdit ? "Update Account" : "Save Account"}
                `);
            }

            isSubmitting = false;
            if (typeof hideLoader === "function") hideLoader();
        }
    });
}
