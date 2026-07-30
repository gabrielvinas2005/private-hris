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

    <title>Travel Authority Unofficial</title>

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
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        td,
        th {
            border: 1px solid black;
            padding: 8px;
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
    @php
        use App\Helpers\CompanyHelper;

        $companyName = CompanyHelper::getName();
        $companyAddress = CompanyHelper::getAddress();
        $companyLogo = CompanyHelper::getLogoBase64() ?? ($image ?? null);
    @endphp
    <div class="header">
        @if ($companyLogo)
        <div style="padding-left: 5px; position: absolute;">
            <img src="data:image/jpeg;base64,{{ $companyLogo }}" width="100" height="100" style="object-fit: contain;">
        </div>
        @elseif (!empty($image))
        <div style="padding-left: 5px; position: absolute;">
            <img src="data:image/png;base64,{{ $image }}" width="100" height="100">
        </div>
        @endif
        <div style="text-align: center;">
            @if ($companyName !== '')
            <p style="margin: 8px;"><strong>{{ $companyName }}</strong></p>
            @endif
            @if ($companyAddress !== '')
            <p style="margin-top: -10px;padding: 0;">{!! nl2br(e($companyAddress)) !!}</p>
            @endif
        </div>
    </div>
    <br>

    <p style="margin-top: 5px; margin-left: 50px; margin-right: 50px; border-bottom:  3px solid transparent; width:85%;"></p>

    <div class="container">
        @foreach ($ob as $ob)
        <table>
            <tr>
                <th colspan="2" style="text-align: center;">
                    <p style="float: right; border: 1px solid;">CONTROL NO. <br>
                        001-01-2024
                    </p><span>Travel Authority for<br>Unofficial (Personal) Travel</span>
                </th>
            </tr>
            <tr>
                <th>NAME</th>
                <td>{{ $ob->name}}</td>
            </tr>
            <tr>
                <th>Position/ Designation</th>
                <td>{{ $ob->position}}</td>
            </tr>
            <tr>
                <th>Permanent Station</th>
                <td>{{ $ob->department}}</td>
            </tr>
            <tr>
                <th>Purpose of Travel</th>
                <td>{{ $ob->purpose}}</td>
            </tr>
            <tr>
                <th>Activity Organized/ Sponsored By</th>
                <td>{{ $ob->client}}</td>
            </tr>
            <tr>
                <th>Period Covered</th>
                <td>
                    {{ \Carbon\Carbon::parse($ob->date_time_from)->format('F j') }} -
                    {{ \Carbon\Carbon::parse($ob->date_time_to)->format('j, Y') }}
                </td>
            </tr>
            <tr>
                <th>Fund Source</th>
                <td>{{ $ob->funds}}</td>
            </tr>
            <tr>
                <th colspan="2">
                    <p style="margin-top: -11px;">I hereby attest that the information in this form and in the supporting documents attached are true and correct.</p>
                    <br><br>
                    <p><strong>{{ $ob->name}}</strong><br>
                        {{ $ob->position}}<br>
                        Name and Signature of Requesting Employee
                    </p>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <p style="margin-top: -11px;">RECOMMENDING APPROVAL:</p>
                    <br><br>
                    <p><strong>{{ $ob->recommending_approval}}</strong><br>
                        {{ $ob->recommending_position}}<br>
                        Name and Signature of Recommending Authority
                    </p>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <p style="margin-top: -11px;">APPROVED:</p>
                    <br><br>
                    <p><strong>{{ $ob->approver}}</strong><br>
                        {{ $ob->approver_position}}<br>
                        Name and Signature of Approving Authority
                    </p>
                </th>
            </tr>
        </table>
    </div>
    @endforeach



</body>

</html>