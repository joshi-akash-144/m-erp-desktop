<style>
    #freight_modal .table-vcenter td, #freight_modal .table-vcenter th {
        vertical-align: middle;
        padding: 4px 8px;
    }
    #freight_modal .grid-label {
        color: #555;
        font-weight: 700;
        font-size: 0.85rem;
    }
    #freight_modal .grid-value {
        color: #000;
        font-weight: 700;
        font-size: 0.85rem;
    }
    #freight_modal .table-header-grey th {
        background-color: #d1d5db !important;
        color: #333 !important;
        font-size: 0.8rem;
        font-weight: 700;
        border-color: #bbb;
    }
    #freight_modal .table-bordered td, #freight_modal .table-bordered th {
        border: 1px solid #ccc;
    }
    #freight_modal .bg-grey-cell {
        background-color: #e5e7eb !important;
    }
</style>

<div class="modal fade" id="freight_modal" tabindex="-1" aria-labelledby="freight_modal_label"
    data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 1400px;">
        <div class="modal-content border-0 shadow-lg rounded-1">
            
            <div class="modal-header bg-light pb-2 pt-3 px-4">
                <h3 class="modal-title text-primary fw-bold m-0" style="font-size: 1.3rem;">
                    <i class="fa-solid fa-truck me-2"></i>Freight - View
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body bg-white pt-0 px-4 pb-4">
                <form id="freight_form" autocomplete="off" data-form-mode="{{ $formMode ?? 'view' }}">

                    <!-- Top Details Grid -->
                    <div class="row gx-3 gy-2 mb-3 mt-2">
                        <div class="col-2">
                            <span class="grid-label">Voucher No :</span>
                            <span class="grid-value ms-1">{{ $freight->voucher->voucher_serial ?? '' }}</span>
                        </div>
                        <div class="col-4">
                            <span class="grid-label">Bill To :</span>
                            <span class="grid-value ms-1 text-uppercase">{{ $freight->account->name ?? '' }}</span>
                        </div>
                        <div class="col-2">
                            <span class="grid-label">Bill Number :</span>
                            <span class="grid-value ms-1">{{ ($freight->prefix ?? '') . ($freight->reference_number ?? '') }}</span>
                        </div>
                        <div class="col-2">
                            <span class="grid-label">Bill Date :</span>
                            <span class="grid-value ms-1">{{ !empty($freight->invoice_date) ? \Carbon\Carbon::parse($freight->invoice_date)->format('d / m / Y') : '' }}</span>
                        </div>
                        <div class="col-2">
                            <span class="grid-label">GRN No :</span>
                            <span class="grid-value ms-1">{{ $freight->grn_serial ?? '' }}</span>
                        </div>
                        <div class="col-2">
                            <span class="grid-label">LR Number :</span>
                            <span class="grid-value ms-1">{{ $freight->lr_number ?? '' }}</span>
                        </div>
                        <div class="col-2">
                            <span class="grid-label">Vehicle No :</span>
                            <span class="grid-value ms-1 text-uppercase">{{ $freight->vehicle->name ?? '' }}</span>
                        </div>

                        <div class="col-2">
                            <span class="grid-label">Item Name :</span>
                            <span class="grid-value ms-1 text-uppercase">{{ isset($freight->items[0]) ? ($freight->items[0]->item->name ?? '') : '' }}</span>
                        </div>
                        <div class="col-4">
                            <span class="grid-label">Consignor :</span>
                            <span class="grid-value ms-1 text-uppercase">{{ $freight->consignor->name ?? '' }}</span>
                        </div>
                        <div class="col-4">
                            <span class="grid-label">Consignee :</span>
                            <span class="grid-value ms-1 text-uppercase">{{ $freight->consignee->name ?? '' }}</span>
                        </div>

                        <div class="col-3">
                            <span class="grid-label">From Destination :</span>
                            <span class="grid-value ms-1 text-uppercase">{{ $freight->fromDestination->name ?? '' }}</span>
                        </div>
                        <div class="col-3">
                            <span class="grid-label">To Destination :</span>
                            <span class="grid-value ms-1 text-uppercase">{{ $freight->toDestination->name ?? '' }}</span>
                        </div>

                    </div>

                    <!-- Bottom Sections -->
                    <div class="row gx-4">
                        <div class="col-7">
                            <label class="form-label text-primary fw-bold mb-1" style="font-size: 0.85rem;">Weight Details</label>
                            <div class="table-responsive border mb-3">
                                <table class="table table-sm table-bordered table-vcenter text-nowrap mb-0" style="font-size: 0.85rem;">
                                    <tbody>
                                        @php
                                            $firstItem = $freight->items[0] ?? null;
                                            $bagTypes = config('constants.bag_types');
                                            $bagTypeName = $firstItem && isset($bagTypes[$firstItem->bag_type]) ? $bagTypes[$firstItem->bag_type] : '';
                                        @endphp
                                        <tr>
                                            <td class="fw-semibold text-secondary w-50" style="background-color: #f8f9fa;">
                                                <i class="fa-solid fa-bag-shopping me-1"></i> Bag Type
                                            </td>
                                            <td class="fw-bold text-dark w-25">{{ $bagTypeName }}</td>
                                            <td class="fw-bold text-dark w-25 text-end">{{ $firstItem->bag_count ?? '0' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-secondary" style="background-color: #f8f9fa;">
                                                <i class="fa-solid fa-weight-scale me-1"></i> Net Weight <small class="text-muted small">(Gross - Tare)</small>
                                            </td>
                                            <td class="fw-bold text-dark text-end" colspan="2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span>{{ !empty($firstItem->net_weight) ? formatIndianNumber($firstItem->net_weight, 3) : '0.000' }}</span>
                                                    <span class="text-muted"><i class="fas fa-tachometer-alt me-1 text-secondary"></i>KMs: <span class="text-dark">{{ !empty($firstItem->kms) ? formatIndianNumber($firstItem->kms, 2) : '0.00' }}</span></span>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-secondary" style="background-color: #f8f9fa;">
                                                <i class="fa-solid fa-scale-balanced me-1"></i> Freight Rate
                                            </td>
                                            <td class="fw-bold text-dark text-end" colspan="2">{{ !empty($firstItem->rate) ? formatIndianNumber($firstItem->rate, 2) : '0.00' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-secondary" style="background-color: #f8f9fa;">
                                                <i class="fa-solid fa-weight-hanging me-1"></i> Freight
                                            </td>
                                            <td class="fw-bold text-dark text-end text-danger" colspan="2" style="font-size: 1rem;">{{ !empty($freight->total_amount) ? formatIndianNumber($freight->total_amount, 2) : '0.00' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-5">
                            <label class="form-label text-primary fw-bold mb-1" style="font-size: 0.85rem;">Remark</label>
                            <textarea class="form-control text-dark bg-white border-1 border-secondary-subtle" rows="4" disabled style="resize: none; font-size: 0.75rem !important;">{{ $freight->remarks ?? '' }}</textarea>
                            
                        </div>
                        
                        <div class="mt-2 row">
                            @if(isset($freight->creator->name))
                            <div class="col-3">
                                <span class="fw-bold text-dark" style="font-size: 0.85rem;"><i class="fa-solid fa-user me-1 text-secondary"></i>Created By: {{ $freight->creator->name }}</span>
                            </div>
                            @endif
                            @if(isset($freight->updater->name))
                            <div class="col-3">
                                <span class="fw-bold text-dark" style="font-size: 0.85rem;"><i class="fa-solid fa-user-pen me-1 text-secondary"></i>Updated By: {{ $freight->updater->name }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Footer -->
            <div class="modal-footer bg-light border-top-0 p-2 position-relative">
                <div class="w-100 text-end">
                    <button class="btn btn-primary px-4 py-1 fw-bold rounded-1" data-bs-dismiss="modal">OK</button>
                </div>
            </div>

        </div>
    </div>
</div>
