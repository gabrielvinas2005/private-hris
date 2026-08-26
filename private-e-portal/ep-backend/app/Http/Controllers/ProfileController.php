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
        $this->middleware('auth')->except(['uploadPhoto']);
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

    public function uploadPhoto(Request $request)
    {
        try {
            $user = Auth::user() ?? $request->user();
            if (!$user) {
                return $this->errorResponse('Unauthenticated', 401);
            }

            if (!$request->hasFile('photo') && !$request->filled('photo')) {
                return $this->errorResponse('No profile picture file or data provided.', 400);
            }

            // Check if 201 schedule allows update
            $today = now()->format('Y-m-d');
            $schedule = DB::table('update_201_schedule')
                ->where('date_to', '>=', $today)
                ->where('date_from', '<=', $today)
                ->first();

            if (!$schedule && !($user->is_admin ?? false)) {
                return $this->errorResponse('Updating 201 File photo is currently not available based on schedule.', 403);
            }

            $photo_base64 = null;
            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                $photo_base64 = base64_encode(file_get_contents($file->getRealPath()));
            } else if ($request->filled('photo')) {
                $rawPhoto = $request->photo;
                if (strpos($rawPhoto, 'base64,') !== false) {
                    $photo_base64 = explode('base64,', $rawPhoto)[1];
                } else {
                    $photo_base64 = $rawPhoto;
                }
            }

            if (!$photo_base64) {
                return $this->errorResponse('Invalid photo format provided.', 400);
            }

            // Store the photo at user's photo field
            DB::table('users')
                ->where('id', $user->id)
                ->update(['photo' => $photo_base64, 'updated_at' => now()]);

            // Also store/sync at employee photo if employee_no exists
            if (!empty($user->employee_no)) {
                DB::table('employees')
                    ->where('employee_no', $user->employee_no)
                    ->update(['photo' => $photo_base64, 'updated_at' => now()]);
            }

            try {
                Audit::create([
                    'user_id' => $user->id,
                    'module'  => 'My Profile & Records',
                    'menu'    => '201 File',
                    'activity' => 'Update Profile Photo',
                    'description' => 'Updated user profile picture.',
                ]);
            } catch (\Exception $auditEx) {
                // Ignore audit failure if any
            }

            return $this->successResponse([
                'photo' => $photo_base64,
                'user_id' => $user->id
            ], 'Profile picture updated successfully!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to upload profile picture: ' . $e->getMessage());
        }
    }
}
