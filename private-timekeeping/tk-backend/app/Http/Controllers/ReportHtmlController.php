<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Traits\ApiResponse;

class ReportHtmlController extends Controller
{
    use ApiResponse;

    /**
     * Generate HTML preview from structured data
     */
    public function generate(Request $request)
    {
        try {
            $validated = $request->validate([
                'report_type' => 'required|string',
                'data' => 'required|array',
            ]);

            $reportType = $validated['report_type'];
            $data = $validated['data'];

            // Generate HTML based on report type
            $html = $this->generateHtmlPreview($reportType, $data);

            // Get origin from request for CORS (required when credentials are included)
            $origin = $request->headers->get('Origin') ?? '*';

            return response()->json([
                'html' => $html
            ])->header('Access-Control-Allow-Origin', $origin)
              ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
              ->header('Access-Control-Allow-Credentials', 'true');

        } catch (\Exception $e) {
            Log::error('HTML preview generation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->serverErrorResponse('Failed to generate HTML preview: ' . $e->getMessage());
        }
    }

    /**
     * Generate HTML preview based on report type
     */
    private function generateHtmlPreview($reportType, $data)
    {
        switch ($reportType) {
            case 'fix_schedule_list':
                return $this->generateFixScheduleListHtml($data);
            case 'fix_schedule_detail':
                return $this->generateFixScheduleDetailHtml($data);
            case 'fix_schedule_assigned':
                return $this->generateFixScheduleAssignedHtml($data);
            case 'shift_schedule_list':
                return $this->generateShiftScheduleListHtml($data);
            case 'shift_schedule_viewer':
                return $this->generateShiftScheduleViewerHtml($data);
            case 'shift_schedule_employees':
                return $this->generateShiftScheduleEmployeesHtml($data);
            case 'leave_credits_list':
                return $this->generateLeaveCreditsListHtml($data);
            default:
                return $this->generateGenericHtml($data, $reportType);
        }
    }

    /**
     * Generate Fix Schedule List HTML
     */
    private function generateFixScheduleListHtml($data)
    {
        $schedules = $data['schedules'] ?? [];
        $title = 'Fix Schedule Report';
        
        $html = $this->getReportHeader($title);
        $html .= $this->generateTableHtml([
            ['#', 'Schedule Name', 'Created At', 'Updated At'],
            array_map(function($schedule, $index) {
                return [
                    $index + 1,
                    $schedule['name'] ?? '',
                    $schedule['created_at'] ?? '',
                    $schedule['updated_at'] ?? ''
                ];
            }, $schedules, array_keys($schedules))
        ]);
        
        return $html;
    }

    /**
     * Generate Fix Schedule Detail HTML
     */
    private function generateFixScheduleDetailHtml($data)
    {
        $scheduleName = $data['schedule_name'] ?? 'Fix Schedule';
        $flags = $data['flags'] ?? [];
        $days = $data['days'] ?? [];
        
        $title = 'Fix Schedule Details for ' . htmlspecialchars($scheduleName);
        $html = $this->getReportHeader($title);
        
        // Add flags
        $flagText = [];
        if (!empty($flags['no_late'])) $flagText[] = 'No Late';
        if (!empty($flags['no_undertime'])) $flagText[] = 'No Undertime';
        if (!empty($flags['is_complete_attendance'])) $flagText[] = 'Complete Attendance';
        if (!empty($flagText)) {
            $html .= '<div style="text-align:center;margin:6px 0 10px;">';
            foreach ($flagText as $flag) {
                $html .= '<label style="display:inline-flex;align-items:center;gap:6px;margin:0 8px;font-family:Arial;font-size:12px;color:#334155;">
                    <input type="checkbox" disabled checked style="width:14px;height:14px" />
                    <span>' . htmlspecialchars($flag) . '</span>
                </label>';
            }
            $html .= '</div>';
        }
        
        // Determine columns
        $hasND = false;
        $hasGrace = false;
        $hasFlexi = false;
        foreach ($days as $day) {
            if (!empty($day['with_nd'])) $hasND = true;
            if (!empty($day['grace_period'])) $hasGrace = true;
            if (!empty($day['flexi_hours'])) $hasFlexi = true;
        }
        
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
        
        $rows = [];
        foreach ($days as $day) {
            $isRestday = !empty($day['is_restday']);
            $dayName = ($day['name'] ?? '') . ($isRestday ? ' (Rest Day)' : '');
            
            $row = [
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
                $row[] = $isRestday ? '' : (!empty($day['with_nd']) ? 'Yes' : 'No');
                $row[] = $isRestday ? '' : ($day['nd_start'] ?? '');
                $row[] = $isRestday ? '' : ($day['nd_end'] ?? '');
                $row[] = $isRestday ? '' : ($day['nd_rate'] ?? '');
            }
            if ($hasGrace) {
                $row[] = $isRestday ? '' : ($day['grace_period'] ?? '');
            }
            if ($hasFlexi) {
                $row[] = $isRestday ? '' : ($day['flexi_hours'] ?? '');
            }
            $row[] = $isRestday ? '' : ($day['work_hours'] ?? '');
            
            $rows[] = $row;
        }
        
        $html .= $this->generateTableHtml([$headers, $rows]);
        return $html;
    }

    /**
     * Generate Fix Schedule Assigned HTML
     */
    private function generateFixScheduleAssignedHtml($data)
    {
        $scheduleName = $data['schedule_name'] ?? 'Fix Schedule';
        $employees = $data['employees'] ?? [];
        
        $title = 'Assigned Employees for ' . htmlspecialchars($scheduleName);
        $html = $this->getReportHeader($title);
        
        $headers = ['Employee No', 'Name', 'Position', 'Department', 'Employment Type'];
        $rows = array_map(function($emp) {
            return [
                $emp['employee_no'] ?? $emp['employee_id'] ?? '',
                $emp['name'] ?? '',
                $emp['position'] ?? '',
                $emp['department'] ?? '',
                $emp['employment_type'] ?? ''
            ];
        }, $employees);
        
        $html .= $this->generateTableHtml([$headers, $rows]);
        return $html;
    }

    /**
     * Generate Shift Schedule List HTML
     */
    private function generateShiftScheduleListHtml($data)
    {
        $schedules = $data['schedules'] ?? [];
        $title = 'Shift Schedule Report';
        
        $html = $this->getReportHeader($title);
        $html .= $this->generateTableHtml([
            ['#', 'Schedule Name', 'Date From', 'Date To'],
            array_map(function($schedule, $index) {
                return [
                    $index + 1,
                    $schedule['name'] ?? $schedule['schedule_name'] ?? '',
                    $schedule['date_from'] ?? '',
                    $schedule['date_to'] ?? ''
                ];
            }, $schedules, array_keys($schedules))
        ]);
        
        return $html;
    }

    /**
     * Generate Shift Schedule Viewer HTML
     */
    private function generateShiftScheduleViewerHtml($data)
    {
        $scheduleName = $data['schedule_name'] ?? 'Shift Schedule';
        $dateFrom = $data['date_from'] ?? '';
        $dateTo = $data['date_to'] ?? '';
        $scheduleData = $data['schedule_data'] ?? [];
        
        $title = 'Shift Schedule Viewer Report';
        $html = $this->getReportHeader($title);
        
        $html .= '<div style="margin: 10px 0;"><strong>Schedule Name:</strong> ' . htmlspecialchars($scheduleName) . '</div>';
        if ($dateFrom || $dateTo) {
            $period = 'Period: ';
            if ($dateFrom) $period .= 'From: ' . htmlspecialchars($dateFrom);
            if ($dateFrom && $dateTo) $period .= ' ';
            if ($dateTo) $period .= 'To: ' . htmlspecialchars($dateTo);
            $html .= '<div style="margin: 10px 0;"><strong>' . $period . '</strong></div>';
        }
        
        $headers = ['Date', 'WFH', 'AM - In', 'AM - Out', 'Break - In', 'Break - Out', 'PM - In', 'PM - Out', 'Grace Period', 'Flexi Hours', 'Work Hours'];
        $rows = array_map(function($item) {
            return [
                $item['shift_date'] ?? $item['date'] ?? '',
                !empty($item['is_wfh']) ? 'Yes' : 'No',
                $item['am_in'] ?? '',
                $item['am_out'] ?? '',
                $item['break_in'] ?? '',
                $item['break_out'] ?? '',
                $item['pm_in'] ?? '',
                $item['pm_out'] ?? '',
                $item['grace_period'] ?? '',
                $item['flexi_hours'] ?? '',
                $item['work_hours'] ?? ''
            ];
        }, $scheduleData);
        
        $html .= $this->generateTableHtml([$headers, $rows]);
        return $html;
    }

    /**
     * Generate Shift Schedule Employees HTML
     */
    private function generateShiftScheduleEmployeesHtml($data)
    {
        $scheduleName = $data['schedule_name'] ?? 'Shift Schedule';
        $employees = $data['employees'] ?? [];
        
        $title = 'Assigned Employees for ' . htmlspecialchars($scheduleName);
        $html = $this->getReportHeader($title);
        
        $headers = ['Employee No', 'Name', 'Position', 'Department'];
        $rows = array_map(function($emp) {
            return [
                $emp['employee_no'] ?? $emp['employee_id'] ?? '',
                $emp['name'] ?? '',
                $emp['position'] ?? '',
                $emp['department'] ?? '',
            ];
        }, $employees);
        
        $html .= $this->generateTableHtml([$headers, $rows]);
        return $html;
    }

    /**
     * Generate Leave Credits List HTML (per-employee format, not table)
     */
    private function generateLeaveCreditsListHtml($data)
    {
        $employees = $data['employees'] ?? [];
        $leaveTypes = $data['leave_types'] ?? [];
        $title = 'Leave Credits Report';
        
        $html = $this->getReportHeader($title);
        
        if (empty($employees)) {
            return $html . '<p style="padding: 20px; text-align: center; color: #909399;">No employee data available.</p>';
        }
        
        $html .= '<div style="padding: 20px; font-family: Arial, sans-serif;">';
        
        foreach ($employees as $index => $employee) {
            // Employee header
            $html .= '<div style="margin-bottom: 30px; page-break-inside: avoid;">';
            $html .= '<h3 style="margin: 0 0 10px 0; font-size: 16px; font-weight: bold; color: #303133;">' . ($index + 1) . '. ' . htmlspecialchars($employee['name'] ?? 'Unknown') . '</h3>';
            
            // Employee details
            $details = [];
            if (!empty($employee['employee_no'])) {
                $details[] = 'Employee No: ' . htmlspecialchars($employee['employee_no']);
            }
            if (!empty($employee['position'])) {
                $details[] = 'Position: ' . htmlspecialchars($employee['position']);
            }
            if (!empty($employee['department'])) {
                $details[] = 'Department: ' . htmlspecialchars($employee['department']);
            }
            if (!empty($details)) {
                $html .= '<div style="margin-bottom: 10px; font-size: 12px; color: #606266;">' . implode(' | ', $details) . '</div>';
            }
            
            // Leave credits list
            if (!empty($employee['leave_credits']) && is_array($employee['leave_credits'])) {
                $html .= '<div style="margin-left: 20px; margin-top: 8px;">';
                foreach ($employee['leave_credits'] as $credit) {
                    $creditName = htmlspecialchars($credit['leave_type_name'] ?? 'Unknown Leave Type');
                    $creditValue = number_format($credit['credits'] ?? 0, 2);
                    $html .= '<div style="margin-bottom: 5px; font-size: 14px; color: #303133;">';
                    $html .= '<span style="font-weight: 500;">' . $creditName . '</span>';
                    $html .= ' - <span style="font-weight: 600;">' . $creditValue . '</span>';
                    $html .= '</div>';
                }
                $html .= '</div>';
            } else {
                $html .= '<div style="margin-left: 20px; margin-top: 8px; font-size: 14px; color: #909399; font-style: italic;">No leave credits available</div>';
            }
            
            $html .= '</div>';
            
            // Add separator line between employees (except last one)
            if ($index < count($employees) - 1) {
                $html .= '<hr style="border: none; border-top: 1px solid #e4e7ed; margin: 20px 0;" />';
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Generate generic HTML
     */
    private function generateGenericHtml($data, $reportType)
    {
        $title = ucfirst(str_replace('_', ' ', $reportType)) . ' Report';
        return $this->getReportHeader($title) . '<p>Report data not available.</p>';
    }

    /**
     * Get report header HTML
     */
    private function getReportHeader($title)
    {
        return '<h2 style="font-family:Arial;margin:0 0 8px; text-align: center; font-weight: bold;">' . htmlspecialchars($title) . '</h2>';
    }

    /**
     * Generate table HTML
     */
    private function generateTableHtml($tableData)
    {
        if (empty($tableData) || count($tableData) < 2) {
            return '<p>No data available.</p>';
        }
        
        $headers = $tableData[0];
        $rows = $tableData[1] ?? [];
        
        $html = '<table style="border-collapse:collapse;width:100%;font-family:Arial;font-size:12px;margin-top:10px;">';
        $html .= '<thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th style="text-align:center;padding:6px 8px;border:1px solid #e5e7eb;background:#f1f5f9;color:#0f172a;font-weight:700;">' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td style="padding:6px 8px;border:1px solid #e5e7eb;text-align:center;">' . htmlspecialchars($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        return $html;
    }
}

