<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class MonthsController extends Controller
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

    /**
     * Get all months reference data
     */
    public function index()
    {
        try {
            $months = DB::table('months')
                ->orderBy('id', 'asc')
                ->get();

            return $this->successResponse($months, 'Months data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve months data: ' . $e->getMessage());
        }
    }
}

