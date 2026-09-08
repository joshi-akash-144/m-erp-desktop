<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver Expense Register</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0; padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Public Sans', Roboto, Arial, sans-serif;
            font-size: 13px; line-height: 1.45; color: #000;
            background-color: #fff;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @page { size: A4 landscape; margin: 8mm; }
        body { padding: 2mm 4mm; }

        .report-company { font-size: 22px; font-weight: bold; text-align: center; text-transform: uppercase; letter-spacing: .5px; margin: 0 0 2px; }
        .report-title   { font-size: 16px; font-weight: 700; text-align: center; margin: 2px 0 4px; letter-spacing: 1px; }
        .report-sub     { font-size: 12px; text-align: center; margin-bottom: 3px; }
        .report-divider { border-top: 1.5px solid #000; margin: 4px 0; }

        .page-number         { text-align: right; font-size: 11.5px; font-weight: 600; margin: 2px 0 0; }
        .continuation-text   { text-align: right; font-size: 11.5px; font-style: italic; margin: 5px 0 0; color: #444; }
        .continuation-header { margin-bottom: 6px; }
        .continuation-header .company-name { font-size: 13px; font-weight: bold; text-transform: uppercase; margin: 0; }
        .continuation-header .page-info    { font-size: 12px; font-weight: 600; margin: 0; }

        table { width: 100%; border-collapse: collapse; page-break-inside: auto; table-layout: fixed; }
        thead { display: table-header-group; }
        tbody { display: table-row-group; }
        tfoot { display: table-footer-group; }
        tr    { page-break-inside: avoid; break-inside: avoid; }

        .report-table thead th {
            font-size: 12px; font-weight: 700;
            border-top: 1px solid #000; border-bottom: 1px solid #000;
            padding: 4px 5px; background: #fff; text-align: center;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .report-table tbody td {
            font-size: 12px; padding: 3px 5px;
            border-bottom: 1px dotted #bbb; vertical-align: middle;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .report-table tfoot td {
            font-size: 12px; padding: 4px 5px; font-weight: 700;
            border-top: 1px solid #000; border-bottom: 1px solid #000;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }

        tr.row-total  { font-weight: 700; }
        tr.row-parent td.bold-cell { font-weight: 700; }
        tr.row-total td { border-top: 1px solid #999 !important; border-bottom: 1px solid #999 !important; }

        .text-right  { text-align: right !important; }
        .text-center { text-align: center !important; }
        .text-left   { text-align: left !important; }

        .report-footer {
            font-size: 11px; margin-top: 14px; padding-top: 5px;
            display: flex; justify-content: space-between; font-weight: 600;
        }

        .btn-print {
            display: block; margin: 6px auto; padding: 6px 22px;
            background: #1e3a5f; color: #fff; border: none;
            border-radius: 4px; font-size: 10pt; cursor: pointer;
        }
        .btn-print:hover { background: #24467a; }

        @media print {
            html, body { margin: 0; padding: 0; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print  { display: none !important; }
            .conditional-page-break { page-break-after: always; break-after: always; }
        }
    </style>
</head>
<body>

<button class="btn-print no-print" onclick="window.print()">Print</button>

@php
    // Pre-count total pages
    $firstPageRows = 23;
    $otherPageRows = 25;

    $tempPage = 1; $tempRowCount = 1;
    foreach ($rows as $row) {
        $tempRowCount++;
        $limit = $tempPage === 1 ? $firstPageRows : $otherPageRows;
        if ($tempRowCount > $limit) { $tempPage++; $tempRowCount = 1; }
    }
    $totalPages = $tempPage;
    $page = 1;

    // Grand totals
    $grandBags   = collect($rows)->where('row_type', 'total')->sum('bags');
    $grandWeight = collect($rows)->where('row_type', 'total')->sum('weight');
    $grandAmount = collect($rows)->where('row_type', 'total')->sum('amount');
@endphp

<header>
    <h3 class="report-company">{{ $company?->print_name ?? $company?->name ?? '' }}</h3>
    <h2 class="report-title">Driver Expense Register</h2>
    <div class="report-sub">
        From : <strong>{{ $fromDate ? \Carbon\Carbon::parse($fromDate)->format('d/m/Y') : '-' }}</strong>
        &nbsp;&nbsp;To : <strong>{{ $toDate ? \Carbon\Carbon::parse($toDate)->format('d/m/Y') : '-' }}</strong>
    </div>
    <div class="report-divider"></div>
    <div class="page-number">Page 1 of {{ $totalPages }}</div>
</header>

<main>
    <table class="report-table">
        <colgroup>
            <col style="width:3%">
            <col style="width:6%">
            <col style="width:7%">
            <col style="width:12%">
            <col style="width:9%">
            <col style="width:7%">
            <col style="width:10%">
            <col style="width:9%">
            <col style="width:9%">
            <col style="width:10%">
            <col style="width:7%">
            <col style="width:6%">
            <col style="width:4%">
            <col style="width:7%">
        </colgroup>
        <thead>
            <tr>
                <th>#</th>
                <th>Vch. No.</th>
                <th>Date</th>
                <th class="text-left">Driver</th>
                <th>Vehicle</th>
                <th>Exp. Date</th>
                <th class="text-left">Expense</th>
                <th class="text-left">From</th>
                <th class="text-left">To</th>
                <th class="text-left">Product</th>
                <th class="text-right">Bags</th>
                <th class="text-right">Weight</th>
                <th class="text-right">Trips</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>

        <tbody>
            @php $rowCountOnPage = 1; @endphp

            @foreach($rows as $row)
                @php
                    $rowCountOnPage++;
                    $currentLimit   = $page === 1 ? $firstPageRows : $otherPageRows;
                    $needsPageBreak = ($rowCountOnPage > $currentLimit);
                @endphp

                @if($needsPageBreak)
                    @php $page++; $rowCountOnPage = 1; @endphp
                    </tbody>
                </table>

                <p class="continuation-text">Continued on Page {{ $page }}...</p>
                <div class="conditional-page-break"></div>

                <div class="continuation-header">
                    <p class="company-name">{{ strtoupper($company?->print_name ?? $company?->name ?? '') }}</p>
                    <p class="page-info">Page {{ $page }} of {{ $totalPages }} &mdash; Driver Expense Register</p>
                </div>

                <table class="report-table">
                    <colgroup>
                        <col style="width:3%">
                        <col style="width:6%">
                        <col style="width:7%">
                        <col style="width:12%">
                        <col style="width:9%">
                        <col style="width:7%">
                        <col style="width:10%">
                        <col style="width:9%">
                        <col style="width:9%">
                        <col style="width:10%">
                        <col style="width:7%">
                        <col style="width:6%">
                        <col style="width:4%">
                        <col style="width:7%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Vch. No.</th>
                            <th>Date</th>
                            <th class="text-left">Driver</th>
                            <th>Vehicle</th>
                            <th>Exp. Date</th>
                            <th class="text-left">Expense</th>
                            <th class="text-left">From</th>
                            <th class="text-left">To</th>
                            <th class="text-left">Product</th>
                            <th class="text-right">Bags</th>
                            <th class="text-right">Weight</th>
                            <th class="text-right">Trips</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                @endif

                @if($row['row_type'] === 'parent')
                <tr class="row-parent">
                    <td class="text-center">{{ $row['row_num'] }}</td>
                    <td class="text-center">{{ $row['voucher_serial'] ?? '-' }}</td>
                    <td class="text-center">{{ $row['voucher_date'] ?? '-' }}</td>
                    <td class="bold-cell">{{ $row['driver_name'] ?? '-' }}</td>
                    <td class="text-center bold-cell">{{ $row['vehicle_name'] ?? '-' }}</td>
                    <td class="text-center">{{ $row['billing_date'] ?? '-' }}</td>
                    <td>{{ $row['expense_name'] ?? '-' }}</td>
                    <td>{{ $row['from_name'] ?? '-' }}</td>
                    <td>{{ $row['to_name'] ?? '-' }}</td>
                    <td>{{ $row['product_name'] ?? '-' }}</td>
                    <td class="text-right">{{ number_format((float)($row['bags'] ?? 0), 2) }}</td>
                    <td class="text-right">{{ number_format((float)($row['weight'] ?? 0), 2) }}</td>
                    <td class="text-right">{{ $row['trips'] ?? '-' }}</td>
                    <td class="text-right">{{ number_format((float)($row['amount'] ?? 0), 2) }}</td>
                </tr>
                @elseif($row['row_type'] === 'item')
                <tr class="row-item">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-center">{{ $row['billing_date'] ?? '-' }}</td>
                    <td>{{ $row['expense_name'] ?? '-' }}</td>
                    <td>{{ $row['from_name'] ?? '-' }}</td>
                    <td>{{ $row['to_name'] ?? '-' }}</td>
                    <td>{{ $row['product_name'] ?? '-' }}</td>
                    <td class="text-right">{{ number_format((float)($row['bags'] ?? 0), 2) }}</td>
                    <td class="text-right">{{ number_format((float)($row['weight'] ?? 0), 2) }}</td>
                    <td class="text-right">{{ $row['trips'] ?? '-' }}</td>
                    <td class="text-right">{{ number_format((float)($row['amount'] ?? 0), 2) }}</td>
                </tr>
                @elseif($row['row_type'] === 'total')
                <tr class="row-total">
                    <td colspan="10" class="text-right">Total</td>
                    <td class="text-right">{{ number_format((float)($row['bags'] ?? 0), 2) }}</td>
                    <td class="text-right">{{ number_format((float)($row['weight'] ?? 0), 2) }}</td>
                    <td class="text-right">{{ $row['trips'] ?? '' }}</td>
                    <td class="text-right">{{ number_format((float)($row['amount'] ?? 0), 2) }}</td>
                </tr>
                @endif
            @endforeach
        </tbody>

        <tfoot>
            <tr>
                <td colspan="10" class="text-right">Grand Total</td>
                <td class="text-right">{{ number_format((float)$grandBags, 2) }}</td>
                <td class="text-right">{{ number_format((float)$grandWeight, 2) }}</td>
                <td class="text-right"></td>
                <td class="text-right">{{ number_format((float)$grandAmount, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</main>


<script>window.addEventListener('afterprint', () => window.close());</script>
</body>
</html>
