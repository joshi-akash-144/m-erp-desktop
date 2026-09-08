var table = null;
var FILTER_KEY = 'vehicle_income_filter';

$(function () {
    // ── Initialize Dates ─────────────────────────────────────────
    new DateInput("#from_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#to_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    $('#from_date').val(currentDate());
    $('#to_date').val(currentDate());

    bindSelect2();

    restoreFilters();
    initTabulator();

    $('#btn_apply').on('click', function () { applyFilters(); });

    $('#btn_clear').on('click', function () {
        $('#from_date').val(currentDate());
        $('#to_date').val(currentDate());
        $('#filter_vehicle').val('').trigger('change');
        $('#filter_account').val('').trigger('change');
        $('#filter_voucher_no').val('').trigger('change');
        $('#filter_type').val('all').trigger('change');
        sessionStorage.removeItem(FILTER_KEY);
        table.setData();
    });

    // Event delegation for dropdown report actions
    $(document).on("click", ".dropdown-item", function (e) {
        const $action = $(this);
        const type = $action.data("type");
        const route = $action.data("route");
        const format = $action.data("format");

        if (!type) return;

        e.preventDefault();

        // Get the current selected filter parameters
        const safeFilter = currentParams();

        if (!safeFilter.from_date || !safeFilter.to_date) {
            Swal.fire({
                icon: "warning",
                title: "Date Required",
                text: "Please select a date range before printing or exporting.",
            });
            return;
        }

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

    $('#from_date, #to_date').on('keydown', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            if (typeof moveFocusToNextField === 'function') {
                moveFocusToNextField(this);
            }
        }
    });
});

/* ── Select2 ──────────────────────────────────────────────────*/
function bindSelect2() {
    var selectIdArray = ['#filter_vehicle', '#filter_account', '#filter_type', '#filter_voucher_no'];

     selectIdArray.forEach(element => {
        $(element).select2({
            theme: "bootstrap-5",
            allowClear: true,
            placeholder: "Select " + element.replace("#", "").replace(".", "").replace("_", " ").replace("id", "") + " ...",
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
                    if (typeof moveFocusToNextField === "function") {
                        moveFocusToNextField(selectElement);
                    }
                }
            });
    });
}

function currentParams() {
    var fromDmy = $('#from_date').val().trim();
    var toDmy   = $('#to_date').val().trim();
   
    return {
        from_date:  fromDmy ? formatDateToYMD(fromDmy) : '',
        to_date:    toDmy   ? formatDateToYMD(toDmy)   : '',
        vehicle_id: $('#filter_vehicle').val() || '',
        account_id: $('#filter_account').val() || '',
        voucher_no: $('#filter_voucher_no').val() || '',
        type:       $('#filter_type').val() || 'all',
    };
}

function applyFilters() {
    saveFilters(); 
    if (!validateDates($('#from_date').val().trim(), $('#to_date').val().trim())) return;   
    table.setData();
        
}

function saveFilters() {
    sessionStorage.setItem(FILTER_KEY, JSON.stringify({
        from_date:      $('#from_date').val(),
        to_date:        $('#to_date').val(),
        filter_vehicle: $('#filter_vehicle').val(),
        filter_account: $('#filter_account').val(),
        filter_voucher_no: $('#filter_voucher_no').val(),
        filter_type:    $('#filter_type').val(),
    }));
}

function restoreFilters() {
    try {
        var saved = JSON.parse(sessionStorage.getItem(FILTER_KEY) || 'null');
        if (!saved) return;
        if (saved.from_date)      $('#from_date').val(saved.from_date);
        if (saved.to_date)        $('#to_date').val(saved.to_date);
        if (saved.filter_vehicle) $('#filter_vehicle').val(saved.filter_vehicle).trigger('change');
        if (saved.filter_account) $('#filter_account').val(saved.filter_account).trigger('change');
        if (saved.filter_voucher_no) $('#filter_voucher_no').val(saved.filter_voucher_no).trigger('change');
        if (saved.filter_type)    $('#filter_type').val(saved.filter_type).trigger('change');
    } catch (e) { /* ignore */ }
}

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

/* ── Build / Rebuild Tabulator ────────────────────────────────*/
function initTabulator() {
    var columns = [
        {
            title: 'Sr. No.',
            field: 'sr_no',
            hozAlign: 'center',
            headerSort: false,
            width: 80,
            frozen: false,
            formatter: 'rownum',
        },
        {
            field: 'voucher_no',
            title: 'Voucher No',
            minWidth: 120,
            headerSort: false,
            hozAlign: 'center',
            headerHozAlign: 'center',
            
        },
        {
            field: 'date',
            title: 'Date',
            frozen: true,
            minWidth: 110,
            headerSort: false,
        },
        {
            field: 'party_name',
            title: 'Party Name',
            minWidth: 200,
            headerSort: false,
        },
        {
            field: 'ref_no',
            title: 'Ref No',
            minWidth: 120,
            headerSort: false,
        },
        {
            field: 'vehicle_name',
            title: 'Vehicle Number',
            minWidth: 150,
            headerSort: false,
            hozAlign: 'center',
            headerHozAlign: 'center',
        },
        {
            field: 'type',
            title: 'Type',
            minWidth: 150,
            headerSort: false,
            hozAlign: 'center',
            headerHozAlign: 'center',           
            // bottomCalcFormatter: function(cell) {
            //     return "Total"
            // }
        },
        {
            field: 'amount',
            title: 'Amount',
            hozAlign: 'right',
            headerHozAlign: 'right',
            minWidth: 200,
            headerSort: false,
            
            formatter: function(cell) {
                var v = parseFloat(cell.getValue());
                return v ? formatIndianNumber(v) : '';
            },
            bottomCalc: 'sum',
            bottomCalcFormatter: function(cell) {
                var v = parseFloat(cell.getValue()) || 0;
                return v ? formatIndianNumber(v) : '';
            }
        }
    ];

    table = new Tabulator('#vehicle_income_table', {
        columns:           columns,
        layout:           'fitDataFill',
        height:           'calc(100vh - 340px)',
        placeholder:      'No data for the selected period.',
        columnCalcs:      'both',
        movableColumns:   false,
        resizableRows:    false,
        
        pagination:       false,
        virtualDom:       true,
        
        ajaxURL: vehicleIncomeDataUrl,
        ajaxParams: function () { return currentParams(); },
        ajaxURLGenerator: function (url, _config, params) {
            var p = Object.assign({}, params, currentParams());
            return url + '?' + new URLSearchParams(p).toString();
        },
        ajaxResponse: function (url, params, response) {
            if (response && response.data && response.data.data) {
                return response.data.data;
            } else if (response && response.data) {
                return response.data;
            }
            return [];
        },
    });



}
