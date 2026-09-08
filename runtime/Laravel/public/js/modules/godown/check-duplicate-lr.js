function validateLrNumber() {

    $(document).on("keyup change", "#lr_number", function () {
        debouncedLrCheck();
    });

    $(document).on("change","#transporter_id", function () {
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

    if (lrNo === '' || lrNo === '0') {
        lrNoInput.removeClass("is-invalid");
        if (lrLoader.length) lrLoader.addClass("d-none");
        $("#lr_duplicate_link").remove();
        return;
    }

    const transporterId = $("#transporter_id").val();

    if (!transporterId) {
        if (lrLoader.length) lrLoader.addClass("d-none");
        return;
    }

    if (typeof checkDuplicateLrFromGodownModule === 'undefined') {
        console.warn('checkDuplicateLrFromGodownModule route is not defined.');
        return;
    }

    $.ajax({
        url: checkDuplicateLrFromGodownModule,
        type: "GET",
        data: {
            transporter_id: transporterId,
            lr_number: lrNo,
            godown_id: $("#id").val()
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
