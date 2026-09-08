<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PDF Document</title>
    <style>
        /* Your CSS styles here */
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
            /* border: 1px solid black; */
            border-right: none;
            border-left: none;
        }

        table thead tr th {
            border-top: 2px solid black;
            /* Replace 'black' with your desired border color */
        }

        table tbody tr td {
            border: 0px;
            /* Replace 'black' with your desired border color */
        }

        table tfoot tr td {
            border-top: 4px double rgb(66, 65, 65);
            border-bottom: 2px solid black;
        }

        th,
        td {
            /* padding: 2px; */
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

        #registerBody tr td{
            font-size: 25px
        }
        @media print {

            /* Print-specific styles */
            body {
                margin: 0;
                /* Remove default margin for print */
            }

            @page {
                size: landscape;
                /* Set the print layout to landscape mode */
            }
        }
    </style>
</head>

<body>
    <center>
        <div>
            <h4 class="d-flex p-O m-0" style="font-size: 20px;font-weight: 600">
                {{ Str::upper($companyName) }}</h4>
            <span class="d-flex p-0 m-0" style="font-size: 16px;font-weight: 600"> Srno Wise Purchase Report</span>
            @php
            $page = 1;
            $temp = 0;
            $row = 32;
            @endphp
            <div style="display: flex; align-items: center;">
                <span class="fw-bold" style="font-size: 17px; font-weight: 600; text-align: start; flex: 33.33%;">
                    Date : {{ date('d / m / Y') }}
                </span>
                <span style="flex: 33.33%;font-size: 18px; font-weight: 600;">@isset($filter['file_no']) File No - {{ $filter['file_no'] }} @endisset</span>
                <div class="text-end p-0 m-0 mb-1" style="flex: 33.33%;">
                    Page : {{ $page }}
                </div>
            </div>
            {{-- <p  style="font-size: 13px"></p> --}}
        </div>

        {{-- <div class="text-end p-0 m-0 mb-1"> Page : {{ $page }}</div> --}}
        <table>
            <thead>
                <tr style="border-bottom: 2px solid black">
                    <th style="width: 5%">Sr</th>
                    <th style="width: 7%">File No.</th>
                    <th style="width: 5%">Date</th>
                    <th style="width: 7%">Bill No.</th>
                    <th style="width: 22%">Name</th>
                    <th style="width: 12%">City</th>
                    <th style="width: 12%">Destination</th>
                    <th style="width: 7%">Qty.</th>
                    <th style="width: 4%">Rate</th>
                    <th style="width: 10%" class="text-end">Amount</th>
                    <th style="width: 8%" class="text-end">C.D.</th>
                </tr>
            </thead>
            <tbody class="register-body">
                @php
                $totalAmount = 0;
                @endphp
                @foreach ($purchaseRegisterData as $supplierItems)
                {{-- @dd($supplierItems) --}}
                @php
                $cd = 0;
                @endphp
                @foreach ($supplierItems as $key => $item)

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
                    <th style="width: 5%">Sr</th>
                    <th style="width: 7%">File No.</th>
                    <th style="width: 5%">Date</th>
                    <th style="width: 7%">Bill No.</th>
                    <th style="width: 22%">Name</th>
                    <th style="width: 12%">City</th>
                    <th style="width: 12%">Destination</th>
                    <th style="width: 7%">Qty.</th>
                    <th style="width: 4%">Rate</th>
                    <th style="width: 10%" class="text-end">Amount</th>
                    <th style="width: 8%" class="text-end">C.D.</th>
                </tr>
            </thead>
            <tbody>
                @endif
                <tr>
                    <td>
                        @isset($item['voucher_number'])
                        {{$item['voucher_number']}}
                        @endisset
                    </td>
                    <td>
                        @if (
                        (isset($item['file_no']) && !empty($item['file_no'])) ||
                        (isset($item['sales_inv_no']) && !empty($item['sales_inv_no'])))
                        {{ $item['file_no'] }} / {{ $item['sales_inv_no'] }}
                        @endif
                    </td>
                    <td>
                        @if ((isset($item['date']) && !empty($item['date'])))
                        
                        {{ format_date($item['date'], 'd/m/Y') }}
                        @endif
                    </td>
                    <td style="text-align: center">
                        @isset($item['other_ref_no'])
                        {{$item['other_ref_no']}}
                        @endisset
                    </td>
                    {{-- <td>{{ $item['supplier_name'] }}</td> --}}
                    <td>{{ Str::limit($item['account_name'], 22) }}</td>
                    {{-- <td>{{ $item['city'] }}</td> --}}
                    <td>{{ Str::limit($item['account_city'], 11) }}</td>
                    <td>{{ Str::limit($item['destination_name'], 11) }}</td>
                    <td>{{ format_number($item['qty'],3) }}</td>
                    <td>{{ $item['rate'] }}</td>
                    <td class="text-end">
                        @if(isset($item['cd']))
                        {{ formatIndianNumber($item['net_total']) ?? '-' }}
                        @else
                        0
                        @endif

                    </td>
                    <td class="text-end">
                        @if(isset($item['cd']))
                        {{ formatIndianNumber(abs($item['cd'])) }}
                        @else
                        0
                        @endif
                    </td>
                </tr>
                @php
                $totalarr[] = $item['net_total'];
                $totalAmount += $item['net_total'];
                $cd += abs($item['cd']) ?? 0;
                $temp++;
                @endphp
                @if ($temp == $row)
            </tbody>
        </table>
        @php
        $page++;
        $temp = -3;
        @endphp
        <div class="conditional-page-break"></div>
        <div class="text-end p-0 m-0 mb-1"> Page : {{ $page }}</div>
        <table>
            <thead>
               <tr style="border-bottom: 2px solid black">
                    <th style="width: 5%">Sr</th>
                    <th style="width: 7%">File No.</th>
                    <th style="width: 8%">Date</th>
                    <th style="width: 6%">Bill No.</th>
                    <th style="width: 20%">Name</th>
                    <th style="width: 12%">City</th>
                    <th style="width: 12%">Destination</th>
                    <th style="width: 7%">Qty.</th>
                    <th style="width: 4%">Rate</th>
                    <th style="width: 10%" class="text-end">Amount</th>
                    <th style="width: 8%" class="text-end">C.D.</th>
                </tr>
            </thead>
            <tbody>
                @endif
                @endforeach
                <!-- Display the total for each supplier -->
                <tr>
                    <td colspan="6"></td>
                    <td style="border-top: 2px solid black;border-bottom: 2px solid black;bold;font-weight: bold ">Pay
                        Total..</td>
                    <td style="border-top: 2px solid black;border-bottom: 2px solid black;" colspan="2"></td>
                    <td style="border-top: 2px solid black;border-bottom: 2px solid black;font-weight: 600;font-size:13px" class="text-end">
                        {{ formatIndianNumber($totalAmount) ?? '-' }}
                    </td>
                    <td style="border-top: 2px solid black;border-bottom: 2px solid black;font-weight: 600;font-size:13px" class="text-end">
                        {{ formatIndianNumber($cd) ?? '-' }}
                    </td>
                </tr>

                @php
                $totalAmount = 0; // Reset total amount for the next supplier
                $temp++;
                @endphp
                @if ($temp == $row)
            </tbody>
        </table>
        @php
        $page++;
        $temp = 0;
        @endphp
        <div class="conditional-page-break" style="border:none"></div>
        <div class="text-end p-0 m-0 mb-1" style="border-top:none"> Page : {{ $page }}</div>
        <table>
            <thead>
               <tr style="border-bottom: 2px solid black">
                    <th style="width: 5%">Sr</th>
                    <th style="width: 7%">File No.</th>
                    <th style="width: 5%">Date</th>
                    <th style="width: 7%">Bill No.</th>
                    <th style="width: 22%">Name</th>
                    <th style="width: 12%">City</th>
                    <th style="width: 12%">Destination</th>
                    <th style="width: 7%">Qty.</th>
                    <th style="width: 4%">Rate</th>
                    <th style="width: 10%" class="text-end">Amount</th>
                    <th style="width: 8%" class="text-end">C.D.</th>
                </tr>
            </thead>
            <tbody>
                @endif
                @endforeach
                @php
                $grandtotal = array_sum($totalarr);
                @endphp
                <tr>
                    <td colspan="10"><br></td>
                </tr>
                <tr>
                    <td style="border-top: 1px solid black;" colspan="6"></td>
                    <td style="border-top: 1px solid black;" colspan="3"><b>Total Pay Amount...</b></td>
                    <td style="border-top: 1px solid black; font-size: 15px" class="text-end">
                        <b>{{ formatIndianNumber(abs($grandtotal)) ?? '-' }}</b>
                    </td>
                    <td style="border-top: 1px solid black;"></td>
                </tr>
                <tr>
                    <td style="border-top: 1px solid black;" colspan="6"></td>
                    <td style="border-top: 1px solid black;" colspan="3"><b>Total CD...</b></td>
                    <td style="border-top: 1px solid black; font-size: 15px" class="text-end">
                        <b>{{ formatIndianNumber(abs($total_cd)) ?? '-' }}</b>
                    </td>
                    <td style="border-top: 1px solid black;"></td>
                </tr>
                <tr>
                    <td style="border-top: 1px solid black;" colspan="6"></td>
                    <td style="border-top: 1px solid black;" colspan="3"><b>Total Rebate...</b></td>
                    <td style="border-top: 1px solid black; font-size: 15px" class="text-end">
                        <b>{{ formatIndianNumber(abs($total_rebate)) ?? '-' }}</b>
                    </td>
                    <td style="border-top: 1px solid black;"></td>
                </tr>
            </tbody>
        </table>
        </div>
    </center>
</body>
<script>
    addEventListener("afterprint", (event) => {
    window.close();
    });
</script>

</html>
