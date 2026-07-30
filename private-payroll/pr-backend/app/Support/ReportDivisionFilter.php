<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportDivisionFilter
{
    public static function activeDivisions()
    {
        return DB::table('divisions')
            ->where('active', true)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * @return mixed|null division id or null when "all" / empty
     */
    public static function resolveId(Request $request)
    {
        $id = $request->input('division_id', $request->input('department_id'));

        if ($id === 'all' || $id === '' || $id === null) {
            return null;
        }

        return $id;
    }

    public static function displayName($divisionId): string
    {
        if (empty($divisionId)) {
            return 'ALL DIVISIONS';
        }

        return DB::table('divisions')->where('id', $divisionId)->value('name') ?: 'ALL DIVISIONS';
    }

    /**
     * Values stored in legacy header.department_id columns when filtering by division.
     * Includes the division id itself plus department ids used by employees in that division.
     */
    public static function legacyHeaderDepartmentIdsForDivision($divisionId): array
    {
        $divisionId = (int) $divisionId;
        $ids = [$divisionId];

        $fromEmployees = DB::table('employees')
            ->where('division_id', $divisionId)
            ->whereNotNull('department_id')
            ->distinct()
            ->pluck('department_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique(array_merge($ids, $fromEmployees)));
    }

    /**
     * Map reimbursement_headers.department_id to a divisions.id (legacy dept ids supported).
     */
    public static function resolveHeaderDivisionId($storedId): ?int
    {
        if ($storedId === null || $storedId === '') {
            return null;
        }

        $storedId = (int) $storedId;

        if (DB::table('divisions')->where('id', $storedId)->exists()) {
            return $storedId;
        }

        $divisionId = DB::table('employees')
            ->where('department_id', $storedId)
            ->whereNotNull('division_id')
            ->value('division_id');

        return $divisionId ? (int) $divisionId : $storedId;
    }

    /**
     * Active divisions that have at least one reimbursement header with employee details.
     */
    public static function divisionsWithReimbursementData()
    {
        $storedIds = DB::table('reimbursement_headers as h')
            ->join('reimbursement_details as d', 'h.id', '=', 'd.reimbursement_headers_id')
            ->distinct()
            ->pluck('h.department_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $divisionIds = [];
        foreach ($storedIds as $storedId) {
            if (DB::table('divisions')->where('id', $storedId)->where('active', true)->exists()) {
                $divisionIds[] = $storedId;
                continue;
            }

            $fromEmployees = DB::table('employees')
                ->where('department_id', $storedId)
                ->whereNotNull('division_id')
                ->distinct()
                ->pluck('division_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $divisionIds = array_merge($divisionIds, $fromEmployees);
        }

        $divisionIds = array_values(array_unique(array_filter($divisionIds)));

        if (empty($divisionIds)) {
            return collect();
        }

        return DB::table('divisions')
            ->where('active', true)
            ->whereIn('id', $divisionIds)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Active divisions that have at least one hazard pay header with employee details.
     */
    public static function divisionsWithHazardPayData()
    {
        $storedIds = DB::table('hazard_pay_headers as h')
            ->join('hazard_pay_details as d', 'h.id', '=', 'd.hazard_pay_id')
            ->distinct()
            ->pluck('h.department_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $divisionIds = [];
        foreach ($storedIds as $storedId) {
            if (DB::table('divisions')->where('id', $storedId)->where('active', true)->exists()) {
                $divisionIds[] = $storedId;
                continue;
            }

            $resolved = self::resolveHeaderDivisionId($storedId);
            if ($resolved) {
                $divisionIds[] = $resolved;
            }
        }

        $divisionIds = array_values(array_unique(array_filter($divisionIds)));

        if (empty($divisionIds)) {
            return collect();
        }

        return DB::table('divisions')
            ->where('active', true)
            ->whereIn('id', $divisionIds)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Active divisions that have at least one mid-year bonus record.
     */
    public static function divisionsWithMidyearBonusData()
    {
        $storedIds = DB::table('midyear_bonus')
            ->distinct()
            ->pluck('department_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $divisionIds = [];
        foreach ($storedIds as $storedId) {
            if (DB::table('divisions')->where('id', $storedId)->where('active', true)->exists()) {
                $divisionIds[] = $storedId;
                continue;
            }

            $resolved = self::resolveHeaderDivisionId($storedId);
            if ($resolved) {
                $divisionIds[] = $resolved;
            }
        }

        $divisionIds = array_values(array_unique(array_filter($divisionIds)));

        if (empty($divisionIds)) {
            return collect();
        }

        return DB::table('divisions')
            ->where('active', true)
            ->whereIn('id', $divisionIds)
            ->orderBy('name', 'asc')
            ->get();
    }
}
