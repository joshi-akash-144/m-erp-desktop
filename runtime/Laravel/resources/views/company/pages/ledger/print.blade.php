<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Ledger Report - Print</title>
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
            background-color: #f4f4f4;
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
    $reportTitle = "Ledger Report";
    $orientation = $orientation ?? 'portrait';
    $page = 1;
    $rowCountOnPage = 0;

    // Adjust rows based on orientation
    if ($orientation === 'landscape') {
        $firstPageRows = 18;
        $otherPageRows = 20;
    } else {
        $firstPageRows = 30;
        $otherPageRows = 34;
    }
    
    // Calculate total number of rows
    $transactions = $ledgerReport['data'] ?? [];
    $totalItems = count($transactions);
    @endphp
    
    <header class="print-header">
        <h3 class="report-company">{{ strtoupper($company->name ?? 'COMPANY NAME') }}</h3>
        <h6 class="report-subtitle">{{ $company->address ?? '' }}</h6>
        <h6 class="report-subtitle">GSTIN: {{ $company->gst_number ?? '' }}</h6>
        <h2 class="report-title">{{ $reportTitle }}</h2>
        
        <div class="report-date">
            @if($account)
                <h3 class="" style="">Ledger Account: {{ $account }}</h3>
            @endif
            <strong>
                Date: 
                {{ !empty($filters['start_date']) ? format_date($filters['start_date']) : '' }}
                to
                {{ !empty($filters['end_date']) ? format_date($filters['end_date']) : '' }}
            </strong>
        </div>
    </header>
    
    <main class="print-main">
        <div class="report-content">
            <table class="report-table">
                <thead>
                    {{-- Opening Balance Row --}}
                    <tr class="balance-row">
                        <!-- <td class="text-start"></td> -->
                        <td colspan="1" class="text-start"></td>
                        <td colspan="2" class="text-end"></td>
                        <td colspan="2" class="text-end"><strong>Opening Balance</strong></td>
                        <td colspan="2" class="text-end">
                            <strong>
                                {{ number_format(abs($ledgerReport['opening_balance'] ?? 0), 2) }} 
                                {{ ($ledgerReport['opening_balance'] ?? 0) >= 0 ? 'Dr' : 'Cr' }}
                            </strong>
                        </td>
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
                        $srNo = 1;
                        $rowCountOnPage = 1; // Count opening balance row
                    @endphp

                    @forelse($transactions as $item)
                        @php
                            $item = (object) $item;
                            $rowCost = 1;
                            if (!empty($item->narration)) {
                                $rowCost += substr_count($item->narration, "\n") + 1;
                            }
                            if (!empty($item->short_narration)) {
                                $rowCost += substr_count($item->short_narration, "\n") + 1;
                            }

                            $rowCountOnPage += $rowCost;
                            $processedItems++;
                            $currentPageLimit = $page === 1 ? $firstPageRows : $otherPageRows;
                            
                            // Check if we need a page break BEFORE printing the row
                            // We need to account for Closing Balance and Grand Total rows at the end
                            $needsPageBreak = false;
                            
                            if ($loop->last) {
                                // Last item needs 3 extra rows (current item + closing balance + grand total)
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
                                $rowCountOnPage = $rowCost; 
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
                                @if($account) - {{ $account }} @endif
                            </p>
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

                        @php
                            // Check if it's an object or array (Controller/Service seems to return objects from DB)
                            $item = (object) $item;
                            $runningBalance = $item->running_balance ?? 0;
                        @endphp
                        <tr>
                            <td class="text-start text-nowrap">{{ !empty($item->voucher_date) ? format_date($item->voucher_date) : '' }}</td>
                            <td class="text-start text-nowrap">
                                {{ $item->against_account_name ?? '' }}
                                @if(!empty($item->narration))
                                    <div style="font-size: 11px; font-style: italic; color: #555; margin-left: 10px;">
                                        ( {!! nl2br(e($item->narration)) !!} )
                                    </div>
                                @endif
                                @if(!empty($item->short_narration))
                                    <div style="font-size: 11px; font-style: italic; color: #555; margin-left: 10px;">
                                        ( {{ $item->short_narration }} )
                                    </div>
                                @endif
                            </td>
                            <td class="text-start text-nowrap">{{ $item->voucher_type ?? '' }}</td>
                            <td class="text-center text-nowrap">{{ !empty($item->voucher_serial) ? trim($item->voucher_serial . '/' . ($item->reference_number ?? ''), '/') : '' }}</td>
                            <td class="text-end text-nowrap">{{ $item->debit ? number_format($item->debit, 2) : '' }}</td>
                            <td class="text-end text-nowrap">{{ $item->credit ? number_format($item->credit, 2) : '' }}</td>
                            <td class="text-end text-nowrap">{{ $item->running_balance !== null ? number_format(abs($item->running_balance), 2) . ($item->running_balance >= 0 ? ' Dr' : ' Cr') : '' }}</td>
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
                        <td colspan="1" class="text-end"><strong>{{ number_format($ledgerReport['debit'] ?? 0, 2) }}</strong></td>
                        <td colspan="1" class="text-end"><strong>{{ number_format($ledgerReport['credit'] ?? 0, 2) }}</strong></td>
                        <td colspan="1" class="text-end"></td>
                    </tr>
                    {{-- Closing Balance Row --}}
                    <tr class="balance-row">
                        <td colspan="1" class="text-end"></td>
                        <td colspan="2" class="text-end"></td>
                        <td colspan="2" class="text-end"><strong>Closing Balance</strong></td>
                        <td colspan="2" class="text-end">
                            <strong>
                                {{ number_format(abs($ledgerReport['closing_balance'] ?? 0), 2) }} 
                                {{ ($ledgerReport['closing_balance'] ?? 0) >= 0 ? 'Dr' : 'Cr' }}
                            </strong>
                        </td>
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