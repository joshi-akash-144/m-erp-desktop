<style>
    #pending_po_modal .modal-dialog {
        max-width: 73% !important;
    }

    /* Highlight selected row */
    .po-row-active {
        background-color: var(--input-focus-bg) !important;
    }

    /* Hover */
    #pending_po_table tbody tr:hover {
        background-color: #f3f4f6;
        cursor: pointer;
    }

    /* Reduce table cell padding */
    #pending_po_table td,
    #pending_po_table th {
        padding: 6px 10px;
        white-space: nowrap;
    }

    /* Sticky search bar */
    #pending_po_search {
        position: sticky;
        top: 0;
        z-index: 1020;
    }

    /* No data icon opacity */
    #po_no_data_row i {
        opacity: 0.4;
    }

    #pending_po_modal .modal-body {
        height: 450px;
        overflow-y: auto;
    }

    /* Fix modal height */
    #pending_po_modal .modal-dialog {
        max-height: 90vh;
    }

    /* Scroll only table area */
    #pending_po_modal .modal-body {
        height: 60vh;
        /* you can change to 55vh / 65vh as you prefer */
        overflow-y: auto;
        padding: 0;
    }

    /* Sticky table header */
    #pending_po_table thead {
        position: sticky;
        top: 0;
        z-index: 5;
    }

    #pending_po_table {
        table-layout: fixed;
        width: 100%;
    }

    #pending_po_table th,
    #pending_po_table td {
        overflow: hidden;
        text-overflow: ellipsis;
        
    }
    #pending_po_table td {
        font-family : var(--tblr-font-monospace);
    }
</style>


<div class="modal fade" id="pending_po_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">

            <!-- HEADER -->
            <div class="modal-header border-bottom py-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary-lt p-2">
                        <i class="fa-solid fa-file text-primary fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0">Purchase Orders</h5>
                        <small class="text-warning fw-semibold">
                            <i class="fa-solid fa-clock me-1"></i> Pending Orders
                        </small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- SEARCH -->
            <div class="p-2 border-bottom bg-light">
                <input type="text" id="pending_po_search" class="form-control"
                    placeholder="Search PO No. / Destination / Broker ...">
            </div>

            <!-- BODY -->
            <div class="modal-body p-0" style="max-height: 450px; overflow-y: auto;">
                <table class="table table-hover table-sm align-middle mb-0" id="pending_po_table" autocomplete="off">
                    <thead class="table-primary sticky-top">
                        <tr>
                            <th style="width:36px" class="text-center"></th>
                            <th style="width:130px" class="text-center"><i class="fas fa-hashtag me-1 text-secondary "></i> Order Serial</th>
                            <th style="width:110px" class="text-center"><i class="fas fa-file-invoice me-1 text-secondary"></i> Order No</th>
                            <th style="width:115px" class="text-center"><i class="fas fa-calendar-alt me-1 text-secondary"></i> Order Date</th>
                            <th style="width:110px" class="text-end"><i class="fas fa-cubes me-1 text-secondary"></i> Order Qty</th>
                            <th style="width:110px" class="text-end"><i class="fas fa-balance-scale-left me-1 text-secondary"></i> Bal. Qty</th>
                            <th style="width:140px"><i class="fas fa-map-marker-alt me-1 text-secondary"></i>Destination</th>
                            <th style="width:250px"><i class="fas fa-user-tie me-1 text-secondary"></i> Broker</th>
                            <th style="width:100px" class="text-center"><i class="fas fa-clock me-1 text-secondary"></i> Del Days</th>
                            <th style="width:115px" class="text-center"><i class="fas fa-calendar-check me-1 text-secondary"></i> Due Date
                            </th>
                        </tr>

                    </thead>

                    <tbody id="pending_po_table_body">
                        <!-- LOADER ROW -->
                        <tr id="po_loader_row" class="text-center d-none">
                            <td colspan="10" class="py-4">
                                <i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i>
                            </td>
                        </tr>

                        <!-- NO DATA ROW -->
                        <tr id="po_no_data_row" class="text-center d-none">
                            <td colspan="10" class="py-4 text-muted">
                                <div
                                    class="list-group-item text-center py-5 border border-dashed rounded-4 bg-primary-lt">
                                    <i class="fa-solid fa-file-circle-xmark fa-3x text-secondary mb-3"></i>

                                    <h5 class="mb-1 text-secondary fw-bold">No Purchase Order Found</h5>
                                    <p class="mb-0 text-muted">Try adjusting your filters or search keywords.</p>
                                </div>

                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

            <!-- FOOTER -->
            <div class="modal-footer py-2 d-flex justify-content-between align-items-center">
                <small class="text-muted d-none d-sm-block">
                    <i class="fas fa-info-circle me-1"></i> Select one purchase order and click <strong>Attach</strong>
                </small>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-success" id="btn_attach_po_confirm">
                        <i class="fa-solid fa-paperclip me-1"></i> Attach Selected
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
