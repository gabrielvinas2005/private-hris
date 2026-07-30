<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Barryvdh\DomPDF\Facade\Pdf;

class DPCRController extends Controller
{
    use ApiResponse;

    /**
     * Resolve Agency Head (approver) employee_id for a given branch.
     * Source: branches.branch_head_id (FK to employees.id).
     */
    private function getAgencyHeadEmployeeIdForBranch($branch_id)
    {
        if (!Schema::hasTable('branches') || !Schema::hasColumn('branches', 'branch_head_id')) {
            return null;
        }

        $bid = (int)($branch_id ?? 0);
        if ($bid === 0) {
            // fallback to main branch if branch_id not set
            if (Schema::hasColumn('branches', 'is_main_branch')) {
                return DB::table('branches')->where('is_main_branch', 1)->value('branch_head_id');
            }
            return DB::table('branches')->orderBy('id', 'asc')->value('branch_head_id');
        }

        return DB::table('branches')->where('id', $bid)->value('branch_head_id');
    }

    private function getEmployeeDisplayNameById($id)
    {
        if (!$id) return null;
        $app_key = env("APP_KEY", "");
        $emp = DB::table('employees as a')
            ->leftJoin('name_prefixes as b', 'a.name_prefix_id', '=', 'b.id')
            ->leftJoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
            ->select(DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                CONCAT(COALESCE(b.name,''), ' ', a.first_name, ' ', COALESCE(a.middle_name,''), ' ', a.last_name, ' ', COALESCE(c.name,''))
            ELSE
                CONCAT(COALESCE(b.name,''), ' ', RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')), ' ',
                RTRIM(COALESCE([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),'')), ' ',
                RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), ' ', COALESCE(c.name,''))
            END as name"))
            ->where('a.id', $id)
            ->first();
        return $emp->name ?? null;
    }

    private function getEmployeePositionNameById($id)
    {
        if (!$id) return null;

        // Prefer positions table via employees.position_id when available
        if (Schema::hasTable('employees') && Schema::hasTable('positions') && Schema::hasColumn('employees', 'position_id')) {
            $pos = DB::table('employees as e')
                ->leftJoin('positions as p', 'e.position_id', '=', 'p.id')
                ->where('e.id', $id)
                ->value('p.name');
            if (!empty($pos)) return (string)$pos;
        }

        // Fallback: some schemas may store a text "position" column directly on employees
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'position')) {
            $pos = DB::table('employees')->where('id', $id)->value('position');
            if (!empty($pos)) return (string)$pos;
        }

        return null;
    }

    /**
     * Employee Portal: Print DPCR as PDF (employee_dpcr.id)
     */
    public function employeePrint($id)
    {
        try {
            $user = Auth::user();
            $employee = DB::table('employees')->where('employee_no', $user->employee_no)->first();
            if (!$employee) return $this->errorResponse('Employee record not found');

            $employee_id = (int)$employee->id;
            if (!Schema::hasTable('employee_dpcr')) return $this->errorResponse('DPCR records table not found');

            $dpcr = DB::table('employee_dpcr')->where('id', (int)$id)->first();
            if (!$dpcr) return $this->errorResponse('DPCR record not found');

            $unit_id = $this->resolveUnitIdFromRecord($dpcr);
            $posted_unit_ids = $this->getPostedDpcrUnitIds();
            $headed_division_ids = $this->getDivisionIdsHeadedBy($employee_id);
            $headed_tagged = array_values(array_intersect(array_map('intval', $headed_division_ids), array_map('intval', $posted_unit_ids)));
            $is_division_chief_tagged = ($unit_id && in_array($unit_id, array_map('intval', $headed_tagged), true));
            $is_pmt_tagged = ($unit_id && in_array($unit_id, array_map('intval', $posted_unit_ids), true) && $this->isPMTUser($employee_id, $unit_id));

            if (!$is_division_chief_tagged && !$is_pmt_tagged) {
                return $this->errorResponse('You do not have access to this DPCR record.');
            }

            $dept_name = $this->getUnitNameById($unit_id);

            $headName = $this->getEmployeeDisplayNameById($dpcr->employee_id ?? null);
            $headPosition = $this->getEmployeePositionNameById($dpcr->employee_id ?? null);
            $periodText = '';
            if (!empty($dpcr->period_start) && !empty($dpcr->period_end)) {
                $periodText = date('M d', strtotime($dpcr->period_start)) . ' - ' . date('M d, Y', strtotime($dpcr->period_end));
            }

            // Outputs
            $outputs = [];
            if (Schema::hasTable('employee_dpcr_outputs') && Schema::hasColumn('employee_dpcr_outputs', 'employee_dpcr_id')) {
                $hasTimeliness = Schema::hasColumn('employee_dpcr_outputs', 'timeliness_rating');
                $hasEffectiveness = Schema::hasColumn('employee_dpcr_outputs', 'effectiveness_rating');

                $rows = DB::table('employee_dpcr_outputs')
                    ->where('employee_dpcr_id', (int)$id)
                    ->when(
                        $this->hasDpcrOutputFunctionTypeColumn(),
                        function ($query) {
                            $query->orderByRaw("CASE function_type WHEN 'core' THEN 1 WHEN 'strategic' THEN 2 WHEN 'support' THEN 3 ELSE 4 END");
                        }
                    )
                    ->orderBy('id', 'asc')
                    ->get();

                $outputs = $rows->map(function ($r) use ($hasTimeliness, $hasEffectiveness) {
                    $t = null;
                    if ($hasTimeliness) $t = $r->timeliness_rating ?? null;
                    elseif ($hasEffectiveness) $t = $r->effectiveness_rating ?? null;
                    return (object)[
                        'function_type' => $this->hasDpcrOutputFunctionTypeColumn()
                            ? $this->normalizeDpcrFunctionType($r->function_type ?? 'core')
                            : 'core',
                        'outputs' => $r->Outputs ?? '',
                        'target_measures' => $r->Target_measures ?? '',
                        'allotted_budget' => $r->alloted_budget ?? null,
                        'accountable' => $r->div_indiv_accountable ?? '',
                        'actual_accomplishments' => $r->actual_accomplishments ?? '',
                        'q' => $this->normalizeRatingForApi($r->quality_rating),
                        'e' => $this->normalizeRatingForApi($r->efficiency_rating),
                        't' => $this->normalizeRatingForApi($t),
                        'a' => $this->normalizeRatingForApi($r->average_rating)
                            ?? $this->computeAverageRating($r->quality_rating, $r->efficiency_rating, $t),
                        'remarks' => $r->remarks ?? '',
                    ];
                })->toArray();
            }

            // Signatories
            $planningOfficerName = !empty($dpcr->planning_officer_id) ? $this->getEmployeeDisplayNameById($dpcr->planning_officer_id) : null;
            $pmtName = !empty($dpcr->assessed_by_employee_id) ? $this->getEmployeeDisplayNameById($dpcr->assessed_by_employee_id) : ($dpcr->assessed_by ?? null);
            $agencyHeadName = !empty($dpcr->approved_by_employee_id) ? $this->getEmployeeDisplayNameById($dpcr->approved_by_employee_id) : ($dpcr->approved_by ?? null);
            $finalRaterName = !empty($dpcr->final_rater_employee_id) ? $this->getEmployeeDisplayNameById($dpcr->final_rater_employee_id) : ($agencyHeadName ?? ($dpcr->final_rater ?? null));

            $groupedOutputs = [
                'strategic' => collect($outputs)->filter(fn ($row) => ($row->function_type ?? 'core') === 'strategic')->values(),
                'core' => collect($outputs)->filter(fn ($row) => ($row->function_type ?? 'core') === 'core')->values(),
                'support' => collect($outputs)->filter(fn ($row) => ($row->function_type ?? 'core') === 'support')->values(),
            ];

            $pdf = Pdf::loadView('dpcr.dpcr_form', [
                'dpcr' => $dpcr,
                'departmentName' => $dept_name,
                'outputs' => $outputs,
                'groupedOutputs' => $groupedOutputs,
                'headName' => $headName,
                'headPosition' => $headPosition,
                'periodText' => $periodText,
                'planningOfficerName' => $planningOfficerName,
                'pmtName' => $pmtName,
                'agencyHeadName' => $agencyHeadName,
                'finalRaterName' => $finalRaterName,
            ])->setPaper('a4', 'portrait');

            return $pdf->stream("dpcr_{$id}.pdf");
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to print DPCR: ' . $e->getMessage());
        }
    }

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

    /**
     * employee_dpcr signatory date columns may be NOT NULL in legacy schemas.
     */
    private function resolveDpcrSignatoryDate($value, ...$fallbacks)
    {
        if (!empty($value)) {
            return $value;
        }

        foreach ($fallbacks as $fallback) {
            if (!empty($fallback)) {
                return $fallback;
            }
        }

        return now()->toDateString();
    }

    private function hasDpcrOutputFunctionTypeColumn(): bool
    {
        static $has = null;
        if ($has === null) {
            $has = Schema::hasTable('employee_dpcr_outputs')
                && Schema::hasColumn('employee_dpcr_outputs', 'function_type');
        }

        return $has;
    }

    private function normalizeDpcrFunctionType($type): string
    {
        $normalized = strtolower(trim((string)($type ?? 'core')));
        return in_array($normalized, ['core', 'strategic', 'support'], true) ? $normalized : 'core';
    }

    private function isHRUser($user)
    {
        $user_record = DB::table('users')->where('employee_no', $user->employee_no)->first();
        if ($user_record) {
            return (bool)($user_record->with_hrm_access ?? false) || (bool)($user_record->is_admin ?? false);
        }
        return (bool)($user->with_hrm_access ?? false) || (bool)($user->is_admin ?? false);
    }

    private function getDpcrUnitColumn($table)
    {
        if (!Schema::hasTable($table)) return null;
        if (Schema::hasColumn($table, 'division_id')) return 'division_id';
        if (Schema::hasColumn($table, 'division')) return 'division';
        if (Schema::hasColumn($table, 'department_id')) return 'department_id';
        if (Schema::hasColumn($table, 'department')) return 'department';
        return null;
    }

    private function getPmtUnitColumn()
    {
        if (!Schema::hasTable('pmt')) return null;
        if (Schema::hasColumn('pmt', 'division_id')) return 'division_id';
        if (Schema::hasColumn('pmt', 'department_id')) return 'department_id';
        if (Schema::hasColumn('pmt', 'department')) return 'department';
        return null;
    }

    private function resolveUnitIdFromRecord($record)
    {
        if (!$record) return 0;
        $col = $this->getDpcrUnitColumn('employee_dpcr');
        if ($col && isset($record->{$col})) {
            return (int)$record->{$col};
        }
        if (isset($record->department)) {
            return (int)$record->department;
        }
        return 0;
    }

    private function getUnitNameById($unit_id)
    {
        if (!$unit_id) return 'N/A';
        if (Schema::hasTable('divisions')) {
            $name = DB::table('divisions')->where('id', $unit_id)->value('name');
            if (!empty($name)) return (string)$name;
        }
        if (Schema::hasTable('departments')) {
            return (string)(DB::table('departments')->where('id', $unit_id)->value('name') ?? 'N/A');
        }
        return 'N/A';
    }

    private function getPostedDpcrUnitIds()
    {
        if (!Schema::hasTable('dpcr_headers')) return [];
        $unitCol = $this->getDpcrUnitColumn('dpcr_headers');
        if (!$unitCol) return [];

        $q = DB::table('dpcr_headers');
        if (Schema::hasColumn('dpcr_headers', 'is_posted')) $q->where('is_posted', 1);
        return $q->whereNotNull($unitCol)->pluck($unitCol)->map(fn($x) => (int)$x)->unique()->values()->toArray();
    }

    private function getPmtUnitIdsForEmployee($employee_id)
    {
        if (!Schema::hasTable('pmt')) return [];
        $unitCol = $this->getPmtUnitColumn();
        if (!$unitCol) return [];

        $q = DB::table('pmt')->where('employee_id', $employee_id);
        if (Schema::hasColumn('pmt', 'is_dpcr')) $q->where('is_dpcr', 1);
        return $q->whereNotNull($unitCol)->pluck($unitCol)->map(fn($x) => (int)$x)->unique()->values()->toArray();
    }

    private function isPMTUser($employee_id, $unit_id = null)
    {
        if (!Schema::hasTable('pmt')) return false;
        $q = DB::table('pmt')->where('employee_id', $employee_id);
        if (Schema::hasColumn('pmt', 'is_dpcr')) $q->where('is_dpcr', 1);
        if ($unit_id) {
            $unitCol = $this->getPmtUnitColumn();
            if ($unitCol) $q->where($unitCol, (int)$unit_id);
        }
        return $q->exists();
    }

    private function isDivisionChief($employee_id)
    {
        if (!Schema::hasTable('divisions') || !Schema::hasColumn('divisions', 'division_chief_id')) return false;
        return DB::table('divisions')->where('division_chief_id', $employee_id)->where('active', true)->exists();
    }

    private function getDivisionIdsHeadedBy($employee_id)
    {
        if (!Schema::hasTable('divisions') || !Schema::hasColumn('divisions', 'division_chief_id')) return [];
        return DB::table('divisions')
            ->where('division_chief_id', $employee_id)
            ->where('active', true)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Employee is tagged in dpcr_details for at least one posted dpcr_header.
     */
    private function hasAccessViaDpcrDetails($employee_id)
    {
        if (!Schema::hasTable('dpcr_details') || !Schema::hasTable('dpcr_headers')) {
            return false;
        }
        if (!Schema::hasColumn('dpcr_details', 'employee_id') || !Schema::hasColumn('dpcr_details', 'dpcr_header_id')) {
            return false;
        }

        $header_ids = DB::table('dpcr_details')
            ->where('employee_id', $employee_id)
            ->whereNotNull('dpcr_header_id')
            ->distinct()
            ->pluck('dpcr_header_id')
            ->filter()
            ->values()
            ->toArray();

        if (empty($header_ids)) {
            return false;
        }

        $q = DB::table('dpcr_headers')->whereIn('id', $header_ids);
        if (Schema::hasColumn('dpcr_headers', 'is_posted')) {
            $q->where('is_posted', 1);
        }

        return $q->exists();
    }

    /**
     * Division IDs from posted dpcr_headers where the employee is tagged in dpcr_details.
     */
    private function getPostedUnitIdsFromDpcrDetails($employee_id)
    {
        if (!Schema::hasTable('dpcr_details') || !Schema::hasTable('dpcr_headers')) {
            return [];
        }
        if (!Schema::hasColumn('dpcr_details', 'employee_id') || !Schema::hasColumn('dpcr_details', 'dpcr_header_id')) {
            return [];
        }

        $header_ids = DB::table('dpcr_details')
            ->where('employee_id', $employee_id)
            ->whereNotNull('dpcr_header_id')
            ->distinct()
            ->pluck('dpcr_header_id')
            ->filter()
            ->values()
            ->toArray();

        if (empty($header_ids)) {
            return [];
        }

        $unitCol = $this->getDpcrUnitColumn('dpcr_headers');
        if (!$unitCol) {
            return [];
        }

        $q = DB::table('dpcr_headers')->whereIn('id', $header_ids);
        if (Schema::hasColumn('dpcr_headers', 'is_posted')) {
            $q->where('is_posted', 1);
        }

        return $q->whereNotNull($unitCol)
            ->pluck($unitCol)
            ->map(fn($x) => (int)$x)
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Employee Portal: Check if user has access to DPCR
     * Rule: Show only when a posted dpcr_header exists and the user must complete it
     * (division chief, PMT for that division, or tagged in dpcr_details).
     */
    public function checkAccess()
    {
        try {
            $user = Auth::user();
            $employee = DB::table('employees')->where('employee_no', $user->employee_no)->first();
            if (!$employee) return $this->errorResponse('Employee record not found');

            $employee_id = $employee->id;
            $posted_dept_ids = $this->getPostedDpcrUnitIds();
            $headed_dept_ids = $this->getDivisionIdsHeadedBy($employee_id);
            $pmt_dept_ids = $this->getPmtUnitIdsForEmployee($employee_id);

            $headed_tagged = array_values(array_intersect(array_map('intval', $headed_dept_ids), array_map('intval', $posted_dept_ids)));
            $pmt_tagged = array_values(array_intersect(array_map('intval', $pmt_dept_ids), array_map('intval', $posted_dept_ids)));

            $is_division_chief_tagged = !empty($headed_tagged);
            $is_pmt_tagged = !empty($pmt_tagged);
            $is_details_tagged = $this->hasAccessViaDpcrDetails($employee_id);

            return $this->successResponse([
                'is_hr' => false,
                'is_pmt' => $is_pmt_tagged,
                'is_division_chief' => $is_division_chief_tagged,
                'is_department_head' => $is_division_chief_tagged,
                'has_access' => $is_division_chief_tagged || $is_pmt_tagged || $is_details_tagged
            ], 'Access check completed');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to check access: ' . $e->getMessage());
        }
    }

    /**
     * Employee Portal: Get DPCR records visible to current user.
     * If existing DPCR created, show it to the division chief of that division.
     */
    public function employeeList()
    {
        try {
            $user = Auth::user();
            $employee = DB::table('employees')->where('employee_no', $user->employee_no)->first();
            if (!$employee) return $this->errorResponse('Employee record not found');

            $employee_id = $employee->id;
            $posted_unit_ids = $this->getPostedDpcrUnitIds();
            $headed_division_ids = $this->getDivisionIdsHeadedBy($employee_id);
            $pmt_unit_ids = $this->getPmtUnitIdsForEmployee($employee_id);

            $headed_tagged = array_values(array_intersect(array_map('intval', $headed_division_ids), array_map('intval', $posted_unit_ids)));
            $pmt_tagged = array_values(array_intersect(array_map('intval', $pmt_unit_ids), array_map('intval', $posted_unit_ids)));
            $details_tagged = $this->getPostedUnitIdsFromDpcrDetails($employee_id);
            $visible_unit_ids = array_values(array_unique(array_merge($headed_tagged, $pmt_tagged, $details_tagged)));

            if (empty($visible_unit_ids)) {
                return $this->successResponse([], 'DPCR records retrieved successfully');
            }

            // IMPORTANT: Only show "existing records" from employee_dpcr.
            // dpcr_headers is just the posted setup/assignment and should not appear as a saved record.
            if (!Schema::hasTable('employee_dpcr')) {
                return $this->successResponse([], 'DPCR records retrieved successfully');
            }

            $unitCol = $this->getDpcrUnitColumn('employee_dpcr');
            if (!$unitCol) {
                return $this->errorResponse('DPCR division column is missing in employee_dpcr.');
            }

            $q = DB::table('employee_dpcr as a')
                ->select('a.id', 'a.' . $unitCol, 'a.period_start', 'a.period_end', 'a.created_at', 'a.updated_at')
                ->whereIn('a.' . $unitCol, $visible_unit_ids);

            $records = $q->orderBy('a.created_at', 'desc')->get();

            $formatted = $records->map(function ($r) {
                $unit_id = $this->resolveUnitIdFromRecord($r);
                $dept_name = $this->getUnitNameById($unit_id);
                $period = '';
                if ($r->period_start && $r->period_end) {
                    $period = date('M d', strtotime($r->period_start)) . ' - ' . date('M d, Y', strtotime($r->period_end));
                }
                return [
                    'id' => $r->id,
                    'division' => $dept_name,
                    'period' => $period,
                    'period_start' => $r->period_start,
                    'period_end' => $r->period_end,
                    'created_at' => $r->created_at,
                    'updated_at' => $r->updated_at,
                ];
            });

            return $this->successResponse($formatted, 'DPCR records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve DPCR records: ' . $e->getMessage());
        }
    }

    /**
     * Employee Portal: Get single saved DPCR record (employee_dpcr.id) with outputs.
     * Access:
     * - HR/PMT can view
     * - Otherwise must be a division chief of the record's division (or owner/creator).
     */
    public function employeeGet($id)
    {
        try {
            $user = Auth::user();
            $employee = DB::table('employees')->where('employee_no', $user->employee_no)->first();
            if (!$employee) return $this->errorResponse('Employee record not found');

            $employee_id = $employee->id;
            $posted_unit_ids = $this->getPostedDpcrUnitIds();
            $headed_division_ids = $this->getDivisionIdsHeadedBy($employee_id);
            $headed_tagged = array_values(array_intersect(array_map('intval', $headed_division_ids), array_map('intval', $posted_unit_ids)));

            if (!Schema::hasTable('employee_dpcr')) {
                return $this->errorResponse('DPCR records table not found');
            }

            $dpcr = DB::table('employee_dpcr')->where('id', (int)$id)->first();
            if (!$dpcr) return $this->errorResponse('DPCR record not found');

            $unit_id = $this->resolveUnitIdFromRecord($dpcr);
            $unit_name = $this->getUnitNameById($unit_id);

            $details_tagged = $this->getPostedUnitIdsFromDpcrDetails($employee_id);
            $is_division_chief_tagged = ($unit_id && in_array($unit_id, array_map('intval', $headed_tagged), true));
            $is_pmt_tagged = ($unit_id && in_array($unit_id, array_map('intval', $posted_unit_ids), true) && $this->isPMTUser($employee_id, $unit_id));
            $is_details_tagged = ($unit_id && in_array($unit_id, array_map('intval', $details_tagged), true));
            if (!$is_division_chief_tagged && !$is_pmt_tagged && !$is_details_tagged) {
                return $this->errorResponse('You do not have access to this DPCR.');
            }

            // Outputs
            $outputs = [];
            if (Schema::hasTable('employee_dpcr_outputs') && Schema::hasColumn('employee_dpcr_outputs', 'employee_dpcr_id')) {
                $hasTimeliness = Schema::hasColumn('employee_dpcr_outputs', 'timeliness_rating');
                $hasEffectiveness = Schema::hasColumn('employee_dpcr_outputs', 'effectiveness_rating');

                $rows = DB::table('employee_dpcr_outputs')
                    ->where('employee_dpcr_id', (int)$id)
                    ->when(
                        $this->hasDpcrOutputFunctionTypeColumn(),
                        function ($query) {
                            $query->orderByRaw("CASE function_type WHEN 'core' THEN 1 WHEN 'strategic' THEN 2 WHEN 'support' THEN 3 ELSE 4 END");
                        }
                    )
                    ->orderBy('id', 'asc')
                    ->get();

                // Pull recalibrations for this DPCR (optional)
                $recalByOutput = [];
                $recalOutputCol = null;
                if (Schema::hasTable('dpcr_recalibrations')) {
                    // Try to find the linking column
                    foreach (['employee_dpcr_output_id', 'dpcr_output_id', 'output_id'] as $col) {
                        if (Schema::hasColumn('dpcr_recalibrations', $col)) {
                            $recalOutputCol = $col;
                            break;
                        }
                    }

                    $recalQ = DB::table('dpcr_recalibrations')->where('employee_dpcr_id', (int)$id);
                    if ($recalOutputCol) {
                        $recalRows = $recalQ->get();
                        foreach ($recalRows as $rr) {
                            $key = (int)($rr->{$recalOutputCol} ?? 0);
                            if (!$key) continue;
                            if (!isset($recalByOutput[$key])) $recalByOutput[$key] = [];
                            $recalByOutput[$key][] = $rr;
                        }
                    }
                }

                $outputs = $rows->map(function ($r) use ($hasTimeliness, $hasEffectiveness, $recalByOutput) {
                    $t = null;
                    if ($hasTimeliness) $t = $r->timeliness_rating ?? null;
                    elseif ($hasEffectiveness) $t = $r->effectiveness_rating ?? null;

                    $outId = (int)($r->id ?? 0);
                    $hrRecal = null;
                    $pmtRecal = null;
                    if ($outId && isset($recalByOutput[$outId])) {
                        $hr = collect($recalByOutput[$outId])->first(fn($x) => ($x->recalibration_level ?? null) === 'hr');
                        $pmt = collect($recalByOutput[$outId])->first(fn($x) => ($x->recalibration_level ?? null) === 'pmt');
                        if ($hr) {
                            $hrT = null;
                            if (property_exists($hr, 'timeliness_rating')) $hrT = $hr->timeliness_rating ?? null;
                            elseif (property_exists($hr, 'effectiveness_rating')) $hrT = $hr->effectiveness_rating ?? null;
                            $hrRecal = [
                                'q' => $this->normalizeRatingForApi($hr->quality_rating),
                                'e' => $this->normalizeRatingForApi($hr->efficiency_rating),
                                't' => $this->normalizeRatingForApi($hrT),
                                'a' => $this->normalizeRatingForApi($hr->average_rating ?? null)
                                    ?? $this->computeAverageRating($hr->quality_rating, $hr->efficiency_rating, $hrT),
                                'remarks' => $hr->remarks ?? null,
                            ];
                        }
                        if ($pmt) {
                            $pmtT = null;
                            if (property_exists($pmt, 'timeliness_rating')) $pmtT = $pmt->timeliness_rating ?? null;
                            elseif (property_exists($pmt, 'effectiveness_rating')) $pmtT = $pmt->effectiveness_rating ?? null;
                            $pmtRecal = [
                                'q' => $this->normalizeRatingForApi($pmt->quality_rating),
                                'e' => $this->normalizeRatingForApi($pmt->efficiency_rating),
                                't' => $this->normalizeRatingForApi($pmtT),
                                'a' => $this->normalizeRatingForApi($pmt->average_rating ?? null)
                                    ?? $this->computeAverageRating($pmt->quality_rating, $pmt->efficiency_rating, $pmtT),
                                'remarks' => $pmt->remarks ?? null,
                            ];
                        }
                    }

                    return [
                        'id' => $outId,
                        'functionType' => $this->hasDpcrOutputFunctionTypeColumn()
                            ? $this->normalizeDpcrFunctionType($r->function_type ?? 'core')
                            : 'core',
                        'outputs' => $r->Outputs ?? '',
                        'targetMeasures' => $r->Target_measures ?? '',
                        'allottedBudget' => $r->alloted_budget ?? null,
                        'divIndivAccountable' => $r->div_indiv_accountable ?? '',
                        'actualAccomplishments' => $r->actual_accomplishments ?? '',
                        'q' => $this->normalizeRatingForApi($r->quality_rating),
                        'e' => $this->normalizeRatingForApi($r->efficiency_rating),
                        't' => $this->normalizeRatingForApi($t),
                        'a' => $this->normalizeRatingForApi($r->average_rating)
                            ?? $this->computeAverageRating($r->quality_rating, $r->efficiency_rating, $t),
                        'remarks' => $r->remarks ?? '',
                        'hr_recalibration' => $hrRecal,
                        'pmt_recalibration' => $pmtRecal,
                    ];
                })->toArray();
            }

            // Signatories (prefer *_employee_id if present)
            $planning_id = $dpcr->planning_officer_id ?? null;
            $approved_id = $dpcr->approved_by_employee_id ?? null;
            $assessed_id = $dpcr->assessed_by_employee_id ?? null;
            $final_id = $dpcr->final_rater_employee_id ?? null;

            return $this->successResponse([
                'id' => (int)$dpcr->id,
                'divisionId' => $unit_id ?: null,
                'division' => $unit_name,
                'departmentId' => $unit_id ?: null,
                'department' => $unit_name,
                'period' => [$dpcr->period_start ?? null, $dpcr->period_end ?? null],
                'planningOfficerEmployeeId' => $planning_id ? (int)$planning_id : null,
                'planningOfficerDate' => $dpcr->planning_officer_date ?? null,
                'approvedByEmployeeId' => $approved_id ? (int)$approved_id : null,
                'approvedDate' => $dpcr->approved_date ?? null,
                'assessedByEmployeeId' => $assessed_id ? (int)$assessed_id : null,
                'assessedDate' => $dpcr->assessed_date ?? null,
                'finalRaterEmployeeId' => $final_id ? (int)$final_id : null,
                'finalRateDate' => $dpcr->final_rater_date ?? null,
                'outputs' => $outputs,
                'is_hr' => false,
                'is_pmt' => $is_pmt_tagged,
            ], 'DPCR record retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve DPCR record: ' . $e->getMessage());
        }
    }

    /**
     * Employee Portal: Save DPCR recalibration (PMT only)
     * Uses dpcr_recalibrations table.
     */
    public function saveRecalibration(Request $request, $dpcrId)
    {
        try {
            $validator = validator($request->all(), [
                'recalibration_level' => 'required|in:pmt',
                'outputs' => 'required|array|min:1',
                'outputs.*.id' => 'required|integer|exists:employee_dpcr_outputs,id',
                'outputs.*.q' => 'nullable|numeric|min:2|max:5',
                'outputs.*.e' => 'nullable|numeric|min:2|max:5',
                'outputs.*.t' => 'nullable|numeric|min:2|max:5',
                'outputs.*.a' => 'nullable|numeric|min:2|max:5',
                'outputs.*.remarks' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $user = Auth::user();
            $employee = DB::table('employees')->where('employee_no', $user->employee_no)->first();
            if (!$employee) return $this->errorResponse('Employee record not found');

            $recalibrator_id = (int)$employee->id;
            $recalibration_level = $request->input('recalibration_level');

            // Verify DPCR exists
            if (!Schema::hasTable('employee_dpcr')) return $this->errorResponse('DPCR records table not found');
            $dpcr = DB::table('employee_dpcr')->where('id', (int)$dpcrId)->first();
            if (!$dpcr) return $this->errorResponse('DPCR record not found');

            // Authorization
            $is_authorized = false;
            if ($recalibration_level === 'pmt') $is_authorized = $this->isPMTUser($recalibrator_id);
            if (!$is_authorized) {
                return $this->errorResponse("You are not authorized to perform {$recalibration_level} recalibration.");
            }

            if (!Schema::hasTable('dpcr_recalibrations')) {
                return $this->errorResponse('DPCR recalibrations table not found');
            }

            // Determine which column stores timeliness in dpcr_recalibrations
            $hasTimeliness = Schema::hasColumn('dpcr_recalibrations', 'timeliness_rating');
            $hasEffectiveness = Schema::hasColumn('dpcr_recalibrations', 'effectiveness_rating');

            // Determine output linking column (optional)
            $outputCol = null;
            foreach (['employee_dpcr_output_id', 'dpcr_output_id', 'output_id'] as $col) {
                if (Schema::hasColumn('dpcr_recalibrations', $col)) {
                    $outputCol = $col;
                    break;
                }
            }
            if (!$outputCol) {
                return $this->errorResponse('DPCR recalibrations table is missing the output linking column (e.g. employee_dpcr_output_id).');
            }

            $data = $request->all();
            $processed = 0;

            DB::beginTransaction();
            try {
                $status_submitted_id = DB::table('status')->where('name', 'Submitted')->value('id');
                $status_recalibrated_id = DB::table('status')->where('name', 'Recalibrated')->value('id');

                foreach ($data['outputs'] as $out) {
                    $outId = (int)($out['id'] ?? 0);

                    $computedA = $this->computeAverageRating($out['q'] ?? null, $out['e'] ?? null, $out['t'] ?? null);

                    $existingQ = DB::table('dpcr_recalibrations')
                        ->where('employee_dpcr_id', (int)$dpcrId)
                        ->where('recalibration_level', $recalibration_level);
                    if ($outputCol) $existingQ->where($outputCol, $outId);
                    $existing = $existingQ->first();

                    $recal_data = [
                        'employee_dpcr_id' => (int)$dpcrId,
                        'recalibrated_by_employee_id' => $recalibrator_id,
                        'recalibration_level' => $recalibration_level,
                        'quality_rating' => $this->normalizeRatingForStorage($out['q'] ?? null),
                        'efficiency_rating' => $this->normalizeRatingForStorage($out['e'] ?? null),
                        'average_rating' => $computedA ?? $this->normalizeRatingForStorage($out['a'] ?? null),
                        'remarks' => $out['remarks'] ?? null,
                        'status' => ($status_recalibrated_id ?? 'Recalibrated'),
                        'updated_at' => now(),
                    ];
                    if ($outputCol) $recal_data[$outputCol] = $outId;
                    if ($hasTimeliness) {
                        $recal_data['timeliness_rating'] = $this->normalizeRatingForStorage($out['t'] ?? null);
                    } elseif ($hasEffectiveness) {
                        $recal_data['effectiveness_rating'] = $this->normalizeRatingForStorage($out['t'] ?? null);
                    }

                    if ($existing) {
                        DB::table('dpcr_recalibrations')->where('id', $existing->id)->update($recal_data);
                    } else {
                        $recal_data['created_at'] = now();
                        DB::table('dpcr_recalibrations')->insert($recal_data);
                    }
                    $processed++;
                }

                DB::commit();
                return $this->successResponse([
                    'processed' => $processed,
                    'level' => $recalibration_level
                ], ucfirst($recalibration_level) . ' recalibration saved successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save DPCR recalibration: ' . $e->getMessage());
        }
    }

    /**
     * Employee Portal: DPCR form data (employees list, division, and PMT/Agency Head defaults)
     */
    public function employeeFormData()
    {
        try {
            $user = Auth::user();
            $employee = DB::table('employees')->where('employee_no', $user->employee_no)->first();
            if (!$employee) return $this->errorResponse('Employee record not found');

            $employee_id = $employee->id;
            $division_ids_headed = $this->getDivisionIdsHeadedBy($employee_id);
            $posted_unit_ids = $this->getPostedDpcrUnitIds();
            $pmt_unit_ids = $this->getPmtUnitIdsForEmployee($employee_id);

            $headed_tagged = array_values(array_intersect(array_map('intval', $division_ids_headed), array_map('intval', $posted_unit_ids)));
            $pmt_tagged = array_values(array_intersect(array_map('intval', $pmt_unit_ids), array_map('intval', $posted_unit_ids)));

            // Employees select list (for signatories)
            $app_key = env("APP_KEY", "");
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
                ->where(['a.is_employee' => true, 'a.active' => true])
                ->orderBy('a.last_name', 'asc')
                ->get();

            // Division chief DPCR header assigned to current employee (dpcr_headers.employee_id)
            $dpcr_header = null;
            if (Schema::hasTable('dpcr_headers') && Schema::hasColumn('dpcr_headers', 'employee_id')) {
                $q = DB::table('dpcr_headers')->where('employee_id', $employee_id);
                if (Schema::hasColumn('dpcr_headers', 'is_posted')) $q->where('is_posted', 1);
                $dpcr_header = $q->orderBy('id', 'desc')->first();
            }

            // Fallback: find a posted dpcr_header for any division headed by the user.
            $headerUnitCol = $this->getDpcrUnitColumn('dpcr_headers');
            if (
                !$dpcr_header &&
                !empty($division_ids_headed) &&
                $headerUnitCol
            ) {
                $q = DB::table('dpcr_headers')->whereIn($headerUnitCol, $division_ids_headed);
                if (Schema::hasColumn('dpcr_headers', 'is_posted')) $q->where('is_posted', 1);
                $dpcr_header = $q->orderBy('id', 'desc')->first();
            }

            // Fallback: employee tagged in dpcr_details for a posted header.
            if (
                !$dpcr_header &&
                Schema::hasTable('dpcr_details') &&
                Schema::hasColumn('dpcr_details', 'employee_id') &&
                Schema::hasColumn('dpcr_details', 'dpcr_header_id')
            ) {
                $detail_header_ids = DB::table('dpcr_details')
                    ->where('employee_id', $employee_id)
                    ->whereNotNull('dpcr_header_id')
                    ->distinct()
                    ->pluck('dpcr_header_id')
                    ->filter()
                    ->values()
                    ->toArray();

                if (!empty($detail_header_ids)) {
                    $q = DB::table('dpcr_headers')->whereIn('id', $detail_header_ids);
                    if (Schema::hasColumn('dpcr_headers', 'is_posted')) {
                        $q->where('is_posted', 1);
                    }
                    $dpcr_header = $q->orderBy('id', 'desc')->first();
                }
            }

            $unit_id = 0;
            if ($dpcr_header && $headerUnitCol) {
                $unit_id = (int)($dpcr_header->{$headerUnitCol} ?? 0);
            }

            // If dpcr_headers didn't resolve a division, pick from tagged division chief list,
            // otherwise from tagged PMT list.
            if (!$unit_id && !empty($headed_tagged)) {
                $unit_id = (int)($headed_tagged[0] ?? 0);
            }
            if (!$unit_id && !empty($pmt_tagged)) {
                $unit_id = (int)($pmt_tagged[0] ?? 0);
            }
            $details_tagged = $this->getPostedUnitIdsFromDpcrDetails($employee_id);
            if (!$unit_id && !empty($details_tagged)) {
                $unit_id = (int)($details_tagged[0] ?? 0);
            }
            $unit_name = $this->getUnitNameById($unit_id);

            // PMT for assessed_by:
            // Prefer division-based PMT (pmt.division_id matches dpcr_headers.division_id) where is_dpcr = 1,
            // fallback to any is_dpcr = 1.
            $pmt_member = null;
            if (Schema::hasTable('pmt')) {
                $pmt_q = DB::table('pmt');
                if (Schema::hasColumn('pmt', 'is_dpcr')) {
                    $pmt_q->where('is_dpcr', 1);
                }

                $pmt_row = null;
                $pmtUnitCol = $this->getPmtUnitColumn();

                if ($unit_id && $pmtUnitCol) {
                    $pmt_row = (clone $pmt_q)->where($pmtUnitCol, $unit_id)->first();
                }
                if (!$pmt_row) {
                    $pmt_row = $pmt_q->first();
                }
                if ($pmt_row && isset($pmt_row->employee_id)) {
                    $pmt_member = ['id' => $pmt_row->employee_id, 'name' => $this->getEmployeeDisplayNameById($pmt_row->employee_id)];
                }
            }

            // Agency Head / Branch Head for approved_by
            $agency_head_id = $this->getAgencyHeadEmployeeIdForBranch($employee->branch_id ?? 0);
            $agency_head = $agency_head_id ? ['id' => $agency_head_id, 'name' => $this->getEmployeeDisplayNameById($agency_head_id)] : null;

            return $this->successResponse([
                'dpcr_header_id' => $dpcr_header->id ?? null,
                'divisionId' => $unit_id ?: null,
                'division' => $unit_name,
                'departmentId' => $unit_id ?: null,
                'department' => $unit_name,
                'monthFrom' => $dpcr_header->month_from ?? null,
                'monthTo' => $dpcr_header->month_to ?? null,
                'year' => $dpcr_header->year ?? null,
                'employees' => $employees,
                'pmt_member' => $pmt_member,
                'agency_head' => $agency_head,
            ], 'DPCR form data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve DPCR form data: ' . $e->getMessage());
        }
    }

    /**
     * Employee Portal: Save DPCR (create/update) using employee_dpcr + employee_dpcr_outputs.
     */
    public function employeeStore(Request $request, $id = null)
    {
        try {
            $validator = validator($request->all(), [
                'id' => 'nullable|integer',
                'departmentId' => 'required|integer',
                'period' => 'required|array|size:2',
                'period.*' => 'required|date',
                'planningOfficerEmployeeId' => 'nullable|integer',
                'planningOfficerDate' => 'nullable|date',
                'approvedByEmployeeId' => 'nullable|integer',
                'approvedDate' => 'nullable|date',
                'assessedByEmployeeId' => 'nullable|integer',
                'assessedDate' => 'nullable|date',
                'finalRaterEmployeeId' => 'nullable|integer',
                'finalRateDate' => 'nullable|date',
                'outputs' => 'required|array|min:1',
                'outputs.*.outputs' => 'required|string',
                'outputs.*.targetMeasures' => 'nullable|string',
                'outputs.*.functionType' => 'nullable|in:core,strategic,support',
                'outputs.*.function_type' => 'nullable|in:core,strategic,support',
                'outputs.*.q' => 'nullable|numeric|min:2|max:5',
                'outputs.*.e' => 'nullable|numeric|min:2|max:5',
                'outputs.*.t' => 'nullable|numeric|min:2|max:5',
                'outputs.*.remarks' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $user = Auth::user();
            $employee = DB::table('employees')->where('employee_no', $user->employee_no)->first();
            if (!$employee) return $this->errorResponse('Employee record not found');

            $employee_id = $employee->id;
            $is_pmt = $this->isPMTUser($employee_id);

            // Only division chiefs tagged in posted dpcr_headers can create/update DPCR (PMT is recalibration-only).
            if ($is_pmt) {
                return $this->errorResponse('PMT users cannot create/update DPCR records.');
            }
            $posted_unit_ids = $this->getPostedDpcrUnitIds();
            $headed_division_ids = $this->getDivisionIdsHeadedBy($employee_id);
            $headed_tagged = array_values(array_intersect(array_map('intval', $headed_division_ids), array_map('intval', $posted_unit_ids)));
            $details_tagged = $this->getPostedUnitIdsFromDpcrDetails($employee_id);
            $allowed_unit_ids = array_values(array_unique(array_merge($headed_tagged, $details_tagged)));
            $unit_id_req = (int)($request->input('divisionId') ?: $request->input('departmentId'));
            if (empty($allowed_unit_ids) || !in_array($unit_id_req, array_map('intval', $allowed_unit_ids), true)) {
                return $this->errorResponse('DPCR is not available for your division.');
            }

            $data = $request->all();
            $data['departmentId'] = $unit_id_req;

            $hasFunctionTypeColumn = $this->hasDpcrOutputFunctionTypeColumn();
            if (!$hasFunctionTypeColumn) {
                $hasNonCore = collect($data['outputs'] ?? [])->contains(function ($out) {
                    $type = $this->normalizeDpcrFunctionType($out['functionType'] ?? $out['function_type'] ?? 'core');
                    return $type !== 'core';
                });
                if ($hasNonCore) {
                    return $this->errorResponse(
                        'Strategic and support functions require the function_type column on employee_dpcr_outputs. Please run database migrations.'
                    );
                }
            }

            // Approved by must always be the Agency Head (branch head).
            // Ignore any client-provided approvedByEmployeeId to keep consistent workflow.
            $agency_head_id = $this->getAgencyHeadEmployeeIdForBranch($employee->branch_id ?? 0);
            if ($agency_head_id) {
                $data['approvedByEmployeeId'] = $agency_head_id;
            }

            // Assessed by must always be the PMT member (is_dpcr = 1), division-based.
            // Prefer pmt.division_id match; fallback to any is_dpcr = 1.
            // Ignore any client-provided assessedByEmployeeId to keep consistent workflow.
            if (Schema::hasTable('pmt') && Schema::hasColumn('pmt', 'is_dpcr')) {
                $unit_id_for_dpcr = (int)($data['departmentId'] ?? 0);
                $pmt_emp_id = null;
                $pmtUnitCol = $this->getPmtUnitColumn();

                if ($unit_id_for_dpcr && $pmtUnitCol) {
                    $pmt_emp_id = DB::table('pmt')
                        ->where('is_dpcr', 1)
                        ->where($pmtUnitCol, $unit_id_for_dpcr)
                        ->value('employee_id');
                }
                if (!$pmt_emp_id) {
                    $pmt_emp_id = DB::table('pmt')->where('is_dpcr', 1)->value('employee_id');
                }
                if ($pmt_emp_id) {
                    $data['assessedByEmployeeId'] = $pmt_emp_id;
                }
            }

            // Resolve names for storage
            $approved_by_name = !empty($data['approvedByEmployeeId']) ? $this->getEmployeeDisplayNameById($data['approvedByEmployeeId']) : null;
            $assessed_by_name = !empty($data['assessedByEmployeeId']) ? $this->getEmployeeDisplayNameById($data['assessedByEmployeeId']) : null;
            $final_rater_name = !empty($data['finalRaterEmployeeId']) ? $this->getEmployeeDisplayNameById($data['finalRaterEmployeeId']) : null;

            $unitCol = $this->getDpcrUnitColumn('employee_dpcr');
            if (!$unitCol) {
                return $this->errorResponse('DPCR division column is missing in employee_dpcr.');
            }

            $id = (int)($request->input('id', 0) ?: ($id ?? 0));
            $existing = $id > 0 ? DB::table('employee_dpcr')->where('id', $id)->first() : null;

            $planningOfficerDate = $data['planningOfficerDate'] ?? null;
            $approvedDate = $data['approvedDate'] ?? null;
            $assessedDate = $data['assessedDate'] ?? null;
            $finalRateDate = $data['finalRateDate'] ?? null;

            if ($existing) {
                $planningOfficerDate = $planningOfficerDate ?: ($existing->planning_officer_date ?? null);
                $approvedDate = $approvedDate ?: ($existing->approved_date ?? null);
                $assessedDate = $assessedDate ?: ($existing->assessed_date ?? null);
                $finalRateDate = $finalRateDate ?: ($existing->final_rater_date ?? null);
            }

            $planningOfficerDate = $this->resolveDpcrSignatoryDate($planningOfficerDate);
            $approvedDate = $this->resolveDpcrSignatoryDate($approvedDate, $planningOfficerDate);
            $assessedDate = $this->resolveDpcrSignatoryDate($assessedDate, $planningOfficerDate, $approvedDate);
            $finalRateDate = $this->resolveDpcrSignatoryDate($finalRateDate, $planningOfficerDate, $approvedDate, $assessedDate);

            $header_data = [
                'employee_id' => $employee_id,
                $unitCol => (int)$data['departmentId'],
                'period_start' => $data['period'][0],
                'period_end' => $data['period'][1],
                'approved_by' => $approved_by_name,
                'approved_by_employee_id' => $data['approvedByEmployeeId'] ?? null,
                'approved_date' => $approvedDate,
                'assessed_by' => $assessed_by_name,
                'assessed_by_employee_id' => $data['assessedByEmployeeId'] ?? null,
                'assessed_date' => $assessedDate,
                'final_rater' => $final_rater_name,
                'final_rater_employee_id' => $data['finalRaterEmployeeId'] ?? null,
                'final_rater_date' => $finalRateDate,
                'planning_officer_id' => $data['planningOfficerEmployeeId'] ?? null,
                'planning_officer_date' => $planningOfficerDate,
                'updated_at' => now(),
            ];

            if ($id === 0) {
                $header_data['created_at'] = now();
                $id = (int)DB::table('employee_dpcr')->insertGetId($header_data);
            } else {
                // Ensure owner (DPCR creation/update is division-chief only; PMT is recalibration-only)
                if (!$existing) return $this->errorResponse('DPCR record not found');
                if ((int)$existing->employee_id !== (int)$employee_id) return $this->errorResponse('Access denied');
                DB::table('employee_dpcr')->where('id', $id)->update($header_data);
                DB::table('employee_dpcr_outputs')->where('employee_dpcr_id', $id)->delete();
            }

            $hasTimeliness = Schema::hasColumn('employee_dpcr_outputs', 'timeliness_rating');
            $hasEffectiveness = Schema::hasColumn('employee_dpcr_outputs', 'effectiveness_rating');

            foreach ($data['outputs'] as $out) {
                $computedA = $this->computeAverageRating($out['q'] ?? null, $out['e'] ?? null, $out['t'] ?? null);

                $functionType = $this->normalizeDpcrFunctionType($out['functionType'] ?? $out['function_type'] ?? 'core');

                $row = [
                    'employee_dpcr_id' => $id,
                    'Outputs' => $out['outputs'] ?? '',
                    'Target_measures' => $out['targetMeasures'] ?? '',
                    'alloted_budget' => $out['allottedBudget'] ?? 0,
                    'div_indiv_accountable' => $out['divIndivAccountable'] ?? '',
                    'actual_accomplishments' => $out['actualAccomplishments'] ?? '',
                    'quality_rating' => $this->normalizeRatingForStorage($out['q'] ?? null),
                    'efficiency_rating' => $this->normalizeRatingForStorage($out['e'] ?? null),
                    'average_rating' => $computedA ?? $this->normalizeRatingForStorage($out['a'] ?? null),
                    'remarks' => $out['remarks'] ?? '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Timeliness column name differs across schemas; prefer timeliness_rating, fallback to effectiveness_rating.
                if ($hasTimeliness) {
                    $row['timeliness_rating'] = $this->normalizeRatingForStorage($out['t'] ?? null);
                } elseif ($hasEffectiveness) {
                    $row['effectiveness_rating'] = $this->normalizeRatingForStorage($out['t'] ?? null);
                } else {
                    // If neither exists, skip writing T to avoid SQL error
                }

                if ($hasFunctionTypeColumn) {
                    $row['function_type'] = $functionType;
                }

                DB::table('employee_dpcr_outputs')->insert($row);
            }

            return $this->successResponse(['id' => $id], 'DPCR saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save DPCR: ' . $e->getMessage());
        }
    }

    public function employeeDelete($id)
    {
        try {
            $user = Auth::user();
            $employee = DB::table('employees')->where('employee_no', $user->employee_no)->first();
            if (!$employee) return $this->errorResponse('Employee record not found');

            $employee_id = $employee->id;
            $posted_unit_ids = $this->getPostedDpcrUnitIds();
            $headed_division_ids = $this->getDivisionIdsHeadedBy($employee_id);
            $headed_tagged = array_values(array_intersect(array_map('intval', $headed_division_ids), array_map('intval', $posted_unit_ids)));

            $dpcr = DB::table('employee_dpcr')->where('id', $id)->first();
            if (!$dpcr) return $this->errorResponse('DPCR record not found');
            $unit_id = $this->resolveUnitIdFromRecord($dpcr);
            $is_division_chief_tagged = ($unit_id && in_array($unit_id, array_map('intval', $headed_tagged), true));
            $is_owner = ((int)($dpcr->employee_id ?? 0) === (int)$employee_id);
            // Only the tagged division chief/owner can delete. PMT cannot delete records.
            if (!$is_owner || !$is_division_chief_tagged) {
                return $this->errorResponse('Access denied');
            }

            DB::table('employee_dpcr_outputs')->where('employee_dpcr_id', $id)->delete();
            DB::table('employee_dpcr')->where('id', $id)->delete();
            return $this->successResponse(null, 'DPCR deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete DPCR: ' . $e->getMessage());
        }
    }
}
