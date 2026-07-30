<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Business Slip</title>
    <style>
        @page {
            size: A4;
            margin: 0.5in;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .form-wrapper {
            padding: 12px;
            min-height: calc(297mm - 1in);
        }
        .afm {
            text-align: right;
            font-size: 11px;
            margin-bottom: 4px;
        }
        .slip {
            page-break-inside: avoid;
            margin-bottom: 12px;
        }
        .title {
            text-align: center;
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }
        .subtitle {
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            margin: 6px 0 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #000;
        }
        td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: top;
            height: 26px;
        }
        .label {
            font-weight: 700;
            margin-right: 6px;
            white-space: nowrap;
        }
        .line {
            display: block;
            width: 100%;
            border-bottom: 1px solid #000;
            padding-top: 4px;
            min-height: 14px;
        }
        .blank-row td {
            height: 22px;
        }
        .approval-row td {
            border-bottom: 1px solid #000;
        }
        .signature {
            margin-top: 6px;
            text-align: right;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="form-wrapper">
        @php
            $phones = [];
            if (!empty($mobile_no)) {
                $phones[] = $mobile_no;
            }
            if (!empty($telephone_no)) {
                $phones[] = $telephone_no;
            }
            if (empty($phones) && !empty($telephone_numbers)) {
                $phones[] = $telephone_numbers;
            }
            $phoneDisplay = count($phones) ? implode(' / ', $phones) : 'none';
            // Determine first approver only
            $approvedBy = '';
            if (!empty($approved_by)) {
                $approvedBy = $approved_by;
            } elseif (!empty($approver_details) && is_array($approver_details) && !empty($approver_details[0])) {
                $approvedBy = $approver_details[0];
            } elseif (!empty($approver_1)) {
                $approvedBy = $approver_1;
            }
        @endphp

        @php
            $isPassSlip = isset($ob_type) && (int)$ob_type === 2;
            $formTitle = $isPassSlip ? 'PASS SLIP' : 'OFFICIAL BUSINESS SLIP';
            $afmCode  = $isPassSlip ? 'AFM-PER.FR#09/REV.00/15-16-14' : 'AFM - PER.FR#08/Rev.00/05-16-14';

            // Single slip renderer with header/logo spacing similar to supplied layout
            $renderSlip = function() use ($date, $time, $names, $destinations, $phoneDisplay, $purposes, $approvedBy, $formTitle, $afmCode) {
                return <<<HTML
                <div class="slip">
                    <div class="afm">{$afmCode}</div>
                    <table>
                        <tr>
                            <td colspan="2" style="text-align:center; font-weight:700; font-size:16px; border-left:1px solid #000; border-right:1px solid #000;">{{ strtoupper($orgCompanyName) }}</td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:center; font-weight:700; font-size:14px; border-left:1px solid #000; border-right:1px solid #000;">{$formTitle}</td>
                        </tr>
                        <tr>
                            <td style="width: 50%;">
                                <span class="label">DATE:</span>
                                <span class="line">{$date}</span>
                            </td>
                            <td>
                                <span class="label">TIME:</span>
                                <span class="line">{$time}</span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <span class="label">NAMES:</span>
                                <span class="line">{$names}</span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="border-bottom: none; padding-bottom: 1px;">
                                <span class="label">DESTINATION/S:</span>
                                <span class="line" style="border-bottom: none; min-height: 40px;">{$destinations}</span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <span class="label">TELEPHONE NUMBER/S:</span>
                                <span class="line">{$phoneDisplay}</span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="border-bottom: none; padding-bottom: 1px;">
                                <span class="label">PURPOSE/S:</span>
                                <span class="line" style="border-bottom: none; min-height: 40px;">{$purposes}</span>
                            </td>
                        </tr>
                        <tr class="approval-row">
                            <td>
                                <span class="label">APPROVED:</span>
                            </td>
                            <td style="text-align: right;">
                                <div style="margin-top: 8px;">
                                    <span class="line" style="width: 75%; margin-left: auto; display: block;">{$approvedBy}</span>
                                    <div class="signature">Head of Office/Division</div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                HTML;
            };
        @endphp

        {!! $renderSlip() !!}

        <div style="height: 24px;"></div>

        {!! $renderSlip() !!}
    </div>
</body>
</html>
