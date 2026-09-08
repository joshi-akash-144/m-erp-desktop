// Payment Register — Tabulator module
let prTable;
let prCurrentFilter = {};
let currentPermissions = {};
let grandTotalRecords = 0;
let totalFilteredRecords = 0;


const PR_WIDTH_KEY    = 'payment_register_col_widths';
const PR_PAGE_SIZE_KEY = 'payment_register_page_size';
const PR_PAGE_NUM_KEY  = 'payment_register_page_num';

function today_or_fy_end() {
    const today = new Date().toISOString().slice(0, 10);
    return today <= FINANCIAL_YEAR_END ? today : FINANCIAL_YEAR_END;
}

// ===========================================================
// SELECT2 INIT
// ===========================================================
function bindSelect2() {
    var selectIdArray = [
        "#pr_account_id", "#pr_voucher_no"
    ];

    $.each(selectIdArray, function (i, element) {
        $(element).select2({
            theme: "bootstrap-5",
            allowClear: true,
            placeholder: "Select ...",
            width: null,
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

$(function () {
    new DateInput('#pr_start_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('#pr_end_date',   FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    bindSelect2();

    // No default dates on load
    $('#pr_start_date').val('');
    $('#pr_end_date').val('');

    // Prime filter for initial load
    prCurrentFilter = {
        start_date: '',
        end_date: '',
    };

    const savedColWidths = JSON.parse(localStorage.getItem(PR_WIDTH_KEY) || '{}');

    prTable = new Tabulator('#payment_register_table', {
        height: 'calc(100vh - 280px)',
        layout: 'fitColumns',
        placeholder: `
            <div class="text-center py-5">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="icon text-muted mb-3">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                    <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                    <path d="M10 12l4 4m0 -4l-4 4"/>
                </svg>
                <h3 class="text-muted">No records found</h3>
                <p class="text-muted">Try adjusting your filters</p>
            </div>
        `,

        pagination: true,
        paginationMode: 'remote',
        paginationSize: parseInt(localStorage.getItem(PR_PAGE_SIZE_KEY)) || 50,
        paginationInitialPage: parseInt(localStorage.getItem(PR_PAGE_NUM_KEY)) || 1,
        paginationSizeSelector: [10, 30, 50, 100, 150, 200, 250, 300],
        paginationDataSent:     { page: 'page', size: 'size' },
        paginationDataReceived: { last_page: 'last_page', data: 'data', total: 'total' },

        ajaxURL: prIndexUrl,
        ajaxParams: () => prCurrentFilter,
        ajaxURLGenerator: (url, _config, params) => {
            const page = params.page || 1;
            const size = params.size || 100;
            return `${url}?${new URLSearchParams({ ...prCurrentFilter, page, size }).toString()}`;
        },

        ajaxResponse: function (url, params, response) {
            if (response.permissions) currentPermissions = response.permissions;
            totalFilteredRecords = response.total || 0;
            grandTotalRecords    = response.grand_total || 0;
            return response;
        },

        paginationCounter: function(pageSize, currentRow, _currentPage, _totalRows, _totalPages) {
            const total = totalFilteredRecords;
            if (!total) return "No entries found";
            const start = currentRow;
            const end   = Math.min(currentRow + pageSize - 1, total);
            let text = `Showing ${start} to ${end} of ${total} entries`;
            if (grandTotalRecords > 0 && grandTotalRecords !== total) {
                text += ` (filtered from ${grandTotalRecords} total entries)`;
            }
            return text;
        },

        columns: prGetColumns(savedColWidths),
    });

    prTable.on('pageLoaded', function(pageNo) {
        localStorage.setItem(PR_PAGE_NUM_KEY, pageNo);
        localStorage.setItem(PR_PAGE_SIZE_KEY, prTable.getPageSize());
    });

    // Persist column widths
    prTable.on('columnResized', function (col) {
        const widths = JSON.parse(localStorage.getItem(PR_WIDTH_KEY) || '{}');
        widths[col.getField()] = col.getWidth();
        localStorage.setItem(PR_WIDTH_KEY, JSON.stringify(widths));
    });

    // Buttons
    $('#pr_show_btn').on('click', prApplyFilter);
    $('#pr_clear_btn').on('click', prClearFilter);
    $('#pr_register_print_btn').on('click', prPrint);
});

/* ------------------------------------------------------------------ */
/* COLUMNS                                                              */
/* ------------------------------------------------------------------ */
function prGetColumns(w = {}) {
    return [
        {
            title: 'Date',
            field: 'payment_date',
            width:  120,
            hozAlign: 'center',
            headerHozAlign: 'center',
            headerSort: false,
            formatter: cell => cell.getValue() ? formatDateToDMY(cell.getValue()) : '',
        },
        {
            title: 'Voucher No.',
            field: 'voucher_no',
            width: w.voucher_no ?? 100,
            hozAlign: 'center',
            headerHozAlign: 'center',
            headerSort: false,
            formatter: cell => cell.getValue() || '',
        },
        {
            title: 'File No.',
            field: 'file_number',
            width: w.file_number ?? 100,
            hozAlign: 'center',
            headerHozAlign: 'center',
            headerSort: false,
            formatter: cell => cell.getValue() || '',
        },
        {
            title: 'Party Name',
            field: 'party_name',
            minWidth: 200,
            headerSort: false,
            formatter: cell => {
                const name = cell.getValue() || '';
                const city = cell.getRow().getData().party_city || '';
                const display = city ? `${name} (${city})` : name;
                cell.getElement().setAttribute('title', display);
                return display;
            },
        },
        {
            title: 'Cheque No.',
            field: 'cheque_number',
            width:  130,
            hozAlign: 'center',
            headerHozAlign: 'center',
            headerSort: false,
            formatter: cell => cell.getValue() || '',
        },
        {
            title: 'Amount',
            field: 'paid_amount',
            width: 200,
            hozAlign: 'right',
            headerHozAlign: 'right',
            headerSort: false,
            bottomCalc: 'sum',
            bottomCalcFormatter: cell => formatIndianNumber(cell.getValue()),
            formatter: cell => parseFloat(cell.getValue()) ? formatIndianNumber(cell.getValue()) : '',
        },
        {
            title: 'Narration',
            field: 'narration',
            minWidth: 200,
            headerSort: false,
            formatter: cell => {
                const val = cell.getValue() || '';
                cell.getElement().setAttribute('title', val);
                return val;
            },
        },
        // =======================
        // Created By
        // =======================
        {
        title: "Created By",
        field: "created_by",
        width: 150,
        hozAlign: "center",
        headerHozAlign: "center",
        headerSort: false,
        resizable: true,
        formatter: (cell) => {
            const creatorName = cell.getRow().getData().creator_name || null;
            return creatorName
            ? `<span class="badge bg-danger-lt ">${creatorName}</span>`
            : `<span class="badge bg-cyan-lt">--</span>`;
        },
        },

        // {
        //     title: 'Status',
        //     field: 'payment_deleted_at',
        //     width: w.payment_deleted_at ?? 90,
        //     hozAlign: 'center',
        //     headerHozAlign: 'center',
        //     headerSort: false,
        //     formatter: cell => cell.getValue()
        //         ? `<span style="background:#d63939;color:#fff;font-size:.75rem;font-weight:700;padding:3px 8px;border-radius:4px;">Deleted</span>`
        //         : `<span style="background:#2fb344;color:#fff;font-size:.75rem;font-weight:700;padding:3px 8px;border-radius:4px;">Active</span>`,
        // },
        {
            title: 'Action',
            field: 'voucher_id',
            width: w.voucher_id ?? 110,
            hozAlign: 'center',
            headerHozAlign: 'center',
            headerSort: false,
            formatter: (cell) => {
                const row       = cell.getRow().getData();
                const voucherId = cell.getValue();
                const canView   = currentPermissions.view   ?? false;
                const canDelete = currentPermissions.delete ?? false;
                const viewIcon   = icons.view;
                const deleteIcon = icons.delete;

                let actions = '<div class="d-flex gap-2 align-items-center justify-content-center">';

                if (canView) {
                    actions += `<span class="erp-btn-icon pr-view text-primary" data-id="${voucherId}" title="View Voucher">
                        ${viewIcon}
                    </span>`;
                }
                if (canDelete && !row.payment_deleted_at) {
                    actions += `<span class="erp-btn-icon pr-delete text-danger" data-id="${voucherId}" title="Delete Voucher">
                        ${deleteIcon}
                    </span>`;
                }
                actions += "</div>";
                return actions;
            },
        },
    ];
}

/* ------------------------------------------------------------------ */
/* FILTER                                                               */
/* ------------------------------------------------------------------ */
function prCollectFilters() {
    return {
        start_date:    prNormalizeDate($('#pr_start_date').val().trim()),
        end_date:      prNormalizeDate($('#pr_end_date').val().trim()),
        account_id:    $('#pr_account_id').val()         || '',
        voucher_no:    $('#pr_voucher_no').val()         || '',
        file_number:   $('#pr_file_number').val().trim() || '',
        cheque_number: $('#pr_cheque_number').val().trim() || '',
        with_deleted:  $('#pr_with_deleted').is(':checked') ? 1 : 0,
    };
}

function prApplyFilter() {
    const f = prCollectFilters();

    // Date validation removed as per user request

    prCurrentFilter = f;
    if (prTable) prTable.setData(prIndexUrl);
}

function prClearFilter() {
    $('#pr_start_date').val('');
    $('#pr_end_date').val('');
    $('#pr_account_id').val('').trigger('change');
    $('#pr_voucher_no').val('').trigger('change');
    $('#pr_file_number').val('');
    $('#pr_cheque_number').val('');
    $('#pr_with_deleted').prop('checked', false);

    prCurrentFilter = {
        start_date: '',
        end_date: '',
    };

    if (prTable) prTable.setData(prIndexUrl);
}

/* ------------------------------------------------------------------ */
/* PRINT                                                                */
/* ------------------------------------------------------------------ */
function prPrint() {
    const filters = prCollectFilters();

    $.ajax({
        url: prPrintUrl,
        method: 'GET',
        data: filters,
        beforeSend: () => showLoader('Preparing register print…'),
        success: function (res) {
            hideLoader();
            if (!res.success) {
                Swal.fire({ icon: 'error', title: 'No Data', text: res.message || 'No records found.' });
                return;
            }
            const blob = new Blob([res.data.html], { type: 'text/html' });
            window.open(URL.createObjectURL(blob), '_blank');
        },
        error: function (xhr) {
            hideLoader();
            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Print failed.' });
        },
    });
}

/* ------------------------------------------------------------------ */
/* HELPERS                                                              */
/* ------------------------------------------------------------------ */
function prNormalizeDate(d) {
    if (!d) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(d)) return d;
    if (typeof formatDateToYMD === 'function') return formatDateToYMD(d) || '';
    return d;
}

window.prViewVoucher = function (voucherId) {
    if (!voucherId) return;
    window.open('/payment-vouchers/' + voucherId, '_blank');
};

/* ------------------------------------------------------------------ */
/* ROW ACTIONS                                                          */
/* ------------------------------------------------------------------ */
$(document).on('click', '.erp-btn-icon.pr-view', function () {
    const voucherId = $(this).data('id');
    if (!voucherId) return;

    const url = prViewUrl.replace(':id', voucherId);

    $.ajax({
        url: url,
        type: 'GET',
        beforeSend: function () { showLoader('Loading Payment Voucher...'); },
        success: function (response) {
            hideLoader();
            if (!response.success || !response.data) {
                showToast('error', response.message || 'Failed to load payment voucher details.');
                return;
            }

            const data    = response.data;
            const voucher = data.voucher;

            $('#view_voucher_date').text(formatDateToDMY(voucher.voucher_date));
            $('#view_voucher_number').text(voucher.voucher_serial || '--');
            const poSerial = data.purchase_order_serials;
            // top-level in response.data, not inside voucher
            if (poSerial) {
                $("#purchase_order_serial").text(poSerial);
                $("#purchase_order_serial_wrap").show();
            } else {
                $("#purchase_order_serial_wrap").hide();
            }
            $('#view_narration').text(voucher.narration || '--');

            const secNarration = voucher.secondary_narration || '';
            $('#view_secondary_narration').text(secNarration || '--');
            $('#view_secondary_narration_row').toggle(!!secNarration);

            let detailsHtml = '';
            let totalDebit  = 0;
            let totalCredit = 0;
            let customerName = '';

            (voucher.details || []).forEach((detail, index) => {
                const debit  = parseFloat(detail.debit)  || 0;
                const credit = parseFloat(detail.credit) || 0;
                totalDebit  += debit;
                totalCredit += credit;
                if (detail.is_party_account) customerName = detail.account_name;
                detailsHtml += `
                    <tr>
                        <td class="text-center font-monospace">${index + 1}</td>
                        <td>${detail.account_name || ''}</td>
                        <td class="text-end font-monospace">${debit  > 0 ? formatIndianNumber(debit)  : ''}</td>
                        <td class="text-end font-monospace">${credit > 0 ? formatIndianNumber(credit) : ''}</td>
                    </tr>`;
            });

            if (!customerName && voucher.details?.length) {
                const firstParty = voucher.details.find(d => d.is_party_account);
                const firstDebit = voucher.details.find(d => (parseFloat(d.debit) || 0) > 0);
                customerName = firstParty?.account_name || firstDebit?.account_name || '';
            }

            $('#view_customer_name').text(customerName || '--');
            $('#view_voucher_details_tbody').html(detailsHtml || '<tr><td colspan="4" class="text-center text-muted py-3">No details available.</td></tr>');
            $('#view_total_debit').text(formatIndianNumber(totalDebit));
            $('#view_total_credit').text(formatIndianNumber(totalCredit));

            let breakUpHtml = '';
            let breakUpIndex = 1;
            (voucher.details || []).forEach((detail) => {
                (detail.refs || []).forEach((ref) => {
                    const method   = ref.method === 'new_ref' ? 'New Ref' : 'Agst Ref';
                    const refAmount = parseFloat(ref.ref_amount) || 0;
                    const drCr     = ref.transaction_type || (parseFloat(detail.debit) > 0 ? 'Dr' : 'Cr');
                    const qtyVal   = ref.qty   ? parseFloat(ref.qty)   : null;
                    const pQtyVal  = ref.p_qty ? parseFloat(ref.p_qty) : null;
                    breakUpHtml += `
                        <tr>
                            <td class="text-center font-monospace">${breakUpIndex++}</td>
                            <td>${method}</td>
                            <td>${ref.ref_number || ''}</td>
                            <td>${ref.ref_date   ? formatDateToDMY(ref.ref_date)   : ''}</td>
                            <td>${ref.show_date  ? formatDateToDMY(ref.show_date)  : ''}</td>
                            <td class="text-center">${drCr}</td>
                            <td class="text-end font-monospace">${qtyVal  !== null ? formatQty(qtyVal)  : ''}</td>
                            <td class="text-end font-monospace">${pQtyVal !== null ? formatQty(pQtyVal) : ''}</td>
                            <td class="text-end font-monospace">${formatIndianNumber(refAmount)}</td>
                            <td class="text-end font-monospace">${ref.cd      ? formatIndianNumber(ref.cd)      : '0.00'}</td>
                            <td class="text-end font-monospace">${ref.tds     ? formatIndianNumber(ref.tds)     : '0.00'}</td>
                            <td class="text-end font-monospace">${ref.premium ? formatIndianNumber(ref.premium) : '0.00'}</td>
                        </tr>`;
                });
            });

            $('#view_bill_break_up_tbody').html(breakUpHtml || '<tr><td colspan="13" class="text-center text-muted py-3">No Bill Break-Up available.</td></tr>');

            $('#payment_voucher_modal').data('voucher-id', voucherId);
            $('#payment_voucher_modal').data('payment-voucher-id', data.payment?.id ?? null);
            $('#payment_voucher_modal').modal('show');
        },
        error: function () {
            hideLoader();
            showToast('error', 'Oops! Something went wrong. Try again later.');
        },
    });
});

$(document).on('click', '#btn_print_payment', function (e) {
    e.preventDefault();
    const pvId = $('#payment_voucher_modal').data('payment-voucher-id');
    if (!pvId) {
        showToast('warning', 'No payment voucher record found for printing.');
        return;
    }
    printReport(paymentVoucherAdvicePrintUrl, { payment_voucher_ids: [pvId] });
});

$(document).on('click', '.erp-btn-icon.pr-delete', function () {
    const voucherId = $(this).data('id');
    if (!voucherId) return;

    Swal.fire({
        title: 'Delete Payment Voucher?',
        html: `This will permanently delete the voucher and:<br>
               <ul class="mt-2 mb-0" style="display:inline-block;text-align:left;">
                 <li>Cancel the associated cheque</li>
                 <li>Reverse all reference allocations</li>
                 <li>Reopen any settled references</li>
               </ul><br>
               This action <strong>cannot be undone</strong>.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d63939',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (!result.isConfirmed) return;

        const url = prDeleteUrl.replace(':id', voucherId);
        showLoader('Deleting voucher...');

        $.ajax({
            url: url,
            type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                hideLoader();
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: res.message || 'Payment Voucher deleted successfully.',
                        timer: 1800,
                        showConfirmButton: false,
                    }).then(() => {
                        if (prTable) prTable.setData(prIndexUrl);
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Cannot Delete', text: res.message || 'Failed to delete.' });
                }
            },
            error: function (xhr) {
                hideLoader();
                const msg = xhr.responseJSON?.message || 'Failed to delete payment voucher.';
                Swal.fire({ icon: 'error', title: 'Error', text: msg });
            },
        });
    });
});
