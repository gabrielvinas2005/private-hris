<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certification of Transfer of Leave Credits</title>
    <style>
        @page { margin: 0; size: A4; }
        body {
            margin: 0;
            padding: 0 1in;
            font-family: Arial, sans-serif;
            font-size: 12pt;
        }
        header, footer {
            position: fixed;
            left: 0;
            right: 0;
            width: 100%;
            text-align: center;
        }
        header { top: 10px; }
        footer { bottom: 10px; }
        header img, footer img {
            display: block;
            margin: 0 auto;
            max-width: 100%;
            height: auto;
            object-fit: contain;
        }
        header img { max-height: 140px; }
        footer img { max-height: 120px; }
        .content {
            margin-top: 180px;
            margin-bottom: 150px;
        }
        p { line-height: 1.5; margin: 0 0 12px 0; text-align: justify; }
        .title { text-align: center; font-weight: bold; margin: 12px 0 18px 0; }
        .label-col { width: 180px; display: inline-block; }
        .leave-credits {
            text-align: center;
        }
        .leave-credits-inner {
            display: inline-block;
            text-align: left;
        }
        .leave-credits-inner p {
            text-align: left;
            margin: 0 0 6px 0;
        }
        .leave-credits-inner .label-col {
            width: 130px;
            display: inline-block;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 12pt;
        }
        .table th, .table td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: left;
        }
        .table th { font-weight: bold; }
        .mt-16 { margin-top: 16px; }
        .list { margin-left: 18px; }
        .list li { margin-bottom: 8px; text-align: justify; }
        .signature {
            margin-top: 30px;
            text-align: right;
        }
        .signature .name { font-weight: bold; margin-top: 24px; }
        .small-text { font-size: 11pt; }
        .report-date {
            position: fixed;
            left: 1in;
            bottom: 130px;
            font-size: 12pt;
        }
    </style>
</head>
<body>
<header>
    @if($headerImg)
        <img src="{{ $headerImg }}" alt="Header">
    @endif
</header>

<footer>
    @if($footerImg)
        <img src="{{ $footerImg }}" alt="Footer">
    @endif
</footer>

<div class="content">
    <div class="title">CERTIFICATION OF TRANSFER OF LEAVE CREDITS</div>

    <p>
        This is to certify that {{ strtoupper($record->employee_name) }}, {{ $record->position_name ?? '' }} who was separated from the {{ strtoupper($orgCompanyName) }} effective {{ $record->separation_date ? \Carbon\Carbon::parse($record->separation_date)->format('F d, Y') : 'N/A' }}, has the following unused leave credits as of the date of separation:
    </p>

    <div class="leave-credits mt-16">
        <div class="leave-credits-inner">
            <p><span class="label-col">Vacation Leave :</span> {{ number_format($vac_total, 3) }} days</p>
            <p><span class="label-col">Sick Leave :</span> {{ number_format($sick_total, 3) }} days</p>
            <p><span class="label-col">Total :</span> {{ number_format($grand_total, 3) }} days</p>
        </div>
    </div>

    <p class="mt-16">
        This is to certify further that as of {{ $record->separation_date ? \Carbon\Carbon::parse($record->separation_date)->format('F d, Y') : 'N/A' }}, {{ explode(' ', $record->employee_name)[0] }}:
    </p>

    <ol class="list">
        <li>{{ $reasons['reason_1'] }}</li>
        <li>{{ $reasons['reason_2'] }}</li>
    </ol>

    <table class="table">
        <tr>
            <th>Type of Leave</th>
            <th>No. of days availed</th>
            <th>Date/s leave was availed</th>
        </tr>
        @foreach($leave_rows as $leaveRow)
        <tr>
            <td>{{ $leaveRow['type'] }}</td>
            <td>{{ $leaveRow['days'] }}</td>
            <td>{{ $leaveRow['dates'] }}</td>
        </tr>
        @endforeach
    </table>

    <ol class="list" start="3">
        <li>{{ $reasons['reason_3'] }}</li>
        <li>{{ $reasons['reason_4'] }}</li>
    </ol>

    <p class="mt-16">
        {{ $purpose_text }}
    </p>

    <div class="signature">
        <div class="name">MARIA ANTONIETTE S. ZOILO</div>
        <div class="small-text">Administrative Officer V</div>
    </div>
</div>

<div class="report-date">Date: {{ $printed_date->format('j F Y') }}</div>
</body>
</html>
