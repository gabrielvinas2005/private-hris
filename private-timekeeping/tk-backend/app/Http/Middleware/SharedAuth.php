<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SharedAuth
{
    public function handle(Request $request, Closure $next)
    {
        $employeeNo = $request->get('employee_no');
        $email = $request->get('email');
        $redirectFrom = $request->get('redirect_from');

        // Only process if we have shared auth parameters
        if (!$employeeNo || !$email || $redirectFrom !== 'e_portal') {
            return $next($request);
        }

        // Debug logging for shared auth flow
        Log::info('Timekeeping SharedAuth Middleware - Processing shared auth:', [
            'employee_no' => $employeeNo,
            'email' => $email,
            'redirect_from' => $redirectFrom,
            'url' => $request->url()
        ]);

        if ($employeeNo && $email && $redirectFrom === 'e_portal') {
            // Find user in the shared database
            $user = DB::table('users')
                ->where('employee_no', $employeeNo)
                ->where('email', $email)
                ->first();

            Log::info('Timekeeping SharedAuth - User found:', ['user' => $user]);

            if ($user) {
                // Log in the user automatically
                $userModel = \App\User::find($user->id);
                if ($userModel) {
                    Auth::login($userModel);
                    Log::info('Timekeeping SharedAuth - User logged in successfully (SHARED AUTH)', [
                        'user_id' => $userModel->id,
                        'employee_no' => $userModel->employee_no,
                        'email' => $userModel->email,
                        'auth_type' => 'shared'
                    ]);

                    // Redirect to remove parameters from URL
                    return redirect()->to($request->url());
                }
            } else {
                Log::warning('Timekeeping SharedAuth - No user found with provided credentials');
            }
        }

        return $next($request);
    }
}
