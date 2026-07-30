<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class VacanciesController extends Controller
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
            $data = DB::table('plantillas')
                ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                ->join('departments', 'departments.id', '=', 'plantillas.department_id')
                ->leftJoin('plantilla_remarks', 'plantillas.id', '=', 'plantilla_remarks.plantilla_id') // Join remarks
                ->select(
                    'plantillas.id',
                    'plantillas.code',
                    'positions.name as position',
                    'salary_steps.name as step',
                    'salary_grades.name as grade',
                    'departments.name as department',
                    'plantillas.eligibility',
                    'plantillas.experience',
                    'plantillas.training',
                    'plantillas.education',
                    'plantillas.unit',
                    'plantillas.publication_from',
                    'plantillas.publication_to',
                    'plantillas.status',
                    'plantillas.active',
                    DB::raw("STRING_AGG(plantilla_remarks.requirement, '|') as remarks") // Use STRING_AGG
                )
                ->where('plantillas.employee_id', '=', 0)
                ->where('plantillas.publication_from', '<=', now())
                ->where('plantillas.publication_to', '>=', now())
                ->whereRaw(
                    "isnull(plantillas.active,0) = 1 and
            isnull(plantillas.approved,0) = 1 and
            isnull(plantillas.cancelled,0) = 0"
                )
                ->groupBy(
                    'plantillas.id',
                    'plantillas.code',
                    'positions.name',
                    'salary_steps.name',
                    'salary_grades.name',
                    'departments.name',
                    'plantillas.eligibility',
                    'plantillas.experience',
                    'plantillas.training',
                    'plantillas.education',
                    'plantillas.unit',
                    'plantillas.publication_from',
                    'plantillas.publication_to',
                    'plantillas.status',
                    'plantillas.active'
                )
                ->orderBy('positions.name', 'asc')
                ->get();


            $non_plantillas = DB::table('non_plantillas as a')
                ->join('positions as b', 'a.position_id', '=', 'b.id')
                ->join('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employee_type_id', '=', 'd.id')
                ->select(
                    'a.id',
                    'a.position_id',
                    'b.name as position',
                    'a.salary',
                    'c.name as department',
                    'a.eligibility',
                    'a.experience',
                    'a.education',
                    'a.training',
                    'a.description',
                    'a.qualification',
                    'a.vacant',
                    'a.publication_from',
                    'a.publication_to',
                    DB::raw(
                        "case when a.status = 0 then 'Inactive' else 'Active' end as status"
                    ),
                    'd.name as employment_type',
                    'a.number_of_months'
                )
                ->where('a.publication_from', '<=', now())
                ->where('a.publication_to', '>=', now())
                ->where('vacant', '>', 0)
                ->where('a.status', 1)
                ->orderBy('b.name', 'asc')
                ->get();

            $companies = DB::table('companies')->get();

            return $this->successResponse([
                'plantillas' => $data,
                'non_plantillas' => $non_plantillas,
                'companies' => $companies
            ], 'Vacancies data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve vacancies data: ' . $e->getMessage());
        }
    }


    public function positions($id, $type_id)
    {
        try {
            if ($type_id == 1) {
                $data =
                    DB::table('plantillas')
                    ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                    ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                    ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                    ->join('departments', 'departments.id', '=', 'plantillas.department_id')
                    ->select(
                        'plantillas.id',
                        'plantillas.code',
                        'positions.name as position',
                        'salary_steps.name as step',
                        'salary_grades.name as grade',
                        'departments.name as department',
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
                    ->get();
            } else {
                $data = DB::table('non_plantillas as a')
                    ->join('positions as b', 'a.position_id', '=', 'b.id')
                    ->join('departments as c', 'a.department_id', '=', 'c.id')
                    ->leftJoin('employment_types as d', 'a.employee_type_id', '=', 'd.id')
                    ->select(
                        'a.id',
                        'a.position_id',
                        'b.name as position',
                        'a.salary',
                        'c.name as department',
                        'a.eligibility',
                        'a.experience',
                        'a.education',
                        'a.training',
                        'a.description',
                        'a.qualification',
                        'a.vacant',
                        'a.publication_from',
                        'a.publication_to',
                        DB::raw(
                            "case when a.status = 0 then 'Inactive' else 'Active' end as status"
                        ),
                        'd.name as employment_type',
                        'a.number_of_months'
                    )
                    ->where('a.id', $id)
                    ->get();
            }

            return $this->successResponse([
                'data' => $data,
                'type_id' => $type_id
            ], 'Position details retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve position details: ' . $e->getMessage());
        }
    }
}
