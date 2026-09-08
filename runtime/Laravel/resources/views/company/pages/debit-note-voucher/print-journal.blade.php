<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Debit Note Voucher</title>
  <style>
    * {
      margin: 0;
      padding: 0; 
      box-sizing: border-box;
    }

    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 13px;
    }

    .print-container {
      max-width: 994px;
      margin: auto;
      background: #fff;
      padding: 15px;
    }

    .header-box {
      width: 105%;
      border: 1px solid #000;
      text-align: center;
      padding: 10px;
      margin-bottom: 15px;
    }

    .header-box h5 {
      margin: 0 0 5px 0;
      font-size: 16px;
      font-weight: bold;
    }

    .header-box p {
      margin: 0 0 3px 0;
      font-size: 12px;
    }

    .voucher-info {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 5px;
      font-size: 13px;
      font-weight: bold;
    }

    table {
      width: 105%;
      border-collapse: collapse;
      page-break-inside: avoid;
    }

    table th, table td {
      border: 1px solid #000;
      padding: 5px 8px;
      font-size: 13px;
    }

    table th {
      text-align: center;
      font-weight: bold;
    }

    .text-center { text-align: center; }
    .text-end { text-align: right; }
    .text-start { text-align: left; }
    .fw-bold { font-weight: bold; }

    .signatures {
      display: flex;
      justify-content: space-between;
      margin-top: 50px;
      font-weight: bold;
      font-size: 13px;
    }

    @media print {
      body {
        margin: 0;
        padding: 0;
      }
      .print-container {
        margin-left: 15mm;
        margin-right: 5mm;
        width: auto;
      }
    }
  </style>
</head>

<body>
  <div class="print-container">
    <!-- Header -->
    <div class="header-box">
      <h5>{{ Str::upper($company->print_name ?? $company->name) }}</h5>
      <p>
        {{ Str::upper($company->address_one ?? "") }}<br />
        {{ Str::upper($company->address_two ?? "") }}
      </p>
      <p>
        @if(isset($company->state->name)) {{ Str::upper($company->state->name) }}, @endif
        @if(isset($company->country->name)) {{ Str::upper($company->country->name) }}, @endif
        PH : {{ $company->mobile_number ?? "" }}
      </p>
    </div>

    <!-- Voucher Info -->
    <div class="voucher-info">
      <div style="flex: 1; text-align: left;">
        Voucher No : {{ $voucher_serial }}
      </div>
      <div style="flex: 1; text-align: center;">
        Debit Note Voucher
      </div>
      <div style="flex: 1; text-align: right;">
        Journal Date : {{ $voucher_date }}
      </div>
    </div>
    
    <!-- Journal Table -->
    <table>
      <thead>
        <tr>
          <th style="width: 50px;">Sr</th>
          <th>Particular</th>
          <th style="width: 125px;">Debit</th>
          <th style="width: 125px;">Credit</th>
        </tr>
      </thead>
      <tbody>
        @php $totalDebitValue = 0; @endphp
        @foreach ($receipt_voucher_data as $particular)
          @if(isset($particular['column1']) && $particular['column1'] === 'Sr')
              @continue
          @endif

          @if(isset($particular['column1']) && $particular['column1'] === 'Total')
              <tr class="fw-bold">
                  <td colspan="2" class="text-end">Total</td>
                  <td class="text-end">{{ $particular['column2'] }}</td>
                  <td class="text-end">{{ $particular['column3'] }}</td>
              </tr>
              @php
                // Extract raw number for Amount In Words
                $totalDebitValue = (float) str_replace(',', '', $particular['column2']);
              @endphp
          @else
              <tr>
                  <td class="text-center">{{ $particular['column1'] }}</td>
                  <td>{{ $particular['column2'] }}</td>
                  <td class="text-end">{{ $particular['column3'] }}</td>
                  <td class="text-end">{{ $particular['column4'] }}</td>
              </tr>
          @endif
        @endforeach

        <tr>
          <td colspan="4"><span class="fw-bold">Narration:</span> {{ $narration }}</td>
        </tr> 
        
        @php
          $amountWords = amountInWords($totalDebitValue);
        @endphp
        
        <tr>
          <td colspan="4"><span class="fw-bold">Amount In Words:</span> {{ $amountWords }}</td>
        </tr>
      </tbody>
    </table>

    <!-- Footer Signatures -->
    <div class="signatures">
      <div>Prepared By : {{ Auth::user()->name }}</div>
      <div>Checked By</div>
      <div>Approved By</div>
    </div>
    
  </div>
</body>
<script>
  window.onload = function() {
      window.print();
  };
  addEventListener("afterprint", (event) => {
      window.close();
  });
</script>

</html>