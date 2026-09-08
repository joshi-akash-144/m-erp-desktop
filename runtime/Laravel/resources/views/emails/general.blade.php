<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333333;
            font-size: 14px;
            line-height: 1.6;
        }

        .email-wrapper {
            max-width: 650px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 4px;
            overflow: hidden;
            border: 1px solid #dde1e7;
        }

        .email-header {
            background-color: #1a3c5e;
            padding: 20px 30px;
            text-align: center;
        }

        .email-header h1 {
            color: #ffffff;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .email-body {
            padding: 30px;
        }

        .email-body table {
            border-collapse: collapse;
            width: 100%;
        }

        .email-body table td {
            border: 1px solid #d2d2d2;
            padding: 6px 10px;
            font-size: 13px;
        }

        .email-body a {
            color: #1a3c5e;
        }

        .attachment-section {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #e8e8e8;
            font-size: 13px;
        }

        .attachment-section a {
            display: inline-block;
            margin-top: 6px;
            padding: 6px 14px;
            background-color: #1a3c5e;
            color: #ffffff;
            text-decoration: none;
            border-radius: 3px;
            font-size: 13px;
        }

        .email-footer {
            background-color: #f8f9fa;
            border-top: 1px solid #dde1e7;
            padding: 16px 30px;
            text-align: center;
            font-size: 12px;
            color: #888888;
        }

        .email-footer p {
            margin: 2px 0;
        }
    </style>
</head>

<body>
    <div class="email-wrapper">

        <div class="email-header">
            <h1>{{ config('app.name', 'ERP') }}</h1>
        </div>

        <div class="email-body">
            {!! html_entity_decode($messageContent) !!}

            @if (!empty($attachment_file))
                <div class="attachment-section">
                    <strong>Attachment:</strong><br>
                    <a href="{{ asset('storage') . '/' . $attachment_file }}" target="_blank">Download Attachment</a>
                </div>
            @endif
        </div>

        <div class="email-footer">
            {{-- <p>This is an automated email. Please do not reply directly to this message.</p> --}}
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'ERP') }}. All rights reserved.</p>
        </div>

    </div>
</body>

</html>