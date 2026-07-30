<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">

    <title>Service Record</title>

    <style>
        html,
        body {
            height: 297mm;
            width: 210mm;
            margin-top: 15px;
            margin-bottom: 15px;
            margin-left: auto;
            margin-right: auto;
        }

        p {
            padding: 0;
            margin: 0;
        }

        .main_table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .main_table th,
        .main_table td {
            border: 1px solid #000;
            padding: 3px 4px;
            text-align: center;
            vertical-align: middle;
            font-size: 9px;
        }

        .main_table th {
            font-weight: bold;
        }

        .main_table .header-stacked {
            padding: 0;
            vertical-align: middle;
        }

        .main_table .header-stacked table {
            width: 100%;
            border-collapse: collapse;
        }

        .main_table .header-stacked td {
            border: none;
            padding: 3px 4px;
            text-align: center;
            font-weight: bold;
            font-size: 9px;
            line-height: 1.2;
        }

        .main_table .text-left {
            text-align: left;
        }

        .main_table .text-right {
            text-align: right;
        }

        .main_table .salary-header {
            font-size: 8px;
            font-weight: normal;
        }

        .main_table .nothing-follows {
            font-style: italic;
            letter-spacing: 1px;
        }

        .field_table {
            width: 70%;
            border-collapse: collapse;
        }

        .field_table.name_table {
            margin-top: 20px;
        }

        .field_table.birth_table {
            margin-bottom: 20px;
        }

        .field_table td {
            border: none;
            padding: 0 8px 2px 8px;
            vertical-align: bottom;
        }

        .field_table .field_label_col {
            width: 50px;
            padding: 0;
            vertical-align: top;
        }

        .field_table .field_value {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            border-bottom: 1px solid #000;
            min-width: 120px;
        }

        .field_table .field_desc {
            font-size: 10px;
            text-align: center;
            padding-top: 2px;
        }

        .field_table.birth_table .field_gap {
            border: none;
            padding: 0;
            width: 60px;
            min-width: 60px;
        }

        .field_table.birth_table .birth_date {
            min-width: 140px;
        }

        .field_table.birth_table .birth_place {
            min-width: 140px;
        }

        .letterhead {
            text-align: center;
            font-family: 'Times New Roman', Times, serif;
        }

        .letterhead .org-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 4px 0;
        }

        .letterhead .org-address {
            font-size: 18px;
            font-weight: normal;
            margin: 0;
            line-height: 1.35;
        }

        .service-record-title {
            text-align: center;
            margin: 40px 0 0 0;
            font-size: 22px;
            font-weight: bold;
        }

        .letterhead_underline {
            border-collapse: collapse;
            margin: 8px auto 0;
        }

        .letterhead_underline td {
            padding: 0;
            vertical-align: bottom;
        }

        .letterhead_underline .letterhead_box {
            border: 1px solid #000;
            width: 75px;
            height: 16px;
        }

        .letterhead_underline .letterhead_gap {
            width: 55px;
            border: none;
        }

        .service-record-footer {
            margin-top: 30px;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            width: 100%;
        }

        .service-record-footer td {
            vertical-align: top;
            border: none;
            padding: 0;
        }

        .certification-box {
            border: 1px solid #000;
            padding: 12px 15px 18px;
            text-align: center;
        }

        .certification-title {
            font-weight: bold;
            letter-spacing: 5px;
            margin: 0 0 14px 0;
            font-size: 13px;
        }

        .certification-body {
            margin: 0 0 18px 0;
            line-height: 1.55;
            font-size: 12px;
        }

        .certification-signatory {
            margin: 0;
            font-size: 12px;
            line-height: 1.45;
        }

        .certification-date {
            margin: 12px 0 0 0;
            text-align: left;
            font-size: 12px;
        }

        .certified-correct {
            padding: 60px 0 0 25px;
            font-size: 12px;
        }

        .certified-correct .certified-name {
            font-weight: bold;
            margin: 6px 0 4px 0;
        }
    </style>
</head>

<body>
    <!-- Main content -->
    @foreach ($employees as $employee)
    <div class="card p-4">
        <div style="margin-right: 1in;margin-left: 0.8in;">
            <div>

                <div class="letterhead">
                    <h1 class="org-name" >
                        {{ strtoupper($orgCompanyName) }}</h1>
                    <p class="org-address">{{ $orgCompanyAddress }}</p>

                </div>
                <h2 class="service-record-title">SERVICE RECORD</h2>

            </div>
            <div>
                <div>
                    <!-- Name -->
                    <table class="field_table name_table">
                        <tbody>
                            <tr>
                                <td class="field_label_col" rowspan="2">
                                    <p style="font-size: 14px;">NAME:</p>
                                </td>
                                <td class="field_value">{{ strtoupper($employee->last_name ?? '') }}</td>
                                <td class="field_value">{{ strtoupper($employee->first_name ?? '') }}</td>
                                <td class="field_value">{{ strtoupper($employee->middle_name ?? '') }}</td>
                            </tr>
                            <tr>
                                <td class="field_desc">SURNAME</td>
                                <td class="field_desc">GIVEN NAME</td>
                                <td class="field_desc">MIDDLE NAME</td>
                            </tr>
                        </tbody>
                    </table>
                    <!-- Birth -->
                    <table class="field_table birth_table">
                        <tbody>
                            <tr>
                                <td class="field_label_col" rowspan="2">
                                    <p style="font-size: 14px;">BIRTH:</p>
                                </td>
                                <td class="field_value birth_date">
                                    {{ $employee->birthdate ? date('F j, Y', strtotime($employee->birthdate)) : '' }}
                                </td>
                                <td class="field_gap"></td>
                                <td class="field_value birth_place">{{ strtoupper($employee->birth_place ?? '') }}</td>
                            </tr>
                            <tr>
                                <td class="field_desc">DATE</td>
                                <td class="field_gap"></td>
                                <td class="field_desc">PLACE</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div>
                    <table class="main_table">
                        <thead>
                            <tr>
                                <th colspan="2">SERVICE</th>
                                <th colspan="3">RECORD OF APPOINTMENT</th>
                                <th class="header-stacked" rowspan="2">
                                    <table>
                                        <tr><td>OFFICE / ENTITY</td></tr>
                                        <tr><td>STATION / PLACE</td></tr>
                                    </table>
                                </th>
                                <th class="header-stacked" rowspan="2">
                                    <table>
                                        <tr><td>DIVISION</td></tr>
                                        <tr><td>BRANCH</td></tr>
                                    </table>
                                </th>
                                <th class="header-stacked" rowspan="2">
                                    <table>
                                        <tr><td>LEAVE OF</td></tr>
                                        <tr><td>ABSENCE</td></tr>
                                    </table>
                                </th>
                                <th colspan="2" rowspan="2">SEPARATION</th>
                            </tr>
                            <tr>
                                <th colspan="2">Inclusive Date</th>
                                <th>DESIGNATION</th>
                                <th>STATUS</th>
                                <th>SALARY</th>
                            </tr>
                            <tr>
                                <th>FROM</th>
                                <th>TO</th>
                                <th></th>
                                <th></th>
                                <th><span class="salary-header">per annum</span></th>
                                <th></th>
                                <th></th>
                                <th>W/O PAY</th>
                                <th>DATE</th>
                                <th>REMARKS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $formatServiceDate = function ($date) {
                                    if (empty($date)) {
                                        return '';
                                    }
                                    $ts = strtotime($date);
                                    return $ts ? date('m/d/y', $ts) : $date;
                                };
                                $formatSeparationDate = function ($date) {
                                    if (empty($date)) {
                                        return '';
                                    }
                                    $ts = strtotime($date);
                                    return $ts ? date('d-M-y', $ts) : $date;
                                };
                            @endphp
                            @foreach ($service_records as $service_record)
                            @php
                                $prev = !$loop->first ? $service_records[$loop->index - 1] : null;
                                $sameAsPrev = function ($field) use ($prev, $service_record) {
                                    return $prev
                                        && ($service_record->{$field} ?? '') === ($prev->{$field} ?? '')
                                        && ($service_record->{$field} ?? '') !== '';
                                };
                            @endphp
                            <tr>
                                <td>{{ $formatServiceDate($service_record->start_date) }}</td>
                                <td>{{ $formatServiceDate($service_record->end_date) }}</td>
                                <td class="text-left">{{ $sameAsPrev('designation') ? '- do -' : ($service_record->designation ?? '') }}</td>
                                <td>{{ $sameAsPrev('employment_type') ? '- do -' : ($service_record->employment_type ?? '') }}</td>
                                <td class="text-right">
                                    @if(!empty($service_record->annual_salary))
                                        P{{ number_format($service_record->annual_salary, 2, '.', ',') }}
                                    @endif
                                </td>
                                <td class="text-left">{{ $sameAsPrev('place_of_assignment') ? '- do -' : ($service_record->place_of_assignment ?? '') }}</td>
                                <td>{{ $sameAsPrev('branch') ? '- do -' : ($service_record->branch ?? '') }}</td>
                                <td>{{ $sameAsPrev('leave_without_pay') ? '- do -' : ($service_record->leave_without_pay ?? '') }}</td>
                                <td>{{ $formatSeparationDate($service_record->separation_date) }}</td>
                                <td class="text-left">{{ $service_record->cause ?? '' }}</td>
                            </tr>
                            @endforeach
                            <tr>
                                <td colspan="4" class="nothing-follows">.................. nothing follows ..................</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <br>
            </div>

            @php
                $middleInitial = !empty($employee->middle_name)
                    ? strtoupper(substr(trim($employee->middle_name), 0, 1)) . '. '
                    : '';
                $employeeDisplayName = strtoupper(trim(($employee->first_name ?? '') . ' ' . $middleInitial . ($employee->last_name ?? '')));
                $employeeTitle = !empty($employee->name_prefix)
                    ? trim($employee->name_prefix)
                    : (($employee->gender_id ?? 0) == 1 ? 'Ms.' : 'Mr.');
                $employeePronoun = ($employee->gender_id ?? 0) == 1 ? 'her' : 'his';
            @endphp
            <table class="service-record-footer">
                <tr>
                    <td style="width: 50%;">
                        <div class="certification-box">
                            <p class="certification-title">C E R T I F I C A T I O N</p>
                            <p class="certification-body">
                                This is to certify that {{ $employeeTitle }} {{ $employeeDisplayName }}<br>
                                did not incur any leave of absence (sick/vacation)<br>
                                without pay during {{ $employeePronoun }} employment in {{ $orgBranchCode }}.
                            </p>
                            <p class="certification-signatory" style="text-align: right;">
                                {{ strtoupper($signatories['signatory'] ?? 'MARIA ANTONIETTE S. ZOILO') }}<br>
                                {{ $signatories['position'] ?? 'Administrative Officer V / HRMO III' }}
                            </p>
                        </div>
                        <p class="certification-date">Date:</p>
                    </td>
                    <td style="width: 42%;">
                        <div class="certified-correct" style = "margin-top: 125px;">
                            <p>Certified correct:</p>
                            <p class="certified-name">{{ strtoupper($signatories['signatory'] ?? 'MARIA ANTONIETTE S. ZOILO') }}</p>
                            <p>{{ $signatories['position'] ?? 'Administrative Officer V / HRMO III' }}</p>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        {{-- <p style="text-align: right;margin-bottom: 0px;margin-right: 20px; font-weight: bold;margin-top: 150px;">
            {{ $footer['document_no'] }}
        </p>
        <p style="text-align: right;margin-top: 0px;margin-right: 20px;font-weight: bold; position:relative ;">
            {{ $footer['revision'] }}
        </p> --}}
    </div>
    @endforeach
</body>

</html>
