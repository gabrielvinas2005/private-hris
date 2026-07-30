<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>DPCR</title>
    <style>
        @page { margin: 18px 18px 22px 18px; }
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 10px; color: #000; padding: 10px; }
        .center { text-align: center; }
        .title { font-size: 12px; font-weight: bold; letter-spacing: .2px; }
        .intro { text-align: center; }
        .line { border-bottom: 1px solid #000; display: inline-block; min-width: 240px; height: 12px; vertical-align: bottom; text-align: center; padding: 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 4px 5px; vertical-align: top; }
        th { background: #0b2a66; color: #fff; font-weight: bold; text-align: center; }
        .subhead th { background: #0b2a66; }
        .no-border td { border: none; padding: 2px 0; }
        .sign-table td { height: 36px; }
        .label { font-weight: bold; }
        .small { font-size: 9px; }
        .approved-table td { padding: 10px; }
        .approved-table .value { text-align: center; height: 34px; vertical-align: middle; }
        .w-outputs { width: 18%; }
        .w-targets { width: 18%; }
        .w-budget { width: 10%; }
        .w-accountable { width: 14%; }
        .w-actual { width: 14%; }
        .w-rate { width: 4.5%; }
        .w-remarks { width: 9%; }
    </style>
</head>
<body>
    <div class="center title">DEPARTMENT PERFORMANCE COMMITMENT AND REVIEW (DPCR)</div>
    <br>
    <table class="no-border">
        <tr>
            <td class="small intro">
                I, <span class="line">{{ $headName ?? '' }}</span> (Name),
                <span class="line">{{ $headPosition ?? '' }}</span> (Position), commit to deliver and agree to be rated on the following targets in accordance with the indicated measures for the period
                <span class="line">{{ $periodText ?? '' }}</span>.
            </td>
        </tr>
    </table>
    <br>

    <table class="approved-table">
        <tr>
            <td style="width:43%; background:#0b2a66; color:#fff; font-weight:bold;">Reviewed by:</td>
            <td style="width:14%; background:#0b2a66; color:#fff; font-weight:bold;">DATE:</td>
            <td style="width:43%; background:#0b2a66; color:#fff; font-weight:bold;">Approved by:</td>
            <td style="width:14%; background:#0b2a66; color:#fff; font-weight:bold;">DATE:</td>
        </tr>
        <tr>
            <td class="value">{{ $agencyHeadName ?? '' }}</td>
            <td class="value">{{ !empty($dpcr->approved_date) ? $dpcr->approved_date : '' }}</td>
            <td class="value">{{ $agencyHeadName ?? '' }}</td>
            <td class="value">{{ !empty($dpcr->approved_date) ? $dpcr->approved_date : '' }}</td>
        </tr>
    </table>
    <br>

    <table>
        <tr>
            <th class="w-outputs" rowspan="2">Outputs</th>
            <th class="w-targets" rowspan="2">Success Indicators(Targets<br>+ Measures)</th>
            {{-- <th class="w-budget" rowspan="2">Allotted Budget</th> --}}
            {{-- <th class="w-accountable" rowspan="2">Division/Individuals Accountable</th> --}}
            <th class="w-actual" rowspan="2">Actual Accomplishments</th>
            <th colspan="4">Rating</th>
            <th class="w-remarks" rowspan="2">Remarks</th>
        </tr>
        <tr class="subhead">
            <th class="w-rate">Q</th>
            <th class="w-rate">E</th>
            <th class="w-rate">T</th>
            <th class="w-rate">A</th>
        </tr>

        @php
            $sections = [
                'core' => 'Core Functions',
                'strategic' => 'Strategic Priority',
                'support' => 'Support Functions',
            ];
            $grouped = $groupedOutputs ?? [
                'strategic' => collect($outputs ?? [])->filter(fn ($row) => ($row->function_type ?? 'core') === 'strategic')->values(),
                'core' => collect($outputs ?? [])->filter(fn ($row) => ($row->function_type ?? 'core') === 'core')->values(),
                'support' => collect($outputs ?? [])->filter(fn ($row) => ($row->function_type ?? 'core') === 'support')->values(),
            ];
            $sectionSubtotals = [];
            $categoryAvg = function ($rows) {
                $values = collect($rows)
                    ->pluck('a')
                    ->filter(fn ($v) => $v !== null && $v !== '' && is_numeric($v) && (float) $v >= 2)
                    ->map(fn ($v) => (float) $v);
                return $values->count() > 0 ? round($values->avg(), 2) : null;
            };
            $hasAnyOutput = collect($grouped)->flatten(1)->count() > 0;
            $showRating = function ($value) {
                if ($value === null || $value === '') {
                    return '';
                }
                $n = (float) $value;
                if ($n < 2) {
                    return '';
                }
                return fmod($n, 1.0) === 0.0 ? (string) (int) $n : number_format($n, 2);
            };
        @endphp

        @foreach($sections as $type => $title)
            @php $rows = $grouped[$type] ?? collect(); @endphp
            @if($rows->count() > 0)
                <tr>
                    <td colspan="8" class="label" style="background:#e8eef8;">{{ $title }}</td>
                </tr>
                @foreach($rows as $row)
                    @php
                        if (is_numeric($row->a) && (float) $row->a >= 2) { $avgVals[] = (float)$row->a; }
                    @endphp
                    <tr>
                        <td>{{ $row->outputs }}</td>
                        <td>{{ $row->target_measures }}</td>
                        <td>{{ $row->actual_accomplishments }}</td>
                        <td class="center">{{ $showRating($row->q) }}</td>
                        <td class="center">{{ $showRating($row->e) }}</td>
                        <td class="center">{{ $showRating($row->t) }}</td>
                        <td class="center">{{ is_numeric($row->a) && (float) $row->a >= 2 ? number_format((float)$row->a, 2) : '' }}</td>
                        <td>{{ $row->remarks }}</td>
                    </tr>
                @endforeach
                @php $sectionAvg = $categoryAvg($rows); @endphp
                <tr>
                    <td colspan="3" class="label">Sub-Total</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="center label">{{ is_numeric($sectionAvg) ? number_format($sectionAvg, 2) : '' }}</td>
                    <td></td>
                </tr>
            @endif
        @endforeach

        @if(!$hasAnyOutput)
            <tr>
                <td colspan="8" class="center">No outputs</td>
            </tr>
        @endif
        <tr>
            <td class="label" colspan="3">Average Rating</td>
            @php
                $overall = count($avgVals) ? array_sum($avgVals)/count($avgVals) : null;
            @endphp
            <td colspan="4" class="center label">{{ is_numeric($overall) ? number_format($overall, 2) : '' }}</td>
            <td></td>
        </tr>
    </table>

    <br>
    {{-- <table>
        <tr>
            <th style="width:35%;">Category</th>
            <th style="width:35%;">MFO</th>
            <th style="width:30%;">Rating</th>
        </tr>
        @php
            $strategicRows = $grouped['strategic'] ?? collect();
            $coreRows = $grouped['core'] ?? collect();
            $supportRows = $grouped['support'] ?? collect();
            $strategicAvg = $categoryAvg($strategicRows);
            $coreAvg = $categoryAvg($coreRows);
            $supportAvg = $categoryAvg($supportRows);
        @endphp
        <tr><td>Strategic Priority (%)</td><td class="center">{{ $strategicRows->count() ?: '' }}</td><td class="center">{{ is_numeric($strategicAvg) ? number_format($strategicAvg, 2) : '' }}</td></tr>
        <tr><td>Core Functions (%)</td><td class="center">{{ $coreRows->count() ?: '' }}</td><td class="center">{{ is_numeric($coreAvg) ? number_format($coreAvg, 2) : '' }}</td></tr>
        <tr><td>Support Functions (%)</td><td class="center">{{ $supportRows->count() ?: '' }}</td><td class="center">{{ is_numeric($supportAvg) ? number_format($supportAvg, 2) : '' }}</td></tr>
        <tr><td class="label">Total Overall Rating</td><td></td><td></td></tr>
        <tr><td class="label">Final Average Rating</td><td></td><td class="center label">{{ is_numeric($overall) ? number_format($overall, 2) : '' }}</td></tr>
        <tr><td class="label">Adjectival Rating</td><td colspan="2">
            @php
                $adj = '';
                if (is_numeric($overall)) {
                    if ($overall >= 4.50) $adj = 'Outstanding';
                    elseif ($overall >= 3.50) $adj = 'Very Satisfactory';
                    elseif ($overall >= 2.50) $adj = 'Satisfactory';
                    else $adj = 'Unsatisfactory';
                }
            @endphp
            {{ $adj }}
        </td></tr>
    </table> --}}

    <br>
    <table class="sign-table">
        <tr>
            <td class="label" style="width:30%;">Discussed with:</td>
            <td class="label" style="width:10%;">Date:</td>
            <td class="label" style="width:30%;">Assessed by:</td>
            <td class="label" style="width:10%;">Date:</td>
            <td class="label" style="width:20%;">Final Rating by:</td>
            <td class="label" style="width:10%;">Date:</td>
        </tr>
        <tr>
            <td class="center">
                <div style="margin-top:12px; font-weight:bold;">{{ $planningOfficerName ?? '' }}</div>
                <div class="small">Employee</div>
            </td>
            <td class="center">{{ !empty($dpcr->planning_officer_date) ? $dpcr->planning_officer_date : '' }}</td>
            <td class="center">
                <div class="cert-text">I certify that I discussed my assessment of the performance with the employee.</div>
                <div style="margin-top:12px; font-weight:bold;">{{ $pmtName ?? '' }}</div>
                <div class="small">Supervisor</div>
            </td>
            <td class="center">{{ !empty($dpcr->assessed_date) ? $dpcr->assessed_date : '' }}</td>
            <td class="center">
                <div style="margin-top:12px; font-weight:bold;">{{ $finalRaterName ?? '' }}</div>
                <div class="small">Head of Office</div>
            </td>
            <td class="center">{{ !empty($dpcr->final_rater_date) ? $dpcr->final_rater_date : '' }}</td>
        </tr>
    </table>
</body>
</html>

