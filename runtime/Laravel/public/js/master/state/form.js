document.addEventListener("DOMContentLoaded", function () {

  const form = document.querySelector("#stateForm");
  if (!form) return;

  const mode    = form.dataset.mode || "create";
  const stateId = form.querySelector('[name="id"]')?.value || null;

  // === VALIDATION ===
  const validation = new JustValidate(form, {
    errorFieldCssClass: "is-invalid",
  });

  validation
    .addField('[name="name"]',     [{ rule: "required", errorMessage: "Name is required" }], { errorsContainer: '#name-error' })
    .addField('[name="code"]',     [{ rule: "required", errorMessage: "Code is required" }], { errorsContainer: '#code-error' })
    .addField('[name="gst_code"]', [{ rule: "required", errorMessage: "GST Code is required" }], { errorsContainer: '#gst_code-error' });

  validation.onSuccess((event) => handleSubmit(event));

  // === SUBMIT ===
  function handleSubmit(event) {
    event.preventDefault();
    const formData = new FormData(form);

    const url = mode === "edit"
      ? updateStateUrl.replace(":id", stateId)
      : createStateUrl;

    if (mode === "edit") {
      formData.append("_method", "PUT");
    }

    showLoader(mode === "edit" ? "Updating state..." : "Creating state...");

    $.ajax({
      url:         url,
      type:        "POST",
      data:        formData,
      processData: false,
      contentType: false,
      headers:     { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
      success: function (data) {
        hideLoader();
        if (data.success) {
          showToast("success", data.message);
          setTimeout(() => { window.location.href = statesIndexUrl; }, 1000);
        } else {
          showToast("error", data.message || "Error saving state");
        }
      },
      error: function (xhr) {
        hideLoader();
        if (xhr.status === 422) {
          const errors = xhr.responseJSON?.errors || {};
          Object.values(errors).forEach((err) => showToast("error", err[0]));
        } else {
          showToast("error", xhr.responseJSON?.message || "Unexpected error occurred");
        }
      },
    });
  }

  // Auto-focus first field
  document.getElementById("name")?.focus();

  // === STATUS LABEL SYNC ===
  const statusSwitch = document.querySelector("#stateStatus");
  const statusLabel  = document.querySelector("#statusLabel");
  if (statusSwitch && statusLabel) {
    const sync = () => {
      statusLabel.textContent = statusSwitch.checked ? "Active" : "Inactive";
      statusLabel.style.color = statusSwitch.checked ? "#2fb344" : "#d63939";
    };
    sync();
    statusSwitch.addEventListener("change", sync);
  }

});
