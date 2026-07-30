<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Offboarding Certificates</title>

    <style>
        html,
        body {
            height: 297mm;
            width: 210mm;
            margin-top: 15px;
            margin-bottom: 15px;
            margin-left: auto;
            margin-right: auto;
            /* background-color: gray; */
        }

        p {
            padding-bottom: 9px;
            margin: 0;
            font: normal 18px "Times", arial;
        }

        td {
            font: normal 18px "Times", arial;
        }
    </style>
</head>

<body>
    <!-- Main content -->
    @foreach ($employees as $employee)
        <div class="card p-4">
            <div style="margin-left: 1in;margin-right: 1in; padding-top: 1.5in;">
                {{-- <div style="padding-left: 20px; padding-top: 15px; position: absolute;">
                <img src="data:image/png;base64,{{ $image }}" width="70" height="70">
            </div>
            <div style="text-align: center;padding-top: 15px; padding-bottom: 30px;">
                <p>Republic of the Philippines</p>
                <h4 style="margin: 0;padding: 0;">{{ isset($companies[0]->name) ? $companies[0]->name : '' }}
                </h4>
                <p><i>{{ isset($companies[0]->address) ? $companies[0]->address : '' }}</i></p>
            </div> --}}
                <div>
                    <h2
                        style="text-align: center; padding-top: 50px; padding-bottom: 0.5in;margin: 0; font: normal 24px Times, arial;">
                        C E R T I F I C A T I O N</h2>
                </div>
                <div>
                    <p style="padding-bottom: 0.1in; font: normal 18px Times, arial;">TO WHOM IT MAY CONCERN:</p><br>
                    <p
                       style="text-indent: 0.5in;text-align: justify; font: normal 18px Times, arial; line-height: 1.8;">
                        This is to certify that according to our records, {{ $employee->name }},
                        {{ $employee->position }} ({{ $employee->department }})
                        has been in the service since {{ date('M d, Y', strtotime($employee->date_hired)) }}.
                        {{ $employee->gender_id == 1 ? 'Her' : 'His' }} last day of service was on
                        @if (isset($employee->retirement_date))
                            {{ date('M d, Y', strtotime($employee->effectivity)) }} and compulsorily retired on
                            {{ date(
                                'M
                                                                                                                                                                                                                                                                                                                d, Y',
                                strtotime($employee->retirement_date),
                            ) }}.
                        @else
                            {{ date('M d, Y', strtotime($employee->effectivity)) }}.
                        @endif
                    </p>
                </div>
                <br>
                <div>
                    <p
                       style="text-indent: 0.5in;text-align: justify; font: normal 18px Times, arial; line-height: 1.8;">
                        {{ $orgCompanyAddress }}, this
                        {{ date('d', strtotime(now())) }} day
                        {{ date('F, Y', strtotime(now())) }}.</p>
                </div>
                <div style="padding-top: 1in;float: right;">
                    <p style="text-align: center; padding:0; font: bold 18px Times, arial;">
                        <b>{{ $signatories['signatory'] }}</b>
                    </p>
                    <p style="text-align: center; padding:0;">{{ $signatories['position'] }}</p>
                </div>
            </div>
        </div>
        {{-- <p style="text-align: right;margin-bottom: 0px;margin-right: 20px; font-weight: bold;margin-top: 400px;">
            {{ $footer['document_no'] }}
        </p>
        <p style="text-align: right;margin-top: 0px;margin-right: 20px;font-weight: bold; position:relative ;">
            {{ $footer['revision'] }}
        </p> --}}
    @endforeach
</body>

</html>
