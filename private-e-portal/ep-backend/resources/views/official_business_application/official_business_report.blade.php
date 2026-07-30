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
            display: inline-block;
            vertical-align: bottom;
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
            // Controller passes `$ob` and `$footer`; normalize `$ob` into a single row for the view.
            $obRow = null;
            if (isset($ob)) {
                if ($ob instanceof \Illuminate\Support\Collection) {
                    $obRow = $ob->first();
                } elseif (is_array($ob)) {
                    $obRow = $ob[0] ?? null;
                } elseif (is_object($ob)) {
                    $obRow = $ob;
                }
            }

            // Provide safe defaults for variables used below to avoid "Undefined variable" errors.
            $ob_type = $ob_type ?? ($obRow->ob_type ?? null);

            // Derive DATE, TIME IN (From), and TIME OUT (To) from date_time_from/date_time_to.
            $date = $date ?? (function () use ($obRow) {
                $from = $obRow->date_time_from ?? null;
                $to = $obRow->date_time_to ?? null;

                try {
                    if ($from && $to) {
                        $fromDt = \Carbon\Carbon::parse($from);
                        $toDt = \Carbon\Carbon::parse($to);

                        // Same calendar day
                        if ($fromDt->isSameDay($toDt)) {
                            return $fromDt->format('F d, Y');
                        }

                        // Same month and year -> "January 27-28, 2026"
                        if ($fromDt->year === $toDt->year && $fromDt->month === $toDt->month) {
                            return $fromDt->format('F d') . '-' . $toDt->format('d, Y');
                        }

                        // Different month and/or year -> "Jan 31, 2026 - Feb 01, 2026"
                        return $fromDt->format('F d, Y') . ' - ' . $toDt->format('F d, Y');
                    }

                    if ($from) {
                        return \Carbon\Carbon::parse($from)->format('F d, Y');
                    }

                    if (isset($obRow->date) && $obRow->date) {
                        return \Carbon\Carbon::parse($obRow->date)->format('F d, Y');
                    }
                } catch (\Throwable $e) {
                    // Fallback to raw values if parsing fails
                    if ($from && $to) {
                        return $from . ' - ' . $to;
                    }
                    if ($from) {
                        return (string) $from;
                    }
                }

                return '';
            })();

            // TIME IN = From, TIME OUT = To
            $timeIn = $timeIn ?? (function () use ($obRow) {
                $from = $obRow->date_time_from ?? null;
                if (!$from) {
                    return '';
                }
                try {
                    return \Carbon\Carbon::parse($from)->format('h:i A');
                } catch (\Throwable $e) {
                    return (string) $from;
                }
            })();

            $timeOut = $timeOut ?? (function () use ($obRow) {
                $to = $obRow->date_time_to ?? null;
                if (!$to) {
                    return '';
                }
                try {
                    return \Carbon\Carbon::parse($to)->format('h:i A');
                } catch (\Throwable $e) {
                    return (string) $to;
                }
            })();
            $names = $names ?? ($obRow->name ?? '');
            $destinations = $destinations ?? ($obRow->client ?? '');
            $purposes = $purposes ?? ($obRow->purpose ?? '');

            $mobile_no = $mobile_no ?? '';
            $telephone_no = $telephone_no ?? '';
            // Prefer explicitly-passed telephone_numbers, otherwise fall back to value from the OB record.
            $telephone_numbers = $telephone_numbers ?? ($obRow->telephone_numbers ?? '');

            // Approved by should be based on approver_headers.approver_id_1 (type_id = 5 / OB Slip-Pass Slip),
            // computed in controller as `approved_by_name`.
            $approved_by = $approved_by ?? (($obRow->approved_by_name ?? '') ?: ($obRow->approver ?? ($obRow->recommending_approval ?? '')));
            $approver_details = $approver_details ?? [];
            $approver_1 = $approver_1 ?? '';

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
            // Render as an underscore placeholder when there are no phone numbers.
            // (The underline/border is already provided by the `.line` class in the markup.)
            $phoneDisplay = count($phones) ? implode(' / ', $phones) : '________________________';
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
            use App\Helpers\CompanyHelper;

            $isPassSlip = isset($ob_type) && (int)$ob_type === 2;
            $formTitle = $isPassSlip ? 'PASS SLIP' : 'OFFICIAL BUSINESS SLIP';
            $afmCode  = $isPassSlip ? 'AFM-PER.FR#09/REV.00/15-16-14' : 'AFM - PER.FR#08/Rev.00/05-16-14';
            $companyName = CompanyHelper::getName() ?: 'Company Name';

            // Single slip renderer with header/logo spacing similar to supplied layout
            $renderSlip = function() use ($date, $timeIn, $timeOut, $names, $destinations, $phoneDisplay, $purposes, $approvedBy, $formTitle, $afmCode, $companyName) {
                return <<<HTML
                <div class="slip">
                    <div class="afm">{$afmCode}</div>
                    <table>
                        <!-- Header: Agency name and form title -->
                        <tr>
                            <td colspan="2" style="text-align:center; font-weight:700; font-size:16px; border-left:1px solid #000; border-right:1px solid #000; border-bottom:none;">
                                {$companyName}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:center; font-weight:700; font-size:14px; border-left:1px solid #000; border-right:1px solid #000; border-top:none; border-bottom:none;">
                                {$formTitle}
                            </td>
                        </tr>

                        <!-- Names (left) and Date / Time Out / Time In (right) -->
                        <tr>
                            <td rowspan="3" style="width: 65%; border-top:none; border-right:1px solid #000; border-right:none; border-bottom:none;">
                                <span class="label">NAMES:</span>
                                <span class="line" style="display:inline-block; padding-top:0; min-height:0; vertical-align:bottom; width:86%;">{$names}</span>
                                <span class="line">&nbsp;</span>
                                <span class="line">&nbsp;</span>
                            </td>
                            <td style="border-top:none; border-left:none; border-bottom:none;">
                                <span class="label">DATE:</span>
                                <span class="line" style="display:inline-block; padding-top:0; min-height:0; vertical-align:bottom; width:70%;">{$date}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="border-left:none; border-top:none; border-bottom:none;">
                                <span class="label">TIME OUT:</span>
                                <span class="line" style="display:inline-block; padding-top:0; min-height:0; vertical-align:bottom; width:60%;">{$timeOut}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="border-left:none; border-top:none; border-bottom:none;">
                                <span class="label">TIME IN:</span>
                                <span class="line" style="display:inline-block; padding-top:0; min-height:0; vertical-align:bottom; width:70%;">{$timeIn}</span>
                            </td>
                        </tr>

                        <!-- Destination -->
                        <tr>
                            <td colspan="2" style="border-top:none; border-bottom:none;">
                                <span class="label">DESTINATION/S:</span>
                                <span class="line" style="margin-top: 2px;">{$destinations}</span>
                                <span class="line">&nbsp;</span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="border-top:none; border-bottom:none;">
                                <span class="label">TELEPHONE NUMBER/S:</span>
                                <span class="line" style="margin-top: 2px;">{$phoneDisplay}</span>
                            </td>
                        </tr>

                        <!-- Purpose -->
                        <tr>
                            <td colspan="2" style="border-top:none; border-bottom:none;">
                                <span class="label">PURPOSE/S:</span>
                                <span class="line" style="margin-top: 2px;">{$purposes}</span>
                                <span class="line">&nbsp;</span>
                                <span class="line">&nbsp;</span>
                            </td>
                        </tr>

                        <!-- Approval signature -->
                        <tr>
                            <td style="border-right:none; border-top:none;">
                                <span class="label"></span>
                            </td>
                            <td style="border-left:none; border-top:none; text-align: right;">
                                    <div class="signature" style="margin-right: 10px; font-weight: normal; text-align: left;">Approved by:</div>
                                <div style="margin-top: 24px;">
                                    <span class="line" style="width: 80%; margin-left: auto; display: block; text-align: center;">{$approvedBy}</span>
                                    <div class="signature" style="margin-left: 70px; margin-right: 10px; font-weight: normal; text-align: center;">Division Chief/Authorized Representative</div>
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

