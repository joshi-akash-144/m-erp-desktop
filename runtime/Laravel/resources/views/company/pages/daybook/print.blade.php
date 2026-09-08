<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Day Book Report - Print</title>
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Public Sans', Roboto, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.4;
            color: #000;
            background-color: #ffffffff;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        /* ============================
   PAGE MARGINS (with left space for filing)
============================ */
        @page {
            size: {{ $orientation ?? 'landscape' }};
            margin: 10mm;
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
            display: flex;
            align-daybooks: center;
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

        .report-table tbody tr.balance-row td {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-weight: bold;
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
        @supports (display: 'flex') {
            .report-footer {
                display: flex;
                justify-content: space-between;
                align-daybooks: center;
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
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .grand-total-row{
                background-color: #ffffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .text-truncate {
                overflow: hidden;
                white-space: nowrap;
                text-overflow: ellipsis;
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
    $reportTitle = "Day Book Report";
    $orientation = $orientation ?? 'portrait';
    
    // Adjust rows based on orientation
    if ($orientation === 'landscape') {
        $firstPageRows = 20;
        $otherPageRows = 23;
    } else {
        $firstPageRows = 34;
        $otherPageRows = 35;
    }
    
    // Calculate total number of rows
    $totaldaybooks = count($daybooks);

    // Calculate total pages for "Page X of Y"
    $tempPage = 1;
    $tempRowCount = 1; // Count opening balance row
    foreach($daybooks as $index => $daybook) {
        $tempRowCount++;
        $limit = $tempPage === 1 ? $firstPageRows : $otherPageRows;
        $needsBreak = false;
        if ($index === $totaldaybooks - 1) {
            // Last daybook needs 2 extra rows (Grand Total + Closing Balance)
            if (($tempRowCount + 2) > $limit) {
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
        <h3 class="report-company">{{ strtoupper($company->name) }}</h3>
        <h6 class="report-subtitle">GSTIN: {{ $company->gst_number ?? '' }}</h6>
        <h5 class="report-title">{{ $reportTitle }}</h5>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
            <tr>
                <td style="text-align: left; padding: 0; border: none;">
                    <b>Date Period : {{ $datePeriod }}</b>             
                </td>
                <td style="text-align: right; padding: 0; border: none;">
                    <b>Page : </b> {{ $page }}
                </td>
            </tr>
        </table>
    </header>
    
    <main class="print-main">
        <div class="report-content">
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
                        $processeddaybooks = 0;
                        $rowCountOnPage = 1; // Count opening balance row
                    @endphp

                    @forelse($daybooks as $daybook)
                        @php
                            $daybook = (object) $daybook;
                            $rowCountOnPage++;
                            $processeddaybooks++;
                            $currentPageLimit = $page === 1 ? $firstPageRows : $otherPageRows;
                            
                            // Check if we need a page break BEFORE printing the row
                            $needsPageBreak = false;
                            
                            if ($loop->last) {
                                // Last daybook needs 2 extra rows (Grand Total + Closing Balance)
                                if (($rowCountOnPage + 2) > $currentPageLimit) {
                                    $needsPageBreak = true;
                                }
                            } else {
                                if ($rowCountOnPage > $currentPageLimit) {
                                    $needsPageBreak = true;
                                }
                            }
                        @endphp

                        {{-- Insert page break if needed BEFORE the row --}}
                        @if ($needsPageBreak)
                            @php
                                $page++;
                                $rowCountOnPage = 1; 
                            @endphp
                            </tbody>
                        </table>

                        <p class="continuation-text">cont. on page {{ $page }}...</p>
                        <div class="conditional-page-break"></div>

                        {{-- Next page header --}}
                        <div class="continuation-header">
                            <p class="company-name">{{ strtoupper($company->name) }}</p>
                            <p class="page-info">
                                Page {{ $page }}: <span class="report-name">{{ $reportTitle }}</span>
                            </p>
                        </div>

                        <table class="w-100 report-table" style="table-layout: fixed; width: 100%;">
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

                        @php
                            // $runningBalance = isset($daybook->running_balance) ? $daybook->running_balance : 0;
                        @endphp
                        <tr>
                            <td class="text-start text-nowrap" style="width: 12%;">{{ $daybook->voucher_date ? format_date($daybook->voucher_date) : '' }}</td>
                                @if(!isset($daybook->row_type))
                                    <td class="text-center text-nowrap" style="width: 10%;">{{ $daybook->voucher_type ?? '' }}</td>
                                    <td class="text-center text-nowrap" style="width: 10%;">{{ $daybook->voucher_serial ?? '' }}</td>
                                    <td class="text-start text-nowrap text-truncate" style="width: 30%;max-width: 0;">
                                        {{ $daybook->account_name ?? '' }}
                                    </td>
                                    <td class="text-end text-nowrap" style="width: 12%;">{{ $daybook->debit ? format_number($daybook->debit) : '' }}</td>
                                    <td class="text-end text-nowrap" style="width: 12%;">{{ $daybook->credit ? format_number($daybook->credit) : '' }}</td>
                                @else
                                    <td class="text-center text-nowrap" style="width: 10%;"></td>
                                    <td class="text-center text-nowrap" style="width: 10%;"></td>
                                    <td class="text-start text-nowrap text-truncate" style="max-width: 0;" colspan="3">
                                        <span style="font-style: italic; color: #555;">
                                            {{ $daybook->narration ? '( ' . $daybook->narration . ' )' : '' }}
                                        </span>
                                    </td>
                                @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">
                                No records found
                            </td>
                        </tr>
                    @endforelse
                    

                    {{-- Grand Total Row --}}
                    <tr class="grand-total-row">
                        <td colspan="1" class="text-end"></td>
                        <td colspan="2" class="text-end"></td>
                        <td colspan="1" class="text-end"><strong>Total</strong></td>
                        <td colspan="1" class="text-end"><strong>{{ number_format($totalDebit ?? 0, 2) }}</strong></td>
                        <td colspan="1" class="text-end"><strong>{{ number_format($totalCredit ?? 0, 2) }}</strong></td>
                        <td colspan="1" class="text-end"></td>
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