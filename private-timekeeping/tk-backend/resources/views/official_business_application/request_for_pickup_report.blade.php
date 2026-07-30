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

    <title>Request for Pick-up</title>

    <style>
        @page {
            size: A4;
            margin: 0.5cm 1.27cm 0.5cm 1.27cm;
        }

        body {
            margin: 0;
            padding: 5px;
            font-family: 'Source Sans Pro', sans-serif;
            font-size: 11px;
        }

        .form-wrapper {
            position: relative;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        .form-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 4px;
            margin-top: 0;
        }

        .form-subtitle {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .ref-number {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 9px;
        }

        .form-body {
            border: 1px solid #000;
            padding: 8px;
            margin-top: 10px; /* so border starts below ref no. */
            font-size: 11px;
        }

        .top-block {
            margin-bottom: 8px;
        }

        .top-block div {
            margin-bottom: 2px;
        }

        .documents-title {
            text-align: center;
            font-size: 11px;
            margin-bottom: 4px;
            font-weight: bold;
        }

        .documents-lines {
            font-size: 11px;
            margin-bottom: 10px;
        }

        .documents-lines .line {
            border-bottom: 1px solid #000;
            min-height: 14px;
            margin-top: 2px;
            padding: 1px 2px;
        }

        .bottom-section {
            font-size: 11px;
        }

        .bottom-row {
            margin-bottom: 6px;
            overflow: hidden;
        }

        .bottom-row span {
            display: inline-block;
        }

        .bottom-row .right {
            float: right;
        }
    </style>
</head>

<body>
@for($i = 0; $i < 2; $i++)
    <div class="form-wrapper">


        <!-- Reference number outside border -->
        <div class="ref-number">
            AFM-PER.FR#04/REV.00/15-16-14
        </div>

        <!-- Bordered form -->
        <div class="form-body">

            <!-- Title and Subtitle -->
            <div class="form-title">
                {{ strtoupper($orgCompanyName) }}
            </div>
            <div class="form-subtitle">
                REQUEST FOR PICK-UP
            </div>

            <!-- Top info -->
            <div class="top-block">
                <div>
                    From: {{ $pickupData ? $pickupData->from : '' }}
                    <span style="float:right;">
                        Date:
                        {{ $pickupData && $pickupData->date ? date('M d, Y', strtotime($pickupData->date)) : '' }}
                    </span>
                </div>
                <div>
                    Company: {{ $pickupData ? $pickupData->company : '' }}
                </div>
                <div>
                    Address: {{ $pickupData ? $pickupData->address : '' }}
                </div>
                <div>
                    Contact No: {{ $pickupData ? $pickupData->contact_no : '' }}
                </div>
            </div>

            <!-- Title -->
            <div class="documents-title">
                Type of Document/Materials
            </div>

            <!-- Document lines -->
            <div class="documents-lines">
                <div class="line">
                    {{ $pickupData ? $pickupData->documents_materials : '' }}
                </div>
                <div class="line"></div>
                <div class="line"></div>
                <div class="line"></div>
            </div>

            <!-- Bottom signatures -->
            <div class="bottom-section">
                <div class="bottom-row">
                    <span>
                        Picked up by:
                        {{ !empty(trim($pickupData->picked_up_by ?? '')) ? $pickupData->picked_up_by : '___________________________' }}
                    </span>
                    <span class="right">
                        Requested By:
                        @php
                            $requestedByResolved = null;
                            if (!empty(trim($pickupData->requested_by_name ?? ''))) {
                                $requestedByResolved = $pickupData->requested_by_division
                                    ? $pickupData->requested_by_name . ' / ' . $pickupData->requested_by_division
                                    : $pickupData->requested_by_name;
                            } elseif (!empty(trim($pickupData->requested_by ?? ''))) {
                                $requestedByResolved = $pickupData->requested_by;
                            }
                        @endphp
                        {{ $requestedByResolved ?? '___________________________' }}
                    </span>
                </div>
                <div class="bottom-row">
                    <span>
                        Date:
                        {{ $pickupData && $pickupData->pickup_date ? date('M d, Y', strtotime($pickupData->pickup_date)) : '___________________________' }}
                    </span>
                    <span class="right">
                        Received By:
                        {{ !empty(trim($pickupData->received_by ?? '')) ? $pickupData->received_by : '___________________________' }}
                    </span>
                </div>
            </div>

        </div>
    </div>
@endfor
</body>
</html>
