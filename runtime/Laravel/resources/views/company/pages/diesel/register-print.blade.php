<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Diesel Expense Register</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0; padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Public Sans', Roboto, Arial, sans-serif;
            font-size: 11px; line-height: 1.45; color: #000;
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
        .report-meta    { font-size: 13px; text-align: left; margin: 6px 0; }

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
            font-size: 11px; font-weight: 700;
            border-top: 1px solid #000; border-bottom: 1px solid #000;
            padding: 4px 5px; background: #fff; text-align: center;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .report-table tbody td {
            font-size: 11px; padding: 3px 5px;
            border-bottom: 1px dotted #bbb; vertical-align: middle;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .report-table tfoot td {
            font-size: 11px; padding: 4px 5px; font-weight: 700;
            border-top: 1px solid #000; border-bottom: 1px solid #000;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }

        .text-right  { text-align: right !important; }
        .text-center { text-align: center !important; }
        .text-left   { text-align: left !important; }

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
    // Group items by Diesel Account Name
    $groupedItems = $items->groupBy(function($item) {
        $dieselModel = $item->getRelation('diesel');
        return $dieselModel->account->name ?? 'UNKNOWN ACCOUNT';
    });

    // Flatten into print lines
    $printLines = [];
    foreach ($groupedItems as $accountName => $accItems) {
        $printLines[] = ['type' => 'account_header', 'name' => $accountName];
        
        $sr = 1;
        $totalDiesel = 0;
        $totalAmount = 0;
        
        foreach ($accItems as $item) {
            $totalDiesel += (float) $item->diesel;
            $totalAmount += (float) $item->amount;
            $printLines[] = ['type' => 'row', 'data' => $item, 'sr' => $sr++];
        }
        
        $printLines[] = ['type' => 'account_footer', 'totalDiesel' => $totalDiesel, 'totalAmount' => $totalAmount];
    }

    // Row limits per page (A4 landscape)
$firstPageRows = 26;
$otherPageRows = 28;

    // Pre-calculate total pages
    $tempPage = 1; $tempRowCount = 0;
    foreach ($printLines as $line) {
        $tempRowCount += ($line['type'] === 'account_header') ? 3 : 1; // Header takes more visual space
        $limit = $tempPage === 1 ? $firstPageRows : $otherPageRows;
        if ($tempRowCount > $limit) { $tempPage++; $tempRowCount = 1; }
    }
    $totalPages = $tempPage;
    $page = 1;
@endphp

<header>
    <h3 class="report-company">{{ $company?->print_name ?? $company?->name ?? '' }}</h3>
    <h2 class="report-title">Diesel Entry Register</h2>
    <div class="report-sub">
        Entry Date : <strong>{{ $fromDate ? \Carbon\Carbon::parse($fromDate)->format('d/m/Y') : '-' }} to {{ $toDate ? \Carbon\Carbon::parse($toDate)->format('d/m/Y') : '-' }}</strong>
    </div>
    <div class="report-divider"></div>
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
            @php $rowCountOnPage = 0; @endphp
            
            @foreach($printLines as $line)
                @php
                    $rowCountOnPage += ($line['type'] === 'account_header') ? 3 : 1;
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
                        Page {{ $page }} of {{ $totalPages }}: Diesel Expense &mdash; Diesel Expense
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
                
                @if($line['type'] === 'account_header')
                    <tr>
                        <td colspan="15" style="border:none; text-align:left; font-size:13px; padding-top:12px; padding-bottom:4px;">
                            <strong>Diesel Account : {{ $line['name'] }}</strong>
                        </td>
                    </tr>
                @elseif($line['type'] === 'row')
                    @php $item = $line['data']; @endphp
                    <tr>
                        <td class="text-center">{{ $line['sr'] }}</td>
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
                                $dieselModel = $item->getRelation('diesel');
                                $rate = (float)$item->rate > 0 ? (float)$item->rate : (float)($dieselModel->diesel_rate ?? 0);
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
                @elseif($line['type'] === 'account_footer')
                    <tr>
                        <td colspan="8" class="text-right"><strong>Total</strong></td>
                        <td class="text-center">{{ formatIndianNumber($line['totalDiesel'], 2) }}</td>
                        <td colspan="2"></td>
                        <td class="text-right">{{ formatIndianNumber($line['totalAmount'], 2) }}</td>
                        <td colspan="3"></td>
                    </tr>
                @endif
            @endforeach
        </tbody>
        </tfoot>
    </table>
</main>


<script>window.addEventListener('afterprint', () => window.close());</script>
</body>
</html>
