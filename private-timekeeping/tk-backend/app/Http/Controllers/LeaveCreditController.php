<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Audit;
use App\LeaveCredit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveCreditController extends Controller
{
    use ApiResponse;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Align controller guard with API routes that use Sanctum
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all leave credit types
     */
    public function index()
    {
        try {
            $leave_types = DB::table('leave_types')->where('active', true)->get();

            return $this->successResponse($leave_types, 'Leave credit types retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve leave credit types: ' . $e->getMessage());
        }
    }

    /**
     * List all leave_credits with employee and leave type info.
     * Includes all leave_credits rows regardless of credits value (including 0).
     * When include_all_employees=1: returns all active employees with their leave credits (0 when no record).
     */
    public function listAll(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");
            $includeAllEmployees = filter_var($request->get('include_all_employees', false), FILTER_VALIDATE_BOOLEAN);

            if ($includeAllEmployees) {
                return $this->listAllEmployeesWithCredits($request, $app_key);
            }

            // Get filter parameters
            $departmentId = $request->get('department_id');
            $employmentTypeId = $request->get('employment_type_id');
            $search = $request->get('search', '');
            $onlyActiveEmployees = $request->get('only_active', true);
            
            // Build the base query (from leave_credits – includes all rows, including credits = 0)
            $query = DB::table('leave_credits as lc')
                ->join('employees as e', 'e.id', '=', 'lc.employee_id')
                ->join('leave_types as lt', 'lt.id', '=', 'lc.leave_type_id')
                ->leftJoin('employment_types as et', 'et.id', '=', 'e.employment_type_id')
                ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
                ->leftJoin('departments as d', 'd.id', '=', 'e.department_id');
            
            if ($onlyActiveEmployees) {
                $query->where('e.active', true)
                      ->where('e.is_employee', true);
            }
            
            if ($departmentId) {
                $query->where('e.department_id', $departmentId);
            }
            
            if ($employmentTypeId) {
                $query->where('e.employment_type_id', $employmentTypeId);
            }
            
            if (!empty($search)) {
                $searchTerm = '%' . $search . '%';
                $query->where(function($q) use ($searchTerm, $app_key) {
                    $q->where('e.employee_no', 'LIKE', $searchTerm);
                    $q->orWhere(function($subQ) use ($searchTerm) {
                        $subQ->where(function($encQ) {
                            $encQ->whereNull('e.is_encrypted')
                                 ->orWhere('e.is_encrypted', 0);
                        })
                        ->where(function($nameQ) use ($searchTerm) {
                            $nameQ->where('e.first_name', 'LIKE', $searchTerm)
                                  ->orWhere('e.last_name', 'LIKE', $searchTerm)
                                  ->orWhere(DB::raw("CONCAT(e.first_name, ' ', e.last_name)"), 'LIKE', $searchTerm);
                        });
                    });
                    // Original (decrypting) name search kept for reference:
                    // $q->orWhereRaw(
                    //     "(ISNULL(e.is_encrypted,0) = 1 AND (" .
                    //     "RTRIM([dbo].[ufn_DecryptString](e.first_name,'" . str_replace("'", "''", $app_key) . "')) LIKE ? OR " .
                    //     "RTRIM([dbo].[ufn_DecryptString](e.last_name,'" . str_replace("'", "''", $app_key) . "')) LIKE ? OR " .
                    //     "(RTRIM([dbo].[ufn_DecryptString](e.first_name,'" . str_replace("'", "''", $app_key) . "'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'" . str_replace("'", "''", $app_key) . "'))) LIKE ?))",
                    //     [$searchTerm, $searchTerm, $searchTerm]
                    // );
                    // Replacement (non-decrypting) name search:
                    $q->orWhereRaw(
                        "(ISNULL(e.is_encrypted,0) = 1 AND (" .
                        "(e.first_name LIKE ? OR e.last_name LIKE ? OR CONCAT(e.first_name,' ',e.last_name) LIKE ?)))",
                        [$searchTerm, $searchTerm, $searchTerm]
                    );
                });
            }
            
            $query->select(
                'lc.id',
                'lc.employee_id',
                'lc.leave_type_id',
                'lc.credits',
                'lc.created_at',
                'lc.updated_at',
                'e.is_encrypted',
                // Original (decrypting) fields kept for reference:
                // DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.first_name ELSE dbo.ufn_DecryptString(e.first_name,'$app_key') END as first_name"),
                // DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.middle_name ELSE dbo.ufn_DecryptString(e.middle_name,'$app_key') END as middle_name"),
                // DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.last_name ELSE dbo.ufn_DecryptString(e.last_name,'$app_key') END as last_name"),
                // DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                //                 CONCAT(e.first_name,' ',e.last_name)
                //             ELSE
                //                 RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                //             END as name"),
                // Replacement (non-decrypting):
                'e.first_name as first_name',
                'e.middle_name as middle_name',
                'e.last_name as last_name',
                DB::raw("CONCAT(e.first_name,' ',e.last_name) as name"),
                'e.employee_no',
                'e.position_id',
                'e.department_id',
                'e.employment_type_id',
                DB::raw("isnull(p.name,'') as position_name"),
                DB::raw("isnull(d.name,'') as department_name"),
                'et.name as employment_type_name',
                'lt.name as leave_type_name'
            );
            
            $query->orderBy('e.employee_no')->orderBy('lc.leave_type_id');

            $export = filter_var($request->get('export', false), FILTER_VALIDATE_BOOLEAN);
            if ($export) {
                $rows = $query->get();
                return $this->successResponse($rows, 'Leave credits loaded');
            }

            $perPage = (int) $request->get('per_page', 25);
            $perPage = min(max($perPage, 1), 100);
            $page = max((int) $request->get('page', 1), 1);
            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            $payload = [
                'data' => $paginator->items(),
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ];
            return $this->successResponse($payload, 'Leave credits loaded');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load leave credits: ' . $e->getMessage());
        }
    }

    /**
     * List all active employees with their leave credits (one row per employee per leave type).
     * Uses 0 for credits when no leave_credits record exists so every employee appears.
     */
    protected function listAllEmployeesWithCredits(Request $request, $app_key)
    {
        $departmentId = $request->get('department_id');
        $employmentTypeId = $request->get('employment_type_id');
        $search = $request->get('search', '');
        $onlyActive = filter_var($request->get('only_active', true), FILTER_VALIDATE_BOOLEAN);
        $export = filter_var($request->get('export', false), FILTER_VALIDATE_BOOLEAN);
        $perPage = (int) $request->get('per_page', 25);
        $perPage = min(max($perPage, 1), 100);
        $page = max((int) $request->get('page', 1), 1);

        $empQuery = DB::table('employees as e')
            ->leftJoin('employment_types as et', 'et.id', '=', 'e.employment_type_id')
            ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
            ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
            ->select(
                'e.id as employee_id',
                'e.photo',
                'e.employee_no',
                'e.position_id',
                'e.department_id',
                'e.employment_type_id',
                'e.is_encrypted',
                // Original (decrypting) fields kept for reference:
                // DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.first_name ELSE dbo.ufn_DecryptString(e.first_name,'$app_key') END as first_name"),
                // DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.middle_name ELSE dbo.ufn_DecryptString(e.middle_name,'$app_key') END as middle_name"),
                // DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.last_name ELSE dbo.ufn_DecryptString(e.last_name,'$app_key') END as last_name"),
                // DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN CONCAT(e.first_name,' ',e.last_name) ELSE RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) END as name"),
                // Replacement (non-decrypting):
                'e.first_name as first_name',
                'e.middle_name as middle_name',
                'e.last_name as last_name',
                DB::raw("CONCAT(e.first_name,' ',e.last_name) as name"),
                DB::raw("isnull(p.name,'') as position_name"),
                DB::raw("isnull(d.name,'') as department_name"),
                'et.name as employment_type_name'
            );

        if ($onlyActive) {
            $empQuery->where('e.active', true)->where('e.is_employee', true);
        }
        if ($departmentId) {
            $empQuery->where('e.department_id', $departmentId);
        }
        if ($employmentTypeId) {
            $empQuery->where('e.employment_type_id', $employmentTypeId);
        }
        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $empQuery->where(function($q) use ($searchTerm, $app_key) {
                $q->where('e.employee_no', 'LIKE', $searchTerm);
                $q->orWhere(function($subQ) use ($searchTerm) {
                    $subQ->where(function($encQ) {
                        $encQ->whereNull('e.is_encrypted')->orWhere('e.is_encrypted', 0);
                    })
                    ->where(function($nameQ) use ($searchTerm) {
                        $nameQ->where('e.first_name', 'LIKE', $searchTerm)
                              ->orWhere('e.last_name', 'LIKE', $searchTerm)
                              ->orWhere(DB::raw("CONCAT(e.first_name, ' ', e.last_name)"), 'LIKE', $searchTerm);
                    });
                });
                // Original (decrypting) name search kept for reference:
                // $q->orWhereRaw(
                //     "(ISNULL(e.is_encrypted,0) = 1 AND (" .
                //     "RTRIM([dbo].[ufn_DecryptString](e.first_name,'" . str_replace("'", "''", $app_key) . "')) LIKE ? OR " .
                //     "RTRIM([dbo].[ufn_DecryptString](e.last_name,'" . str_replace("'", "''", $app_key) . "')) LIKE ? OR " .
                //     "(RTRIM([dbo].[ufn_DecryptString](e.first_name,'" . str_replace("'", "''", $app_key) . "'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'" . str_replace("'", "''", $app_key) . "'))) LIKE ?))",
                //     [$searchTerm, $searchTerm, $searchTerm]
                // );
                // Replacement (non-decrypting) name search:
                $q->orWhereRaw(
                    "(ISNULL(e.is_encrypted,0) = 1 AND (" .
                    "(e.first_name LIKE ? OR e.last_name LIKE ? OR CONCAT(e.first_name,' ',e.last_name) LIKE ?)))",
                    [$searchTerm, $searchTerm, $searchTerm]
                );
            });
        }

        $empQuery->orderBy('e.employee_no');
        $totalEmployees = (clone $empQuery)->count();
        $employees = $export
            ? (clone $empQuery)->get()
            : (clone $empQuery)->offset(($page - 1) * $perPage)->limit($perPage)->get();

        if ($employees->isEmpty()) {
            $payload = [
                'data' => [],
                'total' => 0,
                'current_page' => $page,
                'per_page' => $perPage,
                'last_page' => 1,
            ];
            return $this->successResponse($payload, 'Leave credits loaded');
        }

        $eids = $employees->pluck('employee_id')->toArray();
        $activeLeaveTypes = DB::table('leave_types')->where('active', 1)->orderBy('id')->get();
        $creditsMap = [];
        $allLc = DB::table('leave_credits')
            ->whereIn('employee_id', $eids)
            ->get();
        foreach ($allLc as $lc) {
            $key = $lc->employee_id . '_' . $lc->leave_type_id;
            $creditsMap[$key] = $lc;
        }

        $rows = [];
        foreach ($employees as $emp) {
            foreach ($activeLeaveTypes as $lt) {
                $key = $emp->employee_id . '_' . $lt->id;
                $lc = $creditsMap[$key] ?? null;
                $rows[] = (object) [
                    'id' => $lc ? $lc->id : null,
                    'employee_id' => (int) $emp->employee_id,
                    'leave_type_id' => $lt->id,
                    'credits' => $lc ? (float) $lc->credits : 0,
                    'created_at' => $lc ? $lc->created_at : null,
                    'updated_at' => $lc ? $lc->updated_at : null,
                    'first_name' => $emp->first_name ?? null,
                    'middle_name' => $emp->middle_name ?? null,
                    'last_name' => $emp->last_name ?? null,
                    'name' => $emp->name,
                    'employee_no' => $emp->employee_no,
                    'photo' => $emp->photo ?? null,
                    'position_name' => $emp->position_name,
                    'department_name' => $emp->department_name,
                    'employment_type_name' => $emp->employment_type_name,
                    'leave_type_name' => $lt->name,
                ];
            }
        }

        $lastPage = $totalEmployees > 0 ? (int) ceil($totalEmployees / $perPage) : 1;
        $payload = [
            'data' => $rows,
            'total' => $totalEmployees,
            'current_page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
        ];
        return $this->successResponse($payload, 'Leave credits loaded');
    }

    /**
     * Store leave credits
     */
    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'leave_type_id' => 'required|exists:leave_types,id',
                'id' => 'required|array',
                'id.*' => 'required|exists:employees,id',
                'credits' => 'required|array',
                'credits.*' => 'numeric|min:0'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = $request->all();
            $leave_credits_data = [];

            for ($i = 0; $i < count($data["id"]); $i++) {
                $leave_credits_data = [
                    'employee_id' => $data["id"][$i],
                    'leave_type_id' => $request->leave_type_id,
                    'credits' => isset($data["credits"][$i]) && $data["credits"][$i] > 0 ? $data["credits"][$i] : 0,
                    'updated_at' => now()
                ];

                // Check if record exists
                $existing = DB::table('leave_credits')
                    ->where('employee_id', $data["id"][$i])
                    ->where('leave_type_id', $request->leave_type_id)
                    ->first();

                if ($existing) {
                    // Update existing record with updated_at
                    DB::table('leave_credits')
                        ->where('employee_id', $data["id"][$i])
                        ->where('leave_type_id', $request->leave_type_id)
                        ->update($leave_credits_data);
                } else {
                    // Insert new record with created_at and updated_at
                    $leave_credits_data['created_at'] = now();
                    DB::table('leave_credits')->insert($leave_credits_data);
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Timekeeping Module',
                'menu'    => 'Leave Credits',
                'activity' => 'Update',
                'description' => 'Updated Leave Credit informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Leave credits updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update leave credits: ' . $e->getMessage());
        }
    }

    /**
     * Show specific leave credit
     */
    public function show($id)
    {
        try {
            $data = DB::table('leave_credits')
                ->join('employees', 'leave_credits.employee_id', '=', 'employees.id')
                ->join('leave_types', 'leave_credits.leave_type_id', '=', 'leave_types.id')
                ->select(
                    'leave_credits.*',
                    'employees.first_name',
                    'employees.last_name',
                    'leave_types.name as leave_type_name'
                )
                ->where('leave_credits.id', $id)
                ->first();

            if (!$data) {
                return $this->notFoundResponse('Leave credit not found');
            }

            return $this->successResponse($data, 'Leave credit retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve leave credit: ' . $e->getMessage());
        }
    }

    /**
     * Create new leave credit form data
     */
    public function create()
    {
        try {
            $leave_types = DB::table('leave_types')->where('active', true)->get();
            $employees = DB::table('employees')
                ->where(['active' => true, 'is_employee' => true])
                ->select('id', 'first_name', 'last_name')
                ->get();

            return $this->successResponse([
                'leave_types' => $leave_types,
                'employees' => $employees,
                'fields' => [
                    'leave_type_id' => ['type' => 'select', 'required' => true],
                    'employee_id' => ['type' => 'select', 'required' => true],
                    'credits' => ['type' => 'number', 'required' => true, 'min' => 0]
                ]
            ], 'Create leave credit form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Edit leave credit form data
     */
    public function edit($id)
    {
        try {
            $data = DB::table('leave_credits')
                ->join('employees', 'leave_credits.employee_id', '=', 'employees.id')
                ->join('leave_types', 'leave_credits.leave_type_id', '=', 'leave_types.id')
                ->select(
                    'leave_credits.*',
                    'employees.first_name',
                    'employees.last_name',
                    'leave_types.name as leave_type_name'
                )
                ->where('leave_credits.id', $id)
                ->first();

            if (!$data) {
                return $this->notFoundResponse('Leave credit not found');
            }

            $leave_types = DB::table('leave_types')->where('active', true)->get();
            $employees = DB::table('employees')
                ->where(['active' => true, 'is_employee' => true])
                ->select('id', 'first_name', 'last_name')
                ->get();

            return $this->successResponse([
                'leave_credit' => $data,
                'leave_types' => $leave_types,
                'employees' => $employees
            ], 'Leave credit retrieved for editing');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve leave credit: ' . $e->getMessage());
        }
    }

    /**
     * Update leave credit
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'employee_id' => 'required|exists:employees,id',
                'leave_type_id' => 'required|exists:leave_types,id',
                'credits' => 'required|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $leave_credit = DB::table('leave_credits')->where('id', $id)->first();

            if (!$leave_credit) {
                return $this->notFoundResponse('Leave credit not found');
            }

            $leave_credits_data = [
                'employee_id' => $request->employee_id,
                'leave_type_id' => $request->leave_type_id,
                'credits' => $request->credits,
            ];

            DB::table('leave_credits')->where('id', $id)->update($leave_credits_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Timekeeping Module',
                'menu'    => 'Leave Credits',
                'activity' => 'Update',
                'description' => 'Updated Leave Credit for employee ID: ' . $request->employee_id,
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Leave credit updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update leave credit: ' . $e->getMessage());
        }
    }

    /**
     * Delete leave credit
     */
    public function destroy($id)
    {
        try {
            $leave_credit = DB::table('leave_credits')->where('id', $id)->first();

            if (!$leave_credit) {
                return $this->notFoundResponse('Leave credit not found');
            }

            DB::table('leave_credits')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Timekeeping Module',
                'menu'    => 'Leave Credits',
                'activity' => 'Delete',
                'description' => 'Deleted Leave Credit for employee ID: ' . $leave_credit->employee_id,
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Leave credit deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete leave credit: ' . $e->getMessage());
        }
    }

    /**
     * List all leave beginning balances
     */
    public function listBeginningBalances(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");
            
            // Get filter parameters
            $employeeId = $request->get('employee_id');
            $leaveTypeId = $request->get('leave_type_id');
            $year = $request->get('year');
            $monthId = $request->get('month_id');
            
            $query = DB::table('leave_beginning_balances as lbb')
                ->join('employees as e', 'e.id', '=', 'lbb.employee_id')
                ->join('leave_types as lt', 'lt.id', '=', 'lbb.leave_type_id')
                ->leftJoin('users as u', 'u.id', '=', 'lbb.encoder_id')
                ->select(
                    'lbb.id',
                    'lbb.employee_id',
                    'lbb.leave_type_id',
                    'lbb.month_id',
                    'lbb.year',
                    'lbb.balance_amount',
                    'lbb.encoder_id',
                    'lbb.date_stamp',
                    // Original (decrypting) fields kept for reference:
                    // DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.first_name ELSE dbo.ufn_DecryptString(e.first_name,'$app_key') END as first_name"),
                    // DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.middle_name ELSE dbo.ufn_DecryptString(e.middle_name,'$app_key') END as middle_name"),
                    // DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.last_name ELSE dbo.ufn_DecryptString(e.last_name,'$app_key') END as last_name"),
                    // DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                    //                 CONCAT(e.first_name,' ',e.last_name)
                    //             ELSE
                    //                 RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                    //             END as employee_name"),
                    // Replacement (non-decrypting):
                    'e.first_name as first_name',
                    'e.middle_name as middle_name',
                    'e.last_name as last_name',
                    DB::raw("CONCAT(e.first_name,' ',e.last_name) as employee_name"),
                    'e.employee_no',
                    'lt.name as leave_type_name',
                    DB::raw("CASE WHEN u.id IS NOT NULL THEN u.name ELSE 'System' END as encoder_name")
                );
            
            if ($employeeId) {
                $query->where('lbb.employee_id', $employeeId);
            }
            
            if ($leaveTypeId) {
                $query->where('lbb.leave_type_id', $leaveTypeId);
            }
            
            if ($year) {
                $query->where('lbb.year', $year);
            }
            
            if ($monthId) {
                $query->where('lbb.month_id', $monthId);
            }
            
            $results = $query->orderBy('lbb.year', 'desc')
                            ->orderBy('lbb.month_id', 'desc')
                            ->orderBy('e.employee_no')
                            ->get();
            
            return $this->successResponse($results, 'Leave beginning balances retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve leave beginning balances: ' . $e->getMessage());
        }
    }

    /**
     * Store leave beginning balance
     */
    public function storeBeginningBalance(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'employee_id' => 'required|exists:employees,id',
                'leave_type_id' => 'required|exists:leave_types,id',
                'month_id' => 'required|integer|min:1|max:12',
                'year' => 'required|integer|min:2000|max:2100',
                'balance_amount' => 'required|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Check if beginning balance already exists for this employee, leave type, month, and year
            $existing = DB::table('leave_beginning_balances')
                ->where('employee_id', $request->employee_id)
                ->where('leave_type_id', $request->leave_type_id)
                ->where('month_id', $request->month_id)
                ->where('year', $request->year)
                ->first();

            $data = [
                'employee_id' => $request->employee_id,
                'leave_type_id' => $request->leave_type_id,
                'month_id' => $request->month_id,
                'year' => $request->year,
                'balance_amount' => $request->balance_amount,
                'encoder_id' => Auth::user()->id,
                'date_stamp' => now()
            ];

            if ($existing) {
                // Update existing record
                DB::table('leave_beginning_balances')
                    ->where('id', $existing->id)
                    ->update($data);
                $balanceId = $existing->id;
            } else {
                // Insert new record
                $balanceId = DB::table('leave_beginning_balances')->insertGetId($data);
            }

            // Save audit trail
            $data_audit = [
                'user_id' => Auth::user()->id,
                'module' => 'Timekeeping Module',
                'menu' => 'Leave Credits',
                'activity' => $existing ? 'Update' : 'Create',
                'description' => ($existing ? 'Updated' : 'Created') . ' Leave Beginning Balance for employee ID: ' . $request->employee_id . ', Leave Type: ' . $request->leave_type_id . ', Month: ' . $request->month_id . ', Year: ' . $request->year,
            ];

            Audit::create($data_audit);

            return $this->successResponse(['id' => $balanceId], 'Leave beginning balance saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save leave beginning balance: ' . $e->getMessage());
        }
    }

    /**
     * Store multiple leave beginning balances (bulk)
     */
    public function storeBeginningBalancesBulk(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'balances' => 'required|array',
                'balances.*.employee_id' => 'required|exists:employees,id',
                'balances.*.leave_type_id' => 'required|exists:leave_types,id',
                'balances.*.month_id' => 'required|integer|min:1|max:12',
                'balances.*.year' => 'required|integer|min:2000|max:2100',
                'balances.*.balance_amount' => 'required|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $saved = 0;
            $updated = 0;

            foreach ($request->balances as $balance) {
                $existing = DB::table('leave_beginning_balances')
                    ->where('employee_id', $balance['employee_id'])
                    ->where('leave_type_id', $balance['leave_type_id'])
                    ->where('month_id', $balance['month_id'])
                    ->where('year', $balance['year'])
                    ->first();

                $data = [
                    'employee_id' => $balance['employee_id'],
                    'leave_type_id' => $balance['leave_type_id'],
                    'month_id' => $balance['month_id'],
                    'year' => $balance['year'],
                    'balance_amount' => $balance['balance_amount'],
                    'encoder_id' => Auth::user()->id,
                    'date_stamp' => now()
                ];

                if ($existing) {
                    DB::table('leave_beginning_balances')
                        ->where('id', $existing->id)
                        ->update($data);
                    $updated++;
                } else {
                    DB::table('leave_beginning_balances')->insert($data);
                    $saved++;
                }
            }

            // Save audit trail
            $data_audit = [
                'user_id' => Auth::user()->id,
                'module' => 'Timekeeping Module',
                'menu' => 'Leave Credits',
                'activity' => 'Bulk Update',
                'description' => "Bulk saved $saved new and updated $updated existing leave beginning balances",
            ];

            Audit::create($data_audit);

            return $this->successResponse([
                'saved' => $saved,
                'updated' => $updated,
                'total' => $saved + $updated
            ], 'Leave beginning balances saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save leave beginning balances: ' . $e->getMessage());
        }
    }

    /**
     * Delete leave beginning balance
     */
    public function deleteBeginningBalance($id)
    {
        try {
            $balance = DB::table('leave_beginning_balances')->where('id', $id)->first();

            if (!$balance) {
                return $this->notFoundResponse('Leave beginning balance not found');
            }

            DB::table('leave_beginning_balances')->where('id', $id)->delete();

            // Save audit trail
            $data_audit = [
                'user_id' => Auth::user()->id,
                'module' => 'Timekeeping Module',
                'menu' => 'Leave Credits',
                'activity' => 'Delete',
                'description' => 'Deleted Leave Beginning Balance ID: ' . $id,
            ];

            Audit::create($data_audit);

            return $this->successResponse(null, 'Leave beginning balance deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete leave beginning balance: ' . $e->getMessage());
        }
    }
}
