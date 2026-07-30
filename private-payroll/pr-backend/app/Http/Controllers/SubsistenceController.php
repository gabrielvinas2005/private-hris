<?php

namespace App\Http\Controllers;

use PDF;
use App\Support\ReportDivisionFilter;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use \NumberFormatter;

class SubsistenceController extends Controller
{
    use ApiResponse;

    /**
     * Get subsistence report data
     */
    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");

            $pay_periods = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.posted',
                    'a.payroll_cutoff_id',
                    DB::raw("CONCAT(b.name,' (',c.name,' - ',a.release_date,') ') as name"),
                    'a.release_date',
                    'c.name as cutoff_name'
                )
                ->orderBy('a.release_date', 'desc')
                ->orderBy('a.id', 'desc')
                ->get()
                ->unique(function ($row) {
                    $month = $row->release_date
                        ? date('Y-m', strtotime($row->release_date))
                        : 'unknown';
                    return $month . '|' . (int) $row->payroll_cutoff_id;
                })
                ->values();

            $divisions = ReportDivisionFilter::activeDivisions();

            return $this->successResponse([
                'divisions' => $divisions,
                'departments' => $divisions,
                'pay_periods' => $pay_periods
            ], 'Subsistence report data loaded successfully');
        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Log::error('SubsistenceController@index error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Return empty data as fallback to prevent 500 error
            return $this->successResponse([
                'departments' => [],
                'pay_periods' => []
            ], 'Subsistence report data loaded successfully (empty data due to database error)');
        }
    }

    /**
     * Get subsistence report data for specific department and payroll period
     */
    public function getReportData(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");
            $payroll_id = $request->payroll_interval_id;

            $divisionId = ReportDivisionFilter::resolveId($request);
            if (!$divisionId || !$payroll_id) {
                return $this->errorResponse('Division and payroll period are required');
            }

            // Fetch the main employee data filtered by division and payroll period
            $data = DB::table('divisions as a')
                ->join('employees as b', 'b.division_id', '=', 'a.id')
                ->join('positions as c', 'b.position_id', '=', 'c.id')
                ->leftJoin('time_data as d', function ($join) use ($payroll_id) {
                    $join->on('d.employee_id', '=', 'b.id')
                        ->where('d.payroll_period_id', '=', $payroll_id);
                })
                ->leftJoin('employee_offboardings as e', 'e.employee_id', '=', 'b.id') // Join offboarding table
                ->select(
                    'a.id as division_id',
                    'a.name as department',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted, 0) = 0 THEN
                CONCAT(b.first_name, ' ', b.last_name)
            ELSE
                RTRIM([dbo].[ufn_DecryptString](b.first_name, '$app_key')) + ' ' +
                RTRIM([dbo].[ufn_DecryptString](b.last_name, '$app_key'))
            END as name"),
                    'b.employee_no',
                    'c.name as position',
                    'b.id as employee_id',
                    DB::raw('COUNT(d.date) as actual_days') // Calculate actual days directly
                )
                ->where('a.id', '=', $divisionId)
                ->whereNull('e.id') // Ensure the employee is NOT offboarded
                ->groupBy('a.name', 'b.is_encrypted', 'b.first_name', 'b.last_name', 'b.employee_no', 'c.name', 'b.id')
                ->get();

            $selected_pay_period = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->where('a.id', $payroll_id)
                ->select(
                    DB::raw("DATENAME(MONTH, a.release_date) + ' ' + CAST(YEAR(a.release_date) AS VARCHAR) as month_year")
                )
                ->first();

            // Calculate actual days for each employee based on attendance
            $data = $data->map(function ($employee) use ($payroll_id) {
                $attendance = DB::table('time_data')
                    ->where('employee_id', $employee->employee_id)
                    ->where('payroll_period_id', $payroll_id)
                    ->select('am_in', 'pm_out', 'absent', 'leave', 'remarks', 'is_ot', 'work_hours')
                    ->get();

                // Calculate actual workdays
                $actualDays = $attendance->reduce(function ($carry, $entry) {
                    if (!is_null($entry->am_in) && !is_null($entry->pm_out)) {
                        $carry += 1; // Add 1 if both am_in and pm_out are present
                    }
                    if ($entry->remarks === 'Rest Day') {
                        $carry -= 1; // Deduct for rest day
                    }
                    if ($entry->work_hours == 0 && $entry->is_ot == 1) {
                        $carry -= 1; // Increment counter for matching condition
                    }
                    return $carry;
                }, 0);

                // Count the days where work_hours == 0 and is_ot == 1
                $zeroWorkHoursOTDays = $attendance->reduce(function ($carry, $entry) {
                    if ($entry->work_hours == 0 && $entry->is_ot == 1) {
                        $carry += 1; // Increment counter for matching condition
                    }
                    return $carry;
                }, 0);

                $employee->actual_days = $actualDays;
                $employee->zero_work_ot_days = $zeroWorkHoursOTDays;
                $employee->zero_work_ot = $zeroWorkHoursOTDays * 25; // Add the new field

                return $employee;
            });

            // ✅ Calculate `totalFullTimeMonths` BEFORE mapping
            $firstEmployee = $data->first(); // Get the first employee

            if ($firstEmployee) {
                $attendance = DB::table('time_data')
                    ->where('employee_id', $firstEmployee->employee_id) // Focus on the first employee
                    ->where('payroll_period_id', $payroll_id)
                    ->select('am_in', 'pm_out', 'absent', 'leave', 'remarks', 'is_ot', 'work_hours')
                    ->get();

                // Calculate full days based on attendance
                $fullDaysNoDeductions = $attendance->reduce(function ($carry, $entry) {
                    if (!is_null($entry->am_in) && !is_null($entry->pm_out)) {
                        $carry += 1; // Count only based on am_in and pm_out
                    }
                    if ($entry->absent == 1 || $entry->leave == 1) {
                        $carry += 1; // Include days for absent/leave
                    }
                    if ($entry->remarks === 'Rest Day') {
                        $carry -= 1; // Subtract for rest day
                    }
                    if ($entry->work_hours == 0 && $entry->is_ot == 1) {
                        $carry -= 1; // Subtract for this condition
                    }
                    return $carry;
                }, 0);

                // Ensure no negative values
                $firstEmployee->full_time_months = max(0, $fullDaysNoDeductions);
            }

            // ✅ Now, `totalFullTimeMonths` is available before mapping
            $totalFullTimeMonths = $firstEmployee ? $firstEmployee->full_time_months : 0;

            // Prepare additional columns for allowances
            $data = $data->map(function ($employee) use ($totalFullTimeMonths) { // Pass $totalFullTimeMonths into closure
                $full_time_days = $employee->actual_days;
                $employee->full_time_days = $full_time_days;
                $employee->full_time_service = $full_time_days * 50; // ₱50/day
                $employee->part_time_days = max(0, $employee->actual_days - $full_time_days);
                $employee->part_time_service = $employee->part_time_days * 25; // ₱25/day

                // Fix prorate calculation & avoid division by zero
                $employee->prorate = ($totalFullTimeMonths > 0) ? (150 / $totalFullTimeMonths) * $full_time_days : 0;

                return $employee;
            });

            return $this->successResponse([
                'employees' => $data,
                'selected_pay_period' => $selected_pay_period,
                'total_full_time_months' => $totalFullTimeMonths
            ], 'Subsistence report data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve subsistence report data: ' . $e->getMessage());
        }
    }

    /**
     * Generate PDF for subsistence report (separate method for PDF generation)
     */
    public function generatePdf(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");
            $payroll_id = $request->payroll_interval_id;

            $divisionId = ReportDivisionFilter::resolveId($request);
            if (!$divisionId || !$payroll_id) {
                return $this->errorResponse('Division and payroll period are required');
            }

            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $data = DB::table('divisions as a')
                ->join('employees as b', 'b.division_id', '=', 'a.id')
                ->join('positions as c', 'b.position_id', '=', 'c.id')
                ->leftJoin('time_data as d', function ($join) use ($payroll_id) {
                    $join->on('d.employee_id', '=', 'b.id')
                        ->where('d.payroll_period_id', '=', $payroll_id);
                })
                ->leftJoin('employee_offboardings as e', 'e.employee_id', '=', 'b.id') // Join offboarding table
                ->select(
                    'a.name as department',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted, 0) = 0 THEN
                CONCAT(b.first_name, ' ', b.last_name)
            ELSE
                RTRIM([dbo].[ufn_DecryptString](b.first_name, '$app_key')) + ' ' +
                RTRIM([dbo].[ufn_DecryptString](b.last_name, '$app_key'))
            END as name"),
                    'b.employee_no',
                    'c.name as position',
                    'b.id as employee_id',
                    DB::raw('COUNT(d.date) as actual_days') // Calculate actual days directly
                )
                ->where('a.id', '=', $divisionId)
                ->whereNull('e.id') // Ensure the employee is NOT offboarded
                ->groupBy('a.name', 'b.is_encrypted', 'b.first_name', 'b.last_name', 'b.employee_no', 'c.name', 'b.id')
                ->get();

            $selected_pay_period = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->where('a.id', $payroll_id)
                ->select(
                    DB::raw("DATENAME(MONTH, a.release_date) + ' ' + CAST(YEAR(a.release_date) AS VARCHAR) as month_year")
                )
                ->first();

            // Calculate actual days for each employee based on attendance
            $data = $data->map(function ($employee) use ($payroll_id) {
                $attendance = DB::table('time_data')
                    ->where('employee_id', $employee->employee_id)
                    ->where('payroll_period_id', $payroll_id)
                    ->select('am_in', 'pm_out', 'absent', 'leave', 'remarks', 'is_ot', 'work_hours')
                    ->get();

                // Calculate actual workdays
                $actualDays = $attendance->reduce(function ($carry, $entry) {
                    if (!is_null($entry->am_in) && !is_null($entry->pm_out)) {
                        $carry += 1; // Add 1 if both am_in and pm_out are present
                    }
                    if ($entry->remarks === 'Rest Day') {
                        $carry -= 1; // Deduct for rest day
                    }
                    if ($entry->work_hours == 0 && $entry->is_ot == 1) {
                        $carry -= 1; // Increment counter for matching condition
                    }
                    return $carry;
                }, 0);

                // Count the days where work_hours == 0 and is_ot == 1
                $zeroWorkHoursOTDays = $attendance->reduce(function ($carry, $entry) {
                    if ($entry->work_hours == 0 && $entry->is_ot == 1) {
                        $carry += 1; // Increment counter for matching condition
                    }
                    return $carry;
                }, 0);

                $employee->actual_days = $actualDays;
                $employee->zero_work_ot_days = $zeroWorkHoursOTDays;
                $employee->zero_work_ot = $zeroWorkHoursOTDays * 25; // Add the new field

                return $employee;
            });

            // ✅ Calculate `totalFullTimeMonths` BEFORE mapping
            $firstEmployee = $data->first(); // Get the first employee

            if ($firstEmployee) {
                $attendance = DB::table('time_data')
                    ->where('employee_id', $firstEmployee->employee_id) // Focus on the first employee
                    ->where('payroll_period_id', $payroll_id)
                    ->select('am_in', 'pm_out', 'absent', 'leave', 'remarks', 'is_ot', 'work_hours')
                    ->get();

                // Calculate full days based on attendance
                $fullDaysNoDeductions = $attendance->reduce(function ($carry, $entry) {
                    if (!is_null($entry->am_in) && !is_null($entry->pm_out)) {
                        $carry += 1; // Count only based on am_in and pm_out
                    }
                    if ($entry->absent == 1 || $entry->leave == 1) {
                        $carry += 1; // Include days for absent/leave
                    }
                    if ($entry->remarks === 'Rest Day') {
                        $carry -= 1; // Subtract for rest day
                    }
                    if ($entry->work_hours == 0 && $entry->is_ot == 1) {
                        $carry -= 1; // Subtract for this condition
                    }
                    return $carry;
                }, 0);

                // Ensure no negative values
                $firstEmployee->full_time_months = max(0, $fullDaysNoDeductions);
            }

            // ✅ Now, `totalFullTimeMonths` is available before mapping
            $totalFullTimeMonths = $firstEmployee ? $firstEmployee->full_time_months : 0;

            // Prepare additional columns for allowances
            $data = $data->map(function ($employee) use ($totalFullTimeMonths) { // Pass $totalFullTimeMonths into closure
                $full_time_days = $employee->actual_days;
                $employee->full_time_days = $full_time_days;
                $employee->full_time_service = $full_time_days * 50; // ₱50/day
                $employee->part_time_days = max(0, $employee->actual_days - $full_time_days);
                $employee->part_time_service = $employee->part_time_days * 25; // ₱25/day

                // Fix prorate calculation & avoid division by zero
                $employee->prorate = ($totalFullTimeMonths > 0) ? (150 / $totalFullTimeMonths) * $full_time_days : 0;

                return $employee;
            });

            $companies = DB::table('companies')->get();

            // Collect signatory data from the request
            $signatories = [
                [
                    'signatory_1' => $request->signatory_1,
                    'signatory_position_1' => $request->signatory_position_1,

                    'signatory_2' => $request->signatory_2,
                    'signatory_position_2' => $request->signatory_position_2,

                    'signatory_3' => $request->signatory_3,
                    'signatory_position_3' => $request->signatory_position_3,

                    'signatory_4' => $request->signatory_4,
                    'signatory_position_4' => $request->signatory_position_4,

                    'signatory_5' => $request->signatory_5,
                    'signatory_position_5' => $request->signatory_position_5,
                ]
            ];

            // Generate PDF
            $pdf = PDF::loadView('subsistence.subsistence_report_print', compact('data', 'image', 'totalFullTimeMonths',
            'companies', 'selected_pay_period', 'signatories'))
                ->setOptions(['defaultFont' => 'sans-serif']);

            $pdf->setPaper('A4', 'landscape');
            
            // Return PDF as base64 for API
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'subsistence_report_' . $request->department_id . '_' . $payroll_id . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate subsistence report PDF: ' . $e->getMessage());
        }
    }

    /**
     * Show specific subsistence data
     */
    public function show($id)
    {
        try {
            $app_key = env("APP_KEY", "");
            
            $data = DB::table('departments as a')
                ->join('employees as b', 'b.department_id', '=', 'a.id')
                ->join('positions as c', 'b.position_id', '=', 'c.id')
                ->select(
                    'a.name as department',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted, 0) = 0 THEN
                        CONCAT(b.first_name, ' ', b.last_name)
                    ELSE
                        RTRIM([dbo].[ufn_DecryptString](b.first_name, '$app_key')) + ' ' +
                        RTRIM([dbo].[ufn_DecryptString](b.last_name, '$app_key'))
                    END as name"),
                    'b.employee_no',
                    'c.name as position',
                    'b.id as employee_id'
                )
                ->where('b.id', $id)
                ->first();

            if (!$data) {
                return $this->notFoundResponse('Employee not found');
            }

            return $this->successResponse($data, 'Employee subsistence data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee subsistence data: ' . $e->getMessage());
        }
    }
}
