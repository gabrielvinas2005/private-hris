<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Employment (COS)</title>
    <style>
        @page {
            margin: 0;
            size: A4;
        }
        html, body {
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
            height: 140px;
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
            height: 120px;
            text-align: center;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .content {
            margin-top: 150px;
            margin-bottom: 130px;
            padding: 0 1in;
            min-height: calc(100vh - 280px);
        }
        p {
            margin: 0 0 10px 0;
            font: normal 12pt Arial, sans-serif;
            text-align: justify;
        }
        .title {
            text-align: center;
            letter-spacing: 4px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .line {
            margin: 0 0 20px 0;
            border-bottom: 1px solid #000;
        }
        .indent {
            text-indent: 0.5in;
            line-height: 1.6;
        }
        .salary-emphasis {
            font-weight: bold;
        }
        .signature {
            margin-top: 50px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <header>
        @if(!empty($headerImg))
            <img src="{{ $headerImg }}" alt="Header" style="display: block; max-height: 130px; width: auto; max-width: 100%; object-fit: contain; margin: 0 auto;">
        @endif
    </header>

    <footer>
        @if(!empty($footerImg))
            <img src="{{ $footerImg }}" alt="Footer" style="display: block; max-height: 110px; width: auto; max-width: 100%; object-fit: contain; margin: 0 auto;">
        @endif
    </footer>

    @foreach ($employees as $employee)
        @php
            $title = $employee->gender_id == 1 ? 'MS.' : 'MR.';
        @endphp
        <div class="content">
            <h3 class="title">C E R T I F I C A T I O N</h3>
            <div class="line"></div>

            <p class="indent">
                This is to certify that <strong>{{ $title }} {{ strtoupper($employee->name) }}</strong> has been hired as a Contract of Service (COS) by the <b>{{ $orgCompanyName }} ({{ $orgBranchCode }}), an attached agency of the Department of Trade and Industry (DTI).</b>
            </p>

            <p class="indent">
                {{ $employee->gender_id == 1 ? 'She' : 'He' }} currently holds the position of <strong>{{ $employee->position }}</strong> under the <strong>{{ $employee->department }}</strong> since <b>{{ date('F d, Y', strtotime($employee->date_hired)) }} up to present.</b> {{ $employee->gender_id == 1 ? 'She' : 'He' }} remains engaged with the Center based on performance.
            </p>

            <p class="indent">
                @php
                    $monthlySalary = (float) ($employee->salary ?? 0);
                    $serviceFeeWords = ucwords(strtolower(\App\Helpers\NumberToWords::formatCurrency($monthlySalary)));
                    $serviceFeeFigures = 'P ' . number_format($monthlySalary, 2);
                    $serviceFeeDisplay = $serviceFeeWords . ' (' . $serviceFeeFigures . ')';
                @endphp
                {{ $employee->gender_id == 1 ? 'She' : 'He' }} receives a monthly Service Fee of <strong class="salary-emphasis" style="font-weight: bold;">{{ $serviceFeeDisplay }}</strong>.
            </p>

            <p class="indent">
                This certification is being issued upon the request of {{ $title }} {{ $employee->name_sig }} {{ $purpose_text ?? 'as a confirmation of ' . ($employee->gender_id == 1 ? 'her' : 'his') . ' engagement with the Center and as a requirement for ' . ($employee->gender_id == 1 ? 'her' : 'his') . ' personal travel abroad' }}. The accuracy of this document may be verified by emailing the Human Resource Section at <b>{{ $orgCompanyEmail }}.</b>
            </p>

            @php
                $issueDate = \Carbon\Carbon::now();
                $day = $issueDate->format('j');
                $suffix = $day == 1 || $day == 21 || $day == 31 ? 'st' : ($day == 2 || $day == 22 ? 'nd' : ($day == 3 || $day == 23 ? 'rd' : 'th'));
            @endphp
            <p class="indent">
                Issued this {{ $day }}{{ $suffix }} day of {{ $issueDate->format('F Y') }} at {{ $orgCompanyAddress }}
            </p>

            <div style="margin-top: 60px; width: 100%; text-align: right;">
                <div style="display: inline-block; text-align: right;">
                    <p style="margin: 0 0 4px 0; font: normal 12pt Arial, sans-serif; font-weight: bold;">{{ $signatories['signatory'] }}</p>
                    <p style="margin: 0; font: normal 12pt Arial, sans-serif;">{{ $signatories['position'] }}</p>
                </div>
            </div>
        </div>
    @endforeach
</body>

</html>

