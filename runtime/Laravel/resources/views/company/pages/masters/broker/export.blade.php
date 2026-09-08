@php
    use Illuminate\Support\Str;
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
    <tr>
        @foreach($headings as $heading)
            <td>{{ $heading }}</td>
        @endforeach
    </tr>
    @foreach($rows as $row)
        <tr>
            @foreach($row as $cell)
                <td>{{ $cell }}</td>
            @endforeach
        </tr>
    @endforeach
</table>