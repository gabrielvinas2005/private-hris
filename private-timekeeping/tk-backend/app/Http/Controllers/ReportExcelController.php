<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use App\Traits\ApiResponse;

class ReportExcelController extends Controller
{
    use ApiResponse;

    /**
     * Generate Excel from structured data and stream as download.
     */
    public function generate(Request $request)
    {
        try {
            $validated = $request->validate([
                'report_type' => 'required|string',
                'data' => 'required|array',
                'filename' => 'nullable|string',
            ]);

            $reportType = $validated['report_type'];
            $data = $validated['data'];
            $filename = $validated['filename'] ?? ($reportType . '_' . date('Ymd_His'));

            // Create Excel document based on report type
            $spreadsheet = $this->createExcelDocument($reportType, $data);

            // Generate temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'excel_report_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            // Return file as download
            $excelContent = file_get_contents($tempFile);
            unlink($tempFile); // Clean up

            // Get origin from request for CORS (required when credentials are included)
            $origin = $request->headers->get('Origin') ?? '*';
            
            return response($excelContent)
                ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '.xlsx"')
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->header('Access-Control-Allow-Credentials', 'true');

        } catch (\Exception $e) {
            Log::error('Excel generation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->serverErrorResponse('Failed to generate Excel document: ' . $e->getMessage());
        }
    }

    /**
     * Create Excel document based on report type
     */
    private function createExcelDocument($reportType, $data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator(CompanyHelper::getName() . ' Timekeeping System')
            ->setLastModifiedBy('System User')
            ->setTitle(ucfirst(str_replace('_', ' ', $reportType)) . ' Report')
            ->setDescription('Generated report from ' . CompanyHelper::getName() . ' Timekeeping System')
            ->setSubject('Timekeeping Report');

        // Generate content based on report type
        switch ($reportType) {
            case 'attendance_report':
                $this->createAttendanceReport($sheet, $data);
                break;
            case 'shift_schedule_report':
                $this->createShiftScheduleReport($sheet, $data);
                break;
            case 'assigned_employees_report':
                $this->createAssignedEmployeesReport($sheet, $data);
                break;
            case 'shifting_schedule_details_report':
                $this->createShiftingScheduleDetailsReport($sheet, $data);
                break;
            case 'fix_schedule_detail':
                $this->createFixScheduleDetailReport($sheet, $data);
                break;
            case 'fix_schedule_assigned':
                $this->createFixScheduleAssignedReport($sheet, $data);
                break;
            case 'fix_schedule_list':
                $this->createFixScheduleListReport($sheet, $data);
                break;
            case 'shift_schedule_list':
                $this->createShiftScheduleListReport($sheet, $data);
                break;
            case 'shift_schedule_viewer':
                $this->createShiftScheduleViewerReport($sheet, $data);
                break;
            case 'shift_schedule_employees':
                $this->createShiftScheduleEmployeesReport($sheet, $data);
                break;
            case 'leave_credits_list':
                $this->createLeaveCreditsListReport($sheet, $data);
                break;
            case 'leave_monitoring':
                $this->createLeaveMonitoringReport($sheet, $data);
                break;
            case 'leave_credit_card_monitoring':
                $this->createLeaveCreditCardMonitoringReport($sheet, $data);
                break;
            case 'ob_monitoring':
                $this->createOBMonitoringReport($sheet, $data);
                break;
            case 'ot_monitoring':
                $this->createOTMonitoringReport($sheet, $data);
                break;
            case 'coc_monitoring':
                $this->createCOCMonitoringReport($sheet, $data);
                break;
            case 'work_suspension':
                $this->createWorkSuspensionReport($sheet, $data);
                break;
            case 'daily_attendance_summary':
                $this->createDailyAttendanceSummaryReport($sheet, $data);
                break;
            case 'late_report':
            case 'tardiness_report':
                $this->createTardinessReport($sheet, $data, 'late');
                break;
            case 'undertime_report':
                $this->createTardinessReport($sheet, $data, 'undertime');
                break;
            case 'absences_report':
                $this->createTardinessReport($sheet, $data, 'absences');
                break;
            case 'combined_tardiness_report':
                $this->createTardinessReport($sheet, $data, 'combined');
                break;
            default:
                $this->createGenericReport($sheet, $data, $reportType);
                break;
        }

        return $spreadsheet;
    }

    /**
     * Create attendance report
     */
    private function createAttendanceReport($sheet, $data)
    {
        $currentRow = 1;

        // Add header
        $this->addReportHeader($sheet, $currentRow, 'ATTENDANCE REPORT', $data);
        $currentRow += 4;

        // Add summary if available
        if (isset($data['summary'])) {
            $this->addSummarySection($sheet, $currentRow, $data['summary']);
            $currentRow += 3;
        }

        // Add employee data table
        if (isset($data['employees']) && is_array($data['employees'])) {
            $this->addEmployeeTable($sheet, $currentRow, $data['employees'], 'attendance');
        }
    }

    /**
     * Create shift schedule report
     */
    private function createShiftScheduleReport($sheet, $data)
    {
        $currentRow = 1;

        // Add header
        $this->addReportHeader($sheet, $currentRow, 'SHIFT SCHEDULE REPORT', $data);
        $currentRow += 4;

        // Add schedule data table
        if (isset($data['schedules']) && is_array($data['schedules'])) {
            $this->addShiftScheduleTable($sheet, $currentRow, $data['schedules']);
        }
    }

    /**
     * Create assigned employees report
     */
    private function createAssignedEmployeesReport($sheet, $data)
    {
        $currentRow = 1;

        // Add header
        $this->addReportHeader($sheet, $currentRow, 'ASSIGNED EMPLOYEES REPORT', $data);
        $currentRow += 4;

        // Add employee data table
        if (isset($data['employees']) && is_array($data['employees'])) {
            $this->addEmployeeTable($sheet, $currentRow, $data['employees'], 'assignment');
        }
    }

    /**
     * Create shifting schedule details report
     */
    private function createShiftingScheduleDetailsReport($sheet, $data)
    {
        $currentRow = 1;

        // Add header
        $this->addReportHeader($sheet, $currentRow, 'SHIFTING SCHEDULE DETAILS REPORT', $data);
        $currentRow += 4;

        // Add detailed schedule table
        if (isset($data['schedule_data']) && is_array($data['schedule_data'])) {
            $this->addDetailedScheduleTable($sheet, $currentRow, $data['schedule_data']);
        }
    }

    /**
     * Create generic report
     */
    private function createGenericReport($sheet, $data, $reportType)
    {
        $currentRow = 1;

        // Add header
        $this->addReportHeader($sheet, $currentRow, strtoupper(str_replace('_', ' ', $reportType)) . ' REPORT', $data);
        $currentRow += 4;

        // Add generic content
        if (isset($data['content'])) {
            $sheet->setCellValue('A' . $currentRow, $data['content']);
        }
    }

    /**
     * Add report header with company info
     */
    private function addReportHeader($sheet, $startRow, $title, $data)
    {
        // Company header
        $sheet->setCellValue('A' . $startRow, 'Department Of Trade and Industry');
        $sheet->mergeCells('A' . $startRow . ':D' . $startRow);
        $sheet->getStyle('A' . $startRow)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A' . $startRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A' . ($startRow + 1), strtoupper(CompanyHelper::getName()));
        $sheet->mergeCells('A' . ($startRow + 1) . ':D' . ($startRow + 1));
        $sheet->getStyle('A' . ($startRow + 1))->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . ($startRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Report title
        $sheet->setCellValue('A' . ($startRow + 2), $title);
        $sheet->mergeCells('A' . ($startRow + 2) . ':D' . ($startRow + 2));
        $sheet->getStyle('A' . ($startRow + 2))->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A' . ($startRow + 2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Add spacing
        $sheet->getRowDimension($startRow + 3)->setRowHeight(10);
    }

    /**
     * Add summary section
     */
    private function addSummarySection($sheet, $startRow, $summary)
    {
        $row = $startRow;
        
        if (isset($summary['total_employees'])) {
            $sheet->setCellValue('A' . $row, 'Total Employees: ' . $summary['total_employees']);
            $row++;
        }
        
        if (isset($summary['total_days_present'])) {
            $sheet->setCellValue('A' . $row, 'Total Days Present: ' . $summary['total_days_present']);
            $row++;
        }
        
        if (isset($summary['average_days_present'])) {
            $sheet->setCellValue('A' . $row, 'Average Days Present: ' . $summary['average_days_present']);
        }
    }

    /**
     * Add shift schedule table
     */
    private function addShiftScheduleTable($sheet, $startRow, $schedules)
    {
        if (empty($schedules)) {
            $sheet->setCellValue('A' . $startRow, 'No schedule data available.');
            return;
        }

        $row = $startRow;

        // Table headers
        $headers = ['#', 'Schedule Name', 'Date From', 'Date To'];
        $col = 'A';
        
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $sheet->getStyle($col . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D0D0D0');
            $sheet->getStyle($col . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $col++;
        }
        $row++;

        // Add schedule data
        foreach ($schedules as $index => $schedule) {
            $col = 'A';
            $rowData = [
                $index + 1,
                $schedule['schedule_name'] ?? $schedule['name'] ?? '',
                $schedule['date_from'] ?? '',
                $schedule['date_to'] ?? ''
            ];
            
            foreach ($rowData as $data) {
                $sheet->setCellValue($col . $row, $data);
                $sheet->getStyle($col . $row)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                $col++;
            }
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'D') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Add employee table
     */
    private function addEmployeeTable($sheet, $startRow, $employees, $type)
    {
        if (empty($employees)) {
            $sheet->setCellValue('A' . $startRow, 'No employee data available.');
            return;
        }

        $row = $startRow;

        // Table headers based on type
        $headers = $this->getEmployeeTableHeaders($type);
        $col = 'A';
        
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $sheet->getStyle($col . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D0D0D0');
            $sheet->getStyle($col . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $col++;
        }
        $row++;

        // Add employee data
        foreach ($employees as $employee) {
            $col = 'A';
            $rowData = $this->getEmployeeTableRowData($employee, $type);
            
            foreach ($rowData as $data) {
                $sheet->setCellValue($col . $row, $data);
                $sheet->getStyle($col . $row)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                $col++;
            }
            $row++;
        }

        // Auto-size columns
        $lastCol = chr(ord('A') + count($headers) - 1);
        foreach (range('A', $lastCol) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Add detailed schedule table
     */
    private function addDetailedScheduleTable($sheet, $startRow, $scheduleData)
    {
        if (empty($scheduleData)) {
            $sheet->setCellValue('A' . $startRow, 'No detailed schedule data available.');
            return;
        }

        $row = $startRow;

        // Table headers for detailed schedule
        $headers = ['Employee ID', 'Employee Name', 'Schedule', 'Start Time', 'End Time', 'Days'];
        $col = 'A';
        
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $sheet->getStyle($col . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D0D0D0');
            $sheet->getStyle($col . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $col++;
        }
        $row++;

        // Add detailed schedule data
        foreach ($scheduleData as $data) {
            $col = 'A';
            $rowData = [
                $data['employee_id'] ?? '',
                $data['employee_name'] ?? '',
                $data['schedule'] ?? '',
                $data['start_time'] ?? '',
                $data['end_time'] ?? '',
                $data['days'] ?? ''
            ];
            
            foreach ($rowData as $value) {
                $sheet->setCellValue($col . $row, $value);
                $sheet->getStyle($col . $row)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                $col++;
            }
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Get employee table headers based on type
     */
    private function getEmployeeTableHeaders($type)
    {
        switch ($type) {
            case 'attendance':
                return ['Employee ID', 'Name', 'Department', 'Date', 'Time In', 'Time Out', 'Hours Worked', 'Status'];
            case 'assignment':
                return ['Employee ID', 'Name', 'Position', 'Department', 'Shift', 'Schedule'];
            case 'fix_schedule_assigned':
                return ['Employee No', 'Name', 'Position', 'Department', 'Employment Type'];
            case 'shift_schedule_employees':
                return ['Employee No', 'Name', 'Position', 'Department', 'Employment Type'];
            default:
                return ['Employee ID', 'Name', 'Department', 'Position'];
        }
    }

    /**
     * Get employee table row data based on type
     */
    private function getEmployeeTableRowData($employee, $type)
    {
        switch ($type) {
            case 'attendance':
                return [
                    $employee['employee_id'] ?? '',
                    $employee['name'] ?? '',
                    $employee['department'] ?? '',
                    $employee['date'] ?? '',
                    $employee['time_in'] ?? '',
                    $employee['time_out'] ?? '',
                    $employee['hours_worked'] ?? '',
                    $employee['status'] ?? ''
                ];
            case 'assignment':
                return [
                    $employee['employee_id'] ?? '',
                    $employee['name'] ?? '',
                    $employee['position'] ?? '',
                    $employee['department'] ?? '',
                    $employee['shift'] ?? '',
                    $employee['schedule'] ?? ''
                ];
            case 'fix_schedule_assigned':
                return [
                    $employee['employee_no'] ?? $employee['employee_id'] ?? '',
                    $employee['name'] ?? '',
                    $employee['position'] ?? '',
                    $employee['department'] ?? '',
                    $employee['employment_type'] ?? ''
                ];
            case 'shift_schedule_employees':
                return [
                    $employee['employee_no'] ?? $employee['employee_id'] ?? '',
                    $employee['name'] ?? '',
                    $employee['position'] ?? '',
                    $employee['department'] ?? '',
                    $employee['employment_type'] ?? ''
                ];
            default:
                return [
                    $employee['employee_id'] ?? '',
                    $employee['name'] ?? '',
                    $employee['department'] ?? '',
                    $employee['position'] ?? ''
                ];
        }
    }

    /**
     * Create fix schedule detail report
     */
    private function createFixScheduleDetailReport($sheet, $data)
    {
        $currentRow = 1;

        // Add header
        $scheduleName = $data['schedule_name'] ?? 'Fix Schedule';
        $this->addReportHeader($sheet, $currentRow, 'FIX SCHEDULE DETAIL REPORT', $data);
        $currentRow += 4;

        // Add schedule name
        $sheet->setCellValue('A' . $currentRow, 'Schedule Name: ' . $scheduleName);
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
        $currentRow += 2;

        // Add flags if available
        if (isset($data['flags'])) {
            $flags = $data['flags'];
            $flagText = [];
            if (!empty($flags['no_late'])) $flagText[] = 'No Late';
            if (!empty($flags['no_undertime'])) $flagText[] = 'No Undertime';
            if (!empty($flags['is_complete_attendance'])) $flagText[] = 'Complete Attendance';
            if (!empty($flagText)) {
                $sheet->setCellValue('A' . $currentRow, 'Rules: ' . implode(', ', $flagText));
                $currentRow += 2;
            }
        }

        // Add schedule days table
        if (isset($data['days']) && is_array($data['days']) && !empty($data['days'])) {
            $this->addFixScheduleDetailTable($sheet, $currentRow, $data['days']);
        }
    }

    /**
     * Create fix schedule assigned employees report
     */
    private function createFixScheduleAssignedReport($sheet, $data)
    {
        $currentRow = 1;

        // Add header
        $scheduleName = $data['schedule_name'] ?? 'Fix Schedule';
        $this->addReportHeader($sheet, $currentRow, 'ASSIGNED EMPLOYEES REPORT', $data);
        $currentRow += 4;

        // Add schedule name
        $sheet->setCellValue('A' . $currentRow, 'Schedule: ' . $scheduleName);
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
        $currentRow += 2;

        // Add employee data table
        if (isset($data['employees']) && is_array($data['employees'])) {
            $this->addEmployeeTable($sheet, $currentRow, $data['employees'], 'fix_schedule_assigned');
        }
    }

    /**
     * Create fix schedule list report
     */
    private function createFixScheduleListReport($sheet, $data)
    {
        $currentRow = 1;

        // Add header
        $this->addReportHeader($sheet, $currentRow, 'FIX SCHEDULE REPORT', $data);
        $currentRow += 4;

        // Add schedule data table
        if (isset($data['schedules']) && is_array($data['schedules'])) {
            $this->addFixScheduleListTable($sheet, $currentRow, $data['schedules']);
        }
    }

    /**
     * Add fix schedule detail table
     */
    private function addFixScheduleDetailTable($sheet, $startRow, $days)
    {
        if (empty($days)) {
            $sheet->setCellValue('A' . $startRow, 'No schedule data available.');
            return;
        }

        $row = $startRow;

        // Determine which columns to include
        $hasND = false;
        $hasGrace = false;
        $hasFlexi = false;
        foreach ($days as $day) {
            if (!empty($day['with_nd'])) $hasND = true;
            if (!empty($day['grace_period'])) $hasGrace = true;
            if (!empty($day['flexi_hours'])) $hasFlexi = true;
        }

        // Build headers
        $headers = ['Day', 'WFH', 'AM - In', 'AM - Out', 'Break - In', 'Break - Out', 'PM - In', 'PM - Out'];
        if ($hasND) {
            $headers = array_merge($headers, ['With ND', 'ND Start', 'ND End', 'ND Rate']);
        }
        if ($hasGrace) {
            $headers[] = 'Grace (min)';
        }
        if ($hasFlexi) {
            $headers[] = 'Flexi (hrs)';
        }
        $headers[] = 'Work Hours';

        // Add headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $sheet->getStyle($col . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D0D0D0');
            $sheet->getStyle($col . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $col++;
        }
        $row++;

        // Add day data
        foreach ($days as $day) {
            $col = 'A';
            $isRestday = !empty($day['is_restday']);
            $dayName = ($day['name'] ?? '') . ($isRestday ? ' (Rest Day)' : '');

            $rowData = [
                $dayName,
                $isRestday ? '' : (!empty($day['is_wfh']) ? 'Yes' : 'No'),
                $isRestday ? '' : ($day['am_in'] ?? ''),
                $isRestday ? '' : ($day['am_out'] ?? ''),
                $isRestday ? '' : ($day['break_in'] ?? ''),
                $isRestday ? '' : ($day['break_out'] ?? ''),
                $isRestday ? '' : ($day['pm_in'] ?? ''),
                $isRestday ? '' : ($day['pm_out'] ?? ''),
            ];

            if ($hasND) {
                $rowData[] = $isRestday ? '' : (!empty($day['with_nd']) ? 'Yes' : 'No');
                $rowData[] = $isRestday ? '' : ($day['nd_start'] ?? '');
                $rowData[] = $isRestday ? '' : ($day['nd_end'] ?? '');
                $rowData[] = $isRestday ? '' : ($day['nd_rate'] ?? '');
            }
            if ($hasGrace) {
                $rowData[] = $isRestday ? '' : ($day['grace_period'] ?? '');
            }
            if ($hasFlexi) {
                $rowData[] = $isRestday ? '' : ($day['flexi_hours'] ?? '');
            }
            $rowData[] = $isRestday ? '' : ($day['work_hours'] ?? '');

            foreach ($rowData as $data) {
                $sheet->setCellValue($col . $row, $data);
                $sheet->getStyle($col . $row)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                $col++;
            }
            $row++;
        }

        // Auto-size columns
        $lastCol = chr(ord('A') + count($headers) - 1);
        foreach (range('A', $lastCol) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Add fix schedule list table
     */
    private function addFixScheduleListTable($sheet, $startRow, $schedules)
    {
        if (empty($schedules)) {
            $sheet->setCellValue('A' . $startRow, 'No schedule data available.');
            return;
        }

        $row = $startRow;

        // Table headers
        $headers = ['#', 'Schedule Name', 'Created At', 'Updated At'];
        $col = 'A';
        
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $sheet->getStyle($col . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D0D0D0');
            $sheet->getStyle($col . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $col++;
        }
        $row++;

        // Add schedule data
        foreach ($schedules as $index => $schedule) {
            $col = 'A';
            $rowData = [
                $index + 1,
                $schedule['name'] ?? $schedule['schedule_name'] ?? '',
                $schedule['created_at'] ?? '',
                $schedule['updated_at'] ?? ''
            ];
            
            foreach ($rowData as $data) {
                $sheet->setCellValue($col . $row, $data);
                $sheet->getStyle($col . $row)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                $col++;
            }
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'D') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Create shift schedule list report
     */
    private function createShiftScheduleListReport($sheet, $data)
    {
        $currentRow = 1;

        // Add header
        $this->addReportHeader($sheet, $currentRow, 'SHIFT SCHEDULE REPORT', $data);
        $currentRow += 4;

        // Add schedule data table
        if (isset($data['schedules']) && is_array($data['schedules'])) {
            $this->addShiftScheduleListTable($sheet, $currentRow, $data['schedules']);
        }
    }

    /**
     * Create shift schedule viewer report
     */
    private function createShiftScheduleViewerReport($sheet, $data)
    {
        $currentRow = 1;

        // Add header
        $scheduleName = $data['schedule_name'] ?? 'Shift Schedule';
        $this->addReportHeader($sheet, $currentRow, 'SHIFT SCHEDULE VIEWER REPORT', $data);
        $currentRow += 4;

        // Add schedule name and date range
        $sheet->setCellValue('A' . $currentRow, 'Schedule Name: ' . $scheduleName);
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
        $currentRow += 1;

        if (isset($data['date_from']) || isset($data['date_to'])) {
            $dateRange = '';
            if (isset($data['date_from'])) $dateRange .= 'From: ' . $data['date_from'];
            if (isset($data['date_from']) && isset($data['date_to'])) $dateRange .= ' ';
            if (isset($data['date_to'])) $dateRange .= 'To: ' . $data['date_to'];
            $sheet->setCellValue('A' . $currentRow, 'Period: ' . $dateRange);
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
            $currentRow += 2;
        } else {
            $currentRow += 1;
        }

        // Add schedule data table
        if (isset($data['schedule_data']) && is_array($data['schedule_data']) && !empty($data['schedule_data'])) {
            $this->addShiftScheduleViewerTable($sheet, $currentRow, $data['schedule_data']);
        }
    }

    /**
     * Create shift schedule employees report
     */
    private function createShiftScheduleEmployeesReport($sheet, $data)
    {
        $currentRow = 1;

        // Add header
        $scheduleName = $data['schedule_name'] ?? 'Shift Schedule';
        $this->addReportHeader($sheet, $currentRow, 'ASSIGNED EMPLOYEES REPORT', $data);
        $currentRow += 4;

        // Add schedule name
        $sheet->setCellValue('A' . $currentRow, 'Schedule: ' . $scheduleName);
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
        $currentRow += 2;

        // Add employee data table
        if (isset($data['employees']) && is_array($data['employees'])) {
            $this->addEmployeeTable($sheet, $currentRow, $data['employees'], 'shift_schedule_employees');
        }
    }

    /**
     * Create Leave Credits List Report (per-employee format, not table)
     */
    private function createLeaveCreditsListReport($sheet, $data)
    {
        $currentRow = 1;

        // Add header
        $this->addReportHeader($sheet, $currentRow, 'LEAVE CREDITS REPORT', $data);
        $currentRow += 4;

        $employees = $data['employees'] ?? [];
        
        if (empty($employees)) {
            $sheet->setCellValue('A' . $currentRow, 'No employee data available.');
            return;
        }

        // Add each employee with their leave credits
        foreach ($employees as $index => $employee) {
            // Employee number and name
            $sheet->setCellValue('A' . $currentRow, ($index + 1) . '. ' . ($employee['name'] ?? 'Unknown'));
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
            $currentRow++;

            // Employee details
            $details = [];
            if (!empty($employee['employee_no'])) {
                $details[] = 'Employee No: ' . $employee['employee_no'];
            }
            if (!empty($employee['position'])) {
                $details[] = 'Position: ' . $employee['position'];
            }
            if (!empty($employee['department'])) {
                $details[] = 'Department: ' . $employee['department'];
            }
            if (!empty($details)) {
                $sheet->setCellValue('A' . $currentRow, implode(' | ', $details));
                $sheet->getStyle('A' . $currentRow)->getFont()->setSize(10);
                $sheet->getStyle('A' . $currentRow)->getFont()->getColor()->setRGB('606266');
                $currentRow++;
            }

            // Leave credits
            if (!empty($employee['leave_credits']) && is_array($employee['leave_credits'])) {
                foreach ($employee['leave_credits'] as $credit) {
                    $creditName = $credit['leave_type_name'] ?? 'Unknown Leave Type';
                    $creditValue = number_format($credit['credits'] ?? 0, 2);
                    $sheet->setCellValue('B' . $currentRow, $creditName . ' - ' . $creditValue);
                    $sheet->getStyle('B' . $currentRow)->getFont()->setSize(11);
                    $currentRow++;
                }
            } else {
                $sheet->setCellValue('B' . $currentRow, 'No leave credits available');
                $sheet->getStyle('B' . $currentRow)->getFont()->setItalic(true);
                $sheet->getStyle('B' . $currentRow)->getFont()->getColor()->setRGB('909399');
                $currentRow++;
            }

            // Add spacing between employees
            $currentRow += 1;
        }

        // Auto-size columns
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }

    /**
     * Add shift schedule list table
     */
    private function addShiftScheduleListTable($sheet, $startRow, $schedules)
    {
        if (empty($schedules)) {
            $sheet->setCellValue('A' . $startRow, 'No schedule data available.');
            return;
        }

        $row = $startRow;

        // Table headers
        $headers = ['#', 'Schedule Name', 'Date From', 'Date To'];
        $col = 'A';
        
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $sheet->getStyle($col . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D0D0D0');
            $sheet->getStyle($col . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $col++;
        }
        $row++;

        // Add schedule data
        foreach ($schedules as $index => $schedule) {
            $col = 'A';
            $rowData = [
                $index + 1,
                $schedule['name'] ?? $schedule['schedule_name'] ?? '',
                $schedule['date_from'] ?? '',
                $schedule['date_to'] ?? ''
            ];
            
            foreach ($rowData as $data) {
                $sheet->setCellValue($col . $row, $data);
                $sheet->getStyle($col . $row)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                $col++;
            }
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'D') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Add shift schedule viewer table
     */
    private function addShiftScheduleViewerTable($sheet, $startRow, $scheduleData)
    {
        if (empty($scheduleData)) {
            $sheet->setCellValue('A' . $startRow, 'No schedule data available.');
            return;
        }

        $row = $startRow;

        // Table headers for shift schedule viewer
        $headers = ['Date', 'WFH', 'AM - In', 'AM - Out', 'Break - In', 'Break - Out', 'PM - In', 'PM - Out', 'Grace Period', 'Flexi Hours', 'Work Hours'];
        $col = 'A';
        
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $sheet->getStyle($col . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D0D0D0');
            $sheet->getStyle($col . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $col++;
        }
        $row++;

        // Add schedule data
        foreach ($scheduleData as $data) {
            $col = 'A';
            $rowData = [
                $data['shift_date'] ?? $data['date'] ?? '',
                !empty($data['is_wfh']) ? 'Yes' : 'No',
                $data['am_in'] ?? '',
                $data['am_out'] ?? '',
                $data['break_in'] ?? '',
                $data['break_out'] ?? '',
                $data['pm_in'] ?? '',
                $data['pm_out'] ?? '',
                $data['grace_period'] ?? '',
                $data['flexi_hours'] ?? '',
                $data['work_hours'] ?? ''
            ];
            
            foreach ($rowData as $value) {
                $sheet->setCellValue($col . $row, $value);
                $sheet->getStyle($col . $row)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                $col++;
            }
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Create leave monitoring report
     */
    private function createLeaveMonitoringReport($sheet, $data)
    {
        $currentRow = 1;

        // Get tab display name
        $tabDisplayName = $data['tab_display_name'] ?? 'Leave Monitoring';
        $reportTitle = 'LEAVE MONITORING REPORT - ' . strtoupper($tabDisplayName);

        // Add header
        $this->addReportHeader($sheet, $currentRow, $reportTitle, $data);
        $currentRow += 1;

        $leaves = $data['leaves'] ?? [];
        
        if (empty($leaves)) {
            $sheet->setCellValue('A' . $currentRow, 'No leave data available.');
            return;
        }

        // Group leaves by employee
        $groupedByEmployee = [];
        foreach ($leaves as $leave) {
            $empKey = $leave['employee_id'] ?? ($leave['employee_no'] . '_' . ($leave['name'] ?? ''));
            if (!isset($groupedByEmployee[$empKey])) {
                $groupedByEmployee[$empKey] = [
                    'employee' => $leave,
                    'leaves' => []
                ];
            }
            $groupedByEmployee[$empKey]['leaves'][] = $leave;
        }

        // Add each employee with all their leave applications
        $empIndex = 0;
        foreach ($groupedByEmployee as $group) {
            $employee = $group['employee'];
            $employeeLeaves = $group['leaves'];
            
            // Employee number and name (shown once per employee)
            $sheet->setCellValue('A' . $currentRow, ($empIndex + 1) . '. ' . ($employee['name'] ?? 'Unknown'));
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
            $currentRow++;

            // Employee details
            $details = [];
            if (!empty($employee['employee_no'])) {
                $details[] = 'Employee No: ' . $employee['employee_no'];
            }
            if (!empty($employee['position'])) {
                $details[] = 'Position: ' . $employee['position'];
            }
            if (!empty($employee['department'])) {
                $details[] = 'Department: ' . $employee['department'];
            }
            if (!empty($details)) {
                $sheet->setCellValue('A' . $currentRow, implode(' | ', $details));
                $sheet->getStyle('A' . $currentRow)->getFont()->setSize(10);
                $sheet->getStyle('A' . $currentRow)->getFont()->getColor()->setRGB('606266');
                $currentRow++;
            }

            // Display all leave applications for this employee
            foreach ($employeeLeaves as $leaveIndex => $leave) {
                // Application header (if multiple applications)
                if (count($employeeLeaves) > 1) {
                    $sheet->setCellValue('B' . $currentRow, 'Application ' . ($leaveIndex + 1) . ':');
                    $sheet->getStyle('B' . $currentRow)->getFont()->setBold(true)->setSize(11);
                    $sheet->getStyle('B' . $currentRow)->getFont()->getColor()->setRGB('606266');
                    $currentRow++;
                }

                // Leave details
                $leaveDetails = [];
            
            if (!empty($leave['leave_type'])) {
                $leaveDetails[] = 'Leave Type: ' . $leave['leave_type'];
            }
            
            // Date range
            $dateFrom = '';
            $dateTo = '';
            if (!empty($leave['dateFrom'])) {
                if (is_string($leave['dateFrom'])) {
                    $dateFrom = date('F d, Y', strtotime($leave['dateFrom']));
                } elseif (is_object($leave['dateFrom']) && method_exists($leave['dateFrom'], 'format')) {
                    $dateFrom = $leave['dateFrom']->format('F d, Y');
                }
            } elseif (!empty($leave['date_covered'])) {
                $parts = explode(' - ', $leave['date_covered']);
                $dateFrom = $parts[0] ?? '';
            }
            
            if (!empty($leave['dateTo'])) {
                if (is_string($leave['dateTo'])) {
                    $dateTo = date('F d, Y', strtotime($leave['dateTo']));
                } elseif (is_object($leave['dateTo']) && method_exists($leave['dateTo'], 'format')) {
                    $dateTo = $leave['dateTo']->format('F d, Y');
                }
            } elseif (!empty($leave['date_covered'])) {
                $parts = explode(' - ', $leave['date_covered']);
                $dateTo = $parts[1] ?? '';
            }
            
            if ($dateFrom || $dateTo) {
                $leaveDetails[] = 'Date Range: ' . $dateFrom . ' - ' . $dateTo;
            }
            
            if (!empty($leave['balance']) || $leave['balance'] === 0 || $leave['balance'] === '0') {
                $leaveDetails[] = 'Days: ' . number_format((float)$leave['balance'], 2);
            }
            
            if (!empty($leave['day_type'])) {
                $leaveDetails[] = 'Day Type: ' . $leave['day_type'];
            }
            
            if (!empty($leave['reason'])) {
                $leaveDetails[] = 'Reason: ' . $leave['reason'];
            }
            
            if (!empty($leave['remarks'])) {
                $leaveDetails[] = 'Remarks: ' . $leave['remarks'];
            }
            
            if (!empty($leave['status'])) {
                $status = ucfirst($leave['status']);
                $leaveDetails[] = 'Status: ' . $status;
            }
            
            // Approvers
            if (!empty($leave['approver_1'])) {
                $approver1Text = 'Approver 1: ' . $leave['approver_1'];
                if (!empty($leave['processed_date'])) {
                    $processedDate = is_string($leave['processed_date']) 
                        ? date('F d, Y', strtotime($leave['processed_date']))
                        : (is_object($leave['processed_date']) && method_exists($leave['processed_date'], 'format')
                            ? $leave['processed_date']->format('F d, Y')
                            : $leave['processed_date']);
                    $approver1Text .= ' (' . $processedDate . ')';
                }
                $leaveDetails[] = $approver1Text;
            }
            
            if (!empty($leave['approver_2'])) {
                $approver2Text = 'Approver 2: ' . $leave['approver_2'];
                if (!empty($leave['processed_date_2'])) {
                    $processedDate2 = is_string($leave['processed_date_2']) 
                        ? date('F d, Y', strtotime($leave['processed_date_2']))
                        : (is_object($leave['processed_date_2']) && method_exists($leave['processed_date_2'], 'format')
                            ? $leave['processed_date_2']->format('F d, Y')
                            : $leave['processed_date_2']);
                    $approver2Text .= ' (' . $processedDate2 . ')';
                }
                $leaveDetails[] = $approver2Text;
            }
            
            // Add leave details
            foreach ($leaveDetails as $detail) {
                $sheet->setCellValue('B' . $currentRow, $detail);
                $sheet->getStyle('B' . $currentRow)->getFont()->setSize(11);
                $currentRow++;
            }

            // Add spacing between applications (if multiple)
            if ($leaveIndex < count($employeeLeaves) - 1) {
                $currentRow += 1;
            }
        }

        // Add minimal spacing between employees
        $empIndex++;
        if ($empIndex < count($groupedByEmployee)) {
            $currentRow += 1;
        }
        }

        // Auto-size columns
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }

    /**
     * Create leave credit card monitoring report
     */
    private function createLeaveCreditCardMonitoringReport($sheet, $data)
    {
        $currentRow = 1;

        // Add header
        $this->addReportHeader($sheet, $currentRow, 'LEAVE CREDIT CARD MONITORING REPORT', $data);
        $currentRow += 4;

        $employees = $data['employees'] ?? [];
        
        if (empty($employees)) {
            $sheet->setCellValue('A' . $currentRow, 'No employee data available.');
            return;
        }

        // Add each employee with their leave credits
        foreach ($employees as $index => $employee) {
            // Employee number and name
            $sheet->setCellValue('A' . $currentRow, ($index + 1) . '. ' . ($employee['name'] ?? 'Unknown'));
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
            $currentRow++;

            // Employee details
            $details = [];
            if (!empty($employee['employee_no'])) {
                $details[] = 'Employee No: ' . $employee['employee_no'];
            }
            if (!empty($employee['position'])) {
                $details[] = 'Position: ' . $employee['position'];
            }
            if (!empty($employee['department'])) {
                $details[] = 'Department: ' . $employee['department'];
            }
            if (!empty($details)) {
                $sheet->setCellValue('A' . $currentRow, implode(' | ', $details));
                $sheet->getStyle('A' . $currentRow)->getFont()->setSize(10);
                $sheet->getStyle('A' . $currentRow)->getFont()->getColor()->setRGB('606266');
                $currentRow++;
            }

            // Leave credits
            if (!empty($employee['leave_credits']) && is_array($employee['leave_credits'])) {
                foreach ($employee['leave_credits'] as $credit) {
                    $creditName = $credit['leave_type_name'] ?? 'Unknown Leave Type';
                    $creditValue = number_format($credit['credits'] ?? 0, 2);
                    $sheet->setCellValue('B' . $currentRow, $creditName . ' - ' . $creditValue);
                    $sheet->getStyle('B' . $currentRow)->getFont()->setSize(11);
                    $currentRow++;
                }
            } else {
                $sheet->setCellValue('B' . $currentRow, 'No leave credits available');
                $sheet->getStyle('B' . $currentRow)->getFont()->setSize(11);
                $sheet->getStyle('B' . $currentRow)->getFont()->getColor()->setRGB('909399');
                $sheet->getStyle('B' . $currentRow)->getFont()->setItalic(true);
                $currentRow++;
            }

            // Add spacing between employees
            $currentRow += 1;
        }

        // Auto-size columns
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }

    /**
     * Create OB monitoring report
     */
    private function createOBMonitoringReport($sheet, $data)
    {
        $currentRow = 1;

        // Get tab display name
        $tabDisplayName = $data['tab_display_name'] ?? 'OB Monitoring';
        $reportTitle = 'OB MONITORING REPORT - ' . strtoupper($tabDisplayName);

        // Add header
        $this->addReportHeader($sheet, $currentRow, $reportTitle, $data);
        $currentRow += 1;

        $obRecords = $data['ob_records'] ?? [];
        
        if (empty($obRecords)) {
            $sheet->setCellValue('A' . $currentRow, 'No OB data available.');
            return;
        }

        // Group OB records by employee
        $groupedByEmployee = [];
        foreach ($obRecords as $ob) {
            $empKey = $ob['employee_id'] ?? ($ob['employee_no'] . '_' . ($ob['name'] ?? ($ob['first_name'] . '_' . $ob['last_name'] ?? '')));
            if (!isset($groupedByEmployee[$empKey])) {
                $groupedByEmployee[$empKey] = [
                    'employee' => $ob,
                    'obRecords' => []
                ];
            }
            $groupedByEmployee[$empKey]['obRecords'][] = $ob;
        }

        // Add each employee with all their OB applications
        $empIndex = 0;
        foreach ($groupedByEmployee as $group) {
            $employee = $group['employee'];
            $employeeObRecords = $group['obRecords'];
            
            // Employee number and name (shown once per employee)
            $employeeName = $employee['name'] ?? ($employee['first_name'] && $employee['last_name'] 
                ? trim($employee['first_name'] . ' ' . $employee['last_name']) 
                : 'Unknown');
            $sheet->setCellValue('A' . $currentRow, ($empIndex + 1) . '. ' . $employeeName);
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
            $currentRow++;

            // Employee details
            $details = [];
            if (!empty($employee['employee_no'])) {
                $details[] = 'Employee No: ' . $employee['employee_no'];
            }
            if (!empty($employee['position'])) {
                $details[] = 'Position: ' . $employee['position'];
            }
            if (!empty($employee['department'])) {
                $details[] = 'Department: ' . $employee['department'];
            }
            if (!empty($details)) {
                $sheet->setCellValue('A' . $currentRow, implode(' | ', $details));
                $sheet->getStyle('A' . $currentRow)->getFont()->setSize(10);
                $sheet->getStyle('A' . $currentRow)->getFont()->getColor()->setRGB('606266');
                $currentRow++;
            }

            // Display all OB applications for this employee
            foreach ($employeeObRecords as $obIndex => $ob) {
                // Application header (if multiple applications)
                if (count($employeeObRecords) > 1) {
                    $sheet->setCellValue('B' . $currentRow, 'Application ' . ($obIndex + 1) . ':');
                    $sheet->getStyle('B' . $currentRow)->getFont()->setBold(true)->setSize(11);
                    $sheet->getStyle('B' . $currentRow)->getFont()->getColor()->setRGB('606266');
                    $currentRow++;
                }

                // OB details
                $obDetails = [];
                
                // Date and Time Covered
                $dateFrom = $ob['date_time_from'] ?? $ob['date'] ?? null;
                $dateTo = $ob['date_time_to'] ?? $ob['date'] ?? null;
                if ($dateFrom && $dateTo) {
                    $dateFromFormatted = $this->formatDateForExcel($dateFrom);
                    $dateToFormatted = $this->formatDateForExcel($dateTo);
                    $timeFromFormatted = $this->formatTimeForExcel($dateFrom);
                    $timeToFormatted = $this->formatTimeForExcel($dateTo);
                    
                    // Check if same day
                    $fromDate = new \DateTime($dateFrom);
                    $toDate = new \DateTime($dateTo);
                    if ($fromDate->format('Y-m-d') === $toDate->format('Y-m-d')) {
                        $obDetails[] = 'Date and Time Covered: ' . $dateFromFormatted . ' • ' . $timeFromFormatted . ' - ' . $timeToFormatted;
                    } else {
                        $obDetails[] = 'Date and Time Covered: ' . $dateFromFormatted . ' ' . $timeFromFormatted . ' - ' . $dateToFormatted . ' ' . $timeToFormatted;
                    }
                }
                
                if (!empty($ob['purpose'])) {
                    $obDetails[] = 'Purpose: ' . $ob['purpose'];
                }
                
                if (!empty($ob['client'])) {
                    $obDetails[] = 'Client: ' . $ob['client'];
                }
                
                if (!empty($ob['created_at'])) {
                    $filedDate = $this->formatDateTimeForExcel($ob['created_at']);
                    $obDetails[] = 'Filed: ' . $filedDate;
                }
                
                if (!empty($ob['status'])) {
                    $status = ucfirst($ob['status']);
                    $obDetails[] = 'Status: ' . $status;
                }
                
                // Approvers
                if (!empty($ob['approver_1'])) {
                    $approver1Text = 'Approver 1: ' . $ob['approver_1'];
                    if (!empty($ob['processed_date'])) {
                        $processedDate = $this->formatDateTimeForExcel($ob['processed_date']);
                        $approver1Text .= ' (' . $processedDate . ')';
                    }
                    $obDetails[] = $approver1Text;
                }
                
                if (!empty($ob['approver_2'])) {
                    $approver2Text = 'Approver 2: ' . $ob['approver_2'];
                    if (!empty($ob['processed_date_2'])) {
                        $processedDate2 = $this->formatDateTimeForExcel($ob['processed_date_2']);
                        $approver2Text .= ' (' . $processedDate2 . ')';
                    }
                    $obDetails[] = $approver2Text;
                }
                
                if (!empty($ob['approver_3'])) {
                    $approver3Text = 'Approver 3: ' . $ob['approver_3'];
                    if (!empty($ob['processed_date_3'])) {
                        $processedDate3 = $this->formatDateTimeForExcel($ob['processed_date_3']);
                        $approver3Text .= ' (' . $processedDate3 . ')';
                    }
                    $obDetails[] = $approver3Text;
                }
                
                if (!empty($ob['approver_4'])) {
                    $obDetails[] = 'Approver 4: ' . $ob['approver_4'];
                }
                
                // Add OB details
                foreach ($obDetails as $detail) {
                    $sheet->setCellValue('B' . $currentRow, $detail);
                    $sheet->getStyle('B' . $currentRow)->getFont()->setSize(11);
                    $currentRow++;
                }

                // Add spacing between applications (if multiple)
                if ($obIndex < count($employeeObRecords) - 1) {
                    $currentRow += 1;
                }
            }

            // Add minimal spacing between employees
            $empIndex++;
            if ($empIndex < count($groupedByEmployee)) {
                $currentRow += 1;
            }
        }

        // Auto-size columns
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }

    /**
     * Create OT monitoring report
     */
    private function createOTMonitoringReport($sheet, $data)
    {
        $currentRow = 1;

        // Get tab display name
        $tabDisplayName = $data['tab_display_name'] ?? 'OT Monitoring';
        $reportTitle = 'OT MONITORING REPORT - ' . strtoupper($tabDisplayName);

        // Add header
        $this->addReportHeader($sheet, $currentRow, $reportTitle, $data);
        $currentRow += 1; // Reduced header spacing

        $otRecords = $data['ot_records'] ?? [];
        
        if (empty($otRecords)) {
            $sheet->setCellValue('A' . $currentRow, 'No OT data available.');
            return;
        }

        // Group OT records by employee
        $groupedByEmployee = [];
        foreach ($otRecords as $ot) {
            $empKey = $ot['employee_id'] ?? ($ot['employee_no'] . '_' . ($ot['name'] ?? (($ot['first_name'] ?? '') . '_' . ($ot['last_name'] ?? ''))));
            if (!isset($groupedByEmployee[$empKey])) {
                $groupedByEmployee[$empKey] = [
                    'employee' => $ot,
                    'otRecords' => []
                ];
            }
            $groupedByEmployee[$empKey]['otRecords'][] = $ot;
        }

        // Add each employee with all their OT applications
        $empIndex = 0;
        foreach ($groupedByEmployee as $group) {
            $employee = $group['employee'];
            $employeeOtRecords = $group['otRecords'];
            
            // Employee number and name (shown once per employee)
            $employeeName = $employee['name'] ?? (($employee['first_name'] ?? '') && ($employee['last_name'] ?? '') 
                ? trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')) 
                : 'Unknown');
            $sheet->setCellValue('A' . $currentRow, ($empIndex + 1) . '. ' . $employeeName);
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
            $currentRow++;

            // Employee details
            $details = [];
            if (!empty($employee['employee_no'])) {
                $details[] = 'Employee No: ' . $employee['employee_no'];
            }
            if (!empty($employee['position'])) {
                $details[] = 'Position: ' . $employee['position'];
            }
            if (!empty($employee['department'])) {
                $details[] = 'Department: ' . $employee['department'];
            }
            if (!empty($details)) {
                $sheet->setCellValue('A' . $currentRow, implode(' | ', $details));
                $sheet->getStyle('A' . $currentRow)->getFont()->setSize(10);
                $sheet->getStyle('A' . $currentRow)->getFont()->getColor()->setRGB('606266');
                $currentRow++;
            }

            // Display all OT applications for this employee
            foreach ($employeeOtRecords as $otIndex => $ot) {
                // Application header (if multiple applications)
                if (count($employeeOtRecords) > 1) {
                    $sheet->setCellValue('B' . $currentRow, 'Application ' . ($otIndex + 1) . ':');
                    $sheet->getStyle('B' . $currentRow)->getFont()->setBold(true)->setSize(11);
                    $sheet->getStyle('B' . $currentRow)->getFont()->getColor()->setRGB('606266');
                    $currentRow++;
                }

                // OT details
                $otDetails = [];
                
                // Date Filed
                if (!empty($ot['created_at'])) {
                    $filedDate = $this->formatDateTimeForExcel($ot['created_at']);
                    $otDetails[] = 'Date Filed: ' . $filedDate;
                }
                
                // Overtime Type
                $overtimeType = $ot['overtime_type_name'] ?? 'Regular Overtime';
                if (!empty($ot['overtime_type_id']) && $ot['overtime_type_id'] == 3) {
                    $serviceCredits = $ot['service_credits'] ?? false;
                    $isServiceCredits = $serviceCredits === true || $serviceCredits === 1 || $serviceCredits === '1' || $serviceCredits === 'true';
                    if ($isServiceCredits) {
                        $overtimeType = 'Regular Overtime (to COC)';
                    }
                }
                $otDetails[] = 'Overtime Type: ' . $overtimeType;
                
                // Overtime Date
                if (!empty($ot['date'])) {
                    $otDate = $this->formatDateForExcel($ot['date']);
                    $otDetails[] = 'Overtime Date: ' . $otDate;
                }
                
                // Time Range
                if (!empty($ot['date_time_from']) || !empty($ot['date_time_to'])) {
                    $timeFrom = $this->formatTimeForExcel($ot['date_time_from'] ?? null);
                    $timeTo = $this->formatTimeForExcel($ot['date_time_to'] ?? null);
                    $otDetails[] = 'Time Range: ' . $timeFrom . ' - ' . $timeTo;
                }
                
                // Total Hours
                $totalHours = number_format((float)($ot['total_hours'] ?? 0), 2);
                $otDetails[] = 'Total Hours: ' . $totalHours . ' hours';
                
                // Remarks
                if (!empty($ot['remarks'])) {
                    $otDetails[] = 'Remarks: ' . $ot['remarks'];
                }
                
                // Status
                if (!empty($ot['status'])) {
                    $status = ucfirst($ot['status']);
                    $otDetails[] = 'Status: ' . $status;
                }
                
                // Approvers
                if (!empty($ot['approver_1'])) {
                    $approver1Text = 'Approver 1: ' . $ot['approver_1'];
                    if (!empty($ot['processed_date'])) {
                        $processedDate = $this->formatDateForExcel($ot['processed_date']);
                        $approver1Text .= ' (' . $processedDate . ')';
                    }
                    $otDetails[] = $approver1Text;
                }
                
                if (!empty($ot['approver_2'])) {
                    $approver2Text = 'Approver 2: ' . $ot['approver_2'];
                    if (!empty($ot['processed_date_2'])) {
                        $processedDate2 = $this->formatDateForExcel($ot['processed_date_2']);
                        $approver2Text .= ' (' . $processedDate2 . ')';
                    }
                    $otDetails[] = $approver2Text;
                }
                
                if (!empty($ot['approver_3'])) {
                    $approver3Text = 'Approver 3: ' . $ot['approver_3'];
                    if (!empty($ot['processed_date_3'])) {
                        $processedDate3 = $this->formatDateForExcel($ot['processed_date_3']);
                        $approver3Text .= ' (' . $processedDate3 . ')';
                    }
                    $otDetails[] = $approver3Text;
                }
                
                if (!empty($ot['approver_4'])) {
                    $otDetails[] = 'Approver 4: ' . $ot['approver_4'];
                }
                
                // Add OT details
                foreach ($otDetails as $detail) {
                    $sheet->setCellValue('B' . $currentRow, $detail);
                    $sheet->getStyle('B' . $currentRow)->getFont()->setSize(11);
                    // Reduce row height for compact spacing
                    $sheet->getRowDimension($currentRow)->setRowHeight(15);
                    $currentRow++;
                }

                // Remove spacing between applications (if multiple) - no extra row
            }

            // Remove spacing between employees - no extra row
            $empIndex++;
        }

        // Auto-size columns
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }

    /**
     * Create COC monitoring report
     */
    private function createCOCMonitoringReport($sheet, $data)
    {
        $currentRow = 1;

        // Get month name
        $monthName = $data['month_name'] ?? '';
        $reportTitle = 'COC MONITORING REPORT';
        if (!empty($monthName)) {
            $reportTitle .= ' - ' . strtoupper($monthName);
        }

        // Add header
        $this->addReportHeader($sheet, $currentRow, $reportTitle, $data);
        $currentRow += 1; // Reduced header spacing

        $cocRecords = $data['coc_records'] ?? [];
        
        if (empty($cocRecords)) {
            $sheet->setCellValue('A' . $currentRow, 'No COC data available.');
            return;
        }

        // Group COC records by employee (already filtered by month on frontend)
        $groupedByEmployee = [];
        foreach ($cocRecords as $coc) {
            $empKey = $coc['employee_id'] ?? ($coc['employee_no'] . '_' . ($coc['name'] ?? ''));
            if (!isset($groupedByEmployee[$empKey])) {
                $groupedByEmployee[$empKey] = $coc;
            }
        }

        // Add each employee with all their COC details
        $empIndex = 0;
        foreach ($groupedByEmployee as $employee) {
            // Employee number and name (shown once per employee)
            $sheet->setCellValue('A' . $currentRow, ($empIndex + 1) . '. ' . ($employee['name'] ?? 'Unknown'));
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
            $currentRow++;

            // Employee details
            $details = [];
            if (!empty($employee['employee_no'])) {
                $details[] = 'Employee No: ' . $employee['employee_no'];
            }
            if (!empty($employee['position'])) {
                $details[] = 'Position: ' . $employee['position'];
            }
            if (!empty($employee['department'])) {
                $details[] = 'Department: ' . $employee['department'];
            }
            if (!empty($details)) {
                $sheet->setCellValue('A' . $currentRow, implode(' | ', $details));
                $sheet->getStyle('A' . $currentRow)->getFont()->setSize(10);
                $sheet->getStyle('A' . $currentRow)->getFont()->getColor()->setRGB('606266');
                $currentRow++;
            }

            // COC details
            $cocDetails = [];
            
            // Month
            if (!empty($employee['months'])) {
                $cocDetails[] = 'Month: ' . $employee['months'];
            }
            
            // Year
            if (!empty($employee['year'])) {
                $cocDetails[] = 'Year: ' . $employee['year'];
            }
            
            // Accrued Hours
            $accrued = number_format((float)($employee['carryover'] ?? 0), 2);
            $cocDetails[] = 'Accrued (hrs): ' . $accrued;
            
            // Cumulative Hours
            $cumulative = number_format((float)($employee['total_hours'] ?? 0), 2);
            $cocDetails[] = 'Cumulative (hrs): ' . $cumulative;
            
            // Leave Used (days)
            $leaveUsedDays = $employee['total_leave_days'] ?? (isset($employee['total_leave_hours']) ? (float)$employee['total_leave_hours'] / 8 : 0);
            $leaveUsed = number_format((float)$leaveUsedDays, 3);
            $cocDetails[] = 'Leave Used (days): ' . $leaveUsed;
            
            // Remaining Balance
            $remaining = number_format((float)($employee['remaining_balance'] ?? 0), 2);
            $cocDetails[] = 'Remaining (hrs): ' . $remaining;
            
            // Converted Days
            $converted = number_format((float)($employee['converted_days'] ?? 0), 4);
            $cocDetails[] = 'Converted (days): ' . $converted;
            
            // Add COC details
            foreach ($cocDetails as $detail) {
                $sheet->setCellValue('B' . $currentRow, $detail);
                $sheet->getStyle('B' . $currentRow)->getFont()->setSize(11);
                // Reduce row height for compact spacing
                $sheet->getRowDimension($currentRow)->setRowHeight(15);
                $currentRow++;
            }

            // Remove spacing between employees - no extra row
            $empIndex++;
        }

        // Auto-size columns
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }

    /**
     * Create work suspension report
     */
    private function createWorkSuspensionReport($sheet, $data)
    {
        $currentRow = 1;

        // Add header
        $this->addReportHeader($sheet, $currentRow, 'WORK SUSPENSION REPORT', $data);
        $currentRow += 2;

        $workSuspensions = $data['work_suspensions'] ?? [];
        
        if (empty($workSuspensions)) {
            $sheet->setCellValue('A' . $currentRow, 'No work suspension data available.');
            return;
        }

        // Table headers - only Reason and Date Range
        $sheet->setCellValue('A' . $currentRow, 'Reason');
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $currentRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D0D0D0');
        $sheet->getStyle('A' . $currentRow)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->setCellValue('B' . $currentRow, 'Date Range');
        $sheet->getStyle('B' . $currentRow)->getFont()->setBold(true);
        $sheet->getStyle('B' . $currentRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D0D0D0');
        $sheet->getStyle('B' . $currentRow)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        $currentRow++;

        // Add data rows
        foreach ($workSuspensions as $suspension) {
            // Reason
            $reason = $suspension['reason'] ?? 'N/A';
            $sheet->setCellValue('A' . $currentRow, $reason);
            $sheet->getStyle('A' . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);

            // Date Range - if 1 day, show only date from
            $dateFrom = $suspension['date_from'] ?? $suspension['start_date'] ?? null;
            $dateTo = $suspension['date_to'] ?? $suspension['end_date'] ?? null;
            
            $dateRange = $this->formatWorkSuspensionDateRange($dateFrom, $dateTo);
            $sheet->setCellValue('B' . $currentRow, $dateRange);
            $sheet->getStyle('B' . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);

            $currentRow++;
        }

        // Auto-size columns
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }

    /**
     * Format work suspension date range (if 1 day, show only date from)
     */
    private function formatWorkSuspensionDateRange($dateFrom, $dateTo)
    {
        if (!$dateFrom) return 'N/A';

        try {
            $from = new \DateTime($dateFrom);
            $fromFormatted = $this->formatDateForExcel($dateFrom);

            // If no date_to or same day, show only date from
            if (!$dateTo) {
                return $fromFormatted;
            }

            $to = new \DateTime($dateTo);
            $timeDiff = $to->getTimestamp() - $from->getTimestamp();
            $diffDays = floor($timeDiff / (60 * 60 * 24));

            // If same day or 1 day range, show only date from
            if ($diffDays === 0) {
                return $fromFormatted;
            }

            // Multiple days range
            $toFormatted = $this->formatDateForExcel($dateTo);
            return $fromFormatted . ' - ' . $toFormatted;
        } catch (\Exception $e) {
            return $dateFrom . ($dateTo ? ' - ' . $dateTo : '');
        }
    }

    /**
     * Create daily attendance summary report
     */
    private function createDailyAttendanceSummaryReport($sheet, $data)
    {
        $currentRow = 1;

        // Get date display
        $dateDisplay = $data['date_display'] ?? '';
        $reportTitle = 'DAILY ATTENDANCE SUMMARY';
        $reportSubtitle = '';
        if (!empty($dateDisplay)) {
            $reportSubtitle = $dateDisplay;
        } elseif (!empty($data['date'])) {
            // Fallback: format date if provided
            try {
                $date = new \DateTime($data['date']);
                $monthNames = [
                    'January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'
                ];
                $reportSubtitle = $monthNames[$date->format('n') - 1] . ' ' . $date->format('j') . ', ' . $date->format('Y');
            } catch (\Exception $e) {
                $reportSubtitle = $data['date'];
            }
        }

        // Add header with company info
        $this->addReportHeader($sheet, $currentRow, $reportTitle, $data);
        $currentRow += 3; // After header (3 rows: company name, PTTC, title)
        
        // Add date subtitle if available
        if (!empty($reportSubtitle)) {
            $sheet->setCellValue('A' . $currentRow, $reportSubtitle);
            $sheet->mergeCells('A' . $currentRow . ':D' . $currentRow);
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $currentRow += 1; // Reduced spacing
        }
        $currentRow += 1; // Space before table

        $attendanceRecords = $data['attendance_records'] ?? [];
        
        if (empty($attendanceRecords)) {
            $sheet->setCellValue('A' . $currentRow, 'No attendance data available.');
            return;
        }

        // Table headers
        $headers = ['Access No', 'Employee Name', 'Position', 'Department', 'AM In', 'AM Out', 'Break In', 'Break Out', 'PM In', 'PM Out'];
        $col = 'A';
        
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $currentRow, $header);
            $sheet->getStyle($col . $currentRow)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle($col . $currentRow)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D0D0D0');
            $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $sheet->getRowDimension($currentRow)->setRowHeight(18); // Reduced row height
            $col++;
        }
        $currentRow++;

        // Add data rows
        foreach ($attendanceRecords as $index => $record) {
            $col = 'A';
            
            // Access No
            $sheet->setCellValue($col . $currentRow, $record['usercode'] ?? '-');
            $sheet->getStyle($col . $currentRow)->getFont()->setSize(10);
            $sheet->getStyle($col . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $col++;
            
            // Employee Name
            $sheet->setCellValue($col . $currentRow, $record['employee_name'] ?? '-');
            $sheet->getStyle($col . $currentRow)->getFont()->setSize(10);
            $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $col++;
            
            // Position
            $sheet->setCellValue($col . $currentRow, $record['position'] ?? '-');
            $sheet->getStyle($col . $currentRow)->getFont()->setSize(10);
            $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $col++;
            
            // Department
            $sheet->setCellValue($col . $currentRow, $record['department'] ?? '-');
            $sheet->getStyle($col . $currentRow)->getFont()->setSize(10);
            $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $col++;
            
            // AM In
            $amIn = $this->formatTimeForAttendance($record['am_in'] ?? null);
            $sheet->setCellValue($col . $currentRow, $amIn);
            $sheet->getStyle($col . $currentRow)->getFont()->setSize(10);
            $sheet->getStyle($col . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $col++;
            
            // AM Out
            $amOut = $this->formatTimeForAttendance($record['am_out'] ?? null);
            $sheet->setCellValue($col . $currentRow, $amOut);
            $sheet->getStyle($col . $currentRow)->getFont()->setSize(10);
            $sheet->getStyle($col . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $col++;
            
            // Break In
            $breakIn = $this->formatTimeForAttendance($record['break_in'] ?? null);
            $sheet->setCellValue($col . $currentRow, $breakIn);
            $sheet->getStyle($col . $currentRow)->getFont()->setSize(10);
            $sheet->getStyle($col . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $col++;
            
            // Break Out
            $breakOut = $this->formatTimeForAttendance($record['break_out'] ?? null);
            $sheet->setCellValue($col . $currentRow, $breakOut);
            $sheet->getStyle($col . $currentRow)->getFont()->setSize(10);
            $sheet->getStyle($col . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $col++;
            
            // PM In
            $pmIn = $this->formatTimeForAttendance($record['pm_in'] ?? null);
            $sheet->setCellValue($col . $currentRow, $pmIn);
            $sheet->getStyle($col . $currentRow)->getFont()->setSize(10);
            $sheet->getStyle($col . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $col++;
            
            // PM Out
            $pmOut = $this->formatTimeForAttendance($record['pm_out'] ?? null);
            $sheet->setCellValue($col . $currentRow, $pmOut);
            $sheet->getStyle($col . $currentRow)->getFont()->setSize(10);
            $sheet->getStyle($col . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            
            // Reduced row height for compact spacing
            $sheet->getRowDimension($currentRow)->setRowHeight(16);
            $currentRow++;
        }

        // Auto-size columns
        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Format time for attendance (helper method)
     */
    private function formatTimeForAttendance($value)
    {
        if (!$value || $value === '-' || $value === null || $value === '') {
            return '-';
        }
        
        try {
            // Handle various time formats
            if (is_string($value)) {
                // Try to parse time string
                $time = \DateTime::createFromFormat('H:i:s', $value);
                if (!$time) {
                    $time = \DateTime::createFromFormat('H:i', $value);
                }
                if (!$time && strtotime($value) !== false) {
                    $time = new \DateTime($value);
                }
                
                if ($time) {
                    $hours = (int)$time->format('H');
                    $minutes = $time->format('i');
                    $isPM = $hours >= 12;
                    $meridiem = $isPM ? 'PM' : 'AM';
                    $hours = $hours % 12;
                    if ($hours === 0) $hours = 12;
                    return sprintf('%d:%s %s', $hours, $minutes, $meridiem);
                }
            }
            
            return '-';
        } catch (\Exception $e) {
            return '-';
        }
    }

    /**
     * Format date for Excel (helper method)
     */
    private function formatDateForExcel($value)
    {
        if (!$value) return 'N/A';
        try {
            $date = new \DateTime($value);
            $monthNames = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            return $monthNames[$date->format('n') - 1] . ' ' . $date->format('j') . ', ' . $date->format('Y');
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Format time for Excel (helper method)
     */
    private function formatTimeForExcel($value)
    {
        if (!$value) return 'N/A';
        try {
            $date = new \DateTime($value);
            $hours = (int)$date->format('H');
            $minutes = $date->format('i');
            $isPM = $hours >= 12;
            $meridiem = $isPM ? 'PM' : 'AM';
            $hours = $hours % 12;
            if ($hours === 0) $hours = 12;
            return sprintf('%d:%s %s', $hours, $minutes, $meridiem);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Format date and time for Excel (helper method)
     */
    private function formatDateTimeForExcel($value)
    {
        if (!$value) return 'N/A';
        try {
            $date = new \DateTime($value);
            $monthNames = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            $month = $monthNames[$date->format('n') - 1];
            $day = $date->format('j');
            $year = $date->format('Y');
            $hours = (int)$date->format('H');
            $minutes = $date->format('i');
            $isPM = $hours >= 12;
            $meridiem = $isPM ? 'PM' : 'AM';
            $hours = $hours % 12;
            if ($hours === 0) $hours = 12;
            return sprintf('%s %s, %s %d:%s %s', $month, $day, $year, $hours, $minutes, $meridiem);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Create tardiness report (Late, Undertime, Absences, or Combined)
     */
    private function createTardinessReport($sheet, $data, $reportType = 'late')
    {
        $currentRow = 1;

        // Get data
        $header = $data['header'] ?? [];
        $rows = $data['rows'] ?? [];
        $dateFrom = $data['date_from'] ?? null;
        $dateTo = $data['date_to'] ?? null;

        // Determine report title based on type
        $reportTitle = 'Late Report';
        if ($reportType === 'undertime') {
            $reportTitle = 'Undertime Report';
        } elseif ($reportType === 'absences') {
            $reportTitle = 'Absences Report';
        } elseif ($reportType === 'combined') {
            $reportTitle = 'Late, Undertime and Absences Report';
        }

        $employeeName = isset($header['name']) ? strtoupper($header['name']) : 'N/A';

        // 1. Report Title
        $sheet->setCellValue('A' . $currentRow, $reportTitle);
        $sheet->mergeCells('A' . $currentRow . ':H' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $currentRow += 2;

        // Employee Name
        $sheet->setCellValue('A' . $currentRow, $employeeName);
        $sheet->mergeCells('A' . $currentRow . ':H' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $currentRow++;

        // Date Range
        if ($dateFrom || $dateTo) {
            $dateRangeText = 'For the period: ';
            if ($dateFrom && $dateTo) {
                try {
                    $fromDate = new \DateTime($dateFrom);
                    $toDate = new \DateTime($dateTo);
                    $dateRangeText .= $fromDate->format('F j') . ' - ' . $toDate->format('F j, Y');
                } catch (\Exception $e) {
                    $dateRangeText .= $dateFrom . ' - ' . $dateTo;
                }
            } elseif ($dateFrom) {
                try {
                    $fromDate = new \DateTime($dateFrom);
                    $dateRangeText .= 'From ' . $fromDate->format('F j, Y');
                } catch (\Exception $e) {
                    $dateRangeText .= 'From ' . $dateFrom;
                }
            } elseif ($dateTo) {
                try {
                    $toDate = new \DateTime($dateTo);
                    $dateRangeText .= 'To ' . $toDate->format('F j, Y');
                } catch (\Exception $e) {
                    $dateRangeText .= 'To ' . $dateTo;
                }
            }
            $sheet->setCellValue('A' . $currentRow, $dateRangeText);
            $sheet->mergeCells('A' . $currentRow . ':H' . $currentRow);
            $sheet->getStyle('A' . $currentRow)->getFont()->setSize(10);
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $currentRow++;
        }
        $currentRow += 1;

        // Employee Details
        if (!empty($header)) {
            $sheet->setCellValue('A' . $currentRow, 'Employee No: ' . ($header['employee_no'] ?? 'N/A'));
            $sheet->getStyle('A' . $currentRow)->getFont()->setSize(10);
            $currentRow++;

            if (!empty($header['position'])) {
                $sheet->setCellValue('A' . $currentRow, 'Position: ' . $header['position']);
                $sheet->getStyle('A' . $currentRow)->getFont()->setSize(10);
                $currentRow++;
            }

            if (!empty($header['department']) || !empty($header['department_name'])) {
                $dept = $header['department'] ?? $header['department_name'] ?? 'N/A';
                $sheet->setCellValue('A' . $currentRow, 'Department: ' . $dept);
                $sheet->getStyle('A' . $currentRow)->getFont()->setSize(10);
                $currentRow++;
            }
            $currentRow += 1;
        }

        // Build headers based on report type (Absences Report = Date and Absent only)
        $isAbsencesOnly = ($reportType === 'absences');
        $headers = $isAbsencesOnly ? ['Date', 'Absent'] : ['Date', 'Time-In and Out', 'Work Hours'];
        if ($reportType === 'late' || $reportType === 'combined') {
            $headers[] = 'Late';
        }
        if ($reportType === 'undertime' || $reportType === 'combined') {
            $headers[] = 'Undertime';
        }
        if ($reportType === 'absences' || $reportType === 'combined') {
            $headers[] = 'Absent';
        }
        if (!$isAbsencesOnly) {
            $headers[] = 'Remarks';
        }

        // Table Headers
        $headerRow = $currentRow;
        $col = 'A';
        foreach ($headers as $headerText) {
            $sheet->setCellValue($col . $headerRow, $headerText);
            $sheet->getStyle($col . $headerRow)->getFont()->setBold(true)->setSize(9);
            $sheet->getStyle($col . $headerRow)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('F0F0F0');
            $sheet->getStyle($col . $headerRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle($col . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col++;
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(15); // Date
        if (!$isAbsencesOnly) {
            $sheet->getColumnDimension('B')->setWidth(20); // Time-In and Out
            $sheet->getColumnDimension('C')->setWidth(15); // Work Hours
        }
        $colIndex = $isAbsencesOnly ? 1 : 3;
        if ($reportType === 'late' || $reportType === 'combined') {
            $sheet->getColumnDimension(chr(ord('A') + $colIndex))->setWidth(15); // Late
            $colIndex++;
        }
        if ($reportType === 'undertime' || $reportType === 'combined') {
            $sheet->getColumnDimension(chr(ord('A') + $colIndex))->setWidth(15); // Undertime
            $colIndex++;
        }
        if ($reportType === 'absences' || $reportType === 'combined') {
            $sheet->getColumnDimension(chr(ord('A') + $colIndex))->setWidth(12); // Absent
            $colIndex++;
        }
        if (!$isAbsencesOnly) {
            $sheet->getColumnDimension(chr(ord('A') + $colIndex))->setWidth(30); // Remarks
        }

        $currentRow++;

        // Add data rows
        foreach ($rows as $row) {
            // Skip placeholder rows for specific report types
            if (isset($row['isPlaceholder']) && $row['isPlaceholder'] && $reportType !== 'combined') {
                continue;
            }

            $col = 'A';

            // Date
            $dateText = '-';
            if (!empty($row['date'])) {
                try {
                    $date = new \DateTime($row['date']);
                    $dateText = $date->format('M j, Y');
                } catch (\Exception $e) {
                    $dateText = $row['date'];
                }
            }
            $sheet->setCellValue($col . $currentRow, $dateText);
            $sheet->getStyle($col . $currentRow)->getFont()->setSize(9);
            $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $col++;

            if (!$isAbsencesOnly) {
                // Time-In and Out
                $timeInOut = '-';
                if (!isset($row['isPlaceholder']) || !$row['isPlaceholder']) {
                    if (empty($row['remarks']) || stripos($row['remarks'], 'rest day') === false) {
                        $amIn = $this->formatTimeForExcel($row['am_in'] ?? null);
                        $pmOut = $this->formatTimeForExcel($row['pm_out'] ?? null);
                        if ($amIn && $pmOut) {
                            $timeInOut = $amIn . ' - ' . $pmOut;
                        } elseif ($amIn) {
                            $timeInOut = $amIn . ' - --';
                        } elseif ($pmOut) {
                            $timeInOut = '-- - ' . $pmOut;
                        }
                    }
                }
                $sheet->setCellValue($col . $currentRow, $timeInOut);
                $sheet->getStyle($col . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle($col . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $col++;

                // Work Hours
                $workHoursText = '-';
                if (!isset($row['isPlaceholder']) || !$row['isPlaceholder']) {
                    if (empty($row['remarks']) || stripos($row['remarks'], 'rest day') === false) {
                        if (!empty($row['work_hours']) && $row['work_hours'] > 0) {
                            $totalHours = floatval($row['work_hours']);
                            $hours = floor($totalHours);
                            $minutes = round(($totalHours - $hours) * 60);
                            if ($hours > 0 && $minutes > 0) {
                                $workHoursText = $hours . ' hrs ' . $minutes . ' mins';
                            } elseif ($hours > 0) {
                                $workHoursText = $hours . ' hrs';
                            } elseif ($minutes > 0) {
                                $workHoursText = $minutes . ' mins';
                            }
                        }
                    }
                }
                $sheet->setCellValue($col . $currentRow, $workHoursText);
                $sheet->getStyle($col . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle($col . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $col++;
            }

            // Late (if applicable)
            if ($reportType === 'late' || $reportType === 'combined') {
                $lateText = '-';
                if (!isset($row['isPlaceholder']) || !$row['isPlaceholder']) {
                    $absentStatus = $this->getAttendanceStatus($row);
                    if ($absentStatus !== 'absent' && (empty($row['remarks']) || stripos($row['remarks'], 'rest day') === false)) {
                        $late = floatval($row['late'] ?? 0);
                        if ($late > 0) {
                            $lateHours = floor($late);
                            $lateMinutes = round(($late - $lateHours) * 60);
                            if ($lateHours > 0 && $lateMinutes > 0) {
                                $lateText = $lateHours . ' hrs ' . $lateMinutes . ' mins';
                            } elseif ($lateHours > 0) {
                                $lateText = $lateHours . ' hrs';
                            } else {
                                $lateText = $lateMinutes . ' mins';
                            }
                        } else {
                            $lateText = 'On Time';
                        }
                    }
                }
                $sheet->setCellValue($col . $currentRow, $lateText);
                $sheet->getStyle($col . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle($col . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $col++;
            }

            // Undertime (if applicable)
            if ($reportType === 'undertime' || $reportType === 'combined') {
                $undertimeText = '-';
                if (!isset($row['isPlaceholder']) || !$row['isPlaceholder']) {
                    $absentStatus = $this->getAttendanceStatus($row);
                    if ($absentStatus !== 'absent' && (empty($row['remarks']) || stripos($row['remarks'], 'rest day') === false)) {
                        $undertime = floatval($row['undertime'] ?? 0);
                        if ($undertime > 0) {
                            $undertimeHours = floor($undertime);
                            $undertimeMinutes = round(($undertime - $undertimeHours) * 60);
                            if ($undertimeHours > 0 && $undertimeMinutes > 0) {
                                $undertimeText = $undertimeHours . ' hrs ' . $undertimeMinutes . ' mins';
                            } elseif ($undertimeHours > 0) {
                                $undertimeText = $undertimeHours . ' hrs';
                            } else {
                                $undertimeText = $undertimeMinutes . ' mins';
                            }
                        } else {
                            $undertimeText = 'Complete Hours';
                        }
                    }
                }
                $sheet->setCellValue($col . $currentRow, $undertimeText);
                $sheet->getStyle($col . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle($col . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $col++;
            }

            // Absent (if applicable): "Yes" in report (not checkmark so PDF doesn't show "?")
            if ($reportType === 'absences' || $reportType === 'combined') {
                $absentText = '-';
                if (!isset($row['isPlaceholder']) || !$row['isPlaceholder']) {
                    $absentStatus = $this->getAttendanceStatus($row);
                    if ($absentStatus === 'absent') {
                        $absentText = 'Yes';
                    }
                }
                $sheet->setCellValue($col . $currentRow, $absentText);
                $sheet->getStyle($col . $currentRow)->getFont()->setSize(9);
                if ($absentText === 'Yes') {
                    $sheet->getStyle($col . $currentRow)->getFont()->setBold(true);
                }
                $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle($col . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $col++;
            }

            if (!$isAbsencesOnly) {
                // Remarks
                $remarks = isset($row['remarks']) ? $row['remarks'] : '-';
                if (isset($row['isPlaceholder']) && $row['isPlaceholder']) {
                    $remarks = $row['remarks'] ?? 'This date does not have a record';
                }
                $sheet->setCellValue($col . $currentRow, $remarks);
                $sheet->getStyle($col . $currentRow)->getFont()->setSize(9);
                $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle($col . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $col++;
            }

            $currentRow++;
        }

        // Signature Section
        $currentRow += 2;
        $sheet->setCellValue('G' . $currentRow, '');
        $sheet->mergeCells('G' . $currentRow . ':H' . $currentRow);
        $sheet->getStyle('G' . $currentRow)->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_THIN);
        $currentRow++;
        $sheet->setCellValue('G' . $currentRow, 'Authorized Signatory');
        $sheet->mergeCells('G' . $currentRow . ':H' . $currentRow);
        $sheet->getStyle('G' . $currentRow)->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('G' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    /**
     * Get attendance status from row data
     */
    private function getAttendanceStatus($row)
    {
        if (isset($row['isPlaceholder']) && $row['isPlaceholder']) {
            return 'no-record';
        }

        // Check for rest days
        if (!empty($row['remarks']) && stripos($row['remarks'], 'rest day') !== false) {
            return 'rest-day';
        }

        // Check if there's any time record or work hours
        $hasTimeRecords = !empty($row['am_in']) || !empty($row['am_out']) || !empty($row['break_in']) || 
                         !empty($row['break_out']) || !empty($row['pm_in']) || !empty($row['pm_out']);
        $hasWorkHours = !empty($row['work_hours']) && $row['work_hours'] > 0 && $row['work_hours'] !== '0.00';

        if ($hasTimeRecords || $hasWorkHours) {
            return 'present';
        }

        // Check absent field
        $absentValue = floatval($row['absent'] ?? 0);
        if ($absentValue > 0 || $absentValue === 1 || $absentValue === 1.00) {
            return 'absent';
        }

        return 'no-record';
    }
}
