$(document).ready(function () {
    $('#grn_id').select2({
        theme: "bootstrap-5",
        placeholder: "Select GRN ...",
    });

    $('#grn_id').on('change', function () {
        const grnId = $(this).val();
        if (grnId) getGrn(grnId);
    });

    // Auto-load if a GRN is pre-selected (edit route with ID)
    const preSelected = $('#grn_id').val();
    if (preSelected) {
        getGrn(preSelected);
    }
});

// ── Fetch GRN data from server ─────────────────────────────────────
function getGrn(grnId) {
    if (!grnId) return;

    $.ajax({
        url: getGrnUrl.replace(':id', grnId), // route('grns.edit', ':id') — no ID substitution needed
        type: 'GET',
        data: { grn_id: grnId },  // controller checks $request->has('grn_id')
        beforeSend: function () {
            showLoader('Fetching GRN data…');
        },
        success: function (response) {
            if (response.success && response.data) {
                setData(response.data);
            } else {
                showToast('error', response.message ?? 'Failed to fetch GRN', 5000);
            }
        },
        error: function (xhr) {
            showToast('error', xhr.responseJSON?.message ?? 'Error fetching GRN', 5000);
        },
        complete: function () {
            hideLoader();
        }
    });
}

// ── Populate all form fields from API response ─────────────────────
function setData(data) {

    // ── Master fields ──────────────────────────────────────────────
    $('#grn_date').val(data.grn_date ? formatDateToDMY(data.grn_date) : '');
    $('#grn_in_date').val(data.grn_in_date ? formatDateToDMY(data.grn_in_date) : '');
    $('#party_bill_date').val(data.party_bill_date ? formatDateToDMY(data.party_bill_date) : '');

    $('#reference_number').val(data.reference_number ?? '');
    $('#vehicle_number').val(data.vehicle_number ?? '');
    $('#remarks').val(data.remarks ?? '');

    // Show attachment link if exists
    if (data.url_path) {
        const linkHtml = `<div class="mt-2" id="attachment_link_container"><a href="/storage/grn_attachments/${data.url_path}" target="_blank" class="text-primary"><i class="fa-solid fa-paperclip"></i> View Attachment</a></div>`;
        $('#attachment_link_container').remove(); // Remove existing if any
        $('#url_path').after(linkHtml);
    } else {
        $('#attachment_link_container').remove();
    }

    // ── Account (supplier) — triggers city fill ─────────
    if (data.account_id) {
        $('#account_id').val(data.account_id).trigger('change');
    }

    // ── Item Details (Mobile GRN has a single item) ─────────────────
    const detail = data.details && data.details.length > 0 ? data.details[0] : null;
    if (detail) {
        if (detail.item_id) {
            $('#item_id').val(detail.item_id).trigger('change');
        } else {
            $('#item_id').val('').trigger('change');
        }
        
        if (detail.destination_id) {
            $('#destination_id').val(detail.destination_id).trigger('change');
        } else {
            $('#destination_id').val('').trigger('change');
        }
        
        $('#rate').val(detail.rate ?? '');
        $('#p_qty').val(detail.party_quantity ?? '');
    } else {
        $('#item_id').val('').trigger('change');
        $('#destination_id').val('').trigger('change');
        $('#rate').val('');
        $('#p_qty').val('');
    }
}