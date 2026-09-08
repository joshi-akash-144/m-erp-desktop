
document.addEventListener("DOMContentLoaded", function () {

  const form = document.querySelector("#roleForm");
  if (!form) return;

  const mode = form.dataset.mode || "create";
  const roleId = form.querySelector('[name="id"]')?.value || null;

  // === VALIDATION SETUP ===
  const validation = new JustValidate(form, {
    errorFieldCssClass: "is-invalid",
    errorLabelCssClass: "text-danger",
    errorLabelStyle: { fontSize: "12px" },
    focusInvalidField: false,
  });

  validation
    .addField('[name="name"]', [
      { rule: "required", errorMessage: "Name is required" },
    ], { errorsContainer: '#name-error' });

  validation.onSuccess((event) => handleSubmit(event));

  // === HANDLE FORM SUBMIT ===
  function handleSubmit(event) {

    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    // Ensure ID exists
    if (!formData.get("id")) {
      formData.append("id", crypto.randomUUID());
    }

    // Determine correct URL
    const url = mode === "edit" ? updateRole.replace(':id', roleId) : createRole;

    // Laravel expects PUT for updates
    if (mode === "edit") {
      formData.append("_method", "PUT");
    }

    showLoader(mode === "edit" ? "Updating role..." : "Creating role...");

    $.ajax({
      url: url,
      type: 'POST',
      data: formData,
      processData: false, // Required for FormData
      contentType: false, // Required for FormData
      headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      beforeSend: function() {
          // Optional: showLoader() could go here
      },
      success: function(data) {
          hideLoader();

          if (data.success) {
              showToast( "success", data.message);

              // Redirect or refresh list
              setTimeout(() => {
                  window.location.href = rolesListUrl;
              }, 1000);
          } else {
              showToast( "error", data.message || "Error saving role");
          }
      },
      error: function(xhr) {
          hideLoader();

          // Handle Laravel validation errors (422)
          if (xhr.status === 422) {
              const errors = xhr.responseJSON.errors;
              Object.values(errors).forEach((err) => showToast( "error", err[0]));
          } else {
              const message = xhr.responseJSON?.message || "Unexpected error occurred";
              showToast( "error", message);
          }
      }
    });
  }
  document.getElementById("name").focus();
  // === CLOSE BUTTON HANDLER ===
  const closeBtn = form.querySelector('button[type="close"]');
  if (closeBtn) {
    closeBtn.addEventListener("click", function (e) {
      e.preventDefault();
      window.location.href = rolesListUrl;
    });
  }

  // === UPDATE BUTTON TEXT DYNAMICALLY ===
  const submitBtn = form.querySelector('button[type="submit"]');
  if (submitBtn) {
    submitBtn.innerHTML =
      mode === "edit"
        ? `<svg xmlns="http://www.w3.org/2000/svg" 
              class="icon icon-tabler icon-tabler-square-check" 
              width="24" height="24" viewBox="0 0 24 24" 
              stroke-width="2" stroke="currentColor" fill="none" 
              stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
              <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
              <path d="M9 12l2 2l4 -4" />
            </svg> Update`
        : `<svg xmlns="http://www.w3.org/2000/svg" 
              class="icon icon-tabler icon-tabler-square-check" 
              width="24" height="24" viewBox="0 0 24 24" 
              stroke-width="2" stroke="currentColor" fill="none" 
              stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
              <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
              <path d="M9 12l2 2l4 -4" />
            </svg> Save`;
  }

  // === STATUS SWITCH HANDLER ===
  const statusSwitch = document.querySelector("#roleStatus");
  const statusLabel = document.querySelector("#statusLabel");

  if (statusSwitch && statusLabel) {
    const updateStatusLabel = () => {
      statusLabel.textContent = statusSwitch.checked ? "Active" : "Inactive";
      statusLabel.style.color = statusSwitch.checked ? "green" : "red";
    };

    // Set initial label & listen for toggle
    updateStatusLabel();
    statusSwitch.addEventListener("change", updateStatusLabel);
  }

  //////
  // === PERMISSIONS: Select All & Module Sync ===
const selectAllCheckbox = document.querySelector("#selectAll");
const moduleCheckboxes = document.querySelectorAll(".row-checkbox");
const permissionCheckboxes = document.querySelectorAll(".permission-checkbox");

// Handle "Select All"
if (selectAllCheckbox) {
  selectAllCheckbox.addEventListener("change", () => {
    const checked = selectAllCheckbox.checked;
    moduleCheckboxes.forEach((mod) => (mod.checked = checked));
    permissionCheckboxes.forEach((perm) => (perm.checked = checked));
  });
}

// Handle each module checkbox (toggle only its permissions)
moduleCheckboxes.forEach((moduleCheckbox) => {
  moduleCheckbox.addEventListener("change", () => {
    const module = moduleCheckbox.dataset.module;
    const perms = document.querySelectorAll(
      `.permission-checkbox[data-module="${module}"]`
    );
    perms.forEach((perm) => (perm.checked = moduleCheckbox.checked));

    updateSelectAllState(); // keep global checkbox synced
  });
});

// Handle permission checkbox clicks — keep module checkbox & select-all synced
permissionCheckboxes.forEach((permCheckbox) => {
  permCheckbox.addEventListener("change", () => {
    const module = permCheckbox.dataset.module;
    const moduleCheckbox = document.querySelector(
      `.row-checkbox[data-module="${module}"]`
    );
    const modulePerms = document.querySelectorAll(
      `.permission-checkbox[data-module="${module}"]`
    );

    const allChecked = [...modulePerms].every((p) => p.checked);
    const noneChecked = [...modulePerms].every((p) => !p.checked);

    // Update module checkbox state
    if (moduleCheckbox) {
        moduleCheckbox.checked = allChecked;
        moduleCheckbox.indeterminate = !allChecked && !noneChecked;
    }

    updateSelectAllState();
  });
});

// === Helper to update individual module checkbox state ===
function updateModuleState(module) {
    const moduleCheckbox = document.querySelector(`.row-checkbox[data-module="${module}"]`);
    const modulePerms = document.querySelectorAll(`.permission-checkbox[data-module="${module}"]`);
    
    if (moduleCheckbox && modulePerms.length > 0) {
        const allChecked = [...modulePerms].every((p) => p.checked);
        const noneChecked = [...modulePerms].every((p) => !p.checked);
        moduleCheckbox.checked = allChecked;
        moduleCheckbox.indeterminate = !allChecked && !noneChecked;
    }
}

// === Helper to update global "Select All" checkbox ===
function updateSelectAllState() {
  const allChecked = [...permissionCheckboxes].every((p) => p.checked);
  const noneChecked = [...permissionCheckboxes].every((p) => !p.checked);
  if (selectAllCheckbox) {
      selectAllCheckbox.checked = allChecked;
      selectAllCheckbox.indeterminate = !allChecked && !noneChecked;
  }
}

// Initialize correct state on load
moduleCheckboxes.forEach((mod) => updateModuleState(mod.dataset.module));
updateSelectAllState();

});
