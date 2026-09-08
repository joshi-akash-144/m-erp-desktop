
var table;
var FILTER_KEY = 'expense_register_filter';
var grandTotalAmount = 0;
var totalFilteredRecords = 0;

$(function () {

    bindSelect2();
    new DateInput('#start_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('#end_date',   FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    initTabulator();
    restoreFilters();

    $('#btn_apply').on('click', applyFilters);

    $('#btn_clear').on('click', function () {
        $('#start_date').val('');
        $('#end_date').val('');
        $('#filter_vehicle').val('').trigger('change');
        $('#filter_expense_account').val('').trigger('change');
        $('#filter_voucher_number').val('').trigger('change');
        $('#filter_party_name').val('').trigger('change');
        $('#filter_reference_number').val('').trigger('change');
        sessionStorage.removeItem(FILTER_KEY);
        table.setData();
    });
   
});

/* ── Tabulator init ─────────────────────────────────────────────────────────── */

function currentParams() {
    var p = {};
    var start   = $('#start_date').val();
    var end     = $('#end_date').val();
    var vehicle = $('#filter_vehicle').val();
    var account = $('#filter_expense_account').val();
    var voucher_serial = $('#filter_voucher_number').val();
    var party_name = $('#filter_party_name').val();
    var reference_number = $('#filter_reference_number').val();

    if (start)   p.start_date          = dmyToYmd(start);
    if (end)     p.end_date            = dmyToYmd(end);
    if (vehicle) p.vehicle_id          = vehicle;
    if (account) p.expense_account_id  = account;
    if (voucher_serial) p.voucher_serial = voucher_serial;
    if (party_name) p.party_id           = party_name;
    if (reference_number) p.reference_number = reference_number;
    return p;
}

function initTabulator() {
    table = new Tabulator('#expense_register_table', {
        height: '550px',
        // layout: 'fitColumns',
        pagination: true,              
        paginationSize: 50,
        paginationSizeSelector: [10, 20, 50, 100, 300],
        paginationMode: "remote",
        paginationDataSent:     { page: 'page', size: 'size' },
        paginationDataReceived: { last_page: 'last_page', data: 'data', total: 'total' },

        ajaxURL: expenseRegisterListUrl,
        ajaxParams: function () { return currentParams(); },
        ajaxURLGenerator: function (url, _config, params) {
            var p = Object.assign({}, params, currentParams());
            return url + '?' + new URLSearchParams(p).toString();
        },
        ajaxResponse: function (url, params, response) {
            grandTotalAmount = parseFloat(response.grand_total_raw) || 0;
            totalFilteredRecords = response.total || 0;
            return response;
        },
        paginationCounter: function(pageSize, currentRow, _currentPage, _totalRows, _totalPages) {
            const total = totalFilteredRecords;
            if (!total) return "No entries found";
            const start = currentRow;
            const end   = Math.min(currentRow + pageSize - 1, total);
            return `Showing ${start} to ${end} of ${total} entries`;
        },

        placeholder: '<div class="text-center py-5 text-muted">' +
            '<i class="fa-solid fa-receipt fs-1 mb-3 d-block"></i>' +
            'No expenses found.' +
            '</div>',

        columns: [
            {
                title: '#SR',
                field: '_row_num',
                width: 55,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
                formatter: 'rownum',
            },
            {
                title: 'Voucher No.',
                field: 'voucher_serial',
                width: 110,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
            },
            {
                title: 'Voucher Date',
                field: 'voucher_date',
                width: 115,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
            },
            {
                title: 'Party Name',
                field: 'party_name',
                minWidth: 300,
                headerSort: false,
            },
            {
                title: 'Reference Number',
                field: 'reference_number',
                minWidth: 150,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
            },
            {
                title: 'Vehicle / Account',
                field: 'account_name',
                minWidth: 350,
                headerSort: false,
            },
            {
                title: 'Expense Account',
                field: 'expense_account',
                minWidth: 350,
                headerSort: false,
            },
            {
                title: 'Amount',
                field: 'amount',
                width: 200,
                hozAlign: 'right',
                headerHozAlign: 'right',
                headerSort: false,
                frozen: true,
                bottomCalc: function(values, data, calcParams) {
                    let total = 0;
                    values.forEach(function(value) {
                        // Strip commas if any, though usually backend sends raw numbers
                        if (typeof value === 'string') {
                            value = value.replace(/,/g, '');
                        }
                        total += parseFloat(value) || 0;
                    });
                    return total;
                },
                bottomCalcFormatter: function (cell) {
                    return '<strong class="text-white">' + formatIndianNumber(cell.getValue() || 0) + '</strong>';
                },
                formatter: function (cell) {
                    return formatIndianNumber(cell.getValue());
                },
            },
        ],
    });   
}

// Event delegation for dropdown report actions
$(document).on("click", ".dropdown-item", function (e) {
    
    const $action = $(this);
    const type = $action.data("type");
    const route = $action.data("route");
    const format = $action.data("format");

    if (!type) return;

    // Get the current selected filter parameters
    const safeFilter = currentParams();

    switch (type) {
        case "print":
            if (typeof printReport === "function") {
                printReport(route, { format, currentFilter: safeFilter });
            }
            break;

        case "excel":
            if (typeof downloadExcel === "function") {
                downloadExcel(route, { currentFilter: safeFilter });
            }
            break;
    }
});

function applyFilters() {
    saveFilters();
    table.setData();
}

function saveFilters() {
    sessionStorage.setItem(FILTER_KEY, JSON.stringify({
        start_date:             $('#start_date').val(),
        end_date:               $('#end_date').val(),
        filter_vehicle:         $('#filter_vehicle').val(),
        filter_expense_account: $('#filter_expense_account').val(),
        voucher_serial:         $('#filter_voucher_number').val(),
        party_name:             $('#filter_party_name').val(),
        reference_number:       $('#filter_reference_number').val(),
    }));
}

function restoreFilters() {
    try {
        var saved = JSON.parse(sessionStorage.getItem(FILTER_KEY) || 'null');
        if (!saved) return;
        if (saved.start_date)             $('#start_date').val(saved.start_date);
        if (saved.end_date)               $('#end_date').val(saved.end_date);
        if (saved.filter_vehicle)         $('#filter_vehicle').val(saved.filter_vehicle).trigger('change');
        if (saved.filter_expense_account) $('#filter_expense_account').val(saved.filter_expense_account).trigger('change');
        if (saved.voucher_serial)         $('#filter_voucher_number').val(saved.voucher_serial).trigger('change');
        if (saved.party_name)             $('#filter_party_name').val(saved.party_name).trigger('change');
        if (saved.reference_number)       $('#filter_reference_number').val(saved.reference_number).trigger('change');

        var hasAny = saved.start_date || saved.end_date || saved.filter_vehicle || saved.filter_expense_account || saved.voucher_serial || saved.party_name || saved.reference_number;
        if (hasAny) table.setData();
    } catch (e) { /* ignore */ }
}



function dmyToYmd(dmy) {
    if (!dmy) return '';
    var parts = dmy.split('-');
    return parts.length === 3 ? parts[2] + '-' + parts[1] + '-' + parts[0] : dmy;
}

function bindSelect2() {

  const selectIdArray = [
    "#filter_vehicle",
    "#filter_voucher_number",
    "#filter_expense_account",
    "#filter_party_name",
    "#filter_reference_number",
  ];
  selectIdArray.forEach((element) => {
    $(element).select2({
      theme: "bootstrap-5",
    //   allowClear: true,
    //   placeholder:"Select " +element .replace("#", "").replace("_", " ").replace(".", " ").replace("_id", "") + " ...",
    //   width: "100%",
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