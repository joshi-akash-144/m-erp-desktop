@php use Illuminate\Support\Str; @endphp
<table>
    <tr>
        <th colspan="{{ count($headings) }}" style="font-weight:bold;text-align:center;">{{ Str::upper($companyName) }}</th>
    </tr>
    <tr>
        <th colspan="{{ count($headings) }}" style="text-align:center;">GSTIN: {{ $gstNumber }}</th>
    </tr>
    <tr>
        <th colspan="{{ count($headings) }}" style="font-weight:bold;text-align:center;">{{ $reportTitle }}</th>
    </tr>
    <tr>
        <th colspan="{{ intval(count($headings) / 2) }}">Account: {{ $account ?? '' }}</th>
        <th colspan="{{ count($headings) - intval(count($headings) / 2) }}" style="text-align:right;">
            Period: {{ $from_date ?? '' }} to {{ $to_date ?? '' }}
        </th>
    </tr>
    <tr>
        <th colspan="{{ count($headings) }}" style="text-align:right;">
            Opening Balance: {{ formatIndianNumber(abs($openingBalance ?? 0)) }} {{ ($openingBalance ?? 0) >= 0 ? 'Dr' : 'Cr' }}
        </th>
    </tr>
    <tr>
        @foreach($headings as $heading)
            <th style="font-weight:bold;">{{ $heading }}</th>
        @endforeach
    </tr>
    @foreach($rows as $row)
        <tr>
            @foreach($row as $cell)
                <td>{{ $cell }}</td>
            @endforeach
        </tr>
    @endforeach
    <tr>
        <td colspan="{{ count($headings) - 2 }}" style="font-weight:bold;text-align:right;">Total</td>
        <td style="font-weight:bold;text-align:right;">{{ formatIndianNumber($totalDebit  ?? 0) }}</td>
        <td style="font-weight:bold;text-align:right;">{{ formatIndianNumber($totalCredit ?? 0) }}</td>
    </tr>
    <tr>
        <td colspan="{{ count($headings) }}" style="font-weight:bold;text-align:right;">
            Closing Balance: {{ formatIndianNumber(abs($closingBalance ?? 0)) }} {{ ($closingBalance ?? 0) >= 0 ? 'Dr' : 'Cr' }}
        </td>
    </tr>
</table>
