<?php

namespace App\Http\Controllers\Auth;

use Auth;
use App\User;
use Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Notifications\EmailUserVerificationNotification;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function authenticated()
    {
        Auth::logoutOtherDevices(request('password'));

        if (Auth::check()) {

            // Check User is Resigned
            $employee_data = DB::table('employees as a')
                ->join('users as b', 'a.employee_no', '=', 'b.employee_no')
                ->select('a.*')
                ->where('b.id', Auth::user()->id)
                ->get();

            if ($employee_data->isNotEmpty()) {
                if ($employee_data[0]->active == 0 && $employee_data[0]->is_employee == 0) {
                    Auth::logout();
                    return redirect()->route('login')->with('error', 'Your Account is Inactive. Please Contact system administrator.');
                }
            }

            if (Auth::user()->with_expiration == 1) {
                if (Auth::user()->expiration_date <= now()) {
                    if (Auth::user()->locked == 0) {
                        DB::table('users')->where('id', Auth::user()->id)->update(['locked' => true, 'locked_date' => now()]);
                    }

                    Auth::logout();
                    return redirect()->route('login')->with('error', 'Your Account is Expired. Please Contact system administrator.');
                }
            }

            if (Auth::user()->locked == 1) {
                Auth::logout();
                return redirect()->route('login')->with('error', 'Your Account is Locked. Please Contact system administrator.');
            } else {
                if (Auth::user()->has_change_password && !Auth::user()->is_applicant) {
                    return redirect()->route('home');
                } elseif (Auth::user()->has_change_password && Auth::user()->is_applicant) {
                    return redirect()->route('applicant_page');
                } else {
                    $opt_code = rand(100000, 999999);
                    $opt_code_hash = Hash::make($opt_code);

                    User::where('id', Auth::user()->id)->update(['otp_code' => $opt_code_hash]);

                    // send OTP Code to Email.
                    $user_account = User::where('id', Auth::user()->id)->get();

                    // send email verification here
                    Notification::send($user_account, new EmailUserVerificationNotification($user_account, $opt_code));

                    return redirect()->route('user_verification')->with('notify', 'Please verify your OTP Code sent to your email to activate your account.');
                }
            }
        }
    }
}
