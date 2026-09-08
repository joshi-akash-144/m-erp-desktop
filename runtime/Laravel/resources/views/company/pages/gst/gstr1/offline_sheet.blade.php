<table>
    <thead>
        <tr>
            <th colspan="{{ count($headers) }}" style="font-weight: bold;">{{ $summaryTitle }}</th>
        </tr>
        <tr>
            @foreach($summaryHeaders as $key => $title)
                <th style="font-weight: bold;">{{ $title }}</th>
            @endforeach
        </tr>
        <tr>
            @foreach($summaryHeaders as $key => $title)
                <th>{{ $summaryValues[$key] ?? ' ' }}</th>
            @endforeach
        </tr>
        <tr>
            @foreach($headers as $header)
                <th style="font-weight: bold;">{{ $header }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
            <tr>
                @foreach($row as $cell)
                    <td>{{ $cell }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
