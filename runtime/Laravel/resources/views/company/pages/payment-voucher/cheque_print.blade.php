<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ config('app.name', 'ERP SYSTEM') }}</title>
    <style>
        body {

            font-family: 'Times New Roman', Times, serif !important;
            margin: 0;
            padding: 0;
        }

        .canvas {
            position: relative;
        }

        .draggable {
            position: absolute;
            /* border: 1px solid #000; */
            /* background-color: #f0f0f0; */
            cursor: grab;
        }

        /* Styles for print */
        @media print {
            body {
                /* Reset margins for print */
                font-family: 'Times New Roman', Times, serif !important;
                margin: 0;
            }

            .canvas {
                /* Additional styles for print version */
                width: 100%;
                height: 100%;
                page-break-before: always;
                /* Add page break before canvas */
            }

            .draggable {
                /* Additional styles for print version of draggable elements */
                border: none;
                /* Remove border */
                background-color: transparent;
                /* Make background transparent */
                cursor: default;
                /* Remove grab cursor */
            }

            /* Add your custom styles for headers and footers */
            @page {
                /* size: A4 portrait; */
                /* Set page size and orientation */
                /* margin: 20mm; */
                /* Set margins for headers and footers */
            }

            /* Example header */
            @page :header {
                content: "Your Header Content";
                font-size: 12pt;
                text-align: center;
            }

            /* Example footer */
            @page :footer {
                content: "Your Footer Content";
                font-size: 10pt;
                text-align: center;
            }

            @page {
                size: A4 portrait;
                margin: 0;
            }
        }
    </style>
    <style media="print">
        body {
            font-family: 'Times New Roman', Times, serif !important;
            margin: 0;
            padding: 0;
        }

        .canvas {
            width: 800px;
            height: 600px;
            border: none;
            /* Remove border for print */
            position: relative;
        }

        .draggable {
            position: absolute;
            background-color: #f0f0f0;
            cursor: default;
            /* Disable cursor for print */
            border: none;
            /* Remove border for print */
        }

        .btn-success {
            display: none;
            /* Hide the button in print mode */
        }
    </style>
</head>

<body>
    <form action="" method="get" id="cheque-style-form">
        @php
            $cheque_height = isset($cheque_data['cheque_height']) ? $cheque_data['cheque_height'] . 'px' : '600px';
            $cheque_width = isset($cheque_data['cheque_width']) ? $cheque_data['cheque_width'] . 'px' : '100%';
            $left_margin = isset($cheque_data['left_margin']) ? $cheque_data['left_margin'] . 'px' : '0px';
            $top_margin = isset($cheque_data['top_margin']) ? $cheque_data['top_margin'] . 'px' : '0px';
            $ac_payee_flag = $cheque_data['ac_payee_flag'] ?? 'n';
        @endphp
        <div class="canvas" id="canvas" style="top: {{ $top_margin }};left: {{ $left_margin }};height: {{ $cheque_height }};width: {{ $cheque_width }};">
            @if (isset($ac_payee_flag) && $ac_payee_flag == 'y')
                <b><p class="draggable" id="aCPayee" style="{{ $cheque_data['ac_payee'] }}">A/C Payee</p></b>
            @endif

            <p class="draggable" id="date" style="{{ $cheque_data['date'] }} font-size: 16px;">
                <b style="font-size: 16px;">{{ $cheque_data['cheque_date'] }}</b>
            </p>
            <p class="draggable" id="name" style="{{ $cheque_data['account_name'] }} font-size: 16px;">
                <b style="font-size: 16px;">{{ $cheque_data['cheque_name'] && $cheque_data['cheque_name'] == 'CASH' ? 'SELF' : $cheque_data['cheque_name'] }}</b>
            </p>
            @php
                $cheque_amount_in_word = $cheque_data['cheque_amount_in_word'];
                $wordsArray = explode(' ', $cheque_amount_in_word);
                $wordCount = count($wordsArray);
            @endphp
            <p class="draggable" id="rsInWord" style="{{ $cheque_data['amount_in_words'] }} font-size: 16px;">

                @if ($wordCount > 8)
                    <b style="font-size: 16px;">{{ implode(' ', array_slice($wordsArray, 0, 8)) }}</b>  <br>
                    <b style="font-size: 16px;">{{ implode(' ', array_slice($wordsArray, 8)) }} </b>
                @else
                    <b style="font-size: 16px;">{{ $cheque_amount_in_word }} </b>
                @endif
            </p>
            <p class="draggable" id="amount" style="{{ $cheque_data['amount'] }} font-size: 18px;">
                <b style="font-size: 18px;">{{ formatIndianNumber($cheque_data['cheque_amount'], 2) }}</b>
            </p>
        </div>
    </form>
</body>

<script>

    document.addEventListener("beforeprint", function() {
        // Hide unwanted elements before printing
        document.body.style.display = "none";
    });

    document.addEventListener("afterprint", function() {
        // Show elements after printing
        document.body.style.display = "";
    });

    document.addEventListener("keydown", function(event) {
        // Check for Esc key
        if (event.key === "Escape") {
            // Closing the tab or window
            console.log("Esc pressed - Closing tab or window");
            window.close(); // Note: This may not work in all browsers due to security restrictions
        }
    });

</script>

</html>
