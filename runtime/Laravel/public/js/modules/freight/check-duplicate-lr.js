function validateLrNumber() {
    $(document).on("keyup change", "#lr_number", function () {
        debouncedLrCheck();
    });
}

let debounceLrTimer = null;
function debouncedLrCheck() {
    clearTimeout(debounceLrTimer);
    debounceLrTimer = setTimeout(() => checkDuplicateLrNumber(), 300);
}

function checkDuplicateLrNumber() {

    const lrLoader = $("#lr_number_loader");
    if (lrLoader.length) {
        lrLoader.removeClass("d-none");
    }

    const lrNoInput = $("#lr_number");
    if (!lrNoInput.length) return;
    
    // Use String() coercion so numeric 0 (falsy in JS) is not treated as empty.
    // Strict === '' check ensures "0" still goes through the duplicate AJAX call.
    const lrNo = String(lrNoInput.val() ?? '').trim();

    if (lrNo === '') {
        lrNoInput.removeClass("is-invalid");
        if (lrLoader.length) lrLoader.addClass("d-none");
        $("#lr_duplicate_link").remove();
        return;
    }

    if (typeof checkDuplicateLrFromFreightModule === 'undefined') {
        console.warn('checkDuplicateLrFromFreightModule route is not defined.');
        return;
    }

    let excludeId = null;
    if (typeof FREIGHT_DATA !== 'undefined' && FREIGHT_DATA && FREIGHT_DATA.id) {
        excludeId = FREIGHT_DATA.id;
    }

    $.ajax({
        url: checkDuplicateLrFromFreightModule,
        type: "GET",
        data: {
            lr_number: lrNo,
            exclude_id: excludeId
        },
        success: function (response) {
            const isDuplicate = response.data && response.data.is_duplicate;

            $("#lr_duplicate_link").remove();

            if (isDuplicate) {
                lrNoInput.addClass("is-invalid");
                lrNoInput.after(`
                    <div id="lr_duplicate_link" class="mt-1">
                        <span class="text-danger text-decoration-underline">Duplicate L.R. Number</span>
                    </div>
                `);
            } else {
                lrNoInput.removeClass("is-invalid");
            }
        },
        error: function (error) {
            console.warn(error);
        },
        complete: function () {
            if (lrLoader.length) lrLoader.addClass("d-none");
        },
    });
}

// Auto-initialize when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
    validateLrNumber();
});
