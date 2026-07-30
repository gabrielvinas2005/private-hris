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

    <title>Travel Order</title>

    <style>
        html,
        body {
            height: 297mm;
            width: 210mm;
            margin-top: 0px;
            margin-bottom: 0px;
            margin-left: auto;
            margin-right: auto;
        }

        @media print {
            .print-header {
                position: fixed;
                top: 0;
                width: 100%;
                height: 100px;
                /* Adjust height based on your header */
            }

            body {
                margin-top: 120px;
                /* Prevent content from overlapping header */
            }
        }

        .container {
            width: 80%;
            margin: auto;
            padding: 0px;
        }

        .title {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
        }

        .sub-title {
            text-align: center;
            font-weight: bold;
            text-decoration: underline;
        }

        table {
            width: 80%;
            border-collapse: collapse;
            margin-top: 10%;
            margin-left: 10%;
            margin-right: auto;
        }

        td,
        th,
        tr {
            border: 1px solid black;
            padding: 20px;
            text-align: left;
        }

        th p {
            font-weight: normal;
            margin: 0;
            padding: 0;
        }


        .control-no {
            text-align: right;
            font-weight: bold;
        }

        .italic {
            font-style: italic;
        }

        .header {
            align-items: center;
            margin-top: 50px;
            margin-left: 100px;
        }
    </style>
</head>

<body>
    @include('header.print_header2')

    <p style="margin-top: 5px; margin-left: 50px; margin-right: 50px; border-bottom:  3px solid transparent; width:85%;"></p>

    <div class="container">
        @foreach ($ob as $ob)

        <div class="container">
            <div style="text-align: center;">
                <h2 style="margin-top:-14px">TRAVEL ORDER</h2>
                <p style="margin-top:-14px"><strong>TO Number:</strong> {{ session('print_number', mt_rand(100000, 999999)) }}</p>
                <p style="margin-top:-14px"><strong>Date:</strong> {{date('M d, Y', strtotime($ob->date))}}</p>
            </div>

            <p style="float: left; margin-left:-40px">TO: </p>
            <p>
                <strong>{{ $ob->name}}</strong> <br>
                {{ $ob->position}} <br>
                This Municipality
            </p>

            <p>You are hereby instructed to proceed to <strong>{{ $ob->client}}</strong> on <strong>
                    {{ \Carbon\Carbon::parse($ob->date_time_from)->format('F j') }} -
                    {{ \Carbon\Carbon::parse($ob->date_time_to)->format('j, Y') }}</strong> to attend:</p>

            <p><strong>{{ $ob->purpose}}</strong></p>

            <p>You will leave your station on <strong>{{date('M d, Y', strtotime($ob->date))}}</strong> and return immediately after completion of your mission.</p>

            <p>For this purpose, you shall likewise be entitled to the usual per diem and allowance including reimbursement of actual necessary expenses there to, subject to the usual accounting and auditing rules and regulations and subject to the availability of funds.</p>

            <p>Please be guided accordingly.</p>

            <p><strong>Recommending Approval:</strong></p>
            <p><strong>{{ $ob->recommending_approval}}</strong> <br>
                {{ $ob->recommending_position}}
            </p>

            <p><strong>Approved:</strong></p>
            <p><strong>{{ $ob->approver}}</strong> <br>
                {{ $ob->approver_position}}
            </p>

            <p><strong>CC:</strong></p>
            <ul>
                <li>File</li>
                <li>All concerned</li>
            </ul>
        </div>
    </div>
    @endforeach
    <div style="text-align: center;">
        <p><strong>ATTESTATION OF APPEARANCE</strong></p>
        <table class="table table-bordered table-striped table-hover" style="margin-top: -15px;">
            <thead>
                <tr>
                    <th>Office/Agency</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Signature above Printed Name of Office Representative</th>
                    <th>Telephone/ Contact. No.</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

    </div>

</body>

</html>

