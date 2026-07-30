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
            /* Margins: top 0.5in, right 0.5in, bottom 0.2in, left 0.5in */
            margin: 0.5in 0.5in 0.2in 0.5in;
            padding: 0;
        }
        @page {
            margin: 0.5in 0.5in 0.2in 0.5in;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .mt-5 { margin-top: 5px; }
        .mt-10 { margin-top: 10px; }
        .mt-15 { margin-top: 15px; }
        .mt-20 { margin-top: 20px; }
        .mt-30 { margin-top: 30px; }
        .content-wrapper {
            page-break-inside: avoid;
        }
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
            font-size: 12px;
            vertical-align: top;
        }
        .table-bordered th {
            text-align: center;
            font-weight: bold;
        }
        .table-bordered td {
            text-align: left;
        }
        .footer-image {
            margin-top: 120px;
            page-break-inside: avoid;
        }
        .footer-image img {
            width: 100%;
            max-height: 800px;
        }
        .header-image {
            margin: 0;
            padding: 0;
            margin-bottom: 5px;
        }
        .header-image img {
            width: 100%;
            max-height: 150px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
        }
        .date-underline {
            border-bottom: 1px solid #000;
            padding-bottom: 1px;
            display: inline-block;
            min-width: 250px;
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
    $date = $record->date ? \Carbon\Carbon::parse($record->date)->format('F d, Y') : '';
    $name = $record->name ?? '';
    $position = $record->position ?? '';
    $branch = $record->branch ?? '';
    
    // Format name for display
    $displayName = $name;
@endphp

    {{-- Header with Header.jpg --}}
    @if(!empty($headerImage))
        <div class="header-image">
            <img src="data:image/jpeg;base64,{{ $headerImage }}" alt="Travel Order Header">
        </div>
    @endif

    <div style="margin-top: 5px;">
        <div>{{ $orgBranchCode }} Memorandum Order No. ________</div>
        <div>Series of 2025</div>
        <div class="text-right" style="margin-top: 3px;">Date: <span class="underline" style="min-width: 120px;">{{ $date }}</span></div>
    </div>

    <div class="text-center" style="margin-top: 10px; margin-bottom: 5px;">
        <div class="text-bold">TRAVEL ORDER</div>
    </div>

    <div style="margin-top: 8px;">
        <p style="margin-bottom: 5px; line-height: 1.4;">
            A. The following officials or personnel are hereby authorized to travel to the
            destination indicated opposite their respective name:
        </p>

        <table class="table-bordered" style="margin-top: 5px; margin-bottom: 5px;">
            <tr>
                <th width="45%">Name / Designation/Official Station</th>
                <th width="25%">Inclusive Dates of Travel</th>
                <th width="30%">Destination</th>
            </tr>
            <tr>
                <td style="text-align: left;">
                    <div class="text-bold" style="margin-bottom: 2px;">{{ $name }}</div>
                    <div style="margin-bottom: 2px;">{{ $position }}</div>
                    <div>{{ $branch }}</div>
                </td>
                <td style="text-align: center; vertical-align: middle;">{{ $inclusive }}</td>
                <td style="text-align: left; vertical-align: middle;">{{ $destination }}</td>
            </tr>
        </table>

        <p style="margin-top: 8px; margin-bottom: 6px; line-height: 1.5;">
            B. Purpose of the Travel. The officials or personnel are authorized to travel
            for the purpose of <span class="text-bold">on an approved personal leave</span>.
        </p>

        <p style="margin-top: 6px; margin-bottom: 6px; line-height: 1.5;">
            C. The <span class="text-bold">{{ $displayName }}</span> will shoulder all expenses and relieve the Agency of any financial obligations.
        </p>

        <p style="margin-top: 6px; margin-bottom: 6px; line-height: 1.5;">
            D. The <span class="text-bold">{{ $displayName }}</span> will turn-over whatever pending matters and assignments to her designated Officer-In-Charge during absence.
        </p>

        <p style="margin-top: 6px; margin-bottom: 6px; line-height: 1.5;">
            E. The travel documentation is being undertaken per Department Order No. 25-63, s. 2025.
        </p>

        <p style="margin-top: 6px; margin-bottom: 6px; line-height: 1.5;">
            F. This Order takes effect immediately.
        </p>
    </div>

    {{-- Signature and approval section --}}
    <div style="margin-top: 15px; page-break-inside: avoid;">
        <div style="margin-top: 10px;">
            <div>Funds Available: <span class="underline" style="min-width: 200px;"></span></div>
        </div>
        
        <div style="margin-top: 10px;">
            <div class="signature-name">MARGIE F. ENGSON</div>
            <div>Budget Officer</div>
        </div>
        
        <div style="margin-top: 10px;">
            <div>Approved:</div>
        </div>

        @php
            // Resolve approver for Travel Order (Personal Leave) from approver_headers / approver_details (type_id = 2)
            // (same logic as Travel Authority report)
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
        
        <div style="margin-top: 10px;">
            <div class="signature-name">{{ $toApproverName ?? '________________________' }}</div>
            <div>{{ $toApproverPosition ?? 'Designation' }}</div>
        </div>
        
        <div style="margin-top: 10px;">
            <div>Date of Approval: <span class="date-underline"></span></div>
        </div>
    </div>

    @if(!empty($footerImage))
        <div class="footer-image" style="page-break-inside: avoid;">
            <img src="data:image/jpeg;base64,{{ $footerImage }}" alt="Travel Order Footer">
        </div>
    @endif
</body>
</html>

