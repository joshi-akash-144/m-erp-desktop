<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sales Bill</title>
    <style>
        .m-0 {
            margin: 0px;
        }

        .p-0 {
            padding: 0px;
        }
        
        @page {
            size: A4;
            margin: 10mm;
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
        /* @media print {
            img {
                display: block !important;
                visibility: visible !important;
            }
        } */

        table[border] td, table[border] th {
            border-color: black;
        }
    </style>
</head>

<body>
        
   @php
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
    @if($headerEnable)
        <div style="margin-top:200px;">
    @endif
    <div style="padding-left: 40px;padding-right: 0px">
        @if(!$headerEnable)                  
            <div class="border" style="display: flex; justify-content: center;">      
                    <table>
                        <tr>
                            <td style="text-align: center;">{{ Str::upper($salesInvoice->company_name) }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: center;">
                                {{ Str::upper($salesInvoice->address_one ?? '') }}
                                {{ $salesInvoice->address_two ? ', ' . Str::upper($salesInvoice->address_two) : '' }}
                            </td>
                        </tr>

                        <tr>
                            <td style="text-align: center;">
                                {{ Str::upper($salesInvoice->state_name) }}
                                {{ $salesInvoice->postal_code }}
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: center;"> Ph : {{ $salesInvoice->mobile_number }}</td>
                        </tr>
                    </table>    
                </div>
            @endif                    
        <div class="border" style="border-bottom : 0">
            <table style="width: 100%;">
                <tr>
                      <td style="width:33.3%;text-align: left;">Original</td>
                           
                            <td style="width:33.3%; text-align: center;">{{ $salesInvoice->bill_type }}</td>
                                <td style="width:33.3%;text-align: right;"> Memo
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
                                                           
                                <span style="font-weight: bold">{{ $salesInvoice->receiver_name }}</span><br>
                                {{ $salesInvoice->receiver_address_one ? $salesInvoice->receiver_address_one . ',' : '' }}<br>
                                {{ $salesInvoice->receiver_address_two ? $salesInvoice->receiver_address_two . ',' : '' }}<br>
                                
                                
                                
                                {{ $salesInvoice->receiver_city }} - 
                                {{ $salesInvoice->receiver_postal_code }}
                                <br>
                                {{ $salesInvoice->receiver_state_name }} 
                                
                                <br>
                                
                                Mobile : {{ $salesInvoice->receiver_mobile_number }}<br>
                                State & Code : {{ $salesInvoice->receiver_state_name }}, ({{ $salesInvoice->receiver_gst_code }})
                            </td>
                        </tr>
                    </table>
                </div>
                <div style="flex: 30%;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="width: 30%;">GST NO</td>
                            <td>: <span style="font-weight: bold">{{ Str::upper($salesInvoice->receiver_gst_number) }}</span></td>
                        </tr>
                        <tr>
                            <td style="width: 30%;">PAN NO</td>
                            <td>: {{ Str::upper($salesInvoice->receiver_pan_no) }}</td>
                        </tr>
                        <tr>
                            <td style="width: 30%;">E-Way Bill NO</td>
                            <td>: {{ $salesInvoice->eway_bill_number }}</td>
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
                        <td>: {{ $salesInvoice?->invoice_date }}</td>
                    </tr>
                    <tr>
                        <td class="w-50">Vehicle No</td>
                        <td>: {{ $salesInvoice?->vehicle_number }}</td>
                    </tr>
                    <tr>
                        <td class="w-50">P.O.No</td>
                        <td>: {{ $salesInvoice?->purchase_order_number }}</td>
                    </tr>
                    <tr>
                        <td class="w-50">Delivery Date</td>
                        <td>: {{ $salesInvoice?->delivery_date }}</td>
                    </tr>
                    <tr>
                        <td class="w-50">Destination</td>
                        <td>: {{ $salesInvoice->destination_name }}</td>
                    </tr>
                    <tr>
                        <td class="w-50">Broker</td>
                        <td>: {{ $salesInvoice->broker_name }}</td>
                    </tr>
                    <tr>
                        <td class="w-50">Transport</td>
                        <td>: {{ $salesInvoice->transport }}</td>
                    </tr>
                    <tr>
                        <td class="w-50">L.R.No & Date</td>
                        <td>: {{ $salesInvoice->lr_no }} {{ $salesInvoice->lr_date }}</td>
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
                @for ($i = 0; $i < 12; $i++) @if ($i==0) <tr class="tr">
                    <td style="border-radius: 0; border-top : 0; border-bottom: 0; text-align:center">1</td>
                    <td style="border-radius: 0; border-top : 0; border-bottom: 0; text-align :center">{{ $salesInvoice->items[0] ? $salesInvoice->items[0]['hsn_sac_code'] : '' }}</td>
                    <td style="border-radius: 0; border-top : 0; border-bottom: 0;">{{ $salesInvoice->items[0] ? $salesInvoice->items[0]['name'] : '' }}</td>
                    <td style="border-radius: 0; border-top : 0; border-bottom: 0; text-align :center">
                        {{ $salesInvoice->items[0] ? $salesInvoice->items[0]['bag_count'] : '' }}
                    </td>
                    <td style="border-radius: 0; border-top : 0; border-bottom: 0; text-align :center">
                        
                        {{ $salesInvoice->items[0] ? $salesInvoice->items[0]['gross_quantity'] : '' }}</td>
                    <td style="border-radius: 0; border-top : 0; border-bottom: 0; text-align :center">
                        {{ $salesInvoice->items[0] ? $salesInvoice->items[0]['net_quantity'] : '' }}
                    </td>
                    <td style="border-radius: 0; border-top : 0; border-bottom: 0; text-align :center">
                        {{ $salesInvoice->items[0] ? $salesInvoice->items[0]['unit'] : '' }}
                    </td>
                    <td style="border-radius: 0; border-top : 0; border-bottom: 0; text-align :center">
                        {{ $salesInvoice->items[0] ? $salesInvoice->items[0]['rate'] : '' }}
                    </td>
                    <td
                        style="border-radius: 0; border-top: 0; border-bottom: 0; text-align: right; padding-right: 6px;">
                        @php
                            $amount = $salesInvoice->items[0] ? $salesInvoice->items[0]['amount'] : 0
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
                            colspan="2">{{ $salesInvoice->items[0] ? $salesInvoice->items[0]['net_quantity'] : 0 }}</td>
                        <td style="border-top : 1px solid black; text-align: right;border-left:0; padding-right: 6px;"
                            colspan="2">{{ $amount ?? 0 }}</td>
                    </tr>
            </table>
        </div>
        <div class="border" style="display: flex; flex: 80%; border-top: 0;">
            <div style="flex: 62%; height: 130px;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 30%;">Company GSTIN</td>
                        <td>: {{ $salesInvoice?->gst_number }}</td>
                    </tr>
                    <tr>
                        <td style="width: 30%;">Company PAN NO</td>
                        <td>: {{ $salesInvoice?->pan_no }}</td>
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
                $particulars = $salesInvoice->billSundries ?? [];
                $grand_total = $amount;
                $gst = 0
                @endphp
                <table style="width: 100%;">
                    @foreach ($particulars as $particular)
                    <tr>
                        <td>{{ $particular['name'] }}</td>
                        @if($particular['rate_percent'])
                            <td style="text-align:center">{{$particular['rate_percent']}} %</td>
                        @else
                            <td></td>
                        @endif

                        <td style="padding-right : 6px;text-align:right">{{$particular['amount']}}</td>
                        @php
                        $grand_total += $particular['amount'];
                        if($particular['is_gst']){
                            $gst += $particular['amount'];
                        }
                        @endphp
                    </tr>
                    @endforeach
                    
                </table>
            </div>
        </div>
        <div>
            <table width="100%" border="1" style="border-collapse: collapse; width: 100%;">
                <tr>
                    <td style="width: 78%; border-right: 0">Amount in Words : <span style="font-size: 11px">{{ amountInWords($grand_total) }}</span></td>
                    <td style="width: 22%; text-align: right; border-left: 0;">
                        <div style="display: flex;justify-content: space-between ; padding-right : 6px"><span style="font-weight: bold;">Grand Total</span><span>{{ number_format($grand_total, 2, ".","") }}</span></div>
                    </td> 
                </tr>
            </table>
        </div>
        <div>
            <table width="100%" border="1" style="border-collapse: collapse; width: 100%; border-top: 0;">
                <tr>
                    <td>Total GST Payble : <span style="font-size: 11px"> @if($gst) {{ amountInWords($gst) }} @endif </span> </td>
                </tr>
                <tr>
                    <td>Our Bank :</td>
                </tr>
            </table>
        </div>
        <div style="display: flex;">
            <div class="border" style="flex: 60%; display: flex; border-top: 0; height: 170px; padding:3px;">
                <div style="flex: 60%; margin-top: 20px;">
                    <div style="height: 70px;">
                        <table>
                            <tr>
                                <td>
                                    <div style="width:250px">
                                        <span style="">Irn :</span><span style="word-wrap: break-word">{{ $salesInvoice->irn }}</span>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div style="height: 30px;">
                        <table style="width: 100%;">
                            <tr>
                                <td style="width: 30%">Ack. No</td>
                                <td>: {{ isset($salesInvoice->ack_no) && !empty($salesInvoice->ack_no) ? $salesInvoice->ack_no : '' }}
                                </td>
                            </tr>
                            <tr>
                                <td style="width: 30%">Ack. Date</td>
                                <td>:
                                    {{ isset($salesInvoice->ack_dt) && !empty($salesInvoice->ack_dt) ? date('d-m-Y H:i:s', strtotime(str_replace('/', '-', $salesInvoice->ack_dt))) : '' }}
                                </td> 
                            </tr>
                        </table>
                    </div>
                </div>
                <div style="flex: 30%; margin-top: 20px;">
                    <table>
                        <tr>
                                <td rowspan="2" colspan="1" style="border-left: none">
                                    {{ isset($salesInvoice->signed_qr_code) && !empty($salesInvoice->signed_qr_code) ? $salesInvoice->signed_qr_code : '' }}
                                </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="border" style="flex: 40%; border-top: 0; border-left : 0">
                <div style="margin-top: 20px;">
                    <table style="width: 100%;">
                        <tbody>
                            <tr>
                                <td style="text-align: center">For, {{ Str::upper($salesInvoice->company_name) }}</td>
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
    @endforeach

    @if($headerEnable)
</div>
    @endif
</body>
<script>
    addEventListener("afterprint", (event) => {
        window.close();
    });
</script>

</html>
