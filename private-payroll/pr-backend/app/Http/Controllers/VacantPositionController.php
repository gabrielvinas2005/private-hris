<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class VacantPositionController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $plantillas  = DB::table('plantillas as a')
                ->join('positions as b', 'b.id', '=', 'a.position_id')
                ->join('salary_steps as c', 'c.id', '=', 'a.salary_step_id')
                ->join('salary_grades as d', 'd.id', '=', 'a.salary_grade_id')
                ->join('departments as e', 'e.id', '=', 'a.department_id')
                ->select(
                    'a.id',
                    'a.code',
                    'b.name as position',
                    'c.name as step',
                    'd.name as grade',
                    'e.name as department',
                    'a.eligibility as eligibility',
                    'a.experience as experience',
                    'a.training as training',
                    'a.education as education',
                    'a.unit as unit',
                    'a.publication_from as publication_from',
                    'a.publication_to as publication_to',
                    'a.status as status',
                    'a.active',
                    'a.approved',
                    'a.approved_by',
                    'a.approved_date',
                    'a.disapproved',
                    'a.disapproved_by',
                    'a.disapproved_date',
                    'a.cancelled',
                    'a.cancelled_by',
                    'a.cancelled_date'
                )
                ->where('a.employee_id', '=', 0)
                ->where('a.active', 1)
                ->orderBy('b.name', 'asc')
                ->get();

            return $this->successResponse($plantillas, 'Vacant positions data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve vacant positions data: ' . $e->getMessage());
        }
    }

    public function details($id)
    {
        try {
            $plantilla = DB::table('plantillas')->where('id', $id)->first();
            
            if (!$plantilla) {
                return $this->notFoundResponse('Plantilla not found');
            }

            $position = DB::table('positions')->where('active', 1)->orderBy('name', 'desc')->get();
            $step = DB::table('salary_steps')->where('active', 1)->orderBy('id', 'asc')->get();
            $grade = DB::table('salary_grades')->where('active', 1)->orderBy('id', 'asc')->get();
            $department = DB::table('departments')->where('active', 1)->orderBy('id', 'asc')->get();

            $plantilla_data = DB::table('plantillas')
                ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                ->leftJoin('departments', 'departments.id', '=', 'plantillas.department_id')
                ->select(
                    'plantillas.id',
                    'plantillas.employee_id',
                    'plantillas.code',
                    'positions.id as position_id',
                    'salary_steps.id as step_id',
                    'salary_grades.id as grade_id',
                    'departments.id as department_id',
                    'plantillas.eligibility as eligibility',
                    'plantillas.experience as experience',
                    'plantillas.training as training',
                    'plantillas.education as education',
                    'plantillas.unit as unit',
                    'plantillas.publication_from as publication_from',
                    'plantillas.publication_to as publication_to',
                    'plantillas.status as status',
                    'plantillas.active'
                )
                ->where('plantillas.id', $id)
                ->first();

            $educations = DB::table('plantilla_education')->where('plantilla_id', $id)->get();
            $employments = DB::table('plantilla_work_experience')->where('plantilla_id', $id)->get();
            $examinations = DB::table('plantilla_eligibility')->where('plantilla_id', $id)->get();
            $trainings = DB::table('plantilla_trainings')->where('plantilla_id', $id)->get();
            $remarks = DB::table('plantilla_remarks')->where('plantilla_id', $id)->get();
            $eligibilities = DB::table('eligibilities')->where('active', true)->orderBy('name', 'asc')->get();

            $competency = DB::table('competencies as a')
                ->leftjoin('subcompetencies as b', 'a.id', '=', 'b.competency_id')
                ->leftjoin('plantilla_competencies as c', function ($join) use ($id) {
                    $join->on('b.id', '=', 'c.subcompetency_id');
                    $join->on('c.plantilla_id', '=', DB::raw("'" . $id . "'"));
                })
                ->select(
                    'a.id',
                    'a.name',
                    'b.id as subcompetency_id',
                    'b.name as subcompetency_name',
                    'b.description as subcompetency_description',
                    'b.code as subcompetency_code',
                    'c.level'
                )
                ->selectRaw('case when c.subcompetency_id = b.id then 1 else 0 end as assign')
                ->get();

            $grouped_arr = [];

            foreach ($competency as $element) {
                $elemName = $element->name;

                if (!isset($grouped_arr[$elemName])) {
                    $grouped_arr[$elemName] = [];
                }

                array_push($grouped_arr[$elemName], $element);
            }

            return $this->successResponse([
                'plantilla' => $plantilla_data,
                'position' => $position,
                'step' => $step,
                'grade' => $grade,
                'department' => $department,
                'educations' => $educations,
                'employments' => $employments,
                'examinations' => $examinations,
                'trainings' => $trainings,
                'eligibilities' => $eligibilities,
                'remarks' => $remarks,
                'competency' => $competency,
                'grouped_arr' => $grouped_arr
            ], 'Vacant position details retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve vacant position details: ' . $e->getMessage());
        }
    }

    public function process($id, $process_id)
    {
        try {
            $plantilla = DB::table('plantillas')->where('id', $id)->first();
            
            if (!$plantilla) {
                return $this->notFoundResponse('Plantilla not found');
            }

            if ($process_id == 1) {
                $data = [
                    'approved' => true,
                    'approved_by' => Auth::user()->id,
                    'approved_date' => now()
                ];

                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Human Resource Module',
                    'menu'    => 'Vacant Position Posting',
                    'activity' => 'Approved',
                    'description' => 'Approved vacant position posting.',
                );

                Audit::create($data_audit);
            } elseif ($process_id == 2) {
                $data = [
                    'disapproved' => true,
                    'disapproved_by' => Auth::user()->id,
                    'disapproved_date' => now()
                ];

                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Human Resource Module',
                    'menu'    => 'Vacant Position Posting',
                    'activity' => 'Disapproved',
                    'description' => 'Disapproved vacant position posting.',
                );

                Audit::create($data_audit);
            } else {
                $data = [
                    'cancelled' => true,
                    'cancelled_by' => Auth::user()->id,
                    'cancelled_date' => now()
                ];

                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Human Resource Module',
                    'menu'    => 'Vacant Position Posting',
                    'activity' => 'Cancelled',
                    'description' => 'Cancelled vacant position posting.',
                );

                Audit::create($data_audit);
            }

            DB::table('plantillas')->where('id', $id)->update($data);

            return $this->successResponse(null, 'Vacant position processed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process vacant position: ' . $e->getMessage());
        }
    }
}
