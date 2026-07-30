<?php

namespace App\Http\Controllers;

use Auth;
use Image;
use App\Audit;
use App\Services\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
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

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        try {
            return $this->successResponse(['view' => 'account.profile'], 'Profile page loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load profile page: ' . $e->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            if ($request->password == null && $request->current_password == null || ($request->password == '' && $request->current_password == '') || ($request->password == ' ' && $request->current_password == ' ')) {
                $is_change_password = false;

                $validator = validator($request->all(), [
                    'name' => 'required',
                    'email' => 'required|email',
                    'photo' => 'image',
                    'user_name' => 'required',
                    'user_password' => 'required|min:8|password'
                ]);

                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors());
                }

                $user_rec = DB::table('users')
                    ->where('id', Auth::user()->id)
                    ->where('name', $request->user_name)
                    ->get();

                if (count($user_rec) == 0) {
                    return $this->errorResponse('User Name is Incorrect!', 400);
                }

                if (!Hash::check($request->user_password, Auth::user()->password)) {
                    return $this->errorResponse('Password is Incorrect!', 400);
                }

                if ($request->hasFile('photo')) {
                    $image_file = $request->photo;
                    $image = Image::make($image_file);

                    Response::make($image->encode('jpeg'));

                    $form_data = array(
                        'name' => $request->name,
                        'email' => $request->email,
                        'photo' => base64_encode($image),
                    );
                } else {
                    $form_data = array(
                        'name' => $request->name,
                        'email' => $request->email,
                    );
                }

                Auth::user()->update($form_data);
            } else {
                $is_change_password = true;

                if (Hash::check($request->current_password, Auth::user()->password)) {
                    $validator = validator($request->all(), [
                        'name' => 'required',
                        'email' => 'required|email',
                        'password' => 'required|min:8|confirmed',
                        'photo' => 'image',
                    ]);

                    if ($validator->fails()) {
                        return $this->validationErrorResponse($validator->errors());
                    }

                    if ($request->hasFile('photo')) {
                        $image_file = $request->photo;
                        $image = Image::make($image_file);

                        Response::make($image->encode('jpeg'));

                        $form_data = array(
                            'name' => $request->name,
                            'email' => $request->email,
                            'password' => Hash::make($request->password),
                            'photo' => base64_encode($image),
                            'has_change_password' => true
                        );
                    } else {
                        $form_data = array(
                            'name' => $request->name,
                            'email' => $request->email,
                            'password' => Hash::make($request->password),
                            'has_change_password' => true
                        );
                    }

                    Auth::user()->update($form_data);
                } else {
                    return $this->errorResponse('Current password is incorrect!', 400);
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'User Profile',
                'activity' => 'Update',
                'description' => 'Update user profile informations.',
            );

            Audit::create($data_audit);

            if (env("ENABLE_INTEGRATION", false)) {
                $process_response = (new UserService)->update($request);

                if ($process_response > 0) {
                    return $this->successResponse(null, 'You have successfully updated your account!');
                } else {
                    return $this->successResponse(null, 'You have successfully updated your account! But OSS UMM Profile not sync.');
                }
            }

            return $this->successResponse(null, 'You have successfully updated your account!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update profile: ' . $e->getMessage());
        }
    }

    public function profilePassword()
    {
        try {
            return $this->successResponse(['view' => 'account.profile_change_password'], 'Profile password page loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load profile password page: ' . $e->getMessage());
        }
    }
}
