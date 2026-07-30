<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RATA General Payroll</title>

    <style>
        html,
        body {
            margin: 0.45in 0.5in;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: 10px;
        }

        p {
            margin: 0;
            padding: 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid black;
            padding: 4px 6px;
            vertical-align: middle;
        }

        .bold {
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .ordered-list {
            margin: 5px 0 5px 20px;
            padding: 0;
        }

        .ordered-list li {
            margin-bottom: 2px;
        }

        .sig-name {
            font-weight: bold;
            margin-top: 35px;
            display: block;
        }

        .sig-title {
            display: block;
        }
    </style>
</head>

<body>

    {{-- ===== PAGE TITLE ===== --}}
    <table style="margin-bottom: 0;">
        <tr style="border-top: none; border-bottom: none; border-left: none; border-right: none;">
            <td class="center bold" style="font-size: 12px; padding: 6px; border-top: none; border-bottom: none; border-left: none; border-right: none;">
                GENERAL PAYROLL
            </td>
        </tr>
        <tr style="border-top: none; border-bottom: none; border-left: none; border-right: none;">
            <td style="padding: 8px 6px; border-top: none; border-bottom: none; border-left: none; border-right: none;">
                <p>
                    WE HEREBY ACKNOWLEDGE to have received from the {{ $orgCompanyName }} the sum herein
                    specified opposite our respective names, for the period
                    {{ $period ?: 'the payroll period covered by this report' }} except as noted otherwise in the
                    Remarks column:
                </p>
            </td>
        </tr>
    </table>

    {{-- ===== MAIN PAYROLL TABLE ===== --}}
    <table style="margin-top: 8px;">
        <colgroup>
            <col style="width: 6%;" />
            <col style="width: 34%;" />
            <col style="width: 14%;" />
            <col style="width: 14%;" />
            <col style="width: 12%;" />
            <col style="width: 20%;" />
        </colgroup>
        <thead>
            <tr>
                <th class="center"></th>
                <th class="center bold">NAME</th>
                <th class="center bold">REPRESENTATION<br>ALLOWANCE</th>
                <th class="center bold">TRANSPORTATION<br>ALLOWANCE</th>
                <th class="center bold">TOTAL<br>AMOUNT</th>
                <th class="center bold">SIGNATURES</th>
            </tr>
        </thead>
        <tbody>
            @php
                $__counter = 0;
                $__totalRA = 0;
                $__totalTA = 0;
                $__totalAmount = 0;
            @endphp
            @foreach (($rata ?? []) as $employee)
                @php
                    $__ra = (float) ($employee->ra_amount ?? 0);
                    $__ta = (float) ($employee->ta_amount ?? 0);
                    $__rowTotal = $__ra + $__ta;
                    $__counter++;
                    $__totalRA += $__ra;
                    $__totalTA += $__ta;
                    $__totalAmount += $__rowTotal;
                @endphp
                <tr>
                    <td class="center" style="vertical-align: top; padding-top: 5px;">{{ $__counter }}.</td>
                    <td style="vertical-align: top;">
                        <span class="bold">{{ strtoupper($employee->name ?? '') }}</span><br>
                        {{ $employee->position ?? '' }}
                    </td>
                    <td class="right">{{ $__ra > 0 ? number_format($__ra, 2) : '-' }}</td>
                    <td class="right">{{ $__ta > 0 ? number_format($__ta, 2) : '-' }}</td>
                    <td class="right">{{ number_format($__rowTotal, 2) }}</td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="bold" style="padding: 4px 6px;">Total</td>
                <td class="right bold">{{ number_format($__totalRA ?? 0, 2) }}</td>
                <td class="right bold">{{ number_format($__totalTA ?? 0, 2) }}</td>
                <td class="right bold">{{ number_format($__totalAmount ?? 0, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    {{-- ===== CERTIFICATION TEXT ===== --}}
    <table style="margin-top: 8px;">
        <tr style="border-top: none; border-bottom: none; border-left: none; border-right: none;">
            <td style="padding: 8px 6px; border-top: none; border-bottom: none; border-left: none; border-right: none;">
                <p>
                    We hereby certify under our official oath that the amount corresponding to our Representation and
                    Transportation Allowances are advances for bonafide representation expenses incurred in the
                    performance of our official duties.
                </p>
            </td>
        </tr>
        <tr style="border-top: none; border-bottom: none; border-left: none; border-right: none;">
            <td style="padding: 8px 6px; border-top: none; border-bottom: none; border-left: none; border-right: none;">
                <p>We certify further, that the amount indicated on the above will be fully spent for the following:</p>
                <ol class="ordered-list">
                    <li>That the said amount is advances for expenses incurred in the performance of our duties;</li>
                    <li>That we will not use any government vehicle for the said month;</li>
                    <li>That the amount corresponding to the number of times we use government vehicles will be deducted
                        from our allowances.</li>
                </ol>
            </td>
        </tr>
    </table>

    {{-- ===== SIGNATORIES ===== --}}
    <table style="margin-top: 8px; table-layout: fixed; border-top: none; border-bottom: none; border-left: none; border-right: none;">
        <colgroup>
            <col style="width: 50%;" />
            <col style="width: 50%;" />
        </colgroup>

        {{-- Row 1 --}}
        <tr style="border-top: none; border-bottom: none; border-left: none; border-right: none;">
            <td style="vertical-align: top; padding: 8px 10px; border-top: none; border-bottom: none; border-left: none; border-right: none;">
                <p>I certify on my offical oath that the above payroll is correct &amp; that the services have been duly
                    rendered as stated.</p>
                <span class="sig-name">{{ $signatories['signatory_1'] ?? 'EDUARDO A. PUYAOAN, JR.' }}</span>
                <span class="sig-title">{{ $signatories['signatory_position_1'] ?? 'Chief Administrative Officer' }}</span>
            </td>
            <td style="vertical-align: top; padding: 8px 10px; border-top: none; border-bottom: none; border-left: none; border-right: none;">
                <p>I certify on my official oath that I have paid to employees whose name appears on the above payroll
                    the amount set opposite his/her name.</p>
                <span class="sig-name">{{ $signatories['signatory_2'] ?? 'MARK ANGELO C. VERDAD' }}</span>
                <span class="sig-title">{{ $signatories['signatory_position_2'] ?? 'Administrative Assistant I (Disbursing Officer II)' }}</span>
            </td>
        </tr>

        {{-- Row 2 --}}
        <tr style="border-top: none; border-bottom: none; border-left: none; border-right: none;">
            <td style="vertical-align: top; padding: 8px 10px; border-top: none; border-bottom: none; border-left: none; border-right: none;">
                <p>approved, payable for appropriation for the amount of Php ___________________</p>
                <span class="sig-name">{{ $signatories['signatory_3'] ?? 'CLARE MARI S. TORRALBA' }}</span>
                <span class="sig-title">{{ $signatories['signatory_position_3'] ?? 'Executive Director' }}</span>
            </td>
            <td style="vertical-align: top; padding: 8px 10px; border-top: none; border-bottom: none; border-left: none; border-right: none;">
                <p>I certify on my official oath that I have witnessed payment to each person whose name appears hereon.</p>
                <span class="sig-name">{{ $signatories['signatory_4'] ?? 'CELESTINA M. CATIBOG' }}</span>
                <span class="sig-title">{{ $signatories['signatory_position_4'] ?? 'Cashier II' }}</span>
            </td>
        </tr>
    </table>

</body>

</html>