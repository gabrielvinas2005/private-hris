<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TabAccessController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->unauthorizedResponse('Authentication required');
            }

            // IMPORTANT: visibility is controlled strictly by `access` rows (no admin bypass)
            // and only for the Employee Portal module (module_id = 1).
            $menus = DB::table('access')
                ->join('menus', 'access.menu_id', '=', 'menus.id')
                ->where('access.user_id', $user->id)
                ->where('menus.active', 1)
                ->where('menus.module_id', 1)
                ->where('access.status', 1)
                ->select(
                    'menus.menu_key',
                    'menus.menu',
                    'menus.module_id',
                    DB::raw('access.menu_id as menu_id')
                )
                ->get();

            return $this->successResponse(
                [
                    'menus' => $menus,
                    'user_id' => $user->id,
                ],
                'Tab access retrieved successfully'
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse('Failed to retrieve tab access: ' . $e->getMessage());
        }
    }
}

