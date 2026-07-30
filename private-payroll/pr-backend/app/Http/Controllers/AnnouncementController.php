<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

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
}
