<?php

namespace App\Http\Controllers;

use PDF;
use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RATAController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $rata_positions = DB::table('plantillas as a')
                ->join('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('rata_positions as c', 'a.id', '=', 'c.plantilla_id')
                ->select(
                    'c.id',
                    'a.id as plantilla_id',
                    'a.code',
                    'b.name as position',
                    'c.ra_amount',
                    'c.ta_amount'
                )
                ->orderBy('b.name', 'asc')
                ->get();

            return $this->successResponse($rata_positions, 'RATA positions data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve RATA positions data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            $validator = validator($request->all(), [
                'plantilla_id' => 'required|array',
                'plantilla_id.*' => 'required|exists:plantillas,id',
                'ra_amount' => 'required|array',
                'ra_amount.*' => 'numeric|min:0|max:999999.99',
                'ta_amount' => 'required|array',
                'ta_amount.*' => 'numeric|min:0|max:999999.99',
            ], [
                'ra_amount.*.numeric' => 'The RA Amount field must be a number.',
                'ra_amount.*.min' => 'The RA Amount field must be a positive number.',
                'ra_amount.*.max' => 'The RA Amount field must be less than or equal to 999999.99.',
                'ta_amount.*.numeric' => 'The TA Amount field must be a number.',
                'ta_amount.*.min' => 'The TA Amount field must be a positive number.',
                'ta_amount.*.max' => 'The TA Amount field must be less than or equal to 999999.99.',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            if (count($data["plantilla_id"]) == 0) {
                return $this->errorResponse('No RATA Positions to Save!', 400);
            }

            $rata_data = [];

            for ($i = 0; $i < count($data["plantilla_id"]); $i++) {
                $id = $data['plantilla_id'][$i];
                $ext_data = DB::table('rata_positions')->where('plantilla_id', $id)->get();
                $ra_amount = $data['ra_amount'][$i];
                $ta_amount = $data['ta_amount'][$i];

                if (count($ext_data) > 0) {
                    $rata_data = [
                        'ra_amount' => $ra_amount,
                        'ta_amount' => $ta_amount
                    ];

                    DB::table('rata_positions')->where('plantilla_id', $id)->update($rata_data);
                } else {
                    $rata_data = [
                        'plantilla_id' => $id,
                        'ra_amount' => $ra_amount,
                        'ta_amount' => $ta_amount
                    ];

                    DB::table('rata_positions')->insert($rata_data);
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'RATA Positions Setup',
                'activity' => 'Update',
                'description' => 'Updated RATA Positions informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'RATA positions updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update RATA positions: ' . $e->getMessage());
        }
    }

    public function loadRATATable()
    {
        try {
            $data = DB::table('rata_tables')->orderBy('percentage', 'asc')->get();

            return $this->successResponse($data, 'RATA table data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve RATA table data: ' . $e->getMessage());
        }
    }

    public function storeRATATable(Request $request)
    {
        try {
            $data = $request->all();

            $arr_len = count($data['id']);

            $rata_data = [];

            for ($i = 1; $i < $arr_len; $i++) {
                if ($data['day_from'][$i] != NULL) {

                    //if else for checking if the value is negative
                    if ($data['day_from'][$i] < 0 && $data['day_to'][$i] > 0 && $data['percentage'][$i] > 0) {
                        return $this->errorResponse('Work Day From cannot be negative.');
                    } else if ($data['day_from'][$i] > 0 && $data['day_to'][$i] < 0 && $data['percentage'][$i] > 0) {
                        return $this->errorResponse('Work Day To cannot be negative.');
                    } else if ($data['day_from'][$i] > 0 && $data['day_to'][$i] > 0 && $data['percentage'][$i] < 0) {
                        return $this->errorResponse('Percentage cannot be negative.');
                    } else if ($data['day_to'][$i] == 0 || $data['day_to'][$i] == '') {
                        return $this->errorResponse('No RATA Work Day To data to save.');
                    } else if ($data['percentage'][$i] == 0 || $data['day_to'][$i] == '') {
                        return $this->errorResponse('No RATA Percentage data to save.');
                    }

                    if ($data['id'][$i] == null) {
                        $id = DB::table('rata_tables')->max('id') + 1;
                    } else {
                        $id = $data['id'][$i];
                    }

                    $day_from = $data['day_from'][$i];
                    $day_to = $data['day_to'][$i];
                    $percentage = $data['percentage'][$i];

                    $rules = [
                        "day_from.$i" => 'numeric|min:1|max:999',
                        "day_to.$i" => 'numeric|min:1|max:999',
                        "percentage.$i" => 'numeric|min:0.01|max:999.99',
                    ];

                    $messages = [
                        "day_from.$i.numeric" => 'The Work Day From field must be a number.',
                        "day_from.$i.min" => 'The Work Day From field must be a positive number.',
                        "day_from.$i.max" => 'The Work Day From field must be less than or equal to 999.',
                        "day_to.$i.numeric" => 'The Work Day To field must be a number.',
                        "day_to.$i.min" => 'The Work Day To field must be a positive number.',
                        "day_to.$i.max" => 'The Work Day To field must be less than or equal to 999.',
                        "percentage.$i.numeric" => 'The Percentage field must be a number.',
                        "percentage.$i.min" => 'The Percentage field must be a positive number.',
                        "percentage.$i.max" => 'The Percentage field must be less than or equal to 999.',
                    ];

                    $validator = Validator::make($data, $rules, $messages);

                    if ($validator->fails()) {
                        return $this->validationErrorResponse($validator->errors());
                    }

                    $rata_data = [
                        'day_from' => $day_from,
                        'day_to' => $day_to,
                        'percentage' => $percentage
                    ];

                    DB::unprepared('SET IDENTITY_INSERT rata_tables ON');
                    DB::table('rata_tables')->updateOrInsert(['id' => $id], $rata_data);
                    DB::unprepared('SET IDENTITY_INSERT rata_tables OFF');
                } else {
                    return $this->errorResponse('No RATA Work Day From data to save.');
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'RATA Table Setup',
                'activity' => 'Update',
                'description' => 'Updated RATA Table informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'You have successfully updated RATA Table!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update RATA table: ' . $e->getMessage());
        }
    }

    public function deleteRATATable($id)
    {
        try {
            $data = DB::table('rata_tables')->where('id', $id)->delete();

            return $this->successResponse(['deleted' => $data], 'RATA table record deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete RATA table record: ' . $e->getMessage());
        }
    }

    public function loadRATAPayroll()
    {
        try {
            $rata = DB::table('rata_payroll_headers as a')
                ->leftJoin('branches as b', 'a.branch_id', '=', 'b.id')
                ->select(
                    'a.*',
                    'b.name as branch'
                )
                ->get();

            return $this->successResponse($rata, 'RATA payroll data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve RATA payroll data: ' . $e->getMessage());
        }
    }

    public function addRATAPayroll($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $branches = DB::table('branches')->get();

            if ($id != 0) {
                $data = DB::table('rata_payroll_headers as a')
                    ->leftJoin('rata_payroll_details as b', 'a.id', '=', 'b.rata_id')
                    ->leftJoin('employees as c', 'b.employee_id', '=', 'c.id')
                    ->leftJoin('positions as d', 'c.position_id', '=', 'd.id')
                    ->select(
                        'a.id',
                        'b.id as dtl_id',
                        'a.branch_id',
                        'a.month_id',
                        'a.year_id',
                        'c.employee_no',
                        DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                    CONCAT(c.first_name,' ',c.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                                END as name"),
                        'd.name as position',
                        'b.ra_amount',
                        'b.ta_amount',
                        'a.posted',
                        'a.rata_type_id',
                        'b.no_of_days_absent',
                        'b.rata_percentage',
                        'b.use_vehicle_rp_ta',
                        'b.total_deduction',
                        'b.amount_earned',
                        'b.net_amount',
                        'c.plantilla_id'
                    )
                    ->where('a.id', $id)
                    ->orderBy('name', 'asc')
                    ->get();
            } else {
                $data_dummy = array(
                    'id' => 0,
                    'dtl_id' => 0,
                    'branch_id' => 0,
                    'month_id' => 0,
                    'year_id' => 0,
                    'employee_no' => '',
                    'name' => '',
                    'position' => '',
                    'ra_amount' => 0,
                    'ta_amount' => 0,
                    'posted' => false,
                    'rata_type_id' => 0,
                    'no_of_days_absent' => 0,
                    'rata_percentage' => 0,
                    'use_vehicle_rp_ta' => 0,
                    'total_deduction' => 0,
                    'amount_earned' => 0,
                    'net_amount' => 0,
                    'plantilla_id' => 0,
                );

                $data = (object)$data_dummy;
                $data = collect([$data]);
            }

            $rata_employees = DB::table('employees as a')
                ->join('plantillas as b', 'a.id', '=', 'b.employee_id')
                ->join('positions as c', 'b.position_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                    UPPER(CONCAT(a.first_name,' ',a.last_name))
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                                END as name"),
                    'b.code',
                    'c.name as position',
                    'b.id as plantilla_id'
                )
                ->where([
                    'a.active' => true,
                    'a.is_employee' => true,
                    'a.is_plantilla' => true
                ])
                ->whereNotIn('a.id', function ($query) use ($id) {
                    $query->select('employee_id')->from('rata_payroll_details')->where('rata_id', $id);
                })
                ->orderBy('a.first_name', 'asc')
                ->get();

            return $this->successResponse([
                'data' => $data,
                'branches' => $branches,
                'rata_employees' => $rata_employees
            ], 'RATA payroll form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load RATA payroll form data: ' . $e->getMessage());
        }
    }

    public function storeRATAPayroll(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'rata_type_id' => 'required',
                'branch_id' => 'required',
                'month_id' => 'required',
                'year_id' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $rata_exist = DB::table('rata_payroll_headers')
                ->where([
                    'rata_type_id' => $request->rata_type_id,
                    'branch_id' => $request->branch_id,
                    'month_id' => $request->month_id,
                    'year_id' => $request->year_id
                ])
                ->where('id', '<>', $id)
                ->count();

            if ($rata_exist > 0) {
                return $this->errorResponse('Existing RATA Payroll with the same Month and Year under the same Branch!');
            }

            if ($id == 0) {
                $id = 1 + DB::table('rata_payroll_headers')->max('id');
            }

            if ($request->month_id == 1) {
                $month = 'January';
            } elseif ($request->month_id == 2) {
                $month = 'February';
            } elseif ($request->month_id == 3) {
                $month = 'March';
            } elseif ($request->month_id == 4) {
                $month = 'April';
            } elseif ($request->month_id == 5) {
                $month = 'May';
            } elseif ($request->month_id == 6) {
                $month = 'June';
            } elseif ($request->month_id == 7) {
                $month = 'July';
            } elseif ($request->month_id == 8) {
                $month = 'August';
            } elseif ($request->month_id == 9) {
                $month = 'September';
            } elseif ($request->month_id == 10) {
                $month = 'October';
            } elseif ($request->month_id == 11) {
                $month = 'November';
            } elseif ($request->month_id == 12) {
                $month = 'December';
            }

            $data = [
                'rata_type_id' => $request->rata_type_id,
                'branch_id' => $request->branch_id,
                'month_id' => $request->month_id,
                'month' => $month,
                'year_id' => $request->year_id
            ];

            DB::unprepared('SET IDENTITY_INSERT rata_payroll_headers ON');
            DB::table('rata_payroll_headers')->updateOrInsert(['id' => $id], $data);
            DB::unprepared('SET IDENTITY_INSERT rata_payroll_headers OFF');

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'RATA Payroll',
                'activity' => 'Create',
                'description' => 'Created RATA Payroll informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Successfully Created RATA Payroll!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create RATA payroll: ' . $e->getMessage());
        }
    }

    public function storeRATAPayrollEmployees(Request $request, $id)
    {
        try {
            $employee_data = $request->all();

            $data = [];

            $rata_header = DB::table('rata_payroll_headers')->where('id', $id)->get();

            $month_id = $rata_header[0]->month_id;
            $year_id = $rata_header[0]->year_id;
            $rata_type_id = $rata_header[0]->rata_type_id ?? 0;

            if (isset($employee_data['id'])) {
                $arr_len = count($employee_data['id']);
                for ($i = 0; $i < $arr_len; $i++) {
                    if ($employee_data['id'][$i] != NULL) {
                        if (in_array($employee_data['id'][$i], $employee_data['select'])) {

                            // Compute RATA Amounts
                            // get attendance and work days
                            $attendance = DB::table('time_data')
                                ->select(
                                    DB::raw("CAST(SUM(work_hours) AS DECIMAL(18,2)) as attendance")
                                )
                                ->where(DB::raw("DATEPART(MONTH,date)"), $month_id)
                                ->where(DB::raw("DATEPART(YEAR,date)"), $year_id)
                                ->where('employee_id', $employee_data['id'][$i])
                                ->get();

                            $work_days = 0;

                            if ($attendance->isNotEmpty()) {
                                $work_days = round($attendance[0]->attendance / 8, 0);
                            } else {
                                $work_days = 0;
                            }

                            if ($work_days == null) {
                                $work_days = 0;
                            }

                            if ($rata_type_id == 1) {
                                $no_of_days_absent = 0;
                                $rata_percentage = 100;
                            } else {
                                $no_of_days_absent = (22 - $work_days);

                                // get RATA perncentage
                                $rata_table = DB::table('rata_tables')
                                    ->whereRaw('day_from <=' . $work_days)
                                    ->whereRaw('day_to >=' . $work_days)
                                    ->get();

                                if ($rata_table->isNotEmpty()) {
                                    $rata_percentage = $rata_table[0]->percentage;
                                } else {
                                    $rata_percentage = 0;
                                }
                            }

                            // get rata amount
                            $ra_amount = 0;
                            $ta_amount = 0;

                            $rata_position = DB::table('rata_positions')
                                ->select(
                                    'ra_amount',
                                    'ta_amount'
                                )
                                ->where('plantilla_id', $employee_data['plantilla_id'][$i])
                                ->get();

                            if ($rata_position->isNotEmpty()) {
                                $ra_amount = (($rata_percentage / 100) * $rata_position[0]->ra_amount);
                                $ta_amount = (($rata_percentage / 100) * $rata_position[0]->ta_amount);
                            } else {
                                $ra_amount = 0;
                                $ta_amount = 0;
                            }

                            $total_earned = ($ra_amount + $ta_amount);

                            $data = [
                                'rata_id' => $id,
                                'employee_id' => $employee_data['id'][$i],
                                'ra_amount' => $ra_amount,
                                'ta_amount' => $ta_amount,
                                'no_of_days_absent' => $no_of_days_absent <= 0 ? 0 : $no_of_days_absent,
                                'rata_percentage' => $rata_percentage,
                                'use_vehicle_rp_ta' => 0,
                                'total_deduction' => 0,
                                'amount_earned' => $total_earned,
                                'net_amount' => $total_earned,
                            ];

                            DB::table('rata_payroll_details')->insert($data);
                        }
                    }
                }
            }

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'RATA Payroll',
                'activity' => 'Added',
                'description' => 'Added RATA Employees.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Successfully Added Employees to RATA Payroll!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add employees to RATA payroll: ' . $e->getMessage());
        }
    }

    public function updateRATAPayroll(Request $request, $id)
    {
        try {
            $employee_data = $request->all();
            $data = [];

            $rata_header = DB::table('rata_payroll_headers')->where('id', $id)->get();
            $rata_type_id = $rata_header[0]->rata_type_id ?? 0;

            $arr_len = count($employee_data['rata_detail_id']);
            for ($i = 0; $i < $arr_len; $i++) {

                $use_vehicle_rp_ta = $employee_data['use_vehicle_rp_ta'][$i];
                $no_of_days_absent = $employee_data['no_of_days_absent'][$i];
                $rata_percentage = $employee_data['rata_percentage'][$i];

                // Compute RATA Amounts
                if ($rata_type_id == 1) {
                    // $no_of_days_absent = 0;
                    $rata_percentage = 100;
                }

                // get rata amount
                $ra_amount = 0;
                $ta_amount = 0;

                $rata_position = DB::table('rata_positions')
                    ->select(
                        'ra_amount',
                        'ta_amount'
                    )
                    ->where('plantilla_id', $employee_data['plantilla_id'][$i])
                    ->get();

                if ($rata_position->isNotEmpty()) {
                    $ra_amount = (($rata_percentage / 100) * $rata_position[0]->ra_amount);
                    $ta_amount = (($rata_percentage / 100) * $rata_position[0]->ta_amount);
                } else {
                    $ra_amount = 0;
                    $ta_amount = 0;
                }

                $total_earned = ($ra_amount + $ta_amount);
                $net_amount = (($ra_amount + $ta_amount) - $use_vehicle_rp_ta);

                $data = [
                    'rata_id' => $id,
                    'ra_amount' => $ra_amount,
                    'ta_amount' => $ta_amount,
                    'no_of_days_absent' => $no_of_days_absent <= 0 ? 0 : $no_of_days_absent,
                    'rata_percentage' => $rata_percentage,
                    'use_vehicle_rp_ta' => $use_vehicle_rp_ta,
                    'total_deduction' => $use_vehicle_rp_ta,
                    'amount_earned' => $total_earned,
                    'net_amount' => $net_amount,
                ];

                DB::table('rata_payroll_details')->updateOrInsert(['id' => $employee_data['rata_detail_id'][$i]], $data);
            }

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'RATA Payroll',
                'activity' => 'Updated',
                'description' => 'Updated RATA Employees.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Successfully Updated Employees to RATA Payroll!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update RATA payroll employees: ' . $e->getMessage());
        }
    }

    public function deleteRATAPayrollEmployees($id)
    {
        try {
            $data = DB::table('rata_payroll_details')->where('id', $id)->delete();

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'RATA Payroll',
                'activity' => 'Delete',
                'description' => 'Deleted RATA Employees.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['deleted' => $data], 'RATA payroll employee deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete RATA payroll employee: ' . $e->getMessage());
        }
    }

    public function processRATAPayroll($id, $type_id)
    {
        try {
            $with_setup = DB::table('rata_tables')->get();

            if ($with_setup->isEmpty()) {
                return $this->errorResponse('There is no RATA Setup.');
            }

            $with_employees = DB::table('rata_payroll_details')->where('rata_id', $id)->get();

            if ($with_employees->isEmpty()) {
                return $this->errorResponse('Please add atleast one employee.');
            }

            if ($type_id == 1) {
                DB::table('rata_payroll_headers')->where('id', $id)->update(['posted' => true]);

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'RATA Payroll',
                    'activity' => 'Posted',
                    'description' => 'Posted RATA Payroll.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'Successfully Posted RATA Payroll!');
            } else {
                DB::table('rata_payroll_headers')->where('id', $id)->update(['posted' => false]);

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'RATA Payroll',
                    'activity' => 'Unposted',
                    'description' => 'Unposted RATA Payroll.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'Successfully Unposted RATA Payroll!');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process RATA payroll: ' . $e->getMessage());
        }
    }

    public function rataReport()
    {
        try {
            $app_key = env("APP_KEY", "");

            $rata_payroll = DB::table('rata_payroll_headers as a')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN a.rata_type_id = 1 THEN
                            CONCAT(a.month,' ',a.year_id,' (DEPARTMENT HEADS & ASSISTANTS - RATA)') 
                        ELSE
                            CONCAT(a.month,' ',a.year_id,' (SANGGUNIANG BAYAN COUNCIL - RATA)') 
                        END as name")
                )
                ->join('branches as b', 'a.branch_id', '=', 'b.id')
                ->where('posted', true)
                ->get();

            $signatories = DB::table('employees')
                ->select(
                    'id',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',employees.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                                END as name")
                )
                ->where([
                    'active' => true,
                    'is_employee' => true
                ])
                ->get();

            return $this->successResponse([
                'rata_payroll' => $rata_payroll,
                'signatories' => $signatories
            ], 'RATA report data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load RATA report data: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'rata_payroll_id' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

            $rata_data = DB::table('rata_payroll_headers')->where('id', $request->rata_payroll_id)->get();

            // save signatories
            $signatories = [
                'report_name' => 'rata_payroll_report',
                'signatory_1' => $request->signatory_1,
                'signatory_position_1' => $request->signatory_position_1,
                'signatory_2' => $request->signatory_2,
                'signatory_position_2' => $request->signatory_position_2,
                'signatory_3' => $request->signatory_3,
                'signatory_position_3' => $request->signatory_position_3,
                'signatory_4' => $request->signatory_4,
                'signatory_position_4' => $request->signatory_position_4,
                'signatory_5' => $request->signatory_5,
                'signatory_position_5' => $request->signatory_position_5,
                'payroll_id' => $request->rata_payroll_id,
                'branch_id' => isset($rata_data[0]->branch_id) ? $rata_data[0]->branch_id : 0
            ];

            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $company = DB::table('companies')->get();

            $rata = DB::table('rata_payroll_headers as a')
                ->join('rata_payroll_details as b', 'a.id', '=', 'b.rata_id')
                ->join('employees as c', 'b.employee_id', '=', 'c.id')
                ->join('positions as d', 'c.position_id', '=', 'd.id')
                ->select(
                    'a.id',
                    'b.employee_id',
                    'c.employee_no',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                    CONCAT(c.first_name,' ',c.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                                END as name"),
                    'b.ra_amount',
                    'b.ta_amount',
                    'b.no_of_days_absent',
                    'b.rata_percentage',
                    'b.use_vehicle_rp_ta',
                    'b.total_deduction',
                    'b.amount_earned',
                    'b.net_amount',
                    'd.name as position',
                    'a.month',
                    'a.year_id',
                    'a.rata_type_id'
                )
                ->where('a.id', $request->rata_payroll_id)
                ->orderBy('name', 'asc')
                ->get();

            if ($rata->isEmpty()) {
                $rata = [];
            }

            $pdf = PDF::loadView('rata_positions.rata_payroll_print', compact(
                'rata',
                'image',
                'company',
                'signatories'
            ))
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('tabloid', 'landscape');
            $pdfContent = $pdf->output();

            $filename = 'rata_payroll_report_' . $request->rata_payroll_id . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate RATA payroll report: ' . $e->getMessage());
        }
    }
}
