/**
 * Credit Note — index.js
 * Tabulator-based listing with filters.
 */

var cnTable;

$(document).ready(function () {

    // ── Select2 ───────────────────────────────────────────────────────────────
    $('#filter_account_id').select2({ theme: 'bootstrap-5' });

    // Date pickers
    new DateInput('#filter_start_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput('#filter_end_date',   FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    // ── Build Tabulator ───────────────────────────────────────────────────────
    cnTable = new Tabulator('#credit_note_table', {
        ajaxURL:          cnListUrl,
        ajaxConfig:       'GET',
        ajaxParams:       buildFilters,
        ajaxRequesting:   function () { return true; },
        ajaxResponse:     handleResponse,
        pagination:       true,
        paginationMode:   'remote',
        paginationSize:   50,
        layout:           'fitDataStretch',
        responsiveLayout: 'collapse',
        placeholder:      'No records found.',
        height:           '60vh',
        columnCalcs: 'both',
        columns: [
            { title: 'Bill No',          field: 'credit_note_serial', width: 80,  hozAlign: 'center', sorter: 'number', headerSort: false },
            { title: 'Bill Date',       field: 'credit_note_date',   width: 110, formatter: dateFormatter, headerSort: false },
            {
                title: 'Customer Name', field: "account.name", minWidth: 800, headerSort: false,
            },
            {
                title: 'Item Name', field: 'details', width: 300, headerSort: false, formatter: function (cell) {
                    let details = cell.getValue();
                    return (details && details.length && details[0].item) ? details[0].item.name : '';

                }
            },
            {
                title: 'Net Total', field: 'net_amount', headerHozAlign: 'right', width: 130, hozAlign: 'right',
                formatter: amountDangerFormatter, headerSort: false,
                bottomCalc: 'sum',
                bottomCalcFormatter: function (cell) {
                    var val = parseFloat(cell.getValue()) || 0;
                    return '<span class="text-danger fw-semibold">₹' + val.toLocaleString('en-IN', { minimumFractionDigits: 2 }) + '</span>';
                },
                bottomCalcFormatterParams: { hozAlign: 'right' },
            },
            { title: 'Actions',    field: 'id',                 width: 110, hozAlign: 'center', formatter: actionsFormatter, headerSort: false },
        ],
    });

    // ── Filters ───────────────────────────────────────────────────────────────
    $('#apply_filter').on('click',  function () { cnTable.replaceData(); });
    $('#clear_filter').on('click',  function () {
        $('#filter_start_date, #filter_end_date').val('');
        $('#filter_account_id').val('').trigger('change.select2');
        cnTable.replaceData();
    });
});

// ────────────────────────────────────────────────────────────────────────────
// AJAX helpers
// ────────────────────────────────────────────────────────────────────────────
function buildFilters() {
    return {
        start_date: formatDateToYMD($('#filter_start_date').val()),
        end_date:   formatDateToYMD($('#filter_end_date').val()),
        account_id: $('#filter_account_id').val(),
        // bill_from:  $('#filter_bill_from').val(),
        // bill_to:    $('#filter_bill_to').val(),
        _: Date.now(),
    };
}

function handleResponse(url, params, response) {
    // Update summary badges
    $('#badge_total').text(response.total ?? 0);
    var totalNet = (response.data || []).reduce(function (s, r) { return s + parseFloat(r.net_amount || 0); }, 0);
    $('#badge_net_amount').text('₹' + totalNet.toFixed(2));

    cnTable.options.customPermissions = response.permissions || {};

    return {
        last_page: response.last_page,
        data:      response.data,
    };
}

// ────────────────────────────────────────────────────────────────────────────
// FORMATTERS
// ────────────────────────────────────────────────────────────────────────────
function dateFormatter(cell) {
    var val = cell.getValue();
    return val ? formatDateToDMY(val) : '—';
}

function amountFormatter(cell) {
    var val = parseFloat(cell.getValue()) || 0;
    return val.toLocaleString('en-IN', { minimumFractionDigits: 2 });
}

function amountDangerFormatter(cell) {
    var val = parseFloat(cell.getValue()) || 0;
    return '<span class="text-danger fw-semibold">₹' + val.toLocaleString('en-IN', { minimumFractionDigits: 2 }) + '</span>';
}

function gstTypeBadge(cell) {
    var val  = cell.getValue();
    var cls  = val === 'local' ? 'bg-info' : 'bg-warning text-dark';
    var label = val ? val.charAt(0).toUpperCase() + val.slice(1) : '—';
    return '<span class="badge ' + cls + '">' + label + '</span>';
}

function actionsFormatter(cell) {
    var id   = cell.getValue();
    var data = cell.getRow().getData();
    var permissions = cnTable.options.customPermissions || {};
    
    var html = '<div class="d-flex gap-2 align-items-center justify-content-center h-100 w-100">';

    if (permissions.update) {
        if (data.is_paid) {
            html += '<span class="erp-btn-icon text-secondary" title="Cannot Edit: Already Paid/Settled" style="opacity: 0.5; cursor: not-allowed;"><i class="fa-solid fa-lock"></i></span>';
        } else {
            html += '<a href="' + cnEditUrl + '?credit_note_id=' + id + '" class="erp-btn-icon edit" title="Edit">' + icons.edit + '</a>';
        }
    }

    if (permissions.delete) {
        if (data.is_paid) {
            html += '<span class="erp-btn-icon text-secondary" title="Cannot Delete: Already Paid/Settled" style="opacity: 0.5; cursor: not-allowed;"><i class="fa-solid fa-lock"></i></span>';
        } else {
            html += '<span class="erp-btn-icon delete delete-btn" data-id="' + id + '" title="Delete">' + icons.delete + '</span>';
        }
    }

    html += '</div>';
    return html;
}

$(document).on('click', '.delete-btn', function() {
    var id = $(this).data('id');
    var url = cnListUrl + '/' + id;

    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, delete it!"
    }).then((result) => {
        if (result.isConfirmed) {
            showLoader('Deleting...');

            $.ajax({
                url: url,
                type: 'DELETE',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function(response) {
                    hideLoader();
                    if (response.success) {
                        showToast('success', response.message);
                        cnTable.setPage(cnTable.getPage());
                    } else {
                        showToast('error', response.message || 'Failed to delete');
                    }
                },
                error: function(xhr) {
                    hideLoader();
                    var msg = xhr.responseJSON ? xhr.responseJSON.message : 'An error occurred';
                    showToast('error', msg);
                }
            });
        }
    });
});
