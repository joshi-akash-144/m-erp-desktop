<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Party Reference List – {{ $voucher->voucher?->voucher_serial ?? $voucher->id }}</title>
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
            <h2 class="report-title">Party Reference List @if($chunks->count() > 1) <span style="font-size:12px;font-weight:normal;">(Page {{ $chunkIndex + 1 }})</span> @endif</h2>
            <div class="report-divider"></div>
            <div class="report-meta">
                <span><strong>Payment Date :</strong> {{ $payment_date }}</span>
            </div>
            <div class="report-meta" style="margin-top: 1px;">
                <span><strong>Party Name :</strong> {{ $voucher->account?->name ?? '-' }} @if(!empty($voucher->account?->city))({{ $voucher->account->city }})@endif</span>
                <span><strong>Bank Name :</strong> {{ $bank_name }}</span>
            </div>
        </header>

        <main>
            <table class="report-table" style="margin-top: 5px;">
                <colgroup>
                    <col style="width:10%">
                    <col style="width:20%">
                    <col style="width:20%">
                    <col style="width:15%">
                    <col style="width:20%">
                    <col style="width:15%">
                </colgroup>
                <thead>
                    <tr>
                        <th class="text-center">Sr. No.</th>
                        <th class="text-center">Voucher No.</th>
                        <th class="text-center">Ref No</th>
                        <th class="text-center">Ref Date</th>
                        <th class="text-left">Cheque No</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>

                <tbody>
                    @if($chunkIndex > 0)
                    <tr>
                        <td colspan="5" class="text-right" style="font-weight:600;font-style:italic;">B/F (Brought Forward)</td>
                        <td class="text-right" style="font-weight:600;">{{ formatIndianNumber($runningTotal, 2) }}</td>
                    </tr>
                    @endif

                    @php $srNo = ($chunkIndex * 43) + 1; @endphp
                    @foreach($chunkItems as $item)
                                        @php
        $runningTotal += (float) $item['amount'];
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ $srNo++ }}</td>
                                            <td class="text-center">{{$voucher->voucher?->voucher_serial ?? '-'  }}</td>
                                            <td class="text-center fw-bold">{{ $item['ref_no'] ?? '-' }}</td>
                                            <td class="text-center">{{ $item['ref_date'] ?? '-' }}</td>
                                            <td class="text-left">{{$voucher->payment?->cheque_number ?? '-' }}</td>
                                            <td class="text-right fw-bold">{{ formatIndianNumber((float) $item['amount'], 2) }}</td>
                                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    @if($chunkIndex == $chunks->keys()->last())
                    <tr>
                        <td colspan="4" style="border: none !important; border-top: 1px solid #000 !important;"></td>
                        <td class="text-right"><strong>Total</strong></td>
                        <td class="text-right fw-bold">{{ formatIndianNumber((float) $runningTotal, 2) }}</td>
                    </tr>
                    @if($voucherParticular)
                    <tr>
                        <td colspan="4" style="border: none !important;"></td>
                        <td class="text-right">{{$voucherParticular['particular_name']}}</td>
                        <td class="text-right fw-bold">- {{ formatIndianNumber((float)$voucherParticular['amount'], 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="4" style="border: none !important;"></td>
                        <td class="text-right"><strong>Payable Amount</strong></td>
                        <td class="text-right fw-bold">{{ formatIndianNumber((float) $total_amount, 2) }}</td>
                    </tr>
                    @endif
                    @else
                    <tr>
                        <td colspan="5" class="text-right" style="font-style:italic;">Continue...</td>
                        <td class="text-right">{{ formatIndianNumber($runningTotal, 2) }}</td>
                    </tr>
                    @endif
                </tfoot>
            </table>
        </main>

        @if($chunkIndex == $chunks->keys()->last())
        @if($narration)
        <div style="font-size:11px; margin-top:6px;">
            <strong>Narration :</strong> {{ $narration }}
        </div>
        @endif

        <footer class="report-footer">
            <span>Prepared By : <strong>{{ $voucher->creator->name ?? auth()->user()->name ?? '' }}</strong></span>
        </footer>
        @endif
    @endforeach

    <script>
        window.addEventListener('afterprint', () => window.close());
    </script>
</body>

</html>