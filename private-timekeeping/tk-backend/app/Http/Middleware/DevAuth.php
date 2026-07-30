<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\User;

class DevAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Only activate in development environment
        if (!app()->environment('local', 'development', 'dev')) {
            return $next($request);
        }

        // Check if dev mode is enabled via configuration
        if (!config('auth.dev_auth_enabled', false)) {
            return $next($request);
        }

        // OPTIMIZATION: Check if user is already authenticated FIRST
        // This prevents unnecessary session operations on every auto-refresh request
        // This is especially important for biometrics auto-refresh (every 5 seconds)
        if (Auth::check()) {
            // User is already logged in, no need to do anything
            // Skip all session manipulation and just proceed with the request
            return $next($request);
        }

        // Only reach here if user is NOT authenticated yet
        // Start session early to ensure session ID is available
        if (!$request->hasSession()) {
            $request->setLaravelSession(app('session.store'));
        }
        
        // Force start session to make sure it's persisted
        $session = $request->session();
        $session->start();

        // Check if SharedAuth is handling the request (has shared auth parameters)
        $employeeNo = $request->get('employee_no');
        $email = $request->get('email');
        $redirectFrom = $request->get('redirect_from');
        
        if ($employeeNo && $email && $redirectFrom === 'e_portal') {
            // SharedAuth is handling this request, skip DevAuth
            return $next($request);
        }

        // Only reach here if user is NOT authenticated
        // Find the Administrator user (is_admin = 1) ONCE
        $adminUser = User::where('is_admin', 1)->first();

        if (!$adminUser) {
            Log::warning('DevAuth Middleware - No admin user found with is_admin = 1');
            return $next($request);
        }

        // Check if admin user is active and not locked
        if (isset($adminUser->active) && (int) $adminUser->active === 0) {
            Log::warning('DevAuth Middleware - Admin user is inactive');
            return $next($request);
        }

        if ($adminUser->locked) {
            Log::warning('DevAuth Middleware - Admin user is locked');
            return $next($request);
        }

        // Check if admin user has expired
        if ($adminUser->with_expiration && $adminUser->expiration_date <= now()) {
            Log::warning('DevAuth Middleware - Admin user has expired');
            return $next($request);
        }

        // Log in the admin user automatically (ONLY ONCE per session)
        // Auth::login() with 'true' parameter sets "remember me" cookie
        // This ensures the session persists across requests
        Auth::login($adminUser, true);
        
        // Save the session explicitly to ensure it's written
        $session->save();

        return $next($request);
    }
}
