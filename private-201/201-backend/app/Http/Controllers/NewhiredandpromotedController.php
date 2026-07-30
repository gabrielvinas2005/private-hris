<?php




namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use Auth;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc as WordJc;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Illuminate\Support\Facades\Log;

class NewhiredandpromotedController extends Controller
{
    use ApiResponse;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }





    public function index()
    {
        try {
            $now   = Carbon::now();
            $year  = $now->year;
            $month = $now->month; // 1–12

            // NEWLY ONBOARDED: all hires this month
            // Use LEFT JOINs so employees are not dropped when related records
            // (employment type, position, department, plantilla) are missing.
            $newlyOnboarded = DB::table('employees as a')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('positions as e', 'a.position_id', '=', 'e.id')
                ->leftJoin('departments as f', 'a.department_id', '=', 'f.id')
                ->leftJoin('plantillas as g', 'a.plantilla_id', '=', 'g.id')
                ->select(
                    'a.id',
                    DB::raw("CONCAT(a.first_name, ' ', a.middle_name, ' ', a.last_name) as name"),
                    'f.code as department',
                    'e.name as position',
                    'a.date_hired as date',
                    DB::raw("'Onboarded' as remarks"),
                    'g.code as plantilla',
                    'd.name as employment_type'
                )
                ->whereYear('a.date_hired', $year)
                ->whereMonth('a.date_hired', $month)
                ->where('a.is_plantilla', 1);   // keep or remove depending on your rule

            // PROMOTED: (optionally also restrict to this month if you want)
            $promoted = DB::table('employees as a')
                ->join('employee_promotions as b', 'b.employee_id', '=', 'a.id')
                ->join('promotion_natures as c', 'b.nature_of_appointment_id', '=', 'c.id')
                ->join('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->join('positions as e', 'a.position_id', '=', 'e.id')
                ->join('departments as f', 'a.department_id', '=', 'f.id')
                ->join('plantillas as g', 'a.plantilla_id', '=', 'g.id')
                ->select(
                    'a.id',
                    DB::raw("CONCAT(a.first_name, ' ', a.middle_name, ' ', a.last_name) as name"),
                    'f.code as department',
                    'e.name as position',
                    'a.date_hired as date',   // or promotion date column if you have one
                    'c.name as remarks',
                    'g.code as plantilla',
                    'd.name as employment_type'
                );
            // if you also want promotions only for this month, add:
            // ->whereYear('b.effective_date', $year)
            // ->whereMonth('b.effective_date', $month)

            $data = $newlyOnboarded
                ->unionAll($promoted)
                ->orderBy('date', 'desc')
                ->get();

            return $this->successResponse($data, 'Newly Hired and Promoted page loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load Newly Hired and Promoted page: ' . $e->getMessage());
        }
    }

    /**
     * Generate PDF report for Newly Hired and Promoted (current month)
     */
    public function print(Request $request)
    {
        try {
            $now      = Carbon::now();
            $yearUsed = $now->year;
            $cyStart  = $yearUsed - 1;
            $cyEnd    = $yearUsed;

            $records = DB::table('employees as a')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('positions as e', 'a.position_id', '=', 'e.id')
                ->leftJoin('departments as f', 'a.department_id', '=', 'f.id')
                ->leftJoin('plantillas as g', 'a.plantilla_id', '=', 'g.id')
                ->select(
                    'a.id',
                    DB::raw("CONCAT(a.first_name, ' ', a.middle_name, ' ', a.last_name) as name"),
                    'f.code as department',
                    'e.name as position',
                    'a.date_hired as date',
                    DB::raw("'Onboarded' as remarks"),
                    'g.code as plantilla',
                    'd.name as employment_type'
                )
                ->whereYear('a.date_hired', $yearUsed)
                ->whereMonth('a.date_hired', $now->month)
                ->where('a.is_plantilla', 1)
                ->unionAll(
                    DB::table('employees as a')
                        ->join('employee_promotions as b', 'b.employee_id', '=', 'a.id')
                        ->join('promotion_natures as c', 'b.nature_of_appointment_id', '=', 'c.id')
                        ->join('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                        ->join('positions as e', 'a.position_id', '=', 'e.id')
                        ->join('departments as f', 'a.department_id', '=', 'f.id')
                        ->join('plantillas as g', 'a.plantilla_id', '=', 'g.id')
                        ->select(
                            'a.id',
                            DB::raw("CONCAT(a.first_name, ' ', a.middle_name, ' ', a.last_name) as name"),
                            'f.code as department',
                            'e.name as position',
                            'a.date_hired as date',
                            'c.name as remarks',
                            'g.code as plantilla',
                            'd.name as employment_type'
                        )
                )
                ->orderBy('date', 'desc')
                ->get();

            if ($records->isEmpty()) {
                return $this->notFoundResponse('No newly hired or promoted employees found for the selected period.');
            }

            // Format date for display
            $records = $records->map(function ($item) {
                try {
                    $item->date_formatted = $item->date
                        ? Carbon::parse($item->date)->format('F j, Y')
                        : '';
                } catch (\Exception $e) {
                    $item->date_formatted = $item->date;
                }
                return $item;
            });

            $pdf = \PDF::loadView('newly_hired_promoted.report', [
                'records' => $records,
                'cyStart' => $cyStart,
                'cyEnd'   => $cyEnd,
            ])->setOptions(['defaultFont' => 'sans-serif']);

            $pdf->setPaper('A4', 'landscape');
            $pdfContent = $pdf->output();

            $filename = 'newly_hired_and_promoted_' . date('Ymd') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate Newly Hired and Promoted report: ' . $e->getMessage());
        }
    }

    /**
     * Generate DOCX report for Newly Hired and Promoted (current month)
     */
    public function word(Request $request)
    {
        try {
            $now      = Carbon::now();
            $yearUsed = $now->year;
            $cyStart  = $yearUsed - 1;
            $cyEnd    = $yearUsed;

            $records = DB::table('employees as a')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('positions as e', 'a.position_id', '=', 'e.id')
                ->leftJoin('departments as f', 'a.department_id', '=', 'f.id')
                ->leftJoin('plantillas as g', 'a.plantilla_id', '=', 'g.id')
                ->select(
                    'a.id',
                    DB::raw("CONCAT(a.first_name, ' ', a.middle_name, ' ', a.last_name) as name"),
                    'f.code as department',
                    'e.name as position',
                    'a.date_hired as date',
                    DB::raw("'Onboarded' as remarks"),
                    'g.code as plantilla',
                    'd.name as employment_type'
                )
                ->whereYear('a.date_hired', $yearUsed)
                ->whereMonth('a.date_hired', $now->month)
                ->where('a.is_plantilla', 1)
                ->unionAll(
                    DB::table('employees as a')
                        ->join('employee_promotions as b', 'b.employee_id', '=', 'a.id')
                        ->join('promotion_natures as c', 'b.nature_of_appointment_id', '=', 'c.id')
                        ->join('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                        ->join('positions as e', 'a.position_id', '=', 'e.id')
                        ->join('departments as f', 'a.department_id', '=', 'f.id')
                        ->join('plantillas as g', 'a.plantilla_id', '=', 'g.id')
                        ->select(
                            'a.id',
                            DB::raw("CONCAT(a.first_name, ' ', a.middle_name, ' ', a.last_name) as name"),
                            'f.code as department',
                            'e.name as position',
                            'a.date_hired as date',
                            'c.name as remarks',
                            'g.code as plantilla',
                            'd.name as employment_type'
                        )
                )
                ->orderBy('date', 'desc')
                ->get();

            if ($records->isEmpty()) {
                return $this->notFoundResponse('No newly hired or promoted employees found for the selected period.');
            }

            // Format date for display
            $records = $records->map(function ($item) {
                try {
                    $item->date_formatted = $item->date
                        ? Carbon::parse($item->date)->format('F j, Y')
                        : '';
                } catch (\Exception $e) {
                    $item->date_formatted = $item->date;
                }
                return $item;
            });

            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(11);

            $section = $phpWord->addSection([
                'marginTop' => Converter::inchToTwip(0.5),
                'marginRight' => Converter::inchToTwip(0.5),
                'marginBottom' => Converter::inchToTwip(0.5),
                'marginLeft' => Converter::inchToTwip(0.5),
                'pageSizeW' => Converter::inchToTwip(11.69), // Landscape width
                'pageSizeH' => Converter::inchToTwip(8.27),   // Landscape height
            ]);

            // Title
            $section->addText('strtoupper(CompanyHelper::getName())', ['bold' => true], ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);
            $section->addText('LIST OF NEWLY HIRED/PROMOTED FOR CY ' . $cyStart . '-' . $cyEnd, ['bold' => true], ['alignment' => WordJc::CENTER, 'spaceAfter' => 240]);

            // Create table
            $table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 40,
                'cellMarginTop' => 40,
                'cellMarginRight' => 40,
                'cellMarginBottom' => 40,
                'cellMarginLeft' => 40,
            ]);

            //header cell style
            $headerCellStyle = [
                'bgColor' => 'D9D9D9',
                'valign' => 'center',
            ];

            // Table header
            $table->addRow();
            $table->addCell(Converter::inchToTwip(1.2), $headerCellStyle)->addText('DEPARTMENT', ['bold' => true], ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);
            $table->addCell(Converter::inchToTwip(2.3), $headerCellStyle)->addText('NAME', ['bold' => true], ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);
            $table->addCell(Converter::inchToTwip(2), $headerCellStyle)->addText('POSITION', ['bold' => true], ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);
            $table->addCell(Converter::inchToTwip(1.2), $headerCellStyle)->addText('DATE', ['bold' => true], ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);
            $table->addCell(Converter::inchToTwip(1.2), $headerCellStyle)->addText('REMARKS', ['bold' => true], ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);
            $table->addCell(Converter::inchToTwip(1.5), $headerCellStyle)->addText('PLANTILLA ITEM NO.', ['bold' => true], ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);
            $table->addCell(Converter::inchToTwip(1.5), $headerCellStyle)->addText('EMPLOYMENT', ['bold' => true], ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);

            // Table rows
            foreach ($records as $row) {
                $table->addRow();
                $table->addCell()->addText($row->department ?? '', [], ['spaceAfter' => 0]);
                $table->addCell()->addText(strtoupper($row->name ?? ''), [], ['spaceAfter' => 0]);
                $table->addCell()->addText($row->position ?? '', [], ['spaceAfter' => 0]);
                $table->addCell()->addText($row->date_formatted ?? '', [], ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);
                $table->addCell()->addText(strtoupper($row->remarks ?? ''), [], ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);
                $table->addCell()->addText($row->plantilla ?? '', [], ['spaceAfter' => 0]);
                $table->addCell()->addText(strtoupper($row->employment_type ?? ''), [], ['alignment' => WordJc::CENTER, 'spaceAfter' => 0]);
            }

            $filename = 'newly_hired_and_promoted_' . date('Ymd_His') . '.docx';
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Failed to generate Newly Hired and Promoted DOCX', [
                'error' => $e->getMessage(),
            ]);
            return $this->serverErrorResponse('Failed to generate Newly Hired and Promoted DOCX: ' . $e->getMessage());
        }
    }

    /**
     * Generate Excel report for Newly Hired and Promoted (current month)
     */
    public function excel(Request $request)
    {
        try {
            $now      = Carbon::now();
            $yearUsed = $now->year;
            $cyStart  = $yearUsed - 1;
            $cyEnd    = $yearUsed;

            $records = DB::table('employees as a')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('positions as e', 'a.position_id', '=', 'e.id')
                ->leftJoin('departments as f', 'a.department_id', '=', 'f.id')
                ->leftJoin('plantillas as g', 'a.plantilla_id', '=', 'g.id')
                ->select(
                    'a.id',
                    DB::raw("CONCAT(a.first_name, ' ', a.middle_name, ' ', a.last_name) as name"),
                    'f.code as department',
                    'e.name as position',
                    'a.date_hired as date',
                    DB::raw("'Onboarded' as remarks"),
                    'g.code as plantilla',
                    'd.name as employment_type'
                )
                ->whereYear('a.date_hired', $yearUsed)
                ->whereMonth('a.date_hired', $now->month)
                ->where('a.is_plantilla', 1)
                ->unionAll(
                    DB::table('employees as a')
                        ->join('employee_promotions as b', 'b.employee_id', '=', 'a.id')
                        ->join('promotion_natures as c', 'b.nature_of_appointment_id', '=', 'c.id')
                        ->join('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                        ->join('positions as e', 'a.position_id', '=', 'e.id')
                        ->join('departments as f', 'a.department_id', '=', 'f.id')
                        ->join('plantillas as g', 'a.plantilla_id', '=', 'g.id')
                        ->select(
                            'a.id',
                            DB::raw("CONCAT(a.first_name, ' ', a.middle_name, ' ', a.last_name) as name"),
                            'f.code as department',
                            'e.name as position',
                            'a.date_hired as date',
                            'c.name as remarks',
                            'g.code as plantilla',
                            'd.name as employment_type'
                        )
                )
                ->orderBy('date', 'desc')
                ->get();

            if ($records->isEmpty()) {
                return $this->notFoundResponse('No newly hired or promoted employees found for the selected period.');
            }

            // Format date for display
            $records = $records->map(function ($item) {
                try {
                    $item->date_formatted = $item->date
                        ? Carbon::parse($item->date)->format('F j, Y')
                        : '';
                } catch (\Exception $e) {
                    $item->date_formatted = $item->date;
                }
                return $item;
            });

            // Create new Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Newly Hired and Promoted');

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(15); // DEPARTMENT
            $sheet->getColumnDimension('B')->setWidth(30); // NAME
            $sheet->getColumnDimension('C')->setWidth(25); // POSITION
            $sheet->getColumnDimension('D')->setWidth(18); // DATE
            $sheet->getColumnDimension('E')->setWidth(15); // REMARKS
            $sheet->getColumnDimension('F')->setWidth(20); // PLANTILLA ITEM NO.
            $sheet->getColumnDimension('G')->setWidth(18); // EMPLOYMENT

            $row = 1;

            // Title
            $sheet->setCellValue('A' . $row, 'strtoupper(CompanyHelper::getName())');
            $sheet->mergeCells('A' . $row . ':G' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;

            $sheet->setCellValue('A' . $row, 'LIST OF NEWLY HIRED/PROMOTED FOR CY ' . $cyStart . '-' . $cyEnd);
            $sheet->mergeCells('A' . $row . ':G' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row += 2;

            // Table header
            $headerRow = $row;
            $sheet->setCellValue('A' . $row, 'DEPARTMENT');
            $sheet->setCellValue('B' . $row, 'NAME');
            $sheet->setCellValue('C' . $row, 'POSITION');
            $sheet->setCellValue('D' . $row, 'DATE');
            $sheet->setCellValue('E' . $row, 'REMARKS');
            $sheet->setCellValue('F' . $row, 'PLANTILLA ITEM NO.');
            $sheet->setCellValue('G' . $row, 'EMPLOYMENT');

            // Apply header style
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9D9D9'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ];
            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($headerStyle);
            $row++;

            // Table data rows
            foreach ($records as $record) {
                $sheet->setCellValue('A' . $row, $record->department ?? '');
                $sheet->setCellValue('B' . $row, strtoupper($record->name ?? ''));
                $sheet->setCellValue('C' . $row, $record->position ?? '');
                $sheet->setCellValue('D' . $row, $record->date_formatted ?? '');
                $sheet->setCellValue('E' . $row, strtoupper($record->remarks ?? ''));
                $sheet->setCellValue('F' . $row, $record->plantilla ?? '');
                $sheet->setCellValue('G' . $row, strtoupper($record->employment_type ?? ''));

                // Apply data row style
                $dataStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'font' => [
                        'size' => 11,
                    ],
                ];
                $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataStyle);
                
                // Center align for DATE, REMARKS, and EMPLOYMENT columns
                $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Left align for other columns
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                
                $row++;
            }

            // Set row heights
            $sheet->getRowDimension(1)->setRowHeight(25);
            $sheet->getRowDimension(2)->setRowHeight(25);
            $sheet->getRowDimension($headerRow)->setRowHeight(25);

            // Set page orientation to landscape
            $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

            $filename = 'newly_hired_and_promoted_' . date('Ymd_His') . '.xlsx';
            $tempPath = storage_path('app/' . $filename);

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Failed to generate Newly Hired and Promoted Excel', [
                'error' => $e->getMessage(),
            ]);
            return $this->serverErrorResponse('Failed to generate Newly Hired and Promoted Excel: ' . $e->getMessage());
        }
    }
}
