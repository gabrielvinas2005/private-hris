<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\TimeData;
use App\Employee;
use App\Department;
use App\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class TardinessReportsController extends Controller
{
    /**
     * Get tardiness reports with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $app_key = env("APP_KEY", "");

            // Subquery: employees that have records in time_data OR time_data_adj within the date range
            $timeDataEmployees = DB::table('time_data as a')
                ->whereNotNull('a.employee_id')
                ->when($request->filled('date_from'), function ($q) use ($request) {
                    $q->where('a.date', '>=', $request->date_from);
                })
                ->when($request->filled('date_to'), function ($q) use ($request) {
                    $q->where('a.date', '<=', $request->date_to);
                })
                ->groupBy('a.employee_id')
                ->select('a.employee_id');

            if (Schema::hasTable('time_data_adj')) {
                $adjEmployees = DB::table('time_data_adj as a')
                    ->whereNotNull('a.employee_id')
                    ->when($request->filled('date_from'), function ($q) use ($request) {
                        $q->where('a.date', '>=', $request->date_from);
                    })
                    ->when($request->filled('date_to'), function ($q) use ($request) {
                        $q->where('a.date', '<=', $request->date_to);
                    })
                    ->groupBy('a.employee_id')
                    ->select('a.employee_id');
                $timeDataEmployees = $timeDataEmployees->union($adjEmployees);
            }

            // List EMPLOYEES limited to those present in time_data
            $query = DB::table('employees as b')
                ->joinSub($timeDataEmployees, 'td', function ($join) {
                    $join->on('b.id', '=', 'td.employee_id');
                })
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->leftJoin('branches as d', 'b.branch_id', '=', 'd.id')
                ->leftJoin('positions as e', 'b.position_id', '=', 'e.id')
                ->select([
                    'b.id as employee_id',
                    'b.employee_no',
                    'b.photo',
                    // Build employee name safely when middle_name is NULL/blank.
                    // Use CONCAT + CASE (instead of '+' with potential NULL) so we never return NULL for employee_name.
                    // Original (decrypting) employee_name expression kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                    //            CONCAT(
                    //                RTRIM(b.first_name),' ',
                    //                CASE
                    //                    WHEN COALESCE(LTRIM(RTRIM(b.middle_name)),'') = '' THEN ''
                    //                    ELSE CONCAT(SUBSTRING(b.middle_name,1,1),'. ')
                    //                END,
                    //                RTRIM(b.last_name)
                    //            )
                    //         ELSE
                    //            CONCAT(
                    //                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')),' ',
                    //                CASE
                    //                    WHEN COALESCE(LTRIM(RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key'))), '') = '' THEN ''
                    //                    ELSE CONCAT(UPPER(SUBSTRING([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1)),'. ')
                    //                END,
                    //                RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                    //            )
                    //         END as employee_name"),
                    DB::raw("CONCAT(
                               RTRIM(b.first_name),' ',
                               CASE
                                   WHEN COALESCE(LTRIM(RTRIM(b.middle_name)),'') = '' THEN ''
                                   ELSE CONCAT(SUBSTRING(b.middle_name,1,1),'. ')
                               END,
                               RTRIM(b.last_name)
                            ) as employee_name"),
                    'c.name as department_name',
                    'd.name as branch_name',
                    'e.name as position_name'
                ]);

            // Filters (search, department, position)
            if ($request->filled('department_id')) {
                $query->where('b.department_id', $request->department_id);
            }
            if ($request->filled('position_id')) {
                $query->where('b.position_id', $request->position_id);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('b.first_name', 'like', "%{$search}%")
                      ->orWhere('b.last_name', 'like', "%{$search}%")
                      ->orWhere('b.employee_no', 'like', "%{$search}%");
                });
            }

            // Pagination
            $perPage = (int) $request->get('per_page', 15);
            $employees = $query->orderBy('b.last_name')
                ->orderBy('b.first_name')
                ->paginate($perPage);

            // Transform data for frontend Employee_Data_Populate
            $transformedData = $employees->getCollection()->map(function ($item) {
                return [
                    'id' => $item->employee_id,
                    'employee_id' => $item->employee_id,
                    'name' => $item->employee_name,
                    'employee_no' => $item->employee_no,
                    'photo' => $item->photo ?? null,
                    'department' => $item->department_name ?? 'N/A',
                    'department_name' => $item->department_name ?? 'N/A',
                    'position' => $item->position_name ?? 'N/A',
                    'position_name' => $item->position_name ?? 'N/A',
                    'branch' => $item->branch_name ?? 'N/A'
                ];
            });

            return response()->json([
                'data' => $transformedData,
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching tardiness reports: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get individual tardiness report details
     */
    public function show($id): JsonResponse
    {
        try {
            $app_key = env("APP_KEY", "");
            
            $timeData = DB::table('time_data as a')
                ->leftJoin('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->leftJoin('branches as d', 'b.branch_id', '=', 'd.id')
                ->leftJoin('positions as e', 'b.position_id', '=', 'e.id')
                ->leftJoin('employment_types as f', 'b.employment_type_id', '=', 'f.id')
                ->select([
                    'a.id',
                    'a.employee_id',
                    'a.date',
                    'a.am_in',
                    'a.am_out',
                    'a.break_in',
                    'a.break_out',
                    'a.pm_in',
                    'a.pm_out',
                    'a.work_hours',
                    'a.late',
                    'a.undertime',
                    'a.absent',
                    'a.is_holiday',
                    'a.is_ob',
                    'a.is_ot',
                    'a.remarks',
                    'a.created_at',
                    'a.updated_at',
                    // Original (decrypting) employee_name expression kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                    //            CONCAT(
                    //                RTRIM(b.first_name),' ',
                    //                CASE
                    //                    WHEN COALESCE(LTRIM(RTRIM(b.middle_name)),'') = '' THEN ''
                    //                    ELSE CONCAT(SUBSTRING(b.middle_name,1,1),'. ')
                    //                END,
                    //                RTRIM(b.last_name)
                    //            )
                    //         ELSE
                    //            CONCAT(
                    //                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')),' ',
                    //                CASE
                    //                    WHEN COALESCE(LTRIM(RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key'))), '') = '' THEN ''
                    //                    ELSE CONCAT(UPPER(SUBSTRING([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1)),'. ')
                    //                END,
                    //                RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                    //            )
                    //         END as employee_name"),
                    DB::raw("CONCAT(
                               RTRIM(b.first_name),' ',
                               CASE
                                   WHEN COALESCE(LTRIM(RTRIM(b.middle_name)),'') = '' THEN ''
                                   ELSE CONCAT(SUBSTRING(b.middle_name,1,1),'. ')
                               END,
                               RTRIM(b.last_name)
                            ) as employee_name"),
                    'c.name as department_name',
                    'd.name as branch_name',
                    'e.name as position_name',
                    'f.name as employment_type_name'
                ])
                ->where('a.id', $id)
                ->first();

            if (!$timeData) {
                return response()->json(['message' => 'Time data not found'], 404);
            }

            $transformedData = [
                'id' => $timeData->id,
                'employee_id' => $timeData->employee_id,
                'employee_name' => $timeData->employee_name,
                'department' => $timeData->department_name ?? 'N/A',
                'branch' => $timeData->branch_name ?? 'N/A',
                'position' => $timeData->position_name ?? 'N/A',
                'employment_type' => $timeData->employment_type_name ?? 'N/A',
                'date' => $timeData->date,
                'am_in' => $timeData->am_in,
                'am_out' => $timeData->am_out,
                'break_in' => $timeData->break_in,
                'break_out' => $timeData->break_out,
                'pm_in' => $timeData->pm_in,
                'pm_out' => $timeData->pm_out,
                'work_hours' => $timeData->work_hours,
                'late' => $timeData->late,
                'undertime' => $timeData->undertime,
                'absent' => $timeData->absent,
                'is_holiday' => $timeData->is_holiday,
                'is_ob' => $timeData->is_ob,
                'is_ot' => $timeData->is_ot,
                'remarks' => $timeData->remarks,
                'created_at' => $timeData->created_at,
                'updated_at' => $timeData->updated_at
            ];

            return response()->json($transformedData);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching tardiness report details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export tardiness reports
     */
    public function export(Request $request)
    {
        try {
            $format = $request->get('format', 'pdf');
            
            // Get filtered data (same logic as index method)
            $query = TimeData::with(['employee', 'employee.department', 'employee.branch'])
                ->select([
                    'time_data.*',
                    'employees.first_name',
                    'employees.last_name',
                    'employees.middle_name',
                    'departments.name as department_name',
                    'branches.name as branch_name'
                ])
                ->leftJoin('employees', 'time_data.employee_id', '=', 'employees.id')
                ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
                ->leftJoin('branches', 'employees.branch_id', '=', 'branches.id');

            // Apply same filters as index method
            if ($request->filled('date_from')) {
                $query->where('time_data.date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('time_data.date', '<=', $request->date_to);
            }

            if ($request->filled('employee_id')) {
                $query->where('time_data.employee_id', $request->employee_id);
            }

            if ($request->filled('department_id')) {
                $query->where('employees.department_id', $request->department_id);
            }


            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('employees.first_name', 'like', "%{$search}%")
                      ->orWhere('employees.last_name', 'like', "%{$search}%")
                      ->orWhere('employees.employee_id', 'like', "%{$search}%");
                });
            }

            $query->where('time_data.late', '>', 0);

            $tardinessData = $query->orderBy('time_data.date', 'desc')->get();

            // Transform data for export
            $exportData = $tardinessData->map(function ($item) {
                return [
                    'Employee ID' => $item->employee_id,
                    'Employee Name' => trim($item->first_name . ' ' . $item->last_name),
                    'Department' => $item->department_name ?? 'N/A',
                    'Branch' => $item->branch_name ?? 'N/A',
                    'Date' => $item->date,
                    'Time In' => $item->am_in,
                    'Work Hours' => $item->work_hours,
                    'Tardiness (Hours)' => $item->late,
                    'Remarks' => $item->remarks
                ];
            });

            if ($format === 'excel') {
                return $this->exportToExcel($exportData);
            } else {
                return $this->exportToPdf($exportData);
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error exporting tardiness reports: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get summary statistics for tardiness reports
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $query = DB::table('time_data as a')
                ->leftJoin('employees as b', 'a.employee_id', '=', 'b.id');

            // Apply same filters as index method
            if ($request->filled('date_from')) {
                $query->where('a.date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('a.date', '<=', $request->date_to);
            }

            if ($request->filled('employee_id')) {
                $query->where('a.employee_id', $request->employee_id);
            }

            if ($request->filled('department_id')) {
                $query->where('b.department_id', $request->department_id);
            }


            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('b.first_name', 'like', "%{$search}%")
                      ->orWhere('b.last_name', 'like', "%{$search}%")
                      ->orWhere('b.employee_no', 'like', "%{$search}%");
                });
            }

            // Get summary statistics
            // For Late Report: calculate on day-by-day basis (not per employee)
            $reportType = $request->get('reportType', 'late');
            // Normalize reportType: handle 'Tardiness', 'tardiness', 'late', or default to 'late'
            $isLateReport = in_array(strtolower($reportType), ['late', 'tardiness']) || $reportType === 'Tardiness';
            
            if ($isLateReport) {
                // Count total days with work hours (day-by-day basis)
                $totalDays = (clone $query)->where('a.work_hours', '>', 0)->count();
                
                // Count days with late > 0
                $totalRecords = (clone $query)->where('a.late', '>', 0)->count();
                
                // Count distinct employees with late > 0
                $lateEmployees = (clone $query)->where('a.late', '>', 0)->distinct('a.employee_id')->count();
                
                // Average tardiness: average of ALL days (including days with 0 late) - day-by-day basis
                // This gives the average late hours per day across all days, converted to minutes
                $averageTardinessHours = $totalDays > 0 
                    ? (clone $query)->where('a.work_hours', '>', 0)->avg('a.late') 
                    : 0;
                $averageTardiness = $averageTardinessHours * 60; // Convert hours to minutes
                
                // On-Time Rate: percentage of days that were on-time (late = 0 or late <= 0) - day-by-day basis
                $onTimeDays = (clone $query)->where('a.work_hours', '>', 0)
                    ->where(function($q) {
                        $q->where('a.late', '<=', 0)
                          ->orWhereNull('a.late');
                    })->count();
                $onTimeRate = $totalDays > 0 ? ($onTimeDays / $totalDays) * 100 : 0;
            } else {
                // For other report types, keep original logic
                $totalRecords = $query->where('a.late', '>', 0)->count();
                $lateEmployees = $query->where('a.late', '>', 0)->distinct('a.employee_id')->count();
                $averageTardiness = $query->where('a.late', '>', 0)->avg('a.late');
                $onTimeRate = $this->calculateOnTimeRate($request);
            }

            return response()->json([
                'data' => [
                    'total_records' => $totalRecords,
                    'late_employees' => $lateEmployees,
                    'average_tardiness' => round($averageTardiness, 2),
                    'on_time_rate' => round($onTimeRate, 2)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching summary data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate on-time rate
     */
    private function calculateOnTimeRate(Request $request): float
    {
        $query = DB::table('time_data as a')
            ->leftJoin('employees as b', 'a.employee_id', '=', 'b.id');

        // Apply same filters
        if ($request->filled('date_from')) {
            $query->where('a.date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('a.date', '<=', $request->date_to);
        }

        if ($request->filled('employee_id')) {
            $query->where('a.employee_id', $request->employee_id);
        }

        if ($request->filled('department_id')) {
            $query->where('b.department_id', $request->department_id);
        }

        if ($request->filled('branch_id')) {
            $query->where('b.branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('b.first_name', 'like', "%{$search}%")
                  ->orWhere('b.last_name', 'like', "%{$search}%")
                  ->orWhere('b.employee_no', 'like', "%{$search}%");
            });
        }

        $totalDays = $query->where('a.work_hours', '>', 0)->count();
        $onTimeDays = $query->where('a.work_hours', '>', 0)
            ->where('a.late', '<=', 0)->count();

        if ($totalDays == 0) return 0;

        return ($onTimeDays / $totalDays) * 100;
    }

    /**
     * Export to Excel
     */
    private function exportToExcel($data)
    {
        // Implementation for Excel export
        // This would typically use a package like Laravel Excel
        return response()->json(['message' => 'Excel export not implemented yet'], 501);
    }

    /**
     * Get employee tardiness data from both time_data and time_data_adj for the date range.
     * Late, Undertime and Absences Report shows data derived from both tables for the selected dates.
     */
    public function employeeTardiness($employeeId, Request $request): JsonResponse
    {
        try {
            $app_key = env("APP_KEY", "");
            $reportType = $request->get('reportType', 'late');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $selectTimeData = [
                'a.id',
                'a.employee_id',
                'a.date',
                'a.am_in',
                'a.am_out',
                'a.break_in',
                'a.break_out',
                'a.pm_in',
                'a.pm_out',
                'a.work_hours',
                'a.late',
                'a.undertime',
                'a.absent',
                'a.is_holiday',
                'a.is_ob',
                'a.is_ot',
                'a.remarks',
                'a.created_at',
                'a.updated_at',
                // Original (decrypting) employee_name expression kept for reference:
                // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                //          CONCAT(
                //              RTRIM(b.first_name),' ',
                //              CASE
                //                  WHEN COALESCE(LTRIM(RTRIM(b.middle_name)),'') = '' THEN ''
                //                  ELSE CONCAT(SUBSTRING(b.middle_name,1,1),'. ')
                //              END,
                //              RTRIM(b.last_name)
                //          )
                //       ELSE
                //          CONCAT(
                //              RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')),' ',
                //              CASE
                //                  WHEN COALESCE(LTRIM(RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key'))), '') = '' THEN ''
                //                  ELSE CONCAT(UPPER(SUBSTRING([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1)),'. ')
                //              END,
                //              RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                //          )
                //       END as employee_name"),
                DB::raw("CONCAT(
                           RTRIM(b.first_name),' ',
                           CASE
                               WHEN COALESCE(LTRIM(RTRIM(b.middle_name)),'') = '' THEN ''
                               ELSE CONCAT(SUBSTRING(b.middle_name,1,1),'. ')
                           END,
                           RTRIM(b.last_name)
                        ) as employee_name"),
                'b.employee_no',
                'c.name as department_name',
                'd.name as position_name'
            ];

            // 1) Records from time_data
            $queryTd = DB::table('time_data as a')
                ->leftJoin('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->leftJoin('positions as d', 'b.position_id', '=', 'd.id')
                ->where('a.employee_id', $employeeId);

            if ($dateFrom) {
                $queryTd->where('a.date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $queryTd->where('a.date', '<=', $dateTo);
            }

            if ($reportType === 'absences') {
                $queryTd->where(function ($q) {
                    $q->where('a.absent', '>', 0)->orWhere('a.absent', '=', 1)->orWhere('a.absent', '=', 1.00);
                });
            } elseif ($reportType === 'undertime') {
                $queryTd->where('a.undertime', '>', 0);
            } elseif ($reportType !== 'combined') {
                $queryTd->where('a.late', '>', 0);
            }

            $rowsTd = $queryTd->select($selectTimeData)->orderBy('a.date', 'desc')->get();

            $allRows = $rowsTd->map(function ($item) {
                return (object) [
                    'id' => $item->id,
                    'employee_id' => $item->employee_id,
                    'date' => $item->date,
                    'am_in' => $item->am_in,
                    'am_out' => $item->am_out,
                    'break_in' => $item->break_in,
                    'break_out' => $item->break_out,
                    'pm_in' => $item->pm_in,
                    'pm_out' => $item->pm_out,
                    'work_hours' => $item->work_hours,
                    'late' => $item->late,
                    'undertime' => $item->undertime,
                    'absent' => $item->absent,
                    'is_holiday' => $item->is_holiday,
                    'is_ob' => $item->is_ob,
                    'is_ot' => $item->is_ot,
                    'remarks' => $item->remarks,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'employee_name' => $item->employee_name,
                    'employee_no' => $item->employee_no,
                    'department_name' => $item->department_name,
                    'position_name' => $item->position_name,
                    'source' => 'time_data',
                ];
            });

            // 2) Records from time_data_adj (same date range)
            if (Schema::hasTable('time_data_adj')) {
                $queryAdj = DB::table('time_data_adj as a')
                    ->leftJoin('employees as b', 'a.employee_id', '=', 'b.id')
                    ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                    ->leftJoin('positions as d', 'b.position_id', '=', 'd.id')
                    ->where('a.employee_id', $employeeId);

                if ($dateFrom) {
                    $queryAdj->where('a.date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $queryAdj->where('a.date', '<=', $dateTo);
                }

                if ($reportType === 'absences') {
                    $queryAdj->where(function ($q) {
                        $q->where('a.absent', '>', 0)->orWhere('a.absent', '=', 1)->orWhere('a.absent', '=', 1.00);
                    });
                } elseif ($reportType === 'undertime') {
                    $queryAdj->where('a.undertime', '>', 0);
                } elseif ($reportType !== 'combined') {
                    $queryAdj->where('a.late', '>', 0);
                }

                $selectAdj = [
                    'a.id',
                    'a.employee_id',
                    'a.date',
                    'a.am_in',
                    'a.am_out',
                    'a.break_in',
                    'a.break_out',
                    'a.pm_in',
                    'a.pm_out',
                    'a.work_hours',
                    'a.late',
                    'a.undertime',
                    'a.absent',
                    'a.is_holiday',
                    'a.is_ob',
                    'a.is_ot',
                    'a.remarks',
                    'a.created_at',
                    'a.updated_at',
                    'a.source_time_data_id',
                    // Original (decrypting) employee_name expression kept for reference:
                    // DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                    //          CONCAT(
                    //              RTRIM(b.first_name),' ',
                    //              CASE
                    //                  WHEN COALESCE(LTRIM(RTRIM(b.middle_name)),'') = '' THEN ''
                    //                  ELSE CONCAT(SUBSTRING(b.middle_name,1,1),'. ')
                    //              END,
                    //              RTRIM(b.last_name)
                    //          )
                    //       ELSE
                    //          CONCAT(
                    //              RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')),' ',
                    //              CASE
                    //                  WHEN COALESCE(LTRIM(RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key'))), '') = '' THEN ''
                    //                  ELSE CONCAT(UPPER(SUBSTRING([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1)),'. ')
                    //              END,
                    //              RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                    //          )
                    //       END as employee_name"),
                    DB::raw("CONCAT(
                               RTRIM(b.first_name),' ',
                               CASE
                                   WHEN COALESCE(LTRIM(RTRIM(b.middle_name)),'') = '' THEN ''
                                   ELSE CONCAT(SUBSTRING(b.middle_name,1,1),'. ')
                               END,
                               RTRIM(b.last_name)
                           ) as employee_name"),
                    'b.employee_no',
                    'c.name as department_name',
                    'd.name as position_name'
                ];

                $rowsAdj = $queryAdj->select($selectAdj)->orderBy('a.date', 'desc')->get();

                foreach ($rowsAdj as $item) {
                    $allRows->push((object) [
                        'id' => 'adj_' . $item->id,
                        'employee_id' => $item->employee_id,
                        'date' => $item->date,
                        'am_in' => $item->am_in,
                        'am_out' => $item->am_out,
                        'break_in' => $item->break_in,
                        'break_out' => $item->break_out,
                        'pm_in' => $item->pm_in,
                        'pm_out' => $item->pm_out,
                        'work_hours' => $item->work_hours,
                        'late' => $item->late,
                        'undertime' => $item->undertime,
                        'absent' => $item->absent,
                        'is_holiday' => $item->is_holiday,
                        'is_ob' => $item->is_ob,
                        'is_ot' => $item->is_ot,
                        'remarks' => $item->remarks,
                        'created_at' => $item->created_at,
                        'updated_at' => $item->updated_at,
                        'employee_name' => $item->employee_name,
                        'employee_no' => $item->employee_no,
                        'department_name' => $item->department_name,
                        'position_name' => $item->position_name,
                        'source' => 'time_data_adj',
                        'source_time_data_id' => $item->source_time_data_id ?? null,
                    ]);
                }
            }

            // Merge by date: when time_data_adj exists for a date (is_adjusted / source_time_data_id), use that row
            // so the report shows one row per date with complete am_in, am_out, work_hours from time_data_adj
            $byDate = [];
            foreach ($allRows as $row) {
                $dateKey = $row->date;
                $byDate[$dateKey] = $row;
            }
            // Prefer time_data_adj: first add time_data rows, then overwrite with time_data_adj so adjusted wins
            $merged = collect($byDate)->sortByDesc(function ($row) {
                return $row->date;
            })->values();

            $transformedData = $merged->map(function ($item) {
                $out = [
                    'id' => $item->id,
                    'employee_id' => $item->employee_id,
                    'name' => $item->employee_name,
                    'employee_no' => $item->employee_no,
                    'department' => $item->department_name ?? 'N/A',
                    'department_name' => $item->department_name ?? 'N/A',
                    'position' => $item->position_name ?? 'N/A',
                    'position_name' => $item->position_name ?? 'N/A',
                    'date' => $item->date,
                    'am_in' => $item->am_in,
                    'am_out' => $item->am_out,
                    'break_in' => $item->break_in,
                    'break_out' => $item->break_out,
                    'pm_in' => $item->pm_in,
                    'pm_out' => $item->pm_out,
                    'work_hours' => $item->work_hours,
                    'late' => $item->late,
                    'undertime' => $item->undertime,
                    'absent' => $item->absent,
                    'is_holiday' => $item->is_holiday,
                    'is_ob' => $item->is_ob,
                    'is_ot' => $item->is_ot,
                    'remarks' => $item->remarks ?: 'None',
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'source' => $item->source ?? 'time_data',
                ];
                if (isset($item->source_time_data_id)) {
                    $out['source_time_data_id'] = $item->source_time_data_id;
                    $out['is_adjusted'] = ! empty($item->source_time_data_id);
                }
                return $out;
            });

            return response()->json($transformedData);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching employee tardiness data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export to PDF
     */
    private function exportToPdf($data)
    {
        // Implementation for PDF export
        // This would typically use a package like DomPDF or TCPDF
        return response()->json(['message' => 'PDF export not implemented yet'], 501);
    }
}
