window.initPermissionValidation = function() {
    const form = document.getElementById('permissionForm');
    if (!form) return;

    // Use JustValidate
    const validation = new JustValidate(form, {
        errorFieldCssClass: "is-invalid",
        errorLabelCssClass: "text-danger",
        errorLabelStyle: { fontSize: "12px", marginTop: "4px" },
        focusInvalidField: true,
    });

    // Helper function to create an error container outside the input group
    function getErrorContainer(inputElement) {
        const wrapper = inputElement.closest('.input-group') || inputElement.closest('.input-icon');
        if (wrapper) {
            let container = wrapper.nextElementSibling;
            if (!container || !container.classList.contains('jv-error-container')) {
                container = document.createElement('div');
                container.className = 'jv-error-container mt-1';
                wrapper.parentNode.insertBefore(container, wrapper.nextSibling);
            }
            return container;
        }
        return null;
    }

    // Validate Module Name
    const moduleInput = form.querySelector('input[name="module_name"]') || form.querySelector('input[name="module"]');
    if (moduleInput) {
        validation.addField(moduleInput, [
            { rule: 'required', errorMessage: 'Module Name is required' }
        ], {
            errorsContainer: getErrorContainer(moduleInput)
        });
    }

    // Validate Permission Names (only the first one is strictly required to not be empty if there are multiples)
    const firstPermInput = form.querySelector('input[name="permissions[]"]') || form.querySelector('input[name="name"]');
    if (firstPermInput) {
        validation.addField(firstPermInput, [
            { rule: 'required', errorMessage: 'Permission Name is required' }
        ], {
            errorsContainer: getErrorContainer(firstPermInput)
        });
    }

    validation.onSuccess((event) => {
        event.preventDefault();

        // Auto-remove any dynamically added permission row that is completely empty
        $('#dynamic_permissions_container .permission-row').each(function() {
            const input = $(this).find('input[name="permissions[]"]');
            if (input.length > 0 && input.val().trim() === '') {
                // We only remove if it's not the last remaining row
                if ($('#dynamic_permissions_container .permission-row').length > 1) {
                    $(this).remove();
                }
            }
        });

        const formData = $(form).serialize();
        const url = form.getAttribute('action');
        const method = form.getAttribute('method');

        $.ajax({
            url: url,
            type: method,
            data: formData,
            success: function (res) {
                if (res.success) {
                    $('#permissionModal').modal('hide');
                    if (typeof table !== 'undefined') {
                        table.setData();
                    }
                    Swal.fire('Success!', res.message || 'Saved successfully', 'success');
                } else {
                    Swal.fire('Error!', res.message || 'Failed to save', 'error');
                }
            },
            error: function (xhr) {
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    let errorsHtml = '<ul class="text-start">';
                    $.each(xhr.responseJSON.errors, function (key, value) {
                        errorsHtml += `<li>${value[0]}</li>`;
                    });
                    errorsHtml += '</ul>';

                    // Combine the general message with the detailed list of errors
                    let displayHtml = xhr.responseJSON.message 
                        ? `<p class="text-center">${xhr.responseJSON.message}</p>` + errorsHtml 
                        : errorsHtml;

                    Swal.fire({
                        title: 'Validation Error',
                        html: displayHtml,
                        icon: 'error'
                    });
                } else {
                    // For non-validation errors (like 500 server error or 403 forbidden), show the message here
                    const msg = xhr.responseJSON?.message || 'Something went wrong.';
                    Swal.fire('Error!', msg, 'error');
                }
            }
        });
    });
};
