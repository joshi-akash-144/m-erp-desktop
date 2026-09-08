@php
    use Illuminate\Support\Str;
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
    @if(!empty($dateLabel))
    <tr>
        <td colspan="{{ count($headings) }}">{{ $dateLabel }}</td>
    </tr>
    @endif
    <tr>
        @foreach($headings as $heading)
            <td>{{ $heading }}</td>
        @endforeach
    </tr>
    @foreach($rows as $row)
        <tr>
            @foreach($row as $cell)
                <td>{{ $cell }}</td>
            @endforeach
        </tr>
    @endforeach
    @if(isset($grandTotal) && !empty($grandTotal))
        <tr>
            <td><strong>Total:</strong></td>
            <td></td>
            <td><strong>{{ $grandTotal['total_quantity'] ?? 0 }}</strong></td>
            <td></td>
            <td><strong>{{ $grandTotal['total_amount'] ?? 0 }}</strong></td>
        </tr>
    @endif
</table>