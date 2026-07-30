@foreach ($employees as $employee)
    <tr>
        <td hidden><input type="number" value="{{ $employee->id }}" name="employee_id[]"></td>
        <td>{{ $employee->employee_no }}</td>
        <td>{{ $employee->name }}</td>
        <td>{{ $employee->position }}</td>
        <td>{{ $employee->prepaid_invoice_no }}</td>

    </tr>
@endforeach
