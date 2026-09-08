<!DOCTYPE html>
<html lang="en">
    <style>
        /* Table styles */
        .styled-table {
            border-collapse: collapse;
            width: 100%;
        }

        .styled-table th,
        .styled-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-family: 'Courier New', Courier, monospace;
        }

        .styled-table th {
            background-color: #f2f2f2;
        }

        .styled-table tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .styled-table tbody tr:hover {
            background-color: #ddd;
        }

        .styled-table tfoot td {
            font-weight: bold;
        }
        .text-end{
            text-align: right !important;
        }
        .fw-bold{
            font-weight: 600 !important;
        }
        .font-monospace{
            font-family: monospace;
        }
        .styled-table th, .styled-table td {
            border: 1px solid black;
            }
    </style>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dairy Outstanding</title>


</head>

<body>
    <div class="d-flex justify-content-center">
        <div>
            <table class="styled-table">
                <thead>
                    @php
                        $grand_total =  0;
                        $report_date = date('d-m-Y');
                    @endphp
                               <tr>
                                <th colspan="3"><span class="text-primary p-1" style="font-size: 20px; background-color: white">Date: {{ $report_date }} according to Ledger remaining
                                </th>
                            </tr>
                    @foreach (($finalArray ?? []) as $key => $party)
                        <tr>
                            <th colspan="3"><span class="text-danger p-1" style="font-size: 20px; background-color: white">{{ $party['party_name'] ?? '' }}</span></th>
                        </tr>
                        <tr>
                            <th class="font-monospace">Sr.</th>
                            <th class="font-monospace">Firm Name</th>
                            <th class="font-monospace" style="text-align: right;">Amount</th>
                        </tr>
                        @php
                            $index = 1;
                        @endphp
                        @foreach ($party['companies'] as $company)
                            <tr>
                                <td class="font-monospace" style="font-size: 16px">{{ $index++ }}</td>
                                <td class="font-monospace" style="font-size: 16px;">{{ $company['company_name'] }}</td>
                                <td class="font-monospace" style="font-size: 16px;text-align: right;font-weight: 600">{{ formatIndianNumber(number_format($company['balance'] ?? 0, 2, '.', ''), 2) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="2" class="text-end font-monospace  fw-bold" style="font-size: 19px">Total</td>
                            <td class="font-monospace text-end text-primary text-success fw-bold" style="font-size: 19px">{{ formatIndianNumber(number_format($party['total_balance'], 2, '.', ''), 2) }}</td>
                        </tr>
                        @php
                            $grand_total += $party['total_balance'];
                        @endphp
                    @endforeach
                    <tr>
                        <td colspan="2" class="text-end font-monospace fw-bold" style="font-size: 19px">Grand Total</td>
                        <td class="font-monospace text-end fw-bold text-primary" style="font-size: 20px" >{{ formatIndianNumber(number_format($grand_total, 2, '.', ''), 2) }}</td>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

        </div>
    </div>
</body>
<script>
    addEventListener("afterprint", (event) => {
        window.close();
    });
</script>
</html>
