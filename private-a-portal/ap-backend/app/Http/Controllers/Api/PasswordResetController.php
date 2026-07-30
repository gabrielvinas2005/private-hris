<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Notifications\FrontendPasswordResetNotification;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    use ApiResponse;

    /**
     * Handle incoming forgot password requests and send reset link email.
     */
    public function sendResetLinkEmail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $frontendUrl = $request->headers->get('origin');
            if (!$frontendUrl) {
                $referer = $request->headers->get('referer');
                if ($referer) {
                    $parts = parse_url($referer);
                    $scheme = $parts['scheme'] ?? null;
                    $host = $parts['host'] ?? null;
                    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
                    if ($scheme && $host) {
                        $frontendUrl = $scheme . '://' . $host . $port;
                    }
                }
            }
            $frontendUrl = $frontendUrl ?: config('app.frontend_url', '/');

            $status = Password::broker()->sendResetLink($request->only('email'), function ($user, $token) use ($frontendUrl) {
                $user->notify(new FrontendPasswordResetNotification($token, $frontendUrl));
            });

            if ($status === Password::RESET_LINK_SENT) {
                return $this->successResponse(null, __($status));
            }

            return $this->errorResponse(__($status), 400);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to send reset link: ' . $e->getMessage());
        }
    }

    /**
     * Handle incoming password reset requests.
     */
    public function reset(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $status = Password::broker()->reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->password = Hash::make($password);
                    $user->setRememberToken(Str::random(60));
                    $user->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return $this->successResponse(null, __($status));
            }

            return $this->errorResponse(__($status), 400);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to reset password: ' . $e->getMessage());
        }
    }
}

