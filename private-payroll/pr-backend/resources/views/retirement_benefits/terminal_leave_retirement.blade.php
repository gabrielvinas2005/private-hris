<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BP FORM 205 - List of Retirees</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }

        p {
            padding: 0;
            margin: 0;
            font-size: 10px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
        }

        td, th {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: top;
            font-size: 10px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* Ensure rightmost cells have right border */
        .main-data-table td:last-child,
        .main-data-table th:last-child {
            border-right: 2px solid #000 !important;
        }

        .main-data-table {
            border-right: 2px solid #000 !important;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .checkbox {
            width: 14px;
            height: 14px;
            border: 1px solid #000;
            display: inline-block;
            margin-right: 5px;
            vertical-align: middle;
        }

        .section-group {
            page-break-inside: avoid;
        }

        .signature-section {
            page-break-inside: avoid;
        }

        @media print {
            @page {
                size: legal portrait;
                margin: 0.5in;
            }

            body {
                margin: 0;
                padding: 0;
            }

            .section-group {
                page-break-inside: avoid;
            }

            .signature-section {
                page-break-inside: avoid;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-footer-group;
            }

            /* Ensure rightmost cells have right border in print */
            .main-data-table td:last-child,
            .main-data-table th:last-child {
                border-right: 2px solid #000 !important;
            }

            .main-data-table {
                border-right: 2px solid #000 !important;
            }
        }
    </style>
</head>

<body>
    <div style="padding: 0.5in;">
        <!-- Header Section with BP FORM 205 aligned to table -->
        <table style="border: none; margin-bottom: 5px; width: 100%; table-layout: fixed;">
            <tr>
                <td style="border: none; text-align: right; font-size: 11px; font-weight: bold; padding: 5px 0;">
                    BP FORM 205
                </td>
            </tr>
        </table>

        <!-- Title Section -->
        <table style="border: none; margin-bottom: 10px; width: 100%; table-layout: fixed;">
            <tr>
                <td style="border: none; text-align: center;">
                    <p style="font-size: 12px; font-weight: bold; margin-bottom: 3px;">LIST OF RETIREES</p>
                    <p style="font-size: 11px; font-weight: bold; margin-bottom: 3px;">FOR PAYMENT OF TERMINAL LEAVE AND RETIREMENT GRATUITY BENEFITS</p>
                    <p style="font-size: 11px; font-weight: bold;">FY {{ $fiscal_year }}</p>
                </td>
            </tr>
        </table>

        <!-- Department and Agency Info -->
        <table style="border: none; margin-bottom: 10px; width: 100%; table-layout: fixed;">
            <colgroup>
                <col style="width: 60%;">
                <col style="width: 40%;">
            </colgroup>
            <tr>
                <td style="border: none; font-size: 11px;">
                    <strong>DEPARTMENT:</strong> {{ $department }}
                </td>
                <td style="border: none; text-align: right; font-size: 11px; padding-right: 0;">
                    <span class="checkbox"></span> Mandatory
                    <span style="margin-left: 30px;"><span class="checkbox"></span> Optional</span>
                </td>
            </tr>
            <tr>
                <td style="border: none; font-size: 11px;">
                    <strong>AGENCY:</strong> {{ $agency }}
                </td>
                <td style="border: none;">&nbsp;</td>
            </tr>
        </table>

        <!-- Main Data Table -->
        <table class="main-data-table" style="table-layout: fixed;">

        <thead>
            <tr>
                <th rowspan="3" style="width: 20%; text-align: center; vertical-align: middle; font-size: 10px; font-weight: bold;">NAME OF RETIREES AND RETIREMENT LAW</th>
                <th rowspan="3" style="text-align: center; vertical-align: middle; font-size: 10px; font-weight: bold;">Position at<br>Retirement<br>(Item No.)</th>
                <th colspan="3" style="text-align: center; font-size: 10px; font-weight: bold;">Date (Mo/Day/Year)</th>
                <th rowspan="3" style="text-align: center; vertical-align: middle; font-size: 10px; font-weight: bold;">Highest<br>Monthly<br>Salary<br>Per NOSAA</th>
                <th colspan="3" style="text-align: center; font-size: 10px; font-weight: bold;">TERMINAL LEAVE</th>
                <th colspan="3" style="text-align: center; font-size: 10px; font-weight: bold;">RETIREMENT GRATUITY</th>
            </tr>
            <tr>
                <th rowspan="2" style="text-align: center; vertical-align: middle; font-size: 10px; font-weight: bold;">Birth</th>
                <th rowspan="2" style="text-align: center; vertical-align: middle; font-size: 10px; font-weight: bold;">Original<br>Appointment</th>
                <th rowspan="2" style="text-align: center; vertical-align: middle; font-size: 10px; font-weight: bold;">Retirement</th>
                <th colspan="2" style="text-align: center; font-size: 10px; font-weight: bold;">No. of Leave<br>Credits Earned</th>
                <th rowspan="2" style="text-align: center; vertical-align: middle; font-size: 10px; font-weight: bold;">Amount</th>
                <th rowspan="2" style="text-align: center; vertical-align: middle; font-size: 10px; font-weight: bold;">Total<br>Creditable<br>Service</th>
                <th rowspan="2" style="text-align: center; vertical-align: middle; font-size: 10px; font-weight: bold;">No of<br>Gratuity<br>Amounts<br>(YR)</th>
                <th rowspan="2" style="text-align: center; vertical-align: middle; font-size: 10px; font-weight: bold;">Amount</th>
            </tr>
            <tr>
                <th style="text-align: center; vertical-align: middle; font-size: 10px; font-weight: bold;">VL</th>
                <th style="text-align: center; vertical-align: middle; font-size: 10px; font-weight: bold;">SL</th>
            </tr>
            <tr style="font-size: 9px; text-align: center;">
                <td>(1)</td>
                <td>(2)</td>
                <td>(3)</td>
                <td>(4)</td>
                <td>(5)</td>
                <td>(6)</td>
                <td>(7)</td>
                <td>(8)</td>
                <td>(9)</td>
                <td>(10)</td>
                <td>(11)</td>
                <td>(12)</td>
            </tr>
        </thead>

            <!-- Section: For GSIS Members -->
            <tbody class="section-group">
            <tr>
                <td style="font-size: 10px; vertical-align: top; padding: 5px;">
                    <p style="font-weight: bold; margin-bottom: 5px;">For GSIS Members :</p>
                    <p style="margin-bottom: 3px;">I. Under RA NO. 1616</p>
                    <p>II. Other Retirement Laws (pls. specify, e.g. RA 8291)</p>
                </td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>

            @foreach($gsisMembers as $retiree)
                @php
                    $birthDate = $retiree->birth_date ? \Carbon\Carbon::parse($retiree->birth_date)->format('m/d/Y') : '';
                    $originalAppointment = $retiree->original_appointment_date ? \Carbon\Carbon::parse($retiree->original_appointment_date)->format('m/d/Y') : '';
                    $retirementDate = $retiree->retirement_date ? \Carbon\Carbon::parse($retiree->retirement_date)->format('m/d/Y') : '';
                @endphp
                <tr>
                    <td style="font-size: 9px; padding: 3px;">
                        {{ $retiree->name }}<br>
                        <span style="font-size: 8px;">{{ $retiree->retirement_law }}</span>
                    </td>
                    <td style="font-size: 9px; padding: 3px; text-align: center;">{{ $retiree->position }}</td>
                    <td style="font-size: 9px; padding: 3px; text-align: center;">{{ $birthDate }}</td>
                    <td style="font-size: 9px; padding: 3px; text-align: center;">{{ $originalAppointment }}</td>
                    <td style="font-size: 9px; padding: 3px; text-align: center;">{{ $retirementDate }}</td>
                    <td style="font-size: 9px; padding: 3px; text-align: right;">{{ number_format($retiree->highest_monthly_salary, 2) }}</td>
                    <td style="font-size: 9px; padding: 3px; text-align: center;">{{ number_format($retiree->vl_credits, 2) }}</td>
                    <td style="font-size: 9px; padding: 3px; text-align: center;">{{ number_format($retiree->sl_credits, 2) }}</td>
                    <td style="font-size: 9px; padding: 3px; text-align: right;">{{ number_format($retiree->terminal_leave_amount, 2) }}</td>
                    <td style="font-size: 9px; padding: 3px; text-align: center;">{{ number_format($retiree->creditable_service, 2) }}</td>
                    <td style="font-size: 9px; padding: 3px; text-align: right;">{{ number_format($retiree->gratuity_amount, 2) }}</td>
                    <td style="font-size: 9px; padding: 3px; text-align: right;">{{ number_format($retiree->retirement_gratuity_amount, 2) }}</td>
                </tr>
            @endforeach

            <tr>
                <td style="font-size: 10px; vertical-align: top; padding: 5px;">
                    <p style="font-weight: bold; margin-bottom: 5px;">Sub Total:</p>
                </td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td style="text-align: right; font-weight: bold;">{{ number_format($gsisSubtotalTerminalLeave, 2) }}</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td style="text-align: right; font-weight: bold;">{{ number_format($gsisSubtotalRetirementGratuity, 2) }}</td>
            </tr>
            </tbody>

            <!-- Section: For Non-GSIS Members -->
            <tbody class="section-group">
            <tr>
                <td style="font-size: 10px; vertical-align: top; padding: 5px;">
                    <p style="font-weight: bold; margin-bottom: 5px;">For Non-GSIS Members : (e.g. Military/Uniformed)</p>
                    <p>Retirement Laws (pls. specify)</p>
                </td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>

            @foreach($nonGsisMembers as $retiree)
                @php
                    $birthDate = $retiree->birth_date ? \Carbon\Carbon::parse($retiree->birth_date)->format('m/d/Y') : '';
                    $originalAppointment = $retiree->original_appointment_date ? \Carbon\Carbon::parse($retiree->original_appointment_date)->format('m/d/Y') : '';
                    $retirementDate = $retiree->retirement_date ? \Carbon\Carbon::parse($retiree->retirement_date)->format('m/d/Y') : '';
                @endphp
                <tr>
                    <td style="font-size: 9px; padding: 3px;">
                        {{ $retiree->name }}<br>
                        <span style="font-size: 8px;">{{ $retiree->retirement_law }}</span>
                    </td>
                    <td style="font-size: 9px; padding: 3px; text-align: center;">{{ $retiree->position }}</td>
                    <td style="font-size: 9px; padding: 3px; text-align: center;">{{ $birthDate }}</td>
                    <td style="font-size: 9px; padding: 3px; text-align: center;">{{ $originalAppointment }}</td>
                    <td style="font-size: 9px; padding: 3px; text-align: center;">{{ $retirementDate }}</td>
                    <td style="font-size: 9px; padding: 3px; text-align: right;">{{ number_format($retiree->highest_monthly_salary, 2) }}</td>
                    <td style="font-size: 9px; padding: 3px; text-align: center;">{{ number_format($retiree->vl_credits, 2) }}</td>
                    <td style="font-size: 9px; padding: 3px; text-align: center;">{{ number_format($retiree->sl_credits, 2) }}</td>
                    <td style="font-size: 9px; padding: 3px; text-align: right;">{{ number_format($retiree->terminal_leave_amount, 2) }}</td>
                    <td style="font-size: 9px; padding: 3px; text-align: center;">{{ number_format($retiree->creditable_service, 2) }}</td>
                    <td style="font-size: 9px; padding: 3px; text-align: right;">{{ number_format($retiree->gratuity_amount, 2) }}</td>
                    <td style="font-size: 9px; padding: 3px; text-align: right;">{{ number_format($retiree->retirement_gratuity_amount, 2) }}</td>
                </tr>
            @endforeach

            <tr>
                <td style="font-size: 10px; vertical-align: top; padding: 5px;">
                    <p style="font-weight: bold; margin-bottom: 5px;">Sub Total:</p>
                </td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td style="text-align: right; font-weight: bold;">{{ number_format($nonGsisSubtotalTerminalLeave, 2) }}</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td style="text-align: right; font-weight: bold;">{{ number_format($nonGsisSubtotalRetirementGratuity, 2) }}</td>
            </tr>
            </tbody>

            <!-- TOTAL Row -->
            <tr>
                <td style="font-weight: bold; font-size: 10px; padding: 5px;">TOTAL:</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td style="text-align: right; font-weight: bold;">{{ number_format($totalTerminalLeave, 2) }}</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td style="text-align: right; font-weight: bold;">{{ number_format($totalRetirementGratuity, 2) }}</td>
            </tr>

            <!-- Signature Section Row -->
            <tbody class="signature-section">
            <tr>
                <td colspan="4" style="padding: 8px; vertical-align: bottom;">
                    <p style="font-size: 10px; font-weight: bold; margin-bottom: 40px;">PREPARED BY:</p>
                    <p style="font-size: 11px; font-weight: bold; text-align: center; border-bottom: 1px solid #000; padding-bottom: 2px; margin-bottom: 3px;">{{ $signatories['prepared_by_name'] }}</p>
                    <p style="font-size: 10px; text-align: center;">{{ $signatories['prepared_by_position'] }}</p>
                </td>
                <td colspan="4" style="padding: 8px; vertical-align: bottom;">
                    <p style="font-size: 10px; font-weight: bold; margin-bottom: 40px;">APPROVED BY:</p>
                    <p style="font-size: 11px; font-weight: bold; text-align: center; border-bottom: 1px solid #000; padding-bottom: 2px; margin-bottom: 3px;">{{ $signatories['approved_by_name'] }}</p>
                    <p style="font-size: 10px; text-align: center;">{{ $signatories['approved_by_position'] }}</p>
                </td>
                <td colspan="4" style="padding: 8px; vertical-align: bottom;">
                    <p style="font-size: 10px; font-weight: bold; margin-bottom: 40px;">DATE:</p>
                    <p style="font-size: 11px; font-weight: bold; text-align: center; border-bottom: 1px solid #000; padding-bottom: 2px; margin-bottom: 3px;">{{ $signatories['date'] }}</p>
                    <p style="font-size: 10px; text-align: center;">Day/Mo/Year</p>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</body>

</html>
