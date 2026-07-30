<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Position Description Form</title>
    <style>
        body {
            margin: 20px;
            font-family: Arial, Helvetica, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            border: 1px solid #000;
            vertical-align: middle;
        }
        p {
            margin: 0;
            padding: 0;
        }

        .gray-background {
            background-color:rgb(185, 184, 184);
        }
        .nested-table {
            width: 100%;
            border-collapse: collapse;
        }
        .nested-table td {
            border: 1px solid #000;
            vertical-align: middle;
        }
        .nested-table div {
            border: 1px solid #000;
        }
        .nested-table-contacts {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }
        .nested-table-contacts td {
            vertical-align: middle;
            border: none;
        }
        .page-break {
            page-break-before: always;
        }

        .pd-checkbox {
            border: 1px solid #000;
            display: inline-block;
            width: 8px;
            height: 8px;
        }

        .pd-checkbox.checked {
            background: #000;
        }

        .nested-table-job-summary {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        .nested-table-job-summary td {
            vertical-align: middle;
            border: 1px solid #000;
        }

        .nested-table-core {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        .nested-table-core td {
            vertical-align: middle;
            border: 1px solid #000;
        }
        .nested-table-leadership {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        .nested-table-leadership td {
            vertical-align: middle;
            border: 1px solid #000;
        }
        .nested-table-technical {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        .nested-table-technical td {
            vertical-align: middle;
            border: 1px solid #000;
        }

        .nested-table-acknowledgment {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }
        .nested-table-acknowledgment td {
            vertical-align: middle;
            border: none;
        }
    </style>
</head>
<body>
    @php
        $sc = $positionData['stakeholders_checks'] ?? \App\Support\PositionDescriptionChecks::defaultStakeholders();
        $wc = $positionData['working_condition_checks'] ?? \App\Support\PositionDescriptionChecks::defaultWorkingCondition();
    @endphp
    <div>
            <table>
                <tr>
                    <td colspan="2" style="padding: 0px; border: none;">
                        <table class="nested-table" style="border: none;">
                            <tr>
                                <td rowspan="2" style="width: 50%; text-align: center; border-bottom: none;">
                                    <span style="font-size: 10px; font-weight: bold;">Republic of the Philippines</span> <br>
                                    <span style="font-size: 10px; font-weight: bold;">POSITION DESCRIPTION FORM</span> <br>
                                    <span style="font-size: 10px; font-weight: bold;">DBM-CSC Form No. 1</span> <br>
                                    <span style="font-size: 8px; font-weight: bold; color: rgb(168, 166, 166);">(Revised version No. 1, 2017)</span>
                                </td>
                                <td class="gray-background" style="width: 50%; vertical-align: top; padding: 0px; border-bottom: none;">
                                    <span style="font-size: 10px; font-weight: bold;">1. POSITION TITLE (as approved by authorized agency) with parenthetical title</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align: center; padding: 14px 0px; border-bottom: none;" >
                                    <span style="font-size: 10px;">{{ $positionData['position_title'] ?? '' }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="padding: 0px; border: none;">
                        <table class="nested-table" style="border: none;">
                            <tr>
                                <td class="gray-background" style="width: 50%;">
                                    <span style="font-size: 10px; font-weight: bold;">2. ITEM NUMBER</span>
                                </td>
                                <td class="gray-background" style="width: 50%;">
                                    <span style="font-size: 10px; font-weight: bold;">3. SALARY GRADE</span>
                                </td>
                            </tr>

                            <tr>
                                <td style="text-align: center; padding: 14px 0px; border-bottom: none;" >
                                    <span style="font-size: 10px;">{{ $positionData['item_number'] ?? '' }}</span>
                                </td>
                                <td style="text-align: center; padding: 14px 0px; border-bottom: none;" >
                                    <span style="font-size: 10px;">{{ $positionData['salary_grade'] ?? '' }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="padding: 0px; border: none;">
                        <table class="nested-table" style="border: none;">
                            <tr>
                                <td colspan="3" class="gray-background" style="width: 50%;">
                                    <span style="font-size: 10px; font-weight: bold;">4. FOR LOCAL GOVERNMENT POSITION, ENUMERATE GOVERNMENTAL UNIT AND CLASS</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="width: 33.33%; vertical-align: middle; padding-left: 70px;">
                                    <span style="display: inline-block;">
                                        <div style="width:10px; height:10px; display: inline-block; vertical-align: middle;"></div>
                                        <span style="font-size: 10px; display: inline-block; vertical-align: middle;">Province</span>
                                    </span> <br>
                                    <span style="display: inline-block;">
                                        <div style="width:10px; height:10px; display: inline-block; vertical-align: middle;"></div>
                                        <span style="font-size: 10px; display: inline-block; vertical-align: middle;">City</span>
                                    </span> <br>
                                    <span style="display: inline-block;">
                                        <div style="width:10px; height:10px; display: inline-block; vertical-align: middle;"></div>
                                        <span style="font-size: 10px; display: inline-block; vertical-align: middle;">Municipality</span>
                                    </span>
                                </td>

                                <td style="width: 33.33%; vertical-align: middle; padding-left: 70px;">
                                    <span style="display: inline-block;">
                                        <div style="width:10px; height:10px; display: inline-block; vertical-align: middle;"></div>
                                        <span style="font-size: 10px; display: inline-block; vertical-align: middle;">1st Class</span>
                                    </span> <br>
                                    <span style="display: inline-block;">
                                        <div style="width:10px; height:10px; display: inline-block; vertical-align: middle;"></div>
                                        <span style="font-size: 10px; display: inline-block; vertical-align: middle;">2nd Class</span>
                                    </span> <br>
                                    <span style="display: inline-block;">
                                        <div style="width:10px; height:10px; display: inline-block; vertical-align: middle;"></div>
                                        <span style="font-size: 10px; display: inline-block; vertical-align: middle;">3rd Class</span>
                                    </span> <br>
                                    <span style="display: inline-block;">
                                        <div style="width:10px; height:10px; display: inline-block; vertical-align: middle;"></div>
                                        <span style="font-size: 10px; display: inline-block; vertical-align: middle;">4th Class</span>
                                    </span>
                                </td>

                                <td style="width: 33.33%; vertical-align: middle; padding-left: 70px; ">
                                    <span style="display: inline-block;">
                                        <div style="width:10px; height:10px; display: inline-block; vertical-align: middle;"></div>
                                        <span style="font-size: 10px; display: inline-block; vertical-align: middle;">5th Class</span>
                                    </span> <br>
                                    <span style="display: inline-block;">
                                        <div style="width:10px; height:10px; display: inline-block; vertical-align: middle;"></div>
                                        <span style="font-size: 10px; display: inline-block; vertical-align: middle;">6th Class</span>
                                    </span> <br>
                                    <span style="display: inline-block;">
                                        <div style="width:10px; height:10px; display: inline-block; vertical-align: middle;"></div>
                                        <span style="font-size: 10px; display: inline-block; vertical-align: middle;">Special</span>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="padding: 0; border:none;">
                        <table class="nested-table" style="border: none;">
                            <tr>
                                <td class="gray-background" style="width: 50%; border-top: none;">
                                    <span style="font-size: 10px; font-weight: bold;">5. DEPARTMENT, CORPORATION OR AGENCY/ LOCAL GOVERNMENT</span>
                                </td>
                                <td class="gray-background" style="width: 50%; border-top: none;">
                                    <span style="font-size: 10px; font-weight: bold;">6. BUREAU OR OFFICE</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align: center; padding: 14px 0px; border-bottom: none;">
                                    <span style="font-size: 10px;">{{ $positionData['department'] ?? 'DEPARTMENT OF TRADE AND INDUSTRY' }}</span>
                                </td>
                                <td style="text-align: center; padding: 14px 0px; border-bottom: none;">
                                    <span style="font-size: 10px;">{{ $positionData['office'] ?? strtoupper($orgCompanyName) }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="padding: 0; border:none;">
                        <table class="nested-table-division-location" style="border: none;">
                            <tr>
                                <td class="gray-background" style="width: 50%; border: 1px solid #000;">
                                    <span style="font-size: 10px; font-weight: bold;">7. DEPARTMENT / BRANCH / DIVISION</span>
                                </td>
                                <td class="gray-background" style="width: 50%; border: 1px solid #000;">
                                    <span style="font-size: 10px; font-weight: bold;">8. WORKSTATION / PLACE OF WORK</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align: center; padding: 14px 0px; border-bottom: none;">
                                    <span style="font-size: 10px;">{{ $positionData['division'] ?? 'Office of the Executive Director (OED)' }}</span>
                                </td>
                                <td style="text-align: center; padding: 14px 0px; border-bottom: none;">
                                    <span style="font-size: 10px;">{{ $positionData['location'] ?? strtoupper($orgCompanyAddress) }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>



                <tr>
                    <td colspan="2" style="padding: 0; border:none;">
                        <table class="nested-table-appropriation-salary" style="border: none;">
                            <tr>
                                <td class="gray-background" style="width: 25%; border: 1px solid #000;">
                                    <span style="font-size: 9px; font-weight: bold;">9. PRESENT APPROP ACT</span>
                                </td>
                                <td class="gray-background" style="width: 25%; border: 1px solid #000;">
                                    <span style="font-size: 9px; font-weight: bold;">10. PREVIOUS APPROP ACT</span>
                                </td>
                                <td class="gray-background" style="width: 25%; border: 1px solid #000;">
                                    <span style="font-size: 9px; font-weight: bold;">11. SALARY AUTHORIZED</span>
                                </td>
                                <td class="gray-background" style="width: 25%; border: 1px solid #000;">
                                    <span style="font-size: 9px; font-weight: bold;">12. OTHER COMPENSATION</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align: center; padding: 14px 0px; border: 1px solid #000; border-bottom: none;">
                                    <span style="font-size: 10px;">{{ $positionData['present_appropriation'] ?? '' }}</span>
                                </td>
                                <td style="text-align: center; padding: 14px 0px; border: 1px solid #000; border-bottom: none;">
                                    <span style="font-size: 10px;">{{ $positionData['previous_appropriation'] ?? '' }}</span>
                                </td>
                                <td style="text-align: center; padding: 14px 0px; border: 1px solid #000; border-bottom: none;">
                                    <span style="font-size: 10px;">{{ $positionData['authorized_salary'] ?? '' }}</span>
                                </td>
                                <td style="text-align: center; padding: 14px 0px; border: 1px solid #000; border-bottom: none;">
                                    <span style="font-size: 10px;">{{ $positionData['other_compensation'] ?? '' }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="padding: 0; border: none;">
                        <table class="nested-table-immediate-supervisor" style="border: none;">
                            <tr>
                                <td class="gray-background" style="width: 50%;">
                                    <span style="font-size: 10px; font-weight: bold;">13. POSITION TITLE OF IMMEDIATE SUPERVISOR</span>
                                </td>
                                <td class="gray-background" style="width: 50%;">
                                    <span style="font-size: 10px; font-weight: bold;">14. POSITION TITLE OF NEXT HIGHER SUPERVISOR</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align: center; padding: 14px 0px; border-bottom: none;">
                                    @php
                                        $immediateSupervisor = $positionData['immediate_supervisor_position_title'] ?? "SUPERVISING\nTRADE-INDUSTRY\nDEVELOPMENT SPECIALIST\n\nCHIEF TRADE-INDUSTRY\nDEVELOPMENT SPECIALIST";
                                        $immediateSupervisorLines = explode("\n", $immediateSupervisor);
                                    @endphp
                                    <span style="font-size: 10px;">
                                        @foreach($immediateSupervisorLines as $line)
                                            @if(!empty(trim($line)))
                                                {{ trim($line) }}@if(!$loop->last)<br>@endif
                                            @endif
                                        @endforeach
                                    </span>
                                </td>
                                <td style="text-align: center; padding: 14px 0px; border-bottom: none;">
                                    @php
                                        $nextHigherSupervisor = $positionData['next_higher_supervisor_position_title'] ?? "DEPUTY EXECUTIVE DIRECTOR\n\nEXECUTIVE DIRECTOR";
                                        $nextHigherSupervisorLines = explode("\n", $nextHigherSupervisor);
                                    @endphp
                                    <span style="font-size: 10px;">
                                        @foreach($nextHigherSupervisorLines as $line)
                                            @if(!empty(trim($line)))
                                                {{ trim($line) }}@if(!$loop->last)<br>@endif
                                            @endif
                                        @endforeach
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>



                <tr>
                    <td colspan="2" style="padding: 0; border: none;">
                        <table class="nested-table-supervisor" style="border: none;">
                            <tr>
                                <td colspan="6" class="gray-background" style="width: 50%; border: 1px solid #000;">
                                    <span style="font-size: 10px; font-weight: bold;">15. POSITION TITLE AND ITEM OF THOSE DIRECTLY SUPERVISED</span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6" style="text-align: center; padding:0px;">
                                    <p style="font-size: 10px; font-style: italic;">(if more than seven (7) list only by their item numbers and titles)</p>
                                </td>
                            </tr>

                            <tr>
                                <td colspan="3" style="text-align: center;">
                                    <p style="font-size: 10px; font-weight: bold;">POSITION TITLE</p>
                                </td>
                                <td colspan="3" style="text-align: center;">
                                    <p style="font-size: 10px; font-weight: bold;">ITEM NUMBER</p>
                                </td>
                            </tr>
                            @php
                                $supervisedPositions = $positionData['supervised_positions'] ?? [];
                                $rowCount = min(count($supervisedPositions), 7);
                            @endphp

                            @if ($rowCount === 0)
                                <tr>
                                    <td colspan="3" style="text-align: center; border-bottom: none; padding: 10px 0px;">
                                        <span style="font-size: 10px;">N/A</span>
                                    </td>
                                    <td colspan="3" style="text-align: center; border-bottom: none; padding: 10px 0px;">
                                        <span style="font-size: 10px;">N/A</span>
                                    </td>
                                </tr>
                            @else
                                @for ($i = 0; $i < $rowCount; $i++)
                                    @php
                                        $row = $supervisedPositions[$i] ?? ['title' => 'N/A', 'item_number' => 'N/A'];
                                    @endphp
                                    <tr>
                                        <td colspan="3" style="text-align: center; border-bottom: none; padding: 0px 0px;">
                                            <span style="font-size: 10px;">{{ $row['title'] ?? 'N/A' }}</span>
                                        </td>
                                        <td colspan="3" style="text-align: center; border-bottom: none; padding: 0px 0px;">
                                            <span style="font-size: 10px;">{{ $row['item_number'] ?? 'N/A' }}</span>
                                        </td>
                                    </tr>
                                @endfor
                            @endif
                        </table>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="padding: 0; border: none;">
                        <table class="nested-table-machines" style="border: none;">
                            <tr>
                                <td colspan="6" class="gray-background" style="width: 50%; border: 1px solid #000;">
                                    <span style="font-size: 10px; font-weight: bold;">16. MACHINE, EQUIPMENT, TOOLS, ETC., USED REGULARLY IN PERFORMANCE OF WORK</span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 14px 0px; border-bottom: none;">
                                    <span style="font-size: 10px;">{{ $positionData['machines_tools'] ?? 'Computer/Laptop, Printer, Telephone, External Hard Drive, Switches, UTP Cable, Television, Headset and Microphone' }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="padding: 0; border: none; border-top: none;">
                        <table class="nested-table-contacts" style="border-bottom: none;">
                            <tr>
                                <td colspan="6" class="gray-background" style="width: 50%; border: 1px solid #000;">
                                    <span style="font-size: 10px; font-weight: bold;">17. CONTACTS / CLIENTS / STAKEHOLDERS</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="gray-background" style="width: 14%; text-align: center; border: 1px solid #000; vertical-align: middle;">
                                    <span style="font-size: 10px; font-weight: bold;">17a. Internal</span>
                                </td>
                                <td class="gray-background" style="width: 14%; text-align: center; border: 1px solid #000;">
                                    <span style="font-size: 10px; font-weight: bold;">Occasional</span>
                                </td>
                                <td class="gray-background" style="width: 15%; text-align: center; border: 1px solid #000;">
                                    <span style="font-size: 10px; font-weight: bold;">Frequent</span>
                                </td>
                                <td class="gray-background" style="width: 14%; text-align: center; border: 1px solid #000; vertical-align: middle;">
                                    <span style="font-size: 10px; font-weight: bold;">17b. External</span>
                                </td>
                                <td class="gray-background" style="width: 14%; text-align: center; border: 1px solid #000;">
                                    <span style="font-size: 10px; font-weight: bold;">Occasional</span>
                                </td>
                                <td class="gray-background" style="width: 15%; text-align: center; border: 1px solid #000;">
                                    <span style="font-size: 10px; font-weight: bold;">Frequent</span>
                                </td>
                            </tr>

                            <!-- Executive / Managerial row -->
                            <tr>
                                <td style="padding: 4px; text-align: left;">
                                    <p style="font-size: 8px;">Executive / Managerial</p>
                                </td>
                                <td style="padding: 4px; text-align: center;"><span class="pd-checkbox {{ !empty($sc['ieo']) ? 'checked' : '' }}"></span></td>
                                <td style="padding: 4px; text-align: center;"><span class="pd-checkbox {{ !empty($sc['ief']) ? 'checked' : '' }}"></span></td>
                                <td style="padding: 4px; text-align: left;">
                                    <p style="font-size: 8px;">General Public</p>
                                </td>
                                <td style="padding: 4px; text-align: center;"><span class="pd-checkbox {{ !empty($sc['egpo']) ? 'checked' : '' }}"></span></td>
                                <td style="padding: 4px; text-align: center;"><span class="pd-checkbox {{ !empty($sc['egpf']) ? 'checked' : '' }}"></span></td>
                            </tr>

                            <!-- Supervisors row -->
                            <tr>
                                <td style="padding: 4px; text-align: left;">
                                    <p style="font-size: 8px;">Supervisors</p>
                                </td>
                                <td style="padding: 4px; text-align: center;"><span class="pd-checkbox {{ !empty($sc['iso']) ? 'checked' : '' }}"></span></td>
                                <td style="padding: 4px; text-align: center;"><span class="pd-checkbox {{ !empty($sc['isf']) ? 'checked' : '' }}"></span></td>
                                <td style="padding: 4px; text-align: left;">
                                    <p style="font-size: 8px;">Other Agencies</p>
                                </td>
                                <td style="padding: 4px; text-align: center;"><span class="pd-checkbox {{ !empty($sc['eoao']) ? 'checked' : '' }}"></span></td>
                                <td style="padding: 4px; text-align: center;"><span class="pd-checkbox {{ !empty($sc['eoaf']) ? 'checked' : '' }}"></span></td>
                            </tr>

                            <!-- Non-Supervisors row -->
                            <tr>
                                <td style="padding: 4px; text-align: left;">
                                    <p style="font-size: 8px;">Non-Supervisors</p>
                                </td>
                                <td style="padding: 4px; text-align: center;"><span class="pd-checkbox {{ !empty($sc['ino']) ? 'checked' : '' }}"></span></td>
                                <td style="padding: 4px; text-align: center;"><span class="pd-checkbox {{ !empty($sc['inf']) ? 'checked' : '' }}"></span></td>
                                <td style="padding: 4px; text-align: left;">
                                    <p style="font-size: 8px;">Others (Please Specify): {{ $sc['eots'] ?? '' }}</p>
                                </td>
                                <td style="padding: 4px; text-align: center;"><span class="pd-checkbox {{ !empty($sc['eoto']) ? 'checked' : '' }}"></span></td>
                                <td style="padding: 4px; text-align: center;"><span class="pd-checkbox {{ !empty($sc['eotf']) ? 'checked' : '' }}"></span></td>
                            </tr>

                            <!-- Staff row -->
                            <tr>
                                <td style="padding: 4px; text-align: left;">
                                    <p style="font-size: 8px;">Staff</p>
                                </td>
                                <td style="padding: 4px; text-align: center;"><span class="pd-checkbox {{ !empty($sc['isto']) ? 'checked' : '' }}"></span></td>
                                <td style="padding: 4px; text-align: center;"><span class="pd-checkbox {{ !empty($sc['istf']) ? 'checked' : '' }}"></span></td>
                                <td style="padding: 4px;"></td>
                                <td style="padding: 4px;"></td>
                                <td style="padding: 4px;"></td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="padding: 0; border: none;">
                        <table class="nested-table-contacts">
                            <tr>
                                <td colspan="6" class="gray-background" style="width: 50%; border: 1px solid #000;">
                                    <span style="font-size: 10px; font-weight: bold;">18. WORKING CONDITION</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 4px; text-align: left; width: 14%;">
                                    <p style="font-size: 8px;">Office Work</p>
                                </td>
                                <td style="padding: 4px; text-align: center; width: 14%;"><span class="pd-checkbox {{ !empty($wc['owo']) ? 'checked' : '' }}"></span></td>
                                <td style="padding: 4px; text-align: center; width: 15%;"><span class="pd-checkbox {{ !empty($wc['owf']) ? 'checked' : '' }}"></span></td>
                                <td style="padding: 4px; text-align: left; width: 14%;">
                                    <p style="font-size: 8px;">Other/s (Please Specify){{ !empty($wc['ots']) ? ': ' . $wc['ots'] : '' }}</p>
                                </td>
                                <td style="padding: 4px; text-align: center; width: 14%;"><span class="pd-checkbox {{ !empty($wc['oto']) ? 'checked' : '' }}"></span></td>
                                <td style="padding: 4px; text-align: center; width: 15%;"><span class="pd-checkbox {{ !empty($wc['otf']) ? 'checked' : '' }}"></span></td>
                            </tr>

                            <tr>
                                <td style="padding: 4px; text-align: left;">
                                    <p style="font-size: 8px;">Field Work</p>
                                </td>
                                <td style="padding: 4px; text-align: center;"><span class="pd-checkbox {{ !empty($wc['fwo']) ? 'checked' : '' }}"></span></td>
                                <td style="padding: 4px; text-align: center;"><span class="pd-checkbox {{ !empty($wc['fwf']) ? 'checked' : '' }}"></span></td>
                                <td style="padding: 4px;"></td>
                                <td style="padding: 4px;"></td>
                                <td style="padding: 4px;"></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

        </div>

        <div class="page-break">
            <table>
                <tr>
                    <td colspan="2" style="padding: 0; border: none;">
                        <table class="nested-table-qualification" style="width: 100%; border-collapse: collapse; ">
                            <tr>
                                <td colspan="2" class="gray-background" style="width: 50%; border-bottom: none;">
                                    <span style="font-size: 10px; font-weight: bold;">19. BRIEF DESCRIPTION OF THE GENERAL FUNCTION OF THE UNIT OR SECTION</span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding: 14px 0px; text-align: center; border-bottom: none;">
                                    <span style="font-size: 10px;">{{ $positionData['unit_function'] ?? '' }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>

                <tr>
                    <td colspan="2" style="padding: 0; border: none;">
                        <table class="nested-table-job-summary" style="width: 100%; border-collapse: collapse; ">
                            <tr>
                                <td colspan="2" class="gray-background" style="width: 50%; border-bottom: none;">
                                    <span style="font-size: 10px; font-weight: bold;">20. BRIEF DESCRIPTION OF THE GENERAL FUNCTION OF THE POSITION (Job Summary)</span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding: 14px 0px; text-align: center; border-bottom: none;">
                                    <span style="font-size: 10px;">{{ $positionData['job_summary'] ?? '' }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="padding: 0; border: none;">
                        <table class="nested-table-qualification" style="width: 100%; border-collapse: collapse; ">
                            <tr>
                                <td colspan="4" class="gray-background" style="width: 50%; border-bottom: none;">
                                    <span style="font-size: 10px; font-weight: bold;">21. QUALIFICATION STANDARDS</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="gray-background" style="width: 25%; text-align: center; padding: 4px;">
                                    <span style="font-size: 10px; font-weight: bold;">21a. Education</span>
                                </td>
                                <td class="gray-background" style="width: 25%; text-align: center; padding: 4px;">
                                    <span style="font-size: 10px; font-weight: bold;">21b. Experience</span>
                                </td>
                                <td class="gray-background" style="width: 25%; text-align: center; padding: 4px;">
                                    <span style="font-size: 10px; font-weight: bold;">21c. Training</span>
                                </td>
                                <td class="gray-background" style="width: 25%; text-align: center; padding: 4px;">
                                    <span style="font-size: 10px; font-weight: bold;">21d. Eligibility</span>
                                </td>
                            </tr>
                            <tr>
                                <td style=" border-bottom: none; padding: 14px 0px; text-align: center;">
                                    <span style="font-size: 10px;">{{ $positionData['education'] ?? '' }}</span>
                                </td>
                                <td style=" border-bottom: none; padding: 14px 0px; text-align: center;">
                                    <span style="font-size: 10px;">{{ $positionData['experience'] ?? '' }}</span>
                                </td>
                                <td style=" border-bottom: none; padding: 14px 0px; text-align: center;">
                                    <span style="font-size: 10px;">{{ $positionData['training'] ?? '' }}</span>
                                </td>
                                <td style=" border-bottom: none; padding: 14px 0px; text-align: center;">
                                    <span style="font-size: 10px;">{{ $positionData['eligibility'] ?? '' }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="padding: 0; border: none;">
                        <table class="nested-table-core" style="width: 100%; border-collapse: collapse; ">
                            <tr>
                                <td class="gray-background" style="width: 75%; text-align: left; padding: 4px;">
                                    <span style="font-size: 10px; font-weight: bold;">21e. Core Competencies</span>
                                </td>
                                <td class="gray-background" style="width: 25%; text-align: center; padding: 4px;">
                                    <span style="font-size: 10px; font-weight: bold;">Competency Level</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 14px 0px; text-align: center; border-bottom: none;">
                                    @if (empty(trim((string) ($positionData['core_competencies'] ?? ''))))
                                        <span style="font-size: 8px; font-style: italic;">(Indicate the required Core Competencies here)</span>
                                        <br><br>
                                    @endif
                                    <span style="font-size: 10px; white-space: pre-line;">
                                        {!! nl2br(e($positionData['core_competencies'] ?? '')) !!}
                                    </span>
                                </td>
                                <td style="padding: 14px 0px; text-align: center; border-bottom: none;">
                                    @if (empty(trim((string) ($positionData['core_level'] ?? 'INTERMEDIATE'))))
                                        <span style="font-size: 8px; font-style: italic;">(Indicate the required Competency Level here)</span>
                                        <br><br>
                                    @endif
                                    <span style="font-size: 10px; white-space: pre-line;">
                                        {!! nl2br(e($positionData['core_level'] ?? 'INTERMEDIATE')) !!}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="padding: 0; border: none;">
                        <table class="nested-table-leadership" style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td class="gray-background" style="width: 75%; text-align: left; padding: 4px;">
                                    <span style="font-size: 10px; font-weight: bold;">21f. Leadership Competencies</span>
                                </td>
                                <td class="gray-background" style="width: 25%; text-align: center; padding: 4px;">
                                    <span style="font-size: 10px; font-weight: bold;">Competency Level</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 14px 0px; vertical-align: top; text-align: center; border-bottom: none;">
                                    @if (empty(trim((string) ($positionData['leadership_competencies'] ?? ''))))
                                        <span style="font-size: 8px; font-style: italic;">(Indicate the required Leadership Competencies here)</span>
                                        <br><br>
                                    @endif
                                    <span style="font-size: 10px; white-space: pre-line;">
                                        {!! nl2br(e($positionData['leadership_competencies'] ?? '')) !!}
                                    </span>
                                </td>
                                <td style="padding: 14px 0px; vertical-align: top; text-align: center; border-bottom: none;">
                                    @if (empty(trim((string) ($positionData['leadership_level'] ?? 'INTERMEDIATE'))))
                                        <span style="font-size: 8px; font-style: italic;">(Indicate the required Competency Level here)</span>
                                        <br><br>
                                    @endif
                                    <span style="font-size: 10px; white-space: pre-line;">
                                        {!! nl2br(e($positionData['leadership_level'] ?? 'INTERMEDIATE')) !!}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="padding: 0; border: none;">
                        <table class="nested-table-technical" style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td colspan="2" class="gray-background" style="text-align: left; padding: 4px; border: 1px solid #000;">
                                    <span style="font-size: 10px; font-weight: bold;">22. STATEMENT OF DUTIES AND RESPONSIBILITIES (Technical Competencies)</span>
                                </td>
                                <td class="gray-background" style="width: 25%; text-align: center; padding: 4px; border: 1px solid #000;">
                                    <span style="font-size: 10px; font-weight: bold;">Competency Level</span>
                                </td>
                            </tr>

                            @php
                                // Count how many duties exist
                                $dutyCount = 0;
                                for ($i = 1; $i <= 20; $i++) {
                                    $percentageKey = 'duty_' . $i . '_percentage';
                                    $descriptionKey = 'duty_' . $i . '_description';
                                    if (!empty($positionData[$percentageKey] ?? '') || !empty($positionData[$descriptionKey] ?? '')) {
                                        $dutyCount++;
                                    }
                                }
                                // If no duties found, set to 1 to show at least one row
                                if ($dutyCount == 0) {
                                    $dutyCount = 1;
                                }
                                // Rowspan = dutyCount + 1 (for the sub-header row)
                                $rowspanValue = $dutyCount + 1;
                            @endphp
                            <tr>
                                <td style="width: 20%; text-align: center;">
                                    <span style="font-size: 8px; font-style: italic; padding: 4px;">Percentage of Working Time</span>
                                </td>
                                <td style="width: 55%; text-align: center;">
                                    <span style="font-size: 8px; font-style: italic; padding: 4px;">(State the duties and responsibilities here)</span>
                                </td>
                                <td rowspan="{{ $rowspanValue }}" style="text-align: center; border-bottom: none;">
                                    @if (empty(trim((string) ($positionData['duty_1_level'] ?? ''))))
                                        <span style="font-size: 8px; font-style: italic; padding: 4px;">(Indicate the required Competency Level here)</span>
                                        <br><br>
                                    @endif
                                    <span style="font-size: 10px;">{{ $positionData['duty_1_level'] ?? '' }}</span>
                                </td>
                            </tr>

                            @php
                                $hasDuty = false;
                            @endphp
                            @for ($i = 1; $i <= 20; $i++)
                                @php
                                    $percentageKey = 'duty_' . $i . '_percentage';
                                    $descriptionKey = 'duty_' . $i . '_description';
                                    $percentage = $positionData[$percentageKey] ?? '';
                                    $description = $positionData[$descriptionKey] ?? '';
                                @endphp
                                @if (!empty($percentage) || !empty($description))
                                    @php $hasDuty = true; @endphp
                                    <tr>
                                        <td style="padding: 15px 0px; text-align: center; border-bottom: none;">
                                            <span style="font-size: 10px;">{{ $percentage }}</span>
                                        </td>
                                        <td style="padding: 15px 0px; text-align: center; border-bottom: none;">
                                            <span style="font-size: 10px;">{{ $description }}</span>
                                        </td>
                                    </tr>
                                @endif
                            @endfor
                            @if (!$hasDuty)
                                <tr>
                                    <td style="padding: 15px 0px; text-align: center; border-bottom: none;">
                                        <span style="font-size: 10px;">{{ $positionData['duty_1_percentage'] ?? '' }}</span>
                                    </td>
                                    <td style="padding: 15px 0px; text-align: center; border-bottom: none;">
                                        <span style="font-size: 10px;">{{ $positionData['duty_1_description'] ?? '' }}</span>
                                    </td>
                                </tr>
                            @endif
                        </table>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="padding: 0; border: none;">
                        <table class="nested-table-acknowledgment" style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td colspan="2" class="gray-background" style="padding: 4px; border: 1px solid #000;">
                                    <span style="font-size: 10px; font-weight: bold;">23. ACKNOWLEDGMENT AND ACCEPTANCE</span>
                                </td>
                            </tr>

                            <tr>
                                <td colspan="2" style="padding: 15px 4px; text-align: justify; text-indent: 30px;">
                                    <span style="font-size: 10px;">I have received a copy of this Position Description Form. It has been discussed with me and I have freely chosen to comply with the performance and behavior/conduct expectations contained herein.</span>
                                </td>
                            </tr>

                            <tr>
                                <td style="width: 50%; text-align: center; padding: 15px 0px;">
                                    <div style="font-size: 10px; font-weight: bold;">{{ $positionData['employee_name'] ?? '' }}</div>
                                    <div style="border-top: 1px solid #000; width: 90%; margin: 2px auto 2px auto;"></div>
                                    <div style="font-size: 10px; margin-top: 2px;">{{ $positionData['employee_date'] ?? '' }}</div><br>
                                    <div style="font-size: 10px; margin-top: 2px; font-weight: bold;">Employee's Name, Date and Signature</div>
                                </td>
                                <td style="width: 50%; text-align: center; padding: 15px 0px;">
                                    <div style="font-size: 10px; font-weight: bold;">{{ $positionData['supervisor_name'] ?? '' }}</div>
                                    <div style="border-top: 1px solid #000; width: 90%; margin: 2px auto 2px auto;"></div>
                                    <div style="font-size: 10px; margin-top: 2px;">{{ $positionData['supervisor_date'] ?? '' }}</div><br>
                                    <div style="font-size: 10px; margin-top: 2px; font-weight: bold;">Supervisor's Name, Date and Signature</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>



            </table>
        </div>








    </body>
</html>
