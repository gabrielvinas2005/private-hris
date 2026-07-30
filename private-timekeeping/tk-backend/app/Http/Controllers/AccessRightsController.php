<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\User;
use App\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class AccessRightsController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index($id)
    {
        try {
            $user = User::findOrFail($id);
            $info = DB::table('users')->where('id', $id)->get();

            $menus_hrm = DB::table('menus')
                ->whereNotIn('id', function ($query) use ($user) {
                    $query->select(DB::raw('menu_id'))
                        ->from('access')
                        ->where('user_id', $user->id);
                })
                ->select('menus.status', 'menus.id', 'menus.menu', 'menus.description')
                ->where(['module_id' => 1, 'active' => true]);

            $menus_hrt = DB::table('menus')
                ->whereNotIn('id', function ($query) use ($user) {
                    $query->select(DB::raw('menu_id'))
                        ->from('access')
                        ->where('user_id', $user->id);
                })
                ->select('menus.status', 'menus.id', 'menus.menu', 'menus.description')
                ->where(['module_id' => 2, 'active' => true]);

            $menus_hrp = DB::table('menus')
                ->whereNotIn('id', function ($query) use ($user) {
                    $query->select(DB::raw('menu_id'))
                        ->from('access')
                        ->where('user_id', $user->id);
                })
                ->select('menus.status', 'menus.id', 'menus.menu', 'menus.description')
                ->where(['module_id' => 3, 'active' => true]);

            $menus_cpm = DB::table('menus')
                ->whereNotIn('id', function ($query) use ($user) {
                    $query->select(DB::raw('menu_id'))
                        ->from('access')
                        ->where('user_id', $user->id);
                })
                ->select('menus.status', 'menus.id', 'menus.menu', 'menus.description')
                ->where(['module_id' => 4, 'active' => true]);

            $menus_ld = DB::table('menus')
                ->whereNotIn('id', function ($query) use ($user) {
                    $query->select(DB::raw('menu_id'))
                        ->from('access')
                        ->where('user_id', $user->id);
                })
                ->select('menus.status', 'menus.id', 'menus.menu', 'menus.description')
                ->where(['module_id' => 5, 'active' => true]);

            $menus_mig = DB::table('menus')
                ->whereNotIn('id', function ($query) use ($user) {
                    $query->select(DB::raw('menu_id'))
                        ->from('access')
                        ->where('user_id', $user->id);
                })
                ->select('menus.status', 'menus.id', 'menus.menu', 'menus.description')
                ->where(['module_id' => 6, 'active' => true]);

            // For HR module, return ONLY the menus explicitly granted in the `access` table.
            // (Do not union with `$menus_hrm` because that includes menus the user does NOT have access to,
            // which can cause incorrect UI permissions when `menus.status` is enabled.)
            $hrm_menu = DB::table('access')
                ->join('menus', 'access.menu_id', '=', 'menus.id')
                ->select('access.status', 'access.menu_id as id', 'menus.menu', 'menus.description')
                ->where(['access.user_id' => $id, 'menus.module_id' => 1, 'menus.active' => true])
                ->orderBy('menus.menu', 'asc')
                ->get();

            $hrt_menu = DB::table('access')
                ->join('menus', 'access.menu_id', '=', 'menus.id')
                ->select('access.status', 'access.menu_id as id', 'menus.menu', 'menus.description')
                ->where(['access.user_id' => $id, 'menus.module_id' => 2, 'menus.active' => true])
                ->orderBy('menus.menu', 'asc')
                ->get();

            $hrp_menu = DB::table('access')
                ->join('menus', 'access.menu_id', '=', 'menus.id')
                ->select('access.status', 'access.menu_id as id', 'menus.menu', 'menus.description')
                ->where(['access.user_id' => $id, 'menus.module_id' => 3, 'menus.active' => true])
                ->orderBy('menus.menu', 'asc')
                ->get();

            $cpm_menu = DB::table('access')
                ->join('menus', 'access.menu_id', '=', 'menus.id')
                ->select('access.status', 'access.menu_id as id', 'menus.menu', 'menus.description')
                ->where(['access.user_id' => $id, 'menus.module_id' => 4, 'menus.active' => true])
                ->orderBy('menus.menu', 'asc')
                ->get();

            $ld_menu = DB::table('access')
                ->join('menus', 'access.menu_id', '=', 'menus.id')
                ->select('access.status', 'access.menu_id as id', 'menus.menu', 'menus.description')
                ->where(['access.user_id' => $id, 'menus.module_id' => 5, 'menus.active' => true])
                ->orderBy('menus.menu', 'asc')
                ->get();

            $mig_menu = DB::table('access')
                ->join('menus', 'access.menu_id', '=', 'menus.id')
                ->select('access.status', 'access.menu_id as id', 'menus.menu', 'menus.description')
                ->where(['access.user_id' => $id, 'menus.module_id' => 6, 'menus.active' => true])
                ->orderBy('menus.menu', 'asc')
                ->get();

            return $this->successResponse(compact(
                'info',
                'hrm_menu',
                'hrt_menu',
                'hrp_menu',
                'cpm_menu',
                'ld_menu',
                'mig_menu'
            ), 'Access rights data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve access rights data: ' . $e->getMessage());
        }
    }

    public function update_access(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'employee_no' => 'nullable|string',
                'professor_no' => 'nullable|string',
                'expiration_date' => 'nullable|date|after:today',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $user = User::findOrFail($id);

            if ($request->has('with_expiration')) {
                if ($request->expiration_date == null) {
                    return $this->errorResponse('Please Specify Expiration Date.', 400);
                } elseif ($request->expiration_date < now()) {
                    return $this->errorResponse('Invalid Expiration Date.', 400);
                }

                $expiration_date = $request->expiration_date;
            } else {
                $expiration_date = null;
            }

            $data = array(
                'employee_no' => $request->employee_no,
                'professor_no' => $request->professor_no,
                'is_admin' => $request->has('is_admin') ? true : false,
                'locked' => $request->has('locked') ? true : false,
                'locked_date' => $request->has('locked') ? date("Y-m-d", strtotime(now())) : null,
                'with_hrm_access' => $request->has('with_hrm_access') ? true : false,
                'with_hrt_access' => $request->has('with_hrt_access') ? true : false,
                'with_hrp_access' => $request->has('with_hrp_access') ? true : false,
                'with_cpm_access' => $request->has('with_cpm_access') ? true : false,
                'with_ld_access' => $request->has('with_ld_access') ? true : false,
                'with_mig_access' => $request->has('with_mig_access') ? true : false,
                'access_all_branches' => $request->has('access_all_branches') ? true : false,
                'with_expiration' => $request->has('with_expiration') ? true : false,
                'expiration_date' => $expiration_date,
                'is_notify' => $request->has('is_notify'),
            );

            $user->fill($data);
            $user->save();

            $data_menu_table = request()->all();

            if (isset($data_menu_table['status'])) {
                $arr_len = count($data_menu_table['menu_id']);
                $arr_status_len = count($data_menu_table['status']) - 1;

                $status_index = 0;

                $menu_access = [];

                for ($i = 0; $i < $arr_len; $i++) {
                    if ($data_menu_table['menu_id'][$i] != NULL) {
                        if ($data_menu_table['status'][$status_index] == $data_menu_table['menu_id'][$i]) {
                            if ($status_index < $arr_status_len) {
                                $status_index += 1;
                            }
                            $status = true;
                        } else {
                            $status = false;
                        }

                        $menu_access = [
                            'user_id'   =>  $id,
                            'status'    =>  $status,
                            'menu_id'   =>  $data_menu_table['menu_id'][$i],
                        ];
                    }

                    DB::table('access')->updateOrInsert(['menu_id' => $data_menu_table['menu_id'][$i], 'user_id' => $id], $menu_access);
                }
            } else {
                $arr_len = count($data_menu_table['menu_id']);

                $status_index = 0;

                $menu_access = [];

                for ($i = 0; $i < $arr_len; $i++) {
                    if ($data_menu_table['menu_id'][$i] != NULL) {

                        $status = false;

                        $menu_access = [
                            'user_id'   =>  $id,
                            'status'    =>  $status,
                            'menu_id'   =>  $data_menu_table['menu_id'][$i],
                        ];
                    }

                    DB::table('access')->updateOrInsert(['menu_id' => $data_menu_table['menu_id'][$i], 'user_id' => $id], $menu_access);
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Access Rights',
                'activity' => 'Update',
                'description' => 'Updated access rights informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['user_id' => $id], 'You have successfully updated access rights!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update access rights: ' . $e->getMessage());
        }
    }
}
