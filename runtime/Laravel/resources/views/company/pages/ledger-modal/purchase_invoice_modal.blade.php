<style>
    #purchase_invoice_modal .table-vcenter td, #purchase_invoice_modal .table-vcenter th {
        vertical-align: middle;
        padding: 4px 8px;
    }
    #purchase_invoice_modal .grid-label {
        color: #555;
        font-weight: 700;
        font-size: 0.85rem;
    }
    #purchase_invoice_modal .grid-value {
        color: #000;
        font-weight: 800;
        font-size: 0.85rem;
    }
    #purchase_invoice_modal .table-header-grey th {
        background-color: #d1d5db !important;
        color: #333 !important;
        font-size: 0.8rem;
        font-weight: 700;
        border-color: #bbb;
    }
    #purchase_invoice_modal .table-bordered td, #purchase_invoice_modal .table-bordered th {
        border: 1px solid #ccc;
    }
    #purchase_invoice_modal .bg-grey-cell {
        background-color: #e5e7eb !important;
    }
</style>

<div class="modal fade" id="purchase_invoice_modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 1200px;">
        <div class="modal-content border-0 shadow-lg rounded-1">
            
            <div class="modal-header bg-light pb-2 pt-1 px-4">
                <h3 class="modal-title text-primary fw-bold m-0" style="font-size: 1rem;">Purchase Voucher Detail</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body bg-white pt-2 px-4 pb-4">
                <!-- Top Details Grid -->
                <div class="row gx-3 gy-2 mb-3">
                    <div class="col-2">
                        <span class="grid-label">Vouch. No :</span>
                        <span class="grid-value ms-1">{{ $invoice->invoice_serial ?? '' }}</span>
                    </div>
                    <div class="col-2">
                        <span class="grid-label">Date :</span>
                        <span class="grid-value ms-1">{{ !empty($invoice->invoice_date) ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d / m / Y') : '' }}</span>
                    </div>
                    <div class="col-2">
                        <span class="grid-label">GRN :</span>
                        <span class="grid-value ms-1">{{ $invoice->grn_serial ?? '' }}</span>
                    </div>

                    <div class="col-2">
                        <span class="grid-label">File No:</span>
                        <span class="grid-value ms-1">{{ $invoice->file_number ?? '' }}</span>
                    </div>

                    <div class="col-2">
                        <span class="grid-label">Sales Inv :</span>
                        <span class="grid-value ms-1">{{ $invoice->sales_invoice_serial ?? '' }}</span>
                    </div>

                    <div class="col-4">
                        <span class="grid-label">Supplier :</span>
                        <span class="grid-value ms-1 text-uppercase">{{ $invoice->account->name ?? '' }}</span>
                    </div>
                    <div class="col-2">
                        <span class="grid-label">City :</span>
                        <span class="grid-value ms-1 text-uppercase">{{ $invoice->account->city ?? '' }}</span>
                    </div>
                    <div class="col-2">
                        <span class="grid-label">Party Bill No:</span>
                        <span class="grid-value ms-1">{{ $invoice->reference_number ?? '' }}</span>
                    </div>
                    <div class="col-3">
                        @php
                            $statusLabel = '--';
                            $statusCls = 'bg-secondary-lt';
                            
                            if (isset($invoice->reference)) {
                                $ref = $invoice->reference;
                                if ($ref->is_closed) {
                                    $statusLabel = 'Fully Paid';
                                    $statusCls = 'bg-success-lt';
                                } elseif ($ref->pending_amount > 0 && $ref->settled_amount > 0) {
                                    $statusLabel = 'Partially Paid';
                                    $statusCls = 'bg-orange-lt';
                                } elseif ($ref->pending_amount < 0) {
                                    $statusLabel = 'Overpaid';
                                    $statusCls = 'bg-cyan-lt';
                                } else {
                                    $statusLabel = 'Unpaid';
                                    $statusCls = 'bg-danger-lt';
                                }
                            }
                        @endphp
                        <span class="grid-label ms-4">Payment Status:</span>
                        <span class="badge {{ $statusCls }} fw-semibold ms-1">{{ $statusLabel }}</span>
                    </div>
                    <div class="col-3">
                        <span class="grid-label">Broker:</span>
                        <span class="grid-value ms-1 text-uppercase">{{ $invoice->broker->name ?? '' }}</span>
                    </div>
                    <div class="col-3">
                        <span class="grid-label">Vehical No :</span>
                        <span class="grid-value ms-1 text-uppercase">{{ $invoice->vehicle_number ?? '' }}</span>
                    </div>
                    <div class="col-2">
                        <span class="grid-label">Bill Date:</span>
                        <span class="grid-value ms-1">{{ !empty($invoice->party_bill_date) ? \Carbon\Carbon::parse($invoice->party_bill_date)->format('d / m / Y') : '' }}</span>
                    </div>
                    <div class="col-3">
                        <span class="grid-label">Invoice Type:</span>
                        <span class="grid-value ms-1 text-uppercase">{{ $invoice->purchaseType->name ?? '' }}</span>
                    </div>
                </div>

                <!-- Item Details Table -->
                <div class="table-responsive border mb-3">
                    <table class="table table-sm table-bordered table-vcenter text-nowrap mb-0" style="font-size: 0.85rem;">
                        <thead class="table-header-grey">
                            <tr>
                                <th>Product</th>
                                <th>Unit</th>
                                <th>OrderNo</th>
                                <th>Destination</th>
                                <th>Condition</th>
                                <th class="text-end">CGST(%)</th>
                                <th class="text-end">SGST(%)</th>
                                <th class="text-end">IGST(%)</th>
                                <th class="text-end">Bags</th>
                                <th class="text-end">P.Qty</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Incl. Tax Rate</th>
                                <th class="text-end">Rate</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($invoice) && $invoice->details)
                                @foreach($invoice->details as $detail)
                                    <tr>
                                        <td class="fw-bold text-dark">{{ $detail->item->name ?? '' }}</td>
                                        <td class="fw-bold text-dark text-uppercase">{{ $detail->item->unit->name ?? '' }}</td>
                                        <td class="text-center fw-bold text-dark">{{ $detail->purchase_order_serial ?? '' }}</td>
                                        <td class="fw-bold text-dark">{{ $detail->destination->name ?? '' }}</td>
                                        <td class="fw-bold text-dark">{{ $detail->condition->name ?? '' }}</td>
                                        <td class="text-end fw-bold text-dark">{{ !empty($detail->cgst_rate) ? formatIndianNumber($detail->cgst_rate, 2) : '0.00' }}</td>
                                        <td class="text-end fw-bold text-dark">{{ !empty($detail->sgst_rate) ? formatIndianNumber($detail->sgst_rate, 2) : '0.00' }}</td>
                                        <td class="text-end fw-bold text-dark">{{ !empty($detail->igst_rate) ? formatIndianNumber($detail->igst_rate, 2) : '0.00' }}</td>
                                        <td class="text-end fw-bold text-dark">{{ $detail->bag_count ?? '' }}</td>
                                        <td class="text-end fw-bold text-dark">{{ !empty($detail->party_quantity) ? formatIndianNumber($detail->party_quantity, 3) : '' }}</td>
                                        <td class="text-end fw-bold text-dark">{{ !empty($detail->quantity) ? formatIndianNumber($detail->quantity, 3) : '' }}</td>
                                        <td class="text-end fw-bold text-dark">{{ !empty($detail->inclusive_rate) ? formatIndianNumber($detail->inclusive_rate, 2) : '' }}</td>
                                        <td class="text-end fw-bold text-dark">{{ !empty($detail->rate) ? formatIndianNumber($detail->rate, 2) : '' }}</td>
                                        <td class="text-end fw-bold text-dark">{{ !empty($detail->amount) ? formatIndianNumber($detail->amount, 2) : '' }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="row mb-3">
                    <div class="col-12 d-flex justify-content-center fw-bold text-secondary gap-5" style="font-size: 0.9rem;">
                        <div>Total Qty : <span class="ms-2 fw-bolder text-dark">{{ !empty($invoice->total_quantity) ? formatIndianNumber($invoice->total_quantity, 3) : '' }}</span></div>
                        <div>Total Amount : <span class="ms-2 fw-bolder text-dark">
                            @php
                                $baseTotal = 0;
                                if(isset($invoice) && $invoice->details) {
                                    foreach($invoice->details as $d) {
                                        $baseTotal += (float) ($d->amount ?? 0);
                                    }
                                }
                            @endphp
                            {{ formatIndianNumber($baseTotal, 2) }}
                        </span></div>
                    </div>
                </div>

                <!-- Bottom Sections -->
                <div class="row gx-4">
                    <div class="col-6">

                        <!-- Payment Detail -->
                        @if(isset($invoice->reference) && $invoice->reference->allocations->count() > 0)
                            <div>
                                <label class="form-label text-primary fw-bold mb-1" style="font-size: 0.85rem;">Payment Detail</label>
                                <div class="table-responsive border">
                                    <table class="table table-sm table-bordered table-vcenter text-nowrap mb-0" style="font-size: 0.85rem;">
                                        <thead class="table-header-grey">
                                            <tr>
                                                <th class="text-center">S.N</th>
                                                <th class="text-center">Payment Date</th>
                                                <th class="text-center">Voucher No.</th>
                                                <th class="text-center">Cheque No</th>
                                                <th class="text-end">Pay Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($invoice->reference->allocations as $index => $allocation)
                                                <tr>
                                                    <td class="text-center fw-bold text-dark">{{ $index + 1 }}</td>
                                                    <td class="text-center fw-bold text-dark">{{ $allocation->voucher ? \Carbon\Carbon::parse($allocation->voucher->voucher_date)->format('d / m / Y') : '--' }}</td>
                                                    <td class="text-center fw-bold text-dark">{{ $allocation->voucher->voucher_serial ?? '--' }}</td>
                                                    <td class="text-center fw-bold text-dark">{{ $allocation->voucher?->paymentVoucher?->payment?->cheque_number ?: '--' }}</td>
                                                    <td class="text-end fw-bold text-dark">{{ formatIndianNumber($allocation->amount, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                        <!-- Remark -->
                        <div class="mb-3">
                            <label class="form-label text-primary fw-bold mb-1" style="font-size: 0.85rem;">Remark</label>
                            <textarea class="form-control text-dark bg-white" rows="3" disabled style="font-size: 0.750rem !important; resize: none;">{{ $invoice->remarks ?? '' }}</textarea>
                        </div>
                        <div class="row mt-2">
                            @if(isset($invoice->creator->name))
                            <div class="col-6">
                                <span class="fw-bold text-dark" style="font-size: 0.85rem;"><i class="fa-solid fa-user me-1 text-secondary"></i>Created By: {{ $invoice->creator->name }}</span>
                            </div>
                            @endif
                            @if(isset($invoice->updater->name))
                            <div class="col-6">
                                <span class="fw-bold text-dark" style="font-size: 0.85rem;"><i class="fa-solid fa-user-pen me-1 text-secondary"></i>Updated By: {{ $invoice->updater->name }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="col-6">
                        <!-- Particulars Table -->
                        <div class="table-responsive border h-100">
                            <table class="table table-sm table-bordered table-vcenter text-nowrap mb-0" style="font-size: 0.85rem;">
                                <thead class="table-header-grey">
                                    <tr>        
                                        <th></th>
                                        <th class="text-center" width="40">No.</th>
                                        <th>Particuler</th>
                                        <th class="text-center" width="60">@</th>
                                        <th class="text-end" width="120">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $sundries = isset($invoice) && $invoice->billSundries ? $invoice->billSundries : collect([]);
                                    @endphp
                                    @foreach($sundries as $index => $bs)
                                        <tr>
                                            <td class="text-center fw-bold text-dark">
                                                @if(isset($bs->affect_net_total) && $bs->affect_net_total == 0)
                                                    *
                                                @endif
                                            </td>
                                            <td class="text-center fw-bold text-dark">{{ $index + 1 }}</td>
                                            <td class="fw-bold text-dark">{{ $bs->name ?? '' }}</td>
                                            <td class="text-center fw-bold text-dark bg-grey-cell">{{ !empty($bs->rate_percent) ? number_format($bs->rate_percent, 2) : '' }}</td>
                                            <td class="text-end fw-bold text-dark">{{ !empty($bs->amount) ? formatIndianNumber(abs($bs->amount), 2) : '' }}</td>
                                        </tr>
                                    @endforeach
                                    @if($sundries->isEmpty())
                                        <tr><td colspan="4" class="text-center text-muted">No Data</td></tr>
                                    @endif
                                </tbody>
                            </table>
                            
                            <div class="d-flex justify-content-between align-items-start mt-3 pe-3 mb-2">
                                <div>
                                    @if(
                                            $sundries->contains(function ($s) {
                                                return isset($s->affect_net_total) && $s->affect_net_total == 0;
                                            })
                                        )
                                        <span class="fw-bold text-dark" style="font-size: 0.7rem;">&nbsp;Note:&nbsp;* This amount is not include in net total.</span>
                                    @endif
                                </div>
                                <div class="d-flex flex-column align-items-end">
                                    <div>
                                        <span class="fw-bold text-dark me-3" style="font-size: 0.95rem;">Net Total :</span>
                                        <span class="fw-bolder text-danger" style="font-size: 1.2rem;">{{ !empty($invoice->net_amount) ? formatIndianNumber($invoice->net_amount, 2) : '0.00' }}</span>
                                    </div>
                                    @if(isset($invoice->grand_total) && isset($invoice->net_amount) && (float) $invoice->grand_total !== (float) $invoice->net_amount && $invoice->grand_total != 0)
                                    <div class="mb-1">
                                        <span class="fw-bold text-dark me-3" style="font-size: 0.95rem;">Grand Total :</span>
                                        <span class="fw-bolder text-primary" style="font-size: 1.1rem;">{{ formatIndianNumber($invoice->grand_total, 2) }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="modal-footer bg-light border-top-0 p-2 position-relative">
                <!-- Using absolute positioning for the OK button as per image bottom right corner layout -->
                <div class="w-100 text-end">
                    <button class="btn btn-primary px-4 py-1 fw-bold rounded-1" data-bs-dismiss="modal">OK</button>
                </div>
            </div>

        </div>
    </div>
</div>