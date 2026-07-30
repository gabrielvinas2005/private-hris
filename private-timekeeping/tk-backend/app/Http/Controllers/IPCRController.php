<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->join('departments as c', 'a.department_id', '=', 'c.id')
            ->join('semester_ratings as d', 'a.semester_id', 'd.id')
            ->leftJoin('divisions as e', 'a.division_id', '=', 'e.id')
            ->join('months as f', 'a.month_from', '=', 'f.id')
            ->join('months as g', 'a.month_to', '=', 'g.id')
            ->select(
                'a.id',
                'a.department_id',
                'a.division_id',
                'a.semester_id',
                'a.month_from as month_from_id',
                'a.month_to as month_to_id',
                'c.name as department',
                'd.name as semester',
                'f.name as month_from',
                'g.name as month_to',
                DB::raw("case when a.division_id = 0 then 'No division' else e.name end as division"),
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
            $departments = DB::table('departments as a')->where('a.active', true)
                ->whereIn('a.id', function ($query) {
                    $query->select('department_id')->from('employees')->distinct();
                })
                ->orderBy('a.name', 'asc')
                ->get();

            $divisions = DB::table('divisions')->where('active', true)->orderBy('name', 'asc')->get();
            $semesters = DB::table('semester_ratings')->where('active', true)->orderBy('id', 'asc')->get();

            if ($id <> 0) {
                $ipcr_ratings = DB::table('ipcr_headers as a')
                    ->select(
                        'a.id',
                        'a.department_id',
                        'a.division_id',
                        'a.semester_id',
                        'a.month_from',
                        'a.month_to',
                    )
                    ->where('a.id', $id)
                    ->get();
            } else {
                $ipcr_ratings_dummy = array(
                    'id' => 0,
                    'department_id' => 0,
                    'division_id' => 0,
                    'semester_id' => 0,
                    'month_from' => 0,
                    'month_to' => 0,
                );

                $ipcr_ratings = (object)$ipcr_ratings_dummy;
                $ipcr_ratings = collect([$ipcr_ratings]);
            }

            return $this->successResponse([
                'ipcr_ratings' => $ipcr_ratings,
                'departments' => $departments,
                'divisions' => $divisions,
                'semesters' => $semesters
            ], 'IPCR form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load IPCR form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'department' => 'required',
                'division' => 'required',
                'semester' => 'required',
                'month_from' => 'required',
                'month_to' => 'required',
                'year' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $is_exists = DB::table('ipcr_headers')->where([
                'department_id' => $request->department,
                'division_id' => $request->division,
                'semester_id' => $request->semester,
                'month_from' => $request->month_from,
                'month_to' => $request->month_to,
                'year' => $request->year,
            ])
                ->where('id', '<>', $request->id)
                ->get();

            if ($is_exists->isNotEmpty()) {
                return $this->errorResponse('Save failed. IPCR with same values already exists.');
            }

            $data = array(
                'department_id' => $request->department,
                'division_id' => $request->division,
                'semester_id' => $request->semester,
                'month_from' => $request->month_from,
                'month_to' => $request->month_to,
                'year' => $request->year,
                'is_posted' => true
            );

            $id = $request->id;

            if ($id == 0 || $id == null) {
                $id = DB::table('ipcr_headers')->max('id') + 1;
            }

            DB::unprepared('SET IDENTITY_INSERT ipcr_headers ON');
            DB::table('ipcr_headers')->updateOrInsert(['id' => $id], $data);
            DB::unprepared('SET IDENTITY_INSERT ipcr_headers OFF');

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

            $ipcr_details = DB::table('ipcr_details')->where('ipcr_header_id', $id)->get();

            if ($ipcr_details->isEmpty()) {

                if (Auth::user()->access_all_branches) {
                    $ipcr_ratings = DB::table('ipcr_headers as a')
                        ->join('departments as c', 'a.department_id', '=', 'c.id')
                        ->join('semester_ratings as d', 'a.semester_id', 'd.id')
                        ->leftJoin('divisions as e', 'a.division_id', '=', 'e.id')
                        ->join('months as f', 'a.month_from', '=', 'f.id')
                        ->join('months as g', 'a.month_to', '=', 'g.id')
                        ->join('employees as h', 'a.department_id', '=', 'h.department_id')
                        ->join('positions as i', 'h.position_id', '=', 'i.id')
                        ->select(
                            'a.id',
                            'a.department_id',
                            'a.division_id',
                            'a.semester_id',
                            'a.year',
                            'a.month_from as month_from_id',
                            'a.month_to as month_to_id',
                            'c.name as department',
                            'd.name as semester',
                            'f.name as month_from',
                            'g.name as month_to',
                            DB::raw("case when a.division_id = 0 then 'No division' else e.name end as division"),
                            'h.photo',
                            'h.employee_no',
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.first_name ELSE dbo.ufn_DecryptString(h.first_name,'$app_key') END as first_name"),
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.middle_name ELSE dbo.ufn_DecryptString(h.middle_name,'$app_key') END as middle_name"),
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.last_name ELSE dbo.ufn_DecryptString(h.last_name,'$app_key') END as last_name"),
                            'i.name as position',
                            db::raw("'' as numerical_rating"),
                            db::raw("'' as adjectival_rating"),
                            db::raw("'' as attachment"),
                            'h.id as employee_id'
                        )
                        ->where('a.id', $id)
                        ->where('h.is_employee', true)
                        ->orderBy('h.last_name', 'asc')
                        ->get();
                } else {
                    $user_branch_id = DB::table('users as a')
                        ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                        ->select('b.branch_id')
                        ->where('a.id', Auth::user()->id)
                        ->get();

                    $ipcr_ratings = DB::table('ipcr_headers as a')
                        ->join('departments as c', 'a.department_id', '=', 'c.id')
                        ->join('semester_ratings as d', 'a.semester_id', 'd.id')
                        ->leftJoin('divisions as e', 'a.division_id', '=', 'e.id')
                        ->join('months as f', 'a.month_from', '=', 'f.id')
                        ->join('months as g', 'a.month_to', '=', 'g.id')
                        ->join('employees as h', 'a.department_id', '=', 'h.department_id')
                        ->join('positions as i', 'h.position_id', '=', 'i.id')
                        ->select(
                            'a.id',
                            'a.department_id',
                            'a.division_id',
                            'a.semester_id',
                            'a.year',
                            'a.month_from as month_from_id',
                            'a.month_to as month_to_id',
                            'c.name as department',
                            'd.name as semester',
                            'f.name as month_from',
                            'g.name as month_to',
                            DB::raw("case when a.division_id = 0 then 'No division' else e.name end as division"),
                            'h.photo',
                            'h.employee_no',
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.first_name ELSE dbo.ufn_DecryptString(h.first_name,'$app_key') END as first_name"),
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.middle_name ELSE dbo.ufn_DecryptString(h.middle_name,'$app_key') END as middle_name"),
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.last_name ELSE dbo.ufn_DecryptString(h.last_name,'$app_key') END as last_name"),
                            'i.name as position',
                            db::raw("'' as numerical_rating"),
                            db::raw("'' as adjectival_rating"),
                            db::raw("'' as attachment"),
                            'h.id as employee_id'
                        )
                        ->where('a.id', $id)
                        ->where([
                            'h.is_employee' => true,
                            'h.branch_id' => $user_branch_id[0]->branch_id
                        ])
                        ->orderBy('h.last_name', 'asc')
                        ->get();
                }

                if ($ipcr_ratings->isEmpty()) {
                    return $this->errorResponse('There are no employees to review under this department yet.');
                }
            } else {

                if (Auth::user()->access_all_branches) {
                    $ipcr_ratings = DB::table('ipcr_headers as a')
                        ->join('departments as c', 'a.department_id', '=', 'c.id')
                        ->join('semester_ratings as d', 'a.semester_id', 'd.id')
                        ->leftJoin('divisions as e', 'a.division_id', '=', 'e.id')
                        ->join('months as f', 'a.month_from', '=', 'f.id')
                        ->join('months as g', 'a.month_to', '=', 'g.id')
                        ->join('employees as h', 'a.department_id', '=', 'h.department_id')
                        ->join('positions as i', 'h.position_id', '=', 'i.id')
                        ->join('ipcr_details as j', function ($join) {
                            $join->on('a.id', '=', 'j.ipcr_header_id');
                            $join->on('h.id', '=', 'j.employee_id');
                        })
                        ->select(
                            'a.id',
                            'a.department_id',
                            'a.division_id',
                            'a.semester_id',
                            'a.year',
                            'a.month_from as month_from_id',
                            'a.month_to as month_to_id',
                            'c.name as department',
                            'd.name as semester',
                            'f.name as month_from',
                            'g.name as month_to',
                            DB::raw("case when a.division_id = 0 then 'No division' else e.name end as division"),
                            'h.photo',
                            'h.employee_no',
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.first_name ELSE dbo.ufn_DecryptString(h.first_name,'$app_key') END as first_name"),
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.middle_name ELSE dbo.ufn_DecryptString(h.middle_name,'$app_key') END as middle_name"),
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.last_name ELSE dbo.ufn_DecryptString(h.last_name,'$app_key') END as last_name"),
                            'i.name as position',
                            'j.rating as numerical_rating',
                            'j.adjectival_rating',
                            'j.attachment',
                            'h.id as employee_id'
                        )
                        ->where('a.id', $id)
                        ->where('h.is_employee', true)
                        ->orderBy('h.last_name', 'asc')
                        ->get();
                } else {
                    $user_branch_id = DB::table('users as a')
                        ->join('employees as b', 'a.employee_no', '=', 'b.employee_no')
                        ->select('b.branch_id')
                        ->where('a.id', Auth::user()->id)
                        ->get();

                    $ipcr_ratings = DB::table('ipcr_headers as a')
                        ->join('departments as c', 'a.department_id', '=', 'c.id')
                        ->join('semester_ratings as d', 'a.semester_id', 'd.id')
                        ->leftJoin('divisions as e', 'a.division_id', '=', 'e.id')
                        ->join('months as f', 'a.month_from', '=', 'f.id')
                        ->join('months as g', 'a.month_to', '=', 'g.id')
                        ->join('employees as h', 'a.department_id', '=', 'h.department_id')
                        ->join('positions as i', 'h.position_id', '=', 'i.id')
                        ->join('ipcr_details as j', function ($join) {
                            $join->on('a.id', '=', 'j.ipcr_header_id');
                            $join->on('h.id', '=', 'j.employee_id');
                        })
                        ->select(
                            'a.id',
                            'a.department_id',
                            'a.division_id',
                            'a.semester_id',
                            'a.year',
                            'a.month_from as month_from_id',
                            'a.month_to as month_to_id',
                            'c.name as department',
                            'd.name as semester',
                            'f.name as month_from',
                            'g.name as month_to',
                            DB::raw("case when a.division_id = 0 then 'No division' else e.name end as division"),
                            'h.photo',
                            'h.employee_no',
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.first_name ELSE dbo.ufn_DecryptString(h.first_name,'$app_key') END as first_name"),
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.middle_name ELSE dbo.ufn_DecryptString(h.middle_name,'$app_key') END as middle_name"),
                            DB::raw("CASE WHEN ISNULL(h.is_encrypted,0) = 0 THEN h.last_name ELSE dbo.ufn_DecryptString(h.last_name,'$app_key') END as last_name"),
                            'i.name as position',
                            'j.rating as numerical_rating',
                            'j.adjectival_rating',
                            'j.attachment',
                            'h.id as employee_id'
                        )
                        ->where('a.id', $id)
                        ->where([
                            'h.is_employee' => true,
                            'h.branch_id' => $user_branch_id[0]->branch_id
                        ])
                        ->orderBy('h.last_name', 'asc')
                        ->get();
                }
            }

            if ($ipcr_ratings->isEmpty()) {
                return $this->errorResponse('There are no employees to review under this department yet.');
            }

            return $this->successResponse($ipcr_ratings, 'IPCR review data retrieved successfully');
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
}
