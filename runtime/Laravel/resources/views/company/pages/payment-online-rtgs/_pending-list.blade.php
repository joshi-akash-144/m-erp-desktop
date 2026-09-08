
@foreach ($records as $record)
<tr>
    <td class="text-center"><input type="checkbox" class="form-check-input mt-0 rtgs-row-chk"
                                  data-id="{{ $record['payment_voucher_id'] }}"
                                  data-amount="{{ $record['paid_amount'] ?? 0 }}"
                                  data-ref="{{ strtolower($record['voucher_serial'] ?? '') }}"></td>
    <td class="text-center">{{ $record['sr_no'] ?? "" }}</td>
    <td class="text-center">{{ $record['voucher_serial'] ?? "" }}</td>
    <td class="text-center">{{ $record['file_number'] ?? "" }}</td>
    <td class="fw-semibold">{{ $record['payee_name'] ?? "" }}</td>
    <td>{{ $record['city'] ?? "" }}</td>
    <td class="text-end fw-semibold" data-amount="{{ $record['paid_amount'] ?? 0 }}">{{ formatIndianNumber($record['paid_amount'] ?? 0) }}</td>
    <td class="text-center chq-number-td">{{ $record['cheque_no'] ?? "" }}</td>
    <td class="text-center">{{ $record['payment_date'] ? format_date($record['payment_date']) : "" }}</td>
    <td>{{ $record['bank_account_number'] ?? "" }}</td>
    <td>{{ $record['bank_name'] ?? "" }}</td>
    <td>{{ $record['bank_ifsc'] ?? "no-data" }}</td>
    <td>{{ $record['bank_branch_name'] ?? "" }}</td>
    <td>{{ $record['email'] ?? "" }}</td>
    <td>{{ $record['mobile_number'] ?? "" }}</td>
</tr>
@endforeach