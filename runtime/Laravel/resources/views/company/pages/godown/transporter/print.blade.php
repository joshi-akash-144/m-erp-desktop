<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Godown Transporter Report - Print</title>
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
   PAGE MARGINS
 ============================ */
        @page {
            size: A4;
            margin: 12mm 10mm 15mm 20mm;
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
            float: right;
        }

        .report-name {
            font-weight: bold;
        }

        /* ============================
   TABLE STYLING
 ============================ */
        .w-100 {
            width: 100%;
        }

        .report-table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 5px;
        }

        .report-table th {
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            padding: 6px 4px;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            background-color: #f5f5f5 !important;
        }

        .report-table td {
            border-bottom: 0.5px dashed #ccc;
            padding: 5px 4px;
            font-size: 12px;
            vertical-align: middle;
        }

        .report-table tr.total-row td {
            border-top: 1.5px solid #000;
            border-bottom: 1.5px double #000;
            font-weight: bold;
            background-color: #fafafa !important;
        }

        /* Helper Classes */
        .text-start { text-align: left; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .ps-2 { padding-left: 8px !important; }
        .pe-2 { padding-right: 8px !important; }

        /* ============================
   FOOTER STYLING
 ============================ */
        .report-footer {
            position: fixed;
            bottom: 0;
            left: 20mm;
            right: 10mm;
            height: 10mm;
            border-top: 1px solid #000;
            padding-top: 2px;
            font-size: 11px;
            display: flex;
            justify-content: space-between;
        }

        /* ============================
   PAGE BREAKING SYSTEM
 ============================ */
        .conditional-page-break {
            page-break-after: always;
        }

        @media print {
            body {
                padding-left: 0;
            }
            .conditional-page-break {
                display: block;
            }
            .report-footer {
                left: 0;
                right: 0;
            }
        }
    </style>
</head>

<body>
    @php
    $reportTitle = "Transporter Wise Weight Summary";
    $orientation = $data['orientation'] ?? 'portrait';
    $page = 1;
    $rowCountOnPage = 0;

    // Adjust rows based on orientation
    if ($orientation === 'landscape') {
        $firstPageRows = 30;
        $otherPageRows = 45;
    } else {
        $firstPageRows = 25;
        $otherPageRows = 30;
    }

    $totalGross = 0;
    $totalTare = 0;
    $totalNet = 0;
    $totalWithoutBag = 0;
    @endphp

    <header class="print-header">
        <h3 class="report-company">{{ strtoupper($data['companyName']) }}</h3>
        @if(!empty($data['gstNumber']))
            <h6 class="report-subtitle">GSTIN: {{ $data['gstNumber'] }}</h6>
        @endif
        @if(!empty($data['companyAddress']))
            <h6 class="report-subtitle">{{ $data['companyAddress'] }}</h6>
        @endif
        <h5 class="report-title">{{ $reportTitle }}</h5>
        <h6 class="report-subtitle">Period: {{ $data['datePeriod'] }}</h6>
    </header>

    <main class="print-main">
        <div class="report-content page-{{ $orientation }}">
            <table class="w-100 report-table">
                <thead>
                    <tr>
                        @foreach ($data['tableConfig']['columns'] as $column)
                            <th class="{{ $column['class'] ?? '' }}" style="width: {{ $column['width'] ?? 'auto' }};">
                                {!! $column['label'] !!}
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @forelse($data['data'] as $index => $item)
                        @php
                            $rowCountOnPage++;
                            $currentPageLimit = $page === 1 ? $firstPageRows : $otherPageRows;
                            
                            $gross = (float)($item->gross_weight ?? 0);
                            $tare = (float)($item->tare_weight ?? 0);
                            $net = (float)($item->net_weight ?? 0);
                            $withoutBag = (float)($item->net_weight_wt_bag ?? 0);

                            $totalGross += $gross;
                            $totalTare += $tare;
                            $totalNet += $net;
                            $totalWithoutBag += $withoutBag;
                        @endphp

                        <tr>
                            {{-- Sr No. --}}
                            <td class="text-center">
                                {{ $index + 1 }}.
                            </td>

                            {{-- Vehicle No --}}
                            <td class="text-start ps-2">
                                {{ $item->vehicle_number ?? '-' }}
                            </td>

                            {{-- Transporter Name --}}
                            <td class="text-start ps-2">
                                {{ $item->transporter->name ?? 'N/A' }}
                            </td>

                            {{-- LR Number --}}
                            <td class="text-start ps-2">
                                {{ $item->lr_number ?? 'N/A' }}
                            </td>

                            {{-- Gross Weight --}}
                            <td class="text-end pe-2">
                                {{ number_format($gross, 3) }}
                            </td>

                            {{-- Tare Weight --}}
                            <td class="text-end pe-2">
                                {{ number_format($tare, 3) }}
                            </td>

                            {{-- Net Weight --}}
                            <td class="text-end pe-2">
                                {{ number_format($net, 3) }}
                            </td>

                            {{-- Without Bag Weight --}}
                            <td class="text-end pe-2">
                                {{ number_format($withoutBag, 3) }}
                            </td>
                        </tr>

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
                                <p class="company-name">{{ strtoupper($data['companyName']) }}</p>
                                <p class="page-info">
                                    Page {{ $page }}: <span class="report-name">{{ $reportTitle }}</span>
                                </p>
                            </div>

                            <table class="w-100 report-table page-{{ $orientation }}">
                                <thead>
                                    <tr>
                                        @foreach ($data['tableConfig']['columns'] as $column)
                                            <th class="{{ $column['class'] ?? '' }}" style="width: {{ $column['width'] ?? 'auto' }};">
                                                {!! $column['label'] !!}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                        @endif

                    @empty
                        <tr>
                            <td colspan="{{ count($data['tableConfig']['columns']) }}" class="text-center">
                                No records found
                            </td>
                        </tr>
                    @endforelse

                    {{-- Total Row --}}
                    @if(count($data['data']) > 0)
                        <tr class="total-row">
                            <td colspan="6" class="text-end pe-2"><strong>Total:</strong></td>
                            <td class="text-end pe-2"><strong>{{ number_format($totalNet, 3) }}</strong></td>
                            <td class="text-end pe-2"><strong>{{ number_format($totalWithoutBag, 3) }}</strong></td>
                        </tr>
                    @endif
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