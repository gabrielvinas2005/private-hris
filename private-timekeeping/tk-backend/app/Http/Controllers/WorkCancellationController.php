<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkCancellationController extends Controller
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
     * Get all work cancellations
     */
    public function index()
    {
        try {
            $data = DB::table('work_cancellations')->orderby('date_from', 'asc')->get();

            return $this->successResponse($data, 'Work cancellations retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve work cancellations: ' . $e->getMessage());
        }
    }

    /**
     * Get form data for work cancellation
     */
    public function add($id)
    {
        try {
            if ($id != 0) {
                $work_cancellations = DB::table('work_cancellations')->where('id', $id)->get();
            } else {
                $dummy_work_cancellations = array(
                    'id' => 0,
                    'date_from' => null,
                    'date_to' => null,
                    'time_from' => null,
                    'time_to' => null,
                    'with_pay' => null,
                    'reason' => null
                );

                $work_cancellations = (object)$dummy_work_cancellations;
                $work_cancellations = collect([$work_cancellations]);
            }

            return $this->successResponse($work_cancellations, 'Work cancellation form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load work cancellation form data: ' . $e->getMessage());
        }
    }

    /**
     * Store work cancellation
     */
    public function store(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'date_from' => 'required',
                'date_to' => 'required',
                'time_from' => 'required',
                'time_to' => 'required',
                'reason' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            if (strtotime($request->date_to) < strtotime($request->date_from)) {
                return $this->errorResponse('Invalid Date From and Date to!');
            }

            if (strtotime($request->time_to) < strtotime($request->time_from)) {
                return $this->errorResponse('Invalid Time From and Time to!');
            }

            $desc = $id == 0 ? 'Added Work Cancellation: ' . $request->reason . ' from ' . $request->date_from . ' to ' . $request->date_to : 'Updated Work Cancellation: ' . $request->reason . ' from ' . $request->date_from . ' to ' . $request->date_to;
            $act = $id == 0 ? 'Add' : 'Update';

            if ($id == 0) {
                $id = 0 + DB::table('work_cancellations')->max('id');
                $id += 1;
            }

            // Normalize boolean-like values to strict 1/0
            $withPayBool = filter_var($request->input('with_pay'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $withPay = $withPayBool ? 1 : 0;

            $data = array(
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'time_from' => $request->time_from,
                'time_to' => $request->time_to,
                'reason' => $request->reason,
                'with_pay' => $withPay
            );

            DB::unprepared('SET IDENTITY_INSERT work_cancellations ON');
            DB::table('work_cancellations')->updateOrInsert(['id' => $id], $data);
            DB::unprepared('SET IDENTITY_INSERT work_cancellations OFF');

            // Sync work suspension remarks into time_data records across modules
            $this->syncWorkSuspensionToTimeData($request->date_from, $request->date_to, $request->reason);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'TimeKeeping Module',
                'menu'    => 'Work Cancellation',
                'activity' => $act,
                'description' => $desc,
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Work cancellation saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save work cancellation: ' . $e->getMessage());
        }
    }

    /**
     * Helper to synchronize Work Suspension details into time_data records across all employees.
     */
    private function syncWorkSuspensionToTimeData($dateFrom, $dateTo, $reason)
    {
        try {
            if (empty($dateFrom) || empty($dateTo)) return;

            $startDate = \Carbon\Carbon::parse($dateFrom)->format('Y-m-d');
            $endDate = \Carbon\Carbon::parse($dateTo)->format('Y-m-d');
            $reasonText = $reason ?: 'Work Suspended';
            $reasonStr = 'Work Suspended (' . $reasonText . ')';

            $employees = DB::table('employees')
                ->where('active', true)
                ->where('is_employee', true)
                ->get();

            $current = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);

            while ($current->lte($end)) {
                $curDate = $current->format('Y-m-d');

                foreach ($employees as $emp) {
                    $record = DB::table('time_data')
                        ->where('employee_id', $emp->id)
                        ->whereDate('date', $curDate)
                        ->first();

                    if ($record) {
                        $remarks = $record->remarks ? trim($record->remarks) : '';
                        if (empty($remarks)) {
                            $newRemarks = $reasonStr;
                        } elseif (strpos($remarks, $reasonText) === false && strpos($remarks, 'Work Suspended') === false) {
                            $newRemarks = $remarks . ' & ' . $reasonStr;
                        } else {
                            $newRemarks = $remarks;
                        }

                        $updateData = ['remarks' => $newRemarks];
                        if (empty($record->am_in) && empty($record->pm_in)) {
                            $updateData['absent'] = 0;
                        }

                        DB::table('time_data')
                            ->where('id', $record->id)
                            ->update($updateData);
                    } else {
                        $pp = DB::table('payroll_periods')
                            ->whereDate('attendance_start_date', '<=', $curDate)
                            ->whereDate('attendance_end_date', '>=', $curDate)
                            ->first();

                        DB::table('time_data')->insert([
                            'employee_id' => $emp->id,
                            'date' => $curDate,
                            'payroll_period_id' => $pp ? $pp->id : 0,
                            'work_hours' => 0,
                            'late' => 0,
                            'undertime' => 0,
                            'absent' => 0,
                            'remarks' => $reasonStr
                        ]);
                    }
                }

                $current->addDay();
            }
        } catch (\Exception $e) {
            \Log::error('Error syncing work suspension to time_data: ' . $e->getMessage());
        }
    }

    /**
     * Show specific work cancellation
     */
    public function show($id)
    {
        try {
            $work_cancellation = DB::table('work_cancellations')->where('id', $id)->first();

            if (!$work_cancellation) {
                return $this->notFoundResponse('Work cancellation not found');
            }

            return $this->successResponse($work_cancellation, 'Work cancellation retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve work cancellation: ' . $e->getMessage());
        }
    }

    /**
     * Create new work cancellation form data
     */
    public function create()
    {
        try {
            return $this->successResponse([
                'fields' => [
                    'date_from' => ['type' => 'date', 'required' => true],
                    'date_to' => ['type' => 'date', 'required' => true],
                    'time_from' => ['type' => 'time', 'required' => true],
                    'time_to' => ['type' => 'time', 'required' => true],
                    'reason' => ['type' => 'text', 'required' => true],
                    'with_pay' => ['type' => 'checkbox', 'required' => false]
                ]
            ], 'Create work cancellation form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Edit work cancellation form data
     */
    public function edit($id)
    {
        try {
            $work_cancellation = DB::table('work_cancellations')->where('id', $id)->first();

            if (!$work_cancellation) {
                return $this->notFoundResponse('Work cancellation not found');
            }

            return $this->successResponse($work_cancellation, 'Work cancellation retrieved for editing');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve work cancellation: ' . $e->getMessage());
        }
    }

    /**
     * Update work cancellation
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'date_from' => 'required',
                'date_to' => 'required',
                'time_from' => 'required',
                'time_to' => 'required',
                'reason' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            if (strtotime($request->date_to) < strtotime($request->date_from)) {
                return $this->errorResponse('Invalid Date From and Date to!');
            }

            if (strtotime($request->time_to) < strtotime($request->time_from)) {
                return $this->errorResponse('Invalid Time From and Time to!');
            }

            $work_cancellation = DB::table('work_cancellations')->where('id', $id)->first();

            if (!$work_cancellation) {
                return $this->notFoundResponse('Work cancellation not found');
            }

            // Normalize boolean-like values to strict 1/0
            $withPayBool = filter_var($request->input('with_pay'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $withPay = $withPayBool ? 1 : 0;

            $data = array(
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'time_from' => $request->time_from,
                'time_to' => $request->time_to,
                'reason' => $request->reason,
                'with_pay' => $withPay
            );

            DB::table('work_cancellations')->where('id', $id)->update($data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'TimeKeeping Module',
                'menu'    => 'Work Cancellation',
                'activity' => 'Update',
                'description' => 'Updated Work Cancellation: ' . $request->reason . ' from ' . $request->date_from . ' to ' . $request->date_to,
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Work cancellation updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update work cancellation: ' . $e->getMessage());
        }
    }

    /**
     * Delete work cancellation
     */
    public function destroy($id)
    {
        try {
            $work_cancellation = DB::table('work_cancellations')->where('id', $id)->first();

            if (!$work_cancellation) {
                return $this->notFoundResponse('Work cancellation not found');
            }

            DB::table('work_cancellations')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'TimeKeeping Module',
                'menu'    => 'Work Cancellation',
                'activity' => 'Delete',
                'description' => 'Deleted Work Cancellation: ' . $work_cancellation->reason,
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Work cancellation deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete work cancellation: ' . $e->getMessage());
        }
    }
}
