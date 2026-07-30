<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionTypeController extends Controller
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
     * Get all promotion types
     */
    public function index()
    {
        try {
            $data = DB::table('promotion_natures')->orderby('name', 'asc')->get();

            return $this->successResponse($data, 'Promotion types retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve promotion types: ' . $e->getMessage());
        }
    }

    /**
     * Store promotion types
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['name']) || !is_array($data['name'])) {
                return $this->errorResponse('Invalid data format. Expected array of promotion types.');
            }

            $arr_len = count($data['name']);

            $promotion_data = [];

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['name'][$i] != NULL) {

                    if ($data['id'][$i] == null || $data['id'][$i] == 0) {

                        $is_exist = DB::table('promotion_natures')->where('name', $data['name'][$i])->get();

                        if ($is_exist->isNotEmpty()) {
                            return $this->errorResponse($data['name'][$i] . ' already exist. Name must be unique.');
                        }

                        $id = 0 + DB::table('promotion_natures')->max('id');
                        $id += 1;
                    } else {
                        $id = $data['id'][$i];
                    }

                    $promotion_data = [
                        'name' => $data['name'][$i],
                        'active' => isset($data['active'][$data['id'][$i]]) ? true : false,
                    ];

                    DB::unprepared('SET IDENTITY_INSERT promotion_natures ON');
                    DB::table('promotion_natures')->updateOrInsert(['id' => $id], $promotion_data);
                    DB::unprepared('SET IDENTITY_INSERT promotion_natures OFF');
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Promotion Types Setup',
                'activity' => 'Update',
                'description' => 'Updated promotion table information',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Promotion types updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update promotion types: ' . $e->getMessage());
        }
    }

    /**
     * Get promotion type for deletion confirmation
     */
    public function delete($id)
    {
        try {
            $data = DB::table('promotion_natures')->where('id', $id)->get();

            if ($data->isEmpty()) {
                return $this->notFoundResponse('Promotion type not found');
            }

            return $this->successResponse($data, 'Promotion type retrieved for deletion');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve promotion type: ' . $e->getMessage());
        }
    }

    /**
     * Delete promotion type
     */
    public function destroy($id)
    {
        try {
            $promotion_type = DB::table('promotion_natures')->where('id', $id)->first();

            if (!$promotion_type) {
                return $this->notFoundResponse('Promotion type not found');
            }

            DB::table('promotion_natures')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Promotion Types Setup',
                'activity' => 'Delete',
                'description' => 'Deleted promotion information',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Promotion type deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete promotion type: ' . $e->getMessage());
        }
    }

    /**
     * Show specific promotion type
     */
    public function show($id)
    {
        try {
            $data = DB::table('promotion_natures')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Promotion type not found');
            }

            return $this->successResponse($data, 'Promotion type retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve promotion type: ' . $e->getMessage());
        }
    }

    /**
     * Create new promotion type form data
     */
    public function create()
    {
        try {
            return $this->successResponse([
                'fields' => [
                    'name' => ['type' => 'text', 'required' => true],
                    'active' => ['type' => 'checkbox', 'required' => false]
                ]
            ], 'Create promotion type form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Edit promotion type form data
     */
    public function edit($id)
    {
        try {
            $data = DB::table('promotion_natures')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Promotion type not found');
            }

            return $this->successResponse($data, 'Promotion type retrieved for editing');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve promotion type: ' . $e->getMessage());
        }
    }

    /**
     * Update promotion type
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|string|unique:promotion_natures,name,' . $id,
                'active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $promotion_type = DB::table('promotion_natures')->where('id', $id)->first();

            if (!$promotion_type) {
                return $this->notFoundResponse('Promotion type not found');
            }

            $promotion_data = [
                'name' => $request->name,
                'active' => $request->has('active') ? true : false,
            ];

            DB::table('promotion_natures')->where('id', $id)->update($promotion_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Promotion Types Setup',
                'activity' => 'Update',
                'description' => 'Updated promotion type: ' . $request->name,
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Promotion type updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update promotion type: ' . $e->getMessage());
        }
    }
}
