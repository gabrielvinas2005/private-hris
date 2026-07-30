<?php

namespace App\Http\Controllers;

use Auth;
use Image;
use App\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class LengthofServiceController extends Controller
{
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
        $app_key = env("APP_KEY", "");

        if (Auth::user()->access_all_branches) {
            $data = DB::table('employees')
                ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                ->join('departments', 'departments.id', '=', 'employees.department_id')
                ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->select(
                    'employees.photo',
                    'employees.id',
                    'employees.employee_no',
                    'employees.access_no',
                    'employees.email',
                    'employees.date_hired',
                    'employment_types.name as employment_type',
                    'positions.name as position',
                    'departments.name as department',
                    'branches.name as branch',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                CONCAT(employees.first_name,' ',employees.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                            END as name"),
                    DB::raw("CONVERT(decimal(18,2),ROUND(DATEDIFF(MONTH, employees.date_hired, CURRENT_TIMESTAMP) / 12.00, 1)) as length"),
                    DB::raw("CASE 
                    WHEN DATEDIFF(MONTH, employees.date_hired, CURRENT_TIMESTAMP) < 12
                        THEN STR(DATEDIFF(MONTH, employees.date_hired, CURRENT_TIMESTAMP)) + ' Month/s'
                    ELSE LTRIM(STR((DATEDIFF(MONTH, employees.date_hired, CURRENT_TIMESTAMP) / 12))) + ' Year/s and ' + LTRIM(STR(DATEDIFF(MONTH, employees.date_hired, CURRENT_TIMESTAMP) - ((DATEDIFF(MONTH, employees.date_hired, CURRENT_TIMESTAMP) / 12) * 12))) + ' Month/s'
                    END AS lenth")
                )
                ->orderBy('employees.first_name', 'asc')
                ->where([
                    'employees.is_employee' => true,
                    'employees.active' => true
                ])
                ->get();
        } else {
            $user_branch_id = DB::table('users as a')
                ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                ->select('b.branch_id')
                ->where('a.id', Auth::user()->id)
                ->get();

            $data = DB::table('employees')
                ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                ->join('departments', 'departments.id', '=', 'employees.department_id')
                ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->select(
                    'employees.photo',
                    'employees.id',
                    'employees.employee_no',
                    'employees.access_no',
                    'employees.email',
                    'employees.date_hired',
                    'employment_types.name as employment_type',
                    'positions.name as position',
                    'departments.name as department',
                    'branches.name as branch',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                CONCAT(employees.first_name,' ',employees.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')) 
                            END as name"),
                    DB::raw("CONVERT(decimal(18,2),ROUND(DATEDIFF(MONTH, employees.date_hired, CURRENT_TIMESTAMP) / 12.00, 1)) as length"),
                    DB::raw("CASE 
                    WHEN DATEDIFF(MONTH, employees.date_hired, CURRENT_TIMESTAMP) < 12
                        THEN STR(DATEDIFF(MONTH, employees.date_hired, CURRENT_TIMESTAMP)) + ' Month/s'
                    ELSE LTRIM(STR((DATEDIFF(MONTH, employees.date_hired, CURRENT_TIMESTAMP) / 12))) + ' Year/s and ' + LTRIM(STR(DATEDIFF(MONTH, employees.date_hired, CURRENT_TIMESTAMP) - ((DATEDIFF(MONTH, employees.date_hired, CURRENT_TIMESTAMP) / 12) * 12))) + ' Month/s'
                    END AS lenth")
                )
                ->orderBy('employees.first_name', 'asc')
                ->where([
                    'employees.is_employee' => true,
                    'employees.active' => true,
                    'employees.branch_id' => $user_branch_id[0]->branch_id
                ])
                ->get();
        }

        return $this->successResponse($data, 'Length of service records retrieved successfully');
    }
}
