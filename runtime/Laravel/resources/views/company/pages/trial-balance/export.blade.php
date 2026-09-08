
@php
    use Illuminate\Support\Str;
@endphp    
<table>
        <tr>
            <th colspan="{{ count($headings) }}" style="font-weight: bold; text-align: center;">{{  Str::upper($companyName) }}</th>
        </tr>
        <tr>
            <th colspan="{{ count($headings) }}" style="text-align: center;">GSTIN: {{ $gstNumber }}</th>
        </tr>
        <tr>
            <th colspan="{{ count($headings) }}" style="font-weight: bold; text-align: center;">{{ $reportTitle }}</th>
        </tr>
        <tr>
            <th colspan="{{ count($headings) }}" style="text-align: right;">
                @php
                    $dateLabel = '';
                    if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
                        $dateLabel = 'Date: ' . \Carbon\Carbon::parse($filters['from_date'])->format('d-m-Y') . ' to ' . \Carbon\Carbon::parse($filters['to_date'])->format('d-m-Y');
                    } elseif (!empty($filters['as_on_date'])) {
                        $dateLabel = 'As On Date: ' . \Carbon\Carbon::parse($filters['as_on_date'])->format('d-m-Y');
                    } elseif (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                        $dateLabel = 'Date: ' . \Carbon\Carbon::parse($filters['start_date'])->format('d-m-Y') . ' to ' . \Carbon\Carbon::parse($filters['end_date'])->format('d-m-Y');
                    }
                @endphp
                {{ $dateLabel }}
            </th>
        </tr>
        <tr>
            @foreach($headings as $heading)
                <th style="font-weight: bold;">{{ $heading }}</th>
            @endforeach
        </tr>
        @foreach($rows as $row)
            <tr>
                @foreach($row as $cell)
                    <td>{{ $cell }}</td>
                @endforeach
            </tr>
        @endforeach
        @if(isset($totalsRow))
            <tr>
                <td colspan="2" style="font-weight: bold; text-align: right;">Total</td>
                @foreach(array_slice($totalsRow, 2) as $value)
                    <td style="font-weight: bold; text-align: right;">{{ $value === null ? '' : number_format($value, 2) }}</td>
                @endforeach
            </tr>
            @if(isset($differenceRow) && array_sum(array_slice($differenceRow, 2)) > 0)
                <tr>
                    <td colspan="2" style="font-weight: bold; text-align: right;">Difference</td>
                    @foreach(array_slice($differenceRow, 2) as $value)
                        <td style="font-weight: bold; text-align: right;">{{ ($value ?? 0) > 0 ? number_format($value, 2) : '' }}</td>
                    @endforeach
                </tr>
            @endif
        @else
            <tr>
                <td colspan="2" style="font-weight: bold; text-align: right;">Total</td>
                <td style="font-weight: bold; text-align: right;">{{ number_format($totalDebit, 2) }}</td>
                <td style="font-weight: bold; text-align: right;">{{ number_format($totalCredit, 2) }}</td>
            </tr>
            @if(($differenceDebit ?? 0) > 0 || ($differenceCredit ?? 0) > 0)
            <tr>
                <td colspan="2" style="font-weight: bold; text-align: right;">Difference</td>
                <td style="font-weight: bold; text-align: right;">{{ ($differenceDebit ?? 0) > 0 ? number_format($differenceDebit, 2) : '' }}</td>
                <td style="font-weight: bold; text-align: right;">{{ ($differenceCredit ?? 0) > 0 ? number_format($differenceCredit, 2) : '' }}</td>
            </tr>
            @endif
        @endif
</table>
