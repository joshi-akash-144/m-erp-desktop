<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Godown Report </title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid black;
            border-right: none;
            border-left: none;
        }

        table thead tr th {
            border-top: 2px solid black;
        }

        table tfoot tr td {
            border-top: 4px double rgb(66, 65, 65);
            border-bottom: 2px solid black;
        }

        th, td {
            padding: 3px;
            font-size: 10pt;
            text-align: left;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .text-end { text-align: right; margin-right: 100px; }
        .fw-bold { font-weight: 700 !important; }
        .m-0 { margin: 0; }
        .mb-1 { margin-bottom: 10px; }
        .p-0 { padding: 0; }
        .nowrap { white-space: nowrap; }
        .d-flex { display: flex; }
        .justify-content-between { justify-content: space-between; }
        .text-uppercase { text-transform: uppercase; }

        .conditional-page-break {
            page-break-before: always;
        }

        .continuation-text {
            text-align: right;
            font-size: 14px;
            margin: 5px 0 0 0;
            padding: 0;
        }

        .continuation-header {
            margin-bottom: 8px;
        }

        .company-name {
            font-size: 13px;
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 0;
        }

        .page-info {
            font-size: 13px;
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 0;
        }

        .report-name {
            letter-spacing: 1px;
        }

        @media print {
            body { margin: 0; }
            table { padding: 0; margin: 0; }
            @page { size: landscape; }
        }
    </style>
</head>

<body>
@php
    $page = 1;
    $rowCountOnPage = 0;
    // Adjust these row limits based on your actual paper size and font sizes
    $firstPageRows = 14; 
    $otherPageRows = 15;
@endphp
    <div class="report-container">
        {{-- Report Header --}}
        <div class="row">
            <div class="col-md-12 text-center">
                <p class="fw-bold" style="margin: 10px 0 !important;">{{ Str::upper($data['companyName']) }}</p>
                <p class="fw-bold" style="margin: 5px 0;">
                    @php
                        $status = $data['action_id'] ?? 'all';
                        $title = match($status) {
                            'all'            => 'All product in out',
                            'product_in'     => 'Ticket in',
                            'product_out'    => 'Ticket out',
                            'product_in_out' => 'Ticket in out',
                            'pending_in'     => 'All in /out & pending',
                            'pending_out'    => 'All pending in/out & pending',
                            'pending_in_out' => 'All pending in/out & pending',
                            default          => 'All pending in/out & pending',
                        };
                    @endphp
                    {{ $title }}
                </p>
                <div class="report-date d-flex justify-content-between" style="margin-top: 5px; border-bottom: 1px solid black; padding-bottom: 5px;">
                    <span><b>Date Period : </b> {{ $data['datePeriod'] }}</span>
                      <span><b>Page : </b> {{ $page }}</span>
                </div>  
            </div>
        </div>

        {{-- Main Data Table --}}
        <div class="row" style="border-bottom: 1px solid black;">
            <div class="table-wrapper">
                <table class="tablestyle table ladger-table" id="salesBillItem">
                    <thead>
                        <tr>
                            @foreach($data['tableConfig']['columns'] as $column)
                                <th class="tabel_header {{ $column['class'] ?? '' }}" style="width: {{ $column['width'] ?? 'auto' }};">
                                    {!! $column['label'] !!}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody id="billVerifyTable">
                        @php
                            $totalInCount = 0;
                            $totalOutCount = 0;
                            $total_bags = 0;
                            $total_net_weight = 0.000;
                            $without_bag_total_weight = 0.000;
                        @endphp

                        @foreach ($data['data'] as $key => $value)
                            @php
                                if ($value->in_out_status == 'in') {
                                    $totalInCount++;
                                } else {
                                    $totalOutCount++;
                                }

                                // Update totals
                                $total_bags += ($value->bag_count ?? 0);
                                $total_net_weight += floatval($value->net_weight ?? 0);
                                $without_bag_total_weight += floatval($value->net_weight_wt_bag ?? 0);

                                $rowCountOnPage++;
                                $currentPageLimit = $page === 1 ? $firstPageRows : $otherPageRows;
                                $needsPageBreak = $rowCountOnPage > $currentPageLimit;
                            @endphp

                            @if ($needsPageBreak)
                                @php
                                    $page++;
                                    $rowCountOnPage = 1;
                                @endphp

                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <p class="continuation-text">cont. on page {{ $page }}...</p>

                            <div class="conditional-page-break"></div>

                            {{-- Next page header --}}
                                <div class="continuation-header">
                                    <p class="company-name">{{ strtoupper($data['companyName']) }}</p>
                                    <p class="page-info">
                                        Page {{ $page }}: <span class="report-name">{{ $title }}</span>
                                    </p>
                                </div>

                            <div class="row" style="border-bottom: 1px solid black;">
                                <div class="table-wrapper">
                                    <table class="tablestyle table ladger-table" id="salesBillItem">
                                        <thead>
                                            <tr>
                                                @foreach($data['tableConfig']['columns'] as $column)
                                                    <th class="tabel_header {{ $column['class'] ?? '' }}" style="width: {{ $column['width'] ?? 'auto' }};">
                                                        {!! $column['label'] !!}
                                                    </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody id="billVerifyTable">
                            @endif


                            <tr>
                                {{-- SR NO / GRN No --}}
                                <td>
                                    <b>{{ $key + 1 }}</b><br>
                                    {{-- @php
                                        if($value->grn_serial){
                                            echo '<span class="text-dark fw-bold">GRN:</span><span>'. $value->grn_serial ?? 0 . '</span>';
                                        }else{
                                            echo '<span class="text-dark fw-bold">DC:</span><span>'. $value->dc_serial ?? 0 . '</span>';
                                        }
                                    @endphp --}}
                                    {{ $value->grn_serial ?? '-'}}
                                </td>

                                {{-- IN/OUT --}}
                                <td class="fw-bold">{{ Str::upper($value->in_out_status) }}<br>&nbsp;</td>

                                {{-- Vehicle No --}}
                                <td class="fw-bold">{{ Str::upper($value->vehicle_number) }}<br>&nbsp;</td>

                                {{-- Particular/Product --}}
                                <td>
                                    <div>{{ \Illuminate\Support\Str::limit($value->account->name ?? 'N/A', 30, '...') }}</div>
                                    <div style="font-size:12px;">{{ $value->item->name ?? 'N/A' }}</div>
                                </td>

                                {{-- Challan No/Challan Weight --}}
                                <td class="text-left">
                                    {{ $value->challan_serial ?? '-' }}<br>
                                    {{ number_format($value->challan_weight ?? 0, 0, '.', '') }}
                                </td>

                                {{-- Bags --}}
                                <td class="text-left">{{ $value->bag_count ?? '0' }}<br>&nbsp;</td>

                                {{-- Destination/Godown --}}
                                <td class="text-uppercase">
                                    {{ \Illuminate\Support\Str::limit($value->destination->name ?? '', 15, '...') }}<br>
                                    {{ \Illuminate\Support\Str::limit($value->godownUnit->godown_name ?? '', 15, '...') }}
                                </td>

                                {{-- Dates --}}
                                <td>
                                    {{ !empty($value->date_in) && $value->date_in !== '-' ? $value->date_in : '---- / ---- / -----' }}<br>
                                    {{ !empty($value->date_out) && $value->date_out !== '-' ? $value->date_out : '---- / ---- / -----' }}
                                </td>

                                {{-- Gross/Tare Weight --}}
                                <td class="nowrap">
                                    <small>Gross:</small> {{ number_format($value->gross_weight ?? 0, 0, '.', '') }}<br>
                                    <small>Tare:&nbsp;&nbsp;&nbsp;&nbsp;</small>{{ number_format($value->tare_weight ?? 0, 0, '.', '') }}
                                </td>

                                {{-- Net Weight --}}
                                <td class="text-right">
                                    <br>{{ number_format($value->net_weight ?? 0, 3, '.', '') }}
                                </td>

                                {{-- Net Without Bags --}}
                                <td class="text-right">
                                    <br>{{ number_format($value->net_weight_wt_bag ?? 0, 3, '.', '') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Totals Table (Styled to match the main table alignment) --}}
            <table>
                <thead>
                    <tr>
                        @foreach($data['tableConfig']['columns'] as $column)
                            <th style="width: {{ $column['width'] ?? 'auto' }};"></th>
                        @endforeach
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <td colspan="3"></td>
                        <td class="text-end fw-bold">TOTAL</td>
                        <td class="text-center fw-bold">{{ $total_bags }}</td>
                        <td colspan="3"></td>
                        <td class="text-end fw-bold">{{ number_format($total_net_weight, 3, '.', '') }}</td>
                        <td class="text-end fw-bold">{{ number_format($without_bag_total_weight, 3, '.', '') }}</td>
                    </tr>
                </tfoot>
            </table>

            {{-- Footer Counts --}}
            <div class="col-md-6 text-end">
                <p style="margin-bottom: -10px; margin-top: 10px;">Total IN : <b>{{ $totalInCount }}</b></p>
                <p style="margin-bottom: 10px;">Total OUT : <b>{{ $totalOutCount }}</b></p>
            </div>
        </div>
    </div>

    <script>
        // window.print();
        window.addEventListener("afterprint", (event) => {
            window.close();
        });
    </script>
</body>

</html>
