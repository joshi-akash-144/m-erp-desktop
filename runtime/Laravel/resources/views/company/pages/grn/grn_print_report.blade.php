<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GRN</title>
    <style>
        .text-end {
            text-align: right !important;
        }

        .text-end {
            text-align: right !important;
        }

        .goods-reciept {
            border: 2px solid;
            border-radius: 30px;
            padding: 10px;
            font-size: 18px;
            font-weight: bold;
        }
      
        h6 {
            padding: 0;
            margin: 5px;
            font-size: 14px;
        }

        p {
            padding: 0;
            margin: 0;
        }

        .w-50 {
            width: 290px;
        }

        .w-200 {
            width: 200px;
        }
       
        @media print {
            body {
                margin: 0;
            }

            @page {
                size: portrait;
            }
        }
    </style>
</head>

<body>
    @php
        if (isset($grnData)) {
            // From GRN Module
            $records = collect([$grnData]);
            $compName = $companyName ?? '';
        } else {
            // From Godown Module
            $records = $data['data'] ?? collect();
            $compName = $data['companyName'] ?? '';
        }
    @endphp

    @if (!empty($records) && count($records) > 0)
        @foreach ($records as $record)
            @php
                $isGrn = $record instanceof \App\Models\Grn;
                
                // Serial / Ref No
                $serialNo = $isGrn ? $record->grn_serial : ($record->grn_serial ?? $record->dc_serial ?? '-');
                
                // Date
                $dateVal = $isGrn ? format_date($record->grn_date) : (!empty($record->grn_date) ? format_date($record->grn_date) : (!empty($record->dc_date) ? format_date($record->dc_date) : (!empty($record->date_in) ? format_date($record->date_in) : '-')));
                
                // Party Name & City
                $partyName = $record->account->name ?? 'N/A';
                $partyCity = Str::upper($record->account->city ?? 'N/A');
                
                // In Date & Out Date
                $inDateVal = $isGrn ? format_date($record->grn_in_date) : (!empty($record->date_in) ? format_date($record->date_in) : '-');
                $outDateVal = $isGrn ? format_date($record->grn_out_date) : (!empty($record->date_out) ? format_date($record->date_out) : '-');
                
                // Vehicle & Bags
                $vehicleNo = Str::upper($record->vehicle_number ?? 'N/A');
                $bagCount = $record->bag_count ?? 0;
                
                // Weights
                $grossWeightVal = $record->gross_weight ?? 0;
                $tareWeightVal = $record->tare_weight ?? 0;
                $netWeightVal = $record->net_weight ?? 0;
                $netWeightWtBagVal = $record->net_weight_wt_bag ?? 0;
                
                // Remarks
                $remarksVal = $record->remarks ?? '';
                
                // Items collection
                $itemsList = $isGrn ? ($record->details ?? collect()) : collect([$record]);
            @endphp

            <div style="height:230px; width:100%; clear:both;"></div>
            <center>
                <div><span class="goods-reciept">Goods Receipt</span></div>
                <div style="width: 90%">
                    <div style="margin-top: 60px">
                        <table style="width: 100%">
                            <tr>
                                <td>
                                    <h6>
                                        Ref. No : {{ $serialNo }}
                                    </h6>
                                </td>
                                <td style="text-align: right;">
                                    <h6>
                                        Date : {{ $dateVal }}                                
                                    </h6>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div>
                        <table style="width: 100%;">
                            <tr>
                                <td><br>
                                    <h6>
                                        <span style="font-weight: bold;">M/s :</span> {{ $partyName }}<br>
                                        <span style="font-weight: bold">Place :</span> {{ $partyCity }}
                                    </h6>
                                </td>
                                <td style="text-align: right;">
                                    <h6>
                                        <span style="font-weight: bold">In Date :</span> {{ $inDateVal }}<br>
                                        <span style="font-weight: bold">Out Date :</span> {{ $outDateVal }}
                                    </h6>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div>
                        <table style="width: 100%;">
                            <tr>
                                <td><br>
                                    <h6><span style="font-weight: bold">Dear Sir,</span></h6>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div>
                        <table style="width: 100%;">
                            <tr>
                                <td>
                                    @foreach ($itemsList as $detail)                                                           
                                    <h6 style="text-indent: 40px;font-size-20px">Today We Received
                                        {{ rtrim(rtrim($netWeightVal, '0'), '.') }} Kgs.
                                        {{ Str::upper($detail->item->name ?? 'N/A') }}
                                    </h6>
                                    @endforeach
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>
                                    <h6 style="text-indent: 40px;font-size-20px">Through your Vehicle No.
                                        {{ $vehicleNo }} in
                                        {{ $bagCount }} Bags. From You.
                                    </h6>
                                </td>
                                <td></td>
                            </tr>
                        </table>
                    </div>
                    <div style="margin-top: 20px;">
                        <table style="width: 100%;">
                            <tr>
                                <td>
                                    <h6 style="text-indent: 40px;font-size-20px">Freight is Paid by You so We have not paid any
                                        Freight.
                                    </h6>
                                </td>
                                <td></td>
                            </tr>
                        </table>
                    </div>
                    <div style="margin-top:20px; display: flex;">
                        <table style="width: 50%;">
                            <tr>
                                <td class="w-50" style="font-weight: bold;">
                                    <h6>Gross Weight </h6>
                                </td>
                                <td class="w-200" style="font-weight: bold;"> : {{ rtrim(rtrim($grossWeightVal, '0'), '.') }} Kgs</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td class="w-50" style="font-weight: bold;">
                                    <h6>Tare Weight </h6>
                                </td>
                                <td class="w-200" style="font-weight: bold;"> : {{ rtrim(rtrim($tareWeightVal, '0'), '.') }} Kgs</td>
                            </tr>
                            <tr>
                                <td class="w-50" style="font-weight: bold;">
                                    <h6>Net Weight </h6>
                                </td>
                                <td class="w-200" style="font-weight: bold;"> : {{ rtrim(rtrim($netWeightVal, '0'), '.') }} Kgs</td>
                            </tr>
                            <tr>
                                <td class="w-50" style="font-weight: bold;">
                                    <h6>Net Weight </h6>
                                    <h6 class="p-0 m-0">(Without Bag Kgs)</h6>
                                </td>
                                <td class="w-200" style="font-weight: bold;"> : {{ rtrim(rtrim($netWeightWtBagVal, '0'), '.') }}</td>
                            </tr>
                        </table>
                    </div>
                    <div style="margin-top: 30px; display: flex; justify-content: space-between;">
                        <table style="width: 40%;">
                            <tr>
                                <td>
                                    <h6>User : {{ auth()->user()->name }}</h6>
                                    <h6 style="margin-top:10px;">Remarks : {{ $remarksVal }}</h6>
                                </td>
                            </tr>
                        </table>
                        <table style="width: 60%;">
                            <tr>
                                <td style="text-align: right;">
                            <h6>For {{ Str::upper($compName) }}</h6>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align: center;">
                                    <h6>Authorized Sign</h6>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </center>
            @if (!$loop->last)
                <div style="page-break-after: always;"></div>
            @endif
        @endforeach
    @endif
</body>
<script>
    // window.print();
    addEventListener("afterprint", (event) => {
        window.close();
    });
</script>

</html>