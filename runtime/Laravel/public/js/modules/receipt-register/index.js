// Receipt Register — Tabulator module
let rrTable;
let rrCurrentFilter  = {};
let rrPermissions    = {};
let rrGrandTotal     = 0;
let rrFilteredTotal  = 0;

const RR_WIDTH_KEY    = 'receipt_register_col_widths';
const RR_PAGE_SIZE_KEY = 'receipt_register_page_size';
const RR_PAGE_NUM_KEY  = 'receipt_register_page_num';

function rr_today_or_fy_end() {
    const today = new Date().toISOString().slice(0, 10);
    return today <= FINANCIAL_YEAR_END ? today : FINANCIAL_YEAR_END;
}
// ===========================================================
// SELECT2 INIT
// ===========================================================
function bindSelect2() {
    var selectIdArray = [
        "#rr_account_id", "#rr_voucher_no",
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
    new DateInput('#rr_start_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('#rr_end_date',   FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    bindSelect2();

    $('#rr_start_date').val(formatDateToDMY(FINANCIAL_YEAR_START));
    $('#rr_end_date').val(formatDateToDMY(rr_today_or_fy_end()));

    rrCurrentFilter = {
        start_date: FINANCIAL_YEAR_START,
        end_date:   rr_today_or_fy_end(),
    };

    const savedColWidths = JSON.parse(localStorage.getItem(RR_WIDTH_KEY) || '{}');

    rrTable = new Tabulator('#receipt_register_table', {
        height: '550px',
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
        paginationSize: parseInt(localStorage.getItem(RR_PAGE_SIZE_KEY)) || 50,
        paginationInitialPage: parseInt(localStorage.getItem(RR_PAGE_NUM_KEY)) || 1,
        paginationSizeSelector: [10, 30, 50, 100, 150, 200, 250, 300],
        paginationDataSent:     { page: 'page', size: 'size' },
        paginationDataReceived: { last_page: 'last_page', data: 'data', total: 'total' },

        ajaxURL: rrIndexUrl,
        ajaxParams: () => rrCurrentFilter,
        ajaxURLGenerator: (_url, _config, params) => {
            const page = params.page || 1;
            const size = params.size || 100;
            return `${rrIndexUrl}?${new URLSearchParams({ ...rrCurrentFilter, page, size }).toString()}`;
        },
        ajaxResponse: function (_url, _params, response) {
            if (response.permissions) rrPermissions = response.permissions;
            rrFilteredTotal = response.total || 0;
            rrGrandTotal    = response.grand_total || 0;
            return response;
        },
        paginationCounter: function(pageSize, currentRow, _currentPage, _totalRows, _totalPages) {
            const total = rrFilteredTotal;
            if (!total) return "No entries found";
            const start = currentRow;
            const end   = Math.min(currentRow + pageSize - 1, total);
            let text = `Showing ${start} to ${end} of ${total} entries`;
            if (rrGrandTotal > 0 && rrGrandTotal !== total) {
                text += ` (filtered from ${rrGrandTotal} total entries)`;
            }
            return text;
        },
        columns: rrGetColumns(savedColWidths),
    });

    rrTable.on('pageLoaded', function(pageNo) {
        localStorage.setItem(RR_PAGE_NUM_KEY, pageNo);
        localStorage.setItem(RR_PAGE_SIZE_KEY, rrTable.getPageSize());
    });

    rrTable.on('columnResized', function (col) {
        const widths = JSON.parse(localStorage.getItem(RR_WIDTH_KEY) || '{}');
        widths[col.getField()] = col.getWidth();
        localStorage.setItem(RR_WIDTH_KEY, JSON.stringify(widths));
    });

    $('#rr_show_btn').on('click',  rrApplyFilter);
    $('#rr_clear_btn').on('click', rrClearFilter);
    $('#rr_print_btn').on('click', rrPrint);
});

/* ------------------------------------------------------------------ */
/* COLUMNS                                                              */
/* ------------------------------------------------------------------ */
function rrGetColumns(w = {}) {
    return [
        {
            title: 'Date',
            field: 'voucher_date',
            width: 115,
            hozAlign: 'center',
            headerHozAlign: 'center',
            headerSort: false,
            formatter: cell => {
                const value = cell.getValue();
                if (!value) return '';
                const prevRow = cell.getRow().getPrevRow();
                if (prevRow && prevRow.getData().voucher_id === cell.getRow().getData().voucher_id) {
                    return '';
                }
                return formatDateToDMY(value);
            },
        },
        {
            title: 'Vch. No.',
            field: 'voucher_serial',
            width: w.voucher_serial ?? 110,
            hozAlign: 'center',
            headerHozAlign: 'center',
            headerSort: false,
            formatter: cell => {
                const value = cell.getValue();
                if (!value) return '';
                const prevRow = cell.getRow().getPrevRow();
                if (prevRow && prevRow.getData().voucher_id === cell.getRow().getData().voucher_id) {
                    return '';
                }
                return value;
            },
        },
        {
            title: 'Party Name',
            field: 'party_name',
            minWidth: 150,
            headerSort: false,
            formatter: cell => {
                cell.getElement().setAttribute('title', cell.getValue() || '');
                return cell.getValue() || '';
            },
        },
        {
            title: 'Amount',
            field: 'received_amount',
            width: 180,
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
            minWidth: 250,
            headerSort: false,
            formatter: cell => cell.getValue() || '',
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
        {
            title: 'Action',
            field: 'voucher_id',
            width: w.voucher_id ?? 110,
            hozAlign: 'center',
            headerHozAlign: 'center',
            headerSort: false,
            formatter: (cell) => {
                const voucherId  = cell.getValue();
                const canView    = rrPermissions.view   ?? false;
                const canDelete  = rrPermissions.delete ?? false;
                const viewIcon   = icons.view;
                const deleteIcon = icons.delete;

                let actions = '<div class="d-flex gap-2 align-items-center justify-content-center">';
                if (canView) {
                    actions += `<span class="erp-btn-icon rr-view text-primary" data-id="${voucherId}" title="View Voucher">${viewIcon}</span>`;
                }
                if (canDelete) {
                    actions += `<span class="erp-btn-icon rr-delete text-danger" data-id="${voucherId}" title="Delete Voucher">${deleteIcon}</span>`;
                }
                actions += '</div>';
                return actions;
            },
        },
    ];
}

/* ------------------------------------------------------------------ */
/* FILTER                                                               */
/* ------------------------------------------------------------------ */
function rrCollectFilters() {
    return {
        start_date: formatDateToYMD($('#rr_start_date').val().trim()) || '',
        end_date:   formatDateToYMD($('#rr_end_date').val().trim())   || '',
        account_id: $('#rr_account_id').val() || '',
        voucher_no: $('#rr_voucher_no').val() || '',
    };
}

function rrApplyFilter() {
    const f = rrCollectFilters();
    if (!f.start_date || !f.end_date) {
        showToast('error', 'Please select both Start and End dates.');
        return;
    }
    rrCurrentFilter = f;
    if (rrTable) rrTable.setData(rrIndexUrl);
}

function rrClearFilter() {
    $('#rr_start_date').val(formatDateToDMY(FINANCIAL_YEAR_START));
    $('#rr_end_date').val(formatDateToDMY(rr_today_or_fy_end()));
    $('#rr_account_id').val('').trigger('change');
    $('#rr_voucher_no').val('').trigger('change');
    rrCurrentFilter = { start_date: FINANCIAL_YEAR_START, end_date: rr_today_or_fy_end() };
    if (rrTable) rrTable.setData(rrIndexUrl);
}

/* ------------------------------------------------------------------ */
/* PRINT                                                                */
/* ------------------------------------------------------------------ */
function rrPrint() {
    const filters = rrCollectFilters();
    $.ajax({
        url: rrPrintUrl,
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
/* ROW ACTIONS                                                          */
/* ------------------------------------------------------------------ */
$(document).on('click', '.erp-btn-icon.rr-view', function () {
    const voucherId = $(this).data('id');
    if (!voucherId) return;

    $.ajax({
        url: `${rrViewUrl}/${voucherId}`,
        type: 'GET',
        beforeSend: () => showLoader('Loading Receipt Voucher...'),
        success: function (response) {
            hideLoader();
            if (!response.success || !response.data) {
                showToast('error', response.message || 'Failed to load receipt voucher details.');
                return;
            }

            const data    = response.data;
            const voucher = data.voucher;

            $('#view_voucher_date').text(formatDateToDMY(voucher.voucher_date));
            $('#view_voucher_number').text(voucher.voucher_serial || '--');
            $('#view_narration').text(voucher.narration || '--');

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
                const p = voucher.details.find(d => d.is_party_account);
                const c = voucher.details.find(d => (parseFloat(d.credit) || 0) > 0);
                customerName = p?.account_name || c?.account_name || '';
            }

            $('#view_customer_name').text(customerName || '--');
            $('#view_voucher_details_tbody').html(detailsHtml || '<tr><td colspan="4" class="text-center text-muted py-3">No details available.</td></tr>');
            $('#view_total_debit').text(formatIndianNumber(totalDebit));
            $('#view_total_credit').text(formatIndianNumber(totalCredit));

            let breakUpHtml  = '';
            let breakUpIndex = 1;
            (voucher.details || []).forEach((detail) => {
                (detail.refs || []).forEach((ref) => {
                    const method    = ref.method === 'new_ref' ? 'New Ref' : 'Agst Ref';
                    const refAmount = parseFloat(ref.ref_amount) || 0;
                    const drCr      = ref.transaction_type || (parseFloat(detail.debit) > 0 ? 'Dr' : 'Cr');
                    const qtyVal    = ref.qty ? parseFloat(ref.qty) : null;
                    breakUpHtml += `
                        <tr>
                            <td class="text-center font-monospace">${breakUpIndex++}</td>
                            <td>${method}</td>
                            <td>${ref.po_number || ''}</td>
                            <td>${ref.ref_number || ''}</td>
                            <td>${ref.ref_date      ? formatDateToDMY(ref.ref_date)      : ''}</td>
                            <td>${ref.delivery_date ? formatDateToDMY(ref.delivery_date) : ''}</td>
                            <td class="text-center">${drCr}</td>
                            <td>${ref.product     || ''}</td>
                            <td>${ref.destination || ''}</td>
                            <td class="text-end font-monospace">${qtyVal !== null ? formatQty(qtyVal) : ''}</td>
                            <td class="text-end font-monospace">${formatIndianNumber(refAmount)}</td>
                        </tr>`;
                });
            });

            $('#view_bill_break_up_tbody').html(breakUpHtml || '<tr><td colspan="11" class="text-center text-muted py-3">No Bill Break-Up available.</td></tr>');
            $('#receipt_voucher_modal').data('voucher-id', voucherId);
            $('#receipt_voucher_modal').modal('show');
        },
        error: function () {
            hideLoader();
            showToast('error', 'Oops! Something went wrong. Try again later.');
        },
    });
});

$(document).on('click', '.erp-btn-icon.rr-delete', function () {
    const voucherId = $(this).data('id');
    if (!voucherId) return;
    rrDeleteVoucher(voucherId, false);
});

$(document).on('click', '#btn_entry_reverse', function () {
    const voucherId = $('#receipt_voucher_modal').data('voucher-id');
    if (!voucherId) return;
    rrDeleteVoucher(voucherId, true);
});

$(document).on('click', '#btn_print_receipt', function (e) {
    e.preventDefault();
    const id = $('#receipt_voucher_modal').data('voucher-id');
    printReport(`${rrPrintReceiptUrl}/${id}`);
});

function rrDeleteVoucher(voucherId, fromModal = false) {
    Swal.fire({
        title: 'Delete Receipt Voucher?',
        html: `This will permanently delete the voucher and:<br>
               <ul class="mt-2 mb-0" style="display:inline-block;text-align:left;">
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

        const url = rrDeleteUrl.replace(':id', voucherId);
        showLoader('Deleting voucher...');

        $.ajax({
            url: url,
            type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                hideLoader();
                if (res.success) {
                    if (fromModal) $('#receipt_voucher_modal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: res.message || 'Receipt Voucher deleted successfully.',
                        timer: 1800,
                        showConfirmButton: false,
                    }).then(() => {
                        if (rrTable) rrTable.setData(rrIndexUrl);
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Cannot Delete', text: res.message || 'Failed to delete.' });
                }
            },
            error: function (xhr) {
                hideLoader();
                Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed to delete receipt voucher.' });
            },
        });
    });
}

