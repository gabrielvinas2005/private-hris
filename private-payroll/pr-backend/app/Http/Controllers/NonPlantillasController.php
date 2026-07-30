<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NonPlantillasController extends Controller
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
            $non_plantillas = DB::table('non_plantillas as a')
                ->join('positions as b', 'a.position_id', '=', 'b.id')
                ->join('departments as c', 'a.department_id', '=', 'c.id')
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
                    )
                )
                ->get();

            return $this->successResponse($non_plantillas, 'Non-plantillas retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve non-plantillas: ' . $e->getMessage());
        }
    }

    public function add($id)
    {
        try {
            if ($id > 0) {
                $non_plantillas = DB::table('non_plantillas')
                    ->where('id', $id)
                    ->get();
            } else {
                $non_plantillas_dummy = array(
                    'id' => 0,
                    'position_id' => 0,
                    'salary_step_id' => 0,
                    'salary_grade_id' => 0,
                    'salary' => 0,
                    'employee_type_id' => 0,
                    'number_of_months' => 0,
                    'vacant' => 0,
                    'department_id' => 0,
                    'description' => '',
                    'qualification' => '',
                    'eligibility' => '',
                    'education' => '',
                    'experience' => '',
                    'training' => '',
                    'publication_from' => '',
                    'publication_to' => '',
                    'status' => 0,
                );

                $non_plantillas = (object)$non_plantillas_dummy;
                $non_plantillas = collect([$non_plantillas]);
            }

            $positions = DB::table('positions')->where('active', true)->get();
            $departments = DB::table('departments')->where('active', true)->get();
            $employee_types = DB::table('employment_types')->where('active', true)->get();

            return $this->successResponse([
                'non_plantillas' => $non_plantillas,
                'positions' => $positions,
                'departments' => $departments,
                'employee_types' => $employee_types
            ], 'Non-plantilla form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load non-plantilla form: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'position_id' => 'required|exists:positions,id',
                'department_id' => 'required|exists:departments,id',
                'salary' => 'required|numeric|min:0',
                'vacant' => 'required|integer|min:0',
                'publication_from' => 'required|date',
                'publication_to' => 'required|date|after:publication_from',
                'employee_type_id' => 'nullable|exists:employment_types,id',
                'number_of_months' => 'nullable|integer|min:0'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = array(
                'position_id' => $request->position_id,
                'salary_step_id' => 0,
                'salary_grade_id' => 0,
                'salary' =>  $request->salary,
                'vacant' =>  $request->vacant,
                'department_id' =>  $request->department_id,
                'description' =>  $request->description,
                'qualification' =>  $request->qualification,
                'eligibility' =>  $request->eligibility,
                'education' =>  $request->education,
                'experience' =>  $request->experience,
                'training' =>  $request->training,
                'publication_from' =>  $request->publication_from,
                'publication_to' =>  $request->publication_to,
                'status' =>  $request->has('active') ? true : false,
                'employee_type_id' => $request->employee_type_id,
                'number_of_months' => $request->number_of_months,
            );

            if ($id == 0) {
                $id = 0 + DB::table('non_plantillas')->max('id');
                $id += 1;
            }
            DB::unprepared('SET IDENTITY_INSERT non_plantillas ON');
            DB::table('non_plantillas')->updateOrInsert(['id' => $id], $data);
            DB::unprepared('SET IDENTITY_INSERT non_plantillas OFF');

            //Save audit trail
            if ($id == 0) {
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Control Panel',
                    'menu'    => 'Non-Plantilla Setup',
                    'activity' => 'Add',
                    'description' => 'Added non-plantilla informations.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'Non-plantilla information added successfully');
            } else {
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Control Panel',
                    'menu'    => 'Non-Plantilla Setup',
                    'activity' => 'Update',
                    'description' => 'Updated non-plantilla informations.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'Non-plantilla information updated successfully');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save non-plantilla information: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $non_plantilla = DB::table('non_plantillas as a')
                ->join('positions as b', 'a.position_id', '=', 'b.id')
                ->join('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employment_types as d', 'a.employee_type_id', '=', 'd.id')
                ->select(
                    'a.*',
                    'b.name as position',
                    'c.name as department',
                    'd.name as employee_type',
                    DB::raw(
                        "case when a.status = 0 then 'Inactive' else 'Active' end as status"
                    )
                )
                ->where('a.id', $id)
                ->first();

            if (!$non_plantilla) {
                return $this->notFoundResponse('Non-plantilla not found');
            }

            return $this->successResponse($non_plantilla, 'Non-plantilla retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve non-plantilla: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            $positions = DB::table('positions')->where('active', true)->get();
            $departments = DB::table('departments')->where('active', true)->get();
            $employee_types = DB::table('employment_types')->where('active', true)->get();

            return $this->successResponse([
                'positions' => $positions,
                'departments' => $departments,
                'employee_types' => $employee_types,
                'fields' => [
                    'position_id' => ['type' => 'select', 'required' => true],
                    'department_id' => ['type' => 'select', 'required' => true],
                    'salary' => ['type' => 'number', 'required' => true, 'min' => 0],
                    'vacant' => ['type' => 'number', 'required' => true, 'min' => 0],
                    'publication_from' => ['type' => 'date', 'required' => true],
                    'publication_to' => ['type' => 'date', 'required' => true],
                    'description' => ['type' => 'textarea'],
                    'qualification' => ['type' => 'textarea'],
                    'eligibility' => ['type' => 'textarea'],
                    'education' => ['type' => 'textarea'],
                    'experience' => ['type' => 'textarea'],
                    'training' => ['type' => 'textarea'],
                    'employee_type_id' => ['type' => 'select'],
                    'number_of_months' => ['type' => 'number', 'min' => 0],
                    'active' => ['type' => 'checkbox']
                ]
            ], 'Create non-plantilla form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $non_plantilla = DB::table('non_plantillas')->where('id', $id)->first();

            if (!$non_plantilla) {
                return $this->notFoundResponse('Non-plantilla not found');
            }

            $positions = DB::table('positions')->where('active', true)->get();
            $departments = DB::table('departments')->where('active', true)->get();
            $employee_types = DB::table('employment_types')->where('active', true)->get();

            return $this->successResponse([
                'non_plantilla' => $non_plantilla,
                'positions' => $positions,
                'departments' => $departments,
                'employee_types' => $employee_types
            ], 'Non-plantilla retrieved for editing');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve non-plantilla: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'position_id' => 'required|exists:positions,id',
                'department_id' => 'required|exists:departments,id',
                'salary' => 'required|numeric|min:0',
                'vacant' => 'required|integer|min:0',
                'publication_from' => 'required|date',
                'publication_to' => 'required|date|after:publication_from',
                'employee_type_id' => 'nullable|exists:employment_types,id',
                'number_of_months' => 'nullable|integer|min:0'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $non_plantilla = DB::table('non_plantillas')->where('id', $id)->first();

            if (!$non_plantilla) {
                return $this->notFoundResponse('Non-plantilla not found');
            }

            $data = array(
                'position_id' => $request->position_id,
                'salary_step_id' => 0,
                'salary_grade_id' => 0,
                'salary' =>  $request->salary,
                'vacant' =>  $request->vacant,
                'department_id' =>  $request->department_id,
                'description' =>  $request->description,
                'qualification' =>  $request->qualification,
                'eligibility' =>  $request->eligibility,
                'education' =>  $request->education,
                'experience' =>  $request->experience,
                'training' =>  $request->training,
                'publication_from' =>  $request->publication_from,
                'publication_to' =>  $request->publication_to,
                'status' =>  $request->has('active') ? true : false,
                'employee_type_id' => $request->employee_type_id,
                'number_of_months' => $request->number_of_months,
            );

            DB::table('non_plantillas')->where('id', $id)->update($data);

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Non-Plantilla Setup',
                'activity' => 'Update',
                'description' => 'Updated non-plantilla informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Non-plantilla information updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update non-plantilla information: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $non_plantilla = DB::table('non_plantillas')->where('id', $id)->first();

            if (!$non_plantilla) {
                return $this->notFoundResponse('Non-plantilla not found');
            }

            DB::table('non_plantillas')->where('id', $id)->delete();

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Non-Plantilla Setup',
                'activity' => 'Delete',
                'description' => 'Deleted non-plantilla informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Non-plantilla deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete non-plantilla: ' . $e->getMessage());
        }
    }
}
