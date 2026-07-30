<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OPCRController extends Controller
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
            $opcr_ratings = DB::table('opcr_headers as a')
                ->leftJoin('divisions as e', 'a.division_id', '=', 'e.id')
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
                    DB::raw("case when a.division_id = 0 then 'No division' else e.name end as division"),
                )
                ->get();

            return $this->successResponse($opcr_ratings, 'OPCR ratings retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve OPCR ratings: ' . $e->getMessage());
        }
    }

    public function add($id)
    {
        try {
            $divisions = DB::table('divisions')->where('active', true)->orderBy('name', 'asc')->get();

            if ($id <> 0) {
                $opcr_ratings = DB::table('opcr_headers as a')
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
                $opcr_ratings_dummy = array(
                    'id' => 0,
                    'division_id' => 0,
                    'year' => date('Y'),
                    'month_from' => 0,
                    'month_to' => 0,
                );

                $opcr_ratings = (object)$opcr_ratings_dummy;
                $opcr_ratings = collect([$opcr_ratings]);
            }

            return $this->successResponse([
                'opcr_ratings' => $opcr_ratings,
                'divisions' => $divisions,
            ], 'OPCR form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load OPCR form data: ' . $e->getMessage());
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

            $is_exists = DB::table('opcr_headers')->where([
                'division_id' => $request->division,
                'month_from' => $request->month_from,
                'month_to' => $request->month_to,
                'year' => $request->year,
            ])
                ->where('id', '<>', $request->id)
                ->get();

            if ($is_exists->isNotEmpty()) {
                return $this->errorResponse('Save failed. OPCR with same values already exists.');
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
                $maxId = DB::table('opcr_headers')->max('id');
                $id = $maxId ? $maxId + 1 : 1;
            }

            if ($request->id == 0 || $request->id == null) {
                $data['created_at'] = $now;

                // Only use IDENTITY_INSERT if using SQL Server
                if (DB::getDriverName() === 'sqlsrv') {
                    DB::unprepared('SET IDENTITY_INSERT opcr_headers ON');
                }
                DB::table('opcr_headers')->updateOrInsert(['id' => $id], $data);
                if (DB::getDriverName() === 'sqlsrv') {
                    DB::unprepared('SET IDENTITY_INSERT opcr_headers OFF');
                }

                // Auto-create OPCR detail for the office head under this office (division).
                // Office head is stored in divisions.division_chief_id (employee id).
                $divisionChiefId = DB::table('divisions')
                    ->where('id', $request->division)
                    ->value('division_chief_id');

                if (!empty($divisionChiefId) && (int)$divisionChiefId !== 0) {
                    // opcr_details schema: id, employee_opcr_id, opcr_header_id, employee_id, status_id, created_at, updated_at
                    $today = now()->toDateString();

                    DB::table('opcr_details')->updateOrInsert(
                        ['opcr_header_id' => $id, 'employee_id' => $divisionChiefId],
                        [
                        'opcr_header_id' => $id,
                            'employee_opcr_id' => $divisionChiefId,
                            'employee_id' => $divisionChiefId,
                        'status_id' => null,
                            'created_at' => $today,
                            'updated_at' => $today,
                        ]
                    );
                }
            } else {
                DB::table('opcr_headers')->where('id', $id)->update($data);
            }

            //Save audit trail
            if ($request->id == 0) {
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'HR Module',
                    'menu'    => 'OPCR',
                    'activity' => 'Add',
                    'description' => 'Added OPCR informations.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'OPCR information added successfully');
            } else {
                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'HR Module',
                    'menu'    => 'OPCR',
                    'activity' => 'Update',
                    'description' => 'Updated OPCR informations.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'OPCR information updated successfully');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save OPCR information: ' . $e->getMessage());
        }
    }

    public function review($id)
    {
        try {
            $app_key = env("APP_KEY", "");
            
            $opcr_details = DB::table('opcr_details')->where('opcr_header_id', $id)->get();

            if ($opcr_details->isEmpty()) {
                // Auto-heal: if OPCR details are missing, derive office head from divisions.division_chief_id
                $headerDivisionId = DB::table('opcr_headers')->where('id', $id)->value('division_id');
                $divisionChiefId = $headerDivisionId
                    ? DB::table('divisions')->where('id', $headerDivisionId)->value('division_chief_id')
                    : null;

                if (!empty($divisionChiefId) && (int)$divisionChiefId !== 0) {
                    // opcr_details schema: id, employee_opcr_id, opcr_header_id, employee_id, status_id, created_at, updated_at
                    $today = now()->toDateString();

                    DB::table('opcr_details')->updateOrInsert(
                        ['opcr_header_id' => $id, 'employee_id' => $divisionChiefId],
                        [
                            'opcr_header_id' => $id,
                            'employee_opcr_id' => $divisionChiefId,
                            'employee_id' => $divisionChiefId,
                            'status_id' => null,
                            'created_at' => $today,
                            'updated_at' => $today,
                        ]
                    );
                    $opcr_details = DB::table('opcr_details')->where('opcr_header_id', $id)->get();
                }

                if ($opcr_details->isEmpty()) {
                    return $this->errorResponse('There are no office heads to review under this OPCR.');
                }
            }

            // OPCR has details (or has been auto-healed above); build list from employee_opcr filtering for office heads
                $header = DB::table('opcr_headers as a')
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

                // Get office heads from opcr_details (employee_opcr_id contains the office head employee_id)
                $rows = DB::table('opcr_details as d')
                    ->where('d.opcr_header_id', $id)
                    ->whereNotNull('d.employee_opcr_id')
                    ->join('employees as emp', 'emp.id', '=', 'd.employee_opcr_id')
                    ->leftJoin('status as s', 's.id', '=', 'd.status_id')
                ->leftJoin('employee_opcr as eo', 'eo.employee_id', '=', 'emp.id')
                    ->leftJoin('positions as pos', 'pos.id', '=', 'emp.position_id')
                    ->select(
                        DB::raw("ISNULL(d.id, 0) as opcr_detail_id"),
                    'eo.id as employee_opcr_id',
                        DB::raw("ISNULL(d.opcr_header_id, $id) as opcr_header_id"),
                        'd.status_id',
                        's.name as status_name',
                        'd.created_at',
                        'd.updated_at',
                        'emp.id as employee_id',
                    'eo.division',
                    'eo.period_start',
                    'eo.period_end',
                    // employee_opcr schema uses reviewed_by_employee_id (not reviewed_by)
                    'eo.reviewed_by_employee_id',
                    'eo.reviewed_date',
                    'eo.approved_by',
                    'eo.approved_date',
                    'eo.assessed_by',
                    'eo.assessed_date',
                    'eo.final_rater',
                    'eo.final_rate_date',
                    // planning officer fields (replaced reviewed_by in form)
                    'eo.planning_officer_id',
                    'eo.planning_officer_date',
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
                    return $this->errorResponse('There are no office heads to review under this OPCR.');
                }

            $employeeOpcrIds = $rows->pluck('employee_opcr_id')->filter()->values()->all();

            // Self-assessment outputs (only if employee_opcr records exist)
                $outputs = collect();
            if (!empty($employeeOpcrIds)) {
                $outputs = DB::table('employee_opcr_outputs')
                    ->whereIn('employee_opcr_id', $employeeOpcrIds)
                        ->get();
                }

                // Recalibrations per output (supervisor, HR, PMT)
                $recalibrations = collect();
                if ($outputs->isNotEmpty()) {
                $recalibrations = DB::table('opcr_recalibrations as r')
                        ->leftJoin('status as s', 's.id', '=', 'r.status')
                    ->whereIn('r.employee_opcr_output_id', $outputs->pluck('id')->all())
                        ->select('r.*', 's.name as status_name')
                        ->orderByRaw("
                            CASE r.recalibration_level
                                WHEN 'supervisor' THEN 1
                                WHEN 'hr' THEN 2
                                WHEN 'pmt' THEN 3
                                ELSE 4
                            END
                        ")
                        ->orderBy('r.id')
                        ->get()
                    ->groupBy('employee_opcr_output_id');
                }

                // Group outputs by employee for easy attachment
            $outputsByEmployee = $outputs->groupBy('employee_opcr_id');

            $response = $rows->map(function ($row) use ($outputsByEmployee, $recalibrations, $divisionName, $header) {
                $employeeOutputs = $outputsByEmployee->get($row->employee_opcr_id, collect())->map(function ($output) use ($recalibrations) {
                        $output->recalibrations = $recalibrations->get($output->id, collect())->values();
                        return $output;
                    })->values();

                    // Derive recalibration status per level and overall
                    $levels = ['supervisor', 'hr', 'pmt'];
                    $levelStatus = [
                        'self' => 'pending',
                        'supervisor' => null,
                        'hr' => null,
                        'pmt' => null,
                    ];

                    $employeeRecalibrations = $employeeOutputs
                        ->pluck('recalibrations')
                        ->flatten(1);

                    foreach ($levels as $level) {
                        $latest = $employeeRecalibrations
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
                    } elseif ($levelStatus['hr']) {
                        $overall = 'hr - ' . $levelStatus['hr'];
                    } elseif ($levelStatus['supervisor']) {
                        $overall = 'supervisor - ' . $levelStatus['supervisor'];
                    }

                    $row->outputs = $employeeOutputs;
                    $row->recalibration_levels = $levelStatus;
                    $row->recalibration_overall = $overall;
                $row->division = $divisionName;
                    $row->year = $header->year ?? null;
                    return $row;
                });

                return $this->successResponse($response, 'OPCR review data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve OPCR review data: ' . $e->getMessage());
        }
    }

    public function opcr_adjective($rating)
    {
        try {
            $data = DB::table('adjectival_ratings')
                ->select('adjectival_rating')
                ->where('numerical_rating1', '<=', $rating)
                ->where('numerical_rating2', '>=', $rating)
                ->get();

            return $this->successResponse($data, 'Adjectival rating retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve adjectival rating: ' . $e->getMessage());
        }
    }

    public function rating(Request $request, $id)
    {
        try {
            $data = $request->all();
            $processed_count = 0;

            $opcr_details = [];

            for ($i = 0; $i < count($data['id']); $i++) {
                if (isset($data['attachment'][$i])) {
                    $attachment = $data['attachment'][$i]->getClientOriginalName();
                    $data['attachment'][$i]->storeAs('opcr_file', $data['id'][$i] . '_' . $attachment, 'public');
                } else {
                    if ($data['attachment_data'][$i] == '' || $data['attachment_data'][$i] == null) {
                        $attachment = '';
                    } else {
                        $attachment = $data['attachment_data'][$i];
                    }
                }

                $opcr_details = [
                    'opcr_header_id' => $id,
                    'employee_opcr_id' => $data['id'][$i], // Store office head employee_id
                    'employee_id' => $data['id'][$i],
                    'rating' => $data['numerical_rating'][$i] == null ? 0 : $data['numerical_rating'][$i],
                    'adjectival_rating' => $data['adjectival_rating'][$i] == null ? '' : $data['adjectival_rating'][$i],
                    'attachment' => $attachment,
                    'progress' => '',
                    'status_id' => null // Can be set later when status is determined
                ];

                // Save office head to opcr_details
                DB::table('opcr_details')->updateOrInsert(['opcr_header_id' => $id, 'employee_id' => $data['id'][$i]], $opcr_details);
                $processed_count++;
            }

            return $this->successResponse(['processed_count' => $processed_count], 'OPCR data saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save OPCR data: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $opcr = DB::table('opcr_headers')->where('id', $id)->first();
            if (!$opcr) {
                return $this->notFoundResponse('OPCR not found.');
            }

            DB::beginTransaction();

            // Delete related opcr_details
            DB::table('opcr_details')->where('opcr_header_id', $id)->delete();

            // Delete the opcr header
            DB::table('opcr_headers')->where('id', $id)->delete();

            DB::commit();

            // Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'OPCR',
                'activity' => 'Delete',
                'description' => 'Deleted OPCR record.',
            );
            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'OPCR deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to delete OPCR: ' . $e->getMessage());
        }
    }
}
