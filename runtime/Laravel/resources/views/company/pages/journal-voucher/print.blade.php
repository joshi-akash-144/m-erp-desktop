<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Journal Voucher Register - Print</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0; padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Public Sans', Roboto, Arial, sans-serif;
            font-size: 13px; line-height: 1.4; color: #000;
            background-color: #fff;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        @page { size: {{ $orientation ?? 'portrait' }}; margin: 10mm; }
        body { padding-left: 5mm; min-height: 100vh; }

        .report-company  { font-size: 21px; font-weight: bold; text-align: center; text-transform: uppercase; letter-spacing: .5px; margin: 0 0 2px; }
        .report-title    { font-size: 16px; font-weight: 700; text-align: center; margin: 3px 0 10px; letter-spacing: 1px; }
        .report-date     { display: flex; align-items: center; justify-content: space-between; font-size: 12px; font-weight: 700; margin: 3px 0 10px; letter-spacing: 1px; }
        .report-content  { margin-top: 4px; }

        .continuation-text   { text-align: right; font-size: 14px; margin: 5px 0 0; }
        .continuation-header { margin-bottom: 8px; }
        .company-name        { font-size: 13px; font-family: 'Courier New', monospace; margin: 0; }
        .page-info           { font-size: 13px; font-family: 'Courier New', monospace; margin: 0; }
        .report-name         { letter-spacing: 2px; }

        table { width: 100%; border-collapse: collapse; page-break-inside: auto; border-spacing: 0; }
        th, td { padding: 4px 6px; text-align: left; vertical-align: top; }
        thead  { display: table-header-group; }
        tbody  { display: table-row-group; }
        tfoot  { display: table-footer-group; }
        tr     { page-break-inside: avoid; break-inside: avoid; }

        .report-table thead th { font-size: 13px; font-weight: 600; border-top: 1px solid #000; border-bottom: 1px solid #000; background-color: #fff; padding: 4px 6px; }
        .report-table tbody td { border-bottom: 1px dotted #bbb; font-size: 12.5px; padding: 4px 6px; }
        .report-table tbody tr.balance-row td { border-top: 1px solid #000; border-bottom: 1px solid #000; font-weight: bold; }

        .text-center { text-align: center !important; }
        .text-end    { text-align: right !important; }
        .text-start  { text-align: left !important; }
        .text-nowrap { white-space: nowrap; }
        .text-truncate { overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }

        .grand-total-row { background-color: #fff !important; font-weight: 700; border-top: 1px solid #000 !important; border-bottom: 1px solid #000 !important; }

        .report-footer { font-size: 11.5px; font-style: italic; margin-top: 12px; padding-top: 5px; display: flex; justify-content: space-between; align-items: center; }

        .w-100 { width: 100% !important; }

        @media print {
            html, body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; margin: 0; padding: 0; }
            .no-print   { display: none !important; }
            .conditional-page-break { page-break-after: always; break-after: always; }
            @page { size: {{ $orientation ?? 'portrait' }}; }
            table { width: 100% !important; page-break-inside: auto; }
            tr    { page-break-inside: avoid; break-inside: avoid; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
            .report-table thead th { background-color: #fff !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .grand-total-row        { background-color: #fff !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>
<body>
@php
    $reportTitle = 'Journal Voucher Register';
    $orientation = $orientation ?? 'portrait';

    if ($orientation === 'landscape') {
        $firstPageRows = 14;
        $otherPageRows = 20;
    } else {
        $firstPageRows = 30;
        $otherPageRows = 32;
    }

    $transactions   = $voucherData ?? [];
    $totalItems     = count($transactions);
    $grandTotalDebit  = 0;
    $grandTotalCredit = 0;
    foreach ($transactions as $t) {
        $t = (object) $t;
        if (isset($t->row_type) && $t->row_type === 'transaction') {
            $grandTotalDebit  += (float) $t->debit;
            $grandTotalCredit += (float) $t->credit;
        }
    }

    // Pre-calculate total pages
    $tempPage = 1; $tempRowCount = 1;
    foreach ($transactions as $index => $item) {
        $tempRowCount++;
        $limit      = $tempPage === 1 ? $firstPageRows : $otherPageRows;
        $needsBreak = false;
        if ($index === $totalItems - 1) {
            if (($tempRowCount + 1) > $limit) $needsBreak = true;
        } else {
            if ($tempRowCount > $limit) $needsBreak = true;
        }
        if ($needsBreak) { $tempPage++; $tempRowCount = 1; }
    }
    $totalPages = $tempPage;
    $page = 1;
@endphp

<header>
    <h3 class="report-company">{{ strtoupper($company->name ?? 'COMPANY NAME') }}</h3>
    <h2 class="report-title">{{ $reportTitle }}</h2>
    <div class="report-date">
        @if($account)
            <span>Ledger Account: {{ $account }}</span>
        @endif
    </div>
</header>

<main>
    <div class="report-content">
        <table class="report-table">
            <thead>
                <tr class="balance-row">
                    <td colspan="2" class="text-start">
                        <strong>
                            Date:
                            {{ !empty($filters['start_date']) ? format_date($filters['start_date']) : '' }}
                            to
                            {{ !empty($filters['end_date']) ? format_date($filters['end_date']) : '' }}
                        </strong>
                    </td>
                    <td colspan="3" class="text-end"></td>
                </tr>
                <tr>
                    @foreach ($tableConfig['columns'] as $column)
                        <th class="{{ $column['class'] ?? '' }} text-nowrap" style="width: {{ $column['width'] ?? 'auto' }};">
                            {{ $column['label'] }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @php $rowCountOnPage = 1; @endphp

                @forelse($transactions as $item)
                    @php
                        $item = (object) $item;
                        $rowCountOnPage++;
                        $currentPageLimit = $page === 1 ? $firstPageRows : $otherPageRows;
                        $needsPageBreak = false;
                        if ($loop->last) {
                            if (($rowCountOnPage + 1) > $currentPageLimit) $needsPageBreak = true;
                        } else {
                            if ($rowCountOnPage > $currentPageLimit) $needsPageBreak = true;
                        }
                    @endphp

                    @if($needsPageBreak)
                        @php $page++; $rowCountOnPage = 1; @endphp
                        </tbody>
                    </table>

                    <p class="continuation-text">cont. on page {{ $page }}...</p>
                    <div class="conditional-page-break"></div>

                    <div class="continuation-header">
                        <p class="company-name">{{ strtoupper($company->legal_name ?? $company->name ?? '') }}</p>
                        <p class="page-info">
                            Page {{ $page }} of {{ $totalPages }}: <span class="report-name">{{ $reportTitle }}</span>
                            @if($account) &mdash; {{ $account }} @endif
                        </p>
                    </div>

                    <table class="w-100 report-table" style="table-layout: fixed; width: 100%;">
                        <thead>
                            <tr>
                                @foreach ($tableConfig['columns'] as $column)
                                    <th class="{{ $column['class'] ?? '' }} text-nowrap" style="width: {{ $column['width'] }};">
                                        {{ $column['label'] }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                    @endif

                    <tr>
                        <td class="text-start text-nowrap" style="width: 12%;">
                            {{ $item->voucher_date ? format_date($item->voucher_date) : '' }}
                        </td>
                        @if(isset($item->row_type) && $item->row_type === 'transaction')
                            <td class="text-start text-nowrap text-truncate" style="width: 38%; max-width: 0;">
                                {{ $item->particulars ?? '' }}
                            </td>
                            <td class="text-center text-nowrap" style="width: 14%;">{{ $item->voucher_number ?? '' }}</td>
                            <td class="text-end text-nowrap" style="width: 18%;">{{ $item->debit  > 0 ? formatIndianNumber($item->debit)  : '' }}</td>
                            <td class="text-end text-nowrap" style="width: 18%;">{{ $item->credit > 0 ? formatIndianNumber($item->credit) : '' }}</td>
                        @else
                            <td class="text-start text-nowrap text-truncate" colspan="4" style="max-width: 0;">
                                <span style="font-style: italic; color: #555;">Narration : ( {{ $item->particulars }} )</span>
                            </td>
                        @endif
                    </tr>

                    @if($loop->last)
                        <tr class="grand-total-row">
                            <td colspan="2" class="text-end"></td>
                            <td colspan="1" class="text-center"><strong>Total</strong></td>
                            <td colspan="1" class="text-end"><strong>{{ formatIndianNumber($grandTotalDebit) }}</strong></td>
                            <td colspan="1" class="text-end"><strong>{{ formatIndianNumber($grandTotalCredit) }}</strong></td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="5" class="text-center">No records found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</main>

<footer class="report-footer">
    <span>Prepared By: <strong>{{ auth()->user()->name ?? 'User' }}</strong></span>
    <span>Prepared On: <strong>{{ now()->format('d-m-Y H:i:s') }}</strong></span>
</footer>

<script>window.addEventListener("afterprint", () => window.close());</script>
</body>
</html>
