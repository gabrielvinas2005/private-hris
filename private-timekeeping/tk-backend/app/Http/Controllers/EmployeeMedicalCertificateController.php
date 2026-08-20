<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Traits\GeneratesPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeMedicalCertificateController extends Controller
{
    use ApiResponse, GeneratesPdf;
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
            ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
            ->select(
                'employees.photo',
                'employees.id',
                'employees.employee_no',
                'employees.email',
                'employees.date_hired',
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
            ->orderBy('employees.first_name', 'asc')
            ->get();

        return $this->successResponse($data, 'Employee medical certificates retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee medical certificates: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
        $app_key = env("APP_KEY", "");
        $companies = DB::table('companies')->get();

            $validator = validator($request->all(), [
                'employee' => 'required|integer|exists:employees,id'
            ], [
                'employee.required' => 'Employee is required.',
                'employee.exists' => 'Selected employee does not exist.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
        }

        $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

        $employees = DB::table('employees as a')
            ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
            ->leftJoin('genders as c', 'a.gender_id', '=', 'c.id')
            ->leftJoin('civil_status as d', 'a.civil_status_id', '=', 'd.id')
            ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
            ->leftJoin('branches as br', 'br.id', '=', 'a.branch_id')
            ->leftJoin('companies as cp', 'cp.id', '=', 'a.company_id')
            ->select(
                'a.id',
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                        CONCAT(a.first_name,' ',a.last_name,' ',a.middle_name)
                    ELSE
                        RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.middle_name,'$app_key'))
                    END as name"),
                'a.pa_region',
                'a.pa_province',
                'a.pa_city',
                'a.pa_barangay',
                DB::raw("CONCAT(
                        COALESCE(a.pa_village, ''), ' ',
                        COALESCE(a.pa_street, ''), ' ',
                        COALESCE(a.pa_house_no, '')
                    ) as partial_address"),
                'a.age',
                'c.name as gender',
                'd.name as civil_status',
                'a.salary',
                DB::raw("isnull(a.salary,0) * 12 as annual_salary"),
                'a.date_hired',
                'a.gender_id',
                'b.name as position',
                DB::raw("CONCAT(e.name,' ',a.last_name) as name_sig"),
                DB::raw("CASE WHEN ISNULL(a.branch_id,0) <> 0 THEN
                          CASE WHEN br.is_main_branch = 1 THEN
                                CAST(1 as INT)
                               ELSE
                                CAST(0 as INT)
                           END
                          ELSE
                           CAST(2 AS INT)
                     END AS from_branch"),
                     'cp.name as company'
            )
            ->where('a.id', $request->employee)
            ->get();

            if ($employees->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
        }

        // Load JSON data for regions, provinces, cities, and barangays
        $region_data = json_decode(file_get_contents('refregion.json'), true)["RECORDS"];
        $province_data = json_decode(file_get_contents('refprovince.json'), true)["RECORDS"];
        $city_data = json_decode(file_get_contents('refcitymun.json'), true)["RECORDS"];
        $barangay_data = json_decode(file_get_contents('refbrgy.json'), true)["RECORDS"];

        // Function to get the description from JSON data
        function getDescription($data, $codeKey, $code, $descKey)
        {
            $result = collect($data)->firstWhere($codeKey, $code);
            return $result ? $result[$descKey] : '';
        }

        // Map codes to their descriptive names
        $address = [
            'pa_region' => getDescription($region_data, 'regCode', $employees[0]->pa_region, 'regDesc'),
            'pa_province' => getDescription($province_data, 'provCode', $employees[0]->pa_province, 'provDesc'),
            'pa_city' => getDescription($city_data, 'citymunCode', $employees[0]->pa_city, 'citymunDesc'),
            'pa_barangay' => getDescription($barangay_data, 'brgyCode', $employees[0]->pa_barangay, 'brgyDesc'),
        ];

        // Combine partial address with full address details
        $employees[0]->address = trim(
            "{$employees[0]->partial_address}, {$address['pa_barangay']}, {$address['pa_city']}, {$address['pa_province']}, {$address['pa_region']}"
        );

        // Generate PDF
        $pdf = PDF::loadView('employee_medical_certificates.employee_medical_certificate_print', compact(
            'employees'
        ))->setOptions(['defaultFont' => 'sans-serif']);
        $pdf->setPaper('A4');
            $pdf_content = $pdf->output();

            $filename = "medical_certificate_{$request->employee}_" . date('Y-m-d') . ".pdf";
            
            return response($pdf_content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdf_content));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate employee medical certificate PDF: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $employee = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('genders as c', 'a.gender_id', '=', 'c.id')
                ->leftJoin('civil_status as d', 'a.civil_status_id', '=', 'd.id')
                ->leftJoin('name_prefixes as e', 'a.name_prefix_id', '=', 'e.id')
                ->leftJoin('branches as br', 'br.id', '=', 'a.branch_id')
                ->leftJoin('companies as cp', 'cp.id', '=', 'a.company_id')
                ->select(
                    'a.id',
                    'a.employee_no',
                    'a.email',
                    'a.date_hired',
                    'a.age',
                    'a.salary',
                    DB::raw("isnull(a.salary,0) * 12 as annual_salary"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                            CONCAT(a.first_name,' ',a.last_name,' ',a.middle_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.middle_name,'$app_key'))
                        END as name"),
                    'a.pa_region',
                    'a.pa_province',
                    'a.pa_city',
                    'a.pa_barangay',
                    'a.pa_village',
                    'a.pa_street',
                    'a.pa_house_no',
                    'c.name as gender',
                    'd.name as civil_status',
                    'b.name as position',
                    'cp.name as company',
                    DB::raw("CASE WHEN ISNULL(a.branch_id,0) <> 0 THEN
                              CASE WHEN br.is_main_branch = 1 THEN
                                    CAST(1 as INT)
                                   ELSE
                                    CAST(0 as INT)
                               END
                              ELSE
                               CAST(2 AS INT)
                         END AS from_branch")
                )
                ->where('a.id', $id)
                ->first();

            if (!$employee) {
                return $this->notFoundResponse('Employee not found');
            }

            // Load JSON data for regions, provinces, cities, and barangays
            $region_data = json_decode(file_get_contents('refregion.json'), true)["RECORDS"];
            $province_data = json_decode(file_get_contents('refprovince.json'), true)["RECORDS"];
            $city_data = json_decode(file_get_contents('refcitymun.json'), true)["RECORDS"];
            $barangay_data = json_decode(file_get_contents('refbrgy.json'), true)["RECORDS"];

            // Function to get the description from JSON data
            function getDescription($data, $codeKey, $code, $descKey)
            {
                $result = collect($data)->firstWhere($codeKey, $code);
                return $result ? $result[$descKey] : '';
            }

            // Map codes to their descriptive names
            $address = [
                'pa_region' => getDescription($region_data, 'regCode', $employee->pa_region, 'regDesc'),
                'pa_province' => getDescription($province_data, 'provCode', $employee->pa_province, 'provDesc'),
                'pa_city' => getDescription($city_data, 'citymunCode', $employee->pa_city, 'citymunDesc'),
                'pa_barangay' => getDescription($barangay_data, 'brgyCode', $employee->pa_barangay, 'brgyDesc'),
            ];

            // Combine partial address with full address details
            $partial_address = trim(
                "{$employee->pa_village} {$employee->pa_street} {$employee->pa_house_no}"
            );
            $full_address = trim(
                "{$partial_address}, {$address['pa_barangay']}, {$address['pa_city']}, {$address['pa_province']}, {$address['pa_region']}"
            );

            return $this->successResponse([
                'employee' => $employee,
                'address_data' => $address,
                'full_address' => $full_address,
                'summary' => [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'position' => $employee->position,
                    'company' => $employee->company,
                    'date_hired' => $employee->date_hired,
                    'age' => $employee->age,
                    'gender' => $employee->gender,
                    'civil_status' => $employee->civil_status,
                    'salary' => $employee->salary,
                    'annual_salary' => $employee->annual_salary,
                    'from_branch' => $employee->from_branch
                ]
            ], 'Employee medical certificate data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee medical certificate data: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            $app_key = env("APP_KEY", "");

            $employees = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('departments as c', 'c.id', '=', 'a.department_id')
                ->leftJoin('branches as d', 'd.id', '=', 'a.branch_id')
                ->leftJoin('employment_types as e', 'e.id', '=', 'a.employment_type_id')
                ->select(
                    'a.id',
                    'a.employee_no',
                    'a.date_hired',
                    'a.age',
                    'a.salary',
                    DB::raw("isnull(a.salary,0) * 12 as annual_salary"),
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                    'b.name as position',
                    'c.name as department',
                    'd.name as branch',
                    'e.name as employment_type'
                )
                ->where(['a.is_employee' => true, 'a.active' => true])
                ->orderBy('a.first_name', 'asc')
                ->get();

            return $this->successResponse([
                'employees' => $employees,
                'fields' => [
                    'employee' => ['type' => 'select', 'required' => true, 'label' => 'Employee']
                ]
            ], 'Create employee medical certificate form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }
}
