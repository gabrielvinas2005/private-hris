<?php

namespace App\Http\Controllers;

use Auth;
use App\User;
use DateTime;
use App\Audit;
use Notification;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Notifications\EmailUserAccountNotification;
use App\Services\UserService;
use App\Traits\ApiResponse;

class UsersController extends Controller
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

    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");

            $users = DB::table('users')
                ->where('is_applicant', false)
                ->orderby('name', 'asc')
                ->get();

            $employees = DB::table('employees')
                ->select(
                    'employee_no',
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN first_name ELSE dbo.ufn_DecryptString(first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN middle_name ELSE dbo.ufn_DecryptString(middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN last_name ELSE dbo.ufn_DecryptString(last_name,'$app_key') END as last_name"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN email ELSE dbo.ufn_DecryptString(email,'$app_key') END as email")
                )
                ->whereNotIn('employee_no', DB::table('users')
                    ->select('employee_no')
                    ->whereNotNull('employee_no')
                    ->where('is_applicant', false)
                    ->pluck('employee_no'))
                ->where([
                    'active' => true,
                    'is_employee' => true
                ])
                ->whereNotNull('employee_no')
                ->limit(900)
                ->orderby('last_name', 'asc')
                ->get();

            return $this->successResponse([
                'users' => $users,
                'employees' => $employees
            ], 'Users data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve users data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'name.*' => 'unique:users,name,' . $request->id,
                'email.*' => 'unique:users,email,' . $request->id,
                'employee_no.*' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");

            $employees = DB::table('employees')
                ->whereNotIn('employee_no', DB::table('users')->select('employee_no')->whereNotNull('employee_no')->get()->pluck('employee_no'))
                ->orderby('last_name', 'asc')
                ->get();

            if ($employees->isEmpty()) {
                return $this->errorResponse('No available records to process!', 400);
            }

            $dataX = $request->all();

            // Store Users
            $arr_len = count($dataX['employee_no']);
            $user = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($dataX['employee_no'][$i] != NULL) {

                    $emp_data2 = DB::table('employees as a')
                        ->leftjoin('name_suffixes as b', 'b.id', '=', 'a.name_suffix_id')
                        ->select(
                            DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN first_name ELSE dbo.ufn_DecryptString(first_name,'$app_key') END as first_name"),
                            DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN middle_name ELSE dbo.ufn_DecryptString(middle_name,'$app_key') END as middle_name"),
                            DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN last_name ELSE dbo.ufn_DecryptString(last_name,'$app_key') END as last_name"),
                            DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN email ELSE dbo.ufn_DecryptString(email,'$app_key') END as email"),
                            'b.name as suffix',
                            'a.birthdate',
                            'a.photo'
                        )
                        ->where('a.employee_no', $dataX['employee_no'][$i])
                        ->get();

                    if ($emp_data2->isNotEmpty()) {
                        if ($emp_data2[0]->suffix == ' ' || $emp_data2[0]->suffix == '' || $emp_data2[0]->suffix == null) {
                            $name = str_replace(' ', '', $emp_data2[0]->last_name) . '.' . str_replace(' ', '', $emp_data2[0]->first_name) . '.' . str_replace(' ', '', $emp_data2[0]->middle_name);
                        } else {
                            $name = str_replace(' ', '', $emp_data2[0]->last_name) . '.' . str_replace(' ', '', $emp_data2[0]->first_name) . '.' . str_replace(' ', '', $emp_data2[0]->middle_name) . '.' . $emp_data2[0]->suffix;
                        }
                    } else {
                        $name = '';
                    }

                    if (in_array($dataX['employee_no'][$i], $dataX['select'])) {
                        $select = true;
                    } else {
                        $select = false;
                    }

                    if ($select == true) {

                        $request->validate([
                            'email' => 'unique:users,email'
                        ]);

                        $now = new DateTime();
                        $date_verified = $now->format('Y-m-d H:i:s');
                        $password = $request->input('password') ?: 'Password@123';
                        $email = $emp_data2[0]->email;

                        $user = [
                            'name' => $name,
                            'email' => $emp_data2[0]->email,
                            'password' => Hash::make($password),
                            'photo' => is_null($emp_data2[0]->photo) ? '' : $emp_data2[0]->photo,
                            'email_verified_at' => $date_verified,
                            'locked' => false,
                            'has_change_password' => true,
                            'is_applicant' => false,
                            'is_encrypted' => true,
                        ];

                        DB::table('users')->updateOrInsert(['employee_no' => $dataX['employee_no'][$i]], $user);

                        $user_api = [
                            'name' => $name,
                            'email' => $emp_data2[0]->email ?? '',
                            'password' => $password,
                        ];

                        if (env("ENABLE_INTEGRATION", false)) {
                            $process_response = (new UserService)->store($user_api);

                            if ($process_response == 'Success') {
                                $user_account = User::where('employee_no', $dataX['employee_no'][$i])->get();
                                // send email verification here
                                Notification::send($user_account, new EmailUserAccountNotification($user_account, $email, $name, $password));
                            }
                        } else {
                            $user_account = User::where('employee_no', $dataX['employee_no'][$i])->get();
                            // send email verification here
                            Notification::send($user_account, new EmailUserAccountNotification($user_account, $email, $name, $password));
                        }
                    }
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Users List',
                'activity' => 'Add',
                'description' => 'Added Employees on User List.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'You have successfully added new users!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add users: ' . $e->getMessage());
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'user_id' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $user = User::find($request->user_id);
            if (!$user) {
                return $this->errorResponse('User not found', 404);
            }

            $newPassword = $request->input('new_password') ?: 'Password@123';

            $user->password = Hash::make($newPassword);
            $user->has_change_password = true;
            $user->save();

            $data_audit = array(
                'user_id' => Auth::user() ? Auth::user()->id : null,
                'module'  => 'Control Panel',
                'menu'    => 'Users List',
                'activity' => 'Reset Password',
                'description' => 'Reset password for user ID: ' . $user->id,
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'new_password' => $newPassword
            ], "Password reset successfully to: {$newPassword}");
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to reset password: ' . $e->getMessage());
        }
    }

    public function userVerification()
    {
        try {
            return $this->successResponse(null, 'User verification page loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load user verification page: ' . $e->getMessage());
        }
    }

    public function userVerificationCheck(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'otp_code' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            if (Hash::check($request->otp_code, Auth::user()->otp_code)) {
                return $this->successResponse([
                    'redirect_url' => route('password.update'),
                    'message' => 'Please reset your password to activate your account.'
                ], 'OTP verification successful');
            } else {
                return $this->errorResponse('Invalid OTP Code. Please try again.', 400);
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to verify OTP: ' . $e->getMessage());
        }
    }
}
