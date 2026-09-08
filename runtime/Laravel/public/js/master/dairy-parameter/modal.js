(function () {
    let validator;

    $(document).ready(function () {
        // validation
        validator = new JustValidate("#dairy_parameter_form", {
            validateBeforeSubmitting: true,
            focusInvalidField: true,
        });

        validator
            .addField("#condition_id", [
                { rule: "required", errorMessage: "Condition is required" },
                { rule: "maxLength", value: 100, errorMessage: "Condition cannot exceed 100 characters" }
            ])
            .addField("#element_id", [
                { rule: "required", errorMessage: "Element is required" },
            ])
            .addField("#guarantee", [
                { rule: "required", errorMessage: "Guarantee is required" },
            ])
            .onSuccess((event) => {
                event.preventDefault();
                submitFormAjax(document.getElementById("dairy_parameter_form"));
            });

        toggleTrashIcons();
    });

    // ===================================================================
    // SUBMIT HANDLER
    // ===================================================================
    let isSubmitting = false;

    function submitFormAjax(form) {
        if (isSubmitting) return;
        isSubmitting = true;

        const $form = $(form);
        const $btn = $("#dairy_parameter_modal .form-save-btn");

        const method = ($form.attr("data-method") || $form.attr("method") || "POST").toUpperCase();
        const url = $form.attr("action");
        const isEdit = method === "PUT" || url.toLowerCase().includes("update");

        // Button loading state
        if ($btn.length) {
            $btn.prop("disabled", true).html(`
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                ${isEdit ? "Updating" : "Saving"} <span class="animated-dots"></span>
            `);
        }

        const formData = new FormData(form);
        if (isEdit) {
            formData.append("_method", "PUT");
        }

        // Remove all existing array fields from FormData, then re-add only non-empty rows
        const arrayFields = ['id[]', 'from[]', 'to[]', 'difference[]', 'rebate[]', 'premium[]'];
        arrayFields.forEach(field => formData.delete(field));

        $('#parameter_table tbody tr').each(function () {
            const $row = $(this);
            const fromVal   = $row.find('input[name="from[]"]').val().trim();
            const toVal     = $row.find('input[name="to[]"]').val().trim();
            const rebateVal = $row.find('input[name="rebate[]"]').val().trim();

            // Only submit rows that have at least one key field filled
            if (fromVal === '' && toVal === '' && rebateVal === '') {
                return; // skip empty rows
            }

            formData.append('id[]',         $row.find('input[name="id[]"]').val() || '');
            formData.append('from[]',       fromVal);
            formData.append('to[]',         toVal);
            formData.append('difference[]', $row.find('input[name="difference[]"]').val().trim());
            formData.append('rebate[]',     rebateVal);
            formData.append('premium[]',    $row.find('input[name="premium[]"]').val().trim() || '0');
        });

        $.ajax({
            url: url,
            type: "POST", // Laravel handles PUT via _method
            data: formData,
            processData: false,
            contentType: false,
            success: function (data) {
                const success = data.success;
                const message = data.message;

                if (success) {
                    showToast("success", message || (isEdit ? "dairy Parameter updated successfully!" : "dairy Parameter saved successfully!"));
                    $('#dairy_parameter_modal').modal('hide');
                    if (typeof table !== "undefined") table.setData(); // Using generic table pointer
                } else {
                    showToast("error", message || "Something went wrong.");
                }
            },
            error: function (xhr) {
                console.error("Submit Error:", xhr);
                const errMsg = xhr.responseJSON?.message || "Server error while submitting. Please try again.";
                showToast("error", errMsg);
            },
            complete: function () {
                if ($btn.length) {
                    $btn.prop("disabled", false).html(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-square-check">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                            <path d="M9 12l2 2l4 -4" />
                        </svg>
                        ${isEdit ? "Update dairy Parameter" : "Save dairy Parameter"}
                    `);
                }
                isSubmitting = false;
            }
        });
    }

    // ===================================================================
    // DYNAMIC ROW MANAGEMENT & CALCULATION
    // ===================================================================
    
    function calculateDifference($row) {
        const $fromInput = $row.find('input[name="from[]"]');
        const $toInput = $row.find('input[name="to[]"]');
        const $diffInput = $row.find('input[name="difference[]"]');

        const fromVal = $fromInput.val();
        const toVal = $toInput.val();

        if (toVal === "" || toVal === null) {
            $diffInput.val("");
            return;
        }

        const from = parseFloat(fromVal) || 0;
        const to = parseFloat(toVal) || 0;
        const diff = (to - from).toFixed(2);
        $diffInput.val(diff);
    }

    $(document).off("input", 'input[name="from[]"], input[name="to[]"]').on("input", 'input[name="from[]"], input[name="to[]"]', function () {
        calculateDifference($(this).closest("tr"));
    });

    $(document).off("click", "#add_line").on("click", "#add_line", function () {
        const isViewMode = $("#form_mode").val() === "view";
        if (isViewMode) return;

        const $tableBody = $("#parameter_table tbody");
        const $lastRow = $tableBody.find("tr").last();
        const $newRow = $lastRow.clone();

        // Clear values of cloned inputs
        $newRow.find("input").val("");
        $newRow.find('input[type="hidden"]').val("");
        $newRow.find(".error-msg").text("");

        $tableBody.append($newRow);
        toggleTrashIcons();
    });

    $(document).off("click", ".remove-line").on("click", ".remove-line", function () {
        const isViewMode = $("#form_mode").val() === "view";
        if (isViewMode) return;

        const $tableBody = $("#parameter_table tbody");
        if ($tableBody.find("tr").length > 1) {
            $(this).closest("tr").remove();
            toggleTrashIcons();
        } else {
            // Clear values if it's the last remaining row
            const $row = $(this).closest("tr");
            $row.find("input").val("");
            $row.find('input[type="hidden"]').val("");
            $row.find(".error-msg").text("");
        }
    });

    function toggleTrashIcons() {
        const $tableBody = $("#parameter_table tbody");
        if ($tableBody.find("tr").length === 1) {
            $tableBody.find(".remove-line").hide();
        } else {
            $tableBody.find(".remove-line").show();
        }
    }

    // ===================================================================
    // CUSTOM ENTER KEY NAVIGATION
    // ===================================================================
    $("#dairy_parameter_form").on("keydown", "input", function (e) {
        if (e.which === 13) {
            // Ignore select2 search fields, they have their own handler
            if ($(this).hasClass("select2-search__field")) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            const id = $(this).attr("id");
            const name = $(this).attr("name");

            if (id === "guarantee") {
                // Focus the first 'from' input in the table
                $("#parameter_table tbody tr:first-child input[name='from[]']").focus();
            } else if (name === "from[]") {
                $(this).closest("td").next("td").find("input[name='to[]']").focus();
            } else if (name === "to[]") {
                $(this).closest("tr").find("input[name='rebate[]']").focus();
            } else if (name === "rebate[]") {
                $(this).closest("td").next("td").find("input[name='premium[]']").focus();
            } else if (name === "premium[]") {
                // Move to next row's 'from' input if it exists, otherwise move to Save button
                const $nextRow = $(this).closest("tr").next("tr");
                if ($nextRow.length > 0) {
                    $nextRow.find("input[name='from[]']").focus();
                } else {
                    $("#dairy_parameter_modal .form-save-btn").focus();
                }
            } else {
                // Fallback for anything else
                if (typeof moveFocusToNextField === "function") {
                    moveFocusToNextField($(this));
                }
            }
        }
    });

})();