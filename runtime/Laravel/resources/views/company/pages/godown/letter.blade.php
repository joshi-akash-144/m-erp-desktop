<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Godown Letter</title>
    <style>
        /* Your CSS styles here */

        body {
            font-family: 'Gujarati', sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table,
        th,
        td {
            border: 1px solid black;
            /* border-right: none; */
            /* border-left: none; */
        }

        th,
        td {
            padding: 16px;
            font-size: 16px;
        }

        .d-flex {
            display: flex;
            justify-content: center;
        }

        .p-1 {
            font-size: 13px;
            letter-spacing: 1px;
        }

        .border-radius {
            border: 1px solid black;
            border-radius: 10px !important;
        }

        .fw-bold {
            font-weight: 500 !important
        }

        .conditional-page-break {
            page-break-after: always;
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
    @if (!empty($data['data']))
    @foreach ($data['data'] as $record)
    @php
        // Dynamic Mapping for Godown vs Delivery Challan
        $serialNum = $record->grn_serial ?? $record->challan_serial ?? '-';
        
        // Date Logic
        $primaryDate = $record->date_out ?? $record->date_in ?? $record->challan_date ?? $record->grn_date ?? null;
        $formattedDate = $primaryDate ? (is_string($primaryDate) ? \Carbon\Carbon::parse($primaryDate)->format('d/m/Y') : $primaryDate->format('d/m/Y')) : '-';

        // Account Name Logic
        if (isset($record->in_out_status)) {
            $displayName = ($record->in_out_status == 'in') 
                ? ($record->account->name ?? '-') 
                : (!empty($record->account_name) && $record->account_name != 'N/A' ? $record->account_name : ($record->account->name ?? '-'));
        } else {
            $displayName = $record->account->name ?? '-';
        }

        // Destination/Village
        if (isset($record->in_out_status) && $record->in_out_status === 'out') {
            $destination = $record->partyDestination->name ?? $record->details?->first()?->partyDestination->name ?? '-';
        } else {
        $destination = $record->destination->name ?? $record->details?->first()?->destination->name ?? '-';
        }
        
        // Item Name
        $itemName = $record->item->name ?? $record->details?->first()?->item->name ?? '-';
        
        // Weights & Bags
        $bags = $record->bag_count ?? 0;
        $weight = $record->challan_weight ?? $record->total_quantity ?? $record->net_weight ?? 0;
        $PoNumber = $record->dairy_po ?? '-';
        // Reference Info
        $refNumber = $record->challan_number ?? $record->grn_number ?? '-';
        $refDate = $record->grn_date ?? $record->challan_date ?? null;
        $formattedRefDate = $refDate ? (is_string($refDate) ? \Carbon\Carbon::parse($refDate)->format('d/m/Y') : $refDate->format('d/m/Y')) : '-';
    @endphp
    <div style="padding-left: 35px;">
        <div style="height:15px; width:100%; clear:both;"></div>
        <center>
            <div class="container border border-radius">
                <h3 class="fw-bold" style="margin-top: 15px;">{{ Str::upper($data['companyName']) }}</h3>
                <h5 style="margin: -15px 0 10px 0;">
                    {{ Str::upper($data['companyAddress']) }}
                    <br>
                    Ph. @if(!empty($data['companyPhone'])) Ph: {{ $data['companyPhone'] }} @endif
                </h5>
            </div>
        </center>

        <div class="container">

            <div class="d-flex" style="margin-top: 20px;">
                <p style="width: 50%;padding-left: 15px;">Sr No
                    <span class="fw-bold" style="padding: 0px 0px 0px 15px;">:&nbsp;{{ $serialNum }}</span>
                </p>
                <p style="width: 50%;text-align:end;padding-right:15px;">Date
                    <span class="fw-bold" style="padding: 0px 0px 0px 15px;">:&nbsp;{{ $formattedDate }}</span>
                </p>
            </div>


            <div style="margin-top: 20px;padding-left: 15px;letter-spacing: 1px;">
                <div class="col-lg-12">
                    <span class="col-lg-2">શ્રીમાન શેઠશ્રી&nbsp;&nbsp;&nbsp;&nbsp;
                        <span class="col-lg-10 border fw-bold" style="border-bottom: 1px inset;">
                            {{ $displayName }}
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        </span>
                    </span>
                </div>

                <p style="text-align: right; ">ગામ&nbsp;:&nbsp;&nbsp;&nbsp;<span class="fw-bold"
                        style="border-bottom: 1px inset; font-size: 20px;">{{ $destination }}
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                </p>

                <p style=" padding-left:200px; margin-top: 50px;">આજ રોજ મોકલાવેલ ગાડી નંબર
                    &nbsp;&nbsp;<span style="border-bottom: 1px inset; padding-left: 15px;">{{ $record->vehicle_number ?? '-' }}
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                    માં </p>

                <p style="margin-top: 25px;">
                    દલાલ <span style="border-bottom: 1px inset; padding-left: 15px;">
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                    ના
                    સોદા મુજબ માલ ની જાત <span
                        style="border-bottom: 1px inset; padding-left: 15px;">{{ $itemName }}
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                </p>

                <p style="">
                    બોરી
                    <span style="border-bottom: 1px inset; padding-left: 15px;">{{ number_format($bags, 0) }}
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                    વજન
                    <span style="border-bottom: 1px inset; padding-left: 15px;">{{ number_format($weight, 3, '.', '') }}
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                    ભરાવીને મોકલાવેલ છે. આ માલ
                </p>

                <p style="margin-top: 25px;">
                    આપના Po. No.
                    <span
                        style="border-bottom: 1px inset; padding-left: 15px;">{{ $PoNumber }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                    Date <span
                        style="border-bottom: 1px inset; padding-left: 15px;">{{ $formattedRefDate }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                    મુજબ મોકલાવેલ છે.
                </p>

                <p style="margin-top: 25px;"> તો સંભાળી માલ મળ્યાની પહોંચ આપશોજી.</p>


            </div>

            <div class="d-flex" style="margin-top: 70px;padding-left: 15px; ">
                <p style="width: 50%">માલ લેનાર ની સહી</p>
                <p class="fw-bold" style="width: 50%; text-align:right;">{{ Str::upper($data['companyName']) }}
                    <br>
                    Himmatnagar
                </p>

            </div>

        </div>
    </div>
    @if (!$loop->last)
        <div class="conditional-page-break"></div>
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
