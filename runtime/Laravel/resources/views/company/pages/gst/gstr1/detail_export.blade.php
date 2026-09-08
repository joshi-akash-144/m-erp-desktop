<table>
    <thead>
        <tr>
            <td colspan="{{ count($headings) }}" class="company-header">{{ $companyName }}</td>
        </tr>
        <tr>
            <td colspan="{{ count($headings) }}" class="gst-header">{{ $gstNumber ? 'GSTIN: ' . $gstNumber : '' }}</td>
        </tr>
        <tr>
            <td colspan="{{ count($headings) }}" class="title-header">{{ $reportTitle }}</td>
        </tr>
        <tr>
            <td colspan="{{ count($headings) }}" class="date-header">Period: {{ $datePeriod }}</td>
        </tr>
        <tr>
            @foreach($headings as $heading)
                <th>{{ $heading }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
            <tr>
                @foreach($row as $colIdx => $val)
                    @php
                        $align = $alignments[$colIdx] ?? 'L';
                        $alignClass = match($align) {
                            'R' => 'text-end',
                            'C' => 'text-center',
                            default => 'text-start',
                        };
                    @endphp
                    <td class="{{ $alignClass }}">
                        {{ is_numeric($val) && str_contains($headings[$colIdx], '%') ? $val : (is_numeric($val) && $align === 'R' ? number_format((float)$val, 2, '.', '') : $val) }}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            @php $lastHeadingIdx = count($headings) - 1; @endphp
            @for($c = 0; $c < count($headings); $c++)
                @if($c < $totalLabelCol - 1)
                    <td></td>
                @elseif($c == $totalLabelCol - 1)
                    <td class="text-end fw-bold">Total:</td>
                @else
                    @php
                        $h = strtolower($headings[$c]);
                        $totVal = null;
                        if (str_contains($h, 'taxable')) {
                            $totVal = $totals['taxable_amount'] ?? null;
                        } elseif (str_contains($h, 'cgst amt') || (str_contains($h, 'central tax') && !str_contains($h, '%'))) {
                            $totVal = $totals['cgst_amount'] ?? $totals['cgst'] ?? null;
                        } elseif (str_contains($h, 'sgst amt') || (str_contains($h, 'state/ut tax') && !str_contains($h, '%'))) {
                            $totVal = $totals['sgst_amount'] ?? $totals['sgst'] ?? null;
                        } elseif (str_contains($h, 'igst amt') || (str_contains($h, 'integrated tax') && !str_contains($h, '%'))) {
                            $totVal = $totals['igst_amount'] ?? $totals['igst'] ?? null;
                        } elseif (str_contains($h, 'cess')) {
                            $totVal = $totals['cess_amount'] ?? $totals['cess'] ?? null;
                        } elseif (str_contains($h, 'total tax')) {
                            $totVal = $totals['total_tax_amount'] ?? null;
                        } elseif (str_contains($h, 'invoice value') || str_contains($h, 'total value')) {
                            $totVal = $totals['invoice_value'] ?? null;
                        } elseif (str_contains($h, 'qty')) {
                            $totVal = $totals['qty'] ?? $totals['total_qty'] ?? null;
                        }
                    @endphp
                    <td class="text-end fw-bold">
                        {{ $totVal !== null ? number_format((float)$totVal, 2, '.', '') : '' }}
                    </td>
                @endif
            @endfor
        </tr>
    </tfoot>
</table>
