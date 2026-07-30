<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceptance Letter - Intern</title>
    <style>
        @page {
            margin: 80px 60px 120px 60px;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
        }

        header {
            position: fixed;
            top: -50px;
            left: 0;
            right: 0;
            height: 100px;
            text-align: center;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        footer {
            position: fixed;
            bottom: -90px;
            left: 0;
            right: 0;
            height: 120px;
            text-align: center;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .content {
            margin-top: 120px;
        }

        .mb-6 {
            margin-bottom: 24px;
        }

        .mb-5 {
            margin-bottom: 20px;
        }

        .mb-4 {
            margin-bottom: 16px;
        }

        .mb-3 {
            margin-bottom: 12px;
        }

        .mb-2 {
            margin-bottom: 8px;
        }

        .mb-1 {
            margin-bottom: 4px;
        }

        .text-bold {
            font-weight: bold;
        }

        .list {
            margin-left: 18px;
        }

        .list li {
            margin-bottom: 4px;
        }

        .students-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }

        .students-table td {
            padding: 2px 4px;
            vertical-align: top;
            font-weight: bold;
        }

        .indent {
            text-indent: 24px;
        }
    </style>
</head>

<body>
    <header>
        @if (!empty($header_img))
            <img src="{{ $header_img }}" alt="Header"
                style="display: block; margin-bottom: 5px; max-height: 140px; width: auto; max-width: 100%; object-fit: contain;">
        @endif
    </header>

    <footer>
        @if (!empty($footer_img))
            <img src="{{ $footer_img }}" alt="Footer"
                style="max-width: 100%; max-height: 120px; width: auto; object-fit: contain;">
        @endif
    </footer>

    <div class="content">
        @php
            $formatted_start = $date_of_start
                ? \Carbon\Carbon::createFromFormat('Y-m-d', $date_of_start)->format('d F Y')
                : '';
            $formatted_letter = $letter_date
                ? \Carbon\Carbon::createFromFormat('Y-m-d', $letter_date)->format('d F Y')
                : now()->format('d F Y');
        @endphp
        <p class="mb-5">{{ $formatted_letter }}</p>

        <p class="mb-1 text-bold">{{ strtoupper($school_officer ?? '') }}</p>
        <p class="mb-1">{{ $school_name ?? '' }}</p>
        @if (!empty($school_address))
            <p class="mb-4">{{ $school_address }}</p>
        @else
            <p class="mb-4">{{ $school_city ?? '' }}</p>
        @endif

        <p class="mb-4">Dear <b>{{ $salutation ?? ($school_officer ?? 'Sir/Madam') }}</b>,</p>

        <p class="mb-2">Greetings from the DTI – {{ $orgCompanyName }}!</p>

        <p class="mb-2 indent">
            We are pleased to inform you that your school’s <b>{{ $school_name ? '(' . $school_name . ')' : '' }}
            </b>internship application for the following recommended students of <b>{{ $course_program ?? '' }}</b> have been approved and accepted as
            Student-Intern by our Agency. The Face-to-Face internship shall commence on
            <strong>{{ $formatted_start }}</strong> and will be reporting for work on a Face-to-Face setup.
        </p>

        @php
            $students = $students ?? [];
            $left = [];
            $right = [];
            foreach ($students as $i => $s) {
                if ($i % 2 === 0) {
                    $left[] = $s;
                } else {
                    $right[] = $s;
                }
            }
            $maxRows = max(count($left), count($right));
        @endphp

        <table class="mb-4 students-table">
            @for ($i = 0; $i < $maxRows; $i++)
                <tr>
                    <td style="width: 30px; text-align: right;">
                        @if (isset($left[$i]))
                            {{ $i * 2 + 1 }}.
                        @endif
                    </td>
                    <td>
                        @if (isset($left[$i]))
                            {{ strtoupper(($left[$i]['first_name'] ?? '') . ' ' . ($left[$i]['middle_name'] ?? '') . ' ' . ($left[$i]['last_name'] ?? '')) }}
                        @endif
                    </td>
                    <td style="width: 30px; text-align: right;">
                        @if (isset($right[$i]))
                            {{ $i * 2 + 2 }}.
                        @endif
                    </td>
                    <td>
                        @if (isset($right[$i]))
                            {{ strtoupper(($right[$i]['first_name'] ?? '') . ' ' . ($right[$i]['middle_name'] ?? '') . ' ' . ($right[$i]['last_name'] ?? '')) }}
                        @endif
                    </td>
                </tr>
            @endfor
        </table>

        <p class="mb-2">The following Internship Program requirements have been received by the Center’s HR
            Office:</p>
        <ul class="mb-4 list">
            <li>Official Endorsement from the University/School (e.g., Dean, International Studies Department) addressed
                to {{ $orgBranchCode }}'s Head of the Agency</li>
            <li>Resume with an updated picture</li>
            <li>Certificate of Good Moral Character from the University/School</li>
            <li>Certificate of Registration</li>
        </ul>

        <p class="mb-2">
            In line with the requirements for the <b> Internship Program</b> in the Center, kindly submit the pertinent
            documents upon successful onboard, which are as follows:
        </p>
        <ul class="mb-5 list">
            <li>
                Practicum Agreement – format will be given by DTI-{{ $orgBranchCode }} upon successful onboard and we
                would need details of the <b> Practicum Adviser – Full Name and contact information (email address)
                </b>for us to prepare this agreement. This agreement must be agreed upon by both parties upon submission
                of the rest of the requirements <b><u> in lieu </u> </b> of the Memorandum of Agreement. To be
                established during
                the first two weeks of the student intern.
            </li>
        </ul>

        <p class="mb-6 text-bold">Congratulations and Welcome to {{ $orgBranchCode }}</p>

        <p class="mb-1 text-bold">{{ $signatory ?? 'MA FE J. AVILA' }}</p>
        <p>{{ $position ?? 'OIC Executive Director' }}</p>
    </div>
</body>

</html>
