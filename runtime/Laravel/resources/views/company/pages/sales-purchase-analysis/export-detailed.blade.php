<table>
    <tr>
<td colspan="6" style="text-align: center;"><strong>{{ $company->name ?? '' }}</strong></td>
    </tr>
    <tr>
<td colspan="6" style="text-align: center;"><strong>Dairy PO Profit and Loss Analysis - {{ $poNumber }}</strong></td>
    </tr>
    <tr>
<td colspan="6" style="text-align: center;"><strong>Period: {{ $datePeriod }}</strong></td>
    </tr>
    <tr>
<td colspan="6"></td>
    </tr>

    <!-- Header Info Section -->
    <tr>
<td colspan="2" style="background-color: #f3f4f6;"><strong>Customer Account</strong></td>
        <td style="background-color: #f3f4f6;"><strong>Dairy PO Number</strong></td>
        <td style="background-color: #f3f4f6;"><strong>Item Name</strong></td>
        <td style="background-color: #f3f4f6;"><strong>Destination</strong></td>
<td style="background-color: #f3f4f6;"><strong>Sales Rate</strong></td>
    </tr>
    <tr>
<td colspan="2"><strong>{{ $parentRow->sales_party_name ?? '--' }}</strong></td>
        <td><strong>{{ $poNumber }}</strong></td>
        <td><strong>{{ $parentRow->item_name ?? '--' }}</strong></td>
        <td><strong>{{ $parentRow->destination_name ?? '--' }}</strong></td>
<td style="color: #2563eb;">
            <strong>{{ $parentRow && $parentRow->sales_rate ? number_format($parentRow->sales_rate, 2) : '0.00' }}</strong>
        </td>
    </tr>
@php
$sumPurchaseQty = 0;
$sumSalesQty = 0;
foreach($rows as $r) {
$sumPurchaseQty += floatval($r->purchase_qty ?? 0);
$sumSalesQty += floatval($r->sales_qty ?? 0);
}
$poQty = $parentRow && $parentRow->so_total_qty ? floatval($parentRow->so_total_qty) : 0;
$soReceivedQty = $parentRow && $parentRow->so_received_qty ? floatval($parentRow->so_received_qty) : 0;

$fulfillmentPct = ($poQty > 0) ? ($sumPurchaseQty / $poQty) * 100 : 0;
$fulfillmentStr = number_format($sumPurchaseQty, 2);
if ($fulfillmentPct > 0) {
$fulfillmentStr .= ' (' . number_format($fulfillmentPct, 1) . '%)';
}

$godownQty = max(0, $soReceivedQty - $sumPurchaseQty);
$billedFromPo = $soReceivedQty - $godownQty;
$remainingQty = max(0, $poQty - $soReceivedQty);
@endphp
    <tr>
        <td style="background-color: #f3f4f6;"><strong>PO Qty</strong></td>
<td style="background-color: #f3f4f6;"><strong>Purchased Qty</strong></td>
<td style="background-color: #f3f4f6;"><strong>Billed Qty (PO)</strong></td>
<td style="background-color: #f3f4f6;"><strong>Godown Qty</strong></td>
<td style="background-color: #f3f4f6;"><strong>Total Billed</strong></td>
<td style="background-color: #f3f4f6;"><strong>Remaining Qty</strong></td>
    </tr>
    <tr>
<td><strong>{{ $poQty > 0 ? number_format($poQty, 2) : '0.00' }}</strong></td>
<td style="color: #16a34a;"><strong>{{ $fulfillmentStr }}</strong></td>
<td style="color: #2563eb;"><strong>{{ number_format($billedFromPo, 2) }}</strong></td>
<td style="color: #d97706;"><strong>{{ number_format($godownQty, 2) }}</strong></td>
<td style="color: #2563eb;"><strong>{{ number_format($soReceivedQty, 2) }}</strong></td>
<td style="color: #dc2626;"><strong>{{ number_format($remainingQty, 2) }}</strong></td>
    </tr>
    <tr>
<td colspan="6"></td>
    </tr>

    <!-- Table Column Headers -->
    <tr>
<td colspan="2" style="background-color: #e2efda; border: 1px solid #000000;"><strong>Supplier Name</strong></td>
        <td colspan="2" style="background-color: #e2efda; border: 1px solid #000000;"><strong>Supplier PO No</strong></td>
        <td style="background-color: #e2efda; border: 1px solid #000000;"><strong>Purchase Rate</strong></td>
        <td style="background-color: #e2efda; border: 1px solid #000000; text-align: right;"><strong>Profit/Loss Percentage</strong></td>
    </tr>
    @foreach($rows as $row)
        @php
            $sr = floatval($row->sales_rate ?? 0);
            $pr = floatval($row->purchase_rate ?? 0);
            $diff = abs($sr - $pr);
            
            $percentageStr = "0.0%";
            $color = "#2563eb"; 

            if ($diff > 0.01) {
                $plPct = ($pr > 0) ? ((($sr - $pr) / $pr) * 100) : 0;
                $percentageStr = ($plPct > 0 ? '+' : '') . number_format($plPct, 1) . '%';
                $color = $plPct > 0 ? "#16a34a" : "#dc2626"; 
            }
        @endphp
        <tr>
<td colspan="2" style="border: 1px solid #000000;">{{ $row->purchase_party_name ?? '--' }}</td>
            <td colspan="2" style="border: 1px solid #000000;">{{ $row->po_number ?? '--' }}</td>
            <td style="border: 1px solid #000000;">{{ $row->purchase_rate ? number_format($row->purchase_rate, 2) : '0.00' }}</td>
            <td style="border: 1px solid #000000; text-align: right; color: {{ $color }};"><strong>{{ $percentageStr }}</strong></td>
        </tr>
    @endforeach

    <tr>
<td colspan="6"></td>
    </tr>

    <!-- Footer Summary Section -->
    <tr>
<td colspan="2" style="background-color: #f3f4f6;"><strong>Sales Rate</strong></td>
        <td colspan="2" style="background-color: #f3f4f6;"><strong>Avg. Purchase Rate</strong></td>
        <td style="background-color: #f3f4f6;"><strong>Rate Difference</strong></td>
        <td style="background-color: #f3f4f6; text-align: center;"><strong>Result</strong></td>
    </tr>
    <tr>
<td colspan="2"><strong>{{ number_format($avgSalesRate, 2) }}</strong></td>
        <td colspan="2"><strong>{{ number_format($avgPurchaseRate, 2) }}</strong></td>
        <td style="color: {{ $diffRate >= 0 ? '#16a34a' : '#dc2626' }};"><strong>{{ number_format(abs($diffRate), 2) }}</strong></td>
        <td style="text-align: center; color: #ffffff; background-color: {{ abs($diffRate) <= 0.01 ? '#2563eb' : ($diffRate > 0 ? '#16a34a' : '#dc2626') }};">
            <strong>{{ abs($diffRate) <= 0.01 ? 'EQUAL' : ($diffRate > 0 ? 'PROFIT' : 'LOSS') }}</strong>
        </td>
    </tr>

    <tr>
<td colspan="6"></td>
    </tr>

{{-- <tr>
        <td colspan="3" style="text-align: right;"><strong>TOTAL SALES:</strong></td>
        <td><strong>{{ number_format($totalSales, 2) }}</strong></td>
    </tr>
    <tr>
        <td colspan="3" style="text-align: right;"><strong>TOTAL PURCHASE:</strong></td>
        <td><strong>{{ number_format($totalPurchase, 2) }}</strong></td>
    </tr>
    <tr>
        <td colspan="3" style="text-align: right;"><strong>PROFIT/LOSS AMOUNT:</strong></td>
        <td style="color: {{ $diffAmount >= 0 ? '#16a34a' : '#dc2626' }};"><strong>{{ number_format(abs($diffAmount), 2) }}</strong></td>
    </tr>
    @php
        $overallPct = 0;
if ($avgPurchaseRate > 0) {
$overallPct = (($avgSalesRate - $avgPurchaseRate) / $avgPurchaseRate) * 100;
        }
        $overallPctStr = (abs($overallPct) <= 0.01) ? "0.0%" : (($overallPct > 0 ? '+' : '') . number_format($overallPct, 1) . '%');
        $overallPctColor = (abs($overallPct) <= 0.01) ? '#2563eb' : ($overallPct > 0 ? '#16a34a' : '#dc2626');
    @endphp
    <tr>
        <td colspan="3" style="text-align: right;"><strong>OVERALL PERCENTAGE:</strong></td>
        <td style="color: {{ $overallPctColor }};"><strong>{{ $overallPctStr }}</strong></td>
</tr> --}}
</table>
