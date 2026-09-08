<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Axis Bank RTGS Form</title>
    <style>
        @page {
            margin: 10px 20px;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
            background: #fff;
            padding: 0;
            margin: 0;
        }

        .main-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }

        .header-bg {
            background-color: #9c1c40;
            /* Axis Bank maroon */
            color: white;
            padding: 10px;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
        }

        .section-header {
            background-color: #ffffffff;
            color: black;
            padding: 2px;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            margin-top: 5px;
            margin-bottom: 2px;
            text-decoration: underline;
        }

        .char-table {
            border-collapse: collapse;
            display: inline-table;
        }

        .char-table td {
            border: 1px solid #000;
            width: 14px;
            height: 14px;
            text-align: center;
            vertical-align: middle;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .layout-table td {
            padding: 2px;
            vertical-align: middle;
        }

        .bordered-table td {
            border: 1px solid #000;
        }

        .line-input {
            border-bottom: 1px dashed #000;
            display: inline-block;
        }

        .check-box {
            border: 1px solid #000;
            width: 12px;
            height: 12px;
            display: inline-block;
            text-align: center;
            line-height: 12px;
            font-size: 10px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
        <div class="main-container page-break">
        <!-- HEADER -->
        <table style="width: 100%; border: 2px solid black; background-color: white; color: black;">
            <tr>
                <td style="width: 25%; padding: 5px;">
                    <h2 style="margin: 0; font-family: 'Arial Black', sans-serif; color:black;">Axis Bank</h2>
                </td>
                <td style="width: 75%; text-align: center;">
                    <b style="font-size: 14px;">Application For National Electronic Fund Transfer/<br>
                        Real-Time Gross Settlement System (NEFT/RTGS)<br>
                        Immediate Payment Service (IMPS)</b>
                </td>
            </tr>
        </table>

        <!-- TOP INFO -->
        <table style="width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 10px;">
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    To<br>
                    <span style="font-weight:bold;">The Branch Head</span><br>
                    <table style="border-collapse: collapse; margin-top: 2px;">
                        <tr>
                            <td
                                style="border-bottom: 1px dashed #000; color: #000; padding-bottom: 1px; min-width: 30px;">
                                {!! $data['applicant']['branch'] ?? '&nbsp;' !!}</td>
                            <td style="vertical-align: bottom; padding-left: 2px; white-space: nowrap;">Branch</td>
                        </tr>
                    </table>
                </td>
                <td style="width: 40%; vertical-align: top;">
                    <table style="border-collapse: collapse; width: 100%;">
                        <!-- Row 1: Date — aligns with "To" -->
                        <tr>
                            <td style="text-align: right; padding-bottom: 3px;">
                                <table style="width: 70%; margin-left: auto;">
                                    <tr>
                                        <td style="width:100px; font-size: 11px;">Date</td>
                                        <td>
                                            <table class="char-table">
                                                <tr>
                                                    @php
                                                        $date_val = !empty($data['acknowledgment']['date']) && $data['acknowledgment']['date'] !== 'N/A' ? date('dmY', strtotime(str_replace('/', '-', $data['acknowledgment']['date']))) : ''; $date_chars = $date_val;
                                                        $date_chars = str_pad($date_chars, 8, ' ', STR_PAD_RIGHT);
                                                    @endphp
                                                    @for ($i = 0; $i < 8; $i++)
                                                        <td style="width: 14px; height: 15px; font-size: 10px;">
                                                            {{ $date_chars[$i] }}</td>
                                                    @endfor
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <tr>
                            <td style="text-align: right;">
                                <table>
                                    <tr>
                                        <td style="width:120px">PAN No.</td>
                                        <td>
                                            <table class="char-table">
                                                <tr>
                                                    @php $pan_chars = str_split($data['applicant']['pan'] ?? ''); @endphp
                                                    @for ($i = 0; $i < 10; $i++)
                                                        <td style="width: 20px; height: 18px;">{!! $pan_chars[$i] ?? '&nbsp;' !!}
                                                        </td>
                                                    @endfor
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div style="margin-top: 1px; font-size: 10px;">
            Dear Sir,<br>
            <table style="width: 100%; border-collapse: collapse; margin-top: 2px;">
                <tr>
                    <td style="white-space: nowrap; width: 1%;">Please remit through RTGS / NEFT / IMPS a sum of
                        Rs.&nbsp;</td>
                    <td style="border-bottom: 1px dashed #000; text-align: center; color: #000; width: 120px;font-weight: bold;">
                        {!! e($data['fund_transfer']['amount'] ?? '') ?: '&nbsp;' !!}</td>
                    <td style="white-space: nowrap; width: 1%;">&nbsp;/- Rupees in words&nbsp;</td>
                    <td style="border-bottom: 1px dashed #000;">&nbsp;</td>
                </tr>
            </table>
            <table style="width: 100%; border-collapse: collapse; margin-top: 4px;">
                <tr>
                    <td style="border-bottom: 1px dashed #000; color: #000; font-weight: bold;">{!! !empty($data['fund_transfer']['amount']) ? amountInWords($data['fund_transfer']['amount']) : '&nbsp;' !!} </td>
                    <td style="white-space: nowrap; width: 1%; vertical-align: bottom;">&nbsp;only, as per details given
                        below:</td>
                </tr>
            </table>
        </div>

        <table
            style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-top: 5px; font-size: 10px;">
            <tr>
                <td style="border: 1px solid #000; padding: 2px;">
                    <table style="border-collapse: collapse;">
                        <tr>
                            <td style="vertical-align: top;">
                                <div class="check-box" style="margin-right: 5px;">&nbsp;</div>
                            </td>
                            <td>Cash (1. Fill pay-in slip &nbsp; 2. Only for NEFT Transactions upto Rs.49,999/- per day
                                )</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 2px;">
                    <table style="border-collapse: collapse;">
                        <tr>
                            <td style="vertical-align: top;">
                                <div class="check-box" style="margin-right: 5px;">✓</div>
                            </td>
                            <td>Debit my / our account. Cheque is enclosed (Please enclose a cheque favouring
                                "Yourselves for NEFT/ RTGS /IMPS remittance favouring _______ (the name of beneficiary)
                                " In case of insufficient space on cheque, beneficiary details can be mentioned on
                                reverse of the cheque.</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table style="width: 100%; margin-top: 2px; font-size: 9px;">
            <tr>
                <td style="text-align: left;">*To be filled by the Applicant in CAPITAL LETTERS</td>
                <td style="text-align: right;">*IMPS limit Rs.5.00 Lacs per Transaction</td>
            </tr>
        </table>

        <!-- REMITTER DETAILS -->
        <div class="section-header">Details of Applicant (Remitter)</div>
        <table class="layout-table">
            <tr>
                <td style="width: 25%;">Account Number</td>
                <td colspan="3">
                    <table class="char-table">
                        <tr>
                            @php $acc_chars = str_split($data['applicant']['account_number'] ?? ''); @endphp
                            @for ($i = 0; $i < 33; $i++)
                                <td>{{ $acc_chars[$i] ?? '' }}</td>
                            @endfor
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td>Cheque Number</td>
                <td>
                    <table class="char-table" style="width: 120px;">
                        <tr>
                            @php $chq_chars = str_split($data['fund_transfer']['cheque_number'] ?? ''); @endphp
                            @for ($i = 0; $i < 6; $i++)
                                <td>{{ $chq_chars[$i] ?? '' }}</td>
                            @endfor
                        </tr>
                    </table>
                </td>
                <td>Cheque Date</td>
                <td>
                    <table class="char-table" style="width: 155px; ">
                        <tr>
                            @php 
                                $date_val = !empty($data['acknowledgment']['date']) && $data['acknowledgment']['date'] !== 'N/A' ? date('dmY', strtotime(str_replace('/', '-', $data['acknowledgment']['date']))) : ''; 
                                $chq_date = str_pad($date_val, 8, ' ', STR_PAD_RIGHT); 
                            @endphp
                            @for ($i = 0; $i < 8; $i++)
                                <td>{!! $chq_date[$i] ?? '&nbsp;' !!}</td>
                            @endfor
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td>Remitter's Name</td>
                <td colspan="3">
                    <table class="char-table" style="margin-bottom: 2px;">
                        <tr>
                            @php $rem_name = str_split($data['applicant']['name'] ?? ''); @endphp
                            @for ($i = 0; $i < 33; $i++)
                                <td>{{ $rem_name[$i] ?? '' }}</td>
                            @endfor
                        </tr>
                    </table><br>
                    <table class="char-table">
                        <tr>
                            @for ($i = 33; $i < 66; $i++)
                                <td>{{ $rem_name[$i] ?? '' }}</td>
                            @endfor
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td>Address</td>
                <td colspan="3">
                    <table class="char-table" style="margin-bottom: 2px;">
                        <tr>
                            @php 
                                $addr1 = ($data['applicant']['address_one'] ?? '') === 'N/A' ? '' : trim($data['applicant']['address_one'] ?? '');
                                $addr2 = ($data['applicant']['address_two'] ?? '') === 'N/A' ? '' : trim($data['applicant']['address_two'] ?? '');
                                $full_addr = trim($addr1 . ' ' . $addr2);
                                if (empty($full_addr)) {
                                    $full_addr = 'N/A';
                                }
                                $rem_addr = str_split($full_addr);
                            @endphp
                            @for ($i = 0; $i < 33; $i++)
                                <td>{{ $rem_addr[$i] ?? '' }}</td>
                            @endfor
                        </tr>
                    </table><br>
                    <table class="char-table">
                        <tr>
                            @for ($i = 33; $i < 66; $i++)
                                <td>{{ $rem_addr[$i] ?? '' }}</td>
                            @endfor
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td>Mobile/Other Number</td>
                <td colspan="3" style="vertical-align: middle;">
                    @php $rem_mob = str_split(str_replace(['-', ' ', '+'], '', $data['applicant']['telephone'] ?? '')); @endphp
                    <table class="char-table" style="width: 80%;">
                        <tr>
                            @for ($i = 0; $i < 10; $i++)
                                <td>{{ $rem_mob[$i] ?? '' }}</td>
                            @endfor
                            <td style="border: none; padding: 0 3px; font-weight: bold; font-size: 12px;">/</td>
                            @for ($i = 10; $i < 25; $i++)
                                <td>{{ $rem_mob[$i] ?? '' }}</td>
                            @endfor
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td>Remitter LEI Code</td>
                <td>
                    <table class="char-table" style="width: 85%;">
                        <tr>
                            @php $rem_lei = str_split($data['applicant']['lei'] ?? ''); @endphp
                            @for ($i = 0; $i < 20; $i++)
                                <td>{!! $rem_lei[$i] ?? '&nbsp;' !!}</td>
                            @endfor
                        </tr>
                    </table>
                    <br>
                    <span style="font-size: 9px; font-weight: bold; white-space: nowrap;">(20 Digit Alphanumeric code is
                        mandatory for Rs.50crore and above transactions for Non-individual customers)</span>
                </td>
            </tr>
        </table>

        <!-- BENEFICIARY DETAILS -->
        <div class="section-header">Details of Beneficiary</div>
        <table class="layout-table">
            <tr>
                <td style="width: 25%;">Beneficiary's Name</td>
                <td>
                    <table class="char-table" style="margin-bottom: 2px;">
                        <tr>
                            @php $ben_name = str_split($data['beneficiary']['name'] ?? ''); @endphp
                            @for ($i = 0; $i < 33; $i++)
                                <td>{{ $ben_name[$i] ?? '' }}</td>
                            @endfor
                        </tr>
                    </table><br>
                    <table class="char-table">
                        <tr>
                            @for ($i = 33; $i < 66; $i++)
                                <td>{{ $ben_name[$i] ?? '' }}</td>
                            @endfor
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td>Beneficiary Account Number</td>
                <td>
                    <table class="char-table">
                        <tr>
                            @php $ben_acc = str_split($data['beneficiary']['account_number'] ?? ''); @endphp
                            @for ($i = 0; $i < 33; $i++)
                                <td>{{ $ben_acc[$i] ?? '' }}</td>
                            @endfor
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td>Reconfirm Account Number</td>
                <td>
                    <table class="char-table">
                        <tr>
                            @for ($i = 0; $i < 33; $i++)
                                <td>{{ $ben_acc[$i] ?? '' }}</td>
                            @endfor
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td>Beneficiary Type</td>
                <td>
                    <div class="check-box"></div> Individual / Government Department (Refer Point No.12 under Terms &
                    Conditions)<br>
                    <div class="check-box">✓</div> Non - Individual (Provide LEI code if transaction amount is Rs.50
                    crore and above)
                </td>
            </tr>
            <tr>
                <td>Beneficiary LEI Code</td>
                <td>
                    <table class="char-table" style="width: 65%;">
                        <tr>
                            @php $ben_lei = str_split($data['beneficiary']['lei'] ?? ''); @endphp
                            @for ($i = 0; $i < 20; $i++)
                                <td>{!! $ben_lei[$i] ?? '&nbsp;' !!}</td>
                            @endfor
                        </tr>
                    </table>
                    (20 digit code)
                </td>
            </tr>
            <tr>
                <td>Beneficiary Bank Name</td>
                <td>
                    <table class="char-table">
                        <tr>
                            @php $ben_bank = str_split($data['beneficiary']['bank_name'] ?? ''); @endphp
                            @for ($i = 0; $i < 33; $i++)
                                <td>{{ $ben_bank[$i] ?? '' }}</td>
                            @endfor
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td>Beneficiary Bank Branch Name & Address</td>
                <td>
                    <table class="char-table" style="margin-bottom: 2px;">
                        <tr>
                            @php $ben_branch = str_split($data['beneficiary']['branch'] ?? ''); @endphp
                            @for ($i = 0; $i < 33; $i++)
                                <td>{{ $ben_branch[$i] ?? '' }}</td>
                            @endfor
                        </tr>
                    </table><br>
                    <table class="char-table">
                        <tr>
                            @for ($i = 33; $i < 66; $i++)
                                <td>{{ $ben_branch[$i] ?? '' }}</td>
                            @endfor
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td>Beneficiary Bank IFSC Code</td>
                <td>
                    <table class="char-table" style="width: 35%;">
                        <tr>
                            @php $ben_ifsc = str_split($data['beneficiary']['ifsc'] ?? ''); @endphp
                            @for ($i = 0; $i < 11; $i++)
                                <td>{{ $ben_ifsc[$i] ?? '' }}</td>
                            @endfor
                        </tr>
                    </table>
                    (11 digit code)
                </td>
            </tr>
            <tr>
                <td>Sender to Receiver Information</td>
                <td>
                    <table class="char-table" style="margin-bottom: 2px;">
                        <tr>
                            @for ($i = 0; $i < 35; $i++)
                                <td></td>
                            @endfor
                        </tr>
                    </table><br>
                    <table class="char-table">
                        <tr>
                            @for ($i = 35; $i < 70; $i++)
                                <td></td>
                            @endfor
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- CUSTOMER SIGNATURE -->
        <div class="section-header">Customer Signature(s)</div>
        <p style="font-size: 10px; margin: 5px 0;">
            I / We hereby authorize Axis Bank Ltd to carry out NEFT/RTGS/IMPS remittance as per the details mentioned in
            this form and also to debit my / our account for the NEFT/RTGS/IMPS amount plus charges and taxes as
            applicable. I / We hereby agree that the above transaction is subject to the Terms & Conditions as given
            overleaf and has been understood and I / We abide by them.
        </p>
        <table class="layout-table" style="margin-top: 2px;">
            <tr>
                <td style="width: 33%; text-align: center;">
                    <div
                        style="border: 1px dashed #ccc; height: 35px; margin: 0 10px; display: flex; align-items: center; justify-content: center; color: #aaa;">
                        Authorised Signatory (Affix Stamp<br>in case of Non-individual) as per<br>mode of operation
                    </div>
                    <div style="text-align: left; padding-left: 10px; margin-top: 2px;">Name:</div>
                </td>
                <td style="width: 33%; text-align: center;">
                    <div
                        style="border: 1px dashed #ccc; height: 35px; margin: 0 10px; display: flex; align-items: center; justify-content: center; color: #aaa;">
                        Authorised Signatory (Affix Stamp<br>in case of Non-individual) as per<br>mode of operation
                    </div>
                    <div style="text-align: left; padding-left: 10px; margin-top: 2px;">Name:</div>
                </td>
                <td style="width: 33%; text-align: center;">
                    <div
                        style="border: 1px dashed #ccc; height: 35px; margin: 0 10px; display: flex; align-items: center; justify-content: center; color: #aaa;">
                        Authorised Signatory (Affix Stamp<br>in case of Non-individual) as per<br>mode of operation
                    </div>
                    <div style="text-align: left; padding-left: 10px; margin-top: 2px;">Name:</div>
                </td>
            </tr>
        </table>

        <hr style="border-top: 1px dashed #000; margin: 5px 0;">

        <!-- ACKNOWLEDGEMENT -->
        <div class="section-header">Acknowledgement to Customer</div>
        <table class="layout-table" style="width: 100%; font-size: 11px;">
            <tr>
                <td style="width: 20%;">Date : <b>{{ !empty($data['acknowledgment']['date']) && $data['acknowledgment']['date'] !== 'N/A' ? date('d-m-Y', strtotime(str_replace('/', '-', $data['acknowledgment']['date']))) : '' }}</b></td>
                <td style="width: 50%;">Time of Receipt:</td>
            </tr>
        </table>
        <div style="margin-top: 5px;">
            We acknowledge receipt of NEFT/RTGS/IMPS instruction(s) for Rs. <span class="line-input"
                style="width: 150px; text-align: center; color: #000; font-weight: bold;">{{ $data['fund_transfer']['amount'] ?? '' }}</span>
            /- <br>(Rupees in words
            <span class="line-input"
                style="width: 500px; padding-left: 5px; color: #000; font-weight: bold;">{{ !empty($data['fund_transfer']['amount']) ? amountInWords($data['fund_transfer']['amount']) : '' }}</span>)
        </div>
        <table class="layout-table" style="margin-top: 2px; width: 100%;">
            <tr>
                <td style="width: 55%; vertical-align: top;">
                    Cheque Number: <span
                        style="padding: 1px 3px;letter-spacing: 3px;">{!! e($data['fund_transfer']['cheque_number'] ?? '') ?: str_repeat('&nbsp;', 20) !!}</span><br>
                    Remitter Account number : <span
                        style="padding: 1px 3px;">{!! e($data['applicant']['account_number'] ?? '') ?: str_repeat('&nbsp;', 20) !!}</span><br>
                    Remitter A/c. Name: <span
                        style="padding: 1px 3px;">{!! e($data['applicant']['name'] ?? '') ?: str_repeat('&nbsp;', 20) !!}</span><br>
                    Reference No. (For RTGS/NEFT) : <span
                        style="padding: 1px 3px;">{!! str_repeat('&nbsp;', 20) !!}</span><br>
                    Retrieval Reference No. (For IMPS): <span
                        style="padding: 1px 3px;">{!! str_repeat('&nbsp;', 20) !!}</span><br>
                    Saksham Reference No. (For IMPS): <span
                        style="padding: 1px 3px;">{!! str_repeat('&nbsp;', 20) !!}</span>
                </td>
                <td style="width: 45%; vertical-align: top;">
                    Beneficiary Bank IFSC: <span
                        style="padding: 1px 3px; letter-spacing: 3px;">{!! e($data['beneficiary']['ifsc'] ?? '') ?: str_repeat('&nbsp;', 20) !!}</span><br>
                    Beneficiary Bank Name: <span
                        style="padding: 1px 3px;">{!! e($data['beneficiary']['bank_name'] ?? '') ?: str_repeat('&nbsp;', 20) !!}</span><br>
                    Beneficiary Account number: <span
                        style="padding: 1px 3px;">{!! e($data['beneficiary']['account_number'] ?? '') ?: str_repeat('&nbsp;', 20) !!}</span><br>
                    Beneficiary Name: <span
                        style="padding: 1px 3px;">{!! e($data['beneficiary']['name'] ?? '') ?: str_repeat('&nbsp;', 20) !!}</span><br>
                    <div style="text-align: right; padding-right: 5px; margin-top: 10px;">
                        Name & Signature of Bank official with date & Time of receipt
                    </div>
                </td>
            </tr>
        </table>
    </div>
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
