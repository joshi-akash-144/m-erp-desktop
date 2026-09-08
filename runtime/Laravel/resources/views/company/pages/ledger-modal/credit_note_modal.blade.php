<style>
    #credit_note_modal .table-vcenter td,
    #credit_note_modal .table-vcenter th {
        vertical-align: middle;
        padding: 4px 8px;
    }

    #credit_note_modal .grid-label {
        color: #555;
        font-weight: 700;
        font-size: 0.85rem;
    }
#credit_note_modal .grid-value {
color: #000;
font-weight: 800;
font-size: 0.85rem;
}

#credit_note_modal .table-header-grey th {
background-color: #d1d5db !important;
color: #333 !important;
font-size: 0.8rem;
font-weight: 700;
border-color: #bbb;
}

#credit_note_modal .table-bordered td,
#credit_note_modal .table-bordered th {
border: 1px solid #ccc;
}

#credit_note_modal .bg-grey-cell {
background-color: #e5e7eb !important;
}
</style>
<div class="modal fade" id="credit_note_modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 1200px;">
        <div class="modal-content border-0 shadow-lg rounded-1">
            <div class="modal-header bg-light pb-2 pt-1 px-4">
                <h3 class="modal-title text-primary fw-bold m-0" style="font-size: 1.3rem;">Credit Note (Sales Return)
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

<div class="modal-body bg-white pt-1 px-4 pb-4">
    <!-- Top Details Grid -->
    <div class="row gx-3 gy-2 mb-3">
        <div class="col-2">
            <span class="grid-label">Voucher No :</span>
            <span class="grid-value ms-1">{{ $creditNote->credit_note_serial ?? '' }}</span>
        </div>
<div class="col-2">
    <span class="grid-label">Date :</span>
    <span
        class="grid-value ms-1">{{ !empty($creditNote->credit_note_date) ? \Carbon\Carbon::parse($creditNote->credit_note_date)->format('d / m / Y') : '' }}</span>
</div>
<div class="col-2">
    <span class="grid-label">Ref No :</span>
    <span class="grid-value ms-1">{{ $creditNote->reference_number ?? '' }}</span>
</div>
<div class="col-4">
    <span class="grid-label">Party Name :</span>
    <span class="grid-value ms-1 text-uppercase">{{ $creditNote->account->name ?? '' }}</span>
</div>
<div class="col-2">
    <span class="grid-label">Bill No :</span>
    <span class="grid-value ms-1 text-uppercase">{{ $creditNote->salesInvoice->invoice_serial ?? '' }}</span>
</div>
<div class="col-3">
    <span class="grid-label">Invoice Type :</span>
    <span class="grid-value ms-1 text-uppercase">{{ $creditNote->saleType->name ?? '' }}</span>
</div>
</div>

<!-- Item Details Table -->
<div class="table-responsive border mb-3">
    <table class="table table-sm table-bordered table-vcenter text-nowrap mb-0" style="font-size: 0.85rem;">
        <thead class="table-header-grey">
            <tr>
<th class="text-center" style="width: 50px;">S.N</th>
<th>Item Name</th>
<th class="text-center">Unit</th>
<th class="text-end">Qty</th>
<th class="text-end">Rate</th>
<th class="text-end">Amount</th>
</tr>
                                </thead>
                                <tbody>
@if(isset($creditNote) && $creditNote->details)
@foreach($creditNote->details as $index => $detail)
<tr>
    <td class="text-center fw-bold text-dark">{{ $index + 1 }}</td>
<td class="fw-bold text-dark">{{ $detail->item->name ?? '' }}</td>
<td class="text-center fw-bold text-dark text-uppercase">{{ $detail->item->unit->name ?? '' }}</td>
<td class="text-end fw-bold text-dark">{{ !empty($detail->quantity) ? formatIndianNumber($detail->quantity, 3) : '' }}
</td>
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
        <div>Total Qty : <span class="ms-2 fw-bolder text-dark">
                @php
                $totalQty = 0;
if(isset($creditNote) && $creditNote->details) {
foreach($creditNote->details as $d) {
$totalQty += (float) ($d->quantity ?? 0);
}
}
@endphp
{{ formatIndianNumber($totalQty, 3) }}
</span></div>
<div>Total Amount : <span class="ms-2 fw-bolder text-dark">
        @php
        $baseTotal = 0;
if(isset($creditNote) && $creditNote->details) {
foreach($creditNote->details as $d) {
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
    <div class="col-7">
        <!-- Remark & Ewaybill -->
        <div class="mb-3 row gx-2">
            <div class="col-12">
                <label class="form-label text-primary fw-bold mb-1" style="font-size: 0.85rem;">Remark</label>
                <textarea class="form-control text-dark bg-white" rows="3" disabled
                    style="resize: none; font-size: 0.750rem !important;">{{ $creditNote->remarks ?? '' }}</textarea>
            </div>
        </div>
        <div class="row mt-2">
            @if(isset($creditNote->creator->name))
            <div class="col-6">
                <span class="fw-bold text-dark" style="font-size: 0.85rem;"><i
                        class="fa-solid fa-user me-1 text-secondary"></i>Created By:
                    {{ $creditNote->creator->name }}</span>
            </div>
@endif
@if(isset($creditNote->updater->name))
<div class="col-6">
    <span class="fw-bold text-dark" style="font-size: 0.85rem;"><i
            class="fa-solid fa-user-pen me-1 text-secondary"></i>Updated By: {{ $creditNote->updater->name }}</span>
</div>
@endif
</div>
</div>

<div class="col-5">
    <!-- Particulars Table -->
    <div class="table-responsive border h-100">
        <table class="table table-sm table-bordered table-vcenter text-nowrap mb-0" style="font-size: 0.85rem;">
            <thead class="table-header-grey">
                <tr>
<th class="text-center" width="40">No.</th>
<th>Particuler</th>
<th class="text-center" width="60">@</th>
<th class="text-end" width="120">Amount</th>
</tr>
</thead>
<tbody>
@php
$sundries = isset($creditNote) && $creditNote->billSundries ? $creditNote->billSundries : collect([]);
@endphp
@foreach($sundries as $index => $bs)
<tr>
    <td class="text-center fw-bold text-dark">{{ $index + 1 }}</td>
<td class="fw-bold text-dark">{{ $bs->name ?? '' }}</td>
<td class="text-center fw-bold text-dark bg-grey-cell">
    {{ !empty($bs->rate_percent) ? number_format($bs->rate_percent, 2) : '' }}
</td>
<td class="text-end fw-bold text-dark">{{ !empty($bs->amount) ? formatIndianNumber(abs($bs->amount), 2) : '' }}</td>
</tr>
@endforeach
@if($sundries->isEmpty())
<tr>
<td colspan="4" class="text-center text-muted">No Data</td>
</tr>
@endif
</tbody>
</table>
<div class="d-flex justify-content-end align-items-center mt-3 pe-3 mb-2">
    <span class="fw-bold text-dark me-3" style="font-size: 0.95rem;">Net Total :</span>
    <span class="fw-bolder text-danger"
        style="font-size: 1.2rem;">{{ !empty($creditNote->net_amount) ? formatIndianNumber($creditNote->net_amount, 2) : '0.00' }}</span>
</div>
</div>
                            </div>
</div>

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
