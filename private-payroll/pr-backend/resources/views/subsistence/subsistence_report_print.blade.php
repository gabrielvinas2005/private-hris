<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">

    <title>Payroll Payment Slip</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        @page {
            size: A4 landscape;
        }

        html, body {
            width: 297mm;
            margin: 0;
            padding: 0;
            font-family: 'Source Sans Pro', sans-serif;
            font-size: 7px;
            overflow-x: hidden;
        }

        /* ── Page block: proper margins on all sides ── */
        .page-block {
            width: 277mm;
            padding: 6mm 10mm 8mm 10mm;
            page-break-after: always;
            box-sizing: border-box
        }

        .page-block:last-child {
            page-break-after: auto;
        }

        /* ── Header ── */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            min-height: 90px;
            margin-bottom: 4px;
        }

        .page-header .logo {
            position: absolute;
            left: 0;
            top: 0;
        }

        .page-header .title-block {
            text-align: center;
            line-height: 1.4;
        }

        .page-header .title-block p {
            margin: 0;
            font-size: 8px;
        }

        .page-header .title-block h2 {
            margin: 2px 0;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .page-header .title-block h3 {
            margin: 2px 0;
            font-size: 10px;
            font-weight: bold;
        }

        /* ── Table label ── */
        .table-title {
            font-size: 7px;
            margin: 4px 0 2px 0;
            line-height: 1.4;
        }

        /* ── Main data table ── */
        .report-table {
            width: 100%;
            max-width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            font-size: 5.5px;
            box-sizing: border-box;

        }

        .table-wrapper {
            width: 100%;
            box-sizing: border-box
        }

        .report-table th,
        .report-table td {
            border: 1px solid #000;
            padding: 2px;
            text-align: center;
            line-height: 1.15;
            vertical-align: middle;

            /* critical fixes */
            overflow: hidden;
            text-overflow: clip;
            word-wrap: break-word;
            white-space: normal;
        }

        .report-table th {
            background-color: #f2f2f2;
            white-space: normal;
            font-weight: bold;
        }
        .report-table td:nth-child(3),
        .report-table td:nth-child(4) {
            font-size: 5px;
        }

        /* ── Signature block ── */
        .signature-table {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 7.5px;
        }

        .signature-table td {
            width: 20%;
            border: none;
            text-align: left;
            vertical-align: top;
            padding: 4px 6px;
            line-height: 1.5;
        }

        .signature-table p {
            margin: 0;
        }

        .signature-table .spacer {
            margin-top: 8px;
        }

        .checkbox-box {
            border: 1px solid black;
            padding: 1px 4px;
            display: inline-block;
            min-width: 10px;
        }
    </style>
</head>

<body>

@php
    $chunks = $data->chunk(25);
    $totalChunks = $chunks->count();
    $globalIndex = 0;
@endphp

@foreach ($chunks as $chunkIndex => $chunk)

<div class="page-block">

    {{-- ── Header (repeated on every page) ── --}}
    <div class="page-header">
        <div class="logo">
            <img src="data:image/png;base64,{{ $image }}" width="85" height="85">
        </div>
        <div class="title-block">
            <p>Republic of the Philippines</p>
            <p style="font-size:11px; font-weight:600;">{{ strtoupper($companies[0]->name) }}</p>
            <h2>SUBSISTENCE</h2>
            <p>for {{ $selected_pay_period->month_year ?? 'N/A' }}</p>
            <h3>{{ strtoupper($data->first()->department ?? '') }}</h3>
        </div>
    </div>

    {{-- ── Table label ── --}}
    <div class="table-title">
        General Form No.4<br>
        Revised January 2022<br>
        We acknowledge receipt of the sum shown opposite our name as full compensation
        for services rendered for the period stated:
    </div>



    {{-- ── Data table ── --}}
    <table class="report-table">
        <colgroup>
            <col style="width:2%">
            <col style="width:3%">
            <col style="width:11%">
            <col style="width:9%">
            <col style="width:3%">
            <col style="width:5%">
            <col style="width:3%">
            <col style="width:5%">
            <col style="width:3%">
            <col style="width:5%">
            <col style="width:6%">
            <col style="width:5%">
            <col style="width:5%">
            <col style="width:5%">
            <col style="width:6%">
            <col style="width:14%">
        </colgroup>
      
        <thead>
            <tr>
                <th rowspan="2">No.</th>
                <th rowspan="2">SL CODE</th>
                <th rowspan="2">Employee Name</th>
                <th rowspan="2">Position</th>
                <th colspan="4">SUBSISTENCE ALLOWANCE</th>
                <th rowspan="2">Actual No.<br> of Days</th>
                <th colspan="2">LAUNDRY ALLOWANCE</th>
                <th colspan="2">Total Amount Earned</th>
                <th rowspan="2">Total Amount Earned</th>
                <th rowspan="2">Amount Received</th>
                <th rowspan="2">Signature</th>
            </tr>
            <tr>
                <th>No. of Days</th>
                <th>Full-time Service (50.00/day)</th>
                <th>No. of Days</th>
                <th>Part-time Service (25.00/day)</th>
                <th>Completed {{ $totalFullTimeMonths }} Working Days</th>
                <th>Prorate: (150.00/working day of the month * Actual Days Present)</th>
                <th>SUBSISTENCE ALLOWANCE</th>
                <th>LAUNDRY ALLOWANCE</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($chunk as $emp)
                @php
                    $globalIndex++;
                    $fullTimeDays    = max(0, $emp->full_time_days    ?? 0);
                    $fullTimeService = max(0, $emp->full_time_service  ?? 0);
                    $partTimeDays    = max(0, $emp->zero_work_ot_days  ?? 0);
                    $partTimeService = max(0, $emp->zero_work_ot       ?? 0);
                    $actualDays      = $fullTimeDays + $partTimeDays;
                    $laundryFull     = ($fullTimeDays == $totalFullTimeMonths) ? 150.00 : 0;
                    $laundryProrate  = ($fullTimeDays <  $totalFullTimeMonths) ? max(0, $emp->prorate ?? 0) : 0;
                    $subsistenceTotal = $fullTimeService + $partTimeService;
                    $laundryTotal     = $laundryFull + $laundryProrate;
                    $grandTotal       = $subsistenceTotal + $laundryTotal;
                @endphp
                <tr>
                    <td>{{ $globalIndex }}</td>
                    <td>{{ $emp->employee_no ?? '' }}</td>
                    <td>{{ $emp->name }}</td>
                    <td>{{ $emp->position ?? '' }}</td>
                    <td>{{ $fullTimeDays }}</td>
                    <td>{{ number_format($fullTimeService, 2) }}</td>
                    <td>{{ $partTimeDays }}</td>
                    <td>{{ number_format($partTimeService, 2) }}</td>
                    <td>{{ $actualDays }}</td>
                    <td>{{ number_format($laundryFull, 2) }}</td>
                    <td>{{ number_format($laundryProrate, 2) }}</td>
                    <td>{{ number_format($subsistenceTotal, 2) }}</td>
                    <td>{{ number_format($laundryTotal, 2) }}</td>
                    <td>{{ number_format($grandTotal, 2) }}</td>
                    <td></td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>


    {{-- ── Signature block — only on the LAST page ── --}}
    @if ($chunkIndex === $totalChunks - 1)
    <table class="signature-table">
        <tbody>
            <tr>
                <td>
                    <p>CERTIFIED: Services have been duly rendered as stated above.</p>
                    <p class="spacer">{{ $signatories[0]['signatory_1'] ?? '' }}</p>
                    <p>{{ $signatories[0]['signatory_position_1'] ?? '' }}</p>
                    <p class="spacer">DATE: _____________________</p>
                </td>
                <td>
                    <p>CERTIFIED:
                        <span class="checkbox-box">&nbsp;</span>
                        Allotment obligated for the purpose as indicated above.<br>
                        <span class="checkbox-box">&nbsp;</span>
                        Supporting documents complete
                    </p>
                    <p class="spacer">{{ $signatories[0]['signatory_2'] ?? '' }}</p>
                    <p>{{ $signatories[0]['signatory_position_2'] ?? '' }}</p>
                    <p class="spacer">DATE: _____________________</p>
                </td>
                <td>
                    <p>CERTIFIED: Funds available.</p>
                    <p class="spacer">{{ $signatories[0]['signatory_3'] ?? '' }}</p>
                    <p>{{ $signatories[0]['signatory_position_3'] ?? '' }}</p>
                    <p class="spacer">DATE: _____________________</p>
                </td>
                <td>
                    <p><strong>APPROVED FOR PAYMENT:</strong></p>
                    <p class="spacer">{{ $signatories[0]['signatory_4'] ?? '' }}</p>
                    <p>{{ $signatories[0]['signatory_position_4'] ?? '' }}</p>
                    <p class="spacer">DATE: _____________________</p>
                </td>
                <td>
                    <p><strong>CERTIFIED:</strong> each employee whose name appears above</p>
                    <p class="spacer">{{ $signatories[0]['signatory_5'] ?? '' }}</p>
                    <p>{{ $signatories[0]['signatory_position_5'] ?? '' }}</p>
                    <p class="spacer">DATE: _____________________</p>
                </td>
            </tr>
        </tbody>
    </table>
    @endif

</div>{{-- end .page-block --}}

@endforeach

</body>
</html>