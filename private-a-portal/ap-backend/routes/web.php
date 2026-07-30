<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

/**
 * Password reset link endpoint used by Laravel's password broker.
 * This must be named "password.reset" so the reset link can be generated.
 * It simply redirects to the frontend application, carrying token & email.
 */
Route::get('/password/reset/{token}', function (Request $request, $token) {
    $email = $request->query('email');

    $frontendUrl = config('app.frontend_url', '/');

    $redirectUrl = rtrim($frontendUrl, '/') . '/reset-password';
    $query = http_build_query([
        'token' => $token,
        'email' => $email,
    ]);

    return redirect($redirectUrl . '?' . $query);
})->name('password.reset');
