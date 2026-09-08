$(document).ready(function () {
    let mode = $('#grn_form').data('form-mode');
    if (mode === 'create') {
        $('#grn_date').focus();
    }
    
    // date input with validation
    new DateInput("#grn_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#grn_in_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#grn_out_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    // replicate grn_date into in/out dates whenever user changes it
    $(document).on('change', '#grn_date', function () {
        const val = $(this).val();
        $('#grn_in_date').val(val);
        $('#grn_out_date').val(val);
    });

    bindSelect2();

    initNetWeightEvents();

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

    // purchase_order_serial: Space opens PO modal, all other keys blocked
    $(document).on('keydown', '.purchase_order_serial', function (event) {
        const key = event.key;

        // Allow Tab / Shift+Tab for navigation only
        if (key === 'Tab') return;

        // Block everything else by default
        event.preventDefault();

        if (key === ' ') {
            const row = $(this).closest('.item-row');
            // Clear previously selected PO data
            row.find('.purchase_order_serial').val('');
            row.find('.purchase_order_id').val('');
            row.find('.purchase_order_item_id').val('');
            row.find('.clear-po').addClass('d-none');
            openPurchaseOrderModal(row);
        }
        // Backspace / Delete clears the selection silently
        if (key === 'Backspace' || key === 'Delete') {
            const row = $(this).closest('.item-row');
            row.find('.purchase_order_serial').val('');
            row.find('.purchase_order_id').val('');
            row.find('.purchase_order_item_id').val('');
            row.find('.clear-po').addClass('d-none');
        }
    });

    // Delete row – minimum 1 row must remain
    $(document).on('click', '.erp-btn-icon.delete', function () {
        const $rows = $('#item_table_body .item-row');
        if ($rows.length <= 1) {
            showToast('warning', 'At least one item row is required.', 3000);
            return;
        }
        $(this).closest('.item-row').remove();
        calculateTotal();
        calculateTotalQty();
    });

    //user want in party qty blur add 3 decimal point
    $(document).on('blur', '.party_quantity', function () {
        const val = parseFloat($(this).val()) || 0;
        $(this).val(val.toFixed(DECIMALS.QTY));
    });

    // Blur events for auto-calculations (work on all rows, including new ones)
    $(document).on('blur', '.quantity', function () {
        const row = $(this).closest('.item-row');
        qtyBlur(row);
        calculateTotal();
        calculateTotalQty();
    });

    $(document).on('blur', '.rate', function () {
        const row = $(this).closest('.item-row');
        rateBlur(row);
        calculateTotal();
    });

    $(document).on('blur', '.inclusive_rate', function () {
        const row = $(this).closest('.item-row');
        inclusiveBlur(row);
        calculateTotal();
    });

    validateReferenceNumber();
    initVehicleRegValidation(".txtRegNo");

    // ── Add Line button ──────────────────────────────────────────
    $('#add_grn_item_row').on('click', function () {
        addRow();
    });

    // Ctrl+L shortcut
    $(document).on('keydown', function (e) {
        if (e.ctrlKey && e.key === 'l') {
            e.preventDefault();
            addRow();
        }
    });

    validateForm();

    bindBrokerSelect2();


    // const selectIdArray = ['#broker_id', '..condition_id'];
    // selectIdArray.forEach(element => {
    //     $(element).select2({
    //         theme: "bootstrap-5",
    //         // allowClear: true,
    //         // placeholder: "Select " + element.replace('#', '').replace('.', '').replace('_id', '') + " ...",
    //     });
    // });

});


function bindBrokerSelect2() {
    const selectIdArray = ['#broker_id', '.condition_id'];
    selectIdArray.forEach(element => {
        $(element).select2({
            theme: "bootstrap-5",
            // allowClear: true,
            // placeholder: "Select " + element.replace('#', '').replace('.', '').replace('_id', '') + " ...",
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

let gstType = "";

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
    const selectIdArray = ['#account_id', '.destination_id', '.item_id', '.condition_id','#bag_type'];
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

// ────────────────────────────────────────────────────────────────
//  addRow – clone template, re-index names, init Select2
// ────────────────────────────────────────────────────────────────
function addRow(skipOpen = false) {
    const template = document.getElementById('item_row_template');
    if (!template) {
        console.error('item_row_template not found');
        return;
    }

    // Next index = current number of rows
    const nextIndex = $('#item_table_body .item-row').length;

    // Deep-clone template content
    const clone = template.content.firstElementChild.cloneNode(true);

    // ── Copy option lists from the first row (Blade populates them) ──────────
    // We clone each <option> and strip Select2's internal data-select2-id
    // attributes to prevent ID conflicts that break selection in new rows.
    const $firstRow = $('#item_table_body .item-row').first();
    const copyOptions = ['.item_id', '.destination_id', '.condition_id'];
    copyOptions.forEach(function (cls) {
        const $cleanOptions = $firstRow.find(cls + ' option').clone()
            .removeAttr('data-select2-id');
        const $dest = $(clone).find(cls);
        if ($dest.length && $cleanOptions.length) {
            $dest.empty().append($cleanOptions);
            $dest[0].selectedIndex = 0; // reset to blank/placeholder
        }
    });

    // Re-index all input/select name attributes: items[0][...] → items[N][...]
    clone.querySelectorAll('input, select').forEach(function (el) {
        if (el.name) {
            el.name = el.name.replace(/items\[\d+\]/, 'items[' + nextIndex + ']');
        }
        // Clear values (skip disabled fields — handled separately below)
        if (!el.disabled) {
            if (el.tagName === 'INPUT')  el.value = '';
            if (el.tagName === 'SELECT') el.selectedIndex = 0;
        }
    });

    // Clear the disabled GST rate display fields (template has value="--")
    clone.querySelectorAll('.cgst_rate, .sgst_rate, .igst_rate').forEach(function (el) {
        el.value = '';
    });

    // Set data-row-id
    clone.setAttribute('data-row-id', nextIndex);

    // Append to tbody
    $('#item_table_body').append(clone);

    const $newRow = $('#item_table_body .item-row').last();

    // Init Select2 for the new row's dropdowns
    const select2Config = {
        theme: 'bootstrap-5',
        allowClear: true,
    };

    $newRow.find('.item_id').select2(Object.assign({}, select2Config, {
        placeholder: 'Select item ...',
    }));
    $newRow.find('.destination_id').select2(Object.assign({}, select2Config, {
        placeholder: 'Select destination ...',
    }));
    $newRow.find('.condition_id').select2(Object.assign({}, select2Config, {
        placeholder: 'Select condition ...',
    }));

    clone.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    if (!skipOpen) {
        $newRow.find('.item_id').select2('open');
    }
}


// ────────────────────────────────────────────────────────────────────
//  Net Weight helpers
//  Dependency chain:
//    gross/tare → calculateNetWeight()
//                  → updates #net_weight
//                  → calls calculateNetWeightWithoutBags()
//    bag_count / bag_type → calculateNetWeightWithoutBags()
// ────────────────────────────────────────────────────────────────────

/** Round & format a decimal input to DECIMALS.WEIGHT_KG places */
function formatDecimal($input) {
    const val = parseFloat($input.val()) || 0;
    $input.val(val.toFixed(DECIMALS.WEIGHT_KG));
}

/** gross − tare → #net_weight, then cascade to without-bags */
function calculateNetWight() {
    const net = calculateNetWeight('#gross_weight', '#tare_weight', '#net_weight');

    // if (net > 0 && $('#item_table_body .item-row').length > 0) {
    //     const convertInTon = convertKgToTon(net);
    //     // fetch first row qty and change it to net
    //     $('#item_table_body .item-row').first().find('.quantity').val(formatQty(convertInTon));
    //     $('#item_table_body .item-row').first().find('.rate').trigger('blur');
    //     // recalculate qty total
    //     calculateTotalQty();
    //     calculateTotal();
    // }

    // Cascade
    calculateWeightWithoutBags();
}

/** net − bag-weight → #net_weight_without_bags */
function calculateWeightWithoutBags() {
   let netWtBags= calculateNetWeightWithoutBags(
        '#gross_weight',
        '#tare_weight',
        '#bag_count',
        '#bag_type',
        '#net_weight_without_bags'
    );
    if (netWtBags > 0 && $('#item_table_body .item-row').length > 0) {
        const convertInTon = convertKgToTon(netWtBags);
        // fetch first row qty and change it to net
        $('#item_table_body .item-row').first().find('.quantity').val(formatQty(convertInTon));
        $('#item_table_body .item-row').first().find('.rate').trigger('blur');
        // recalculate qty total
        calculateTotalQty();
        calculateTotal();
    }
}

function initNetWeightEvents() {
    // Gross weight — blur to format + recalculate on focus-out
    $(document).on('change', '#gross_weight', function () {
        formatDecimal($(this));
        calculateNetWight();
    });

    // Tare weight — blur (text input, change alone doesn't always fire)
    $(document).on('blur', '#tare_weight', function () {
        formatDecimal($(this));
        calculateNetWight();
    });

    // Bag count — blur
    $(document).on('blur', '#bag_count', function () {
        formatDecimal($(this));
        calculateWeightWithoutBags();
    });

    // Bag type — change (it's a <select>, change fires reliably)
    $(document).on('change', '#bag_type', function () {
        calculateWeightWithoutBags();
    });
}

function validateForm() {
    let validator = new JustValidate("#grn_form", {
        validateBeforeSubmitting: true,
        focusInvalidField: true,
        errorLabelCssClass: "text-danger",
    });

    validator
        .addField("#grn_date", [{ rule: "required" }])
        .addField("#grn_in_date", [{ rule: "required" }])
        .addField("#account_id", [{ rule: "required" }])
        .addField("#reference_number", [{ rule: "required" }])

    // Validate table rows
    document.querySelectorAll(".item-row").forEach((row) => {
        let itemId = row.querySelector(".item_id");

        if (itemId) validator.addField(itemId, [{ rule: "required" }]);
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
        submitFormAjax(document.getElementById("grn_form"));
    });
}

function submitFormAjax(form) {
    const formData = new FormData(form);
    const isEdit = (form.dataset.formMode === 'edit');

    // Convert dates to YMD format before sending
    ["grn_date", "grn_in_date", "grn_out_date"].forEach((field) => {
        if (formData.has(field)) {
            formData.set(field, formatDateToYMD(formData.get(field)));
        }
    });

    // If rate pass is blank than add 0
    for (let [key, value] of formData.entries()) {
        if (key.match(/^items\[\d+\]\[rate\]$/) && !value) {
            formData.set(key, 0);
        }
    }

    // Custom validation: out date must be ≥ in date
    const grnOutDate = new Date(formData.get("grn_out_date"));
    const grnInDate = new Date(formData.get("grn_in_date"));
    if (formData.get("grn_out_date") && formData.get("grn_in_date") && grnOutDate < grnInDate) {
        showToast("error", "GRN out date must be equal or greater than GRN in date.");
        return;
    }

    // Determine URL & HTTP method
    let ajaxUrl, ajaxType;
    if (isEdit) {
        const grnId = $('#grn_id').val();
        if (!grnId) {
            showToast('error', 'Please select a GRN to update.');
            return;
        }
        // Laravel requires _method=PUT for HTML/AJAX form spoofing
        ajaxUrl = updateGrnUrl.replace(':id', grnId);
        ajaxType = 'POST';
        formData.set('_method', 'PUT');
    } else {
        ajaxUrl = form.action;
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
                const html = `GRN <span class="fw-bold text-danger">${orderSerial}</span> ${actionText} successfully`;
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