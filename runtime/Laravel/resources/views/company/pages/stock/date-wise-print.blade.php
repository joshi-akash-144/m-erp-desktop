<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Date Wise Stock Report - Print</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        /* ============================
        UNIVERSAL RESET
        ============================ */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Public Sans', 'Roboto', 'Arial', 'sans-serif';
            font-size: 13px;
            line-height: 1.4;
            color: #000;
            background-color: #fff;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        /* ============================
        PAGE MARGINS (with left space for filing)
        ============================ */
        @page {
            size: portrait;
        }

        body {
            padding-left: 5mm;
            min-height: 100vh;
        }

        /* ============================
        REPORT STRUCTURE
        ============================ */
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
        .report-date{
            font-size: 12px;
            font-weight: 700;
            margin: 3px 0 10px 0;
            padding: 0;
            letter-spacing: 1px;
        }
        .report-content {
            margin-top: 8px;
        }

        /* ============================
        ITEM HEADER
        ============================ */
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 10px 0 8px 0;
            padding: 2px 0;
            /* border-bottom: 2px solid #000; */
        }

        .item-name {
            font-size: 14px;
            font-weight: 700;
            flex: 1;
        }

        .opening-stock {
            font-size: 13px;
            font-weight: 600;
            text-align: right;
        }

        .closing-stock-row {
            background-color: #f9f9f9 !important;
            font-weight: 700;
            border-top: 2px solid #000 !important;
            border-bottom: 2px solid #000 !important;
        }

        /* ============================
        CONTINUATION ELEMENTS
        ============================ */
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

        /* ============================
        TABLE STYLES
        ============================ */
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
            border-top: 1.5px solid #000;
            border-bottom: 1px solid #000;
            background-color: #f4f4f4;
            padding: 4px 6px;
        }

        .report-table tbody td {
            border-bottom: 1px dotted #bbb;
            font-size: 13px;
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

        .grand-total-row{
            background-color: #ffffffff !important;
            font-weight: 700;
            border-top: 1px solid #000 !important;
            border-bottom: 1px solid #000 !important;
        }

        /* ============================
        FOOTER
        ============================ */
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
            width: 33.33%;
            text-align: left;
        }

        .report-footer .footer-center {
            float: left;
            width: 33.33%;
            text-align: center;
            font-weight: bold;
        }

        .report-footer .footer-right {
            float: right;
            width: 33.33%;
            text-align: right;
        }

        /* Flexbox fallback for modern browsers */
        @supports (display: flex) {
            .report-footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: nowrap;
            }

            .report-footer .footer-left {
                float: none;
                width: auto;
                flex: 0 0 auto;
            }

            .report-footer .footer-center {
                float: none;
                width: auto;
                flex: 0 0 auto;
                font-weight: bold;
                text-align: center;
            }

            .report-footer .footer-right {
                float: none;
                width: auto;
                flex: 0 0 auto;
                margin-left: auto;
            }
        }

        /* ============================
        UTILITY CLASSES
        ============================ */
        .w-100 {
            width: 100% !important;
        }

        .mt-2 {
            margin-top: 8px !important;
        }

        .p-0 {
            padding: 0 !important;
        }

        .m-0 {
            margin: 0 !important;
        }

        /* ============================
        PRINT STYLES
        ============================ */
        @media print {

            html,
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
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
                size: portrait;
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
                background-color: #f4f4f4 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .grand-total-row{
                background-color: #ffffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .closing-stock-row {
                background-color: #f9f9f9 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

        /* ============================
        BROWSER-SPECIFIC FIXES
        ============================ */
        /* Firefox specific fixes */
        @-moz-document url-prefix() {
            table {
                border-collapse: collapse;
            }
        }

        /* Chrome/Safari specific fixes */
        @media screen and (-webkit-min-device-pixel-ratio:0) {
            table {
                border-collapse: collapse;
            }
        }
    </style>
</head>

<body>
    @php
    $reportTitle = "Date Wise Stock Report";
    $orientation = $orientation ?? 'landscape';
    $page = 1;
    $rowCountOnPage = 0;

    // Adjust rows based on orientation
    if ($orientation === 'landscape') {
        $firstPageRows = 17;
        $otherPageRows = 19;
    } else {
        $firstPageRows = 42;
        $otherPageRows = 45;
    }
    
    // Calculate total number of rows
    $totalItems = count($DateWiseStock['data'] ?? []);
    // dd($DateWiseStock);
    @endphp
    
    <header class="print-header">
        <h3 class="report-company">{{ strtoupper($company->name ?? 'COMPANY NAME') }}</h3>
        <h6 class="report-subtitle">GSTIN: {{ $company->gst_number ?? '' }}</h6>
        <h5 class="report-title">{{ $reportTitle }}</h5>
        <h5 class="report-subtitle">
            @isset($filters['file_no'])
                File No: {{ $filters['file_no'] }}    
            @endisset
        </h5>
        @php
            $dateLabel = 'From: ' . ($filters['start_date'] ?? 'N/A') . '  To: ' . ($filters['end_date'] ?? 'N/A');
        @endphp
        <div class="report-date"><strong>{{ $dateLabel }}</strong></div>
    </header>
    
    <main class="print-main">
        <div class="report-content page-{{ $orientation }}">
            {{-- Item Header with Opening Stock --}}
            <div class="item-header">
                <div class="item-name">{{ $DateWiseStock['item_name'] ?? 'N/A' }}</div>
                <div class="opening-stock">Opening Stock: <strong>{{ formatIndianNumber(($DateWiseStock['opening_stock'] ?? 0),3) }}</strong> | Opening Amount: <strong>{{ formatIndianNumber(($DateWiseStock['opening_amount'] ?? 0)) }}</strong></div>
            </div>

            <table class="report-table">
                <thead>
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
                        $totalQtyIn = 0;
                        $totalQtyOut = 0;
                        $totalBalance = 0;
                        $processedItems = 0;
                        $srNo = 1;
                    @endphp

                    @forelse($DateWiseStock['data'] ?? [] as $item)
                        @php
                            $rowCountOnPage++;
                            $processedItems++;
                            $currentPageLimit = $page === 1 ? $firstPageRows : $otherPageRows;
                            
                            $qtyIn = $item['quantity_in'] ?? 0;
                            $qtyOut = $item['quantity_out'] ?? 0;
                            $balance = $item['balance'] ?? 0;
                            
                            $totalQtyIn += $qtyIn;
                            $totalQtyOut += $qtyOut;
                            $totalBalance += $balance;
                            
                            // Check if we need a page break BEFORE printing the row
                            $needsPageBreak = false;
                            
                            // If this is the last item, we need space for 3 more rows (current + 2 total rows)
                            if ($loop->last) {
                                $needsPageBreak = ($rowCountOnPage + 2) > $currentPageLimit;
                            } else {
                                // Just check current row
                                $needsPageBreak = $rowCountOnPage > $currentPageLimit;
                            }
                        @endphp

                        {{-- Insert page break if needed BEFORE the row --}}
                        @if ($needsPageBreak && $processedItems < $totalItems)
                            @php
                                $page++;
                                $rowCountOnPage = 1; // Reset to 1 for the current row
                            @endphp
                            </tbody>
                        </table>

                        <p class="continuation-text">cont. on page {{ $page }}...</p>
                        <div class="conditional-page-break"></div>

                        {{-- Next page header --}}
                        <div class="continuation-header">
                            <p class="company-name">{{ strtoupper($company->name ?? 'COMPANY NAME') }}</p>
                            <p class="page-info">
                                Page {{ $page }}: <span class="report-name">{{ $reportTitle }}</span>
                            </p>
                        </div>

                        {{-- Item Header on continuation page --}}
                        <div class="item-header">
                            <div class="item-name">{{ $DateWiseStock['item_name'] ?? 'N/A' }}</div>
                            <div class="opening-stock">Opening Stock: <strong>{{ formatIndianNumber(($DateWiseStock['opening_stock'] ?? 0),3) }}</strong> | Opening Amount: <strong>{{ formatIndianNumber(($DateWiseStock['opening_amount'] ?? 0)) }}</strong></div>
                        </div>

                        <table class="w-100 report-table">
                            <thead>
                               <tr>
                                    @foreach ($tableConfig['columns'] as $column)
                                    <th class="{{ $column['class'] ?? '' }} text-nowrap" style="width: {{ $column['width'] ?? 'auto' }};">
                                        {{ $column['label'] }}
                                    </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                        @endif

                        <tr>
                            <td class="text-start">{{ $srNo++ }}</td>
                            <td class="text-start d-inline-block text-truncate text-nowrap">{{ date('d-m-Y', strtotime($item['voucher_date'])) ?? '-' }}</td>
                            <td class="text-start d-inline-block text-truncate text-nowrap">{{ $item['voucher_type'] ?? '-' }}</td>
                            <td class="text-start d-inline-block text-truncate text-nowrap">{{ $item['voucher_bill_no'] ?? '-' }}</td>
                            <td class="text-start d-inline-block text-truncate text-nowrap">{{ $item['sales_invoice_number'] ?? '-' }}</td>
                            <td class="text-start d-inline-block text-truncate text-nowrap">{{ $item['supplier_name'] ?? '-' }}</td>
                            <td class="text-end">{{ formatIndianNumber($qtyIn,3) }}</td>
                            <td class="text-end">{{ formatIndianNumber($qtyOut,3) }}</td>
                            <td class="text-end">{{ formatIndianNumber($balance,3) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">
                                No records found
                            </td>
                        </tr>
                    @endforelse
                    
                    {{-- Total Row --}}
                    <tr class="grand-total-row">
                        <td colspan="6" class="text-end"><strong>Total</strong></td>
                        <td class="text-end"><strong>{{ formatIndianNumber($totalQtyIn,3) }}</strong></td>
                        <td class="text-end"><strong>{{ formatIndianNumber($totalQtyOut,3) }}</strong></td>
                        <td class="text-end"><strong>{{ formatIndianNumber($totalBalance,3) }}</strong></td>
                    </tr>

                    {{-- Closing Stock Row --}}
                    <tr class="closing-stock-row">
                        <td colspan="8" class="text-end"><strong>Closing Stock:</strong></td>
                        <td class="text-end"><strong>{{ formatIndianNumber(($DateWiseStock['closing_stock'] ?? 0),3) }}</strong></td>
                    </tr>
                    <tr class="closing-stock-row">
                        <td colspan="8" class="text-end"><strong>Closing Amount:</strong></td>
                        <td class="text-end"><strong>{{ formatIndianNumber(($DateWiseStock['closing_amount'] ?? 0)) }}</strong></td>
                    </tr>
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