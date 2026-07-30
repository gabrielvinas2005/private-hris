<?php

namespace App\Http\Controllers;

use PDF;
use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HazardPayController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $data = DB::table('hazard_pay_setups')->orderBy('percentage', 'asc')->get();

            return $this->successResponse($data, 'Hazard pay table retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve hazard pay table: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $arr_len = count($data['id']);
            $hazard_data = [];

            for ($i = 1; $i < $arr_len; $i++) {

                if ($data['salary_from'][$i] != NULL && $data['salary_to'][$i] != NULL && $data['percentage'][$i] != NULL) {

                    $id = $data['id'][$i];
                    $salary_from = $data['salary_from'][$i];
                    $salary_to = $data['salary_to'][$i];
                    $percentage = $data['percentage'][$i];

                    if ($id == 0) {
                        $data_exists = DB::table('hazard_pay_setups')->where([
                            'salary_from' => $salary_from,
                            'salary_to' => $salary_to,
                            'percentage' => $percentage,
                        ])->get();

                        if ($data_exists->isNotEmpty()) {
                            return $this->errorResponse('Hazard Pay Allowance record already exist.', 400);
                        }
                        if ($salary_from > $salary_to) {
                            return $this->errorResponse('Salary From cannot be greater than Salary To.', 400);
                        }
                    } else {
                        $data_exists = DB::table('hazard_pay_setups')->where([
                            'salary_from' => $salary_from,
                            'salary_to' => $salary_to,
                            'percentage' => $percentage
                        ])
                            ->whereNotIn('id', [$id])
                            ->get();

                        if ($data_exists->isNotEmpty()) {
                            return $this->errorResponse('Hazard Pay Allowance record already exist.', 400);
                        }

                        //if else for checking if the value is negative
                        if ($data['salary_from'][$i] < 0 && $data['salary_to'][$i] > 0 && $data['percentage'][$i] > 0) {
                            return $this->errorResponse('Salary From cannot be negative.', 400);
                        } else if ($data['salary_from'][$i] > 0 && $data['salary_to'][$i] < 0 && $data['percentage'][$i] > 0) {
                            return $this->errorResponse('Salary To cannot be negative.', 400);
                        } else if ($data['salary_from'][$i] > 0 && $data['salary_to'][$i] > 0 && $data['percentage'][$i] < 0) {
                            return $this->errorResponse('Percentage cannot be negative.', 400);
                        } else if ($data['salary_to'][$i] == 0 || $data['salary_to'][$i] == '') {
                            return $this->errorResponse('No salary To data to save.', 400);
                        } else if ($data['percentage'][$i] == 0 || $data['salary_to'][$i] == '') {
                            return $this->errorResponse('No Percentage data to save.', 400);
                        }
                    }

                    $rules = [
                        "salary_from.$i" => 'numeric|min:1|max:1000000000',
                        "salary_to.$i" => 'numeric|min:1|max:1000000000',
                        "percentage.$i" => 'numeric|min:0.01|max:100',
                    ];

                    $messages = [
                        'salary_from.' . $i . '.numeric' => 'The salary from field must be a number.',
                        'salary_from.' . $i . '.min' => 'The salary from field must be at least :min.',
                        'salary_from.$i.max' => 'The Salary From field must be less than or equal to 1000000000.',
                        'salary_to.' . $i . '.numeric' => 'The salary to field must be a number.',
                        'salary_to.' . $i . '.min' => 'The salary to field must be at least :min.',
                        'salary_to.$i.max' => 'The Salary To field must be less than or equal to 1000000000.',
                        'percentage.' . $i . '.numeric' => 'The percentage field must be a number.',
                        'percentage.' . $i . '.min' => 'The percentage field must be at least :min.',
                    ];

                    $validator = Validator::make($data, $rules, $messages);

                    if ($validator->fails()) {
                        return $this->validationErrorResponse($validator->errors());
                    }

                    $request->validate($rules);

                    if ($data['id'][$i] == null) {
                        $id = 0 + DB::table('hazard_pay_setups')->max('id');
                        $id += 1;
                    } else {
                        $id = $data['id'][$i];
                    }

                    $hazard_data = [
                        'salary_from' => $salary_from,
                        'salary_to' => $salary_to,
                        'percentage' => $percentage,
                    ];

                    DB::unprepared('SET IDENTITY_INSERT hazard_pay_setups ON');
                    DB::table('hazard_pay_setups')->updateOrInsert(['id' => $id], $hazard_data);
                    DB::unprepared('SET IDENTITY_INSERT hazard_pay_setups OFF');
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Hazard Pay Setup',
                'activity' => 'Update',
                'description' => 'Updated Hazard Pay information.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'You have successfully update Hazard Pay Table!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to store hazard pay data: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $data = DB::table('hazard_pay_setups')->where('id', $id)->get();

            return $this->successResponse($data, 'Hazard pay data for deletion retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve hazard pay data for deletion: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::table('hazard_pay_setups')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Hazard Pay Setup',
                'activity' => 'Delete',
                'description' => 'Deleted Hazard Pay information.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Successfully deleted!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete hazard pay data: ' . $e->getMessage());
        }
    }

    public function loadHazard()
    {
        try {
            $data = DB::table('hazard_pay_headers as a')
                ->leftJoin('departments as b', 'a.department_id', '=', 'b.id')
                ->leftJoin('months as c', 'a.month_id', '=', 'c.id')
                ->select(
                    'a.*',
                    'b.name as department',
                    'c.name as month'
                )
                ->get();

            return $this->successResponse($data, 'Hazard list retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load hazard list: ' . $e->getMessage());
        }
    }

    public function addHazard($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $departments = DB::table('departments')->get();

            if ($id != 0) {
                $data = DB::table('hazard_pay_headers as a')
                    ->leftJoin('hazard_pay_details as b', 'a.id', '=', 'b.hazard_pay_id')
                    ->leftJoin('employees as c', 'b.employee_id', '=', 'c.id')
                    ->leftJoin('name_suffixes as f', 'c.name_suffix_id', '=', 'f.id')
                    ->leftJoin('positions as g', 'c.position_id', '=', 'g.id')
                    ->select(
                        'a.id',
                        'a.posted',
                        'a.department_id',
                        'a.month_id',
                        'a.year',
                        'b.id as hazard_dtl',
                        'b.employee_id',
                        'b.no_of_days',
                        'b.salary_grade_id',
                        'b.salary_step_id',
                        'b.hazard_pay_setup_id',
                        'c.employee_no',
                        'c.salary',
                        DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                    CONCAT(c.first_name,' ',c.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key'))
                                END as name"),
                        'g.name as position',
                        'f.name as name_suffix'
                    )
                    ->where('a.id', $id)
                    ->get();

                return $this->successResponse([
                    'departments' => $departments,
                    'data' => $data
                ], 'Hazard pay data retrieved successfully');
            } else {
                return $this->successResponse([
                    'departments' => $departments,
                    'data' => []
                ], 'Hazard pay form loaded successfully');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load hazard pay form: ' . $e->getMessage());
        }
    }

    public function storeHazard(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'department_id' => 'required|exists:departments,id',
                'month_id' => 'required|exists:months,id',
                'year' => 'required|integer|min:2000|max:2100'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = [
                'department_id' => $request->department_id,
                'month_id' => $request->month_id,
                'year' => $request->year,
                'posted' => false
            ];

            if ($id == 0) {
                $id = DB::table('hazard_pay_headers')->max('id') + 1;
            }

            DB::unprepared('SET IDENTITY_INSERT hazard_pay_headers ON');
            DB::table('hazard_pay_headers')->updateOrInsert(['id' => $id], $data);
            DB::unprepared('SET IDENTITY_INSERT hazard_pay_headers OFF');

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'Hazard Pay',
                'activity' => 'Create',
                'description' => 'Created Hazard Pay information',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Successfully Created Hazard Pay!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to store hazard pay: ' . $e->getMessage());
        }
    }

    public function storeHazardEmployees(Request $request, $id)
    {
        try {
            $employee_data = $request->all();
            $data = [];

            if (isset($employee_data['hazard_pay_setup_id'])) {
                $arr_len = count($employee_data['hazard_pay_setup_id']);
                for ($i = 0; $i < $arr_len; $i++) {
                    if ($employee_data['hazard_pay_setup_id'][$i] != NULL) {
                        if (in_array($employee_data['hazard_pay_setup_id'][$i], $employee_data['select'])) {

                            $hazard_pay_setup_id = $employee_data['hazard_pay_setup_id'][$i];

                            $data = [
                                'hazard_pay_setup_id' => $hazard_pay_setup_id,
                                'hazard_pay_id' => $id,
                                'employee_id' => $employee_data['id'][$i],
                                'no_of_days' => $employee_data['no_of_days'][$i],
                                'salary_grade_id' => $employee_data['salary_grade_id'][$i],
                                'salary_step_id' => $employee_data['salary_step_id'][$i]
                            ];

                            DB::table('hazard_pay_details')->insert($data);
                        }
                    }
                }
            }

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'Hazard Pay',
                'activity' => 'Added',
                'description' => 'Added Hazard Pay Employees.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Successfully Added Employees to Hazard Pay!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to store hazard pay employees: ' . $e->getMessage());
        }
    }

    public function deleteHazardEmployees($id)
    {
        try {
            DB::table('hazard_pay_details')->where('id', $id)->delete();

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'Hazard Pay',
                'activity' => 'Delete',
                'description' => 'Deleted Hazard Pay Employee.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Successfully deleted hazard pay employee!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete hazard pay employee: ' . $e->getMessage());
        }
    }

    public function processHazard(Request $request, $id, $type_id)
    {
        try {
            if ($type_id == 1) {
                DB::table('hazard_pay_headers')->where('id', $id)->update(['posted' => true]);

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'Hazard Pay',
                    'activity' => 'Posted',
                    'description' => 'Posted Hazard Pay Allowance.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'Successfully Posted Hazard Pay Allowance!');
            } else {
                DB::table('hazard_pay_headers')->where('id', $id)->update(['posted' => false]);

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'Hazard Pay',
                    'activity' => 'Unposted',
                    'description' => 'Unposted Hazard Pay Allowance.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'Successfully Unposted Hazard Pay Allowance!');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process hazard pay: ' . $e->getMessage());
        }
    }

    public function Report()
    {
        try {
            $app_key = env("APP_KEY", "");
            $departments = DB::table('departments')->get();
            $hazard = DB::table('hazard_pay_headers as a')
                ->join('months as b', 'a.month_id', '=', 'b.id')
                ->join('departments as c', 'a.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'b.name as department',
                    DB::raw("CONCAT(a.month_id,' ',a.year,' (', b.name ,')') as name")
                )
                ->get();

            return $this->successResponse([
                'hazard' => $hazard,
                'departments' => $departments
            ], 'Hazard pay report data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve hazard pay report data: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'department_id' => [
                    'required',
                    'exists:departments,id'
                ],
                'month_id' => [
                    'required',
                    'exists:months,id'
                ],
                'year' => [
                    'required',
                    'integer',
                    'min:2005',
                    'max:' . date('Y')
                ]
            ], [
                'department_id.required' => 'The department field is required.',
                'department_id.exists' => 'The selected department does not exist.',
                'month_id.required' => 'The month field is required.',
                'month_id.exists' => 'The selected month is invalid.',
                'year.required' => 'The year field is required.',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));
            $company = DB::table('companies')->get();

            $hazard = DB::table('hazard_pay_headers as a')
                ->join('hazard_pay_details as b', 'a.id', '=', 'b.hazard_pay_id')
                ->join('employees as c', 'b.employee_id', '=', 'c.id')
                ->join('positions as d', 'c.position_id', '=', 'd.id')
                ->leftJoin('name_suffixes as g', 'c.name_suffix_id', '=', 'g.id')
                ->join('hazard_pay_setups as h', 'b.hazard_pay_setup_id', '=', 'h.id')
                ->join('departments as i', 'a.department_id', '=', 'i.id')
                ->join('months as j', 'a.month_id', '=', 'j.id')
                ->select(
                    'a.id',
                    'b.employee_id',
                    'c.employee_no',
                    'c.salary',
                    DB::raw("CONCAT(j.name,' ',a.year) as month_year"),
                    DB::raw("CONCAT(
                        CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN c.last_name ELSE dbo.ufn_DecryptString(c.last_name,'$app_key') END,
                        ', ',
                        CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN c.first_name ELSE dbo.ufn_DecryptString(c.first_name,'$app_key') END,
                        ' ',
                        CASE
                            WHEN ISNULL(c.is_encrypted, 0) = 0 THEN LEFT(c.middle_name, 1)
                            ELSE LEFT(dbo.ufn_DecryptString(c.middle_name, '$app_key'), 1)
                        END,
                        CASE WHEN c.middle_name IS NOT NULL AND c.middle_name <> '' THEN '. ' ELSE ' ' END,
                        CASE WHEN g.name IS NOT NULL THEN g.name ELSE '' END
                    ) as full_name"),
                    'b.no_of_days',
                    'd.name as position',
                    'h.percentage',
                    'i.name as department',
                    'b.salary_grade_id',
                    'b.salary_step_id',
                    'b.hazard_pay_setup_id',
                )
                ->distinct()
                ->where('c.salary', '>', 0)
                ->where('b.salary_grade_id', '>', 0)
                ->where('b.salary_step_id', '>', 0)
                ->where('a.department_id', $request->department_id)
                ->where('a.month_id', $request->month_id)
                ->where('a.year', $request->year)
                ->orderBy('h.percentage', 'asc')
                ->orderBy('full_name', 'asc')
                ->get();

            if ($hazard->isEmpty()) {
                return $this->errorResponse('No Hazard Pay data found.', 404);
            }

            $signatories = [
                'signatory1' => $request->signatory1,
                'signatory2' => $request->signatory2,
                'signatory3' => $request->signatory3,
                'signatory4' => $request->signatory4,
                'signatory5' => $request->signatory5,
            ];

            $pdf = PDF::loadView('hazard_pay.hazard_pay_print', compact('hazard', 'image', 'company', 'signatories'))
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('tabloid', 'landscape');
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'hazard_pay_report_' . $request->department_id . '_' . $request->month_id . '_' . $request->year . '_' . date('Y-m-d') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate hazard pay report: ' . $e->getMessage());
        }
    }
}
