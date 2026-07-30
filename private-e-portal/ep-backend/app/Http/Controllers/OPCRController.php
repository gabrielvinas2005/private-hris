<?php

namespace App\Http\Controllers;

use Auth;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OPCRController extends Controller
{
    use ApiResponse;

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

    private function resolveOpcrSignatoryDate($value, ...$fallbacks)
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

    private function hasOpcrOutputFunctionTypeColumn($table = 'employee_opcr_outputs'): bool
    {
        static $cache = [];
        if (!isset($cache[$table])) {
            $cache[$table] = Schema::hasTable($table)
                && Schema::hasColumn($table, 'function_type');
        }

        return $cache[$table];
    }

    private function normalizeOpcrFunctionType($type): string
    {
        $normalized = strtolower(trim((string)($type ?? 'core')));
        return in_array($normalized, ['core', 'strategic', 'support'], true) ? $normalized : 'core';
    }

    private function opcrCategorySummaryColumnMap(): array
    {
        return [
            'strategic' => 'strategic_mfo',
            'core' => 'core_mfo',
            'support' => 'support_mfo',
        ];
    }

    private function hasOpcrCategorySummaryColumns(): bool
    {
        return Schema::hasTable('employee_opcr')
            && Schema::hasColumn('employee_opcr', 'strategic_mfo');
    }

    private function normalizeCategoryPercentageValue($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }

    private function formatCategorySummaryForApi($record): array
    {
        $summary = [
            'strategic' => ['percentage' => null],
            'core' => ['percentage' => null],
            'support' => ['percentage' => null],
        ];

        if (!$record || !$this->hasOpcrCategorySummaryColumns()) {
            return $summary;
        }

        foreach ($this->opcrCategorySummaryColumnMap() as $type => $column) {
            $percentage = $record->{$column} ?? null;
            $summary[$type]['percentage'] = $percentage !== null && $percentage !== ''
                ? (float) $percentage
                : null;
        }

        return $summary;
    }

    private function applyCategorySummaryToHeaderData(array &$headerData, $payload): void
    {
        if (!$this->hasOpcrCategorySummaryColumns()) {
            return;
        }

        $summary = $payload['categorySummary'] ?? $payload['category_summary'] ?? null;
        if (!is_array($summary)) {
            return;
        }

        foreach ($this->opcrCategorySummaryColumnMap() as $type => $column) {
            $entry = $summary[$type] ?? null;
            if (!is_array($entry)) {
                continue;
            }

            $raw = $entry['percentage'] ?? $entry['mfo'] ?? null;
            if (array_key_exists('percentage', $entry) || array_key_exists('mfo', $entry)) {
                $headerData[$column] = $this->normalizeCategoryPercentageValue($raw);
            }
        }

        foreach (['strategic_rating', 'core_rating', 'support_rating'] as $ratingColumn) {
            if (Schema::hasColumn('employee_opcr', $ratingColumn)) {
                $headerData[$ratingColumn] = null;
            }
        }
    }

    private function computeCategoryAverageFromRows($rows): ?float
    {
        $values = collect($rows)
            ->pluck('a')
            ->filter(fn ($v) => $v !== null && $v !== '' && is_numeric($v) && (float) $v >= 2)
            ->map(fn ($v) => (float) $v);

        return $values->count() > 0 ? round($values->avg(), 2) : null;
    }

    private function buildCategorySummaryForPrint($opcr, $groupedOutputs): array
    {
        $stored = $this->formatCategorySummaryForApi($opcr);
        $resolved = [];

        foreach (['strategic', 'core', 'support'] as $type) {
            $rows = $groupedOutputs[$type] ?? collect();
            $resolved[$type] = [
                'percentage' => $stored[$type]['percentage'],
                'mfo_count' => $rows->count() > 0 ? $rows->count() : null,
                'rating' => $this->computeCategoryAverageFromRows($rows),
            ];
        }

        return $resolved;
    }

    private function resolveOpcrFunctionTypeFromRecord($row): string
    {
        if (isset($row->function_type) && $row->function_type !== '') {
            return $this->normalizeOpcrFunctionType($row->function_type);
        }

        $category = strtolower(trim((string)($row->category ?? '')));
        if (str_contains($category, 'strateg')) {
            return 'strategic';
        }
        if (str_contains($category, 'support')) {
            return 'support';
        }
        if (str_contains($category, 'core')) {
            return 'core';
        }

        return 'core';
    }

    private function categoryLabelForFunctionType($type): string
    {
        return match ($this->normalizeOpcrFunctionType($type)) {
            'strategic' => 'Strategy Priority',
            'support' => 'Support Functions',
            default => 'Core Functions',
        };
    }

    private function formatOpcrOutputRowForApi($output)
    {
        $recalibrations = collect();
        if (Schema::hasTable('opcr_recalibrations')) {
            $recalibrations = DB::table('opcr_recalibrations')
                ->where('employee_opcr_output_id', $output->id)
                ->orderByRaw("CASE recalibration_level WHEN 'hr' THEN 1 WHEN 'pmt' THEN 2 ELSE 99 END")
                ->get();
        }
        $hr_recal = $recalibrations->firstWhere('recalibration_level', 'hr');
        $pmt_recal = $recalibrations->firstWhere('recalibration_level', 'pmt');

        $q = $this->normalizeRatingForApi($output->quality_rating);
        $e = $this->normalizeRatingForApi($output->efficiency_rating);
        $t = $this->normalizeRatingForApi($output->timeliness_rating);
        $functionType = $this->resolveOpcrFunctionTypeFromRecord($output);

        return [
            'id' => $output->id,
            'category' => $output->category ?? $this->categoryLabelForFunctionType($functionType),
            'functionType' => $functionType,
            'function_type' => $functionType,
            'mfoPap' => $output->mfo_pap ?? '',
            'successIndicators' => $output->success_indicators ?? '',
            'allottedBudget' => $output->allotted_budget ?? 0,
            'divisionIndividualsAccountable' => $output->division_individuals_accountable ?? '',
            'actualAccomplishments' => $output->actual_accomplishments ?? '',
            'q' => $q,
            'e' => $e,
            't' => $t,
            'a' => $this->normalizeRatingForApi($output->average_rating)
                ?? $this->computeAverageRating($q, $e, $t),
            'remarks' => $output->remarks ?? '',
            'hr_recalibration' => $hr_recal ? [
                'q' => $hr_recal->quality_rating,
                'e' => $hr_recal->efficiency_rating,
                't' => $hr_recal->timeliness_rating,
                'a' => $hr_recal->average_rating,
                'remarks' => $hr_recal->remarks,
                'recalibrated_by' => $hr_recal->recalibrated_by_employee_id,
                'recalibrated_at' => $hr_recal->created_at,
            ] : null,
            'pmt_recalibration' => $pmt_recal ? [
                'q' => $pmt_recal->quality_rating,
                'e' => $pmt_recal->efficiency_rating,
                't' => $pmt_recal->timeliness_rating,
                'a' => $pmt_recal->average_rating,
                'remarks' => $pmt_recal->remarks,
                'recalibrated_by' => $pmt_recal->recalibrated_by_employee_id,
                'recalibrated_at' => $pmt_recal->created_at,
            ] : null,
        ];
    }

    private function defaultOpcrRecalibrationMeta(): array
    {
        return [
            'recalibration_status' => 'submitted',
            'is_locked' => false,
            'can_recalibrate_hr' => true,
            'can_recalibrate_pmt' => true,
        ];
    }

    private function resolveOpcrRecalibrationMetaFromOutputs($outputIds, $recalsByOutputId): array
    {
        $default = $this->defaultOpcrRecalibrationMeta();
        $outputIds = collect($outputIds)->filter()->values();

        if ($outputIds->isEmpty()) {
            return $default;
        }

        $allHavePmt = true;
        $allHaveHr = true;

        foreach ($outputIds as $outputId) {
            $rows = collect($recalsByOutputId[$outputId] ?? []);
            $hasHr = $rows->contains(fn ($row) => ($row->recalibration_level ?? null) === 'hr');
            $hasPmt = $rows->contains(fn ($row) => ($row->recalibration_level ?? null) === 'pmt');

            if (!$hasPmt) {
                $allHavePmt = false;
            }
            if (!$hasHr) {
                $allHaveHr = false;
            }
        }

        if ($allHavePmt) {
            return [
                'recalibration_status' => 'pmt_recalibrated',
                'is_locked' => true,
                'can_recalibrate_hr' => false,
                'can_recalibrate_pmt' => false,
            ];
        }

        if ($allHaveHr) {
            return [
                'recalibration_status' => 'hr_recalibrated',
                'is_locked' => false,
                'can_recalibrate_hr' => true,
                'can_recalibrate_pmt' => true,
            ];
        }

        return $default;
    }

    private function getOpcrRecalibrationMeta(int $opcrId): array
    {
        if (!Schema::hasTable('opcr_recalibrations') || !Schema::hasTable('employee_opcr_outputs')) {
            return $this->defaultOpcrRecalibrationMeta();
        }

        $outputIds = DB::table('employee_opcr_outputs')
            ->where('employee_opcr_id', $opcrId)
            ->pluck('id');

        if ($outputIds->isEmpty()) {
            return $this->defaultOpcrRecalibrationMeta();
        }

        $recals = DB::table('opcr_recalibrations')
            ->whereIn('employee_opcr_output_id', $outputIds->all())
            ->get()
            ->groupBy('employee_opcr_output_id');

        $recalsByOutputId = [];
        foreach ($recals as $outputId => $rows) {
            $recalsByOutputId[$outputId] = $rows->all();
        }

        return $this->resolveOpcrRecalibrationMetaFromOutputs($outputIds, $recalsByOutputId);
    }

    private function buildOpcrRecalibrationMetaMap(array $opcrIds): array
    {
        $map = [];
        foreach ($opcrIds as $opcrId) {
            $map[(int) $opcrId] = $this->defaultOpcrRecalibrationMeta();
        }

        if (empty($opcrIds) || !Schema::hasTable('opcr_recalibrations') || !Schema::hasTable('employee_opcr_outputs')) {
            return $map;
        }

        $outputs = DB::table('employee_opcr_outputs')
            ->whereIn('employee_opcr_id', $opcrIds)
            ->select('id', 'employee_opcr_id')
            ->get();

        if ($outputs->isEmpty()) {
            return $map;
        }

        $recals = DB::table('opcr_recalibrations')
            ->whereIn('employee_opcr_output_id', $outputs->pluck('id')->all())
            ->get()
            ->groupBy('employee_opcr_output_id');

        $recalsByOutputId = [];
        foreach ($recals as $outputId => $rows) {
            $recalsByOutputId[$outputId] = $rows->all();
        }

        foreach ($outputs->groupBy('employee_opcr_id') as $opcrId => $opcrOutputs) {
            $map[(int) $opcrId] = $this->resolveOpcrRecalibrationMetaFromOutputs(
                $opcrOutputs->pluck('id'),
                $recalsByOutputId
            );
        }

        return $map;
    }

    private function appendOpcrRecalibrationMeta($records)
    {
        $records = collect($records);
        $metaMap = $this->buildOpcrRecalibrationMetaMap(
            $records->pluck('id')->filter()->map(fn ($id) => (int) $id)->values()->all()
        );

        return $records->map(function ($record) use ($metaMap) {
            $recordId = (int) ($record['id'] ?? 0);
            $meta = $metaMap[$recordId] ?? $this->defaultOpcrRecalibrationMeta();

            return array_merge($record, $meta);
        })->values();
    }

    private function orderOpcrOutputsQuery($query, $table)
    {
        if ($this->hasOpcrOutputFunctionTypeColumn($table)) {
            $query->orderByRaw("CASE function_type WHEN 'strategic' THEN 1 WHEN 'core' THEN 2 WHEN 'support' THEN 3 ELSE 4 END");
        }

        return $query->orderBy('id', 'asc');
    }

    private function buildGroupedOpcrOutputs($outputs)
    {
        $collection = collect($outputs);

        return [
            'strategic' => $collection->filter(fn ($row) => $this->resolveOpcrFunctionTypeFromRecord($row) === 'strategic')->values(),
            'core' => $collection->filter(fn ($row) => $this->resolveOpcrFunctionTypeFromRecord($row) === 'core')->values(),
            'support' => $collection->filter(fn ($row) => $this->resolveOpcrFunctionTypeFromRecord($row) === 'support')->values(),
        ];
    }

    private function mapOpcrOutputForPrint($row)
    {
        $q = $this->normalizeRatingForApi($row->quality_rating ?? null);
        $e = $this->normalizeRatingForApi($row->efficiency_rating ?? null);
        $t = $this->normalizeRatingForApi($row->timeliness_rating ?? null);

        return (object) [
            'function_type' => $this->resolveOpcrFunctionTypeFromRecord($row),
            'mfo_pap' => $row->mfo_pap ?? '',
            'success_indicators' => $row->success_indicators ?? '',
            'allotted_budget' => $row->allotted_budget ?? null,
            'accountable' => $row->division_individuals_accountable ?? '',
            'actual_accomplishments' => $row->actual_accomplishments ?? '',
            'q' => $q,
            'e' => $e,
            't' => $t,
            'a' => $this->normalizeRatingForApi($row->average_rating ?? null)
                ?? $this->computeAverageRating($row->quality_rating ?? null, $row->efficiency_rating ?? null, $row->timeliness_rating ?? null),
            'remarks' => $row->remarks ?? '',
        ];
    }

    private function buildGroupedOpcrPrintOutputs($outputs)
    {
        $mapped = collect($outputs)->map(fn ($row) => $this->mapOpcrOutputForPrint($row));

        return [
            'strategic' => $mapped->filter(fn ($row) => ($row->function_type ?? 'core') === 'strategic')->values(),
            'core' => $mapped->filter(fn ($row) => ($row->function_type ?? 'core') === 'core')->values(),
            'support' => $mapped->filter(fn ($row) => ($row->function_type ?? 'core') === 'support')->values(),
        ];
    }

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

    /**
     * Helper: fetch employee display name by ID (with decrypt handling).
     */
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

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Helper method to check if employee has access to OPCR
     * Checks both opcr_details (via employee_opcr_id) and opcr_headers (via departments_id)
     */
    private function hasAccessToOPCR($employee_id, $employee_department_id = null)
    {
        // First, check if employee is tagged in opcr_details via employee_opcr_id
        if (Schema::hasTable('opcr_details')) {
            // Check if opcr_details has employee_opcr_id column
            if (Schema::hasColumn('opcr_details', 'employee_opcr_id')) {
                // Check if employee has any employee_opcr records that are referenced in opcr_details
                $has_opcr_details = DB::table('opcr_details as od')
                    ->join('employee_opcr as eo', 'od.employee_opcr_id', '=', 'eo.id')
                    ->where('eo.employee_id', $employee_id)
                    ->exists();
                
                if ($has_opcr_details) {
                    return true;
                }
            }
            
            // Also check if opcr_details has employee_id column directly
            if (Schema::hasColumn('opcr_details', 'employee_id')) {
                $has_opcr_details = DB::table('opcr_details')
                    ->where('employee_id', $employee_id)
                    ->exists();
                
                if ($has_opcr_details) {
                    return true;
                }
            }
        }

        // Fallback: Check if employee's department_id is in opcr_headers departments_id
        if ($employee_department_id && $employee_department_id != 0) {
            $planning_officer_name = null;
            $planning_officer_date = null;
            $assessed_by_name_for_view = null;
            $final_rater_name_for_view = null;

            if (Schema::hasTable('opcr_headers') && Schema::hasColumn('opcr_headers', 'departments_id')) {
                $opcr_headers = DB::table('opcr_headers')
                    ->whereNotNull('departments_id')
                    ->get();

                foreach ($opcr_headers as $header) {
                    if ($this->isDepartmentInList($employee_department_id, $header->departments_id)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if employee is a PMT member with OPCR access (is_opcr = 1)
     */
    private function isPMTUser($employee_id)
    {
        if (!Schema::hasTable('pmt')) {
            return false;
        }

        $pmt_member = DB::table('pmt')
            ->where('employee_id', $employee_id)
            ->where('is_opcr', 1)
            ->first();

        return $pmt_member !== null;
    }

    /**
     * Check if user has HR access
     * Matches employee_no between users and employees tables
     */
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
     * Helper method to check if department_id is in departments_id field
     * Handles comma-separated strings, JSON arrays, or single integers
     */
    private function isDepartmentInList($department_id, $departments_id)
    {
        if (empty($departments_id) || $department_id == 0) {
            return false;
        }

        // Convert department_id to integer for consistent comparison
        $department_id = (int)$department_id;

        // Try JSON decode first
        $decoded = json_decode($departments_id, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // Convert all array values to integers for comparison
            $decoded = array_map('intval', $decoded);
            return in_array($department_id, $decoded, true);
        }

        // Try comma-separated string
        if (strpos($departments_id, ',') !== false) {
            $ids = array_map('trim', explode(',', $departments_id));
            // Convert all to integers for comparison
            $ids = array_map('intval', $ids);
            return in_array($department_id, $ids, true);
        }

        // Try direct comparison (single integer)
        return (int)$departments_id === $department_id;
    }

    /**
     * Employee Portal: Check if user has access to OPCR (based on department_id in opcr_headers)
     */
    public function checkDivisionChiefAccess()
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
            $employee_department_id = $employee->department_id ?? 0;

            $has_tagged_access = $this->hasAccessToOPCR($employee_id, $employee_department_id);

            $is_division_chief = DB::table('divisions')->where([
                'division_chief_id' => $employee_id,
                'active' => true
            ])->exists();

            $is_pmt_user = $this->isPMTUser($employee_id);

            $has_ongoing_opcr = Schema::hasTable('opcr_headers')
                && DB::table('opcr_headers')->exists();

            $has_access = $has_ongoing_opcr
                && ($has_tagged_access || $is_division_chief || $is_pmt_user);

            return $this->successResponse([
                'is_division_chief' => $is_division_chief || $has_tagged_access,
                'has_access' => $has_access,
                'is_pmt_user' => $is_pmt_user
            ], 'Access check completed');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to check access: ' . $e->getMessage());
        }
    }

    /**
     * Employee Portal: Get OPCR records for current user
     */
    public function employeeList()
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
            $employee_department_id = $employee->department_id ?? 0;

            // Check access: either division chief, PMT member, or tagged in opcr_details/opcr_headers
            $is_division_chief = DB::table('divisions')->where([
                'division_chief_id' => $employee_id,
                'active' => true
            ])->exists();

            $has_access = $this->hasAccessToOPCR($employee_id, $employee_department_id);
            $is_pmt_user = $this->isPMTUser($employee_id);

            if (!$is_division_chief && !$has_access && !$is_pmt_user) {
                return $this->errorResponse('OPCR is only available to employees in tagged departments, Division Chiefs, or PMT members.');
            }

            // Check if opcr_headers table exists, if so use it, otherwise fallback to employee_opcr
            if (Schema::hasTable('opcr_headers') && Schema::hasColumn('opcr_headers', 'departments_id')) {
                $accessible_opcr_ids = [];

                // First, get OPCR records from opcr_details where employee is tagged via employee_opcr_id
                if (Schema::hasTable('opcr_details')) {
                    if (Schema::hasColumn('opcr_details', 'employee_opcr_id')) {
                        // Get employee_opcr IDs for this employee
                        $employee_opcr_ids = DB::table('employee_opcr')
                            ->where('employee_id', $employee_id)
                            ->pluck('id')
                            ->toArray();
                        
                        if (!empty($employee_opcr_ids)) {
                            // Get opcr_header_ids from opcr_details that reference these employee_opcr records
                            $opcr_header_ids_from_details = DB::table('opcr_details')
                                ->whereIn('employee_opcr_id', $employee_opcr_ids)
                                ->whereNotNull('opcr_header_id')
                                ->distinct()
                                ->pluck('opcr_header_id')
                                ->toArray();
                            
                            $accessible_opcr_ids = array_merge($accessible_opcr_ids, $opcr_header_ids_from_details);
                        }
                    }
                    
                    // Also check if opcr_details has employee_id column directly
                    if (Schema::hasColumn('opcr_details', 'employee_id')) {
                        $opcr_header_ids_from_employee = DB::table('opcr_details')
                            ->where('employee_id', $employee_id)
                            ->whereNotNull('opcr_header_id')
                            ->distinct()
                            ->pluck('opcr_header_id')
                            ->toArray();
                        
                        $accessible_opcr_ids = array_merge($accessible_opcr_ids, $opcr_header_ids_from_employee);
                    }
                }

                // Also get OPCR records from opcr_headers where employee's department is in departments_id
                $opcr_headers = DB::table('opcr_headers')
                    ->whereNotNull('departments_id')
                    ->get();

                foreach ($opcr_headers as $header) {
                    if ($this->isDepartmentInList($employee_department_id, $header->departments_id)) {
                        $accessible_opcr_ids[] = $header->id;
                    }
                }

                // Also include if user is division chief or PMT member (get all opcr_headers)
                if ($is_division_chief || $is_pmt_user) {
                    // PMT users and division chiefs can see all OPCR records
                    $all_opcr_ids = DB::table('opcr_headers')->pluck('id')->toArray();
                    $accessible_opcr_ids = !empty($all_opcr_ids) ? array_unique($all_opcr_ids) : [];
                } else {
                    $accessible_opcr_ids = array_unique($accessible_opcr_ids);
                }

                $formatted_records = collect();

                if (!empty($accessible_opcr_ids)) {
                // Get opcr_headers with details
                $select_fields = [
                    'a.id',
                    'a.period_start',
                    'a.period_end',
                    'a.created_at',
                    'a.updated_at',
                    'a.departments_id'
                ];

                // Note: "Reviewed by" is no longer used in OPCR; do not select reviewed_by/reviewed_date
                if (Schema::hasColumn('opcr_headers', 'approved_by')) {
                    $select_fields[] = 'a.approved_by';
                }
                if (Schema::hasColumn('opcr_headers', 'approved_date')) {
                    $select_fields[] = 'a.approved_date';
                }
                
                // Add division field if it exists
                if (Schema::hasColumn('opcr_headers', 'division')) {
                    $select_fields[] = 'a.division';
                }
                
                $opcr_records = DB::table('opcr_headers as a')
                    ->select($select_fields)
                    ->whereIn('a.id', $accessible_opcr_ids)
                    ->orderBy('a.created_at', 'desc')
                    ->get();

                $formatted_records = $opcr_records->map(function ($record) {
                    // Get division/office from OPCR record - prioritize division field
                    $division_name = 'N/A';
                    
                    // First, check if opcr_headers has a division field
                    if (Schema::hasColumn('opcr_headers', 'division') && isset($record->division) && !empty($record->division)) {
                        $division_name = $record->division;
                    }
                    // Fallback to department name if division is not available
                    else if (!empty($record->departments_id)) {
                        $dept_ids = $this->parseDepartmentIds($record->departments_id);
                        if (!empty($dept_ids)) {
                            $dept = DB::table('departments')
                                ->whereIn('id', $dept_ids)
                                ->where('active', true)
                                ->first();
                            $division_name = $dept ? $dept->name : 'N/A';
                        }
                    }

                    // Format period
                    $period = '';
                    if ($record->period_start && $record->period_end) {
                        $period = date('M d', strtotime($record->period_start)) . ' - ' . date('M d, Y', strtotime($record->period_end));
                    }

                    return [
                        'id' => $record->id,
                        'division' => $division_name,
                        'period' => $period,
                        'period_start' => $record->period_start,
                        'period_end' => $record->period_end,
                        'reviewed_by' => null,
                        'reviewed_date' => null,
                        'approved_by' => property_exists($record, 'approved_by') ? ($record->approved_by ?? '') : null,
                        'approved_date' => property_exists($record, 'approved_date') ? $record->approved_date : null,
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at
                    ];
                });
                }

                $portal_select = [
                    'a.id',
                    'a.division',
                    'a.period_start',
                    'a.period_end',
                    'a.created_at',
                    'a.updated_at',
                ];
                if (Schema::hasColumn('employee_opcr', 'approved_by')) {
                    $portal_select[] = 'a.approved_by';
                }
                if (Schema::hasColumn('employee_opcr', 'approved_date')) {
                    $portal_select[] = 'a.approved_date';
                }

                $portal_query = DB::table('employee_opcr as a')->select($portal_select);
                if (!$is_division_chief && !$is_pmt_user) {
                    $portal_query->where('a.employee_id', $employee_id);
                }

                $portal_records = $portal_query->orderBy('a.created_at', 'desc')->get()->map(function ($record) {
                    $period = '';
                    if ($record->period_start && $record->period_end) {
                        $period = date('M d', strtotime($record->period_start)) . ' - ' . date('M d, Y', strtotime($record->period_end));
                    }

                    return [
                        'id' => $record->id,
                        'division' => $record->division,
                        'period' => $period,
                        'period_start' => $record->period_start,
                        'period_end' => $record->period_end,
                        'reviewed_by' => null,
                        'reviewed_date' => null,
                        'approved_by' => property_exists($record, 'approved_by') ? ($record->approved_by ?? '') : null,
                        'approved_date' => property_exists($record, 'approved_date') ? $record->approved_date : null,
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at,
                    ];
                });

                $formatted_records = $formatted_records
                    ->concat($portal_records)
                    ->sortByDesc('created_at')
                    ->values();
            } else {
                // Fallback to employee_opcr table (backward compatibility)
                $select_fields = [
                    'a.id',
                    'a.division',
                    'a.period_start',
                    'a.period_end',
                    'a.created_at',
                    'a.updated_at'
                ];

                // Note: "Reviewed by" is no longer used in OPCR; do not select reviewed_by/reviewed_date
                if (Schema::hasColumn('employee_opcr', 'approved_by')) {
                    $select_fields[] = 'a.approved_by';
                }
                if (Schema::hasColumn('employee_opcr', 'approved_date')) {
                    $select_fields[] = 'a.approved_date';
                }

                $query = DB::table('employee_opcr as a')->select($select_fields);
                
                // PMT users and division chiefs can see all OPCR records
                if (!$is_division_chief && !$is_pmt_user) {
                    $query->where('a.employee_id', $employee_id);
                }
                
                $opcr_records = $query->orderBy('a.created_at', 'desc')->get();

                $formatted_records = $opcr_records->map(function ($record) {
                    $period = '';
                    if ($record->period_start && $record->period_end) {
                        $period = date('M d', strtotime($record->period_start)) . ' - ' . date('M d, Y', strtotime($record->period_end));
                    }
                    return [
                        'id' => $record->id,
                        'division' => $record->division,
                        'period' => $period,
                        'period_start' => $record->period_start,
                        'period_end' => $record->period_end,
                        'reviewed_by' => null,
                        'reviewed_date' => null,
                        'approved_by' => property_exists($record, 'approved_by') ? $record->approved_by : null,
                        'approved_date' => property_exists($record, 'approved_date') ? $record->approved_date : null,
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at
                    ];
                });
            }

            $formatted_records = $this->appendOpcrRecalibrationMeta($formatted_records);

            return $this->successResponse($formatted_records, 'OPCR records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve OPCR records: ' . $e->getMessage());
        }
    }

    /**
     * Employee Portal: Get form data (division info and employee lists)
     */
    public function employeeFormData()
    {
        try {
            $app_key = env("APP_KEY", "");
            $user = Auth::user();
            
            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee record not found');
            }

            $employee_id = $employee->id;
            $employee_department_id = $employee->department_id ?? 0;
            $employee_branch_id = $employee->branch_id ?? 0;

            // Check access: either division chief or tagged in opcr_details/opcr_headers
            $is_division_chief = DB::table('divisions')
                ->where('division_chief_id', $employee_id)
                ->where('active', true)
                ->exists();

            $has_access = $this->hasAccessToOPCR($employee_id, $employee_department_id);
            $is_pmt_user = $this->isPMTUser($employee_id);

            if (!$is_division_chief && !$has_access && !$is_pmt_user) {
                return $this->errorResponse('OPCR is only available to employees in tagged departments, Division Chiefs, or PMT members.');
            }

            // Get division name if user is division chief, otherwise get department name
            $division = null;
            $division_name = '';
            
            if ($is_division_chief) {
                $division = DB::table('divisions')
                    ->where('division_chief_id', $employee_id)
                    ->where('active', true)
                    ->first();
                $division_name = $division ? $division->name : '';
            } else {
                // Get department name
                $department = DB::table('departments')
                    ->where('id', $employee_department_id)
                    ->where('active', true)
                    ->first();
                $division_name = $department ? $department->name : '';
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

            // Get employees under this division
            $division_employees = [];
            if ($division) {
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
                        'a.division_id' => $division->id,
                        'a.is_employee' => true,
                        'a.active' => true
                    ])
                    ->orderBy('a.last_name', 'asc')
                    ->get();
            }

            // Get PMT member for "Assessed by" field
            // Logic: Prefer division-based PMT calibrator; fallback to section-based; then any PMT where is_opcr = 1.
            $pmt_assessed_by = null;
            if (Schema::hasTable('pmt') && Schema::hasColumn('pmt', 'is_opcr')) {
                $pmt_record = null;

                // 1) Prefer division_id match
                if (Schema::hasColumn('pmt', 'division_id') && isset($employee->division_id) && $employee->division_id) {
                    $pmt_record = DB::table('pmt')
                        ->where('is_opcr', 1)
                        ->where('division_id', $employee->division_id)
                        ->first();
                }

                // 2) Fallback to section_id match
                if (!$pmt_record && Schema::hasColumn('pmt', 'section_id') && isset($employee->section_id) && $employee->section_id) {
                    $pmt_record = DB::table('pmt')
                        ->where('is_opcr', 1)
                        ->where('section_id', $employee->section_id)
                        ->first();
                }

                // 3) Fallback: any OPCR PMT member
                if (!$pmt_record) {
                    $pmt_record = DB::table('pmt')->where('is_opcr', 1)->first();
                }

                if ($pmt_record && isset($pmt_record->employee_id)) {
                    $pmt_assessed_by = [
                        'id' => $pmt_record->employee_id,
                        'name' => $this->getEmployeeDisplayNameById($pmt_record->employee_id)
                    ];
                }
            }

            // Approved by = Agency Head (branch head)
            $agency_head = null;
            $agency_head_id = $this->getAgencyHeadEmployeeIdForBranch($employee_branch_id);
            if ($agency_head_id) {
                $agency_head = [
                    'id' => $agency_head_id,
                    'name' => $this->getEmployeeDisplayNameById($agency_head_id)
                ];
            }

            return $this->successResponse([
                'division' => $division_name,
                'employees' => $employees,
                'department_employees' => $division_employees,
                'pmt_assessed_by' => $pmt_assessed_by,
                'agency_head' => $agency_head
            ], 'OPCR form data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve OPCR form data: ' . $e->getMessage());
        }
    }

    /**
     * Employee Portal: Get single OPCR record with outputs
     */
    public function employeeGet($id)
    {
        try {
            $app_key = env("APP_KEY", "");
            $user = Auth::user();
            
            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee record not found');
            }

            $employee_id = $employee->id;
            $employee_department_id = $employee->department_id ?? 0;

            // Check access: either division chief or tagged in opcr_details/opcr_headers
            $is_division_chief = DB::table('divisions')->where([
                'division_chief_id' => $employee_id,
                'active' => true
            ])->exists();

            $has_access = $this->hasAccessToOPCR($employee_id, $employee_department_id);
            $is_pmt_user = $this->isPMTUser($employee_id);

            if (!$is_division_chief && !$has_access && !$is_pmt_user) {
                return $this->errorResponse('OPCR is only available to employees in tagged departments, Division Chiefs, or PMT members.');
            }

            // Check if opcr_headers table exists, if so use it, otherwise fallback to employee_opcr
            $use_opcr_headers = Schema::hasTable('opcr_headers') && Schema::hasColumn('opcr_headers', 'departments_id');
            $opcr_header = null;
            
            if ($use_opcr_headers) {
                // Get opcr_header
                $opcr_header = DB::table('opcr_headers')
                    ->where('id', $id)
                    ->first();
                }
            
            if ($opcr_header) {

                // Check if employee has access to this specific OPCR
                // PMT users and division chiefs can access all OPCRs
                if (!$is_division_chief && !$is_pmt_user) {
                    $has_access_to_this_opcr = false;
                    
                    // Check via opcr_details
                    if (Schema::hasTable('opcr_details')) {
                        if (Schema::hasColumn('opcr_details', 'employee_opcr_id')) {
                            // Check if employee has employee_opcr records referenced in opcr_details for this opcr_header
                            $employee_opcr_ids = DB::table('employee_opcr')
                                ->where('employee_id', $employee_id)
                                ->pluck('id')
                                ->toArray();
                            
                            if (!empty($employee_opcr_ids)) {
                                $has_access_to_this_opcr = DB::table('opcr_details')
                                    ->where('opcr_header_id', $id)
                                    ->whereIn('employee_opcr_id', $employee_opcr_ids)
                                    ->exists();
                            }
                        }
                        
                        if (!$has_access_to_this_opcr && Schema::hasColumn('opcr_details', 'employee_id')) {
                            $has_access_to_this_opcr = DB::table('opcr_details')
                                ->where('opcr_header_id', $id)
                                ->where('employee_id', $employee_id)
                                ->exists();
                        }
                    }
                    
                    // Check via departments_id in opcr_headers
                    if (!$has_access_to_this_opcr && $opcr_header->departments_id) {
                        $has_access_to_this_opcr = $this->isDepartmentInList($employee_department_id, $opcr_header->departments_id);
                    }
                    
                    if (!$has_access_to_this_opcr) {
                        return $this->errorResponse('You do not have access to this OPCR record.');
                    }
                }

                // Get opcr_details
                $outputs = [];
                if (Schema::hasTable('opcr_details')) {
                    $outputsQuery = DB::table('opcr_details')->where('opcr_header_id', $id);
                    $outputs = $this->orderOpcrOutputsQuery($outputsQuery, 'opcr_details')
                        ->get()
                        ->map(function ($output) {
                            return $this->formatOpcrOutputRowForApi($output);
                        })->toArray();
                }

                // Get division/office from OPCR record - prioritize division field in opcr_headers
                $division_name = '';
                
                // First, check if opcr_headers has a division field
                if (Schema::hasColumn('opcr_headers', 'division') && !empty($opcr_header->division)) {
                    $division_name = $opcr_header->division;
                } 
                // If not, try to get from related employee_opcr records via opcr_details
                else if (Schema::hasTable('opcr_details') && Schema::hasColumn('opcr_details', 'employee_opcr_id')) {
                    $employee_opcr_ids = DB::table('opcr_details')
                        ->where('opcr_header_id', $id)
                        ->whereNotNull('employee_opcr_id')
                        ->distinct()
                        ->pluck('employee_opcr_id')
                        ->toArray();
                    
                    if (!empty($employee_opcr_ids)) {
                        $employee_opcr = DB::table('employee_opcr')
                            ->whereIn('id', $employee_opcr_ids)
                            ->whereNotNull('division')
                            ->first();
                        
                        if ($employee_opcr && !empty($employee_opcr->division)) {
                            $division_name = $employee_opcr->division;
                        }
                    }
                }
                
                // Fallback to department name if division is still empty
                if (empty($division_name) && $opcr_header->departments_id) {
                    $dept_ids = $this->parseDepartmentIds($opcr_header->departments_id);
                    if (!empty($dept_ids)) {
                        $dept = DB::table('departments')
                            ->whereIn('id', $dept_ids)
                            ->where('active', true)
                            ->first();
                        $division_name = $dept ? $dept->name : '';
                    }
                }

                $form_data = $this->getFormDataForEmployee($employee_id);
                
                // Get PMT member for "Assessed by" field (prefer division_id match, fallback to section_id, then any)
                $pmt_assessed_by = null;
                if (Schema::hasTable('pmt') && Schema::hasColumn('pmt', 'is_opcr') && Schema::hasColumn('pmt', 'section_id') && isset($employee->section_id)) {
                    $pmt_record = null;

                    if (Schema::hasColumn('pmt', 'division_id') && isset($employee->division_id) && $employee->division_id) {
                        $pmt_record = DB::table('pmt')
                            ->where('is_opcr', 1)
                            ->where('division_id', $employee->division_id)
                            ->first();
                    }

                    if (!$pmt_record) {
                        $pmt_record = DB::table('pmt')
                            ->where('is_opcr', 1)
                            ->where('section_id', $employee->section_id)
                            ->first();
                    }

                    if (!$pmt_record) {
                        $pmt_record = DB::table('pmt')->where('is_opcr', 1)->first();
                    }

                    if ($pmt_record && isset($pmt_record->employee_id)) {
                        $pmt_assessed_by = [
                            'id' => $pmt_record->employee_id,
                            'name' => $this->getEmployeeDisplayNameById($pmt_record->employee_id)
                        ];
                    }
                }
                
                $formatted = [
                    'id' => $opcr_header->id,
                    'division' => $division_name,
                    'period' => ($opcr_header->period_start && $opcr_header->period_end) 
                        ? [$opcr_header->period_start, $opcr_header->period_end] 
                        : [],
                    'approvedBy' => $opcr_header->approved_by ?? '',
                    'approvedByEmployeeId' => $opcr_header->approved_by_employee_id ?? null,
                    'approvedDate' => $opcr_header->approved_date,
                    'outputs' => $outputs,
                    'planningOfficerEmployeeId' => null,
                    'planningOfficer' => '',
                    'assessedBy' => $opcr_header->assessed_by ?? '',
                    'assessedByEmployeeId' => $opcr_header->assessed_by_employee_id ?? null,
                    'assessedDate' => $opcr_header->assessed_date,
                    'finalRater' => $opcr_header->final_rater ?? '',
                    'finalRaterEmployeeId' => $opcr_header->final_rater_employee_id ?? null,
                    'finalRateDate' => $opcr_header->final_rate_date,
                    'employees' => $form_data['employees'],
                    'department_employees' => $form_data['department_employees'],
                    'pmt_assessed_by' => $pmt_assessed_by,
                    'categorySummary' => [
                        'strategic' => ['percentage' => null],
                        'core' => ['percentage' => null],
                        'support' => ['percentage' => null],
                    ],
                ];

                $formatted = array_merge($formatted, $this->getOpcrRecalibrationMeta((int) $id));
            } else {
                // Fallback to employee_opcr table
                $opcr_query = DB::table('employee_opcr')
                    ->where('id', $id);
                
                // PMT users and division chiefs can access all OPCR records
                if (!$is_division_chief && !$is_pmt_user) {
                    $opcr_query->where('employee_id', $employee_id);
                }
                
                $opcr = $opcr_query->first();

                if (!$opcr) {
                    return $this->errorResponse('OPCR record not found');
                }

                $outputsQuery = DB::table('employee_opcr_outputs')->where('employee_opcr_id', $id);
                $outputs = $this->orderOpcrOutputsQuery($outputsQuery, 'employee_opcr_outputs')
                    ->get()
                    ->map(function ($output) {
                        return $this->formatOpcrOutputRowForApi($output);
                    })->toArray();

                $form_data = $this->getFormDataForEmployee($employee_id);

                $pmt_assessed_by = null;
                if (Schema::hasTable('pmt') && Schema::hasColumn('pmt', 'is_opcr') && Schema::hasColumn('pmt', 'section_id') && isset($employee->section_id)) {
                    $pmt_record = DB::table('pmt')
                        ->where('is_opcr', 1)
                        ->where('section_id', $employee->section_id)
                        ->first();

                    if ($pmt_record && isset($pmt_record->employee_id)) {
                        $pmt_assessed_by = [
                            'id' => $pmt_record->employee_id,
                            'name' => $this->getEmployeeDisplayNameById($pmt_record->employee_id)
                        ];
                    }
                }

                $formatted = [
                    'id' => $opcr->id,
                    'division' => $opcr->division,
                    'period' => $opcr->period_start && $opcr->period_end
                        ? [$opcr->period_start, $opcr->period_end]
                        : [],
                    'approvedBy' => $opcr->approved_by,
                    'approvedByEmployeeId' => $opcr->approved_by_employee_id ?? null,
                    'approvedDate' => $opcr->approved_date,
                    'outputs' => $outputs,
                    'planningOfficerEmployeeId' => (Schema::hasColumn('employee_opcr', 'planning_officer_id') ? ($opcr->planning_officer_id ?? null) : null),
                    'planningOfficer' => (Schema::hasColumn('employee_opcr', 'planning_officer_id') && !empty($opcr->planning_officer_id))
                        ? ($this->getEmployeeDisplayNameById($opcr->planning_officer_id) ?? '')
                        : '',
                    'planningOfficerDate' => (Schema::hasColumn('employee_opcr', 'planning_officer_date') ? ($opcr->planning_officer_date ?? null) : null),
                    'assessedBy' => $opcr->assessed_by ?? '',
                    'assessedByEmployeeId' => $opcr->assessed_by_employee_id ?? null,
                    'assessedDate' => $opcr->assessed_date,
                    'finalRater' => $opcr->final_rater ?? '',
                    'finalRaterEmployeeId' => $opcr->final_rater_employee_id ?? null,
                    'finalRateDate' => $opcr->final_rate_date,
                    'employees' => $form_data['employees'],
                    'department_employees' => $form_data['department_employees'],
                    'pmt_assessed_by' => $pmt_assessed_by,
                    'categorySummary' => $this->formatCategorySummaryForApi($opcr),
                ];

                $formatted = array_merge($formatted, $this->getOpcrRecalibrationMeta((int) $id));
            }

            return $this->successResponse($formatted, 'OPCR record retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve OPCR record: ' . $e->getMessage());
        }
    }

    /**
     * Helper method to parse department IDs from departments_id field
     */
    private function parseDepartmentIds($departments_id)
    {
        if (empty($departments_id)) {
            return [];
        }

        // Try JSON decode first
        $decoded = json_decode($departments_id, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_map('intval', $decoded);
        }

        // Try comma-separated string
        if (strpos($departments_id, ',') !== false) {
            return array_map('intval', array_map('trim', explode(',', $departments_id)));
        }

        // Single integer
        return [(int)$departments_id];
    }

    /**
     * Employee Portal: Save OPCR (create or update)
     */
    public function employeeStore(Request $request, $id = null)
    {
        try {
            $validator = validator($request->all(), [
                'division' => 'required|string|max:255',
                'period' => 'required|array|size:2',
                'period.*' => 'required|date',
                'planningOfficerEmployeeId' => 'nullable|integer',
                'planningOfficerDate' => 'nullable|date',
                'outputs' => 'required|array|min:1',
                'outputs.*.mfoPap' => 'required|string',
                'outputs.*.functionType' => 'nullable|in:core,strategic,support',
                'outputs.*.function_type' => 'nullable|in:core,strategic,support',
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

            $employee_id = $employee->id;
            $employee_department_id = $employee->department_id ?? 0;

            // Check access: either division chief or tagged in opcr_details/opcr_headers
            $is_division_chief = DB::table('divisions')->where([
                'division_chief_id' => $employee_id,
                'active' => true
            ])->exists();

            $has_access = $this->hasAccessToOPCR($employee_id, $employee_department_id);
            $is_pmt_user = $this->isPMTUser($employee_id);
            $is_hr_user = $this->isHRUser($user);

            // For create/update operations, allow access if:
            // - Division chief, OR
            // - Employee's department is in opcr_headers, OR
            // - PMT member, OR
            // - HR user
            if (!$is_division_chief && !$has_access && !$is_pmt_user && !$is_hr_user) {
                return $this->errorResponse('OPCR is only available to employees in tagged departments, Division Chiefs, PMT members, or HR users.');
            }
            $data = $request->all();

            // Approved by must always be the Agency Head (branch head).
            // Ignore any client-provided approvedByEmployeeId to keep consistent workflow.
            $agency_head_id = $this->getAgencyHeadEmployeeIdForBranch($employee->branch_id ?? 0);
            if ($agency_head_id) {
                $data['approvedByEmployeeId'] = $agency_head_id;
            }

            // Assessed by must always be the PMT member (is_opcr = 1).
            // Prefer division-based PMT; fallback to section-based; then any OPCR PMT member.
            // Ignore any client-provided assessedByEmployeeId to keep consistent workflow.
            if (Schema::hasTable('pmt') && Schema::hasColumn('pmt', 'is_opcr')) {
                $pmt_emp_id = null;
                if (Schema::hasColumn('pmt', 'division_id') && isset($employee->division_id) && $employee->division_id) {
                    $pmt_emp_id = DB::table('pmt')
                        ->where('is_opcr', 1)
                        ->where('division_id', $employee->division_id)
                        ->value('employee_id');
                }
                if (!$pmt_emp_id && Schema::hasColumn('pmt', 'section_id') && isset($employee->section_id) && $employee->section_id) {
                    $pmt_emp_id = DB::table('pmt')
                        ->where('is_opcr', 1)
                        ->where('section_id', $employee->section_id)
                        ->value('employee_id');
                }
                if (!$pmt_emp_id) {
                    $pmt_emp_id = DB::table('pmt')->where('is_opcr', 1)->value('employee_id');
                }
                if ($pmt_emp_id) {
                    $data['assessedByEmployeeId'] = $pmt_emp_id;
                }
            }

            // Get employee names for storage
            $approved_by_name = null;
            $assessed_by_name = null;
            $final_rater_name = null;

            $app_key = env("APP_KEY", "");

            if (!empty($data['approvedByEmployeeId'])) {
                $approved_emp = DB::table('employees as a')
                    ->leftJoin('name_prefixes as b', 'a.name_prefix_id', '=', 'b.id')
                    ->leftJoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                    ->select(DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN 
                        CONCAT(COALESCE(b.name,''), ' ', a.first_name, ' ', COALESCE(a.middle_name,''), ' ', a.last_name, ' ', COALESCE(c.name,''))
                    ELSE 
                        CONCAT(COALESCE(b.name,''), ' ', RTRIM([dbo].[ufn_DecryptString](a.first_name,'" . $app_key . "')), ' ', 
                        RTRIM(COALESCE([dbo].[ufn_DecryptString](a.middle_name,'" . $app_key . "'),'')), ' ', 
                        RTRIM([dbo].[ufn_DecryptString](a.last_name,'" . $app_key . "')), ' ', COALESCE(c.name,''))
                    END as name"))
                    ->where('a.id', $data['approvedByEmployeeId'])
                    ->first();
                $approved_by_name = $approved_emp->name ?? null;
            }

            if (!empty($data['assessedByEmployeeId'])) {
                $assessed_emp = DB::table('employees as a')
                    ->leftJoin('name_prefixes as b', 'a.name_prefix_id', '=', 'b.id')
                    ->leftJoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                    ->select(DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN 
                        CONCAT(COALESCE(b.name,''), ' ', a.first_name, ' ', COALESCE(a.middle_name,''), ' ', a.last_name, ' ', COALESCE(c.name,''))
                    ELSE 
                        CONCAT(COALESCE(b.name,''), ' ', RTRIM([dbo].[ufn_DecryptString](a.first_name,'" . $app_key . "')), ' ', 
                        RTRIM(COALESCE([dbo].[ufn_DecryptString](a.middle_name,'" . $app_key . "'),'')), ' ', 
                        RTRIM([dbo].[ufn_DecryptString](a.last_name,'" . $app_key . "')), ' ', COALESCE(c.name,''))
                    END as name"))
                    ->where('a.id', $data['assessedByEmployeeId'])
                    ->first();
                $assessed_by_name = $assessed_emp->name ?? null;
            }

            if (!empty($data['finalRaterEmployeeId'])) {
                $final_rater_emp = DB::table('employees as a')
                    ->leftJoin('name_prefixes as b', 'a.name_prefix_id', '=', 'b.id')
                    ->leftJoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                    ->select(DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN 
                        CONCAT(COALESCE(b.name,''), ' ', a.first_name, ' ', COALESCE(a.middle_name,''), ' ', a.last_name, ' ', COALESCE(c.name,''))
                    ELSE 
                        CONCAT(COALESCE(b.name,''), ' ', RTRIM([dbo].[ufn_DecryptString](a.first_name,'" . $app_key . "')), ' ', 
                        RTRIM(COALESCE([dbo].[ufn_DecryptString](a.middle_name,'" . $app_key . "'),'')), ' ', 
                        RTRIM([dbo].[ufn_DecryptString](a.last_name,'" . $app_key . "')), ' ', COALESCE(c.name,''))
                    END as name"))
                    ->where('a.id', $data['finalRaterEmployeeId'])
                    ->first();
                $final_rater_name = $final_rater_emp->name ?? null;
            }

            // Determine which optional *_employee_id columns exist
            $hasReviewedById = Schema::hasColumn('employee_opcr', 'reviewed_by_employee_id');
            $hasApprovedById = Schema::hasColumn('employee_opcr', 'approved_by_employee_id');
            $hasAssessedById = Schema::hasColumn('employee_opcr', 'assessed_by_employee_id');
            $hasFinalRaterId = Schema::hasColumn('employee_opcr', 'final_rater_employee_id');

            $planningOfficerDate = $this->resolveOpcrSignatoryDate($data['planningOfficerDate'] ?? null);
            $approvedDate = $this->resolveOpcrSignatoryDate($data['approvedDate'] ?? null, $planningOfficerDate);
            $assessedDate = $this->resolveOpcrSignatoryDate($data['assessedDate'] ?? null, $planningOfficerDate, $approvedDate);
            $finalRateDate = $this->resolveOpcrSignatoryDate($data['finalRateDate'] ?? null, $planningOfficerDate, $approvedDate, $assessedDate);

            $header_data = [
                'employee_id' => $employee_id,
                'division' => $data['division'],
                'period_start' => $data['period'][0],
                'period_end' => $data['period'][1],
                'approved_by' => $approved_by_name,
                'approved_date' => $approvedDate,
                'assessed_by' => $assessed_by_name,
                'assessed_date' => $assessedDate,
                'final_rater' => $final_rater_name,
                'final_rate_date' => $finalRateDate,
                'updated_at' => now()
            ];

            if ($hasApprovedById) { $header_data['approved_by_employee_id'] = $data['approvedByEmployeeId'] ?? null; }
            if ($hasAssessedById) { $header_data['assessed_by_employee_id'] = $data['assessedByEmployeeId'] ?? null; }
            if ($hasFinalRaterId) { $header_data['final_rater_employee_id'] = $data['finalRaterEmployeeId'] ?? null; }

            // Planning Office (employee_opcr.planning_officer_id)
            if (Schema::hasColumn('employee_opcr', 'planning_officer_id')) {
                $header_data['planning_officer_id'] = $data['planningOfficerEmployeeId'] ?? null;
            }
            if (Schema::hasColumn('employee_opcr', 'planning_officer_date')) {
                $header_data['planning_officer_date'] = $planningOfficerDate;
            }

            $this->applyCategorySummaryToHeaderData($header_data, $data);

            $routeId = (int) ($id ?? 0);
            $bodyId = (int) ($request->input('id', 0));
            $id = $routeId > 0 ? $routeId : $bodyId;

            if ($id == 0) {
                // Creating new OPCR
                $header_data['created_at'] = now();
                $id = DB::table('employee_opcr')->insertGetId($header_data);
            } else {
                // Updating existing OPCR
                // Check if record exists in opcr_headers first
                $existing_header = null;
                $existing_opcr = null;
                
                if (Schema::hasTable('opcr_headers') && Schema::hasColumn('opcr_headers', 'departments_id')) {
                    $existing_header = DB::table('opcr_headers')
                    ->where('id', $id)
                    ->first();
                }
                
                // If not in opcr_headers, check employee_opcr
                if (!$existing_header) {
                    $existing_opcr = DB::table('employee_opcr')
                        ->where('id', $id)
                        ->first();
                }

                if (!$existing_header && !$existing_opcr) {
                    return $this->errorResponse('OPCR record not found or access denied');
                }

                if ($existing_opcr) {
                    $recalibrationMeta = $this->getOpcrRecalibrationMeta((int) $id);
                    if (!empty($recalibrationMeta['is_locked'])) {
                        return $this->errorResponse('This OPCR is locked after PMT recalibration and can no longer be edited.');
                    }
                }

                // Check access: PMT and HR users can update any OPCR, others can only update their own
                if (!$is_division_chief && !$is_pmt_user && !$is_hr_user) {
                    // For regular users, check if they own the record
                    if ($existing_opcr && $existing_opcr->employee_id != $employee_id) {
                        return $this->errorResponse('OPCR record not found or access denied');
                    }
                    
                    // For opcr_headers, check if user has access via departments
                    if ($existing_header) {
                        $has_access_to_record = false;
                        if ($existing_header->departments_id) {
                            $has_access_to_record = $this->isDepartmentInList($employee_department_id, $existing_header->departments_id);
                        }
                        
                        // Also check via opcr_details
                        if (!$has_access_to_record && Schema::hasTable('opcr_details')) {
                            if (Schema::hasColumn('opcr_details', 'employee_id')) {
                                $has_access_to_record = DB::table('opcr_details')
                                    ->where('opcr_header_id', $id)
                                    ->where('employee_id', $employee_id)
                                    ->exists();
                            }
                        }
                        
                        if (!$has_access_to_record) {
                            return $this->errorResponse('OPCR record not found or access denied');
                        }
                    }
                }

                // Update the record
                if ($existing_header) {
                    // Update opcr_headers table
                    DB::table('opcr_headers')->where('id', $id)->update($header_data);
                    
                    // Delete existing outputs from opcr_details
                    if (Schema::hasTable('opcr_details')) {
                        DB::table('opcr_details')->where('opcr_header_id', $id)->delete();
                    }
                } else {
                    // Update employee_opcr table
                DB::table('employee_opcr')->where('id', $id)->update($header_data);
                
                // Delete existing outputs
                DB::table('employee_opcr_outputs')->where('employee_opcr_id', $id)->delete();
                }
            }

            // Save outputs
            // Determine which table structure to use
            $use_opcr_headers_structure = false;
            if ($id != 0) {
                // Check if record exists in opcr_headers
                if (Schema::hasTable('opcr_headers') && Schema::hasColumn('opcr_headers', 'departments_id')) {
                    $header_exists = DB::table('opcr_headers')->where('id', $id)->exists();
                    if ($header_exists) {
                        $use_opcr_headers_structure = true;
                    }
                }
            }
            
            foreach ($data['outputs'] as $output) {
                $functionType = $this->normalizeOpcrFunctionType($output['functionType'] ?? $output['function_type'] ?? 'core');
                $category = $this->categoryLabelForFunctionType($functionType);
                $q = $this->normalizeRatingForStorage($output['q'] ?? null);
                $e = $this->normalizeRatingForStorage($output['e'] ?? null);
                $t = $this->normalizeRatingForStorage($output['t'] ?? null);
                $computedA = $this->computeAverageRating($output['q'] ?? null, $output['e'] ?? null, $output['t'] ?? null);

                $outputTable = $use_opcr_headers_structure ? 'opcr_details' : 'employee_opcr_outputs';
                $hasFunctionTypeColumn = $this->hasOpcrOutputFunctionTypeColumn($outputTable);

                if (in_array($functionType, ['strategic', 'support'], true) && !$hasFunctionTypeColumn) {
                    return $this->errorResponse(
                        'Strategic and support functions require the function_type column on ' . $outputTable . '. Please run database migrations.'
                    );
                }

                $row = [
                    'category' => $category,
                    'mfo_pap' => $output['mfoPap'] ?? null,
                    'success_indicators' => $output['successIndicators'] ?? null,
                    'allotted_budget' => $output['allottedBudget'] ?? null,
                    'division_individuals_accountable' => $output['divisionIndividualsAccountable'] ?? null,
                    'actual_accomplishments' => $output['actualAccomplishments'] ?? null,
                    'quality_rating' => $q,
                    'efficiency_rating' => $e,
                    'timeliness_rating' => $t,
                    'average_rating' => $computedA ?? ($this->normalizeRatingForStorage($output['a'] ?? null) >= 2
                        ? $this->normalizeRatingForStorage($output['a'] ?? null)
                        : 1),
                    'remarks' => $output['remarks'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($hasFunctionTypeColumn) {
                    $row['function_type'] = $functionType;
                }

                if ($use_opcr_headers_structure) {
                    $row['opcr_header_id'] = $id;
                    DB::table('opcr_details')->insert($row);
                } else {
                    $row['employee_opcr_id'] = $id;
                    DB::table('employee_opcr_outputs')->insert($row);
                }
            }

            return $this->successResponse(['id' => $id], 'OPCR saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save OPCR: ' . $e->getMessage());
        }
    }

    /**
     * Employee Portal: Delete OPCR
     */
    public function employeeDelete($id)
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
            $employee_department_id = $employee->department_id ?? 0;

            // Check access: either division chief or tagged in opcr_details/opcr_headers
            $is_division_chief = DB::table('divisions')->where([
                'division_chief_id' => $employee_id,
                'active' => true
            ])->exists();

            $has_access = $this->hasAccessToOPCR($employee_id, $employee_department_id);
            $is_pmt_user = $this->isPMTUser($employee_id);

            if (!$is_division_chief && !$has_access && !$is_pmt_user) {
                return $this->errorResponse('OPCR is only available to employees in tagged departments, Division Chiefs, or PMT members.');
            }

            // Check if opcr_headers exists, otherwise use employee_opcr
            if (Schema::hasTable('opcr_headers') && Schema::hasColumn('opcr_headers', 'departments_id')) {
                $opcr = DB::table('opcr_headers')
                    ->where('id', $id)
                    ->first();
                
                if (!$opcr) {
                    return $this->errorResponse('OPCR record not found or access denied');
                }

                // Check if employee has access to this specific OPCR
                if (!$is_division_chief) {
                    $has_access_to_this_opcr = false;
                    
                    // Check via opcr_details
                    if (Schema::hasTable('opcr_details')) {
                        if (Schema::hasColumn('opcr_details', 'employee_opcr_id')) {
                            $employee_opcr_ids = DB::table('employee_opcr')
                                ->where('employee_id', $employee_id)
                                ->pluck('id')
                                ->toArray();
                            
                            if (!empty($employee_opcr_ids)) {
                                $has_access_to_this_opcr = DB::table('opcr_details')
                                    ->where('opcr_header_id', $id)
                                    ->whereIn('employee_opcr_id', $employee_opcr_ids)
                                    ->exists();
                            }
                        }
                        
                        if (!$has_access_to_this_opcr && Schema::hasColumn('opcr_details', 'employee_id')) {
                            $has_access_to_this_opcr = DB::table('opcr_details')
                                ->where('opcr_header_id', $id)
                                ->where('employee_id', $employee_id)
                                ->exists();
                        }
                    }
                    
                    // Check via departments_id
                    if (!$has_access_to_this_opcr && $opcr->departments_id) {
                        $has_access_to_this_opcr = $this->isDepartmentInList($employee_department_id, $opcr->departments_id);
                    }
                    
                    if (!$has_access_to_this_opcr) {
                        return $this->errorResponse('You do not have access to this OPCR record.');
                    }
                }

                // Delete opcr_details first
                if (Schema::hasTable('opcr_details')) {
                    DB::table('opcr_details')->where('opcr_header_id', $id)->delete();
                }
                // Delete opcr_header
                DB::table('opcr_headers')->where('id', $id)->delete();
            } else {
                $opcr = DB::table('employee_opcr')
                    ->where('id', $id)
                    ->where('employee_id', $employee_id)
                    ->first();
                
                if (!$opcr) {
                    return $this->errorResponse('OPCR record not found or access denied');
                }

                DB::table('employee_opcr_outputs')->where('employee_opcr_id', $id)->delete();
                DB::table('employee_opcr')->where('id', $id)->delete();
            }


            return $this->successResponse(null, 'OPCR deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete OPCR: ' . $e->getMessage());
        }
    }

    /**
     * Employee Portal: Print OPCR as PDF
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
            $employee_department_id = $employee->department_id ?? 0;

            // Check access: either division chief or tagged in opcr_details/opcr_headers
            $is_division_chief = DB::table('divisions')->where([
                'division_chief_id' => $employee_id,
                'active' => true
            ])->exists();

            $has_access = $this->hasAccessToOPCR($employee_id, $employee_department_id);
            $is_pmt_user = $this->isPMTUser($employee_id);

            if (!$is_division_chief && !$has_access && !$is_pmt_user) {
                return $this->errorResponse('OPCR is only available to employees in tagged departments, Division Chiefs, or PMT members.');
            }

            $planning_officer_name = null;
            $planning_officer_date = null;
            $assessed_by_name_for_view = '';
            $final_rater_name_for_view = '';

            $use_opcr_headers = Schema::hasTable('opcr_headers') && Schema::hasColumn('opcr_headers', 'departments_id');
            $opcr_header = null;
            $outputs = [];

            if ($use_opcr_headers) {
                $opcr_header = DB::table('opcr_headers')
                    ->where('id', $id)
                    ->first();
            }

            if ($opcr_header) {
                // Check if employee has access to this specific OPCR
                // PMT users and division chiefs can access all OPCRs
                if (!$is_division_chief && !$is_pmt_user) {
                    $has_access_to_this_opcr = false;
                    
                    // Check via opcr_details
                    if (Schema::hasTable('opcr_details')) {
                        if (Schema::hasColumn('opcr_details', 'employee_opcr_id')) {
                            $employee_opcr_ids = DB::table('employee_opcr')
                                ->where('employee_id', $employee_id)
                                ->pluck('id')
                                ->toArray();
                            
                            if (!empty($employee_opcr_ids)) {
                                $has_access_to_this_opcr = DB::table('opcr_details')
                                    ->where('opcr_header_id', $id)
                                    ->whereIn('employee_opcr_id', $employee_opcr_ids)
                                    ->exists();
                            }
                        }
                        
                        if (!$has_access_to_this_opcr && Schema::hasColumn('opcr_details', 'employee_id')) {
                            $has_access_to_this_opcr = DB::table('opcr_details')
                                ->where('opcr_header_id', $id)
                                ->where('employee_id', $employee_id)
                                ->exists();
                        }
                    }
                    
                    // Check via departments_id
                    if (!$has_access_to_this_opcr && $opcr_header->departments_id) {
                        $has_access_to_this_opcr = $this->isDepartmentInList($employee_department_id, $opcr_header->departments_id);
                    }
                    
                    if (!$has_access_to_this_opcr) {
                        return $this->errorResponse('You do not have access to this OPCR record.');
                    }
                }

                // Get opcr_details
                $outputs = [];
                if (Schema::hasTable('opcr_details')) {
                    $outputsQuery = DB::table('opcr_details')->where('opcr_header_id', $id);
                    $outputs = $this->orderOpcrOutputsQuery($outputsQuery, 'opcr_details')->get();
                }

                // Get division/office from OPCR record
                $division_name = '';
                
                // First, check if opcr_headers has a division field
                if (Schema::hasColumn('opcr_headers', 'division') && !empty($opcr_header->division)) {
                    $division_name = $opcr_header->division;
                } 
                // If not, try to get from related employee_opcr records via opcr_details
                else if (Schema::hasTable('opcr_details') && Schema::hasColumn('opcr_details', 'employee_opcr_id')) {
                    $employee_opcr_ids = DB::table('opcr_details')
                        ->where('opcr_header_id', $id)
                        ->whereNotNull('employee_opcr_id')
                        ->distinct()
                        ->pluck('employee_opcr_id')
                        ->toArray();
                    
                    if (!empty($employee_opcr_ids)) {
                        $employee_opcr = DB::table('employee_opcr')
                            ->whereIn('id', $employee_opcr_ids)
                            ->whereNotNull('division')
                            ->first();
                        
                        if ($employee_opcr && !empty($employee_opcr->division)) {
                            $division_name = $employee_opcr->division;
                        }
                    }
                }
                
                // Fallback to department name if division is still empty
                if (empty($division_name) && $opcr_header->departments_id) {
                    $dept_ids = $this->parseDepartmentIds($opcr_header->departments_id);
                    if (!empty($dept_ids)) {
                        $dept = DB::table('departments')
                            ->whereIn('id', $dept_ids)
                            ->where('active', true)
                            ->first();
                        $division_name = $dept ? $dept->name : '';
                    }
                }
                
                // Resolve assessed_by and final_rater display names for print
                $assessed_by_name = $opcr_header->assessed_by ?? null;
                $final_rater_name = $opcr_header->final_rater ?? null;

                if (Schema::hasColumn('opcr_headers', 'assessed_by_employee_id') && isset($opcr_header->assessed_by_employee_id)) {
                    $assessed_by_name = $this->getEmployeeDisplayNameById($opcr_header->assessed_by_employee_id);
                } elseif (is_numeric($assessed_by_name)) {
                    $assessed_by_name = $this->getEmployeeDisplayNameById((int)$assessed_by_name);
                }

                if (Schema::hasColumn('opcr_headers', 'final_rater_employee_id') && isset($opcr_header->final_rater_employee_id)) {
                    $final_rater_name = $this->getEmployeeDisplayNameById($opcr_header->final_rater_employee_id);
                } elseif (is_numeric($final_rater_name)) {
                    $final_rater_name = $this->getEmployeeDisplayNameById((int)$final_rater_name);
                }

                // Values to pass to view
                $assessed_by_name_for_view = $assessed_by_name ?? '';
                $final_rater_name_for_view = $final_rater_name ?? '';

                // Convert opcr_header to format expected by view
                $opcr = (object)[
                    'id' => $opcr_header->id,
                    'employee_id' => $opcr_header->employee_id ?? null,
                    'division' => $division_name,
                    'period_start' => $opcr_header->period_start ?? null,
                    'period_end' => $opcr_header->period_end ?? null,
                    'reviewed_by' => $opcr_header->reviewed_by ?? '',
                    'reviewed_date' => $opcr_header->reviewed_date ?? null,
                    'approved_by' => $opcr_header->approved_by ?? '',
                    'approved_by_employee_id' => $opcr_header->approved_by_employee_id ?? null,
                    'approved_date' => $opcr_header->approved_date ?? null,
                    'planning_officer_id' => $opcr_header->planning_officer_id ?? null,
                    'planning_officer_date' => $opcr_header->planning_officer_date ?? null,
                    'assessed_by' => $assessed_by_name_for_view,
                    'assessed_by_employee_id' => $opcr_header->assessed_by_employee_id ?? null,
                    'assessed_date' => $opcr_header->assessed_date ?? null,
                    'final_rater' => $final_rater_name_for_view,
                    'final_rater_employee_id' => $opcr_header->final_rater_employee_id ?? null,
                    'final_rate_date' => $opcr_header->final_rate_date ?? null,
                ];

                if (Schema::hasColumn('opcr_headers', 'planning_officer_id') && !empty($opcr_header->planning_officer_id)) {
                    $planning_officer_name = $this->getEmployeeDisplayNameById($opcr_header->planning_officer_id);
                }
                if (Schema::hasColumn('opcr_headers', 'planning_officer_date')) {
                    $planning_officer_date = $opcr_header->planning_officer_date ?? null;
                }
            } else {
                // Fallback to employee_opcr table (new portal records are stored here)
                $opcr_query = DB::table('employee_opcr')->where('id', $id);

                if (!$is_division_chief && !$is_pmt_user) {
                    $opcr_query->where('employee_id', $employee_id);
                }

                $opcr = $opcr_query->first();
                if (!$opcr) {
                    return $this->errorResponse('OPCR record not found');
                }

                // Resolve assessed_by and final_rater display names for print when *_employee_id columns exist
                $assessed_by_name = $opcr->assessed_by ?? null;
                $final_rater_name = $opcr->final_rater ?? null;

                if (Schema::hasColumn('employee_opcr', 'assessed_by_employee_id') && isset($opcr->assessed_by_employee_id)) {
                    $assessed_by_name = $this->getEmployeeDisplayNameById($opcr->assessed_by_employee_id);
                } elseif (is_numeric($assessed_by_name)) {
                    $assessed_by_name = $this->getEmployeeDisplayNameById((int)$assessed_by_name);
                }

                if (Schema::hasColumn('employee_opcr', 'final_rater_employee_id') && isset($opcr->final_rater_employee_id)) {
                    $final_rater_name = $this->getEmployeeDisplayNameById($opcr->final_rater_employee_id);
                } elseif (is_numeric($final_rater_name)) {
                    $final_rater_name = $this->getEmployeeDisplayNameById((int)$final_rater_name);
                }

                $assessed_by_name_for_view = $assessed_by_name ?? '';
                $final_rater_name_for_view = $final_rater_name ?? '';

                $opcr->assessed_by = $assessed_by_name_for_view;
                $opcr->final_rater = $final_rater_name_for_view;

                // Planning officer for view (if columns exist)
                if (Schema::hasColumn('employee_opcr', 'planning_officer_id') && isset($opcr->planning_officer_id)) {
                    $planning_officer_name = $this->getEmployeeDisplayNameById($opcr->planning_officer_id);
                }
                if (Schema::hasColumn('employee_opcr', 'planning_officer_date')) {
                    $planning_officer_date = $opcr->planning_officer_date ?? null;
                }

                $outputsQuery = DB::table('employee_opcr_outputs')->where('employee_opcr_id', $id);
                $outputs = $this->orderOpcrOutputsQuery($outputsQuery, 'employee_opcr_outputs')->get();
            }

            $mappedOutputs = collect($outputs)->map(fn ($row) => $this->mapOpcrOutputForPrint($row))->toArray();
            $groupedOutputs = $this->buildGroupedOpcrPrintOutputs($outputs);

            $headEmployeeId = $opcr->employee_id ?? null;
            if (!$headEmployeeId && !empty($opcr->division)) {
                $headEmployeeId = DB::table('divisions')
                    ->where('name', $opcr->division)
                    ->where('active', true)
                    ->value('division_chief_id');
            }
            if (!$headEmployeeId) {
                $headEmployeeId = $employee_id;
            }

            $headName = $this->getEmployeeDisplayNameById($headEmployeeId);
            $headPosition = !empty($opcr->division) ? 'Head of ' . $opcr->division : '';

            $periodText = '';
            if (!empty($opcr->period_start) && !empty($opcr->period_end)) {
                $periodText = date('M d', strtotime($opcr->period_start)) . ' - ' . date('M d, Y', strtotime($opcr->period_end));
            }

            $agencyHeadId = null;
            if (isset($opcr->approved_by_employee_id) && $opcr->approved_by_employee_id) {
                $agencyHeadId = $opcr->approved_by_employee_id;
            }
            $agencyHeadName = $agencyHeadId
                ? $this->getEmployeeDisplayNameById($agencyHeadId)
                : ($opcr->approved_by ?? '');

            if (empty($final_rater_name_for_view)) {
                $finalRaterId = $opcr->final_rater_employee_id ?? null;
                $final_rater_name_for_view = $finalRaterId
                    ? ($this->getEmployeeDisplayNameById($finalRaterId) ?? '')
                    : ($opcr->final_rater ?? $agencyHeadName ?? '');
            }

            if (!isset($opcr->final_rater_date)) {
                $opcr->final_rater_date = $opcr->final_rate_date ?? null;
            }

            $data = [
                'opcr' => $opcr,
                'outputs' => $mappedOutputs,
                'groupedOutputs' => $groupedOutputs,
                'categorySummary' => $this->buildCategorySummaryForPrint($opcr, $groupedOutputs),
                'headName' => $headName,
                'headPosition' => $headPosition,
                'periodText' => $periodText,
                'agencyHeadName' => $agencyHeadName,
                'planningOfficerName' => $planning_officer_name,
                'pmtName' => $assessed_by_name_for_view,
                'finalRaterName' => $final_rater_name_for_view,
            ];

            $pdf = \PDF::loadView('opcr/opcr_form', $data)->setPaper('a4', 'portrait');
            return $pdf->stream('opcr_' . $id . '.pdf');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate OPCR PDF: ' . $e->getMessage());
        }
    }

    /**
     * Helper method to get form data for employee
     */
    private function getFormDataForEmployee($employee_id)
    {
        $app_key = env("APP_KEY", "");
        
        $division = DB::table('divisions')
            ->where('division_chief_id', $employee_id)
            ->where('active', true)
            ->first();

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

        $division_employees = [];
        if ($division) {
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
                    'a.division_id' => $division->id,
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
     * Employee Portal: Save OPCR recalibration (HR/PMT)
     * Uses opcr_recalibrations table. PMT may recalibrate directly after submission
     * (same as DPCR); HR recalibration is optional and not required before PMT.
     */
    public function saveRecalibration(Request $request, $opcrId)
    {
        try {
            $validator = validator($request->all(), [
                'recalibration_level' => 'required|in:hr,pmt',
                'outputs' => 'required|array|min:1',
                'outputs.*.id' => 'required|integer|exists:employee_opcr_outputs,id',
                'outputs.*.q' => 'required|numeric|min:2|max:5',
                'outputs.*.e' => 'required|numeric|min:2|max:5',
                'outputs.*.t' => 'required|numeric|min:2|max:5',
                // A is computed from Q/E/T; accept it if provided for backward compatibility
                'outputs.*.a' => 'nullable|numeric|min:2|max:5',
                'outputs.*.remarks' => 'nullable|string',
                'categorySummary' => 'nullable|array',
                'categorySummary.strategic' => 'nullable|array',
                'categorySummary.core' => 'nullable|array',
                'categorySummary.support' => 'nullable|array',
                'categorySummary.strategic.percentage' => 'nullable|numeric|min:0|max:100',
                'categorySummary.core.percentage' => 'nullable|numeric|min:0|max:100',
                'categorySummary.support.percentage' => 'nullable|numeric|min:0|max:100',
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

            // Verify OPCR exists
            $opcr = DB::table('employee_opcr')
                ->where('id', $opcrId)
                ->first();

            if (!$opcr) {
                return $this->errorResponse('OPCR record not found');
            }

            $recalibrationMeta = $this->getOpcrRecalibrationMeta((int) $opcrId);
            if (!empty($recalibrationMeta['is_locked'])) {
                return $this->errorResponse('This OPCR has already been PMT recalibrated.');
            }

            // Verify authorization based on recalibration level
            $is_authorized = false;
            switch ($recalibration_level) {
                case 'hr':
                    $is_authorized = $this->isHRUser($user);
                    break;
                case 'pmt':
                    $is_authorized = $this->isPMTUser($recalibrator_id);
                    break;
            }

            if (!$is_authorized) {
                return $this->errorResponse("You are not authorized to perform {$recalibration_level} recalibration.");
            }

            $data = $request->all();
            $processed = 0;

            DB::beginTransaction();

            try {
                // Pull status IDs from status table (fallback to sensible string defaults)
                $status_submitted_id = DB::table('status')->where('name', 'Submitted')->value('id');
                $status_recalibrated_id = DB::table('status')->where('name', 'Recalibrated')->value('id');

                foreach ($data['outputs'] as $output_data) {
                    $computedA = $this->computeAverageRating(
                        $output_data['q'] ?? null,
                        $output_data['e'] ?? null,
                        $output_data['t'] ?? null
                    );

                    // Check if recalibration already exists for this level
                    $existing = DB::table('opcr_recalibrations')
                        ->where('employee_opcr_output_id', $output_data['id'])
                        ->where('recalibration_level', $recalibration_level)
                        ->first();

                    $recal_data = [
                        'employee_opcr_output_id' => $output_data['id'],
                        'recalibrated_by_employee_id' => $recalibrator_id,
                        'recalibration_level' => $recalibration_level,
                        'quality_rating' => $output_data['q'],
                        'efficiency_rating' => $output_data['e'],
                        'timeliness_rating' => $output_data['t'],
                        // A is the average of Q/E/T (2 decimals), not a manual input
                        'average_rating' => $computedA ?? ($output_data['a'] ?? 2),
                        'remarks' => $output_data['remarks'] ?? null,
                        // Status: pmt -> Recalibrated, hr -> Submitted
                        'status' => ($recalibration_level === 'pmt')
                            ? ($status_recalibrated_id ?? 'Recalibrated')
                            : ($status_submitted_id ?? 'Submitted'),
                        'updated_at' => now(),
                    ];

                    if ($existing) {
                        DB::table('opcr_recalibrations')
                            ->where('id', $existing->id)
                            ->update($recal_data);
                    } else {
                        $recal_data['created_at'] = now();
                        DB::table('opcr_recalibrations')->insert($recal_data);
                    }

                    $processed++;
                }

                if ($recalibration_level === 'pmt') {
                    $categoryUpdate = ['updated_at' => now()];
                    $this->applyCategorySummaryToHeaderData($categoryUpdate, $data);
                    if (count($categoryUpdate) > 1) {
                        DB::table('employee_opcr')
                            ->where('id', $opcrId)
                            ->update($categoryUpdate);
                    }
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
            return $this->serverErrorResponse('Failed to save OPCR recalibration: ' . $e->getMessage());
        }
    }
}

