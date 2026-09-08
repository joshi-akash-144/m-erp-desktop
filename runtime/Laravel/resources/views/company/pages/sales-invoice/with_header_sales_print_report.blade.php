<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sales Bill</title>
    {{-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" /> --}}
    <style>
        .m-0 {
            margin: 0px;
        }

        .p-0 {
            padding: 0px;
        }


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

        .border-top {
            border-top: 0;
        }

        .border-bottom {
            border-bottom: 0;
        }

        .border-left {
            border-left: 0;
        }

        .border-right {
            border-right: 0;
        }

        .border {
            border: 1px solid black;
        }

        .item-table tr td {
            border: 1px solid black;
            font-family: Arial, Helvetica, sans-serif;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: end;
        }

        .p-5 {
            padding: 5px;
        }

        .w-50 {
            width: 50%;
        }

        svg {
            max-width: 125px !important;
            max-height: 125px !important;
        }

        table[border] td, table[border] th {
            border-color: black;
        }
    </style>
</head>

<body>
    @php
    $companyId = session('company_id');

    $signArray = [
        1 => '/front/sales_sign_3.png',
        2 => '/front/sales_sign_3.png',
        3 => '/front/sales_sign.png',
        4 => '/front/vinayak.png',
        5 => '/front/sales_sign.png',
        6 => '/front/sales_sign.png',
        7 => '/front/sales_sign_2.png',
        8 => '/front/bhavana.png',
    ];

    $path = $signArray[$companyId] ?? '/front/sales_sign.png';

    @endphp

    @foreach ($salesInvoices as $key => $salesInvoice)
        <div style="margin-top:200px;">
            <div style="padding-left: 40px;padding-right: 0px">
                {{-- <div class="border" style="display: flex; justify-content: center;">
                <table>
                    <tr>
                        <td style="text-align: center;">{{ Str::upper($data->company_name) }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: center;">{{ Str::upper($data->add1)}} ,{{ Str::upper($data->add2) }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: center;">{{ Str::upper($data->comapny_taluka) }},{{
                            Str::upper($data->dist)
                            }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: center;"> Ph : {{ Str::upper($data->phone) }}</td>
                    </tr>
                </table>
            </div> --}}
                <div class="border" style="border-bottom : 0">
                    <table style="width: 100%;">
                        <tr>
                            <td style="width:33.3%;text-align: left;">Original</td>
                            @if(($salesInvoice->details[0] ?? null)?->cgst_rate > 0 || ($salesInvoice->details[0] ?? null)?->sgst_rate > 0 || ($salesInvoice->details[0] ?? null)?->igst_rate > 0)
                                <td style="width:33.3%; text-align: center;">Tax Invoice</td>
                            @else
                                <td style="width:33.3%; text-align: center;">Bill of Supply</td>
                            @endif
                                <td style="width:33.3%;text-align: right;">{{ Str::title(trim($salesInvoice->payment_received_status)) }} Memo
                            </td>
                        </tr>
                    </table>
                </div>
                <div style="display: flex;" class="border">
                    <div style="flex: 70%; display: flex; flex-direction: column;">
                        <div style="flex: 70%; border-bottom: 1px solid black;">
                            <table style="width: 100%;">
                        <tr>
                            <td colspan="5">Details of Receiver (Bill To)<br> 
                                                           
                                <span style="font-weight: bold">{{ $salesInvoice->account->name }}</span><br>
                                {{ $salesInvoice->account->address_one ? $salesInvoice->account->address_one . ',' : '' }}<br>
                                {{ $salesInvoice->address_two ? $salesInvoice->address_two . ',' : '' }}<br>
                                {{-- {{ $data->district }} {{ $data->state_name ? $data->state_name . ',' : '' }}<br> --}}
                                {{ $salesInvoice->company->state->name }} {{ $salesInvoice->company->country->name ? $salesInvoice->company->country->name . ',' : '' }}<br>
                                {{-- {{ $data->taluka }}{{ $data->pin }}<br> --}}
                                Mobile : {{ optional($salesInvoice->account)->mobile_number }}<br>
                                State & Code : {{ optional($salesInvoice->company->state)->name }}, ({{ optional($salesInvoice->company->state)->gst_code }})
                            </td>
                        </tr>
                    </table>
                        </div>
                        <div style="flex: 30%;">
                            <table style="width: 100%;">
                                <tr>
                                    <td style="width: 30%;">GST NO</td>
                                    <td>: <span style="font-weight: bold">{{ Str::upper($salesInvoice->company?->gst_number) }}</span></td>
                                </tr>
                                <tr>
                                    <td style="width: 30%;">PAN NO</td>
                                    <td>: {{ Str::upper($salesInvoice?->company->pan) }}</td>
                                </tr>
                                <tr>
                                    <td style="width: 30%;">E-Way Bill NO</td>
                                    <td>: {{ $salesInvoice?->eWayBill?->ewb_no }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div style="flex: 30%; border-left: 1px solid black;">
                        <table style="width: 100%;">
                                <tr>
                                    <td class="w-50">Invoice No</td>
                                    <td>: {{ $salesInvoice?->invoice_serial }}</td>
                                </tr>
                                <tr>
                                    <td class="w-50">Date</td>
                                    <td>: {{ format_date($salesInvoice?->invoice_date) }}</td>
                                </tr>
                                <tr>
                                    <td class="w-50">Vehicle No</td>
                                    <td>: {{ $salesInvoice?->vehicle_number }}</td>
                                </tr>
                                <tr>
                                    <td class="w-50">P.O.No</td>
                                    <td>: {{ $salesInvoice->salesOrder?->purchase_order_number }}</td>
                                </tr>
                                <tr>
                                    <td class="w-50">Delivery Date</td>
                                    <td>: {{ format_date($salesInvoice?->delivery_date) }}</td>
                                </tr>
                                <tr>
                                    <td class="w-50">Destination</td>
                                    <td>: {{ ($salesInvoice->details[0] ?? null)?->destination->name }}</td>
                                </tr>
                                <tr>
                                    <td class="w-50">Broker</td>
                                    <td>: Self</td>
                                </tr>
                                <tr>
                                    <td class="w-50">Transport</td>
                                    <td>: </td>
                                </tr>
                                <tr>
                                    <td class="w-50">L.R.No & Date</td>
                                    <td>: </td>
                                </tr>
                                <tr>
                                    <td class="w-50">GRN No</td>
                                    <td>: {{ $salesInvoice?->grn_number }}</td>
                                </tr>
                        </table>
                    </div>
                </div>
                <div>
                    <table width="100%" border="1"
                        style="border-collapse: collapse; width: 100%; font-family: Arial; border-top: 0;">
                        <tr>
                            <td class="text-center">No</td>
                            <td class="text-center">HSN</td>
                            <td class="text-center">Description of Good</td>
                            <td class="text-center">Bags</td>
                            <td class="text-center">G.Weight</td>
                            <td class="text-center">Net.Qty</td>
                            <td class="text-center">Unit</td>
                            <td class="text-center">Rate</td>
                            <td class="text-center">Net Amount</td>
                        </tr>
                        @for ($i = 0; $i < 12; $i++)
                            @if ($i == 0)
                                <tr class="tr">
                                    <td style="border-radius: 0; border-top : 0; border-bottom: 0; text-align:center">1</td>
                                    <td style="border-radius: 0; border-top : 0; border-bottom: 0; text-align :center">{{ ($salesInvoice->details[0] ?? null)?->item->hsn_sac_code }}</td>
                                    <td style="border-radius: 0; border-top : 0; border-bottom: 0;">{{ ($salesInvoice->details[0] ?? null)?->item->name }}</td>
                                    <td style="border-radius: 0; border-top : 0; border-bottom: 0; text-align :center">{{ ($salesInvoice->details[0] ?? null)?->bag_count }}
                                    </td>
                                    <td style="border-radius: 0; border-top : 0; border-bottom: 0; text-align :center">{{ number_format(($salesInvoice->details[0] ?? null)?->item->p_qty, 3, ".","")  }}</td>
                                    <td style="border-radius: 0; border-top : 0; border-bottom: 0; text-align :center">
                                        {{ number_format(($salesInvoice->details[0] ?? null)?->quantity, 3, ".","")  }}
                                    </td>
                                    <td style="border-radius: 0; border-top : 0; border-bottom: 0; text-align :center">
                                        {{ ($salesInvoice->details[0] ?? null)?->item->unit->name}}</td>
                                    <td style="border-radius: 0; border-top : 0; border-bottom: 0; text-align :center">
                                        {{ number_format(($salesInvoice->details[0] ?? null)?->rate, 2, ".","") }}
                                    </td>
                                    <td
                                        style="border-radius: 0; border-top: 0; border-bottom: 0; text-align: right; padding-right: 6px;">
                                        @php
                                            $amount = ($salesInvoice->details[0] ?? null)?->quantity *  ($salesInvoice->details[0] ?? null)?->rate;
                                        @endphp
                                        @php
                                            if(isset($salesInvoice->net_amount) && !empty($salesInvoice->net_amount)){
                                                $amount = $salesInvoice->net_amount;
                                            }else{
                                                $amount = ($salesInvoice->details[0] ?? null)?->quantity * ($salesInvoice->details[0] ?? null)?->rate;;
                                            }
                                        @endphp
                                        <span>{{  number_format($amount, 2 ,".","");  }}</span>
                                    </td>
                                </tr>
                            @else
                                <tr>
                                    <td style="border-radius: 0; border-top : 0; border-bottom: 0;"> &nbsp;</td>
                                    <td style="border-radius: 0; border-top : 0; border-bottom: 0;"> &nbsp;</td>
                                    <td style="border-radius: 0; border-top : 0; border-bottom: 0;"> &nbsp;</td>
                                    <td style="border-radius: 0; border-top : 0; border-bottom: 0;"> &nbsp;</td>
                                    <td style="border-radius: 0; border-top : 0; border-bottom: 0;"> &nbsp;</td>
                                    <td style="border-radius: 0; border-top : 0; border-bottom: 0;"> &nbsp;</td>
                                    <td style="border-radius: 0; border-top : 0; border-bottom: 0;"> &nbsp;</td>
                                    <td style="border-radius: 0; border-top : 0; border-bottom: 0;"> &nbsp;</td>
                                    <td style="border-radius: 0; border-top : 0; border-bottom: 0;"> &nbsp;</td>
                                </tr>
                            @endif
                        @endfor
                        <tr class="p-0 m-0">
                            <td style="border-top : 1px solid black; padding-right: 6px; border-right: 0" colspan="5"
                            align="right">Total</td>
                            <td style="border-top : 1px solid black;border-right:0;border-left:0; padding-left: 10px;"
                                colspan="2">{{ number_format(($salesInvoice->details[0] ?? null)?->quantity, 3 ,".","")  }}</td>
                            <td style="border-top : 1px solid black; text-align: right;border-left:0; padding-right: 6px;"
                                colspan="2">{{ number_format($amount, 2 ,".","") }}</td>
                        </tr>
                    </table>
                </div>
                <div class="border" style="display: flex; flex: 80%; border-top: 0;">
                    <div style="flex: 62%; height: 125px;">
                        <table style="width: 100%;">
                            <tr>
                                <td style="width: 30%;">Company GSTIN</td>
                                <td>: {{ $salesInvoice->company?->gst_number }}</td>
                            </tr>
                            <tr>
                                <td style="width: 30%;">Company PAN NO</td>
                                <td>: {{ $salesInvoice->company?->pan }}</td>
                            </tr>
                            <tr>
                                <td style="width: 30%;">Payment Terms</td>
                                <td>: </td>
                            </tr>
                            <tr>
                                <td style="width: 30%;">Remarks</td>
                                <td>:{{ $salesInvoice?->remarks }}</td>
                            </tr>
                        </table>
                    </div>
                    <div style="flex: 38%;">
                @php
                // $particular = json_decode($data->sales_particular);
                $particular = ($salesInvoice->details[0] ?? null);
                $grand_total = $amount;
                $gst = 0
                @endphp
                <table style="width: 100%;">
                        @if (isset($particular->cgst_rate) && $particular->cgst_rate !== null && $particular->cgst_rate != 0)
                        <tr>
                            <td>CGST</td>
                            <td style="text-align:center">{{$particular->cgst_rate}} %</td>
                            <td style="padding-right : 6px;text-align:right">{{$particular->cgst_amount}}</td>
                            @php
                            $grand_total += $particular->cgst_amount;
                            $gst += $particular->cgst_amount;
                            @endphp
                        </tr>
                        @endif
                        @if (isset($particular->sgst_rate) && $particular->sgst_rate !== null && $particular->sgst_rate != 0)
                        <tr>
                            <td>SGST</td>
                            <td style="text-align:center">{{$particular->sgst_rate}} %</td>
                            <td style="padding-right : 6px;text-align:right">{{$particular->sgst_amount}}</td>
                            @php
                            $grand_total += $particular->sgst_amount;
                            $gst += $particular->sgst_amount;
                            @endphp
                        </tr>
                        @endif
                        @if (isset($particular->igst_rate) && $particular->igst_rate !== null && $particular->igst_rate != 0)
                        <tr>
                            <td>IGST</td>
                            <td style="text-align:center">{{$particular->igst_rate}} %</td>
                            <td style="padding-right : 6px;text-align:right">{{$particular->igst_amount}}</td>
                            @php
                            $grand_total += $particular->igst_amount;
                            $gst += $particular->igst_amount;
                            @endphp
                        </tr>
                        @endif
                        {{-- @if (isset($particular->labour) && $particular->labour !== null && $particular->labour != 0)
                        <tr>
                            <td>Labour</td>
                            <td style="text-align:center"></td>
                            <td style="padding-right : 6px;text-align:right">{{$particular->labour}}</td>
                            @php
                            $grand_total += $particular->labour;
                            @endphp
                        </tr>
                        @endif
                        @if (isset($particular->freight) && $particular->freight !== null && $particular->freight != 0)
                        <tr>
                            <td>Freight</td>
                            <td style="text-align:center"></td>
                            <td style="padding-right : 6px;text-align:right">{{$particular->freight}}</td>
                            @php
                            $grand_total += $particular->freight;
                            @endphp
                        </tr>
                        @endif
                        @if (isset($particular->rebate) && $particular->rebate !== null && $particular->rebate != 0)
                        <tr>
                            <td>Rebate</td>
                            <td style="text-align:center"></td>
                            <td style="padding-right : 6px; text-align:right">{{$particular->rebate}}</td>
                            @php
                            $grand_total += $particular->rebate;
                            @endphp
                        </tr>
                        @endif --}}
                    </table>
                </div>
                </div>
                <div>
                    <table width="100%" border="1" style="border-collapse: collapse; width: 100%;">
                        <tr>
                             <td style="width: 78%; border-right: 0">Amount in Words : 
                                <span style="font-size: 11px">{{ amountInWords($grand_total) }}
                            </span></td>
                            <td style="width: 22%; text-align: right; border-left: 0;">
                                <div style="display: flex;justify-content: space-between ; padding-right : 6px">
                                    <span style="font-weight: bold;">Grand Total</span>
                                <span>{{ number_format($grand_total, 2, ".","") }}</span></div>
                            </td> 
                        </tr>
                    </table>
                </div>
                <div>
                    <table width="100%" border="1"
                        style="border-collapse: collapse; width: 100%; border-top: 0;">
                        <tr>
                            <td>Total GST Payble : 
                                <span style="font-size: 11px"> @if($gst) {{ amountInWords($gst) }} @endif </span> 
                            </td>
                        </tr>
                        <tr>
                            <td>Our Bank :</td>
                        </tr>
                    </table>
                </div>
                <div style="display: flex;">
                    <div class="border" style="flex: 60%; display: flex; border-top: 0; height: 170px; padding:5px;">
                        <div style="flex: 60%; margin-top: 20px;">
                            <div style="height: 70px;">
                                <table>
                                    <tr>
                                        <td>
                                            <div style="width:250px">
                                                 <span style="">Irn :</span><span style="word-wrap: break-word">{{ $salesInvoice->eInvoice?->irn }}</span>
                                            </div>
                                        </td>
                                         {{-- <td style="">Irn :</td>
                                            <td style="width:250px;"><span style="word-wrap: break-word">{{ $salesInvoice->ewaybill?->irn }}</span>
                                        </td> --}}
                                    </tr>
                                </table>
                            </div>
                            <div style="height: 30px;">
                                <table style="width: 100%;">
                                     <tr>
                                        <td style="width: 30%">Ack. No</td>
                                        <td>: {{ isset($salesInvoice->eInvoice?->ack_no) && !empty($salesInvoice->eInvoice?->ack_no) ? $salesInvoice->eInvoice?->ack_no : '' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="width: 30%">Ack. Date</td>
                                        <td>:
                                            {{ isset($salesInvoice->eInvoice?->ack_dt) && !empty($salesInvoice->eInvoice?->ack_dt) ? date('d-m-Y H:i:s', strtotime(str_replace('/', '-', $salesInvoice->eInvoice?->ack_dt))) : '' }}
                                        </td> 
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div style="flex: 30%; margin-top: 20px;">
                            <table>
                                <tr>
                                    <td rowspan="2" colspan="1" style="border-left: none">
                                        {{ isset($salesInvoice->qr_code) && !empty($salesInvoice->qr_code) ? $salesInvoice->qr_code : '' }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="border" style="flex: 40%; border-top: 0; border-left : 0">
                        <div style="margin-top: 25px;">
                            <table style="width: 100%;">
                                <tbody>
                                    <tr>
                                       <td style="text-align: center">For, {{ Str::upper($salesInvoice->company->name) }}</td>
                                    </tr>
                                    <tr height="40px">
                                        <td>
                                            <div style="display: flex;justify-content: center">
                                                <img src="{{ asset($path) }}" width="100px" height="100px" alt="sign">
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: center">Authorized Signatory</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
            <div class="conditional-page-break"></div>
        </div>
    @endforeach
</body>
<script>
    addEventListener("afterprint", (event) => {
        window.close();
    });
</script>

</html>
