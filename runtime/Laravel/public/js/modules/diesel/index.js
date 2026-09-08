/* Diesel Register – index.js  (Tabulator – v2) */

var table;
var FILTER_KEY = 'diesel_register_filter';
var currentPermissions = { update: false, print: false, view: false };

$(function () {

    bindSelect2();
    new DateInput('#from_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('#to_date',   FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    initTabulator();
    restoreFilters();

    $('#btn_apply').on('click', applyFilters);

    $('#btn_clear').on('click', function () {
        $('#from_date').val('');
        $('#to_date').val('');
        $('#filter_account').val('').trigger('change');
        sessionStorage.removeItem(FILTER_KEY);
        table.setData();
    });

    $('#btn_print_register').on('click', printRegister);
});



/* ── Tabulator init ─────────────────────────────────────────────────────────── */

function currentParams() {
    var p = {};
    var from    = $('#from_date').val();
    var to      = $('#to_date').val();
    var voucher = $('#filter_voucher').val();
    var driver  = $('#filter_account').val();
    var vehicle = $('#filter_vehicle').val();

    if (from)    p.from_date      = dmyToYmd(from);
    if (to)      p.to_date        = dmyToYmd(to);
    if (voucher) p.voucher_serial = voucher;
    if (driver)  p.account_id     = driver;
    if (vehicle) p.vehicle_id     = vehicle;
    return p;
}

function initTabulator() {
    table = new Tabulator('#diesel_register_table', {
        height: '550px',
        layout: 'fitColumns',

        pagination: true,
        paginationMode: "remote",
        paginationSize: 50,
        paginationDataSent:     { page: 'page', size: 'size' },
        paginationDataReceived: { last_page: 'last_page', data: 'data' },

        ajaxURL:    dieselListUrl,
        ajaxParams: function () { return currentParams(); },
        ajaxURLGenerator: function (url, _config, params) {
            var p = Object.assign({}, params, currentParams());
            return url + '?' + new URLSearchParams(p).toString();
        },
        ajaxResponse: function (url, params, response) {
            if (response && response.permissions) {
                currentPermissions = response.permissions;
            }
            return response;
        },

        placeholder: `<div class="text-center py-5 text-muted">
            <i class="fa-solid fa-table-list fs-1 mb-3 d-block"></i>
            Apply filters and click <strong>Show</strong> to load data.
        </div>`,

        rowFormatter: function (row) {
            var type = row.getData().row_type;
            var el   = row.getElement();
            el.classList.remove('row-parent', 'row-item', 'row-total');
            if (type) el.classList.add('row-' + type);
        },

        columns: [
            {
                title: '#',
                field: 'row_num',
                width: 80,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
                frozen: true,
                formatter: function (cell) {
                    var d = cell.getRow().getData();
                    if (d.row_type === 'total') return '<strong>Total</strong>';
                    return d.row_num != null ? d.row_num : '';
                },
            },
            // {
            //     title: 'Voucher No.',
            //     field: 'voucher_serial',
            //     width: 150,
            //     hozAlign: 'center',
            //     headerHozAlign: 'center',
            //     headerSort: false,
            //     formatter: function (cell) {
            //         var d = cell.getRow().getData();
            //         return d.row_type === 'parent' ? (d.voucher_serial || '-') : '';
            //     },
            // },
            {
                title: 'Date',
                field: 'voucher_date',
                width: 100,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
                formatter: function (cell) {
                    var d = cell.getRow().getData();
                    return d.row_type === 'parent' ? (d.voucher_date || '-') : '';
                },
            },
            {
                title: 'Name',
                field: 'Account_name',
                width: 300,
                headerSort: false,
                formatter: function (cell) {
                    var d = cell.getRow().getData();
                    return d.account_name || '-';
                },
            },
            {
                title: 'Average Rate',
                field: 'rate',
                width: 120,
                hozAlign:'right',
                headerHozAlign:'right',
                headerSort: false,
                formatter: function (cell) {
                    var d = cell.getRow().getData();
                    return (d.total_amount / d.total_diesel).toFixed(2);
                },
            },
            {
                title: 'Total Diesel',
                field: 'total_diesel',
                width: 150,
                hozAlign:'right',
                headerHozAlign:'right',
                headerSort: false,
                formatter: function (cell) {
                    var d = cell.getRow().getData();
                    return d.total_diesel.toFixed(2) || '-';
                },
            },
            {
                title: 'Total Amount',
                field: 'total_amount',
                width: 150,
                hozAlign:'right',
                headerHozAlign:'right',
                headerSort: false,
                formatter: function (cell) {
                    var d = cell.getRow().getData();
                    return d.total_amount || '-';
                },
            },
            {
                title: 'Total Vehicle',
                field: 'total_vehicle',
                width: 150,
                hozAlign:'right',
                headerHozAlign:'right',
                headerSort: false,
                formatter: function (cell) {
                    var d = cell.getRow().getData();
                    if (!d.total_vehicle) return '-';
                    return parseInt(d.total_vehicle, 10);
                },
            },

            // {
            //     title: 'Vehicle',
            //     field: 'vehicle_name',
            //     width: 115,
            //     hozAlign: 'center',
            //     headerHozAlign: 'center',
            //     headerSort: false,
            //     formatter: function (cell) {
            //         var d = cell.getRow().getData();
            //         return d.vehicle_name || '-';
            //     },
            // },
            // {
            //     title: 'Bill No.',
            //     field: 'bill_no',
            //     width: 100,
            //     hozAlign: 'center',
            //     headerHozAlign: 'center',
            //     headerSort: false,
            //     formatter: function (cell) {
            //         var d = cell.getRow().getData();
            //         if (d.row_type === 'total') return '';
            //         return d.bill_no || '-';
            //     },
            // },
            // {
            //     title: 'Challan No.',
            //     field: 'challan_number',
            //     width: 100,
            //     hozAlign: 'center',
            //     headerHozAlign: 'center',
            //     headerSort: false,
            //     formatter: function (cell) {
            //         var d = cell.getRow().getData();
            //         if (d.row_type === 'total') return '';
            //         return d.challan_number || '-';
            //     },
            // },

            // {
            //     title: 'Remark',
            //     field: 'remark',
            //     width: 300,
            //     headerSort: false,
            //     formatter: function (cell) {
            //         var d = cell.getRow().getData();
            //         if (d.row_type === 'total') return '';
            //         return d.remark || '-';
            //     },
            // },
            // {
            //     title: 'Diesel Ltr',
            //     field: 'diesel',
            //     width: 110,
            //     hozAlign: 'right',
            //     headerHozAlign: 'right',
            //     headerSort: false,
            //     formatter: function (cell) {
            //         var d = cell.getRow().getData();
            //         var v = fmt2(cell.getValue());
            //         return d.row_type === 'total'
            //             ? '<strong class="text-primary">' + v + '</strong>'
            //             : v;
            //     },
            // },
            // {
            //     title: 'Amount',
            //     field: 'amount',
            //     width: 140,
            //     hozAlign: 'right',
            //     headerHozAlign: 'right',
            //     headerSort: false,
            //     frozen: true,
            //     formatter: function (cell) {
            //         var d = cell.getRow().getData();
            //         var v = fmt2(cell.getValue());
            //         return d.row_type === 'total'
            //             ? '<strong class="text-primary">' + v + '</strong>'
            //             : v;
            //     },
            // },
            {
                title: 'Status',
                field: 'is_locked',
                width: 260,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
                frozen: true,
                formatter: function (cell) {
                    var d = cell.getRow().getData();
                    if (d.row_type !== 'parent') return '';
                    
                    var badges = [];
                    
                    if (d.clear_count > 0) {
                        badges.push('<span class="badge bg-success-lt text-success border border-success fw-bold" title="Clear"><i class="fa-solid fa-check-circle me-1"></i>' + d.clear_count + ' Clear</span>');
                    }
                    if (d.pending_count > 0) {
                        badges.push('<span class="badge bg-warning-lt text-warning border border-warning fw-bold" title="Pending"><i class="fa-solid fa-clock me-1"></i>' + d.pending_count + ' Pending</span>');
                    }
                    
                    if (badges.length === 0) return '-';
                    return '<div class="d-flex align-items-center justify-content-center gap-1 flex-wrap">' + badges.join('') + '</div>';
                }
            },
    {
      title: "Created By",
      field: "created_by",
      width: 150,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      resizable: true,
      formatter: (cell) => {
        const creatorName = cell.getRow().getData().creator?.name || null;
        return creatorName
          ? `<span class="badge bg-danger-lt ">${creatorName}</span>`
          : `<span class="badge bg-cyan-lt">--</span>`;
      },
    },

    // =======================
    // Updated By
    // =======================
    {
      title: "Updated By",
      field: "updated_by",
      width: 150,
      hozAlign: "center",
      headerHozAlign: "center",
      headerSort: false,
      resizable: true,
      formatter: (cell) => {
        const updaterName = cell.getRow().getData().updater?.name || null;
        return updaterName
          ? `<span class="badge bg-danger-lt ">${updaterName}</span>`
          : `<span class="badge bg-cyan-lt">--</span>`;
      },
    },

            {
                title: 'Action',
                field: '_action',
                width: 150,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
                formatter: function (cell) {
                    var d = cell.getRow().getData();
                    if (d.row_type !== 'parent' || !d.id) return '';
                    const canEdit = currentPermissions.update;
                    const canPrint = currentPermissions.print;
                    const canView = currentPermissions.view;
                    const canDelete = currentPermissions.delete;

                    const printIcon = icons.print;
                    const viewIcon = icons.view;
                    const deleteIcon = icons.delete;
                    
                    let actions = '<div class="d-flex gap-2 justify-content-center h-100 w-100 align-items-center">';
                    if(canView){

                    actions += `
                       <span class="erp-btn-icon view" style="background: #e6f0ff; border-color: #b3d4ff;" onclick="viewVoucher(${d.id})" title="View Voucher">
                            ${viewIcon}
                        </span>`;
                    }

                    const lockIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler-lock text-muted">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M5 11m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z" />
                    <path d="M12 16m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                    <path d="M8 11v-4a4 4 0 0 1 8 0v4" />
                    </svg>`;

                    if (canEdit && d.is_locked == 0) {
                        actions += `
                            <a href="/transports/diesel/edit/${d.id}" class="erp-btn-icon edit" style="background: #fff7e6; border-color: #ffe8b3;" title="Edit Voucher">
                                ${icons.edit}
                            </a>`;
                    } else {               
                        actions += `<span class="erp-btn-icon text-dark" style="cursor: not-allowed !important;" title="Locked">${lockIcon}</span>`;
                    }

                    if (canPrint) {
                        actions += `
                        <span class="erp-btn-icon print" style="background: #f2e6ff; border-color: #d4b3ff;" onclick="printVoucher(${d.id})" title="Print Voucher">
                            ${printIcon}
                        </span>`;
                    }

                    if (canDelete) {
                        if (d.clear_count > 0) {
                            actions += `<span class="erp-btn-icon text-dark" style="cursor: not-allowed !important;" title="Locked">${lockIcon}</span>`;
                        } else {
                            actions += `
                            <span class="erp-btn-icon delete" style="background: #ffe6e6; border-color: #ffcccc;" onclick="deleteVoucher(${d.id})" title="Delete Voucher">
                                ${deleteIcon}
                            </span>`;
                        }
                    }
                    actions += '</div>';
                    return actions;
                },
            },
        ],
    });
}

/* ── Filters ─────────────────────────────────────────────────────────────────── */

function applyFilters() {
    saveFilters();
    table.setData();
}

function saveFilters() {
    sessionStorage.setItem(FILTER_KEY, JSON.stringify({
        from_date:      $('#from_date').val(),
        to_date:        $('#to_date').val(),
        filter_voucher: $('#filter_voucher').val(),
        filter_account:  $('#filter_account').val(),
        filter_vehicle: $('#filter_vehicle').val(),
    }));
}

function restoreFilters() {
    try {
        var saved = JSON.parse(sessionStorage.getItem(FILTER_KEY) || 'null');
        if (!saved) return;
        if (saved.from_date)      $('#from_date').val(saved.from_date);
        if (saved.to_date)        $('#to_date').val(saved.to_date);
        if (saved.filter_voucher) $('#filter_voucher').val(saved.filter_voucher).trigger('change');
        if (saved.filter_account)  $('#filter_account').val(saved.filter_account).trigger('change');
        if (saved.filter_vehicle) $('#filter_vehicle').val(saved.filter_vehicle).trigger('change');

        if (saved.from_date || saved.to_date || saved.filter_voucher || saved.filter_account || saved.filter_vehicle) {
            table.setData();
        }
    } catch (e) { /* ignore */ }
}

/* ── Detail Modal ────────────────────────────────────────────────────────────── */

var detailTable;

function viewVoucher(id) {
    $('#detailModal').modal('show');
    $('#view_modal_content').addClass('d-none');
    $('#view_loading_spinner').removeClass('d-none');
    
    $.ajax({
        url: dieselShowUrl.replace(':id', id),
        type: 'GET',
        success: function(response) {
            if (response.success && response.data) {
                var d = response.data;
                
                var detailsData = d.details.map(function(item) {
                    return {
                        voucher_serial: item.voucher_serial || '-',
                        account_name: d.account ? d.account.name : '-',
                        driver_name: (item.driver && item.driver.account) ? item.driver.account.name : '-',
                        bill_no: item.reference_number || '-',
                        challan_number: item.challan_number || '-',
                        remark: item.remark || '-',
                        // rate: d.diesel_rate,
                        rate: item.rate > 0 ? item.rate : (parseFloat(d.diesel_rate) || 0),
                        vehicle: item.vehicle ? item.vehicle.name : '-',
                        diesel: item.diesel,
                        amount: item.amount,
                        status: item.is_closed ? 'Clear' : 'Pending',
                        last_date: item.last_date,
                        today_date: item.today_date,
                        old_km: item.old_km,
                        new_km: item.new_km,
                        diff: item.diff,
                        average: item.average,
                    };
                });
                
                var renderContent = function() {
                    if (!detailTable) {
                        detailTable = new Tabulator('#diesel_detail_table', {
                        height: '600px',
                        layout: 'fitColumns',
                        data: detailsData,
                        columns: [
                            {
                                title: 'Voucher No',
                                field: 'voucher_serial',
                                width: 120,
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                                formatter: function (cell) {
                                    return cell.getValue();
                                },
                            },
                            {
                                title: 'Driver',
                                field: 'driver_name',
                                width: 300,
                                headerSort: false,
                                formatter: function (cell) {
                                    var d = cell.getRow().getData();
                                    return d.driver_name || '-';
                                },
                            },
                            {
                                title: 'Bill No.',
                                field: 'bill_no',
                                width: 100,
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                                formatter: function (cell) {
                                    var d = cell.getRow().getData();
                                    if (d.row_type === 'total') return '';
                                    return d.bill_no || '-';
                                },
                            },
                            {
                                title: 'Challan No.',
                                field: 'challan_number',
                                width: 100,
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                                formatter: function (cell) {
                                    var d = cell.getRow().getData();
                                    if (d.row_type === 'total') return '';
                                    return d.challan_number || '-';
                                },
                            },
                            {
                                title: 'Last Date',
                                field: 'last_date',
                                width: 100,
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                                formatter: function (cell) {
                                    var val = cell.getValue();
                                    if (!val) return '-';
                                    var p = val.split('-');
                                    return (p.length === 3 && p[0].length === 4) ? p[2] + '-' + p[1] + '-' + p[0] : val;
                                },
                            },
                            {
                                title: 'Today Date',
                                field: 'today_date',
                                width: 100,
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort: false,
                                formatter: function (cell) {
                                    var val = cell.getValue();
                                    if (!val) return '-';
                                    var p = val.split('-');
                                    return (p.length === 3 && p[0].length === 4) ? p[2] + '-' + p[1] + '-' + p[0] : val;
                                },
                            },
                            {
                                title: 'Vehicle', 
                                field: 'vehicle', 
                                width: 120 ,
                                headerSort:false,
                                headerHozAlign: 'left',
                                hozAlign: 'left',
                                bottomCalc: function() { return "Total:"; },
                                bottomCalcFormatter: function(cell) {
                                    return '<strong class="text-primary">' + cell.getValue() + '</strong>';
                                }
                            },
                            {
                                title: 'Rate', 
                                field: 'rate', 
                                width: 150,
                                headerHozAlign: 'right',
                                hozAlign: 'right',
                                formatter: 'money',
                                formatterParams: { symbol: '', precision: 2 },
                                headerSort:false,
                            },
                            {
                                title: 'Diesel (Ltr)',
                                 field: 'diesel', 
                                 width: 100, 
                                 headerHozAlign: 'right',
                                 hozAlign: 'right', 
                                 formatter: 'money', 
                                 formatterParams: { symbol: '', precision: 2 },
                                 headerSort:false,
                                 bottomCalc: "sum",
                                 bottomCalcFormatter: "money",
                                 bottomCalcFormatterParams: { symbol: '', precision: 2 }
                            },
                            { 
                                title: 'Amount', 
                                field: 'amount', 
                                width: 120, 
                                headerHozAlign: 'right', 
                                hozAlign: 'right', 
                                formatter: 'money', 
                                formatterParams: { symbol: '', precision: 2 } ,
                                 headerSort:false,
                                 bottomCalc: "sum",
                                 bottomCalcFormatter: "money",
                                 bottomCalcFormatterParams: { symbol: '', precision: 2 }
                            },
                            { 
                                title: 'Old KM', 
                                field: 'old_km', 
                                width: 120, 
                                headerHozAlign: 'right', 
                                hozAlign: 'right', 
                                formatter: 'money', 
                                formatterParams: { symbol: '', precision: 2 } ,
                                 headerSort:false,
                            },
                            { 
                                title: 'New KM', 
                                field: 'new_km', 
                                width: 120, 
                                headerHozAlign: 'right', 
                                hozAlign: 'right', 
                                formatter: 'money', 
                                formatterParams: { symbol: '', precision: 2 } ,
                                 headerSort:false,
                            },
                            { 
                                title: 'Difference', 
                                field: 'diff', 
                                width: 120, 
                                headerHozAlign: 'right', 
                                hozAlign: 'right', 
                                formatter: 'money', 
                                formatterParams: { symbol: '', precision: 2 } ,
                                 headerSort:false,
                            },                            { 
                                title: 'Average', 
                                field: 'average', 
                                width: 120, 
                                headerHozAlign: 'right', 
                                hozAlign: 'right', 
                                formatter: 'money', 
                                formatterParams: { symbol: '', precision: 2 } ,
                                 headerSort:false,
                            },
                            {
                                title: 'Status',
                                field: 'status',
                                width: 130,
                                hozAlign: 'center',
                                headerHozAlign: 'center',
                                headerSort:false,
                                formatter: function(cell) {
                                    var val = cell.getValue();
                                    if (val === 'Clear') {
                                        return '<span class="badge bg-success-lt text-success border border-success fw-bold"><i class="fa-solid fa-check-circle me-1"></i>Clear</span>';
                                    } else {
                                        return '<span class="badge bg-warning-lt text-warning border border-warning fw-bold"><i class="fa-solid fa-clock me-1"></i>Pending</span>';
                                    }
                                }
                            },
                            {
                                title: 'Remark',
                                field: 'remark',
                                minWidth: 150,
                                headerSort: false,
                                formatter: function (cell) {
                                    var d = cell.getRow().getData();
                                    if (d.row_type === 'total') return '';
                                    return d.remark || '-';
                                },
                            },
                        ]
                    });
                    
                    $('#detail_search').off('input').on('input', function() {
                        var val = $(this).val().toLowerCase();
                        if (val) {
                            detailTable.setFilter(function(data) {
                                if (data.vehicle && String(data.vehicle).toLowerCase().includes(val)) return true;
                                if (data.voucher_serial && String(data.voucher_serial).toLowerCase().includes(val)) return true;
                                if (data.driver_name && String(data.driver_name).toLowerCase().includes(val)) return true;
                                if (data.bill_no && String(data.bill_no).toLowerCase().includes(val)) return true;
                                if (data.challan_number && String(data.challan_number).toLowerCase().includes(val)) return true;
                                return false;
                            });
                        } else {
                            detailTable.clearFilter();
                        }
                    });
                } else {
                    detailTable.setData(detailsData).then(function() {
                        detailTable.redraw(true);
                    });
                    $('#detail_search').val('');
                    detailTable.clearFilter();
                }
                
                $('#view_loading_spinner').addClass('d-none');
                $('#view_modal_content').removeClass('d-none');
            };

            if ($('#detailModal').hasClass('show')) {
                renderContent();
                setTimeout(function() { $('#detail_search').focus(); }, 10);
            } else {
                $('#detailModal').off('shown.bs.modal').on('shown.bs.modal', function() {
                    renderContent();
                    $('#detail_search').focus();
                });
            }
            } else {
                Swal.fire('Error', 'Unable to fetch details', 'error');
                $('#detailModal').modal('hide');
            }
        },
        error: function(xhr) {
            $('#view_loading_spinner').addClass('d-none');
            $('#detailModal').modal('hide');
            Swal.fire('Error', xhr.responseJSON?.message || 'Failed to fetch details.', 'error');
        }
    });
}

/* ── Print: individual voucher ──────────────────────────────────────────────── */

function printVoucher(id) {
    printReport(dieselPrintUrl.replace(':id', id));
}

/* ── Print: register ─────────────────────────────────────────────────────────── */

function printRegister() {
    printReport(dieselRegisterPrintUrl, currentParams());
}

/* ── Delete: voucher ──────────────────────────────────────────────────────────── */

function deleteVoucher(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: dieselDeleteUrl.replace(':id', id),
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.success) {
                        Swal.fire('Deleted!', response.message, 'success');
                        fetchRegister(currentPage);
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function (xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Failed to delete diesel voucher.', 'error');
                }
            });
        }
    });
}

/* ── Helpers ─────────────────────────────────────────────────────────────────── */

function fmt2(val) {
    var n = parseFloat(val) || 0;
    return n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function dmyToYmd(dmy) {
    if (!dmy) return '';
    var parts = dmy.split('-');
    return parts.length === 3 ? parts[2] + '-' + parts[1] + '-' + parts[0] : dmy;
}

function bindSelect2() {
    const selectIdArray = [
        '#filter_voucher, #filter_account, #filter_vehicle'
    ];

    selectIdArray.forEach(function (id) {
        $(id).select2({
            theme: 'bootstrap-5',
        });
    });

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
