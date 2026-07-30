<!DOCTYPE html>
<html>

<head>
    <title>Print SALN</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .container {
            width: 100%;
            margin-top: -50px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            margin-top: 60px;
        }

        .header h3 {
            font-size: 18px;
            font-weight: bold;
        }

        .note {
            font-size: 12px;
            margin-bottom: 15px;
        }

        .checkbox-group {
            margin-top: 5px;
        }

        .checkbox-group label {
            margin-right: 15px;
            font-size: 12px;
        }

        .section-title {
            margin-top: 15px;
            font-size: 14px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
        }

        .row label {
            width: 32%;
            font-size: 12px;
        }

        .full-row {
            width: 100%;
            margin-top: 5px;
        }

        .table-container {
            margin-top: 0px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .taybol {
            font-size: 8px;
        }

        .taybol table,
        .taybol th,
        .taybol tr,
        .taybol td {
            border: 1px solid;
        }

        th,
        td {
            padding: 8px;
            text-align: center;
            font-size: 12px;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div style="float:right; font-size: 9px;">
            <p>Revised as of Janus.ry 2015 <br>Per CSC Resolution No. 1500088 <br>Promulgated on January 23, 2015</p>
        </div>
        <!-- Header Section -->
        <div class="header">
            <h3>SWORN STATEMENT OF ASSETS, LIABILITIES AND NET WORTH</h3>
            <p style="font-size:12px; margin-top:-15px;">As of ___________________________</p>
            <p style="font-size:12px; margin-top:-10px;">(Required by R.A. 6713)</p>
            <p style="font-size:12px;"><strong>Note:</strong> <i>Husband and wife who are both public officials and
                    employees may file the required statements jointly or separately.</i></p>
            <div class="checkbox-group">
                <label><input type="checkbox"> Joint Filing</label>
                <label><input type="checkbox"> Separate Filing</label>
                <label><input type="checkbox"> Not Applicable</label>
            </div>
        </div>

        <!-- Declarant Information -->
        <div class="section-title">
            <table class="names" style="width: 100%; border: none;">
                <tr>
                    <td style=" text-align: left; vertical-align: top; width: 50%;">
                        <strong>DECLARANT:</strong> <u>{{ $info[0]->name }}</u>
                        <br>
                        <strong>Address:</strong>
                        <u>
                            {{ $info[0]->pa_house_no == null ? '' : $info[0]->pa_house_no . ', ' }}
                            {{ $info[0]->pa_village == null ? '' : $info[0]->pa_village . ', ' }}
                            {{ $info[0]->pa_street == null ? '' : $info[0]->pa_street . ', ' }}
                            {{ 'Brgy. ' . $address['pa_brgy'] }},
                            {{ $address['pa_city'] }}, {{ $address['pa_province'] }},
                            {{ $address['pa_region'] }}
                        </u>
                    </td>
                    <td style="text-align: left; vertical-align: top;">
                        <strong>POSITION:</strong> <u>{{ $info[0]->position == null ? '-' : $info[0]->position }}</u>
                        <br>
                        <strong>AGENCY/OFFICE:</strong>
                        <u>{{ $info[0]->department == null ? '-' : $info[0]->department }}</u>
                        <br>
                        <label><strong>OFFICE ADDRESS: ______</strong> </label>
                    </td>
                </tr>
            </table>
        </div>

        <div>
            <table class="names" style="width: 100%; border: none;">
                <tr>
                    <td style=" text-align: left; vertical-align: top;  width: 50%;">
                        <strong>SPOUSE:</strong> <u>{{ $info[0]->spouse_name }}</u>
                    </td>
                    <td style="text-align: left; vertical-align: top;">
                        <strong>POSITION:</strong>
                        <u>{{ $info[0]->spouse_occupation == null ? '-' : $info[0]->spouse_occupation }}</u>
                        <br>
                        <strong>AGENCY/OFFICE:</strong>
                        <u>{{ $info[0]->spouse_employer == null ? '-' : $info[0]->spouse_employer }}</u>
                        <br>
                        <label><strong>OFFICE ADDRESS:</strong>
                            <u>{{ $info[0]->spouse_business_address == null ? '-' : $info[0]->spouse_business_address }}</u>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        <hr>
        <hr style="margin-top:-7px;">
        <!-- Unmarried Children Section -->
        <div class="section-title">UNMARRIED CHILDREN BELOW EIGHTEEN (18) YEARS OF AGE LIVING IN DECLARANT'S HOUSEHOLD
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Date of Birth</th>
                        <th>Age</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($children as $child)
                        <tr>
                            <td><u>
                                    {{ $child->child_name }}
                                    {{ $child->child_middlename }}
                                    {{ $child->child_lastname }}</u>
                            </td>
                            <td>
                                <u>{{ date('m-d-Y', strtotime($child->child_birthdate)) }}</u>
                            </td>
                            <td>
                                <u>{{ \Carbon\Carbon::parse($child->child_birthdate)->age }} years old</u>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <hr>
        <hr style="margin-top:-7px;">
        <div class="text-center mb-4">
            <h3 style="text-align: center; font-size: 18px; margin-top:-2px;"><u>ASSETS, LIABILITIES AND NETWORTH </u>
            </h3>
            <p style="text-align: center; font-size: 12px; margin-top:-20px;">(Including those of the spouse and
                unmarried children below eighteen (18) years of age living in declarant's household) </p>
        </div>
        <div style="margin-top:-20px;">
            <h3 style="font-size: 14px;">1. ASSETS</h3>
            <h3 style="font-size: 14px; text-indent:20px;margin-top:-15px;">a. Real Properties *</h3>
        </div>
        <table class="taybol">
            <thead>
                <tr>
                    <th rowspan="2">DESCRIPTION</th>
                    <th rowspan="2">KIND</th>
                    <th rowspan="2">EXACT LOCATION</th>
                    <th>ASSESSED VALUE</th>
                    <th>CURRENT FAIR MARKET VALUE</th>

                    <th colspan="2">ACQUISITION</th>
                    <th rowspan="2">ACQUISITION COST</th>
                </tr>
                <tr>
                    <th colspan="2">As found in the Tax Declaration of Real Property</th>
                    <th>YEAR</th>
                    <th>MODE</th>
                </tr>
            </thead>
            <tbody id="realPropertiesBody">
                @foreach ($realProperties as $property)
                    <tr>
                        <td>{{ $property->description }}</td>
                        <td>{{ $property->kind }}</td>
                        <td>{{ $property->exact_location }}</td>
                        <td>{{ $property->assessed_value }}</td>
                        <td>{{ $property->current_fair_market_value }}</td>
                        <td>{{ $property->acquisition_year }}</td>
                        <td>{{ $property->acquisition_mode }}</td>
                        <td>{{ $property->acquisition_cost }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="float: right; margin-top: -10px;">
            <p>Subtotal: </p>
        </div><br><br>

        <h3 style="font-size: 14px; text-indent:20px;">b. Personal Properties *</h3>
        <table class="taybol">
            <thead>
                <tr>
                    <th>DESCRIPTION</th>
                    <th>YEAR ACQUIRED</th>
                    <th>ACQUISITION COST/AMOUNT</th>
                </tr>
            </thead>
            <tbody id="personalPropertiesBody">
                @foreach ($personalProperties ?? [] as $property)
                    <tr>
                        <td>{{ $property->description }}</td>
                        <td>{{ $property->year_acquired }}</td>
                        <td>{{ $property->acquisition_cost }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="float: right;  margin-top: -10px;">
            <p>Subtotal: </p>
        </div><br>
        <div style="float: right;">
            <p>TOTAL ASSETS (a+b): </p>
        </div><br><br>
        <br><br><br><br><br><br><br>

        <div>
            <br>
            <h3 style="font-size: 14px;">2. LIABILITIES</h3>
        </div>
        <table class="taybol">
            <thead>
                <tr>
                    <th>NATURE</th>
                    <th>NAME OF CREDITORS</th>
                    <th>OUTSTANDING BALANCE</th>
                </tr>
            </thead>
            <tbody id="liabilitiesBody">
                @foreach ($liabilities ?? [] as $liability)
                    <tr>
                        <td>{{ $liability->nature }}</td>
                        <td>{{ $liability->creditor_name }}</td>
                        <td>{{ number_format($liability->outstanding_balance, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="float: right;  margin-top: -10px;">
            <p>TOTAL LIABILITIES: </p>
            <p style="margin-top: -10px;">NET WORTH : Total Assets less Total Liabilities = </p>
        </div><br><br><br>
        <div>
            <p>* Additional sheet/ s may be used, if necessary. </p>
        </div>
        <div class="text-center mb-4">
            <h3 style="text-align: center; font-size: 18px; margin-top:-2px;"><u>BUSINESS INTERESTS AND
                    FINANCIAL CONNECTIONS OUTSTANDING BALANCE </u>
            </h3>
            <p style="text-align: center; font-size: 12px; margin-top:-18px;">(of Declarant
                /Declarant's spouse/ Unmarried Children Below Eighteen (18)
                years of Age Luring in Declarant's Household)</p>
            <div style=" font-size: 12px; text-align: center;">
                <label><input type="checkbox"> I/ We do not have any business interest or financial connection. </label>
            </div>
        </div>
        <div class="checkbox-group">
        </div>
        <table class="taybol">
            <thead>
                <tr>
                    <th>NAME OF ENTITY/BUSINESS ENTERPRISE</th>
                    <th>BUSINESS ADDRESS</th>
                    <th>NATURE OF BUSINESS INTEREST OR FINANCIAL
                        CONNECTION</th>
                    <th>DATE OF ACQUISITION</th>
                </tr>
            </thead>
            <tbody id="businessInterestsBody">
                @foreach ($business ?? [] as $buss)
                    <tr>
                        <td hidden>{{ $buss->id }}</td>
                        <td>{{ $buss->entity_name }}</td>
                        <td>{{ $buss->business_address }}</td>
                        <td>{{ $buss->nature_of_business }}</td>
                        <td>{{ date('m-d-Y', strtotime($buss->date_acquired)) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <br><br>
        <div class="text-center mb-4">
            <h3 style="text-align: center; font-size: 18px; margin-top:-2px;"><u>RELATIVES IN THE GOVERNMENT
                    SERVICE </u>
            </h3>
            <p style="text-align: center; font-size: 12px; margin-top:-18px;">(Within the Fourth Degree of Consanguinity
                or
                Affinity. Include also Biles, Balae and Inso)</p>
            <div style=" font-size: 12px; text-align: center;">
                <label><input type="checkbox"> I/ We do not have any business interest or financial connection. </label>
            </div>
        </div>
        <div class="checkbox-group">
        </div>
        <table class="taybol">
            <thead>
                <tr>
                    <th>NAME OF RELATIVE</th>
                    <th>RELATIONSHIP</th>
                    <th>POSITION</th>
                    <th>NAME OF AGENCY/OFFICE AND ADDRESS</th>
                </tr>
                </tr>
            </thead>
            <tbody id="businessRelativesBody">
                @foreach ($relatives ?? [] as $relate)
                    <tr>
                        <td>{{ $relate->relatives_name }}</td>
                        <td>{{ $relate->relationship }}</td>
                        <td>{{ $relate->position }}</td>
                        <td>{{ $relate->office_address }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <br>
        <div class="w-full max-w-4xl mx-auto border border-gray-300 p-6 bg-white shadow-md">
            <p style="text-indent: 40px; text-align: justify; font-size: 14px;">
                I hereby certify that these are true and correct statements of my assets, liabilities, net worth,
                business interests and financial connections, including those of my spouse and unmarried children below
                eighteen (18) years of age living in my household, and that to the best of my knowledge, the
                above-enumerated
                are names of my relatives in the government within the fourth civil degree of consanguinity or affinity.
            </p>

            <p style="text-indent: 40px; text-align: justify; font-size: 14px;">
                I hereby authorize the Ombudsman or his/her duly authorized representative to obtain and secure from all
                appropriate government agencies, including the Bureau of Internal Revenue such documents that may show
                my assets, liabilities, net worth, business interests and financial connections, to include those of my
                spouse
                and unmarried children below 18 years of age living with me in my household covering previous years
                to include the year I first assumed office in government.
            </p>

            <div class="mb-6">
                <p class="mb-2" style="font-size: 14px;"><strong>Date:</strong>
                    ___________________________________________</p>
            </div>

            <div class="section-title">
                <table class="names" style="width: 100%; border: none;">
                    <tr>
                        <td style=" text-align: left; vertical-align: top;">
                            <strong>Government Issued ID: _________________________</strong>
                            <br>
                            <strong>ID No.: _________________________</strong>
                            <br>
                            <strong>Date Issued: _________________________</strong>
                        </td>
                        <td style="text-align: left; vertical-align: top;">
                            <strong>Government Issued ID: _________________________</strong>
                            <br>
                            <strong>ID No.: _________________________</strong>
                            <br>
                            <strong>Date Issued: _________________________</strong>
                        </td>
                    </tr>
                </table>
            </div>

            <p style="font-size: 14px; class=" font-bold mb-4">
                <strong>SUBSCRIBED AND SWORN</strong> to before me this ____ day of ______, affiant exhibiting to me the
                above-stated
                government issued identification card.
            </p>

            <p class="text-center italic">___________________________________________</p>
            <p style="font-size: 14px;" class="text-center italic">(Person Administering Oath)</p>
        </div>

    </div>
</body>

</html>