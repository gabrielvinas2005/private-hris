<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Travel Authority - Personal</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            /* Top 1in, Right 1in, Bottom 0.5in, Left 1in */
            margin: 1in 1in 0.5in 1in;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .mt-5 { margin-top: 5px; }
        .mt-10 { margin-top: 10px; }
        .mt-15 { margin-top: 15px; }
        .mt-20 { margin-top: 20px; }
        .mt-30 { margin-top: 30px; }
        .mb-5 { margin-bottom: 5px; }
        .mb-10 { margin-bottom: 10px; }
        .underline {
            border-bottom: 1px solid #000;
            padding-bottom: 1px;
            display: inline-block;
            min-width: 120px;
        }
        .footer-image img {
            padding-top: 7%;
            width: 100%;
        }
        ol {
            margin: 0;
            padding-left: 18px;
        }
        li {
            margin-bottom: 8px;
            text-align: justify;
        }
    </style>
</head>
<body>
@php
    use App\Helpers\CompanyHelper;

    $record = $ob[0];
    $companyName = CompanyHelper::getName() ?: 'the agency';
    $fromDate = $record->date_time_from ? \Carbon\Carbon::parse($record->date_time_from)->format('F d, Y') : '';
    $toDate = $record->date_time_to ? \Carbon\Carbon::parse($record->date_time_to)->format('F d, Y') : '';
    $inclusive = trim($fromDate . ($toDate && $toDate !== $fromDate ? ' – ' . $toDate : ''));
    $destination = $record->client ?? '';
    $date = $record->date ? \Carbon\Carbon::parse($record->date)->format('d F Y') : '';
@endphp

    {{-- Header with TA logo --}}
    @if(!empty($taLogo))
        <div style="margin-bottom: 10px;">
            <img src="data:image/png;base64,{{ $taLogo }}" alt="Travel Authority Logo" style="height: 80px;">
        </div>
    @endif

    {{-- Date and memo number --}}
    <div class="mt-10">
        <div>{{ $date }}</div>
        <div class="mt-20">
            <div>MEMORANDUM ORDER NO. _________</div>
            <div>Series of {{ \Carbon\Carbon::now()->year }}</div>
        </div>
    </div>

    {{-- Title --}}
    <div class="text-center mt-30 mb-10">
        <div class="text-bold">TRAVEL AUTHORITY</div>
    </div>

    {{-- Narrative bullets --}}
    <ol>
        <li>
            This is to authorize <span class="text-bold">{{ $record->name ?? '' }}</span>,
            {{ $record->position ?? '' }} of {{ $companyName }} to travel to
            <span class="text-bold">{{ $destination }}</span> on
            <span class="text-bold">{{ $inclusive }}</span> (inclusive of travel time) while on approved personal leave.
        </li>
        <li>
            It is understood that the above-mentioned personnel will shoulder all expenses to be
            incurred during the travel, thus relieving the Department of any financial obligations.
        </li>
        <li>
            In the interest of service, {{ $record->name ?? 'the personnel' }} will turn over whatever pending matters and
            assignments to her/his colleague to ensure smooth functioning of the unit during her/his absence.
        </li>
        <li>
            The travel documentation is being undertaken in accordance with the provision of
            Department Order 10-57, DTI Foreign Travel Policy.
        </li>
    </ol>

    {{-- Signatory --}}
    <div class="mt-30">
        <div class="mt-30 text-bold">
            CLARE MARI S. TORRALBA
        </div>
        <div>Executive Director</div>
    </div>

    {{-- Footer image removed as requested --}}
</body>
</html>


