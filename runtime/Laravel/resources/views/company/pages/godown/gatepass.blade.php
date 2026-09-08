<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Pass</title>
    {{-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css"> --}}
    {{-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css"> --}}
    <style>
        /* Your CSS styles here */
        body {
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        p {
            line-height: 10px;
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
            padding: 10px;
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
            <h4 style="margin-top: 15px;">{{ Str::upper($data['companyName']) }}</h4>
            <h5 style="margin: -15px 0 10px 0;">
                {{ Str::upper($data['companyAddress']) }}
                <br>
                Ph.@if(!empty($data['companyPhone'])) {{ $data['companyPhone'] }} @endif
            </h5>
        </div>
    </center>

    <center>
        <h4 style="margin: 9px !important;">GATE PASS</h4>
    </center>

    <div class="container border border-radius">
        <div class="d-flex" style="border-bottom: 1px solid;">
            <p style="width: 50%;padding-left: 15px;">Print Date
                <span class="fw-bold" style="padding: 0px 0px 0px 40px;">:&nbsp;{{ date('d/m/Y') }}</span>
            </p>
            <p style="width: 50%;">Time
                <span class="fw-bold">: {{ date('H:i:s') }}</span>
            </p>
        </div>

        <div class="d-flex">
            <p style="width: 50%;padding-left: 15px;">No
                <span class="fw-bold" style="padding: 0px 0px 0px 90px;">:&nbsp;{{ $record->grn_serial ?? $record->dc_serial }}</span>
            </p>
            <p style="width: 50%; text-align: center;">Date:
                <span class="fw-bold">{{ !empty($record->dc_date) ? \Carbon\Carbon::parse($record->dc_date)->format('d/m/Y') : (!empty($record->grn_date) ? \Carbon\Carbon::parse($record->grn_date)->format('d/m/Y') : (!empty($record->date_in) && $record->date_in !== '-' ? \Carbon\Carbon::parse($record->date_in)->format('d/m/Y') : '-')) }}</span>
            </p>
        </div>
        <p style="padding-left: 15px; margin: 0px !important;">Party Name
            <span class="fw-bold" style="padding: 0px 0px 0px 27px;">:&nbsp;{{ $record->account->name ?? 'N/A' }}&nbsp;{{ $record->in_out_status == 'in' ? ($record->destination->name ?? 'N/A') : ($record->partyDestination->name ?? 'N/A') }}</span>
            {{-- <span class="fw-bold" style="padding: 0px 0px 0px 27px;">:&nbsp;{{ $go->supplier_name }}</span> --}}
        </p>
        <div class="row">
            <div class="col-lg-12">
                <table>
                    <thead>
                        <tr>
                            <th style="text-align: left;">Sr. No</th>
                            <th style="text-align: left;">Product Name</th>
                            <th>Bags</th>
                            <th>Weight</th>
                            <th>Truck No</th>
                        </tr>
                    </thead>
                    <tbody>
                        <td style="text-align: left">1</td>
                        <td style="text-align: left">{{ $record->item->name }}</td>
                        <td style="text-align: center">{{ $record->bag_count }}</td>
                        <td style="text-align: center">{{ !empty($record->net_weight) ? number_format($record->net_weight, 0, '.', '') : '-' }} kg</td>
                        <td style="text-align: center">{{ Str::upper($record->vehicle_number) }}</td>
                    </tbody>
                </table>
            </div>
        </div>

        <p style="padding-left: 15px;">Remark
            <span class="fw-bold" style="padding-left: 55px;">:&nbsp;{{ !empty($record->in_out_status) && $record->in_out_status == 'out' ? 'OUT' : 'IN' }}&nbsp;{{ !empty($record->remark) ? $record->remark : ''  }}</span>
        </p>
        <p style="padding-left: 15px;">User
            <span class="fw-bold" style="padding-left: 78px;">:&nbsp;{{ $record->updater->name ?? $record->creator->name ?? 'N/A' }}</span>
        </p>
        {{-- <p style="padding-left: 15px;" class="fw-bold">Product {{ $data['inout_status'] == 1 ? 'IN' : 'OUT' }}</p> --}}

        <div class="d-flex" style="margin-top: 44px;">
            <p style="width: 33.33%;padding-left: 15px;">Gate Keeper</p>
            <p style="width: 33.33%;text-align: center;">Driver Signature</p>
            <p style="width: 33.33%;text-align: end;padding-right: 15px;">Store Incharge</p>

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
