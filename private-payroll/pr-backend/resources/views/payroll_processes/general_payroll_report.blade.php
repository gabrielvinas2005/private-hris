@php
    $money = fn($value) => number_format($value ?? 0, 2, '.', ',');
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>General Payroll Report</title>
    <style>

        @page {
            size: 14in 8.5in; /* legal landscape similar to legacy sample */
            margin: 0.15in;
        }

        body {
            counter-reset: page;
            /* Dompdf is more consistent with body padding than multi-value @page margins */
            padding-top: 0.95in;  /* space for repeating header */
            padding-bottom: 1.8in; /* space for footer / page number */
        }

        .pagination {
            position: fixed;
            bottom: 0.15in;
            right: 0.5in;
            font-size: 14px;
            color: #7ba3d1;
            font-style: italic;
            font-weight: 400;
            z-index: 9999;
        }

        table { 
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        html,
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8px;
            color: #163a74;
            margin: 0;
        }

        .wrapper {
            padding: 0 2px 30px;
        }

        .pdf-header {
            position: fixed;
            top: 0.15in;
            left: 0.15in;
            right: 0.15in;
            /* Keep header compact so table has room */
            height: 0.85in;
        }

        .header-title {
            text-align: center;
            margin-bottom: 8px;
        }

        .header-title h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 5px;
            font-weight: 700;
        }

        .header-subtext {
            text-align: center;
            font-size: 10px;
            margin-bottom: 16px;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            font-size: 8px;
            margin-bottom: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            table-layout: auto;
        }

        th,
        td {
            padding: 2px 0.5px;
        }

        td {
            white-space: nowrap;
            border-bottom: 0.7px solid #1a4c8f;
        }

        th {
            font-size: 9px;
            text-transform: uppercase;
            text-align: center;
            white-space: normal;
            border-bottom: 0.7px solid #1a4c8f;
        }

        .stacked-header {
            min-height: 48px;
            padding: 6px 3px;
        }

        .stacked-header span,
        .cell-stack span {
            display: block;
            line-height: 1.25;
        }

        .label {
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .sublabel {
            font-size: 8px;
            font-weight: 500;
            letter-spacing: 0;
        }

        .col-number,
        .col-name,
        .col-money {
            width: auto;
            padding: 3px 0.5px;
        }

        .col-number {
            text-align: center;
        }

        .col-name {
            text-align: left;
        }

        .col-money {
            text-align: right;
        }

        .cell-stack {
            white-space: normal;
            text-align: right;
        }

        .cell-stack span {
            line-height: 1.2;
        }

        .cell-stack span:last-child {
            color: #4d6ba5;
            font-size: 8px;
        }

        .totals-row td {
            font-weight: 700;
            background: #f0f4ff;
        }

        .remarks-line {
            border-top: 1px dotted #5374b0;
            margin: 10px 0 16px;
        }

        .pdf-footer {
            position: fixed;
            bottom: 0in;
            left: 0.15in;
            right: 0.15in;
            background: #fff;
            padding-top: 6px;
        }


        .signatories {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .sign-card {
            flex: 1 1 30%;
            padding: 10px;
            min-width: 240px;
            font-size: 10px;
        }

        .sign-card p {
            margin: 4px 0;
        }

        .sign-name {
            margin-top: 35px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .checkbox {
            display: inline-block;
            width: 9px;
            height: 9px;
            border: 0.7px solid #1a4c8f;
            margin-right: 4px;
            vertical-align: middle;
        }


    </style>
</head>

<body>
    <div class="pdf-header">
        <div class="header-title">
            <h1>GENERAL PAYROLL</h1>
        </div>
        <div class="header-subtext">
            <strong>WE HEREBY ACKNOWLEDGE to have received of the Phil. Trade Training Center the sums therein specified opposite our respective names before the period - - - - - - Month of
            {{ $payroll_month ?? '_________' }} {{ $payroll_year ?? date('Y') }} except as noted otherwise in the Remarks column</strong>
        </div>
        <div class="meta-row">
            <div>DIVISION / OFFICE: <strong>{{ $departmentName ?? ($payrolls[0]->department ?? '') }}</strong></div>
        </div>
    </div>
    <div class="wrapper">
        @php
            $incomeLookup = collect($incomes ?? [])
                ->groupBy('employee_id')
                ->map(fn ($items) => $items->keyBy('income_id')->map(fn ($item) => (array) $item)->toArray())
                ->toArray();

            $deductionLookup = collect($deductions ?? [])
                ->groupBy('employee_id')
                ->map(fn ($items) => $items->keyBy('deduction_id')->map(fn ($item) => (array) $item)->toArray())
                ->toArray();

            $peraHeader = collect($income_headers ?? [])->first(fn ($header) => stripos($header->income ?? '', 'pera') !== false);
            $mpliteHeader = collect($deduction_headers ?? [])->first(fn ($header) => stripos($header->deduction ?? '', 'mplite') !== false);
            $pabahayHeader = collect($deduction_headers ?? [])->first(fn ($header) => stripos($header->deduction ?? '', 'pabahay') !== false);
            $consoHeader = collect($deduction_headers ?? [])->first(fn ($header) => stripos($header->deduction ?? '', 'conso') !== false);
            $eaHeader = collect($deduction_headers ?? [])->first(fn ($header) => (
                strtoupper(trim((string) ($header->deduction ?? ''))) === 'EA'
                || strtoupper(trim((string) ($header->deduction ?? ''))) === 'E A'
                || stripos($header->deduction ?? '', 'emergency allowance') !== false
            ));
            $geHeader = collect($deduction_headers ?? [])->first(fn ($header) => stripos($header->deduction ?? '', 'gsis ge') !== false);
            $cplHeader = collect($deduction_headers ?? [])->first(fn ($header) => stripos($header->deduction ?? '', 'gsis cpl') !== false)
                ?? collect($deduction_headers ?? [])->first(fn ($header) => stripos($header->deduction ?? '', 'cpl') !== false);
            $pPremHeader = collect($deduction_headers ?? [])->first(fn ($header) => (
                stripos($header->deduction ?? '', 'p prem') !== false
                || stripos($header->deduction ?? '', 'p_prem') !== false
                || stripos($header->deduction ?? '', 'p-prem') !== false
            ));
            $pMplHeader = collect($deduction_headers ?? [])->first(fn ($header) => (
                stripos($header->deduction ?? '', 'p mpl') !== false
                || stripos($header->deduction ?? '', 'p_mpl') !== false
                || stripos($header->deduction ?? '', 'p-mpl') !== false
                || stripos($header->deduction ?? '', 'mpl') !== false
                || stripos($header->deduction ?? '', 'multi purpose loan') !== false
                || stripos($header->deduction ?? '', 'multipurpose loan') !== false
            ));
            $gliteHeader = collect($deduction_headers ?? [])->first(fn ($header) => (
                stripos($header->deduction ?? '', 'glite') !== false
                || stripos($header->deduction ?? '', 'g-lite') !== false
                || strtoupper(trim((string) ($header->deduction ?? ''))) === 'GSIS GLITE'
            ));
            $solarHeader = collect($deduction_headers ?? [])->first(fn ($header) => stripos($header->deduction ?? '', 'solar') !== false);
            $hmoDepHeader = collect($deduction_headers ?? [])->first(fn ($header) => (
                stripos($header->deduction ?? '', 'hmo dep') !== false
                || stripos($header->deduction ?? '', 'hmo dependent') !== false
            ));
            $gsisFlexHeader = collect($deduction_headers ?? [])->first(fn ($header) => (
                stripos($header->deduction ?? '', 'gsisflex') !== false
                || stripos($header->deduction ?? '', 'gsis flex') !== false
            ));
            $coopHeader = collect($deduction_headers ?? [])->first(fn ($header) => (
                stripos($header->deduction ?? '', 'coop') !== false
                || stripos($header->deduction ?? '', 'cooperative') !== false
            ));
            $pagibigMp2Header = collect($deduction_headers ?? [])->first(fn ($header) => (
                stripos($header->deduction ?? '', 'pagibig mp2') !== false
                || stripos($header->deduction ?? '', 'pag-ibig mp2') !== false
                || stripos($header->deduction ?? '', 'mp2') !== false
            ));
            $pagibigMp22Header = collect($deduction_headers ?? [])->first(fn ($header) => (
                stripos($header->deduction ?? '', 'pagibig mp2-2') !== false
                || stripos($header->deduction ?? '', 'pag-ibig mp2-2') !== false
                || stripos($header->deduction ?? '', 'mp2-2') !== false
            ));
            $loanGsiserHeader = collect($deduction_headers ?? [])->first(fn ($header) => (
                stripos($header->deduction ?? '', 'gsiser') !== false
                || stripos($header->deduction ?? '', 'gsis er') !== false
            ));
            $pagcalHeader = collect($deduction_headers ?? [])->first(fn ($header) => (
                stripos($header->deduction ?? '', 'pagibig calamity loan') !== false
                || stripos($header->deduction ?? '', 'pag-ibig calamity loan') !== false
                || stripos($header->deduction ?? '', 'calamity loan') !== false
                || stripos($header->deduction ?? '', 'pagcal') !== false
            ));
            $polLoanHeader = collect($deduction_headers ?? [])->first(fn ($header) => (
                stripos($header->deduction ?? '', 'gsis policy loan') !== false
                || stripos($header->deduction ?? '', 'policy loan') !== false
                || stripos($header->deduction ?? '', 'pol loan') !== false
            ));
            $resSalHeader = collect($deduction_headers ?? [])->first(fn ($header) => (
                stripos($header->deduction ?? '', 'res sal') !== false
                || stripos($header->deduction ?? '', 'ressal') !== false
                || stripos($header->deduction ?? '', 'res salary') !== false
            ));
            /**
             * ESP/OTHERS should be a strict residual bucket:
             * total payroll_deductions for employee minus deduction IDs that already
             * feed dedicated displayed columns in this report.
             */
            $computeEspOthers = function ($employeeId) use (
                $deductionLookup,
                $geHeader,
                $cplHeader,
                $pPremHeader,
                $pMplHeader,
                $mpliteHeader,
                $consoHeader,
                $polLoanHeader,
                $pagcalHeader,
                $gliteHeader,
                $solarHeader,
                $hmoDepHeader,
                $eaHeader,
                $coopHeader,
                $pagibigMp2Header,
                $pagibigMp22Header,
                $gsisFlexHeader,
                $resSalHeader,
                $loanGsiserHeader
            ) {
                $employeeDeductions = collect(data_get($deductionLookup, "{$employeeId}", []));
                if ($employeeDeductions->isEmpty()) {
                    return 0.0;
                }

                $listedDeductionIds = collect([
                    data_get($geHeader, 'deduction_id'),
                    data_get($cplHeader, 'deduction_id'),
                    data_get($pPremHeader, 'deduction_id'),
                    data_get($pMplHeader, 'deduction_id'),
                    data_get($mpliteHeader, 'deduction_id'),
                    data_get($consoHeader, 'deduction_id'),
                    data_get($polLoanHeader, 'deduction_id'),
                    data_get($pagcalHeader, 'deduction_id'),
                    data_get($gliteHeader, 'deduction_id'),
                    data_get($solarHeader, 'deduction_id'),
                    data_get($hmoDepHeader, 'deduction_id'),
                    data_get($eaHeader, 'deduction_id'),
                    data_get($coopHeader, 'deduction_id'),
                    data_get($pagibigMp2Header, 'deduction_id'),
                    data_get($pagibigMp22Header, 'deduction_id'),
                    data_get($gsisFlexHeader, 'deduction_id'),
                    data_get($resSalHeader, 'deduction_id'),
                    data_get($loanGsiserHeader, 'deduction_id'),
                ])->filter()->map(fn ($id) => (int) $id)->unique()->values();

                $manualTotal = (float) $employeeDeductions->sum(fn ($row) => (float) data_get($row, 'amount', 0));
                $listedTotal = (float) $employeeDeductions
                    ->filter(fn ($row, $deductionId) => $listedDeductionIds->contains((int) $deductionId))
                    ->sum(fn ($row) => (float) data_get($row, 'amount', 0));

                return max(0, $manualTotal - $listedTotal);
            };
            $normalizeName = function ($name) {
                return strtolower(preg_replace('/[^a-z0-9]+/', '', (string) $name));
            };

            // Keep existing fixed columns for these labels and only add truly extra dynamic items.
            $fixedIncomeNames = collect([
                'pera',
                'stepinc',
                'stepincrement',
                'differential',
                'additionalcompensation',
                'addlcomp',
                'adcomp',
            ]);

            /**
             * Map schedule income label to a fixed General Payroll column slot.
             * Long official names (e.g. "PERSONAL ECONOMIC ... (PERA)") normalize to strings
             * that are not exactly 'pera', which used to create duplicate dynamic columns.
             * Returns non-null => treat as fixed slot, exclude from $dynamicIncomeColumns.
             */
            $mapIncomeHeaderToFixedSlot = function ($header) use ($normalizeName, $fixedIncomeNames) {
                $raw = (string) ($header->income ?? '');
                $n = $normalizeName($raw);
                if ($n === '') {
                    // No label: do not add as dynamic (matches old filter: normalized !== '')
                    return 'skip';
                }
                if ($fixedIncomeNames->contains($n)) {
                    return 'fixed';
                }
                // PERA: any label containing PERA (matches $peraHeader discovery)
                if (stripos($raw, 'pera') !== false || str_contains($n, 'pera')) {
                    return 'pera';
                }
                // Salary differential
                if (stripos($raw, 'differential') !== false || str_contains($n, 'differential')) {
                    return 'diff';
                }
                // Step increment (avoid matching unrelated "step" only)
                if (
                    str_contains($n, 'stepincrement')
                    || str_contains($n, 'stepinc')
                    || (str_contains($n, 'step') && str_contains($n, 'increment'))
                    || (stripos($raw, 'step') !== false && stripos($raw, 'increment') !== false)
                ) {
                    return 'step';
                }
                // Additional compensation / ADCOMP / ADD'L COMP (avoid broad "...comp" matches)
                if (
                    str_contains($n, 'additionalcompensation')
                    || str_contains($n, 'addlcomp')
                    || str_contains($n, 'adcomp')
                    || stripos($raw, "add'l") !== false
                    || stripos($raw, 'addl comp') !== false
                    || stripos($raw, 'additional compensation') !== false
                ) {
                    return 'adcomp';
                }

                return null;
            };
            $fixedDeductionNames = collect([
                'lifeandret',
                'medicare',
                'ge',
                'gsisge',
                'gsiscpl',
                'cpl',
                'pprem',
                'pmpl',
                'optlifeins',
                'gsismplite',
                'gsisconsompl',
                'gsisgl',
                'polloan',
                'pagcal',
                'phsgloan',
                'gsishsg',
                'gsispabahay',
                'hdmfgs',
                'hdmfloan',
                'phicgs',
                'hmo',
                'ecash',
                'ea',
                'coop',
                'pagibigmp2',
                'pagibigmp22',
                'wtax',
                'gsisflex',
                'umid',
                'ressal',
                'loangsiser',
                'loanemergency',
                'absent',
                'tardiness',
                'preceding',
                'others',
            ]);

            $dynamicIncomeColumns = collect($income_headers ?? [])
                ->filter(function ($header) use ($mapIncomeHeaderToFixedSlot) {
                    return $mapIncomeHeaderToFixedSlot($header) === null;
                })
                ->unique('income_id')
                ->map(function ($header) {
                    return [
                        'label' => strtoupper((string) ($header->income ?? 'INCOME')),
                        'sublabel' => '&nbsp;',
                        'top' => 'dyn_inc_' . (int) $header->income_id,
                        'bottom' => null,
                        'income_id' => (int) $header->income_id,
                    ];
                })
                ->values();

            $dynamicDeductionColumns = collect($deduction_headers ?? [])
                ->filter(function ($header) use ($normalizeName, $fixedDeductionNames) {
                    $normalized = $normalizeName($header->deduction ?? '');
                    return $normalized !== '' && !$fixedDeductionNames->contains($normalized);
                })
                ->unique('deduction_id')
                ->map(function ($header) {
                    return [
                        'label' => strtoupper((string) ($header->deduction ?? 'DEDUCTION')),
                        'sublabel' => '&nbsp;',
                        'top' => 'dyn_ded_' . (int) $header->deduction_id,
                        'bottom' => null,
                        'deduction_id' => (int) $header->deduction_id,
                    ];
                })
                ->values();

            $acronym = function ($text) {
                if (!$text) {
                    return '';
                }

                $stopWords = ['OF', 'THE', 'AND', 'IN'];
                $words = preg_split('/\s+/', trim($text));

                $letters = collect($words)
                    ->map(fn ($w) => strtoupper($w))
                    ->reject(fn ($w) => in_array($w, $stopWords))
                    ->map(fn ($w) => $w[0] ?? '')
                    ->filter()
                    ->join('');

                return $letters ?: strtoupper(substr(trim($text), 0, 1));
            };

            $baseTotalKeys = [
                'basic', 'basic_undertime', 'step_inc', 'step_inc_undertime', 'diff', 'diff_undertime',
                'adcomp', 'adcomp_undertime', 'pera', 'pera_undertime', 'gross', 'gsis', 'medicare',
                'gsis_ge', 'gsis_cpl', 'p_prem', 'p_mpl', 'gsis_opt', 'gsis_mplite', 'gsis_conso',
                'pol_loan', 'pagcal', 'phsg_loan', 'gsis_glite', 'gsis_solar', 'pagibig', 'pagibig_gs',
                'pagibig_loan', 'philhealth', 'philhealth_employer', 'others', 'hmo', 'ecash', 'ea',
                'coop', 'hmo_dep', 'pagibig_mp2', 'pagibig_mp2_2', 'w_tax', 'gsis_flex', 'u_mid', 'res_sal', 'loan_gsiser', 'loan_emergency', 'absent',
                'tardiness', 'deduction', 'net', 'first', 'second', 'preceding',
            ];
            $baseTotalKeys = array_merge(
                $baseTotalKeys,
                $dynamicIncomeColumns->pluck('top')->all(),
                $dynamicDeductionColumns->pluck('top')->all()
            );

            $totals = array_fill_keys($baseTotalKeys, 0);
            $preparedPayrolls = [];

            foreach (($payrolls ?? []) as $payroll) {
                $stepInc = $payroll->step_increment ?? 0;
                $diff = $payroll->differential ?? 0;
                $adComp = $payroll->additional_comp ?? 0;
                $pera = $payroll->pera ?? 0;
                if ((!$pera || $pera == 0) && $peraHeader) {
                    $pera = data_get(
                        $incomeLookup,
                        "{$payroll->employee_id}.{$peraHeader->income_id}.amount",
                        0
                    );
                }
                $gsis = $payroll->gsis ?? 0;
                $medicare = data_get($payroll, 'philhealth', 0);
                $gsisGe = $payroll->gsis_ge ?? 0;
                $gsisCpl = data_get($payroll, 'gsis_cpl', 0);
                $pPrem = data_get($payroll, 'p_prem', 0);
                $pMpl = data_get($payroll, 'p_mpl', 0);
                if ((!$gsisGe || $gsisGe == 0) && $geHeader) {
                    $gsisGe = data_get(
                        $deductionLookup,
                        "{$payroll->employee_id}.{$geHeader->deduction_id}.amount",
                        0
                    );
                }
                if ((!$gsisCpl || $gsisCpl == 0) && $cplHeader) {
                    $gsisCpl = data_get(
                        $deductionLookup,
                        "{$payroll->employee_id}.{$cplHeader->deduction_id}.amount",
                        0
                    );
                }
                if ((!$pPrem || $pPrem == 0) && $pPremHeader) {
                    $pPrem = data_get(
                        $deductionLookup,
                        "{$payroll->employee_id}.{$pPremHeader->deduction_id}.amount",
                        0
                    );
                }
                if ((!$pMpl || $pMpl == 0) && $pMplHeader) {
                    $pMpl = data_get(
                        $deductionLookup,
                        "{$payroll->employee_id}.{$pMplHeader->deduction_id}.amount",
                        0
                    );
                }
                $gsisOpt = $payroll->gsis_opt_life ?? 0;
                $gsisMpl = $payroll->gsis_mpl ?? 0;
                $gsisMplite = data_get($payroll, 'gsis_mplite', 0);
                if ((!$gsisMplite || $gsisMplite == 0) && $mpliteHeader) {
                    $gsisMplite = data_get(
                        $deductionLookup,
                        "{$payroll->employee_id}.{$mpliteHeader->deduction_id}.amount",
                        0
                    );
                }
                $gsisConso = data_get($payroll, 'gsis_conso', $gsisMpl);
                if ((!$gsisConso || $gsisConso == 0) && $consoHeader) {
                    $gsisConso = data_get(
                        $deductionLookup,
                        "{$payroll->employee_id}.{$consoHeader->deduction_id}.amount",
                        0
                    );
                }
                $polLoan = data_get($payroll, 'pol_loan', $payroll->gsis_policy ?? 0);
                if ((!$polLoan || $polLoan == 0) && $polLoanHeader) {
                    $polLoan = data_get(
                        $deductionLookup,
                        "{$payroll->employee_id}.{$polLoanHeader->deduction_id}.amount",
                        0
                    );
                }
                $pagcal = data_get($payroll, 'pagcal', 0);
                if ((!$pagcal || $pagcal == 0) && $pagcalHeader) {
                    $pagcal = data_get(
                        $deductionLookup,
                        "{$payroll->employee_id}.{$pagcalHeader->deduction_id}.amount",
                        0
                    );
                }
                $phsgLoan = data_get($payroll, 'phsg_loan', 0);
                $gsisHsg = data_get($payroll, 'gsis_hsg', $payroll->gsis_phsg ?? 0);
                $gsisPabahay = data_get($payroll, 'gsis_pabahay', $payroll->gsis_pahabay ?? 0);
                $gsisGlite = data_get($payroll, 'gsis_glite', $payroll->gsis_glite ?? 0);
                $gsisSolar = data_get($payroll, 'gsis_solar', $payroll->gsis_solar ?? 0);
                if ((!$gsisGlite || $gsisGlite == 0) && $gliteHeader) {
                    $gsisGlite = data_get(
                        $deductionLookup,
                        "{$payroll->employee_id}.{$gliteHeader->deduction_id}.amount",
                        0
                    );
                }
                if ((!$gsisSolar || $gsisSolar == 0) && $solarHeader) {
                    $gsisSolar = data_get(
                        $deductionLookup,
                        "{$payroll->employee_id}.{$solarHeader->deduction_id}.amount",
                        0
                    );
                }
                $pagibig = $payroll->pagibig ?? 0;
                $pagibigGs = data_get($payroll, 'pagibig_gs', 0);
                $pagibigLoan = $payroll->pagibig_loan ?? 0;
                $philhealth = $payroll->philhealth ?? 0;
                $philhealthEmployer = data_get($payroll, 'philhealth_employer', 0);
                // W TAX: prefer the canonical payroll_summaries `tax` first, then legacy aliases.
                $wTax = (float) (
                    data_get($payroll, 'tax')
                    ?? data_get($payroll, 'w_tax')
                    ?? data_get($payroll, 'withholding_tax')
                    ?? 0
                );
                $sssDeduct = (float) data_get($payroll, 'sss', 0);
                $precedingAdj = (float) data_get($payroll, 'preceding_period_adjustment', 0);
                // Strict ESP/OTHERS residual to avoid double counting listed deductions.
                $other = (float) $computeEspOthers($payroll->employee_id);
                $totalDeduction = ($payroll->total_deduction ?? 0) ?: ($gsis + $pagibig + $philhealth + $other);
                $netPay = (float) ($payroll->net_pay ?? 0);
                $firstPayRaw = data_get($payroll, 'first_pay', null);
                $secondPayRaw = data_get($payroll, 'second_pay', null);
                $firstHalfNetRaw = data_get($payroll, 'first_half_net_pay', null);
                $secondHalfNetRaw = data_get($payroll, 'second_half_net_pay', null);

                if ($firstPayRaw !== null || $secondPayRaw !== null) {
                    $firstPay = (float) ($firstPayRaw ?? 0);
                    $secondPay = (float) ($secondPayRaw ?? 0);
                } elseif ($firstHalfNetRaw !== null || $secondHalfNetRaw !== null) {
                    $firstPay = (float) ($firstHalfNetRaw ?? 0);
                    $secondPay = (float) ($secondHalfNetRaw ?? 0);
                } else {
                    // Monthly fallback: keep 1st half whole pesos, carry centavos to 2nd half.
                    $firstPay = (float) floor($netPay / 2);
                    $secondPay = (float) round($netPay - $firstPay, 2);
                }
                $hmo = data_get($payroll, 'hmo', 0);
                $ecash = data_get($payroll, 'ecash', 0);
                $ea = data_get($payroll, 'ea', 0);
                if ((!$ea || $ea == 0) && $eaHeader) {
                    $ea = data_get(
                        $deductionLookup,
                        "{$payroll->employee_id}.{$eaHeader->deduction_id}.amount",
                        0
                    );
                }
                $coop = data_get($payroll, 'coop', 0);
                if ((!$coop || $coop == 0) && $coopHeader) {
                    $coop = data_get(
                        $deductionLookup,
                        "{$payroll->employee_id}.{$coopHeader->deduction_id}.amount",
                        0
                    );
                }
                $hmoDep = data_get($payroll, 'hmo_dep', 0);
                if ((!$hmoDep || $hmoDep == 0) && $hmoDepHeader) {
                    $hmoDep = data_get(
                        $deductionLookup,
                        "{$payroll->employee_id}.{$hmoDepHeader->deduction_id}.amount",
                        0
                    );
                }
                $pagibigMp2 = data_get($payroll, 'pagibig_mp2', 0);
                if ((!$pagibigMp2 || $pagibigMp2 == 0) && $pagibigMp2Header) {
                    $pagibigMp2 = data_get(
                        $deductionLookup,
                        "{$payroll->employee_id}.{$pagibigMp2Header->deduction_id}.amount",
                        0
                    );
                }
                $pagibigMp22 = data_get($payroll, 'pagibig_mp2_2', 0);
                if ((!$pagibigMp22 || $pagibigMp22 == 0) && $pagibigMp22Header) {
                    $pagibigMp22 = data_get(
                        $deductionLookup,
                        "{$payroll->employee_id}.{$pagibigMp22Header->deduction_id}.amount",
                        0
                    );
                }
                $gsisFlex = data_get($payroll, 'gsis_flex', 0);
                if ((!$gsisFlex || $gsisFlex == 0) && $gsisFlexHeader) {
                    $gsisFlex = data_get(
                        $deductionLookup,
                        "{$payroll->employee_id}.{$gsisFlexHeader->deduction_id}.amount",
                        0
                    );
                }
                $uMid = data_get($payroll, 'u_mid', 0);
                $resSal = data_get($payroll, 'res_sal', 0);
                if ((!$resSal || $resSal == 0) && $resSalHeader) {
                    $resSal = data_get(
                        $deductionLookup,
                        "{$payroll->employee_id}.{$resSalHeader->deduction_id}.amount",
                        0
                    );
                }
                $loanGsiser = data_get($payroll, 'loan_gsiser', 0);
                if ((!$loanGsiser || $loanGsiser == 0) && $loanGsiserHeader) {
                    $loanGsiser = data_get(
                        $deductionLookup,
                        "{$payroll->employee_id}.{$loanGsiserHeader->deduction_id}.amount",
                        0
                    );
                }
                $loanEmergency = data_get($payroll, 'loan_emergency', 0);
                $absentAmount = data_get($payroll, 'absent_amount', 0);
                $tardinessAmount = data_get($payroll, 'late_amount', 0) + data_get($payroll, 'ut_amount', 0);
                $dynamicIncomeValues = [];
                foreach ($dynamicIncomeColumns as $column) {
                    $incomeId = (int) ($column['income_id'] ?? 0);
                    $dynamicIncomeValues[$column['top']] = (float) data_get(
                        $incomeLookup,
                        "{$payroll->employee_id}.{$incomeId}.amount",
                        0
                    );
                }

                $dynamicDeductionValues = [];
                foreach ($dynamicDeductionColumns as $column) {
                    $deductionId = (int) ($column['deduction_id'] ?? 0);
                    $dynamicDeductionValues[$column['top']] = (float) data_get(
                        $deductionLookup,
                        "{$payroll->employee_id}.{$deductionId}.amount",
                        0
                    );
                }
                // Strict client layout mode: dynamic columns are not appended to the report table.
                // Keep all non-fixed deductions inside OTHERS to preserve totals and avoid hidden amounts.
                $dynamicDeductionTotal = 0;
                $other = max(0, (float) $other - (float) $dynamicDeductionTotal);

                $values = [
                    'basic' => $payroll->salary ?? 0,
                    'basic_undertime' => $payroll->basic_undertime ?? 0,
                    'step_inc' => $stepInc,
                    'step_inc_undertime' => $payroll->step_inc_undertime ?? 0,
                    'diff' => $diff,
                    'diff_undertime' => $payroll->diff_undertime ?? 0,
                    'adcomp' => $adComp,
                    'adcomp_undertime' => $payroll->adcomp_undertime ?? 0,
                    'pera' => $pera,
                    'pera_undertime' => $payroll->pera_undertime ?? 0,
                    'gross' => $payroll->gross_amount ?? 0,
                    'gsis' => $gsis,
                    'medicare' => $medicare,
                    'gsis_ge' => $gsisGe,
                    'gsis_cpl' => $gsisCpl,
                    'p_prem' => $pPrem,
                    'p_mpl' => $pMpl,
                    'gsis_opt' => $gsisOpt,
                    'gsis_mplite' => $gsisMplite,
                    'gsis_conso' => $gsisConso,
                    'pol_loan' => $polLoan,
                    'pagcal' => $pagcal,
                    'phsg_loan' => $phsgLoan,
                    'gsis_glite' => $gsisGlite,
                    'gsis_solar' => $gsisSolar,
                    'pagibig' => $pagibig,
                    'pagibig_gs' => $pagibigGs,
                    'pagibig_loan' => $pagibigLoan,
                    'philhealth' => $philhealth,
                    'philhealth_employer' => $philhealthEmployer,
                    'others' => $other,
                    'hmo' => $hmo,
                    'ecash' => $ecash,
                    'ea' => $ea,
                    'coop' => $coop,
                    'hmo_dep' => $hmoDep,
                    'pagibig_mp2' => $pagibigMp2,
                    'pagibig_mp2_2' => $pagibigMp22,
                    'w_tax' => $wTax,
                    'gsis_flex' => $gsisFlex,
                    'u_mid' => $uMid,
                    'res_sal' => $resSal,
                    'loan_gsiser' => $loanGsiser,
                    'loan_emergency' => $loanEmergency,
                    'absent' => $absentAmount,
                    'tardiness' => $tardinessAmount,
                    'deduction' => $totalDeduction,
                    'net' => $payroll->net_pay ?? 0,
                    'first' => $firstPay,
                    'second' => $secondPay,
                    'preceding' => $precedingAdj,
                ];
                $values = array_merge($values, $dynamicIncomeValues, $dynamicDeductionValues);

                foreach ($values as $key => $amount) {
                    $totals[$key] += (float) $amount;
                }

                $preparedPayrolls[] = [
                    'payroll' => $payroll,
                    'values' => $values,
                ];
            }

            $columns = [
                ['label' => 'BASIC', 'sublabel' => 'UNDERTIME', 'top' => 'basic', 'bottom' => 'basic_undertime'],
                ['label' => 'STEP INC', 'sublabel' => 'UNDERTIME', 'top' => 'step_inc', 'bottom' => 'step_inc_undertime'],
                ['label' => 'DIFF', 'sublabel' => 'UNDERTIME', 'top' => 'diff', 'bottom' => 'diff_undertime'],
                ['label' => 'ADCOMP', 'sublabel' => 'UNDERTIME', 'top' => 'adcomp', 'bottom' => 'adcomp_undertime'],
                ['label' => 'PERA', 'sublabel' => 'UNDERTIME', 'top' => 'pera', 'bottom' => 'pera_undertime'],
                ['label' => 'GROSS', 'sublabel' => '&nbsp;', 'top' => 'gross', 'bottom' => null],
                ['label' => 'LIFE / RET', 'sublabel' => 'PHIC', 'top' => 'gsis', 'bottom' => 'medicare'],
                ['label' => 'GE', 'sublabel' => 'GSISCPL', 'top' => 'gsis_ge', 'bottom' => 'gsis_cpl'],
                ['label' => 'HDMF PREM', 'sublabel' => 'HDMF MPL', 'top' => 'p_prem', 'bottom' => 'p_mpl'],
                ['label' => 'HDMFMP2-2', 'sublabel' => 'GSIS MPLITE', 'top' => 'pagibig_mp2_2', 'bottom' => 'gsis_mplite'],
                ['label' => 'GSIS MPL', 'sublabel' => 'GSIS POL LOAN', 'top' => 'gsis_conso', 'bottom' => 'pol_loan'],
                ['label' => 'HDMF CAL', 'sublabel' => '&nbsp;', 'top' => 'pagcal', 'bottom' => null],
                ['label' => 'GSIS GLITE', 'sublabel' => 'GSIS SOLAR', 'top' => 'gsis_glite', 'bottom' => 'gsis_solar'],
                ['label' => 'HMO Dep', 'sublabel' => '&nbsp;', 'top' => 'hmo_dep', 'bottom' => null],
                // ['label' => 'HDMF', 'sublabel' => 'LOAN', 'top' => 'pagibig_loan', 'bottom' => null],
                // ['label' => 'PHIC', 'sublabel' => 'G/S', 'top' => '', 'bottom' => 'philhealth_employer'],
                // ['label' => 'OTHERS', 'sublabel' => 'PRECEDING', 'top' => 'others', 'bottom' => 'preceding'],
                // ['label' => 'HMO', 'sublabel' => 'ECASH++', 'top' => 'hmo', 'bottom' => 'ecash'],
                ['label' => 'EA', 'sublabel' => 'COOP', 'top' => 'ea', 'bottom' => 'coop'],
                ['label' => 'ESP/OTHERS', 'sublabel' => 'HDMF MP2-1', 'top' => 'others', 'bottom' => 'pagibig_mp2'],
                ['label' => 'W TAX', 'sublabel' => 'GSISFlex', 'top' => 'w_tax', 'bottom' => 'gsis_flex'],
                ['label' => 'RES SAL', 'sublabel' => 'LOAN GSISEr', 'top' => 'res_sal', 'bottom' => 'loan_gsiser'],
                // ['label' => 'ABSENT', 'sublabel' => 'AMOUNT', 'top' => 'absent', 'bottom' => null],
                // ['label' => 'TARDINESS', 'sublabel' => 'TOTAL', 'top' => 'tardiness', 'bottom' => null],
                ['label' => 'DEDUCTIONS', 'sublabel' => 'NET PAY', 'top' => 'deduction', 'bottom' => 'net'],
                ['label' => 'FIRST PAY', 'sublabel' => 'SECOND PAY', 'top' => 'first', 'bottom' => 'second'],
                
            ];
            // Strict fixed-layout payroll report: do not append dynamic columns.
            // This preserves the exact client-required column order and avoids extra columns after FIRST/SECOND PAY.
            // $columns = array_merge($columns, $dynamicIncomeColumns->all(), $dynamicDeductionColumns->all());

            $visibleColumns = collect($columns)
                ->filter(function ($column) use ($totals, $showAllColumns) {
                    if (!empty($showAllColumns)) {
                        return true;
                    }
                    $topTotal = abs((float) ($totals[$column['top']] ?? 0));
                    $bottomKey = $column['bottom'] ?? null;
                    $bottomTotal = $bottomKey ? abs((float) ($totals[$bottomKey] ?? 0)) : 0;
                    return ($topTotal + $bottomTotal) > 0;
                })
                ->values();
        @endphp

        <table>
            <thead>
                <tr>
                    <th class="col-number stacked-header">
                        <span class="label">DIV</span>
                    </th>
                    <th class="col-name stacked-header">
                        <span class="label">NAME</span>
                        <span class="sublabel">DESIGNATION</span>
                    </th>
                    @foreach ($visibleColumns as $column)
                        <th class="col-money stacked-header">
                            <span class="label">{{ $column['label'] }}</span>
                            <span class="sublabel">{!! $column['sublabel'] !!}</span>
                        </th>
                    @endforeach
                    <th class="stacked-header">
                        <span class="label">REMARKS</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @php
                    $currentDepartment = null;
                    $deptTotals = array_fill_keys(array_keys($totals), 0);
                @endphp

                @foreach ($preparedPayrolls as $row)
                    @php
                        $payroll = $row['payroll'];
                        $values = $row['values'];
                        $departmentName = $payroll->department ?? '';
                        $isDepartmentBreak = $currentDepartment !== null && $departmentName !== $currentDepartment;

                        if ($currentDepartment === null) {
                            $currentDepartment = $departmentName;
                        }
                    @endphp
                    @if ($isDepartmentBreak)
                        <tr class="totals-row">
                            <td colspan="2" style="text-align:left; color: red; font-style: italic;">SUB-TOTALS</td>
                            @foreach ($visibleColumns as $column)
                                @php
                                    $top = $deptTotals[$column['top']] ?? 0;
                                    $bottomKey = $column['bottom'] ?? null;
                                    $bottom = $bottomKey ? ($deptTotals[$bottomKey] ?? 0) : null;
                                @endphp
                                <td class="col-money {{ $bottomKey ? 'cell-stack' : '' }}">
                                    <span>{{ $money($top) }}</span>
                                    @if ($bottomKey)
                                        <span>{{ $money($bottom) }}</span>
                                    @endif
                                </td>
                            @endforeach
                            <td></td>
                        </tr>
                        @php
                            $deptTotals = array_fill_keys(array_keys($deptTotals), 0);
                            $currentDepartment = $departmentName;
                        @endphp
                    @endif
                    @php
                        foreach ($values as $key => $amount) {
                            $deptTotals[$key] += (float) $amount;
                        }
                    @endphp
                    <tr>
                        <td class="col-number"> {{ $acronym($payroll->department) }}</td>
                        <td class="col-name">
                            <strong>{{ strtoupper($payroll->name) }}</strong><br>
                            <span>{{ $payroll->position }}</span>
                        </td>
                        @foreach ($visibleColumns as $column)
                            @php
                                $top = $values[$column['top']] ?? 0;
                                $bottomKey = $column['bottom'] ?? null;
                                $bottom = $bottomKey ? ($values[$bottomKey] ?? 0) : null;
                            @endphp
                            <td class="col-money {{ $bottomKey ? 'cell-stack' : '' }}">
                                <span>{{ $money($top) }}</span>
                                @if ($bottomKey)
                                    <span>{{ $money($bottom) }}</span>
                                @endif
                            </td>
                        @endforeach
                        <td>{{ $payroll->remarks ?? '' }}</td>
                    </tr>
                @endforeach

                @if(!empty($preparedPayrolls) && $currentDepartment !== null)
                    <tr class="totals-row">
                        <td colspan="2" style="text-align:left; color: red; font-style: italic;">SUB-TOTALS</td>
                        @foreach ($visibleColumns as $column)
                            @php
                                $top = $deptTotals[$column['top']] ?? 0;
                                $bottomKey = $column['bottom'] ?? null;
                                $bottom = $bottomKey ? ($deptTotals[$bottomKey] ?? 0) : null;
                            @endphp
                            <td class="col-money {{ $bottomKey ? 'cell-stack' : '' }}">
                                <span>{{ $money($top) }}</span>
                                @if ($bottomKey)
                                    <span>{{ $money($bottom) }}</span>
                                @endif
                            </td>
                        @endforeach
                        <td></td>
                    </tr>
                @endif
            </tbody>
            <tfoot>
                <tr class="totals-row">
                    <td colspan="2" style="text-align:left; color: red; font-style: italic;">GRAND TOTALS</td>
                    @foreach ($visibleColumns as $column)
                        @php
                            $top = $totals[$column['top']] ?? 0;
                            $bottomKey = $column['bottom'] ?? null;
                            $bottom = $bottomKey ? ($totals[$bottomKey] ?? 0) : null;
                        @endphp
                        <td class="col-money {{ $bottomKey ? 'cell-stack' : '' }}">
                            <span>{{ $money($top) }}</span>
                            @if ($bottomKey)
                                <span>{{ $money($bottom) }}</span>
                            @endif
                        </td>
                    @endforeach
                    <td></td>
                </tr>
            </tfoot>
        </table>

    </div>

    

        {{-- <div class="pdf-footer">
            <div class="signatories">
                <div class="sign-card">
                    <p>I CERTIFY on my oath that the above Payroll is Correct and that
                        <br />the services have been duly rendered as stated.</p>
                    <p class="sign-name">{{ $signatories['signatory_1'] ?? '__________________________' }}</p>
                    <p>{{ $signatories['signatory_position_1'] ?? 'Chief Administrative Officer' }}</p>
                </div>
                <div class="sign-card">
                    <p>APPROVED, payable for appropriation for P __________</p>
                    <p class="sign-name">{{ $signatories['signatory_2'] ?? '__________________________' }}</p>
                    <p>{{ $signatories['signatory_position_2'] ?? 'Budget Officer' }}</p>
                </div>
            </div>
        </div> --}}

        {{-- pagination HTML span stays but dompdf ignores it anyway, the script below is what actually renders it --}}
        {{-- <div class="pagination">
            Page <span class="pageNumber"></span> of <span class="totalPages">{{ $totalPages ?? '' }}</span>
        </div>

        <script type="text/php">
            if (isset($pdf)) {
                $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
                $size = 10;
                $font = $fontMetrics->getFont("Arial");
                $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
                $x = ($pdf->get_width() - $width) - 50;
                $y = $pdf->get_height() - 30;
                $pdf->page_text($x, $y, $text, $font, $size, array(0.48, 0.64, 0.82));
            }
        </script> --}}



        <!-- this one work's fine, checkpoint 2026-05-18 -->
        {{-- <script type="text/php">
            if (isset($pdf)) {
                $pageWidth  = $pdf->get_width();
                $pageHeight = $pdf->get_height();
                $font       = $fontMetrics->getFont("Arial");
                $boldFont   = $fontMetrics->getFont("Arial", "bold");
                $color      = array(0.086, 0.227, 0.455);
                $x          = 11;
                $y          = $pageHeight - 72;

                // Left signatory
                $pdf->page_text($x, $y,      "I CERTIFY on my oath that the above Payroll is Correct and that", $font, 8, $color);
                $pdf->page_text($x, $y + 10, "the services have been duly rendered as stated.",                 $font, 8, $color);
                $pdf->page_text($x, $y + 38, strtoupper("{{ $signatories['signatory_1'] ?? '__________________________' }}"), $boldFont, 8, $color);
                $pdf->page_text($x, $y + 48, "{{ $signatories['signatory_position_1'] ?? 'Chief Administrative Officer' }}", $font, 8, $color);

                // Right signatory
                $col2x = $pageWidth / 2;
                $pdf->page_text($col2x, $y,      "APPROVED, payable for appropriation for P __________",                                                  $font, 8, $color);
                $pdf->page_text($col2x, $y + 38, strtoupper("{{ $signatories['signatory_2'] ?? '__________________________' }}"), $boldFont, 8, $color);
                $pdf->page_text($col2x, $y + 48, "{{ $signatories['signatory_position_2'] ?? 'Budget Officer' }}",               $font, 8, $color);

                // Pagination
                $text   = "Page {PAGE_NUM} of {PAGE_COUNT}";
                $pWidth = $fontMetrics->get_text_width($text, $font, 10) / 2;
                $pdf->page_text($pageWidth - $pWidth - 50, $pageHeight - 30, $text, $font, 10, array(0.48, 0.64, 0.82));
            }
        </script> --}}

        {{-- <script type="text/php">
            if (isset($pdf)) {
                $pageWidth  = $pdf->get_width();
                $pageHeight = $pdf->get_height();
                $font       = $fontMetrics->getFont("Arial");
                $boldFont   = $fontMetrics->getFont("Arial", "bold");
                $italicFont = $fontMetrics->getFont("Arial", "italic");
                $color      = array(0.086, 0.227, 0.455);

                $x = 80; // left indent matching image 1
                $y = $pageHeight - 160;

              // First signatory block
                $pdf->page_text($x, $y,      "I CERTIFY on my oath that the above Payroll is correct and that", $italicFont, 8, $color);
                $pdf->page_text($x, $y + 11, "the services have been duly rendered as stated.",                 $italicFont, 8, $color);
                $pdf->page_text($x, $y + 25, strtoupper("{{ $signatories['signatory_1'] ?? '__________________________' }}"), $boldFont, 9, $color);
                $pdf->page_text($x, $y + 35, "{{ $signatories['signatory_position_1'] ?? 'Chief Administrative Officer' }}", $italicFont, 8, $color);

                // Second signatory block
                $y2 = $y + 55;
                $pdf->page_text($x, $y2,      "APPROVED, payable for appropriation for P __________", $italicFont, 8, $color);
                $pdf->page_text($x, $y2 + 25, strtoupper("{{ $signatories['signatory_2'] ?? '__________________________' }}"), $boldFont, 9, $color);
                $pdf->page_text($x, $y2 + 35, "{{ $signatories['signatory_position_2'] ?? 'Budget Officer' }}",               $italicFont, 8, $color);

                // Bottom left: date and reference
                $pdf->page_text(11, $pageHeight - 30, date("l, d F Y"), $italicFont, 7, $color);
                $pdf->page_text(11, $pageHeight - 19, "/rc04",          $italicFont, 7, $color);

                // Bottom right: pagination
                $pageText  = "Page {PAGE_NUM} of {PAGE_COUNT}";
                $pWidth    = $fontMetrics->get_text_width($pageText, $italicFont, 8) / 2;
                $lightBlue = array(0.48, 0.64, 0.82);
                $pdf->page_text($pageWidth - $pWidth - 11, $pageHeight - 27, $pageText, $italicFont, 8, $lightBlue);
            }
        </script> --}}

        <script type="text/php">
            if (isset($pdf)) {
                $pageWidth  = $pdf->get_width();
                $pageHeight = $pdf->get_height();
                $font       = $fontMetrics->getFont("Arial");
                $boldFont   = $fontMetrics->getFont("Arial", "bold");
                $italicFont = $fontMetrics->getFont("Arial", "oblique");
                $color      = array(0.086, 0.227, 0.455);
                $lightBlue  = array(0.48, 0.64, 0.82);

                $x = 80;
                $y = $pageHeight - 160;

                $pdf->page_text($x, $y,      "I CERTIFY on my oath that the above Payroll is correct and that", $italicFont, 8, $color);
                $pdf->page_text($x, $y + 11, "the services have been duly rendered as stated.",                 $italicFont, 8, $color);
                $pdf->page_text($x, $y + 25, strtoupper("{{ $signatories['signatory_1'] ?? '__________________________' }}"), $boldFont, 9, $color);
                $pdf->page_text($x, $y + 35, "{{ $signatories['signatory_position_1'] ?? 'Chief Administrative Officer' }}", $italicFont, 8, $color);

                $y2 = $y + 55;
                $pdf->page_text($x, $y2,      "APPROVED, payable for appropriation for P __________", $italicFont, 8, $color);
                $pdf->page_text($x, $y2 + 25, strtoupper("{{ $signatories['signatory_2'] ?? '__________________________' }}"), $boldFont, 9, $color);
                $pdf->page_text($x, $y2 + 35, "{{ $signatories['signatory_position_2'] ?? 'Budget Officer' }}",               $italicFont, 8, $color);

                $pdf->page_text(11, $pageHeight - 38, date("l, d F Y"), $italicFont, 6, $color);
                $pdf->page_text(11, $pageHeight - 27, "/rc04",          $italicFont, 6, $color);

                $pageText = "Page {PAGE_NUM} of {PAGE_COUNT}";
                $pWidth   = $fontMetrics->get_text_width($pageText, $italicFont, 8);
                $pdf->page_text($pageWidth - $pWidth - 11, $pageHeight - 27, $pageText, $italicFont, 8, $lightBlue);
            }
        </script>


</body>

</html>