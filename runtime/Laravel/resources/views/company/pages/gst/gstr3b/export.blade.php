<table>
    <tr>
        <td colspan="10" style="text-align: center; font-size: 14pt; font-weight: bold;">{{ $companyName }}</td>
    </tr>
    <tr>
        <td colspan="10" style="text-align: center; font-size: 12pt; font-weight: bold;">GSTIN: {{ $gstNumber }}</td>
    </tr>
    <tr>
        <td colspan="10" style="text-align: center; font-size: 12pt; font-weight: bold;">GSTR-3B Return Summary</td>
    </tr>
    <tr>
        <td colspan="10" style="text-align: center; font-size: 10pt; font-style: italic;">From {{ $datePeriod }}</td>
    </tr>
    <tr>
        <td colspan="10"></td>
    </tr>
    
    <!-- Table 3.1 -->
    <tr>
        <td colspan="10">3.1 Details of Outward Supplies and inward supplies liable to reverse charge (other than those covered by Table 3.1.1)</td>
    </tr>
    <tr>
        <th style="text-align: center;">Nature of Supplier</th>
        <th style="text-align: center;">Txbl.Value</th>
        <th style="text-align: center;">IGST</th>
        <th style="text-align: center;">CGST</th>
        <th style="text-align: center;">State/UT Tax</th>
        <th style="text-align: center;">Cess</th>
        <th colspan="4"></th>
    </tr>
    @foreach($data['table_3_1'] as $row)
        <tr>
            <td>{{ $row['label'] }}</td>
            <td style="text-align: right;">{{ $row['taxable_amount'] ?? '' }}</td>
            <td style="text-align: right;">{{ $row['igst'] ?? '' }}</td>
            <td style="text-align: right;">{{ $row['cgst'] ?? '' }}</td>
            <td style="text-align: right;">{{ $row['sgst'] ?? '' }}</td>
            <td style="text-align: right;">{{ $row['cess'] ?? '' }}</td>
            <td colspan="4"></td>
        </tr>
    @endforeach
    <tr><td colspan="10"></td></tr>

    <!-- Table 3.1.1 -->
    <tr>
        <td colspan="10">3.1.1 Details of Supplies Notified u/s 9(5) of the CGST Act, 2017</td>
    </tr>
    <tr>
        <th style="text-align: center;">Description</th>
        <th style="text-align: center;">Txbl. Value</th>
        <th style="text-align: center;">IGST</th>
        <th style="text-align: center;">CGST</th>
        <th style="text-align: center;">State/UT Tax</th>
        <th style="text-align: center;">Cess</th>
        <th colspan="4"></th>
    </tr>
    @foreach($data['table_3_1_1'] as $row)
        <tr>
            <td>{{ $row['label'] }}</td>
            <td style="text-align: right;">{{ $row['taxable_amount'] ?? '' }}</td>
            <td style="text-align: right;">{{ $row['igst'] ?? '' }}</td>
            <td style="text-align: right;">{{ $row['cgst'] ?? '' }}</td>
            <td style="text-align: right;">{{ $row['sgst'] ?? '' }}</td>
            <td style="text-align: right;">{{ $row['cess'] ?? '' }}</td>
            <td colspan="4"></td>
        </tr>
    @endforeach
    <tr><td colspan="10"></td></tr>

    <!-- Table 3.2 -->
    <tr>
        <td colspan="10">3.2 inter-State supplies made to unregistered persons, composition taxable persons and UIN holders</td>
    </tr>
    <tr>
        <th style="text-align: center;">Place of Supply(State/UT)</th>
        <th style="text-align: center;">Total Taxable Value</th>
        <th style="text-align: center;">Amount of IGST</th>
        <th colspan="7"></th>
    </tr>
    @foreach($data['table_3_2'] as $row)
        <tr>
            <td>{{ $row['label'] }}</td>
            <td style="text-align: right;">{{ $row['taxable_amount'] ?? '' }}</td>
            <td style="text-align: right;">{{ $row['igst'] ?? '' }}</td>
            <td colspan="7"></td>
        </tr>
    @endforeach
    <tr><td colspan="10"></td></tr>

    <!-- Table 4 -->
    <tr>
        <td colspan="10">4. Eligible ITC</td>
    </tr>
    <tr>
        <th style="text-align: center;">Details</th>
        <th style="text-align: center;">Integrated Tax</th>
        <th style="text-align: center;">Central Tax</th>
        <th style="text-align: center;">State/Ut Tax</th>
        <th style="text-align: center;">Cess</th>
        <th colspan="5"></th>
    </tr>
    @foreach($data['table_4'] as $row)
        <tr>
            <td>{{ $row['label'] }}</td>
            <td style="text-align: right;">{{ $row['igst'] ?? '' }}</td>
            <td style="text-align: right;">{{ $row['cgst'] ?? '' }}</td>
            <td style="text-align: right;">{{ $row['sgst'] ?? '' }}</td>
            <td style="text-align: right;">{{ $row['cess'] ?? '' }}</td>
            <td colspan="5"></td>
        </tr>
    @endforeach
    <tr><td colspan="10"></td></tr>

    <!-- Table 5 -->
    <tr>
        <td colspan="10">5. Values of exempt, nil-rated and non-GST inward supplies</td>
    </tr>
    <tr>
        <th style="text-align: center;">Nature of supplies</th>
        <th style="text-align: center;">Inter-State supplies</th>
        <th style="text-align: center;">Intra-State supplies</th>
        <th colspan="7"></th>
    </tr>
    @foreach($data['table_5'] as $row)
        <tr>
            <td>{{ $row['label'] }}</td>
            <td style="text-align: right;">{{ $row['inter'] ?? '' }}</td>
            <td style="text-align: right;">{{ $row['intra'] ?? '' }}</td>
            <td colspan="7"></td>
        </tr>
    @endforeach
    <tr><td colspan="10"></td></tr>

    <!-- Table 6.1 -->
    <tr>
        <td colspan="10">6.1 Payment of tax</td>
    </tr>
    <tr>
        <th rowspan="2" style="text-align: center; vertical-align: middle;">Description</th>
        <th rowspan="2" style="text-align: center; vertical-align: middle;">Tax Payable</th>
        <th colspan="4" style="text-align: center;">Paid through ITC</th>
        <th rowspan="2" style="text-align: center; vertical-align: middle;">Tax Paid TDS/TCS</th>
        <th rowspan="2" style="text-align: center; vertical-align: middle;">Tax/Cess Paid in Cash</th>
        <th rowspan="2" style="text-align: center; vertical-align: middle;">Interest</th>
        <th rowspan="2" style="text-align: center; vertical-align: middle;">Late Fee</th>
    </tr>
    <tr>
        <th style="text-align: center;">IGST</th>
        <th style="text-align: center;">CGST</th>
        <th style="text-align: center;">SGST/UTGST</th>
        <th style="text-align: center;">Cess</th>
    </tr>
    @foreach($data['table_6_1'] as $row)
        <tr>
            <td>{{ $row['label'] }}</td>
            <td style="text-align: right;">{{ $row['payable'] ?? '' }}</td>
            <td style="text-align: right;">{{ $row['itc_igst'] ?? '' }}</td>
            <td style="text-align: right;">{!! !empty($row['_dash1']) ? '<span style="color:#999;">-------</span>' : ($row['itc_cgst'] ?? '') !!}</td>
            <td style="text-align: right;">{!! !empty($row['_dash2']) ? '<span style="color:#999;">-------</span>' : ($row['itc_sgst'] ?? '') !!}</td>
            <td style="text-align: right;">{{ $row['itc_cess'] ?? '' }}</td>
            <td style="text-align: right;">{{ $row['tds'] ?? '' }}</td>
            <td style="text-align: right;">{{ $row['cash'] ?? '' }}</td>
            <td style="text-align: right;">{{ $row['interest'] ?? '' }}</td>
            <td style="text-align: right;">{{ $row['late_fee'] ?? '' }}</td>
        </tr>
    @endforeach
    <tr><td colspan="10"></td></tr>

    <!-- Table 6.2 -->
    <tr>
        <td colspan="10">6.2 TDS/TCS Credit</td>
    </tr>
    <tr>
        <th style="text-align: center;">Details</th>
        <th style="text-align: center;">Integrated Tax</th>
        <th style="text-align: center;">Central Tax</th>
        <th style="text-align: center;">State/UT Tax</th>
        <th colspan="6"></th>
    </tr>
    @foreach($data['table_6_2'] as $row)
        <tr>
            <td>{{ $row['label'] }}</td>
            <td style="text-align: right;">{{ $row['igst'] ?? '' }}</td>
            <td style="text-align: right;">{{ $row['cgst'] ?? '' }}</td>
            <td style="text-align: right;">{{ $row['sgst'] ?? '' }}</td>
            <td colspan="6"></td>
        </tr>
    @endforeach
</table>
