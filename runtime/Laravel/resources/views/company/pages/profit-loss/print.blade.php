<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Trading Account &amp; P&amp;L - Print</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0; padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Public Sans', Roboto, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #fff;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @page { size: landscape; margin: 5mm; }
        body  { padding-left: 3mm; }

        /* ── Main header ─────────────────────────────────── */
        .report-company  { font-size: 21px; font-weight: 700; text-align: center; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 2px; }
        .report-subtitle { font-size: 12px; text-align: center; margin: 0 0 2px; }
        .report-title    { font-size: 16px; font-weight: 700; text-align: center; letter-spacing: 1px; margin: 4px 0 2px; }
        .report-period   { font-size: 12px; font-weight: 700; text-align: center; margin: 0 0 6px; }

        /* ── Continuation header (page 2+) ───────────────── */
        .cont-header     { margin-bottom: 6px; }
        .cont-company    { font-size: 13px; font-family: 'Courier New', Courier, monospace; margin: 0; }
        .cont-pageinfo   { font-size: 13px; font-family: 'Courier New', Courier, monospace; margin: 0; }
        .cont-text       { text-align: right; font-size: 13px; margin: 4px 0 0; }

        /* ── Section title (inside each section block) ───── */
        .sec-title {
            font-size: 12px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 2px; text-align: center;
            padding: 3px 6px;
            border-top: 1px solid #000; border-bottom: 1px solid #000;
            margin-bottom: 0;
        }

        /* ── Page break helper ───────────────────────────── */
        .page-break { page-break-after: always; break-after: always; }

        /* ── Table ───────────────────────────────────────── */
        table { width: 100%; border-collapse: collapse; border-spacing: 0; page-break-inside: auto; }
        thead { display: table-header-group; }
        tbody { display: table-row-group; }
        tr    { page-break-inside: avoid; break-inside: avoid; }

        .pl-table { table-layout: fixed; }
        .pl-table col.c-name { width: 28%; }
        .pl-table col.c-amt  { width: 10%; }
        .pl-table col.c-tot  { width: 12%; }
        .pl-table col.c-sep  { width: 0%; }

        /* Column group header row (Dr / Cr) */
        .pl-table thead tr.tr-side th {
            font-size: 11px; font-weight: 700; letter-spacing: 3px;
            text-align: center; padding: 2px 5px;
            border-top: 1px solid #000; border-bottom: 1px solid #ccc;
        }
        .pl-table thead tr.tr-side th.th-sep { border-left: 1px solid #000; }

        /* Column label row (Particulars / Amount / Total) */
        .pl-table thead tr.tr-col th {
            font-size: 11px; font-weight: 600;
            text-align: right; padding: 2px 5px;
            border-bottom: 1px solid #000;
        }
        .pl-table thead tr.tr-col th.th-name { text-align: left; }
        .pl-table thead tr.tr-col th.th-sep  { border-left: 1px solid #000; border-bottom: 1px solid #000; padding: 0; }

        /* Data cells */
        .pl-table td          { padding: 2px 5px; vertical-align: middle; border-bottom: 1px dotted #bbb; font-size: 11.5px; }
        .pl-table td.pl-name  { text-align: left; }
        .pl-table td.pl-amt   { text-align: right; white-space: nowrap; }
        .pl-table td.pl-tot   { text-align: right; white-space: nowrap; }
        .pl-table td.sep-col  { border-left: 1px solid #000; padding: 0; border-bottom: 1px dotted #bbb; }

        /* ── Per-cell type styles (applied independently on Dr and Cr) ─ */

        /* Stock header */
        td.t-stock.pl-name { font-weight: 700; border-top: 1px solid #000; }
        td.t-stock.pl-amt  {                   border-top: 1px solid #000; }
        td.t-stock.pl-tot  { font-weight: 700; border-top: 1px solid #000; }

        /* Group / sub-total */
        td.t-group.pl-name { font-weight: 700; padding-left: 10px; font-size: 12px; }
        td.t-group.pl-tot  { font-weight: 700; }

        /* Individual accounts */
        td.t-account.pl-name { padding-left: 22px; }

        /* Stock items */
        td.t-item.pl-name    { padding-left: 22px; }
        td.t-item .meta      { font-size: 9.5px; font-style: italic; }

        /* Transfer (Gross Profit/Loss b/d) */
        td.t-transfer.pl-name { font-weight: 700; font-style: italic; border-top: 1px solid #999; }
        td.t-transfer.pl-amt  {                                        border-top: 1px solid #999; }
        td.t-transfer.pl-tot  { font-weight: 700; font-style: italic; border-top: 1px solid #999; }

        /* Profit / Loss c/d */
        td.t-profit.pl-name { font-weight: 700; border-top: 1px solid #000; }
        td.t-profit.pl-amt  {                   border-top: 1px solid #000; }
        td.t-profit.pl-tot  { font-weight: 700; border-top: 1px solid #000; }
        td.t-loss.pl-name   { font-weight: 700; border-top: 1px solid #000; }
        td.t-loss.pl-amt    {                   border-top: 1px solid #000; }
        td.t-loss.pl-tot    { font-weight: 700; border-top: 1px solid #000; }

        /* Grand total row — still row-level since it spans uniformly */
        .r-total td         { font-weight: 700; font-size: 12px; border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 3px 5px; }
        .r-total td.sep-col { border-left: 1px solid #000; }

        /* ── Net result bar ──────────────────────────────── */
        .net-bar { display: flex; justify-content: space-between; border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 4px 8px; margin-top: 8px; font-size: 13px; font-weight: 700; }

        /* ── Footer ──────────────────────────────────────── */
        .report-footer { font-size: 11px; font-style: italic; margin-top: 12px; padding-top: 4px; border-top: 1px solid #ccc; display: flex; justify-content: space-between; }

        @media print {
            .page-break { page-break-after: always; break-after: always; }
            thead { display: table-header-group; }
            tr    { page-break-inside: avoid; break-inside: avoid; }
        }
    </style>
</head>
<body>

@php
    $trading  = $report['trading'] ?? [];
    $pl       = $report['pl']      ?? [];
    $isAcct   = ($filters['view_type'] ?? 'group_wise') === 'account_wise';
    $fmtN     = fn($n) => formatIndianNumber((float)$n, 2);
    $esc      = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

    // ── Build flat rows for one side ─────────────────────────────────────────
    $buildSide = function(
        array  $items,
        array  $sections,
        bool   $isAcct,
        float  $stockTotal,
        string $stockLabel,
        string $stockSection
    ) use (&$buildSide): array {
        $rows = [];

        if ($stockTotal > 0) {
            $rows[] = ['type' => 'stock', 'name' => $stockLabel, 'amount' => null, 'total' => $stockTotal, 'meta' => null];
            if ($isAcct) {
                foreach ($items as $grp) {
                    if (($grp['section'] ?? '') !== $stockSection) continue;
                    foreach ($grp['children'] ?? [] as $child) {
                        if ($child['is_item'] ?? false) {
                            $qty  = round(abs((float)($child['quantity'] ?? 0)), 3);
                            $rate = (float)($child['rate'] ?? 0);
                            $unit = $child['unit_name'] ?? '';
                            $rows[] = ['type' => 'item', 'name' => $child['name'], 'amount' => (float)($child['amount'] ?? 0), 'total' => null,
                                       'meta' => formatIndianNumber($qty, 3) . ($unit ? ' ' . $unit : '') . ' @ ' . formatIndianNumber($rate, 2)];
                        } else {
                            $rows[] = ['type' => 'account', 'name' => $child['name'], 'amount' => (float)($child['amount'] ?? 0), 'total' => null, 'meta' => null];
                        }
                    }
                }
            }
        }

        $bySection = [];
        foreach ($items as $item) {
            $sec = $item['section'] ?? '';
            if (!in_array($sec, ['opening_stock', 'closing_stock'])) {
                $bySection[$sec][] = $item;
            }
        }
        foreach ($sections as $section) {
            foreach ($bySection[$section] ?? [] as $group) {
                $rows[] = ['type' => 'group', 'name' => $group['name'], 'amount' => null, 'total' => (float)($group['amount'] ?? 0), 'meta' => null];
                if ($isAcct) {
                    foreach ($group['children'] ?? [] as $child) {
                        $rows[] = ['type' => 'account', 'name' => $child['name'], 'amount' => (float)($child['amount'] ?? 0), 'total' => null, 'meta' => null];
                    }
                }
            }
        }
        return $rows;
    };

    // ── Trading ───────────────────────────────────────────────────────────────
    $isGpProfit = (bool)($trading['is_gross_profit'] ?? true);
    $gpProfit   = (float)($trading['gross_profit']   ?? 0);
    $gpLoss     = (float)($trading['gross_loss']     ?? 0);

    $drRows = $buildSide($trading['debit']  ?? [], ['trading_dr_purchase', 'trading_dr_direct_expense'], $isAcct, (float)($trading['opening_stock_total'] ?? 0), 'Opening Stock', 'opening_stock');
    $crRows = $buildSide($trading['credit'] ?? [], ['trading_cr_sales', 'trading_cr_direct_income'],     $isAcct, (float)($trading['closing_stock_total'] ?? 0), 'Closing Stock', 'closing_stock');

    if ($isGpProfit && $gpProfit > 0) $drRows[] = ['type' => 'profit', 'name' => 'Gross Profit c/d', 'amount' => null, 'total' => $gpProfit, 'meta' => null];
    if (!$isGpProfit && $gpLoss > 0)  $crRows[] = ['type' => 'loss',   'name' => 'Gross Loss c/d',   'amount' => null, 'total' => $gpLoss,   'meta' => null];

    // ── P&L ───────────────────────────────────────────────────────────────────
    $isNpProfit = (bool)($pl['is_net_profit'] ?? true);
    $netProfit  = (float)($pl['net_profit']   ?? 0);
    $netLoss    = (float)($pl['net_loss']     ?? 0);

    $plDrRows = $buildSide($pl['debit']  ?? [], ['indirect_expense'], $isAcct, 0, '', '');
    $plCrRows = $buildSide($pl['credit'] ?? [], ['indirect_income'],  $isAcct, 0, '', '');

    if ($isGpProfit  && $gpProfit  > 0) array_unshift($plCrRows, ['type' => 'transfer', 'name' => 'Gross Profit b/d', 'amount' => null, 'total' => $gpProfit,  'meta' => null]);
    if (!$isGpProfit && $gpLoss    > 0) array_unshift($plDrRows, ['type' => 'transfer', 'name' => 'Gross Loss b/d',   'amount' => null, 'total' => $gpLoss,    'meta' => null]);
    if ($isNpProfit  && $netProfit > 0) $plDrRows[] = ['type' => 'profit', 'name' => 'Net Profit', 'amount' => null, 'total' => $netProfit, 'meta' => null];
    if (!$isNpProfit && $netLoss   > 0) $plCrRows[] = ['type' => 'loss',   'name' => 'Net Loss',   'amount' => null, 'total' => $netLoss,   'meta' => null];

    // ── Zip ───────────────────────────────────────────────────────────────────
    $blank   = ['type' => 'blank', 'name' => '', 'amount' => null, 'total' => null, 'meta' => null];
    $zipRows = function(array $left, array $right) use ($blank): array {
        $maxLen = max(count($left), count($right), 1);
        $out    = [];
        for ($i = 0; $i < $maxLen; $i++) {
            $out[] = ['dr' => $left[$i] ?? $blank, 'cr' => $right[$i] ?? $blank];
        }
        return $out;
    };

    $tradingZipped = $zipRows($drRows, $crRows);
    $plZipped      = $zipRows($plDrRows, $plCrRows);
    $tradingTotal  = $fmtN($trading['trading_total'] ?? 0);
    $plTotal       = $fmtN($pl['pl_total'] ?? 0);
    $netAmt        = $isNpProfit ? $netProfit : $netLoss;
    $netLabel      = $isNpProfit ? 'Net Profit' : 'Net Loss';

    // ── Helpers ───────────────────────────────────────────────────────────────
    $companyName = strtoupper($company->print_name ?? $company->name ?? '');
    $reportTitle = 'Trading A/c & Profit & Loss Statement';

    // Render 3 cells for one side of a row — each td carries its own type class
    $cells = function(array $row) use ($fmtN, $esc): string {
        $type   = $row['type'] ?? 'blank';
        $name   = $esc($row['name'] ?? '');
        $amount = $row['amount'] !== null ? $fmtN($row['amount']) : '';
        $total  = $row['total']  !== null ? $fmtN($row['total'])  : '';
        $meta   = $row['meta']   ? ' <span class="meta">(' . $esc($row['meta']) . ')</span>' : '';
        return "<td class=\"pl-name t-{$type}\">{$name}{$meta}</td>"
             . "<td class=\"pl-amt  t-{$type}\">{$amount}</td>"
             . "<td class=\"pl-tot  t-{$type}\">{$total}</td>";
    };

    // Compute inline border style for the separator column
    $sepStyle = function(array $dr, array $cr): string {
        $solid = ['stock', 'profit', 'loss'];
        if (in_array($dr['type'], $solid) || in_array($cr['type'], $solid)) return 'border-top:1px solid #000;';
        if ($dr['type'] === 'transfer'    || $cr['type'] === 'transfer')    return 'border-top:1px solid #999;';
        return '';
    };

    // Build the colgroup + thead HTML (reused on every continuation page)
    $thead = '<colgroup>'
           . '<col class="c-name"><col class="c-amt"><col class="c-tot">'
           . '<col class="c-sep">'
           . '<col class="c-name"><col class="c-amt"><col class="c-tot">'
           . '</colgroup>'
           . '<thead>'
           . '<tr class="tr-side">'
           . '<th colspan="3">DEBIT</th><th class="th-sep"></th><th colspan="3">CREDIT</th>'
           . '</tr>'
           . '<tr class="tr-col">'
           . '<th class="th-name">Particulars</th><th>Amount (₹)</th><th>Total (₹)</th>'
           . '<th class="th-sep"></th>'
           . '<th class="th-name">Particulars</th><th>Amount (₹)</th><th>Total (₹)</th>'
           . '</tr>'
           . '</thead>';

    // Total row HTML
    $totalRow = function(string $grand) use ($esc): string {
        return "<tr class=\"r-total\">"
             . "<td class=\"pl-name\">Total</td><td class=\"pl-amt\"></td><td class=\"pl-tot\">{$grand}</td>"
             . "<td class=\"sep-col\"></td>"
             . "<td class=\"pl-name\">Total</td><td class=\"pl-amt\"></td><td class=\"pl-tot\">{$grand}</td>"
             . "</tr>";
    };

    // Pagination constants for landscape
    $firstPageRows = 20;   // conservative — main header + section title take space
    $otherPageRows = 26;   // compact continuation header + section title + thead

    $page      = 1;
    $rowCount  = 0;
@endphp

{{-- ════════════════════════════════════════════════════════════════════════ --}}
{{-- MAIN HEADER (first page only)                                           --}}
{{-- ════════════════════════════════════════════════════════════════════════ --}}
<h3 class="report-company">{{ $companyName }}</h3>
<p class="report-subtitle">{{ $company->address ?? '' }}</p>
<p class="report-subtitle">GSTIN: {{ $company->gst_number ?? '' }}</p>
<h2 class="report-title">{{ $reportTitle }}</h2>
<p class="report-period">Period: {{ $datePeriod }}</p>

{{-- ════════════════════════════════════════════════════════════════════════ --}}
{{-- TRADING ACCOUNT                                                         --}}
{{-- ════════════════════════════════════════════════════════════════════════ --}}
<div class="sec-title">Trading Account</div>

<table class="pl-table">
    {!! $thead !!}
    <tbody>

@php $rowCount = 0; @endphp

@foreach ($tradingZipped as $row)
    @php
        $rowCount++;
        $dr         = $row['dr'];
        $cr         = $row['cr'];
        $sepInline  = $sepStyle($dr, $cr);
        $limit      = $page === 1 ? $firstPageRows : $otherPageRows;
        $isLast     = $loop->last;
        $needsBreak = $isLast ? ($rowCount + 1) > $limit : $rowCount > $limit;
    @endphp

    @if ($needsBreak)
        @php $page++; $rowCount = 1; @endphp
    </tbody>
</table>
<p class="cont-text">cont. on page {{ $page }}...</p>
<div class="page-break"></div>
<div class="cont-header">
    <p class="cont-company">{{ $companyName }}</p>
    <p class="cont-pageinfo">Page {{ $page }}: <strong>Trading Account</strong> &mdash; {{ $reportTitle }}</p>
</div>
<div class="sec-title">Trading Account <small style="font-size:10px;font-weight:400;letter-spacing:0">(continued)</small></div>
<table class="pl-table">
    {!! $thead !!}
    <tbody>
    @endif

    <tr>
        {!! $cells($dr) !!}
        <td class="sep-col" style="{{ $sepInline }}"></td>
        {!! $cells($cr) !!}
    </tr>
@endforeach

        {!! $totalRow($tradingTotal) !!}
    </tbody>
</table>

{{-- ════════════════════════════════════════════════════════════════════════ --}}
{{-- PAGE BREAK — P&L always starts on a fresh page                         --}}
{{-- ════════════════════════════════════════════════════════════════════════ --}}
@php $page++; $rowCount = 0; @endphp
<div class="page-break"></div>

{{-- ════════════════════════════════════════════════════════════════════════ --}}
{{-- P&L ACCOUNT                                                             --}}
{{-- ════════════════════════════════════════════════════════════════════════ --}}
<div class="cont-header">
    <p class="cont-company">{{ $companyName }}</p>
    <p class="cont-pageinfo">Page {{ $page }}: <strong>Profit &amp; Loss Account</strong> &mdash; {{ $reportTitle }}</p>
</div>
<div class="sec-title">Profit &amp; Loss Account</div>

<table class="pl-table">
    {!! $thead !!}
    <tbody>

@php $rowCount = 0; @endphp

@foreach ($plZipped as $row)
    @php
        $rowCount++;
        $dr         = $row['dr'];
        $cr         = $row['cr'];
        $sepInline  = $sepStyle($dr, $cr);
        $isLast     = $loop->last;
        $needsBreak = $isLast ? ($rowCount + 2) > $otherPageRows : $rowCount > $otherPageRows;
    @endphp

    @if ($needsBreak)
        @php $page++; $rowCount = 1; @endphp
    </tbody>
</table>
<p class="cont-text">cont. on page {{ $page }}...</p>
<div class="page-break"></div>
<div class="cont-header">
    <p class="cont-company">{{ $companyName }}</p>
    <p class="cont-pageinfo">Page {{ $page }}: <strong>Profit &amp; Loss Account</strong> &mdash; {{ $reportTitle }}</p>
</div>
<div class="sec-title">Profit &amp; Loss Account <small style="font-size:10px;font-weight:400;letter-spacing:0">(continued)</small></div>
<table class="pl-table">
    {!! $thead !!}
    <tbody>
    @endif

    <tr>
        {!! $cells($dr) !!}
        <td class="sep-col" style="{{ $sepInline }}"></td>
        {!! $cells($cr) !!}
    </tr>
@endforeach

        {!! $totalRow($plTotal) !!}
    </tbody>
</table>

<div class="net-bar">
    <span>{{ $netLabel }}</span>
    <span>{{ $fmtN($netAmt) }}</span>
</div>

<footer class="report-footer">
    <span>Prepared By: <strong>{{ auth()->user()->name ?? '' }}</strong></span>
    <span>Prepared On: <strong>{{ now()->format('d-m-Y H:i:s') }}</strong></span>
</footer>

<script>
    window.addEventListener('load', () => { window.focus(); window.print(); });
    window.addEventListener('afterprint', () => window.close());
</script>
</body>
</html>
