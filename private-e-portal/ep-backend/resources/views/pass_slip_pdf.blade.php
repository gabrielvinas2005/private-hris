<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pass Slip</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0.5in;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            margin: 0;
            padding: 0;
        }

        .container {
            border: 2px solid black;
            padding: 15px 20px 20px 20px;
            min-height: 600px;
        }

        .form-code-top,
        .form-code-bottom {
            width: 100%;
            text-align: right;
            font-size: 10pt;
            font-weight: bold;
            margin-right: 5px;
        }

        .header {
            text-align: center;
            margin: 5px 0 20px 0;
        }

        .org-name {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 20px;
        }

        .form-title {
            font-weight: bold;
            font-size: 12pt;
        }

        .main-section {
            margin-top: 20px;
            position: relative;
            min-height: 100px;
        }

        .name-date-table {
            width: 100%;
            border-collapse: collapse;
        }

        .names-cell {
            width: 60%;
            vertical-align: top;
        }

        .date-cell {
            width: 40%;
            vertical-align: top;
            padding-left: 40px;
        }

        .field-line {
            margin-bottom: 12px;
        }

        .label {
            font-weight: bold;
            display: inline-block;
            vertical-align: bottom;
            margin-right: 5px;
        }

        .underline {
            display: inline-block;
            border-bottom: 1px solid black;
            vertical-align: bottom;
            min-height: 16px;
            padding-bottom: 1px;
        }

        .names-line1 {
            width: 260px;
        }

        .names-line2 {
            width: 330px;
            margin-left: 60px;
        }

        .right-underline {
            width: 200px;
        }

        .section-break {
            clear: both;
            margin-top: 15px;
        }

        .full-label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .full-underline {
            border-bottom: 1px solid black;
            min-height: 18px;
            padding-bottom: 1px;
            margin-bottom: 12px;
            margin-left: 120px;
        }

        .purpose-section {
            margin-top: 15px;
            margin-bottom: 40px;
        }

        .purpose-line {
            border-bottom: 1px solid black;
            min-height: 18px;
            padding-bottom: 1px;
            margin-bottom: 12px;
            margin-left: 120px;
        }

        .approval-section {
            margin-top: 60px;
            position: relative;
        }

        .approval-text {
            font-weight: bold;
            text-align: center;
            margin-bottom: 40px;
        }

        .signature-container {
            float: right;
            width: 450px;
            margin-right: 20px;
        }

        .signature-line {
            border-bottom: 1px solid black;
            width: 100%;
            min-height: 20px;
            padding-bottom: 2px;
            margin-bottom: 3px;
            text-align: center;
        }

        .signature-label {
            font-size: 10pt;
            text-align: center;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    @php
        use App\Helpers\CompanyHelper;

        $companyName = CompanyHelper::getName() ?: 'Company Name';
    @endphp
    <div class="form-code-top">AFM-PER FR#09/REV.00/15-16-14</div>

    <div class="container">
        <div class="header">
            <div class="org-name">{{ $companyName }}</div>
            <div class="form-title">PASS SLIP</div>
        </div>

        <div class="main-section clearfix">
            <table class="name-date-table">
                <tr>
                    <!-- Left Side: NAMES with 2 lines -->
                    <td class="names-cell">
                        <div class="field-line">
                            <span class="label">NAMES:</span>
                            <span class="underline names-line1">{{ $pass_slip->employee_full_name ?? '' }}</span>
                        </div>
                        <div class="field-line">
                            <span class="underline names-line2"></span>
                        </div>
                    </td>

                    <!-- Right Side: DATE, TIME OUT, TIME IN -->
                    <td class="date-cell">
                        <div class="field-line">
                            <span class="label">DATE:</span>
                            <span class="underline right-underline">{{ $pass_slip->date ? \Carbon\Carbon::parse($pass_slip->date)->format('F d, Y') : '' }}</span>
                        </div>
                        <div class="field-line">
                            <span class="label">TIME OUT:</span>
                            <span class="underline right-underline">{{ $pass_slip->time_out ? \Carbon\Carbon::parse($pass_slip->time_out)->format('h:i A') : '' }}</span>
                        </div>
                        <div class="field-line">
                            <span class="label">TIME IN:</span>
                            <span class="underline right-underline">{{ $pass_slip->time_in ? \Carbon\Carbon::parse($pass_slip->time_in)->format('h:i A') : '' }}</span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- DESTINATION: Full width -->
        <div class="section-break">
            <div class="full-label">DESTINATION:</div>
            <div class="full-underline">{{ $pass_slip->destination ?? '' }}</div>
        </div>

        <!-- PURPOSE: Full width with 3 lines -->
        <div class="purpose-section">
            <div class="full-label">PURPOSE:</div>
            <div class="purpose-line">{{ $pass_slip->purpose ?? '' }}</div>
            <div class="purpose-line"></div>
            <div class="purpose-line"></div>
        </div>

        <!-- Approval Section -->
        <div class="approval-section">
            <div class="approval-text">Approved:</div>
            <div class="signature-container">
                <div class="signature-line">
                    @if($pass_slip->status === 'approved' && $pass_slip->division_chief)
                        {{ $pass_slip->division_chief }}
                    @endif
                </div>
                <div class="signature-label">Division Chief/Authorized Representative</div>
            </div>
        </div>
    </div>

</body>
</html>
