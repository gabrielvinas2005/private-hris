<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class AssumptionOfDutyController extends Controller
{
    use ApiResponse, GeneratesPdf;
use App\Traits\GeneratesPdf;

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
            $app_key = env("APP_KEY", "");

            $data = DB::table('employees')
                ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
                ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                ->leftJoin('employee_promotions', 'employees.id', '=', 'employee_promotions.employee_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->select(
                    'employees.photo',
                    DB::raw("CASE WHEN ISNULL(employee_promotions.id,0) = 0 THEN
                                    employees.id
                                ELSE
                                    employee_promotions.id
                                END as id"),
                    'employees.employee_no',
                    'employees.email',
                    'employment_types.name as employment_type',
                    'positions.name as position',
                    'departments.name as department',
                    'branches.name as branch',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',substring(employees.middle_name,1,1),'. ',employees.last_name,' - ',positions.name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](employees.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')+' - '+RTRIM(positions.name))
                                END as name"),
                )
                ->orderBy('employees.first_name', 'asc')
                ->where(['employees.is_employee' => true, 'employees.active' => true])
                ->paginate(10000);

            return $this->successResponse($data, 'Assumption of duty records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve assumption of duty records: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

            if ($request->employee == null) {
                return $this->errorResponse('Please select employee.', 400);
            }

            $appointments = DB::table('employee_promotions as a')
                ->rightJoin('employees as b', 'a.employee_id', '=', 'b.id')
                ->leftJoin('positions as c', 'b.position_id', '=', 'c.id')
                ->leftJoin('departments as d', 'b.department_id', '=', 'd.id')
                ->leftJoin('genders as e', 'b.gender_id', '=', 'e.id')
                ->leftJoin('name_prefixes as f', 'b.name_prefix_id', '=', 'f.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as name"),
                    'c.name as position',
                    'd.name as department',
                    'e.name as gender',
                    'a.date_of_effectivity',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted, 0) = 0 THEN
                                CONCAT(f.name, ' ', b.last_name)
                                    ELSE CONCAT(f.name, ' ', RTRIM([dbo].[ufn_DecryptString](b.last_name, '$app_key')))
                                END as name_sig"),
                )
                ->where('a.id', $request->employee)
                ->orWhere('b.id', $request->employee)
                ->get();

            if ($appointments->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $signatories = array(
                'signatory' => $request->signatory,
                'position' => $request->position,
                'assested_date' => $request->assested_date,
                'assested_signatory' => $request->assested_signatory,
                'assested_position' => $request->assested_position
            );

            $pdf = PDF::loadView('assumption_of_duty.assumption_of_duty_print', compact('appointments', 'signatories'))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            
            // Generate PDF content as base64 for API response
            $pdfContent = $pdf->output();
            $base64Content = base64_encode($pdfContent);
            
            $filename = 'assumption_of_duty_' . $request->employee . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate assumption of duty PDF: ' . $e->getMessage());
        }
    }
}
