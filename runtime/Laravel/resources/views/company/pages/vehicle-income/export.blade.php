@php 
    use Illuminate\Support\Str; 
@endphp

<table>
    <tr>
        <td colspan="{{ count($columns) }}">{{ Str::upper($companyName) }}</td>
    </tr>
    <tr>
        <td colspan="{{ count($columns) }}">GSTIN: {{ $gstNumber }}</td>
    </tr>
    <tr>
        <td colspan="{{ count($columns) }}">{{ $reportTitle }}</td>
    </tr>
    @if($datePeriod || $reportType)
        <tr>
            <td colspan="4">{{ $reportType }}</td>
            <td colspan="{{ count($columns) - 4 }}">Date:- {{ $datePeriod }}</td>
        </tr>
    @endif
    <tr>
        @foreach($columns as $column)
            <td>{{ $column['title'] }}</td>
        @endforeach
    </tr>
    @php
        $totals = [];
        foreach ($columns as $col) {
            if ($col['field'] === 'amount') {
                $totals[$col['field']] = 0;
            }
        }
    @endphp
    @foreach($reportData as $row)
        <tr>
            @foreach($columns as $column)
                @php
                    $field = $column['field'];
                    $val = $row[$field] ?? '';
                    if ($field === 'amount') {
                        $totals[$field] += (float) $val;
                    }
                @endphp
                <td>{{ $val }}</td>
            @endforeach
        </tr>
    @endforeach
    <tr>
        @foreach($columns as $column)
            @php $field = $column['field']; @endphp
            @if($field === 'amount')
                <td>{{ $totals[$field] }}</td>
            @elseif($field === 'type')
                <td>Total</td>
            @else
                <td></td>
            @endif
        @endforeach
    </tr>
</table>
