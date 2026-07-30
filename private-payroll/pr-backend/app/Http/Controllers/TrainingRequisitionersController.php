<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\TrainingRequisitioners;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class TrainingRequisitionersController extends Controller
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
            $data = DB::table('training_requisitioners as a')
                ->leftJoin('employees as b', 'a.division_chief_id', '=', 'b.id')
                ->leftJoin('employees as c', 'a.section_chief_id', '=', 'c.id')
                ->leftJoin('departments as d', 'a.department_id', '=', 'd.id')
                ->select(
                    'a.*',
                    DB::raw("CONCAT(b.employee_no,'-',b.last_name,'/', 'Division Chief', ' ', c.employee_no, '-', c.last_name, '/', 'Section Chief') as req_name"),
                    'd.name as department'
                )
                ->orderBy('a.srno', 'asc')
                ->get();

            return $this->successResponse($data, 'Training requisitioners data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve training requisitioners data: ' . $e->getMessage());
        }
    }

    public function add()
    {
        try {
            $employees = DB::table('employees')
                ->select(
                    'id',
                    DB::raw("UPPER(CONCAT(rtrim(first_name),' ',last_name)) as name")
                )
                ->whereNotIn('id', function ($query) {
                    $query->select(DB::raw("isnull(division_chief_id,0)"))->from('divisions')->get();
                })
                ->whereNotIn('id', function ($query) {
                    $query->select(DB::raw("isnull(section_chief_id,0)"))->from('sections')->get();
                })
                ->orderBy('name', 'asc')
                ->get();

            $branches = DB::table('branches')->where('active', true)->get();

            return $this->successResponse([
                'employees' => $employees,
                'branches' => $branches
            ], 'Training requisitioner add form data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve training requisitioner add form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'code' => 'required|string|min:1',
                'name' => 'required|string|min:3|unique:training_requisitioners',
                'department_id' => 'required|exists:departments,id',
                'division_chief_id' => 'nullable|exists:employees,id',
                'section_chief_id' => 'nullable|exists:employees,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $training_requisitioner_data = array(
                'code' => $request->code,
                'name' => $request->name,
                'department_id' => $request->department_id,
                'division_chief_id' => $request->division_chief_id,
                'section_chief_id' => $request->section_chief_id,
                'active' => $request->has('active') ? true : false,
            );

            TrainingRequisitioners::create($training_requisitioner_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Training Requisitioners Setup',
                'activity' => 'Add',
                'description' => 'Added ' . $request->name . ' on training requisitioners setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'You have successfully added new training requisitioner!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to store training requisitioner: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $training_requisitioner = DB::table('training_requisitioners')->where('id', $id)->first();
            
            if (!$training_requisitioner) {
                return $this->notFoundResponse('Training requisitioner not found');
            }

            $employees = DB::table('employees')
                ->select(
                    'id',
                    DB::raw("UPPER(CONCAT(rtrim(first_name),' ',last_name)) as name")
                )
                ->orderBy('name', 'asc')
                ->get();

            $departments = DB::table('departments')->where('active', true)->get();

            return $this->successResponse([
                'training_requisitioner' => $training_requisitioner,
                'employees' => $employees,
                'departments' => $departments
            ], 'Training requisitioner edit form data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve training requisitioner edit form data: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'code' => 'required|string|min:1',
                'name' => 'required|string|min:3|unique:training_requisitioners,name,' . $id,
                'department_id' => 'required|exists:departments,id',
                'division_chief_id' => 'nullable|exists:employees,id',
                'section_chief_id' => 'nullable|exists:employees,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $training_requisitioner = TrainingRequisitioners::find($id);
            
            if (!$training_requisitioner) {
                return $this->notFoundResponse('Training requisitioner not found');
            }

            $training_requisitioner_data = array(
                'code' => $request->code,
                'name' => $request->name,
                'department_id' => $request->department_id,
                'division_chief_id' => $request->division_chief_id,
                'section_chief_id' => $request->section_chief_id,
                'active' => $request->has('active') ? true : false,
            );

            $training_requisitioner->update($training_requisitioner_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Training Requisitioners Setup',
                'activity' => 'Update',
                'description' => 'Updated ' . $request->name . ' on training requisitioners setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'You have successfully updated training requisitioner!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update training requisitioner: ' . $e->getMessage());
        }
    }
}
