<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loyalty Award Report</title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            margin: 5px;
            padding: 5px;
            text-align: center;
        }

        table {
            width: 100%;
            font-family: Georgia;
            border-collapse: collapse;
            border: 2px solid black;
            font-size: 11px;
            margin-bottom: 80px;
        }

        th,
        td {
            border: 1px solid black;
            padding: 5px;
            text-align: left;
        }

        th {
            text-align: center;
            /*
            .page {
                page-break-after: always;
            } */
        }


        .title {
            font-size: 13px;
            font-family: 'Arial Black', sans-serif;
            font-weight: bold;
            margin-top: -10px;
        }

        .subtitle {
            font-size: 11px;
            font-weight: normal;
            margin-top: -10px;
            text-align: left;
        }

        .note {
            margin-top: 20px;
            text-align: left;
            font-size: 11px;
        }


        .signatories {
            position: relative;
            border: none;
            margin-top: 20px;
            width: 100%;
        }

        .page-break {
            page-break-before: always;
        }

        .footer {
            position: fixed;
            text-align: center;
            left: 0;
            bottom: 20px;
            font-size: 10px;
            width: 100%;
            margin: 0px;
        }
    </style>
</head>

<body>
    @php
        $totaldata = $employees->count();
        $dataPerPage = $totaldata <= 16 ? 16 : 25;
        $chunks = $employees->chunk($dataPerPage);
        $totalPages = $chunks->count();
        $pages = 0;
        $number = 1;
    @endphp

    <h3 style="font-weight:normal; font-size:13px;">Republic of the Philippines<br>{{ strtoupper($companies[0]->name) }}
    </h3>
    <p class="title">LOYALTY INCENTIVE 2025</p>
    <p class="subtitle">
        General Form No.4 <br>Revised January 1922
        <span>We acknowledgement receipt of the sum shown opposite our name as full compensation <br>for service
            rendered for the period stated.
        </span>
    </p>

    @foreach ($chunks as $pageIndex => $dataChunk)
        <div style="text-align:right;margin-right:30px;font-size:11px; margin-bottom:10px;">Sheet
            <u>{{ $pages = $pageIndex + 1 }}</u> of <u>{{ $totalPages }}</u> Sheets
        </div>
        <table class="data" style="width: 100%">
            <thead>
                <tr>
                    <th rowspan="2">NO.</th>
                    <th rowspan="2">PIN <br>SL Code</th>
                    <th rowspan="2">Employee name <br> Description</th>
                    <th rowspan="2" style="width:16%;">POSITION</th>
                    <th colspan="3">LOYALTY INCENTIVE 2025</th>
                    <th rowspan="2">AMOUNT EARNED</th>
                    <th rowspan="2">No.</th>
                    <th rowspan="2">AMOUNT RECEIVED</th>
                    <th rowspan="2">SIGNATURE</th>
                </tr>
                <tr>
                    <th>No. of Years in Service</th>
                    <th>Loyalty Cash Incentive</th>
                    <th>(Token) <br> Cash Equivalent</th>
                </tr>
            </thead>
            <tbody>
                @php

                    $ctrs = 1;
                    $total_cash_award = 0;
                    $total_cash_token = 0;
                    $total_amount_received = 0;
                @endphp
                @foreach ($dataChunk as $index => $employees)
                    @php
                        $total_cash_award += $employees->cash_award;
                        $total_cash_token += $employees->cash_token;
                        $total_amount_received += $employees->amount_received;
                    @endphp
                    <tr>
                        <td style="width: 10px">{{ $number++ }}</td>
                        <td>{{ $employees->employee_no }}</td>
                        <td>{{ $employees->full_name }}</td>
                        <td>{{ $employees->position }}</td>
                        <td style="text-align: center">{{ $employees->years }}</td>
                        <td style="text-align: right">{{ number_format($employees->cash_award, 2, '.', ',') }}</td>
                        <td style="text-align: right">{{ number_format($employees->cash_token, 2, '.', ',') }}</td>
                        <td style="text-align: right">{{ number_format($employees->amount_received, 2, '.', ',') }}
                        </td>
                        <td style="text-align:center">{{ $ctrs++ }}</td>
                        <td style="text-align: right">{{ number_format($employees->amount_received, 2, '.', ',') }}
                        </td>
                        <td></td>
                    </tr>
                @endforeach

                {{-- Spacer Row (No Borders) --}}
                <tr style="border: none; font-weight:bold;">
                    <td colspan="5" style="border: none; ">GRAND TOTAL</td>\<td
                        style="text-align: right;border: none;">{{ number_format($total_cash_award, 2, '.', ',') }}
                    </td>
                    <td style="text-align: right;border: none;">{{ number_format($total_cash_token, 2, '.', ',') }}
                    </td>
                    <td style="text-align: right;border: none;">
                        {{ number_format($total_amount_received, 2, '.', ',') }}</td>
                    <td style="border: none;"></td>
                    <td style="text-align: right;border: none;">
                        {{ number_format($total_amount_received, 2, '.', ',') }}</td>
                    <td style="border: none;"></td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <p>Page
                {{ $pages = $pageIndex + 1 }} of {{ $totalPages }}</p>
        </div>
        @if ($pages < $totalPages - 1)
            <div class="page-break"></div>
        @endif
    @endforeach
    <div class="signatories">
        <table style="border:none; width:100%;">
            <tr>
                <td style="text-align:center; border:none; width:20%">CERTIFIED: Services have been duly
                    rendered as stated above.</td>
                <td style=" border:none; width:20% ">CERTIFIED: <input type="checkbox"> Allotment obligated
                    for the purpose as indicated above.<br>
                    <input type="checkbox"> Supporting documents complete.
                </td>
                <td style="border:none;  width:20%">CERTIFIED: Funds available.</td>
                <td style="border:none; width:20% ">APPROVED FOR PAYMENT:</td>
                <td style=" border:none; width:20% ">CERTIFIED: Each employee whose names appear above has
                    been paid the amount opposite his/her name.</td>
            </tr>

            <tr>

                <td style="border:none; padding-top:40px">
                    <b>Name & Signature of Supervisor:</b><br><br>
                    <b>{{ $signatories[0]->signatory_1 }}</b><br>{{ $signatories[0]->signatory_position_1 }}<br><br>
                </td>
                <td style="border:none; padding-top:45px">
                    <b>{{ $signatories[0]->signatory_2 }}</b><br>{{ $signatories[0]->signatory_position_2 }}<br><br>
                </td>
                <td style="border:none; padding-top:40px">
                    <b>{{ $signatories[0]->signatory_3 }}</b><br>{{ $signatories[0]->signatory_position_3 }}<br><br>
                </td>
                <td style="border:none; padding-top:45px">
                    <b>{{ $signatories[0]->signatory_4 }}</b><br>{{ $signatories[0]->signatory_position_4 }}<br><br>
                </td>
                <td style="border:none; padding-top:15px">
                    <b>{{ $signatories[0]->signatory_5 }}</b><br>{{ $signatories[0]->signatory_position_5 }}
                </td>
            </tr>
            <tr>
                <td style="border:none;">Date:_____________________
                </td>
                <td style="border:none;">Date:_____________________
                </td>
                <td style="border:none;">Date:_____________________
                </td>
                <td style="border:none;">Date:_____________________
                </td>
                <td style="border:none;">Date:_____________________
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
