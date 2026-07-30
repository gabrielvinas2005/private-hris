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

    <title>Acceptance of Retirement</title>

    <style>
        html,
        body {
            height: 297mm;
            width: 210mm;
            margin-top: 15px;
            margin-bottom: 15px;
            margin-left: auto;
            margin-right: auto;
            text-align: justify;
        }

        p {
            padding: 0;
            margin: 0;
        }
    </style>
</head>

<body>
    <!-- Main content -->
    @foreach ($employees as $retirement)
        <div class="p-4 card">
            <div style="margin-left: 1.5in;margin-right: 1.5in;">
                <div>
                    <p style="font: 12px, arial;"><i>CS Form No. 10</i></p>
                    <p style="font: 12px, arial;"><i>Series of 2017</i></p>
                </div>
                <div>
                    <p style="padding-top: 40px;text-align: center; font:  15px, arial;">Republic of the Philippines</p>
                    <p style="padding-top:-10px;text-align: center; font:  15px, arial;">{{ $companies[0]->name }}</p>
                </div>
                <div>
                    <p style="padding-top: 40px;padding-bottom: 20px;text-align: center; font: 18px, arial;">
                        ACCEPTANCE OF RETIREMENT</p>
                </div>
                <div style="text-align: right;">
                    <p style="padding-top: 20px;padding-bottom: 20px;font: 14px, arial;"></p>
                        {{ date('F d, Y', strtotime(now())) }}
                    </p>
                </div>
                <div style="text-align:left; font: 14px, arial;">
                    <p><b>{{ strtoupper($retirement->name_prefix) }} {{ strtoupper($retirement->name) }} </b>
                    </p>
                    <p>{{ $retirement->pa_house_no == null ? '' : $retirement->pa_house_no . ', ' }}{{ $retirement->pa_village == null ? '' : $retirement->pa_village . ', ' }}{{ $retirement->pa_street == null ? '' : $retirement->pa_street . ', ' }}{{ 'Brgy. ' . $address['pa_brgy'] }},
                                {{ $address['pa_city'] }}, {{ $address['pa_province'] }},
                                {{ $address['pa_region'] }}</p><br>
                </div>
                <div>
                    <div>
                        <p style="text-align:left; font: 14px, arial; margin-top: 20px;">
                            @php
                                $rawGender = strtolower((string) ($retirement->gender ?? ''));
                                $isFemale = ($retirement->gender_id ?? null) == 1 || $rawGender === 'female';
                                $title = $isFemale ? 'Ms.' : 'Mr.';
                                $surname = trim((string) ($retirement->last_name ?? ''));
                                if ($surname === '') {
                                    $nameParts = preg_split('/\s+/', trim((string) ($retirement->name ?? '')));
                                    $surname = !empty($nameParts) ? end($nameParts) : '';
                                }
                            @endphp
                            {{ $title }} {{ strtoupper($surname) }}:<br><br>
                        <p style="text-align: justify ;text-indent: 40px; line-height: 1.8; font: normal 13px, arial;">
                            In reply to your letter dated <u><b>{{ date('F d, Y', strtotime($signatories['date'])) }}</b></u>
                            tendering your
                            retirement from the position of <u><b>{{ $retirement->position ?? ' ' }}</b></u> in
                            <u><b>{{ $retirement->department ?? ' ' }},</b></u> may I inform you that the same is hereby
                            accepted to
                            take effect on
                            <u><b>{{ date('F d, Y', strtotime($signatories['effectivity_date'] ?? $retirement->date_effectivity ?? '')) }}.</b></u>
                        </p>
                    </div>
                    <br>
                    {{-- <div>
                        <p style="text-align: justify ;text-indent: 40px; line-height: 1.8; font: normal 13px, arial;">
                            Your services while employed from this Office have been rated as
                            <u>{{ $retirement->adjectival_rating ?? ' ' }}</u>
                            for your reference.
                        </p>
                    </div> --}}
                    <br>

                    <div style="padding-top: 30px;float: right;">
                        <p>Very truly yours,</p><br><br>
                        <br>
                        <p style="text-align: center; ">
                            <u><b>{{ $signatories['signatory'] }}</b></u>
                        </p>
                        <p style="text-align: center; ">{{ $signatories['position1'] }}</p>
                    </div>
                    <div style="padding-top: 200px;">
                        <label style="padding-bottom: 50px;padding-left: 0; width:10px ">Received By:</label>
                        <u><b>{{ $signatories['received_signatory'] }}</b></u>
                        <p style="text-align: justify; padding-left:90px;font: normal 11px, arial; ">Signature over
                            Printed Name</p>
                        <label style="padding: 40 ">Date:
                            <u>{{ date('F d, Y', strtotime($signatories['received_date'])) }}</u></label>
                        <br>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</body>

</html>

