<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice of Step Increment</title>
    <style>
        body {
            font-family: "DejaVu Sans", Calibri, sans-serif;
            margin: 40px;
            font-size: 12px;
        }


        .title {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 20px;
            float: right;
        }

        .subtitle {
            text-align: center;
            font-size: 16px;
            font-weight: bold;

        }

        .content {
            margin-top: 20px;
        }

        .employee-info {
            font-size: 12px;
            font-weight: bold;
        }

        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .salary-table td {
            padding: 8px;
        }

        .content {
            font-size: 13px;
        }

        .signature {
            float: right;
            margin-top: 20px;
            text-align: left;
        }

        .end-note {
            margin-top: 110px;
        }
    </style>
</head>

<body>

    <div class="title">Annex "B"</div><br>
    <div class="subtitle">Notice of Step Increment</div>

    <p style="float: right; font-size: 11px;">Date: {{ \Carbon\Carbon::now()->format('F j, Y') }} </p>
    <p style="border-top: 1px solid black; margin-top: 28px; width:110px; margin-left: 520px;"></p>



    <div class="employee-info">
        <p>Mr./Ms. {{ $nosi[0]->name }} </p>
        <p style="border-top: 1px solid black; margin-top: -13px; width:180px; margin-left: 49px;"></p>
        <p style=" text-indent:45px; border-bottom: 1px solid black; margin-top: -8px; width:228px;">
            {{ $nosi[0]->position }}</p>
        <p style="border-bottom: 1px solid black; margin-top: -8px; width:228px;">{{ $nosi[0]->department }}</p>
        <p style="border-bottom: 1px solid black; margin-top: -8px; width:228px;">{{ $orgCompanyAddress }}</p>

    </div>

    <div class="content">
        <p>Dear Mr./Ms. <span
                style="border-bottom: 1px solid black; display: inline-block;  text-align: left;"><b>{{ $nosi[0]->name }}</b>
                :</span>
        </p>

        <p style="text-indent: 40px">Pursuant to the Civil Service Commission and Department of Budget and Management
            Joint Circular No. <span
                style="border-bottom: 1px solid black; margin-top: -8px; width:80px;">{{ $dates['joint_no'] }}</span>
            dated
            <span
                style="border-bottom: 1px solid black; margin-top: -8px; width:80px;">{{ \Carbon\Carbon::parse($dates['joint_date'])->format('F j, Y') }}</span>,
            implementing item (4)(d) of the Senate and House of Representatives Joint Resolution No. 4, s.
            2009, approved on June 17, 2009, your salary as <span
                style="border-bottom: 1px solid black; margin-top: -8px; width:150px;"> {{ $nosi[0]->position }} </span>
            is hereby adjusted
            effective <span style="border-bottom: 1px solid black; margin-top: -8px; width:150px;">
                {{ \Carbon\Carbon::parse($nosi[0]->effectivity_date)->format('F j, Y') }}
            </span>, as follows:
        </p>
    </div>


    <table class="salary-table">
        <tr>
            <td>1.</td>
            <td>Actual monthly basic salary as of
                <span style="border-bottom: 1px solid black; display: inline-block; width: 120px; text-align: center;">
                    {{ \Carbon\Carbon::parse($dates['salary_as_of'])->format('F j, Y') }}
                </span>
                <br>(SG:
                <span style="border-bottom: 1px solid black; display: inline-block; width: 120px; text-align: center;">
                    {{ $nosi[0]->employee_salary_grade_name ?? $nosi[0]->current_salary_grade_name ?? $nosi[0]->current_salary_grade_id }}
                </span>, Step
                <span style="border-bottom: 1px solid black; display: inline-block; width: 120px; text-align: center;">
                    {{ $nosi[0]->employee_salary_step_name ?? $nosi[0]->current_salary_step_name ?? $nosi[0]->current_salary_step_id }}
                </span>)
            </td>
            <td>₱
                <span style="border-bottom: 1px solid black; display: inline-block; width: 100px; text-align: right;">
                    {{ number_format($nosi[0]->current_salary, 2, '.', ',') }}
                </span>
            </td>
        </tr>
        <tr>
            <td>2.</td>
            <td>Add: one (1) Step Increment <br>Due to Length of Service</td>
            <td>₱
                <span style="border-bottom: 1px solid black; display: inline-block; width: 100px; text-align: right;">
                    {{ number_format($nosi[0]->salary2, 2, '.', ',') }}
                </span>
            </td>
        </tr>
        <tr>
            <td>3.</td>
            <td>Adjusted monthly basic salary effective
                <span style="border-bottom: 1px solid black; display: inline-block; width: 150px; text-align: center;">
                    {{ \Carbon\Carbon::parse($nosi[0]->effectivity_date)->format('F j, Y') }}
                </span>
            </td>
            <td>₱
                <span style="border-bottom: 1px solid black; display: inline-block; width: 100px; text-align: right;">
                    {{ number_format($nosi[0]->new_salary, 2, '.', ',') }}
                </span>
            </td>
        </tr>

    </table>

    <p style="text-indent: 50px;">This salary adjustment is subject to review and post-audit, and to appropriate
        re-adjustment and refund if found
        not in order.</p>

    <div class="signature">
        <p>Very truly yours,</p><br>
        <p style="border-bottom: 1px solid black; margin-top: -8px; width:225px;">{{ $signatories['signatory'] }}</p>
        <p style="text-align:center; margin-top: -8px; ">{{ $signatories['position'] }}
        </p>
    </div><br>
    <div class="end-note">
        <p>Item No. <span
                style="border-bottom: 1px solid black; margin-top: -8px; width:20px;">{{ $nosi[0]->code }}</span><br>
            FY <span
                style="border-bottom: 1px solid black; margin-top: -8px; width:20px;">{{ \Carbon\Carbon::now()->year }}
            </span> Personal Services Itemization and/or <br>Plantilla of Personnel</p>

        <p>CF: GSIS</p>
    </div>

</body>

</html>
