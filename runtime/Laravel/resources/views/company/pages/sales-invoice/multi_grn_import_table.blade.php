<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: sans-serif;
            font-size: 25px;
        }

        .container,
        .container-fluid,
        .container-xxl,
        .container-xl,
        .container-lg,
        .container-md,
        .container-sm {
            width: 100%;
            padding-right: var(--bs-gutter-x, 0.75rem);
            padding-left: var(--bs-gutter-x, 0.75rem);
            margin-right: auto;
            margin-left: auto;
        }

        .row {
            --bs-gutter-x: 1.5rem;
            --bs-gutter-y: 0;
            display: flex;
            flex-wrap: wrap;
            margin-top: calc(-1 * var(--bs-gutter-y));
            margin-right: calc(-0.5 * var(--bs-gutter-x));
            margin-left: calc(-0.5 * var(--bs-gutter-x));
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
        }

        /* table thead tr th {
            border-top: 2px solid black;
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

        .fw-bold {
            font-weight: 700 !important;
        }

        .payment-advice tr td {
            padding: 2px !important;
            padding-bottom: 0 !important;
        }

        .p-1 {
            padding: 3px;
        }

        .p-2 {
            padding: 10px;
        }

        @media print {
            body {
                margin: 0;
            }

            @page {
                size: landscape;
            }

            .payment-advice tr td {
                padding: 2px !important;
                padding-bottom: 0 !important;
            }

            /* Remove header */
            @page {
                margin-top: 30px !important;
            }

            /* Remove footer */
            @page {
                margin-bottom: 30px !important;
            }

            /* Optionally, you can also hide any specific elements by their class or ID */
            .header-class,
            .footer-class {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="row">
        <div class="container">            
            @if (!empty($row_array))
                @foreach ($row_array as $row)
                    <table>
                        <thead>
                            <tr>
                                <td style="font-size:18px;" class="text-center p-1" colspan="8">
                                    {{ !empty($vendor) ? $vendor->name : 'N/A' }}
                                </td>
                            </tr>
                            <tr>
                                <td style="font-size:18px;" class="text-center p-1" colspan="7">CATTLE FEED RAW
                                    MATERIAL INWORD NOTE</td>
                                <td style="font-size:16px;" class="text-center p-1"><b>{{ $row['plant'] }}</b></td>
                            </tr>
                            <tr>
                                <td style="font-size:16px;" class="text-center p-1" colspan="8">Date :</td>
                            </tr>
                            <tr>
                                <td style="font-size:16px;" class="text-left p-1">INWORD NO</td>
                                <td style="font-size:16px;" class="text-left p-1">{{ $row['inward_no'] }}</td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"><b>{{ $row['plant'] }}</b></td>
                                <td style="font-size:16px;" class="text-left p-1">ISO DOC NO</td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                            </tr>
                            <tr>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1">OUT WORD DOC</td>
                                <td style="font-size:16px;" class="text-left p-1"><b>{{ $row['plant'] }}</b></td>
                                <td style="font-size:16px;" class="text-left p-1">INWORD DATE</td>
                                <td style="font-size:16px;" class="text-left p-1">
                                    {{-- {{ isset($row['truck_inward_date']) && !empty($row['truck_inward_date']) ? removeSpaceInDate(dateReFormat($row['truck_inward_date'])) : '' }} --}}
                                    {{-- {{ isset($row['truck_inward_date']) && !empty($row['truck_inward_date']) ? $row['truck_inward_date'] : '' }} --}}
                                    {{ isset($row['truck_inward_date']) && !empty($row['truck_inward_date']) ? (new DateTime($row['truck_inward_date']))->format('d-m-Y') : '' }}
                                </td>
                            </tr>
                            <tr>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1">IN TIME</td>
                                <td style="font-size:16px;" class="text-center p-1">0</td>
                            </tr>
                            <tr>
                                <td style="font-size:16px;" class="text-left p-1">PO NO</td>
                                <td style="font-size:16px;" class="text-left p-1">{{ $row['p_o_no'] }}</td>
                                <td style="font-size:16px;" class="text-left p-1">PO DATE</td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"><b>{{ $row['plant'] }}</b></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1">OUT TIME</td>
                                <td style="font-size:16px;" class="text-center p-1">0</td>
                            </tr>
                            <tr>
                                <td style="font-size:16px;" class="text-left p-2">&nbsp;</td>
                                <td style="font-size:16px;" class="text-left p-2"></td>
                                <td style="font-size:16px;" class="text-left p-2"></td>
                                <td style="font-size:16px;" class="text-left p-2"></td>
                                <td style="font-size:16px;" class="text-left p-2"></td>
                                <td style="font-size:16px;" class="text-left p-2"></td>
                                <td style="font-size:16px;" class="text-left p-2"></td>
                                <td style="font-size:16px;" class="text-left p-2"></td>
                            </tr>
                            <tr>
                                <td style="font-size:16px;" class="text-left p-1">VENDOR'S NAME</td>
                                <td style="font-size:16px;" class="text-left p-1" colspan="5">
                                    {{ $row['vendor_name'] }}</td>
                                <td style="font-size:16px;" class="text-left p-1">TRUCK NO</td>
                                <td style="font-size:16px;" class="text-left p-1">{{ $row['truck_no'] }}</td>
                            </tr>
                            <tr>
                                <td style="font-size:16px;" class="text-left p-2">&nbsp;</td>
                                <td style="font-size:16px;" class="text-left p-2"></td>
                                <td style="font-size:16px;" class="text-left p-2"></td>
                                <td style="font-size:16px;" class="text-left p-2"></td>
                                <td style="font-size:16px;" class="text-left p-2"></td>
                                <td style="font-size:16px;" class="text-left p-2"></td>
                                <td style="font-size:16px;" class="text-left p-2"></td>
                                <td style="font-size:16px;" class="text-left p-2"></td>
                            </tr>
                            <tr>
                                <td style="font-size:16px;" class="text-left p-1">CHALLAN NO</td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1">GROSS WEIGHT(KG)</td>
                                <td style="font-size:16px;" class="text-end p-1">{{ $row['gross_wt'] }}</td>
                            </tr>
                            <tr>
                                <td style="font-size:16px;" class="text-left p-1">CHALLAN DATE</td>
                                <td style="font-size:16px;" class="text-left p-1">
                                    {{-- {{ isset($row['truck_inward_date']) && !empty($row['truck_inward_date']) ? removeSpaceInDate(dateReFormat($row['truck_inward_date'])): '' }} --}}

                                    {{ isset($row['truck_inward_date']) && !empty($row['truck_inward_date']) ? $row['truck_inward_date'] : '' }}
                                </td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1">TARE WEIGHT</td>
                                <td style="font-size:16px;" class="text-end p-1">{{ $row['tare_wt'] }}</td>
                            </tr>
                            <tr>
                                <td style="font-size:16px;" class="text-left p-1">CHALLAN ONTY</td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                            </tr>
                            <tr>
                                <td style="font-size:16px;" class="text-left p-1">CHALLAN BAGS</td>
                                <td style="font-size:18px;" class="text-left p-1"><b>{{ $row['no_of_bag'] }}</b></td>
                                <td style="font-size:16px;" class="text-left p-1">BAG TYPE</td>
                                <td style="font-size:16px;" class="text-left p-1">0</td>
                                <td style="font-size:16px;" class="text-left p-1">AV WT PER KG</td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1">NETWEIGHT WITH <br> (KG)</td>
                                <td style="font-size:16px;" class="text-end p-1">
                                    {{ (int) $row['gross_wt'] - (int) $row['tare_wt'] }}</td>
                            </tr>
                            <tr>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1">MATERIAL DOCUMENT</td>
                                <td style="font-size:16px;" class="text-left p-1">{{ $row['material_doc_no'] }}
                                </td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                            </tr>
                            <tr>
                                <td style="font-size:16px;" class="text-left p-1">MATERIAL</td>
                                <td style="font-size:16px;" class="text-left p-1" colspan="3">
                                    {{ $row['material_desc'] }}
                                </td>
                                <td style="font-size:16px;" class="text-left p-1">NET WEIGHT WITHOUT BAG(KG)</td>
                                <td style="font-size:18px;" class="text-left p-1"><b>{{ $row['nt_wt_wo_bag'] }}</b>
                                </td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                            </tr>
                            <tr>
                                <td style="font-size:16px;" class="text-left p-1">DAMAGE BAGS >>>>>></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                            </tr>
                            <tr>
                                <td style="font-size:16px;" class="text-left p-1">ENTRY NO</td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1" colspan="2">TOTAL BAGS</td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1">MATERIAL <br> QNTY, (KG)</td>
                            </tr>
                            <tr>
                                <td style="font-size:16px;" class="text-left p-1">18</td>
                                <td style="font-size:16px;" class="text-left p-1"><b>{{ $row['plant'] }}</b></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-left p-1"><b
                                        style="font-size: 30px;">{{ $row['no_of_bag'] }}</b>
                                </td>
                                <td style="font-size:16px;" class="text-left p-1"></td>
                                <td style="font-size:16px;" class="text-end p-1">
                                    <b style="font-size: 30px;">{{ $row['nt_wt_wo_bag'] }}
                                        {{-- {{ (int) $row['gross_wt'] - (int) $row['tare_wt'] }} --}}</b>
                                </td>
                            </tr>
                        </thead>
                    </table>
                    <div class="conditional-page-break"></div>
                @endforeach
            @endif
        </div>
    </div>
</body>

<script>
    window.print();
    addEventListener("afterprint", (event) => {
        window.close();
    });
</script>

</html>
