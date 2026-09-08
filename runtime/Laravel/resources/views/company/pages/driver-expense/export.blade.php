<table>
    <thead>
        <tr>
            <th>{{ $companyName }}</th>
        </tr>
        <tr>
            <th>GSTIN: {{ $gstNumber }}</th>
        </tr>
        <tr>
            <th>{{ $reportTitle }}</th>
        </tr>
        @if($datePeriod)
            <tr>
                <th>{{ $datePeriod }}</th>
            </tr>
        @endif
        <tr>
            @foreach($columns as $column)
                <th>{{ $column['title'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @php
            $totals = [];
            foreach ($columns as $col) {
                if ($col['field'] !== 'vehicle_name') {
                    $totals[$col['field']] = 0;
                }
            }
        @endphp

        @foreach($reportData as $row)
            <tr>
                @foreach($columns as $column)
                    @php
                        $field = $column['field'];
                        $val = $row[$field] ?? 0;
                        if ($field !== 'vehicle_name') {
                            $totals[$field] += (float) $val;
                        }
                    @endphp
                    <td>{{ $val !== '' ? $val : 0 }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            @foreach($columns as $column)
                @php $field = $column['field']; @endphp
                @if($field === 'vehicle_name')
                    <td>Total</td>
                @else
                    <td>{{ $totals[$field] }}</td>
                @endif
            @endforeach
        </tr>
    </tfoot>
</table>
