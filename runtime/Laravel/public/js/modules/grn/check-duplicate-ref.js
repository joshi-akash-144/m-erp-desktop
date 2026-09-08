function validateReferenceNumber() {
  $(document).on("keyup change", "#reference_number", function () {
    debouncedRefCheck();
  });
}

// -------------------------------
// DEBOUNCE FOR DUPLICATE CHECK
// -------------------------------
let debounceTimer = null;
function debouncedRefCheck(el) {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => checkDuplicateReference(el), 300);
}

function checkDuplicateReference() {
  $("#reference_number_loader").removeClass("d-none");

  const refNo = $("#reference_number").val().trim();

  if (!refNo.length) {
    $("#reference_number").removeClass("is-invalid");
    // $("#reference_number").removeClass("is-valid");
    $("#reference_number_loader").addClass("d-none");
    $("#ref_duplicate_link").remove();
    return;
  }

  const accountId = $("#account_id").val();

  if (!accountId) {
    $("#reference_number_loader").addClass("d-none");
    return;
  }

  $.ajax({
    url: checkDuplicateReferenceFromGrn,
    type: "GET",
    data: {
      account_id: accountId,
      reference_number: refNo,
      grn_id: $("#grn_id").val(),
    },
    success: function (response) {
      const { is_duplicate, url } = response.data;

      // Remove old link
      $("#ref_duplicate_link").remove();

      if (is_duplicate) {
        // Add error class
        $("#reference_number").addClass("is-invalid");

        // Add VIEW BILL link
        $("#reference_number").after(`
                  <div id="ref_duplicate_link" class="mt-1">
                      <a href="${url}" target="_blank" tabindex="-1" class="text-danger text-decoration-underline">
                          Duplicate Found – View Bill
                      </a>
                  </div>
              `);
      } else {
        $("#reference_number").removeClass("is-invalid");
        // $("#reference_number").addClass("is-valid");
      }
    },
    error: function (error) {
      console.warn(error);
    },
    complete: function () {
      $("#reference_number_loader").addClass("d-none");
    },
  });
}