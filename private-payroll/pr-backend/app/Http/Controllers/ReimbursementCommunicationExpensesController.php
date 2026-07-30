<?php

namespace App\Http\Controllers;

use App\Audit;
use App\Department;
use Auth;
use Illuminate\Http\Request;
use PDF;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Support\PayrollBenefitsEmployeeScope;
use App\Support\ReportDivisionFilter;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Validator;

class ReimbursementCommunicationExpensesController extends Controller
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
            $data = DB::table('reimbursement_headers as a')
                ->leftJoin('divisions as b', 'b.id', '=', 'a.department_id')
                ->leftJoin('departments as dept', 'dept.id', '=', 'a.department_id')
                ->leftJoin('months as c', 'c.id', '=', 'a.month_id')
                ->leftJoin('reimbursement_details as d', 'a.id', '=', 'd.reimbursement_headers_id')
                ->select(
                    'a.*',
                    DB::raw('COALESCE(b.name, dept.name) as division'),
                    DB::raw('COALESCE(b.name, dept.name) as department'),
                    'c.name as month',
                    DB::raw('COUNT(d.id) as employee_count'),
                    DB::raw('COALESCE(SUM(CAST(ISNULL(d.prepaid_amount, 0) AS FLOAT) + CAST(ISNULL(d.postpaid_amount, 0) AS FLOAT)), 0) as total_amount')
                )
                ->groupBy(
                    'a.id',
                    'a.department_id',
                    'a.month_id',
                    'a.year',
                    'a.posted',
                    'a.created_at',
                    'a.updated_at',
                    'b.name',
                    'dept.name',
                    'c.name'
                )
                ->get();

            return $this->successResponse($data, 'Reimbursement communication expenses data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve reimbursement data: ' . $e->getMessage());
        }
    }

    public function add(Request $request, $id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $divisions = ReportDivisionFilter::activeDivisions();
            $divisionId = ReportDivisionFilter::resolveId($request);

            if ($id != 0) {
                $data = DB::table('reimbursement_headers as a')
                    ->leftJoin('reimbursement_details as b', 'a.id', '=', 'b.reimbursement_headers_id')
                    ->leftJoin('employees as c', 'b.employee_id', '=', 'c.id')
                    ->leftJoin('name_suffixes as d', 'c.name_suffix_id', '=', 'd.id')
                    ->leftJoin('positions as e', 'c.position_id', '=', 'e.id')
                    ->select(
                        'a.*',
                        'b.id as reimbursement_dtl',
                        'c.id as employee_id',
                        DB::raw("CONCAT(
                            CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN c.first_name ELSE dbo.ufn_DecryptString(c.first_name,'$app_key') END,
                            ' ',
                            CASE
                                WHEN ISNULL(c.is_encrypted, 0) = 0 THEN LEFT(c.middle_name, 1)
                                ELSE LEFT(dbo.ufn_DecryptString(c.middle_name, '$app_key'), 1)
                            END,
                            CASE WHEN c.middle_name IS NOT NULL AND c.middle_name <> '' THEN '. ' ELSE ' ' END,
                            CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN c.last_name ELSE dbo.ufn_DecryptString(c.last_name,'$app_key') END,
                            CASE WHEN d.name IS NOT NULL THEN CONCAT(', ', d.name) ELSE '' END
                        ) as full_name"),
                        'e.name as position',
                        'b.prepaid_invoice_no',
                        'b.prepaid_amount',
                        'b.postpaid_invoice_no',
                        'b.postpaid_amount',

                    )
                    ->where('a.id', $id)
                    ->orderBy('full_name', 'asc')
                    ->get();
            } else {
                $data_dummy = array(
                    'id' => 0,
                    'reimbursement_dtl' => 0,
                    'department_id' => 0,
                    'month_id' => 0,
                    'year' => 0,
                    'employee_id' => 0,
                    'full_name' => '',
                    'position' => '',
                    'prepaid_invoice_no' => 0,
                    'prepaid_amount' => 0,
                    'postpaid_invoice_no' => 0,
                    'postpaid_amount' => 0,
                    'posted' => false,
                );

                $data = (object)$data_dummy;
                $data = collect([$data]);
            }

            $employeesQuery = DB::table('employees as a')
                ->join('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('divisions as div', 'div.id', '=', 'a.division_id')
                ->leftJoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'a.division_id',
                    DB::raw("CONCAT(
                        CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END,
                        ' ',
                        CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END,
                        CASE WHEN c.name IS NOT NULL THEN CONCAT(', ', c.name) ELSE '' END
                    ) as full_name"),
                    'b.name as position',
                    'div.name as division',
                    'div.name as department'
                )
                ->where('a.active', true)
                ->when($id != 0, function ($q) use ($id) {
                    $storedId = DB::table('reimbursement_headers')->where('id', $id)->value('department_id');
                    if ($storedId) {
                        if (DB::table('divisions')->where('id', $storedId)->exists()) {
                            $q->where('a.division_id', $storedId);
                        } else {
                            $q->where('a.department_id', $storedId);
                        }
                    }
                    return $q;
                })
                ->when($divisionId, function ($q) use ($divisionId) {
                    return $q->where('a.division_id', $divisionId);
                });
            PayrollBenefitsEmployeeScope::apply($employeesQuery, 'a');
            $employees = $employeesQuery
                ->orderBy('full_name', 'asc')
                ->get();

            return $this->successResponse([
                'data' => $data,
                'divisions' => $divisions,
                'departments' => $divisions,
                'employees' => $employees
            ], 'Reimbursement add form data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve reimbursement add form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $divisionId = ReportDivisionFilter::resolveId($request);

            $validator = Validator::make(
                array_merge($request->all(), ['division_id' => $divisionId]),
                [
                    'division_id' => 'required|exists:divisions,id',
                    'month_id' => 'required',
                    'year' => 'required',
                ]
            );

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $payroll_exist = DB::table('reimbursement_headers')
                ->where([
                    'department_id' => $divisionId,
                    'month_id' => $request->month_id,
                    'year' => $request->year
                ])
                ->where('id', '<>', $id)
                ->count();

            if ($payroll_exist > 0) {
                return $this->errorResponse('Existing Payroll Communication Expenses with the same Month and Year under the same Division!');
            }

            if ($id == 0) {
                $id = 1 + DB::table('reimbursement_headers')->max('id');
            }

            $data = array(
                'department_id' => $divisionId,
                'month_id' => $request->month_id,
                'year' => $request->year
            );

            DB::unprepared('SET IDENTITY_INSERT reimbursement_headers ON');
            DB::table('reimbursement_headers')->updateOrInsert(['id' => $id], $data);
            DB::unprepared('SET IDENTITY_INSERT reimbursement_headers OFF');

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'Payroll Communication Expenses',
                'activity' => 'Create',
                'description' => 'Created Payroll Communication Expenses information.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Successfully Created Payroll Communication Expenses!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create reimbursement: ' . $e->getMessage());
        }
    }

    public function addEmployee(Request $request, $id)
    {
        try {
            $employee_data = $request->all();
            $data = [];

            if (isset($employee_data['id'])) {

                $arr_len = count($employee_data['id']);

                for ($i = 0; $i < $arr_len; $i++) {
                    if ($employee_data['id'][$i] != NULL) {
                        if (in_array($employee_data['id'][$i], $employee_data['select'])) {

                            // Fetch Employee Details
                            $employee = DB::table('employees')
                                ->select(
                                    'employees.id',
                                    'employees.position_id',
                                )
                                ->where('employees.id', $employee_data['id'][$i])
                                ->get();

                            // Ensure the employee exists
                            if ($employee->isNotEmpty()) {
                                // Fetch number of days worked
                                $prepaid_invoice_no = 0;
                                $prepaid_amount = 0;
                                $postpaid_invoice_no = 0;
                                $postpaid_amount = 0;


                                // Prepare data to store in reimbursement_details table
                                $data = [
                                    'reimbursement_headers_id' => $id,
                                    'employee_id' => $employee[0]->id,
                                    'position_id' => $employee[0]->position_id,
                                    'prepaid_invoice_no' => $prepaid_invoice_no,
                                    'prepaid_amount' => $prepaid_amount,
                                    'postpaid_invoice_no' => $postpaid_invoice_no,
                                    'postpaid_amount' => $postpaid_amount,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];

                                // Insert data into reimbursement_details table
                                DB::table('reimbursement_details')->insert($data);
                            }
                        }
                    }
                }

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'Payroll Communication Expenses',
                    'activity' => 'Added',
                    'description' => 'Added Employees.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'Successfully Added Employees to Payroll Communication Expenses!');
            }

            return $this->errorResponse('No employee data provided');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add employees: ' . $e->getMessage());
        }
    }

    public function deleteEmployee($id)
    {
        try {
            $data = DB::table('reimbursement_details')->where('id', $id)->delete();

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'Payroll Communication Expenses',
                'activity' => 'Delete',
                'description' => 'Deleted Payroll Communication Expenses Employees.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['deleted' => $data], 'Employee deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete employee: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            // Guard: reject if already posted
            $header = DB::table('reimbursement_headers')->where('id', $id)->first();
            if (!$header) {
                return $this->errorResponse('Reimbursement not found', 404);
            }
            if ($header->posted) {
                return $this->errorResponse('Cannot delete a posted reimbursement. Unpost first.');
            }

            // Delete detail rows first
            DB::table('reimbursement_details')->where('reimbursement_headers_id', $id)->delete();

            // Delete header
            $deleted = DB::table('reimbursement_headers')->where('id', $id)->delete();

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'Payroll Communication Expenses',
                'activity' => 'Delete',
                'description' => 'Deleted Payroll Communication Expenses.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['deleted' => $deleted], 'Reimbursement communication expenses deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete reimbursement: ' . $e->getMessage());
        }
    }

    public function process(Request $request, $id, $type_id)
    {
        try {
            $with_employees = DB::table('reimbursement_details')->where('reimbursement_headers_id', $id)->get();

            if ($with_employees->isEmpty()) {
                return $this->errorResponse('Please add atleast one employee.');
            }

            $employee_data = $request->all();
            $data = [];

            // Only update employee data if it's provided and not empty
            if (isset($employee_data['id']) && !empty($employee_data['id'])) {

                $arr_len = count($employee_data['id']);

                for ($i = 0; $i < $arr_len; $i++) {
                    if ($employee_data['id'][$i] != NULL) {
                        // Fetch data from the form
                        $prepaid_invoice_no = $employee_data['prepaid_invoice_no'][$i];
                        $prepaid_amount = $employee_data['prepaid_amount'][$i];
                        $postpaid_invoice_no = $employee_data['postpaid_invoice_no'][$i];
                        $postpaid_amount = $employee_data['postpaid_amount'][$i];

                        // Prepare data to store in reimbursement_details table
                        $data = [
                            'prepaid_invoice_no' => $prepaid_invoice_no,
                            'prepaid_amount' => $prepaid_amount,
                            'postpaid_invoice_no' => $postpaid_invoice_no,
                            'postpaid_amount' => $postpaid_amount,
                        ];

                        // Update data in reimbursement_details table
                        DB::table('reimbursement_details')
                            ->where('id', $employee_data['id'][$i])
                            ->update($data);
                    }
                }
            }

            // Update the posted status regardless of employee data
            if ($type_id == 1) {
                $updated = DB::table('reimbursement_headers')->where('id', $id)->update(['posted' => true]);
                
                // Debug: Log the update result
                \Log::info("Posted reimbursement ID: $id, Updated rows: $updated");

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'Payroll Communication Expenses',
                    'activity' => 'Posted',
                    'description' => 'Posted Payroll Communication Expenses.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id, 'updated' => $updated], 'Successfully Posted Payroll Communication Expenses!');
            } else {
                $updated = DB::table('reimbursement_headers')->where('id', $id)->update(['posted' => false]);
                
                // Debug: Log the update result
                \Log::info("Unposted reimbursement ID: $id, Updated rows: $updated");

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'Payroll Communication Expenses',
                    'activity' => 'Unposted',
                    'description' => 'Unposted Payroll Communication Expenses.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id, 'updated' => $updated], 'Successfully Unposted Payroll Communication Expenses!');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process reimbursement: ' . $e->getMessage());
        }
    }

    public function report()
    {
        try {
            $app_key = env("APP_KEY", "");
            $divisions = ReportDivisionFilter::divisionsWithReimbursementData();
            $months = DB::table('months')->orderBy('id', 'asc')->get();
            $data = DB::table('reimbursement_headers as a')
                ->join('months as b', 'a.month_id', '=', 'b.id')
                ->join('reimbursement_details as rd', 'a.id', '=', 'rd.reimbursement_headers_id')
                ->leftJoin('divisions as c', 'a.department_id', '=', 'c.id')
                ->leftJoin('departments as d', 'a.department_id', '=', 'd.id')
                ->select(
                    'a.id',
                    'a.department_id as division_id',
                    'a.month_id',
                    'a.year',
                    DB::raw('COALESCE(c.name, d.name) as division'),
                    DB::raw('COALESCE(c.name, d.name) as department'),
                    DB::raw("CONCAT(a.month_id,' ',a.year,' (', b.name ,')') as name")
                )
                ->distinct()
                ->get()
                ->map(function ($row) {
                    $row->resolved_division_id = ReportDivisionFilter::resolveHeaderDivisionId(
                        $row->division_id
                    );
                    return $row;
                });

            return $this->successResponse([
                'data' => $data,
                'records' => $data,
                'months' => $months,
                'divisions' => $divisions,
                'departments' => $divisions,
            ], 'Reimbursement report data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve reimbursement report data: ' . $e->getMessage());
        }
    }

    public function generatePdf(Request $request)
    {
        try {
            $divisionId = ReportDivisionFilter::resolveId($request);

            $validator = validator(
                array_merge($request->all(), ['division_id' => $divisionId]),
                [
                    'division_id' => [
                        'required',
                        'exists:divisions,id',
                    ],
                    'month_id' => [
                        'required',
                        'exists:months,id',
                    ],
                    'year' => [
                        'required',
                        'integer',
                        'min:2015',
                        'max:' . date('Y'),
                    ],
                ],
                [
                    'division_id.required' => 'The division field is required.',
                    'division_id.exists' => 'The selected division does not exist.',
                    'month_id.required' => 'The month field is required.',
                    'month_id.exists' => 'The selected month is invalid.',
                    'year.required' => 'The year field is required.',
                ]
            );

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");
            $company = DB::table('companies')->first();
            $headerDepartmentIds = ReportDivisionFilter::legacyHeaderDepartmentIdsForDivision($divisionId);

            $data = DB::table('reimbursement_headers as a')
                ->join('reimbursement_details as b', 'a.id', '=', 'b.reimbursement_headers_id')
                ->join('employees as c', 'b.employee_id', '=', 'c.id')
                ->join('positions as d', 'c.position_id', '=', 'd.id')
                ->leftJoin('name_suffixes as g', 'c.name_suffix_id', '=', 'g.id')
                ->leftJoin('divisions as i', 'a.department_id', '=', 'i.id')
                ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
                ->join('months as j', 'a.month_id', '=', 'j.id')
                ->select(
                    'a.id',
                    'b.employee_id',
                    DB::raw("CONCAT(j.name,' ',a.year) as month_year"),
                    DB::raw("CONCAT(
                    CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN c.first_name ELSE dbo.ufn_DecryptString(c.first_name,'$app_key') END,
                    ' ',
                    CASE
                        WHEN ISNULL(c.is_encrypted, 0) = 0 THEN LEFT(c.middle_name, 1)
                        ELSE LEFT(dbo.ufn_DecryptString(c.middle_name, '$app_key'), 1)
                    END,
                    CASE WHEN c.middle_name IS NOT NULL AND c.middle_name <> '' THEN '. ' ELSE ' ' END,
                    CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN c.last_name ELSE dbo.ufn_DecryptString(c.last_name,'$app_key') END,
                    CASE WHEN g.name IS NOT NULL THEN CONCAT(', ', g.name) ELSE '' END
                ) as full_name"),
                    'b.prepaid_invoice_no',
                    'b.prepaid_amount',
                    'b.postpaid_invoice_no',
                    'b.postpaid_amount',
                    'd.name as position',
                    DB::raw('COALESCE(i.name, dept.name) as department'),
                )
                ->distinct()
                ->whereIn('a.department_id', $headerDepartmentIds)
                ->where('a.month_id', (int) $request->month_id)
                ->where('a.year', (int) $request->year)
                ->orderBy('full_name', 'asc')
                ->get();

            if ($data->isEmpty()) {
                return $this->errorResponse(
                    'No Payroll Communication Expenses found for the selected division, month, and year. Create and save a record with employees under Payroll Benefits → Payroll Communication Macco first.',
                    404
                );
            }

            $signatories = [
                'signatory1' => $request->signatory1,
                'signatory_position_1' => $request->signatory_position_1,
                'signatory2' => $request->signatory2,
                'signatory_position_2' => $request->signatory_position_2,
                'signatory3' => $request->signatory3,
                'signatory_position_3' => $request->signatory_position_3,
                'signatory4' => $request->signatory4,
                'signatory_position_4' => $request->signatory_position_4,
                'signatory5' => $request->signatory5,
                'signatory_position_5' => $request->signatory_position_5,
            ];

            $pdf = PDF::loadView('reimbursement_report.reimbursement_report_print', compact(
                'data',
                'signatories',
                'company'
            ))
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('tabloid', 'landscape');
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'reimbursement_communication_expenses_' . $divisionId . '_' . $request->month_id . '_' . $request->year . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate reimbursement PDF: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        // Keep the original print method for backward compatibility
        return $this->generatePdf($request);
    }
}
