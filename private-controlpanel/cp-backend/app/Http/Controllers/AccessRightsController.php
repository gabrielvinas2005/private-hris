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

            // Resolve module IDs dynamically so UI mapping stays correct even if IDs change in DB.
            $moduleRows = DB::table('module')->select('id', 'name')->get();
            $moduleIdByName = [];
            foreach ($moduleRows as $row) {
                $moduleIdByName[strtolower(trim((string) $row->name))] = (int) $row->id;
            }

            $hrModuleId = $moduleIdByName['hr module'] ?? 1;
            $recruitmentModuleId = $moduleIdByName['recruitment'] ?? null;

            $hrtModuleId = $moduleIdByName['time keeping'] ?? 2;
            $hrpModuleId = $moduleIdByName['payroll'] ?? 3;
            $cpmModuleId = $moduleIdByName['control panel'] ?? 4;
            $migModuleId = $moduleIdByName['migration'] ?? 6;
            $epModuleId = $moduleIdByName['employee portal'] ?? 7;

            // Build available (not yet granted) menus per module
            $menus_hrm = DB::table('menus')
                ->whereNotIn('id', function ($query) use ($user) {
                    $query->select(DB::raw('menu_id'))
                        ->from('access')
                        ->where('user_id', $user->id);
                })
                ->select('menus.status', 'menus.id', 'menus.menu', 'menus.description')
                ->where('menus.module_id', $hrModuleId)
                ->where('menus.active', true);

            $menus_recruitment = null;
            if ($recruitmentModuleId) {
                $menus_recruitment = DB::table('menus')
                    ->whereNotIn('id', function ($query) use ($user) {
                        $query->select(DB::raw('menu_id'))
                            ->from('access')
                            ->where('user_id', $user->id);
                    })
                    ->select('menus.status', 'menus.id', 'menus.menu', 'menus.description')
                    ->where('menus.module_id', $recruitmentModuleId)
                    ->where('menus.active', true);
            }

            $menus_hrt = DB::table('menus')
                ->whereNotIn('id', function ($query) use ($user) {
                    $query->select(DB::raw('menu_id'))
                        ->from('access')
                        ->where('user_id', $user->id);
                })
                ->select('menus.status', 'menus.id', 'menus.menu', 'menus.description')
                ->where('menus.module_id', $hrtModuleId)
                ->where('menus.active', true);

            $menus_hrp = DB::table('menus')
                ->whereNotIn('id', function ($query) use ($user) {
                    $query->select(DB::raw('menu_id'))
                        ->from('access')
                        ->where('user_id', $user->id);
                })
                ->select('menus.status', 'menus.id', 'menus.menu', 'menus.description')
                ->where('menus.module_id', $hrpModuleId)
                ->where('menus.active', true);

            $menus_cpm = DB::table('menus')
                ->whereNotIn('id', function ($query) use ($user) {
                    $query->select(DB::raw('menu_id'))
                        ->from('access')
                        ->where('user_id', $user->id);
                })
                ->select('menus.status', 'menus.id', 'menus.menu', 'menus.description')
                ->where('menus.module_id', $cpmModuleId)
                ->where('menus.active', true);

            $menus_mig = DB::table('menus')
                ->whereNotIn('id', function ($query) use ($user) {
                    $query->select(DB::raw('menu_id'))
                        ->from('access')
                        ->where('user_id', $user->id);
                })
                ->select('menus.status', 'menus.id', 'menus.menu', 'menus.description')
                ->where('menus.module_id', $migModuleId)
                ->where('menus.active', true);

            $menus_ep = DB::table('menus')
                ->whereNotIn('id', function ($query) use ($user) {
                    $query->select(DB::raw('menu_id'))
                        ->from('access')
                        ->where('user_id', $user->id);
                })
                ->select('menus.status', 'menus.id', 'menus.menu', 'menus.description')
                ->where('menus.module_id', $epModuleId)
                ->where('menus.active', true);

            $hrm_menu = DB::table('access')
                ->join('menus', 'access.menu_id', '=', 'menus.id')
                ->select('access.status', 'access.menu_id as id', 'menus.menu', 'menus.description')
                ->where('access.user_id', $id)
                ->where('menus.module_id', $hrModuleId)
                ->where('menus.active', true)
                ->union($menus_hrm)
                ->orderBy('menu', 'asc')
                ->get();

            $recruitment_menu = collect();
            if ($recruitmentModuleId) {
                $recruitment_menu = DB::table('access')
                    ->join('menus', 'access.menu_id', '=', 'menus.id')
                    ->select('access.status', 'access.menu_id as id', 'menus.menu', 'menus.description')
                    ->where('access.user_id', $id)
                    ->where('menus.module_id', $recruitmentModuleId)
                    ->where('menus.active', true)
                    ->union($menus_recruitment)
                    ->orderBy('menu', 'asc')
                    ->get();
            }

            $hrt_menu = DB::table('access')
                ->join('menus', 'access.menu_id', '=', 'menus.id')
                ->select('access.status', 'access.menu_id as id', 'menus.menu', 'menus.description')
                ->where('access.user_id', $id)
                ->where('menus.module_id', $hrtModuleId)
                ->where('menus.active', true)
                ->union($menus_hrt)
                ->orderBy('menu', 'asc')
                ->get();

            $hrp_menu = DB::table('access')
                ->join('menus', 'access.menu_id', '=', 'menus.id')
                ->select('access.status', 'access.menu_id as id', 'menus.menu', 'menus.description')
                ->where('access.user_id', $id)
                ->where('menus.module_id', $hrpModuleId)
                ->where('menus.active', true)
                ->union($menus_hrp)
                ->orderBy('menu', 'asc')
                ->get();

            $cpm_menu = DB::table('access')
                ->join('menus', 'access.menu_id', '=', 'menus.id')
                ->select('access.status', 'access.menu_id as id', 'menus.menu', 'menus.description')
                ->where('access.user_id', $id)
                ->where('menus.module_id', $cpmModuleId)
                ->where('menus.active', true)
                ->union($menus_cpm)
                ->orderBy('menu', 'asc')
                ->get();

            $mig_menu = DB::table('access')
                ->join('menus', 'access.menu_id', '=', 'menus.id')
                ->select('access.status', 'access.menu_id as id', 'menus.menu', 'menus.description')
                ->where('access.user_id', $id)
                ->where('menus.module_id', $migModuleId)
                ->where('menus.active', true)
                ->union($menus_mig)
                ->orderBy('menu', 'asc')
                ->get();

            $ep_menu = DB::table('access')
                ->join('menus', 'access.menu_id', '=', 'menus.id')
                ->select('access.status', 'access.menu_id as id', 'menus.menu', 'menus.description')
                ->where('access.user_id', $id)
                ->where('menus.module_id', $epModuleId)
                ->where('menus.active', true)
                ->union($menus_ep)
                ->orderBy('menu', 'asc')
                ->get();

            return $this->successResponse(compact(
                'info',
                'hrm_menu',
                'recruitment_menu',
                'hrt_menu',
                'hrp_menu',
                'cpm_menu',
                'mig_menu',
                'ep_menu'
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
                // Validate expiration date only when with_expiration is truthy
                'expiration_date' => 'nullable|exclude_unless:with_expiration,1,true,on|date|after:today',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $user = User::findOrFail($id);

            if ($request->has('with_expiration') && $request->with_expiration) {
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
                // employee_no is intentionally NOT updatable from access rights screen
                'professor_no' => $request->professor_no,
                'is_admin' => $request->boolean('is_admin'),
                'locked' => $request->boolean('locked'),
                'locked_date' => $request->boolean('locked') ? date("Y-m-d", strtotime(now())) : null,
                'with_hrm_access' => $request->boolean('with_hrm_access'),
                'with_hrt_access' => $request->boolean('with_hrt_access'),
                'with_hrp_access' => $request->boolean('with_hrp_access'),
                'with_cpm_access' => $request->boolean('with_cpm_access'),
                // Learning & Development module is no longer used.
                'with_ld_access' => false,
                'with_mig_access' => $request->boolean('with_mig_access'),
                'with_ep_access' => $request->boolean('with_ep_access'),
                'access_all_branches' => $request->boolean('access_all_branches'),
                'with_expiration' => $request->boolean('with_expiration'),
                'expiration_date' => $expiration_date,
                'is_notify' => $request->boolean('is_notify'),
            );

            $user->fill($data);
            $user->save();

            $data_menu_table = request()->all();

            // Handle new menu access format
            if (isset($data_menu_table['menu_access'])) {
                foreach ($data_menu_table['menu_access'] as $menuAccess) {
                    if (isset($menuAccess['menu_id']) && $menuAccess['menu_id'] != null) {
                        if ((bool)$menuAccess['status']) {
                            $menu_access = [
                                'user_id' => $id,
                                'status' => true,
                                'menu_id' => $menuAccess['menu_id'],
                            ];
                            DB::table('access')->updateOrInsert(
                                ['menu_id' => $menuAccess['menu_id'], 'user_id' => $id],
                                $menu_access
                            );
                        } else {
                            DB::table('access')
                                ->where('menu_id', $menuAccess['menu_id'])
                                ->where('user_id', $id)
                                ->delete();
                        }
                    }
                }
            }
            // Handle legacy format for backward compatibility
            elseif (isset($data_menu_table['status'])) {
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
                'description' => 'Updated access rights information.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['user_id' => $id], 'You have successfully updated access rights!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update access rights: ' . $e->getMessage());
        }
    }
}
