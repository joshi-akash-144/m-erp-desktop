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

            /* @page {
                size: landscape;
            } */
        }
    </style>
</head>

<body>
    <center>
        <div>
            <h4 class="d-flex p-O m-0">{{ Str::upper($companyName) }}</h4>
            <p class="d-flex p-0 m-0">Dairy Analysis Report Register</p>
        </div>
        <table>
            <thead>
                <tr style="border-bottom: 2px solid black">
                    <th>Sr. No.</th>
                    <th class="text-center">Supplier</th>
                    {{-- <th>City</th> --}}
                    <th>S.billno</th>
                    <th>file no.</th>
                    <th>P.date</th>
                    <th>P.bill no.</th>
                    <th>P.Qty</th>
                    <th class="text-end">Rebate</th>
                    <th class="text-end">Bal. Amt.</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $rebate = 0;
                    $totalamt = 0;
                    $sr = 1;
                    $totalrebatearr = [];
                    $totalamtarr   = [];
                @endphp
                @foreach ($finaldardata as $darItems)
                    @foreach ($darItems as $key => $item)
                    
                        <tr>
                            <td>{{ $sr++ }}</td>
                            <td><b>{{ $item['supplier'] }}</b></td>
                            {{-- <td>{{ $item['city'] }}</td> --}}
                            <td class="text-center">{{ $item['sbill_no'] }}</td>
                            <td class="text-center">{{ $item['file_no'] }}</td>
                            <td class="text-center">{{ format_date($item['date'], 'd/m/Y') }}</td>
                            <td class="text-center"><b>{{ $item['pbill_no'] }}</b></td>
                            <td class="text-center">{{ $item['p_qty'] }}</td>
                            <td class="text-end">-{{ formatIndianNumber($item['rebate'], 2) }}</td>
                            <td class="text-end">{{ formatIndianNumber($item['balance_amt'], 2) }}</td>
                        </tr>
                        @php
                            $totalrebatearr[] = $item['rebate'];
                            $totalamtarr[] = $item['balance_amt'];
                            $rebate += $item['rebate'] ?? 0;
                            $totalamt += $item['balance_amt'] ?? 0;
                        @endphp
                    @endforeach
                    <tr>
                        <td style="border-top: 1px solid black;border-bottom: 2px solid black;font-weight: bold;text-align:right"
                            colspan="7">Total..</td>
                        {{-- <td class="border-left-none" style="border-top: 1px solid black;border-bottom: 2px solid black;bold;font-weight: bold;"> --}}
                        </td>
                        {{-- <td style="border-top: 1px solid black;border-bottom: 2px solid black" colspan="2"></td> --}}
                        <td style="border-top: 1px solid black;border-bottom: 2px solid black" class="text-end">
                            -{{ formatIndianNumber($rebate, 2) ?? '-' }}
                        </td>
                        <td style="border-top: 1px solid black;border-bottom: 2px solid black" class="text-end">
                            {{ formatIndianNumber($totalamt, 2) ?? '-' }}
                        </td>
                    </tr>
                    @php
                        $rebate = 0;
                        $totalamt = 0;
                    @endphp
                @endforeach
                @php
                    $rebategrandtotal = array_sum($totalrebatearr);
                    $amtgrandtotal = array_sum($totalamtarr);
                @endphp
                <tr>
                    {{-- <td colspan="5"></td> --}}
                    <td style="text-align:right" colspan="7"><b>Grand Total</b></td>
                    <td class="text-end"><b>{{ formatIndianNumber(abs($rebategrandtotal), 2) ?? '-' }}</b></td>
                    <td class="text-end"><b>{{ formatIndianNumber(abs($amtgrandtotal), 2) ?? '-' }}</b></td>
                </tr>
            </tbody>
        </table>
        </div>
    </center>

    <script>
        addEventListener("afterprint", (event) => {
            window.close();
        });
    </script>
</body>

</html>
