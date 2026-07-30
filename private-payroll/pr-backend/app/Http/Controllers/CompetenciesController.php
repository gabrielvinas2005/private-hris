<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Competencies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class CompetenciesController extends Controller
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
            $data = DB::table('competencies')
                // ->leftJoin('employees as b', 'a.employee_id', '=', 'b.id')
                // ->select(
                //     'a.*',
                //     DB::raw("CONCAT(b.first_name,' ',b.last_name) as supervisor")
                // )
                // ->orderBy('a.code', 'asc')
                ->get();

            return $this->successResponse($data, 'Competencies retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve competencies: ' . $e->getMessage());
        }
    }

    public function add()
    {
        try {
            $employees = DB::table('employees')
                ->select(
                    'employees.id',
                    DB::raw("CONCAT(employees.first_name,' ',employees.last_name) as name")
                )
                ->orderBy('employees.first_name', 'asc')
                ->get();

            $subcompetencies = DB::table('subcompetencies')->where('competency_id', 0)->get();

            return $this->successResponse([
                'employees' => $employees,
                'subcompetencies' => $subcompetencies
            ], 'Competency form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load competency form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|min:3|unique:competencies'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $sec_data = array(
                'name' =>  $request->name,
                'active' => $request->has('active') ? true : false,
            );

            $id = 0;
            if ($id == 0) {
                $id = 0 + DB::table('competencies')->max('id');
                $id += 1;
            }

            // Save Subcompetencies
            $data_subcompetencies = $request->all();
            $arr_len_subcompetencies = count($data_subcompetencies["code"]);
            $subcompetencies_data = [];
            $created_subcompetencies = 0;
            $updated_subcompetencies = 0;
            $errors = [];

            for ($i = 0; $i < $arr_len_subcompetencies; $i++) {
                if ($data_subcompetencies["code"][$i] != NULL) {

                    if (DB::table('subcompetencies')->where('code', $data_subcompetencies["code"][$i])->exists()) {
                        $errors[] = 'Subcompetencies code "' . $data_subcompetencies["code"][$i] . '" already exists!';
                        continue;
                    }

                    if (DB::table('subcompetencies')->where('name', $data_subcompetencies["subcompetenciesname"][$i])->exists()) {
                        $errors[] = 'Subcompetencies name "' . $data_subcompetencies["subcompetenciesname"][$i] . '" already exists!';
                        continue;
                    }

                    if ($data_subcompetencies["subcompetencies_id"][$i] == null) {
                        $subcompetencies_id = DB::table('subcompetencies')->max('id') + 1;
                        $created_subcompetencies++;
                    } else {
                        $subcompetencies_id = $data_subcompetencies["subcompetencies_id"][$i];
                        $updated_subcompetencies++;
                    }

                    $subcompetencies_data = [
                        'competency_id' => $id,
                        'code' => $data_subcompetencies["code"][$i],
                        'name' => $data_subcompetencies["subcompetenciesname"][$i],
                        'description' => $data_subcompetencies["description"][$i]
                    ];

                    DB::unprepared('SET IDENTITY_INSERT subcompetencies ON');
                    DB::table('subcompetencies')->updateOrInsert(['id' => $subcompetencies_id], $subcompetencies_data);
                    DB::unprepared('SET IDENTITY_INSERT subcompetencies OFF');
                }
            }

            if (!empty($errors)) {
                return $this->errorResponse('Validation errors occurred: ' . implode('; ', $errors));
            }

            Competencies::create($sec_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Competencies Setup',
                'activity' => 'Add',
                'description' => 'Added ' . $request->name . ' on competencies setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'competency_id' => $id,
                'name' => $request->name,
                'active' => $request->has('active') ? true : false,
                'subcompetencies_created' => $created_subcompetencies,
                'subcompetencies_updated' => $updated_subcompetencies,
                'total_subcompetencies' => $created_subcompetencies + $updated_subcompetencies
            ], 'Competency added successfully!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add competency: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $competencies = DB::table('competencies')->where('id', $id)->first();

            if (!$competencies) {
                return $this->notFoundResponse('Competency not found');
            }

            $subcompetencies = DB::table('subcompetencies')->where('competency_id', $id)->get();

            $employees = DB::table('employees')
                ->select(
                    'employees.id',
                    DB::raw("CONCAT(employees.first_name,' ',employees.last_name) as name")
                )
                ->where(['active' => true, 'is_employee' => true])
                ->orderBy('employees.first_name', 'asc')
                ->get();

            return $this->successResponse([
                'competencies' => $competencies,
                'employees' => $employees,
                'subcompetencies' => $subcompetencies
            ], 'Competency edit data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load competency edit data: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $competency = DB::table('competencies')->where('id', $id)->first();

            if (!$competency) {
                return $this->notFoundResponse('Competency not found');
            }

            $validator = validator($request->all(), [
                'name' => 'required|min:3|unique:competencies,name,' . $id
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $sec_data = array(
                'name' =>  $request->name,
                'active' => $request->has('active') ? true : false,
            );

            // Save Subcompetencies
            $data_subcompetencies = $request->all();
            $arr_len_subcompetencies = count($data_subcompetencies["code"]);
            $subcompetencies_data = [];
            $created_subcompetencies = 0;
            $updated_subcompetencies = 0;

            for ($i = 0; $i < $arr_len_subcompetencies; $i++) {
                if ($data_subcompetencies["code"][$i] != NULL) {

                    if ($data_subcompetencies["subcompetencies_id"][$i] == null) {
                        $subcompetencies_id = 0 + DB::table('subcompetencies')->max('id');
                        $subcompetencies_id += 1;
                        $created_subcompetencies++;
                    } else {
                        $subcompetencies_id = $data_subcompetencies["subcompetencies_id"][$i];
                        $updated_subcompetencies++;
                    }

                    $subcompetencies_data = [
                        'competency_id' => $id,
                        'code' => $data_subcompetencies["code"][$i],
                        'name' => $data_subcompetencies["subcompetenciesname"][$i],
                        'description' => $data_subcompetencies["description"][$i]
                    ];

                    DB::unprepared('SET IDENTITY_INSERT subcompetencies ON');
                    DB::table('subcompetencies')->updateOrInsert(['id' => $subcompetencies_id], $subcompetencies_data);
                    DB::unprepared('SET IDENTITY_INSERT subcompetencies OFF');
                }
            }

            DB::table('competencies')->where('id', $id)->update($sec_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Competencies Setup',
                'activity' => 'Update',
                'description' => 'Updated ' . $request->name . ' on competencies setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'id' => $id,
                'name' => $request->name,
                'active' => $request->has('active') ? true : false,
                'subcompetencies_created' => $created_subcompetencies,
                'subcompetencies_updated' => $updated_subcompetencies,
                'total_subcompetencies' => $created_subcompetencies + $updated_subcompetencies
            ], 'Competency updated successfully!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update competency: ' . $e->getMessage());
        }
    }

    public function delete($type_id, $id)
    {
        try {
            if ($type_id == 1) {
                // delete subcompetencies data
                $data = DB::table('subcompetencies')
                    ->select('id', 'name', DB::raw('1 as type_id'))
                    ->where('id', $id)->first();

                if (!$data) {
                    return $this->notFoundResponse('Subcompetency not found');
                }
            } else {
                return $this->errorResponse('Invalid type ID');
            }

            return $this->successResponse($data, 'Competency data for deletion retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve competency data for deletion: ' . $e->getMessage());
        }
    }

    public function destroy($type_id, $id)
    {
        try {
            if ($type_id == 1) {
                // delete child data
                $subcompetency = DB::table('subcompetencies')->where('id', $id)->first();

                if (!$subcompetency) {
                    return $this->notFoundResponse('Subcompetency not found');
                }

                DB::table('subcompetencies')->where('id', $id)->delete();
                
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Control Panel',
                    'menu'    => 'Competencies Setup',
                    'activity' => 'Delete',
                    'description' => 'Deleted Sub Competencies table informations.',
                );

                Audit::create($data_audit);

                return $this->successResponse([
                    'deleted_id' => $id,
                    'deleted_name' => $subcompetency->name,
                    'type_id' => $type_id
                ], 'Subcompetency deleted successfully!');
            } else {
                return $this->errorResponse('Invalid type ID');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete competency: ' . $e->getMessage());
        }
    }
}
