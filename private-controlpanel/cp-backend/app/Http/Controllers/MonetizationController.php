<?php

namespace App\Http\Controllers;

use PDF;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MonetizationController extends Controller
{
    use ApiResponse;

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

        $emp_id_data = DB::table('users as a')
            ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
            ->leftJoin('departments as c', 'b.id', '=', 'c.employee_id')
            ->selectRaw('case when b.id = null then 0 else b.id end as id')
            ->selectRaw('case when c.employee_id = null then 0 else c.employee_id end as supervisor_id')
            ->where('a.id', $id)
            ->get();

        if ($emp_id_data->isNotEmpty()) {
            $emp_id = $emp_id_data[0]->id;
            $supervisor_id = 0;

            $leave_balances = DB::table('leave_credits as a')
                ->join('leave_types as b', 'b.id', '=', 'a.leave_type_id')
                ->select('a.leave_type_id', 'b.name as type', 'a.credits as balance')
                ->where('a.employee_id', $emp_id)
                ->whereIn('a.leave_type_id', [16, 3])
                ->get();

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
            $emp_id = 0;
            $supervisor_id = 0;

            $leave_balances = DB::table('leave_credits as a')
                ->join('leave_types as b', 'b.id', '=', 'a.leave_type_id')
                ->select('a.leave_type_id', 'b.name as type', 'a.credits as balance')
                ->where('a.employee_id', $emp_id)
                ->whereIn('a.leave_type_id', [16, 3])
                ->get();

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
                ->unionAll($leave_for_approvals_1)
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

        // Load Attachments for each monetization
        foreach ($leave_monetization as $monetization) {
            $attachments = DB::table('leave_monetization_attachments')
                ->where('leave_monetization_id', $monetization->id)
                ->get();

            $monetization->attachments = $attachments;
        }

        // Load Attachments for approval items
        foreach ($leave_for_approvals as $approval) {
            $attachments = DB::table('leave_monetization_attachments')
                ->where('leave_monetization_id', $approval->id)
                ->get();

            $approval->attachments = $attachments;
        }

        return $this->successResponse([
            'emp_id' => $emp_id,
            'supervisor_id' => $supervisor_id,
            'leave_balances' => $leave_balances,
            'leave_monetization' => $leave_monetization,
            'leave_for_approvals' => $leave_for_approvals,
            'allowed' => $allowed
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

            if ($data["type_id"] == 1) {
                if ($data["vl_credit"] <= 0) {
                    return $this->errorResponse('No credit amount to monetize');
                }

                if ($data["vl_to_monetize"] <= 0 || $data["vl_to_monetize"] < 10) {
                    return $this->errorResponse('Minimum credit amount to monetize is 10.');
                } elseif ($data["vl_to_monetize"] > $data["vl_credit"]) {
                    return $this->errorResponse('Invalid VL Credits to monetize. Monetized Leave amount must be less than VL Credits.');
                } elseif ($data["vl_to_monetize"] > 50) {
                    return $this->errorResponse('Invalid VL Credits to monetize. Leave to monetize must only be maximum to 50 credits only.');
                } elseif (($data["vl_credit"] - $data["vl_to_monetize"]) < 5) {
                    return $this->errorResponse('Invalid VL Credits to monetize. Must retain atleast 5 leave credits.');
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

            if ($data["vl_credit"] <= 5 && $data["vl_to_monetize"] >= 5) {
                return $this->errorResponse('You must retain atleast 5 VL Credits. Use SL Credit instead to continue application.');
            } elseif ($data["vl_to_monetize"] > $data["vl_credit"]) {
                return $this->errorResponse('Invalid VL Credits to monetize.');
            } elseif ($data["sl_to_monetize"] > $data["sl_credit"]) {
                return $this->errorResponse('Invalid SL Credits to monetize.');
            } elseif (($data["vl_credit"] - $data["vl_to_monetize"]) < 5) {
                return $this->errorResponse('Invalid VL Credits to monetize. Must retain atleast 5 leave credits.');
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
                    'type_id' => $data["type_id"],
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
                    'type_id' => $data["type_id"],
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

            if ($type_id == 1) {

                $leave_monetization_data = DB::table('leave_monetizations')
                    ->where([
                        'id' => $id,
                        'approve_1' => true
                    ])
                    ->get();

                if ($leave_monetization_data->isNotEmpty()) {
                    $data = array(
                        'approve_2_id' => $approver_id,
                        'approve_date_2' => now(),
                        'approve_2' => true,
                        'approve_2_remarks' => $remarks
                    );
                } else {
                    $data = array(
                        'approve_1_id' => $approver_id,
                        'approve_date_1' => now(),
                        'approve_1' => true,
                        'approve_1_remarks' => $remarks
                    );
                }

                DB::table('leave_monetizations')->where('id', $id)->update($data);

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Monetization Leave',
                    'menu'    => 'Monetization',
                    'activity' => 'Approved',
                    'description' => 'Approved Monetization.',
                );

                //Save audit trail
                Audit::create($data_audit);
            } else {

                $leave_monetization_data = DB::table('leave_monetizations')
                    ->where([
                        'id' => $id,
                        'approve_1' => true
                    ])
                    ->get();

                if ($leave_monetization_data->isNotEmpty()) {
                    $data = array(
                        'disapprove_2_id' => $approver_id,
                        'disapprove_date_2' => now(),
                        'disapprove_2' => true,
                        'disapprove_2_remarks' => $remarks
                    );
                } else {
                    $data = array(
                        'disapprove_1_id' => $approver_id,
                        'disapprove_date_1' => now(),
                        'disapprove_1' => true,
                        'disapprove_1_remarks' => $remarks
                    );
                }

                DB::table('leave_monetizations')->where('id', $id)->update($data);

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Monetization Leave',
                    'menu'    => 'Monetization',
                    'activity' => 'Disapproved',
                    'description' => 'Disapproved Monetization.',
                );

                //Save audit trail
                Audit::create($data_audit);
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
            'description' => 'Created Monetization Payroll information',
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

                foreach ($monetization as $mon) {
                    if ($mon->vl_credit > 0) {
                        DB::table('leave_credits')->where([
                            'employee_id' => $mon->employee_id,
                            'leave_type_id' => 16
                        ])
                            ->update([
                                'credits' => DB::raw("credits - $mon->vl_credit")
                            ]);
                    }

                    if ($mon->sl_credit > 0) {
                        DB::table('leave_credits')->where([
                            'employee_id' => $mon->employee_id,
                            'leave_type_id' => 3
                        ])
                            ->update([
                                'credits' =>  DB::raw("credits - $mon->sl_credit")
                            ]);
                    }
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

                foreach ($monetization as $mon) {
                    if ($mon->vl_credit > 0) {
                        DB::table('leave_credits')->where([
                            'employee_id' => $mon->employee_id,
                            'leave_type_id' => 16
                        ])
                            ->update([
                                'credits' => DB::raw("credits + $mon->vl_credit")
                            ]);
                    }

                    if ($mon->sl_credit > 0) {
                        DB::table('leave_credits')->where([
                            'employee_id' => $mon->employee_id,
                            'leave_type_id' => 3
                        ])
                            ->update([
                                'credits' => DB::raw("credits + $mon->sl_credit")
                            ]);
                    }
                }

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
