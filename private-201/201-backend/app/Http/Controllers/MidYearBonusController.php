<?php

namespace App\Http\Controllers;

use PDF;
use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MidYearBonusController extends Controller
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
            $branches = DB::table('branches')->get();
            $departments = DB::table('departments')->get();
            $dummy_midyear_records = array(
                'id' => 0,
                'photo' => null,
                'employee_no' => null,
                'department' => null,
                'position' => null,
                'name' => null,
                'salary' => 0,
                'earned' => 0
            );

            $midyear_records = (object) $dummy_midyear_records;
            $midyear_records = collect([$midyear_records]);

            return $this->successResponse([
                'branches' => $branches,
                'midyear_records' => $midyear_records,
                'departments' => $departments
            ], 'Mid-year bonus data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve mid-year bonus data: ' . $e->getMessage());
        }
    }

    public function process(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'branch_id' => 'required|exists:branches,id',
                'department_id' => 'required|exists:departments,id',
                'years' => 'required|integer|min:2000|max:2100'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $with_setup = DB::table("midyear_table")->get();

            if ($with_setup->isEmpty()) {
                return $this->errorResponse('Failed to generate Mid Year bonus. Please setup MidYear Bonus table.', 400);
            }

            $midyear_header = DB::table('midyear_bonus_header')->where([
                'year_id' => $request->years,
                'branch_id' => $request->branch_id,
                'department_id' => $request->department_id
            ])
                ->get();
            if ($midyear_header->isNotEmpty()) {
                $header_id = $midyear_header[0]->id;
            } else {
                $header_id = DB::table('midyear_bonus_header')->max('id');
                $header_id = $header_id + 1;
            }

            $employees = DB::table('employees as a')
                ->leftJoin('midyear_table as e', DB::raw("(CASE WHEN DATEDIFF(MONTH, a.date_hired, CURRENT_TIMESTAMP) < 12 THEN 
                                                                    (CASE WHEN (DATEDIFF(MONTH, a.date_hired, CURRENT_TIMESTAMP)) <= (SELECT MIN(months) FROM midyear_table) THEN 0 
                                                                          ELSE 
                                                                               (CASE WHEN (DATEDIFF(MONTH, a.date_hired, CURRENT_TIMESTAMP)) >= (SELECT MAX(months) FROM midyear_table) THEN 
                                                                                        (SELECT MAX(months) FROM midyear_table) 
                                                                                     ELSE 
                                                                                        DATEDIFF(MONTH, a.date_hired, CURRENT_TIMESTAMP) 
                                                                                     END) 
                                                                                END)
                                                            ELSE 
                                                                    (CASE WHEN (DATEDIFF(MONTH, a.date_hired, CURRENT_TIMESTAMP)) >= (SELECT MAX(months) FROM midyear_table) THEN 
                                                                             (SELECT MAX(months) FROM midyear_table) 
                                                                     ELSE 
                                                                          (SELECT MAX(months) FROM midyear_table) 
                                                                     END) 
                                                            END)"), '=', 'e.months')
                ->select(
                    'a.id',
                    'a.first_name',
                    'a.last_name',
                    'a.salary',
                    DB::raw("(a.salary * isnull(e.percentage,0)) as amount"),
                )
                ->where([
                    'active' => true,
                    'is_employee' => true,
                    'branch_id' => $request->branch_id,
                    'department_id' => $request->department_id
                ])
                ->get();

            if ($employees->isNotEmpty()) {
                foreach ($employees as $emp) {
                    $midyear_bonus = array(
                        'employee_id' => $emp->id,
                        'branch_id' => $request->branch_id,
                        'department_id' => $request->department_id,
                        'years' => $request->years,
                        'salary' => $emp->salary,
                        'bonus_amount' => $emp->amount,
                        'midyear_header_id' => $header_id
                    );

                    DB::table('midyear_bonus')->updateOrInsert(['employee_id' => $emp->id, 'branch_id' => $request->branch_id, 'years' => $request->years], $midyear_bonus);
                }

                $midyear_bonus_header = array(
                    'branch_id' => $request->branch_id,
                    'department_id' => $request->department_id,
                    'year_id' => $request->years,
                    'posted' => false
                );

                DB::table('midyear_bonus_header')->updateOrInsert([
                    'branch_id' => $request->branch_id,
                    'department_id' => $request->department_id,
                    'year_id' => $request->years
                ], $midyear_bonus_header);

                return $this->successResponse(null, 'Successfully Generated 14th Month Pay!');
            } else {
                return $this->errorResponse('No Employee to process 14th Month Pay.', 400);
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process mid-year bonus: ' . $e->getMessage());
        }
    }

    public function post(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'branch_id' => 'required|exists:branches,id',
                'department_id' => 'required|exists:departments,id',
                'years' => 'required|integer|min:2000|max:2100'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $midyear_header = DB::table('midyear_bonus_header')->where([
                'year_id' => $request->years,
                'branch_id' => $request->branch_id,
                'department_id' => $request->department_id
            ])
                ->get();
            if ($midyear_header->isNotEmpty()) {
                $header_id = $midyear_header[0]->id;
                DB::table('midyear_bonus_header')->where([
                    'branch_id' => $request->branch_id,
                    'department_id' => $request->department_id,
                    'year_id' => $request->years
                ])->update(['posted' => true]);
            } else {
                $header_id = DB::table('midyear_bonus_header')->max('id');
                $header_id = $header_id + 1;
            }

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module' => 'Payroll Module',
                'menu' => 'Mid Year Bonus',
                'activity' => 'Posted',
                'description' => 'Posted Mid Year Bonus.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Successfully Posted 14th Month Pay!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to post mid-year bonus: ' . $e->getMessage());
        }
    }

    public function report(Request $request)
    {
        try {
            $Branch = DB::table('midyear_bonus as a')
                ->join('departments as b', 'a.department_id', '=', 'b.id')
                ->select(
                    'a.department_id as id',
                    'b.name'
                )
                ->distinct()
                ->get();

            $years = DB::table('midyear_bonus as a')
                ->select(
                    'a.years'
                )
                ->distinct()
                ->orderBy('years', 'asc')
                ->get();

            $signatories = DB::table('payroll_signatories')
                ->where(['branch_id' => $request->branch_id, 'report_name' => "14th Month Pay"])
                ->get();

            return $this->successResponse([
                'branches' => $Branch,
                'years' => $years,
                'signatories' => $signatories
            ], 'Mid-year bonus report data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve mid-year bonus report data: ' . $e->getMessage());
        }
    }

    public function generatePdf(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'department_id' => 'required|exists:departments,id',
                'years' => 'required|integer|min:2000|max:2100',
                'signatory_1' => 'nullable|string',
                'signatory_position_1' => 'nullable|string',
                'signatory_2' => 'nullable|string',
                'signatory_position_2' => 'nullable|string',
                'signatory_3' => 'nullable|string',
                'signatory_position_3' => 'nullable|string',
                'signatory_4' => 'nullable|string',
                'signatory_position_4' => 'nullable|string',
                'signatory_5' => 'nullable|string',
                'signatory_position_5' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $midyear = DB::table('midyear_bonus_header as a')
                ->leftjoin('midyear_bonus as b', 'a.id', '=', 'b.midyear_header_id')
                ->where('a.department_id', $request->department_id)
                ->get();

            $years = DB::table('midyear_bonus_header as a')
                ->select(
                    'a.id',
                    'a.year_id'
                )
                ->where('a.year_id', $request->years)
                ->orderBy('a.year_id', 'asc')
                ->get();

            $employees = DB::table('midyear_bonus as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('departments as c', 'c.id', '=', 'b.department_id')
                ->join('positions as d', 'd.id', '=', 'b.position_id')
                ->select(
                    'a.id',
                    'a.branch_id',
                    'b.photo',
                    'b.employee_no',
                    'c.name as department',
                    'd.name as position',
                    'a.salary',
                    'a.bonus_amount as amount',
                    DB::raw("CASE 
                WHEN ISNULL(b.is_encrypted,0) = 0 THEN 
                    CONCAT(b.last_name, ', ', b.first_name, ' ', LEFT(b.middle_name, 1)) 
                ELSE 
                    CONCAT(RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')), ', ', 
                           RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')), ' ', 
                           LEFT(RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key')), 1)) 
                END as name")
                )
                ->where([
                    'a.years' => $request->years,
                    'b.active' => true,
                    'b.is_employee' => true,
                    'a.department_id' => $request->department_id
                ])
                ->distinct()
                ->orderBy('name', 'asc')
                ->get();

            $signatories = [
                [
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
                ]
            ];

            $month = $years[0]->year_id ?? $request->years;

            $department = DB::table('departments')
                ->where('id', $request->department_id)
                ->value('name');

            $pdf = PDF::loadView('process_bonus.midyear_report_print', compact('employees', 'midyear', 'department', 'signatories', 'image', 'month'))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4', 'landscape');
            
            $pdfContent = $pdf->output();
            $base64Pdf = base64_encode($pdfContent);

            $filename = 'midyear_bonus_report_' . $request->years . '_' . $department . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate mid-year bonus PDF: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        // Keep the original print method for backward compatibility
        return $this->generatePdf($request);
    }
}
