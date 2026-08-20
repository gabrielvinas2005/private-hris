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
        // Check if user is already authenticated
        if (Auth::check()) {
            Log::info('Control Panel SharedAuth - User already authenticated');
            return $next($request);
        }

        $employeeNo = $request->get('employee_no');
        $email = $request->get('email');
        $redirectFrom = $request->get('redirect_from');

        // Debug logging
        Log::info('Control Panel SharedAuth Middleware - Parameters:', [
            'employee_no' => $employeeNo,
            'email' => $email,
            'redirect_from' => $redirectFrom,
            'url' => $request->url(),
            'all_params' => $request->all(),
            'is_authenticated' => Auth::check()
        ]);

        if ($employeeNo && $email && $redirectFrom === 'e_portal') {
            // Try to find user by email first (more reliable)
            $user = DB::table('users')
                ->where('email', $email)
                ->first();

            // If not found by email, try by employee_no
            if (!$user) {
                $user = DB::table('users')
                    ->where('employee_no', $employeeNo)
                    ->first();
            }

            // If still not found, try by ID (for admin users)
            if (!$user && is_numeric($employeeNo)) {
                $user = DB::table('users')
                    ->where('id', $employeeNo)
                    ->first();
            }

            Log::info('Control Panel SharedAuth - User found:', ['user' => $user]);

            if ($user) {
                // Check if user has Control Panel access permission
                $hasCpAccess = !empty($user->with_cpm_access) || !empty($user->is_admin);
                if (!$hasCpAccess) {
                    Log::warning('Control Panel SharedAuth - User lacks Control Panel permissions:', ['email' => $email]);
                    return $next($request);
                }

                // Log in the user automatically
                $userModel = \App\User::find($user->id);
                if ($userModel) {
                    Auth::login($userModel);

                    // Generate a real Sanctum token for API access
                    $token = $userModel->createToken('shared-auth-' . time())->plainTextToken;

                    Log::info('Control Panel SharedAuth - User logged in successfully', [
                        'user_id' => $userModel->id,
                        'token_prefix' => substr($token, 0, 10) . '...'
                    ]);

                    // Store the token and user data in session
                    session([
                        'api_token' => $token,
                        'user_data' => $userModel->toArray()
                    ]);

                    // Redirect to remove parameters from URL
                    return redirect()->to($request->url());
                }
            } else {
                Log::warning('Control Panel SharedAuth - No user found with provided credentials');
            }
        }

        return $next($request);
    }
}
