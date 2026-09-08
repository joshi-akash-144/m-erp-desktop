@php
    use Illuminate\Support\Str;
    $totalSalesQty = 0;
    $totalPurchaseQty = 0;
@endphp
<table>
    <tr>
        <td colspan="{{ count($headings) }}">{{ Str::upper($companyName) }}</td>
    </tr>
    <tr>
        <td colspan="{{ count($headings) }}">GSTIN : {{ $gstNumber }}</td>
    </tr>
    <tr>
        <td colspan="{{ count($headings) }}">{{ $reportTitle }}</td>
    </tr>
    <tr>
        <td colspan="{{ count($headings) }}">{{ $datePeriod }}</td>
    </tr>
    <tr>
        @foreach($headings as $heading)
            <td style="font-weight: bold;">{{ $heading }}</td>
        @endforeach
    </tr>
    @foreach($rows as $row)
        @php
            $totalSalesQty += (float)($row['sales_quantity'] ?? 0);
            $totalPurchaseQty += (float)($row['purchase_quantity'] ?? 0);
        @endphp
        <tr>
            @foreach($row as $cell)
                <td>{{ $cell }}</td>
            @endforeach
        </tr>
    @endforeach
    <tr>
        {{-- <td colspan="7" style="font-weight: bold; text-align: right;">Total</td>
        <td style="font-weight: bold;">{{ $totalSalesQty }}</td>
        <td colspan="6"></td>
        <td style="font-weight: bold;">{{ $totalPurchaseQty }}</td>
        <td></td> --}}
    </tr>
</table>