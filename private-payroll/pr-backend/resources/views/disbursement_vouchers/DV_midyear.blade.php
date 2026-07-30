<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Disbursement Voucher - Mid-Year Bonus</title>

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

        .checkbox {
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            display: inline-block;
            margin-right: 5px;
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <div style="margin: 0.25in;">
        <!-- Appendix 32 -->
        <p class="text-right font-small" style="margin-bottom: 5px;">Appendix 32</p>

        <!-- Main Table -->
        <table style="border: 2px solid #000;">
            <!-- Header Row -->
            <tr>
                <!-- Left: Entity and Title -->
                <td colspan="4" style="border-bottom: 2px solid #000; border-right: 2px solid #000; padding: 8px 6px;">
                    <div style="text-align: center; position: relative;">
                        <p style="font-size: 12px; font-weight: bold; text-decoration: underline; margin-bottom: 2px;">
                            {{ strtoupper($orgCompanyName) }}
                        </p>
                        <p class="font-small" style="margin-bottom: 6px;">Entity Name</p>
                        <p style="font-size: 16px; font-weight: bold; margin: 0;">DISBURSEMENT VOUCHER</p>
                    </div>
                </td>
                <!-- Right: Fund/Date/DV -->
                <td colspan="2" style="padding: 4px 6px; border-bottom: 2px solid #000;">
                    <table style="width: 100%; border: none;">
                        <tr>
                            <td style="border: none; padding: 2px 0; width: 45%; font-size: 10px;">Fund Cluster :</td>
                            <td style="border: none; border-bottom: 1px solid black; padding: 2px 0; font-size: 10px;">&nbsp;</td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2px 0; font-size: 10px;">Date :</td>
                            <td style="border: none; border-bottom: 1px solid black; padding: 2px 0; font-size: 10px;">{{ $current_date ?? date('F d, Y') }}</td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2px 0; font-size: 10px;">DV No. :</td>
                            <td style="border: none; border-bottom: 1px solid black; padding: 2px 0; font-size: 10px;">&nbsp;</td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- Mode of Payment Row -->
            <tr>
                <td style="width: 15%; padding: 6px; border-right: 2px solid #000;" class="font-small">
                    <p>Mode of Payment</p>
                </td>
                <td colspan="5" style="padding: 5px; font-size: 11px;">
                    <span style="margin-right: 15px;">
                        <span class="checkbox"></span> MDS Check
                    </span>
                    <span style="margin-right: 15px;">
                        <span class="checkbox"></span> Commercial Check
                    </span>
                    <span style="margin-right: 15px;">
                        <span class="checkbox"></span> ADA
                    </span>
                    <span style="margin-right: 15px;">
                        <span class="checkbox"></span> Others (Please specify): _______________
                    </span>
                </td>
            </tr>

            <!-- Payee Row -->
            <tr>
                <td style="width: 15%; padding: 5px; border-right: 2px solid #000;" class="font-small">
                    <p>Payee</p>
                </td>
                <td colspan="3" style="padding: 5px; font-weight: bold; text-align: center; font-size: 11px;">
                    <p>LANDBANK OF THE PHILIPPINES</p>
                </td>
                <td style="width: 20%; padding: 5px; border-left: 2px solid #000; border-right: 2px solid #000;" class="font-small">
                    <p style="margin-bottom: 3px;">TIN/Employee No.:</p>
                    <p style="border-top: 1px solid #000; padding-top: 3px; min-height: 12px;">&nbsp;</p>
                </td>
                <td style="width: 15%; padding: 5px;" class="font-small">
                    <p style="margin-bottom: 3px;">ORS/BURS No.:</p>
                    <p style="border-top: 1px solid #000; padding-top: 3px; min-height: 12px;">&nbsp;</p>
                </td>
            </tr>

            <!-- Address Row -->
            <tr>
                <td style="padding: 5px; border-right: 2px solid #000;" class="font-small">
                    <p>Address</p>
                </td>
                <td colspan="5" style="padding: 5px; font-size: 11px;">
                    <p>&nbsp;</p>
                </td>
            </tr>

            <!-- Particulars Header Row -->
            <tr>
                <td colspan="3" style="padding: 5px; font-weight: bold; border-right: 2px solid #000;" class="text-center">
                    <p>Particulars</p>
                </td>
                <td style="padding: 5px; font-weight: bold; border-right: 2px solid #000;" class="text-center">
                    <p>Responsibility Center</p>
                </td>
                <td style="padding: 5px; font-weight: bold; border-right: 2px solid #000;" class="text-center">
                    <p>MFO/PAP</p>
                </td>
                <td style="padding: 5px; font-weight: bold;" class="text-center">
                    <p>Amount</p>
                </td>
            </tr>

            <!-- Particulars Content Row -->
            <tr>
                <td colspan="3" style="padding: 8px; border-right: 2px solid #000; height: 165px;" class="text-left">
                    <p style="margin-bottom: 10px; font-size: 11px;">
                        For payment of Mid-year Bonus for CY {{ $year ?? date('Y') }} of {{ $orgBranchCode }} officials and employees, as per attached supporting papers, amounting to ...
                    </p>
                </td>
                <td style="padding: 5px; border-right: 2px solid #000;">
                    <p>&nbsp;</p>
                </td>
                <td style="padding: 5px; border-right: 2px solid #000;">
                    <p>&nbsp;</p>
                </td>
                <td style="padding: 5px; vertical-align: bottom;" class="text-right font-bold">
                    <p> {{ number_format($total_amount ?? 0, 2, '.', ',') }}</p>
                </td>
            </tr>

            <!-- Amount Due Row -->
            <tr>
                <td colspan="5" style="padding: 5px; border-right: 2px solid #000;" class="text-right">
                    <p><strong>Amount Due</strong></p>
                </td>
                <td style="padding: 5px;" class="text-right font-bold">
                    <p> {{ number_format($total_amount ?? 0, 2, '.', ',') }}</p>
                </td>
            </tr>

            <!-- Section A: Certification -->
            <tr>
                <td colspan="6" style="padding: 0;">
                    <div style="padding: 6px 8px;">
                        <p class="font-bold" style="margin-bottom: 6px;">A. Certified: Expenses/Cash Advance necessary, lawful and incurred under my direct supervision.</p>
                        <div style="margin-top: 18px; text-align: center;">
                            <p class="underline font-bold" style="font-size: 12px; margin-bottom: 2px;">{{ $signatories['certifying_officer_name'] ?? 'MARIA ANTONIETTE S. ZOILO' }}</p>
                            <p style="font-size: 11px;">{{ $signatories['certifying_officer_position'] ?? 'Administrative Officer V' }}</p>
                        </div>
                    </div>
                </td>
            </tr>

            <!-- Section B: Accounting Entry -->
            <tr>
                <td colspan="6" style="padding: 0;">
                    <div style="padding: 6px 8px 0 8px;">
                        <p class="font-bold" style="margin-bottom: 6px;">B. Accounting Entry:</p>
                    </div>
                    <!-- Accounting Entry table without left/right borders -->
                    <table style="width: 100%; border: none; margin: 0; border-collapse: collapse;">
                        <tr>
                            <td style="width: 45%; padding: 3px; font-weight: bold; font-size: 10px; border-top: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; text-align: center;">Account Title</td>
                            <td style="width: 20%; padding: 3px; font-weight: bold; font-size: 10px; border-top: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; text-align: center;">UACS Code</td>
                            <td style="width: 17.5%; padding: 3px; font-weight: bold; font-size: 10px; border-top: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; text-align: center;">Debit</td>
                            <td style="width: 17.5%; padding: 3px; font-weight: bold; font-size: 10px; border-top: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-right: none; text-align: center;">Credit</td>
                        </tr>
                        <tr>
                            <td style="padding: 3px; height: 18px; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none;">&nbsp;</td>
                            <td style="padding: 3px; height: 18px; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none;">&nbsp;</td>
                            <td style="padding: 3px; height: 18px; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none;">&nbsp;</td>
                            <td style="padding: 3px; height: 18px; border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none;">&nbsp;</td>
                        </tr>
                        <tr>
                            <td style="padding: 3px; height: 18px; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none;">&nbsp;</td>
                            <td style="padding: 3px; height: 18px; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none;">&nbsp;</td>
                            <td style="padding: 3px; height: 18px; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none;">&nbsp;</td>
                            <td style="padding: 3px; height: 18px; border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none;">&nbsp;</td>
                        </tr>
                        <tr>
                            <td style="padding: 3px; height: 18px; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none;">&nbsp;</td>
                            <td style="padding: 3px; height: 18px; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none;">&nbsp;</td>
                            <td style="padding: 3px; height: 18px; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none;">&nbsp;</td>
                            <td style="padding: 3px; height: 18px; border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none;">&nbsp;</td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- Section C and D: Certified and Approved for Payment -->
            <tr>
                <td colspan="3" style="padding: 0; vertical-align: top; border-right: 2px solid #000;">
                    <div style="padding: 6px 8px 0 8px;">
                        <p class="font-bold" style="margin-bottom: 6px;">C. Certified:</p>
                        <div style="margin-bottom: 8px;">
                            <p style="margin-bottom: 4px; font-size: 11px;">
                                <span class="checkbox"></span> Cash available
                            </p>
                            <p style="margin-bottom: 4px; font-size: 11px;">
                                <span class="checkbox"></span> Subject to Authority to Debit Account (when applicable)
                            </p>
                            <p style="margin-bottom: 4px; font-size: 11px;">
                                <span class="checkbox"></span> Supporting documents complete and amount claimed proper
                            </p>
                        </div>
                    </div>
                    <!-- Signature table without left/right borders -->
                    <table style="width: 100%; border: none; margin: 0; font-size: 10px; border-collapse: collapse;">
                        <tr>
                            <td style="border-top: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; padding: 3px; width: 30%;">Signature:</td>
                            <td style="border-top: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-right: none; padding: 3px;">&nbsp;</td>
                        </tr>
                        <tr>
                            <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none; padding: 3px;">Printed Name:</td>
                            <td style="border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none; padding: 3px;">
                                <p class="font-bold" style="margin: 0;">{{ $signatories['accountant_name'] ?? 'GERALENE Q. NADELA' }}</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none; padding: 3px; vertical-align: top;">Position:</td>
                            <td style="border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none; padding: 3px; vertical-align: top;">
                                <p class="font-bold" style="margin: 0; margin-bottom: 2px;">{{ $signatories['accountant_position'] ?? 'Accountant III' }}</p>
                                <p style="margin: 0; font-size: 9px;">{{ $signatories['accountant_role'] ?? 'Head, Accounting Unit/Authorized Representative' }}</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none; padding: 3px;">Date:</td>
                            <td style="border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none; padding: 3px;">&nbsp;</td>
                        </tr>
                    </table>
                </td>
                <td colspan="3" style="padding: 0; vertical-align: top;">
                    <div style="padding: 6px 8px 0 8px;">
                        <p class="font-bold" style="margin-bottom: 6px;">D. Approved for Payment</p>
                        <div style="margin-bottom: 62px;">
                            &nbsp;
                        </div>
                    </div>
                    <!-- Signature table without left/right borders -->
                    <table style="width: 100%; border: none; margin: 0; font-size: 10px; border-collapse: collapse;">
                        <tr>
                            <td style="border-top: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; padding: 3px; width: 30%;">Signature:</td>
                            <td style="border-top: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-right: none; padding: 3px;">&nbsp;</td>
                        </tr>
                        <tr>
                            <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none; padding: 3px;">Printed Name:</td>
                            <td style="border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none; padding: 3px;">
                                <p class="font-bold" style="margin: 0;">{{ $signatories['approving_officer_name'] ?? 'CLARE MARI S. TORRALBA' }}</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none; padding: 3px; vertical-align: top;">Position:</td>
                            <td style="border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none; padding: 3px; vertical-align: top;">
                                <p class="font-bold" style="margin: 0; margin-bottom: 2px;">{{ $signatories['approving_officer_position'] ?? 'Executive Director' }}</p>
                                <p style="margin: 0; font-size: 9px;">{{ $signatories['approving_officer_role'] ?? 'Agency Head/Authorized Representative' }}</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none; padding: 3px;">Date:</td>
                            <td style="border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none; padding: 3px;">&nbsp;</td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- Section E: Receipt of Payment -->
            <tr>
                <td colspan="5" style="padding: 0; border-right: 2px solid #000;">
                    <div style="padding: 6px 8px 0 8px;">
                        <p class="font-bold" style="margin-bottom: 6px;">E. Receipt of Payment</p>
                    </div>
                    <table style="border: none; width: 100%; font-size: 10px; border-collapse: collapse; margin: 0;">
                        <tr>
                            <td style="padding: 3px; width: 22%; border-top: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none;">Check/ADA No.:</td>
                            <td style="padding: 3px; width: 28%; border-top: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none;">&nbsp;</td>
                            <td style="padding: 3px; width: 12%; border-top: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none;">Date:</td>
                            <td style="padding: 3px; border-top: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-right: none;">&nbsp;</td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding: 3px; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none;">Bank Name & Account Number:</td>
                            <td colspan="2" style="padding: 3px; border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none;">&nbsp;</td>
                        </tr>
                        <tr>
                            <td style="padding: 3px; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none;">Signature:</td>
                            <td style="padding: 3px; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none;">&nbsp;</td>
                            <td style="padding: 3px; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none;">Date:</td>
                            <td style="padding: 3px; border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none;">&nbsp;</td>
                        </tr>
                        <tr>
                            <td style="padding: 3px; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none;">Printed Name:</td>
                            <td colspan="2" style="padding: 3px; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none;">
                                <p style="margin: 0;">&nbsp;</p>
                            </td>
                            <td style="padding: 3px; border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none;">Date:</td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding: 3px; border-right: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-top: none;">Official Receipt No. & Date/Other Documents</td>
                            <td colspan="2" style="padding: 3px; border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none;">&nbsp;</td>
                        </tr>
                    </table>
                </td>
                <td style="padding: 0; vertical-align: top;">
                    <div style="padding: 6px 8px 0 8px;">
                        &nbsp;
                    </div>
                    <table style="width: 100%; border: none; font-size: 10px; border-collapse: collapse; margin: 0;">
                        <tr>
                            <td style="border-top: 1px solid #000; border-bottom: 1px solid #000; border-left: none; border-right: none; padding: 3px; text-align: center;">JEV No.</td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none; padding: 3px; height: 20px;">&nbsp;</td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none; padding: 3px; text-align: center;">Date</td>
                        </tr>
                        <tr>
                            <td style="padding: 3px; height: 20px; border-bottom: 1px solid #000; border-left: none; border-right: none; border-top: none;">&nbsp;</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
