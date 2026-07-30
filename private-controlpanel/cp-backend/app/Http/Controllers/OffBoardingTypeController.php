<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class OffBoardingTypeController extends Controller
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
            $data = DB::table('offboarding_natures')->orderby('name', 'asc')->get();
            return $this->successResponse($data, 'Off-boarding types retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve off-boarding types: ' . $e->getMessage());
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
                    $offBoardingNameExist  = DB::table('offboarding_natures')
                        ->where('name', $data['name'][$i])
                        ->when($data['id'][$i], function ($query, $id) {
                            return $query->where('id', '<>', $id);
                        })
                        ->exists();

                    if ($offBoardingNameExist) {
                        $errors[] = 'Off-Boarding Type "' . $data['name'][$i] . '" already exists.';
                        continue;
                    }

                    if ($data['id'][$i] == null) {
                        $id = 0 + DB::table('offboarding_natures')->max('id');
                        $id += 1;
                        $created++;
                    } else {
                        $id = $data['id'][$i];
                        $updated++;
                    }

                    $offboarding_data = [
                        'name' => $data['name'][$i],
                        'active' => isset($data['active'][$data['id'][$i]]) ? true : false,
                    ];

                    // Exclude 'id' from the insert statement
                    unset($offboarding_data['id']);

                    DB::unprepared('SET IDENTITY_INSERT offboarding_natures ON');
                    DB::table('offboarding_natures')->updateOrInsert(['id' => $id], $offboarding_data);
                    DB::unprepared('SET IDENTITY_INSERT offboarding_natures OFF');
                }
            }

            // Save audit trail
            $data_audit = [
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Off-Boarding Types Setup',
                'activity' => 'Update',
                'description' => 'Updated off-boarding table information.',
            ];
            Audit::create($data_audit);

            if (!empty($errors)) {
                return $this->validationErrorResponse(['errors' => $errors]);
            }

            return $this->successResponse([
                'created_count' => $created,
                'updated_count' => $updated
            ], 'You have successfully updated off-boarding!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update off-boarding types: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $data = DB::table('offboarding_natures')->where('id', $id)->get();
            return $this->successResponse($data, 'Off-boarding type data for deletion retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve off-boarding type for deletion: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::table('offboarding_natures')->where('id', $id)->delete();
            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Off-Boarding Types Setup',
                'activity' => 'Delete',
                'description' => 'Deleted off-boarding information',
            );
            Audit::create($data_audit);
            return $this->successResponse(['id' => $id], 'Successfully deleted!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete off-boarding type: ' . $e->getMessage());
        }
    }
}
