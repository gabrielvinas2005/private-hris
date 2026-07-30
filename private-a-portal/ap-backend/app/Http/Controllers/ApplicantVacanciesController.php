<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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

    public function index(): JsonResponse
    {
        try {
            // Get current applicant ID
            $applicantId = null;
            if (Auth::check()) {
                $applicant = DB::table('applicant_headers')
                    ->where('user_id', Auth::id())
                    ->first();
                $applicantId = $applicant ? $applicant->id : null;
            }

            $query = DB::table('plantillas')
                ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                ->leftJoin('departments', 'departments.id', '=', 'plantillas.department_id')
                ->leftJoin('employees', 'employees.position_applied_id', '=', 'plantillas.id')
                ->select('plantillas.id', 'plantillas.code', 'positions.name as position', 'salary_steps.name as step', 'salary_grades.name as grade', 'departments.name as department', 'plantillas.eligibility as eligibility', 'plantillas.experience as experience', 'plantillas.training as training', 'plantillas.education as education', 'plantillas.unit as unit', 'plantillas.publication_from as publication_from', 'plantillas.publication_to as publication_to', 'plantillas.status as status', 'plantillas.active', DB::raw('count(employees.id) as total'))
                ->where('plantillas.employee_id', 0)
                ->where('plantillas.publication_to', '>=', now())
                ->where('plantillas.active', 1)
                ->where('plantillas.approved', 1);

            // If applicant is logged in, exclude positions they've already applied for
            if ($applicantId) {
                $query->whereNotExists(function ($subquery) use ($applicantId) {
                    $subquery->select(DB::raw(1))
                        ->from('applicant_details')
                        ->whereColumn('applicant_details.position_applied_id', 'plantillas.id')
                        ->where('applicant_details.applicant_id', $applicantId)
                        ->where('applicant_details.is_plantilla', 1);
                });
            }

            $data = $query->groupBy('plantillas.id', 'plantillas.code', 'positions.name', 'salary_steps.name', 'salary_grades.name', 'departments.name', 'plantillas.eligibility', 'plantillas.experience', 'plantillas.training', 'plantillas.education', 'plantillas.unit', 'plantillas.publication_from', 'plantillas.publication_to', 'plantillas.status', 'plantillas.active')
                ->orderBy('plantillas.code', 'asc')
                ->get();

            $plantillaIds = $data->pluck('id')->toArray();

            $workExperiences = DB::table('plantilla_work_experience')
                ->whereIn('plantilla_id', $plantillaIds)
                ->select('plantilla_id', 'position')
                ->get()
                ->groupBy('plantilla_id');

            $trainings = DB::table('plantilla_trainings')
                ->whereIn('plantilla_id', $plantillaIds)
                ->select('plantilla_id', 'training', 'hours')
                ->get()
                ->groupBy('plantilla_id');

            $eligibilities = DB::table('plantilla_eligibility')
                ->join('eligibilities', 'eligibilities.id', '=', 'plantilla_eligibility.examination_id')
                ->whereIn('plantilla_eligibility.plantilla_id', $plantillaIds)
                ->select('plantilla_eligibility.plantilla_id as plantilla_id', 'eligibilities.name as eligibility')
                ->get()
                ->groupBy('plantilla_id');

            $educations = DB::table('plantilla_education')
                ->whereIn('plantilla_id', $plantillaIds)
                ->select('plantilla_id', 'program', 'academic_level_id')
                ->get()
                ->groupBy('plantilla_id');

            $remarks = DB::table('plantilla_remarks')
                ->whereIn('plantilla_id', $plantillaIds)
                ->select('plantilla_id', 'requirement')
                ->get()
                ->groupBy('plantilla_id');

            $competencies = DB::table('plantilla_competencies')
                ->leftJoin('subcompetencies', 'subcompetencies.id', '=', 'plantilla_competencies.subcompetency_id')
                ->whereIn('plantilla_competencies.plantilla_id', $plantillaIds)
                ->select('plantilla_competencies.plantilla_id as plantilla_id', 'subcompetencies.name as competency')
                ->get()
                ->groupBy('plantilla_id');

            $data = $data->map(function ($plantilla) use ($workExperiences, $trainings, $eligibilities, $educations, $remarks, $competencies) {
                $id = $plantilla->id;

                $experienceList = $workExperiences->get($id, collect());
                $plantilla->work_experience_list = $experienceList->pluck('position')->filter()->toArray();
                $plantilla->experience = !empty($plantilla->experience) 
                    ? $plantilla->experience 
                    : ($experienceList->isNotEmpty() ? implode(', ', $experienceList->pluck('position')->filter()->toArray()) : null);

                $trainingList = $trainings->get($id, collect());
                $plantilla->training_list = $trainingList->map(function ($t) {
                    $hours = $t->hours ? " ({$t->hours} hours)" : '';
                    return $t->training . $hours;
                })->filter()->toArray();
                $plantilla->training = !empty($plantilla->training) 
                    ? $plantilla->training 
                    : ($trainingList->isNotEmpty() ? implode(', ', $trainingList->pluck('training')->filter()->toArray()) : null);

                $eligibilityList = $eligibilities->get($id, collect());
                $plantilla->eligibility_list = $eligibilityList->pluck('eligibility')->filter()->toArray();
                $plantilla->eligibility = !empty($plantilla->eligibility) 
                    ? $plantilla->eligibility 
                    : ($eligibilityList->isNotEmpty() ? implode(', ', $eligibilityList->pluck('eligibility')->filter()->toArray()) : null);

                $educationList = $educations->get($id, collect());
                $plantilla->education_list = $educationList->pluck('program')->filter()->toArray();
                $plantilla->education = !empty($plantilla->education) 
                    ? $plantilla->education 
                    : ($educationList->isNotEmpty() ? implode(', ', $educationList->pluck('program')->filter()->toArray()) : null);

                $plantilla->education_details = $educationList->map(function ($edu) {
                    return [
                        'program' => $edu->program,
                        'academic_level_id' => $edu->academic_level_id
                    ];
                })->filter(function ($edu) {
                    return !empty($edu['program']);
                })->values()->toArray();
                

                $remarksList = $remarks->get($id, collect());
                $plantilla->requirements_list = $remarksList->pluck('requirement')->filter()->toArray();
                

                $competenciesList = $competencies->get($id, collect());
                $plantilla->competencies_list = $competenciesList->pluck('competency')->filter()->toArray();
                
                return $plantilla;
            });

            $data = $data->map(function ($item) {
                $item->type = 'plantilla';
                return $item;
            });
    
            $nonPlantillaQuery = DB::table('non_plantillas')
                ->join('positions', 'positions.id', '=', 'non_plantillas.position_id')
                ->leftJoin('departments', 'departments.id', '=', 'non_plantillas.department_id')
                ->select(
                    'non_plantillas.id',
                    DB::raw('NULL as code'),
                    'positions.name as position',
                    DB::raw('NULL as step'),
                    DB::raw('NULL as grade'),
                    'departments.name as department',
                    'non_plantillas.eligibility',
                    'non_plantillas.experience',
                    'non_plantillas.training',
                    'non_plantillas.education',
                    DB::raw('NULL as unit'),
                    'non_plantillas.publication_from',
                    'non_plantillas.publication_to',
                    DB::raw("'non_plantilla' as type"),
                    'non_plantillas.salary',
                    'non_plantillas.vacant'
                )
                ->where('non_plantillas.status', 1)
                ->where('non_plantillas.publication_from', '<=', now())
                ->where('non_plantillas.publication_to', '>=', now());

            // If applicant is logged in, exclude non-plantilla positions they've already applied for
            if ($applicantId) {
                $nonPlantillaQuery->whereNotExists(function ($subquery) use ($applicantId) {
                    $subquery->select(DB::raw(1))
                        ->from('applicant_details')
                        ->whereColumn('applicant_details.position_applied_id', 'non_plantillas.id')
                        ->where('applicant_details.applicant_id', $applicantId)
                        ->where('applicant_details.is_plantilla', 0);
                });
            }

            $nonPlantillaData = $nonPlantillaQuery
                ->orderBy('positions.name', 'asc')
                ->get();

            // Ensure plantilla rows keep a stable discriminator for the frontend
            $data = $data->map(function ($item) {
                $item->type = 'plantilla';
                return $item;
            });


            $applicant_count = DB::table('applicant_details')
                ->select('position_applied_id', DB::raw('COUNT(DISTINCT applicant_id) as total'))
                ->where('is_plantilla', 1)
                ->groupBy('position_applied_id')
                ->get();

            $totalApplicants = DB::table('applicant_headers')->count();

            $appliedResult = DB::table('applicant_details')
                ->select(DB::raw('COUNT(DISTINCT applicant_id) as count'))
                ->first();
            $totalApplicantsWhoApplied = $appliedResult ? $appliedResult->count : 0;

            $hiredResult = DB::table('applicant_details')
                ->whereIn('application_status_id', [4, 5])
                ->select(DB::raw('COUNT(DISTINCT applicant_id) as count'))
                ->first();
            $hiredApplicants = $hiredResult ? $hiredResult->count : 0;

            $successRate = $totalApplicantsWhoApplied > 0 
                ? round(($hiredApplicants / $totalApplicantsWhoApplied) * 100, 1) 
                : 0;

            return $this->successResponse([
                'vacancies' => $data,
                'non_plantilla_vacancies' => $nonPlantillaData,
                'applicant_count' => $applicant_count,
                'total_applicants' => $totalApplicants,
                'hired_applicants' => $hiredApplicants,
                'success_rate' => $successRate
            ], 'Applicant vacancies retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve applicant vacancies: ' . $e->getMessage());
        }
    }

    /**
     * Get detailed position information
     */
    public function positions($id, $type_id): JsonResponse
    {
        try {
            $position = DB::table('plantillas')
                ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                ->leftJoin('departments', 'departments.id', '=', 'plantillas.department_id')
                ->select(
                    'plantillas.id',
                    'plantillas.code',
                    'positions.name as position',
                    'positions.description as position_description',
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
                    'plantillas.active'
                )
                ->where('plantillas.id', $id)
                ->where('plantillas.active', 1)
                ->where('plantillas.approved', 1)
                ->first();

            if (!$position) {
                return $this->notFoundResponse('Position not found');
            }

            return $this->successResponse([
                'position' => $position,
                'type_id' => $type_id
            ], 'Position information retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve position information: ' . $e->getMessage());
        }
    }

    public function changestatus(Request $request, $id, $status_id): JsonResponse
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

            // Note: Audit trail removed for now - can be re-added if Audit model is available

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

    /**
     * Get monthly applicant count (for current month)
     */
    public function monthlyApplicantCount(): JsonResponse
    {
        try {
            // Get current month start and end dates
            $startOfMonth = now()->startOfMonth();
            $endOfMonth = now()->endOfMonth();

            // Count applicants registered in the current month
            $count = DB::table('applicant_headers')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();

            return $this->successResponse([
                'monthly_count' => $count,
                'month' => now()->format('F Y')
            ], 'Monthly applicant count retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve monthly applicant count: ' . $e->getMessage());
        }
    }
}
