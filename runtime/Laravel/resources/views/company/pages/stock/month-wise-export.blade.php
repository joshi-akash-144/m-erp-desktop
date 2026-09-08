@php
    use Illuminate\Support\Str;
    use Carbon\Carbon;
@endphp
<table>
    <tr>
        <td colspan="{{ count($headings) }}">{{ Str::upper($companyName) }}</td>
    </tr>
    <tr>
        <td colspan="{{ count($headings) }}">GSTIN : {{ $gstNumber }}</td>
    </tr>
    <tr>
        <td colspan="{{ count($headings) }}">{{ $reportTitle }}</td>
    </tr>
    @if(!empty($itemName))
    <tr>
        <td colspan="{{ count($headings) }}">Item: {{ $itemName }}</td>
    </tr>
    @endif
    @if(!empty($dateLabel))
    <tr>
        <td colspan="{{ count($headings) }}">{{ $dateLabel }}</td>
    </tr>
    @endif
    <tr>
        <td colspan="{{ count($headings) }}" style="text-align: right;">
            <strong>Opening Stock: {{ $openingStock ?? 0 }} @isset($openingAmount) | Opening Amount: {{ $openingAmount }} @endisset</strong>
        </td>
    </tr>
    <tr>
        @foreach($headings as $heading)
            <td><strong>{{ $heading }}</strong></td>
        @endforeach
    </tr>
    @foreach($rows as $row)
        <tr>
            @foreach($row as $cell)
                <td>{{ $cell }}</td>
            @endforeach
        </tr>
    @endforeach
    <tr>
        <td colspan="{{ count($headings) }}" style="text-align: right;">
            <strong>Closing Stock: {{ $closingStock ?? 0 }} @isset($closingAmount) | Closing Amount: {{ $closingAmount }} @endisset</strong>
        </td>
    </tr>
</table>