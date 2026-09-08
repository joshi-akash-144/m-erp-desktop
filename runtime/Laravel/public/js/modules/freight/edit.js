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

function populateEditData(data) {
  $("#account_id").val(data.account_id).trigger("change");
  
  if (data.invoice_date) {
    let date = new Date(data.invoice_date);
    let formatted = ("0" + date.getDate()).slice(-2) + "-" + ("0" + (date.getMonth() + 1)).slice(-2) + "-" + date.getFullYear();
    $("#invoice_date").val(formatted);
  }
  
  // $("#bill_number").val(data.invoice_number);
  $("#bill_number").val(data.prefix + data.reference_number);
  $("#grn_serial").val(data.grn_serial);
  $("#lr_number").val(data.lr_number);
  
  // vehicle options are pre-rendered in HTML, so just set the value directly
  if (data.vehicle_id) {
    $('#vehicle_id').val(data.vehicle_id).trigger('change');
  }

  if (data.from_destination) {
    let newOption = new Option(data.from_destination.name, data.from_destination.id, true, true);
    $('#from_destination_id').append(newOption).trigger('change');
  }

  if (data.to_destination) {
    let newOption = new Option(data.to_destination.name, data.to_destination.id, true, true);
    $('#to_destination_id').append(newOption).trigger('change');
  }

  if (data.consignor) {
    let newOption = new Option(data.consignor.name, data.consignor.id, true, true);
    $('#consignor_id').append(newOption).trigger('change');
  }

  if (data.consignee) {
    let newOption = new Option(data.consignee.name, data.consignee.id, true, true);
    $('#consignee_id').append(newOption).trigger('change');
  }

  if (data.items && data.items.length > 0) {
    let item = data.items[0];
    if (item.item) {
      // item_id uses AJAX Select2 — clear existing options, then inject the saved one
      $('#item_id').empty();
      let newOption = new Option(item.item.name, item.item_id, true, true);
      $('#item_id').append(newOption).trigger('change');
    }
    
    if (item.bag_type) {
      $("#bag_type").val(item.bag_type).trigger("change");
    }
    $("#bag_count").val(item.bag_count);
    $("#net_weight").val(item.net_weight);
    $("#kms").val(item.kms);
    $("#freight_rate").val(item.rate);
  }

  $("#total_amount").val(data.total_amount);
  $("#remarks").val(data.remarks);
}

$(document).ready(function () {
    setTimeout(function() {
        // $('#account_id').select2('focus');
        $('#invoice_date').focus();
    }, 100);

  bindSelect2();
  
  if (typeof FREIGHT_DATA !== 'undefined' && FREIGHT_DATA) {
      populateEditData(FREIGHT_DATA);
  }

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
      url: updateFreightUrl,
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
              html: `Freight <span class="fw-bold">${response.data.prefix + response.data.reference_number}</span> updated successfully.`,
              showConfirmButton: true,
              allowOutsideClick: false,
              allowEscapeKey: false,
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.href = listUrl;
              }
            });
          } else {
            window.location.href = listUrl;
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
