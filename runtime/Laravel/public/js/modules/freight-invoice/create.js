// Tracks DairyImport IDs brought in via the import modal
var usedDairyImportIds = [];

$(document).ready(function () {
  // Map data to Select2 format
  const itemsData = itemMasterData.map((i) => ({ id: i.id, text: i.name }));
  const zonesData = zoneMasterData.map((z) => ({ id: z.id, text: z.name }));

  initSelect2ForAllRows(itemsData, zonesData);
  bindSelect2();

  new DateInput("#invoice_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

  // check within financial year date
  $("#invoice_date").on("blur", function () {
    let invDate = formatDateToYMD($(this).val());
    if (!invDate) return;
    if (!isWithinFY(invDate, FINANCIAL_YEAR_START, FINANCIAL_YEAR_END)) {
      showToast(
        "error",
        `Date must be within Financial Year:<br>(${formatDateToDMY(
          FINANCIAL_YEAR_START,
        )} to ${formatDateToDMY(FINANCIAL_YEAR_END)})`,
        9000,
      );
      $(this).val("");
    }
  });

  new DateInput("#from_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
  new DateInput("#to_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

  // Auto-fill narration based on from and to dates
  function updateNarration() {
    let fromDate = $("#from_date").val();
    let toDate = $("#to_date").val();
    if (fromDate || toDate) {
      $("#voucher_narration").val(`DATE: ${fromDate || ""} TO ${toDate || ""}`.replace(/\s+/g, ' ').trim());
    }
  }

  $("#from_date, #to_date").on("blur", updateNarration);

  $("#invoice_date").focus().select();

  validateForm();
});

function bindSelect2() {
  var selects = ["#account_id"];
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

  $("#account_id").on("change", function () {
    handleAccountId($(this).val());
  });
}

function handleAccountId(accountId) {
  if (!accountId) return;

  $.ajax({
    url: masterRoutes.accountDetails(accountId),
    type: "GET",
    beforeSend: function () {
      $("#account_id_loader").removeClass("d-none");
    },
    success: function (response) {
      fillAccountDetails(response.data);
      // validateGrn();
    },
    error: function () {
      showToast("error", "Failed to fetch account details", 5000);
    },
    complete: function () {
      $("#account_id_loader").addClass("d-none");
    },
  });
}

function fillAccountDetails(data) {
  var $city = $("#account_id_city");
  var $type = $("#account_id_type");
  var $kms = $("#kms");

  $city.val("");
  $type.val("");
  $kms.val("");

  if (!data) return;

  gstType = data.gst_type;
  var formatGstType = gstType === GST_TYPE.LOCAL ? "LOCAL" : "INTERSTATE";

  $city.val(data.city || "");
  $type.val(formatGstType);
  $kms.val(data.kms || 0);
}

// ── Dairy File Import Modal ───────────────────────────────────────────────────

$("#get_import_file_data").on("click", function () {
  $("#dairyFileModalTrigger").trigger("click");

  $("#dairyFileModalLoader").removeClass("d-none");
  $("#dairyFileModalNoData").addClass("d-none");
  $("#dairyFileModalTableWrapper").addClass("d-none");
  $("#dairyFileModalFooter").addClass("d-none");
  $("#dairyFileModalBody").empty();
  $("#selectAllDairyItems").prop("checked", false).prop("indeterminate", false);

  $.ajax({
    url: getDairyFileDataUrl,
    type: "GET",
    success: function (response) {
      $("#dairyFileModalLoader").addClass("d-none");

      if (!response.success || !response.data || response.data.length === 0) {
        $("#dairyFileModalNoData").removeClass("d-none");
        return;
      }

      var html = "";
      response.data.forEach(function (item, index) {
        html +=
          "<tr" +
          ' data-id="' +
          item.id +
          '">' +
          '<td class="text-center text-muted">' +
          (index + 1) +
          "</td>" +
          "<td>" +
          item.import_date +
          "</td>" +
          "<td>" +
          escapeHtml(item.product_name) +
          "</td>" +
          '<td class="text-center"><input type="checkbox" class="form-check-input dairy-item-check"></td>' +
          "</tr>";
      });

      $("#dairyFileModalBody").html(html);
      $("#dairyFileModalTableWrapper").removeClass("d-none");
      $("#dairyFileModalFooter").removeClass("d-none");
    },
    error: function (xhr) {
      $("#dairyFileModalLoader").addClass("d-none");
      var msg =
        xhr.responseJSON && xhr.responseJSON.message
          ? xhr.responseJSON.message
          : "Failed to fetch data.";
      $("#dairyFileModalNoData").find("div").text(msg);
      $("#dairyFileModalNoData").removeClass("d-none");
    },
  });
});

$("#selectAllDairyItems").on("change", function () {
  $(".dairy-item-check").prop("checked", $(this).is(":checked"));
});

$(document).on("change", ".dairy-item-check", function () {
  var total = $(".dairy-item-check").length;
  var checked = $(".dairy-item-check:checked").length;
  $("#selectAllDairyItems")
    .prop("indeterminate", checked > 0 && checked < total)
    .prop("checked", checked === total && total > 0);
});

$("#importDairyItemsBtn").on("click", function () {
  var selectedIds = [];
  $(".dairy-item-check:checked").each(function () {
    selectedIds.push($(this).closest("tr").data("id"));
  });

  if (selectedIds.length === 0) {
    showToast("error", "Please select at least one item.", 3000);
    return;
  }

  var $btn = $(this);
  $btn
    .prop("disabled", true)
    .html(
      '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Importing...',
    );

  $.ajax({
    url: getImportItemsDataUrl,
    type: "POST",
    data: {
      import_ids: selectedIds,
      _token: $('meta[name="csrf-token"]').attr("content"),
    },
    success: function (response) {
      if (!response.success || !response.data || response.data.length === 0) {
        showToast("error", "No items found for selected imports.", 3000);
        return;
      }
      fillTableFromImport(response.data);
      selectedIds.forEach(function (id) {
        if (!usedDairyImportIds.includes(id)) usedDairyImportIds.push(id);
      });
      $("#dairyFileModal")
        .find('[data-bs-dismiss="modal"]')
        .first()
        .trigger("click");
      showToast("success", response.data.length + " row(s) imported.", 3000);
    },
    error: function (xhr) {
      var msg =
        xhr.responseJSON && xhr.responseJSON.message
          ? xhr.responseJSON.message
          : "Failed to import data.";
      showToast("error", msg, 4000);
    },
    complete: function () {
      $btn
        .prop("disabled", false)
        .html('<i class="fa-solid fa-file-import me-1"></i> Import Selected');
    },
  });
});

function fillTableFromImport(items) {
  var $tableRows = $("#freight_invoice_table_body tr");
  var rowIndex = 0;

  items.forEach(function (item) {
    while (rowIndex < $tableRows.length) {
      var $tr = $tableRows.eq(rowIndex);
      var currentVal = $tr.find(".item-id").val();

      if (!currentVal || currentVal === "" || currentVal === "__NULL__") {
        setSelect2Value(
          $tr.find(".item-id"),
          item.product_id,
          item.product_name,
        );
        setSelect2Value($tr.find(".zone-id"), item.zone_id, item.zone_name);
        $tr.find(".quantity").val(parseFloat(item.quantity).toFixed(2));
        if (item.rate) {
          $tr.find(".rate").val(parseFloat(item.rate).toFixed(2));
        }
        calculateRow($tr);
        rowIndex++;
        break;
      }
      rowIndex++;
    }
  });

  calculateTotals();
}

// ── Calculations ──────────────────────────────────────────────────────────────

$(document).on(
  "input keyup change",
  "#freight_invoice_table_body .quantity, #freight_invoice_table_body .rate",
  function () {
    calculateRow($(this).closest("tr"));
    calculateTotals();
  },
);

function calculateRow($tr) {
  var qty = parseNum($tr.find(".quantity").val());
  var rate = parseNum($tr.find(".rate").val());
  var amt = qty * rate;
  $tr.find(".amount").val(amt > 0 ? amt.toFixed(2) : "");
}

function calculateTotals() {
  var totalQty = 0;
  var totalAmt = 0;

  $("#freight_invoice_table_body tr").each(function () {
    totalQty += parseNum($(this).find(".quantity").val());
    totalAmt += parseNum($(this).find(".amount").val());
  });

  $("#total_debit").val(totalQty > 0 ? totalQty.toFixed(2) : "0.00");
  $("#total_credit").val(totalAmt > 0 ? totalAmt.toFixed(2) : "0.00");
}

function parseNum(val) {
  return parseFloat(String(val || "").replace(/,/g, "")) || 0;
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function setSelect2Value($select, id, text) {
  if (!id) return;
  // Remove any stale option with this id, then re-append as pre-selected
  $select.find("option[value='" + id + "']").remove();
  var $opt = $("<option>", { value: id, text: text, selected: true });
  $select.append($opt).trigger("change");
}

function escapeHtml(str) {
  if (!str || str === "--") return str || "--";
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

// ── Clear row / Clear all ─────────────────────────────────────────────────────

$(document).on("click", ".clear-row-btn", function () {
  var $tr = $(this).closest("tr");
  Swal.fire({
    title: "Clear this row?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, clear it",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#d33",
  }).then(function (result) {
    if (result.isConfirmed) {
      clearRow($tr);
      calculateTotals();
    }
  });
});

$("#clear_all_rows_btn").on("click", function () {
  Swal.fire({
    title: "Clear all rows?",
    text: "This will reset all items, zones, quantities, rates and amounts.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, clear all",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#d33",
  }).then(function (result) {
    if (result.isConfirmed) {
      $("#freight_invoice_table_body tr").each(function () {
        clearRow($(this));
      });
      usedDairyImportIds = [];
      calculateTotals();
    }
  });
});

function clearRow($tr) {
  $tr.find(".item-id").val(null).trigger("change");
  $tr.find(".zone-id").val(null).trigger("change");
  $tr.find(".quantity, .rate, .amount").val("");
}

// ── End Dairy File Import Modal ───────────────────────────────────────────────

// ── Form Submit ───────────────────────────────────────────────────────────────

function buildPayload() {
  var items = [];
  $("#freight_invoice_table_body tr").each(function () {
    var itemId = $(this).find(".item-id").val();
    // Skip blank / empty rows — item_id is mandatory to treat row as real
    if (!itemId || itemId === "" || itemId === "__NULL__") return;

    items.push({
      item_id: itemId,
      zone_id: $(this).find(".zone-id").val() || null,
      quantity: parseNum($(this).find(".quantity").val()),
      rate: parseNum($(this).find(".rate").val()),
      amount: parseNum($(this).find(".amount").val()),
    });
  });

  return {
    _token: $('meta[name="csrf-token"]').attr("content"),
    uuid: $("#uuid").val(),
    invoice_date: formatDateToYMD($("#invoice_date").val()),
    account_id: $("#account_id").val() || null,
    from_date: formatDateToYMD($("#from_date").val()) || null,
    to_date: formatDateToYMD($("#to_date").val()) || null,
    narration: $("#voucher_narration").val() || null,
    total_quantity: parseNum($("#total_debit").val()),
    total_amount: parseNum($("#total_credit").val()),
    dairy_import_ids: usedDairyImportIds,
    items: items,
  };
}

function validatePayload(payload) {
  var errors = [];

  if (!payload.invoice_date) errors.push("Invoice date is required.");
  if (!payload.account_id) errors.push("Customer / Account is required.");
  if (!payload.from_date) errors.push("From date is required.");
  if (!payload.to_date) errors.push("To date is required.");

  if (payload.items.length === 0) {
    errors.push("Please add at least one item row.");
  } else {
    payload.items.forEach(function (item, index) {
      var row = index + 1; // 1-based row number for user display
      if (!item.zone_id) errors.push("Row " + row + ": Zone is required.");
      if (!item.quantity || item.quantity <= 0) errors.push("Row " + row + ": Quantity must be greater than 0.");
      if (!item.rate || item.rate <= 0) errors.push("Row " + row + ": Rate must be greater than 0.");
      if (!item.amount || item.amount <= 0) errors.push("Row " + row + ": Amount must be greater than 0.");
    });
  }

  return errors;
}

function submitFreightInvoice() {
  var payload = buildPayload();

  // Client-side validation before sending to server
  var validationErrors = validatePayload(payload);
  if (validationErrors.length > 0) {
    Swal.fire({
      icon: "error",
      title: "Validation Error",
      html: validationErrors.map(function (e) { return "<div>" + e + "</div>"; }).join(""),
    });
    return;
  }

  var $btn = $("#save_btn");
  var originalBtnHtml = $btn.html();
  $btn
    .prop("disabled", true)
    .html(
      '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...',
    );

  $.ajax({
    url: storeFreightInvoiceUrl,
    type: "POST",
    data: payload,
    success: function (response) {
      if (response.success) {
        var ref = response.data.invoice_number;
        Swal.fire({
          icon: "success",
          title: "Success",
          html: `<p>Freight Invoice <span class="fw-bold">${ref}</span> Created successfully.</p>`,
          confirmButtonText: "OK",
          allowOutsideClick: false,
          allowEsacpeKey: false,
        }).then(() => {
          window.location.reload();
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: response.message || "Failed to save.",
        });
      }
    },
    error: function (xhr) {
      var msg =
        xhr.responseJSON && xhr.responseJSON.message
          ? xhr.responseJSON.message
          : "Failed to save freight invoice.";
      if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
        var errors = Object.values(xhr.responseJSON.errors).flat().join("\n");
        Swal.fire({
          icon: "error",
          title: "Validation Error",
          text: errors,
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: msg,
        });
      }
    },
    complete: function () {
      $btn.prop("disabled", false).html(originalBtnHtml);
    },
  });
}

// ── Select2 Init ──────────────────────────────────────────────────────────────

function initSelect2ForAllRows(itemsData, zonesData) {
  $(".item-id")
    .select2({
      placeholder: "Select Item",
      allowClear: true,
      theme: "bootstrap-5",
      width: "100%",
      ajax: {
        transport: function (params, success) {
          let term = (params.data.term || "").toLowerCase();
          let results = itemsData.filter((i) =>
            i.text.toLowerCase().includes(term),
          );
          results.unshift({ id: "__NULL__", text: "— None —" });
          success({ results });
        },
        cache: true,
      },
    })
    .on("select2:select", function (e) {
      if (e.params.data.id === "__NULL__") {
        $(this).val("").trigger("change");
      }
    });

  $(".zone-id")
    .select2({
      placeholder: "Select Zone",
      allowClear: true,
      theme: "bootstrap-5",
      width: "100%",
      ajax: {
        transport: function (params, success) {
          let term = (params.data.term || "").toLowerCase();
          let results = zonesData.filter((z) =>
            z.text.toLowerCase().includes(term),
          );
          results.unshift({ id: "__NULL__", text: "— None —" });
          success({ results });
        },
        cache: true,
      },
    })
    .on("select2:select", function (e) {
      if (e.params.data.id === "__NULL__") {
        $(this).val("").trigger("change");
      }
    });
}

function validateForm() {
  let validator = new JustValidate("#freight_invoice_form", {
    validateBeforeSubmitting: true,
    focusInvalidField: true,
    errorLabelCssClass: "text-danger",
  });

  validator
    .addField("#invoice_date", [{ rule: "required" }])
    .addField("#account_id", [{ rule: "required" }])
    .addField("#from_date", [{ rule: "required" }])
    .addField("#to_date", [{ rule: "required" }]);

  let toastShown = false;

  validator.onFail(() => {
    if (toastShown) return;
    toastShown = true;
    showToast("error", "Please fix the highlighted fields before saving.");
    setTimeout(() => (toastShown = false), 800);
  });

  validator.onSuccess((event) => {
    event.preventDefault();
    submitFreightInvoice();
  });
}

// ----- Item Order Configuration Logic -----
$(document).on('shown.bs.modal', '#itemOrderModal', function () {
  $('#search-item-input').focus();
});
$(document).ready(function () {
    const list = document.getElementById('reorder-items-list');
    if (list) {
        const sortableInstance = new Sortable(list, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            handle: '.cursor-move'
        });

        // Search functionality
        let searchTimeout;
        $('#search-item-input').on('keyup', function() {
            clearTimeout(searchTimeout);
            const query = $(this).val().toLowerCase().trim();
            if (!query) return;

            searchTimeout = setTimeout(function() {
                $('#reorder-items-list .list-group-item').each(function() {
                    const itemName = $(this).find('strong').text().toLowerCase();
                    if (itemName.includes(query)) {
                        const el = $(this);
                        const container = $('#reorder-items-list');
                        
                        container.animate({
                            scrollTop: el.offset().top - container.offset().top + container.scrollTop() - 50
                        }, 300);

                        el.addClass('highlight-row');
                        setTimeout(() => el.removeClass('highlight-row'), 1000);
                        
                        return false; // Break out of each loop
                    }
                });
            }, 300); // 300ms debounce
        });

        let originalOrder = [];
        $('#itemOrderModal').on('shown.bs.modal', function () {
            originalOrder = sortableInstance.toArray();
        });

        $('#itemOrderModal').on('hidden.bs.modal', function () {
            if (originalOrder.length > 0) {
                sortableInstance.sort(originalOrder);
            }
            $('#search-item-input').val('');
        });

        $('#btn-save-order').on('click', function(e) {
            e.preventDefault();
            const itemIds = sortableInstance.toArray();
            showLoader("Please wait... Saving Item Order...");

            $.ajax({
                url: saveOrderUrl,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    item_ids: itemIds
                },
                success: function(response) {
                    if (response.success) {
                        originalOrder = itemIds; // Update the reference order
                        hideLoader();
                        showToast('success', response.message || 'Items reordered successfully.');
                        $('#itemOrderModal').modal('hide');
                    } else {
                        hideLoader();
                        Swal.fire({ icon: 'error', title: 'Error', text: response.message || "Failed to save order." });
                    }
                },
                error: function(xhr) {
                    hideLoader();
                    let msg = xhr.responseJSON?.message || "An error occurred while saving the order.";
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                }
            });
        });

        $('#btn-reset-order').on('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: "This will restore the original default Item order. You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, reset it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoader("Please wait... Resetting Item Order...");
                    $.ajax({
                        url: resetOrderUrl,
                        type: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                showToast('success', response.message || 'Order reset successfully.');
                                window.location.reload();
                            } else {
                                hideLoader();
                                Swal.fire({ icon: 'error', title: 'Error', text: response.message || "Failed to reset order." });
                            }
                        },
                        error: function(xhr) {
                            hideLoader();
                            let msg = xhr.responseJSON?.message || "An error occurred while resetting the order.";
                            Swal.fire({ icon: 'error', title: 'Error', text: msg });
                        }
                    });
                }
            });
        });
    }
});
