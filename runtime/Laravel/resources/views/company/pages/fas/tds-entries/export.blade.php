@php
    use Illuminate\Support\Str;
    use Carbon\Carbon;
@endphp
<table>
    <tr>
        <td colspan="{{ count($headings) }}">{{ Str::upper($companyName) }}</td>
    </tr>
    <tr>
        <td colspan="{{ count($headings) }}">{{ $reportTitle }}</td>
    </tr>
    <tr></tr>
    <tr>
        <td colspan="10">
            @if(!empty($filters['tds_category_id']))
                @php
                    $cat = \App\Models\TdsCategory::find($filters['tds_category_id']);
                @endphp
                TDS Category: {{ $cat ? $cat->category_name . ' : Section ' . $cat->section : 'All Categories' }}
            @else
                TDS Category: All Categories
            @endif
        </td>
        <td colspan="{{ count($headings) - 10 }}" style="text-align: right;">
            From: {{ !empty($filters['from_date']) ? $filters['from_date'] : 'N/A' }} 
            To: {{ !empty($filters['to_date']) ? $filters['to_date'] : 'N/A' }}
        </td>
    </tr>
    <tr>
        <td colspan="10"></td>
        <td colspan="{{ count($headings) - 10 }}" style="text-align: right;">
            Status as on: {{ !empty($filters['to_date']) ? $filters['to_date'] : now()->format('d-m-Y') }}
        </td>
    </tr>
    <tr>
        @foreach($headings as $heading)
            <th>{{ $heading }}</th>
        @endforeach
    </tr>
    @foreach($rows as $row)
        <tr>
            @foreach($row as $cell)
                <td>{{ $cell }}</td>
            @endforeach
        </tr>
    @endforeach
    @if(count($rows) > 0)
<tr>
        <th></th>
<th>Total</th>
<th>{{ number_format($totalPaymentAmount, 2, '.', '') }}</th>
        <th></th>
<th></th>
<th>{{ number_format($totalTdsAmount, 2, '.', '') }}</th>
<th></th>
<th>0.00</th>
<th></th>
<th>0.00</th>
<th></th>
<th>0.00</th>
<th>{{ number_format($totalTdsAmount, 2, '.', '') }}</th>
<th></th>
<th>0.00</th>
<th colspan="4"></th>
    </tr>
    @endif
</table>
