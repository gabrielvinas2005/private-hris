<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Audit;
use App\WfhApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Arr;

class WfhApplicationController extends Controller
{
    /**
     * WFH Application type_id in approver_headers table
     * Leave = 1, Official Business = 2, Overtime = 3, Work From Home = 4
     */
    const WFH_TYPE_ID = 4;

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
     * Get all WFH applications for the current user
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Get employee_id from user
            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee record not found', 404);
            }

            $employeeId = $employee->id;

            // Check if employee has approver configured for WFH
            $hasApprover = DB::table('approver_details as ad')
                ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                ->where('ad.employee_id', $employeeId)
                ->where('ah.type_id', self::WFH_TYPE_ID)
                ->exists();

            $allowed = $hasApprover ? 1 : 0;

            // Check if user is an approver
            $isApprover = $this->isApprover($employeeId);

            $query = DB::table('wfh_application as wfh')
                ->select('wfh.*');

            // If not approver, only show their own applications
            if (!$isApprover) {
                $query->where('wfh.employee_id', $employeeId);
            }

            // Apply filters
            if ($request->has('status')) {
                $status = $request->status;
                if ($status === 'pending') {
                    $query->where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->where('wfh.approved', 0)
                                ->where('wfh.disapproved', 0)
                                ->whereNull('wfh.processed_at');
                        })
                            ->orWhere(function ($q2) {
                                $q2->where('wfh.approved', 1)
                                    ->where('wfh.approved_2', 0)
                                    ->where('wfh.disapproved_2', 0)
                                    ->whereNull('wfh.processed_at_2');
                            })
                            ->orWhere(function ($q2) {
                                $q2->where('wfh.approved_2', 1)
                                    ->where('wfh.approved_3', 0)
                                    ->where('wfh.disapproved_3', 0)
                                    ->whereNull('wfh.processed_at_3');
                            });
                    });
                } elseif ($status === 'approved') {
                    $query->where(function ($q) {
                        $q->where('wfh.approved_3', 1)
                            ->orWhere(function ($q2) {
                                $q2->where('wfh.approved_2', 1)
                                    ->whereNull('wfh.approved_3')
                                    ->whereNull('wfh.disapproved_3');
                            })
                            ->orWhere(function ($q2) {
                                $q2->where('wfh.approved', 1)
                                    ->whereNull('wfh.approved_2')
                                    ->whereNull('wfh.disapproved_2')
                                    ->whereNull('wfh.approved_3')
                                    ->whereNull('wfh.disapproved_3');
                            });
                    });
                } elseif ($status === 'disapproved') {
                    $query->where(function ($q) {
                        $q->where('wfh.disapproved', 1)
                            ->orWhere('wfh.disapproved_2', 1)
                            ->orWhere('wfh.disapproved_3', 1);
                    });
                } elseif ($status === 'cancelled') {
                    $query->where('wfh.cancelled', 1);
                }
            }

            $applications = $query->orderBy('wfh.id', 'desc')->get();

            // Add employee names after fetching and cast bit fields to boolean
            foreach ($applications as $application) {
                $emp = DB::table('employees')->where('id', $application->employee_id)->first();
                $application->employee_name = $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'Unknown';

                // Cast bit fields to proper boolean values (SQL Server returns 0/1)
                $application->approved = (bool) $application->approved;
                $application->disapproved = (bool) $application->disapproved;
                $application->approved_2 = (bool) $application->approved_2;
                $application->disapproved_2 = (bool) $application->disapproved_2;
                $application->approved_3 = (bool) $application->approved_3;
                $application->disapproved_3 = (bool) $application->disapproved_3;
                $application->cancelled = (bool) $application->cancelled;

                // For approvers: check if this application is pending at their level
                if ($isApprover && $application->employee_id != $employeeId) {
                    $approverLevel = $this->getApproverLevel($application->employee_id, $employeeId);
                    if ($approverLevel) {
                        // Check if application is pending at this approver's level
                        $isPendingAtLevel = false;
                        if ($approverLevel === 1) {
                            // Pending at level 1: not approved/disapproved and no processed_at
                            $isPendingAtLevel = !$application->approved && !$application->disapproved && !$application->processed_at;
                        } elseif ($approverLevel === 2) {
                            // Pending at level 2: level 1 approved, level 2 not processed
                            $isPendingAtLevel = $application->approved && !$application->approved_2 && !$application->disapproved_2 && !$application->processed_at_2;
                        } elseif ($approverLevel === 3) {
                            // Pending at level 3: level 2 approved, level 3 not processed
                            $isPendingAtLevel = $application->approved_2 && !$application->approved_3 && !$application->disapproved_3 && !$application->processed_at_3;
                        }
                        $application->is_pending_for_current_approver = $isPendingAtLevel;
                    } else {
                        $application->is_pending_for_current_approver = false;
                    }
                } else {
                    $application->is_pending_for_current_approver = false;
                }
            }

            return $this->successResponse([
                'applications' => $applications,
                'permissions' => [
                    'allowed' => $allowed,
                    'is_approver' => $isApprover ? 1 : 0,
                ],
                'current_employee_id' => $employeeId,
            ], 'WFH applications retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve WFH applications: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get a single WFH application
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee record not found', 404);
            }

            $employeeId = $employee->id;

            $application = DB::table('wfh_application')
                ->where('id', $id)
                ->first();

            if (!$application) {
                return $this->errorResponse('WFH application not found', 404);
            }

            // Add employee name and cast bit fields to boolean
            $emp = DB::table('employees')->where('id', $application->employee_id)->first();
            $application->employee_name = $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'Unknown';

            // Cast bit fields to proper boolean values (SQL Server returns 0/1)
            $application->approved = (bool) $application->approved;
            $application->disapproved = (bool) $application->disapproved;
            $application->approved_2 = (bool) $application->approved_2;
            $application->disapproved_2 = (bool) $application->disapproved_2;
            $application->approved_3 = (bool) $application->approved_3;
            $application->disapproved_3 = (bool) $application->disapproved_3;
            $application->cancelled = (bool) $application->cancelled;

            // Check if user has access (owner or approver)
            $isApprover = $this->isApprover($employeeId);
            if ($application->employee_id != $employeeId && !$isApprover) {
                return $this->errorResponse('Unauthorized to view this application', 403);
            }

            return $this->successResponse($application, 'WFH application retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve WFH application: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a new WFH application
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after_or_equal:start_date',
                'reason' => 'required|string|max:255',
                'attachment' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed: ' . Arr::first(Arr::flatten($validator->errors()->all())), 422);
            }

            // Get employee_id from user
            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee record not found', 404);
            }

            // Check if employee has approver configured for WFH
            $hasApprover = DB::table('approver_details as ad')
                ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                ->where('ad.employee_id', $employee->id)
                ->where('ah.type_id', self::WFH_TYPE_ID)
                ->exists();

            if (!$hasApprover) {
                return $this->errorResponse('WFH approver is not setup. Notify your HRD.', 400);
            }

            $data = [
                'employee_id' => $employee->id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'reason' => $request->reason,
                'attachment' => $request->attachment,
                'approved' => 0,
                'disapproved' => 0,
                'approved_2' => 0,
                'disapproved_2' => 0,
                'approved_3' => 0,
                'disapproved_3' => 0,
                'cancelled' => 0,
            ];

            $application = WfhApplication::create($data);

            // Save audit trail
            Audit::create([
                'user_id' => $user->id,
                'module' => 'Control Panel',
                'menu' => 'WFH Application',
                'activity' => 'Add',
                'description' => 'Add WFH application.',
            ]);

            return $this->successResponse($application, 'WFH application created successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create WFH application: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update a WFH application (only if not processed)
     */
    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $application = WfhApplication::find($id);
            if (!$application) {
                return $this->errorResponse('WFH application not found', 404);
            }

            // Get employee_id from user
            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee record not found', 404);
            }

            // Check if user owns this application
            if ($application->employee_id != $employee->id) {
                return $this->errorResponse('Unauthorized to update this application', 403);
            }

            // Check if application is already processed or cancelled
            if (
                $application->cancelled ||
                $application->disapproved || $application->disapproved_2 || $application->disapproved_3 ||
                $application->approved_3
            ) {
                return $this->errorResponse('Cannot update application that has been processed or cancelled', 400);
            }

            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after_or_equal:start_date',
                'reason' => 'required|string|max:255',
                'attachment' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed: ' . Arr::first(Arr::flatten($validator->errors()->all())), 422);
            }

            $application->update([
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'reason' => $request->reason,
                'attachment' => $request->attachment,
            ]);

            // Save audit trail
            Audit::create([
                'user_id' => $user->id,
                'module' => 'Control Panel',
                'menu' => 'WFH Application',
                'activity' => 'Update',
                'description' => 'Update WFH application.',
            ]);

            return $this->successResponse($application, 'WFH application updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update WFH application: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cancel a WFH application
     */
    public function cancel(Request $request, $id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $application = WfhApplication::find($id);
            if (!$application) {
                return $this->errorResponse('WFH application not found', 404);
            }

            // Get employee_id from user
            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$employee) {
                return $this->errorResponse('Employee record not found', 404);
            }

            // Check if user owns this application
            if ($application->employee_id != $employee->id) {
                return $this->errorResponse('Unauthorized to cancel this application', 403);
            }

            // Check if already cancelled or fully approved
            if ($application->cancelled) {
                return $this->errorResponse('Application is already cancelled', 400);
            }

            if ($application->approved_3) {
                return $this->errorResponse('Cannot cancel a fully approved application', 400);
            }

            $application->update([
                'cancelled' => 1,
                'cancelled_reason' => $request->cancelled_reason ?? null,
                'cancelled_at' => now(),
            ]);

            // Save audit trail
            Audit::create([
                'user_id' => $user->id,
                'module' => 'Control Panel',
                'menu' => 'WFH Application',
                'activity' => 'Cancel',
                'description' => 'Cancel WFH application.',
            ]);

            return $this->successResponse($application, 'WFH application cancelled successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to cancel WFH application: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Approve WFH application at a specific level
     */
    public function approve(Request $request, $id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $application = WfhApplication::find($id);
            if (!$application) {
                return $this->errorResponse('WFH application not found', 404);
            }

            // Get approver employee_id
            $approverEmployee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$approverEmployee) {
                return $this->errorResponse('Approver employee record not found', 404);
            }

            $approverEmpId = $approverEmployee->id;
            $empId = $application->employee_id;

            // Determine which approval level this approver is
            $approverLevel = $this->getApproverLevel($empId, $approverEmpId);

            if (!$approverLevel) {
                return $this->errorResponse('You are not authorized to approve this application', 403);
            }

            // Check if already processed at this level
            if ($approverLevel === 1 && $application->processed_at) {
                return $this->errorResponse('Application already processed at level 1', 400);
            }
            if ($approverLevel === 2 && $application->processed_at_2) {
                return $this->errorResponse('Application already processed at level 2', 400);
            }
            if ($approverLevel === 3 && $application->processed_at_3) {
                return $this->errorResponse('Application already processed at level 3', 400);
            }

            // Check prerequisites
            if ($approverLevel === 2 && !$application->approved) {
                return $this->errorResponse('Level 1 approval required before level 2', 400);
            }
            if ($approverLevel === 3 && !$application->approved_2) {
                return $this->errorResponse('Level 2 approval required before level 3', 400);
            }

            // Update approval
            $updateData = [];
            if ($approverLevel === 1) {
                $updateData = [
                    'approved' => 1,
                    'disapproved' => 0,
                    'processed_at' => now(),
                ];
            } elseif ($approverLevel === 2) {
                $updateData = [
                    'approved_2' => 1,
                    'disapproved_2' => 0,
                    'processed_at_2' => now(),
                ];
            } elseif ($approverLevel === 3) {
                $updateData = [
                    'approved_3' => 1,
                    'disapproved_3' => 0,
                    'processed_at_3' => now(),
                ];
            }

            $application->update($updateData);

            // Save audit trail
            Audit::create([
                'user_id' => $user->id,
                'module' => 'Control Panel',
                'menu' => 'WFH Application',
                'activity' => 'Approve',
                'description' => "Approved WFH application at level {$approverLevel}.",
            ]);

            return $this->successResponse($application, "WFH application approved at level {$approverLevel} successfully");
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to approve WFH application: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Disapprove WFH application at a specific level
     */
    public function disapprove(Request $request, $id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $validator = Validator::make($request->all(), [
                'disapproved_reason' => 'required|string|max:255'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed: ' . Arr::first(Arr::flatten($validator->errors()->all())), 422);
            }

            $application = WfhApplication::find($id);
            if (!$application) {
                return $this->errorResponse('WFH application not found', 404);
            }

            // Get approver employee_id
            $approverEmployee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$approverEmployee) {
                return $this->errorResponse('Approver employee record not found', 404);
            }

            $approverEmpId = $approverEmployee->id;
            $empId = $application->employee_id;

            // Determine which approval level this approver is
            $approverLevel = $this->getApproverLevel($empId, $approverEmpId);

            if (!$approverLevel) {
                return $this->errorResponse('You are not authorized to disapprove this application', 403);
            }

            // Check if already processed at this level
            if ($approverLevel === 1 && $application->processed_at) {
                return $this->errorResponse('Application already processed at level 1', 400);
            }
            if ($approverLevel === 2 && $application->processed_at_2) {
                return $this->errorResponse('Application already processed at level 2', 400);
            }
            if ($approverLevel === 3 && $application->processed_at_3) {
                return $this->errorResponse('Application already processed at level 3', 400);
            }

            // Update disapproval
            $updateData = [];
            if ($approverLevel === 1) {
                $updateData = [
                    'approved' => 0,
                    'disapproved' => 1,
                    'disapproved_reason' => $request->disapproved_reason,
                    'processed_at' => now(),
                ];
            } elseif ($approverLevel === 2) {
                $updateData = [
                    'approved_2' => 0,
                    'disapproved_2' => 1,
                    'disapproved_reason_2' => $request->disapproved_reason,
                    'processed_at_2' => now(),
                ];
            } elseif ($approverLevel === 3) {
                $updateData = [
                    'approved_3' => 0,
                    'disapproved_3' => 1,
                    'disapproved_reason_3' => $request->disapproved_reason,
                    'processed_at_3' => now(),
                ];
            }

            $application->update($updateData);

            // Save audit trail
            Audit::create([
                'user_id' => $user->id,
                'module' => 'Control Panel',
                'menu' => 'WFH Application',
                'activity' => 'Disapprove',
                'description' => "Disapproved WFH application at level {$approverLevel}.",
            ]);

            return $this->successResponse($application, "WFH application disapproved at level {$approverLevel} successfully");
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to disapprove WFH application: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Check if user is an approver for WFH applications
     */
    private function isApprover($employeeId)
    {
        // Check if employee is an approver at any level for WFH applications
        $approver = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->where('a.type_id', self::WFH_TYPE_ID)
            ->where(function ($query) use ($employeeId) {
                $query->where('a.approver_id_1', $employeeId)
                    ->orWhere('a.approver_id_2', $employeeId)
                    ->orWhere('a.approver_id_3', $employeeId)
                    ->orWhere('a.branch_approver_id_1', $employeeId)
                    ->orWhere('a.division_approver_id_1', $employeeId)
                    ->orWhere('a.section_approver_id_1', $employeeId);
            })
            ->exists();

        return $approver;
    }

    /**
     * Get the approval level for a specific approver
     */
    private function getApproverLevel($employeeId, $approverEmpId)
    {
        // Check level 1
        $approver1 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->where('b.employee_id', $employeeId)
            ->where('a.type_id', self::WFH_TYPE_ID)
            ->where(function ($query) use ($approverEmpId) {
                $query->where('a.approver_id_1', $approverEmpId)
                    ->orWhere('a.branch_approver_id_1', $approverEmpId)
                    ->orWhere('a.division_approver_id_1', $approverEmpId)
                    ->orWhere('a.section_approver_id_1', $approverEmpId);
            })
            ->exists();

        if ($approver1) {
            return 1;
        }

        // Check level 2
        $approver2 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->where('b.employee_id', $employeeId)
            ->where('a.type_id', self::WFH_TYPE_ID)
            ->where('a.approver_id_2', $approverEmpId)
            ->exists();

        if ($approver2) {
            return 2;
        }

        // Check level 3
        $approver3 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->where('b.employee_id', $employeeId)
            ->where('a.type_id', self::WFH_TYPE_ID)
            ->where('a.approver_id_3', $approverEmpId)
            ->exists();

        if ($approver3) {
            return 3;
        }

        return null;
    }
}
