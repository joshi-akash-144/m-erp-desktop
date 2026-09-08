@php
/**
 * Profit & Loss Excel export view.
 * 7-column T-account layout: A=DrName | B=DrAmount | C=DrTotal | D=sep | E=CrName | F=CrAmount | G=CrTotal
 * Amount/Total cells contain raw floats — no number_format() — so Excel treats them as numbers.
 * AfterSheet in ProfitLossExport.php applies number format, bold, borders.
 */
$fmtAmt = fn(?float $v): string => ($v === null || $v === 0.0) ? '' : rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
$rawNum = fn(?float $v): string => ($v === null) ? '' : (string) $v;
@endphp
<table>
    {{-- ── Header rows 1–4 ─────────────────────────────────────────────────────── --}}
    <tr><td colspan="7" style="font-weight:bold;text-align:center;font-size:18pt;">{{ strtoupper($company->company_name ?? $company->name ?? '') }}</td></tr>
    <tr><td colspan="7" style="text-align:center;">GSTIN: {{ $company->gst_number ?? $company->gstin ?? '' }}</td></tr>
    <tr><td colspan="7" style="font-weight:bold;text-align:center;font-size:14pt;">Profit &amp; Loss Account</td></tr>
    <tr><td colspan="7" style="font-weight:bold;text-align:center;">Period: {{ $datePeriod }}</td></tr>

    {{-- ══ TRADING ACCOUNT ══════════════════════════════════════════════════════ --}}
    <tr><td colspan="7" style="font-weight:bold;text-align:center;border-top:1px solid #000;border-bottom:1px solid #000;">TRADING ACCOUNT</td></tr>
    <tr>
        <td colspan="3" style="font-weight:bold;text-align:center;">DEBIT</td>
        <td style="border-left:1px solid #000;"></td>
        <td colspan="3" style="font-weight:bold;text-align:center;">CREDIT</td>
    </tr>
    <tr>
        <td style="font-weight:bold;">Particulars</td>
        <td style="font-weight:bold;text-align:right;">Amount (₹)</td>
        <td style="font-weight:bold;text-align:right;">Total (₹)</td>
        <td style="border-left:1px solid #000;"></td>
        <td style="font-weight:bold;">Particulars</td>
        <td style="font-weight:bold;text-align:right;">Amount (₹)</td>
        <td style="font-weight:bold;text-align:right;">Total (₹)</td>
    </tr>

    {{-- Trading data rows --}}
    @foreach($tradingZipped as $row)
    @php $dr = $row['dr']; $cr = $row['cr']; @endphp
    <tr>
        <td>{{ $dr['name'] }}</td>
        <td style="text-align:right;">{{ $rawNum($dr['amount']) }}</td>
        <td style="text-align:right;">{{ $rawNum($dr['total']) }}</td>
        <td style="border-left:1px solid #000;"></td>
        <td>{{ $cr['name'] }}</td>
        <td style="text-align:right;">{{ $rawNum($cr['amount']) }}</td>
        <td style="text-align:right;">{{ $rawNum($cr['total']) }}</td>
    </tr>
    @endforeach

    {{-- Trading grand total row --}}
    <tr>
        <td style="font-weight:bold;">Total</td>
        <td></td>
        <td style="font-weight:bold;text-align:right;">{{ $rawNum($tradingTotal) }}</td>
        <td style="border-left:1px solid #000;"></td>
        <td style="font-weight:bold;">Total</td>
        <td></td>
        <td style="font-weight:bold;text-align:right;">{{ $rawNum($tradingTotal) }}</td>
    </tr>

    {{-- Blank separator --}}
    <tr><td colspan="7"></td></tr>

    {{-- ══ PROFIT & LOSS ACCOUNT ════════════════════════════════════════════════ --}}
    <tr><td colspan="7" style="font-weight:bold;text-align:center;border-top:1px solid #000;border-bottom:1px solid #000;">PROFIT &amp; LOSS ACCOUNT</td></tr>
    <tr>
        <td colspan="3" style="font-weight:bold;text-align:center;">DEBIT</td>
        <td style="border-left:1px solid #000;"></td>
        <td colspan="3" style="font-weight:bold;text-align:center;">CREDIT</td>
    </tr>
    <tr>
        <td style="font-weight:bold;">Particulars</td>
        <td style="font-weight:bold;text-align:right;">Amount (₹)</td>
        <td style="font-weight:bold;text-align:right;">Total (₹)</td>
        <td style="border-left:1px solid #000;"></td>
        <td style="font-weight:bold;">Particulars</td>
        <td style="font-weight:bold;text-align:right;">Amount (₹)</td>
        <td style="font-weight:bold;text-align:right;">Total (₹)</td>
    </tr>

    {{-- P&L data rows --}}
    @foreach($plZipped as $row)
    @php $dr = $row['dr']; $cr = $row['cr']; @endphp
    <tr>
        <td>{{ $dr['name'] }}</td>
        <td style="text-align:right;">{{ $rawNum($dr['amount']) }}</td>
        <td style="text-align:right;">{{ $rawNum($dr['total']) }}</td>
        <td style="border-left:1px solid #000;"></td>
        <td>{{ $cr['name'] }}</td>
        <td style="text-align:right;">{{ $rawNum($cr['amount']) }}</td>
        <td style="text-align:right;">{{ $rawNum($cr['total']) }}</td>
    </tr>
    @endforeach

    {{-- P&L grand total row --}}
    <tr>
        <td style="font-weight:bold;">Total</td>
        <td></td>
        <td style="font-weight:bold;text-align:right;">{{ $rawNum($plTotal) }}</td>
        <td style="border-left:1px solid #000;"></td>
        <td style="font-weight:bold;">Total</td>
        <td></td>
        <td style="font-weight:bold;text-align:right;">{{ $rawNum($plTotal) }}</td>
    </tr>

    {{-- Blank separator --}}
    <tr><td colspan="7"></td></tr>

    {{-- Net result row --}}
    <tr>
        <td style="font-weight:bold;">{{ $netLabel }}</td>
        <td></td>
        <td></td>
        <td style="border-left:1px solid #000;"></td>
        <td></td>
        <td></td>
        <td style="font-weight:bold;text-align:right;">{{ $rawNum($netAmt) }}</td>
    </tr>
</table>
