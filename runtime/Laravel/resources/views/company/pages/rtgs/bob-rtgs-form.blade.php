<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BANK OF BARODA - NEFT/RTGS Form</title>
    <style>
        body {
            font-family: Times New Roman, Times, serif;
            font-size: 12px;
            /* margin: 30px; */
            color: #000;
            background: #fff;
            padding: 0;
            margin: 0;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #222;
            padding: 2px 3px;
            vertical-align: top;
        }
        .no-border {
            border: none !important;
        }
        .section-title {
            font-weight: bold;
            text-align: center;
        }
        .highlight {
            color: red;
            font-weight: bold;
        }
        .small-text {
            font-size: 12px;
            font-weight: bold;
        }
        .sign-area {
            height: 40px;
        }
        .bank-seal {
            padding-top: 20px;
        }

        .ac-cell {
            border: 1px solid black;
            width: 20px;     /* Increased so number fits better */
            height: 20px;
            text-align: center;
            vertical-align: middle;
            color: black;
            line-height: 2.4px;
            }
        .conditional-page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <table class="no-border"  style="width:100%; border-collapse:collapse;">
        <tr>
            <td class="no-border" style="text-align: center"><b><u>BANK OF BARODA</u></b></td>
            <td class="no-border">
            </td>
            <td class="no-border" style="text-align: left;"><b><u>BANK OF BARODA</u></b></td>
        </tr>
        <tr>
            <td class="no-border section-title" style="width:30%;"></td>
            <td class="no-border section-title" style="width:25%; text-align:center;">
                ANNEXURE - II
            </td>
            <td class="no-border section-title" style="width:45%;">
                <div style="display:flex; justify-content:space-between; width:100%;">
                    <span style="white-space:nowrap;"><u>PAYING – IN – SLIP FOR NEFT / RTGS</u></span>
                    <span style="text-align:right;">Form No. 404</span>
                </div>
            </td>


        </tr>

        <tr>
            <td class="no-border section-title" style="width:30%;">
                <b>Branch:</b> <u>MOTIPURA</u>
            </td>
            <td class="no-border section-title" style="width:25%; text-align:right;">
                <b>Branch:</b> <u>MOTIPURA</u>
            </td>
            <td class="no-border" style="width:45%;">
                <div style="display:flex; justify-content:space-between; width:100%;">
                    <span style="white-space:nowrap;margin-left: 50px"><b>Date:  <u>{{ $data['acknowledgment']['date'] }}</u></b></span>
                    <span style="text-align:right;"><b>Time of Receipt</b>____________</span>
                </div>
            </td>


        </tr>

        <tr>
            <td style="width:30%; text-align: center" class="no-border" colspan="1"><b><u>Date:  {{ $data['acknowledgment']['date'] }}</u></b></td>
            {{-- <td style="width:15%;" class="no-border"></td> --}}
            <td colspan="2" class="no-border" style="width:70%;text-align:center;">
                <b style="white-space:nowrap;">(FOR RTGS – AMOUNT MUST BE FOR ₹ 2 LACS OR MORE)</b>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <th style="width:30%;">COUNTER FOIL</th>
            <th style="width:70%;">Application for Electronic Funds Transfer to a customer of another Bank through RTGS/NEFT. (to be filled in by customer)</th>
        </tr>
        <tr>
            <td><b>Sender's a/c no.</b>:{{ $data['applicant']['account_number'] }}</td>
            <td><b>Sender's a/c no. of base branch:</b>{{ $data['applicant']['account_number'] }}</td>
        </tr>
        <tr>
            <td><b>Name of a/c holder:</b>{{ $data['applicant']['name'] }}</td>
            <td><b>Name of a/c holder (sender):</b> {{ $data['applicant']['name'] }}</td>
        </tr>
        <tr>
            <td>
                <b>NEFT / RTGS: RTGS</b>
            </td>
            <td>
                <b>NEFT / RTGS: RTGS</b>
            </td>
        </tr>
        <tr>
            <td><b>Favouring (payee) Name </b>:- {{ $data['beneficiary']['name'] }}</td>
            <td><b>Favouring /beneficiary Name </b>:- {{ $data['beneficiary']['name'] }}</td>
        </tr>
        <tr>
            <td><b>Bank:</b> {{ $data['beneficiary']['bank_name'] }}</td>
            <td><b>Receiving Bank Name:</b>{{ $data['beneficiary']['bank_name'] }}</td>
        </tr>
        <tr>
            <td><b>Branch:</b>{{ $data['beneficiary']['branch'] }}</td>
            <td><b>Receiving Branch Name:</b>{{ $data['beneficiary']['branch'] }}</td>
        </tr>
        <tr>
            <td><b>IFS Code:</b>{{ $data['beneficiary']['ifsc'] }}</td>
            <td><b>Receiving Branch IFS Code:</b>{{ $data['beneficiary']['ifsc'] }}</td>
        </tr>
        <tr>
            <td class="highlight"><b>Beneficiary a/c no:</b>{{ $data['beneficiary']['account_number'] }}</td>
            <td class="highlight" style="display: flex; align-items: center; gap: 6px;">
                <span style="white-space: nowrap;"><b>Beneficiary A/c No:</b></span>

                <table style="border-collapse: collapse;" style="padding: 0; margin: 0;">
                     @php
                        $chars = str_split($data['beneficiary']['account_number']);
                    @endphp

                    @foreach(range(0, 17) as $i)
                        <td class="ac-cell">
                            <span>{{ $chars[$i] ?? '' }}</span>
                        </td>
                    @endforeach
                </table>
            </td>

        </tr>
        <tr>
            <td><b>Beneficiary a/c type: CC A/C</b></td>
            <td><b>Beneficiary a/c type: (SB/CA/OD/CC/NRE/Credit Card)/Remittance to Indo Nepal-C C A/C </b></td>
        </tr>
        <tr>
            <td></td>
            <td><b>Message for beneficiary (applicable for RTGS only)</b></td>
        </tr>
        <tr>
            <td><b>Amount ₹:-</b> {{ $data['fund_transfer']['amount'] ? format_number($data['fund_transfer']['amount'], 2) : "" }}</td>
            <td><b>Amount ₹:-</b> {{ $data['fund_transfer']['amount'] ? format_number($data['fund_transfer']['amount'], 2) : "" }}</td>
        </tr>
        <tr>
            <td><b>Exchange ₹:-</b></td>
            <td><b>Exchange ₹:-</b></td>
        </tr>
        <tr>
            <td><b>Total amount ₹:- </b>{{ $data['fund_transfer']['total_amount'] ? format_number($data['fund_transfer']['total_amount'], 2) : "" }}</td>
            <td><b>Total amount ₹:- </b>{{ $data['fund_transfer']['total_amount'] ? format_number($data['fund_transfer']['total_amount'], 2) : "" }}</td>
        </tr>
        <tr>
            <td><b>Total amount in words:-</b> {{ $data['fund_transfer']['amount_in_words'] }}</td>
            <td><b>Total amount in words:-</b>{{ $data['fund_transfer']['amount_in_words'] }}</td>
        </tr>
        <tr>
            <td><b>CH NO. </b>{{ $data['fund_transfer']['financial_tran_id'] ?? '' }}</td>
            <td class="highlight" style="display: flex; align-items: flex-start; gap: 10px;">
                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                    <span style="white-space: nowrap;"><b>Beneficiary A/c No: </b></span>
                    <span class="highlight" style="font-size: 0.8em;"></b>(to be written 2nd time as per RBI guidelines)</b></span>
                </div>

                <table style="border-collapse: collapse; padding: 0; margin: auto;">

                    @php
                        $chars = str_split($data['beneficiary']['account_number']);
                    @endphp

                    @foreach(range(0, 17) as $i)
                        <td class="ac-cell">
                            <span>{{ $chars[$i] ?? '' }}</span>
                        </td>
                    @endforeach
                </table>
            </td>


        </tr>
    </table>
    <br>
    <table>
        <tr>
            <td class="no-border" style="width:31%;">
                <b>Signature of Customer</b><br>
                <b>Tel/Mob No.:__________________</b><br>
                <b>PAN: _________________________</b><br>
                <br>
                <div class="bank-seal"><b>Bank's Seal</b></div>
            </td>
            <td class="no-border" style="width:69%;">
                <span class="small-text">
                    I /We request you to make the above remittance. It is being understood that the remittance is to be sent at my/our own risk and
                    my/our responsibility and on the distinct understanding that no liability whatsoever is to attach to the Bank for any loss or
                    damage arising or resulting from delay in transmission, delivery or non delivery of the message or for any mistake, exchange or
                    error in transmission or delivery thereof or in deciphering the message from whatsoever cause or from its misinterpretation when
                    received or from failure to properly identity the persons name. I/We also hereby undertake to refund to bank any over remittance,
                    which is made as per RBI RTGS/NEFT scheme.
                    <br>
                    Please remit the amount as per above details by (i) debiting my/our SB/CA/OD/CC/NRE/ a/c No.-  {{$data['applicant']['account_number'] ?? ""}} with
                    <u>MOTIPURA</u> Branch (ii) I/We herewith tender cheque No- {{ $data['fund_transfer']['financial_tran_id'] ?? '' }} drawn on our a/c towards its cost including Bank charge.
                    <br>
                </span>
            </td>
        </tr>
    </table>
    <br>
    <table>
        <tr>
            <td style="padding:8px 6px;width:30%;" class=""><b>Sign.of Clerk/Cashier/Teller</b></td>
            <td style="padding:8px 6px; width:36%;" class=""><b>Sign.of Customer</b></td>
            <td style="padding:8px 6px; width:12%;" class=""><b>Sign. of operator </b></td>
            <td style="padding:8px 6px; width:12%;" class=""><b>Sign. of officer </b></td>
            <td style="padding:8px 6px; width:12%;" class=""><b>Sign. of officer </b></td>
        </tr>
        <tr>
            <td style="padding:8px 6px" class="" rowspan="2"></td>
            <td style="padding:8px 6px" class=""><b>Tel/Mob No.:__________________</td>
            <td style="padding:8px 6px" class=""><b>(who created msg.)</b></td>
            <td style="padding:8px 6px" class=""><b>(who authorized)</b></td>
            <td style="padding:8px 6px" class=""><b>(who verified)</b></td>
        </tr>
        <tr>
                <td style="padding:8px 6px" class=""><b> PAN: ______________________</b></td>
                <td style="padding:8px 6px" class=""></td>
                <td style="padding:8px 6px" class=""></td>
                <td style="padding:8px 6px" class=""></td>
        </tr>
    </table>
</body>
<script>
    document.addEventListener("keydown", function(event) {
        if (event.key === "Escape") {
            console.log("Esc pressed - Closing tab or window");
            window.close();
        }
    });
</script>
</html>
