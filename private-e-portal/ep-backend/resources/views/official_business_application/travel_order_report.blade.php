<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Travel Order</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            /* Margins: top 1in, right 1in, bottom 0.5in, left 1in */
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
        .underline {
            border-bottom: 1px solid #000;
            padding-bottom: 1px;
            display: inline-block;
            min-width: 120px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        .table-bordered th,
        .table-bordered td {
            border: 1px solid #000;
            padding: 4px 6px;
        }
        .table-bordered th {
            text-align: center;
        }
        .footer {
            font-size: 9px;
            margin-top: 40px;
            text-align: center;
        }
        .footer-image img {
            padding-top: 7%;
            width: 100%;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
        }
        .dts-underline {
            border-bottom: 1px solid #000;
            padding-bottom: 1px;
            display: inline-block;
            min-width: 200px;
        }
    </style>
</head>
<body>
@php
    $record = $ob[0];
    $fromDate = $record->date_time_from ? \Carbon\Carbon::parse($record->date_time_from)->format('F d, Y') : '';
    $toDate = $record->date_time_to ? \Carbon\Carbon::parse($record->date_time_to)->format('F d, Y') : '';
    $inclusive = trim($fromDate . ($toDate && $toDate !== $fromDate ? ' - ' . $toDate : ''));
    $destination = $record->client ?? '';
    $purpose = $record->purpose ?? '';
    $funds = $record->funds ?? '';
    $date = $record->date ? \Carbon\Carbon::parse($record->date)->format('F d, Y') : '';
@endphp

    {{-- Header with TA logo and Annex A --}}
    <table width="100%">
        <tr>
            <td>
                @if(!empty($taLogo))
                    <img src="data:image/png;base64,{{ $taLogo }}" alt="Travel Order Logo" style="height: 80px;">
                @endif
            </td>
            <td class="text-right text-bold" style="vertical-align: top; font-size: 12px;">
                ANNEX A
            </td>
        </tr>
    </table>

    <div class="mt-10">
        <div>MEMORANDUM ORDER NO. ________</div>
        <div>Series of 2025</div>
        <div class="text-right mt-5">Date: <span class="underline" style="min-width: 120px;">{{ $date }}</span></div>
    </div>

    <div class="text-center mt-20 mb-10">
        <div class="text-bold">TRAVEL ORDER</div>
    </div>

    <div class="mt-10">
        <p class="mb-5">
            A. The following officials or personnel are hereby authorized to travel to the
            destination indicated opposite their respective name:
        </p>

        <table class="table-bordered mt-5">
            <tr>
                <th width="45%">Name<br>Designation/Official Station</th>
                <th width="25%">Inclusive Dates of Travel</th>
                <th width="30%">Destination</th>
            </tr>
            <tr>
                <td>
                    <div class="text-bold">{{ $record->name ?? '' }}</div>
                    <div>{{ $record->position ?? '' }}</div>
                    <div>{{ $record->branch ?? '' }}</div>
                </td>
                <td class="text-center">{{ $inclusive }}</td>
                <td>{{ $destination }}</td>
            </tr>
        </table>

        <p class="mt-15">
            B. Purpose of the Travel. The officials or personnel are authorized to travel
            for the purpose of <span class="underline" style="min-width: 260px;">{{ $purpose }}</span>.
        </p>

        <p class="mt-10">
            C. The allowable travel expenses based on Executive Order No. 77, s. 2019 and
            Department Order No. 25-63, series of 2025 is hereby authorized subject to
            availability of fund, and pertinent accounting, auditing, and procurement rules
            and regulations.
        </p>

        <p class="mt-10">
            D. The allowable travel expenses shall be charged to the appropriation of
            <span class="underline" style="min-width: 200px;">{{ $funds }}</span>.
        </p>
    </div>

    {{-- Signature and approval section --}}
    <div class="mt-30">
        <div class="mt-20">
            <div class="signature-name">{{ $record->recommending_approval ?? '________________________' }}</div>
            <div>Budget Officer</div>
        </div>
        
        <div class="mt-20">
            <div>Approved:</div>
        </div>
        
        <div class="mt-20">
            @php
                // Resolve approver for Travel Order from approver_headers / approver_details (type_id = 2)
                $toApproverName = $record->approver ?? null;
                $toApproverPosition = $record->approver_position ?? null;

                try {
                    $appKey = env('APP_KEY', '');

                    $toApprover = \DB::table('official_business_applications as ob')
                        ->join('approver_details as ad', 'ad.employee_id', '=', 'ob.employee_id')
                        ->join('approver_headers as ah', function ($join) {
                            $join->on('ah.id', '=', 'ad.approver_id')
                                ->where('ah.type_id', 2); // Official Business approver setup
                        })
                        ->join('employees as emp', 'ah.approver_id_1', '=', 'emp.id')
                        ->leftJoin('positions as pos', 'emp.position_id', '=', 'pos.id')
                        ->where('ob.id', $record->id)
                        ->orderBy('ah.id', 'desc')
                        ->selectRaw("
                            CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.first_name
                                ELSE RTRIM([dbo].[ufn_DecryptString](emp.first_name,'$appKey')) END as first_name,
                            CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.middle_name
                                ELSE RTRIM([dbo].[ufn_DecryptString](emp.middle_name,'$appKey')) END as middle_name,
                            CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.last_name
                                ELSE RTRIM([dbo].[ufn_DecryptString](emp.last_name,'$appKey')) END as last_name,
                            pos.name as position
                        ")
                        ->first();

                    if ($toApprover) {
                        $first = ucwords(strtolower(trim($toApprover->first_name ?? '')));
                        $last = ucwords(strtolower(trim($toApprover->last_name ?? '')));
                        $middle = trim($toApprover->middle_name ?? '');
                        $middleInitial = $middle !== '' ? strtoupper(mb_substr($middle, 0, 1, 'UTF-8')) . '.' : '';

                        $nameParts = array_filter([$first, $middleInitial, $last]);
                        $toApproverName = implode(' ', $nameParts);
                        $toApproverPosition = $toApprover->position ?: $toApproverPosition;
                    }
                } catch (\Throwable $e) {
                    // Fallback silently to $record->approver / $record->approver_position
                }
            @endphp
            <div class="signature-name">{{ $toApproverName ?? '________________________' }}</div>
            <div>{{ $toApproverPosition ?? 'Designation' }}</div>
        </div>
        
        <div class="mt-20">
            <div>DTS No.: <span class="dts-underline"></span></div>
        </div>
    </div>

     @if(!empty($taFooter))
        <div class="footer-image">
            <img src="data:image/png;base64,{{ $taFooter }}" alt="Travel Order Footer">
        </div>
    @else
        @if(!empty($footer['document_no']) || !empty($footer['revision']))
            <div class="footer">
                Document No.: {{ $footer['document_no'] ?? '' }} &nbsp;&nbsp; | &nbsp;&nbsp;
                Revision: {{ $footer['revision'] ?? '' }}
            </div>
        @endif
    @endif
</body>
</html>


