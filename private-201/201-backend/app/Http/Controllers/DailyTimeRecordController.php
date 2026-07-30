<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

                        if not exists(select * from sys.servers where name = N'SRV_NAME')
                            begin
                                EXEC sp_addlinkedserver @server='SRV_NAME',
                                                @srvproduct=N'',
                                                @provider=N'MSOLEDBSQL',   
                                                @datasrc=@Server_Name;

                                EXEC sp_addlinkedsrvlogin 'SRV_NAME', 'false', NULL,  @Username, @Password;
                            end
                        else
                            begin
                            
                                EXEC sp_dropserver
                                @server = N'SRV_NAME',
                                @droplogins = 'droplogins'
                                
                                EXEC sp_addlinkedserver @server='SRV_NAME',
                                                @srvproduct=N'',
                                                @provider=N'MSOLEDBSQL',   
                                                @datasrc =@Server_Name; 

                                EXEC sp_addlinkedsrvlogin 'SRV_NAME', 'false', NULL, @Username, @Password;
                            
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
                                 $database1.dbo.employees as c on a.badgenumber = c.access_no
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
