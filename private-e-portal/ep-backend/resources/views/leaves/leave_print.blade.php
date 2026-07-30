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

    <title>Application for Leave</title>

    <style>
        .cs {
            float: left;
            margin-left: 10px;
            font-size: 9px;
            margin-top: -60px;
        }

        .Annex {
            float: right;
            margin-top: -60px;
        }

        .header {
            text-align: center;
            margin: 20px;
            margin-top: -80px;
            margin-left: -30px;
            margin-bottom: -14px;
            font-size: 14px;
        }

        .header img {
            width: 70px;
            float: left;
            margin-left: 100px;
            margin-right: -250px;
        }

        .header .title {
            margin: 5px 0;
        }

        .header .subtitle {
            margin: 5px 0;
        }

        html,
        body {
            height: 297mm;
            width: 210mm;
            margin-top: 10px;
            margin-bottom: 5px;
            margin-left: auto;
            margin-right: auto;
        }

        p {
            padding: 0;
            margin: 0;
        }

        .main {
            margin: 0.5in 1in 0.5in 1in;
        }
    </style>
</head>

<body>
    @php
        use App\Helpers\CompanyHelper;

        $companyName = CompanyHelper::getName();
        $companyAddress = CompanyHelper::getAddress();
        $companyLogo = CompanyHelper::getLogoBase64() ?? ($image ?? null);
    @endphp
    <!-- Main content -->
    @foreach ($leave as $dtl)
        <div class="main">

            <p class="cs"><strong>Civil Service Form No. 6</strong><br>Revised 2020</p>
            <p class="Annex"><strong>Annex A</strong><br>
            <div class="header">
                </p><br><br><br><br>
                @if ($companyLogo)
                <img src="data:image/jpeg;base64,{{ $companyLogo }}" style="object-fit: contain;">
                @elseif (!empty($image))
                <img src="data:image/png;base64,{{ $image }}">
                @endif
                @if ($companyName !== '')
                <div class="title">{{ $companyName }}</div>
                @endif
                @if ($companyAddress !== '')
                <div class="subtitle">{!! nl2br(e($companyAddress)) !!}</div>
                @endif
                <h3>APPLICATION FOR LEAVE</h3>
            </div>
            <table style="border: solid 1px black;border-collapse: collapse;width: 100%;margin-top: 10px;">
                <tr>
                    <td>
                        <p style="font-size: 10px;">1. OFFICE/DEPARTMENT</p>
                    </td>
                    <td>
                        <p style="font-size: 10px;">2. NAME :</p>
                    </td>
                    <td>
                        <p style="font-size: 10px;">(Last) </p>
                    </td>
                    <td>
                        <p style="font-size: 10px;">(First) </p>
                    </td>
                    <td>
                        <p style="font-size: 10px;">(Middle) </p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p style="font-size: 10px;">{{ $dtl->department }}</p>
                    </td>
                    <td>
                        <p style="font-size: 10px;"></p>
                    </td>
                    <td>
                        <p style="font-size: 10px;">{{ $dtl->last_name }}</p>
                    </td>
                    <td>
                        <p style="font-size: 10px;">{{ $dtl->first_name }}</p>
                    </td>
                    <td>
                        <p style="font-size: 10px;">{{ $dtl->middle_name }}</p>
                    </td>
                </tr>
            </table>
            <table
                style="border: solid 1px black;border-collapse: collapse;width: 100%;margin-top: 0px;border-top: none;">
                <tr>
                    <td style="padding-bottom: 10px;width: 200px;">
                        <p style="font-size: 10px;display: inline-block;">3. DATE OF FILING</p>
                        <p
                            style="font-size: 10px;border-bottom: solid 1px black;text-align: center;display: inline-block;width: 100px;">
                            {{ date('M d, Y', strtotime($dtl->created_at)) }}
                        </p>
                    </td>
                    <td style="padding-bottom: 10px;">
                        <p style="font-size: 10px;display: inline-block;">4. POSITION</p>
                        <p
                            style="font-size: 10px;border-bottom: solid 1px black;text-align: center;display: inline-block;width: 130px;">
                            {{ $dtl->position }}
                        </p>
                    </td>
                    <td style="padding-bottom: 10px;">
                        <p style="font-size: 10px;display: inline-block;">5. SALARY GRADE</p>
                        <p
                            style="font-size: 10px;border-bottom: solid 1px black;text-align: center;display: inline-block;width: 100px;">
                            {{ $dtl->salary_grade_id ?? '' }}
                        </p>
                    </td>
                </tr>
            </table>
            <table
                style="border: solid 1px black;border-collapse: collapse;width: 100%;margin-top: 0px;border-top: none;">
                <tr>
                    <td style="padding: 5px;width: 200px;">
                        <p style="font-size: 9;font-weight: bold;text-align: center;">6. DETAILS OF APPLICATION</p>
                    </td>
                </tr>
            </table>
            <table
                style="border: solid 1px black;border-collapse: collapse;width: 100%;margin-top: 0px;border-top: none;">
                <tr>
                    <td style="padding: 5px;width: 100px;">
                        @php
                            // Prefer the explicit alias if present, but remain backwards compatible
                            $leaveTypeName = $dtl->leave_type_name ?? $dtl->leave_type ?? '';
                            $leaveTypeId = (int) ($dtl->leave_type_id ?? 0);
                            $predefinedLeaveTypeIds = [1, 6, 2, 21, 22, 3, 4, 5, 23, 24, 25, 26, 27];
                        @endphp
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                                <p style="font-size: 5;margin: 0px;">
                                    {{ stripos($leaveTypeName, 'vacation') !== false ? 'X' : '' }}
                                </p>
                            </div>
                            <p style="font-size: 6;display: inline-block;"><b>Vacation Leave</b> (Sec. 51, Rule XVI,
                                Omnibus
                                Rules Implementing E.O. No. 292)</p>
                        </div>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                            <p style="font-size: 5;margin: 0px;">
                                    {{ stripos($leaveTypeName, 'forced') !== false || stripos($leaveTypeName, 'mandatory') !== false ? 'X' : '' }}
                                </p>
                            </div>
                            <p style="font-size: 6;display: inline-block;"><b>Mandatory/Forced Leave</b>(Sec. 25, Rule
                                XVI,
                                Omnibus Rules Implementing E.O. No. 292)</p>
                        </div>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                            <p style="font-size: 5;margin: 0px;">
                                    {{ stripos($leaveTypeName, 'sick') !== false ? 'X' : '' }}
                                </p>
                            </div>
                            <p style="font-size: 6;display: inline-block;"><b>Sick Leave</b> (Sec. 43, Rule XVI, Omnibus
                                Rules
                                Implementing E.O. No. 292)</p>
                        </div>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                            <p style="font-size: 5;margin: 0px;">
                                    {{ stripos($leaveTypeName, 'maternity') !== false ? 'X' : '' }}
                                </p>
                            </div>
                            <p style="font-size: 6;display: inline-block;"><b>Maternity Leave</b> (R.A. No. 11210 / IRR
                                issued
                                by CSC, DOLE and SSS)</p>
                        </div>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                            <p style="font-size: 5;margin: 0px;">
                                    {{ stripos($leaveTypeName, 'paternity') !== false ? 'X' : '' }}
                                </p>
                            </div>
                            <p style="font-size: 6;display: inline-block;"><b>Paternity Leave</b> (R.A. No. 8187 / CSC
                                MC
                                No.
                                71, s. 1998, as amended)</p>
                        </div>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                            <p style="font-size: 5;margin: 0px;">
                                    {{ stripos($leaveTypeName, 'special privilege') !== false ? 'X' : '' }}
                                </p>
                            </div>
                            <p style="font-size: 6;display: inline-block;"><b>Special Privilege Leave</b> (Sec. 21, Rule
                                XVI,
                                Omnibus Rules Implementing E.O. No. 292)</p>
                        </div>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                            <p style="font-size: 5;margin: 0px;">
                                    {{ stripos($leaveTypeName, 'solo parent') !== false ? 'X' : '' }}
                                </p>
                            </div>
                            <p style="font-size: 6;display: inline-block;"><b>Solo Parent Leave</b> (RA No. 8972 / CSC
                                MC
                                No.
                                8, s. 2004)</p>
                        </div>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                            <p style="font-size: 5;margin: 0px;">
                                    {{ stripos($leaveTypeName, 'study') !== false ? 'X' : '' }}
                                </p>
                            </div>
                            <p style="font-size: 6;display: inline-block;"><b>Study Leave</b> (Sec. 68, Rule XVI,
                                Omnibus
                                Rules
                                Implementing E.O. No. 292)</p>
                        </div>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                            <p style="font-size: 5;margin: 0px;">
                                    {{ stripos($leaveTypeName, 'vawc') !== false ? 'X' : '' }}
                                </p>
                            </div>
                            <p style="font-size: 6;display: inline-block;"><b>10-Day VAWC Leave</b> (RA No. 9262 / CSC
                                MC
                                No.
                                15, s. 2005)</p>
                        </div>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                            <p style="font-size: 5;margin: 0px;">
                                    {{ stripos($leaveTypeName, 'rehabilitation') !== false ? 'X' : '' }}
                                </p>
                            </div>
                            <p style="font-size: 6;display: inline-block;"><b>Rehabilitation Privilege</b> (Sec. 55,
                                Rule
                                XVI,
                                Omnibus Rules Implementing E.O. No. 292)</p>
                        </div>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                            <p style="font-size: 5;margin: 0px;">
                                    {{ stripos($leaveTypeName, 'special leave benefits') !== false ? 'X' : '' }}
                                </p>
                            </div>
                            <p style="font-size: 6;display: inline-block;"><b>Special Leave Benefits for Women</b> (RA
                                No.
                                9710
                                / CSC MC No. 25, s. 2010)</p>
                        </div>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                            <p style="font-size: 5;margin: 0px;">
                                    {{ stripos($leaveTypeName, 'emergency') !== false || stripos($leaveTypeName, 'calamity') !== false ? 'X' : '' }}
                                </p>
                            </div>
                            <p style="font-size: 6;display: inline-block;"><b>Special Emergency (Calamity) Leave</b>
                                (CSC MC
                                No. 2, s. 2012, as amended)</p>
                        </div>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                            <p style="font-size: 5;margin: 0px;">
                                    {{ stripos($leaveTypeName, 'adoption') !== false ? 'X' : '' }}
                                </p>
                            </div>
                            <p style="font-size: 6;display: inline-block;"><b>Adoption Leave</b> (R.A. No. 8552)</p>
                        </div>
                        <p style="margin-top: 10px;font-size: 8;"><i>Others:</i></p>
                        <p style="border-bottom: solid 1px black;margin-top: 20px;margin-bottom: 5px; width: 200px;font-size: 8;">
                            {{ !in_array($leaveTypeId, $predefinedLeaveTypeIds, true) ? $leaveTypeName : '' }}
                        </p>
                    </td>
                    <td style="padding: 5px;width: 100px;border-left: solid 1px black;">
                        <p style="font-size: 8;font-weight: bold;margin-bottom: 5px;">6.B DETAILS OF LEAVE </p>
                        <p style="font-size: 6;">In case of Vacation/Special Privilege Leave:
                        </p>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                                <p style="font-size: 5;margin: 0px;">
                                    {{ $dtl->incase_vacation_leave_id == 1 ? 'X' : '' }}
                                </p>
                            </div>
                            <p style="font-size: 6;display: inline-block;">Within the Philippines</p>
                            <p style="font-size: 8;display: inline-block;border-bottom: solid 1px black;width: 150px;">
                                {{ $dtl->incase_vacation_leave_id == 1 ? $dtl->incase_vacation_leave_specify : '' }}
                            </p>
                        </div>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                                <p style="font-size: 5;margin: 0px;">
                                    {{ $dtl->incase_vacation_leave_id == 2 ? 'X' : '' }}
                                </p>
                            </div>
                            <p style="font-size: 6;display: inline-block;">Abroad (Specify)</p>
                            <p style="font-size: 8;display: inline-block;border-bottom: solid 1px black;width: 150px;">
                                {{ $dtl->incase_vacation_leave_id == 2 ? $dtl->incase_vacation_leave_specify : '' }}
                            </p>
                        </div>
                        <br>
                        <p style="font-size: 6;">In case of Sick Leave:</p>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                                <p style="font-size: 5;margin: 0px;">
                                    {{ $dtl->incase_sick_leave_id == 1 ? 'X' : '' }}
                                </p>
                            </div>
                            <p style="font-size: 6;display: inline-block;">In Hospital (Specify Illness)</p>
                            <p style="font-size: 8;display: inline-block;border-bottom: solid 1px black;width: 100px;">
                                {{ $dtl->incase_sick_leave_id == 1 ? $dtl->incase_sick_leave_specify : '' }}
                            </p>
                        </div>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                                <p style="font-size: 5;margin: 0px;">
                                    {{ $dtl->incase_sick_leave_id == 2 ? 'X' : '' }}
                                </p>
                            </div>
                            <p style="font-size: 6;display: inline-block;">Out Patient (Specify Illness)</p>
                            <p style="font-size: 8;display: inline-block;border-bottom: solid 1px black;width: 100px;">
                                {{ $dtl->incase_sick_leave_id == 2 ? $dtl->incase_sick_leave_specify : '' }}
                            </p>
                        </div>
                        <br>
                        <p style="font-size: 6;">In case of Special Leave Benefits for Women:
                        </p>
                        <div style="vertical-align: middle;">
                            <p style="font-size: 6;display: inline-block;">(Specify Illness)</p>
                            <p style="font-size: 8;display: inline-block;border-bottom: solid 1px black;width: 150px;">
                                {{ $dtl->incase_special_leave_specify }}
                            </p>
                        </div>
                        <br>
                        <p style="font-size: 6;">In case of Study Leave:</p>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                                <p style="font-size: 5;margin: 0px;">
                                    {{ $dtl->incase_study_leave_id == 1 ? 'X' : '' }}
                                </p>
                            </div>
                            <p style="font-size: 6;display: inline-block;">Completion of Master's Degree</p>
                        </div>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                                <p style="font-size: 5;margin: 0px;">
                                    {{ $dtl->incase_study_leave_id == 2 ? 'X' : '' }}
                                </p>
                            </div>
                            <p style="font-size: 6;display: inline-block;">BAR/Board Examination Review</p>
                        </div>
                        <br>
                        <p style="font-size: 6;">Other purpose:</p>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                                <p style="font-size: 5;margin: 0px;">
                                    {{ $dtl->other_purpose_id == 1 ? 'X' : '' }}
                                </p>
                            </div>
                            <p style="font-size: 6;display: inline-block;">Monetization of Leave Credits</p>
                        </div>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                                <p style="font-size: 5;margin: 0px;">
                                    {{ $dtl->other_purpose_id == 2 ? 'X' : '' }}
                                </p>
                            </div>
                            <p style="font-size: 6;display: inline-block;">Terminal Leave</p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 5px;width: 100px;border-top: solid 1px black;">
                        <p style="font-size: 6;font-weight: bold;">6.C NUMBER OF WORKING DAYS APPLIED FOR</p>
                        <div>
                            <p
                                style="margin-left: 15px;font-size: 6;text-align: center;border-bottom: solid 1px black;margin-top: 20px;width: 180px;">
                                {{-- {{ (new DateTime($dtl->date_from))->diff(new DateTime($dtl->date_to))->days + 1 }}
                            --}}
                                {{ in_array((int)($dtl->other_purpose_id ?? 0), [1, 2], true) ? '' : ($data_with_pay[0]->total_applied_days ?? '') }}
                            </p>
                        </div>
                        <p style="margin-left: 15px;margin-top: 15px;font-size: 6;font-weight: bold;">INCLUSIVE DATES
                        </p>
                        <div>
                            <p
                                style="margin-left: 15px;font-size: 6;text-align: center;border-bottom: solid 1px black;margin-top: 8px;width: 180px;">
                                {{ in_array((int)($dtl->other_purpose_id ?? 0), [1, 2], true) ? '' : ($dtl->date_from == $dtl->date_to ? date('m-d-Y', strtotime($dtl->date_from)) : date('m-d-Y', strtotime($dtl->date_from)) . ' - ' . date('m-d-Y', strtotime($dtl->date_to))) }}
                            </p>
                        </div>
                    </td>
                    <td style="padding: 5px;width: 100px;border-left: solid 1px black;border-top: solid 1px black;">
                        <p style="font-size: 6;font-weight: bold;">6.D COMMUTATION</p>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                                <p style="font-size: 5;margin: 0px;">{{ $dtl->commutation_id == 2 ? 'X' : '' }}</p>
                            </div>
                            <p style="font-size: 6;display: inline-block;">Not Requested</p>
                        </div>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                                <p style="font-size: 5;margin: 0px;">{{ $dtl->commutation_id == 1 ? 'X' : '' }}</p>
                            </div>
                            <p style="font-size: 6;display: inline-block;">Requested</p>
                        </div>
                        <div>
                            <p style="font-size: 6;text-align: center;border-top: solid 1px black;margin-top: 15px;">
                                (Signature of Applicant)</p>
                        </div>
                    </td>
                </tr>
            </table>
            <table
                style="border: solid 1px black;border-collapse: collapse;width: 100%;margin-top: 0px;border-top: none;">
                <tr>
                    <td style="padding: 5px;width: 200px;" colspan="2">
                        <p style="font-size: 9;font-weight: bold;text-align: center;">7. DETAILS OF ACTION ON
                            APPLICATION</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 5px;width: 100px;border-top: solid 1px black;">
                        <p style="font-size: 6;font-weight: bold;">7.A CERTIFICATION OF LEAVE CREDITS</p>
                        <div style="text-align: center;">
                            <p style="font-size: 6;margin-top: 20px;display: inline-block;">
                                As of </p>
                            <p
                                style="font-size: 6;border-bottom: solid 1px black;margin-top: 20px;display: inline-block;width: 100px;">
                                {{ date('m-d-Y', strtotime(now())) }}
                            </p>
                        </div>
                        <div>
                            <table
                                style="border: solid 1px black;border-collapse: collapse;width: 100%;margin-top: 3px;">
                                <tr>
                                    <td style="border: solid 1px black;border-collapse: collapse;"></td>
                                    <td
                                        style="border: solid 1px black;border-collapse: collapse;font-size: 6;text-align: center;">
                                        Vacation Leave</td>
                                    <td
                                        style="border: solid 1px black;border-collapse: collapse;font-size: 6;text-align: center;">
                                        Sick Leave</td>
                                </tr>
                                @php
                                    $printLeaveTypeName = $leave[0]->leave_type_name ?? $leave[0]->leave_type ?? '';
                                    $isVacationType = stripos($printLeaveTypeName, 'vacation') !== false;
                                    $isSickType = stripos($printLeaveTypeName, 'sick') !== false;
                                    $withPayDays = $data_with_pay[0]->with_pay ?? 0;

                                    $vlCreditsRaw = $leave_credeits_vl[0]->credits ?? 0;
                                    $slCreditsRaw = isset($leave_credeits_sl[0]) ? $leave_credeits_sl[0]->credits : 0;

                                    // Display "Total Earned" as original credits:
                                    // - Before approval: show current credits.
                                    // - After full approval (when leave_credits already deducted): add back with_pay
                                    //   for the relevant leave type so the numbers remain the same as pre-approval.
                                    $vlTotalEarned = $vlCreditsRaw;
                                    $slTotalEarned = $slCreditsRaw;

                                    if (!empty($isFullyApproved)) {
                                        if ($isVacationType) {
                                            $vlTotalEarned += $withPayDays;
                                        }
                                        if ($isSickType) {
                                            $slTotalEarned += $withPayDays;
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td
                                        style="border: solid 1px black;border-collapse: collapse;font-size: 6;text-align: center;font-style: italic;">
                                        Total Earned</td>
                                    <td
                                        style="border: solid 1px black;border-collapse: collapse;font-size: 6;text-align: center;">
                                        @if ($vlTotalEarned < 0)
                                            {{ number_format(0, 3, '.', ',') }}
                                        @else
                                            {{ number_format($vlTotalEarned, 3, '.', ',') }}
                                        @endif
                                    </td>
                                    <td
                                        style="border: solid 1px black;border-collapse: collapse;font-size: 6;text-align: center;">
                                        @if (!isset($leave_credeits_sl))
                                            {{ number_format(0, 3, '.', ',') }}
                                        @else
                                            {{ number_format($slTotalEarned, 3, '.', ',') }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        style="border: solid 1px black;border-collapse: collapse;font-size: 6;text-align: center;font-style: italic;">
                                        Less this
                                        application</td>
                                    <td
                                        style="border: solid 1px black;border-collapse: collapse;font-size: 6;text-align: center;">
                                        @php
                                            $isMonetization = (int) ($dtl->monetization ?? 0) === 1;
                                            $monetizationAmount = (float) ($dtl->monetization_amount ?? 0);
                                        @endphp
                                        @if ($isVacationType)
                                            {{ number_format($isMonetization ? $monetizationAmount : $withPayDays, 3, '.', ',') }}
                                        @else
                                            {{ number_format(0, 3, '.', ',') }}
                                        @endif
                                    </td>
                                    <td
                                        style="border: solid 1px black;border-collapse: collapse;font-size: 6;text-align: center;">
                                        @if ($isSickType)
                                            {{ number_format($isMonetization ? $monetizationAmount : $withPayDays, 3, '.', ',') }}
                                        @else
                                            {{ number_format(0, 3, '.', ',') }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        style="border: solid 1px black;border-collapse: collapse;font-size: 6;text-align: center;font-style: italic;">
                                        Balance</td>
                                    <td
                                        style="border: solid 1px black;border-collapse: collapse;font-size: 6;text-align: center;">
                                        @if ($isVacationType)
                                            @php
                                                $isMonetization = (int) ($dtl->monetization ?? 0) === 1;
                                                $monetizationAmount = (float) ($dtl->monetization_amount ?? 0);
                                                // Before approval: show credits minus this application.
                                                // After full approval: leave_credits already deducted, so show current credits.
                                                $vlBalance = $vlCreditsRaw;
                                                if ($isMonetization) {
                                                    // Monetization does not deduct leave_credits even when fully approved,
                                                    // so balance must be based on Total Earned - Less this application.
                                                    $vlBalance = $vlTotalEarned - $monetizationAmount;
                                                } elseif (empty($isFullyApproved)) {
                                                    $vlBalance = $vlCreditsRaw - $withPayDays;
                                                }
                                            @endphp
                                            {{ number_format(max(0, $vlBalance), 3, '.', ',') }}
                                        @else
                                            {{ number_format($vlCreditsRaw, 3, '.', ',') }}
                                        @endif
                                    </td>
                                    <td
                                        style="border: solid 1px black;border-collapse: collapse;font-size: 6;text-align: center;">
                                        @if ($isSickType)
                                            @if (!isset($leave_credeits_sl))
                                                {{ number_format(0, 3, '.', ',') }}
                                            @else
                                                @php
                                                    $isMonetization = (int) ($dtl->monetization ?? 0) === 1;
                                                    $monetizationAmount = (float) ($dtl->monetization_amount ?? 0);
                                                    $slBalance = $slCreditsRaw;
                                                    if ($isMonetization) {
                                                        // Monetization does not deduct leave_credits even when fully approved,
                                                        // so balance must be based on Total Earned - Less this application.
                                                        $slBalance = $slTotalEarned - $monetizationAmount;
                                                    } elseif (empty($isFullyApproved)) {
                                                        $slBalance = $slCreditsRaw - $withPayDays;
                                                    }
                                                @endphp
                                                {{ number_format(max(0, $slBalance), 3, '.', ',') }}
                                            @endif
                                        @else
                                            {{ number_format($slCreditsRaw, 3, '.', ',') }}
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div>
                            <p style="font-size: 6;text-align: center;margin-top: 20px;">
                                {{ $leave_signatories[0]->approver_1 ?? '' }}
                            </p>
                            <p style="font-size: 6;text-align: center;border-top: solid 1px black;">
                                (Authorized Officer)</p>
                        </div>
                    </td>
                    <td style="padding: 5px;width: 100px;border-left: solid 1px black;border-top: solid 1px black;">
                        <p style="font-size: 6;font-weight: bold;">7.B RECOMMENDATION</p>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                                <p style="font-size: 5;margin: 0px;">{{ $leave[0]->approved_2 == 1 ? 'X' : '' }}</p>
                            </div>
                            <p style="font-size: 6;display: inline-block;">For Approval</p>
                            <p style="font-size: 8;display: inline-block;border-bottom: solid 1px black;width: 150px;">
                                {{ $leave[0]->approved_2_remarks }}
                            </p>
                        </div>
                        <div style="vertical-align: middle;">
                            <div
                                style="margin: 2px;border: solid 1px black;width: 10px;height: 10px;display: inline-block;text-align: center;">
                                <p style="font-size: 8;margin: 0px;">{{ $leave[0]->disapproved_2 == 1 ? 'X' : '' }}</p>
                            </div>
                            <p style="font-size: 6;display: inline-block;">For disapproval due to</p>
                            <p style="font-size: 8;display: inline-block;border-bottom: solid 1px black;width: 150px;">
                                {{ $leave[0]->disapproved_2_remarks }}
                            </p>
                        </div>
                        <div>
                            <p style="font-size: 6;text-align: center;border-top: solid 1px black;margin-top: 10px;">
                            <p style="font-size: 6;text-align: center;border-top: solid 1px black;margin-top: 10px;">
                            <p style="font-size: 6;text-align: center;border-top: solid 1px black;margin-top: 10px;">
                        </div>
                        <div>
                            <p style="font-size: 6;text-align: center;margin-top: 20px;">
                                {{ $leave_signatories[0]->approver_2 ?? '' }}
                            </p>
                            <p style="font-size: 6;text-align: center;border-top: solid 1px black;">
                                (Authorized Officer)</p>
                        </div>
                    </td>
                </tr>
            </table>
            <table
                style="border: solid 1px black;border-collapse: collapse;width: 100%;margin-top: 0px;border-top: none;">
                <tr>
                    <td
                        style="padding: 5px;width: 100px;border-top: solid 1px black;border-right: none;padding-left: 15px;">
                        <p style="font-size: 6;font-weight: bold;">7.C APPROVED FOR:</p>
                        <div>
                            <p
                                style="text-align: center;font-size: 6;border-bottom: solid 1px black;margin-top: 5px;display: inline-block;width: 80px;">
                                {{ number_format($data_with_pay[0]->with_pay, 2, '.', ',') }}
                            </p>
                            <p style="font-size: 6;margin-top: 5px;display: inline-block;">
                                days with pay</p>
                        </div>
                        <div>
                            <p
                                style="text-align: center;font-size: 6;border-bottom: solid 1px black;margin-top: 5px;display: inline-block;width: 80px;">
                                {{ number_format($data_with_pay[0]->without_pay, 2, '.', ',') }}
                            </p>
                            <p style="font-size: 6;margin-top: 5px;display: inline-block;">
                                days without pay</p>
                        </div>
                        <div>
                            <p
                                style="text-align: center;font-size: 6;border-bottom: solid 1px black;margin-top: 5px;display: inline-block;width: 80px;">
                            </p>
                            <p style="font-size: 6;margin-top: 5px;display: inline-block;">
                                others (Specify)</p>
                        </div>
                    </td>
                    <td
                        style="padding: 5px;width: 100px;border-left: solid 1px black;border-top: solid 1px black;border-left: none;">
                        <p style="font-size: 6;font-weight: bold;">7.D DISAPPROVED DUE TO:</p>
                        <div>
                            <p style="font-size: 8;margin-bottom: 0px;margin-top: 5px;text-align: center;">
                                {{ $leave[0]->disapproved_3_remarks }}
                            </p>
                            <p style="font-size: 6;text-align: center;border-top: solid 1px black;margin-top: 10px;">
                            </p>
                            <p style="font-size: 6;text-align: center;border-top: solid 1px black;margin-top: 10px;">
                            </p>
                            <p style="font-size: 6;text-align: center;border-top: solid 1px black;margin-top: 10px;">
                            </p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: center;">
                        <div style="margin-top: 10px;margin-bottom: 5px;">
                            <p style="font-size: 6;text-align: center;margin:0px;">
                                @if ($data_with_pay[0]->total_applied_days > 3)
                                    {{ $leave_signatories[0]->approver_4 ?? '' }}
                                @else
                                    {{ $leave_signatories[0]->approver_3 ?? '' }}
                                @endif
                            </p>
                            <p
                                style="font-size: 6;text-align: center;border-top: solid 1px black;margin: auto;width: 200px">
                                (Authorized Official)</p>
                        </div>
                    </td>
                </tr>
            </table>
            <br>
            <br>
            <br>
            <br>
            <br>
            {{-- Page 2 --}}
            <table style="width: 100%;margin-top: 40px;border: solid 1px black;border-collapse: collapse;">
                <tr>
                    <td colspan="3" style="border: solid 1px black;border-collapse: collapse;">
                        <div style="padding: 5px;">
                            <p style="font-size: 6;font-weight: bold;text-align: center;">INSTRUCTIONS AND REQUIREMENTS
                            </p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px;">
                        <p style="font-size: 8;text-align: justify;">
                            Application for any type of leave shall be made on this Form and to be
                            accomplished at least in duplicate with documentary requirements, as
                            follows:
                        </p>
                    </td>
                    <td style="width: 30px;border: solid 1px black;border-collapse: collapse;" rowspan="3"></td>
                    <td style="padding: 10px;">
                        <p style="font-size: 8;text-align: justify;">
                            TPO or PPO has been filed with the said office shall be sufficient
                            to support the application for the ten-day leave; or
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0px 10px 0px 10px;">
                        <p style="font-size: 8;font-weight: bold;text-align: justify;">
                            1. Vacation leave*
                        </p>
                        <p style="font-size: 8;text-align: justify;padding-left: 10px;">
                            It shall be filed five (5) days in advance, whenever possible, of the
                            effective date of such leave. Vacation leave within in the Philippines or
                            abroad shall be indicated in the form for purposes of securing travel
                            authority and completing clearance from money and work
                            accountabilities.
                        </p>
                    </td>
                    <td style="padding: 0px 10px 0px 10px;">
                        <p style="font-size: 8;text-align: justify;">
                            d. In the absence of the BPO/TPO/PPO or the certification, a police
                        </p>
                        <p style="font-size: 8;text-align: justify;padding-left: 10px;">
                            report specifying the details of the occurrence of violence on the
                            victim and a medical certificate may be considered, at the
                            discretion of the immediate supervisor of the woman employee
                            concerned.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0px 10px 0px 10px;">
                        <p style="font-size: 8;font-weight: bold;text-align: justify;">
                            2. Mandatory/Forced leave
                        </p>
                        <p style="font-size: 8;text-align: justify;padding-left: 10px;">
                            Annual five-day vacation leave shall be forfeited if not taken during the
                            year. In case the scheduled leave has been cancelled in the exigency
                            of the service by the head of agency, it shall no longer be deducted from
                            the accumulated vacation leave. Availment of one (1) day or more
                            Vacation Leave (VL) shall be considered for complying the
                            mandatory/forced leave subject to the conditions under Section 25, Rule
                            XVI of the Omnibus Rules Implementing E.O. No. 292.
                        </p>
                        <p style="font-size: 8;font-weight: bold;text-align: justify;margin-top: 5px">
                            3. Sick leave*
                        </p>
                        <ul style="margin: 0px 0px 0px 15px;padding: 0px;">
                            <li>
                                <p style="font-size: 8;text-align: justify;">
                                    It shall be filed immediately upon employee's return from such leave
                                </p>
                            </li>
                            <li>
                                <p style="font-size: 8;text-align: justify;">
                                    If filed in advance or exceeding five (5) days, application shall be
                                    accompanied by a medical certificate. In case medical consultation
                                    was not availed of, an affidavit should be executed by an applicant.
                                </p>
                            </li>
                        </ul>
                        <p style="font-size: 8;font-weight: bold;text-align: justify;margin-top: 5px">
                            4. Maternity leave* – 105 days
                        </p>
                        <ul style="margin: 0px 0px 0px 15px;padding: 0px;">
                            <li>
                                <p style="font-size: 8;text-align: justify;">
                                    Proof of pregnancy e.g. ultrasound, doctor’s certificate on the
                                    expected date of delivery
                                </p>
                            </li>
                            <li>
                                <p style="font-size: 8;text-align: justify;">
                                    Accomplished Notice of Allocation of Maternity Leave Credits (CS
                                    Form No. 6a), if needed
                                </p>
                            </li>
                            <li>
                                <p style="font-size: 8;text-align: justify;">
                                    Seconded female employees shall enjoy maternity leave with full pay
                                    in the recipient agency.
                                </p>
                            </li>
                        </ul>
                        <p style="font-size: 8;font-weight: bold;text-align: justify;margin-top: 5px">
                            5. Paternity leave – 7 days
                        </p>
                        <p style="font-size: 8;text-align: justify;margin-left: 10px;">
                            Proof of child’s delivery e.g. birth certificate, medical certificate and
                            marriage contract
                        </p>
                        <p style="font-size: 8;font-weight: bold;text-align: justify;margin-top: 5px">
                            6. Special Privilege leave – 3 days
                        </p>
                        <p style="font-size: 8;text-align: justify;margin-left: 10px;">
                            It shall be filed/approved for at least one (1) week prior to availment,
                            except on emergency cases. Special privilege leave within the
                            Philippines or abroad shall be indicated in the form for purposes of
                            securing travel authority and completing clearance from money and work
                            accountabilities.
                        </p>
                        <p style="font-size: 8;font-weight: bold;text-align: justify;margin-top: 5px">
                            7. Solo Parent leave – 7 days
                        </p>
                        <p style="font-size: 8;text-align: justify;margin-left: 10px;">
                            It shall be filed in advance or whenever possible five (5) days before
                            going on such leave with updated Solo Parent Identification Card.
                        </p>
                        <p style="font-size: 8;font-weight: bold;text-align: justify;margin-top: 5px">
                            8. Study leave* – up to 6 months
                        </p>
                        <ul style="margin: 0px 0px 0px 15px;padding: 0px;">
                            <li>
                                <p style="font-size: 8;text-align: justify;">
                                    Shall meet the agency’s internal requirements, if any;
                                </p>
                            </li>
                            <li>
                                <p style="font-size: 8;text-align: justify;">
                                    Contract between the agency head or authorized representative and
                                    the employee concerned.
                                </p>
                            </li>
                        </ul>
                        <p style="font-size: 8;font-weight: bold;text-align: justify;margin-top: 5px">
                            9. VAWC leave – 10 days
                        </p>
                        <ul style="margin: 0px 0px 0px 15px;padding: 0px;">
                            <li>
                                <p style="font-size: 8;text-align: justify;">
                                    It shall be filed in advance or immediately upon the woman
                                    employee’s return from such leave
                                </p>
                            </li>
                            <li>
                                <p style="font-size: 8;text-align: justify;">
                                    It shall be accompanied by any of the following supporting documents:
                                </p>
                                <p style="font-size: 8;text-align: justify;">
                                    a. Barangay Protection Order (BPO) obtained from the barangay;
                                </p>
                                <p style="font-size: 8;text-align: justify;">
                                    b. Temporary/Permanent Protection Order (TPO/PPO) obtained from
                                    the court;
                                </p>
                                <p style="font-size: 8;text-align: justify;">
                                    c. If the protection order is not yet issued by the barangay or the court,
                                    a certification issued by the Punong Barangay/Kagawad or
                                    Prosecutor or the Clerk of Court that the application for the BPO,
                                </p>
                            </li>
                        </ul>
                    </td>
                    <td style="padding: 0px 10px 0px 10px;">
                        <p style="font-size: 8;font-weight: bold;text-align: justify;">
                            10. Rehabilitation leave* – up to 6 months
                        </p>
                        <ul style="margin: 0px 0px 0px 15px;padding: 0px;">
                            <li>
                                <p style="font-size: 8;text-align: justify;">
                                    Application shall be made within one (1) week from the time of the
                                    accident except when a longer period is warranted.
                                </p>
                            </li>
                            <li>
                                <p style="font-size: 8;text-align: justify;">
                                    Letter request supported by relevant reports such as the police
                                    report, if any,
                                </p>
                            </li>
                            <li>
                                <p style="font-size: 8;text-align: justify;">
                                    Medical certificate on the nature of the injuries, the course of
                                    treatment involved, and the need to undergo rest, recuperation, and
                                    rehabilitation, as the case may be.
                                </p>
                            </li>
                            <li>
                                <p style="font-size: 8;text-align: justify;">
                                    Written concurrence of a government physician should be obtained
                                    relative to the recommendation for rehabilitation if the attending
                                    physician is a private practitioner, particularly on the duration of the
                                    period of rehabilitation.
                                </p>
                            </li>
                        </ul>
                        <p style="font-size: 8;font-weight: bold;text-align: justify;">
                            11. Special leave benefits for women* – up to 2 months
                        </p>
                        <ul style="margin: 0px 0px 0px 15px;padding: 0px;">
                            <li>
                                <p style="font-size: 8;text-align: justify;">
                                    The application may be filed in advance, that is, at least five (5) days
                                    prior to the scheduled date of the gynecological surgery that will be
                                    undergone by the employee. In case of emergency, the application
                                    for special leave shall be filed immediately upon employee’s return
                                    but during confinement the agency shall be notified of said surgery.
                                </p>
                            </li>
                            <li>
                                <p style="font-size: 8;text-align: justify;">
                                    The application shall be accompanied by a medical certificate filled
                                    out by the proper medical authorities, e.g. the attending surgeon
                                    accompanied by a clinical summary reflecting the gynecological
                                    disorder which shall be addressed or was addressed by the said
                                    surgery; the histopathological report; the operative technique used
                                    for the surgery; the duration of the surgery including the perioperative period
                                    (period of confinement around surgery);
                                    as well as
                                    the employees estimated period of recuperation for the same.
                                </p>
                            </li>
                        </ul>
                        <p style="font-size: 8;font-weight: bold;text-align: justify;">
                            12. Special Emergency (Calamity) leave – up to 5 days
                        </p>
                        <ul style="margin: 0px 0px 0px 15px;padding: 0px;">
                            <li>
                                <p style="font-size: 8;text-align: justify;">
                                    The special emergency leave can be applied for a maximum of five
                                    (5) straight working days or staggered basis within thirty (30) days
                                    from the actual occurrence of the natural calamity/disaster. Said
                                    privilege shall be enjoyed once a year, not in every instance of
                                    calamity or disaster
                                </p>
                            </li>
                            <li>
                                <p style="font-size: 8;text-align: justify;">
                                    The head of office shall take full responsibility for the grant of special
                                    emergency leave and verification of the employee’s eligibility to be
                                    granted thereof. Said verification shall include: validation of place of
                                    residence based on latest available records of the affected
                                    employee; verification that the place of residence is covered in the
                                    declaration of calamity area by the proper government agency; and
                                    such other proofs as may be necessary.
                                </p>
                            </li>
                        </ul>
                        <p style="font-size: 8;font-weight: bold;text-align: justify;">
                            13. Monetization of leave credits
                        </p>
                        <p style="font-size: 8;text-align: justify;">
                            Application for monetization of fifty percent (50%) or more of the
                            accumulated leave credits shall be accompanied by letter request to
                            the head of the agency stating the valid and justifiable reasons.
                        </p>
                        <p style="font-size: 8;font-weight: bold;text-align: justify;">
                            14. Terminal leave*
                        </p>
                        <p style="font-size: 8;text-align: justify;">
                            Proof of employee’s resignation or retirement or separation from the
                            service.
                        </p>
                        <p style="font-size: 8;font-weight: bold;text-align: justify;">
                            15. Adoption Leave
                        </p>
                        <ul style="margin: 0px 0px 0px 15px;padding: 0px;">
                            <li>
                                <p style="font-size: 8;text-align: justify;">
                                    Application for adoption leave shall be filed with an authenticated
                                    copy of the Pre-Adoptive Placement Authority issued by the
                                    Department of Social Welfare and Development (DSWD).
                                </p>
                            </li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="border: solid 1px black;border-collapse: collapse;">
                        <p style="font-size: 8;text-align: justify;margin-top: 5px;">
                            * For leave of absence for thirty (30) calendar days or more and terminal leave, application
                            shall be accompanied by a
                            clearance from money, property and
                            work-related accountabilities (pursuant to CSC Memorandum Circular No. 2, s. 1985)
                        </p>
                    </td>
                </tr>
            </table>
        </div>
    @endforeach
</body>

</html>
