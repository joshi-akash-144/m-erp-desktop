
const WIDTH_KEY = "tds_entries_col_widths";
// ============================================================
// TDS ENTRIES REPORT
// ============================================================

function formatAmount(val) {
    return parseFloat(val || 0).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}


let table;
let currentSummary = { total_payment_amount: 0, total_tds_amount: 0, total_records: 0 };

function loadTdsEntries() {
    if (table) {
        table.setData();
    } else {
        const saved = getSavedWidths();
        table = new Tabulator("#tds_output", {
            height:"550px",
            ajaxURL: TDS_LIST_URL,
            ajaxURLGenerator: function(url, config, params) {
                var page = params.page || 1;
                var size = params.size || 50;
                var formParams = $('#tds_filter_form').serializeArray().reduce(function(obj, item) {
                    if (item.value) obj[item.name] = item.value;
                    return obj;
                }, {});
                var qp = new URLSearchParams($.extend({}, formParams, { page: page, size: size }));
                return url + "?" + qp.toString();
            },
            progressiveLoad: "scroll",
            paginationSize: 50,
            paginationDataSent: {
                page: "page",
                size: "size",
            },
            paginationDataReceived: {
                last_page: "last_page",
                data: "data",
            },
            layout: "fitColumns",
            columns: getColumns(saved),
            ajaxResponse: function(url, params, response){
                if(response.summary) {
                    currentSummary = response.summary;
                }
                return response;
            },
            columnResized: function (column) {
                let widths = getSavedWidths();
                widths[column.getField()] = column.getWidth();
                localStorage.setItem(WIDTH_KEY, JSON.stringify(widths));
            },
        });
    }
}

$(function () {
    bindSelect2();

    new DateInput('#from_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('#to_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    loadTdsEntries();

    $('#tds_filter_form').on('submit', function (e) {
        e.preventDefault();
        loadTdsEntries();
    });

    $('#filter_clear').on('click', function () {
        $('#tds_filter_form')[0].reset();
        $('.select2').val('').trigger('change');
        if (table) {
            table.setData();
        }
    });

    // Print Report
  $(document).on("click", ".dropdown-item", function (e) {
    const action = e.target.closest(".dropdown-item");
    if (!action) return;
 
    const type = action.dataset.type; // print / export
    const route = action.dataset.route;
    const format = action.dataset.format;
 
    if (!type) return;

    var currentFilter = $('#tds_filter_form').serializeArray().reduce(function(obj, item) {
        if (item.value) obj[item.name] = item.value;
        return obj;
    }, {});

    if (!currentFilter.from_date || !currentFilter.to_date) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Date Required',
                text: 'Please select both From Date and To Date before exporting or printing.'
            });
        } else if (typeof showToast !== 'undefined') {
            showToast('warning', 'Please select both From Date and To Date before exporting or printing.');
        } else {
            alert('Please select both From Date and To Date before exporting or printing.');
        }
        return;
    }
 
    switch (type) {
      case "print":
        printReport(route, $.extend({}, currentFilter, { format: format }));
        break;

      case "excel":
        downloadExcel(route, currentFilter);
        break;
    }
  });
});

// ===========================================================
// SELECT2 INIT
// ===========================================================
function bindSelect2() {
    var selectIdArray = [
        "#tds_category_id", "#voucher_type_id"
    ];

    $.each(selectIdArray, function (i, element) {
        $(element).select2({
            theme: "bootstrap-5",
            allowClear: true,
            placeholder: "Select ...",
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
                    moveFocusToNextField(selectElement);
                }
            });
    });
}
function getSavedWidths() {
    try {
        return JSON.parse(localStorage.getItem(WIDTH_KEY)) || {};
    } catch (e) {
        return {};
    }
}

// ===========================================================
// TABLE COLUMNS
// ===========================================================
function getColumns(saved) {
    return [
        {
            title: "No",
            field: "no",
            width: saved.date ?? 70,
            headerHozAlign: "center",
            hozAlign: "center",
            formatter:'rownum',
            headerSort:false,
        },
        {
            title: "Date",
            field: "date",
            width: saved.date ?? 150,
            headerHozAlign: "center",
            hozAlign: "center",
            headerSort:false,
        },
        {
            title: "Reference No.",
            field: "reference_number",
            width: saved.reference_number ?? 150,
            headerSort:false,
            headerHozAlign: "center",
            hozAlign: "center",
        },
        {
            title: "Deductee Name",
            field: "deductee_name",
            headerSort: false,
            headerHozAlign: "left",
            hozAlign: "left",
            width: saved.deductee_name ?? 350,
            formatter:function(cell){
                let data = cell.getRow().getData();
                return `<div class="text-truncate" title="${data.deductee_name}">
                            ${data.deductee_name || '—'}
                        </div>`
            }
        },
        {
            title: "Pan No",
            field: "pan_no",
            width: saved.pan_no ?? 150,
            headerSort: false,
            headerHozAlign: "left",
            hozAlign: "left",
            bottomCalc: () => "Sub-Total",
            bottomCalcFormatter: (cell) => `<span class="fw-bold text-end w-100 d-block">${cell.getValue()}</span>`
        },
        {
            title: "Payment Amt",
            field: "payment_amount",
            width: saved.payment_amount ?? 180,
            headerSort: false,
            headerHozAlign: "right",
            hozAlign: "right",
            formatter: (cell) => formatAmount(cell.getValue()),
            bottomCalc: () => currentSummary.total_payment_amount || 0,
            bottomCalcFormatter: (cell) => `<span class="fw-bold">₹${formatAmount(cell.getValue())}</span>`
        },
        {
            title: "TDS Rate",
            field: "tds_rate",
            width: saved.tds_rate ?? 100,
            headerSort: false,
            headerHozAlign: "right",
            hozAlign: "right",
            formatter: (cell) => cell.getValue() ? `${formatNumber(cell.getValue() , 2 )}%` : '—'
        },
        {
            title: "TDS Amount",
            field: "tds_amount",
            width: saved.tds_amount ?? 150,
            headerSort: false,
            headerHozAlign: "right",
            hozAlign: "right",
            formatter: (cell) => formatAmount(cell.getValue()),
            bottomCalc: () => currentSummary.total_tds_amount || 0,
            bottomCalcFormatter: (cell) => `<span class="fw-bold text-danger">₹${formatAmount(cell.getValue())}</span>`
        },
        {
            title: "Voucher Type",
            field: "voucher_type",
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
            width: saved.voucher_type ?? 150,
            formatter: (cell) => {
                const val = cell.getValue();
                if (!val) return '—';
                
                let colorClass = 'bg-secondary-lt text-secondary';
                const lowerVal = val.toLowerCase();
                
                if (lowerVal.includes('payment')) {
                    colorClass = 'bg-success-lt text-success';
                } else if (lowerVal.includes('sale')) {
                    colorClass = 'bg-danger-lt text-danger';
                } else if (lowerVal.includes('purchase')) {
                    colorClass = 'bg-primary-lt text-primary';
                }
                
                return `<span class="badge ${colorClass}">${val}</span>`;
            }
        },
        {
            title: "Section",
            field: "section",
            headerHozAlign: "left",
            hozAlign: "left",
            headerSort: false,
            width: saved.section ?? 130,
        },
        {
            title: "Remarks",
            field: "remarks",
            headerHozAlign: "left",
            headerSort: false,
            width: saved.remarks ?? 130,
            hozAlign: "left",
        }
    ]
}