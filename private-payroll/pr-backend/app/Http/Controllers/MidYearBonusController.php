<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Auth;
use App\Audit;
use App\Support\PayrollBenefitsEmployeeScope;
use App\Support\ReportDivisionFilter;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MidYearBonusController extends Controller
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
            $app_key = env("APP_KEY", "");

            $branches = DB::table('branches')->get();
            $divisions = ReportDivisionFilter::activeDivisions();
            
            // Get employee options for signatory dropdown (all employees initially)
            $employees = DB::table('employees as e')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->select(
                    'e.id',
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            CONCAT(RTRIM(e.last_name), ', ', RTRIM(e.first_name), ' ', LEFT(RTRIM(e.middle_name), 1))
                        ELSE
                            CONCAT(
                                RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')), ', ',
                                RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')), ' ',
                                LEFT(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')), 1)
                            )
                        END as name"),
                    'p.name as position'
                )
                ->where([
                    'e.active' => true,
                    'e.is_employee' => true,
                ]);
            PayrollBenefitsEmployeeScope::apply($employees, 'e');
            $employees = $employees->orderBy('name', 'asc')->get();
            
            // Return empty array initially - no mid-year bonus records until processed
            $midyear_records = collect([]);
            return $this->successResponse([
                'branches' => $branches,
                'midyear_records' => $midyear_records,
                'divisions' => $divisions,
                'departments' => $divisions,
                'employee_options' => $employees
            ], 'Mid-year bonus data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve mid-year bonus data: ' . $e->getMessage());
        }
    }

    public function getRecords(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'branch_id' => 'nullable|integer|exists:branches,id',
                'department_id' => 'required',
                'years' => 'required|integer|min:2000|max:2100'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");
            $isAllDepartments = $request->department_id === 'all';
            $divisionId = $isAllDepartments
                ? null
                : (ReportDivisionFilter::resolveId($request) ?? (int) $request->department_id);
            $year = (int) $request->years;
            $dateFrom = ($year - 1) . '-07-01';
            $dateTo = $year . '-05-15';

            $attendanceSub = DB::table('time_data as td')
                ->leftJoin('holidays as h', function ($j) {
                    $j->on('td.holiday_id', '=', 'h.id')->where('td.is_holiday', '=', 1);
                })
                ->leftJoin('holiday_types as ht', 'h.holiday_type', '=', 'ht.id')
                ->whereBetween('td.date', [$dateFrom, $dateTo])
                ->groupBy('td.employee_id')
                ->select(
                    'td.employee_id',
                    DB::raw("COUNT(CASE
                        WHEN td.work_hours > 0 THEN 1
                        WHEN td.leave > 0 THEN 1
                        WHEN td.is_ob = 1 THEN 1
                        WHEN td.is_holiday = 1 AND ISNULL(ht.absent_with_pay, 0) = 1 THEN 1
                    END) as days_present")
                );

            $query = DB::table('midyear_bonus as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('divisions as div', 'div.id', '=', 'b.division_id')
                ->leftJoin('departments as c', 'c.id', '=', 'b.department_id')
                ->join('positions as d', 'd.id', '=', 'b.position_id')
                ->leftJoinSub($attendanceSub, 'att', function ($join) {
                    $join->on('b.id', '=', 'att.employee_id');
                })
                ->select(
                    'a.id',
                    'a.employee_id',
                    'a.branch_id',
                    'a.department_id',
                    'b.division_id',
                    'a.years',
                    'a.salary',
                    'a.bonus_amount',
                    DB::raw('ISNULL(att.days_present, 0) as days_present'),
                    'b.photo',
                    'b.employee_no',
                    DB::raw('COALESCE(div.name, c.name) as department'),
                    DB::raw('COALESCE(div.name, c.name) as division'),
                    'd.name as position',
                    DB::raw("CASE
                        WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                            CONCAT(b.last_name, ', ', b.first_name, ' ', LEFT(b.middle_name, 1))
                        ELSE
                            CONCAT(RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')), ', ',
                                   RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')), ' ',
                                   LEFT(RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key')), 1))
                        END as name"),
                    DB::raw("CASE 
                        WHEN a.bonus_amount > 0 THEN 
                            CONCAT(ROUND((a.bonus_amount / a.salary) * 100, 2), '%')
                        ELSE '0%'
                        END as period_earned")
                )
                ->where([
                    'a.years' => $request->years,
                    'b.active' => true,
                    'b.is_employee' => true
                ]);

            if (!empty($request->branch_id)) {
                $query->where('a.branch_id', $request->branch_id);
            }

            if (!$isAllDepartments && $divisionId) {
                $legacyIds = ReportDivisionFilter::legacyHeaderDepartmentIdsForDivision($divisionId);
                $query->where(function ($q) use ($divisionId, $legacyIds) {
                    $q->whereIn('a.department_id', $legacyIds)
                        ->orWhere('b.division_id', $divisionId);
                });
            }

            $midyear_records = $query->orderBy('name', 'asc')->get();

            return $this->successResponse([
                'midyear_records' => $midyear_records
            ], 'Mid-year bonus records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve mid-year bonus records: ' . $e->getMessage());
        }
    }

    /**
     * Update a single mid-year bonus record's bonus_amount.
     */
    public function updateRecord(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'bonus_amount' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            if (!$request->has('bonus_amount')) {
                return $this->successResponse(null, 'No changes to apply.');
            }

            $exists = DB::table('midyear_bonus')->where('id', $id)->exists();
            if (!$exists) {
                return $this->errorResponse('Mid-year bonus record not found.', 404);
            }

            DB::table('midyear_bonus')->where('id', $id)->update([
                'bonus_amount' => (float) $request->bonus_amount,
            ]);

            return $this->successResponse(null, 'Mid-year bonus record updated successfully.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update mid-year bonus record: ' . $e->getMessage());
        }
    }

    /**
     * Mid-year bonus: one month basic pay for personnel with at least 4 months
     * (120 days) from July 1 previous year to May 15 current year.
     * days_present = work_hours>0 OR leave>0 OR is_ob=1 OR (is_holiday AND absent_with_pay).
     * No midyear_table lookup.
     */
    public function process(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'branch_id' => 'nullable|integer|exists:branches,id',
                'department_id' => 'required',
                'years' => 'required|integer|min:2000|max:2100'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $departmentId = $request->department_id;
            $year = (int) $request->years;

            $resolveBranchIds = function (int $divisionId) use ($request) {
                if (!empty($request->branch_id)) {
                    return collect([(int) $request->branch_id]);
                }

                return DB::table('employees')
                    ->where([
                        'active' => true,
                        'is_employee' => true,
                        'division_id' => $divisionId,
                    ])
                    ->whereNotNull('branch_id')
                    ->distinct()
                    ->orderBy('branch_id', 'asc')
                    ->pluck('branch_id');
            };

            if ($departmentId === 'all') {
                $divisions = ReportDivisionFilter::activeDivisions();
                foreach ($divisions as $division) {
                    $branchIds = $resolveBranchIds((int) $division->id);
                    foreach ($branchIds as $branchId) {
                        $this->processDepartment(
                            (int) $branchId,
                            (int) $division->id,
                            $year
                        );
                    }
                }
                return $this->successResponse(null, 'Successfully Generated 14th Month Pay for all divisions!');
            }

            $divisionId = ReportDivisionFilter::resolveId($request) ?? (int) $departmentId;
            $branchIds = $resolveBranchIds($divisionId);
            $processedAny = false;
            foreach ($branchIds as $branchId) {
                $result = $this->processDepartment(
                    (int) $branchId,
                    $divisionId,
                    $year
                );
                $processedAny = $processedAny || (bool) $result;
            }

            if (!$processedAny) {
                return $this->errorResponse('No Employee to process 14th Month Pay.', 400);
            }

            return $this->successResponse(null, 'Successfully Generated 14th Month Pay!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process mid-year bonus: ' . $e->getMessage());
        }
    }

    /**
     * Process mid-year bonus for a single department.
     *
     * @param int $branchId
     * @param int $divisionId divisions.id (stored in legacy department_id columns)
     * @param int $year
     * @return bool true if at least one employee was processed
     */
    protected function processDepartment($branchId, $divisionId, $year)
    {
        $dateFrom = ($year - 1) . '-07-01';
        $dateTo = $year . '-05-15';

        $midyear_header = DB::table('midyear_bonus_header')->where([
            'year_id' => $year,
            'branch_id' => $branchId,
            'department_id' => $divisionId
        ])->get();

        $header_id = $midyear_header->isNotEmpty()
            ? $midyear_header[0]->id
            : (int) DB::table('midyear_bonus_header')->max('id') + 1;

        $attendanceSub = DB::table('time_data as td')
            ->leftJoin('holidays as h', function ($j) {
                $j->on('td.holiday_id', '=', 'h.id')->where('td.is_holiday', '=', 1);
            })
            ->leftJoin('holiday_types as ht', 'h.holiday_type', '=', 'ht.id')
            ->whereBetween('td.date', [$dateFrom, $dateTo])
            ->groupBy('td.employee_id')
            ->select(
                'td.employee_id',
                DB::raw("COUNT(CASE
                    WHEN td.work_hours > 0 THEN 1
                    WHEN td.leave > 0 THEN 1
                    WHEN td.is_ob = 1 THEN 1
                    WHEN td.is_holiday = 1 AND ISNULL(ht.absent_with_pay, 0) = 1 THEN 1
                END) as days_present")
            );

        $employees = DB::table('employees as a')
            ->leftJoinSub($attendanceSub, 'att', function ($join) {
                $join->on('a.id', '=', 'att.employee_id');
            })
            ->select(
                'a.id',
                'a.first_name',
                'a.last_name',
                'a.salary',
                DB::raw('ISNULL(att.days_present, 0) as days_present')
            )
            ->where([
                'a.active' => true,
                'a.is_employee' => true,
                'a.branch_id' => $branchId,
                'a.division_id' => $divisionId,
            ])
            ->get();

        if ($employees->isEmpty()) {
            return false;
        }

        foreach ($employees as $emp) {
            $daysPresent = (int) ($emp->days_present ?? 0);
            $bonusAmount = $daysPresent >= 120 ? (float) ($emp->salary ?? 0) : 0.0;

            $midyear_bonus = [
                'employee_id' => $emp->id,
                'branch_id' => $branchId,
                'department_id' => $divisionId,
                'years' => $year,
                'salary' => $emp->salary,
                'bonus_amount' => $bonusAmount,
                'midyear_header_id' => $header_id
            ];

            DB::table('midyear_bonus')->updateOrInsert(
                [
                    'employee_id' => $emp->id,
                    'branch_id' => $branchId,
                    'years' => $year
                ],
                $midyear_bonus
            );
        }

        DB::table('midyear_bonus_header')->updateOrInsert([
            'branch_id' => $branchId,
            'department_id' => $divisionId,
            'year_id' => $year
        ], [
            'branch_id' => $branchId,
            'department_id' => $divisionId,
            'year_id' => $year,
            'posted' => false
        ]);

        return true;
    }

    public function post(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'branch_id' => 'required|exists:branches,id',
                'department_id' => 'required|exists:departments,id',
                'years' => 'required|integer|min:2000|max:2100'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $midyear_header = DB::table('midyear_bonus_header')->where([
                'year_id' => $request->years,
                'branch_id' => $request->branch_id,
                'department_id' => $request->department_id
            ])
                ->get();
            if ($midyear_header->isNotEmpty()) {
                $header_id = $midyear_header[0]->id;
                DB::table('midyear_bonus_header')->where([
                    'branch_id' => $request->branch_id,
                    'department_id' => $request->department_id,
                    'year_id' => $request->years
                ])->update(['posted' => true]);
            } else {
                $header_id = DB::table('midyear_bonus_header')->max('id');
                $header_id = $header_id + 1;
            }

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module' => 'Payroll Module',
                'menu' => 'Mid Year Bonus',
                'activity' => 'Posted',
                'description' => 'Posted Mid Year Bonus.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Successfully Posted 14th Month Pay!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to post mid-year bonus: ' . $e->getMessage());
        }
    }

    /**
     * Delete a single mid-year bonus record.
     *
     * This deletes the detail row from midyear_bonus as long as the
     * corresponding header (if any) is not posted.
     */
    public function destroy($id)
    {
        try {
            $record = DB::table('midyear_bonus')->where('id', $id)->first();

            if (!$record) {
                return $this->errorResponse('Mid-year bonus record not found.', 404);
            }

            // Prevent deleting records that belong to a posted header
            if (!empty($record->midyear_header_id)) {
                $header = DB::table('midyear_bonus_header')
                    ->where('id', $record->midyear_header_id)
                    ->first();

                if ($header && !empty($header->posted)) {
                    return $this->errorResponse('Cannot delete record from a posted mid-year bonus.', 400);
                }
            }

            DB::table('midyear_bonus')->where('id', $id)->delete();

            // Optionally clean up header when it has no more detail rows
            if (!empty($record->midyear_header_id)) {
                $remaining = DB::table('midyear_bonus')
                    ->where('midyear_header_id', $record->midyear_header_id)
                    ->count();

                if ($remaining === 0) {
                    DB::table('midyear_bonus_header')
                        ->where('id', $record->midyear_header_id)
                        ->delete();
                }
            }

            $data_audit = [
                'user_id' => Auth::user()->id,
                'module' => 'Payroll Module',
                'menu' => 'Mid Year Bonus',
                'activity' => 'Deleted',
                'description' => 'Deleted mid-year bonus record for employee ID ' . $record->employee_id . ' (year ' . $record->years . ').',
            ];

            Audit::create($data_audit);

            return $this->successResponse(null, 'Mid-year bonus record deleted successfully.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete mid-year bonus record: ' . $e->getMessage());
        }
    }

    public function report(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            $divisions = DB::table('midyear_bonus as a')
                ->join('divisions as b', 'a.department_id', '=', 'b.id')
                ->select(
                    'a.department_id as id',
                    'b.name'
                )
                ->distinct()
                ->get();

            $years = DB::table('midyear_bonus as a')
                ->select(
                    'a.years'
                )
                ->distinct()
                ->orderBy('years', 'asc')
                ->get();

            $signatories = DB::table('payroll_signatories')
                ->where(['branch_id' => $request->branch_id, 'report_name' => "Mid Year Bonus"])
                ->get();

            // Get employee options for signatory dropdown
            $employees = DB::table('employees as e')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->select(
                    'e.id',
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            CONCAT(RTRIM(e.last_name), ', ', RTRIM(e.first_name), ' ', LEFT(RTRIM(e.middle_name), 1))
                        ELSE
                            CONCAT(
                                RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')), ', ',
                                RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')), ' ',
                                LEFT(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')), 1)
                            )
                        END as name"),
                    'p.name as position'
                )
                ->where([
                    'e.active' => true,
                    'e.is_employee' => true,
                ])
                ->when($request->branch_id, function ($query, $branchId) {
                    return $query->where('e.branch_id', $branchId);
                });
            PayrollBenefitsEmployeeScope::apply($employees, 'e');
            $employees = $employees->orderBy('name', 'asc')->get();

            return $this->successResponse([
                'divisions' => $divisions,
                'departments' => $divisions,
                'years' => $years,
                'signatories' => $signatories,
                'employee_options' => $employees
            ], 'Mid-year bonus report data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve mid-year bonus report data: ' . $e->getMessage());
        }
    }

    public function generatePdf(Request $request)
    {
        try {
            $departmentOrDivision = $request->input('department_id', $request->input('division_id'));
            $isAll = $departmentOrDivision === 'all';
            $divisionId = $isAll ? null : ReportDivisionFilter::resolveId($request);

            $validator = validator(
                array_merge($request->all(), ['department_id' => $departmentOrDivision]),
                [
                    'department_id' => [
                        'required',
                        function ($attribute, $value, $fail) {
                            if ($value === 'all') {
                                return;
                            }

                            if (DB::table('divisions')->where('id', $value)->exists()) {
                                return;
                            }

                            if (DB::table('departments')->where('id', $value)->exists()) {
                                return;
                            }

                            $fail('The selected division is invalid.');
                        },
                    ],
                    'years' => 'required|integer|min:2000|max:2100',
                    'signatory_1' => 'nullable|string|max:255',
                    'signatory_position_1' => 'nullable|string|max:255',
                    'signatory_2' => 'nullable|string|max:255',
                    'signatory_position_2' => 'nullable|string|max:255',
                ],
                [
                    'department_id.required' => 'Division is required.',
                    'years.required' => 'Year is required.',
                    'years.integer' => 'Year must be a valid number.',
                ]
            );

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $legacyDivisionIds = $isAll || !$divisionId
                ? []
                : ReportDivisionFilter::legacyHeaderDepartmentIdsForDivision($divisionId);

            $midyear = DB::table('midyear_bonus_header as a')
                ->leftjoin('midyear_bonus as b', 'a.id', '=', 'b.midyear_header_id')
                ->when(!$isAll && !empty($legacyDivisionIds), function ($query) use ($legacyDivisionIds) {
                    return $query->whereIn('a.department_id', $legacyDivisionIds);
                })
                ->get();

            $years = DB::table('midyear_bonus_header as a')
                ->select(
                    'a.id',
                    'a.year_id'
                )
                ->where('a.year_id', $request->years)
                ->orderBy('a.year_id', 'asc')
                ->get();

            $employees = DB::table('midyear_bonus as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('divisions as div', 'div.id', '=', 'b.division_id')
                ->leftJoin('departments as c', 'c.id', '=', 'b.department_id')
                ->join('positions as d', 'd.id', '=', 'b.position_id')
                ->select(
                    'a.id',
                    'a.branch_id',
                    'b.photo',
                    'b.employee_no',
                    DB::raw('COALESCE(div.name, c.name) as department'),
                    'd.name as position',
                    'a.salary',
                    'a.bonus_amount as amount',
                    DB::raw("CASE
                WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                    CONCAT(b.last_name, ', ', b.first_name, ' ', LEFT(b.middle_name, 1))
                ELSE
                    CONCAT(RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')), ', ',
                           RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')), ' ',
                           LEFT(RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key')), 1))
                END as name")
                )
                ->where([
                    'a.years' => $request->years,
                    'b.active' => true,
                    'b.is_employee' => true,
                ])
                ->when(!$isAll && !empty($legacyDivisionIds), function ($query) use ($legacyDivisionIds, $divisionId) {
                    return $query->where(function ($q) use ($legacyDivisionIds, $divisionId) {
                        $q->whereIn('a.department_id', $legacyDivisionIds)
                            ->orWhere('b.division_id', $divisionId);
                    });
                })
                ->distinct()
                ->orderBy('department', 'asc')
                ->orderBy('name', 'asc')
                ->get();

            $signatories = [
                [
                    'signatory_1' => $request->signatory_1,
                    'signatory_position_1' => $request->signatory_position_1,
                    'signatory_2' => $request->signatory_2,
                    'signatory_position_2' => $request->signatory_position_2,
                ]
            ];

            $month = $years[0]->year_id ?? $request->years;

            $department = $isAll
                ? 'All Divisions'
                : ReportDivisionFilter::displayName($divisionId);

            $pdf = PDF::loadView('process_bonus.midyear_report_print', compact('employees', 'midyear', 'department', 'signatories', 'image', 'month'))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4', 'landscape');

            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'midyear_bonus_report_' . $request->years . '_' . $department . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate mid-year bonus PDF: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        // Keep the original print method for backward compatibility
        return $this->generatePdf($request);
    }

    /**
     * Get initial data for ATM Letter for Mid-Year Bonus
     */
    public function atmLetterIndex()
    {
        try {
            $app_key = env("APP_KEY", "");

            $divisions = ReportDivisionFilter::divisionsWithMidyearBonusData();

            $years = DB::table('midyear_bonus as a')
                ->select('a.years')
                ->distinct()
                ->orderBy('years', 'desc')
                ->get();

            // Get employee options for signatory dropdown
            $employees = DB::table('employees as e')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->select(
                    'e.id',
                    'e.division_id',
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            CONCAT(RTRIM(e.last_name), ', ', RTRIM(e.first_name), ' ', LEFT(RTRIM(e.middle_name), 1))
                        ELSE
                            CONCAT(
                                RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')), ', ',
                                RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')), ' ',
                                LEFT(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')), 1)
                            )
                        END as name"),
                    'p.name as position'
                )
                ->where([
                    'e.active' => true,
                    'e.is_employee' => true,
                ]);
            PayrollBenefitsEmployeeScope::apply($employees, 'e');
            $employees = $employees->orderBy('name', 'asc')->get();

            // Default signatory and personnel information
            $defaultSignatory = [
                'signatory_name' => 'Default Signatory Name',
                'signatory_position' => 'Default Position',
                'personnel_name' => 'LandBank Personnel Name',
                'personnel_position' => 'LandBank Position'
            ];

            return $this->successResponse([
                'divisions' => $divisions,
                'departments' => $divisions,
                'years' => $years,
                'employee_options' => $employees,
                'default_signatory' => $defaultSignatory
            ], 'ATM Letter for Mid-Year Bonus data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve ATM Letter data: ' . $e->getMessage());
        }
    }

    /**
     * Generate ATM Letter in PDF format for Mid-Year Bonus
     */
    public function generateAtmLetterPdf(Request $request)
    {
        try {
            $departmentOrDivision = $request->input('department_id', $request->input('division_id'));
            $isAll = $departmentOrDivision === 'all';
            $divisionId = $isAll ? null : ReportDivisionFilter::resolveId($request);

            $validator = Validator::make(
                array_merge($request->all(), ['department_id' => $departmentOrDivision]),
                [
                    'department_id' => [
                        'required',
                        function ($attribute, $value, $fail) {
                            if ($value === 'all') {
                                return;
                            }
                            if (DB::table('divisions')->where('id', $value)->exists()) {
                                return;
                            }
                            if (DB::table('departments')->where('id', $value)->exists()) {
                                return;
                            }
                            $fail('The selected division is invalid.');
                        },
                    ],
                    'years' => 'required|integer|min:2000|max:2100',
                    'signatory_name' => 'required|string|max:255',
                    'signatory_position' => 'required|string|max:255',
                    'personnel_name' => 'required|string|max:255',
                    'personnel_position' => 'required|string|max:255',
                ],
                [
                    'department_id.required' => 'Division is required.',
                    'years.required' => 'Year is required.',
                ]
            );

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

            $legacyDivisionIds = $isAll || !$divisionId
                ? []
                : ReportDivisionFilter::legacyHeaderDepartmentIdsForDivision($divisionId);

            // Get employee data for the ATM letter
            $employees = DB::table('midyear_bonus as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('divisions as div', 'div.id', '=', 'b.division_id')
                ->leftJoin('departments as c', 'b.department_id', '=', 'c.id')
                ->select(
                    'a.bonus_amount as net_pay',
                    'a.bonus_amount',
                    'a.years',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                rtrim(CONCAT(upper(b.last_name),', ',upper(b.first_name),' ',upper(b.middle_name)))
                          ELSE
                                UPPER(RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))+', '+RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')+' '+RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key'))))
                          END as full_name"),
                    'b.account_no',
                    DB::raw('COALESCE(div.name, c.name) as department')
                )
                ->where([
                    'a.years' => $request->years,
                    'b.active' => true,
                    'b.is_employee' => true,
                ])
                ->when(!$isAll && !empty($legacyDivisionIds), function ($query) use ($legacyDivisionIds, $divisionId) {
                    return $query->where(function ($q) use ($legacyDivisionIds, $divisionId) {
                        $q->whereIn('a.department_id', $legacyDivisionIds)
                            ->orWhere('b.division_id', $divisionId);
                    });
                })
                ->orderBy('b.last_name', 'asc')
                ->get();

            if ($employees->isEmpty()) {
                return $this->errorResponse('No employee data found for the specified criteria');
            }

            $department = $isAll
                ? (object) ['name' => 'All Divisions']
                : (object) ['name' => ReportDivisionFilter::displayName($divisionId)];

            $payroll = (object)[
                'year_id' => $request->years,
                'years' => $request->years,
            ];

            $image = base64_encode(file_get_contents(public_path('/dist/img/reports/atmletterheader.png')));
            
            $footerImagePath = public_path('/dist/img/reports/atmletterfooter.png');
            $image2 = file_exists($footerImagePath) 
                ? base64_encode(file_get_contents($footerImagePath)) 
                : '';

            $signatories = [
                'signatory_name' => $request->signatory_name,
                'signatory_position' => $request->signatory_position,
                'personnel_name' => $request->personnel_name,
                'personnel_position' => $request->personnel_position
            ];

            $pdf = PDF::loadView('process_bonus.atm_midyear_print', compact(
                'employees',
                'department',
                'payroll',
                'image',
                'image2',
                'signatories'
            ))->setOptions(['defaultFont' => 'sans-serif']);

            $pdf->setPaper('A4', 'portrait');
            $pdfContent = $pdf->output();

            $filename = 'atm_letter_midyear_' . $request->department_id . '_' . $request->years . '_' . date('Y-m-d') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate ATM Letter PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generate Individual Disbursement Voucher for Mid-Year Bonus
     */
    public function generateIndividualDV(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:employees,id',
                'years' => 'required|integer|min:2000|max:2100',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

            // Get company information
            $company = DB::table('companies')->get();
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            // Get employee data
            $employee = DB::table('employees as e')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
                ->select(
                    'e.id',
                    'e.employee_no',
                    'e.department_id',
                    DB::raw("CASE
                        WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            CONCAT(RTRIM(e.last_name), ', ', RTRIM(e.first_name), ' ', LEFT(RTRIM(e.middle_name), 1))
                        ELSE
                            CONCAT(
                                RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')), ', ',
                                RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key')), ' ',
                                LEFT(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')), 1)
                            )
                        END as name"),
                    'p.name as position',
                    'd.name as department'
                )
                ->where('e.id', $request->employee_id)
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee not found', 404);
            }

            // Get mid-year bonus amount for the employee
            $midyearBonus = DB::table('midyear_bonus')
                ->where([
                    'employee_id' => $request->employee_id,
                    'years' => $request->years
                ])
                ->first();

            if (!$midyearBonus) {
                return $this->errorResponse('No mid-year bonus record found for this employee and year', 404);
            }

            $bonusYear = $request->years;
            $amount = $midyearBonus->bonus_amount ?? 0;
            $current_date = date('F d, Y');

            // Get signatories from request or use defaults
            $signatories = [
                'certifying_officer_name' => $request->certifying_officer_name ?? 'MARIA ANTONIETTE S. ZOILO',
                'certifying_officer_position' => $request->certifying_officer_position ?? 'Administrative Officer V',
                'accountant_name' => $request->accountant_name ?? 'GERALENE Q. NADELA',
                'accountant_position' => $request->accountant_position ?? 'Accountant III',
                'accountant_role' => $request->accountant_role ?? 'Head, Accounting Unit/Authorized Representative',
                'approving_officer_name' => $request->approving_officer_name ?? 'CLARE MARI S. TORRALBA',
                'approving_officer_position' => $request->approving_officer_position ?? 'Executive Director',
                'approving_officer_role' => $request->approving_officer_role ?? 'Agency Head/Authorized Representative',
            ];

            $pdf = PDF::loadView('disbursement_vouchers.individual_DV_report', compact(
                'employee',
                'bonusYear',
                'amount',
                'company',
                'image',
                'signatories',
                'current_date'
            ))->setOptions(['defaultFont' => 'sans-serif']);

            $pdf->setPaper('A4', 'portrait');
            $pdfContent = $pdf->output();

            $filename = 'individual_dv_midyear_' . $employee->employee_no . '_' . $bonusYear . '_' . date('Y-m-d') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate Individual DV: ' . $e->getMessage());
        }
    }
}
