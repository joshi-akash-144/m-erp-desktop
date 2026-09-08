<table>
    <thead>
        <tr>
            <td colspan="9" class="company-header">{{ $companyName }}</td>
        </tr>
        <tr>
            <td colspan="9" class="gst-header">GSTIN: {{ $gstNumber }}</td>
        </tr>
        <tr>
            <td colspan="9" class="gst-header">GSTR1</td>
        </tr>
        <tr>
            <td colspan="9" class="date-header">From {{ $datePeriod }}</td>
        </tr>
        <tr>
            <th>Section Name</th>
            <th>No. of Records</th>
            <th>Total Invoice Amt.</th>
            <th>Total Taxable Amt.</th>
            <th>Total Tax Liability</th>
            <th>Total CGST Amt.</th>
            <th>Total SGST Amt.</th>
            <th>Total IGST Amt.</th>
            <th>Total CESS</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
            @if(isset($row['count']) && $row['count'] === ':')
                <tr>
                    <td class="text-start">{{ $row['section'] }}</td>
                    <td class="text-center">:</td>
                    <td class="text-end">{{ $row['invoice_value'] }}</td>
                    <td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>
            @elseif(isset($row['count']) && $row['count'] === '-' && $row['taxable_amount'] === '')
                <tr>
                    <td class="text-start">{{ $row['section'] }}</td>
                    <td class="text-center">-</td>
                    <td class="text-end">{{ $row['invoice_value'] }}</td>
                    <td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>
            @else
                <tr>
                    <td class="text-start">{{ $row['section'] }}</td>
                    <td class="text-center">{{ $row['count'] }}</td>
                    <td class="text-end">{{ $row['invoice_value'] }}</td>
                    <td class="text-end">{{ $row['taxable_amount'] }}</td>
                    <td class="text-end">{{ $row['total_tax'] }}</td>
                    <td class="text-end">{{ $row['cgst'] }}</td>
                    <td class="text-end">{{ $row['sgst'] }}</td>
                    <td class="text-end">{{ $row['igst'] }}</td>
                    <td class="text-end">{{ $row['cess'] }}</td>
                </tr>
            @endif
        @endforeach
    </tbody>
</table>
