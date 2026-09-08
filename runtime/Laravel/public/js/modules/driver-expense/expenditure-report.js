/* ============================================================
   Vehicle Expenditure Report  —  Tabulator JS
   ============================================================ */

'use strict';

var _table = null;

$(function () {
    // ── Initialize Dates ─────────────────────────────────────────
    new DateInput("#from_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#to_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    $('#from_date').val(currentDate());
    $('#to_date').val(currentDate());

    bindSelect2();

    loadReport();

    $('#btn_apply').on('click', function () { loadReport(); });

    $('#btn_clear').on('click', function () {
        $('#from_date').val(currentDate());
        $('#to_date').val(currentDate());
        $('#filter_vehicle').val('').trigger('change');
        $('#view_type').val('summary');
        loadReport();
    });

    $('#from_date, #to_date').on('keydown', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            if (typeof moveFocusToNextField === 'function') {
                moveFocusToNextField(this);
            }
        }
    });

    // ── Export ───────────────────────────────────────────────
    $('#export_xlsx_btn').on('click', function (e) {
        e.preventDefault();
        var route = $(this).data('route');
        
        var fromDmy = $('#from_date').val().trim();
        var toDmy   = $('#to_date').val().trim();

        var params = {
            from_date:  fromDmy ? dmyToYmd(fromDmy) : '',
            to_date:    toDmy   ? dmyToYmd(toDmy)   : '',
            vehicle_id: $('#filter_vehicle').val() || '',
            view_type:  $('#view_type').val() || 'summary',
        };

        if (typeof downloadExcel === "function") {
            downloadExcel(route, { currentFilter: params });
        } else {
            console.error("downloadExcel function is not defined in main.js");
            showToast('error', 'Export handler not found.');
        }
    });
});

/* ── Select2 ──────────────────────────────────────────────────*/
function bindSelect2() {
    var selectIdArray = ['#filter_vehicle', '#view_type'];

    selectIdArray.forEach(function (element) {
        $(element).select2({
            theme: 'bootstrap-5',
            // allowClear: true,
            // placeholder: 'Select ...',
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

/* ── Core loader ──────────────────────────────────────────────*/
function loadReport() {
    var fromDmy = $('#from_date').val().trim();
    var toDmy   = $('#to_date').val().trim();

    $.ajax({
        url:    vehicleExpenditureDataUrl,
        method: 'GET',
        data: {
            from_date:  fromDmy ? dmyToYmd(fromDmy) : '',
            to_date:    toDmy   ? dmyToYmd(toDmy)   : '',
            vehicle_id: $('#filter_vehicle').val() || '',
            view_type:  $('#view_type').val() || 'summary',
        },
        beforeSend: function () { showTableLoader(); },
        success: function (res) {
            if (res && res.data) {
                buildTable(res.data.columns, res.data.reportRows);
            }
        },
        error: function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to load report.';
            showToast('error', msg);
            hideTableLoader();
        }
    });
}

/* ── Build / Rebuild Tabulator ────────────────────────────────*/
function buildTable(serverColumns, rows) {
    if (_table) { _table.destroy(); _table = null; }

    var columns = serverColumns.map(function (col) {
        var def = {
            field:      col.field,
            title:      col.title,
            minWidth:   col.minWidth || 100,
            hozAlign:   col.hozAlign || 'left',
            headerHozAlign: col.hozAlign || 'center',
            headerSort: false,       // no sorting on any column
            frozen:     col.frozen || false,
            headerFilter: false,     // NO column search inputs
        };

        // ── Numeric columns: format + bottom sum ──────────────
        if (col.hozAlign === 'right') {
            def.formatter = function (cell) {
                var v = parseFloat(cell.getValue());
                if (!v) return '';
                return formatNum(v);
            };

            if (col.field !== 'profit_loss') {
                def.bottomCalc       = 'sum';
                def.bottomCalcFormatter = function (cell) {
                    var v = parseFloat(cell.getValue()) || 0;
                    return v ? formatNum(v) : '';
                };
            }
        }

        if (col.field === 'profit_loss' || col.field === 'income' || col.field === 'total_expense') {
            def.minWidth = 170;
        }

        // ── Profit / Loss: green / red in cell and total ──────
        if (col.field === 'profit_loss') {
            def.formatter = profitLossFormatter;
            def.bottomCalc = function (values) {
                return values.reduce(function (sum, v) {
                    return sum + (parseFloat(v) || 0);
                }, 0);
            };
            def.bottomCalcFormatter = function (cell) {
                var v = parseFloat(cell.getValue()) || 0;
                // !important needed: blade's .tabulator-calcs-bottom .tabulator-cell rule
                // forces color:#fff !important, which beats a plain inline color.
                var color = v >= 0 ? '#ffffff' : '#dc3545';
                var display = v >= 0 ? formatNum(v) : '(' + formatNum(Math.abs(v)) + ')';
                return '<span style="color:' + color + ' !important;font-weight:700;">' + display + '</span>';
            };
        }

        return def;
    });

    _table = new Tabulator('#vehicle_expenditure_table', {
        data:             rows,
        columns:          columns,
        layout:           'fitDataFill',
        height:           'calc(100vh - 240px)',
        placeholder:      'No data for the selected period.',
        columnCalcs:      'both',      // show bottom calc row
        movableColumns:   false,
        resizableRows:    false,
    });
}

/* ── Profit / Loss cell formatter ────────────────────────────*/
function profitLossFormatter(cell) {
    var v   = parseFloat(cell.getValue()) || 0;
    var el  = cell.getElement();

    // .tabulator-cell color is forced with !important in tabulator.css,
    // so plain style.color is silently ignored — must set !important too.
    el.style.removeProperty('color');
    el.style.fontWeight = '';

    if (v === 0) return '';

    if (v > 0) {
        el.style.setProperty('color', '#198754', 'important');
        el.style.fontWeight = '700';
        return formatNum(v);
    } else {
        el.style.setProperty('color', '#dc3545', 'important');
        el.style.fontWeight = '700';
        return '(' + formatNum(Math.abs(v)) + ')';
    }
}

/* ── Loader helpers ───────────────────────────────────────────*/
function showTableLoader() {
    $('#vehicle_expenditure_table').html(
        '<div class="d-flex align-items-center justify-content-center py-5 gap-2">' +
        '<div class="spinner-border text-primary"></div>' +
        '<span class="fw-semibold text-secondary">Loading report…</span></div>'
    );
}
function hideTableLoader() {
    if (!_table) {
        $('#vehicle_expenditure_table').html(
            '<div class="text-center py-4 text-muted">No data loaded.</div>'
        );
    }
}

/* ── Utilities ────────────────────────────────────────────────*/
function formatNum(v) {
    return parseFloat(v || 0).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function dmyToYmd(str) {
    if (!str) return '';
    var p = str.split('-');
    return p.length === 3 ? p[2] + '-' + p[1] + '-' + p[0] : str;
}
