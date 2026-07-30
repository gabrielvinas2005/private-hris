<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Travel Order</title>
    <style>
        * { box-sizing: border-box; }
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
        table { border-collapse: collapse; width: 100%; }
        .table-bordered th, .table-bordered td {
            border: 1px solid #000;
            padding: 4px 6px;
        }
        .table-bordered th { text-align: center; }
        .footer {
            font-size: 9px;
            margin-top: 40px;
            text-align: center;
        }
        .footer-image img {
            padding-top: 7%;
            width: 100%;
        }
    </style>
</head>
<body>
@php
    use App\Helpers\BranchHelper;

    $record = $ob[0];
    $branchCode = BranchHelper::getMainBranchCode();
    $fromDate = $record->date_time_from ? \Carbon\Carbon::parse($record->date_time_from)->format('F d, Y') : '';
    $toDate = $record->date_time_to ? \Carbon\Carbon::parse($record->date_time_to)->format('F d, Y') : '';
    $inclusive = trim($fromDate . ($toDate && $toDate !== $fromDate ? ' - ' . $toDate : ''));
    $destination = $record->client ?? '';
    $purpose = $record->purpose ?? '';
    $funds = $record->funds ?? '';
    $date = $record->date ? \Carbon\Carbon::parse($record->date)->format('F d, Y') : '';
    $name = $record->name ?? '';
    $position = $record->position ?? '';
    $branch = $record->branch ?? '';
    
    // Format name with title prefix if needed
    $fullName = $name;
    if (!empty($name) && stripos($name, 'Mr.') === false && stripos($name, 'Ms.') === false && stripos($name, 'Mrs.') === false) {
        $fullName = 'Mr. ' . $name;
    }
@endphp

    <div class="mt-10">
        <div>MEMORANDUM ORDER NO. ________</div>
        <div>Series of 2025</div>
        <div class="text-right mt-5">Date: <span class="underline" style="min-width: 120px;">{{ $date }}</span></div>
    </div>

    <div class="text-center mt-20 mb-10">
        <div class="text-bold">TRAVEL ORDER</div>
    </div>

    <div class="mt-10">
        <ol style="padding-left: 20px; line-height: 1.6;">
            <li style="margin-bottom: 15px;">
                In the interest of the service, <span class="text-bold">{{ $fullName }}</span>, is hereby authorized
                to travel to <span class="text-bold">{{ $destination }}</span> on <span class="text-bold">{{ $inclusive }}</span> (inclusive of travel
                time) to facilitate the <span class="text-bold">{{ $purpose }}</span>@if(!empty($destination)) to be held at <span class="text-bold">{{ $destination }}</span>@endif.
            </li>
            <li style="margin-bottom: 15px;">
                {{ $branchCode }} shall shoulder the incidental expenses and applicable per diem charged to {{ $branchCode }}
                Trust Fund subject to applicable government rules and regulations.
            </li>
            <li style="margin-bottom: 15px;">
                The above-mentioned personnel shall submit within thirty (30) days the required
                Certificate of Travel Completed, together with the transportation tickets and Certificate of
                Appearance and other necessary supporting papers, if any, and to refund any excess
                cash advance within ten (10) days upon return to official station.
            </li>
            <li style="margin-bottom: 15px;">
                This Order shall take effect immediately.
            </li>
        </ol>
    </div>

    @php
        // Resolve approver for Travel Order (LDSD) from approver_headers / approver_details (type_id = 2)
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

    {{-- Signature block left-aligned --}}
    <div class="mt-30">
        <div class="mt-20">
            <div class="text-bold">{{ $toApproverName ?? '________________________' }}</div>
            <div>{{ $toApproverPosition ?? 'Designation' }}</div>
        </div>
    </div>
</body>
</html>


