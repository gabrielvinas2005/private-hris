<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class BiometricsController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");
            $pdo = DB::connection('anviz')->getPdo();
            $database = DB::connection('anviz')->getDatabaseName();

            if ($database != '') {
                $anvizConn = DB::connection('anviz');
                $bio_server = $anvizConn->getConfig('host');
                $bio_username = $anvizConn->getConfig('username');
                $bio_password = $anvizConn->getConfig('password');

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
                            set @Server_Name = @Server_Name
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

                    $database2 = 'SRV_NAME.' . DB::connection('anviz')->getDatabaseName();
                    $database1 = DB::connection('sqlsrv')->getDatabaseName();

                    // Load employees existing in Anviz userinfo; include message when no timetable
                    // Match userinfo.UserCode with employees.employee_no
                    $bio_users = DB::table(DB::raw($database1 . '.dbo.employees as e'))
                        ->leftJoin(DB::raw($database2 . '.dbo.userinfo as a'), DB::raw('a.UserCode'), '=', DB::raw('e.employee_no'))
                        ->leftJoin(DB::raw($database2 . '.dbo.TimeTable as t'), DB::raw('t.Timeid'), '=', DB::raw('a.ClassFlag'))
                        ->where('e.active', 1)
                        ->where('e.is_employee', 1)
                        ->whereNotNull(DB::raw('a.UserCode'))
                        ->whereNotNull(DB::raw('e.employee_no'))
                        ->select([
                            DB::raw('e.id'),
                            DB::raw('e.employee_no'),
                            // Original (decrypting) name kept for reference:
                            // DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN UPPER(CONCAT(e.first_name,' ',SUBSTRING(e.middle_name,1,1),'. ',e.last_name)) ELSE UPPER(RTRIM(dbo.ufn_DecryptString(e.first_name,'$app_key'))) + ' ' + UPPER(RTRIM(SUBSTRING(dbo.ufn_DecryptString(e.middle_name,'$app_key'),1,1))) + '. ' + UPPER(RTRIM(dbo.ufn_DecryptString(e.last_name,'$app_key'))) END as name"),
                            DB::raw("UPPER(CONCAT(e.first_name,' ',SUBSTRING(e.middle_name,1,1),'. ',e.last_name)) as name"),
                            DB::raw('a.Userid as bio_userid'),
                            DB::raw('a.UserCode as bio_usercode'),
                            DB::raw('a.Name as bio_name'),
                            DB::raw('t.Timeid as timetable_id'),
                            DB::raw('t.Timename as timetable_name'),
                            DB::raw("CASE WHEN t.Timeid IS NULL THEN 'No attendance for this schedule' ELSE '' END as attendance_message"),
                        ])
                        ->orderBy(DB::raw('name'), 'asc')
                        ->get();

                    // Return the joined list under employees for the UI
                    $employees = $bio_users;
                } else {
                    $employees = [];
                    $bio_users = [];
                }
            } else {
                $employees = [];
                $database2 = '';
                $bio_users = [];
            }

            return $this->successResponse([
                'employees' => $employees,
                'database2' => $database2,
                'bio_users' => $bio_users ?? [],
                'configured' => isset($bio_server) && $bio_server !== ''
            ], 'Biometrics data retrieved successfully');
        } catch (\Throwable $th) {
            $employees = [];
            $database2 = '';
            $bio_users = [];

            return $this->successResponse([
                'employees' => $employees,
                'database2' => $database2,
                'bio_users' => $bio_users,
                'configured' => false
            ], 'Biometrics data retrieved successfully');
        }
    }

    public function load(Request $request)
    {
        try {
            $date_from = $request->attendance_from;
            $date_to = $request->attendance_to;

            if ($date_from == '' || $date_from == null) {
                return $this->errorResponse('Attendance date From is required.');
            }

            if ($date_to == '' || $date_to == null) {
                return $this->errorResponse('Attendance date To is required.');
            }

            if ($date_from > $date_to) {
                return $this->errorResponse('Invalid Attendance Period.');
            }

            if ($request->has('select') == false) {
                return $this->errorResponse('Please select atleast one employee.');
            }

            // Get selected employees
            $employees = [];
            $data = $request->all();

            for ($i = 0; $i < count($data["select"]); $i++) {
                array_push($employees, intval($data["select"][$i], 0));
            }

            $employees = json_encode($employees);
            $employees = str_replace("[", "", $employees);
            $employees = str_replace("]", "", $employees);

            $database1 = DB::connection('sqlsrv')->getDatabaseName();
            $database2 = '[SRV_NAME].' . DB::connection('anviz')->getDatabaseName();

            // link external server or server 2
            $anvizConn = DB::connection('anviz');
            $bio_server = $anvizConn->getConfig('host');
            $bio_username = $anvizConn->getConfig('username');
            $bio_password = $anvizConn->getConfig('password');

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

            $app_key = env("APP_KEY", "");

            // CheckType mapping: 0=AM IN, 1=PM OUT
            $biometrics = DB::select("
                            SELECT DISTINCT
                                c.id,
                                convert(date,b.checktime) as date,
                                CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                                    UPPER(CONCAT(c.first_name,' ',SUBSTRING(c.middle_name,1,1),'. ',c.last_name))
                                ELSE
                                    /* Original (decrypting) name kept for reference:
                                       UPPER(RTRIM(dbo.ufn_DecryptString(c.first_name,'$app_key')))+' '+UPPER(RTRIM(SUBSTRING(dbo.ufn_DecryptString(c.middle_name,'$app_key'),1,1)))+'. '+UPPER(RTRIM(dbo.ufn_DecryptString(c.last_name,'$app_key'))) */
                                    UPPER(CONCAT(c.first_name,' ',SUBSTRING(c.middle_name,1,1),'. ',c.last_name))
                                END as name,
                                convert(nvarchar(50),b.checktime,101) as checktime,
                                -- AM IN - checktype 0 (first of the day)
                                (select top(1) isnull(bio.checktime,'') from $database2.dbo.checkinout as bio where bio.checktype = 0 and convert(date,bio.checktime) = convert(date,b.checktime) and bio.userid = b.userid order by bio.checktime asc) as am_in,
                                -- AM OUT - checktype 1 (first of the day)
                                (select top(1) isnull(bio.checktime,'') from $database2.dbo.checkinout as bio where bio.checktype = 1 and convert(date,bio.checktime) = convert(date,b.checktime) and bio.userid = b.userid order by bio.checktime asc) as am_out,
                                -- BREAK IN - checktype 1 (second of the day, after AM OUT)
                                (select top(1) isnull(bio.checktime,'') from $database2.dbo.checkinout as bio where bio.checktype = 1 and convert(date,bio.checktime) = convert(date,b.checktime) and bio.userid = b.userid order by bio.checktime desc) as break_in,
                                -- BREAK OUT - checktype 2 (first of the day)
                                (select top(1) isnull(bio.checktime,'') from $database2.dbo.checkinout as bio where bio.checktype = 2 and convert(date,bio.checktime) = convert(date,b.checktime) and bio.userid = b.userid order by bio.checktime asc) as break_out,
                                -- PM IN - checktype 2 (second of the day, after BREAK OUT)
                                (select top(1) isnull(bio.checktime,'') from $database2.dbo.checkinout as bio where bio.checktype = 2 and convert(date,bio.checktime) = convert(date,b.checktime) and bio.userid = b.userid order by bio.checktime desc) as pm_in,
                                -- PM OUT - checktype 1
                                (select top(1) isnull(bio.checktime,'') from $database2.dbo.checkinout as bio where bio.checktype = 1 and convert(date,bio.checktime) = convert(date,b.checktime) and bio.userid = b.userid order by bio.checktime desc) as pm_out
                            FROM $database2.dbo.userinfo as a inner join
                                 $database2.dbo.checkinout as b on a.userid = b.userid inner join
                                 $database1.dbo.employees as c on a.UserCode = c.employee_no
                            WHERE c.id in ($employees) 
                            AND CONVERT(date,b.checktime) >= CONVERT(date,'$date_from') AND CONVERT(date,b.checktime) <= CONVERT(date,'$date_to')
                            ORDER BY id,date ASC
                        ");

            $biometrics = collect($biometrics);

            if ($biometrics->isEmpty()) {
                return $this->notFoundResponse('No record found. Please check the parameters.');
            } else {
                return $this->successResponse($biometrics, 'Biometric attendance data loaded successfully');
            }
        } catch (\Throwable $th) {
            return $this->serverErrorResponse('Failed to load biometric data: ' . $th->getMessage());
        }
    }

    /**
     * Get real-time individual check-in/check-out records
     * Returns individual records from Checkinout table as they are logged
     */
    public function realtime(Request $request)
    {
        try {
            $date_from = $request->get('attendance_from');
            $date_to = $request->get('attendance_to');

            if (empty($date_from) || empty($date_to)) {
                return $this->errorResponse('Attendance date From and To are required.');
            }

            if ($date_from > $date_to) {
                return $this->errorResponse('Invalid Attendance Period.');
            }

            if ($request->has('select') == false) {
                return $this->errorResponse('Please select at least one employee.');
            }

            // Get selected employees - handle array format (select[] or select)
            $employees = [];
            $data = $request->all();
            
            // Check for select[] first (Laravel parses this as 'select')
            $selectData = null;
            if (isset($data["select"])) {
                $selectData = $data["select"];
            } elseif ($request->has('select')) {
                $selectData = $request->get('select');
            }
            
            if ($selectData !== null) {
                if (is_array($selectData)) {
                    foreach ($selectData as $id) {
                        $id = intval($id, 0);
                        if ($id > 0) {
                            array_push($employees, $id);
                        }
                    }
                } else {
                    // Handle comma-separated string or single value
                    if (is_string($selectData) && strpos($selectData, ',') !== false) {
                        $selectArray = explode(',', $selectData);
                        foreach ($selectArray as $id) {
                            $id = trim($id);
                            $id = intval($id, 0);
                            if ($id > 0) {
                                array_push($employees, $id);
                            }
                        }
                    } else {
                        $id = intval($selectData, 0);
                        if ($id > 0) {
                            array_push($employees, $id);
                        }
                    }
                }
            }

            if (empty($employees)) {
                return $this->errorResponse('Please select at least one employee.');
            }

            $employees = json_encode($employees);
            $employees = str_replace("[", "", $employees);
            $employees = str_replace("]", "", $employees);

            $database1 = DB::connection('sqlsrv')->getDatabaseName();
            $database2 = '[SRV_NAME].' . DB::connection('anviz')->getDatabaseName();

            // Link external server
            $anvizConn = DB::connection('anviz');
            $bio_server = $anvizConn->getConfig('host');
            $bio_username = $anvizConn->getConfig('username');
            $bio_password = $anvizConn->getConfig('password');

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

            $app_key = env("APP_KEY", "");

            // Query individual check-in/check-out records
            // CheckType mapping: 0=AM IN, 1=PM OUT
            $records = DB::select("
                SELECT 
                    b.logid,
                    c.id as employee_id,
                    c.employee_no,
                    CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                        UPPER(CONCAT(c.first_name,' ',SUBSTRING(c.middle_name,1,1),'. ',c.last_name))
                    ELSE
                        /* Original (decrypting) name kept for reference:
                           UPPER(RTRIM(dbo.ufn_DecryptString(c.first_name,'$app_key')))+' '+UPPER(RTRIM(SUBSTRING(dbo.ufn_DecryptString(c.middle_name,'$app_key'),1,1)))+'. '+UPPER(RTRIM(dbo.ufn_DecryptString(c.last_name,'$app_key'))) */
                        UPPER(CONCAT(c.first_name,' ',SUBSTRING(c.middle_name,1,1),'. ',c.last_name))
                    END as employee_name,
                    b.checktime,
                    CONVERT(date, b.checktime) as date,
                    CONVERT(time, b.checktime) as time,
                    b.checktype,
                    CASE 
                        WHEN b.checktype = 0 THEN 'AM IN'
                        WHEN b.checktype = 1 THEN 'PM OUT'
                        ELSE 'Unknown'
                    END as check_type_label,
                    CASE 
                        WHEN b.checktype = 0 THEN 'In'
                        WHEN b.checktype = 1 THEN 'Out'
                        ELSE 'Unknown'
                    END as type,
                    b.userid,
                    a.UserCode as access_no
                FROM $database2.dbo.checkinout as b
                INNER JOIN $database2.dbo.userinfo as a ON a.userid = b.userid
                INNER JOIN $database1.dbo.employees as c ON a.UserCode = c.employee_no
                WHERE c.id IN ($employees)
                AND CONVERT(date, b.checktime) >= CONVERT(date, '$date_from')
                AND CONVERT(date, b.checktime) <= CONVERT(date, '$date_to')
                AND b.checktype IN (0, 1)
                ORDER BY b.checktime DESC, c.id ASC
            ");

            $records = collect($records);

            if ($records->isEmpty()) {
                return $this->notFoundResponse('No biometric records found for the selected criteria.');
            } else {
                return $this->successResponse($records, 'Real-time biometric records loaded successfully');
            }
        } catch (\Throwable $th) {
            return $this->serverErrorResponse('Failed to load real-time biometric data: ' . $th->getMessage());
        }
    }

    /**
     * Get daily attendance summary for all users in the biometric database
     * Shows all users from userinfo table that have checkinout records for the current day
     * Displays AM In, AM Out, Break In, Break Out, PM In, PM Out
     */
    public function dailySummary(Request $request)
    {
        try {
            $date = $request->get('date', date('Y-m-d'));
            
            // Check if anviz connection is configured
            try {
                // Use config() instead of env() - env() doesn't work after config caching
                $anvizDriver = config('database.connections.anviz.driver', '');
                
                if (empty($anvizDriver)) {
                    return $this->errorResponse('Biometric database not configured. Please configure the database connection first.', 400);
                }
                
                $anvizConn = DB::connection('anviz');
                $database2 = '[SRV_NAME].' . $anvizConn->getDatabaseName();
                $bio_server = $anvizConn->getConfig('host');
                $bio_username = $anvizConn->getConfig('username');
                $bio_password = $anvizConn->getConfig('password');
            } catch (\Exception $e) {
                // Connection not configured or driver not set
                // Check if it's a driver error
                $errorMessage = $e->getMessage();
                if (strpos($errorMessage, 'Unsupported driver') !== false || 
                    strpos($errorMessage, 'driver') !== false) {
                    return $this->errorResponse('Biometric database not configured. Please configure the database connection first.', 400);
                }
                // Re-throw other exceptions
                throw $e;
            }

            if (empty($bio_server)) {
                return $this->errorResponse('Biometric database not configured. Please configure the database connection first.', 400);
            }

            // Get main database connection for employees and departments tables
            $mainConn = DB::connection('sqlsrv');
            $database1 = $mainConn->getDatabaseName();
            $app_key = env("APP_KEY", "");

            // When both databases are on the same SQL Server instance, use the database name directly
            // instead of a linked server (avoids "No such host is known" when server connects to itself)
            $main_host = (string) $mainConn->getConfig('host');
            $main_port = (string) $mainConn->getConfig('port');
            $bio_port = (string) $anvizConn->getConfig('port');
            $sameServer = ($main_host === $bio_server && $main_port === $bio_port);

            if ($sameServer) {
                $database2 = $anvizConn->getDatabaseName();
            } else {
                // Only create linked server if it doesn't exist - don't drop/recreate on every request
                // This was causing major performance issues when called every second via auto-refresh
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

                    -- Only create linked server if it doesn't exist
                    -- Don't drop/recreate on every request - this was causing performance issues
                    if not exists(select * from sys.servers where name = N'SRV_NAME')
                        begin
                            EXEC sp_addlinkedserver @server='SRV_NAME',
                                            @srvproduct=N'',
                                            @provider=N'MSOLEDBSQL',   
                                            @datasrc=@Server_Name;

                            EXEC sp_addlinkedsrvlogin 'SRV_NAME', 'false', NULL,  @Username, @Password;
                        end
                ");
                $database2 = '[SRV_NAME].' . $anvizConn->getDatabaseName();
            }

            // Get ALL users from biometric database userinfo table
            // Join with employees table to get photo and department information
            // Join with departments table to get department name
            // Show today's attendance times for those users (blank if no records for today)
            // CheckType mapping: 0=AM IN, 1=PM OUT
            // ORDER BY latest_checktime DESC to show employees with most recent attendance records first
            $summary = DB::select("
                SELECT 
                    a.Userid as userid,
                    a.UserCode as usercode,
                    a.Name as employee_name,
                    -- Employee data from main database
                    e.id as employee_id,
                    e.photo as photo,
                    e.employee_no,
                    e.department_id,
                    e.work_schedule_id,
                    e.is_shifting,
                    d.name as department_name,
                    p.id as position_id,
                    p.name as position_name,
                    -- Build employee name from main database (handle encryption)
                    CASE 
                        WHEN ISNULL(e.is_encrypted, 0) = 0 THEN
                            UPPER(CONCAT(e.first_name, ' ', SUBSTRING(e.middle_name, 1, 1), '. ', e.last_name))
                        ELSE
                            /* Original (decrypting) name kept for reference:
                               UPPER(RTRIM(dbo.ufn_DecryptString(e.first_name, '$app_key'))) + ' ' +
                               UPPER(RTRIM(SUBSTRING(dbo.ufn_DecryptString(e.middle_name, '$app_key'), 1, 1))) + '. ' +
                               UPPER(RTRIM(dbo.ufn_DecryptString(e.last_name, '$app_key'))) */
                            UPPER(CONCAT(e.first_name, ' ', SUBSTRING(e.middle_name, 1, 1), '. ', e.last_name))
                    END as employee_full_name,
                    -- Get latest check time for sorting (most recent entry first)
                    (SELECT MAX(b.checktime)
                     FROM $database2.dbo.checkinout b
                     WHERE b.userid = a.Userid
                     AND CONVERT(date, b.checktime) = CONVERT(date, '$date')) as latest_checktime,
                    -- AM In - checktype 0 (first checktype 0 of the day)
                    (SELECT TOP 1 CONVERT(time, b.checktime)
                     FROM $database2.dbo.checkinout b
                     WHERE b.userid = a.Userid
                     AND CONVERT(date, b.checktime) = CONVERT(date, '$date')
                     AND b.checktype = 0
                     ORDER BY b.checktime ASC) as am_in,
                    -- AM Out - checktype 1 (first checktype 1 of the day, before break)
                    (SELECT TOP 1 CONVERT(time, b.checktime)
                     FROM $database2.dbo.checkinout b
                     WHERE b.userid = a.Userid
                     AND CONVERT(date, b.checktime) = CONVERT(date, '$date')
                     AND b.checktype = 1
                     ORDER BY b.checktime ASC) as am_out,
                    -- Break In - checktype 1 (second checktype 1 of the day, after AM Out)
                    (SELECT TOP 1 CONVERT(time, b.checktime)
                     FROM $database2.dbo.checkinout b
                     WHERE b.userid = a.Userid
                     AND CONVERT(date, b.checktime) = CONVERT(date, '$date')
                     AND b.checktype = 1
                     ORDER BY b.checktime DESC) as break_in,
                    -- Break Out - checktype 2 (first checktype 2 of the day)
                    (SELECT TOP 1 CONVERT(time, b.checktime)
                     FROM $database2.dbo.checkinout b
                     WHERE b.userid = a.Userid
                     AND CONVERT(date, b.checktime) = CONVERT(date, '$date')
                     AND b.checktype = 2
                     ORDER BY b.checktime ASC) as break_out,
                    -- PM In - checktype 2 (second checktype 2 of the day, after Break Out)
                    (SELECT TOP 1 CONVERT(time, b.checktime)
                     FROM $database2.dbo.checkinout b
                     WHERE b.userid = a.Userid
                     AND CONVERT(date, b.checktime) = CONVERT(date, '$date')
                     AND b.checktype = 2
                     ORDER BY b.checktime DESC) as pm_in,
                    -- PM Out - checktype 1
                    (SELECT TOP 1 CONVERT(time, b.checktime)
                     FROM $database2.dbo.checkinout b
                     WHERE b.userid = a.Userid
                     AND CONVERT(date, b.checktime) = CONVERT(date, '$date')
                     AND b.checktype = 1
                     ORDER BY b.checktime DESC) as pm_out
                FROM $database2.dbo.userinfo a
                LEFT JOIN $database1.dbo.employees e ON (e.employee_no = a.UserCode OR e.access_no = a.UserCode)
                    AND e.active = 1 
                    AND e.is_employee = 1
                LEFT JOIN $database1.dbo.departments d ON d.id = e.department_id
                    AND d.active = 1
                LEFT JOIN $database1.dbo.positions p ON p.id = e.position_id
                WHERE e.id IS NULL OR e.id != 0
                ORDER BY 
                    -- Put employees without records at the bottom, then by most recent check-in time
                    CASE WHEN (SELECT MAX(b.checktime)
                               FROM $database2.dbo.checkinout b
                               WHERE b.userid = a.Userid
                               AND CONVERT(date, b.checktime) = CONVERT(date, '$date')) IS NULL 
                         THEN 1 ELSE 0 END,
                    -- Most recent check-in time first (using subquery since we can't reference alias in ORDER BY)
                    (SELECT MAX(b.checktime)
                     FROM $database2.dbo.checkinout b
                     WHERE b.userid = a.Userid
                     AND CONVERT(date, b.checktime) = CONVERT(date, '$date')) DESC,
                    -- Then by usercode for employees without records
                    a.UserCode ASC
            ");

            // Get day of week for schedule lookup (1=Monday, 7=Sunday)
            $dateObj = \Carbon\Carbon::parse($date);
            $dayOfWeek = $dateObj->dayOfWeek; // 0=Sunday, 1=Monday, ..., 6=Saturday
            if ($dayOfWeek == 0) {
                $dayOfWeek = 7; // Convert Sunday to 7
            }
            
            // OPTIMIZATION: Batch load all employee data to avoid N+1 queries
            // Get all unique employee IDs from the summary
            $employeeIds = collect($summary)
                ->pluck('employee_id')
                ->filter(function($id) {
                    return $id !== null && $id !== 0;
                })
                ->unique()
                ->values()
                ->toArray();
            
            // Batch load all schedules for employees that have work_schedule_id
            $workScheduleIds = collect($summary)
                ->pluck('work_schedule_id')
                ->filter(function($id) {
                    return $id !== null && $id !== 0;
                })
                ->unique()
                ->values()
                ->toArray();
            
            $schedulesMap = [];
            if (!empty($workScheduleIds)) {
                $schedules = DB::table('fix_schedules_details')
                    ->whereIn('fix_schedule_id', $workScheduleIds)
                    ->where('day_id', $dayOfWeek)
                    ->get();
                
                // Create map: fix_schedule_id => schedule details
                foreach ($schedules as $schedule) {
                    $schedulesMap[(int) $schedule->fix_schedule_id] = $schedule;
                }
            }

            // Batch load shift schedule rows for the calendar date (when work_schedule_id points at shift_schedules_headers.id)
            $shiftSchedulesMap = [];
            $shiftScheduleIds = collect($summary)
                ->filter(function ($row) {
                    $wid = $row->work_schedule_id ?? null;
                    if ($wid === null || $wid === '' || (int) $wid === 0) {
                        return false;
                    }
                    $sh = $row->is_shifting ?? null;

                    return $sh === true || $sh === 1 || $sh === '1' || (int) $sh === 1;
                })
                ->map(function ($row) {
                    return (int) $row->work_schedule_id;
                })
                ->unique()
                ->values()
                ->toArray();

            if (!empty($shiftScheduleIds)) {
                $shiftRows = DB::table('shift_schedules_details')
                    ->whereIn('shift_schedule_id', $shiftScheduleIds)
                    ->where('shift_date', $date)
                    ->get();

                foreach ($shiftRows as $sr) {
                    $shiftSchedulesMap[(int) $sr->shift_schedule_id] = $sr;
                }
            }
            
            // Batch load all leaves for the date
            $leavesMap = [];
            if (!empty($employeeIds)) {
                $leaves = DB::table('leave_headers')
                    ->whereIn('employee_id', $employeeIds)
                    ->where('date_from', '<=', $date)
                    ->where('date_to', '>=', $date)
                    ->where(function($q) {
                        $q->where('approved', 1)
                          ->orWhere('approved_2', 1)
                          ->orWhere('approved_3', 1);
                    })
                    ->pluck('employee_id')
                    ->unique()
                    ->toArray();
                
                // Create map: employee_id => true (has leave)
                foreach ($leaves as $employeeId) {
                    $leavesMap[$employeeId] = true;
                }
            }
            
            // Batch load all OB applications for the date
            $obMap = [];
            if (!empty($employeeIds)) {
                $obs = DB::table('official_business_applications')
                    ->whereIn('employee_id', $employeeIds)
                    ->whereRaw("CAST(date_time_from AS DATE) <= ?", [$date])
                    ->whereRaw("CAST(date_time_to AS DATE) >= ?", [$date])
                    ->where(function($q) {
                        $q->where('approved', 1)
                          ->orWhere('approved_2', 1)
                          ->orWhere('approved_3', 1);
                    })
                    ->pluck('employee_id')
                    ->unique()
                    ->toArray();
                
                // Create map: employee_id => true (has OB)
                foreach ($obs as $employeeId) {
                    $obMap[$employeeId] = true;
                }
            }
            
            // Format the results and convert NULL to "-"
            $formattedSummary = collect($summary)->map(function ($item) use ($date, $dayOfWeek, $schedulesMap, $shiftSchedulesMap, $leavesMap, $obMap) {
                // TIME values from SQL Server are already in H:i:s format or null
                $formatTime = function($time) {
                    if ($time === null || $time === '') {
                        return '-';
                    }
                    // If it's already a string in H:i:s format, return as is
                    if (is_string($time)) {
                        return $time;
                    }
                    // SQL Server / PDO may return DateTimeImmutable; Carbon extends DateTime
                    if ($time instanceof \DateTimeInterface) {
                        return $time->format('H:i:s');
                    }
                    return '-';
                };
                
                // Use employee name from main database if available, otherwise use userinfo name
                $employeeName = $item->employee_full_name ?? $item->employee_name ?? '';
                
                // Filter out employees with id = 0
                $employeeId = $item->employee_id ?? null;
                if ($employeeId === 0 || $employeeId === null) {
                    return null; // Skip this record
                }
                
                // Get employee work schedule from query result (already loaded)
                $workScheduleId = $item->work_schedule_id ?? null;
                $isShiftingEmployee = ($item->is_shifting === true || $item->is_shifting === 1 || $item->is_shifting === '1' || (int) ($item->is_shifting ?? 0) === 1);
                
                // Initialize violation flags
                $isLateAmIn = false;
                $isEarlyAmOut = false;
                $isLatePmIn = false;
                $isEarlyPmOut = false;
                $hasMissingRecord = false;
                $hasMissingAmIn = false;
                $hasMissingAmOut = false;
                $hasMissingPmIn = false;
                $hasMissingPmOut = false;
                
                // Get schedule details: shift_schedules_details when is_shifting, else fix_schedules_details by day_id
                $scheduleDetails = null;
                if ($workScheduleId) {
                    $wid = (int) $workScheduleId;
                    if ($isShiftingEmployee && isset($shiftSchedulesMap[$wid])) {
                        $scheduleDetails = $shiftSchedulesMap[$wid];
                    } elseif (!$isShiftingEmployee && isset($schedulesMap[$wid])) {
                        $scheduleDetails = $schedulesMap[$wid];
                    }
                }
                
                // Check for leave from pre-loaded map (O(1) lookup)
                $hasLeave = isset($leavesMap[$employeeId]);
                
                // Check for Official Business from pre-loaded map (O(1) lookup)
                $hasOB = isset($obMap[$employeeId]);
                
                // Parse actual times
                $actualAmIn = $item->am_in ? $formatTime($item->am_in) : null;
                $actualAmOut = $item->am_out ? $formatTime($item->am_out) : null;
                $actualPmIn = $item->pm_in ? $formatTime($item->pm_in) : null;
                $actualPmOut = $item->pm_out ? $formatTime($item->pm_out) : null;
                
                // Calculate violations if schedule exists and not rest day
                if ($scheduleDetails && ($scheduleDetails->is_restday ?? 0) == 0 && !$hasLeave && !$hasOB) {
                    $flexiHours = (float) ($scheduleDetails->flexi_hours ?? 0);

                    // Normalize DB time values (DateTime, "1900-01-01 16:00:00", decimals) to H:i:s for reliable parsing
                    $normalizeScheduleTime = function ($t) use ($formatTime) {
                        if ($t === null || $t === '') {
                            return null;
                        }
                        if ($t instanceof \DateTimeInterface) {
                            return $t->format('H:i:s');
                        }
                        if (is_string($t)) {
                            $s = trim($t);
                            if ($s === '' || $s === '-') {
                                return null;
                            }
                            if (preg_match('/(\d{1,2}:\d{2}:\d{2})/', $s, $m)) {
                                return $m[1];
                            }
                            if (preg_match('/\b(\d{1,2}:\d{2})\b/', $s, $m)) {
                                return $m[1] . ':00';
                            }
                        }
                        $formatted = $formatTime($t);

                        return ($formatted !== null && $formatted !== '' && $formatted !== '-') ? $formatted : null;
                    };

                    $timeStrToSeconds = function ($timeStr) {
                        if ($timeStr === null || $timeStr === '' || $timeStr === '-') {
                            return null;
                        }
                        $timeStr = trim((string) $timeStr);
                        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?/', $timeStr, $m)) {
                            return ((int) $m[1]) * 3600 + ((int) $m[2]) * 60 + (isset($m[3]) ? (int) $m[3] : 0);
                        }

                        return null;
                    };

                    $scheduledAmIn = $normalizeScheduleTime($scheduleDetails->am_in ?? null);
                    $scheduledAmOut = $normalizeScheduleTime($scheduleDetails->am_out ?? null);
                    $scheduledPmIn = $normalizeScheduleTime($scheduleDetails->pm_in ?? null);
                    $scheduledPmOut = $normalizeScheduleTime($scheduleDetails->pm_out ?? null);

                    // Check late AM IN (after scheduled am_in + flexi_hours)
                    if ($actualAmIn && $scheduledAmIn) {
                        $actualAmInSec = $timeStrToSeconds($actualAmIn);
                        $scheduledAmInSec = $timeStrToSeconds($scheduledAmIn);
                        if ($actualAmInSec !== null && $scheduledAmInSec !== null) {
                            $flexDeadlineSec = $scheduledAmInSec + (int) round($flexiHours * 3600);
                            if ($actualAmInSec > $flexDeadlineSec) {
                                $isLateAmIn = true;
                            }
                        }
                    }

                    // Check early AM OUT
                    if ($actualAmOut && $scheduledAmOut) {
                        $actualAmOutSec = $timeStrToSeconds($actualAmOut);
                        $scheduledAmOutSec = $timeStrToSeconds($scheduledAmOut);
                        if ($actualAmOutSec !== null && $scheduledAmOutSec !== null && $actualAmOutSec < $scheduledAmOutSec) {
                            $isEarlyAmOut = true;
                        }
                    }

                    // Check late PM IN
                    if ($actualPmIn && $scheduledPmIn) {
                        $actualPmInSec = $timeStrToSeconds($actualPmIn);
                        $scheduledPmInSec = $timeStrToSeconds($scheduledPmIn);
                        if ($actualPmInSec !== null && $scheduledPmInSec !== null && $actualPmInSec > $scheduledPmInSec) {
                            $isLatePmIn = true;
                        }
                    }

                    // Check early PM OUT (undertime on out)
                    // If employee was late beyond AM flexi window, allow checkout until scheduled PM out + flexi_hours
                    // (e.g. 4 PM + 2h flexi => 6 PM; 5:59 PM is still undertime). Compare in seconds so parsing stays exact.
                    if ($actualPmOut && $scheduledPmOut) {
                        $actualPmOutSec = $timeStrToSeconds($actualPmOut);
                        $scheduledPmOutSec = $timeStrToSeconds($scheduledPmOut);
                        if ($scheduledPmOutSec === null || $actualPmOutSec === null) {
                            // skip
                        } elseif ($isLateAmIn) {
                            $flexAdjustedPmOutSec = $scheduledPmOutSec + (int) round($flexiHours * 3600);
                            if ($actualPmOutSec < $flexAdjustedPmOutSec) {
                                $isEarlyPmOut = true;
                            }
                        } elseif ($actualPmOutSec < $scheduledPmOutSec) {
                            $isEarlyPmOut = true;
                        }
                    }
                }
                
                // Check for missing records (no attendance data, no leave, no OB)
                // Only check if employee has a schedule and it's not a rest day
                if ($scheduleDetails && ($scheduleDetails->is_restday ?? 0) == 0 && !$hasLeave && !$hasOB) {
                    $scheduledAmIn = $scheduleDetails->am_in ?? null;
                    $scheduledAmOut = $scheduleDetails->am_out ?? null;
                    $scheduledPmIn = $scheduleDetails->pm_in ?? null;
                    $scheduledPmOut = $scheduleDetails->pm_out ?? null;
                    
                    // Check if there are any records at all
                    $hasAnyRecord = ($actualAmIn && $actualAmIn !== '-') || 
                                    ($actualAmOut && $actualAmOut !== '-') || 
                                    ($actualPmIn && $actualPmIn !== '-') || 
                                    ($actualPmOut && $actualPmOut !== '-') || 
                                    ($item->break_in && $formatTime($item->break_in) !== '-') || 
                                    ($item->break_out && $formatTime($item->break_out) !== '-');
                    
                    // If no records at all, mark as missing
                    if (!$hasAnyRecord) {
                        $hasMissingRecord = true;
                        // Mark individual fields as missing if they're scheduled
                        if ($scheduledAmIn) $hasMissingAmIn = true;
                        if ($scheduledAmOut) $hasMissingAmOut = true;
                        if ($scheduledPmIn) $hasMissingPmIn = true;
                        if ($scheduledPmOut) $hasMissingPmOut = true;
                    } else {
                        // Check individual fields - mark as missing if scheduled but not present
                        if ($scheduledAmIn && (!$actualAmIn || $actualAmIn === '-')) {
                            $hasMissingAmIn = true;
                            $hasMissingRecord = true;
                        }
                        if ($scheduledAmOut && (!$actualAmOut || $actualAmOut === '-')) {
                            $hasMissingAmOut = true;
                            $hasMissingRecord = true;
                        }
                        if ($scheduledPmIn && (!$actualPmIn || $actualPmIn === '-')) {
                            $hasMissingPmIn = true;
                            $hasMissingRecord = true;
                        }
                        if ($scheduledPmOut && (!$actualPmOut || $actualPmOut === '-')) {
                            $hasMissingPmOut = true;
                            $hasMissingRecord = true;
                        }
                    }
                }
                
                return [
                    'userid' => $item->userid,
                    'usercode' => $item->usercode,
                    'employee_name' => $employeeName,
                    'employee_no' => $item->employee_no ?? $item->usercode,
                    // Employee data for Employee_Data_Populate component
                    'photo' => $item->photo ?? null,
                    'department_id' => $item->department_id ?? null,
                    'department' => $item->department_name ?? '',
                    'position_id' => $item->position_id ?? null,
                    'position' => $item->position_name ?? '',
                    'employee_id' => $employeeId,
                    // Attendance times
                    'am_in' => $actualAmIn ?? '-',
                    'am_out' => $actualAmOut ?? '-',
                    'break_in' => $formatTime($item->break_in),
                    'break_out' => $formatTime($item->break_out),
                    'pm_in' => $actualPmIn ?? '-',
                    'pm_out' => $actualPmOut ?? '-',
                    // Violation flags for styling
                    'is_late_am_in' => $isLateAmIn,
                    'is_early_am_out' => $isEarlyAmOut,
                    'is_late_pm_in' => $isLatePmIn,
                    'is_early_pm_out' => $isEarlyPmOut,
                    'has_missing_record' => $hasMissingRecord,
                    'has_missing_am_in' => $hasMissingAmIn,
                    'has_missing_am_out' => $hasMissingAmOut,
                    'has_missing_pm_in' => $hasMissingPmIn,
                    'has_missing_pm_out' => $hasMissingPmOut,
                ];
            })->filter(function ($item) {
                // Remove null entries (employees with id = 0)
                return $item !== null;
            })->values();

            return $this->successResponse($formattedSummary, 'Daily attendance summary loaded successfully');
        } catch (\Throwable $th) {
            return $this->serverErrorResponse('Failed to load daily summary: ' . $th->getMessage());
        }
    }

}
