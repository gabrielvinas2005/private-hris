<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnnouncementController extends Controller
{
	use ApiResponse;

	public function __construct()
	{
		$this->middleware('auth:sanctum');
	}

	// GET /api/announcements
	public function index()
	{
		try {
			// Determine current employee id via authenticated user
			$employeeId = null;
			$user = Auth::user();
			if ($user && isset($user->employee_no)) {
				$emp = DB::table('employees')->select('id')->where('employee_no', $user->employee_no)->first();
				if ($emp) { $employeeId = $emp->id; }
			}

			// Detect actual table name (some DBs might use singular)
			$table = Schema::hasTable('announcements') ? 'announcements' : (Schema::hasTable('announcement') ? 'announcement' : null);
			if (!$table) {
				return $this->successResponse([], 'No announcements table found');
			}

			// Table given: id, employee_id, Title, Event, Creted_at
			$announcements = DB::table($table)
				->select(
					'id',
					'employee_id',
					'Title',
					DB::raw("Event as content"),
					DB::raw("Creted_at as created_at")
				)
				->when(!is_null($employeeId), function ($q) use ($employeeId) {
					$q->where(function ($qq) use ($employeeId) {
						$qq->where('employee_id', $employeeId)
						   ->orWhereNull('employee_id')
						   ->orWhere('employee_id', 0);
					});
				}, function ($q) {
					$q->where(function ($qq) {
						$qq->whereNull('employee_id')->orWhere('employee_id', 0);
					});
				})
				->orderBy(DB::raw('Creted_at'), 'desc')
				->limit(10)
				->get();

			return $this->successResponse($announcements, 'Announcements retrieved successfully');
		} catch (\Exception $e) {
			return $this->serverErrorResponse('Failed to retrieve announcements: ' . $e->getMessage());
		}
	}

	// POST /api/announcements
	public function store(Request $request)
	{
		try {
			// Check if user has HR or Admin access
			$user = Auth::user();
			if (!$user) {
				return $this->errorResponse('Unauthorized', 401);
			}

			// Check HR or Admin access
			$user_record = DB::table('users')
				->where('employee_no', $user->employee_no)
				->first();

			$hasAccess = false;
			if ($user_record) {
				$hasAccess = (bool)($user_record->with_hrm_access ?? false) 
					|| (bool)($user_record->is_admin ?? false)
					|| ($user_record->user_type_id ?? null) == 1
					|| in_array(strtolower($user_record->role ?? ''), ['admin', 'hr', 'administrator', 'hr_admin']);
			}
			
			if (!$hasAccess) {
				$hasAccess = (bool)($user->with_hrm_access ?? false) 
					|| (bool)($user->is_admin ?? false)
					|| ($user->user_type_id ?? null) == 1
					|| in_array(strtolower($user->role ?? ''), ['admin', 'hr', 'administrator', 'hr_admin']);
			}

			if (!$hasAccess) {
				return $this->errorResponse('Access denied. Admin or HR access required to make announcements.', 403);
			}

			// Validate request
			$validator = Validator::make($request->all(), [
				'title' => 'required|string|max:255',
				'content' => 'required|string',
				'employee_id' => 'nullable|integer', // If null or 0, it's a global announcement
			]);

			if ($validator->fails()) {
				return $this->errorResponse($validator->errors()->first(), 422);
			}

			// Detect actual table name
			$table = Schema::hasTable('announcements') ? 'announcements' : (Schema::hasTable('announcement') ? 'announcement' : null);
			if (!$table) {
				return $this->errorResponse('Announcements table not found', 500);
			}

			// Get employee_id if provided, otherwise set to null/0 for global announcement
			$employeeId = $request->input('employee_id');
			if (empty($employeeId) || $employeeId == 0) {
				$employeeId = null;
			}

			// Insert announcement
			$id = DB::table($table)->insertGetId([
				'Title' => $request->input('title'),
				'Event' => $request->input('content'),
				'employee_id' => $employeeId,
				'Creted_at' => now(),
			]);

			return $this->successResponse(['id' => $id], 'Announcement created successfully');
		} catch (\Exception $e) {
			return $this->serverErrorResponse('Failed to create announcement: ' . $e->getMessage());
		}
	}

	// GET /api/announcements/employees - Get employees list for personal announcements
	public function getEmployees()
	{
		try {
			// Check if user has HR or Admin access
			$user = Auth::user();
			if (!$user) {
				return $this->errorResponse('Unauthorized', 401);
			}

			// Check HR or Admin access
			$user_record = DB::table('users')
				->where('employee_no', $user->employee_no)
				->first();

			$hasAccess = false;
			if ($user_record) {
				$hasAccess = (bool)($user_record->with_hrm_access ?? false) 
					|| (bool)($user_record->is_admin ?? false)
					|| ($user_record->user_type_id ?? null) == 1
					|| in_array(strtolower($user_record->role ?? ''), ['admin', 'hr', 'administrator', 'hr_admin']);
			}
			
			if (!$hasAccess) {
				$hasAccess = (bool)($user->with_hrm_access ?? false) 
					|| (bool)($user->is_admin ?? false)
					|| ($user->user_type_id ?? null) == 1
					|| in_array(strtolower($user->role ?? ''), ['admin', 'hr', 'administrator', 'hr_admin']);
			}

			if (!$hasAccess) {
				return $this->errorResponse('Access denied. Admin or HR access required.', 403);
			}

			$app_key = env("APP_KEY", "");

			// Get active employees
			$employees = DB::table('employees as a')
				->leftJoin('name_prefixes as b', 'a.name_prefix_id', '=', 'b.id')
				->leftJoin('name_suffixes as c', 'a.name_suffix_id', '=', 'c.id')
				->select(
					'a.id',
					'a.employee_no',
					DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
						CONCAT(COALESCE(b.name,''), ' ', a.first_name, ' ', COALESCE(a.middle_name,''), ' ', a.last_name, ' ', COALESCE(c.name,''))
					ELSE
						CONCAT(COALESCE(b.name,''), ' ', RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key')), ' ',
						RTRIM(COALESCE([dbo].[ufn_DecryptString](a.middle_name,'$app_key'),'')), ' ',
						RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')), ' ', COALESCE(c.name,''))
					END as name")
				)
				->where('a.is_employee', true)
				->where('a.active', true)
				->orderBy('a.last_name', 'asc')
				->get();

			return $this->successResponse($employees, 'Employees retrieved successfully');
		} catch (\Exception $e) {
			return $this->serverErrorResponse('Failed to retrieve employees: ' . $e->getMessage());
		}
	}
}
