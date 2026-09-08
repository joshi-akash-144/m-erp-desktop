<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Sales Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table,
        th,
        td {
            border-right: none;
            border-left: none;
        }

        table thead tr th {
            border-top: 2px solid black;
        }

        table tbody tr td {
            border: 0px;
        }

        table tfoot tr td {
            border-top: 4px double rgb(66, 65, 65);
            border-bottom: 2px solid black;
        }

        th,
        td {
            text-align: left;
            font-size: 13px;
            padding: 1px;
        }

        .d-flex {
            display: flex;
            justify-content: center;
        }

        .text-end {
            text-align: end;
            margin-right: 100px;
        }

        .p-O {
            padding: 0;
        }

        .m-0 {
            margin: 0;
        }

        .conditional-page-break {
            page-break-after: always;
        }

        #registerBody tr td {
            font-size: 25px
        }

        @media print {
            body {
                margin: 0;
            }

            @page {
                size: landscape;
            }
        }
    </style>
</head>

<body>
    <center>
        @php
            $totalarr = [];
            $totalQtyarr = [];
            $grandtotal = 0;
            $qtygrandtotal = 0;
        @endphp
        <div>
            <h4 class="d-flex p-O m-0" style="font-size: 20px;font-weight: 600">
                {{ Str::upper($companyName) }}</h4>
            <span class="d-flex p-0 m-0" style="font-size: 16px;font-weight: 600">Sales Report</span>
            @php
                $page = 1;
                $temp = 0;
                $row = 32;
            @endphp
            <div style="display: flex; align-items: center;">
                <span class="fw-bold" style="font-size: 17px; font-weight: 600; text-align: start; flex: 33.33%;">
                    Date : {{ date('d / m / Y') }}
                </span>
                <div class="text-end p-0 m-0 mb-1" style="flex: 33.33%;">
                    Page : {{ $page }}
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr style="border-bottom: 2px solid black">
                    <th style="width: 10%">Bill No.</th>
                    <th style="width: 10%">Date</th>
                    <th style="width: 30%">Customer</th>
                    <th style="width: 20%">Product</th>
                    <th style="width: 10%">Zone</th>
                    <th style="width: 5%">Qty.</th>
                    <th style="width: 5%">Rate</th>
                    <th style="width: 10%" class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody class="register-body">
                @foreach ($allData as $data)
                    @if ($temp == $row)
            </tbody>
        </table>
        @php
            $page++;
            $temp = 0;
        @endphp
        <div class="conditional-page-break"></div>
        <div class="text-end p-0 m-0 mb-1"> Page : {{ $page }}</div>
        <table>
            <thead>
                <tr style="border-bottom: 2px solid black">
                    <th style="width: 10%">Bill No.</th>
                    <th style="width: 10%">Date</th>
                    <th style="width: 30%">Customer</th>
                    <th style="width: 25%">Product</th>
                    <th style="width: 10%">Zone</th>
                    <th style="width: 5%">Qty.</th>
                    <th style="width: 5%">Rate</th>
                    <th style="width: 7%" class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @endif
                
                @if(isset($data->is_total) && $data->is_total)
                <tr>
                    <td colspan="4"></td>
                    <td style="border-top: 2px solid black;border-bottom: 2px solid black;font-weight: bold;">
                        Total..</td>
                    <td style="border-top: 2px solid black;border-bottom: 2px solid black;font-weight: 600;font-size:13px">
                        {{ $data->quantity ?: '-' }}
                    </td>
                    <td style="border-top: 2px solid black;border-bottom: 2px solid black;"></td>
                    <td style="border-top: 2px solid black;border-bottom: 2px solid black;font-weight: 600;font-size:13px" class="text-end">
                        {{ $data->total_amount ?: '-' }}
                    </td>
                </tr>
                @else
                <tr>
                    <td>{{ $data->invoice_serial ?? '' }}</td>
                    <td>{{ $data->invoice_date ?? '' }}</td>
                    <td>{{ isset($data->account_name) ? substr($data->account_name, 0, 30) : '' }}</td>
                    <!-- <td>{{ isset($data->item_name) ? substr($data->item_name, 0, 28) : '' }}</td> -->
                    <td>{{ isset($data->item_name) ? \Illuminate\Support\Str::limit($data->item_name, 23, '...') : '' }}</td>
                    <td>{{ $data->zone ?? '' }}</td>
                    <td>{{ $data->quantity ?? '' }}</td>
                    <td>{{ $data->rate ?? '' }}</td>
                    <td class="text-end">{{ $data->total_amount ?? '' }}</td>
                </tr>
                @php
                    if (isset($data->total_amount) && $data->total_amount != '') {
                        $grandtotal += (float) str_replace(',', '', $data->total_amount ?? '0');
                    }
                    if (isset($data->item_name) && $data->item_name != '') {
                        $qtygrandtotal += (float) str_replace(',', '', $data->quantity ?? '0');
                    }
                @endphp
                @endif
                
                @php
                    $temp++;
                @endphp
                @endforeach
                
                <tr>
                    <td colspan="8"><br></td>
                </tr>
                <tr>
                    <td style="border-top: 1px solid black;" colspan="4"></td>
                    <td style="border-top: 1px solid black;"><b>Grand Total</b></td>
                    <td style="border-top: 1px solid black; font-size: 15px">
                        <b>{{ formatIndianNumber(abs($qtygrandtotal), 2) ?? '-' }}</b>
                    </td>
                    <td style="border-top: 1px solid black;"></td>
                    <td style="border-top: 1px solid black; font-size: 15px" class="text-end">
                        <b>{{ formatIndianNumber(abs($grandtotal), 2) ?? '-' }}</b>
                    </td>
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

