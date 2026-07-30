<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <title>Obligation Request and Status</title>

    <style>
        @media print {
            @page {
                size: letter;
                margin: 0.25in;
            }
            body {
                margin: 0;
            }
        }

        html,
        body {
            height: 297mm;
            width: 210mm;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        p {
            padding: 0;
            margin: 0;
            font-size: 11px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: top;
        }

        .no-border {
            border: none;
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

        .font-small {
            font-size: 10px;
        }

        .underline {
            text-decoration: underline;
        }

        /* Remove inner grid lines in A/B signature blocks; keep only outer cell border */
        .signature-table td {
            border: none !important;
        }
    </style>
</head>
<body>
@php
    // Fallbacks so this works with existing ORS controllers
    $currentDate = $current_date ?? date('F d, Y');
    $firstRow    = isset($data[0]) ? $data[0] : null;
    $totalAmount = $amount ?? ($data->sum('amount') ?? 0);
    $particular  = $description
        ?? ($firstRow->particular ?? 'Initial Salary / Benefits');
    $entityName  = 'DTI - ' . $orgCompanyName;
    $payeeName   = $payee ?? ($company[0]->name ?? $orgCompanyName);
@endphp

<div style="margin: 0.25in;">
    <!-- Appendix label -->
    <p class="text-right font-small" style="margin-bottom: 5px;">Appendix 11</p>

    <!-- Main ORS table -->
    <table style="border: 2px solid #000; table-layout: fixed; width: 100%;">
        <!-- Header row -->
        <tr>
            <!-- Entity / Title -->
            <td colspan="4"
                style="border-bottom: 2px solid #000; border-right: 2px solid #000; padding: 6px 4px;">
                <div class="text-center">
                    <p style="font-size: 12px; font-weight: bold; text-decoration: underline; margin-bottom: 2px;">
                        OBLIGATION REQUEST AND STATUS
                    </p>
                    <p style="font-size: 14px; font-weight: bold; margin: 0;">
                        {{ $entityName }}
                    </p>
                    <p class="font-small" style="margin-top: 2px;">Entity Name</p>
                </div>
            </td>

            <!-- Serial / Date / Fund Cluster -->
            <td colspan="2" style="padding: 4px 6px; border-bottom: 2px solid #000;">
                <table style="width: 100%; border: none;">
                    <tr>
                        <td class="no-border" style="width: 40%; font-size: 10px;">Serial No. :</td>
                        <td class="no-border" style="border-bottom: 1px solid #000; font-size: 10px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td class="no-border" style="font-size: 10px;">Date :</td>
                        <td class="no-border" style="border-bottom: 1px solid #000; font-size: 10px;">
                            {{ $currentDate }}
                        </td>
                    </tr>
                    <tr>
                        <td class="no-border" style="font-size: 10px;">Fund Cluster :</td>
                        <td class="no-border" style="border-bottom: 1px solid #000; font-size: 10px;">&nbsp;</td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Payee row -->
        <tr>
            <td style="width: 12%; padding: 4px;" class="font-small">
                <p>Payee</p>
            </td>
            <td colspan="5" style="padding: 4px; text-align: left; font-size: 11px;">
                <p>{{ $payeeName }}</p>
            </td>
        </tr>

        <!-- Office row -->
        <tr>
            <td style="padding: 4px;" class="font-small">
                <p>Office</p>
            </td>
            <td colspan="5" style="padding: 4px; text-align: left; font-size: 11px;">
                &nbsp;
            </td>
        </tr>

        <!-- Address row -->
        <tr>
            <td style="padding: 4px;" class="font-small">
                <p>Address</p>
            </td>
            <td colspan="5" style="padding: 4px; font-size: 11px;">
                <p>
                    {{ strtoupper($company[0]->address ?? $orgCompanyAddress) }}
                </p>
            </td>
        </tr>

        <!-- Column headers row (Responsibility Center, Particulars, etc.) -->
        <tr>
            <td style="padding: 4px; font-weight: bold;" class="text-center">
                <p>Responsibility Center</p>
            </td>
            <td colspan="2" style="padding: 4px; font-weight: bold;" class="text-center">
                <p>Particulars</p>
            </td>
            <td style="padding: 4px; font-weight: bold;" class="text-center">
                <p>MFO/PAP</p>
            </td>
            <td style="padding: 4px; font-weight: bold;" class="text-center">
                <p>UACS Object Code</p>
            </td>
            <td style="padding: 4px; font-weight: bold;" class="text-center">
                <p>Amount (PhP)</p>
            </td>
        </tr>

        <!-- Main particulars row (single tall cell to avoid extra dividers) -->
        <tr>
            <td style="padding: 8px;">&nbsp;</td>
            <td colspan="2" style="padding: 8px; text-align: left; height: 120px;">
                For payment of {{ $particular }} as per attached supporting papers,
                amounting to {{ number_format($totalAmount, 2, '.', ',') }}.
            </td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td class="text-right font-bold" style="vertical-align: bottom;">
                {{ number_format($totalAmount, 2, '.', ',') }}
            </td>
        </tr>

        <!-- TOTAL row -->
        <tr>
            <td colspan="5" class="text-right font-bold">
                TOTAL&nbsp;&nbsp;
            </td>
            <td class="text-right font-bold">
                {{ number_format($totalAmount, 2, '.', ',') }}
            </td>
        </tr>

        <!-- Section A and B (certifications) -->
        <tr>
            <!-- A. Certified (Head, Requesting Office) -->
            <td colspan="3" style="padding: 0; vertical-align: top; width: 50%; max-width: 50%; overflow: hidden;">
                <div style="padding: 6px 8px 0 8px;">
                    <p class="font-bold" style="margin-bottom: 6px;">A.</p>
                    <p class="font-small" style="margin-left: 10px; margin-bottom: 12px;">
                        Certified: Charges to appropriation/allotment are necessary, lawful and under my direct
                        supervision; and supporting documents valid, proper and legal.
                    </p>
                </div>
                <table class="signature-table" style="width: 100%; border: none; font-size: 10px; border-collapse: collapse; margin: 0;">
                    <tr>
                        <td style="border-top: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; padding: 3px; width: 22%;">Signature</td>
                        <td style="border-top: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-right: none; padding: 3px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none; padding: 3px;">Printed Name</td>
                        <td style="border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none; padding: 3px;">
                            <p class="font-bold" style="margin: 0; border-bottom: 1px solid #000;">
                                {{ $signatories['certifying_officer_name'] ?? 'MARIA ANTONIETTE S. ZOILO' }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none; padding: 3px;">Position</td>
                        <td style="border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none; padding: 3px;">
                            <p style="margin: 0; font-size: 10px; border-bottom: 1px solid #000;">
                                {{ $signatories['certifying_officer_position'] ?? 'Administrative Officer V' }}
                            </p>
                            <p style="margin: 0; font-size: 9px; border-bottom: 1px solid #000;">
                                {{ $signatories['certifying_officer_role'] ?? 'Head, Requesting Office/Authorized Representative' }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none; padding: 3px;">Date</td>
                        <td style="border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none; padding: 3px;">&nbsp;</td>
                    </tr>
                </table>
            </td>

            <!-- B. Certified (Budget Officer) -->
            <td colspan="3" style="padding: 0; vertical-align: top; width: 50%; max-width: 50%; overflow: hidden;">
                <div style="padding: 6px 8px 0 8px;">
                    <p class="font-bold" style="margin-bottom: 6px;">B.</p>
                    <p class="font-small" style="margin-left: 10px; margin-bottom: 12px;">
                        Certified: Allotment available and obligated for the purpose/adjustment necessary as indicated
                        above.
                    </p>
                </div>
                <table class="signature-table" style="width: 100%; border: none; font-size: 10px; border-collapse: collapse; margin: 0;">
                    <tr>
                        <td style="border-top: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; padding: 3px; width: 22%;">Signature</td>
                        <td style="border-top: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-right: none; padding: 3px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none; padding: 3px;">Printed Name</td>
                        <td style="border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none; padding: 3px;">
                            <p class="font-bold" style="margin: 0; border-bottom: 1px solid #000;">
                                {{ $signatories['approving_officer_name'] ?? 'MARGIE F. ENGSON' }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none; padding: 3px;">Position</td>
                        <td style="border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none; padding: 3px;">
                            <p style="margin: 0; font-size: 10px; border-bottom: 1px solid #000;">
                                {{ $signatories['approving_officer_position'] ?? 'OIC - Budget Officer III' }}
                            </p>
                            <p style="margin: 0; font-size: 9px; border-bottom: 1px solid #000;">
                                {{ $signatories['approving_officer_role'] ?? 'Head, Budget Division/Unit/Authorized Representative' }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none; padding: 3px;">Date</td>
                        <td style="border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none; padding: 3px;">&nbsp;</td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Section C: Status of Obligation -->
        <tr>
            <td colspan="6" style="padding: 0;">
                <div style="padding: 6px 8px 0 8px;">
                    <p class="font-bold" style="margin-bottom: 6px;">C. STATUS OF OBLIGATION</p>
                </div>
                <table style="width: 100%; border-collapse: collapse; font-size: 10px; margin: 0;">
                    <!-- Header row: Reference / Amount / Balance -->
                    <tr>
                        <td colspan="3" rowspan="2" style="border: 1px solid #000; padding: 3px; text-align: center; vertical-align: middle; font-weight: bold;">
                            Reference
                        </td>
                        <td colspan="5" style="border: 1px solid #000; padding: 3px; text-align: center; font-weight: bold;">
                            Amount
                        </td>
                    </tr>
                    <!-- Second header row: Obligation, Payable, Payment, then Balance spans the last two -->
                    <tr>
                        <td style="border: 1px solid #000; padding: 3px; width: 9%; text-align: center;">Obligation</td>
                        <td style="border: 1px solid #000; padding: 3px; width: 9%; text-align: center;">Payable</td>
                        <td style="border: 1px solid #000; padding: 3px; width: 9%; text-align: center;">Payment</td>
                        <td colspan="2" style="border: 1px solid #000; padding: 3px; text-align: center;">Balance</td>
                    </tr>
                    <!-- Third header row: Date, Particulars, ORS No., (a), (b), (c), Not Yet Due, Due and Demandable -->
                    <tr>
                        <td style="border: 1px solid #000; padding: 3px; width: 10%;">Date</td>
                        <td style="border: 1px solid #000; padding: 3px; width: 22%;">Particulars</td>
                        <td style="border: 1px solid #000; padding: 3px; width: 18%;">ORS/JEV/RCI/RAD/AI No.</td>
                        <td style="border: 1px solid #000; padding: 3px; width: 9%; text-align: center;">(a)</td>
                        <td style="border: 1px solid #000; padding: 3px; width: 9%; text-align: center;">(b)</td>
                        <td style="border: 1px solid #000; padding: 3px; width: 9%; text-align: center;">(c)</td>
                        <td style="border: 1px solid #000; padding: 3px; width: 11%; text-align: center;">Not Yet Due<br/>(a-b)</td>
                        <td style="border: 1px solid #000; padding: 3px; width: 12%; text-align: center;">Due and Demandable<br/>(b-c)</td>
                    </tr>

                    @for($i = 0; $i < 3; $i++)
                        <tr>
                            @for($j = 0; $j < 8; $j++)
                                <td style="border: 1px solid #000; padding: 3px; height: 16px;">&nbsp;</td>
                            @endfor
                        </tr>
                    @endfor
                </table>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
