<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Month Wise Account Summary - Print</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0; padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            font-size: 13px; line-height: 1.4; color: #000; background: #fff;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @page { size: portrait; margin: 10mm; }
        body  { padding-left: 5mm; min-height: 100vh; }

        .report-company  { font-size: 21px; font-weight: bold; text-align: center; text-transform: uppercase; margin: 0 0 2px; }
        .report-subtitle { font-size: 12px; text-align: center; margin: 0 0 2px; }
        .report-title    { font-size: 16px; font-weight: 700; text-align: center; margin: 3px 0 10px; letter-spacing: 1px; }
        .report-date     { display: flex; align-items: center; justify-content: space-between; font-size: 12px; font-weight: 700; margin: 3px 0 10px; }

        table { width: 100%; border-collapse: collapse; page-break-inside: auto; }
        th, td { padding: 4px 6px; text-align: left; vertical-align: top; }
        thead { display: table-header-group; }
        tbody { display: table-row-group; }
        tfoot { display: table-footer-group; }
        tr    { page-break-inside: avoid; break-inside: avoid; }

        .report-table thead th {
            font-size: 13px; font-weight: 600;
            border-top: 1px solid #000; border-bottom: 1px solid #000;
            background-color: #f4f4f4; padding: 4px 6px;
        }
        .report-table tbody td { border-bottom: 1px dotted #bbb; font-size: 12.5px; padding: 4px 6px; }
        .report-table tbody tr.balance-row td { border-top: 1px solid #000; border-bottom: 1px solid #000; font-weight: bold; }

        .text-end   { text-align: right !important; }
        .text-start { text-align: left !important; }
        .text-nowrap { white-space: nowrap; }

        .grand-total-row {
            background-color: #fff !important; font-weight: 700;
            border-top: 1px solid #000 !important; border-bottom: 1px solid #000 !important;
        }
        .grand-total-row td { border-top: 1px solid #000 !important; border-bottom: 1px solid #000 !important; }

        .report-footer {
            font-size: 11.5px; font-style: italic; margin-top: 12px; padding-top: 5px;
            display: flex; justify-content: space-between; align-items: center;
        }

        @media print {
            html, body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print  { display: none !important; }
            @page { size: portrait; }
            .report-table thead th { background-color: #f4f4f4 !important; -webkit-print-color-adjust: exact !important; }
            .grand-total-row       { background-color: #fff !important;    -webkit-print-color-adjust: exact !important; }
        }
    </style>
</head>
<body>
@php
    $ob = $opening_balance ?? 0;
    $cb = $closing_balance ?? 0;
    $gt = $grand_total ?? ['total_debit' => 0, 'total_credit' => 0];

    function mwFmt($v) { return formatIndianNumber(abs($v)) . ($v >= 0 ? ' Dr' : ' Cr'); }
@endphp

<header>
    <h3 class="report-company">{{ strtoupper($company->name ?? '') }}</h3>
    <p class="report-subtitle">{{ $company->address ?? '' }}</p>
    <p class="report-subtitle">GSTIN: {{ $company->gst_number ?? '' }}</p>
    <h2 class="report-title">Month Wise Account Summary</h2>
    <div class="report-date">
        <strong>Account: {{ $account_name ?? '' }}</strong>
        <strong>Period: {{ $from_date }} — {{ $to_date }}</strong>
    </div>
</header>

<main>
    <table class="report-table">
        <thead>
            <tr class="balance-row">
                <td colspan="5" class="text-end"><strong>Opening Balance</strong></td>
                <td colspan="2" class="text-end"><strong>{{ mwFmt($ob) }}</strong></td>
            </tr>
            <tr>
                <th class="text-start"  style="width:5%;">Sr No.</th>
                <th class="text-start"  style="width:18%;">Month</th>
                <th class="text-start"  style="width:25%;">Period</th>
                <th class="text-end"    style="width:13%;">Opening (₹)</th>
                <th class="text-end"    style="width:13%;">Debit (₹)</th>
                <th class="text-end"    style="width:13%;">Credit (₹)</th>
                <th class="text-end"    style="width:13%;">Closing (₹)</th>
            </tr>
        </thead>

        <tbody>
            @forelse($months as $i => $month)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $month['month_label'] }}</td>
                <td>{{ $month['from_date'] }} — {{ $month['to_date'] }}</td>
                <td class="text-end text-nowrap">{{ mwFmt($month['opening']) }}</td>
                <td class="text-end text-nowrap">{{ formatIndianNumber($month['debit']) }}</td>
                <td class="text-end text-nowrap">{{ formatIndianNumber($month['credit']) }}</td>
                <td class="text-end text-nowrap">{{ mwFmt($month['closing']) }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-end">No data found.</td></tr>
            @endforelse
        </tbody>

        <tfoot>
            <tr class="grand-total-row">
                <td colspan="4" class="text-end">Total</td>
                <td class="text-end text-nowrap">{{ formatIndianNumber($gt['total_debit']) }}</td>
                <td class="text-end text-nowrap">{{ formatIndianNumber($gt['total_credit']) }}</td>
                <td></td>
            </tr>
            <tr class="balance-row">
                <td colspan="5" class="text-end"><strong>Closing Balance</strong></td>
                <td colspan="2" class="text-end"><strong>{{ mwFmt($cb) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="report-footer">
        <span>Prepared By: {{ auth()->user()->name ?? '' }}</span>
        <span>Prepared On: {{ now()->format('d-M-Y h:i A') }}</span>
    </div>
</main>
<script>
        window.addEventListener("load", () => {
            // Uncomment the next line if you want it to trigger print automatically
            // window.print();
        });
        window.addEventListener("afterprint", () => window.close());
    </script>
</body>
</html>
