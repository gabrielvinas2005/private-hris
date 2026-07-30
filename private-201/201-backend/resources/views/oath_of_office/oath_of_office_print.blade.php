<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Oath of Office</title>

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
    @foreach ($oath_of_offices as $oath_of_offices)
        <div class="card p-4">
            <div style="margin-left: 1in; margin-right: 1in;">

                <!-- Header -->
                <div>
                    <p style="font: 12px arial;"><i>CS Form No. 32</i></p>
                    <p style="font: italic 12px arial;">Revised 2018</p>
                </div>

                <!-- Agency Info -->
                <div>
                    <p style="padding-top: 40px; text-align: center; font: 18px arial;">Republic of the Philippines</p>
                    <p style="text-align: center; font: 15px arial;">{{ $orgCompanyName }}</p>
                    <p style="text-align: center; font: 15px arial;">{{ $orgCompanyAddress }}</p>
                </div>

                <!-- Title -->
                <div>
                    <p style="padding-top: 30px; padding-bottom: 20px; text-align: center; font: 18px arial;">OATH OF OFFICE</p>
                </div>

                <!-- Body -->
                <div>
                    <p style="text-indent: 0.3in; text-align: justify; line-height: 1.8; font: normal 16px arial;">
                        I, <b><u>{{ strtoupper($oath_of_offices->name) }}</u></b> of <b><u>
                            @php
                                $addressParts = [];
                                if (!empty($address['ra_province'])) {
                                    $addressParts[] = $address['ra_province'];
                                }
                                if (!empty($address['ra_city'])) {
                                    $addressParts[] = $address['ra_city'];
                                }
                                if (!empty($address['ra_brgy'])) {
                                    $addressParts[] = $address['ra_brgy'];
                                }
                                echo implode(', ', $addressParts);
                            @endphp
                        </u></b>
                        having been appointed to the position <b><u>{{ $oath_of_offices->position }}</u></b> hereby
                        solemnly swear, that I will faithfully discharge to the best
                        of my ability, the duties of my present position and of all others that I may hereafter hold
                        under the Republic of the Philippines; that I will bear true faith and allegiance to the same;
                        that I will obey the laws, legal orders, and decrees promulgated by the duly constituted
                        authorities of the Republic of the Philippines; and that I impose this obligation upon myself
                        voluntarily, without mental reservation or purpose of evasion.
                    </p>
                </div>

                <br>

                <!-- So Help Me God -->
                <div>
                    <p style="text-indent: 0.3in; line-height: 1.8; font: normal 16px arial;">SO HELP ME GOD.</p>
                </div>

                <br>

                <!-- Appointee Signature -->
                <div style="text-align: right; padding-top: 10px; padding-bottom: 10px;">
                    <p style="font-family: arial;"><b><u>{{ strtoupper($oath_of_offices->name) }}</u></b></p>
                    <p style="font-family: arial;">(Signature over Printed Name <br>of the Appointee)</p>
                </div>

                <!-- Government ID -->
                <div style="padding-top: 15px;">
                    <p>Government ID: ______________</p>
                    <p>ID Number: _________________</p>
                    <p>Date Issued: ________________</p>
                </div>

                <br>

                <!-- Subscribed Section -->
                <div style="border-top: 2px solid; width: 100%; padding-top: 5px;">
                    <p style="text-indent: 0.3in; text-align: justify; font-family: arial;">
                        Subscribed and sworn before me this
                        <b><u>{{ date('d', strtotime(now())) }}</u></b> day of
                        <b><u>{{ date('M Y', strtotime(now())) }}</u></b>,
                        in {{ $orgCompanyAddress }}.
                    </p>

                    <!-- Administering Officer Signature -->
                    <div style="text-align: right; padding-top: 20px;">
                        <p style="font-family: arial;"><b><u>{{ $signatories['signatory'] }}</u></b></p>
                        <p style="font-family: arial;">(Signature over Printed Name <br>of Person Administering the Oath)</p>
                    </div>
                </div>

            </div>
        </div>
    @endforeach
</body>

</html>
