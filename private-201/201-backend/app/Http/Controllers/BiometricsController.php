<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Traits\ApiResponse;

class BiometricsController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");
            $pdo = DB::connection('sqlsrv_bio')->getPdo();
            $database = DB::connection('sqlsrv_bio')->getDatabaseName();

            if ($database != '') {
                $bio_server = env('DB_HOST_BIO', '');
                $bio_username = env('DB_USERNAME_BIO', '');
                $bio_password = env('DB_PASSWORD_BIO', '');

                if ($bio_server == '' && env('DB_BIO_ENCRYPTED', false)) {
                    $bio_server = secEnv('DB_HOST_BIO', '');
                    $bio_username = secEnv('DB_USERNAME_BIO', '');
                    $bio_password = secEnv('DB_PASSWORD_BIO', '');
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
                            set @Server_Name = @Server_Name
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
                                            @datasrc=@Server_Name; 

                            EXEC sp_addlinkedsrvlogin 'SRV_NAME', 'false', NULL, @Username, @Password;
                        
                        end
                ");

                    $database2 = 'SRV_NAME.' . DB::connection('sqlsrv_bio')->getDatabaseName();

                    $employees = DB::table('employees as a')
                        ->join('positions as b', 'a.position_id', '=', 'b.id')
                        ->select(
                            'a.id',
                            'a.employee_no',
                            'a.access_no',
                            DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                    UPPER(CONCAT(a.first_name,' ',SUBSTRING(a.middle_name,1,1),'. ',a.last_name))
                                 ELSE
                                    UPPER(RTRIM(dbo.ufn_DecryptString(a.first_name,'$app_key')))+' '+UPPER(RTRIM(SUBSTRING(dbo.ufn_DecryptString(a.middle_name,'$app_key'),1,1)))+'. '+UPPER(RTRIM(dbo.ufn_DecryptString(a.last_name,'$app_key')))
                                 END as name"),
                            'b.name as position'
                        )
                        ->where([
                            'a.active' => true,
                            'a.is_employee' => true
                        ])
                        ->whereIn('a.access_no', function ($query) use ($database2) {
                            $query->select('a.badgenumber')->from($database2 . '.dbo.userinfo as a');
                        })
                        ->orderBy('name', 'asc')
                        ->get();
                } else {
                    $employees = [];
                }
            } else {
                $employees = [];
                $database2 = '';
            }

            return $this->successResponse([
                'employees' => $employees,
                'database2' => $database2
            ], 'Biometrics data retrieved successfully');
        } catch (\Throwable $th) {
            $employees = [];
            $database2 = '';

            return $this->successResponse([
                'employees' => $employees,
                'database2' => $database2
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
            $database2 = '[SRV_NAME].' . DB::connection('sqlsrv_bio')->getDatabaseName();

            // link external server or server 2
            $bio_server = env('DB_HOST_BIO', '');
            $bio_username = env('DB_USERNAME_BIO', '');
            $bio_password = env('DB_PASSWORD_BIO', '');

            if ($bio_server == '' && env('DB_BIO_ENCRYPTED', false)) {
                $bio_server = secEnv('DB_HOST_BIO', '');
                $bio_username = secEnv('DB_USERNAME_BIO', '');
                $bio_password = secEnv('DB_PASSWORD_BIO', '');
            }

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
                                        @datasrc=@Server_Name; 

                        EXEC sp_addlinkedsrvlogin 'SRV_NAME', 'false', NULL, @Username, @Password;
                    end
            ");

            $app_key = env("APP_KEY", "");

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

    public function configLoad()
    {
        try {
            return $this->successResponse([], 'Biometric setup page loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load biometric configuration page: ' . $e->getMessage());
        }
    }

    public function config(Request $request)
    {
        try {
            $new_server = $request->get("server");
            $new_database = $request->get("database");
            $new_username = $request->get("username");
            $new_password = $request->get("password");

            $this->setEnvironmentValue('DB_HOST_BIO', $new_server);
            $this->setEnvironmentValue('DB_DATABASE_BIO', $new_database);
            $this->setEnvironmentValue('DB_USERNAME_BIO', $new_username);
            $this->setEnvironmentValue('DB_PASSWORD_BIO', $new_password);

            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('optimize:clear');

            DB::connection('sqlsrv_bio')->getPDO();
            
            return $this->successResponse([
                'server' => $new_server,
                'database' => $new_database,
                'username' => $new_username
            ], 'Successfully configured biometric connection.');
        } catch (\Exception $e) {
            return $this->errorResponse('Invalid biometric connection: ' . $e->getMessage());
        }
    }

    public function setEnvironmentValue(string $key, string $value)
    {
        try {
            Artisan::call('config:cache');
            Artisan::call('optimize:clear');

            $path = app()->environmentFilePath();
            $env = file_get_contents($path);

            $old_value = env($key);

            if (!str_contains($env, $key . '=')) {
                $env .= sprintf("%s=%s\n", $key, $value);
            } else if ($old_value) {
                $env = str_replace(sprintf('%s=%s', $key, $old_value), sprintf('%s=%s', $key, $value), $env);
            } else {
                $env = str_replace(sprintf('%s=', $key), sprintf('%s=%s', $key, $value), $env);
            }

            file_put_contents($path, $env);

            Artisan::call('config:cache');
            Artisan::call('optimize:clear');
        } catch (\Exception $e) {
            throw new \Exception('Failed to update environment configuration: ' . $e->getMessage());
        }
    }
}
