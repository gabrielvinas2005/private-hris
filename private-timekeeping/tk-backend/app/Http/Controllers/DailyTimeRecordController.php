<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Font as WordFont;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class DailyTimeRecordController extends Controller
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

    public function index($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            // First, get the user information to check if they have an employee_no
            $user_info = DB::table('users')
                ->select('id', 'name', 'email', 'employee_no')
                ->where('id', $id)
                ->first();

            if (!$user_info) {
                return $this->errorResponse('User not found', 404);
            }

            // Check if user has employee_no set
            if (empty($user_info->employee_no)) {
                return $this->successResponse([
                    'employee_info' => null,
                    'dtr_records' => collect([]),
                    'debug_info' => [
                        'user_id' => $id,
                        'user_name' => $user_info->name,
                        'user_email' => $user_info->email,
                        'employee_no' => $user_info->employee_no,
                        'message' => 'User does not have an employee number assigned'
                    ]
                ], 'User is not linked to an employee record');
            }

            // Try to find the employee record
            $employee_record = DB::table('employees')
                ->where('employee_no', $user_info->employee_no)
                ->first();

            if (!$employee_record) {
                return $this->successResponse([
                    'employee_info' => null,
                    'dtr_records' => collect([]),
                    'debug_info' => [
                        'user_id' => $id,
                        'user_name' => $user_info->name,
                        'user_email' => $user_info->email,
                        'employee_no' => $user_info->employee_no,
                        'message' => 'Employee record not found for the given employee number'
                    ]
                ], 'Employee record not found');
            }

            // Get employee information with details
            $employee_info = DB::table('employees as a')
                ->leftJoin('departments as b', 'b.id', '=', 'a.department_id')
                ->leftJoin('positions as c', 'c.id', '=', 'a.position_id')
                ->leftJoin('employment_types as d', 'd.id', '=', 'a.employment_type_id')
                ->select(
                    'a.id as employee_id',
                    'a.photo',
                    'a.employee_no',
                    'b.name as department',
                    'c.name as position',
                    'd.name as employment_type',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name")
                )
                ->where('a.id', $employee_record->id)
                ->first();

            // Get DTR records for this employee
            $daily_time_records = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'b.id', '=', 'a.payroll_interval_id')
                ->join('payroll_cutoffs as c', 'c.id', '=', 'a.payroll_cutoff_id')
                ->join('time_data as d', 'd.payroll_period_id', '=', 'a.id')
                ->select(
                    'a.id',
                    'd.employee_id',
                    'b.name as payroll_interval',
                    'c.name as cut_off',
                    'a.attendance_start_date',
                    'a.attendance_end_date',
                    'a.id as payroll_period_id'
                )
                ->where('d.employee_id', $employee_record->id)
                ->orderBy('a.attendance_start_date', 'asc')
                ->distinct()
                ->get();

            if ($daily_time_records->isEmpty()) {
                $dummy_daily_time_records = array(
                    'id' => 0,
                    'employee_id' => $employee_record->id,
                    'payroll_interval' => null,
                    'cut_off' => null,
                    'attendance_start_date' => null,
                    'attendance_end_date' => null,
                    'payroll_period_id' => 0
                );

                $daily_time_records = (object)$dummy_daily_time_records;
                $daily_time_records = collect([$daily_time_records]);
            }

            // Prepare response data
            $response_data = [
                'employee_info' => $employee_info,
                'dtr_records' => $daily_time_records,
                'debug_info' => [
                    'user_id' => $id,
                    'user_name' => $user_info->name,
                    'user_email' => $user_info->email,
                    'employee_no' => $user_info->employee_no,
                    'employee_id' => $employee_record->id,
                    'message' => 'Successfully retrieved employee and DTR data'
                ]
            ];

            return $this->successResponse($response_data, 'Daily time records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve daily time records: ' . $e->getMessage());
        }
    }

    /**
     * Get DTR data by employee number (alternative method)
     */
    public function getByEmployeeNo($employee_no)
    {
        try {
            $app_key = env("APP_KEY", "");

            // Try to find the employee record directly by employee_no
            $employee_record = DB::table('employees')
                ->where('employee_no', $employee_no)
                ->first();

            if (!$employee_record) {
                return $this->errorResponse('Employee record not found for employee number: ' . $employee_no, 404);
            }

            // Get employee information with details
            $employee_info = DB::table('employees as a')
                ->leftJoin('departments as b', 'b.id', '=', 'a.department_id')
                ->leftJoin('positions as c', 'c.id', '=', 'a.position_id')
                ->leftJoin('employment_types as d', 'd.id', '=', 'a.employment_type_id')
                ->select(
                    'a.id as employee_id',
                    'a.photo',
                    'a.employee_no',
                    'b.name as department',
                    'c.name as position',
                    'd.name as employment_type',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name")
                )
                ->where('a.id', $employee_record->id)
                ->first();

            // Get DTR records for this employee
            $daily_time_records = DB::table('payroll_periods as a')
                ->join('payroll_intervals as b', 'b.id', '=', 'a.payroll_interval_id')
                ->join('payroll_cutoffs as c', 'c.id', '=', 'a.payroll_cutoff_id')
                ->join('time_data as d', 'd.payroll_period_id', '=', 'a.id')
                ->select(
                    'a.id',
                    'd.employee_id',
                    'b.name as payroll_interval',
                    'c.name as cut_off',
                    'a.attendance_start_date',
                    'a.attendance_end_date',
                    'a.id as payroll_period_id'
                )
                ->where('d.employee_id', $employee_record->id)
                ->orderBy('a.attendance_start_date', 'asc')
                ->distinct()
                ->get();

            if ($daily_time_records->isEmpty()) {
            $dummy_daily_time_records = array(
                'id' => 0,
                    'employee_id' => $employee_record->id,
                'payroll_interval' => null,
                'cut_off' => null,
                'attendance_start_date' => null,
                'attendance_end_date' => null,
                'payroll_period_id' => 0
            );

            $daily_time_records = (object)$dummy_daily_time_records;
            $daily_time_records = collect([$daily_time_records]);
        }

            // Prepare response data
            $response_data = [
                'employee_info' => $employee_info,
                'dtr_records' => $daily_time_records,
                'debug_info' => [
                    'employee_no' => $employee_no,
                    'employee_id' => $employee_record->id,
                    'message' => 'Successfully retrieved employee and DTR data by employee number'
                ]
            ];

            return $this->successResponse($response_data, 'Daily time records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve daily time records: ' . $e->getMessage());
        }
    }


    public function view($id, $payroll_period_id)
    {
        try {
            $app_key = env("APP_KEY", "");

        // get daily time records
        $daily_time_records = DB::table('time_data as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->leftJoin('departments as c', 'c.id', '=', 'b.department_id')
            ->leftJoin('positions as d', 'd.id', '=', 'b.position_id')
            ->join('payroll_periods as e', 'e.id', '=', 'a.payroll_period_id')
            ->join('employment_types as f', 'f.id', '=', 'b.employment_type_id')
            ->select(
                'a.id',
                'b.id as employee_id',
                'b.photo',
                'b.employee_no',
                'c.name as department',
                'd.name as position',
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                'a.date',
                'a.am_in',
                'a.am_out',
                'a.break_in',
                'a.break_out',
                'a.pm_in',
                'a.pm_out',
                'a.work_hours',
                'a.late',
                'a.undertime',
                'a.absent',
                'a.leave',
                'a.is_ob',
                'a.ob_id',
                'a.is_holiday',
                'a.holiday_id',
                'a.holiday_pay',
                'a.is_ot',
                'a.ot_id',
                'a.ot_pay',
                'a.ot_hours',
                'a.nd_pay',
                'a.remarks',
                'a.is_shifting',
                'a.work_schedule_id',
                'e.attendance_start_date',
                'e.attendance_end_date',
                'f.name as employment_type'
            )
            ->where([
                'a.payroll_period_id' => $payroll_period_id,
                'b.id' => $id,
                'b.active' => true,
                'b.is_employee' => true
            ])
            ->orderBy('date', 'asc')
            ->get();

        // get totals
        $totals = DB::table('time_data as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->select(
                DB::raw("sum(a.ot_hours) as ot"),
                DB::raw("sum(a.late) as late"),
                DB::raw("sum(a.undertime) as undertime"),
                DB::raw("sum(a.leave) as leave"),
                DB::raw("sum(a.absent) as absent"),
                DB::raw("sum(a.work_hours) as work_hours")
            )
            ->where([
                'a.payroll_period_id' => $payroll_period_id,
                'b.id' => $id,
                'b.active' => true,
                'b.is_employee' => true
            ])
            ->groupBy('a.employee_id')
            ->get();

        return $this->successResponse([
            'daily_time_records' => $daily_time_records,
            'totals' => $totals
        ], 'Daily time record details retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve daily time record details: ' . $e->getMessage());
        }
    }

    public function logs($id)
    {
        try {
            $app_key = env("APP_KEY", "");

        // get daily time records
        $daily_time_records = DB::table('employees as b')
            ->leftJoin('departments as c', 'c.id', '=', 'b.department_id')
            ->leftJoin('positions as d', 'd.id', '=', 'b.position_id')
            ->leftJoin('employment_types as f', 'f.id', '=', 'b.employment_type_id')
            ->select(
                'b.id as employee_id',
                'b.photo',
                'b.employee_no',
                'c.name as department',
                'd.name as position',
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                'f.name as employment_type'
            )
            ->where([
                'b.id' => $id,
                'b.active' => true,
                'b.is_employee' => true
            ])
            ->orderBy('b.id', 'asc')
            ->get();

        return $this->successResponse($daily_time_records, 'Daily time record view retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve daily time record view: ' . $e->getMessage());
        }
    }

    public function getLogs($id, $from, $to)
    {
        try {
            $app_key = env("APP_KEY", "");

        // Check if Biometrics Server is Connected
        try {
            $pdo = DB::connection('sqlsrv_bio')->getPdo();
            $is_bio_connected = false;

            $database = DB::connection('sqlsrv_bio')->getDatabaseName();

            if ($database != '') {

                $bio_server = secEnv('DB_HOST_BIO', '');
                $bio_username = secEnv('DB_USERNAME_BIO', '');
                $bio_password = secEnv('DB_PASSWORD_BIO', '');

                if ($bio_server == '') {
                    $bio_server = env('DB_HOST_BIO', '');
                    $bio_username = env('DB_USERNAME_BIO', '');
                    $bio_password = env('DB_PASSWORD_BIO', '');
                }

                if ($bio_server != '') {
                    DB::unprepared("
                        DECLARE @Server_Name as nvarchar(250) = '$bio_server'
                        DECLARE @Username as nvarchar(50) = '$bio_username'
                        DECLARE @Password as nvarchar(50) = '$bio_password'

                        if @Server_Name = '.'
                            begin
                                set @Server_Name = N'(local)'
                            end
                        else
                            begin
                                set @Server_Name = N''+@Server_Name
                            end

                        -- Only create linked server if it doesn't exist - don't drop/recreate on every request
                        -- This was causing major performance issues when called frequently
                        if not exists(select * from sys.servers where name = N'SRV_NAME')
                            begin
                                EXEC sp_addlinkedserver @server='SRV_NAME',
                                                @srvproduct=N'',
                                                @provider=N'MSOLEDBSQL',   
                                                @datasrc=@Server_Name;

                                EXEC sp_addlinkedsrvlogin 'SRV_NAME', 'false', NULL,  @Username, @Password;
                            end
                    ");
                }

                $is_bio_connected = true;
            } else {
                $is_bio_connected = false;
            }

            if ($is_bio_connected) {
                // Database Configuration Query.
                $database1 = DB::connection('sqlsrv')->getDatabaseName();
                $database2 = 'SRV_NAME.' . DB::connection('sqlsrv_bio')->getDatabaseName();
            }
        } catch (\Throwable $th) {
            $is_bio_connected = false;
        }

        $date = $from;
        $employee_data = db::table('employees')->where('id', $id)->get();

        for ($date = $from; $date <= $to; $date = date("Y-m-d", strtotime("$date +1 day"))) {

            //  check log if exist.
            $existing_time_data = db::table('time_data')
                ->where('employee_id', $id)
                ->where('date', $date)
                ->get();

            // insert data if not existing in time data table.
            if (count($existing_time_data) == 0) {

                if ($is_bio_connected) {
                    // check timelogs in biometrics.
                    $biometrics = DB::select("
                            SELECT DISTINCT
                                c.id,
                                convert(date,b.checktime) as date,
                                CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                    UPPER(CONCAT(c.first_name,' ',SUBSTRING(c.middle_name,1,1),'. ',c.last_name))
                                ELSE
                                    UPPER(RTRIM(dbo.ufn_DecryptString(c.first_name,'$app_key')))+' '+UPPER(RTRIM(SUBSTRING(dbo.ufn_DecryptString(c.middle_name,'$app_key'),1,1)))+'. '+UPPER(RTRIM(dbo.ufn_DecryptString(c.last_name,'$app_key')))
                                END as name,
                                convert(nvarchar(50),b.checktime,101) as checktime,
                                (select top(1) isnull(bio.checktime,'') from $database2.dbo.checkinout as bio where bio.checktype = 'I' and convert(nvarchar(50),bio.checktime,101) = convert(nvarchar(50),b.checktime,101) and bio.userid = b.userid order by bio.checktime asc) as am_in,
                                (select top(1) isnull(bio.checktime,'') from $database2.dbo.checkinout as bio where bio.checktype = '0' and convert(nvarchar(50),bio.checktime,101) = convert(nvarchar(50),b.checktime,101) and bio.userid = b.userid order by bio.checktime desc) as am_out,
                                (select top(1) isnull(bio.checktime,'') from $database2.dbo.checkinout as bio where bio.checktype = '1' and convert(nvarchar(50),bio.checktime,101) = convert(nvarchar(50),b.checktime,101) and bio.userid = b.userid order by bio.checktime asc) as pm_in,
                                (select top(1) isnull(bio.checktime,'') from $database2.dbo.checkinout as bio where bio.checktype = 'O' and convert(nvarchar(50),bio.checktime,101) = convert(nvarchar(50),b.checktime,101) and bio.userid = b.userid order by bio.checktime desc) as pm_out
                            FROM $database2.dbo.userinfo as a inner join
                                 $database2.dbo.checkinout as b on a.userid = b.userid inner join
                                 $database1.dbo.employees as c on a.UserCode = c.access_no
                            WHERE c.id in ($id) 
                            AND b.checktime = '$date'
                            ORDER BY id,date ASC
                        ");
                } else {
                    $biometrics = null;
                }

                if ($biometrics != null) {
                    $time_data = array(
                        'employee_id' => $id,
                        'payroll_period_id' => 0,
                        'date' => $date,
                        'am_in' => $biometrics[0]->am_in,
                        'am_out' => $biometrics[0]->am_out,
                        'break_in' => null,
                        'break_out' => null,
                        'pm_in' => $biometrics[0]->pm_in,
                        'pm_out' => $biometrics[0]->pm_out,
                        'work_hours' => 0,
                        'late' => 0,
                        'undertime' => 0,
                        'absent' => 0,
                        'leave' => 0,
                        'is_ob' => false,
                        'ob_id' => 0,
                        'is_holiday' => false,
                        'holiday_id' => 0,
                        'holiday_pay' => 0,
                        'is_ot' => false,
                        'ot_id' => 0,
                        'ot_pay' => 0,
                        'nd_pay' => 0,
                        'remarks' => '',
                        'is_shifting' => true,
                        'work_schedule_id' => $employee_data[0]->work_schedule_id,
                        'ob_hours' => 0,
                        'ot_hours' => 0,
                        'for_approval' => 0,
                        'is_edited' => 0
                    );
                } else {
                    $time_data = array(
                        'employee_id' => $id,
                        'payroll_period_id' => 0,
                        'date' => $date,
                        'am_in' => null,
                        'am_out' => null,
                        'break_in' => null,
                        'break_out' => null,
                        'pm_in' => null,
                        'pm_out' => null,
                        'work_hours' => 0,
                        'late' => 0,
                        'undertime' => 0,
                        'absent' => 0,
                        'leave' => 0,
                        'is_ob' => false,
                        'ob_id' => 0,
                        'is_holiday' => false,
                        'holiday_id' => 0,
                        'holiday_pay' => 0,
                        'is_ot' => false,
                        'ot_id' => 0,
                        'ot_pay' => 0,
                        'nd_pay' => 0,
                        'remarks' => '',
                        'is_shifting' => true,
                        'work_schedule_id' => $employee_data[0]->work_schedule_id,
                        'ob_hours' => 0,
                        'ot_hours' => 0,
                        'for_approval' => 0,
                        'is_edited' => 0
                    );
                }

                DB::table('time_data')->Insert($time_data);
            }
        }

        // get daily time records from time data
        $data = DB::table('time_data as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->leftJoin('departments as c', 'c.id', '=', 'b.department_id')
            ->leftJoin('positions as d', 'd.id', '=', 'b.position_id')
            ->leftJoin('employment_types as f', 'f.id', '=', 'b.employment_type_id')
            ->select(
                'a.id',
                'b.id as employee_id',
                'b.photo',
                'b.employee_no',
                'c.name as department',
                'd.name as position',
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                'a.date',
                'a.am_in',
                'a.am_out',
                'a.break_in',
                'a.break_out',
                'a.pm_in',
                'a.pm_out',
                'a.work_hours',
                'a.late',
                'a.undertime',
                'a.absent',
                'a.leave',
                'a.is_ob',
                'a.ob_id',
                'a.is_holiday',
                'a.holiday_id',
                'a.holiday_pay',
                'a.is_ot',
                'a.ot_id',
                'a.ot_pay',
                'a.ot_hours',
                'a.nd_pay',
                'a.remarks',
                'a.is_shifting',
                'a.work_schedule_id',
                'f.name as employment_type',
                'a.is_edited',
                'a.payroll_period_id',
                'a.attachment_name'
            )
            ->where([
                'a.employee_id' => $id,
                'b.active' => true,
                'b.is_employee' => true
            ])
            ->whereBetween('a.date', [$from, $to])
            ->distinct()
            ->orderBy('date', 'asc')
            ->get();

        return json_encode($data);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve logs: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $app_key = env("APP_KEY", "");

        if ($request->hasFile('attachment') == false) {
            return $this->errorResponse('Please attach file on date need to be reviewed to save request.', 400);
        }

        $data = $request->all();
        $employee_data = db::table('employees')->where('id', $id)->get();

        // Save log to request DTR Table
        $data_request = [];

        // check if there is pending request
        $daily_time_record_request = DB::table('time_data_request')
            ->where([
                'employee_id' => $id,
                'status' => 0
            ])
            ->get();

        if ($daily_time_record_request->isEmpty()) {
            $data_request = [
                'employee_id' => $id,
                'request_date' => now(),
                'status' => false,
                'approved_1' => 0,
                'approved_by_1_id' => 0,
                'approved_date_1' => '',
                'disapproved_1' => 0,
                'disapproved_by_1_id' => 0,
                'disapproved_date_1' => '',
                'approved_2' => 0,
                'approved_by_2_id' => 0,
                'approved_date_2' => '',
                'disapproved_2' => 0,
                'disapproved_by_2_id' => 0,
                'disapproved_date_2' => '',
            ];

            DB::table('time_data_request')->Insert($data_request);
        }

        $arr_len = count($data['date']);
        $time_data = [];

        for ($i = 0; $i < $arr_len; $i++) {

            // Save Attachments
            if ($request->hasFile('attachment')) {

                $allowedfileExtension = ['pdf', 'jpg', 'png', 'doc', 'docx', 'xlsx', 'xls'];
                $files = $request->file('attachment');

                if (isset($files[$i])) {
                    $file_name = $files[$i]->getClientOriginalName();
                    $file_path = Storage::disk('local')->getAdapter()->getPathPrefix() . 'employee_DTR_documents\\' . 'DOCS' . $id . $data['id'][$i] . '_' . $file_name;
                    $extension = $files[$i]->getClientOriginalExtension();
                    $check = in_array($extension, $allowedfileExtension);

                    if ($check) {
                        // Save record of attachments to database.
                        if (
                            $data['payroll_period_id'][$i] == 0 || $data['payroll_period_id'][$i] == null
                        ) {
                            $time_data = [
                                'employee_id' => $id,
                                'payroll_period_id' => 0,
                                'date' => $data['date'][$i],
                                'am_in' => $data['am_in'][$i],
                                'am_out' => $data['am_out'][$i],
                                'break_in' => $data['break_in'][$i],
                                'break_out' => $data['break_out'][$i],
                                'pm_in' => $data['pm_in'][$i],
                                'pm_out' => $data['pm_out'][$i],
                                'work_hours' => 0,
                                'late' => 0,
                                'undertime' => 0,
                                'absent' => 0,
                                'leave' => 0,
                                'is_ob' => false,
                                'ob_id' => 0,
                                'is_holiday' => false,
                                'holiday_id' => 0,
                                'holiday_pay' => 0,
                                'is_ot' => false,
                                'ot_id' => 0,
                                'ot_pay' => 0,
                                'nd_pay' => 0,
                                'remarks' => '',
                                'is_shifting' => true,
                                'work_schedule_id' => $employee_data[0]->work_schedule_id,
                                'ob_hours' => 0,
                                'ot_hours' => 0,
                                'for_approval' => 0,
                                'is_edited' => 1,
                                'attachment_name' => $file_name,
                                'path' => $file_path,
                                'extension' => $extension
                            ];
                        } else {
                            $time_data = [
                                'employee_id' => $id,
                                'date' => $data['date'][$i],
                                'am_in' => $data['am_in'][$i],
                                'am_out' => $data['am_out'][$i],
                                'break_in' => $data['break_in'][$i],
                                'break_out' => $data['break_out'][$i],
                                'pm_in' => $data['pm_in'][$i],
                                'pm_out' => $data['pm_out'][$i],
                                'work_hours' => 0,
                                'late' => 0,
                                'undertime' => 0,
                                'absent' => 0,
                                'leave' => 0,
                                'is_ob' => false,
                                'ob_id' => 0,
                                'is_holiday' => false,
                                'holiday_id' => 0,
                                'holiday_pay' => 0,
                                'is_ot' => false,
                                'ot_id' => 0,
                                'ot_pay' => 0,
                                'nd_pay' => 0,
                                'remarks' => '',
                                'is_shifting' => true,
                                'work_schedule_id' => $employee_data[0]->work_schedule_id,
                                'ob_hours' => 0,
                                'ot_hours' => 0,
                                'for_approval' => 0,
                                'is_edited' => 1,
                                'attachment_name' => $file_name,
                                'path' => $file_path,
                                'extension' => $extension
                            ];
                        }

                        // Update or Insert Time Data Table
                        // $log_exist = DB::table('time_data')->where('id', $data['id'][$i])->get();

                        // if (count($log_exist) > 0) {
                        //     DB::table('time_data')->where('id', $data['id'][$i])->update($time_data);
                        // } else {
                        //     DB::table('time_data')->insert($time_data);
                        // }

                        // Update Time Data Table
                        DB::table('time_data')->where('id', $data['id'][$i])->update($time_data);

                        // Save attachment to path.
                        $request->attachment[$i]->storeAs('employee_DTR_documents', 'DOCS' . $id . $data['id'][$i] . '_' . $file_name);
                    }
                }
            }
        }

        return $this->successResponse(null, 'Time data was saved and submitted to HR.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to store time data: ' . $e->getMessage());
        }
    }

    public function loadDTRRequest($id)
    {
        try {
            $app_key = env("APP_KEY", "");

        $emp_id_data = DB::table('users as a')
            ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
            ->selectRaw('case when b.id = null then 0 else b.id end as id')
            ->where('a.id', $id)
            ->get();

        if ($emp_id_data->isNotEmpty()) {
            $emp_id = $emp_id_data[0]->id;

            // Check if Approver Start
            $approver_1 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select(
                    'a.id',
                    'a.branch_approver_id_1 as supervisor_id'
                )
                ->where('a.branch_approver_id_1', $emp_id)
                ->orWhere('a.approver_id_1', $emp_id)
                ->orWhere('a.division_approver_id_1', $emp_id)
                ->orWhere('a.section_approver_id_1', $emp_id)
                ->distinct()
                ->get();

            $approver_2 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select(
                    'a.id',
                    'a.approver_id_2 as supervisor_id'
                )
                ->where('a.approver_id_2', $emp_id)
                ->distinct()
                ->get();

            $approver_3 = DB::table('approver_headers as a')
                ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
                ->select(
                    'a.id',
                    'a.approver_id_3 as supervisor_id'
                )
                ->where('a.approver_id_3', $emp_id)
                ->distinct()
                ->get();

            if ($approver_1->isNotEmpty() && $approver_2->isEmpty()) {
                $approver_id = $approver_1[0]->id;
                $supervisor_id = $approver_1[0]->supervisor_id;

                // Get for Approvals
                $data = DB::table('time_data_request as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->leftJoin(
                        'branches as c',
                        'b.branch_id',
                        '=',
                        'c.id'
                    )
                    ->leftJoin(
                        'departments as d',
                        'b.department_id',
                        '=',
                        'd.id'
                    )
                    ->leftJoin('divisions as e', 'b.division_id', '=', 'e.id')
                    ->leftJoin(
                        'sections as f',
                        'b.section_id',
                        '=',
                        'f.id'
                    )
                    ->leftJoin('positions as g', 'b.position_id', '=', 'g.id')
                    ->select(
                        'a.id',
                        'b.photo',
                        'a.employee_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                        'a.status',
                        'a.request_date',
                        'c.name as branch',
                        'd.name as department',
                        'e.name as division',
                        'f.name as section',
                        'g.name as position'
                    )
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.branch_approver_id_1', $emp_id)
                            ->orWhere('b.approver_id_1', $emp_id)
                            ->orWhere('b.division_approver_id_1', $emp_id)
                            ->orWhere('b.section_approver_id_1', $emp_id);
                    })
                    ->whereNotIn('b.id', function ($query) use ($emp_id) {
                        $query->select('id')
                            ->from('employees')
                            ->where('id', $emp_id);
                    })
                    ->orderBy('request_date', 'desc')
                    ->get();
            } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty()) {
                $approver_id = $approver_2[0]->id;
                $supervisor_id = $approver_2[0]->supervisor_id;

                // Get for Approvals
                $data = DB::table('time_data_request as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->leftJoin('branches as c', 'b.branch_id', '=', 'c.id')
                    ->leftJoin(
                        'departments as d',
                        'b.department_id',
                        '=',
                        'd.id'
                    )
                    ->leftJoin('divisions as e', 'b.division_id', '=', 'e.id')
                    ->leftJoin('sections as f', 'b.section_id', '=', 'f.id')
                    ->leftJoin('positions as g', 'b.position_id', '=', 'g.id')
                    ->select(
                        'a.id',
                        'b.photo',
                        'a.employee_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                        'a.status',
                        'a.request_date',
                        'c.name as branch',
                        'd.name as department',
                        'e.name as division',
                        'f.name as section',
                        'g.name as position'
                    )
                    ->where([
                        'a.approved_1' => true,
                        'a.disapproved_1' => false
                    ])
                    ->whereIn('b.id', function ($query) use ($approver_id) {
                        $query->select('employee_id')
                            ->from('approver_details')
                            ->where('approver_id', $approver_id);
                    })
                    ->whereNotIn('b.id', function ($query) use ($emp_id) {
                        $query->select('id')
                            ->from('employees')
                            ->where('id', $emp_id);
                    })
                    ->orderBy('request_date', 'desc')
                    ->get();
            } elseif ($approver_1->isNotEmpty() && $approver_2->isNotEmpty()) {
                $approver_id = $approver_2[0]->id;
                $supervisor_id = $approver_2[0]->supervisor_id;

                // Get for Approvals
                $data = DB::table('time_data_request as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->leftJoin(
                        'branches as c',
                        'b.branch_id',
                        '=',
                        'c.id'
                    )
                    ->leftJoin(
                        'departments as d',
                        'b.department_id',
                        '=',
                        'd.id'
                    )
                    ->leftJoin('divisions as e', 'b.division_id', '=', 'e.id')
                    ->leftJoin(
                        'sections as f',
                        'b.section_id',
                        '=',
                        'f.id'
                    )
                    ->leftJoin('positions as g', 'b.position_id', '=', 'g.id')
                    ->select(
                        'a.id',
                        'b.photo',
                        'a.employee_id',
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                        'a.status',
                        'a.request_date',
                        'c.name as branch',
                        'd.name as department',
                        'e.name as division',
                        'f.name as section',
                        'g.name as position'
                    )
                    ->where([
                        'a.disapproved_1' => false,
                    ])
                    ->whereIn('b.id', function ($query) use ($emp_id) {
                        $query->select('a.employee_id')
                            ->from('approver_details as a')
                            ->join('approver_headers as b', 'a.approver_id', '=', 'b.id')
                            ->where('b.branch_approver_id_1', $emp_id)
                            ->orWhere('b.approver_id_1', $emp_id)
                            ->orWhere('b.approver_id_2', $emp_id)
                            ->orWhere('b.division_approver_id_1', $emp_id)
                            ->orWhere('b.section_approver_id_1', $emp_id);
                    })
                    ->whereNotIn('b.id', function ($query) use ($emp_id) {
                        $query->select('id')
                            ->from('employees')
                            ->where('id', $emp_id);
                    })
                    ->orderBy('request_date', 'desc')
                    ->get();
            } else {
                $approver_id = 0;
                $supervisor_id = 0;
                $data  = [];
            }
        } else {
            $emp_id = 0;

            $dummy_data = array(
                'id' => 0,
                'photo' => '',
                'employee_id' => 0,
                'name' => '',
                'status' => '',
                'request_date' => '',
                'branch' => '',
                'department' => '',
                'division' => '',
                'section' => '',
                'position' => ''
            );

            $data = (object)$dummy_data;
            $data = collect([$data]);
        }

        return $this->successResponse([
            'employee_id' => $emp_id,
            'records' => $data
        ], 'Review daily time records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load DTR request: ' . $e->getMessage());
        }
    }

    public function reviewDTRRequest($id)
    {
        try {
            $app_key = env("APP_KEY", "");

        $daily_time_records = DB::table('time_data_request as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->leftJoin('branches as c', 'b.branch_id', '=', 'c.id')
            ->leftJoin('departments as d', 'b.department_id', '=', 'd.id')
            ->leftJoin('divisions as e', 'b.division_id', '=', 'e.id')
            ->leftJoin('sections as f', 'b.section_id', '=', 'f.id')
            ->join('positions as g', 'b.position_id', '=', 'g.id')
            ->select(
                'a.id',
                'b.photo',
                'a.employee_id',
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as name"),
                'a.status',
                'a.request_date',
                'c.name as branch',
                'd.name as department',
                'e.name as division',
                'f.name as section',
                'g.name as position',
                'b.payroll_interval_id',
                'approved_1'
            )
            ->where('a.id', $id)
            ->get();

        $time_data = DB::table('time_data')
            ->where([
                'payroll_period_id' => 0,
                'for_approval' => 0,
                'is_edited' => 1,
                'employee_id' => $daily_time_records[0]->employee_id
            ])
            ->orderBy('date', 'asc')
            ->get();

        $time_data_request = DB::table('time_data as a')
            ->join('payroll_periods as b', 'a.payroll_period_id', '=', 'b.id')
            ->join('payroll_intervals as c', 'b.payroll_interval_id', '=', 'c.id')
            ->select(
                'a.*',
                DB::raw("CONCAT(c.name,' (', DATENAME(MONTH,b.release_date),' ',DATEPART(YEAR, b.release_date) ,')') as payroll_period")
            )
            ->where([
                'a.employee_id' => $daily_time_records[0]->employee_id,
                'a.dtr_request_id' => $id
            ])
            ->orderBy('date', 'asc')
            ->get();

        $payroll_periods = DB::table('payroll_periods as a')
            ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
            ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
            ->select(
                'a.id',
                DB::raw("CONCAT(b.name,' (',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date),') Attendance From: ',convert(nvarchar(50),a.attendance_start_date,107),' To ',convert(nvarchar(50),a.attendance_end_date,107)) as name")
            )
            ->where(['posted' => false, 'a.active' => true, 'a.payroll_interval_id' => $daily_time_records[0]->payroll_interval_id])
            ->orderBy('a.release_date', 'desc')
            ->get();

        $emp_id_data = DB::table('users as a')
            ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
            ->selectRaw('case when b.id = null then 0 else b.id end as id')
            ->where('a.id', Auth::user()->id)
            ->get();

        if ($emp_id_data->isNotEmpty()) {
            $emp_id = $emp_id_data[0]->id;
        } else {
            $emp_id = 0;
        }

        $approver_2 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select(
                'a.id',
                'a.approver_id_2 as supervisor_id'
            )
            ->where('a.approver_id_2', $emp_id)
            ->distinct()
            ->get();

        if ($approver_2->isNotEmpty()) {
            $is_second_approver = true;
        } else {
            $is_second_approver = false;
        }


        return $this->successResponse([
            'daily_time_records' => $daily_time_records,
            'time_data' => $time_data,
            'payroll_periods' => $payroll_periods,
            'time_data_request' => $time_data_request,
            'is_second_approver' => $is_second_approver
        ], 'Review daily time records data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to review DTR request: ' . $e->getMessage());
        }
    }

    public function approve(Request $request, $id, $type_id)
    {
        try {
            $app_key = env("APP_KEY", "");

        // Absolute approver bypass: admin can approve/disapprove without approver mapping
        if (Auth::user() && Auth::user()->is_admin) {
            if ($type_id == 1) {
                $request->validate([
                    'payroll_period_id' => 'required'
                ]);

                if ($request->has('select') == false) {
                    return $this->errorResponse('Select atleast 1 date to approve.', 400);
                }

                $data = $request->all();

                for ($i = 0; $i <= (count($data["date"]) - 1); $i++) {
                    if (in_array($data['date'][$i], $data['select'], true)) {
                        DB::table('time_data')->where('id', $data['id'][$i])
                            ->update([
                                'am_in' => $data['am_in'][$i],
                                'am_out' => $data['am_out'][$i],
                                'break_in' => $data['break_in'][$i],
                                'break_out' => $data['break_out'][$i],
                                'pm_in' => $data['pm_in'][$i],
                                'pm_out' => $data['pm_out'][$i],
                                'for_approval' => 1,
                                'payroll_period_id' => $request->payroll_period_id,
                                'is_edited' => 0,
                                'dtr_request_id' => $id
                            ]);
                    }
                }

                return $this->successResponse(null, 'Successfully Approved Time logs.');
            } elseif ($type_id == 2) {
                $data = [
                    'approved_1' => 1,
                    'approved_by_1_id' => 0,
                    'approved_date_1' => now(),
                    'approved_2' => 1,
                    'approved_by_2_id' => 0,
                    'approved_date_2' => now(),
                    'status' => 1
                ];

                DB::table('time_data_request')->where('id', $id)->update($data);

                return $this->successResponse(null, 'Successfully Approved Time logs Request!');
            } else {
                $data = [
                    'disapproved_1' => 1,
                    'disapproved_by_1_id' => 0,
                    'disapproved_date_1' => now(),
                    'disapproved_2' => 1,
                    'disapproved_by_2_id' => 0,
                    'disapproved_date_2' => now(),
                    'status' => 1
                ];

                // update time logs
                DB::table('time_data')->where('dtr_request_id', $id)
                    ->update([
                        'am_in' => null,
                        'am_out' => null,
                        'break_in' => null,
                        'break_out' => null,
                        'pm_in' => null,
                        'pm_out' => null,
                        'for_approval' => 0,
                        'payroll_period_id' => 0,
                        'is_edited' => 1,
                    ]);

                DB::table('time_data_request')->where('id', $id)->update($data);

                return $this->successResponse(null, 'Successfully Disapproved Time logs Request!');
            }
        }

        $emp_id_data = DB::table('users as a')
            ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
            ->selectRaw('case when b.id = null then 0 else b.id end as id')
            ->where('a.id', Auth::user()->id)
            ->get();

        if ($emp_id_data->isNotEmpty()) {
            $emp_id = $emp_id_data[0]->id;
        } else {
            $emp_id = 0;
        }

        $employee = DB::table('time_data_request')->select('employee_id')->where('id', $id)->get();

        if ($employee->isNotEmpty()) {
            $employee_id = $employee[0]->employee_id;
        } else {
            $employee_id = 0;
        }

        // Check if Approver
        $approver_1 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select(
                'a.id',
                'a.branch_approver_id_1 as supervisor_id'
            )
            ->whereRaw("
                        b.employee_id = $employee_id
                        AND (a.branch_approver_id_1 = $emp_id OR a.approver_id_1  = $emp_id or a.division_approver_id_1 = $emp_id or a.section_approver_id_1 = $emp_id)
                    ")
            ->distinct()
            ->get();

        $approver_2 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select(
                'a.id',
                'a.approver_id_2 as supervisor_id'
            )
            ->where('b.employee_id', $employee_id)
            ->where('a.approver_id_2', $emp_id)
            ->distinct()
            ->get();

        if ($type_id == 1) {

            $request->validate([
                'payroll_period_id' => 'required'
            ]);

            if ($request->has('select') == false) {
                return $this->errorResponse('Select atleast 1 date to approve.', 400);
            }

            $data = $request->all();

            for ($i = 0; $i <= (count($data["date"]) - 1); $i++) {
                if (in_array($data['date'][$i], $data['select'], true)) {
                    DB::table('time_data')->where('id', $data['id'][$i])
                        ->update([
                            'am_in' => $data['am_in'][$i],
                            'am_out' => $data['am_out'][$i],
                            'break_in' => $data['break_in'][$i],
                            'break_out' => $data['break_out'][$i],
                            'pm_in' => $data['pm_in'][$i],
                            'pm_out' => $data['pm_out'][$i],
                            'for_approval' => 1,
                            'payroll_period_id' => $request->payroll_period_id,
                            'is_edited' => 0,
                            'dtr_request_id' => $id
                        ]);
                }
            }

            return $this->successResponse(null, 'Successfully Approved Time logs.');
        } elseif ($type_id == 2) {

            if ($approver_1->isNotEmpty()) {
                $data = [
                    'approved_1' => 1,
                    'approved_by_1_id' => $approver_1[0]->id,
                    'approved_date_1' => now(),
                    'status' => 0
                ];
            } elseif ($approver_2->isNotEmpty()) {
                $data = [
                    'approved_2' => 1,
                    'approved_by_2_id' => $approver_2[0]->id,
                    'approved_date_2' => now(),
                    'status' => 1
                ];
            }

            DB::table('time_data_request')->where('id', $id)->update($data);

            return $this->successResponse(null, 'Successfully Approved Time logs Request!');
        } else {

            if ($approver_1->isNotEmpty()) {
                $data = [
                    'disapproved_1' => 1,
                    'disapproved_by_1_id' => $approver_1[0]->id,
                    'disapproved_date_1' => '',
                    'status' => 1
                ];

                // update time logs
                DB::table('time_data')->where('dtr_request_id', $id)
                    ->update([
                        'am_in' => null,
                        'am_out' => null,
                        'break_in' => null,
                        'break_out' => null,
                        'pm_in' => null,
                        'pm_out' => null,
                        'for_approval' => 0,
                        'payroll_period_id' => 0,
                        'is_edited' => 1,
                    ]);
            } elseif ($approver_2->isNotEmpty()) {
                $data = [
                    'disapproved_2' => 1,
                    'disapproved_by_2_id' => $approver_2[0]->id,
                    'disapproved_date_2' => '',
                    'status' => 1
                ];

                // update time logs
                DB::table('time_data')->where('dtr_request_id', $id)
                    ->update([
                        'am_in' => null,
                        'am_out' => null,
                        'break_in' => null,
                        'break_out' => null,
                        'pm_in' => null,
                        'pm_out' => null,
                        'for_approval' => 0,
                        'payroll_period_id' => 0,
                        'is_edited' => 1,
                    ]);
            }

            DB::table('time_data_request')->where('id', $id)->update($data);

            return $this->successResponse(null, 'Successfully Disapproved Time logs Request!');
        }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to approve time logs: ' . $e->getMessage());
        }
    }

    /**
     * Export DTR DOCX from DailyTimeRecordController.
     * Non-DTR report types are delegated to ReportDocxController.
     */
    public function exportDocxReport(Request $request)
    {
        $reportType = (string) $request->input('report_type', '');
        if ($reportType !== 'daily_time_record') {
            return app(ReportDocxController::class)->generate($request);
        }

        try {
            $validated = $request->validate([
                'report_type' => 'required|string|in:daily_time_record',
                'data' => 'required|array',
                'filename' => 'nullable|string',
                'paper_size' => 'nullable|string|in:A4,Letter',
                'orientation' => 'nullable|string|in:portrait,landscape',
            ]);

            $data = $validated['data'];
            $filename = $validated['filename'] ?? ('daily_time_record_' . date('Ymd_His'));
            $paperSize = $validated['paper_size'] ?? 'A4';
            $orientation = $validated['orientation'] ?? 'portrait';

            $phpWord = $this->createDailyTimeRecordDocxDocument($data, $paperSize, $orientation);

            $tempFile = tempnam(sys_get_temp_dir(), 'docx_report_');
            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($tempFile);

            $wordContent = file_get_contents($tempFile);
            @unlink($tempFile);

            $origin = $request->headers->get('Origin', '*');

            return response($wordContent)
                ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '.docx"')
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Vary', 'Origin');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate DTR DOCX: ' . $e->getMessage());
        }
    }

    /**
     * Export DTR Excel from DailyTimeRecordController.
     * Non-DTR report types are delegated to ReportExcelController.
     */
    public function exportExcelReport(Request $request)
    {
        $reportType = (string) $request->input('report_type', '');
        if ($reportType !== 'daily_time_record') {
            return app(ReportExcelController::class)->generate($request);
        }

        try {
            $validated = $request->validate([
                'report_type' => 'required|string|in:daily_time_record',
                'data' => 'required|array',
                'filename' => 'nullable|string',
            ]);

            $data = $validated['data'];
            $filename = $validated['filename'] ?? ('daily_time_record_' . date('Ymd_His'));

            $spreadsheet = $this->createDailyTimeRecordExcelDocument($data);
            $tempFile = tempnam(sys_get_temp_dir(), 'excel_report_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            $excelContent = file_get_contents($tempFile);
            @unlink($tempFile);

            $origin = $request->headers->get('Origin', '*');

            return response($excelContent)
                ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '.xlsx"')
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Vary', 'Origin');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate DTR Excel: ' . $e->getMessage());
        }
    }

    private function createDailyTimeRecordDocxDocument(array $data, string $paperSize = 'A4', string $orientation = 'portrait'): PhpWord
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);

        $section = $phpWord->addSection([
            'paperSize' => $paperSize === 'Letter' ? 'Letter' : 'A4',
            'orientation' => $orientation,
            // Remove extra white space above content in DOCX
            'marginTop' => 0,
            'marginBottom' => 1440,
            'marginLeft' => 0,
            'marginRight' => 0,
        ]);

        $this->buildDailyTimeRecordDocx($section, $data);
        return $phpWord;
    }

    private function createDailyTimeRecordExcelDocument(array $data): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daily Time Record');
        $this->buildDailyTimeRecordExcel($sheet, $data);
        return $spreadsheet;
    }

    private function buildDailyTimeRecordDocx($section, array $data): void
    {
        $oneInch = 1440;
        $contentInner = $oneInch * 2;       // Blade .content-inner (2in)
        $periodInner = $oneInch;            // Blade .period-content-inner (1in)
        $tableInner = intval($oneInch * 1.5); // Blade .table-content-inner (1.5in)
        $certInner = $oneInch * 2;          // Blade .certification-content-inner (2in)
        $docContentWidth = 7000;            // Working content width for DOCX inner blocks
        $rowHeaderHeight = 260;             // tighter header rows
        $rowBodyHeight = 240;               // tighter body rows
        $rowTotalHeight = 240;              // tighter total row

        $employee = $data['employee'] ?? [];
        $payrollPeriod = $data['payroll_period'] ?? [];
        $timeDataMap = $data['timeDataMap'] ?? [];
        $scheduleTimesMap = $data['scheduleTimesMap'] ?? [];
        $regularHours = $data['regular_hours'] ?? '';
        $saturdayHours = $data['saturday_hours'] ?? '';
        $approvers = $data['approvers'] ?? ['approver_1' => null];

        $startDate = !empty($payrollPeriod['attendance_start_date'])
            ? Carbon::parse($payrollPeriod['attendance_start_date'])->startOfDay()
            : Carbon::now()->startOfDay();
        $endDate = !empty($payrollPeriod['attendance_end_date'])
            ? Carbon::parse($payrollPeriod['attendance_end_date'])->startOfDay()
            : $startDate->copy()->endOfMonth();

        if ($startDate->format('Y-m') === $endDate->format('Y-m')) {
            $dtrPeriodLabel = 'For the month of';
            $dtrPeriodMonthYear = $startDate->format('F Y');
        } elseif ($startDate->format('Y') === $endDate->format('Y')) {
            $dtrPeriodLabel = 'For the months of';
            $dtrPeriodMonthYear = $startDate->format('F') . ' - ' . $endDate->format('F Y');
        } else {
            $dtrPeriodLabel = 'For the months of';
            $dtrPeriodMonthYear = $startDate->format('F Y') . ' - ' . $endDate->format('F Y');
        }

        $fn = trim((string) data_get($employee, 'first_name', ''));
        $mn = trim((string) data_get($employee, 'middle_name', ''));
        $ln = trim((string) data_get($employee, 'last_name', ''));
        if ($fn !== '' && $ln !== '') {
            $displayName = $mn !== '' ? $fn . ' ' . mb_substr($mn, 0, 1) . '. ' . $ln : $fn . ' ' . $ln;
        } else {
            $displayName = trim((string) data_get($employee, 'name', ''));
        }
        $employeeName = strtoupper($displayName !== '' ? $displayName : 'N/A');

        $formatTimeValue = static function ($value) {
            if (empty($value)) {
                return '';
            }
            try {
                return Carbon::parse($value)->format('h:i A');
            } catch (\Exception $e) {
                return '';
            }
        };
        $recVal = static fn ($rec, string $key, $default = null) => $rec === null ? $default : data_get($rec, $key, $default);

        $section->addText('CIVIL SERVICE FORM NO. 48', ['size' => 9, 'name' => 'Arial']);
        $section->addText('DAILY TIME RECORD', ['bold' => true, 'size' => 14, 'name' => 'Arial'], [
            'alignment' => Jc::CENTER,
            'indentation' => ['left' => $contentInner, 'right' => $contentInner],
        ]);
        $section->addText('---o0o---', ['size' => 10, 'name' => 'Arial'], [
            'alignment' => Jc::CENTER,
            'indentation' => ['left' => $contentInner, 'right' => $contentInner],
        ]);
        $section->addText($employeeName, ['bold' => true, 'size' => 17, 'name' => 'Arial'], [
            'alignment' => Jc::CENTER,
            'indentation' => ['left' => $contentInner, 'right' => $contentInner],
        ]);
        $section->addText('(Name)', ['italic' => true, 'size' => 10, 'name' => 'Arial'], [
            'alignment' => Jc::CENTER,
            'indentation' => ['left' => $contentInner, 'right' => $contentInner],
        ]);

        $periodRun = $section->addTextRun([
            'alignment' => Jc::LEFT,
            'indentation' => ['left' => $periodInner, 'right' => $periodInner],
        ]);
        $periodRun->addText($dtrPeriodLabel . ' ', ['italic' => true, 'size' => 14, 'name' => 'Arial']);
        $periodRun->addText($dtrPeriodMonthYear, ['italic' => true, 'size' => 14, 'name' => 'Arial', 'underline' => WordFont::UNDERLINE_SINGLE]);

        // Wrapper table to emulate blade 1in left/right margin for period details block
        $ohWrap = $section->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 0,
        ]);
        $ohWrap->addRow();
        $ohSpacerStyle = [
            'borderTopSize' => 0,
            'borderRightSize' => 0,
            'borderBottomSize' => 0,
            'borderLeftSize' => 0,
            'borderTopColor' => 'FFFFFF',
            'borderRightColor' => 'FFFFFF',
            'borderBottomColor' => 'FFFFFF',
            'borderLeftColor' => 'FFFFFF',
        ];
        $ohWrap->addCell($periodInner, $ohSpacerStyle);
        $ohCell = $ohWrap->addCell($docContentWidth, $ohSpacerStyle);
        $ohWrap->addCell($periodInner, $ohSpacerStyle);

        $ohTable = $ohCell->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 50,
        ]);
        $ohTable->addRow();
        $ohCellStyle = [
            'borderTopSize' => 0,
            'borderRightSize' => 0,
            'borderBottomSize' => 0,
            'borderLeftSize' => 0,
            'borderTopColor' => 'FFFFFF',
            'borderRightColor' => 'FFFFFF',
            'borderBottomColor' => 'FFFFFF',
            'borderLeftColor' => 'FFFFFF',
        ];
        $ohTable->addCell(2500, $ohCellStyle)->addText('Official hours for arrival and departure', ['italic' => true, 'size' => 9, 'name' => 'Arial']);
        $cRight = $ohTable->addCell(4500, $ohCellStyle);
        $cRight->addText('Regular days: ' . $regularHours, ['size' => 9, 'name' => 'Arial']);
        $cRight->addText('Saturdays: ' . $saturdayHours, ['size' => 9, 'name' => 'Arial']);

        $totalUndertimeDecimal = 0;
        foreach ($timeDataMap as $rec) {
            $appliedOffset = intval($recVal($rec, 'applied_offset', 0));
            $totalUndertimeDecimal += $appliedOffset === 1
                ? floatval($recVal($rec, 'undertime_offset', 0))
                : floatval($recVal($rec, 'undertime', 0));
        }
        $totalUndertimeConverted = \App\Helpers\Time_Calculation::dayFractionToHoursMinutes($totalUndertimeDecimal);

        // Wrapper table to emulate blade 1.5in left/right margin for DTR table block
        $tableWrap = $section->addTable(['borderSize' => 0, 'cellMargin' => 0, 'borderColor' => 'FFFFFF']);
        $tableWrap->addRow();
        $tableWrap->addCell($tableInner, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
        $tableCell = $tableWrap->addCell($docContentWidth, ['borderSize' => 0, 'borderBottomColor' => '000000','borderTopColor' => '000000', 'borderLeftColor' => '000000', 'borderRightColor' => 'FFFFFF']);
        $tableWrap->addCell($tableInner, ['borderSize' => 0, 'borderBottomColor' => 'FFFFFF','borderTopColor' => 'FFFFFF', 'borderLeftColor' => '000000', 'borderRightColor' => 'FFFFFF']);

        $table = $tableCell->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 10,
        ]);
        $hdrBg = ['bgColor' => 'F0F0F0'];
        $table->addRow($rowHeaderHeight, ['exactHeight' => true]);
        $table->addCell(500, array_merge($hdrBg, ['vMerge' => 'restart']))->addText('Day', ['bold' => true, 'size' => 7, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);
        $table->addCell(1800, array_merge($hdrBg, ['gridSpan' => 2]))->addText('A.M.', ['bold' => true, 'size' => 7, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);
        $table->addCell(1800, array_merge($hdrBg, ['gridSpan' => 2]))->addText('P.M.', ['bold' => true, 'size' => 7, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);
        $table->addCell(1200, array_merge($hdrBg, ['gridSpan' => 2]))->addText('Undertime', ['bold' => true, 'size' => 7, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);
        $table->addCell(1700, array_merge($hdrBg, ['vMerge' => 'restart']))->addText('Remarks', ['bold' => true, 'size' => 7, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);

        $table->addRow($rowHeaderHeight, ['exactHeight' => true]);
        $table->addCell(500, ['vMerge' => 'continue']);
        $table->addCell(900, $hdrBg)->addText('Arrival', ['bold' => true, 'size' => 7, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);
        $table->addCell(900, $hdrBg)->addText('Departure', ['bold' => true, 'size' => 7, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);
        $table->addCell(900, $hdrBg)->addText('Arrival', ['bold' => true, 'size' => 7, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);
        $table->addCell(900, $hdrBg)->addText('Departure', ['bold' => true, 'size' => 7, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);
        $table->addCell(600, $hdrBg)->addText('Hr', ['bold' => true, 'size' => 7, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);
        $table->addCell(600, $hdrBg)->addText('Min', ['bold' => true, 'size' => 7, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);
        $table->addCell(1700, ['vMerge' => 'continue']);

        $tableStart = $startDate->copy()->startOfMonth();
        $tableEnd = $endDate->copy()->endOfMonth();
        $datesInPeriod = [];
        $currentDate = $tableStart->copy();
        while ($currentDate->lte($tableEnd)) {
            $datesInPeriod[] = $currentDate->copy();
            $currentDate->addDay();
        }

        foreach ($datesInPeriod as $dateForRow) {
            $dateKey = $dateForRow->format('Y-m-d');
            $record = $timeDataMap[$dateKey] ?? null;
            $amIn = $record ? $formatTimeValue($recVal($record, 'am_in')) : '';
            $amOut = $record ? $formatTimeValue($recVal($record, 'am_out')) : '';
            $pmIn = $record ? $formatTimeValue($recVal($record, 'pm_in')) : '';
            $pmOut = $record ? $formatTimeValue($recVal($record, 'pm_out')) : '';

            $isAdjusted = $record && intval($recVal($record, 'is_adjusted', 0)) === 1;
            $isRestdayRecord = $record && intval($recVal($record, 'is_restday', 0)) === 1;
            $absentValue = $record ? floatval($recVal($record, 'absent', 0)) : 0;
            $leaveValue = $record ? floatval($recVal($record, 'leave', 0)) : 0;
            $isObRecord = $record && intval($recVal($record, 'is_ob', 0)) === 1;
            $isHolidayRecord = $record && intval($recVal($record, 'is_holiday', 0)) === 1;

            $eligibleForScheduleDefault = $isAdjusted && !$isRestdayRecord && $absentValue <= 0 && $leaveValue <= 0 && !$isObRecord && !$isHolidayRecord;
            if ($eligibleForScheduleDefault && isset($scheduleTimesMap[$dateKey])) {
                $sched = $scheduleTimesMap[$dateKey];
                if ($amIn === '' && ($sched['am_in'] ?? '') !== '') $amIn = $sched['am_in'];
                if ($pmOut === '' && ($sched['pm_out'] ?? '') !== '') $pmOut = $sched['pm_out'];
            }

            $undertimeHours = 0;
            $undertimeMinutes = 0;
            $remarks = '';
            if ($record) {
                $appliedOffset = intval($recVal($record, 'applied_offset', 0));
                $undertime = $appliedOffset === 1
                    ? floatval($recVal($record, 'undertime_offset', 0))
                    : floatval($recVal($record, 'undertime', 0));
                if ($undertime > 0) {
                    $ut = \App\Helpers\Time_Calculation::dayFractionToHoursMinutes($undertime);
                    $undertimeHours = $ut['hours'];
                    $undertimeMinutes = $ut['minutes'];
                }
                $remarks = trim((string) $recVal($record, 'remarks', ''));
            }

            $table->addRow($rowBodyHeight, ['exactHeight' => true]);
            $table->addCell(500)->addText($dateForRow->format('j'), ['size' => 8, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);
            foreach ([$amIn, $amOut, $pmIn, $pmOut] as $tv) {
                $table->addCell(900)->addText($tv, ['size' => 7, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);
            }
            $table->addCell(600)->addText($undertimeHours > 0 ? (string) $undertimeHours : '', ['size' => 8, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);
            $table->addCell(600)->addText($undertimeMinutes > 0 ? (string) $undertimeMinutes : '', ['size' => 8, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);
            $table->addCell(1700)->addText($remarks, ['size' => 7, 'name' => 'Arial'], ['alignment' => Jc::LEFT]);
        }

        $table->addRow($rowTotalHeight, ['exactHeight' => true]);
        $table->addCell(500)->addText('Total', ['bold' => true, 'size' => 8, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);
        for ($i = 0; $i < 4; $i++) {
            $table->addCell(900)->addText('', ['size' => 8, 'name' => 'Arial']);
        }
        $table->addCell(600)->addText($totalUndertimeConverted['hours'] > 0 ? (string) $totalUndertimeConverted['hours'] : '', ['bold' => true, 'size' => 8, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);
        $table->addCell(600)->addText($totalUndertimeConverted['minutes'] > 0 ? (string) $totalUndertimeConverted['minutes'] : '', ['bold' => true, 'size' => 8, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);
        $table->addCell(1700)->addText('', ['size' => 8, 'name' => 'Arial']);

        $section->addText('I certify on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.', ['size' => 9, 'name' => 'Arial'], [
            'alignment' => Jc::CENTER,
            'indentation' => ['left' => $certInner, 'right' => $certInner],
        ]);
        $section->addText('VERIFIED as to the prescribed office hours:', ['size' => 9, 'name' => 'Arial'], [
            'alignment' => Jc::CENTER,
            'indentation' => ['left' => $certInner, 'right' => $certInner],
        ]);

        $approver1 = data_get($approvers, 'approver_1');
        if ($approver1 && trim((string) data_get($approver1, 'name', '')) !== '') {
            $section->addText(strtoupper(trim(data_get($approver1, 'name'))), ['bold' => true, 'size' => 9, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);
        } else {
            $section->addText(' ', ['size' => 9, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);
        }
        $section->addText('_______________________________________________', ['size' => 8, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);
        $section->addText('In Charge', ['italic' => true, 'size' => 9, 'name' => 'Arial'], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(1);
    }

    private function buildDailyTimeRecordExcel($sheet, array $data): void
    {
        $currentRow = 1;

        $employee = $data['employee'] ?? [];
        $payrollPeriod = $data['payroll_period'] ?? [];
        $timeDataMap = $data['timeDataMap'] ?? [];
        $scheduleTimesMap = $data['scheduleTimesMap'] ?? [];
        $regularHours = $data['regular_hours'] ?? '8:00 AM - 5:00 PM';
        $saturdayHours = $data['saturday_hours'] ?? '';
        $approvers = $data['approvers'] ?? ['approver_1' => null];

        $startDate = !empty($payrollPeriod['attendance_start_date'])
            ? Carbon::parse($payrollPeriod['attendance_start_date'])->startOfDay()
            : Carbon::now()->startOfDay();
        $endDate = !empty($payrollPeriod['attendance_end_date'])
            ? Carbon::parse($payrollPeriod['attendance_end_date'])->startOfDay()
            : $startDate->copy()->endOfMonth();

        if ($startDate->format('Y-m') === $endDate->format('Y-m')) {
            $dtrPeriodLabel = 'For the month of';
            $dtrPeriodMonthYear = $startDate->format('F Y');
        } elseif ($startDate->format('Y') === $endDate->format('Y')) {
            $dtrPeriodLabel = 'For the months of';
            $dtrPeriodMonthYear = $startDate->format('F') . ' - ' . $endDate->format('F Y');
        } else {
            $dtrPeriodLabel = 'For the months of';
            $dtrPeriodMonthYear = $startDate->format('F Y') . ' - ' . $endDate->format('F Y');
        }

        $fn = trim((string) data_get($employee, 'first_name', ''));
        $mn = trim((string) data_get($employee, 'middle_name', ''));
        $ln = trim((string) data_get($employee, 'last_name', ''));
        if ($fn !== '' && $ln !== '') {
            $displayName = $mn !== '' ? $fn . ' ' . mb_substr($mn, 0, 1) . '. ' . $ln : $fn . ' ' . $ln;
        } else {
            $displayName = trim((string) data_get($employee, 'name', ''));
        }
        $employeeName = strtoupper($displayName !== '' ? $displayName : 'N/A');

        $formatTimeValue = static function ($value) {
            if (empty($value)) {
                return '';
            }
            try {
                return Carbon::parse($value)->format('h:i A');
            } catch (\Exception $e) {
                return '';
            }
        };
        $recVal = static fn ($rec, string $key, $default = null) => $rec === null ? $default : data_get($rec, $key, $default);

        $sheet->setCellValue('A' . $currentRow, 'CIVIL SERVICE FORM NO. 48');
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(false)->setSize(9);
        $currentRow += 2;

        $sheet->setCellValue('A' . $currentRow, 'DAILY TIME RECORD');
        $sheet->mergeCells('A' . $currentRow . ':H' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $currentRow++;

        $sheet->setCellValue('A' . $currentRow, '---o0o---');
        $sheet->mergeCells('A' . $currentRow . ':H' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $currentRow++;

        $sheet->setCellValue('A' . $currentRow, $employeeName);
        $sheet->mergeCells('A' . $currentRow . ':H' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(17);
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $currentRow++;

        $sheet->setCellValue('A' . $currentRow, '(Name)');
        $sheet->mergeCells('A' . $currentRow . ':H' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $currentRow += 2;

        $richPeriod = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
        $richPeriod->createTextRun($dtrPeriodLabel . ' ')->getFont()->setItalic(true)->setSize(14);
        $runPeriod = $richPeriod->createTextRun($dtrPeriodMonthYear);
        $runPeriod->getFont()->setItalic(true)->setSize(14)->setUnderline(Font::UNDERLINE_SINGLE);
        $sheet->setCellValue('A' . $currentRow, $richPeriod);
        $sheet->mergeCells('A' . $currentRow . ':H' . $currentRow);
        $currentRow += 2;

        $ohRow = $currentRow;
        $sheet->mergeCells('A' . $ohRow . ':C' . $ohRow);
        $sheet->setCellValue('A' . $ohRow, 'Official hours for arrival and departure');
        $sheet->getStyle('A' . $ohRow)->getFont()->setItalic(true)->setSize(9);
        $sheet->mergeCells('D' . $ohRow . ':H' . $ohRow);
        $sheet->setCellValue('D' . $ohRow, 'Regular days: ' . $regularHours);
        $sheet->getStyle('D' . $ohRow)->getFont()->setSize(9);
        $currentRow++;
        $sheet->mergeCells('A' . $currentRow . ':C' . $currentRow);
        $sheet->mergeCells('D' . $currentRow . ':H' . $currentRow);
        $sheet->setCellValue('D' . $currentRow, 'Saturdays: ' . $saturdayHours);
        $sheet->getStyle('D' . $currentRow)->getFont()->setSize(9);
        $currentRow += 2;

        $totalUndertimeDecimal = 0;
        foreach ($timeDataMap as $rec) {
            $appliedOffset = intval($recVal($rec, 'applied_offset', 0));
            $totalUndertimeDecimal += $appliedOffset === 1
                ? floatval($recVal($rec, 'undertime_offset', 0))
                : floatval($recVal($rec, 'undertime', 0));
        }
        $totalUndertime = \App\Helpers\Time_Calculation::dayFractionToHoursMinutes($totalUndertimeDecimal);

        $h1 = $currentRow;
        $h2 = $currentRow + 1;
        $sheet->setCellValue('A' . $h1, 'Day'); $sheet->mergeCells('A' . $h1 . ':A' . $h2);
        $sheet->setCellValue('B' . $h1, 'A.M.'); $sheet->mergeCells('B' . $h1 . ':C' . $h1);
        $sheet->setCellValue('D' . $h1, 'P.M.'); $sheet->mergeCells('D' . $h1 . ':E' . $h1);
        $sheet->setCellValue('F' . $h1, 'Undertime'); $sheet->mergeCells('F' . $h1 . ':G' . $h1);
        $sheet->setCellValue('H' . $h1, 'Remarks'); $sheet->mergeCells('H' . $h1 . ':H' . $h2);
        $sheet->setCellValue('B' . $h2, 'Arrival');
        $sheet->setCellValue('C' . $h2, 'Departure');
        $sheet->setCellValue('D' . $h2, 'Arrival');
        $sheet->setCellValue('E' . $h2, 'Departure');
        $sheet->setCellValue('F' . $h2, 'Hr');
        $sheet->setCellValue('G' . $h2, 'Min');

        foreach (range('A', 'H') as $col) {
            foreach ([$h1, $h2] as $hr) {
                $cell = $col . $hr;
                $sheet->getStyle($cell)->getFont()->setBold(true)->setSize(7);
                $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');
                $sheet->getStyle($cell)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        $currentRow = $h2 + 1;
        $tableStart = $startDate->copy()->startOfMonth();
        $tableEnd = $endDate->copy()->endOfMonth();
        $walk = $tableStart->copy();
        $dates = [];
        while ($walk->lte($tableEnd)) {
            $dates[] = $walk->copy();
            $walk->addDay();
        }

        foreach ($dates as $dateForRow) {
            $dateKey = $dateForRow->format('Y-m-d');
            $record = $timeDataMap[$dateKey] ?? null;
            $amIn = $record ? $formatTimeValue($recVal($record, 'am_in')) : '';
            $amOut = $record ? $formatTimeValue($recVal($record, 'am_out')) : '';
            $pmIn = $record ? $formatTimeValue($recVal($record, 'pm_in')) : '';
            $pmOut = $record ? $formatTimeValue($recVal($record, 'pm_out')) : '';

            $isAdjusted = $record && intval($recVal($record, 'is_adjusted', 0)) === 1;
            $isRestdayRecord = $record && intval($recVal($record, 'is_restday', 0)) === 1;
            $absentValue = $record ? floatval($recVal($record, 'absent', 0)) : 0;
            $leaveValue = $record ? floatval($recVal($record, 'leave', 0)) : 0;
            $isObRecord = $record && intval($recVal($record, 'is_ob', 0)) === 1;
            $isHolidayRecord = $record && intval($recVal($record, 'is_holiday', 0)) === 1;
            $eligibleForScheduleDefault = $isAdjusted && !$isRestdayRecord && $absentValue <= 0 && $leaveValue <= 0 && !$isObRecord && !$isHolidayRecord;
            if ($eligibleForScheduleDefault && isset($scheduleTimesMap[$dateKey])) {
                $sched = $scheduleTimesMap[$dateKey];
                if ($amIn === '' && ($sched['am_in'] ?? '') !== '') $amIn = $sched['am_in'];
                if ($pmOut === '' && ($sched['pm_out'] ?? '') !== '') $pmOut = $sched['pm_out'];
            }

            $utHours = 0; $utMinutes = 0; $remarks = '';
            if ($record) {
                $appliedOffset = intval($recVal($record, 'applied_offset', 0));
                $undertime = $appliedOffset === 1
                    ? floatval($recVal($record, 'undertime_offset', 0))
                    : floatval($recVal($record, 'undertime', 0));
                if ($undertime > 0) {
                    $ut = \App\Helpers\Time_Calculation::dayFractionToHoursMinutes($undertime);
                    $utHours = $ut['hours']; $utMinutes = $ut['minutes'];
                }
                $remarks = trim((string) $recVal($record, 'remarks', ''));
            }

            $sheet->setCellValue('A' . $currentRow, $dateForRow->format('j'));
            $sheet->setCellValue('B' . $currentRow, $amIn);
            $sheet->setCellValue('C' . $currentRow, $amOut);
            $sheet->setCellValue('D' . $currentRow, $pmIn);
            $sheet->setCellValue('E' . $currentRow, $pmOut);
            $sheet->setCellValue('F' . $currentRow, $utHours > 0 ? $utHours : '');
            $sheet->setCellValue('G' . $currentRow, $utMinutes > 0 ? $utMinutes : '');
            $sheet->setCellValue('H' . $currentRow, $remarks);

            foreach (range('A', 'H') as $col) {
                $cell = $col . $currentRow;
                $fontSize = in_array($col, ['B', 'C', 'D', 'E', 'H'], true) ? 7 : 8;
                $sheet->getStyle($cell)->getFont()->setSize($fontSize);
                $sheet->getStyle($cell)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle($cell)->getAlignment()->setHorizontal($col === 'H' ? Alignment::HORIZONTAL_LEFT : Alignment::HORIZONTAL_CENTER);
            }
            $currentRow++;
        }

        $sheet->setCellValue('A' . $currentRow, 'Total');
        $sheet->setCellValue('F' . $currentRow, $totalUndertime['hours'] > 0 ? $totalUndertime['hours'] : '');
        $sheet->setCellValue('G' . $currentRow, $totalUndertime['minutes'] > 0 ? $totalUndertime['minutes'] : '');
        foreach (range('A', 'H') as $col) {
            $cell = $col . $currentRow;
            $sheet->getStyle($cell)->getFont()->setBold(true)->setSize(8);
            $sheet->getStyle($cell)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $currentRow += 2;

        $sheet->setCellValue('A' . $currentRow, 'I certify on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.');
        $sheet->mergeCells('A' . $currentRow . ':H' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A' . $currentRow)->getFont()->setSize(9);
        $currentRow += 3;
        $sheet->setCellValue('A' . $currentRow, 'VERIFIED as to the prescribed office hours:');
        $sheet->mergeCells('A' . $currentRow . ':H' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A' . $currentRow)->getFont()->setSize(9);
        $currentRow += 3;

        $approver1 = data_get($approvers, 'approver_1');
        $sheet->setCellValue('A' . $currentRow, $approver1 && trim((string) data_get($approver1, 'name', '')) !== '' ? strtoupper(trim((string) data_get($approver1, 'name'))) : '');
        $sheet->mergeCells('A' . $currentRow . ':H' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $currentRow++;
        $sheet->mergeCells('A' . $currentRow . ':H' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $currentRow++;
        $sheet->setCellValue('A' . $currentRow, 'In Charge');
        $sheet->mergeCells('A' . $currentRow . ':H' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getFont()->setItalic(true)->setSize(9);
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(40);
    }

    public function download($id)
    {
        try {
            $app_key = env("APP_KEY", "");

        $documents = DB::table('time_data as a')
            ->select(
                'a.id',
                'a.employee_id',
                'a.attachment_name'
            )
            ->where('a.id', $id)
            ->get();

        $pathToFile = storage_path('app/employee_DTR_documents/' . 'DOCS' . $documents[0]->employee_id . $documents[0]->id . '_' . $documents[0]->attachment_name);

        if (!file_exists($pathToFile)) {
            return $this->notFoundResponse('File not found');
        }

        $fileContent = file_get_contents($pathToFile);
        $base64Content = base64_encode($fileContent);

        return $this->successResponse([
            'file_content' => $base64Content,
            'filename' => $documents[0]->attachment_name,
            'content_type' => mime_content_type($pathToFile)
        ], 'Document downloaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to download document: ' . $e->getMessage());
        }
    }
}
