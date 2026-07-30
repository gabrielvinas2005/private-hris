<?php

namespace App\Http\Controllers;

use PDF;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PayrollExtraBonusController extends Controller
{
    use ApiResponse;
    
    public function index()
    {
        try {
            $extra_bonus_payrolls = DB::table('extra_bonus_payroll_headers as a')
                ->join('extra_bonus_types as b', 'a.extra_bonus_type_id', '=', 'b.id')
                ->join('departments as c', 'a.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'b.name as extra_bonus_type',
                    'c.name as department',
                    'a.year_id',
                    'a.is_posted'
                )
                ->get();

            return $this->successResponse($extra_bonus_payrolls, 'Payroll extra bonus list retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve payroll extra bonus list: ' . $e->getMessage());
        }
    }

    public function show($extra_bonus_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $extra_bonuses = DB::table('extra_bonus_types')->where('active', true)->orderBy('name', 'asc')->get();
            $departments = DB::table('departments')->where('active', true)->orderBy('name', 'asc')->get();
            $extra_bonus_payrolls = DB::table("extra_bonus_payroll_headers")->where('id', $extra_bonus_id)->get();

            $employees = DB::table('extra_bonus_payroll_headers as a')
                ->join('extra_bonus_payroll_details as b', 'a.id', '=', 'b.extra_bonus_id')
                ->join('employees as c', 'b.employee_id', '=', 'c.id')
                ->leftJoin('positions as d', 'c.position_id', '=', 'd.id')
                ->select(
                    'c.id as employee_id',
                    'c.employee_no',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                        CONCAT(c.first_name,' ',c.last_name)
                    ELSE
                        RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                    END as name"),
                    'd.name as position',
                    'c.salary',
                    'b.amount'
                )
                ->where('a.id', $extra_bonus_id)
                ->orderBy('name', 'asc')
                ->get();

            if ($extra_bonus_payrolls->isEmpty()) {
                $extra_bonus_payrolls = [
                    'id' => 0,
                    'extra_bonus_type_id' => 0,
                    'department_id' => 0,
                    'year_id' => 0,
                    'is_posted' => false
                ];

                $extra_bonus_payrolls = (object)$extra_bonus_payrolls;
                $extra_bonus_payrolls = collect([$extra_bonus_payrolls]);
            }

            return $this->successResponse([
                'extra_bonuses' => $extra_bonuses,
                'departments' => $departments,
                'employees' => $employees,
                'extra_bonus_payrolls' => $extra_bonus_payrolls
            ], 'Payroll extra bonus form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load payroll extra bonus form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'extra_bonus_type_id' => 'required',
                'department_id' => 'required',
                'year_id' => 'required',
                'employee_id' => 'required|array',
                'employee_id.*' => 'required|integer',
                'amount' => 'required|array',
                'amount.*' => 'required|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $extra_bonus_id = $request->extra_bonus_id;
            $created_count = 0;
            $updated_count = 0;

            if ($extra_bonus_id == 0) {
                DB::table('extra_bonus_payroll_headers')->insert([
                    'extra_bonus_type_id' => $request->extra_bonus_type_id,
                    'department_id' => $request->department_id,
                    'year_id' => $request->year_id,
                ]);

                $extra_bonus_id = DB::table('extra_bonus_payroll_headers')->max('id');
            } else {
                DB::table('extra_bonus_payroll_headers')
                    ->where(['id' => $extra_bonus_id])
                    ->update([
                        'extra_bonus_type_id' => $request->extra_bonus_type_id,
                        'department_id' => $request->department_id,
                        'year_id' => $request->year_id,
                    ]);
            }

            $employee_data = $request->all();
            $data = [];

            for ($i = 0; $i < count($employee_data['employee_id']); $i++) {
                $data = [
                    'amount' => $employee_data['amount'][$i],
                ];

                // Check if record exists
                $existing = DB::table('extra_bonus_payroll_details')
                    ->where([
                        'extra_bonus_id' => $extra_bonus_id,
                        'employee_id' => $employee_data['employee_id'][$i]
                    ])
                    ->exists();

                DB::table('extra_bonus_payroll_details')->updateOrInsert([
                    'extra_bonus_id' => $extra_bonus_id,
                    'employee_id' => $employee_data['employee_id'][$i]
                ], $data);

                if ($existing) {
                    $updated_count++;
                } else {
                    $created_count++;
                }
            }

            return $this->successResponse([
                'extra_bonus_id' => $extra_bonus_id,
                'created_count' => $created_count,
                'updated_count' => $updated_count,
                'total_processed' => count($employee_data['employee_id'])
            ], 'Successfully Saved Extra Bonus Payroll!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save extra bonus payroll: ' . $e->getMessage());
        }
    }

    public function loadEmployees($extra_bonus_type_id, $department_id, $year_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $employee_data = DB::table('employees as a')
                ->join('positions as b', 'a.position_id', '=', 'b.id')
                ->select(
                    'a.id as employee_id',
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                        CONCAT(a.first_name,' ',a.last_name)
                    ELSE
                        RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                    END as name"),
                    'b.name as position',
                    'a.salary',
                    DB::raw("CAST(0 AS DECIMAL(18,2)) as amount")
                )
                ->where('a.department_id', $department_id)
                ->whereNotIn('a.id', function ($query) use ($extra_bonus_type_id, $department_id, $year_id) {
                    $query->select('b.employee_id')
                        ->from('extra_bonus_payroll_headers as a')
                        ->join('extra_bonus_payroll_details as b', 'a.id', '=', 'b.extra_bonus_id')
                        ->where([
                            'a.extra_bonus_type_id' => $extra_bonus_type_id,
                            'a.department_id' => $department_id,
                            'a.year_id' => $year_id,
                        ]);
                });

            $employees = DB::table('extra_bonus_payroll_headers as a')
                ->join('extra_bonus_payroll_details as b', 'a.id', '=', 'b.extra_bonus_id')
                ->join('employees as c', 'b.employee_id', '=', 'c.id')
                ->leftJoin('positions as d', 'c.position_id', '=', 'd.id')
                ->select(
                    'c.id as employee_id',
                    'c.employee_no',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                        CONCAT(c.first_name,' ',c.last_name)
                    ELSE
                        RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                    END as name"),
                    'd.name as position',
                    'c.salary',
                    'b.amount'
                )
                ->where([
                    'a.extra_bonus_type_id' => $extra_bonus_type_id,
                    'a.department_id' => $department_id,
                    'a.year_id' => $year_id,
                ])
                ->union($employee_data)
                ->orderBy('name', 'asc')
                ->get();

            return $this->successResponse($employees, 'Payroll extra bonus table retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load payroll extra bonus employees: ' . $e->getMessage());
        }
    }

    public function process($extra_bonus_id, $type_id)
    {
        try {
            if ($type_id == 1) {
                DB::table('extra_bonus_payroll_headers')->where('id', $extra_bonus_id)->update(['is_posted' => true]);

                return $this->successResponse(['extra_bonus_id' => $extra_bonus_id], 'Successfully Posted Extra Bonus Payroll!');
            } else {
                DB::table('extra_bonus_payroll_headers')->where('id', $extra_bonus_id)->update(['is_posted' => false]);

                return $this->successResponse(['extra_bonus_id' => $extra_bonus_id], 'Successfully Unposted Extra Bonus Payroll!');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process extra bonus payroll: ' . $e->getMessage());
        }
    }

    public function report()
    {
        try {
            $extra_bonuses = DB::table('extra_bonus_types')->where('active', true)->orderBy('name', 'asc')->get();
            $departments = DB::table('departments')->where('active', true)->orderBy('name', 'asc')->get();

            return $this->successResponse([
                'extra_bonuses' => $extra_bonuses,
                'departments' => $departments
            ], 'Payroll extra bonus report form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load payroll extra bonus report form data: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'extra_bonus_type_id' => 'required',
                'department_id' => 'required',
                'year_id' => 'required',
                'signatory_1' => 'required|string|max:255',
                'signatory_position_1' => 'required|string|max:255',
                'signatory_2' => 'required|string|max:255',
                'signatory_position_2' => 'required|string|max:255',
                'signatory_3' => 'required|string|max:255',
                'signatory_position_3' => 'required|string|max:255',
                'signatory_4' => 'required|string|max:255',
                'signatory_position_4' => 'required|string|max:255',
                'signatory_5' => 'required|string|max:255',
                'signatory_position_5' => 'required|string|max:255'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $extra_bonus_payrolls = DB::table('extra_bonus_payroll_headers as a')
                ->join('extra_bonus_payroll_details as b', 'a.id', '=', 'b.extra_bonus_id')
                ->join('employees as c', 'b.employee_id', '=', 'c.id')
                ->join('positions as d', 'c.position_id', '=', 'd.id')
                ->join('departments as e', 'c.department_id', '=', 'e.id')
                ->join('extra_bonus_types as f', 'a.extra_bonus_type_id', '=', 'f.id')
                ->select(
                    'c.id as employee_id',
                    'c.employee_no',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                        CONCAT(c.first_name,' ',c.last_name)
                    ELSE
                        RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                    END as name"),
                    'd.name as position',
                    'e.name as department',
                    'f.name as extra_bonus_type',
                    'f.code',
                    'c.salary',
                    'b.amount',
                    'a.year_id'
                )
                ->where([
                    'a.extra_bonus_type_id' => $request->extra_bonus_type_id,
                    'a.department_id' => $request->department_id,
                    'a.year_id' => $request->year_id,
                    'a.is_posted' => true,
                ])
                ->where('b.amount', '>', 0)
                ->orderBy('name', 'asc')
                ->get();

            if ($extra_bonus_payrolls->isEmpty()) {
                return $this->errorResponse('No data found for the selected criteria.');
            }

            $signatories = [
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
            ];
            
            $companies = DB::table('companies')->get();

            $pdf = PDF::loadView('payroll_extra_bonus.payroll_extra_bonus_print', compact(
                'extra_bonus_payrolls',
                'image',
                'signatories',
                'companies',
            ))
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('tabloid', 'landscape');
            $pdfContent = $pdf->output();

            $filename = 'payroll_extra_bonus_report_' . $request->extra_bonus_type_id . '_' . $request->department_id . '_' . $request->year_id . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate payroll extra bonus report: ' . $e->getMessage());
        }
    }
}
