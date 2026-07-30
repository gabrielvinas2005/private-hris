<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Position Description Form</title>
    <style>
        html, body { width: 210mm; height: 297mm; margin: 12mm auto; font-family: Arial, Helvetica, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        .b { border: 1px solid #000; }
        .th { background: #f2f2f2; font-weight: bold; }
        .c { text-align: center; }
        .vtop { vertical-align: top; }
        .pad { padding: 10px; }
        .bigpad {padding: 20px;}
        .small { font-size: 11px; }
        .xs { font-size: 10px; }
        .title { font-size: 14px; font-weight: bold; }
        .page-break { page-break-after: always; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <table class="b small">
        <tr>
            <td class="b pad vtop" style="width:40%">
                <div class="c title">Republic of the Philippines</div>
                <div class="c title">POSITION DESCRIPTION FORM</div>
                <div class="c xs title">DBM-CSC Form No. 1</div>
                <div class= "c xs">(Revised version No. 1, 2017)</div>
            </td>
            
                <table class="" style="width:100%">
                    
                        <td class="b pad th" style="width:100%; border-bottom: 2px solid #000;">1. POSITION TITLE (as approved by authorized agency) with parenthetical title</td>
                    
                    <tr>
                        <td class="center pad" style="width:100%">{{ $positionData['position_title'] ?? '' }}</td>
                    </tr>
                </table>
            
        </tr>
    </table>

    <!-- 2-3 ITEM NUMBER AND SALARY GRADE -->
    <table class="b small" style="margin-top:4px">
        <tr>
            <td class="b pad th" style="width:50%">2. ITEM NUMBER</td>
            <td class="b pad th" style="width:50%">3. SALARY GRADE</td>
        </tr>
        <tr>
            <td class="b bigpad c" style="width:50%">{{ $positionData['item_number'] ?? '' }}</td>
            <td class="b bigpad c" style="width:50%">{{ $positionData['salary_grade'] ?? '' }}</td>
        </tr>
    </table>

    <!-- 4. FOR LOCAL GOVERNMENT POSITION, ENUMERATE GOVERNMENTAL UNIT AND CLASS -->
    <table class="b small" style="margin-top:4px">
        <tr>
            <td class="b pad th c" style="text-align: left;" colspan="3">4. FOR LOCAL GOVERNMENT POSITION, ENUMERATE GOVERNMENTAL UNIT AND CLASS</td>
        </tr>
        <tr>
            <td class="pad c" style="width:33%; border-left: none; border-right: none;">
                Province<br>City<br>Municipality
            </td>
            <td class="pad c" style="width:33%; border-left: none; border-right: none;">
                1st Class<br>2nd Class<br>3rd Class<br>4th Class
            </td>
            <td class="pad c" style="width:34%; border-left: none; border-right: none;">
                5th Class<br>6th Class<br>Special
            </td>
        </tr>
    </table>

    <!-- 5-8 ORG INFO / WORKSTATION -->
    <table class="b small" style="margin-top:4px">
        <tr>
            <td class="b pad th" style="width:50%">5. DEPARTMENT, CORPORATION OR AGENCY/ LOCAL GOVERNMENT</td>
            <td class="b pad th" style="width:50%">6. BUREAU OR OFFICE</td>
        </tr>
        <tr>
            <td class="b bigpad c" style="width:50%">{{ $positionData['department'] ?? 'DEPARTMENT OF TRADE AND INDUSTRY' }}</td>
            <td class="b bigpad c" style="width:50%">{{ $positionData['office'] ?? strtoupper($orgCompanyName) }}</td>
        </tr>
        <tr>
            <td class="b pad th" style="width:50%">7. DEPARTMENT / BRANCH / DIVISION</td>
            <td class="b pad th" style="width:50%">8. WORKSTATION / PLACE OF WORK</td>
        </tr>
        <tr>
            <td class="b bigpad c" style="width:50%">{{ $positionData['division'] ?? 'Office of the Executive Director (OED)' }}</td>
            <td class="b bigpad c" style="width:50%">{{ $positionData['location'] ?? strtoupper($orgCompanyAddress) }}</td>
        </tr>
    </table>

    <!-- 9-12 APPROPRIATION / SALARY -->
    <table class="b small" style="margin-top:4px">
        <tr>
            <td class="b pad th" style="width:25%">9. PRESENT APPROP ACT</td>
            <td class="b pad th" style="width:25%">10. PREVIOUS APPROP ACT</td>
            <td class="b pad th" style="width:25%">11. SALARY AUTHORIZED</td>
            <td class="b pad th" style="width:25%">12. OTHER COMPENSATION</td>
        </tr>
        <tr>
            <td class="b bigpad c" style="width:25%"></td>
            <td class="b bigpad c" style="width:25%"></td>
            <td class="b bigpad c" style="width:25%">{{ $positionData['authorized_salary'] ?? '' }}</td>
            <td class="b bigpad c" style="width:25%">{{ $positionData['other_compensation'] ?? '' }}</td>
        </tr>
    </table>

    <!-- 13-14 SUPERVISOR INFO -->
    <table class="b small" style="margin-top:4px">
        <tr>
            <td class="b pad th" style="width:50%">13. POSITION TITLE OF IMMEDIATE SUPERVISOR</td>
            <td class="b pad th" style="width:50%">14. POSITION TITLE OF NEXT HIGHER SUPERVISOR</td>
        </tr>
        <tr>
            <td class="b pad c" style="width:50%">
                SUPERVISING<br>TRADE-INDUSTRY<br>DEVELOPMENT SPECIALIST<br><br>CHIEF TRADE-INDUSTRY<br>DEVELOPMENT SPECIALIST
            </td>
            <td class="b pad c" style="width:50%">
                DEPUTY EXECUTIVE DIRECTOR<br><br>EXECUTIVE DIRECTOR
            </td>
        </tr>
    </table>

    <!-- 15. POSITION TITLE AND ITEM OF THOSE DIRECTLY SUPERVISED -->
    <table class="b small" style="margin-top:4px">
        <tr>
            <td class="b pad th" colspan="2">15. POSITION TITLE, AND ITEM OF THOSE DIRECTLY SUPERVISED</td>
        </tr>
        <tr>
            <td class="b pad th c" style="width:50%">POSITION TITLE</td>
            <td class="b pad th c" style="width:50%">ITEM NUMBER</td>
        </tr>
        <tr>
            <td class="b bigpad c" style="width:50%">N/A</td>
            <td class="b bigpad c" style="width:50%">N/A</td>
        </tr>
    </table>

    <!-- 16 MACHINES -->
    <table class="b small" style="margin-top:4px">
        <tr>
            <td class="b pad th" style="width:100%">16. MACHINE, EQUIPMENT, TOOLS, ETC., USED REGULARLY IN PERFORMANCE OF WORK</td>
        </tr>
        <tr>
            <td class="b bigpad c" style="width:100%">{{ $positionData['machines_tools'] ?? 'Computer/Laptop, Printer, Telephone, External Hard Drive, Switches, UTP Cable, Television, Headset and Microphone' }}</td>
        </tr>
    </table>

    <!-- 17 CONTACTS GRID -->
    <table class="b small" style="margin-top:4px">
        <tr>
            <td class="b pad th" colspan="7">17. CONTACTS / CLIENTS / STAKEHOLDERS</td>
        </tr>
        <tr>
            <td class="b pad th c" style="width:15%">17a. Internal</td>
            <td class="b pad th c" style="width:15%">Occasional</td>
            <td class="b pad th c" style="width:15%">Frequent</td>
            <td class="b pad th c" style="width:15%">17b. External</td>
            <td class="b pad th c" style="width:15%">Occasional</td>
            <td class="b pad th c" style="width:15%">Frequent</td>
            <td class="b pad" style="width:10%"></td>
        </tr>
        @php
            $internal = ['Executive / Managerial','Supervisors','Non-Supervisors','Staff'];
            $external = ['General Public','Other Agencies','Others (Please Specify):'];
            $rows = max(count($internal), count($external));
        @endphp
        @for($i=0;$i<$rows;$i++)
        <tr>
            <td class="b pad c">{{ $internal[$i] ?? '' }}</td>
            <td class="b pad c"></td>
            <td class="b pad c"></td>
            <td class="b pad c">{{ $external[$i] ?? '' }}</td>
            <td class="b pad c"></td>
            <td class="b pad c"></td>
            <td class="b pad c"></td>
        </tr>
        @endfor
    </table>

    <!-- 18 WORKING CONDITION GRID -->
    <table class="b small" style="margin-top:4px; width:100%;">
        <tr>
            <td class="b pad th" colspan="2" style="width:100%">18. WORKING CONDITION</td>
        </tr>
        <tr>
            <td class="b pad c" style="width:50%">Office Work</td>
            <td class="b pad c" style="width:50%">Other/s (Please Specify)</td>
        </tr>
        <tr>
            <td class="b pad c" style="width:50%">Field Work</td>
            <td class="b pad c" style="width:50%"></td>
        </tr>
    </table>

    <table class="b small" style="margin-top:4px">
        <tr>
            <td class="b pad th" style="width:100%">19. BRIEF DESCRIPTION OF THE GENERAL FUNCTION OF THE UNIT OR SECTION</td>
        </tr>
        <tr>
            <td class="b pad vtop c" style="height:70px">{!! nl2br(e($positionData['unit_function'] ?? '')) !!}</td>
        </tr>
    </table>

    <table class="b small" style="margin-top:4px">
        <tr>
            <td class="b pad th" style="width:100%">20. BRIEF DESCRIPTION OF THE GENERAL FUNCTION OF THE POSITION (Job Summary)</td>
        </tr>
        <tr>
            <td class="b pad vtop c" style="height:70px">{!! nl2br(e($positionData['job_summary'] ?? ($positionData['position_summary'] ?? ''))) !!}</td>
        </tr>
    </table>

    <!-- 21. QUALIFICATION STANDARDS -->
    <table class="b small" style="margin-top:4px">
        <tr>
            <td class="b pad th" style="width:100%">21. QUALIFICATION STANDARDS</td>
        </tr>
    </table>

    <table class="b small" style="margin-top:4px">
        <tr>
            <td class="b pad th" style="width:25%">21a. Education</td>
            <td class="b pad th" style="width:25%">21b. Experience</td>
            <td class="b pad th" style="width:25%">21c. Training</td>
            <td class="b pad th" style="width:25%">21d. Eligibility</td>
        </tr>
        <tr>
            <td class="b bigpad vtop c">{{ $positionData['education'] ?? '' }}</td>
            <td class="b bigpad vtop c">{{ $positionData['experience'] ?? '' }}</td>
            <td class="b bigpad vtop c">{{ $positionData['training'] ?? '' }}</td>
            <td class="b bigpad vtop c">{{ $positionData['eligibility'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="b pad th" colspan="4">21e. Core Competencies</td>
        </tr>
        <tr>
            <td class="b pad vtop c" colspan="3">{!! nl2br(e($positionData['core_competencies'] ?? 'Technologically Savvy\nEffective Communication\nCustomer Focus\nResults Driven\nTeam Player\nKnowledge Management')) !!}</td>
            <td class="b pad c vtop c" style="width:20%">Competency Level<br><br>{{ $positionData['core_level'] ?? 'INTERMEDIATE' }}</td>
        </tr>
        <tr>
            <td class="b bigpad th" colspan="4">21f. Leadership Competencies</td>
        </tr>
        <tr style="page-break-inside: avoid">
            <td class="b pad vtop c" colspan="3" style="white-space: pre-line; word-break: break-word">{!! nl2br(e($positionData['leadership_competencies'] ?? 'Building collaborative, inclusive working relationships\nManaging performance and coaching results\nLeading change\nThinking strategically and creatively\nCreating and nurturing a high performing organization')) !!}</td>
            <td class=" pad c vtop ">Competency Level<br><br>{{ $positionData['leadership_level'] ?? 'INTERMEDIATE' }}</td>
        </tr>
    </table>

    <table class="b small" style="margin-top:4px">
        <tr>
            <td class="b pad th c" style="width:20%">22. Percentage of Working Time</td>
            <td class="b pad th c" style="width:60%">STATEMENT OF DUTIES AND RESPONSIBILITIES (Technical Competencies)</td>
            <td class="b pad th c " style="width:20%">Competency Level</td>
        </tr>
        @for($i=1;$i<=20;$i++)
            @php $desc = $positionData['duty_'.$i.'_description'] ?? '' @endphp
            @if(!empty($desc))
            <tr>
                <td class="b pad c vtop">{{ $positionData['duty_'.$i.'_percentage'] ?? '' }}</td>
                <td class="b pad vtop">{!! nl2br(e($desc)) !!}</td>
                <td class="b pad c vtop">{{ $positionData['duty_'.$i.'_level'] ?? '' }}</td>
            </tr>
            @endif
        @endfor
    </table>

    <table class="b small" style="margin-top:10px">
        <tr>
            <td class="b pad th">23. ACKNOWLEDGMENT AND ACCEPTANCE</td>
        </tr>
        <tr>
            <td class=" bigpad" style="text-indent: 20px;">I have received a copy of this position description. It has been discussed with me and I have freely chosen to comply with the performance and behavioral/values expectations contained herein.</td>
        </tr>

        <table class="small" style="width:100%; margin-top:40px">
            <tr>
                <td class="c" style="width:33%">
                    <div class="xs title">{{ $positionData['employee_name'] ?? '' }}</div>
                    <div style="border-top:1px solid #000; width:90%; margin:2px auto 2px auto"></div>
                    <div class="xs" style="margin-top:2px">{{ $positionData['employee_date'] ?? '' }}</div>
                    <div class="xs" style="margin-top:2px">Employee's Name, Date and Signature</div>
                </td>
            
                <td class="c" style="width:34%"></td>
                <td class="c" style="width:33%">
                    <div class="xs title">{{ $positionData['supervisor_name'] ?? '' }}</div>
                    <div style="border-top:1px solid #000; width:90%; margin:2px auto 2px auto"></div>
                    <div class="xs" style="margin-top:2px">{{ $positionData['supervisor_date'] ?? '' }}</div>
                    <div class="xs" style="margin-top:2px">Supervisor's Name, Date and Signature</div>
                </td>
            </tr>
        </table>
               
        </table>
    </table>

    <!-- <table class="small" style="width:100%; margin-top:40px">
        <tr>
            <td class="c" style="width:33%">
                <div class="xs title">{{ $positionData['employee_name'] ?? '' }}</div>
                <div style="border-top:1px solid #000; width:90%; margin:2px auto 2px auto"></div>
                <div class="xs" style="margin-top:2px">{{ $positionData['employee_date'] ?? '' }}</div>
                <div class="xs" style="margin-top:2px">Employee's Name, Date and Signature</div>
            </td>
            <td class="c" style="width:34%"></td>
            <td class="c" style="width:33%">
                <div class="xs title">{{ $positionData['supervisor_name'] ?? '' }}</div>
                <div style="border-top:1px solid #000; width:90%; margin:2px auto 2px auto"></div>
                <div class="xs" style="margin-top:2px">{{ $positionData['supervisor_date'] ?? '' }}</div>
                <div class="xs" style="margin-top:2px">Supervisor's Name, Date and Signature</div>
            </td>
        </tr>
    </table> -->
</body>
</html>
