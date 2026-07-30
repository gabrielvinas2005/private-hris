<?php

namespace App\Http\Controllers;

use Auth;
use App\Traits\ApiResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class NonDTRController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Employee Portal: Check if COS accomplishment report is available.
     * Visible only when a COS payroll period exists (and user is COS employee or division head).
     */
    public function checkAccess()
    {
        try {
            $result = $this->getEmployeeFromUser();
            if ($result['error']) {
                return $result['error'];
            }

            $employee = $result['employee'];
            $employee_id = $employee->id;
            $is_cos = $this->isCOSEmployee($employee_id);
            $payroll_period = $is_cos ? $this->getOpenCosPayrollPeriod($employee) : null;
            $has_cos_payroll_period = $payroll_period !== null;

            $is_division_head = Schema::hasTable('divisions')
                && DB::table('divisions')
                ->where('division_chief_id', $employee_id)
                ->where('active', true)
                ->exists();

            $can_review_reports = $is_division_head && $this->divisionHeadHasCosPayrollContext($employee_id);
            $can_submit_report = $is_cos && $has_cos_payroll_period;
            $approver_roles = $this->getAccomplishmentApproverRoles($employee_id);
            $is_accomplishment_approver = $this->hasAccomplishmentApproverRole($approver_roles);
            $has_access = $can_submit_report || $can_review_reports || $is_accomplishment_approver;

            return $this->successResponse([
                'has_access' => $has_access,
                'is_cos_employee' => $is_cos,
                'is_division_head' => $is_division_head,
                'is_accomplishment_approver' => $is_accomplishment_approver,
                'has_cos_payroll_period' => $has_cos_payroll_period || $can_review_reports,
                'can_submit_report' => $can_submit_report,
                'can_review_reports' => $can_review_reports,
                'can_approve_reports' => $is_accomplishment_approver,
                'payroll_period' => $payroll_period ? $this->formatPayrollPeriod($payroll_period) : null,
                'diagnostics' => [
                    'cos_employment_type_ids' => $this->getCosEmploymentTypeIds(),
                    'employee_employment_type_id' => (int)($employee->employment_type_id ?? 0),
                    'employee_payroll_interval_id' => (int)($employee->payroll_interval_id ?? 0),
                    'uses_payroll_period_etype' => Schema::hasTable('payroll_period_Etype'),
                ],
            ], 'COS accomplishment access check completed');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to check COS accomplishment access: ' . $e->getMessage());
        }
    }

    /**
     * Employee Portal: COS payroll periods grouped by month with 1st/2nd half slices.
     */
    public function employeePayrollPeriods(Request $request)
    {
        try {
            $result = $this->getEmployeeFromUser();
            if ($result['error']) {
                return $result['error'];
            }

            $employee = $result['employee'];
            $employee_id = $employee->id;

            if (!$this->isCOSEmployee($employee_id)) {
                return $this->unauthorizedResponse('This module is only accessible to Contract of Service (COS) employees');
            }

            $periods = $this->getCosPayrollPeriodsForEmployee($employee);
            if ($periods->isEmpty()) {
                return $this->successResponse([], 'No COS payroll periods are available yet');
            }

            $tasks = DB::table('non_dtr_task')
                ->where('employee_id', $employee_id)
                ->whereNull('deleted_at')
                ->get();

            $groups = [];
            foreach ($periods as $period) {
                $start = $period->attendance_start_date ?? $period->payroll_start_date ?? null;
                if (!$start) {
                    continue;
                }

                $group_key = date('Y-m', strtotime($start));
                $month_year = date('F Y', strtotime($start));
                $interval_name = $period->payroll_interval ?? 'Monthly';

                if (!isset($groups[$group_key])) {
                    $groups[$group_key] = [
                        'group_key' => $group_key,
                        'label' => $interval_name . ' (' . $month_year . ')',
                        'payroll_interval' => $interval_name,
                        'month_year' => $month_year,
                        'halves' => [],
                    ];
                }

                $half = $this->formatPayrollPeriodHalf($period);
                $half['accomplishment_report'] = $this->findAccomplishmentReportForPayrollPeriod($tasks, $period);
                $groups[$group_key]['halves'][] = $half;
            }

            $grouped = array_values($groups);
            foreach ($grouped as &$group) {
                usort($group['halves'], function ($a, $b) {
                    return ($a['sort_order'] ?? 99) <=> ($b['sort_order'] ?? 99);
                });
            }
            unset($group);

            usort($grouped, function ($a, $b) {
                return strcmp($b['group_key'], $a['group_key']);
            });

            return $this->successResponse($grouped, 'COS payroll periods retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve COS payroll periods: ' . $e->getMessage());
        }
    }

    /**
     * Employee Portal: List all COS accomplishment reports
     * Only for COS (Contract of Service) employees with an open payroll period
     */
    public function employeeList(Request $request)
    {
        try {
            $result = $this->getEmployeeFromUser();
            if ($result['error']) {
                return $result['error'];
            }
            $employee = $result['employee'];
            $employee_id = $employee->id;

            if (!$this->isCOSEmployee($employee_id)) {
                return $this->unauthorizedResponse('This module is only accessible to Contract of Service (COS) employees');
            }

            if (!$this->hasCosPayrollPeriod($employee)) {
                return $this->successResponse([], 'No COS payroll period is available yet');
            }

            // Get all tasks with their entries and attachments
            $tasks = DB::table('non_dtr_task as t')
                ->leftJoin('employees as e', 'e.id', '=', 't.employee_id')
                ->leftJoin('employees as creator', 'creator.id', '=', 't.created_by')
                ->leftJoin('employees as approver', 'approver.id', '=', 't.approved_by')
                ->select(
                    't.id',
                    't.employee_id',
                    DB::raw("CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) as employee_name"),
                    't.task_1',
                    't.task_2',
                    't.task_3',
                    't.period_from',
                    't.period_to',
                    't.status',
                    't.for_payroll',
                    't.remarks',
                    't.approved_at',
                    DB::raw("CONCAT(creator.first_name, ' ', COALESCE(creator.middle_name, ''), ' ', creator.last_name) as created_by_name"),
                    DB::raw("CONCAT(approver.first_name, ' ', COALESCE(approver.middle_name, ''), ' ', approver.last_name) as approved_by_name"),
                    't.created_at',
                    't.updated_at'
                )
                ->where('t.employee_id', $employee_id)
                ->whereNull('t.deleted_at')
                ->orderBy('t.created_at', 'desc')
                ->get();

            // For each task, get entries and attachments
            foreach ($tasks as $task) {
                // Get entries count and total hours
                $entries = DB::table('non_dtr_entries')
                    ->where('non_dtr_task_id', $task->id)
                    ->get();

                $task->entries_count = $entries->count();
                $task->total_hours = $entries->sum('hours_worked');
                $task->entries = $entries;

                $task->attachments = $this->getAttachmentsForTask($task->id);
                $task->attachments_count = $task->attachments->count();
            }

            return $this->successResponse($tasks, 'COS accomplishment reports retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve COS accomplishment reports: ' . $e->getMessage());
        }
    }

    /**
     * Get single accomplishment report with all details
     */
    public function employeeGet($id)
    {
        try {
            $result = $this->getEmployeeFromUser();
            if ($result['error']) {
                return $result['error'];
            }
            $employee = $result['employee'];
            $employee_id = $employee->id;

            // Check if user is a COS employee OR a division head
            $isCOS = $this->isCOSEmployee($employee_id);

            // Check if user is a division head
            $divisions = DB::table('divisions')
                ->where('division_chief_id', $employee_id)
                ->where('active', true)
                ->get();
            $isDivisionHead = !$divisions->isEmpty();

            if (!$isCOS && !$isDivisionHead) {
                return $this->unauthorizedResponse('This module is only accessible to Contract of Service (COS) employees or division heads');
            }

            $task = DB::table('non_dtr_task as t')
                ->leftJoin('employees as e', 'e.id', '=', 't.employee_id')
                ->leftJoin('employees as creator', 'creator.id', '=', 't.created_by')
                ->leftJoin('employees as approver', 'approver.id', '=', 't.approved_by')
                ->select(
                    't.*',
                    DB::raw("CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) as employee_name"),
                    DB::raw("CONCAT(creator.first_name, ' ', COALESCE(creator.middle_name, ''), ' ', creator.last_name) as created_by_name"),
                    DB::raw("CONCAT(approver.first_name, ' ', COALESCE(approver.middle_name, ''), ' ', approver.last_name) as approved_by_name")
                )
                ->where('t.id', $id)
                ->whereNull('t.deleted_at')
                ->first();

            if (!$task) {
                return $this->notFoundResponse('Accomplishment report not found');
            }

            // If division head, verify the report belongs to an employee in their division(s)
            if ($isDivisionHead && !$isCOS) {
                $divisionIds = $divisions->pluck('id')->toArray();
                $employeeInDivision = DB::table('employees')
                    ->where('id', $task->employee_id)
                    ->whereIn('division_id', $divisionIds)
                    ->exists();

                if (!$employeeInDivision) {
                    return $this->unauthorizedResponse('You can only view reports from employees in your division');
                }
            }

            // If COS employee, verify they own the report
            if ($isCOS && $task->employee_id != $employee_id) {
                return $this->unauthorizedResponse('You can only view your own reports');
            }

            // Get entries
            $task->entries = DB::table('non_dtr_entries')
                ->where('non_dtr_task_id', $task->id)
                ->orderBy('work_date', 'asc')
                ->get();

            $task->attachments = $this->getAttachmentsForTask($task->id, true);

            return $this->successResponse($task, 'Accomplishment report retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve accomplishment report: ' . $e->getMessage());
        }
    }

    /**
     * Generate printable accomplishment report PDF from saved task data.
     */
    public function employeePrint($id)
    {
        try {
            $result = $this->getEmployeeFromUser();
            if ($result['error']) {
                return $result['error'];
            }

            $viewerId = (int) $result['employee']->id;
            $viewData = $this->prepareAccomplishmentPrintViewData((int) $id);
            $task = $viewData['task'];

            if (!$this->userCanViewAccomplishmentReport($viewerId, (int) $task->employee_id)) {
                return $this->unauthorizedResponse('You do not have access to print this accomplishment report');
            }

            $pdf = Pdf::loadView('non_dtr.accomplishment_report', $viewData)
                ->setPaper('a4', 'portrait');

            return $pdf->stream("accomplishment_report_{$id}.pdf");
        } catch (\InvalidArgumentException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to print accomplishment report: ' . $e->getMessage());
        }
    }

    /**
     * Download approved accomplishment report PDF for modal preview (base64 JSON).
     */
    public function downloadApprovedAccomplishmentReport($id)
    {
        try {
            $result = $this->getEmployeeFromUser();
            if ($result['error']) {
                return $result['error'];
            }

            $viewerId = (int) $result['employee']->id;
            $task = DB::table('non_dtr_task')->where('id', $id)->whereNull('deleted_at')->first();

            if (!$task) {
                return $this->notFoundResponse('Accomplishment report not found');
            }

            if (!$this->userCanViewAccomplishmentReport($viewerId, (int) $task->employee_id)) {
                return $this->unauthorizedResponse('You do not have access to this accomplishment report');
            }

            if (!$this->isAccomplishmentRequestFullyApproved($task)) {
                return $this->notFoundResponse('Approved accomplishment report not found');
            }

            $path = $this->resolveApprovedAccomplishmentStoragePath((int) $id, $task->approved_report_path ?? null);

            try {
                $path = $this->generateAndStoreApprovedAccomplishmentPdf((int) $id) ?: $path;
            } catch (\Exception $pdfError) {
                Log::error('Failed to generate approved accomplishment report PDF on download', [
                    'task_id' => $id,
                    'employee_id' => (int) ($task->employee_id ?? 0),
                    'error' => $pdfError->getMessage(),
                ]);
            }

            if (empty($path) || !is_file($path)) {
                $path = $this->resolveApprovedAccomplishmentStoragePath((int) $id, $task->approved_report_path ?? null);
            }

            if (empty($path) || !is_file($path)) {
                return $this->notFoundResponse('Approved accomplishment report file is not available for this request');
            }

            $fileContent = file_get_contents($path);

            return $this->successResponse([
                'file_content' => base64_encode($fileContent),
                'filename' => 'approved_accomplishment_report_' . $id . '.pdf',
                'content_type' => 'application/pdf',
            ], 'Approved accomplishment report downloaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to download approved accomplishment report: ' . $e->getMessage());
        }
    }

    /**
     * Create new accomplishment report
     */
    public function employeeStore(Request $request)
    {
        try {
            $result = $this->getEmployeeFromUser();
            if ($result['error']) {
                return $result['error'];
            }
            $employee = $result['employee'];
            $employee_id = $employee->id;

            if (!$this->isCOSEmployee($employee_id)) {
                return $this->unauthorizedResponse('This module is only accessible to Contract of Service (COS) employees');
            }

            if (!$this->employeeHasAccomplishmentApprover($employee_id)) {
                return $this->badRequestResponse('You cannot submit an accomplishment report. No approver has been configured for Accomplishment Report applications.');
            }

            $payroll_period_id = (int)$request->input('payroll_period_id', 0);
            if ($payroll_period_id > 0) {
                $payroll_period = $this->getPayrollPeriodForEmployee($employee, $payroll_period_id);
                if (!$payroll_period) {
                    return $this->badRequestResponse('Invalid payroll period selected.');
                }

                if (Schema::hasColumn('non_dtr_task', 'payroll_period_id')) {
                    $exists = DB::table('non_dtr_task')
                        ->where('employee_id', $employee_id)
                        ->where('payroll_period_id', $payroll_period_id)
                        ->whereNull('deleted_at')
                        ->exists();
                    if ($exists) {
                        return $this->badRequestResponse('An accomplishment report already exists for this payroll period half.');
                    }
                }
            } else {
                $payroll_period = $this->getOpenCosPayrollPeriod($employee);
                if (!$payroll_period) {
                    return $this->badRequestResponse('COS accomplishment reports are not available until a payroll period is created.');
                }
            }

            $validator = $this->makeAccomplishmentDraftValidator($request);

            if ($validator->fails()) {
                return $this->badRequestResponse('Validation failed', $validator->errors());
            }

            if (!$this->periodWithinPayrollPeriod($request->period_from, $request->period_to, $payroll_period)) {
                return $this->badRequestResponse('Report period must fall within the selected COS payroll period.');
            }

            $schedule_header_id = $this->getCosPayrollScheduleHeaderId();
            DB::beginTransaction();

            $task_data = [
                'employee_id' => $employee_id,
                'task_1' => $request->input('task_1'),
                'task_2' => $request->input('task_2'),
                'task_3' => $request->input('task_3'),
                'period_from' => $request->period_from,
                'period_to' => $request->period_to,
                'status' => 'draft',
                'created_by' => $employee_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('non_dtr_task', 'payroll_period_id') && $payroll_period_id > 0) {
                $task_data['payroll_period_id'] = $payroll_period_id;
            }

            $taskId = DB::table('non_dtr_task')->insertGetId($task_data);

            $this->syncAccomplishmentEntries($taskId, $request->input('entries', []), $schedule_header_id, true);

            DB::commit();

            return $this->successResponse(['id' => $taskId, 'status' => 'draft'], 'Accomplishment report draft saved successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to create accomplishment report: ' . $e->getMessage());
        }
    }

    /**
     * Update accomplishment report
     */
    public function employeeUpdate(Request $request, $id)
    {
        try {
            $result = $this->getEmployeeFromUser();
            if ($result['error']) {
                return $result['error'];
            }
            $employee = $result['employee'];
            $employee_id = $employee->id;

            $task = DB::table('non_dtr_task')
                ->where('id', $id)
                ->where('employee_id', $employee_id)
                ->whereNull('deleted_at')
                ->first();

            if (!$task) {
                return $this->notFoundResponse('Accomplishment report not found or access denied');
            }

            if ($task->status === 'approved') {
                return $this->badRequestResponse('Cannot update an approved report');
            }

            $isDraftSave = $this->isAccomplishmentDraftSave($request);
            if ($isDraftSave && !in_array($task->status, ['draft', 'rejected'], true)) {
                return $this->badRequestResponse('Only draft or returned reports can be saved as a draft');
            }

            $payroll_period = null;
            if (Schema::hasColumn('non_dtr_task', 'payroll_period_id') && !empty($task->payroll_period_id)) {
                $payroll_period = $this->getPayrollPeriodForEmployee($employee, (int)$task->payroll_period_id);
            }
            if (!$payroll_period) {
                $payroll_period = $this->getOpenCosPayrollPeriod($employee);
            }
            if (!$payroll_period) {
                return $this->badRequestResponse('COS accomplishment reports are not available until a payroll period is created.');
            }

            $validator = $isDraftSave
                ? $this->makeAccomplishmentDraftValidator($request)
                : $this->makeAccomplishmentSubmitValidator($request);

            if ($validator->fails()) {
                return $this->badRequestResponse('Validation failed', $validator->errors());
            }

            if (!$this->periodWithinPayrollPeriod($request->period_from, $request->period_to, $payroll_period)) {
                return $this->badRequestResponse('Report period must fall within the selected COS payroll period.');
            }

            $schedule_header_id = $this->getCosPayrollScheduleHeaderId();
            DB::beginTransaction();

            $updateData = [
                'task_1' => $request->input('task_1'),
                'task_2' => $request->input('task_2'),
                'task_3' => $request->input('task_3'),
                    'period_from' => $request->period_from,
                    'period_to' => $request->period_to,
                    'updated_at' => now(),
            ];

            if ($isDraftSave) {
                $updateData['status'] = 'draft';
            }

            DB::table('non_dtr_task')
                ->where('id', $id)
                ->update($updateData);

            $this->syncAccomplishmentEntries(
                $id,
                $request->input('entries', []),
                $schedule_header_id,
                $isDraftSave
            );

            DB::commit();

            return $this->successResponse(
                ['id' => $id, 'status' => $isDraftSave ? 'draft' : $task->status],
                $isDraftSave ? 'Accomplishment report draft saved successfully' : 'Accomplishment report updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to update accomplishment report: ' . $e->getMessage());
        }
    }

    /**
     * Submit a draft or returned accomplishment report for approval.
     */
    public function employeeSubmit(Request $request, $id)
    {
        try {
            $result = $this->getEmployeeFromUser();
            if ($result['error']) {
                return $result['error'];
            }
            $employee = $result['employee'];
            $employee_id = $employee->id;

            if (!$this->employeeHasAccomplishmentApprover($employee_id)) {
                return $this->badRequestResponse('You cannot submit an accomplishment report. No approver has been configured for Accomplishment Report applications.');
            }

            $task = DB::table('non_dtr_task')
                ->where('id', $id)
                ->where('employee_id', $employee_id)
                ->whereNull('deleted_at')
                ->first();

            if (!$task) {
                return $this->notFoundResponse('Accomplishment report not found or access denied');
            }

            if (!in_array($task->status, ['draft', 'rejected'], true)) {
                return $this->badRequestResponse('Only draft or returned reports can be submitted');
            }

            $payroll_period = null;
            if (Schema::hasColumn('non_dtr_task', 'payroll_period_id') && !empty($task->payroll_period_id)) {
                $payroll_period = $this->getPayrollPeriodForEmployee($employee, (int)$task->payroll_period_id);
            }
            if (!$payroll_period) {
                $payroll_period = $this->getOpenCosPayrollPeriod($employee);
            }
            if (!$payroll_period) {
                return $this->badRequestResponse('COS accomplishment reports are not available until a payroll period is created.');
            }

            $validator = $this->makeAccomplishmentSubmitValidator($request);
            if ($validator->fails()) {
                return $this->badRequestResponse('Validation failed', $validator->errors());
            }

            if (!$this->periodWithinPayrollPeriod($request->period_from, $request->period_to, $payroll_period)) {
                return $this->badRequestResponse('Report period must fall within the selected COS payroll period.');
            }

            $schedule_header_id = $this->getCosPayrollScheduleHeaderId();
            DB::beginTransaction();

            $submitData = [
                'task_1' => $request->input('task_1'),
                'task_2' => $request->input('task_2'),
                'task_3' => $request->input('task_3'),
                'period_from' => $request->period_from,
                'period_to' => $request->period_to,
                'status' => 'pending',
                    'updated_at' => now(),
            ];

            if (Schema::hasColumn('non_dtr_task', 'approved_1')) {
                $submitData['approved_1'] = 0;
                $submitData['approved_2'] = 0;
                $submitData['disapproved_1'] = 0;
                $submitData['disapproved_2'] = 0;
            }

            DB::table('non_dtr_task')
                ->where('id', $id)
                ->update($submitData);

            $this->syncAccomplishmentEntries($id, $request->input('entries', []), $schedule_header_id, false);

            DB::commit();

            return $this->successResponse(
                ['id' => $id, 'status' => 'pending'],
                'Accomplishment report submitted successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to submit accomplishment report: ' . $e->getMessage());
        }
    }

    /**
     * Delete accomplishment report
     */
    public function employeeDelete($id)
    {
        try {
            $result = $this->getEmployeeFromUser();
            if ($result['error']) {
                return $result['error'];
            }
            $employee = $result['employee'];
            $employee_id = $employee->id;

            $task = DB::table('non_dtr_task')
                ->where('id', $id)
                ->where('employee_id', $employee_id)
                ->whereNull('deleted_at')
                ->first();

            if (!$task) {
                return $this->notFoundResponse('Accomplishment report not found or access denied');
            }

            if ($task->status === 'approved') {
                return $this->badRequestResponse('Cannot delete an approved report');
            }

            // Soft delete
            DB::table('non_dtr_task')
                ->where('id', $id)
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now()
                ]);

            return $this->successResponse(null, 'Accomplishment report deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete accomplishment report: ' . $e->getMessage());
        }
    }

    /**
     * Upload attachment for accomplishment report
     */
    public function employeeUploadAttachment(Request $request)
    {
        try {
            $result = $this->getEmployeeFromUser();
            if ($result['error']) {
                return $result['error'];
            }
            $employee = $result['employee'];
            $employee_id = $employee->id;

            $validator = Validator::make($request->all(), [
                'non_dtr_task_id' => 'required|exists:non_dtr_task,id',
                'file' => 'required|file|max:10240', // Max 10MB
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->badRequestResponse('Validation failed', $validator->errors());
            }

            $task = DB::table('non_dtr_task')
                ->where('id', $request->non_dtr_task_id)
                ->where('employee_id', $employee_id)
                ->whereNull('deleted_at')
                ->first();

            if (!$task) {
                return $this->notFoundResponse('Accomplishment report not found or access denied');
            }

            if ($task->status !== 'approved') {
                return $this->badRequestResponse('Attachments can only be uploaded after the report is approved.');
            }

            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $fileContent = file_get_contents($file->getRealPath());

            $attachmentId = $this->insertAttachmentToDb([
                'non_dtr_task_id' => $request->non_dtr_task_id,
                'non_dtr_entry_id' => $request->non_dtr_entry_id ?? null,
                'file_name' => $fileName,
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'description' => $request->description,
                'uploaded_by' => $employee_id,
            ], $fileContent);

            return $this->successResponse([
                'id' => $attachmentId,
                'file_name' => $fileName,
            ], 'Attachment uploaded successfully');
        } catch (\Exception $e) {
            \Log::error('NonDTR attachment upload failed', [
                'non_dtr_task_id' => $request->non_dtr_task_id ?? null,
                'file_name' => $request->file('file') ? $request->file('file')->getClientOriginalName() : null,
                'error' => $e->getMessage(),
            ]);

            return $this->serverErrorResponse('Failed to upload attachment. Please try again or use a different file.');
        }
    }

    /**
     * Delete attachment
     */
    public function employeeDeleteAttachment($id)
    {
        try {
            $result = $this->getEmployeeFromUser();
            if ($result['error']) {
                return $result['error'];
            }
            $employee = $result['employee'];
            $employee_id = $employee->id;

            $resolved = $this->resolveAttachmentById($id);

            if (!$resolved) {
                return $this->notFoundResponse('Attachment not found or access denied');
            }

            $task = DB::table('non_dtr_task')
                ->where('id', $resolved->attachment->non_dtr_task_id)
                ->where('employee_id', $employee_id)
                ->whereNull('deleted_at')
                ->first();

            if (!$task) {
                return $this->notFoundResponse('Attachment not found or access denied');
            }

            if ($task->status !== 'approved') {
                return $this->badRequestResponse('Attachments can only be removed after the report is approved.');
            }

            if ($resolved->source === 'db') {
                $this->attachmentsDb()->table('non_dtr_attachments')->where('id', $id)->delete();
            } else {
                if (!empty($resolved->attachment->file_path) && Storage::disk('public')->exists($resolved->attachment->file_path)) {
                    Storage::disk('public')->delete($resolved->attachment->file_path);
                }

            DB::table('non_dtr_attachments')->where('id', $id)->delete();
            }

            return $this->successResponse(null, 'Attachment deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete attachment: ' . $e->getMessage());
        }
    }

    /**
     * View attachment file (for preview in browser)
     * Note: Accepts bearer token via Authorization header OR token query parameter
     */
    public function viewAttachment(Request $request, $id)
    {
        try {
            $user = null;

            // Try to get token from query parameter (for iframe src)
            if ($request->has('token')) {
                $token = $request->get('token');
                $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
                if ($accessToken) {
                    $user = $accessToken->tokenable;
                }
            }

            // If no token in query, try Authorization header
            if (!$user) {
                $token = $request->bearerToken();
                if ($token) {
                    $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
                    if ($accessToken) {
                        $user = $accessToken->tokenable;
                    }
                }
            }

            if (!$user) {
                return response('<html><body style="display:flex;align-items:center;justify-content:center;height:100vh;font-family:sans-serif;background:#f5f5f5;"><div style="text-align:center;padding:2rem;background:white;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);"><h2 style="color:#e74c3c;margin-bottom:1rem;">⚠ Authentication Required</h2><p style="color:#666;">Please log in to view this file.</p><p style="margin-top:1rem;"><a href="/" style="color:#3498db;text-decoration:none;">Go to Login</a></p></div></body></html>', 401)
                    ->header('Content-Type', 'text/html');
            }

            // Get employee from user
            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$employee) {
                return response('<html><body style="display:flex;align-items:center;justify-content:center;height:100vh;font-family:sans-serif;background:#f5f5f5;"><div style="text-align:center;padding:2rem;background:white;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);"><h2 style="color:#e74c3c;">Employee Record Not Found</h2><p>Your account is not linked to an employee record.</p></div></body></html>', 404)
                    ->header('Content-Type', 'text/html');
            }

            $employee_id = $employee->id;

            $resolved = $this->resolveAttachmentById($id);

            if (!$resolved) {
                return response()->json(['success' => false, 'message' => 'Attachment not found'], 404);
            }

            $attachment = $resolved->attachment;

            $task = DB::table('non_dtr_task')
                ->where('id', $attachment->non_dtr_task_id)
                ->whereNull('deleted_at')
                        ->first();

            if (!$task) {
                return response()->json(['success' => false, 'message' => 'Attachment not found'], 404);
            }

            if (!$this->userCanAccessAttachment($employee_id, $task->employee_id)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            $fileContent = $this->getAttachmentFileContent($attachment, $resolved->source);
            if ($fileContent === null) {
                return response()->json(['success' => false, 'message' => 'File not found'], 404);
            }

            return $this->attachmentFileResponse($attachment, $fileContent, 'inline');
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to view attachment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download attachment file
     */
    public function downloadAttachment($id)
    {
        try {
            $result = $this->getEmployeeFromUser();
            if ($result['error']) {
                return $result['error'];
            }
            $employee = $result['employee'];
            $employee_id = $employee->id;

            $resolved = $this->resolveAttachmentById($id);

            if (!$resolved) {
                return response()->json(['success' => false, 'message' => 'Attachment not found'], 404);
            }

            $attachment = $resolved->attachment;

            $task = DB::table('non_dtr_task')
                ->where('id', $attachment->non_dtr_task_id)
                ->whereNull('deleted_at')
                        ->first();

            if (!$task) {
                return response()->json(['success' => false, 'message' => 'Attachment not found'], 404);
            }

            if (!$this->userCanAccessAttachment($employee_id, $task->employee_id)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            $fileContent = $this->getAttachmentFileContent($attachment, $resolved->source);
            if ($fileContent === null) {
                return response()->json(['success' => false, 'message' => 'File not found'], 404);
            }

            return $this->attachmentFileResponse($attachment, $fileContent, 'attachment');
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to download attachment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get form data (employee info, etc.)
     */
    public function employeeFormData(Request $request)
    {
        try {
            $result = $this->getEmployeeFromUser();
            if ($result['error']) {
                return $result['error'];
            }
            $employee = $result['employee'];
            $employee_id = $employee->id;

            if (!$this->isCOSEmployee($employee_id)) {
                return $this->unauthorizedResponse('This module is only accessible to Contract of Service (COS) employees');
            }

            $payroll_period_id = (int)$request->query('payroll_period_id', 0);
            if ($payroll_period_id > 0) {
                $payroll_period = $this->getPayrollPeriodForEmployee($employee, $payroll_period_id);
                if (!$payroll_period) {
                    return $this->badRequestResponse('Invalid payroll period selected.');
                }
            } else {
                $payroll_period = $this->getOpenCosPayrollPeriod($employee);
                if (!$payroll_period) {
                    return $this->badRequestResponse('COS accomplishment reports are not available until a payroll period is created.');
                }
            }

            $employeeDetail = DB::table('employees as e')
                ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->select(
                    'e.id',
                    'e.first_name',
                    'e.middle_name',
                    'e.last_name',
                    DB::raw("CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) as full_name"),
                    'd.name as department_name',
                    'p.name as position_name'
                )
                ->where('e.id', $employee_id)
                ->first();

            return $this->successResponse([
                'employee' => $employeeDetail,
                'payroll_period' => $this->formatPayrollPeriodHalf($payroll_period),
                'payroll_schedule_header_id' => $this->getCosPayrollScheduleHeaderId(),
            ], 'Form data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve form data: ' . $e->getMessage());
        }
    }

    /**
     * Debug endpoint to check user-employee linking
     */
    public function debugUserEmployee()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('User not authenticated');
            }

            // Get user info
            $userInfo = [
                'user_id' => $user->id,
                'user_email' => $user->email ?? 'N/A',
                'user_employee_no' => $user->employee_no ?? 'NULL',
                'employee_no_type' => gettype($user->employee_no),
            ];

            // Try to find employee
            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            $employeeInfo = null;
            if ($employee) {
                $employeeInfo = [
                    'found' => true,
                    'employee_id' => $employee->id,
                    'employee_no' => $employee->employee_no,
                    'name' => ($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''),
                    'active' => $employee->active ?? 'N/A',
                    'is_employee' => $employee->is_employee ?? 'N/A'
                ];
            } else {
                $employeeInfo = [
                    'found' => false,
                    'message' => 'No employee found with this employee_no'
                ];

                // Try to find similar employee_no
                $similarEmployees = DB::table('employees')
                    ->select('id', 'employee_no', 'first_name', 'last_name')
                    ->whereRaw("CAST(employee_no AS NVARCHAR) LIKE ?", ['%' . $user->employee_no . '%'])
                    ->limit(5)
                    ->get();

                $employeeInfo['similar_employees'] = $similarEmployees;
            }

            // Check COS status if employee found
            $cosStatus = null;
            if ($employee) {
                $cosStatus = [
                    'is_cos_employee' => $employee->employment_type_id == 2,
                    'employment_type_id' => $employee->employment_type_id ?? null,
                    'employment_type_name' => DB::table('employment_types')
                        ->where('id', $employee->employment_type_id)
                        ->value('name')
                ];
            }

            // Get division information if employee found
            $divisionInfo = null;
            if ($employee) {
                $division = DB::table('divisions')
                    ->where('id', $employee->division_id)
                    ->first();

                if ($division) {
                    $divisionInfo = [
                        'division_id' => $division->id,
                        'division_code' => $division->division_code,
                        'division_name' => $division->division_name,
                        'division_chief_id' => $division->division_chief_id,
                        'is_division_chief' => $division->division_chief_id == $employee->id
                    ];
                }
            }

            return $this->successResponse([
                'user_info' => $userInfo,
                'employee_info' => $employeeInfo,
                'cos_status' => $cosStatus,
                'division_info' => $divisionInfo
            ], 'Debug information retrieved');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Debug failed: ' . $e->getMessage());
        }
    }

    /**
     * Division Head: List COS accomplishment reports of employees under their division
     */
    public function divisionHeadList(Request $request)
    {
        try {
            $result = $this->getEmployeeFromUser();
            if ($result['error']) {
                return $result['error'];
            }
            $employee = $result['employee'];
            $employee_id = $employee->id;

            // Check if user is a division head (get ALL divisions they head)
            $divisions = DB::table('divisions')
                ->where('division_chief_id', $employee_id)
                ->where('active', true)
                ->get();

            if ($divisions->isEmpty()) {
                \Log::info('NonDTR Division Head Check Failed', [
                    'employee_id' => $employee_id,
                    'all_divisions' => DB::table('divisions')->where('active', true)->get()
                ]);
                return $this->unauthorizedResponse('This module is only accessible to division heads. Your employee_id: ' . $employee_id);
            }

            if (!$this->divisionHeadHasCosPayrollContext($employee_id)) {
                return $this->unauthorizedResponse('COS accomplishment review is not available until a COS payroll period is created.');
            }

            $divisionIds = $divisions->pluck('id')->toArray();

            \Log::info('NonDTR Division Head Access Granted', [
                'employee_id' => $employee_id,
                'division_ids' => $divisionIds,
                'division_names' => $divisions->pluck('division_name')->toArray()
            ]);

            // Get all COS employees under ALL divisions managed by this chief
            // COS employees have employment_type_id = 2
            $divisionEmployees = DB::table('employees')
                ->whereIn('division_id', $divisionIds)
                ->where('employment_type_id', 2) // Only Contract of Service employees
                ->where('active', true)
                ->pluck('id');

            \Log::info('NonDTR COS Employees Found', [
                'division_ids' => $divisionIds,
                'cos_employee_ids' => $divisionEmployees->toArray(),
                'count' => $divisionEmployees->count()
            ]);

            // Get tasks for these employees
            $tasks = DB::table('non_dtr_task as t')
                ->leftJoin('employees as e', 'e.id', '=', 't.employee_id')
                ->leftJoin('employees as creator', 'creator.id', '=', 't.created_by')
                ->leftJoin('employees as approver', 'approver.id', '=', 't.approved_by')
                ->select(
                    't.id',
                    't.employee_id',
                    DB::raw("CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) as employee_name"),
                    't.task_1',
                    't.task_2',
                    't.task_3',
                    't.period_from',
                    't.period_to',
                    't.status',
                    't.remarks',
                    't.approved_at',
                    DB::raw("CONCAT(creator.first_name, ' ', COALESCE(creator.middle_name, ''), ' ', creator.last_name) as created_by_name"),
                    DB::raw("CONCAT(approver.first_name, ' ', COALESCE(approver.middle_name, ''), ' ', approver.last_name) as approved_by_name"),
                    't.created_at',
                    't.updated_at'
                )
                ->whereIn('t.employee_id', $divisionEmployees)
                ->whereNull('t.deleted_at')
                ->orderBy('t.created_at', 'desc')
                ->get();

            // For each task, get entries and attachments
            foreach ($tasks as $task) {
                $entries = DB::table('non_dtr_entries')
                    ->where('non_dtr_task_id', $task->id)
                    ->get();

                $task->entries_count = $entries->count();
                $task->total_hours = $entries->sum('hours_worked');
                $task->entries = $entries;

                $task->attachments = $this->getAttachmentsForTask($task->id);
                $task->attachments_count = $task->attachments->count();
            }

            \Log::info('NonDTR Tasks Retrieved', [
                'division_ids' => $divisionIds,
                'tasks_count' => $tasks->count(),
                'task_ids' => $tasks->pluck('id')->toArray()
            ]);

            return $this->successResponse($tasks, 'COS accomplishment reports retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve COS accomplishment reports: ' . $e->getMessage());
        }
    }

    /**
     * Division Head: Approve/Update payroll status of accomplishment entries
     */
    public function divisionHeadApprove(Request $request, $taskId)
    {
        try {
            $result = $this->getEmployeeFromUser();
            if ($result['error']) {
                return $result['error'];
            }
            $employee = $result['employee'];
            $employee_id = $employee->id;

            // Check if user is a division head (get ALL divisions they head)
            $divisions = DB::table('divisions')
                ->where('division_chief_id', $employee_id)
                ->where('active', true)
                ->get();

            if ($divisions->isEmpty()) {
                return $this->unauthorizedResponse('Only division heads can approve accomplishment reports');
            }

            $divisionIds = $divisions->pluck('id')->toArray();

            // Get the task and verify it belongs to an employee in their divisions
            $task = DB::table('non_dtr_task as t')
                ->join('employees as e', 'e.id', '=', 't.employee_id')
                ->where('t.id', $taskId)
                ->whereIn('e.division_id', $divisionIds)
                ->whereNull('t.deleted_at')
                ->select('t.*')
                ->first();

            if (!$task) {
                return $this->notFoundResponse('Accomplishment report not found or access denied');
            }

            $validator = Validator::make($request->all(), [
                'for_payroll' => 'required|boolean',
                'status' => 'required|in:pending,approved,rejected',
                'remarks' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->badRequestResponse('Validation failed', $validator->errors());
            }

            DB::beginTransaction();

            // Update task status and payroll flag
            $updateData = [
                'status' => $request->status,
                'for_payroll' => $request->for_payroll,
                'remarks' => $request->remarks,
                'updated_at' => now()
            ];

            if ($request->status === 'approved') {
                $updateData['approved_by'] = $employee_id;
                $updateData['approved_at'] = now();
            }

            DB::table('non_dtr_task')
                ->where('id', $taskId)
                ->update($updateData);

            DB::commit();

            return $this->successResponse(null, 'Accomplishment report updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to approve accomplishment report: ' . $e->getMessage());
        }
    }

    /**
     * List accomplishment applications for a COS employee (portal user id).
     */
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
                ], 'Accomplishment applications are only available to COS employees');
            }

            $hasApprover = $this->employeeHasAccomplishmentApprover($employee->id);

            $applications = DB::table('non_dtr_task as t')
                ->leftJoin('payroll_periods as pp', 'pp.id', '=', 't.payroll_period_id')
                ->leftJoin('payroll_intervals as pi', 'pi.id', '=', 'pp.payroll_interval_id')
                ->where('t.employee_id', $employee->id)
                ->whereNull('t.deleted_at')
                ->orderBy('t.created_at', 'desc')
                ->select(
                    't.*',
                    DB::raw("CASE WHEN t.payroll_period_id > 0 THEN
                        CONCAT(pi.name,' (', DATENAME(MONTH, pp.release_date),' ', DATEPART(YEAR, pp.release_date),')')
                        ELSE NULL END as payroll_period")
                )
                ->get()
                ->map(fn ($row) => $this->mapAccomplishmentApplicationRow($row));

            return $this->successResponse([
                'allowed' => $hasApprover ? 1 : 0,
                'is_cos_employee' => 1,
                'employee_id' => $employee->id,
                'applications' => $applications,
            ], 'Accomplishment applications retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve accomplishment applications: ' . $e->getMessage());
        }
    }

    public function checkAccomplishmentApproverAccess($userId)
    {
        try {
            $employee = $this->resolveEmployeeFromUserId($userId);
            $empId = $employee ? (int) $employee->id : 0;
            $roles = $this->getAccomplishmentApproverRoles($empId);
            $isApprover = $this->hasAccomplishmentApproverRole($roles);

            return $this->successResponse([
                'employee_id' => $empId,
                'supervisor_id' => $isApprover ? 1 : 0,
                'is_approver' => $isApprover ? 1 : 0,
            ], 'Accomplishment approver access retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to check accomplishment approver access: ' . $e->getMessage());
        }
    }

    public function loadAccomplishmentRequests($userId)
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
                $roles = $this->getAccomplishmentApproverRoles($empId);
                $isApprover = $this->hasAccomplishmentApproverRole($roles);

                if ($isApprover) {
                    $pending = $this->collectAccomplishmentPendingForApprover($empId, $appKey, $roles);
                    $approved = $this->collectAccomplishmentApprovedForApprover($empId, $appKey, $roles);
                    $returned = $this->queryAccomplishmentReturnedForApprover($empId, $appKey);
                }
            }

            $mapRows = fn ($rows) => $rows->map(fn ($row) => $this->mapAccomplishmentApprovalRow($row))->values();

            return $this->successResponse([
                'employee_id' => $empId,
                'supervisor_id' => $isApprover ? 1 : 0,
                'is_approver' => $isApprover ? 1 : 0,
                'accomplishment_pending' => $mapRows($pending),
                'accomplishment_approved' => $mapRows($approved),
                'accomplishment_returned' => $mapRows($returned),
            ], 'Accomplishment requests retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load accomplishment requests: ' . $e->getMessage());
        }
    }

    public function reviewAccomplishmentRequest($id)
    {
        try {
            $task = $this->baseAccomplishmentApprovalQuery(env('APP_KEY', ''))
                ->where('t.id', $id)
                ->first();

            if (!$task) {
                return $this->notFoundResponse('Accomplishment report not found');
            }

            $entries = DB::table('non_dtr_entries')
                ->where('non_dtr_task_id', $id)
                ->orderBy('work_date', 'asc')
                ->get();

            $attachments = $this->getAttachmentsForTask($id);

            $statusSource = (object) [
                'employee_id' => $task->employee_id,
                'status' => $task->status ?? 'pending',
                'approved_1' => $task->approved_1 ?? 0,
                'approved_2' => $task->approved_2 ?? 0,
                'disapproved_1' => $task->disapproved_1 ?? 0,
                'disapproved_2' => $task->disapproved_2 ?? 0,
            ];

            return $this->successResponse([
                'task' => $task,
                'entries' => $entries,
                'attachments' => $attachments,
                'request_status_label' => $this->formatAccomplishmentRequestStatus($statusSource),
                'entries_count' => $entries->count(),
                'total_hours' => $entries->sum('hours_worked'),
                'has_approved_report' => $this->isAccomplishmentRequestFullyApproved($statusSource),
            ], 'Accomplishment report details retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to review accomplishment report: ' . $e->getMessage());
        }
    }

    public function approveAccomplishmentRequest(Request $request, $id, $typeId)
    {
        try {
            $empId = $this->resolveCurrentEmployeeId();
            $task = DB::table('non_dtr_task')->where('id', $id)->whereNull('deleted_at')->first();

            if (!$task) {
                return $this->notFoundResponse('Accomplishment report not found');
            }

            $approvers = $this->getAccomplishmentApproversForRequestEmployee($empId, (int) $task->employee_id);
            $approver_1 = $approvers['approver_1'];
            $approver_2 = $approvers['approver_2'];
            $approver_3 = $approvers['approver_3'];

            if ((int) $typeId === 2 || (int) $typeId === 3) {
                $level_1_responded = (int) ($task->approved_1 ?? 0) === 1 || (int) ($task->disapproved_1 ?? 0) === 1;
                $level_2_responded = (int) ($task->approved_2 ?? 0) === 1 || (int) ($task->disapproved_2 ?? 0) === 1;

                if ($approver_2->isNotEmpty() && !$level_1_responded) {
                    return $this->errorResponse('Level 2 approver cannot act until Level 1 has approved or returned.', 400);
                }
                if ($approver_3->isNotEmpty() && !$level_2_responded) {
                    return $this->errorResponse('Level 3 approver cannot act until Level 2 has approved or returned.', 400);
                }
            }

            if ((int) $typeId === 2) {
                if ($approver_1->isNotEmpty()) {
                    $maxLevel = $this->getAccomplishmentMaxApproverLevelForEmployee((int) $task->employee_id);
                    $data = [
                        'approved_1' => 1,
                        'approved_by_1_id' => $approver_1[0]->id,
                        'approved_date_1' => now(),
                        'status' => $maxLevel === 1 ? 'approved' : 'pending',
                    ];
                    if ($maxLevel === 1) {
                        $data['approved_by'] = $empId;
                        $data['approved_at'] = now();
                    }
                } elseif ($approver_2->isNotEmpty()) {
                    $hasLevel3 = !empty($approver_2[0]->approver_id_3) && (int) $approver_2[0]->approver_id_3 > 0;
                    $data = [
                        'approved_2' => 1,
                        'approved_by_2_id' => $approver_2[0]->id,
                        'approved_date_2' => now(),
                        'status' => $hasLevel3 ? 'pending' : 'approved',
                    ];
                    if (!$hasLevel3) {
                        $data['approved_by'] = $empId;
                        $data['approved_at'] = now();
                    }
                } elseif ($approver_3->isNotEmpty()) {
                    $data = [
                        'approved_2' => 1,
                        'approved_by_2_id' => $approver_3[0]->id,
                        'approved_date_2' => now(),
                        'status' => 'approved',
                        'approved_by' => $empId,
                        'approved_at' => now(),
                    ];
                } else {
                    return $this->errorResponse('You are not authorized to approve this accomplishment report.', 403);
                }

                DB::table('non_dtr_task')->where('id', $id)->update(array_merge($data, ['updated_at' => now()]));

                $updatedTask = DB::table('non_dtr_task')->where('id', $id)->first();
                if ($updatedTask && $this->isAccomplishmentRequestFullyApproved($updatedTask)) {
                    try {
                        $this->generateAndStoreApprovedAccomplishmentPdf((int) $id);
                    } catch (\Exception $pdfError) {
                        Log::error('Failed to generate approved accomplishment report PDF after approval', [
                            'task_id' => $id,
                            'error' => $pdfError->getMessage(),
                        ]);
                    }
                }

                return $this->successResponse(null, 'Accomplishment report approved successfully');
            }

            if ($approver_1->isNotEmpty()) {
                $data = [
                    'disapproved_1' => 1,
                    'disapproved_by_1_id' => $approver_1[0]->id,
                    'disapproved_date_1' => now(),
                    'status' => 'rejected',
                ];
            } elseif ($approver_2->isNotEmpty()) {
                $data = [
                    'disapproved_2' => 1,
                    'disapproved_by_2_id' => $approver_2[0]->id,
                    'disapproved_date_2' => now(),
                    'status' => 'rejected',
                ];
            } elseif ($approver_3->isNotEmpty()) {
                $data = [
                    'disapproved_2' => 1,
                    'disapproved_by_2_id' => $approver_3[0]->id,
                    'disapproved_date_2' => now(),
                    'status' => 'rejected',
                ];
            } else {
                return $this->errorResponse('You are not authorized to return this accomplishment report.', 403);
            }

            DB::table('non_dtr_task')->where('id', $id)->update(array_merge($data, ['updated_at' => now()]));

            return $this->successResponse(null, 'Accomplishment report returned successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process accomplishment report: ' . $e->getMessage());
        }
    }

    /**
     * Get employee record from authenticated user
     * Returns employee record or throws error response
     */
    private function getEmployeeFromUser()
    {
        $user = Auth::user();

        if (!$user) {
            return [
                'error' => $this->unauthorizedResponse('User not authenticated'),
                'employee' => null
            ];
        }

        // Try to get employee by employee_no
        $employee = DB::table('employees')
            ->where('employee_no', $user->employee_no)
            ->first();

        if (!$employee) {
            \Log::error('NonDTR: Employee record not found', [
                'user_id' => $user->id,
                'employee_no' => $user->employee_no,
                'user_email' => $user->email ?? 'N/A'
            ]);

            return [
                'error' => $this->errorResponse('Employee record not found. Your account may not be properly linked to an employee record. Please contact HR.'),
                'employee' => null
            ];
        }

        return [
            'error' => null,
            'employee' => $employee
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
            ->map(fn($x) => (int)$x)
            ->unique()
            ->values()
            ->toArray();

        if (empty($cos_type_ids)) {
            $cos_type_ids = [2, 4, 6];
        }

        return $cos_type_ids;
    }

    private function getCosEmploymentTypeId()
    {
        $ids = $this->getCosEmploymentTypeIds();
        return $ids[0] ?? 2;
    }

    /**
     * COS payroll employees: Contract of Service, Consultant, Service Provider.
     */
    private function isCOSEmployee($employee_id)
    {
        $employee = DB::table('employees')->where('id', $employee_id)->first();
        if (!$employee) {
            return false;
        }

        return in_array((int)($employee->employment_type_id ?? 0), $this->getCosEmploymentTypeIds(), true);
    }

    private function getPayrollPeriodEtypeColumns()
    {
        static $columns = null;
        if ($columns !== null) {
            return $columns;
        }

        $columns = [
            'period' => Schema::hasColumn('payroll_period_Etype', 'payrollperiod_id')
                ? 'payrollperiod_id'
                : 'payroll_period_id',
            'employment' => Schema::hasColumn('payroll_period_Etype', 'employmenttype_id')
                ? 'employmenttype_id'
                : 'employment_type_id',
        ];

        return $columns;
    }

    private function buildPayrollPeriodQueryFromEtype(array $employment_type_ids)
    {
        $etype_columns = $this->getPayrollPeriodEtypeColumns();

        return DB::table('payroll_periods as pp')
            ->join('payroll_period_Etype as pet', 'pet.' . $etype_columns['period'], '=', 'pp.id')
            ->leftJoin('payroll_intervals as pi', 'pi.id', '=', 'pp.payroll_interval_id')
            ->leftJoin('payroll_cutoffs as pc', 'pc.id', '=', 'pp.payroll_cutoff_id')
            ->whereIn('pet.' . $etype_columns['employment'], $employment_type_ids)
            ->select(
                'pp.id',
                'pp.payroll_interval_id',
                'pp.payroll_cutoff_id',
                'pp.attendance_start_date',
                'pp.attendance_end_date',
                'pp.payroll_start_date',
                'pp.payroll_end_date',
                'pp.release_date',
                'pp.active',
                'pp.posted',
                'pi.name as payroll_interval',
                'pc.name as payroll_cutoff'
            )
            ->distinct()
            ->orderByDesc('pp.attendance_start_date');
    }

    private function buildPayrollPeriodQuery(array $interval_ids)
    {
        return DB::table('payroll_periods as pp')
            ->leftJoin('payroll_intervals as pi', 'pi.id', '=', 'pp.payroll_interval_id')
            ->leftJoin('payroll_cutoffs as pc', 'pc.id', '=', 'pp.payroll_cutoff_id')
            ->whereIn('pp.payroll_interval_id', $interval_ids)
            ->select(
                'pp.id',
                'pp.payroll_interval_id',
                'pp.payroll_cutoff_id',
                'pp.attendance_start_date',
                'pp.attendance_end_date',
                'pp.payroll_start_date',
                'pp.payroll_end_date',
                'pp.release_date',
                'pp.active',
                'pp.posted',
                'pi.name as payroll_interval',
                'pc.name as payroll_cutoff'
            )
            ->orderByDesc('pp.attendance_start_date');
    }

    private function getOpenCosPayrollPeriod($employee)
    {
        if (!Schema::hasTable('payroll_periods')) {
            return null;
        }

        $employee_type_id = (int)($employee->employment_type_id ?? 0);
        $cos_type_ids = $this->getCosEmploymentTypeIds();

        // COS payroll periods are tagged in payroll_period_Etype (HRMS COS payroll tab).
        if (Schema::hasTable('payroll_period_Etype') && !empty($cos_type_ids)) {
            if ($employee_type_id > 0 && in_array($employee_type_id, $cos_type_ids, true)) {
                $period = $this->pickLatestPayrollPeriod(
                    $this->buildPayrollPeriodQueryFromEtype([$employee_type_id])
                );
                if ($period) {
                    return $period;
                }
            }

            $period = $this->pickLatestPayrollPeriod(
                $this->buildPayrollPeriodQueryFromEtype($cos_type_ids)
            );
            if ($period) {
                return $period;
            }
        }

        // Fallback for legacy setups: monthly interval (id 1) or employee interval.
        $interval_ids = [1];
        $employee_interval = (int)($employee->payroll_interval_id ?? 0);
        if ($employee_interval > 0) {
            $interval_ids[] = $employee_interval;
        }

        return $this->pickLatestPayrollPeriod(
            $this->buildPayrollPeriodQuery(array_values(array_unique($interval_ids)))
        );
    }

    private function pickLatestPayrollPeriod($query)
    {
        if (Schema::hasColumn('payroll_periods', 'active')) {
            $active_period = (clone $query)->where(function ($q) {
                $q->where('pp.active', 1)->orWhere('pp.active', true);
            })->first();
            if ($active_period) {
                return $active_period;
            }
        }

        return $query->first();
    }

    private function hasCosPayrollPeriod($employee)
    {
        return $this->getOpenCosPayrollPeriod($employee) !== null;
    }

    private function divisionHeadHasCosPayrollContext($employee_id)
    {
        if (!Schema::hasTable('divisions') || !Schema::hasTable('employees')) {
            return false;
        }

        $division_ids = DB::table('divisions')
            ->where('division_chief_id', $employee_id)
            ->where('active', true)
            ->pluck('id')
            ->toArray();

        if (empty($division_ids)) {
            return false;
        }

        $cos_employees = DB::table('employees')
            ->whereIn('division_id', $division_ids)
            ->whereIn('employment_type_id', $this->getCosEmploymentTypeIds())
            ->where('active', true)
            ->get();

        foreach ($cos_employees as $cos_employee) {
            if ($this->hasCosPayrollPeriod($cos_employee)) {
                return true;
            }
        }

        return false;
    }

    private function getCosPayrollScheduleHeaderId()
    {
        if (!Schema::hasTable('payroll_item_schedule_headers')) {
            return null;
        }

        return DB::table('payroll_item_schedule_headers')
            ->where('employment_type_id', $this->getCosEmploymentTypeId())
            ->orderByDesc('id')
            ->value('id');
    }

    private function periodWithinPayrollPeriod($period_from, $period_to, $payroll_period)
    {
        if (!$payroll_period || !$period_from || !$period_to) {
            return false;
        }

        $start = $payroll_period->attendance_start_date ?? $payroll_period->payroll_start_date ?? null;
        $end = $payroll_period->attendance_end_date ?? $payroll_period->payroll_end_date ?? null;
        if (!$start || !$end) {
            return true;
        }

        return $period_from >= $start && $period_to <= $end;
    }

    private function formatPayrollPeriod($payroll_period)
    {
        return [
            'id' => $payroll_period->id ?? null,
            'payroll_interval' => $payroll_period->payroll_interval ?? null,
            'payroll_cutoff' => $payroll_period->payroll_cutoff ?? null,
            'attendance_start_date' => $payroll_period->attendance_start_date ?? null,
            'attendance_end_date' => $payroll_period->attendance_end_date ?? null,
            'payroll_start_date' => $payroll_period->payroll_start_date ?? null,
            'payroll_end_date' => $payroll_period->payroll_end_date ?? null,
            'release_date' => $payroll_period->release_date ?? null,
        ];
    }

    private function getCosPayrollPeriodsForEmployee($employee)
    {
        if (!Schema::hasTable('payroll_periods')) {
            return collect();
        }

        $employee_type_id = (int)($employee->employment_type_id ?? 0);
        $cos_type_ids = $this->getCosEmploymentTypeIds();

        if (Schema::hasTable('payroll_period_Etype') && $employee_type_id > 0 && in_array($employee_type_id, $cos_type_ids, true)) {
            return $this->buildPayrollPeriodQueryFromEtype([$employee_type_id])->get();
        }

        if (Schema::hasTable('payroll_period_Etype') && !empty($cos_type_ids)) {
            return $this->buildPayrollPeriodQueryFromEtype($cos_type_ids)->get();
        }

        $interval_ids = [1];
        $employee_interval = (int)($employee->payroll_interval_id ?? 0);
        if ($employee_interval > 0) {
            $interval_ids[] = $employee_interval;
        }

        return $this->buildPayrollPeriodQuery(array_values(array_unique($interval_ids)))->get();
    }

    private function getPayrollPeriodForEmployee($employee, $payroll_period_id)
    {
        $employee_type_id = (int)($employee->employment_type_id ?? 0);
        $payroll_period_id = (int)$payroll_period_id;

        if ($payroll_period_id <= 0 || !Schema::hasTable('payroll_periods')) {
            return null;
        }

        $cos_type_ids = $this->getCosEmploymentTypeIds();

        if (Schema::hasTable('payroll_period_Etype') && $employee_type_id > 0 && in_array($employee_type_id, $cos_type_ids, true)) {
            $period = $this->buildPayrollPeriodQueryFromEtype([$employee_type_id])
                ->where('pp.id', $payroll_period_id)
            ->first();
            if ($period) {
                return $period;
            }
        }

        if (Schema::hasTable('payroll_period_Etype') && !empty($cos_type_ids)) {
            $period = $this->buildPayrollPeriodQueryFromEtype($cos_type_ids)
                ->where('pp.id', $payroll_period_id)
                ->first();
            if ($period) {
                return $period;
            }
        }

        return DB::table('payroll_periods as pp')
            ->leftJoin('payroll_intervals as pi', 'pi.id', '=', 'pp.payroll_interval_id')
            ->leftJoin('payroll_cutoffs as pc', 'pc.id', '=', 'pp.payroll_cutoff_id')
            ->where('pp.id', $payroll_period_id)
            ->select(
                'pp.id',
                'pp.payroll_interval_id',
                'pp.payroll_cutoff_id',
                'pp.attendance_start_date',
                'pp.attendance_end_date',
                'pp.payroll_start_date',
                'pp.payroll_end_date',
                'pp.release_date',
                'pp.active',
                'pp.posted',
                'pi.name as payroll_interval',
                'pc.name as payroll_cutoff'
            )
            ->first();
    }

    private function formatPayrollPeriodHalf($period)
    {
        $cutoff_id = (int)($period->payroll_cutoff_id ?? 0);
        $cutoff_name = $period->payroll_cutoff ?? 'Period';
        $sort_order = 99;
        $half_label = $cutoff_name;

        if ($cutoff_id === 1 || stripos($cutoff_name, 'first') !== false) {
            $sort_order = 1;
            $half_label = '1st Half';
        } elseif ($cutoff_id === 3 || stripos($cutoff_name, 'second') !== false) {
            $sort_order = 2;
            $half_label = '2nd Half';
        }

        $from = $period->attendance_start_date ?? $period->payroll_start_date ?? null;
        $to = $period->attendance_end_date ?? $period->payroll_end_date ?? null;
        $date_range_label = null;
        if ($from && $to) {
            $date_range_label = date('M j', strtotime($from)) . ' - ' . date('M j, Y', strtotime($to));
        }

        return array_merge($this->formatPayrollPeriod($period), [
            'half_label' => $half_label,
            'sort_order' => $sort_order,
            'date_range_label' => $date_range_label,
            'is_active' => isset($period->active) ? (bool)$period->active : null,
        ]);
    }

    private function findAccomplishmentReportForPayrollPeriod($tasks, $period)
    {
        $period_id = (int)($period->id ?? 0);
        $from = $period->attendance_start_date ?? $period->payroll_start_date ?? null;
        $to = $period->attendance_end_date ?? $period->payroll_end_date ?? null;

        if (Schema::hasColumn('non_dtr_task', 'payroll_period_id') && $period_id > 0) {
            foreach ($tasks as $task) {
                if ((int)($task->payroll_period_id ?? 0) === $period_id) {
                    return $this->formatAccomplishmentReportSummary($task);
                }
            }
        }

        foreach ($tasks as $task) {
            if ($from && $to && $task->period_from === $from && $task->period_to === $to) {
                return $this->formatAccomplishmentReportSummary($task);
            }
        }

        foreach ($tasks as $task) {
            if ($from && $to && $task->period_from >= $from && $task->period_to <= $to) {
                return $this->formatAccomplishmentReportSummary($task);
            }
        }

        return null;
    }

    private function formatAccomplishmentReportSummary($task)
    {
        $entries_count = DB::table('non_dtr_entries')
            ->where('non_dtr_task_id', $task->id)
            ->count();
        $total_hours = DB::table('non_dtr_entries')
            ->where('non_dtr_task_id', $task->id)
            ->sum('hours_worked');
        $attachments_count = $this->countAttachmentsForTask($task->id);

        return [
            'id' => $task->id,
            'status' => $task->status,
            'period_from' => $task->period_from,
            'period_to' => $task->period_to,
            'entries_count' => $entries_count,
            'attachments_count' => $attachments_count,
            'total_hours' => round((float)$total_hours, 2),
            'created_at' => $task->created_at,
        ];
    }

    private function attachmentsDb()
    {
        return DB::connection('attachments');
    }

    private function insertAttachmentToDb(array $metadata, $fileContent)
    {
        $now = now();

        return $this->attachmentsDb()->table('non_dtr_attachments')->insertGetId([
            'non_dtr_task_id' => $metadata['non_dtr_task_id'],
            'non_dtr_entry_id' => $metadata['non_dtr_entry_id'],
            'file_name' => $metadata['file_name'],
            'file_content' => DB::raw('CONVERT(varbinary(max), 0x' . bin2hex($fileContent) . ')'),
            'file_type' => $metadata['file_type'],
            'file_size' => $metadata['file_size'],
            'description' => $metadata['description'],
            'uploaded_by' => $metadata['uploaded_by'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function getAttachmentsForTask($taskId, $withUploaderNames = false)
    {
        $dbAttachments = $this->attachmentsDb()
            ->table('non_dtr_attachments')
            ->select(
                'id',
                'non_dtr_task_id',
                'non_dtr_entry_id',
                'file_name',
                'file_type',
                'file_size',
                'description',
                'uploaded_by',
                'created_at',
                'updated_at'
            )
            ->where('non_dtr_task_id', $taskId)
            ->get()
            ->map(function ($attachment) {
                $attachment->storage = 'db';
                return $attachment;
            });

        $legacyAttachments = DB::table('non_dtr_attachments')
            ->select(
                'id',
                'non_dtr_task_id',
                'non_dtr_entry_id',
                'file_name',
                'file_type',
                'file_size',
                'description',
                'uploaded_by',
                'created_at',
                'updated_at'
            )
            ->where('non_dtr_task_id', $taskId)
            ->get()
            ->map(function ($attachment) {
                $attachment->storage = 'legacy';
                return $attachment;
            });

        $attachments = $dbAttachments
            ->concat($legacyAttachments)
            ->sortBy('created_at')
            ->values();

        if ($withUploaderNames && !$attachments->isEmpty()) {
            $uploaderIds = $attachments->pluck('uploaded_by')->unique()->filter()->values();
            $uploaders = DB::table('employees')
                ->whereIn('id', $uploaderIds)
                ->select(
                    'id',
                    DB::raw("CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) as uploaded_by_name")
                )
                ->get()
                ->keyBy('id');

            foreach ($attachments as $attachment) {
                $attachment->uploaded_by_name = $uploaders[$attachment->uploaded_by]->uploaded_by_name ?? null;
            }
        }

        return $attachments;
    }

    private function resolveAttachmentById($id)
    {
        $attachment = $this->attachmentsDb()
            ->table('non_dtr_attachments')
            ->where('id', $id)
            ->first();

        if ($attachment) {
            return (object) [
                'attachment' => $attachment,
                'source' => 'db',
            ];
        }

        $legacyAttachment = DB::table('non_dtr_attachments')
            ->where('id', $id)
            ->first();

        if ($legacyAttachment) {
            return (object) [
                'attachment' => $legacyAttachment,
                'source' => 'legacy',
            ];
        }

        return null;
    }

    private function userCanAccessAttachment($employeeId, $taskEmployeeId)
    {
        $employeeId = (int) $employeeId;
        $taskEmployeeId = (int) $taskEmployeeId;

        if ($employeeId === $taskEmployeeId) {
            return true;
        }

        if ($this->isAccomplishmentApproverForEmployee($employeeId, $taskEmployeeId)) {
            return true;
        }

        return $this->userCanViewAccomplishmentReport($employeeId, $taskEmployeeId);
    }

    private function isAccomplishmentApproverForEmployee($approverId, $employeeId)
    {
        $approvers = $this->getAccomplishmentApproversForRequestEmployee((int) $approverId, (int) $employeeId);

        return $approvers['approver_1']->isNotEmpty()
            || $approvers['approver_2']->isNotEmpty()
            || $approvers['approver_3']->isNotEmpty();
    }

    private function getAttachmentFileContent($attachment, $source = 'db')
    {
        if ($source === 'db') {
            if (!empty($attachment->file_content)) {
                return is_resource($attachment->file_content)
                    ? stream_get_contents($attachment->file_content)
                    : $attachment->file_content;
            }

            $record = $this->attachmentsDb()
                ->table('non_dtr_attachments')
                ->select('file_content')
                ->where('id', $attachment->id)
                ->first();

            if (!$record || empty($record->file_content)) {
                return null;
            }

            return is_resource($record->file_content)
                ? stream_get_contents($record->file_content)
                : $record->file_content;
        }

        if (!empty($attachment->file_path) && Storage::disk('public')->exists($attachment->file_path)) {
            return Storage::disk('public')->get($attachment->file_path);
        }

        return null;
    }

    private function attachmentFileResponse($attachment, $fileContent, $disposition = 'inline')
    {
        $contentType = $attachment->file_type ?: 'application/octet-stream';

        return response($fileContent, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => $disposition . '; filename="' . $attachment->file_name . '"',
        ]);
    }

    private function getAccomplishmentTypeId(): int
    {
        static $typeId = null;
        if ($typeId === null) {
            $typeId = (int) DB::table('approver_type')->where('name', 'Accomplishment Report')->value('id');
        }

        return $typeId > 0 ? $typeId : 9;
    }

    private function resolveEmployeeFromUserId($userId)
    {
        return DB::table('users as u')
            ->join('employees as e', 'e.employee_no', '=', 'u.employee_no')
            ->select('e.id', 'e.employee_no')
            ->where('u.id', $userId)
            ->first();
    }

    private function resolveCurrentEmployeeId(): int
    {
        $result = $this->getEmployeeFromUser();
        if ($result['error']) {
            return 0;
        }

        return (int) $result['employee']->id;
    }

    private function employeeHasAccomplishmentApprover($employeeId)
    {
        return DB::table('approver_details as ad')
            ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
            ->where('ad.employee_id', $employeeId)
            ->where('ah.type_id', $this->getAccomplishmentTypeId())
            ->exists();
    }

    private function getAccomplishmentApproverRoles($empId)
    {
        $typeId = $this->getAccomplishmentTypeId();

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

    private function hasAccomplishmentApproverRole(array $roles)
    {
        return $roles['approver_1']->isNotEmpty()
            || $roles['approver_2']->isNotEmpty()
            || $roles['approver_3']->isNotEmpty();
    }

    private function getAccomplishmentApproversForRequestEmployee($empId, $employeeId)
    {
        $typeId = $this->getAccomplishmentTypeId();

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

    private function getAccomplishmentMaxApproverLevelForEmployee($employeeId)
    {
        $approverHeader = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->where('b.employee_id', $employeeId)
            ->where('a.type_id', $this->getAccomplishmentTypeId())
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

    private function toAccomplishmentBool($value): bool
    {
        return (int) ($value ?? 0) === 1 || $value === true || $value === '1';
    }

    private function isAccomplishmentRequestFullyApproved($request)
    {
        if ($this->toAccomplishmentBool($request->disapproved_1 ?? 0) || $this->toAccomplishmentBool($request->disapproved_2 ?? 0)) {
            return false;
        }

        if (($request->status ?? 'pending') === 'approved') {
            return true;
        }

        $employeeId = (int) ($request->employee_id ?? 0);
        if ($employeeId <= 0) {
            return false;
        }

        $maxLevel = $this->getAccomplishmentMaxApproverLevelForEmployee($employeeId);

        if ($maxLevel === 1) {
            return $this->toAccomplishmentBool($request->approved_1 ?? 0);
        }

        if ($maxLevel === 2) {
            return $this->toAccomplishmentBool($request->approved_2 ?? 0);
        }

        return false;
    }

    private function formatAccomplishmentRequestStatus($request)
    {
        if (($request->status ?? '') === 'draft') {
            return 'Draft';
        }

        if ($this->toAccomplishmentBool($request->disapproved_1 ?? 0) || $this->toAccomplishmentBool($request->disapproved_2 ?? 0)) {
            return 'Returned';
        }

        if ($this->isAccomplishmentRequestFullyApproved($request)) {
            return 'Approved';
        }

        $employeeId = (int) ($request->employee_id ?? 0);
        $maxLevel = $employeeId > 0 ? $this->getAccomplishmentMaxApproverLevelForEmployee($employeeId) : 2;

        if ($this->toAccomplishmentBool($request->approved_2 ?? 0) && $maxLevel >= 3) {
            return 'Pending Level 3';
        }

        if ($this->toAccomplishmentBool($request->approved_1 ?? 0) && $maxLevel >= 2) {
            return 'Pending Level 2';
        }

        return 'Pending';
    }

    private function mapAccomplishmentApplicationRow($row): array
    {
        $entriesCount = DB::table('non_dtr_entries')->where('non_dtr_task_id', $row->id)->count();

        return [
            'id' => $row->id,
            'employee_id' => $row->employee_id,
            'request_date' => $row->created_at,
            'period_from' => $row->period_from,
            'period_to' => $row->period_to,
            'payroll_period_id' => $row->payroll_period_id ?? 0,
            'payroll_period' => $row->payroll_period ?? null,
            'task_1' => $row->task_1,
            'status' => $row->status,
            'approved_1' => $this->toAccomplishmentBool($row->approved_1 ?? 0),
            'approved_2' => $this->toAccomplishmentBool($row->approved_2 ?? 0),
            'disapproved_1' => $this->toAccomplishmentBool($row->disapproved_1 ?? 0),
            'disapproved_2' => $this->toAccomplishmentBool($row->disapproved_2 ?? 0),
            'status_label' => $this->formatAccomplishmentRequestStatus($row),
            'entries_count' => $entriesCount,
            'has_approved_report' => $this->isAccomplishmentRequestFullyApproved($row),
        ];
    }

    private function mapAccomplishmentApprovalRow($row): array
    {
        $statusLabel = $this->formatAccomplishmentRequestStatus($row);
        $isReturned = $statusLabel === 'Returned';
        $isFullyApproved = $statusLabel === 'Approved';

        return [
            'id' => $row->id,
            'employee_id' => $row->employee_id,
            'name' => $row->name,
            'request_date' => $row->created_at,
            'period_from' => $row->period_from,
            'period_to' => $row->period_to,
            'payroll_period' => $row->payroll_period ?? null,
            'department' => $row->department ?? null,
            'division' => $row->division ?? null,
            'section' => $row->section ?? null,
            'position' => $row->position ?? null,
            'task_1' => $row->task_1,
            'entries_count' => $row->entries_count ?? 0,
            'approved_1' => $this->toAccomplishmentBool($row->approved_1 ?? 0),
            'approved_2' => $this->toAccomplishmentBool($row->approved_2 ?? 0),
            'disapproved_1' => $this->toAccomplishmentBool($row->disapproved_1 ?? 0),
            'disapproved_2' => $this->toAccomplishmentBool($row->disapproved_2 ?? 0),
            'status' => $isFullyApproved,
            'is_pending' => !$isFullyApproved && !$isReturned,
            'status_label' => $statusLabel,
        ];
    }

    private function baseAccomplishmentApprovalQuery($appKey)
    {
        return DB::table('non_dtr_task as t')
            ->join('employees as e', 't.employee_id', '=', 'e.id')
            ->leftJoin('departments as d', 'e.department_id', '=', 'd.id')
            ->leftJoin('divisions as dv', 'e.division_id', '=', 'dv.id')
            ->leftJoin('sections as s', 'e.section_id', '=', 's.id')
            ->leftJoin('positions as p', 'e.position_id', '=', 'p.id')
            ->leftJoin('payroll_periods as pp', 'pp.id', '=', 't.payroll_period_id')
            ->leftJoin('payroll_intervals as pi', 'pi.id', '=', 'pp.payroll_interval_id')
            ->whereNull('t.deleted_at')
            ->select(
                't.*',
                DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                               CONCAT(e.first_name,' ',substring(e.middle_name,1,1),'. ',e.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](e.first_name,'$appKey'))+' '+UPPER(substring([dbo].[ufn_DecryptString](e.middle_name,'$appKey'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$appKey'))
                            END as name"),
                'd.name as department',
                'dv.name as division',
                's.name as section',
                'p.name as position',
                DB::raw("CASE WHEN t.payroll_period_id > 0 THEN
                        CONCAT(pi.name,' (', DATENAME(MONTH, pp.release_date),' ', DATEPART(YEAR, pp.release_date),')')
                        ELSE NULL END as payroll_period"),
                DB::raw('(SELECT COUNT(*) FROM non_dtr_entries ne WHERE ne.non_dtr_task_id = t.id) as entries_count')
            );
    }

    private function collectAccomplishmentPendingForApprover($empId, $appKey, array $roles)
    {
        $approver_1 = $roles['approver_1'];
        $approver_2 = $roles['approver_2'];
        $approver_3 = $roles['approver_3'];
        $pending = collect();

        if ($approver_3->isNotEmpty()) {
            $pending = $this->queryAccomplishmentApprovalsForLevel($empId, $appKey, 3);
        } elseif ($approver_1->isNotEmpty() && $approver_2->isEmpty()) {
            $pending = $this->queryAccomplishmentApprovalsForLevel($empId, $appKey, 1);
        } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty()) {
            $pending = $this->queryAccomplishmentApprovalsForLevel($empId, $appKey, 2);
        } elseif ($approver_1->isNotEmpty() && $approver_2->isNotEmpty()) {
            $pending = $this->queryAccomplishmentApprovalsForLevel($empId, $appKey, 1)
                ->merge($this->queryAccomplishmentApprovalsForLevel($empId, $appKey, 2))
                ->unique('id')
                ->values();
        }

        return $pending;
    }

    private function collectAccomplishmentApprovedForApprover($empId, $appKey, array $roles)
    {
        $approver_1 = $roles['approver_1'];
        $approver_2 = $roles['approver_2'];
        $approver_3 = $roles['approver_3'];
        $approved = collect();

        if ($approver_3->isNotEmpty()) {
            $approved = $this->queryAccomplishmentApprovedForLevel($empId, $appKey, 3);
        } elseif ($approver_1->isNotEmpty() && $approver_2->isEmpty()) {
            $approved = $this->queryAccomplishmentApprovedForLevel($empId, $appKey, 1);
        } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty()) {
            $approved = $this->queryAccomplishmentApprovedForLevel($empId, $appKey, 2);
        } elseif ($approver_1->isNotEmpty() && $approver_2->isNotEmpty()) {
            $approved = $this->queryAccomplishmentApprovedForLevel($empId, $appKey, 1)
                ->merge($this->queryAccomplishmentApprovedForLevel($empId, $appKey, 2))
                ->unique('id')
                ->values();
        }

        return $approved;
    }

    private function queryAccomplishmentApprovedForLevel($empId, $appKey, $level)
    {
        $query = $this->baseAccomplishmentApprovalQuery($appKey)
            ->whereIn('t.id', function ($subQuery) use ($empId, $level) {
                $subQuery->select('c.id')
                    ->from('approver_details as ad')
                    ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                    ->join('non_dtr_task as c', 'ad.employee_id', '=', 'c.employee_id')
                    ->where('ah.type_id', $this->getAccomplishmentTypeId())
                    ->whereNull('c.deleted_at');

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
                        ->where('c.status', 'approved');
                }
            });

        return $query->distinct()->orderBy('t.created_at', 'desc')->get();
    }

    private function queryAccomplishmentReturnedForApprover($empId, $appKey)
    {
        $subordinateIds = $this->getAccomplishmentSubordinateEmployeeIds($empId);
        if (empty($subordinateIds)) {
            return collect();
        }

        return $this->baseAccomplishmentApprovalQuery($appKey)
            ->whereIn('e.id', $subordinateIds)
            ->where(function ($q) {
                $q->whereRaw('(ISNULL(t.disapproved_1,0) = 1)')
                    ->orWhereRaw('(ISNULL(t.disapproved_2,0) = 1)')
                    ->orWhere('t.status', 'rejected');
            })
            ->distinct()
            ->orderBy('t.created_at', 'desc')
            ->get();
    }

    private function queryAccomplishmentApprovalsForLevel($empId, $appKey, $level)
    {
        $query = $this->baseAccomplishmentApprovalQuery($appKey)
            ->whereIn('t.id', function ($subQuery) use ($empId, $level) {
                $subQuery->select('c.id')
                    ->from('approver_details as ad')
                    ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                    ->join('non_dtr_task as c', 'ad.employee_id', '=', 'c.employee_id')
                    ->where('ah.type_id', $this->getAccomplishmentTypeId())
                    ->whereNull('c.deleted_at')
                    ->where('c.status', 'pending');

                if ($level === 1) {
                    $subQuery->where('ah.approver_id_1', $empId)
                        ->whereRaw('(ISNULL(c.approved_1,0) = 0 AND ISNULL(c.disapproved_1,0) = 0)');
                } elseif ($level === 2) {
                    $subQuery->where('ah.approver_id_2', $empId)
                        ->whereRaw('(ISNULL(c.approved_1,0) = 1 AND ISNULL(c.disapproved_1,0) = 0)')
                        ->whereRaw('(ISNULL(c.approved_2,0) = 0 AND ISNULL(c.disapproved_2,0) = 0)');
                } else {
                    $subQuery->where(function ($q) use ($empId) {
                        $q->where('ah.approver_id_3', $empId)->orWhere('ah.approver_id_4', $empId);
                    })
                        ->whereRaw('(ISNULL(c.approved_1,0) = 1 AND ISNULL(c.disapproved_1,0) = 0)')
                        ->whereRaw('(ISNULL(c.approved_2,0) = 1 AND ISNULL(c.disapproved_2,0) = 0)');
                }
            });

        return $query->distinct()->orderBy('t.created_at', 'desc')->get();
    }

    private function getAccomplishmentSubordinateEmployeeIds($empId)
    {
        return DB::table('approver_details as ad')
            ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
            ->where('ah.type_id', $this->getAccomplishmentTypeId())
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

    private function userCanViewAccomplishmentReport($viewerId, $taskEmployeeId)
    {
        $viewerId = (int) $viewerId;
        $taskEmployeeId = (int) $taskEmployeeId;

        if ($viewerId === $taskEmployeeId) {
            return true;
        }

        $divisions = DB::table('divisions')
            ->where('division_chief_id', $viewerId)
            ->where('active', true)
            ->pluck('id');

        if ($divisions->isNotEmpty()) {
            $inDivision = DB::table('employees')
                ->where('id', $taskEmployeeId)
                ->whereIn('division_id', $divisions)
                ->exists();
            if ($inDivision) {
                return true;
            }
        }

        $roles = $this->getAccomplishmentApproverRoles($viewerId);
        if (!$this->hasAccomplishmentApproverRole($roles)) {
            return false;
        }

        return in_array($taskEmployeeId, $this->getAccomplishmentSubordinateEmployeeIds($viewerId), true);
    }

    private function getImmediateSuperiorForEmployee($employeeId)
    {
        $header = DB::table('approver_headers as ah')
            ->join('approver_details as ad', 'ad.approver_id', '=', 'ah.id')
            ->where('ad.employee_id', $employeeId)
            ->where('ah.type_id', $this->getAccomplishmentTypeId())
            ->select('ah.approver_id_1')
            ->first();

        if (!$header || empty($header->approver_id_1)) {
            return null;
        }

        return DB::table('employees as e')
            ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
            ->where('e.id', $header->approver_id_1)
            ->select(
                DB::raw("CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) as name"),
                'p.name as position'
            )
            ->first();
    }

    private function formatAccomplishmentPeriodLabel($periodFrom, $periodTo)
    {
        if (empty($periodFrom) || empty($periodTo)) {
            return '';
        }

        $from = strtotime($periodFrom);
        $to = strtotime($periodTo);
        if (!$from || !$to) {
            return '';
        }

        $fromMonth = date('F', $from);
        $toMonth = date('F', $to);
        $fromYear = date('Y', $from);
        $toYear = date('Y', $to);

        if ($fromMonth === $toMonth && $fromYear === $toYear) {
            return $fromMonth . ' ' . date('j', $from) . '-' . date('j', $to) . ', ' . $fromYear;
        }

        return date('F j, Y', $from) . ' - ' . date('F j, Y', $to);
    }

    private function isAccomplishmentDraftSave(Request $request)
    {
        return filter_var($request->input('save_as_draft', false), FILTER_VALIDATE_BOOLEAN);
    }

    private function makeAccomplishmentDraftValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'period_from' => 'required|date',
            'period_to' => 'required|date|after_or_equal:period_from',
            'task_1' => 'nullable|string',
            'task_2' => 'nullable|string',
            'task_3' => 'nullable|string',
            'entries' => 'nullable|array',
            'entries.*.work_date' => 'nullable|date',
            'entries.*.accomplishments' => 'nullable|string',
            'entries.*.output_description' => 'nullable|string',
            'entries.*.location' => 'nullable|string',
            'entries.*.payroll_schedule_header_id' => 'nullable|numeric',
            'payroll_period_id' => 'nullable|integer',
            'save_as_draft' => 'nullable|boolean',
        ]);
    }

    private function makeAccomplishmentSubmitValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'task_1' => 'required|string',
            'period_from' => 'required|date',
            'period_to' => 'required|date|after_or_equal:period_from',
            'entries' => 'required|array|min:1',
            'entries.*.work_date' => 'required|date',
            'entries.*.accomplishments' => 'required|string',
            'entries.*.output_description' => 'nullable|string',
            'entries.*.location' => 'nullable|string',
            'entries.*.payroll_schedule_header_id' => 'nullable|numeric',
            'payroll_period_id' => 'nullable|integer',
            'save_as_draft' => 'nullable|boolean',
        ]);
    }

    private function syncAccomplishmentEntries($taskId, $entries, $scheduleHeaderId, $isDraft)
    {
        DB::table('non_dtr_entries')->where('non_dtr_task_id', $taskId)->delete();

        $entries = is_array($entries) ? $entries : [];

        if ($isDraft) {
            $entries = array_values(array_filter($entries, function ($entry) {
                $workDate = $entry['work_date'] ?? null;
                $accomplishments = trim((string)($entry['accomplishments'] ?? ''));

                return !empty($workDate) || $accomplishments !== '';
            }));
        }

        foreach ($entries as $entry) {
            DB::table('non_dtr_entries')->insert([
                'non_dtr_task_id' => $taskId,
                'payroll_schedule_header_id' => $entry['payroll_schedule_header_id'] ?? $scheduleHeaderId,
                'work_date' => $entry['work_date'],
                'time_in' => null,
                'time_out' => null,
                'hours_worked' => 0,
                'accomplishments' => $entry['accomplishments'] ?? '',
                'output_description' => $entry['output_description'] ?? '',
                'location' => $entry['location'] ?? '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function countAttachmentsForTask($taskId)
    {
        return (int) $this->attachmentsDb()
            ->table('non_dtr_attachments')
            ->where('non_dtr_task_id', $taskId)
            ->count();
    }

    private function prepareAccomplishmentPrintViewData(int $taskId): array
    {
        $task = DB::table('non_dtr_task as t')
            ->join('employees as e', 'e.id', '=', 't.employee_id')
            ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
            ->leftJoin('divisions as d', 'd.id', '=', 'e.division_id')
            ->where('t.id', $taskId)
            ->whereNull('t.deleted_at')
            ->select(
                't.*',
                DB::raw("CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) as employee_name"),
                'p.name as employee_position',
                'd.name as employee_division',
                't.employee_id'
            )
            ->first();

        if (!$task) {
            throw new \InvalidArgumentException('Accomplishment report not found');
        }

        $entries = DB::table('non_dtr_entries')
            ->where('non_dtr_task_id', $taskId)
            ->orderBy('work_date', 'asc')
            ->get()
            ->map(function ($entry) {
                $entry->work_date_label = $entry->work_date
                    ? date('F j, Y', strtotime($entry->work_date))
                    : '';

                return $entry;
            });

        $superior = $this->getImmediateSuperiorForEmployee((int) $task->employee_id);

        return [
            'task' => $task,
            'entries' => $entries,
            'employeeName' => trim($task->employee_name ?? ''),
            'employeePosition' => $task->employee_position ?? '',
            'employeeDivision' => $task->employee_division ?? '',
            'reportDate' => $task->created_at ? date('F j, Y', strtotime($task->created_at)) : date('F j, Y'),
            'periodLabel' => $this->formatAccomplishmentPeriodLabel($task->period_from, $task->period_to),
            'superiorName' => $superior->name ?? 'Immediate Superior',
            'superiorPosition' => $superior->position ?? '',
            'electronic_approval' => null,
        ];
    }

    private function buildAccomplishmentElectronicApprovalMeta($task): array
    {
        $signedDate = '';

        if ($this->toAccomplishmentBool($task->approved_2 ?? 0) && !empty($task->approved_date_2)) {
            $signedDate = Carbon::parse($task->approved_date_2)->format('F d, Y h:i A');
        } elseif ($this->toAccomplishmentBool($task->approved_1 ?? 0) && !empty($task->approved_date_1)) {
            $signedDate = Carbon::parse($task->approved_date_1)->format('F d, Y h:i A');
        } elseif (!empty($task->approved_at)) {
            $signedDate = Carbon::parse($task->approved_at)->format('F d, Y h:i A');
        }

        return [
            'status' => 'SIGNED',
            'signed_date' => $signedDate,
        ];
    }

    private function generateAndStoreApprovedAccomplishmentPdf(int $taskId): ?string
    {
        $task = DB::table('non_dtr_task')->where('id', $taskId)->whereNull('deleted_at')->first();
        if (!$task || !$this->isAccomplishmentRequestFullyApproved($task)) {
            return null;
        }

        $viewData = $this->prepareAccomplishmentPrintViewData($taskId);
        $viewData['electronic_approval'] = $this->buildAccomplishmentElectronicApprovalMeta($task);

        if (!view()->exists('non_dtr.accomplishment_report_approved')) {
            throw new \RuntimeException('Approved accomplishment report print template not found');
        }

        $pdf = Pdf::loadView('non_dtr.accomplishment_report_approved', $viewData)
            ->setPaper('a4', 'portrait');
        $pdfContent = $pdf->output();

        $relativePath = $this->buildApprovedAccomplishmentStorageRelativePath($taskId);
        Storage::disk('local')->put($relativePath, $pdfContent);
        $filePath = Storage::disk('local')->path($relativePath);

        $updateData = ['updated_at' => now()];
        if (Schema::hasColumn('non_dtr_task', 'approved_report_path')) {
            $updateData['approved_report_path'] = $filePath;
        }
        DB::table('non_dtr_task')->where('id', $taskId)->update($updateData);

        return is_file($filePath) ? $filePath : null;
    }

    private function buildApprovedAccomplishmentStorageRelativePath(int $taskId): string
    {
        return 'accomplishment_reports/APPROVED_ACCOMPLISHMENT_' . $taskId . '.pdf';
    }

    private function resolveApprovedAccomplishmentStoragePath(int $taskId, ?string $storedPath = null): ?string
    {
        $candidates = [
            $storedPath,
            Storage::disk('local')->path($this->buildApprovedAccomplishmentStorageRelativePath($taskId)),
            storage_path('app/accomplishment_reports/APPROVED_ACCOMPLISHMENT_' . $taskId . '.pdf'),
        ];

        foreach ($candidates as $candidate) {
            if (!empty($candidate) && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
