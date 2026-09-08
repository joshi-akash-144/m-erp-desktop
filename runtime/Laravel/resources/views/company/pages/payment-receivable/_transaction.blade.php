@php $colCount = 12; @endphp

@forelse ($data as $item)
@php
    $itemDetail  = $item['items'] ?? [];
    $index       = 1;
    $daysDiff    = daysDiff($paymentDate, $item['reference_date']);
@endphp

<tr id="row_{{ $loop->iteration }}"
    data-id="{{ $item['id'] }}"
    data-row="{{ $loop->iteration }}"
    data-source-type="{{ $item['source_type'] }}"
    data-source-id="{{ $item['source_id'] }}"
    data-reference-id="{{ $item['id'] }}"
    data-account-id="{{ $item['account_id'] }}"
    data-voucher-id="{{ $item['voucher_id'] }}"
    data-direction="{{ $item['direction'] }}"
>
    {{-- Yes / No --}}
    <td class="text-center not-selectable-cell">
        <input class="form-check-input border border-1 border-dark-subtle row-checkbox non-selectable"
               type="checkbox" value="{{ $item['id'] }}"
               data-ref="{{ $item['reference_number'] }}">
    </td>

    {{-- Sr No --}}
    <td class="text-center">0</td>

    {{-- Bill No --}}
    <td class="fw-bold text-center reference-number"
        data-reference-number="{{ $item['reference_number'] }}">
        {{ $item['reference_number'] }}
    </td>

    {{-- Date --}}
    <td class="text-center">
        {{ date('d-m-Y', strtotime($item['reference_date'])) }}
    </td>

    {{-- Bill Amount --}}
    <td class="text-end fw-bold">
        {{ formatIndianNumber($item['amount']) }}
    </td>

    {{-- Receive-Amount (editable) --}}
    <td class="text-end">
        <input class="form-control table-input fw-bold text-end receive-amount nav-input"
               type="text" tabindex="0"
               data-original="{{ $item['pending_amount'] }}"
               data-cell-id="{{ $index++ }}"
               value="{{ $item['pending_amount'] }}">
    </td>

    {{-- DrCr --}}
    <td class="text-center">
        @if ($item['direction'] == 'debit')
            <span class="badge fw-bold text-danger">DR</span>
        @else
            <span class="badge fw-bold text-success">CR</span>
        @endif
    </td>

    {{-- Days --}}
    <td class="text-center days
               @if($daysDiff > 10) bg-warning bg-opacity-50 text-dark fw-semibold @endif"
        data-original="{{ $daysDiff }}">
        {{ $daysDiff }}
    </td>

    {{-- Qty --}}
    <td class="text-end">
        {{ number_format($item['total_quantity'], 3) }}
    </td>

    {{-- Rate --}}
    <td class="text-center">
        @foreach ($itemDetail as $value)
            <p class="my-0">{{ number_format($value['rate'] ?? 0, 2, '.', '') }}</p>
        @endforeach
    </td>

    {{-- GRN --}}
    <td class="text-start">
        <p class="my-0">{{ $item['grn_number'] }}</p>
        
    </td>

    {{-- Product (item_name) --}}
    <td class="text-start">
        @foreach ($itemDetail as $value)
            <p class="my-0">{{ Str::limit($value['item_name'] ?? '', 20) }}</p>
        @endforeach
    </td>
</tr>

@empty

@php $firstRow = true; @endphp
@for ($i = 0; $i <= 10; $i++)
    <tr class="bg-table">
        @for ($k = 0; $k < $colCount; $k++)
            @if ($firstRow && $k === 0)
                <td class="border-0 text-center fw-bold text-muted" colspan="{{ $colCount }}">-- No data found --</td>
                @php $firstRow = false; @endphp
            @elseif (!$firstRow || $k > 0)
                <td class="border-0">&nbsp;</td>
            @endif
        @endfor
    </tr>
@endfor

@endforelse

{{-- Padding rows when fewer than 10 results --}}
@if (count($data) > 0 && count($data) <= 10)
    @for ($i = 0; $i <= 9 - count($data); $i++)
        <tr class="bg-table">
            @for ($k = 0; $k < $colCount; $k++)
                <td class="border-0">&nbsp;</td>
            @endfor
        </tr>
    @endfor
@endif