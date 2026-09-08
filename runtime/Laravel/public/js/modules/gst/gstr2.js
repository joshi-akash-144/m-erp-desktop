'use strict';

// ─── Formatters ───────────────────────────────────────────────────────────────

function fmt(val) {
    const n = parseFloat(val) || 0;
    return n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function fmtInt(val) {
    return parseInt(val) || 0;
}

function fmtRate(val) {
    const n = parseFloat(val) || 0;
    return n > 0 ? parseFloat(n.toFixed(2)) + '%' : '—';
}

function fmtDate(val) {
    if (!val) return '';
    if (typeof formatDateToDMY === 'function') {
        const clean = String(val).split(' ')[0].split('T')[0];
        return formatDateToDMY(clean);
    }
    const parts = String(val).split(' ')[0].split('T')[0].split('-');
    if (parts.length === 3 && parts[0].length === 4) {
        return `${parts[2]}-${parts[1]}-${parts[0]}`;
    }
    return val;
}

// ─── Section config: label → section key (for drill-down) ────────────────────

const DRILL_SECTIONS = {
    'b2b':             { key: 'b2b',             label: 'B2B Invoice - 3, 4A' },
    'b2bur':           { key: 'b2bur',           label: 'B2BUR Invoices - 4B' },
    'cdnr':            { key: 'cdnr',            label: 'Credit/Debit Notes - 6C' },
    'cdnu':            { key: 'cdnu',            label: 'Credit/Debit Notes Unregistered' },
    'import_goods':    { key: 'import_goods',    label: 'Import of Goods/ Capitals' },
    'import_services': { key: 'import_services', label: 'Import of Services - 4 C' },
    'nil_rated':       { key: 'nil_rated',       label: 'Nil Rated Invoices - 7A, 7B' },
    'hsn_summary':     { key: 'hsn_summary',     label: 'HSN-wise Summary of Inward Supplies' },
};

// ─── Tabulator column definitions per section ─────────────────────────────────

function amountCol(title, field, opts = {}) {
    return {
        title,
        field,
        hozAlign: 'right',
        headerHozAlign: 'right',
        formatter: cell => fmt(cell.getValue()),
        bottomCalc: 'sum',
        bottomCalcFormatter: cell => fmt(cell.getValue()),
        ...opts,
    };
}

function getColumns(section) {
    const base = [
        { title: '#', formatter: 'rownum', hozAlign: 'center', width: 46, headerHozAlign: 'center', resizable: false },
    ];

    const invoiceNo   = { title: 'Invoice / Note No', field: 'invoice_no', minWidth: 130 };
    const invoiceDate = { 
        title: 'Date', 
        field: 'invoice_date', 
        width: 100, 
        hozAlign: 'center', 
        headerHozAlign: 'center',
        formatter: cell => fmtDate(cell.getValue()) 
    };
    const supplier    = { title: 'Supplier Name', field: 'account_name', minWidth: 180 };
    const gstin       = { title: 'GSTIN', field: 'gstin', minWidth: 160 };
    const pos         = { title: 'Place of Supply', field: 'place_of_supply', minWidth: 140 };
    const rc          = {
        title: 'RC', field: 'reverse_charge', hozAlign: 'center', width: 52,
        formatter: cell => cell.getValue() ? '<span class="badge bg-warning text-dark">Y</span>' : '<span class="text-muted">N</span>',
    };
    const taxable   = amountCol('Taxable', 'taxable_amount', { minWidth: 110 });
    const cgstRate  = { title: 'CGST%', field: 'cgst_rate', hozAlign: 'center', width: 70, formatter: cell => fmtRate(cell.getValue()) };
    const cgst      = amountCol('CGST', 'cgst_amount', { minWidth: 100 });
    const sgstRate  = { title: 'SGST%', field: 'sgst_rate', hozAlign: 'center', width: 70, formatter: cell => fmtRate(cell.getValue()) };
    const sgst      = amountCol('SGST', 'sgst_amount', { minWidth: 100 });
    const igstRate  = { title: 'IGST%', field: 'igst_rate', hozAlign: 'center', width: 70, formatter: cell => fmtRate(cell.getValue()) };
    const igst      = amountCol('IGST', 'igst_amount', { minWidth: 100 });
    const cess      = amountCol('CESS', 'cess_amount', { minWidth: 90 });
    const totalTax  = amountCol('Total Tax', 'total_tax_amount', { minWidth: 110 });
    const invVal    = amountCol('Invoice Value', 'invoice_value', { minWidth: 120 });

    switch (section) {
        case 'b2b':
            return [...base, supplier, gstin, invoiceNo, invoiceDate, pos, rc, taxable, cgstRate, cgst, sgstRate, sgst, igstRate, igst, cess, totalTax, invVal];
        case 'b2bur':
            return [...base, supplier, invoiceNo, invoiceDate, pos, taxable, cgstRate, cgst, sgstRate, sgst, igstRate, igst, cess, totalTax, invVal];
        case 'cdnr':
            return [...base, supplier, gstin, invoiceNo, invoiceDate, pos, rc, taxable, cgstRate, cgst, sgstRate, sgst, igstRate, igst, cess, totalTax, invVal];
        case 'cdnu':
            return [...base, supplier, invoiceNo, invoiceDate, pos, taxable, cgstRate, cgst, sgstRate, sgst, igstRate, igst, cess, totalTax, invVal];
        case 'import_goods':
            return [...base, supplier, invoiceNo, invoiceDate, taxable, igstRate, igst, cess, totalTax, invVal];
        case 'import_services':
            return [...base, supplier, invoiceNo, invoiceDate, taxable, igstRate, igst, cess, totalTax, invVal];
        case 'nil_rated':
            return [...base, supplier, invoiceNo, invoiceDate, pos, taxable, invVal];
        case 'hsn_detail':
            return [
                ...base,
                { title: 'HSN Code', field: 'hsn_code', minWidth: 110 },
                { title: 'Item', field: 'item_name', minWidth: 200 },
                { title: 'UQC', field: 'uqc', width: 70, hozAlign: 'center' },
                amountCol('Qty', 'qty', { minWidth: 80 }),
                supplier,
                invoiceNo,
                invoiceDate,
                taxable,
                cgstRate, cgst,
                sgstRate, sgst,
                igstRate, igst,
                cess, totalTax, invVal,
            ];
        default:
            return [...base, supplier, gstin, invoiceNo, invoiceDate, pos, taxable, cgst, sgst, igst, cess, totalTax, invVal];
    }
}

// ─── Data Builders for Tabulator ────────────────────────────────────────────────────────────
function buildSummaryRow(label, d, sectionKey, isNil = false) {
    const drillable = sectionKey && DRILL_SECTIONS[sectionKey] !== undefined;
    const drillIcon = drillable && d && d.count > 0 ? `<i class="fas fa-external-link-alt ms-1 small text-primary" style="opacity: 0.8;"></i>` : '';
    const sectionHtml = drillable && d && d.count > 0 
        ? `<a href="javascript:void(0)" class="text-primary text-decoration-none" style="font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">${label} ${drillIcon}</a>`
        : `<span>${label}</span>`;

    if (!d || d.count === 0) {
        return { section: sectionHtml, count: 0, invoice_value: '', taxable_amount: '', total_tax: '', cgst: '', sgst: '', igst: '', cess: '', _sectionKey: sectionKey };
    }

    if (isNil) {
        return { section: sectionHtml, count: d.count, invoice_value: '', taxable_amount: d.taxable_amount || 0, total_tax: '', cgst: '', sgst: '', igst: '', cess: '', _sectionKey: sectionKey };
    }

    return {
        section: sectionHtml,
        count: d.count,
        invoice_value: d.invoice_value || 0,
        taxable_amount: d.taxable_amount || 0,
        total_tax: d.total_tax || 0,
        cgst: d.cgst || 0,
        sgst: d.sgst || 0,
        igst: d.igst || 0,
        cess: parseFloat(d.cess) > 0 ? d.cess : '',
        _sectionKey: sectionKey
    };
}

function buildEmptyRow(label) {
    return { section: label, count: 0, invoice_value: '', taxable_amount: '', total_tax: '', cgst: '', sgst: '', igst: '', cess: '' };
}

function buildTaxSubRow(label, value) {
    return {
        section: label, count: '-', invoice_value: value, taxable_amount: '', total_tax: '', cgst: '', sgst: '', igst: '', cess: ''
    };
}

let summaryTable = null;

const gstr2SummaryColumns = [
    { title: "Section Name", field: "section", width: 350, formatter: "html", headerSort: false, resizable: true },
    { title: "No. of Result", field: "count", width: 120, hozAlign: "center", headerHozAlign: "center", headerSort: false, resizable: true, formatter: function(cell) { return cell.getValue() !== '' && cell.getValue() !== '-' ? fmtInt(cell.getValue()) : cell.getValue(); } },
    { title: "Total Invoice Value", field: "invoice_value", minWidth: 150, hozAlign: "right", headerHozAlign: "right", headerSort: false, resizable: true, formatter: function(cell) { return cell.getValue() !== '' ? fmt(cell.getValue()) : ''; } },
    { title: "Total Taxable Value", field: "taxable_amount", minWidth: 150, hozAlign: "right", headerHozAlign: "right", headerSort: false, resizable: true, formatter: function(cell) { return cell.getValue() !== '' ? fmt(cell.getValue()) : ''; } },
    { title: "Total Tax Liability", field: "total_tax", minWidth: 150, hozAlign: "right", headerHozAlign: "right", headerSort: false, resizable: true, formatter: function(cell) { return cell.getValue() !== '' ? fmt(cell.getValue()) : ''; } },
    { title: "Total CGST Value", field: "cgst", minWidth: 140, hozAlign: "right", headerHozAlign: "right", headerSort: false, resizable: true, formatter: function(cell) { return cell.getValue() !== '' ? fmt(cell.getValue()) : ''; } },
    { title: "Total SGST Value", field: "sgst", minWidth: 140, hozAlign: "right", headerHozAlign: "right", headerSort: false, resizable: true, formatter: function(cell) { return cell.getValue() !== '' ? fmt(cell.getValue()) : ''; } },
    { title: "Total IGST Value", field: "igst", minWidth: 140, hozAlign: "right", headerHozAlign: "right", headerSort: false, resizable: true, formatter: function(cell) { return cell.getValue() !== '' ? fmt(cell.getValue()) : ''; } },
    { title: "Total CESS Value", field: "cess", minWidth: 140, hozAlign: "right", headerHozAlign: "right", headerSort: false, resizable: true, formatter: function(cell) { return cell.getValue() !== '' ? fmt(cell.getValue()) : ''; } },
];

function renderSummary(data) {
    let rows = [];

    rows.push(buildSummaryRow('B2B Invoice - 3, 4A', data.b2b, 'b2b'));
    rows.push(buildSummaryRow('B2BUR Invoices - 4B', data.b2bur, 'b2bur'));
    rows.push(buildSummaryRow('Credit/Debit Notes - 6C', data.cdnr, 'cdnr'));
    rows.push(buildSummaryRow('Credit/Debit Notes Unregistered', data.cdnu, 'cdnu'));
    rows.push(buildSummaryRow('Import of Goods/ Capitals', data.import_goods, 'import_goods'));
    rows.push(buildSummaryRow('Import of Services - 4 C', data.import_services, 'import_services'));
    rows.push(buildSummaryRow('Nil Rated Invoices - 7A, 7B', data.nil_rated, 'nil_rated', true));

    // Aggregate HSN
    let hsnTotals = { count: 0, invoice_value: 0, taxable_amount: 0, total_tax: 0, cgst: 0, sgst: 0, igst: 0, cess: 0 };
    currentHsnSummaryData = data.hsn_summary || [];
    if (currentHsnSummaryData.length > 0) {
        hsnTotals.count = currentHsnSummaryData.length;
        currentHsnSummaryData.forEach(r => {
            hsnTotals.invoice_value += parseFloat(r.invoice_value) || 0;
            hsnTotals.taxable_amount += parseFloat(r.taxable_amount) || 0;
            hsnTotals.total_tax += parseFloat(r.total_tax) || 0;
            hsnTotals.cgst += parseFloat(r.cgst) || 0;
            hsnTotals.sgst += parseFloat(r.sgst) || 0;
            hsnTotals.igst += parseFloat(r.igst) || 0;
            hsnTotals.cess += parseFloat(r.cess) || 0;
        });
    }
    rows.push(buildSummaryRow('HSN-wise Summary of Inward Supplies', hsnTotals, 'hsn_summary'));

    // ITC Summary
    rows.push({ section: 'Total Input Tax Credit', count: '-', invoice_value: '', taxable_amount: '', total_tax: '', cgst: '', sgst: '', igst: '', cess: '' });
    rows.push(buildTaxSubRow('CGST', data.itc_summary?.cgst || 0));
    rows.push(buildTaxSubRow('SGST', data.itc_summary?.sgst || 0));
    rows.push(buildTaxSubRow('IGST', data.itc_summary?.igst || 0));
    rows.push(buildTaxSubRow('CESS', data.itc_summary?.cess || 0));
    rows.push(buildTaxSubRow('Total', data.itc_summary?.total || 0));

    if (summaryTable) {
        summaryTable.destroy();
    }
    
    summaryTable = new Tabulator('#gstr2_tabulator', {
        data: rows,
        layout: 'fitColumns',
        columns: gstr2SummaryColumns,
        height: '100%',
        rowFormatter: function(row) {
            const el = row.getElement();
            const d = row.getData();
            if (d.section && (d.section.includes('color: #9c7300') || d.section === 'Total Input Tax Credit')) {
                el.style.backgroundColor = '#fffaf0';
                el.style.fontWeight = 'bold';
            }
        }
    });
    
    summaryTable.on("rowClick", function(e, row) {
        const d = row.getData();
        if (d._sectionKey && d.count > 0 && DRILL_SECTIONS[d._sectionKey]) {
            if (d._sectionKey === 'hsn_summary') {
                openHsnSummaryModal();
            } else {
                openDrillModal(d._sectionKey, String(d.section).replace(/<[^>]*>?/gm, '').trim());
            }
        }
    });
}

// ─── Tabulator instance ───────────────────────────────────────────────────────

let currentHsnSummaryData = [];
let drillTable = null;
let hsnSummaryTable = null;
let currentDrillSection = null;
let currentDrillHsnCode = null;

function openHsnSummaryModal() {
    const fromDMY = $('#from_date').val().trim();
    const toDMY   = $('#to_date').val().trim();
    $('#hsn_summary_modal_subtitle').text(`${fromDMY}  →  ${toDMY}`);
    
    $('#hsnSummaryModal').modal('show');
    
    if (hsnSummaryTable) {
        hsnSummaryTable.destroy();
    }
    
    hsnSummaryTable = new Tabulator('#hsn_summary_tabulator', {
        data: currentHsnSummaryData,
        layout: 'fitDataStretch',
        height: 'calc(100vh - 130px)',
        columns: [
            { title: "HSN Code", field: "hsn_code", minWidth: 130, formatter: function(cell) {
                return `<a href="javascript:void(0)" class="text-primary text-decoration-none hover-underline" style="font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">${cell.getValue()} <i class="fas fa-external-link-alt ms-1 small text-primary" style="opacity: 0.8;"></i></a>`;
            }},
            { title: "UQC", field: "uqc", hozAlign: "center", width: 80 },
            amountCol("Total Qty", "total_qty", { minWidth: 100 }),
            amountCol("Total Value", "invoice_value", { minWidth: 120 }),
            amountCol("Taxable Value", "taxable_amount", { minWidth: 130 }),
            amountCol("Integrated Tax", "igst", { minWidth: 130 }),
            amountCol("Central Tax", "cgst", { minWidth: 120 }),
            amountCol("State/UT Tax", "sgst", { minWidth: 120 }),
            amountCol("Cess", "cess", { minWidth: 100 })
        ],
        placeholder: `<div class="text-center py-5 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No HSN data found</div>`,
    });
    
    hsnSummaryTable.on("rowClick", function(e, row) {
        const hsnCode = row.getData().hsn_code;
        openDrillModal('hsn_detail', 'HSN: ' + hsnCode, hsnCode);
    });
}

function destroyDrillTable() {
    if (drillTable) {
        try { drillTable.destroy(); } catch(e) {}
        drillTable = null;
    }
    $('#drill_tabulator_wrap').html('<div id="drill_tabulator"></div>');
}

// ─── Drill-Down Modal ─────────────────────────────────────────────────────────

function openDrillModal(section, label, hsnCode) {
    currentDrillSection = section;
    currentDrillHsnCode = hsnCode || null;

    const fromDMY = $('#from_date').val().trim();
    const toDMY   = $('#to_date').val().trim();

    const sectionInfo = DRILL_SECTIONS[section] || { label: label };
    $('#drill_modal_title').text(sectionInfo.label || label);
    $('#drill_modal_subtitle').text(`${fromDMY}  →  ${toDMY}`);
    $('#drill_modal_stats').html('<span class="modal-stat-pill"><span class="spinner-border spinner-border-sm text-primary me-1" role="status" style="width:0.8rem;height:0.8rem;border-width:0.15em;"></span><span class="text-primary fw-semibold">Loading details...</span></span>');

    $('#gstr2DrillModal').modal('show');

    const params = {
        from_date: formatDateToYMD(fromDMY),
        to_date:   formatDateToYMD(toDMY),
        section:   section,
    };
    if (section === 'hsn_detail' && hsnCode) params.hsn_code = hsnCode;

    destroyDrillTable();

    drillTable = new Tabulator('#drill_tabulator', {
        ajaxURL: GSTR2_DETAIL_URL,
        ajaxParams: params,
        ajaxConfig: 'GET',
        ajaxLoader: true,
        ajaxLoaderLoading: `<div class="d-flex flex-column align-items-center justify-content-center py-5 text-center" style="min-height: 250px;">
            <div class="spinner-border text-primary mb-3" style="width: 2.75rem; height: 2.75rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="text-primary fw-bold fs-6">Loading records, please wait...</div>
            <div class="text-muted small mt-1">Fetching transaction details</div>
        </div>`,
        ajaxLoaderError: `<div class="d-flex flex-column align-items-center justify-content-center py-5 text-center" style="min-height: 250px;">
            <i class="fas fa-exclamation-circle text-danger fa-2x mb-2"></i>
            <div class="text-danger fw-bold">Failed to load data</div>
        </div>`,
        pagination: true,
        paginationMode: 'remote',
        paginationSize: 100,
        paginationSizeSelector: [50, 100, 250, true],
        paginationDataSent: {
            page: "page",
            size: "size",
        },
        paginationDataReceived: {
            last_page: "last_page",
            data: "data",
        },
        layout: 'fitDataStretch',
        height: 'calc(100vh - 130px)',
        columns: getColumns(section),
        columnDefaults: {
            resizable: true,
            headerSort: false,
        },
        placeholder: `<div class="text-center py-5 text-muted">
            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>No records found
        </div>`,
        ajaxRequesting: function() {
            $('#drill_modal_stats').html('<span class="modal-stat-pill"><span class="spinner-border spinner-border-sm text-primary me-1" role="status" style="width:0.8rem;height:0.8rem;border-width:0.15em;"></span><span class="text-primary fw-semibold">Loading details...</span></span>');
        },
        ajaxResponse: function(url, params, response) {
            $('#drill_modal_stats').html(`<span class="modal-stat-pill"><i class="fas fa-file-invoice me-1"></i>Total Records: <span>${response.total || 0}</span></span>`);
            return response;
        },
        ajaxError: function(xhr, textStatus, errorThrown) {
            const msg = xhr.responseJSON?.message ?? 'Failed to load detail.';
            showToast('error', msg);
            $('#drill_modal_stats').html(`<span class="modal-stat-pill text-danger"><i class="fas fa-exclamation-triangle me-1"></i>${msg}</span>`);
        }
    });
}

// ─── Validation ───────────────────────────────────────────────────────────────

function validateDates(fromDMY, toDMY) {
    if (!isValidDateDMY(fromDMY) || !isValidDateDMY(toDMY)) {
        showToast('error', 'From Date and To Date are required in DD-MM-YYYY format.');
        return false;
    }

    const fromYMD = formatDateToYMD(fromDMY);
    const toYMD   = formatDateToYMD(toDMY);

    if (new Date(fromYMD) > new Date(toYMD)) {
        showToast('error', 'To Date must be greater than or equal to From Date.');
        return false;
    }

    if (!isWithinFY(fromYMD, FINANCIAL_YEAR_START, FINANCIAL_YEAR_END)) {
        showToast('error', 'From Date must be within the current financial year.');
        return false;
    }

    if (!isWithinFY(toYMD, FINANCIAL_YEAR_START, FINANCIAL_YEAR_END)) {
        showToast('error', 'To Date must be within the current financial year.');
        return false;
    }

    return true;
}

// ─── AJAX ─────────────────────────────────────────────────────────────────────

function fetchReport(fromDMY, toDMY) {
    showLoader('Loading GSTR-2 report...');

    $('#report_output').removeClass('d-none');
    
    if (summaryTable) {
        summaryTable.clearData();
    } else {
        $('#gstr2_tabulator').html('<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>Loading...</div>');
    }

    $.ajax({
        url: GSTR2_SUMMARY_URL,
        method: 'GET',
        data: {
            from_date: formatDateToYMD(fromDMY),
            to_date:   formatDateToYMD(toDMY),
        },
        success: function (data) {
            renderSummary(data);
            $('#btn_export_gstr2, #btn_print_gstr2').removeClass('d-none');
        },
        error: function (xhr) {
            const msg = xhr.responseJSON?.message ?? 'Failed to load GSTR-2 report.';
            showToast('error', msg);
            if (!summaryTable) {
                $('#gstr2_tabulator').html(`<div class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>${msg}</div>`);
            }
        },
        complete: function () {
            hideLoader();
        }
    });
}

// ─── Init ─────────────────────────────────────────────────────────────────────

$(function () {
    // Handle multiple modals by adjusting z-indexes dynamically
    $(document).on('show.bs.modal', '.modal', function () {
        const zIndex = 1040 + (10 * $('.modal:visible').length);
        $(this).css('z-index', zIndex);
        setTimeout(function() {
            $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
        }, 0);
    });

    // Clean up overflow on body when a modal is closed but another is still open
    $(document).on('hidden.bs.modal', '.modal', function () {
        if($('.modal:visible').length) {
            $('body').addClass('modal-open');
        }
    });

    new DateInput('.date-format', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    $('#from_date').val(formatDateToDMY(FINANCIAL_YEAR_START));
    $('#to_date').val(formatDateToDMY(FINANCIAL_YEAR_END));

    const STORAGE_KEY = 'gstr2_dates_' + window.location.pathname;
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
            const parsed = JSON.parse(stored);
            if (parsed.from) $('#from_date').val(parsed.from);
            if (parsed.to) $('#to_date').val(parsed.to);
        }
    } catch (e) {}

    const emptyStateHtml = `
        <div class="d-flex flex-column justify-content-center align-items-center py-5 text-muted" style="min-height: 250px;">
            <i class="fa-solid fa-file-invoice fa-3x mb-3" style="opacity: 0.2;"></i>
            <h4 class="mb-1 text-secondary">No Data Loaded</h4>
            <p class="mb-0" style="font-size: 0.85rem;">Select dates and click Apply to fetch the GSTR-2 report.</p>
        </div>
    `;

    $('#filter_clear').on('click', function () {
        $('#from_date').val('');
        $('#to_date').val('');
        $('#btn_export_gstr2, #btn_print_gstr2').addClass('d-none');
        document.getElementById('gstr2_tabulator').innerHTML = emptyStateHtml;
        try { localStorage.removeItem(STORAGE_KEY); } catch(e) {}
    });

    // Show empty state on load
    $('#report_output').removeClass('d-none');
    $('#btn_export_gstr2, #btn_print_gstr2').addClass('d-none');
    document.getElementById('gstr2_tabulator').innerHTML = emptyStateHtml;

    $('#gstr2_form').on('submit', function (e) {
        e.preventDefault();
        const fromDMY = $('#from_date').val().trim();
        const toDMY   = $('#to_date').val().trim();
        if (!validateDates(fromDMY, toDMY)) return;
        
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify({ from: fromDMY, to: toDMY })); } catch(e) {}
        
        fetchReport(fromDMY, toDMY);
    });

    // ─── Export & Print ───────────────────────────────────────────────────────────

    $('#btn_print_gstr2').on('click', function () {
        if (summaryTable) {
            summaryTable.print(false, true);
        } else {
            window.print();
        }
    });

    $('#btn_export_gstr2').on('click', function () {
        const fromDate = $('#from_date').val();
        const toDate = $('#to_date').val();
        
        if (typeof downloadExcel === "function") {
            downloadExcel(GSTR2_EXPORT_EXCEL_URL, {
                from_date: formatDateToYMD(fromDate),
                to_date: formatDateToYMD(toDate)
            });
        } else {
            showToast('error', 'Export function is not available.');
        }
    });

    $('#btn_export_drill_excel').on('click', function () {
        const fromDMY = $('#from_date').val().trim();
        const toDMY   = $('#to_date').val().trim();

        if (!currentDrillSection) {
            showToast('error', 'No section selected to export.');
            return;
        }

        if (typeof downloadExcel === "function") {
            const params = {
                from_date: formatDateToYMD(fromDMY),
                to_date:   formatDateToYMD(toDMY),
                section:   currentDrillSection,
            };
            if (currentDrillSection === 'hsn_detail' && currentDrillHsnCode) {
                params.hsn_code = currentDrillHsnCode;
            }
            downloadExcel(GSTR2_EXPORT_DETAIL_EXCEL_URL, params);
        } else {
            showToast('error', 'Export function is not available.');
        }
    });

    $('#btn_export_hsn_summary_excel').on('click', function () {
        const fromDMY = $('#from_date').val().trim();
        const toDMY   = $('#to_date').val().trim();

        if (typeof downloadExcel === "function") {
            downloadExcel(GSTR2_EXPORT_DETAIL_EXCEL_URL, {
                from_date: formatDateToYMD(fromDMY),
                to_date:   formatDateToYMD(toDMY),
                section:   'hsn_summary'
            });
        } else {
            showToast('error', 'Export function is not available.');
        }
    });

    // Drill-down click on summary rows
    $(document).on('click', '.gstr2-section-row[data-section]', function () {
        const section = $(this).data('section');
        const label   = $(this).data('label') || '';
        const hsn     = $(this).data('hsn') || null;
        if (!section) return;
        openDrillModal(section, label, hsn);
    });

    // Destroy table when modal closes to free memory
    $('#gstr2DrillModal').on('hidden.bs.modal', function () {
        destroyDrillTable();
    });

    // Fix tabulator blank rendering issue if data loads before modal animation completes
    $('#gstr2DrillModal').on('shown.bs.modal', function () {
        if (drillTable) {
            drillTable.redraw();
        }
    });
});
