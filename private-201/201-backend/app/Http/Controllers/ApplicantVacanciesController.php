<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Plantilla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class ApplicantVacanciesController extends Controller
{
    use ApiResponse;

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function index()
    {
        try {
            $data  = DB::table('plantillas')
                ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                ->leftJoin('departments', 'departments.id', '=', 'plantillas.department_id')
                ->leftJoin('employees', 'employees.position_applied_id', '=', 'plantillas.id')
                ->select('plantillas.id', 'plantillas.code', 'positions.name as position', 'salary_steps.name as step', 'salary_grades.name as grade', 'departments.name as department', 'plantillas.eligibility as eligibility', 'plantillas.experience as experience', 'plantillas.training as training', 'plantillas.education as education', 'plantillas.unit as unit', 'plantillas.publication_from as publication_from', 'plantillas.publication_to as publication_to', 'plantillas.status as status', 'plantillas.active', DB::raw('count(employees.id) as total'))
                ->where('plantillas.employee_id', 0)
                ->where('plantillas.publication_to', '>=', now())
                ->where('plantillas.active', 1)
                ->groupBy('plantillas.id', 'plantillas.code', 'positions.name', 'salary_steps.name', 'salary_grades.name', 'departments.name', 'plantillas.eligibility', 'plantillas.experience', 'plantillas.training', 'plantillas.education', 'plantillas.unit', 'plantillas.publication_from', 'plantillas.publication_to', 'plantillas.status', 'plantillas.active')
                ->orderBy('plantillas.code', 'asc')
                ->get();

            $applicant_count = DB::table('employees')->select('position_applied_id', DB::raw('count(*) as total'))
                ->where('is_employee', 0)
                ->where('active', 1)
                ->groupBy('position_applied_id')
                ->get();

            return $this->successResponse([
                'vacancies' => $data,
                'applicant_count' => $applicant_count
            ], 'Applicant vacancies retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve applicant vacancies: ' . $e->getMessage());
        }
    }

    public function changestatus(Request $request, $id, $status_id)
    {
        try {
            $plantilla = DB::table('plantillas')->where('id', $id)->first();
            
            if (!$plantilla) {
                return $this->notFoundResponse('Plantilla not found');
            }

            $plantilla_id = $id;
            $data_plantilla = [];

            if ($status_id == 1) {
                $data_plantilla = array(
                    'status' => "On-going",
                    'active' => true
                );
            } elseif ($status_id == 2) {
                $data_plantilla = array(
                    'status' => "Complete",
                    'active' => false
                );
            } elseif ($status_id == 3) {
                $data_plantilla = array(
                    'status' => "Expired",
                    'active' => false
                );
            } else {
                return $this->errorResponse('Invalid status ID provided', 400);
            }

            // Update plantilla status
            DB::table('plantillas')->where('id', $plantilla_id)->update($data_plantilla);

            // Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'Applicant Vacancies',
                'activity' => 'Update status of plantilla',
                'description' => 'Updated plantilla status to ' . $data_plantilla['status'] . ' for plantilla ID: ' . $plantilla_id,
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'plantilla_id' => $plantilla_id,
                'status_id' => $status_id,
                'new_status' => $data_plantilla['status'],
                'active' => $data_plantilla['active'],
                'action' => 'status_updated'
            ], 'Plantilla status updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update plantilla status: ' . $e->getMessage());
        }
    }
}
