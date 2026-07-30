<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AccessRightsController;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Notification;
use App\Notifications\EmailUserVerificationNotification;
use Laravel\Sanctum\PersonalAccessToken;

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

                // Check if user is active (treat null as active)
                if (isset($user->active) && (int) $user->active === 0) {
                    return $this->errorResponse('Account is inactive', 401);
                }

                // Check if user is locked
                if ($user->locked) {
                    return $this->errorResponse('Account is locked', 401);
                }

                // Check if user has expired
                if ($user->with_expiration && $user->expiration_date <= now()) {
                    return $this->errorResponse('Account has expired', 401);
                }

                // For API testing, we'll create a simple token
                // In production, you should use Laravel Sanctum
                $token = $user->createToken('api-token')->plainTextToken ??
                    hash('sha256', $user->id . time() . config('app.key'));

                // Build base response payload
                $payload = [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'employee_no' => $user->employee_no,
                        'is_applicant' => (bool) ($user->is_applicant ?? false),
                        'has_change_password' => (bool) ($user->has_change_password ?? false),
                        'is_admin' => (bool) ($user->is_admin ?? false),
                        'payroll_menu_keys' => AccessRightsController::computePayrollMenuKeys($user),
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'requires_otp' => false,
                    'next' => '/dashboard'
                ];

                // If user has not changed password yet, issue OTP and direct client to verification
                if (!(bool) ($user->has_change_password ?? false)) {
                    $otp = rand(100000, 999999);
                    $otpHash = Hash::make($otp);
                    User::where('id', $user->id)->update(['otp_code' => $otpHash]);

                    // Send OTP via email
                    $userAccount = User::where('id', $user->id)->get();
                    Notification::send($userAccount, new EmailUserVerificationNotification($userAccount, $otp));

                    $payload['requires_otp'] = true;
                    $payload['next'] = '/verify-otp';
                } else {
                    // Optional: route applicants differently
                    if ((bool) ($user->is_applicant ?? false)) {
                        $payload['next'] = '/dashboard';
                    } else {
                        $payload['next'] = '/dashboard';
                    }
                }

                return $this->successResponse($payload, 'Login successful');
            } else {
                // Fallback: manually verify user credentials in case default guard prevents attempt()
                $user = User::where('email', $request->email)->first();
                if ($user && Hash::check($request->password, $user->password)) {
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

                    // Check if user has expired
                    if ($user->with_expiration && $user->expiration_date <= now()) {
                        return $this->errorResponse('Account has expired', 401);
                    }

                    $token = $user->createToken('api-token')->plainTextToken ??
                        hash('sha256', $user->id . time() . config('app.key'));

                    $payload = [
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'employee_no' => $user->employee_no,
                            'is_applicant' => (bool) ($user->is_applicant ?? false),
                            'has_change_password' => (bool) ($user->has_change_password ?? false),
                            'is_admin' => (bool) ($user->is_admin ?? false),
                            'payroll_menu_keys' => AccessRightsController::computePayrollMenuKeys($user),
                        ],
                        'token' => $token,
                        'token_type' => 'Bearer',
                        'requires_otp' => false,
                        'next' => '/dashboard'
                    ];
                    // if ($user->is_applicant) {
                    //     $payload['next'] = '/applicant-page';
                    // }

                    if (!(bool) ($user->has_change_password ?? false)) {
                        $otp = rand(100000, 999999);
                        $otpHash = Hash::make($otp);
                        User::where('id', $user->id)->update(['otp_code' => $otpHash]);

                        $userAccount = User::where('id', $user->id)->get();
                        Notification::send($userAccount, new EmailUserVerificationNotification($userAccount, $otp));

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
     * Exchange E-Portal SSO parameters for a payroll API token.
     */
    public function sharedAuth(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_no' => 'required',
                'email' => 'required|email',
                'auth_token' => 'required|string',
                'redirect_from' => 'required|in:e_portal',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $employeeNo = $request->employee_no;
            $email = $request->email;
            $authToken = $this->normalizeSharedAuthToken($request->auth_token);

            $user = User::where('email', $email)
                ->where(function ($query) use ($employeeNo) {
                    $query->where('employee_no', $employeeNo)
                        ->orWhere('id', $employeeNo);
                })
                ->first();

            if (!$user) {
                return $this->errorResponse('User not found for shared authentication', 401);
            }

            if (isset($user->active) && (int) $user->active === 0) {
                return $this->errorResponse('Account is inactive', 401);
            }

            if ($user->locked) {
                return $this->errorResponse('Account is locked', 401);
            }

            if ($user->with_expiration && $user->expiration_date <= now()) {
                return $this->errorResponse('Account has expired', 401);
            }

            $portalToken = PersonalAccessToken::findToken($authToken);
            if ($portalToken && (int) $portalToken->tokenable_id !== (int) $user->id) {
                return $this->errorResponse('Authentication token does not match user', 401);
            }

            $token = $user->createToken('payroll-shared-auth')->plainTextToken;

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_no' => $user->employee_no,
                    'is_admin' => (bool) ($user->is_admin ?? false),
                    'payroll_menu_keys' => AccessRightsController::computePayrollMenuKeys($user),
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'Shared authentication successful');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Shared authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * @param string $raw
     */
    private function normalizeSharedAuthToken($raw)
    {
        $token = trim((string) $raw);
        if ($token !== '' && $token[0] === '{') {
            $decoded = json_decode($token, true);
            if (is_array($decoded) && !empty($decoded['token'])) {
                return trim((string) $decoded['token']);
            }
        }

        return $token;
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
                    'employee_no' => $user->employee_no,
                    'is_applicant' => $user->is_applicant,
                    'active' => $user->active,
                ]
            ], 'Profile retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve profile: ' . $e->getMessage());
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
