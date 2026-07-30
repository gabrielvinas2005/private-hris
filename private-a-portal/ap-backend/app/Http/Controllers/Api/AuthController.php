<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Notification;
use App\Notifications\EmailUserVerificationNotification;

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

            // Check if email exists first
            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                return $this->errorResponse('Email address is wrong', 401);
            }

            // Check if password is correct
            if (!Hash::check($request->password, $user->password)) {
                return $this->errorResponse('Password is wrong', 401);
            }

            // Check if password is correct
            if (!Hash::check($request->password, $user->password)) {
                return $this->errorResponse('Password is wrong', 401);
            }

            // If both email and password are correct, login the user
            Auth::login($user);
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

            // Check if user is an employee - employees cannot login to applicant portal
            if ($user->employee_no) {
                $employee = DB::table('users')
                    ->where('employee_no', $user->employee_no)
                    ->where('is_applicant', 0)
                    ->first();
                
                if ($employee) {
                    return $this->errorResponse('Employees cannot access the applicant portal. Please use the employee portal instead.', 403);
                }
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
                    'mobile_no' => $user->mobile_no,
                    'gender' => $user->gender,
                    'is_applicant' => $user->is_applicant,
                    'active' => $user->active,
                ]
            ], 'Profile retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve profile: ' . $e->getMessage());
        }
    }
    
    public function pds_data(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->errorResponse('User not authenticated', 401);
            }

            $app_key = env("APP_KEY", "");

            // 1) Find applicant header by current user to get applicant_no
            $applicant = DB::table('applicant_headers')
                ->select('id', 'user_id', 'applicant_no', 'photo')
                ->where('user_id', $user->id)
                ->first();

            // 2) Match employees.employee_no to applicant_headers.applicant_no with decryption
            $employee = null;
            if ($applicant && !empty($applicant->applicant_no)) {
                $employee = DB::table('employees')
                    ->select(
                        'id',
                        'photo',
                        'employee_no',
                        'access_no',
                        'name_prefix_id',
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN first_name ELSE dbo.ufn_DecryptString(first_name,'$app_key') END as first_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN middle_name ELSE dbo.ufn_DecryptString(middle_name,'$app_key') END as middle_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN last_name ELSE dbo.ufn_DecryptString(last_name,'$app_key') END as last_name"),
                        'name_suffix_id',
                        'birth_place',
                        'birthdate',
                        'age',
                        'gender_id',
                        'height',
                        'weight',
                        'blood_type_id',
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN email ELSE dbo.ufn_DecryptString(email,'$app_key') END as email"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mobile_no ELSE dbo.ufn_DecryptString(mobile_no,'$app_key') END as mobile_no"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN telephone_no ELSE dbo.ufn_DecryptString(telephone_no,'$app_key') END as telephone_no"),
                        'citizenship_id',
                        'civil_status_id',
                        'religion_id',
                        'ra_region',
                        'ra_province',
                        'ra_city',
                        'ra_barangay',
                        'ra_house_no',
                        'ra_street',
                        'ra_village',
                        'pa_region',
                        'pa_province',
                        'pa_city',
                        'pa_barangay',
                        'pa_house_no',
                        'pa_street',
                        'pa_village',
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN father_first_name ELSE dbo.ufn_DecryptString(father_first_name,'$app_key') END as father_first_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN father_middle_name ELSE dbo.ufn_DecryptString(father_middle_name,'$app_key') END as father_middle_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN father_last_name ELSE dbo.ufn_DecryptString(father_last_name,'$app_key') END as father_last_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mother_first_name ELSE dbo.ufn_DecryptString(mother_first_name,'$app_key') END as mother_first_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mother_middle_name ELSE dbo.ufn_DecryptString(mother_middle_name,'$app_key') END as mother_middle_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mother_last_name ELSE dbo.ufn_DecryptString(mother_last_name,'$app_key') END as mother_last_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_first_name ELSE dbo.ufn_DecryptString(spouse_first_name,'$app_key') END as spouse_first_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_middle_name ELSE dbo.ufn_DecryptString(spouse_middle_name,'$app_key') END as spouse_middle_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_last_name ELSE dbo.ufn_DecryptString(spouse_last_name,'$app_key') END as spouse_last_name"),
                        'spouse_occupation',
                        'spouse_employer',
                        'spouse_business_address'
                    )
                    ->where('employee_no', $applicant->applicant_no)
                    ->first();
            }

            // 3) Fallback: if user has employee_no, try that
            if (!$employee && !empty($user->employee_no)) {
                $employee = DB::table('employees')
                    ->select(
                        'id',
                        'photo',
                        'employee_no',
                        'access_no',
                        'name_prefix_id',
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN first_name ELSE dbo.ufn_DecryptString(first_name,'$app_key') END as first_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN middle_name ELSE dbo.ufn_DecryptString(middle_name,'$app_key') END as middle_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN last_name ELSE dbo.ufn_DecryptString(last_name,'$app_key') END as last_name"),
                        'name_suffix_id',
                        'birth_place',
                        'birthdate',
                        'age',
                        'gender_id',
                        'height',
                        'weight',
                        'blood_type_id',
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN email ELSE dbo.ufn_DecryptString(email,'$app_key') END as email"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mobile_no ELSE dbo.ufn_DecryptString(mobile_no,'$app_key') END as mobile_no"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN telephone_no ELSE dbo.ufn_DecryptString(telephone_no,'$app_key') END as telephone_no"),
                        'citizenship_id',
                        'civil_status_id',
                        'religion_id',
                        'ra_region',
                        'ra_province',
                        'ra_city',
                        'ra_barangay',
                        'ra_house_no',
                        'ra_street',
                        'ra_village',
                        'pa_region',
                        'pa_province',
                        'pa_city',
                        'pa_barangay',
                        'pa_house_no',
                        'pa_street',
                        'pa_village',
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN father_first_name ELSE dbo.ufn_DecryptString(father_first_name,'$app_key') END as father_first_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN father_middle_name ELSE dbo.ufn_DecryptString(father_middle_name,'$app_key') END as father_middle_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN father_last_name ELSE dbo.ufn_DecryptString(father_last_name,'$app_key') END as father_last_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mother_first_name ELSE dbo.ufn_DecryptString(mother_first_name,'$app_key') END as mother_first_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mother_middle_name ELSE dbo.ufn_DecryptString(mother_middle_name,'$app_key') END as mother_middle_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mother_last_name ELSE dbo.ufn_DecryptString(mother_last_name,'$app_key') END as mother_last_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_first_name ELSE dbo.ufn_DecryptString(spouse_first_name,'$app_key') END as spouse_first_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_middle_name ELSE dbo.ufn_DecryptString(spouse_middle_name,'$app_key') END as spouse_middle_name"),
                        DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_last_name ELSE dbo.ufn_DecryptString(spouse_last_name,'$app_key') END as spouse_last_name"),
                        'spouse_occupation',
                        'spouse_employer',
                        'spouse_business_address'
                    )
                    ->where('employee_no', $user->employee_no)
                    ->first();
            }

            // If no employee record but we have applicant, return applicant data with photo
            if (!$employee && $applicant) {
                $applicantData = DB::table('applicant_headers')
                    ->where('id', $applicant->id)
                    ->first(['id', 'photo', 'first_name', 'middle_name', 'last_name', 'email', 'mobile_no', 'birth_date', 'age', 'gender']);
                
                return $this->successResponse([
                    'employee' => $applicantData,
                ], 'PDS data retrieved successfully');
            }

            return $this->successResponse([
                'employee' => $employee,
            ], $employee ? 'PDS data retrieved successfully' : 'No PDS data found');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve PDS data: ' . $e->getMessage());
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

    /**
     * Verify OTP code
     */
    public function verifyOTP(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'otp' => 'required|string|size:6',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $user = Auth::user();
            
            if (!$user) {
                return $this->errorResponse('User not authenticated', 401);
            }

            // Check if OTP matches
            if (!Hash::check($request->otp, $user->otp_code)) {
                return $this->errorResponse('Invalid verification code', 400);
            }

            // Mark user as verified and clear OTP, but don't set has_change_password yet
            User::where('id', $user->id)->update([
                'otp_code' => null,
                'email_verified_at' => now()
            ]);

            return $this->successResponse([
                'verified' => true,
                'next' => '/change-password',
                'requires_password_change' => true
            ], 'Account verified successfully. Please change your password.');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('OTP verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Resend OTP code
     */
    public function resendOTP(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return $this->errorResponse('User not authenticated', 401);
            }

            // Generate new OTP
            $otp = rand(100000, 999999);
            $otpHash = Hash::make($otp);
            
            // Update user's OTP
            User::where('id', $user->id)->update(['otp_code' => $otpHash]);

            // Send OTP via email
            $userAccount = User::where('id', $user->id)->get();
            Notification::send($userAccount, new EmailUserVerificationNotification($userAccount, $otp));

            return $this->successResponse([
                'resent' => true
            ], 'Verification code resent successfully');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to resend OTP: ' . $e->getMessage());
        }
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
                'new_password_confirmation' => 'required|string|same:new_password',
            ], [
                'new_password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
                'new_password_confirmation.same' => 'Password confirmation does not match.'
            ]);

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

            // Update password and mark as changed
            User::where('id', $user->id)->update([
                'password' => Hash::make($request->new_password),
                'has_change_password' => true,
                'updated_at' => now()
            ]);

            // Don't revoke tokens for first-time password change to allow dashboard access
            // Only revoke tokens if this is a regular password change (not first-time)
            $isFirstTimeChange = !$user->has_change_password;
            if (!$isFirstTimeChange && method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            return $this->successResponse([
                'changed' => true,
                'is_first_time' => $isFirstTimeChange,
                'next' => $isFirstTimeChange ? '/dashboard' : '/login',
                'message' => $isFirstTimeChange 
                    ? 'Password changed successfully. Welcome to your dashboard!' 
                    : 'Password changed successfully. Please login again.'
            ], 'Password changed successfully');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to change password: ' . $e->getMessage());
        }
    }
} 