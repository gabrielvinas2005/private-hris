<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class AuditController extends Controller
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
            // Return recent activities for dashboard list
            $data = DB::table('audits')
                ->leftJoin('users', 'users.id', '=', 'audits.user_id')
                ->select(
                    'users.photo',
                    'users.name',
                    'audits.module',
                    'audits.menu',
                    'audits.activity',
                    'audits.description',
                    'audits.created_at',
                    'audits.updated_at'
                )
                ->orderBy('audits.created_at', 'desc')
                ->limit(20)
                ->get();

            return $this->successResponse($data, 'Audit records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load audit page: ' . $e->getMessage());
        }
    }

    public function lazyGet(Request $request)
    {
        try {
            $limit = $request->length;
            $offset = $request->start;
            $search = $request->search['value'] ?? ''; // Extract search value with fallback

            $startDate = $request->startDate;
            $endDate = $request->endDate;

            $data = DB::table('users')
                ->join('audits', 'users.id', '=', 'audits.user_id')
                ->select(
                    'users.photo',
                    'users.name',
                    'audits.module',
                    'audits.menu',
                    'audits.activity',
                    'audits.description',
                    'audits.created_at',
                    'audits.updated_at'
                );

            if ($startDate && $endDate && $startDate != '') {
                $data = $data->whereDate('audits.created_at', '>=', $startDate)
                    ->whereDate('audits.created_at', '<=', $endDate);
            }

            // Apply search query if provided
            if (!empty($search)) {
                $data = $data->where(function ($query) use ($search) {
                    $query->where(DB::raw('LOWER(users.name)'), 'like', "%" . strtolower($search) . "%")
                        ->orWhere('audits.module', 'like', "%$search%")
                        ->orWhere('audits.menu', 'like', "%$search%")
                        ->orWhere('audits.activity', 'like', "%$search%")
                        ->orWhere('audits.description', 'like', "%$search%");
                });
            }

            // Count total records before applying pagination
            $totalRecords = $data->count();

            // Apply pagination
            $data = $data->orderBy('audits.created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            return $this->successResponse([
                'data' => $data,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords, // For server-side processing, these should match
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'hasMore' => ($offset + $limit) < $totalRecords
                ]
            ], 'Audit records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve audit records: ' . $e->getMessage());
        }
    }

    /**
     * Get paginated audit trail for a specific user (User Activities)
     */
    public function userActivities(Request $request, $userId)
    {
        try {
            $limit = $request->length ?? 10;
            $offset = $request->start ?? 0;
            $search = $request->search['value'] ?? '';
            $startDate = $request->startDate;
            $endDate = $request->endDate;

            $data = DB::table('users')
                ->join('audits', 'users.id', '=', 'audits.user_id')
                ->select(
                    'users.photo',
                    'users.name',
                    'audits.module',
                    'audits.menu',
                    'audits.activity',
                    'audits.description',
                    'audits.created_at',
                    'audits.updated_at'
                )
                ->where('audits.user_id', $userId);

            if ($startDate && $endDate && $startDate != '') {
                $data = $data->whereDate('audits.created_at', '>=', $startDate)
                    ->whereDate('audits.created_at', '<=', $endDate);
            }

            if (!empty($search)) {
                $data = $data->where(function ($query) use ($search) {
                    $query->where(DB::raw('LOWER(users.name)'), 'like', '%' . strtolower($search) . '%')
                        ->orWhere('audits.module', 'like', "%$search%")
                        ->orWhere('audits.menu', 'like', "%$search%")
                        ->orWhere('audits.activity', 'like', "%$search%")
                        ->orWhere('audits.description', 'like', "%$search%");
                });
            }

            $totalRecords = $data->count();

            $data = $data->orderBy('audits.created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            return $this->successResponse([
                'data' => $data,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'hasMore' => ($offset + $limit) < $totalRecords
                ]
            ], 'User activities retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve user activities: ' . $e->getMessage());
        }
    }
}
