// Profit & Loss Report — Single 4-column Tabulator per section
/* global plListUrl, plPrintUrl, plExcelUrl, FINANCIAL_YEAR_START, FINANCIAL_YEAR_END */
'use strict';

const STOCK_SECTIONS = new Set(['opening_stock', 'closing_stock']);
const STOCK_LABELS   = { opening_stock: 'Opening Stock', closing_stock: 'Closing Stock' };
const TRADING_DR     = ['trading_dr_purchase', 'trading_dr_direct_expense'];
const TRADING_CR     = ['trading_cr_sales', 'trading_cr_direct_income'];
const PL_DR          = ['indirect_expense'];
const PL_CR          = ['indirect_income'];

const tables = {};
let plFilter  = {};

/* ── Init ─────────────────────────────────────────────────── */
$(function () {
    new DateInput('#pl_from_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('#pl_to_date',   FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    $('#pl_view_type').select2({ theme: 'bootstrap-5', width: null });
    $('#pl_from_date').val(formatDateToDMY(FINANCIAL_YEAR_START));
    $('#pl_to_date').val(formatDateToDMY(plToday()));
    plFilter = plCollect();
    plLoad();
    $('#pl_show_btn').on('click',  plApply);
    $('#pl_clear_btn').on('click', plClear);
    $('#pl_print_btn').on('click', e => { e.preventDefault(); plPrint(); });
    $('#pl_excel_btn').on('click', e => { e.preventDefault(); plExport(); });
});

function plToday() {
    const t = new Date().toISOString().slice(0, 10);
    return t <= FINANCIAL_YEAR_END ? t : FINANCIAL_YEAR_END;
}

/* ── Filters ──────────────────────────────────────────────── */
function plCollect() {
    return {
        view_type: $('#pl_view_type').val() || 'group_wise',
        from_date: plNorm($('#pl_from_date').val().trim()),
        to_date:   plNorm($('#pl_to_date').val().trim()),
    };
}
function plApply() {
    const f = plCollect();
    if (!f.from_date || !f.to_date) { showToast('error', 'Select both From and To dates.'); return; }
    plFilter = f;
    plLoad();
}
function plClear() {
    $('#pl_view_type').val('group_wise').trigger('change');
    $('#pl_from_date').val(formatDateToDMY(FINANCIAL_YEAR_START));
    $('#pl_to_date').val(formatDateToDMY(plToday()));
    plFilter = { view_type: 'group_wise', from_date: FINANCIAL_YEAR_START, to_date: plToday() };
    plLoad();
}

/* ── Load ─────────────────────────────────────────────────── */
function plLoad() {
    // plDateBadge();
    showLoader('Loading Profit & Loss…');
    $.ajax({
        url: plListUrl, method: 'GET', data: plFilter,
        success(res) {
            hideLoader();
            if (!res.success) { Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Failed.' }); return; }
            plRender(res.data || {});
        },
        error(xhr) {
            hideLoader();
            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed.' });
        },
    });
}

/* ── Main Render ──────────────────────────────────────────── */
function plRender(data) {
    const { trading, pl } = data;
    const expand    = plFilter.view_type === 'account_wise';
    const dateLabel = `From ${fmtD(plFilter.from_date)} to ${fmtD(plFilter.to_date)}`;

    Object.keys(tables).forEach(k => { if (tables[k]) { tables[k].destroy(); delete tables[k]; } });
    $('#pl_empty_state').remove();

    let html = '';
    html += `
    <div class="pl-section-wrap mb-3" id="trading_section">
        <div class="pl-section-header">
            Trading Account
            <span class="pl-section-date">${plEsc(dateLabel)}</span>
        </div>
        <div class="pl-table-wrap" id="trading_table"></div>
    </div>`;

    if (pl) {
        html += `
    <div class="pl-section-wrap mb-3" id="pl_section">
        <div class="pl-section-header">
            Profit &amp; Loss Account
            <span class="pl-section-date">${plEsc(dateLabel)}</span>
        </div>
        <div class="pl-table-wrap" id="pl_table"></div>
    </div>`;
    }

    $('#pl_report_container').html(html);
    $('#pl_report_container').append(buildSummaryStrip(trading, pl));

    window.PL_GROUPS = {}; // Reset global group map for drill-down

    // Double rAF: first frame injects HTML, second frame lets the browser reflow
    // and compute final container dimensions before Tabulator initializes.
    requestAnimationFrame(() => requestAnimationFrame(() => {
        makeTable('trading_table', buildTradingRows(trading, expand));
        if (pl) makeTable('pl_table', buildPLRows(trading, pl, expand));

        // Sync name column widths between Trading and P&L tables
        setTimeout(() => {
            if (tables['trading_table'] && tables['pl_table']) {
                const trCols = tables['trading_table'].getColumns();
                const plCols = tables['pl_table'].getColumns();
                // col[0]=dr_name, col[4]=cr_name (after 3 debit cols + sep)
                if (trCols[0] && plCols[0]) plCols[0].setWidth(trCols[0].getWidth());
                if (trCols[4] && plCols[4]) plCols[4].setWidth(trCols[4].getWidth());
                tables['trading_table'].redraw(true);
                tables['pl_table'].redraw(true);
            } else if (tables['trading_table']) {
                tables['trading_table'].redraw(true);
            }
        }, 150);
    }));
}

/* ── Flatten one side into a flat array of row objects ──── */
// Each row: { type, name, amount, level, meta }
function flattenSide(apiItems, sectionOrder, expand) {
    const byS  = plBySection(apiItems);
    const rows = [];

    sectionOrder.forEach(section => {
        const items = byS[section] || [];
        if (!items.length) return;

        if (STOCK_SECTIONS.has(section)) {
            const total = items.reduce((s, i) => s + parseFloat(i.amount || 0), 0);
            rows.push({ type: 'stock_label', name: STOCK_LABELS[section], acc_amount: null, grp_total: total, level: 0 });
            items.forEach(grp => {
                window.PL_GROUPS[grp.account_group_id] = grp;
                rows.push({ type: 'group', name: grp.name, acc_amount: null, grp_total: grp.amount, level: 1, group_id: grp.account_group_id });
                if (expand) {
                    (grp.children || []).forEach(c => rows.push(flatChild(c, 2)));
                }
            });
        } else {
            items.forEach(grp => {
                window.PL_GROUPS[grp.account_group_id] = grp;
                rows.push({ type: 'group', name: grp.name, acc_amount: null, grp_total: grp.amount, level: 0, group_id: grp.account_group_id });
                if (expand) {
                    (grp.children || []).forEach(c => rows.push(flatChild(c, 1)));
                }
            });
        }
    });

    return rows;
}

function flatChild(child, level) {
    if (child.is_item) {
        const qty  = Math.abs(parseFloat(child.quantity || 0));
        const rate = parseFloat(child.rate || 0);
        const unit = child.unit_name || '';
        return { type: 'item', name: child.name, acc_amount: child.amount, grp_total: null, level,
                 meta: `${qty.toFixed(3)} ${unit} @ ${fmt(rate)}` };
    }
    return { type: 'account', name: child.name, acc_amount: child.amount, grp_total: null, level, account_id: child.account_id };
}

/* ── Zip two flat arrays into combined 4-column row objects ── */
function zip(drRows, crRows) {
    const blank  = { type: 'blank', name: '', amount: null, level: 0 };
    const maxLen = Math.max(drRows.length, crRows.length);
    const out    = [];
    for (let i = 0; i < maxLen; i++) {
        out.push(mkRow(drRows[i] || blank, crRows[i] || blank));
    }
    return out;
}
function mkRow(dr, cr) {
    return {
        dr_type:       dr.type,               cr_type:       cr.type,
        dr_name:       dr.name,               cr_name:       cr.name,
        dr_acc_amount: dr.acc_amount ?? null,  cr_acc_amount: cr.acc_amount ?? null,
        dr_grp_total:  dr.grp_total  ?? null,  cr_grp_total:  cr.grp_total  ?? null,
        dr_level:      dr.level || 0,          cr_level:      cr.level || 0,
        dr_meta:       dr.meta || '',          cr_meta:       cr.meta || '',
        dr_group_id:   dr.group_id ?? null,    cr_group_id:   cr.group_id ?? null,
        dr_account_id: dr.account_id ?? null,  cr_account_id: cr.account_id ?? null,
    };
}
const BLANK_ROW = { type: 'blank', name: '', acc_amount: null, grp_total: null, level: 0 };

/* ── Stock section rows: single total line in group_wise, full hierarchy in account_wise ── */
function buildStockRows(items, label, total, expand) {
    const rows = [];
    if (!total) return rows;
    rows.push({ type: 'stock_label', name: label, acc_amount: null, grp_total: total, level: 0 });
    if (expand) {
        // Skip intermediate group (e.g. "General") — show individual items directly
        items.forEach(grp => {
            (grp.children || []).forEach(c => rows.push(flatChild(c, 1)));
        });
    }
    return rows;
}

/* ── Build Trading combined rows ──────────────────────────── */
function buildTradingRows(trading, expand) {
    const openingItems = (trading.debit  || []).filter(i => i.section === 'opening_stock');
    const closingItems = (trading.credit || []).filter(i => i.section === 'closing_stock');

    const drFlat = [
        ...buildStockRows(openingItems, 'Opening Stock', trading.opening_stock_total, expand),
        ...flattenSide(trading.debit,  TRADING_DR, expand),
    ];
    const crFlat = [
        ...buildStockRows(closingItems, 'Closing Stock', trading.closing_stock_total, expand),
        ...flattenSide(trading.credit, TRADING_CR, expand),
    ];

    const rows = zip(drFlat, crFlat);

    // Balance row — on whichever side needs it
    if (trading.is_gross_profit && trading.gross_profit > 0) {
        rows.push(mkRow(
            { type: 'balance_profit', name: 'Gross Profit c/d', acc_amount: null, grp_total: trading.gross_profit, level: 0 },
            BLANK_ROW
        ));
    } else if (!trading.is_gross_profit && trading.gross_loss > 0) {
        rows.push(mkRow(
            BLANK_ROW,
            { type: 'balance_loss', name: 'Gross Loss c/d', acc_amount: null, grp_total: trading.gross_loss, level: 0 }
        ));
    }

    // Dashes + Total
    rows.push(mkRow(
        { type: 'dashes', name: '', acc_amount: null, grp_total: null },
        { type: 'dashes', name: '', acc_amount: null, grp_total: null }
    ));
    rows.push(mkRow(
        { type: 'total', name: 'Total', acc_amount: null, grp_total: trading.trading_total },
        { type: 'total', name: 'Total', acc_amount: null, grp_total: trading.trading_total }
    ));
    return rows;
}

/* ── Build P&L combined rows ──────────────────────────────── */
function buildPLRows(trading, pl, expand) {
    const drFlat = flattenSide(pl.debit,  PL_DR, expand);
    const crFlat = flattenSide(pl.credit, PL_CR, expand);

    // Transfer rows at start
    if (!trading.is_gross_profit && trading.gross_loss > 0)
        drFlat.unshift({ type: 'transfer', name: 'Gross Loss b/d',   acc_amount: null, grp_total: trading.gross_loss,   level: 0 });
    if (trading.is_gross_profit  && trading.gross_profit > 0)
        crFlat.unshift({ type: 'transfer', name: 'Gross Profit b/d', acc_amount: null, grp_total: trading.gross_profit, level: 0 });

    const rows = zip(drFlat, crFlat);

    // Balance row
    if (pl.is_net_profit && pl.net_profit > 0) {
        rows.push(mkRow(
            { type: 'balance_profit', name: 'Net Profit', acc_amount: null, grp_total: pl.net_profit, level: 0 },
            BLANK_ROW
        ));
    } else if (!pl.is_net_profit && pl.net_loss > 0) {
        rows.push(mkRow(
            BLANK_ROW,
            { type: 'balance_loss', name: 'Net Loss', acc_amount: null, grp_total: pl.net_loss, level: 0 }
        ));
    }

    rows.push(mkRow(
        { type: 'dashes', name: '', acc_amount: null, grp_total: null },
        { type: 'dashes', name: '', acc_amount: null, grp_total: null }
    ));
    rows.push(mkRow(
        { type: 'total', name: 'Total', acc_amount: null, grp_total: pl.pl_total },
        { type: 'total', name: 'Total', acc_amount: null, grp_total: pl.pl_total }
    ));
    return rows;
}

/* ── Cell styling helper ──────────────────────────────────── */
const TYPE_STYLE = {
    stock_label:    { bg: '#dbeafe', color: '#1e40af', fw: '700' },
    group:          { bg: '#f0f4ff', color: '#3730a3', fw: '700' },
    account:        { bg: '#f8fafc', color: '#374151', fw: 'normal' },
    item:           { bg: '#f8fafc', color: '#374151', fw: 'normal' },
    transfer:       { bg: '#eff6ff', color: '#1e40af', fw: '700' },
    balance_profit: { bg: '#d1fae5', color: '#065f46', fw: '700' },
    balance_loss:   { bg: '#fee2e2', color: '#991b1b', fw: '700' },
    dashes:         { bg: '#f9fafb', color: '#9ca3af', fw: 'normal' },
    total:          { bg: '#e6f0fb', color: '#1a3a6b', fw: '700' },
    blank:          { bg: '#fff',    color: '',         fw: 'normal' },
};

function applyTypeStyle(el, type) {
    const s = TYPE_STYLE[type] || TYPE_STYLE.blank;
    el.style.background  = s.bg;
    el.style.color       = s.color;
    el.style.fontWeight  = s.fw;
}

/* ── Column definitions (3 columns per side) ─────────────── */
function nameFmt(side) {
    return function(cell) {
        const d  = cell.getRow().getData();
        const tp = d[`${side}_type`];
        applyTypeStyle(cell.getElement(), tp);
        if (tp === 'blank')  return '';
        if (tp === 'dashes') return '<span style="font-size:11px;letter-spacing:1px;">—</span>';
        const indent = (d[`${side}_level`] || 0) * 18;
        const meta   = d[`${side}_meta`]
            ? ` <span style="font-size:10.5px;color:#6b7280;font-family:'Courier New',monospace;">(${plEsc(d[`${side}_meta`])})</span>`
            : '';
            
        let nameHtml = plEsc(d[`${side}_name`]);
        if (tp === 'group' && d[`${side}_group_id`]) {
            nameHtml = `<a href="javascript:void(0)" class="text-decoration-none text-inherit pl-drill-link" onclick="openGroupModal(${d[`${side}_group_id`]})">${nameHtml}</a>`;
        } else if (tp === 'account' && d[`${side}_account_id`]) {
            const escName = plEsc(d[`${side}_name`]).replace(/'/g, "\\'");
            nameHtml = `<a href="javascript:void(0)" class="text-decoration-none text-inherit pl-drill-link" onclick="openLedgerModal(${d[`${side}_account_id`]}, '${escName}')">${nameHtml}</a>`;
        }

        return `<span style="padding-left:${indent}px;display:block">${nameHtml}${meta}</span>`;
    };
}
function accAmtFmt(side) {
    return function(cell) {
        const d  = cell.getRow().getData();
        const tp = d[`${side}_type`];
        applyTypeStyle(cell.getElement(), tp);
        if (tp === 'blank' || tp === 'dashes') return '';
        const v = d[`${side}_acc_amount`];
        return v !== null ? `<span class="pl-amt-cell">${fmt(v)}</span>` : '';
    };
}
function grpTotalFmt(side) {
    return function(cell) {
        const d  = cell.getRow().getData();
        const tp = d[`${side}_type`];
        applyTypeStyle(cell.getElement(), tp);
        if (tp === 'blank')  return '';
        if (tp === 'dashes') return '<span style="letter-spacing:1px;">———————</span>';
        const v = d[`${side}_grp_total`];
        return v !== null ? `<span class="pl-amt-cell">${fmt(v)}</span>` : '';
    };
}

function plTableColumns() {
    const AMT_W = 210;
    return [
        {
            title: 'Debit',
            columns: [
                {
                    title: 'Particulars', field: 'dr_name',
                    headerSort: false, resizable: true, widthGrow: 1,
                    formatter: nameFmt('dr'),
                },
                {
                    title: 'Amount (₹)', field: 'dr_acc_amount',
                    headerSort: false, hozAlign: 'right', headerHozAlign: 'right',
                    resizable: true, width: AMT_W, minWidth: 160, widthShrink: 0,
                    formatter: accAmtFmt('dr'),
                },
                {
                    title: 'Total (₹)', field: 'dr_grp_total',
                    headerSort: false, hozAlign: 'right', headerHozAlign: 'right',
                    resizable: true, width: AMT_W, minWidth: 160, widthShrink: 0,
                    formatter: grpTotalFmt('dr'),
                },
            ],
        },
        {
            title: '',
            cssClass: 'pl-sep-grp',
            columns: [
                {
                    title: '', field: '_sep',
                    width: 3, headerSort: false, resizable: true,
                    cssClass: 'pl-sep-col',
                    formatter(cell) {
                        const el = cell.getElement();
                        el.style.background  = '#1a3a6b';
                        el.style.padding     = '0';
                        el.style.borderLeft  = 'none';
                        el.style.borderRight = 'none';
                        return '';
                    },
                },
            ],
        },
        {
            title: 'Credit',
            columns: [
                {
                    title: 'Particulars', field: 'cr_name',
                    headerSort: false, resizable: true, widthGrow: 1,
                    formatter: nameFmt('cr'),
                },
                {
                    title: 'Amount (₹)', field: 'cr_acc_amount',
                    headerSort: false, hozAlign: 'right', headerHozAlign: 'right',
                    resizable: true, width: AMT_W, minWidth: 160, widthShrink: 0,
                    formatter: accAmtFmt('cr'),
                },
                {
                    title: 'Total (₹)', field: 'cr_grp_total',
                    headerSort: false, hozAlign: 'right', headerHozAlign: 'right',
                    resizable: true, width: AMT_W, minWidth: 160, widthShrink: 0,
                    formatter: grpTotalFmt('cr'),
                },
            ],
        },
    ];
}

/* ── Tabulator init ───────────────────────────────────────── */
function makeTable(elId, rows) {
    tables[elId] = new Tabulator(`#${elId}`, {
        data:           rows,
        layout:         'fitColumns',
        headerVisible:  true,
        selectable:     false,
        rowFormatter(row) {
            const d  = row.getData();
            const el = row.getElement();
            if (d.dr_type === 'total') {
                el.style.borderTop    = '2px solid #1a3a6b';
                el.style.borderBottom = '2px solid #1a3a6b';
            } else {
                el.style.borderTop    = '';
                el.style.borderBottom = '';
            }
        },
        columns: plTableColumns(),
        placeholder: '<div class="text-center py-4 text-muted">No data</div>',
    });
    tables[elId].on('tableBuilt', function () { this.redraw(true); });
}

/* ── Summary strip ────────────────────────────────────────── */
function buildSummaryStrip(trading, pl) {
    const gpAmt   = trading.is_gross_profit ? trading.gross_profit : trading.gross_loss;
    const gpLabel = trading.is_gross_profit ? 'Gross Profit' : 'Gross Loss';
    const gpCls   = trading.is_gross_profit ? 'profit' : 'loss';
    let netHtml = '';
    if (pl) {
        const netAmt   = pl.is_net_profit ? pl.net_profit : pl.net_loss;
        const netLabel = pl.is_net_profit ? 'Net Profit' : 'Net Loss';
        const netCls   = pl.is_net_profit ? 'profit' : 'loss';
        netHtml = `<div class="pl-sum-item"><span class="pl-sum-label">${netLabel}</span><span class="pl-sum-value ${netCls}">${fmt(netAmt)}</span></div>`;
    }
    return `<div class="pl-summary-strip">
        <div class="pl-sum-item"><span class="pl-sum-label">${gpLabel}</span><span class="pl-sum-value ${gpCls}">${fmt(gpAmt)}</span></div>
        <div class="pl-sum-item"><span class="pl-sum-label">Opening Stock</span><span class="pl-sum-value">${fmt(trading.opening_stock_total)}</span></div>
        <div class="pl-sum-item"><span class="pl-sum-label">Closing Stock</span><span class="pl-sum-value">${fmt(trading.closing_stock_total)}</span></div>
        ${netHtml}
    </div>`;
}

/* ── Date badge ───────────────────────────────────────────── */
function plDateBadge() {
    $('#pl_date_badge_text').text(`${fmtD(plFilter.from_date)} — ${fmtD(plFilter.to_date)}`);
    $('#pl_date_badge').show();
}

/* ── Print / Export ───────────────────────────────────────── */
function plPrint()  {
    if (!plFilter.from_date) { showToast('error', 'Generate the report first.'); return; }
    printReport(plPrintUrl, { ...plFilter, format: 'print', orientation: 'landscape' });
}
function plExport() {
    if (!plFilter.from_date) { showToast('error', 'Generate the report first.'); return; }
    downloadExcel(plExcelUrl, plFilter);
}

/* ── Helpers ──────────────────────────────────────────────── */
function plBySection(items) {
    return (items || []).reduce((m, i) => { (m[i.section] = m[i.section] || []).push(i); return m; }, {});
}
function plNorm(v) {
    if (!v) return '';
    if (/^\d{2}-\d{2}-\d{4}$/.test(v)) { const [d,m,y] = v.split('-'); return `${y}-${m}-${d}`; }
    return v;
}
function fmtD(iso) { return typeof formatDateToDMY === 'function' ? formatDateToDMY(iso) : (iso || ''); }
function fmt(n)    { return typeof formatIndianNumber === 'function' ? formatIndianNumber(n) : Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2 }); }
function plEsc(s)  { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

/* ── Drill-down Modals (Trial Balance Style) ──────────────────────────────── */
let gwTable = null;
let ldrTable = null;
let ldrCurrentAccountId = null;
let ldrCurrentAccountName = null;
let ldrCurrentFromDate = null;
let ldrCurrentToDate = null;

$(function() {
    new DateInput('#ldr_from_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('#ldr_to_date',   FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    $('#gw_print_btn').on('click', e => { e.preventDefault(); gwPrint(); });
    $('#gw_excel_btn').on('click', e => { e.preventDefault(); gwExport(); });

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

    $('#group_wise_modal').on('shown.bs.modal', function () {
        if (gwTable) gwTable.redraw(true);
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

    $('#ldr_print_btn').on('click', e => { e.preventDefault(); ldrPrint(); });
    $('#ldr_excel_btn').on('click', e => { e.preventDefault(); ldrExport(); });
});

// We need helpers for dates and columns
function tbNormalizeDate(d) {
    if (!d) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(d)) return d;
    if (typeof formatDateToYMD === 'function') return formatDateToYMD(d) || '';
    return d;
}

function tbAmountColumn(title, field, signed) {
    return {
        title, field, width: 220, hozAlign: 'right', headerHozAlign: 'right', headerSort: false,
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

window.openGroupModal = function(groupId) {
    const grp = window.PL_GROUPS[groupId];
    if (!grp) return;
    showLoader('Loading group data…');
    
    // First, hit the Trial Balance select-group endpoint which saves group_id in session
    $.ajax({
        url: tbSelectGroupUrl,
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        data: { group_id: groupId },
        success() {
            // Then load the actual balance data which relies on the session variable
            $.ajax({
                url: tbGroupBalanceUrl,
                method: 'GET',
                data: { as_on_date: plFilter.to_date, view_type: 'balance_only' },
                success(res) {
                    hideLoader();
                    if (!res.success) {
                        Swal.fire({ icon: 'error', title: 'No Data', text: res.message || 'No accounts found in this group.' });
                        return;
                    }
                    const data   = res.data  || {};
                    const rows   = data.data || [];
                    const totals = data.grand_total || {};
        
                    $('#gw_modal_title').text(grp.name || data.group_name || 'Group Wise Trial Balance');
        
                    if (plFilter.to_date) {
                        const label = fmtD(plFilter.to_date);
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
                    $('#gw_footer_group_name').text(grp.name || data.group_name || '—');
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
        },
        error(xhr) {
            hideLoader();
            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed to select group.' });
        },
    });
};

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
            { title: 'Sr No.', formatter: 'rownum', width: 60, hozAlign: 'center', headerHozAlign: 'center', headerSort: false },
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
        openLedgerModal(d.account_id, d.account_name);
    });
}

function gwPrint() {
    printReport(tbGroupPrintUrl, { as_on_date: plFilter.to_date, view_type: 'balance_only', format: 'print', orientation: 'portrait' });
}
function gwExport() {
    downloadExcel(tbGroupExcelUrl, { as_on_date: plFilter.to_date, view_type: 'balance_only' });
}

window.openLedgerModal = function(accountId, accountName) {
    ldrCurrentAccountId   = accountId;
    ldrCurrentAccountName = accountName;

    $('#ldr_modal_title').text(accountName || '');
    $('#ldr_date_badge').hide();
    $('#ldr_opening_bar').hide();
    $('#ldr_closing_bar').hide();
    $('#ldr_totals_bar').hide();
    $('#ledger_detail_table').css('display', 'none');
    $('#ldr_inner_loader').css('display', 'flex');

    $('#ldr_from_date').val(fmtD(plFilter.from_date));
    $('#ldr_to_date').val(fmtD(plFilter.to_date));

    $('#ledger_detail_modal').modal('show');
    ldrLoadData(accountId, accountName, plFilter.from_date, plFilter.to_date);
};

function ldrLoadData(accountId, accountName, fromDate, toDate) {
    ldrCurrentFromDate = fromDate;
    ldrCurrentToDate   = toDate;
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
            $('#ldr_date_text').text(`${fmtD(fromDate)} — ${fmtD(toDate)}`);
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
            { title: 'Sr No.', formatter: 'rownum', width: 55, hozAlign: 'center', headerHozAlign: 'center', headerSort: false },
            {
                title: 'Date', field: 'voucher_date', width: 130, headerSort: false,
                formatter: cell => {
                    const v = cell.getValue();
                    return v ? `<span class="text-dark">${fmtD(v)}</span>` : '';
                },
            },
            {
                title: 'Particulars', field: 'against_account_name', minWidth: 200, headerSort: false,
                formatter: cell => {
                    const v = cell.getValue() || '';
                    cell.getElement().setAttribute('title', v);
                    return `<span class="text-dark">${v}</span>`;
                },
            },
            {
                title: 'Voucher Type', field: 'voucher_type', width: 180, headerSort: false,
                formatter: cell => `<span class="text-dark">${cell.getValue() || ''}</span>`,
            },
            {
                title: 'Voucher No.', field: 'voucher_serial', width: 140, headerSort: false,
                formatter: cell => {
                    const d = cell.getRow().getData();
                    const serial = d.voucher_serial || '';
                    const ref    = d.reference_number || '';
                    const num    = serial && ref ? `${serial}/${ref}` : (serial || ref);
                    return `<span class="text-dark">${num}</span>`;
                },
            },
            {
                title: 'Debit (₹)', field: 'debit', width: 180, hozAlign: 'right', headerHozAlign: 'right', headerSort: false,
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
                title: 'Credit (₹)', field: 'credit', width: 180, hozAlign: 'right', headerHozAlign: 'right', headerSort: false,
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
                title: 'Balance (₹)', field: 'running_balance', width: 200, hozAlign: 'right', headerHozAlign: 'right', headerSort: false,
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

function ldrPrint() {
    if (!ldrCurrentAccountId || !ldrCurrentFromDate || !ldrCurrentToDate) return;
    printReport(tbAccountLedgerPrint, { account_id: ldrCurrentAccountId, from_date: ldrCurrentFromDate, to_date: ldrCurrentToDate, format: 'print' });
}
function ldrExport() {
    if (!ldrCurrentAccountId || !ldrCurrentFromDate || !ldrCurrentToDate) return;
    downloadExcel(tbAccountLedgerExcel, { account_id: ldrCurrentAccountId, from_date: ldrCurrentFromDate, to_date: ldrCurrentToDate });
}
