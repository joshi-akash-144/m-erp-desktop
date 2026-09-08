$(document).ready(function () {
    // Page Load first focus 
    $('#order_date').focus();

    // date input with validation
    new DateInput("#order_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    // check within financial year date
    $('#order_date').on('blur', function () {
        let orderDate = formatDateToYMD($(this).val());
        if (!orderDate) return;
        if (!isWithinFY(orderDate, FINANCIAL_YEAR_START, FINANCIAL_YEAR_END)) {
            showToast(
                "error",
                `Date must be within Financial Year:<br>(${formatDateToDMY(
                    FINANCIAL_YEAR_START
                )} to ${formatDateToDMY(FINANCIAL_YEAR_END)})`,
                9000
            );
            $(this).val("");
            //set due date
            updateDueDate();
        } else {
            //set due date
            updateDueDate();
        }
    })

    //set due date
    $('#delivery_days').on('blur', function () {
        updateDueDate();
    });

    $('#contract_number').on('blur', function () {
        checkContractUniqueness();
    });
    
    $('#broker_id').on('change', function () {
        checkContractUniqueness();
    });

    // handle account id
    $(document).on('change', '#account_id', function () {
        let accountId = $(this).val();
        if (accountId) {
            handleAccountId(accountId);
        } else {
            $("#account_id_city").val("");
            $("#account_id_type").val("");
            gstType = "";

            // clear tax info on all items and recalculate
            $(".item-row").each(function () {
                let row = $(this);
                row.find(".cgst_rate, .sgst_rate, .igst_rate").val(0);
                rateBlur(row);
            });
        }
    });

    $(document).on("change", ".item_id", function () {
        let itemId = $(this).val();
        let row = $(this).closest(".item-row");
        if (itemId) {
            handleItemId(itemId, row);
        } else {
            // Handle clearing item data
            row.find(".unit_name").val("--");
            row.find(".cgst_rate, .sgst_rate, .igst_rate").val(0);
            rateBlur(row);
        }
        calculateTotal();
        calculateTotalQty();
    });

    bindSelect2();

    // Bind the blur events to automatically execute the calculations
    $(document).on("blur", ".quantity", function () {
        qtyBlur($(this).closest(".item-row"));
        calculateTotalQty();
    });

    $(document).on("blur", ".rate", function () {
        rateBlur($(this).closest(".item-row"));
        calculateTotal();
    });

    $(document).on("blur", ".inclusive_rate", function () {
        inclusiveBlur($(this).closest(".item-row"));
        calculateTotal();
    });
    // validate form
    validateForm();

});

function checkContractUniqueness() {
    let contractNumber = $('#contract_number').val();
    let brokerId = $('#broker_id').val();
    let purchaseOrderId = $('#purchase_order_id').length ? $('#purchase_order_id').val() : null;

    if (!contractNumber || !brokerId) return;

    if (typeof checkContractUniqueUrl === 'undefined') return;

    $.ajax({
        url: checkContractUniqueUrl,
        type: 'GET',
        data: {
            contract_number: contractNumber,
            broker_id: brokerId,
            purchase_order_id: purchaseOrderId
        },
        success: function (response) {
            if (response && response.success === false) {
                showToast("error", response.message || "Contract number already exists for this broker.",9000);
                $('#contract_number').val('');
            }
        },
        error: function (xhr) {
            if (xhr.status === 422) {
                let errorMessage = "Contract number already exists for this broker.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showToast("error", errorMessage);
                $('#contract_number').val('');
            }
        }
    });
}

let gstType = "";

function validateForm() {

   let  validator = new JustValidate("#purchase_order_form", {
    validateBeforeSubmitting: true,
    focusInvalidField: true,
    errorLabelCssClass: "text-danger",
  });


      validator
    .addField("#order_date", [{ rule: "required" }])
    .addField("#delivery_days", [{ rule: "required" }])
    .addField("#account_id", [{ rule: "required" }])
    .addField("#broker_id", [{ rule: "required" }])
    .addField("#destination_id", [{ rule: "required" }]);
    
    // Validate table rows
    document.querySelectorAll(".item-row").forEach((row) => {
        let itemId = row.querySelector(".item_id");
        let qty = row.querySelector(".quantity");
        let rate = row.querySelector(".rate");
        
        if (itemId) validator.addField(itemId, [{ rule: "required" }]);
        if (qty) validator.addField(qty, [{ rule: "required" }, { rule: "minNumber", value: 0.001 }]);
        if (rate) validator.addField(rate, [{ rule: "required" }, { rule: "minNumber", value: 0.001 }]);
    });
    

    let toastShown = false;
    
      validator.onFail(() => {
        if (toastShown) return;
        toastShown = true;
        showToast("error", "Please fix the highlighted fields before saving.");
        setTimeout(() => (toastShown = false), 800);
      });
    
      validator.onSuccess((event) => {
        event.preventDefault();
        submitFormAjax(document.getElementById("purchase_order_form"));
      });

}

function handleAccountId(accountId) {
    if (!accountId) return;

    $.ajax({
        url: masterRoutes.accountDetails(accountId),
        type: "GET",
        beforeSend: function () {
            $('#account_id_loader').removeClass('d-none');
        },
        success: function (response) {
            fillAccountDetails(response.data);
        },
        error: function (error) {
            console.warn(error);
            showToast("error", "Failed to fetch account details", 5000);
        },
        complete: function () {
            $('#account_id_loader').addClass('d-none');
        }
    });
}

function handleItemId(itemId, row) {
    if (!itemId) return;

    $.ajax({
        url: masterRoutes.itemDetails(itemId),
        type: "GET",
        beforeSend: function () {
            $('#item_id_loader').removeClass('d-none');
        },
        success: function (response) {
            fillItemDetails(response.data, row);
        },
        error: function (error) {
            console.warn(error);
            showToast("error", "Failed to fetch item details", 5000);
        },
        complete: function () {
            $('#item_id_loader').addClass('d-none');
        }
    });
}

function fillAccountDetails(data) {
    const cityEl = $("#account_id_city");
    const typeEl = $("#account_id_type");
    cityEl.val("");
    typeEl.val("");

    if (!data) return;

    gstType = data.gst_type;
    let formatGstType = gstType === GST_TYPE.LOCAL ? "LOCAL" : "INTERSTATE";

    cityEl.val(data.city ?? "");
    typeEl.val(formatGstType);

    // get all row of item
    const allRows = $(".item-row");
    allRows.each(function () {
        const currentRow = $(this);

        currentRow.find(".item_id").trigger("change");
    });
}

function fillItemDetails(data, row) {

    let cgstElm = row.find(".cgst_rate");
    let sgstElm = row.find(".sgst_rate");
    let igstElm = row.find(".igst_rate");
    let unitElm = row.find(".unit_name");

    if (!data) return;
    unitElm.val(data.unit_name ?? "");
    if (!gstType) return;

    if (gstType === GST_TYPE.LOCAL) {
        cgstElm.val(data.cgst ?? "");
        sgstElm.val(data.sgst ?? "");
        igstElm.val(0);
    } else {
        cgstElm.val(0);
        sgstElm.val(0);
        igstElm.val(data.igst ?? "");
    }

    // Automatically recalculate row values based on new GST 
    rateBlur(row);
}

function bindSelect2() {
    const selectIdArray = ['#broker_id', '#account_id', '#destination_id', '.item_id', '.condition_id'];
    selectIdArray.forEach(element => {
        $(element).select2({
            theme: "bootstrap-5",
            allowClear: true,
            placeholder: "Select " + element.replace('#', '').replace('.', '').replace('_id', '') + " ...",
            width: '100%',
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

function updateDueDate() {
    const orderDate = document.getElementById("order_date")?.value;
    const deliveryDays = document.getElementById("delivery_days")?.value;
    const dueDateEl = document.getElementById("due_date");
    if (!dueDateEl) return;

    dueDateEl.value =
        orderDate && deliveryDays ? calculateDueDate(orderDate, deliveryDays) : "";
}


function submitFormAjax() {
    const form = document.getElementById("purchase_order_form");
    if (!form) return;

    const btn = document.querySelector("#purchase_order_form .form-save-btn");
    const formMode = form.getAttribute("data-form-mode") || "create";
    const isEdit = formMode === "edit";

    let method = isEdit ? "PUT" : "POST";
    let url = form.action;

    if (isEdit) {
        const orderId = document.getElementById("purchase_order_id")?.value;
        if (!orderId) {
            showToast(
                "error",
                "Order Number is required for updating a Purchase Order."
            );
            return;
        }
        url = updatePurchaseOrderUrl.replace(':id', orderId);
    }

    showLoader(
        `Please wait, ${isEdit ? "updating" : "saving"} Purchase Order...`
    );

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `
      <span class="spinner-border spinner-border-sm me-2" role="status"></span>
      ${isEdit ? "Updating" : "Saving"} <span class="animated-dots"></span>
    `;
    }

    // ✅ Build FormData and modify date fields
    const formData = new FormData(form);

    // PHP cannot read multipart/form-data on true PUT requests.
    // Use Laravel method spoofing: send POST + _method=PUT
    if (isEdit) {
        formData.append("_method", "PUT");
    }

    // Convert dates to YMD format before sending
    ["order_date", "due_date"].forEach((field) => {
        if (formData.has(field)) {
            formData.set(field, formatDateToYMD(formData.get(field)));
        }
    });

    // due_date is disabled in the form, so FormData skips it — inject manually
    const dueDateEl = document.getElementById("due_date");
    if (dueDateEl && dueDateEl.value) {
        formData.set("due_date", formatDateToYMD(dueDateEl.value));
    }

    $.ajax({
        url: url,
        type: "POST",   // Always POST — Laravel reads _method for spoofing
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function () {
            let loadingText = isEdit ? 'Updating' : 'Saving';
            $('#submit_btn, .form-save-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> ' + loadingText + ' <span class="animated-dots"></span>');
        },
        success: function (response) {
            if (response.success) {
                let actionText = isEdit ? "updated" : "created";
                let orderSerial = response.data?.order_serial ?? "";
                let html = "Purchase Order <span class='fw-bold text-danger'>" + orderSerial + "</span> " + actionText + " successfully";
                Swal.fire({
                    title: "Success!",
                    html: html,
                    icon: "success",
                    confirmButtonText: "OK",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                }).then((result) => {
                    if (result.isConfirmed) {
                        location.reload();
                    }
                });

            } else {
                showToast("error", response.message || "Failed to process request", 5000);
            }
        },
        error: function (xhr) {
            console.warn(xhr);
            if (typeof handleAjaxError === 'function') {
                handleAjaxError(xhr);
            } else {
                let errorMessage = "Failed to submit form";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showToast("error", errorMessage, 5000);
            }
        },
        complete: function () {
            let defaultText = isEdit ? '<i class="fa-solid fa-pencil me-1"></i> Update' : '<i class="fa-solid fa-floppy-disk me-1"></i> Save';
            $('#submit_btn, .form-save-btn').prop('disabled', false).html(defaultText);
            hideLoader();
        }
    });
}