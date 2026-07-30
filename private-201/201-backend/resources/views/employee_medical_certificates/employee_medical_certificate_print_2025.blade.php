<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Certificate</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

    </style>
</head>

<body>
    <div class="card p-4">
        <table>
            <tr>
                <td style="font-size: 12px; font-style: italic;">
                    <span style="font-weight: bold;">CS Form No. 211</span><br>
                    <span>Revised 2025</span>
                </td>
            </tr>

            <tr>
                <td style="text-align: center; padding: 20px 0;">
                    <span style="font-weight: bold; font-size: 19px;">MEDICAL CERTIFICATE</span><br>
                    <span style="font-size: 12px;">(For Employment)</span>
                </td>
            </tr>

            <tr>
                <td style="padding: 20px; text-align: center; border: 2px solid black; border-right: none; border-left: none;">
                    <span style="font-size: 15px;">I N S T R U C T I O N S</span>
                    <div style="font-size: 13px; text-align: left; width: 75%; margin: 0 auto; line-height: 1;">
                        <p style="margin: 5px 0;">a. This medical certificate should be accomplished by a licensed government physician.</p>
                        <p style="margin: 5px 0;">b. Attach this certificate to original appointment, transfer, and reemployment.</p>
                        <p style="margin: 5px 0; padding-left: 15px; text-indent: -15px;">c. The results of the following pre-employment medical/physical/psychological examinations must be attached to this form:</p>
                        <div style="padding-left: 30px; line-height: 1.1;">
                            <div style="margin-bottom: 0;  vertical-align: middle;">
                                <div style="width: 10px; height: 10px; border: 1px solid black; display: inline-block; vertical-align: middle;"></div> Blood Test
                            </div>
                            <div style="margin-bottom: 0;  vertical-align: middle;">
                                <div style="width: 10px; height: 10px; border: 1px solid black; display: inline-block; vertical-align: middle;"></div> Urinalysis
                            </div>
                            <div style="margin-bottom: 0;  vertical-align: middle;">
                                <div style="width: 10px; height: 10px; border: 1px solid black; display: inline-block; vertical-align: middle;"></div> Chest X-Ray
                            </div>
                            <div style="margin-bottom: 0;  vertical-align: middle;">
                                <div style="width: 10px; height: 10px; border: 1px solid black; display: inline-block; vertical-align: middle;"></div> Drug Test
                            </div>
                            <div style="margin-bottom: 0;  vertical-align: middle;">
                                <div style="width: 10px; height: 10px; border: 1px solid black; display: inline-block; vertical-align: middle;"></div> Psychological Examination
                            </div>
                            <div style="margin-bottom: 0;  vertical-align: middle;">
                                <div style="width: 10px; height: 10px; border: 1px solid black; display: inline-block; vertical-align: middle;"></div> Neuro-Psychiatric Examination (if applicable)
                            </div>
                        </div>
                    </div>
                </td>
            </tr>

            <tr>
                <td style="padding: 8px; text-align: center;">
                    <span style="font-size: 15px;">FOR THE PROPOSED APPOINTEE</span>
                </td>
            </tr>

            <tr>
                <td>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td colspan="3" style="border: 1px solid black; border-bottom: none; width: 66%; padding: 0px 4px;">
                                <span style="font-size: 10px; vertical-align: middle;">NAME</span> <span style="font-size: 8px; vertical-align: middle;">(Last Name, First Name, Name Extension (if any), and Middle Name)</span>
                            </td>
                            <td rowspan="4" style="border: 1px solid black; padding: 0px 4px; vertical-align: top; width: 34%;">
                                <div style="font-size: 10px; text-align: center; margin: 4px 0;">AGENCY / ADDRESS</div>
                                <strong style="font-size: 16px; word-wrap: break-word; overflow-wrap: break-word;">{{ $employees[0]->company ?? '' }}</strong>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" style="border: 1px solid black; border-top: none; padding: 8px 4px; width: 66%;">
                                <strong style="font-size: 16px;">{{ $employees[0]->name ?? '' }}</strong>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" style="border: 1px solid black; border-bottom: none; width: 66%; padding: 0px 4px;">
                                <span style="font-size: 10px; vertical-align: middle;">ADDRESS</span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" style="border: 1px solid black; border-top: none; padding: 8px 4px; width: 66%;">
                                <strong style="font-size: 16px;">{{ $employees[0]->address ?? '' }}</strong>
                            </td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; border-bottom: none; width: 22%; padding: 0px 4px;">
                                <span style="font-size: 10px; vertical-align: middle;">AGE</span>
                            </td>
                            <td style="border: 1px solid black; border-bottom: none; width: 22%; padding: 0px 4px;">
                                <span style="font-size: 10px; vertical-align: middle;">SEX</span>
                            </td>
                            <td style="border: 1px solid black; border-bottom: none; width: 22%; padding: 0px 4px;">
                                <span style="font-size: 10px; vertical-align: middle;">CIVIL STATUS</span>
                            </td>
                            <td style="border: 1px solid black; border-bottom: none; width: 34%; padding: 0px 4px;">
                                <span style="font-size: 10px; vertical-align: middle;">PROPOSED POSITION</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; border-top: none; padding: 8px 4px; width: 22%;">
                                <strong style="font-size: 16px;">{{ $employees[0]->age ?? '' }}</strong>
                            </td>
                            <td style="border: 1px solid black; border-top: none; padding: 8px 4px; width: 22%;">
                                <strong style="font-size: 16px;">{{ $employees[0]->gender ?? '' }}</strong>
                            </td>
                            <td style="border: 1px solid black; border-top: none; padding: 8px 4px; width: 22%;">
                                <strong style="font-size: 16px;">{{ $employees[0]->civil_status ?? '' }}</strong>
                            </td>
                            <td style="border: 1px solid black; border-top: none; padding: 8px 4px; width: 34%;">
                                <strong style="font-size: 16px;">{{ $employees[0]->position ?? '' }}</strong>
                            </td>
                        </tr>
                    </table>
                </td>
            <tr>

            <tr>
                <td style="padding: 8px; text-align: center;">
                    <span style="font-size: 15px;">FOR THE LICENSED GOVERNMENT OR PRIVATE PHYSICIAN</span>
                </td>
            </tr>

            <tr>
                <td>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td colspan="7" style="border: 1px solid black; padding: 4px; width: 100%;">
                                <p style="font-size: 13px; line-height: 1.2; font-style: italic; text-indent: 15px; margin: 0;">
                                    I hereby certify that I have reviewed and evaluated the attached examination results, personally examined the above-named individual and found him/her to be physically and medically
                                    <span style="display: inline-block; width: 12px; height: 12px; border: 1px solid black; vertical-align: baseline; margin: 0 3px; position: relative; top: 2px;"></span> FIT/
                                    <span style="display: inline-block; width: 12px; height: 12px; border: 1px solid black; vertical-align: baseline; margin: 0 3px; position: relative; top: 2px;"></span> UNFIT for employment.
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="4" style="border: 1px solid black; border-bottom: none; padding: 0px 4px; width: 66%;">
                                <span style="font-size: 10px; vertical-align: middle;">
                                    SIGNATURE over PRINTED NAME of Licensed Government or Private Physician:
                                </span>
                            </td>
                            <td colspan="3" style="border: 1px solid black; border-bottom: none; text-align: center; padding: 0px 4px; width: 34%;">
                                <span style="font-size: 10px; vertical-align: middle; ">
                                    OTHER INFORMATION ABOUT THE
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="4" style="border: 1px solid black; border-bottom: none; border-top: none; padding: 0px 4px; width: 66%;">
                                {{-- empty space --}}
                            </td>
                            <td colspan="3" style="border: 1px solid black; border-bottom: none; border-top: none; text-align: center; padding: 0px 4px; width: 34%;">
                                <p style="font-size: 10px; vertical-align: middle; margin: 0;">
                                    PROPOSED APPOINTEE
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="4" style="border: 1px solid black; border-top: none; padding: 8px 4px; width: 66%;">
                                {{-- empty space --}}
                            </td>
                            <td colspan="3" style="border: 1px solid black; border-top: none; border-bottom: none; text-align: center; padding: 0px 4px; width: 34%;">
                                {{-- empty space --}}
                            </td>
                        </tr>

                        <tr>
                            <td colspan="4" style="border: 1px solid black; border-bottom: none; padding: 0px 4px; width: 66%;">
                                <span style="font-size: 10px; vertical-align: middle;">
                                    AGENCY/Affiliation of Licensed Government or Private Physician:
                                </span>
                            </td>
                            <td colspan="3" style="border: 1px solid black; border-top: none; border-bottom: none; text-align: center; padding: 0px 4px; width: 34%;">
                                {{-- empty space --}}
                            </td>
                        </tr>

                        <tr>
                            <td colspan="4" style="border: 1px solid black; padding: 20px 4px; width: 66%;">
                                {{-- empty space --}}
                            </td>
                            <td colspan="3" style="border: 1px solid black; border-top: none; text-align: center; padding: 20px 4px; width: 34%;">
                                {{-- empty space --}}
                            </td>
                        </tr>

                        <tr>
                            <td colspan="4" style="border: 1px solid black; border-bottom: none; padding: 0px 4px; width: 66%;">
                                <span style="font-size: 10px; vertical-align: middle;">
                                    LICENSE NO.:
                                </span>
                            </td>
                            <td style="border: 1px solid black; border-bottom: none; text-align: center; padding: 0px 4px; width: 11.33%;">
                                <span style="font-size: 10px; vertical-align: middle;">HEIGHT (M)</span>
                            </td>
                            <td style="border: 1px solid black; border-bottom: none; text-align: center; padding: 0px 4px; width: 11.33%;">
                                <span style="font-size: 10px; vertical-align: middle;">WEIGHT (KG)</span>
                            </td>
                            <td style="border: 1px solid black; border-bottom: none; text-align: center; padding: 0px 4px; width: 11.33%;">
                                <span style="font-size: 10px; vertical-align: middle;">BLOOD TYPE</span>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="4" style="border: 1px solid black; border-top: none; border-bottom: none; padding: 0px 4px; width: 66%;">
                                {{-- empty space --}}
                            </td>
                            <td style="border: 1px solid black; border-top: none; text-align: center; padding: 0px 4px; width: 11.33%;">
                                <p style="font-size: 8px; vertical-align: top; margin: 0;">Bare Foot</p>
                            </td>
                            <td style="border: 1px solid black; border-top: none; text-align: center; padding: 0px 4px; width: 11.33%;">
                                <p style="font-size: 8px; vertical-align: top; margin: 0;">Stripped</p>
                            </td>
                            <td style="border: 1px solid black; border-top: none; text-align: center; padding: 0px 4px; width: 11.33%;">
                                <p style="font-size: 8px; vertical-align: top; margin: 0;">TYPE</p>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="4" style="border: 1px solid black; border-top: none; padding: 10px 4px; width: 66%;">
                                {{-- empty space --}}
                            </td>
                            <td style="border: 1px solid black; text-align: center; padding: 10px 4px; width: 11.33%;">
                                {{-- empty space --}}
                            </td>
                            <td style="border: 1px solid black; text-align: center; padding: 10px 4px; width: 11.33%;">
                                {{-- empty space --}}
                            </td>
                            <td style="border: 1px solid black;  text-align: center; padding: 10px 4px; width: 11.33%;">
                                {{-- empty space --}}
                            </td>
                        </tr>

                        <tr>
                            <td colspan="4" style="border: 1px solid black; border-bottom: none; padding: 0px 4px; width: 66%;">
                                <span style="font-size: 10px; vertical-align: middle;">OFFICIAL DESIGNATION</span>
                            </td>
                            <td colspan="3" style="border: 1px solid black; border-bottom: none; padding: 0px 10px; width: 34%;">
                                <span style="font-size: 10px; vertical-align: middle;">DATE EXAMINED:</span>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="4" style="border: 1px solid black; border-top: none; padding: 13px; width: 66%;">
                                {{-- empty space --}}
                            </td>
                            <td colspan="3" style="border: 1px solid black; border-top: none; padding: 13px; width: 34%;">
                                {{-- empty space --}}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>


        </table>
    </div>

</body>

</html>
