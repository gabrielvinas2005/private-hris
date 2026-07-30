<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StepIncrementApprovalController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * List forwarded and pending step increments (employee-level rows)
     */
    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");

            $rows = DB::table('step_increments as s')
                ->join('employees as a', 's.employee_id', '=', 'a.id')
                ->leftJoin('months as m', 's.month_id', '=', 'm.id')
                ->leftJoin('salary_grades as f', 's.current_salary_grade_id', '=', 'f.id')
                ->leftJoin('salary_steps as g', 's.current_salary_step_id', '=', 'g.id')
                ->leftJoin('salary_grades as ff', 's.new_salary_grade_id', '=', 'ff.id')
                ->leftJoin('salary_steps as gg', 's.new_salary_step_id', '=', 'gg.id')
                ->select(
                    's.id',
                    'a.id as employee_id',
                    'a.photo',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                             ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                             END as name"),
                    's.effectivity_date',
                    's.forwarded_date',
                    's.month_id',
                    's.year_id',
                    'm.name as month',
                    DB::raw('ISNULL(s.is_forwarded, 0) as is_forwarded'),
                    DB::raw('ISNULL(s.is_approved, 0) as is_approved'),
                    DB::raw('ISNULL(s.is_disapproved, 0) as is_disapproved'),
                    // Current salary information from step_increments table
                    's.current_salary_grade_id',
                    's.current_salary_step_id',
                    'f.name as current_salary_grade',
                    'g.name as current_salary_step',
                    's.current_salary',
                    // New salary information from step_increments table
                    's.new_salary_grade_id',
                    's.new_salary_step_id',
                    'ff.name as new_salary_grade',
                    'gg.name as new_salary_step',
                    's.new_salary',
                    // Current deduction amounts from employees table
                    'a.tax_amount as current_tax_amount',
                    'a.gsis_amount as current_gsis_amount',
                    'a.sss_amount as current_sss_amount',
                    'a.pagibig_amount as current_pagibig_amount',
                    'a.philhealth_amount as current_philhealth_amount',
                    // New deduction amounts from step_increments table
                    's.new_tax_amount',
                    's.new_gsis_amount',
                    's.new_sss_amount',
                    's.new_pagibig_amount',
                    's.new_philhealth_amount'
                )
                ->whereRaw('ISNULL(s.is_forwarded, 0) = 1')
                ->whereRaw('ISNULL(s.is_approved, 0) = 0')
                ->whereRaw('ISNULL(s.is_disapproved, 0) = 0')
                ->orderBy('s.forwarded_date', 'desc')
                ->get();

            return $this->successResponse($rows, 'Pending step increment approvals retrieved successfully');
        } catch (\Exception $e) {
            Log::error('StepIncrementApprovalController@index - Error occurred', [
                'message' => $e->getMessage()
            ]);
            return $this->serverErrorResponse('Failed to retrieve approvals: ' . $e->getMessage());
        }
    }

    /** Approve single step increment */
    public function approve($id)
    {
        try {
            $updated = DB::table('step_increments')->where('id', $id)->update([
                'is_approved' => true,
                'approved_date' => now(),
                'approved_by_id' => Auth::user()->id
            ]);

            if (!$updated) {
                return $this->notFoundResponse('Step increment not found');
            }

            return $this->successResponse(null, 'Step increment approved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to approve step increment: ' . $e->getMessage());
        }
    }

    /** Reject single step increment */
    public function reject($id)
    {
        try {
            $updated = DB::table('step_increments')->where('id', $id)->update([
                'is_disapproved' => true
            ]);

            if (!$updated) {
                return $this->notFoundResponse('Step increment not found');
            }

            return $this->successResponse(null, 'Step increment rejected successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to reject step increment: ' . $e->getMessage());
        }
    }
}
