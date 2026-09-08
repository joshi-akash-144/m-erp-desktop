<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Godown Ticket</title>
       <style>
        / Your CSS styles here / body {
            font-family: Arial, sans-serif;
        }

        p {
            line-height: 10px;
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
            / border-right: none;/ / border-left: none;/
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
            font-weight: 700 !important
        }

        .conditional-page-break {
            page-break-after: always;
        }

        @media print {
            body {
                margin: 0;
                font-size: 10pt;
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
                        <div style="height:15px; width:100%; clear:both;"></div>
                        <center>
                            <div class="container border border-radius">
                                <h3 class="fw-bold" style="margin-top: 15px;">{{ Str::upper($data['companyName']) }}</h3>
                                <h5 style="margin: -15px 0 10px 0;">
                                    {{ Str::upper($data['companyAddress']) }}
                                    <br>
                                    Ph. @if(!empty($data['companyPhone'])) {{ $data['companyPhone'] }} @endif
                                </h5>
                            </div>
                        </center>

                        <center>
                            <h2 style="margin: 1px !important;">TICKET</h2>
                        </center>
            <div class="container border border-radius">

                <div class="d-flex" style="border-bottom: 1px solid;">
                    <p style="width: 50%;padding-left: 12px;">Print Date
                        <span class="fw-bold" style="padding: 0px 0px 0px 80px;">:&nbsp;{{ date('d/m/Y') }}</span>
                    </p>
                    <p style="width: 50%;">Time
                        <span class="fw-bold">: {{ date('H:i:s') }}</span>
                    </p>
                </div>

                <p style="padding-left: 12px;">Transaction No
                    <span class="fw-bold" style="padding: 0px 0px 0px 53px;">:&nbsp;{{ $record->grn_serial ?? '-' }}</span>
                </p>
                <p style="padding-left: 12px;">Truck
                    <span class="fw-bold" style="padding: 0px 0px 0px 104px;">:&nbsp;{{ $record->vehicle_number ?? '-' }}</span>
                </p>
                <p style="padding-left: 12px;">Product Name
                    <span class="fw-bold" style="padding: 0px 0px 0px 58px;">:&nbsp;{{ $record->item->name ?? 'N/A' }}</span>
                </p>
                <p style="padding-left: 12px;">Party Name

                    {{-- @if ($record->in_out_status == 'in')
                        @if ($record->account_id != 0) --}}
                    <span class="fw-bold" style="padding: 0px 0px 0px 71px;">:&nbsp;{{ $record->account->name ?? 'N/A'}}</span>
                    {{-- @endif
                    @else
                        @if ($record->in_out_status == 'out')
                            @if ($record->account_id != 0 && $record->account_name != '' && $record->account_name != 'N/A')
                                <span class="fw-bold" style="padding: 0px 0px 0px 71px;">:&nbsp;{{ $record->account_name }}</span>
                            @else
                                @if (
                                        $record->account_id != 0 &&
                                        $record->account_name != '' &&
                                        $record->account_name != 'N/A'
                                    )
                                    <span class="fw-bold" style="padding: 0px 0px 0px 71px;">:&nbsp;{{ $record->account_name }}</span>
                                @endif
                            @endif
                        @endif
                    @endif --}}

                </p>
                <p style="padding-left: 12px;">Transport Name
                    <span class="fw-bold" style="padding: 0px 0px 0px 48px;">:&nbsp;SELF</span>
                </p>

                <div class="d-flex">
                    <p style="width: 50%;padding-left: 12px;margin: 0px;" style="">Bags
                        <span class="fw-bold"
                            style="padding: 0px 0px 0px 108px;">:&nbsp;{{number_format($record->bag_count ?? 0, 0)}}</span>
                    </p>
                    <p style="width: 50%;margin: 0px;padding: 0 0 0 210px;">
                        @if($record->in_out_status == 'in')
                            Challan No
                            <span class="fw-bold" style="padding: 0 0 0 30px;">:&nbsp;{{ $record->reference_number ?? '-' }}</span>
                        @else
                            Challan No
                            <span class="fw-bold" style="padding: 0 0 0 30px;">:&nbsp;{{ $record->grn_serial ?? '-' }}</span>
                        @endif
                    </p>
                </div>

                <div class="d-flex">
                    <p style="width: 50%;padding-left: 12px;">Delivery Note Number
                        <span class="fw-bold" style="padding: 0px 0px 0px 11px;">:&nbsp;</span>
                    </p>
                    <p style="width: 50%;padding: 0 0 0 210px;">Challan Date
                        <span class="fw-bold" style="padding: 0 0 0 21px;">:&nbsp;
                            @if($record->in_out_status == 'in')
                                {{ !empty($record->challan_date) ? \Carbon\Carbon::parse($record->challan_date)->format('d/m/Y') : '-' }}
                            @else
                                {{ !empty($record->grn_date) ? \Carbon\Carbon::parse($record->grn_date)->format('d/m/Y') : '-' }}
                            @endif
                        </span>
                    </p>
                </div>

                <div class="d-flex">
                    <p style="width: 65%;padding-left: 12px;margin: 0px;">Godown
                        <span class="fw-bold"
                            style="padding: 0px 0px 0px 89px;">:&nbsp;{{ $record->godownUnit->godown_name ?? 'N/A' }}</span>
                    </p>
                    <p style="width: 35%;margin: 0px;padding: 0 0 0 0px;">Challan Weight
                        <span class="fw-bold"
                            style="padding: 0 0 0 8px;">:&nbsp;{{number_format($record->challan_weight ?? 0, 3, '.', '') }}</span>
                    </p>
                </div>
                <p style="padding-left: 12px;">Remark
                    <span class="fw-bold" style="padding: 0 0 0 92px;">:&nbsp;{{ $record->grn->remarks ?? '' }}</span>
                </p>


                <div class="d-flex">
                    <p style="width: 33.33%;padding-left: 12px;margin: 0px;">Date In
                        <span class="fw-bold" style="padding: 0px 0px 0px 95px;">:&nbsp;
                            {{ !empty($record->date_in) && $record->date_in !== '-' ? str_replace('-', '/', $record->date_in) : '-' }}</span>
                    </p>
                    <p style="width: 33.33%;margin: 0px;">Time In
                        <span class="fw-bold"
                            style="padding: 0 0 0 12px;">:&nbsp;{{ !empty($record->time_in) ? $record->time_in : '-' }}</span>
                    </p>

                    <p style="width: 33.33%;margin: 0px;">Gross Weight
                        <span class="fw-bold"
                            style="padding: 0px 0px 0px 24px;">:&nbsp;{{ !empty($record->gross_weight) ? number_format($record->gross_weight, 0, '.', '') : '-' }}</span>
                    </p>
                </div>

                <div class="d-flex">
                    <p style="width: 33.33%;padding-left: 12px;" style="">Date Out
                        <span class="fw-bold"
                            style="padding: 0px 0px 0px 86px;">:&nbsp;{{!empty($record->date_out) && $record->date_out !== '-' ? str_replace('-', '/', $record->date_out) : '-'}}</span>
                    </p>
                    <p style="width: 33.33%;">Time Out
                        <span class="fw-bold"
                            style="padding: 0px 0px 0px 3px;">:&nbsp;{{  !empty($record->time_out) ? $record->time_out : '-' }}</span>
                    </p>

                    <p style="width: 33.33%;">Tare Weight
                        <span class="fw-bold"
                            style="padding: 0 0 0 16px;">:&nbsp;{{!empty($record->tare_weight) ? number_format($record->tare_weight, 0, '.', '') : '-' }}</span>
                    </p>
                </div>

                <div class="d-flex" style="border-bottom: 1px solid;">
                    <p style="width: 50%;padding-left: 12px;margin-top:0px; margin-bottom:10px">Net Wt(Without Bags)
                        <span class="fw-bold"
                            style="padding: 0px 0px 0px 13px;">:&nbsp;{{  !empty($record->net_weight_wt_bag) ? number_format($record->net_weight_wt_bag, 0, '.', '') : '-' }}</span>
                    </p>
                    <p style="width: 50%;margin: 0px;margin-bottom:10px;padding: 0 0 0 234px;">Net Weight
                        <span class="fw-bold"
                            style="padding: 0 0 0 28px;">:&nbsp;{{  !empty($record->net_weight) ? number_format($record->net_weight, 0, '.', '') : '-' }}</span>
                    </p>
                </div>

                <p style="padding-left: 12px;" class="fw-bold">
                    {{ Str::upper($record->in_out_status == 'in' ? 'Product IN' : 'Product OUT') }}
                </p>

                <div class="d-flex" style="margin-top: -22px;">
                    <p style="width: 80%;padding-left: 12px;">User
                        <span class="fw-bold">:&nbsp;{{ $record->updater->name ?? $record->creator->name ?? 'N/A' }}</span>
                    </p>
                    <p style="width: 20%;">Way Bridge Incharge</p>

                </div>
            </div>
            @if (!$loop->last)
                <div class="conditional-page-break"></div>
            @endif
        @endforeach
    @endif

    <script>
        // window.print();
        window.addEventListener("afterprint", (event) => {
            window.close();
        });
    </script>
</body>

</html>
