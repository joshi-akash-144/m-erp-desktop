$(document).ready(function () {

    $('#purchase_order_number').focus();

    // date input with validation
    new DateInput(".date-format", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput(".custom-date-format", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    // check within financial year date
    $('#purchase_order_date').on('blur', function () {
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
            updateDeliveryDateFromDays();
        } else {
            updateDeliveryDateFromDays();
        }
    });

    $('#delivery_date').on('blur', function () {
        let deliveryDate = formatDateToYMD($(this).val());
        let orderDate = formatDateToYMD($('#purchase_order_date').val());
        if (deliveryDate && orderDate && deliveryDate < orderDate) {
            showToast("error", "Delivery Date must be greater than or equal to Order Date.", 5000);
            $(this).val("");
            $('#delivery_days').val("");
            return;
        }
        updateDeliveryDateFromDays();
    });

    //set due date
    $('#delivery_days').on('blur', function () {
        updateDeliveryDateFromDays();
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
    // Override Enter key on Rate to move to Save button instead of Clear button
    $('#sales_order_form').on("keydown", ".rate", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            e.stopPropagation();
            $('.form-save-btn').focus();
        }
    });

    // validate form
    validateForm();

});


let gstType = "";

function validateForm() {

   let  validator = new JustValidate("#sales_order_form", {
    validateBeforeSubmitting: true,
    focusInvalidField: true,
    errorLabelCssClass: "text-danger",
  });


      validator
    .addField("#purchase_order_number", [{ rule: "required" }])
    .addField("#purchase_order_date", [{ rule: "required" }])
    .addField("#delivery_date", [
        { rule: "required" },
        {
            validator: () => {
                let deliveryDate = formatDateToYMD($('#delivery_date').val());
                let orderDate = formatDateToYMD($('#purchase_order_date').val());
                if (!deliveryDate || !orderDate) return true;
                return deliveryDate >= orderDate;
            },
            errorMessage: "Delivery Date must be >= Order Date",
        },
    ])
    .addField("#delivery_days", [{ rule: "required" }])
    .addField("#account_id", [{ rule: "required" }]);
    
    // Validate table rows
    document.querySelectorAll(".item-row").forEach((row) => {
        let itemId = row.querySelector(".item_id");
        let qty = row.querySelector(".quantity");
        let rate = row.querySelector(".rate");
        let destination_id = row.querySelector(".destination_id");
        let condition_id = row.querySelector(".condition_id");
        
        if (itemId) validator.addField(itemId, [{ rule: "required" }]);
        if (qty) validator.addField(qty, [{ rule: "required" }, { rule: "minNumber", value: 0.001 }]);
        if (rate) validator.addField(rate, [{ rule: "required" }, { rule: "minNumber", value: 0.001 }]);
            if (destination_id) validator.addField(destination_id, [{ rule: "required" }]);
            if (condition_id) validator.addField(condition_id, [{ rule: "required" }]);
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
        submitFormAjax(document.getElementById("sales_order_form"));
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
    const selectIdArray = ['#account_id', '.item_id', '.destination_id', '.condition_id'];
    selectIdArray.forEach(element => {
        $(element).select2({
            theme: "bootstrap-5",
            allowClear: true,
            placeholder: "Select " + element.replace('#', '').replace('.', '').replace('_id', '') + " ...",
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

function updateDeliveryDateFromDays() {
    const purchaseOrderDate = $('#purchase_order_date').val();
    const deliveryDate = $('#delivery_date').val();
    const deliveryDayElm = $('#delivery_days');

    if (!purchaseOrderDate || !deliveryDate) return;

    const day = calculateDay(purchaseOrderDate, deliveryDate);

    deliveryDayElm.val(day)
}


function submitFormAjax() {
    const form = document.getElementById("sales_order_form");
    if (!form) return;

    const btn = document.querySelector("#sales_order_form .form-save-btn");
    const formMode = form.getAttribute("data-form-mode") || "create";
    const isEdit = formMode === "edit";

    let method = isEdit ? "PUT" : "POST";
    let url = form.action;

    if (isEdit) {
        const orderId = document.getElementById("sales_order_id")?.value;
        if (!orderId) {
            showToast(
                "error",
                "Order Number is required for updating a Sales Order."
            );
            return;
        }
        url = `/vouchers/sales-orders/${orderId}`;
    }

    showLoader(
        `Please wait, ${isEdit ? "updating" : "saving"} Sales Order...`
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
    ["purchase_order_date", "delivery_date"].forEach((field) => {
        if (formData.has(field)) {
            formData.set(field, formatDateToYMD(formData.get(field)));
        }
    });

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
                let html = "Sales Order <b>" + orderSerial + "</b> " + actionText + " successfully";
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