<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Notification;
use App\Notifications\EmailUserVerificationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Login user and return token
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $credentials = $request->only('email', 'password');

            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                $isAdmin = (int) ($user->is_admin ?? 0) === 1;
                $hasEpAccess = (bool) ($user->with_ep_access ?? false);

                // Allow admins regardless of other flags
                if (!$isAdmin && !$hasEpAccess) {
                    // Enforce employee-only login using employees table and block applicants
                    if ((int) ($user->is_applicant ?? 0) === 1) {
                        Auth::logout();
                        return $this->errorResponse('Applicant accounts cannot log in to this portal.', 401);
                    }

                    $employee = DB::table('employees')
                        ->where('employee_no', $user->employee_no)
                        ->select('is_employee', 'active')
                        ->first();

                    if (!$employee || (int) ($employee->is_employee ?? 0) !== 1) {
                        Auth::logout();
                        return $this->errorResponse('Your account is not an employee. Please contact the system administrator.', 401);
                    }
                }
                if ((int) ($user->is_applicant ?? 0) === 1) {
                    Auth::logout();
                    return $this->errorResponse('Applicant accounts cannot log in to this portal.', 401);
                }

                // Check if user is active (treat null as active)
                if (isset($user->active) && (int) $user->active === 0) {
                    return $this->errorResponse('Account is inactive', 401);
                }

                // Check if user has e-portal access (admin users can bypass this check)
                if (!(bool) ($user->is_admin ?? false) && !(bool) ($user->with_ep_access ?? false)) {
                    return $this->errorResponse('You do not have access to the e-portal. Please contact the system administrator.', 401);
                }

                // Check if user is locked
                if ($user->locked) {
                    return $this->errorResponse('Account is locked', 401);
                }

                // Check if user has expired
                if ($user->with_expiration && $user->expiration_date <= now()) {
                    return $this->errorResponse('Account has expired', 401);
                }

                // Revoke all existing tokens to enforce single active session per user.
                // If another browser/device is logged in, it will get a 401 on the next request.
                try {
                    /** @var \Laravel\Sanctum\HasApiTokens $user */
                    $user->tokens()->delete();
                } catch (\Exception $revokeError) {
                    Log::warning('Could not revoke existing tokens on login: ' . $revokeError->getMessage());
                }

                try {
                    /** @var \Laravel\Sanctum\HasApiTokens $user */
                    $token = $user->createToken('api-token')->plainTextToken;
                } catch (\Exception $tokenError) {
                    // Fallback to hash-based token if Sanctum fails
                    $token = hash('sha256', $user->id . time() . config('app.key'));
                    Log::error('Failed to create Sanctum token: ' . $tokenError->getMessage());
                }

                // Build base response payload
                $payload = [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'photo' => $user->photo,
                        'employee_no' => $user->employee_no,
                        'is_applicant' => (bool) ($user->is_applicant ?? false),
                        'has_change_password' => (bool) ($user->has_change_password ?? false),
                        'is_admin' => (bool) ($user->is_admin ?? false),
                        'with_hrm_access' => (bool) ($user->with_hrm_access ?? false),
                        'with_hrt_access' => (bool) ($user->with_hrt_access ?? false),
                        'with_hrp_access' => (bool) ($user->with_hrp_access ?? false),
                        'with_cpm_access' => (bool) ($user->with_cpm_access ?? false),
                        'with_ep_access' => (bool) ($user->with_ep_access ?? false),
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'requires_otp' => false,
                    'next' => '/dashboard'
                ];

                // OTP and email verification for first-time employee login
                // If user has not changed password yet, send OTP first for validation
                if (!(bool) ($user->has_change_password ?? false)) {
                    // Generate OTP for password change validation
                    $otp = rand(100000, 999999);
                    $otpHash = Hash::make($otp);
                    User::where('id', $user->id)->update(['otp_code' => $otpHash]);

                    // Send OTP via email - wrap in try-catch to handle email sending errors
                    try {
                        $userAccount = User::where('id', $user->id)->get();
                        Log::info('Attempting to send OTP email to: ' . $user->email . ' | DEV OTP CODE: ' . $otp);
                        Notification::send($userAccount, new EmailUserVerificationNotification($userAccount, $otp));
                        Log::info('OTP email sent successfully to: ' . $user->email . ' | DEV OTP CODE: ' . $otp);
                    } catch (\Exception $emailError) {
                        // Log email error but don't fail the login
                        Log::error('Failed to send OTP email to ' . $user->email . ': ' . $emailError->getMessage());
                        Log::error('Email error trace: ' . $emailError->getTraceAsString());
                    }

                    $payload['requires_otp'] = true;
                    $payload['next'] = '/verify-otp';
                } else {
                    // User has changed password, proceed to dashboard
                    $payload['next'] = '/dashboard';
                }

                return $this->successResponse($payload, 'Login successful');
            } else {
                // Fallback: manually verify user credentials in case default guard prevents attempt()
                $user = User::where('email', $request->email)->first();
                if ($user && Hash::check($request->password, $user->password)) {
                    $isAdmin = (int) ($user->is_admin ?? 0) === 1;
                    $hasEpAccess = (bool) ($user->with_ep_access ?? false);

                    // Allow admins regardless of other flags
                    if (!$isAdmin && !$hasEpAccess) {
                        // Enforce employee-only login using employees table and block applicants prior to logging in
                        if ((int) ($user->is_applicant ?? 0) === 1) {
                            return $this->errorResponse('Applicant accounts cannot log in to this portal.', 401);
                        }

                        $employee = DB::table('employees')
                            ->where('employee_no', $user->employee_no)
                            ->select('is_employee', 'active')
                            ->first();

                        if (!$employee || (int) ($employee->is_employee ?? 0) !== 1) {
                            return $this->errorResponse('Your account is not an employee. Please contact the system administrator.', 401);
                        }
                    }

                    Auth::login($user);
                    // Re-run the same success branch as above

                    // Check if user is active (treat null as active)
                    if (isset($user->active) && (int) $user->active === 0) {
                        return $this->errorResponse('Account is inactive', 401);
                    }

                    // Check if user is locked
                    if ($user->locked) {
                        return $this->errorResponse('Account is locked', 401);
                    }

                    // Check if user has e-portal access (admin users can bypass this check)
                    if (!(bool) ($user->is_admin ?? false) && !(bool) ($user->with_ep_access ?? false)) {
                        return $this->errorResponse('You do not have access to the e-portal. Please contact the system administrator.', 401);
                    }

                    // Check if user has expired
                    if ($user->with_expiration && $user->expiration_date <= now()) {
                        return $this->errorResponse('Account has expired', 401);
                    }

                    // Revoke all existing tokens to enforce single active session.
                    try {
                        /** @var \Laravel\Sanctum\HasApiTokens $user */
                        $user->tokens()->delete();
                    } catch (\Exception $revokeError) {
                        Log::warning('Could not revoke existing tokens on login: ' . $revokeError->getMessage());
                    }

                    try {
                        /** @var \Laravel\Sanctum\HasApiTokens $user */
                        $token = $user->createToken('api-token')->plainTextToken;
                    } catch (\Exception $tokenError) {
                        // Fallback to hash-based token if Sanctum fails
                        $token = hash('sha256', $user->id . time() . config('app.key'));
                        Log::error('Failed to create Sanctum token: ' . $tokenError->getMessage());
                    }

                    $payload = [
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'employee_no' => $user->employee_no,
                            'is_applicant' => (bool) ($user->is_applicant ?? false),
                            'has_change_password' => (bool) ($user->has_change_password ?? false),
                            'is_admin' => (bool) ($user->is_admin ?? false),
                            'with_hrm_access' => (bool) ($user->with_hrm_access ?? false),
                            'with_hrt_access' => (bool) ($user->with_hrt_access ?? false),
                            'with_hrp_access' => (bool) ($user->with_hrp_access ?? false),
                            'with_cpm_access' => (bool) ($user->with_cpm_access ?? false),
                            'with_ep_access' => (bool) ($user->with_ep_access ?? false),
                        ],
                        'token' => $token,
                        'token_type' => 'Bearer',
                        'requires_otp' => false,
                        'next' => '/dashboard'
                    ];
                    // if ($user->is_applicant) {
                    //     $payload['next'] = '/applicant-page';
                    // }

                    // OTP and email verification for first-time employee login
                    if (!(bool) ($user->has_change_password ?? false)) {
                        // Generate OTP for password change validation
                        $otp = rand(100000, 999999);
                        $otpHash = Hash::make($otp);

                        Log::info('OTP Generation:', [
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'generated_otp' => $otp,
                            'otp_hash' => $otpHash,
                            'otp_length' => strlen($otp)
                        ]);

                        User::where('id', $user->id)->update(['otp_code' => $otpHash]);

                        // Send OTP via email - wrap in try-catch to handle email sending errors
                        try {
                            $userAccount = User::where('id', $user->id)->get();
                            Log::info('Attempting to send OTP email to: ' . $user->email . ' with OTP: ' . $otp);
                            Notification::send($userAccount, new EmailUserVerificationNotification($userAccount, $otp));
                            Log::info('OTP email sent successfully to: ' . $user->email . ' with OTP: ' . $otp);
                        } catch (\Exception $emailError) {
                            // Log email error but don't fail the login
                            Log::error('Failed to send OTP email to ' . $user->email . ': ' . $emailError->getMessage());
                            Log::error('Email error trace: ' . $emailError->getTraceAsString());
                        }

                        $payload['requires_otp'] = true;
                        $payload['next'] = '/verify-otp';
                    }

                    return $this->successResponse($payload, 'Login successful');
                }

                return $this->errorResponse('Invalid credentials', 401);
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Login failed: ' . $e->getMessage());
        }
    }

    /**
     * Logout user and invalidate token
     */
    public function logout(Request $request)
    {
        try {
            $user = Auth::user();

            if ($user) {
                // Revoke token if using Sanctum
                if (method_exists($user, 'tokens')) {
                    /** @var \Laravel\Sanctum\HasApiTokens $user */
                    $user->tokens()->delete();
                }

                Auth::logout();
            }

            return $this->successResponse([], 'Logout successful');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Logout failed: ' . $e->getMessage());
        }
    }

    /**
     * Get authenticated user profile
     */
    public function profile(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->errorResponse('User not authenticated', 401);
            }

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'photo' => $user->photo,
                    'employee_no' => $user->employee_no,
                    'is_applicant' => (bool) ($user->is_applicant ?? false),
                    'has_change_password' => (bool) ($user->has_change_password ?? false),
                    'is_admin' => (bool) ($user->is_admin ?? false),
                    'with_hrm_access' => (bool) ($user->with_hrm_access ?? false),
                    'with_hrt_access' => (bool) ($user->with_hrt_access ?? false),
                    'with_hrp_access' => (bool) ($user->with_hrp_access ?? false),
                    'with_cpm_access' => (bool) ($user->with_cpm_access ?? false),
                    'with_ep_access' => (bool) ($user->with_ep_access ?? false),
                    'active' => $user->active,
                ]
            ], 'Profile retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve profile: ' . $e->getMessage());
        }
    }

    /**
     * Verify OTP and generate token for password change
     */
    public function verifyOtp(Request $request)
    {
        try {
            $email = trim((string) $request->get('email', ''));
            $otpRaw = $request->get('otp_code');
            $otpDigits = preg_replace('/\D/', '', (string) $otpRaw);

            $validator = Validator::make(
                [
                    'email' => $email,
                    'otp_code' => $otpDigits,
                ],
                [
                    'email' => 'required|email',
                    'otp_code' => ['required', 'regex:/^[0-9]{6}$/'],
                ]
            );

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [Str::lower($email)])
                ->first();

            if (!$user) {
                return $this->errorResponse('User not found', 404);
            }

            if (empty($user->otp_code)) {
                return $this->errorResponse('No verification code on file. Please sign in again to receive a new code.', 400);
            }

            if ((bool) ($user->has_change_password ?? false)) {
                return $this->errorResponse('Password has already been changed', 400);
            }

            if (!Hash::check($otpDigits, $user->otp_code)) {
                return $this->errorResponse('Invalid OTP code', 400);
            }

            // Generate temporary token for password change
            try {
                /** @var \Laravel\Sanctum\HasApiTokens $user */
                $tempToken = $user->createToken('password-change-token', ['change-password'])->plainTextToken;
            } catch (\Exception $tokenError) {
                // Fallback to hash-based token if Sanctum fails
                $tempToken = hash('sha256', $user->id . time() . config('app.key'));
                Log::error('Failed to create Sanctum token: ' . $tokenError->getMessage());
            }

            // Clear OTP code after successful verification
            $user->otp_code = null;
            /** @var \Illuminate\Database\Eloquent\Model $user */
            $user->save();

            Log::info('OTP Verification successful:', [
                'user_id' => $user->id,
                'email' => $user->email,
                'temp_token_generated' => true
            ]);

            return $this->successResponse([
                'temp_token' => $tempToken,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_no' => $user->employee_no,
                    'is_applicant' => (bool) ($user->is_applicant ?? false),
                    'has_change_password' => (bool) ($user->has_change_password ?? false),
                    'is_admin' => (bool) ($user->is_admin ?? false),
                    'with_hrm_access' => (bool) ($user->with_hrm_access ?? false),
                    'with_hrt_access' => (bool) ($user->with_hrt_access ?? false),
                    'with_hrp_access' => (bool) ($user->with_hrp_access ?? false),
                    'with_cpm_access' => (bool) ($user->with_cpm_access ?? false),
                    'with_ep_access' => (bool) ($user->with_ep_access ?? false),
                ]
            ], 'OTP verified successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to verify OTP: ' . $e->getMessage());
        }
    }

    /**
     * Resend OTP email for the authenticated user (first login / password not yet changed).
     */
    public function resendOtp(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->errorResponse('User not authenticated', 401);
            }

            if ((bool) ($user->has_change_password ?? false)) {
                return $this->errorResponse('Account is already verified.', 400);
            }

            $otp = random_int(100000, 999999);
            $otpHash = Hash::make($otp);
            User::where('id', $user->id)->update(['otp_code' => $otpHash]);

            try {
                Log::info('Attempting to resend OTP email to: ' . $user->email . ' | DEV OTP CODE: ' . $otp);
                Notification::send($userAccount, new EmailUserVerificationNotification($userAccount, $otp));
                Log::info('Resent OTP email successfully to: ' . $user->email . ' | DEV OTP CODE: ' . $otp);
            } catch (\Exception $emailError) {
                Log::error('Failed to send OTP email: ' . $emailError->getMessage());

                return $this->errorResponse('Could not send email. Please try again later or contact support.', 500);
            }

            return $this->successResponse(null, 'A new verification code was sent to your email.');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to resend OTP: ' . $e->getMessage());
        }
    }

    /**
     * Change password for users who haven't changed it yet
     */
    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'current_password' => 'required|string',
                    'new_password' => [
                        'required',
                        'string',
                        'min:10',
                        'max:128',
                        'confirmed',
                        'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/',
                    ],
                    'new_password_confirmation' => 'required|string|min:10',
                ],
                [
                    'new_password.regex' => 'The new password must include at least one uppercase letter, one lowercase letter, one number, and one special character.',
                ]
            );

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $user = Auth::user();

            if (!$user) {
                return $this->errorResponse('User not authenticated', 401);
            }

            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->errorResponse('Current password is incorrect', 400);
            }

            // Check if user has already changed password
            if ((bool) ($user->has_change_password ?? false)) {
                return $this->errorResponse('Password has already been changed', 400);
            }

            // Update password and mark as changed
            $user->password = Hash::make($request->new_password);
            $user->has_change_password = true;
            /** @var \Illuminate\Database\Eloquent\Model $user */
            $user->save();

            // Generate new token for the updated user session
            try {
                /** @var \Laravel\Sanctum\HasApiTokens $user */
                $newToken = $user->createToken('api-token')->plainTextToken;
            } catch (\Exception $tokenError) {
                // Fallback to hash-based token if Sanctum fails
                $newToken = hash('sha256', $user->id . time() . config('app.key'));
                Log::error('Failed to create Sanctum token: ' . $tokenError->getMessage());
            }

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_no' => $user->employee_no,
                    'is_applicant' => (bool) ($user->is_applicant ?? false),
                    'has_change_password' => true,
                    'is_admin' => (bool) ($user->is_admin ?? false),
                    'with_hrm_access' => (bool) ($user->with_hrm_access ?? false),
                    'with_hrt_access' => (bool) ($user->with_hrt_access ?? false),
                    'with_hrp_access' => (bool) ($user->with_hrp_access ?? false),
                    'with_cpm_access' => (bool) ($user->with_cpm_access ?? false),
                    'with_ep_access' => (bool) ($user->with_ep_access ?? false),
                ],
                'token' => $newToken,
                'token_type' => 'Bearer'
            ], 'Password changed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to change password: ' . $e->getMessage());
        }
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->errorResponse('User not authenticated', 401);
            }

            // Create new token
            /** @var \Laravel\Sanctum\HasApiTokens $user */
            $token = $user->createToken('api-token')->plainTextToken ??
                hash('sha256', $user->id . time() . config('app.key'));

            return $this->successResponse([
                'token' => $token,
                'token_type' => 'Bearer'
            ], 'Token refreshed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Token refresh failed: ' . $e->getMessage());
        }
    }
}