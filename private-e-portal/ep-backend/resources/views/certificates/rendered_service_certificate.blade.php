<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate of Rendered Service</title>
    <style>
        @page { margin: 0; size: A4; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0 1in;
            font-size: 12pt;
        }
        header, footer {
            position: fixed;
            left: 0;
            right: 0;
            text-align: center;
            width: 100%;
        }
        header { top: 10px; }
        footer { bottom: 10px; }
        header img, footer img {
            display: block;
            margin: 0 auto;
            max-width: 100%;
            height: auto;
            object-fit: contain;
        }
        header img { max-height: 120px; }
        footer img { max-height: 120px; }
        .content { margin-top: 170px; margin-bottom: 170px; }
        p { line-height: 1.5; margin: 0 0 12px 0; text-align: justify; }
        .title { text-align: center; font-weight: bold; margin: 16px 0; text-transform: uppercase; }
        .sig { margin-top: 50px; }
        .sig-line { width: 220px; }
        .signature-block {
            position: relative;
            margin-top: 24px;
            width: 320px;
        }
        .signature-block p {
            text-align: left;
            text-align-last: left;
            text-justify: auto;
        }
        .signatory-name {
            font-weight: bold;
            line-height: 1.35;
            word-wrap: break-word;
            overflow-wrap: break-word;
            margin: 0;
        }
        .signatory-position {
            line-height: 1.35;
            margin: 0;
        }
        .sig-line-wrap {
            position: relative;
            height: 48px;
            margin-bottom: 6px;
        }
        .sig-line {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 8px;
            border-bottom: 1px solid #000;
        }
        .electronic-approval-wrapper {
            position: absolute;
            left: 38%;
            bottom: 16px;
            transform: translateX(-50%);
            z-index: 2;
            pointer-events: none;
        }
        .electronic-approval-box {
            padding: 6px 14px;
            text-align: center;
            background-color: transparent;
        }
        .electronic-approval-stamp {
            font-size: 14px;
            font-weight: bold;
            color: #000;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        .electronic-approval-date {
            margin-top: 3px;
            font-size: 8px;
            color: #000;
            line-height: 1.2;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <header>
        @if(!empty($headerImg))
            <img src="{{ $headerImg }}" alt="Header">
        @endif
    </header>

    <footer>
        @if(!empty($footerImg))
            <img src="{{ $footerImg }}" alt="Footer">
        @endif
    </footer>

    <div class="content">
        <p style="text-align:right; color:rgb(17, 16, 16); margin-bottom: 40px;">
            {{ $issueDate->format('j F Y') }}
        </p>

        <div class="title">CERTIFICATION</div>

        <p>
            This is to certify that <strong>{{ $employee->full_name }}</strong>, <strong>{{ $employee->position_name ?? 'Employee' }}</strong> under the <strong>{{ $employee->department_name ?? 'Department' }}</strong>, had rendered very satisfactory services covering the period <strong>{{ $coverStart->format('F d, Y') }} to {{ $coverEnd->format('F d, Y') }}</strong>.
        </p>

        <p>
            This certification is being issued for accounting and auditing purposes.
        </p>

        @php
            $approvalMeta = $electronicApproval ?? [];
            $level1Approval = $approvalMeta['level_1'] ?? null;
            $level2Approval = $approvalMeta['level_2'] ?? null;
            $formatSignatoryName = function ($name) {
                $normalized = trim(preg_replace('/\s+/u', ' ', (string) ($name ?? '')));

                return $normalized !== '' ? mb_strtoupper($normalized, 'UTF-8') : '';
            };
            $formatSignatoryPosition = function ($position) {
                return trim(preg_replace('/\s+/u', ' ', (string) ($position ?? '')));
            };
            $notedByName = $formatSignatoryName($notedBy ?? '');
            $notedByTitle = $formatSignatoryPosition($notedByPosition ?? '');
            $approvedByName = $formatSignatoryName($approvedBy ?? '');
            $approvedByTitle = $formatSignatoryPosition($approvedByPosition ?? '');
        @endphp

        <div class="sig" style="margin-top: 50px;">
            <p><strong>Noted By:</strong></p>
            <div class="signature-block">
                <div class="sig-line-wrap">
                    <div class="sig-line"></div>
                    @if(!empty($level1Approval['signed']))
                        <div class="electronic-approval-wrapper">
                            <div class="electronic-approval-box">
                                <div class="electronic-approval-stamp">{{ $level1Approval['status'] ?? 'SIGNED' }}</div>
                                @if(!empty($level1Approval['date']))
                                    <div class="electronic-approval-date">{{ $level1Approval['date'] }}</div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
                <p class="signatory-name">{{ $notedByName }}</p>
                <p class="signatory-position">{{ $notedByTitle }}</p>
            </div>
        </div>

        <div class="sig" style="margin-top: 60px;">
            <p><strong>Approved By:</strong></p>
            <div class="signature-block">
                <div class="sig-line-wrap">
                    <div class="sig-line"></div>
                    @if(!empty($level2Approval['signed']))
                        <div class="electronic-approval-wrapper">
                            <div class="electronic-approval-box">
                                <div class="electronic-approval-stamp">{{ $level2Approval['status'] ?? 'SIGNED' }}</div>
                                @if(!empty($level2Approval['date']))
                                    <div class="electronic-approval-date">{{ $level2Approval['date'] }}</div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
                <p class="signatory-name">{{ $approvedByName }}</p>
                <p class="signatory-position">{{ $approvedByTitle }}</p>
            </div>
        </div>
    </div>
</body>
</html>
