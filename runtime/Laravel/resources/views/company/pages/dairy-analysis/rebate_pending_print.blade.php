<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Dairy Analysis Report Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table,
        th,
        td {
            border: 1px solid black;
            /* border-right: none;
            border-left: none; */
        }

        table thead tr th {
            border-top: 2px solid black;
        }

        /* table tbody tr td {
            border: 0px;
        } */

        table tfoot tr td {
            border-top: 4px double rgb(66, 65, 65);
            border-bottom: 2px solid black;
        }

        th,
        td {
            text-align: left;
            font-size: 12px;
        }

        .d-flex {
            display: flex;
            justify-content: center;
        }

        .text-end {
            text-align: end;
            margin-right: 100px;
        }

        .text-center {
            text-align: center;
        }

        .p-O {
            padding: 0;
        }

        .m-0 {
            margin: 0;
        }

        .border-left-none {
            border-left: none !important;
        }

        @media print {
            body {
                margin: 0;
            }

            @page {
                size: landscape;
            }
        }
         /* ============================
        FOOTER
        ============================ */
        .report-footer {
            font-size: 11.5px;
            font-style: italic;
            margin-top: 12px;
            border-top: 1px solid #000;
            padding-top: 5px;
            width: 100%;
            display: table;
            table-layout: fixed;
        }

        .report-footer::after {
            content: "";
            display: table;
            clear: both;
        }

        .report-footer .footer-left {
            float: left;
            width: 50%;
            text-align: left;
        }

        .report-footer .footer-right {
            float: right;
            width: 50%;
            text-align: right;
        }
    </style>
</head>

<body>
    <center>
        <div>
            <h4 class="d-flex p-O m-0">{{ Str::upper($companyName) }}</h4>
            <p class="d-flex p-0 m-0">Dairy Analysis Pending Rebate Report Register</p>
            <h6 class="text-start p-0 m-0 "><strong>Date Period :</strong> {{ $datePeriod }}</h6>           
        </div>
        <table>
            <thead>
                <tr style="border-bottom: 2px solid black">
                    <th>Sr. No.</th>
                    <th class="text-center">Supplier</th>
                    <th>City</th>
                    <th class="text-center">Customer</th>
                    <th class="text-center">Destination</th>
                    <th>S.billno</th>
                    <th>Grn Number</th>
                    <th>P.billno</th>
                    <th>P.date</th>
                    <th>Vehicle</th>
                    <th>P.Qty</th>
                    <th class="text-end">Bal. Amt.</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $rebate = 0;
                    $totalamt = 0;
                    $sr = 1;
                @endphp
                @php
                    $totalAmount = 0;
                    $prevVendor = null;
                @endphp
                @foreach ($finaldata as $data)                     
                    @foreach ($data as $key => $item)
                        @php
                            $date = !empty($item['date']) ? format_date($item['date']) : '--';
                        @endphp
                        @if ($prevVendor != $item['customer_name'])
                            {{-- Output total for previous vendor --}}
                            @if ($key != 0)                           
                                <tr>
                                    <td colspan="11" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end">{{ formatIndianNumber($totalAmount) }}</td>
                                </tr>
                                @php
                                    $totalAmount = 0; // Reset total amount for the new vendor
                                @endphp
                            @endif
                        @endif
                        <tr>
                            @php
                            // dd($accountData);
                             if(isset($item['purchase_type']) && $item['purchase_type'] == 3){
                                $supplierName = $accountData[$item['supplier_id']]['account_name'];
                                $city = $accountData[$item['supplier_id']]['city'];
                            }
                            if(isset($item['purchase_type']) && $item['purchase_type'] == 1){
                                $supplierName = $item['supplier_name'];
                                $city = $item['supplier_city'];
                            }
                            if(isset($item['sales_type']) && $item['sales_type'] == 3){
                                $vendorName = $accountData[$item['vendor_id']]['account_name'];
                            }
                            if(isset($item['sales_type']) && $item['sales_type'] == 2){
                                $vendorName = $item['vendor_name'];
                            }

                            @endphp
                            <td>{{ $sr++ }}</td>
                            <td>{{ Str::of($item['supplier'])->limit(22) ?? '-' }}</td>
                            <td>{{ Str::of($item['city'])->limit(8) ?? '-' }}</td>
                            <td>{{ Str::of($item['customer_name'])->limit(17) ?? '-' }}</td>
                            <td>{{ $item['destination'] ?? '-' }}</td>
                            <td>{{ $item['sale_invoice_serial'] ?? '-' }}</td>
                            <td>{{ $item['grn_no'] ?? '-' }}</td>
                            <td>{{ $item['reference_number'] ?? '-' }}</td>
                            <td>{{ $date ?? '-' }}</td>
                            <td>{{ $item['vehicle'] ?? '-' }}</td>
                            <td>{{ $item['p_qty'] ?? '-' }}</td>
                            <td class="text-end">{{ formatIndianNumber($item['balance_amt']) }}</td>
                        </tr>
                        @php
                            $totalamtarr[] = $item['balance_amt'];
                            $rebate += $item['rebate'] ?? 0;
                            $totalamt += $item['balance_amt'] ?? 0;
                        @endphp
                        {{-- Update total amount for the current vendor --}}
                        @php
                            $totalAmount += $item['balance_amt'];
                            $prevVendor = $item['customer_name'];
                        @endphp
                    @endforeach
                @endforeach
                {{-- Output total for the last vendor --}}
                <tr>
                    <td colspan="11" class="text-end"><strong>Total:</strong></td>
                    <td class="text-end">{{ formatIndianNumber($totalAmount) }}</td>
                </tr>
            </tbody>
        </table>
        </div>
        <footer class="report-footer">
            <span class="footer-left">Prepared By: <strong>{{ auth()->user()->name }}</strong></span>
            <span class="footer-right">Prepared On: <strong>{{ now()->format('d-m-Y H:i:s') }}</strong></span>
        </footer>
    </center>

    <script>
        addEventListener("afterprint", (event) => {
            window.close();
        });
    </script>
</body>

</html>
