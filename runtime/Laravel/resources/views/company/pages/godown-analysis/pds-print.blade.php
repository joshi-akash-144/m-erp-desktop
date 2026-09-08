<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Payment Difference System Report</title>
    <style>
        table,
        th,
        td {
            border: 1px solid black;
            border-collapse: collapse;
            padding: 3px;
            font-size: 0.8rem;
        }

        .p-O {
            padding: 0;
        }

        .m-0 {
            margin: 0;
        }

        .p-1 {
            font-size: 13px;
        }

        .w-6 {
            width: 7%;
            border-right: 0px;
        }

        .w-27 {
            width: 26%;
            font-weight: bold;
            border-left: 0px;
        }

        @page {
            size: a4;
        }

        .be-0 {
            border-left: 0px;
        }

        .bs-0 {
            border-left: 0px;
        }

        .pdetail {
            margin-bottom: 5px;
        }

        .text-align-end {
            text-align: end;
        }
    </style>
</head>

<body>
    <center>
        <h4 class="p-0 m-0">{{ Str::upper($companyName) }}</h4>
        <p class="p-0 m-0 p-1">{{ $companyAddress }}{{ $postalCode ? ' - '.$postalCode : '' }}, Ph.
            {{ $mobileNo }}</p>
    </center>
    <hr>
    <center>
        <h4 class="p-0 m-0">Price Difference Statement</h4>
    </center>
    @php
        $date = \Carbon\Carbon::parse($analysis->created_at)->format('d-m-Y');
        $pdate = \Carbon\Carbon::parse($analysis->grn->grn_date)->format('d-m-Y');
    @endphp
    <p class="p-0 m-0 p-1 text-align-end">Date: {{ $date }}</p>
    <table width="100%" class="pdetail">
        <tbody>
            <tr>
                <td class="w-6">GRN No.</td>
                <td class="w-27">{{ $analysis->grn->grn_serial }}</td>
                <td class="w-6">Date</td>
                <td class="w-27">{{ $pdate }}</td>
                <td class="w-6">G.R. Qty.</td>
                <td class="w-27">{{ number_format($analysis->grn->details->sum('quantity'), 3) }}</td>
            </tr>
            <tr>
                <td class="w-6">Name</td>
                <td class="w-27">{{ $analysis->grn->account->name ?? '--' }}</td>
                <td class="w-6">P Bill No.</td>
                <td class="w-27">{{ $analysis->grn->reference_number ?? '--' }}</td>
                <td class="w-6">Rate</td>
                <td class="w-27">{{ collect($analysis->grn->details)->pluck('inclusive_rate')->filter()->unique()->join(', ') }}</td>
            </tr>
            <tr>
                <td class="w-6">City</td>
                <td class="w-27">{{ $analysis->grn->account->city ?? '--' }}</td>
                <td class="w-6">Vehicle</td>
                <td class="w-27">{{ $analysis->grn->vehicle_number ?? '--' }}</td>
                <td class="w-6">Amount</td>
                <td class="w-27">{{ format_number(collect($analysis->grn->details)->sum(fn($d) => $d->quantity * $d->inclusive_rate), 2) }}</td>
            </tr>
            <tr>
                <td class="w-6">Product</td>
                <td class="w-27">{{ collect($analysis->grn->details)->pluck('item.name')->unique()->filter()->join(', ') ?: '--' }}</td>
                <td class="w-6">Destination</td>
                <td class="w-27">{{ collect($analysis->grn->details)->pluck('destination.name')->unique()->filter()->join(', ') ?: '--' }}</td>
                <td class="w-6">Condition</td>
                <td class="w-27">{{ collect($analysis->grn->details)->pluck('condition.name')->unique()->filter()->join(', ') ?: '--' }}</td>
            </tr>
        </tbody>
    </table>
    <table width='100%'>
        <thead>
            <tr>
                <th>Element</th>
                <th>Actual</th>
                <th>Diff</th>
                <th>Rebate</th>
                <th>Premium</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($analysis->details as $data)
                <tr>
                    <td>{{ $data->element->name ?? '--' }} ({{ ($data->element->element_range ?? 0) == 1 ? '>=' : '<=' }}
                        {{ number_format((float)$data->guarantee, 2) }})</td>
                    <td>{{ number_format((float)$data->actual, 4) }}</td>
                    <td>{{ number_format(abs((float)$data->difference), 4) }}</td>
                    <td>{{ number_format((float)$data->rebate, 2) }} ({{ number_format((float)$data->rebate_percentage, 2) }}%)</td>
                    <td>{{ number_format((float)$data->premium, 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="3" align="right">Rebate Total: </td>
                <td><b>{{ number_format((float)$analysis->rebate_total, 2) }} ({{ number_format((float)$analysis->rebate_percentage, 2) }}%)</b></td>
                <td><b>{{ number_format((float)$analysis->premium_total, 2) }}</b></td>
            </tr>
            <tr>
                <td colspan="5" align="center"><b>Total rebate of the bill is {{ round($analysis->rebate_total) }} Rs for {{ number_format((float)$analysis->rebate_percentage, 2) }}%</b></td>
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
