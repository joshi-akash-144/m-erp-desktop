// Trial Balance — Tabulator module
/* global tbIndexUrl, tbPrintUrl, tbExcelUrl, tbSelectGroupUrl, tbGroupPrintUrl, tbGroupExcelUrl, tbGroupBalanceUrl, tbGroupDetailUrl, tbAccountMonthWiseUrl, tbAccountLedgerUrl, tbAccountMonthWisePrint, tbAccountMonthWiseExcel, tbAccountLedgerPrint, tbAccountLedgerExcel */
let tbTable;
let tbCurrentFilter = {};
let tbGrandTotal    = {};
let tbDifference    = { debit: 0, credit: 0 };

// Group-wise modal tables
let gwTable;
let gwdTable;

// Month-wise summary modal table
let mwsTable;
let mwsCurrentAccountId   = null;
let mwsCurrentAccountName = null;
let mwsCurrentFromDate    = null;
let mwsCurrentToDate      = null;

// Ledger detail modal table
let ldrTable;
let ldrCurrentAccountId   = null;
let ldrCurrentAccountName = null;
let ldrCurrentFromDate    = null;
let ldrCurrentToDate      = null;

function tbToday() {
    const today = new Date().toISOString().slice(0, 10);
    return today <= FINANCIAL_YEAR_END ? today : FINANCIAL_YEAR_END;
}

$(function () {
    new DateInput('#tb_as_on_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('#tb_from_date',  FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('#tb_to_date',    FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('#ldr_from_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('#ldr_to_date',   FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    bindSelect2();


    $('#tb_as_on_date').val(formatDateToDMY(tbToday()));
    $('#tb_from_date').val(formatDateToDMY(FINANCIAL_YEAR_START));
    $('#tb_to_date').val(formatDateToDMY(tbToday()));

    tbToggleFilterUI();
    tbCurrentFilter = tbCollectFilters();

    tbTable = new Tabulator('#trial_balance_table', {
        height: 'calc(100vh - 340px)',
        layout: 'fitColumns',
        data: [],
        columns: tbBuildColumns('group_wise', 'balance', 'no'),
        placeholder: `
            <div class="text-center py-5">
                <i class="fa-solid fa-scale-balanced fa-3x text-muted mb-3"></i>
                <h3 class="text-muted">No records found</h3>
                <p class="text-muted">Try adjusting your filters</p>
            </div>`,

        rowFormatter(row) {
            const rt = tbCurrentFilter.report_type;
            if (rt === 'group_wise' || rt === 'alphabetic') {
                row.getElement().style.cursor = 'pointer';
            }
        },
    });

    // Tabulator v5: row click must use event listener, not constructor option
    tbTable.on('rowClick', function (_e, row) {
        const data = row.getData();
        if (tbCurrentFilter.report_type === 'group_wise') {
            tbOpenGroupWise(data.account_group_id, data.account_group_name);
        } else if (tbCurrentFilter.report_type === 'alphabetic') {
            const fromDate = tbCurrentFilter.view_type === 'detail'
                ? tbCurrentFilter.from_date
                : FINANCIAL_YEAR_START;
            const toDate = tbCurrentFilter.view_type === 'detail'
                ? tbCurrentFilter.to_date
                : tbCurrentFilter.as_on_date;
            tbOpenMonthWise(data.account_id, data.account_name, fromDate, toDate);
        }
    });

    tbLoadData();

    $('#tb_report_type, #tb_view_type').on('change', function () {
        tbToggleFilterUI();
    });

    $('#tb_search').on('input', function () {
        if (!tbTable) return;
        const q     = $(this).val().trim();
        const field = tbCurrentFilter.report_type === 'alphabetic' ? 'account_name' : 'account_group_name';
        $('#tb_search_clear').toggle(q.length > 0);
        q ? tbTable.setFilter(field, 'like', q) : tbTable.clearFilter();
    });

    $('#tb_search_clear').on('click', function () {
        $('#tb_search').val('').trigger('input');
    });

    $('#tb_show_btn').on('click',  tbApplyFilter);
    $('#tb_clear_btn').on('click', tbClearFilter);
    $('#tb_print_btn').on('click', e => { e.preventDefault(); tbPrint(); });
    $('#tb_excel_btn').on('click', e => { e.preventDefault(); tbExport(); });

    $('#gw_print_btn').on('click', e => { e.preventDefault(); gwPrint(); });
    $('#gw_excel_btn').on('click', e => { e.preventDefault(); gwExport(); });
    
    $('#gwd_print_btn').on('click', e => { e.preventDefault(); gwdPrint(); });
    $('#gwd_excel_btn').on('click', e => { e.preventDefault(); gwdExport(); });

    $('#gw_search').on('input', function () {
        if (!gwTable) return;
        const q = $(this).val().toLowerCase().trim();
        q ? gwTable.setFilter('account_name', 'like', q) : gwTable.clearFilter();
    });

    $('#group_wise_modal').on('hidden.bs.modal', function () {
        $('#gw_search').val('');
        if (gwTable) gwTable.clearFilter();
        $('#gw_totals_bar').hide();
        $('#gw_date_badge').hide();
    });

    // Tabulator computes column widths/row layout when the table is built or given data.
    // If that happens while the Bootstrap modal is still display:none, it lays out at zero
    // width and renders no rows (the plain-text totals footer is unaffected, since it's not
    // part of the Tabulator instance) — redraw once the modal is actually visible to fix it.
    $('#group_wise_modal').on('shown.bs.modal', function () {
        if (gwTable) gwTable.redraw(true);
    });

    $('#gwd_search').on('input', function () {
        if (!gwdTable) return;
        const q = $(this).val().toLowerCase().trim();
        q ? gwdTable.setFilter('account_name', 'like', q) : gwdTable.clearFilter();
    });

    $('#group_wise_detail_modal').on('hidden.bs.modal', function () {
        $('#gwd_search').val('');
        if (gwdTable) gwdTable.clearFilter();
    });

    $('#group_wise_detail_modal').on('shown.bs.modal', function () {
        if (gwdTable) gwdTable.redraw(true);
    });

    $('#month_wise_summary_modal').on('hidden.bs.modal', function () {
        $('#mws_date_badge').hide();
        $('#mws_totals_bar').hide();
    });

    $('#month_wise_summary_modal').on('shown.bs.modal', function () {
        if (mwsTable) mwsTable.redraw(true);
    });

    $('#ledger_detail_modal').on('hidden.bs.modal', function () {
        $('#ldr_date_badge').hide();
        $('#ldr_totals_bar').hide();
    });

    $('#ledger_detail_modal').on('shown.bs.modal', function () {
        if (ldrTable) ldrTable.redraw(true);
    });

    $('#ldr_show_btn').on('click', function () {
        const from = tbNormalizeDate($('#ldr_from_date').val().trim());
        const to   = tbNormalizeDate($('#ldr_to_date').val().trim());
        if (!from || !to) { showToast('error', 'Please select both dates.'); return; }
        ldrLoadData(ldrCurrentAccountId, ldrCurrentAccountName, from, to);
    });

    $('#mws_print_btn').on('click', e => { e.preventDefault(); mwsPrint(); });
    $('#mws_excel_btn').on('click', e => { e.preventDefault(); mwsExport(); });
    $('#ldr_print_btn').on('click', e => { e.preventDefault(); ldrPrint(); });
    $('#ldr_excel_btn').on('click', e => { e.preventDefault(); ldrExport(); });

    // Escape key: close only the topmost visible modal (highest z-index).
    // All modals have data-bs-keyboard="false" so Bootstrap won't handle it itself.
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        const openModals = Array.from(document.querySelectorAll('.modal.show'));
        if (!openModals.length) return;
        e.preventDefault();
        const topModal = openModals.reduce((a, b) =>
            (parseInt(getComputedStyle(b).zIndex) || 0) > (parseInt(getComputedStyle(a).zIndex) || 0) ? b : a
        );
        bootstrap.Modal.getInstance(topModal)?.hide();
    }, true); // capture phase so it runs before any other handler
});


function bindSelect2() {
    const selectIdArray = ['#tb_report_type', '#tb_view_type','#tb_parent_group'];
    selectIdArray.forEach(element => {
        $(element).select2({
            theme: "bootstrap-5",
            // allowClear: true,
            placeholder: "Select " + element.replace('#', '').replace('.', '').replace('_id', '') + " ...",
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
/* ------------------------------------------------------------------ */
/* FILTER UI TOGGLING                                                   */
/* ------------------------------------------------------------------ */
function tbToggleFilterUI() {
    const reportType = $('#tb_report_type').val();
    const viewType    = $('#tb_view_type').val();

    if (reportType === 'alphabetic') {
        $('#tb_parent_group_wrap').show();
    } else {
        $('#tb_parent_group_wrap').hide();
    }

    if (viewType === 'detail') {
        $('#tb_single_date_group').hide();
        $('#tb_range_date_group').show();
        $('#tb_range_date_group_to').show();
    } else {
        $('#tb_range_date_group').hide();
        $('#tb_range_date_group_to').hide();
        $('#tb_single_date_group').show();
        $('#tb_single_date_label').text(reportType === 'alphabetic' ? 'As On Date' : 'End Of Date');
    }
}

/* ------------------------------------------------------------------ */
/* TABLE COLUMNS (per report_type x view_type combination)              */
/* ------------------------------------------------------------------ */
function tbAmountColumn(title, field, signed) {
    return {
        title,
        field,
        width: 220,
        hozAlign: 'right',
        headerHozAlign: 'right',
        headerSort: false,
        formatter: cell => {
            const value = parseFloat(cell.getValue()) || 0;
            if (!signed) return `<span class="text-dark">${formatIndianNumber(value)}</span>`;
            if (value > 0) return `<span class="text-dark">${formatIndianNumber(value)} Dr</span>`;
            if (value < 0) return `<span class="text-dark">${formatIndianNumber(Math.abs(value))} Cr</span>`;
            return `<span class="text-dark">0.00</span>`;
        },
        bottomCalc: 'sum',
        bottomCalcFormatter: cell => {
            const value = parseFloat(cell.getValue()) || 0;
            if (!signed) return `<span class="text-dark">${formatIndianNumber(value)}</span>`;
            if (value > 0) return `<span class="text-dark">${formatIndianNumber(value)} Dr</span>`;
            if (value < 0) return `<span class="text-dark">${formatIndianNumber(Math.abs(value))} Cr</span>`;
            return `<span class="text-dark">0.00</span>`;
        },
    };
}

function tbBuildColumns(reportType, viewType, parentGroup) {
    const labelField = reportType === 'alphabetic' ? 'account_name' : 'account_group_name';
    const labelTitle = reportType === 'alphabetic' ? 'Account Name' : 'Account Group';

    const columns = [
        {
            title: 'Sr No.',
            formatter: 'rownum',
            width: 70,
            hozAlign: 'center',
            headerHozAlign: 'center',
            headerSort: false,
        },
        {
            title: labelTitle,
            field: labelField,
            minWidth: 250,
            headerSort: true,
            formatter: cell => {
                cell.getElement().setAttribute('title', cell.getValue() || '');
                return `<span class="text-primary fw-semibold">${cell.getValue() || ''}</span>`;
            },
        },
    ];

    if (reportType === 'alphabetic' && parentGroup === 'yes') {
        columns.push({
            title: 'Account Group',
            field: 'account_group_name',
            minWidth: 200,
            headerSort: true,
            formatter: cell => {
                cell.getElement().setAttribute('title', cell.getValue() || '');
                return cell.getValue() || '';
            },
        });
    }

    if (viewType === 'detail') {
        columns.push(tbAmountColumn('Opening (₹)', 'opening', true));
    }
    columns.push(tbAmountColumn('Debit (₹)',  'debit',  false));
    columns.push(tbAmountColumn('Credit (₹)', 'credit', false));
    if (viewType === 'detail') {
        columns.push(tbAmountColumn('Closing (₹)', 'closing', true));
    }

    return columns;
}

/* ------------------------------------------------------------------ */
/* DATE BADGE                                                           */
/* ------------------------------------------------------------------ */
function tbUpdateDateBadge() {
    const f = tbCurrentFilter;
    let text;
    if (f.view_type === 'detail') {
        const from = typeof formatDateToDMY === 'function' ? formatDateToDMY(f.from_date) : (f.from_date || '');
        const to   = typeof formatDateToDMY === 'function' ? formatDateToDMY(f.to_date)   : (f.to_date   || '');
        text = `${from} — ${to}`;
    } else {
        const date = typeof formatDateToDMY === 'function' ? formatDateToDMY(f.as_on_date) : (f.as_on_date || '');
        text = `End of Date: ${date}`;
    }
    $('#tb_date_badge_text').text(text);
}

/* ------------------------------------------------------------------ */
/* DATA LOAD (single AJAX call, no pagination)                          */
/* ------------------------------------------------------------------ */
function tbLoadData() {
    $('#tb_search').val('');
    $('#tb_search_clear').hide();
    if (tbTable) tbTable.clearFilter();
    tbUpdateDateBadge();

    showLoader('Loading trial balance…');

    $.ajax({
        url: tbIndexUrl,
        method: 'GET',
        data: tbCurrentFilter,
        success(res) {
            hideLoader();
            const payload = res.data || {};
            tbGrandTotal  = payload.grand_total || {};
            tbDifference  = payload.difference  || { debit: 0, credit: 0 };

            const reportType  = tbCurrentFilter.report_type;
            const viewType    = tbCurrentFilter.view_type;
            const parentGroup = tbCurrentFilter.parent_group;

            tbTable.setColumns(tbBuildColumns(reportType, viewType, parentGroup));

            const labelField = reportType === 'alphabetic' ? 'account_name' : 'account_group_name';
            const rows = (payload.data || []).sort((a, b) => {
                if (reportType === 'alphabetic' && parentGroup === 'yes') {
                    return a.account_name;
                }
                return (a[labelField] || '').localeCompare(b[labelField] || '');
            });

            tbTable.setData(rows);

            tbRenderTotals(viewType);
        },
        error(xhr) {
            hideLoader();
            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed to load data.' });
        },
    });
}

/* ------------------------------------------------------------------ */
/* FILTER                                                               */
/* ------------------------------------------------------------------ */
function tbCollectFilters() {
    const reportType  = $('#tb_report_type').val();
    const viewType    = $('#tb_view_type').val();
    const parentGroup = $('#tb_parent_group').val();

    const filters = { report_type: reportType, view_type: viewType, parent_group: parentGroup };

    if (viewType === 'detail') {
        filters.from_date = tbNormalizeDate($('#tb_from_date').val().trim());
        filters.to_date   = tbNormalizeDate($('#tb_to_date').val().trim());
    } else {
        filters.as_on_date = tbNormalizeDate($('#tb_as_on_date').val().trim());
    }

    return filters;
}

function tbApplyFilter() {
    const f = tbCollectFilters();

    if (f.view_type === 'detail' && (!f.from_date || !f.to_date)) {
        showToast('error', 'Please select both From and To dates.');
        return;
    }
    if (f.view_type !== 'detail' && !f.as_on_date) {
        showToast('error', 'Please select a date.');
        return;
    }

    tbCurrentFilter = f;
    tbLoadData();
}

function tbClearFilter() {
    $('#tb_report_type').val('group_wise');
    $('#tb_view_type').val('balance_only');
    $('#tb_parent_group').val('no');
    $('#tb_as_on_date').val(formatDateToDMY(tbToday()));
    $('#tb_from_date').val(formatDateToDMY(FINANCIAL_YEAR_START));
    $('#tb_to_date').val(formatDateToDMY(tbToday()));
    tbToggleFilterUI();

    tbCurrentFilter = {
        report_type: 'group_wise',
        view_type: 'balance_only',
        parent_group: 'no',
        as_on_date: tbToday(),
    };
    tbLoadData();
}

/* ------------------------------------------------------------------ */
/* TOTALS BAR                                                           */
/* ------------------------------------------------------------------ */
function tbRenderTotals(viewType) {
    if (viewType === 'detail') {
        $('#tb_total_opening_wrap, #tb_total_closing_wrap').show();
        $('#tb_total_opening').text(formatIndianNumber(tbGrandTotal.total_opening || 0));
        $('#tb_total_closing').text(formatIndianNumber(tbGrandTotal.total_closing || 0));
    } else {
        $('#tb_total_opening_wrap, #tb_total_closing_wrap').hide();
    }

    const totalDebit  = tbGrandTotal.total_debit  || 0;
    const totalCredit = tbGrandTotal.total_credit || 0;
    $('#tb_total_debit').text(formatIndianNumber(totalDebit));
    $('#tb_total_credit').text(formatIndianNumber(totalCredit));

    const diff = Math.round(Math.abs(totalDebit - totalCredit) * 100) / 100;
    if (diff > 0) {
        const isDr = totalDebit > totalCredit;
        $('#tb_diff_amount').text(formatIndianNumber(diff));
        $('#tb_diff_label').text(isDr ? 'Dr' : 'Cr');
        $('#tb_totals_bar').show();
        $('#tb_diff_bar').show();
    } else {
        $('#tb_totals_bar').hide();
        $('#tb_diff_bar').hide();
    }
}

/* ------------------------------------------------------------------ */
/* GROUP WISE — BALANCE ONLY DRILL-DOWN (existing modal)                */
/* ------------------------------------------------------------------ */
function tbOpenGroupWise(groupId, groupName) {
    const isDetail = tbCurrentFilter.view_type === 'detail';

    if (isDetail) {
        // Detail modal keeps inner-spinner approach (#gwd_inner_loader exists in that modal).
        $('#gwd_modal_title').text(groupName || '');
        $('#group_wise_trial_balance_detail_table').css('display', 'none');
        $('#gwd_inner_loader').css('display', 'flex');
        $('#group_wise_detail_modal').modal('show');

        $.ajax({
            url: tbSelectGroupUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { group_id: groupId },
            success() { gwdLoadData(groupName); },
            error(xhr) {
                $('#gwd_inner_loader').css('display', 'none');
                $('#group_wise_detail_modal').modal('hide');
                Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed to select group.' });
            },
        });
    } else {
        // Balance modal: use global loader, show modal only after data is ready (same as Opening Trial Balance).
        showLoader('Loading group data…');

        $.ajax({
            url: tbSelectGroupUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { group_id: groupId },
            success() { gwLoadData(groupName); },
            error(xhr) {
                hideLoader();
                Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed to select group.' });
            },
        });
    }
}

function gwLoadData(groupName) {
    $.ajax({
        url: tbGroupBalanceUrl,
        method: 'GET',
        data: { as_on_date: tbCurrentFilter.as_on_date, view_type: tbCurrentFilter.view_type },
        success(res) {
            hideLoader();

            if (!res.success) {
                Swal.fire({ icon: 'error', title: 'No Data', text: res.message || 'No accounts found in this group.' });
                return;
            }

            const data   = res.data  || {};
            const rows   = data.data || [];
            const totals = data.grand_total || {};

            $('#gw_modal_title').text(groupName || data.group_name || 'Group Wise Trial Balance');

            if (tbCurrentFilter.as_on_date) {
                const label = typeof formatDateToDMY === 'function'
                    ? formatDateToDMY(tbCurrentFilter.as_on_date)
                    : tbCurrentFilter.as_on_date;
                $('#gw_date_text').text(`End of Date: ${label}`);
                $('#gw_date_badge').show();
            } else {
                $('#gw_date_badge').hide();
            }

            gwBuildTable(rows);

            const totalDebit  = totals.total_debit  || 0;
            const totalCredit = totals.total_credit || 0;
            const netBalance  = totalDebit - totalCredit;
            const isDr        = netBalance >= 0;
            $('#gw_footer_group_name').text(groupName || data.group_name || '—');
            $('#gw_total_debit').text(formatIndianNumber(totalDebit));
            $('#gw_total_credit').text(formatIndianNumber(totalCredit));
            $('#gw_net_balance').text(`${formatIndianNumber(Math.abs(netBalance))} ${isDr ? 'Dr' : 'Cr'}`);
            $('#gw_totals_bar').css('display', 'block');

            $('#group_wise_modal').modal('show');
        },
        error(xhr) {
            hideLoader();
            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed to load group data.' });
        },
    });
}

function gwBuildTable(rows) {
    rows.sort((a, b) => (a.account_name || '').localeCompare(b.account_name || ''));

    if (gwTable) {
        gwTable.setData(rows);
        return;
    }

    gwTable = new Tabulator('#group_wise_trial_balance_table', {
        data: rows,
        layout: 'fitColumns',
        height: '400px',
        placeholder: `
            <div class="text-center py-4">
                <i class="fa-solid fa-folder-open fa-2x text-muted mb-2"></i>
                <p class="text-muted mb-0">No accounts found in this group</p>
            </div>`,
        rowFormatter(row) {
            row.getElement().style.cursor = 'pointer';
        },
        columns: [
            {
                title: 'Sr No.',
                formatter: 'rownum',
                width: 60,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
            },
            {
                title: 'Account Name',
                field: 'account_name',
                minWidth: 220,
                headerSort: true,
                formatter: cell => {
                    cell.getElement().setAttribute('title', cell.getValue() || '');
                    return `<span class="text-primary fw-semibold">${cell.getValue() || ''}</span>`;
                },
            },
            tbAmountColumn('Debit (₹)',  'debit',  false),
            tbAmountColumn('Credit (₹)', 'credit', false),
        ],
    });

    gwTable.on('rowClick', function (_e, row) {
        const d = row.getData();
        tbOpenMonthWise(d.account_id, d.account_name, FINANCIAL_YEAR_START, tbCurrentFilter.as_on_date);
    });
}

function gwPrint() {
    printReport(tbGroupPrintUrl, { as_on_date: tbCurrentFilter.as_on_date, view_type: tbCurrentFilter.view_type, format: 'print', orientation: 'portrait' });
}

function gwExport() {
    downloadExcel(tbGroupExcelUrl, { as_on_date: tbCurrentFilter.as_on_date, view_type: tbCurrentFilter.view_type });
}

/* ------------------------------------------------------------------ */
/* GROUP WISE — DETAIL DRILL-DOWN (new modal)                           */
/* ------------------------------------------------------------------ */
function gwdLoadData(groupName) {
    $.ajax({
        url: tbGroupDetailUrl,
        method: 'GET',
        data: { from_date: tbCurrentFilter.from_date, to_date: tbCurrentFilter.to_date },
        success(res) {
            $('#gwd_inner_loader').css('display', 'none');
            $('#group_wise_trial_balance_detail_table').css('display', 'block');

            if (!res.success) {
                $('#group_wise_detail_modal').modal('hide');
                Swal.fire({ icon: 'error', title: 'No Data', text: res.message || 'No accounts found in this group.' });
                return;
            }

            const data = res.data  || {};
            const rows = data.data || [];

            $('#gwd_modal_title').text((groupName || data.group_name || 'Group Wise Trial Balance') + ' — Detail');

            const from = typeof formatDateToDMY === 'function' ? formatDateToDMY(tbCurrentFilter.from_date) : tbCurrentFilter.from_date;
            const to   = typeof formatDateToDMY === 'function' ? formatDateToDMY(tbCurrentFilter.to_date)   : tbCurrentFilter.to_date;
            $('#gwd_date_badge').text(`${from} — ${to}`).show();

            gwdBuildTable(rows);
        },
        error(xhr) {
            $('#gwd_inner_loader').css('display', 'none');
            $('#group_wise_detail_modal').modal('hide');
            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed to load group data.' });
        },
    });
}

function gwdBuildTable(rows) {
    rows.sort((a, b) => (a.account_name || '').localeCompare(b.account_name || ''));

    if (gwdTable) {
        gwdTable.setData(rows);
        return;
    }

    gwdTable = new Tabulator('#group_wise_trial_balance_detail_table', {
        data: rows,
        layout: 'fitColumns',
        height: '400px',
        placeholder: `
            <div class="text-center py-4">
                <i class="fa-solid fa-folder-open fa-2x text-muted mb-2"></i>
                <p class="text-muted mb-0">No accounts found in this group</p>
            </div>`,
        rowFormatter(row) {
            row.getElement().style.cursor = 'pointer';
        },
        columns: [
            {
                title: 'Sr No.',
                formatter: 'rownum',
                width: 60,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
            },
            {
                title: 'Account Name',
                field: 'account_name',
                minWidth: 180,
                headerSort: true,
                formatter: cell => {
                    cell.getElement().setAttribute('title', cell.getValue() || '');
                    return `<span class="text-primary fw-semibold">${cell.getValue() || ''}</span>`;
                },
            },
            tbAmountColumn('Opening (₹)', 'opening', true),
            tbAmountColumn('Debit (₹)',   'debit',   false),
            tbAmountColumn('Credit (₹)',  'credit',  false),
            tbAmountColumn('Closing (₹)', 'closing', true),
        ],
    });

    gwdTable.on('rowClick', function (_e, row) {
        const d = row.getData();
        tbOpenMonthWise(d.account_id, d.account_name, tbCurrentFilter.from_date, tbCurrentFilter.to_date);
    });
}

function gwdPrint() {
    printReport(tbGroupDetailPrintUrl, { from_date: tbCurrentFilter.from_date, to_date: tbCurrentFilter.to_date, format: 'print', orientation: 'portrait' });
}

function gwdExport() {
    downloadExcel(tbGroupDetailExcelUrl, { from_date: tbCurrentFilter.from_date, to_date: tbCurrentFilter.to_date });
}

/* ------------------------------------------------------------------ */
/* PRINT / EXPORT — MAIN LIST                                           */
/* ------------------------------------------------------------------ */
function tbPrint() {
    const f = tbCollectFilters();
    printReport(tbPrintUrl, { ...f, format: 'print', orientation: 'portrait' });
}

function tbExport() {
    const f = tbCollectFilters();
    downloadExcel(tbExcelUrl, f);
}

/* ------------------------------------------------------------------ */
/* MONTH WISE SUMMARY MODAL                                             */
/* ------------------------------------------------------------------ */
function tbOpenMonthWise(accountId, accountName, fromDate, toDate) {
    mwsCurrentAccountId   = accountId;
    mwsCurrentAccountName = accountName;
    mwsCurrentFromDate    = fromDate;
    mwsCurrentToDate      = toDate;
    $('#mws_modal_title').text(accountName || '');
    $('#month_wise_summary_table').css('display', 'none');
    $('#mws_inner_loader').css('display', 'flex');
    $('#mws_totals_bar').hide();
    $('#month_wise_summary_modal').modal('show');
    mwsLoadData(accountId, accountName, fromDate, toDate);
}

function mwsLoadData(accountId, accountName, fromDate, toDate) {
    $.ajax({
        url: tbAccountMonthWiseUrl,
        method: 'GET',
        data: { account_id: accountId, from_date: fromDate, to_date: toDate },
        success(res) {
            $('#mws_inner_loader').css('display', 'none');
            $('#month_wise_summary_table').css('display', 'block');

            if (!res.success) {
                $('#month_wise_summary_modal').modal('hide');
                Swal.fire({ icon: 'error', title: 'No Data', text: res.message || 'No data found.' });
                return;
            }

            const data   = res.data  || {};
            const months = data.months || [];
            const totals = data.grand_total || {};

            $('#mws_modal_title').text(data.account_name || accountName || 'Month Wise Summary');

            const from = typeof formatDateToDMY === 'function' ? formatDateToDMY(fromDate) : fromDate;
            const to   = typeof formatDateToDMY === 'function' ? formatDateToDMY(toDate)   : toDate;
            $('#mws_date_text').text(`${from} — ${to}`);
            $('#mws_date_badge').show();

            mwsBuildTable(months);

            const opening = data.opening_balance || 0;
            const closing = data.closing_balance || 0;

            $('#mws_opening_balance').text(
                opening !== 0 ? `${formatIndianNumber(Math.abs(opening))} ${opening >= 0 ? 'Dr' : 'Cr'}` : '0.00'
            );
            $('#mws_total_debit').text(formatIndianNumber(totals.total_debit  || 0));
            $('#mws_total_credit').text(formatIndianNumber(totals.total_credit || 0));
            $('#mws_closing_balance').text(
                closing !== 0 ? `${formatIndianNumber(Math.abs(closing))} ${closing >= 0 ? 'Dr' : 'Cr'}` : '0.00'
            );

            $('#mws_totals_bar').show();
        },
        error(xhr) {
            $('#mws_inner_loader').css('display', 'none');
            $('#month_wise_summary_modal').modal('hide');
            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed to load data.' });
        },
    });
}

// Like tbAmountColumn but without bottomCalc and without Dr/Cr color coding.
// Opening/closing are carry-forward values — summing them is meaningless, and
// coloring every row red/green adds visual noise in a month-wise context.
function mwsAmountCol(title, field, signed) {
    const col = tbAmountColumn(title, field, signed);
    delete col.bottomCalc;
    delete col.bottomCalcFormatter;
    if (signed) {
        col.formatter = cell => {
            const value = parseFloat(cell.getValue()) || 0;
            if (value > 0) return `<span class="text-dark">${formatIndianNumber(value)} Dr</span>`;
            if (value < 0) return `<span class="text-dark">${formatIndianNumber(Math.abs(value))} Cr</span>`;
            return `<span class="text-dark">0.00</span>`;
        };
    }
    return col;
}

function mwsBuildTable(rows) {
    if (mwsTable) {
        mwsTable.setData(rows);
        return;
    }

    mwsTable = new Tabulator('#month_wise_summary_table', {
        data: rows,
        layout: 'fitColumns',
        height: '450px',
        placeholder: `
            <div class="text-center py-4">
                <i class="fa-solid fa-calendar-xmark fa-2x text-muted mb-2"></i>
                <p class="text-muted mb-0">No transactions found for this period</p>
            </div>`,
        rowFormatter(row) {
            row.getElement().style.cursor = 'pointer';
        },
        columns: [
            {
                title: 'Sr No.',
                formatter: 'rownum',
                width: 60,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
            },
            {
                title: 'Month',
                field: 'month_label',
                minWidth: 200,
                headerSort: false,
                formatter: cell => `<span class="text-primary fw-semibold">${cell.getValue() || ''}</span>`,
            },
            {
                title: 'Period',
                field: 'from_date',
                minWidth: 200,
                headerSort: false,
                formatter: cell => {
                    const d = cell.getRow().getData();
                    return `<span class="text-muted small">${d.from_date} — ${d.to_date}</span>`;
                },
            },
            mwsAmountCol('Opening (₹)', 'opening', true),
            tbAmountColumn('Debit (₹)',   'debit',   false),
            tbAmountColumn('Credit (₹)',  'credit',  false),
            mwsAmountCol('Closing (₹)', 'closing', true),
        ],
    });

    mwsTable.on('rowClick', function (_e, row) {
        const d = row.getData();
        tbOpenLedger(mwsCurrentAccountId, mwsCurrentAccountName, d.from_date_raw, d.to_date_raw);
    });
}

/* ------------------------------------------------------------------ */
/* ACCOUNT LEDGER MODAL                                                 */
/* ------------------------------------------------------------------ */
function tbOpenLedger(accountId, accountName, fromDate, toDate) {
    ldrCurrentAccountId   = accountId;
    ldrCurrentAccountName = accountName;

    $('#ldr_modal_title').text(accountName || '');
    $('#ldr_date_badge').hide();
    $('#ldr_opening_bar').hide();
    $('#ldr_closing_bar').hide();
    $('#ldr_totals_bar').hide();
    $('#ledger_detail_table').css('display', 'none');
    $('#ldr_inner_loader').css('display', 'flex');

    // Pre-fill date inputs with the clicked range
    $('#ldr_from_date').val(typeof formatDateToDMY === 'function' ? formatDateToDMY(fromDate) : fromDate);
    $('#ldr_to_date').val(typeof formatDateToDMY === 'function' ? formatDateToDMY(toDate)   : toDate);

    $('#ledger_detail_modal').modal('show');
    ldrLoadData(accountId, accountName, fromDate, toDate);
}

function ldrLoadData(accountId, accountName, fromDate, toDate) {
    ldrCurrentFromDate = fromDate;
    ldrCurrentToDate   = toDate;
    // Show overlay loader — do NOT hide the table/bars so the container keeps its
    // height. On a fresh open tbOpenLedger already hid them; on refresh the overlay
    // covers the stale content while new data loads.
    $('#ldr_inner_loader').css('display', 'flex');

    $.ajax({
        url: tbAccountLedgerUrl,
        method: 'GET',
        data: { account_id: accountId, from_date: fromDate, to_date: toDate },
        success(res) {
            $('#ldr_inner_loader').css('display', 'none');
            $('#ledger_detail_table').css('display', 'block');

            if (!res.success) {
                Swal.fire({ icon: 'error', title: 'No Data', text: res.message || 'No ledger data found.' });
                return;
            }

            const data         = res.data || {};
            const transactions = data.transactions || [];
            const totals       = data.totals || {};

            $('#ldr_modal_title').text(data.account_name || accountName || 'Ledger');

            const from = typeof formatDateToDMY === 'function' ? formatDateToDMY(fromDate) : fromDate;
            const to   = typeof formatDateToDMY === 'function' ? formatDateToDMY(toDate)   : toDate;
            $('#ldr_date_text').text(`${from} — ${to}`);
            $('#ldr_date_badge').show();

            ldrBuildTable(transactions);

            const opening = data.opening_balance || 0;
            const closing = data.closing_balance || 0;

            $('#ldr_opening_balance').text(
                opening !== 0 ? `${formatIndianNumber(Math.abs(opening))} ${opening >= 0 ? 'Dr' : 'Cr'}` : '0.00'
            );
            $('#ldr_opening_bar').show();

            $('#ldr_total_debit').text(formatIndianNumber(totals.total_debit   || 0));
            $('#ldr_total_credit').text(formatIndianNumber(totals.total_credit || 0));
            $('#ldr_totals_bar').show();

            $('#ldr_closing_balance').text(
                closing !== 0 ? `${formatIndianNumber(Math.abs(closing))} ${closing >= 0 ? 'Dr' : 'Cr'}` : '0.00'
            );
            $('#ldr_closing_bar').show();
        },
        error(xhr) {
            $('#ldr_inner_loader').css('display', 'none');
            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed to load ledger.' });
        },
    });
}

function ldrBuildTable(rows) {
    if (ldrTable) {
        ldrTable.setData(rows);
        return;
    }

    ldrTable = new Tabulator('#ledger_detail_table', {
        data: rows,
        layout: 'fitColumns',
        height: 'calc(100vh - 400px)',
        placeholder: `
            <div class="text-center py-4">
                <i class="fa-solid fa-book fa-2x text-muted mb-2"></i>
                <p class="text-muted mb-0">No transactions found</p>
            </div>`,
        columns: [
            {
                title: 'Sr No.',
                formatter: 'rownum',
                width: 55,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
            },
            {
                title: 'Date',
                field: 'voucher_date',
                width: 130,
                headerSort: false,
                formatter: cell => {
                    const v = cell.getValue();
                    return v ? `<span class="text-dark">${typeof formatDateToDMY === 'function' ? formatDateToDMY(v) : v}</span>` : '';
                },
            },
            {
                title: 'Particulars',
                field: 'against_account_name',
                minWidth: 200,
                headerSort: false,
                formatter: cell => {
                    const v = cell.getValue() || '';
                    cell.getElement().setAttribute('title', v);
                    return `<span class="text-dark">${v}</span>`;
                },
            },
            {
                title: 'Voucher Type',
                field: 'voucher_type',
                width: 180,
                headerSort: false,
                formatter: cell => `<span class="text-dark">${cell.getValue() || ''}</span>`,
            },
            {
                title: 'Voucher No.',
                field: 'voucher_serial',
                width: 140,
                headerSort: false,
                formatter: cell => {
                    const d = cell.getRow().getData();
                    const serial = d.voucher_serial || '';
                    const ref    = d.reference_number || '';
                    const num    = serial && ref ? `${serial}/${ref}` : (serial || ref);
                    return `<span class="text-dark">${num}</span>`;
                },
            },
            {
                title: 'Debit (₹)',
                field: 'debit',
                width: 180,
                hozAlign: 'right',
                headerHozAlign: 'right',
                headerSort: false,
                formatter: cell => {
                    const v = parseFloat(cell.getValue()) || 0;
                    return v > 0 ? `<span class="text-dark">${formatIndianNumber(v)}</span>` : '';
                },
                bottomCalc: 'sum',
                bottomCalcFormatter: cell => {
                    const v = parseFloat(cell.getValue()) || 0;
                    return `<span class="text-dark">${formatIndianNumber(v)}</span>`;
                },
            },
            {
                title: 'Credit (₹)',
                field: 'credit',
                width: 180,
                hozAlign: 'right',
                headerHozAlign: 'right',
                headerSort: false,
                formatter: cell => {
                    const v = parseFloat(cell.getValue()) || 0;
                    return v > 0 ? `<span class="text-dark">${formatIndianNumber(v)}</span>` : '';
                },
                bottomCalc: 'sum',
                bottomCalcFormatter: cell => {
                    const v = parseFloat(cell.getValue()) || 0;
                    return `<span class="text-dark">${formatIndianNumber(v)}</span>`;
                },
            },
            {
                title: 'Balance (₹)',
                field: 'running_balance',
                width: 200,
                hozAlign: 'right',
                headerHozAlign: 'right',
                headerSort: false,
                formatter: cell => {
                    const v = parseFloat(cell.getValue());
                    if (isNaN(v)) return '';
                    const abs  = formatIndianNumber(Math.abs(v));
                    const side = v >= 0 ? 'Dr' : 'Cr';
                    return `<span class="text-dark">${abs} ${side}</span>`;
                },
            },
        ],
    });
}

/* ------------------------------------------------------------------ */
/* MONTH WISE SUMMARY PRINT / EXPORT                                   */
/* ------------------------------------------------------------------ */
function mwsPrint() {
    if (!mwsCurrentAccountId || !mwsCurrentFromDate || !mwsCurrentToDate) return;
    printReport(tbAccountMonthWisePrint, {
        account_id: mwsCurrentAccountId,
        from_date:  mwsCurrentFromDate,
        to_date:    mwsCurrentToDate,
        format:     'print',
    });
}

function mwsExport() {
    if (!mwsCurrentAccountId || !mwsCurrentFromDate || !mwsCurrentToDate) return;
    downloadExcel(tbAccountMonthWiseExcel, {
        account_id: mwsCurrentAccountId,
        from_date:  mwsCurrentFromDate,
        to_date:    mwsCurrentToDate,
    });
}

/* ------------------------------------------------------------------ */
/* LEDGER DETAIL PRINT / EXPORT                                        */
/* ------------------------------------------------------------------ */
function ldrPrint() {
    if (!ldrCurrentAccountId || !ldrCurrentFromDate || !ldrCurrentToDate) return;
    printReport(tbAccountLedgerPrint, {
        account_id: ldrCurrentAccountId,
        from_date:  ldrCurrentFromDate,
        to_date:    ldrCurrentToDate,
        format:     'print',
    });
}

function ldrExport() {
    if (!ldrCurrentAccountId || !ldrCurrentFromDate || !ldrCurrentToDate) return;
    downloadExcel(tbAccountLedgerExcel, {
        account_id: ldrCurrentAccountId,
        from_date:  ldrCurrentFromDate,
        to_date:    ldrCurrentToDate,
    });
}

/* ------------------------------------------------------------------ */
/* HELPERS                                                              */
/* ------------------------------------------------------------------ */
function tbNormalizeDate(d) {
    if (!d) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(d)) return d;
    if (typeof formatDateToYMD === 'function') return formatDateToYMD(d) || '';
    return d;
}
