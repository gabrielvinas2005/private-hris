<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveCreditCardController extends Controller
{
    public function index()
    {
        $app_key = env("APP_KEY", "");

        $employees = DB::table('employees as a')
            ->join('positions as b', 'a.position_id', '=', 'b.id')
            ->select(
                'a.id',
                'a.photo',
                'a.employee_no',
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                'b.name as position'
            )
            ->where([
                'a.active' => true,
                'a.is_employee' => true
            ])
            ->orderBy('name', 'asc')
            ->get();

        return $this->successResponse($employees, 'Leave credit card list retrieved successfully');
    }

    public function details($id)
    {
        $app_key = env("APP_KEY", "");

        $year = Carbon::now()->format('Y');

        $data = DB::table('employees as a')
            ->join('positions as b', 'a.position_id', '=', 'b.id')
            ->select(
                'a.id',
                DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                'b.name as position'
            )
            ->where('a.id', $id)
            ->get();

        $leave_credit_cards = DB::select("
        select
            CASE WHEN (CAST((DATEDIFF(DAY,
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2 - 1, 0)),
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2, 0))
                    )) as int) - (SUM(a.absent) + SUM(a.late) + SUM(a.undertime))) <= 0 THEN CAST(0 AS INT) 
                ELSE 
                    (CAST((DATEDIFF(DAY,
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2 - 1, 0)),
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2, 0))
                    )) as int) - (SUM(a.absent) + SUM(a.late) + SUM(a.undertime)))
                END as days_present,
            DATEPART(M,a.date) as month_id,
            DATEname(M,a.date) as month,
            SUM(a.leave) as leave,
            SUM(a.absent) as absent,
            SUM(a.late) as late,
            SUM(a.undertime) as undertime,
            isnull((select leave_earned from leave_earnings where days_present = (DATEDIFF(DAY,
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2 - 1, 0)),
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2, 0))
                    ) - (SUM(a.absent) + SUM(a.late) + SUM(a.undertime)))),0) as leave_earned
        from time_data a 
        where a.employee_id = $id and YEAR(a.date) = $year
        group by DATEPART(M,a.date),DATEname(M,a.date)
        order by DATEPART(M,a.date) asc
        ");

        return $this->successResponse([
            'data' => $data,
            'leave_credit_cards' => $leave_credit_cards
        ], 'Leave credit card data retrieved successfully');
    }

    public function getLeaveCredits(int $id, int $year)
    {
        $leave_credit_cards = DB::select("
        select
            CASE WHEN (CAST((DATEDIFF(DAY,
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2 - 1, 0)),
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2, 0))
                    )) as int) - (SUM(a.absent) + SUM(a.late) + SUM(a.undertime))) <= 0 THEN CAST(0 AS INT) 
                ELSE 
                    (CAST((DATEDIFF(DAY,
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2 - 1, 0)),
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2, 0))
                    )) as int) - (SUM(a.absent) + SUM(a.late) + SUM(a.undertime)))
                END as days_present,
            DATEPART(M,a.date) as month_id,
            DATEname(M,a.date) as month,
            SUM(a.leave) as leave,
            SUM(a.absent) as absent,
            SUM(a.late) as late,
            SUM(a.undertime) as undertime,
            isnull((select leave_earned from leave_earnings where days_present = (DATEDIFF(DAY,
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2 - 1, 0)),
                        DATEADD(DAY, 0, DATEADD(m, (($year - 1900) * CAST(DATEPART(M,a.date) AS INT)) + 2, 0))
                    ) - (SUM(a.absent) + SUM(a.late) + SUM(a.undertime)))),0) as leave_earned
        from time_data a 
        where a.employee_id = $id and YEAR(a.date) = $year
        group by DATEPART(M,a.date),DATEname(M,a.date)
        order by DATEPART(M,a.date) asc
        ");

        return $this->successResponse($leave_credit_cards, 'Leave credit card table retrieved successfully');
    }
}
