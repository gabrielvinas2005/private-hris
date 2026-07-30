<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
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
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            if (Auth::user()->has_change_password == false) {
                Auth::logout();
                return $this->unauthorizedResponse('Please reset your password first before login.');
            }

            return $this->successResponse([
                'user' => Auth::user(),
                'dashboard_url' => '/dashboard'
            ], 'Dashboard loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load dashboard: ' . $e->getMessage());
        }
    }
}
