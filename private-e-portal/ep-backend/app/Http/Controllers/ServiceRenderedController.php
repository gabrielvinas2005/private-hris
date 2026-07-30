<?php

namespace App\Http\Controllers;

use Auth;
use App\Http\Controllers\Concerns\HandlesRenderedServiceApproval;
use App\Traits\ApiResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class ServiceRenderedController extends Controller
{
    use ApiResponse;
    use HandlesRenderedServiceApproval;

    /** Rendered Service — matches approver_type.id */
    const RENDERED_SERVICE_TYPE_ID = 10;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Form data for Certificate of Rendered Service (COS employees).
     */
    public function formData()
    {
        try {
            $result = $this->getEmployeeFromUser();
            if ($result['error']) {
                return $result['error'];
            }

            $employee = $result['employee'];
            if (!$this->isCOSEmployee($employee->id)) {
                return $this->unauthorizedResponse('This module is only accessible to Contract of Service (COS) employees');
            }

            $employees = $this->getCosEmployeeOptions($employee->id);

            return $this->successResponse([
                'employees' => $employees,
                'defaults' => $this->getDefaultSignatories($employee->id),
            ], 'Service rendered form data loaded');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load service rendered form data: ' . $e->getMessage());
        }
    }

    /**
     * Generate Certificate of Rendered Service PDF.
     */
    public function print(Request $request)
    {
        try {
            $result = $this->getEmployeeFromUser();
            if ($result['error']) {
                return $result['error'];
            }

            $currentEmployee = $result['employee'];
            if (!$this->isCOSEmployee($currentEmployee->id)) {
                return $this->unauthorizedResponse('This module is only accessible to Contract of Service (COS) employees');
            }

            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|integer',
                'date_start' => 'required|date',
                'date_end' => 'required|date|after_or_equal:date_start',
                'noted_by' => 'required|string|max:255',
                'noted_by_position' => 'required|string|max:255',
                'approved_by' => 'required|string|max:255',
                'approved_by_position' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $employeeId = (int) $request->employee_id;
            if ($employeeId !== (int) $currentEmployee->id) {
                return $this->unauthorizedResponse('You may only generate a certificate for your own employee record');
            }

            if (!$this->isCOSEmployee($employeeId)) {
                return $this->errorResponse('Selected employee is not a COS employee.', 400);
            }

            $employee = $this->getEmployeeDetails($employeeId);
            if (!$employee) {
                return $this->errorResponse('Employee not found.', 404);
            }

            $pdfContent = $this->buildCertificatePdf($employee, $request);

            $filename = 'rendered_service_certificate_' . str_replace(' ', '_', $employee->full_name) . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate certificate of rendered service: ' . $e->getMessage());
        }
    }

    public function employeeApplications($userId)
    {
        try {
            $employee = $this->resolveEmployeeFromUserId($userId);
            if (!$employee) {
                return $this->errorResponse('Employee record not found', 404);
            }

            if (!$this->isCOSEmployee($employee->id)) {
                return $this->successResponse([
                    'allowed' => 0,
                    'is_cos_employee' => 0,
                    'employee_id' => $employee->id,
                    'applications' => [],
                ], 'Service rendered applications are only available to COS employees');
            }

            $hasApprover = $this->employeeHasRenderedServiceApprover($employee->id);

            $applications = DB::table('service_rendered_requests')
                ->where('employee_id', $employee->id)
                ->orderBy('request_date', 'desc')
                ->get()
                ->map(fn ($row) => $this->mapRenderedServiceApplicationRow($row));

            return $this->successResponse([
                'allowed' => $hasApprover ? 1 : 0,
                'is_cos_employee' => 1,
                'employee_id' => $employee->id,
                'applications' => $applications,
            ], 'Service rendered applications retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve service rendered applications: ' . $e->getMessage());
        }
    }

    public function storeApplication(Request $request)
    {
        try {
            $result = $this->getEmployeeFromUser();
            if ($result['error']) {
                return $result['error'];
            }

            $currentEmployee = $result['employee'];
            if (!$this->isCOSEmployee($currentEmployee->id)) {
                return $this->unauthorizedResponse('This module is only accessible to Contract of Service (COS) employees');
            }

            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|integer',
                'date_start' => 'required|date',
                'date_end' => 'required|date|after_or_equal:date_start',
                'noted_by' => 'required|string|max:255',
                'noted_by_position' => 'required|string|max:255',
                'approved_by' => 'required|string|max:255',
                'approved_by_position' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $employeeId = (int) $request->employee_id;
            if ($employeeId !== (int) $currentEmployee->id) {
                return $this->unauthorizedResponse('You may only submit a certificate for your own employee record');
            }

            if (!$this->employeeHasRenderedServiceApprover($employeeId)) {
                return $this->errorResponse('You cannot apply for rendered service. No approver has been configured.', 400);
            }

            if ($this->hasPendingRenderedServiceApplication($employeeId)) {
                return $this->errorResponse('You already have a pending rendered service application.', 400);
            }

            $employee = $this->getEmployeeDetails($employeeId);
            if (!$employee) {
                return $this->errorResponse('Employee not found.', 404);
            }

            $requestId = DB::table('service_rendered_requests')->insertGetId([
                'employee_id' => $employeeId,
                'request_date' => now(),
                'date_start' => $request->date_start,
                'date_end' => $request->date_end,
                'noted_by' => $request->noted_by,
                'noted_by_position' => $request->noted_by_position,
                'approved_by' => $request->approved_by,
                'approved_by_position' => $request->approved_by_position,
                'status' => 0,
                'approved_1' => 0,
                'approved_by_1_id' => 0,
                'disapproved_1' => 0,
                'approved_2' => 0,
                'approved_by_2_id' => 0,
                'disapproved_2' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $pdfContent = $this->buildCertificatePdf($employee, $request);
            $certificatePath = $this->storeRenderedServiceCertificateFile($employeeId, $requestId, $pdfContent);

            DB::table('service_rendered_requests')->where('id', $requestId)->update([
                'certificate_path' => $certificatePath,
                'updated_at' => now(),
            ]);

            return $this->successResponse(['id' => $requestId], 'Rendered service application submitted for approval.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to submit rendered service application: ' . $e->getMessage());
        }
    }

    public function updateApplication(Request $request, $requestId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_start' => 'required|date',
                'date_end' => 'required|date|after_or_equal:date_start',
                'noted_by' => 'required|string|max:255',
                'noted_by_position' => 'required|string|max:255',
                'approved_by' => 'required|string|max:255',
                'approved_by_position' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $application = DB::table('service_rendered_requests')->where('id', $requestId)->first();
            if (!$application) {
                return $this->errorResponse('Application not found', 404);
            }

            $currentEmployee = $this->getEmployeeFromUser()['employee'] ?? null;
            if (!$currentEmployee || (int) $currentEmployee->id !== (int) $application->employee_id) {
                return $this->errorResponse('You are not authorized to update this application.', 403);
            }

            if (!$this->canEmployeeEditRenderedServiceApplication($application)) {
                return $this->errorResponse('Only pending or returned applications can be edited.', 400);
            }

            $employee = $this->getEmployeeDetails((int) $application->employee_id);
            if (!$employee) {
                return $this->errorResponse('Employee not found.', 404);
            }

            $pdfContent = $this->buildCertificatePdf($employee, $request);
            $certificatePath = $this->storeRenderedServiceCertificateFile((int) $application->employee_id, (int) $requestId, $pdfContent);

            DB::table('service_rendered_requests')->where('id', $requestId)->update(array_merge([
                'date_start' => $request->date_start,
                'date_end' => $request->date_end,
                'noted_by' => $request->noted_by,
                'noted_by_position' => $request->noted_by_position,
                'approved_by' => $request->approved_by,
                'approved_by_position' => $request->approved_by_position,
                'certificate_path' => $certificatePath,
                'request_date' => now(),
                'updated_at' => now(),
            ], $this->resetRenderedServiceApprovalColumns()));

            return $this->successResponse(['id' => (int) $requestId], 'Rendered service application updated successfully.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update rendered service application: ' . $e->getMessage());
        }
    }

    public function checkApproverAccess($userId)
    {
        try {
            $employee = $this->resolveEmployeeFromUserId($userId);
            $empId = $employee ? (int) $employee->id : 0;
            $roles = $this->getRenderedServiceApproverRoles($empId);
            $isApprover = $this->hasRenderedServiceApproverRole($roles);

            return $this->successResponse([
                'employee_id' => $empId,
                'supervisor_id' => $isApprover ? 1 : 0,
                'is_approver' => $isApprover ? 1 : 0,
            ], 'Rendered service approver access retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to check rendered service approver access: ' . $e->getMessage());
        }
    }

    public function loadRequests($userId)
    {
        try {
            $appKey = env('APP_KEY', '');
            $employee = $this->resolveEmployeeFromUserId($userId);
            $empId = $employee ? (int) $employee->id : 0;
            $pending = collect();
            $approved = collect();
            $returned = collect();
            $isApprover = false;

            if ($empId > 0) {
                $roles = $this->getRenderedServiceApproverRoles($empId);
                $isApprover = $this->hasRenderedServiceApproverRole($roles);

                if ($isApprover) {
                    $pending = $this->collectRenderedServicePendingForApprover($empId, $appKey, $roles);
                    $approved = $this->collectRenderedServiceApprovedForApprover($empId, $appKey, $roles);
                    $returned = $this->queryRenderedServiceReturnedForApprover($empId, $appKey);
                }
            }

            $mapRows = fn ($rows) => $rows->map(fn ($row) => $this->mapRenderedServiceApprovalRow($row))->values();

            return $this->successResponse([
                'employee_id' => $empId,
                'supervisor_id' => $isApprover ? 1 : 0,
                'is_approver' => $isApprover ? 1 : 0,
                'records' => $mapRows($pending),
                'rendered_service_pending' => $mapRows($pending),
                'rendered_service_approved' => $mapRows($approved),
                'rendered_service_returned' => $mapRows($returned),
            ], 'Rendered service requests retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load rendered service requests: ' . $e->getMessage());
        }
    }

    public function reviewRequest($id)
    {
        try {
            $appKey = env('APP_KEY', '');
            $requestRow = $this->baseRenderedServiceApprovalQuery($appKey)
                ->where('a.id', $id)
                ->first();

            if (!$requestRow) {
                return $this->notFoundResponse('Rendered service application not found');
            }

            $statusSource = (object) [
                'employee_id' => $requestRow->employee_id,
                'status' => $requestRow->status ?? 0,
                'approved_1' => $requestRow->approved_1 ?? 0,
                'approved_2' => $requestRow->approved_2 ?? 0,
                'disapproved_1' => $requestRow->disapproved_1 ?? 0,
                'disapproved_2' => $requestRow->disapproved_2 ?? 0,
            ];

            return $this->successResponse([
                'application' => $requestRow,
                'request_status_label' => $this->formatRenderedServiceRequestStatus($statusSource),
                'has_certificate' => !empty($requestRow->certificate_path),
                'is_fully_approved' => $this->isRenderedServiceRequestFullyApproved($statusSource),
            ], 'Rendered service application details retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to review rendered service application: ' . $e->getMessage());
        }
    }

    public function approve(Request $request, $id, $typeId)
    {
        try {
            $empId = $this->resolveCurrentEmployeeId();
            $application = DB::table('service_rendered_requests')->where('id', $id)->first();

            if (!$application) {
                return $this->notFoundResponse('Rendered service application not found');
            }

            $approvers = $this->getRenderedServiceApproversForRequestEmployee($empId, (int) $application->employee_id);
            $approver_1 = $approvers['approver_1'];
            $approver_2 = $approvers['approver_2'];
            $approver_3 = $approvers['approver_3'];

            if ((int) $typeId === 2 || (int) $typeId === 3) {
                $level_1_responded = (int) ($application->approved_1 ?? 0) === 1 || (int) ($application->disapproved_1 ?? 0) === 1;
                $level_2_responded = (int) ($application->approved_2 ?? 0) === 1 || (int) ($application->disapproved_2 ?? 0) === 1;

                if ($approver_2->isNotEmpty() && !$level_1_responded) {
                    return $this->errorResponse('Level 2 approver cannot act until Level 1 has approved or returned.', 400);
                }
                if ($approver_3->isNotEmpty() && !$level_2_responded) {
                    return $this->errorResponse('Level 3 approver cannot act until Level 2 has approved or returned.', 400);
                }
            }

            if ((int) $typeId === 2) {
                if ($approver_1->isNotEmpty()) {
                    $maxLevel = $this->getRenderedServiceMaxApproverLevelForEmployee((int) $application->employee_id);
                    $data = [
                        'approved_1' => 1,
                        'approved_by_1_id' => $empId,
                        'approved_date_1' => now(),
                        'status' => $maxLevel === 1 ? 1 : 0,
                    ];
                } elseif ($approver_2->isNotEmpty()) {
                    $hasLevel3 = !empty($approver_2[0]->approver_id_3) && (int) $approver_2[0]->approver_id_3 > 0;
                    $data = [
                        'approved_2' => 1,
                        'approved_by_2_id' => $empId,
                        'approved_date_2' => now(),
                        'status' => $hasLevel3 ? 0 : 1,
                    ];
                } elseif ($approver_3->isNotEmpty()) {
                    $data = [
                        'approved_2' => 1,
                        'approved_by_2_id' => $empId,
                        'approved_date_2' => now(),
                        'status' => 1,
                    ];
                } else {
                    return $this->errorResponse('You are not authorized to approve this rendered service application.', 403);
                }

                DB::table('service_rendered_requests')->where('id', $id)->update($data);

                try {
                    $this->generateAndStoreRenderedServiceCertificatePdf((int) $id);
                } catch (\Exception $pdfError) {
                    \Log::error('Failed to regenerate rendered service certificate after approval', [
                        'request_id' => $id,
                        'error' => $pdfError->getMessage(),
                    ]);
                }

                return $this->successResponse(null, 'Rendered service application approved successfully.');
            }

            if ((int) $typeId === 3) {
                if ($approver_1->isNotEmpty()) {
                    $data = [
                        'disapproved_1' => 1,
                        'disapproved_by_1_id' => $empId,
                        'disapproved_date_1' => now(),
                        'status' => 1,
                    ];
                } elseif ($approver_2->isNotEmpty()) {
                    $data = [
                        'disapproved_2' => 1,
                        'disapproved_by_2_id' => $empId,
                        'disapproved_date_2' => now(),
                        'status' => 1,
                    ];
                } elseif ($approver_3->isNotEmpty()) {
                    $data = [
                        'disapproved_2' => 1,
                        'disapproved_by_2_id' => $empId,
                        'disapproved_date_2' => now(),
                        'status' => 1,
                    ];
                } else {
                    return $this->errorResponse('You are not authorized to return this rendered service application.', 403);
                }

                DB::table('service_rendered_requests')->where('id', $id)->update($data);

                return $this->successResponse(null, 'Rendered service application returned successfully.');
            }

            return $this->errorResponse('Invalid approval action.', 400);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process rendered service application: ' . $e->getMessage());
        }
    }

    public function downloadCertificate($requestId)
    {
        try {
            $application = DB::table('service_rendered_requests')->where('id', $requestId)->first();
            if (!$application) {
                return $this->notFoundResponse('Rendered service application not found');
            }

            $currentEmployeeId = $this->resolveCurrentEmployeeId();
            if (!$this->canAccessRenderedServiceRequest($currentEmployeeId, (int) $application->employee_id)) {
                return $this->errorResponse('You are not authorized to view this certificate.', 403);
            }

            $fileContent = $this->readRenderedServiceCertificateContents($application);
            $hasApprovalStamp = $this->toRenderedServiceBool($application->approved_1 ?? 0)
                || $this->toRenderedServiceBool($application->approved_2 ?? 0);

            if (!$this->isPdfBinary($fileContent) || $hasApprovalStamp) {
                $employee = $this->getEmployeeDetails((int) $application->employee_id);
                if (!$employee) {
                    return $this->notFoundResponse('Certificate file not found');
                }

                $fileContent = $this->buildCertificatePdfFromApplication($employee, $application);
                $certificatePath = $this->storeRenderedServiceCertificateFile(
                    (int) $application->employee_id,
                    (int) $requestId,
                    $fileContent
                );

                DB::table('service_rendered_requests')->where('id', $requestId)->update([
                    'certificate_path' => $certificatePath,
                    'updated_at' => now(),
                ]);
            }

            return $this->successResponse([
                'file_content' => base64_encode($fileContent),
                'filename' => 'rendered_service_certificate_' . $requestId . '.pdf',
                'content_type' => 'application/pdf',
            ], 'Certificate downloaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to download certificate: ' . $e->getMessage());
        }
    }

    private function isPdfBinary($content)
    {
        return is_string($content) && strlen($content) >= 4 && substr($content, 0, 4) === '%PDF';
    }

    private function generateAndStoreRenderedServiceCertificatePdf(int $requestId): ?string
    {
        $application = DB::table('service_rendered_requests')->where('id', $requestId)->first();
        if (!$application) {
            return null;
        }

        $employee = $this->getEmployeeDetails((int) $application->employee_id);
        if (!$employee) {
            return null;
        }

        $pdfContent = $this->buildCertificatePdfFromApplication($employee, $application);
        $certificatePath = $this->storeRenderedServiceCertificateFile(
            (int) $application->employee_id,
            $requestId,
            $pdfContent
        );

        DB::table('service_rendered_requests')->where('id', $requestId)->update([
            'certificate_path' => $certificatePath,
            'updated_at' => now(),
        ]);

        return $certificatePath;
    }

    private function buildCertificatePdfFromApplication($employee, $application)
    {
        $defaults = $this->getDefaultSignatories((int) $application->employee_id);

        $request = Request::create('/', 'POST', [
            'date_start' => $application->date_start,
            'date_end' => $application->date_end,
            'noted_by' => $defaults['noted_by'] ?: $application->noted_by,
            'noted_by_position' => $defaults['noted_by_position'] ?: $application->noted_by_position,
            'approved_by' => $defaults['approved_by'] ?: $application->approved_by,
            'approved_by_position' => $defaults['approved_by_position'] ?: $application->approved_by_position,
        ]);

        return $this->buildCertificatePdf($employee, $request, $application);
    }

    private function buildCertificatePdf($employee, Request $request, $application = null)
    {
        $headerImg = null;
        $footerImg = null;
        $headerPath = resource_path('img/Header.jpg');
        $footerPath = resource_path('img/Footer.jpg');
        if (is_file($headerPath)) {
            $headerImg = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($headerPath));
        }
        if (is_file($footerPath)) {
            $footerImg = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($footerPath));
        }

        $issueDate = Carbon::now();
        $coverStart = Carbon::parse($request->date_start);
        $coverEnd = Carbon::parse($request->date_end);
        $electronicApproval = $application
            ? $this->buildRenderedServiceElectronicApprovalMeta($application)
            : null;

        $pdf = Pdf::loadView('certificates.rendered_service_certificate', [
            'employee' => $employee,
            'coverStart' => $coverStart,
            'coverEnd' => $coverEnd,
            'issueDate' => $issueDate,
            'notedBy' => $request->noted_by,
            'notedByPosition' => $request->noted_by_position,
            'approvedBy' => $request->approved_by,
            'approvedByPosition' => $request->approved_by_position,
            'headerImg' => $headerImg,
            'footerImg' => $footerImg,
            'electronicApproval' => $electronicApproval,
        ])->setOptions(['defaultFont' => 'sans-serif']);
        $pdf->setPaper('A4');

        return $pdf->output();
    }

    private function getDefaultSignatories($employeeId)
    {
        $defaults = [
            'noted_by' => '',
            'noted_by_position' => '',
            'approved_by' => '',
            'approved_by_position' => '',
        ];

        $chief = $this->getDivisionChiefForEmployee($employeeId);
        if ($chief) {
            $defaults['noted_by'] = $chief->name ?? '';
            $defaults['noted_by_position'] = $chief->position_name ?? '';
        }

        $executiveChief = $this->getExecutiveOfficeDivisionChief();
        if ($executiveChief) {
            $defaults['approved_by'] = $executiveChief->name ?? '';
            $defaults['approved_by_position'] = $executiveChief->position_name ?? '';
        }

        return $defaults;
    }

    private function getExecutiveOfficeDivisionChief()
    {
        if (!Schema::hasTable('divisions')) {
            return null;
        }

        $division = DB::table('divisions')
            ->where('active', true)
            ->where(function ($q) {
                $q->where('name', 'like', '%Office of the Executive%')
                    ->orWhere('name', 'like', '%Office of the Executive Director%')
                    ->orWhere('code', 'like', '%OED%');
            })
            ->orderByRaw("CASE
                WHEN LOWER(name) LIKE '%office of the executive director%' THEN 0
                WHEN LOWER(name) LIKE '%office of the executive%' THEN 1
                ELSE 2
            END")
            ->first();

        if (!$division) {
            return null;
        }

        return $this->getDivisionChiefByDivisionId($division->id);
    }

    private function getDivisionChiefForEmployee($employeeId)
    {
        if (!Schema::hasTable('divisions') || !Schema::hasColumn('employees', 'division_id')) {
            return null;
        }

        $employee = DB::table('employees')->where('id', $employeeId)->first();
        if (!$employee || empty($employee->division_id)) {
            return null;
        }

        return $this->getDivisionChiefByDivisionId($employee->division_id);
    }

    private function getDivisionChiefByDivisionId($divisionId)
    {
        if (!Schema::hasTable('divisions') || empty($divisionId)) {
            return null;
        }

        $app_key = env('APP_KEY', '');

        $chief = DB::table('divisions as d')
            ->join('employees as chief', 'chief.id', '=', 'd.division_chief_id')
            ->leftJoin('positions as p', 'p.id', '=', 'chief.position_id')
            ->where('d.id', $divisionId)
            ->where('d.active', true)
            ->where('chief.active', true)
            ->whereNotNull('d.division_chief_id')
            ->where('d.division_chief_id', '>', 0)
            ->select(
                'chief.id',
                DB::raw("CASE WHEN ISNULL(chief.is_encrypted,0) = 0 THEN chief.first_name ELSE [dbo].[ufn_DecryptString](chief.first_name,'$app_key') END as first_name"),
                DB::raw("CASE WHEN ISNULL(chief.is_encrypted,0) = 0 THEN chief.middle_name ELSE [dbo].[ufn_DecryptString](chief.middle_name,'$app_key') END as middle_name"),
                DB::raw("CASE WHEN ISNULL(chief.is_encrypted,0) = 0 THEN chief.last_name ELSE [dbo].[ufn_DecryptString](chief.last_name,'$app_key') END as last_name"),
                'p.name as position_name',
                'd.name as division_name'
            )
            ->first();

        if (!$chief) {
            return null;
        }

        $chief->name = $this->formatEmployeeSignatoryName(
            $chief->first_name ?? '',
            $chief->middle_name ?? '',
            $chief->last_name ?? ''
        );

        return $chief;
    }

    private function getCosEmployeeOptions($employeeId)
    {
        $employee = $this->getEmployeeDetails($employeeId);
        if (!$employee) {
            return [];
        }

        $chief = $this->getDivisionChiefForEmployee($employeeId);

        return [[
            'id' => (int) $employee->id,
            'name' => $employee->full_name,
            'employee_no' => $employee->employee_no ?? null,
            'position' => $employee->position_name ?? null,
            'noted_by' => $chief->name ?? '',
            'noted_by_position' => $chief->position_name ?? '',
        ]];
    }

    private function getEmployeeDetails($employeeId)
    {
        $app_key = env('APP_KEY', '');

        $employee = DB::table('employees as a')
            ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
            ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
            ->leftJoin('employment_types as d', 'a.employment_type_id', '=', 'd.id')
            ->select(
                'a.id',
                'a.employee_no',
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE [dbo].[ufn_DecryptString](a.first_name,'$app_key') END as first_name"),
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.middle_name ELSE [dbo].[ufn_DecryptString](a.middle_name,'$app_key') END as middle_name"),
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE [dbo].[ufn_DecryptString](a.last_name,'$app_key') END as last_name"),
                'b.name as position_name',
                'c.name as department_name',
                'd.name as employment_type',
                'a.date_hired'
            )
            ->where('a.id', $employeeId)
            ->where('a.active', true)
            ->first();

        if ($employee) {
            $employee->full_name = $this->formatEmployeeSignatoryName(
                $employee->first_name ?? '',
                $employee->middle_name ?? '',
                $employee->last_name ?? ''
            );
        }

        return $employee;
    }

    /**
     * Format: First Extension M.I. Surname
     * - middle_name stores the extension name (e.g. Mari) and is shown in full
     * - when last_name contains a space (e.g. "S Torralba"), the first token is the middle initial
     */
    private function formatEmployeeSignatoryName(?string $firstName, ?string $extensionName, ?string $lastName): string
    {
        $firstName = trim(preg_replace('/\s+/u', ' ', (string) $firstName));
        $extensionName = trim(preg_replace('/\s+/u', ' ', (string) $extensionName));
        $lastName = trim(preg_replace('/\s+/u', ' ', (string) $lastName));

        if ($firstName === '' && $lastName === '') {
            return '';
        }

        $segments = [];
        if ($firstName !== '') {
            $segments[] = $firstName;
        }

        if ($extensionName !== '') {
            $lastParts = $lastName === '' ? [] : preg_split('/\s+/u', $lastName);

            if (count($lastParts) >= 2) {
                $segments[] = $extensionName;

                $middleInitial = rtrim($lastParts[0], '.');
                $surname = implode(' ', array_slice($lastParts, 1));

                if ($middleInitial !== '') {
                    $segments[] = mb_strtoupper(mb_substr($middleInitial, 0, 1, 'UTF-8'), 'UTF-8') . '.';
                }
                if ($surname !== '') {
                    $segments[] = $surname;
                }
            } else {
                $segments[] = mb_strtoupper(mb_substr($extensionName, 0, 1, 'UTF-8'), 'UTF-8') . '.';
                if ($lastName !== '') {
                    $segments[] = $lastName;
                }
            }
        } elseif ($lastName !== '') {
            $segments[] = $lastName;
        }

        return mb_strtoupper(implode(' ', $segments), 'UTF-8');
    }

    private function getEmployeeFromUser()
    {
        $user = Auth::user();

        if (!$user) {
            return [
                'error' => $this->unauthorizedResponse('User not authenticated'),
                'employee' => null,
            ];
        }

        $employee = DB::table('employees')
            ->where('employee_no', $user->employee_no)
            ->first();

        if (!$employee) {
            return [
                'error' => $this->errorResponse('Employee record not found. Please contact HR.'),
                'employee' => null,
            ];
        }

        return [
            'error' => null,
            'employee' => $employee,
        ];
    }

    private function getCosEmploymentTypeIds()
    {
        static $cos_type_ids = null;
        if ($cos_type_ids !== null) {
            return $cos_type_ids;
        }

        if (!Schema::hasTable('employment_types')) {
            $cos_type_ids = [2, 4, 6];
            return $cos_type_ids;
        }

        $cos_type_ids = DB::table('employment_types')
            ->where(function ($q) {
                $q->where('name', 'like', '%Contract of Service%')
                    ->orWhere('name', 'like', '%Consultant%')
                    ->orWhere('name', 'like', '%Service Provider%')
                    ->orWhere('name', 'like', '%COS%');
            })
            ->pluck('id')
            ->map(fn ($x) => (int) $x)
            ->unique()
            ->values()
            ->toArray();

        if (empty($cos_type_ids)) {
            $cos_type_ids = [2, 4, 6];
        }

        return $cos_type_ids;
    }

    private function isCOSEmployee($employee_id)
    {
        $employee = DB::table('employees')->where('id', $employee_id)->first();
        if (!$employee) {
            return false;
        }

        return in_array((int) ($employee->employment_type_id ?? 0), $this->getCosEmploymentTypeIds(), true);
    }
}
