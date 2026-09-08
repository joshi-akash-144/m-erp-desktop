$(document).ready(function () {

  bindSelect2();
  
  // Format username in real-time: lowercase, no spaces, letters, numbers, and underscores only
  $(document).on('input', 'input[name="username"]', function () {
    let value = $(this).val();
    value = value.toLowerCase();
    value = value.replace(/\s+/g, '_');
    value = value.replace(/[^a-z0-9_]/g, '');
    $(this).val(value);
  });

  const $form = $("#userForm");
  if (!$form.length) return;

  const mode = $form.data("mode") || "create";
  const userId = $form.find('[name="uuid"]').val() || null;

  // =========================
  // VALIDATION
  // =========================
  const validation = new JustValidate("#userForm", {
    errorFieldCssClass: "is-invalid",
  });

  validation
    .addField('[name="name"]', [
      { rule: "required", errorMessage: "Name is required" },
    ])
    .addField('[name="username"]', [
      { rule: "required", errorMessage: "Username is required" },
      {
        rule: "customRegexp",
        value: /^[a-z0-9_]+$/,
        errorMessage: "The username may only contain (a-z, 0-9, _) characters.",
      }
    ])
    .addField('[name="email"]', [
      { rule: "required", errorMessage: "Email is required" },
      { rule: "email", errorMessage: "Invalid email address" },
    ]);

  // Only validate password in create mode
  if (mode === "create") {
    validation.addField('[name="password"]', [
      { rule: "required", errorMessage: "Password is required" },
      { rule: "minLength", value: 8, errorMessage: "Password must be at least 8 characters" },
    ])
    .addField('[name="role_id"]', [
      { rule: "required", errorMessage: "Role is required" },
    ]);
  }

  validation.onSuccess(function (event) {
    handleSubmit(event);
  });

  // =========================
  // SUBMIT HANDLER
  // =========================
  function handleSubmit(event) {
    event.preventDefault();

    const formEl = event.target;
    const formData = new FormData(formEl);

    // Backend requires a UUID even for new users
    if (!formData.get("uuid")) {
      formData.append("uuid", crypto.randomUUID());
    }

    // Determine target URL
    const url = mode === "edit" ? updateUserUrl : createUserUrl;

    if (mode === "edit") {
      formData.append("_method", "PUT");
    }

    showLoader(mode === "edit" ? "Updating user..." : "Creating user...");

    $.ajax({
      url: url,
      method: "POST", // Standard way to handle FormData
      data: formData,
      processData: false,
      contentType: false,
      success: function (data) {
        hideLoader();

console.log(data);

        if (data.success) {
          // Swal.fire({
          //   icon: "success",
          //   title: "Success",
          //   text: data.message,
          //   timer: 1500,
          //   showConfirmButton: false
          // });
          showToast("success", data.message);
        return;
          setTimeout(function () {
            window.location.href = usersIndexUrl;
          }, 1500);
        } else {
          // Swal.fire({
          //   icon: "error",
          //   title: "Error",
          //   text: data.message || "Error saving user"
          // });
          showToast("error", data.message || "Error saving user");
        }
      },
      error: function (xhr) {
        hideLoader();
        // handleAjaxError is defined globally in main.js
        if (typeof handleAjaxError === "function") {
          handleAjaxError(xhr);
        } else {
          // Swal.fire({
          //   icon: "error",
          //   title: "Error",
          //   text: "Something went wrong!"
          // });
          showToast("error", "Something went wrong!");
        }
      }
    });
  }

  // =========================
  // CLOSE BUTTON
  // =========================
  $form.find('button[type="close"]').on("click", function (e) {
    e.preventDefault();
    window.location.href = usersIndexUrl;
  });

  // =========================
  // BUTTON TEXT UPDATE
  // =========================
  const $submitBtn = $("#submitBtn");

  if ($submitBtn.length) {
    const btnHTML =
      mode === "edit"
        ? `<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-square-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
             <path stroke="none" d="M0 0h24v24H0z" fill="none" />
             <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
             <path d="M9 12l2 2l4 -4" />
           </svg> Update`
        : `<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-square-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
             <path stroke="none" d="M0 0h24v24H0z" fill="none" />
             <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
             <path d="M9 12l2 2l4 -4" />
           </svg> Save`;

    $submitBtn.html(btnHTML);
  }

  // =========================
  // STATUS SWITCH
  // =========================
  const $statusSwitch = $("#userStatus");
  const $statusLabel = $("#statusLabel");

  if ($statusSwitch.length && $statusLabel.length) {

    function updateStatusLabel() {
      if ($statusSwitch.is(":checked")) {
        $statusLabel.text("Active").css("color", "green");
      } else {
        $statusLabel.text("Inactive").css("color", "red");
      }
    }

    updateStatusLabel();
    $statusSwitch.on("change", updateStatusLabel);
  }

});
function bindSelect2() {
    const selectIdArray = [
        '#role_id'
    ];

    selectIdArray.forEach(function (id) {
        $(id).select2({
            theme: 'bootstrap-5',
            allowClear: true,
            placeholder: 'Select ...',
            width: $(id).attr('style') && $(id).attr('style').includes('width') ? 'element' : '100%'
        });
    });

    // Handle Enter-key on Select2 search fields
    // Use namespaced event and .off() to prevent duplicate listeners
    $(document).off('select2:open.manual_focus').on('select2:open.manual_focus', function (e) {
        const selectElement = $(e.target);
        const searchInput = selectElement.data('select2').$dropdown.find('.select2-search__field');

        searchInput.off('keydown.select2Enter').on('keydown.select2Enter', function (event) {
            if (event.which === 13) {
                event.preventDefault();
                selectElement.select2('close');
                moveFocusToNextField(selectElement);
            }
        });
    });
}