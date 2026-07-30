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

    <title>Personal Data Sheet</title>

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
            padding: 5px;
            margin: 0;
        }

        table,
        th,
        td {
            border: 1px solid black;
            border-collapse: collapse;
            padding: 1px;
            font-size: 6px;
        }

        input[type="checkbox"] {
            vertical-align: middle;
        }

        label,
        input {
            display: inline-block;
            vertical-align: middle;
        }

        .main {
            padding: 2px;
        }
    </style>
</head>

<body>
    <!-- Main content -->
    @foreach ($info as $info)
        <div class="card p-4">
            <div class="main" style="width: 100%;">
                <div>
                    <table style="width: 100%;padding:10px;">
                        <tr>
                            <td colspan="9" style="border-bottom: none;">CS Form No. 212</td>
                        </tr>
                        <tr>
                            <td colspan="9" style="border-bottom: none;border-top: none;">Revised 2017</td>
                        </tr>
                        <tr>
                            <td colspan="9"
                                style="border-bottom: none;border-top: none;padding: 8px;font-size: 14px;font-weight: bolder;">
                                <center>PERSONAL DATA SHEET</center>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="9" style="border-bottom: none;border-top: none;">
                                <b>WARNING: Any misrepresentation made in the Personal Data Sheet and the Work
                                    Experience Sheet shall cause the filing of administrative/criminal case/s against
                                    the person concerned.</b>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="9" style="border-bottom: none;border-top: none;">
                                <b>READ THE ATTACHED GUIDE TO FILLING OUT THE PERSONAL DATA SHEET (PDS) BEFORE
                                    ACCOMPLISHING THE PDS FORM.</b>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7">Print legibly. Tick appropriate boxes ( ) and use separate sheet if
                                necessary. Indicate N/A if not applicable. <b>DO NOT ABBREVIATE.</b></td>
                            <td style="background-color: gray;">1. CS ID No.</td>
                            <td style="text-align: right;">(Do not fill up. For CSC use only)</td>
                        </tr>
                        <tr>
                            <td colspan="9" style="background-color: gray;color: white;">I. PERSONAL INFORMATION</td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">2.
                                SURNAME</td>
                            <td colspan="8">{{ $info->last_name }}</td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black;border-bottom: none;border-top: none;">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; FIRST NAME</td>
                            <td colspan="7">{{ $info->first_name }}</td>
                            <td style="background-color: lightgray;color: black;padding-bottom: 10px;">NAME EXTENSION
                                (JR., SR) {{ $info->suffix }}</td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding-bottom: 10px;">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; MIDDLE NAME</td>
                            <td colspan="8">{{ $info->middle_name }}</td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">3.
                                DATE OF BIRTH </td>
                            <td colspan="2" style="border-bottom: none;border-top: none;padding: 5px;">
                                {{ date('m/d/Y', strtotime($info->birthdate)) }}</td>
                            <td colspan="3"
                                style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">16.
                                CITIZENSHIP</td>
                            <td>
                                <div style="text-align: start;"><label style="word-wrap:break-word;"><input
                                               type="checkbox" style="vertical-align: middle;"
                                               {{ $info->is_dual_citizent == false ? 'checked' : '' }}> Filipino</label>
                                </div>
                            </td>
                            <td colspan="2"><label><input type="checkbox"
                                           {{ $info->is_dual_citizent == true ? 'checked' : '' }}> Dual
                                    Citizenship</label></td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; (mm/dd/yyyy) </td>
                            <td colspan="2" style="border-bottom: none;border-top: none;padding: 5px;"></td>
                            <td colspan="3"
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td><label><input type="checkbox" {{ $info->by_birth == true ? 'checked' : '' }}> by
                                    Birth</label></td>
                            <td colspan="2"><label><input type="checkbox"
                                           {{ $info->by_naturalization == true ? 'checked' : '' }}> by
                                    Naturalization</label></td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">4.
                                PLACE OF BIRTH </td>
                            <td colspan="2" style="border-bottom: none;padding: 5px;">{{ $info->birth_place }}</td>
                            <td colspan="3"
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                                <center>If holder of dual citizenship,</center>
                            </td>
                            <td colspan="3">
                                <center>Pls. indicate country:</center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">5.
                                SEX</td>
                            <td colspan="2" style="border-bottom: none;padding: 5px;">
                                <div style="display: inline-block;">
                                    <label><input type="checkbox" {{ $info->gender == 'Male' ? 'checked' : '' }}>
                                        Male</label>
                                </div>
                                <div style="display: inline-block;">
                                    <label><input type="checkbox" {{ $info->gender == 'Female' ? 'checked' : '' }}>
                                        Female</label>
                                </div>
                            </td>
                            <td colspan="3"
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                                <center>please indicate the details.</center>
                            </td>
                            <td colspan="3">
                                <center>{{ $info->indicate_country }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black;padding: 5px;border-bottom: none;">6.
                                CIVIL STATUS</td>
                            <td colspan="2" style="border-bottom: none;padding: 5px;">
                                <label for=""><input type="checkbox"
                                           {{ $info->civil_status == 'Single' ? 'checked' : '' }}> Single</label>
                                <label for=""><input type="checkbox"
                                           {{ $info->civil_status == 'Married' ? 'checked' : '' }}> Married</label>
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">
                                17. RESIDENTIAL ADDRESS
                            </td>
                            <td colspan="2">
                                <center>{{ $info->ra_house_no }}</center>
                            </td>
                            <td colspan="2">
                                <center>{{ $info->ra_street }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2" style="border-bottom: none;border-top: none;padding: 5px;"></td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2">
                                <center>House/Block/Lot No.</center>
                            </td>
                            <td colspan="2">
                                <center>Street</center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2" style="border-bottom: none;border-top: none;padding: 5px;">
                                <label for=""><input type="checkbox"
                                           {{ $info->civil_status == 'Widowed' ? 'checked' : '' }}> Widowed</label>
                                <label for=""><input type="checkbox"
                                           {{ $info->civil_status == 'Separated' ? 'checked' : '' }}> Separated</label>
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2" style="border-bottom: none;">
                                <center></center>
                            </td>
                            <td colspan="2" style="border-bottom: none;">
                                <center></center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2" style="border-bottom: none;border-top: none;padding: 5px;">
                                <label for=""><input type="checkbox"
                                           {{ $info->civil_status == 'Other' ? 'checked' : '' }}> Other</label>
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2" style="border-bottom: none;border-top: none;">
                                <center>{{ $info->ra_village }}</center>
                            </td>
                            <td colspan="2" style="border-bottom: none;border-top: none;">
                                <center>{{ $address['ra_brgy'] }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2" style="border-bottom: none;border-top: none;padding: 5px;"></td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2">
                                <center>Subdivision/Village</center>
                            </td>
                            <td colspan="2">
                                <center>Barangay</center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">7.
                                HEIGHT (m)</td>
                            <td colspan="2" style="border-bottom: none; none;padding: 5px;">{{ $info->height }}
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                                <center></center>
                            </td>
                            <td colspan="2" style="border-bottom: none;">
                                <center>{{ $address['ra_city'] }}</center>
                            </td>
                            <td colspan="2" style="border-bottom: none;border-top: none;">
                                <center>{{ $address['ra_province'] }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2" style="border-bottom: none;border-top: none;padding: 5px;"></td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2">
                                <center>City/Municipality</center>
                            </td>
                            <td colspan="2">
                                <center>Province</center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">8.
                                WEIGHT (kg)</td>
                            <td colspan="2" style="border-bottom: none; none;padding: 5px;">{{ $info->weight }}
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                                <center>ZIP CODE</center>
                            </td>
                            <td colspan="4" style="border-bottom: none;">
                                <center></center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">9.
                                BLOOD TYPE</td>
                            <td colspan="2" style="border-bottom: none; none;padding: 5px;">
                                {{ $info->blood_type }}
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">
                                18. PERMANENT ADDRESS
                            </td>
                            <td colspan="2" style="border-bottom: none;">
                                <center>{{ $info->pa_house_no }}</center>
                            </td>
                            <td colspan="2" style="border-bottom: none;">
                                <center>{{ $info->pa_street }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2" style="border-bottom: none;border-top: none;padding: 5px;"></td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2">
                                <center>House/Block/Lot No.</center>
                            </td>
                            <td colspan="2">
                                <center>Street</center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">10.
                                GSIS ID NO.</td>
                            <td colspan="2" style="border-bottom: none; none;padding: 5px;">{{ $info->gsis_no }}
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2" style="border-bottom: none;">
                                <center>{{ $info->pa_village }}</center>
                            </td>
                            <td colspan="2" style="border-bottom: none;">
                                <center>{{ $address['pa_brgy'] }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2" style="border-bottom: none;border-top: none;padding: 5px;"></td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2">
                                <center>Subdivision/Village</center>
                            </td>
                            <td colspan="2">
                                <center>Barangay</center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">11.
                                PAG-IBIG ID NO.</td>
                            <td colspan="2" style="border-bottom: none; none;padding: 5px;">
                                {{ $info->pagibig_no }}
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2" style="border-bottom: none;">
                                <center>{{ $address['pa_city'] }}</center>
                            </td>
                            <td colspan="2" style="border-bottom: none;">
                                <center>{{ $address['pa_province'] }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2" style="border-bottom: none;border-top: none;padding: 5px;"></td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                            </td>
                            <td colspan="2">
                                <center>City/Municipality</center>
                            </td>
                            <td colspan="2">
                                <center>Province</center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">12.
                                PHILHEALTH NO.</td>
                            <td colspan="2" style="border-bottom: none; none;padding: 5px;"></td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                                <center>ZIP CODE</center>
                            </td>
                            <td colspan="4" style="border-bottom: none;">
                                <center></center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">12.
                                SSS NO.</td>
                            <td colspan="2" style="border-bottom: none; none;padding: 5px;">{{ $info->sss_no }}
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">19.
                                TELEPHONE NO.</td>
                            <td colspan="4" style="border-bottom: none;">
                                <center>{{ $info->telephone_no }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">13.
                                TIN NO.</td>
                            <td colspan="2" style="border-bottom: none; none;padding: 5px;">{{ $info->tin_no }}
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">20.
                                MOBILE NO.</td>
                            <td colspan="4" style="border-bottom: none;">
                                <center>{{ $info->mobile_no }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">15.
                                AGENCY EMPLOYEE NO.</td>
                            <td colspan="2" style="border-bottom: none; none;padding: 5px;">
                                {{ $info->employee_no }}
                            </td>
                            <td colspan="2"
                                style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">21.
                                E-MAIL ADDRESS (if any)</td>
                            <td colspan="4" style="border-bottom: none;">
                                <center>{{ $info->email }}</center>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="9" style="background-color: gray;color: white;">II. FAMILY BACKGROUND</td>
                        </tr>
                        <!-- Spouse Name -->
                        <tr>
                            <td style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">22.
                                SPOUSE'S SURNAME</td>
                            <td colspan="4">{{ $info->spouse_last_name }}</td>
                            <td colspan="3" style="background-color: lightgray;color: black;">
                                <center>23. NAME of CHILDREN (Write full name and list all)</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>DATE OF BIRTH <br> (mm/dd/yyyy)</center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black;border-bottom: none;border-top: none;">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; FIRST NAME</td>
                            <td colspan="3">{{ $info->spouse_first_name }}</td>
                            <td style="background-color: lightgray;color: black;padding-bottom: 10px;">NAME EXTENSION
                                (JR., SR) {{ $info->spouse_suffix }}</td>
                            <td colspan="3">
                                <center>
                                    {{ isset($children[0]->row) && $children[0]->row == 1 ? $children[0]->child_name : '' }}
                                </center>
                            </td>
                            <td>
                                <center>
                                    {{ isset($children[0]->row) && $children[0]->row == 1 ? date('m/d/Y', strtotime($children[0]->child_birthdate)) : '' }}
                                </center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding-bottom: 10px;">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; MIDDLE NAME</td>
                            <td colspan="4">{{ $info->spouse_middle_name }}</td>
                            <td colspan="3">
                                <center>
                                    {{ isset($children[1]->row) && $children[1]->row == 2 ? $children[1]->child_name : '' }}
                                </center>
                            </td>
                            <td>
                                <center>
                                    {{ isset($children[1]->row) && $children[1]->row == 2 ? date('m/d/Y', strtotime($children[1]->child_birthdate)) : '' }}
                                </center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding-bottom: 10px;">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; OCCUPATION</td>
                            <td colspan="4">{{ $info->spouse_occupation }}</td>
                            <td colspan="3">
                                <center>
                                    {{ isset($children[2]->row) && $children[2]->row == 3 ? $children[2]->child_name : '' }}
                                </center>
                            </td>
                            <td>
                                <center>
                                    {{ isset($children[2]->row) && $children[2]->row == 3 ? date('m/d/Y', strtotime($children[2]->child_birthdate)) : '' }}
                                </center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding-bottom: 10px;">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; EMPLOYER/BUSINESS NAME</td>
                            <td colspan="4">{{ $info->spouse_employer }}</td>
                            <td colspan="3">
                                <center>
                                    {{ isset($children[3]->row) && $children[3]->row == 4 ? $children[3]->child_name : '' }}
                                </center>
                            </td>
                            <td>
                                <center>
                                    {{ isset($children[3]->row) && $children[3]->row == 4 ? date('m/d/Y', strtotime($children[3]->child_birthdate)) : '' }}
                                </center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding-bottom: 10px;">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; BUSINESS ADDRESS</td>
                            <td colspan="4">{{ $info->spouse_business_address }}</td>
                            <td colspan="3">
                                <center>
                                    {{ isset($children[4]->row) && $children[4]->row == 5 ? $children[4]->child_name : '' }}
                                </center>
                            </td>
                            <td>
                                <center>
                                    {{ isset($children[4]->row) && $children[4]->row == 5 ? date('m/d/Y', strtotime($children[4]->child_birthdate)) : '' }}
                                </center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding-bottom: 10px;">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; TELEPHONE NO.</td>
                            <td colspan="4"></td>
                            <td colspan="3">
                                <center>
                                    {{ isset($children[5]->row) && $children[5]->row == 6 ? $children[5]->child_name : '' }}
                                </center>
                            </td>
                            <td>
                                <center>
                                    {{ isset($children[5]->row) && $children[5]->row == 6 ? date('m/d/Y', strtotime($children[5]->child_birthdate)) : '' }}
                                </center>
                            </td>
                        </tr>
                        <!-- Fathers Name -->
                        <tr>
                            <td style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">24.
                                FATHER'S SURNAME</td>
                            <td colspan="4">{{ $info->father_last_name }}</td>
                            <td colspan="3">
                                <center>
                                    {{ isset($children[6]->row) && $children[6]->row == 7 ? $children[6]->child_name : '' }}
                                </center>
                            </td>
                            <td>
                                <center>
                                    {{ isset($children[6]->row) && $children[6]->row == 7 ? date('m/d/Y', strtotime($children[6]->child_birthdate)) : '' }}
                                </center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black;border-bottom: none;border-top: none;">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; FIRST NAME</td>
                            <td colspan="3">{{ $info->father_first_name }}</td>
                            <td style="background-color: lightgray;color: black;padding-bottom: 10px;">NAME EXTENSION
                                (JR., SR) {{ $info->father_suffix }}</td>
                            <td colspan="3">
                                <center>
                                    {{ isset($children[7]->row) && $children[7]->row == 8 ? $children[7]->child_name : '' }}
                                </center>
                            </td>
                            <td>
                                <center>
                                    {{ isset($children[7]->row) && $children[7]->row == 8 ? date('m/d/Y', strtotime($children[7]->child_birthdate)) : '' }}
                                </center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding-bottom: 10px;">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; MIDDLE NAME</td>
                            <td colspan="4">{{ $info->father_middle_name }}</td>
                            <td colspan="3">
                                <center>
                                    {{ isset($children[8]->row) && $children[8]->row == 9 ? $children[8]->child_name : '' }}
                                </center>
                            </td>
                            <td>
                                <center>
                                    {{ isset($children[8]->row) && $children[8]->row == 9 ? date('m/d/Y', strtotime($children[8]->child_birthdate)) : '' }}
                                </center>
                            </td>
                        </tr>
                        <!-- Mother's Name -->
                        <tr>
                            <td colspan="5"
                                style="background-color: lightgray;color: black;border-bottom: none;padding: 5px;">25.
                                MOTHER'S MAIDEN NAME</td>
                            <td colspan="3">
                                <center>
                                    {{ isset($children[9]->row) && $children[9]->row == 10 ? $children[9]->child_name : '' }}
                                </center>
                            </td>
                            <td>
                                <center>
                                    {{ isset($children[9]->row) && $children[9]->row == 10 ? date('m/d/Y', strtotime($children[9]->child_birthdate)) : '' }}
                                </center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding: 5px;">
                                &nbsp;&nbsp;&nbsp; SURNAME</td>
                            <td colspan="4">{{ $info->mother_last_name }}</td>
                            <td colspan="3">
                                <center>
                                    {{ isset($children[10]->row) && $children[10]->row == 11 ? $children[10]->child_name : '' }}
                                </center>
                            </td>
                            <td>
                                <center>
                                    {{ isset($children[10]->row) && $children[10]->row == 11 ? date('m/d/Y', strtotime($children[10]->child_birthdate)) : '' }}
                                </center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding-bottom: 10px;">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; FIRST NAME</td>
                            <td colspan="4">{{ $info->mother_first_name }}</td>
                            <td colspan="3">
                                <center>
                                    {{ isset($children[11]->row) && $children[11]->row == 12 ? $children[11]->child_name : '' }}
                                </center>
                            </td>
                            <td>
                                <center>
                                    {{ isset($children[11]->row) && $children[11]->row == 12 ? date('m/d/Y', strtotime($children[11]->child_birthdate)) : '' }}
                                </center>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="background-color: lightgray;color: black;border-bottom: none;border-top: none;padding-bottom: 10px;">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; MIDDLE NAME</td>
                            <td colspan="4">{{ $info->mother_middle_name }}</td>
                            <td colspan="4" style="background-color: lightgray;color: red;">
                                <center><i>(Continue on separate sheet if necessary)</i></center>
                            </td>
                        </tr>
                        <!-- Education -->
                        <tr>
                            <td colspan="9" style="background-color: gray;color: white;">III. EDUCATIONAL
                                BACKGROUND
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="background-color: lightgray;color: black;">
                                <center>26. LEVEL</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>NAME OF SCHOOL <br> (Write in full)</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>BASIC EDUCATION/DEGREE/COURSE <br> (Write in full) </center>
                            </td>
                            <td colspan="2" style="background-color: lightgray;color: black;">
                                <center>PERIOD OF ATTENDANCE</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>HIGHEST LEVEL/ UNITS EARNED
                                    (if not graduated)</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>YEAR GRADUATED </center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>SCHOLARSHIP/ ACADEMIC HONORS RECEIVED</center>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" style="background-color: lightgray;color: black;"></td>
                            <td style="background-color: lightgray;color: black;">
                                <center>From</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>To</center>
                            </td>
                            <td colspan="3" style="background-color: lightgray;color: black;"></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="background-color: lightgray;color: black;padding-bottom: 10px;">
                                &nbsp;&nbsp;&nbsp; ELEMENTARY
                            </td>
                            @if (!$educations_elem->isEmpty())
                                @foreach ($educations_elem as $education)
                                    <td>
                                        {{ $education->school_name }}
                                    </td>
                                    <td>
                                        {{ $education->program }}
                                    </td>
                                    <td>
                                        <center>{{ $education->from }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->to }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->units_earned }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->graduated_year }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->honors }}</center>
                                    </td>
                                @endforeach
                            @else
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            @endif
                        </tr>
                        <tr>
                            <td colspan="2" style="background-color: lightgray;color: black;padding-bottom: 10px;">
                                &nbsp;&nbsp;&nbsp; SECONDARY
                            </td>
                            @if (!$educations_sec->isEmpty())
                                @foreach ($educations_sec as $education)
                                    <td>
                                        {{ $education->school_name }}
                                    </td>
                                    <td>
                                        {{ $education->program }}
                                    </td>
                                    <td>
                                        <center>{{ $education->from }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->to }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->units_earned }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->graduated_year }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->honors }}</center>
                                    </td>
                                @endforeach
                            @else
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            @endif
                        </tr>
                        <tr>
                            <td colspan="2" style="background-color: lightgray;color: black;padding-bottom: 10px;">
                                &nbsp;&nbsp;&nbsp; VOCATIONAL <br> / TRADE COURSE
                            </td>
                            @if (!$educations_voc->isEmpty())
                                @foreach ($educations_voc as $education)
                                    <td>
                                        {{ $education->school_name }}
                                    </td>
                                    <td>
                                        {{ $education->program }}
                                    </td>
                                    <td>
                                        <center>{{ $education->from }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->to }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->units_earned }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->graduated_year }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->honors }}</center>
                                    </td>
                                @endforeach
                            @else
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            @endif
                        </tr>
                        <tr>
                            <td colspan="2" style="background-color: lightgray;color: black;padding-bottom: 10px;">
                                &nbsp;&nbsp;&nbsp; COLLEGE
                            </td>
                            @if (!$educations_col->isEmpty())
                                @foreach (!$educations_col as $education)
                                    <td>
                                        {{ $education->school_name }}
                                    </td>
                                    <td>
                                        {{ $education->program }}
                                    </td>
                                    <td>
                                        <center>{{ $education->from }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->to }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->units_earned }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->graduated_year }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->honors }}</center>
                                    </td>
                                @endforeach
                            @else
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            @endif
                        </tr>
                        <tr>
                            <td colspan="2" style="background-color: lightgray;color: black;padding-bottom: 10px;">
                                &nbsp;&nbsp;&nbsp; GRADUATE STUDIES
                            </td>
                            @if (!$educations_grad->isEmpty())
                                @foreach ($educations_grad as $education)
                                    <td>
                                        {{ $education->school_name }}
                                    </td>
                                    <td>
                                        {{ $education->program }}
                                    </td>
                                    <td>
                                        <center>{{ $education->from }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->to }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->units_earned }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->graduated_year }}</center>
                                    </td>
                                    <td>
                                        <center>{{ $education->honors }}</center>
                                    </td>
                                @endforeach
                            @else
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            @endif
                        </tr>
                        <tr>
                            <td colspan="9" style="background-color: lightgray;color: red;padding: 0px;">
                                <center><i>(Continue on separate sheet if necessary)</i></center>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="background-color: lightgray;color: black;">
                                <center><b>SIGNATURE</b></center>
                            </td>
                            <td colspan="4"></td>
                            <td style="background-color: lightgray;color: black;">
                                <center><b>Date</b></center>
                            </td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="9" style="border: none;text-align: right;">
                                <i>CS FORM 212 (Revised 2017), Page 1 of 4</i>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <!-- Page 2 -->
        <br>
        <div class="card p-4">
            <div class="main" style="width: 100%;">
                <div>
                    <table style="width: 100%;padding:10px;">
                        <!-- Eligibility -->
                        <tr>
                            <td colspan="9" style="background-color: gray;color: white;">IV. CIVIL SERVICE
                                ELIGIBILITY
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="background-color: lightgray;color: black;">
                                <center>27. CES/CSEE/CAREER SERVICE/RA 1080 (BOARD/BAR)/UNDER SPECIAL LAWS/CATEGORY II/IV ELIGIBILITY and ELIGIBILITIES FOR UNIFORMED PERSONNEL</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>RATING <br> (If Applicable)</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>DATE OF EXAMINATION / CONFERMENT</center>
                            </td>
                            <td colspan="3" style="background-color: lightgray;color: black;">
                                <center>PLACE OF EXAMINATION / CONFERMENT</center>
                            </td>
                            <td colspan="2" style="background-color: lightgray;color: black;">
                                <center>LICENSE (if applicable)</center>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;"></td>
                            <td style="background-color: lightgray;color: black;">
                                <center>NUMBER</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>Date of <br> Validity</center>
                            </td>
                        </tr>
                        @foreach ($examinations as $examination)
                            <td colspan="2" style="padding: 10px;">
                                <center>{{ $examination->eligibility }}</center>
                            </td>
                            <td style="padding: 10px;">
                                <center>{{ $examination->exam_rating }}</center>
                            </td>
                            <td style="padding: 10px;">
                                <center>{{ date('m/d/Y', strtotime($examination->exam_date)) }}</center>
                            </td>
                            <td colspan="3" style="padding: 10px;">
                                <center>{{ $examination->place_of_exam }}</center>
                            </td>
                            <td style="padding: 10px;">
                                <center>{{ $examination->license_number }}</center>
                            </td>
                            <td style="padding: 10px;">
                                <center>{{ date('m/d/Y', strtotime($examination->date_released)) }}</center>
                            </td>
                        @endforeach
                        @if (count($examinations) < 14)
                            @for ($i = count($examinations); $i < 14; $i++)
                                <tr>
                                    <td colspan="2" style="padding: 10px;"></td>
                                    <td></td>
                                    <td></td>
                                    <td colspan="3"></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            @endfor
                        @endif
                        <tr>
                            <td colspan="9" style="background-color: lightgray;color: red;padding: 0px;">
                                <center><i>(Continue on separate sheet if necessary)</i></center>
                            </td>
                        </tr>
                        <!-- Work Experiences -->
                        <tr>
                            <td colspan="9" style="background-color: gray;color: white;">V. WORK EXPERIENCE
                                <br> (Include private employment. Start from your recent work) Description of duties
                                should be indicated in the attached Work Experience sheet.
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="background-color: lightgray;color: black;">
                                <center>28. INCLUSIVE DATES <br> (mm/dd/yyyy)</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>POSITION TITLE <br> (Write in full/Do not abbreviate)</center>
                            </td>
                            <td colspan="4" style="background-color: lightgray;color: black;">
                                <center>DEPARTMENT / AGENCY / OFFICE / COMPANY <br> (Write in full/Do not abbreviate)
                                </center>
                            </td>
                            <!-- <td style="background-color: lightgray;color: black;">
                                <center>MONTHLY SALARY</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>SALARY/ JOB/ PAY GRADE (if applicable)& STEP (Format "00-0")/ INCREMENT</center>
                            </td> -->
                            <td style="background-color: lightgray;color: black;">
                                <center>STATUS OF APPOINTMENT</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>GOV'T SERVICE <br> (Y/ N)</center>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: lightgray;color: black;">
                                <center>From</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>To</center>
                            </td>
                            <td colspan="7" style="background-color: lightgray;color: black;"></td>
                        </tr>
                        @foreach ($employments as $employment)
                            <tr>
                                <td style="padding: 10px;">
                                    <center>{{ date('m/d/Y', strtotime($employment->work_start_date)) }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ date('m/d/Y', strtotime($employment->work_end_date)) }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ $employment->position }}</center>
                                </td>
                                <td colspan="4" style="padding: 10px;">
                                    <center>{{ $employment->work_company }}</center>
                                </td>
                                <!-- <td style="padding: 10px;">
                                    <center>{{ number_format($employment->monthly_salary, 2, '.', ',') }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ $employment->salary_grade_step }}</center>
                                </td> -->
                                <td style="padding: 10px;">
                                    <center>{{ $employment->status_of_appointment }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ $employment->government_service_id == 0 ? 'No' : 'Yes' }}</center>
                                </td>
                            </tr>
                        @endforeach
                        @if (count($employments) < 28)
                            @for ($i = count($employments); $i < 28; $i++)
                                <tr>
                                    <td style="padding: 10px;"></td>
                                    <td></td>
                                    <td></td>
                                    <td colspan="4"></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            @endfor
                        @endif
                        <tr>
                            <td colspan="9" style="background-color: lightgray;color: red;padding: 0px;">
                                <center><i>(Continue on separate sheet if necessary)</i></center>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="background-color: lightgray;color: black;">
                                <center><b>SIGNATURE</b></center>
                            </td>
                            <td colspan="4"></td>
                            <td style="background-color: lightgray;color: black;">
                                <center><b>Date</b></center>
                            </td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="9" style="border: none;text-align: right;">
                                <i>CS FORM 212 (Revised 2017), Page 2 of 4</i>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <!-- Page 3 -->
        <br>
        <div class="card p-4">
            <div class="main" style="width: 100%;">
                <div>
                    <table style="width: 100%;padding:10px;">
                        <!-- Organization -->
                        <tr>
                            <td colspan="9" style="background-color: gray;color: white;">VI. VOLUNTARY WORK OR
                                INVOLVEMENT IN CIVIC / NON-GOVERNMENT / PEOPLE / VOLUNTARY ORGANIZATION/S</td>
                        </tr>
                        <tr>
                            <td colspan="4" style="background-color: lightgray;color: black;">
                                <center>29. NAME & ADDRESS OF ORGANIZATION <br> (Write in full)</center>
                            </td>
                            <td colspan="2" style="background-color: lightgray;color: black;">
                                <center>INCLUSIVE DATES <br> (mm/dd/yyyy)</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>NUMBER OF <br> HOURS</center>
                            </td>
                            <td colspan="2" style="background-color: lightgray;color: black;">
                                <center>POSITION / NATURE OF WORK</center>
                            </td>
                        </tr>
                        <td colspan="4" style="background-color: lightgray;color: black;"></td>
                        <td style="background-color: lightgray;color: black;">
                            <center>From</center>
                        </td>
                        <td style="background-color: lightgray;color: black;">
                            <center>To</center>
                        </td>
                        <td colspan="3" style="background-color: lightgray;color: black;"></td>
                        @foreach ($organizations as $organization)
                            <tr>
                                <td colspan="4" style="padding: 10px;">
                                    <center>{{ $organization->organization }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ date('m/d/Y', strtotime($organization->org_from)) }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ date('m/d/Y', strtotime($organization->org_to)) }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ $organization->org_hours }}</center>
                                </td>
                                <td colspan="2" style="padding: 10px;">
                                    <center>{{ $organization->org_position }}</center>
                                </td>
                            </tr>
                        @endforeach
                        @if (count($organizations) < 7)
                            @for ($i = count($organizations); $i < 7; $i++)
                                <tr>
                                    <td colspan="4" style="padding: 10px;"></td>
                                    <td style="padding: 10px;"></td>
                                    <td style="padding: 10px;"></td>
                                    <td style="padding: 10px;"></td>
                                    <td colspan="2" style="padding: 10px;"></td>
                                </tr>
                            @endfor
                        @endif
                        <tr>
                            <td colspan="9" style="background-color: lightgray;color: red;padding: 0px;">
                                <center><i>(Continue on separate sheet if necessary)</i></center>
                            </td>
                        </tr>
                        <!-- Learning and Development -->
                        <tr>
                            <td colspan="9" style="background-color: gray;color: white;">VII. LEARNING AND
                                DEVELOPMENT
                                (L&D) INTERVENTIONS/TRAINING PROGRAMS ATTENDED</td>
                        </tr>
                        <tr>
                            <td colspan="4" style="background-color: lightgray;color: black;">
                                <center>30. TITLE OF LEARNING AND DEVELOPMENT INTERVENTIONS/TRAINING PROGRAMS <br>
                                    (Write in full)</center>
                            </td>
                            <td colspan="2" style="background-color: lightgray;color: black;">
                                <center>INCLUSIVE DATES OF <br> ATTENDANCE <br> (mm/dd/yyyy)</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>NUMBER OF <br> HOURS</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>Type of LD <br>
                                    ( Managerial/ Supervisory/ <br>
                                    Technical/etc)</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>CONDUCTED/ SPONSORED BY <br> (Write in full)</center>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" style="background-color: lightgray;color: black;"></td>
                            <td style="background-color: lightgray;color: black;">
                                <center>From</center>
                            </td>
                            <td style="background-color: lightgray;color: black;">
                                <center>To</center>
                            </td>
                            <td colspan="3" style="background-color: lightgray;color: black;"></td>
                        </tr>
                        @foreach ($trainings as $training)
                            <tr>
                                <td colspan="4" style="padding: 10px;">
                                    <center>{{ $training->training }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ date('m/d/Y', strtotime($training->training_from)) }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ date('m/d/Y', strtotime($training->training_to)) }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ $training->hours }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ $training->learning }}</center>
                                </td>
                                <td style="padding: 10px;">
                                    <center>{{ $training->sponsored_by }}</center>
                                </td>
                            </tr>
                        @endforeach
                        @if (count($trainings) < 22)
                            @for ($i = count($trainings); $i < 22; $i++)
                                <tr>
                                    <td colspan="4" style="padding: 10px;"></td>
                                    <td style="padding: 10px;"></td>
                                    <td style="padding: 10px;"></td>
                                    <td style="padding: 10px;"></td>
                                    <td style="padding: 10px;"></td>
                                    <td style="padding: 10px;"></td>
                                </tr>
                            @endfor
                        @endif
                        <tr>
                            <td colspan="9" style="background-color: lightgray;color: red;padding: 0px;">
                                <center><i>(Continue on separate sheet if necessary)</i></center>
                            </td>
                        </tr>
                        <!-- Skills and Hobbies -->
                        <tr>
                            <td colspan="9" style="background-color: gray;color: white;">VIII. OTHER INFORMATION
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="background-color: lightgray;color: black;">
                                <center>31. SPECIAL SKILLS and HOBBIES</center>
                            </td>
                            <td colspan="5" style="background-color: lightgray;color: black;">
                                <center>32. NON-ACADEMIC DISTINCTIONS / RECOGNITION <br> (Write in full)</center>
                            </td>
                            <td colspan="2" style="background-color: lightgray;color: black;">
                                <center>33. MEMBERSHIP IN ASSOCIATION/ORGANIZATION <br> (Write in full)</center>
                            </td>
                        </tr>

                        @for ($i = 0; $i < 10; $i++)
                            <tr>
                                <td colspan="2" style="padding: 10px;">
                                    <center>
                                        {{ isset($skills[$i]) && $skills[$i]->row == $i + 1 ? $skills[$i]->skill : '' }}
                                    </center>
                                </td>
                                <td colspan="5" style="padding: 10px;">
                                    <center>
                                        {{ isset($recognitions[$i]) && $recognitions[$i]->row == $i + 1 ? $recognitions[$i]->recognation : '' }}
                                    </center>
                                </td>
                                <td colspan="2" style="padding: 10px;">
                                    <center>
                                        {{ isset($memberships[$i]) && $memberships[$i]->row == $i + 1 ? $memberships[$i]->membership : '' }}
                                    </center>
                                </td>
                            </tr>
                        @endfor
                        <tr>
                            <td colspan="9" style="background-color: lightgray;color: red;padding: 0px;">
                                <center><i>(Continue on separate sheet if necessary)</i></center>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="background-color: lightgray;color: black;">
                                <center><b>SIGNATURE</b></center>
                            </td>
                            <td colspan="4"></td>
                            <td style="background-color: lightgray;color: black;">
                                <center><b>Date</b></center>
                            </td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="9" style="border: none;text-align: right;">
                                <i>CS FORM 212 (Revised 2017), Page 3 of 4</i>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <!-- Page 4 -->
        <br>
        <div class="card p-4">
            <div class="main" style="width: 100%;">
                <div>
                    <table style="width: 100%;padding:10px;">
                        <!-- Quistionaire -->
                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;">
                                <p style="font-size: 8px;">
                                    34. Are you related by consanguinity or affinity to the appointing or recommending
                                    authority, or to the
                                    <br> chief of bureau or office or to the person who has immediate supervision over
                                    you in the Office,
                                    <br> Bureau or Department where you will be apppointed,
                                    <br> a. within the third degree?
                                    <br> b. within the fourth degree (for Local Government Unit - Career Employees)?
                                </p>
                            </td>
                            <td colspan="2">
                                <div>
                                    <label for=""><input type="checkbox"> Yes</label>
                                    <label for=""><input type="checkbox"> No</label>
                                </div>
                                <div>
                                    <label for=""><input type="checkbox"> Yes</label>
                                    <label for=""><input type="checkbox"> No</label>
                                </div>
                                <div>
                                    <p>If YES, give details:</p>
                                    <input type="text"
                                           style="border-left: none;border-top: none;border-right: none;border-bottom: 1px solid black; border-collapse: collapse;">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;border-bottom: none;">
                                <p style="font-size: 8px;">35. a. Have you ever been found guilty of any administrative
                                    offense?</p>
                            </td>
                            <td colspan="2" style="border-bottom: none;">
                                <div>
                                    <label for=""><input type="checkbox"> Yes</label>
                                    <label for=""><input type="checkbox"> No</label>
                                </div>
                                <div>
                                    <p>If YES, give details:</p>
                                    <input type="text"
                                           style="border-left: none;border-top: none;border-right: none;border-bottom: 1px solid black; border-collapse: collapse;">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;border-top: none;">
                                <p style="font-size: 8px;"> b. Have you been criminally charged before any court?</p>
                            </td>
                            <td colspan="2" style="border-top: none;">
                                <div>
                                    <label for=""><input type="checkbox"> Yes</label>
                                    <label for=""><input type="checkbox"> No</label>
                                </div>
                                <div>
                                    <p>If YES, give details:</p>
                                    Date Filed:
                                    <input type="text"
                                           style="border-left: none;border-top: none;border-right: none;border-bottom: 1px solid black; border-collapse: collapse;">
                                    <br> Status of Case/s:
                                    <input type="text"
                                           style="border-left: none;border-top: none;border-right: none;border-bottom: 1px solid black; border-collapse: collapse;">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;">
                                <p style="font-size: 8px;">36. Have you ever been convicted of any crime or violation
                                    of
                                    any law, decree, ordinance or regulation by any court or tribunal?</p>
                            </td>
                            <td colspan="2">
                                <div>
                                    <label for=""><input type="checkbox"> Yes</label>
                                    <label for=""><input type="checkbox"> No</label>
                                </div>
                                <div>
                                    <p>If YES, give details:</p>
                                    <input type="text"
                                           style="border-left: none;border-top: none;border-right: none;border-bottom: 1px solid black; border-collapse: collapse;">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;">
                                <p style="font-size: 8px;">
                                    37. Have you ever been separated from the service in any of the following modes:
                                    resignation, retirement, dropped from the rolls, dismissal, termination, end of
                                    term, finished contract or phased out (abolition) in the public or private sector?
                                </p>
                            </td>
                            <td colspan="2">
                                <div>
                                    <label for=""><input type="checkbox"> Yes</label>
                                    <label for=""><input type="checkbox"> No</label>
                                </div>
                                <div>
                                    <p>If YES, give details:</p>
                                    <input type="text"
                                           style="border-left: none;border-top: none;border-right: none;border-bottom: 1px solid black; border-collapse: collapse;">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;">
                                <p style="font-size: 8px;">
                                    38. a. Have you ever been a candidate in a national or local election held within
                                    the last year (except Barangay election)?
                                </p>
                            </td>
                            <td colspan="2">
                                <div>
                                    <label for=""><input type="checkbox"> Yes</label>
                                    <label for=""><input type="checkbox"> No</label>
                                </div>
                                <div>
                                    <p>If YES, give details:</p>
                                    <input type="text"
                                           style="border-left: none;border-top: none;border-right: none;border-bottom: 1px solid black; border-collapse: collapse;">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;">
                                <p style="font-size: 8px;">
                                    b. Have you resigned from the government service during the three (3)-month period
                                    before the last election to promote/actively campaign for a national or local
                                    candidate?
                                </p>
                            </td>
                            <td colspan="2">
                                <div>
                                    <label for=""><input type="checkbox"> Yes</label>
                                    <label for=""><input type="checkbox"> No</label>
                                </div>
                                <div>
                                    <p>If YES, give details:</p>
                                    <input type="text"
                                           style="border-left: none;border-top: none;border-right: none;border-bottom: 1px solid black; border-collapse: collapse;">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;">
                                <p style="font-size: 8px;">39. Have you acquired the status of an immigrant or
                                    permanent
                                    resident of another country? </p>
                            </td>
                            <td colspan="2">
                                <div>
                                    <label for=""><input type="checkbox"> Yes</label>
                                    <label for=""><input type="checkbox"> No</label>
                                </div>
                                <div>
                                    <p>If YES, give details (country):</p>
                                    <input type="text"
                                           style="border-left: none;border-top: none;border-right: none;border-bottom: 1px solid black; border-collapse: collapse;">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black; border-bottom: none;">
                                <p style="font-size: 8px;">
                                    40. Pursuant to: (a) Indigenous People's Act (RA 8371); (b) Magna Carta for Disabled
                                    Persons (RA 7277); and (c) Solo Parents Welfare Act of 2000 (RA 8972), please answer
                                    the following items:
                                    <br> a. Are you a member of any indigenous group?
                                </p>
                            </td>
                            <td colspan="2" style="border-bottom: none;">
                                <div>
                                    <label for=""><input type="checkbox"> Yes</label>
                                    <label for=""><input type="checkbox"> No</label>
                                </div>
                                <div>
                                    <p>If YES, please specify:</p>
                                    <input type="text"
                                           style="border-left: none;border-top: none;border-right: none;border-bottom: 1px solid black; border-collapse: collapse;">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7"
                                style="background-color: lightgray;color: black; border-bottom: none; border-top: none;">
                                <p style="font-size: 8px;">b. Are you a person with disability?</p>
                            </td>
                            <td colspan="2" style="border-bottom: none; border-top: none;">
                                <div>
                                    <label for=""><input type="checkbox"> Yes</label>
                                    <label for=""><input type="checkbox"> No</label>
                                </div>
                                <div>
                                    <p>If YES, please specify ID No:</p>
                                    <input type="text"
                                           style="border-left: none;border-top: none;border-right: none;border-bottom: 1px solid black; border-collapse: collapse;">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;border-top: none;">
                                <p style="font-size: 8px;">c. Are you a solo parent?</p>
                            </td>
                            <td colspan="2" style="border-top: none;">
                                <div>
                                    <label for=""><input type="checkbox"> Yes</label>
                                    <label for=""><input type="checkbox"> No</label>
                                </div>
                                <div>
                                    <p>If YES, please specify ID No:</p>
                                    <input type="text"
                                           style="border-left: none;border-top: none;border-right: none;border-bottom: 1px solid black; border-collapse: collapse;">
                                </div>
                            </td>
                        </tr>
                        <!-- References -->
                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;">
                                <p style="font-size: 8px;">41. REFERENCES <label style="color: red;">(Person not
                                        related
                                        by consanguinity or affinity to applicant /appointee)</label></p>
                            </td>
                            <td colspan="2" rowspan="6" style="border-bottom: none;">
                                <div
                                     style="border: 1px solid black; width: 70px; height: 100px; margin: auto;padding: 10px;">
                                    <br><br>
                                    <p>
                                        <center>ID picture taken within
                                            the last 6 months
                                            4.5 cm. X 3.5 cm
                                            (passport size)
                                        </center>
                                    </p>
                                    <br>
                                    <p>
                                        <center>
                                            Computer generated
                                            or photocopied picture
                                            is not acceptable
                                        </center>
                                    </p>
                                </div>
                                <p style="text-align: center;">PHOTO</p>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" style="background-color: lightgray;color: black;">
                                <center>NAME</center>
                            </td>
                            <td colspan="2" style="background-color: lightgray;color: black;">
                                <center>ADDRESS</center>
                            </td>
                            <td colspan="2" style="background-color: lightgray;color: black;">
                                <center>TEL. NO.</center>
                            </td>
                            <!-- <td colspan="2" style="border-bottom: none;border-top: none;"></td> -->
                        </tr>
                        @foreach ($references as $reference)
                            <tr>
                                <td colspan="3" style="padding: 10px;">
                                    <center>{{ $reference->ref_name }}</center>
                                </td>
                                <td colspan="2" style="padding: 10px;">
                                    <center>{{ $reference->ref_address }}</center>
                                </td>
                                <td colspan="2" style="padding: 10px;">
                                    <center>{{ $reference->ref_contact_no }}</center>
                                </td>
                                <!-- <td colspan="2" style="border-bottom: none;border-top: none;"></td> -->
                            </tr>
                        @endforeach
                        @if (count($references) < 3)
                            @for ($i = count($references); $i < 3; $i++)
                                <tr>
                                    <td colspan="3" style="padding: 10px;"></td>
                                    <td colspan="2" style="padding: 10px;"></td>
                                    <td colspan="2" style="padding: 10px;"></td>
                                    <!-- <td colspan="2" style="border-bottom: none;border-top: none;"></td> -->
                                </tr>
                            @endfor
                        @endif
                        <tr>
                            <td colspan="7" style="background-color: lightgray;color: black;">
                                <p style="font-size: 8px;">
                                    42. I declare under oath that I have personally accomplished this Personal Data
                                    Sheet which is a true, correct and
                                    <br> complete statement pursuant to the provisions of pertinent laws, rules and
                                    regulations of the Republic of the
                                    <br> Philippines. I authorize the agency head/authorized representative to
                                    verify/validate the contents stated herein.
                                    <br> I agree that any misrepresentation made in this document and its attachments
                                    shall cause the filing of
                                    <br> administrative/criminal case/s against me.
                                </p>
                            </td>
                            <!-- <td colspan="2" style="border-top: none;"></td> -->
                        </tr>
                        <tr>
                            <td colspan="3" style="padding:5px; border-right:none;">
                                <div style="width: 200px; height: 80px;margin: auto;">
                                    <table style="height: 100%;">
                                        <tr>
                                            <td style="background-color: lightgray;color: black;">Government Issued ID
                                                (i.e.Passport, GSIS, SSS, PRC, Driver's License, etc.) PLEASE INDICATE
                                                ID Number and Date of Issuance</td>
                                        </tr>
                                        <tr>
                                            <td style="padding-bottom: 10px;">Government Issued ID:</td>
                                        </tr>
                                        <tr>
                                            <td style="padding-bottom: 10px;">ID/License/Passport No.: </td>
                                        </tr>
                                        <tr>
                                            <td style="padding-bottom: 10px;">Date/Place of Issuance:</td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                            <td colspan="3" style="padding:5px; border-right:none;border-left: none;">
                                <div style="width: 200px; height: 80px;margin: auto;">
                                    <table style="width: 100%;height: 100%;">
                                        <tr>
                                            <td style="padding: 20px;"></td>
                                        </tr>
                                        <tr>
                                            <td style="background-color: lightgray;color: black;padding-bottom: 5px;">
                                                <center>Signature (Sign inside the box)</center>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding-bottom: 5px;"></td>
                                        </tr>
                                        <tr>
                                            <td style="background-color: lightgray;color: black;padding-bottom: 5px;">
                                                <center>Date Accomplished</center>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                            <td colspan="3" style="padding:5px;border-left: none;">
                                <div style="width: 100px; height: 80px;margin: auto;">
                                    <table style="width: 100%;height: 100%;">
                                        <tr>
                                            <td style="padding: 30px;"></td>
                                        </tr>
                                        <tr>
                                            <td style="background-color: lightgray;color: black;padding-bottom: 5px;">
                                                <center>Right Thumbmark</center>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="9" style="border-bottom: none;">
                                <center>SUBSCRIBED AND SWORN to before me this ___________________________, affiant
                                    exhibiting his/her validly issued government ID as indicated above."</center>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="9" style="padding: 10px;border-top: none;">
                                <div style="margin:auto;width: 300px; height: 80px;">
                                    <table style="width: 100%;height: 100%;">
                                        <tr>
                                            <td style="padding-bottom: 50px;"></td>
                                        </tr>
                                        <tr>
                                            <td style="background-color: lightgray;color: black;padding-bottom: 10px;">
                                                <center>Person Administering Oath</center>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="9" style="border: none;text-align: right;">
                                <i>CS FORM 212 (Revised 2017), Page 4 of 4</i>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</body>

</html>
