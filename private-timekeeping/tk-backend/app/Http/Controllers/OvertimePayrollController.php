<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Audit;
use App\Traits\ApiResponse;
use App\Traits\GeneratesPdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class OvertimePayrollController extends Controller
{
    use ApiResponse, GeneratesPdf;
    public function index()
    {
        try {
            $payroll_intervals = DB::table('payroll_intervals')
                ->where('active', true)
                ->whereIn('id', function ($query) {
                    $query->select('payroll_interval_id')->from('payroll_periods')->where('posted', true)->get();
                })
                ->get();

            $periods = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CONCAT(b.name,' (',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date),') ') as name")
                )
                ->where('a.active', true)
                ->whereIn('a.id', function ($query) {
                    $query->select('payroll_period_id')->from('overtime_payroll_headers')->where('posted', true);
                })
                ->orderBy('a.release_date', 'desc')
                ->get();

            return $this->successResponse([
                'payroll_intervals' => $payroll_intervals,
                'periods' => $periods
            ], 'Overtime payroll data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve overtime payroll data: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'payroll_period_id' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $payroll_period_id = $request->payroll_period_id;
            $company = DB::table('companies')->get();

            // Fetch particulars like in dvOvertime
            $particulars = DB::table('payroll_periods as a')
                ->select(
                    DB::raw("CONCAT('OVERTIME PAYROLL - ',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date)) as particular")
                )
                ->where('a.id', $payroll_period_id)
                ->get();

            $particular = $particulars[0]->particular ?? 'OVERTIME PAYROLL';

            // Match the data logic from dvOvertime
            $data = DB::table('overtime_payroll_headers as e')
                ->join('overtime_payroll_details as d', 'e.id', '=', 'd.overtime_payroll_id')
                ->join('overtime_applications as f', 'd.employee_id', '=', 'f.employee_id')
                ->join('overtime_types as c', 'f.overtime_type_id', '=', 'c.id')
                ->join('employees as b', 'f.employee_id', '=', 'b.id')
                ->select(
                    DB::raw("'$particular' as particular"),
                    'f.date',
                    'b.salary',
                    'c.rate',
                    'f.employee_id',
                    DB::raw('SUM(f.total_hours) as total_hours'),
                    DB::raw("
                    (
                        (((b.salary / 22) / 8) * SUM(f.total_hours)) * c.rate
                    ) as earned
                ")
                )
                ->where([
                    'f.approved' => true,
                    'f.approved_2' => true,
                    'f.payroll' => true,
                    'e.payroll_period_id' => $payroll_period_id,
                    'e.posted' => true
                ])
                ->groupBy(
                    'f.date',
                    'b.salary',
                    'c.rate',
                    'f.employee_id'
                )
                ->get();

            // Get the latest OT per employee and overtime type
            $latest_ot_query = DB::table('overtime_applications as sub')
                ->select(
                    DB::raw('MAX(sub.id) as latest_ot_id'),
                    'sub.employee_id',
                    'sub.date',
                    'sub.overtime_type_id'
                )
                ->groupBy('sub.employee_id', 'sub.date', 'sub.overtime_type_id');

            // Match the employees logic from dvOvertime
            $employees = DB::table('overtime_payroll_headers as a')
                ->join('overtime_payroll_details as b', 'a.id', '=', 'b.overtime_payroll_id')
                ->join('employees as c', 'b.employee_id', '=', 'c.id')
                ->join('positions as d', 'c.position_id', '=', 'd.id')
                ->join('overtime_applications as e', function ($join) {
                    $join->on('b.employee_id', '=', 'e.employee_id');
                    $join->on('b.overtime_type_id', '=', 'e.overtime_type_id');
                })
                ->join('overtime_types as f', 'e.overtime_type_id', '=', 'f.id')
                ->select(
                    'b.employee_id',
                    'c.photo',
                    'c.employee_no',
                    'e.date',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted, 0) = 0 THEN
                   CONCAT(c.first_name, ' ', c.last_name)
               ELSE
                   RTRIM([dbo].[ufn_DecryptString](c.first_name, '$app_key')) + ' ' + RTRIM([dbo].[ufn_DecryptString](c.last_name, '$app_key'))
               END as name"),
                    DB::raw("
               (
                   (((c.salary / 22) / 8) * SUM(e.total_hours)) * f.rate
               ) as earned
           "),
                    DB::raw("SUM(e.total_hours) AS total_hours"),
                    'd.name as position',
                    'c.salary',
                    DB::raw("MAX(f.name) as ot_type"),
                    DB::raw("MAX(f.rate) as rate"),
                    DB::raw("MAX(b.id) as dtl_id"),
                    DB::raw("MAX(a.id) as id"),
                    DB::raw("MAX(e.id) as latest_ot_id") // Ensuring the latest OT is fetched
                )
                ->where([
                    'a.payroll_period_id' => $payroll_period_id,
                    'e.payroll' => true,
                    'e.approved' => true,
                    'e.approved_2' => true
                ])
                ->groupBy([
                    'b.employee_id',
                    'c.photo',
                    'c.is_encrypted',
                    'c.employee_no',
                    'c.first_name',
                    'c.last_name',
                    'd.name',
                    'e.date',
                    'c.salary',
                    'e.id',
                    'f.rate'
                ])
                ->joinSub($latest_ot_query, 'latest_ot', function ($join) {
                    $join->on('e.id', '=', 'latest_ot.latest_ot_id');
                })            // Filter to only keep the latest record for the same date
                ->get();

            $companies = DB::table('companies')->get();

            $signatories = [
                'signatory1' => $request->signatory1,
                'signatory2' => $request->signatory2,
                'signatory3' => $request->signatory3,
                'signatory4' => $request->signatory4,
                'signatory5' => $request->signatory5,
            ];

            // Load the same disbursement_vouchers report
            $pdf = PDF::loadView('overtime_payroll.overtime_payroll_print', compact(
                'image',
                'company',
                'data',
                'employees',
                'companies',
                'signatories',
                'latest_ot_query'
            ))->setOptions(['defaultFont' => 'sans-serif']);

            $pdf->setPaper('A4', 'landscape');
            $pdfContent = $pdf->output();

            $filename = 'overtime_payroll_' . $payroll_period_id . '_' . date('Y-m-d') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate overtime payroll PDF: ' . $e->getMessage());
        }
    }

    public function load()
    {
        try {
            $overtime_payroll = DB::table('overtime_payroll_headers as a')
                ->join('payroll_periods as b', 'a.payroll_period_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'b.payroll_cutoff_id', '=', 'c.id')
                ->join('payroll_intervals as d', 'b.payroll_interval_id', '=', 'd.id')
                ->select(
                    'a.id',
                    'b.id as payroll_period_id',
                    'c.name as cut_off',
                    'b.release_date',
                    'b.attendance_start_date',
                    'b.attendance_end_date',
                    'a.posted',
                    DB::raw("CONCAT(d.name,' (',DATENAME(MONTH,b.release_date),' ',DATEPART(YEAR,b.release_date),') ') as payroll_period")
                )
                ->where('b.active', true)
                ->get();

            return $this->successResponse($overtime_payroll, 'Overtime payroll list retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve overtime payroll list: ' . $e->getMessage());
        }
    }

    public function add($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $payroll_intervals = DB::table('payroll_intervals')
                ->where('active', true)
                ->whereIn('id', function ($query) {
                    $query->select('payroll_interval_id')->from('payroll_periods')->where('posted', true)->get();
                })
                ->get();

            $payroll_periods = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->select(
                    'a.id',
                    DB::raw("CONCAT(b.name,' (',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date),') ') as name"),
                    'a.release_date',
                    'a.attendance_start_date',
                    'a.attendance_end_date',
                )
                ->where([
                    'a.posted' => true,
                    'a.active' => true
                ])
                ->orderBy('a.release_date', 'desc')
                ->get();

            if ($id > 0) {
                $data = DB::table('overtime_payroll_headers as a')
                    ->join('payroll_periods as b', 'a.payroll_period_id', '=', 'b.id')
                    ->select(
                        'a.id',
                        'a.payroll_period_id',
                        'a.date_forwarded',
                        'b.payroll_interval_id',
                        'b.attendance_start_date',
                        'b.attendance_end_date',
                        'a.posted'
                    )
                    ->where('a.id', $id)
                    ->get();
            } else {
                $data_dummy = array(
                    'id' => 0,
                    'payroll_period_id' => 0,
                    'payroll_interval_id' => 0,
                    'date_forwarded' => '',
                    'attendance_start_date' => '',
                    'attendance_end_date' => '',
                    'posted' => 0
                );

                $data = (object) $data_dummy;
                $data = collect([$data]);
            }

            $employees = DB::table('overtime_payroll_headers as a')
                ->join('overtime_payroll_details as b', 'a.id', '=', 'b.overtime_payroll_id')
                ->join('employees as c', 'b.employee_id', '=', 'c.id')
                ->join('positions as d', 'c.position_id', '=', 'd.id')
                ->join('overtime_applications as e', function ($join) {
                    $join->on('b.employee_id', '=', 'e.employee_id');
                    $join->on('b.overtime_type_id', '=', 'e.overtime_type_id');
                })
                ->select(
                    'a.id',
                    'b.id as dtl_id',
                    'b.employee_id',
                    'c.photo',
                    'c.employee_no',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                   CONCAT(c.first_name,' ',c.last_name)
                               ELSE
                                   RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key'))
                               END as name"),
                    DB::raw("SUM(e.total_hours) AS total_hours"),
                    'd.name as position',
                    'c.salary',
                    'e.overtime_type_id'
                )
                ->where([
                    'a.id' => $id,
                    'e.payroll' => true,
                    'e.approved' => true,
                    'e.approved_2' => true
                ])
                // ->whereBetween('e.date', [
                //     $data[0]->attendance_start_date, $data[0]->attendance_end_date
                // ])
                ->groupBy([
                    'a.id',
                    'b.id',
                    'b.employee_id',
                    'e.overtime_type_id',
                    'c.photo',
                    'c.is_encrypted',
                    'c.employee_no',
                    'c.first_name',
                    'c.last_name',
                    'd.name',
                    'c.salary',
                ])
                ->distinct()
                ->get();

            $employee_unselected = DB::table('employees as b')
                ->leftJoin('positions as c', 'b.position_id', '=', 'c.id')
                ->leftJoin('departments as d', 'b.department_id', '=', 'd.id')
                ->leftJoin('branches as e', 'b.branch_id', '=', 'e.id')
                ->join('overtime_applications as f', 'b.id', '=', 'f.employee_id')
                ->select(
                    'b.id',
                    'b.employee_no',
                    // 'f.id as overtime_id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                   CONCAT(b.first_name,' ',b.last_name)
                               ELSE
                                   RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                               END as name"),
                    'e.name as branch',
                    'd.name as department',
                    'c.name as position',
                    'b.salary',
                    DB::raw("SUM(f.total_hours) AS total_hours"),
                    'd.id as department_id',
                    'f.overtime_type_id'
                )
                ->where([
                    'f.approved' => true,
                    'f.approved_2' => true,
                    'f.payroll' => true
                ])
                // ->whereBetween('f.date', [
                //     $payroll_periods[0]->attendance_start_date, $payroll_periods[0]->attendance_end_date
                // ])
                ->whereNotIn('f.id', function ($query) use ($id) {
                    $query->select('overtime_id')->from('overtime_payroll_details')->where('overtime_payroll_id', $id);
                })
                ->groupBy(
                    'b.id',
                    'b.is_encrypted',
                    'b.employee_no',
                    'b.first_name',
                    'b.last_name',
                    'e.name',
                    'd.name',
                    'c.name',
                    'b.salary',
                    'd.id',
                    'f.overtime_type_id',
                    // 'f.id',
                )
                ->orderBy('b.first_name', 'asc')
                ->get();

            $overtime_types = DB::table('overtime_applications as f')
                ->join('overtime_types as g', 'f.overtime_type_id', '=', 'g.id')
                ->join('employees as c', 'c.id', '=', 'f.employee_id')
                ->select(
                    'f.overtime_type_id',
                    'g.name as overtime_type',
                    'g.rate',
                    DB::raw("SUM(f.total_hours) AS total_hours"),
                )
                ->where([
                    'f.payroll' => true,
                    'f.approved' => true,
                    'f.approved_2' => true
                ])
                // ->whereBetween('f.date', [
                //     $data[0]->attendance_start_date, $data[0]->attendance_end_date
                // ])
                ->groupBy(
                    'f.overtime_type_id',
                    'g.name',
                    'g.rate',
                )
                ->get();

            $year = Carbon::parse($data[0]->attendance_end_date)->format('Y');

            $ot_tax = DB::table('overtime_taxes')->where('fiscal_year', $year)->get();

            return $this->successResponse([
                'payroll_intervals' => $payroll_intervals,
                'data' => $data,
                'overtime_types' => $overtime_types,
                'ot_tax' => $ot_tax,
                'payroll_periods' => $payroll_periods,
                'employee_unselected' => $employee_unselected,
                'employees' => $employees
            ], 'Overtime payroll form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load overtime payroll form data: ' . $e->getMessage());
        }
    }

    public function storeOvertimePayroll(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'payroll_interval_id' => 'required',
                'payroll_period_id' => 'required'
            ], [
                'payroll_interval_id.required' => 'Payroll Interval is required.',
                'payroll_period_id.required' => 'Payroll Period is required.',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            if ($request->payroll_period_id == 0) {
                return $this->errorResponse('Payroll Period is required.', 400);
            }

            $rata_exist = DB::table('overtime_payroll_headers')
                ->where([
                    'payroll_period_id' => $request->payroll_period_id
                ])
                ->where('id', '<>', $id)
                ->count();

            if ($rata_exist > 0) {
                return $this->errorResponse('Overtime Payroll already exist!', 400);
            }

            if ($id == 0) {
                $id = DB::table('overtime_payroll_headers')->max('id') + 1;
            }

            $data = array(
                'payroll_period_id' => $request->payroll_period_id,
                'date_forwarded' => $request->date_forwarded
            );

            DB::unprepared('SET IDENTITY_INSERT overtime_payroll_headers ON');
            DB::table('overtime_payroll_headers')->updateOrInsert(['id' => $id], $data);
            DB::unprepared('SET IDENTITY_INSERT overtime_payroll_headers OFF');

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module' => 'Payroll Module',
                'menu' => 'Overtime Payroll',
                'activity' => 'Create',
                'description' => 'Created Overtime Payroll informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Successfully Created Overtime Payroll!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create overtime payroll: ' . $e->getMessage());
        }
    }

    public function storeOvertimePayrollEmployee(Request $request, $id)
    {
        try {
            $employee_data = $request->all();

            $data = [];

            if (isset($employee_data['id'])) {
                $arr_len = count($employee_data['id']);
                for ($i = 0; $i < $arr_len; $i++) {
                    if ($employee_data['id'][$i] != NULL) {
                        if (in_array($employee_data['id'][$i] . $employee_data['overtime_type_id'][$i], $employee_data['select'])) {

                            $data = [
                                'overtime_payroll_id' => $id,
                                'employee_id' => $employee_data['id'][$i],
                                'overtime_id' => 0,
                                'overtime_type_id' => $employee_data['overtime_type_id'][$i],
                            ];

                            DB::table('overtime_payroll_details')->insert($data);
                        }
                    }
                }
            }

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module' => 'Payroll Module',
                'menu' => 'Overtime Payroll',
                'activity' => 'Added',
                'description' => 'Added Overtime Employees.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Successfully Added Employees to Overtime Payroll!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add employees to overtime payroll: ' . $e->getMessage());
        }
    }

    public function deleteOvertimePayrollEmployee($id)
    {
        try {
            $data = DB::table('overtime_payroll_details')->where('id', $id)->delete();

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module' => 'Payroll Module',
                'menu' => 'Overtime Payroll',
                'activity' => 'Delete',
                'description' => 'Deleted Overtime Payroll Employees.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Successfully deleted overtime payroll employee!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete overtime payroll employee: ' . $e->getMessage());
        }
    }

    public function processOvertimePayroll($id, $type_id)
    {
        try {
            if ($type_id == 1) {
                DB::table('overtime_payroll_headers')->where('id', $id)->update(['posted' => true]);

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'Payroll Module',
                    'menu' => 'Overtime Payroll',
                    'activity' => 'Posted',
                    'description' => 'Posted Overtime Payroll.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'Successfully Posted Overtime Payroll!');
            } else {
                DB::table('overtime_payroll_headers')->where('id', $id)->update(['posted' => false]);

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'Payroll Module',
                    'menu' => 'Overtime Payroll',
                    'activity' => 'Unposted',
                    'description' => 'Unposted Overtime Payroll.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'Successfully Unposted Overtime Payroll!');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process overtime payroll: ' . $e->getMessage());
        }
    }
}
