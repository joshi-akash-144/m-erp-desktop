<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Sales Invoice</title>

    <style>
        @media print {
            body {
                margin: 0;
            }

            @page {
                size: portrait;
            }
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid black;
        }

        th, td {
            padding: 4px;
        }

        .conditional-page-break {
            page-break-after: always;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }
    </style>
</head>

<body>

    @foreach ($freight_data as $item)

        <div style="padding-left: 20px; padding-right: 20px; padding-top: 20px;">

            {{-- FORMAT / LAYOUT & COMPANY DETAILS --}}
            <div style="border: 1px solid black; border-bottom: none; padding: 5px;">
                <div class="bold" style="font-size: 14px; margin-top: 2px;">{{ Str::upper($company_data->name ?? '') }}</div>
                <div style="margin-top: 2px;">
                    {{ Str::upper($company_data->address_one ?? '') }}
                    @if(!empty($company_data->address_two))
                        , {{ Str::upper($company_data->address_two ?? '') }}
                    @endif
                    @if(!empty($company_data->city))
                        , {{ Str::upper($company_data->city ?? '') }}
                    @endif
                    @if(!empty($company_data->state))
                        , {{ Str::upper($company_data->state->name ?? '') }}
                    @endif
                </div>
                <div style="margin-top: 2px;">GSTIN: {{ $company_data->gst_number ?? '' }}</div>
                <div style="margin-top: 2px;">PAN: {{ $company_data->pan ?? '' }}</div>
            </div>

            {{-- HEADER --}}
            <div style="border: 1px solid black; border-bottom: none; text-align: center; padding: 5px;" class="bold">
                TAX INVOICE / FREIGHT INVOICE
            </div>

            {{-- INVOICE DETAILS --}}
            <div style="border: 1px solid black; border-bottom: none; padding: 5px;">
                <div><span class="bold">Invoice No: </span> <span>{{ $item->prefix && $item->reference_number ? $item->prefix . $item->reference_number : $item->reference_number }}</span></div>
                <div style="margin-top: 2px;"><span class="bold">Invoice Date: </span> <span>{{ format_date($item->invoice_date ?? '') }}</span></div>
                <div style="margin-top: 2px;"><span class="bold">Place of Supply: </span> <span>{{ Str::upper($company_data->state->name ?? '') }}</span></div>
            </div>

            {{-- CONSIGNOR / CONSIGNEE --}}
            <table style="width: 100%; border-collapse: collapse; margin: 0; padding: 0;">
                <tr>
                    <td style="width: 50%; padding: 5px; vertical-align: top;">
                        <div style="text-align: start;">
                            <span class="bold">Consignor:</span> {{ Str::upper($item->consignor->name ?? '') }}
                        </div>
                        <div style="text-align: start; padding-top: 10px;">
                            <span class="bold"> GST NO:</span> {{ $item->consignor->gst_number ?? '' }}
                        </div>
                    </td>
                    <td style="width: 50%; padding: 5px; vertical-align: top;">
                        <div style="text-align: start;">
                            <span class="bold">Consignee:</span> {{ Str::upper($item->consignee->name ?? '') }}
                        </div>
                        <div style="text-align: start; padding-top: 10px;">
                            <span class="bold"> GST NO:</span> {{ $item->consignee->gst_number ?? '' }}
                        </div>
                        <div style="text-align: start; padding-top: 10px; border-top: 1px solid black;">
                            <span class="bold"> PLACE OF DELIVERY:</span>  {{ Str::upper($item->toDestination->name ?? '') }}
                        </div>
                    </td>
                </tr>
            </table>

            {{-- MAIN TABLE --}}
            <table>
                <thead>
                    <tr>
                        <th class="text-center">S. No.</th>
                        <th class="text-center">Description of Goods Transported</th>
                        <th class="text-center">VEHICLE NO.</th>
                        <th class="text-center">LR NO.</th>
                        <th class="text-center">BAGS</th>
                        <th class="text-center">NET WEIGHT</th>
                        <th class="text-center">FREIGHT RATE</th>
                        <th class="text-center">Amount</th>
                        <th class="text-center">SAC Code</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($item->items as $index => $freightItem)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $freightItem->item->name ?? '' }}</td>
                        <td>{{ $item->vehicle->name ?? '' }}</td>
                        <td>{{ $item->lr_number ?? '' }}</td>
                        <td class="text-right">{{ number_format($freightItem->bag_count ?? 0, 0) }}</td>
                        <td class="text-right">{{ formatIndianNumber($freightItem->net_weight ?? 0, 2) }}</td>
                        <td class="text-right">{{ formatIndianNumber($freightItem->rate ?? 0, 2) }}</td>
                        <td class="text-right">{{ formatIndianNumber($freightItem->amount ?? 0, 2) }}</td>
                        <td class="text-center">9965</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td></td>
                        <td class="bold">Total Taxable Value</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="bold text-right">{{ formatIndianNumber($item->total_amount ?? 0, 2) }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>

            {{-- FOOTER --}}
            <div style="border: 1px solid black; border-top: none; padding: 5px;">
                <div style="margin-top: 2px;"><span class="bold"> Total Invoice Amount (in words):</span> {{ amountInWords($item->total_amount ?? 0) }}</div>
                @if(!empty($item->remarks))
                <div style="margin-top: 2px;"><span class="bold">Narration: </span>{{ $item->remarks }}</div>
                @endif
              <br>
                <div class="bold">Declaration:</div>
                <div style="font-style: italic; margin-top: 2px;">
                    <span class="text-declaration-underline">Note </span>: The GST on this transport service is payable by the consignor under reverse charge (RCM). No GST has been charged by the supplier."
                </div>
                <div class="bold text-right" style="margin-top: 2px;">For {{ $company_data->name ?? '' }}</div>
                <br><br>
            </div>

        </div>

        <div class="conditional-page-break"></div>

    @endforeach

</body>

<script>
    addEventListener("afterprint", () => {
        window.close();
    });
</script>

</html>