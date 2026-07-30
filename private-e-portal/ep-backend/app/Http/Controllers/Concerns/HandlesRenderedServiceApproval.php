<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

trait HandlesRenderedServiceApproval
{
    private function employeeHasRenderedServiceApprover($employeeId)
    {
        return DB::table('approver_details as ad')
            ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
            ->where('ad.employee_id', $employeeId)
            ->where('ah.type_id', self::RENDERED_SERVICE_TYPE_ID)
            ->exists();
    }

    private function getRenderedServiceApproverRoles($empId)
    {
        $typeId = self::RENDERED_SERVICE_TYPE_ID;

        $approver_1 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select('a.id', 'a.approver_id_1 as supervisor_id')
            ->where('a.approver_id_1', $empId)
            ->where('a.type_id', $typeId)
            ->distinct()
            ->get();

        $approver_2 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select('a.id', 'a.approver_id_2 as supervisor_id', 'a.approver_id_3')
            ->where('a.approver_id_2', $empId)
            ->where('a.type_id', $typeId)
            ->distinct()
            ->get();

        $approver_3 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select('a.id', 'a.approver_id_3 as supervisor_id', 'a.approver_id_3')
            ->where(function ($q) use ($empId) {
                $q->where('a.approver_id_3', $empId)->orWhere('a.approver_id_4', $empId);
            })
            ->where('a.type_id', $typeId)
            ->distinct()
            ->get();

        return compact('approver_1', 'approver_2', 'approver_3');
    }

    private function hasRenderedServiceApproverRole(array $roles)
    {
        return $roles['approver_1']->isNotEmpty()
            || $roles['approver_2']->isNotEmpty()
            || $roles['approver_3']->isNotEmpty();
    }

    private function getRenderedServiceApproversForRequestEmployee($empId, $employeeId)
    {
        $typeId = self::RENDERED_SERVICE_TYPE_ID;

        $approver_1 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select('a.id', 'a.approver_id_1 as supervisor_id', 'a.approver_id_3')
            ->where('b.employee_id', $employeeId)
            ->where('a.approver_id_1', $empId)
            ->where('a.type_id', $typeId)
            ->distinct()
            ->get();

        $approver_2 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select('a.id', 'a.approver_id_2 as supervisor_id', 'a.approver_id_3')
            ->where('b.employee_id', $employeeId)
            ->where('a.approver_id_2', $empId)
            ->where('a.type_id', $typeId)
            ->distinct()
            ->get();

        $approver_3 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select('a.id', 'a.approver_id_3 as supervisor_id', 'a.approver_id_3')
            ->where('b.employee_id', $employeeId)
            ->where(function ($q) use ($empId) {
                $q->where('a.approver_id_3', $empId)->orWhere('a.approver_id_4', $empId);
            })
            ->where('a.type_id', $typeId)
            ->distinct()
            ->get();

        return compact('approver_1', 'approver_2', 'approver_3');
    }

    private function getRenderedServiceSubordinateEmployeeIds($empId)
    {
        return DB::table('approver_details as ad')
            ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
            ->where('ah.type_id', self::RENDERED_SERVICE_TYPE_ID)
            ->where(function ($q) use ($empId) {
                $q->where('ah.approver_id_1', $empId)
                    ->orWhere('ah.approver_id_2', $empId)
                    ->orWhere('ah.approver_id_3', $empId)
                    ->orWhere('ah.approver_id_4', $empId);
            })
            ->pluck('ad.employee_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function canAccessRenderedServiceRequest($currentEmployeeId, $applicationEmployeeId)
    {
        if ($currentEmployeeId <= 0) {
            return false;
        }

        if ((int) $applicationEmployeeId === $currentEmployeeId) {
            return true;
        }

        $subordinateIds = $this->getRenderedServiceSubordinateEmployeeIds($currentEmployeeId);

        return in_array((int) $applicationEmployeeId, $subordinateIds, true);
    }

    private function resolveRenderedServiceCertificatePath($requestRow)
    {
        $path = $requestRow->certificate_path ?? null;
        if ($path && is_file($path)) {
            return $path;
        }

        $employeeId = (int) ($requestRow->employee_id ?? 0);
        $requestId = (int) ($requestRow->id ?? 0);
        if ($employeeId <= 0 || $requestId <= 0) {
            return null;
        }

        $relativePath = 'service_rendered_certificates/SRS' . $employeeId . $requestId . '.pdf';
        $candidates = [
            storage_path('app/' . $relativePath),
            Storage::disk('local')->path($relativePath),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function readRenderedServiceCertificateContents($requestRow)
    {
        $path = $this->resolveRenderedServiceCertificatePath($requestRow);
        if (!$path) {
            return null;
        }

        return file_get_contents($path);
    }

    private function baseRenderedServiceApprovalQuery($appKey)
    {
        return DB::table('service_rendered_requests as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->leftJoin('departments as d', 'b.department_id', '=', 'd.id')
            ->leftJoin('divisions as e', 'b.division_id', '=', 'e.id')
            ->leftJoin('positions as g', 'b.position_id', '=', 'g.id')
            ->select(
                'a.id',
                'a.employee_id',
                'a.request_date',
                'a.date_start',
                'a.date_end',
                'a.noted_by',
                'a.noted_by_position',
                'a.approved_by',
                'a.approved_by_position',
                'a.status',
                'a.approved_1',
                'a.approved_2',
                'a.disapproved_1',
                'a.disapproved_2',
                'a.certificate_path',
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$appKey'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$appKey'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$appKey'))
                            END as name"),
                'd.name as department',
                'e.name as division',
                'g.name as position'
            );
    }

    private function queryRenderedServiceApprovalsForLevel($empId, $appKey, $level)
    {
        $typeId = self::RENDERED_SERVICE_TYPE_ID;

        return $this->baseRenderedServiceApprovalQuery($appKey)
            ->addSelect(DB::raw("CAST($level as int) as approver_level_id"))
            ->whereIn('a.id', function ($subQuery) use ($empId, $level, $typeId) {
                $subQuery->select('c.id')
                    ->from('approver_details as ad')
                    ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                    ->join('service_rendered_requests as c', 'ad.employee_id', '=', 'c.employee_id')
                    ->where('ah.type_id', $typeId);

                if ($level === 1) {
                    $subQuery->where('ah.approver_id_1', $empId)
                        ->whereRaw('(ISNULL(c.approved_1,0) = 0 AND ISNULL(c.disapproved_1,0) = 0)')
                        ->whereRaw('(ISNULL(c.status,0) = 0)');
                } elseif ($level === 2) {
                    $subQuery->where('ah.approver_id_2', $empId)
                        ->whereRaw('(ISNULL(c.approved_1,0) = 1 AND ISNULL(c.disapproved_1,0) = 0)')
                        ->whereRaw('(ISNULL(c.approved_2,0) = 0 AND ISNULL(c.disapproved_2,0) = 0)')
                        ->whereRaw('(ISNULL(c.status,0) = 0)');
                } else {
                    $subQuery->where(function ($q) use ($empId) {
                        $q->where('ah.approver_id_3', $empId)->orWhere('ah.approver_id_4', $empId);
                    })
                        ->whereRaw('(ISNULL(c.approved_1,0) = 1 AND ISNULL(c.disapproved_1,0) = 0)')
                        ->whereRaw('(ISNULL(c.approved_2,0) = 1 AND ISNULL(c.disapproved_2,0) = 0)')
                        ->whereRaw('(ISNULL(c.status,0) = 0)');
                }
            })
            ->distinct()
            ->orderBy('a.request_date', 'desc')
            ->get();
    }

    private function queryRenderedServiceApprovedForLevel($empId, $appKey, $level)
    {
        $typeId = self::RENDERED_SERVICE_TYPE_ID;

        return $this->baseRenderedServiceApprovalQuery($appKey)
            ->addSelect(DB::raw("CAST($level as int) as approver_level_id"))
            ->whereIn('a.id', function ($subQuery) use ($empId, $level, $typeId) {
                $subQuery->select('c.id')
                    ->from('approver_details as ad')
                    ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                    ->join('service_rendered_requests as c', 'ad.employee_id', '=', 'c.employee_id')
                    ->where('ah.type_id', $typeId);

                if ($level === 1) {
                    $subQuery->where('ah.approver_id_1', $empId)
                        ->whereRaw('(ISNULL(c.approved_1,0) = 1 AND ISNULL(c.disapproved_1,0) = 0)');
                } elseif ($level === 2) {
                    $subQuery->where('ah.approver_id_2', $empId)
                        ->whereRaw('(ISNULL(c.approved_2,0) = 1 AND ISNULL(c.disapproved_2,0) = 0)');
                } else {
                    $subQuery->where(function ($q) use ($empId) {
                        $q->where('ah.approver_id_3', $empId)->orWhere('ah.approver_id_4', $empId);
                    })
                        ->whereRaw('(ISNULL(c.approved_2,0) = 1 AND ISNULL(c.disapproved_2,0) = 0)')
                        ->whereRaw('(ISNULL(c.status,0) = 1)');
                }
            })
            ->distinct()
            ->orderBy('a.request_date', 'desc')
            ->get();
    }

    private function collectRenderedServicePendingForApprover($empId, $appKey, array $roles)
    {
        $approver_1 = $roles['approver_1'];
        $approver_2 = $roles['approver_2'];
        $approver_3 = $roles['approver_3'];
        $pending = collect();

        if ($approver_3->isNotEmpty()) {
            $pending = $this->queryRenderedServiceApprovalsForLevel($empId, $appKey, 3);
        } elseif ($approver_1->isNotEmpty() && $approver_2->isEmpty()) {
            $pending = $this->queryRenderedServiceApprovalsForLevel($empId, $appKey, 1);
        } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty()) {
            $pending = $this->queryRenderedServiceApprovalsForLevel($empId, $appKey, 2);
        } elseif ($approver_1->isNotEmpty() && $approver_2->isNotEmpty()) {
            $pending = $this->queryRenderedServiceApprovalsForLevel($empId, $appKey, 1)
                ->merge($this->queryRenderedServiceApprovalsForLevel($empId, $appKey, 2))
                ->unique('id')
                ->values();
        }

        return $pending;
    }

    private function collectRenderedServiceApprovedForApprover($empId, $appKey, array $roles)
    {
        $approver_1 = $roles['approver_1'];
        $approver_2 = $roles['approver_2'];
        $approver_3 = $roles['approver_3'];
        $approved = collect();

        if ($approver_3->isNotEmpty()) {
            $approved = $this->queryRenderedServiceApprovedForLevel($empId, $appKey, 3);
        } elseif ($approver_1->isNotEmpty() && $approver_2->isEmpty()) {
            $approved = $this->queryRenderedServiceApprovedForLevel($empId, $appKey, 1);
        } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty()) {
            $approved = $this->queryRenderedServiceApprovedForLevel($empId, $appKey, 2);
        } elseif ($approver_1->isNotEmpty() && $approver_2->isNotEmpty()) {
            $approved = $this->queryRenderedServiceApprovedForLevel($empId, $appKey, 1)
                ->merge($this->queryRenderedServiceApprovedForLevel($empId, $appKey, 2))
                ->unique('id')
                ->values();
        }

        return $approved;
    }

    private function queryRenderedServiceReturnedForApprover($empId, $appKey)
    {
        $subordinateIds = $this->getRenderedServiceSubordinateEmployeeIds($empId);
        if (empty($subordinateIds)) {
            return collect();
        }

        return $this->baseRenderedServiceApprovalQuery($appKey)
            ->whereIn('b.id', $subordinateIds)
            ->where(function ($q) {
                $q->whereRaw('(ISNULL(a.disapproved_1,0) = 1)')
                    ->orWhereRaw('(ISNULL(a.disapproved_2,0) = 1)');
            })
            ->distinct()
            ->orderBy('a.request_date', 'desc')
            ->get();
    }

    private function toRenderedServiceBool($value): bool
    {
        return (int) ($value ?? 0) === 1 || $value === true;
    }

    private function getRenderedServiceMaxApproverLevelForEmployee($employeeId)
    {
        $approverHeader = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->where('b.employee_id', $employeeId)
            ->where('a.type_id', self::RENDERED_SERVICE_TYPE_ID)
            ->select('a.approver_id_1', 'a.approver_id_2', 'a.approver_id_3', 'a.approver_id_4')
            ->first();

        $maxApproverLevel = 1;
        if ($approverHeader) {
            if (!empty($approverHeader->approver_id_3) || !empty($approverHeader->approver_id_4)) {
                $maxApproverLevel = 3;
            } elseif (!empty($approverHeader->approver_id_2)) {
                $maxApproverLevel = 2;
            }
        }

        return $maxApproverLevel;
    }

    private function isRenderedServiceRequestFullyApproved($request)
    {
        if ($this->toRenderedServiceBool($request->disapproved_1 ?? 0) || $this->toRenderedServiceBool($request->disapproved_2 ?? 0)) {
            return false;
        }

        if ($this->toRenderedServiceBool($request->status ?? 0)) {
            return true;
        }

        $employeeId = (int) ($request->employee_id ?? 0);
        if ($employeeId <= 0) {
            return false;
        }

        $maxLevel = $this->getRenderedServiceMaxApproverLevelForEmployee($employeeId);

        if ($maxLevel === 1) {
            return $this->toRenderedServiceBool($request->approved_1 ?? 0);
        }

        if ($maxLevel === 2) {
            return $this->toRenderedServiceBool($request->approved_2 ?? 0);
        }

        return false;
    }

    private function formatRenderedServiceRequestStatus($request)
    {
        if ($this->toRenderedServiceBool($request->disapproved_1 ?? 0) || $this->toRenderedServiceBool($request->disapproved_2 ?? 0)) {
            return 'Returned';
        }

        if ($this->isRenderedServiceRequestFullyApproved($request)) {
            return 'Approved';
        }

        $employeeId = (int) ($request->employee_id ?? 0);
        $maxLevel = $employeeId > 0 ? $this->getRenderedServiceMaxApproverLevelForEmployee($employeeId) : 2;

        if ($this->toRenderedServiceBool($request->approved_2 ?? 0) && $maxLevel >= 3) {
            return 'Pending Level 3';
        }

        if ($this->toRenderedServiceBool($request->approved_1 ?? 0) && $maxLevel >= 2) {
            return 'Pending Level 2';
        }

        return 'Pending';
    }

    private function canEmployeeEditRenderedServiceApplication($request): bool
    {
        return in_array($this->formatRenderedServiceRequestStatus($request), ['Pending', 'Returned'], true);
    }

    private function mapRenderedServiceApplicationRow($request): array
    {
        return [
            'id' => $request->id,
            'employee_id' => $request->employee_id,
            'request_date' => $request->request_date,
            'date_start' => $request->date_start,
            'date_end' => $request->date_end,
            'noted_by' => $request->noted_by,
            'noted_by_position' => $request->noted_by_position,
            'approved_by' => $request->approved_by,
            'approved_by_position' => $request->approved_by_position,
            'status' => $this->toRenderedServiceBool($request->status),
            'approved_1' => $this->toRenderedServiceBool($request->approved_1),
            'approved_2' => $this->toRenderedServiceBool($request->approved_2),
            'disapproved_1' => $this->toRenderedServiceBool($request->disapproved_1),
            'disapproved_2' => $this->toRenderedServiceBool($request->disapproved_2),
            'status_label' => $this->formatRenderedServiceRequestStatus($request),
            'has_certificate' => !empty($request->certificate_path),
            'can_edit' => $this->canEmployeeEditRenderedServiceApplication($request),
        ];
    }

    private function mapRenderedServiceApprovalRow($row): array
    {
        $statusSource = (object) [
            'employee_id' => $row->employee_id ?? null,
            'status' => $row->status ?? 0,
            'approved_1' => $row->approved_1 ?? 0,
            'approved_2' => $row->approved_2 ?? 0,
            'disapproved_1' => $row->disapproved_1 ?? 0,
            'disapproved_2' => $row->disapproved_2 ?? 0,
        ];

        $statusLabel = $this->formatRenderedServiceRequestStatus($statusSource);
        $isReturned = $statusLabel === 'Returned';
        $isFullyApproved = $statusLabel === 'Approved';

        return [
            'id' => $row->id,
            'employee_id' => $row->employee_id,
            'name' => $row->name,
            'request_date' => $row->request_date,
            'date_start' => $row->date_start,
            'date_end' => $row->date_end,
            'department' => $row->department ?? null,
            'division' => $row->division ?? null,
            'position' => $row->position ?? null,
            'approver_level_id' => $row->approver_level_id ?? null,
            'approved_1' => $this->toRenderedServiceBool($row->approved_1 ?? 0),
            'approved_2' => $this->toRenderedServiceBool($row->approved_2 ?? 0),
            'disapproved_1' => $this->toRenderedServiceBool($row->disapproved_1 ?? 0),
            'disapproved_2' => $this->toRenderedServiceBool($row->disapproved_2 ?? 0),
            'status' => $isFullyApproved,
            'is_pending' => !$isFullyApproved && !$isReturned,
            'status_label' => $statusLabel,
            'has_certificate' => !empty($row->certificate_path),
        ];
    }

    private function resolveEmployeeFromUserId($userId)
    {
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user || empty($user->employee_no)) {
            return null;
        }

        return DB::table('employees')->where('employee_no', $user->employee_no)->first();
    }

    private function resolveCurrentEmployeeId()
    {
        $user = auth()->user();
        if (!$user) {
            return 0;
        }

        $employee = $this->resolveEmployeeFromUserId($user->id);

        return $employee ? (int) $employee->id : 0;
    }

    private function hasPendingRenderedServiceApplication($employeeId)
    {
        return DB::table('service_rendered_requests')
            ->where('employee_id', $employeeId)
            ->where('status', 0)
            ->where('disapproved_1', 0)
            ->where('disapproved_2', 0)
            ->exists();
    }

    private function resetRenderedServiceApprovalColumns(): array
    {
        return [
            'approved_1' => 0,
            'approved_by_1_id' => 0,
            'approved_date_1' => null,
            'disapproved_1' => 0,
            'disapproved_by_1_id' => 0,
            'disapproved_date_1' => null,
            'approved_2' => 0,
            'approved_by_2_id' => 0,
            'approved_date_2' => null,
            'disapproved_2' => 0,
            'disapproved_by_2_id' => 0,
            'disapproved_date_2' => null,
            'status' => 0,
        ];
    }

    private function storeRenderedServiceCertificateFile($employeeId, $requestId, $pdfContent)
    {
        $directory = 'service_rendered_certificates';
        if (!Storage::disk('local')->exists($directory)) {
            Storage::disk('local')->makeDirectory($directory);
        }

        $filename = 'SRS' . $employeeId . $requestId . '.pdf';
        $relativePath = $directory . '/' . $filename;
        Storage::disk('local')->put($relativePath, $pdfContent);

        return Storage::disk('local')->path($relativePath);
    }

    private function streamRenderedServiceCertificate($requestRow, $inline = true)
    {
        $content = $this->readRenderedServiceCertificateContents($requestRow);
        if ($content === null || $content === false) {
            return null;
        }

        $filename = 'rendered_service_certificate_' . ($requestRow->id ?? 'document') . '.pdf';
        $disposition = $inline ? 'inline' : 'attachment';

        return response($content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $disposition . '; filename="' . $filename . '"')
            ->header('Content-Length', strlen($content));
    }

    private function formatRenderedServiceApprovalTimestamp($value): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('F d, Y h:i A');
        } catch (\Exception $e) {
            return '';
        }
    }

    private function resolveRenderedServiceEmployeeDisplayName($employeeId): string
    {
        if ($employeeId <= 0) {
            return '';
        }

        $appKey = env('APP_KEY', '');
        $row = DB::table('employees')
            ->select(
                DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN
                           CONCAT(first_name,' ',last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](first_name,'$appKey'))+' '+RTRIM([dbo].[ufn_DecryptString](last_name,'$appKey'))
                        END as name")
            )
            ->where('id', $employeeId)
            ->first();

        return $row->name ?? '';
    }

    private function buildRenderedServiceApprovalStamp($signed, $date, $approverName = ''): array
    {
        return [
            'signed' => $signed,
            'status' => $signed ? 'SIGNED' : '',
            'date' => $signed ? $date : '',
            'approver_name' => $signed ? $approverName : '',
        ];
    }

    private function buildRenderedServiceElectronicApprovalMeta($application): array
    {
        $level1Approved = $this->toRenderedServiceBool($application->approved_1 ?? 0);
        $level2Approved = $this->toRenderedServiceBool($application->approved_2 ?? 0);
        $fullyApproved = $this->isRenderedServiceRequestFullyApproved($application);

        $level1Stamp = $this->buildRenderedServiceApprovalStamp(false, '', '');
        $level2Stamp = $this->buildRenderedServiceApprovalStamp(false, '', '');

        // Hierarchy level 1 (first approver / division chief) → Noted By.
        if ($level1Approved) {
            $level1Stamp = $this->buildRenderedServiceApprovalStamp(
                true,
                $this->formatRenderedServiceApprovalTimestamp($application->approved_date_1 ?? null),
                $this->resolveRenderedServiceEmployeeDisplayName((int) ($application->approved_by_1_id ?? 0))
            );
        } elseif ($level2Approved && !$level1Approved) {
            // Single-approver setup where only level-2 slot is configured.
            $level1Stamp = $this->buildRenderedServiceApprovalStamp(
                true,
                $this->formatRenderedServiceApprovalTimestamp($application->approved_date_2 ?? null),
                $this->resolveRenderedServiceEmployeeDisplayName((int) ($application->approved_by_2_id ?? 0))
            );
        }

        // Hierarchy level 2 (second / final approver) → Approved By.
        if ($level1Approved && $level2Approved) {
            $level2Stamp = $this->buildRenderedServiceApprovalStamp(
                true,
                $this->formatRenderedServiceApprovalTimestamp($application->approved_date_2 ?? null),
                $this->resolveRenderedServiceEmployeeDisplayName((int) ($application->approved_by_2_id ?? 0))
            );
        }

        return [
            'level_1' => $level1Stamp,
            'level_2' => $level2Stamp,
            'fully_approved' => $fullyApproved,
            'generated_at' => Carbon::now()->format('F d, Y h:i A'),
        ];
    }
}
