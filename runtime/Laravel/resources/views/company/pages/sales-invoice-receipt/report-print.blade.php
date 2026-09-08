@php
    use App\Helpers\NumberHelper;
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Sales Dairy Hisab Report - Print</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Public Sans', Roboto, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.4;
            color: #000;
            background-color: #fff;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @page { size: portrait; }

        body { padding-left: 5mm; min-height: 100vh; }

        .report-company {
            font-size: 21px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 2px 0;
        }

        .report-title {
            font-size: 16px;
            font-weight: 700;
            text-align: center;
            margin: 3px 0 10px 0;
            letter-spacing: 1px;
        }

        .report-content { margin-top: 8px; }

        table { width: 100%; border-collapse: collapse; page-break-inside: auto; border-spacing: 0; }

        th, td { padding: 4px 6px; text-align: left; vertical-align: top; }

        thead { display: table-header-group; }
        tbody { display: table-row-group; }

        tr { page-break-inside: avoid; break-inside: avoid; }

        .report-table thead th {
            font-size: 13px;
            font-weight: 600;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            background-color: #f4f4f4;
        }

        .report-table tbody td {
            border-bottom: 1px dotted #bbb;
            font-size: 12.5px;
        }

        .text-center { text-align: center !important; }
        .text-end { text-align: right !important; }
        .text-start { text-align: left !important; }

        .report-footer {
            font-size: 11.5px;
            font-style: italic;
            margin-top: 12px;
            padding-top: 5px;
            display: flex;
            justify-content: space-between;
        }

        @media print {
            html, body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                margin: 0;
                padding: 0;
            }

            .no-print { display: none !important; }

            @page { size: A4; margin: 10mm 15mm 10mm 15mm; }

            .report-table thead th {
                background-color: #f4f4f4 !important;
                -webkit-print-color-adjust: exact !important;
            }
        }
    </style>
</head>

<body>
    <header class="print-header">
        <h3 class="report-company">{{ strtoupper($company->name ?? '') }}</h3>
        <h5 class="report-title">Sales Dairy Hisab Report</h5>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
            <tr>
                <td style="text-align: left; padding: 0; border: none;">
                    <b>
                        @if (!empty($filters['start_date']) && !empty($filters['end_date']))
                            Date Period : {{ $filters['start_date'] }} to {{ $filters['end_date'] }}
                        @else
                            Date Period : All
                        @endif
                    </b>
                </td>
                <td style="text-align: right; padding: 0; border: none;">
                    <b>Printed On : {{ now()->format('d-m-Y H:i:s') }}</b>
                </td>
            </tr>
        </table>
    </header>

    <main class="print-main">
        <div class="report-content">
            <table class="report-table">
                <thead style="background-color: #f4f4f4;">
                    <tr>
                        <th class="text-center" style="width: 4%;">#</th>
                        <th class="text-center" style="width: 8%;">DATE</th>
                        <th class="text-center" style="width: 6%;">DALAL N</th>
                        <th class="text-center" style="width: 6%;">P.O NO</th>
                        <th class="text-center" style="width: 10%;">DEL STION</th>
                        <th class="text-center" style="width: 10%;">ITEM</th>
                        <th class="text-end" style="width: 7%;">RATE</th>
                        <th class="text-center" style="width: 5%;">DAYS</th>
                        <th class="text-end" style="width: 7%;">QTY</th>
                        <th class="text-center" style="width: 8%;">DATE</th>
                        <th class="text-center" style="width: 9%;">TRUCK NO</th>
                        <th class="text-center" style="width: 6%;">BAGS</th>
                        <th class="text-end" style="width: 8%;">WT</th>
                        <th class="text-end" style="width: 6%;">IN NO</th>
                        <th class="text-end" style="width: 8%;">REM. QTY</th>
                        <th class="text-center" style="width: 8%;">REM. DAYS</th>
                        <th class="text-center" style="width: 8%;">REC. DATE</th>
                        <th class="text-center" style="width: 10%;">REC. BY</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalWt = 0;
                    @endphp
                    @forelse ($rows as $index => $row)
                        @php
                            $totalWt += (float) ($row['wt'] ?? 0);
                        @endphp
                        <tr>
                            <td class="text-center" style="font-weight: bold;">{{ $index + 1 }}</td>
                            <td class="text-center">{{ $row['so_date'] }}</td>
                            <td class="text-center">{{ $row['dalal'] }}</td>
                            <td class="text-center">{{ $row['po_no'] }}</td>
                            <td class="text-center">{{ $row['delivery'] }}</td>
                            <td class="text-center">{{ $row['item'] }}</td>
                            <td class="text-end">{{ $row['rate'] }}</td>
                            <td class="text-center">{{ $row['days'] }}</td>
                            <td class="text-end">{{ $row['qty'] !== '' ? number_format((float)$row['qty'], 3) : '' }}</td>
                            <td class="text-center">{{ $row['inv_date'] }}</td>
                            <td class="text-center">{{ $row['truck_no'] }}</td>
                            <td class="text-center">{{ $row['bags'] }}</td>
                            <td class="text-end" style="font-weight: 500;">{{ $row['wt'] !== '' ? number_format((float)$row['wt'], 3) : '' }}</td>
                            <td class="text-end">{{ $row['inv_no'] }}</td>
                            <td class="text-end">{{ $row['rem_qty'] !== '' ? number_format((float)$row['rem_qty'], 3) : '' }}</td>
                            <td class="text-center">{{ $row['rem_days'] }}</td>
                            <td class="text-center">{{ $row['received_date'] }}</td>
                            <td class="text-center">{{ $row['received_by_name'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="17" class="text-center">No records found</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="12" class="text-end" style="font-weight: bold;">Total:</td>
                        <td class="text-end" style="font-weight: bold;">{{ number_format($totalWt, 3) }}</td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </main>

    <footer class="report-footer">
        <span>Prepared By: <strong>{{ auth()->user()->name }}</strong></span>
        <span>Prepared On: <strong>{{ now()->format('d-m-Y H:i:s') }}</strong></span>
    </footer>
</body>
<script>
    window.addEventListener("afterprint", () => window.close());
</script>

</html>
