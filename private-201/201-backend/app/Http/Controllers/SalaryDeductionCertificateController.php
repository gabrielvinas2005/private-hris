<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use PDF;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc as WordJc;
use Carbon\Carbon;

class SalaryDeductionCertificateController extends Controller
{
    use ApiResponse;

    public function employees()
    {
        $app_key = config('app.key');

        $employees = DB::table('employee_offboardings as o')
            ->join('employees as e', 'e.id', '=', 'o.employee_id')
            ->select(
                'o.id',
                'o.employee_id',
                DB::raw('o.date_effectivity as separation_date'),
                DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN CONCAT(e.first_name,' ',e.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) END as full_name")
            )
            ->orderBy('full_name', 'asc')
            ->get();

        return $this->successResponse($employees, 'Offboarded employees loaded');
    }

    public function print(Request $request)
    {
        $request->validate([
            'offboarding_id' => 'required|integer|exists:employee_offboardings,id',
            'signatory' => 'required|string|min:1',
            'position' => 'required|string|min:1',
        ]);

        $app_key = config('app.key');

        $offboarding = DB::table('employee_offboardings as o')
            ->join('employees as e', 'e.id', '=', 'o.employee_id')
            ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
            ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
            ->select(
                'o.id',
                'o.employee_id',
                DB::raw('o.date_effectivity as separation_date'),
                DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN CONCAT(e.first_name,' ',e.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) END as full_name"),
                'p.name as position_name',
                'd.name as department_name'
            )
            ->where('o.id', $request->offboarding_id)
            ->first();

        if (!$offboarding) {
            return $this->notFoundResponse('Employee not found');
        }

        // Assets
        $headerImg = null;
        $footerImg = null;
        $headerPath = resource_path('img/report_header.jpg');
        $footerPath = resource_path('img/report_footer.jpg');
        if (file_exists($headerPath)) {
            $headerImg = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($headerPath));
        }
        if (file_exists($footerPath)) {
            $footerImg = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($footerPath));
        }

        // Determine latest payroll period for this employee based on deductions; fallback to now
        $dateExpr = DB::raw("COALESCE(pp.payroll_start_date, pp.release_date, pp.created_at)");
        $latestPeriod = DB::table('payroll_deductions as pd')
            ->join('payroll_periods as pp', 'pd.payroll_period_id', '=', 'pp.id')
            ->where('pd.employee_id', $offboarding->employee_id)
            ->orderBy($dateExpr, 'desc')
            ->limit(1)
            ->value($dateExpr);

        $latestDate = $latestPeriod ? Carbon::parse($latestPeriod) : Carbon::now();
        $startDate = $latestDate->copy()->startOfMonth()->subMonths(2); // latest month plus previous 2

        $deductions = DB::table('payroll_deductions as pd')
            ->join('payroll_periods as pp', 'pd.payroll_period_id', '=', 'pp.id')
            ->leftJoin('deductions as d', 'pd.deduction_id', '=', 'd.id')
            ->select(
                DB::raw("FORMAT($dateExpr, 'yyyy-MM-01') as month_key"),
                DB::raw("FORMAT($dateExpr, 'MMMM yyyy') as month_label"),
                DB::raw("UPPER(ISNULL(d.name,'OTHER')) as deduction_name"),
                DB::raw("SUM(pd.amount) as total_amount")
            )
            ->where('pd.employee_id', $offboarding->employee_id)
            ->where($dateExpr, '>=', $startDate)
            ->groupBy(DB::raw("FORMAT($dateExpr, 'yyyy-MM-01')"), DB::raw("FORMAT($dateExpr, 'MMMM yyyy')"), DB::raw("UPPER(ISNULL(d.name,'OTHER'))"))
            ->orderBy(DB::raw("FORMAT($dateExpr, 'yyyy-MM-01')"))
            ->get();

        $columns = ['GSIS', 'GSIS CONSO LOAN', 'GSIS ECC', 'PAGIBIG', 'PHIC'];

        $rows = collect($deductions)
            ->groupBy('month_key')
            ->map(function ($items) use ($columns) {
                $row = [
                    'month' => $items->first()->month_label,
                ];
                $total = 0;
                foreach ($columns as $col) {
                    $amount = (float) optional($items->firstWhere('deduction_name', $col))->total_amount ?? 0;
                    $row[$col] = $amount;
                    $total += $amount;
                }
                $row['TOTAL'] = $total;
                return $row;
            })
            ->values()
            ->toArray();

        $columnTotals = [];
        foreach ($columns as $col) {
            $columnTotals[$col] = collect($rows)->sum($col);
        }
        $columnTotals['TOTAL'] = collect($rows)->sum('TOTAL');

        $loans = DB::table('loan_applications as la')
            ->leftJoin('deductions as d', 'la.deduction_id', '=', 'd.id')
            ->select(
                DB::raw("UPPER(ISNULL(d.name,'LOAN')) as name"),
                'la.loan_amortization as amount',
                'la.balance'
            )
            ->where('la.employee_id', $offboarding->employee_id)
            ->where('la.is_approve', 1)
            ->get();

        $pdf = PDF::loadView('certificates.salary_deduction_certificate', [
            'employee' => $offboarding,
            'rows' => $rows,
            'columns' => $columns,
            'columnTotals' => $columnTotals,
            'loans' => $loans,
            'signatory' => $request->signatory,
            'signatory_position' => $request->position,
            'headerImg' => $headerImg,
            'footerImg' => $footerImg,
            'issueDate' => Carbon::now(),
        ])->setOptions(['defaultFont' => 'sans-serif']);

        $pdf->setPaper('A4');
        $pdf_content = $pdf->output();
        $filename = 'certificate_of_salary_deductions_' . $offboarding->full_name . '.pdf';

        return response($pdf_content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Length', strlen($pdf_content));
    }

    public function downloadDocx(Request $request)
    {
        try {
            $request->validate([
                'offboarding_id' => 'required|integer|exists:employee_offboardings,id',
                'signatory' => 'required|string|min:1',
                'position' => 'required|string|min:1',
            ]);

            $app_key = config('app.key');

            $offboarding = DB::table('employee_offboardings as o')
                ->join('employees as e', 'e.id', '=', 'o.employee_id')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
                ->select(
                    'o.id',
                    'o.employee_id',
                    DB::raw('o.date_effectivity as separation_date'),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN CONCAT(e.first_name,' ',e.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) END as full_name"),
                    'p.name as position_name',
                    'd.name as department_name'
                )
                ->where('o.id', $request->offboarding_id)
                ->first();

            if (!$offboarding) {
                return $this->notFoundResponse('Employee not found');
            }

            // Determine latest payroll period for this employee based on deductions; fallback to now
            $dateExpr = DB::raw("COALESCE(pp.payroll_start_date, pp.release_date, pp.created_at)");
            $latestPeriod = DB::table('payroll_deductions as pd')
                ->join('payroll_periods as pp', 'pd.payroll_period_id', '=', 'pp.id')
                ->where('pd.employee_id', $offboarding->employee_id)
                ->orderBy($dateExpr, 'desc')
                ->limit(1)
                ->value($dateExpr);

            $latestDate = $latestPeriod ? Carbon::parse($latestPeriod) : Carbon::now();
            $startDate = $latestDate->copy()->startOfMonth()->subMonths(2); // latest month plus previous 2

            $deductions = DB::table('payroll_deductions as pd')
                ->join('payroll_periods as pp', 'pd.payroll_period_id', '=', 'pp.id')
                ->leftJoin('deductions as d', 'pd.deduction_id', '=', 'd.id')
                ->select(
                    DB::raw("FORMAT($dateExpr, 'yyyy-MM-01') as month_key"),
                    DB::raw("FORMAT($dateExpr, 'MMMM yyyy') as month_label"),
                    DB::raw("UPPER(ISNULL(d.name,'OTHER')) as deduction_name"),
                    DB::raw("SUM(pd.amount) as total_amount")
                )
                ->where('pd.employee_id', $offboarding->employee_id)
                ->where($dateExpr, '>=', $startDate)
                ->groupBy(DB::raw("FORMAT($dateExpr, 'yyyy-MM-01')"), DB::raw("FORMAT($dateExpr, 'MMMM yyyy')"), DB::raw("UPPER(ISNULL(d.name,'OTHER'))"))
                ->orderBy(DB::raw("FORMAT($dateExpr, 'yyyy-MM-01')"))
                ->get();

            $columns = ['GSIS', 'GSIS CONSO LOAN', 'GSIS ECC', 'PAGIBIG', 'PHIC'];

            $rows = collect($deductions)
                ->groupBy('month_key')
                ->map(function ($items) use ($columns) {
                    $row = [
                        'month' => $items->first()->month_label,
                    ];
                    $total = 0;
                    foreach ($columns as $col) {
                        $amount = (float) optional($items->firstWhere('deduction_name', $col))->total_amount ?? 0;
                        $row[$col] = $amount;
                        $total += $amount;
                    }
                    $row['TOTAL'] = $total;
                    return $row;
                })
                ->values()
                ->toArray();

            $columnTotals = [];
            foreach ($columns as $col) {
                $columnTotals[$col] = collect($rows)->sum($col);
            }
            $columnTotals['TOTAL'] = collect($rows)->sum('TOTAL');

            $loans = DB::table('loan_applications as la')
                ->leftJoin('deductions as d', 'la.deduction_id', '=', 'd.id')
                ->select(
                    DB::raw("UPPER(ISNULL(d.name,'LOAN')) as name"),
                    'la.loan_amortization as amount',
                    'la.balance'
                )
                ->where('la.employee_id', $offboarding->employee_id)
                ->where('la.is_approve', 1)
                ->get();

            $issueDate = Carbon::now();
            $day = $issueDate->format('j');
            $suffix = ($day == 1 || $day == 21 || $day == 31) ? 'st'
                : (($day == 2 || $day == 22) ? 'nd'
                : (($day == 3 || $day == 23) ? 'rd' : 'th'));

            // Create PhpWord document
            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(12);

            // Page setup with margins (matching PDF: padding 1in on sides)
            $section = $phpWord->addSection([
                'marginTop' => Converter::inchToTwip(0),
                'marginRight' => Converter::inchToTwip(1),
                'marginBottom' => Converter::inchToTwip(0),
                'marginLeft' => Converter::inchToTwip(1),
                'pageSizeW' => Converter::inchToTwip(8.27),
                'pageSizeH' => Converter::inchToTwip(11.69),
            ]);

            // Header image
            $headerPath = resource_path('img/report_header.jpg');
            if (file_exists($headerPath)) {
                $header = $section->addHeader();
                $header->addImage($headerPath, [
                    'width' => Converter::cmToPoint(12),
                    'alignment' => 'center'
                ]);
            }

            // Footer image
            $footerPath = resource_path('img/report_footer.jpg');
            if (file_exists($footerPath)) {
                $footer = $section->addFooter();
                $footer->addImage($footerPath, [
                    'width' => Converter::cmToPoint(12),
                    'alignment' => 'center'
                ]);
            }


            // Title
            $section->addText('CERTIFICATION OF SALARY DEDUCTIONS', ['bold' => true, 'size' => 12], ['alignment' => WordJc::CENTER, 'spaceAfter' => 240]);
            $section->addTextBreak(1);

            // First paragraph - Clean and escape data
            $employeeName = str_replace(["\0", "\r"], '', (string)($offboarding->full_name ?? 'N/A'));
            $employeeName = htmlspecialchars(strtoupper($employeeName), ENT_XML1, 'UTF-8');
            
            $positionName = str_replace(["\0", "\r"], '', (string)($offboarding->position_name ?? 'employee'));
            $positionName = htmlspecialchars($positionName, ENT_XML1, 'UTF-8');
            
            $paragraphStyle = [
                'alignment'   => WordJc::BOTH,
                'indentation' => ['firstLine' => Converter::inchToTwip(0.4)],
                'spaceAfter'  => 240,
            ];

            // First paragraph (single text run, indented + justified)
            $para1 = $section->addTextRun(null, $paragraphStyle);
            $para1->addText('This is to certify that ');
            $para1->addText($employeeName, ['bold' => true]);
            $para1->addText(', former ');
            $para1->addText($positionName, ['bold' => true]);
            $para1->addText(' of the ');
            $para1->addText(CompanyHelper::getName(), ['bold' => true]);
            $para1->addText(', has the following Year-to-Date (YTD) salary deductions based on the last three months:');

            // Table
            $table = $section->addTable([
                'borderSize' => 1,
                'borderColor' => '000000',
                'cellMargin' => Converter::pointToTwip(1),
            ]);

            // Header row
            $table->addRow();
            $table->addCell(Converter::inchToTwip(1.5))->addText('Month', ['bold' => true], ['spaceAfter' => 0], ['alignment' => WordJc::CENTER]);
            foreach ($columns as $col) {
                $table->addCell(Converter::inchToTwip(1))->addText($col, ['bold' => true], ['spaceAfter' => 0], ['alignment' => WordJc::CENTER]);
            }
            $table->addCell(Converter::inchToTwip(1))->addText('Total', ['bold' => true], ['spaceAfter' => 0], ['alignment' => WordJc::CENTER]);

            // Data rows
            if (empty($rows)) {
                $table->addRow();
                $cell = $table->addCell(Converter::inchToTwip(1.5 + count($columns) * 1 + 1));
                $cell->getStyle()->setGridSpan(count($columns) + 2);
                $cell->addText('No deduction records found for the last 3 months.', [], ['alignment' => WordJc::CENTER]);
            } else {
                foreach ($rows as $row) {
                    $table->addRow();
                    $monthLabel = str_replace(["\0", "\r"], '', (string)($row['month'] ?? ''));
                    $monthLabel = htmlspecialchars($monthLabel, ENT_XML1, 'UTF-8');
                    $table->addCell()->addText($monthLabel, [], ['alignment' => WordJc::CENTER]);
                    foreach ($columns as $col) {
                        $table->addCell()->addText(number_format($row[$col] ?? 0, 2), [], ['alignment' => WordJc::CENTER]);
                    }
                    $table->addCell()->addText(number_format($row['TOTAL'] ?? 0, 2), [], ['alignment' => WordJc::CENTER]);
                }
            }

            // Total row
            $table->addRow();
            $table->addCell()->addText('Total', ['bold' => true], ['alignment' => WordJc::CENTER]);
            foreach ($columns as $col) {
                $table->addCell()->addText(number_format($columnTotals[$col] ?? 0, 2), ['bold' => true], ['alignment' => WordJc::CENTER]);
            }
            $table->addCell()->addText(number_format($columnTotals['TOTAL'] ?? 0, 2), ['bold' => true], ['alignment' => WordJc::CENTER]);

            $section->addTextBreak(1);

            // Active Loans section
            if ($loans->isNotEmpty()) {
                $section->addText('Active Loans:', ['bold' => true], ['spaceAfter' => 120]);
                foreach ($loans as $loan) {
                    $loanName = str_replace(["\0", "\r"], '', (string)($loan->name ?? 'LOAN'));
                    $loanName = htmlspecialchars($loanName, ENT_XML1, 'UTF-8');
                    
                    $loanText = $section->addTextRun(['indentation' => ['left' => Converter::inchToTwip(0.25)], 'spaceAfter' => 60]);
                    $loanText->addText('• ');
                    $loanText->addText($loanName);
                    $loanText->addText(' — Amortization: ₱' . number_format($loan->amount, 2));
                    $loanText->addText(' | Balance: ₱' . number_format($loan->balance, 2));
                }
            }

            // Second paragraph
            $para2 = $section->addTextRun($paragraphStyle);
            $para2->addText('This certification is issued upon request for record purposes. The accuracy of this document may be verified by emailing the Human Resource Section at CompanyHelper::getEmail().');

            // Third paragraph
            $section->addTextBreak(1);
            $para3 = $section->addTextRun($paragraphStyle);
            $para3->addText('Issued this ');
            $para3->addText($day . $suffix);
            $para3->addText(' day of ');
            $para3->addText($issueDate->format('F Y'));
            $para3->addText(' at ' . CompanyHelper::getAddress() . '.');
            $section->addTextBreak(2);

            // Signatory (right aligned) - Clean and escape data
            $signatoryName = str_replace(["\0", "\r"], '', (string)($request->signatory ?? 'N/A'));
            $signatoryName = htmlspecialchars($signatoryName, ENT_XML1, 'UTF-8');
            
            $signatoryPosition = str_replace(["\0", "\r"], '', (string)($request->position ?? 'N/A'));
            $signatoryPosition = htmlspecialchars($signatoryPosition, ENT_XML1, 'UTF-8');
            
            $section->addText($signatoryName, ['bold' => true], ['alignment' => WordJc::END, 'spaceAfter' => 0]);
            $section->addText($signatoryPosition, [], ['alignment' => WordJc::END]);

            // Save and return
            $filename = 'certificate_of_salary_deductions_' . str_replace(' ', '_', $offboarding->full_name) . '_' . date('Ymd_His') . '.docx';
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate salary deduction certificate DOCX: ' . $e->getMessage());
        }
    }
}

