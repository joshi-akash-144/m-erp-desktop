<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Transport Payment Register</title>
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            line-height: 1.45;
            color: #000;
            background: #fff;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        body {
            padding: 2mm 4mm;
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
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 2px 0 10px;
            letter-spacing: 0.5px;
        }

        .report-divider {
            border-top: 2px solid #000;
            margin: 8px 0;
        }

        /* Info row: Payment Date + Bank */
        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 5px;
            padding: 0 5px;
        }

        .info-row span {
            display: inline-block;
        }

        /* Main table */
        table {
            width: 100%;
            border-collapse: collapse;
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
            font-size: 13px;
            font-weight: bold;
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            padding: 4px 8px;
            background: #fff;
            white-space: nowrap;
        }

        .report-table tbody td {
            font-size: 13px;
            padding: 3px 8px;
            border-bottom: 1px dotted #ccc;
            vertical-align: middle;
        }

        .report-table tfoot td {
            font-size: 14px;
            padding: 4px 8px;
            font-weight: bold;
            border-top: 2px solid #000;
            border-bottom: 3px solid #000;
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

        .sig-footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
        }

        .sig-block {
            text-align: center;
            min-width: 130px;
            font-size: 12px;
            font-weight: bold;
        }

        .sig-line {
            border-top: 1.5px solid #000;
            padding-top: 5px;
        }

        .prepared-by {
            font-size: 11px;
            text-align: right;
            margin-top: 5px;
            color: #666;
            padding-right: 5px;
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
    $companyName = $company?->print_name ?? $company?->name ?? '';
    $companyAddress = trim(($company?->address_one ?? '') . ' ' . ($company?->address_two ?? ''));
    $companyCity = $company?->city ?? '';

    $partiesCollection = collect($parties);
    $chunks = $partiesCollection->chunk(30);

    $runningTotal = 0;
    $grandTotal = $partiesCollection->sum('party_total');
    $globalIndex = 0;
    @endphp

    @foreach($chunks as $chunkIndex => $chunkItems)
    @if($chunkIndex > 0)
    <div style="page-break-before: always;"></div>
    @endif

    {{-- Company + Title --}}
    <h3 class="report-company">{{ $companyName }}</h3>
    @if($companyAddress || $companyCity)
    <div style="font-size:12px;text-align:center;margin-bottom:2px;">
        {{ strtolower($companyAddress) }}{{ $companyCity ? ', ' . strtolower($companyCity) : '' }}
    </div>
    @endif
    <h2 class="report-title">Transport Payment Register @if($chunks->count() > 1) <span style="font-size:13px;font-weight:normal;">(Page {{ $chunkIndex + 1 }})</span> @endif</h2>

    {{-- Payment Date & Bank --}}
    <div class="info-row">
        <span>Payment Date : {{ $payment_date }}</span>
        <span>Bank : {{ $bank_name }}</span>
    </div>

    {{-- Party-wise table --}}
    <table class="report-table">
        <colgroup>
            <col style="width:10%">
            <col style="width:18%">
            <col style="width:52%">
            <col style="width:20%">
        </colgroup>
        <thead>
            <tr>
                <th class="text-center">Sr. No.</th>
                <th class="text-center">Pay. Ref No.</th>
                <th class="text-left">Party Name</th>
                <th class="text-right">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            @if($chunkIndex > 0)
            <tr>
                <td colspan="3" class="text-right" style="font-weight:bold;font-style:italic;">B/F (Brought Forward)</td>
                <td class="text-right" style="font-weight:bold;">{{ formatIndianNumber($runningTotal, 2) }}</td>
            </tr>
            @endif

            @foreach($chunkItems as $party)
            @php
            $runningTotal += $party['party_total'];
            $globalIndex++;
            @endphp
            <tr>
                <td class="text-center">{{ $globalIndex }}</td>
                <td class="text-center" style="font-weight:bold;">{{ $party['voucher_serial'] }}</td>
                <td class="text-left" style="color:#000;">
                    {{ $party['party_name'] }}@if(!empty($party['party_city'])) <span style="color:#666;">({{ $party['party_city'] }})</span>@endif
                </td>
                <td class="text-right" style="font-weight:bold;">{{ formatIndianNumber($party['party_total'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            @if($chunkIndex == $chunks->keys()->last())
            <tr>
                <td colspan="3" class="text-right">Total Amount</td>
                <td class="text-right">{{ formatIndianNumber($grandTotal, 2) }}</td>
            </tr>
            @else
            <tr>
                <td colspan="3" class="text-right" style="font-style:italic;">Continue...</td>
                <td class="text-right">{{ formatIndianNumber($runningTotal, 2) }}</td>
            </tr>
            @endif
        </tfoot>
    </table>

    @if($chunkIndex == $chunks->keys()->last())
    <div class="prepared-by">Prepared by: {{ $created_by }}</div>

    {{-- Signatures --}}
    <div class="sig-footer">
        <div class="sig-block">
            <div class="sig-line">Approved By</div>
        </div>
        <div class="sig-block">
            <div class="sig-line">Checked By</div>
        </div>
        <div class="sig-block">
            <div class="sig-line">Payment By</div>
        </div>
    </div>
    @endif
    @endforeach

    <script>
        window.addEventListener('afterprint', () => window.close());
    </script>
</body>

</html>