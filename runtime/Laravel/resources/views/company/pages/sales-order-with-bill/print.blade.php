   <!DOCTYPE html>
   <html lang="en">

   <head>
       <meta charset="UTF-8">
       <meta name="viewport" content="width=device-width, initial-scale=1.0">
       <meta http-equiv="X-UA-Compatible" content="ie=edge">
       <title>Sales Order Detail with Bill</title>
       <style>
           body {
               font-family: Arial, sans-serif;
           }

           table {
               width: 100%;
               border-collapse: collapse;
               margin-top: 10px;
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
           }

           table tbody tr td {
               border: 0px;
           }

           table tfoot tr td {
               border-top: 4px double rgb(66, 65, 65);
               border-bottom: 2px solid black;
           }

           th,
           td {
               text-align: left;
               font-size: 12px;
               vertical-align: top;
           }

           .d-flex {
               display: flex;
               justify-content: center;
           }

           .text-end {
               text-align: end;
               margin-right: 100px;
           }

           .text-center {
               text-align: center;
           }

           .p-O {
               padding: 0;
           }

           .m-0 {
               margin: 0;
           }

           @media print {
               body {
                   margin: 0;
               }

               @page {
                   size: landscape;
               }
           }

           .conditional-page-break {
               page-break-after: always;
           }
       </style>
   </head>

   <body>
       <center>
           <div>
               <h4 class="d-flex p-O m-0">{{ Str::upper($company->name) }}</h4>
               <p class="d-flex p-0 m-0">Sales Order Detailed Register</p>
               <p class="d-flex p-0 m-0">Report Form <strong> &nbsp;  {{ $startDate }} </strong> &nbsp; To &nbsp;<strong> {{ $endDate }} </strong></p>
           </div>
        <table>
            <thead>
                <tr style="border-bottom: 2px solid black">
                    <th style="width: 5%">P.O. No.</th>
                    <th style="width: 7%">So Date</th>
                    <th style="width: 25%; padding-left:20px">Customer</th>
                    <th style="width: 8%;">Product</th>
                    <th style="width: 5%" class="text-end">Rate</th>

                    <th style="width: 6%" class="text-center">Ord.Qty</th>

                    <th style="width: 7%" class="text-center">Invoice Date</th>

                    <th style="width: 5%">GRN Number</th>
                    <th style="width: 5%">Invoice No.</th>
                    <th style="width: 5%" class="text-center">Vehical</th>
                    <th style="width: 5%" class="text-center">Bags</th>
                    <th style="width: 5%" class="text-end">Bill.Qty</th>
                    <th style="width: 5%" class="text-end">Rem.Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reportData as $row)
                    @if ($row['rate'] === 'Total')
                        <tr>
                            <td style="font-weight: 600; border-top: solid 1px black; border-bottom: 1px solid" class="text-end" colspan="5">Total</td>
                            <td style="font-weight: 600; border-top: solid 1px black; border-bottom: 1px solid" class="text-center">{{ $row['qty'] }}</td>
                            <td style="font-weight: 600; border-top: solid 1px black; border-bottom: 1px solid" colspan="5"></td>
                            <td style="font-weight: 600; border-top: solid 1px black; border-bottom: 1px solid" class="text-end">{{ $row['rec_qty'] }}</td>
                            <td style="font-weight: 600; border-top: solid 1px black; border-bottom: 1px solid" class="text-end">{{ $row['remaining_qty'] }}</td>
                        </tr>
                    @else
                        <tr>
                            <td>{{ $row['purchase_order_number'] ?? '' }}</td>
                            <td>{{ $row['so_date'] ? format_date($row['so_date']) : '' }}</td>
                            <td style="font-weight: 600; padding-left: 20px;">
                                {{ Str::of($row['customer_name'])->limit(18) }}
                                @if ($row['customer_city'])
                                    ({{ Str::of($row['customer_city'])->limit(6) }})
                                @endif
                            </td>
                            <td>{{ Str::of($row['product_name'])->limit(8) }}</td>
                            <td class="text-end">{{ !empty($row['rate']) ? number_format((float)$row['rate'], 2, '.', '') : '' }}</td>
                            <td class="text-center">{{ $row['qty'] ?? '' }}</td>
                            <td>{{ $row['invoice_date'] && $row['invoice_date'] !== '-' ? format_date($row['invoice_date']) : '' }}</td>
                            <td class="text-center">{{ $row['grn_no'] !== '-' ? $row['grn_no'] : '' }}</td>
                            <td>{{ $row['invoice_no'] !== '-' ? $row['invoice_no'] : '' }}</td>
                            <td>{{ $row['vehicle_no'] !== '-' ? $row['vehicle_no'] : '' }}</td>
                            <td class="text-center">{{ $row['bags'] !== '-' ? $row['bags'] : '' }}</td>
                            <td class="text-end">{{ $row['rec_qty'] }}</td>
                            <td class="text-end">{{ $row['remaining_qty'] }}</td>
                        </tr>
                    @endif
                @endforeach
                <tr>
                    <td colspan="13">&nbsp;</td>
                </tr>
            </tbody>
        </table>
       </center>
       <script>
           addEventListener("afterprint", (event) => {
               window.close();
           });
       </script>
   </body>

   </html>
