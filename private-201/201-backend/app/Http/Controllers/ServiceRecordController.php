<?php

namespace App\Http\Controllers;

use PDF;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class ServiceRecordController extends Controller
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

    /**
     * Get all service records
     */
    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");

            $data = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('service_records as f', 'a.id', '=', 'f.employee_id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->select(
                    'a.id',
                    'a.first_name',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name"),
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    'a.salary',
                    'a.date_hired',
                    DB::raw("CONCAT(e.name,' ',a.last_name) as name_sig")
                )
                ->distinct()
                ->orderBy('a.first_name', 'asc')
                ->get();

            return $this->successResponse($data, 'Service records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve service records: ' . $e->getMessage());
        }
    }

    /**
     * Print service record
     */
    public function print(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            if ($request->employee == null) {
                return $this->errorResponse('Please select employee.');
            }

            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->leftJoin('branches as br', 'br.id', '=', 'a.branch_id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                    'a.birthdate',
                    'a.birth_place',
                    'a.gender_id',
                    'e.name as name_prefix',
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    DB::raw("CASE WHEN ISNULL(a.branch_id,0) <> 0 THEN
                                  CASE WHEN br.is_main_branch = 1 THEN
                                        CAST(1 as INT)
                                       ELSE
                                        CAST(0 as INT)
                                   END
                                  ELSE
                                   CAST(2 AS INT)
                             END AS from_branch")
                )
                ->distinct()
                ->where('a.id', $request->employee)
                ->orderBy('first_name', 'asc')
                ->get();

            if ($employees->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $document_no = DB::table('document_numbers')->where('id', 8)->get();

            if ($document_no->isNotEmpty()) {
                if ($employees[0]->from_branch == 1) {
                    $footer = [
                        'document_no' => $document_no[0]->co_document_number,
                        'revision' => $document_no[0]->co_revision,
                    ];
                } elseif ($employees[0]->from_branch == 0) {
                    $footer = [
                        'document_no' => $document_no[0]->rd_document_number,
                        'revision' => $document_no[0]->rd_revision,
                    ];
                } else {
                    $footer = [
                        'document_no' => '',
                        'revision' => '',
                    ];
                }
            } else {
                $footer = [
                    'document_no' => '',
                    'revision' => '',
                ];
            }

            $service_records = $this->resolveServiceRecordEmploymentTypes(
                DB::table('service_records')
                    ->where('employee_id', $request->employee)
                    ->get()
            );

            $signatories = array(
                'signatory' => $request->signatory,
                'position' => $request->position
            );

            // For API, return the data instead of generating PDF
            return $this->successResponse([
                'employees' => $employees,
                'service_records' => $service_records,
                'signatories' => $signatories,
                'image' => $image,
                'footer' => $footer
            ], 'Service record data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve service record: ' . $e->getMessage());
        }
    }

    /**
     * Show specific service record
     */
    public function show($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $data = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('service_records as f', 'a.id', '=', 'f.employee_id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->select(
                    'a.id',
                    'a.first_name',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name"),
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    'a.salary',
                    'a.date_hired',
                    DB::raw("CONCAT(e.name,' ',a.last_name) as name_sig")
                )
                ->where('a.id', $id)
                ->first();

            if (!$data) {
                return $this->notFoundResponse('Service record not found');
            }

            return $this->successResponse($data, 'Service record retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve service record: ' . $e->getMessage());
        }
    }

    /**
     * Generate PDF for service record (separate method for PDF generation)
     */
    public function generatePdf(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            if ($request->employee == null) {
                return $this->errorResponse('Please select employee.');
            }

            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->leftJoin('branches as br', 'br.id', '=', 'a.branch_id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                    'a.birthdate',
                    'a.birth_place',
                    'a.gender_id',
                    'e.name as name_prefix',
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    DB::raw("CASE WHEN ISNULL(a.branch_id,0) <> 0 THEN
                                  CASE WHEN br.is_main_branch = 1 THEN
                                        CAST(1 as INT)
                                       ELSE
                                        CAST(0 as INT)
                                   END
                                  ELSE
                                   CAST(2 AS INT)
                             END AS from_branch")
                )
                ->distinct()
                ->where('a.id', $request->employee)
                ->orderBy('first_name', 'asc')
                ->get();

            if ($employees->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $document_no = DB::table('document_numbers')->where('id', 8)->get();
        

            if ($document_no->isNotEmpty()) {
                if ($employees[0]->from_branch == 1) {
                    $footer = [
                        'document_no' => $document_no[0]->co_document_number,
                        'revision' => $document_no[0]->co_revision,
                    ];
                } elseif ($employees[0]->from_branch == 0) {
                    $footer = [
                        'document_no' => $document_no[0]->rd_document_number,
                        'revision' => $document_no[0]->rd_revision,
                    ];
                } else {
                    $footer = [
                        'document_no' => '',
                        'revision' => '',
                    ];
                }
            } else {
                $footer = [
                    'document_no' => '',
                    'revision' => '',
                ];
            }

            $service_records = $this->resolveServiceRecordEmploymentTypes(
                DB::table('service_records')
                    ->where('employee_id', $request->employee)
                    ->get()
            );

            $signatories = array(
                'signatory' => $request->signatory,
                'position' => $request->position
            );

            $pdf = PDF::loadView('service_records.service_record_print', compact('employees', 'service_records', 'signatories', 'image', 'footer'))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');

            // Return PDF as base64 for API
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'service_record_' . $request->employee . '_' . date('Y-m-d') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate service record PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generate Excel for service record
     */
    public function generateExcel(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            if ($request->employee == null) {
                return $this->errorResponse('Please select employee.');
            }

            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->leftJoin('branches as br', 'br.id', '=', 'a.branch_id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE dbo.ufn_DecryptString(a.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END as last_name"),
                    'a.birthdate',
                    'a.birth_place',
                    'a.gender_id',
                    'e.name as name_prefix',
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    DB::raw("CASE WHEN ISNULL(a.branch_id,0) <> 0 THEN
                                  CASE WHEN br.is_main_branch = 1 THEN
                                        CAST(1 as INT)
                                       ELSE
                                        CAST(0 as INT)
                                   END
                                  ELSE
                                   CAST(2 AS INT)
                             END AS from_branch")
                )
                ->distinct()
                ->where('a.id', $request->employee)
                ->orderBy('first_name', 'asc')
                ->get();

            if ($employees->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $document_no = DB::table('document_numbers')->where('id', 8)->get();

            if ($document_no->isNotEmpty()) {
                if ($employees[0]->from_branch == 1) {
                    $footer = [
                        'document_no' => $document_no[0]->co_document_number,
                        'revision' => $document_no[0]->co_revision,
                    ];
                } elseif ($employees[0]->from_branch == 0) {
                    $footer = [
                        'document_no' => $document_no[0]->rd_document_number,
                        'revision' => $document_no[0]->rd_revision,
                    ];
                } else {
                    $footer = [
                        'document_no' => '',
                        'revision' => '',
                    ];
                }
            } else {
                $footer = [
                    'document_no' => '',
                    'revision' => '',
                ];
            }

            $service_records = $this->resolveServiceRecordEmploymentTypes(
                DB::table('service_records')
                    ->where('employee_id', $request->employee)
                    ->get()
            );

            $signatory = $request->signatory ?? '';
            $position = $request->position ?? '';

            // Create new Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Service Record');

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(15);
            $sheet->getColumnDimension('C')->setWidth(20);
            $sheet->getColumnDimension('D')->setWidth(15);
            $sheet->getColumnDimension('E')->setWidth(15);
            $sheet->getColumnDimension('F')->setWidth(30);
            $sheet->getColumnDimension('G')->setWidth(15);
            $sheet->getColumnDimension('H')->setWidth(15);
            $sheet->getColumnDimension('I')->setWidth(20);
            $sheet->getColumnDimension('J')->setWidth(20);

            $row = 1;

            // Title
            $sheet->setCellValue('A' . $row, 'SERVICE RECORD');
            $sheet->mergeCells('A' . $row . ':J' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
            $sheet->setCellValue('A' . $row, '(To be accomplished by Employer)');
            $sheet->mergeCells('A' . $row . ':J' . $row);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Employee Information
            $row += 2;
            foreach ($employees as $employee) {
                // Name
                $sheet->setCellValue('A' . $row, 'NAME:');
                $sheet->getStyle('A' . $row)->getFont()->setSize(12);
                $employeeName = $employee->last_name . ', ' . $employee->first_name . ' ' . strtoupper(substr($employee->middle_name ?? '', 0, 1));
                $sheet->setCellValue('B' . $row, $employeeName);
                $sheet->getStyle('B' . $row)->getFont()->setSize(12);
                $sheet->mergeCells('B' . $row . ':J' . $row);

                // Birth Date and Birth Place
                $row++;
                $sheet->setCellValue('A' . $row, 'BIRTH:');
                $sheet->getStyle('A' . $row)->getFont()->setSize(12);
                if ($employee->birthdate) {
                    $sheet->setCellValue('B' . $row, date('M d, Y', strtotime($employee->birthdate)));
                    $sheet->getStyle('B' . $row)->getFont()->setSize(12);
                }
                $sheet->setCellValue('C' . $row, 'BIRTHPLACE:');
                $sheet->getStyle('C' . $row)->getFont()->setSize(12);
                $sheet->setCellValue('D' . $row, $employee->birth_place ?? '');
                $sheet->getStyle('D' . $row)->getFont()->setSize(12);
                $sheet->mergeCells('D' . $row . ':J' . $row);

                // Certificate Paragraph
                $row += 2;
                $certText = 'This is to certify that the employee named hereinabove actually rendered services in this office as shown by the service record below, each line of which is supported by appointment and other papers actually issued by this office and approved by the authorities concerned:';
                $sheet->setCellValue('A' . $row, $certText);
                $sheet->mergeCells('A' . $row . ':J' . $row);
                $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_JUSTIFY);
                $sheet->getStyle('A' . $row)->getFont()->setSize(11);
                $sheet->getRowDimension($row)->setRowHeight(-1);

                // Table Headers
                $row += 2;
                $sheet->setCellValue('A' . $row, 'SERVICE');
                $sheet->mergeCells('A' . $row . ':B' . $row);
                $sheet->setCellValue('C' . $row, 'RECORD OF APPOINTMENT');
                $sheet->mergeCells('C' . $row . ':E' . $row);
                $sheet->setCellValue('F' . $row, 'OFFICE ENTITY/DIVISION');
                $sheet->mergeCells('F' . $row . ':G' . $row);
                $sheet->setCellValue('H' . $row, 'L/V ABS');
                $sheet->setCellValue('I' . $row, 'SEPARATION');
                $sheet->mergeCells('I' . $row . ':J' . $row);

                // Style header row
                $headerStyle = [
                    'font' => ['bold' => true, 'size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E0E0E0'],
                    ],
                ];
                $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($headerStyle);
                $sheet->getRowDimension($row)->setRowHeight(30);

                // Sub-headers
                $row++;
                $sheet->setCellValue('A' . $row, '(Inclusive Date)');
                $sheet->mergeCells('A' . $row . ':B' . $row);
                $sheet->setCellValue('C' . $row, 'Designation');
                $sheet->setCellValue('D' . $row, 'Salary');
                $sheet->setCellValue('E' . $row, 'Status');
                $sheet->setCellValue('F' . $row, 'Division');
                $sheet->setCellValue('G' . $row, 'BRANCH');
                $sheet->setCellValue('H' . $row, 'W/O PAY');
                $sheet->setCellValue('I' . $row, 'Date');
                $sheet->setCellValue('J' . $row, 'Remarks');

                $subHeaderStyle = [
                    'font' => ['bold' => true, 'size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ];
                $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($subHeaderStyle);

                // Third header row
                $row++;
                $sheet->setCellValue('A' . $row, 'From');
                $sheet->setCellValue('B' . $row, 'To');
                $sheet->setCellValue('C' . $row, '');
                $sheet->setCellValue('D' . $row, '(1)');
                $sheet->setCellValue('E' . $row, '(2)');
                $sheet->setCellValue('F' . $row, 'Station/Place of Assignment');
                $sheet->setCellValue('G' . $row, '(3)');
                $sheet->setCellValue('H' . $row, '');
                $sheet->setCellValue('I' . $row, '');
                $sheet->setCellValue('J' . $row, '');
                $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($subHeaderStyle);

                // Service Records Data
                $row++;
                foreach ($service_records as $service_record) {
                    $sheet->setCellValue('A' . $row, $service_record->start_date ?? '');
                    $sheet->setCellValue('B' . $row, $service_record->end_date ?? '');
                    $sheet->setCellValue('C' . $row, $service_record->designation ?? '');
                    $sheet->setCellValue('D' . $row, number_format($service_record->annual_salary ?? 0, 2, '.', ','));
                    $sheet->setCellValue('E' . $row, $service_record->employment_type ?? '');
                    $sheet->setCellValue('F' . $row, $service_record->place_of_assignment ?? '');
                    $sheet->setCellValue('G' . $row, $service_record->branch ?? '');
                    $sheet->setCellValue('H' . $row, $service_record->leave_without_pay ?? '');
                    $sheet->setCellValue('I' . $row, $service_record->separation_date ?? '');
                    $sheet->setCellValue('J' . $row, $service_record->cause ?? '');

                    // Apply borders to data rows
                    $dataStyle = [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        'font' => ['size' => 10],
                    ];
                    $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($dataStyle);
                    $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $row++;
                }

                // Footer text
                $row++;
                $sheet->setCellValue('A' . $row, '-X-X-X-X-X-X-X-X-X-X-X NOTHING FOLLOWS X-X-X-X-X-X-X-X-X-X-X-X-');
                $sheet->mergeCells('A' . $row . ':J' . $row);
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A' . $row)->getFont()->setItalic(true)->setSize(11);

                // Issued text
                $row += 2;
                $issuedText = 'Issued in compliance with Executive Order No. 54 dated August 10, 1954 and in accordance with Circular No. 58 dated August 10, 1954 of the system.';
                $sheet->setCellValue('A' . $row, $issuedText);
                $sheet->mergeCells('A' . $row . ':J' . $row);
                $sheet->getStyle('A' . $row)->getFont()->setSize(11);

                // Certified Correct
                $row += 2;
                $sheet->setCellValue('A' . $row, 'CERTIFIED CORRECT');
                $sheet->mergeCells('A' . $row . ':J' . $row);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Signatory
                $row += 2;
                $sheet->setCellValue('J' . $row, $signatory);
                $sheet->getStyle('J' . $row)->getFont()->setBold(true)->setUnderline(true)->setSize(12);
                $sheet->getStyle('J' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $row++;
                $sheet->setCellValue('J' . $row, $position);
                $sheet->getStyle('J' . $row)->getFont()->setItalic(true)->setSize(11);
                $sheet->getStyle('J' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Document number and revision
                $row += 3;
                $sheet->setCellValue('J' . $row, $footer['document_no']);
                $sheet->getStyle('J' . $row)->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('J' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $row++;
                $sheet->setCellValue('J' . $row, $footer['revision']);
                $sheet->getStyle('J' . $row)->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('J' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            $filename = 'service_record_' . $request->employee . '_' . date('Ymd_His') . '.xlsx';
            $tempPath = storage_path('app/' . $filename);

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate service record Excel: ' . $e->getMessage());
        }
    }

    /**
     * Resolve employment_type values stored as IDs to their display names.
     */
    private function resolveServiceRecordEmploymentTypes($serviceRecords)
    {
        if ($serviceRecords->isEmpty()) {
            return $serviceRecords;
        }

        $employmentTypeMap = DB::table('employment_types')->pluck('name', 'id');

        return $serviceRecords->map(function ($record) use ($employmentTypeMap) {
            $value = $record->employment_type ?? '';

            if ($value !== '' && is_numeric($value)) {
                $id = (int) $value;
                if ($employmentTypeMap->has($id)) {
                    $record->employment_type = $employmentTypeMap[$id];
                }
            }

            return $record;
        });
    }
}
