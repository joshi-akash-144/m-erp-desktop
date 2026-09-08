@php use Carbon\Carbon; @endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Sales Order Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .print-container {
            width: 100%;
            padding: 10px;
        }

        .report-company {
            font-size: 21px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 2px 0;
            padding: 0;
        }

        .report-subtitle {
            font-size: 12px;
            text-align: center;
            line-height: 1.2;
            margin: 0 0 2px 0;
            padding: 0;
        }

        .report-title {
            font-size: 16px;
            font-weight: 700;
            text-align: center;
            margin: 3px 0 10px 0;
            padding: 0;
            letter-spacing: 1px;
        }

        .report-content {
            margin-top: 8px;
        }

        /* Utility Classes */
        .d-flex { display: flex; }
        .align-items-center { align-items: center; }
        .position-relative { position: relative; }
        .position-absolute { position: absolute; }
        .w-100 { width: 100%; }
        .text-center { text-align: center; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-top: 10px;
        }

        table, th, td { border: none; }

        thead tr th {
            border-top: 1px solid black;
            border-bottom: 1px solid black;
            padding: 5px 2px;
            text-align: left;
            font-weight: bold;
        }

        tbody tr td {
            padding: 3px 2px;
            vertical-align: top;
        }

        /* Dashed separator on first detail row of each new SO */
        .so-first-row td {
            border-top: 1px dashed #aaa;
        }

        .total-row td {
            border-top: 1px solid black;
            border-bottom: 1px solid black;
            padding: 2px 2px;
            padding-right: 15px;
        }

        .fw-bold {
            font-weight: 600;
        }

        .grand-total-row td {
            border-top: 1px solid black;
            border-bottom: 3px double black;
            font-weight: bold;
            padding: 6px 2px;
            padding-right: 15px;
        }

        .text-end {
            text-align: right;
            padding-right: 15px;
        }

        .ps-2 { padding-left: 8px; }

        @page {
            size: landscape;
            margin: 10mm;
        }

        @media print {
            .no-print { display: none; }
        }

        .conditional-page-break {
            page-break-after: always;
        }

        .continuation-text {
            text-align: right;
            font-size: 10px;
            margin-top: 5px;
            margin-right: 5px;
        }

        .continuation-header {
            margin-bottom: 10px;
            text-align: center;
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
            width: 99%;
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
    @php
        $reportTitle     = 'Sales Order Register';
        $orientation     = $orientation ?? 'landscape';
        $page            = 1;
        $rowCountOnPage  = 0;
        $maxRowsPerPage  = 29;

        $order_qty_net_total     = 0;
        $receiving_qty_net_total = 0;
        $balance_qty_net_total   = 0;

        // Group by customer name
        $groupedData = collect($salesOrders)->groupBy(fn($so) => $so['account']['name']);

        // Table header definition (12 columns)
        $tableHeader = '
            <thead>
                <tr>
                    <th style="width:5%">So No.</th>
                    <th style="width:5%">PO No</th>
                    <th style="width:7%">Po date</th>
                    <th style="width:20%">Customer</th>                    
                    <th style="width:14%">Items</th>
                    <th style="width:6%">Destination</th>
                    <th style="width:6%">Condition</th>
                    <th style="width:7%" class="text-end">Incl Rate</th>
                    <th style="width:6%" class="text-end">Qty</th>                    
                    <th style="width:7%" class="text-end">Rate</th>
                    <th style="width:6%" class="text-end">Rec.Qty</th>
                    <th style="width:7%" class="text-end">Rem.Qty</th>
                </tr>
            </thead>';
    @endphp

    <div class="print-container">
        <header class="print-header">
            <h3 class="report-company">{{ strtoupper($company->name) }}</h3>
            <h6 class="report-subtitle">GSTIN: {{ $company->gst_number ?? '' }}</h6>
            <h5 class="report-title">{{ $reportTitle }}</h5>
            <div class="report-date" style="width: 99%; margin-bottom: 10px;">
                <div style="float: left; width: 50%; text-align: left;">
                    <b>Date Period : {{ $datePeriod }}</b> 
                </div>
                <div style="float: right; width: 50%; text-align: right;">
                    <b>Page : {{ $page }} </b> 
                </div>
                <div style="clear: both;"></div>
            </div>
        </header>

        <main class="print-main">
            <table class="report-table">
                {!! $tableHeader !!}
                <tbody>
                    @forelse ($groupedData as $customerName => $orders)
                        @php
                            $total_qty           = 0;
                            $total_reciving_qty  = 0;
                            $total_remaining_qty = 0;

                            // Page break before customer group if page is nearly full
                            $needsPageBreak = ($rowCountOnPage > 0 && ($rowCountOnPage + 1) > $maxRowsPerPage);
                        @endphp
                        @if($needsPageBreak)
                            @php
                                $page++;
                                $rowCountOnPage = 0;
                            @endphp
                            </tbody></table>
                            <p class="continuation-text">cont. on page {{ $page }}...</p>
                            <div class="conditional-page-break"></div>
                            <div class="continuation-header"><p class="company-name">{{ strtoupper($company->name) }}</p><p class="page-info">Page {{ $page }}: <span class="report-name">{{ $reportTitle }}</span></p></div>
                            <table>{!! $tableHeader !!}<tbody>
                        @endif
                        @php $rowCountOnPage++; @endphp

                        @foreach ($orders as $so)
                            @php
                                $order_date  = format_date($so['delivery_date']);
                                // $due_date    = format_date($so['due_date']);
                                // $broker      = $so['broker']['name'] ?? '-';
                                $po_no       = $so['purchase_order_number'] ?? $so['order_serial'] ?? '-';
                                $so_customer = $so['account']['name'] ?? '-';
                            @endphp

                            @foreach ($so['details'] as $detailIndex => $detail)
                                @php
                                    $isFirstDetail = ($detailIndex === 0);

                                    $product       = $detail['item']['name'] ?? '-';
                                    $qty           = (float) $detail['ordered_qty'];
                                    $receiving_qty = (float) $detail['received_qty'];
                                    $remaining_qty = (float) $detail['remaining_qty'];

                                    $total_qty           += $qty;
                                    $total_reciving_qty  += $receiving_qty;
                                    $total_remaining_qty += $remaining_qty;

                                    $needsDetailBreak = ($rowCountOnPage >= $maxRowsPerPage);
                                @endphp
                                @if($needsDetailBreak)
                                    @php
                                        $page++;
                                        $rowCountOnPage = 0;
                                    @endphp
                                    </tbody></table>
                                    <p class="continuation-text">cont. on page {{ $page }}...</p>
                                    <div class="conditional-page-break"></div>
                                    <div class="continuation-header"><p class="company-name">{{ strtoupper($company->name) }}</p><p class="page-info">Page {{ $page }}: <span class="report-name">{{ $reportTitle }}</span></p></div>
                                    <table>{!! $tableHeader !!}<tbody>
                                @endif

                                {{-- First detail row gets a dashed separator and SO-level data --}}
                                <tr>
                                    {{-- SO-level columns: only on first detail row --}}
                                    {{-- <td class="ps-2">{{ $isFirstDetail ? $order_date : '' }}</td>
                                    <td>{{ $isFirstDetail ? $po_no : '' }}</td>
                                    <td style="max-width:80px">{{ $isFirstDetail ? (strlen($so_customer) > 25 ? substr($so_customer, 0, 25) . '…' : $so_customer) : '' }}</td>
                                    <td style="max-width:50px">{{ $isFirstDetail ? $broker : '' }}</td>
                                    <td>{{ $isFirstDetail ? $due_date : '' }}</td>
                                  
                                    <td style="max-width:80px">{{ $product }}</td>
                                    <td class="text-end">{{ number_format($detail['inclusive_rate'], 2) }}</td>
                                    <td class="text-end">{{ number_format($detail['rate'], 2) }}</td>
                                    <td class="text-end">{{ number_format($qty, 3) }}</td>
                                    <td class="text-end">{{ number_format($receiving_qty, 3) }}</td>
                                    <td class="text-end">{{ number_format($remaining_qty, 3) }}</td> --}}


                                    <td class="ps-2">{{ $isFirstDetail ? ($so['order_number'] ?? $so['order_serial']) : '' }}</td>
                                    <td>{{ $isFirstDetail ? $po_no : '' }}</td>
                                    <td>{{ $isFirstDetail ? $order_date : '' }}</td>
                                    <td style="max-width:85px">{{ $isFirstDetail ? (strlen($so_customer) > 20 ? substr($so_customer, 0, 25) . '…' : $so_customer) : '' }}</td>                                                               
                                    <td style="max-width:80px">{{ strlen($product) > 15 ? substr($product, 0, 15) . '…' : $product }}</td>
                                    <td>{{ $detail['destination']['name'] ?? '-' }}</td>
                                    <td>{{ $detail['condition']['name'] ?? '-' }}</td>
                                    <td class="text-end">{{ number_format($detail['inclusive_rate'], 2) }}</td>
                                    <td class="text-end">{{ number_format($detail['rate'], 2) }}</td>
                                    <td class="text-end">{{ number_format($qty, 3) }}</td>
                                    <td class="text-end">{{ number_format($receiving_qty, 3) }}</td>
                                    <td class="text-end">{{ number_format($remaining_qty, 3) }}</td>
                                </tr>

                                @php $rowCountOnPage++; @endphp
                            @endforeach
                        @endforeach

                        @php
                            $needsTotalBreak = ($rowCountOnPage >= $maxRowsPerPage);
                        @endphp
                        @if($needsTotalBreak)
                            @php
                                $page++;
                                $rowCountOnPage = 0;
                            @endphp
                            </tbody></table>
                            <div class="conditional-page-break"></div>
                            <div class="continuation-header"><p class="company-name">{{ strtoupper($company->name) }}</p><p class="page-info">Page {{ $page }}: <span class="report-name">{{ $reportTitle }}</span></p></div>
                            <table>{!! $tableHeader !!}<tbody>
                        @endif

                        {{-- Customer subtotal --}}
                        <tr class="total-row">
                            <td class="text-end fw-bold" colspan="9">Total..</td>
                            <td class="text-end">{{ number_format($total_qty, 3) }}</td>
                            <td class="text-end">{{ number_format($total_reciving_qty, 3) }}</td>
                            <td class="text-end">{{ number_format($total_remaining_qty, 3) }}</td>
                        </tr>

                        @php
                            $rowCountOnPage++;
                            $order_qty_net_total    += $total_qty;
                            $receiving_qty_net_total += $total_reciving_qty;
                            $balance_qty_net_total  += $total_remaining_qty;
                        @endphp
                    @empty
                        <tr><td colspan="10" class="text-center">No records found</td></tr>
                    @endforelse

                    @if ($groupedData->count())
                        @php
                            $needsGrandTotalBreak = ($rowCountOnPage >= $maxRowsPerPage);
                        @endphp
                        @if($needsGrandTotalBreak)
                            @php
                                $page++;
                                $rowCountOnPage = 0;
                            @endphp
                            </tbody></table>
                            <div class="conditional-page-break"></div>
                            <table>{!! $tableHeader !!}<tbody>
                        @endif
                        <tr class="grand-total-row">
                            <td class="text-end" colspan="9">Grand Total</td>
                            <td class="text-end">{{ number_format($order_qty_net_total, 3) }}</td>
                            <td class="text-end">{{ number_format($receiving_qty_net_total, 3) }}</td>
                            <td class="text-end">{{ number_format($balance_qty_net_total, 3) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </main>
        <footer class="report-footer">
            <span class="footer-left">Prepared By: <strong>{{ auth()->user()->name }}</strong></span>
            <span class="footer-right">Prepared On: <strong>{{ now()->format('d-m-Y H:i:s') }}</strong></span>
        </footer>
    </div>

    <script>
        window.addEventListener("afterprint", () => window.close());
    </script>
</body>
</html>