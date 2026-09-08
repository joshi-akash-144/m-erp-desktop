<style>
    #vehicle_list_modal .modal-dialog {
        max-width: 73% !important;
    }

    /* Highlight selected row */
    .vehicle-row-active {
        background-color: var(--input-focus-bg) !important;
    }

    /* Hover */
    #vehicle_selection_table tbody tr:hover {
        background-color: #f3f4f6;
        cursor: pointer;
    }

    /* Reduce table cell padding */
    #vehicle_selection_table td,
    #vehicle_selection_table th {
        padding: 6px 10px;
        white-space: nowrap;
    }

    /* Sticky search bar */
    #vehicle_search {
        position: sticky;
        top: 0;
        z-index: 1020;
    }

    /* No data icon opacity */
    #vehicle_no_data_row i {
        opacity: 0.4;
    }

    /* Fix modal height */
    #vehicle_list_modal .modal-dialog {
        max-height: 90vh;
    }

    /* Scroll only table area */
    #vehicle_list_modal .modal-body {
        height: 60vh;
        overflow-y: auto;
        padding: 0;
    }

    /* Sticky table header */
    #vehicle_selection_table thead {
        position: sticky;
        top: 0;
        z-index: 5;
    }

    #vehicle_selection_table {
        table-layout: fixed;
        width: 100%;
    }

    #vehicle_selection_table th,
    #vehicle_selection_table td {
        overflow: hidden;
        text-overflow: ellipsis;
    }

    #vehicle_selection_table td {
        font-family: var(--tblr-font-monospace);
    }
</style>

<div class="modal fade" id="vehicle_list_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">

            <!-- HEADER -->
            <div class="modal-header border-bottom py-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary-lt p-2">
                        <i class="fa-solid fa-truck-moving text-primary fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0">Vehicle Selection</h5>
                        <small class="text-warning fw-semibold">
                            <i class="fa-solid fa-clock me-1"></i> Pending in Godown Cycle
                        </small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- SEARCH -->
            <div class="p-2 border-bottom bg-light">
                <input type="text" id="vehicle_search" class="form-control"
                    placeholder="Search Vehicle No / GRN / Product ...">
            </div>

            <!-- BODY -->
            <div class="modal-body p-0">
                <table class="table table-hover table-sm align-middle mb-0" id="vehicle_selection_table" autocomplete="off">
                    <thead class="table-primary sticky-top">
                        <tr>
                            <th style="width:130px" class="text-center">Vehicle Number</th>
                            <th style="width:110px" class="text-center">GRN Number</th>
                            <th style="width:250px">Product Name</th>
                            <th style="width:120px" class="text-end">Gross-Weight</th>
                            <th style="width:120px" class="text-end">Tare-Weight</th>
                            <th style="width:120px" class="text-end">Net-Weight</th>
                        </tr>
                    </thead>

                    <tbody id="vehicle_list_body">
                        @forelse ($vehicles as $vehicle)
                            @php
                                $firstDetail = $vehicle->details->first();
                            @endphp
                            <tr class="selectable-row" 
                                data-id="{{ $vehicle->id }}"
                                data-vehicle="{{ $vehicle->vehicle_number }}" 
                                data-grn-id="{{ $vehicle->grn_id }}"
                                data-grn-serial="{{ $vehicle->grn_serial }}" 
                                data-grn-type="{{ strtoupper($vehicle->gst_type ?? '') }}"
                                data-grn-date="{{ $vehicle->grn_date ? (is_string($vehicle->grn_date) ? $vehicle->grn_date : $vehicle->grn_date->format('d-m-Y')) : '' }}"
                                data-account-id="{{ $vehicle->account_id }}" 
                                data-broker-id="{{ $vehicle->broker_id }}"
                                data-item-id="{{ $firstDetail?->item_id ?? '' }}"
                                data-destination-id="{{ $firstDetail?->destination_id ?? '' }}"
                                data-party-destination="{{ $vehicle->party_destination_id ?? '' }}"
                                data-gross="{{ $vehicle->gross_weight ?? 0 }}" 
                                data-tare="{{ $vehicle->tare_weight ?? 0 }}"
                                data-bag-type="{{ $vehicle->bag_type }}"
                                data-bag-qty="{{ $vehicle->bag_count }}"
                                data-rate="{{ $vehicle->sub_total > 0 && $vehicle->total_quantity > 0 ? number_format($vehicle->sub_total / $vehicle->total_quantity, 3, '.', '') : '0.000' }}"
                                data-bill-no="{{ $vehicle->reference_number }}"
                                data-po-id="{{ $vehicle->details->first()?->purchase_order_id ?? ($vehicle->purchase_order_id ?? '') }}"
                                data-challan-weight="{{ $vehicle->challan_weight ?? 0 }}"
                                data-challan-bags="{{ $vehicle->challan_bags ?? '' }}"
                                data-remarks="{{ $vehicle->remarks }}"
                                data-transporter-id="{{ $vehicle->transporter_id }}"
                                data-lr-number="{{ $vehicle->lr_number }}"
                                data-challan-date="{{ $vehicle->challan_date ? (is_string($vehicle->challan_date) ? $vehicle->challan_date : $vehicle->challan_date->format('Y-m-d')) : '' }}"
                                data-godown-id="{{ $vehicle->godown_id ?? '' }}"
                                data-godown-unit-id="{{ $vehicle->godown_unit_id ?? '' }}"
                                data-in-date="{{ $vehicle->in_date ? (is_string($vehicle->in_date) ? $vehicle->in_date : $vehicle->in_date->format('Y-m-d')) : '' }}"
                                data-in-time="{{ $vehicle->in_time ?? '' }}"
                                data-out-date="{{ $vehicle->out_date ? (is_string($vehicle->out_date) ? $vehicle->out_date : $vehicle->out_date->format('Y-m-d')) : '' }}"
                                data-out-time="{{ $vehicle->out_time ?? '' }}">
                                <td class="text-center fw-bold text-primary">{{ $vehicle->vehicle_number }}</td>
                                <td class="text-center">{{ $vehicle->grn_serial ?: 'N/A' }}</td>
                                <td>{{ $firstDetail?->item->name ?? 'N/A' }}</td>
                                <td class="text-end">{{ number_format($vehicle->gross_weight ?? 0, 3) }}</td>
                                <td class="text-end">{{ number_format($vehicle->tare_weight ?? 0, 3) }}</td>
                                <td class="text-end text-success fw-bold">{{ number_format($vehicle->net_weight ?? 0, 3) }}</td>
                            </tr>
                        @empty
                            <tr id="vehicle_no_data_row" class="text-center">
                                <td colspan="6" class="py-4 text-muted">
                                    <div class="list-group-item text-center py-5 border border-dashed rounded-4 bg-primary-lt">
                                        <i class="fa-solid fa-truck-slash fa-3x text-secondary mb-3"></i>
                                        <h5 class="mb-1 text-secondary fw-bold">No Vehicle Found</h5>
                                        <p class="mb-0 text-muted">No pending Godown cycles available.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse 

                        <!-- NO DATA ROW (FOR SEARCH FILTERING) -->
                        <tr id="search_no_data_row" class="text-center d-none">
                            <td colspan="6" class="py-4 text-muted">
                                <div class="list-group-item text-center py-5 border border-dashed rounded-4 bg-primary-lt">
                                    <i class="fa-solid fa-truck-slash fa-3x text-secondary mb-3"></i>
                                    <h5 class="mb-1 text-secondary fw-bold">No Match Found</h5>
                                    <p class="mb-0 text-muted">Try adjusting your search keywords.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- FOOTER -->
            <div class="modal-footer py-2">
                <div class="d-none d-sm-block mb-1 text-muted small">
                    <i class="fas fa-keyboard me-1"></i>
                    Use <strong>↑↓</strong> arrows to navigate • <strong>Enter</strong> to select
                </div>
            </div>

        </div>
    </div>
</div>
