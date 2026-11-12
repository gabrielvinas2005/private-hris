<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Employee 201 File (Personnel Record)</title>
    <style>
        @page {
            margin: 10mm 10mm 10mm 10mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5px;
            color: #111827;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        th,
        td {
            border: 1px solid #374151;
            padding: 3px 5px;
            vertical-align: middle;
        }

        .no-border td,
        .no-border th {
            border: none;
        }

        .header-company {
            font-size: 12px;
            font-weight: bold;
            text-align: left;
            border: none;
            padding: 1px 0;
        }

        .header-address {
            font-size: 8.5px;
            color: #374151;
            border: none;
            padding: 1px 0 4px 0;
        }

        .doc-title {
            font-size: 13px;
            font-weight: bold;
            background-color: #1e3a8a;
            color: #ffffff;
            padding: 5px 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .doc-subtitle {
            font-size: 8px;
            font-style: italic;
            color: #4b5563;
            padding: 2px 0 6px 0;
            border: none;
        }

        .section-bar {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            font-size: 9px;
            padding: 4px 6px;
            text-transform: uppercase;
        }

        .sub-bar {
            background-color: #dbeafe;
            color: #1e3a8a;
            font-weight: bold;
            font-size: 8.5px;
            padding: 3px 6px;
        }

        .tbl-header th {
            background-color: #e5e7eb;
            color: #111827;
            font-weight: bold;
            text-align: left;
            font-size: 8px;
        }

        .lbl {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #1f2937;
            width: 18%;
        }

        .val {
            background-color: #ffffff;
            color: #111827;
            width: 32%;
        }

        .photo-box {
            width: 90px;
            height: 90px;
            text-align: center;
            vertical-align: middle;
            font-size: 8px;
            color: #6b7280;
            background-color: #f9fafb;
        }

        .page-break {
            page-break-before: always;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .sig-line {
            border-top: 1px solid #111827;
            padding-top: 3px;
            text-align: center;
            font-weight: bold;
            font-size: 8px;
        }
    </style>
</head>

<body>
    @foreach ($info as $emp)
        @php
            $val = function ($v, $fallback = '') {
                return (!empty($v) && $v !== 'N/A') ? $v : $fallback;
            };
            $presentAddress = trim(implode(', ', array_filter([
                $val($emp->ra_house_no),
                $val($emp->ra_street),
                $val($emp->ra_village),
                $val($address['ra_brgy'] ?? $emp->ra_barangay),
                $val($address['ra_city'] ?? $emp->ra_city),
                $val($address['ra_province'] ?? $emp->ra_province),
                $val($address['ra_region'] ?? $emp->ra_region),
            ])));
            $permanentAddress = trim(implode(', ', array_filter([
                $val($emp->pa_house_no),
                $val($emp->pa_street),
                $val($emp->pa_village),
                $val($address['pa_brgy'] ?? $emp->pa_barangay),
                $val($address['pa_city'] ?? $emp->pa_city),
                $val($address['pa_province'] ?? $emp->pa_province),
                $val($address['pa_region'] ?? $emp->pa_region),
            ])));
            $movements = isset($service_records) ? $service_records : collect();
            $evals = isset($ipcr_details) ? $ipcr_details : collect();
        @endphp

        <!-- REPORT HEADER -->
        <table class="no-border" style="margin-bottom: 2px;">
            <tr>
                <td class="header-company">{{ $val($emp->company, '[COMPANY NAME, INC.]') }}</td>
            </tr>
            <tr>
                <td class="header-address">{{ $val($emp->branch, '[Street, Barangay, City, Province, ZIP]') }}</td>
            </tr>
            <tr>
                <td class="doc-title">EMPLOYEE 201 FILE (PERSONNEL RECORD)</td>
            </tr>
            <tr>
                <td class="doc-subtitle">Confidential &ndash; For HR use only</td>
            </tr>
        </table>

        <!-- TOP SUMMARY & PHOTO BLOCK -->
        <table>
            <tr>
                <td class="lbl" style="width: 16%;">Employee No.</td>
                <td class="val" style="width: 26%;">{{ $val($emp->employee_no) }}</td>
                <td class="lbl" style="width: 18%;">Date Prepared</td>
                <td class="val" style="width: 24%;">{{ date('Y-m-d') }}</td>
                <td rowspan="4" class="photo-box" style="width: 16%;">
                    @if (!empty($emp->photo) && $emp->photo !== 'N/A' && file_exists(public_path('images/employees/' . $emp->photo)))
                        <img src="{{ public_path('images/employees/' . $emp->photo) }}" style="max-width: 85px; max-height: 85px;" />
                    @else
                        2" x 2"<br>PHOTO
                    @endif
                </td>
            </tr>
            <tr>
                <td class="lbl">Position</td>
                <td class="val">{{ $val($emp->position) }}</td>
                <td class="lbl">Department</td>
                <td class="val">{{ $val($emp->department) }}</td>
            </tr>
            <tr>
                <td class="lbl">Employment Status</td>
                <td class="val">{{ $val($emp->employment_type) }}</td>
                <td class="lbl">Date Hired</td>
                <td class="val">{{ $val($emp->date_hired) }}</td>
            </tr>
            <tr>
                <td class="lbl">Immediate Supervisor</td>
                <td class="val">{{ $val($emp->immediate_supervisor ?? '') }}</td>
                <td class="lbl">Prepared by (HR)</td>
                <td class="val">HR Department</td>
            </tr>
        </table>

        <!-- A. PERSONAL INFORMATION -->
        <table>
            <tr>
                <td colspan="4" class="section-bar">A. PERSONAL INFORMATION</td>
            </tr>
            <tr>
                <td class="lbl">Last Name</td>
                <td class="val">{{ $val($emp->last_name) }}</td>
                <td class="lbl">First Name</td>
                <td class="val">{{ $val($emp->first_name) }}</td>
            </tr>
            <tr>
                <td class="lbl">Middle Name</td>
                <td class="val">{{ $val($emp->middle_name) }}</td>
                <td class="lbl">Suffix / Nickname</td>
                <td class="val">{{ $val($emp->suffix) }}</td>
            </tr>
            <tr>
                <td class="lbl">Date of Birth</td>
                <td class="val">{{ $val($emp->birthdate) }}</td>
                <td class="lbl">Age</td>
                <td class="val">{{ $val($emp->age) }}</td>
            </tr>
            <tr>
                <td class="lbl">Place of Birth</td>
                <td class="val">{{ $val($emp->birth_place) }}</td>
                <td class="lbl">Gender</td>
                <td class="val">{{ $val($emp->gender) }}</td>
            </tr>
            <tr>
                <td class="lbl">Civil Status</td>
                <td class="val">{{ $val($emp->civil_status) }}</td>
                <td class="lbl">Citizenship</td>
                <td class="val">{{ $val($emp->citizenship) }}</td>
            </tr>
            <tr>
                <td class="lbl">Religion</td>
                <td class="val">{{ $val($emp->religion) }}</td>
                <td class="lbl">Blood Type</td>
                <td class="val">{{ $val($emp->blood_type) }}</td>
            </tr>
            <tr>
                <td class="lbl">Height (cm)</td>
                <td class="val">{{ $val($emp->height) }}</td>
                <td class="lbl">Weight (kg)</td>
                <td class="val">{{ $val($emp->weight) }}</td>
            </tr>
        </table>

        <!-- B. CONTACT & ADDRESS -->
        <table>
            <tr>
                <td colspan="4" class="section-bar">B. CONTACT &amp; ADDRESS</td>
            </tr>
            <tr>
                <td class="lbl">Present Address</td>
                <td colspan="3" class="val">{{ $presentAddress }}</td>
            </tr>
            <tr>
                <td class="lbl">Permanent Address</td>
                <td colspan="3" class="val">{{ $permanentAddress }}</td>
            </tr>
            <tr>
                <td class="lbl">Mobile No.</td>
                <td class="val">{{ $val($emp->mobile_no) }}</td>
                <td class="lbl">Landline</td>
                <td class="val">{{ $val($emp->telephone_no) }}</td>
            </tr>
            <tr>
                <td class="lbl">Personal Email</td>
                <td class="val">{{ $val($emp->email) }}</td>
                <td class="lbl">Company Email</td>
                <td class="val">{{ $val($emp->company_email ?? $emp->email) }}</td>
            </tr>
        </table>

        <!-- C. GOVERNMENT IDs & STATUTORY NUMBERS -->
        <table>
            <tr>
                <td colspan="4" class="section-bar">C. GOVERNMENT IDs &amp; STATUTORY NUMBERS</td>
            </tr>
            <tr>
                <td class="lbl">SSS No.</td>
                <td class="val">{{ $val($emp->sss_no) }}</td>
                <td class="lbl">PhilHealth PIN</td>
                <td class="val">{{ $val($emp->philhealth_no) }}</td>
            </tr>
            <tr>
                <td class="lbl">Pag-IBIG (HDMF) MID No.</td>
                <td class="val">{{ $val($emp->pagibig_no) }}</td>
                <td class="lbl">TIN</td>
                <td class="val">{{ $val($emp->tin_no) }}</td>
            </tr>
            <tr>
                <td class="lbl">Tax Status</td>
                <td class="val">{{ $val($emp->tax_status ?? 'Regular (Taxable)') }}</td>
                <td class="lbl">RDO Code</td>
                <td class="val">{{ $val($emp->rdo_code ?? '') }}</td>
            </tr>
            <tr>
                <td class="lbl">UMID / CRN</td>
                <td class="val">{{ $val($emp->crn ?? $emp->crn_no ?? '') }}</td>
                <td class="lbl">PhilSys National ID</td>
                <td class="val">{{ $val($emp->philsys_no ?? '') }}</td>
            </tr>
            <tr>
                <td class="lbl">Passport No.</td>
                <td class="val">{{ $val($emp->passport_no ?? '') }}</td>
                <td class="lbl">Passport Expiry</td>
                <td class="val">{{ $val($emp->passport_expiry ?? '') }}</td>
            </tr>
            <tr>
                <td class="lbl">Driver's License No.</td>
                <td class="val">{{ $val($emp->drivers_license_no ?? '') }}</td>
                <td class="lbl">License Expiry</td>
                <td class="val">{{ $val($emp->drivers_license_expiry ?? '') }}</td>
            </tr>
            <tr>
                <td class="lbl">PRC License No.</td>
                <td class="val">{{ $val($examinations[0]->license_no ?? '') }}</td>
                <td class="lbl">PRC Expiry</td>
                <td class="val">{{ $val($examinations[0]->date_of_validity ?? '') }}</td>
            </tr>
            <tr>
                <td class="lbl">NBI Clearance No.</td>
                <td class="val">{{ $val($emp->nbi_clearance_no ?? '') }}</td>
                <td class="lbl">Postal / Voter's ID</td>
                <td class="val">{{ $val($emp->postal_voters_id ?? '') }}</td>
            </tr>
        </table>

        <!-- D. EMPLOYMENT INFORMATION -->
        <table>
            <tr>
                <td colspan="4" class="section-bar">D. EMPLOYMENT INFORMATION</td>
            </tr>
            <tr>
                <td class="lbl">Company / Entity</td>
                <td class="val">{{ $val($emp->company) }}</td>
                <td class="lbl">Branch / Work Location</td>
                <td class="val">{{ $val($emp->branch) }}</td>
            </tr>
            <tr>
                <td class="lbl">Position Title</td>
                <td class="val">{{ $val($emp->position) }}</td>
                <td class="lbl">Job Level / Grade</td>
                <td class="val">{{ $val($emp->rank_level ?? $emp->grade) }}</td>
            </tr>
            <tr>
                <td class="lbl">Department</td>
                <td class="val">{{ $val($emp->department) }}</td>
                <td class="lbl">Division / Section</td>
                <td class="val">{{ trim($val($emp->division) . ' ' . $val($emp->section)) }}</td>
            </tr>
            <tr>
                <td class="lbl">Immediate Supervisor</td>
                <td class="val">{{ $val($emp->immediate_supervisor ?? '') }}</td>
                <td class="lbl">Employment Status</td>
                <td class="val">{{ $val($emp->employment_type) }}</td>
            </tr>
            <tr>
                <td class="lbl">Employment Type</td>
                <td class="val">{{ $val($emp->employment_type) }}</td>
                <td class="lbl">Work Schedule</td>
                <td class="val">{{ $val($emp->work_schedule ?? 'Regular') }}</td>
            </tr>
            <tr>
                <td class="lbl">Date Hired</td>
                <td class="val">{{ $val($emp->date_hired) }}</td>
                <td class="lbl">Probationary End Date</td>
                <td class="val">{{ $val($emp->probationary_end_date ?? '') }}</td>
            </tr>
            <tr>
                <td class="lbl">Regularization Date</td>
                <td class="val">{{ $val($emp->date_regularized ?? '') }}</td>
                <td class="lbl">Contract End Date</td>
                <td class="val">{{ $val($emp->contract_end_date ?? '') }}</td>
            </tr>
            <tr>
                <td class="lbl">Payroll Group / Frequency</td>
                <td class="val">{{ $val($emp->payroll_interval) }}</td>
                <td class="lbl">Mode of Payment</td>
                <td class="val">{{ $val($emp->payment_mode ?? 'Bank Transfer') }}</td>
            </tr>
            <tr>
                <td class="lbl">Monthly Basic Salary (PHP)</td>
                <td class="val">{{ is_numeric($emp->salary) ? number_format((float)$emp->salary, 2) : $val($emp->salary) }}</td>
                <td class="lbl">Monthly Allowances (PHP)</td>
                <td class="val">{{ $val($emp->allowances ?? '0.00') }}</td>
            </tr>
            <tr>
                <td class="lbl">Bank Name</td>
                <td class="val">{{ $val($emp->bank_name ?? '') }}</td>
                <td class="lbl">Bank Account No.</td>
                <td class="val">{{ $val($emp->bank_account_no ?? '') }}</td>
            </tr>
            <tr>
                <td class="lbl">HMO / Insurance Provider</td>
                <td class="val">{{ $val($emp->hmo_provider ?? '') }}</td>
                <td class="lbl">HMO / Insurance Card No.</td>
                <td class="val">{{ $val($emp->hmo_card_no ?? '') }}</td>
            </tr>
            <tr>
                <td class="lbl">Separation Date</td>
                <td class="val">{{ $val($emp->date_separated ?? '') }}</td>
                <td class="lbl">Separation Reason</td>
                <td class="val">{{ $val($emp->separation_reason ?? '') }}</td>
            </tr>
            <tr>
                <td class="lbl">Clearance Status</td>
                <td class="val">{{ $val($emp->clearance_status ?? '') }}</td>
                <td class="lbl">Last Pay Release Date</td>
                <td class="val">{{ $val($emp->last_pay_date ?? '') }}</td>
            </tr>
        </table>

        <!-- D (Sub). Compensation & Movement History -->
        <table>
            <tr>
                <td colspan="6" class="sub-bar">Compensation &amp; Movement History (hire, promotion, transfer, salary adjustment)</td>
            </tr>
            <tr class="tbl-header">
                <th style="width: 14%;">Effective Date</th>
                <th style="width: 16%;">Movement Type</th>
                <th style="width: 20%;">Position</th>
                <th style="width: 18%;">Department</th>
                <th style="width: 14%;" class="text-right">Basic Salary (PHP)</th>
                <th style="width: 18%;">Remarks / Memo No.</th>
            </tr>
            @php $movCount = max(4, count($movements)); @endphp
            @for ($i = 0; $i < $movCount; $i++)
                @php $m = $movements[$i] ?? null; @endphp
                <tr>
                    <td>{{ $m ? $val($m->start_date) : '' }}&nbsp;</td>
                    <td>{{ $m ? $val($m->movement_type ?? $m->cause ?? $m->employment_type) : '' }}</td>
                    <td>{{ $m ? $val($m->designation) : '' }}</td>
                    <td>{{ $m ? $val($m->place_of_assignment ?? $m->branch) : '' }}</td>
                    <td class="text-right">
                        @if ($m && !empty($m->annual_salary))
                            {{ number_format((float)$m->annual_salary / 12, 2) }}
                        @endif
                    </td>
                    <td>{{ $m ? $val($m->cause) : '' }}</td>
                </tr>
            @endfor
        </table>

        <!-- E. FAMILY BACKGROUND -->
        <table>
            <tr>
                <td colspan="4" class="section-bar">E. FAMILY BACKGROUND</td>
            </tr>
            <tr>
                <td colspan="4" class="sub-bar">Spouse</td>
            </tr>
            <tr>
                <td class="lbl">Spouse Name</td>
                <td class="val">{{ $val($emp->spouse_name) }}</td>
                <td class="lbl">Date of Birth</td>
                <td class="val">{{ $val($emp->spouse_birthdate ?? '') }}</td>
            </tr>
            <tr>
                <td class="lbl">Occupation</td>
                <td class="val">{{ $val($emp->spouse_occupation) }}</td>
                <td class="lbl">Employer</td>
                <td class="val">{{ $val($emp->spouse_employer) }}</td>
            </tr>
            <tr>
                <td class="lbl">Contact No.</td>
                <td class="val">{{ $val($emp->spouse_mobile_no) }}</td>
                <td class="lbl">Date of Marriage</td>
                <td class="val">{{ $val($emp->marriage_date ?? '') }}</td>
            </tr>
            <tr>
                <td colspan="4" class="sub-bar">Parents</td>
            </tr>
            <tr>
                <td class="lbl">Father's Name</td>
                <td class="val">{{ $val($emp->father_name) }}</td>
                <td class="lbl">Date of Birth</td>
                <td class="val">{{ $val($emp->father_birthdate ?? '') }}</td>
            </tr>
            <tr>
                <td class="lbl">Occupation</td>
                <td class="val">{{ $val($emp->father_occupation ?? '') }}</td>
                <td class="lbl">Living / Deceased</td>
                <td class="val">{{ $val($emp->father_status ?? '') }}</td>
            </tr>
            <tr>
                <td class="lbl">Mother's Maiden Name</td>
                <td class="val">{{ $val($emp->mother_name) }}</td>
                <td class="lbl">Date of Birth</td>
                <td class="val">{{ $val($emp->mother_birthdate ?? '') }}</td>
            </tr>
            <tr>
                <td class="lbl">Occupation</td>
                <td class="val">{{ $val($emp->mother_occupation ?? '') }}</td>
                <td class="lbl">Living / Deceased</td>
                <td class="val">{{ $val($emp->mother_status ?? '') }}</td>
            </tr>
        </table>

        <!-- Children / Dependents -->
        <table>
            <tr>
                <td colspan="5" class="sub-bar">Children / Dependents</td>
            </tr>
            <tr class="tbl-header">
                <th style="width: 38%;">Full Name</th>
                <th style="width: 18%;">Date of Birth</th>
                <th style="width: 12%;">Age</th>
                <th style="width: 16%;">Gender</th>
                <th style="width: 16%;">Dependent?</th>
            </tr>
            @php $childRows = max(3, count($children)); @endphp
            @for ($i = 0; $i < $childRows; $i++)
                @php $ch = $children[$i] ?? null; @endphp
                <tr>
                    <td>{{ $ch ? $val($ch->name) : '' }}&nbsp;</td>
                    <td>{{ $ch ? $val($ch->child_birthdate) : '' }}</td>
                    <td>{{ $ch ? $val($ch->age ?? '') : '' }}</td>
                    <td>{{ $ch ? $val($ch->gender ?? '') : '' }}</td>
                    <td>{{ $ch ? 'Yes' : '' }}</td>
                </tr>
            @endfor
        </table>

        <!-- Emergency Contact & Beneficiary -->
        <table>
            <tr>
                <td colspan="4" class="sub-bar">Emergency Contact &amp; Beneficiary</td>
            </tr>
            <tr>
                <td class="lbl">Contact Person</td>
                <td class="val">{{ $val($emp->emergency_contact_name ?? $emp->spouse_name) }}</td>
                <td class="lbl">Relationship</td>
                <td class="val">{{ $val($emp->emergency_contact_relationship ?? '') }}</td>
            </tr>
            <tr>
                <td class="lbl">Address</td>
                <td colspan="3" class="val">{{ $val($emp->emergency_contact_address ?? $presentAddress) }}</td>
            </tr>
            <tr>
                <td class="lbl">Contact No.</td>
                <td class="val">{{ $val($emp->emergency_contact_no ?? $emp->spouse_mobile_no) }}</td>
                <td class="lbl">Alternate No.</td>
                <td class="val"></td>
            </tr>
            <tr>
                <td class="lbl">Beneficiary (Insurance)</td>
                <td class="val">{{ $val($emp->beneficiary_name ?? '') }}</td>
                <td class="lbl">Relationship</td>
                <td class="val">{{ $val($emp->beneficiary_relationship ?? '') }}</td>
            </tr>
        </table>

        <div class="page-break"></div>

        <!-- F. EDUCATIONAL BACKGROUND -->
        <table>
            <tr>
                <td colspan="5" class="section-bar">F. EDUCATIONAL BACKGROUND</td>
            </tr>
            <tr class="tbl-header">
                <th style="width: 16%;">Level</th>
                <th style="width: 34%;">School / University</th>
                <th style="width: 24%;">Course / Degree</th>
                <th style="width: 12%;">Year Graduated</th>
                <th style="width: 14%;">Honors</th>
            </tr>
            @php
                $eduLevels = [
                    'Elementary' => $educations_elem[0] ?? null,
                    'High School' => $educations_sec[0] ?? null,
                    'Vocational' => $educations_voc[0] ?? null,
                    'College' => $educations_col[0] ?? null,
                    'Postgraduate' => $educations_grad[0] ?? null,
                ];
            @endphp
            @foreach ($eduLevels as $lvlName => $ed)
                <tr>
                    <td style="font-weight: bold; background-color: #f9fafb;">{{ $lvlName }}</td>
                    <td>{{ $ed ? $val($ed->school_name) : '' }}</td>
                    <td>{{ $ed ? $val($ed->degree ?? $ed->program ?? '') : '' }}</td>
                    <td>{{ $ed ? $val($ed->year_graduated ?? $ed->graduated_year ?? '') : '' }}</td>
                    <td>{{ $ed ? $val($ed->scholarship ?? $ed->honors ?? '') : '' }}</td>
                </tr>
            @endforeach
        </table>

        <!-- G. PREVIOUS EMPLOYMENT -->
        <table>
            <tr>
                <td colspan="6" class="section-bar">G. PREVIOUS EMPLOYMENT</td>
            </tr>
            <tr class="tbl-header">
                <th style="width: 24%;">Company</th>
                <th style="width: 20%;">Position</th>
                <th style="width: 12%;">From</th>
                <th style="width: 12%;">To</th>
                <th style="width: 14%;" class="text-right">Last Salary</th>
                <th style="width: 18%;">Reason for Leaving</th>
            </tr>
            @php $empRows = max(4, count($employments)); @endphp
            @for ($i = 0; $i < $empRows; $i++)
                @php $ex = $employments[$i] ?? null; @endphp
                <tr>
                    <td>{{ $ex ? $val($ex->agency ?? $ex->department) : '' }}&nbsp;</td>
                    <td>{{ $ex ? $val($ex->position_title) : '' }}</td>
                    <td>{{ $ex ? $val($ex->period_from) : '' }}</td>
                    <td>{{ $ex ? $val($ex->period_to) : '' }}</td>
                    <td class="text-right">{{ $ex && is_numeric($ex->salary) ? number_format((float)$ex->salary, 2) : ($ex ? $val($ex->salary) : '') }}</td>
                    <td>{{ $ex ? $val($ex->status) : '' }}</td>
                </tr>
            @endfor
        </table>

        <!-- H. TRAININGS, SEMINARS, LICENSES & CERTIFICATIONS -->
        <table>
            <tr>
                <td colspan="5" class="section-bar">H. TRAININGS, SEMINARS, LICENSES &amp; CERTIFICATIONS</td>
            </tr>
            <tr class="tbl-header">
                <th style="width: 34%;">Title</th>
                <th style="width: 24%;">Provider</th>
                <th style="width: 14%;">Date</th>
                <th style="width: 10%;">Hours</th>
                <th style="width: 18%;">Certificate / License No.</th>
            </tr>
            @php
                $combinedTrainings = [];
                foreach ($examinations as $exam) {
                    $combinedTrainings[] = [
                        'title' => $val($exam->eligibility),
                        'provider' => $val($exam->place_of_examination),
                        'date' => $val($exam->date_of_examination),
                        'hours' => '-',
                        'cert' => $val($exam->license_no),
                    ];
                }
                foreach ($trainings as $tr) {
                    $combinedTrainings[] = [
                        'title' => $val($tr->learning),
                        'provider' => $val($tr->conducted_by),
                        'date' => $val($tr->inclusive_dates_from),
                        'hours' => $val($tr->number_of_hours),
                        'cert' => '',
                    ];
                }
                $trRows = max(4, count($combinedTrainings));
            @endphp
            @for ($i = 0; $i < $trRows; $i++)
                @php $ct = $combinedTrainings[$i] ?? null; @endphp
                <tr>
                    <td>{{ $ct['title'] ?? '' }}&nbsp;</td>
                    <td>{{ $ct['provider'] ?? '' }}</td>
                    <td>{{ $ct['date'] ?? '' }}</td>
                    <td>{{ $ct['hours'] ?? '' }}</td>
                    <td>{{ $ct['cert'] ?? '' }}</td>
                </tr>
            @endfor
        </table>

        <!-- I. LEAVE ENTITLEMENT & BENEFITS -->
        <table>
            <tr>
                <td colspan="6" class="section-bar">I. LEAVE ENTITLEMENT &amp; BENEFITS</td>
            </tr>
            <tr class="tbl-header">
                <th style="width: 24%;">Leave Type</th>
                <th style="width: 16%;">Annual Entitlement (days)</th>
                <th style="width: 14%;">Carry-over (days)</th>
                <th style="width: 14%;">Used YTD (days)</th>
                <th style="width: 14%;">Balance (days)</th>
                <th style="width: 18%;">Remarks</th>
            </tr>
            @php
                $leaveTypes = [
                    'Vacation Leave',
                    'Sick Leave',
                    'Emergency Leave',
                    'Maternity / Paternity',
                    'Solo Parent Leave',
                    'Bereavement / Other',
                ];
            @endphp
            @foreach ($leaveTypes as $lt)
                <tr>
                    <td style="font-weight: bold; background-color: #f9fafb;">{{ $lt }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endforeach
        </table>

        <!-- J. DISCIPLINARY RECORD -->
        <table>
            <tr>
                <td colspan="5" class="section-bar">J. DISCIPLINARY RECORD</td>
            </tr>
            <tr class="tbl-header">
                <th style="width: 14%;">Date</th>
                <th style="width: 28%;">Violation / Incident</th>
                <th style="width: 26%;">Action Taken (Memo / Warning / Suspension)</th>
                <th style="width: 16%;">Issued By</th>
                <th style="width: 16%;">Status / Remarks</th>
            </tr>
            @for ($i = 0; $i < 3; $i++)
                <tr>
                    <td>&nbsp;</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endfor
        </table>

        <!-- K. PERFORMANCE EVALUATION HISTORY -->
        <table>
            <tr>
                <td colspan="5" class="section-bar">K. PERFORMANCE EVALUATION HISTORY</td>
            </tr>
            <tr class="tbl-header">
                <th style="width: 24%;">Evaluation Period</th>
                <th style="width: 16%;">Rating</th>
                <th style="width: 22%;">Evaluator</th>
                <th style="width: 14%;">Date</th>
                <th style="width: 24%;">Remarks</th>
            </tr>
            @php $evalRows = max(3, count($evals)); @endphp
            @for ($i = 0; $i < $evalRows; $i++)
                @php $ev = $evals[$i] ?? null; @endphp
                <tr>
                    <td>{{ $ev ? $val($ev->period ?? 'Performance Review') : '' }}&nbsp;</td>
                    <td>{{ $ev ? $val($ev->rating) : '' }}</td>
                    <td>{{ $ev ? $val($ev->evaluator ?? '') : '' }}</td>
                    <td>{{ $ev ? $val($ev->created_at ?? '') : '' }}</td>
                    <td>{{ $ev ? $val($ev->adjectival_rating ?? '') : '' }}</td>
                </tr>
            @endfor
        </table>

        <!-- L. DOCUMENTARY REQUIREMENTS CHECKLIST -->
        <table>
            <tr>
                <td colspan="4" class="section-bar">L. DOCUMENTARY REQUIREMENTS CHECKLIST</td>
            </tr>
            <tr class="tbl-header">
                <th style="width: 44%;">Document</th>
                <th style="width: 14%;" class="text-center">Submitted?</th>
                <th style="width: 18%;">Date Submitted</th>
                <th style="width: 24%;">Remarks</th>
            </tr>
            @php
                $checklistDocs = [
                    'Resume / Curriculum Vitae',
                    'Application Form',
                    'Employment Contract / Job Offer',
                    'PSA Birth Certificate',
                    'PSA Marriage Certificate (if married)',
                    "Dependents' PSA Birth Certificates",
                    'Diploma / Transcript of Records',
                    'NBI Clearance',
                    'Police Clearance',
                    'Barangay Clearance',
                    'Pre-employment Medical Exam',
                    'Drug Test Result',
                    '2×2 ID Photos',
                    'SSS E-1 / SS Number Proof',
                    'PhilHealth MDR / Proof of PIN',
                    'Pag-IBIG MDF / Proof of MID',
                    'TIN / BIR Form 1902 or 2305',
                    'BIR Form 2316 from Previous Employer',
                    'Certificate of Employment (Previous)',
                    'Valid Government ID (photocopy)',
                    'Data Privacy Consent Form',
                    'Company Handbook Acknowledgment',
                ];
            @endphp
            @foreach ($checklistDocs as $docName)
                <tr>
                    <td>{{ $docName }}</td>
                    <td class="text-center">[ &nbsp; ]</td>
                    <td></td>
                    <td></td>
                </tr>
            @endforeach
        </table>

        <!-- M. CERTIFICATION -->
        <table>
            <tr>
                <td colspan="3" class="section-bar">M. CERTIFICATION</td>
            </tr>
            <tr>
                <td colspan="3" style="padding: 6px; font-size: 8px; line-height: 1.4;">
                    I hereby certify that the information provided above is true and correct to the best of my knowledge.
                    I agree to notify HR of any change within a reasonable period and consent to the processing of my
                    personal data for employment purposes in accordance with the Data Privacy Act of 2012.
                </td>
            </tr>
            <tr>
                <td style="width: 34%; padding-top: 24px; text-align: center;">
                    <div class="sig-line">Employee Signature over Printed Name / Date</div>
                </td>
                <td style="width: 33%; padding-top: 24px; text-align: center;">
                    <div class="sig-line">Prepared by (HR Staff) / Date</div>
                </td>
                <td style="width: 33%; padding-top: 24px; text-align: center;">
                    <div class="sig-line">Verified by (HR Manager) / Date</div>
                </td>
            </tr>
        </table>
    @endforeach
</body>

</html>
