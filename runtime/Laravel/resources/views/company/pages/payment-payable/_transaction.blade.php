
@forelse ($data as $item)
@php

    $accountName = isset($item['account_name']) ? $item['account_name'] : '';
    $accountCity = isset($item['account_city']) ? $item['account_city'] : '--';
    $rebateFromAnalysis = isset($item['rebate_from_analysis']) ? $item['rebate_from_analysis'] : 0;

    $isCdReadOnly = false;
    $isOldBill = false;

    if($item['reference_date']){
        if($item['reference_date'] < $yearStartDate){
            $isCdReadOnly = true;
            $isOldBill = true;
        }
    }

    $isOverPayment = false;

    if($rebateFromAnalysis && $item['source_type'] == \App\Enums\SourceType::PURCHASE && $item['pending_amount'] < 0){
        $isOverPayment = true;
    }

    $itemDetail = $item['items'];
    
    $tdsValue = $item['tds'] ?? 0;
    $cgstValue = $item['cgst'] ?? 0;
    $sgstValue = $item['sgst'] ?? 0;
    $igstValue = $item['igst'] ?? 0;
    $freightValue = $item['freight'] ?? 0;
    $labourValue = $item['labour'] ?? 0;
    $otherValue = $item['other'] ?? 0;
    $penaltyValue = $item['penalty'] ?? 0;
    $rebateValue = $item['rebate'] ?? 0;
    $roundValue = ($item['round_additive'] ?? 0) - ($item['round_deductive'] ?? 0);
    $cdValue = $item['cd'] ?? 0.00;
    $cdPercent = $item['cd_percent'] ?? 0;
    $netTotal = $item['net_total'] ?? $item['amount'] ?? 0;


    $highlightClass = '';

    if (!empty($itemDetail)) {
        foreach ($itemDetail as $value) {
            $destination = strtoupper(trim($value['destination_name'] ?? ''));

            if (in_array($destination, ['PALANPUR', 'KATARVA'])) {
                $highlightClass = 'text-danger fw-bold';
                break; // important
            }
        }
    }

    $partialPaid = $item['is_partial_paid'];
    $index = 1;
@endphp


<tr  id="row_{{ $loop->iteration }}"
    data-id="{{ $item['id'] }}"
    data-row="{{ $loop->iteration }}"
    data-source-type="{{ $item['source_type'] }}"
    data-source-id="{{ $item['source_id'] }}"
    data-reference-id="{{ $item['id'] }}"
    data-account-id="{{ $item['account_id'] }}"
    data-voucher-id="{{ $item['voucher_id'] }}"
    data-direction="{{ $item['direction'] }}"
    @if($isOldBill)style="background-color: #D1E7DD;" @endif
    @if($item['reference_type'] === 'advance') style="background-color: #ffe4e4;" @endif
>
    
    {{-- Check Box --}}
    <td class="text-center not-selectable-cell">
        <input class="form-check-input border border-1 border-dark-subtle row-checkbox non-selectable" type="checkbox" value="{{ $item['id'] }}"  data-ref="{{ $item['reference_number'] }}">
    </td>
    {{-- File Number --}}
    <td class="text-center"> {{ $item['file_number'] }}</td>
    {{-- Bill No --}}
    <td class="fw-bold text-center reference-number" data-reference-number="{{ $item['reference_number'] }}">{{ $item['reference_number'] }}</td>
    {{-- Date --}}
    <td class="text-center">
        {{ date('d-m-Y', strtotime($item['reference_date'])) }}
    </td>
    {{-- Show Date --}}
    <td class="text-center fw-bold show-date nav-input date-format @if($partialPaid) bg-secondary bg-opacity-25 text-dark @endif"
        contenteditable="{{ $partialPaid ? 'false' : 'true' }}"
        data-original="{{ date('d-m-Y', strtotime($item['show_date'])) }}"
        data-cell-id="{{ $index++ }}"
        tabindex="0">{{ date('d-m-Y', strtotime($item['show_date'])) }}</td>
    {{-- Pending Amount --}}
    <td class="text-end fw-bold settle-amount nav-input pending-amount {{ $highlightClass }}"
        contenteditable="true"
        data-held-amount="0"
        data-partial-amount="{{ $item['pending_amount'] }}"
        data-original="{{ $item['pending_amount'] }}"
        data-cell-id="{{ $index++ }}"
        tabindex="0">{{ $item['pending_amount'] }}</td>
    {{-- CD --}}
    <td class="fw-bold text-center cd-percent nav-input"
        contenteditable="{{ $isCdReadOnly ? 'false' : 'true' }}"
        data-original="{{ $cdPercent }}"
        data-cell-id="{{ $index++ }}"
        tabindex="0">{{ $cdPercent }}</td>

    {{-- CD Value --}}
    <td class="fw-bold cd-amount nav-input non-selectable text-end"
        contenteditable="false"
        data-new-value="{{ $cdValue }}"
        data-original="{{ $cdValue }}"
        data-cell-id="{{ $index++ }}"
        tabindex="0">{{ number_format($cdValue, 2, '.', '') }}</td>

    {{-- Days --}}
    @php
        $daysDiff = daysDiff($paymentDate, $item['reference_date']);
    @endphp
    <td class="text-center days @if($daysDiff > 10) bg-warning bg-opacity-50 text-dark semi-bold @endif" data-original="{{ $daysDiff }}">
        {{ $daysDiff }}
    </td>
    {{-- Balance --}}
    <td data-original="{{ $netTotal }}" class="text-end"> {{ formatIndianNumber($netTotal) }}</td>
    {{-- Account Name --}}
    <td class="text-start fw-bold account-id {{ $highlightClass }}" data-account-id="{{ $item['account_id'] }}" data-account-name="{{ $accountName }}">{{ Str::limit($accountName,36) }}</td>
    {{-- Account City --}}
    <td class="account-city" data-account-city="{{ $accountCity }}">{{ Str::limit($accountCity,14) }}</td>
    {{-- Rebate --}}
    <td class="text-end fw-bold @if($rebateFromAnalysis) rebate-from-analysis @endif @if($isOverPayment) over-payment-rebate @endif">
        {{ $rebateValue ? abs($rebateValue) : 0 }}
    </td>
    {{-- Destination // selected bold condition wise --}}
    <td class="text-start {{ $highlightClass }}">
        @foreach ($itemDetail as $value)
            @php
                $destinationName = Str::upper($value['destination_name'] ?? '');
            @endphp
            @if ($destinationName == 'PALANPUR' || $destinationName == 'KATARVA')
                <p class="my-0" title="{{ $destinationName }}">
                    {{ Str::limit($destinationName, 14) }}
                </p>
            @else
                <p class="my-0">
                    {{ Str::limit($destinationName, 14) }}
                </p>
            @endif
        @endforeach
    </td>
    
    {{-- Item --}}
    <td class="text-start">
        @foreach ($itemDetail as $value)
        @php
            $itemName = $value['item_name'] ?? '';
        @endphp

        <p class="my-0">
            {{ Str::limit($itemName, 20) }}
        </p>
        
    @endforeach
    </td>
    {{-- Qty --}}
    <td class="text-end" data-items='@json($itemDetail)'>
            @foreach ($itemDetail as $value)
            @php
                $qtyValue = $value['quantity'] ?? 0;
                $qty = $qtyValue ? number_format($qtyValue, 3, '.', '') : '0.000';
            @endphp
    
            <p class="my-0">
                {{ $qty }} 
            </p>
            
        @endforeach
    </td>
    {{-- Rate --}}
    <td class="text-center">
        @foreach ($itemDetail as $value)
            @php
                $rateValue = $value['rate'] ?? 0;
                $rate = $rateValue ? number_format($rateValue, 2, '.', '') : '0.00';
            @endphp
    
            <p class="my-0">
                {{ $rate }} 
            </p>

        @endforeach
    </td>
    {{-- Total Amount --}}
    <td class="text-end total-amount"  data-original="{{ $item['taxable_amount'] }}">
        {{ formatIndianNumber($item['taxable_amount']) }}
    </td>
    {{-- Gross Qty --}}
    <td class="text-end">
        {{ formatIndianNumber($item['total_quantity']) }}
    </td>
    {{-- TDS --}}
    <td class="text-end">
        {{ $tdsValue }}
    </td>
    {{-- Freight --}}
    <td class="text-end">
        {{ $freightValue }}
    </td>
    {{-- SGST --}}
    <td class="text-end">
        {{ $sgstValue }}
    </td>
    {{-- CGST --}}
    <td class="text-end">
        {{ $cgstValue }}
    </td>
    {{-- IGST --}}
    <td class="text-end">
        {{ $igstValue }}
    </td>
    {{-- Premium --}}
    {{-- <td class="text-end">
        {{ $igstValue }}
    </td> --}}
    {{-- Labour --}}
    <td class="text-end">
        {{ $labourValue }}
    </td>
    {{-- Penalty --}}
    <td class="text-end">
        {{ $penaltyValue }}
    </td>
    {{-- Round --}}
    <td class="text-end">
        {{ $roundValue }}
    </td>
    {{-- Other --}}
    <td class="text-end">
        {{ $otherValue }}
    </td>
    {{-- DR/CR --}}
    <td class="text-center">
        {{-- Add Badge for DR/CR if debit then red color badge else green color badge --}}
        @if ($item['direction'] == 'debit')
            <span class="badge fw-bold text-danger">DR</span>
        @else
            <span class="badge fw-bold text-success">CR</span>
        @endif
    </td>
    <td class="text-center">
        {{ $item['source_type'] }}
    </td>
</tr>
@empty

@php
    $firstRow = true;
@endphp

@for ($i = 0; $i <= 10; $i++)
    <tr class="bg-table">
        @for ($k = 0; $k < 29; $k++)
            @if ($firstRow)
                <td class="border-0 text-center fw-bold text-muted" colspan="15">-- No data found --</td>
            @php
                $firstRow = false;
            @endphp
            @else
            <td class="border-0"> &nbsp;</td>
            @endif
        @endfor
    </tr>
@endfor



@endforelse

@if (count($data) > 0 && count($data) <= 11)    
@for ($i = 0; $i <= 9 - count($data); $i++)
    <tr class="bg-table">
        @for ($k = 0; $k < 29; $k++)
            <td class="border-0"> &nbsp;</td>
        @endfor
    </tr>
    @endfor
@endif