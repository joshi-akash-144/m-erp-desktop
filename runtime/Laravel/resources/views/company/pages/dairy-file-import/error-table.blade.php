<div class="table-responsive">
    <table class="table table-sm table-bordered table-hover align-middle mb-0" style="font-size:0.82rem;">
        <thead class="table-danger text-center">
            <tr>
                <th>Row</th>
                <th>Billing Date</th>
                <th>Customer P.O. No</th>
                <th>Sold to Party</th>
                <th>Name of Sold to Party</th>
                <th>Qty</th>
                <th>Vehicle No</th>
                <th>Material Description</th>
                <th>Transporter Name</th>
                <th>Transport Zone</th>
                <th>Error</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($errorRows as $row)
                <tr>
                    <td class="text-center fw-bold text-danger">{{ $row['row'] }}</td>
                    <td>{{ $row['billing_date'] }}</td>
                    <td>{{ $row['customer_po_no'] }}</td>
                    <td>{{ $row['sold_to_party'] }}</td>
                    <td>{{ $row['name_of_sold_to_party'] }}</td>
                    <td class="text-end">{{ $row['material_quantity'] }}</td>
                    <td>{{ $row['vehicle_number'] }}</td>
                    <td>{{ $row['material_description'] }}</td>
                    <td>{{ $row['transporter_name'] }}</td>
                    <td>{{ $row['transport_zone'] }}</td>
                    <td class="text-danger fw-semibold">{{ $row['errors'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
