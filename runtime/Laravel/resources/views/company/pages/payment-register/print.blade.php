<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Register</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 10px; }
        h3, h4, h5 { margin: 3px 0; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 6px; }
        thead th { border-top: 2px solid #000; background: #f0f0f0; font-weight: bold; }
        tfoot td { border-top: 3px double #000; border-bottom: 2px solid #000; font-weight: bold; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        @media print {
            @page { size: landscape; margin: 10mm; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    @php
        $companyName = $company->name ?? '';
        $totalAmount = $rows->sum('paid_amount');
        $startDate   = !empty($filters['start_date']) ? $filters['start_date'] : '';
        $endDate     = !empty($filters['end_date'])   ? $filters['end_date']   : '';
    @endphp

    <h3>{{ strtoupper($companyName) }}</h3>
    <h4>Payment Register</h4>
    @if($startDate && $endDate)
        <h5>Period: {{ $startDate }} to {{ $endDate }}</h5>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width:40px;">#</th>
                <th style="width:90px;">Date</th>
                <th style="width:80px;">File No.</th>
                <th>Party Name</th>
                <th style="width:100px;">Cheque No.</th>
                <th class="text-end" style="width:110px;">Amount</th>
                <th style="width:70px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $i => $row)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $row->payment_date ? \Carbon\Carbon::parse($row->payment_date)->format('d/m/Y') : '' }}</td>
                <td>{{ $row->file_number ?? '' }}</td>
                <td>{{ $row->party_name ?? '' }}</td>
                <td>{{ $row->cheque_number ?? '' }}</td>
                <td class="text-end">{{ number_format($row->paid_amount, 2) }}</td>
                <td>{{ $row->payment_deleted_at ? 'Deleted' : 'Active' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-end">Total :</td>
                <td class="text-end">{{ number_format($totalAmount, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div style="display:flex; justify-content:space-between; margin-top:60px;">
        <span>Printed by: {{ Auth::user()->name ?? '' }}</span>
        <span>Authorised Signatory</span>
    </div>

    <script>window.onload = function(){ window.print(); }; addEventListener('afterprint', () => window.close());</script>
</body>
</html>
