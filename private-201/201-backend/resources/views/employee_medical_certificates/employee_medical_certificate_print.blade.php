<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Certificate</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
        }

        p {
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid black;
            padding: 5px;
            text-align: left;
        }

        .header,
        .instructions,
        .section-title {
            text-align: center;
        }

        .instructions {
            font-size: 14px;
        }

        .section-title {
            margin-top: 20px;
        }

        .input-field {
            width: 100%;
            border: none;
            border-bottom: 1px solid black;
            outline: none;
        }
    </style>
</head>

<body>
    <div>
        <p>CS Form No. 211<br>Revised 2018</p>
    </div>
    <table>
        <tr>
            <th colspan="2" class="header"
                style="font-size:16px;border-top: none; border-right:none; border-left:none; border-bottom: 3px solid black;">
                MEDICAL
                CERTIFICATE<br>(For Employment)</th>
        </tr>
        <tr>
            <td colspan="2" style="border-right:none; border-left:none;">
                <div>
                    <ol type="a">
                        <h3 style="text-align: center; margin-top: -10px;">I N S T R U C T I O N S</h3>
                        <li>This medical certificate should be accomplished by a licensed government physician.</li>
                        <li>Attach this certificate to original appointment, transfer, and reemployment.</li>
                        <li>The results of the following pre-employment medical/physical/psychological must be attached
                            to
                            this form:
                            <ul style="list-style: none;margin-left: 30%;">
                                <li>Blood Test</li>
                                <li>Urinalysis</li>
                                <li>Chest X-Ray</li>
                                <li>Drug Test</li>
                                <li>Psychological Test</li>
                                <li>Neuro-Psychiatric Examination (if applicable)</li>
                            </ul>
                        </li>
                    </ol>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2" class="section-title"
                style="border-right:none; border-left:none; border-top: 3px solid black; border-bottom:none;">FOR THE
                PROPOSED APPOINTEE</td>
        </tr>
    </table>
    <table>
        @foreach ($employees as $employee)
        <tr>
            <td colspan="3">
                <p style="font-size: 12px;margin-top: 0;">NAME (Last Name, First Name, Name Extension (if any), and
                    Middle Name)
                </p>
                <p style="font-size: 16px;font-weight: bold;margin:15px 0px 0px 0px;">{{ $employee->name }}</p>
            </td>
            <td rowspan="2">
                <p style="margin-top: -40px; text-align:center;">AGENCY / ADDRESS</p>
                <p style="font-size: 16px;font-weight: bold;margin:15px 0px 0px 0px;">{{ $employee->company }}</p>
            </td>
        </tr>
        <tr>
            <td colspan="3"><span>ADDRESS</span>
                <p style="font-size: 16px;font-weight: bold;margin:15px 0px 0px 0px;">{{ $employee->address }}</p>
            </td>
        </tr>
        <tr>
            <td>AGE
                <p style="font-size: 16px;font-weight: bold;margin:15px 0px 0px 0px;">{{ $employee->age }}</p>
            </td>
            <td>SEX
                <p style="font-size: 16px;font-weight: bold;margin:15px 0px 0px 0px;">{{ $employee->gender }}</p>
            </td>
            <td>CIVIL STATUS
                <p style="font-size: 16px;font-weight: bold;margin:15px 0px 0px 0px;">{{ $employee->civil_status }}
                </p>
            </td>
            <td>PROPOSED POSITION
                <p style="font-size: 16px;font-weight: bold;margin:15px 0px 0px 0px;">{{ $employee->position }}</p>
            </td>
        </tr>
        <tr>
            <td colspan="4" class="section-title" style="border-right:none; border-left:none;border-top:none;">
                FOR THE LICENSED GOVERNMENT PHYSICIAN</td>
        </tr>
    </table>
    <table>
        <tr>
            <td colspan="7">
                <p>I hereby certify that I have reviewed and evaluated the attached examination results, personally
                    examined the above-named individual and found him/her to be physically and medically FIT/UNFIT for
                    employment.</p>
            </td>
        </tr>
        <tr>
            <td colspan="4">
                <span style="font-size: 12px;">SIGNATURE over PRINTED NAME OF LICENSED GOVERNMENT PHYSICIAN: </span>
                <p></p>
            </td>
            <td rowspan="2" colspan="3">
                <p style="margin-top: -40px; text-align:center;">OTHER INFORMATION ABOUT THE PROPOSED APPOINTEE
                </p>
                <p></p>
            </td>
        </tr>
        <tr>
            <td colspan="4"><span style="font-size: 12px;">AGENCY/Affiliation of Licensed Government Physician:
                </span>
                <p></p>
            </td>
        </tr>
        <tr>
            <td colspan="4"><span style="font-size: 12px;">LICENSE NO.</span>
                <p></p>
            </td>
            <td><span style="font-size: 12px;">HEIGHT (M)</span>
                <p></p>
            </td>
            <td><span style="font-size: 12px;">WEIGHT (KG)</span>
                <p></p>
            </td>
            <td><span style="font-size: 12px;">BLOOD TYPE</span>
                <p></p>
            </td>/p>
            </td>
        </tr>
        <tr>
            <td colspan="4"><span style="font-size: 12px;">OFFICIAL DESIGNATION</span>
                <p></p>
            </td>
            <td colspan="3"><span style="font-size: 12px;">DATE EXAMINED:</span>
                <p></p>
            </td>
        </tr>
    </table>
    @endforeach
</body>

</html>