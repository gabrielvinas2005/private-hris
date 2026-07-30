<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DPCRController extends Controller
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
            $dpcr_ratings = DB::table('dpcr_headers as a')
                ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
                ->join('months as f', 'a.month_from', '=', 'f.id')
                ->join('months as g', 'a.month_to', '=', 'g.id')
                ->select(
                    'a.id',
                    'a.division_id',
                    'a.year',
                    'a.month_from as month_from_id',
                    'a.month_to as month_to_id',
                    'f.name as month_from',
                    'g.name as month_to',
                    DB::raw("case when a.division_id = 0 then 'No division' else d.name end as division"),
                )
                ->get();

            return $this->successResponse($dpcr_ratings, 'DPCR ratings retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve DPCR ratings: ' . $e->getMessage());
        }
    }

    public function add($id)
    {
        try {
            $divisions = DB::table('divisions')->where('active', true)->orderBy('name', 'asc')->get();

            if ($id <> 0) {
                $dpcr_ratings = DB::table('dpcr_headers as a')
                    ->select(
                        'a.id',
                        'a.division_id',
                        'a.year',
                        'a.month_from',
                        'a.month_to',
                    )
                    ->where('a.id', $id)
                    ->get();
            } else {
                $dpcr_ratings_dummy = array(
                    'id' => 0,
                    'division_id' => 0,
                    'year' => date('Y'),
                    'month_from' => 0,
                    'month_to' => 0,
                );

                $dpcr_ratings = (object)$dpcr_ratings_dummy;
                $dpcr_ratings = collect([$dpcr_ratings]);
            }

            return $this->successResponse([
                'dpcr_ratings' => $dpcr_ratings,
                'divisions' => $divisions,
            ], 'DPCR form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load DPCR form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'division' => 'required',
                'month_from' => 'required',
                'month_to' => 'required',
                'year' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $is_exists = DB::table('dpcr_headers')->where([
                'division_id' => $request->division,
                'month_from' => $request->month_from,
                'month_to' => $request->month_to,
                'year' => $request->year,
            ])
                ->where('id', '<>', $request->id)
                ->get();

            if ($is_exists->isNotEmpty()) {
                return $this->errorResponse('Save failed. DPCR with same values already exists.');
            }

            $now = now();

            $data = array(
                'division_id' => $request->division,
                'month_from' => $request->month_from,
                'month_to' => $request->month_to,
                'year' => $request->year,
                'is_posted' => true,
                'updated_at' => $now,
            );

            $id = $request->id;

            if ($id == 0 || $id == null) {
                $maxId = DB::table('dpcr_headers')->max('id');
                $id = $maxId ? $maxId + 1 : 1;
            }

            if ($request->id == 0 || $request->id == null) {
                $data['created_at'] = $now;

                // Only use IDENTITY_INSERT if using SQL Server
                if (DB::getDriverName() === 'sqlsrv') {
                    DB::unprepared('SET IDENTITY_INSERT dpcr_headers ON');
                }
                DB::table('dpcr_headers')->updateOrInsert(['id' => $id], $data);
                if (DB::getDriverName() === 'sqlsrv') {
                    DB::unprepared('SET IDENTITY_INSERT dpcr_headers OFF');
                }

                // Auto-create DPCR details for department heads under this division.
                $this->syncDpcrDetailsForDivision($id, $request->division);
            } else {
                DB::table('dpcr_headers')->where('id', $id)->update($data);
            }

            //Save audit trail
            if ($request->id == 0) {
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'HR Module',
                    'menu'    => 'DPCR',
                    'activity' => 'Add',
                    'description' => 'Added DPCR informations.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'DPCR information added successfully');
            } else {
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'HR Module',
                    'menu'    => 'DPCR',
                    'activity' => 'Update',
                    'description' => 'Updated DPCR informations.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'DPCR information updated successfully');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save DPCR information: ' . $e->getMessage());
        }
    }

    public function review($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $headerDivisionId = DB::table('dpcr_headers')->where('id', $id)->value('division_id');
            $this->syncDpcrDetailsForDivision($id, $headerDivisionId);

            $dpcr_details = DB::table('dpcr_details')->where('dpcr_header_id', $id)->get();
            if ($dpcr_details->isEmpty()) {
                return $this->errorResponse('There are no department heads to review under this DPCR.');
            }

            // DPCR has details (or has been auto-healed above); build list from employee_dpcr filtering for department heads
            $header = DB::table('dpcr_headers as a')
                ->leftJoin('months as f', 'a.month_from', '=', 'f.id')
                ->leftJoin('months as g', 'a.month_to', '=', 'g.id')
                ->select(
                    'a.id',
                    'a.division_id',
                    'a.year',
                    'a.is_posted',
                    'a.created_at',
                    'a.updated_at',
                    'a.month_from as month_from_id',
                    'a.month_to as month_to_id',
                    'f.name as month_from',
                    'g.name as month_to'
                )
                ->where('a.id', $id)
                ->first();

            $divisionName = $header && $header->division_id
                ? DB::table('divisions')->where('id', $header->division_id)->value('name')
                : null;

            // Get department heads from dpcr_details (employee_dpcr_id contains the department head employee_id)
            $rows = DB::table('dpcr_details as d')
                ->where('d.dpcr_header_id', $id)
                ->whereNotNull('d.employee_dpcr_id')
                ->join('employees as emp', 'emp.id', '=', 'd.employee_dpcr_id')
                ->leftJoin('status as s', 's.id', '=', 'd.status_id')
                ->leftJoin('employee_dpcr as ed', 'ed.employee_id', '=', 'emp.id')
                ->leftJoin('positions as pos', 'pos.id', '=', 'emp.position_id')
                ->select(
                    DB::raw("ISNULL(d.id, 0) as dpcr_detail_id"),
                    'ed.id as employee_dpcr_id',
                    DB::raw("ISNULL(d.dpcr_header_id, $id) as dpcr_header_id"),
                    'd.status_id',
                    's.name as status_name',
                    'd.created_at',
                    'd.updated_at',
                    'emp.id as employee_id',
                    'ed.department',
                    'ed.period_start',
                    'ed.period_end',
                    'ed.approved_by',
                    'ed.approved_by_employee_id',
                    'ed.approved_date',
                    'ed.assessed_by',
                    'ed.assessed_by_employee_id',
                    'ed.assessed_date',
                    'ed.final_rater',
                    'ed.final_rater_employee_id',
                    'ed.final_rater_date',
                    'ed.planning_officer_id',
                    'ed.planning_officer_date',
                    'emp.employee_no',
                    DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.first_name ELSE dbo.ufn_DecryptString(emp.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.middle_name ELSE dbo.ufn_DecryptString(emp.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.last_name ELSE dbo.ufn_DecryptString(emp.last_name,'$app_key') END as last_name"),
                    'pos.name as position'
                )
                ->where('emp.is_employee', true)
                ->where('emp.active', true)
                ->orderBy('emp.last_name', 'asc')
                ->get();

            if ($rows->isEmpty()) {
                // Re-sync details in case the lookup rules changed or data was updated.
                $headerDivisionId = $header->division_id ?? DB::table('dpcr_headers')->where('id', $id)->value('division_id');
                $this->syncDpcrDetailsForDivision($id, $headerDivisionId);

                $rows = DB::table('dpcr_details as d')
                    ->where('d.dpcr_header_id', $id)
                    ->whereNotNull('d.employee_dpcr_id')
                    ->join('employees as emp', 'emp.id', '=', 'd.employee_dpcr_id')
                    ->leftJoin('status as s', 's.id', '=', 'd.status_id')
                    ->leftJoin('employee_dpcr as ed', 'ed.employee_id', '=', 'emp.id')
                    ->leftJoin('positions as pos', 'pos.id', '=', 'emp.position_id')
                    ->select(
                        DB::raw("ISNULL(d.id, 0) as dpcr_detail_id"),
                        'ed.id as employee_dpcr_id',
                        DB::raw("ISNULL(d.dpcr_header_id, $id) as dpcr_header_id"),
                        'd.status_id',
                        's.name as status_name',
                        'd.created_at',
                        'd.updated_at',
                        'emp.id as employee_id',
                        'ed.department',
                        'ed.period_start',
                        'ed.period_end',
                        'ed.approved_by',
                        'ed.approved_by_employee_id',
                        'ed.approved_date',
                        'ed.assessed_by',
                        'ed.assessed_by_employee_id',
                        'ed.assessed_date',
                        'ed.final_rater',
                        'ed.final_rater_employee_id',
                        'ed.final_rater_date',
                        'ed.planning_officer_id',
                        'ed.planning_officer_date',
                        'emp.employee_no',
                        DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.first_name ELSE dbo.ufn_DecryptString(emp.first_name,'$app_key') END as first_name"),
                        DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.middle_name ELSE dbo.ufn_DecryptString(emp.middle_name,'$app_key') END as middle_name"),
                        DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.last_name ELSE dbo.ufn_DecryptString(emp.last_name,'$app_key') END as last_name"),
                        'pos.name as position'
                    )
                    ->where('emp.is_employee', true)
                    ->where('emp.active', true)
                    ->orderBy('emp.last_name', 'asc')
                    ->get();
            }

            if ($rows->isEmpty()) {
                return $this->errorResponse('There are no department heads to review under this DPCR.');
            }

            $employeeDpcrIds = $rows->pluck('employee_dpcr_id')->filter()->values()->all();

            // Self-assessment outputs (only if employee_dpcr records exist)
            $outputs = collect();
            if (!empty($employeeDpcrIds)) {
                $outputs = DB::table('employee_dpcr_outputs')
                    ->whereIn('employee_dpcr_id', $employeeDpcrIds)
                    ->get();
            }

            // Recalibrations (DPCR is stored per employee_dpcr_id; no per-output FK in schema)
            $recalibrationsByEmployee = collect();
            if (!empty($employeeDpcrIds)) {
                $recalibrationsByEmployee = DB::table('dpcr_recalibrations as r')
                    ->leftJoin('status as s', 's.id', '=', 'r.status')
                    ->whereIn('r.employee_dpcr_id', $employeeDpcrIds)
                    ->select('r.*', 's.name as status_name')
                    ->orderByRaw("
                            CASE r.recalibration_level
                                WHEN 'pmt' THEN 1
                                ELSE 2
                            END
                        ")
                    ->orderBy('r.id')
                    ->get()
                    ->groupBy('employee_dpcr_id');
            }

            // Group outputs by employee for easy attachment
            $outputsByEmployee = $outputs->groupBy('employee_dpcr_id');

            $response = $rows->map(function ($row) use ($outputsByEmployee, $recalibrationsByEmployee, $divisionName, $header) {
                $employeeOutputs = $outputsByEmployee->get($row->employee_dpcr_id, collect())->values();
                $employeeRecalibrations = $recalibrationsByEmployee->get($row->employee_dpcr_id, collect())->values();

                // Derive recalibration status per level and overall
                // DPCR calibration is PMT-only (no HR / no Supervisor).
                $levels = ['pmt'];
                $levelStatus = [
                    'self' => 'pending',
                    'pmt' => null,
                ];

                foreach ($levels as $level) {
                    $latest = collect($employeeRecalibrations)
                        ->where('recalibration_level', $level)
                        ->sortByDesc('created_at')
                        ->first();
                    if ($latest) {
                        $levelStatus[$level] = $latest->status_name ?? $latest->status ?? 'pending';
                    }
                }

                $overall = 'pending';
                if ($levelStatus['pmt']) {
                    $overall = 'pmt - ' . $levelStatus['pmt'];
                }

                $row->outputs = $employeeOutputs;
                $row->recalibrations = $employeeRecalibrations;
                $row->recalibration_levels = $levelStatus;
                $row->recalibration_overall = $overall;
                $row->division = $divisionName;
                $row->year = $header->year ?? null;
                $row->month_from = $header->month_from ?? null;
                $row->month_to = $header->month_to ?? null;
                return $row;
            });

            return $this->successResponse($response, 'DPCR review data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve DPCR review data: ' . $e->getMessage());
        }
    }

    public function rating(Request $request, $id)
    {
        try {
            $data = $request->all();
            $processed_count = 0;

            for ($i = 0; $i < count($data['id']); $i++) {
                $dpcr_details = [
                    'dpcr_header_id' => $id,
                    'employee_id' => $data['id'][$i],
                    'status_id' => $data['status_id'][$i] ?? null,
                ];

                DB::table('dpcr_details')->updateOrInsert(
                    ['dpcr_header_id' => $id, 'employee_id' => $data['id'][$i]],
                    $dpcr_details
                );
                $processed_count++;
            }

            return $this->successResponse(['processed_count' => $processed_count], 'DPCR data saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save DPCR data: ' . $e->getMessage());
        }
    }

    private function getDepartmentHeadIdsForDivision($divisionId)
    {
        $query = DB::table('divisions')
            ->where('active', true)
            ->where('division_chief_id', '>', 0);

        if ((int)$divisionId !== 0) {
            $query->where('id', $divisionId);
        }

        return $query->pluck('division_chief_id')->unique()->filter()->values();
    }

    private function syncDpcrDetailsForDivision($dpcrHeaderId, $divisionId)
    {
        $departmentHeadIds = $this->getDepartmentHeadIdsForDivision($divisionId);
        $today = now()->toDateString();

        if ($departmentHeadIds->isEmpty()) {
            DB::table('dpcr_details')->where('dpcr_header_id', $dpcrHeaderId)->delete();
            return;
        }

        DB::table('dpcr_details')
            ->where('dpcr_header_id', $dpcrHeaderId)
            ->whereNotIn('employee_id', $departmentHeadIds->all())
            ->delete();

        foreach ($departmentHeadIds as $departmentHeadId) {
            DB::table('dpcr_details')->updateOrInsert(
                ['dpcr_header_id' => $dpcrHeaderId, 'employee_id' => $departmentHeadId],
                [
                    'dpcr_header_id' => $dpcrHeaderId,
                    'employee_dpcr_id' => $departmentHeadId,
                    'employee_id' => $departmentHeadId,
                    'status_id' => null,
                    'created_at' => $today,
                    'updated_at' => $today,
                ]
            );
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Delete related records first
            DB::table('dpcr_details')->where('dpcr_header_id', $id)->delete();
            DB::table('dpcr_headers')->where('id', $id)->delete();

            // Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'DPCR',
                'activity' => 'Delete',
                'description' => 'Deleted DPCR information.',
            );

            Audit::create($data_audit);

            DB::commit();

            return $this->successResponse(null, 'DPCR deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to delete DPCR: ' . $e->getMessage());
        }
    }
}
