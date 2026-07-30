<?php

namespace App\Http\Controllers;

use App\Audit;
use App\Department;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Validator;

class ReimbursementCommunicationExpensesController extends Controller
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
            $data = DB::table('reimbursement_headers as a')
                ->leftJoin('departments as b', 'b.id', '=', 'a.department_id')
                ->leftJoin('months as c', 'c.id', '=', 'a.month_id')
                ->select(
                    'a.*',
                    'b.name as department',
                    'c.name as month',
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

            $departments = DB::table('departments')->get();

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

            $employees = DB::table('employees as a')
                ->join('positions as b', 'a.position_id', '=', 'b.id')
                ->leftJoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CONCAT(
                        CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.first_name ELSE dbo.ufn_DecryptString(a.first_name,'$app_key') END,
                        ' ',
                        CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN a.last_name ELSE dbo.ufn_DecryptString(a.last_name,'$app_key') END,
                        CASE WHEN c.name IS NOT NULL THEN CONCAT(', ', c.name) ELSE '' END
                    ) as full_name"),
                    'b.name as position'
                )
                ->where('a.active', true)
                ->orderBy('full_name', 'asc')
                ->get();

            return $this->successResponse([
                'data' => $data,
                'departments' => $departments,
                'employees' => $employees
            ], 'Reimbursement add form data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve reimbursement add form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'department_id' => 'required',
                'month_id' => 'required',
                'year' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $payroll_exist = DB::table('reimbursement_headers')
                ->where([
                    'department_id' => $request->department_id,
                    'month_id' => $request->month_id,
                    'year' => $request->year
                ])
                ->where('id', '<>', $id)
                ->count();

            if ($payroll_exist > 0) {
                return $this->errorResponse('Existing Payroll Communication Expenses with the same Month and Year under the same Department!');
            }

            if ($id == 0) {
                $id = 1 + DB::table('reimbursement_headers')->max('id');
            }

            $data = array(
                'department_id' => $request->department_id,
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

    public function process(Request $request, $id, $type_id)
    {
        try {
            $with_employees = DB::table('reimbursement_details')->where('reimbursement_headers_id', $id)->get();

            if ($with_employees->isEmpty()) {
                return $this->errorResponse('Please add atleast one employee.');
            }

            $employee_data = $request->all();
            $data = [];

            if (isset($employee_data['id'])) {

                $arr_len = count($employee_data['id']);

                for ($i = 0; $i < $arr_len; $i++) {
                    if ($employee_data['id'][$i] != NULL) {
                        // Fetch data from the form
                        $prepaid_invoice_no = $employee_data['prepaid_invoice_no'][$i];
                        $prepaid_amount = $employee_data['prepaid_amount'][$i];
                        $postpaid_invoice_no = $employee_data['postpaid_invoice_no'][$i];
                        $postpaid_amount = $employee_data['postpaid_amount'][$i];

                        // Prepare data to store in hazard_pay_details table
                        $data = [
                            'prepaid_invoice_no' => $prepaid_invoice_no,
                            'prepaid_amount' => $prepaid_amount,
                            'postpaid_invoice_no' => $postpaid_invoice_no,
                            'postpaid_amount' => $postpaid_amount,
                        ];

                        // Insert data into hazard_pay_details table
                        DB::table('reimbursement_details')
                            ->where('id', $employee_data['id'][$i])
                            ->update($data);
                    }
                }
            }

            if ($type_id == 1) {
                DB::table('reimbursement_headers')->where('id', $id)->update(['posted' => true]);

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'Payroll Communication Expenses',
                    'activity' => 'Posted',
                    'description' => 'Posted Payroll Communication Expenses.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'Successfully Posted Payroll Communication Expenses!');
            } else {
                DB::table('reimbursement_headers')->where('id', $id)->update(['posted' => false]);

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Payroll Module',
                    'menu'    => 'Payroll Communication Expenses',
                    'activity' => 'Unposted',
                    'description' => 'Unposted Payroll Communication Expenses.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'Successfully Unposted Payroll Communication Expenses!');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process reimbursement: ' . $e->getMessage());
        }
    }

    public function report()
    {
        try {
            $app_key = env("APP_KEY", "");
            $departments = DB::table('departments')->get();
            $data = DB::table('reimbursement_headers as a')
                ->join('months as b', 'a.month_id', '=', 'b.id')
                ->join('departments as c', 'a.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'b.name as department',
                    DB::raw("CONCAT(a.month_id,' ',a.year,' (', b.name ,')') as name")
                )
                ->get();

            return $this->successResponse([
                'data' => $data,
                'departments' => $departments
            ], 'Reimbursement report data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve reimbursement report data: ' . $e->getMessage());
        }
    }

    public function generatePdf(Request $request)
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
                    'min:2015',
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
            $company = DB::table('companies')->first();
            $data = DB::table('reimbursement_headers as a')
                ->join('reimbursement_details as b', 'a.id', '=', 'b.reimbursement_headers_id')
                ->join('employees as c', 'b.employee_id', '=', 'c.id')
                ->join('positions as d', 'c.position_id', '=', 'd.id')
                ->leftJoin('name_suffixes as g', 'c.name_suffix_id', '=', 'g.id')
                ->join('departments as i', 'a.department_id', '=', 'i.id')
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
                    'i.name as department',
                )
                ->distinct()
                ->where('a.department_id', $request->department_id)
                ->where('a.month_id', $request->month_id)
                ->where('a.year', $request->year)
                ->orderBy('full_name', 'asc')
                ->get();

            if ($data->isEmpty()) {
                return $this->errorResponse('No Payroll Communication Expenses data found.', 404);
            }

            $signatories = [
                'signatory1' => $request->signatory1,
                'signatory2' => $request->signatory2,
                'signatory3' => $request->signatory3,
                'signatory4' => $request->signatory4,
                'signatory5' => $request->signatory5,
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

            $filename = 'reimbursement_communication_expenses_' . $request->department_id . '_' . $request->month_id . '_' . $request->year . '_' . date('Y-m-d') . '.pdf';
            
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
