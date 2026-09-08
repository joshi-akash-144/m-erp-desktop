<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Payment Difference System Report</title>
    <style>
        body {
            font-family: 'inter', sans-serif;
        }
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
        .text-align-start {
            text-align: start;
        }
    </style>
</head>

<body>
    <center>
        <h4 class="p-0 m-0">{{ Str::upper($companyName) }}</h4>
        <p class="p-0 m-0 p-1">{{ $companyAddress }}, {{ $postalCode }}, {{ $mobileNo }}</p>
    </center>
    <hr>
    <center>
        <h4 class="p-0 m-0">Payment Difference Statement</h4>
    </center>
    <p class="p-0 m-0 p-1 text-align-end">Date: {{ $analysis->created_at->format('d-m-Y') }}</p>
    <table width="100%" class="pdetail">
       
        <tbody>
            <tr>
                <td class="w-6">Bill No.</td>
                <td class="w-27">{{ $analysis->purchaseInvoice->invoice_serial ?? '--' }}</td>
                <td class="w-6">Date</td>
                <td class="w-27">{{ format_date($analysis?->purchaseInvoice?->invoice_date) ?? '--' }}</td>
                <td class="w-6">G.R. Qty.</td>
                <td class="w-27">{{ number_format($analysis?->purchaseInvoice?->details?->first()?->quantity ?? 0, 3) }}</td>
            </tr>
            <tr>
                <td class="w-6">Name</td>
                <td class="w-27">{{ $analysis->purchaseInvoice->account->name ?? '--' }}</td>
                <td class="w-6">GRN No.</td>
                <td class="w-27">{{ $analysis->purchaseInvoice->grn_number ?? '--' }}</td>
                <td class="w-6">Rate</td>
                {{-- <td class="w-27">{{ $analysis->purchaseInvoice->details->first()->rate ?? '0.00' }}</td> --}}
               <td class="w-27">
                    {{ $analysis->purchaseInvoice->details->map(fn($d) => number_format($d->inclusive_rate, 2))->implode(', ') }}
                </td>
            </tr>
            <tr>
                <td class="w-6">City</td>
                <td class="w-27">{{ $analysis->purchaseInvoice->account->city ?? '--' }}</td>
                <td class="w-6">Vehicle</td>
                <td class="w-27">{{ $analysis->purchaseInvoice->vehicle_number ?? '--' }}</td>
                <td class="w-6">Amount</td>
                <td class="w-27">
                    {{ formatIndianNumber($analysis->purchaseInvoice->calculated_total) }}
                </td>
            </tr>
            <tr>
                <td class="w-6">Product</td>
                <td class="w-27">{{ $analysis->purchaseInvoice->details->first()->item->name ?? '--' }}</td>
                <td class="w-6">Destination</td>
                <td class="w-27">{{ $analysis->purchaseInvoice->details->first()->destination->name ?? '--' }}</td>
                <td class="w-6">Condition</td>
                <td class="w-27">{{ $analysis->purchaseInvoice->details->first()->condition->name ?? '--' }}</td>
            </tr>
        </tbody>
    </table>
    <table width='100%'>
        <thead>
            <tr>
                <th class="text-align-start">Element</th>
                <th class="text-align-end">Actual</th>
                <th class="text-align-end">Diff</th>
                <th class="text-align-end">Rebate</th>
                <th class="text-align-end">Premium</th>
            </tr>
        </thead>
        <tbody>
            @php
                $purchaseRebatePercentageTotal = 0;
                $purchasePremiumPercentageTotal = 0;
            @endphp
            @foreach ($analysis->details as $data)
                {{-- @dd($data->toArray()); --}}
                @php
                    $purchaseRebatePercentageTotal += $data->purchase_rebate_percentage ?? 0;
                    $purchasePremiumPercentageTotal += $data->purchase_premium_percentage ?? 0;
                @endphp
                <tr>
                    <td>{{ $data->element->name }} ({{ $data->element->range == 1 ? '>=' : '<=' }}
                        {{ $data->guarantee }})</td>
                    <td class="text-align-end">{{ number_format($data->actual,4)  }}</td>
                    <td class="text-align-end">{{ number_format($data->diff,4) }}</td>
                    <td class="text-align-end">
                        {{ format_number($data->purchase_rebate) }}
                        @if($data->purchase_rebate_percentage > 0)
                            ({{ number_format($data->purchase_rebate_percentage, 2) }}%)
                        @endif
                    </td>
                    <td class="text-align-end">
                        {{ $data->purchase_premium }}
                        @if($data->purchase_premium_percentage > 0)
                            ({{ number_format($data->purchase_premium_percentage, 2) }}%)
                        @endif
                    </td>
                </tr>
            @endforeach
            <tr>
                <td colspan="3" align="right" style="font-weight: bold;"> Rebate Total: </td>
                <td class="text-align-end" style="font-weight: bold;">
                    {{ format_number($analysis->purchase_rebate_total) }}
                    @if($purchaseRebatePercentageTotal > 0)
                        ({{ number_format($purchaseRebatePercentageTotal, 2) }}%)
                    @endif
                </td>
                <td class="text-align-end">
                    {{ $analysis->purchase_premium_total }}
                    @if($purchasePremiumPercentageTotal > 0)
                        ({{ number_format($purchasePremiumPercentageTotal, 2) }}%)
                    @endif
                </td>
            </tr>
        </tbody>
    </table>
    <script>
        window.onload = function() {
            window.print();
        };
        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>

</html>
