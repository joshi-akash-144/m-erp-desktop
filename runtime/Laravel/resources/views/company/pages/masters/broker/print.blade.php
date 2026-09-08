<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Broker Report - Print</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0; padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Public Sans', Roboto, Arial, sans-serif;
            font-size: 13px; line-height: 1.4; color: #000; background-color: #fff;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @page { size: A4; margin: 12mm 10mm 15mm 20mm; }
        body { padding-left: 5mm; min-height: 100vh; }

        .report-company  { font-size: 21px; font-weight: bold; text-align: center; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 2px 0; }
        .report-subtitle { font-size: 12px; text-align: center; line-height: 1.2; margin: 0 0 2px 0; }
        .report-title    { font-size: 16px; font-weight: 700; text-align: center; margin: 3px 0 10px 0; letter-spacing: 1px; }
        .report-content  { margin-top: 8px; }

        .continuation-text   { text-align: right; font-size: 14px; margin: 5px 0 0 0; }
        .continuation-header { margin-bottom: 8px; }
        .company-name { font-size: 13px; font-family: 'Courier New', Courier, monospace; margin: 0; }
        .page-info    { font-size: 13px; font-family: 'Courier New', Courier, monospace; margin: 0; }

        table { width: 100%; border-collapse: collapse; page-break-inside: auto; border-spacing: 0; }
        th, td { padding: 4px 6px; text-align: left; vertical-align: top; }
        thead { display: table-header-group; }
        tbody { display: table-row-group; }
        tfoot { display: table-footer-group; }
        tr { page-break-inside: avoid; break-inside: avoid; }

        .report-table thead th {
            font-size: 13px; font-weight: 600;
            border-top: 1px solid #000; border-bottom: 1px solid #000;
            background-color: #f4f4f4; padding: 4px 6px;
        }
        .report-table tbody td { border-bottom: 1px dotted #bbb; font-size: 12.5px; padding: 4px 6px; }

        .text-center { text-align: center !important; }
        .text-end    { text-align: right !important; }
        .text-start  { text-align: left !important; }
        .ps-2        { padding-left: 8px !important; }
        .w-100       { width: 100% !important; }

        .report-footer {
            font-size: 11.5px; font-style: italic;
            margin-top: 12px; border-top: 1px solid #000; padding-top: 5px;
            display: flex; justify-content: space-between; align-items: center;
        }

        @media print {
            html, body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print  { display: none !important; }
            .conditional-page-break { page-break-after: always; break-after: always; }
            @page { size: A4; margin: 10mm 10mm 12mm 20mm; }
            body { padding-left: 5mm; }
            tr    { page-break-inside: avoid; break-inside: avoid; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
            .report-table thead th { background-color: #f4f4f4 !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>
<body>
    @php
        $reportTitle    = 'Broker Report';
        $orientation    = $orientation ?? 'portrait';
        $page           = 1;
        $rowCountOnPage = 0;
        $firstPageRows  = $orientation === 'landscape' ? 30 : 25;
        $otherPageRows  = $orientation === 'landscape' ? 45 : 38;
    @endphp

    <header>
        <h3 class="report-company">{{ strtoupper($company->name) }}</h3>
        <h6 class="report-subtitle">GSTIN: {{ $company->gst_number ?? '' }}</h6>
        <h5 class="report-title">{{ $reportTitle }}</h5>
    </header>

    <main>
        <div class="report-content page-{{ $orientation }}">
            <table class="w-100 report-table">
                <thead>
                    <tr>
                        @foreach ($tableConfig['columns'] as $column)
                            <th class="{{ $column['class'] ?? '' }}">{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($brokers as $index => $broker)
                        @php
                            $rowCountOnPage++;
                            $currentPageLimit = $page === 1 ? $firstPageRows : $otherPageRows;
                        @endphp

                        <tr>
                            @foreach ($tableConfig['body'][$index] as $cell)
                                <td class="{{ $cell['class'] ?? '' }}" style="{{ $cell['style'] ?? '' }}">
                                    {{ $cell['value'] }}
                                </td>
                            @endforeach
                        </tr>

                        @if ($rowCountOnPage >= $currentPageLimit && !$loop->last)
                            @php $page++; $rowCountOnPage = 0; @endphp
                            </tbody></table>
                            <p class="continuation-text">cont. on page {{ $page }}...</p>
                            <div class="conditional-page-break"></div>
                            <div class="continuation-header">
                                <p class="company-name">{{ strtoupper($company->name) }}</p>
                                <p class="page-info">Page {{ $page }}: <span>{{ $reportTitle }}</span></p>
                            </div>
                            <table class="w-100 report-table page-{{ $orientation }}">
                                <thead>
                                    <tr>
                                        @foreach ($tableConfig['columns'] as $column)
                                            <th class="{{ $column['class'] ?? '' }}">{{ $column['label'] }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                            <tbody>
                        @endif
                    @empty
                        <tr>
                            <td colspan="{{ count($tableConfig['columns']) }}" class="text-center">No records found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>

    <footer class="report-footer">
        <span>Prepared By: <strong>{{ auth()->user()->name }}</strong></span>
        <span>Prepared On: <strong>{{ now()->format('d-m-Y H:i:s') }}</strong></span>
    </footer>
</body>
<script>window.addEventListener("afterprint", () => window.close());</script>
</html>
