<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Position;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PositionsController extends Controller
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
     * Get all positions
     */
    public function index()
    {
        try {
            $data = Position::all();

            return $this->successResponse($data, 'Positions retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve positions: ' . $e->getMessage());
        }
    }

    /**
     * Get form data for adding position
     */
    public function add()
    {
        try {
            $competency = DB::table('competencies as a')
                ->leftjoin('subcompetencies as b', 'a.id', '=', 'b.competency_id')
                ->leftjoin('position_competencies as c', 'b.id', '=', 'c.subcompetency_id')
                ->select(
                    'a.id',
                    'a.name',
                    'b.id as subcompetency_id',
                    'b.name as subcompetency_name',
                    'b.description as subcompetency_description',
                    'b.code as subcompetency_code',
                    'c.level'
                )
                ->get();

            $grouped_arr = [];

            foreach ($competency as $element) {
                $elemName = $element->name;

                if (!isset($grouped_arr[$elemName])) {
                    $grouped_arr[$elemName] = [];
                }

                array_push($grouped_arr[$elemName], $element);
            }

            return $this->successResponse([
                'competency' => $competency,
                'grouped_arr' => $grouped_arr
            ], 'Position form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load position form data: ' . $e->getMessage());
        }
    }

    /**
     * Store position
     */
    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|min:3|unique:positions'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $form_data = array(
                'code' => $request->code,
                'name' => $request->name,
                'is_administrative_position' => $request->has('is_administrative_position') ? true : false,
                'active' => $request->has('active') ? true : false,
            );

            $position = Position::create($form_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Position Setup',
                'activity' => 'Add',
                'description' => 'Added ' . $request->name . ' on position setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $position->id], 'Position added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add position: ' . $e->getMessage());
        }
    }

    /**
     * Get form data for editing position
     */
    public function edit($id)
    {
        try {
            $position = DB::table('positions')->where('id', $id)->first();

            if (!$position) {
                return $this->notFoundResponse('Position not found');
            }

            $competency = DB::table('competencies as a')
                ->leftjoin('subcompetencies as b', 'a.id', '=', 'b.competency_id')
                ->leftjoin('position_competencies as c', function ($join) use ($id) {
                    $join->on('b.id', '=', 'c.subcompetency_id');
                    $join->on('c.position_id', '=', DB::raw("'" . $id . "'"));
                })
                ->select(
                    'a.id',
                    'a.name',
                    'b.id as subcompetency_id',
                    'b.name as subcompetency_name',
                    'b.description as subcompetency_description',
                    'b.code as subcompetency_code',
                    'c.level'
                )
                ->selectRaw('case when c.subcompetency_id = b.id then 1 else 0 end as assign')
                ->get();

            $grouped_arr = [];

            foreach ($competency as $element) {
                $elemName = $element->name;

                if (!isset($grouped_arr[$elemName])) {
                    $grouped_arr[$elemName] = [];
                }

                array_push($grouped_arr[$elemName], $element);
            }

            return $this->successResponse([
                'position' => $position,
                'competency' => $competency,
                'grouped_arr' => $grouped_arr
            ], 'Position edit data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load position edit data: ' . $e->getMessage());
        }
    }

    /**
     * Update position
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|min:3|unique:positions,name,' . $id
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $position = DB::table('positions')->where('id', $id)->first();

            if (!$position) {
                return $this->notFoundResponse('Position not found');
            }

            $pos_data = array(
                'code' => $request->code,
                'name' =>  $request->name,
                'is_administrative_position' => $request->has('is_administrative_position') ? true : false,
                'active' => $request->has('active') ? true : false,
            );

            // // Save Competencies
            // $data_competencies = $request->all();

            // // DB::table('position_competencies')->where(['position_id' => $id])->delete();
            // if (isset($data_competencies['subcomp_id'])) {
            //     $arr_len_competencies = count($data_competencies['subcomp_id']);

            //     for ($i = 0; $i < $arr_len_competencies; $i++) {
            //         if ($data_competencies['subcomp_id'][$i] != NULL) {

            //             if ($data_competencies["subcomp_id"][$i] == null) {
            //                 $competency_id = 0 + DB::table('position_competencies')->max('id');
            //                 $competency_id += 1;
            //             } else {
            //                 $competency_id = $data_competencies["subcomp_id"][$i];
            //             }



            //             $competencies_data = [
            //                 'position_id' => $id,
            //                 'subcompetency_id' => $data_competencies["subcomp_id"][$i],
            //                 'level' => $data_competencies["level"][$i] == null ? 0 : $data_competencies["level"][$i]
            //             ];

            //             // DB::unprepared('SET IDENTITY_INSERT position_competencies ON');
            //             DB::table('position_competencies')->updateOrInsert(['position_id' => $id, 'subcompetency_id' => $competency_id], $competencies_data);
            //             // DB::unprepared('SET IDENTITY_INSERT position_competencies OFF');
            //         }
            //     }
            // }
            DB::table('positions')->where('id', $id)->update($pos_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Position Setup',
                'activity' => 'Update',
                'description' => 'Updated ' . $request->name . ' on position setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Position updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update position: ' . $e->getMessage());
        }
    }

    /**
     * Show specific position
     */
    public function show($id)
    {
        try {
            $data = DB::table('positions')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Position not found');
            }

            return $this->successResponse($data, 'Position retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve position: ' . $e->getMessage());
        }
    }

    /**
     * Create new position form data
     */
    public function create()
    {
        try {
            return $this->successResponse([
                'fields' => [
                    'code' => ['type' => 'text', 'required' => false],
                    'name' => ['type' => 'text', 'required' => true],
                    'is_administrative_position' => ['type' => 'checkbox', 'required' => false],
                    'active' => ['type' => 'checkbox', 'required' => false]
                ]
            ], 'Create position form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Delete position
     */
    public function destroy($id)
    {
        try {
            $position = DB::table('positions')->where('id', $id)->first();

            if (!$position) {
                return $this->notFoundResponse('Position not found');
            }

            DB::table('positions')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Position Setup',
                'activity' => 'Delete',
                'description' => 'Deleted position: ' . $position->name,
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Position deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete position: ' . $e->getMessage());
        }
    }
}
