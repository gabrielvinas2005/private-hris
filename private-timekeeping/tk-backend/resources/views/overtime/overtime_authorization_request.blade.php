<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overtime Authorization Request</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0.5in 0.5in;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            margin: 0;
            padding: 15px;
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
        }
        .header-right {
            text-align: right;
            margin-bottom: 15px;
        }
        .form-number {
            font-size: 9pt;
            border: 1px solid #000;
            display: inline-block;
            padding: 2px 6px;
            margin-bottom: 5px;
        }
        .date-prepared {
            text-align: right;
            margin-bottom: 15px;
            font-size: 10pt;
        }
        .date-prepared-line {
            border-bottom: 1px solid #000;
            padding: 0 5px;
            min-width: 150px;
            display: inline-block;
        }
        .title-section {
            text-align: center;
            margin-bottom: 15px;
        }
        .org-name {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-title {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .month-year {
            font-size: 10pt;
        }
        .month-line {
            border-bottom: 1px solid #000;
            padding: 0 10px;
            min-width: 80px;
            display: inline-block;
            text-align: center;
        }
        .section-division {
            margin-bottom: 10px;
            font-size: 10pt;
        }
        .section-label {
            font-weight: bold;
            text-decoration: underline;
        }
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .main-table th,
        .main-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
            vertical-align: middle;
            font-size: 9pt;
        }
        .main-table th {
            background-color: #fff8dc;
            font-weight: bold;
        }
        .main-table td {
            height: 22px;
        }
        .col-activities {
            width: 18%;
            text-align: left !important;
        }
        .col-quantity {
            width: 12%;
        }
        .col-mh {
            width: 12%;
        }
        .col-period {
            width: 20%;
            min-width: 200px;
        }
        .col-person {
            width: 20%;
        }
        .col-ot-pay {
            width: 18%;
        }
        .signature-table {
            width: 100%;
            margin-top: 30px;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 10px 20px;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            margin: 0 auto 8px auto;
            height: 1px;
        }
        .signatory-name {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 3px;
            min-height: 20px;
        }
        .signatory-name.has-name {
            text-decoration: underline;
        }
        .signatory-name.empty {
            text-decoration: none;
        }
        .signatory-title {
            font-size: 9pt;
            margin-bottom: 5px;
        }
        .signatory-label {
            font-size: 9pt;
            margin-top: 5px;
            font-weight: normal;
        }
    </style>
</head>
<body>
    {{-- Header with Form Number and Date Prepared (Right Aligned) --}}
    <div class="header-right">
        <div class="form-number">AFM-PER.FR#14/Rev.00/05-16-14</div>
        <div class="date-prepared">
            Date Prepared: <span class="date-prepared-line">{{ $date_prepared ?? '' }}</span>
        </div>
    </div>

    {{-- Title Section --}}
    <div class="title-section">
        <div class="org-name">{{ strtoupper($orgCompanyName) }}</div>
        <div class="form-title">OVERTIME AUTHORIZATION REQUEST</div>
        <div class="month-year">
            FOR THE MONTH OF: <span class="month-line">{{ $month ?? '' }}</span> {{ $year ?? date('Y') }}
        </div>
    </div>

    {{-- Section/Division --}}
    <div class="section-division">
        <span class="section-label">SECTION/DIVISION:</span>
        {{ $section_division ?? '' }}
    </div>

    {{-- Main Table --}}
    <table class="main-table">
        <thead>
            <tr>
                <th class="col-activities">ACTIVITIES TO BE ACCOMPLISHED</th>
                <th class="col-quantity">ESTIMATED QUANTITY</th>
                <th class="col-mh">EST. MH NEEDED</th>
                <th class="col-period">PERIOD COVERED</th>
                <th class="col-person">PERSON RESPONSIBLE</th>
                <th class="col-ot-pay">WITH / WITHOUT OT PAY</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($items) && count($items) > 0)
                @foreach($items as $item)
                    <tr>
                        <td class="col-activities">{{ $item['activity'] ?? $item['remarks'] ?? '' }}</td>
                        <td class="col-quantity">{{ $item['quantity'] ?? '1' }}</td>
                        <td class="col-mh">{{ $item['mh_needed'] ?? $item['total_hours'] ?? '' }}</td>
                        {{-- Period: date + date_time_from / date_time_to, e.g. May 13, 2026 04:00 PM - 06:00 PM --}}
                        <td class="col-period">{{ $item['period'] ?? '' }}</td>
                        <td class="col-person">{{ $item['person'] ?? '' }}</td>
                        <td class="col-ot-pay">{{ $item['ot_pay_status'] ?? '' }}</td>
                    </tr>
                @endforeach
            @endif
            {{-- Add empty rows to fill the table --}}
            @php
                $itemCount = isset($items) ? count($items) : 0;
                $emptyRows = max(0, 8 - $itemCount);
            @endphp
            @for($i = 0; $i < $emptyRows; $i++)
                <tr>
                    <td class="col-activities">&nbsp;</td>
                    <td class="col-quantity">&nbsp;</td>
                    <td class="col-mh">&nbsp;</td>
                    <td class="col-period">&nbsp;</td>
                    <td class="col-person">&nbsp;</td>
                    <td class="col-ot-pay">&nbsp;</td>
                </tr>
            @endfor
        </tbody>
    </table>

    {{-- Signature Section using table for PDF compatibility --}}
    <table class="signature-table">
        <tr>
            <td>
                {{-- Left signatory: Dept Head --}}
                @php
                    $hasDeptHeadData = isset($has_dept_head_data) && 
                                      ($has_dept_head_data === true || $has_dept_head_data === 'true' || $has_dept_head_data === 1 || $has_dept_head_data === '1');
                    $showSignatureLine = !$hasDeptHeadData;
                    $showNameUnderline = !empty(trim((string) ($dept_head_name ?? '')));
                @endphp
                @if($showSignatureLine)
                    <div class="signature-line"></div>
                @endif
                <div class="signatory-name {{ $showNameUnderline ? 'has-name' : 'empty' }}">{{ $dept_head_name ?? '' }}</div>
                <div class="signatory-title">{{ $dept_head_position ?? '' }}</div>
                <div class="signatory-label">Dept Head</div>
            </td>
            <td>
                {{-- Right signatory: Executive Director --}}
                @php
                    $hasExecDirData = isset($has_executive_director_data) && 
                                     ($has_executive_director_data === true || $has_executive_director_data === 'true' || $has_executive_director_data === 1 || $has_executive_director_data === '1');
                    $showExecSignatureLine = !$hasExecDirData;
                    $showExecNameUnderline = $hasExecDirData && !empty($approver_name);
                @endphp
                @if($showExecSignatureLine)
                    <div class="signature-line"></div>
                @endif
                <div class="signatory-name {{ $showExecNameUnderline ? 'has-name' : 'empty' }}">{{ $approver_name ?? '' }}</div>
                <div class="signatory-title">{{ $approver_position ?? '' }}</div>
            </td>
        </tr>
    </table>
</body>
</html>


