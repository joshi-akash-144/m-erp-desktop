Dropzone.autoDiscover = false;
let myDropzone = null;

$(document).ready(function () {
  // Initialize Select2 for customer selection
  registerSelect2();

  // Global event listeners
  registerGlobalEventListeners();

  // Initialize Dropzone for file uploads
  const el = document.querySelector("#dropzone-default");
  if (el) {
    myDropzone = new Dropzone(el, {
      url: "#",
      autoProcessQueue: false,
      paramName: "excel_import_file",
      maxFiles: 1,
      acceptedFiles: ".xlsx,.xls,.csv",
      addRemoveLinks: true,
      init: function() {
        this.on("addedfile", function() {
          if (this.files.length > 1) {
            this.removeFile(this.files[0]); // Keep only latest
          }
        });
      }
    });
  }

  // Validate and handle form submission
  validateForm();
});

// GLOBAL EVENTS
function registerGlobalEventListeners() {
  $(document).on("change", "#account_id", function () {
    fetchAccountDetails($(this).val());
  });

  $("#CancelImport").click(function () {
    window.location.reload();
  });

  $("#account_id").focus();
}

// FETCH ACCOUNT DETAILS
function fetchAccountDetails(accountId) {
  if (!accountId) {
    $("#account_id_city").val("");
    $("#account_id_type").val("");
    return;
  }

  $("#account_id_loader").removeClass("d-none");

  $.ajax({
    url: masterRoutes.accountDetails(accountId),
    type: "GET",
    success: function (response) {
      const data = response.data || {};
      $("#account_id_city").val(data.city || "");
      const formatGstType = data.gst_type === GST_TYPE.LOCAL ? "LOCAL" : "INTERSTATE";
      $("#account_id_type").val(formatGstType);
    },
    error: function () {
      showToast("error", "Failed to load customer details");
    },
    complete: function () {
      $("#account_id_loader").addClass("d-none");
    },
  });
}

// SELECT2 REGISTRATION
function registerSelect2() {
  $("#account_id").select2({
    theme: "bootstrap-5",
    // placeholder: "Select Customer...",
    // allowClear: true,
    // closeOnSelect: true,
    // width: "100%",
    // dropdownParent: $("#account_id").parent(),
    // ajax: {
    //   url: masterRoutes.parties,
    //   dataType: "json",
    //   delay: 250,
    //   data: function (params) {
    //     return { q: params.term || "" };
    //   },
    //   processResults: function (response) {
    //     var items = response.data || [];
    //     var resultsArray = items.map(function (item) {
    //       return {
    //         id: item.id,
    //         text: item.name,
    //       };
    //     });
    //     return {
    //       results: resultsArray,
    //     };
    //   },
    //   cache: true,
    // },
    // minimumInputLength: 0,
  });

  $("#account_id").on("select2:open", function () {
    var searchInput = $(this)
      .data("select2")
      .$dropdown.find(".select2-search__field");

    searchInput.on("keydown", function (event) {
      if (event.which === 13) {
        let currentField = $("#account_id");
        currentField.select2("close");
        moveFocusToNextField(currentField);
      }
    });
  });
}

// FORM VALIDATION
function validateForm() {
  if (!document.getElementById("multi_grn_form")) return;

  const validator = new JustValidate("#multi_grn_form", {
    validateBeforeSubmitting: true,
    focusInvalidField: true,
    errorLabelCssClass: "text-danger",
  });

  validator.addField("#account_id", [{ rule: "required" }]);

  let toastShown = false;
  validator.onFail(function () {
    if (toastShown) return;
    toastShown = true;
    showToast("error", "Please select a Customer Name before saving.");
    setTimeout(function () {
      toastShown = false;
    }, 800);
  });

  validator.onSuccess(function (event) {
    event.preventDefault();
    submitFormAjax();
  });
}

// FORM SUBMIT AJAX
function submitFormAjax() {
  const form = document.getElementById("multi_grn_form");
  if (!form) return;

  const btn = document.querySelector("#multi_grn_form .form-save-btn");
  const formMode = form.getAttribute("data-form-mode") || "create";
  const isEdit = formMode === "edit";

  const url = form.action;

  const formData = new FormData(form);

  // Check and append Dropzone file
  if (myDropzone && myDropzone.files.length > 0) {
    formData.append("excel_import_file", myDropzone.files[0]);
  } else {
    showToast("error", "Please select a valid Excel/CSV file before saving.");
    return;
  }

  $.ajax({
    url: url,
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    beforeSend: function () {
      showLoader("Please wait, saving Multi GRN...");
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = `
          <span class="spinner-border spinner-border-sm me-2" role="status"></span>
          Saving <span class="animated-dots"></span>
        `;
      }
    },
    success: function (response) {
      if (response.success) {
        if (response.data && response.data.html) {
          const printWin = window.open("", "_blank");
          printWin.document.open();
          printWin.document.write(response.data.html);
          printWin.document.close();
        }
      } else {
        showToast("error", response.message || "Failed to process file.");
      }
    },
    error: function (xhr) {
      const errMsg = xhr.responseJSON?.message || "Something went wrong while saving the Multi GRN.";
      showToast("error", errMsg);
    },
    complete: function () {
      hideLoader();
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = `<i class="fa-solid fa-floppy-disk me-1"></i> Save`;
      }
    },
  });
}
