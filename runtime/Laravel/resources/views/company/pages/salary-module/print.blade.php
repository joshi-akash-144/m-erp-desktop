<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Voucher – {{ $expense->voucher?->voucher_serial ?? $expense->id }}</title>
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
            size: A4 portrait;
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
        }
    </style>
</head>

<body>

    <button class="btn-print no-print" onclick="window.print()">Print</button>

    @php
    $items = $expense->items;
    $itemsCollection = collect($items);
    $chunks = $itemsCollection->chunk(43);
    $runningTotal = 0;
    @endphp

    @foreach($chunks as $chunkIndex => $chunkItems)
    @if($chunkIndex > 0)
    <div style="page-break-before: always;"></div>
    @endif

    <header>
        <h3 class="report-company">{{ $company?->print_name ?? $company?->name ?? '' }}</h3>
        <h2 class="report-title">Salary Voucher @if($chunks->count() > 1) <span style="font-size:12px;font-weight:normal;">(Page {{ $chunkIndex + 1 }})</span> @endif</h2>
        <div class="report-sub">
            Voucher No. : <strong>{{ $expense->voucher?->voucher_serial ?? '-' }}</strong>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            Voucher Date : <strong>{{ $expense->voucher_date ? \Carbon\Carbon::parse($expense->voucher_date)->format('d/m/Y') : '-' }}</strong>
        </div>
        <div class="report-divider"></div>
        <div class="report-meta">
            <span><strong>Month :</strong> {{ $expense->month ?? '-' }}</span>
            <span><strong>Expense Type (Dr) :</strong> {{ $expense->expenseAccount?->name ?? '-' }}</span>
        </div>
    </header>

    <main>
        <table class="report-table">
            <colgroup>
                <col style="width:10%">
                <col style="width:25%">
                <col style="width:20%">
                <col style="width:15%">
                <col style="width:30%">
            </colgroup>
            <thead>
                <tr>
                    <th>Ref No</th>
                    <th class="text-left">Party (Cr)</th>
                    <th class="text-left">Vehicle</th>
                    <th class="text-right">Amount</th>
                    <th class="text-left">Remark</th>
                </tr>
            </thead>

            <tbody>
                @if($chunkIndex > 0)
                <tr>
                    <td colspan="3" class="text-right" style="font-weight:600;font-style:italic;">B/F (Brought Forward)</td>
                    <td class="text-right" style="font-weight:600;">{{ formatIndianNumber($runningTotal, 2) }}</td>
                    <td></td>
                </tr>
                @endif

                @foreach($chunkItems as $item)
                @php
                $runningTotal += (float) $item->amount;
                @endphp
                <tr>
                    <td class="text-center">{{ $item->ref_no ?? '' }}</td>
                    <td>{{ $item->account?->name ?? '-' }}</td>
                    <td>{{ $item->vehicle?->name ?? '' }}</td>
                    <td class="text-right">{{ formatIndianNumber((float) $item->amount, 2) }}</td>
                    <td>{{ $item->remark ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>

            <tfoot>
                @if($chunkIndex == $chunks->keys()->last())
                <tr>
                    <td colspan="3" class="text-right"><strong>Total</strong></td>
                    <td class="text-right">{{ formatIndianNumber((float) $expense->total_amount, 2) }}</td>
                    <td></td>
                </tr>
                @else
                <tr>
                    <td colspan="3" class="text-right" style="font-style:italic;">Continue...</td>
                    <td class="text-right">{{ formatIndianNumber($runningTotal, 2) }}</td>
                    <td></td>
                </tr>
                @endif
            </tfoot>
        </table>
    </main>

    @if($chunkIndex == $chunks->keys()->last())
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
    @endif
    @endforeach

    <script>
        window.addEventListener('afterprint', () => window.close());
    </script>
</body>

</html>