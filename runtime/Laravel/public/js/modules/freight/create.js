function bindSelect2() {
  $("#item_id").select2({
    theme: "bootstrap-5",
    placeholder: "Select Item...",
    allowClear: true,
    width: "100%",
    ajax: {
      url: masterRoutes.items,
      dataType: "json",
      delay: 250,
      data: (params) => ({ q: params.term || "" }),
      processResults: (response) => ({
        results: (response.data || []).map((item) => ({
          id: item.id,
          text: item.name,
        })),
      }),
      cache: true,
    },
    minimumInputLength: 0,
  });

  $("#consignor_id").select2({
    theme: "bootstrap-5",
    placeholder: "Select Consignor...",
    allowClear: true,
    width: "100%",
    ajax: {
      url: masterRoutes.transportParties,
      dataType: "json",
      delay: 250,
      data: (params) => ({ q: params.term || "" }),
      processResults: (response) => ({
        results: (response.data || []).map((item) => ({
          id: item.id,
          text: `${item.name} (${item.city})`,
        })),
      }),
      cache: true,
    },
    minimumInputLength: 0,
  });

  $("#consignee_id").select2({
    theme: "bootstrap-5",
    placeholder: "Select Consignee...",
    allowClear: true,
    width: "100%",
    ajax: {
      url: masterRoutes.transportParties,
      dataType: "json",
      delay: 250,
      data: (params) => ({ q: params.term || "" }),
      processResults: (response) => ({
        results: (response.data || []).map((item) => ({
          id: item.id,
          text: `${item.name} (${item.city})`,
        })),
      }),
      cache: true,
    },
    minimumInputLength: 0,
  });

  $("#from_destination_id").select2({
    theme: "bootstrap-5",
    placeholder: "Select From Destination...",
    allowClear: true,
    width: "100%",
    ajax: {
      url: masterRoutes.destinations,
      dataType: "json",
      delay: 250,
      data: (params) => ({ q: params.term || "" }),
      processResults: (response) => ({
        results: (response.data || []).map((item) => ({
          id: item.id,
          text: item.name,
        })),
      }),
      cache: true,
    },
    minimumInputLength: 0,
  });

  $("#to_destination_id").select2({
    theme: "bootstrap-5",
    placeholder: "Select To Destination...",
    allowClear: true,
    width: "100%",
    ajax: {
      url: masterRoutes.destinations,
      dataType: "json",
      delay: 250,
      data: (params) => ({ q: params.term || "" }),
      processResults: (response) => ({
        results: (response.data || []).map((item) => ({
          id: item.id,
          text: item.name,
        })),
      }),
      cache: true,
    },
    minimumInputLength: 0,
  });

  const selectIdArray = ["#account_id", "#vehicle_id", "#bag_type"];
  selectIdArray.forEach((element) => {
    $(element).select2({
      theme: "bootstrap-5",
      allowClear: true,
      placeholder:
        "Select " +
        element
          .replace("#", "")
          .replace(".", "")
          .replace("_id", "")
          .replace("from_destination", "From Destination")
          .replace("to_destination", "To Destination") +
        " ...",
    });
  });

  $(document).on("select2:open", function (e) {
    const selectElement = $(e.target);
    const searchInput = selectElement
      .data("select2")
      .$dropdown.find(".select2-search__field");

    searchInput
      .off("keydown.select2Enter")
      .on("keydown.select2Enter", (event) => {
        if (event.which === 13) {
          event.preventDefault();
          selectElement.select2("close");
          moveFocusToNextField(selectElement);
        }
      });
  });
}

$(document).ready(function () {
    setTimeout(function() {
        $('#account_id').select2('focus');
    }, 100);

  bindSelect2();
  validateForm();

  if (typeof DateInput !== "undefined") {
    new DateInput("#invoice_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
  }

  // Fetch next Bill Number when Bill To (account_id) changes
  $("#account_id").on("change", function () {
    let accountId = $(this).val();
    if (accountId) {
      $.ajax({
        url: nextBillNumberUrl.replace(":id", accountId),
        type: "GET",
        success: function (response) {
          if (response.success) {
            $("#bill_number").val(response.display_number);
          }
        },
      });
    } else {
      $("#bill_number").val("");
    }
  });

  $("#grn_serial").on("blur keypress", function (e) {
    if (e.type === "keypress" && e.which !== 13) return;
    if (e.type === "keypress" && e.which === 13) e.preventDefault();

    let grnSerial = $(this).val();
    if (grnSerial) {
      $.ajax({
        url: grnDataUrl.replace(":id", grnSerial),
        type: "GET",
        data: { account_id: $("#account_id").val() },
        success: function (response) {
          if (response.success && response.data) {
            let data = response.data;

            let grnData = data.grn || data;

            // Populate form fields
            if (grnData.bag_type) {
              $("#bag_type").val(grnData.bag_type).trigger("change");
            }

            let bagCount = grnData.bag_count;
            if (bagCount) {
              $("#bag_count").val(bagCount);
            }

            let netWeight = grnData.net_weight;
            if (netWeight) {
              $("#net_weight").val(netWeight);
            }

            let lrNumber = data.lr_number;
            if (lrNumber) {
              $("#lr_number").val(lrNumber);
            }
          } else {
            if (typeof toastr !== "undefined") {
              toastr.warning(response.message || "GRN not found");
            }
          }
        },
        error: function () {
          if (typeof toastr !== "undefined") {
            toastr.error("Error fetching GRN data");
          }
        },
      });
    }
  });

  // Fetch data by LR Number
  $("#lr_number").on("blur keypress", function (e) {
    if (e.type === "keypress" && e.which !== 13) return;
    if (e.type === "keypress" && e.which === 13) e.preventDefault();

    let lrNumber = $(this).val();
    if (lrNumber) {
      $.ajax({
        url: lrNumberDataUrl.replace(":id", encodeURIComponent(lrNumber)),
        type: "GET",
        data: { account_id: $("#account_id").val() },
        success: function (response) {
          if (response.success && response.data) {
            let data = response.data;
            let grnData = data.grn || data;

            if (grnData.bag_type) {
              $("#bag_type").val(grnData.bag_type).trigger("change");
            }
            let bagCount = grnData.bag_count || data.challan_bags;
            if (bagCount) {
              $("#bag_count").val(bagCount);
            }
            let netWeight = grnData.net_weight || data.challan_weight;
            if (netWeight) {
              $("#net_weight").val(netWeight);
            }
            // Populate GRN serial from related grn
            if (grnData.grn_serial) {
              $("#grn_serial").val(grnData.grn_serial);
            }
          } else {
            if (typeof toastr !== "undefined") {
              toastr.warning(response.message || "LR Number not found");
            }
          }
        },
        error: function () {
          if (typeof toastr !== "undefined") {
            toastr.error("Error fetching LR Number data");
          }
        },
      });
    }
  });

  // Freight Calculation Logic
  function calculateFreight() {
    let netWeight = parseFloat($("#net_weight").val()) || 0;
    let kms = parseFloat($("#kms").val()) || 0;
    let rate = parseFloat($("#freight_rate").val()) || 0;

    let freightAmount = 0;

    if (netWeight > 0) {
      freightAmount = netWeight * rate;
    } else if (kms > 0) {
      freightAmount = kms * rate;
    }

    $("#total_amount").val(freightAmount.toFixed(2));
  }

  $(document).on("input", "#net_weight", function () {
    let val = parseFloat($(this).val()) || 0;
    if (val > 0) {
      $("#kms").val("0.00");
    }
    calculateFreight();
  });

  $(document).on("input", "#kms", function () {
    let val = parseFloat($(this).val()) || 0;
    if (val > 0) {
      $("#net_weight").val("0.00");
    }
    calculateFreight();
  });

  $(document).on("input", "#freight_rate", function () {
    calculateFreight();
  });
});

function validateForm() {
  let validator = new JustValidate("#freight_form", {
    validateBeforeSubmitting: true,
    focusInvalidField: true,
    errorLabelCssClass: "text-danger",
  });

  validator
    .addField("#invoice_date", [{ rule: "required" }])
    .addField("#account_id", [{ rule: "required" }])
    .addField("#consignor_id", [{ rule: "required" }])
    .addField("#consignee_id", [{ rule: "required" }])
    .addField("#to_destination_id", [{ rule: "required" }])
    .addField("#from_destination_id", [{ rule: "required" }])
    .addField("#item_id", [{ rule: "required" }])
    .addField("#vehicle_id", [{ rule: "required" }]);

  let toastShown = false;

  validator.onFail(() => {
    if (toastShown) return;
    toastShown = true;
    showToast("error", "Please fix the highlighted fields before saving.");
    setTimeout(() => (toastShown = false), 800);
  });

  validator.onSuccess((event) => {
    event.preventDefault();

    let lrNoInput = $("#lr_number");
    if (lrNoInput.hasClass("is-invalid")) {
        if (typeof Swal !== "undefined") {
            Swal.fire({
                icon: "error",
                title: "Duplicate LR Number",
                text: "Duplicate L.R. Number found. Please change it before saving.",
                showConfirmButton: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
            }).then(() => {
                lrNoInput.focus();
            });
        } else {
            lrNoInput.focus();
        }
        return;
    }

    let form = $("#freight_form");
    let formData = form.serialize();
    let submitBtn = $(".form-save-btn");
    let originalBtnHtml = submitBtn.html();
        
    $.ajax({
      url: storeFreightUrl,
      type: "POST",
      data: formData,
      headers: {
        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
      },
      beforeSend: function () {
        submitBtn
          .prop("disabled", true)
          .html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');
      },
      success: function (response) {
        if (response.success) {
          if (typeof Swal !== "undefined") {
            Swal.fire({
              icon: "success",
              title: "Success!",
              html: `Freight <span class="fw-bold">${response.data.prefix + response.data.reference_number}</span> saved successfully.`,
              showConfirmButton: true,
              allowOutsideClick: false,
              allowEscapeKey: false,
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.reload();
              }
            });
          } else {
            window.location.reload();
          }
        } else {
          submitBtn.prop("disabled", false).html(originalBtnHtml);
          if (typeof Swal !== "undefined") {
            Swal.fire({
              icon: "error",
              title: "Error!",
              text: response.message || "Failed to save data",
            });
          }
        }
      },
      error: function (xhr) {
        submitBtn.prop("disabled", false).html(originalBtnHtml);

        let errorMessage = "An error occurred. Please try again.";
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }

        if (typeof Swal !== "undefined") {
          Swal.fire({
            icon: "error",
            title: "Oops...",
            text: errorMessage,
          });
        }
        console.error(xhr.responseText);
      },
    });
  });
}
