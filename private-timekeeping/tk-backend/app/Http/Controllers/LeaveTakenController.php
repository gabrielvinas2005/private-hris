<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveTakenController extends Controller
{
    public function index(Request $request)
    {
        $app_key = env("APP_KEY", "");
        $search = $request->input('search', '');
        $departmentId = $request->input('department_id');
        $positionId = $request->input('position_id');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);

        $query = DB::table('employees as a')
            ->join('positions as b', 'a.position_id', '=', 'b.id')
            ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
            ->select(
                'a.id',
                'a.photo',
                'a.employee_no',
                // Original (decrypting) name selection kept for reference:
                // DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                //                 CONCAT(a.first_name,' ',a.last_name)
                //             ELSE
                //                 RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                //             END as name"),
                // Replacement (non-decrypting):
                DB::raw("CONCAT(a.first_name,' ',a.last_name) as name"),
                'b.name as position',
                DB::raw('c.name as department_name'),
                DB::raw('c.name as department')
            )
            ->where([
                'a.active' => true,
                'a.is_employee' => true
            ]);

        // Add search functionality
        if (!empty($search)) {
            $query->where(function ($q) use ($search, $app_key) {
                $q->where('a.employee_no', 'LIKE', '%' . $search . '%')
                  ->orWhere('b.name', 'LIKE', '%' . $search . '%')
                  ->orWhere('c.name', 'LIKE', '%' . $search . '%')
                  // Original (decrypting) name search kept for reference:
                  // ->orWhereRaw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                  //                 CONCAT(a.first_name,' ',a.last_name)
                  //             ELSE
                  //                 RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                  //             END LIKE ?", ['%' . $search . '%']);
                  // Replacement (non-decrypting):
                  ->orWhereRaw("CONCAT(a.first_name,' ',a.last_name) LIKE ?", ['%' . $search . '%']);
            });
        }

        // Apply department filter
        if (!empty($departmentId) && is_numeric($departmentId)) {
            $query->where('a.department_id', (int) $departmentId);
        }

        // Apply position filter
        if (!empty($positionId) && is_numeric($positionId)) {
            $query->where('a.position_id', (int) $positionId);
        }

        // Get total count for pagination
        $total = $query->count();
        
        // Apply pagination
        $employees = $query->orderBy('name', 'asc')
                          ->offset(($page - 1) * $perPage)
                          ->limit($perPage)
                          ->get();

        // Calculate pagination info
        $lastPage = ceil($total / $perPage);
        $from = $total > 0 ? (($page - 1) * $perPage) + 1 : 0;
        $to = min($page * $perPage, $total);

        $pagination = [
            'current_page' => (int) $page,
            'per_page' => (int) $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'has_more_pages' => $page < $lastPage,
            'from' => $from,
            'to' => $to
        ];

        // Return data in the format expected by the frontend service
        return $this->successResponse([
            'data' => $employees,
            'pagination' => $pagination
        ], 'Leave taken list retrieved successfully');
    }

    public function load($id)
    {
        $app_key = env("APP_KEY", "");

        $year = Carbon::now()->format('Y');

        $data = DB::table('employees as a')
            ->join('positions as b', 'a.position_id', '=', 'b.id')
            ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
            ->select(
                'a.id',
                // Original (decrypting) name selection kept for reference:
                // DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                //                 CONCAT(a.first_name,' ',a.last_name)
                //             ELSE
                //                 RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                //             END as name"),
                // Replacement (non-decrypting):
                DB::raw("CONCAT(a.first_name,' ',a.last_name) as name"),
                'b.name as position',
                DB::raw('c.name as department_name'),
                DB::raw('c.name as department')
            )
            ->where('a.id', $id)
            ->get();

        $leave_available = DB::table('leave_credits as a')
            ->join('leave_types as b', 'a.leave_type_id', '=', 'b.id')
            ->select(
                'b.name as leave_types',
                DB::raw("cast(0 as decimal(18,3)) as leave_taken"),
                'a.credits as leave_balance',
                'b.id as leave_type_id'
            )
            ->where('a.employee_id', $id)
            ->whereNotIn('b.id', function ($query) use ($id, $year) {
                $query->select('a.leave_type_id')->from('leave_headers as a')
                    ->join('leave_details as b', 'b.leave_id', '=', 'a.id')
                    ->where('a.employee_id', $id)
                    ->whereYear('b.leave_date', $year);
            });

        $leaves = DB::table('leave_headers as a')
            ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
            ->join('leave_credits as c', function ($join) {
                $join->on('a.employee_id', '=', 'c.employee_id')
                    ->on('a.leave_type_id', '=', 'c.leave_type_id');
            })
            ->join('leave_types as d', 'c.leave_type_id', '=', 'd.id')
            ->select(
                'd.name as leave_types',
                DB::raw("sum(isnull(b.with_pay,0)) as leave_taken"),
                'c.credits as leave_balance',
                'd.id as leave_type_id'
            )
            ->where('a.employee_id', $id)
            ->whereYear('b.leave_date', $year)
            ->groupBy(
                'd.name',
                'c.credits',
                'd.id'
            )
            ->unionAll($leave_available)
            ->get();

        return $this->successResponse([
            'data' => $data,
            'leaves' => $leaves
        ], 'Leave taken data retrieved successfully');
    }
}
