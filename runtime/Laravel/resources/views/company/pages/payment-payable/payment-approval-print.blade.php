<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Approval</title>

    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
        }

        table {
            border-collapse: collapse;
        }

        table td,
        table th {
            border: 1px solid #000;
            border-collapse: collapse;
            padding: 2px 8px;
            font-size: 13px;
            width: 50%;
        }

        .bb-0 {
            border-bottom: none;
        }

        .bt-0 {
            border-top: none;
        }

        .br-0 {
            border-right: none;
        }

        .bl-0 {
            border-left: none;
        }

        .w-17 {
            width: 17%;
        }

        .w-8 {
            width: 6%;
        }

        .w-10 {
            width: 11%;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: end;
        }

        .fw-bold {
            font-weight: 600;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .company-name h4 {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }

        .page-number span {
            font-size: 16px;
            font-weight: 600;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 5px;
        }

        .date span {
            font-size: 17px;
            font-weight: bold;
        }

        .file-no span {
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            flex: 1;
        }

        .date-section-header {
            background-color: #f0f0f0;
            font-size: 15px;
            font-weight: bold;
            padding: 4px 8px;
            margin-top: 10px;
            border: 1px solid #999;
        }

        .conditional-page-break {
            page-break-after: always;
        }

        @media print {
            body {
                margin: 0;
            }
        }
    </style>
</head>

<body>
    @php
        $page = 1;
        $rowCount = 1;
        $rowsPerPage = 48;
        $grandTotal = 0;
        foreach ($paymentVoucherNumberCollection as $dateAmounts) {
            foreach ($dateAmounts as $amt) {
                $grandTotal += $amt;
            }
        }
    @endphp

    <div class="page">
        <div class="header">
            <div class="company-name">
                <h4 style="text-align: center;">
                    {{ Str::upper($companyDetail['company_name']) }}
                </h4>
            </div>
        </div>

        <div class="info-row" style="display: flex; justify-content: space-between">
            <div class="date">
                <span style="font-size: 18px">Payment Approval Print</span>
            </div>
            {{-- <div class="file-no">
                @if(!empty($fileNo))
                    <span style="font-size: 18px">File No: {{ $fileNo }}</span>
                @endif
            </div> --}}
            <div class="page-number">
                <span>Page: {{ $page }}</span>
            </div>
        </div>

        <div>
            @foreach ($dateWiseData as $voucherDate => $voucherWiseData)
                @php
                    $formattedDate = !empty($voucherDate) ? \Carbon\Carbon::parse($voucherDate)->format('d-m-Y') : 'N/A';
                    $dateTotal = isset($paymentVoucherNumberCollection[$voucherDate])
                        ? array_sum($paymentVoucherNumberCollection[$voucherDate])
                        : 0;
                    $rowCount++;
                @endphp

                <div class="date-section-header" style="display: flex; justify-content: space-between;">
                    <span>Payment Date: {{ $formattedDate }}</span>
                    @if(!empty($modifyDateWiseFile[$voucherDate]))
                        <div class="file-no">
                            <span style="font-size: 18px">File No: {{ $modifyDateWiseFile[$voucherDate] }}</span>
                        </div>
                    @endif
                    <span>Date Total: {{ formatIndianNumber($dateTotal, 2) }}</span>
                </div>

                @foreach ($voucherWiseData as $voucherNumber => $supplierWiseData)
                    @foreach ($supplierWiseData as $supplierId => $billData)
                        <p>
                            @php $rowCount++; @endphp
                        </p>
                        <div style="display: flex; justify-content: space-between">
                            <span style="font-size: 12px">
                                Account Name: <strong>{{ Str::limit($supplierData[$supplierId]['supplier_name'], 43) }}
                                    @if(isset($supplierData[$supplierId]['city']))
                                        ({{ Str::limit($supplierData[$supplierId]['city'], 15) }})
                                    @endif
                                </strong>
                            </span>
                            <span style="font-size: 12px">
                                Total Bill Amt.: <strong>{{ formatIndianNumber($paymentVoucherNumberCollection[$voucherDate][$voucherNumber] ?? 0, 2) }}</strong>
                            </span>
                        </div>
                        @php $rowCount++; @endphp

                        <table>
                            <tr>
                                <td style="width:3%;">Srno.</td>
                                <td style="width:3%;">File</td>
                                <td style="width:3%;">Bill</td>
                                <td style="width:10%;">Bil Date</td>
                                <td style="width:10%; white-space: nowrap;">Show Date</td>
                                <td style="width:10%;">Pay Amt.</td>
                                <td style="width:4%;">Per.</td>
                                <td class="text-center" style="width:10%;">CD</td>
                                <td style="width:10%;">Balance</td>
                                <td style="width:10%;">Rebate</td>
                                <td style="width:10%;">Destination</td>
                                <td style="width:30%; white-space: nowrap;">Product</td>
                            </tr>
                            @php $rowCount++; @endphp

                            @foreach ($billData as $srno => $item)
                                @php
                                    $class = (strtolower($item['destination_name']) == "palanpur" || strtolower($item['destination_name']) == "katarva") ? "fw-bold" : "";
                                    $rowCount++;
                                @endphp

                                <tr>
                                    <td class="text-center" style="width:3%;">{{ $srno + 1 }}</td>
                                    <td class="text-center" style="width:3%;">{{ $item['file_no'] }}</td>
                                    <td class="text-center" style="width:3%;">{{ $item['ref_no'] }}</td>
                                    <td class="text-center" style="width:10%;">{{ !empty($item['ref_date']) ? \Carbon\Carbon::parse($item['ref_date'])->format('d/m/y') : "" }}</td>
                                    <td class="text-center" style="width:10%;">{{ !empty($item['show_date']) ? \Carbon\Carbon::parse($item['show_date'])->format('d/m/y') : "" }}</td>
                                    <td class="{{ $class }} text-end" style="width:10%;">{{ $item['pay_amount'] }}</td>
                                    <td class="text-center" style="width:4%;">{{ $item['cd_percentage'] }}</td>
                                    <td class="text-end" style="width:10%;">{{ $item['cd'] }}</td>
                                    <td class="text-end" style="width:10%;">{{ $item['net_total'] }}</td>
                                    <td class="text-end" style="width:10%;">{{ $item['rebate'] }}</td>
                                    <td class="{{ $class }}" style="width:12%;">{{ Str::limit($item['destination_name'], 8) }}</td>
                                    <td style="width:28%; white-space: nowrap;">{{ Str::limit($item['product_name'], 12) }}</td>
                                </tr>

                                @if ($rowCount >= $rowsPerPage)
                                    </table>
                                    <div style="page-break-after: always;"></div>
                                    @php
                                        $page++;
                                        $rowCount = -1;
                                    @endphp
                                    <div class="page">
                                        <div style="display: flex; justify-content: space-between">
                                            <div><span style="font-size: 18px">Payment Date: {{ $formattedDate }}</span></div>
                                            <div class="file-no">
                                                @if(!empty($fileNo))
                                                    <span style="font-size: 18px">File No: {{ $fileNo }}</span>
                                                @endif
                                            </div>
                                            <div class="page-number">
                                                <span>Page: {{ $page }}</span>
                                            </div>
                                            @php
                                                $rowCount++;
                                                $rowCount++;
                                            @endphp
                                        </div>
                                    </div>
                                    <table>
                                @endif
                            @endforeach
                        </table>
                    @endforeach
                @endforeach
            @endforeach

            <div style="display: flex; justify-content: space-between; margin-top: 18px">
                <span></span>
                <span style="font-size: 20px">Grand Total: <strong>{{ formatIndianNumber($grandTotal, 2) }}</strong></span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top:55px">
                <div><span class="font-arial">User Name: <strong>{{ Auth::user()->name }}</strong></span></div>
                <div><span class="font-arial">Checker Sign:</span></div>
                <div><span class="font-arial">Payment Approver Sign:</span></div>
            </div>
        </div>
    </div>
</body>

<script>
    window.onload = function() {
        window.print();
    };
    addEventListener("afterprint", (event) => {
        window.close();
    });
</script>

</html>
