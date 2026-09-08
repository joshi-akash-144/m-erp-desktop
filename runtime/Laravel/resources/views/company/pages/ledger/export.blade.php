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
    @if(isset($account))
    <tr>
        <td colspan="{{ count($headings)/2 }}">Ledger Account: {{ $account }}</td>
    @endif
    @if(isset($filters['start_date']))

        <td colspan="{{ round(count($headings)/2) }}" style="text-align: right;">
            Date: 
            {{ !empty($filters['start_date']) ? format_date($filters['start_date']) : '' }}
            to
            {{ !empty($filters['end_date']) ? format_date($filters['end_date']) : '' }}
        </td>
    </tr>
    @endif
    <tr>
        <td colspan="{{ count($headings) }}" style="text-align: right;">
            <strong>Opening Balance: {{formatIndianNumber($openingStock ?? 0) }}</strong>
        </td>
    </tr>
    <tr>
        @foreach($headings as $heading)
            <td><strong>{{ $heading }}</strong></td>
        @endforeach
    </tr>
    @foreach($rows as $row)
        <tr>
            @foreach($row as $cell)
                <td>{!! nl2br(e($cell)) !!}</td>
            @endforeach
            
        </tr>
    @endforeach
    <tr></tr>
    <tr>
        <td colspan="{{ count($headings) - 3 }}" style="text-align: right;"> <strong>Totals</strong> </td>
        <td style="text-align: right;"><strong>{{ formatIndianNumber($totalDebit ?? 0) }}</strong></td>
        <td style="text-align: right;"><strong>{{ formatIndianNumber($totalCredit ?? 0) }}</strong></td>
    </tr>    
    <tr>
        <td colspan="{{ count($headings) }}" style="text-align: right;">
            <strong>Closing Balance: {{ formatIndianNumber($closingStock ?? 0) }} {{ ($closingStock ?? 0) >= 0 ? 'Dr' : 'Cr' }}</strong>
        </td>
    </tr>
</table>