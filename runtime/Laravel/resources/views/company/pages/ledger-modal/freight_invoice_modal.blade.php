<!-- ================================
     FREIGHT INVOICE VIEW MODAL
================================ -->
<style>
    #freight_invoice_modal .table-vcenter td, #freight_invoice_modal .table-vcenter th {
        vertical-align: middle;
        padding: 4px 8px;
    }
    #freight_invoice_modal .grid-label {
        color: #555;
        font-weight: 700;
        font-size: 0.85rem;
    }
    #freight_invoice_modal .grid-value {
        color: #000000d0;
        font-weight: 800;
        font-size: 0.85rem;
    }
    #freight_invoice_modal .table-header-grey th,
    #freight_invoice_modal .table-header-grey td {
        background-color: #d1d5db !important;
        color: #333 !important;
        font-size: 0.8rem;
        font-weight: 700;
        border-color: #bbb;
    }
    #freight_invoice_modal .table-bordered td, #freight_invoice_modal .table-bordered th {
        border: 1px solid #ccc;
    }
    #freight_invoice_modal .bg-grey-cell {
        background-color: #e5e7eb !important;
    }
</style>

<div class="modal fade" id="freight_invoice_modal" tabindex="-1" aria-labelledby="freight_invoice_modal_label"
    data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 1000px;">
        <div class="modal-content border-0 shadow-lg rounded-1">
            
            <!-- Modal header -->
            <div class="modal-header bg-light pb-2 pt-3 px-4">
                <h3 class="modal-title text-primary fw-bold m-0" style="font-size: 1.3rem;">
                    <i class="fa-solid fa-file-invoice me-2"></i>Freight Invoice - View
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body bg-white pt-0 px-4 pb-4">
                <form id="freight_invoice_form" autocomplete="off" data-form-mode="{{ $formMode ?? 'view' }}">
                    <!-- Top Details Grid -->
                    <div class="row gx-3 gy-2 mb-3 mt-2">
                        <div class="col-2">
                            <span class="grid-label">Bill No :</span>
                            <span class="grid-value ms-1">{{ $freight->invoice_number ?? '' }}</span>
                        </div>
                        <div class="col-3">
                            <span class="grid-label">Bill Date :</span>
                            <span class="grid-value ms-1">{{ !empty($freight->invoice_date) ? \Carbon\Carbon::parse($freight->invoice_date)->format('d / m / Y') : '' }}</span>
                        </div>
                        <div class="col-6">
                            <span class="grid-label">To Bill :</span>
                            <span class="grid-value ms-1 text-uppercase">{{ $freight->account->name ?? '' }}</span>
                        </div>
                        <div class="col-2">
                            <span class="grid-label">City :</span>
                            <span class="grid-value ms-1 text-uppercase">{{ $freight->account->city ?? '' }}</span>
                        </div>
                        <div class="col-3">
                            <span class="grid-label">From Date :</span>
                            <span class="grid-value ms-1">{{ !empty($freight->from_date) ? \Carbon\Carbon::parse($freight->from_date)->format('d / m / Y') : '' }}</span>
                        </div>
                        <div class="col-3">
                            <span class="grid-label">To Date :</span>
                            <span class="grid-value ms-1">{{ !empty($freight->to_date) ? \Carbon\Carbon::parse($freight->to_date)->format('d / m / Y') : '' }}</span>
                        </div>
                    </div>

                    <div class="row gx-4">
                        <div class="col-12">
                            <!-- Item Details Table -->
                            <div class="table-responsive border mb-3" style="max-height: 350px;">
                                <table class="table table-sm table-bordered table-vcenter text-nowrap mb-0" style="font-size: 0.85rem;">
                                    <thead class="table-header-grey" style="position: sticky; top: 0; z-index: 2;">
                                        <tr>
                                            <th class="text-center" width="50">No.</th>
                                            <th>Item</th>
                                            <th>Zone</th>
                                            <th class="text-end">Quantity</th>
                                            <th class="text-end">Rate</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(isset($freight) && $freight->items)
                                            @foreach($freight->items as $index => $item)
                                                <tr>
                                                    <td class="text-center fw-bold text-dark">{{ $index + 1 }}</td>
                                                    <td class="fw-bold text-dark">{{ $item->item->name ?? '' }}</td>
                                                    <td class="fw-bold text-dark">{{ $item->zone->name ?? '' }}</td>
                                                    <td class="text-end fw-bold text-dark">{{ !empty($item->quantity) ? formatIndianNumber($item->quantity, 3) : '' }}</td>
                                                    <td class="text-end fw-bold text-dark">{{ !empty($item->rate) ? formatIndianNumber($item->rate, 2) : '' }}</td>
                                                    <td class="text-end fw-bold text-dark">{{ !empty($item->amount) ? formatIndianNumber($item->amount, 2) : '' }}</td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                    <tfoot class="table-header-grey" style="position: sticky; bottom: 0; z-index: 2; box-shadow: inset 0 1px 0 #ccc, inset 0 -1px 0 #ccc;">
                                        <tr>
                                            <td colspan="3" class="text-end fw-bold text-dark pe-2">Total</td>
                                            <td class="text-end fw-bold text-dark" style="font-size: 14px !important;">{{ formatIndianNumber($freight->items->sum('quantity') ?? 0, 3) }}</td>
                                            <td></td>
                                            <td class="text-end fw-bold text-danger" style="font-size: 14px !important;">{{ !empty($freight->total_amount) ? formatIndianNumber($freight->total_amount, 2) : '' }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-primary fw-bold mb-1" style="font-size: 0.85rem;">Narration</label>
                            <textarea class="form-control text-dark bg-white border-1 border-secondary-subtle" rows="2" disabled style="resize: none; font-size: 0.750rem !important;">{{ $freight->remarks ?? '' }}</textarea>
                            
                            <div class="mt-2 row">
                                @if(isset($freight->creator->name))
                                <div class="col-6 mb-1">
                                    <span class="fw-bold text-dark" style="font-size: 0.85rem;"><i class="fa-solid fa-user me-1 text-secondary"></i>Created By: {{ $freight->creator->name }}</span>
                                </div>
                                @endif
                                @if(isset($freight->updater->name))
                                <div class="col-6">
                                    <span class="fw-bold text-dark" style="font-size: 0.85rem;"><i class="fa-solid fa-user-pen me-1 text-secondary"></i>Updated By: {{ $freight->updater->name }}</span>
                                </div>
                                @endif
                            </div>
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