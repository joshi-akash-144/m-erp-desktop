/* Multi Expense Voucher Register – index.js (Tabulator) */

var table;
var FILTER_KEY = 'multi_expense_register_filter';
var _permissions = { update: false, print: false };

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
        $('#filter_account').val('').trigger('change');
        $('#filter_expense_account').val('').trigger('change');
        sessionStorage.removeItem(FILTER_KEY);
        table.clearData();
    });

    $('#btn_print_register').on('click', printRegister);

});

/* ── Tabulator init ─────────────────────────────────────────────────────────── */

function currentParams() {
    var p = {};
    var from            = $('#from_date').val();
    var to               = $('#to_date').val();
    var voucher          = $('#filter_voucher').val();
    var account          = $('#filter_account').val();
    var expenseAccount   = $('#filter_expense_account').val();

    if (from)          p.from_date          = dmyToYmd(from);
    if (to)            p.to_date            = dmyToYmd(to);
    if (voucher)       p.voucher_serial     = voucher;
    if (account)       p.account_id         = account;
    if (expenseAccount) p.expense_account_id = expenseAccount;
    return p;
}

function initTabulator() {
    table = new Tabulator('#multi_expense_register_table', {
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
        paginationSizeSelector: [10, 30, 50, 100, 200, 500],
        paginationDataSent:     { page: 'page', size: 'size' },
        paginationDataReceived: { last_page: 'last_page', data: 'data' },

        ajaxURL:    multiExpenseListUrl,
        ajaxParams: function () { return currentParams(); },
        ajaxURLGenerator: function (url, _config, params) {
            var p = Object.assign({}, params, currentParams());
            return url + '?' + new URLSearchParams(p).toString();
        },
        ajaxResponse: function (url, params, response) {
            _permissions = response.permissions || _permissions;
            return response;
        },

        placeholder: `<div class="text-center py-5 text-muted">
            <i class="fa-solid fa-table-list fs-1 mb-3 d-block"></i>
            Apply filters and click <strong>Apply</strong> to load data.
        </div>`,

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
            // {
            //     title: 'Voucher No.',
            //     field: 'voucher_serial',
            //     width: 100,
            //     hozAlign: 'center',
            //     headerHozAlign: 'center',
            //     headerSort: false,
            //     formatter: cell => cell.getValue() || '-',
            // },
            {
                title: 'Vch. Date',
                field: 'voucher_date',
                width: 110,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
                formatter: cell => cell.getValue() || '-',
            },
            {
                title: 'Party (Cr)',
                field: 'account_name',
                minWidth: 160,
                headerSort: false,
                formatter: cell => cell.getValue() || '-',
            },
            {
                title: 'Expense Type (Dr)',
                field: 'expense_account_name',
                minWidth: 160,
                headerSort: false,
                formatter: cell => cell.getValue() || '-',
            },
            {
                title: 'Narration',
                field: 'narration',
                minWidth: 150,
                headerSort: false,
                formatter: cell => cell.getValue() || '-',
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

                    const printIcon = icons.print;
                    const editIcon  = icons.edit;

                    let actions = '<div class="d-flex gap-2 justify-content-center h-100 w-100 align-items-center">';

                    actions += `
                        <span class="erp-btn-icon erp-btn-icon-sm view" style="background: #e6f2ff; border-color: #b3d9ff; color: #0066cc;" onclick="viewVoucher(${d.id})" title="View Details">
                            <i class="fa-regular fa-eye"></i>
                        </span>`;

                    if (_permissions.print) {
                        actions += `
                        <span class="erp-btn-icon print" style="background: #f2e6ff; border-color: #d4b3ff;" onclick="printVoucher(${d.id})" title="Print Voucher">
                            ${printIcon}
                        </span>`;
                    }

                    if (_permissions.update) {
                        if (d.is_locked) {
                            actions += `
                            <span class="erp-btn-icon edit" style="background: #f8d7da; border-color: #f5c6cb; cursor: not-allowed; opacity: 0.7;" title="Locked (Reference is paid)">
                                <i class="fa-solid fa-lock text-danger"></i>
                            </span>`;
                        } else {
                            actions += `
                            <span class="erp-btn-icon edit" style="background: #fff7e6; border-color: #ffe8b3;" onclick="editVoucher(${d.id})" title="Edit">
                                ${editIcon}
                            </span>`;
                        }
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
                formatter: cell => cell.getValue() || '-',
            },
            {
                title: 'Updated By',
                field: 'updated_by',
                minWidth: 120,
                headerSort: false,
                formatter: cell => cell.getValue() || '-',
            },
            {
                title: 'Amount',
                field: 'amount',
                width: 180,
                hozAlign: 'right',
                headerHozAlign: 'right',
                headerSort: false,
                frozen: true,
                formatter: function (cell) {
                    return fmt2(cell.getValue());
                },
            },
        ],
    });
}

/* ── Print: individual voucher ──────────────────────────────────────────────── */

function printVoucher(id) {
    printReport(multiExpensePrintUrl.replace(':id', id));
}

/* ── Edit: individual voucher ───────────────────────────────────────────────── */

function editVoucher(id) {
    window.location.href = multiExpenseEditUrl.replace(':id', id);
}

/* ── View: individual voucher details ───────────────────────────────────────── */

let viewItemsTable = null;

async function viewVoucher(id) {
    var row = table.searchRows("id", "=", id)[0];
    var rowData = row ? row.getData() : {};

    // Open modal immediately and show spinner
    $('#view_item_search').val('');
    $('#view_loading_spinner').removeClass('d-none');
    $('#view_modal_content').addClass('d-none');
    $('#view_voucher_no, #view_date, #view_party_cr, #view_expense_dr, #view_total_amount').text('-');
    $('#viewExpenseModal').modal('show');

    try {
        const url = multiExpenseDataUrl.replace(':id', id).replace('%3Aid', id);
        const data = await $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json'
        });

        const renderContent = () => {
            // Populate header
            $('#view_voucher_no').text(data.voucher_serial || rowData.voucher_serial || '-');
            $('#view_date').text(data.voucher_date || rowData.voucher_date || '-');
            $('#view_party_cr').text(rowData.account_name || '-');
            $('#view_expense_dr').text(rowData.expense_account_name || '-');
            $('#view_total_amount').text(fmt2(data.total_amount || data.expense_total));

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
                initialSort: [
                    { column: "vehicle_name", dir: "asc" }
                ],
                columns: [
                    { title: "Bill Date", field: "bill_date", width: 120, headerSort: false },
                    {
                        title: 'Voucher No.',
                        field: 'voucher_serial',
                        width: 100,
                        hozAlign: 'center',
                        headerHozAlign: 'center',
                        headerSort: false,
                        formatter: cell => cell.getValue() || '-',
                    },
                    { title: "Bill No", field: "bill_no", width: 120, headerSort: false },
                    { title: "Vehicle", field: "vehicle_name", minWidth: 150, headerSort: false },
                    { title: "Challan No.", field: "challan_number", width: 120, headerSort: false },
                    { title: "Amount", field: "amount", width: 130, hozAlign: "right", bottomCalc: "sum", headerSort: false, formatter: cell => fmt2(cell.getValue()), bottomCalcFormatter: cell => fmt2(cell.getValue()) },
                    { title: "Remark", field: "remark", minWidth: 200, headerSort: false },
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
                        {field: "bill_no", type: "like", value: val},
                        { field: "voucher_serial", type: "like", value: val },
                        {field: "vehicle_name", type: "like", value: val},
                        {field: "challan_number", type: "like", value: val},
                        {field: "remark", type: "like", value: val}
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

/* ── Print: register ─────────────────────────────────────────────────────────── */

function printRegister() {
    printReport(multiExpenseRegisterPrintUrl, currentParams());
}

/* ── Filters ─────────────────────────────────────────────────────────────────── */

function applyFilters() {
    saveFilters();
    table.setData();
}

function saveFilters() {
    sessionStorage.setItem(FILTER_KEY, JSON.stringify({
        from_date:               $('#from_date').val(),
        to_date:                 $('#to_date').val(),
        filter_voucher:          $('#filter_voucher').val(),
        filter_account:          $('#filter_account').val(),
        filter_expense_account:  $('#filter_expense_account').val(),
    }));
}

function restoreFilters() {
    try {
        var saved = JSON.parse(sessionStorage.getItem(FILTER_KEY) || 'null');
        if (!saved) return;
        if (saved.from_date)              $('#from_date').val(saved.from_date);
        if (saved.to_date)                $('#to_date').val(saved.to_date);
        if (saved.filter_voucher)         $('#filter_voucher').val(saved.filter_voucher).trigger('change');
        if (saved.filter_account)         $('#filter_account').val(saved.filter_account).trigger('change');
        if (saved.filter_expense_account) $('#filter_expense_account').val(saved.filter_expense_account).trigger('change');

        if (saved.from_date || saved.to_date || saved.filter_voucher || saved.filter_account || saved.filter_expense_account) {
            table.setData();
        }
    } catch (e) { /* ignore */ }
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
        '#filter_voucher, #filter_account, #filter_expense_account'
    ];

    selectIdArray.forEach(function (id) {
        $(id).select2({
            theme: 'bootstrap-5',
            // allowClear: true,
            // placeholder: 'Select ...',
            // width: $(id).attr('style') && $(id).attr('style').includes('width') ? 'element' : '100%'
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
