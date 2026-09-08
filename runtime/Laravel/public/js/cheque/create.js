

$(document).ready(function () {
    // Focus on format name input
    $("#formateName").focus();

    bindSelect2();
    initValidation();
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });
  
    $("#chequeFormatForm").on("submit", function (e) {
        e.preventDefault();
    });

    // Prevent duplicate values in the properties table
    $(document).on('change', 'select[name="value[]"]', function() {
        const currentValue = $(this).val();
        const currentRow = $(this).closest('tr');
        const currentId = currentRow.data('id');
        
        if (!currentValue) return;

        let isDuplicate = false;
        $("#chequePropertiesTable tbody tr").each(function() {
            const otherRow = $(this);
            if (otherRow.data('id') !== currentId) {
                const otherValue = otherRow.find('select[name="value[]"]').val();
                if (otherValue === currentValue) {
                    isDuplicate = true;
                    return false;
                }
            }
        });

        if (isDuplicate) {
            Swal.fire({ 
                title: 'Duplicate Row', 
                text: `The value "${currentValue}" is already added to this format.`, 
                icon: 'error',
                allowOutsideClick: false,
                allowEscapeKey: false
            });
            $(this).val(''); // Reset the selection
        }
    });
});
const defaultChequeProperties = [
    { value: 'A/C Payee', top: '0.31', left: '4.34', width: '1.5', height: '0.3', font_size: '14', font_name: 'Times New Roman', align_text: 'left', editable: false, font_style: 'bold' },
    { value: 'Date', top: '0.20', left: '6.30', width: '1.5', height: '0.3', font_size: '16', font_name: 'Times New Roman', align_text: 'left', editable: false, font_style: 'bold' },
    { value: 'Account Name', top: '0.68', left: '1.29', width: '6.8', height: '0.3', font_size: '16', font_name: 'Times New Roman', align_text: 'left', editable: false, font_style: 'bold' },
    { value: 'Amount In Words', top: '1.04', left: '1.4', width: '4.7', height: '0.6', font_size: '16', font_name: 'Times New Roman', align_text: 'left', editable: false, font_style: 'bold' },
    { value: 'Amount', top: '1.28', left: '6.20', width: '1.5', height: '0.3', font_size: '18', font_name: 'Times New Roman', align_text: 'left', editable: false, font_style: 'bold' }
];
function submitChequeForm(form) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'Do you want to submit this format?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, submit it!',
        cancelButtonText: 'No, cancel',
        allowOutsideClick: false,
        allowEscapeKey: false,
    }).then(function (result) {
        if (result.isConfirmed) {
            var formData = {
                formate_name: $("#formateName").val(),
                unique_token: $("#unique_token").val(),
                cheque_height: $("#cheque_height").val(),
                cheque_width: $("#cheque_width").val(),
                top_margin: $("#top_margin").val(),
                left_margin: $("#left_margin").val(),
                bank_accounts: $("#bankAccounts").val(),
            };

            const chequeProperties = [];
            $("#chequePropertiesTable tbody tr").each(function () {
                const row = $(this);
                if (row.attr('data-confirmed') == 'true' || row.attr('data-editable') == 'true') {
                    const property = {
                        id: row.data('id'),
                        value: row.find("select[name='value[]']").val(),
                        top: row.find("input[name='top[]']").val(),
                        left: row.find("input[name='left[]']").val(),
                        width: row.find("input[name='width[]']").val(),
                        height: row.find("input[name='height[]']").val(),
                        align_text: row.find("select[name='align[]']").val(),
                        font_name: row.find("select[name='font[]']").val(),
                        font_size: row.find("select[name='font_size[]']").val(),
                        font_style: row.find("select[name='font_style[]']").val(),
                    };
                    chequeProperties.push(property);
                }
            });

            $.ajax({
                url: storeChequeFormat,
                type: 'POST',
                data: {
                    formData: formData,
                    chequeProperties: chequeProperties
                },
                success: function (response) {
                    if (response.success || response.code == 200) {
                        const formMode = $('#chequeFormatForm').data('form-mode');
                        const title = formMode === 'edit' ? 'Updated!' : 'Saved!';
                        const text = formMode === 'edit' ? 'Cheque format updated successfully.' : 'Cheque format saved successfully.';
                        Swal.fire({ title: title, text: text, icon: 'success', allowOutsideClick: false, allowEscapeKey: false }).then(function () {
                            window.location.href = chequeListUrl;
                        });
                    } else {
                        Swal.fire({ title: 'Error', text: response.message, icon: 'error', allowOutsideClick: false, allowEscapeKey: false });
                    }
                },
                error: function (xhr, status, error) {
                    console.log(error);
                }
            });
        }
    });
}
    
    const select2Fields = ["#bankAccounts"];
    select2Fields.forEach(field => {
        if (typeof initializeSelect2 === 'function') {
            initializeSelect2(field);
        } else if ($.fn.select2) {
            $(field).select2({ width: '100%' });
        }
    });

    // Initialize Canvas Drag and Drop
    enableChequeContainerDragAndDrop();

    // Initial load
    if (typeof editChequeProperties !== 'undefined' && editChequeProperties.length > 0) {
        const reverseValueMap = {
            'date': 'Date',
            'account_name': 'Account Name',
            'amount': 'Amount',
            'amount_in_words': 'Amount In Words',
            'ac_payee': 'A/C Payee'
        };

        editChequeProperties.forEach(prop => {
            const displayValue = reverseValueMap[prop.column_value] || prop.column_value;
            addChequePropertiesTableRow(
                1, 
                prop.id, 
                true, 
                displayValue, 
                prop.top, 
                prop.left, 
                prop.width, 
                prop.height, 
                prop.font_size, 
                prop.font_name, 
                prop.align_text, 
                false, 
                prop.font_style
            );
        });
        setChequeContainerSize();
    } else if ($("#chequePropertiesTable tbody tr").length === 0) {
        loadDefaultChequeProperties(0);
    }


/**
 * Initialize Select2 for all relevant dropdowns
 */
function bindSelect2() {
    const selectIdArray = [
        '#bankAccounts'
    ];

    selectIdArray.forEach(function (id) {
        $(id).select2({
            theme: 'bootstrap-5',
            allowClear: true,
            placeholder: 'Select ...',
            width: $(id).attr('style') && $(id).attr('style').includes('width') ? 'element' : '100%'
        });
    });

    // Handle Enter-key on Select2 search fields
    // Use namespaced event and .off() to prevent duplicate listeners
    $(document).off('select2:open.manual_focus').on('select2:open.manual_focus', function (e) {
        const selectElement = $(e.target);
        const searchInput = selectElement.data('select2').$dropdown.find('.select2-search__field');

        searchInput.off('keydown.select2Enter').on('keydown.select2Enter', function (event) {
            if (event.which === 13) {
                event.preventDefault();
                selectElement.select2('close');
                moveFocusToNextField(selectElement);
            }
        });
    });
}



function loadDefaultChequeProperties(array_count = 0) {
    if (array_count > 0 && typeof chequeProperties !== 'undefined') {
        chequeProperties.forEach(property => {
            addChequePropertiesTableRow(1, property.id, true, property.value, property.top, property.left, property.width, property.height, property.font_size, property.font_name, property.align_text, property.editable, property.font_style);
        });
    } else {
        defaultChequeProperties.forEach(property => {
            addChequePropertiesTableRow(1, null, true, property.value, property.top, property.left, property.width, property.height, property.font_size, property.font_name, property.align_text, property.editable, property.font_style);
        });
    }
}

// Native HTML5 Drag and Drop Implementation
function enableDragAndResize(element) {
    element.setAttribute('draggable', true);

    element.addEventListener('dragstart', function (event) {
        const id = event.target.getAttribute('data-id');
        if (id) {
            event.dataTransfer.setData('text/plain', id);
            event.target.style.opacity = '0.5';
        }
    });

    element.addEventListener('dragend', function (event) {
        event.target.style.opacity = '1';
        const id = event.target.getAttribute('data-id');
        updateFormFields(event.target, id);
    });

    // Resize Logic (Native Mouse Events)
    const resizeHandle = element.querySelector('.resize-handle');
    if (resizeHandle) {
        resizeHandle.addEventListener('mousedown', (event) => {
            event.preventDefault();
            const initialWidth = element.offsetWidth;
            const initialHeight = element.offsetHeight;
            const initialX = event.clientX;
            const initialY = event.clientY;
            const id = element.getAttribute('data-id');

            const onMouseMove = (moveEvent) => {
                const newWidth = initialWidth + (moveEvent.clientX - initialX);
                const newHeight = initialHeight + (moveEvent.clientY - initialY);
                element.style.width = `${newWidth}px`;
                element.style.height = `${newHeight}px`;
                updateFormFields(element, id);
            };

            const onMouseUp = () => {
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
            };

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        });
    }
}

function enableChequeContainerDragAndDrop() {
    const chequeContainer = document.getElementById('chequeContainer');
    if (!chequeContainer) return;

    chequeContainer.ondragover = function (event) {
        event.preventDefault();
    };

    chequeContainer.ondrop = function (event) {
        event.preventDefault();
        const elementId = event.dataTransfer.getData('text/plain');
        const draggableElement = document.querySelector(`.draggable[data-id="${elementId}"]`);

        if (!draggableElement) return;

        const rect = chequeContainer.getBoundingClientRect();
        const offsetX = event.clientX - rect.left - draggableElement.offsetWidth / 2;
        const offsetY = event.clientY - rect.top - draggableElement.offsetHeight / 2;

        draggableElement.style.left = `${Math.max(0, Math.min(rect.width - draggableElement.offsetWidth, offsetX))}px`;
        draggableElement.style.top = `${Math.max(0, Math.min(rect.height - draggableElement.offsetHeight, offsetY))}px`;

        updateFormFields(draggableElement, elementId);
    };
}

function setChequeContainerSize() {
    const chequeHeight = document.getElementById('cheque_height').value;
    const chequeWidth = document.getElementById('cheque_width').value;
    const chequeContainer = document.getElementById('chequeContainer');
    if (chequeContainer) {
        chequeContainer.style.height = `${chequeHeight}px`;
        chequeContainer.style.width = `${chequeWidth}px`;
    }
}

function addChequePropertiesTableRow(number = 1, id = null, confirmed = false, value = '', top = '', left = '', width = '', height = '',
    font_size = 12, font_name = 'Arial', align_text = 'left', editable = false, font_style = 'normal') {
    const uniqueId = id || Math.random().toString(36).substring(2, 15);
    let rowHtml = '';

    for (let i = 0; i < number; i++) {
        rowHtml += `
            <tr class="cheque-properties-row" data-confirmed="${confirmed}" data-id="${uniqueId}" data-editable="${editable}">
                <td>${generateSelect('value[]', ['', 'Date','Account Name','Amount','Amount In Words','A/C Payee'], value)}</td>
                <td><input type="text" name="top[]" class="form-control form-control-sm" value="${top}"></td>
                <td><input type="text" name="left[]" class="form-control form-control-sm" value="${left}"></td>
                <td><input type="text" name="width[]" class="form-control form-control-sm" value="${width}"></td>
                <td><input type="text" name="height[]" class="form-control form-control-sm" value="${height}"></td>
                <td>${generateSelect('align[]', ['left', 'center', 'right'], align_text)}</td>
                <td>${generateSelect('font[]', ['Arial', 'Times New Roman', 'Courier New'], font_name)}</td>
                <td>${generateSelect('font_size[]', ['12', '14', '16', '18', '20'], font_size)}</td>
                <td>${generateSelect('font_style[]', ['normal', 'bold'], font_style)}</td>
                <td class="text-center action-buttons align-middle">
                    <button class="erp-btn-icon view confirm-row" type="button" onclick="confirmChequePropertiesTableRow(this)" title="Confirm"><i class="fa-solid fa-check"></i></button>
                    <button class="erp-btn-icon delete remove-row" type="button" onclick="removeChequePropertiesTableRow(this)" title="Delete">${icons.delete}</button>
                </td>
            </tr>`;
    }

    $('#chequePropertiesTable tbody').append(rowHtml);
    if (confirmed) confirmChequePropertiesTableRow(null, uniqueId);
}

function generateSelect(name, options, selectedValue) {
    return `<select name="${name}" class="form-control form-control-sm">
        ${options.map(option => {
            const val = typeof option === 'object' ? option.value : option;
            const label = typeof option === 'object' ? option.label : option;
            return `<option value="${val}" ${val == selectedValue ? 'selected' : ''}>${label}</option>`;
        }).join('')}
    </select>`;
}

function removeChequePropertiesTableRow(element) {
    const row = $(element).closest('tr');
    Swal.fire({ title: 'Are you sure?', text: 'Delete this property?', icon: 'warning', showCancelButton: true, allowOutsideClick: false, allowEscapeKey: false })
        .then(result => {
            if (result.isConfirmed) {
                removeDraggableElement(row.data('id'));
                row.remove();
            }
        });
}

function confirmChequePropertiesTableRow(element, rowId = null) {
    const row = rowId ? $(`#chequePropertiesTable tbody tr[data-id="${rowId}"]`) : $(element).closest('tr');
    const data = getRowData(row);

    if (!data.value) {
        Swal.fire({ title: 'Warning', text: 'Please select a value.', icon: 'warning', allowOutsideClick: false, allowEscapeKey: false });
        return;
    }
    const editIcon = icons.edit;
    row.find('input, select').attr('readonly', true);
    if (row.find('.edit-row').length === 0) {
        row.find('.action-buttons').prepend(`<button class="erp-btn-icon edit edit-row" type="button" onclick="editChequePropertiesTableRow(this)" title="Edit">${editIcon}</button>`);
    }
    row.find('.confirm-row').hide();
    row.attr('data-confirmed', true);

    addDraggableElement(data.id, data.value, data.top, data.left, data.width, data.height, data.font_size, data.font_name, data.align_text, data.editable, data.font_style);
}

function editChequePropertiesTableRow(element) {
    const row = $(element).closest('tr');
    row.find('input, select').attr('readonly', false);
    row.attr('data-confirmed', false).attr('data-editable', true);
    row.find('.edit-row').remove();
    row.find('.confirm-row').show();
}

function getRowData(row) {
    return {
        id: row.data('id'),
        value: row.find('select[name="value[]"]').val(),
        top: row.find('input[name="top[]"]').val(),
        left: row.find('input[name="left[]"]').val(),
        width: row.find('input[name="width[]"]').val(),
        height: row.find('input[name="height[]"]').val(),
        font_size: row.find('select[name="font_size[]"]').val(),
        font_name: row.find('select[name="font[]"]').val(),
        align_text: row.find('select[name="align[]"]').val(),
        editable: row.attr('data-editable') === 'true',
        font_style: row.find('select[name="font_style[]"]').val()
    };
}

function addDraggableElement(id, value, top, left, width, height, font_size, font_name, align_text, editable, font_style) {
    let draggableElement = document.querySelector(`.draggable[data-id="${id}"]`);

    if (draggableElement) {
        updateDraggableElement(draggableElement, value, top, left, width, height, font_size, font_name, align_text, font_style);
    } else {
        draggableElement = createDraggableElement(id, value, top, left, width, height, font_size, font_name, align_text, font_style);
        document.getElementById('chequeContainer').appendChild(draggableElement);
    }
    enableDragAndResize(draggableElement);
}

function createDraggableElement(id, value, top, left, width, height, font_size, font_name, align_text, font_style) {
    const draggable = document.createElement('div');
    draggable.classList.add('draggable');
    draggable.setAttribute('data-id', id);
    setElementStyles(draggable, top, left, width, height, font_size, font_name, align_text, font_style);
    draggable.innerText = value;

    const resizeHandle = document.createElement('div');
    resizeHandle.classList.add('resize-handle');
    draggable.appendChild(resizeHandle);

    return draggable;
}

function updateDraggableElement(element, value, top, left, width, height, font_size, font_name, align_text, font_style) {
    setElementStyles(element, top, left, width, height, font_size, font_name, align_text, font_style);
    element.innerText = value;

    if (!element.querySelector('.resize-handle')) {
        const resizeHandle = document.createElement('div');
        resizeHandle.classList.add('resize-handle');
        element.appendChild(resizeHandle);
    }
}

function setElementStyles(element, top, left, width, height, font_size, font_name, align_text, font_style) {
    let justifyContent = 'center';
    if (align_text === 'left') justifyContent = 'flex-start';
    if (align_text === 'right') justifyContent = 'flex-end';

    Object.assign(element.style, {
        position: 'absolute',
        top: `${parseFloat(top) * 100}px`,
        left: `${parseFloat(left) * 100}px`,
        width: `${parseFloat(width) * 100}px`,
        height: `${parseFloat(height) * 100}px`,
        fontSize: `${font_size}px`,
        fontFamily: font_name,
        textAlign: align_text,
        fontWeight: font_style === 'bold' ? 'bold' : 'normal',
        display: 'flex',
        alignItems: 'center',
        justifyContent: justifyContent,
        padding: '0 5px',
        overflow: 'hidden',
        whiteSpace: 'nowrap'
    });
}

function removeDraggableElement(id) {
    const draggableElement = document.querySelector(`.draggable[data-id="${id}"]`);
    if (draggableElement) draggableElement.remove();
}

function updateFormFields(element, id) {
    if (!element) return;
    const row = $(`#chequePropertiesTable tbody tr[data-id="${id}"]`);
    if (row.length === 0) return;

    row.find('input[name="top[]"]').val((parseFloat(element.style.top) / 100).toFixed(2));
    row.find('input[name="left[]"]').val((parseFloat(element.style.left) / 100).toFixed(2));
    row.find('input[name="width[]"]').val((parseFloat(element.style.width) / 100).toFixed(2));
    row.find('input[name="height[]"]').val((parseFloat(element.style.height) / 100).toFixed(2));
}

// Form Validation using JustValidate
function initValidation() {
    if (typeof JustValidate === 'undefined') return;

    let toastShown = false;
    const validator = new JustValidate('#chequeFormatForm', {
        validateBeforeSubmitting: true,
        focusInvalidField: true,
        errorLabelCssClass: 'text-danger small mt-1',
    });

    validator
        .addField('#formateName', [{ rule: 'required', errorMessage: 'Name is required' }])
        .addField('#top_margin', [{ rule: 'required', errorMessage: 'Top Margin is required' }])
        .addField('#left_margin', [{ rule: 'required', errorMessage: 'Left Margin is required' }])
        .addField('#cheque_width', [{ rule: 'required', errorMessage: 'Width is required' }])
        .addField('#cheque_height', [{ rule: 'required', errorMessage: 'Height is required' }])
        .onSuccess(function (event) {
            event.preventDefault();
            submitChequeForm(event.target);
        })
        .onFail(() => {
            if (toastShown) return;
            toastShown = true;
            showToast("error", "Please fix the highlighted fields before saving.");
            setTimeout(() => (toastShown = false), 800);
        });
}