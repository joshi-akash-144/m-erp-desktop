/* Transport Payment Register – register.js */

var table;

$(function () {

    $('#filter_bank_id').select2({ theme: 'bootstrap-5', allowClear: true, placeholder: 'All Banks' });
    new DateInput('#from_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('#to_date',   FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    initTabulator();

    $('#btn_apply').on('click', function () { table.replaceData(); });

    $('#btn_clear').on('click', function () {
        $('#from_date').val('');
        $('#to_date').val('');
        $('#filter_bank_id').val('').trigger('change');
        table.clearData();
    });

});

/* ── Tabulator ─────────────────────────────────────────────────────────── */

function currentParams() {
    var p = {};
    var from   = $('#from_date').val();
    var to     = $('#to_date').val();
    var bankId = $('#filter_bank_id').val();
    if (from)   p.from_date = dmyToYmd(from);
    if (to)     p.to_date   = dmyToYmd(to);
    if (bankId) p.bank_id   = bankId;
    return p;
}

function initTabulator() {
    table = new Tabulator('#trp_register_table', {
        height: 'calc(100vh - 240px)',
        layout: 'fitColumns',

        progressiveLoad: 'scroll',
        progressiveLoadScrollMargin: 300,
        paginationSize: 50,
        paginationDataSent:     { page: 'page', size: 'size' },
        paginationDataReceived: { last_page: 'last_page', data: 'data' },

        ajaxURL: trpRegisterListUrl,
        ajaxParams: function () { return currentParams(); },
        ajaxURLGenerator: function (url, _cfg, params) {
            var p = Object.assign({}, params, currentParams());
            return url + '?' + new URLSearchParams(p).toString();
        },

        placeholder: '<div class="text-center py-5 text-muted">'
            + '<i class="fa-solid fa-file-invoice-dollar fs-1 mb-3 d-block"></i>'
            + 'Apply filters and click <strong>Show</strong> to load data.</div>',

        columns: [
            {
                title: '#',
                field: 'sr_no',
                width: 60,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
                frozen: true,
            },
            {
                title: 'Payment Date',
                field: 'payment_date',
                width: 130,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
                formatter: function (cell) {
                    return escHtml(cell.getValue() || '-');
                },
            },
            {
                title: 'Bank Name',
                field: 'bank_name',
                minWidth: 200,
                headerSort: false,
                formatter: function (cell) {
                    return escHtml(cell.getValue() || '-');
                },
            },
            {
                title: 'Total Amount (₹)',
                field: 'total_amount',
                width: 160,
                hozAlign: 'right',
                headerHozAlign: 'right',
                headerSort: false,
                formatter: function (cell) {
                    return '<strong class="text-primary">' + fmt(cell.getValue()) + '</strong>';
                },
            },
            {
                title: 'Status',
                field: 'status',
                minWidth: 220,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
                formatter: function (cell) {
                    var d = cell.getRow().getData();
                    var badges = [];
                    
                    if (d.paid_count > 0) {
                        badges.push('<span class="badge bg-success-lt text-success border border-success fw-bold" title="Paid"><i class="fa-solid fa-check-circle me-1"></i>' + d.paid_count + ' Paid</span>');
                    }
                    if (d.pending_count > 0) {
                        badges.push('<span class="badge bg-warning-lt text-warning border border-warning fw-bold" title="Pending"><i class="fa-solid fa-clock me-1"></i>' + d.pending_count + ' Pending</span>');
                    }
                    if (d.adjusted_count > 0) {
                        badges.push('<span class="badge bg-secondary-lt text-secondary border border-secondary fw-bold" title="Adjusted"><i class="fa-solid fa-code-compare me-1"></i>' + d.adjusted_count + ' Adjusted</span>');
                    }
                    if (d.deleted_count > 0) {
                        badges.push('<span class="badge bg-danger-lt text-danger border border-danger fw-bold" title="Voucher Deleted"><i class="fa-solid fa-trash me-1"></i>' + d.deleted_count + ' Deleted</span>');
                    }
                    
                    if (badges.length === 0) return '-';
                    return '<div class="d-flex align-items-center justify-content-center gap-1 flex-wrap">' + badges.join('') + '</div>';
                }
            },
            {
                title: 'Created By',
                field: 'created_by',
                width: 140,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
                formatter: function (cell) {
                    var v = cell.getValue();
                    return v ? '<span class="badge bg-light text-dark border"><i class="fa-regular fa-user me-1"></i>' + escHtml(v) + '</span>' : '-';
                },
            },
            {
                title: 'Action',
                field: 'id',
                width: 120,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
                frozen: true,
                formatter: function (cell) {
                    var rid = cell.getValue();
                    const printIcon = icons?.print || '<i class="fa-solid fa-print"></i>';
                    
                    let actions = '<div class="d-flex gap-2 justify-content-center h-100 w-100 align-items-center">';
                    
                    // View Icon
                    actions += `
                        <span class="erp-btn-icon erp-btn-icon-sm view" style="background: #e6f2ff; border-color: #b3d9ff; color: #0066cc; cursor: pointer;" onclick="showDetail(${rid})" title="View Details">
                            <i class="fa-regular fa-eye"></i>
                        </span>`;
                        
                    // Print Icon
                    actions += `
                        <span class="erp-btn-icon print" style="background: #f2e6ff; border-color: #d4b3ff; cursor: pointer;" onclick="printRelease(${rid})" title="Print Release">
                            ${printIcon}
                        </span>`;
                        
                    actions += '</div>';
                    return actions;
                },
            },
        ],
    });
}

/* ── Detail Modal ──────────────────────────────────────────────────────── */

let detailVouchersTable = null;

async function showDetail(releaseId) {
    // Reset modal UI
    $('#detail_search').val('');
    $('#view_loading_spinner').removeClass('d-none');
    $('#view_modal_content').addClass('d-none');
    $('#d_payment_date, #d_bank_name, #d_total_amount').text('—');
    $('#detailModal').modal('show');

    try {
        const url = trpRegisterDetailUrl.replace('__ID__', releaseId);
        const res = await $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json'
        });

        if (!res.success) throw new Error('Failed to load details');
        const data = res.data;

        const renderContent = () => {
            // Populate Header
            $('#d_payment_date').text(data.payment_date || '—');
            $('#d_bank_name').text(data.bank_name || '—');
            $('#d_total_amount').text('₹ ' + fmt(data.total_amount));

            // Hide spinner, show content
            $('#view_loading_spinner').addClass('d-none');
            $('#view_modal_content').removeClass('d-none');

            // Setup Tabulator for Vouchers
            if (detailVouchersTable) {
                detailVouchersTable.destroy();
            }

            detailVouchersTable = new Tabulator("#detail_vouchers_table", {
                layout: "fitColumns",
                height: "400px",
                renderVertical: "basic",
                headerSort: false,
                data: data.vouchers || [],
                columns: [
                    { 
                        title: "Voucher No", 
                        field: "voucher_serial", 
                        width: 140, 
                        hozAlign: "center",
                        formatter: function(cell) {
                            var d = cell.getRow().getData();
                            if (d.is_deleted) {
                                return '<span class="badge bg-danger-lt text-danger border border-danger fw-bold"><i class="fa-solid fa-trash me-1"></i>Deleted</span>';
                            }
                            return '<span class="badge bg-warning-lt text-warning border border-warning fw-bold">' + escHtml(cell.getValue()) + '</span>';
                        }
                    },
                    { 
                        title: "Party Name", 
                        field: "party_name", 
                        minWidth: 200,
                        formatter: function(cell) {
                            var d = cell.getRow().getData();
                            var name = escHtml(d.party_name || '-');
                            if (d.party_city) {
                                name += ' <span style="font-size:0.8rem;opacity:0.75;">(' + escHtml(d.party_city) + ')</span>';
                            }
                            if (d.is_deleted) {
                                return '<span style="text-decoration:line-through;opacity:0.6;" class="text-danger">' + name + '</span>';
                            }
                            return name;
                        }
                    },
                    { 
                        title: "Settled References", 
                        field: "references", 
                        minWidth: 300,
                        formatter: function(cell) {
                            var refs = cell.getValue() || [];
                            if (refs.length === 0) return '<span class="text-muted small">No references</span>';
                            
                            var html = '<div class="d-flex flex-column gap-1 py-1">';
                            refs.forEach(function(r) {
                                html += '<div class="d-flex justify-content-between border-bottom pb-1 mb-1" style="font-size:0.85rem;">' +
                                        '<span><strong class="text-dark">' + escHtml(r.reference_number) + '</strong> <span class="text-muted ms-1">(' + escHtml(r.reference_date) + ')</span></span>' +
                                        '<strong class="text-success">₹ ' + fmt(r.amount) + '</strong>' +
                                        '</div>';
                            });
                            html += '</div>';
                            return html;
                        }
                    },
                    {
                        title: "Cheque No.",
                        field: "cheque_number",
                        width: 120,
                        hozAlign: "center",
                        formatter: function(cell) {
                            var v = cell.getValue();
                            return v ? '<span class="text-secondary fw-bold">' + escHtml(v) + '</span>' : '-';
                        }
                    },
                    { 
                        title: "Voucher Amount", 
                        field: "paid_amount", 
                        width: 150, 
                        hozAlign: "right",
                        bottomCalc: "sum",
                        bottomCalcFormatter: function(cell) { return '₹ ' + fmt(cell.getValue()); },
                        formatter: function(cell) { return '<strong class="text-primary">₹ ' + fmt(cell.getValue()) + '</strong>'; }
                    },
                    {
                        title: "Action",
                        field: "voucher_id",
                        width: 80,
                        hozAlign: "center",
                        headerSort: false,
                        formatter: function(cell) {
                            var d = cell.getRow().getData();
                            if (d.is_deleted || !cell.getValue()) return '-';
                            var vid = cell.getValue();
                            const printIcon = icons.print;
                            return `<span class="erp-btn-icon print" style="background: #f2e6ff; border-color: #d4b3ff; cursor: pointer;" onclick="printVoucher(${vid})" title="Print Voucher">
                                ${printIcon}
                            </span>`;
                        }
                    }
                ]
            });
            
            $('#detail_search').val('');
        };

        if ($('#detailModal').hasClass('show')) {
            renderContent();
        } else {
            $('#detailModal').off('shown.bs.modal').on('shown.bs.modal', renderContent);
        }

        // Search within modal table
        $('#detail_search').off('input').on('input', function() {
            var val = $(this).val().toLowerCase();
            if (val && detailVouchersTable) {
                detailVouchersTable.setFilter(function(data) {
                    if (data.voucher_serial && String(data.voucher_serial).toLowerCase().includes(val)) return true;
                    if (data.party_name && String(data.party_name).toLowerCase().includes(val)) return true;
                    if (data.party_city && String(data.party_city).toLowerCase().includes(val)) return true;
                    if (data.cheque_number && String(data.cheque_number).toLowerCase().includes(val)) return true;
                    if (data.paid_amount && String(data.paid_amount).toLowerCase().includes(val)) return true;
                    
                    if (data.references && Array.isArray(data.references)) {
                        for (var i = 0; i < data.references.length; i++) {
                            var ref = data.references[i];
                            if (ref.reference_number && String(ref.reference_number).toLowerCase().includes(val)) return true;
                            if (ref.amount && String(ref.amount).toLowerCase().includes(val)) return true;
                        }
                    }
                    return false;
                });
            } else if (detailVouchersTable) {
                detailVouchersTable.clearFilter();
            }
        });

    } catch (err) {
        console.error(err);
        $('#view_loading_spinner').addClass('d-none');
        $('#detailModal').modal('hide');
        showToast('error', 'Failed to load details.');
    }
}

/* ── Print ─────────────────────────────────────────────────────────────── */

function printRelease(releaseId) {
    var url = trpRegisterPrintUrl.replace('__ID__', releaseId);
    printReport(url);
}

function printVoucher(voucherId) {
    var url = trpVoucherPrintUrl.replace('__ID__', voucherId);
    printReport(url);
}

/* ── Helpers ───────────────────────────────────────────────────────────── */

function fmt(val) {
    var n = parseFloat(String(val).replace(/,/g, '')) || 0;
    return n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function dmyToYmd(dmy) {
    if (!dmy) return '';
    var p = dmy.split('-');
    return p.length === 3 ? p[2] + '-' + p[1] + '-' + p[0] : dmy;
}

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
