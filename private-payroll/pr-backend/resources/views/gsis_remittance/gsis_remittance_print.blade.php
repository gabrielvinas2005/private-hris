<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">

    <title>GSIS Remittance Report</title>

    <style>
        html,
        body {
            margin-left: auto;
            margin-right: auto;
            font-size: 14px;
            font-family: 'Arial', sans-serif;
        }

        p {
            padding: 0;
            margin: 2px;
        }

        .bold-text {
            font-weight: bold;
        }

        table {
            width: 100%;
            border: 1px solid black;
            border-collapse: collapse;
            margin-bottom: 20px;
            margin-top: 20px;
        }

        th {
            text-align: left;
        }

        th,
        td {
            border: 1px solid black;
            padding: 5px;
        }

        .amount {
            text-align: right;
        }
    </style>
</head>

<body>
    <div>
        <div style="margin: 0px 30px 0px 30px;">
            <div>
                <p>Remitting Agency: {{ strtoupper($companies[0]->name) }}</p>
                <p>Office Code: <span>{{ $gsis_remittances[0]->department_code ?? '' }}</span></p>
                <p>Due Month: <span>{{ date('m/Y', strtotime($gsis_remittances[0]->release_date ?? '')) }}</span></p>
            </div>
            <div>
                <table>
                    <thead>
                        <tr>
                            <th>BPNO</th>
                            <th>LastName</th>
                            <th>FirstName</th>
                            <th>MI</th>
                            <th>PREFIX</th>
                            <th>APPELATION</th>
                            <th>BirthDate</th>
                            <th>CRN</th>
                            <th>Basic Monthly Salary</th>
                            <th>Effectivity Date</th>
                            <th>PS</th>
                            <th>GS</th>
                            <th>EC</th>
                            @foreach ($gsis_loan_headers as $gsis_loan)
                                <th>{{ $gsis_loan->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php($gsis_amount = 0)
                        @foreach ($gsis_remittances as $gsis_remittance)
                            <tr>
                                <td>{{ $gsis_remittance->gsis_no }}</td>
                                <td>{{ $gsis_remittance->last_name }}</td>
                                <td>{{ $gsis_remittance->first_name }}</td>
                                <td>{{ $gsis_remittance->middle_name }}</td>
                                <td>{{ $gsis_remittance->name_prefix }}</td>
                                <td>{{ $gsis_remittance->name_suffix }}</td>
                                <td>{{ $gsis_remittance->birthdate }}</td>
                                <td>{{ $gsis_remittance->crn_no ?? 'NO CRN' }}</td>
                                <td class="amount">{{ number_format($gsis_remittance->salary, 2, '.', ',') }}</td>
                                <td></td>
                                <td class="amount">{{ number_format($gsis_remittance->gsis, 2, '.', ',') }}</td>
                                <td class="amount">{{ number_format($gsis_remittance->gsis, 2, '.', ',') }}</td>
                                <td class="amount">{{ number_format($gsis_remittance->gsis, 2, '.', ',') }}</td>
                                @foreach ($gsis_loan_headers as $headers)
                                    <td class="amount">
                                        @foreach ($gsis_loans as $gsis_loan)
                                            @if ($headers->deduction_id == $gsis_loan->deduction_id)
                                                @if ($gsis_remittance->employee_id == $gsis_loan->employee_id)
                                                    {{ number_format($gsis_loan->amount, 2, '.', ',') }}
                                                @endif
                                            @endif
                                        @endforeach
                                    </td>
                                @endforeach
                            </tr>
                            {{ $gsis_amount += $gsis_remittance->gsis }}
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="10" style="text-align: right">TOTAL</td>
                            <td class="amount">{{ number_format($gsis_amount, 2, '.', ',') }}</td>
                            <td class="amount">{{ number_format($gsis_amount, 2, '.', ',') }}</td>
                            <td class="amount">{{ number_format($gsis_amount, 2, '.', ',') }}</td>
                            @foreach ($gsis_loan_headers as $gsis_loan)
                                <td class="amount">
                                    @foreach ($gsis_loan_totals as $gsis_loan_total)
                                        @if ($gsis_loan->deduction_id == $gsis_loan_total->deduction_id)
                                            {{ number_format($gsis_loan_total->amount, 2, '.', ',') }}
                                        @endif
                                    @endforeach
                                </td>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div style="margin-top: 35px;width: 35%;">
                <p>CERTIFIED CORRECT:</p>
                <div style="margin-top: 40px;text-align: center;">
                    <p class="bold-text">
                        {{ strtoupper($signatories['name'] ?? '') }}</p>
                    <p>{{ $signatories['position'] ?? '' }}</p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
