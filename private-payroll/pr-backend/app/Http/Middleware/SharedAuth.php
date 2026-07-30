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

        // Debug logging
        Log::info('Payroll SharedAuth Middleware - Parameters:', [
            'employee_no' => $employeeNo,
            'email' => $email,
            'redirect_from' => $redirectFrom,
            'url' => $request->url(),
            'all_params' => $request->all()
        ]);

        if ($employeeNo && $email && $redirectFrom === 'e_portal') {
            // Find user in the shared database
            $user = DB::table('users')
                ->where('employee_no', $employeeNo)
                ->where('email', $email)
                ->first();

            Log::info('Payroll SharedAuth - User found:', ['user' => $user]);

            if ($user) {
                // Log in the user automatically
                $userModel = \App\User::find($user->id);
                if ($userModel) {
                    Auth::login($userModel);
                    Log::info('Payroll SharedAuth - User logged in successfully');

                    // Redirect to remove parameters from URL
                    return redirect()->to($request->url());
                }
            } else {
                Log::warning('Payroll SharedAuth - No user found with provided credentials');
            }
        }

        return $next($request);
    }
}
