@php
    use Illuminate\Support\Str;
    $totalBalance = 0;
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
            <td>{{ $heading }}</td>
        @endforeach
    </tr>
    @foreach($rows as $row)
        @php
            $totalBalance += (float)($row['balance_amt'] ?? 0);
        @endphp
        <tr>
            @foreach($row as $cell)
                <td>{{ $cell }}</td>
            @endforeach
        </tr>
    @endforeach
    <tr>
        <td colspan="7"></td>
        <td style="font-weight: bold; text-align: right;">Total</td>
        <td style="font-weight: bold;">{{ $totalBalance }}</td>
        <td colspan="2"></td>
    </tr>
</table>