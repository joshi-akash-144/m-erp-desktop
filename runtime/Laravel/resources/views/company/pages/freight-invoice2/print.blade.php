<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Freight Invoice 2 Report - Print</title>
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
            font-size: 11px; /* Smaller font to fit many columns */
            line-height: 1.4;
            color: #000;
            background-color: #fff;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        @page {
            size: A4 landscape; /* LANDSCAPE format */
            margin: 12mm 10mm 15mm 10mm;
        }
        body {
            padding-left: 2mm;
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

        .continuation-text {
            text-align: right;
            font-size: 12px;
            margin: 5px 0 0 0;
            padding: 0;
        }

        .continuation-header {
            margin-bottom: 8px;
        }

        .company-name {
            font-size: 12px;
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 0;
        }

        .page-info {
            font-size: 12px;
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
            padding: 4px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
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
            table-layout: fixed;
        }

        .report-table thead th {
            font-size: 11px;
            font-weight: 600;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            background-color: #f4f4f4;
            padding: 4px;
            vertical-align: middle;
        }

        .report-table tbody td {
            border-bottom: 1px dotted #bbb;
            font-size: 10.5px;
            padding: 4px;
        }

        .text-center {
            text-align: center !important;
        }
        
        .text-start {
            text-align: left !important;
        }
        
        .text-end {
            text-align: right !important;
        }
        
        .fw-bold {
            font-weight: bold !important;
        }
        
        .report-footer {
            font-size: 10px;
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
        .w-100 {
            width: 100% !important;
        }

        @media print {
            html,
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            .no-print {
                display: none !important;
            }

            .conditional-page-break {
                page-break-after: always;
                break-after: always;
            }

            @page {
                size: A4 landscape; /* LANDSCAPE format for printing */
                margin: 10mm 10mm 12mm 10mm;
            }

            table {
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
        }
    </style>
</head>

<body>
    @php
    $reportTitle = "Freight Invoice 2 Report";
    $page = 1;
    $rowCountOnPage = 0;

    // Landscape rows limit
    $firstPageRows = 28;
    $otherPageRows = 38;
    @endphp
    <header class="print-header">
        <h3 class="report-company">{{ strtoupper($company->name ?? '') }}</h3>
        <h6 class="report-subtitle">GSTIN: {{ $company->gst_number ?? '' }}</h6>
        <h5 class="report-title">{{ $reportTitle }}<br><small style="font-size: 11px;">Date Period: {{ $datePeriod }}</small></h5>
    </header>
    <main class="print-main">
        <div class="report-content">
            <table class="w-100 report-table">
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
                    @forelse($rows as $index => $row)
                    @php
                    $rowCountOnPage++;
                    $currentPageLimit = $page === 1 ? $firstPageRows : $otherPageRows;
                    @endphp

                    @if(isset($row['is_total']) && $row['is_total'])
                    <tr>
                        <td class="text-end fw-bold" colspan="7">Total</td>
                        <td class="text-end fw-bold">{{ $row['bag_count'] ? number_format($row['bag_count']) : '' }}</td>
                        <td colspan="4"></td>
                        <td class="text-end fw-bold">{{ $row['amount'] ? number_format($row['amount'], 2) : '' }}</td>
                        <td></td>
                    </tr>
                    @else
                    <tr>
                        <td class="text-start">{{ $row['bill_number'] ?? '' }}</td>
                        <td class="text-center">{{ $row['invoice_date'] ?? '' }}</td>
                        <td class="text-start">{{ Str::limit($row['account_name'] ?? '', 20) }}</td>
                        <td class="text-center">{{ $row['item_date'] ?? '' }}</td>
                        <td class="text-start">{{ $row['code'] ?? '' }}</td>
                        <td class="text-start">{{ Str::limit($row['society_name'] ?? '', 20) }}</td>
                        <td class="text-start">{{ Str::limit($row['route'] ?? '', 15) }}</td>
                        <td class="text-end">{{ $row['bag_count'] ?? '' }}</td>
                        <td class="text-start">{{ Str::limit($row['vehicle_no'] ?? '', 12) }}</td>
                        <td class="text-start">{{ Str::limit($row['vendor'] ?? '', 12) }}</td>
                        <td class="text-end">{{ $row['kms'] ?? '' }}</td>
                        <td class="text-end">{{ $row['rate'] ?? '' }}</td>
                        <td class="text-end">{{ $row['amount'] ?? '' }}</td>
                        <td class="text-start">{{ Str::limit($row['contractor'] ?? '', 20) }}</td>
                    </tr>
                    @endif

                    {{-- Page break logic --}}
                    @if ($rowCountOnPage >= $currentPageLimit && !$loop->last)
                    @php
                    $page++;
                    $rowCountOnPage = 0;
                    @endphp

                </tbody>
            </table>

            <p class="continuation-text">cont. on page {{ $page }}...</p>

            <div class="conditional-page-break"></div>

            {{-- Next page header --}}
            <div class="continuation-header">
                <p class="company-name">{{ strtoupper($company->name ?? '') }}</p>
                <p class="page-info">
                    Page {{ $page }}: <span class="report-name">{{ $reportTitle }}</span>
                </p>
            </div>

            <table class="w-100 report-table">
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
        <span class="footer-left">Prepared By: <strong>{{ auth()->user()->name ?? 'Admin' }}</strong></span>
        <span class="footer-right">Prepared On: <strong>{{ now()->format('d-m-Y H:i:s') }}</strong></span>
    </footer>

</body>
<script>
    window.addEventListener("afterprint", () => window.close());
</script>

</html>
