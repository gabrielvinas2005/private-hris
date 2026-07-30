<?php

namespace App\Http\Controllers;

use App\Services\LeaveService;
use App\Traits\ApiResponse;
use PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcessAttendanceController extends Controller
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
            $intervals = DB::table('payroll_intervals')->where('active', true)->get();
            $dummy_daily_time_records = array(
                'id' => 0,
                'photo' => null,
                'employee_no'  => null,
                'department' => null,
                'position' => null,
                'name' => null
            );

            $daily_time_records = (object)$dummy_daily_time_records;
            $daily_time_records = collect([$daily_time_records]);

            // trigger auto-approved leave
            // (new LeaveService)->autoApproved();

            return $this->successResponse([
                'intervals' => $intervals,
                'daily_time_records' => $daily_time_records
            ], 'Process attendance data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve process attendance data: ' . $e->getMessage());
        }
    }

    public function process(Request $request)
    {
        set_time_limit(16000);

        $request->validate([
            'payroll_interval_id' => 'required',
            'payroll_period_id' => 'required'
        ]);

        $payroll_period = DB::table('payroll_periods')->where('id', $request->payroll_period_id)->get();

        $from_date = $payroll_period[0]->attendance_start_date ?? null;
        $to_date = $payroll_period[0]->attendance_end_date ?? null;
        $is_complete_attendance = false;

        // first get employee schedule type and insert data to time data table.
        // Get & loop employees base on payroll interval id.
        $employees = DB::table('employees')
            ->select('id', 'first_name', 'last_name', 'is_shifting', 'work_schedule_id', 'date_hired', 'payroll_interval_id')
            ->where([
                'active' => true,
                'is_employee' => true,
                'payroll_interval_id' => $request->payroll_interval_id
            ])
            ->where('work_schedule_id', '<>', 0)
            ->get();

        try {
            // Check if Biometrics Server is Connected
            $pdo = DB::connection('sqlsrv_bio')->getPdo();
            $database = DB::connection('sqlsrv_bio')->getDatabaseName();

            if ($database != '') {
                // link external server or server 2
                $bio_server = env('DB_HOST_BIO', '');
                $bio_username = env('DB_USERNAME_BIO', '');
                $bio_password = env('DB_PASSWORD_BIO', '');
                $bio_database = env('DB_DATABASE_BIO', '');

                if ($bio_server == '' && env('DB_BIO_ENCRYPTED', false)) {
                    $bio_server = secEnv('DB_HOST_BIO', '');
                    $bio_username = secEnv('DB_USERNAME_BIO', '');
                    $bio_password = secEnv('DB_PASSWORD_BIO', '');
                    $bio_database = secEnv('DB_DATABASE_BIO', '');
                }

                if ($bio_server == '' || $bio_database == '') {
                    $is_bio_connected = false;
                } else {
                    $is_bio_connected = true;
                }
            } else {
                $is_bio_connected = false;
            }

            if ($is_bio_connected) {

                set_time_limit(16000);

                // Database Configuration Query.
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
                            set @Server_Name = @Server_Name
                        end

                    if not exists(select * from sys.servers where name = N'SRV_NAME')
                        begin
                            EXEC sp_addlinkedserver @server='SRV_NAME',
                                            @srvproduct=N'',
                                            @provider=N'MSOLEDBSQL',
                                            @datasrc=@Server_Name;

                            EXEC sp_addlinkedsrvlogin 'SRV_NAME', 'false', NULL, @Username, @Password;
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

                $database1 = DB::connection('sqlsrv')->getDatabaseName();
                $database2 = 'SRV_NAME.' . DB::connection('sqlsrv_bio')->getDatabaseName();
            }
        } catch (\Throwable $th) {
            $is_bio_connected = false;
            $database2 = '';
        }

        if ($employees->isNotEmpty()) {

            foreach ($employees as $emp) {
                if ($emp->is_shifting == true) {

                    // Temporary commented out.
                    // if ($emp->date_hired > $from_date) {
                    //     $from_date = $emp->date_hired;
                    // }

                    for ($date = $from_date; $date <= $to_date; $date = date("Y-m-d", strtotime("$date +1 day"))) {

                        $this->updateNewStepIncrement($emp->id, $date);

                        // get shifting schedule.
                        $shifting_schedule = DB::table('shift_schedules_details')
                            ->where([
                                'shift_schedule_id' => $emp->work_schedule_id,
                                'shift_date' => $date
                            ])
                            ->get();

                        $existing_time_data = db::table('time_data')
                            ->where('employee_id', $emp->id)
                            ->where('date', $date)
                            ->get();

                        if ($shifting_schedule->isNotEmpty()) {
                            if ($existing_time_data->isEmpty()) {

                                if ($is_bio_connected) {
                                    // check timelogs in biometrics.
                                    $biometrics = DB::select("
                                            SELECT TOP(1)
                                                c.id,
                                                UPPER(CONCAT(c.first_name,' ',c.last_name)) as name,
                                                convert(nvarchar(50),b.checktime,101) as checktime,
                                                (select top(1) isnull(bio.checktime,'') from $database2.dbo.checkinout as bio where bio.checktype = 'I' and convert(nvarchar(50),bio.checktime,101) = convert(nvarchar(50),b.checktime,101) and bio.userid = b.userid order by bio.checktime asc) as am_in,
                                                (select top(1) isnull(bio.checktime,'') from $database2.dbo.checkinout as bio where bio.checktype = '0' and convert(nvarchar(50),bio.checktime,101) = convert(nvarchar(50),b.checktime,101) and bio.userid = b.userid order by bio.checktime desc) as am_out,
                                                (select top(1) isnull(bio.checktime,'') from $database2.dbo.checkinout as bio where bio.checktype = '1' and convert(nvarchar(50),bio.checktime,101) = convert(nvarchar(50),b.checktime,101) and bio.userid = b.userid order by bio.checktime asc) as pm_in,
                                                (select top(1) isnull(bio.checktime,'') from $database2.dbo.checkinout as bio where bio.checktype = 'O' and convert(nvarchar(50),bio.checktime,101) = convert(nvarchar(50),b.checktime,101) and bio.userid = b.userid order by bio.checktime desc) as pm_out
                                            FROM $database2.dbo.userinfo as a inner join
                                                 $database2.dbo.checkinout as b on a.userid = b.userid inner join
                                                 $database1.dbo.employees as c on rtrim(a.badgenumber) = rtrim(c.access_no)
                                            WHERE convert(date,b.checktime) = convert(date,'$date')
                                            AND c.id = $emp->id
                                        ");

                                    $biometrics = collect($biometrics);
                                } else {
                                    $biometrics = null;
                                }

                                //for test
                                // if ($date == '2024-01-22' && $emp->id == 3) {
                                //     dd($biometrics);
                                // }

                                if ($biometrics != null) {
                                    // insert data for shifting schedule from biometrics.
                                    $time_data = array(
                                        'employee_id' => $emp->id,
                                        'payroll_period_id' => $request->payroll_period_id,
                                        'date' => $date,
                                        'am_in' => $biometrics[0]->am_in ?? null,
                                        'am_out' => $biometrics[0]->am_out ?? null,
                                        'break_in' => null,
                                        'break_out' => null,
                                        'pm_in' => $biometrics[0]->pm_in ?? null,
                                        'pm_out' => $biometrics[0]->pm_out ?? null,
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
                                        'is_shifting' => false,
                                        'work_schedule_id' => $emp->work_schedule_id,
                                        'ob_hours' => 0,
                                        'ot_hours' => 0,
                                        'for_approval' => 1
                                    );

                                    DB::table('time_data')->updateOrInsert(['employee_id' => $emp->id, 'date' => $date], $time_data);
                                } else {
                                    // insert data for shifting schedule
                                    $time_data = array(
                                        'employee_id' => $emp->id,
                                        'payroll_period_id' => $request->payroll_period_id,
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
                                        'work_schedule_id' => $emp->work_schedule_id,
                                        'ob_hours' => 0,
                                        'ot_hours' => 0,
                                        'for_approval' => 1
                                    );
                                }

                                DB::table('time_data')->updateOrInsert(['employee_id' => $emp->id, 'date' => $date], $time_data);
                            } else {
                                $time_data = array(
                                    'employee_id' => $emp->id,
                                    'payroll_period_id' => $request->payroll_period_id,
                                    'date' => $date,
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
                                    'work_schedule_id' => $emp->work_schedule_id,
                                    'ob_hours' => 0,
                                    'ot_hours' => 0,
                                    'for_approval' => 1
                                );

                                DB::table('time_data')->updateOrInsert(['employee_id' => $emp->id, 'date' => $date], $time_data);
                            }
                        }
                    }
                } else {

                    // Temporary commented out
                    // if ($emp->date_hired > $from_date) {
                    //     $from_date = $emp->date_hired;
                    // }

                    for ($date = $from_date; $date <= $to_date; $date = date("Y-m-d", strtotime("$date +1 day"))) {
                        $this->updateNewStepIncrement($emp->id, $date);

                        $day_id = Carbon::parse($date)->dayOfWeek;

                        // if sunday
                        if ($day_id == 0) {
                            $day_id = 7;
                        }

                        //get fix schedule
                        $fix_schedule = DB::table('fix_schedules as a')
                            ->join('fix_schedules_details as b', 'a.id', '=', 'b.fix_schedule_id')
                            ->select(
                                'b.*',
                                'a.is_complete_attendance'
                            )
                            ->where([
                                'b.fix_schedule_id' => $emp->work_schedule_id,
                                'b.day_id' => $day_id
                            ])
                            ->get();

                        $existing_time_data = db::table('time_data')
                            ->where('employee_id', $emp->id)
                            ->where('date', $date)
                            ->get();

                        if ($fix_schedule->isNotEmpty()) {

                            $is_complete_attendance = $fix_schedule[0]->is_complete_attendance ?? false;

                            if ($existing_time_data->isEmpty()) {
                                if ($is_bio_connected) {
                                    // check timelogs in biometrics.
                                    $biometrics = DB::select("
                                            SELECT TOP(1)
                                                c.id,
                                                UPPER(CONCAT(c.first_name,' ',c.last_name)) as name,
                                                convert(nvarchar(50),b.checktime,101) as checktime,
                                                (select top(1) isnull(bio.checktime,'') from $database2.dbo.checkinout as bio where bio.checktype = 'I' and convert(nvarchar(50),bio.checktime,101) = convert(nvarchar(50),b.checktime,101) and bio.userid = b.userid order by bio.checktime asc) as am_in,
                                                (select top(1) isnull(bio.checktime,'') from $database2.dbo.checkinout as bio where bio.checktype = '0' and convert(nvarchar(50),bio.checktime,101) = convert(nvarchar(50),b.checktime,101) and bio.userid = b.userid order by bio.checktime desc) as am_out,
                                                (select top(1) isnull(bio.checktime,'') from $database2.dbo.checkinout as bio where bio.checktype = '1' and convert(nvarchar(50),bio.checktime,101) = convert(nvarchar(50),b.checktime,101) and bio.userid = b.userid order by bio.checktime asc) as pm_in,
                                                (select top(1) isnull(bio.checktime,'') from $database2.dbo.checkinout as bio where bio.checktype = 'O' and convert(nvarchar(50),bio.checktime,101) = convert(nvarchar(50),b.checktime,101) and bio.userid = b.userid order by bio.checktime desc) as pm_out
                                            FROM $database2.dbo.userinfo as a inner join
                                                 $database2.dbo.checkinout as b on a.userid = b.userid inner join
                                                 $database1.dbo.employees as c on rtrim(a.badgenumber) = rtrim(c.access_no)
                                            WHERE convert(date,b.checktime) = convert(date,'$date')
                                            AND c.id = $emp->id
                                        ");

                                    $biometrics = collect($biometrics);
                                } else {
                                    $biometrics = null;
                                }

                                //for test
                                // if ($date == '2024-01-22' && $emp->id == 3) {
                                //     dd($biometrics);
                                // }

                                if ($biometrics != null) {
                                    // insert data for fixed schedule from biometrics.
                                    $time_data = array(
                                        'employee_id' => $emp->id,
                                        'payroll_period_id' => $request->payroll_period_id,
                                        'date' => $date,
                                        'am_in' => $biometrics[0]->am_in ?? null,
                                        'am_out' => $biometrics[0]->am_out ?? null,
                                        'break_in' => null,
                                        'break_out' => null,
                                        'pm_in' => $biometrics[0]->pm_in ?? null,
                                        'pm_out' => $biometrics[0]->pm_out ?? null,
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
                                        'is_shifting' => false,
                                        'work_schedule_id' => $emp->work_schedule_id,
                                        'ob_hours' => 0,
                                        'ot_hours' => 0,
                                        'for_approval' => 1
                                    );

                                    DB::table('time_data')->updateOrInsert(['employee_id' => $emp->id, 'date' => $date], $time_data);
                                } else {
                                    // insert data for fixed schedule
                                    $time_data = array(
                                        'employee_id' => $emp->id,
                                        'payroll_period_id' => $request->payroll_period_id,
                                        'date' => $date,
                                        'am_in' => $is_complete_attendance ? $fix_schedule[0]->am_in : null,
                                        'am_out' => $is_complete_attendance ? $fix_schedule[0]->am_out : null,
                                        'break_in' => $is_complete_attendance ? $fix_schedule[0]->break_in : null,
                                        'break_out' => $is_complete_attendance ? $fix_schedule[0]->break_out : null,
                                        'pm_in' => $is_complete_attendance ? $fix_schedule[0]->pm_in : null,
                                        'pm_out' => $is_complete_attendance ? $fix_schedule[0]->pm_out : null,
                                        'work_hours' => $is_complete_attendance ? $fix_schedule[0]->work_hours : 0,
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
                                        'is_shifting' => false,
                                        'work_schedule_id' => $emp->work_schedule_id,
                                        'ob_hours' => 0,
                                        'ot_hours' => 0,
                                        'for_approval' => 1
                                    );
                                }

                                DB::table('time_data')->updateOrInsert(['employee_id' => $emp->id, 'date' => $date], $time_data);
                            } else {
                                $time_data = array(
                                    'employee_id' => $emp->id,
                                    'payroll_period_id' => $request->payroll_period_id,
                                    'date' => $date,
                                    'am_in' => $is_complete_attendance ? $fix_schedule[0]->am_in : null,
                                    'am_out' => $is_complete_attendance ? $fix_schedule[0]->am_out : null,
                                    'break_in' => $is_complete_attendance ? $fix_schedule[0]->break_in : null,
                                    'break_out' => $is_complete_attendance ? $fix_schedule[0]->break_out : null,
                                    'pm_in' => $is_complete_attendance ? $fix_schedule[0]->pm_in : null,
                                    'pm_out' => $is_complete_attendance ? $fix_schedule[0]->pm_out : null,
                                    'work_hours' => $is_complete_attendance ? $fix_schedule[0]->work_hours : 0,
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
                                    'is_shifting' => false,
                                    'work_schedule_id' => $emp->work_schedule_id,
                                    'ob_hours' => 0,
                                    'ot_hours' => 0,
                                    'for_approval' => 1
                                );

                                DB::table('time_data')->updateOrInsert(['employee_id' => $emp->id, 'date' => $date], $time_data);
                            }
                        }
                    }
                }

                // attendance computation.
                // get data in time data table
                $employee_time_data = DB::table('time_data as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->select(
                        'a.*',
                        'b.salary',
                        'b.employment_type_id'
                    )
                    ->where('a.payroll_period_id', $request->payroll_period_id)
                    ->where('a.employee_id', $emp->id)
                    ->where([
                        'b.active' => true,
                        'b.is_employee' => true
                    ])
                    ->get();

                foreach ($employee_time_data as $emp_dtr) {

                    $remarks = '';
                    $absent = 0;

                    if ($emp_dtr->is_shifting == true) {
                        // get shifting schedule
                        $shifting_schedule = DB::table('shift_schedules_details')
                            ->where([
                                'shift_schedule_id' => $emp_dtr->work_schedule_id,
                                'shift_date' => $emp_dtr->date
                            ])
                            ->get();

                        if ($shifting_schedule->isNotEmpty()) {
                            $am_in_schedule = strtotime($shifting_schedule[0]->am_in);
                            $am_out_schedule = strtotime($shifting_schedule[0]->am_out);
                            $break_in_schedule = strtotime($shifting_schedule[0]->break_in);
                            $break_out_schedule = strtotime($shifting_schedule[0]->break_out);
                            $pm_in_schedule = strtotime($shifting_schedule[0]->pm_in);
                            $pm_out_schedule = strtotime($shifting_schedule[0]->pm_out);
                            $am_in_schedule_flex = $shifting_schedule[0]->am_in;
                            $am_out_schedule_flex = $shifting_schedule[0]->am_out;
                            $break_in_schedule_flex = $shifting_schedule[0]->break_in;
                            $break_out_schedule_flex = $shifting_schedule[0]->break_out;
                            $pm_in_schedule_flex = $shifting_schedule[0]->pm_in;
                            $pm_out_schedule_flex = $shifting_schedule[0]->pm_out;
                            $grace_period = $shifting_schedule[0]->grace_period;
                            $flexi_hours = $shifting_schedule[0]->flexi_hours;
                            $work_hours = $shifting_schedule[0]->work_hours;
                            // get nd setup data
                            $nd_rate = $shifting_schedule[0]->nd_rate;
                            $nd_start = $shifting_schedule[0]->nd_start;
                            $nd_end = $shifting_schedule[0]->nd_end;
                            $with_nd = $shifting_schedule[0]->with_nd;
                        } else {
                            $am_in_schedule = '';
                            $am_out_schedule = '';
                            $break_in_schedule = '';
                            $break_out_schedule = '';
                            $pm_in_schedule = '';
                            $pm_out_schedule = '';
                            $grace_period = 0;
                            $flexi_hours = 0;
                            $work_hours = 0;
                            // get nd setup data
                            $nd_rate = 0;
                            $nd_start = '';
                            $nd_end = '';
                            $with_nd = false;
                        }
                    } else {

                        $day_id = Carbon::parse($emp_dtr->date)->dayOfWeek;

                        // if sunday
                        if ($day_id == 0) {
                            $day_id = 7;
                        }

                        // get fix schedule
                        $fix_schedule = DB::table('fix_schedules_details')
                            ->where([
                                'fix_schedule_id' => $emp_dtr->work_schedule_id,
                                'day_id' => $day_id
                            ])
                            ->get();

                        if ($fix_schedule->isNotEmpty()) {
                            $am_in_schedule = strtotime($fix_schedule[0]->am_in);
                            $am_out_schedule = strtotime($fix_schedule[0]->am_out);
                            $break_in_schedule = strtotime($fix_schedule[0]->break_in);
                            $break_out_schedule = strtotime($fix_schedule[0]->break_out);
                            $pm_in_schedule = strtotime($fix_schedule[0]->pm_in);
                            $pm_out_schedule = strtotime($fix_schedule[0]->pm_out);
                            $am_in_schedule_flex = $fix_schedule[0]->am_in;
                            $am_out_schedule_flex = $fix_schedule[0]->am_out;
                            $break_in_schedule_flex = $fix_schedule[0]->break_in;
                            $break_out_schedule_flex = $fix_schedule[0]->break_out;
                            $pm_in_schedule_flex = $fix_schedule[0]->pm_in;
                            $pm_out_schedule_flex = $fix_schedule[0]->pm_out;
                            $grace_period = $fix_schedule[0]->grace_period;
                            $flexi_hours = $fix_schedule[0]->flexi_hours;
                            $work_hours = $fix_schedule[0]->work_hours;
                            // get nd setup data
                            $nd_rate = $fix_schedule[0]->nd_rate;
                            $nd_start = $fix_schedule[0]->nd_start;
                            $nd_end = $fix_schedule[0]->nd_end;
                            $with_nd = $fix_schedule[0]->with_nd;
                        } else {
                            $am_in_schedule = '';
                            $am_out_schedule = '';
                            $break_in_schedule = '';
                            $break_out_schedule = '';
                            $pm_in_schedule = '';
                            $pm_out_schedule = '';
                            $grace_period = 0;
                            $flexi_hours = 0;
                            $work_hours = 0;
                            // get nd setup data
                            $nd_rate = 0;
                            $nd_start = '';
                            $nd_end = '';
                            $with_nd = false;
                        }
                    }

                    // set variables.
                    // $am_in = $emp_dtr->am_in == null ? 0 : strtotime(date('g:i a', strtotime($emp_dtr->am_in)));
                    // $am_out = $emp_dtr->am_out == null ? 0 : strtotime(date('g:i a', strtotime($emp_dtr->am_out)));
                    // $break_in = $emp_dtr->break_in == null ? 0 : strtotime(date('g:i a', strtotime($emp_dtr->break_in)));
                    // $break_out = $emp_dtr->break_out == null ? 0 : strtotime(date('g:i a', strtotime($emp_dtr->break_out)));
                    // $pm_in = $emp_dtr->pm_in == null ? 0 : strtotime(date('g:i a', strtotime($emp_dtr->pm_in)));
                    // $pm_out = $emp_dtr->pm_out == null ? 0 : strtotime(date('g:i a', strtotime($emp_dtr->pm_out)));

                    $am_in = strtotime($emp_dtr->am_in);
                    $am_out = strtotime($emp_dtr->am_out);
                    $break_in = strtotime($emp_dtr->break_in);
                    $break_out = strtotime($emp_dtr->break_out);
                    $pm_in = strtotime($emp_dtr->pm_in);
                    $pm_out = strtotime($emp_dtr->pm_out);

                    // check if absent or rest day
                    if ($am_in_schedule == null && $pm_out_schedule == null || $am_in_schedule == '' && $pm_out_schedule == '') {
                        $is_rest_day = true;
                        $remarks = 'Rest Day';
                        $absent = 0;
                    } else {
                        $is_rest_day = false;
                        $remarks = '';
                    }

                    // set holiday parameters
                    $year_id = Carbon::parse($emp_dtr->date)->year;
                    $month_id = Carbon::parse($emp_dtr->date)->month;
                    $day_id = Carbon::parse($emp_dtr->date)->day;
                    $branch_id = DB::table('employees')
                        ->select('branch_id', 'salary', 'employment_type_id')
                        ->where('id', $emp_dtr->employee_id)->get();

                    // get time keeping setup.
                    $time_keeping = DB::table('time_keeping_setups')->where('employment_type_id', $branch_id[0]->employment_type_id)->get();

                    if ($time_keeping->isNotEmpty()) {
                        $tk_days = $time_keeping[0]->work_days;
                        $tk_hours = $time_keeping[0]->work_hours;
                        $with_holiday_pay = $time_keeping[0]->with_holiday_pay;
                    } else {
                        $tk_days = 22;
                        $tk_hours = 8;
                        $with_holiday_pay = false;
                    }

                    $date = $emp_dtr->date;

                    $yesterday = date('Y-m-d', strtotime("$date -1 days"));
                    $tomorrow = date('Y-m-d', strtotime("$date +1 days"));

                    // get holiday.
                    $holidays = DB::table('holidays as a')
                        ->join('holiday_types as b', 'a.holiday_type', '=', 'b.id')
                        ->select(
                            'a.id',
                            'b.id as holiday_type_id',
                            'a.id as holiday_id',
                            'b.name as holiday_type',
                            'a.name as holiday',
                            'b.rate',
                            'b.absent_with_pay'
                        )
                        ->whereIn('a.branch', [$branch_id[0]->branch_id, 0])
                        ->whereMonth('a.date', '=', $month_id)
                        ->whereDay('a.date', '=', $day_id)
                        ->get();

                    if ($holidays->isNotEmpty()) {
                        $holiday_name = $holidays[0]->holiday;
                        $remarks = $holiday_name; //$remarks == '' ? $holiday_name : $remarks . ', ' . $holiday_name;
                    }

                    // get work cancellation
                    $work_cancellations = DB::table('work_cancellations')
                        ->whereDate('date_from', '<=', $emp_dtr->date)
                        ->whereDate('date_to', '>=', $emp_dtr->date)
                        ->get();

                    if ($work_cancellations->isNotEmpty()) {
                        $is_work_cancellation = true;
                        $work_cancellation_with_pay = $work_cancellations[0]->with_pay;
                        $work_cancellation_reason = $work_cancellations[0]->reason;
                        $wc_time_from = strtotime(date('g:i a', strtotime($work_cancellations[0]->time_from)));
                        $wc_time_to = strtotime(date('g:i a', strtotime($work_cancellations[0]->time_to)));
                        $remarks = $remarks == '' ? 'Work Cancellation: ' . $work_cancellation_reason : $remarks . ', Work Cancellation: ' . $work_cancellation_reason;
                    } else {
                        $is_work_cancellation = false;
                        $work_cancellation_with_pay = false;
                        $work_cancellation_reason = '';
                    }

                    // get leave.
                    $leave = DB::table('leave_headers as a')
                        ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                        ->join('leave_types as c', 'c.id', '=', 'a.leave_type_id')
                        ->select(
                            'a.id',
                            'a.employee_id',
                            'a.leave_type_id',
                            'b.leave_id',
                            'b.with_pay',
                            'b.without_pay',
                            'c.name as leave_type',
                            'b.id as leave_detail_id'
                        )
                        ->where([
                            'leave_date' => $emp_dtr->date,
                            'a.employee_id' => $emp_dtr->employee_id,
                            'a.approved' => true,
                            'a.disapproved' => false,
                            'a.approved_2' => true,
                            'a.disapproved_2' => false
                        ])
                        ->get();

                    $note = '';

                    // auto cancel of leave due to attendance
                    if ($leave->isNotEmpty()) {
                        // check if with attendance
                        if ($holidays->isNotEmpty() == true || $is_work_cancellation == true || $am_in <> false || $am_out <> false || $pm_in <> false || $pm_out <> false || $am_in <> 0 || $am_out <> 0 || $pm_in <> 0 || $pm_out <> 0) {
                            // process auto leave cancellation
                            // get credit balance
                            $credit_balance = DB::table('leave_credits')
                                ->where([
                                    'leave_type_id' => $leave[0]->leave_type_id,
                                    'employee_id' => $leave[0]->employee_id
                                ])
                                ->get();

                            // get total approve credits
                            $credits_return = DB::table('leave_details')
                                ->where('leave_id', $leave[0]->id)
                                ->whereDate('leave_date', $emp_dtr->date)
                                ->sum('with_pay');

                            if ($credits_return <= 0) {
                                $credits_return = 0;
                            }

                            $balance = $credit_balance[0]->credits;

                            if ($balance < 0) {
                                $balance = 0;
                            }

                            $credits = $balance + $credits_return;

                            // return credits with pay to leave credits table
                            DB::table('leave_credits')
                                ->where([
                                    'leave_type_id' => $leave[0]->leave_type_id,
                                    'employee_id' => $leave[0]->employee_id
                                ])
                                ->update(['credits' => $credits]);

                            // check if leave is single application
                            $leave_count = DB::table('leave_headers as a')
                                ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                                ->where('a.id', $leave[0]->id)
                                ->count('b.leave_date');

                            if ($leave_count > 1) {
                                // set leave date to LWOP.
                                DB::table('leave_details')->where('id', $leave[0]->leave_detail_id)->update(['with_pay' => 0, 'without_pay' => $leave[0]->with_pay]);

                                $note = 'Leave on this date set to without pay due to attendance.';
                            } else {
                                // set leave date to LWOP.
                                DB::table('leave_details')->where('id', $leave[0]->leave_detail_id)->update(['with_pay' => 0, 'without_pay' => $leave[0]->with_pay]);

                                // cancel leave application.
                                $process_data = array(
                                    'approved' => false,
                                    'disapproved' => false,
                                    'approved_2' => false,
                                    'disapproved_2' => false,
                                    'processed_date' => null,
                                    'processed_by' => null,
                                    'is_cancel' => true,
                                    'canceled_by' => 0,
                                    'canceled_date' => now(),
                                    'canceled_remarks' => 'Cancelled Leave due to employee with attendance.'
                                );

                                // main leave update for cancel
                                DB::table('leave_headers')->where('id', $leave[0]->id)->update($process_data);

                                $note = 'Leave on this date set to cancelled due to attendance.';
                            }

                            $is_leave_cancelled = true;
                        } else {
                            $is_leave_cancelled = false;
                            $note = '';
                        }
                    }

                    if ($leave->isNotEmpty()) {
                        if ($is_leave_cancelled) {
                            $is_leave = false;
                            $leave_credits = 0;
                            $lwop = 0;
                        } else {
                            $is_leave = true;
                            $leave_credits = $leave[0]->with_pay;
                            $lwop = 0;
                            if ($leave[0]->without_pay > 0) {
                                $lwop = $leave[0]->without_pay;
                                $remarks = $remarks == '' ? $leave[0]->leave_type . ' (LWOP)' : $remarks . ', ' . $leave[0]->leave_type;
                            } else {
                                $remarks = $remarks == '' ? $leave[0]->leave_type : $remarks . ', ' . $leave[0]->leave_type;
                            }
                        }
                    } else {
                        $is_leave = false;
                        $leave_credits = 0;
                        $lwop = 0;
                    }

                    // get overtime.
                    $overtime = DB::table('overtime_applications as a')
                        ->join('overtime_types as b', 'b.id', '=', 'a.overtime_type_id')
                        ->select('a.*', 'b.name as overtime_type')
                        ->where([
                            'employee_id' => $emp_dtr->employee_id,
                            'date' => $emp_dtr->date,
                            'approved' => true,
                            'disapproved' => false,
                            'a.approved_2' => true,
                            'a.disapproved_2' => false
                        ])
                        ->get();

                    if ($overtime->isNotEmpty()) {
                        $is_ot = true;
                        $ot_id = $overtime[0]->id;
                        $ot_type_id = $overtime[0]->overtime_type_id;
                        $ot_amount = $overtime[0]->ot_amount;
                        $nd_amount = $overtime[0]->nd_amount;
                        $ot_hours = $overtime[0]->total_hours;
                        $remarks = $remarks == '' ? $overtime[0]->overtime_type : $remarks . ', ' . $overtime[0]->overtime_type;
                    } else {
                        $is_ot = false;
                        $ot_id = 0;
                        $ot_type_id = 0;
                        $ot_amount = 0;
                        $nd_amount = 0;
                        $ot_hours = 0;
                    }

                    // get ob
                    $ob = DB::table('official_business_applications')
                        ->where([
                            'employee_id' => $emp_dtr->employee_id,
                            'date' => $emp_dtr->date,
                            'approved' => true,
                            'disapproved' => false,
                            'approved_2' => true,
                            'disapproved_2' => false
                        ])
                        ->get();

                    if ($ob->isNotEmpty()) {
                        $is_ob = true;
                        $ob_id = $ob[0]->id;
                        $remarks = $remarks == '' ? 'Official Business' : $remarks . ', Official Business';
                    } else {
                        $is_ob = false;
                        $ob_id = 0;
                    }

                    // only if work cancellation is with pay subtitute pm out to work cancellation.
                    if ($is_work_cancellation == true && $work_cancellation_with_pay == true) {
                        if ($wc_time_from == $am_in_schedule && $wc_time_to == $pm_out_schedule) { // if schedule of work cancellation is whole day match employee schedule.
                            if ($am_in == '' || $am_in == 0) {
                                $am_in = $wc_time_from;
                            }
                            $pm_out = $wc_time_to;
                        } elseif ($wc_time_from >= $pm_in_schedule && $wc_time_to == $pm_out_schedule) { // if schedule cancellation is afternoon.
                            $pm_out = $wc_time_to;
                        } elseif ($wc_time_from <= $am_in_schedule && $wc_time_to == $am_out_schedule) { // if schedule cancellation is morning.
                            if ($am_in == '' || $am_in == 0) {
                                $am_in = $wc_time_from;
                            }
                        } elseif (
                            $wc_time_from <= $am_in_schedule && $wc_time_to <= $pm_out_schedule
                        ) { // if schedule cancellation is half day.
                            if ($am_in == '' || $am_in == 0) {
                                $am_in = $wc_time_from;
                            }
                            $pm_out = $wc_time_to;
                        } elseif ($am_in_schedule > $wc_time_to) { // if schedule is out of range of work cancellation schedule.
                            $am_in = $am_in;
                            $pm_out = $pm_out;
                        } else {
                            $am_in = $wc_time_from;
                            $pm_out = $wc_time_to;
                        }
                    }

                    // get absent
                    $is_halfday = false;
                    if ($is_leave != true && $is_ob != true) {
                        if ($am_in == null && $am_out == null && $pm_in != null && $pm_out != null && $is_rest_day == false) {
                            // am absent
                            $is_halfday = true;
                            $absent = 0.5;
                            $absent_hours = $work_hours / 2;
                            $remarks = $remarks == '' ? 'Half-Day Absent' : $remarks . ', Half-Day Absent';
                        } elseif ($am_in != null && $am_out != null && $pm_in == null && $pm_out == null && $is_rest_day == false) {
                            // pm absent
                            $is_halfday = true;
                            $absent = 0.5;
                            $absent_hours = $work_hours / 2;
                            $remarks = $remarks == '' ? 'Half-Day Absent' : $remarks . ', Half-Day Absent';
                        } elseif ($am_in == null && $am_out == null && $pm_in == null && $pm_out == null && $is_rest_day == false) {
                            // whole day absent
                            $absent = 1;
                            $absent_hours = $work_hours;
                            $remarks = $remarks == '' ? 'Absent' : $remarks . ', Absent';
                        } elseif ($am_in == null && $pm_out == null && $is_rest_day == false) {
                            // whole day absent for incomplete timelogs set absent
                            $absent = 1;
                            $absent_hours = $work_hours;
                            $remarks = $remarks == '' ? 'Absent' : $remarks . ', Absent';
                        } elseif ($am_in == null && $pm_out != null && $is_rest_day == false) {
                            // whole day absent for incomplete timelogs set absent
                            $absent = 1;
                            $absent_hours = $work_hours;
                            $remarks = $remarks == '' ? 'Absent' : $remarks . ', Absent';
                        } elseif ($am_in != null && $pm_out == null && $is_rest_day == false) {
                            // whole day absent for incomplete timelogs set absent
                            $absent = 1;
                            $absent_hours = $work_hours;
                            $remarks = $remarks == '' ? 'Absent' : $remarks . ', Absent';
                        }
                    } else {
                        if ($is_leave == true && $is_ob != true) {
                            if ($leave_credits == 0 || $leave_credits == 0.5) {
                                if ($am_in == null && $am_out == null && $pm_in != null && $pm_out != null && $is_rest_day == false) {
                                    // am absent
                                    $is_halfday = true;
                                    $absent = 0.5;
                                    $absent_hours = $work_hours / 2;
                                    $remarks = $remarks == '' ? 'Half-Day Absent' : $remarks . ', Half-Day Absent';
                                } elseif ($am_in != null && $am_out != null && $pm_in == null && $pm_out == null && $is_rest_day == false) {
                                    // pm absent
                                    $is_halfday = true;
                                    $absent = 0.5;
                                    $absent_hours = $work_hours / 2;
                                    $remarks = $remarks == '' ? 'Half-Day Absent' : $remarks . ', Half-Day Absent';
                                } elseif ($am_in == null && $am_out == null && $pm_in == null && $pm_out == null && $is_rest_day == false) {
                                    // whole day absent
                                    $absent = 1;
                                    $absent_hours = $work_hours;
                                    $remarks = $remarks == '' ? 'Absent' : $remarks . ', Absent';
                                } elseif ($am_in == null && $pm_out == null && $is_rest_day == false) {
                                    // whole day absent for incomplete timelogs set absent
                                    $absent = 1;
                                    $absent_hours = $work_hours;
                                    $remarks = $remarks == '' ? 'Absent' : $remarks . ', Absent';
                                }
                            }
                        } elseif ($is_leave != true && $is_ob == true) {
                            // get absent base on ob.
                            $absent = 0;
                        } else {
                            $absent = 0;
                        }
                    }

                    // check first if grace period or flex. Code will prioritize grace period.
                    // set values to compare.
                    $am_in_schedule_for_tardy = $am_in_schedule;
                    $am_out_schedule_for_tardy = $am_out_schedule;
                    $pm_in_schedule_for_tardy = $pm_in_schedule;
                    $pm_out_schedule_for_tardy = $pm_out_schedule;

                    if (!$is_rest_day) {
                        if ($am_in != null || $am_in != 0) {
                            $actual_late = 0;
                            // get actual late base on actual am in and schedule am in.
                            if ($am_in == null || $am_in == 0) {
                                $actual_late = 0;
                            } else {
                                // get late base in employee am_in.
                                if (is_numeric($am_in_schedule_for_tardy)) {
                                    $actual_late = ((($am_in - $am_in_schedule_for_tardy) / 60) / 60);
                                } else {
                                    $actual_late = ((($am_in - 0) / 60) / 60);
                                }
                            }

                            if ($actual_late > 0) {
                                if ($grace_period > 0 && $flexi_hours > 0) {
                                    // compare actual late to grace.
                                    // set grace period.
                                    if ($grace_period < 1) {
                                        $am_in_schedule_for_tardy = Carbon::parse($am_in_schedule_flex)->addMinutes(($grace_period * 100));
                                        $am_in_schedule_for_tardy = strtotime($am_in_schedule_for_tardy);
                                    } else {
                                        $am_in_schedule_for_tardy = Carbon::parse($am_in_schedule_flex)->addMinutes(($grace_period * 60));
                                        $am_in_schedule_for_tardy = strtotime($am_in_schedule_for_tardy);
                                    }
                                } elseif ($grace_period > 0 && $flexi_hours == 0) {
                                    // compare actual late to grace.
                                    // set grace period.
                                    if ($grace_period < 1) {
                                        $actual_grace_period = ($grace_period * 100);
                                    } else {
                                        $actual_grace_period = $grace_period;
                                    }

                                    if (($actual_late * 60) <= $actual_grace_period) {
                                        if ($grace_period < 1) {
                                            $am_in_schedule_for_tardy = Carbon::parse($am_in_schedule_flex)->addMinutes(($grace_period * 100));
                                            $am_in_schedule_for_tardy = strtotime($am_in_schedule_for_tardy);
                                        } else {
                                            $am_in_schedule_for_tardy = Carbon::parse($am_in_schedule_flex)->addMinutes(($grace_period * 60));
                                            $am_in_schedule_for_tardy = strtotime($am_in_schedule_for_tardy);
                                        }
                                    }
                                } elseif ($grace_period == 0 && $flexi_hours > 0) {
                                    // compare actual late to flexi hours.
                                    if ($actual_late <= $flexi_hours) {
                                        // set flexi hours.
                                        $am_in_schedule_for_tardy = Carbon::parse($am_in_schedule_flex)->addMinutes(($actual_late * 60));
                                        $am_in_schedule_for_tardy = strtotime($am_in_schedule_for_tardy);

                                        // slide pm out schedule base on flexi or actual late if in range to flexi hours.
                                        $pm_out_schedule_for_tardy = Carbon::parse($pm_out_schedule_flex)->addMinutes(($actual_late * 60));
                                        $pm_out_schedule_for_tardy = strtotime($pm_out_schedule_for_tardy);
                                    } else {
                                        // set flexi hours.
                                        $am_in_schedule_for_tardy = Carbon::parse($am_in_schedule_flex)->addMinutes(($flexi_hours * 60));
                                        $am_in_schedule_for_tardy = strtotime($am_in_schedule_for_tardy);

                                        // slide pm out schedule base on flexi or actual late if in range to flexi hours.
                                        $pm_out_schedule_for_tardy = Carbon::parse($pm_out_schedule_flex)->addMinutes(($flexi_hours * 60));
                                        $pm_out_schedule_for_tardy = strtotime($pm_out_schedule_for_tardy);
                                    }
                                }
                            }
                        } else {
                            // This code is for night shift.
                            if ($pm_in != null || $pm_in != '') {
                                $actual_late = 0;
                                // get actual late base on actual am in and schedule am in.
                                if ($pm_in == null) {
                                    $actual_late = 0;
                                } else {
                                    // get late base in employee am_in.
                                    $actual_late = ((($pm_in - $pm_in_schedule) / 60) / 60);
                                }

                                if ($actual_late > 0) {
                                    if ($grace_period > 0 && $flexi_hours > 0) {
                                        // compare actual late to grace.
                                        // set grace period.
                                        if ($grace_period < 1) {
                                            $pm_in_schedule_for_tardy = Carbon::parse($pm_in_schedule_flex)->addMinutes(($grace_period * 100));
                                            $pm_in_schedule_for_tardy = strtotime($pm_in_schedule_for_tardy);
                                        } else {
                                            $pm_in_schedule_for_tardy = Carbon::parse($pm_in_schedule_flex)->addMinutes(($grace_period * 60));
                                            $pm_in_schedule_for_tardy = strtotime($pm_in_schedule_for_tardy);
                                        }
                                    } elseif ($grace_period > 0 && $flexi_hours == 0) {
                                        // compare actual late to grace.
                                        // set grace period.
                                        if ($grace_period < 1) {
                                            $actual_grace_period = ($grace_period * 100);
                                        } else {
                                            $actual_grace_period = $grace_period;
                                        }

                                        if (($actual_late * 60) <= $actual_grace_period) {
                                            if ($grace_period < 1) {
                                                $pm_in_schedule_for_tardy = Carbon::parse($pm_in_schedule_flex)->addMinutes(($grace_period * 100));
                                                $pm_in_schedule_for_tardy = strtotime($pm_in_schedule_for_tardy);
                                            } else {
                                                $pm_in_schedule_for_tardy = Carbon::parse($pm_in_schedule_flex)->addMinutes(($grace_period * 60));
                                                $pm_in_schedule_for_tardy = strtotime($pm_in_schedule_for_tardy);
                                            }
                                        }
                                    } elseif ($grace_period == 0 && $flexi_hours > 0) {
                                        // compare actual late to flexi hours.
                                        if ($actual_late <= $flexi_hours) {
                                            // set flexi hours.
                                            $pm_in_schedule_for_tardy = Carbon::parse($pm_in_schedule_flex)->addMinutes(($actual_late * 60));
                                            $pm_in_schedule_for_tardy = strtotime($pm_in_schedule_for_tardy);

                                            // slide pm out schedule base on flexi or actual late if in range to flexi hours.
                                            $am_out_schedule_for_tardy = Carbon::parse($am_out_schedule_flex)->addMinutes(($actual_late * 60));
                                            $am_out_schedule_for_tardy = strtotime($am_out_schedule_for_tardy);
                                        } else {
                                            $pm_in_schedule_for_tardy = Carbon::parse($pm_in_schedule_flex)->addMinute(($flexi_hours * 60));
                                            $pm_in_schedule_for_tardy = strtotime($pm_in_schedule_for_tardy);

                                            // slide pm out schedule base on flexi or actual late if in range to flexi hours.
                                            $am_out_schedule_for_tardy = Carbon::parse($am_out_schedule_flex)->addMinutes(($flexi_hours * 60));
                                            $am_out_schedule_for_tardy = strtotime($am_out_schedule_for_tardy);
                                        }
                                    }
                                }
                            }
                        }

                        // get late.
                        if ($am_in_schedule == '' || $am_in_schedule == null) {
                            // set late to zero if no schedule or if rest day.
                            $late = 0;
                        } else {
                            if ($am_in == null || $am_in == 0) {
                                if ($pm_in == null || $pm_in == 0) {
                                    $late = 0;
                                } else {
                                    // get late base in employee pm_in.
                                    $late = ((($pm_in - $pm_in_schedule_for_tardy) / 60) / 60);

                                    if ($late > 0 && $is_halfday == false) {
                                        $remarks = $remarks == '' ? 'Late' : $remarks . ', Late';
                                    } elseif ($late > 5 && $is_halfday == true) {
                                        $remarks = $remarks == '' ? 'Late' : $remarks . ', Late';
                                    } else {
                                        $late = 0;
                                    }
                                }
                            } else {
                                // get late base in employee am_in.
                                $late = ((($am_in - $am_in_schedule_for_tardy) / 60) / 60);

                                if ($late > 0 && $is_halfday == false) {
                                    $remarks = $remarks == '' ? 'Late' : $remarks . ', Late';
                                } elseif ($late > 5 && $is_halfday == true) {
                                    $remarks = $remarks == '' ? 'Late' : $remarks . ', Late';
                                } else {
                                    $late = 0;
                                }
                            }
                        }

                        // get undertime.
                        if ($pm_out_schedule == '' || $pm_out_schedule == null) {
                            // set undertime to zero if no schedule or if rest day.
                            $undertime = 0;
                        } else {
                            if ($pm_out == null || $pm_out == 0) {
                                if ($am_out == null || $am_out == 0) {
                                    $undertime = 0;
                                } else {
                                    // get undertime base in employee am_out.
                                    if ($am_out < $am_out_schedule_for_tardy) {
                                        $undertime = ((($am_out_schedule_for_tardy - $am_out) / 60) / 60);
                                    } else {
                                        $undertime = 0;
                                    }

                                    if ($undertime > 0 && $is_halfday == false) {
                                        $remarks = $remarks == '' ? 'Undertime' : $remarks . ', Undertime';
                                    } elseif (
                                        $undertime > 5 && $is_halfday == true
                                    ) {
                                        $remarks = $remarks == '' ? 'Undertime' : $remarks . ', Undertime';
                                    } else {
                                        $undertime = 0;
                                    }
                                }
                            } else {
                                // get undertime base in employee pm_out.
                                if ($pm_out < $pm_out_schedule_for_tardy) {
                                    $undertime = ((($pm_out_schedule_for_tardy - $pm_out) / 60) / 60);
                                } else {
                                    $undertime = 0;
                                }

                                if ($undertime > 0 && $is_halfday == false) {
                                    $remarks = $remarks == '' ? 'Undertime' : $remarks . ', Undertime';
                                } elseif (
                                    $undertime > 5 && $is_halfday == true
                                ) {
                                    $remarks = $remarks == '' ? 'Undertime' : $remarks . ', Undertime';
                                } else {
                                    $undertime = 0;
                                }
                            }
                        }

                        // Compute Nght Differential.
                        if ($absent == 0 && $with_nd == true && $nd_start != '' && $nd_end != '') {
                            $nd_from = Carbon::parse($nd_start);
                            $nd_to = Carbon::parse($nd_end);
                            $nd_to = $nd_to->addDay(1);
                            $nd_setup_hours = ($nd_to->diffInMinutes($nd_from, true) / 60);

                            if ($pm_out >= $nd_end) {

                                $from = Carbon::parse($nd_start);
                                $to = Carbon::parse($pm_out);
                                $to = $to->addDay(1);

                                $nd_hours = ($to->diffInMinutes($from, true) / 60);

                                if ($nd_hours > $nd_setup_hours) {
                                    $from = Carbon::parse($nd_start);
                                    $to = Carbon::parse($nd_end);
                                    $to = $to->addDay(1);

                                    $nd_hours = ($to->diffInMinutes($from, true) / 60);
                                }

                                $nd_pay = ((((($employee_time_data[0]->salary / $tk_days) * 12) / 8) * $nd_rate) * $nd_hours);
                            } elseif ($pm_out < $nd_end) {

                                $from = Carbon::parse($nd_start);
                                $to = Carbon::parse($nd_end);
                                $to = $to->addDay(1);

                                $nd_hours = ($to->diffInMinutes($from, true) / 60);
                                $nd_pay = ((((($employee_time_data[0]->salary / $tk_days) * 12) / 8) * $nd_rate) * $nd_hours);
                            } else {
                                $nd_hours = 0;
                                $nd_pay = 0;
                            }
                        } else {
                            $nd_hours = 0;
                            $nd_pay = 0;
                        }
                    } else {
                        $late = 0;
                        $undertime = 0;
                        $nd_hours = 0;
                        $nd_pay = 0;
                        $absent = 0;
                    }

                    // filter value
                    if ($late < 0) {
                        $late = 0;
                    }

                    if ($undertime < 0) {
                        $undertime = 0;
                    }

                    if ($absent == null) {
                        $absent = 0;
                        $absent_hours = 0;
                    }

                    if ($is_halfday == true && $late > 0) {
                        $late = 0; //($late - 5);
                    }

                    if ($is_halfday == true && $undertime > 0) {
                        $undertime = 0; //($undertime - 5);
                    }

                    // compute actual work hours
                    if ($absent != 0 && $leave_credits == 0) { // if absent no leave applied.
                        if ($absent == 1) {
                            $actual_work_hours = 0;
                        } else {
                            $actual_work_hours = ($work_hours - ($late + $undertime + $absent_hours));
                        }
                    } elseif ($absent != 0 && $leave_credits != 0) { // if absent with applied leave.
                        if ($leave_credits == 1) {
                            $actual_work_hours = $work_hours;
                        } else {
                            $actual_work_hours = ($work_hours - ($late + $undertime + $absent_hours + $leave_credits));
                        }
                    } else {
                        // by defaul if OB no late no undertime.
                        if ($is_ob == true) {
                            $actual_work_hours = $work_hours;
                        } else {
                            $actual_work_hours = ($work_hours - ($late + $undertime));
                        }
                    }

                    if ($holidays->isNotEmpty() && $with_holiday_pay == true) {
                        $holiday_id = $holidays[0]->holiday_id;
                        $holiday_type_id = $holidays[0]->holiday_type_id;
                        $holiday_rate = $holidays[0]->rate;
                        $is_absent_with_pay =  $holidays[0]->absent_with_pay;
                        $absent = $holidays[0]->absent_with_pay == true ? 0 : $absent;
                        $is_holiday = true;

                        // check if absent day before and/or after holiday with pay.
                        $time_data_holiday_check_yesterday = DB::table('time_data')
                            ->where('date', '=', $yesterday)
                            ->where('absent', '>', 0)
                            ->where('leave', '=', 0)
                            ->where('employee_id', $emp_dtr->employee_id)
                            ->count();

                        $time_data_holiday_check_tomorrow = DB::table('time_data')
                            ->where('date', '=', $tomorrow)
                            ->where('absent', '>', 0)
                            ->where('leave', '=', 0)
                            ->where('employee_id', $emp_dtr->employee_id)
                            ->count();

                        if ($time_data_holiday_check_yesterday > 0) {
                            $holiday_pay = 0;
                        } else {
                            // if ($actual_work_hours != 0 && $is_rest_day == false) { //if holiday is with work and not rest day.
                            //     if ($work_hours != 0 || $work_hours != null) {
                            //         $holiday_pay = (($actual_work_hours * $holiday_rate) * ($branch_id[0]->salary / $tk_days / $work_hours));
                            //     } else {
                            //         $holiday_pay = (($actual_work_hours * $holiday_rate) * ($branch_id[0]->salary / $tk_days / $tk_hours));
                            //     }
                            // } else {
                            //     if ($is_absent_with_pay == true) {
                            //         if ($work_hours != 0 || $work_hours != null) {
                            //             $holiday_pay = (($work_hours * $holiday_rate) * ($branch_id[0]->salary / $tk_days / $work_hours));
                            //         } else {
                            //             $holiday_pay = (($tk_hours * $holiday_rate) * ($branch_id[0]->salary / $tk_days / $tk_hours));
                            //         }
                            //     } else {
                            //         $holiday_pay = 0;
                            //     }
                            // }

                            if ($is_absent_with_pay == true) {
                                if ($work_hours != 0 || $work_hours != null) {
                                    $holiday_pay = (($work_hours * $holiday_rate) * ($branch_id[0]->salary / $tk_days / $work_hours));
                                } else {
                                    $holiday_pay = (($tk_hours * $holiday_rate) * ($branch_id[0]->salary / $tk_days / $tk_hours));
                                }
                            } else {
                                $holiday_pay = 0;
                            }

                            if ($actual_work_hours == 0 && $is_rest_day == false) { //if holiday is without work and not rest day and with pay.
                                $actual_work_hours = $is_absent_with_pay == true ? $work_hours : 0;
                            } elseif ($actual_work_hours < $work_hours && $is_rest_day == false) {
                                $actual_work_hours = $is_absent_with_pay == true ? $work_hours : $actual_work_hours;
                            }
                        }
                    } else {
                        $holiday_id = 0;
                        $holiday_type_id = 0;
                        $is_holiday = false;
                        $holiday_pay = 0;
                    }

                    $offset_total = (($late / 8) + ($undertime / 8) + $absent);

                    $excess_hours = 0;

                    if ($absent == 0 && $undertime == 0 && $is_holiday == false && $leave_credits == 0) {
                        if ($is_rest_day == true) {
                            $start = new Carbon($am_in);
                            $end = new Carbon($pm_out);
                        } else {
                            $start = new Carbon($pm_out_schedule);
                            $end = new Carbon($pm_out);

                            if ($flexi_hours > 0 && $actual_late > 0) {
                                $start = new Carbon($pm_out_schedule_for_tardy);
                            }
                        }

                        $excess_hours = $start->diffInHours($end);
                    } else {
                        $excess_hours = 0;
                    }

                    // finally update time data table
                    $time_data = array(
                        'employee_id' => $emp_dtr->employee_id,
                        'payroll_period_id' => $request->payroll_period_id,
                        'date' => $emp_dtr->date,
                        'am_in' => $emp_dtr->am_in,
                        'am_out' => $emp_dtr->am_out,
                        'break_in' => $emp_dtr->break_in,
                        'break_out' => $emp_dtr->break_out,
                        'pm_in' => $emp_dtr->pm_in,
                        'pm_out' => $emp_dtr->pm_out,
                        'work_hours' => $actual_work_hours,
                        'late' => $emp_dtr->applied_offset == 1 ? 0 : $late,
                        'undertime' => $emp_dtr->applied_offset == 1 ? 0 : $undertime,
                        'absent' => $emp_dtr->applied_offset == 1 ? 0 : ($absent - $lwop),
                        'late_offset' => $emp_dtr->applied_offset == 1 ? $emp_dtr->late_offset : $late,
                        'undertime_offset' => $emp_dtr->applied_offset == 1 ? $emp_dtr->undertime_offset : $undertime,
                        'absent_offset' => $emp_dtr->applied_offset == 1 ? $emp_dtr->absent_offset : $absent,
                        'leave' => $emp_dtr->applied_offset == 1 ? $offset_total : $leave_credits,
                        'is_ob' => $is_ob,
                        'ob_id' => $ob_id,
                        'is_holiday' => $is_holiday,
                        'holiday_id' => $holiday_id,
                        'holiday_pay' => $holiday_pay,
                        'is_ot' => $is_ot,
                        'ot_id' => $ot_id,
                        'ot_pay' => $ot_amount,
                        'nd_pay' => $nd_amount,
                        'remarks' => $remarks,
                        'is_shifting' => $emp_dtr->is_shifting,
                        'work_schedule_id' => $emp_dtr->work_schedule_id,
                        'ob_hours' => 0,
                        'ot_hours' => $ot_hours,
                        'holiday_type_id' => $holiday_type_id,
                        'overtime_type_id' => $ot_type_id,
                        'for_approval' => 1,
                        'note' => $note,
                        'excess_hours' => $excess_hours,
                        'nd_start' => $nd_start,
                        'nd_end' => $nd_end,
                        'nd_hours' => $nd_hours,
                        'nd_rate' => $nd_rate,
                        'nd_pay' => $nd_pay,
                        'lwop' => $lwop,
                    );

                    DB::table('time_data')->updateOrInsert(['employee_id' => $emp_dtr->employee_id, 'date' => $emp_dtr->date], $time_data);
                } // end foreach

            } //end for each.

            // Process Employee leave Earned.
            $payroll_period_id = $payroll_period[0]->id;
            $attendance_from = $payroll_period[0]->attendance_start_date;
            $attendance_to = $payroll_period[0]->attendance_end_date;

            // loop Employees for Leave Earned.
            $employee_leave_earned = DB::table('employees as a')
                ->join('time_data as b', 'a.id', '=', 'b.employee_id')
                ->select('a.id')
                ->where('b.payroll_period_id', $payroll_period_id)
                ->distinct()
                ->get();

            foreach ($employee_leave_earned as $employee_earned) {

                $employee_id = $employee_earned->id;

                // get timekeeping and tardiness data.
                $totals = DB::table('time_data as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->select(
                        DB::raw("sum(a.leave) as leave"),
                        DB::raw("sum(a.absent) as absent"),
                        DB::raw("sum(a.late) as late"),
                        DB::raw("sum(a.undertime) as undertime"),
                    )
                    ->where([
                        'a.payroll_period_id' => $payroll_period_id,
                        'b.id' => $employee_id,
                        'b.active' => true,
                        'b.is_employee' => true
                    ])
                    ->groupBy('a.employee_id')
                    ->get();

                $data_check = DB::table('employee_leave_earned')
                    ->where([
                        'employee_id' => $employee_id,
                        'payroll_period_id' => $payroll_period_id
                    ])
                    ->get();

                if ($data_check->isEmpty()) {
                    //Process Leave Accrual start
                    $from = Carbon::parse($attendance_from);
                    $to = Carbon::parse($attendance_to);
                    $calendar_days = $from->diffInDays($to) + 1;
                    $absent = isset($totals[0]->absent) ? $totals[0]->absent : 0;
                    $late = isset($totals[0]->late) ? $totals[0]->late : 0;
                    $undertime = isset($totals[0]->undertime) ? $totals[0]->undertime : 0;
                    $leave = isset($totals[0]->leave) ? $totals[0]->leave : 0;
                    $month_id = $from->month;
                    $year_id = $from->year;

                    if ($absent < 0) {
                        $absent = 0;
                    }

                    if ($late < 0) {
                        $late = 0;
                    }

                    if ($undertime < 0) {
                        $undertime = 0;
                    }

                    if ($leave < 0) {
                        $leave = 0;
                    }

                    $total_present = ($calendar_days - ($late + $undertime + $absent));

                    if ($total_present < 0) {
                        $total_present = 0;
                    }

                    if ($total_present >= 30) {
                        $vl = 1.250;
                        $sl = 1.250;
                    } else {
                        $leave_earned_data = DB::table('leave_earnings')->where('days_present', $total_present)->get();

                        if ($leave_earned_data->isNotEmpty()) {
                            $leave_earned = $leave_earned_data[0]->leave_earned;
                        } else {
                            $leave_earned = 0;
                        }

                        $leave_earned_vl = DB::table('leave_types')->where('id', 16)->get();

                        if ($leave_earned_vl->isNotEmpty()) {
                            $vl = $leave_earned;
                        } else {
                            $vl = 0;
                        }

                        $leave_earned_sl = DB::table('leave_types')->where('id', 3)->get();

                        if ($leave_earned_sl->isNotEmpty()) {
                            $sl = $leave_earned;
                        } else {
                            $sl = 0;
                        }
                    }

                    //VL Earned
                    DB::table('leave_credits')
                        ->where([
                            'leave_type_id' => 16,
                            'employee_id' => $employee_id
                        ])
                        ->update(['credits' => db::raw("credits + $vl")]);

                    //SL Earned
                    DB::table('leave_credits')
                        ->where([
                            'leave_type_id' => 3,
                            'employee_id' => $employee_id
                        ])
                        ->update(['credits' => db::raw("credits + $sl")]);

                    $employee_leave_earned_id = 0;

                    if (
                        $employee_leave_earned_id == null ||
                        $employee_leave_earned_id == 0
                    ) {
                        $employee_leave_earned_id = DB::table('employee_leave_earned')->max('id') + 1;
                    } else {
                        $employee_leave_earned_id = $data_check[0]->id;
                    }

                    $data_leave_earned = array(
                        'employee_id' => $employee_id,
                        'payroll_period_id' => $payroll_period_id,
                        'month_id' => $month_id,
                        'year_id' => $year_id,
                        'vl_earned' => $vl,
                        'sl_earned' => $sl,
                        'absent' => $total_present
                    );

                    DB::unprepared('SET IDENTITY_INSERT employee_leave_earned ON');
                    DB::table('employee_leave_earned')->updateOrInsert(['id' => $employee_leave_earned_id], $data_leave_earned);
                    DB::unprepared('SET IDENTITY_INSERT employee_leave_earned OFF');
                    //Process Leave Accrual end
                } else {
                    $from = Carbon::parse($attendance_from);
                    $to = Carbon::parse($attendance_to);
                    $calendar_days = $from->diffInDays($to) + 1;
                    $absent = isset($totals[0]->absent) ? $totals[0]->absent : 0;
                    $late = isset($totals[0]->late) ? $totals[0]->late : 0;
                    $undertime = isset($totals[0]->undertime) ? $totals[0]->undertime : 0;
                    $leave = isset($totals[0]->leave) ? $totals[0]->leave : 0;
                    $month_id = $from->month;
                    $year_id = $from->year;

                    if ($absent < 0) {
                        $absent = 0;
                    }

                    if ($late < 0) {
                        $late = 0;
                    }

                    if ($undertime < 0) {
                        $undertime = 0;
                    }

                    if ($leave < 0) {
                        $leave = 0;
                    }

                    $total_present = ($calendar_days - ($late + $undertime + $absent));

                    if ($total_present < 0) {
                        $total_present = 0;
                    }

                    if ($total_present >= 30) {
                        $vl = 1.250;
                        $sl = 1.250;
                    } else {
                        $leave_earned_data = DB::table('leave_earnings')->where('days_present', $total_present)->get();

                        if ($leave_earned_data->isNotEmpty()) {
                            $leave_earned = $leave_earned_data[0]->leave_earned;
                        } else {
                            $leave_earned = 0;
                        }

                        $leave_earned_vl = DB::table('leave_types')->where('id', 16)->get();

                        if ($leave_earned_vl->isNotEmpty()) {
                            $vl = $leave_earned;
                        } else {
                            $vl = 0;
                        }

                        $leave_earned_sl = DB::table('leave_types')->where('id', 3)->get();

                        if ($leave_earned_sl->isNotEmpty()) {
                            $sl = $leave_earned;
                        } else {
                            $sl = 0;
                        }

                        $less_vl = $data_check[0]->vl_earned;
                        $less_sl = $data_check[0]->sl_earned;

                        //Less VL Earned
                        DB::table('leave_credits')
                            ->where([
                                'leave_type_id' => 16,
                                'employee_id' => $employee_id
                            ])
                            ->update(['credits' => db::raw("credits - $less_vl")]);

                        //Less SL Earned
                        DB::table('leave_credits')
                            ->where([
                                'leave_type_id' => 3,
                                'employee_id' => $employee_id
                            ])
                            ->update(['credits' => db::raw("credits - $less_sl")]);

                        //VL Earned
                        DB::table('leave_credits')
                            ->where([
                                'leave_type_id' => 16,
                                'employee_id' => $employee_id
                            ])
                            ->update(['credits' => db::raw("credits + $vl")]);

                        //SL Earned
                        DB::table('leave_credits')
                            ->where([
                                'leave_type_id' => 3,
                                'employee_id' => $employee_id
                            ])
                            ->update(['credits' => db::raw("credits + $sl")]);
                    }

                    $employee_leave_earned_id = $data_check[0]->id;

                    if (
                        $employee_leave_earned_id == null ||
                        $employee_leave_earned_id == 0
                    ) {
                        $employee_leave_earned_id = DB::table('employee_leave_earned')->max('id') + 1;
                    } else {
                        $employee_leave_earned_id = $data_check[0]->id;
                    }

                    $data_leave_earned = array(
                        'employee_id' => $employee_id,
                        'payroll_period_id' => $payroll_period_id,
                        'month_id' => $month_id,
                        'year_id' => $year_id,
                        'vl_earned' => $vl,
                        'sl_earned' => $sl,
                        'absent' => $total_present
                    );

                    DB::unprepared('SET IDENTITY_INSERT employee_leave_earned ON');
                    DB::table('employee_leave_earned')->updateOrInsert(['id' => $employee_leave_earned_id], $data_leave_earned);
                    DB::unprepared('SET IDENTITY_INSERT employee_leave_earned OFF');
                }
            } //end for loop.
        } else {
            // redirect back to page.
            return $this->errorResponse('No Employee to process attendance. Please assign schedules to employees first.');
        }

        return $this->successResponse(null, 'Successfully Process Attendance!');
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
                ->leftJoin('payroll_periods as e', 'e.id', '=', 'a.payroll_period_id')
                ->leftJoin('employment_types as f', 'f.id', '=', 'b.employment_type_id')
                ->select(
                    'a.id',
                    'a.payroll_period_id',
                    'a.employee_id',
                    'b.photo',
                    'b.employee_no',
                    'c.name as department',
                    'd.name as position',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                   CONCAT(b.first_name,' ',substring(b.middle_name,1,1),'. ',b.last_name)
                                ELSE
                                    RTRIM(RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](b.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')))
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
                    'a.nd_pay',
                    'a.remarks',
                    'b.is_shifting',
                    'b.work_schedule_id',
                    'a.ot_hours',
                    'e.attendance_start_date',
                    'e.attendance_end_date',
                    'f.name as employment_type',
                    'a.note',
                    'a.late_offset',
                    'a.undertime_offset',
                    'a.absent_offset',
                    'a.applied_offset',
                    'a.excess_hours',
                    'a.nd_start',
                    'a.nd_end',
                    'a.nd_hours',
                    'a.nd_rate',
                    'a.nd_pay'
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
                    DB::raw("sum(a.excess_hours) as excess_hours"),
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
            ], 'Process attendance view data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve process attendance view data: ' . $e->getMessage());
        }
    }

    public function reprocess(Request $request, $id, $payroll_period_id)
    {
        set_time_limit(16000);

        // attendance computation.
        // get data in time data table
        $data = $request->all();

        for ($i = 0; $i < count($data["date"]); $i++) {

            $this->updateNewStepIncrement($id, $data["date"][$i]);

            $salary = DB::table('employees')->select('salary', 'employment_type_id')->where('id', $id)->get();

            $remarks = '';
            $absent = 0;
            $emp_id = $id;

            if ($data["is_shifting"][$i] == true) {
                // get shifting schedule
                $shifting_schedule = DB::table('shift_schedules_details')
                    ->where([
                        'shift_schedule_id' => $data["work_schedule_id"][$i],
                        'shift_date' => $data["date"][$i]
                    ])
                    ->get();

                if ($shifting_schedule->isNotEmpty()) {
                    $am_in_schedule = strtotime($shifting_schedule[0]->am_in);
                    $am_out_schedule = strtotime($shifting_schedule[0]->am_out);
                    $break_in_schedule = strtotime($shifting_schedule[0]->break_in);
                    $break_out_schedule = strtotime($shifting_schedule[0]->break_out);
                    $pm_in_schedule = strtotime($shifting_schedule[0]->pm_in);
                    $pm_out_schedule = strtotime($shifting_schedule[0]->pm_out);
                    $am_in_schedule_flex = $shifting_schedule[0]->am_in;
                    $am_out_schedule_flex = $shifting_schedule[0]->am_out;
                    $break_in_schedule_flex = $shifting_schedule[0]->break_in;
                    $break_out_schedule_flex = $shifting_schedule[0]->break_out;
                    $pm_in_schedule = $shifting_schedule[0]->pm_in;
                    $pm_out_schedule = $shifting_schedule[0]->pm_out;
                    $grace_period = $shifting_schedule[0]->grace_period;
                    $flexi_hours = $shifting_schedule[0]->flexi_hours;
                    $work_hours = $shifting_schedule[0]->work_hours;
                    // get nd setup data
                    $nd_rate = $shifting_schedule[0]->nd_rate;
                    $nd_start = $shifting_schedule[0]->nd_start;
                    $nd_end = $shifting_schedule[0]->nd_end;
                    $with_nd = $shifting_schedule[0]->with_nd;
                } else {
                    $am_in_schedule = '';
                    $am_out_schedule = '';
                    $break_in_schedule = '';
                    $break_out_schedule = '';
                    $pm_in_schedule = '';
                    $pm_out_schedule = '';
                    $grace_period = 0;
                    $flexi_hours = 0;
                    $work_hours = 0;
                    // get nd setup data
                    $nd_rate = 0;
                    $nd_start = '';
                    $nd_end = '';
                    $with_nd = false;
                }
            } else {

                $day_id = Carbon::parse($data["date"][$i])->dayOfWeek;

                // if sunday
                if ($day_id == 0) {
                    $day_id = 7;
                }

                // get fix schedule
                $fix_schedule = DB::table('fix_schedules_details')
                    ->where([
                        'fix_schedule_id' => $data["work_schedule_id"][$i],
                        'day_id' => $day_id
                    ])
                    ->get();

                if ($fix_schedule->isNotEmpty()) {
                    $am_in_schedule = strtotime($fix_schedule[0]->am_in);
                    $am_out_schedule = strtotime($fix_schedule[0]->am_out);
                    $break_in_schedule = strtotime($fix_schedule[0]->break_in);
                    $break_out_schedule = strtotime($fix_schedule[0]->break_out);
                    $pm_in_schedule = strtotime($fix_schedule[0]->pm_in);
                    $pm_out_schedule = strtotime($fix_schedule[0]->pm_out);
                    $am_in_schedule_flex = $fix_schedule[0]->am_in;
                    $am_out_schedule_flex = $fix_schedule[0]->am_out;
                    $break_in_schedule_flex = $fix_schedule[0]->break_in;
                    $break_out_schedule_flex = $fix_schedule[0]->break_out;
                    $pm_in_schedule_flex = $fix_schedule[0]->pm_in;
                    $pm_out_schedule_flex = $fix_schedule[0]->pm_out;
                    $grace_period = $fix_schedule[0]->grace_period;
                    $flexi_hours = $fix_schedule[0]->flexi_hours;
                    $work_hours = $fix_schedule[0]->work_hours;
                    // get nd setup data
                    $nd_rate = $fix_schedule[0]->nd_rate;
                    $nd_start = $fix_schedule[0]->nd_start;
                    $nd_end = $fix_schedule[0]->nd_end;
                    $with_nd = $fix_schedule[0]->with_nd;
                } else {
                    $am_in_schedule = '';
                    $am_out_schedule = '';
                    $break_in_schedule = '';
                    $break_out_schedule = '';
                    $pm_in_schedule = '';
                    $pm_out_schedule = '';
                    $grace_period = 0;
                    $flexi_hours = 0;
                    $work_hours = 0;
                    // get nd setup data
                    $nd_rate = 0;
                    $nd_start = '';
                    $nd_end = '';
                    $with_nd = false;
                }
            }

            // set variables.
            // $am_in = $data["am_in"][$i] == null ? 0 : strtotime(date('g:i a', strtotime($data["am_in"][$i])));
            // $am_out = $data["am_out"][$i] == null ? 0 : strtotime(date('g:i a', strtotime($data["am_out"][$i])));
            // $break_in = $data["break_in"][$i] == null ? 0 : strtotime(date('g:i a', strtotime($data["break_in"][$i])));
            // $break_out = $data["break_out"][$i] == null ? 0 : strtotime(date('g:i a', strtotime($data["break_out"][$i])));
            // $pm_in = $data["pm_in"][$i] == null ? 0 : strtotime(date('g:i a', strtotime($data["pm_in"][$i])));
            // $pm_out = $data["pm_out"][$i] == null ? 0 : strtotime(date('g:i a', strtotime($data["pm_out"][$i])));


            $am_in = strtotime($data["am_in"][$i]);
            $am_out = strtotime($data["am_out"][$i]);
            $break_in = strtotime($data["break_in"][$i]);
            $break_out = strtotime($data["break_out"][$i]);
            $pm_in = strtotime($data["pm_in"][$i]);
            $pm_out = strtotime($data["pm_out"][$i]);

            // $am_in = $data["am_in"][$i];
            // $am_out = $data["am_out"][$i];
            // $break_in = $data["break_in"][$i];
            // $break_out = $data["break_out"][$i];
            // $pm_in = $data["pm_in"][$i];
            // $pm_out = $data["pm_out"][$i];

            // $am_in = Carbon::parse($data["am_in"][$i]);
            // $am_out = Carbon::parse($data["am_out"][$i]);
            // $break_in = Carbon::parse($data["break_in"][$i]);
            // $break_out = Carbon::parse($data["break_out"][$i]);
            // $pm_in = Carbon::parse($data["pm_in"][$i]);
            // $pm_out = Carbon::parse($data["pm_out"][$i]);

            // check if absent or rest day
            if ($am_in_schedule == null && $pm_out_schedule == null || $am_in_schedule == '' && $pm_out_schedule == '') {
                $is_rest_day = true;
                $remarks = 'Rest Day';
                $absent = 0;
            } else {
                $is_rest_day = false;
                $remarks = '';
            }

            // set holiday parameters
            $year_id = Carbon::parse($data["date"][$i])->year;
            $month_id = Carbon::parse($data["date"][$i])->month;
            $day_id = Carbon::parse($data["date"][$i])->day;
            $branch_id = DB::table('employees')
                ->select('branch_id', 'salary', 'employment_type_id')
                ->where('id', $id)->get();

            // get time keeping setup.
            $time_keeping = DB::table('time_keeping_setups')->where('employment_type_id', $branch_id[0]->employment_type_id)->get();

            if ($time_keeping->isNotEmpty()) {
                $tk_days = $time_keeping[0]->work_days;
                $tk_hours = $time_keeping[0]->work_hours;
                $with_holiday_pay = $time_keeping[0]->with_holiday_pay;
            } else {
                $tk_days = 22;
                $tk_hours = 8;
                $with_holiday_pay = false;
            }

            $date = $data["date"][$i];

            $yesterday = date('Y-m-d', strtotime("$date -1 days"));
            $tomorrow = date('Y-m-d', strtotime("$date +1 days"));

            // get holiday.
            $holidays = DB::table('holidays as a')
                ->join('holiday_types as b', 'a.holiday_type', '=', 'b.id')
                ->select(
                    'a.id',
                    'b.id as holiday_type_id',
                    'a.id as holiday_id',
                    'b.name as holiday_type',
                    'a.name as holiday',
                    'b.rate',
                    'b.absent_with_pay'
                )
                ->whereIn('a.branch', [$branch_id[0]->branch_id, 0])
                ->whereMonth('a.date', '=', $month_id)
                ->whereDay('a.date', '=', $day_id)
                ->get();

            if ($holidays->isNotEmpty()) {
                $holiday_name = $holidays[0]->holiday;
                $remarks = $holiday_name; //$remarks == '' ? $holiday_name : $remarks . ', ' . $holiday_name;
            }

            // get work cancellation
            $work_cancellations = DB::table('work_cancellations')
                ->where("date_from", "<=", date("Y-m-d", strtotime($data["date"][$i])))
                ->Where("date_to", ">=", date("Y-m-d", strtotime($data["date"][$i])))
                ->get();

            if ($work_cancellations->isNotEmpty()) {
                $is_work_cancellation = true;
                $work_cancellation_with_pay = $work_cancellations[0]->with_pay;
                $work_cancellation_reason = $work_cancellations[0]->reason;
                $wc_time_from = strtotime(date('g:i a', strtotime($work_cancellations[0]->time_from)));
                $wc_time_to = strtotime(date('g:i a', strtotime($work_cancellations[0]->time_to)));
                $remarks = $remarks == '' ? 'Work Cancellation: ' . $work_cancellation_reason : $remarks . ', Work Cancellation: ' . $work_cancellation_reason;
            } else {
                $is_work_cancellation = false;
                $work_cancellation_with_pay = false;
                $work_cancellation_reason = '';
            }

            // get leave.
            $leave = DB::table('leave_headers as a')
                ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                ->join('leave_types as c', 'c.id', '=', 'a.leave_type_id')
                ->select(
                    'a.id',
                    'a.employee_id',
                    'a.leave_type_id',
                    'b.leave_id',
                    'b.with_pay',
                    'b.without_pay',
                    'c.name as leave_type',
                    'b.id as leave_detail_id'
                )
                ->where([
                    'leave_date' => $data["date"][$i],
                    'a.employee_id' => $id,
                    'a.approved' => true,
                    'a.disapproved' => false,
                    'a.approved_2' => true,
                    'a.disapproved_2' => false
                ])
                ->get();

            $note = $data["note"][$i];

            // auto cancel of leave due to attendance
            if ($leave->isNotEmpty()) {
                // check if with attendance
                if ($holidays->isNotEmpty() == true || $is_work_cancellation == true || $am_in <> false || $am_out <> false || $pm_in <> false || $pm_out <> false || $am_in <> 0 || $am_out <> 0 || $pm_in <> 0 || $pm_out <> 0) {
                    // process auto leave cancellation
                    // get credit balance
                    $credit_balance = DB::table('leave_credits')
                        ->where([
                            'leave_type_id' => $leave[0]->leave_type_id,
                            'employee_id' => $leave[0]->employee_id
                        ])
                        ->get();

                    // get total approve credits
                    $credits_return = DB::table('leave_details')
                        ->where('leave_id', $leave[0]->id)
                        ->whereDate('leave_date', $data["date"][$i])
                        ->sum('with_pay');

                    if ($credits_return <= 0) {
                        $credits_return = 0;
                    }

                    $balance = $credit_balance[0]->credits;

                    if ($balance < 0) {
                        $balance = 0;
                    }

                    $credits = $balance + $credits_return;

                    // return credits with pay to leave credits table
                    DB::table('leave_credits')
                        ->where([
                            'leave_type_id' => $leave[0]->leave_type_id,
                            'employee_id' => $leave[0]->employee_id
                        ])
                        ->update(['credits' => $credits]);

                    // check if leave is single application
                    $leave_count = DB::table('leave_headers as a')
                        ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                        ->where('a.id', $leave[0]->id)
                        ->count('b.leave_date');

                    if ($leave_count > 1) {
                        // set leave date to LWOP.
                        DB::table('leave_details')->where('id', $leave[0]->leave_detail_id)->update(['with_pay' => 0, 'without_pay' => $leave[0]->with_pay]);

                        $note = 'Leave on this date set to without pay due to attendance.';
                    } else {
                        // set leave date to LWOP.
                        DB::table('leave_details')->where('id', $leave[0]->leave_detail_id)->update(['with_pay' => 0, 'without_pay' => $leave[0]->with_pay]);

                        // cancel leave application.
                        $process_data = array(
                            'approved' => false,
                            'disapproved' => false,
                            'approved_2' => false,
                            'disapproved_2' => false,
                            'processed_date' => null,
                            'processed_by' => null,
                            'is_cancel' => true,
                            'canceled_by' => 0,
                            'canceled_date' => now(),
                            'canceled_remarks' => 'Cancelled Leave due to employee with attendance.'
                        );

                        // main leave update for cancel
                        DB::table('leave_headers')->where('id', $leave[0]->id)->update($process_data);

                        $note = 'Leave on this date set to cancelled due to attendance.';
                    }

                    $is_leave_cancelled = true;
                } else {
                    $is_leave_cancelled = false;
                    $note = '';
                }
            }

            if ($leave->isNotEmpty()) {
                if ($is_leave_cancelled) {
                    $is_leave = false;
                    $leave_credits = 0;
                    $lwop = 0;
                } else {
                    $is_leave = true;
                    $leave_credits = $leave[0]->with_pay;
                    $lwop = 0;
                    if ($leave[0]->without_pay > 0) {
                        $lwop = $leave[0]->without_pay;
                        $remarks = $remarks == '' ? $leave[0]->leave_type . ' (LWOP)' : $remarks . ', ' . $leave[0]->leave_type;
                    } else {
                        $remarks = $remarks == '' ? $leave[0]->leave_type : $remarks . ', ' . $leave[0]->leave_type;
                    }
                }
            } else {
                $is_leave = false;
                $leave_credits = 0;
                $lwop = 0;
            }

            // get overtime.
            $overtime = DB::table('overtime_applications as a')
                ->join('overtime_types as b', 'b.id', '=', 'a.overtime_type_id')
                ->select('a.*', 'b.name as overtime_type')
                ->where([
                    'a.employee_id' => $id,
                    'a.date' => $data["date"][$i],
                    'a.approved' => true,
                    'a.disapproved' => false,
                    'a.approved_2' => true,
                    'a.disapproved_2' => false
                ])
                ->get();

            if ($overtime->isNotEmpty()) {
                $is_ot = true;
                $ot_id = $overtime[0]->id;
                $ot_type_id = $overtime[0]->overtime_type_id;
                $ot_amount = $overtime[0]->ot_amount;
                $nd_amount = $overtime[0]->nd_amount;
                $ot_hours = $overtime[0]->total_hours;
                $remarks = $remarks == '' ? $overtime[0]->overtime_type : $remarks . ', ' . $overtime[0]->overtime_type;
            } else {
                $is_ot = false;
                $ot_id = 0;
                $ot_type_id = 0;
                $ot_amount = 0;
                $nd_amount = 0;
                $ot_hours  = 0;
            }

            // get ob
            $ob = DB::table('official_business_applications')
                ->where([
                    'employee_id' => $id,
                    'date' => $data["date"][$i],
                    'approved' => true,
                    'disapproved' => false,
                    'approved_2' => true,
                    'disapproved_2' => false
                ])
                ->get();

            if ($ob->isNotEmpty()) {
                $is_ob = true;
                $ob_id = $ob[0]->id;
                $remarks = $remarks == '' ? 'Official Business' : $remarks . ', Official Business';
            } else {
                $is_ob = false;
                $ob_id = 0;
            }

            // only if work cancellation is with pay subtitute pm out to work cancellation.
            if ($is_work_cancellation == true && $work_cancellation_with_pay == true) {
                if ($wc_time_from == $am_in_schedule && $wc_time_to == $pm_out_schedule) { // if schedule of work cancellation is whole day match employee schedule.
                    if ($am_in == 0) {
                        $am_in = $wc_time_from;
                    }
                    $pm_out = $wc_time_to;
                } elseif ($wc_time_from >= $pm_in_schedule && $wc_time_to == $pm_out_schedule) { // if schedule cancellation is afternoon.
                    $pm_out = $wc_time_to;
                    // } elseif ($wc_time_from <= $am_in_schedule && $wc_time_to == $am_out_schedule) { // if schedule cancellation is morning.
                    //     if ($am_in == 0) {
                    //         $am_in = $wc_time_from;
                    //     }
                } elseif ($wc_time_from <= $am_in_schedule) { // if schedule cancellation is half day and night-shift (schedule-out is on the following day).
                    if ($data["is_shifting"][$i] == true) {
                        if ($shifting_schedule->isNotEmpty()) {
                            $pm_out_schedule_plus = Carbon::parse($shifting_schedule[0]->pm_out)->addDay(1);
                            $pm_out_schedule_plus = strtotime($pm_out_schedule_plus);

                            if ($wc_time_to < $pm_out_schedule_plus) {
                                if ($am_in == 0) {
                                    $am_in = $wc_time_from;
                                }
                                if ($pm_out == 0) {
                                    $pm_out = $wc_time_to;
                                }
                            } else {
                                if ($am_in == 0) {
                                    $am_in = $wc_time_from;
                                }
                            }
                        }
                    } else {
                        if ($fix_schedule->isNotEmpty()) {
                            $pm_out_schedule_plus = Carbon::parse($fix_schedule[0]->pm_out)->addDay(1);
                            $pm_out_schedule_plus = strtotime($pm_out_schedule_plus);

                            if ($wc_time_to < $pm_out_schedule_plus) {
                                if ($am_in == 0) {
                                    $am_in = $wc_time_from;
                                }
                                if ($pm_out == 0) {
                                    $pm_out = $wc_time_to;
                                }

                                // if ($data["date"][$i] == '2024-01-11') {
                                //     dump($wc_time_from);
                                //     dump($wc_time_to);
                                //     dump($pm_out_schedule_plus);

                                //     dump($am_out_schedule);

                                //     dump($data["am_in"][$i]);
                                //     dump($data["am_out"][$i]);

                                //     dump($am_in_schedule);
                                //     dump($pm_out_schedule);

                                //     dump($am_in);
                                //     dd($pm_out);
                                // }
                            } else {
                                if ($am_in == 0) {
                                    $am_in = $wc_time_from;
                                }
                            }
                        }
                    }
                } elseif ($am_in_schedule > $wc_time_to) { // if schedule is out of range of work cancellation schedule.
                    $am_in = $am_in;
                    $pm_out = $pm_out;
                } else {
                    $am_in = $wc_time_from;
                    $pm_out = $wc_time_to;
                }
            }

            // get absent
            $is_halfday = false;

            if ($is_leave != true && $is_ob != true) {
                if (
                    $am_in == null && $am_out == null && $pm_in != null && $pm_out != null && $is_rest_day == false
                ) {
                    // am absent half day
                    $is_halfday = true;
                    $absent = 0.5;
                    $absent_hours = $work_hours / 2;
                    $remarks = $remarks == '' ? 'Half-Day Absent' : $remarks . ', Half-Day Absent';
                } elseif ($am_in != null && $am_out != null && $pm_in == null && $pm_out == null && $is_rest_day == false) {
                    // pm absent half day
                    $is_halfday = true;
                    $absent = 0.5;
                    $absent_hours = $work_hours / 2;
                    $remarks = $remarks == '' ? 'Half-Day Absent' : $remarks . ', Half-Day Absent';
                } elseif ($am_in == null && $am_out == null && $pm_in == null && $pm_out == null && $is_rest_day == false) {
                    // whole day absent
                    $absent = 1;
                    $absent_hours = $work_hours;
                    $remarks = $remarks == '' ? 'Absent' : $remarks . ', Absent';
                } elseif ($am_in == null && $pm_out == null && $is_rest_day == false) {
                    // whole day absent for incomplete timelogs set absent
                    $absent = 1;
                    $absent_hours = $work_hours;
                    $remarks = $remarks == '' ? 'Absent' : $remarks . ', Absent';
                } elseif ($am_in == null && $pm_out != null && $is_rest_day == false) {
                    // whole day absent for incomplete timelogs set absent no am-in
                    $absent = 1;
                    $absent_hours = $work_hours;
                    $remarks = $remarks == '' ? 'Absent' : $remarks . ', Absent';
                } elseif ($am_in != null && $pm_out == null && $is_rest_day == false) {
                    // whole day absent for incomplete timelogs set absent no pm-out
                    $absent = 1;
                    $absent_hours = $work_hours;
                    $remarks = $remarks == '' ? 'Absent' : $remarks . ', Absent';
                }
            } else {
                if ($is_leave == true && $is_ob != true) {
                    if ($leave_credits == 0 || $leave_credits == 0.5) {
                        if ($am_in == null && $am_out == null && $pm_in != null && $pm_out != null && $is_rest_day == false) {
                            // am absent
                            $is_halfday = true;
                            $absent = 0.5;
                            $absent_hours = $work_hours / 2;
                            $remarks = $remarks == '' ? 'Half-Day Absent' : $remarks . ', Half-Day Absent';
                        } elseif ($am_in != null && $am_out != null && $pm_in == null && $pm_out == null && $is_rest_day == false) {
                            // pm absent
                            $is_halfday = true;
                            $absent = 0.5;
                            $absent_hours = $work_hours / 2;
                            $remarks = $remarks == '' ? 'Half-Day Absent' : $remarks . ', Half-Day Absent';
                        } elseif ($am_in == null && $am_out == null && $pm_in == null && $pm_out == null && $is_rest_day == false) {
                            // whole day absent
                            $absent = 1;
                            $absent_hours = $work_hours;
                            $remarks = $remarks == '' ? 'Absent' : $remarks . ', Absent';
                        } elseif ($am_in == null && $pm_out == null && $is_rest_day == false) {
                            // whole day absent for incomplete timelogs set absent
                            $absent = 1;
                            $absent_hours = $work_hours;
                            $remarks = $remarks == '' ? 'Absent' : $remarks . ', Absent';
                        }
                    }
                } elseif ($is_leave != true && $is_ob == true) {
                    // get absent base on ob.
                    $absent = 0;
                } else {
                    $absent = 0;
                }
            }

            // check first if grace period or flex. Code will prioritize grace period.
            // set values to compare.
            $am_in_schedule_for_tardy = $am_in_schedule;
            $am_out_schedule_for_tardy = $am_out_schedule;
            $pm_in_schedule_for_tardy = $pm_in_schedule;
            $pm_out_schedule_for_tardy = $pm_out_schedule;

            // $am_in_schedule_for_tardy = Carbon::parse($am_in_schedule);
            // $am_out_schedule_for_tardy = Carbon::parse($am_out_schedule);
            // $pm_in_schedule_for_tardy = Carbon::parse($pm_in_schedule);
            // $pm_out_schedule_for_tardy = Carbon::parse($pm_out_schedule);

            if (!$is_rest_day) {
                if ($am_in != null || $am_in != 0) {
                    $actual_late = 0;
                    // get actual late base on actual am in and schedule am in.
                    if ($am_in == null || $am_in == 0) {
                        $actual_late = 0;
                    } else {
                        // get late base in employee am_in.
                        if (is_numeric($am_in_schedule_for_tardy)) {
                            $actual_late = ((($am_in - $am_in_schedule_for_tardy) / 60) / 60);
                        } else {
                            $actual_late = ((($am_in - 0) / 60) / 60);
                        }
                    }

                    if ($actual_late > 0) {
                        if ($grace_period > 0 && $flexi_hours > 0) {
                            // compare actual late to grace.
                            // set grace period.
                            if ($grace_period < 1) {
                                $am_in_schedule_for_tardy = Carbon::parse($am_in_schedule_flex)->addMinutes(($grace_period * 100));
                                $am_in_schedule_for_tardy = strtotime($am_in_schedule_for_tardy);
                            } else {
                                $am_in_schedule_for_tardy = Carbon::parse($am_in_schedule_flex)->addMinutes(($grace_period * 60));
                                $am_in_schedule_for_tardy = strtotime($am_in_schedule_for_tardy);
                            }
                        } elseif ($grace_period > 0 && $flexi_hours == 0) {
                            // compare actual late to grace.
                            // set grace period.
                            if ($grace_period < 1) {
                                $actual_grace_period = ($grace_period * 100);
                            } else {
                                $actual_grace_period = $grace_period;
                            }

                            if (($actual_late * 60) <= $actual_grace_period) {
                                if ($grace_period < 1) {
                                    $am_in_schedule_for_tardy = Carbon::parse($am_in_schedule_flex)->addMinutes(($grace_period * 100));
                                    $am_in_schedule_for_tardy = strtotime($am_in_schedule_for_tardy);
                                } else {
                                    $am_in_schedule_for_tardy = Carbon::parse($am_in_schedule_flex)->addMinutes(($grace_period * 60));
                                    $am_in_schedule_for_tardy = strtotime($am_in_schedule_for_tardy);
                                }
                            }
                        } elseif ($grace_period == 0 && $flexi_hours > 0) {
                            // compare actual late to flexi hours.
                            if ($actual_late <= $flexi_hours) {
                                // set flexi hours.
                                $am_in_schedule_for_tardy = Carbon::parse($am_in_schedule_flex)->addMinutes(($actual_late * 60));
                                $am_in_schedule_for_tardy = strtotime($am_in_schedule_for_tardy);

                                // slide pm out schedule base on flexi or actual late if in range to flexi hours.
                                $pm_out_schedule_for_tardy = Carbon::parse($pm_out_schedule_flex)->addMinutes(($actual_late * 60));
                                $pm_out_schedule_for_tardy = strtotime($pm_out_schedule_for_tardy);
                            } else {
                                // set flexi hours.
                                $am_in_schedule_for_tardy = Carbon::parse($am_in_schedule_flex)->addMinutes(($flexi_hours * 60));
                                $am_in_schedule_for_tardy = strtotime($am_in_schedule_for_tardy);

                                // slide pm out schedule base on flexi or actual late if in range to flexi hours.
                                $pm_out_schedule_for_tardy = Carbon::parse($pm_out_schedule_flex)->addMinutes(($flexi_hours * 60));
                                $pm_out_schedule_for_tardy = strtotime($pm_out_schedule_for_tardy);
                            }
                        }
                    }
                } else {
                    // This code is for night shift.
                    if ($pm_in != null || $pm_in != '') {
                        $actual_late = 0;
                        // get actual late base on actual am in and schedule am in.
                        if ($pm_in == null) {
                            $actual_late = 0;
                        } else {
                            // get late base in employee am_in.
                            $actual_late = ((($pm_in - $pm_in_schedule) / 60) / 60);
                        }

                        if ($actual_late > 0) {
                            if ($grace_period > 0 && $flexi_hours > 0) {
                                // compare actual late to grace.
                                // set grace period.
                                if ($grace_period < 1) {
                                    $pm_in_schedule_for_tardy = Carbon::parse($pm_in_schedule_flex)->addMinutes(($grace_period * 100));
                                    $pm_in_schedule_for_tardy = strtotime($pm_in_schedule_for_tardy);
                                } else {
                                    $pm_in_schedule_for_tardy = Carbon::parse($pm_in_schedule_flex)->addMinutes(($grace_period * 60));
                                    $pm_in_schedule_for_tardy = strtotime($pm_in_schedule_for_tardy);
                                }
                            } elseif ($grace_period > 0 && $flexi_hours == 0) {
                                // compare actual late to grace.
                                // set grace period.
                                if ($grace_period < 1) {
                                    $actual_grace_period = ($grace_period * 100);
                                } else {
                                    $actual_grace_period = $grace_period;
                                }

                                if (($actual_late * 60) <= $actual_grace_period) {
                                    if ($grace_period < 1) {
                                        $pm_in_schedule_for_tardy = Carbon::parse($pm_in_schedule_flex)->addMinutes(($grace_period * 100));
                                        $pm_in_schedule_for_tardy = strtotime($pm_in_schedule_for_tardy);
                                    } else {
                                        $pm_in_schedule_for_tardy = Carbon::parse($pm_in_schedule_flex)->addMinutes(($grace_period * 60));
                                        $pm_in_schedule_for_tardy = strtotime($pm_in_schedule_for_tardy);
                                    }
                                }
                            } elseif ($grace_period == 0 && $flexi_hours > 0) {
                                // compare actual late to flexi hours.
                                if ($actual_late <= $flexi_hours) {
                                    // set flexi hours.
                                    $pm_in_schedule_for_tardy = Carbon::parse($pm_in_schedule_flex)->addMinutes(($actual_late * 60));
                                    $pm_in_schedule_for_tardy = strtotime($pm_in_schedule_for_tardy);

                                    // slide pm out schedule base on flexi or actual late if in range to flexi hours.
                                    $am_out_schedule_for_tardy = Carbon::parse($am_out_schedule_flex)->addMinutes(($actual_late * 60));
                                    $am_out_schedule_for_tardy = strtotime($am_out_schedule_for_tardy);
                                } else {
                                    $pm_in_schedule_for_tardy = Carbon::parse($pm_in_schedule_flex)->addMinute(($flexi_hours * 60));
                                    $pm_in_schedule_for_tardy = strtotime($pm_in_schedule_for_tardy);

                                    // slide pm out schedule base on flexi or actual late if in range to flexi hours.
                                    $am_out_schedule_for_tardy = Carbon::parse($am_out_schedule_flex)->addMinutes(($flexi_hours * 60));
                                    $am_out_schedule_for_tardy = strtotime($am_out_schedule_for_tardy);
                                }
                            }
                        }
                    }
                }

                // get late.
                if ($am_in_schedule == '' || $am_in_schedule == null) {
                    // set late to zero if no schedule or if rest day.
                    $late = 0;
                } else {
                    if ($am_in == null || $am_in == 0) {
                        if ($pm_in == null || $pm_in == 0) {
                            $late = 0;
                        } else {
                            // get late base in employee pm_in.
                            $late = ((($pm_in - $pm_in_schedule_for_tardy) / 60) / 60);

                            if ($late > 0 && $is_halfday == false) {
                                $remarks = $remarks == '' ? 'Late' : $remarks . ', Late';
                            } elseif ($late > 5 && $is_halfday == true) {
                                $remarks = $remarks == '' ? 'Late' : $remarks . ', Late';
                            } else {
                                $late = 0;
                            }
                        }
                    } else {
                        // get late base in employee am_in.
                        $late = ((($am_in - $am_in_schedule_for_tardy) / 60) / 60);

                        if ($late > 0 && $is_halfday == false) {
                            $remarks = $remarks == '' ? 'Late' : $remarks . ', Late';
                        } elseif ($late > 5 && $is_halfday == true) {
                            $remarks = $remarks == '' ? 'Late' : $remarks . ', Late';
                        } else {
                            $late = 0;
                        }
                    }
                }

                // get undertime.
                if ($pm_out_schedule == '' || $pm_out_schedule == null) {
                    // set undertime to zero if no schedule or if rest day.
                    $undertime = 0;
                } else {
                    if ($pm_out == null || $pm_out == 0) {
                        if ($am_out == null || $am_out == 0) {
                            $undertime = 0;
                        } else {
                            // get undertime base in employee am_out.
                            if ($am_out < $am_out_schedule_for_tardy) {
                                $undertime = ((($am_out_schedule_for_tardy - $am_out) / 60) / 60);
                            } else {
                                $undertime = 0;
                            }

                            if ($undertime > 0 && $is_halfday == false) {
                                $remarks = $remarks == '' ? 'Undertime' : $remarks . ', Undertime';
                            } elseif (
                                $undertime > 5 && $is_halfday == true
                            ) {
                                $remarks = $remarks == '' ? 'Undertime' : $remarks . ', Undertime';
                            } else {
                                $undertime = 0;
                            }
                        }
                    } else {
                        // get undertime base in employee pm_out.
                        if ($pm_out < $pm_out_schedule_for_tardy) {
                            $undertime = ((($pm_out_schedule_for_tardy - $pm_out) / 60) / 60);
                        } else {
                            $undertime = 0;
                        }

                        if ($undertime > 0 && $is_halfday == false) {
                            $remarks = $remarks == '' ? 'Undertime' : $remarks . ', Undertime';
                        } elseif (
                            $undertime > 5 && $is_halfday == true
                        ) {
                            $remarks = $remarks == '' ? 'Undertime' : $remarks . ', Undertime';
                        } else {
                            $undertime = 0;
                        }
                    }
                }

                // Compute Nght Differential.
                if ($absent == 0 && $with_nd == true && $nd_start != '' && $nd_end != '') {
                    $nd_from = Carbon::parse($nd_start);
                    $nd_to = Carbon::parse($nd_end);
                    $nd_to = $nd_to->addDay(1);
                    $nd_setup_hours = ($nd_to->diffInMinutes($nd_from, true) / 60);

                    if ($pm_out >= $nd_end) {

                        $from = Carbon::parse($nd_start);
                        $to = Carbon::parse($pm_out);
                        $to = $to->addDay(1);

                        $nd_hours = ($to->diffInMinutes($from, true) / 60);

                        if ($nd_hours > $nd_setup_hours) {
                            $from = Carbon::parse($nd_start);
                            $to = Carbon::parse($nd_end);
                            $to = $to->addDay(1);

                            $nd_hours = ($to->diffInMinutes($from, true) / 60);
                        }

                        $nd_pay = ((((($salary[0]->salary / $tk_days) * 12) / 8) * $nd_rate) * $nd_hours);
                    } elseif ($pm_out < $nd_end) {

                        $from = Carbon::parse($nd_start);
                        $to = Carbon::parse($nd_end);
                        $to = $to->addDay(1);

                        $nd_hours = ($to->diffInMinutes($from, true) / 60);
                        $nd_pay = ((((($salary[0]->salary / $tk_days) * 12) / 8) * $nd_rate) * $nd_hours);
                    } else {
                        $nd_hours = 0;
                        $nd_pay = 0;
                    }
                } else {
                    $nd_hours = 0;
                    $nd_pay = 0;
                }
            } else {
                $late = 0;
                $undertime = 0;
                $nd_hours = 0;
                $nd_pay = 0;
                $absent = 0;
            }

            // filter value
            if ($late < 0) {
                $late = 0;
            }

            if ($undertime < 0) {
                $undertime = 0;
            }

            if ($absent == null) {
                $absent = 0;
                $absent_hours = 0;
            }

            if ($is_halfday == true && $late > 0) {
                $late = 0; //($late - 0.5);
            }

            if ($is_halfday == true && $undertime > 0) {
                $undertime = 0; //($undertime - 5);
            }

            // compute actual work hours
            if ($absent != 0 && $leave_credits == 0) { // if absent no leave applied.
                if ($absent == 1) {
                    $actual_work_hours = 0;
                } else {
                    $actual_work_hours = ($work_hours - ($late + $undertime + $absent_hours));
                }
            } elseif (
                $absent != 0 && $leave_credits != 0
            ) { // if absent with applied leave.
                if ($leave_credits == 1) {
                    $actual_work_hours = $work_hours;
                } else {
                    $actual_work_hours = ($work_hours - ($late + $undertime + $absent_hours + $leave_credits));
                }
            } else {
                // by defaul if OB no late no undertime.
                if ($is_ob == true) {
                    $actual_work_hours = $work_hours;
                } else {
                    $actual_work_hours = ($work_hours - ($late + $undertime));
                }
            }

            if ($holidays->isNotEmpty() && $with_holiday_pay == true) {
                $holiday_id = $holidays[0]->holiday_id;
                $holiday_type_id = $holidays[0]->holiday_type_id;
                $holiday_rate = $holidays[0]->rate;
                $is_absent_with_pay =  $holidays[0]->absent_with_pay;
                $absent = $holidays[0]->absent_with_pay == true ? 0 : $absent;
                $is_holiday = true;

                // check if absent day before and/or after holiday with pay.
                $time_data_holiday_check_yesterday = DB::table('time_data')
                    ->where('date', '=', $yesterday)
                    ->where('absent', '>', 0)
                    ->where('leave', '=', 0)
                    ->where('employee_id', $id)
                    ->count();

                $time_data_holiday_check_tomorrow = DB::table('time_data')
                    ->where('date', '=', $tomorrow)
                    ->where('absent', '>', 0)
                    ->where('leave', '=', 0)
                    ->where('employee_id', $id)
                    ->count();

                if ($time_data_holiday_check_yesterday > 0) {
                    $holiday_pay = 0;
                } else {
                    if ($actual_work_hours != 0 && $is_rest_day == false) { //if holiday is with work and not rest day.
                        if ($work_hours != 0 || $work_hours != null) {
                            $holiday_pay = (($actual_work_hours * $holiday_rate) * ($branch_id[0]->salary / $tk_days / $work_hours));
                        } else {
                            $holiday_pay = (($actual_work_hours * $holiday_rate) * ($branch_id[0]->salary / $tk_days / $tk_hours));
                        }
                    } else {
                        if ($is_absent_with_pay == true) {
                            if ($work_hours != 0 || $work_hours != null) {
                                $holiday_pay = (($work_hours * $holiday_rate) * ($branch_id[0]->salary / $tk_days / $work_hours));
                            } else {
                                $holiday_pay = (($tk_hours * $holiday_rate) * ($branch_id[0]->salary / $tk_days / $tk_hours));
                            }
                        } else {
                            $holiday_pay = 0;
                        }
                    }

                    if ($actual_work_hours == 0 && $is_rest_day == false) { //if holiday is without work and not rest day and with pay.
                        $actual_work_hours = $is_absent_with_pay == true ? $work_hours : 0;
                    } elseif ($actual_work_hours < $work_hours && $is_rest_day == false) {
                        $actual_work_hours = $is_absent_with_pay == true ? $work_hours : $actual_work_hours;
                    }
                }
            } else {
                $holiday_id = 0;
                $holiday_type_id = 0;
                $is_holiday = false;
                $holiday_pay = 0;
            }

            $offset_total = (($late / 8) + ($undertime / 8) + $absent);

            $excess_hours = 0;

            if ($absent == 0 && $undertime == 0 && $is_holiday == false && $leave_credits == 0) {

                if ($is_rest_day == true) {
                    $start = new Carbon($am_in);
                    $end = new Carbon($pm_out);
                } else {
                    $start = new Carbon($pm_out_schedule);
                    $end = new Carbon($pm_out);

                    if ($flexi_hours > 0 && $actual_late > 0) {
                        $start = new Carbon($pm_out_schedule_for_tardy);
                    }
                }

                $excess_hours = $start->diffInHours($end);
            } else {
                $excess_hours = 0;
            }

            // finally update time data table
            $time_data = array(
                'employee_id' => $id,
                'payroll_period_id' => $payroll_period_id,
                'date' => $data["date"][$i],
                'am_in' => $data["am_in"][$i],
                'am_out' => $data["am_out"][$i],
                'break_in' => $data["break_in"][$i],
                'break_out' => $data["break_out"][$i],
                'pm_in' => $data["pm_in"][$i],
                'pm_out' => $data["pm_out"][$i],
                'work_hours' => $actual_work_hours,
                'late' => $data["applied_offset"][$i] == 1 ? 0 : $late,
                'undertime' => $data["applied_offset"][$i] == 1 ? 0 : $undertime,
                'absent' => $data["applied_offset"][$i] == 1 ? 0 : ($absent - $lwop),
                'late_offset' => $data["applied_offset"][$i] == 1 ? $data["late_offset"][$i] : $late,
                'undertime_offset' => $data["applied_offset"][$i] == 1 ? $data["undertime_offset"][$i] : $undertime,
                'absent_offset' => $data["applied_offset"][$i] == 1 ? $data["absent_offset"][$i] : $absent,
                'leave' => $data["applied_offset"][$i] == 1 ? $offset_total : $leave_credits,
                'is_ob' => $is_ob,
                'ob_id' => $ob_id,
                'is_holiday' => $is_holiday,
                'holiday_id' => $holiday_id,
                'holiday_pay' => $holiday_pay,
                'is_ot' => $is_ot,
                'ot_id' => $ot_id,
                'ot_pay' => $ot_amount,
                'nd_pay' => $nd_amount,
                'remarks' => $remarks,
                'ob_hours' => 0,
                'ot_hours' => $ot_hours,
                'holiday_type_id' => $holiday_type_id,
                'overtime_type_id' => $ot_type_id,
                'note' => $note,
                'excess_hours' => $excess_hours,
                'nd_start' => $nd_start,
                'nd_end' => $nd_end,
                'nd_hours' => $nd_hours,
                'nd_rate' => $nd_rate,
                'nd_pay' => $nd_pay,
                'lwop' => $lwop,
            );

            DB::table('time_data')->updateOrInsert(['employee_id' => $id, 'date' => $data["date"][$i]], $time_data);
        } // end foreach

        // dd('test');

        // Process Employee leave Earned.
        $payroll_period = DB::table('payroll_periods')->where('id', $payroll_period_id)->get();

        if ($payroll_period->isEmpty()) {
            $payroll_period_id = 0;
            $attendance_from = null;
            $attendance_to = null;
        } else {
            $payroll_period_id = $payroll_period[0]->id;
            $attendance_from = $payroll_period[0]->attendance_start_date;
            $attendance_to = $payroll_period[0]->attendance_end_date;
        }

        if ($payroll_period_id > 0) {
            // loop Employees for Leave Earned.
            $employee_leave_earned = DB::table('employees as a')
                ->join('time_data as b', 'a.id', '=', 'b.employee_id')
                ->select('a.id')
                ->where('b.payroll_period_id', $payroll_period_id)
                ->distinct()
                ->get();

            foreach ($employee_leave_earned as $employee_earned) {

                $employee_id = $employee_earned->id;

                // get timekeeping and tardiness data.
                $totals = DB::table('time_data as a')
                    ->join('employees as b', 'a.employee_id', '=', 'b.id')
                    ->select(
                        DB::raw("sum(a.leave) as leave"),
                        DB::raw("sum(a.absent) as absent"),
                        DB::raw("sum(a.late) as late"),
                        DB::raw("sum(a.undertime) as undertime"),
                    )
                    ->where([
                        'a.payroll_period_id' => $payroll_period_id,
                        'b.id' => $employee_id,
                        'b.active' => true,
                        'b.is_employee' => true
                    ])
                    ->groupBy('a.employee_id')
                    ->get();

                $data_check = DB::table('employee_leave_earned')
                    ->where([
                        'employee_id' => $employee_id,
                        'payroll_period_id' => $payroll_period_id
                    ])
                    ->get();

                if ($data_check->isEmpty()) {
                    //Process Leave Accrual start
                    $from = Carbon::parse($attendance_from);
                    $to = Carbon::parse($attendance_to);
                    $calendar_days = $from->diffInDays($to) + 1;
                    $absent = isset($totals[0]->absent) ? $totals[0]->absent : 0;
                    $late = isset($totals[0]->late) ? $totals[0]->late : 0;
                    $undertime = isset($totals[0]->undertime) ? $totals[0]->undertime : 0;
                    $leave = isset($totals[0]->leave) ? $totals[0]->leave : 0;
                    $month_id = $from->month;
                    $year_id = $from->year;

                    $total_present = ($calendar_days - ($late + $undertime + $absent));

                    if ($total_present > 30) {
                        $vl = 1.250;
                        $sl = 1.250;
                    } else {
                        $leave_earned_data = DB::table('leave_earnings')->where('days_present', $total_present)->get();

                        if ($leave_earned_data->isNotEmpty()) {
                            $leave_earned = $leave_earned_data[0]->leave_earned;
                        } else {
                            $leave_earned = 0;
                        }

                        $leave_earned_vl = DB::table('leave_types')->where('id', 16)->get();

                        if ($leave_earned_vl->isNotEmpty()) {
                            $vl = $leave_earned;
                        } else {
                            $vl = 0;
                        }

                        $leave_earned_sl = DB::table('leave_types')->where('id', 3)->get();

                        if ($leave_earned_sl->isNotEmpty()) {
                            $sl = $leave_earned;
                        } else {
                            $sl = 0;
                        }
                    }

                    //VL Earned
                    DB::table('leave_credits')
                        ->where([
                            'leave_type_id' => 16,
                            'employee_id' => $employee_id
                        ])
                        ->update(['credits' => db::raw("credits + $vl")]);

                    //SL Earned
                    DB::table('leave_credits')
                        ->where([
                            'leave_type_id' => 3,
                            'employee_id' => $employee_id
                        ])
                        ->update(['credits' => db::raw("credits + $sl")]);

                    $employee_leave_earned_id = 0;

                    if (
                        $employee_leave_earned_id == null ||
                        $employee_leave_earned_id == 0
                    ) {
                        $employee_leave_earned_id = DB::table('employee_leave_earned')->max('id') + 1;
                    } else {
                        $employee_leave_earned_id = $data_check[0]->id;
                    }

                    $data_leave_earned = array(
                        'employee_id' => $employee_id,
                        'payroll_period_id' => $payroll_period_id,
                        'month_id' => $month_id,
                        'year_id' => $year_id,
                        'vl_earned' => $vl,
                        'sl_earned' => $sl,
                        'absent' => $total_present
                    );

                    DB::unprepared('SET IDENTITY_INSERT employee_leave_earned ON');
                    DB::table('employee_leave_earned')->updateOrInsert(['id' => $employee_leave_earned_id], $data_leave_earned);
                    DB::unprepared('SET IDENTITY_INSERT employee_leave_earned OFF');
                    //Process Leave Accrual end
                } else {
                    $from = Carbon::parse($attendance_from);
                    $to = Carbon::parse($attendance_to);
                    $calendar_days = $from->diffInDays($to) + 1;
                    $absent = isset($totals[0]->absent) ? $totals[0]->absent : 0;
                    $late = isset($totals[0]->late) ? $totals[0]->late : 0;
                    $undertime = isset($totals[0]->undertime) ? $totals[0]->undertime : 0;
                    $leave = isset($totals[0]->leave) ? $totals[0]->leave : 0;
                    $month_id = $from->month;
                    $year_id = $from->year;

                    $total_present = ($calendar_days - ($late + $undertime + $absent));

                    if ($total_present > 30) {
                        $vl = 1.250;
                        $sl = 1.250;
                    } else {
                        $leave_earned_data = DB::table('leave_earnings')->where('days_present', $total_present)->get();

                        if ($leave_earned_data->isNotEmpty()) {
                            $leave_earned = $leave_earned_data[0]->leave_earned;
                        } else {
                            $leave_earned = 0;
                        }

                        $leave_earned_vl = DB::table('leave_types')->where('id', 16)->get();

                        if ($leave_earned_vl->isNotEmpty()) {
                            $vl = $leave_earned;
                        } else {
                            $vl = 0;
                        }

                        $leave_earned_sl = DB::table('leave_types')->where('id', 3)->get();

                        if ($leave_earned_sl->isNotEmpty()) {
                            $sl = $leave_earned;
                        } else {
                            $sl = 0;
                        }

                        $less_vl = $data_check[0]->vl_earned;
                        $less_sl = $data_check[0]->sl_earned;

                        //Less VL Earned
                        DB::table('leave_credits')
                            ->where([
                                'leave_type_id' => 16,
                                'employee_id' => $employee_id
                            ])
                            ->update(['credits' => db::raw("credits - $less_vl")]);

                        //Less SL Earned
                        DB::table('leave_credits')
                            ->where([
                                'leave_type_id' => 3,
                                'employee_id' => $employee_id
                            ])
                            ->update(['credits' => db::raw("credits - $less_sl")]);

                        //VL Earned
                        DB::table('leave_credits')
                            ->where([
                                'leave_type_id' => 16,
                                'employee_id' => $employee_id
                            ])
                            ->update(['credits' => db::raw("credits + $vl")]);

                        //SL Earned
                        DB::table('leave_credits')
                            ->where([
                                'leave_type_id' => 3,
                                'employee_id' => $employee_id
                            ])
                            ->update(['credits' => db::raw("credits + $sl")]);
                    }

                    $employee_leave_earned_id = $data_check[0]->id;

                    if (
                        $employee_leave_earned_id == null ||
                        $employee_leave_earned_id == 0
                    ) {
                        $employee_leave_earned_id = DB::table('employee_leave_earned')->max('id') + 1;
                    } else {
                        $employee_leave_earned_id = $data_check[0]->id;
                    }

                    $data_leave_earned = array(
                        'employee_id' => $employee_id,
                        'payroll_period_id' => $payroll_period_id,
                        'month_id' => $month_id,
                        'year_id' => $year_id,
                        'vl_earned' => $vl,
                        'sl_earned' => $sl,
                        'absent' => $total_present
                    );

                    DB::unprepared('SET IDENTITY_INSERT employee_leave_earned ON');
                    DB::table('employee_leave_earned')->updateOrInsert(['id' => $employee_leave_earned_id], $data_leave_earned);
                    DB::unprepared('SET IDENTITY_INSERT employee_leave_earned OFF');
                }
            } //end for loop.
        }

        return $this->successResponse(null, 'Successfully Process Attendance!');
    }

    public function print($employee_id, $payroll_period_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $companies = DB::table('companies')->get();

        if ($employee_id == 0) {
            $time_data_absent = DB::table('time_data as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    DB::raw("SUM(isnull(a.absent,0)) as days_absent")
                )
                ->where([
                    'a.employee_id' => $employee_id,
                    'a.payroll_period_id' => $payroll_period_id
                ])
                ->groupBy(
                    'a.employee_id'
                )
                ->get();
            $time_data = DB::table('time_data as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->select(
                    'a.*',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',b.last_name)
                           ELSE
                               RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                           END as name"),
                )
                ->where([
                    'a.payroll_period_id' => $payroll_period_id
                ])
                ->orderBy('a.employee_id', 'asc')
                ->orderBy('a.date', 'asc')
                ->get();


            $employees = DB::table('time_data as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('departments as c', 'b.department_id', '=', 'c.id')
                ->join('positions as d', 'b.position_id', '=', 'd.id')
                ->select(
                    'b.id as employee_id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',b.last_name)
                           ELSE
                               RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                           END as name"),
                    'b.employee_no',
                    'c.name as department',
                    'd.name as position'
                )
                ->distinct()
                ->where('a.payroll_period_id', $payroll_period_id)
                ->orderby('b.id', 'asc')
                ->get();
        } else {
            $time_data_absent = DB::table('time_data as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->select(
                    'a.employee_id',
                    DB::raw("SUM(isnull(a.absent,0)) as days_absent")
                )
                ->where([
                    'a.employee_id' => $employee_id,
                    'a.payroll_period_id' => $payroll_period_id
                ])
                ->groupBy(
                    'a.employee_id'
                )
                ->get();
            $time_data = DB::table('time_data as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->select(
                    'a.*',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',b.last_name)
                           ELSE
                               RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                           END as name"),
                )
                ->where([
                    'a.employee_id' => $employee_id,
                    'a.payroll_period_id' => $payroll_period_id
                ])
                ->orderBy('a.employee_id', 'asc')
                ->orderBy('a.date', 'asc')
                ->get();

            $employees = DB::table('time_data as a')
                ->join('employees as b', 'a.employee_id', '=', 'b.id')
                ->join('departments as c', 'b.department_id', '=', 'c.id')
                ->join('positions as d', 'b.position_id', '=', 'd.id')
                ->leftJoin('branches as br', 'br.id', '=', 'b.branch_id')
                ->select(
                    'b.id as employee_id',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                               CONCAT(b.first_name,' ',b.last_name)
                           ELSE
                               RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                           END as name"),
                    'b.employee_no',
                    'c.name as department',
                    'd.name as position',
                    DB::raw("CASE WHEN ISNULL(b.branch_id,0) <> 0 THEN
                              CASE WHEN br.is_main_branch = 1 THEN
                                    CAST(1 as INT)
                                   ELSE
                                    CAST(0 as INT)
                               END
                              ELSE
                               CAST(2 AS INT)
                         END AS from_branch")
                )
                ->distinct()
                ->where([
                    'a.employee_id' => $employee_id,
                    'a.payroll_period_id' => $payroll_period_id
                ])
                ->orderby('b.id', 'asc')
                ->get();
        }

        $document_no = DB::table('document_numbers')->where('id', 7)->get();

        if ($document_no->isNotEmpty()) {
            if ($employees[0]->from_branch == 1) {
                $footer = [
                    'document_no' => $document_no[0]->co_document_number,
                    'revision' => $document_no[0]->co_revision,
                ];
            } elseif ($employees[0]->from_branch == 0) {
                $footer = [
                    'document_no' => $document_no[0]->rd_document_number,
                    'revision' => $document_no[0]->rd_revision,
                ];
            } else {
                $footer = [
                    'document_no' => '',
                    'revision' => '',
                ];
            }
        } else {
            $footer = [
                'document_no' => '',
                'revision' => '',
            ];
        }

        $pdf = PDF::loadView('process_attendance.process_attendance_report', compact('time_data', 'employees', 'time_data_absent', 'companies', 'footer'))->setOptions(['defaultFont' => 'sans-serif']);
        $pdf->setPaper('A4');
        $pdfContent = $pdf->output();
        $base64Pdf = base64_encode($pdfContent);

        $filename = 'process_attendance_report_' . $employee_id . '_' . $payroll_period_id . '_' . date('Y-m-d') . '.pdf';

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate process attendance report: ' . $e->getMessage());
        }
    }

    public function offset(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");

        $dataX = $request->all();
        $late_config = $dataX['late_config'];
        $ut_config = $dataX['ut_config'];
        $absent_config = $dataX['absent_config'];
        $is_config = $dataX['is_config'];
        $id = $dataX['id'];
        $payroll_period_id = $dataX['payroll_period_id'];
        $time_data = DB::table('time_data as a')
            ->join('leave_credits as b', 'a.employee_id', '=', 'b.employee_id')
            ->join('employees as c', 'a.employee_id', '=', 'c.id')
            ->select(
                DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                               CONCAT(c.first_name,' ',c.last_name)
                           ELSE
                               RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key'))
                           END as name"),
                'b.credits',
                'a.id',
                'a.employee_id',
                'a.applied_offset',
                DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.late,0))/8)) as late"),
                DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.undertime,0))/8)) as ut"),
                DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.absent,0)))) as absent"),
                DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.late,0))/8)) + CONVERT(DECIMAL(18,2),(sum(isnull(a.undertime,0))/8)) + CONVERT(DECIMAL(18,2),(sum(isnull(a.absent,0)))) as total")
            )
            ->where('payroll_period_id', $payroll_period_id)
            ->where([
                'active' => true,
                'is_employee' => true,
                'b.leave_type_id' => 16,
                'a.id' => $id
            ])
            ->groupBy(
                'c.is_encrypted',
                'c.first_name',
                'c.last_name',
                'b.credits',
                'a.employee_id',
                'a.applied_offset',
                'a.id'
            )
            ->get();

        $time_data_details = DB::table('time_data as a')
            ->where('payroll_period_id', $payroll_period_id)
            ->where([
                'a.id' => $id
            ])
            ->get();



        if ($is_config == "true") {

            $late_offset = $time_data[0]->late <= $late_config ? $time_data_details[0]->late : $late_config * 8;
            $late = $time_data[0]->late <= $late_config ? 0 : ($time_data_details[0]->late - $late_config * 8);
            $ut_offset = $time_data[0]->ut <= $ut_config ? $time_data_details[0]->undertime : $ut_config * 8;
            $ut = $time_data[0]->ut <= $ut_config ? 0 : ($time_data_details[0]->undertime - $ut_config * 8);
            $absent_offset = $time_data[0]->absent <= $absent_config ? $time_data[0]->absent : $absent_config;
            $absent = $time_data[0]->absent <= $absent_config ? 0 : ($time_data[0]->absent - $absent_config);
            $offset_total = ($late_offset / 8) + ($ut_offset / 8) + $absent_offset;

            if (($time_data[0]->applied_offset == 1) || ($offset_total) > ($time_data[0]->credits)) {
                $leave_credits_update = ($time_data[0]->credits) - 0;
                $is_offset = 0;
                $leave_offset = 0;
            } else {

                $leave_credits_update = ($time_data[0]->credits) - ($offset_total);
                $leave_offset = $offset_total;
                DB::table('time_data')
                    ->where([
                        'id' => $id,
                        'payroll_period_id' => $payroll_period_id
                    ])
                    ->update([
                        'applied_offset' => 1,
                        'late_offset' => $late_offset,
                        'undertime_offset' => $ut_offset,
                        'absent_offset' => $absent_offset,
                        'late' => $late,
                        'undertime' => $ut,
                        'absent' => $absent,
                        'leave' => $leave_offset
                    ]);
            }
        } else {

            if (($time_data[0]->applied_offset == 1) || ($time_data[0]->total) > ($time_data[0]->credits)) {
                $leave_credits_update = floatval($time_data[0]->credits) - 0;
                $is_offset = 0;
                $leave_offset = 0;
            } else {
                $leave_credits_update = floatval($time_data[0]->credits) - floatval($time_data[0]->total);
                $leave_offset = $time_data[0]->total;
                DB::table('time_data')
                    ->where([
                        'id' => $id,
                        'payroll_period_id' => $payroll_period_id
                    ])
                    ->update([
                        'applied_offset' => 1,
                        'late' => 0,
                        'undertime' => 0,
                        'absent' => 0,
                        'leave' => $leave_offset
                    ]);
            }
        }

        $emp_id = $time_data[0]->employee_id;
        DB::table('leave_credits')->where(['employee_id' => $emp_id, 'leave_type_id' => 16])->update(['credits' => $leave_credits_update]);
        return $this->successResponse(null, 'You have successfully offsetted tardiness for this period!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to offset tardiness: ' . $e->getMessage());
        }
    }

    public function cancel_offset($id, $payroll_period_id)
    {
        try {
            $app_key = env("APP_KEY", "");

        $time_data = DB::table('time_data as a')
            ->join('leave_credits as b', 'a.employee_id', '=', 'b.employee_id')
            ->join('employees as c', 'a.employee_id', '=', 'c.id')
            ->select(
                DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                               CONCAT(c.first_name,' ',c.last_name)
                           ELSE
                               RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key'))
                           END as name"),
                'b.credits',
                'a.id',
                'a.employee_id',
                'a.applied_offset',
                DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.late,0))/8)) as late"),
                DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.undertime,0))/8)) as ut"),
                DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.absent,0)))) as absent"),
                DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.late_offset,0))/8)) + CONVERT(DECIMAL(18,2),(sum(isnull(a.undertime_offset,0))/8)) + CONVERT(DECIMAL(18,2),(sum(isnull(a.absent_offset,0)))) as total")
            )
            ->where('payroll_period_id', $payroll_period_id)
            ->where([
                'active' => true,
                'is_employee' => true,
                'b.leave_type_id' => 16,
                'a.id' => $id
            ])
            ->groupBy(
                'c.is_encrypted',
                'c.first_name',
                'c.last_name',
                'b.credits',
                'a.employee_id',
                'a.applied_offset',
                'a.id'
            )
            ->get();

        $time_data_details = DB::table('time_data as a')
            ->where('payroll_period_id', $payroll_period_id)
            ->where([
                'a.id' => $id
            ])
            ->get();

        if (($time_data[0]->applied_offset == 0)) {
            $leave_credits_update = floatval($time_data[0]->credits) + 0;
        } else {
            $leave_credits_update = floatval($time_data[0]->credits) + floatval($time_data[0]->total);
        }
        $emp_id = $time_data[0]->employee_id;
        DB::table('leave_credits')->where(['employee_id' => $emp_id, 'leave_type_id' => 16])->update(['credits' => $leave_credits_update]);
        DB::table('time_data')
            ->where([
                'id' => $id,
                'payroll_period_id' => $payroll_period_id
            ])
            ->update([
                'applied_offset' => 0,
                'late' => floatval($time_data_details[0]->late) + floatval($time_data_details[0]->late_offset),
                'undertime' => floatval($time_data_details[0]->undertime) + floatval($time_data_details[0]->undertime_offset),
                'absent' => floatval($time_data_details[0]->absent) + floatval($time_data_details[0]->absent_offset),
                'leave' => 0
            ]);
        return $this->successResponse(null, 'You have successfully cancelled offset for this period!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to cancel offset: ' . $e->getMessage());
        }
    }

    public function offset_details($id, $payroll_period_id)
    {
        try {
            $app_key = env("APP_KEY", "");

        $time_data = DB::table('time_data as a')
            ->join('leave_credits as b', 'a.employee_id', '=', 'b.employee_id')
            ->join('employees as c', 'a.employee_id', '=', 'c.id')
            ->select(
                DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                               CONCAT(c.first_name,' ',c.last_name)
                           ELSE
                               RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key'))
                           END as name"),
                'b.credits',
                'a.employee_id',
                'a.applied_offset',
                DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.late,0))/8)) as late"),
                DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.undertime,0))/8)) as ut"),
                DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.absent,0)))) as absent"),
                DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.late,0))/8)) + CONVERT(DECIMAL(18,2),(sum(isnull(a.undertime,0))/8)) + CONVERT(DECIMAL(18,2),(sum(isnull(a.absent,0)))) as total")
            )
            ->where('payroll_period_id', $payroll_period_id)
            ->where('b.credits', '>', 0)
            ->where([
                'active' => true,
                'is_employee' => true,
                'b.leave_type_id' => 16,
                'a.id' => $id
            ])
            ->where(function ($query) {
                $query->where('a.late_offset', '>', 0);
                $query->orWhere('a.undertime_offset', '>', 0);
                $query->orWhere('a.absent_offset', '>', 0);
            })
            ->groupBy(
                'c.is_encrypted',
                'c.first_name',
                'c.last_name',
                'b.credits',
                'a.employee_id',
                'a.applied_offset'
            )
            ->get();

        if (($time_data[0]->applied_offset == 1) || ($time_data[0]->total) > ($time_data[0]->credits)) {
            $leave_credits_update = ($time_data[0]->credits) - 0;
        } else {
            $leave_credits_update = ($time_data[0]->credits) - ($time_data[0]->total);
            $leave_offset = $time_data[0]->total;
        }
        $emp_id = $time_data[0]->employee_id;
        DB::table('leave_credits')->where(['employee_id' => $emp_id, 'leave_type_id' => 16])->update(['credits' => $leave_credits_update]);
        DB::table('time_data')
            ->where([
                'id' => $id,
                'payroll_period_id' => $payroll_period_id
            ])
            ->where(function ($query) {
                $query->where('late', '>', 0);
                $query->orWhere('undertime', '>', 0);
                $query->orWhere('absent', '>', 0);
            })
            ->update([
                'applied_offset' => 1,
                'late' => 0,
                'undertime' => 0,
                'absent' => 0,
                'leave' => $leave_offset
            ]);
        return $this->successResponse(null, 'You have successfully offsetted tardiness for this period!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to offset tardiness details: ' . $e->getMessage());
        }
    }

    public function cancel_offset_details($id, $payroll_period_id)
    {
        try {
            $app_key = env("APP_KEY", "");

        $time_data = DB::table('time_data as a')
            ->join('leave_credits as b', 'a.employee_id', '=', 'b.employee_id')
            ->join('employees as c', 'a.employee_id', '=', 'c.id')
            ->select(
                DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                               CONCAT(c.first_name,' ',c.last_name)
                           ELSE
                               RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key'))
                           END as name"),
                'b.credits',
                'a.employee_id',
                'a.applied_offset',
                DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.late,0))/8)) as late"),
                DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.undertime,0))/8)) as ut"),
                DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.absent,0)))) as absent"),
                DB::raw("CONVERT(DECIMAL(18,2),(sum(isnull(a.late,0))/8)) + CONVERT(DECIMAL(18,2),(sum(isnull(a.undertime,0))/8)) + CONVERT(DECIMAL(18,2),(sum(isnull(a.absent,0)))) as total")
            )
            ->where('payroll_period_id', $payroll_period_id)
            ->where('b.credits', '>', 0)
            ->where([
                'active' => true,
                'is_employee' => true,
                'b.leave_type_id' => 16,
                'a.id' => $id
            ])
            ->where(function ($query) {
                $query->where('a.late_offset', '>', 0);
                $query->orWhere('a.undertime_offset', '>', 0);
                $query->orWhere('a.absent_offset', '>', 0);
            })
            ->groupBy(
                'c.is_encrypted',
                'c.first_name',
                'c.last_name',
                'b.credits',
                'a.employee_id',
                'a.applied_offset'
            )
            ->get();
        if (($time_data[0]->applied_offset == 0)) {
            $leave_credits_update = ($time_data[0]->credits) + 0;
        } else {
            $leave_credits_update = ($time_data[0]->credits) + ($time_data[0]->total);
        }
        $emp_id = $time_data[0]->employee_id;
        DB::table('leave_credits')->where(['employee_id' => $emp_id, 'leave_type_id' => 16])->update(['credits' => $leave_credits_update]);
        DB::table('time_data')
            ->where([
                'id' => $id,
                'payroll_period_id' => $payroll_period_id,
                'applied_offset' => 1
            ])
            ->where(function ($query) {
                $query->where('late_offset', '>', 0);
                $query->orWhere('undertime_offset', '>', 0);
                $query->orWhere('absent_offset', '>', 0);
            })
            ->update(['applied_offset' => 0]);
        return $this->successResponse(null, 'You have successfully cancelled offset details for this period!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to cancel offset details: ' . $e->getMessage());
        }
    }

    private function updateNewStepIncrement($employee_id, $date)
    {
        // Get the most recent approved step increment that is effective on or before the current date
        // This ensures step increments apply on their effectivity date and on any day after
        $step_increment = DB::table('step_increments')
            ->where([
                'employee_id' => $employee_id,
                'is_approved' => true
            ])
            ->whereDate('effectivity_date', '<=', $date)
            ->orderBy('effectivity_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($step_increment) {
            // Check if employee's current salary step is already updated to this step increment
            // This prevents re-applying the same step increment multiple times
            $employee = DB::table('employees')->where('id', $employee_id)->first();

            if ($employee) {
                // Only update if the employee's current step is different from the new step
                // This ensures we apply the step increment once, but don't re-apply it every day
                if ($employee->salary_step_id != $step_increment->new_salary_step_id) {
                    // Update Employee Info
                    $data_employee = [
                        'salary_step_id' => $step_increment->new_salary_step_id,
                        'salary' => $step_increment->new_salary,
                        'tax_amount' => $step_increment->new_tax_amount,
                        'gsis_amount' => $step_increment->new_gsis_amount,
                        'sss_amount' => $step_increment->new_sss_amount,
                        'pagibig_amount' => $step_increment->new_pagibig_amount,
                        'philhealth_amount' => $step_increment->new_philhealth_amount
                    ];

                    DB::table('employees')
                        ->where('id', $employee_id)
                        ->update($data_employee);
                }
            }
        }
    }
}
