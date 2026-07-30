<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Employee Certificates</title>

    <style>
        @page {
            margin: 0;
            size: A4;
        }
        html,
        body {
            height: 297mm;
            width: 210mm;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            position: relative;
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 150px;
            text-align: center;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 130px;
            text-align: center;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .content {
            margin-top: 160px;
            margin-bottom: 140px;
            padding: 0 20px;
            min-height: calc(100vh - 300px);
        }

        p {
            padding-bottom: 9px;
            margin: 0;
            font: normal 12pt Arial, sans-serif;
        }

        td {
            font: normal 12pt Arial, sans-serif;
        }
    </style>
</head>

<body>
    <header>
        @if(!empty($headerImg))
            <img src="{{ $headerImg }}" alt="Header" style="display: block; max-height: 140px; width: auto; max-width: 100%; object-fit: contain; margin: 0 auto;">
        @endif
    </header>

    <footer>
        @if(!empty($footerImg))
            <img src="{{ $footerImg }}" alt="Footer" style="max-width: 100%; max-height: 120px; width: auto; height: auto; object-fit: contain; display: block; margin: 0 auto;">
        @endif
    </footer>

    <!-- Main content -->
    @foreach ($employees as $employee)
        <div class="content">
            <div style="margin-left: 1in;margin-right: 1.5in; padding-top: 0.3in;">
                {{-- <div style="padding-left: 10px; padding-top: 15px; position: absolute;">
                    <img src="data:image/png;base64,{{ $image }}" width="70" height="70">
                </div>
                <div style="text-align: center;padding-top: 15px; padding-bottom: 30px;">
                    <p>Republic of the Philippines</p>
                    <h4 style="margin: 0;padding: 0;">{{ isset($companies[0]->name) ? $companies[0]->name : '' }}
                    </h4>
                    <p><i>{{ isset($companies[0]->address) ? $companies[0]->address : '' }}</i></p>
                </div> --}}
                <div>
                    <h2 style="text-align: center;padding-bottom: 0.5in;margin: 0; font: normal 12pt Arial, sans-serif; font-weight: bold;">
                        <u>CERTIFICATE OF EMPLOYMENT</u>
                    </h2>
                </div>
                <div>
                    <p
                        style="text-indent: 0.5in;text-align: justify; font: normal 12pt Arial, sans-serif; line-height: 1.8; margin-bottom: 15px;">
                        This is to certify that <strong>{{ strtoupper($employee->name) }}</strong>, is a {{ strtolower($employee->employment_type) }} employee of the <b> {{ $orgCompanyName }} ({{ $orgBranchCode }})</b>, an attached agency of the <b> Department of Trade and Industry (DTI) </b> since <b>{{ date('F d, Y', strtotime($employee->date_hired)) }}, up to present.</b> {{ $employee->gender_id == 1 ? 'She' : 'He' }} is currently holding the position of <b> {{ $employee->position }}@if(!empty($employee->salary_grade)) with SG {{ $employee->salary_grade }}@endif under the {{ $employee->department }}.</b>
                    </p>
                    <p
                        style="text-indent: 0.5in;text-align: justify; font: normal 12pt Arial, sans-serif; line-height: 1.8; margin-bottom: 15px;">
                        This certification is being issued upon the request of {{ $employee->name_sig }} {{ $purpose_text ?? 'as a confirmation of ' . ($employee->gender_id == 1 ? 'her' : 'his') . ' employment with the Center and as a requirement for ' . ($employee->gender_id == 1 ? 'her' : 'his') . ' personal travel abroad' }}. The accuracy of this document may verify by emailing the Human Resource Section at {{ $orgCompanyEmail }}.
                    </p>
                </div>
                <br>
                <div>
                    @php
                        $issueDate = \Carbon\Carbon::now();
                        $day = $issueDate->format('j');
                        $suffix = $day == 1 || $day == 21 || $day == 31 ? 'st' : ($day == 2 || $day == 22 ? 'nd' : ($day == 3 || $day == 23 ? 'rd' : 'th'));
                    @endphp
                    <p
                        style="text-indent: 0.5in;text-align: justify; font: normal 12pt Arial, sans-serif; line-height: 1.8;">
                        Issued this {{ $day }}{{ $suffix }} day of {{ $issueDate->format('F Y') }} at {{ $orgCompanyAddress }}
                    </p>
                </div>
                <div style="padding-top: 1in;float: right;">
                    <p style="text-align: center; padding:0; font: normal 12pt Arial, sans-serif; "><b>{{ $signatories['signatory'] }}</b></p>
                    <p style="text-align: center; padding:0; font: normal 12pt Arial, sans-serif;">{{ $signatories['position'] }}</p>
                </div>
            </div>
        </div>
    @endforeach
</body>

</html>
