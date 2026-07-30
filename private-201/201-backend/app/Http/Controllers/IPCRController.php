<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class IPCRController extends Controller
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
            $ipcr_ratings = DB::table('ipcr_headers as a')
                ->join('sections as d', 'a.section_id', '=', 'd.id')
                ->join('months as f', 'a.month_from', '=', 'f.id')
                ->join('months as g', 'a.month_to', '=', 'g.id')
                ->select(
                    'a.id',
                    'a.section_id',
                    'a.year',
                    'a.month_from as month_from_id',
                    'a.month_to as month_to_id',
                    'd.name as section',
                    'f.name as month_from',
                    'g.name as month_to',
                )
                ->get();

            return $this->successResponse($ipcr_ratings, 'IPCR ratings retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve IPCR ratings: ' . $e->getMessage());
        }
    }

    public function add($id)
    {
        try {
            // IPCR is section-only (department/division removed from ipcr_headers)
            $sections = DB::table('sections as s')
                ->leftJoin('divisions as d', 'd.id', '=', 's.division_id')
                ->leftJoin('departments as dep', 'dep.id', '=', 'd.department_id')
                ->where('s.active', true)
                ->orderBy('s.name', 'asc')
                ->select(
                    's.id',
                    's.name',
                    's.division_id',
                    's.code',
                    's.active',
                    DB::raw("ISNULL(d.name,'') as division_name"),
                    DB::raw("ISNULL(dep.name,'') as department_name")
                )
                ->get();

            if ($id <> 0) {
                $ipcr_ratings = DB::table('ipcr_headers as a')
                    ->select(
                        'a.id',
                        'a.section_id',
                        'a.year',
                        'a.month_from',
                        'a.month_to',
                    )
                    ->where('a.id', $id)
                    ->get();
            } else {
                $ipcr_ratings_dummy = array(
                    'id' => 0,
                    'section_id' => 0,
                    'year' => date('Y'),
                    'month_from' => 0,
                    'month_to' => 0,
                );

                $ipcr_ratings = (object)$ipcr_ratings_dummy;
                $ipcr_ratings = collect([$ipcr_ratings]);
            }

            return $this->successResponse([
                'ipcr_ratings' => $ipcr_ratings,
                'sections' => $sections
            ], 'IPCR form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load IPCR form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'section' => 'required',
                'month_from' => 'required',
                'month_to' => 'required',
                'year' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $is_exists = DB::table('ipcr_headers')->where([
                'section_id' => $request->section,
                'month_from' => $request->month_from,
                'month_to' => $request->month_to,
                'year' => $request->year,
            ])
                ->where('id', '<>', $request->id)
                ->get();

            if ($is_exists->isNotEmpty()) {
                return $this->errorResponse('Save failed. IPCR with same values already exists.');
            }

            $now = now();

            $data = array(
                'section_id' => $request->section,
                'month_from' => $request->month_from,
                'month_to' => $request->month_to,
                'year' => $request->year,
                'is_posted' => true,
                'updated_at' => $now,
            );

            $id = $request->id;

            if ($id == 0 || $id == null) {
                $id = DB::table('ipcr_headers')->max('id') + 1;
            }

            if ($request->id == 0 || $request->id == null) {
                // Ensure created_at on insert
                $data['created_at'] = $now;

                DB::unprepared('SET IDENTITY_INSERT ipcr_headers ON');
                DB::table('ipcr_headers')->updateOrInsert(['id' => $id], $data);
                DB::unprepared('SET IDENTITY_INSERT ipcr_headers OFF');
            } else {
                DB::table('ipcr_headers')->where('id', $id)->update($data);
            }

            //Save audit trail
            if ($request->id == 0) {

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'HR Module',
                    'menu'    => 'IPCR',
                    'activity' => 'Add',
                    'description' => 'Added IPCR informations.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'IPCR information added successfully');
            } else {

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'HR Module',
                    'menu'    => 'IPCR',
                    'activity' => 'Update',
                    'description' => 'Updated IPCR informations.',
                );

                Audit::create($data_audit);

                return $this->successResponse(['id' => $id], 'IPCR information updated successfully');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save IPCR information: ' . $e->getMessage());
        }
    }

    public function review($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            // Build list from employees in the header's department/division/section,
            // with optional employee_ipcr + ipcr_details for ratings/status.
            $header = DB::table('ipcr_headers as a')
                ->leftJoin('months as f', 'a.month_from', '=', 'f.id')
                ->leftJoin('months as g', 'a.month_to', '=', 'g.id')
                ->select(
                    'a.*',
                    'a.month_from as month_from_id',
                    'a.month_to as month_to_id',
                    'f.name as month_from',
                    'g.name as month_to'
                )
                ->where('a.id', $id)
                ->first();
            $sectionName = $header && Schema::hasTable('sections') ? DB::table('sections')->where('id', $header->section_id)->value('name') : null;

            $rows = DB::table('employees as emp')
                ->leftJoin('employee_ipcr as ei', 'ei.employee_id', '=', 'emp.id')
                ->leftJoin('ipcr_details as d', function ($join) use ($id) {
                    $join->on('d.employee_ipcr_id', '=', 'ei.id')
                        ->where('d.ipcr_header_id', '=', $id);
                })
                ->leftJoin('status as s', 's.id', '=', 'd.status_id')
                ->leftJoin('positions as pos', 'pos.id', '=', 'emp.position_id')
                ->select(
                    DB::raw("ISNULL(d.id, 0) as ipcr_detail_id"),
                    'ei.id as employee_ipcr_id',
                    DB::raw("ISNULL(d.ipcr_header_id, $id) as ipcr_header_id"),
                    'd.status_id',
                    's.name as status_name',
                    'd.created_at',
                    'd.updated_at',
                    'emp.id as employee_id',
                    'ei.employee_name',
                    'ei.period_start',
                    'ei.period_end',
                    'ei.reviewed_by',
                    'ei.reviewed_date',
                    'ei.approved_by',
                    'ei.approved_date',
                    'ei.comments',
                    'ei.employee_date',
                    'ei.assessed_by',
                    'ei.assessed_date',
                    'ei.final_rater',
                    'ei.final_rate_date',
                    'emp.employee_no',
                    DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.first_name ELSE dbo.ufn_DecryptString(emp.first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.middle_name ELSE dbo.ufn_DecryptString(emp.middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.last_name ELSE dbo.ufn_DecryptString(emp.last_name,'$app_key') END as last_name"),
                    'pos.name as position'
                )
                ->when($header && Schema::hasColumn('employees', 'section_id') && !is_null($header->section_id) && $header->section_id != 0, function ($q) use ($header) {
                        $q->where('emp.section_id', $header->section_id);
                })
                ->where([
                    'emp.is_employee' => true,
                    'emp.active' => true
                ])
                ->orderBy('emp.last_name', 'asc')
                ->get();

            if ($rows->isEmpty()) {
                return $this->errorResponse('There are no employees to review under this IPCR.');
            }

            // Only employees that already have employee_ipcr get outputs/recalibrations
            $employeeIpcrIds = $rows->pluck('employee_ipcr_id')->filter()->values()->all();

            // Self-assessment outputs
            $outputs = collect();
            if (!empty($employeeIpcrIds)) {
                $outputs = DB::table('employee_ipcr_outputs')
                    ->whereIn('employee_ipcr_id', $employeeIpcrIds)
                    ->get();
            }

            // Recalibrations per output (supervisor, HR, PMT)
            $recalibrations = collect();
            if ($outputs->isNotEmpty()) {
                // Join status table - r.status (integer ID) should match status.id
                $recalibrations = DB::table('ipcr_recalibrations as r')
                    ->leftJoin('status as s', 's.id', '=', 'r.status')
                    ->whereIn('r.employee_ipcr_output_id', $outputs->pluck('id')->all())
                    ->select('r.*', 's.name as status_name', 'r.status as status_id')
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
                    ->groupBy('employee_ipcr_output_id');

                // Debug: Log recalibrations to check join
                Log::info('IPCR Recalibrations fetched', [
                    'count' => $recalibrations->count(),
                    'sample' => $recalibrations->take(2)->toArray()
                ]);
            }

            // Group outputs by employee for easy attachment
            $outputsByEmployee = $outputs->groupBy('employee_ipcr_id');

            // Debug: Log outputs structure
            Log::info('Outputs grouped', [
                'outputs_count' => $outputs->count(),
                'employees_with_outputs' => $outputsByEmployee->keys()->toArray(),
                'sample_output_ids' => $outputs->take(5)->pluck('id')->toArray()
            ]);

            $response = $rows->map(function ($row) use ($outputsByEmployee, $recalibrations, $sectionName, $header) {
                $employeeOutputs = $outputsByEmployee->get($row->employee_ipcr_id, collect())->map(function ($output) use ($recalibrations) {
                    $output->recalibrations = $recalibrations->get($output->id, collect())->values();
                    return $output;
                })->values();

                // Debug: Log per employee
                if ($row->employee_ipcr_id) {
                    Log::info('Employee IPCR data', [
                        'employee_ipcr_id' => $row->employee_ipcr_id,
                        'outputs_count' => $employeeOutputs->count(),
                        'output_ids' => $employeeOutputs->pluck('id')->toArray(),
                        'recalibrations_count' => $employeeOutputs->pluck('recalibrations')->flatten(1)->count()
                    ]);
                }

                // Derive recalibration status per level and overall
                $levels = ['supervisor', 'hr', 'pmt'];
                $levelStatus = [
                    'self' => $employeeOutputs->isNotEmpty() ? 'submitted' : 'pending', // Check if self-assessment has outputs
                    'supervisor' => null,
                    'hr' => null,
                    'pmt' => null,
                ];

                // Flatten recalibrations for this employee
                $employeeRecalibrations = $employeeOutputs
                    ->pluck('recalibrations')
                    ->flatten(1);

                foreach ($levels as $level) {
                    // Get all recalibrations for this level
                    $levelRecalibrations = $employeeRecalibrations
                        ->where('recalibration_level', $level);

                    if ($levelRecalibrations->isNotEmpty()) {
                        // Get the latest recalibration for this level
                        $latest = $levelRecalibrations->sortByDesc('created_at')->first();

                        // Get status from status table (status_name) - this comes from the joined status table
                        if ($latest) {
                            // status_name is from the status table join - use it directly
                            if (!empty($latest->status_name)) {
                                $levelStatus[$level] = $latest->status_name;
                            } elseif (!empty($latest->status_id)) {
                                // If status_name is null (join failed), fetch directly from status table
                                $statusName = DB::table('status')
                                    ->where('id', $latest->status_id)
                                    ->value('name');
                                $levelStatus[$level] = $statusName ?: 'pending';
                            } else {
                                // Recalibration exists but no status - default to 'pending'
                                $levelStatus[$level] = 'pending';
                            }
                        }
                    }
                }

                // Overall status: take the highest level present with its status
                $overall = 'pending';
                if ($levelStatus['pmt']) {
                    $overall = 'pmt - ' . $levelStatus['pmt'];
                } elseif ($levelStatus['hr']) {
                    $overall = 'hr - ' . $levelStatus['hr'];
                } elseif ($levelStatus['supervisor']) {
                    $overall = 'supervisor - ' . $levelStatus['supervisor'];
                } elseif ($levelStatus['self'] === 'submitted') {
                    $overall = 'self - submitted';
                }

                $row->outputs = $employeeOutputs;
                $row->recalibration_levels = $levelStatus;
                $row->recalibration_overall = $overall;
                // IPCR meta for header display
                $row->section = $sectionName;
                $row->year = $header->year ?? null;
                // Use months from ipcr_headers if period_start/period_end are not in employee_ipcr
                $row->period_start = $row->period_start ?: ($header->month_from ?? null);
                $row->period_end = $row->period_end ?: ($header->month_to ?? null);
                $row->month_from = $header->month_from ?? null;
                $row->month_to = $header->month_to ?? null;
                $row->month_from_id = $header->month_from_id ?? null;
                $row->month_to_id = $header->month_to_id ?? null;
                return $row;
            });

            return $this->successResponse($response, 'IPCR review data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve IPCR review data: ' . $e->getMessage());
        }
    }

    public function ipcr_adjective($rating)
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

            $ipcr_details = [];

            for ($i = 0; $i < count($data['id']); $i++) {
                if (isset($data['attachment'][$i])) {
                    $attachment = $data['attachment'][$i]->getClientOriginalName();
                    $data['attachment'][$i]->storeAs('ipcr_file', $data['id'][$i] . '_' . $attachment, 'public');
                } else {
                    if ($data['attachment_data'][$i] == '' || $data['attachment_data'][$i] == null) {
                        $attachment = '';
                    } else {
                        $attachment = $data['attachment_data'][$i];
                    }
                }

                $ipcr_details = [
                    'ipcr_header_id' => $id,
                    'employee_id' => $data['id'][$i],
                    'rating' => $data['numerical_rating'][$i] == null ? 0 : $data['numerical_rating'][$i],
                    'adjectival_rating' => $data['adjectival_rating'][$i] == null ? '' : $data['adjectival_rating'][$i],
                    'attachment' => $attachment,
                    'progress' => ''
                ];

                DB::table('ipcr_details')->updateOrInsert(['ipcr_header_id' => $id, 'employee_id' => $data['id'][$i]], $ipcr_details);
                $processed_count++;
            }

            return $this->successResponse(['processed_count' => $processed_count], 'IPCR data saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save IPCR data: ' . $e->getMessage());
        }
    }

    /**
     * Summary of ratings report (PDF).
     */
    public function summaryRatings($id)
    {
        try {
            $header = DB::table('ipcr_headers')->where('id', $id)->first();
            if (!$header) {
                return $this->notFoundResponse('IPCR header not found.');
            }

            $app_key = env('APP_KEY');
            $hasKey = !empty($app_key);

            // Find employees based on header's section (section-only IPCR)
            $rows = DB::table('employees as emp')
                ->join('employee_ipcr as ei', 'ei.employee_id', '=', 'emp.id')
                ->leftJoin('positions as pos', 'pos.id', '=', 'emp.position_id')
                ->when(Schema::hasColumn('employees', 'section_id') && !is_null($header->section_id) && $header->section_id != 0, function ($q) use ($header) {
                    $q->where('emp.section_id', $header->section_id);
                })
                ->leftJoin('sections as sec', 'sec.id', '=', 'emp.section_id')
                ->where('emp.is_employee', true)
                ->where('emp.active', true)
                ->select(
                    'ei.id as employee_ipcr_id',
                    'emp.employee_no',
                    $hasKey
                        ? DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.first_name ELSE dbo.ufn_DecryptString(emp.first_name,'$app_key') END as first_name")
                        : DB::raw("emp.first_name as first_name"),
                    $hasKey
                        ? DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.middle_name ELSE dbo.ufn_DecryptString(emp.middle_name,'$app_key') END as middle_name")
                        : DB::raw("emp.middle_name as middle_name"),
                    $hasKey
                        ? DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.last_name ELSE dbo.ufn_DecryptString(emp.last_name,'$app_key') END as last_name")
                        : DB::raw("emp.last_name as last_name"),
                    'pos.name as position',
                    DB::raw("ISNULL(sec.name,'') as section"),
                    'ei.period_start',
                    'ei.period_end'
                )
                ->orderBy('last_name')
                ->get();

            if ($rows->isEmpty()) {
                return $this->errorResponse('No employees found for this IPCR.');
            }

            $employeeIpcrIds = $rows->pluck('employee_ipcr_id')->all();
            $outputs = DB::table('employee_ipcr_outputs')
                ->whereIn('employee_ipcr_id', $employeeIpcrIds)
                ->get()
                ->groupBy('employee_ipcr_id');

            // Filter to only employees with outputs (self-assessment submitted)
            $rows = $rows->filter(function ($row) use ($outputs) {
                return $outputs->has($row->employee_ipcr_id);
            })->values();

            if ($rows->isEmpty()) {
                return $this->errorResponse('No employees with submitted assessments found for this IPCR.');
            }

            $summary = $rows->map(function ($row, $idx) use ($outputs) {
                $empOutputs = $outputs->get($row->employee_ipcr_id, collect());
                $ratings = $empOutputs->pluck('average_rating')
                    ->filter(function ($v) {
                        return $v !== null;
                    })
                    ->map(function ($v) {
                        return (float) $v;
                    })
                    ->values();
                $avg = $ratings->count() ? round($ratings->avg(), 2) : null;
                return [
                    'no' => $idx + 1,
                    'name' => trim(($row->first_name ?? '') . ' ' . ($row->middle_name ?? '') . ' ' . ($row->last_name ?? '')),
                    'position' => $row->position ?? '',
                    'section' => $row->section ?? '',
                    'performance' => $avg !== null ? number_format($avg, 2) : 'N/A',
                ];
            });

            $pdf = \PDF::loadView('ipcr.summary_ratings', [
                'summary' => $summary,
                'header' => $header,
            ])->setPaper('A4', 'portrait');

            return $pdf->stream('summary_of_ratings.pdf');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate summary of ratings: ' . $e->getMessage());
        }
    }

    /**
     * PMT Calibration Results report (PDF).
     */
    public function pmtCalibration($id)
    {
        try {
            $header = DB::table('ipcr_headers')->where('id', $id)->first();
            if (!$header) {
                return $this->notFoundResponse('IPCR header not found.');
            }

            $app_key = env('APP_KEY');
            $hasKey = !empty($app_key);

            // Find employees based on header's section (section-only IPCR)
            $rows = DB::table('employees as emp')
                ->join('employee_ipcr as ei', 'ei.employee_id', '=', 'emp.id')
                ->leftJoin('positions as pos', 'pos.id', '=', 'emp.position_id')
                ->when(Schema::hasColumn('employees', 'section_id') && !is_null($header->section_id) && $header->section_id != 0, function ($q) use ($header) {
                    $q->where('emp.section_id', $header->section_id);
                })
                ->leftJoin('sections as sec', 'sec.id', '=', 'emp.section_id')
                ->where('emp.is_employee', true)
                ->where('emp.active', true)
                ->select(
                    'ei.id as employee_ipcr_id',
                    'emp.employee_no',
                    $hasKey
                        ? DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.first_name ELSE dbo.ufn_DecryptString(emp.first_name,'$app_key') END as first_name")
                        : DB::raw("emp.first_name as first_name"),
                    $hasKey
                        ? DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.middle_name ELSE dbo.ufn_DecryptString(emp.middle_name,'$app_key') END as middle_name")
                        : DB::raw("emp.middle_name as middle_name"),
                    $hasKey
                        ? DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.last_name ELSE dbo.ufn_DecryptString(emp.last_name,'$app_key') END as last_name")
                        : DB::raw("emp.last_name as last_name"),
                    'pos.name as position',
                    DB::raw("ISNULL(sec.name,'') as section"),
                    'ei.period_start',
                    'ei.period_end'
                )
                ->orderBy('last_name')
                ->get();

            if ($rows->isEmpty()) {
                return $this->errorResponse('No employees found for this IPCR.');
            }

            $employeeIpcrIds = $rows->pluck('employee_ipcr_id')->all();

            // Filter to only employees with outputs (self-assessment submitted)
            $outputs = DB::table('employee_ipcr_outputs')
                ->whereIn('employee_ipcr_id', $employeeIpcrIds)
                ->get()
                ->groupBy('employee_ipcr_id');

            $rows = $rows->filter(function ($row) use ($outputs) {
                return $outputs->has($row->employee_ipcr_id);
            })->values();

            if ($rows->isEmpty()) {
                return $this->errorResponse('No employees with submitted assessments found for this IPCR.');
            }

            // Re-fetch employeeIpcrIds after filtering
            $employeeIpcrIds = $rows->pluck('employee_ipcr_id')->all();

            // Recalibrations
            $recalibrations = DB::table('ipcr_recalibrations')
                ->whereIn('employee_ipcr_output_id', $outputs->flatten()->pluck('id')->all())
                ->get()
                ->groupBy('employee_ipcr_output_id');

            $summary = $rows->map(function ($row) use ($outputs, $recalibrations) {
                $empOutputs = $outputs->get($row->employee_ipcr_id, collect());
                $selfRatings = $empOutputs->pluck('average_rating')->filter()->map(fn($v) => (float)$v);
                $selfAvg = $selfRatings->count() ? round($selfRatings->avg(), 2) : null;

                $levels = ['supervisor', 'hr', 'pmt'];
                $levelAvg = [
                    'supervisor' => null,
                    'hr' => null,
                    'pmt' => null,
                ];

                // For each output, pick latest recalibration per level and average across outputs
                foreach ($levels as $level) {
                    $levelValues = $empOutputs->map(function ($o) use ($recalibrations, $level) {
                        $recs = $recalibrations->get($o->id, collect());
                        $latest = $recs->where('recalibration_level', $level)->sortByDesc('created_at')->first();
                        return $latest ? (float)$latest->average_rating : null;
                    })->filter();
                    $levelAvg[$level] = $levelValues->count() ? round($levelValues->avg(), 2) : null;
                }

                return [
                    'name' => trim(($row->first_name ?? '') . ' ' . ($row->middle_name ?? '') . ' ' . ($row->last_name ?? '')),
                    'section' => $row->section ?? '',
                    'self' => $selfAvg !== null ? number_format($selfAvg, 2) : '',
                    'supervisor' => $levelAvg['supervisor'] !== null ? number_format($levelAvg['supervisor'], 2) : '',
                    'hr' => $levelAvg['hr'] !== null ? number_format($levelAvg['hr'], 2) : '',
                    'pmt' => $levelAvg['pmt'] !== null ? number_format($levelAvg['pmt'], 2) : '',
                ];
            });

            $pdf = \PDF::loadView('ipcr.pmt_calibration', [
                'summary' => $summary,
                'header' => $header,
            ])->setPaper('A4', 'portrait');

            return $pdf->stream('pmt_calibration_results.pdf');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate PMT calibration results: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $ipcr = DB::table('ipcr_headers')->where('id', $id)->first();
            if (!$ipcr) {
                return $this->notFoundResponse('IPCR not found.');
            }

            DB::beginTransaction();

            // Delete related ipcr_details
            DB::table('ipcr_details')->where('ipcr_header_id', $id)->delete();

            // Delete the ipcr header
            DB::table('ipcr_headers')->where('id', $id)->delete();

            DB::commit();

            // Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'IPCR',
                'activity' => 'Delete',
                'description' => 'Deleted IPCR record.',
            );
            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'IPCR deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to delete IPCR: ' . $e->getMessage());
        }
    }
}
