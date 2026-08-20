<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PDF;

class DailyTimeRecordController extends Controller
{
    use ApiResponse;

    /** Daily Time Record — matches approver_type.id (Daily Time Record) */
    const DTR_TYPE_ID = 8;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->except(['getTodayStatus']);
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

            $payroll_period = DB::table('payroll_periods')
                ->where('id', $payroll_period_id)
                ->first();

            if (!$payroll_period) {
                return $this->errorResponse('Payroll period not found', 404);
            }

            $dtrPayrollPeriodIds = $this->getDtrIncludedPayrollPeriodIds($payroll_period);
            $displayPeriod = $this->normalizeDtrPayrollPeriodForDisplay($payroll_period);

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
            ->whereIn('a.payroll_period_id', $dtrPayrollPeriodIds)
            ->where([
                'b.id' => $id,
                'b.active' => true,
                'b.is_employee' => true
            ])
            ->orderBy('date', 'asc')
            ->get();

        $daily_time_records = $this->applyDtrSecondHalfSnapshotOverrides(
            $daily_time_records,
            (int) $id,
            $payroll_period
        );

        foreach ($daily_time_records as $record) {
            $record->attendance_start_date = $displayPeriod['attendance_start_date'] ?? $record->attendance_start_date;
            $record->attendance_end_date = $displayPeriod['attendance_end_date'] ?? $record->attendance_end_date;
        }

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
            ->whereIn('a.payroll_period_id', $dtrPayrollPeriodIds)
            ->where([
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

    public function getLogs(Request $request, $id, $from, $to)
    {
        try {
            $app_key = env("APP_KEY", "");
            $skipBioSync = $request->boolean('skip_bio_sync', false);

            $employee_data = DB::table('employees')->where('id', $id)->first();
            if (!$employee_data) {
                return $this->notFoundResponse('Employee not found');
            }

            if ($skipBioSync) {
                $this->ensureTimeDataPlaceholderRows((int) $id, $from, $to, $employee_data);
            } else {
                $this->syncTimeDataFromBiometrics((int) $id, $from, $to, $employee_data, $app_key);
            }

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

    private function ensureTimeDataPlaceholderRows($employeeId, $from, $to, $employee)
    {
        $existingDates = DB::table('time_data')
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$from, $to])
            ->pluck('date')
            ->map(fn ($date) => date('Y-m-d', strtotime($date)))
            ->flip();

        $rowsToInsert = [];

        for ($date = $from; $date <= $to; $date = date('Y-m-d', strtotime("$date +1 day"))) {
            if (isset($existingDates[$date])) {
                continue;
            }

            $rowsToInsert[] = $this->buildEmptyTimeDataRow($employeeId, $date, $employee);
        }

        foreach (array_chunk($rowsToInsert, 100) as $chunk) {
            if (!empty($chunk)) {
                DB::table('time_data')->insert($chunk);
            }
        }
    }

    private function buildEmptyTimeDataRow($employeeId, $date, $employee)
    {
        return [
            'employee_id' => $employeeId,
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
            'work_schedule_id' => $employee->work_schedule_id,
            'ob_hours' => 0,
            'ot_hours' => 0,
            'for_approval' => 0,
            'is_edited' => 0,
        ];
    }

    private function syncTimeDataFromBiometrics($id, $from, $to, $employee_data, $app_key)
    {
        $is_bio_connected = false;
        $database1 = null;
        $database2 = null;

        // Check if Biometrics Server is Connected
        try {
            $pdo = DB::connection('sqlsrv_bio')->getPdo();

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
                $database1 = DB::connection('sqlsrv')->getDatabaseName();
                $database2 = 'SRV_NAME.' . DB::connection('sqlsrv_bio')->getDatabaseName();
            }
        } catch (\Throwable $th) {
            $is_bio_connected = false;
        }

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
                        'work_schedule_id' => $employee_data->work_schedule_id,
                        'ob_hours' => 0,
                        'ot_hours' => 0,
                        'for_approval' => 0,
                        'is_edited' => 0
                    );
                } else {
                    $time_data = $this->buildEmptyTimeDataRow($id, $date, $employee_data);
                }

                DB::table('time_data')->Insert($time_data);
            }
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $app_key = env("APP_KEY", "");

        if ($request->hasFile('attachment') == false) {
            return $this->errorResponse('Please attach file on date need to be reviewed to save request.', 400);
        }

        if (!$this->employeeHasDtrApprover($id)) {
            return $this->errorResponse('You cannot apply for DTR correction. No approver has been configured for DTR applications.', 400);
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

        return $this->successResponse(null, 'DTR application submitted for approval.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to store time data: ' . $e->getMessage());
        }
    }

    /**
     * List DTR correction applications for the logged-in employee.
     */
    public function employeeApplications($userId)
    {
        try {
            $employee = $this->resolveEmployeeFromUserId($userId);
            if (!$employee) {
                return $this->errorResponse('Employee record not found', 404);
            }

            $hasApprover = $this->employeeHasDtrApprover($employee->id);

            $applications = DB::table('time_data_request as tdr')
                ->leftJoin('payroll_periods as pp', 'pp.id', '=', 'tdr.payroll_period_id')
                ->leftJoin('payroll_intervals as pi', 'pi.id', '=', 'pp.payroll_interval_id')
                ->where('tdr.employee_id', $employee->id)
                ->orderBy('tdr.request_date', 'desc')
                ->select(
                    'tdr.*',
                    DB::raw("CASE WHEN tdr.payroll_period_id > 0 THEN
                        CONCAT(pi.name,' (', DATENAME(MONTH, pp.release_date),' ', DATEPART(YEAR, pp.release_date),')')
                        ELSE NULL END as payroll_period")
                )
                ->get()
                ->map(function ($request) {
                    $entriesCount = DB::table('time_data')
                        ->where('dtr_request_id', $request->id)
                        ->count();

                    if ($entriesCount === 0) {
                        $entriesCount = DB::table('time_data')
                            ->where('employee_id', $request->employee_id)
                            ->where('is_edited', 1)
                            ->where('payroll_period_id', 0)
                            ->count();
                    }

                    return [
                        'id' => $request->id,
                        'employee_id' => $request->employee_id,
                        'request_date' => $request->request_date,
                        'payroll_period_id' => $request->payroll_period_id ?? 0,
                        'payroll_period' => $request->payroll_period,
                        'attachment_name' => $request->attachment_name ?? null,
                        'status' => $this->toDtrBool($request->status),
                        'approved_1' => $this->toDtrBool($request->approved_1),
                        'approved_2' => $this->toDtrBool($request->approved_2),
                        'disapproved_1' => $this->toDtrBool($request->disapproved_1),
                        'disapproved_2' => $this->toDtrBool($request->disapproved_2),
                        'status_label' => $this->formatDtrRequestStatus($request),
                        'entries_count' => $entriesCount,
                        'has_approved_dtr' => $this->isDtrRequestFullyApproved($request),
                        'can_edit' => $this->canEmployeeEditDtrApplication($request),
                    ];
                });

            return $this->successResponse([
                'allowed' => $hasApprover ? 1 : 0,
                'employee_id' => $employee->id,
                'applications' => $applications,
                'permissions' => [
                    'can_approve' => $hasApprover ? 1 : 0,
                ],
            ], 'DTR applications retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve DTR applications: ' . $e->getMessage());
        }
    }

    /**
     * Payroll periods available for a new DTR application.
     */
    public function payrollPeriodsForApplication($employeeId)
    {
        try {
            $employee = DB::table('employees')->where('id', $employeeId)->first();
            if (!$employee) {
                return $this->errorResponse('Employee record not found', 404);
            }

            $periods = $this->getPayrollPeriodsForEmployee($employee);

            return $this->successResponse([
                'payroll_periods' => $periods,
            ], 'Payroll periods retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve payroll periods: ' . $e->getMessage());
        }
    }

    /**
     * Submit a DTR application with payroll period and supporting attachment.
     */
    public function storeApplication(Request $request, $employeeId)
    {
        try {
            $request->validate([
                'payroll_period_id' => 'required|integer|min:1',
                'dtr_attachment' => 'required|file|max:10240',
            ]);

            if (!$this->employeeHasDtrApprover($employeeId)) {
                return $this->errorResponse('You cannot apply for DTR correction. No approver has been configured for DTR applications.', 400);
            }

            $pending = DB::table('time_data_request')
                ->where(['employee_id' => $employeeId, 'status' => 0])
                ->where('disapproved_1', 0)
                ->where('disapproved_2', 0)
                ->exists();

            if ($pending) {
                return $this->errorResponse('You already have a pending DTR application.', 400);
            }

            $employee = DB::table('employees')->where('id', $employeeId)->first();
            if (!$employee) {
                return $this->errorResponse('Employee record not found', 404);
            }

            $period = $this->resolvePayrollPeriodForEmployee($employee, $request->payroll_period_id);
            if (!$period) {
                return $this->errorResponse('Invalid payroll period selected for your employment type.', 400);
            }

            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xlsx', 'xls'];
            $file = $request->file('dtr_attachment');
            $extension = strtolower($file->getClientOriginalExtension());

            if (!in_array($extension, $allowedExtensions)) {
                return $this->errorResponse('Invalid file type. Allowed: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX.', 400);
            }

            $fileName = $file->getClientOriginalName();

            $requestId = DB::table('time_data_request')->insertGetId([
                'employee_id' => $employeeId,
                'request_date' => now(),
                'status' => false,
                'payroll_period_id' => $request->payroll_period_id,
                'approved_1' => 0,
                'approved_by_1_id' => 0,
                'approved_date_1' => null,
                'disapproved_1' => 0,
                'disapproved_by_1_id' => 0,
                'disapproved_date_1' => null,
                'approved_2' => 0,
                'approved_by_2_id' => 0,
                'approved_date_2' => null,
                'disapproved_2' => 0,
                'disapproved_by_2_id' => 0,
                'disapproved_date_2' => null,
                'attachment_name' => $fileName,
                'extension' => $extension,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $storageName = 'DOCS_REQ' . $employeeId . $requestId . '_' . $fileName;
            $filePath = Storage::disk('local')->getAdapter()->getPathPrefix() . 'employee_DTR_documents\\' . $storageName;

            DB::table('time_data_request')->where('id', $requestId)->update(['path' => $filePath]);

            $file->storeAs('employee_DTR_documents', $storageName);

            return $this->successResponse(['id' => $requestId], 'DTR application submitted for approval.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->validator->errors()->first(), 422);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to submit DTR application: ' . $e->getMessage());
        }
    }

    /**
     * Update a pending or returned DTR application (payroll period and/or attachment).
     */
    public function updateApplication(Request $request, $requestId)
    {
        try {
            $request->validate([
                'payroll_period_id' => 'required|integer|min:1',
                'dtr_attachment' => 'nullable|file|max:10240',
            ]);

            $dtrRequest = DB::table('time_data_request')->where('id', $requestId)->first();
            if (!$dtrRequest) {
                return $this->errorResponse('DTR application not found', 404);
            }

            $employeeId = (int) ($dtrRequest->employee_id ?? 0);
            $currentEmployee = Auth::check()
                ? $this->resolveEmployeeFromUserId(Auth::user()->id)
                : null;
            if (!$currentEmployee || (int) $currentEmployee->id !== $employeeId) {
                return $this->errorResponse('You are not authorized to update this DTR application.', 403);
            }

            if (!$this->canEmployeeEditDtrApplication($dtrRequest)) {
                return $this->errorResponse('Only pending or returned DTR applications can be edited.', 400);
            }

            if (!$this->employeeHasDtrApprover($employeeId)) {
                return $this->errorResponse('You cannot update a DTR application. No approver has been configured.', 400);
            }

            $employee = DB::table('employees')->where('id', $employeeId)->first();
            if (!$employee) {
                return $this->errorResponse('Employee record not found', 404);
            }

            $period = $this->resolvePayrollPeriodForEmployee($employee, $request->payroll_period_id);
            if (!$period) {
                return $this->errorResponse('Invalid payroll period selected for your employment type.', 400);
            }

            if (!$request->hasFile('dtr_attachment') && empty($dtrRequest->attachment_name)) {
                return $this->errorResponse('Please upload a DTR attachment.', 400);
            }

            $updateData = [
                'payroll_period_id' => (int) $request->payroll_period_id,
                'updated_at' => now(),
                'approved_1' => 0,
                'approved_by_1_id' => 0,
                'approved_date_1' => null,
                'disapproved_1' => 0,
                'disapproved_by_1_id' => 0,
                'disapproved_date_1' => null,
                'approved_2' => 0,
                'approved_by_2_id' => 0,
                'approved_date_2' => null,
                'disapproved_2' => 0,
                'disapproved_by_2_id' => 0,
                'disapproved_date_2' => null,
                'status' => 0,
            ];

            if (Schema::hasColumn('time_data_request', 'approved_dtr_path')) {
                $updateData['approved_dtr_path'] = null;
            }

            if ($request->hasFile('dtr_attachment')) {
                $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xlsx', 'xls'];
                $file = $request->file('dtr_attachment');
                $extension = strtolower($file->getClientOriginalExtension());

                if (!in_array($extension, $allowedExtensions)) {
                    return $this->errorResponse('Invalid file type. Allowed: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX.', 400);
                }

                $fileName = $file->getClientOriginalName();
                $storageName = 'DOCS_REQ' . $employeeId . $requestId . '_' . $fileName;
                $filePath = Storage::disk('local')->getAdapter()->getPathPrefix() . 'employee_DTR_documents\\' . $storageName;

                if (!empty($dtrRequest->path) && file_exists($dtrRequest->path)) {
                    @unlink($dtrRequest->path);
                }
                $approvedPath = Schema::hasColumn('time_data_request', 'approved_dtr_path')
                    ? ($dtrRequest->approved_dtr_path ?? null)
                    : null;
                if (!empty($approvedPath) && file_exists($approvedPath)) {
                    @unlink($approvedPath);
                }

                $file->storeAs('employee_DTR_documents', $storageName);

                $updateData['attachment_name'] = $fileName;
                $updateData['extension'] = $extension;
                $updateData['path'] = $filePath;
            }

            DB::table('time_data_request')->where('id', $requestId)->update($updateData);

            return $this->successResponse(['id' => (int) $requestId], 'DTR application updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->validator->errors()->first(), 422);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update DTR application: ' . $e->getMessage());
        }
    }

    /**
     * Download the DTR attachment stored on a time_data_request record.
     */
    public function downloadApplicationAttachment($requestId)
    {
        try {
            $record = DB::table('time_data_request')
                ->where('id', $requestId)
                ->first();

            if (!$record || empty($record->attachment_name)) {
                return $this->notFoundResponse('Attachment not found');
            }

            $pathToFile = $record->path;
            if (empty($pathToFile) || !file_exists($pathToFile)) {
                $storageName = 'DOCS_REQ' . $record->employee_id . $requestId . '_' . $record->attachment_name;
                $pathToFile = storage_path('app/employee_DTR_documents/' . $storageName);
            }

            if (!file_exists($pathToFile)) {
                return $this->notFoundResponse('File not found');
            }

            $fileContent = file_get_contents($pathToFile);

            return $this->successResponse([
                'file_content' => base64_encode($fileContent),
                'filename' => $record->attachment_name,
                'content_type' => mime_content_type($pathToFile) ?: 'application/octet-stream',
            ], 'Document downloaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to download attachment: ' . $e->getMessage());
        }
    }

    public function checkDtrApproverAccess($id)
    {
        try {
            $employee = $this->resolveEmployeeFromUserId($id);
            $empId = $employee ? (int) $employee->id : 0;
            $roles = $this->getDtrApproverRoles($empId);
            $supervisorId = $this->hasDtrApproverRole($roles);

            return $this->successResponse([
                'employee_id' => $empId,
                'supervisor_id' => $supervisorId,
                'is_approver' => $supervisorId,
            ], 'DTR approver access retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to check DTR approver access: ' . $e->getMessage());
        }
    }

    public function loadDTRRequest($id)
    {
        try {
            $app_key = env("APP_KEY", "");
            $employee = $this->resolveEmployeeFromUserId($id);
            $emp_id = $employee ? (int) $employee->id : 0;
            $dtr_for_approvals = collect();
            $supervisor_id = false;

            if ($emp_id > 0) {
                $roles = $this->getDtrApproverRoles($emp_id);
                $approver_1 = $roles['approver_1'];
                $approver_2 = $roles['approver_2'];
                $approver_3 = $roles['approver_3'];

                if ($approver_1->isNotEmpty() || $approver_2->isNotEmpty() || $approver_3->isNotEmpty()) {
                    $supervisor_id = true;
                }

                $dtr_for_approvals = $this->collectDtrPendingForApprover($emp_id, $app_key, $roles);
                $dtr_approved = $supervisor_id
                    ? $this->collectDtrApprovedForApprover($emp_id, $app_key, $roles)
                    : collect();
                $dtr_returned = $supervisor_id
                    ? $this->queryDtrReturnedForApprover($emp_id, $app_key)
                    : collect();
            } else {
                $dtr_approved = collect();
                $dtr_returned = collect();
            }

            $mapRows = fn ($rows) => $rows->map(fn ($row) => $this->mapDtrApprovalRow($row))->values();

            $dtr_for_approvals = $mapRows($dtr_for_approvals);
            $dtr_approved = $mapRows($dtr_approved);
            $dtr_returned = $mapRows($dtr_returned);

        return $this->successResponse([
            'employee_id' => $emp_id,
                'supervisor_id' => $supervisor_id ? 1 : 0,
                'is_approver' => $supervisor_id ? 1 : 0,
                'records' => $dtr_for_approvals,
                'dtr_for_approvals' => $dtr_for_approvals,
                'dtr_pending' => $dtr_for_approvals,
                'dtr_approved' => $dtr_approved,
                'dtr_returned' => $dtr_returned,
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
                'g.name as position',
                'b.payroll_interval_id',
                'a.payroll_period_id',
                'a.approved_1',
                'a.approved_2',
                'a.disapproved_1',
                'a.disapproved_2'
            )
            ->where('a.id', $id)
            ->get();

        if ($daily_time_records->isEmpty()) {
            return $this->errorResponse('DTR request not found', 404);
        }

        $employeeId = $daily_time_records[0]->employee_id;
        $time_data = $this->getPendingTimeLogCorrectionsForDtrRequest($id, $employeeId);
        $time_data_request = $this->getApprovedTimeLogCorrectionsForDtrRequest($id, $employeeId);

        $statusSource = (object) [
            'employee_id' => $employeeId,
            'status' => $daily_time_records[0]->status ?? 0,
            'approved_1' => $daily_time_records[0]->approved_1 ?? 0,
            'approved_2' => $daily_time_records[0]->approved_2 ?? 0,
            'disapproved_1' => $daily_time_records[0]->disapproved_1 ?? 0,
            'disapproved_2' => $daily_time_records[0]->disapproved_2 ?? 0,
        ];

        $requestEmployee = DB::table('employees')
            ->where('id', $daily_time_records[0]->employee_id)
            ->first();

        $payroll_periods = $requestEmployee
            ? $this->getPayrollPeriodsForEmployee($requestEmployee)
            : collect();

        $payrollPeriodList = $payroll_periods instanceof \Illuminate\Support\Collection
            ? $payroll_periods->values()
            : collect($payroll_periods ?? [])->values();

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

        $requestEmployeeId = $daily_time_records[0]->employee_id;
        $approvers = $this->getDtrApproversForRequestEmployee($emp_id, $requestEmployeeId);
        $is_second_approver = $approvers['approver_2']->isNotEmpty();

        return $this->successResponse([
            'daily_time_records' => $daily_time_records,
            'time_data' => $time_data,
            'payroll_periods' => $payrollPeriodList,
            'time_data_request' => $time_data_request,
            'request_status_label' => $this->formatDtrRequestStatus($statusSource),
            'has_pending_corrections' => $time_data->isNotEmpty(),
            'has_approved_corrections' => $time_data_request->isNotEmpty(),
            'is_second_approver' => $is_second_approver,
            'application_attachment' => $this->getApplicationAttachmentMeta($id),
            'has_approved_dtr' => $this->isDtrRequestFullyApproved($statusSource),
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

        $approvers = $this->getDtrApproversForRequestEmployee($emp_id, $employee_id);
        $approver_1 = $approvers['approver_1'];
        $approver_2 = $approvers['approver_2'];
        $approver_3 = $approvers['approver_3'];

        if ($type_id == 2 || $type_id == 3) {
            $dtrRequest = DB::table('time_data_request')->where('id', $id)->first();
            $level_1_responded = $dtrRequest && (
                (int) ($dtrRequest->approved_1 ?? 0) === 1 || (int) ($dtrRequest->disapproved_1 ?? 0) === 1
            );
            $level_2_responded = $dtrRequest && (
                (int) ($dtrRequest->approved_2 ?? 0) === 1 || (int) ($dtrRequest->disapproved_2 ?? 0) === 1
            );

            if ($approver_2->isNotEmpty() && !$level_1_responded) {
                return $this->errorResponse('Level 2 approver cannot act until Level 1 has approved or disapproved.', 400);
            }
            if ($approver_3->isNotEmpty() && !$level_2_responded) {
                return $this->errorResponse('Level 3 approver cannot act until Level 2 has approved or disapproved.', 400);
            }
        }

        if ($type_id == 1) {
            if ($approver_1->isEmpty() && $approver_2->isEmpty() && $approver_3->isEmpty()) {
                return $this->errorResponse('You are not authorized to correct time logs for this DTR request.', 403);
            }

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
                $maxLevel = $this->getDtrMaxApproverLevelForEmployee($employee_id);
                $data = [
                    'approved_1' => 1,
                    'approved_by_1_id' => $approver_1[0]->id,
                    'approved_date_1' => now(),
                    'status' => $maxLevel === 1 ? 1 : 0
                ];
            } elseif ($approver_2->isNotEmpty()) {
                $hasLevel3 = !empty($approver_2[0]->approver_id_3) && (int) $approver_2[0]->approver_id_3 > 0;
                $data = [
                    'approved_2' => 1,
                    'approved_by_2_id' => $approver_2[0]->id,
                    'approved_date_2' => now(),
                    'status' => $hasLevel3 ? 0 : 1
                ];
            } elseif ($approver_3->isNotEmpty()) {
                $data = [
                    'approved_2' => 1,
                    'approved_by_2_id' => $approver_3[0]->id,
                    'approved_date_2' => now(),
                    'status' => 1
                ];
            } else {
                return $this->errorResponse('You are not authorized to approve this DTR request.', 403);
            }

            DB::table('time_data_request')->where('id', $id)->update($data);

            $updatedRequest = DB::table('time_data_request')->where('id', $id)->first();
            if ($updatedRequest && $this->isDtrRequestFullyApproved($updatedRequest)) {
                try {
                    $this->generateAndStoreApprovedDtrPdf((int) $id);
                } catch (\Exception $pdfError) {
                    Log::error('Failed to generate approved DTR PDF after approval', [
                        'request_id' => $id,
                        'error' => $pdfError->getMessage(),
                    ]);
                }
            }

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
            } elseif ($approver_3->isNotEmpty()) {
                $data = [
                    'disapproved_2' => 1,
                    'disapproved_by_2_id' => $approver_3[0]->id,
                    'disapproved_date_2' => '',
                    'status' => 1
                ];

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
            } else {
                return $this->errorResponse('You are not authorized to disapprove this DTR request.', 403);
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

    public function downloadApprovedDtr($requestId)
    {
        try {
            $record = DB::table('time_data_request')->where('id', $requestId)->first();
            if (!$record || !$this->isDtrRequestFullyApproved($record)) {
                return $this->notFoundResponse('Approved DTR not found');
            }

            $employeeId = (int) ($record->employee_id ?? 0);
            $path = $this->resolveApprovedDtrStoragePath((int) $requestId, $employeeId, $record->approved_dtr_path ?? null);

            try {
                $path = $this->generateAndStoreApprovedDtrPdf((int) $requestId) ?: $path;
            } catch (\Exception $pdfError) {
                Log::error('Failed to generate approved DTR PDF on download', [
                    'request_id' => $requestId,
                    'employee_id' => $employeeId,
                    'error' => $pdfError->getMessage(),
                ]);
            }

            if (empty($path) || !is_file($path)) {
                $path = $this->resolveApprovedDtrStoragePath((int) $requestId, $employeeId, $record->approved_dtr_path ?? null);
            }

            if (empty($path) || !is_file($path)) {
                $payrollPeriodId = $this->resolvePayrollPeriodIdForApprovedDtr($record);
                $message = $payrollPeriodId <= 0
                    ? 'Approved DTR cannot be generated because this request has no payroll period.'
                    : 'Approved DTR file is not available for this request';

                return $this->notFoundResponse($message);
            }

            $fileContent = file_get_contents($path);

            return $this->successResponse([
                'file_content' => base64_encode($fileContent),
                'filename' => 'approved_dtr_' . $requestId . '.pdf',
                'content_type' => 'application/pdf',
            ], 'Approved DTR downloaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to download approved DTR: ' . $e->getMessage());
        }
    }

    public function print($id, $payroll_period_id)
    {
        try {
            $viewData = $this->prepareDtrPrintViewData((int) $id, (int) $payroll_period_id);

            Log::info('DTR Print - Preparing to generate PDF', [
                'employee_id' => $id,
                'payroll_period_id' => $payroll_period_id,
                'employee_name' => $viewData['employee']['name'] ?? 'N/A',
                'timeDataMap_count' => count($viewData['timeDataMap'] ?? []),
                'has_payroll_period' => !empty($viewData['payroll_period']['attendance_start_date'] ?? null)
            ]);

            if (!view()->exists('DTR.DTR')) {
                Log::error('DTR Print - View not found: DTR.DTR');
                return response()->json([
                    'success' => false,
                    'message' => 'DTR print template not found'
                ], 500);
            }

            try {
                $pdf = PDF::loadView('DTR.DTR', $viewData)->setOptions(['defaultFont' => 'sans-serif']);
                $pdf->setPaper('A4', 'portrait');
                $pdfContent = $pdf->output();
            } catch (\Exception $viewError) {
                Log::error('DTR Print - PDF generation error', [
                    'error' => $viewError->getMessage(),
                    'file' => $viewError->getFile(),
                    'line' => $viewError->getLine(),
                    'trace' => $viewError->getTraceAsString()
                ]);
                throw $viewError;
            }

            $filename = "dtr_{$id}_{$payroll_period_id}_" . date('Y-m-d') . ".pdf";

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));

        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (\Exception $e) {
            Log::error('DTR Print Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'employee_id' => $id ?? null,
                'payroll_period_id' => $payroll_period_id ?? null
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate DTR PDF: ' . $e->getMessage(),
                'error' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : 'An error occurred while generating the PDF'
            ], 500);
        }
    }

    private function prepareDtrPrintViewData(int $employeeId, int $payrollPeriodId): array
    {
        $app_key = env("APP_KEY", "");

        $employee_record = DB::table('employees as a')
            ->leftJoin('departments as c', 'c.id', '=', 'a.department_id')
            ->select(
                'a.id as employee_id',
                'a.department_id',
                'a.employment_type_id',
                'c.name as department',
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                               CONCAT(a.first_name,' ',substring(a.middle_name,1,1),'. ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name")
            )
            ->where([
                'a.id' => $employeeId,
                'a.active' => true,
                'a.is_employee' => true
            ])
            ->first();

        if (!$employee_record) {
            throw new \InvalidArgumentException('Employee not found');
        }

        $payroll_period = DB::table('payroll_periods')
            ->where('id', $payrollPeriodId)
            ->first();

        if (!$payroll_period) {
            throw new \InvalidArgumentException('Payroll period not found');
        }

        $dtrPayrollPeriodIds = $this->getDtrIncludedPayrollPeriodIds($payroll_period);
        $displayPeriod = $this->normalizeDtrPayrollPeriodForDisplay($payroll_period);

        $time_records = DB::table('time_data as a')
            ->select(
                'a.date',
                'a.am_in',
                'a.am_out',
                'a.pm_in',
                'a.pm_out',
                'a.late',
                'a.undertime',
                'a.remarks',
                'a.applied_offset',
                'a.late_offset',
                'a.undertime_offset',
                'a.is_adjusted',
                'a.is_restday',
                'a.absent',
                'a.leave',
                'a.is_ob',
                'a.is_holiday'
            )
            ->where('a.employee_id', $employeeId)
            ->whereIn('a.payroll_period_id', $dtrPayrollPeriodIds)
            ->orderBy('date', 'asc')
            ->get();

        try {
            $time_records = $this->applyDtrSecondHalfSnapshotOverrides($time_records, $employeeId, $payroll_period);
        } catch (\Exception $snapshotError) {
            Log::warning('DTR Print - Snapshot override skipped', [
                'employee_id' => $employeeId,
                'payroll_period_id' => $payrollPeriodId,
                'error' => $snapshotError->getMessage()
            ]);
        }

        $timeDataMap = [];
        foreach ($time_records as $record) {
            try {
                $dateKey = $record->date ? Carbon::parse($record->date)->format('Y-m-d') : null;
                if ($dateKey) {
                    $timeDataMap[$dateKey] = [
                        'am_in' => $record->am_in ?? null,
                        'am_out' => $record->am_out ?? null,
                        'pm_in' => $record->pm_in ?? null,
                        'pm_out' => $record->pm_out ?? null,
                        'late' => floatval($record->late ?? 0),
                        'undertime' => floatval($record->undertime ?? 0),
                        'remarks' => $record->remarks ?? '',
                        'applied_offset' => intval($record->applied_offset ?? 0),
                        'late_offset' => floatval($record->late_offset ?? 0),
                        'undertime_offset' => floatval($record->undertime_offset ?? 0),
                        'is_adjusted' => intval($record->is_adjusted ?? 0),
                        'is_restday' => intval($record->is_restday ?? 0),
                        'absent' => floatval($record->absent ?? 0),
                        'leave' => floatval($record->leave ?? 0),
                        'is_ob' => intval($record->is_ob ?? 0),
                        'is_holiday' => intval($record->is_holiday ?? 0),
                    ];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        $regular_hours = '8:00 AM - 5:00 PM';
        $saturday_hours = '';
        $employeeScheduleMeta = DB::table('employees')
            ->select('work_schedule_id', 'is_shifting')
            ->where('id', $employeeId)
            ->first();

        if (
            $employeeScheduleMeta &&
            !empty($employeeScheduleMeta->work_schedule_id) &&
            intval($employeeScheduleMeta->is_shifting ?? 0) !== 1
        ) {
            $regularSchedule = DB::table('fix_schedules_details')
                ->where([
                    'fix_schedule_id' => $employeeScheduleMeta->work_schedule_id,
                    'day_id' => 1
                ])
                ->first();

            if ($regularSchedule) {
                $amIn = $regularSchedule->am_in ? Carbon::parse($regularSchedule->am_in)->format('h:i A') : '8:00 AM';
                $pmOut = $regularSchedule->pm_out ? Carbon::parse($regularSchedule->pm_out)->format('h:i A') : '5:00 PM';
                $regular_hours = $amIn . ' - ' . $pmOut;
            }

            $saturdaySchedule = DB::table('fix_schedules_details')
                ->where([
                    'fix_schedule_id' => $employeeScheduleMeta->work_schedule_id,
                    'day_id' => 6
                ])
                ->first();

            if ($saturdaySchedule) {
                $satAmIn = $saturdaySchedule->am_in ? Carbon::parse($saturdaySchedule->am_in)->format('h:i A') : '';
                $satPmOut = $saturdaySchedule->pm_out ? Carbon::parse($saturdaySchedule->pm_out)->format('h:i A') : '';
                if ($satAmIn && $satPmOut) {
                    $saturday_hours = $satAmIn . ' - ' . $satPmOut;
                }
            }
        }

        $approver_details = DB::table('approver_details as ad')
            ->join('approver_headers as ah', 'ah.id', '=', 'ad.approver_id')
            ->where('ad.employee_id', $employeeId)
            ->where('ah.type_id', self::DTR_TYPE_ID)
            ->first();

        $approvers = [];
        if ($approver_details) {
            foreach ([1 => 'approver_id_1', 2 => 'approver_id_2', 3 => 'approver_id_3'] as $level => $field) {
                if (empty($approver_details->{$field})) {
                    continue;
                }

                $approverEmp = DB::table('employees')
                    ->select(
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN
                                   CONCAT(first_name,' ',last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](last_name,'$app_key')) 
                                END as name")
                    )
                    ->where('id', $approver_details->{$field})
                    ->first();

                if ($approverEmp) {
                    $approverDept = DB::table('employees as e')
                        ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
                        ->where('e.id', $approver_details->{$field})
                        ->value('d.name');

                    $approvers['approver_' . $level] = [
                        'name' => $approverEmp->name ?? '',
                        'department_name' => $approverDept ?? ''
                    ];
                }
            }
        }

        $employee_dept_id = $employee_record->department_id ?? null;
        $department_head_name = null;
        $department_name = $employee_record->department ?? '';

        if ($employee_dept_id) {
            $department_head = DB::table('departments as d')
                ->join('employees as e', 'e.id', '=', 'd.employee_id')
                ->select(
                    'd.name as department_name',
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                               CONCAT(e.first_name,' ',e.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) 
                            END as name")
                )
                ->where('d.id', $employee_dept_id)
                ->first();

            if ($department_head) {
                $department_head_name = $department_head->name;
                $department_name = $department_head->department_name ?? $department_name;
            }
        }

        $employee = [
            'name' => $employee_record->name ?? 'N/A'
        ];

        $payroll_period = [
            'attendance_start_date' => $displayPeriod['attendance_start_date'] ?? null,
            'attendance_end_date' => $displayPeriod['attendance_end_date'] ?? null
        ];

        $tableStart = !empty($payroll_period['attendance_start_date'])
            ? Carbon::parse($payroll_period['attendance_start_date'])->startOfMonth()
            : Carbon::now()->startOfMonth();
        $tableEnd = !empty($payroll_period['attendance_end_date'])
            ? Carbon::parse($payroll_period['attendance_end_date'])->endOfMonth()
            : $tableStart->copy()->endOfMonth();
        $scheduleTimesMap = [];

        if ($employeeScheduleMeta && !empty($employeeScheduleMeta->work_schedule_id)) {
            $scheduleRows = DB::table('fix_schedules_details')
                ->where('fix_schedule_id', $employeeScheduleMeta->work_schedule_id)
                ->get()
                ->keyBy('day_id');

            $currentDate = $tableStart->copy();
            while ($currentDate->lte($tableEnd)) {
                $dayId = ((int) $currentDate->dayOfWeek + 6) % 7 + 1;
                $schedule = $scheduleRows->get($dayId);
                if ($schedule) {
                    $scheduleTimesMap[$currentDate->format('Y-m-d')] = [
                        'am_in' => $schedule->am_in ? Carbon::parse($schedule->am_in)->format('h:i A') : '',
                        'pm_out' => $schedule->pm_out ? Carbon::parse($schedule->pm_out)->format('h:i A') : '',
                    ];
                }
                $currentDate->addDay();
            }
        }

        return [
            'employee' => $employee,
            'payroll_period' => $payroll_period,
            'timeDataMap' => $timeDataMap,
            'scheduleTimesMap' => $scheduleTimesMap,
            'regular_hours' => $regular_hours,
            'saturday_hours' => $saturday_hours ?: null,
            'approvers' => $approvers,
            'department_head_name' => $department_head_name,
            'department_name' => $department_name,
        ];
    }

    private function generateAndStoreApprovedDtrPdf(int $requestId): ?string
    {
        $dtrRequest = DB::table('time_data_request')->where('id', $requestId)->first();
        if (!$dtrRequest || !$this->isDtrRequestFullyApproved($dtrRequest)) {
            return null;
        }

        $employeeId = (int) ($dtrRequest->employee_id ?? 0);
        $payrollPeriodId = $this->resolvePayrollPeriodIdForApprovedDtr($dtrRequest);
        if ($employeeId <= 0 || $payrollPeriodId <= 0) {
            Log::warning('Approved DTR PDF skipped: missing employee or payroll period', [
                'request_id' => $requestId,
                'employee_id' => $employeeId,
                'payroll_period_id' => $payrollPeriodId,
            ]);

            return null;
        }

        $viewData = $this->prepareDtrPrintViewData($employeeId, $payrollPeriodId);
        $viewData['electronic_approval'] = $this->buildDtrElectronicApprovalMeta($dtrRequest);

        if (!view()->exists('DTR.DTR_approved')) {
            throw new \RuntimeException('Approved DTR print template not found');
        }

        $pdf = PDF::loadView('DTR.DTR_approved', $viewData)->setOptions(['defaultFont' => 'sans-serif']);
        $pdf->setPaper('A4', 'portrait');
        $pdfContent = $pdf->output();

        $relativePath = $this->buildApprovedDtrStorageRelativePath($requestId, $employeeId);
        Storage::disk('local')->put($relativePath, $pdfContent);
        $filePath = Storage::disk('local')->path($relativePath);

        $updateData = ['updated_at' => now()];
        if (Schema::hasColumn('time_data_request', 'approved_dtr_path')) {
            $updateData['approved_dtr_path'] = $filePath;
        }
        if ((int) ($dtrRequest->payroll_period_id ?? 0) <= 0) {
            $updateData['payroll_period_id'] = $payrollPeriodId;
        }
        DB::table('time_data_request')->where('id', $requestId)->update($updateData);

        return is_file($filePath) ? $filePath : null;
    }

    private function resolvePayrollPeriodIdForApprovedDtr($dtrRequest): int
    {
        $payrollPeriodId = (int) ($dtrRequest->payroll_period_id ?? 0);
        if ($payrollPeriodId > 0) {
            return $payrollPeriodId;
        }

        $fromCorrections = DB::table('time_data')
            ->where('dtr_request_id', $dtrRequest->id)
            ->where('payroll_period_id', '>', 0)
            ->value('payroll_period_id');

        return (int) ($fromCorrections ?? 0);
    }

    private function buildApprovedDtrStorageRelativePath(int $requestId, int $employeeId): string
    {
        return 'employee_DTR_documents/APPROVED_DTR_REQ' . $requestId . '_EMP' . $employeeId . '.pdf';
    }

    private function resolveApprovedDtrStoragePath(int $requestId, int $employeeId, ?string $storedPath = null): ?string
    {
        $candidates = [
            $storedPath,
            Storage::disk('local')->path($this->buildApprovedDtrStorageRelativePath($requestId, $employeeId)),
            storage_path('app/employee_DTR_documents/APPROVED_DTR_REQ' . $requestId . '_EMP' . $employeeId . '.pdf'),
        ];

        foreach ($candidates as $candidate) {
            if (!empty($candidate) && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function buildDtrElectronicApprovalMeta($dtrRequest): array
    {
        $employeeId = (int) ($dtrRequest->employee_id ?? 0);
        $maxLevel = $employeeId > 0 ? $this->getDtrMaxApproverLevelForEmployee($employeeId) : 2;
        $approvals = [];

        if ($this->toDtrBool($dtrRequest->approved_1 ?? 0) && (int) ($dtrRequest->approved_by_1_id ?? 0) > 0) {
            $approvals[] = [
                'label' => 'Level 1 Approver',
                'name' => $this->resolveEmployeeDisplayName((int) $dtrRequest->approved_by_1_id),
                'date' => !empty($dtrRequest->approved_date_1)
                    ? Carbon::parse($dtrRequest->approved_date_1)->format('F d, Y h:i A')
                    : '',
            ];
        }

        if ($this->toDtrBool($dtrRequest->approved_2 ?? 0) && (int) ($dtrRequest->approved_by_2_id ?? 0) > 0) {
            $approvals[] = [
                'label' => $maxLevel >= 3 ? 'Level 3 Approver' : 'Level 2 Approver',
                'name' => $this->resolveEmployeeDisplayName((int) $dtrRequest->approved_by_2_id),
                'date' => !empty($dtrRequest->approved_date_2)
                    ? Carbon::parse($dtrRequest->approved_date_2)->format('F d, Y h:i A')
                    : '',
            ];
        }

        $signedDate = '';
        if ($this->toDtrBool($dtrRequest->approved_2 ?? 0) && !empty($dtrRequest->approved_date_2)) {
            $signedDate = Carbon::parse($dtrRequest->approved_date_2)->format('F d, Y h:i A');
        } elseif ($this->toDtrBool($dtrRequest->approved_1 ?? 0) && !empty($dtrRequest->approved_date_1)) {
            $signedDate = Carbon::parse($dtrRequest->approved_date_1)->format('F d, Y h:i A');
        }

        return [
            'reference_no' => (int) ($dtrRequest->id ?? 0),
            'status' => 'SIGNED',
            'signed_date' => $signedDate,
            'approvals' => $approvals,
            'generated_at' => Carbon::now()->format('F d, Y h:i A'),
        ];
    }

    private function resolveEmployeeDisplayName(int $employeeId): string
    {
        if ($employeeId <= 0) {
            return '';
        }

        $app_key = env('APP_KEY', '');
        $row = DB::table('employees')
            ->select(
                DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN
                           CONCAT(first_name,' ',last_name)
                        ELSE
                            RTRIM([dbo].[ufn_DecryptString](first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](last_name,'$app_key'))
                        END as name")
            )
            ->where('id', $employeeId)
            ->first();

        return $row->name ?? '';
    }

    private function resolveEmployeeFromUserId($userId)
    {
        if (is_numeric($userId)) {
            // 1. Try matching users.id -> employee_no -> employees
            $user = DB::table('users')->where('id', $userId)->first();
            if ($user) {
                if (!empty($user->employee_no)) {
                    return DB::table('employees')
                        ->select('id', 'employee_no', 'first_name', 'last_name')
                        ->where('employee_no', $user->employee_no)
                        ->first();
                }
                return null; // User account exists but has no linked employee (e.g. Administrator)
            }

            // 2. Fallback: User not found in users table, caller passed employees.id directly
            return DB::table('employees')
                ->select('id', 'employee_no', 'first_name', 'last_name')
                ->where('id', $userId)
                ->first();
        } else {
            // 3. Fallback: caller passed employee_no string directly
            return DB::table('employees')
                ->select('id', 'employee_no', 'first_name', 'last_name')
                ->where('employee_no', $userId)
                ->first();
        }

        return null;
    }

    private function employeeHasDtrApprover($employeeId)
    {
        return DB::table('approver_details as ad')
            ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
            ->where('ad.employee_id', $employeeId)
            ->where('ah.type_id', self::DTR_TYPE_ID)
            ->exists();
    }

    /**
     * Same approver role lookup as Leave (type_id = 1), but for DTR (type_id = 8).
     */
    private function getDtrApproverRoles($empId)
    {
        $approver_1 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select('a.id', 'a.approver_id_1 as supervisor_id')
            ->where('a.approver_id_1', $empId)
            ->where('a.type_id', self::DTR_TYPE_ID)
            ->distinct()
            ->get();

        $approver_2 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select('a.id', 'a.approver_id_2 as supervisor_id', 'a.approver_id_3')
            ->where('a.approver_id_2', $empId)
            ->where('a.type_id', self::DTR_TYPE_ID)
            ->distinct()
            ->get();

        $approver_3 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select('a.id', 'a.approver_id_3 as supervisor_id', 'a.approver_id_3')
            ->where(function ($q) use ($empId) {
                $q->where('a.approver_id_3', $empId)->orWhere('a.approver_id_4', $empId);
            })
            ->where('a.type_id', self::DTR_TYPE_ID)
            ->distinct()
            ->get();

        return compact('approver_1', 'approver_2', 'approver_3');
    }

    private function hasDtrApproverRole(array $roles)
    {
        return $roles['approver_1']->isNotEmpty()
            || $roles['approver_2']->isNotEmpty()
            || $roles['approver_3']->isNotEmpty();
    }

    /**
     * Approver authorization for a specific DTR request employee (same pattern as Leave process).
     */
    private function getDtrApproversForRequestEmployee($empId, $employeeId)
    {
        $approver_1 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select('a.id', 'a.approver_id_1 as supervisor_id', 'a.approver_id_3')
            ->where('b.employee_id', $employeeId)
            ->where('a.approver_id_1', $empId)
            ->where('a.type_id', self::DTR_TYPE_ID)
            ->distinct()
            ->get();

        $approver_2 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select('a.id', 'a.approver_id_2 as supervisor_id', 'a.approver_id_3')
            ->where('b.employee_id', $employeeId)
            ->where('a.approver_id_2', $empId)
            ->where('a.type_id', self::DTR_TYPE_ID)
            ->distinct()
            ->get();

        $approver_3 = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->select('a.id', 'a.approver_id_3 as supervisor_id', 'a.approver_id_3')
            ->where('b.employee_id', $employeeId)
            ->where(function ($q) use ($empId) {
                $q->where('a.approver_id_3', $empId)->orWhere('a.approver_id_4', $empId);
            })
            ->where('a.type_id', self::DTR_TYPE_ID)
            ->distinct()
            ->get();

        return compact('approver_1', 'approver_2', 'approver_3');
    }

    private function collectDtrPendingForApprover($empId, $appKey, array $roles)
    {
        $approver_1 = $roles['approver_1'];
        $approver_2 = $roles['approver_2'];
        $approver_3 = $roles['approver_3'];
        $pending = collect();

        if ($approver_3->isNotEmpty()) {
            $pending = $this->queryDtrApprovalsForLevel($empId, $appKey, 3);
        } elseif ($approver_1->isNotEmpty() && $approver_2->isEmpty()) {
            $pending = $this->queryDtrApprovalsForLevel($empId, $appKey, 1);
        } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty()) {
            $pending = $this->queryDtrApprovalsForLevel($empId, $appKey, 2);
        } elseif ($approver_1->isNotEmpty() && $approver_2->isNotEmpty()) {
            $pending = $this->queryDtrApprovalsForLevel($empId, $appKey, 1)
                ->merge($this->queryDtrApprovalsForLevel($empId, $appKey, 2))
                ->unique('id')
                ->values();
        }

        return $pending;
    }

    private function getDtrSubordinateEmployeeIds($empId)
    {
        return DB::table('approver_details as ad')
            ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
            ->where('ah.type_id', self::DTR_TYPE_ID)
            ->where(function ($q) use ($empId) {
                $q->where('ah.approver_id_1', $empId)
                    ->orWhere('ah.approver_id_2', $empId)
                    ->orWhere('ah.approver_id_3', $empId)
                    ->orWhere('ah.approver_id_4', $empId);
            })
            ->pluck('ad.employee_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function collectDtrApprovedForApprover($empId, $appKey, array $roles)
    {
        $approver_1 = $roles['approver_1'];
        $approver_2 = $roles['approver_2'];
        $approver_3 = $roles['approver_3'];
        $approved = collect();

        if ($approver_3->isNotEmpty()) {
            $approved = $this->queryDtrApprovedForLevel($empId, $appKey, 3);
        } elseif ($approver_1->isNotEmpty() && $approver_2->isEmpty()) {
            $approved = $this->queryDtrApprovedForLevel($empId, $appKey, 1);
        } elseif ($approver_1->isEmpty() && $approver_2->isNotEmpty()) {
            $approved = $this->queryDtrApprovedForLevel($empId, $appKey, 2);
        } elseif ($approver_1->isNotEmpty() && $approver_2->isNotEmpty()) {
            $approved = $this->queryDtrApprovedForLevel($empId, $appKey, 1)
                ->merge($this->queryDtrApprovedForLevel($empId, $appKey, 2))
                ->unique('id')
                ->values();
        }

        return $approved;
    }

    private function queryDtrApprovedForLevel($empId, $appKey, $level)
    {
        $query = $this->baseDtrApprovalRequestQuery($appKey)
            ->addSelect(DB::raw("CAST($level as int) as approver_level_id"))
            ->whereIn('a.id', function ($subQuery) use ($empId, $level) {
                $subQuery->select('c.id')
                    ->from('approver_details as ad')
                    ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                    ->join('time_data_request as c', 'ad.employee_id', '=', 'c.employee_id')
                    ->where('ah.type_id', self::DTR_TYPE_ID);

                if ($level === 1) {
                    $subQuery->where('ah.approver_id_1', $empId)
                        ->whereRaw('(ISNULL(c.approved_1,0) = 1 AND ISNULL(c.disapproved_1,0) = 0)');
                } elseif ($level === 2) {
                    $subQuery->where('ah.approver_id_2', $empId)
                        ->whereRaw('(ISNULL(c.approved_2,0) = 1 AND ISNULL(c.disapproved_2,0) = 0)');
                } else {
                    $subQuery->where(function ($q) use ($empId) {
                        $q->where('ah.approver_id_3', $empId)->orWhere('ah.approver_id_4', $empId);
                    })
                        ->whereRaw('(ISNULL(c.approved_2,0) = 1 AND ISNULL(c.disapproved_2,0) = 0)')
                        ->whereRaw('(ISNULL(c.status,0) = 1)');
                }
            });

        return $query->distinct()->orderBy('a.request_date', 'desc')->get();
    }

    private function queryDtrApprovedForApprover($empId, $appKey)
    {
        $roles = $this->getDtrApproverRoles($empId);

        return $this->collectDtrApprovedForApprover($empId, $appKey, $roles);
    }

    private function queryDtrReturnedForApprover($empId, $appKey)
    {
        $subordinateIds = $this->getDtrSubordinateEmployeeIds($empId);
        if (empty($subordinateIds)) {
            return collect();
        }

        return $this->baseDtrApprovalRequestQuery($appKey)
            ->whereIn('b.id', $subordinateIds)
            ->where(function ($q) {
                $q->whereRaw('(ISNULL(a.disapproved_1,0) = 1)')
                    ->orWhereRaw('(ISNULL(a.disapproved_2,0) = 1)');
            })
            ->distinct()
            ->orderBy('a.request_date', 'desc')
            ->get();
    }

    private function queryDtrApprovalsForLevel($empId, $appKey, $level)
    {
        $query = $this->baseDtrApprovalRequestQuery($appKey)
            ->addSelect(DB::raw("CAST($level as int) as approver_level_id"))
            ->whereIn('a.id', function ($subQuery) use ($empId, $level) {
                $subQuery->select('c.id')
                    ->from('approver_details as ad')
                    ->join('approver_headers as ah', 'ad.approver_id', '=', 'ah.id')
                    ->join('time_data_request as c', 'ad.employee_id', '=', 'c.employee_id')
                    ->where('ah.type_id', self::DTR_TYPE_ID);

                if ($level === 1) {
                    $subQuery->where('ah.approver_id_1', $empId)
                        ->whereRaw('(ISNULL(c.approved_1,0) = 0 AND ISNULL(c.disapproved_1,0) = 0)')
                        ->whereRaw('(ISNULL(c.status,0) = 0)');
                } elseif ($level === 2) {
                    $subQuery->where('ah.approver_id_2', $empId)
                        ->whereRaw('(ISNULL(c.approved_1,0) = 1 AND ISNULL(c.disapproved_1,0) = 0)')
                        ->whereRaw('(ISNULL(c.approved_2,0) = 0 AND ISNULL(c.disapproved_2,0) = 0)')
                        ->whereRaw('(ISNULL(c.status,0) = 0)');
                } else {
                    $subQuery->where(function ($q) use ($empId) {
                        $q->where('ah.approver_id_3', $empId)->orWhere('ah.approver_id_4', $empId);
                    })
                        ->whereRaw('(ISNULL(c.approved_1,0) = 1 AND ISNULL(c.disapproved_1,0) = 0)')
                        ->whereRaw('(ISNULL(c.approved_2,0) = 1 AND ISNULL(c.disapproved_2,0) = 0)')
                        ->whereRaw('(ISNULL(c.status,0) = 0)');
                }
            });

        return $query->distinct()->orderBy('a.request_date', 'desc')->get();
    }

    private function baseDtrApprovalRequestQuery($appKey)
    {
        return DB::table('time_data_request as a')
            ->join('employees as b', 'a.employee_id', '=', 'b.id')
            ->leftJoin('branches as c', 'b.branch_id', '=', 'c.id')
            ->leftJoin('departments as d', 'b.department_id', '=', 'd.id')
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
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$appKey'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$appKey'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$appKey')) 
                            END as name"),
                'a.status',
                'a.approved_1',
                'a.approved_2',
                'a.disapproved_1',
                'a.disapproved_2',
                'a.request_date',
                'c.name as branch',
                'd.name as department',
                'e.name as division',
                'f.name as section',
                'g.name as position'
            );
    }

    private function toDtrBool($value): bool
    {
        return (int) ($value ?? 0) === 1 || $value === true;
    }

    private function mapDtrApprovalRow($row): array
    {
        $statusSource = (object) [
            'employee_id' => $row->employee_id ?? null,
            'status' => $row->status ?? 0,
            'approved_1' => $row->approved_1 ?? 0,
            'approved_2' => $row->approved_2 ?? 0,
            'disapproved_1' => $row->disapproved_1 ?? 0,
            'disapproved_2' => $row->disapproved_2 ?? 0,
        ];

        $statusLabel = $this->formatDtrRequestStatus($statusSource);
        $isReturned = $statusLabel === 'Returned';
        $isFullyApproved = $statusLabel === 'Approved';

        return [
            'id' => $row->id,
            'photo' => $row->photo ?? null,
            'employee_id' => $row->employee_id,
            'name' => $row->name,
            'request_date' => $row->request_date,
            'branch' => $row->branch ?? null,
            'department' => $row->department ?? null,
            'division' => $row->division ?? null,
            'section' => $row->section ?? null,
            'position' => $row->position ?? null,
            'approver_level_id' => $row->approver_level_id ?? null,
            'approved_1' => $this->toDtrBool($row->approved_1 ?? 0),
            'approved_2' => $this->toDtrBool($row->approved_2 ?? 0),
            'disapproved_1' => $this->toDtrBool($row->disapproved_1 ?? 0),
            'disapproved_2' => $this->toDtrBool($row->disapproved_2 ?? 0),
            'status' => $isFullyApproved,
            'is_pending' => !$isFullyApproved && !$isReturned,
            'status_label' => $statusLabel,
        ];
    }

    private function formatDtrRequestStatus($request)
    {
        if ($this->toDtrBool($request->disapproved_1 ?? 0) || $this->toDtrBool($request->disapproved_2 ?? 0)) {
            return 'Returned';
        }

        if ($this->isDtrRequestFullyApproved($request)) {
            return 'Approved';
        }

        $employeeId = (int) ($request->employee_id ?? 0);
        $maxLevel = $employeeId > 0 ? $this->getDtrMaxApproverLevelForEmployee($employeeId) : 2;

        if ($this->toDtrBool($request->approved_2 ?? 0) && $maxLevel >= 3) {
            return 'Pending Level 3';
        }

        if ($this->toDtrBool($request->approved_1 ?? 0) && $maxLevel >= 2) {
            return 'Pending Level 2';
        }

        return 'Pending';
    }

    private function getDtrMaxApproverLevelForEmployee($employeeId)
    {
        $approverHeader = DB::table('approver_headers as a')
            ->join('approver_details as b', 'a.id', '=', 'b.approver_id')
            ->where('b.employee_id', $employeeId)
            ->where('a.type_id', self::DTR_TYPE_ID)
            ->select(
                'a.approver_id_1',
                'a.approver_id_2',
                'a.approver_id_3',
                'a.approver_id_4'
            )
            ->first();

        $maxApproverLevel = 1;
        if ($approverHeader) {
            if (!empty($approverHeader->approver_id_3) || !empty($approverHeader->approver_id_4)) {
                $maxApproverLevel = 3;
            } elseif (!empty($approverHeader->approver_id_2)) {
                $maxApproverLevel = 2;
            }
        }

        return $maxApproverLevel;
    }

    private function isDtrRequestFullyApproved($request)
    {
        if ($this->toDtrBool($request->disapproved_1 ?? 0) || $this->toDtrBool($request->disapproved_2 ?? 0)) {
            return false;
        }

        if ($this->toDtrBool($request->status ?? 0)) {
            return true;
        }

        $employeeId = (int) ($request->employee_id ?? 0);
        if ($employeeId <= 0) {
            return false;
        }

        $maxLevel = $this->getDtrMaxApproverLevelForEmployee($employeeId);

        if ($maxLevel === 1) {
            return $this->toDtrBool($request->approved_1 ?? 0);
        }

        if ($maxLevel === 2) {
            return $this->toDtrBool($request->approved_2 ?? 0);
        }

        return false;
    }

    private function canEmployeeEditDtrApplication($request): bool
    {
        return in_array($this->formatDtrRequestStatus($request), ['Pending', 'Returned'], true);
    }

    private function getPayrollPeriodsForEmployee($employee)
    {
        $employeeTypeId = (int)($employee->employment_type_id ?? 0);
        $nameExpression = "CONCAT(b.name,' (',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date),') - ', c.name,' | ',convert(nvarchar(50),a.attendance_start_date,107),' to ',convert(nvarchar(50),a.attendance_end_date,107))";

        if (Schema::hasTable('payroll_period_Etype') && $employeeTypeId > 0) {
            $etypeColumns = $this->getPayrollPeriodEtypeColumns();

            $periods = DB::table('payroll_periods as a')
                ->join('payroll_period_Etype as pet', 'pet.' . $etypeColumns['period'], '=', 'a.id')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
                ->where('pet.' . $etypeColumns['employment'], $employeeTypeId)
                ->tap(fn ($query) => $this->applyOpenPayrollPeriodConstraints($query, 'a'))
                ->select(
                    'a.id',
                    'a.attendance_start_date',
                    'a.attendance_end_date',
                    'a.release_date',
                    DB::raw("$nameExpression as name")
                )
                ->distinct()
                ->orderBy('a.release_date', 'desc')
                ->get();

            if ($periods->isNotEmpty()) {
                return $periods;
            }
        }

        return $this->getPayrollPeriodsLegacyFallback($employee);
    }

    private function getPayrollPeriodsLegacyFallback($employee)
    {
        $employeeTypeId = (int)($employee->employment_type_id ?? 0);
        $cosTypeIds = $this->getCosEmploymentTypeIds();
        $isCos = $employeeTypeId > 0 && in_array($employeeTypeId, $cosTypeIds, true);
        $nameExpression = "CONCAT(b.name,' (',DATENAME(MONTH,a.release_date),' ',DATEPART(YEAR,a.release_date),') - ', c.name,' | ',convert(nvarchar(50),a.attendance_start_date,107),' to ',convert(nvarchar(50),a.attendance_end_date,107))";

        $query = DB::table('payroll_periods as a')
            ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
            ->join('payroll_cutoffs as c', 'a.payroll_cutoff_id', '=', 'c.id')
            ->select(
                'a.id',
                'a.attendance_start_date',
                'a.attendance_end_date',
                'a.release_date',
                DB::raw("$nameExpression as name")
            )
            ->tap(fn ($q) => $this->applyOpenPayrollPeriodConstraints($q, 'a'));

        if ($isCos) {
            $intervalIds = [1];
            $employeeInterval = (int)($employee->payroll_interval_id ?? 0);
            if ($employeeInterval > 0) {
                $intervalIds[] = $employeeInterval;
            }
            $query->whereIn('a.payroll_interval_id', array_values(array_unique($intervalIds)));
        } elseif (!empty($employee->payroll_interval_id)) {
            $query->where('a.payroll_interval_id', $employee->payroll_interval_id);
        }

        if (!$isCos && Schema::hasTable('payroll_period_Etype') && !empty($cosTypeIds)) {
            $etypeColumns = $this->getPayrollPeriodEtypeColumns();
            $cosPeriodIds = DB::table('payroll_period_Etype')
                ->whereIn($etypeColumns['employment'], $cosTypeIds)
                ->pluck($etypeColumns['period'])
                ->unique()
                ->values()
                ->all();

            if (!empty($cosPeriodIds)) {
                $query->whereNotIn('a.id', $cosPeriodIds);
            }
        }

        return $query->orderBy('a.release_date', 'desc')->get();
    }

    private function resolvePayrollPeriodForEmployee($employee, $payrollPeriodId)
    {
        $payrollPeriodId = (int)$payrollPeriodId;
        if ($payrollPeriodId <= 0) {
            return null;
        }

        $periodQuery = DB::table('payroll_periods')->where('id', $payrollPeriodId);
        $this->applyOpenPayrollPeriodConstraints($periodQuery);
        $period = $periodQuery->first();

        if (!$period) {
            return null;
        }

        $employeeTypeId = (int)($employee->employment_type_id ?? 0);

        if (Schema::hasTable('payroll_period_Etype') && $employeeTypeId > 0) {
            $etypeColumns = $this->getPayrollPeriodEtypeColumns();
            $allowed = DB::table('payroll_period_Etype')
                ->where($etypeColumns['period'], $payrollPeriodId)
                ->where($etypeColumns['employment'], $employeeTypeId)
                ->exists();

            return $allowed ? $period : null;
        }

        $allowedPeriods = $this->getPayrollPeriodsLegacyFallback($employee);

        return $allowedPeriods->firstWhere('id', $payrollPeriodId);
    }

    private function getCosEmploymentTypeIds()
    {
        static $cosTypeIds = null;
        if ($cosTypeIds !== null) {
            return $cosTypeIds;
        }

        if (!Schema::hasTable('employment_types')) {
            $cosTypeIds = [2, 4, 6];
            return $cosTypeIds;
        }

        $cosTypeIds = DB::table('employment_types')
            ->where(function ($q) {
                $q->where('name', 'like', '%Contract of Service%')
                    ->orWhere('name', 'like', '%Consultant%')
                    ->orWhere('name', 'like', '%Service Provider%')
                    ->orWhere('name', 'like', '%COS%');
            })
            ->pluck('id')
            ->map(fn($id) => (int)$id)
            ->unique()
            ->values()
            ->toArray();

        if (empty($cosTypeIds)) {
            $cosTypeIds = [2, 4, 6];
        }

        return $cosTypeIds;
    }

    private function getPayrollPeriodEtypeColumns()
    {
        static $columns = null;
        if ($columns !== null) {
            return $columns;
        }

        $columns = [
            'period' => Schema::hasColumn('payroll_period_Etype', 'payrollperiod_id')
                ? 'payrollperiod_id'
                : 'payroll_period_id',
            'employment' => Schema::hasColumn('payroll_period_Etype', 'employmenttype_id')
                ? 'employmenttype_id'
                : 'employment_type_id',
        ];

        return $columns;
    }

    /**
     * Only unposted, active payroll periods are selectable in DTR applications.
     */
    private function applyOpenPayrollPeriodConstraints($query, string $alias = '')
    {
        $prefix = $alias !== '' ? $alias . '.' : '';

        $query->where(function ($q) use ($prefix) {
            $q->where($prefix . 'posted', 0)
                ->orWhere($prefix . 'posted', false)
                ->orWhereNull($prefix . 'posted');
        });

        if (Schema::hasColumn('payroll_periods', 'active')) {
            $query->where(function ($q) use ($prefix) {
                $q->where($prefix . 'active', 1)
                    ->orWhere($prefix . 'active', true);
            });
        }

        return $query;
    }

    private function getApplicationAttachmentMeta($requestId)
    {
        $record = DB::table('time_data_request as tdr')
            ->leftJoin('payroll_periods as pp', 'pp.id', '=', 'tdr.payroll_period_id')
            ->leftJoin('payroll_intervals as pi', 'pi.id', '=', 'pp.payroll_interval_id')
            ->where('tdr.id', $requestId)
            ->select(
                'tdr.id',
                'tdr.attachment_name',
                'tdr.payroll_period_id',
                'pp.attendance_start_date',
                'pp.attendance_end_date',
                DB::raw("CASE WHEN tdr.payroll_period_id > 0 THEN
                    CONCAT(pi.name,' (', DATENAME(MONTH, pp.release_date),' ', DATEPART(YEAR, pp.release_date),')')
                    ELSE NULL END as payroll_period")
            )
            ->first();

        if (!$record) {
            return null;
        }

        if (empty($record->attachment_name) && (int) ($record->payroll_period_id ?? 0) <= 0) {
            return null;
        }

        return [
            'request_id' => $record->id,
            'attachment_name' => $record->attachment_name,
            'payroll_period_id' => $record->payroll_period_id,
            'payroll_period' => $record->payroll_period,
            'attendance_start_date' => $record->attendance_start_date ?? null,
            'attendance_end_date' => $record->attendance_end_date ?? null,
        ];
    }

    private function getPendingTimeLogCorrectionsForDtrRequest($dtrRequestId, $employeeId)
    {
        $linkedPending = DB::table('time_data')
            ->where('dtr_request_id', $dtrRequestId)
            ->whereRaw('ISNULL(for_approval,0) = 0')
            ->orderBy('date', 'asc')
            ->get();

        if ($linkedPending->isNotEmpty()) {
            return $linkedPending;
        }

        return DB::table('time_data')
            ->where([
                'employee_id' => $employeeId,
                'payroll_period_id' => 0,
                'for_approval' => 0,
                'is_edited' => 1,
            ])
            ->orderBy('date', 'asc')
            ->get();
    }

    private function getApprovedTimeLogCorrectionsForDtrRequest($dtrRequestId, $employeeId)
    {
        return DB::table('time_data as a')
            ->join('payroll_periods as b', 'a.payroll_period_id', '=', 'b.id')
            ->join('payroll_intervals as c', 'b.payroll_interval_id', '=', 'c.id')
            ->select(
                'a.*',
                DB::raw("CONCAT(c.name,' (', DATENAME(MONTH,b.release_date),' ',DATEPART(YEAR, b.release_date) ,')') as payroll_period")
            )
            ->where([
                'a.employee_id' => $employeeId,
                'a.dtr_request_id' => $dtrRequestId,
            ])
            ->whereRaw('ISNULL(a.for_approval,0) = 1')
            ->orderBy('a.date', 'asc')
            ->get();
    }

    private function getEditableTimeLogsForDtrRequest($dtrRequestId, $employeeId)
    {
        $pending = $this->getPendingTimeLogCorrectionsForDtrRequest($dtrRequestId, $employeeId);
        if ($pending->isNotEmpty()) {
            return $pending;
        }

        $approved = $this->getApprovedTimeLogCorrectionsForDtrRequest($dtrRequestId, $employeeId);
        if ($approved->isNotEmpty()) {
            return $approved;
        }

        $dtrRequest = DB::table('time_data_request')->where('id', $dtrRequestId)->first();
        $payrollPeriodId = (int) ($dtrRequest->payroll_period_id ?? 0);

        if ($payrollPeriodId <= 0) {
            return collect();
        }

        $period = DB::table('payroll_periods')->where('id', $payrollPeriodId)->first();
        if (!$period) {
            return collect();
        }

        return DB::table('time_data')
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$period->attendance_start_date, $period->attendance_end_date])
            ->orderBy('date', 'asc')
            ->get();
    }

    private function isDtrSecondHalfPayrollPeriod($payrollPeriod): bool
    {
        if (!$payrollPeriod) {
            return false;
        }

        $cutoffId = (int) ($payrollPeriod->payroll_cutoff_id ?? 0);
        if ($cutoffId <= 0) {
            return false;
        }

        $cutoffName = DB::table('payroll_cutoffs')
            ->where('id', $cutoffId)
            ->value('name');

        if (is_string($cutoffName) && $cutoffName !== '') {
            $lower = strtolower($cutoffName);
            if (str_contains($lower, '2nd') || str_contains($lower, 'second')) {
                return true;
            }
            if (str_contains($lower, '1st') || str_contains($lower, 'first')) {
                return false;
            }
        }

        return $cutoffId === 3;
    }

    private function resolveDtrFirstHalfPayrollPeriodId($payrollPeriod): ?int
    {
        $currentId = (int) ($payrollPeriod->id ?? 0);
        $intervalId = (int) ($payrollPeriod->payroll_interval_id ?? 0);
        $start = !empty($payrollPeriod->attendance_start_date)
            ? Carbon::parse($payrollPeriod->attendance_start_date)
            : null;

        if ($currentId <= 0 || $intervalId <= 0 || !$start) {
            return null;
        }

        $employmentTypeIds = DB::table('payroll_period_Etype')
            ->where('payrollperiod_id', $currentId)
            ->pluck('employmenttype_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        $firstHalfCutoffIds = DB::table('payroll_cutoffs')
            ->where('payroll_interval_id', $intervalId)
            ->where(function ($q) {
                $q->where('name', 'LIKE', '%1st%')
                    ->orWhere('name', 'LIKE', '%1ST%')
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%first%half%']);
            })
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        if (empty($firstHalfCutoffIds)) {
            $firstHalfCutoffIds = [1];
        }

        $query = DB::table('payroll_periods as pp')
            ->where('pp.payroll_interval_id', $intervalId)
            ->whereIn('pp.payroll_cutoff_id', $firstHalfCutoffIds)
            ->whereYear('pp.attendance_start_date', $start->year)
            ->whereMonth('pp.attendance_start_date', $start->month);

        if (!empty($employmentTypeIds)) {
            $query->whereExists(function ($q) use ($employmentTypeIds) {
                $q->select(DB::raw(1))
                    ->from('payroll_period_Etype as ppe')
                    ->whereColumn('ppe.payrollperiod_id', 'pp.id')
                    ->whereIn('ppe.employmenttype_id', $employmentTypeIds);
            });
        }

        $firstHalfId = $query->value('pp.id');

        return !empty($firstHalfId) ? (int) $firstHalfId : null;
    }

    /**
     * @return int[]
     */
    private function getDtrIncludedPayrollPeriodIds($payrollPeriod): array
    {
        $currentId = (int) ($payrollPeriod->id ?? 0);
        if ($currentId <= 0) {
            return [];
        }

        $ids = [$currentId];

        if (!$this->isDtrSecondHalfPayrollPeriod($payrollPeriod)) {
            return $ids;
        }

        $firstHalfId = $this->resolveDtrFirstHalfPayrollPeriodId($payrollPeriod);
        if (!empty($firstHalfId)) {
            $ids[] = $firstHalfId;
        }

        $ids = array_values(array_unique(array_filter($ids, fn($v) => (int) $v > 0)));
        sort($ids);

        return $ids;
    }

    private function normalizeDtrPayrollPeriodForDisplay($payrollPeriod): array
    {
        $period = (array) $payrollPeriod;

        if (
            $this->isDtrSecondHalfPayrollPeriod($payrollPeriod)
            && !empty($period['attendance_start_date'])
            && !empty($period['attendance_end_date'])
        ) {
            $period['attendance_start_date'] = Carbon::parse($period['attendance_start_date'])
                ->startOfMonth()
                ->format('Y-m-d');
            $period['attendance_end_date'] = Carbon::parse($period['attendance_end_date'])
                ->endOfMonth()
                ->format('Y-m-d');
        }

        return $period;
    }

    private function applyDtrSecondHalfSnapshotOverrides($dailyTimeRecords, int $employeeId, $payrollPeriod)
    {
        $currentPeriodId = (int) ($payrollPeriod->id ?? 0);
        if (!$this->isDtrSecondHalfPayrollPeriod($payrollPeriod) || $currentPeriodId <= 0 || $employeeId <= 0) {
            return $dailyTimeRecords;
        }

        $snapshots = DB::table('time_data_adj')
            ->select(
                'date',
                'am_in',
                'am_out',
                'break_in',
                'break_out',
                'pm_in',
                'pm_out',
                'work_hours',
                'late',
                'undertime',
                'absent',
                'leave',
                'is_ob',
                'ob_id',
                'is_holiday',
                'holiday_id',
                'holiday_pay',
                'is_ot',
                'ot_id',
                'ot_pay',
                'remarks',
                'note',
                'late_offset',
                'undertime_offset',
                'absent_offset',
                'applied_offset',
                'excess_hours',
                'ot_hours',
                'work_schedule_id',
                'is_shifting',
                'is_restday'
            )
            ->where('employee_id', $employeeId)
            ->where('target_payroll_period_id', $currentPeriodId)
            ->where('adjustment_type', 'PRE_CUTOFF_SNAPSHOT')
            ->where(function ($q) {
                $q->whereNull('is_assumed')->orWhere('is_assumed', 0);
            })
            ->get();

        if ($snapshots->isEmpty()) {
            return $dailyTimeRecords;
        }

        $byDate = [];
        foreach ($dailyTimeRecords as $record) {
            $dateKey = Carbon::parse($record->date)->format('Y-m-d');
            $byDate[$dateKey] = $record;
        }

        foreach ($snapshots as $snap) {
            $dateKey = Carbon::parse($snap->date)->format('Y-m-d');
            $base = $byDate[$dateKey] ?? null;
            if (!$base) {
                continue;
            }

            $base->am_in = $snap->am_in;
            $base->am_out = $snap->am_out;
            $base->break_in = $snap->break_in;
            $base->break_out = $snap->break_out;
            $base->pm_in = $snap->pm_in;
            $base->pm_out = $snap->pm_out;
            $base->work_hours = $snap->work_hours;
            $base->late = $snap->late;
            $base->undertime = $snap->undertime;
            $base->absent = $snap->absent;
            $base->leave = $snap->leave;
            $base->is_ob = $snap->is_ob;
            $base->ob_id = $snap->ob_id;
            $base->is_holiday = $snap->is_holiday;
            $base->holiday_id = $snap->holiday_id;
            $base->holiday_pay = $snap->holiday_pay;
            $base->is_ot = $snap->is_ot;
            $base->ot_id = $snap->ot_id;
            $base->ot_pay = $snap->ot_pay;
            $base->remarks = $snap->remarks;
            $base->note = $snap->note;
            $base->late_offset = $snap->late_offset;
            $base->undertime_offset = $snap->undertime_offset;
            $base->absent_offset = $snap->absent_offset;
            $base->applied_offset = $snap->applied_offset;
            $base->excess_hours = $snap->excess_hours;
            $base->ot_hours = $snap->ot_hours;
            $base->work_schedule_id = $snap->work_schedule_id;
            $base->is_shifting = $snap->is_shifting;
            $base->is_restday = $snap->is_restday;

            $byDate[$dateKey] = $base;
        }

        return collect($byDate)
            ->sortBy(function ($row) {
                return $row->date;
            })
            ->values();
    }

    public function calculateAndUpdateRealtimeWorkHours($todayRecord)
    {
        if (!$todayRecord) {
            return 0.0;
        }

        $dateStr = $todayRecord->date ?? Carbon::now('Asia/Manila')->format('Y-m-d');
        $now = Carbon::now('Asia/Manila');
        $isToday = ($dateStr === $now->format('Y-m-d'));

        $totalSeconds = 0;

        // Morning Session
        if (!empty($todayRecord->am_in)) {
            $amIn = Carbon::parse("{$dateStr} {$todayRecord->am_in}", 'Asia/Manila');
            $amOut = null;
            if (!empty($todayRecord->am_out)) {
                $amOut = Carbon::parse("{$dateStr} {$todayRecord->am_out}", 'Asia/Manila');
            } elseif ($isToday && empty($todayRecord->pm_in)) {
                // Currently active AM session
                $noon = Carbon::parse("{$dateStr} 12:00:00", 'Asia/Manila');
                $amOut = $now->gt($noon) ? $noon : $now;
            }

            if ($amOut && $amOut->gt($amIn)) {
                $totalSeconds += $amOut->diffInSeconds($amIn);
            }
        }

        // Afternoon Session
        if (!empty($todayRecord->pm_in)) {
            $pmIn = Carbon::parse("{$dateStr} {$todayRecord->pm_in}", 'Asia/Manila');
            $pmOut = null;
            if (!empty($todayRecord->pm_out)) {
                $pmOut = Carbon::parse("{$dateStr} {$todayRecord->pm_out}", 'Asia/Manila');
            } elseif ($isToday) {
                // Currently active PM session
                $pmOut = $now;
            }

            if ($pmOut && $pmOut->gt($pmIn)) {
                $totalSeconds += $pmOut->diffInSeconds($pmIn);
            }
        }

        // Direct single shift: AM IN to PM OUT without middle punches
        if (!empty($todayRecord->am_in) && !empty($todayRecord->pm_out) && empty($todayRecord->am_out) && empty($todayRecord->pm_in)) {
            $start = Carbon::parse("{$dateStr} {$todayRecord->am_in}", 'Asia/Manila');
            $end = Carbon::parse("{$dateStr} {$todayRecord->pm_out}", 'Asia/Manila');
            if ($end->gt($start)) {
                $grossSeconds = $end->diffInSeconds($start);
                // Deduct 1 hr break if spanning across 12pm - 1pm
                $noon = Carbon::parse("{$dateStr} 12:00:00", 'Asia/Manila');
                $onePm = Carbon::parse("{$dateStr} 13:00:00", 'Asia/Manila');
                if ($start->lt($noon) && $end->gt($onePm)) {
                    $grossSeconds = max(0, $grossSeconds - 3600);
                }
                $totalSeconds = $grossSeconds;
            }
        }

        $workHours = round($totalSeconds / 3600, 2);

        // Update database table time_data if changed
        if ((float)($todayRecord->work_hours ?? 0) !== (float)$workHours) {
            DB::table('time_data')
                ->where('id', $todayRecord->id)
                ->update([
                    'work_hours' => number_format($workHours, 2, '.', ''),
                    'updated_at' => $now->format('Y-m-d H:i:s')
                ]);
            $todayRecord->work_hours = $workHours;
        }

        return $workHours;
    }

    public function getTodayStatus($userId)
    {
        try {
            $user = null;
            $employee = null;

            if (is_numeric($userId)) {
                // 1. Try finding user by users.id
                $user = DB::table('users')->where('id', $userId)->first();
                if ($user) {
                    if (!empty($user->employee_no)) {
                        $employee = DB::table('employees')->where('employee_no', $user->employee_no)->first();
                    }
                    // If user exists but has no employee_no (e.g. Administrator), $employee is intentionally null.
                } else {
                    // 2. User ID was not found in users table; check if caller passed an employee.id directly
                    $employee = DB::table('employees')->where('id', $userId)->first();
                    if ($employee) {
                        $user = DB::table('users')->where('employee_no', $employee->employee_no)->first();
                    }
                }
            } else {
                // Caller passed an employee_no string directly (e.g., 'EMP-00001')
                $employee = DB::table('employees')->where('employee_no', $userId)->first();
                if ($employee) {
                    $user = DB::table('users')->where('employee_no', $employee->employee_no)->first();
                }
            }

            $today = Carbon::now('Asia/Manila')->format('Y-m-d');
            $serverTime = Carbon::now('Asia/Manila')->format('Y-m-d H:i:s');

            $todayRecord = null;
            if ($employee) {
                $todayRecord = DB::table('time_data')
                    ->where('employee_id', $employee->id)
                    ->where('date', $today)
                    ->first();
                if ($todayRecord) {
                    $this->calculateAndUpdateRealtimeWorkHours($todayRecord);
                }
            }

            // Schedule info from fix_schedules & fix_schedules_details
            $scheduleName = 'Fixed Schedule (08:00 AM - 05:00 PM)';
            $scheduleWindow = '08:00 AM - 05:00 PM';
            $weeklyScheduleDetails = collect([]);
            $todayScheduleDetail = null;
            $todayDayId = Carbon::now('Asia/Manila')->dayOfWeekIso; // 1 = Mon ... 7 = Sun

            if ($employee && !empty($employee->work_schedule_id)) {
                $ws = DB::table('fix_schedules')->where('id', $employee->work_schedule_id)->first();
                if ($ws) {
                    $scheduleName = $ws->name ?? $scheduleName;
                }

                $weeklyScheduleDetails = DB::table('fix_schedules_details')
                    ->where('fix_schedule_id', $employee->work_schedule_id)
                    ->orderBy('day_id', 'asc')
                    ->get();

                $todayScheduleDetail = $weeklyScheduleDetails->firstWhere('day_id', $todayDayId);
                if ($todayScheduleDetail && !empty($todayScheduleDetail->am_in) && !empty($todayScheduleDetail->pm_out)) {
                    $scheduleWindow = Carbon::parse($todayScheduleDetail->am_in)->format('h:i A') . ' - ' . Carbon::parse($todayScheduleDetail->pm_out)->format('h:i A');
                }
            }

            // Determine status
            $status = 'Clocked Out';
            $amIn  = $todayRecord->am_in ?? null;
            $amOut = $todayRecord->am_out ?? null;
            $pmIn  = $todayRecord->pm_in ?? null;
            $pmOut = $todayRecord->pm_out ?? null;

            if ($todayRecord) {
                $hasLeave = (float) ($todayRecord->leave ?? 0) > 0;
                $hasOB    = (int) ($todayRecord->is_ob ?? 0) === 1;

                if ($hasLeave) {
                    $status = 'On Leave';
                } elseif ($hasOB) {
                    $status = 'On Travel';
                } elseif (!empty($amIn) && empty($amOut)) {
                    $status = 'Clocked In';
                } elseif (!empty($pmIn) && empty($pmOut)) {
                    $status = 'Clocked In';
                }
            }

            // Check biometric raw log (gracefully handles missing connection or timeout)
            $biometricLog = null;
            $bioHost = config('database.connections.sqlsrv_bio.host');
            if ($employee && !empty($employee->employee_no) && !empty($bioHost)) {
                try {
                    $biometricLog = DB::connection('sqlsrv_bio')
                        ->table('biometric_logs')
                        ->where('employee_no', $employee->employee_no)
                        ->whereDate('punch_time', $today)
                        ->orderBy('punch_time', 'asc')
                        ->first();
                } catch (\Exception $bioEx) {
                    \Log::info('Biometric connection skip: ' . $bioEx->getMessage());
                }
            }

            // Pass slips used count for current month
            $startOfMonth = Carbon::now('Asia/Manila')->startOfMonth()->format('Y-m-d');
            $endOfMonth = Carbon::now('Asia/Manila')->endOfMonth()->format('Y-m-d');
            $passSlipsUsed = 0;
            if ($employee) {
                $passSlipsUsed = DB::table('pass_slips')
                    ->where('employee_id', $employee->id)
                    ->whereBetween('date', [$startOfMonth, $endOfMonth])
                    ->whereIn('status', ['Approved', 'Pending', 'Approved Level 1', 'Approved Level 2'])
                    ->count();
            }

            // Determine WFH vs On-Site setup for today
            // Strict source of truth: fix_schedules_details (is_wfh = 1) OR an approved WFH application.
            $wfhApp = null;
            if ($employee) {
                $wfhApp = DB::table('wfh_application')
                    ->where('employee_id', $employee->id)
                    ->where('cancelled', 0)
                    ->where('disapproved', 0)
                    ->where('disapproved_2', 0)
                    ->where('disapproved_3', 0)
                    ->where('approved_3', 1)
                    ->whereDate('start_date', '<=', $today)
                    ->whereDate('end_date', '>=', $today)
                    ->first();
            }

            $isWfhToday = false;
            $wfhSource = null;

            if ($todayScheduleDetail && (int)($todayScheduleDetail->is_wfh ?? 0) === 1) {
                $isWfhToday = true;
                $wfhSource = 'Fixed WFH Schedule';
            } elseif (!empty($wfhApp)) {
                $isWfhToday = true;
                $wfhSource = 'Approved WFH Application';
            }

            // Load configurable portal feature flags from time_keeping_setups.
            // Try to match the employee's employment type first; fall back to any active row; then use hard defaults.
            $portalConfig = null;
            if ($employee && !empty($employee->employment_type_id)) {
                $portalConfig = DB::table('time_keeping_setups')
                    ->where('employment_type_id', $employee->employment_type_id)
                    ->first();
            }
            if (!$portalConfig) {
                $portalConfig = DB::table('time_keeping_setups')->first();
            }

            $requireSelfie        = isset($portalConfig->require_selfie)   ? (bool) $portalConfig->require_selfie   : true;
            $enforceGeofence      = isset($portalConfig->enforce_geofence) ? (bool) $portalConfig->enforce_geofence : true;

            // Automatically assign logging method based on employee setup
            if ($isWfhToday) {
                $setupType = 'wfh';
                $loggingMethod = 'Web Clock / Selfie / GPS';
                $enableWebClock = true;
                $setupMessage = 'WFH Setup: Web Clock enabled with selfie verification & GPS location tracking.';
            } else {
                $setupType = 'on_site';
                $loggingMethod = 'Office Biometric Terminal';
                $enableWebClock = false;
                $setupMessage = 'On-Site Setup: Assigned to office Biometric Terminal login.';
            }

            return $this->successResponse([
                'server_time'              => $serverTime,
                'today_date'               => $today,
                'employee_id'              => $employee->id ?? 0,
                'employee_name'            => $user->name ?? ($employee ? ($employee->first_name . ' ' . $employee->last_name) : 'Employee'),
                'status'                   => $status,
                'schedule_name'            => $scheduleName,
                'schedule_window'          => $scheduleWindow,
                'am_in'                    => $amIn ? Carbon::parse($amIn)->format('h:i A') : null,
                'am_out'                   => $amOut ? Carbon::parse($amOut)->format('h:i A') : null,
                'pm_in'                    => $pmIn ? Carbon::parse($pmIn)->format('h:i A') : null,
                'pm_out'                   => $pmOut ? Carbon::parse($pmOut)->format('h:i A') : null,
                'work_hours'               => $todayRecord->work_hours ?? 0,
                'is_late'                  => ($todayRecord->late ?? 0) > 0,
                'is_undertime'             => ($todayRecord->undertime ?? 0) > 0,
                'is_missed_log'            => empty($amIn) || (empty($amOut) && !empty($pmIn)) || (empty($pmOut) && !empty($amOut)),
                'has_biometric_today'      => !empty($biometricLog),
                'biometric_time'           => $biometricLog ? Carbon::parse($biometricLog->punch_time)->format('h:i A') : null,
                'pass_slips_used_this_month' => $passSlipsUsed,
                // Setup & Assigned Logging Method
                'setup_type'               => $setupType,
                'logging_method'           => $loggingMethod,
                'is_wfh_today'             => $isWfhToday,
                'wfh_source'               => $wfhSource,
                'setup_message'            => $setupMessage,
                // Portal feature flags — configured from Control Panel → Timekeeping Setup
                'enable_web_clock'         => $enableWebClock,
                'enable_biometric'         => true,
                'require_selfie'           => $requireSelfie,
                'enforce_geofence'         => $enforceGeofence,
                'weekly_schedule'          => $weeklyScheduleDetails->map(function($item) {
                    $win = 'Rest Day';
                    if ((int)($item->is_restday ?? 0) === 0 && !empty($item->am_in) && !empty($item->pm_out)) {
                        $win = Carbon::parse($item->am_in)->format('h:i A') . ' - ' . Carbon::parse($item->pm_out)->format('h:i A');
                    }
                    return [
                        'day_id'      => (int) $item->day_id,
                        'am_in'       => $item->am_in,
                        'pm_out'      => $item->pm_out,
                        'is_restday'  => (int) ($item->is_restday ?? 0) === 1,
                        'is_wfh'      => (int) ($item->is_wfh ?? 0) === 1,
                        'work_hours'  => $item->work_hours,
                        'time_window' => $win,
                    ];
                })->values(),
            ], 'Today attendance status fetched successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }


    public function webClock(Request $request)
    {
        try {
            $userId = $request->input('user_id');
            $action = $request->input('action', 'in'); // 'in' or 'out'
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');
            $geofenceStatus = $request->input('geofence_status', 'within');
            $reason = $request->input('reason');

            $user = DB::table('users')->where('id', $userId)->first();
            if (!$user) {
                return $this->errorResponse('User not found', 404);
            }

            $employee = DB::table('employees')->where('employee_no', $user->employee_no)->first();
            if (!$employee) {
                return $this->errorResponse('Employee record not linked to user account', 400);
            }

            $today = Carbon::now('Asia/Manila')->format('Y-m-d');
            $nowTime = Carbon::now('Asia/Manila')->format('H:i:s');
            $nowFull = Carbon::now('Asia/Manila')->format('Y-m-d H:i:s');

            $existing = DB::table('time_data')
                ->where('employee_id', $employee->id)
                ->where('date', $today)
                ->first();

            // Format WFH GPS Location string if coordinates provided
            $wfhLocation = null;
            if (!empty($latitude) && !empty($longitude)) {
                $wfhLocation = "Lat: {$latitude}, Long: {$longitude}";
            } elseif (!empty($reason)) {
                $wfhLocation = $reason;
            }

            if ($action === 'in') {
                if ($existing && !empty($existing->am_in) && empty($existing->am_out)) {
                    return $this->errorResponse('You are already clocked in. Please clock out first.', 422);
                }
                if ($existing) {
                    if (empty($existing->am_in)) {
                        DB::table('time_data')->where('id', $existing->id)->update([
                            'am_in' => $nowTime,
                            'is_wfh' => 1,
                            'wfh_location' => $wfhLocation ?? $existing->wfh_location,
                            'manual_entry_source' => 'Web Clock (GPS Verified)',
                            'remarks' => $reason ? "Web Clock In: {$reason}" : 'Web Clock In',
                            'updated_at' => $nowFull
                        ]);
                    } else {
                        DB::table('time_data')->where('id', $existing->id)->update([
                            'pm_in' => $nowTime,
                            'is_wfh' => 1,
                            'wfh_location' => $wfhLocation ?? $existing->wfh_location,
                            'manual_entry_source' => 'Web Clock (GPS Verified)',
                            'remarks' => $reason ? "Web PM Clock In: {$reason}" : 'Web PM Clock In',
                            'updated_at' => $nowFull
                        ]);
                    }
                } else {
                    DB::table('time_data')->insert([
                        'employee_id' => $employee->id,
                        'date' => $today,
                        'am_in' => $nowTime,
                        'is_wfh' => 1,
                        'wfh_location' => $wfhLocation,
                        'manual_entry_source' => 'Web Clock (GPS Verified)',
                        'remarks' => $reason ? "Web Clock In: {$reason}" : 'Web Clock In',
                        'created_at' => $nowFull,
                        'updated_at' => $nowFull
                    ]);
                }
            } else { // out
                if ($existing) {
                    if (!empty($existing->pm_in) && empty($existing->pm_out)) {
                        DB::table('time_data')->where('id', $existing->id)->update([
                            'pm_out' => $nowTime,
                            'wfh_location' => $wfhLocation ?? $existing->wfh_location,
                            'updated_at' => $nowFull
                        ]);
                    } elseif (!empty($existing->am_in) && empty($existing->am_out)) {
                        DB::table('time_data')->where('id', $existing->id)->update([
                            'am_out' => $nowTime,
                            'wfh_location' => $wfhLocation ?? $existing->wfh_location,
                            'updated_at' => $nowFull
                        ]);
                    } else {
                        DB::table('time_data')->where('id', $existing->id)->update([
                            'pm_out' => $nowTime,
                            'wfh_location' => $wfhLocation ?? $existing->wfh_location,
                            'updated_at' => $nowFull
                        ]);
                    }
                } else {
                    DB::table('time_data')->insert([
                        'employee_id' => $employee->id,
                        'date' => $today,
                        'am_out' => $nowTime,
                        'is_wfh' => 1,
                        'wfh_location' => $wfhLocation,
                        'manual_entry_source' => 'Web Clock (GPS Verified)',
                        'remarks' => 'Web Clock Out (No prior clock-in)',
                        'created_at' => $nowFull,
                        'updated_at' => $nowFull
                    ]);
                }
            }

            return $this->getTodayStatus($userId);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}

