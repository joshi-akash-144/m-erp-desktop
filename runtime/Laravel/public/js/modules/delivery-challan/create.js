let gstType = "";

$(document).ready(function () {
    $('#challan_date').focus();

    // date input with validation
    new DateInput("#challan_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    bindSelect2();

    initNetWeightEvents();

    // ── Customer / Item Selection ──────────────────────────────────
    
    $(document).on('change', '#account_id', function () {
        let accountId = $(this).val();
        if (accountId) {
            handleAccountId(accountId);
            // Refresh all sales orders for existing rows
            $(".item-row").each(function () {
                fetchSalesOrders($(this));
            });
        } else {
            $("#account_id_city").val("");
            $("#account_id_type").val("");
            gstType = "";

            // clear tax info and sales orders on all items and recalculate
            $(".item-row").each(function () {
                let row = $(this);
                row.find(".cgst_rate, .sgst_rate, .igst_rate").val(0);
                row.find(".sales_order_id").empty().append('<option value="">Select Sales Order</option>');
                rateBlur(row);
            });
        }
    });

    $(document).on("change", ".item_id", function () {
        let itemId = $(this).val();
        let row = $(this).closest(".item-row");
        if (itemId) {
            handleItemId(itemId, row);
            fetchSalesOrders(row);
        } else {
            // Handle clearing item data
            row.find(".unit_name").val("--");
            row.find(".cgst_rate, .sgst_rate, .igst_rate").val(0);
            row.find(".sales_order_id").empty().append('<option value="">Select Sales Order</option>');
            rateBlur(row);
        }

        calculateTotal();
        calculateTotalQty();
    });

    $(document).on("change", ".destination_id", function () {
        let row = $(this).closest(".item-row");
        fetchSalesOrders(row);
    });

    $(document).on("change", ".sales_order_id", function () {
        let soId = $(this).val();
        let row = $(this).closest(".item-row");
        let orders = $(this).data('orders');

        // Clear hidden fields first
        row.find('.sales_order_item_id, .sales_order_serial').val('');

        if (soId && orders) {
            let selectedSO = orders.find(o => o.id == soId);
            if (selectedSO && selectedSO.details) {
                let itemId = row.find('.item_id').val();
                let destinationId = row.find('.destination_id').val();

                // Find the specific line item in the Sales Order matching current item/destination
                let detail = selectedSO.details.find(d => 
                    d.item_id == itemId && 
                    (!destinationId || d.destination_id == destinationId)
                );

                if (detail) {
                    row.find('.condition_id').val(detail.condition_id).trigger('change');
                    row.find('.inclusive_rate').val(detail.inclusive_rate);
                    row.find('.rate').val(detail.rate);
                    
                    // Set hidden SO references explicitly from detail and SO objects
                    const soItemId = detail.id || '';
                    const soSerial = selectedSO.order_serial || '';
                    
                    row.find('.sales_order_item_id').val(soItemId);
                    row.find('.sales_order_serial').val(soSerial);

                    rateBlur(row);
                } else {
                    // Clear references if no matching detail found
                    row.find('.sales_order_item_id, .sales_order_serial').val('');
                }
            }
        }
    });

    // ── Table row events ──────────────────────────────────────────
    
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

   initVehicleRegValidation(".txtRegNo");
    // ── Add Line button ──────────────────────────────────────────
    $('#add_delivery_challan_item_row').on('click', function () {
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
});

function bindSelect2() {
    const selectIdArray = ['#challan_id','#broker_id', '#account_id', '.destination_id', '.item_id', '.condition_id','#bag_type','.sales_order_id'];
    selectIdArray.forEach(element => {
        $(element).select2({
            theme: "bootstrap-5",
            allowClear: true,
            placeholder: "Select " + element.replace('#', '').replace('.', '').replace('_id', '').replace('_order', '').replace('_type','') + " ...",
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

function addRow() {
    const template = document.getElementById('item_row_template');
    if (!template) {
        console.error('item_row_template not found');
        return;
    }

    const nextIndex = $('#item_table_body .item-row').length;
    const clone = template.content.firstElementChild.cloneNode(true);

    const $firstRow = $('#item_table_body .item-row').first();
    const copyOptions = ['.item_id', '.destination_id', '.condition_id', '.sales_order_id'];
    copyOptions.forEach(function (cls) {
        const $cleanOptions = $firstRow.find(cls + ' option').clone()
            .removeAttr('data-select2-id');
        const $dest = $(clone).find(cls);
        if ($dest.length && $cleanOptions.length) {
            $dest.empty().append($cleanOptions);
            $dest[0].selectedIndex = 0; 
        }
    });

    clone.querySelectorAll('input, select').forEach(function (el) {
        if (el.name) {
            el.name = el.name.replace(/items\[\d+\]/, 'items[' + nextIndex + ']');
        }
        if (!el.disabled) {
            if (el.tagName === 'INPUT')  el.value = '';
            if (el.tagName === 'SELECT') el.selectedIndex = 0;
        }
    });

    clone.querySelectorAll('.cgst_rate, .sgst_rate, .igst_rate').forEach(function (el) {
        el.value = '--';
    });

    clone.setAttribute('data-row-id', nextIndex);
    $('#item_table_body').append(clone);

    const $newRow = $('#item_table_body .item-row').last();
    const select2Config = { theme: 'bootstrap-5', allowClear: true };

    $newRow.find('.item_id').select2(Object.assign({}, select2Config, { placeholder: 'Select item ...' }));
    $newRow.find('.destination_id').select2(Object.assign({}, select2Config, { placeholder: 'Select destination ...' }));
    $newRow.find('.condition_id').select2(Object.assign({}, select2Config, { placeholder: 'Select condition ...' }));
    $newRow.find('.sales_order_id').select2(Object.assign({}, select2Config, { placeholder: 'Select sales order ...' }));

    clone.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    $newRow.find('.item_id').select2('open');
}

// ── Net Weight helpers ──────────────────────────────────────────

function formatDecimal($input) {
    const weightDec = typeof DECIMALS !== 'undefined' ? DECIMALS.WEIGHT_KG : 3;
    const val = parseFloat($input.val()) || 0;
    $input.val(val.toFixed(weightDec));
}

function calculateNetWeight() {
    const weightDec = typeof DECIMALS !== 'undefined' ? DECIMALS.WEIGHT_KG : 3;
    const gross = parseFloat($('#gross_weight').val()) || 0;
    const tare  = parseFloat($('#tare_weight').val())  || 0;

    if (tare > gross) {
        $('#net_weight').val((0).toFixed(weightDec));
        $('#net_weight_without_bags').val((0).toFixed(weightDec));
        return;
    }

    const net = gross - tare;
    $('#net_weight').val(net.toFixed(weightDec));

    // Update first row quantity if net weight is available
    if (net > 0 && $('#item_table_body .item-row').length > 0) {
        let finalQty = net;
        // If convertKgToTon exists, use it (standard for this ERP)
        if (typeof convertKgToTon === 'function') {
            finalQty = convertKgToTon(net);
        }
        
        const $firstRow = $('#item_table_body .item-row').first();
        const formattedQty = typeof formatQty === 'function' ? formatQty(finalQty) : finalQty.toFixed(weightDec);
        
        $firstRow.find('.quantity').val(formattedQty);
        $firstRow.find('.rate').trigger('blur');
        
        calculateTotalQty();
        calculateTotal();
    }

    // Cascade to without-bags
    calculateNetWeightWithoutBags();
}

function calculateNetWeightWithoutBags() {
    const weightDec = typeof DECIMALS !== 'undefined' ? DECIMALS.WEIGHT_KG : 3;
    const gross    = parseFloat($('#gross_weight').val()) || 0;
    const tare     = parseFloat($('#tare_weight').val())  || 0;
    const bagCount = parseFloat($('#bag_count').val())    || 0;
    const bagType  = $('#bag_type').val();
    const net      = gross - tare;

    let netWithoutBags = net;

    if (bagCount > 0 && net > 0) {
        let bagWeight = 0;

        if (bagType === BAG_GUNNY) {
            bagWeight = bagCount * BAG_GUNNY_WEIGHT;
        } else if (bagType === BAG_PLASTIC) {
            bagWeight = bagCount * BAG_PLASTIC_WEIGHT;
        }

        netWithoutBags = net - bagWeight;
    }

    $('#net_weight_without_bags').val(netWithoutBags.toFixed(weightDec));
}

function initNetWeightEvents() {
    $(document).on('blur', '#gross_weight', function () {
        formatDecimal($(this));
        calculateNetWeight();
    });

    $(document).on('blur', '#tare_weight', function () {
        formatDecimal($(this));
        calculateNetWeight();
    });

    $(document).on('blur', '#bag_count', function () {
        // formatDecimal($(this)); // Bag count usually doesn't have decimals but grn does it
        calculateNetWeightWithoutBags();
    });

    $(document).on('change', '#bag_type', function () {
        calculateNetWeightWithoutBags();
    });
}

function validateForm() {
    let validator = new JustValidate("#delivery_challan_form", {
        validateBeforeSubmitting: true,
        focusInvalidField: true,
        errorLabelCssClass: "text-danger",
    });

    validator
        .addField("#challan_date", [{ rule: "required" }])
        .addField("#broker_id", [{ rule: "required" }])
        .addField("#account_id", [{ rule: "required" }])

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
        submitFormAjax(document.getElementById("delivery_challan_form"));
    });
}

function submitFormAjax(form) {
    const formData = new FormData(form);
    const isEdit = (form.dataset.formMode === 'edit');

    // Convert dates to YMD format before sending
    ["challan_date", "challan_in_date", "challan_out_date"].forEach((field) => {
        if (formData.has(field) && formData.get(field)) {
            formData.set(field, formatDateToYMD(formData.get(field)));
        }
    });

    // Custom validation: out date must be ≥ in date
    if (formData.get("challan_out_date") && formData.get("challan_in_date")) {
        const outDate = new Date(formData.get("challan_out_date"));
        const inDate = new Date(formData.get("challan_in_date"));
        if (outDate < inDate) {
            showToast("error", "Challan out date must be equal or greater than Challan in date.");
            return;
        }
    }

    // Determine URL & HTTP method
    let ajaxUrl, ajaxType;
    if (isEdit) {
        const challanId = $('#challan_id').val();
        if (!challanId) {
            showToast('error', 'Please select a Challan to update.');
            return;
        }
        // Laravel requires _method=PUT for HTML/AJAX form spoofing
        ajaxUrl = updateDeliveryChallanRouteUrl.replace(':id', challanId);
        ajaxType = 'POST';
        formData.set('_method', 'PUT');
    } else {
        ajaxUrl = storeDeliveryChallanRouteUrl;
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
            showLoader(`Please wait, ${actionText} Challan...`);
        },
        success: function (response) {
            if (response.success) {
                const serial = response.data?.challan_serial ?? '';
                const html = `Delivery Challan <b>${serial}</b> ${actionText} successfully`;
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

// ── Selection Helpers ──────────────────────────────────────────

function handleAccountId(accountId) {
    if (!accountId) return;

    $.ajax({
        url: masterRoutes.accountDetails(accountId),
        type: "GET",
        beforeSend: function () {
            $('#account_city_loader, #account_type_loader').removeClass('d-none');
        },
        success: function (response) {
            fillAccountDetails(response.data);
        },
        error: function (error) {
            console.warn(error);
            showToast("error", "Failed to fetch customer details", 5000);
        },
        complete: function () {
            $('#account_city_loader, #account_type_loader').addClass('d-none');
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

    // Refresh tax rates for all item rows based on new customer location
    $(".item-row").each(function () {
        const currentRow = $(this);
        const itemId = currentRow.find(".item_id").val();
        if (itemId) {
            currentRow.find(".item_id").trigger("change");
        }
    });
}

function handleItemId(itemId, row) {
    if (!itemId) return;

    $.ajax({
        url: masterRoutes.itemDetails(itemId),
        type: "GET",
        beforeSend: function () {
            row.find('.unit_name_loader').removeClass('d-none');
        },
        success: function (response) {
            fillItemDetails(response.data, row);
        },
        error: function (error) {
            console.warn(error);
            showToast("error", "Failed to fetch item details", 5000);
        },
        complete: function () {
            row.find('.unit_name_loader').addClass('d-none');
        }
    });
}

function fillItemDetails(data, row) {
    let cgstElm = row.find(".cgst_rate");
    let sgstElm = row.find(".sgst_rate");
    let igstElm = row.find(".igst_rate");
    let unitElm = row.find(".unit_name");

    if (!data) return;
    unitElm.val(data.unit_name ?? "");
    
    if (!gstType) {
        cgstElm.val(0);
        sgstElm.val(0);
        igstElm.val(0);
        return;
    }

    if (gstType === GST_TYPE.LOCAL) {
        cgstElm.val(data.cgst ?? 0);
        sgstElm.val(data.sgst ?? 0);
        igstElm.val(0);
    } else {
        cgstElm.val(0);
        sgstElm.val(0);
        igstElm.val(data.igst ?? 0);
    }

    // Automatically recalculate row values based on new GST 
    rateBlur(row);
}

function fetchSalesOrders(row) {
    const accountId = $('#account_id').val();
    const itemId = row.find('.item_id').val();
    const destinationId = row.find('.destination_id').val();
    const brokerId = $('#broker_id').val();
    const $soSelect = row.find('.sales_order_id');

    // Requirement: Must select customer first
    if (!accountId) {
        $soSelect.empty().append('<option value="">Select Sales Order</option>');
        return;
    }

    $.ajax({
        url: pendingSalesOrders,
        type: 'GET',
        data: {
            account_id: accountId,
            item_id: itemId,
            destination_id: destinationId,
            broker_id: brokerId
        },
        beforeSend: function () {
            // Optional: Show a small loader in the select if desired
        },
        success: function (response) {
            $soSelect.empty().append('<option value="">Select Sales Order</option>');
            if (response.success && response.data) {
                // Store the orders data on the select element for later use on change
                $soSelect.data('orders', response.data);

                response.data.forEach(so => {
                    $soSelect.append(`<option value="${so.id}">${so.order_serial}</option>`);
                });
            }
        },
        error: function (xhr) {
            console.error('Failed to fetch sales orders', xhr);
        }
    });
}