<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diesel Expense – {{ $expense->id }}</title>
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
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background-color: #fff;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        body {
            padding: 2mm 5mm;
        }

        .report-company {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin: 0 0 2px;
        }

        .report-title {
            font-size: 15px;
            font-weight: 700;
            text-align: center;
            margin: 2px 0 6px;
            letter-spacing: 1px;
        }

        .report-sub {
            font-size: 11px;
            text-align: center;
            margin-bottom: 3px;
        }

        .report-divider {
            border-top: 1.5px solid #000;
            margin: 4px 0;
        }

        .report-meta {
            display: flex;
            justify-content: space-between;
            font-size: 11.5px;
            font-weight: 600;
            margin: 3px 0 4px;
        }

        .continuation-text {
            text-align: right;
            font-size: 11px;
            margin: 4px 0 0;
            font-style: italic;
        }

        .continuation-header {
            margin-bottom: 6px;
        }

        .continuation-header .company-name {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }

        .continuation-header .page-info {
            font-size: 11px;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
            table-layout: fixed;
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

        .report-table thead th {
            font-size: 11px;
            font-weight: 700;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 4px;
            background: #fff;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .report-table tbody td {
            font-size: 11px;
            padding: 2px 4px;
            border-bottom: 1px dotted #bbb;
            vertical-align: middle;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .report-table tfoot td {
            font-size: 11px;
            padding: 3px 4px;
            font-weight: 700;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .text-left {
            text-align: left !important;
        }

        .report-footer {
            font-size: 11px;
            margin-top: 14px;
            padding-top: 5px;
            display: flex;
            justify-content: space-between;
            font-weight: 600;
        }

        .btn-print {
            display: block;
            margin: 6px auto;
            padding: 6px 22px;
            background: #1e3a5f;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 10pt;
            cursor: pointer;
        }

        .btn-print:hover {
            background: #24467a;
        }

        @media print {

            html,
            body {
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print {
                display: none !important;
            }

            .conditional-page-break {
                page-break-after: always;
                break-after: always;
            }
        }
    </style>
</head>

<body>

    <button class="btn-print no-print" onclick="window.print()">Print</button>

    @php
    $items = $expense->details;
    $dates = $items->pluck('today_date')->filter()->sort()->values();
    $minDate = $dates->first() ? \Carbon\Carbon::parse($dates->first())->format('d/m/Y') : '-';
    $maxDate = $dates->last() ? \Carbon\Carbon::parse($dates->last())->format('d/m/Y') : '-';
    $totalDiesel = $items->sum('diesel');

    // Row limits per page (A4 landscape)
    $firstPageRows = 27;
    $otherPageRows = 30;

    // Pre-calculate total pages
    $tempPage = 1; $tempRowCount = 1;
    foreach ($items as $item) {
    $tempRowCount++;
    $limit = $tempPage === 1 ? $firstPageRows : $otherPageRows;
    if ($tempRowCount > $limit) { $tempPage++; $tempRowCount = 1; }
    }
    $totalPages = $tempPage;
    $page = 1;
    @endphp

    <header>
        <h3 class="report-company">{{ $company?->print_name ?? $company?->name ?? '' }}</h3>
        <h2 class="report-title">Diesel Entry</h2>
        <div class="report-sub">
            <!-- Diesel Entry Between Date : {{ $minDate }} to {{ $maxDate }} -->
            <!-- &nbsp;&nbsp;|&nbsp;&nbsp;
            Voucher No. : <strong>-</strong> -->
            <!-- &nbsp;&nbsp;|&nbsp;&nbsp; -->
            Entry Date :
            <strong>{{ $expense->created_at ? \Carbon\Carbon::parse($expense->created_at)->format('d/m/Y') : '-' }}</strong>
        </div>
        <div class="report-divider"></div>
        <div class="report-meta">
            <span><strong>Diesel Account :</strong> {{ $expense->account?->name ?? '-' }}</span>
        </div>
    </header>

    <main>
        <table class="report-table">
            <colgroup>
                <col style="width:3%">
                <col style="width:7%">
                <col style="width:7%">
                <col style="width:11%">
                <col style="width:11%">
                <col style="width:7%">
                <col style="width:7%">
                <col style="width:7%">
                <col style="width:7%">
                <col style="width:7%">
                <col style="width:7%">
                <col style="width:7%">
                <col style="width:6%">
                <col style="width:6%">
                <col style="width:7%">
            </colgroup>
            <thead>
                <tr>
                    <th class="text-center">Sr.</th>
                    <th class="text-center">Bill No.</th>
                    <th class="text-center">Challan No.</th>
                    <th class="text-center">Vehicle</th>
                    <th class="text-center">Driver</th>
                    <th class="text-center">Last Date</th>
                    <th class="text-center">Today</th>
                    <th class="text-center">Rate</th>
                    <th class="text-center">Diesel</th>
                    <th class="text-right">Old K.M</th>
                    <th class="text-right">New K.M</th>
                    <th class="text-right">Amount</th>
                    <th class="text-right">Diff</th>
                    <th class="text-right">Average</th>
                    <th class="text-left">Remark</th>
                </tr>
            </thead>

            <tbody>
                @php $rowCountOnPage = 1; $sr = 1; @endphp

                @foreach($items as $item)
                @php
                $rowCountOnPage++;
                $currentLimit = $page === 1 ? $firstPageRows : $otherPageRows;
                $needsPageBreak = ($rowCountOnPage > $currentLimit);
                @endphp

                @if($needsPageBreak)
                @php $page++; $rowCountOnPage = 1; @endphp
            </tbody>
        </table>

        <p class="continuation-text">cont. on page {{ $page }}...</p>
        <div class="conditional-page-break"></div>

        <div class="continuation-header">
            <p class="company-name">{{ strtoupper($company?->print_name ?? $company?->name ?? '') }}</p>
            <p class="page-info">
                Page {{ $page }} of {{ $totalPages }}: Diesel Expense
                &mdash; Diesel Expense
            </p>
        </div>

        <table class="report-table">
            <colgroup>
                <col style="width:3%">
                <col style="width:7%">
                <col style="width:7%">
                <col style="width:11%">
                <col style="width:11%">
                <col style="width:7%">
                <col style="width:7%">
                <col style="width:7%">
                <col style="width:7%">
                <col style="width:7%">
                <col style="width:7%">
                <col style="width:7%">
                <col style="width:6%">
                <col style="width:6%">
                <col style="width:7%">
            </colgroup>
            <thead>
                <tr>
                    <th class="text-center">Sr.</th>
                    <th class="text-center">Bill No.</th>
                    <th class="text-center">Challan No.</th>
                    <th class="text-center">Vehicle</th>
                    <th class="text-center">Driver</th>
                    <th class="text-center">Last Date</th>
                    <th class="text-center">Today</th>
                    <th class="text-center">Rate</th>
                    <th class="text-center">Diesel</th>
                    <th class="text-right">Old K.M</th>
                    <th class="text-right">New K.M</th>
                    <th class="text-right">Amount</th>
                    <th class="text-right">Diff</th>
                    <th class="text-right">Average</th>
                    <th class="text-left">Remark</th>
                </tr>
            </thead>
            <tbody>
                @endif

                <tr>
                    <td class="text-center">{{ $sr++ }}</td>
                    <td class="text-center">{{ $item->reference_number ?? '-' }}</td>
                    <td class="text-center">{{ $item->challan_number ?? '-' }}</td>
                    <td class="text-center">{{ $item->vehicle?->name ?? '' }}</td>
                    <td>{{ $item->driver?->account?->name ?? '' }}</td>
                    <td class="text-center">
                        {{ $item->last_date ? \Carbon\Carbon::parse($item->last_date)->format('d/m/Y') : '' }}
                    </td>
                    <td class="text-center">
                        {{ $item->today_date ? \Carbon\Carbon::parse($item->today_date)->format('d/m/Y') : '' }}
                    </td>
                    <td class="text-center">
                        @php
                            $rate = (float)$item->rate > 0 ? (float)$item->rate : (float)$expense->diesel_rate;
                        @endphp
                        {{ formatIndianNumber($rate, 2) }}
                    </td>
                    <td class="text-center">{{ formatIndianNumber((float)$item->diesel, 2) }}</td>
                    <td class="text-right">{{ formatIndianNumber((float)$item->old_km, 2) }}</td>
                    <td class="text-right">{{ formatIndianNumber((float)$item->new_km, 2) }}</td>
                    <td class="text-right">{{ formatIndianNumber((float)$item->amount, 2) }}</td>
                    <td class="text-right">{{ formatIndianNumber((float)$item->diff, 2) }}</td>
                    <td class="text-right">{{ formatIndianNumber((float)$item->average, 2) }}</td>
                    <td>{{ $item->remark ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="8" class="text-right"><strong>Total</strong></td>
                    <td class="text-center">{{ formatIndianNumber($totalDiesel, 2) }}</td>
                    <td colspan="2"></td>
                    <td class="text-right">{{ formatIndianNumber((float)$expense->total_amount, 2) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </main>

    @if($expense->narration)
    <div style="font-size:11px; margin-top:6px;">
        <strong>Narration :</strong> {{ $expense->narration }}
    </div>
    @endif

    <footer class="report-footer">
        <span>Prepared By : <strong>{{ auth()->user()->name ?? '' }}</strong></span>
        <span>Checked By : _______________</span>
        <span>Payment By : _______________</span>
    </footer>

    <script>
        window.addEventListener('afterprint', () => window.close());
    </script>
</body>

</html>