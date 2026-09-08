<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Payment Advice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .double {
            border-top: 4px double rgb(66, 65, 65);
        }

        .solid {
            border-top: 2px solid rgb(66, 65, 65);
            border-bottom: 1px solid rgb(66, 65, 65);

        }

        th,
        td {
            padding: 0px;
            text-align: left;
            font-size: 13px;
        }

        .d-flex {
            display: flex;
            justify-content: center;
        }

        .text-end {
            text-align: end;
            / margin-right: 100px;/
        }

        .text-center {
            text-align: center;
            / margin-right: 100px;/
        }

        .text-start {
            text-align: start;
        }

        .p-O {
            padding: 0;
        }

        .m-0 {
            margin: 0;
        }

        .p-1 {
            font-size: 13px;
        }

        .item-table tr td {
            font-size: 12px;
        }

        .item-table-head tr td {
            font-size: 12px;
            font-weight: bold;
            padding: 3px;
        }

        .bold {
            font-weight: 600;
        }

        .radius {
            border: 1px solid black;
            border-radius: 7px;
            padding: 3px;
        }

        .conditional-page-break {
            page-break-after: always;
        }

        .item-table td {
            border-bottom: 1px solid black !important;
            border-top: 1px solid black !important;
        }

        .item-table td {
            font-size: 12px;
            padding: 2px;
        }

        .tfoot td {
            font-size: 12px;
            padding: 2px;
        }

        table {
            width: 100%;
            table-layout: auto;
        }

        .f-12 {
            font-size: 12px;
        }

        .w-8 {
            width: 8%;
        }

        .w-9 {
            width: 9%;
        }

        .p-2 {
            padding: 2px;
        }

        .payment-advice tr td {
            padding: 2px !important;
            padding-bottom: 0 !important;
        }

        .fw-bold {
            font-weight: 600
        }

        .text-right {
            text-align: end;
        }
    </style>
</head>

<body>
    @if (count($data) > 0)
    @php
    $i = 1;
    @endphp
    @foreach ($data['voucher'] as $voucherId => $voucher)
    @php
    $companyData = $data['companyData'];
    $accountDetail = (object) $voucher['accountDetail'];
    $voucherNumber = $voucher['accountDetail']['voucher_number'];
    $voucherData = $voucher['rows'];
    $voucherParticular = isset($voucher['particular']) && $voucher['particular'] ? $voucher['particular'] : null;
    @endphp
    <center>
        <h4 class="p-0 m-0">{{ Str::upper($companyData['company_name']) }}</h4>
        <p class="p-0 m-0 p-1">
            @isset($companyData['companyDetail']['address1'])
            {{ $companyData['companyDetail']['address1'] }} ,
            @endisset
            @isset($companyData['companyDetail']['address2'])
            {{ $companyData['companyDetail']['address2'] }},<br>
            @endisset
            {{-- @isset($companyData['companyDetail']['taluka'])
            {{ $companyData['companyDetail']['taluka'] }} -
            @endisset
            @isset($companyData['companyDetail']['pincode'])
            {{ $companyData['companyDetail']['pincode'] }}
            @isset($companyData['companyDetail']['district'])
            ({{ $companyData['companyDetail']['district'] }})
            @endisset,<br>
            @endisset --}}
            @isset($companyData['companyDetail']['email'])
            Email : {{ $companyData['companyDetail']['email'] }}
            @endisset
        </p>

    </center>
    <table>
        <tr>
            <td colspan="1">No: <strong>{{ $voucherNumber }}</strong></td>
            <td colspan="10"></td>
            <td colspan="1" class="text-end">Page:{{ $i }}</td>
        </tr>
        <tr>
            <td colspan="8"></td>

            <td>
                {{-- <center> --}}
                <h2 class="p-0 m-0" style="text-align: center">Payment Advice</h2>
                {{-- </center> --}}
            </td>
        </tr>
        <tr>
            <td class="p-0 m-0"><strong>To,</strong></td>
        </tr>

    </table>
    {{-- Two-panel layout using a table so dompdf and browsers both render it side by side --}}
    {{-- Both inner tables use height:100% so they always match the taller column --}}
    <table style="width:100%; border-collapse:collapse; margin-top:4px;">
        <tr>
            {{-- Left panel: Supplier info --}}
            <td style="width:49%; vertical-align:top; padding:0;">
                <table style="width:100%; border:1px solid #000; table-layout:fixed; height:200px;" class="p-0 m-0 payment-advice">
                    <tr>
                        <td style="width:26%; font-weight:bold; vertical-align:top; padding:2px;">Supplier</td>
                        <td style="width:2%;  vertical-align:top; padding:2px;">:</td>
                        <td style="width:72%; vertical-align:top; font-weight:bold; font-family:Arial,Helvetica,sans-serif; font-size:10pt; padding:2px; word-wrap:break-word;">
                            {{ $accountDetail->account_name }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; vertical-align:top; padding:2px;">PAN</td>
                        <td style="vertical-align:top; padding:2px;">:</td>
                        <td style="vertical-align:top; padding:2px;">{{ $accountDetail->pan ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; vertical-align:top; padding:2px;">City</td>
                        <td style="vertical-align:top; padding:2px;">:</td>
                        <td style="vertical-align:top; padding:2px;">{{ $accountDetail->city ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; vertical-align:top; padding:2px;">Contact No</td>
                        <td style="vertical-align:top; padding:2px;">:</td>
                        <td style="vertical-align:top; padding:2px;">{{ $accountDetail->contact ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; vertical-align:top; padding:2px;">GST No</td>
                        <td style="vertical-align:top; padding:2px;">:</td>
                        <td style="vertical-align:top; padding:2px;">{{ $accountDetail->gst_no ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; vertical-align:top; padding:2px;">Email Id</td>
                        <td style="vertical-align:top; padding:2px;">:</td>
                        <td style="vertical-align:top; padding:2px; word-wrap:break-word;">{{ $accountDetail->email ?? 'N/A' }}</td>
                    </tr>
                </table>
            </td>

            {{-- Gap column --}}
            <td style="width:2%;"></td>

            {{-- Right panel: Payment info --}}
            <td style="width:49%; vertical-align:top; padding:0;">
                <table style="width:100%; border:1px solid #000; table-layout:fixed; height:200px;" class="p-0 m-0 payment-advice">
                    <tr>
                        <td style="width:30%; font-weight:bold; vertical-align:top; padding:2px;">Date</td>
                        <td style="width:2%;  vertical-align:top; padding:2px;">:</td>
                        <td style="width:68%; vertical-align:top; padding:2px;">
                            {{ isset($accountDetail->payment_date) ? format_date($accountDetail->payment_date) : 'N/A' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; vertical-align:top; padding:2px;">Drawn on</td>
                        <td style="vertical-align:top; padding:2px;">:</td>
                        <td style="vertical-align:top; padding:2px;">{{ $accountDetail->drawn_bank_name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; vertical-align:top; padding:2px;">Amount</td>
                        <td style="vertical-align:top; padding:2px;">:</td>
                        <td style="vertical-align:top; padding:2px;"><strong>{{ $accountDetail->cheque_amount }}</strong></td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; vertical-align:top; padding:2px;">Amount in Words</td>
                        <td style="vertical-align:top; padding:2px;">:</td>
                        <td style="vertical-align:top; padding:2px;">
                            {{ isset($accountDetail->cheque_amount) ? amountInWords($accountDetail->cheque_amount) : 'N/A' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; vertical-align:top; padding:2px;">Chq no.</td>
                        <td style="vertical-align:top; padding:2px;">:</td>
                        <td style="vertical-align:top; padding:2px;">{{ $accountDetail->cheque_no ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; vertical-align:top; padding:2px;">Bank</td>
                        <td style="vertical-align:top; padding:2px;">:</td>
                        <td style="vertical-align:top; padding:2px;">{{ $accountDetail->bank ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; vertical-align:top; padding:2px;">Bank A/c</td>
                        <td style="vertical-align:top; padding:2px;">:</td>
                        <td style="vertical-align:top; padding:2px;">{{ $accountDetail->bank_acc_no ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; vertical-align:top; padding:2px;">IFSC Code</td>
                        <td style="vertical-align:top; padding:2px;">:</td>
                        <td style="vertical-align:top; padding:2px;">{{ $accountDetail->bank_IFSCcode ?? 'N/A' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <div>
        {{-- <span>Bill-Brk-Up</span> --}}
        <table style="width: 100%;">
            <thead class="item-table-head">
                <tr>
                    <td class=" w-8 solid text-start">Date</td>
                    <td class=" w-8 solid text-center" style="text-wrap: nowrap;">Bill No</td>
                    <td class=" w-8 solid text-center">Amount</td>
                    <td class=" w-8 solid ">Qty(Party)</td>
                    <td class=" w-8 solid ">Qty(Rec.)</td>
                    <td class=" w-8 solid text-center">Rate</td>
                    <td class=" w-8 solid text-end">Freight</td>
                    <td class=" w-8 solid text-end">C.D.</td>
                    <td class=" w-8 solid text-end">GST</td>
                    <td class=" w-8 solid text-end">TDS</td>
                    <td class=" w-8 solid text-end">Premium</td>
                    <td class=" w-8 solid text-end">Rebate</td>
                    <td class=" w-8 solid text-end">Penalty</td>
                </tr>
            </thead>
            <tbody>
                @php
                // $item = (object) $item;
                // $total = 0;
                $amount = 0;
                $rebate = 0;
                $tds = 0;
                $premium = 0;
                $total = 0;
                $cd = 0;
                $freight = 0;
                $unique_bill = [];
                $grossTotal = 0;
                $advance = 0;
                @endphp
                {{-- @if (count($voucher_transaction_data) > 0) --}}


                @foreach ($voucherData as $item)
                @php
                $date = format_date($item['date'], 'd/m/Y');
                $amount = (float)$item['amount'];
                $rebate += (float)$item['rebate'] ?? 0;
                $tds += (float)$item['tds'] ?? 0;
                $premium += (float)$item['premium'] ?? 0;
                $cd += (float)$item['cd'] ?? 0;
                $freight += (float)$item['freight'] ?? 0;
                $unique_bill = [];

                if ($item['type'] == 'advance' || $item['type'] == 'on_account') {
                //advance calculation
                $advance += $amount;
                } else {
                $total += $amount;
                }
                @endphp
                <tr class="item-table">
                    <td class="text-start">{{ isset($date) ? $date : '-' }}</td>
                    <td
                        class="text-center @if (($item['type'] == 'advance') || ($item['type'] == 'on_account')) fw-bold @endif">
                        {{ $item['bill_no'] }}
                    </td>

                    <td
                        class="text-end @if (($item['type'] == 'advance') || ($item['type'] == 'on_account')) fw-bold @endif">
                        @if (($item['type'] == 'advance') || $item['type'] == 'on_account')
                        {{ isset($item['amount']) ? -number_format(abs((float)$item['amount']), 2, '.', '') : '0.00' }}
                        @else
                        {{ isset($item['amount']) ? number_format($item['amount'], 2, '.', '') : '0.00' }}
                        @endif
                    </td>
                    <td class="text-end">{{ $item['p_qty'] }}</td>
                    <td class="text-end">{{ $item['qty'] }}</td>
                    <td class="text-end">{{ $item['rate'] }}</td>
                    <td class="text-end">{{ $item['freight'] }}</td>
                    <td class="text-end">{{ $item['cd'] }}</td>
                    <td class="text-end">{{ $item['gst'] }}</td>
                    <td class="text-end">{{ $item['tds'] }}</td>
                    <td class="text-end">{{ $item['premium'] }}</td>
                    <td class="text-end">{{ $item['rebate'] ?? '0.00' }}</td>
                    <td class="text-end">
                        {{ isset($item['penalty']) ? number_format(abs((float)$item['penalty']), 2, '.', '') : '0.00' }}
                    </td>
                </tr>
                @endforeach
                <tr class="tfoot">
                    {{-- <td>ONAC</td> --}}
                    <td colspan="2" style="font-size: 14px;"><strong>Total</strong></td>
                    <td class="text-end">
                        {{ number_format($total, 2, '.', '') }}
                    </td>
                    {{-- <td class="text-start"></td> --}}
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end">{{ number_format($freight, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($cd, 2, '.', '') }}</td>
                    <td class="text-end"></td>
                    <td class="text-end">{{ number_format($tds, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($premium, 2, '.', '') }}</td>
                    <td class="text-end">{{ number_format($rebate, 2, '.', '') }}</td>
                </tr>
                @if ($advance > 0)
                <tr class="tfoot">
                    {{-- <td>ONAC</td> --}}
                    <td colspan="2" style="font-size: 14px;"><strong>Advance</strong></td>
                    <td class="text-end">
                        {{ number_format($advance, 2, '.', '') }}
                    </td>
                    {{-- <td class="text-start"></td> --}}
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                </tr>
                @endif
                @if ($advance > 0)
                <tr class="tfoot">
                    {{-- <td>ONAC</td> --}}
                    <td colspan="2" style="font-size: 14px;"><strong>Net Payable</strong></td>
                    <td class="text-end">
                        @php
                        $grossTotal = $total - $advance;
                        @endphp
                        @if($grossTotal > 0)
                        {{ number_format($grossTotal, 2, '.', '') }}
                        @else
                        {{"0.00"}}
                        @endif
                    </td>
                    {{-- <td class="text-start"></td> --}}
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                </tr>
                @endif
                </tr>
                @if($voucherParticular)
                <tr>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="7" class="fw-bold"
                        style="border-bottom: 1px solid black;border-top: 2px solid black;padding: 3px">
                        <div style="display: flex;justify-content: space-between">
                            <span>Particular</span>
                            <span>Amount</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="7" style="padding: 3px">
                        <div
                            style="display: flex;justify-content: space-between;margin-top: 2px;border-bottom: 1px solid black">
                            <span>{{$voucherParticular['particular_name']}}</span>
                            <span>{{$voucherParticular['amount']}}</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="7">
                        <div style="display: flex;justify-content: end;margin-top: 2px">
                            <span style="margin-right: 40px">Total</span>
                            <span>{{$voucherParticular['amount']}}</span>
                        </div>
                    </td>
                </tr>
                @endif

                @php
                $i++;
                @endphp
                {{-- @if ($grossTotal < 0)
                    <tr>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                    </tr>
                    @if ($advance > 0)
                        <tr class="tfoot">
                            
                            <td colspan="2" style="font-size: 14px;"><strong>Advance Paid</strong></td>
                            <td class="text-end">
                                {{ number_format($advance, 2, '.', '') }}
                </td>

                <td class="text-end"></td>
                <td class="text-end"></td>
                <td class="text-end"></td>
                <td class="text-end"></td>
                <td class="text-end"></td>
                <td class="text-end"></td>
                <td class="text-end"></td>
                <td class="text-end"></td>
                <td class="text-end"></td>
                </tr>
                @endif
                <tr class="tfoot">

                    <td colspan="2" style="font-size: 14px;"><strong>Total Bill Amt.</strong></td>
                    <td class="text-end">
                        {{ number_format($total, 2, '.', '') }}
                    </td>

                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                </tr>
                <tr>
                    <td style="border-top: 1px solid rgb(82, 78, 78)"></td>
                    <td style="border-top: 1px solid rgb(82, 78, 78)"></td>
                    <td style="border-top: 1px solid rgb(82, 78, 78)"></td>
                </tr>
                <tr class="tfoot">

                    <td colspan="2" style="font-size: 14px;"><strong>Differance Amt.</strong></td>
                    <td class="text-end">
                        @php
                        $differance = $advance - $total;
                        @endphp
                        {{ number_format($differance, 2, '.', '') }}
                    </td>

                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                    <td class="text-end"></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="12"> <strong>Note:</strong> Please return payment of
                        <strong>{{ number_format(abs($grossTotal), 2, '.', '') }}</strong>. If already returned, please
                        ignore this message.
                    </td>
                </tr>
                @endif --}}

                @if (isset($accountDetail->narration) && !empty($accountDetail->narration))
                <tr>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td><strong>Narration:</strong></td>
                    <td colspan="8">{{ $accountDetail->narration }}</td>
                </tr>
                @endif

                <tr>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                </tr>

                <tr>
                    <td colspan="8" style="font-weight:bold">Kindly acknowledge receipt.</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="4">Thanking You..</td>
                </tr>
            </tbody>
        </table>
    </div>

    @if (!$loop->last)
    <div class="conditional-page-break"></div>
    @endif
    @endforeach
    @endif




</body>
<script>
    window.print();
    addEventListener("afterprint", (event) => {
        window.close();
    });
</script>

</html>