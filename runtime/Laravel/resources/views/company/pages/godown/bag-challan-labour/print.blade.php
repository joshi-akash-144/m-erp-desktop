<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Bags Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid black;
        }

        table, th {
            /* text-align: center !important; */
        }

        .m-0  { margin: 0; }
        .mb-1 { margin-bottom: 10px; }

        th, td {
            padding: 3px;
            font-size: 13px;
            /* text-align: left; */
        }

        .conditional-page-break {
            page-break-after: always;
        }

        .fw-bold { font-weight: bold; }

        /* ── Header ── */
        .report-company  { font-size: 20px; font-weight: bold; text-align: center; text-transform: uppercase; margin: 0 0 2px; }
        .report-subtitle { font-size: 12px; text-align: center; margin: 0 0 2px; }
        .report-title    { font-size: 15px; font-weight: 700; text-align: center; margin: 4px 0 6px; letter-spacing: 1px; }

        /* ── Alignment ── */
        .text-center { text-align: center !important; }
        .text-start  { text-align: left !important; }
        .text-end    { text-align: right !important; }

        @media print {
            body { margin: 0; }
            table { padding: 0; margin: 0; }
        }
    </style>
</head>
<body>

@php
    $total_bags   = 0;
    $total_amount = 0;
    $rate         = (float) $record->rate;
    $reportTitle  = 'Bag Challan Labour Report';
    $page         = 1;
    $datePeriod   = $record->created_at? \Carbon\Carbon::parse($record->created_at)->format('d-m-Y'): '-';
@endphp

 <header class="print-header">
    <h3 class="report-company">{{ strtoupper($company) }}</h3>
    <h6 class="report-subtitle">GSTIN: {{ $gstNumber }}</h6>
    <h5 class="report-title">{{ $reportTitle }}</h5>   
</header>

<table>
    <thead>
        <tr>
            <th style="width: 5%">No</th>
            <th class="text-center" style="width: 15%">Date</th>
            <th class="text-center" style="width: 15%">Truck No.</th>
            <th class="text-start" style="width: 30%">Material</th>
            <th class="text-end" style="width: 15%">Bags</th>
            <th class="text-end" style="width: 20%">Amount by Bags</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($record->items as $i => $item)
            @php
                $bags           = (float) $item->bags;
                $amount_by_bags = $bags * $rate;               
                $total_bags    += $bags;
                $total_amount  += $amount_by_bags;
                $grn            = $item->grn;
                $material       = $grn?->details->pluck('item.name')->filter()->unique()->implode(', ') ?: '-';                
            @endphp
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td class="text-center">
                    {{ $grn?->grn_date ? \Carbon\Carbon::parse($grn->grn_date)->format('d-m-Y') : '-' }}
                </td>
                <td class="text-center">{{ $grn?->vehicle_number ?? '-' }}</td>
                <td class="text-start">{{ $material }}</td>
                <td class="text-end">{{ number_format($bags ?: 0, 2) }}</td>
                <td class="text-end">{{ number_format($amount_by_bags ?: 0, 2) }}</td>
            </tr>
        @endforeach

        <tr>
            <td colspan="4" class="text-end"><b>Total: </b></td>
            <td class="text-end"><b>{{ number_format($total_bags ?: 0, 2) }}</b></td>
            <td class="text-end"><b>{{ number_format($total_amount ?: 0, 2) }}</b></td>
        </tr>
        <tr>
            <td colspan="5" class="text-end"><b>Loading Charges: </b></td>
            <td class="text-end"><b>{{ number_format($record->loading_amount ?: 0, 2) }}</b></td>
        </tr>
        <tr>
            <td colspan="5" class="text-end"><b>Total Amount: </b></td>
            <td class="text-end"><b>{{ number_format($record->total_amount ?: 0, 2) }}</b></td>
        </tr>
    </tbody>
</table>

<script>
    addEventListener("afterprint", (event) => {
        window.close();
    });
</script>
</body>
</html>
