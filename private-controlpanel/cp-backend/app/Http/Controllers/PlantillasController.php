<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Plantilla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use \stdClass;
use App\Traits\ApiResponse;

class PlantillasController extends Controller
{
    use ApiResponse;

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
            $data  = DB::table('plantillas')
                ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                ->leftJoin('departments', 'departments.id', '=', 'plantillas.department_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'plantillas.employment_type_Id')
                ->select(
                    'plantillas.id',
                    'plantillas.code',
                    'positions.name as position',
                    'salary_steps.name as step',
                    'salary_grades.name as grade',
                    'departments.name as department',
                    'plantillas.employment_type_Id as employment_type_Id',
                    'employment_types.name as employment_type_name',
                    'plantillas.eligibility as eligibility',
                    'plantillas.experience as experience',
                    'plantillas.training as training',
                    'plantillas.education as education',
                    'plantillas.remark as remark',
                    'plantillas.unit as unit',
                    'plantillas.publication_from as publication_from',
                    'plantillas.publication_to as publication_to',
                    DB::raw("CASE WHEN plantillas.employee_id IS NOT NULL AND plantillas.employee_id <> 0 THEN 'Occupied' ELSE 'Vacant' END as status"),
                    'plantillas.active'
                )
                ->orderBy('plantillas.code', 'asc')
                ->get();

            return $this->successResponse($data, 'Plantillas data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve plantillas data: ' . $e->getMessage());
        }
    }

    // Get the latest added plantillas for dashboard "Recently Added" table.
    public function recentlyAdded(Request $request)
    {
        try {
            $limit = (int)($request->input('limit') ?? 5);
            if ($limit <= 0) $limit = 5;

            $data = DB::table('plantillas')
                ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                ->leftJoin('departments', 'departments.id', '=', 'plantillas.department_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'plantillas.employment_type_Id')
                ->select(
                    'plantillas.id',
                    'plantillas.code',
                    'positions.name as position',
                    'salary_steps.name as step',
                    'salary_grades.name as grade',
                    'departments.name as department',
                    'plantillas.employment_type_Id as employment_type_Id',
                    'employment_types.name as employment_type_name',
                    'plantillas.active',
                    DB::raw("CASE WHEN plantillas.employee_id IS NOT NULL AND plantillas.employee_id <> 0 THEN 'Occupied' ELSE 'Vacant' END as status")
                )
                ->orderBy('plantillas.created_at', 'desc')
                ->limit($limit)
                ->get();

            return $this->successResponse($data, 'Recently added plantillas retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve recently added plantillas: ' . $e->getMessage());
        }
    }

    public function add(Request $request)
    {
        try {
            $position = DB::table('positions')->where('active', 1)->orderBy('name', 'desc')->get();
            $step = DB::table('salary_steps')->where('active', 1)->orderBy('id', 'asc')->get();
            $grade = DB::table('salary_grades')->where('active', 1)->orderBy('id', 'asc')->get();
            $department = DB::table('departments')->where('active', 1)->orderBy('id', 'asc')->get();
            $employmentTypes = DB::table('employment_types')->where('active', true)->orderBy('name', 'asc')->get();
            $educations = DB::table('plantilla_education')->where('plantilla_id', 0)->get();
            $employments = DB::table('plantilla_work_experience')->where('plantilla_id', 0)->get();
            $examinations = DB::table('plantilla_eligibility')->where('plantilla_id', 0)->get();
            $trainings = DB::table('plantilla_trainings')->where('plantilla_id', 0)->get();
            $remarks = []; // Start with an empty array to avoid carrying over remarks

            $eligibilities = DB::table('eligibilities')->where('active', true)->orderBy('name', 'asc')->get();
            $academicLevels = DB::table('academic_level')->orderBy('id', 'asc')->get();
            $competency = DB::table('competencies as a')
                ->leftjoin('subcompetencies as b', 'a.id', '=', 'b.competency_id')
                ->select(
                    'a.id',
                    'a.name',
                    'b.id as subcompetency_id',
                    'b.name as subcompetency_name',
                    'b.description as subcompetency_description',
                    'b.code as subcompetency_code',
                    db::raw("'' as level")
                )
                ->where('a.active', true)
                ->orderBy('a.name', 'asc')
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
                'position' => $position,
                'step' => $step,
                'grade' => $grade,
                'department' => $department,
                'employment_types' => $employmentTypes,
                'educations' => $educations,
                'employments' => $employments,
                'examinations' => $examinations,
                'trainings' => $trainings,
                'remarks' => $remarks,
                'eligibilities' => $eligibilities,
                'academicLevels' => $academicLevels,
                'competency' => $competency,
                'grouped_arr' => $grouped_arr
            ], 'Plantilla add form data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve plantilla add form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'code' => 'required|min:3|unique:plantillas',
                'position_id' => 'required|exists:positions,id',
                'salary_step_id' => 'required|exists:salary_steps,id',
                'salary_grade_id' => 'required|exists:salary_grades,id',
                'department_id' => 'nullable|exists:departments,id',
                'employment_type_id' => 'nullable|exists:employment_types,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Create the plantilla first to get a real ID
            $plantilla = Plantilla::create([
                'code' => $request->code,
                'position_id' => $request->position_id,
                'salary_step_id' => $request->salary_step_id,
                'salary_grade_id' => $request->salary_grade_id,
                'employee_id' => 0,
                'active' => $request->input('active', false) === true || $request->input('active') === 'true' || $request->input('active') === '1',
                'department_id' => $request->department_id,
                'employment_type_Id' => $request->employment_type_id,
                'unit' => $request->unit,
                'publication_from' => $request->publication_from,
                'publication_to' => $request->publication_to,
                // employee_id is 0 when creating, so plantilla should start as Vacant.
                'status' => 'Vacant',
            ]);
            // Use the actual created ID rather than computing next max ID
            $id = $plantilla->id;
            if (!$id) {
                return $this->errorResponse('Error: Plantilla ID not generated.', 500);
            }

            // Save Remarks
            if ($request->has('remark')) {
                foreach ($request->remark as $index => $remarkText) {
                    if (!empty($remarkText)) {
                        DB::table('plantilla_remarks')->insert([
                            'plantilla_id' => $id,  // Make sure $id is correctly used
                            'requirement' => $remarkText,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            // Save Education
            $data_educ = $request->all();
            $arr_len_educ = isset($data_educ["program"]) && is_array($data_educ["program"]) ? count($data_educ["program"]) : 0;
            $educ_data = [];

            for ($i = 0; $i < $arr_len_educ; $i++) {
                if (isset($data_educ["program"][$i]) && $data_educ["program"][$i] != NULL) {

                    $academicLevelId = $data_educ["academic_level_id"][$i] ?? null;
                    // DB column does not allow NULL for academic_level_id, so block before trying to write.
                    if ($academicLevelId === null || $academicLevelId === '') {
                        return $this->validationErrorResponse(['academic_level_id' => ['Academic level is required for each education program.']], 'Academic level is required for each education program.');
                    }

                    if (!isset($data_educ["education_id"][$i]) || $data_educ["education_id"][$i] == null) {
                        $educ_id = 0 + DB::table('plantilla_education')->max('id');
                        $educ_id += 1;
                    } else {
                        $educ_id = $data_educ["education_id"][$i];
                    }

                    $educ_data = [
                        'plantilla_id' => $id,
                        'academic_level_id' => $academicLevelId,
                        'program' => $data_educ["program"][$i]
                    ];

                    DB::unprepared('SET IDENTITY_INSERT plantilla_education ON');
                    DB::table('plantilla_education')->updateOrInsert(['id' => $educ_id], $educ_data);
                    DB::unprepared('SET IDENTITY_INSERT plantilla_education OFF');
                }
            }
            // Save Employment Records
            $data_emp = $request->all();
            $arr_len_emp = isset($data_emp["position"]) && is_array($data_emp["position"]) ? count($data_emp["position"]) : 0;
            $emp_data = [];

            for ($i = 0; $i < $arr_len_emp; $i++) {
                if (isset($data_emp["position"][$i]) && $data_emp["position"][$i] != NULL) {

                    if (!isset($data_emp["employment_record_id"][$i]) || $data_emp["employment_record_id"][$i] == null) {
                        $emp_id = 0 + DB::table('plantilla_work_experience')->max('id');
                        $emp_id += 1;
                    } else {
                        $emp_id = isset($data_emp["employment_record_id"][$i]) ? $data_emp["employment_record_id"][$i] : (isset($data_emp["id"][$i]) ? $data_emp["id"][$i] : null);
                    }

                    $emp_data = [
                        'plantilla_id' => $id,
                        'position' => $data_emp["position"][$i],
                        'years' => isset($data_emp["years"][$i]) ? $data_emp["years"][$i] : 0,
                    ];

                    DB::unprepared('SET IDENTITY_INSERT plantilla_work_experience ON');
                    DB::table('plantilla_work_experience')->updateOrInsert(['id' => $emp_id], $emp_data);
                    DB::unprepared('SET IDENTITY_INSERT plantilla_work_experience OFF');
                }
            }

            // Save Examinations
            $data_exam = $request->all();
            $arr_len_exam = isset($data_exam["examination_id"]) && is_array($data_exam["examination_id"]) ? count($data_exam["examination_id"]) : (isset($data_exam["eligibility_id"]) && is_array($data_exam["eligibility_id"]) ? count($data_exam["eligibility_id"]) : 0);
            $exam_data = [];

            for ($i = 0; $i < $arr_len_exam; $i++) {
                if (isset($data_exam["eligibility_id"][$i]) && $data_exam["eligibility_id"][$i] != 0) {

                    if (!isset($data_exam["examination_id"][$i]) || $data_exam["examination_id"][$i] == null || $data_exam["examination_id"][$i] == 0) {
                        $exam_id = DB::table('plantilla_eligibility')->max('id') + 1;
                    } else {
                        $exam_id = $data_exam["examination_id"][$i];
                    }

                    $exam_data = [
                        'plantilla_id' => $id,
                        'examination_id' => $data_exam["eligibility_id"][$i],
                    ];

                    DB::unprepared('SET IDENTITY_INSERT plantilla_eligibility ON');
                    DB::table('plantilla_eligibility')->updateOrInsert(['id' => $exam_id], $exam_data);
                    DB::unprepared('SET IDENTITY_INSERT plantilla_eligibility OFF');
                }
            }

            // Save Training
            $data_training = $request->all();
            $arr_len_training = isset($data_training["training"]) && is_array($data_training["training"]) ? count($data_training["training"]) : 0;
            $training_data = [];

            for ($i = 0; $i < $arr_len_training; $i++) {
                if (isset($data_training["training"][$i]) && $data_training["training"][$i] != NULL) {

                    if (!isset($data_training["training_id"][$i]) || $data_training["training_id"][$i] == null) {
                        $training_id = 0 + DB::table('plantilla_trainings')->max('id');
                        $training_id += 1;
                    } else {
                        $training_id = $data_training["training_id"][$i];
                    }

                    $training_data = [
                        'plantilla_id' => $id,
                        'training' => $data_training["training"][$i],
                        'hours' => isset($data_training["hours"][$i]) ? $data_training["hours"][$i] : 0,
                    ];

                    DB::unprepared('SET IDENTITY_INSERT plantilla_trainings ON');
                    DB::table('plantilla_trainings')->updateOrInsert(['id' => $training_id], $training_data);
                    DB::unprepared('SET IDENTITY_INSERT plantilla_trainings OFF');
                }
            }

            // Save Competencies
            $data_competencies = $request->all();

            DB::table('plantilla_competencies')->where('plantilla_id', $id)->delete();

            if (isset($data_competencies['subcomp_id']) && is_array($data_competencies['subcomp_id'])) {
                foreach ($data_competencies['subcomp_id'] as $competency_id) {
                    if ($competency_id != NULL && $competency_id != '') {
                        $indexData = isset($data_competencies['subcompetency_id']) && is_array($data_competencies['subcompetency_id'])
                            ? array_search($competency_id, $data_competencies['subcompetency_id'])
                            : false;

                        if ($indexData === false || !isset($data_competencies["level"][$indexData]) || $data_competencies["level"][$indexData] == null) {
                            return $this->errorResponse('The competency Required level is required.', 422);
                        }

                        $competencies_data = [
                            'level' => $data_competencies["level"][$indexData]
                        ];

                        // DB::unprepared('SET IDENTITY_INSERT plantilla_competencies ON');
                        DB::table('plantilla_competencies')->updateOrInsert(['plantilla_id' => $id, 'subcompetency_id' => $competency_id], $competencies_data);
                        // DB::unprepared('SET IDENTITY_INSERT plantilla_competencies OFF');
                    }
                }
            }
            // Plantilla::create($plantilla);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Plantilla Setup',
                'activity' => 'Add',
                'description' => 'Added ' . $request->code . ' on plantilla setup.',
            );

            Audit::create($data_audit);

            // Return a proper success payload with plantilla ID
            return $this->successResponse(['id' => $id], 'You have successfully added new plantilla!', 201);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to store plantilla: ' . $e->getMessage());
        }
    }

    public function checkCode(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'code' => 'required|string|min:1'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $code = $request->input('code');
            $excludeId = $request->input('exclude_id'); // For edit mode

            // Check if code exists in plantillas table
            $query = DB::table('plantillas')->where('code', $code);

            // Exclude current record if editing
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            $exists = $query->exists();

            return $this->successResponse([
                'exists' => $exists,
                'message' => $exists
                    ? 'This code already exists. Please choose a different code.'
                    : 'Code is available.'
            ], 'Code availability checked successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to check code availability: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $plantilla = DB::table('plantillas')->where('id', $id)->first();

            if (!$plantilla) {
                return $this->notFoundResponse('Plantilla not found');
            }

            $position = DB::table('positions')->where('active', 1)->orderBy('name', 'asc')->get();
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
                    'salary_steps.id as salary_step_id',
                    'salary_grades.id as salary_grade_id',
                    'departments.id as department_id',
                    'plantillas.employment_type_Id as employment_type_Id',
                    'plantillas.eligibility as eligibility',
                    'plantillas.experience as experience',
                    'plantillas.training as training',
                    'plantillas.education as education',
                    'plantillas.unit as unit',
                    'plantillas.publication_from as publication_from',
                    'plantillas.publication_to as publication_to',
                    DB::raw("CASE WHEN plantillas.employee_id IS NOT NULL AND plantillas.employee_id <> 0 THEN 'Occupied' ELSE 'Vacant' END as status"),
                    'plantillas.active'
                )
                ->where('plantillas.id', $id)
                ->first();

            $educations = DB::table('plantilla_education')->where('plantilla_id', $id)->get();
            $employments = DB::table('plantilla_work_experience')->where('plantilla_id', $id)->get();
            $examinations = DB::table('plantilla_eligibility')->where('plantilla_id', $id)->get();
            $remarks = DB::table('plantilla_remarks')->where('plantilla_id', $id)->get();
            $trainings = DB::table('plantilla_trainings')->where('plantilla_id', $id)->get();
            $eligibilities = DB::table('eligibilities')->where('active', true)->orderBy('name', 'asc')->get();
            $academicLevels = DB::table('academic_level')->orderBy('id', 'asc')->get();

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
                ->where('a.active', true)
                ->orderBy('a.name', 'asc')
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
                'remarks' => $remarks,
                'trainings' => $trainings,
                'eligibilities' => $eligibilities,
                'academicLevels' => $academicLevels,
                'competency' => $competency,
                'grouped_arr' => $grouped_arr
            ], 'Plantilla edit data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve plantilla edit data: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'code'              => 'required|min:3|unique:plantillas,code,' . $id,
                'position_id'       => 'required|exists:positions,id',
                'salary_step_id'    => 'required|exists:salary_steps,id',
                'salary_grade_id'   => 'required|exists:salary_grades,id',
                'department_id'     => 'required|exists:departments,id',
                'employment_type_id' => 'nullable|exists:employment_types,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $plantilla = DB::table('plantillas')->where('id', $id)->first();

            if (!$plantilla) {
                return $this->notFoundResponse('Plantilla not found');
            }

            // If plantilla is already occupied (has an employee assigned),
            // prevent changing it to Vacant from the UI.
            if ($plantilla->employee_id !== null && (int)$plantilla->employee_id !== 0) {
                $requestedStatusRaw = $request->input('status');
                $requestedStatus = strtolower(trim((string)$requestedStatusRaw));
                // Block any attempt to set it not-occupied (covers "Vacant" and any other unexpected value).
                if ($requestedStatusRaw !== null && $requestedStatus !== '' && $requestedStatus !== 'occupied') {
                    return $this->validationErrorResponse(
                        ['status' => ['Occupied plantilla cannot be changed to Vacant.']],
                        'Occupied plantilla cannot be changed to Vacant.'
                    );
                }
            }

            // Enforce status based on whether plantilla already has an employee assigned.
            $computedStatus = ($plantilla->employee_id !== null && (int)$plantilla->employee_id !== 0)
                ? 'Occupied'
                : 'Vacant';

            $data = array(
                'code' => $request->code,
                'position_id' => $request->position_id,
                'salary_step_id' => $request->salary_step_id,
                'salary_grade_id' => $request->salary_grade_id,
                'active' => $request->input('active', false) === true || $request->input('active') === 'true' || $request->input('active') === '1',
                'department_id' => $request->department_id,
                'employment_type_Id' => $request->employment_type_id,
                'unit' => $request->unit,
                'publication_from' => $request->publication_from,
                'publication_to' => $request->publication_to,
                'status' => $computedStatus,
            );

            // Save Remarks
            $remark_inputs = $request->input('remark', []);
            $remark_ids = $request->input('remark_id', []);
            if (!is_array($remark_inputs)) {
                $remark_inputs = [];
            }
            if (!is_array($remark_ids)) {
                $remark_ids = [];
            }
            $keptRemarkIds = [];
            foreach ($remark_inputs as $i => $remarkText) {
                if (!empty($remarkText)) {
                    $remark_id = $remark_ids[$i] ?? (DB::table('plantilla_remarks')->max('id') + 1);
                    $remark_data = [
                        'plantilla_id' => $id,
                        'requirement' => $remarkText,
                    ];
                    DB::unprepared('SET IDENTITY_INSERT plantilla_remarks ON');
                    DB::table('plantilla_remarks')->updateOrInsert(['id' => $remark_id], $remark_data);
                    DB::unprepared('SET IDENTITY_INSERT plantilla_remarks OFF');
                    $keptRemarkIds[] = $remark_id;
                }
            }
            if (!empty($keptRemarkIds)) {
                DB::table('plantilla_remarks')
                    ->where('plantilla_id', $id)
                    ->whereNotIn('id', $keptRemarkIds)
                    ->delete();
            } else {
                DB::table('plantilla_remarks')->where('plantilla_id', $id)->delete();
            }

            // Save Education
            $education_programs = $request->input('program', []);
            $education_ids = $request->input('education_id', []);
            $academic_levels = $request->input('academic_level_id', []);
            if (!is_array($education_programs)) $education_programs = [];
            if (!is_array($education_ids)) $education_ids = [];
            if (!is_array($academic_levels)) $academic_levels = [];

            foreach ($education_programs as $i => $program) {
                if ($program !== null && $program !== '') {
                    $academicLevelId = $academic_levels[$i] ?? null;
                    // Prevent NULL insert/update for academic_level_id (DB constraint).
                    if ($academicLevelId === null || $academicLevelId === '') {
                        return $this->validationErrorResponse(
                            ['academic_level_id' => ['Academic level is required for each education program.']],
                            'Academic level is required for each education program.'
                        );
                    }

                    $educ_id = $education_ids[$i] ?? (DB::table('plantilla_education')->max('id') + 1);
                    $educ_data = [
                        'plantilla_id' => $id,
                        'academic_level_id' => $academicLevelId,
                        'program' => $program
                    ];
                    DB::unprepared('SET IDENTITY_INSERT plantilla_education ON');
                    DB::table('plantilla_education')->updateOrInsert(['id' => $educ_id], $educ_data);
                    DB::unprepared('SET IDENTITY_INSERT plantilla_education OFF');
                }
            }

            // Save Employment Records
            $positions = $request->input('position', []);
            $years = $request->input('years', []);
            $employment_ids = $request->input('employment_record_id', []);
            if (!is_array($positions)) $positions = [];
            if (!is_array($years)) $years = [];
            if (!is_array($employment_ids)) $employment_ids = [];

            $keptExperienceIds = [];
            foreach ($positions as $i => $pos) {
                if ($pos !== null && $pos !== '') {
                    $emp_id = $employment_ids[$i] ?? (DB::table('plantilla_work_experience')->max('id') + 1);
                    $emp_data = [
                        'plantilla_id' => $id,
                        'position' => $pos,
                        'years' => $years[$i] ?? 0,
                    ];
                    DB::unprepared('SET IDENTITY_INSERT plantilla_work_experience ON');
                    DB::table('plantilla_work_experience')->updateOrInsert(['id' => $emp_id], $emp_data);
                    DB::unprepared('SET IDENTITY_INSERT plantilla_work_experience OFF');
                    $keptExperienceIds[] = $emp_id;
                }
            }
            if (!empty($keptExperienceIds)) {
                DB::table('plantilla_work_experience')
                    ->where('plantilla_id', $id)
                    ->whereNotIn('id', $keptExperienceIds)
                    ->delete();
            } else {
                DB::table('plantilla_work_experience')->where('plantilla_id', $id)->delete();
            }

            // Save Examinations
            $eligibility_ids = $request->input('eligibility_id', []);
            $examination_ids = $request->input('examination_id', []);
            if (!is_array($eligibility_ids)) $eligibility_ids = [];
            if (!is_array($examination_ids)) $examination_ids = [];

            $keptExamIds = [];
            foreach ($eligibility_ids as $i => $elig_id) {
                if ($elig_id && $elig_id != 0) {
                    $exam_id = $examination_ids[$i] ?? (DB::table('plantilla_eligibility')->max('id') + 1);
                    $exam_data = [
                        'plantilla_id' => $id,
                        'examination_id' => $elig_id,
                    ];
                    DB::unprepared('SET IDENTITY_INSERT plantilla_eligibility ON');
                    DB::table('plantilla_eligibility')->updateOrInsert(['id' => $exam_id], $exam_data);
                    DB::unprepared('SET IDENTITY_INSERT plantilla_eligibility OFF');
                    $keptExamIds[] = $exam_id;
                }
            }
            if (!empty($keptExamIds)) {
                DB::table('plantilla_eligibility')
                    ->where('plantilla_id', $id)
                    ->whereNotIn('id', $keptExamIds)
                    ->delete();
            } else {
                DB::table('plantilla_eligibility')->where('plantilla_id', $id)->delete();
            }

            // Save Training
            $trainings = $request->input('training', []);
            $hours = $request->input('hours', []);
            $training_ids = $request->input('training_id', []);
            if (!is_array($trainings)) $trainings = [];
            if (!is_array($hours)) $hours = [];
            if (!is_array($training_ids)) $training_ids = [];

            $keptTrainingIds = [];
            foreach ($trainings as $i => $training) {
                if ($training !== null && $training !== '') {
                    $training_id = $training_ids[$i] ?? (DB::table('plantilla_trainings')->max('id') + 1);
                    $training_data = [
                        'plantilla_id' => $id,
                        'training' => $training,
                        'hours' => $hours[$i] ?? 0,
                    ];
                    DB::unprepared('SET IDENTITY_INSERT plantilla_trainings ON');
                    DB::table('plantilla_trainings')->updateOrInsert(['id' => $training_id], $training_data);
                    DB::unprepared('SET IDENTITY_INSERT plantilla_trainings OFF');
                    $keptTrainingIds[] = $training_id;
                }
            }
            if (!empty($keptTrainingIds)) {
                DB::table('plantilla_trainings')
                    ->where('plantilla_id', $id)
                    ->whereNotIn('id', $keptTrainingIds)
                    ->delete();
            } else {
                DB::table('plantilla_trainings')->where('plantilla_id', $id)->delete();
            }

            // Save Competencies
            $subcomp_ids = $request->input('subcomp_id', []);
            $subcompetency_ids = $request->input('subcompetency_id', []);
            $levels = $request->input('level', []);
            if (!is_array($subcomp_ids)) $subcomp_ids = [];
            if (!is_array($subcompetency_ids)) $subcompetency_ids = [];
            if (!is_array($levels)) $levels = [];

            DB::table('plantilla_competencies')->where('plantilla_id', $id)->delete();

            foreach ($subcomp_ids as $competency_id) {
                if ($competency_id != null && $competency_id != '') {
                    $indexData = array_search($competency_id, $subcompetency_ids);
                    if ($indexData === false || !isset($levels[$indexData]) || $levels[$indexData] == null) {
                        return $this->validationErrorResponse(['level' => ['The competency Required level is required.']]);
                    }
                    $competencies_data = [
                        'level' => $levels[$indexData]
                    ];
                    DB::table('plantilla_competencies')->updateOrInsert(['plantilla_id' => $id, 'subcompetency_id' => $competency_id], $competencies_data);
                }
            }

            DB::table('plantillas')->where('id', $id)->update($data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Plantilla Setup',
                'activity' => 'Update',
                'description' => 'Updated ' . $request->code . ' on plantilla setup.',
            );

            Audit::create($data_audit);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Plantilla Setup',
                'activity' => 'Update',
                'description' => 'Updated ' . $request->code . ' on plantilla setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'You have successfully updated plantilla!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update plantilla: ' . $e->getMessage());
        }
    }

    public function delete($type_id, $id)
    {
        try {
            $data = null;

            if ($type_id == 2) {
                // delete child data
                $data = DB::table('plantilla_education')
                    ->select('id', 'program as name', DB::raw('2 as type_id'))
                    ->where('id', $id)->first();
            } elseif ($type_id == 4) {
                // delete child data
                $data = DB::table('plantilla_work_experience')
                    ->select('id', 'position as name', DB::raw('4 as type_id'))
                    ->where('id', $id)->first();
            } elseif ($type_id == 5) {
                // delete child data
                $data = DB::table('plantilla_eligibility')
                    ->join('eligibilities', 'eligibilities.id', '=', 'plantilla_eligibility.examination_id')
                    ->select('plantilla_eligibility.id', 'eligibilities.name as name', DB::raw('5 as type_id'))
                    ->where('plantilla_eligibility.id', $id)->first();
            } elseif ($type_id == 6) {
                // delete child data
                $data = DB::table('plantilla_trainings')
                    ->select('id', 'training as name', DB::raw('6 as type_id'))
                    ->where('id', $id)->first();
            } elseif ($type_id == 7) {
                // delete child data
                $data = DB::table('plantilla_remarks')
                    ->select('id', 'requirement as name', DB::raw('7 as type_id'))
                    ->where('id', $id)->first();
            }

            if (!$data) {
                return $this->notFoundResponse('Record not found');
            }

            return $this->successResponse($data, 'Plantilla delete data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve plantilla delete data: ' . $e->getMessage());
        }
    }

    public function destroy($type_id, $id)
    {
        try {
            $data_audit = null;

            if ($type_id == 2) {
                // delete child data
                $record = DB::table('plantilla_education')->where('id', $id)->first();
                if (!$record) {
                    return $this->notFoundResponse('Educational record not found');
                }
                DB::table('plantilla_education')->where('id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'HR Module',
                    'menu'    => 'Plantilla Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Educational Attainment table information',
                );
            } elseif ($type_id == 4) {
                // delete child data
                $record = DB::table('plantilla_work_experience')->where('id', $id)->first();
                if (!$record) {
                    return $this->notFoundResponse('Work experience record not found');
                }
                DB::table('plantilla_work_experience')->where('id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'HR Module',
                    'menu'    => 'Plantilla Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Work Experience table information',
                );
            } elseif ($type_id == 5) {
                // delete child data
                $record = DB::table('plantilla_eligibility')->where('id', $id)->first();
                if (!$record) {
                    return $this->notFoundResponse('Eligibility record not found');
                }
                DB::table('plantilla_eligibility')->where('id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'HR Module',
                    'menu'    => 'Plantilla Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Eligibility table information',
                );
            } elseif ($type_id == 6) {
                // delete child data
                $record = DB::table('plantilla_trainings')->where('id', $id)->first();
                if (!$record) {
                    return $this->notFoundResponse('Training record not found');
                }
                DB::table('plantilla_trainings')->where('id', $id)->delete();
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'HR Module',
                    'menu'    => 'Plantilla Record',
                    'activity' => 'Delete',
                    'description' => 'Deleted Training table information',
                );
            } else {
                return $this->errorResponse('Invalid type_id provided', 400);
            }

            Audit::create($data_audit);

            return $this->successResponse(null, 'Successfully deleted!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete record: ' . $e->getMessage());
        }
    }

    /**
     * Delete main plantilla record
     */
    public function destroyMain($id)
    {
        try {
            $plantilla = DB::table('plantillas')->where('id', $id)->first();

            if (!$plantilla) {
                return $this->notFoundResponse('Plantilla not found');
            }

            // Delete all related records first
            DB::table('plantilla_education')->where('plantilla_id', $id)->delete();
            DB::table('plantilla_work_experience')->where('plantilla_id', $id)->delete();
            DB::table('plantilla_eligibility')->where('plantilla_id', $id)->delete();
            DB::table('plantilla_trainings')->where('plantilla_id', $id)->delete();
            DB::table('plantilla_remarks')->where('plantilla_id', $id)->delete();
            DB::table('plantilla_competencies')->where('plantilla_id', $id)->delete();

            // Delete the main plantilla record
            DB::table('plantillas')->where('id', $id)->delete();

            // Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Plantilla Setup',
                'activity' => 'Delete',
                'description' => 'Deleted plantilla: ' . $plantilla->code,
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Plantilla deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete plantilla: ' . $e->getMessage());
        }
    }
}
