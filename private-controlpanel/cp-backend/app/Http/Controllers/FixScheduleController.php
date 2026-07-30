<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FixScheduleController extends Controller
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
            $data = DB::table('fix_schedules')->orderBy('name', 'asc')->get();

            return $this->successResponse($data, 'Fix schedules retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve fix schedules: ' . $e->getMessage());
        }
    }

    public function add($id)
    {
        try {
            if ($id == 0) {

                $dummy_fix_schedules = array(
                    'id' => 0,
                    'name' => null,
                    'no_late' => false,
                    'no_undertime' => false,
                    'is_complete_attendance' => false,
                );

                $fix_schedules = (object) $dummy_fix_schedules;
                $fix_schedules = collect([$fix_schedules]);

                $days = DB::table('schedule_days')
                    ->select('id as day_id', 'name')
                    ->selectRaw(
                        '0 as id,
                        null as am_in,
                        null as am_out,
                        null as break_in,
                        null as break_out,
                        null as pm_in,
                        null as pm_out,
                        null as with_nd,
                        null as nd_start,
                        null as nd_end,
                        null as nd_rate,
                        null as grace_period,
                        null as flexi_hours,
                        null as work_hours'
                    )
                    ->orderBy('day_id', 'asc')
                    ->get();
            } else {

                $fix_schedules = DB::table('fix_schedules')->where('id', $id)->get();

                $days = DB::table('fix_schedules_details as a')
                    ->join('schedule_days as b', 'a.day_id', '=', 'b.id')
                    ->select('a.*', 'b.name as name')
                    ->where('fix_schedule_id', $id)
                    ->orderBy('b.id', 'asc')
                    ->get();
            }

            return $this->successResponse([
                'fix_schedules' => $fix_schedules,
                'days' => $days
            ], 'Fix schedule form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load fix schedule form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|unique:fix_schedules,name' . ($id ? ",$id" : '')
            ]);

            if ($id == 0) {
                $id = DB::table('fix_schedules')->max('id') + 1;
            }

            $header_data = array(
                'name' => $request->name,
                'no_late' => $request->has('with_late') ? true : false,
                'no_undertime' => $request->has('with_undertime') ? true : false,
                'is_complete_attendance' => $request->has('is_complete_attendance') ? true : false,
            );

            DB::unprepared('SET IDENTITY_INSERT fix_schedules ON');
            DB::table('fix_schedules')->updateOrInsert(['id' => $id], $header_data);
            DB::unprepared('SET IDENTITY_INSERT fix_schedules OFF');

            $data = $request->all();

            $arr_len = count($data['day_id']);

            $detail_data = [];

            if ($id == 0) {
                $id = DB::table('fix_schedules')->max('id');
            }

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['name'][$i] != NULL) {

                    if ($data['id'][$i] == 0) {
                        $dtl_id = 0 + DB::table('fix_schedules_details')->max('id');
                        $dtl_id += 1;
                    } else {
                        $dtl_id = $data['id'][$i];
                    }

                    $flexiHours = $data['flexi_hours'][$i];
                    $gracePeriod = $data['grace_period'][$i];

                    // Adjust grace period based on flexi hours and vice versa
                    if ($flexiHours > 0) {
                        $gracePeriod = 0;
                    } elseif ($gracePeriod > 0) {
                        $flexiHours = 0;
                    }

                    $detail_data = [
                        'fix_schedule_id' => $id,
                        'day_id' => $data['day_id'][$i],
                        'am_in' => $data['am_in'][$i],
                        'am_out' => $data['am_out'][$i],
                        'break_in' => $data['break_in'][$i],
                        'break_out' => $data['break_out'][$i],
                        'pm_in' => $data['pm_in'][$i],
                        'pm_out' => $data['pm_out'][$i],
                        'nd_start' => $data['nd_start'][$i],
                        'nd_end' => $data['nd_end'][$i],
                        'nd_rate' => $data['nd_rate'][$i],
                        'with_nd' => isset($data['with_nd'][$data['day_id'][$i]]) ? true : false,
                        'grace_period' => $data['grace_period'][$i],
                        'flexi_hours' => $data['flexi_hours'][$i],
                        'work_hours' => $data['work_hours'][$i],
                    ];

                    DB::unprepared('SET IDENTITY_INSERT fix_schedules_details ON');
                    DB::table('fix_schedules_details')->updateOrInsert(['id' => $dtl_id], $detail_data);
                    DB::unprepared('SET IDENTITY_INSERT fix_schedules_details OFF');
                }
            }

            if ($id == 0) {
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'Timekeeping Module',
                    'menu' => 'Fix Schedule',
                    'activity' => 'Add',
                    'description' => 'Added fix schedule: ' . $request->name . ' information',
                );

                Audit::create($data_audit);
            } else {
                //Save audit trail
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module' => 'Timekeeping Module',
                    'menu' => 'Fix Schedule',
                    'activity' => 'Update',
                    'description' => 'Updated fix schedule: ' . $request->name . ' information',
                );

                Audit::create($data_audit);
            }

            return $this->successResponse(['id' => $id], 'Fix schedule saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save fix schedule: ' . $e->getMessage());
        }
    }
}
