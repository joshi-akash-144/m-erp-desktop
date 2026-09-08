<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Freight Invoice</title>

    <style>
        @media print {
            body {
                margin: 0;
            }

            @page {
                size: portrait;
            }
        }

        table tr td {
            padding: 0;
            margin: 0;
            font-size: 13px;
        }

        .conditional-page-break {
            page-break-after: always;
        }

        .border {
            border: 1px solid black;
        }

        .text-center {
            text-align: center;
        }

        .w-50 {
            width: 50%;
        }
    </style>
</head>

<body>
    @php
        $items       = $freight->items ?? collect();
        $bagsTotal   = 0;
        $nos         = 0;
        $totalAmount = (float) $freight->total_amount;
        $round       = $totalAmount - round($totalAmount);
        $invoiceDate = $freight->invoice_date ? $freight->invoice_date->format('d-m-Y') : '';
        $fromDate    = $freight->from_date    ? $freight->from_date->format('d-m-Y')    : '';
        $toDate      = $freight->to_date      ? $freight->to_date->format('d-m-Y')      : '';
        $chunks      = $items->chunk(14);
    @endphp

    @foreach($chunks as $chunkIndex => $chunkItems)
    <div style="margin-top:200px;">
        <div style="padding-left: 40px;padding-right: 0px">
            <div>
                <table style="width: 100%;">
                    <tr>
                        <td style="width:33.3%;">Tax Invoice</td>
                        <td style="width:33.3%;text-align: right;">Original</td>
                    </tr>
                </table>
            </div>
            <div style="display: flex; border: 1px solid black; border-bottom: 0;">
                <div style="flex: 70%; padding: 0 5px;">
                    <table style="width: 100%;">
                        <tr>
                            <td>Consigner</td>
                        </tr>
                        <tr>
                            <td><b>{{ $freight->account?->name ?? '' }}</b></td>
                        </tr>
                        @if(!empty($freight->account?->address_one))
                        <tr>
                            <td>{{ $freight->account->address_one }}</td>
                        </tr>
                        @endif
                        @if(!empty($freight->account?->address_two))
                        <tr>
                            <td>{{ $freight->account->address_two }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td>{{ $freight->account?->city ?? '' }}{{ $freight->account?->postal_code ? ' - ' . $freight->account->postal_code : '' }}</td>
                        </tr>
                        <tr>
                            <td>GSTIN: {{ $freight->account?->taxDetail?->gst_number ?? '' }}</td>
                            </tr>
                    </table>
                </div>
                <div class="border-left"
                    style="flex: 30%; display: flex; flex-direction: column; border-left: 1px solid black;">
                    <div style="padding: 0 5px;border-bottom:1px solid black;">
                        <table style="width: 100%;">
                            <tr>
                                <td>Mode of Payment</td>
                                <td><b>Debit Memo</b></td>
                            </tr>
                        </table>
                    </div>
                    <div style="padding: 0 4px;">
                        <table style="width: 100%;">
                            <tr>
                                <td colspan="2">&nbsp;</td>
                            </tr>
                            <tr>
                                <td style="white-space: nowrap;">Invoice No.</td>
                                <td><b>{{ $freight->invoice_serial }}</b></td>
                            </tr>
                            <tr>
                                <td>Dated</td>
                                <td>{{ $invoiceDate }}</td>
                            </tr>
                            <tr>
                                <td>Order No</td>
                                <td></td>
                            </tr>                          
                            <tr>
                                <td>Dated</td>
                                <td></td>
                            </tr>
                    
                            <tr>
                                <td colspan="2">&nbsp;</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div>
                <table width="100%" border="1"
                    style="border-collapse: collapse; width: 100%; font-family: Arial; border-color: black !important;">
                    <thead>
                        <tr>
                            <td class="text-center" style="padding: 2px;">No.</td>
                            <td class="text-center" style="padding: 2px;">Description of Goods Transported</td>
                            <td class="text-center" style="padding: 2px;">Zone</td>
                            <td class="text-center" style="padding: 2px;">Bags <small>(NOS)</small></td>
                            <td class="text-center" style="padding: 2px;">Carting Rate</td>
                            <td class="text-center" style="padding: 2px;">Amount (₹)</td>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($chunkItems as $val)
                            @php
                                $nos++;
                                $qty    = (float) $val->quantity;
                                $rate   = (float) $val->rate;
                                $amount = $qty * $rate;
                                $bagsTotal += $qty;
                            @endphp
                            <tr>
                                <td
                                    style="padding: 2px; border-radius: 0; border-top : 0; border-bottom: 0; text-align:end">
                                    {{ $nos }}</td>
                                <td style="padding: 2px; border-radius: 0; border-top : 0; border-bottom: 0;">
                                    <b>{{ $val->item->name ?? '' }}</b>
                                </td>
                                <td
                                    style="padding: 2px; border-radius: 0; border-top : 0; border-bottom: 0; text-align:end">
                                    {{ $val->zone->name ?? '' }}</td>
                                <td
                                    style="padding: 2px; border-radius: 0; border-top : 0; border-bottom: 0; text-align:end">
                                    {{ number_format($qty, 2, '.', '') }}</td>
                                <td
                                    style="padding: 2px; border-radius: 0; border-top : 0; border-bottom: 0; text-align:end">
                                    {{ number_format($rate, 2, '.', '') }}</td>
                                <td
                                    style="padding: 2px; border-radius: 0; border-top : 0; border-bottom: 0; text-align:end">
                                    {{ number_format($amount, 2, '.', '') }}</td>
                            </tr>
                        @endforeach
                        @if ($chunkItems->count() < 17)
                            @for ($i = 1; $i < 17 - $chunkItems->count(); $i++)
                                <tr>
                                    <td
                                        style="padding: 2px; border-radius: 0; border-top : 0; border-bottom: 0; text-align:end">
                                        &nbsp;</td>
                                    <td
                                        style="padding: 2px; border-radius: 0; border-top : 0; border-bottom: 0;">
                                        &nbsp;
                                    </td>
                                    <td
                                        style="padding: 2px; border-radius: 0; border-top : 0; border-bottom: 0; text-align:end">
                                        &nbsp;
                                    </td>
                                    <td
                                        style="padding: 2px; border-radius: 0; border-top : 0; border-bottom: 0; text-align:end">
                                        &nbsp;
                                    </td>
                                    <td
                                        style="padding: 2px; border-radius: 0; border-top : 0; border-bottom: 0;">
                                        &nbsp;
                                    </td>
                                    <td
                                        style="padding: 2px; border-radius: 0; border-top : 0; border-bottom: 0; text-align:end">
                                        &nbsp;
                                    </td>
                                </tr>
                            @endfor
                        @endif
                        <tr>
                            <td colspan="3"
                                style="padding: 1px 1px 1px 5px; border-radius: 0; border-right : 0;">
                                <span>Local Sales</span><span style="float: right">Total:</span>
                            </td>
                            <td style="padding: 2px; border-radius: 0; border-left : 0; border-right : 0;"
                                align="right"><b>{{ number_format($bagsTotal, 2, '.', '') }}</b></td>
                            <td style="padding: 2px; border-radius: 0; border-left : 0; border-right : 0;"
                                colspan="1"></td>
                            <td style="padding: 2px; border-radius: 0; border-left : 0;" align="right">
                                <b>{{ formatIndianNumber($totalAmount, 2) }}</b>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div style="display: flex;border-top: 0;" class="border">
                <div style="flex: 70%; display: flex; flex-direction: column;">
                    <div style="padding: 0 5px; border-bottom: 1px solid black;">
                        <table style="width: 100%;">
                            <tr>
                                <td>Remark : {{ $freight->remarks }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="2">&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan="2">&nbsp;</td>
                            </tr>
                        </table>
                    </div>
                    <div style="padding: 0 5px;">
                        <table style="width: 100%;">
                            <tr>
                                <td>Company GST No. :{{ $company->gst_no ?? '' }}</td>
                            </tr>
                            <tr>
                                <td>PAN of Transporter:&nbsp;<b>{{ $company->pan_no ?? '' }}</b></td>
                                <td></td>
                            </tr>
                        </table>
                    </div>
                    <div style="padding: 0 5px;border-top: 1px solid black;">
                        <table style="width: 100%;">
                            <tr>
                                <td>Rs. {{ amountInWords(round($totalAmount), 2) }} Only</td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div style="flex: 30%; display: flex; flex-direction: column; border-left: 1px solid black;">
                    <div style="padding:0 2px 5px 10px">
                        <table style="width: 100%;">
                            <tr>
                                <td colspan="2">&nbsp;</td>
                            </tr>
                            <tr>
                                <td>Diesel</td>
                                <td align="right"></td>
                            </tr>
                            <tr>
                                <td>Freight</td>
                                <td align="right"></td>
                            </tr>
                            <tr>
                                <td>Round</td>
                                <td align="right">
                                    {{ number_format($round, 2) }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan="2">&nbsp;</td>
                            </tr>
                        </table>
                    </div>
                    <div style="padding: 0 2px 0 10px;border-top: 1px solid black;">
                        <table style="width: 100%;">
                            <tr>
                                <td>Grand Total</td>
                                <td align="end">
                                    <b>{{ formatIndianNumber(round($totalAmount), 2) }}</b>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div style="padding: 0 5px; border-top: 0;" class="border">
                <table style="width: 100%">
                    <tr>
                        <td>Our Bank:</td>
                    </tr>
                </table>
            </div>
            <div style="display: flex; border-top: 0;" class="border">
                <div style="flex: 45%; padding: 0 5px;">
                    <table style="width: 100%">
                        <tr>
                            <td>Note: The GST on this transport service is payable by the recipient under reverse charge (RCM). No GST has been charged by the supplier.</td>
                        </tr>
                    </table>
                </div>
                <div style="border-left: 1px solid black;flex: 20%; padding: 0 5px;">
                    <table style="width: 100%">
                        <tr>
                            <td>&nbsp;</td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                        </tr>
                        <tr>
                            <td align="center">Recieved Signature</td>
                        </tr>
                    </table>
                </div>
                <div style="border-left: 1px solid black;flex: 35%; padding: 0 5px;">
                    <table style="width: 100%">
                        <tr>
                            <td align="center">For, <b>{{ $companyName }}</b></td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                        </tr>
                        <tr>
                            <td align="center">Authorized Signature</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="conditional-page-break"></div>
    @endforeach
</body>

<script>
    addEventListener("afterprint", (event) => {
        window.close();
    });
    window.print();
</script>

</html>
