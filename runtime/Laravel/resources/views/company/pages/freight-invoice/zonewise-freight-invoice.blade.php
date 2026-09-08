<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zone Wise Data</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        table thead tr th {
            text-align: center
        }

        table tfoot tr th {
            border-top: 2px solid black;
            border-bottom: 2px solid black;
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

        @media print {
            body {
                font-family: 'Times New Roman', Times, serif !important;
                margin: 0;
            }

            @page {
                size: A4 portrait;
                margin: 30px 40px;
            }
        }
    </style>
</head>

<body>
    <center>
        @php
            $page = 1;
            $temp = 0;
            $row = 37;
            $page_total = 0;
            $product_pages = 0;
        @endphp
        @foreach ($zonewise_data as $zone => $products)
            <table>
                @php
                    $totalAmount = 0;
                @endphp
                @foreach ($products as $product_name => $data)
                    <div style="display: flex; align-items: center;">
                        <span class="fw-bold" style="font-size: 17px; font-weight: 600; text-align: start; flex: 33.33%;">
                            Date : {{ date('d / m / Y') }}
                        </span>
                        <div class="text-end p-0 m-0 mb-1" style="flex: 33.33%;">
                            Page : {{ $page }}
                        </div>
                    </div>
                    @php
                        $page_total = 0;
                        $product_pages = 0;
                        $grandtotal = 0;
                        $temp = -3;
                    @endphp
                    <table>
                        <thead>
                            <tr style="border-bottom: 2px solid black; border-top: 2px solid black;">
                                {{-- <th>1</th> --}}
                                <th colspan="4">{{ $product_name }}</th>
                                <th>{{ $zone }}</th>
                            </tr>
                            <tr style="border-bottom: 2px solid black">
                                <th style="width: 15%">Billing Date</th>
                                <th style="width: 15%">Customer PO</th>
                                <th style="width: 40%">Name of Sold to Party</th>
                                <th style="width: 10%">Qty.</th>
                                <th style="width: 20%">Vehicle Number</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data as $r)
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
                            <tr style="border-bottom: 2px solid black; border-top: 2px solid black;">
                                {{-- <th>2</th> --}}
                                <th colspan="4">{{ $product_name }}</th>
                                <th>{{ $zone }}</th>
                            </tr>
                            <tr style="border-bottom: 2px solid black">
                                <th style="width: 15%">Billing Date</th>
                                <th style="width: 15%">Customer PO</th>
                                <th style="width: 40%">Name of Sold to Party</th>
                                <th style="width: 10%">Qty.</th>
                                <th style="width: 20%">Vehicle Number</th>
                            </tr>
                        </thead>
                        <tbody>
                @endif
                <tr>
                    <td>{{ $r['Billing Date'] }}</td>
                    <td>{{ $r['Customer PO No'] }}</td>
                    <td>{{ $r['Name of Sold To Party'] }}</td>
                    <td align="end">{{ $r['Material Quantity'] }}</td>
                    <td align="end">{{ $r['Vehicle Number'] }}</td>
                </tr>
                @php
                    $page_total += (float) $r['Material Quantity'] ?? 0;
                    $totalAmount += (float) $r['Material Quantity'] ?? 0;
                    $temp++;
                @endphp
                @if ($temp == $row)
                    <tr>
                        <td style="border-top: 2px solid black;border-bottom: 2px solid black;bold;font-weight: bold"
                            colspan="3"></td>
                        <td style="border-top: 2px solid black;border-bottom: 2px solid black;font-weight: 600;font-size:13px"
                            class="text-end">
                            {{ formatIndianNumber($page_total, 2) ?? '-' }}
                        </td>
                        <td style="border-top: 2px solid black;border-bottom: 2px solid black;bold;font-weight: bold"></td>
                    </tr>
                </tbody>
            </table>
            @php
                $page++;
                $temp = -3;
                $page_total = 0;
                $product_pages++;
            @endphp
            <div class="conditional-page-break"></div>
            <div class="text-end p-0 m-0 mb-1"> Page : {{ $page }}</div>
            <table>
                <thead>
                    <tr style="border-bottom: 2px solid black; border-top: 2px solid black;">
                        {{-- <th>3</th> --}}
                        <th colspan="4">{{ $product_name }}</th>
                        <th>{{ $zone }}</th>
                    </tr>
                    <tr style="border-bottom: 2px solid black">
                        <th style="width: 15%">Billing Date</th>
                        <th style="width: 15%">Customer PO</th>
                        <th style="width: 40%">Name of Sold to Party</th>
                        <th style="width: 10%">Qty.</th>
                        <th style="width: 20%">Vehicle Number</th>
                    </tr>
                </thead>
                <tbody>
        @endif
        @endforeach
        @if ($product_pages > 0)
        <tr>
            <td style="border-top: 2px solid black;border-bottom: 2px solid black;bold;font-weight: bold"
                colspan="3"></td>
            <td style="border-top: 2px solid black;border-bottom: 2px solid black;font-weight: 600;font-size:13px"
                class="text-end">
                {{ formatIndianNumber($page_total, 2) ?? '-' }}
            </td>
            <td style="border-top: 2px solid black;border-bottom: 2px solid black;bold;font-weight: bold"></td>
        </tr>
        @endif
        <tr>
            <td style="border-top: 2px solid black;border-bottom: 2px solid black;bold;font-weight: bold"
                colspan="2"></td>
            <td style="border-top: 2px solid black;border-bottom: 2px solid black;bold;font-weight: bold">
                Total..</td>
            <td style="border-top: 2px solid black;border-bottom: 2px solid black;font-weight: 600;font-size:13px"
                class="text-end">
                {{ formatIndianNumber($totalAmount, 2) ?? '-' }}
            </td>
            <td style="border-top: 2px solid black;border-bottom: 2px solid black;bold;font-weight: bold"></td>
        </tr>
        @php
            $temp = 0;
            $page++;
        @endphp
        </tbody>
        </table>
        <div class="conditional-page-break"></div>
        @php
            $totalAmount = 0;
            $temp += 3;
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
                    <tr style="border-bottom: 2px solid black; border-top: 2px solid black;">
                        {{-- <th>4</th> --}}
                        <th colspan="4">{{ $product_name }}</th>
                        <th>{{ $zone }}</th>
                    </tr>
                    <tr style="border-bottom: 2px solid black">
                        <th style="width: 15%">Billing Date</th>
                        <th style="width: 15%">Customer PO</th>
                        <th style="width: 40%">Name of Sold to Party</th>
                        <th style="width: 10%">Qty.</th>
                        <th style="width: 20%">Vehicle Number</th>
                    </tr>
                </thead>
                <tbody>
        @endif
        @endforeach
        @endforeach
    </center>
</body>
<script>
    addEventListener("afterprint", (event) => {
        window.close();
    });
</script>

</html>
