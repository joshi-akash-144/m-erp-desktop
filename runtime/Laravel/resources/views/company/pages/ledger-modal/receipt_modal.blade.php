<style>
    table.table td {
        padding: 5px;
    }
</style>

<div class="modal fade" id="receipt_voucher_modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content shadow border-0">
            <!-- Header -->
            <div class="modal-header text-primary text-white">
                <h4 class="modal-title fw-bold">
                    <i class="fa-solid fa-receipt me-2"></i>
                    Receipt Voucher - View
                </h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <div class="module-form-section">

                    <div class="module-page-title">
                        <i class="fa-solid fa-file-pen me-2 text-primary"></i>
                        General Details
                    </div>
                    <!-- Voucher Date and Details Section -->
                    <div class="card border mb-1">
                        <div class="card-body p-2">
                            <div class="row">
                                <span class="fs-4 fw-bold text-primary">
                                    Voucher Date: <span id="view_voucher_date" class="text-dark">{{ \Carbon\Carbon::parse($voucher->voucher_date)->format('d-m-Y') }}</span>
                                </span>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm align-middle text-nowrap mb-0 mt-0"
                                        id="view_voucher_details_table">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center" style="width: 8%">Sr</th>
                                                <th class="">Particular</th>
                                                <th class="text-end" style="width: 20%">Debit</th>
                                                <th class="text-end" style="width: 20%">Credit</th>
                                            </tr>
                                        </thead>
                                        <tbody id="view_voucher_details_tbody">
                                            @php
                                                $totalDebit = 0;
                                                $totalCredit = 0;
                                                $customerName = '';
                                            @endphp
                                            @forelse($voucher->details as $index => $detail)
                                                @php
                                                    $debit = (float)($detail->debit ?? 0);
                                                    $credit = (float)($detail->credit ?? 0);
                                                    $totalDebit += $debit;
                                                    $totalCredit += $credit;
                                                    if ($detail->is_party_account) {
                                                        $customerName = $detail->account_name;
                                                    }
                                                @endphp
                                                <tr>
                                                    <td class="text-center font-monospace">{{ $index + 1 }}</td>
                                                    <td>{{ $detail->account_name ?? '' }}</td>
                                                    <td class="text-end font-monospace">{{ $debit > 0 ? formatIndianNumber($debit) : '' }}</td>
                                                    <td class="text-end font-monospace">{{ $credit > 0 ? formatIndianNumber($credit) : '' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-3">No details available for this voucher.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot class="bg-light">
                                            <tr class="fw-bold">
                                                <td colspan="2" class="text-end">Total</td>
                                                <td id="view_total_debit" class="text-end font-monospace">{{ formatIndianNumber($totalDebit) }}</td>
                                                <td id="view_total_credit" class="text-end font-monospace">{{ formatIndianNumber($totalCredit) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="module-page-footer mt-0">
                        <span class="fw-bold">Narration:</span> <span id="view_narration" class="text-dark">{{ $voucher->narration ?? '--' }}</span>
                    </div>
                    @php $secNarration = $voucher->secondary_narration ?? ''; @endphp
                    <div class="module-page-footer mt-0" id="view_secondary_narration_row" style="{{ $secNarration ? '' : 'display:none;' }}">
                        <span class="fw-bold">Cheque:</span> <span id="view_secondary_narration" class="bg-white text-dark p-1">{{ $secNarration ?: '--' }}</span>
                    </div>
                    <div class="module-page-footer mt-0"><span class="fw-bold">Created by:</span> {{ $voucher->creator->name ?? '--' }}</div>
                    <div class="module-page-footer mt-0"><span class="fw-bold">Updated by:</span> {{ $voucher->updater->name ?? '--' }}</div>
                </div>
                <!-- Bill Break-Up Section -->
                @php
                    // Fallback for customer name if not found via is_party_account
                    if (!$customerName && $voucher->details && count($voucher->details) > 0) {
                        $firstCredit = $voucher->details->first(fn($d) => ((float)$d->credit) > 0);
                        if ($firstCredit) {
                            $customerName = $firstCredit->account_name;
                        }
                    }
                    
                    $hasBreakUp = false;
                    foreach($voucher->details as $detail) {
                        if (isset($detail->refs) && count($detail->refs) > 0) {
                            $hasBreakUp = true;
                            break;
                        }
                    }
                @endphp
                <div class="module-form-section overflow-x-auto" id="view_bill_break_up_section" style="{{ $hasBreakUp ? '' : 'display:none;' }}">
                    <div class="module-page-title">
                        <i class="fa-solid fa-file-invoice-dollar me-2 text-primary"></i>
                        Bill Break-Up
                    </div>
                    <div class="card border mb-1">
                        <div class="card-body p-1">
                            <div class="mb-1">
                                <span class="fs-4 fw-semibold text-secondary">
                                    <i class="fa-solid fa-user-tie me-1 text-secondary"></i>
                                    Customer Name: <span id="view_customer_name" class="text-dark fw-bold">{{ $customerName ?: '--' }}</span>
                                </span>
                            </div>

                            <!-- Bill Break-Up Table -->
                            <div class="table-responsive" style="max-height: 290px; overflow-y: auto;">
                                <table class="table table-bordered table-sm align-middle text-nowrap mb-0 mt-0"
                                    id="view_bill_break_up_table">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th class="text-center">Sr.</th>
                                            <th class="">Method</th>
                                            <th class="">Po Num.</th>
                                            <th class="">Ref No.</th>
                                            <th class="">Ref Date</th>
                                            <th class="">Delivery Date</th>
                                            <th class="text-center">Dr/Cr</th>
                                            <th class="">Product</th>
                                            <th class="">Destination</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody id="view_bill_break_up_tbody">
                                        @php $breakUpIndex = 1; @endphp
                                        @foreach($voucher->details as $detail)
                                            @if(isset($detail->refs) && count($detail->refs) > 0)
                                                @foreach($detail->refs as $ref)
                                                    @php
                                                        $method = $ref['method'] === 'new_ref' ? 'New Ref' : 'Agst Ref';
                                                        $refAmount = (float)($ref['ref_amount'] ?? 0);
                                                        $drCr = $ref['transaction_type'] ?? (((float)$detail->debit > 0) ? 'Dr' : 'Cr');
                                                        $qtyVal = isset($ref['qty']) && $ref['qty'] !== '' ? (float)$ref['qty'] : null;
                                                    @endphp
                                                    <tr>
                                                        <td class="text-center font-monospace">{{ $breakUpIndex++ }}</td>
                                                        <td>{{ $method }}</td>
                                                        <td>{{ $ref['po_number'] ?? '' }}</td>
                                                        <td>{{ $ref['ref_number'] ?? '' }}</td>
                                                        <td>{{ !empty($ref['ref_date']) ? \Carbon\Carbon::parse($ref['ref_date'])->format('d-m-Y') : '' }}</td>
                                                        <td>{{ !empty($ref['delivery_date']) ? \Carbon\Carbon::parse($ref['delivery_date'])->format('d-m-Y') : '' }}</td>
                                                        <td class="text-center">{{ $drCr }}</td>
                                                        <td>{{ $ref['product'] ?? '' }}</td>
                                                        <td>{{ $ref['destination'] ?? '' }}</td>
                                                        <td class="text-end font-monospace">{{ function_exists('formatQty') ? formatQty($qtyVal ?? 0, 3) : number_format($qtyVal ?? 0, 3) }}</td>
                                                        <td class="text-end font-monospace">{{ formatIndianNumber($refAmount) }}</td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Footer -->
            <div class="modal-footer">
                <button class="btn btn-secondary waves-effect" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>
</div>
</div>