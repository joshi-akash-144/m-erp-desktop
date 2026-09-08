$(document).ready(function () {
    $('#grn_date').focus();
    
    // date input with validation
    new DateInput("#grn_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#grn_in_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#party_bill_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    bindSelect2();
    initVehicleRegValidation(".txtRegNo");
    validateReferenceNumber();
    validateForm();

    // handle account id
    $(document).on('change', '#account_id', function () {
        let accountId = $(this).val();
        if (accountId) {
            handleAccountId(accountId);
        } else {
            $("#city").val("");
        }
    });
});

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
function bindSelect2() {
    const selectIdArray = ['#account_id', '#destination_id', '#item_id'];
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

function fillAccountDetails(data) {
    const cityEl = $("#city");
    cityEl.val("");
    
    if (!data) return;

    cityEl.val(data.city ?? "");

    // get all row of item
    const allRows = $(".item-row");
    allRows.each(function () {
        const currentRow = $(this);

        currentRow.find(".item_id").trigger("change");
    });
}
function validateForm() {
    let validator = new JustValidate("#mobile_grn_form", {
        validateBeforeSubmitting: true,
        focusInvalidField: true,
        errorLabelCssClass: "text-danger",
    });

    validator
        .addField("#grn_date", [{ rule: "required" }])
        .addField("#grn_in_date", [{ rule: "required" }])
        .addField("#account_id", [{ rule: "required" }])
        .addField("#reference_number", [{ rule: "required" }])
        .addField("#item_id", [{ rule: "required" }])
        .addField("#p_qty", [{ rule: "required" }])
        .addField("#party_bill_date", [{ rule: "required" }])
        .addField("#vehicle_number", [{ rule: "required" }])
        .addField("#rate", [{ rule: "required" }]);

    let toastShown = false;

    validator.onFail(() => {
        if (toastShown) return;
        toastShown = true;
        showToast("error", "Please fix the highlighted fields before saving.");
        setTimeout(() => (toastShown = false), 800);
    });

    validator.onSuccess((event) => {
        event.preventDefault();
        submitFormAjax(document.getElementById("mobile_grn_form"));
    });
}

function submitFormAjax(form) {
    const formData = new FormData(form);
    const isEdit = (form.dataset.formMode === 'edit');

    // Convert flat fields to array format required by StoreGrnRequest
    const itemId = formData.get('item_id');
    const destinationId = formData.get('destination_id');
    const rate = formData.get('rate');
    const partyQty = formData.get('party_qty');

    if (itemId) formData.append('items[0][item_id]', itemId);
    if (destinationId) formData.append('items[0][destination_id]', destinationId);
    if (rate) formData.append('items[0][rate]', rate);
    if (partyQty) formData.append('items[0][party_quantity]', partyQty);
    
    // Add default bag_type as it is required by StoreGrnRequest
    formData.append('bag_type', 'gunny');

    // Convert dates to YMD format before sending
    ["grn_date", "grn_in_date", "grn_out_date", "party_bill_date"].forEach((field) => {
        if (formData.has(field) && formData.get(field)) {
            formData.set(field, formatDateToYMD(formData.get(field)));
        }
    });

    // Custom validation: out date must be ≥ in date
    if (formData.has("grn_out_date") && formData.get("grn_out_date") && formData.has("grn_in_date") && formData.get("grn_in_date")) {
        const grnOutDate = new Date(formData.get("grn_out_date"));
        const grnInDate = new Date(formData.get("grn_in_date"));
        if (grnOutDate < grnInDate) {
            showToast("error", "GRN out date must be equal or greater than GRN in date.");
            return;
        }
    }

    // Determine URL & HTTP method
    let ajaxUrl, ajaxType;
    if (isEdit) {
        const grnId = $('#grn_id').val();
        if (!grnId) {
            showToast('error', 'Please select a GRN to update.');
            return;
        }
        ajaxUrl = updateMobileGrnUrl.replace(':id', grnId); // Adjust based on your update route
        ajaxType = 'POST';
        formData.set('_method', 'PUT');
    } else {
        ajaxUrl = storeMobileGrnUrl;
        ajaxType = form.method.toUpperCase() || 'POST';
    }

    const actionText = isEdit ? 'updated' : 'created';

    $.ajax({
        url: ajaxUrl,
        type: ajaxType,
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function () {
            showLoader(`Please wait, ${actionText} GRN…`);
        },
        success: function (response) {
            if (response.success) {
                const orderSerial = response.data?.grn_serial ?? '';
                const html = `GRN <b>${orderSerial}</b> ${actionText} successfully`;
                Swal.fire({
                    title: 'Success!',
                    html: html,
                    icon: 'success',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                }).then((result) => {
                    if (result.isConfirmed) {
                        location.reload();
                    }
                });
            } else {
                showToast('error', response.message || 'Failed to process request', 5000);
            }
        },
        error: function (xhr) {
            const msg = xhr.responseJSON?.message ?? 'An unexpected error occurred.';
            showToast('error', msg, 5000);
        },
        complete: function () {
            hideLoader();
        }
    });
}
