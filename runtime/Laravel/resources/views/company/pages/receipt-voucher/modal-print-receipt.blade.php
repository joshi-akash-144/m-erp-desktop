<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt</title>

    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
        }

        table {
            border-collapse: collapse;
        }

        table td,
        table th {
            border: 1px solid #000;
            border-collapse: collapse;
            padding: 2px 8px;
            font-size: 13px;
            width: 50%;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: end;
        }

        .fw-bold {
            font-weight: 600;
        }

        #brackUpTable tr td {
            border: 0;
            border-bottom: 1px solid black;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            /* table tr,
            td {
                font-family: Arial, Helvetica, sans-serif;
                font-size: 17px;
                padding: 0;
            } */
        }
    </style>
</head>

<body>
    <div style="padding-left: 40px;padding-right: 0px">

        <div style="margin-bottom: 5px">
            <div style="margin-bottom: 10px;">
                <h4 style="display: flex;justify-content: center;padding:0;margin:0;">
                    @isset($company->print_name)
                    {{ Str::upper($company->print_name) }}
                    @endisset
                </h4>
                <h6 style="display: flex;justify-content: center; padding:0;margin:0;">
                    @isset($company->print_name)
                    {{ Str::upper($company->address_one)}} ,{{
                    Str::upper($company->address_two) }}
                    @endisset
                </h6>
                <h6 style="display: flex;justify-content: center; padding:0;margin:0;">
                    @isset($company->print_name)
                    {{ Str::upper($company->state->name ?? '') }},{{
                    Str::upper($company->country->name ?? '')
                    }},Ph : {{ Str::upper($company->mobile_number) }}
                    @endisset
                </h6>

            </div>
            <div style="display: flex;justify-content: center">
                <span style="flex:33; font-size:15px" class="fw-bold">Voucher No : {{ $voucher_serial }}</span>
                <span style="flex:33; font-size:15px; text-align: center" class="fw-bold">Receipt Voucher</span>
                <span style="flex:33; font-size:15px; text-align: end" class="fw-bold">Receipt Date :{{ $voucher_date
                    }}</span>
            </div>
        </div>
        <div>
            <table>
                <tbody>
                    <tr>
                        <th style="width: 4%">Sr</th>
                        <th style="width: 56%">Particular</th>
                        <th class="text-end" style="width: 20%">Debit</th>
                        <th class="text-end" style="width: 20%">Credit</th>
                    </tr>
                    @foreach ($receipt_voucher_data as $sr_no => $voucher_data)
                    @if (isset($voucher_data['column1']) && $voucher_data['column1'] == "Sr")
                    @continue
                    @endif

                    @if (isset($voucher_data['column1']) && $voucher_data['column1'] == "Total")
                    <tr>
                        <td colspan="2" class="fw-bold text-end">Total</td>
                        <td style="width: 20%" class="text-end">{{ $voucher_data['column2'] }}</td>
                        <td style="width: 20%;" class="text-end">{{ $voucher_data['column3'] }}</td>
                    </tr>
                    @elseif (isset($voucher_data['column4']) && $voucher_data['column4'] == "Total")
                    <tr>
                        <td colspan="2" class="fw-bold text-end">Total</td>
                        <td style="width: 20%" class="text-end">{{ $voucher_data['column5'] }}</td>
                        <td style="width: 20%;" class="text-end">{{ $voucher_data['column6'] }}</td>
                    </tr>

                    @else
                    <tr>
                        <td style="width: 4%">{{ $sr_no }}</td>
                        <td style="width: 56%;">{{ Str::limit($voucher_data['column2'] ?? '', 55, '...') }}</td>
                        <td class="text-end" style="width: 20%">{{ $voucher_data['column3'] ?? '' }}</td>
                        <td class="text-end" style="width: 20%;">{{ $voucher_data['column4'] ?? '' }}</td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
                @if(!empty($narration))
                <tfoot>
                    <tr>
                        <td colspan="4"><span class="fw-bold">Narration</span> : {!! nl2br(e($narration)) !!}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @if (count($receipt_voucher_breakup) > 0)
        <div style="margin-top: 20px">
            <span class="fw-bold">Account Name : {{ $account_name }}</span>
        </div>
        <div style="margin-top: 10px">
            <table id="brackUpTable">
                <tr style="border-top: 2px solid black;border-bottom: 1px solid black;">
                    <td class="fw-bold" style="width:2%">Sr.</td>
                    <td class="fw-bold" style="width:10%; white-space: nowrap;">Po Num.</td>
                    <td class="fw-bold text-center" style="width:10%; white-space: nowrap;">Ref No.</td>
                    <td class="fw-bold" style="width:20%">Ref Date</td>
                    <td class="fw-bold" style="width:27%">Product</td>
                    <td class="fw-bold" style="width:30%">Destination</td>
                    <td class="fw-bold text-end" style="width:6%">Qty</td>
                    <td class="fw-bold text-end" style="width:10%">Amount</td>
                </tr>
                @foreach ($receipt_voucher_breakup as $sr_no => $breakup_data)
                @if ($breakup_data['column1'] == "Sr.")
                @php
                continue;
                @endphp
                @endif

                <tr>
                    <td style="width:2%" class="text-center">{{ $sr_no ?? '-' }}</td>
                    <td style="width:10%" class="text-center">{{ $breakup_data['column3'] ?? '-' }}</td>
                    <td style="width:10%" class="fw-bold text-center">{{ $breakup_data['column4'] ?? '-' }}</td>
                    <td style="width:5%">{{ ($breakup_data['column5'] ?? '-') }}</td>
                    <td style="width:27%">{{ Str::limit($breakup_data['column8'] ?? '-', 10, '...') }}</td>
                    <td style="width:30%">{{ Str::limit($breakup_data['column9'] ?? '-', 15, '...') }}</td>
                    <td style="width:6%" class="text-end">{{ is_numeric($breakup_data['column10'] ?? null) ? number_format((float) $breakup_data['column10'], 3) : ($breakup_data['column10'] ?? '-') }}</td>
                    <td style="width:10%" class="text-end fw-bold">{{ $breakup_data['column11'] ?? '-' }}</td>
                </tr>
                @endforeach
            </table>
        </div>
        @endif
        {{-- <div style="position: fixed; bottom: 0; width: 100%;margin-top:5px"> --}}
        <div style="display: flex; justify-content: space-between; margin-top:55px">
            <div>Prepared By : {{ Auth::user()->name }}</div>
            <div>Checked By</div>
            <div>Authorization Sign</div>
        </div>
        {{-- </div> --}}
    </div>
</body>

<script>
    addEventListener("afterprint", (event) => {
        window.close();
    });
</script>

</html>

