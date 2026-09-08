<!DOCTYPE html>
@php
    use Carbon\Carbon;
@endphp
<html lang="en">
 
<head>
    <meta charset="utf-8">
    <title>Delivery Challan Report - Print</title>
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
            size: landscape;
            /* margin: 12mm 10mm 15mm 20mm; */
            /* Increased left margin for hole punch */
        }
 
        body {
            padding-left: 5mm;
            /* Additional left padding for content */
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
 
        .report-content {
            margin-top: 8px;
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
 
        .po-first-row td {
            border-top: 1px dashed #aaa;
        }
 
        .text-center {
            text-align: center !important;
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
 
        .text-end {
            text-align: right !important;
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
                margin: 0; /* Optional: Resets default body margins for maximum space */
                padding: 0;
                /* padding-left: 5mm; */
            }
            .no-print {
                display: none !important;
            }
 
            .conditional-page-break {
                page-break-after: always;
                break-after: always;
            }
 
            @page {
                size: landscape;
                /* Left margin for filing */
            }
 
            table {
                width: 100% !important; /* Forces the table to use the full width of the printed page */
                page-break-inside: auto; /* Allows the table to break across pages if needed */
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
    $reportTitle = "Delivery Challan Report";
    $orientation = $orientation ?? 'portrait';
    $page = 1;
    $rowCountOnPage = 0;
 
    // Adjust rows based on orientation
    if ($orientation === 'landscape') {
    $firstPageRows = 30;
    $otherPageRows = 45;
    } else {
    $firstPageRows = 25;
    $otherPageRows = 38;
    }
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
        <div class="report-content page-{{ $orientation }}">
            <table class="report-table" style="width: auto;">
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
                    @forelse($deliveryChallans as $index => $challan)
                        @foreach($challan->details as $detailIndex => $detail)
                            @php
                                $rowCountOnPage++;
                                $currentPageLimit = $page === 1 ? $firstPageRows : $otherPageRows;
                                $needsPageBreak = $rowCountOnPage > $currentPageLimit;
                                $isFirstDetail = ($detailIndex === 0);
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
 
                                {{-- Next page header --}}
                                <div class="continuation-header">
                                    <p class="company-name">{{ strtoupper($company->name) }}</p>
                                    <p class="page-info">
                                        Page {{ $page }}: <span class="report-name">{{ $reportTitle }}</span>
                                    </p>
                                </div>
 
                                <table class="w-100 report-table page-{{ $orientation }}">
                                    <thead>
                                        <tr>
                                            @foreach ($tableConfig['columns'] as $column)
                                            <th class="{{ $column['class'] ?? '' }}" style="width: {{ $column['width'] ?? 'auto' }};">
                                                {{ $column['label'] }}
                                            </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                            @endif
 
                            <tr class="{{ $isFirstDetail ? 'po-first-row' : '' }}">
                                {{-- 0. Delivery Challan Number (DC No.) --}}
                                <td class="text-start text-nowrap" style="width: {{ $tableConfig['columns'][0]['width'] ?? 'auto'}};">
                                    {{ $isFirstDetail ? $challan->challan_serial : '' }}
                                </td>
                                
                                {{-- 1. Date In --}}
                                <td class="text-start text-nowrap" style="width: {{ $tableConfig['columns'][1]['width'] ?? 'auto'}};">
                                    @if ($isFirstDetail)
                                        @php
                                            $dateIn = $challan->challan_date ? $challan->challan_date : '';
                                        @endphp
                                        {{ $dateIn ? \Carbon\Carbon::parse($dateIn)->format('d-m-Y') : '-' }}
                                    @endif
                                </td>
                                
                                {{-- 2. Sales Order No (S.O.No.) --}}
                                <td class="text-start text-nowrap" style="width: {{ $tableConfig['columns'][2]['width'] ?? 'auto'}};">
                                    {{ $isFirstDetail ? ($detail->sales_order_serial ?? '-') : '' }}
                                </td>
                                
                                {{-- 3. Customer --}}
                                <td class="text-start" style="width: {{ $tableConfig['columns'][3]['width'] ?? 'auto'}};">
                                    {{ $isFirstDetail ? Str::limit($challan->account->name ?? '', 24) : '' }}
                                </td>
                                
                                {{-- 5. Items --}}
                                <td class="text-start text-nowrap" style="width: {{ $tableConfig['columns'][4]['width'] ?? 'auto'}};">
                                    {{ Str::limit($detail->item->name ?? '', 15) }}
                                </td>
                                
                                {{-- 6. Destination --}}
                                <td class="text-start" style="width: {{ $tableConfig['columns'][5]['width'] ?? 'auto'}};">
                                    {{ Str::limit($detail->destination->name ?? '', 20) }}
                                </td>
                                
                                {{-- 7. Quantity (Qty.) --}}
                                <td class="text-end text-nowrap" style="width: {{ $tableConfig['columns'][6]['width'] ?? 'auto'}};">
                                    {{ number_format($detail->quantity ?? 0, 3, '.', '') }}
                                </td>
                                
                                {{-- 8. Party Quantity (P.Qty.) --}}
                                <td class="text-end text-nowrap" style="width: {{ $tableConfig['columns'][7]['width'] ?? 'auto'}};">
                                    {{ number_format($detail->party_quantity ?? 0, 3, '.', '') }}
                                </td>
                                
                                {{-- 9. Rate --}}
                                <td class="text-end text-nowrap" style="width: {{ $tableConfig['columns'][8]['width'] ?? 'auto'}};">
                                    {{ number_format($detail->rate ?? 0, 2, '.', '') }}
                                </td>

                                {{-- 10. Inclusive Rate (Incl Rate) --}}
                                <td class="text-end text-nowrap" style="width: {{ $tableConfig['columns'][9]['width'] ?? 'auto'}};">
                                    {{ number_format($detail->inclusive_rate ?? 0, 2, '.', '') }}
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="{{ count($tableConfig['columns']) }}" class="text-center">
                                No records found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
 
    </main>
    <footer class="report-footer">
        <span class="footer-left">Prepared By: <strong>{{ auth()->user()->name }}</strong></span>
        <span class="footer-right">Prepared On: <strong>{{ now()->format('d-m-Y H:i:s') }}</strong></span>
    </footer>
 
</body>
<script>
    window.addEventListener("afterprint", () => window.close());
</script>
 
</html>
 