<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Font;
use App\Traits\ApiResponse;

class ReportDocxController extends Controller
{
    use ApiResponse;

    /**
     * Generate DOCX from structured data and stream as download.
     */
    public function generate(Request $request)
    {
        try {
            $validated = $request->validate([
                'report_type' => 'required|string',
                'data' => 'required|array',
                'filename' => 'nullable|string',
                'paper_size' => 'nullable|string|in:A4,Letter',
                'orientation' => 'nullable|string|in:portrait,landscape',
            ]);

            $reportType = $validated['report_type'];
            $data = $validated['data'];
            $filename = $validated['filename'] ?? ($reportType . '_' . date('Ymd_His'));
            $paperSize = $validated['paper_size'] ?? 'A4';
            $orientation = $validated['orientation'] ?? 'portrait';

            // Create Word document based on report type
            $phpWord = $this->createWordDocument($reportType, $data, $paperSize, $orientation);

            // Generate temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'docx_report_');
            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($tempFile);

            // Return file as download
            $wordContent = file_get_contents($tempFile);
            unlink($tempFile); // Clean up

            // Get origin from request for CORS (required when credentials are included)
            $origin = $request->headers->get('Origin') ?? '*';
            
            return response($wordContent)
                ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '.docx"')
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->header('Access-Control-Allow-Credentials', 'true');

        } catch (\Exception $e) {
            Log::error('DOCX generation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->serverErrorResponse('Failed to generate Word document: ' . $e->getMessage());
        }
    }

    /**
     * Create Word document based on report type
     */
    private function createWordDocument($reportType, $data, $paperSize, $orientation)
    {
        $phpWord = new PhpWord();

        // Set document properties
        $phpWord->getDocInfo()
            ->setCreator(CompanyHelper::getName() . ' Timekeeping System')
            ->setCompany(CompanyHelper::getName())
            ->setTitle(ucfirst(str_replace('_', ' ', $reportType)) . ' Report')
            ->setDescription('Generated report from ' . CompanyHelper::getName() . ' Timekeeping System')
            ->setSubject('Timekeeping Report');

        // Set page size to A4 with proper margins (0.25in top/bottom, 0.5in left/right)
        // Always use portrait orientation as default
        $pageWidth = Converter::inchToTwip(8.27); // A4 width
        $pageHeight = Converter::inchToTwip(11.69); // A4 height

        // Add section with proper margins matching PDF format
        $section = $phpWord->addSection([
            'marginTop' => Converter::inchToTwip(0.25),    // 0.25 inch top
            'marginBottom' => Converter::inchToTwip(0.25), // 0.25 inch bottom
            'marginLeft' => Converter::inchToTwip(0.5),    // 0.5 inch left
            'marginRight' => Converter::inchToTwip(0.5),   // 0.5 inch right
            'pageSizeW' => $pageWidth,
            'pageSizeH' => $pageHeight,
            'headerHeight' => Converter::inchToTwip(0.5),
            'footerHeight' => Converter::inchToTwip(0.5),
        ]);

        // Define styles
        $this->defineDocumentStyles($phpWord);

        // Add company header in main content (not document header)
        $this->addCompanyHeader($section);

        // Generate content based on report type
        switch ($reportType) {
            case 'attendance_report':
                $this->createAttendanceReport($section, $data);
                break;
            case 'shift_schedule_report':
                $this->createShiftScheduleReport($section, $data);
                break;
            case 'assigned_employees_report':
                $this->createAssignedEmployeesReport($section, $data);
                break;
            case 'shifting_schedule_details_report':
                $this->createShiftingScheduleDetailsReport($section, $data);
                break;
            case 'fix_schedule_detail':
                $this->createFixScheduleDetailReport($section, $data);
                break;
            case 'fix_schedule_assigned':
                $this->createFixScheduleAssignedReport($section, $data);
                break;
            case 'fix_schedule_list':
                $this->createFixScheduleListReport($section, $data);
                break;
            case 'shift_schedule_list':
                $this->createShiftScheduleListReport($section, $data);
                break;
            case 'shift_schedule_viewer':
                $this->createShiftScheduleViewerReport($section, $data);
                break;
            case 'shift_schedule_employees':
                $this->createShiftScheduleEmployeesReport($section, $data);
                break;
            case 'leave_credits_list':
                $this->createLeaveCreditsListReport($section, $data);
                break;
            case 'leave_monitoring':
                $this->createLeaveMonitoringReport($section, $data);
                break;
            case 'leave_credit_card_monitoring':
                $this->createLeaveCreditCardMonitoringReport($section, $data);
                break;
            case 'ob_monitoring':
                $this->createOBMonitoringReport($section, $data);
                break;
            case 'ot_monitoring':
                $this->createOTMonitoringReport($section, $data);
                break;
            case 'coc_monitoring':
                $this->createCOCMonitoringReport($section, $data);
                break;
            case 'work_suspension':
                $this->createWorkSuspensionReport($section, $data);
                break;
            case 'daily_attendance_summary':
                $this->createDailyAttendanceSummaryReport($section, $data);
                break;
            case 'late_report':
            case 'tardiness_report':
                $this->createTardinessReport($section, $data, 'late');
                break;
            case 'undertime_report':
                $this->createTardinessReport($section, $data, 'undertime');
                break;
            case 'absences_report':
                $this->createTardinessReport($section, $data, 'absences');
                break;
            case 'combined_tardiness_report':
                $this->createTardinessReport($section, $data, 'combined');
                break;
            default:
                $this->createGenericReport($section, $data, $reportType);
                break;
        }

        // Add footer to document footer section
        $this->addDocumentFooter($section);

        return $phpWord;
    }

    /**
     * Define document styles
     */
    private function defineDocumentStyles($phpWord)
    {
        // Title style
        $phpWord->addTitleStyle(1, [
            'bold' => true,
            'size' => 16,
            'name' => 'Arial',
            'color' => '000000'
        ], [
            'alignment' => Jc::CENTER,
            'spaceAfter' => Converter::pointToTwip(12)
        ]);

        // Subtitle style
        $phpWord->addTitleStyle(2, [
            'bold' => true,
            'size' => 14,
            'name' => 'Arial',
            'color' => '000000'
        ], [
            'alignment' => Jc::CENTER,
            'spaceAfter' => Converter::pointToTwip(10)
        ]);

        // Header style
        $phpWord->addTitleStyle(3, [
            'bold' => true,
            'size' => 12,
            'name' => 'Arial',
            'color' => '000000'
        ], [
            'spaceAfter' => Converter::pointToTwip(6)
        ]);

        // Normal text style
        $phpWord->addParagraphStyle('normal', [
            'alignment' => Jc::LEFT,
            'spaceAfter' => Converter::pointToTwip(6)
        ]);

        // Table header style
        $phpWord->addTableStyle('reportTable', [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => Converter::pointToTwip(4),
        ], [
            'borderSize' => 6,
            'borderColor' => '000000',
        ]);
    }

    /**
     * Create attendance report
     */
    private function createAttendanceReport($section, $data)
    {
        // Report title
        $section->addTitle('ATTENDANCE REPORT', 1);
        
        if (isset($data['date_range'])) {
            $section->addText('Period: ' . $data['date_range'], ['size' => 12, 'name' => 'Arial']);
        }
        
        $section->addTextBreak(1);

        // Report details
        if (isset($data['summary'])) {
            $this->addSummarySection($section, $data['summary']);
        }

        // Employee data table
        if (isset($data['employees']) && is_array($data['employees'])) {
            $this->addEmployeeTable($section, $data['employees'], 'attendance');
        }
    }

    /**
     * Create shift schedule report
     */
    private function createShiftScheduleReport($section, $data)
    {
        // Add report title matching PDF format
        $section->addText('SHIFT SCHEDULE REPORT', [
            'bold' => true,
            'size' => 16,
            'name' => 'Arial',
            'color' => '222222'
        ], ['alignment' => Jc::CENTER]);

        $section->addTextBreak(1);
        
        // Schedule period removed - no longer displaying below title
        
        $section->addTextBreak(1);

        // Schedule data table with proper columns matching PDF format
        if (isset($data['schedules']) && is_array($data['schedules'])) {
            $this->addShiftScheduleTable($section, $data['schedules']);
        }
    }

    /**
     * Create assigned employees report
     */
    private function createAssignedEmployeesReport($section, $data)
    {
        // Report title
        $section->addTitle('ASSIGNED EMPLOYEES REPORT', 1);
        
        if (isset($data['department'])) {
            $section->addText('Department: ' . $data['department'], ['size' => 12, 'name' => 'Arial']);
        }
        
        $section->addTextBreak(1);

        // Employee data table
        if (isset($data['employees']) && is_array($data['employees'])) {
            $this->addEmployeeTable($section, $data['employees'], 'assignment');
        }
    }

    /**
     * Create shifting schedule details report
     */
    private function createShiftingScheduleDetailsReport($section, $data)
    {
        // Report title
        $section->addTitle('SHIFTING SCHEDULE DETAILS REPORT', 1);
        
        if (isset($data['schedule_details'])) {
            $section->addText('Schedule Details: ' . $data['schedule_details'], ['size' => 12, 'name' => 'Arial']);
        }
        
        $section->addTextBreak(1);

        // Detailed schedule table
        if (isset($data['schedule_data']) && is_array($data['schedule_data'])) {
            $this->addDetailedScheduleTable($section, $data['schedule_data']);
        }
    }

    /**
     * Create generic report
     */
    private function createGenericReport($section, $data, $reportType)
    {
        // Report title
        $section->addTitle(strtoupper(str_replace('_', ' ', $reportType)) . ' REPORT', 1);
        
        $section->addTextBreak(1);

        // Generic content
        if (isset($data['content'])) {
            $section->addText($data['content'], ['size' => 12, 'name' => 'Arial']);
        }
    }

    /**
     * Add summary section
     */
    private function addSummarySection($section, $summary)
    {
        $section->addTitle('SUMMARY', 3);
        
        if (is_array($summary)) {
            foreach ($summary as $key => $value) {
                $section->addText(ucfirst(str_replace('_', ' ', $key)) . ': ' . $value, 
                    ['size' => 11, 'name' => 'Arial']);
            }
        } else {
            $section->addText($summary, ['size' => 11, 'name' => 'Arial']);
        }
        
        $section->addTextBreak(1);
    }

    /**
     * Add employee table
     */
    private function addEmployeeTable($section, $employees, $type = 'attendance')
    {
        if (empty($employees)) {
            $section->addText('No employee data available.', ['size' => 11, 'name' => 'Arial']);
            return;
        }

        $table = $section->addTable('reportTable');
        
        // Table headers based on type
        $headers = $this->getEmployeeTableHeaders($type);
        $table->addRow();
        
        foreach ($headers as $header) {
            $cell = $table->addCell(2000, ['bgColor' => 'D0D0D0']);
            $cell->addText($header, ['bold' => true, 'size' => 10, 'name' => 'Arial'], 
                ['alignment' => Jc::CENTER]);
        }

        // Add employee data
        foreach ($employees as $employee) {
            $table->addRow();
            $rowData = $this->getEmployeeTableRowData($employee, $type);
            
            foreach ($rowData as $data) {
                $cell = $table->addCell(2000);
                $cell->addText($data, ['size' => 9, 'name' => 'Arial']);
            }
        }
    }

    /**
     * Add shift schedule table matching PDF format
     */
    private function addShiftScheduleTable($section, $schedules)
    {
        if (empty($schedules)) {
            $section->addText('No schedule data available.', ['size' => 11, 'name' => 'Arial']);
            return;
        }

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50
        ]);
        
        // Table headers matching PDF format: #, Schedule Name, Date From, Date To
        $headers = ['#', 'Schedule Name', 'Date From', 'Date To'];
        $table->addRow();
        
        // Calculate cell widths to span full page width (A4: 8.27" - 0.5" left - 0.5" right = 7.27" = ~10,440 twips)
        $cellWidths = [1000, 5000, 2000, 2000]; // Total: 10,000 twips (full width within margins)
        
        foreach ($headers as $index => $header) {
            $cell = $table->addCell($cellWidths[$index], ['bgColor' => 'D0D0D0']);
            $cell->addText($header, ['bold' => true, 'size' => 10, 'name' => 'Arial'], 
                ['alignment' => Jc::CENTER]);
        }

        // Add schedule data with row numbers
        foreach ($schedules as $index => $schedule) {
            $table->addRow();
            $rowData = [
                $index + 1, // Row number
                $schedule['schedule_name'] ?? $schedule['name'] ?? '',
                $schedule['date_from'] ?? '',
                $schedule['date_to'] ?? ''
            ];
            
            foreach ($rowData as $dataIndex => $data) {
                $cell = $table->addCell($cellWidths[$dataIndex]);
                $cell->addText($data, ['size' => 9, 'name' => 'Arial']);
            }
        }
    }

    /**
     * Add schedule table (legacy method - kept for backward compatibility)
     */
    private function addScheduleTable($section, $schedules)
    {
        if (empty($schedules)) {
            $section->addText('No schedule data available.', ['size' => 11, 'name' => 'Arial']);
            return;
        }

        $table = $section->addTable('reportTable');
        
        // Table headers
        $headers = ['Employee ID', 'Employee Name', 'Shift', 'Start Time', 'End Time', 'Days'];
        $table->addRow();
        
        foreach ($headers as $header) {
            $cell = $table->addCell(2000, ['bgColor' => 'D0D0D0']);
            $cell->addText($header, ['bold' => true, 'size' => 10, 'name' => 'Arial'], 
                ['alignment' => Jc::CENTER]);
        }

        // Add schedule data
        foreach ($schedules as $schedule) {
            $table->addRow();
            $rowData = [
                $schedule['employee_id'] ?? '',
                $schedule['employee_name'] ?? '',
                $schedule['shift_name'] ?? '',
                $schedule['start_time'] ?? '',
                $schedule['end_time'] ?? '',
                $schedule['days'] ?? ''
            ];
            
            foreach ($rowData as $data) {
                $cell = $table->addCell(2000);
                $cell->addText($data, ['size' => 9, 'name' => 'Arial']);
            }
        }
    }

    /**
     * Add detailed schedule table
     */
    private function addDetailedScheduleTable($section, $scheduleData)
    {
        if (empty($scheduleData)) {
            $section->addText('No detailed schedule data available.', ['size' => 11, 'name' => 'Arial']);
            return;
        }

        $table = $section->addTable('reportTable');
        
        // Table headers
        $headers = ['Date', 'Employee', 'Shift', 'Time In', 'Time Out', 'Status', 'Remarks'];
        $table->addRow();
        
        foreach ($headers as $header) {
            $cell = $table->addCell(1500, ['bgColor' => 'D0D0D0']);
            $cell->addText($header, ['bold' => true, 'size' => 9, 'name' => 'Arial'], 
                ['alignment' => Jc::CENTER]);
        }

        // Add schedule data
        foreach ($scheduleData as $data) {
            $table->addRow();
            $rowData = [
                $data['date'] ?? '',
                $data['employee_name'] ?? '',
                $data['shift'] ?? '',
                $data['time_in'] ?? '',
                $data['time_out'] ?? '',
                $data['status'] ?? '',
                $data['remarks'] ?? ''
            ];
            
            foreach ($rowData as $cellData) {
                $cell = $table->addCell(1500);
                $cell->addText($cellData, ['size' => 8, 'name' => 'Arial']);
            }
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
     * Add company header in main content area
     */
    private function addCompanyHeader($section)
    {
        // Create header table with logos and organization info in main content
        $headerTable = $section->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 0,
            'cellSpacing' => 0,
        ]);

        $headerTable->addRow();
        
        // Left cell - PTTC Logo
        $leftCell = $headerTable->addCell(Converter::pixelToTwip(5), [
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 0
        ]);
        $pttcLogoPath = public_path('dist/img/logo.png');
        if (file_exists($pttcLogoPath)) {
            $leftCell->addImage($pttcLogoPath, [
                'width' => Converter::pixelToTwip(2.5),
                'height' => Converter::pixelToTwip(2.5),
                'alignment' => Jc::CENTER
            ]);
        } else {
            $leftCell->addText(strtoupper(BranchHelper::getMainBranchCode()) . ' LOGO', ['size' => 8, 'name' => 'Arial', 'color' => '666666'], ['alignment' => Jc::CENTER]);
        }

        // Center cell - Organization info
        $centerCell = $headerTable->addCell(Converter::inchToTwip(7.0), [
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 0
        ]);
        $centerCell->addText('Department Of Trade and Industry', [
            'bold' => true,
            'size' => 12,
            'name' => 'Arial',
            'color' => '222222'
        ], ['alignment' => Jc::CENTER]);
        
        $centerCell->addText(strtoupper(CompanyHelper::getName()), [
            'bold' => true,
            'size' => 11,
            'name' => 'Arial',
            'color' => '111111'
        ], ['alignment' => Jc::CENTER]);

        // Right cell - Pilipinas Logo
        $rightCell = $headerTable->addCell(Converter::pixelToTwip(5), [
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 0
        ]);
        $pilipinasLogoPath = public_path('dist/img/reports/Pilipinas_logo.png');
        if (file_exists($pilipinasLogoPath)) {
            $rightCell->addImage($pilipinasLogoPath, [
                'width' => Converter::pixelToTwip(2.5),
                'height' => Converter::pixelToTwip(2.5),
                'alignment' => Jc::CENTER
            ]);
        } else {
            $rightCell->addText('PILIPINAS LOGO', ['size' => 8, 'name' => 'Arial', 'color' => '666666'], ['alignment' => Jc::CENTER]);
        }

        // Add some spacing after header
        $section->addTextBreak(2);
    }

    /**
     * Add document header matching ReportsHeader.vue format
     */
    private function addDocumentHeader($section)
    {
        // Get the header object
        $header = $section->addHeader();
        
        // Create header table with logos and organization info
        $headerTable = $header->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 0,
            'cellSpacing' => 0,
        ]);

        $headerTable->addRow();
        
        // Left cell - PTTC Logo
        $leftCell = $headerTable->addCell(Converter::pixelToTwip(5), [
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 0
        ]);
        $pttcLogoPath = public_path('dist/img/logo.png');
        if (file_exists($pttcLogoPath)) {
            $leftCell->addImage($pttcLogoPath, [
                'width' => Converter::pixelToTwip(2.5),
                'height' => Converter::pixelToTwip(2.5),
                'alignment' => Jc::CENTER
            ]);
        } else {
            $leftCell->addText(strtoupper(BranchHelper::getMainBranchCode()) . ' LOGO', ['size' => 8, 'name' => 'Arial', 'color' => '666666'], ['alignment' => Jc::CENTER]);
        }

        // Center cell - Organization info
        $centerCell = $headerTable->addCell(Converter::inchToTwip(7.0), [
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 0
        ]);
        $centerCell->addText('Department Of Trade and Industry', [
            'bold' => true,
            'size' => 12,
            'name' => 'Arial',
            'color' => '222222'
        ], ['alignment' => Jc::CENTER]);
        
        $centerCell->addText(strtoupper(CompanyHelper::getName()), [
            'bold' => true,
            'size' => 11,
            'name' => 'Arial',
            'color' => '111111'
        ], ['alignment' => Jc::CENTER]);

        // Right cell - Pilipinas Logo
        $rightCell = $headerTable->addCell(Converter::pixelToTwip(5), [
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 0
        ]);
        $pilipinasLogoPath = public_path('dist/img/reports/Pilipinas_logo.png');
        if (file_exists($pilipinasLogoPath)) {
            $rightCell->addImage($pilipinasLogoPath, [
                'width' => Converter::pixelToTwip(2.5),
                'height' => Converter::pixelToTwip(2.5),
                'alignment' => Jc::CENTER
            ]);
        } else {
            $rightCell->addText('PILIPINAS LOGO', ['size' => 8, 'name' => 'Arial', 'color' => '666666'], ['alignment' => Jc::CENTER]);
        }
    }

    /**
     * Add document footer matching ReportsFooter.vue format
     */
    private function addDocumentFooter($section)
    {
        // Get the footer object
        $footer = $section->addFooter();
        
        // Create footer table that spans full width
        $footerTable = $footer->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 0,
            'cellSpacing' => 0
        ]);

        $footerTable->addRow();
        
        // Left cell - Print info (takes up more space)
        $leftCell = $footerTable->addCell(Converter::inchToTwip(5), [
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 0
        ]);
        $leftCell->addText('Printed on: ' . date('F d, Y'), [
            'size' => 8,
            'name' => 'Arial',
            'color' => '666666'
        ]);
        $leftCell->addText('Printed by: System User', [
            'size' => 8,
            'name' => 'Arial',
            'color' => '666666'
        ]);

        // Right cell - Page info (takes up less space)
        $rightCell = $footerTable->addCell(Converter::inchToTwip(3), [
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 0
        ]);
        $rightCell->addText('Page 1 of 1', [
            'size' => 8,
            'name' => 'Arial',
            'color' => '666666'
        ], ['alignment' => Jc::END]);
    }

    /**
     * Create fix schedule detail report
     */
    private function createFixScheduleDetailReport($section, $data)
    {
        // Report title
        $scheduleName = $data['schedule_name'] ?? 'Fix Schedule';
        $section->addText('FIX SCHEDULE DETAIL REPORT', [
            'bold' => true,
            'size' => 16,
            'name' => 'Arial',
            'color' => '222222'
        ], ['alignment' => Jc::CENTER]);

        $section->addTextBreak(1);

        // Schedule name
        $section->addText('Schedule Name: ' . $scheduleName, [
            'bold' => true,
            'size' => 12,
            'name' => 'Arial'
        ]);

        // Add flags if available
        if (isset($data['flags'])) {
            $flags = $data['flags'];
            $flagText = [];
            if (!empty($flags['no_late'])) $flagText[] = 'No Late';
            if (!empty($flags['no_undertime'])) $flagText[] = 'No Undertime';
            if (!empty($flags['is_complete_attendance'])) $flagText[] = 'Complete Attendance';
            if (!empty($flagText)) {
                $section->addText('Rules: ' . implode(', ', $flagText), [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
            }
        }

        $section->addTextBreak(1);

        // Schedule days table
        if (isset($data['days']) && is_array($data['days']) && !empty($data['days'])) {
            $this->addFixScheduleDetailTable($section, $data['days']);
        }
    }

    /**
     * Create fix schedule assigned employees report
     */
    private function createFixScheduleAssignedReport($section, $data)
    {
        // Report title
        $scheduleName = $data['schedule_name'] ?? 'Fix Schedule';
        $section->addText('ASSIGNED EMPLOYEES REPORT', [
            'bold' => true,
            'size' => 16,
            'name' => 'Arial',
            'color' => '222222'
        ], ['alignment' => Jc::CENTER]);

        $section->addTextBreak(1);

        // Schedule name
        $section->addText('Schedule: ' . $scheduleName, [
            'bold' => true,
            'size' => 12,
            'name' => 'Arial'
        ]);

        $section->addTextBreak(1);

        // Employee data table
        if (isset($data['employees']) && is_array($data['employees'])) {
            $this->addEmployeeTable($section, $data['employees'], 'fix_schedule_assigned');
        }
    }

    /**
     * Create fix schedule list report
     */
    private function createFixScheduleListReport($section, $data)
    {
        // Report title
        $section->addText('FIX SCHEDULE REPORT', [
            'bold' => true,
            'size' => 16,
            'name' => 'Arial',
            'color' => '222222'
        ], ['alignment' => Jc::CENTER]);

        $section->addTextBreak(2);

        // Schedule data table
        if (isset($data['schedules']) && is_array($data['schedules'])) {
            $this->addFixScheduleListTable($section, $data['schedules']);
        }
    }

    /**
     * Add fix schedule detail table
     */
    private function addFixScheduleDetailTable($section, $days)
    {
        if (empty($days)) {
            $section->addText('No schedule data available.', ['size' => 11, 'name' => 'Arial']);
            return;
        }

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

        // Calculate cell widths (total width ~10,000 twips for A4 with margins)
        $numCols = count($headers);
        $baseWidth = intval(10000 / $numCols);
        $cellWidths = array_fill(0, $numCols, $baseWidth);

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50
        ]);

        // Add headers
        $table->addRow();
        foreach ($headers as $index => $header) {
            $cell = $table->addCell($cellWidths[$index], ['bgColor' => 'D0D0D0']);
            $cell->addText($header, ['bold' => true, 'size' => 9, 'name' => 'Arial'], 
                ['alignment' => Jc::CENTER]);
        }

        // Add day data
        foreach ($days as $day) {
            $table->addRow();
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

            foreach ($rowData as $index => $cellData) {
                $cell = $table->addCell($cellWidths[$index]);
                $cell->addText($cellData, ['size' => 8, 'name' => 'Arial']);
            }
        }
    }

    /**
     * Add fix schedule list table
     */
    private function addFixScheduleListTable($section, $schedules)
    {
        if (empty($schedules)) {
            $section->addText('No schedule data available.', ['size' => 11, 'name' => 'Arial']);
            return;
        }

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50
        ]);

        // Table headers
        $headers = ['#', 'Schedule Name', 'Created At', 'Updated At'];
        $cellWidths = [1000, 5000, 2000, 2000];
        $table->addRow();

        foreach ($headers as $index => $header) {
            $cell = $table->addCell($cellWidths[$index], ['bgColor' => 'D0D0D0']);
            $cell->addText($header, ['bold' => true, 'size' => 10, 'name' => 'Arial'], 
                ['alignment' => Jc::CENTER]);
        }

        // Add schedule data
        foreach ($schedules as $index => $schedule) {
            $table->addRow();
            $rowData = [
                $index + 1,
                $schedule['name'] ?? $schedule['schedule_name'] ?? '',
                $schedule['created_at'] ?? '',
                $schedule['updated_at'] ?? ''
            ];

            foreach ($rowData as $dataIndex => $data) {
                $cell = $table->addCell($cellWidths[$dataIndex]);
                $cell->addText($data, ['size' => 9, 'name' => 'Arial']);
            }
        }
    }

    /**
     * Create shift schedule list report
     */
    private function createShiftScheduleListReport($section, $data)
    {
        // Report title
        $section->addText('SHIFT SCHEDULE REPORT', [
            'bold' => true,
            'size' => 16,
            'name' => 'Arial',
            'color' => '222222'
        ], ['alignment' => Jc::CENTER]);

        $section->addTextBreak(2);

        // Schedule data table
        if (isset($data['schedules']) && is_array($data['schedules'])) {
            $this->addShiftScheduleListTable($section, $data['schedules']);
        }
    }

    /**
     * Create shift schedule viewer report
     */
    private function createShiftScheduleViewerReport($section, $data)
    {
        // Report title
        $scheduleName = $data['schedule_name'] ?? 'Shift Schedule';
        $section->addText('SHIFT SCHEDULE VIEWER REPORT', [
            'bold' => true,
            'size' => 16,
            'name' => 'Arial',
            'color' => '222222'
        ], ['alignment' => Jc::CENTER]);

        $section->addTextBreak(1);

        // Schedule name
        $section->addText('Schedule Name: ' . $scheduleName, [
            'bold' => true,
            'size' => 12,
            'name' => 'Arial'
        ]);

        // Date range
        if (isset($data['date_from']) || isset($data['date_to'])) {
            $dateRange = '';
            if (isset($data['date_from'])) $dateRange .= 'From: ' . $data['date_from'];
            if (isset($data['date_from']) && isset($data['date_to'])) $dateRange .= ' ';
            if (isset($data['date_to'])) $dateRange .= 'To: ' . $data['date_to'];
            $section->addText('Period: ' . $dateRange, [
                'bold' => true,
                'size' => 12,
                'name' => 'Arial'
            ]);
        }

        $section->addTextBreak(1);

        // Schedule data table
        if (isset($data['schedule_data']) && is_array($data['schedule_data']) && !empty($data['schedule_data'])) {
            $this->addShiftScheduleViewerTable($section, $data['schedule_data']);
        }
    }

    /**
     * Create shift schedule employees report
     */
    private function createShiftScheduleEmployeesReport($section, $data)
    {
        // Report title
        $scheduleName = $data['schedule_name'] ?? 'Shift Schedule';
        $section->addText('ASSIGNED EMPLOYEES REPORT', [
            'bold' => true,
            'size' => 16,
            'name' => 'Arial',
            'color' => '222222'
        ], ['alignment' => Jc::CENTER]);

        $section->addTextBreak(1);

        // Schedule name
        $section->addText('Schedule: ' . $scheduleName, [
            'bold' => true,
            'size' => 12,
            'name' => 'Arial'
        ]);

        $section->addTextBreak(1);

        // Employee data table
        if (isset($data['employees']) && is_array($data['employees'])) {
            $this->addEmployeeTable($section, $data['employees'], 'shift_schedule_employees');
        }
    }

    /**
     * Create Leave Credits List Report (per-employee format, not table)
     */
    private function createLeaveCreditsListReport($section, $data)
    {
        // Report title
        $section->addText('LEAVE CREDITS REPORT', [
            'bold' => true,
            'size' => 16,
            'name' => 'Arial',
            'color' => '222222'
        ], ['alignment' => Jc::CENTER]);

        $section->addTextBreak(2);

        $employees = $data['employees'] ?? [];
        
        if (empty($employees)) {
            $section->addText('No employee data available.', [
                'size' => 12,
                'name' => 'Arial',
                'italic' => true
            ]);
            return;
        }

        // Add each employee with their leave credits
        foreach ($employees as $index => $employee) {
            // Employee number and name
            $section->addText(($index + 1) . '. ' . ($employee['name'] ?? 'Unknown'), [
                'bold' => true,
                'size' => 12,
                'name' => 'Arial'
            ]);

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
                $section->addText(implode(' | ', $details), [
                    'size' => 10,
                    'name' => 'Arial',
                    'color' => '606266'
                ]);
            }

            $section->addTextBreak(1);

            // Leave credits
            if (!empty($employee['leave_credits']) && is_array($employee['leave_credits'])) {
                foreach ($employee['leave_credits'] as $credit) {
                    $creditName = $credit['leave_type_name'] ?? 'Unknown Leave Type';
                    $creditValue = number_format($credit['credits'] ?? 0, 2);
                    $section->addText('  ' . $creditName . ' - ' . $creditValue, [
                        'size' => 11,
                        'name' => 'Arial'
                    ]);
                }
            } else {
                $section->addText('  No leave credits available', [
                    'size' => 11,
                    'name' => 'Arial',
                    'italic' => true,
                    'color' => '909399'
                ]);
            }

            // Add spacing between employees
            $section->addTextBreak(2);
        }
    }

    /**
     * Create leave monitoring report
     */
    private function createLeaveMonitoringReport($section, $data)
    {
        // Get tab display name
        $tabDisplayName = $data['tab_display_name'] ?? 'Leave Monitoring';
        $reportTitle = 'LEAVE MONITORING REPORT - ' . strtoupper($tabDisplayName);

        // Report title
        $section->addText($reportTitle, [
            'bold' => true,
            'size' => 16,
            'name' => 'Arial',
            'color' => '222222'
        ], ['alignment' => Jc::CENTER]);

        $leaves = $data['leaves'] ?? [];
        
        if (empty($leaves)) {
            $section->addText('No leave data available.', [
                'size' => 12,
                'name' => 'Arial',
                'italic' => true
            ]);
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
            $section->addText(($empIndex + 1) . '. ' . ($employee['name'] ?? 'Unknown'), [
                'bold' => true,
                'size' => 12,
                'name' => 'Arial'
            ]);

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
                $section->addText(implode(' | ', $details), [
                    'size' => 10,
                    'name' => 'Arial',
                    'color' => '606266'
                ]);
            }

            // Display all leave applications for this employee
            foreach ($employeeLeaves as $leaveIndex => $leave) {
                // Application header (if multiple applications)
                if (count($employeeLeaves) > 1) {
                    $section->addText('Application ' . ($leaveIndex + 1) . ':', [
                        'bold' => true,
                        'size' => 11,
                        'name' => 'Arial',
                        'color' => '606266'
                    ]);
                }

            // Leave details
            if (!empty($leave['leave_type'])) {
                $section->addText('Leave Type: ' . $leave['leave_type'], [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
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
                $section->addText('Date Range: ' . $dateFrom . ' - ' . $dateTo, [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
            }
            
            if (!empty($leave['balance']) || $leave['balance'] === 0 || $leave['balance'] === '0') {
                $section->addText('Days: ' . number_format((float)$leave['balance'], 2), [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
            }
            
            if (!empty($leave['day_type'])) {
                $section->addText('Day Type: ' . $leave['day_type'], [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
            }
            
            if (!empty($leave['reason'])) {
                $section->addText('Reason: ' . $leave['reason'], [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
            }
            
            if (!empty($leave['remarks'])) {
                $section->addText('Remarks: ' . $leave['remarks'], [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
            }
            
            if (!empty($leave['status'])) {
                $status = ucfirst($leave['status']);
                $section->addText('Status: ' . $status, [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
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
                $section->addText($approver1Text, [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
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
                $section->addText($approver2Text, [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
            }

                // Add spacing between applications (if multiple)
                if ($leaveIndex < count($employeeLeaves) - 1) {
                    $section->addTextBreak(0.5);
                }
            }

            // Add minimal spacing between employees
            $empIndex++;
            if ($empIndex < count($groupedByEmployee)) {
                $section->addTextBreak(0.5);
            }
        }
    }

    /**
     * Create leave credit card monitoring report
     */
    private function createLeaveCreditCardMonitoringReport($section, $data)
    {
        // Report title
        $section->addText('LEAVE CREDIT CARD MONITORING REPORT', [
            'bold' => true,
            'size' => 16,
            'name' => 'Arial',
            'color' => '222222'
        ], ['alignment' => Jc::CENTER]);

        $section->addTextBreak(2);

        $employees = $data['employees'] ?? [];
        
        if (empty($employees)) {
            $section->addText('No employee data available.', [
                'size' => 12,
                'name' => 'Arial',
                'italic' => true
            ]);
            return;
        }

        // Add each employee with their leave credits
        foreach ($employees as $index => $employee) {
            // Employee number and name
            $section->addText(($index + 1) . '. ' . ($employee['name'] ?? 'Unknown'), [
                'bold' => true,
                'size' => 12,
                'name' => 'Arial'
            ]);

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
                $section->addText(implode(' | ', $details), [
                    'size' => 10,
                    'name' => 'Arial',
                    'color' => '606266'
                ]);
            }

            $section->addTextBreak(1);

            // Leave credits
            if (!empty($employee['leave_credits']) && is_array($employee['leave_credits'])) {
                foreach ($employee['leave_credits'] as $credit) {
                    $creditName = $credit['leave_type_name'] ?? 'Unknown Leave Type';
                    $creditValue = number_format($credit['credits'] ?? 0, 2);
                    $section->addText('  ' . $creditName . ' - ' . $creditValue, [
                        'size' => 11,
                        'name' => 'Arial'
                    ]);
                }
            } else {
                $section->addText('  No leave credits available', [
                    'size' => 11,
                    'name' => 'Arial',
                    'italic' => true,
                    'color' => '909399'
                ]);
            }

            // Add spacing between employees
            $section->addTextBreak(2);
        }
    }

    /**
     * Create OB monitoring report
     */
    private function createOBMonitoringReport($section, $data)
    {
        // Get tab display name
        $tabDisplayName = $data['tab_display_name'] ?? 'OB Monitoring';
        $reportTitle = 'OB MONITORING REPORT - ' . strtoupper($tabDisplayName);

        // Report title
        $section->addText($reportTitle, [
            'bold' => true,
            'size' => 16,
            'name' => 'Arial',
            'color' => '222222'
        ], ['alignment' => Jc::CENTER]);

        $obRecords = $data['ob_records'] ?? [];
        
        if (empty($obRecords)) {
            $section->addText('No OB data available.', [
                'size' => 12,
                'name' => 'Arial',
                'italic' => true
            ]);
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
            $section->addText(($empIndex + 1) . '. ' . $employeeName, [
                'bold' => true,
                'size' => 12,
                'name' => 'Arial'
            ]);

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
                $section->addText(implode(' | ', $details), [
                    'size' => 10,
                    'name' => 'Arial',
                    'color' => '606266'
                ]);
            }

            // Display all OB applications for this employee
            foreach ($employeeObRecords as $obIndex => $ob) {
                // Application header (if multiple applications)
                if (count($employeeObRecords) > 1) {
                    $section->addText('Application ' . ($obIndex + 1) . ':', [
                        'bold' => true,
                        'size' => 11,
                        'name' => 'Arial',
                        'color' => '606266'
                    ]);
                }

            // OB details
            // Date and Time Covered
            $dateFrom = $ob['date_time_from'] ?? $ob['date'] ?? null;
            $dateTo = $ob['date_time_to'] ?? $ob['date'] ?? null;
            if ($dateFrom && $dateTo) {
                $dateFromFormatted = $this->formatDateForDocx($dateFrom);
                $dateToFormatted = $this->formatDateForDocx($dateTo);
                $timeFromFormatted = $this->formatTimeForDocx($dateFrom);
                $timeToFormatted = $this->formatTimeForDocx($dateTo);
                
                // Check if same day
                try {
                    $fromDate = new \DateTime($dateFrom);
                    $toDate = new \DateTime($dateTo);
                    if ($fromDate->format('Y-m-d') === $toDate->format('Y-m-d')) {
                        $section->addText('Date and Time Covered: ' . $dateFromFormatted . ' • ' . $timeFromFormatted . ' - ' . $timeToFormatted, [
                            'size' => 11,
                            'name' => 'Arial'
                        ]);
                    } else {
                        $section->addText('Date and Time Covered: ' . $dateFromFormatted . ' ' . $timeFromFormatted . ' - ' . $dateToFormatted . ' ' . $timeToFormatted, [
                            'size' => 11,
                            'name' => 'Arial'
                        ]);
                    }
                } catch (\Exception $e) {
                    $section->addText('Date and Time Covered: ' . ($dateFrom ?? 'N/A') . ' - ' . ($dateTo ?? 'N/A'), [
                        'size' => 11,
                        'name' => 'Arial'
                    ]);
                }
            }
            
            if (!empty($ob['purpose'])) {
                $section->addText('Purpose: ' . $ob['purpose'], [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
            }
            
            if (!empty($ob['client'])) {
                $section->addText('Client: ' . $ob['client'], [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
            }
            
            if (!empty($ob['created_at'])) {
                $filedDate = $this->formatDateTimeForDocx($ob['created_at']);
                $section->addText('Filed: ' . $filedDate, [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
            }
            
            if (!empty($ob['status'])) {
                $status = ucfirst($ob['status']);
                $section->addText('Status: ' . $status, [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
            }
            
            // Approvers
            if (!empty($ob['approver_1'])) {
                $approver1Text = 'Approver 1: ' . $ob['approver_1'];
                if (!empty($ob['processed_date'])) {
                    $processedDate = $this->formatDateTimeForDocx($ob['processed_date']);
                    $approver1Text .= ' (' . $processedDate . ')';
                }
                $section->addText($approver1Text, [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
            }
            
            if (!empty($ob['approver_2'])) {
                $approver2Text = 'Approver 2: ' . $ob['approver_2'];
                if (!empty($ob['processed_date_2'])) {
                    $processedDate2 = $this->formatDateTimeForDocx($ob['processed_date_2']);
                    $approver2Text .= ' (' . $processedDate2 . ')';
                }
                $section->addText($approver2Text, [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
            }
            
            if (!empty($ob['approver_3'])) {
                $approver3Text = 'Approver 3: ' . $ob['approver_3'];
                if (!empty($ob['processed_date_3'])) {
                    $processedDate3 = $this->formatDateTimeForDocx($ob['processed_date_3']);
                    $approver3Text .= ' (' . $processedDate3 . ')';
                }
                $section->addText($approver3Text, [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
            }
            
            if (!empty($ob['approver_4'])) {
                $section->addText('Approver 4: ' . $ob['approver_4'], [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
            }

                // Add spacing between applications (if multiple)
                if ($obIndex < count($employeeObRecords) - 1) {
                    $section->addTextBreak(0.5);
                }
            }

            // Add minimal spacing between employees
            $empIndex++;
            if ($empIndex < count($groupedByEmployee)) {
                $section->addTextBreak(0.5);
            }
        }
    }

    /**
     * Create OT monitoring report
     */
    private function createOTMonitoringReport($section, $data)
    {
        // Get tab display name
        $tabDisplayName = $data['tab_display_name'] ?? 'OT Monitoring';
        $reportTitle = 'OT MONITORING REPORT - ' . strtoupper($tabDisplayName);

        // Report title
        $section->addText($reportTitle, [
            'bold' => true,
            'size' => 16,
            'name' => 'Arial',
            'color' => '222222'
        ], ['alignment' => Jc::CENTER]);

        $otRecords = $data['ot_records'] ?? [];
        
        if (empty($otRecords)) {
            $section->addText('No OT data available.', [
                'size' => 12,
                'name' => 'Arial',
                'italic' => true
            ]);
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
            $section->addText(($empIndex + 1) . '. ' . $employeeName, [
                'bold' => true,
                'size' => 12,
                'name' => 'Arial'
            ]);

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
                $section->addText(implode(' | ', $details), [
                    'size' => 10,
                    'name' => 'Arial',
                    'color' => '606266'
                ]);
            }

            // Display all OT applications for this employee
            foreach ($employeeOtRecords as $otIndex => $ot) {
                // Application header (if multiple applications)
                if (count($employeeOtRecords) > 1) {
                    $section->addText('Application ' . ($otIndex + 1) . ':', [
                        'bold' => true,
                        'size' => 11,
                        'name' => 'Arial',
                        'color' => '606266'
                    ]);
                }

                // OT details
                // Date Filed
                if (!empty($ot['created_at'])) {
                    $filedDate = $this->formatDateTimeForDocx($ot['created_at']);
                    $section->addText('Date Filed: ' . $filedDate, [
                        'size' => 11,
                        'name' => 'Arial'
                    ]);
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
                $section->addText('Overtime Type: ' . $overtimeType, [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
                
                // Overtime Date
                if (!empty($ot['date'])) {
                    $otDate = $this->formatDateForDocx($ot['date']);
                    $section->addText('Overtime Date: ' . $otDate, [
                        'size' => 11,
                        'name' => 'Arial'
                    ]);
                }
                
                // Time Range
                if (!empty($ot['date_time_from']) || !empty($ot['date_time_to'])) {
                    $timeFrom = $this->formatTimeForDocx($ot['date_time_from'] ?? null);
                    $timeTo = $this->formatTimeForDocx($ot['date_time_to'] ?? null);
                    $section->addText('Time Range: ' . $timeFrom . ' - ' . $timeTo, [
                        'size' => 11,
                        'name' => 'Arial'
                    ]);
                }
                
                // Total Hours
                $totalHours = number_format((float)($ot['total_hours'] ?? 0), 2);
                $section->addText('Total Hours: ' . $totalHours . ' hours', [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
                
                // Remarks
                if (!empty($ot['remarks'])) {
                    $section->addText('Remarks: ' . $ot['remarks'], [
                        'size' => 11,
                        'name' => 'Arial'
                    ]);
                }
                
                // Status
                if (!empty($ot['status'])) {
                    $status = ucfirst($ot['status']);
                    $section->addText('Status: ' . $status, [
                        'size' => 11,
                        'name' => 'Arial'
                    ]);
                }
                
                // Approvers
                if (!empty($ot['approver_1'])) {
                    $approver1Text = 'Approver 1: ' . $ot['approver_1'];
                    if (!empty($ot['processed_date'])) {
                        $processedDate = $this->formatDateForDocx($ot['processed_date']);
                        $approver1Text .= ' (' . $processedDate . ')';
                    }
                    $section->addText($approver1Text, [
                        'size' => 11,
                        'name' => 'Arial'
                    ]);
                }
                
                if (!empty($ot['approver_2'])) {
                    $approver2Text = 'Approver 2: ' . $ot['approver_2'];
                    if (!empty($ot['processed_date_2'])) {
                        $processedDate2 = $this->formatDateForDocx($ot['processed_date_2']);
                        $approver2Text .= ' (' . $processedDate2 . ')';
                    }
                    $section->addText($approver2Text, [
                        'size' => 11,
                        'name' => 'Arial'
                    ]);
                }
                
                if (!empty($ot['approver_3'])) {
                    $approver3Text = 'Approver 3: ' . $ot['approver_3'];
                    if (!empty($ot['processed_date_3'])) {
                        $processedDate3 = $this->formatDateForDocx($ot['processed_date_3']);
                        $approver3Text .= ' (' . $processedDate3 . ')';
                    }
                    $section->addText($approver3Text, [
                        'size' => 11,
                        'name' => 'Arial'
                    ]);
                }
                
                if (!empty($ot['approver_4'])) {
                    $section->addText('Approver 4: ' . $ot['approver_4'], [
                        'size' => 11,
                        'name' => 'Arial'
                    ]);
                }

                // Remove spacing between applications (if multiple) - no text break
            }

            // Remove spacing between employees - no text break
            $empIndex++;
        }
    }

    /**
     * Create COC monitoring report
     */
    private function createCOCMonitoringReport($section, $data)
    {
        // Get month name
        $monthName = $data['month_name'] ?? '';
        $reportTitle = 'COC MONITORING REPORT';
        if (!empty($monthName)) {
            $reportTitle .= ' - ' . strtoupper($monthName);
        }

        // Report title
        $section->addText($reportTitle, [
            'bold' => true,
            'size' => 16,
            'name' => 'Arial',
            'color' => '222222'
        ], ['alignment' => Jc::CENTER]);

        $cocRecords = $data['coc_records'] ?? [];
        
        if (empty($cocRecords)) {
            $section->addText('No COC data available.', [
                'size' => 12,
                'name' => 'Arial',
                'italic' => true
            ]);
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
            $section->addText(($empIndex + 1) . '. ' . ($employee['name'] ?? 'Unknown'), [
                'bold' => true,
                'size' => 12,
                'name' => 'Arial'
            ]);

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
                $section->addText(implode(' | ', $details), [
                    'size' => 10,
                    'name' => 'Arial',
                    'color' => '606266'
                ]);
            }

            // COC details
            // Month
            if (!empty($employee['months'])) {
                $section->addText('Month: ' . $employee['months'], [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
            }
            
            // Year
            if (!empty($employee['year'])) {
                $section->addText('Year: ' . $employee['year'], [
                    'size' => 11,
                    'name' => 'Arial'
                ]);
            }
            
            // Accrued Hours
            $accrued = number_format((float)($employee['carryover'] ?? 0), 2);
            $section->addText('Accrued (hrs): ' . $accrued, [
                'size' => 11,
                'name' => 'Arial'
            ]);
            
            // Cumulative Hours
            $cumulative = number_format((float)($employee['total_hours'] ?? 0), 2);
            $section->addText('Cumulative (hrs): ' . $cumulative, [
                'size' => 11,
                'name' => 'Arial'
            ]);
            
            // Leave Used (days)
            $leaveUsedDays = $employee['total_leave_days'] ?? (isset($employee['total_leave_hours']) ? (float)$employee['total_leave_hours'] / 8 : 0);
            $leaveUsed = number_format((float)$leaveUsedDays, 3);
            $section->addText('Leave Used (days): ' . $leaveUsed, [
                'size' => 11,
                'name' => 'Arial'
            ]);
            
            // Remaining Balance
            $remaining = number_format((float)($employee['remaining_balance'] ?? 0), 2);
            $section->addText('Remaining (hrs): ' . $remaining, [
                'size' => 11,
                'name' => 'Arial'
            ]);
            
            // Converted Days
            $converted = number_format((float)($employee['converted_days'] ?? 0), 4);
            $section->addText('Converted (days): ' . $converted, [
                'size' => 11,
                'name' => 'Arial'
            ]);

            // Remove spacing between employees - no text break
            $empIndex++;
        }
    }

    /**
     * Create work suspension report
     */
    private function createWorkSuspensionReport($section, $data)
    {
        // Report title
        $section->addText('WORK SUSPENSION REPORT', [
            'bold' => true,
            'size' => 16,
            'name' => 'Arial',
            'color' => '222222'
        ], ['alignment' => Jc::CENTER]);

        $workSuspensions = $data['work_suspensions'] ?? [];
        
        if (empty($workSuspensions)) {
            $section->addText('No work suspension data available.', [
                'size' => 12,
                'name' => 'Arial',
                'italic' => true
            ]);
            return;
        }

        // Create table with only Reason and Date Range columns
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50
        ]);

        // Table headers
        $cellWidths = [7000, 3000]; // Reason wider, Date Range narrower
        $table->addRow();
        
        // Reason header
        $cell = $table->addCell($cellWidths[0], ['bgColor' => 'D0D0D0']);
        $cell->addText('Reason', ['bold' => true, 'size' => 10, 'name' => 'Arial'], 
            ['alignment' => Jc::CENTER]);

        // Date Range header
        $cell = $table->addCell($cellWidths[1], ['bgColor' => 'D0D0D0']);
        $cell->addText('Date Range', ['bold' => true, 'size' => 10, 'name' => 'Arial'], 
            ['alignment' => Jc::CENTER]);

        // Add data rows
        foreach ($workSuspensions as $suspension) {
            $table->addRow();
            
            // Reason
            $reason = $suspension['reason'] ?? 'N/A';
            $cell = $table->addCell($cellWidths[0]);
            $cell->addText($reason, ['size' => 10, 'name' => 'Arial']);

            // Date Range - if 1 day, show only date from
            $dateFrom = $suspension['date_from'] ?? $suspension['start_date'] ?? null;
            $dateTo = $suspension['date_to'] ?? $suspension['end_date'] ?? null;
            $dateRange = $this->formatWorkSuspensionDateRangeForDocx($dateFrom, $dateTo);
            
            $cell = $table->addCell($cellWidths[1]);
            $cell->addText($dateRange, ['size' => 10, 'name' => 'Arial']);
        }
    }

    /**
     * Format work suspension date range for DOCX (if 1 day, show only date from)
     */
    private function formatWorkSuspensionDateRangeForDocx($dateFrom, $dateTo)
    {
        if (!$dateFrom) return 'N/A';

        try {
            $from = new \DateTime($dateFrom);
            $fromFormatted = $this->formatDateForDocx($dateFrom);

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
            $toFormatted = $this->formatDateForDocx($dateTo);
            return $fromFormatted . ' - ' . $toFormatted;
        } catch (\Exception $e) {
            return $dateFrom . ($dateTo ? ' - ' . $dateTo : '');
        }
    }

    /**
     * Create daily attendance summary report
     */
    private function createDailyAttendanceSummaryReport($section, $data)
    {
        // Get date display
        $dateDisplay = $data['date_display'] ?? '';
        $reportTitle = 'DAILY ATTENDANCE SUMMARY';
        if (!empty($dateDisplay)) {
            $reportTitle .= ' - ' . strtoupper($dateDisplay);
        }

        // Report title
        $section->addText($reportTitle, [
            'bold' => true,
            'size' => 16,
            'name' => 'Arial',
            'color' => '222222'
        ], ['alignment' => Jc::CENTER]);

        $attendanceRecords = $data['attendance_records'] ?? [];
        
        if (empty($attendanceRecords)) {
            $section->addText('No attendance data available.', [
                'size' => 12,
                'name' => 'Arial',
                'italic' => true
            ]);
            return;
        }

        // Create table with compact design
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 40 // Reduced cell margin for compact spacing
        ]);

        // Table headers
        $headers = ['Access No', 'Employee Name', 'Position', 'Department', 'AM In', 'AM Out', 'Break In', 'Break Out', 'PM In', 'PM Out'];
        // Calculate cell widths (total width ~10,000 twips for A4 with margins in landscape)
        // Reasonable widths for each column
        $cellWidths = [1000, 2000, 1800, 1800, 900, 900, 900, 900, 900, 900];
        $table->addRow();
        
        foreach ($headers as $index => $header) {
            $cell = $table->addCell($cellWidths[$index] ?? 1000, ['bgColor' => 'D0D0D0']);
            $alignment = in_array($header, ['Access No', 'AM In', 'AM Out', 'Break In', 'Break Out', 'PM In', 'PM Out']) 
                ? Jc::CENTER 
                : Jc::LEFT;
            $cell->addText($header, ['bold' => true, 'size' => 9, 'name' => 'Arial'], 
                ['alignment' => $alignment]);
        }

        // Add data rows
        foreach ($attendanceRecords as $record) {
            $table->addRow();
            
            // Access No
            $cell = $table->addCell($cellWidths[0]);
            $cell->addText($record['usercode'] ?? '-', ['size' => 9, 'name' => 'Arial'], 
                ['alignment' => Jc::CENTER]);
            
            // Employee Name
            $cell = $table->addCell($cellWidths[1]);
            $cell->addText($record['employee_name'] ?? '-', ['size' => 9, 'name' => 'Arial']);
            
            // Position
            $cell = $table->addCell($cellWidths[2]);
            $cell->addText($record['position'] ?? '-', ['size' => 9, 'name' => 'Arial']);
            
            // Department
            $cell = $table->addCell($cellWidths[3]);
            $cell->addText($record['department'] ?? '-', ['size' => 9, 'name' => 'Arial']);
            
            // AM In
            $amIn = $this->formatTimeForAttendanceDocx($record['am_in'] ?? null);
            $cell = $table->addCell($cellWidths[4]);
            $cell->addText($amIn, ['size' => 9, 'name' => 'Arial'], 
                ['alignment' => Jc::CENTER]);
            
            // AM Out
            $amOut = $this->formatTimeForAttendanceDocx($record['am_out'] ?? null);
            $cell = $table->addCell($cellWidths[5]);
            $cell->addText($amOut, ['size' => 9, 'name' => 'Arial'], 
                ['alignment' => Jc::CENTER]);
            
            // Break In
            $breakIn = $this->formatTimeForAttendanceDocx($record['break_in'] ?? null);
            $cell = $table->addCell($cellWidths[6]);
            $cell->addText($breakIn, ['size' => 9, 'name' => 'Arial'], 
                ['alignment' => Jc::CENTER]);
            
            // Break Out
            $breakOut = $this->formatTimeForAttendanceDocx($record['break_out'] ?? null);
            $cell = $table->addCell($cellWidths[7]);
            $cell->addText($breakOut, ['size' => 9, 'name' => 'Arial'], 
                ['alignment' => Jc::CENTER]);
            
            // PM In
            $pmIn = $this->formatTimeForAttendanceDocx($record['pm_in'] ?? null);
            $cell = $table->addCell($cellWidths[8]);
            $cell->addText($pmIn, ['size' => 9, 'name' => 'Arial'], 
                ['alignment' => Jc::CENTER]);
            
            // PM Out
            $pmOut = $this->formatTimeForAttendanceDocx($record['pm_out'] ?? null);
            $cell = $table->addCell($cellWidths[9]);
            $cell->addText($pmOut, ['size' => 9, 'name' => 'Arial'], 
                ['alignment' => Jc::CENTER]);
        }
    }

    /**
     * Format time for attendance DOCX (helper method)
     */
    private function formatTimeForAttendanceDocx($value)
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
     * Format date for DOCX (helper method)
     */
    private function formatDateForDocx($value)
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
     * Format time for DOCX (helper method)
     */
    private function formatTimeForDocx($value)
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
     * Format date and time for DOCX (helper method)
     */
    private function formatDateTimeForDocx($value)
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
     * Add shift schedule list table
     */
    private function addShiftScheduleListTable($section, $schedules)
    {
        if (empty($schedules)) {
            $section->addText('No schedule data available.', ['size' => 11, 'name' => 'Arial']);
            return;
        }

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50
        ]);

        // Table headers
        $headers = ['#', 'Schedule Name', 'Date From', 'Date To'];
        $cellWidths = [1000, 5000, 2000, 2000];
        $table->addRow();

        foreach ($headers as $index => $header) {
            $cell = $table->addCell($cellWidths[$index], ['bgColor' => 'D0D0D0']);
            $cell->addText($header, ['bold' => true, 'size' => 10, 'name' => 'Arial'], 
                ['alignment' => Jc::CENTER]);
        }

        // Add schedule data
        foreach ($schedules as $index => $schedule) {
            $table->addRow();
            $rowData = [
                $index + 1,
                $schedule['name'] ?? $schedule['schedule_name'] ?? '',
                $schedule['date_from'] ?? '',
                $schedule['date_to'] ?? ''
            ];

            foreach ($rowData as $dataIndex => $data) {
                $cell = $table->addCell($cellWidths[$dataIndex]);
                $cell->addText($data, ['size' => 9, 'name' => 'Arial']);
            }
        }
    }

    /**
     * Add shift schedule viewer table
     */
    private function addShiftScheduleViewerTable($section, $scheduleData)
    {
        if (empty($scheduleData)) {
            $section->addText('No schedule data available.', ['size' => 11, 'name' => 'Arial']);
            return;
        }

        // Build headers
        $headers = ['Date', 'WFH', 'AM - In', 'AM - Out', 'Break - In', 'Break - Out', 'PM - In', 'PM - Out', 'Grace Period', 'Flexi Hours', 'Work Hours'];

        // Calculate cell widths (total width ~10,000 twips for A4 with margins)
        $numCols = count($headers);
        $baseWidth = intval(10000 / $numCols);
        $cellWidths = array_fill(0, $numCols, $baseWidth);

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50
        ]);

        // Add headers
        $table->addRow();
        foreach ($headers as $index => $header) {
            $cell = $table->addCell($cellWidths[$index], ['bgColor' => 'D0D0D0']);
            $cell->addText($header, ['bold' => true, 'size' => 9, 'name' => 'Arial'], 
                ['alignment' => Jc::CENTER]);
        }

        // Add schedule data
        foreach ($scheduleData as $data) {
            $table->addRow();
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

            foreach ($rowData as $index => $cellData) {
                $cell = $table->addCell($cellWidths[$index]);
                $cell->addText($cellData, ['size' => 8, 'name' => 'Arial']);
            }
        }
    }

    /**
     * Create tardiness report (Late, Undertime, Absences, or Combined)
     */
    private function createTardinessReport($section, $data, $reportType = 'late')
    {
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
        $section->addText($reportTitle, [
            'bold' => true,
            'size' => 16,
            'name' => 'Arial'
        ], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(2);

        // Employee Name
        $section->addText($employeeName, [
            'bold' => true,
            'size' => 12,
            'name' => 'Arial'
        ], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(1);

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
            $section->addText($dateRangeText, [
                'size' => 10,
                'name' => 'Arial'
            ], ['alignment' => Jc::CENTER]);
            $section->addTextBreak(1);
        }
        $section->addTextBreak(1);

        // Employee Details
        if (!empty($header)) {
            $section->addText('Employee No: ' . ($header['employee_no'] ?? 'N/A'), [
                'size' => 10,
                'name' => 'Arial'
            ]);
            
            if (!empty($header['position'])) {
                $section->addText('Position: ' . $header['position'], [
                    'size' => 10,
                    'name' => 'Arial'
                ]);
            }

            if (!empty($header['department']) || !empty($header['department_name'])) {
                $dept = $header['department'] ?? $header['department_name'] ?? 'N/A';
                $section->addText('Department: ' . $dept, [
                    'size' => 10,
                    'name' => 'Arial'
                ]);
            }
            $section->addTextBreak(1);
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

        // Create table
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 30
        ]);

        // Calculate column widths (based on number of columns)
        $numCols = count($headers);
        $totalWidth = 10000; // Total table width in twips
        $colWidths = [];
        if ($numCols === 2) {
            // Absences Report: Date, Absent only
            $colWidths = [5000, 5000];
        } elseif ($numCols === 4) {
            // Late/Undertime/Absences only
            $colWidths = [1500, 2500, 2000, 4000];
        } elseif ($numCols === 5) {
            // Combined with one extra column
            $colWidths = [1500, 2000, 1500, 1500, 3500];
        } elseif ($numCols === 6) {
            // Combined with two extra columns
            $colWidths = [1200, 1800, 1200, 1200, 1200, 3400];
        } elseif ($numCols === 7) {
            // Combined with all columns
            $colWidths = [1200, 1800, 1200, 1200, 1200, 1000, 2400];
        } else {
            // Default equal widths
            $colWidth = $totalWidth / $numCols;
            for ($i = 0; $i < $numCols; $i++) {
                $colWidths[] = $colWidth;
            }
        }

        // Table Headers
        $table->addRow();
        foreach ($headers as $index => $headerText) {
            $cell = $table->addCell($colWidths[$index] ?? 1500, ['bgColor' => 'F0F0F0']);
            $cell->addText($headerText, [
                'bold' => true,
                'size' => 9,
                'name' => 'Arial'
            ], ['alignment' => Jc::CENTER]);
        }

        // Add data rows
        foreach ($rows as $row) {
            // Skip placeholder rows for specific report types
            if (isset($row['isPlaceholder']) && $row['isPlaceholder'] && $reportType !== 'combined') {
                continue;
            }

            $table->addRow();
            $colIndex = 0;

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
            $cell = $table->addCell($colWidths[$colIndex++] ?? 1500);
            $cell->addText($dateText, ['size' => 9, 'name' => 'Arial'], 
                ['alignment' => Jc::CENTER]);

            if (!$isAbsencesOnly) {
                // Time-In and Out
                $timeInOut = '-';
                if (!isset($row['isPlaceholder']) || !$row['isPlaceholder']) {
                    if (empty($row['remarks']) || stripos($row['remarks'], 'rest day') === false) {
                        $amIn = $this->formatTimeForWord($row['am_in'] ?? null);
                        $pmOut = $this->formatTimeForWord($row['pm_out'] ?? null);
                        if ($amIn && $pmOut) {
                            $timeInOut = $amIn . ' - ' . $pmOut;
                        } elseif ($amIn) {
                            $timeInOut = $amIn . ' - --';
                        } elseif ($pmOut) {
                            $timeInOut = '-- - ' . $pmOut;
                        }
                    }
                }
                $cell = $table->addCell($colWidths[$colIndex++] ?? 1500);
                $cell->addText($timeInOut, ['size' => 9, 'name' => 'Arial'], 
                    ['alignment' => Jc::CENTER]);

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
                $cell = $table->addCell($colWidths[$colIndex++] ?? 1500);
                $cell->addText($workHoursText, ['size' => 9, 'name' => 'Arial'], 
                    ['alignment' => Jc::CENTER]);
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
                $cell = $table->addCell($colWidths[$colIndex++] ?? 1500);
                $cell->addText($lateText, ['size' => 9, 'name' => 'Arial'], 
                    ['alignment' => Jc::CENTER]);
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
                $cell = $table->addCell($colWidths[$colIndex++] ?? 1500);
                $cell->addText($undertimeText, ['size' => 9, 'name' => 'Arial'], 
                    ['alignment' => Jc::CENTER]);
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
                $cell = $table->addCell($colWidths[$colIndex++] ?? 1500);
                $cell->addText($absentText, [
                    'size' => 9,
                    'name' => 'Arial',
                    'bold' => $absentText === 'Yes'
                ], ['alignment' => Jc::CENTER]);
            }

            if (!$isAbsencesOnly) {
                // Remarks
                $remarks = isset($row['remarks']) ? $row['remarks'] : '-';
                if (isset($row['isPlaceholder']) && $row['isPlaceholder']) {
                    $remarks = $row['remarks'] ?? 'This date does not have a record';
                }
                $cell = $table->addCell($colWidths[$colIndex++] ?? 1500);
                $cell->addText($remarks, ['size' => 9, 'name' => 'Arial'], 
                    ['alignment' => Jc::LEFT]);
            }
        }

        // Signature Section
        $section->addTextBreak(2);
        $section->addText('', ['size' => 10, 'name' => 'Arial']);
        $section->addText('Authorized Signatory', [
            'bold' => true,
            'size' => 10,
            'name' => 'Arial'
        ], ['alignment' => Jc::RIGHT]);
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

    /**
     * Format time for Word document
     */
    private function formatTimeForWord($timeString)
    {
        if (!$timeString) return '';
        try {
            $parts = explode(':', $timeString);
            if (count($parts) >= 2) {
                $hours = (int)$parts[0];
                $minutes = $parts[1];
                $isPM = $hours >= 12;
                $meridiem = $isPM ? 'PM' : 'AM';
                $hours = $hours % 12;
                if ($hours === 0) $hours = 12;
                return sprintf('%d:%s %s', $hours, $minutes, $meridiem);
            }
            return $timeString;
        } catch (\Exception $e) {
            return $timeString;
        }
    }
}


