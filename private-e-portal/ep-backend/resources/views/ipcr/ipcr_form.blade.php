<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>IPCR</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 8px; font-weight: bold; font-size: 13px; }
        .meta { width: 100%; margin-bottom: 8px; border-collapse: collapse; }
        .meta td { padding: 4px; font-size: 11px; border: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #2f4a7a; padding: 5px 6px; vertical-align: top; }
        th { background: #1f3a70; color: #fff; font-weight: bold; font-size: 10px; }
        .approval-top-table th { text-align: left; padding-left: 8px; }
        .approval-top-table td { height: 52px; vertical-align: bottom; }
        .sign-name { font-weight: bold; text-transform: uppercase; text-align: center; min-height: 18px; }
        .sign-caption { font-size: 9px; text-align: center; margin-top: 4px; font-weight: bold; }
        .sign-date { text-align: center; font-size: 10px; }
        .performance-table { margin-bottom: 8px; }
        .performance-table th { text-align: center; }
        .performance-table .rating-header { background: #1f3a70; }
        .category-row { background: #e7ecf7; font-weight: bold; text-align: left; }
        .category-row td { padding: 5px 8px; }
        .section-title { background: #1f3a70; color: #fff; padding: 6px; font-weight: bold; font-size: 10px; }
        .comments-table td { min-height: 48px; }
        .bottom-sign-table th { text-align: center; font-size: 9px; }
        .bottom-sign-table td { text-align: center; vertical-align: bottom; height: 58px; }
        .bottom-sign-table .sign-name { font-size: 9px; line-height: 1.2; }
        .bottom-sign-table .sign-caption { font-size: 8px; }
        .cert-text { font-size: 7px; text-align: justify; line-height: 1.25; margin-bottom: 4px; }
        .avg-table th { text-align: center; }
        .avg-table td { text-align: center; }
        .avg-table td:first-child { text-align: left; }
        .nowrap { white-space: nowrap; }
        .ratee-block { text-align: right; }
        .ratee-line { display: inline-block; min-width: 220px; border-bottom: 1px solid #333; min-height: 14px; }
    </style>
</head>
<body>

    <div class="header">INDIVIDUAL PERFORMANCE COMMITMENT AND REVIEW (IPCR)</div>

    @php
        $periodFrom = $ipcr->period_start ? \Carbon\Carbon::parse($ipcr->period_start)->format('M j') : '_________';
        $periodTo   = $ipcr->period_end ? \Carbon\Carbon::parse($ipcr->period_end)->format('M j') : '_________';
        $periodYr   = $ipcr->period_end ? \Carbon\Carbon::parse($ipcr->period_end)->format('Y') : '____';
        $rateeName  = $ipcr->employee_name ?: '_____________________________';
        $division   = $ipcr->division ?: '_____________________________';
        $sections = [
            'core' => 'Core Functions',
            'strategic' => 'Strategic Functions',
            'support' => 'Support Functions',
        ];
        $grouped = $groupedOutputs ?? [
            'core' => collect($outputs ?? [])->filter(fn ($row) => ($row->function_type ?? 'core') === 'core')->values(),
            'strategic' => collect($outputs ?? [])->filter(fn ($row) => ($row->function_type ?? 'core') === 'strategic')->values(),
            'support' => collect($outputs ?? [])->filter(fn ($row) => ($row->function_type ?? 'core') === 'support')->values(),
        ];
        $formatDate = function ($date) {
            if (empty($date)) {
                return '';
            }
            try {
                return \Carbon\Carbon::parse($date)->format('M d, Y');
            } catch (\Exception $e) {
                return $date;
            }
        };
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
        $hasAnyOutput = collect($grouped)->flatten(1)->count() > 0;
        $avgColumn = function ($rows, $column) {
            $values = collect($rows)
                ->pluck($column)
                ->filter(fn ($v) => $v !== null && $v !== '' && is_numeric($v) && (float) $v >= 2)
                ->map(fn ($v) => (float) $v);
            return $values->count() > 0 ? round($values->avg(), 2) : null;
        };
        $allOutputs = collect($outputs ?? []);
        $coreRows = $grouped['core'] ?? collect();
        $strategicRows = $grouped['strategic'] ?? collect();
        $supportRows = $grouped['support'] ?? collect();
        $totalQ = $avgColumn($allOutputs, 'quality_rating');
        $totalE = $avgColumn($allOutputs, 'efficiency_rating');
        $totalT = $avgColumn($allOutputs, 'timeliness_rating');
        $totalA = $avgColumn($allOutputs, 'average_rating');
        $getAdjectivalRating = function ($rating) {
            if ($rating === null || (float) $rating <= 0) {
                return '';
            }
            $rating = (float) $rating;
            if ($rating >= 4.5) return 'Outstanding';
            if ($rating >= 3.5) return 'Very Satisfactory';
            if ($rating >= 2.5) return 'Satisfactory';
            return 'Unsatisfactory';
        };
    @endphp

    <table class="meta" style="margin-bottom:10px;">
        <tr>
            <td colspan="2">
                <span>I,&nbsp;</span>
                <span style="display:inline-block;min-width:220px;border-bottom:1px solid #333;">{{ $rateeName }}</span>
                <span>,&nbsp;of the&nbsp;</span>
                <span style="display:inline-block;min-width:220px;border-bottom:1px solid #333;">{{ $division }}</span>
                <span>&nbsp;Division of&nbsp;</span>
                <span style="display:inline-block;min-width:260px;border-bottom:1px solid #333;">{{ $orgCompanyName }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding-top:6px;">
                <span>commit to deliver and agree to be rated on the attainment of the following targets in accordance with the indicated measures for the period&nbsp;</span>
                <span style="display:inline-block;min-width:70px;border-bottom:1px solid #333;text-align:center;">{{ $periodFrom }}</span>
                <span>&nbsp;to&nbsp;</span>
                <span style="display:inline-block;min-width:70px;border-bottom:1px solid #333;text-align:center;">{{ $periodTo }}</span>
                <span>,&nbsp;20&nbsp;</span>
                <span style="display:inline-block;min-width:40px;border-bottom:1px solid #333;text-align:center;">{{ $periodYr }}</span>
                <span>.</span>
            </td>
        </tr>
        <tr>
            <td style="width:60%;"></td>
            <td class="ratee-block" style="width:40%;">
                <div class="ratee-line">&nbsp;</div>
                <div style="font-size:10px;margin-top:4px;">Ratee</div>
                <div style="margin-top:6px;font-size:10px;">Date:&nbsp;<span style="display:inline-block;min-width:140px;border-bottom:1px solid #333;">{{ $formatDate($ipcr->employee_date ?? null) }}</span></div>
            </td>
        </tr>
    </table>

    <table class="approval-top-table" style="margin-bottom:8px;">
        <thead>
            <tr>
                <th>Reviewed by</th>
                <th style="width:14%;">Date</th>
                <th>Approved by</th>
                <th style="width:14%;">Date</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div class="sign-name">{{ $ipcr->reviewed_by ?? '' }}</div>
                    <div class="sign-caption">Immediate Superior</div>
                </td>
                <td class="sign-date">{{ $formatDate($ipcr->reviewed_date ?? null) }}</td>
                <td>
                    <div class="sign-name">{{ $ipcr->approved_by ?? '' }}</div>
                    <div class="sign-caption">Head of Agency</div>
                </td>
                <td class="sign-date">{{ $formatDate($ipcr->approved_date ?? null) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="performance-table">
        <thead>
            <tr>
                <th style="width:18%;">Output</th>
                <th style="width:24%;">Success Indicators (Targets + Measures)</th>
                <th style="width:24%;">Actual Accomplishments</th>
                <th colspan="4" class="rating-header">Rating</th>
                <th style="width:12%;">Remarks</th>
            </tr>
            <tr>
                <th></th>
                <th></th>
                <th></th>
                <th style="width:5%;">Q</th>
                <th style="width:5%;">E</th>
                <th style="width:5%;">T</th>
                <th style="width:5%;">A</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($sections as $type => $title)
                @php $rows = $grouped[$type] ?? collect(); @endphp
                @if($rows->count() > 0)
                    <tr class="category-row">
                        <td colspan="8">{{ $title }}</td>
                    </tr>
                    @foreach($rows as $row)
                        <tr>
                            <td>{{ $row->output }}</td>
                            <td>{{ $row->success_indicators }}</td>
                            <td>{{ $row->accomplishment }}</td>
                            <td class="nowrap" style="text-align:center;">{{ $showRating($row->quality_rating) }}</td>
                            <td class="nowrap" style="text-align:center;">{{ $showRating($row->efficiency_rating) }}</td>
                            <td class="nowrap" style="text-align:center;">{{ $showRating($row->timeliness_rating) }}</td>
                            <td class="nowrap" style="text-align:center;">{{ $row->average_rating !== null && (float) $row->average_rating >= 2 ? number_format((float) $row->average_rating, 2) : '' }}</td>
                            <td>{{ $row->remarks }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
            @if(!$hasAnyOutput)
                <tr>
                    <td colspan="8" style="text-align:center;">No entries</td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- <table class="avg-table" style="margin-bottom:8px;">
        <thead>
            <tr>
                <th style="width:34%;">Category</th>
                <th style="width:16%;">Q</th>
                <th style="width:16%;">E</th>
                <th style="width:16%;">T</th>
                <th style="width:18%;">A</th>
            </tr>
        </thead>
        <tbody>
            @if($coreRows->count() > 0)
                <tr>
                    <td>Core Functions</td>
                    <td>{{ $avgColumn($coreRows, 'quality_rating') !== null ? number_format($avgColumn($coreRows, 'quality_rating'), 2) : '' }}</td>
                    <td>{{ $avgColumn($coreRows, 'efficiency_rating') !== null ? number_format($avgColumn($coreRows, 'efficiency_rating'), 2) : '' }}</td>
                    <td>{{ $avgColumn($coreRows, 'timeliness_rating') !== null ? number_format($avgColumn($coreRows, 'timeliness_rating'), 2) : '' }}</td>
                    <td>{{ $avgColumn($coreRows, 'average_rating') !== null ? number_format($avgColumn($coreRows, 'average_rating'), 2) : '' }}</td>
                </tr>
            @endif
            @if($strategicRows->count() > 0)
                <tr>
                    <td>Strategic Functions</td>
                    <td>{{ $avgColumn($strategicRows, 'quality_rating') !== null ? number_format($avgColumn($strategicRows, 'quality_rating'), 2) : '' }}</td>
                    <td>{{ $avgColumn($strategicRows, 'efficiency_rating') !== null ? number_format($avgColumn($strategicRows, 'efficiency_rating'), 2) : '' }}</td>
                    <td>{{ $avgColumn($strategicRows, 'timeliness_rating') !== null ? number_format($avgColumn($strategicRows, 'timeliness_rating'), 2) : '' }}</td>
                    <td>{{ $avgColumn($strategicRows, 'average_rating') !== null ? number_format($avgColumn($strategicRows, 'average_rating'), 2) : '' }}</td>
                </tr>
            @endif
            @if($supportRows->count() > 0)
                <tr>
                    <td>Support Functions</td>
                    <td>{{ $avgColumn($supportRows, 'quality_rating') !== null ? number_format($avgColumn($supportRows, 'quality_rating'), 2) : '' }}</td>
                    <td>{{ $avgColumn($supportRows, 'efficiency_rating') !== null ? number_format($avgColumn($supportRows, 'efficiency_rating'), 2) : '' }}</td>
                    <td>{{ $avgColumn($supportRows, 'timeliness_rating') !== null ? number_format($avgColumn($supportRows, 'timeliness_rating'), 2) : '' }}</td>
                    <td>{{ $avgColumn($supportRows, 'average_rating') !== null ? number_format($avgColumn($supportRows, 'average_rating'), 2) : '' }}</td>
                </tr>
            @endif
            <tr>
                <td><strong>Total Overall Rating</strong></td>
                <td><strong>{{ $totalQ !== null ? number_format($totalQ, 2) : '' }}</strong></td>
                <td><strong>{{ $totalE !== null ? number_format($totalE, 2) : '' }}</strong></td>
                <td><strong>{{ $totalT !== null ? number_format($totalT, 2) : '' }}</strong></td>
                <td><strong>{{ $totalA !== null ? number_format($totalA, 2) : '' }}</strong></td>
            </tr>
            <tr>
                <td><strong>Adjectival Rating</strong></td>
                <td colspan="3"></td>
                <td><strong>{{ $getAdjectivalRating($totalA) }}</strong></td>
            </tr>
        </tbody>
    </table> --}}

    <table class="comments-table" style="margin-bottom:8px;">
        <thead>
            <tr>
                <th class="section-title" style="text-align:left;">Comments and Recommendations for Development Purposes</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $ipcr->comments }}</td>
            </tr>
        </tbody>
    </table>

    <table class="bottom-sign-table">
        <thead>
            <tr>
                <th style="width:22%;">Discussed with</th>
                <th style="width:10%;">Date</th>
                <th style="width:34%;">Assessed by</th>
                <th style="width:10%;">Date</th>
                <th style="width:16%;">Final Rating by</th>
                <th style="width:8%;">Date</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div class="sign-name">{{ $rateeName }}</div>
                    <div class="sign-caption">Employee</div>
                </td>
                <td class="sign-date">{{ $formatDate($ipcr->employee_date ?? null) }}</td>
                <td>
                    <div class="cert-text">I certify that I discussed my assessment of the performance with the employee.</div>
                    <div class="sign-name">{{ $ipcr->assessed_by ?? '' }}</div>
                    <div class="sign-caption">Supervisor</div>
                </td>
                <td class="sign-date">{{ $formatDate($ipcr->assessed_date ?? null) }}</td>
                <td>
                    <div class="sign-name">{{ $ipcr->final_rater ?? '' }}</div>
                    <div class="sign-caption">Head of Office</div>
                </td>
                <td class="sign-date">{{ $formatDate($ipcr->final_rate_date ?? null) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="legend">
        Legend: Q = Quality, E = Efficiency, T = Timeliness, A = Average
    </div>
</body>
</html>
