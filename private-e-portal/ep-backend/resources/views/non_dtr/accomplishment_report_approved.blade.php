<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Accomplishment Report</title>
    <style>
        @page { margin: 36px 48px; }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.45;
        }
        .center { text-align: center; }
        .title {
            font-size: 16px;
            font-weight: bold;
            text-decoration: underline;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }
        .period-range {
            color: #c00;
            text-decoration: underline;
            font-weight: bold;
            margin-bottom: 22px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .meta-table td {
            padding: 4px 0;
            vertical-align: top;
        }
        .meta-label {
            width: 72px;
            font-weight: normal;
        }
        .meta-value {
            border-bottom: 1px solid #000;
            min-height: 16px;
            padding: 0 4px 2px;
        }
        .doc-type {
            text-align: right;
            font-weight: bold;
            padding-top: 4px;
        }
        .section-title {
            font-weight: bold;
            margin: 14px 0 8px;
        }
        ul {
            margin: 0;
            padding-left: 18px;
        }
        li {
            margin-bottom: 8px;
        }
        .entry-date {
            font-weight: bold;
        }
        .sign-blocks {
            margin-top: 48px;
        }
        .sign-block {
            width: 55%;
            margin: 0 0 36px 0;
            text-align: left;
        }
        .sign-block:last-child {
            margin-bottom: 0;
        }
        .sign-label {
            margin-bottom: 4px;
        }
        .sign-line {
            border-bottom: 1px solid #000;
            height: 28px;
            margin-bottom: 4px;
        }
        .sign-line-wrap {
            position: relative;
            height: 48px;
            margin-bottom: 4px;
            width: 85%;
        }
        .sign-line-on-stamp {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 8px;
            border-bottom: 1px solid #000;
        }
        .sign-name {
            font-weight: bold;
            text-align: left;
            line-height: 1.35;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .sign-role {
            text-align: left;
            font-size: 10px;
            line-height: 1.35;
        }
        .electronic-approval-wrapper {
            position: absolute;
            left: 48%;
            bottom: 16px;
            transform: translateX(-50%);
            z-index: 2;
            pointer-events: none;
            page-break-inside: avoid;
        }
        .electronic-approval-box {
            padding: 4px 10px;
            text-align: center;
            background-color: transparent;
        }
        .electronic-approval-stamp {
            font-size: 11px;
            font-weight: bold;
            color: #000;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .electronic-approval-date {
            margin-top: 2px;
            font-size: 7px;
            color: #000;
            line-height: 1.2;
            white-space: nowrap;
        }
        .task-block {
            margin-bottom: 10px;
        }
        .task-label {
            font-weight: bold;
        }
    </style>
</head>
<body>
    @php
        $formatSignatoryName = function ($name) {
            $normalized = trim(preg_replace('/\s+/u', ' ', (string) ($name ?? '')));
            return $normalized !== '' ? mb_strtoupper($normalized, 'UTF-8') : '';
        };
        $formatSignatoryPosition = function ($position) {
            return trim(preg_replace('/\s+/u', ' ', (string) ($position ?? '')));
        };
        $preparedByName = $formatSignatoryName($employeeName ?? '');
        $preparedByPosition = $formatSignatoryPosition($employeePosition ?? '');
        $notedByName = $formatSignatoryName($superiorName ?? '');
        $notedByPosition = $formatSignatoryPosition($superiorPosition ?: 'Immediate Superior');
    @endphp

    <div class="center title">ACCOMPLISHMENT REPORT</div>
    <div class="center period-range">{{ $periodLabel }}</div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Name:</td>
            <td class="meta-value" style="width: 42%;">{{ $employeeName }}</td>
            <td class="doc-type" rowspan="4">Accomplishment Report</td>
        </tr>
        <tr>
            <td class="meta-label">Position:</td>
            <td class="meta-value">{{ $employeePosition }}</td>
        </tr>
        <tr>
            <td class="meta-label">Division:</td>
            <td class="meta-value">{{ $employeeDivision }}</td>
        </tr>
        <tr>
            <td class="meta-label">Date:</td>
            <td class="meta-value">{{ $reportDate }}</td>
        </tr>
    </table>

    <div class="section-title">• Breakdown of accomplishment</div>

    @if(!empty($task->task_1))
        <div class="task-block">
            <span class="task-label">Main Tasks:</span> {{ $task->task_1 }}
        </div>
    @endif
    @if(!empty($task->task_2))
        <div class="task-block">
            <span class="task-label">Secondary Tasks:</span> {{ $task->task_2 }}
        </div>
    @endif
    @if(!empty($task->task_3))
        <div class="task-block">
            <span class="task-label">Additional Tasks:</span> {{ $task->task_3 }}
        </div>
    @endif

    <ul>
        @forelse($entries as $entry)
            <li>
                <span class="entry-date">{{ $entry->work_date_label }}</span>
                @if(!empty($entry->location))
                    — {{ $entry->location }}
                @endif
                <br>
                {{ $entry->accomplishments }}
                @if(!empty($entry->output_description))
                    <br><em>Output:</em> {{ $entry->output_description }}
                @endif
            </li>
        @empty
            <li>No daily entries recorded.</li>
        @endforelse
    </ul>

    <div class="sign-blocks">
        <div class="sign-block">
            <div class="sign-label">Prepared By:</div>
            <div class="sign-line"></div>
            <div class="sign-name">{{ $preparedByName }}</div>
            <div class="sign-role">{{ $preparedByPosition }}</div>
        </div>

        <div class="sign-block">
            <div class="sign-label">Noted By:</div>
            <div class="sign-line-wrap">
                @if(!empty($electronic_approval))
                <div class="electronic-approval-wrapper">
                    <div class="electronic-approval-box">
                        <div class="electronic-approval-stamp">{{ $electronic_approval['status'] ?? 'SIGNED' }}</div>
                        @if(!empty($electronic_approval['signed_date']))
                            <div class="electronic-approval-date">{{ $electronic_approval['signed_date'] }}</div>
                        @endif
                    </div>
                </div>
                @endif
                <div class="sign-line-on-stamp"></div>
            </div>
            <div class="sign-name">{{ $notedByName }}</div>
            <div class="sign-role">{{ $notedByPosition }}</div>
        </div>
    </div>
</body>
</html>
