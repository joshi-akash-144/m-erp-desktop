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
                size: landscape;
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
        @php
            $items = $item->contractorItems ?? collect();
            $itemsCount = $items->count();
            $chunks = collect();
            
            if ($itemsCount <= 11) {
                $chunks->push($items);
            } else {
                $chunks->push($items->slice(0, 11));
                $remaining = $items->slice(11);
                while ($remaining->count() > 0) {
                    $chunks->push($remaining->slice(0, 10));
                    $remaining = $remaining->slice(10);
                }
            }

            $runningBags = 0;
            $runningAmount = 0;
        @endphp

        @foreach($chunks as $chunkIndex => $chunkItems)
        <div style="padding-left: 10px; padding-right: 10px; padding-top: 10px;">
            
            @if ($chunks->count() > 1 && $chunkIndex > 0)
                <div style="text-align: right; font-weight: bold; margin-bottom: 1px; font-size: 12px;">
                    Page {{ $chunkIndex + 1 }} of {{ $chunks->count() }}
                </div>
            @endif

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
                    <td style="width: 100%; padding: 5px; vertical-align: top;">
                        <div style="text-align: start;">
                            <span class="bold">Bill To:</span> {{ Str::upper($item->account->name ?? '') }}
                        </div>
                        <div style="text-align: start; padding-top: 10px;">
                            <span class="bold"> GST NO:</span> {{ $item->account->taxDetail->gst_number ?? '' }}
                        </div>
                    </td>
                </tr>
            </table>

            {{-- MAIN TABLE --}}
            <table>
                <thead>
                    <tr>
                        @foreach($tableConfig['columns'] as $col)
                            <th class="{{ $col['class'] }}" style="width: {{ $col['width'] }};">{{ $col['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @if ($chunkIndex > 0)
                    <tr>
                        <td colspan="3" class="bold text-right">B/F</td>
                        <td class="bold text-right">{{ number_format($runningBags, 2) }}</td>
                        <td colspan="3"></td>
                        <td class="bold text-right">{{ formatIndianNumber($runningAmount, 2) }}</td>
                        <td></td>
                    </tr>
                    @endif

                    @foreach($chunkItems as $index => $freightItem)
                    @php
                        $runningBags += (float) $freightItem->bag_count;
                        $runningAmount += (float) $freightItem->amount;
                    @endphp
                    <tr>
                        <td class="text-center">{{ format_date($freightItem->date ?? '') }}</td>
                        <td title="{{ $freightItem->destination->name ?? '' }}">{{ Str::limit($freightItem->destination->name ?? '', 30) }}</td>
                        <td>{{ $freightItem->route ?? '' }}</td>
                        <td class="text-right">{{ number_format($freightItem->bag_count ?? 0, 2) }}</td>
                        <td>{{ $freightItem->vehicle->name ?? '' }}</td>
                        <td class="text-right">{{ number_format($freightItem->kms ?? 0, 2) }}</td>
                        <td class="text-right">{{ formatIndianNumber($freightItem->rate ?? 0, 2) }}</td>
                        <td class="text-right">{{ formatIndianNumber($freightItem->amount ?? 0, 2) }}</td>
                        <td title="{{ $freightItem->contractor->name ?? '' }}">{{ Str::limit($freightItem->contractor->name ?? '', 30) }}</td>
                    </tr>
                    @endforeach

                    @php
                        $targetRows = ($chunkIndex == 0) ? 11 : 10;
                    @endphp
                    @if ($chunkItems->count() < $targetRows)
                        @for ($i = 0; $i < $targetRows - $chunkItems->count(); $i++)
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                        @endfor
                    @endif

                    <tr>
                        <td colspan="3" class="bold text-right">{{ $loop->last ? 'Total' : 'C/F' }}</td>
                        <td class="bold text-right">{{ number_format($runningBags, 2) }}</td>
                        <td colspan="3"></td>
                        <td class="bold text-right">{{ formatIndianNumber($runningAmount, 2) }}</td>
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
                @if(!$loop->last)
                    <div class="bold text-right" style="margin-top: 2px; font-style: italic; font-family:monospace; font-size:10px;">Continued to Page {{ $loop->iteration + 1 }} ...</div>
                @endif

        </div>

        <div class="conditional-page-break"></div>

        @endforeach
    @endforeach

</body>

<script>
    addEventListener("afterprint", () => {
        window.close();
    });
</script>

</html>