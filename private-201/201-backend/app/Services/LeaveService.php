<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class LeaveService
{

    public function autoApproved(): void
    {
        $leaves = DB::table('leave_headers as a')->where([
            'a.approved_2' => false,
            'a.disapproved' => false,
            'a.disapproved_2' => false,
            'a.is_cancel' => false,
            'a.is_cancel_2' => false,
        ])
            ->whereIn('a.leave_type_id', [3, 16, 20])
            ->whereRaw("(DATEDIFF(d,a.date_from,GETDATE())+1) >= 5")
            ->get();

        foreach ($leaves as $leave) {
            $employee_id = $leave->employee_id;
            $leave_id = $leave->id;

            // get credit balance
            $credit_balance = DB::table('leave_credits')
                ->where([
                    'leave_type_id' => $leave->leave_type_id,
                    'employee_id' => $employee_id,
                ])
                ->get();

            if ($credit_balance->isEmpty()) {
                $credits = 0;
            } else {
                $credits = $credit_balance[0]->credits;
            }

            $date_from = date("Y-m-d", strtotime($leave->date_from));
            $date_to = date("Y-m-d", strtotime($leave->date_to));
            $date = date("Y-m-d", strtotime($leave->date_from));

            // delete detail data first
            DB::table('leave_details')->where('leave_id', $leave_id)->delete();

            // get unavailable dates
            $employee = db::table('employees')->select('branch_id', 'work_schedule_id')->where('id', $employee_id)->get();

            if ($employee->isEmpty()) {
                $employee = db::table('branches')->select('id as branch_id', db::raw("cast(1 as int) as work_schedule_id"))->where('is_main_branch', true)->get();
            }

            $applied_leaves = DB::table('leave_headers as a')
                ->join('leave_details as b', 'a.id', '=', 'b.leave_id')
                ->select(
                    db::raw("CONVERT(NVARCHAR(50),DATEPART(D,b.leave_date))+'-'+CONVERT(NVARCHAR(50),DATEPART(M,b.leave_date))+'-'+CONVERT(NVARCHAR(50),DATEPART(YEAR,b.leave_date)) as un_date")
                )
                ->where('a.employee_id', $employee_id)
                ->where('a.is_cancel', false)
                ->where('a.is_cancel_2', false)
                ->where('a.id', '<>', $leave_id);

            $un_dates = DB::table('holidays as c')
                ->select(
                    db::raw("CONVERT(NVARCHAR(50),DATEPART(D,c.date))+'-'+CONVERT(NVARCHAR(50),DATEPART(M,c.date))+'-'+CONVERT(NVARCHAR(50),DATEPART(YEAR,c.date)) as un_date")
                )
                ->where('c.active', true)
                ->whereIn('c.branch', [0, $employee[0]->branch_id])
                ->unionAll($applied_leaves)
                ->orderBy('un_date', 'asc')
                ->get();

            $data_un_date = [];

            foreach ($un_dates as $un) {
                array_push($data_un_date, date("d-m-Y", strtotime($un->un_date)));
            }

            $leave_detail_data = [];

            for ($date = $date_from; $date <= $date_to; $date = date("Y-m-d", strtotime("$date +1 day"))) {
                if (in_array(date("d-m-Y", strtotime($date)), $data_un_date, true) == false) {
                    if (date('w', strtotime($date)) != 6 && date('w', strtotime($date)) != 0) {
                        if ($credits == 0) {
                            if ($leave->day_type_id == 1) {
                                $day_type_value = 1;
                            } else {
                                $day_type_value = 0.5;
                            }

                            $leave_detail_data = [
                                'leave_id' => $leave_id,
                                'leave_date' => date("Y-m-d", strtotime("$date")),
                                'with_pay' => 0,
                                'without_pay' => $day_type_value,
                            ];
                        } else {

                            if ($leave->day_type_id == 1) {
                                $day_type_value = 1;
                            } else {
                                $day_type_value = 0.5;
                            }

                            if ($credits >= $day_type_value) {
                                $leave_detail_data = [
                                    'leave_id' => $leave_id,
                                    'leave_date' => date("Y-m-d", strtotime("$date")),
                                    'with_pay' => $day_type_value,
                                    'without_pay' => 0,
                                ];
                            } else {
                                $leave_detail_data = [
                                    'leave_id' => $leave_id,
                                    'leave_date' => date("Y-m-d", strtotime("$date")),
                                    'with_pay' => 0,
                                    'without_pay' => $day_type_value,
                                ];
                            }
                        }

                        // insert leave details
                        DB::table('leave_details')->Insert($leave_detail_data);
                        $credits = $credits - $day_type_value;

                        if ($credits < 0) {
                            $credits = 0;
                        }

                        // less credits with pay to leave credits table
                        DB::table('leave_credits')
                            ->where([
                                'leave_type_id' => $leave->leave_type_id,
                                'employee_id' => $leave->employee_id
                            ])
                            ->update(['credits' => $credits]);
                    }
                }
            }

            if ($leave->approved == true) {
                $process_data = [
                    'approved_2' => true,
                    'disapproved_2' => false,
                    'processed_date_2' => now(),
                    'processed_by_2' => 0,
                    'approved_2_remarks' => 'Auto approved leave due to no action.'
                ];
            } else {
                $process_data = [
                    'approved' => true,
                    'disapproved' => false,
                    'processed_date' => now(),
                    'processed_by' => 0,
                    'approved_remarks' => 'Auto approved leave due to no action.',
                    'approved_2' => true,
                    'disapproved_2' => false,
                    'processed_date_2' => now(),
                    'processed_by_2' => 0,
                    'approved_2_remarks' => 'Auto approved leave due to no action.'
                ];
            }

            // update leave header table
            DB::table('leave_headers')->where('id', $leave_id)->update($process_data);
        }
    }
}
