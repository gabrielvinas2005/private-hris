<?php

namespace App\Http\Controllers\Auth;

use Auth;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Services\UserService;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::LOGIN;

    protected function sendResetResponse(Request $request, $response)
    {

        if (env("ENABLE_INTEGRATION", false)) {
            $process_response = (new UserService)->update($request);

            if ($process_response > 0) {
                return redirect($this->redirectPath())
                    ->with('status', trans($response));
            } else {
                return redirect($this->redirectPath())
                    ->with('status', trans($response) . " User does not exist yet in OSS UMM.");
            }
        }

        return redirect($this->redirectPath())->with('status', trans($response));
    }
}
