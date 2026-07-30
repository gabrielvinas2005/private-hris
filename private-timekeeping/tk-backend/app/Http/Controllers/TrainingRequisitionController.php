<?php

namespace App\Http\Controllers;

use App\Audit;
use App\Rules\DateRangeValidation;
use App\Rules\TimeRangeValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponse;

class TrainingRequisitionController extends Controller
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
            $data = DB::table('training_requisition as a')
                ->leftJoin('learnings as b', 'a.training_type_id', '=', 'b.id')
                ->select(
                    'a.*',
                    'b.name as training_type'
                )
                ->orderBy('a.srno', 'asc')
                ->get();

            return $this->successResponse($data, 'Training requisition data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve training requisition data: ' . $e->getMessage());
        }
    }

    public function add($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $training_types = DB::table('learnings')->where('active', true)->get();
            if ($id == 0) {
                $dummy_training_info = array(
                    'id' => 0,
                    'request_date' => date("Y-m-d", strtotime(now())),
                    'date_from' => null,
                    'date_to' => null,
                    'start_time' => null,
                    'end_time' => null,
                    'hrs_day' => 0,
                    'total_hrs' => 0,
                    'is_internal' => 1,
                    'is_paid' => 1,
                    'training_location' => null,
                    'training_type_id' => 0,
                    'training_title' => null,
                    'remarks' => null
                );
                $training_info = (object)$dummy_training_info;
                $training_info = collect([$training_info]);
            } else {
                $training_info = DB::table('training_requisition')->where('id', $id)->get();
            }
            $request_date = date("Y-m-d", strtotime($training_info[0]->request_date));
            $start_time = date("H:i:s", strtotime($training_info[0]->start_time));
            $end_time = date("H:i:s", strtotime($training_info[0]->end_time));

            $employees = DB::table('employees as a')
                ->join('positions as b', 'a.position_id', '=', 'b.id')
                ->join('departments as c', 'a.department_id', '=', 'c.id')
                ->join('training_requisition_employees as d', 'a.id', '=', 'd.employee_id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                    CONCAT(a.first_name,' ',a.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))
                                END as name"),
                    'c.name as department',
                    'b.name as position'
                )
                ->where(['a.is_employee' => true, 'a.active' => true, 'd.training_requisition_id' => $id])
                ->orderBy('name', 'asc')
                ->get();

            return $this->successResponse([
                'training_types' => $training_types,
                'training_info' => $training_info,
                'request_date' => $request_date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'employees' => $employees
            ], 'Training requisition add form data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve training requisition add form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_from' => ['date_format:Y-m-d', new DateRangeValidation($request->date_to)],
                'date_to' => 'date_format:Y-m-d',
                'start_time' => [new TimeRangeValidation($request->end_time)],
            ], [
                'date_from.date_format' => 'Invalid date format.',
                'date_to.date_format' => 'Invalid date format.',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = array(
                'request_date' => $request->request_date,
                'date_from' =>  $request->date_from,
                'date_to' => $request->date_to,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'hrs_day' => $request->hrs_day,
                'total_hrs' => $request->total_hrs,
                'is_internal' => $request->is_internal == 'is_internal' ? true : false,
                'is_paid' => $request->is_paid == 'is_paid' ? true : false,
                'training_location' => $request->training_location,
                'training_type_id' => $request->training_type_id,
                'training_title' => $request->training_title,
                'remarks' => $request->remarks
            );

            if ($id == 0) {
                $id = DB::table('training_requisition')->max('id') + 1;
                DB::table('training_requisition')->insertGetId($data);
            } else {
                DB::table('training_requisition')->updateOrInsert(['id' => $id], $data);
            }

            // Save Leave Attachments
            if ($request->hasFile('attachments')) {

                $allowedfileExtension = ['pdf', 'jpeg', 'jpg', 'png', 'doc', 'docx', 'xlsx', 'xls'];
                $files = $request->file('attachments');
                $ctr = 0;

                foreach ($files as $file) {
                    $file_name = $file->getClientOriginalName();
                    $file_path = Storage::path('training_attachments/TR' . $id . '_' . $file_name);
                    $extension = $file->getClientOriginalExtension();
                    $check = in_array($extension, $allowedfileExtension);

                    if ($check) {
                        // Save record of attachments to database.
                        $training_attachment_data = [
                            'training_id' => $id,
                            'attachment_name' => $file_name,
                            'attachment_path' => $file_path
                        ];

                        DB::table('training_attachments')->insert($training_attachment_data);

                        // Save attachment to path.
                        $request->attachments[$ctr]->storeAs('training_attachments', 'TR' . $id . '_' . $file_name);
                        $ctr++;
                    }
                }
            }

            //Save audit trail
            if ($id == 0) {
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Timekeeping Module',
                    'menu'    => 'Leave Application',
                    'activity' => 'Add',
                    'description' => 'Added Leave Application.',
                );
            } else {
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Timekeeping Module',
                    'menu'    => 'Leave Application',
                    'activity' => 'Update',
                    'description' => 'Updated Leave Application.',
                );
            }

            Audit::create($data_audit);

            return $this->successResponse(null, 'You have successfully created training request!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to store training requisition: ' . $e->getMessage());
        }
    }

    public function addEmployees(Request $request, $id)
    {
        try {
            $employee_data = $request->all();

            if (isset($employee_data['employee_id'])) {
                $arr_len = count($employee_data['employee_id']);

                for ($i = 0; $i < $arr_len; $i++) {
                    if ($employee_data['employee_id'][$i] != NULL) {
                        $data = array(
                            'employee_id' => $employee_data['employee_id'][$i],
                            'training_requisition_id' =>  $id,
                            'status_id' => null
                        );
                        DB::table('training_requisition_employees')->updateOrInsert(['employee_id' => $employee_data['employee_id'][$i]], $data);
                    }
                }
            } else {
                return $this->validationErrorResponse(['error' => 'No Employee Added.']);
            }

            return $this->successResponse(null, 'Successfully Added Participants.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add employees to training requisition: ' . $e->getMessage());
        }
    }

    public function loadUnassignedEmployees()
    {
        try {
            $app_key = env("APP_KEY", "");

            // load employeess to assign to training requisition
            $employees = DB::table('employee_competencies as a')
                ->join('plantilla_competencies as b', function ($join) {
                    $join->on('a.plantilla_id', '=', 'b.plantilla_id');
                    $join->on('a.subcompetency_id', '=', 'b.subcompetency_id');
                })
                ->join('employees as c', 'a.employee_id', '=', 'c.id')
                ->join('plantillas as f', 'a.plantilla_id', '=', 'f.id')
                ->join('positions as d', 'f.position_id', '=', 'd.id')
                ->join('departments as e', 'c.department_id', '=', 'e.id')
                ->select(
                    'c.id',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                    CONCAT(c.first_name,' ',c.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key'))
                                END as name"),
                    'c.position_id',
                    'd.name as position',
                    'c.department_id',
                    'e.name as department',
                    DB::raw("(SELECT CASE WHEN COUNT(A.id) > 0 THEN 1 ELSE 0 END FROM employee_competencies A
                JOIN plantilla_competencies B ON A.plantilla_id = B.plantilla_id AND A.subcompetency_id = B.subcompetency_id
                JOIN employees C ON A.employee_id = c.id
                WHERE is_submitted = 1 AND a.plantilla_id = f.id) as is_submitted")
                )
                ->where(DB::raw("(SELECT CASE WHEN COUNT(A.id) > 0 THEN 1 ELSE 0 END FROM employee_competencies A
                JOIN plantilla_competencies B ON A.plantilla_id = B.plantilla_id AND A.subcompetency_id = B.subcompetency_id
                JOIN employees C ON A.employee_id = c.id
                WHERE is_submitted = 1 AND a.plantilla_id = f.id)"), 1)
                ->whereNotIn('a.employee_id', function ($query) {
                    $query->select(DB::raw("isnull(employee_id,0)"))->from('training_requisition_employees')->get();
                })
                ->distinct()
                ->orderBy('name', 'asc')
                ->get();

            return $this->successResponse($employees, 'Unassigned employees retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve unassigned employees: ' . $e->getMessage());
        }
    }

    public function removeEmployees($id, $employee_id)
    {
        try {
            // update employees assigned to shift schedule
            DB::table('training_requisition_employees')->where('training_requisition_id', $id)->where('employee_id', $employee_id)->delete();

            return $this->successResponse(null, 'Employee removed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to remove employee: ' . $e->getMessage());
        }
    }


    public function attachments($id)
    {
        try {
            $attachments = DB::table('training_attachments')->where('training_id', $id)->get();

            return $this->successResponse($attachments, 'Training attachments retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve training attachments: ' . $e->getMessage());
        }
    }

    public function remove_attachments($id)
    {
        try {
            $data = DB::table('training_attachments')->where('id', $id)->delete();

            return $this->successResponse(null, 'Training attachment removed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to remove training attachment: ' . $e->getMessage());
        }
    }

    public function downloadAttachment($id)
    {
        try {
            $documents = DB::table('training_attachments as a')
                ->join('training_requisition as b', 'a.training_id', '=', 'b.id')
                ->select(
                    'a.id',
                    'b.id as training_id',
                    'a.attachment_name'
                )
                ->where('a.id', $id)
                ->get();

            $pathToFile = storage_path('app/training_attachments/' . 'TR' . $documents[0]->training_id . '_' . $documents[0]->attachment_name);

            return response()->download($pathToFile);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to download attachment: ' . $e->getMessage());
        }
    }
}
