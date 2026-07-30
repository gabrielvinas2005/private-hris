<!DOCTYPE html>
<html>

<head>
    <title>Print SALN</title>
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
            /* margin: 10px; */
        }

        .container {
            width: 100%;
            margin: 0 auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            line-height: 1
        }

        /* Make table headers repeat on each page */
        thead {
            display: table-header-group;
        }

        thead tr {
            page-break-inside: avoid;
            page-break-after: avoid;
        }

        tbody {
            display: table-row-group;
        }

        tr {
            page-break-inside: avoid;
        }

        /* custom css */
        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .underline {
            border-bottom: 1px solid black;
            display: inline-block;
            width: 50px;
            text-align: center;
        }

        .font-22 {
            font-size: 22px;
        }

        .font-20 {
            font-size: 20px;
        }

        .font-18 {
            font-size: 18px;
        }

        .font-16 {
            font-size: 16px;
        }

        .font-14 {
            font-size: 14px;
        }

        .font-12 {
            font-size: 12px;
        }

        .font-10 {
            font-size: 10px;
        }

        .font-9 {
            font-size: 9px;
        }

        .bold {
            font-weight: bold;
        }
        .mb-10 {
            margin-bottom: 10px;
        }

        .mb-40 {
            margin-bottom: 40px;
        }
        .mb-20 {
            margin-bottom: 20px;
        }
        .mb-30 {
            margin-bottom: 30px;
        }
        .mb-20 {
            margin-bottom: 20px;
        }
        .mb-10 {
            margin-bottom: 10px;
        }
        .mb-5 {
            margin-bottom: 5px;
        }
        .bg-gray {
            background-color: #bbbbbb;
        }

        ul {
            list-style-type: disc;
            margin: 0;
            padding-left: 20px;
            line-height: 1.5;
        }
        ul ul {
            list-style-type: none;
            padding-left: 20px;
        }
        ul ul li::before {
            content: "- ";
        }

        /* Styles for Annex A */
        .annex-a-b-c .header {
            text-align: center;
            margin-bottom: 0;
        }

        .annex-a-b-c .header h3 {
            font-size: 18px;
            font-weight: bold;
        }


        .annex-a-b-c .checkbox-group label {
            margin-right: 15px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .annex-a-b-c .checkbox-group input[type="checkbox"] {
            margin: 0;
            vertical-align: middle;
        }


        .annex-a-b-c table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .annex-a-b-c .taybol {
            font-size: 8px;
        }

        .annex-a-b-c .taybol table,
        .annex-a-b-c .taybol th,
        .annex-a-b-c .taybol tr,
        .annex-a-b-c .taybol td {
            border: 1px solid;
        }

        .annex-a-b-c .taybol th {
            background-color: #B3B3B3;
        }

        .annex-a-b-c th,
        .annex-a-b-c td {
            padding: 8px;
            text-align: center;
            font-size: 12px;
        }

        .annex-a-b-c th {
            padding-bottom: 0;
        }

        .annex-a-b-c .names td {
            text-align: left;
            vertical-align: top;
        }

        /* Automatic Page Numbering for Annex A */
        .annex-a-page-number {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-style: italic;
            font-size: 10px;
        }
        /* Styles for Annex A -- END */

    </style>
</head>

<body>
    <div class="container">

        {{-- Annex A --}}
        <div class="annex-a-b-c">
            <!-- Mark Annex A start page (for footer page numbering range) -->
            <script type="text/php">
                if (isset($pdf) && !isset($GLOBALS['annexAStartPage'])) {
                    $GLOBALS['annexAStartPage'] = $pdf->get_page_number();
                }
            </script>
             <!-- Revision Date Section -->
            <table style="width: 100%; font-size: 9px;">
                <tr>
                    <td style="vertical-align: top; width: 23%; font-size: 22px; text-align: left;">
                        ANNEX A
                    </td>
                    <td style="width: 54%;"></td>
                    <td style="text-align: left; font-size: 9px; margin: 0; padding: 0; width: 23%;">
                        <p style="margin: 10px; ">2025 SALN Form <br>Per CSC Resolution No.___________<br>Promulgated on ___________</p>
                    </td>
                </tr>
            </table>

            <!------- Header Section -------->

            <!-- Sworn Statement of Assets, Liabilities and Net Worth Section -->
            <div class="header" style="margin-bottom: 0;">
                <div style="text-align: center; margin-top: 0;">
                    <h3 style="margin: 0; font-size: 16px;">SWORN STATEMENT OF ASSETS, LIABILITIES AND NET WORTH</h3>
                    <span style="font-size:12px;">(As required by R.A. No. 6713)</span>
                </div>
                <table style="width: 100%; margin-top: 12px; padding: 0px;">
                    <tr>
                        <th colspan="6" style="padding-bottom: 4px; font-size: 12px; text-center"><strong>COMPLIANCE FOR:</strong></th>
                    </tr>
                    <tr>
                        <td style="width: 10px; padding-top: 0; text-align: center;"><input type="checkbox" {{ $complianceType === 'assumption' ? 'checked' : '' }} /></td>
                        <td style="font-style: italic; padding-top: 0; text-align: left; font-size: 12px;">Assumption of office as of @if($complianceType === 'assumption' && $complianceDate)<span>{{ \Carbon\Carbon::parse($complianceDate)->format('m/d/Y') }}</span>@else<span class="underline"></span>@endif</td>
                        <td style="width: 10px; padding-top: 0; text-align: center;"><input type="checkbox" {{ $complianceType === 'annual' ? 'checked' : '' }} /></td>
                        <td style="font-style: italic; padding-top: 0; text-align: left; font-size: 12px;">Annual filing as of December 31, @if($complianceType === 'annual' && $complianceDate)<span>{{ \Carbon\Carbon::parse($complianceDate)->format('Y') }}</span>@else<span class="underline"></span>@endif</td>
                        <td style="width: 10px; padding-top: 0; text-align: center;"><input type="checkbox" {{ $complianceType === 'exit' ? 'checked' : '' }} /></td>
                        <td style="font-style: italic; padding-top: 0; text-align: left; font-size: 12px;">Exit as of @if($complianceType === 'exit' && $complianceDate)<span>{{ \Carbon\Carbon::parse($complianceDate)->format('m/d/Y') }}</span>@else<span class="underline"></span>@endif</td>
                    </tr>
                </table>
            </div>

            <hr style="border: 0.5px solid black;">
            <hr style="border: 0.5px solid black; margin-top:-6px;">

            <!-- Declarant Information -->
            <div class="section-title" style="margin-top: 0; margin-bottom: 20px;">
                <table class="names" style="width: 100%; border-collapse: collapse; ">
                    <tr>
                        <td style="text-align: left; padding: 0; padding-right: 12px; width: 55%;">
                            <table style="width: 100%; border-collapse: collapse; margin: 0;">
                                <!-- Row 1: DECLARANT -->
                                <tr>
                                    <!-- Column 1: label -->
                                    <td style="text-align: left; padding: 5px; width: 30%; vertical-align: top;">
                                        <strong>DECLARANT:</strong>
                                    </td>

                                    <!-- Column 2: three separate name fields -->
                                    <td style="text-align: left; padding: 5px; vertical-align: top;">
                                        <table style="width: 100%; border-collapse: collapse; margin-top: 0;">
                                            <tr>
                                                <td style="text-align: center; border-bottom: 1px solid #000; padding: 0 5px; font-size: 10px; {{ empty($info[0]->last_name) ? 'padding-top: 5px;' : '' }}">
                                                    {{ ($info[0]->last_name ?? '') !== '' ? $info[0]->last_name : 'N/A' }}
                                                </td>
                                                <td style="text-align: center; border-bottom: 1px solid #000; padding: 0 5px; font-size: 10px; {{ empty($info[0]->first_name) ? 'padding-top: 5px;' : '' }}">
                                                    {{ ($info[0]->first_name ?? '') !== '' ? $info[0]->first_name : 'N/A' }}
                                                </td>
                                                <td style="text-align: center; border-bottom: 1px solid #000; padding: 0 5px; font-size: 10px; {{ empty($info[0]->middle_name) ? 'padding-top: 5px;' : '' }}">
                                                    {{ ($info[0]->middle_name ?? '') !== '' ? $info[0]->middle_name : 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="text-align: center; font-size: 10px; padding-top: 2px;">
                                                    (Family Name)
                                                </td>
                                                <td style="text-align: center; font-size: 10px; padding-top: 2px;">
                                                    (First Name)
                                                </td>
                                                <td style="text-align: center; font-size: 10px; padding-top: 2px;">
                                                    (M.I.)
                                                </td>
                                            </tr>
                                        </table>
                                        <div style="border-bottom: 1px solid #000; width: 100%; margin-top: 0;">
                                            &nbsp;
                                        </div>
                                        <div style="border-bottom: 1px solid #000; width: 100%; margin-top: 5px;">
                                            &nbsp;
                                        </div>
                                    </td>
                                </tr>

                                <!-- Row 2: SPOUSE -->
                                <tr>
                                    <!-- Column 1: label -->
                                    <td style="text-align: left; padding: 5px; width: 30%; vertical-align: top;">
                                        <strong>SPOUSE:</strong>
                                    </td>

                                    <!-- Column 2: three separate name fields -->
                                    <td style="text-align: left; padding: 5px; padding-top: 0; vertical-align: top;">
                                        <table style="width: 100%; border-collapse: collapse;">
                                            <tr>
                                                <td style="text-align: center; border-bottom: 1px solid #000; padding: 0 5px; font-size: 10px; {{ empty($info[0]->spouse_last_name) ? 'padding-top: 5px;' : '' }}">
                                                    {{ ($info[0]->spouse_last_name ?? '') !== '' ? $info[0]->spouse_last_name : 'N/A' }}
                                                </td>
                                                <td style="text-align: center; border-bottom: 1px solid #000; padding: 0 5px; font-size: 10px; {{ empty($info[0]->spouse_first_name) ? 'padding-top: 5px;' : '' }}">
                                                    {{ ($info[0]->spouse_first_name ?? '') !== '' ? $info[0]->spouse_first_name : 'N/A' }}
                                                </td>
                                                <td style="text-align: center; border-bottom: 1px solid #000; padding: 0 5px; font-size: 10px; {{ empty($info[0]->spouse_middle_name) ? 'padding-top: 5px;' : '' }}">
                                                    {{ ($info[0]->spouse_middle_name ?? '') !== '' ? $info[0]->spouse_middle_name : 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="text-align: center; font-size: 10px; padding: 2px 0px;">
                                                    (Family Name)
                                                </td>
                                                <td style="text-align: center; font-size: 10px; padding: 2px 0px;">
                                                    (First Name)
                                                </td>
                                                <td style="text-align: center; font-size: 10px; padding: 2px 0px;">
                                                    (M.I.)
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>

                        <td style="text-align: left; vertical-align: top; padding: 0; padding-left: 13px; width: 45%;">
                            <table style="width: 100%; border-collapse: collapse; margin-top: 0;">
                                <!-- POSITION -->
                                <tr>
                                    <td style="text-align: left; padding: 2px; width: 40%; vertical-align: middle;">
                                        <strong>POSITION:</strong>
                                    </td>
                                    <td style="text-align: left; padding: 2px; vertical-align: middle;">
                                        <div style="border-bottom: 1px solid #000; width: 100%; font-size: 9px; {{ empty($info[0]->position) ? 'padding-top: 5px;' : '' }}">
                                            {{ $info[0]->position ?? 'N/A' }}
                                        </div>
                                    </td>
                                </tr>
                                <!-- AGENCY/OFFICE -->
                                <tr>
                                    <td style="text-align: left; padding: 2px; width: 40%; vertical-align: middle;">
                                        <strong>AGENCY/OFFICE:</strong>
                                    </td>
                                    <td style="text-align: left; padding: 2px; vertical-align: middle;">
                                        <div style="border-bottom: 1px solid #000; width: 100%; font-size: 10px; {{ empty($info[0]->department) ? 'padding-top: 5px;' : '' }}">
                                            {{ $info[0]->department ?? '' }}
                                        </div>
                                    </td>
                                </tr>
                                <!-- OFFICE ADDRESS -->
                                <tr>
                                    <td style="text-align: left; padding: 2px; width: 40%; vertical-align: middle;">
                                        <strong>OFFICE ADDRESS:</strong>
                                    </td>
                                    <td style="text-align: left; padding: 2px; vertical-align: middle;">
                                        <div style="border-bottom: 1px solid #000; width: 100%; font-size: 10px; {{ empty($info[0]->office_address) ? 'padding-top: 5px;' : '' }}">
                                            {{ $info[0]->office_address ?? 'N/A' }}
                                        </div>
                                    </td>
                                </tr>
                            </table>


                            <table style="width: 100%; border-collapse: collapse;">
                                <!-- SPOUSE POSITION -->
                                <tr>
                                    <td style="text-align: left; padding: 2px; width: 40%; vertical-align: middle;">
                                        <strong>POSITION:</strong>
                                    </td>
                                    <td style="text-align: left; padding: 2px; vertical-align: middle;">
                                        <div style="border-bottom: 1px solid #000; width: 100%; font-size: 10px; {{ (empty($info[0]->spouse_occupation) || $info[0]->spouse_occupation === 'N/A') ? 'padding-top: 5px;' : '' }}">
                                            {{ ($info[0]->spouse_occupation && $info[0]->spouse_occupation !== 'N/A') ? $info[0]->spouse_occupation : 'N/A' }}
                                        </div>
                                    </td>
                                </tr>
                                <!-- SPOUSE AGENCY/OFFICE -->
                                <tr>
                                    <td style="text-align: left; padding: 2px; width: 40%; vertical-align: middle;">
                                        <strong>AGENCY/OFFICE:</strong>
                                    </td>
                                    <td style="text-align: left; padding: 2px; vertical-align: middle;">
                                        <div style="border-bottom: 1px solid #000; width: 100%; font-size: 10px; {{ (empty($info[0]->spouse_employer) || $info[0]->spouse_employer === 'N/A') ? 'padding-top: 5px;' : '' }}">
                                            {{ ($info[0]->spouse_employer && $info[0]->spouse_employer !== 'N/A') ? $info[0]->spouse_employer : 'N/A' }}
                                        </div>
                                    </td>
                                </tr>
                                <!-- SPOUSE OFFICE ADDRESS -->
                                <tr>
                                    <td style="text-align: left; padding: 2px; width: 40%; vertical-align: middle;">
                                        <strong>OFFICE ADDRESS:</strong>
                                    </td>
                                    <td style="text-align: left; padding: 2px; vertical-align: middle;">
                                        <div style="border-bottom: 1px solid #000; width: 100%; font-size: 10px; {{ (empty($info[0]->spouse_business_address) || $info[0]->spouse_business_address === 'N/A') ? 'padding-top: 5px;' : '' }}">
                                            {{ ($info[0]->spouse_business_address && $info[0]->spouse_business_address !== 'N/A') ? $info[0]->spouse_business_address : 'N/A' }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>

            @php
            // Normalize filing selection coming from the UI (joint / separate / na)
            $filing = $filing ?? null;
            @endphp
            <div style="margin-bottom: 10px;">
                <div class="font-12" style="font-weight: bold; margin-bottom: 5px;">
                    SPOUSES, WHO ARE BOTH PUBLIC OFFICIALS OR EMPLOYEES, MAY FILE THE SALN JOINTLY OR SEPARATELY. <br>
                    THE DECLARANT SHALL CHECK THE APPROPRIATE BOX
                </div>
                <div style="padding-left: 20px;">
                    <label class="font-12" style="margin-right: 30px; font-style: italic; font-size: 12px; display: inline-block; vertical-align: middle;">
                        <input type="checkbox" style="margin-right: 5px; vertical-align: middle;" {{ $filing === 'joint' ? 'checked' : '' }}>
                        Joint Filing
                    </label>
                    <label class="font-12" style="margin-right: 30px; font-style: italic; font-size: 12px; display: inline-block; vertical-align: middle;">
                        <input type="checkbox" style="margin-right: 5px; vertical-align: middle;" {{ $filing === 'separate' ? 'checked' : '' }}>
                        Separate Filing
                    </label>
                    <label class="font-12" style="font-style: italic; font-size: 12px; display: inline-block; vertical-align: middle;">
                        <input type="checkbox" style="margin-right: 5px; vertical-align: middle;" {{ $filing === 'na' ? 'checked' : '' }}>
                        Not Applicable
                    </label>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <div class="font-12" style="font-weight: bold; margin-bottom: 0;">
                    IF WITH MULTIPLE MARRIAGES, INDICATE NAME(S) OF SPOUSES, OTHERWISE CHECK THE "NOT APPLICABLE" BOX.
                </div>
                <div style="padding: 0px 0px 0px 20px; ">
                    <table style="width: 100%; border-collapse: collapse; margin: 0;">
                        <tr>
                            <td style="vertical-align: top; width: 45%; padding: 0;">
                                <div style="border-bottom: 1px solid #000; width: 100%; margin: 0; padding: 0;">
                                    &nbsp;
                                </div>
                                <div style="border-bottom: 1px solid #000; width: 100%; margin: 0; padding: 0;">
                                    &nbsp;
                                </div>
                            </td>
                            <td style="vertical-align: top; text-align: left; padding: 0px 0px 0px 60px; ">
                                <label class="font-12" style="font-style: italic; font-size: 12px; display: inline-block; vertical-align: middle;">
                                    {{-- System assumes only one spouse, so default this to checked --}}
                                    <input type="checkbox" style="margin-right: 5px; vertical-align: middle;" checked>
                                    Not Applicable
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <hr style="border: 0.5px solid black;">
            <hr style="border: 0.5px solid black; margin-top:-6px;">


            <!-- Unmarried Children Section -->
            <div class="section-title" style="font-size: 13.3px; font-weight: bold; text-align: center;">
                <u>UNMARRIED CHILDREN BELOW EIGHTEEN (18) YEARS OF AGE LIVING IN DECLARANT'S HOUSEHOLD</u>
            </div>
            <div class="table-container">
                <table style="width: 100%; table-layout: fixed;">
                    <thead>
                        <tr>
                            <th style="width: 80%; text-align: center; vertical-align: top;">Name of Child</th>
                            <th style="width: 20%; text-align: center; vertical-align: top;">Age</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($children as $child)
                            @php
                                $age = $child->child_birthdate ? \Carbon\Carbon::parse($child->child_birthdate)->age : null;
                                $isUnder18 = $age !== null && $age <= 18;
                                $childName = trim(($child->child_name ?? '') . ' ' . ($child->child_middlename ?? '') . ' ' . ($child->child_lastname ?? ''));
                            @endphp
                            <tr>
                                <td style="text-align: center; font-size: 10px; padding: 4px 5px;">
                                    <div style="border-bottom: 1px solid #000; width: 95%; margin: 0 auto; min-height: 12px; line-height: 1.2;">
                                        {{ $isUnder18 && $childName ? $childName : 'N/A' }}
                                    </div>
                                </td>
                                <td style="text-align: center; font-size: 10px; padding: 4px 5px;">
                                    <div style="border-bottom: 1px solid #000; width: 95%; margin: 0 auto; min-height: 12px; line-height: 1.2;">
                                        {{ $isUnder18 && $age !== null ? $age . ' years old' : 'N/A' }}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            @for($i = 0; $i < 3; $i++)
                                <tr>
                                    <td style="padding: 4px 5px; text-align: center; font-size: 10px;">
                                        <div style="border-bottom: 1px solid #000; width: 95%; margin: 0 auto; min-height: 12px; line-height: 1.2;">N/A</div>
                                    </td>
                                    <td style="padding: 4px 5px; text-align: center; font-size: 10px;">
                                        <div style="border-bottom: 1px solid #000; width: 95%; margin: 0 auto; min-height: 12px; line-height: 1.2;">N/A</div>
                                    </td>
                                </tr>
                            @endfor
                        @endforelse
                    </tbody>
                </table>
            </div>


            <hr style="border: 0.5px solid black;">
            <hr style="border: 0.5px solid black; margin-top:-6px;">

            <!-- Assets, Liabilities and Net Worth Section -->
            <div style="text-align: center; margin-bottom: 10px;">
                <h3 style="text-align: center; font-size: 14px; margin: 0; padding: 0;">
                    <u>ASSETS, LIABILITIES AND NETWORTH</u>
                </h3>
                <div style="text-align: center; font-size: 12px; margin-top: 5px;">
                    (Including those of the spouse and unmarried children below eighteen (18) years of age living in declarant's household)
                </div>
            </div>
            <div>
                <h3 style="font-size: 12px; margin: 0; padding: 0;">1. ASSETS</h3>

                <!-- Section a: Real Properties -->
                <div>
                    <table class="taybol" style="margin: 0;">
                        <thead style="display: table-header-group;">
                            <tr style="border: none; page-break-inside: avoid;">
                                <th colspan="8" style="text-align: left; font-size: 12px; border: none; background-color: transparent; padding-left: 20px;">a. Real Properties *</th>
                            </tr>
                            <tr style="page-break-inside: avoid;">
                                <th rowspan="2" style="text-align: center; vertical-align: top;">DESCRIPTION <br><span style="font-size: 10px; font-weight: normal;">(e.g. lot, house and <br> lot, condominium, <br> and improvements)</span></th>
                                <th rowspan="2" style="text-align: center; vertical-align: top;">KIND <br><span style="font-size: 10px; font-weight: normal;">(e.g. residential,<br> <span style="white-space: nowrap;">commercial, industrial,</span> <br> agricultural and mixed use)</th>
                                <th rowspan="2" style="text-align: center; vertical-align: top;">EXACT LOCATION</th>
                                <th style="text-align: center; vertical-align: top;"> <span style="white-space: nowrap;">ASSESSED <br> VALUE</span></th>
                                <th style="text-align: center; vertical-align: top;"> <span style="white-space: nowrap;">CURRENT FAIR <br> MARKET VALUE</span></th>

                                <th colspan="2" style="text-align: center; vertical-align: top;">ACQUISITION</th>
                                <th rowspan="2" style="text-align: center; vertical-align: top;">ACQUISITION COST</th>
                            </tr>
                            <tr style="page-break-inside: avoid;">
                                <th colspan="2" style="font-size: 10px; font-weight: normal; text-align: center; vertical-align: top;">(As found in the Tax Declaration of Real Property)</th>
                                <th style="text-align: center; vertical-align: top;">YEAR</th>
                                <th style="text-align: center; vertical-align: top;">MODE</th>
                            </tr>
                        </thead>

                        @php
                            $realSubtotal = $realProperties->sum('acquisition_cost');
                            $personalSubtotal = $personalProperties->sum('acquisition_cost');
                            $totalAssets = $realSubtotal + $personalSubtotal;
                        @endphp

                        <tbody id="realPropertiesBody">
                            @forelse ($realProperties as $property)
                                <tr>
                                    <td>{{ ($property->description ?? '') !== '' ? $property->description : 'N/A' }}</td>
                                    <td>{{ ($property->kind ?? '') !== '' ? $property->kind : 'N/A' }}</td>
                                    <td>{{ ($property->exact_location ?? '') !== '' ? $property->exact_location : 'N/A' }}</td>
                                    <td>{{ ($property->assessed_value ?? '') !== '' ? $property->assessed_value : 'N/A' }}</td>
                                    <td>{{ ($property->current_fair_market_value ?? '') !== '' ? $property->current_fair_market_value : 'N/A' }}</td>
                                    <td>{{ ($property->acquisition_year ?? '') !== '' ? $property->acquisition_year : 'N/A' }}</td>
                                    <td>{{ ($property->acquisition_mode ?? '') !== '' ? $property->acquisition_mode : 'N/A' }}</td>
                                    <td>{{ ($property->acquisition_cost ?? '') !== '' ? $property->acquisition_cost : 'N/A' }}</td>
                                </tr>
                            @empty
                                @for($i = 0; $i < 3; $i++)
                                    <tr>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                    </tr>
                                @endfor
                            @endforelse
                            <tr style="border: none;">
                                <td colspan="6" style="text-align: right; font-weight: bold; border: none; padding: 2px;">Subtotal:</td>
                                <td colspan="2" style="text-align: right; font-weight: bold; border: none; border-bottom: 0.2px solid #000; padding: 2px;">{{ number_format($realSubtotal, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>

                </div>

                <!-- Section b: Personal Properties -->
                <div>
                    @php
                        $personalSubtotal = collect($personalProperties)->sum(function ($property) {
                            return floatval(preg_replace('/[^\d.]/', '', $property->acquisition_cost));
                        });
                    @endphp

                    <table class="taybol" style="width: 100%; table-layout: fixed; margin: 0; page-break-inside: avoid;">
                        <thead style="display: table-header-group;">
                            <tr style="border: none; page-break-inside: avoid;">
                                <th colspan="3" style="text-align: left; font-size: 12px; border: none; background-color: transparent; padding-left: 20px;">b. Personal Properties *</th>
                            </tr>
                            <tr style="page-break-inside: avoid;">
                                <th style="width: 60%; text-align: center; vertical-align: middle; padding: 5px;">DESCRIPTION</th>
                                <th style="width: 20%; text-align: center; vertical-align: middle; padding: 5px;">YEAR ACQUIRED</th>
                                <th style="width: 20%; text-align: center; vertical-align: middle; padding: 5px;">ACQUISITION COST/AMOUNT</th>
                            </tr>
                        </thead>

                        <tbody id="personalPropertiesBody">
                            @forelse ($personalProperties ?? [] as $property)
                                <tr>
                                    <td>{{ ($property->description ?? '') !== '' ? $property->description : 'N/A' }}</td>
                                    <td>{{ ($property->year_acquired ?? '') !== '' ? $property->year_acquired : 'N/A' }}</td>
                                    <td>{{ ($property->acquisition_cost ?? '') !== '' ? $property->acquisition_cost : 'N/A' }}</td>
                                </tr>
                            @empty
                                @for($i = 0; $i < 3; $i++)
                                    <tr>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                    </tr>
                                @endfor
                            @endforelse
                        </tbody>
                    </table>

                    <div style="page-break-inside: avoid;">
                        <table class="taybol" style="width: 40%; table-layout: fixed; margin: 0; margin-left: auto; margin-right: 0;">
                            <tbody>
                                <tr style="border: none;">
                                    <td colspan="2" style="text-align: right; font-weight: bold; border: none; border-bottom: 0.2px solid #000; padding: 2px;">Subtotal:</td>
                                    <td colspan="1" style="text-align: right; font-weight: bold; border: none; border-bottom: 0.2px solid #000; padding: 2px;">
                                        {{ number_format($personalSubtotal, 2) }}
                                    </td>
                                </tr>
                                <tr style="border: none;">
                                    <td colspan="2" style="text-align: right; font-weight: bold; border: none; border-bottom: 2px solid #000; padding: 2px;">TOTAL ASSETS (a+b):</td>
                                    <td colspan="1" style="text-align: right; font-weight: bold; border: none; border-bottom: 2px solid #000; padding: 2px;">
                                        {{ number_format($totalAssets, 2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


            <!-- Liabilities Section -->
            <div>
                @php
                    $totalLiabilities = collect($liabilities)->sum(function ($liability) {
                        return floatval(preg_replace('/[^\d.]/', '', $liability->outstanding_balance));
                    });
                @endphp
                <table class="taybol" style="width: 100%; table-layout: fixed; margin: 0;">
                    <thead style="display: table-header-group;">
                        <tr style="border: none; page-break-inside: avoid;">
                            <th colspan="3" style="text-align: left; font-size: 12px; border: none; background-color: transparent; padding-left: 20px;">2. LIABILITIES</th>
                        </tr>
                        <tr style="page-break-inside: avoid;">
                            <th style="width: 50%; text-align: center; vertical-align: top; padding: 8px;">NATURE</th>
                            <th style="width: 25%; text-align: center; vertical-align: top; padding: 8px;">NAME OF CREDITORS</th>
                            <th style="width: 25%; text-align: center; vertical-align: top; padding: 8px;">OUTSTANDING BALANCE</th>
                        </tr>
                    </thead>
                    <tbody id="liabilitiesBody">
                        @forelse ($liabilities ?? [] as $liability)
                            <tr>
                                <td>{{ ($liability->nature ?? '') !== '' ? $liability->nature : 'N/A' }}</td>
                                <td>{{ ($liability->creditor_name ?? '') !== '' ? $liability->creditor_name : 'N/A' }}</td>
                                <td>{{ ($liability->outstanding_balance ?? '') !== '' ? number_format($liability->outstanding_balance, 2) : 'N/A' }}</td>
                            </tr>
                        @empty
                            @for($i = 0; $i < 3; $i++)
                                <tr>
                                    <td style="padding: 6px 0px">N/A</td>
                                    <td style="padding: 6px 0px">N/A</td>
                                    <td style="padding: 6px 0px">N/A</td>
                                </tr>
                            @endfor
                        @endforelse
                    </tbody>
                </table>

                <div style="page-break-inside: avoid; margin-top: 2px;">
                    <table class="taybol" style="width: 80%; table-layout: fixed; margin: 0; margin-left: auto; margin-right: 0;">
                        <tbody>
                            <tr style="border: none;">
                                <td colspan="2" style="text-align: right; font-weight: bold; border: none; padding: 2px;">TOTAL LIABILITIES:</td>
                                <td colspan="1" style="text-align: right; font-weight: bold; border: none; border-bottom: 2px solid #000; padding: 2px;">
                                    {{ number_format($totalLiabilities, 2) }}
                                </td>
                            </tr>
                            <tr style="border: none;">
                                <td colspan="2" style="text-align: right; font-weight: bold; border: none; padding: 2px;">NET WORTH : Total Assets less Total Liabilities =</td>
                                <td colspan="1" style="text-align: right; font-weight: bold; border: none; border-bottom: 2px solid #000; padding: 2px;">
                                    {{ number_format($totalAssets - $totalLiabilities, 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div> <br>


            <!-- Business Interests and Financial Connections Section -->
            <div style="text-align: center; margin: 0;">
                <h3 style="text-align: center; font-size: 14px; margin: 0; padding: 0;">
                    <u>BUSINESS INTERESTS AND FINANCIAL CONNECTIONS OUTSTANDING BALANCE</u>
                </h3>
                <div style="text-align: center; font-size: 10px; font-style: italic; margin-top: 5px;">
                    (of Declarant /Declarant's spouse/ Unmarried Children Below Eighteen (18) years of Age Living in Declarant's Household)
                </div>
                @php
                    // Check if business interests array/collection is empty or all fields are empty
                    $hasBusinessInterests = false;
                    if (!empty($business)) {
                        foreach ($business as $buss) {
                            if (!empty($buss->entity_name) || !empty($buss->business_address) ||
                                !empty($buss->nature_of_business) || !empty($buss->date_acquired)) {
                                $hasBusinessInterests = true;
                                break;
                            }
                        }
                    }
                @endphp
                <div class="checkbox-group" style="text-align: center; margin-top: 5px; margin-bottom: 0;">
                    <label style="margin: 0; display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-style: italic;">
                        <input type="checkbox" style="margin: 0; vertical-align: middle;" {{ !$hasBusinessInterests ? 'checked' : '' }}>
                        <span style="vertical-align: middle;">I/ We do not have any business interest or financial connection.</span>
                    </label>
                </div>
            </div>
            <table class="taybol" style="width: 100%; table-layout: fixed; margin: 0;">
                <thead style="display: table-header-group;">
                    <tr style="page-break-inside: avoid;">
                        <th style="width: 25%; text-align: center; vertical-align: middle; padding: 5px;"> <span style="white-space: nowrap;">NAME OF ENTITY/BUSINESS <br> ENTERPRISE</span></th>
                        <th style="width: 25%; text-align: center; vertical-align: middle; padding: 5px;">BUSINESS ADDRESS</th>
                        <th style="width: 25%; text-align: center; vertical-align: middle; padding: 5px;">NATURE OF BUSINESS INTEREST OR FINANCIAL CONNECTION</th>
                        <th style="width: 25%; text-align: center; vertical-align: middle; padding: 5px;"> <span style="white-space: nowrap;">DATE OF ACQUISITION OF <br> INTEREST OR CONNECTION </span></th>
                    </tr>
                </thead>
                <tbody id="businessInterestsBody">
                    @if($hasBusinessInterests)
                        @foreach ($business ?? [] as $buss)
                            <tr>
                                <td>{{ $buss->entity_name ?? '' !== '' ? $buss->entity_name : 'N/A' }}</td>
                                <td>{{ $buss->business_address ?? '' !== '' ? $buss->business_address : 'N/A' }}</td>
                                <td>{{ $buss->nature_of_business ?? '' !== '' ? $buss->nature_of_business : 'N/A' }}</td>
                                <td>{{ $buss->date_acquired ?? '' !== '' ? date('m-d-Y', strtotime($buss->date_acquired)) : 'N/A' }}</td>
                            </tr>
                        @endforeach
                    @else
                        @for($i = 0; $i < 3; $i++)
                            <tr>
                                <td style="padding: 6px 0px">N/A</td>
                                <td style="padding: 6px 0px">N/A</td>
                                <td style="padding: 6px 0px">N/A</td>
                                <td style="padding: 6px 0px">N/A</td>
                            </tr>
                        @endfor
                    @endif
                </tbody>
            </table>
            <br>


            <!-- Relatives in the Government Service Section -->
            <div style="text-align: center; margin: 0;">
                <h3 style="text-align: center; font-size: 14px; margin: 0; padding: 0;">
                    <u>RELATIVES IN THE GOVERNMENT SERVICE</u>
                </h3>
                <div style="text-align: center; font-size: 10px; font-style: italic; margin-top: 5px;">
                    (Within the Fourth Degree of Consanguinity or Affinity. Include also Bilas, Balae and Inso)
                </div>
                @php
                    // Check if relatives array/collection is empty or all fields are empty
                    $hasRelatives = false;
                    if (!empty($relatives)) {
                        foreach ($relatives as $relate) {
                            if (!empty($relate->relatives_name) || !empty($relate->relationship) ||
                                !empty($relate->position) || !empty($relate->office_address)) {
                                $hasRelatives = true;
                                break;
                            }
                        }
                    }
                @endphp
                <div class="checkbox-group" style="text-align: center; margin: 0;">
                    <label style="margin: 0; display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-style: italic;">
                        <input type="checkbox" style="margin: 0;vertical-align: middle;" {{ !$hasRelatives ? 'checked' : '' }}>
                        <span style="vertical-align: middle;">I/ We do not know of any relative/s in the government service.</span>
                    </label>
                </div>
            </div>
            <table class="taybol" style="width: 100%; table-layout: fixed; margin: 0;">
                <thead style="display: table-header-group;">

                    <tr style="page-break-inside: avoid;">
                        <th style="width: 20%; text-align: center; vertical-align: middle; padding: 8px;">NAME OF RELATIVE</th>
                        <th style="width: 20%; text-align: center; vertical-align: middle; padding: 8px;">RELATIONSHIP</th>
                        <th style="width: 20%; text-align: center; vertical-align: middle; padding: 8px;">POSITION</th>
                        <th style="width: 40%; text-align: center; vertical-align: middle; padding: 8px;"> <span style="white-space: nowrap;">NAME OF AGENCY/OFFICE AND ADDRESS</span></th>
                    </tr>
                </thead>
                <tbody id="businessRelativesBody">
                    @if($hasRelatives)
                        @foreach ($relatives ?? [] as $relate)
                            <tr>
                                <td>{{ $relate->relatives_name ?? '' !== '' ? $relate->relatives_name : 'N/A' }}</td>
                                <td>{{ $relate->relationship ?? '' !== '' ? $relate->relationship : 'N/A' }}</td>
                                <td>{{ $relate->position ?? '' !== '' ? $relate->position : 'N/A' }}</td>
                                <td>{{ $relate->office_address ?? '' !== '' ? $relate->office_address : 'N/A' }}</td>
                            </tr>
                        @endforeach
                    @else
                        @for($i = 0; $i < 3; $i++)
                            <tr>
                                <td style="padding: 6px 0px">N/A</td>
                                <td style="padding: 6px 0px">N/A</td>
                                <td style="padding: 6px 0px">N/A</td>
                                <td style="padding: 6px 0px">N/A</td>
                            </tr>
                        @endfor
                    @endif
                </tbody>
            </table>
            <div class="w-full max-w-4xl mx-auto border border-gray-300 p-6 bg-white shadow-md">
                <p style="text-indent: 40px; text-align: justify; font-size: 14px;">
                    I hereby certify that these are true and correct statements of my assets, liabilities, net worth,
                    business interests and financial connections, including those of my spouse and unmarried children below
                    eighteen (18) years of age living in my household, and that to the best of my knowledge, the
                    above-enumerated
                    are names of my relatives in the government within the fourth civil degree of consanguinity or affinity.
                </p>

                <p style="text-indent: 40px; text-align: justify; font-size: 14px;">
                    I hereby authorize the Ombudsman or his/her duly authorized representative to obtain and secure from all
                    appropriate government agencies, including the Bureau of Internal Revenue such documents that may show
                    my assets, liabilities, net worth, business interests and financial connections, to include those of my
                    spouse
                    and unmarried children below 18 years of age living with me in my household covering previous years
                    to include the year I first assumed office in government.
                </p>

                <div class="section-title">
                    <p style="margin-bottom: 8px;">Date: ___________________</p>
                    <table class="names" style="width: 100%; border: none;">

                        <tr>
                            <td style=" text-align: center; vertical-align: top;">
                                <strong>______________________________________________</strong>
                                <br><center>(Signature of Declarant)</center>
                            </td>
                            <td style=" text-align: center; vertical-align: top;">
                                <strong>______________________________________________</strong>
                                <br><center>(Signature of Co-Declarant/Spouse)</center>
                            </td>
                        </tr>
                        <tr>
                            <td style=" text-align: left; vertical-align: top;">
                                <p>Government Issued ID: _________________________</p>
                                <p>ID No.:               _________________________</p>
                                <p>Date Issued:          _________________________</p>
                            </td>
                            <td style="text-align: left; vertical-align: top;">
                                <p>Government Issued ID: _________________________</p>
                                <p>ID No.:               _________________________</p>
                                <p>Date Issued:          _________________________</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div>
                    <table style="width: 100%; margin-top: 0px;">
                        <tr>
                            <td colspan="2" style="text-align: left; font-size: 14px; text-indent: 4px; padding: 0;">
                    <strong>SUBSCRIBED AND SWORN</strong> to before me this __ day of ____, affiant exhibiting to me the
                    above-stated
                    government issued identification card.
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 0;"></td>
                            <td style="padding: 0; text-align: center;">
                                <div style="border-top: 1px solid black; font-size: 14px;  margin-top: 10px; font-style: italic; width: 70%; text-align: center;">(Person Administering Oath)</div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div style="margin-top: 5px; font-size: 10px;">
                    <p style="margin: 0;"><strong>i</strong>. Position, Agency, and Address shall only be declared if the spouse is a public official or employee.</p>
                    <p style="margin: 0;"><strong>ii</strong>. Additional sheets may be used by the declarant, if necessary.</p>
                    <p style="margin: 0;"><strong>iii</strong>. Capital or paraphernal assets, and liabilities of the declarant's spouse, and properties of children below 18 years of age and living in the declarant's household shall be disclosed using the additional sheets provided.</p>
                    <p style="margin: 0;"><strong>iv</strong>. <em>Balae</em> refers to the parent of one's son or daughter-in-law; <em>Bilas</em> refers to a brother-in-law's wife or sister-in-law's husband; <em>Inso</em> refers to the appellation for the wife of an elder brother or male cousin.</p>
            </div>
        </div>

        </div>
        {{-- Annex A --}}


        {{-- Next Page  --}}


        {{-- Annex B --}}
        <div class="annex-a-b-c" style="page-break-before: always;">
            <!-- Mark Annex B start page (end of Annex A numbering range) -->
            <script type="text/php">
                if (isset($pdf) && !isset($GLOBALS['annexBStartPage'])) {
                    $GLOBALS['annexBStartPage'] = $pdf->get_page_number();
                }
            </script>
            <!-- Revision Date Section -->
            <table style="width: 100%; font-size: 9px;">
                <tr>
                    <td style="vertical-align: top; width: 23%; font-size: 22px; text-align: left;">
                        ANNEX B
                    </td>
                    <td style="width: 54%;"></td>
                    <td style="text-align: left; font-size: 9px; margin: 0; padding: 0; width: 23%;">
                        <p style="margin: 10px; ">SALN Form AS-1 (Declarant)<br>Per CSC Resolution No.___________<br>Promulgated on ___________</p>
                    </td>
                </tr>
            </table>

            <!------- Header Section -------->

            <!-- Sworn Statement of Assets, Liabilities and Net Worth Section -->
            <div class="header" style="margin-bottom: 15px;">
                <div style="text-align: center;">
                    <h3 style="margin: 0; font-size: 16px; font-weight: bold;">SWORN STATEMENT OF ASSETS, LIABILITIES AND NET WORTH</h3>
                    <p style="margin: 0; font-size: 12px;">As of {{ $asOfDate ? \Carbon\Carbon::parse($asOfDate)->format('m/d/Y') : '___________' }}</p>
                    <p style="margin: 0; font-size: 12px; font-style: italic;">(Additional sheet/s for the declarant)</p>
                </div>
            </div>

            <!-- Declarant Information -->
            <div class="section-title" style="margin-top: 0; margin-bottom: 20px;">
                <table class="names" style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="text-align: left; padding: 0; padding-right: 10px; width: 55%;">
                            <table style="width: 100%; border-collapse: collapse; margin: 0; ">
                                <!-- Row 1: DECLARANT -->
                                <tr>
                                    <!-- Column 1: label -->
                                    <td style="text-align: left; padding: 5px; width: 20%; vertical-align: top; font-size: 12px;">
                                        <strong>NAME:</strong>
                                    </td>

                                    <!-- Column 2: three separate name fields -->
                                    <td style="text-align: left; padding: 5px; vertical-align: top;">
                                        <table style="width: 100%; border-collapse: collapse; margin-top: 0;">
                                            <tr>
                                                <td style="text-align: center; border-bottom: 1px solid #000; padding: 0 5px; font-size: 10px;">
                                                    {{ $info[0]->last_name ?? 'N/A' }}
                                                </td>
                                                <td style="text-align: center; border-bottom: 1px solid #000; padding: 0 5px; font-size: 10px;">
                                                    {{ $info[0]->first_name ?? 'N/A' }}
                                                </td>
                                                <td style="text-align: center; border-bottom: 1px solid #000; padding: 0 5px; font-size: 10px;">
                                                    {{ $info[0]->middle_name ?? 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="text-align: center; font-size: 10px; padding-top: 2px;">
                                                    (Family Name)
                                                </td>
                                                <td style="text-align: center; font-size: 10px; padding-top: 2px;">
                                                    (First Name)
                                                </td>
                                                <td style="text-align: center; font-size: 10px; padding-top: 2px;">
                                                    (M.I.)
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>

                        <td style="text-align: left; vertical-align: top; padding: 0; padding-left: 10px; width: 45%;">
                            <table style="width: 100%; border-collapse: collapse; margin-top: 0;">
                                <!-- POSITION -->
                                <tr>
                                    <td style="text-align: left; padding: 2px; width: 40%; vertical-align: middle; font-size: 12px;">
                                        <strong>POSITION:</strong>
                                    </td>
                                    <td style="text-align: left; padding: 2px; vertical-align: middle;">
                                        <div style="border-bottom: 1px solid #000; width: 100%; font-size: 9px;">
                                            {{ $info[0]->position ?? 'N/A' }}
                                        </div>
                                    </td>
                                </tr>
                                <!-- AGENCY/OFFICE -->
                                <tr>
                                    <td style="text-align: left; padding: 2px; width: 40%; vertical-align: middle; font-size: 12px;">
                                        <strong>AGENCY/OFFICE:</strong>
                                    </td>
                                    <td style="text-align: left; padding: 2px; vertical-align: middle;">
                                        <div style="border-bottom: 1px solid #000; width: 100%; font-size: 10px;">
                                            {{ $info[0]->department ?? 'N/A' }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>


            <hr style="border: 0.5px solid black;">
            <hr style="border: 0.5px solid black; margin-top:-6px;">

            <!-- Assets, Liabilities and Net Worth Section -->
            <div style="text-align: center; margin-bottom: 10px;">
                <h3 style="text-align: center; font-size: 14px; margin: 0; padding: 0;">
                    <u>ASSETS, LIABILITIES AND NETWORTH</u>
                </h3>
            </div>
            <div>
                <h3 style="font-size: 12px; margin: 0; padding: 0;">1. ASSETS</h3>

                <!-- Section a: Real Properties -->
                <div>
                    <table class="taybol" style="margin: 0;">
                        <thead style="display: table-header-group;">
                            <tr style="border: none; page-break-inside: avoid;">
                                <th colspan="8" style="text-align: left; font-size: 12px; border: none; background-color: transparent; padding-left: 20px;">a. Real Properties *</th>
                            </tr>
                            <tr style="page-break-inside: avoid;">
                                <th rowspan="2" style="text-align: center; vertical-align: top;">DESCRIPTION <br><span style="font-size: 10px; font-weight: normal;">(e.g. lot, house and <br> lot, condominium, <br> and improvements)</span></th>
                                <th rowspan="2" style="text-align: center; vertical-align: top;">KIND <br><span style="font-size: 10px; font-weight: normal;">(e.g. residential,<br> <span style="white-space: nowrap;">commercial, industrial,</span> <br> agricultural and mixed use)</th>
                                <th rowspan="2" style="text-align: center; vertical-align: top;">EXACT LOCATION</th>
                                <th style="text-align: center; vertical-align: top;"> <span style="white-space: nowrap;">ASSESSED <br> VALUE</span></th>
                                <th style="text-align: center; vertical-align: top;"> <span style="white-space: nowrap;">CURRENT FAIR <br> MARKET VALUE</span></th>

                                <th colspan="2" style="text-align: center; vertical-align: top;">ACQUISITION</th>
                                <th rowspan="2" style="text-align: center; vertical-align: top;">ACQUISITION COST</th>
                            </tr>
                            <tr style="page-break-inside: avoid;">
                                <th colspan="2" style="font-size: 10px; font-weight: normal; text-align: center; vertical-align: top;">(As found in the Tax Declaration of Real Property)</th>
                                <th style="text-align: center; vertical-align: top;">YEAR</th>
                                <th style="text-align: center; vertical-align: top;">MODE</th>
                            </tr>
                        </thead>

                        @php
                            $realSubtotal = $realProperties->sum('acquisition_cost');
                            $personalSubtotal = $personalProperties->sum('acquisition_cost');
                            $totalAssets = $realSubtotal + $personalSubtotal;
                        @endphp

                        <tbody id="realPropertiesBody">
                            @forelse ($realProperties as $property)
                                <tr>
                                    <td>{{ $property->description ?? '' !== '' ? $property->description : 'N/A' }}</td>
                                    <td>{{ $property->kind ?? '' !== '' ? $property->kind : 'N/A' }}</td>
                                    <td>{{ $property->exact_location ?? '' !== '' ? $property->exact_location : 'N/A' }}</td>
                                    <td>{{ $property->assessed_value ?? '' !== '' ? $property->assessed_value : 'N/A' }}</td>
                                    <td>{{ $property->current_fair_market_value ?? '' !== '' ? $property->current_fair_market_value : 'N/A' }}</td>
                                    <td>{{ $property->acquisition_year ?? '' !== '' ? $property->acquisition_year : 'N/A' }}</td>
                                    <td>{{ $property->acquisition_mode ?? '' !== '' ? $property->acquisition_mode : 'N/A' }}</td>
                                    <td>{{ $property->acquisition_cost ?? '' !== '' ? $property->acquisition_cost : 'N/A' }}</td>
                                </tr>
                            @empty
                                @for($i = 0; $i < 3; $i++)
                                    <tr>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                    </tr>
                                @endfor
                            @endforelse
                            <tr style="border: none;">
                                <td colspan="6" style="text-align: right; font-weight: bold; border: none; padding: 2px;">Subtotal:</td>
                                <td colspan="2" style="text-align: right; font-weight: bold; border: none; padding: 2px;">{{ number_format($realSubtotal, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>

                </div>

                <!-- Section b: Personal Properties -->
                <div>
                    @php
                        $personalSubtotal = collect($personalProperties)->sum(function ($property) {
                            return floatval(preg_replace('/[^\d.]/', '', $property->acquisition_cost));
                        });
                    @endphp

                    <table class="taybol" style="width: 100%; table-layout: fixed; margin: 0;">
                        <thead style="display: table-header-group;">
                            <tr style="border: none; page-break-inside: avoid;">
                                <th colspan="3" style="text-align: left; font-size: 12px; border: none; background-color: transparent; padding-left: 20px;">b. Personal Properties *</th>
                            </tr>
                            <tr style="page-break-inside: avoid;">
                                <th style="width: 60%; text-align: center; vertical-align: middle; padding: 5px;">DESCRIPTION</th>
                                <th style="width: 20%; text-align: center; vertical-align: middle; padding: 5px;">YEAR ACQUIRED</th>
                                <th style="width: 20%; text-align: center; vertical-align: middle; padding: 5px;">ACQUISITION COST/AMOUNT</th>
                            </tr>
                        </thead>

                        <tbody id="personalPropertiesBody">
                            @forelse ($personalProperties ?? [] as $property)
                                <tr>
                                    <td>{{ $property->description ?? '' !== '' ? $property->description : 'N/A' }}</td>
                                    <td>{{ $property->year_acquired ?? '' !== '' ? $property->year_acquired : 'N/A' }}</td>
                                    <td>{{ $property->acquisition_cost ?? '' !== '' ? $property->acquisition_cost : 'N/A' }}</td>
                                </tr>
                            @empty
                                @for($i = 0; $i < 3; $i++)
                                    <tr>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                    </tr>
                                @endfor
                            @endforelse
                        </tbody>
                    </table>

                    <div style="page-break-inside: avoid;">
                        <table class="taybol" style="width: 100%; table-layout: fixed; margin: 0;">
                            <tbody>
                                <tr style="border: none;">
                                    <td colspan="3" style="text-align: right; font-weight: bold; border: none; padding: 2px;">Subtotal: <strong>{{ number_format($personalSubtotal, 2) }}</strong></td>
                                </tr>
                                <tr style="border: none;">
                                    <td colspan="3" style="text-align: right; font-weight: bold; border: none; padding: 2px;">TOTAL ASSETS (a+b): <strong>{{ number_format($totalAssets, 2) }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>


            <!-- Liabilities Section -->
            <div>
                @php
                    $totalLiabilities = collect($liabilities)->sum(function ($liability) {
                        return floatval(preg_replace('/[^\d.]/', '', $liability->outstanding_balance));
                    });
                @endphp
                <table class="taybol" style="width: 100%; table-layout: fixed; margin: 0;">
                    <thead style="display: table-header-group;">
                        <tr style="border: none; page-break-inside: avoid;">
                            <th colspan="3" style="text-align: left; font-size: 12px; border: none; background-color: transparent; padding-left: 20px;">2. LIABILITIES</th>
                        </tr>
                        <tr style="page-break-inside: avoid;">
                            <th style="width: 50%; text-align: center; vertical-align: top; padding: 8px;">NATURE</th>
                            <th style="width: 25%; text-align: center; vertical-align: top; padding: 8px;">NAME OF CREDITORS</th>
                            <th style="width: 25%; text-align: center; vertical-align: top; padding: 8px;">OUTSTANDING BALANCE</th>
                        </tr>
                    </thead>
                    <tbody id="liabilitiesBody">
                        @forelse ($liabilities ?? [] as $liability)
                            <tr>
                                <td>{{ $liability->nature ?? '' !== '' ? $liability->nature : 'N/A' }}</td>
                                <td>{{ $liability->creditor_name ?? '' !== '' ? $liability->creditor_name : 'N/A' }}</td>
                                <td>{{ $liability->outstanding_balance ?? '' !== '' ? number_format($liability->outstanding_balance, 2) : 'N/A' }}</td>
                            </tr>
                        @empty
                            @for($i = 0; $i < 3; $i++)
                                <tr>
                                    <td style="padding: 6px 0px">N/A</td>
                                    <td style="padding: 6px 0px">N/A</td>
                                    <td style="padding: 6px 0px">N/A</td>
                                </tr>
                            @endfor
                        @endforelse
                        <tr style="border: none;">
                            <td colspan="3" style="text-align: right; font-weight: bold; border: none; padding: 2px;">TOTAL LIABILITIES: <strong>{{ number_format($totalLiabilities, 2) }}</strong></td>
                        </tr>
                        <tr style="border: none;">
                            <td colspan="3" style="text-align: right; font-weight: bold; border: none; padding: 2px;">NET WORTH : Total Assets less Total Liabilities = <strong>{{ number_format($totalAssets - $totalLiabilities, 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <hr style="border: 0.5px solid black;">
            <hr style="border: 0.5px solid black; margin-top:-6px;">

            <!-- Business Interests and Financial Connections Section -->
            <div style="text-align: center; margin: 15px;">
                <h3 style="text-align: center; font-size: 14px; margin: 0; padding: 0;">
                    <u>BUSINESS INTERESTS AND FINANCIAL CONNECTIONS OUTSTANDING BALANCE</u>
                </h3>
            </div>
            <table class="taybol" style="width: 100%; table-layout: fixed; margin: 0;">
                <thead style="display: table-header-group;">
                    <tr style="page-break-inside: avoid;">
                        <th style="width: 25%; text-align: center; vertical-align: middle; padding: 5px;"> <span style="white-space: nowrap;">NAME OF ENTITY/BUSINESS <br> ENTERPRISE</span></th>
                        <th style="width: 25%; text-align: center; vertical-align: middle; padding: 5px;">BUSINESS ADDRESS</th>
                        <th style="width: 25%; text-align: center; vertical-align: middle; padding: 5px;">NATURE OF BUSINESS INTEREST OR FINANCIAL CONNECTION</th>
                        <th style="width: 25%; text-align: center; vertical-align: middle; padding: 5px;"> <span style="white-space: nowrap;">DATE OF ACQUISITION OF <br> INTEREST OR CONNECTION </span></th>
                    </tr>
                </thead>
                <tbody id="businessInterestsBody">
                    @if($hasBusinessInterests)
                        @foreach ($business ?? [] as $buss)
                            <tr>
                                <td>{{ $buss->entity_name ?? '' !== '' ? $buss->entity_name : 'N/A' }}</td>
                                <td>{{ $buss->business_address ?? '' !== '' ? $buss->business_address : 'N/A' }}</td>
                                <td>{{ $buss->nature_of_business ?? '' !== '' ? $buss->nature_of_business : 'N/A' }}</td>
                                <td>{{ $buss->date_acquired ?? '' !== '' ? date('m-d-Y', strtotime($buss->date_acquired)) : 'N/A' }}</td>
                            </tr>
                        @endforeach
                    @else
                        @for($i = 0; $i < 3; $i++)
                            <tr>
                                <td style="padding: 6px 0px">N/A</td>
                                <td style="padding: 6px 0px">N/A</td>
                                <td style="padding: 6px 0px">N/A</td>
                                <td style="padding: 6px 0px">N/A</td>
                            </tr>
                        @endfor
                    @endif
                </tbody>
            </table>
        </div>
        {{-- Annex B --}}


        {{-- Next Page  --}}


        {{-- Annex C --}}
        <div class="annex-a-b-c" style="page-break-before: always;">
            <!-- Mark Annex C start page (end of Annex B numbering range) -->
            <script type="text/php">
                if (isset($pdf) && !isset($GLOBALS['annexCStartPage'])) {
                    $GLOBALS['annexCStartPage'] = $pdf->get_page_number();
                }
            </script>
            <!-- Revision Date Section -->
            <table style="width: 100%; font-size: 9px;">
                <tr>
                    <td style="vertical-align: top; width: 23%; font-size: 22px; text-align: left;">
                        ANNEX C
                    </td>
                    <td style="width: 54%;"></td>
                    <td style="text-align: left; font-size: 9px; margin: 0; padding: 0; width: 28%;">
                        <p style="margin: 10px; ">2025 SALN Form AS-2 (Spouse & Children) <br>Per CSC Resolution No.___________<br>Promulgated on ___________</p>
                    </td>
                </tr>
            </table>

            <!------- Header Section -------->

            <!-- Sworn Statement of Assets, Liabilities and Net Worth Section -->
            <div class="header" style="margin-bottom: 15px;">
                <div style="text-align: center;">
                    <h3 style="margin: 0; font-size: 16px; font-weight: bold;">SWORN STATEMENT OF ASSETS, LIABILITIES AND NET WORTH</h3>
                    <p style="margin: 0; font-size: 12px;">As of {{ $asOfDate ? \Carbon\Carbon::parse($asOfDate)->format('m/d/Y') : '___________' }}</p>
                    <p style="margin: 0; font-size: 12px; font-style: italic;">(Additional sheet/s for the exclusive properties of the declarant's spouse and unmarried children <br> below eighteen (18) years of age living in declarant's household) </p>
                </div>
            </div>

            <!-- Declarant Information -->
            <div class="section-title" style="margin-top: 0; margin-bottom: 20px;">
                <table class="names" style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="text-align: left; padding: 0; padding-right: 10px; width: 55%;">
                            <table style="width: 100%; border-collapse: collapse; margin: 0; ">
                                <!-- Row 1: DECLARANT -->
                                <tr>
                                    <!-- Column 1: label -->
                                    <td style="text-align: left; padding: 5px; width: 20%; vertical-align: top; font-size: 12px;">
                                        <strong>NAME:</strong>
                                    </td>

                                    <!-- Column 2: three separate name fields -->
                                    <td style="text-align: left; padding: 5px; vertical-align: top;">
                                        <table style="width: 100%; border-collapse: collapse; margin-top: 0;">
                                            <tr>
                                                <td style="text-align: center; border-bottom: 1px solid #000; padding: 0 5px; font-size: 10px;">
                                                    {{ $info[0]->last_name ?? 'N/A' }}
                                                </td>
                                                <td style="text-align: center; border-bottom: 1px solid #000; padding: 0 5px; font-size: 10px;">
                                                    {{ $info[0]->first_name ?? 'N/A' }}
                                                </td>
                                                <td style="text-align: center; border-bottom: 1px solid #000; padding: 0 5px; font-size: 10px;">
                                                    {{ $info[0]->middle_name ?? 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="text-align: center; font-size: 10px; padding-top: 2px;">
                                                    (Family Name)
                                                </td>
                                                <td style="text-align: center; font-size: 10px; padding-top: 2px;">
                                                    (First Name)
                                                </td>
                                                <td style="text-align: center; font-size: 10px; padding-top: 2px;">
                                                    (M.I.)
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>

                        <td style="text-align: left; vertical-align: top; padding: 0; padding-left: 10px; width: 45%;">
                            <table style="width: 100%; border-collapse: collapse; margin-top: 0;">
                                <!-- POSITION -->
                                <tr>
                                    <td style="text-align: left; padding: 2px; width: 40%; vertical-align: middle; font-size: 12px;">
                                        <strong>POSITION:</strong>
                                    </td>
                                    <td style="text-align: left; padding: 2px; vertical-align: middle;">
                                        <div style="border-bottom: 1px solid #000; width: 100%; font-size: 9px;">
                                            {{ $info[0]->position ?? 'N/A' }}
                                        </div>
                                    </td>
                                </tr>
                                <!-- AGENCY/OFFICE -->
                                <tr>
                                    <td style="text-align: left; padding: 2px; width: 40%; vertical-align: middle; font-size: 12px;">
                                        <strong>AGENCY/OFFICE:</strong>
                                    </td>
                                    <td style="text-align: left; padding: 2px; vertical-align: middle;">
                                        <div style="border-bottom: 1px solid #000; width: 100%; font-size: 10px;">
                                            {{ $info[0]->department ?? 'N/A' }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>


            <hr style="border: 0.5px solid black;">
            <hr style="border: 0.5px solid black; margin-top:-6px;">

            <!-- Assets, Liabilities and Net Worth Section -->
            <div style="text-align: center; margin-bottom: 10px;">
                <h3 style="text-align: center; font-size: 14px; margin: 0; padding: 0;">
                    <u>ASSETS, LIABILITIES AND NETWORTH</u>
                </h3>
            </div>
            <div>
                <h3 style="font-size: 12px; margin: 0; padding: 0;">1. ASSETS</h3>

                <!-- Section a: Real Properties -->
                <div>
                    <table class="taybol" style="margin: 0;">
                        <thead style="display: table-header-group;">
                            <tr style="border: none; page-break-inside: avoid;">
                                <th colspan="8" style="text-align: left; font-size: 12px; border: none; background-color: transparent; padding-left: 20px;">a. Real Properties *</th>
                            </tr>
                            <tr style="page-break-inside: avoid;">
                                <th rowspan="2" style="text-align: center; vertical-align: top;">DESCRIPTION <br><span style="font-size: 10px; font-weight: normal;">(e.g. lot, house and <br> lot, condominium, <br> and improvements)</span></th>
                                <th rowspan="2" style="text-align: center; vertical-align: top;">KIND <br><span style="font-size: 10px; font-weight: normal;">(e.g. residential,<br> <span style="white-space: nowrap;">commercial, industrial,</span> <br> agricultural and mixed use)</th>
                                <th rowspan="2" style="text-align: center; vertical-align: top;">EXACT LOCATION</th>
                                <th style="text-align: center; vertical-align: top;"> <span style="white-space: nowrap;">ASSESSED <br> VALUE</span></th>
                                <th style="text-align: center; vertical-align: top;"> <span style="white-space: nowrap;">CURRENT FAIR <br> MARKET VALUE</span></th>

                                <th colspan="2" style="text-align: center; vertical-align: top;">ACQUISITION</th>
                                <th rowspan="2" style="text-align: center; vertical-align: top;">ACQUISITION COST</th>
                            </tr>
                            <tr style="page-break-inside: avoid;">
                                <th colspan="2" style="font-size: 10px; font-weight: normal; text-align: center; vertical-align: top;">(As found in the Tax Declaration of Real Property)</th>
                                <th style="text-align: center; vertical-align: top;">YEAR</th>
                                <th style="text-align: center; vertical-align: top;">MODE</th>
                            </tr>
                        </thead>

                        @php
                            $realSubtotal = $realProperties->sum('acquisition_cost');
                            $personalSubtotal = $personalProperties->sum('acquisition_cost');
                            $totalAssets = $realSubtotal + $personalSubtotal;
                        @endphp

                        <tbody id="realPropertiesBody">
                            @forelse ($realProperties as $property)
                                <tr style="font-size: 10px;">
                                    <td>{{ $property->description ?? '' !== '' ? $property->description : 'N/A' }}</td>
                                    <td>{{ $property->kind ?? '' !== '' ? $property->kind : 'N/A' }}</td>
                                    <td>{{ $property->exact_location ?? '' !== '' ? $property->exact_location : 'N/A' }}</td>
                                    <td>{{ $property->assessed_value ?? '' !== '' ? $property->assessed_value : 'N/A' }}</td>
                                    <td>{{ $property->current_fair_market_value ?? '' !== '' ? $property->current_fair_market_value : 'N/A' }}</td>
                                    <td>{{ $property->acquisition_year ?? '' !== '' ? $property->acquisition_year : 'N/A' }}</td>
                                    <td>{{ $property->acquisition_mode ?? '' !== '' ? $property->acquisition_mode : 'N/A' }}</td>
                                    <td>{{ $property->acquisition_cost ?? '' !== '' ? $property->acquisition_cost : 'N/A' }}</td>
                                </tr>
                            @empty
                                @for($i = 0; $i < 3; $i++)
                                    <tr>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                    </tr>
                                @endfor
                            @endforelse
                            <tr style="border: none;">
                                <td colspan="6" style="text-align: right; font-weight: bold; border: none; padding: 2px;">Subtotal:</td>
                                <td colspan="2" style="text-align: right; font-weight: bold; border: none; padding: 2px;">{{ number_format($realSubtotal, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>

                </div>

                <!-- Section b: Personal Properties -->
                <div>
                    @php
                        $personalSubtotal = collect($personalProperties)->sum(function ($property) {
                            return floatval(preg_replace('/[^\d.]/', '', $property->acquisition_cost));
                        });
                    @endphp

                    <table class="taybol" style="width: 100%; table-layout: fixed; margin: 0;">
                        <thead style="display: table-header-group;">
                            <tr style="border: none; page-break-inside: avoid;">
                                <th colspan="3" style="text-align: left; font-size: 12px; border: none; background-color: transparent; padding-left: 20px;">b. Personal Properties *</th>
                            </tr>
                            <tr style="page-break-inside: avoid;">
                                <th style="width: 60%; text-align: center; vertical-align: middle; padding: 5px;">DESCRIPTION</th>
                                <th style="width: 20%; text-align: center; vertical-align: middle; padding: 5px;">YEAR ACQUIRED</th>
                                <th style="width: 20%; text-align: center; vertical-align: middle; padding: 5px;">ACQUISITION COST/AMOUNT</th>
                            </tr>
                        </thead>

                        <tbody id="personalPropertiesBody">
                            @forelse ($personalProperties ?? [] as $property)
                                <tr>
                                    <td>{{ $property->description ?? '' !== '' ? $property->description : 'N/A' }}</td>
                                    <td>{{ $property->year_acquired ?? '' !== '' ? $property->year_acquired : 'N/A' }}</td>
                                    <td>{{ $property->acquisition_cost ?? '' !== '' ? $property->acquisition_cost : 'N/A' }}</td>
                                </tr>
                            @empty
                                @for($i = 0; $i < 3; $i++)
                                    <tr>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                        <td style="padding: 6px 0px">N/A</td>
                                    </tr>
                                @endfor
                            @endforelse
                        </tbody>
                    </table>

                    <div style="page-break-inside: avoid;">
                        <table class="taybol" style="width: 100%; table-layout: fixed; margin: 0;">
                            <tbody>
                                <tr style="border: none;">
                                    <td colspan="3" style="text-align: right; font-weight: bold; border: none; padding: 2px;">Subtotal: <strong>{{ number_format($personalSubtotal, 2) }}</strong></td>
                                </tr>
                                <tr style="border: none;">
                                    <td colspan="3" style="text-align: right; font-weight: bold; border: none; padding: 2px;">TOTAL ASSETS (a+b): <strong>{{ number_format($totalAssets, 2) }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>


            <!-- Liabilities Section -->
            <div>
                @php
                    $totalLiabilities = collect($liabilities)->sum(function ($liability) {
                        return floatval(preg_replace('/[^\d.]/', '', $liability->outstanding_balance));
                    });
                @endphp
                <table class="taybol" style="width: 100%; table-layout: fixed; margin: 0;">
                    <thead style="display: table-header-group;">
                        <tr style="border: none; page-break-inside: avoid;">
                            <th colspan="3" style="text-align: left; font-size: 12px; border: none; background-color: transparent; padding-left: 20px;">2. LIABILITIES</th>
                        </tr>
                        <tr style="page-break-inside: avoid;">
                            <th style="width: 50%; text-align: center; vertical-align: top; padding: 8px;">NATURE</th>
                            <th style="width: 25%; text-align: center; vertical-align: top; padding: 8px;">NAME OF CREDITORS</th>
                            <th style="width: 25%; text-align: center; vertical-align: top; padding: 8px;">OUTSTANDING BALANCE</th>
                        </tr>
                    </thead>
                    <tbody id="liabilitiesBody">
                        @forelse ($liabilities ?? [] as $liability)
                            <tr>
                                <td>{{ $liability->nature ?? '' !== '' ? $liability->nature : 'N/A' }}</td>
                                <td>{{ $liability->creditor_name ?? '' !== '' ? $liability->creditor_name : 'N/A' }}</td>
                                <td>{{ $liability->outstanding_balance ?? '' !== '' ? number_format($liability->outstanding_balance, 2) : 'N/A' }}</td>
                            </tr>
                        @empty
                            @for($i = 0; $i < 3; $i++)
                                <tr>
                                    <td style="padding: 6px 0px">N/A</td>
                                    <td style="padding: 6px 0px">N/A</td>
                                    <td style="padding: 6px 0px">N/A</td>
                                </tr>
                            @endfor
                        @endforelse

                    </tbody>
                </table>
                <div style="page-break-inside: avoid;">
                    <table class="taybol" style="width: 100%; table-layout: fixed; margin: 0;">
                        <tbody>
                            <tr style="border: none;">
                                <td colspan="3" style="text-align: right; font-weight: bold; border: none; padding: 2px;">TOTAL LIABILITIES: <strong>{{ number_format($totalLiabilities, 2) }}</strong></td>
                            </tr>
                            <tr style="border: none;">
                                <td colspan="3" style="text-align: right; font-weight: bold; border: none; padding: 2px;">NET WORTH : Total Assets less Total Liabilities = <strong>{{ number_format($totalAssets - $totalLiabilities, 2) }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <hr style="border: 0.5px solid black;">
            <hr style="border: 0.5px solid black; margin-top:-6px;">

            <!-- Business Interests and Financial Connections Section -->
            <div style="text-align: center; margin: 15px;">
                <h3 style="text-align: center; font-size: 14px; margin: 0; padding: 0;">
                    <u>BUSINESS INTERESTS AND FINANCIAL CONNECTIONS OUTSTANDING BALANCE</u>
                </h3>
            </div>
            <table class="taybol" style="width: 100%; table-layout: fixed; margin: 0;">
                <thead style="display: table-header-group;">
                    <tr style="page-break-inside: avoid;">
                        <th style="width: 25%; text-align: center; vertical-align: middle; padding: 5px;"> <span style="white-space: nowrap;">NAME OF ENTITY/BUSINESS <br> ENTERPRISE</span></th>
                        <th style="width: 25%; text-align: center; vertical-align: middle; padding: 5px;">BUSINESS ADDRESS</th>
                        <th style="width: 25%; text-align: center; vertical-align: middle; padding: 5px;">NATURE OF BUSINESS INTEREST OR FINANCIAL CONNECTION</th>
                        <th style="width: 25%; text-align: center; vertical-align: middle; padding: 5px;"> <span style="white-space: nowrap;">DATE OF ACQUISITION OF <br> INTEREST OR CONNECTION </span></th>
                    </tr>
                </thead>
                <tbody id="businessInterestsBody">
                    @if($hasBusinessInterests)
                        @foreach ($business ?? [] as $buss)
                            <tr>
                                <td>{{ $buss->entity_name ?? '' !== '' ? $buss->entity_name : 'N/A' }}</td>
                                <td>{{ $buss->business_address ?? '' !== '' ? $buss->business_address : 'N/A' }}</td>
                                <td>{{ $buss->nature_of_business ?? '' !== '' ? $buss->nature_of_business : 'N/A' }}</td>
                                <td>{{ $buss->date_acquired ?? '' !== '' ? date('m-d-Y', strtotime($buss->date_acquired)) : 'N/A' }}</td>
                            </tr>
                        @endforeach
                    @else
                        @for($i = 0; $i < 3; $i++)
                            <tr>
                                <td style="padding: 6px 0px">N/A</td>
                                <td style="padding: 6px 0px">N/A</td>
                                <td style="padding: 6px 0px">N/A</td>
                                <td style="padding: 6px 0px">N/A</td>
                            </tr>
                        @endfor
                    @endif
                </tbody>
            </table>



        </div>
        {{-- Annex C --}}


        {{-- Next Page  --}}


        {{-- Annex D --}}
        <div style="page-break-before: always; margin-top: none;">
            <!-- Mark Annex D start page (end of Annex C numbering range) -->
            <script type="text/php">
                if (isset($pdf) && !isset($GLOBALS['annexDStartPage'])) {
                    $GLOBALS['annexDStartPage'] = $pdf->get_page_number();
                }
            </script>
            <div class="font-16 text-center" style="line-height: 1.5; margin-bottom: 8px; font-weight: bold;">
                ANNEX D <br> List of Repository Agencies and Corresponding Public Officials and Employees
            </div>
            <table class="font-12" style="margin-top: 20px; line-height: 1.5;">
                <thead>
                    <tr style="background-color: black;">
                        <th class="text-center" style="border: 1px solid black; padding: 0px; width: 35%; line-height: 1.5; color: white;">REPOSITORY AGENCY</th>
                        <th class="text-center" style="border: 1px solid black; padding: 0px; width: 65%; line-height: 1.5; color: white;">PUBLIC OFFICIALS AND EMPLOYEES</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-left" style="border: 1px solid black; padding: 8px; vertical-align: top; line-height: 1.5;">
                            <strong>National Office of the Ombudsman</strong>
                        </td>
                        <td class="text-left" style="border: 1px solid black; padding: 8px; vertical-align: top; line-height: 1.5;">
                            <ul>
                                <li>President</li>
                                <li>Vice President</li>
                                <li>Constitutional Officials which includes Chairpersons and Commissioners of:
                                    <ul>
                                        <li>Commission on Audit (COA)</li>
                                        <li>Commission on Election (COMELEC)</li>
                                        <li>Civil Service Commission (CSC)</li>
                                        <li>Ombudsman and his Deputies</li>
                                    </ul>
                                </li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-left" style="border: 1px solid black; padding: 8px; vertical-align: top; line-height: 1.5;">
                            <strong>Secretary of the Senate</strong>
                        </td>
                        <td class="text-left" style="border: 1px solid black; padding: 8px; vertical-align: top; line-height: 1.5;">
                            <ul>
                                <li>Senators</li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-left" style="border: 1px solid black; padding: 8px; vertical-align: top; line-height: 1.5;">
                            <strong>Secretary of the House of Representatives</strong>
                        </td>
                        <td class="text-left" style="border: 1px solid black; padding: 8px; vertical-align: top; line-height: 1.5;">
                            <ul>
                                <li>Representatives (Congressmen/Congresswomen)</li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-left" style="border: 1px solid black; padding: 8px; vertical-align: top; line-height: 1.5;">
                            <strong>Clerk of Court of the Supreme Court</strong>
                        </td>
                        <td class="text-left" style="border: 1px solid black; padding: 8px; vertical-align: top; line-height: 1.5;">
                            <ul>
                                <li>Justices of the:
                                    <ul>
                                        <li>Supreme Court</li>
                                        <li>Court of Appeals</li>
                                        <li>Sandiganbayan</li>
                                        <li>Court of Tax Appeals</li>
                                    </ul>
                                </li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-left" style="border: 1px solid black; padding: 8px; vertical-align: top; line-height: 1.5;">
                            <strong>Court Administrator</strong>
                        </td>
                        <td class="text-left" style="border: 1px solid black; padding: 8px; vertical-align: top; line-height: 1.5;">
                            <ul>
                                <li>Judges of the:
                                    <ul>
                                        <li>Regional Trial Court</li>
                                        <li>Metropolitan Trial Court</li>
                                        <li>Municipal Trial Court in Cities</li>
                                        <li>Municipal Trial Court</li>
                                        <li>Municipal Circuit Trial Court</li>
                                        <li>Shari'a District Courts</li>
                                    </ul>
                                </li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-left" style="border: 1px solid black; padding: 8px; vertical-align: top; line-height: 1.5;">
                            <strong>Office of the President</strong>
                        </td>
                        <td class="text-left" style="border: 1px solid black; padding: 8px; vertical-align: top; line-height: 1.5;">
                            <ul>
                                <li>National executive officials, including, but not limited to the following:
                                    <ul>
                                        <li>Members of the Cabinet</li>
                                        <li>Undersecretaries</li>
                                        <li>Assistant Secretaries</li>
                                        <li>Officials in the Foreign Service</li>
                                        <li>Heads of Government Owned or Controlled Corporations with Original Charters and their Subsidiaries</li>
                                        <li>Heads of State Colleges and Universities</li>
                                    </ul>
                                </li>
                                <li>Officers of the Armed Forces of the Philippines from the rank of Colonel or Naval Captain:
                                    <ul>
                                        <li>Colonel/ Captain</li>
                                        <li>Brigadier General/ Commodore</li>
                                        <li>Major General/ Rear Admiral</li>
                                        <li>Lieutenant General/ Vice Admiral</li>
                                        <li>General/ Admiral</li>
                                    </ul>
                                </li>
                                <li>Officers of the Philippine National Police from the rank of Police Colonel (formerly Senior Superintendent):
                                    <ul>
                                        <li>Police Brigadier General (Chief Superintendent)</li>
                                        <li>Police Major General (Director)</li>
                                        <li>Police Lieutenant General (Deputy Director General)</li>
                                        <li>Police General (Director General)</li>
                                    </ul>
                                </li>
                                <li>Officers of the Bureau Jail Management and Penology, and Bureau of Fire Protection from the rank of Chief Superintendent:
                                    <ul>
                                        <li>Chief Superintendent</li>
                                        <li>Director</li>
                                    </ul>
                                </li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-left" style="border: 1px solid black; padding: 8px; vertical-align: top; line-height: 1.5;">
                            <strong>Deputy Ombudsman in their respective region (Luzon, Visayas or Mindanao)</strong>
                        </td>
                        <td class="text-left" style="border: 1px solid black; padding: 8px; vertical-align: top; line-height: 1.5;">
                            <ul>
                                <li>Regional officials and employees of the following offices:
                                    <ul>
                                        <li>Departments, bureaus and agencies of the National Government</li>
                                        <li>Judiciary and Constitutional Commissions and offices</li>
                                        <li>Government Owned and/or Controlled Corporations with and without original charter, and their subsidiaries in the regions</li>
                                        <li>State Colleges and Universities</li>
                                    </ul>
                                </li>
                                <li>Provincial elective officials and appointed officials/employees, such as:
                                    <ul>
                                        <li>Governors</li>
                                        <li>Vice-Governors</li>
                                        <li>Sangguniang Panlalawigan Members</li>
                                    </ul>
                                </li>
                                <li>City and Municipal elective officials and appointed officials/employees, such as:
                                    <ul>
                                        <li>Mayors</li>
                                        <li>Vice-Mayors</li>
                                        <li>Sangguniang Bayan/Panlungsod Members</li>
                                        <li>Barangay Officials</li>
                                    </ul>
                                </li>
                                <li>Officers of the Armed Forces of the Philippines (AFP) below the rank of colonel or naval captain:
                                    <ul>
                                        <li>Lieutenant Colonel/Lieutenant Commander</li>
                                        <li>Major/ Commander</li>
                                        <li>Captain/ Lieutenant Senior Grade</li>
                                        <li>1st Lieutenant/ Lieutenant Junior Grade</li>
                                        <li>2nd Lieutenant/ Ensign</li>
                                        <li>Other enlisted officers</li>
                                    </ul>
                                </li>
                                <li>Officers of the Philippine National Police (PNP) below the rank of Police Colonel (Senior Superintendent):
                                    <ul>
                                        <li>Police Lieutenant Colonel (Superintendent)</li>
                                        <li>Police Major (Chief Inspector)</li>
                                        <li>Police Captain (Senior Inspector)</li>
                                        <li>Police Lieutenant (Inspector)</li>
                                        <li>Other police officers (SPO4 to PO1)</li>
                                    </ul>
                                </li>
                                <li>Officers of the Bureau Jail Management and Penology, and Bureau of Fire Protection below the rank of Chief Superintendent:
                                    <ul>
                                        <li>Senior Superintendent</li>
                                        <li>Superintendent</li>
                                        <li>Chief Inspector</li>
                                        <li>Senior Inspector</li>
                                        <li>Inspector</li>
                                        <li>Other Jail/Fire officers (SJO4 to JO1/SFO4 to FO1)</li>
                                    </ul>
                                </li>
                                <li>Officers of the Philippine Coast Guard (PCG) below the rank of Commodore:
                                    <ul>
                                        <li>Captain</li>
                                        <li>Commander</li>
                                        <li>Lieutenant Commander</li>
                                        <li>Lieutenant</li>
                                        <li>Lieutenant Junior Grade</li>
                                        <li>Ensign</li>
                                    </ul>
                                </li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-left" style="border: 1px solid black; padding: 8px; vertical-align: top; line-height: 1.5;">
                            <strong>Civil Service Commission</strong>
                        </td>
                        <td class="text-left" style="border: 1px solid black; padding: 8px; vertical-align: top; line-height: 1.5;">
                            <ul>
                                <li>All other officials and employees in the central/main/national offices of the following:
                                    <ul>
                                        <li>Departments, bureaus and agencies of the National Government</li>
                                        <li>Judiciary and Constitutional Commissions and offices</li>
                                        <li>Government owned and/or controlled corporations with and without original charters, and their subsidiaries in the regions</li>
                                    </ul>
                                </li>
                                <li>All other appointive officials and employees of the Legislature</li>
                                <li>All civilian personnel of the AFP</li>
                                <li>All other central officers (uniformed personnel) below the rank of Police Colonel (Senior Superintendent) as well as all non-uniformed personnel of the PNP.</li>
                                <li>All other central officers below the rank of Commodore as well as all civilian personnel of the PCG</li>
                                <li>All other central officers below the rank of Colonel or Naval Captain as well as all civilian personnel of the AFP</li>
                            </ul>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        {{-- Annex D --}}
        <!-- Mark Annex E start page (end of Annex D numbering range) - at end of Annex D -->
        <script type="text/php">
            if (isset($pdf) && !isset($GLOBALS['annexEStartPage'])) {
                // Mark the next page as Annex E start (accounting for page-break-before on Annex E)
                // This ensures all Annex D pages are numbered, including the last one
                $GLOBALS['annexEStartPage'] = $pdf->get_page_number() + 1;
            }
        </script>

        {{-- Next Page  --}}


        {{-- Annex E --}}
        <div style="page-break-before: always;">
            <div style="border: 1px solid black; padding: 18px 18px 22px 18px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="text-left font-16 bold" style="width: 33%; vertical-align: top; border: none;">
                            ANNEX E
                        </td>
                        <td class="text-center" style="width: 34%; border: none;"></td>
                        <td class="text-right font-10" style="width: 33%; vertical-align: top; border: none;">
                            CSC SALN Request Form<br>
                            Revised 2025
                        </td>
                    </tr>
                </table>

                <table style="width: 100%; border-collapse: collapse; margin-top: 6px;">
                    <tr>
                        <td class="text-center" style="border: none;">
                            <div class="font-12">Republic of the Philippines</div>
                            <div class="font-16 bold">CIVIL SERVICE COMMISSION</div>
                            <div class="font-10">IBP Road, Constitution Hills, Quezon City</div>
                        </td>
                    </tr>
                </table>

                <div style="border-top: 1px solid black; margin: 10px 0 18px 0;"></div>

                <table style="width: 100%; border-collapse: collapse; margin-bottom: 14px;">
                    <tr>
                        <td class="text-center bold font-12" style="border: none; line-height: 1.5;">
                            REQUEST FOR COPY OF STATEMENT OF ASSETS, LIABILITIES,<br>
                            AND NET WORTH (SALN)
                        </td>
                    </tr>
                </table>

                <table style="width: 100%; border-collapse: collapse; border: 1px solid black;">
                    <tr>
                        <td style="border: 1px solid black; padding: 6px; width: 22%;" class="font-12">
                            <strong>1. Name :</strong>
                        </td>
                        <td style="border: 1px solid black; padding: 6px;" class="font-12">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="border: none; border-bottom: 1px solid black; width: 33%; font-size: 10px; text-align: center;">{{ $info[0]->last_name ?? 'N/A' }}</td>
                                    <td style="border: none; border-bottom: 1px solid black; width: 33%; font-size: 10px; text-align: center;">{{ $info[0]->first_name ?? 'N/A' }}</td>
                                    <td style="border: none; border-bottom: 1px solid black; width: 34%; font-size: 10px; text-align: center;">{{ $info[0]->middle_name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="border: none;" class="font-10 text-center"><em>Last Name</em></td>
                                    <td style="border: none;" class="font-10 text-center"><em>First Name</em></td>
                                    <td style="border: none;" class="font-10 text-center"><em>Middle Name</em></td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="border: 1px solid black; padding: 6px;" class="font-12">
                            <strong>2. Address:</strong>
                        </td>
                        <td style="border: 1px solid black; padding: 6px;" class="font-12">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="border: none; border-bottom: 1px solid black; width: 33%; font-size: 10px; text-align: center;">{{ $info[0]->ra_house_no ?? 'N/A' }}</td>
                                    <td style="border: none; border-bottom: 1px solid black; width: 33%; font-size: 10px; text-align: center;">{{ $info[0]->ra_street ?? 'N/A' }}</td>
                                    <td style="border: none; border-bottom: 1px solid black; width: 34%; font-size: 10px; text-align: center;">{{ $address['ra_brgy'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="border: none;" class="font-10 text-center"><em>House No./Block No.</em></td>
                                    <td style="border: none;" class="font-10 text-center"><em>Street</em></td>
                                    <td style="border: none;" class="font-10 text-center"><em>Barangay</em></td>
                                </tr>
                                <tr>
                                    <td style="border: none; border-bottom: 1px solid black; font-size: 10px; text-align: center;">{{ $address['ra_city'] ?? 'N/A' }}</td>
                                    <td style="border: none; border-bottom: 1px solid black; font-size: 10px; text-align: center;">{{ $address['ra_province'] ?? 'N/A' }}</td>
                                    <td style="border: none; border-bottom: 1px solid black; font-size: 10px; text-align: center;">{{ $info[0]->ra_postal_id ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="border: none;" class="font-10 text-center"><em>City/Municipality</em></td>
                                    <td style="border: none;" class="font-10 text-center"><em>Province</em></td>
                                    <td style="border: none;" class="font-10 text-center"><em>Postal Code</em></td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="border: 1px solid black; padding: 6px;" class="font-12"><strong>3. Position Title:</strong></td>
                        <td style="border: 1px solid black; padding: 6px;" class="font-12"><div style="border-bottom: 1px solid black; font-size: 10px;">{{ $info[0]->position ?? 'N/A' }}</div></td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid black; padding: 6px;" class="font-12"><strong>4. Name of Agency/School:</strong></td>
                        <td style="border: 1px solid black; padding: 6px;" class="font-12"><div style="border-bottom: 1px solid black; font-size: 10px;">{{ $info[0]->company ?? 'N/A' }}</div></td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid black; padding: 6px;" class="font-12"><strong>5. Address</strong></td>
                        <td style="border: 1px solid black; padding: 6px;" class="font-12">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="border: none; border-bottom: 1px solid black; width: 33%; font-size: 10px; text-align: center;">{{ $info[0]->pa_house_no ?? 'N/A' }}</td>
                                    <td style="border: none; border-bottom: 1px solid black; width: 33%; font-size: 10px; text-align: center;">{{ $address['pa_brgy'] ?? 'N/A' }}</td>
                                    <td style="border: none; border-bottom: 1px solid black; width: 34%; font-size: 10px; text-align: center;">{{ $address['pa_city'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="border: none;" class="font-10 text-center"><em>No. and Street</em></td>
                                    <td style="border: none;" class="font-10 text-center"><em>Barangay</em></td>
                                    <td style="border: none;" class="font-10 text-center"><em>City/Municipality</em></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid black; padding: 6px;" class="font-12"><strong>6. Contact Number:</strong></td>
                        <td style="border: 1px solid black; padding: 6px;" class="font-12"><div style="border-bottom: 1px solid black; font-size: 10px;">{{ $info[0]->mobile_no ?? ($info[0]->telephone_no ?? 'N/A') }}</div></td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid black; padding: 6px;" class="font-12"><strong>7. Electronic Mail Address:</strong></td>
                        <td style="border: 1px solid black; padding: 6px;" class="font-12"><div style="border-bottom: 1px solid black; font-size: 10px;">{{ $info[0]->email ?? 'N/A' }}</div></td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid black; padding: 6px;" class="font-12">
                            <strong>8. Are you the declarant or his/her authorized representative?</strong>
                        </td>
                        <td style="border: 1px solid black; padding: 6px;" class="font-12">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="border: none; width: 2%; padding-top: 0; text-align: center;">
                                        <input type="checkbox" checked />
                                    </td>
                                    <td style="border: none; width: 26%; padding-top: 0; text-align: left; font-size: 12px;">Declarant</td>
                                    <td style="border: none; width: 2%; padding-top: 0; text-align: center;">
                                        <input type="checkbox" />
                                    </td>
                                    <td style="border: none; width: 37%; padding-top: 0; text-align: left; font-size: 12px;">Authorized representative</td>
                                    <td style="border: none; width: 2%; padding-top: 0; text-align: center;">
                                        <input type="checkbox" />
                                    </td>
                                    <td style="border: none; width: 31%; padding-top: 0; text-align: left; font-size: 12px;">Third party</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <div style="height: 12px;"></div>

                <div class="font-12 bold" style="margin: 0 0 6px 0;">
                    9. Indicate below the details of the SALN being requested:
                </div>
                <table style="width: 100%; border-collapse: collapse; border: 1px solid black;">
                    <tr>
                        <th class="font-12 text-center" style="border: 1px solid black; padding: 6px; width: 4%;">&nbsp;</th>
                        <th class="font-12 text-center" style="border: 1px solid black; padding: 6px; width: 36%;">Name of Declarant</th>
                        <th class="font-12 text-center" style="border: 1px solid black; padding: 6px; width: 48%;">Office/Agency of Declarant</th>
                        <th class="font-12 text-center" style="border: 1px solid black; padding: 6px; width: 16%;">Year of SALN</th>
                    </tr>
                    <tr>
                        <td style="border: 1px solid black; padding: 6px;" class="font-12 text-center">1.</td>
                        <td style="border: 1px solid black; padding: 6px; font-size: 10px; text-align: center;">{{ $info[0]->name ?? 'N/A' }}</td>
                        <td style="border: 1px solid black; padding: 6px; font-size: 10px; text-align: center;">{{ $info[0]->company ?? 'N/A' }}</td>
                        <td style="border: 1px solid black; padding: 6px; font-size: 10px; text-align: center;">
                            {{ ($complianceType === 'annual' && $complianceDate)
                                ? \Carbon\Carbon::parse($complianceDate)->format('Y')
                                : 'N/A' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid black; padding: 6px;" class="font-12 text-center">2.</td>
                        <td style="border: 1px solid black; padding: 6px;">&nbsp;</td>
                        <td style="border: 1px solid black; padding: 6px;">&nbsp;</td>
                        <td style="border: 1px solid black; padding: 6px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid black; padding: 6px;" class="font-12 text-center">3.</td>
                        <td style="border: 1px solid black; padding: 6px;">&nbsp;</td>
                        <td style="border: 1px solid black; padding: 6px;">&nbsp;</td>
                        <td style="border: 1px solid black; padding: 6px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid black; padding: 6px;" class="font-12 text-center">4.</td>
                        <td style="border: 1px solid black; padding: 6px;">&nbsp;</td>
                        <td style="border: 1px solid black; padding: 6px;">&nbsp;</td>
                        <td style="border: 1px solid black; padding: 6px;">&nbsp;</td>
                    </tr>
                </table>

                <div style="height: 14px;"></div>

                <div class="font-12 bold" style="margin: 0 0 6px 0;">
                    10. Indicate the purpose/s of the request:
                </div>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="font-12" style="border: none; width: 5%;">1.</td>
                        <td style="border: none; border-bottom: 1px solid black;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td class="font-12" style="border: none;">2.</td>
                        <td style="border: none; border-bottom: 1px solid black;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td class="font-12" style="border: none;">3.</td>
                        <td style="border: none; border-bottom: 1px solid black;">&nbsp;</td>
                    </tr>
                </table>

                <div style="height: 14px;"></div>

                <div class="font-12 bold" style="margin: 0 0 6px 0;">
                    11. Valid identification cards presented:
                </div>
                <div class="font-12 text-center" style="margin-bottom: 6px;">
                    <span class="bold">11.1.</span> If requesting on behalf of an agency/organization or school, agency/school ID is required to be presented.
                </div>
                <table style="width: 100%; border-collapse: collapse; border: 1px solid black;">
                    <tr>
                        <th class="font-12 text-center" style="border: 1px solid black; padding: 4px; width: 33%;">Government-Issued ID</th>
                        <th class="font-12 text-center" style="border: 1px solid black; padding: 4px; width: 33%;">Government-Issued ID</th>
                        <th class="font-12 text-center" style="border: 1px solid black; padding: 4px; width: 34%;">Agency/School ID (if applicable)</th>
                    </tr>
                    <tr>
                        <td style="border: 1px solid black; padding: 4px;" class="font-12">Issuing Agency</td>
                        <td style="border: 1px solid black; padding: 4px;" class="font-12">Issuing Agency</td>
                        <td style="border: 1px solid black; padding: 4px;" class="font-12">Issuing Agency</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid black; padding: 4px;" class="font-12">ID Number</td>
                        <td style="border: 1px solid black; padding: 4px;" class="font-12">ID Number</td>
                        <td style="border: 1px solid black; padding: 4px;" class="font-12">ID Number</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid black; padding: 4px;" class="font-12">Date Issued</td>
                        <td style="border: 1px solid black; padding: 4px;" class="font-12">Date Issued</td>
                        <td style="border: 1px solid black; padding: 4px;" class="font-12">Date Issued</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid black; padding: 4px;" class="font-12">Valid Until</td>
                        <td style="border: 1px solid black; padding: 4px;" class="font-12">Valid Until</td>
                        <td style="border: 1px solid black; padding: 4px;" class="font-12">Valid Until</td>
                    </tr>
                </table>
            </div>
        </div>
        {{-- Annex E --}}


        {{-- Next Page  --}}


        {{-- Annex E Continuation --}}
        <div style="page-break-before: always;">
        <div style="margin-bottom: 16px; padding: 18px 18px 22px 18px; border: 1px solid black;">
            <div style="margin-bottom: 16px;">
                <div class="font-12 bold" style="margin: 0 0 8px 0;">
                    12. Additional requirements:
                </div>
                <div style="margin-left: 20px; margin-bottom: 12px;">
                    <div class="font-12 bold" style="margin-bottom: 6px;">
                        12.1. If request is for research purposes:
                    </div>
                    <table style="width: 100%; margin-bottom: 8px;">
                        <tr>
                            <td style="width: 20px; vertical-align: middle;">
                                <input type="checkbox" style="vertical-align: middle;" />
                            </td>
                            <td style="vertical-align: middle;">
                                <span class="font-12">Endorsement letter from the school dean or any official of the organization concerned.</span>
                            </td>
                        </tr>
                    </table>
                    <table style="width: 100%; margin-left: 20px;">
                        <tr>
                            <td class="font-12" style="width: 150px; padding-bottom: 4px;">Name of signatory:</td>
                            <td style="border-bottom: 1px solid black;">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="font-12" style="padding-bottom: 4px;">Contact number:</td>
                            <td style="border-bottom: 1px solid black;">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="font-12" style="padding-bottom: 4px;">Email address:</td>
                            <td style="border-bottom: 1px solid black;">&nbsp;</td>
                        </tr>
                    </table>
                </div>
                <div style="margin-left: 20px;">
                    <div class="font-12 bold" style="margin-bottom: 6px;">
                        12.2. If request is made by a representative:
                    </div>
                    <table style="width: 100%;">
                        <tr>
                            <td style="width: 20px; vertical-align: middle;">
                                <input type="checkbox" style="vertical-align: middle;" />
                            </td>
                            <td class="font-12" style="padding-bottom: 4px; vertical-align: middle;">Authorization letter from declarant</td>
                        </tr>
                        <tr>
                            <td style="width: 20px; vertical-align: middle;">
                                <input type="checkbox" style="vertical-align: middle;" />
                            </td>
                            <td class="font-12" style="vertical-align: middle;">Copy of valid ID of declarant</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div style="border-top: 2px solid black;  padding: 12px 0; margin: 10px 0;">
                <div class="font-12 bold" style="margin-bottom: 12px;">
                    TO BE ACCOMPLISHED BY PROCESSOR:
                </div>
                    <table style="width: 100%; margin-bottom: 12px; border: 1px solid black; border-collapse: collapse;">
                        <tr>
                            <td style="border: 1px solid black; padding: 6px; width: 50%; vertical-align: middle;">
                                <input type="checkbox" style="vertical-align: middle;" /> <span class="font-12" style="vertical-align: middle;">SALN/s available</span>
                            </td>
                            <td style="border: 1px solid black; padding: 6px; width: 50%; vertical-align: middle;">
                                <input type="checkbox" style="vertical-align: middle;" /> <span class="font-12" style="vertical-align: middle;">SALN/s not available</span>
                            </td>
                        </tr>
                    </table>
                    <div class="font-12 bold" style="margin-bottom: 8px;">
                        Recommendation:
                    </div>
                    <table style="width: 100%; margin-bottom: 12px; border: 1px solid black; border-collapse: collapse;">
                        <tr>
                            <td style="border: 1px solid black; padding: 6px; width: 50%; vertical-align: top;">
                                <div style="margin-bottom: 6px;">
                                    <input type="checkbox" style="vertical-align: middle;" /> <span class="font-12" style="vertical-align: middle;">Approval</span>
                                </div>
                                <div>
                                    <span class="font-12">Processed by:</span>
                                    <div style="border-bottom: 1px solid black; margin-top: 4px;">&nbsp;</div>
                                </div>
                            </td>
                            <td style="border: 1px solid black; padding: 6px; width: 50%; vertical-align: top;">
                                <div style="margin-bottom: 6px;">
                                    <input type="checkbox" style="vertical-align: middle;" /> <span class="font-12" style="vertical-align: middle;">Disapproval</span>
                                </div>
                                <div>
                                    <span class="font-12">REASON/S:</span>
                                    <div style="border-bottom: 1px solid black; margin-top: 4px;">&nbsp;</div>
                                </div>
                            </td>
                        </tr>
                    </table>
            </div>

                <div style="border-top: 2px solid black; border-bottom: 2px solid black; padding: 8px 0; margin: 20px 0;">
                    <div class="font-12 bold" style="margin-bottom: 12px;">
                        Action taken:
                    </div>
                    <table style="width: 100%; margin-bottom: 12px; border: 1px solid black; border-collapse: collapse;">
                        <tr>
                            <td style="border: 1px solid black; padding: 6px; width: 50%; vertical-align: middle;">
                                <input type="checkbox" style="vertical-align: middle;" /> <span class="font-12" style="vertical-align: middle;">Approved</span>
                            </td>
                            <td style="border: 1px solid black; padding: 6px; width: 50%; vertical-align: middle;">
                                <input type="checkbox" style="vertical-align: middle;" /> <span class="font-12" style="vertical-align: middle;">Disapproved</span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="border: 1px solid black; padding: 6px; text-align: center;">
                                <div style="border-bottom: 1px solid black; margin-bottom: 4px; width: 50%; margin-left: auto; margin-right: auto;">&nbsp;</div>
                                <div class="font-12 bold" style="text-align: center;">Name, Position and Signature:</div>
                            </td>
                        </tr>
                    </table>
                </div>

                <table style="width: 100%; border-collapse: collapse; border: 1px solid black; margin: 20px 0;">
                    <tr>
                        <td class="font-12 bold" style="border: 1px solid black; padding: 6px; width: 30%;">Number of SALN/s</td>
                        <td style="border: 1px solid black; padding: 6px; border-bottom: 1px solid black;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td class="font-12 bold" style="border: 1px solid black; padding: 6px;">Amount Paid</td>
                        <td style="border: 1px solid black; padding: 6px; border-bottom: 1px solid black;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td class="font-12 bold" style="border: 1px solid black; padding: 6px;">OR Number</td>
                        <td style="border: 1px solid black; padding: 6px; border-bottom: 1px solid black;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td class="font-12 bold" style="border: 1px solid black; padding: 6px;">Date</td>
                        <td style="border: 1px solid black; padding: 6px; border-bottom: 1px solid black;">&nbsp;</td>
                    </tr>
                </table>

                <table style="width: 100%; margin-top: 20px; border-collapse: collapse;">
                    <tr>
                        <td class="font-12" style="width: 15%; padding: 6px; vertical-align: middle;">Released by:</td>
                        <td style="width: 85%;"></td>
                    </tr>
                    <tr>
                        <td class="font-12" style="width: 15%; padding: 6px; vertical-align: middle;">Received by:</td>
                        <td style="width: 85%;"></td>
                    </tr>
                </table>
            </div>
        </div>
        {{-- Annex E Continuation --}}


        {{-- Next Page  --}}


        {{-- Annex E Undertaking --}}
        <div style="page-break-before: always;">
            <div style="border: 1px solid black; padding: 18px 18px 22px 18px; margin-bottom: 20px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="text-center" style="width: 50%; border: none;"></td>
                        <td class="text-right font-10" style="width: 50%; vertical-align: top; border: none;">
                            CSC SALN Request Form<br>
                            Revised 2025
                        </td>
                    </tr>
                </table>

                <table style="width: 100%; border-collapse: collapse; margin-top: 6px;">
                    <tr>
                        <td class="text-center" style="border: none;">
                            <div class="font-12">Republic of the Philippines</div>
                            <div class="font-16 bold">CIVIL SERVICE COMMISSION</div>
                            <div class="font-10">IBP Road, Constitution Hills, Quezon City</div>
                        </td>
                    </tr>
                </table>

                <div style="border-top: 1px solid black; margin: 10px 0 18px 0;"></div>

                <div style="margin-bottom: 20px; padding: 18px 18px 22px 18px;">
                    <div class="font-16 text-center bold" style="margin-bottom: 30px;">
                        UNDERTAKING OF REQUESTING PARTY
                    </div>
                    <div class="font-12" style="margin-bottom: 20px; line-height: 1.8; text-indent: 40px;">
                        I, <span style="border-bottom: 1px solid black; display: inline; font-size: 10px;">{{ $info[0]->name ?? '&nbsp;' }}</span>,
                        with address at <span style="border-bottom: 1px solid black; display: inline; font-size: 10px;">@php
                            $addressParts = array_filter([
                                $info[0]->ra_house_no ?? null,
                                $info[0]->ra_street ?? null,
                                $address['ra_brgy'] ?? null,
                                $address['ra_city'] ?? null,
                                $address['ra_province'] ?? null
                            ]);
                            echo !empty($addressParts) ? implode(', ', $addressParts) : '&nbsp;';
                        @endphp</span>,
                        swear that the copies of the Statement of Assets, Liabilities and Net Worth (SALN) with the following details:
                    </div>
                    <table style="width: 100%; border-collapse: collapse; border: 1px solid black; margin-bottom: 20px;">
                        <tr>
                            <th class="font-12 text-center" style="border: 1px solid black; padding: 6px; width: 4%;">&nbsp;</th>
                            <th class="font-12 text-center" style="border: 1px solid black; padding: 6px; width: 36%;">Name of Declarant</th>
                            <th class="font-12 text-center" style="border: 1px solid black; padding: 6px; width: 48%;">Office/Agency of Declarant</th>
                            <th class="font-12 text-center" style="border: 1px solid black; padding: 6px; width: 16%;">Year of SALN</th>
                        </tr>
                        <tr>
                            <td class="font-12 text-center" style="border: 1px solid black; padding: 6px;">1.</td>
                            <td style="border: 1px solid black; padding: 6px; border-bottom: 1px solid black; font-size: 10px; text-align: center;">{{ $info[0]->name ?? 'N/A' }}</td>
                            <td style="border: 1px solid black; padding: 6px; border-bottom: 1px solid black; font-size: 10px; text-align: center;">{{ $info[0]->company ?? 'N/A' }}</td>
                            <td style="border: 1px solid black; padding: 6px; border-bottom: 1px solid black; font-size: 10px; text-align: center;">{{ ($complianceType === 'annual' && $complianceDate)
                                ? \Carbon\Carbon::parse($complianceDate)->format('Y')
                                : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="font-12 text-center" style="border: 1px solid black; padding: 6px;">2.</td>
                            <td style="border: 1px solid black; padding: 6px; border-bottom: 1px solid black;">&nbsp;</td>
                            <td style="border: 1px solid black; padding: 6px; border-bottom: 1px solid black;">&nbsp;</td>
                            <td style="border: 1px solid black; padding: 6px; border-bottom: 1px solid black;">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="font-12 text-center" style="border: 1px solid black; padding: 6px;">3.</td>
                            <td style="border: 1px solid black; padding: 6px; border-bottom: 1px solid black;">&nbsp;</td>
                            <td style="border: 1px solid black; padding: 6px; border-bottom: 1px solid black;">&nbsp;</td>
                            <td style="border: 1px solid black; padding: 6px; border-bottom: 1px solid black;">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="font-12 text-center" style="border: 1px solid black; padding: 6px;">4.</td>
                            <td style="border: 1px solid black; padding: 6px; border-bottom: 1px solid black;">&nbsp;</td>
                            <td style="border: 1px solid black; padding: 6px; border-bottom: 1px solid black;">&nbsp;</td>
                            <td style="border: 1px solid black; padding: 6px; border-bottom: 1px solid black;">&nbsp;</td>
                        </tr>
                    </table>
                    <div class="font-12" style="margin-bottom: 12px;">
                        shall be used solely for the following purpose/s:
                    </div>
                    <table style="width: 100%; margin-bottom: 30px;">
                        <tr>
                            <td class="font-12" style="width: 20px; vertical-align: middle;">1.</td>
                            <td style="border-bottom: 1px solid black; padding-bottom: 4px; vertical-align: middle;">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="font-12" style="width: 20px; vertical-align: middle;">2.</td>
                            <td style="border-bottom: 1px solid black; padding-bottom: 4px; vertical-align: middle;">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="font-12" style="width: 20px; vertical-align: middle;">3.</td>
                            <td style="border-bottom: 1px solid black; padding-bottom: 4px; vertical-align: middle;">&nbsp;</td>
                        </tr>
                    </table>
                    <div class="font-12" style="margin-bottom: 12px;">
                        I agree to comply with the following undertakings:
                    </div>
                    <div class="font-12" style="margin-bottom: 20px; line-height: 1.8; text-align: justify;">
                        <div style="margin-bottom: 6px;">
                            <strong>1.</strong> I shall not use nor disclose the abovementioned SALN/s for purposes contrary to morals, public policy or commercial purpose/s;
                        </div>
                        <div style="margin-bottom: 6px;">
                            <strong>2.</strong> I shall not use nor disclose the abovementioned SALN/s for extortion purposes or for any purpose that will endanger the personal safety of the official or employee, and
                        </div>
                        <div style="margin-bottom: 6px;">
                            <strong>3.</strong> I shall not disclose the name of declarant and its contents, nor lend, show or reproduce a photocopy of the same for distribution to other individuals/groups/organizations;
                        </div>
                        <div style="margin-bottom: 6px;">
                            <strong>4.</strong> If I violate the terms and conditions of this undertaking, I understand that an action may be filed against me and that as a consequence, the Court may assess a penalty in an amount not to exceed Twenty Five Thousand Pesos (Php 25,000.00) pursuant to Section 11(d) of Republic Act No. 6713 (Code of Conduct and Ethical Standards for Public Officials and Employees). In addition, the Civil Service Commission may separately file a legal action under applicable laws.
                        </div>
                    </div>
                    <table style="width: 100%; margin-top: 40px;">
                        <tr>
                            <td style="text-align: center; vertical-align: middle;">
                                <table style="margin: 0 auto;">
                                    <tr>
                                        <td style="text-align: center; vertical-align: middle; padding-right: 40px;">
                                            <div style="border-bottom: 1px solid black; width: 200px; margin: 0 auto 4px auto; padding-top: 20px;">&nbsp;</div>
                                            <div class="font-12" style="text-align: center;">Requesting Party</div>
                                        </td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            <div style="border-bottom: 1px solid black; width: 150px; margin: 0 auto 4px auto; padding-top: 20px;">&nbsp;</div>
                                            <div class="font-12" style="text-align: center;">Date</div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        {{-- Annex E Undertaking --}}


    </div>

    <!-- Annex A/B/C page number footer (each annex has its own page count + page 1 signature line) -->
    <script type="text/php">
        if (isset($pdf)) {
            $annexAStart = isset($GLOBALS['annexAStartPage']) ? (int) $GLOBALS['annexAStartPage'] : 1;
            $annexBStart = isset($GLOBALS['annexBStartPage']) ? (int) $GLOBALS['annexBStartPage'] : 999999;
            $annexCStart = isset($GLOBALS['annexCStartPage']) ? (int) $GLOBALS['annexCStartPage'] : 999999;
            $annexDStart = isset($GLOBALS['annexDStartPage']) ? (int) $GLOBALS['annexDStartPage'] : 999999;

            $pdf->page_script(function ($pageNumber, $pageCount, $pdf, $fontMetrics) use ($annexAStart, $annexBStart, $annexCStart, $annexDStart) {
                // Determine which annex this page belongs to (A, B, or C only)
                $start = null;
                $end = null;

                if ($pageNumber >= $annexAStart && $pageNumber < $annexBStart) {
                    $start = $annexAStart;
                    $end = $annexBStart;
                } elseif ($pageNumber >= $annexBStart && $pageNumber < $annexCStart) {
                    $start = $annexBStart;
                    $end = $annexCStart;
                } elseif ($pageNumber >= $annexCStart && $pageNumber < $annexDStart) {
                    $start = $annexCStart;
                    $end = $annexDStart;
                } else {
                    return;
                }

                $relativePage = ($pageNumber - $start) + 1;
                $totalPages = max(1, $end - $start);
                $prefix = "Page {$relativePage} of ";
                $total = (string) $totalPages;
                $size = 10;
                $font = $fontMetrics->getFont("times", "italic") ?: $fontMetrics->getFont("times-italic");

                $prefixWidth = $fontMetrics->get_text_width($prefix, $font, $size);
                $totalWidth = $fontMetrics->get_text_width($total, $font, $size);
                $fullWidth = $prefixWidth + $totalWidth;
                $x = ($pdf->get_width() - $fullWidth) / 2;
                $y = $pdf->get_height() - 30;

                // Draw directly (do NOT use page_text here or it will register globally again)
                $pdf->text($x, $y, $prefix, $font, $size);
                $pdf->text($x + $prefixWidth, $y, $total, $font, $size);

                // On page 1 of each annex (A, B, C), draw the right-side signature/initial line + label
                if ($relativePage === 1) {
                    $lineWidth = 150;
                    $rightMargin = 40;
                    $x2 = $pdf->get_width() - $rightMargin;
                    $x1 = $x2 - $lineWidth;
                    $lineY = $y+9; // align roughly with the page number vertically

                    // Draw the signature line
                    $pdf->line($x1, $lineY, $x2, $lineY, [0, 0, 0], 0.6);

                    // Draw the label centered under the line
                    $label = "Signature/Initial of Declarant";
                    $labelSize = 8;
                    $labelWidth = $fontMetrics->get_text_width($label, $font, $labelSize);
                    $labelX = $x1 + ($lineWidth - $labelWidth) / 2;
                    // Reduce gap between the line and the label (closer like the form sample)
                    $labelY = $lineY + 2;
                    $pdf->text($labelX, $labelY, $label, $font, $labelSize);
                }
            });

            // Annex D page numbering (right-aligned, format: "Page | X")
            $annexDStart = isset($GLOBALS['annexDStartPage']) ? (int) $GLOBALS['annexDStartPage'] : 999999;
            $annexEStart = isset($GLOBALS['annexEStartPage']) ? (int) $GLOBALS['annexEStartPage'] : 999999;

            $pdf->page_script(function ($pageNumber, $pageCount, $pdf, $fontMetrics) use ($annexDStart, $annexEStart) {
                if ($pageNumber < $annexDStart || $pageNumber >= $annexEStart) {
                    return;
                }

                $relativePage = ($pageNumber - $annexDStart) + 1;
                $text = "Page | {$relativePage}";
                $size = 10;
                $font = $fontMetrics->getFont("times", "italic") ?: $fontMetrics->getFont("times-italic");

                $textWidth = $fontMetrics->get_text_width($text, $font, $size);
                $rightMargin = 40;
                $x = $pdf->get_width() - $textWidth - $rightMargin;
                $y = $pdf->get_height() - 30;

                // Draw right-aligned page number for Annex D
                $pdf->text($x, $y, $text, $font, $size);
            });
        }
    </script>

</body>

</html>
