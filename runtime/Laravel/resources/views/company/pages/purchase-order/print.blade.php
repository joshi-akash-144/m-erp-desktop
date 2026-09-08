<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Purchase Order Register</title>
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

        .header-meta {
            text-align: left;
            font-size: 12px;
            margin-bottom: 10px;
        }

        /* Utility Classes */
        .d-flex { display: flex; }
        .justify-content-between { justify-content: space-between; }
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
            padding: 2px 2px;
            vertical-align: top;
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
            margin-top: 1px;
            padding-right: 5px;
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
    @php
        $reportTitle = 'Purchase Order Register';
        $statusVal = request('currentFilter.order_status', 'all');
        if ($statusVal === 'all') $reportTitle = 'Purchase Order All Register';
        if ($statusVal === 'open') $reportTitle = 'Purchase Order Pending Register';
        if ($statusVal === 'close') $reportTitle = 'Purchase Order Complete Register';

        $page            = 1;
        $rowCountOnPage  = 0;
        $maxRowsPerPage  = 30;

        $orderqty_net_total    = 0;
        $recevingqty_net_total = 0;
        $balanceqty_net_total  = 0;

        // Table header definition (10 columns)
        $tableHeader = '
            <thead>
                <tr>
                    <th style="width:10%" class="ps-2">Date</th>
                    <th style="width:20%">Particular/Broker</th>
                    <th style="width:20%">Destination</th>
                    <th style="width:9%">Last Date</th>
                    <th style="width:20%">Product</th>
                    <th style="width:7%" class="text-end">Incl Rate</th>
                    <th style="width:7%" class="text-end">Rate</th>
                    <th style="width:7%" class="text-end">Order Qty</th>
                    <th style="width:7%" class="text-end">Rec.Qty</th>
                    <th style="width:7%" class="text-end">Balance</th>
                </tr>
            </thead>';
            
        // Group by supplier name
        $groupedData = collect($purchaseOrders ?? [])->groupBy(fn($po) => $po['account']['name'] ?? 'Unknown Supplier');
    @endphp

    <div class="print-container">
        <header class="print-header">
            <h3 class="report-company">{{ strtoupper($company->name ?? '') }}</h3>
            <h5 class="report-title">{{ $reportTitle }}</h5>
            <div class="report-date" style="width: 99%; margin-bottom: 10px;">
                <span style="float: right;"><b>Page : {{ $page }}</b></span>
                <div style="clear: both;"></div>
            </div>
        </header> 

        <main class="print-main">
            <table class="report-table">
                {!! $tableHeader !!}
                <tbody>
                    @php
                        $hasData = false;
                    @endphp
                    @if($groupedData->isNotEmpty())
                        @foreach ($groupedData as $supplierName => $orders)
                            @php
                                $hasData = true;
                                $total_qty           = 0;
                                $total_reciving_qty  = 0;
                                $total_remaining_qty = 0;

                                $needsPageBreak = ($rowCountOnPage > 0 && ($rowCountOnPage + 2) > $maxRowsPerPage);
                            @endphp
                            @if($needsPageBreak)
                                @php
                                    $page++;
                                    $rowCountOnPage = 0;
                                @endphp
                                </tbody></table>
                                <p class="continuation-text">cont. on page {{ $page }}...</p>
                                <div class="conditional-page-break"></div>
                                <div class="continuation-header"><p class="report-company">{{ strtoupper($company->name ?? '') }}</p><p class="page-info">Page {{ $page }}: <span class="report-title">{{ $reportTitle }}</span></p></div>
                                <table>{!! $tableHeader !!}<tbody>
                            @endif

                            <tr>
                                <td colspan="10" class="fw-bold ps-2">{{ $supplierName }}</td>
                            </tr>
                            @php $rowCountOnPage++; @endphp

                            @foreach ($orders as $po)
                                @php
                                    $order_date = !empty($po['order_date']) ? \Carbon\Carbon::parse($po['order_date'])->format('d-m-Y') : '';
                                    $due_date = !empty($po['due_date']) ? \Carbon\Carbon::parse($po['due_date'])->format('d-m-Y') : '';
                                    $broker = \Illuminate\Support\Str::limit($po['broker']['name'] ?? '', 20, '...');
                                    $destination = $po['destination']['name'] ?? '-';
                                @endphp
                                @foreach ($po['details'] ?? [] as $detail)
                                    @php
                                        $product = \Illuminate\Support\Str::limit($detail['item']['name'] ?? '-', 20, '...');
                                        
                                        $qty           = (float) ($detail['ordered_qty'] ?? 0);
                                        $receiving_qty = (float) ($detail['received_qty'] ?? 0);
                                        $remaining_qty = (float) ($detail['remaining_qty'] ?? 0);

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
                                        <div class="continuation-header"><p class="report-company">{{ strtoupper($company->name ?? '') }}</p><p class="page-info">Page {{ $page }}: <span class="report-title">{{ $reportTitle }}</span></p></div>
                                        <table>{!! $tableHeader !!}<tbody>
                                    @endif
                                    
                                    <tr>
                                        <td class="ps-2">{{ $order_date }}</td>
                                        <td>{{ $broker }}</td>
                                        <td>{{ $destination }}</td>
                                        <td>{{ $due_date }}</td>
                                        <td>{{ strlen($product) > 25 ? substr($product, 0, 25) . '...' : $product }}</td>
                                        <td class="text-end">{{ number_format($detail['inclusive_rate'] ?? 0, 2, ".", "") }}</td>
                                        <td class="text-end">{{ number_format($detail['rate'] ?? 0, 2, ".", "") }}</td>
                                        <td class="text-end">{{ number_format($qty, 3, '.', '') }}</td>
                                        <td class="text-end">{{ number_format($receiving_qty, 3, '.', '') }}</td>
                                        <td class="text-end">{{ number_format($remaining_qty, 3, '.', '') }}</td>
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
                                <div class="continuation-header"><p class="report-company">{{ strtoupper($company->name ?? '') }}</p><p class="page-info">Page {{ $page }}: <span class="report-title">{{ $reportTitle }}</span></p></div>
                                <table>{!! $tableHeader !!}<tbody>
                            @endif

                            {{-- Supplier subtotal --}}
                            <tr class="total-row">
                                <td class="text-end fw-bold" colspan="7">Total..</td>
                                <td class="text-end">{{ number_format($total_qty, 3, '.', '') }}</td>
                                <td class="text-end">{{ number_format($total_reciving_qty, 3, '.', '') }}</td>
                                <td class="text-end">{{ number_format($total_remaining_qty, 3, '.', '') }}</td>
                            </tr>

                            @php
                                $rowCountOnPage++;
                                $orderqty_net_total    += $total_qty;
                                $recevingqty_net_total += $total_reciving_qty;
                                $balanceqty_net_total  += $total_remaining_qty;
                            @endphp
                        @endforeach
                    @endif

                    @if (!$hasData)
                        <tr><td colspan="10" class="text-center">No records found</td></tr>
                    @else
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
                            <td class="text-end" colspan="7">Grand Total</td>
                            <td class="text-end">{{ function_exists('formatIndianNumber') ? formatIndianNumber($orderqty_net_total, 2) : number_format($orderqty_net_total, 2, '.', '') }}</td>
                            <td class="text-end">{{ function_exists('formatIndianNumber') ? formatIndianNumber($recevingqty_net_total, 3) : number_format($recevingqty_net_total, 3, '.', '') }}</td>
                            <td class="text-end">{{ function_exists('formatIndianNumber') ? formatIndianNumber($balanceqty_net_total, 3) : number_format($balanceqty_net_total, 3, '.', '') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </main>
    </div>
    
    <footer class="report-footer">
        <span class="footer-left">Prepared By: <strong>{{ auth()->user()->name ?? 'Admin' }}</strong></span>
        <span class="footer-right">Prepared On: <strong>{{ now()->format('d-m-Y H:i:s') }}</strong></span>
    </footer>

    <script>
        window.addEventListener("afterprint", () => window.close());
    </script>
</body>
</html>
