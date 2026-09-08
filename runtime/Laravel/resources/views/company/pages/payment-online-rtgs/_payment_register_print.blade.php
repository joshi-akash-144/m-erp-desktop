<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Payment Register</title>
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

        table,
        th,
        td {
            border: 1px solid black;
            border-right: none;
            border-left: none;
        }

        table thead tr th {
            border-top: 2px solid black;
            /* Replace 'black' with your desired border color */
        }

        table tfoot tr td {
            border-top: 4px double rgb(66, 65, 65);
            border-bottom: 2px solid black;
        }

        th,
        td {
            padding: 6px;
            text-align: left;
        }

        .d-flex {
            display: flex;
            justify-content: center;
        }

        .text-end {
            text-align: end;
            margin-right: 100px;
        }

        .p-O {
            padding: 0;
        }

        .m-0 {
            margin: 0;
        }

        .mb-1 {
            margin-bottom: 10px;
        }

        th,
        td {
            padding: 3px;
            font-size: 13px;
            text-align: left;
        }

        .conditional-page-break {
            page-break-after: always;
        }

        .fw-bold {
            font-weight: bold;
        }

        @media print {

            /* Print-specific styles */
            body {
                margin: 0;
                /* Remove default margin for print */
            }

            table {
                padding: 0px;
                margin: 0px;
            }

            @page {
                size: landscape;
                /* Set the print layout to landscape mode */
            }
        }
    </style>
</head>

<table>
    @php
        $comapny_name = $final_data['header']['company_name'];
        $register_data = $final_data['rows'];
        $temp = 1;
        $total_amount = 0;
        $page = 1;
        $counter = 1;
    @endphp
    <div>
        <h4 class="d-flex p-O m-0" style="margin:7px">
            @isset($comapny_name)
                {{ Str::upper($comapny_name) }}
            @endisset
        </h4>
         <h4 class="d-flex p-O m-0" style="margin:7px">RTGS DATE :{{ $final_data['header']['payment_date'] }}</h4>
                    <h4 class="d-flex p-O m-0" style="margin:7px">RTGS/Cheque: {{ $final_data['header']['cheque_number'] }} File No:
                        {{ $final_data['header']['file_no'] }}
        {{-- @if ($single_payment == true)
            @foreach ($register_data as $data)
                @once
                    <h4 class="d-flex p-O m-0" style="margin:7px">RTGS DATE :{{ dateReFormat($data['payment_date']) }}</h4>
                    <h4 class="d-flex p-O m-0" style="margin:7px">RTGS/Cheque: {{ $data['cheque_no'] }} File No:
                        {{ $data['file_no'] }}
                    @endonce
            @endforeach
        @else
            <h4 class="d-flex p-O m-0" style="margin:7px">Payemnt Register</h4>
            @if (isset($final_data['start_date']) &&
                    !empty($final_data['start_date']) &&
                    (isset($final_data['end_date']) && !empty($final_data['end_date'])))
                <h4 class="d-flex p-O m-0" style="margin:7px">Date From {{ $final_data['start_date'] }} To
                    {{ $final_data['end_date'] }}</h4>
            @endif
        @endif --}}
    </div>
    <div class="text-end p-0 m-0 mb-1"><span>Page : {{ $page }}</span> </div>

    <body>
        <div>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        {{-- @if (!$single_payment == true) --}}
                        {{-- <th>Payment Date</th>
                        <th>Cheque Num.</th> --}}
                        {{-- @endif --}}
                        <th>Name</th>
                        <th>Bank Name</th>
                        <th>Account No.</th>
                        <th>IFSC Code</th>
                        <th class="text-end">Amount</th>
                        <th> </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($register_data as $item)
                        <tr>
                            <td>{{ $counter }}</td>
                            {{-- @if (!$single_payment)
                                <td class="fw-bold font-arial">
                                    {{ isset($item['payment_date']) ? dateReformat($item['payment_date']) : 'N/A' }}</td>
                                <td class="fw-bold font-arial">{{ isset($item['cheque_no']) ? $item['cheque_no'] : 'N/A' }}
                                </td>
                            @endif --}}
                            <td class="fw-bold font-arial">
                                {{ isset($item['name']) ? Str::of($item['name'])->limit(30) : 'N/A' }}</td>
                            <td class="fw-bold font-arial">{{ isset($item['bank_name']) ? $item['bank_name'] : 'N/A' }}
                            </td>
                            <td class="fw-bold font-arial">
                                {{ isset($item['bank_account_no']) ? $item['bank_account_no'] : 'N/A' }}
                            </td>
                            <td class="fw-bold font-arial">
                                {{ isset($item['bank_ifsc_code']) ? $item['bank_ifsc_code'] : 'N/A' }}
                            </td>
                            <td class="text-end fw-bold font-arial">{{ formatIndianNumber($item['amount']) ?? 'N/A' }}</td>
                            <td class="border"></td>
                        </tr>
                        @php
                            $counter++;
                            $temp++;
                            $total_amount += $item['amount'];
                        @endphp
                        @if ($temp == 25)
                            @php
                                $temp = -2;
                                $page++;
                            @endphp

                </tbody>
            </table>
            <div class="conditional-page-break"></div>
            <div class="text-end p-0 m-0 mb-1"> Page : {{ $page }}</div>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        {{-- @if (!$single_payment == true)
                            <th>Payment Date</th>
                            <th>Cheque Num.</th>
                        @endif --}}
                        <th>Name</th>
                        <th>Bank Name</th>
                        <th>Account No.</th>
                        <th>IFSC Code</th>
                        <th class="text-end">Amount</th>
                        <th> </th>
                    </tr>
                </thead>
                <tbody>
                    @endif
                    @endforeach
                    {{-- <tr style="border: 1px solid black">&nbsp;</tr> --}}
                    {{-- <tr><hr></tr> --}}
                    <tr style="border-top: 3px double black;border-bottom: 4px double black">
                        <td 
                        {{-- @if ($single_payment) colspan="4" @else colspan="6" @endif --}}
                        colspan="4" style="font-size: 14px">
                            Rs :
                            {{ isset($total_amount) ? amountInWords($total_amount) : 'N/A' }}
                        </td>
                        <td class="fw-bold font-arial">Total:</td>
                        <td class="text-end fw-bold font-arial">{{ formatIndianNumber($total_amount, 2) ?? 'N/A' }}</td>
                    </tr>
                </tbody>
            </table>
           {{-- <div style="position: fixed; bottom: 0; width: 100%;"> --}}
            <div style="display: flex; justify-content: space-between; margin-top:75px">
                <div><span class="font-arial">User Name : {{ Auth::user()->name }}</span></div>
                <div>Checker Sign </div>
                <div>Email Sender Sign</div>
                <div>Payment Authorization Sign</div>
            </div>
            {{-- </div> --}}
        </div>
    </body>
    <script>
        window.onload = function () { window.print(); };
        addEventListener("afterprint", (event) => {
            window.close();
        });
    </script>

</html>
