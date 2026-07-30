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
            // Plantilla vacancies
            $plantillas  = DB::table('plantillas as a')
                ->join('positions as b', 'b.id', '=', 'a.position_id')
                ->join('departments as e', 'e.id', '=', 'a.department_id')
                ->select(
                    'a.id',
                    'a.code',
                    'b.name as position',
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
                ->get();

            // Non-plantilla vacancies (use non_plantillas table)
            // Rules:
            // - Do not use salary grade/step
            // - Do not use number_of_months
            // - is_approved: 1 = approved, 0 = not approved (status is no longer used for approval validation)
            $nonPlantillas = DB::table('non_plantillas as a')
                ->leftJoin('positions as b', 'b.id', '=', 'a.position_id')
                ->leftJoin('departments as e', 'e.id', '=', 'a.department_id')
                ->select(
                    'a.id',
                    DB::raw("NULL as code"),
                    'b.name as position',
                    'e.name as department',
                    'a.eligibility as eligibility',
                    'a.experience as experience',
                    'a.training as training',
                    'a.education as education',
                    DB::raw("NULL as unit"),
                    'a.publication_from as publication_from',
                    'a.publication_to as publication_to',
                    // status is the active flag for non-plantilla (0 = inactive, 1 = active)
                    DB::raw("a.status as status"),
                    DB::raw("CASE WHEN ISNULL(a.status,0) = 1 THEN 1 ELSE 0 END as active"),
                    DB::raw("CASE WHEN ISNULL(a.is_approved,0) = 1 THEN 1 ELSE 0 END as approved"),
                    DB::raw("NULL as approved_by"),
                    DB::raw("NULL as approved_date"),
                    DB::raw("0 as disapproved"),
                    DB::raw("NULL as disapproved_by"),
                    DB::raw("NULL as disapproved_date"),
                    DB::raw("0 as cancelled"),
                    DB::raw("NULL as cancelled_by"),
                    DB::raw("NULL as cancelled_date"),
                    'a.salary',
                    'a.description',
                    'a.qualification',
                    'a.employee_type_id',
                    DB::raw("ISNULL(a.is_approved,0) as is_approved")
                )
                ->where('a.vacant', '>', 0)
                ->where('a.status', 1) // show only active non-plantilla postings
                ->get();

            $combined = $plantillas
                ->map(function ($p) {
                    $p->is_plantilla = true;
                    // fields to keep shape consistent
                    $p->salary = null;
                    $p->description = null;
                    $p->qualification = null;
                    $p->employee_type_id = null;
                    return $p;
                })
                ->merge($nonPlantillas->map(function ($np) {
                    $np->is_plantilla = false;
                    return $np;
                }))
                ->sortBy('position')
                ->values();

            return $this->successResponse($combined, 'Vacant positions data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve vacant positions data: ' . $e->getMessage());
        }
    }

    public function details(Request $request, $id)
    {
        try {
            $type = $request->query('type', 'plantilla'); // 'plantilla' | 'non_plantilla'

            if ($type === 'non_plantilla') {
                $nonPlantilla = DB::table('non_plantillas')->where('id', $id)->first();
                if (!$nonPlantilla) {
                    return $this->notFoundResponse('Non-plantilla not found');
                }

                $position = DB::table('positions')->where('active', 1)->orderBy('name', 'desc')->get();
                $department = DB::table('departments')->where('active', 1)->orderBy('id', 'asc')->get();

                // Return a "plantilla-like" payload for UI compatibility
                $npData = (object) [
                    'id' => $nonPlantilla->id,
                    'employee_id' => 0,
                    'code' => null,
                    'position_id' => $nonPlantilla->position_id,
                    'department_id' => $nonPlantilla->department_id,
                    'eligibility' => $nonPlantilla->eligibility,
                    'experience' => $nonPlantilla->experience,
                    'training' => $nonPlantilla->training,
                    'education' => $nonPlantilla->education,
                    'unit' => null,
                    'publication_from' => $nonPlantilla->publication_from,
                    'publication_to' => $nonPlantilla->publication_to,
                    // use is_approved for approval validation (status is no longer used for approval validation)
                    'status' => $nonPlantilla->status,
                    'active' => 1,
                    'approved' => (int) (is_null($nonPlantilla->is_approved) ? 0 : $nonPlantilla->is_approved),
                    'approved_by' => null,
                    'approved_date' => null,
                    'disapproved' => 0,
                    'disapproved_by' => null,
                    'disapproved_date' => null,
                    'cancelled' => 0,
                    'cancelled_by' => null,
                    'cancelled_date' => null,
                    // non-plantilla specific
                    'salary' => $nonPlantilla->salary,
                    'description' => $nonPlantilla->description,
                    'qualification' => $nonPlantilla->qualification,
                    'employee_type_id' => $nonPlantilla->employee_type_id,
                    'is_approved' => (int) (is_null($nonPlantilla->is_approved) ? 0 : $nonPlantilla->is_approved),
                    'is_plantilla' => false,
                ];

                return $this->successResponse([
                    'plantilla' => $npData,
                    'position' => $position,
                    'step' => [],   // not applicable
                    'grade' => [],  // not applicable
                    'department' => $department,
                    'educations' => [],
                    'employments' => [],
                    'examinations' => [],
                    'trainings' => [],
                    'eligibilities' => DB::table('eligibilities')->where('active', true)->orderBy('name', 'asc')->get(),
                    'remarks' => [],
                    'competency' => [],
                    'grouped_arr' => []
                ], 'Vacant position details retrieved successfully');
            }

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
                    'plantillas.active',
                    'plantillas.approved',
                    'plantillas.approved_by',
                    'plantillas.approved_date',
                    'plantillas.disapproved',
                    'plantillas.disapproved_by',
                    'plantillas.disapproved_date',
                    'plantillas.cancelled',
                    'plantillas.cancelled_by',
                    'plantillas.cancelled_date'
                )
                ->where('plantillas.id', $id)
                ->first();

            // Format educations with level names from academic_level table
            $educations_raw = DB::table('plantilla_education')
                ->leftJoin('academic_level', 'academic_level.id', '=', 'plantilla_education.academic_level_id')
                ->where('plantilla_education.plantilla_id', $id)
                ->select(
                    'plantilla_education.program',
                    'academic_level.name as level'
                )
                ->get();
            $educations = $educations_raw->map(function($item) {
                return (object)[
                    'level' => $item->level ?? 'N/A',
                    'course' => $item->program ?? 'N/A'
                ];
            });

            // Format work experience
            $employments_raw = DB::table('plantilla_work_experience')->where('plantilla_id', $id)->get();
            $employments = $employments_raw->map(function($item) {
                return (object)[
                    'position' => $item->position ?? 'N/A',
                    'years' => $item->years ?? 'N/A'
                ];
            });

            // Format eligibility/examinations - join with eligibilities table
            $examinations_raw = DB::table('plantilla_eligibility')
                ->leftJoin('eligibilities', 'eligibilities.id', '=', 'plantilla_eligibility.examination_id')
                ->where('plantilla_eligibility.plantilla_id', $id)
                ->select('eligibilities.name as eligibility')
                ->get();
            $examinations = $examinations_raw->map(function($item) {
                return (object)[
                    'eligibility' => $item->eligibility ?? 'N/A'
                ];
            });

            // Format trainings
            $trainings_raw = DB::table('plantilla_trainings')->where('plantilla_id', $id)->get();
            $trainings = $trainings_raw->map(function($item) {
                return (object)[
                    'title' => $item->training ?? 'N/A',
                    'hours' => $item->hours ?? 'N/A'
                ];
            });

            // Format remarks - map requirement to remarks
            $remarks_raw = DB::table('plantilla_remarks')->where('plantilla_id', $id)->get();
            $remarks = $remarks_raw->map(function($item) {
                return (object)[
                    'remarks' => $item->requirement ?? 'N/A',
                    'created_at' => $item->created_at ?? null
                ];
            });
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
                // Only include competencies that are assigned (active)
                if ($element->assign == 1) {
                    $elemName = $element->name;

                    if (!isset($grouped_arr[$elemName])) {
                        $grouped_arr[$elemName] = [];
                    }

                    array_push($grouped_arr[$elemName], $element);
                }
            }

            return $this->successResponse([
                'plantilla' => $plantilla_data,
                'position' => $position,
                'step' => $step,
                'grade' => $grade,
                'department' => $department,
                'educations' => $educations->values()->all(),
                'employments' => $employments->values()->all(),
                'examinations' => $examinations->values()->all(),
                'trainings' => $trainings->values()->all(),
                'eligibilities' => $eligibilities,
                'remarks' => $remarks->values()->all(),
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
            $type = request()->query('type', 'plantilla'); // 'plantilla' | 'non_plantilla'

            if ($type === 'non_plantilla') {
                $nonPlantilla = DB::table('non_plantillas')->where('id', $id)->first();
                if (!$nonPlantilla) {
                    return $this->notFoundResponse('Non-plantilla not found');
                }

                // Use is_approved for approval validation (status is no longer used)
                if ($process_id == 1) {
                    DB::table('non_plantillas')->where('id', $id)->update(['is_approved' => 1]);
                    Audit::create([
                        'user_id' => Auth::user()->id,
                        'module'  => 'Human Resource Module',
                        'menu'    => 'Vacant Position Posting',
                        'activity' => 'Approved',
                        'description' => 'Approved non-plantilla vacant position posting.',
                    ]);
                    return $this->successResponse(['id' => $id], 'Non-plantilla position approved successfully');
                }

                if ($process_id == 2 || $process_id == 3) {
                    DB::table('non_plantillas')->where('id', $id)->update(['is_approved' => 0]);
                    $activity = $process_id == 2 ? 'Disapproved' : 'Cancelled';
                    Audit::create([
                        'user_id' => Auth::user()->id,
                        'module'  => 'Human Resource Module',
                        'menu'    => 'Vacant Position Posting',
                        'activity' => $activity,
                        'description' => strtolower($activity) . ' non-plantilla vacant position posting.',
                    ]);
                    return $this->successResponse(['id' => $id], "Non-plantilla position {$activity} successfully");
                }

                return $this->errorResponse('Invalid process action.');
            }

            $plantilla = DB::table('plantillas')->where('id', $id)->first();
            if (!$plantilla) {
                return $this->notFoundResponse('Plantilla not found');
            }

            if ($process_id == 1) {
                $data = [
                    'approved' => true,
                    'approved_by' => Auth::user()->id,
                    'approved_date' => now(),
                    // Mutually exclusive flags (cancel/disapprove used to leave others true)
                    'disapproved' => false,
                    'cancelled' => false,
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
                    'disapproved_date' => now(),
                    'approved' => false,
                    'cancelled' => false,
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
                    'cancelled_date' => now(),
                    // Cancel used to leave approved=1, so the row appeared in both tabs
                    'approved' => false,
                    'disapproved' => false,
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
