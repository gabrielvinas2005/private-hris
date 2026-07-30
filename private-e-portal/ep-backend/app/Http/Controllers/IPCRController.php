<?php

namespace App\Http\Controllers;

use App\Audit;
use App\Traits\ApiResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IPCRController extends Controller
{
    use ApiResponse;

    /**
     * Compute the "A" (Average) rating from Q/E/T.
     * Only ratings with a value (2–5) are included; empty/zero fields are skipped.
     * Returns a 2-decimal average clamped to 2..5 (e.g. 4.33).
     */
    private function computeAverageRating($q, $e, $t)
    {
        $values = [];
        foreach ([$q, $e, $t] as $v) {
            if (!is_numeric($v)) {
                continue;
            }
            $n = (float) $v;
            if ($n >= 2 && $n <= 5) {
                $values[] = $n;
            }
        }

        if (count($values) === 0) {
            return null;
        }

        $avg = array_sum($values) / count($values);
        $avg = max(2, min(5, $avg));

        return round($avg, 2);
    }

    /**
     * Valid IPCR ratings are 2–5. Values outside that range (incl. legacy 1 = unset) are treated as empty.
     */
    private function normalizeRatingForApi($v)
    {
        if (!is_numeric($v)) {
            return null;
        }
        $n = (float) $v;
        if ($n >= 2 && $n <= 5) {
            return $n;
        }

        return null;
    }

    /**
     * Persist unset ratings as 1 (legacy NOT NULL default) — only 2–5 are real ratings.
     */
    private function normalizeRatingForStorage($v)
    {
        if (!is_numeric($v)) {
            return 1;
        }
        $n = (int) $v;
        if ($n >= 2 && $n <= 5) {
            return $n;
        }

        return 1;
    }

    private function formatRecalibrationForApi($recal, $has_justification)
    {
        if (!$recal) {
            return null;
        }

        return [
            'id' => $recal->id,
            'q' => $this->normalizeRatingForApi($recal->quality_rating),
            'e' => $this->normalizeRatingForApi($recal->efficiency_rating),
            't' => $this->normalizeRatingForApi($recal->timeliness_rating),
            'a' => $this->normalizeRatingForApi($recal->average_rating)
                ?? $this->computeAverageRating($recal->quality_rating, $recal->efficiency_rating, $recal->timeliness_rating),
            'remarks' => $recal->remarks,
            'justification' => $has_justification ? ($recal->justification ?? null) : null,
            'recalibrated_by' => $recal->recalibrated_by_employee_id,
            'recalibrated_at' => $recal->created_at,
        ];
    }

    /**
     * Helper: get section name for an employee (best-effort, schema-safe).
     * Used as replacement display label when `employee_ipcr.division` is not present in DB.
     */
    private function getSectionNameForEmployeeId($employee_id)
    {
        if (!$employee_id) {
            return '';
        }

        if (!Schema::hasTable('employees') || !Schema::hasColumn('employees', 'section_id')) {
            return '';
        }

        $section_id = DB::table('employees')->where('id', $employee_id)->value('section_id');
        if (!$section_id) {
            return '';
        }

        if (!Schema::hasTable('sections') || !Schema::hasColumn('sections', 'name')) {
            return '';
        }

        return (string)(DB::table('sections')->where('id', $section_id)->value('name') ?? '');
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        try {
            $ipcr_ratings = DB::table('ipcr_headers as a')
                ->join('departments as c', 'a.department_id', '=', 'c.id')
                ->join('semester_ratings as d', 'a.semester_id', 'd.id')
                ->leftJoin('divisions as e', 'a.division_id', '=', 'e.id')
                ->join('months as f', 'a.month_from', '=', 'f.id')
                ->join('months as g', 'a.month_to', '=', 'g.id')
                ->select(
                    'a.id',
                    'a.department_id',
                    'a.division_id',
                    'a.semester_id',
                    'a.month_from as month_from_id',
                    'a.month_to as month_to_id',
                    'c.name as department',
                    'd.name as semester',
                    'f.name as month_from',
                    'g.name as month_to',
                    DB::raw("case when a.division_id = 0 then 'No division' else e.name end as division"),
                )
                ->get();

            return $this->successResponse($ipcr_ratings, 'IPCR ratings retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve IPCR ratings: ' . $e->getMessage());
        }
    }

    public function add($id)
    {
        try {
            $departments = DB::table('departments as a')->where('a.active', true)
                ->whereIn('a.id', function ($query) {
                    $query->select('department_id')->from('employees')->distinct();
                })
                ->orderBy('a.name', 'asc')
                ->get();

            $divisions = DB::table('divisions')->where('active', true)->orderBy('name', 'asc')->get();
            $semesters = DB::table('semester_ratings')->where('active', true)->orderBy('id', 'asc')->get();

            if ($id <> 0) {
                $ipcr_ratings = DB::table('ipcr_headers as a')
                    ->select(
                        'a.id',
                        'a.department_id',
                        'a.division_id',
                        'a.semester_id',
                        'a.month_from',
                        'a.month_to',
                    )
                    ->where('a.id', $id)
                    ->get();
            } else {
                $ipcr_ratings_dummy = array(
                    'id' => 0,
                    'department_id' => 0,
                    'division_id' => 0,
                    'semester_id' => 0,
                    'month_from' => 0,
                    'month_to' => 0,
                );

                $ipcr_ratings = (object)$ipcr_ratings_dummy;
                $ipcr_ratings = collect([$ipcr_ratings]);
            }

            return $this->successResponse([
                'ipcr_ratings' => $ipcr_ratings,
                'departments' => $departments,
                'divisions' => $divisions,
                'semesters' => $semesters
            ], 'IPCR form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load IPCR form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'department' => 'required',
                'division' => 'required',
                'semester' => 'required',
                'month_from' => 'required',
                'month_to' => 'required',
                'year' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $is_exists = DB::table('ipcr_headers')->where([
                'department_id' => $request->department,
                'division_id' => $request->division,
                'semester_id' => $request->semester,
                'month_from' => $request->month_from,
                'month_to' => $request->month_to,
                'year' => $request->year,
            ])
                ->where('id', '<>', $request->id)
                ->get();

            if ($is_exists->isNotEmpty()) {
                return $this->errorResponse('Save failed. IPCR with same values already exists.');
            }

            $data = array(
                'department_id' => $request->department,
                'division_id' => $request->division,
                'semester_id' => $request->semester,
                'month_from' => $request->month_from,
                'month_to' => $request->month_to,
                'year' => $request->year,
                'is_posted' => true
            );

            $id = $request->id;

            if ($id == 0 || $id == null) {
                $id = DB::table('ipcr_headers')->max('id') + 1;
            }

            DB::unprepared('SET IDENTITY_INSERT ipcr_headers ON');
            DB::table('ipcr_headers')->updateOrInsert(['id' => $id], $data);
            DB::unprepared('SET IDENTITY_INSERT ipcr_headers OFF');

            //Save audit trail
            if ($request->id == 0) {

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'HR Module',
                    'menu'    => 'IPCR',
                    'activity' => 'Add',
                    'description' => 'Added IPCR informations.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'IPCR information added successfully');
            } else {

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'HR Module',
                    'menu'    => 'IPCR',
                    'activity' => 'Update',
                    'description' => 'Updated IPCR informations.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'IPCR information updated successfully');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save IPCR information: ' . $e->getMessage());
        }
    }

    public function review($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $ipcr_details = DB::table('ipcr_details')->where('ipcr_header_id', $id)->get();

            if ($ipcr_details->isEmpty()) {

                if (Auth::user()->access_all_branches) {
                    $ipcr_ratings = DB::table('ipcr_headers as a')
                        ->join('departments as c', 'a.department_id', '=', 'c.id')
                        ->join('semester_ratings as d', 'a.semester_id', 'd.id')
                        ->leftJoin('divisions as e', 'a.division_id', '=', 'e.id')
                        ->join('months as f', 'a.month_from', '=', 'f.id')
                        ->join('months as g', 'a.month_to', '=', 'g.id')
                        ->join('employees as h', 'a.department_id', '=', 'h.department_id')
                        ->join('positions as i', 'h.position_id', '=', 'i.id')
                        ->select(
                            'a.id',
                            'a.department_id',
                            'a.division_id',
                            'a.semester_id',
                            'a.year',
                            'a.month_from as month_from_id',
                            'a.month_to as month_to_id',
                            'c.name as department',
                            'd.name as semester',
                            'f.name as month_from',
                            'g.name as month_to',
                            DB::raw("case when a.division_id = 0 then 'No division' else e.name end as division"),
                            'h.photo',
                            'h.employee_no',
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.first_name ELSE dbo.ufn_DecryptString(h.first_name,'$app_key') END as first_name"),
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.middle_name ELSE dbo.ufn_DecryptString(h.middle_name,'$app_key') END as middle_name"),
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.last_name ELSE dbo.ufn_DecryptString(h.last_name,'$app_key') END as last_name"),
                            'i.name as position',
                            db::raw("'' as numerical_rating"),
                            db::raw("'' as adjectival_rating"),
                            db::raw("'' as attachment"),
                            'h.id as employee_id'
                        )
                        ->where('a.id', $id)
                        ->where('h.is_employee', true)
                        ->orderBy('h.last_name', 'asc')
                        ->get();
                } else {
                    $user_branch_id = DB::table('users as a')
                        ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                        ->select('b.branch_id')
                        ->where('a.id', Auth::user()->id)
                        ->get();

                    $ipcr_ratings = DB::table('ipcr_headers as a')
                        ->join('departments as c', 'a.department_id', '=', 'c.id')
                        ->join('semester_ratings as d', 'a.semester_id', 'd.id')
                        ->leftJoin('divisions as e', 'a.division_id', '=', 'e.id')
                        ->join('months as f', 'a.month_from', '=', 'f.id')
                        ->join('months as g', 'a.month_to', '=', 'g.id')
                        ->join('employees as h', 'a.department_id', '=', 'h.department_id')
                        ->join('positions as i', 'h.position_id', '=', 'i.id')
                        ->select(
                            'a.id',
                            'a.department_id',
                            'a.division_id',
                            'a.semester_id',
                            'a.year',
                            'a.month_from as month_from_id',
                            'a.month_to as month_to_id',
                            'c.name as department',
                            'd.name as semester',
                            'f.name as month_from',
                            'g.name as month_to',
                            DB::raw("case when a.division_id = 0 then 'No division' else e.name end as division"),
                            'h.photo',
                            'h.employee_no',
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.first_name ELSE dbo.ufn_DecryptString(h.first_name,'$app_key') END as first_name"),
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.middle_name ELSE dbo.ufn_DecryptString(h.middle_name,'$app_key') END as middle_name"),
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.last_name ELSE dbo.ufn_DecryptString(h.last_name,'$app_key') END as last_name"),
                            'i.name as position',
                            db::raw("'' as numerical_rating"),
                            db::raw("'' as adjectival_rating"),
                            db::raw("'' as attachment"),
                            'h.id as employee_id'
                        )
                        ->where('a.id', $id)
                        ->where([
                            'h.is_employee' => true,
                            'h.branch_id' => $user_branch_id[0]->branch_id
                        ])
                        ->orderBy('h.last_name', 'asc')
                        ->get();
                }

                if ($ipcr_ratings->isEmpty()) {
                    return $this->errorResponse('There are no employees to review under this department yet.');
                }
            } else {

                if (Auth::user()->access_all_branches) {
                    $ipcr_ratings = DB::table('ipcr_headers as a')
                        ->join('departments as c', 'a.department_id', '=', 'c.id')
                        ->join('semester_ratings as d', 'a.semester_id', 'd.id')
                        ->leftJoin('divisions as e', 'a.division_id', '=', 'e.id')
                        ->join('months as f', 'a.month_from', '=', 'f.id')
                        ->join('months as g', 'a.month_to', '=', 'g.id')
                        ->join('employees as h', 'a.department_id', '=', 'h.department_id')
                        ->join('positions as i', 'h.position_id', '=', 'i.id')
                        ->join('ipcr_details as j', function ($join) {
                            $join->on('a.id', '=', 'j.ipcr_header_id');
                            $join->on('h.id', '=', 'j.employee_id');
                        })
                        ->select(
                            'a.id',
                            'a.department_id',
                            'a.division_id',
                            'a.semester_id',
                            'a.year',
                            'a.month_from as month_from_id',
                            'a.month_to as month_to_id',
                            'c.name as department',
                            'd.name as semester',
                            'f.name as month_from',
                            'g.name as month_to',
                            DB::raw("case when a.division_id = 0 then 'No division' else e.name end as division"),
                            'h.photo',
                            'h.employee_no',
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.first_name ELSE dbo.ufn_DecryptString(h.first_name,'$app_key') END as first_name"),
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.middle_name ELSE dbo.ufn_DecryptString(h.middle_name,'$app_key') END as middle_name"),
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.last_name ELSE dbo.ufn_DecryptString(h.last_name,'$app_key') END as last_name"),
                            'i.name as position',
                            'j.rating as numerical_rating',
                            'j.adjectival_rating',
                            'j.attachment',
                            'h.id as employee_id'
                        )
                        ->where('a.id', $id)
                        ->where('h.is_employee', true)
                        ->orderBy('h.last_name', 'asc')
                        ->get();
                } else {
                    $user_branch_id = DB::table('users as a')
                        ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                        ->select('b.branch_id')
                        ->where('a.id', Auth::user()->id)
                        ->get();

                    $ipcr_ratings = DB::table('ipcr_headers as a')
                        ->join('departments as c', 'a.department_id', '=', 'c.id')
                        ->join('semester_ratings as d', 'a.semester_id', 'd.id')
                        ->leftJoin('divisions as e', 'a.division_id', '=', 'e.id')
                        ->join('months as f', 'a.month_from', '=', 'f.id')
                        ->join('months as g', 'a.month_to', '=', 'g.id')
                        ->join('employees as h', 'a.department_id', '=', 'h.department_id')
                        ->join('positions as i', 'h.position_id', '=', 'i.id')
                        ->join('ipcr_details as j', function ($join) {
                            $join->on('a.id', '=', 'j.ipcr_header_id');
                            $join->on('h.id', '=', 'j.employee_id');
                        })
                        ->select(
                            'a.id',
                            'a.department_id',
                            'a.division_id',
                            'a.semester_id',
                            'a.year',
                            'a.month_from as month_from_id',
                            'a.month_to as month_to_id',
                            'c.name as department',
                            'd.name as semester',
                            'f.name as month_from',
                            'g.name as month_to',
                            DB::raw("case when a.division_id = 0 then 'No division' else e.name end as division"),
                            'h.photo',
                            'h.employee_no',
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.first_name ELSE dbo.ufn_DecryptString(h.first_name,'$app_key') END as first_name"),
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.middle_name ELSE dbo.ufn_DecryptString(h.middle_name,'$app_key') END as middle_name"),
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.last_name ELSE dbo.ufn_DecryptString(h.last_name,'$app_key') END as last_name"),
                            'i.name as position',
                            'j.rating as numerical_rating',
                            'j.adjectival_rating',
                            'j.attachment',
                            'h.id as employee_id'
                        )
                        ->where('a.id', $id)
                        ->where([
                            'h.is_employee' => true,
                            'h.branch_id' => $user_branch_id[0]->branch_id
                        ])
                        ->orderBy('h.last_name', 'asc')
                        ->get();
                }
            }

            if ($ipcr_ratings->isEmpty()) {
                return $this->errorResponse('There are no employees to review under this department yet.');
            }

            return $this->successResponse($ipcr_ratings, 'IPCR review data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve IPCR review data: ' . $e->getMessage());
        }
    }

    public function ipcr_adjective($rating)
    {
        try {
            $data = DB::table('adjectival_ratings')
                ->select('adjectival_rating')
                ->where('numerical_rating1', '<=', $rating)
                ->where('numerical_rating2', '>=', $rating)
                ->get();

            return $this->successResponse($data, 'Adjectival rating retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve adjectival rating: ' . $e->getMessage());
        }
    }

    public function rating(Request $request, $id)
    {
        try {
            $data = $request->all();
            $processed_count = 0;

            $ipcr_details = [];

            for ($i = 0; $i < count($data['id']); $i++) {
                if (isset($data['attachment'][$i])) {
                    $attachment = $data['attachment'][$i]->getClientOriginalName();
                    $data['attachment'][$i]->storeAs('ipcr_file', $data['id'][$i] . '_' . $attachment, 'public');
                } else {
                    if ($data['attachment_data'][$i] == '' || $data['attachment_data'][$i] == null) {
                        $attachment = '';
                    } else {
                        $attachment = $data['attachment_data'][$i];
                    }
                }

                $ipcr_details = [
                    'ipcr_header_id' => $id,
                    'employee_id' => $data['id'][$i],
                    'rating' => $data['numerical_rating'][$i] == null ? 0 : $data['numerical_rating'][$i],
                    'adjectival_rating' => $data['adjectival_rating'][$i] == null ? '' : $data['adjectival_rating'][$i],
                    'attachment' => $attachment,
                    'progress' => ''
                ];

                DB::table('ipcr_details')->updateOrInsert(['ipcr_header_id' => $id, 'employee_id' => $data['id'][$i]], $ipcr_details);
                $processed_count++;
            }

            return $this->successResponse(['processed_count' => $processed_count], 'IPCR data saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save IPCR data: ' . $e->getMessage());
        }
    }

    /**
     * Employee Portal: Check if IPCR is available for current user assignment
     */
    public function checkDivisionChiefAccess()
    {
        try {
            $user = Auth::user();

            // Get employee ID from user by matching employee_no
            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee record not found');
            }

            $has_existing_ipcr = DB::table('employee_ipcr')
                ->where('employee_id', $employee->id)
                ->exists();

            // Check if user has HR access (match employee_no between users and employees tables)
            $is_hr_user = $this->isHRUser($user);
            $is_pmt_user = $this->isPMTUser($employee->id);
            $is_agency_head_user = $this->isAgencyHeadUser($employee->id);

            // If user is section chief and has members, allow IPCR even if their own assignment isn't covered
            $section_employee_ids = $this->getSectionEmployeeIdsForChief($employee->id);

            // IPCR is available if:
            // 1. User has HR access, OR
            // 2. User is PMT member, OR
            // 3. IPCR is available for their assignment, OR
            // 4. User is section chief with members
            // 5. User already has existing IPCR record(s)
            // 6. User is Head of Agency (workflow approver)
            $is_available = $is_hr_user
                || $is_pmt_user
                || $is_agency_head_user
                || $has_existing_ipcr
                || $this->isIpcrAvailableForEmployee($employee)
                || !empty($section_employee_ids);

            $pending_agency_head_count = 0;
            if ($is_agency_head_user && Schema::hasColumn('employee_ipcr', 'recalibration_status')) {
                $pending_agency_head_count = DB::table('employee_ipcr')
                    ->where('recalibration_status', 'supervisor_recalibrated')
                    ->count();
            }

            $pending_hr_recalibration_count = 0;
            if ($is_hr_user && Schema::hasColumn('employee_ipcr', 'recalibration_status')) {
                $pending_hr_recalibration_count = DB::table('employee_ipcr')
                    ->where('recalibration_status', 'agency_head_approved')
                    ->count();
            }

            return $this->successResponse([
                // Legacy field kept for compatibility; not used anymore
                'is_division_chief' => false,
                'is_available' => $is_available,
                'is_hr_user' => $is_hr_user,
                'is_pmt_user' => $is_pmt_user,
                'is_agency_head' => $is_agency_head_user,
                'pending_agency_head_count' => $pending_agency_head_count,
                'pending_hr_recalibration_count' => $pending_hr_recalibration_count,
            ], 'Access check completed');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to check access: ' . $e->getMessage());
        }
    }

    /**
     * Employee Portal: Get IPCR records for current user
     */
    public function employeeList()
    {
        try {
            $user = Auth::user();

            // Get employee ID from user
            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee record not found');
            }

            $employee_id = $employee->id;

            // If the employee already has IPCR records, allow access even if current assignment
            // isn't covered by ipcr_headers (common for COS/non-plantilla records missing department_id).
            $has_existing_ipcr = DB::table('employee_ipcr')
                ->where('employee_id', $employee_id)
                ->exists();

            // Check if user has HR access
            $is_hr_user = $this->isHRUser($user);
            $is_pmt_user = $this->isPMTUser($employee_id);
            $is_agency_head_user = $this->isAgencyHeadUser($employee_id);

            // Determine if user is section chief and get employee IDs under their section (including self)
            $section_employee_ids = $this->getSectionEmployeeIdsForChief($employee_id);

            // Access gate: IPCR must be available for this assignment (skip if section chief with members, HR, PMT, or agency head)
            if (empty($section_employee_ids) && !$is_hr_user && !$is_pmt_user && !$is_agency_head_user && !$has_existing_ipcr && !$this->isIpcrAvailableForEmployee($employee)) {
                return $this->errorResponse('IPCR is not available for your assignment.');
            }

            // Build query for IPCR records
            $ipcr_query = DB::table('employee_ipcr as a');
            $this->applyEmployeeIpcrAccessScope(
                $ipcr_query,
                $employee_id,
                $section_employee_ids,
                $is_hr_user,
                $is_pmt_user,
                $is_agency_head_user,
                'a'
            );

            // Preload names for mapping
            $scope_employee_ids = array_values(array_unique(array_merge(
                [(int) $employee_id],
                array_map('intval', $section_employee_ids ?? [])
            )));

            $names_query = DB::table('employees as e')
                ->leftJoin('name_prefixes as b', 'e.name_prefix_id', '=', 'b.id')
                ->leftJoin('name_suffixes as c', 'e.name_suffix_id', '=', 'c.id')
                ->whereIn('e.id', $scope_employee_ids);

            $names = $names_query->pluck(DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                CONCAT(COALESCE(b.name,''), ' ', e.first_name, ' ', COALESCE(e.middle_name,''), ' ', e.last_name, ' ', COALESCE(c.name,''))
            ELSE
                CONCAT(COALESCE(b.name,''), ' ', RTRIM([dbo].[ufn_DecryptString](e.first_name,'" . env("APP_KEY", "") . "')), ' ',
                RTRIM(COALESCE([dbo].[ufn_DecryptString](e.middle_name,'" . env("APP_KEY", "") . "'),'')), ' ',
                RTRIM([dbo].[ufn_DecryptString](e.last_name,'" . env("APP_KEY", "") . "')), ' ', COALESCE(c.name,''))
            END as name"), 'e.id');

            // Get IPCR records for self (and section, if chief)
            // Check if recalibration_status column exists
            $has_recalibration_status = Schema::hasColumn('employee_ipcr', 'recalibration_status');

            $select_fields = [
                'a.id',
                'a.employee_id',
                'a.period_start',
                'a.period_end',
                'a.reviewed_date',
                'a.approved_date',
                'a.created_at',
                'a.updated_at'
            ];

            // Optional columns (schema differs across environments)
            if (Schema::hasColumn('employee_ipcr', 'division')) {
                $select_fields[] = 'a.division';
            }
            if (Schema::hasColumn('employee_ipcr', 'reviewed_by')) {
                $select_fields[] = 'a.reviewed_by';
            }
            if (Schema::hasColumn('employee_ipcr', 'approved_by')) {
                $select_fields[] = 'a.approved_by';
            }

            if ($has_recalibration_status) {
                $select_fields[] = 'a.recalibration_status';
            }

            $ipcr_records = $ipcr_query
                ->select($select_fields)
                ->orderBy('a.created_at', 'desc')
                ->get();

            // If HR/PMT/agency head, get all employee IDs from the IPCR records to load names
            if ($is_hr_user || $is_pmt_user || $is_agency_head_user) {
                $all_employee_ids = $ipcr_records->pluck('employee_id')->unique()->toArray();
                if (!empty($all_employee_ids)) {
                    $names = DB::table('employees as e')
                        ->leftJoin('name_prefixes as b', 'e.name_prefix_id', '=', 'b.id')
                        ->leftJoin('name_suffixes as c', 'e.name_suffix_id', '=', 'c.id')
                        ->whereIn('e.id', $all_employee_ids)
                        ->pluck(DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                            CONCAT(COALESCE(b.name,''), ' ', e.first_name, ' ', COALESCE(e.middle_name,''), ' ', e.last_name, ' ', COALESCE(c.name,''))
                        ELSE
                            CONCAT(COALESCE(b.name,''), ' ', RTRIM([dbo].[ufn_DecryptString](e.first_name,'" . env("APP_KEY", "") . "')), ' ',
                            RTRIM(COALESCE([dbo].[ufn_DecryptString](e.middle_name,'" . env("APP_KEY", "") . "'),'')), ' ',
                            RTRIM([dbo].[ufn_DecryptString](e.last_name,'" . env("APP_KEY", "") . "')), ' ', COALESCE(c.name,''))
                        END as name"), 'e.id');
                }
            }

            // Check if current user is supervisor (for calibration button visibility)
            $is_supervisor_for_list = false;
            if (!empty($section_employee_ids)) {
                $is_supervisor_for_list = true;
            }

            // Format the records
            $formatted_records = $ipcr_records->map(function ($record) use ($names, $employee_id, $is_supervisor_for_list, $has_recalibration_status, $is_hr_user, $is_pmt_user, $is_agency_head_user) {
                $period = '';
                if ($record->period_start && $record->period_end) {
                    $period = date('M d', strtotime($record->period_start)) . ' - ' . date('M d, Y', strtotime($record->period_end));
                }

                // Check recalibration status (if column exists)
                $recal_status = 'self_assessment'; // Default
                if ($has_recalibration_status && isset($record->recalibration_status)) {
                    $recal_status = $record->recalibration_status;
                }

                // Check if this record can be calibrated
                // Supervisor can calibrate if: they are supervisor AND record belongs to their subordinate AND status is self_assessment
                $can_calibrate_supervisor = false;
                if ($is_supervisor_for_list && $record->employee_id != $employee_id) {
                    $can_calibrate_supervisor = ($recal_status === 'self_assessment');
                }

                // HR can calibrate after Head of Agency approval
                $can_calibrate_hr = false;
                if ($is_hr_user) {
                    $can_calibrate_hr = ($recal_status === 'agency_head_approved');
                }

                // Head of Agency can approve after supervisor recalibration
                $can_approve_agency_head = false;
                if ($is_agency_head_user) {
                    $can_approve_agency_head = ($recal_status === 'supervisor_recalibrated');
                }

                // PMT can calibrate if: user is PMT member AND status is hr_recalibrated
                $can_calibrate_pmt = false;
                if ($is_pmt_user) {
                    $can_calibrate_pmt = ($recal_status === 'hr_recalibrated');
                }

                // Show calibrate button if any level can calibrate
                $can_calibrate = $can_calibrate_supervisor || $can_calibrate_hr || $can_calibrate_pmt;

                return [
                    'id' => $record->id,
                    'employee_id' => $record->employee_id,
                    'employee_name' => $names[$record->employee_id] ?? '',
                    // Use stored value when available; otherwise show section name (new rule: section-based)
                    'division' => (property_exists($record, 'division') ? ($record->division ?? '') : $this->getSectionNameForEmployeeId($record->employee_id)),
                    'period' => $period,
                    'period_start' => $record->period_start,
                    'period_end' => $record->period_end,
                    'reviewed_by' => (property_exists($record, 'reviewed_by') ? $record->reviewed_by : null),
                    'reviewed_date' => $record->reviewed_date,
                    'approved_by' => (property_exists($record, 'approved_by') ? $record->approved_by : null),
                    'approved_date' => $record->approved_date,
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at,
                    'can_calibrate' => $can_calibrate,
                    'can_calibrate_supervisor' => $can_calibrate_supervisor,
                    'can_calibrate_hr' => $can_calibrate_hr,
                    'can_calibrate_pmt' => $can_calibrate_pmt,
                    'can_approve_agency_head' => $can_approve_agency_head,
                    'recalibration_status' => $recal_status
                ];
            });

            return $this->successResponse($formatted_records, 'IPCR records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve IPCR records: ' . $e->getMessage());
        }
    }

    /**
     * Employee Portal: Get form data (department info and employee lists)
     */
    public function employeeFormData()
    {
        try {
            $app_key = env("APP_KEY", "");
            $user = Auth::user();

            // Get employee ID from user
            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee record not found');
            }

            $employee_id = $employee->id;

            // If the employee already has IPCR records, allow access even if current assignment
            // isn't covered by ipcr_headers (common for COS/non-plantilla records missing department_id).
            $has_existing_ipcr = DB::table('employee_ipcr')
                ->where('employee_id', $employee_id)
                ->exists();

            // Check if user has HR access
            $is_hr_user = $this->isHRUser($user);
            $is_pmt_user = $this->isPMTUser($employee_id);
            $is_agency_head_user = $this->isAgencyHeadUser($employee_id);

            // Section chief allowance: if they have section members, allow even if their own assignment is not covered
            $section_employee_ids = $this->getSectionEmployeeIdsForChief($employee_id);

            // Access gate: IPCR must be available for this assignment (skip if HR, PMT, agency head, or section chief with members)
            if (empty($section_employee_ids) && !$is_hr_user && !$is_pmt_user && !$is_agency_head_user && !$has_existing_ipcr && !$this->isIpcrAvailableForEmployee($employee)) {
                return $this->errorResponse('IPCR is not available for your assignment.');
            }

            // Display label for Division/Department field: use SECTION now (fallback to department name)
            $division_name = '';
            $department = null;

            if (Schema::hasTable('sections') && Schema::hasColumn('employees', 'section_id') && isset($employee->section_id) && $employee->section_id) {
                if (Schema::hasColumn('sections', 'name')) {
                    $division_name = (string)(DB::table('sections')->where('id', $employee->section_id)->value('name') ?? '');
                }
            }

            // Legacy fallback: department name
            if ($division_name === '') {
                $employee_department_id = $employee->department_id ?? 0;
            if ($employee_department_id && $employee_department_id != 0) {
                $department = DB::table('departments')
                    ->where('id', $employee_department_id)
                    ->where('active', true)
                    ->first();

                if ($department) {
                    $division_name = $department->name;
                    }
                }
            }

            // Get all employees for dropdowns (all active employees)
            $employees = DB::table('employees as a')
                ->leftJoin('name_prefixes as b', 'a.name_prefix_id', '=', 'b.id')
                ->leftJoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                        CONCAT(COALESCE(b.name,''), ' ', a.first_name, ' ', COALESCE(a.middle_name,''), ' ', a.last_name, ' ', COALESCE(c.name,''))
                    ELSE
                        CONCAT(COALESCE(b.name,''), ' ', RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')), ' ',
                        RTRIM(COALESCE([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),'')), ' ',
                        RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), ' ', COALESCE(c.name,''))
                    END as name")
                )
                ->where([
                    'a.is_employee' => true,
                    'a.active' => true
                ])
                ->orderBy('a.last_name', 'asc')
                ->get();

            // Get employees under this department (if user has a department)
            $division_employees = [];
            if ($department) {
                $division_employees = DB::table('employees as a')
                    ->leftJoin('name_prefixes as b', 'a.name_prefix_id', '=', 'b.id')
                    ->leftJoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                    ->select(
                        'a.id',
                        'a.employee_no',
                        DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                            CONCAT(COALESCE(b.name,''), ' ', a.first_name, ' ', COALESCE(a.middle_name,''), ' ', a.last_name, ' ', COALESCE(c.name,''))
                        ELSE
                            CONCAT(COALESCE(b.name,''), ' ', RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')), ' ',
                            RTRIM(COALESCE([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),'')), ' ',
                            RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), ' ', COALESCE(c.name,''))
                        END as name")
                    )
                    ->where([
                        'a.department_id' => $department->id,
                        'a.is_employee' => true,
                        'a.active' => true
                    ])
                    ->orderBy('a.last_name', 'asc')
                    ->get();
            }

            // Get current employee info for self-assessment
            $current_employee = DB::table('employees as a')
                ->leftJoin('name_prefixes as b', 'a.name_prefix_id', '=', 'b.id')
                ->leftJoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                        CONCAT(COALESCE(b.name,''), ' ', a.first_name, ' ', COALESCE(a.middle_name,''), ' ', a.last_name, ' ', COALESCE(c.name,''))
                    ELSE
                        CONCAT(COALESCE(b.name,''), ' ', RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')), ' ',
                        RTRIM(COALESCE([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),'')), ' ',
                        RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), ' ', COALESCE(c.name,''))
                    END as name")
                )
                ->where('a.id', $employee_id)
                ->first();

            // Resolve section chief (section_chief_id from sections table, by employee's section_id)
            $section_chief = null;
            if (Schema::hasTable('sections') && Schema::hasColumn('sections', 'section_chief_id') && isset($employee->section_id)) {
                $section_chief_id = DB::table('sections')->where('id', $employee->section_id)->value('section_chief_id');
                if ($section_chief_id) {
                    $section_chief_employee = $this->getEmployeeNameById($section_chief_id);
                    if ($section_chief_employee) {
                        $section_chief = [
                            'id' => (int) $section_chief_id,
                            'name' => $section_chief_employee->name ?? null,
                        ];
                    }
                }
            }

            // Resolve Head of Agency from main branch (branches.branch_head_id)
            $agency_head = null;
            $agency_head_id = $this->getAgencyHeadEmployeeId();
            if ($agency_head_id) {
                $agency_head_employee = $this->getEmployeeNameById($agency_head_id);
                if ($agency_head_employee) {
                    $agency_head = [
                        'id' => (int) $agency_head_id,
                        'name' => $agency_head_employee->name ?? null,
                    ];
                }
            }

            // Get PMT member with is_ipcr = 1 for final rater default (per section)
            $pmt_member = null;
            if (Schema::hasTable('pmt') && Schema::hasColumn('pmt', 'is_ipcr') && Schema::hasColumn('pmt', 'section_id') && isset($employee->section_id)) {
                $pmt_record = DB::table('pmt')
                    ->where('is_ipcr', 1)
                    ->where('section_id', $employee->section_id)
                    ->first();
                
                if ($pmt_record && isset($pmt_record->employee_id)) {
                    $pmt_employee = $this->getEmployeeNameById($pmt_record->employee_id);
                    if ($pmt_employee) {
                        $pmt_member = [
                            'id' => $pmt_record->employee_id,
                            'name' => $pmt_employee->name ?? null
                        ];
                    }
                }
            }

            return $this->successResponse([
                'division' => $division_name,
                'employees' => $employees,
                'department_employees' => $division_employees,
                'current_employee' => $current_employee,
                'section_chief' => $section_chief,
                'agency_head' => $agency_head,
                'pmt_member' => $pmt_member,
            ], 'IPCR form data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve IPCR form data: ' . $e->getMessage());
        }
    }

    /**
     * Employee Portal: Get single IPCR record with outputs
     */
    public function employeeGet($id)
    {
        try {
            $user = Auth::user();

            // Get employee ID from user
            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee record not found');
            }

            $employee_id = $employee->id;

            // If the employee already has IPCR records, allow access even if current assignment
            // isn't covered by ipcr_headers (common for COS/non-plantilla records missing department_id).
            $has_existing_ipcr = DB::table('employee_ipcr')
                ->where('employee_id', $employee_id)
                ->exists();

            // Check if user has HR access
            $is_hr_user = $this->isHRUser($user);
            $is_pmt_user = $this->isPMTUser($employee_id);
            $is_agency_head_user = $this->isAgencyHeadUser($employee_id);

            // Allowed employees: self + section (if chief)
            $section_employee_ids = $this->getSectionEmployeeIdsForChief($employee_id);

            // Gate: IPCR must be available for this assignment (skip if HR, PMT, agency head, or section chief with members)
            if (empty($section_employee_ids) && !$is_hr_user && !$is_pmt_user && !$is_agency_head_user && !$has_existing_ipcr && !$this->isIpcrAvailableForEmployee($employee)) {
                return $this->errorResponse('IPCR is not available for your assignment.');
            }

            // Get IPCR header
            $ipcr_query = DB::table('employee_ipcr')
                ->where('id', $id);

            $this->applyEmployeeIpcrAccessScope(
                $ipcr_query,
                $employee_id,
                $section_employee_ids,
                $is_hr_user,
                $is_pmt_user,
                $is_agency_head_user
            );

            $ipcr = $ipcr_query->first();

            if (!$ipcr) {
                return $this->errorResponse('IPCR record not found');
            }

            // Get IPCR outputs
            $outputs = DB::table('employee_ipcr_outputs')
                ->where('employee_ipcr_id', $id)
                ->when(
                    Schema::hasColumn('employee_ipcr_outputs', 'function_type'),
                    function ($query) {
                        $query->orderByRaw("CASE function_type WHEN 'core' THEN 1 WHEN 'strategic' THEN 2 WHEN 'support' THEN 3 ELSE 4 END");
                    }
                )
                ->orderBy('id', 'asc')
                ->get();

            // Get form data for dropdowns
            $form_data = $this->getFormDataForEmployee($employee_id);

            // Helper function to get employee name by ID
            $getEmployeeName = function ($emp_id) {
                if (!$emp_id) return null;
                $emp = DB::table('employees as a')
                    ->leftJoin('name_prefixes as b', 'a.name_prefix_id', '=', 'b.id')
                    ->leftJoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                    ->select(DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                        CONCAT(COALESCE(b.name,''), ' ', a.first_name, ' ', COALESCE(a.middle_name,''), ' ', a.last_name, ' ', COALESCE(c.name,''))
                    ELSE
                        CONCAT(COALESCE(b.name,''), ' ', RTRIM([dbo].[ufn_DecryptString](a.first_name,'" . env("APP_KEY", "") . "')), ' ',
                        RTRIM(COALESCE([dbo].[ufn_DecryptString](a.middle_name,'" . env("APP_KEY", "") . "'),'')), ' ',
                        RTRIM([dbo].[ufn_DecryptString](a.last_name,'" . env("APP_KEY", "") . "')), ' ', COALESCE(c.name,''))
                    END as name"))
                    ->where('a.id', $emp_id)
                    ->first();
                return $emp->name ?? null;
            };

            // Schema-safe: prefer *_employee_id columns when present; otherwise treat reviewed_by/approved_by as name strings.
            $reviewed_by_id = $this->resolveSignatoryIdFromIpcrRecord($ipcr, 'reviewed_by_employee_id', 'reviewed_by');
            $approved_by_id = $this->resolveSignatoryIdFromIpcrRecord($ipcr, 'approved_by_employee_id', 'approved_by');
            $assessed_by_id = $this->resolveSignatoryIdFromIpcrRecord($ipcr, 'assessed_by_employee_id', 'assessed_by');
            $final_rater_id = $this->resolveSignatoryIdFromIpcrRecord($ipcr, 'final_rater_employee_id', 'final_rater');

            $this->hydrateIpcrSignatoriesForPrint($ipcr, (int) $ipcr->employee_id, $getEmployeeName);

            $reviewed_by_name = $ipcr->reviewed_by ?? null;
            $approved_by_name = $ipcr->approved_by ?? null;
            $assessed_by_name = $ipcr->assessed_by ?? null;
            $final_rater_name = $ipcr->final_rater ?? null;

            if (!$reviewed_by_id && $assessed_by_id) {
                $reviewed_by_id = $assessed_by_id;
            }
            if (empty($reviewed_by_name) && $assessed_by_name) {
                $reviewed_by_name = $assessed_by_name;
            }
            if (!$assessed_by_id && $reviewed_by_id) {
                $assessed_by_id = $reviewed_by_id;
            }
            if (empty($assessed_by_name) && $reviewed_by_name) {
                $assessed_by_name = $reviewed_by_name;
            }

            // Employee name should be the IPCR owner (the employee who created/self-assessed it)
            $employee_name_value = $getEmployeeName($ipcr->employee_id);

            // Check user roles for recalibration access
            $is_supervisor = $this->isSupervisorOfEmployee($employee_id, $ipcr->employee_id);
            $is_hr = $this->isHRUser($user);
            $is_pmt = $this->isPMTUser($employee_id);
            $is_agency_head = $this->isAgencyHeadUser($employee_id);
            // Get recalibration status (if column exists)
            $recalibration_status = 'self_assessment'; // Default
            if (Schema::hasColumn('employee_ipcr', 'recalibration_status')) {
                $recalibration_status = $ipcr->recalibration_status ?? 'self_assessment';
            }

            // Check if justification column exists in ipcr_recalibrations table
            $has_justification = Schema::hasColumn('ipcr_recalibrations', 'justification');

            // Get all recalibrations for each output
            $outputs_with_recal = $outputs->map(function ($output) use ($has_justification) {
                $recalibrations = DB::table('ipcr_recalibrations')
                    ->where('employee_ipcr_output_id', $output->id)
                    ->orderByRaw("CASE recalibration_level
                        WHEN 'supervisor' THEN 1
                        WHEN 'hr' THEN 2
                        WHEN 'pmt' THEN 3
                        ELSE 4 END")
                    ->get();

                $supervisor_recal = $recalibrations->firstWhere('recalibration_level', 'supervisor');
                $hr_recal = $recalibrations->firstWhere('recalibration_level', 'hr');
                $pmt_recal = $recalibrations->firstWhere('recalibration_level', 'pmt');

                // Determine current effective ratings (latest recalibration or employee's original)
                $current_recal = $pmt_recal ?? $hr_recal ?? $supervisor_recal;

                return [
                    'id' => $output->id,
                    'functionType' => Schema::hasColumn('employee_ipcr_outputs', 'function_type')
                        ? ($output->function_type ?? 'core')
                        : 'core',
                    'output' => $output->output,
                    'successIndicators' => $output->success_indicators,
                    'accomplishment' => $output->accomplishment,
                    // Employee's original ratings
                    'q' => $this->normalizeRatingForApi($output->quality_rating),
                    'e' => $this->normalizeRatingForApi($output->efficiency_rating),
                    't' => $this->normalizeRatingForApi($output->timeliness_rating),
                    'a' => $this->normalizeRatingForApi($output->average_rating)
                        ?? $this->computeAverageRating($output->quality_rating, $output->efficiency_rating, $output->timeliness_rating),
                    'remarks' => $output->remarks ?? '',
                    // Supervisor recalibration
                    'supervisor_recalibration' => $this->formatRecalibrationForApi($supervisor_recal, $has_justification),
                    // HR recalibration
                    'hr_recalibration' => $this->formatRecalibrationForApi($hr_recal, $has_justification),
                    // PMT recalibration
                    'pmt_recalibration' => $this->formatRecalibrationForApi($pmt_recal, $has_justification),
                ];
            })->toArray();

            // Format the data
            $formatted = [
                'id' => $ipcr->id,
                'division' => (Schema::hasColumn('employee_ipcr', 'division') ? ($ipcr->division ?? '') : $this->getSectionNameForEmployeeId($ipcr->employee_id)),
                'period' => $ipcr->period_start && $ipcr->period_end
                    ? [$ipcr->period_start, $ipcr->period_end]
                    : [],
                'reviewedBy' => $reviewed_by_name ?? '',
                'reviewedByEmployeeId' => $reviewed_by_id ?? null,
                'reviewedDate' => $ipcr->reviewed_date,
                'approvedBy' => $approved_by_name ?? '',
                'approvedByEmployeeId' => $approved_by_id ?? null,
                'approvedDate' => $ipcr->approved_date,
                'outputs' => $outputs_with_recal,
                'comments' => $ipcr->comments ?? '',
                'employee' => $employee_name_value ?? '',
                'employeeId' => $ipcr->employee_id, // IPCR owner's ID (the employee who created it)
                'employeeDate' => $ipcr->employee_date,
                'assessedBy' => $assessed_by_name ?? '',
                'assessedByEmployeeId' => $assessed_by_id ?? null,
                'assessedDate' => $ipcr->assessed_date,
                'finalRater' => $final_rater_name ?? '',
                'finalRaterEmployeeId' => $final_rater_id ?? null,
                'finalRateDate' => $ipcr->final_rate_date,
                'employees' => $form_data['employees'],
                'department_employees' => $form_data['department_employees'],
                // Recalibration access flags
                'is_supervisor' => $is_supervisor,
                'is_hr' => $is_hr,
                'is_pmt' => $is_pmt,
                'is_agency_head' => $is_agency_head,
                'recalibration_status' => $recalibration_status,
                'can_recalibrate_supervisor' => $is_supervisor && in_array($recalibration_status, ['self_assessment']),
                'can_recalibrate_hr' => $is_hr && in_array($recalibration_status, ['agency_head_approved']),
                'can_recalibrate_pmt' => $is_pmt && in_array($recalibration_status, ['hr_recalibrated']),
                'can_approve_agency_head' => $is_agency_head && in_array($recalibration_status, ['supervisor_recalibrated']),
            ];

            if (Schema::hasColumn('employee_ipcr', 'agency_head_approved_at')) {
                $formatted['agency_head_approved_at'] = $ipcr->agency_head_approved_at ?? null;
            }
            if (Schema::hasColumn('employee_ipcr', 'agency_head_approved_by')) {
                $formatted['agency_head_approved_by'] = $ipcr->agency_head_approved_by ?? null;
                $formatted['agency_head_approved_by_name'] = !empty($ipcr->agency_head_approved_by)
                    ? ($getEmployeeName($ipcr->agency_head_approved_by) ?? null)
                    : null;
            }
            if (Schema::hasColumn('employee_ipcr', 'agency_head_approval_remarks')) {
                $formatted['agency_head_approval_remarks'] = $ipcr->agency_head_approval_remarks ?? null;
            }

            return $this->successResponse($formatted, 'IPCR record retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve IPCR record: ' . $e->getMessage());
        }
    }

    /**
     * Employee Portal: Save IPCR (create or update)
     */
    public function employeeStore(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'division' => 'required|string|max:255',
                'period' => 'required|array|size:2',
                'period.*' => 'required|date',
                'reviewedBy' => 'nullable|string|max:255',
                'reviewedDate' => 'nullable|date',
                'approvedBy' => 'nullable|string|max:255',
                'approvedDate' => 'nullable|date',
                'outputs' => 'required|array|min:1',
                'outputs.*.output' => 'required|string',
                'employee' => 'nullable|string|max:255',
                'employeeDate' => 'nullable|date',
                'assessedBy' => 'nullable|string|max:255',
                'assessedDate' => 'nullable|date',
                'finalRater' => 'nullable|string|max:255',
                'finalRateDate' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $user = Auth::user();

            // Get employee ID from user
            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee record not found');
            }

            $employee_id = $employee->id;

            $data = $request->all();

            // Gate: require IPCR availability only when creating a NEW record.
            // Match checkDivisionChiefAccess / employeeFormData rules (HR, PMT, section chief, existing records).
            $id = (int)$request->input('id', 0);
            if ($id === 0) {
                $is_hr_user = $this->isHRUser($user);
                $is_pmt_user = $this->isPMTUser($employee_id);
                $section_employee_ids = $this->getSectionEmployeeIdsForChief($employee_id);
                $has_existing_ipcr = DB::table('employee_ipcr')
                    ->where('employee_id', $employee_id)
                    ->exists();

                if (
                    empty($section_employee_ids)
                    && !$is_hr_user
                    && !$is_pmt_user
                    && !$has_existing_ipcr
                    && !$this->isIpcrAvailableForEmployee($employee)
                ) {
                    return $this->errorResponse('IPCR is not available for your assignment.');
                }
            }

            // Prepare header data (schema-safe; some deployments don't have `division` column, etc.)
            $header_data = [
                'employee_id'  => $employee_id,
                'updated_at'   => now()
            ];

            // Store section label only if legacy `division` column exists
            if (Schema::hasColumn('employee_ipcr', 'division')) {
                $header_data['division'] = $data['division'] ?? null; // currently used as Section label in UI
            }

            if (Schema::hasColumn('employee_ipcr', 'period_start')) {
                $header_data['period_start'] = $data['period'][0] ?? null;
            }
            if (Schema::hasColumn('employee_ipcr', 'period_end')) {
                $header_data['period_end'] = $data['period'][1] ?? null;
            }
            if (Schema::hasColumn('employee_ipcr', 'reviewed_date')) {
                $header_data['reviewed_date'] = $data['reviewedDate'] ?? null;
            }
            if (Schema::hasColumn('employee_ipcr', 'approved_date')) {
                $header_data['approved_date'] = $data['approvedDate'] ?? null;
            }
            if (Schema::hasColumn('employee_ipcr', 'employee_date')) {
                $header_data['employee_date'] = $data['employeeDate'] ?? null;
            }
            if (Schema::hasColumn('employee_ipcr', 'assessed_date')) {
                $header_data['assessed_date'] = $data['assessedDate'] ?? null;
            }
            if (Schema::hasColumn('employee_ipcr', 'final_rate_date')) {
                $header_data['final_rate_date'] = $data['finalRateDate'] ?? null;
            }
            if (Schema::hasColumn('employee_ipcr', 'comments')) {
                $header_data['comments'] = $data['comments'] ?? null;
            }

            if (Schema::hasColumn('employee_ipcr', 'recalibration_status') && $id === 0) {
                $header_data['recalibration_status'] = 'self_assessment';
            }

            // Signatories: support both name-string columns and legacy integer ID columns.
            $this->assignEmployeeIpcrSignatoryFields($header_data, $data);

            if (Schema::hasColumn('employee_ipcr', 'employee_name_id')) {
                $header_data['employee_name_id'] = $employee_id;
            }
            if (Schema::hasColumn('employee_ipcr', 'employee_name')) {
                if ($this->isIntegerColumn('employee_ipcr', 'employee_name')) {
                    $header_data['employee_name'] = $employee_id;
                } else {
                    $header_data['employee_name'] = $data['employee'] ?? null;
                }
            }

            if ($id == 0) {
                // Create new
                $header_data['created_at'] = now();
                $id = DB::table('employee_ipcr')->insertGetId($header_data);
            } else {
                // Update existing - verify ownership
                $existing = DB::table('employee_ipcr')
                    ->where('id', $id)
                    ->where('employee_id', $employee_id)
                    ->first();

                if (!$existing) {
                    return $this->errorResponse('IPCR record not found or access denied');
                }

                if (!$this->canEmployeeEditOwnIpcrSelfAssessment($user, $employee_id, $existing)) {
                    return $this->errorResponse('This IPCR can no longer be edited after supervisor or HR calibration.');
                }

                DB::table('employee_ipcr')
                    ->where('id', $id)
                    ->update($header_data);

                // Delete existing outputs
                DB::table('employee_ipcr_outputs')
                    ->where('employee_ipcr_id', $id)
                    ->delete();
            }

            // Save outputs
            foreach ($data['outputs'] as $output) {
                $computedA = $this->computeAverageRating($output['q'] ?? null, $output['e'] ?? null, $output['t'] ?? null);
                $functionType = $output['functionType'] ?? $output['function_type'] ?? 'core';
                if (!in_array($functionType, ['core', 'strategic', 'support'], true)) {
                    $functionType = 'core';
                }

                $output_data = [
                    'employee_ipcr_id' => $id,
                    'output' => $output['output'],
                    'success_indicators' => $output['successIndicators'] ?? null,
                    'accomplishment' => $output['accomplishment'] ?? null,
                    'quality_rating' => $this->normalizeRatingForStorage($output['q'] ?? null),
                    'efficiency_rating' => $this->normalizeRatingForStorage($output['e'] ?? null),
                    'timeliness_rating' => $this->normalizeRatingForStorage($output['t'] ?? null),
                    // A is the average of Q/E/T (rounded), not a manual input
                    'average_rating' => $computedA ?? $this->normalizeRatingForStorage($output['a'] ?? null),
                    'remarks' => $output['remarks'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                if (Schema::hasColumn('employee_ipcr_outputs', 'function_type')) {
                    $output_data['function_type'] = $functionType;
                }

                DB::table('employee_ipcr_outputs')->insert($output_data);
            }

            // Sync ipcr_details linkage (if table/columns exist)
            $this->syncIpcrDetails($employee, $id, $data['period'] ?? []);

            // Save audit trail
            $audit_data = [
                'user_id' => $user->id,
                'module' => 'Employee Portal',
                'menu' => 'IPCR',
                'activity' => $request->input('id', 0) == 0 ? 'Add' : 'Update',
                'description' => ($request->input('id', 0) == 0 ? 'Created' : 'Updated') . ' IPCR record',
            ];

            if (class_exists('App\Audit')) {
                \App\Audit::create($audit_data);
            }

            return $this->successResponse(['id' => $id], 'IPCR saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save IPCR: ' . $e->getMessage());
        }
    }

    /**
     * Employee Portal: Delete IPCR record
     */
    public function employeeDelete($id)
    {
        try {
            $user = Auth::user();

            // Get employee ID from user
            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee record not found');
            }

            $employee_id = $employee->id;

            // Verify ownership
            $ipcr = DB::table('employee_ipcr')
                ->where('id', $id)
                ->where('employee_id', $employee_id)
                ->first();

            if (!$ipcr) {
                return $this->errorResponse('IPCR record not found or access denied');
            }

            if (!$this->canEmployeeEditOwnIpcrSelfAssessment($user, $employee_id, $ipcr)) {
                return $this->errorResponse('This IPCR can no longer be deleted after supervisor or HR calibration.');
            }

            DB::beginTransaction();

            try {
                $this->deleteIpcrRelatedData((int) $id);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            // Save audit trail
            if (class_exists('App\Audit')) {
                \App\Audit::create([
                    'user_id' => $user->id,
                    'module' => 'Employee Portal',
                    'menu' => 'IPCR',
                    'activity' => 'Delete',
                    'description' => 'Deleted IPCR record',
                ]);
            }

            return $this->successResponse(null, 'IPCR deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete IPCR: ' . $e->getMessage());
        }
    }

    /**
     * Employee Portal: Print IPCR as PDF
     */
    public function employeePrint($id)
    {
        try {
            $user = Auth::user();

            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();
            if (!$employee) {
                return $this->errorResponse('Employee record not found');
            }

            $employee_id = $employee->id;

            // Reuse the same access model as employeeGet, but return raw record for printing.
            // 1) Determine role flags and allowed employee IDs
            $has_existing_ipcr = DB::table('employee_ipcr')
                ->where('employee_id', $employee_id)
                ->exists();

            $is_hr_user = $this->isHRUser($user);
            $is_pmt_user = $this->isPMTUser($employee_id);
            $is_agency_head_user = $this->isAgencyHeadUser($employee_id);

            $section_employee_ids = $this->getSectionEmployeeIdsForChief($employee_id);

            // Gate: require IPCR availability unless HR/PMT/agency head/section-chief with members or existing IPCRs
            if (empty($section_employee_ids) && !$is_hr_user && !$is_pmt_user && !$is_agency_head_user && !$has_existing_ipcr && !$this->isIpcrAvailableForEmployee($employee)) {
                return $this->errorResponse('IPCR is not available for your assignment.');
            }

            // 2) Build IPCR query with access scope
            $ipcr_query = DB::table('employee_ipcr')
                ->where('id', $id);

            $this->applyEmployeeIpcrAccessScope(
                $ipcr_query,
                $employee_id,
                $section_employee_ids,
                $is_hr_user,
                $is_pmt_user,
                $is_agency_head_user
            );

            $ipcr = $ipcr_query->first();
            if (!$ipcr) {
                return $this->errorResponse('IPCR record not found');
            }

            // Provide a section-based label for print view when `division` isn't stored
            if (!Schema::hasColumn('employee_ipcr', 'division')) {
                $ipcr->division = $this->getSectionNameForEmployeeId($ipcr->employee_id);
            }

            // For print, align with employeeGet logic: resolve IDs to display names when columns exist.
            $getEmployeeName = function ($emp_id) {
                if (!$emp_id) return null;
                $emp = DB::table('employees as a')
                    ->leftJoin('name_prefixes as b', 'a.name_prefix_id', '=', 'b.id')
                    ->leftJoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                    ->select(DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                        CONCAT(COALESCE(b.name,''), ' ', a.first_name, ' ', COALESCE(a.middle_name,''), ' ', a.last_name, ' ', COALESCE(c.name,''))
                    ELSE
                        CONCAT(COALESCE(b.name,''), ' ', RTRIM([dbo].[ufn_DecryptString](a.first_name,'" . env("APP_KEY", "") . "')), ' ',
                        RTRIM(COALESCE([dbo].[ufn_DecryptString](a.middle_name,'" . env("APP_KEY", "") . "'),'')), ' ',
                        RTRIM([dbo].[ufn_DecryptString](a.last_name,'" . env("APP_KEY", "") . "')), ' ', COALESCE(c.name,''))
                    END as name"))
                    ->where('a.id', $emp_id)
                    ->first();
                return $emp->name ?? null;
            };

            // Resolve employee/assessor/final rater names when only IDs are stored
            // Ratee: always derive from employee_id so it is reliable across schema changes.
            $ipcr->employee_name = $getEmployeeName($ipcr->employee_id);
            $this->hydrateIpcrSignatoriesForPrint($ipcr, (int) $ipcr->employee_id, $getEmployeeName);

            $outputs = DB::table('employee_ipcr_outputs')
                ->where('employee_ipcr_id', $id)
                ->when(
                    Schema::hasColumn('employee_ipcr_outputs', 'function_type'),
                    function ($query) {
                        $query->orderByRaw("CASE function_type WHEN 'core' THEN 1 WHEN 'strategic' THEN 2 WHEN 'support' THEN 3 ELSE 4 END");
                    }
                )
                ->orderBy('id', 'asc')
                ->get();

            $groupedOutputs = [
                'core' => $outputs->filter(fn ($row) => ($row->function_type ?? 'core') === 'core')->values(),
                'strategic' => $outputs->filter(fn ($row) => ($row->function_type ?? 'core') === 'strategic')->values(),
                'support' => $outputs->filter(fn ($row) => ($row->function_type ?? 'core') === 'support')->values(),
            ];

            $data = [
                'ipcr' => $ipcr,
                'outputs' => $outputs,
                'groupedOutputs' => $groupedOutputs,
            ];

            $pdf = Pdf::loadView('ipcr/ipcr_form', $data)->setPaper('A4', 'portrait');
            return $pdf->stream('ipcr_' . $id . '.pdf');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate IPCR PDF: ' . $e->getMessage());
        }
    }

    /**
     * Helper method to get form data for employee
     */
    private function getFormDataForEmployee($employee_id)
    {
        $app_key = env("APP_KEY", "");

        // Get employee's department
        $employee = DB::table('employees')
            ->where('id', $employee_id)
            ->first();

        $department = null;
        if ($employee && isset($employee->department_id) && $employee->department_id != 0) {
            $department = DB::table('departments')
                ->where('id', $employee->department_id)
                ->where('active', true)
                ->first();
        }

        // Get all employees for dropdowns
        $employees = DB::table('employees as a')
            ->leftJoin('name_prefixes as b', 'a.name_prefix_id', '=', 'b.id')
            ->leftJoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
            ->select(
                'a.id',
                'a.employee_no',
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                    CONCAT(COALESCE(b.name,''), ' ', a.first_name, ' ', COALESCE(a.middle_name,''), ' ', a.last_name, ' ', COALESCE(c.name,''))
                ELSE
                    CONCAT(COALESCE(b.name,''), ' ', RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')), ' ',
                    RTRIM(COALESCE([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),'')), ' ',
                    RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), ' ', COALESCE(c.name,''))
                END as name")
            )
            ->where([
                'a.is_employee' => true,
                'a.active' => true
            ])
            ->orderBy('a.last_name', 'asc')
            ->get();

        // Get employees under this department
        $division_employees = [];
        if ($department) {
            $division_employees = DB::table('employees as a')
                ->leftJoin('name_prefixes as b', 'a.name_prefix_id', '=', 'b.id')
                ->leftJoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                        CONCAT(COALESCE(b.name,''), ' ', a.first_name, ' ', COALESCE(a.middle_name,''), ' ', a.last_name, ' ', COALESCE(c.name,''))
                    ELSE
                        CONCAT(COALESCE(b.name,''), ' ', RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')), ' ',
                        RTRIM(COALESCE([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),'')), ' ',
                        RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), ' ', COALESCE(c.name,''))
                    END as name")
                )
                ->where([
                    'a.department_id' => $department->id,
                    'a.is_employee' => true,
                    'a.active' => true
                ])
                ->orderBy('a.last_name', 'asc')
                ->get();
        }

        return [
            'employees' => $employees,
            'department_employees' => $division_employees
        ];
    }

    /**
     * Resolve Head of Agency employee_id from the main branch.
     * Source: branches.branch_head_id where is_main_branch = 1.
     */
    private function getAgencyHeadEmployeeId()
    {
        if (!Schema::hasTable('branches') || !Schema::hasColumn('branches', 'branch_head_id')) {
            return null;
        }

        if (Schema::hasColumn('branches', 'is_main_branch')) {
            $headId = (int) DB::table('branches')->where('is_main_branch', 1)->value('branch_head_id');
            if ($headId > 0) {
                return $headId;
            }
        }

        $headId = (int) DB::table('branches')->where('id', '>', 0)->orderBy('id')->value('branch_head_id');

        return $headId > 0 ? $headId : null;
    }

    private function normalizeEmployeeId($employeeId)
    {
        $id = (int) ($employeeId ?? 0);

        return $id > 0 ? $id : null;
    }

    /**
     * Detect whether a DB column stores integer employee IDs (legacy schemas).
     */
    private function isIntegerColumn($table, $column)
    {
        static $cache = [];

        $cacheKey = $table . '.' . $column;
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return $cache[$cacheKey] = false;
        }

        try {
            $row = DB::selectOne(
                "SELECT DATA_TYPE AS data_type
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_NAME = ? AND COLUMN_NAME = ?",
                [$table, $column]
            );
            $dataType = strtolower($row->data_type ?? '');
            $cache[$cacheKey] = in_array($dataType, ['int', 'bigint', 'smallint', 'tinyint'], true);
        } catch (\Exception $e) {
            $cache[$cacheKey] = false;
        }

        return $cache[$cacheKey];
    }

    private function resolveStoredSignatoryNameFromPayload(array $data, $nameKey, $idKey)
    {
        $empId = $this->normalizeEmployeeId($data[$idKey] ?? null);
        if ($empId) {
            $employee = $this->getEmployeeNameById($empId);
            if ($employee && !empty($employee->name)) {
                return $employee->name;
            }
        }

        $name = $data[$nameKey] ?? null;

        return (is_string($name) && trim($name) !== '') ? trim($name) : null;
    }

    private function assignEmployeeIpcrSignatoryFields(array &$header_data, array $data)
    {
        $table = 'employee_ipcr';
        $assign = function ($column, $idColumn, $nameKey, $idKey) use (&$header_data, $data, $table) {
            $empId = $this->normalizeEmployeeId($data[$idKey] ?? null);

            if ($idColumn && Schema::hasColumn($table, $idColumn)) {
                $header_data[$idColumn] = $empId;
            }

            if (!Schema::hasColumn($table, $column)) {
                return;
            }

            if ($this->isIntegerColumn($table, $column)) {
                $header_data[$column] = $empId;
                return;
            }

            $header_data[$column] = $this->resolveStoredSignatoryNameFromPayload($data, $nameKey, $idKey);
        };

        $assign('reviewed_by', 'reviewed_by_employee_id', 'reviewedBy', 'reviewedByEmployeeId');
        $assign('approved_by', 'approved_by_employee_id', 'approvedBy', 'approvedByEmployeeId');
        $assign('assessed_by', 'assessed_by_employee_id', 'assessedBy', 'assessedByEmployeeId');
        $assign('final_rater', 'final_rater_employee_id', 'finalRater', 'finalRaterEmployeeId');

        if (Schema::hasColumn($table, 'reviewed_by_employee_id')
            && empty($header_data['reviewed_by_employee_id'] ?? null)
            && !empty($header_data['assessed_by_employee_id'] ?? null)) {
            $header_data['reviewed_by_employee_id'] = $header_data['assessed_by_employee_id'];
        }

        if (Schema::hasColumn($table, 'reviewed_by')) {
            $reviewedEmpty = empty($header_data['reviewed_by'] ?? null);
            if ($this->isIntegerColumn($table, 'reviewed_by')) {
                $reviewedEmpty = !$this->normalizeEmployeeId($header_data['reviewed_by'] ?? null);
            }

            if ($reviewedEmpty) {
                if ($this->isIntegerColumn($table, 'reviewed_by')
                    && !empty($header_data['assessed_by'] ?? null)
                    && $this->isIntegerColumn($table, 'assessed_by')) {
                    $header_data['reviewed_by'] = $header_data['assessed_by'];
                } elseif (!$this->isIntegerColumn($table, 'reviewed_by')
                    && !empty($header_data['assessed_by'] ?? null)) {
                    $header_data['reviewed_by'] = $header_data['assessed_by'];
                }
            }
        }
    }

    private function resolveSignatoryIdFromIpcrRecord($ipcr, $idColumn, $legacyColumn)
    {
        if ($idColumn && Schema::hasColumn('employee_ipcr', $idColumn)) {
            $id = $this->normalizeEmployeeId($ipcr->{$idColumn} ?? null);
            if ($id) {
                return $id;
            }
        }

        if ($legacyColumn
            && Schema::hasColumn('employee_ipcr', $legacyColumn)
            && $this->isIntegerColumn('employee_ipcr', $legacyColumn)) {
            return $this->normalizeEmployeeId($ipcr->{$legacyColumn} ?? null);
        }

        return null;
    }

    private function resolveSignatoryName($employeeId, $storedName, callable $getEmployeeName)
    {
        $id = (int) ($employeeId ?? 0);
        if ($id > 0) {
            $name = $getEmployeeName($id);
            if (!empty($name)) {
                return $name;
            }
        }

        if (!empty($storedName) && !is_numeric($storedName)) {
            return $storedName;
        }

        if (is_numeric($storedName) && (int) $storedName > 0) {
            return $getEmployeeName((int) $storedName);
        }

        return null;
    }

    private function getSectionChiefEmployeeIdForEmployee($employee_id)
    {
        if (!Schema::hasTable('sections') || !Schema::hasColumn('sections', 'section_chief_id')) {
            return null;
        }

        $sectionId = DB::table('employees')->where('id', $employee_id)->value('section_id');
        if (!$sectionId) {
            return null;
        }

        $chiefId = (int) DB::table('sections')->where('id', $sectionId)->value('section_chief_id');

        return $chiefId > 0 ? $chiefId : null;
    }

    private function getDepartmentHeadEmployeeIdForEmployee($employee_id)
    {
        if (!Schema::hasTable('departments') || !Schema::hasColumn('departments', 'employee_id')) {
            return null;
        }

        $departmentId = DB::table('employees')->where('id', $employee_id)->value('department_id');
        if (!$departmentId) {
            return null;
        }

        $headId = (int) DB::table('departments')->where('id', $departmentId)->value('employee_id');

        return $headId > 0 ? $headId : null;
    }

    private function getPmtMemberEmployeeIdForEmployee($employee_id)
    {
        if (!Schema::hasTable('pmt') || !Schema::hasColumn('pmt', 'is_ipcr')) {
            return null;
        }

        $sectionId = DB::table('employees')->where('id', $employee_id)->value('section_id');
        if (Schema::hasColumn('pmt', 'section_id') && $sectionId) {
            $pmtId = (int) DB::table('pmt')
                ->where('is_ipcr', 1)
                ->where('section_id', $sectionId)
                ->value('employee_id');
            if ($pmtId > 0) {
                return $pmtId;
            }
        }

        $pmtId = (int) DB::table('pmt')->where('is_ipcr', 1)->orderBy('id')->value('employee_id');

        return $pmtId > 0 ? $pmtId : null;
    }

    private function hydrateIpcrSignatoriesForPrint($ipcr, $rateeEmployeeId, callable $getEmployeeName)
    {
        $supervisorId = $this->getSectionChiefEmployeeIdForEmployee($rateeEmployeeId)
            ?: $this->getDepartmentHeadEmployeeIdForEmployee($rateeEmployeeId);

        $reviewedBy = $this->resolveSignatoryName(
            Schema::hasColumn('employee_ipcr', 'reviewed_by_employee_id') ? ($ipcr->reviewed_by_employee_id ?? null) : null,
            $ipcr->reviewed_by ?? null,
            $getEmployeeName
        );
        $assessedBy = $this->resolveSignatoryName(
            Schema::hasColumn('employee_ipcr', 'assessed_by_employee_id') ? ($ipcr->assessed_by_employee_id ?? null) : null,
            $ipcr->assessed_by ?? null,
            $getEmployeeName
        );

        if (empty($reviewedBy) && $assessedBy) {
            $reviewedBy = $assessedBy;
        }
        if (empty($assessedBy) && $reviewedBy) {
            $assessedBy = $reviewedBy;
        }
        if (empty($reviewedBy) && $supervisorId) {
            $reviewedBy = $getEmployeeName($supervisorId);
        }
        if (empty($assessedBy) && $supervisorId) {
            $assessedBy = $getEmployeeName($supervisorId);
        }

        $approvedBy = $this->resolveSignatoryName(
            Schema::hasColumn('employee_ipcr', 'approved_by_employee_id') ? ($ipcr->approved_by_employee_id ?? null) : null,
            $ipcr->approved_by ?? null,
            $getEmployeeName
        );
        if (empty($approvedBy)) {
            $agencyHeadId = $this->getAgencyHeadEmployeeId();
            if ($agencyHeadId) {
                $approvedBy = $getEmployeeName($agencyHeadId);
            }
        }

        $finalRater = $this->resolveSignatoryName(
            Schema::hasColumn('employee_ipcr', 'final_rater_employee_id') ? ($ipcr->final_rater_employee_id ?? null) : null,
            $ipcr->final_rater ?? null,
            $getEmployeeName
        );
        if (empty($finalRater)) {
            $pmtId = $this->getPmtMemberEmployeeIdForEmployee($rateeEmployeeId);
            if ($pmtId) {
                $finalRater = $getEmployeeName($pmtId);
            }
        }

        $ipcr->reviewed_by = $reviewedBy;
        $ipcr->assessed_by = $assessedBy;
        $ipcr->approved_by = $approvedBy;
        $ipcr->final_rater = $finalRater;

        return $ipcr;
    }

    /**
     * Delete IPCR child records across related tables before removing the header.
     */
    private function deleteIpcrRelatedData($ipcrId)
    {
        $outputIds = DB::table('employee_ipcr_outputs')
            ->where('employee_ipcr_id', $ipcrId)
            ->pluck('id');

        if (Schema::hasTable('ipcr_recalibrations') && $outputIds->isNotEmpty()) {
            DB::table('ipcr_recalibrations')
                ->whereIn('employee_ipcr_output_id', $outputIds)
                ->delete();
        }

        DB::table('employee_ipcr_outputs')
            ->where('employee_ipcr_id', $ipcrId)
            ->delete();

        if (Schema::hasTable('ipcr_details') && Schema::hasColumn('ipcr_details', 'employee_ipcr_id')) {
            DB::table('ipcr_details')
                ->where('employee_ipcr_id', $ipcrId)
                ->delete();
        }

        DB::table('employee_ipcr')
            ->where('id', $ipcrId)
            ->delete();
    }

    /**
     * Helper: fetch employee display name by ID (with decrypt handling)
     */
    private function getEmployeeNameById($id)
    {
        if (!$id) {
            return null;
        }

        $app_key = env("APP_KEY", "");

        return DB::table('employees as a')
            ->leftJoin('name_prefixes as b', 'a.name_prefix_id', '=', 'b.id')
            ->leftJoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
            ->select(
                'a.id',
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                    CONCAT(COALESCE(b.name,''), ' ', a.first_name, ' ', COALESCE(a.middle_name,''), ' ', a.last_name, ' ', COALESCE(c.name,''))
                ELSE
                    CONCAT(COALESCE(b.name,''), ' ', RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')), ' ',
                    RTRIM(COALESCE([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),'')), ' ',
                    RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), ' ', COALESCE(c.name,''))
                END as name")
            )
            ->where('a.id', $id)
            ->first();
    }

    /**
     * Helper: get IDs of employees for whom this employee acts as supervisor.
     * Primary rule: section chief (sections.section_chief_id).
     * Fallback/extension: department head (departments.employee_id) when sections are not used.
     */
    private function getSectionEmployeeIdsForChief($employee_id)
    {
        $ids = [];

        // Section-based supervision
        if (Schema::hasTable('sections') && Schema::hasColumn('sections', 'section_chief_id')) {
        $section_ids = DB::table('sections')
            ->where('section_chief_id', $employee_id)
            ->pluck('id')
            ->toArray();

            if (!empty($section_ids)) {
                $section_emp_ids = DB::table('employees')
                    ->whereIn('section_id', $section_ids)
                    ->where('is_employee', true)
                    ->where('active', true)
                    ->pluck('id')
                    ->toArray();

                $ids = array_merge($ids, $section_emp_ids);
            }
        }

        // Department-head supervision (for setups that tag heads at department level)
        if (Schema::hasTable('departments') && Schema::hasColumn('departments', 'employee_id')) {
            $department_ids = DB::table('departments')
                ->where('employee_id', $employee_id)
                ->pluck('id')
                ->toArray();

            if (!empty($department_ids)) {
                $dept_emp_ids = DB::table('employees')
                    ->whereIn('department_id', $department_ids)
            ->where('is_employee', true)
            ->where('active', true)
            ->pluck('id')
            ->toArray();

                $ids = array_merge($ids, $dept_emp_ids);
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Link employee_ipcr to ipcr_details (if table/columns exist)
     */
    private function syncIpcrDetails($employee, $employeeIpcrId, $period = [])
    {
        if (!Schema::hasTable('ipcr_details') || !Schema::hasColumn('ipcr_details', 'employee_ipcr_id')) {
            return;
        }

        $headerId = $this->resolveIpcrHeaderId($employee, $period);

        $data = [
            'employee_ipcr_id' => $employeeIpcrId,
            'ipcr_header_id' => $headerId ?? 0,
            'status_id' => 1,
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('ipcr_details', 'employee_id')) {
            $data['employee_id'] = $employee->id;
        }

        // upsert by employee_ipcr_id
        $exists = DB::table('ipcr_details')->where('employee_ipcr_id', $employeeIpcrId)->first();
        if ($exists) {
            DB::table('ipcr_details')
                ->where('employee_ipcr_id', $employeeIpcrId)
                ->update($data);
        } else {
            if (Schema::hasColumn('ipcr_details', 'created_at')) {
                $data['created_at'] = now();
            }
            DB::table('ipcr_details')->insert($data);
        }
    }

    /**
     * Find matching ipcr_headers.id for the employee and period (best-effort)
     */
    private function resolveIpcrHeaderId($employee, $period = [])
    {
        if (!Schema::hasTable('ipcr_headers')) {
            return null;
        }

        $departmentId = $employee->department_id ?? 0;
        $divisionId   = $employee->division_id ?? 0;
        $sectionId    = property_exists($employee, 'section_id') ? ($employee->section_id ?? 0) : 0;

        // derive year and months from period if available
        $periodStart = isset($period[0]) ? $period[0] : null;
        $periodEnd   = isset($period[1]) ? $period[1] : null;
        $year = $periodEnd ? date('Y', strtotime($periodEnd)) : ($periodStart ? date('Y', strtotime($periodStart)) : date('Y'));
        $startMonth = $periodStart ? (int)date('n', strtotime($periodStart)) : null;
        $endMonth   = $periodEnd ? (int)date('n', strtotime($periodEnd)) : null;

        $query = DB::table('ipcr_headers');

        // Availability/filtering is now SECTION-based. Only apply department filters when column exists.
        // This prevents SQL Server errors when `department_id` is not part of the schema.
        if (Schema::hasColumn('ipcr_headers', 'section_id')) {
            $query->where(function ($q) use ($sectionId) {
                $q->where('section_id', $sectionId)
                    ->orWhereNull('section_id')
                    ->orWhere('section_id', 0);
            });
        }

        if (Schema::hasColumn('ipcr_headers', 'division_id')) {
            $query->where(function ($q) use ($divisionId) {
                $q->where('division_id', $divisionId)
                    ->orWhereNull('division_id')
                    ->orWhere('division_id', 0);
            });
        }

        // Note: section_id filtering is handled above (preferred over department_id).

        if (Schema::hasColumn('ipcr_headers', 'year')) {
            $query->where('year', $year);
        }

        if ($startMonth && $endMonth && Schema::hasColumn('ipcr_headers', 'month_from') && Schema::hasColumn('ipcr_headers', 'month_to')) {
            $query->where('month_from', '<=', $startMonth)
                ->where('month_to', '>=', $endMonth);
        }

        if (Schema::hasColumn('ipcr_headers', 'is_posted')) {
            $query->where('is_posted', true);
        }

        return $query->orderByDesc('id')->value('id');
    }

    /**
     * Helper: determine if IPCR is available for the given employee based on ipcr_headers.
     *
     * Current rule (per latest requirement):
     *  - If `ipcr_headers.section_id` exists, IPCR is available when there is at least one
     *    posted row in `ipcr_headers` whose `section_id` matches the employee's `section_id`.
     *  - Backward compatible fallback: if `section_id` column does not exist, use `department_id`.
     */
    private function isIpcrAvailableForEmployee($employee)
    {
        if (!$employee) {
            return false;
        }

        // Prefer section-based availability when supported by schema
        if (Schema::hasColumn('ipcr_headers', 'section_id')) {
            $sectionId = $employee->section_id ?? 0;
            if ((int)$sectionId === 0) {
            return false;
        }

        $query = DB::table('ipcr_headers')
                ->where('section_id', $sectionId);

        // Only consider posted IPCR headers when the column exists
        if (Schema::hasColumn('ipcr_headers', 'is_posted')) {
            $query->where('is_posted', true);
        }

        return $query->exists();
        }

        // Section-only implementation: if the schema doesn't support section_id, treat as unavailable.
        return false;
    }

    /**
     * Check if current user is supervisor of the given employee.
     * Primary rule: section chief (sections.section_chief_id).
     * Fallback/extension: department head (departments.employee_id).
     */
    private function isSupervisorOfEmployee($current_employee_id, $target_employee_id)
    {
        $target_employee = DB::table('employees')
            ->where('id', $target_employee_id)
            ->first();

        if (!$target_employee) {
            return false;
        }

        // Section-based supervisor
        if (Schema::hasTable('sections') && Schema::hasColumn('sections', 'section_chief_id') && isset($target_employee->section_id)) {
        $section = DB::table('sections')
            ->where('id', $target_employee->section_id)
            ->where('section_chief_id', $current_employee_id)
            ->first();

            if ($section) {
                return true;
            }
        }

        // Department-head supervisor
        if (Schema::hasTable('departments') && Schema::hasColumn('departments', 'employee_id') && isset($target_employee->department_id)) {
            $dept = DB::table('departments')
                ->where('id', $target_employee->department_id)
                ->where('employee_id', $current_employee_id)
                ->first();

            if ($dept) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has HR access
     * Matches employee_no between users and employees tables
     */
    /**
     * Limit IPCR queries to records the user may view:
     * own IPCRs (+ section subordinates if chief), plus HR/PMT workflow queues.
     */
    private function applyEmployeeIpcrAccessScope($query, $employee_id, $section_employee_ids, $is_hr_user, $is_pmt_user, $is_agency_head_user = false, $columnPrefix = '')
    {
        $prefix = $columnPrefix ? rtrim($columnPrefix, '.') . '.' : '';
        $has_recalibration_status = Schema::hasColumn('employee_ipcr', 'recalibration_status');

        $scopeEmployeeIds = array_values(array_unique(array_merge(
            [(int) $employee_id],
            array_map('intval', $section_employee_ids ?? [])
        )));

        $query->where(function ($q) use ($prefix, $scopeEmployeeIds, $is_hr_user, $is_pmt_user, $is_agency_head_user, $has_recalibration_status) {
            $q->whereIn($prefix . 'employee_id', $scopeEmployeeIds);

            if ($is_agency_head_user && $has_recalibration_status) {
                $q->orWhereIn($prefix . 'recalibration_status', [
                    'supervisor_recalibrated',
                    'agency_head_approved',
                    'hr_recalibrated',
                    'pmt_recalibrated',
                ]);
            }

            if ($is_hr_user && $has_recalibration_status) {
                $q->orWhereIn($prefix . 'recalibration_status', [
                    'agency_head_approved',
                    'hr_recalibrated',
                    'pmt_recalibrated',
                ]);
            }

            if ($is_pmt_user && $has_recalibration_status) {
                $q->orWhereIn($prefix . 'recalibration_status', [
                    'hr_recalibrated',
                    'pmt_recalibrated',
                ]);
            }
        });

        return $query;
    }

    /**
     * True when the employee is the Head of Agency (main branch branch_head_id).
     */
    private function isAgencyHeadUser($employee_id)
    {
        $agencyHeadId = $this->getAgencyHeadEmployeeId();

        return $agencyHeadId && (int) $agencyHeadId === (int) $employee_id;
    }

    private function isHRUser($user)
    {
        // Check in users table by matching employee_no
        $user_record = DB::table('users')
            ->where('employee_no', $user->employee_no)
            ->first();

        if ($user_record) {
            // Check if with_hrm_access = 1 or is_admin = 1
            return (bool)($user_record->with_hrm_access ?? false) || (bool)($user_record->is_admin ?? false);
        }

        // Fallback to user object if query fails
        return (bool)($user->with_hrm_access ?? false) || (bool)($user->is_admin ?? false);
    }

    /**
     * Check if employee is a PMT member with IPCR access (is_ipcr = 1)
     */
    private function isPMTUser($employee_id)
    {
        if (!Schema::hasTable('pmt')) {
            return false;
        }

        $pmt_member = DB::table('pmt')
            ->where('employee_id', $employee_id)
            ->where('is_ipcr', 1)
            ->first();

        return $pmt_member !== null;
    }

    private function isEmployeeSelfAssessmentLockedStatus($status)
    {
        return in_array($status, [
            'supervisor_recalibrated',
            'agency_head_approved',
            'hr_recalibrated',
            'pmt_recalibrated',
        ], true);
    }

    private function canEmployeeEditOwnIpcrSelfAssessment($user, $employee_id, $ipcr)
    {
        if ($this->isHRUser($user) || $this->isPMTUser($employee_id)) {
            return true;
        }

        if ($ipcr && $this->isSupervisorOfEmployee($employee_id, $ipcr->employee_id ?? 0)) {
            return true;
        }

        if (!Schema::hasColumn('employee_ipcr', 'recalibration_status')) {
            return true;
        }

        $status = $ipcr->recalibration_status ?? 'self_assessment';

        return !$this->isEmployeeSelfAssessmentLockedStatus($status);
    }

    /**
     * Employee Portal: Head of Agency — list IPCRs pending/approved after supervisor calibration.
     */
    public function agencyHeadApprovalList()
    {
        try {
            $user = Auth::user();
            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee record not found');
            }

            if (!$this->isAgencyHeadUser($employee->id)) {
                return $this->forbiddenResponse('Only the Head of Agency may access IPCR approvals.');
            }

            if (!Schema::hasColumn('employee_ipcr', 'recalibration_status')) {
                return $this->successResponse([
                    'is_agency_head' => true,
                    'pending' => [],
                    'approved' => [],
                ], 'Agency head approval list retrieved');
            }

            $selectFields = [
                'a.id',
                'a.employee_id',
                'a.period_start',
                'a.period_end',
                'a.recalibration_status',
                'a.updated_at',
            ];
            if (Schema::hasColumn('employee_ipcr', 'agency_head_approved_at')) {
                $selectFields[] = 'a.agency_head_approved_at';
            }
            if (Schema::hasColumn('employee_ipcr', 'agency_head_approval_remarks')) {
                $selectFields[] = 'a.agency_head_approval_remarks';
            }

            $records = DB::table('employee_ipcr as a')
                ->whereIn('a.recalibration_status', [
                    'supervisor_recalibrated',
                    'agency_head_approved',
                    'hr_recalibrated',
                    'pmt_recalibrated',
                ])
                ->select($selectFields)
                ->orderBy('a.updated_at', 'desc')
                ->get();

            $employeeIds = $records->pluck('employee_id')->unique()->filter()->values()->all();
            $names = $this->buildEmployeeNameMap($employeeIds);

            $mapRecord = function ($record) use ($names) {
                $period = '';
                if ($record->period_start && $record->period_end) {
                    $period = date('M d', strtotime($record->period_start)) . ' - ' . date('M d, Y', strtotime($record->period_end));
                }

                return [
                    'id' => $record->id,
                    'employee_id' => $record->employee_id,
                    'employee_name' => $names[$record->employee_id] ?? '',
                    'period' => $period,
                    'period_start' => $record->period_start,
                    'period_end' => $record->period_end,
                    'recalibration_status' => $record->recalibration_status,
                    'agency_head_approved_at' => $record->agency_head_approved_at ?? null,
                    'agency_head_approval_remarks' => $record->agency_head_approval_remarks ?? null,
                    'updated_at' => $record->updated_at,
                ];
            };

            $pending = $records
                ->where('recalibration_status', 'supervisor_recalibrated')
                ->map($mapRecord)
                ->values();

            $approved = $records
                ->whereIn('recalibration_status', ['agency_head_approved', 'hr_recalibrated', 'pmt_recalibrated'])
                ->map($mapRecord)
                ->values();

            return $this->successResponse([
                'is_agency_head' => true,
                'pending' => $pending,
                'approved' => $approved,
            ], 'Agency head approval list retrieved');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve agency head approval list: ' . $e->getMessage());
        }
    }

    /**
     * Employee Portal: Head of Agency — approve or return an IPCR after supervisor calibration.
     */
    public function processAgencyHeadApproval(Request $request, $ipcrId)
    {
        try {
            $validator = validator($request->all(), [
                'action' => 'required|in:approve,return',
                'remarks' => 'nullable|string|max:2000',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $user = Auth::user();
            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee record not found');
            }

            if (!$this->isAgencyHeadUser($employee->id)) {
                return $this->forbiddenResponse('Only the Head of Agency may approve IPCR records.');
            }

            if (!Schema::hasColumn('employee_ipcr', 'recalibration_status')) {
                return $this->errorResponse('IPCR approval workflow is not available in this environment.');
            }

            $ipcr = DB::table('employee_ipcr')->where('id', $ipcrId)->first();
            if (!$ipcr) {
                return $this->notFoundResponse('IPCR record not found');
            }

            $current_status = $ipcr->recalibration_status ?? 'self_assessment';
            if ($current_status !== 'supervisor_recalibrated') {
                return $this->errorResponse('This IPCR is not awaiting Head of Agency approval.');
            }

            $action = $request->input('action');
            $remarks = trim((string) $request->input('remarks', ''));

            $update_data = [
                'updated_at' => now(),
            ];

            if ($action === 'approve') {
                $update_data['recalibration_status'] = 'agency_head_approved';
                if (Schema::hasColumn('employee_ipcr', 'agency_head_approved_by')) {
                    $update_data['agency_head_approved_by'] = $employee->id;
                }
                if (Schema::hasColumn('employee_ipcr', 'agency_head_approved_at')) {
                    $update_data['agency_head_approved_at'] = now();
                }
                if (Schema::hasColumn('employee_ipcr', 'agency_head_approval_remarks')) {
                    $update_data['agency_head_approval_remarks'] = $remarks !== '' ? $remarks : null;
                }
            } else {
                $update_data['recalibration_status'] = 'self_assessment';
                if (Schema::hasColumn('employee_ipcr', 'agency_head_approved_by')) {
                    $update_data['agency_head_approved_by'] = null;
                }
                if (Schema::hasColumn('employee_ipcr', 'agency_head_approved_at')) {
                    $update_data['agency_head_approved_at'] = null;
                }
                if (Schema::hasColumn('employee_ipcr', 'agency_head_approval_remarks')) {
                    $update_data['agency_head_approval_remarks'] = $remarks !== '' ? $remarks : null;
                }
            }

            DB::table('employee_ipcr')->where('id', $ipcrId)->update($update_data);

            if (class_exists('App\Audit')) {
                \App\Audit::create([
                    'user_id' => $user->id,
                    'module' => 'Employee Portal',
                    'menu' => 'IPCR',
                    'activity' => $action === 'approve' ? 'Approve' : 'Return',
                    'description' => 'Head of Agency ' . $action . 'd IPCR #' . $ipcrId,
                ]);
            }

            return $this->successResponse([
                'id' => (int) $ipcrId,
                'action' => $action,
                'recalibration_status' => $update_data['recalibration_status'],
            ], $action === 'approve'
                ? 'IPCR approved by Head of Agency. It may now proceed to HR recalibration.'
                : 'IPCR returned to employee for revision.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process agency head approval: ' . $e->getMessage());
        }
    }

    private function buildEmployeeNameMap(array $employeeIds)
    {
        $employeeIds = array_values(array_unique(array_filter(array_map('intval', $employeeIds))));
        if (empty($employeeIds)) {
            return [];
        }

        return DB::table('employees as e')
            ->leftJoin('name_prefixes as b', 'e.name_prefix_id', '=', 'b.id')
            ->leftJoin('name_suffixes as c', 'e.name_suffix_id', '=', 'c.id')
            ->whereIn('e.id', $employeeIds)
            ->pluck(DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                CONCAT(COALESCE(b.name,''), ' ', e.first_name, ' ', COALESCE(e.middle_name,''), ' ', e.last_name, ' ', COALESCE(c.name,''))
            ELSE
                CONCAT(COALESCE(b.name,''), ' ', RTRIM([dbo].[ufn_DecryptString](e.first_name,'" . env("APP_KEY", "") . "')), ' ',
                RTRIM(COALESCE([dbo].[ufn_DecryptString](e.middle_name,'" . env("APP_KEY", "") . "'),'')), ' ',
                RTRIM([dbo].[ufn_DecryptString](e.last_name,'" . env("APP_KEY", "") . "')), ' ', COALESCE(c.name,''))
            END as name"), 'e.id')
            ->toArray();
    }

    /**
     * Employee Portal: HR — list IPCRs pending/completed for HR recalibration.
     */
    public function hrRecalibrationList()
    {
        try {
            $user = Auth::user();
            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee record not found');
            }

            if (!$this->isHRUser($user)) {
                return $this->forbiddenResponse('Only HR users may access IPCR recalibration queue.');
            }

            if (!Schema::hasColumn('employee_ipcr', 'recalibration_status')) {
                return $this->successResponse([
                    'is_hr' => true,
                    'pending' => [],
                    'completed' => [],
                ], 'HR recalibration list retrieved');
            }

            $selectFields = [
                'a.id',
                'a.employee_id',
                'a.period_start',
                'a.period_end',
                'a.recalibration_status',
                'a.updated_at',
            ];
            if (Schema::hasColumn('employee_ipcr', 'agency_head_approved_at')) {
                $selectFields[] = 'a.agency_head_approved_at';
            }
            if (Schema::hasColumn('employee_ipcr', 'agency_head_approved_by')) {
                $selectFields[] = 'a.agency_head_approved_by';
            }
            if (Schema::hasColumn('employee_ipcr', 'agency_head_approval_remarks')) {
                $selectFields[] = 'a.agency_head_approval_remarks';
            }

            $records = DB::table('employee_ipcr as a')
                ->whereIn('a.recalibration_status', [
                    'agency_head_approved',
                    'hr_recalibrated',
                    'pmt_recalibrated',
                ])
                ->select($selectFields)
                ->orderBy('a.updated_at', 'desc')
                ->get();

            $employeeIds = $records->pluck('employee_id')->unique()->filter()->values()->all();
            $agencyHeadIds = $records->pluck('agency_head_approved_by')->unique()->filter()->values()->all();
            $allNameIds = array_values(array_unique(array_merge($employeeIds, $agencyHeadIds)));
            $names = $this->buildEmployeeNameMap($allNameIds);

            $mapRecord = function ($record) use ($names) {
                $period = '';
                if ($record->period_start && $record->period_end) {
                    $period = date('M d', strtotime($record->period_start)) . ' - ' . date('M d, Y', strtotime($record->period_end));
                }

                $row = [
                    'id' => $record->id,
                    'employee_id' => $record->employee_id,
                    'employee_name' => $names[$record->employee_id] ?? '',
                    'period' => $period,
                    'period_start' => $record->period_start,
                    'period_end' => $record->period_end,
                    'recalibration_status' => $record->recalibration_status,
                    'updated_at' => $record->updated_at,
                ];

                if (isset($record->agency_head_approved_at)) {
                    $row['agency_head_approved_at'] = $record->agency_head_approved_at;
                }
                if (isset($record->agency_head_approved_by)) {
                    $row['agency_head_approved_by'] = $record->agency_head_approved_by;
                    $row['agency_head_approved_by_name'] = $names[$record->agency_head_approved_by] ?? null;
                }
                if (isset($record->agency_head_approval_remarks)) {
                    $row['agency_head_approval_remarks'] = $record->agency_head_approval_remarks;
                }

                return $row;
            };

            $pending = $records
                ->where('recalibration_status', 'agency_head_approved')
                ->map($mapRecord)
                ->values();

            $completed = $records
                ->whereIn('recalibration_status', ['hr_recalibrated', 'pmt_recalibrated'])
                ->map($mapRecord)
                ->values();

            return $this->successResponse([
                'is_hr' => true,
                'pending' => $pending,
                'completed' => $completed,
            ], 'HR recalibration list retrieved');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve HR recalibration list: ' . $e->getMessage());
        }
    }

    /**
     * Employee Portal: Save recalibration (Supervisor/HR/PMT)
     */
    public function saveRecalibration(Request $request, $ipcrId)
    {
        try {
            $validator = validator($request->all(), [
                'recalibration_level' => 'required|in:supervisor,hr,pmt',
                'outputs' => 'required|array|min:1',
                'outputs.*.id' => 'required|integer|exists:employee_ipcr_outputs,id',
                'outputs.*.q' => 'required|integer|min:2|max:5',
                'outputs.*.e' => 'required|integer|min:2|max:5',
                'outputs.*.t' => 'required|integer|min:2|max:5',
                // A is computed from Q/E/T (may be sent as decimal, e.g. 3.67)
                'outputs.*.a' => 'nullable|numeric|min:2|max:5',
                'outputs.*.remarks' => 'nullable|string',
                'outputs.*.justification' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $user = Auth::user();

            // Get employee ID from user
            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee record not found');
            }

            $recalibrator_id = $employee->id;
            $recalibration_level = $request->input('recalibration_level');

            // Verify IPCR exists
            $ipcr = DB::table('employee_ipcr')
                ->where('id', $ipcrId)
                ->first();

            if (!$ipcr) {
                return $this->errorResponse('IPCR record not found');
            }

            // Verify authorization based on recalibration level
            $is_authorized = false;
            $ipcr_owner = DB::table('employees')
                ->where('id', $ipcr->employee_id)
                ->first();

            switch ($recalibration_level) {
                case 'supervisor':
                    // Check if user is section chief of the IPCR owner
                    $is_authorized = $this->isSupervisorOfEmployee($recalibrator_id, $ipcr->employee_id);
                    break;

                case 'hr':
                    // Check if user has HR access
                    $is_authorized = $this->isHRUser($user);
                    break;

                case 'pmt':
                    // Check if user is PMT member
                    $is_authorized = $this->isPMTUser($recalibrator_id);
                    break;
            }

            if (!$is_authorized) {
                return $this->errorResponse("You are not authorized to perform {$recalibration_level} recalibration.");
            }

            // Check workflow order: supervisor -> hr -> pmt
            // Get recalibration status (if column exists)
            $current_status = 'self_assessment'; // Default
            if (Schema::hasColumn('employee_ipcr', 'recalibration_status')) {
                $current_status = $ipcr->recalibration_status ?? 'self_assessment';
            }

            $allowed_levels = [
                'self_assessment' => ['supervisor'],
                'supervisor_recalibrated' => [],
                'agency_head_approved' => ['hr'],
                'hr_recalibrated' => ['pmt'],
                'pmt_recalibrated' => [], // No further recalibrations
            ];

            if (!in_array($recalibration_level, $allowed_levels[$current_status] ?? [])) {
                return $this->errorResponse("Cannot perform {$recalibration_level} recalibration. Current status: {$current_status}");
            }

            $data = $request->all();
            $processed = 0;

            DB::beginTransaction();

            try {
                $has_justification = Schema::hasColumn('ipcr_recalibrations', 'justification');
                // Pull status IDs from status table (fallback to sensible string defaults)
                $status_submitted_id = DB::table('status')->where('name', 'Submitted')->value('id');
                $status_recalibrated_id = DB::table('status')->where('name', 'Recalibrated')->value('id');

                foreach ($data['outputs'] as $output_data) {
                    $computedA = $this->computeAverageRating($output_data['q'] ?? null, $output_data['e'] ?? null, $output_data['t'] ?? null);
                    // Check if recalibration already exists for this level
                    $existing = DB::table('ipcr_recalibrations')
                        ->where('employee_ipcr_output_id', $output_data['id'])
                        ->where('recalibration_level', $recalibration_level)
                        ->first();

                    $recal_data = [
                        'employee_ipcr_output_id' => $output_data['id'],
                        'recalibrated_by_employee_id' => $recalibrator_id,
                        'recalibration_level' => $recalibration_level,
                        'quality_rating' => $this->normalizeRatingForStorage($output_data['q'] ?? null),
                        'efficiency_rating' => $this->normalizeRatingForStorage($output_data['e'] ?? null),
                        'timeliness_rating' => $this->normalizeRatingForStorage($output_data['t'] ?? null),
                        // A is the average of Q/E/T (rounded), not a manual input
                        'average_rating' => $computedA ?? $this->normalizeRatingForStorage($output_data['a'] ?? null),
                        'remarks' => $output_data['remarks'] ?? null,
                        // Status: supervisor/hr -> Submitted (id=2), pmt -> Recalibrated (id=3)
                        'status' => ($recalibration_level === 'pmt')
                            ? ($status_recalibrated_id ?? 'Recalibrated')
                            : ($status_submitted_id ?? 'Submitted'),
                        'updated_at' => now(),
                    ];

                    // Only include justification if column exists (backward compatible)
                    if ($has_justification) {
                        $recal_data['justification'] = $output_data['justification'] ?? null;
                    }

                    if ($existing) {
                        // Update existing recalibration
                        DB::table('ipcr_recalibrations')
                            ->where('id', $existing->id)
                            ->update($recal_data);
                    } else {
                        // Create new recalibration
                        $recal_data['created_at'] = now();
                        DB::table('ipcr_recalibrations')->insert($recal_data);
                    }
                    $processed++;
                }

                // Update IPCR status (only if columns exist)
                $status_map = [
                    'supervisor' => 'supervisor_recalibrated',
                    'hr' => 'hr_recalibrated',
                    'pmt' => 'pmt_recalibrated',
                ];

                $update_data = [
                    'updated_at' => now(),
                ];

                // Only update recalibration_status if column exists
                if (Schema::hasColumn('employee_ipcr', 'recalibration_status')) {
                    $update_data['recalibration_status'] = $status_map[$recalibration_level];
                }

                // Update timestamp columns if they exist
                if ($recalibration_level === 'supervisor' && Schema::hasColumn('employee_ipcr', 'supervisor_recalibrated_at')) {
                    $update_data['supervisor_recalibrated_at'] = now();
                } elseif ($recalibration_level === 'hr' && Schema::hasColumn('employee_ipcr', 'hr_recalibrated_at')) {
                    $update_data['hr_recalibrated_at'] = now();
                } elseif ($recalibration_level === 'pmt' && Schema::hasColumn('employee_ipcr', 'pmt_recalibrated_at')) {
                    $update_data['pmt_recalibrated_at'] = now();
                }

                DB::table('employee_ipcr')
                    ->where('id', $ipcrId)
                    ->update($update_data);

                DB::commit();

                // Save audit trail
                if (class_exists('App\Audit')) {
                    Audit::create([
                        'user_id' => $user->id,
                        'module' => 'Employee Portal',
                        'menu' => 'IPCR',
                        'activity' => 'Recalibrate',
                        'description' => ucfirst($recalibration_level) . " recalibrated IPCR #{$ipcrId} for employee #{$ipcr->employee_id}",
                    ]);
                }

                return $this->successResponse([
                    'processed' => $processed,
                    'new_status' => $status_map[$recalibration_level]
                ], ucfirst($recalibration_level) . ' recalibration saved successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save recalibration: ' . $e->getMessage());
        }
    }
}
