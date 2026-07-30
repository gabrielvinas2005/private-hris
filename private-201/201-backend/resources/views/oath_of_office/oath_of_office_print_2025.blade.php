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
            margin: 16px 32px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 0;
            vertical-align: top;
        }

        p {
            margin: 0;
            padding: 0;
        }

        .oath-line {
            margin: 4px 0;
        }

        .oath-position {
            font-weight: bold;
            text-align: center;
            word-wrap: break-word;
            overflow-wrap: break-word;
            line-height: 1.25;
        }

        .oath-position-long {
            font-size: 13px;
        }

        .oath-en {
            display: block;
            font-size: 12px;
            font-weight: normal;
            font-style: italic;
        }

        .oath-footer-block {
            page-break-inside: avoid;
        }

        @media print {
            html,
            body {
                margin: 10px 24px;
            }

            .oath-page {
                page-break-inside: avoid;
            }

            .oath-footer-block {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <!-- Main content -->
    @foreach ($oath_of_offices as $oath_of_offices)
        <div class="card p-4 oath-page">
            <table>
                <tr>
                    <td>
                        <p style="font-size: 13px; font-weight: bold; font-style: italic;">SS Porma Blg. 32</p>
                        <p style="font-size: 12px; font-style: italic; margin-bottom: 10px;">CS Form No. 32</p>
                        <p style="font-size: 13px; font-weight: bold;">Narebisa 2025</p>
                        <p style="font-size: 12px; font-style: italic;">Revised 2025</p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p style="text-align: center; font-size: 17px; margin-bottom: 0;">REPUBLIKA NG PILIPINAS</p>
                        <p style="text-align: center; font-size: 12px; font-style: italic; margin-top: 0;">Republic of the Philippines</p>
                        <br>
                        <p style="text-align: center; font-size: 17px; margin-bottom: 0;">{{ $orgCompanyName }}</p>
                        <p style="text-align: center; font-size: 12px; font-style: italic; margin-top: 0;">{{ $orgCompanyAddress }}</p>
                        <br>
                        <p style="text-align: center; font-size: 21px; font-weight: bold; margin-bottom: 0;">PANUNUMPA SA KATUNGKULAN</p>
                        <p style="text-align: center; font-size: 12px; font-style: italic; margin-top: 0;">OATH OF OFFICE</p><br>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="oath-line">
                            <span style="vertical-align: top; margin-left: 20px;">
                                <div style="display: inline-block; min-width: 20px; font-size: 16px; text-align: center; vertical-align: top;">
                                    Ako si
                                    <br>
                                    <span style="font-size: 12px; font-weight: normal; font-style: italic;"> I, </span>
                                </div>
                                <div style="display: inline-block; min-width: 150px; font-weight: bold; font-size: 15px; text-align: center; vertical-align: top;">
                                    @if(!empty($oath_of_offices->name))
                                        <u>{{ strtoupper($oath_of_offices->name) }}</u>
                                    @else
                                        <u>________________________</u>
                                    @endif
                                    <br>
                                    <span style="font-size: 12px; font-weight: normal; font-style: italic;">(Name of Appointee)</span>
                                </div>
                            </span>
                            <span style="vertical-align: top;">ng
                                <div style="display: inline-block; min-width: 270px; font-weight: bold; font-size: 15px; text-align: center; vertical-align: top;">
                                    @if(!empty($address['ra_province']) || !empty($address['ra_city']) || !empty($address['ra_brgy']))
                                        <u>
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
                                        </u>
                                    @else
                                        <u>________________________</u>
                                    @endif
                                    <br>
                                    <span style="font-size: 12px; font-weight: normal; font-style: italic;">(Address)</span>
                                </div>
                                <div style="display: inline-block; min-width: 20px; font-size: 16px; text-align: center; vertical-align: top;">
                                    na
                                    <br>
                                    <span style="font-size: 12px; font-weight: normal; font-style: italic;"> having </span>
                                </div>
                            </span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        @php
                            $positionText = $oath_of_offices->position ?? '';
                            $positionClass = strlen($positionText) > 40 ? 'oath-position oath-position-long' : 'oath-position';
                            $positionFontSize = strlen($positionText) > 55 ? '12px' : (strlen($positionText) > 40 ? '13px' : '15px');
                        @endphp
                        <table class="oath-line" style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                            <tr>
                                <td style="width: 18%; vertical-align: top; font-size: 16px; white-space: nowrap; padding-right: 4px;">
                                    itinalaga bilang
                                    <span class="oath-en">been appointed to</span>
                                </td>
                                <td style="width: 34%; vertical-align: top; padding: 0 6px;">
                                    <div class="{{ $positionClass }}" style="font-size: {{ $positionFontSize }};">
                                        @if(!empty($positionText))
                                            <u>{{ $positionText }}</u>
                                        @else
                                            <u>________________________</u>
                                        @endif
                                    </div>
                                    <span class="oath-en">(Position)</span>
                                </td>
                                <td style="width: 48%; vertical-align: top; font-size: 16px; padding-left: 4px;">
                                    ay taimtim na nanunumpa na tutuparin ko nang
                                    <span class="oath-en">hereby solemnly swear, that I will faithfully discharge</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="oath-line">
                            <span style="vertical-align: top;">
                                <div style="display: inline-block; min-width: 20px; font-size: 16px; text-align: center; vertical-align: top;">
                                    buong husay at katapatan, sa abot ng aking kakayahan,
                                    <br>
                                    <span style="font-size: 12px; font-weight: normal; font-style: italic;"> to the best of my ability </span>
                                </div>
                                <div style="display: inline-block; min-width: 20px; font-size: 16px; text-align: center; vertical-align: top;">
                                    ang mga katungkulang pinagtalagahan
                                    <br>
                                    <span style="font-size: 12px; font-weight: normal; font-style: italic;"> the duties of my present position</span>
                                </div>
                            </span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="oath-line">
                            <span style="vertical-align: top;">
                                <div style="display: inline-block; min-width: 20px; font-size: 16px; text-align: center; vertical-align: top;">
                                    sa akin at sa dapat gampanan sa iba pang pagkaraan nito’y gagampanan ko
                                    <br>
                                    <span style="font-size: 12px; font-weight: normal; font-style: italic;">and of all others that I may hereafter hold</span>
                                </div>
                                <div style="display: inline-block; min-width: 20px; font-size: 16px; text-align: center; vertical-align: top;">
                                    sa ilalim ng
                                    <br>
                                    <span style="font-size: 12px; font-weight: normal; font-style: italic;">under the</span>
                                </div>
                            </span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="oath-line">
                            <span style="vertical-align: top;">
                                <div style="display: inline-block; min-width: 20px; font-size: 16px; text-align: center; vertical-align: top;">
                                    Republika ng Pilipinas;
                                    <br>
                                    <span style="font-size: 12px; font-weight: normal; font-style: italic;">Republic of the Philippines;</span>
                                </div>
                                <div style="display: inline-block; min-width: 20px; font-size: 16px; text-align: center; vertical-align: top;">
                                    na aking itataguyod at ipagtatanggol ang Saligang Batas ng Pilipinas;
                                    <br>
                                    <span style="font-size: 12px; font-weight: normal; font-style: italic;">to uphold and defend the Constitution,</span>
                                </div>
                            </span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="oath-line">
                            <span style="vertical-align: top;">
                                <div style="display: inline-block; min-width: 20px; font-size: 16px; text-align: center; vertical-align: top;">
                                    na tunay na mananalig at tatalima ako rito;
                                    <br>
                                    <span style="font-size: 12px; font-weight: normal; font-style: italic;">that I will bear true faith and allegiance to the same;</span>
                                </div>
                                <div style="display: inline-block; min-width: 20px; font-size: 16px; text-align: center; vertical-align: top;">
                                    na susundin ko ang mga batas at mga kautusang
                                    <br>
                                    <span style="font-size: 12px; font-weight: normal; font-style: italic;">that I will obey the laws, legal orders, and</span>
                                </div>
                            </span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="oath-line">
                            <span style="vertical-align: top;">
                                <div style="display: inline-block; min-width: 20px; font-size: 16px; text-align: center; vertical-align: top;">
                                    legal, at mga dekretong pinaiiral ng mga sadyang itinakdang maykapangyarihan ng Republika
                                    <br>
                                    <span style="font-size: 12px; font-weight: normal; font-style: italic;">decrees promulgated by the duly constituted authorities of the Republic </span>
                                </div>
                            </span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="oath-line">
                            <span style="vertical-align: top;">
                                <div style="display: inline-block; min-width: 20px; font-size: 16px; text-align: center; vertical-align: top;">
                                    ng Pilipinas; at kusa kong babalikatin ang pananagutang ito nang walang ano mang pasubali
                                    <br>
                                    <span style="font-size: 12px; font-weight: normal; font-style: italic;">of the Philippines;  and that I impose this obligation upon myself voluntarily, without mental reservation</span>
                                </div>
                            </span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="oath-line">
                            <span style="vertical-align: top;">
                                <div style="display: inline-block; min-width: 20px; font-size: 16px; text-align: center; vertical-align: top;">
                                    o hangaring umiwas.
                                    <br>
                                    <span style="font-size: 12px; font-weight: normal; font-style: italic;">or purpose of evasion.</span>
                                </div>
                            </span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td>
                        <p style="margin: 6px 50px; font-size: 16px;"> KASIHAN NAWA AKO NG DIYOS. <br>
                            <span style=" margin-left: 10px; font-size: 12px; font-style: italic; margin-top: 0;">
                                SO HELP ME GOD.
                            </span>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table style="width: 100%; margin-bottom: 10px;">
                            <td style="width: 33%;"></td>
                            <td style="width: 33%;"></td>
                            <td style="width: 33%;">
                                <div class="oath-line">
                                    <span style="vertical-align: top;">
                                        <div style="display: inline-block; min-width: 20px; font-size: 15px; text-align: center; vertical-align: top;">
                                            @if(!empty($oath_of_offices->name))
                                                <b><u>{{ strtoupper($oath_of_offices->name) }} </u></b>
                                            @else
                                                <b><u>________________________</u></b>
                                            @endif
                                            <br>
                                            <span style="font-size: 11px; font-weight: normal;">(Lagda sa itaas ng pangalan ng hinirang)</span>
                                        </div>
                                    </span>
                                </div>
                            </td>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p style="font-size: 13px; font-style: italic; ">Government ID: ______________</p>
                        <p style="font-size: 13px; font-style: italic;">ID Number: _________________</p>
                        <p style="font-size: 13px; font-style: italic; margin-bottom: 8px;">Date Issued: ________________</p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="oath-footer-block">
                            <hr style="border: .01px solid black; margin-bottom: 4px">
                            <hr style="border: .01px solid black; margin: 0 0 8px 0">
                            @php
                                $months = [
                                    'January' => 'Enero',
                                    'February' => 'Pebrero',
                                    'March' => 'Marso',
                                    'April' => 'Abril',
                                    'May' => 'Mayo',
                                    'June' => 'Hunyo',
                                    'July' => 'Hulyo',
                                    'August' => 'Agosto',
                                    'September' => 'Setyembre',
                                    'October' => 'Oktubre',
                                    'November' => 'Nobyembre',
                                    'December' => 'Disyembre'
                                ];
                                $currentMonth = date('F', strtotime(now()));
                                $monthTagalog = $months[$currentMonth] ?? $currentMonth;
                                $yearSuffix = date('y', strtotime(now()));
                            @endphp
                            <p style="text-indent: 20px; text-align: justify; font-size: 14px; margin-top: 10px; line-height: 1.35;">Nilagdaan at pinanumpaan sa harap ko ngayong ika <span style="font-size: 15px;"><u>{{ date('d', strtotime(now())) }}</u></span> ng
                                    <span style="font-size: 15px;"><b><u>{{ $monthTagalog }}</u></b></span> ng
                                    <span style="font-size: 15px; vertical-align: baseline; line-height: 1.2;">20</span><span style="font-size: 15px; font-weight: bold; vertical-align: baseline; line-height: 1.2; margin-left: 5px; text-decoration: underline;">{{ $yearSuffix }}</span>, <u>sa {{ $orgCompanyAddress }}.</u>
                            </p>
                            <table style="width: 100%; margin-top: 8px;">
                                <tr>
                                    <td style="width: 33%;"></td>
                                    <td style="width: 33%;"></td>
                                    <td style="width: 34%; vertical-align: top;">
                                        <p style="text-align: center; font-size: 14px; line-height: 1.25;"><b><u>{{ $signatories['signatory'] }}</b></u></p>
                                        @if(!empty($signatories['position']))
                                            <p style="text-align: center; font-size: 14px; line-height: 1.25;">{{ $signatories['position'] }}</p>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    @endforeach
</body>

</html>
