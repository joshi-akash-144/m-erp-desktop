$(function () {
  // select2 — file type
  $("#file_type").select2({
    placeholder: "Select File Type",
    allowClear: true,
  });

  // select2 — zone (multi)
  $("#zone_ids").select2({
    placeholder: "Select Zone",
    allowClear: true,
    multiple: true,
  });

  bindSelect2();

  new DateInput("#import_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

  // Drag & drop
  const dropZone = document.getElementById("drop_zone");
  const fileInput = document.getElementById("import_file");
  const selectedName = document.getElementById("selected_file_name");

  dropZone.addEventListener("click", () => fileInput.click());

  dropZone.addEventListener("dragover", (e) => {
    e.preventDefault();
    dropZone.classList.add("dragover");
  });

  dropZone.addEventListener("dragleave", () => {
    dropZone.classList.remove("dragover");
  });

  dropZone.addEventListener("drop", (e) => {
    e.preventDefault();
    dropZone.classList.remove("dragover");
    const files = e.dataTransfer.files;
    if (files.length) {
      fileInput.files = files;
      showFileName(files[0].name);
    }
  });

  fileInput.addEventListener("change", () => {
    if (fileInput.files.length) {
      showFileName(fileInput.files[0].name);
    }
  });

  function showFileName(name) {
    selectedName.textContent = name;
    selectedName.classList.remove("d-none");
    dropZone.querySelector(".drop-text").classList.add("d-none");
  }

  // Cancel — reset form
  $("#cancel_btn").on("click", function () {
    $("#dairy_import_form")[0].reset();
    $("#file_type").val(null).trigger("change");
    $("#zone_ids").val(null).trigger("change");
    selectedName.classList.add("d-none");
    dropZone.querySelector(".drop-text").classList.remove("d-none");
  });

  // Refresh
  $("#refresh_btn").on("click", function () {
    location.reload();
  });

  // Download sample
  $("#download_sample_btn").on("click", function () {
    const fileType = $("#file_type").val();

    if (!fileType) {
      showToast(
        "warning",
        "Please select a File Type before downloading the sample.",
      );
      return;
    }

    const typeMap = { dairy_file: 1, day_to_day: 2 };
    const type = typeMap[fileType];

    window.location.href =
      dairyFileImportRoutes.downloadSample + "?type=" + type;
  });

  // Form submit
  $("#dairy_import_form").on("submit", function (e) {
    e.preventDefault();

    const fileType = $("#file_type").val();
    const date = $("#import_date").val();
    const file = $("#import_file")[0].files[0];

    if (!fileType) {
      showToast("warning", "Please select a File Type.");
      return;
    }
    if (!date) {
      showToast("warning", "Please select a Date.");
      return;
    }
    if (!file) {
      showToast("warning", "Please select a file to import.");
      return;
    }

    const formData = new FormData(this);
    formData.set("import_date", formatDateToYMD(date));

    $.ajax({
      url: dairyFileImportRoutes.store,
      method: "POST",
      data: formData,
      processData: false,
      contentType: false,
      beforeSend: function () {
        hideErrorSection();
        $("#import_btn")
          .prop("disabled", true)
          .html(
            '<span class="spinner-border spinner-border-sm me-1"></span> Importing...',
          );
      },
      success: function (res) {
        const imported = res.imported ?? 0;

        let html = '<div class="text-start fs-6">';
        html += `<p class="mb-0"><i class="fa-solid fa-circle-check text-success me-1"></i><strong>${imported}</strong> row(s) imported successfully.</p>`;
        html += '</div>';

        Swal.fire({
          title: 'Import Complete',
          html: html,
          icon: 'success',
          confirmButtonText: 'OK',
          confirmButtonColor: '#0054a6',
        }).then(function () {
          // Keep file type, zone(s) and date filled in — only clear the file
          // picker so the user can pick and upload another file right away.
          $("#import_file").val("");
          selectedName.classList.add("d-none");
          dropZone.querySelector(".drop-text").classList.remove("d-none");
        });
      },
      error: function (xhr) {
        const res = xhr.responseJSON ?? {};
        const msg = res.message ?? "Import failed. Please try again.";

        // Laravel validation errors (422 with `errors` object)
        if (xhr.status === 422 && res.errors && typeof res.errors === "object" && !Array.isArray(res.errors)) {
          const firstError = Object.values(res.errors)[0];
          showToast("error", Array.isArray(firstError) ? firstError[0] : firstError);
          return;
        }

        // Row-level errors — show error table
        if (res.errors_html) {
          showErrorSection(res.skipped, res.errors_html);
          showToast("error", msg);
          return;
        }

        // Same product already imported for this date — warn, don't touch the form/page
        if (res.duplicate) {
          Swal.fire({
            title: 'Already Imported',
            text: msg,
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#0054a6',
          });
          return;
        }

        // No data rows found in the file — Swal with context
        Swal.fire({
          title: 'Nothing Imported',
          text: msg,
          icon: 'info',
          confirmButtonText: 'OK',
          confirmButtonColor: '#0054a6',
        });
      },
      complete: function () {
        $("#import_btn")
          .prop("disabled", false)
          .html('<i class="fa-solid fa-file-import me-1"></i> Import');
      },
    });

    
  });

  // Close error panel
  $("#close_error_section").on("click", hideErrorSection);

  function showErrorSection(count, html) {
    $("#error_row_count").text(count);
    $("#import_error_table").html(html);
    $("#import_error_section").show();
    $("#import_error_section")[0].scrollIntoView({ behavior: "smooth", block: "start" });
  }

  function hideErrorSection() {
    $("#import_error_section").hide();
    $("#import_error_table").html("");
  }

  function bindSelect2() {
      var selects = [
        "#file_type",
      ];
      selects.forEach(function (el) {
        $(el).select2({ theme: "bootstrap-5" });
      });

      $(document).on("select2:open", function (e) {
        var $select = $(e.target);
        var $search = $select
          .data("select2")
          .$dropdown.find(".select2-search__field");
        $search
          .off("keydown.select2Enter")
          .on("keydown.select2Enter", function (event) {
            if (event.key === "Enter") {
              event.preventDefault();
              $select.select2("close");
              moveFocusToNextField($select);
            }
          });
      });

    }
});
