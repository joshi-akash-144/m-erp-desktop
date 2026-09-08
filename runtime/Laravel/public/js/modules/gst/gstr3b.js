$(document).ready(function () {
    new DateInput('.date-format', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    const loadingSpinner = `<div class="d-flex justify-content-center align-items-center p-4">
        <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <span class="ms-3 text-muted fw-bold">Loading report data...</span>
    </div>`;

    // ─── Helpers ──────────────────────────────────────────────────────────────────
    function fmt(val) {
        if (val === '' || val === null || val === undefined) return '';
        const num = parseFloat(val);
        if (isNaN(num)) return val;
        return num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function fmtInt(val) {
        if (val === '' || val === null || val === undefined) return '';
        const num = parseInt(val, 10);
        return isNaN(num) ? val : num.toLocaleString('en-IN');
    }

    function fmtRate(val) {
        if (!val || parseFloat(val) === 0) return '—';
        return parseFloat(val) + '%';
    }

    function fmtDate(val) {
        if (!val || val === '—' || val === '-') return '—';
        const str = String(val).trim();
        // If already DD-MM-YYYY
        if (/^\d{2}-\d{2}-\d{4}$/.test(str)) return str;
        // If YYYY-MM-DD
        if (/^\d{4}-\d{2}-\d{2}/.test(str)) {
            const parts = str.substring(0, 10).split('-');
            return `${parts[2]}-${parts[1]}-${parts[0]}`;
        }
        const d = new Date(str);
        if (!isNaN(d.getTime())) {
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = d.getFullYear();
            return `${day}-${month}-${year}`;
        }
        return str;
    }

    // ─── Section Configuration ───────────────────────────────────────────────────
    const DRILL_SECTIONS = {
        '3.1_a': '3.1(a) Outward Taxable Supplies (Other than zero rated, nil rated and exempted)',
        '3.1_b': '3.1(b) Outward Taxable Supplies (Zero Rated - Exports)',
        '3.1_c': '3.1(c) Other Outward Supplies (Nil Rated, Exempted)',
        '3.1_d': '3.1(d) Inward Supplies Liable to Reverse Charge',
        '3.2':   '3.2 Inter-State Supplies to Unregistered Persons',
        '4_a_1': '4(A)(1) Import of Goods',
        '4_a_2': '4(A)(2) Import of Services',
        '4_a_3': '4(A)(3) Inward Supplies Liable to Reverse Charge (ITC)',
        '4_a_5': '4(A)(5) All Other ITC (B2B Purchases)',
        '4_b_2': '4(B)(2) ITC Reversed - Others (Debit Notes / Returns)',
        '5_nil': '5. Inward Nil Rated / Exempted Supplies',
        '5_nil_inter': '5. Inward Nil Rated / Exempted Supplies (Inter-State)',
        '5_nil_intra': '5. Inward Nil Rated / Exempted Supplies (Intra-State)'
    };

    function getColumnsForSection(sectionKey) {
        const base = [
            { title: "#", formatter: "rownum", minWidth: 160, hozAlign: "center", headerHozAlign: "center", headerSort: false, resizable: true },
        ];

        const partyName   = { title: "Party Name", field: "account_name", minWidth: 200, resizable: true };
        const gstin       = { title: "GSTIN", field: "gstin", minWidth: 160, resizable: true };
        const invoiceNo   = { title: "Invoice / Note No", field: "invoice_no", minWidth: 160, resizable: true };
        const invoiceDate = { title: "Date", field: "invoice_date", minWidth: 160, hozAlign: "center", headerHozAlign: "center", resizable: true, formatter: function(cell) { return fmtDate(cell.getValue()); } };
        const pos         = { title: "Place of Supply", field: "place_of_supply", minWidth: 160, resizable: true };
        const rc          = { title: "RC", field: "reverse_charge", minWidth: 160, hozAlign: "center", headerHozAlign: "center", formatter: (c) => c.getValue() ? 'Y' : 'N', resizable: true };

        function getBottomCalc(field) {
            return function() {
                if (!window.gstr3bSummaryData || !currentDrillSection) return 0;
                if (currentDrillSection === '3.2') {
                    let total = 0;
                    const arr = window.gstr3bSummaryData['sec_3_2'];
                    if (arr) {
                        arr.forEach(r => {
                            if (field === 'taxable_amount') total += parseFloat(r.taxable_amount) || 0;
                            if (field === 'igst_amount') total += parseFloat(r.igst_amount) || 0;
                            if (field === 'cess_amount') total += parseFloat(r.cess_amount) || 0;
                        });
                    }
                    return total;
                }
                
                let key = 'sec_' + currentDrillSection.replace(/\./g, '_');
                let secData = window.gstr3bSummaryData[key];
                
                if (!secData) {
                    if (currentDrillSection === '5_nil_inter') {
                        secData = window.gstr3bSummaryData['sec_5_nil'];
                        if (secData) {
                            if (field === 'taxable_amount') return parseFloat(secData['inter_taxable_amount']) || 0;
                            if (field === 'invoice_value') return parseFloat(secData['inter_invoice_value']) || 0;
                            return 0;
                        }
                    } else if (currentDrillSection === '5_nil_intra') {
                        secData = window.gstr3bSummaryData['sec_5_nil'];
                        if (secData) {
                            if (field === 'taxable_amount') return parseFloat(secData['intra_taxable_amount']) || 0;
                            if (field === 'invoice_value') return parseFloat(secData['intra_invoice_value']) || 0;
                            return 0;
                        }
                    }
                    return 0;
                }
                
                if (field === 'cgst_amount') return parseFloat(secData['cgst']) || 0;
                if (field === 'sgst_amount') return parseFloat(secData['sgst']) || 0;
                if (field === 'igst_amount') return parseFloat(secData['igst']) || 0;
                if (field === 'cess_amount') return parseFloat(secData['cess']) || 0;
                if (field === 'total_tax_amount') return parseFloat(secData['total_tax']) || 0;
                
                return parseFloat(secData[field]) || 0;
            };
        }

        const taxable   = { title: "Taxable Amount", field: "taxable_amount", minWidth: 160, hozAlign: "right", headerHozAlign: "right", resizable: true, formatter: (c) => fmt(c.getValue()), bottomCalc: getBottomCalc('taxable_amount'), bottomCalcFormatter: (c) => fmt(c.getValue()) };
        const cgstRate  = { title: "CGST %", field: "cgst_rate", minWidth: 160, hozAlign: "center", headerHozAlign: "center", resizable: true, formatter: (c) => fmtRate(c.getValue()) };
        const cgst      = { title: "CGST Amount", field: "cgst_amount", minWidth: 160, hozAlign: "right", headerHozAlign: "right", resizable: true, formatter: (c) => fmt(c.getValue()), bottomCalc: getBottomCalc('cgst_amount'), bottomCalcFormatter: (c) => fmt(c.getValue()) };
        const sgstRate  = { title: "SGST %", field: "sgst_rate", minWidth: 160, hozAlign: "center", headerHozAlign: "center", resizable: true, formatter: (c) => fmtRate(c.getValue()) };
        const sgst      = { title: "SGST Amount", field: "sgst_amount", minWidth: 160, hozAlign: "right", headerHozAlign: "right", resizable: true, formatter: (c) => fmt(c.getValue()), bottomCalc: getBottomCalc('sgst_amount'), bottomCalcFormatter: (c) => fmt(c.getValue()) };
        const igstRate  = { title: "IGST %", field: "igst_rate", minWidth: 160, hozAlign: "center", headerHozAlign: "center", resizable: true, formatter: (c) => fmtRate(c.getValue()) };
        const igst      = { title: "IGST Amount", field: "igst_amount", minWidth: 160, hozAlign: "right", headerHozAlign: "right", resizable: true, formatter: (c) => fmt(c.getValue()), bottomCalc: getBottomCalc('igst_amount'), bottomCalcFormatter: (c) => fmt(c.getValue()) };
        const cess      = { title: "CESS Amount", field: "cess_amount", minWidth: 160, hozAlign: "right", headerHozAlign: "right", resizable: true, formatter: (c) => fmt(c.getValue()), bottomCalc: getBottomCalc('cess_amount'), bottomCalcFormatter: (c) => fmt(c.getValue()) };
        const totalTax  = { title: "Total Tax Amount", field: "total_tax_amount", minWidth: 160, hozAlign: "right", headerHozAlign: "right", resizable: true, formatter: (c) => fmt(c.getValue()), bottomCalc: getBottomCalc('total_tax_amount'), bottomCalcFormatter: (c) => fmt(c.getValue()) };
        const invVal    = { title: "Invoice Value", field: "invoice_value", minWidth: 160, hozAlign: "right", headerHozAlign: "right", resizable: true, formatter: (c) => fmt(c.getValue()), bottomCalc: getBottomCalc('invoice_value'), bottomCalcFormatter: (c) => fmt(c.getValue()) };

        switch (sectionKey) {
            case '3.1_c':
            case '5_nil':
            case '5_nil_inter':
            case '5_nil_intra':
                return [...base, partyName, invoiceNo, invoiceDate, pos, taxable, invVal];
            case '4_a_1':
            case '4_a_2':
                return [...base, partyName, invoiceNo, invoiceDate, taxable, igstRate, igst, cess, totalTax, invVal];
            default:
                return [...base, partyName, gstin, invoiceNo, invoiceDate, pos, rc, taxable, cgstRate, cgst, sgstRate, sgst, igstRate, igst, cess, totalTax, invVal];
        }
    }

    // ─── Data Builders for Summary ───────────────────────────────────────────────
    // Event listener for HTML table drilldown
    $(document).on('click', '.gstr3b-section-row', function() {
        const sectionKey = $(this).data('section');
        const sectionTitle = $(this).data('label');
        if (sectionKey) {
            openDrillModal(sectionKey, sectionTitle);
        }
    });

    window.summaryTables = [];

    function renderSummary(data) {
        // Clear previous tables
        if (window.summaryTables && window.summaryTables.length > 0) {
            window.summaryTables.forEach(t => t.destroy());
            window.summaryTables = [];
        }

        const container = $('#gstr3b_tabulator');
        container.empty();

        let html = `
            <div class="mb-4 border shadow-sm">
                <div class="p-2 fw-bold text-dark border-bottom" style="background:#f1f5f9; font-size:14px;">3.1 Details of Outward Supplies and inward supplies liable to reverse charge (other than those covered by Table 3.1.1)</div>
                <div id="tab_31"></div>
                <div class="p-2 fw-bold text-dark border-bottom border-top" style="background:#f1f5f9; font-size:14px;">3.1.1 Details of Supplies Notified u/s 9(5) of the CGST Act, 2017 and Corresponding Provision in IGST/UTGST/SGST Acts</div>
                <div id="tab_311"></div>
            </div>
            
            <div class="mb-4 border shadow-sm">
                <div class="p-2 fw-bold text-dark border-bottom" style="background:#f1f5f9; font-size:14px;">3.2 Of the Supplies shown in 3.1(a) and 3.1.1(i), details of inter-State supplies made to unregistered persons,composition taxable persons and UIN holders</div>
                <div id="tab_32"></div>
            </div>
            
            <div class="mb-4 border shadow-sm">
                <div class="p-2 fw-bold text-dark border-bottom" style="background:#f1f5f9; font-size:14px;">4. Eligible ITC</div>
                <div id="tab_4"></div>
            </div>
            
            <div class="mb-4 border shadow-sm">
                <div class="p-2 fw-bold text-dark border-bottom" style="background:#f1f5f9; font-size:14px;">5. Values of exempt, nil-rated and non-GST inward supplies</div>
                <div id="tab_5"></div>
            </div>
            
            <div class="mb-4 border shadow-sm">
                <div class="p-2 fw-bold text-dark border-bottom" style="background:#f1f5f9; font-size:14px;">6.1 Payment of tax</div>
                <div id="tab_61"></div>
            </div>
            
            <div class="mb-4 border shadow-sm">
                <div class="p-2 fw-bold text-dark border-bottom" style="background:#f1f5f9; font-size:14px;">6.2 TDS/TCS Credit</div>
                <div id="tab_62"></div>
            </div>
        `;
        container.html(html);

        function fmt(val) {
            if (val === '' || val === null || val === undefined) return '';
            const num = parseFloat(val);
            return isNaN(num) ? val : num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        const commonFormatter = function(cell) {
            const d = cell.getData();
            if (d._isLabel) return `<span style="font-weight:600; color:#334155; background:#f8f9fa; display:block; padding: 4px;">${cell.getValue()}</span>`;
            if (d._isHighlight) return `<span style="font-weight:bold;">${cell.getValue() !== undefined ? cell.getValue() : ''}</span>`;
            
            const field = cell.getField();
            if (field !== 'label' && d[field] !== undefined && d[field] !== '') {
                if (field === 'cess' && parseFloat(d[field]) === 0) return '';
                return fmt(d[field]);
            }
            
            if (field === 'label' && d._sectionKey && d.count > 0 && typeof DRILL_SECTIONS !== 'undefined' && DRILL_SECTIONS[d._sectionKey]) {
                const titleStr = String(cell.getValue()).replace(/'/g, "\\'").replace(/<[^>]*>?/gm, '').trim();
                return `<span class="text-primary" style="font-weight:500; cursor:pointer;" onclick="window.openGstr3bDrillModal('${d._sectionKey}', '${titleStr}')" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">${cell.getValue()} <i class="fas fa-external-link-alt ms-1 small" style="opacity:0.7"></i></span>`;
            }
            return cell.getValue() || '';
        };

        const numFmt = function(cell) {
            const d = cell.getData();
            if (d._isLabel) return `<span style="background:#f8f9fa; display:block; padding: 4px;">&nbsp;</span>`;
            if (d._isHighlight) return `<span style="font-weight:bold;">${cell.getValue() !== undefined && cell.getValue() !== '' ? fmt(cell.getValue()) : ''}</span>`;
            
            const val = cell.getValue();
            const field = cell.getField();
            
            if (field === 'cess' && parseFloat(val) === 0) return '';
            
            let formattedVal = val !== undefined && val !== '' ? fmt(val) : '';
            
            if (d._sectionKey === '5_nil' && parseFloat(val) > 0 && (field === 'inter' || field === 'intra')) {
                const drillKey = field === 'inter' ? '5_nil_inter' : '5_nil_intra';
                const drillTitle = field === 'inter' ? '5. Inward Nil Rated / Exempted Supplies (Inter-State)' : '5. Inward Nil Rated / Exempted Supplies (Intra-State)';
                return `<span class="text-primary" style="font-weight:500; cursor:pointer;" onclick="window.openGstr3bDrillModal('${drillKey}', '${drillTitle}')" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">${formattedVal} <i class="fas fa-external-link-alt ms-1 small" style="opacity:0.7"></i></span>`;
            }
            
            return formattedVal;
        };

        const rowClick = function(e, row) {
            const d = row.getData();
            if (d._sectionKey && d.count > 0 && DRILL_SECTIONS[d._sectionKey]) {
                openDrillModal(d._sectionKey, String(d.label).replace(/<[^>]*>?/gm, '').trim());
            }
        };

        const config = {
            layout: "fitColumns",
            headerSort: false,
            resizableColumns: false,
            height: "100%",
            rowFormatter: function(row) {
                const d = row.getData();
                const el = row.getElement();
                if (d._isLabel) {
                    el.style.backgroundColor = '#f8f9fa';
                    el.style.fontWeight = 'bold';
                }
                if (d._isHighlight) {
                    el.style.backgroundColor = '#f1f5f9';
                    el.style.fontWeight = 'bold';
                }
            }
        };

        // ── 3.1 ──
        let t31 = { taxable_amount: 0, igst: 0, cgst: 0, sgst: 0, cess: 0 };
        ['sec_3_1_a', 'sec_3_1_b', 'sec_3_1_c', 'sec_3_1_d', 'sec_3_1_e'].forEach(k => {
            if (data[k]) {
                t31.taxable_amount += parseFloat(data[k].taxable_amount) || 0;
                t31.igst += parseFloat(data[k].igst) || 0;
                t31.cgst += parseFloat(data[k].cgst) || 0;
                t31.sgst += parseFloat(data[k].sgst) || 0;
                t31.cess += parseFloat(data[k].cess) || 0;
            }
        });

        window.summaryTables.push(new Tabulator("#tab_31", Object.assign({}, config, {
            data: [
                {label: '(a) Outward txbl. supplies(other than zero rated<br>&nbsp;&nbsp;&nbsp;&nbsp;nil rated and exempted)', taxable_amount: data.sec_3_1_a?.taxable_amount, igst: data.sec_3_1_a?.igst, cgst: data.sec_3_1_a?.cgst, sgst: data.sec_3_1_a?.sgst, cess: data.sec_3_1_a?.cess, count: data.sec_3_1_a?.count, _sectionKey: '3.1_a'},
                {label: '(b) Outward taxable supplies(zero rated)', taxable_amount: data.sec_3_1_b?.taxable_amount, igst: data.sec_3_1_b?.igst, cgst: data.sec_3_1_b?.cgst, sgst: data.sec_3_1_b?.sgst, cess: data.sec_3_1_b?.cess, count: data.sec_3_1_b?.count, _sectionKey: '3.1_b'},
                {label: '(c) Other outward supp.(Nil rated,exempt)', taxable_amount: data.sec_3_1_c?.taxable_amount, igst: data.sec_3_1_c?.igst, cgst: data.sec_3_1_c?.cgst, sgst: data.sec_3_1_c?.sgst, cess: data.sec_3_1_c?.cess, count: data.sec_3_1_c?.count, _sectionKey: '3.1_c'},
                {label: '(d) Inward supp.(liable to Rev. charge)', taxable_amount: data.sec_3_1_d?.taxable_amount, igst: data.sec_3_1_d?.igst, cgst: data.sec_3_1_d?.cgst, sgst: data.sec_3_1_d?.sgst, cess: data.sec_3_1_d?.cess, count: data.sec_3_1_d?.count, _sectionKey: '3.1_d'},
                {label: '(e) Non-GST outward supplies', taxable_amount: data.sec_3_1_e?.taxable_amount, igst: data.sec_3_1_e?.igst, cgst: data.sec_3_1_e?.cgst, sgst: data.sec_3_1_e?.sgst, cess: data.sec_3_1_e?.cess, count: data.sec_3_1_e?.count},
                {label: '<span class="float-end pe-2">Total</span>', taxable_amount: t31.taxable_amount, igst: t31.igst, cgst: t31.cgst, sgst: t31.sgst, cess: t31.cess, _isHighlight: true}
            ],
            columns: [
                {title: "Nature of Supplier", field: "label", minWidth: 250, formatter: commonFormatter, headerSort: false},
                {title: "Txbl.Value", field: "taxable_amount", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false},
                {title: "IGST", field: "igst", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false},
                {title: "CGST", field: "cgst", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false},
                {title: "State/UT Tax", field: "sgst", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false},
                {title: "Cess", field: "cess", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false}
            ],
            rowClick: rowClick
        })));

        window.summaryTables.push(new Tabulator("#tab_311", Object.assign({}, config, {
            data: [
                {label: '(i) Taxable supplies on which E-Commerce operator pays<br>&nbsp;&nbsp;&nbsp;&nbsp;tax u/s 9(5) [To be furnished by the E-Commerce operator]'},
                {label: '(ii) Taxable supplies made by the registered person through<br>&nbsp;&nbsp;&nbsp;&nbsp;E-Commerce operator, on which E-Commerce operator is<br>&nbsp;&nbsp;&nbsp;&nbsp;required to pay tax u/s 9(5) [To be furnished by registered<br>&nbsp;&nbsp;&nbsp;&nbsp;person making supplies through E-Commerce operator]'}
            ],
            columns: [
                {title: "Description", field: "label", minWidth: 250, formatter: commonFormatter, headerSort: false},
                {title: "Txbl. Value", field: "taxable_amount", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false},
                {title: "IGST", field: "igst", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false},
                {title: "CGST", field: "cgst", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false},
                {title: "State/UT Tax", field: "sgst", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false},
                {title: "Cess", field: "cess", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false}
            ]
        })));

        // ── 3.2 ──
        let d32 = [{label: 'Supplies made to UnReg. Persons', _isLabel: true}];
        let t32 = { count: 0, taxable_amount: 0, igst: 0 };
        if (data.sec_3_2 && data.sec_3_2.length > 0) {
            data.sec_3_2.forEach(r => {
                d32.push({label: `&nbsp;&nbsp;&nbsp;&nbsp;${r.place_of_supply}`, taxable_amount: r.taxable_amount, igst: r.igst_amount, count: r.count, _sectionKey: '3.2'});
                t32.taxable_amount += parseFloat(r.taxable_amount) || 0;
                t32.igst += parseFloat(r.igst_amount) || 0;
            });
        }
        d32.push({label: '<span class="float-end pe-2">Total</span>', taxable_amount: t32.taxable_amount, igst: t32.igst, _isHighlight: true});
        d32.push({label: 'Supp. made to Composition Dealers', _isLabel: true});
        d32.push({label: '<span class="float-end pe-2">Total</span>', _isHighlight: true});
        d32.push({label: 'Supplies made to UIN holder', _isLabel: true});
        d32.push({label: '<span class="float-end pe-2">Total</span>', _isHighlight: true});

        window.summaryTables.push(new Tabulator("#tab_32", Object.assign({}, config, {
            data: d32,
            columns: [
                {title: "Place of Supply(State/UT)", field: "label", minWidth: 250, formatter: commonFormatter, headerSort: false},
                {title: "Total Taxable Value", field: "taxable_amount", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false},
                {title: "Amount of IGST", field: "igst", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false}
            ],
            rowClick: rowClick
        })));

        // ── 4 ──
        window.summaryTables.push(new Tabulator("#tab_4", Object.assign({}, config, {
            data: [
                {label: '(A) ITC Available(whether in full or part)', _isLabel: true},
                {label: '&nbsp;&nbsp;&nbsp;&nbsp;(1) Import of goods', igst: data.sec_4_a_1?.igst, cgst: data.sec_4_a_1?.cgst, sgst: data.sec_4_a_1?.sgst, cess: data.sec_4_a_1?.cess, count: data.sec_4_a_1?.count, _sectionKey: '4_a_1'},
                {label: '&nbsp;&nbsp;&nbsp;&nbsp;(2) Import of services', igst: data.sec_4_a_2?.igst, cgst: data.sec_4_a_2?.cgst, sgst: data.sec_4_a_2?.sgst, cess: data.sec_4_a_2?.cess, count: data.sec_4_a_2?.count, _sectionKey: '4_a_2'},
                {label: '&nbsp;&nbsp;&nbsp;&nbsp;(3) Inward supplies liable to reverse (other than 1 & 2 above)', igst: data.sec_4_a_3?.igst, cgst: data.sec_4_a_3?.cgst, sgst: data.sec_4_a_3?.sgst, cess: data.sec_4_a_3?.cess, count: data.sec_4_a_3?.count, _sectionKey: '4_a_3'},
                {label: '&nbsp;&nbsp;&nbsp;&nbsp;(4) Inward supplies from ISD', igst: data.sec_4_a_4?.igst, cgst: data.sec_4_a_4?.cgst, sgst: data.sec_4_a_4?.sgst, cess: data.sec_4_a_4?.cess, count: data.sec_4_a_4?.count, _sectionKey: '4_a_4'},
                {label: '&nbsp;&nbsp;&nbsp;&nbsp;(5) All other ITC', igst: data.sec_4_a_5?.igst, cgst: data.sec_4_a_5?.cgst, sgst: data.sec_4_a_5?.sgst, cess: data.sec_4_a_5?.cess, count: data.sec_4_a_5?.count, _sectionKey: '4_a_5'},
                {label: '(B) ITC Reversed', _isLabel: true},
                {label: '&nbsp;&nbsp;&nbsp;&nbsp;(1) As per rules 38,42 & 43 of CGST Rules and section 17(5)', igst: data.sec_4_b_1?.igst, cgst: data.sec_4_b_1?.cgst, sgst: data.sec_4_b_1?.sgst, cess: data.sec_4_b_1?.cess},
                {label: '&nbsp;&nbsp;&nbsp;&nbsp;(2) Others', igst: data.sec_4_b_2?.igst, cgst: data.sec_4_b_2?.cgst, sgst: data.sec_4_b_2?.sgst, cess: data.sec_4_b_2?.cess, count: data.sec_4_b_2?.count, _sectionKey: '4_b_2'},
                {label: '(C) Net ITC Available(A) - (B)', igst: data.sec_4_c?.igst, cgst: data.sec_4_c?.cgst, sgst: data.sec_4_c?.sgst, cess: data.sec_4_c?.cess, _isHighlight: true},
                {label: '(D) Other Details', _isLabel: true},
                {label: '&nbsp;&nbsp;&nbsp;&nbsp;(1) ITC reclaimed which was reversed under Table 4(B)(2)<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;in earlier tax period', igst: data.sec_4_d_1?.igst, cgst: data.sec_4_d_1?.cgst, sgst: data.sec_4_d_1?.sgst, cess: data.sec_4_d_1?.cess},
                {label: '&nbsp;&nbsp;&nbsp;&nbsp;(2) Ineligible ITC under section 16(4)<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;& ITC restricted due to PoS rules', igst: data.sec_4_d_2?.igst, cgst: data.sec_4_d_2?.cgst, sgst: data.sec_4_d_2?.sgst, cess: data.sec_4_d_2?.cess}
            ],
            columns: [
                {title: "Details", field: "label", minWidth: 250, formatter: commonFormatter, headerSort: false},
                {title: "Integrated Tax", field: "igst", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false},
                {title: "Central Tax", field: "cgst", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false},
                {title: "State/Ut Tax", field: "sgst", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false},
                {title: "Cess", field: "cess", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false}
            ],
            rowClick: rowClick
        })));

        // ── 5 ──
        window.summaryTables.push(new Tabulator("#tab_5", Object.assign({}, config, {
            data: [
                {label: 'From a supplier under composition scheme, Exempt and Nil rated supply', inter: data.sec_5_nil?.inter_taxable_amount, intra: data.sec_5_nil?.intra_taxable_amount, count: data.sec_5_nil?.count, _sectionKey: '5_nil'},
                {label: 'Non GST supply', inter: data.sec_5_nongst?.inter_taxable_amount, intra: data.sec_5_nongst?.intra_taxable_amount}
            ],
            columns: [
                {title: "Nature of supplies", field: "label", minWidth: 250, formatter: commonFormatter, headerSort: false},
                {title: "Inter-State supplies", field: "inter", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false},
                {title: "Intra-State supplies", field: "intra", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false}
            ],
            rowClick: rowClick
        })));

        // ── 6.1 ──
        window.summaryTables.push(new Tabulator("#tab_61", Object.assign({}, config, {
            data: [
                {label: 'Other than Reverse Charge', _isLabel: true},
                {label: 'Integrated Tax', payable: data.total_liability?.igst, itc_igst: data.paid_through_itc?.igst, cash: data.tax_payable_cash?.igst},
                {label: 'Central Tax', payable: data.total_liability?.cgst, itc_cgst: data.paid_through_itc?.cgst, cash: data.tax_payable_cash?.cgst, _dash2: true},
                {label: 'State/UT Tax', payable: data.total_liability?.sgst, itc_sgst: data.paid_through_itc?.sgst, cash: data.tax_payable_cash?.sgst, _dash1: true},
                {label: 'Cess', payable: data.total_liability?.cess, itc_cess: data.paid_through_itc?.cess, cash: data.tax_payable_cash?.cess},
                {label: 'Reverse Charge', _isLabel: true},
                {label: 'Integrated Tax'},
                {label: 'Central Tax'},
                {label: 'State/UT Tax'},
                {label: 'Cess'}
            ],
            columns: [
                {title: "Description", field: "label", minWidth: 150, formatter: commonFormatter, headerSort: false},
                {title: "Tax Payable", field: "payable", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false},
                {
                    title: "Paid through ITC", headerHozAlign: "center",
                    columns: [
                        {title: "IGST", field: "itc_igst", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false},
                        {title: "CGST", field: "itc_cgst", minWidth: 160, hozAlign: "right", formatter: function(c){ return c.getData()._dash1 ? '<span class="text-muted">-------</span>' : numFmt(c); }, headerSort: false},
                        {title: "SGST/UTGST", field: "itc_sgst", minWidth: 160, hozAlign: "right", formatter: function(c){ return c.getData()._dash2 ? '<span class="text-muted">-------</span>' : numFmt(c); }, headerSort: false},
                        {title: "Cess", field: "itc_cess", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false}
                    ]
                },
                {title: "Tax Paid<br>TDS/TCS", field: "tds", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false},
                {title: "Tax/Cess<br>Paid in Cash", field: "cash", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false},
                {title: "Interest", field: "interest", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false},
                {title: "Late Fee", field: "late_fee", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false}
            ]
        })));

        // ── 6.2 ──
        window.summaryTables.push(new Tabulator("#tab_62", Object.assign({}, config, {
            data: [
                {label: 'TDS'},
                {label: 'TCS'}
            ],
            columns: [
                {title: "Details", field: "label", minWidth: 250, formatter: commonFormatter, headerSort: false},
                {title: "Integrated Tax", field: "igst", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false},
                {title: "Central Tax", field: "cgst", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false},
                {title: "State/UT Tax", field: "sgst", minWidth: 160, hozAlign: "right", formatter: numFmt, headerSort: false}
            ]
        })));
    }

    // ─── Drill Modal Tabulator ───────────────────────────────────────────────────
    let drillTable = null;
    let currentDrillSection = null;

    function openDrillModal(sectionKey, sectionTitle) {
        currentDrillSection = sectionKey;
        const fromDMY = $('#from_date').val().trim();
        const toDMY   = $('#to_date').val().trim();

        $('#drill_modal_title').text(sectionTitle || DRILL_SECTIONS[sectionKey] || 'Section Detail');
        $('#drill_modal_subtitle').text(`${fromDMY}  →  ${toDMY}`);
        $('#drill_modal_stats').html('');

        $('#gstr3bDrillModal').modal('show');

        const container = document.getElementById('drill_tabulator');
        container.innerHTML = loadingSpinner;

        if (drillTable) {
            drillTable.destroy();
            drillTable = null;
        }

        const [fDay, fMonth, fYear] = fromDMY.split('-');
        const [tDay, tMonth, tYear] = toDMY.split('-');
        const fromYMD = `${fYear}-${fMonth}-${fDay}`;
        const toYMD   = `${tYear}-${tMonth}-${tDay}`;

        drillTable = new Tabulator('#drill_tabulator', {
            layout: 'fitDataStretch',
            progressiveLoad: "scroll",
            paginationMode: "remote",
            paginationSize: 100,
            ajaxURL: GSTR3B_DETAIL_URL,
            ajaxParams: {
                from_date: fromYMD,
                to_date:   toYMD,
                section:   sectionKey,
            },
            ajaxResponse: function(url, params, response) {
                if (response && response.data) {
                    $('#drill_modal_stats').html(
                        `<span class="modal-stat-pill">Vouchers: <span>${response.total ?? response.data.length}</span></span>`
                    );
                    return {
                        last_page: response.last_page || 1,
                        data: response.data
                    };
                }
                return { last_page: 1, data: [] };
            },
            columns: getColumnsForSection(sectionKey),
            height: 'calc(100vh - 120px)',
            placeholder: '<div class="text-muted p-4 text-center"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No records found for this section.</div>'
        });
    }
    
    window.openGstr3bDrillModal = openDrillModal;

    // ─── Export Detail Excel ──────────────────────────────────────────────────────
    $('#btn_export_drill_excel').on('click', function () {
        if (!currentDrillSection) return;

        const fromDMY = $('#from_date').val().trim();
        const toDMY   = $('#to_date').val().trim();
        const [fDay, fMonth, fYear] = fromDMY.split('-');
        const [tDay, tMonth, tYear] = toDMY.split('-');
        const fromYMD = `${fYear}-${fMonth}-${fDay}`;
        const toYMD   = `${tYear}-${tMonth}-${tDay}`;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Exporting...');
        if (typeof showLoader === 'function') showLoader('Please wait... Generating Excel report...');

        $.ajax({
            url: GSTR3B_EXPORT_DETAIL_EXCEL_URL,
            type: 'GET',
            data: {
                from_date: fromYMD,
                to_date:   toYMD,
                section:   currentDrillSection,
            },
            success: function(res) {
                if (res && res.success && res.data && res.data.file_url) {
                    const link = document.createElement('a');
                    link.href = res.data.file_url;
                    link.download = res.data.file_name || 'gstr3b_detail.xlsx';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else if (res && res.message) {
                    if (typeof Swal !== 'undefined') Swal.fire({icon: 'error', title: 'Export Failed', text: res.message});
                    else alert(res.message);
                }
            },
            error: function(err) {
                if (typeof Swal !== 'undefined') Swal.fire({icon: 'error', title: 'Export Error', text: 'Export failed. Please try again.'});
                else alert('Export failed. Please try again.');
            },
            complete: function() {
                if (typeof hideLoader === 'function') hideLoader();
                $btn.prop('disabled', false).html('<i class="fa-solid fa-file-excel me-1"></i> Export Excel');
            }
        });
    });

    // ─── Export Full GSTR-3B Summary Excel ────────────────────────────────────────
    $('#btn_export_gstr3b').on('click', function () {
        const fromDMY = $('#from_date').val().trim();
        const toDMY   = $('#to_date').val().trim();
        const [fDay, fMonth, fYear] = fromDMY.split('-');
        const [tDay, tMonth, tYear] = toDMY.split('-');
        const fromYMD = `${fYear}-${fMonth}-${fDay}`;
        const toYMD   = `${tYear}-${tMonth}-${tDay}`;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Exporting...');
        if (typeof showLoader === 'function') showLoader('Please wait... Generating Excel report...');

        $.ajax({
            url: GSTR3B_EXPORT_EXCEL_URL,
            type: 'GET',
            data: {
                from_date: fromYMD,
                to_date:   toYMD,
            },
            success: function(res) {
                if (res && res.success && res.data && res.data.file_url) {
                    const link = document.createElement('a');
                    link.href = res.data.file_url;
                    link.download = res.data.file_name || 'gstr3b_report.xlsx';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else if (res && res.message) {
                    if (typeof Swal !== 'undefined') Swal.fire({icon: 'error', title: 'Export Failed', text: res.message});
                    else alert(res.message);
                }
            },
            error: function(err) {
                if (typeof Swal !== 'undefined') Swal.fire({icon: 'error', title: 'Export Error', text: 'Export failed. Please try again.'});
                else alert('Export failed. Please try again.');
            },
            complete: function() {
                if (typeof hideLoader === 'function') hideLoader();
                $btn.prop('disabled', false).html('<i class="fa-solid fa-file-excel me-1"></i> Export Excel');
            }
        });
    });

    // ─── Form Submit & Initial Load ───────────────────────────────────────────────
    $('#gstr3b_form').on('submit', function (e) {
        e.preventDefault();

        const fromDMY = $('#from_date').val().trim();
        const toDMY   = $('#to_date').val().trim();

        if (!fromDMY || !toDMY) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Dates',
                    text: 'Please select both From Date and To Date',
                    confirmButtonText: 'OK'
                });
            } else {
                alert('Please select both From Date and To Date');
            }
            return;
        }

        const [fDay, fMonth, fYear] = fromDMY.split('-');
        const [tDay, tMonth, tYear] = toDMY.split('-');
        const fromYMD = `${fYear}-${fMonth}-${fDay}`;
        const toYMD   = `${tYear}-${tMonth}-${tDay}`;

        const STORAGE_KEY = 'gstr3b_dates_' + window.location.pathname;
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify({ from: fromDMY, to: toDMY })); } catch(e) {}

        $('#report_output').removeClass('d-none');
        $('#btn_export_gstr3b').removeClass('d-none');

        const summaryContainer = document.getElementById('gstr3b_tabulator');
        summaryContainer.innerHTML = loadingSpinner;

        $.ajax({
            url: GSTR3B_SUMMARY_URL,
            type: 'GET',
            data: {
                from_date: fromYMD,
                to_date:   toYMD,
            },
            success: function(data) {
                window.gstr3bSummaryData = data;
                summaryContainer.innerHTML = '';
                renderSummary(data);
            },
            error: function() {
                summaryContainer.innerHTML = '<div class="alert alert-danger m-3">Failed to load GSTR-3B report data.</div>';
            }
        });
    });

    $('#filter_clear').on('click', function () {
        $('#from_date').val('');
        $('#to_date').val('');
        $('#btn_export_gstr3b').addClass('d-none');
        document.getElementById('gstr3b_tabulator').innerHTML = `
            <div class="d-flex flex-column justify-content-center align-items-center py-5 text-muted" style="min-height: 250px;">
                <i class="fa-solid fa-file-invoice fa-3x mb-3" style="opacity: 0.2;"></i>
                <h4 class="mb-1 text-secondary">No Data Loaded</h4>
                <p class="mb-0" style="font-size: 0.85rem;">Select dates and click Apply to fetch the GSTR-3B report.</p>
            </div>
        `;
        
        const STORAGE_KEY = 'gstr3b_dates_' + window.location.pathname;
        try { localStorage.removeItem(STORAGE_KEY); } catch(e) {}
    });

    const STORAGE_KEY = 'gstr3b_dates_' + window.location.pathname;
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
            const parsed = JSON.parse(stored);
            if (parsed.from && !$('#from_date').val()) $('#from_date').val(parsed.from);
            if (parsed.to && !$('#to_date').val()) $('#to_date').val(parsed.to);
        }
    } catch (e) {}

    // Show blank placeholder on load for better UX
    $('#report_output').removeClass('d-none');
    $('#btn_export_gstr3b').addClass('d-none');
    document.getElementById('gstr3b_tabulator').innerHTML = `
        <div class="d-flex flex-column justify-content-center align-items-center py-5 text-muted" style="min-height: 250px;">
            <i class="fa-solid fa-file-invoice fa-3x mb-3" style="opacity: 0.2;"></i>
            <h4 class="mb-1 text-secondary">No Data Loaded</h4>
            <p class="mb-0" style="font-size: 0.85rem;">Select dates and click Apply to fetch the GSTR-3B report.</p>
        </div>
    `;

});
