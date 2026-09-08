<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Expense Register - Print</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Public Sans', Roboto, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.4;
            color: #000;
            background-color: #ffffffff;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        @page {
            size: {{ $orientation ?? 'landscape' }};
            margin: 10mm;
        }

        body {
            padding-left: 5mm;
            min-height: 100vh;
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

        .report-title {
            font-size: 16px;
            font-weight: 700;
            text-align: center;
            margin: 3px 0 10px 0;
            padding: 0;
            letter-spacing: 1px;
        }
        
        .report-date {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            font-weight: 700;
            margin: 3px 0 10px 0;
            padding: 0;
            letter-spacing: 1px;
        }

        .report-content {
            margin-top: 4px;
        }

        .continuation-text {
            text-align: right;
            font-size: 14px;
            margin: 5px 0 0 0;
            padding: 0;
        }

        .continuation-header {
            margin-bottom: 8px;
        }

        .company-name {
            font-size: 13px;
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 0;
        }

        .page-info {
            font-size: 13px;
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 0;
        }

        .report-name {
            letter-spacing: 2px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
            border-spacing: 0;
        }

        th,
        td {
            padding: 4px 6px;
            text-align: left;
            vertical-align: top;
        }

        thead {
            display: table-header-group;
        }

        tbody {
            display: table-row-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .report-table {
            width: 100%;
        }

        .report-table thead th {
            font-size: 13px;
            font-weight: 600;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            background-color: #ffffffff;
            padding: 4px 6px;
        }

        .report-table tbody td {
            border-bottom: 1px dotted #bbb;
            font-size: 12.5px;
            padding: 4px 6px;
        }

        .text-center {
            text-align: center !important;
        }

        .text-end {
            text-align: right !important;
        }

        .text-start {
            text-align: left !important;
        }

        .text-nowrap {
            white-space: nowrap;
        }

        .grand-total-row {
            background-color: #ffffffff !important;
            font-weight: 700;
            border-top: 1px solid #000 !important;
            border-bottom: 1px solid #000 !important;
        }

        .report-footer {
            font-size: 11.5px;
            font-style: italic;
            margin-top: 12px;
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

        @media print {
            html,
            body {
                margin: 0;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .conditional-page-break {
                page-break-after: always;
                break-after: always;
            }

            @page {
                size: {{ $orientation ?? 'landscape' }};
            }

            table {
                width: 100% !important;
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-footer-group;
            }

            .report-table thead th {
                background-color: #ffffffff !important;
            }

            .grand-total-row {
                background-color: #ffffffff !important;
            }
        }
    </style>
</head>

<body>
    @php
    $reportTitle = "Expense Register";
    $orientation = $orientation ?? 'landscape';
    
    if ($orientation === 'landscape') {
        $firstPageRows = 22;
        $otherPageRows = 23;
    } else {
        $firstPageRows = 30;
        $otherPageRows = 35;
    }
    
    $items = $expenseData ?? [];
    $totalItems = count($items);

    $grandTotal = 0;
    foreach($items as $item) {
        $grandTotal += (float)($item->amount ?? $item['amount'] ?? 0);
    }

    $tempPage = 1;
    $tempRowCount = 1;
    foreach($items as $index => $item) {
        $tempRowCount++;
        $limit = $tempPage === 1 ? $firstPageRows : $otherPageRows;
        $needsBreak = false;
        if ($index === $totalItems - 1) {
            if (($tempRowCount + 1) > $limit) {
                $needsBreak = true;
            }
        } else {
            if ($tempRowCount > $limit) {
                $needsBreak = true;
            }
        }
        if ($needsBreak) {
            $tempPage++;
            $tempRowCount = 1;
        }
    }
    $totalPages = $tempPage;
    $page = 1;
    @endphp
    
    <header class="print-header">
        <h3 class="report-company">{{ strtoupper($company->name ?? 'COMPANY NAME') }}</h3>
        <h2 class="report-title">{{ $reportTitle }}</h2>
    </header>
    
    <main class="print-main">
        <div class="report-content">
            <table class="report-table">
                <thead>
                    <tr class="balance-row">
                        <td colspan="3" class="text-start">
                            <strong>
                                Date: 
                                {{ !empty($filters['start_date']) ? format_date($filters['start_date']) : '' }}
                                to
                                {{ !empty($filters['end_date']) ? format_date($filters['end_date']) : '' }}
                            </strong>
                        </td>
                        <td colspan="3" class="text-end">
                            @if(!empty($expenseAccountName))
                                <strong>
                                    Expense Type: 
                                    {{ !empty($expenseAccountName) ? $expenseAccountName : '' }}
                                </strong>
                            @endif                            
                        </td>
                        <td colspan="3" class="text-end"></td>
                    </tr>
                    <tr>
                        @foreach ($tableConfig['columns'] as $column)
                        <th class="{{ $column['class'] ?? '' }} text-nowrap" style="width: {{ $column['width'] ?? 'auto' }};">
                            {{ $column['label'] }}
                        </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @php
                        $processedItems = 0;
                        $rowCountOnPage = 1;
                    @endphp

                    @forelse($items as $item)
                        @php
                            $item = (object) $item;
                            $rowCountOnPage++;
                            $processedItems++;
                            $currentPageLimit = $page === 1 ? $firstPageRows : $otherPageRows;
                            
                            $needsPageBreak = false;
                            if ($loop->last) {
                                if (($rowCountOnPage + 1) > $currentPageLimit) {
                                    $needsPageBreak = true;
                                }
                            } else {
                                if ($rowCountOnPage > $currentPageLimit) {
                                    $needsPageBreak = true;
                                }
                            }
                        @endphp

                        @if ($needsPageBreak)
                            @php
                                $page++;
                                $rowCountOnPage = 1; 
                            @endphp
                            </tbody>
                        </table>

                        <p class="continuation-text">cont. on page {{ $page }}...</p>
                        <div class="conditional-page-break"></div>

                        <div class="continuation-header">
                            <p class="company-name">{{ strtoupper($company->name ?? 'COMPANY NAME') }}</p>
                            <p class="page-info">
                                Page {{ $page }} of {{ $totalPages }}: <span class="report-name">{{ $reportTitle }}</span> 
                            </p>
                        </div>

                        <table class="w-100 report-table">
                            <thead>
                                <tr>
                                    @foreach ($tableConfig['columns'] as $column)
                                    <th class="{{ $column['class'] ?? '' }} text-nowrap" style="width: {{ $column['width'] }};">
                                        {{ $column['label'] }}
                                    </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                        @endif

                        <tr>
                            <td class="text-start text-nowrap" style="width: 8%;">{{ $item->voucher_date ?? '-' }}</td>
                            <td class="text-center text-nowrap" style="width: 10%;">{{ $item->voucher_serial ?? '-' }}</td>
                            <td class="text-start" style="width: 30%;">{{ $item->party_name ?? '-' }}</td>
                            <td class="text-center text-nowrap" style="width: 15%;">{{ $item->reference_number ?? '-' }}</td>
                            <td class="text-start" style="width: 17%;">{{ $item->account_name ?? '-' }}</td>
                            <td class="text-start" style="width: 15%;">{{ $item->expense_account ?? '-' }}</td>
                            <td class="text-end text-nowrap" style="width: 10%;">{{ formatIndianNumber($item->amount ?? 0) }}</td>
                        </tr>

                        @if($loop->last)
                            <tr class="grand-total-row">
                                <td colspan="5" class="text-end"></td>
                                <td class="text-end"><strong>Total</strong></td>
                                <td class="text-end text-nowrap"><strong>{{ formatIndianNumber($item->amount ?? 0 ? $grandTotal : 0) }}</strong></td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">
                                No records found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
    
    <footer class="report-footer">
        <span class="footer-left">Prepared By: <strong>{{ auth()->user()->name ?? 'User' }}</strong></span>
        <span class="footer-right">Prepared On: <strong>{{ now()->format('d-m-Y H:i:s') }}</strong></span>
    </footer>

    <script>
        window.addEventListener("afterprint", () => window.close());
    </script>
</body>

</html>
