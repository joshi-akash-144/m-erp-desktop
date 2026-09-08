$(document).ready(function () {
    $('#invoice_date').focus();
    
    new DateInput("#invoice_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("input[name='bill_date[]']", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    
    bindSelect2();
    validateForm();
});


function calculateRowTotals($row) {
    let kms = parseFloat($row.find("input[name='km[]']").val()) || 0;
    let bags = parseFloat($row.find("input[name='bag[]']").val()) || 0;
    let rate = parseFloat($row.find("input[name='rate[]']").val()) || 0;
    
    let multiplier = kms > 0 ? kms : bags;
    let total = multiplier * rate;
    
    $row.find("input[name='amount[]']").val(total > 0 ? total.toFixed(2) : "");
    
    calculateGrandTotal();
}

function calculateGrandTotal() {
    let totalBag = 0;
    let totalAmount = 0;

    $("#freight_invoice_table_body tr").each(function () {
        totalBag += parseFloat($(this).find("input[name='bag[]']").val()) || 0;
        totalAmount += parseFloat($(this).find("input[name='amount[]']").val()) || 0;
    });

    $("#total_bags").val(totalBag > 0 ? totalBag.toFixed(2) : "0.00");
    $("#total_amount").val(totalAmount > 0 ? totalAmount.toFixed(2) : "0.00");
}

$(document).on("input", "input[name='bag[]']", function () {
    this.value = this.value.replace(/[^0-9]/g, '');
    calculateRowTotals($(this).closest("tr"));
});

$(document).on("input", "input[name='km[]'], input[name='rate[]']", function () {
    this.value = this.value.replace(/[^0-9.]/g, '');
    if ((this.value.match(/\./g) || []).length > 1) {
        this.value = this.value.replace(/\.+$/, "");
    }
    calculateRowTotals($(this).closest("tr"));
});

$(document).on("input", "input[name='amount[]']", function () {
    this.value = this.value.replace(/[^0-9.]/g, '');
    if ((this.value.match(/\./g) || []).length > 1) {
        this.value = this.value.replace(/\.+$/, "");
    }
    
    let $row = $(this).closest("tr");
    let kms = parseFloat($row.find("input[name='km[]']").val()) || 0;
    let bags = parseFloat($row.find("input[name='bag[]']").val()) || 0;
    let amount = parseFloat($(this).val()) || 0;
    
    let multiplier = kms > 0 ? kms : bags;
    
    if (multiplier > 0 && amount > 0) {
        let rate = amount / multiplier;
        $row.find("input[name='rate[]']").val(rate.toFixed(2));
    } else {
        $row.find("input[name='rate[]']").val("");
    }
    
    calculateGrandTotal();
});



// ── Picker state ──────────────────────────────────────────────────────────────
var _pickerTarget        = null;
var _pickerUrl           = null;
var _pickerSelected      = false;
var _pickerShouldAdvance = false;
var _pickerInitialSearch = '';

var _pickerType         = null;
var _pickerMasterData   = { destination: null, vehicle: null, contractor: null };
var _pickerDataRequests = { destination: null, vehicle: null, contractor: null };
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
    if (!accountId) {
        $("#account_id_city").val("");
        return;
    }

    $.ajax({
        url: masterRoutes.accountDetails(accountId),
        type: "GET",
        beforeSend: function () {
            $("#account_city_loader").removeClass("d-none");
        },
        success: function (response) {
            if (response && response.data) {
                $("#account_id_city").val(response.data.city || "");
            } else {
                $("#account_id_city").val("");
            }
        },
        error: function () {
            $("#account_id_city").val("");
        },
        complete: function () {
            $("#account_city_loader").addClass("d-none");
        },
    });
}

// ── Picker Master Data Preload ────────────────────────────────────────────────
function preloadPickerMasterData() {
    if (typeof showLoader === 'function') {
        showLoader("Loading master data, please wait...");
    }

    var configs = [
        { type: 'destination', url: masterRoutes.allDestinations || masterRoutes.destinations },
        { type: 'vehicle',     url: masterRoutes.allVehicles || masterRoutes.vehicles },
        { type: 'contractor',  url: masterRoutes.allContractor }
    ];

    var requests = configs.map(function (cfg) {
        _pickerDataRequests[cfg.type] = $.ajax({ url: cfg.url, method: 'GET', dataType: 'json' })
            .done(function (res) {
                _pickerMasterData[cfg.type] = (res.data || []).map(function (d) {
                    return { id: d.id, text: d.name || d.text || '' };
                });
            });
        return _pickerDataRequests[cfg.type];
    });

    $.when.apply($, requests).always(function () {
        if (typeof hideLoader === 'function') {
            hideLoader();
        }
    });
}

// Initialize preload
$(document).ready(function() {
    preloadPickerMasterData();
});

// ── Picker Logic ─────────────────────────────────────────────────────────────
function openPicker($el, initialSearch) {
    if (_pickerTarget !== null) return;

    var type = $el.data('picker');
    var url  = type === 'destination' ? (masterRoutes.allDestinations || masterRoutes.destinations)
             : type === 'vehicle'     ? (masterRoutes.allVehicles || masterRoutes.vehicles)
             : type === 'contractor'  ? masterRoutes.allContractor
             : null;

    if (!url) {
        showToast('error', 'Unknown picker type.');
        return;
    }

    _pickerTarget        = $el;
    _pickerUrl           = url;
    _pickerType          = type;
    _pickerSelected      = false;
    _pickerInitialSearch = initialSearch || '';

    var titles = { destination: 'Destination', vehicle: 'Vehicle', contractor: 'Contractor' };
    $('#picker_title').text(titles[type] || 'Select');
    $('#pickerModal').modal('show');
}

function highlightFirstPickerResult() {
    $('#pickerModal .select2-results__option[aria-selected]:not(.select2-results__option--disabled)')
        .first()
        .trigger('mouseenter');
}

$('#pickerModal').on('show.bs.modal', function () {
    $('#picker_loader').show();
    $('#pickerModal .select2-container').remove();
});

$('#pickerModal').on('shown.bs.modal', function () {
    var $sel    = $('#picker_select');
    var $loader = $('#picker_loader');

    if ($sel.data('select2')) { $sel.select2('destroy'); }
    $sel.empty().append('<option value=""></option>');

    $sel.select2({
        theme:              'bootstrap-5',
        width:              '100%',
        dropdownParent:     $('#pickerModal'),
        closeOnSelect:      true,
        placeholder:        'Type to search…',
        allowClear:         false,
        minimumInputLength: 0,
        ajax: {
            transport: function (params, success) {
                var term    = (params.data.term || '').toLowerCase();
                var request = _pickerDataRequests[_pickerType];

                if (!request) { success({ results: [] }); return; }

                request.done(function () {
                    var dataset = _pickerMasterData[_pickerType] || [];
                    var results = term
                        ? dataset.filter(function (d) { return d.text.toLowerCase().indexOf(term) !== -1; })
                        : dataset;
                    success({ results: results });
                    setTimeout(highlightFirstPickerResult, 0);
                });
            },
            cache: true,
        },
        templateSelection: function (d) { return d.id ? d.text : ''; },
    }).val('').trigger('change.select2');

    $loader.hide();
    $sel.select2('open');

    setTimeout(function () {
        var $searchBox = $('#pickerModal .select2-search__field');
        $searchBox.trigger('focus');

        if (_pickerInitialSearch) {
            $searchBox.val(_pickerInitialSearch).trigger('input');
        }

        var el = $searchBox[0];
        if (el) {
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    e.stopImmediatePropagation();
                    _pickerShouldAdvance = false;
                    _pickerSelected      = false;
                    $('#pickerModal').modal('hide');
                    return;
                }

                if (e.key !== 'Enter') return;

                var highlighted = !!$('#pickerModal .select2-results__option--highlighted').length;
                if (highlighted) return;

                if (this.value === '') {
                    e.stopImmediatePropagation();
                    _pickerShouldAdvance = true;
                    $('#pickerModal').modal('hide');
                } else {
                    e.stopImmediatePropagation();
                    $('#pickerModal .select2-results__option[aria-selected]:not(.select2-results__option--disabled)')
                        .first()
                        .trigger('mouseup');
                }
            }, true);
        }
    }, 0);
});

$('#picker_select').on('select2:select', function () {
    var data = $(this).select2('data');
    if (!data || !data.length || !data[0].id) return;
    _pickerSelected      = true;
    _pickerShouldAdvance = true;
    if (_pickerTarget) {
        _pickerTarget.val(data[0].text);
        // Set the hidden input next to it
        _pickerTarget.siblings('.picked-id-hidden').val(data[0].id);
    }
    $('#pickerModal').modal('hide');
});

$('#pickerModal').on('hidden.bs.modal', function () {
    var $target      = _pickerTarget;
    var shouldAdvance = _pickerShouldAdvance;

    if ($('#picker_select').data('select2')) {
        $('#picker_select').select2('destroy');
    }

    _pickerTarget        = null;
    _pickerSelected      = false;
    _pickerShouldAdvance = false;
    _pickerInitialSearch = '';

    if (shouldAdvance && $target) {
        // Move to next input in row
        var $row = $target.closest('tr');
        var $inputs = $row.find('input:visible:not([disabled]):not([readonly]), select:visible:not([disabled])');
        var index = $inputs.index($target);
        if (index > -1 && index < $inputs.length - 1) {
            $inputs.eq(index + 1).focus();
        } else {
            var $nextRow = $row.next('tr');
            if ($nextRow.length) {
                $nextRow.find('input:visible:not([disabled]):not([readonly]), select:visible:not([disabled])').first().focus();
            }
        }
    } else if ($target) {
        $target.focus();
    }
});

$(document).on('click', '.row-picker', function () {
    openPicker($(this), '');
});

$(document).on('keydown', '.row-picker', function (e) {
    if (e.key === 'Backspace' || e.key === 'Delete') {
        e.preventDefault();
        $(this).val('');
        $(this).siblings('.picked-id-hidden').val('');
        return;
    }
    
    if (e.which === 9 || e.which === 27 || e.which === 13) return;
    if (e.ctrlKey || e.altKey || e.metaKey) return;
    if (!e.key || e.key.length !== 1) return;
    
    e.preventDefault();
    e.stopPropagation();
    
    if (_pickerTarget !== null) {
        _pickerInitialSearch += e.key;
    } else {
        openPicker($(this), e.key);
    }
});

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
            calculateGrandTotal();
        }
    });
});

$("#clear_all_rows_btn").on("click", function () {
    Swal.fire({
        title: "Clear all rows?",
        text: "This will reset all data in the table.",
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
            calculateGrandTotal();
        }
    });
});

function clearRow($tr) {
    $tr.find(".row-picker, .picked-id-hidden").val("");
    $tr.find("input[name='code[]'], input[name='route[]'], input[name='bag[]'], input[name='vendor[]'], input[name='km[]'], input[name='rate[]'], input[name='amount[]']").val("");
}

function validateForm() {
    let validator = new JustValidate("#freight_invoice_2_form", {
        validateBeforeSubmitting: true,
        focusInvalidField: true,
        errorLabelCssClass: "text-danger",
    });

    validator
        .addField("#invoice_date", [{ rule: "required" }])
        .addField("#account_id", [{ rule: "required" }])
        .addField("#destination_id", [{ rule: "required" }])
        .addField("#km", [{ rule: "required" }])
        .addField("#rate", [{ rule: "required" }])
        .addField("#vehicle_id", [{ rule: "required" }])
        .addField("#bill_date", [{ rule: "required" }]); 

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

function submitFreightInvoice() {
    let form = document.getElementById("freight_invoice_2_form");
    
    // Disable empty rows to avoid hitting PHP max_input_vars limit
    let emptyRows = $("#freight_invoice_table_body tr").filter(function() {
        let destination = $(this).find("input[name='destination_id[]']").val();
        return !destination; 
    });
    let disabledInputs = emptyRows.find("input, select").prop("disabled", true);

    let formData = new FormData(form);
    
    // Add total amount since disabled inputs are not serialized
    formData.append("total_amount", $("#total_amount").val() || 0);

    let submitBtn = $("#save_btn");
    let originalText = submitBtn.html();
    submitBtn.prop("disabled", true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

    // Re-enable inputs so the UI doesn't look broken
    disabledInputs.prop("disabled", false);

    $.ajax({
        url: storeFreightInvoice2Url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if (response.success) {
                Swal.fire({
                    icon: "success",
                    title: "Success",
                    text: response.message,
                    timer: 1500,
                    showConfirmButton: false,
                }).then(() => {
                    window.location.reload();
                });
            } else {
                showToast("error", response.message || "An error occurred");
                submitBtn.prop("disabled", false).html(originalText);
            }
        },
        error: function (xhr) {
            let msg = "An error occurred";
            if (xhr.responseJSON && xhr.responseJSON.errors && Object.keys(xhr.responseJSON.errors).length > 0) {
                msg = Object.values(xhr.responseJSON.errors)[0][0];
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            showToast("error", msg);
            submitBtn.prop("disabled", false).html(originalText);
        },
    });
}