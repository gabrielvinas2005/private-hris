<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use Carbon\Carbon;
use App\Traits\ApiResponse;
use App\Traits\GeneratesPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeTrainingController extends Controller
{
    use ApiResponse, GeneratesPdf;
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        try {
            $positions = DB::table('positions')->where('active', true)->get();
            $competencies = DB::table('competencies')->where('active', true)->get();

            return $this->successResponse([
                'positions' => $positions,
                'competencies' => $competencies
            ], 'Employee training data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee training data: ' . $e->getMessage());
        }
    }
    
    public function submittraining($id, $subcompetency_id)
    {
        try {
            if ($subcompetency_id == 0) {
                $returnd = DB::table('employee_competencies')
                    ->where('employee_id', $id)
                    ->update([
                        'is_submitted' => 1
                    ]);
            } else {
                DB::table('employee_competencies')
                    ->where([
                        'id' => $id,
                        'subcompetency_id' => $subcompetency_id
                    ])
                    ->update([
                        'is_submitted' => 1
                    ]);
            }
            
            return $this->successResponse([
                'employee_id' => $id,
                'subcompetency_id' => $subcompetency_id,
                'action' => 'submitted'
            ], 'Training request submitted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to submit training request: ' . $e->getMessage());
        }
    }
    
    public function canceltraining($id, $subcompetency_id)
    {
        try {
            if ($subcompetency_id == 0) {
                DB::table('employee_competencies')
                    ->where([
                        'employee_id' => $id
                    ])
                    ->update([
                        'is_submitted' => 0,
                    ]);
            } else {
                DB::table('employee_competencies')
                    ->where([
                        'id' => $id,
                        'subcompetency_id' => $subcompetency_id
                    ])
                    ->update([
                        'is_submitted' => 0,
                    ]);
            }
            
            return $this->successResponse([
                'employee_id' => $id,
                'subcompetency_id' => $subcompetency_id,
                'action' => 'cancelled'
            ], 'Training request cancelled successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to cancel training request: ' . $e->getMessage());
        }
    }
}
