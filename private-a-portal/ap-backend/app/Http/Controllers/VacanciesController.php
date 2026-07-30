<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class VacanciesController extends Controller
{
    use ApiResponse;

    /**
     * Get all available vacancies
     */
    public function index()
    {
        try {
            $vacancies = DB::table('plantillas')
                ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                ->select(
                    'plantillas.id',
                    'plantillas.code',
                    'positions.name as position',
                    'positions.description',
                    'plantillas.vacant',
                    'salary_grades.grade',
                    'salary_steps.step'
                )
                ->where('plantillas.active', true)
                ->where('plantillas.vacant', '>', 0)
                ->orderBy('positions.name', 'asc')
                ->get();

            return $this->successResponse($vacancies, 'Vacancies retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve vacancies: ' . $e->getMessage());
        }
    }

    /**
     * Get position information by ID and type
     */
    public function positions($id, $type_id)
    {
        try {
            $position = DB::table('plantillas')
                ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                ->select(
                    'plantillas.*',
                    'positions.name as position_name',
                    'positions.description',
                    'positions.education',
                    'positions.training',
                    'positions.qualification',
                    'salary_grades.grade',
                    'salary_steps.step'
                )
                ->where('plantillas.id', $id)
                ->where('plantillas.active', true)
                ->first();

            if (!$position) {
                return $this->errorResponse('Position not found', 404);
            }

            return $this->successResponse($position, 'Position information retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve position information: ' . $e->getMessage());
        }
    }
}
