<style>
    .custom-bill-table {
        border-collapse: collapse;
        width: 100%;
    }
    .custom-bill-table th, .custom-bill-table td {
        border: 1px solid #444 !important;
        color: #000 !important;
        padding: 4px 6px !important;
    }
    .custom-bill-table th {
        font-weight: bold;
        background-color: #f8f9fa !important;
    }
</style>
<div class="modal fade master-modal" id="billDetailModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-fullscreen modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header master-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="master-badge bg-primary-lt rounded fs-2">
                        <i class="fa-solid fa-file-invoice text-primary"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0 font-monospace">Bill Detail</h5>
                        <small class="badge bg-teal text-teal-fg">Payment Advice Data</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="table-responsive">
                    <table class="table custom-bill-table table-sm m-0" id="billDetailTable" style="font-size: 0.85rem;">
                        <thead class="text-nowrap text-center">
                            <tr>
                                <th class="text-center">Srno</th>
                                <th class="text-center">Refno</th>
                                <th class="text-center">File</th>
                                <th class="text-center">Date</th>
                                <th class="text-center">Show Date</th>
                                <th class="text-center">Pay Amt.</th>
                                <th class="text-center">CD Per.</th>
                                <th class="text-center">CD</th>
                                <th class="text-center">Balance</th>
                                <th class="text-center">Particular</th>
                                <th class="text-center">City</th>
                                <th class="text-center">Rebate</th>
                                <th class="text-center">Destination</th>
                                <th class="text-center">Product</th>
                                <th class="text-center">Rate</th>
                                <th class="text-center">Qty</th>
                                <th class="text-center">PQty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($voucherData && isset($voucherData['rows']) && count($voucherData['rows']) > 0)
                                @foreach($voucherData['rows'] as $index => $row)
                                    <tr class="text-nowrap text-center">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $row['bill_no'] ?? '' }}</td>
                                        <td>{{ $row['file_no'] ?? '' }}</td>
                                        <td>{{ format_date($row['date']) ?? '' }}</td>
                                        <td>{{ format_date($row['date']) ?? '' }}</td>
                                        <td class="fw-bold text-end">{{ isset($row['amount']) && is_numeric($row['amount']) ? formatIndianNumber($row['amount'], 2) : '0.00' }}</td>
                                        <td class="text-end">{{ isset($row['cd_per']) && is_numeric($row['cd_per']) ? formatIndianNumber($row['cd_per'], 2) : '0.00' }}</td>
                                        <td class="text-end">{{ isset($row['cd']) && is_numeric($row['cd']) ? formatIndianNumber($row['cd'], 2) : '0.00' }}</td>
                                        <td class="text-end">{{ isset($row['balance']) && is_numeric($row['balance']) ? formatIndianNumber($row['balance'], 2) : '0.00' }}</td>
                                        <td class="fw-bold text-start">{{ $row['particular'] ?? '' }}</td>
                                        <td class="fw-bold text-start">{{ $row['city'] ?? '' }}</td>
                                        <td class="text-end">{{ isset($row['rebate']) && is_numeric($row['rebate']) ? formatIndianNumber($row['rebate'], 2) : '0.00' }}</td>
                                        <td class="fw-bold text-start">{{ $row['destination'] ?? '' }}</td>
                                        <td class="text-start">{{ $row['product'] ?? '' }}</td>
                                        <td class="text-end">{{ isset($row['rate']) && is_numeric($row['rate']) ? formatIndianNumber($row['rate'], 2) : '0.00' }}</td>
                                        <td class="text-end">{{ isset($row['qty']) && is_numeric($row['qty']) ? formatIndianNumber($row['qty'], 3) : '0.000' }}</td>
                                        <td class="text-end">{{ isset($row['p_qty']) && is_numeric($row['p_qty']) ? formatIndianNumber($row['p_qty'], 3) : '0.000' }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="17" class="text-center text-muted py-4">No details found</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
