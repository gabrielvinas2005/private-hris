<?php

namespace App\Http\Controllers;

use File;
use Response;
use Auth;
use App\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Traits\ApiResponse;

class PACSVALController extends Controller
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
            $departments = DB::table('departments')->where('active', true)->orderBy('name', 'asc')->get();

            $payrolls =
                DB::table('time_data as a')
                ->join('payroll_periods as b', 'b.id', '=', 'a.payroll_period_id')
                ->join('payroll_intervals as c', 'c.id', '=', 'b.payroll_interval_id')
                ->join('payroll_cutoffs as d', 'd.id', '=', 'b.payroll_cutoff_id')
                ->select(
                    'b.id',
                    DB::raw("CONCAT(c.name,' (',d.name,' - ',CONVERT(NVARCHAR(50),b.release_date,110),') ') as name"),
                    'b.release_date'
                )
                ->where(['b.active' => true, 'b.posted' => true])
                ->orderBy('b.release_date', 'asc')
                ->distinct()
                ->get();

            return $this->successResponse([
                'departments' => $departments,
                'payrolls' => $payrolls
            ], 'PACSVAL data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve PACSVAL data: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'payroll_period_id' => 'required',
                'department_id' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            /* USE THIS QUERY FOR MYSQL OR PGSQQL DATABASE */
            // $pacsval = DB::table('payroll_summaries as a')
            // ->join('employees as b', 'a.employee_id', '=', 'b.id')
            //     ->select(
            //         DB::raw("
            //         CONCAT(case when coalesce(b.account_no,'') = '' then '0000000000' else rtrim(b.account_no) end,''
            //         ,rtrim(CONCAT(upper(b.last_name),', ',upper(b.first_name),' ',upper(b.middle_name)))
            //         ,repeat(' ',50 - lenght(CONCAT(case when coalesce(b.account_no,'') = '' then '0000000000' else rtrim(b.account_no) end,'',rtrim(CONCAT(b.last_name,', ',b.first_name,' ',b.middle_name)))))
            //         ,substring(replace(cast((1000000000000000 + a.net_pay) as char(19)),'.',''),2,16),''
            //         ,substring(case when coalesce(b.account_no,'') = '' then '0000000000' else rtrim(b.account_no) end,1,3)
            //         ,'00001       '
            //         ) as content
            //         ")
            //     )
            //     ->where([
            //         'a.payroll_period_id' => $request->payroll_period_id,
            //         'b.department_id' => $request->department_id
            //     ])
            //     ->orderBy('b.last_name', 'asc')
            //     ->pluck('content');

            /* USE THIS QUERY FOR MS SQL DATABASE */

            $app_key = env("APP_KEY", "");

            $pacsval = DB::table('payroll_summaries as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->select(
                    DB::raw("
                    CONCAT(case when coalesce(b.account_no,'') = '' then '0000000000' else rtrim(b.account_no) end,''
                    ,CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN 
                                rtrim(CONCAT(upper(b.last_name),', ',upper(b.first_name),' ',upper(b.middle_name)))
                          ELSE
                                UPPER(RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))+', '+RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')+' '+RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key')))) 
                          END
                    ,REPLICATE(' ',50 - len(CONCAT(case when coalesce(b.account_no,'') = '' then '0000000000' else rtrim(b.account_no) end,'',CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN 
                                rtrim(CONCAT(upper(b.last_name),', ',upper(b.first_name),' ',upper(b.middle_name)))
                          ELSE
                                UPPER(RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))+', '+RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key')+' '+RTRIM([dbo].[ufn_DecryptString](b.middle_name,'$app_key')))) 
                          END)))
                    ,substring(replace(cast((1000000000000000 + a.net_pay) as char(19)),'.',''),2,16),''
                    ,substring(case when coalesce(b.account_no,'') = '' then '0000000000' else rtrim(b.account_no) end,1,3)
                    ,'00001       '
                    ) as content
                    ")
                )
                ->where([
                    'a.payroll_period_id' => $request->payroll_period_id,
                    'b.department_id' => $request->department_id
                ])
                ->orderBy('b.last_name', 'asc')
                ->pluck('content');

            if ($pacsval->isEmpty()) {
                return $this->errorResponse('No Data Found!');
            }

            $department = DB::table('departments')->where('id', $request->department_id)->orderBy('name', 'asc')->get();

            $payroll =
                DB::table('time_data as a')
                ->join('payroll_periods as b', 'b.id', '=', 'a.payroll_period_id')
                ->join('payroll_intervals as c', 'c.id', '=', 'b.payroll_interval_id')
                ->join('payroll_cutoffs as d', 'd.id', '=', 'b.payroll_cutoff_id')
                ->select(
                    'b.id',
                    DB::raw("CONCAT(c.name,' (',d.name,' - ',b.release_date,') ') as name"),
                    'b.release_date'
                )
                ->where('b.id', $request->payroll_period_id)
                ->distinct()
                ->get();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Payroll Module',
                'menu'    => 'PACSVAL',
                'activity' => 'Export',
                'description' => 'Exported PACSVAL File.',
            );

            Audit::create($data_audit);

            $data = array();

            foreach ($pacsval as $content) {
                $data[] = $content . PHP_EOL;
            }

            $fileName = $department[0]->name . '_' . $payroll[0]->name . '_pacsval.txt';
            $fileContent = implode('', $data);
            $base64Content = base64_encode($fileContent);

            return $this->successResponse([
                'file_content' => $base64Content,
                'filename' => $fileName,
                'content_type' => 'text/plain',
                'file_size' => strlen($fileContent),
                'department' => $department,
                'payroll' => $payroll,
                'summary' => [
                    'department_name' => $department[0]->name,
                    'payroll_period' => $payroll[0]->name,
                    'total_records' => $pacsval->count()
                ]
            ], 'PACSVAL file exported successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to export PACSVAL file: ' . $e->getMessage());
        }
    }
}
