let mainTable;
let modalTable;
let currentFilter = { is_used: '0' };
let totalFilteredRecords = 0;
// let currentPermissions = {};

const FILTER_KEY = "dairy_file_filter";

const filterFields = [
    "import_date",
    "product_id",
    "is_used"
];

$(function () {

    ['#product_id', '#is_used'].forEach(sel => {
        $(sel).select2({ theme: 'bootstrap-5', allowClear: true });
    });

    new DateInput('#import_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    restoreFilters();

    // ── Main Tabulator (one row per import batch) ──────────────────────────
    mainTable = new Tabulator('#dairy_file_register_table', {
        height: '600px',
        layout: 'fitColumns',
        placeholder: `
            <div class="text-center py-5">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="icon text-muted mb-3">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                    <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                    <path d="M10 12l4 4m0 -4l-4 4"/>
                </svg>
                <h3 class="text-muted">No data found</h3>
                <p class="text-muted">Try adjusting your filters</p>
            </div>`,

        pagination:             true,
        paginationMode:         'remote',
        paginationSize:         50,
        paginationSizeSelector: [10, 20, 50, 100, true],
        paginationDataSent:     { page: 'page', size: 'size' },
        paginationDataReceived: { last_page: 'last_page', data: 'data', total: 'total' },

        ajaxURL: dairyFileRegisterListUrl,
        ajaxParams: () => currentFilter,
        ajaxURLGenerator(url, _config, params) {
            const qs = new URLSearchParams({ ...currentFilter, page: params.page ?? 1, size: params.size ?? 50 });
            return `${url}?${qs}`;
        },
        ajaxResponse(_url, _params, response) {
            totalFilteredRecords = response.total || 0;
            return response;
        },

        paginationCounter(pageSize, currentRow, _page, _totalRows, _totalPages) {
            if (!totalFilteredRecords) return 'No entries found';
            const end = Math.min(currentRow + pageSize - 1, totalFilteredRecords);
            return `Showing ${currentRow} to ${end} of ${totalFilteredRecords} entries`;
        },

        columns: [
            {
                title: '#',
                formatter: 'rownum',
                hozAlign: 'center',
                headerHozAlign: 'center',
                vertAlign: 'middle',
                headerSort: false,
                width: 55,
            },
            {
                title: 'Import Date',
                field: 'import_date',
                hozAlign: 'center',
                headerHozAlign: 'center',
                vertAlign: 'middle',
                headerSort: false,
                width: 130,
            },
            {
                title: 'Product',
                field: 'product_name',
                vertAlign: 'middle',
                headerSort: false,
                minWidth: 200,
            },
            {
                title: 'Total Items',
                field: 'items_count',
                hozAlign: 'center',
                headerHozAlign: 'center',
                vertAlign: 'middle',
                headerSort: false,
                width: 110,
                formatter(cell) {
                    return `<span class="badge bg-blue-lt text-primary fw-semibold px-2">${cell.getValue()}</span>`;
                },
            },
            {
                title: 'Status',
                field: 'is_used',
                hozAlign: 'center',
                headerHozAlign: 'center',
                vertAlign: 'middle',
                headerSort: false,
                width: 110,
                formatter(cell) {
                    return cell.getValue()
                        ? '<span class="badge bg-success-lt text-success fw-semibold">Used</span>'
                        : '<span class="badge bg-warning-lt text-warning fw-semibold">Not Used</span>';
                },
            },
            {
                title: "Created By",
                field: "created_by",            
                hozAlign: "center",
                headerHozAlign: "center",
                headerSort: false,
                resizable: true,
                width: 100,
                formatter: (cell) => {
                    const creatorName = cell.getRow().getData().creator?.name || null;
                    return creatorName
                    ? `<span class="badge bg-danger-lt ">${creatorName}</span>`
                    : `<span class="badge bg-cyan-lt">System</span>`;
                },
            },
            {
                title: 'Action',
                field: 'id',
                hozAlign: 'center',
                headerHozAlign: 'center',
                vertAlign: 'middle',
                headerSort: false,
                width: 90,
                formatter(cell) {
                    const row = cell.getRow().getData();
                    // const canView = currentPermissions.view ?? true;                    
                    // const canDelete = currentPermissions.delete ?? true;

                    const viewIcon = icons.view;
                    const deleteIcon = icons.delete;
                    
                    let actions =
                    '<div class="d-flex gap-2 align-items-center justify-content-center h-100 w-100">';                    

                    // if (canView) {
                        actions += `
                            <button class="erp-btn-icon view show-detail-btn" data-id="${row.id}" data-date="${row.import_date}" data-product="${row.product_name}" title="View Details">
                                ${viewIcon}
                            </button>`;
                    // }                    
                    // if (canDelete) {
                        actions += `
                        <button class="erp-btn-icon delete delete-import-btn" data-id="${row.id}" title="Delete Batch">
                            ${deleteIcon}
                        </button>`;
                    // }

                    actions += "</div>";
                    return actions;
                },
            },
        ],
    });

    // ── Filter apply ───────────────────────────────────────────────────────
    $('#filter_apply').on('click', function (e) {
        e.preventDefault();
        applyFilter();
    });

    $('#filter_clear').on('click', function () {

        $.each(filterFields, function (i, id) {
            const defaultValue = id === "is_used" ? '0' : "";
            setFieldValue(id, defaultValue);
        });

        // $('#import_date').val('');
        // $('#product_id').val('').trigger('change');
        // $('#is_used').val('0').trigger('change');
        currentFilter = { is_used: '0' };
        localStorage.removeItem(FILTER_KEY);
        mainTable.setPage(1);
    });

    function applyFilter() {
        currentFilter = {};
        $.each(filterFields, function (i, id) {
            var value = getFieldValue(id);
            if (value) currentFilter[id] = value;
        });
        if ($('#import_date').val()) currentFilter.import_date = $('#import_date').val();
        if ($('#product_id').val())  currentFilter.product_id  = $('#product_id').val();
        const isUsed = $('#is_used').val();
        if (isUsed !== '') currentFilter.is_used = isUsed;
        localStorage.setItem(FILTER_KEY, JSON.stringify(currentFilter));
        mainTable.setPage(1);
    }

     /** Restore filters on page load */
    function restoreFilters() {
        var saved = localStorage.getItem(FILTER_KEY);
        if (!saved) return;

        currentFilter = JSON.parse(saved);

        $.each(currentFilter, function (id, value) {
            setFieldValue(id, value);
        });
    }
    // ── Show detail modal ──────────────────────────────────────────────────
    $(document).on('click', '.show-detail-btn', function () {
        const $btn    = $(this);
        const id      = $btn.data('id');
        const date    = $btn.data('date');
        const product = $btn.data('product');

        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

        $('#importDetailModalLabel').html(
            `<i class="fa-solid fa-table-list me-2"></i>Import Details`
        );
        $('#modal_subtitle').text(`${product}  ·  ${date}`);
        $('#modal_loading').show().html(
            `<i class="fa-solid fa-spinner fa-spin fa-2x mb-2 d-block"></i> Loading...`
        );
        $('#modal_detail_table').hide();

        $('#importDetailModal').modal('show');

        const url = dairyFileImportItemsUrl.replace(':id', id);
        $.get(url)
            .done(res => {
                buildModalTable(res.items);
                $('#modal_loading').hide();
                $('#modal_detail_table').show();
            })
            .fail(xhr => {
                $('#modal_loading').html(
                    `<div class="text-danger py-4 text-center">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        ${xhr.responseJSON?.message ?? 'Failed to load details.'}
                    </div>`
                );
            })
            .always(() => {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-eye"></i>');
            });
    });

    // ── Delete import batch ────────────────────────────────────────────────
    $(document).on('click', '.delete-import-btn', function () {
        const id  = $(this).data('id');
        const url = dairyFileImportDestroyUrl.replace(':id', id);

        Swal.fire({
            title: 'Delete Import Batch?',
            text: 'All items in this batch will be permanently deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel',
        }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({
                url,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success(res) {
                    showToast('success', res.message ?? 'Deleted.');
                    mainTable.replaceData();
                },
                error(xhr) {
                    showToast('error', xhr.responseJSON?.message ?? 'Delete failed.');
                },
            });
        });
    });

    // ── Bulk delete ────────────────────────────────────────────────────────
    $('#bulk_delete_btn').on('click', function () {
        Swal.fire({
            title: 'Delete All Filtered Batches?',
            text: 'All import batches matching the current filter will be permanently deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete All',
            cancelButtonText: 'Cancel',
        }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: dairyFileRegisterBulkDeleteUrl,
                method: 'DELETE',
                data: currentFilter,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success(res) {
                    showToast('success', res.message ?? 'Deleted.');
                    mainTable.replaceData();
                },
                error(xhr) {
                    showToast('error', xhr.responseJSON?.message ?? 'Bulk delete failed.');
                },
            });
        });
    });

    // Destroy modal Tabulator when modal is hidden to avoid DOM conflicts on re-open
    $('#importDetailModal').on('hidden.bs.modal', function () {
        if (modalTable) {
            modalTable.destroy();
            modalTable = null;
        }
        $('#modal_detail_table').hide().empty();
        $('#modal_loading').show().html(
            `<i class="fa-solid fa-spinner fa-spin fa-2x mb-2 d-block"></i> Loading...`
        );
    });
});

// ── Build Tabulator inside the modal ──────────────────────────────────────
function buildModalTable(items) {
    if (modalTable) {
        modalTable.destroy();
        modalTable = null;
    }

    // Ensure the div is empty and visible before Tabulator mounts
    $('#modal_detail_table').empty().show();

    modalTable = new Tabulator('#modal_detail_table', {
        data: items,
        layout: 'fitColumns',
        height: '450px',
        placeholder: '<div class="text-center py-4 text-muted">No items found</div>',

        columns: [
            {
                title: '#',
                formatter: 'rownum',
                hozAlign: 'center',
                headerHozAlign: 'center',
                vertAlign: 'middle',
                headerSort: false,
                width: 50,
            },
            {
                title: 'Billing Date',
                field: 'billing_date',
                hozAlign: 'center',
                headerHozAlign: 'center',
                vertAlign: 'middle',
                headerSort: false,
                width: 120,
            },
            {
                title: 'Customer P.O. No',
                field: 'customer_po_no',
                vertAlign: 'middle',
                headerSort: false,
                width: 150,
            },
            {
                title: 'Sold to Party',
                field: 'sold_to_party',
                vertAlign: 'middle',
                headerSort: false,
                width: 130,
            },
            {
                title: 'Destination',
                field: 'destination',
                vertAlign: 'middle',
                headerSort: false,
                minWidth: 150,
            },
            {
                title: 'Qty',
                field: 'quantity',
                hozAlign: 'right',
                headerHozAlign: 'right',
                vertAlign: 'middle',
                headerSort: false,
                width: 80,
                bottomCalc: 'sum',
                bottomCalcFormatter(cell) {
                    return `<strong>${cell.getValue()}</strong>`;
                },
            },
            {
                title: 'Vehicle No',
                field: 'vehicle_no',
                hozAlign: 'center',
                headerHozAlign: 'center',
                vertAlign: 'middle',
                headerSort: false,
                width: 130,
            },
            {
                title: 'Zone',
                field: 'zone',
                vertAlign: 'middle',
                headerSort: false,
                minWidth: 110,
            },
            {
                title: 'Status',
                field: 'is_used',
                hozAlign: 'center',
                headerHozAlign: 'center',
                vertAlign: 'middle',
                headerSort: false,
                width: 110,
                formatter(cell) {
                    return cell.getValue()
                        ? '<span class="badge bg-success-lt text-success fw-semibold">Used</span>'
                        : '<span class="badge bg-warning-lt text-warning fw-semibold">Not Used</span>';
                },
            },
        ],
    });
}

// ===========================================================
// UTILITY FUNCTIONS
// ===========================================================

/** Set field value safely (Select2 / normal input) */
function setFieldValue(id, value) {
  const $el = $(`#${id}`);
  if (!$el.length) return;

  if ($el.hasClass("select2-hidden-accessible")) {
    $el.val(value).trigger("change");
  } else {
    $el.val(value);
  }
}

/** Read field value safely */
function getFieldValue(id) {
  const $el = $(`#${id}`);
  if (!$el.length) return "";

  if ($el.hasClass("select2-hidden-accessible")) {
    return $el.val() || "";
  }
  return $el.val()?.trim() || "";
}