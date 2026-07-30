<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollPeriodController extends Controller
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
            $periods = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.payroll_interval_id',
                    'b.name as payroll_interval',
                    'c.name as payroll_cutoff',
                    DB::raw("CONCAT(DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date)) as payroll_date"),
                    'a.attendance_start_date',
                    'a.attendance_end_date',
                    'a.payroll_start_date',
                    'a.payroll_end_date',
                    'a.release_date',
                    DB::raw("case when a.active = 'true' then 'YES' else 'NO' end as active"),
                    DB::raw("case when a.posted = 'true' then 'YES' else 'NO' end as posted"),
                    'a.created_at',
                    'a.updated_at'
                )
                ->orderBy('a.attendance_start_date', 'asc')
                ->get();

            if ($periods->isEmpty()) {
                return $this->successResponse([], 'Payroll periods retrieved successfully');
            }

            // Attach employment types for each period
            foreach ($periods as $period) {
                $employmentTypes = DB::table('payroll_period_Etype as ppe')
                    ->join('employment_types as et', 'ppe.employmenttype_id', '=', 'et.id')
                    ->where('ppe.payrollperiod_id', $period->id)
                    ->select('et.id', 'et.name')
                    ->get();
                $period->employment_types = $employmentTypes;
            }

            // Group by interval + payroll month (month of attendance_end_date) + employment-type set
            $grouped = $periods
                ->groupBy(function ($period) {
                    $dateForMonth = $period->release_date ?? $period->attendance_end_date;
                    $monthKey = Carbon::parse($dateForMonth)->format('Y-m');
                    $etypeKey = collect($period->employment_types ?? [])
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->sort()
                        ->implode(',');
                    return implode('|', [(int) $period->payroll_interval_id, $monthKey, $etypeKey]);
                })
                ->values()
                ->map(function ($group) {
                    $first = $group->first();
                    $dateForLabel = $first->release_date ?? $first->attendance_end_date;
                    $monthLabel = Carbon::parse($dateForLabel)->format('F Y');
                    $periodsArray = $group->sortBy('attendance_start_date')->values()->all();
                    $anyActive = $group->contains(fn ($p) => $p->active === 'YES' || $p->active === true);
                    $allPosted = $group->every(fn ($p) => $p->posted === 'YES' || $p->posted === true);
                    return (object) [
                        'id' => $first->id,
                        'label' => ($first->payroll_interval ?? 'Payroll') . ' (' . $monthLabel . ')',
                        'attendance_start_date' => $group->min('attendance_start_date'),
                        'attendance_end_date' => $group->max('attendance_end_date'),
                        'release_date' => $group->max('release_date'),
                        'periods' => $periodsArray,
                        'employment_types' => $first->employment_types ?? [],
                        'active' => $anyActive ? 'YES' : 'NO',
                        'posted' => $allPosted ? 'YES' : 'NO',
                    ];
                })
                ->sortByDesc('release_date')
                ->values()
                ->all();

            return $this->successResponse($grouped, 'Payroll periods retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve payroll periods: ' . $e->getMessage());
        }
    }

    public function add(Request $request, $id)
    {
        try {
            $intervals = DB::table('payroll_intervals')->where('active', true)->get();
            $cutoffs = DB::table('payroll_cutoffs')->get();

            if ($id == 0) {
                $dummy_period = array(
                    'id' => 0,
                    'payroll_interval_id' => 0,
                    'payroll_cutoff_id' => 0,
                    'attendance_start_date' => null,
                    'attendance_end_date' => null,
                    'payroll_start_date' => null,
                    'payroll_end_date' => null,
                    'release_date' => null,
                    'posted' => null,
                    'active' => null
                );

                $period = (object)$dummy_period;
                $period = collect([$period]);
                $release_date = null;
            } else {
                $period = DB::table('payroll_periods')
                    ->select(
                        'id',
                        'payroll_interval_id',
                        'payroll_cutoff_id',
                        'attendance_start_date',
                        'attendance_end_date',
                        'payroll_start_date',
                        'payroll_end_date',
                        'release_date',
                        'posted',
                        'active',
                        'created_at',
                        'updated_at'
                    )
                    ->where('id', $id)->get();

                if ($period->isEmpty()) {
                    return $this->notFoundResponse('Payroll period not found');
                }

                // Load employment types for editing
                $employmentTypeIds = DB::table('payroll_period_Etype')
                    ->where('payrollperiod_id', $id)
                    ->pluck('employmenttype_id')
                    ->map(function ($id) {
                        return (int) $id;
                    })
                    ->values()
                    ->toArray();

                // Add employment_type_ids to period data
                $period[0]->employment_type_ids = $employmentTypeIds;

                // Debug: Log the period data being returned
                Log::info('Payroll Period Data for Edit:', [
                    'id' => $id,
                    'period_data' => $period[0],
                    'active_value' => $period[0]->active,
                    'posted_value' => $period[0]->posted,
                    'employment_type_ids' => $employmentTypeIds
                ]);

                $release_date = Carbon::parse($period[0]->release_date)->format('Y-m');
            }

            return $this->successResponse([
                'intervals' => $intervals,
                'cutoffs' => $cutoffs,
                'period' => $period,
                'release_date' => $release_date
            ], 'Payroll period form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load payroll period form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id)
    {
        try {
            // Debug: Log the incoming request data
            Log::info('Payroll Period Store Request:', [
                'id' => $id,
                'all_data' => $request->all(),
                'active_value' => $request->input('active'),
                'posted_value' => $request->input('posted'),
                'has_active' => $request->has('active'),
                'has_posted' => $request->has('posted')
            ]);

            $validator = Validator::make($request->all(), [
                'payroll_interval_id' => 'required',
                'payroll_cutoff_id' => 'required',
                'attendance_start_date' => 'required|date',
                'attendance_end_date' => 'required|date',
                'payroll_start_date' => 'nullable|date', // Changed to nullable - will validate separately
                'payroll_end_date' => 'required|date',
                'release_date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Additional validation: payroll_start_date is required only for First-Half cutoff
            if ($request->payroll_cutoff_id == 1 && empty($request->payroll_start_date)) {
                return $this->validationErrorResponse(['payroll_start_date' => ['The payroll start date field is required for First-Half cutoff.']]);
            }

            // $payroll_date = Carbon::parse($request->release_date)->endOfMonth()->subMonth()->toDateString();
            $payroll_date = Carbon::parse($request->release_date);

            // Validate date ranges based on payroll interval
            $interval = DB::table('payroll_intervals')
                ->where('id', $request->payroll_interval_id)
                ->first();

            if ($interval) {
                $intervalName = $interval->name;
                $attendanceStart = Carbon::parse($request->attendance_start_date);
                $attendanceEnd = Carbon::parse($request->attendance_end_date);
                $days = $attendanceStart->diffInDays($attendanceEnd) + 1;

                $normalizedIntervalName = strtolower(str_replace(' ', '', $intervalName));

                // Half-month should be approximately 15 days
                if ($normalizedIntervalName === 'halfmonth' || $normalizedIntervalName === 'half-month') {
                    // if ($days < 14 || $days > 16) {
                    //     return $this->errorResponse("Half-month period should be approximately 15 days. Current range: {$days} days.");
                    // }
                }


                if ($normalizedIntervalName === 'monthly') {
                    // if ($days < 27 || $days > 31) {
                    //     return $this->errorResponse("Monthly period should be approximately 27-31 days. Current range: {$days} days.");
                    // }
                }
            }


            // TESTING
            // $is_exist = DB::table('payroll_periods')->where([
            //     'payroll_interval_id' => $request->payroll_interval_id,
            //     'attendance_start_date' => $request->attendance_start_date,
            //     'attendance_end_date' => $request->attendance_end_date,
            //     'payroll_start_date' => $request->payroll_start_date,
            //     'payroll_end_date' => $request->payroll_end_date,
            //     'release_date' => $payroll_date,
            // ])
            //     ->get();

            // if ($is_exist->isNotEmpty() && $id == 0) {
            //     return $this->errorResponse('Payroll Period already exists.');
            // }

            $candidates = DB::table('payroll_periods')->where([
                'payroll_interval_id' => $request->payroll_interval_id,
                'attendance_start_date' => $request->attendance_start_date,
                'attendance_end_date' => $request->attendance_end_date,
                'payroll_start_date' => $request->payroll_start_date,
                'payroll_end_date' => $request->payroll_end_date,
                'release_date' => $payroll_date,
            ])->get();

            if ($id == 0 && $candidates->isNotEmpty()) {
                $newTypeIds = $request->has('employment_type_ids') && is_array($request->employment_type_ids)
                    ? array_values(array_map('intval', array_filter($request->employment_type_ids)))
                    : [];
                sort($newTypeIds);

                foreach ($candidates as $candidate) {
                    $existingTypeIds = DB::table('payroll_period_Etype')
                        ->where('payrollperiod_id', $candidate->id)
                        ->pluck('employmenttype_id')
                        ->map(fn ($id) => (int) $id)
                        ->values()
                        ->toArray();
                    sort($existingTypeIds);

                    if($newTypeIds === $existingTypeIds) {
                        return $this->errorResponse('Payroll Period already exists.');
                    }
                }
            }

            // Validate payout dates are not before attendance start date
            // if (($request->payroll_start_date && $request->payroll_start_date < $request->attendance_start_date) || 
            //     $request->payroll_end_date < $request->attendance_start_date) {
            //     return $this->errorResponse('Payout Date should not be less than attendance period');
            // }

            $currentTimestamp = now();

            $data = array(
                'payroll_interval_id' => $request->payroll_interval_id,
                'payroll_cutoff_id' => $request->payroll_cutoff_id,
                'attendance_start_date' => $request->attendance_start_date,
                'attendance_end_date' => $request->attendance_end_date,
                'payroll_start_date' => $request->payroll_start_date,
                'payroll_end_date' => $request->payroll_end_date,
                'release_date' => $payroll_date,
                'active' => $request->input('active', false) ? 'true' : 'false',
                'posted' => $request->input('posted', false) ? 'true' : 'false',
                'updated_at' => $currentTimestamp
            );

            // Debug: Log the data being saved
            Log::info('Payroll Period Data to Save:', $data);

            if ($id == 0) {
                // Add created_at timestamp for new records
                $data['created_at'] = $currentTimestamp;
                DB::table('payroll_periods')->insert($data);
                $new_id = DB::table('payroll_periods')->max('id');

                // Save employment types to pivot table
                if ($request->has('employment_type_ids') && is_array($request->employment_type_ids)) {
                    foreach ($request->employment_type_ids as $employmentTypeId) {
                        if (!empty($employmentTypeId)) {
                            DB::table('payroll_period_Etype')->insert([
                                'payrollperiod_id' => $new_id,
                                'employmenttype_id' => $employmentTypeId,
                                'created_at' => $currentTimestamp
                            ]);
                        }
                    }
                }

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'Payroll Periods',
                    'activity' => 'Add',
                    'description' => 'Added Payroll Periods informations.',
                );
                Audit::create($data_audit);

                return $this->successResponse([
                    'id' => $new_id,
                    'payroll_interval_id' => $request->payroll_interval_id,
                    'payroll_cutoff_id' => $request->payroll_cutoff_id,
                    'attendance_start_date' => $request->attendance_start_date,
                    'attendance_end_date' => $request->attendance_end_date,
                    'payroll_start_date' => $request->payroll_start_date,
                    'payroll_end_date' => $request->payroll_end_date,
                    'release_date' => $payroll_date,
                    'active' => $request->input('active', false) ? 'true' : 'false',
                    'posted' => $request->input('posted', false) ? 'true' : 'false',
                    'created_at' => $currentTimestamp,
                    'updated_at' => $currentTimestamp
                ], 'You have successfully added payroll period!');
            } else {
                DB::table('payroll_periods')->where('id', $id)->update($data);

                // Update employment types - delete existing and insert new ones
                DB::table('payroll_period_Etype')->where('payrollperiod_id', $id)->delete();
                
                if ($request->has('employment_type_ids') && is_array($request->employment_type_ids)) {
                    foreach ($request->employment_type_ids as $employmentTypeId) {
                        if (!empty($employmentTypeId)) {
                            DB::table('payroll_period_Etype')->insert([
                                'payrollperiod_id' => $id,
                                'employmenttype_id' => $employmentTypeId,
                                'created_at' => $currentTimestamp
                            ]);
                        }
                    }
                }

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'Payroll Periods',
                    'activity' => 'Update',
                    'description' => 'Updated Payroll Periods informations.',
                );
                Audit::create($data_audit);

                return $this->successResponse([
                    'id' => $id,
                    'payroll_interval_id' => $request->payroll_interval_id,
                    'payroll_cutoff_id' => $request->payroll_cutoff_id,
                    'attendance_start_date' => $request->attendance_start_date,
                    'attendance_end_date' => $request->attendance_end_date,
                    'payroll_start_date' => $request->payroll_start_date,
                    'payroll_end_date' => $request->payroll_end_date,
                    'release_date' => $payroll_date,
                    'active' => $request->input('active', false) ? 'true' : 'false',
                    'posted' => $request->input('posted', false) ? 'true' : 'false',
                    'updated_at' => $currentTimestamp
                ], 'You have successfully updated payroll period!');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save payroll period: ' . $e->getMessage());
        }
    }

    /**
     * Delete a payroll period
     */
    public function destroy($id)
    {
        try {
            $period = DB::table('payroll_periods')->where('id', $id)->first();

            if (!$period) {
                return $this->notFoundResponse('Payroll period not found');
            }

            // Check if period is posted - prevent deletion of posted periods
            if ($period->posted === 'true' || $period->posted === true || $period->posted === 1) {
                return $this->errorResponse('Cannot delete a posted payroll period. Please unpost it first.');
            }

            // Delete related employment type associations
            DB::table('payroll_period_Etype')->where('payrollperiod_id', $id)->delete();

            // Delete the payroll period
            DB::table('payroll_periods')->where('id', $id)->delete();

            // Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'Payroll Periods',
                'activity' => 'Delete',
                'description' => 'Deleted Payroll Period.',
            );
            Audit::create($data_audit);

            return $this->successResponse([
                'deleted_id' => $id,
            ], 'Payroll period deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete payroll period: ' . $e->getMessage());
        }
    }

    /**
     * Create the two standard periods for a given month (1st half: full previous calendar month, release 15th;
     * 2nd half: full selected month, release last day).
     */
    public function createMonthlyPeriods(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'year' => 'required|integer|min:2000|max:2100',
                'month' => 'required|integer|min:1|max:12',
                'payroll_interval_id' => 'nullable|integer',
                'monthly_cutoff_id' => 'nullable|integer',
                'employment_type_ids' => 'required|array|min:1',
                'employment_type_ids.*' => 'integer',
                // Optional overrides for the release dates (used as payout defaults)
                'first_half_release_date' => 'nullable|date',
                'second_half_release_date' => 'nullable|date',
                // Optional overrides for attendance/payout ranges
                'attendance_start_date' => 'nullable|date',
                'attendance_end_date' => 'nullable|date',
                'payroll_start_date' => 'nullable|date',
                'payroll_end_date' => 'nullable|date',
            ]);
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $year = (int) $request->year;
            $month = (int) $request->month;
            $intervalId = (int) ($request->payroll_interval_id ?? 1);

            $employmentTypeIds = array_values(array_map('intval', array_filter($request->employment_type_ids ?? [])));
            if (empty($employmentTypeIds)) {
                return $this->errorResponse('Please select at least one employment type.');
            }

            $cutoffs = DB::table('payroll_cutoffs')
                ->where('payroll_interval_id', $intervalId)
                ->get();

            // Monthly single-row model:
            // Pick the cutoff that is NOT a "1st/2nd" half cutoff.
            $monthlyCutoffId = $request->monthly_cutoff_id ? (int) $request->monthly_cutoff_id : null;
            $monthlyCutoff = $monthlyCutoffId
                ? $cutoffs->first(fn ($c) => (int) $c->id === $monthlyCutoffId)
                : null;

            if (!$monthlyCutoff) {
                $monthlyCutoff = $cutoffs->first(fn ($c) => preg_match('/monthly/i', $c->name ?? ''))
                    ?? $cutoffs->first(fn ($c) => !preg_match('/1st|2nd|first|second/i', $c->name ?? ''))
                    ?? $cutoffs->first();
            }
            if (!$monthlyCutoff) {
                return $this->errorResponse('No payroll cutoff found for this Monthly interval. Please add one in the database.');
            }

            $thisMonth = Carbon::createFromDate($year, $month, 1);
            $prevMonth = $thisMonth->copy()->subMonth();
            $lastDay = $thisMonth->copy()->endOfMonth();

            $attendanceStartOverride = $request->attendance_start_date
                ? Carbon::parse($request->attendance_start_date)
                : null;
            $attendanceEndOverride = $request->attendance_end_date
                ? Carbon::parse($request->attendance_end_date)
                : null;

            $payrollStartOverride = $request->payroll_start_date
                ? Carbon::parse($request->payroll_start_date)
                : null;
            $payrollEndOverride = $request->payroll_end_date
                ? Carbon::parse($request->payroll_end_date)
                : null;

            // Payout dates:
            // - Prefer explicit release date overrides from the UI if present
            // - Otherwise fall back to the payout/cutoff fields
            // - Otherwise use legacy defaults (15th and last day)
            $firstHalfReleaseDate = !empty($request->first_half_release_date)
                ? Carbon::parse($request->first_half_release_date)
                : ($payrollStartOverride ? $payrollStartOverride->copy() : $thisMonth->copy()->day(15));
            $secondHalfReleaseDate = !empty($request->second_half_release_date)
                ? Carbon::parse($request->second_half_release_date)
                : ($payrollEndOverride ? $payrollEndOverride->copy() : $lastDay->copy());

            // Attendance range: previous full calendar month (e.g. Nov 1–Nov 30 when creating December).
            $attendanceStart = $attendanceStartOverride
                ? $attendanceStartOverride->copy()->format('Y-m-d')
                : $prevMonth->copy()->startOfMonth()->format('Y-m-d');
            $attendanceEnd = $attendanceEndOverride
                ? $attendanceEndOverride->copy()->format('Y-m-d')
                : $prevMonth->copy()->endOfMonth()->format('Y-m-d');

            $payoutFirstDate = $firstHalfReleaseDate->format('Y-m-d'); // 1st payout date
            $payoutSecondDate = $secondHalfReleaseDate->format('Y-m-d'); // 2nd payout date

            $currentTimestamp = now();
            $data = [
                'payroll_interval_id' => $intervalId,
                'payroll_cutoff_id' => $monthlyCutoff->id,
                'attendance_start_date' => $attendanceStart,
                'attendance_end_date' => $attendanceEnd,
                // Re-purposed for 1st/2nd payout dates (single-row model)
                'payroll_start_date' => $payoutFirstDate,
                'payroll_end_date' => $payoutSecondDate,
                // Use 2nd payout as month/grouping key (for sorting/labels)
                'release_date' => $payoutSecondDate,
                'posted' => 'false',
                'active' => 'true',
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp,
            ];

            DB::table('payroll_periods')->insert($data);
            $newId = (int) DB::table('payroll_periods')->max('id');

            foreach ($employmentTypeIds as $eid) {
                DB::table('payroll_period_Etype')->insert([
                    'payrollperiod_id' => $newId,
                    'employmenttype_id' => $eid,
                    'created_at' => $currentTimestamp,
                ]);
            }

            return $this->successResponse([
                'created' => [array_merge($data, ['id' => $newId])],
                'message' => 'Created 1 monthly payroll period for ' . $thisMonth->format('F Y') . '.',
            ], 'Monthly period created successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create monthly periods: ' . $e->getMessage());
        }
    }

    /**
     * Export payroll periods to PDF
     */
    public function exportPdf()
    {
        try {
            $data = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'b.name as payroll_interval',
                    'c.name as payroll_cutoff',
                    DB::raw("CONCAT(DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date)) as payroll_date"),
                    'a.attendance_start_date',
                    'a.attendance_end_date',
                    'a.payroll_start_date',
                    'a.payroll_end_date',
                    'a.release_date',
                    DB::raw("case when a.active = 'true' then 'YES' else 'NO' end as active"),
                    DB::raw("case when a.posted = 'true' then 'YES' else 'NO' end as posted"),
                    'a.created_at',
                    'a.updated_at'
                )
                ->orderby('a.release_date', 'desc')
                ->get();

            $companies = DB::table('companies')->get();

            // Get logo for header
            $image = null;
            if (file_exists(public_path('/dist/img/logo.png'))) {
                $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));
            }

            $pdf = PDF::loadView('payroll_periods.export_pdf', compact('data', 'companies', 'image'))
                ->setOptions(['defaultFont' => 'sans-serif']);

            $pdf->setPaper('A4', 'landscape');
            $pdfContent = $pdf->output();

            $filename = 'payroll_periods_' . date('Y-m-d') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate payroll periods PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export payroll periods to Excel
     */
    public function exportExcel()
    {
        try {
            $data = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'b.name as payroll_interval',
                    'c.name as payroll_cutoff',
                    DB::raw("CONCAT(DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date)) as payroll_date"),
                    'a.attendance_start_date',
                    'a.attendance_end_date',
                    'a.payroll_start_date',
                    'a.payroll_end_date',
                    'a.release_date',
                    DB::raw("case when a.active = 'true' then 'YES' else 'NO' end as active"),
                    DB::raw("case when a.posted = 'true' then 'YES' else 'NO' end as posted"),
                    'a.created_at',
                    'a.updated_at'
                )
                ->orderby('a.release_date', 'desc')
                ->get();

            // Transform data for Excel export
            $exportData = $data->map(function ($item) {
                return [
                    $item->payroll_interval,
                    $item->payroll_cutoff,
                    $item->payroll_date,
                    Carbon::parse($item->attendance_start_date)->format('m-d-Y'),
                    Carbon::parse($item->attendance_end_date)->format('m-d-Y'),
                    Carbon::parse($item->payroll_start_date)->format('m-d-Y'),
                    Carbon::parse($item->payroll_end_date)->format('m-d-Y'),
                ];
            })->toArray();

            $filename = 'payroll_periods_' . date('Y-m-d') . '.xlsx';

            return Excel::download(new class($exportData) implements FromArray, WithHeadings, WithStyles, WithColumnWidths {
                private $data;

                public function __construct($data)
                {
                    $this->data = $data;
                }

                public function array(): array
                {
                    return $this->data;
                }

                public function headings(): array
                {
                    return [
                        'Payroll Interval',
                        'Payroll Cut-off',
                        'Payroll Date',
                        'Attendance Start Date',
                        'Attendance End Date',
                        'Payout Date (1st Half)',
                        'Payout Date (2nd Half)',
                    ];
                }

                public function styles(Worksheet $sheet)
                {
                    return [
                        1 => [
                            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'D3D3D3']
                            ]
                        ],
                    ];
                }

                public function columnWidths(): array
                {
                    return [
                        'A' => 20,
                        'B' => 20,
                        'C' => 20,
                        'D' => 20,
                        'E' => 20,
                        'F' => 25,
                        'G' => 25,
                    ];
                }
            }, $filename);

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate payroll periods Excel: ' . $e->getMessage());
        }
    }

    /**
     * Generate print view for payroll periods
     */
    public function print()
    {
        try {
            $data = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'b.name as payroll_interval',
                    'c.name as payroll_cutoff',
                    DB::raw("CONCAT(DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date)) as payroll_date"),
                    'a.attendance_start_date',
                    'a.attendance_end_date',
                    'a.payroll_start_date',
                    'a.payroll_end_date',
                    'a.release_date',
                    DB::raw("case when a.active = 'true' then 'YES' else 'NO' end as active"),
                    DB::raw("case when a.posted = 'true' then 'YES' else 'NO' end as posted"),
                    'a.created_at',
                    'a.updated_at'
                )
                ->orderby('a.release_date', 'desc')
                ->get();

            $companies = DB::table('companies')->get();

            // Get logo for header
            $image = null;
            if (file_exists(public_path('/dist/img/logo.png'))) {
                $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));
            }

            return view('payroll_periods.print', compact('data', 'companies', 'image'));

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate print view: ' . $e->getMessage()], 500);
        }
    }
}
