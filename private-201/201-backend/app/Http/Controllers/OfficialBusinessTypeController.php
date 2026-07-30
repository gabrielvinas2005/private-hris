<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class OfficialBusinessTypeController extends Controller
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
            $data = DB::table('official_business_types')->orderby('name', 'asc')->get();
            return $this->successResponse($data, 'Official business types retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve official business types: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $arr_len = count($data['name']);
            $errors = [];
            $created = 0;
            $updated = 0;

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['name'][$i] != NULL) {
                    if ($data['id'][$i] == null) {
                        $is_exist = DB::table('official_business_types')->where('name', $data['name'][$i])->get();
                        if ($is_exist->isNotEmpty()) {
                            $errors[] = 'Official business type "' . $data['name'][$i] . '" already exists!';
                            continue;
                        }
                        $id = 0 + DB::table('official_business_types')->max('id');
                        $id += 1;
                        $created++;
                    } else {
                        $id = $data['id'][$i];
                        $updated++;
                    }

                    $ob_data = [
                        'name' => $data['name'][$i],
                        'active' => isset($data['active'][$data['id'][$i]]) ? true : false,
                    ];

                    DB::unprepared('SET IDENTITY_INSERT official_business_types ON');
                    DB::table('official_business_types')->updateOrInsert(['id' => $id], $ob_data);
                    DB::unprepared('SET IDENTITY_INSERT official_business_types OFF');
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Official Business Types Setup',
                'activity' => 'Update',
                'description' => 'Updated official business type table informations.',
            );
            Audit::create($data_audit);

            if (!empty($errors)) {
                return $this->validationErrorResponse(['errors' => $errors]);
            }

            return $this->successResponse([
                'created_count' => $created,
                'updated_count' => $updated
            ], 'You have successfully updated official business type!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update official business types: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $data = DB::table('official_business_types')->where('id', $id)->get();
            return $this->successResponse($data, 'Official business type data for deletion retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve official business type for deletion: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::table('official_business_types')->where('id', $id)->delete();
            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Official Business Types Setup',
                'activity' => 'Delete',
                'description' => 'Deleted official business type informations.',
            );
            Audit::create($data_audit);
            return $this->successResponse(['id' => $id], 'Successfully deleted!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete official business type: ' . $e->getMessage());
        }
    }
}
