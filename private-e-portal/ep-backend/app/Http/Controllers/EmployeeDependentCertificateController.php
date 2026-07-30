<?php

namespace App\Http\Controllers;

use PDF;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeDependentCertificateController extends Controller
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
            $app_key = env("APP_KEY", "");

            $data = DB::table('employees')
                ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
                ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                ->leftJoin('employee_dependents as b', 'employees.id', '=', 'b.employee_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->select(
                    'employees.photo',
                    'employees.id',
                    'employees.first_name',
                    'employees.employee_no',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN employees.email ELSE dbo.ufn_DecryptString(employees.email,'$app_key') END as email"),
                    'employment_types.name as employment_type',
                    'positions.name as position',
                    'departments.name as department',
                    'branches.name as branch',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                CONCAT(employees.first_name,' ',employees.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                            END as name")
                )
                ->distinct()
                ->where(['employees.is_employee' => true, 'employees.active' => true])
                ->orderBy('employees.first_name', 'asc')
                ->get();

            return $this->successResponse($data, 'Employee dependent certificates retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee dependent certificates: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");
            $companies = DB::table('companies')->get();

            $validator = validator($request->all(), [
                'employee' => 'required|integer|exists:employees,id',
                'signatory' => 'required|string|min:1',
                'position' => 'required|string|min:1',
                'semester' => 'required|string|in:1st,2nd',
                'year_from' => 'required|integer|min:2000|max:2100',
                'year_to' => 'required|integer|min:2000|max:2100|gte:year_from'
            ], [
                'employee.required' => 'Employee is required.',
                'employee.exists' => 'Selected employee does not exist.',
                'signatory.required' => 'Signatory name is required.',
                'position.required' => 'Signatory position is required.',
                'semester.required' => 'Semester is required.',
                'semester.in' => 'Semester must be either 1st or 2nd.',
                'year_from.required' => 'Year from is required.',
                'year_from.min' => 'Year from must be at least 2000.',
                'year_from.max' => 'Year from cannot exceed 2100.',
                'year_to.required' => 'Year to is required.',
                'year_to.min' => 'Year to must be at least 2000.',
                'year_to.max' => 'Year to cannot exceed 2100.',
                'year_to.gte' => 'Year to must be greater than or equal to year from.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $employees = DB::table('employees as a')
                ->join('positions as b', 'a.position_id', '=', 'b.id')
                ->join('departments as c', 'a.department_id', '=', 'c.id')
                ->join('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    'a.salary',
                    'a.date_hired',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(e.name,' ',a.last_name)
                            ELSE
                                RTRIM(e.name)+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name_sig")
                )
                ->where('a.id', $request->employee)
                ->get();

            if ($employees->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $dependents = DB::table('employee_dependents as a')
                ->where('a.employee_id', $request->employee)
                ->get();

            $signatories = array(
                'signatory' => $request->signatory,
                'position' => $request->position,
                'semester' => $request->semester,
                'year_from' => $request->year_from,
                'year_to' => $request->year_to,
            );

            $pdf = PDF::loadView('employee_dependent_certificates.employee_dependent_certificate_print', compact(
                'employees',
                'dependents',
                'signatories',
                'image',
                'companies'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdf_content = $pdf->output();

            $filename = "dependent_certificate_{$request->employee}_" . date('Y-m-d') . ".pdf";
            
            return response($pdf_content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdf_content));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate employee dependent certificate PDF: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $employee = DB::table('employees as a')
                ->join('positions as b', 'a.position_id', '=', 'b.id')
                ->join('departments as c', 'a.department_id', '=', 'c.id')
                ->join('employment_types as d', 'a.employment_type_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->select(
                    'a.id',
                    'a.employee_no',
                    'a.email',
                    'a.date_hired',
                    'a.salary',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                    'b.name as position',
                    'c.name as department',
                    'd.name as employment_type',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(e.name,' ',a.last_name)
                            ELSE
                                RTRIM(e.name)+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name_sig")
                )
                ->where('a.id', $id)
                ->first();

            if (!$employee) {
                return $this->notFoundResponse('Employee not found');
            }

            $dependents = DB::table('employee_dependents as a')
                ->where('a.employee_id', $id)
                ->get();

            return $this->successResponse([
                'employee' => $employee,
                'dependent_data' => [
                    'dependents' => $dependents,
                    'dependent_count' => $dependents->count()
                ],
                'summary' => [
                    'total_dependents' => $dependents->count(),
                    'dependent_details' => $dependents->map(function($dependent) {
                        return [
                            'name' => $dependent->name,
                            'relationship' => $dependent->relationship,
                            'birth_date' => $dependent->birth_date
                        ];
                    })
                ]
            ], 'Employee dependent certificate data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee dependent certificate data: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            $app_key = env("APP_KEY", "");

            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('employee_dependents as d', 'a.id', '=', 'd.employee_id')
                ->select(
                    'a.id',
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))
                            END as name"),
                    'b.name as position',
                    'c.name as department',
                    DB::raw('COUNT(d.id) as dependent_count')
                )
                ->where(['a.is_employee' => true, 'a.active' => true])
                ->groupBy('a.id', 'a.employee_no', 'a.is_encrypted', 'a.first_name', 'a.last_name', 'b.name', 'c.name')
                ->having('dependent_count', '>', 0)
                ->orderBy('a.first_name', 'asc')
                ->get();

            return $this->successResponse([
                'employees' => $employees,
                'fields' => [
                    'employee' => ['type' => 'select', 'required' => true, 'label' => 'Employee'],
                    'signatory' => ['type' => 'text', 'required' => true, 'label' => 'Signatory Name'],
                    'position' => ['type' => 'text', 'required' => true, 'label' => 'Signatory Position'],
                    'semester' => ['type' => 'select', 'required' => true, 'label' => 'Semester', 'options' => ['1st' => '1st Semester', '2nd' => '2nd Semester']],
                    'year_from' => ['type' => 'number', 'required' => true, 'label' => 'Year From', 'min' => 2000, 'max' => 2100],
                    'year_to' => ['type' => 'number', 'required' => true, 'label' => 'Year To', 'min' => 2000, 'max' => 2100]
                ]
            ], 'Create employee dependent certificate form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }
}
