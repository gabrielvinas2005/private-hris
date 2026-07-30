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

    <title>Mid Year Bonus Report</title>

    <style>
        html,
        body {
            height: 297mm;
            width: auto;
            margin-top: -15px;
            margin-bottom: 0px;
        }

        .header {
            align-items: center;
            margin-top: 50px;
            margin-left: 100px;
            margin-right: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 20px;
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }

        table th {
            background-color: #f2f2f2;
        }

        .table-title {
            text-align: start;
            margin-top: 10px;
            font-size: 10px;
            margin-bottom: -10px;
        }
    </style>
</head>

<body>
    <!-- Main content -->
    <div class="header" style="text-align:center;">
        <h3 style="margin-top: -10px;">PAYROLL</h3>
        <p style="margin: 0;">
            WE HEREBY ACKNOWLEDGE to have received of the {{ $orgCompanyName }} the sums therein specified our
            respective names as payment of MID-YEAR BONUS for CY {{ $month ?? 'N/A' }}.
        </p>
        <p style="margin-top: 6px; font-weight:bold;">{{ strtoupper($department ?? 'ALL DEPARTMENTS') }}</p>
    </div>

    @php
        $currentDepartment = null;
        $deptSalaryTotal = 0;
        $deptBonusTotal = 0;
        $overallSalary = 0;
        $overallBonus = 0;

        $acronym = function ($text) {
            if (!$text) {
                return '';
            }

            $stopWords = ['OF', 'THE', 'AND', 'IN'];
            $words = preg_split('/\s+/', trim($text));

            $letters = collect($words)
                ->map(fn ($w) => strtoupper($w))
                ->reject(fn ($w) => in_array($w, $stopWords))
                ->map(fn ($w) => $w[0] ?? '')
                ->filter()
                ->join('');

            return $letters ?: strtoupper(substr(trim($text), 0, 1));
        };
    @endphp

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">#</th>
                <th style="width: 80px;">DIV.</th>
                <th style="width: 180px;">NAME</th>
                <th style="width: 200px;">Position Title</th>
                <th>BASIC SALARY</th>
                <th>MID YEAR BONUS</th>
                <th style="width: 120px;">Signature</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($employees as $index => $emp)
                @php
                    if ($currentDepartment !== null && $currentDepartment !== $emp->department) {
                        echo '<tr style="font-weight:bold;">
                                <td colspan="4" style="text-align:right;">SUB-TOTAL</td>
                                <td>' . number_format($deptSalaryTotal, 2) . '</td>
                                <td>' . number_format($deptBonusTotal, 2) . '</td>
                                <td></td>
                              </tr>';
                        $deptSalaryTotal = 0;
                        $deptBonusTotal = 0;
                    }
                    $currentDepartment = $emp->department;
                    $deptSalaryTotal += $emp->salary ?? 0;
                    $deptBonusTotal += $emp->amount ?? 0;
                    $overallSalary += $emp->salary ?? 0;
                    $overallBonus += $emp->amount ?? 0;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $acronym($emp->department ?? '') }}</td>
                    <td>{{ strtoupper($emp->name ?? '') }}</td>
                    <td>{{ $emp->position ?? '' }}</td>
                    <td>{{ number_format($emp->salary ?? 0, 2) }}</td>
                    <td>{{ number_format($emp->amount ?? 0, 2) }}</td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            @if ($currentDepartment !== null)
                <tr style="font-weight:bold;">
                    <td colspan="4" style="text-align:right;">SUB-TOTAL</td>
                    <td>{{ number_format($deptSalaryTotal, 2) }}</td>
                    <td>{{ number_format($deptBonusTotal, 2) }}</td>
                    <td></td>
                </tr>
            @endif
            <tr style="font-weight:bold;">
                <td colspan="4" style="text-align:right;">GRAND TOTAL</td>
                <td>{{ number_format($overallSalary, 2) }}</td>
                <td>{{ number_format($overallBonus, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <table style="width: 100%; margin-top: 20px; border: none;">
        <tbody>
            <tr>
                <td style="width: 50%; text-align: left; border: none; padding-right: 30px;">
                    <p style="margin: 0;">
                        I CERTIFY on my official oath that the above Payroll<br>
                        is correct and that the services have been duly<br>
                        rendered as stated.
                    </p>
                    <br><br>
                    <p style="margin: 0; font-weight: bold; text-transform: uppercase;">
                        {{ $signatories[0]['signatory_1'] ?? '' }}
                    </p>
                    <p style="margin-top: -3px;">
                        {{ $signatories[0]['signatory_position_1'] ?? '' }}
                    </p>
                </td>
                <td style="width: 50%; text-align: left; border: none; padding-left: 30px;">
                    <p style="margin: 0;">
                        APPROVED, payable for appropriation for
                    </p>
                    <p style="margin: 0 0 10px;">Php ________________________.</p>
                    <br><br>
                    <p style="margin: 0; font-weight: bold; text-transform: uppercase;">
                        {{ $signatories[0]['signatory_2'] ?? '' }}
                    </p>
                    <p style="margin-top: -3px;">
                        {{ $signatories[0]['signatory_position_2'] ?? '' }}
                    </p>
                </td>
            </tr>
        </tbody>
    </table>

</body>

</html>
