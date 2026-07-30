<?php

namespace App\Http\Controllers;

use PDF;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class MonetizationController extends Controller
{
    use ApiResponse;

    /** Minimum VL balance required before an employee may file monetization. */
    private const MIN_VL_BALANCE_TO_APPLY = 15;

    /** VL credits that must remain after monetization. */
    private const MIN_VL_BALANCE_TO_RETAIN = 5;

    /** leave_monetizations table supports approve_1 and approve_2 (see schema). */
    private const MONETIZATION_TABLE_APPROVAL_LEVELS = 2;

    /**
     * Portal passes users.id; HR tools may pass employees.id.
     * Prefer users.id → employees.id via employee_no (same as storeMonetization).
     */
    private function resolveMonetizationEmployeeId($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return 0;
        }

        $user = DB::table('users')->where('id', $id)->first();
        if ($user && !empty($user->employee_no)) {
            $employee = DB::table('employees')->where('employee_no', $user->employee_no)->first();
            if ($employee) {
                return (int) $employee->id;
            }
        }

        $employee = DB::table('employees')->where('id', $id)->first();
        if ($employee) {
            return (int) $id;
        }

        return 0;
    }

    public function index()
    {
        try {
            $monetizations = DB::table('monetization_setups')->get();

            if ($monetizations->isEmpty()) {
                $monetizations = array(
                    'id' => 0,
                    'cf_rate' => 0,
                    'maximum_number_allowed' => 0
                );

                $monetizations = (object)$monetizations;
                $monetizations = collect([$monetizations]);
            }

            return $this->successResponse($monetizations, 'Monetization setup retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve monetization setup: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'cf_rate' => 'required|numeric|min:0',
                'maximum_number_allowed' => 'required|integer|min:0'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            if ($id == 0) {
                DB::table('monetization_setups')->insert(['cf_rate' => $request->cf_rate, 'maximum_number_allowed' => $request->maximum_number_allowed]);

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Control Panel',
                    'menu'    => 'Monetization Setup',
                    'activity' => 'Added',
                    'description' => 'Added Monetization Setup.',
                );
            } else {
                DB::table('monetization_setups')->where('id', $id)->update(['cf_rate' => $request->cf_rate, 'maximum_number_allowed' => $request->maximum_number_allowed]);

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Control Panel',
                    'menu'    => 'Monetization Setup',
                    'activity' => 'Update',
                    'description' => 'Updated Monetization Setup.',
                );
            }

            //Save audit trail
            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Monetization setup updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update monetization setup: ' . $e->getMessage());
        }
    }

    public function loadMonetization($id)
    {
        $app_key = env("APP_KEY", "");

        $emp_id = $this->resolveMonetizationEmployeeId($id);
        $supervisor_id = 0;

        if ($emp_id > 0) {
            $emp_id_data = DB::table('employees as b')
                ->leftJoin('departments as c', 'b.id', '=', 'c.employee_id')
                ->select(
                    'b.id as employee_id',
                    DB::raw('case when c.employee_id is null then 0 else c.employee_id end as supervisor_id')
                )
                ->where('b.id', $emp_id)
                ->first();

            if ($emp_id_data) {
                $supervisor_id = $emp_id_data->supervisor_id ?? 0;
            }
        }

        // Debug: Log employee lookup
        Log::info('Monetization employee lookup', [
            'input_id' => $id,
            'emp_id' => $emp_id,
            'supervisor_id' => $supervisor_id
        ]);

        if ($emp_id > 0) {
            // Query leave_credits using employees.id (employee_id in leave_credits table)
            // Get Vacation Leave (1) and Sick Leave (2) credits
            // Note: We query leave_credits table directly using employee_id which matches employees.id
            $leaveTypeIds = $this->resolveEmployeeLeaveTypeIds($emp_id);
            $balanceTypeIds = array_values(array_filter([
                $leaveTypeIds['vl'],
                $leaveTypeIds['sl'],
            ]));

            $leave_balances = DB::table('leave_credits as a')
                ->join('leave_types as b', 'b.id', '=', 'a.leave_type_id')
                ->select('a.leave_type_id', 'b.name as type', 'a.credits as balance')
                ->where('a.employee_id', $emp_id)
                ->whereIn('a.leave_type_id', $balanceTypeIds)
                ->where('b.active', true)
                ->get();

            // Ensure we always return an array, even if empty
            if ($leave_balances->isEmpty()) {
                // If no records found, check if records exist without active filter
                $check_balances = DB::table('leave_credits as a')
                    ->join('leave_types as b', 'b.id', '=', 'a.leave_type_id')
                    ->select('a.leave_type_id', 'b.name as type', 'a.credits as balance', 'b.active')
                    ->where('a.employee_id', $emp_id)
                    ->whereIn('a.leave_type_id', $balanceTypeIds)
                    ->get();

                Log::warning('Monetization: No active leave balances found, but found inactive records', [
                    'emp_id' => $emp_id,
                    'check_balances' => $check_balances->toArray()
                ]);
            }

            // Debug: Log the query results
            Log::info('Monetization leave_balances query', [
                'emp_id' => $emp_id,
                'user_id' => $id,
                'leave_balances_count' => $leave_balances->count(),
                'leave_balances' => $leave_balances->toArray()
            ]);

            $leave_monetization = DB::table('leave_monetizations as a')
                ->leftJoin('employees as b', 'a.approve_1_id', '=', 'b.id')
                ->leftJoin('employees as c', 'a.approve_2_id', '=', 'c.id')
                ->leftJoin('employees as d', 'a.disapprove_1_id', '=', 'd.id')
                ->leftJoin('employees as e', 'a.disapprove_2_id', '=', 'e.id')
                ->select(
                    'a.*',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as level_1_approver"),
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key'))
                            END as level_2_approver"),
                    DB::raw("CASE WHEN ISNULL(d.is_encrypted,0) = 0 THEN
                                CONCAT(d.first_name,' ',d.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](d.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](d.last_name,'$app_key'))
                            END as level_1_disapprover"),
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                CONCAT(e.first_name,' ',e.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                            END as level_2_disapprover")
                )
                ->where([
                    'a.employee_id' => $emp_id
                ])
                ->get();
        } else {
            // Return empty collection if no employee found
            $leave_balances = collect([]);

            $leave_monetization = array(
                'id' => 0,
                'vl_credit' => 0,
                'sl_credit' => 0,
                'total_days' => 0,
                'amount' => 0,
                'created_at' => '',
                'approve_1' => false,
                'disapprove_1' => false,
                'approve_2' => false,
                'disapprove_2' => false,
                'is_processed' => false,
                'approve_date_1' => null,
                'approve_date_2' => null,
                'disapprove_date_1' => null,
                'disapprove_date_2' => null,
                'level_1_approver' => null,
                'level_2_approver' => null,
                'level_1_disapprover' => null,
                'level_2_disapprover' => null,
                'type_id' => 0,
            );

            $leave_monetization = (object)$leave_monetization;
            $leave_monetization = collect([$leave_monetization]);
        }

        // Check if Approver Start
        $approver_1 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select(
                'a.id',
                'a.approver_id_1 as supervisor_id'
            )
            ->where('a.branch_approver_id_1', $emp_id)
            ->orWhere('a.approver_id_1', $emp_id)
            ->orWhere('a.division_approver_id_1', $emp_id)
            ->orWhere('a.section_approver_id_1', $emp_id)
            ->distinct()
            ->get();

        $approver_2 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select(
                'a.id',
                'a.approver_id_2 as supervisor_id'
            )
            ->where('a.approver_id_2', $emp_id)
            ->distinct()
            ->get();

        $approver_3 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select(
                'a.id',
                'a.approver_id_3 as supervisor_id'
            )
            ->where('a.approver_id_3', $emp_id)
            ->distinct()
            ->get();

        if ($approver_1->isNotEmpty() && $approver_2->isEmpty()) {
            $approver_id = $approver_1[0]->id;
            $supervisor_id = $approver_1[0]->supervisor_id;

            $leave_for_approvals = DB::table('leave_monetizations as a')
                ->join('employees as c', 'a.employee_id', '=', 'c.id')
                ->select(
                    'a.*',
                    DB::raw('1 as approver_level'),
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key'))
                            END as name")
                )
                ->whereIn('a.id', function ($query) use ($emp_id) {
                    $query->select('c.id')
                        ->from('approver_details as a')
                        ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                        ->join('leave_monetizations as c', 'a.employee_id', '=', 'c.employee_id')
                        ->whereRaw("
                                (ISNULL(c.approve_1,0) = 0 OR ISNULL(c.approve_1,0) = 1)
                                AND (b.branch_approver_id_1 = $emp_id OR b.approver_id_1  = $emp_id or b.division_approver_id_1 = $emp_id or b.section_approver_id_1 = $emp_id)
                            ");
                })
                ->whereNotIn('c.id', function ($query) use ($emp_id) {
                    $query->select('id')->from('employees')->where('id', $emp_id);
                })
                ->distinct()
                ->orderBy('a.created_at', 'desc')
                ->get();
        } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty()) {
            $approver_id = $approver_2[0]->id;
            $supervisor_id = $approver_2[0]->supervisor_id;

            // Get leave for Approvals
            $leave_for_approvals = DB::table('leave_monetizations as a')
                ->join('employees as c', 'a.employee_id', '=', 'c.id')
                ->select(
                    'a.*',
                    DB::raw('2 as approver_level'),
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key'))
                            END as name")
                )
                ->whereIn('a.id', function ($query) use ($emp_id) {
                    $query->select('c.id')
                        ->from('approver_details as a')
                        ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                        ->join('leave_monetizations as c', 'a.employee_id', '=', 'c.employee_id')
                        ->whereRaw("
                                    c.approve_1 = 1 AND b.approver_id_2 = $emp_id
                                  ");
                })
                ->whereNotIn('c.id', function ($query) use ($emp_id) {
                    $query->select('id')
                        ->from('employees')
                        ->where('id', $emp_id);
                })
                ->whereRaw(
                    "isnull(a.approve_1,0) = 1"
                )
                ->distinct()
                ->orderBy('a.created_at', 'desc')
                ->get();
        } elseif ($approver_1->isNotEmpty() && $approver_2->isNotEmpty()) {
            $approver_id = $approver_2[0]->id;
            $supervisor_id = $approver_2[0]->supervisor_id;

            // Get leave for Approvals
            $leave_for_approvals_1 = DB::table('leave_monetizations as a')
                ->join('employees as c', 'a.employee_id', '=', 'c.id')
                ->select(
                    'a.*',
                    DB::raw('1 as approver_level'),
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key'))
                            END as name")
                )
                ->whereIn(
                    'a.id',
                    function ($query) use ($emp_id) {
                        $query->select('c.id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->join('leave_monetizations as c', 'a.employee_id', '=', 'c.employee_id')
                            ->whereRaw("
                                (ISNULL(c.approve_1,0) = 0 OR ISNULL(c.approve_1,0) = 1)
                                AND (b.branch_approver_id_1 = $emp_id OR b.approver_id_1  = $emp_id or b.division_approver_id_1 = $emp_id or b.section_approver_id_1 = $emp_id)
                            ");
                    }
                )
                ->whereNotIn('c.id', function ($query) use ($emp_id) {
                    $query->select('id')->from('employees')->where('id', $emp_id);
                })
                ->distinct();

            $leave_for_approvals = DB::table('leave_monetizations as a')
                ->join('employees as c', 'a.employee_id', '=', 'c.id')
                ->select(
                    'a.*',
                    DB::raw('2 as approver_level'),
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key'))
                            END as name")
                )
                ->whereIn('a.id', function ($query) use ($emp_id) {
                    $query->select('c.id')
                        ->from('approver_details as a')
                        ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                        ->join('leave_monetizations as c', 'a.employee_id', '=', 'c.employee_id')
                        ->whereRaw("
                                    c.approve_1 = 1 AND b.approver_id_2 = $emp_id
                                  ");
                })
                ->whereNotIn('c.id', function ($query) use ($emp_id) {
                    $query->select('id')
                        ->from('employees')
                        ->where('id', $emp_id);
                })
                ->whereRaw(
                    "isnull(a.approve_1,0) = 1"
                );

            $leave_for_approvals = $leave_for_approvals->unionAll($leave_for_approvals_1);

            if ($approver_3->isNotEmpty() && Schema::hasColumn('leave_monetizations', 'approve_3')) {
                $leave_for_approvals_3 = DB::table('leave_monetizations as a')
                    ->join('employees as c', 'a.employee_id', '=', 'c.id')
                    ->select(
                        'a.*',
                        DB::raw('3 as approver_level'),
                        DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                    CONCAT(c.first_name,' ',c.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key'))
                                END as name")
                    )
                    ->whereIn('a.id', function ($query) use ($emp_id) {
                        $query->select('c.id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->join('leave_monetizations as c', 'a.employee_id', '=', 'c.employee_id')
                            ->whereRaw("
                                isnull(c.approve_1,0) = 1
                                AND isnull(c.approve_2,0) = 1
                                AND (b.approver_id_3 = $emp_id OR b.approver_id_4 = $emp_id)
                            ");
                    })
                    ->whereNotIn('c.id', function ($query) use ($emp_id) {
                        $query->select('id')->from('employees')->where('id', $emp_id);
                    })
                    ->whereRaw('isnull(a.approve_1,0) = 1')
                    ->whereRaw('isnull(a.approve_2,0) = 1')
                    ->whereRaw('isnull(a.approve_3,0) = 0');

                $leave_for_approvals = $leave_for_approvals->unionAll($leave_for_approvals_3);
            }

            $leave_for_approvals = $leave_for_approvals
                ->distinct()
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $approver_id = 0;
            $supervisor_id = 0;
            $leave_for_approvals = [];
        }

        // Check if With Approver.
        $with_approvers = DB::table('approver_details')->where('employee_id', $emp_id)->get();

        if (count($with_approvers) > 0) {
            $allowed = 1;
        } else {
            $allowed = 0;
        }

        // Load Attachments for each monetization and sync approved leave credits
        foreach ($leave_monetization as $monetization) {
            $attachments = DB::table('leave_monetization_attachments')
                ->where('leave_monetization_id', $monetization->id)
                ->get();

            $monetization->attachments = $attachments;
            $this->decorateMonetizationRecord($monetization, $emp_id);
            $this->applyLeaveCreditsForApprovedMonetizationIfNeeded($monetization);
        }

        // Refresh balances after deductions applied above
        if ($emp_id > 0) {
            $refreshedTypeIds = $this->resolveEmployeeLeaveTypeIds($emp_id);
            $refreshedBalanceTypeIds = array_values(array_filter([
                $refreshedTypeIds['vl'],
                $refreshedTypeIds['sl'],
            ]));
            if (!empty($refreshedBalanceTypeIds)) {
                $leave_balances = DB::table('leave_credits as a')
                    ->join('leave_types as b', 'b.id', '=', 'a.leave_type_id')
                    ->select('a.leave_type_id', 'b.name as type', 'a.credits as balance')
                    ->where('a.employee_id', $emp_id)
                    ->whereIn('a.leave_type_id', $refreshedBalanceTypeIds)
                    ->where('b.active', true)
                    ->get();
            }
        }

        // Load Attachments for approval items
        foreach ($leave_for_approvals as $approval) {
            $attachments = DB::table('leave_monetization_attachments')
                ->where('leave_monetization_id', $approval->id)
                ->get();

            $approval->attachments = $attachments;
            $this->decorateMonetizationRecord($approval, (int) ($approval->employee_id ?? 0));
        }

        // Get employee salary and monetization setup for amount calculation
        $salary = 0;
        $cf_rate = 0.0481927; // default value

        if ($emp_id > 0) {
            $employee = DB::table('employees')->where('id', $emp_id)->first();
            if ($employee) {
                $salary = $employee->salary ?? 0;
            }

            $monetization_setup = DB::table('monetization_setups')->first();
            if ($monetization_setup && isset($monetization_setup->cf_rate)) {
                $cf_rate = $monetization_setup->cf_rate;
                if ($cf_rate == 0) {
                    $cf_rate = 0.0481927; // use default if 0
                }
            }
        }

        return $this->successResponse([
            'emp_id' => $emp_id,
            'supervisor_id' => $supervisor_id,
            'leave_balances' => $leave_balances,
            'leave_monetization' => $leave_monetization,
            'leave_for_approvals' => $leave_for_approvals,
            'salary' => $salary,
            'cf_rate' => $cf_rate,
            'allowed' => $allowed,
            'required_approval_levels' => $emp_id > 0
                ? $this->getMonetizationMaxApprovalLevel($emp_id)
                : 1,
            'requires_second_approval' => $emp_id > 0
                ? $this->getMonetizationMaxApprovalLevel($emp_id) > 1
                : false,
            'vl_monetization_rules' => [
                'min_vl_balance_to_apply' => self::MIN_VL_BALANCE_TO_APPLY,
                'min_vl_balance_to_retain' => self::MIN_VL_BALANCE_TO_RETAIN,
            ],
        ], 'Monetization data retrieved successfully');
    }

    public function storeMonetization(Request $request)
    {
        try {
            $data = $request->all();


            $monetization_setup = DB::table('monetization_setups')->get();

            // Handle both user_id and employee_id
            $employee_id = null;
            if (isset($data["employee_id"])) {
                // Check if this is actually a user_id by looking in users table
                $user_check = DB::table('users')->where('id', $data["employee_id"])->first();
                if ($user_check) {
                    // This is a user_id, get the corresponding employee_id
                    $emp_data = DB::table('employees')->where('employee_no', $user_check->employee_no)->first();
                    $employee_id = $emp_data ? $emp_data->id : null;
                } else {
                    // This is already an employee_id
                    $employee_id = $data["employee_id"];
                }
            }

            if (!$employee_id) {
                return $this->errorResponse('Employee not found. Please ensure your account is linked to an employee record.');
            }

            $employees = DB::table('employees')->where('id', $employee_id)->get();
            $leave_monetization_id = $data["leave_monetization_id"];

            $total_credits_validation = ((($data["vl_credit"] - 5) + $data["sl_credit"]) / 2);
            $total_vl_credits_validation = ((($data["vl_credit"] - 5)) / 2);
            $total_credits = ($data["vl_to_monetize"] + $data["sl_to_monetize"]);
            $vl = ($data["vl_credit"] - 5);
            $sl = $data["sl_credit"];

            if ($employees->isNotEmpty()) {
                $salary = ($employees[0]->salary / 22);
            } else {
                $salary = 0;
            }

            if ($monetization_setup->isNotEmpty()) {
                $cf_rate = $monetization_setup[0]->cf_rate;
                $maximum_number_allowed = $monetization_setup[0]->maximum_number_allowed;
            } else {
                $cf_rate = 0.0481927;
                $maximum_number_allowed = 0;
            }

            if ($cf_rate == 0) {
                $cf_rate = 0.0481927;
            }

            if ($vl <= 0) {
                $vl = 0;
            }

            if ($sl <= 0) {
                $sl = 0;
            }

            if ($total_vl_credits_validation <= 0) {
                $total_vl_credits_validation = 0;
            }

            if ($total_credits_validation <= 0) {
                $total_credits_validation = 0;
            }

            // Ensure type_id is an integer (1 = Regular, 2 = Special)
            $type_id = (int)($data["type_id"] ?? 1);

            $vlBalance = (float) ($data['vl_credit'] ?? 0);
            $vlToMonetize = (float) ($data['vl_to_monetize'] ?? 0);
            $vlValidationError = $this->validateVlMonetizationCredits($vlBalance, $vlToMonetize);
            if ($vlValidationError) {
                return $this->errorResponse($vlValidationError);
            }

            if ($type_id == 1) {
                if ($vlBalance <= 0) {
                    return $this->errorResponse('No credit amount to monetize');
                }

                if ($vlToMonetize <= 0 || $vlToMonetize < 10) {
                    return $this->errorResponse('Minimum credit amount to monetize is 10.');
                } elseif ($vlToMonetize > 50) {
                    return $this->errorResponse('Invalid VL Credits to monetize. Leave to monetize must only be maximum to 50 credits only.');
                }
            } else {
                if ($data["vl_credit"] <= 0 && $data["sl_credit"] <= 0) {
                    return $this->errorResponse('No credit amount to monetize');
                }

                if (($data["vl_to_monetize"] + $data["sl_to_monetize"]) <= 0 || ($data["vl_to_monetize"] + $data["sl_to_monetize"]) < 10) {
                    return $this->errorResponse('Minimum credit amount to monetize is 10.');
                } elseif (($data["vl_to_monetize"] + $data["sl_to_monetize"]) > 50) {
                    return $this->errorResponse('Invalid Credits to monetize. Leave to monetize must only be maximum to 50 credits only.');
                }
            }

            if ($vlToMonetize > $vlBalance) {
                return $this->errorResponse('Invalid VL Credits to monetize.');
            } elseif ($data["sl_to_monetize"] > $data["sl_credit"]) {
                return $this->errorResponse('Invalid SL Credits to monetize.');
            }

            $amount = ((($salary * $data["total_days"]) * $cf_rate));

            if ($leave_monetization_id == 0) {
                $monetization_data = [
                    'employee_id' => $employee_id,
                    'vl_credit' => $data["vl_to_monetize"],
                    'sl_credit' => $data["sl_to_monetize"],
                    'total_days' => $data["total_days"],
                    'cf_rate' => $cf_rate,
                    'amount' => $amount,
                    'type_id' => $type_id,
                    'credits_applied' => 0,
                    'created_at' => now()
                ];

                DB::table('leave_monetizations')->insert($monetization_data);
                $leave_monetization_id = DB::table('leave_monetizations')->max('id');
            } else {
                $monetization_data = [
                    'employee_id' => $employee_id,
                    'vl_credit' => $data["vl_to_monetize"],
                    'sl_credit' => $data["sl_to_monetize"],
                    'total_days' => $data["total_days"],
                    'cf_rate' => $cf_rate,
                    'amount' => $amount,
                    'type_id' => $type_id,
                    'credits_applied' => 0,
                    'updated_at' => now()
                ];

                DB::table('leave_monetizations')->where('id', $leave_monetization_id)->update($monetization_data);
            }

            // insert Leavee Attachments
            if ($request->hasFile('supporting_documents')) {

                $allowedfileExtension = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xlsx', 'xls'];
                $files = $request->file('supporting_documents');
                $ctr = 0;

                foreach ($files as $file) {
                    $file_name = $file->getClientOriginalName();
                    $file_path = Storage::disk('local')->getAdapter()->getPathPrefix() . 'leave_monetization_attachments\\' . 'LM' . '_' . $leave_monetization_id . '_' . $file_name;
                    $extension = $file->getClientOriginalExtension();
                    $check = in_array($extension, $allowedfileExtension);

                    if ($check) {
                        // Save record of attachments to database.
                        $leave_attachment_data = [
                            'leave_monetization_id' => $leave_monetization_id,
                            'attachment' => $file_name,
                            'path' => $file_path
                        ];

                        DB::table('leave_monetization_attachments')->insert($leave_attachment_data);

                        // Save attachment to path.
                        $request->supporting_documents[$ctr]->storeAs('leave_monetization_attachments', 'LM' . '_' . $leave_monetization_id . '_' . $file_name);
                        $ctr++;
                    }
                }
            }

            return $this->successResponse($data, 'Monetization stored successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to store monetization: ' . $e->getMessage());
        }
    }

    public function deleteMonetization($id)
    {
        try {
            DB::table('leave_monetizations')->where('id', $id)->delete();

            DB::table('leave_monetization_attachments')->where('leave_monetization_id', $id)->delete();

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Monetization Leave',
                'menu'    => 'Monetization',
                'activity' => 'Delete',
                'description' => 'Deleted Monetization.',
            );

            //Save audit trail
            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Monetization deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete monetization: ' . $e->getMessage());
        }
    }


    public function processMonetization($id, $type_id, $remarks)
    {
        try {
            // Get Approver ID
            $emp_id_data = DB::table('users as a')
                ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
                ->selectRaw('case when ISNULL(b.id,0) = 0 then 0 else b.id end as id')
                ->where('a.id', Auth::user()->id)
                ->get();

            if ($emp_id_data->isNotEmpty()) {
                $approver_id = $emp_id_data[0]->id;
            } else {
                $approver_id = 0;
            }

            $monetizationRecord = DB::table('leave_monetizations')->where('id', $id)->first();
            if (!$monetizationRecord) {
                return $this->notFoundResponse('Monetization record not found');
            }

            $wasFullyApproved = $this->isMonetizationFullyApproved($monetizationRecord);

            if ($type_id == 1) {
                $approvalLevel = $this->getMonetizationActionLevel($monetizationRecord, false);
                if ($approvalLevel === null) {
                    return $this->successResponse([], 'Monetization is already fully approved.');
                }

                $data = $this->buildMonetizationLevelActionData($approvalLevel, $approver_id, $remarks, true);

                DB::table('leave_monetizations')->where('id', $id)->update($data);

                $updatedRecord = DB::table('leave_monetizations')->where('id', $id)->first();
                $this->applyLeaveCreditsForApprovedMonetizationIfNeeded($updatedRecord);

                Audit::create([
                    'user_id' => Auth::user()->id,
                    'module'  => 'Monetization Leave',
                    'menu'    => 'Monetization',
                    'activity' => 'Approved',
                    'description' => 'Approved Monetization (level ' . $approvalLevel . ').',
                ]);
            } else {
                $disapprovalLevel = $this->getMonetizationActionLevel($monetizationRecord, true);
                if ($disapprovalLevel === null) {
                    return $this->errorResponse('Unable to determine disapproval level for this monetization.', 400);
                }

                $data = $this->buildMonetizationLevelActionData($disapprovalLevel, $approver_id, $remarks, false);

                DB::table('leave_monetizations')->where('id', $id)->update($data);

                if ($wasFullyApproved) {
                    $this->adjustLeaveCredits(
                        $monetizationRecord->employee_id,
                        $monetizationRecord->vl_credit,
                        $monetizationRecord->sl_credit,
                        'add',
                        (int) $id
                    );
                    DB::table('leave_monetizations')->where('id', $id)->update(['credits_applied' => 0]);
                }

                Audit::create([
                    'user_id' => Auth::user()->id,
                    'module'  => 'Monetization Leave',
                    'menu'    => 'Monetization',
                    'activity' => 'Disapproved',
                    'description' => 'Disapproved Monetization.',
                ]);
            }

            return $this->successResponse($data, 'Monetization processed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process monetization: ' . $e->getMessage());
        }
    }

    public function loadMonetizationPayroll()
    {

        $monetization_parolls = DB::table('monetization_payroll_headers as a')
            ->leftJoin('branches as b', 'a.branch_id', '=', 'b.id')
            ->select(
                'a.*',
                'b.name as branch'
            )
            ->get();

        return $this->successResponse($monetization_parolls, 'Monetization payroll list retrieved successfully');
    }

    public function addMonetizationPayroll($id)
    {
        $app_key = env("APP_KEY", "");

        $branches = DB::table('branches')->get();

        if ($id != 0) {
            $data = DB::table('monetization_payroll_headers as a')
                ->leftJoin('monetization_payroll_details as b', 'a.id', '=', 'b.monetization_payroll_id')
                ->leftJoin('employees as c', 'b.employee_id', '=', 'c.id')
                ->leftJoin('positions as d', 'c.position_id', '=', 'd.id')
                ->leftJoin('leave_monetizations as e', 'b.monetization_id', '=', 'e.id')
                ->select(
                    'a.id',
                    'b.id as dtl_id',
                    'a.branch_id',
                    'a.month_id',
                    'a.year_id',
                    'c.employee_no',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                CONCAT(c.first_name,' ',c.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key'))
                            END as name"),
                    'd.name as position',
                    'e.total_days',
                    'e.amount',
                    'a.posted'
                )
                ->where('a.id', $id)
                ->get();
        } else {
            $data = array(
                'id' => 0,
                'dtl_id' => 0,
                'branch_id' => 0,
                'month_id' => 0,
                'year_id' => 0,
                'employee_no' => '',
                'name' => '',
                'position' => '',
                'total_days' => 0,
                'amount' => 0,
                'posted' => false
            );

            $data = (object)$data;
            $data = collect([$data]);
        }

        $monetization_employees = DB::table('employees as a')
            ->leftJoin('plantillas as b', 'a.id', '=', 'b.employee_id')
            ->join('positions as c', 'a.position_id', '=', 'c.id')
            ->join('leave_monetizations as e', 'a.id', '=', 'e.employee_id')
            ->select(
                'a.id',
                'a.employee_no',
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                            END as name"),
                'b.code',
                'c.name as position',
                'b.id as plantilla_id',
                'e.total_days',
                'e.amount',
                'e.id as monetization_id'
            )
            ->where([
                'a.active' => true,
                'a.is_employee' => true,
                'e.approve_1' => true,
                'e.approve_2' => true
            ])
            ->whereRaw("isnull(e.is_processed,0) = 0")
            ->orderBy('a.first_name', 'asc')
            ->get();

        return $this->successResponse([
            'branches' => $branches,
            'data' => $data,
            'monetization_employees' => $monetization_employees
        ], 'Monetization payroll form data loaded successfully');
    }

    public function storeMonetizationPayroll(Request $request, $id)
    {
        $request->validate([
            'branch_id' => 'required',
            'month_id' => 'required',
            'year_id' => 'required'
        ]);

        $monetization_exist = DB::table('monetization_payroll_headers')
            ->where([
                'branch_id' => $request->branch_id,
                'month_id' => $request->month_id,
                'year_id' => $request->year_id
            ])
            ->where('id', '<>', $id)
            ->count();

        if ($monetization_exist > 0) {
            return $this->errorResponse('Existing Monetization Payroll with the same Month and Year under the same Branch!', 400);
        }

        if ($id == 0) {
            $id = DB::table('monetization_payroll_headers')->max('id') + 1;
        }

        if ($request->month_id == 1) {
            $month = 'January';
        } elseif ($request->month_id == 2) {
            $month = 'February';
        } elseif ($request->month_id == 3) {
            $month = 'March';
        } elseif ($request->month_id == 4) {
            $month = 'April';
        } elseif ($request->month_id == 5) {
            $month = 'May';
        } elseif ($request->month_id == 6) {
            $month = 'June';
        } elseif ($request->month_id == 7) {
            $month = 'July';
        } elseif ($request->month_id == 8) {
            $month = 'August';
        } elseif ($request->month_id == 9) {
            $month = 'September';
        } elseif ($request->month_id == 10) {
            $month = 'October';
        } elseif ($request->month_id == 11) {
            $month = 'November';
        } elseif ($request->month_id == 12) {
            $month = 'December';
        }

        $data = array(
            'branch_id' => $request->branch_id,
            'month_id' => $request->month_id,
            'month' => $month,
            'year_id' => $request->year_id
        );

        DB::unprepared('SET IDENTITY_INSERT monetization_payroll_headers ON');
        DB::table('monetization_payroll_headers')->updateOrInsert(['id' => $id], $data);
        DB::unprepared('SET IDENTITY_INSERT monetization_payroll_headers OFF');

        //Save audit trail
        $data_audit = array(
            'user_id' => Auth::user()->id,
            'module'  => 'Payroll Module',
            'menu'    => 'Monetization Payroll',
            'activity' => 'Create',
            'description' => 'Created Monetization Payroll informations.',
        );

        Audit::create($data_audit);

        return $this->successResponse(['id' => $id], 'Successfully Created Monetization Payroll!');
    }

    public function storeMonetizationPayrollEmployee(Request $request, $id)
    {
        $employee_data = $request->all();

        $data = [];

        $monetization_header = DB::table('monetization_payroll_headers')->where('id', $id)->get();

        $month_id = $monetization_header[0]->month_id;
        $year_id = $monetization_header[0]->year_id;
        $monetization_id = 0;

        if (isset($employee_data['monetization_id'])) {
            $arr_len = count($employee_data['monetization_id']);
            for ($i = 0; $i < $arr_len; $i++) {
                if ($employee_data['monetization_id'][$i] != NULL) {
                    if (in_array($employee_data['monetization_id'][$i], $employee_data['select'])) {

                        $monetization_id = $employee_data['monetization_id'][$i];

                        // update monetization
                        DB::table('leave_monetizations')->where('id', $monetization_id)->update(['is_processed' => true]);

                        $data = [
                            'monetization_id' => $monetization_id,
                            'monetization_payroll_id' => $id,
                            'employee_id' => $employee_data['id'][$i]
                        ];

                        DB::table('monetization_payroll_details')->insert($data);
                    }
                }
            }
        }

        $data_audit = array(
            'user_id' => Auth::user()->id,
            'module'  => 'Payroll Module',
            'menu'    => 'Monetization Payroll',
            'activity' => 'Added',
            'description' => 'Added Monetization Employees.',
        );

        Audit::create($data_audit);

        return $this->successResponse(['id' => $id], 'Successfully Added Employees to Monetization Payroll!');
    }

    public function deleteMonetizationPayrollEmployees($id)
    {

        $dtl = DB::table('monetization_payroll_details')->where('id', $id)->get();

        $monetization_id = $dtl[0]->monetization_id;

        // update monetization
        DB::table('leave_monetizations')->where('id', $monetization_id)->update(['is_processed' => false]);

        $data = DB::table('monetization_payroll_details')->where('id', $id)->delete();

        $data_audit = array(
            'user_id' => Auth::user()->id,
            'module'  => 'Payroll Module',
            'menu'    => 'Monetization Payroll',
            'activity' => 'Delete',
            'description' => 'Deleted Monetization Employees.',
        );

        Audit::create($data_audit);

        return json_encode($data);
    }

    public function processMonetizationPayroll($id, $type_id)
    {
        try {
            if ($type_id == 1) {
                // Credit Approved Days to monetize
                $monetization = DB::table('monetization_payroll_details as a')
                    ->join('leave_monetizations as b', 'a.monetization_id', '=', 'b.id')
                    ->select(
                        'b.employee_id',
                        'b.vl_credit',
                        'sl_credit'
                    )
                    ->where('a.monetization_payroll_id', $id)
                    ->get();

                if ($monetization->isEmpty()) {
                    return $this->errorResponse('Unable to approve monetization without employees. Please add employees with monetizations first.');
                }

                DB::table('monetization_payroll_headers')->where('id', $id)->update(['posted' => true]);

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'Monetization Payroll',
                    'activity' => 'Posted',
                    'description' => 'Posted Monetization Payroll.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'Successfully Posted Monetization Payroll!');
            } else {

                // Revert Credit Approved Days to monetize
                $monetization = DB::table('monetization_payroll_details as a')
                    ->join('leave_monetizations as b', 'a.monetization_id', '=', 'b.id')
                    ->select(
                        'b.employee_id',
                        'b.vl_credit',
                        'sl_credit'
                    )
                    ->where('a.monetization_payroll_id', $id)
                    ->get();

                DB::table('monetization_payroll_headers')->where('id', $id)->update(['posted' => false]);

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'Monetization Payroll',
                    'activity' => 'Unposted',
                    'description' => 'Unposted Monetization Payroll.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'Successfully Unposted Monetization Payroll!');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process monetization payroll: ' . $e->getMessage());
        }
    }

    public function monetizationReport()
    {
        $app_key = env("APP_KEY", "");

        $monetization_payroll = DB::table('monetization_payroll_headers as a')
            ->select(
                'a.id',
                DB::raw("CONCAT(a.month,' ',a.year_id,' (', b.name ,')') as name")
            )
            ->join('branches as b', 'a.branch_id', '=', 'b.id')
            ->where('posted', true)
            ->get();

        $signatories = DB::table('employees')
            ->select(
                'id',
                DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                CONCAT(employees.first_name,' ',employees.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key'))
                            END as name")
            )
            ->where([
                'active' => true,
                'is_employee' => true
            ])
            ->get();

        return $this->successResponse([
            'monetization_payroll' => $monetization_payroll,
            'signatories' => $signatories
        ], 'Monetization payroll report data retrieved successfully');
    }

    public function print(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            $signatory_data_1 = DB::table('employees as a')
                ->join('positions as b', 'a.position_id', '=', 'b.id')
                ->select(
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                    CONCAT(a.first_name,' ',a.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                                END as name"),
                    'b.name as position'
                )
                ->where('a.id', $request->signatory_1)
                ->get();

            if ($signatory_data_1->isNotEmpty()) {
                $signatory_1 = $signatory_data_1[0]->name;
                $signatory_position_1 = $signatory_data_1[0]->position;
            } else {
                $signatory_1 = '';
                $signatory_position_1 = '';
            }

            $signatory_data_2 = DB::table('employees as a')
                ->join('positions as b', 'a.position_id', '=', 'b.id')
                ->select(
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                    CONCAT(a.first_name,' ',a.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                                END as name"),
                    'b.name as position'
                )
                ->where('a.id', $request->signatory_2)
                ->get();

            if ($signatory_data_2->isNotEmpty()) {
                $signatory_2 = $signatory_data_2[0]->name;
                $signatory_position_2 = $signatory_data_2[0]->position;
            } else {
                $signatory_2 = '';
                $signatory_position_2 = '';
            }

            $signatory_data_3 = DB::table('employees as a')
                ->join('positions as b', 'a.position_id', '=', 'b.id')
                ->select(
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                    CONCAT(a.first_name,' ',a.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                                END as name"),
                    'b.name as position'
                )
                ->where('a.id', $request->signatory_3)
                ->get();

            if ($signatory_data_3->isNotEmpty()) {
                $signatory_3 = $signatory_data_3[0]->name;
                $signatory_position_3 = $signatory_data_3[0]->position;
            } else {
                $signatory_3 = '';
                $signatory_position_3 = '';
            }

            $signatory_data_4 = DB::table('employees as a')
                ->join('positions as b', 'a.position_id', '=', 'b.id')
                ->select(
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                    CONCAT(a.first_name,' ',a.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                                END as name"),
                    'b.name as position'
                )
                ->where('a.id', $request->signatory_4)
                ->get();

            if ($signatory_data_4->isNotEmpty()) {
                $signatory_4 = $signatory_data_4[0]->name;
                $signatory_position_4 = $signatory_data_4[0]->position;
            } else {
                $signatory_4 = '';
                $signatory_position_4 = '';
            }

            $monetization = DB::table('monetization_payroll_headers')->where('id', $request->payroll_id)->get();

            // save signatories
            $signatories_data = [
                'report_name' => 'monetization_payroll_report',
                'signatory_1' => $signatory_1,
                'signatory_position_1' => $signatory_position_1,
                'signatory_2' => $signatory_2,
                'signatory_position_2' => $signatory_position_2,
                'signatory_3' => $signatory_3,
                'signatory_position_3' => $signatory_position_3,
                'signatory_4' => $signatory_4,
                'signatory_position_4' => $signatory_position_4,
                'payroll_id' => $request->rata_payroll_id,
                'branch_id' => $monetization[0]->branch_id
            ];

            $signatory_exist = DB::table('payroll_signatories')
                ->where([
                    'branch_id' => $monetization[0]->branch_id,
                    'report_name' => 'monetization_payroll_report'
                ])
                ->get();

            if (count($signatory_exist) == 0) {
                DB::table('payroll_signatories')->insert($signatories_data);
            } else {
                DB::table('payroll_signatories')->where('id', $signatory_exist[0]->id)->update($signatories_data);
            }

            $signatories = DB::table('payroll_signatories')->where('id', $signatory_exist[0]->id)->get();

            $company = DB::table('companies')->get();

            $monetization = DB::table('monetization_payroll_headers as a')
                ->join('monetization_payroll_details as b', 'a.id', '=', 'b.monetization_payroll_id')
                ->join('employees as c', 'b.employee_id', '=', 'c.id')
                ->join('leave_monetizations as e', 'b.monetization_id', '=', 'e.id')
                ->select(
                    'a.id',
                    'b.employee_id',
                    'c.employee_no',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                    CONCAT(c.first_name,' ',c.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key'))
                                END as name"),
                    'e.total_days',
                    'e.amount',
                    'a.month',
                    'a.year_id'
                )
                ->where('a.id', $request->payroll_id)
                ->orderBy('name', 'asc')
                ->get();

            $pdf = PDF::loadView('monetizations.monetization_payroll_print', compact('monetization', 'company', 'signatories'))
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('Legal', 'landscape');

            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'monetization_payroll_' . $request->payroll_id . '_' . date('Y-m-d') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate monetization payroll PDF: ' . $e->getMessage());
        }
    }

    private function isTruthyFlag($value): bool
    {
        if ($value === true || $value === 1 || $value === '1') {
            return true;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            return in_array($normalized, ['1', 'true', 'yes'], true);
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        // SQL Server bit via PDO can surface as non-scalar in edge cases
        if (is_bool($value)) {
            return $value;
        }

        return false;
    }

    /**
     * VL monetization policy: at least 15 VL to file; always retain 5 VL after monetization.
     */
    private function validateVlMonetizationCredits(float $vlBalance, float $vlToMonetize): ?string
    {
        if ($vlBalance < self::MIN_VL_BALANCE_TO_APPLY) {
            return 'You must have at least ' . self::MIN_VL_BALANCE_TO_APPLY
                . ' Vacation Leave credits to apply for monetization.';
        }

        if ($vlToMonetize <= 0) {
            return null;
        }

        $maxVlToMonetize = max(0, $vlBalance - self::MIN_VL_BALANCE_TO_RETAIN);
        if ($vlToMonetize > $maxVlToMonetize) {
            return 'You must retain at least ' . self::MIN_VL_BALANCE_TO_RETAIN
                . ' Vacation Leave credits. The maximum VL you can monetize is '
                . number_format($maxVlToMonetize, 3, '.', '') . ' credits.';
        }

        $remainingVl = $vlBalance - $vlToMonetize;
        if ($remainingVl < self::MIN_VL_BALANCE_TO_RETAIN) {
            return 'You must retain at least ' . self::MIN_VL_BALANCE_TO_RETAIN
                . ' Vacation Leave credits after monetization.';
        }

        return null;
    }

    /**
     * Approver header for an employee (monetization uses same setup as leave approvers).
     */
    private function getMonetizationApproverHeader($employeeId)
    {
        if (!$employeeId) {
            return null;
        }

        return DB::table('approver_details as ad')
            ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
            ->where('ad.employee_id', $employeeId)
            ->select(
                'ah.approver_id_1',
                'ah.approver_id_2',
                'ah.approver_id_3',
                'ah.approver_id_4',
                'ah.branch_approver_id_1',
                'ah.division_approver_id_1',
                'ah.section_approver_id_1'
            )
            ->first();
    }

    /**
     * Distinct level-1 approver slots (branch/division/section/approver_id_1 are alternatives).
     */
    private function getMonetizationLevel1ApproverIds($header): array
    {
        if (!$header) {
            return [];
        }

        return array_values(array_unique(array_filter([
            (int) ($header->approver_id_1 ?? 0),
            (int) ($header->branch_approver_id_1 ?? 0),
            (int) ($header->division_approver_id_1 ?? 0),
            (int) ($header->section_approver_id_1 ?? 0),
        ], static fn ($id) => $id > 0)));
    }

    /**
     * Count configured approval levels (1–3). Same person on multiple slots counts once.
     */
    private function countApprovalLevelsFromHeader($header): int
    {
        if (!$header) {
            return 1;
        }

        $level1Ids = $this->getMonetizationLevel1ApproverIds($header);
        $levels = 1;

        $approver2 = (int) ($header->approver_id_2 ?? 0);
        if ($approver2 > 0 && !in_array($approver2, $level1Ids, true)) {
            $levels = 2;
        }

        // Level 3 only when approver_id_3 is set (do not treat approver_id_4 as a 3rd level).
        $approver3 = (int) ($header->approver_id_3 ?? 0);
        if (
            $approver3 > 0
            && !in_array($approver3, $level1Ids, true)
            && $approver3 !== $approver2
        ) {
            $levels = 3;
        }

        return $levels;
    }

    /**
     * Monetization approval levels from approver_headers (1 or 2 only).
     * Uses approver_id_1 slots + approver_id_2 — not approver_id_3/4 (those are for leave, not monetization).
     */
    private function countMonetizationApprovalLevelsFromHeader($header): int
    {
        if (!$header) {
            return 1;
        }

        $level1Ids = $this->getMonetizationLevel1ApproverIds($header);
        $approver2 = (int) ($header->approver_id_2 ?? 0);

        if ($approver2 > 0 && !in_array($approver2, $level1Ids, true)) {
            return 2;
        }

        return 1;
    }

    /**
     * Approver levels configured for leave monetization (1–2).
     */
    private function getMonetizationConfiguredApprovalLevels($employeeId): int
    {
        return $this->countMonetizationApprovalLevelsFromHeader(
            $this->getMonetizationApproverHeader($employeeId)
        );
    }

    /**
     * Required approve_N flags on leave_monetizations (approve_1, approve_2).
     */
    private function getMonetizationMaxApprovalLevel($employeeId): int
    {
        return min(
            $this->getMonetizationConfiguredApprovalLevels($employeeId),
            self::MONETIZATION_TABLE_APPROVAL_LEVELS
        );
    }

    private function getMonetizationTableMaxApprovalLevel(): int
    {
        return Schema::hasColumn('leave_monetizations', 'approve_3')
            ? 3
            : self::MONETIZATION_TABLE_APPROVAL_LEVELS;
    }

    private function isMonetizationLevelApproved($record, int $level): bool
    {
        $field = 'approve_' . $level;

        return $this->isTruthyFlag($record->{$field} ?? false);
    }

    private function isMonetizationLevelDisapproved($record, int $level): bool
    {
        $field = 'disapprove_' . $level;

        return $this->isTruthyFlag($record->{$field} ?? false);
    }

    private function isMonetizationDisapproved($record): bool
    {
        for ($level = 1; $level <= $this->getMonetizationTableMaxApprovalLevel(); $level++) {
            if ($this->isMonetizationLevelDisapproved($record, $level)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Next approval level still pending (1..max).
     */
    private function getNextMonetizationApprovalLevel($record, ?int $maxLevel = null): ?int
    {
        if (!$record) {
            return null;
        }

        $max = $maxLevel ?? $this->getMonetizationMaxApprovalLevel((int) $record->employee_id);
        for ($level = 1; $level <= $max; $level++) {
            if (!$this->isMonetizationLevelApproved($record, $level)) {
                return $level;
            }
        }

        return null;
    }

    /**
     * Level the current approver should act on (approve or disapprove).
     */
    private function getMonetizationActionLevel($record, bool $forDisapprove): ?int
    {
        if ($forDisapprove && $this->isMonetizationFullyApproved($record)) {
            return $this->getMonetizationMaxApprovalLevel((int) $record->employee_id);
        }

        return $this->getNextMonetizationApprovalLevel($record);
    }

    private function buildMonetizationLevelActionData(int $level, int $approverId, string $remarks, bool $approve): array
    {
        if ($approve) {
            return [
                'approve_' . $level . '_id' => $approverId,
                'approve_date_' . $level => now(),
                'approve_' . $level => true,
                'approve_' . $level . '_remarks' => $remarks,
            ];
        }

        return [
            'disapprove_' . $level . '_id' => $approverId,
            'disapprove_date_' . $level => now(),
            'disapprove_' . $level => true,
            'disapprove_' . $level . '_remarks' => $remarks,
        ];
    }

    /**
     * Monetization is fully approved when all configured approver levels have approved.
     */
    private function isMonetizationFullyApproved($record): bool
    {
        if (!$record) {
            return false;
        }

        if ($this->isMonetizationDisapproved($record)) {
            return false;
        }

        $employeeId = (int) ($record->employee_id ?? 0);
        $maxLevel = $this->getMonetizationMaxApprovalLevel($employeeId);

        for ($level = 1; $level <= $maxLevel; $level++) {
            if (!$this->isMonetizationLevelApproved($record, $level)) {
                return false;
            }
        }

        return true;
    }

    private function resolveLeaveTypeIdByNameLike(string $fragment): ?int
    {
        $row = DB::table('leave_types')
            ->where('active', true)
            ->where('name', 'like', '%' . $fragment . '%')
            ->orderBy('id')
            ->first();

        return $row ? (int) $row->id : null;
    }

    private function resolveEmployeeLeaveTypeIds($employeeId): array
    {
        $defaults = ['vl' => 16, 'sl' => 3];
        if (!$employeeId) {
            return $defaults;
        }

        $credits = DB::table('leave_credits as lc')
            ->join('leave_types as lt', 'lt.id', '=', 'lc.leave_type_id')
            ->select('lc.leave_type_id', 'lt.name')
            ->where('lc.employee_id', $employeeId)
            ->where('lt.active', true)
            ->get();

        $vlTypeId = null;
        $slTypeId = null;

        foreach ($credits as $credit) {
            $name = strtolower((string) ($credit->name ?? ''));
            $typeId = (int) $credit->leave_type_id;

            if (!$vlTypeId && (str_contains($name, 'vacation') || str_contains($name, ' vl'))) {
                $vlTypeId = $typeId;
            }

            if (!$slTypeId && (str_contains($name, 'sick') || str_contains($name, ' sl'))) {
                $slTypeId = $typeId;
            }
        }

        return [
            'vl' => $vlTypeId ?? $this->resolveLeaveTypeIdByNameLike('vacation') ?? $defaults['vl'],
            'sl' => $slTypeId ?? $this->resolveLeaveTypeIdByNameLike('sick') ?? $defaults['sl'],
        ];
    }

    private function decorateMonetizationRecord($record, $employeeId): void
    {
        if (!$record) {
            return;
        }

        $record->approve_1 = $this->isTruthyFlag($record->approve_1 ?? false);
        $record->approve_2 = $this->isTruthyFlag($record->approve_2 ?? false);
        $record->disapprove_1 = $this->isTruthyFlag($record->disapprove_1 ?? false);
        $record->disapprove_2 = $this->isTruthyFlag($record->disapprove_2 ?? false);
        if (Schema::hasColumn('leave_monetizations', 'approve_3')) {
            $record->approve_3 = $this->isTruthyFlag($record->approve_3 ?? false);
            $record->disapprove_3 = $this->isTruthyFlag($record->disapprove_3 ?? false);
        }
        $applicantEmployeeId = (int) ($record->employee_id ?? $employeeId);
        $maxLevels = $this->getMonetizationMaxApprovalLevel($applicantEmployeeId);
        $record->configured_approval_levels = $this->getMonetizationConfiguredApprovalLevels($applicantEmployeeId);
        $record->required_approval_levels = $maxLevels;
        $record->requires_second_approval = $maxLevels > 1;
        $record->is_fully_approved = $this->isMonetizationFullyApproved($record);
    }

    /**
     * Deduct leave credits for a fully-approved monetization request.
     * Uses a DB transaction with a direct name-based lookup to reliably find
     * the leave_credits row regardless of leave_type_id configuration.
     * The credits_applied flag prevents double-deduction.
     */
    private function applyLeaveCreditsForApprovedMonetizationIfNeeded($record): void
    {
        if (!$record) {
            return;
        }

        if (!$this->isMonetizationFullyApproved($record)) {
            $applicantId = (int) ($record->employee_id ?? 0);
            Log::info('Monetization: not fully approved yet, skipping credit deduction.', [
                'monetization_id'          => $record->id ?? null,
                'employee_id'              => $applicantId,
                'approve_1'                => $record->approve_1 ?? null,
                'approve_2'                => $record->approve_2 ?? null,
                'required_approval_levels' => $this->getMonetizationMaxApprovalLevel($applicantId),
            ]);
            return;
        }

        Log::info('Monetization: fully approved, applying leave credit deduction.', [
            'monetization_id' => $record->id ?? null,
            'employee_id'     => $record->employee_id ?? null,
            'vl_credit'       => $record->vl_credit ?? null,
            'sl_credit'       => $record->sl_credit ?? null,
            'type_id'         => $record->type_id ?? null,
        ]);

        $monetizationId = (int) ($record->id ?? 0);
        $employeeId    = (int) ($record->employee_id ?? 0);
        $vlAmount      = (float) ($record->vl_credit ?? 0);
        $slAmount      = (float) ($record->sl_credit ?? 0);

        if ($monetizationId <= 0 || $employeeId <= 0 || ($vlAmount <= 0 && $slAmount <= 0)) {
            Log::warning('Monetization: skipping deduction — invalid data', [
                'monetization_id' => $monetizationId,
                'employee_id'     => $employeeId,
                'vl_amount'       => $vlAmount,
                'sl_amount'       => $slAmount,
            ]);
            return;
        }

        try {
            DB::transaction(function () use ($monetizationId, $employeeId, $vlAmount, $slAmount) {
                $current = DB::table('leave_monetizations')
                    ->where('id', $monetizationId)
                    ->first();

                if (!$current) {
                    return;
                }

                if ($this->isTruthyFlag($current->credits_applied ?? false)) {
                    Log::info('Monetization: credits already applied, skipping.', [
                        'monetization_id' => $monetizationId,
                        'employee_id'     => $employeeId,
                    ]);
                    return;
                }

                $allOk = true;

                if ($vlAmount > 0) {
                    $ok = $this->deductEmployeeLeaveCredit($employeeId, $vlAmount, 'vl', $monetizationId);
                    if (!$ok) {
                        $allOk = false;
                    }
                }

                if ($slAmount > 0) {
                    $ok = $this->deductEmployeeLeaveCredit($employeeId, $slAmount, 'sl', $monetizationId);
                    if (!$ok) {
                        $allOk = false;
                    }
                }

                if ($allOk) {
                    DB::table('leave_monetizations')
                        ->where('id', $monetizationId)
                        ->update(['credits_applied' => 1]);

                    Log::info('Monetization: leave credits deducted and credits_applied set.', [
                        'monetization_id' => $monetizationId,
                        'employee_id'     => $employeeId,
                        'vl_deducted'     => $vlAmount,
                        'sl_deducted'     => $slAmount,
                    ]);
                } else {
                    DB::table('leave_monetizations')
                        ->where('id', $monetizationId)
                        ->update(['credits_applied' => 0]);

                    Log::error('Monetization: deduction failed — credits_applied left at 0.', [
                        'monetization_id' => $monetizationId,
                        'employee_id'     => $employeeId,
                        'vl_amount'       => $vlAmount,
                        'sl_amount'       => $slAmount,
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::error('Monetization: transaction error during credit deduction.', [
                'monetization_id' => $monetizationId,
                'employee_id'     => $employeeId,
                'error'           => $e->getMessage(),
            ]);
        }
    }

    private function findLeaveCreditRow($employeeId, int $primaryTypeId, string $kind = 'vl')
    {
        $fallbacks = $kind === 'sl' ? [3, 2] : [16, 1];
        $typeIds = array_values(array_unique(array_filter(
            array_merge([$primaryTypeId], $fallbacks),
            static fn ($id) => (int) $id > 0
        )));

        foreach ($typeIds as $typeId) {
            $credit = DB::table('leave_credits')
                ->select('id', 'credits', 'leave_type_id')
                ->where('employee_id', $employeeId)
                ->where('leave_type_id', $typeId)
                ->first();

            if ($credit) {
                return $credit;
            }
        }

        return null;
    }

    private function deductEmployeeLeaveCredit(int $employeeId, float $amount, string $kind, int $monetizationId): bool
    {
        $typeIds = $this->resolveEmployeeLeaveTypeIds($employeeId);
        $leaveTypeId = $kind === 'sl' ? $typeIds['sl'] : $typeIds['vl'];
        $credit = $this->findLeaveCreditRow($employeeId, (int) $leaveTypeId, $kind);

        if (!$credit) {
            Log::error('Monetization: leave_credits row not found for deduction.', [
                'monetization_id' => $monetizationId,
                'employee_id'     => $employeeId,
                'kind'            => $kind,
                'leave_type_id'   => $leaveTypeId,
            ]);
            return false;
        }

        $oldCredits = (float) $credit->credits;
        $newCredits = max(0.0, $oldCredits - $amount);

        $affected = DB::table('leave_credits')
            ->where('id', $credit->id)
            ->update(['credits' => $newCredits]);

        if (!$affected) {
            Log::error('Monetization: DB update affected 0 rows.', [
                'monetization_id'  => $monetizationId,
                'leave_credits_id' => $credit->id,
                'employee_id'      => $employeeId,
                'kind'             => $kind,
                'old_credits'      => $oldCredits,
                'new_credits'      => $newCredits,
            ]);
            return false;
        }

        Log::info('Monetization: leave credit deducted.', [
            'monetization_id'  => $monetizationId,
            'leave_credits_id' => $credit->id,
            'employee_id'      => $employeeId,
            'leave_type_id'    => $credit->leave_type_id,
            'kind'             => $kind,
            'old_credits'      => $oldCredits,
            'amount_deducted'  => $amount,
            'new_credits'      => $newCredits,
        ]);

        return true;
    }

    private function restoreEmployeeLeaveCredit(int $employeeId, float $amount, string $kind, int $monetizationId): bool
    {
        $typeIds = $this->resolveEmployeeLeaveTypeIds($employeeId);
        $leaveTypeId = $kind === 'sl' ? $typeIds['sl'] : $typeIds['vl'];
        $credit = $this->findLeaveCreditRow($employeeId, (int) $leaveTypeId, $kind);

        if (!$credit) {
            Log::error('Monetization: leave_credits row not found for restore.', [
                'monetization_id' => $monetizationId,
                'employee_id'     => $employeeId,
                'kind'            => $kind,
                'leave_type_id'   => $leaveTypeId,
            ]);
            return false;
        }

        $oldCredits = (float) $credit->credits;
        $newCredits = $oldCredits + $amount;

        DB::table('leave_credits')
            ->where('id', $credit->id)
            ->update(['credits' => $newCredits]);

        Log::info('Monetization: leave credit restored.', [
            'monetization_id'  => $monetizationId,
            'leave_credits_id' => $credit->id,
            'employee_id'      => $employeeId,
            'kind'             => $kind,
            'old_credits'      => $oldCredits,
            'amount_restored'  => $amount,
            'new_credits'      => $newCredits,
        ]);

        return true;
    }

    private function adjustLeaveCredits($employeeId, $vlAmount = 0, $slAmount = 0, $operation = 'subtract', $monetizationId = 0): bool
    {
        if (!$employeeId) {
            return false;
        }

        $success = true;
        $monetizationId = (int) $monetizationId;

        if ((float) $vlAmount > 0) {
            if ($operation === 'subtract') {
                $ok = $this->deductEmployeeLeaveCredit((int) $employeeId, (float) $vlAmount, 'vl', $monetizationId);
            } else {
                $ok = $this->restoreEmployeeLeaveCredit((int) $employeeId, (float) $vlAmount, 'vl', $monetizationId);
            }
            $success = $ok && $success;
        }

        if ((float) $slAmount > 0) {
            if ($operation === 'subtract') {
                $ok = $this->deductEmployeeLeaveCredit((int) $employeeId, (float) $slAmount, 'sl', $monetizationId);
            } else {
                $ok = $this->restoreEmployeeLeaveCredit((int) $employeeId, (float) $slAmount, 'sl', $monetizationId);
            }
            $success = $ok && $success;
        }

        if ((float) $vlAmount <= 0 && (float) $slAmount <= 0) {
            return false;
        }

        return $success;
    }

    public function loadAttachments($id)
    {
        try {
            $leave_attachment_data = DB::table('leave_monetization_attachments')
                ->where('leave_monetization_id', $id)
                ->orderBy('id', 'asc')
                ->get();

            return $this->successResponse($leave_attachment_data, 'Monetization attachments loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load monetization attachments: ' . $e->getMessage());
        }
    }

    public function deleteAttachments($id)
    {
        DB::table('leave_monetization_attachments')
            ->where('id', $id)
            ->delete();

        return json_encode('success');
    }

    public function download($id)
    {
        try {
            // Check if user is authenticated
            if (!Auth::check()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $documents = DB::table('leave_monetization_attachments as a')
                ->select(
                    'a.id',
                    'a.leave_monetization_id',
                    'a.attachment'
                )
                ->where('a.id', $id)
                ->get();

            if ($documents->isEmpty()) {
                return response()->json(['error' => 'Attachment not found'], 404);
            }

            $pathToFile = storage_path('app/leave_monetization_attachments/' . 'LM' . '_' . $documents[0]->leave_monetization_id . '_' . $documents[0]->attachment);

            if (!file_exists($pathToFile)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            return response()->download($pathToFile);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to download file: ' . $e->getMessage()], 500);
        }
    }
}
