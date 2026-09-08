/* Driver Expense Register – index.js  (Tabulator – v2) */

var table;
var FILTER_KEY = 'driver_expense_register_filter';

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
        $('#filter_voucher').val('').trigger('change');
        $('#filter_driver').val('').trigger('change');
        $('#filter_vehicle').val('').trigger('change');
        sessionStorage.removeItem(FILTER_KEY);
        table.clearData();
    });

    $('#btn_print_register').on('click', printRegister);

    $('#btn_download_summary').on('click', function () {
        var from = $('#voucher_from_no').val();
        var to = $('#voucher_to_no').val();
        
        if (!from) {
            showToast('error', 'Voucher From No is required');
            $('#voucher_from_no').focus();
            return;
        }
        
        if (!to) {
            showToast('error', 'Voucher To No is required');
            $('#voucher_to_no').focus();
            return;
        }

        if (parseInt(from, 10) > parseInt(to, 10)) {
            showToast('error', 'Voucher From No cannot be greater than Voucher To No.');
            return;
        }
        
        var url = new URL(driverExpenseExportSummaryUrl, window.location.origin);
        url.searchParams.append('from_no', from);
        url.searchParams.append('to_no', to);
        
        window.location.href = url.toString();
        $('#exportSummaryModal').modal('hide');
    });

    $('#voucher_from_no').on('keydown', function(e) {
        if(e.which === 13) {
            e.preventDefault();
            $('#voucher_to_no').focus();
        }
    });

    $('#voucher_to_no').on('keydown', function(e) {
        if(e.which === 13) {
            e.preventDefault();
            $('#btn_download_summary').click();
        }
    });

    $('#exportSummaryModal').on('shown.bs.modal', function () {
        $('#voucher_from_no').focus();
    });

    $('#exportSummaryModal').on('hidden.bs.modal', function () {
        $('#voucher_from_no').val('');
        $('#voucher_to_no').val('');
    });

});

/* ── Tabulator init ─────────────────────────────────────────────────────────── */

function currentParams() {
    var p = {};
    var from    = $('#from_date').val();
    var to      = $('#to_date').val();
    var voucher = $('#filter_voucher').val();
    var driver  = $('#filter_driver').val();
    var vehicle = $('#filter_vehicle').val();

    if (from)    p.from_date      = dmyToYmd(from);
    if (to)      p.to_date        = dmyToYmd(to);
    if (voucher) p.voucher_serial = voucher;
    if (driver)  p.account_id     = driver;
    if (vehicle) p.vehicle_id     = vehicle;
    return p;
}

function initTabulator() {
    table = new Tabulator('#driver_expense_register_table', {
        height: '600px',
        layout: 'fitColumns',
        selectableRows: 1,
        rowClick: function(e, row){
            table.deselectRow();
            row.select();
        },

        pagination: true,
        paginationMode: "remote",
        paginationSize: 50,
        paginationDataSent:     { page: 'page', size: 'size' },
        paginationDataReceived: { last_page: 'last_page', data: 'data' },

        ajaxURL:    driverExpenseListUrl,
        ajaxParams: function () { return currentParams(); },
        ajaxURLGenerator: function (url, _config, params) {
            var p = Object.assign({}, params, currentParams());
            return url + '?' + new URLSearchParams(p).toString();
        },

        placeholder: `<div class="text-center py-5 text-muted">
            <i class="fa-solid fa-table-list fs-1 mb-3 d-block"></i>
            Apply filters and click <strong>Show</strong> to load data.
        </div>`,

        rowFormatter: function (row) {
            // No complex formatting needed now as rows are flat
        },

        columns: [
            {
                title: '#',
                field: 'row_num',
                width: 60,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
                frozen: true,
            },
            {
                title: 'Voucher No.',
                field: 'voucher_serial',
                width: 130,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
            },
            {
                title: 'Date',
                field: 'voucher_date',
                width: 110,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
            },
            {
                title: 'Driver Name (Particular)',
                field: 'driver_name',
                minWidth: 170,
                headerSort: false,
            },
            {
                title: 'Vehicle',
                field: 'vehicle_name',
                minWidth: 130,
                headerSort: false,
            },
            {
                title: 'Action',
                field: '_action',
                width: 140,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
                formatter: function (cell) {
                    var d = cell.getRow().getData();
                    if (!d.id) return '';
                    
                    const editIcon = icons.edit;
                    const printIcon = icons.print;
                    const viewIcon = '<i class="fa-regular fa-eye"></i>';
                    
                    let actions = '<div class="d-flex gap-2 justify-content-center h-100 w-100 align-items-center">';
                    
                    actions += `
                        <span class="erp-btn-icon view" style="background: #e6f2ff; border-color: #b3d9ff; color: #0066cc;" onclick="viewExpense(${d.id})" title="View Details">
                            ${viewIcon}
                        </span>`;

                    actions += `
                        <span class="erp-btn-icon print" style= "background: #f2e6ff; border-color: #d4b3ff;" onclick="printVoucher(${d.id})" title="Print Voucher">
                            ${printIcon}
                        </span>`;
                        
                    if (d.is_locked) {
                        actions += `
                            <span class="erp-btn-icon lock" title="Locked – payment applied" style="opacity:0.5; cursor:not-allowed;">
                                <i class="fa-solid fa-lock"></i>
                            </span>`;
                    } else {
                        actions += `
                            <span class="erp-btn-icon edit" style="background: #fff7e6;border-color: #ffe8b3;" onclick="editExpense(${d.id})" title="Edit">
                                ${editIcon}
                            </span>`;
                    }
                    
                    actions += '</div>';
                    return actions;
                },
            },
            {
                title: 'Created By',
                field: 'created_by',
                minWidth: 120,
                headerSort: false,
            },
            {
                title: 'Updated By',
                field: 'updated_by',
                minWidth: 120,
                headerSort: false,
            },
            {
                title: 'Total Amount',
                field: 'amount',
                width: 130,
                hozAlign: 'right',
                headerHozAlign: 'right',
                headerSort: false,
                frozen: true,
                formatter: function (cell) {
                    return '<strong class="text-primary">' + fmt2(cell.getValue()) + '</strong>';
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
        filter_driver:  $('#filter_driver').val(),
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
        if (saved.filter_driver)  $('#filter_driver').val(saved.filter_driver).trigger('change');
        if (saved.filter_vehicle) $('#filter_vehicle').val(saved.filter_vehicle).trigger('change');

        if (saved.from_date || saved.to_date || saved.filter_voucher || saved.filter_driver || saved.filter_vehicle) {
            table.setData();
        }
    } catch (e) { /* ignore */ }
}

/* ── Edit: individual expense ───────────────────────────────────────────────── */

let viewItemsTable = null;

async function viewExpense(id) {
    var row = table.searchRows("id", "=", id)[0];
    var rowData = row ? row.getData() : {};

    // Open modal immediately and show spinner
    $('#view_item_search').val('');
    $('#view_loading_spinner').removeClass('d-none');
    $('#view_modal_content').addClass('d-none');
    $('#viewExpenseModal').modal('show');

    try {
        const url = driverExpenseDataUrl.replace(':id', id).replace('%3Aid', id);
        const data = await $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json'
        });

        const renderContent = () => {
            // Populate header
            $('#view_voucher_no').text(data.voucher_serial || rowData.voucher_serial || '-');
            $('#view_date').text(data.voucher_date || rowData.voucher_date || '-');
            $('#view_driver').text(rowData.driver_name || '-');
            $('#view_vehicle').text(rowData.vehicle_name || '-');
            $('#view_total_amount').text(fmt2(data.expense_total));

            // Hide spinner and show content
            $('#view_loading_spinner').addClass('d-none');
            $('#view_modal_content').removeClass('d-none');

            // Setup item table
            if (viewItemsTable) {
                viewItemsTable.destroy();
            }
            
            viewItemsTable = new Tabulator("#view_items_table", {
                layout: "fitColumns",
                height: "500px",
                headerSort: false,
                columns: [
                    { title: "Date", field: "date", width: 110 ,headerSort:false},
                    { title: "Expense Acc.", field: "expense_account_name", width: 150 ,headerSort:false},
                    { title: "From", field: "from_name", width: 130 ,headerSort:false},
                    { title: "To", field: "to_name", minWidth: 110 ,headerSort:false},
                    { title: "Product", field: "item_name", minWidth: 120 ,headerSort:false},
                    { title: "DC/LR", field: "dc_lr", width: 100 ,headerSort:false},
                    { title: "Bags", field: "bags", width: 90, hozAlign: "right", bottomCalc: "sum" ,headerSort:false},
                    {
                        title: "Weight",
                        field: "weight",
                        width: 90,
                        hozAlign: "right",
                        bottomCalc: "sum",
                        formatter: cell => fmt3(cell.getValue()),
                        bottomCalcFormatter: cell => fmt3(cell.getValue()),
                        headerSort: false
                    },
                    { title: "Trips", field: "trips", width: 90, hozAlign: "right", bottomCalc: "sum" ,headerSort:false},
                    { title: "Rate", field: "rate", width: 80, hozAlign: "right" ,headerSort:false},
                    { 
                        title: "Amount", 
                        field: "amount", 
                        width: 110, 
                        hozAlign: "right",
                        bottomCalc: "sum",
                        formatter: cell => fmt2(cell.getValue()),
                        bottomCalcFormatter: cell => fmt2(cell.getValue())
                    },
                    { title: "Remark", field: "remark", minWidth: 150 },
                ],
                data: data.items,
            });

            $('#view_item_search').val('');
        };

        if ($('#viewExpenseModal').hasClass('show')) {
            renderContent();
        } else {
            $('#viewExpenseModal').off('shown.bs.modal').on('shown.bs.modal', renderContent);
        }

        $('#view_item_search').off('input').on('input', function() {
            var val = $(this).val();
            if(val && viewItemsTable) {
                viewItemsTable.setFilter([
                    [
                        {field: "expense_account_name", type: "like", value: val},
                        {field: "from_name", type: "like", value: val},
                        {field: "to_name", type: "like", value: val},
                        {field: "item_name", type: "like", value: val}
                    ]
                ]);
            } else if (viewItemsTable) {
                viewItemsTable.clearFilter();
            }
        });

    } catch(err) {
        console.error(err);
        $('#view_loading_spinner').addClass('d-none');
        $('#viewExpenseModal').modal('hide');
        showToast('error', 'Failed to load expense details');
    }
}

function editExpense(id) {
    window.location.href = driverExpenseEditUrl.replace(':id', id);
}

/* ── Print: individual voucher ──────────────────────────────────────────────── */

function printVoucher(id) {
    printReport(driverExpensePrintUrl.replace(':id', id));
}

/* ── Print: register ─────────────────────────────────────────────────────────── */

function printRegister() {
    printReport(driverExpenseRegisterPrintUrl, currentParams());
}

/* ── Helpers ─────────────────────────────────────────────────────────────────── */

function fmt2(val) {
    var n = parseFloat(val) || 0;
    return n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function fmt3(val) {
    var n = parseFloat(val) || 0;
    return n.toLocaleString('en-IN', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
}

function dmyToYmd(dmy) {
    if (!dmy) return '';
    var parts = dmy.split('-');
    return parts.length === 3 ? parts[2] + '-' + parts[1] + '-' + parts[0] : dmy;
}

function bindSelect2() {
    const selectIdArray = [
        '#filter_voucher, #filter_driver, #filter_vehicle'
    ];

    selectIdArray.forEach(function (id) {
        $(id).select2({
            theme: 'bootstrap-5',
            // allowClear: true,
            // placeholder: 'Select ...',
            // width: $(id).attr('style') && $(id).attr('style').includes('width') ? 'element' : '100%'
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
