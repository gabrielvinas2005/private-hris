<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentRequestController extends Controller
{
    use ApiResponse;

    protected $documentTypes = [
        ['value' => 'coe', 'label' => 'Certificate of Employment (COE)'],
        ['value' => 'health_clearance', 'label' => 'Health Clearance'],
        ['value' => 'workspace_clearance', 'label' => 'Workspace Clearance'],
    ];

    /**
     * Determine if the authenticated user is an HR admin
     * (can manage/view all document requests).
     */
    protected function isHRAdmin($user): bool
    {
        return (bool) ($user->is_admin ?? false)
            || (bool) ($user->with_hrm_access ?? false);
    }

    /**
     * List document requests.
     * - Employees: only their own requests.
     * - Admins / HR with permission: all requests with requester details.
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $isAdmin = $this->isHRAdmin($user);

            $query = DB::table('document_requests')
                ->leftJoin('users', 'document_requests.user_id', '=', 'users.id')
                ->orderBy('document_requests.created_at', 'desc')
                ->select(
                    'document_requests.*',
                    'users.name as requester_name',
                    'users.employee_no as requester_employee_no'
                );

            if (!$isAdmin) {
                $query->where('document_requests.user_id', $user->id);
            }

            $requests = $query->get()->map(function ($row) use ($isAdmin) {
                return $this->formatRequest($row, $isAdmin);
            });

            return $this->successResponse([
                'document_types' => $this->documentTypes,
                'requests' => $requests,
                'is_admin' => $isAdmin,
            ]);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load document requests: ' . $e->getMessage());
        }
    }

    /**
     * Submit a new document request (employee action).
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'document_type' => 'required|in:coe,health_clearance,workspace_clearance',
                'purpose' => 'required|string|max:500',
                'notes' => 'nullable|string|max:1000',
            ]);

            $user = Auth::user();

            $id = DB::table('document_requests')->insertGetId([
                'user_id' => $user->id,
                'document_type' => $data['document_type'],
                'purpose' => $data['purpose'],
                'notes' => $data['notes'] ?? null,
                'status' => 'Pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $docRequest = DB::table('document_requests')->where('id', $id)->first();

            Audit::create([
                'user_id' => $user->id,
                'module' => 'HR Module',
                'menu' => 'Document Request',
                'activity' => 'Add',
                'description' => 'Submitted a document request (' . $data['document_type'] . ').',
            ]);

            return $this->successResponse($this->formatRequest($docRequest, false), 'Document request submitted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to submit document request: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a pending document request (employee/owner action only).
     * Admins should use updateStatus() to deny requests instead.
     */
    public function cancel($id)
    {
        try {
            $user = Auth::user();
            $docRequest = DB::table('document_requests')->where('id', $id)->first();

            if (!$docRequest) {
                return $this->notFoundResponse('Document request not found.');
            }

            // Only the employee who submitted the request can cancel it.
            if ((int) $docRequest->user_id !== (int) $user->id) {
                return $this->forbiddenResponse('You are not authorized to cancel this request. Only the requester can cancel.');
            }

            if ($docRequest->status !== 'Pending') {
                return $this->errorResponse('Only pending requests can be cancelled.');
            }

            DB::table('document_requests')
                ->where('id', $id)
                ->update([
                    'status' => 'Cancelled',
                    'updated_at' => now(),
                ]);

            Audit::create([
                'user_id' => $user->id,
                'module' => 'HR Module',
                'menu' => 'Document Request',
                'activity' => 'Cancel',
                'description' => 'Cancelled document request #' . $id,
            ]);

            return $this->successResponse(null, 'Document request cancelled successfully.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to cancel request: ' . $e->getMessage());
        }
    }

    /**
     * Update the status of a document request (HR admin action).
     * Allowed statuses: Processing, Ready for Pickup, Released, Denied
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $user = Auth::user();

            if (!$this->isHRAdmin($user)) {
                return $this->forbiddenResponse('You do not have permission to update document request status.');
            }

            $data = $request->validate([
                'status' => 'required|in:Processing,Ready for Pickup,Released,Denied',
                'remarks' => 'nullable|string|max:500',
            ]);

            $docRequest = DB::table('document_requests')->where('id', $id)->first();

            if (!$docRequest) {
                return $this->notFoundResponse('Document request not found.');
            }

            if (in_array($docRequest->status, ['Cancelled', 'Released', 'Denied'])) {
                return $this->errorResponse('This request has already been finalized and cannot be updated.');
            }

            DB::table('document_requests')
                ->where('id', $id)
                ->update([
                    'status' => $data['status'],
                    'remarks' => $data['remarks'] ?? null,
                    'processed_by' => $user->id,
                    'updated_at' => now(),
                ]);

            Audit::create([
                'user_id' => $user->id,
                'module' => 'HR Module',
                'menu' => 'Document Request',
                'activity' => 'Update Status',
                'description' => 'Updated document request #' . $id . ' status to ' . $data['status'],
            ]);

            $updated = DB::table('document_requests')
                ->leftJoin('users', 'document_requests.user_id', '=', 'users.id')
                ->where('document_requests.id', $id)
                ->select(
                    'document_requests.*',
                    'users.name as requester_name',
                    'users.employee_no as requester_employee_no'
                )
                ->first();

            return $this->successResponse($this->formatRequest($updated, true), 'Document request status updated successfully.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update status: ' . $e->getMessage());
        }
    }

    protected function formatRequest($row, bool $includeRequester = false)
    {
        $typeLabel = collect($this->documentTypes)->firstWhere('value', $row->document_type)['label']
            ?? $row->document_type;

        $result = [
            'id' => $row->id,
            'request_date' => $row->created_at,
            'document_type' => $typeLabel,
            'document_type_key' => $row->document_type,
            'purpose' => $row->purpose,
            'notes' => $row->notes,
            'remarks' => $row->remarks ?? null,
            'status_label' => $row->status,
        ];

        if ($includeRequester) {
            $result['requester_name'] = $row->requester_name ?? null;
            $result['requester_employee_no'] = $row->requester_employee_no ?? null;
        }

        return $result;
    }
}