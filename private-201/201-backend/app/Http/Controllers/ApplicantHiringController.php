<?php

namespace App\Http\Controllers;

use Auth;
use Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Notifications\EmailApplicantHiring;
use App\Notifications\EmailApplicantQualification;
use App\Notifications\EmailApplicantStatus;
use App\User;
use Carbon\Carbon;

class ApplicantHiringController extends Controller
{
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

        $data = DB::table('plantillas')
            ->join('positions', 'positions.id', '=', 'plantillas.position_id')
            ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
            ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
            ->join('departments', 'departments.id', '=', 'plantillas.department_id')
            ->leftJoin('applicant_details as a', function ($join) {
                $join->on('a.position_applied_id', '=', 'plantillas.id')
                    ->where('a.is_plantilla', true)
                    ->where('a.application_status_id', 1);
            })
            ->leftJoin('applicant_headers as ah', function ($join) {
                $join->on('ah.id', '=', 'a.applicant_id')
                    ->where('ah.application_status_id', 1);
            })
            ->select(
                'plantillas.id',
                'plantillas.code',
                'positions.name as position',
                'salary_steps.name as step',
                'salary_grades.name as grade',
                'departments.name as department',
                'plantillas.eligibility as eligibility',
                'plantillas.experience as experience',
                'plantillas.training as training',
                'plantillas.education as education',
                'plantillas.unit as unit',
                'plantillas.publication_from as publication_from',
                'plantillas.publication_to as publication_to',
                'plantillas.status as status',
                'plantillas.active',
                DB::raw("count(distinct ah.id) as total")
            )
            ->where('plantillas.employee_id', 0)
            ->where('plantillas.active', 1)
            ->where('plantillas.approved', true)
            ->groupBy(
                'plantillas.id',
                'plantillas.code',
                'positions.name',
                'salary_steps.name',
                'salary_grades.name',
                'departments.name',
                'plantillas.eligibility',
                'plantillas.experience',
                'plantillas.training',
                'plantillas.education',
                'plantillas.unit',
                'plantillas.publication_from',
                'plantillas.publication_to',
                'plantillas.status',
                'plantillas.active'
            )
            ->orderBy('positions.name', 'asc')
            ->get();

        $non_plantillas = DB::table('non_plantillas as a')
            ->join('positions as b', 'a.position_id', '=', 'b.id')
            ->join('departments as c', 'a.department_id', '=', 'c.id')
            ->leftJoin('applicant_details as d', function ($join) {
                $join->on('d.position_applied_id', '=', 'a.id')
                    ->where('d.is_plantilla', false)
                    ->where('d.application_status_id', 1);
            })
            ->leftJoin('applicant_headers as ah', function ($join) {
                $join->on('ah.id', '=', 'd.applicant_id')
                    ->where('ah.application_status_id', 1);
            })
            ->select(
                'a.id',
                'a.position_id',
                'b.name as position',
                'a.salary',
                'c.name as department',
                'a.eligibility',
                'a.experience',
                'a.education',
                'a.training',
                'a.description',
                'a.qualification',
                'a.vacant',
                'a.publication_from',
                'a.publication_to',
                DB::raw(
                    "case when a.status = 0 then 'Inactive' else 'Active' end as status"
                ),
                DB::raw("count(distinct ah.id) as total")
            )
            ->where('vacant', '>', 0)
            ->where('a.status', 1)
            ->groupBy(
                'a.id',
                'a.position_id',
                'b.name',
                'a.salary',
                'c.name',
                'a.eligibility',
                'a.experience',
                'a.education',
                'a.training',
                'a.description',
                'a.qualification',
                'a.vacant',
                'a.publication_from',
                'a.publication_to',
                'a.status'
            )
            ->orderBy('b.name', 'asc')
            ->get();

        return $this->successResponse([
            'data' => $data,
            'non_plantillas' => $non_plantillas
        ], 'Applicant hiring list retrieved successfully');
    }

    public function hiring($id, $type)
    {
        // Load applicants from the main HR database
        if ($type == 1) {

            $applicants = DB::table('applicant_headers as a')
                ->join('applicant_details as b', 'a.id', '=', 'b.applicant_id')
                ->join('plantillas as c', 'c.id', '=', 'b.position_applied_id')
                ->leftJoin('positions as d', 'd.id', '=', 'c.position_id')
                ->leftJoin('departments as e', 'e.id', '=', 'c.department_id')
                ->leftJoin('salary_steps as f', 'f.id', '=', 'c.salary_step_id')
                ->leftJoin('salary_grades as g', 'g.id', '=', 'c.salary_grade_id')
                ->leftJoin('genders as h', 'h.id', '=', 'a.gender')
                ->leftJoin('applicant_eete_ratings as i', 'a.id', '=', 'i.applicant_id')
                ->select(
                    'a.id',
                    'a.applicant_no',
                    'a.photo',
                    'a.first_name',
                    'a.middle_name',
                    'a.last_name',
                    'a.address',
                    'a.birth_date',
                    'a.age',
                    'h.name as gender',
                    'a.mobile_no',
                    'a.email',
                    'a.employee_no',
                    'a.application_status_id',
                    'a.application_date',
                    'c.id as plantilla_id',
                    'c.code',
                    'd.name as position',
                    'f.name as salary_step',
                    'g.name as salary_grade',
                    'e.name as department',
                    'c.eligibility',
                    'c.experience',
                    'c.training',
                    'c.education',
                    'c.unit',
                    'c.publication_from',
                    'c.publication_to',
                    DB::raw('1 as is_plantilla'),
                    'b.position_applied_id',
                    'b.application_status_id as detail_status_id',
                    DB::raw("isnull(i.reviewed_status_id,0) as status_id"),
                    'i.education_rating',
                    'i.experience_rating',
                    'i.training_rating',
                    'i.eligibility_rating',
                    'i.interview',
                    'i.bonus',
                    'i.is_education_passed',
                    'i.is_experience_passed',
                    'i.is_training_passed',
                    'i.is_eligibility_passed'
                )
                ->where('b.position_applied_id', $id)
                ->where('b.is_plantilla', true)
                ->where('b.application_status_id', 1)
                ->where('a.application_status_id', 1)
                ->where('c.employee_id', 0)
                ->orderBy('a.application_date', 'asc')
                ->get();
        } else {

            $applicants = DB::table('applicant_headers as a')
                ->join('applicant_details as b', 'a.id', '=', 'b.applicant_id')
                ->join('non_plantillas as c', 'c.id', '=', 'b.position_applied_id')
                ->leftJoin('positions as d', 'd.id', '=', 'c.position_id')
                ->leftJoin('departments as e', 'e.id', '=', 'c.department_id')
                ->leftJoin('genders as h', 'h.id', '=', 'a.gender')
                ->leftJoin('applicant_eete_ratings as i', 'a.id', '=', 'i.applicant_id')
                ->select(
                    'a.id',
                    'a.applicant_no',
                    'a.photo',
                    'a.first_name',
                    'a.middle_name',
                    'a.last_name',
                    'a.address',
                    'a.birth_date',
                    'a.age',
                    'h.name as gender',
                    'a.mobile_no',
                    'a.email',
                    'a.employee_no',
                    'a.application_status_id',
                    'a.application_date',
                    'c.id as plantilla_id',
                    'd.name as position',
                    'e.name as department',
                    'c.salary',
                    'c.eligibility',
                    'c.experience',
                    'c.training',
                    'c.education',
                    'c.vacant',
                    'c.description',
                    'c.qualification',
                    'c.publication_from',
                    'c.publication_to',
                    DB::raw('0 as is_plantilla'),
                    'b.position_applied_id',
                    'b.application_status_id as detail_status_id',
                    DB::raw("isnull(i.reviewed_status_id,0) as status_id"),
                    'i.education_rating',
                    'i.experience_rating',
                    'i.training_rating',
                    'i.eligibility_rating',
                    'i.interview',
                    'i.bonus',
                    'i.is_education_passed',
                    'i.is_experience_passed',
                    'i.is_training_passed',
                    'i.is_eligibility_passed'
                )
                ->where('b.position_applied_id', $id)
                ->where('b.is_plantilla', false)
                ->where('b.application_status_id', 1)
                ->where('a.application_status_id', 1)
                ->orderBy('a.application_date', 'asc')
                ->get();
        }

        // Attach resume metadata from the separate attachments database
        $applicantIds = $applicants->pluck('id')->all();
        $attachments = collect();

        if (!empty($applicantIds)) {
            $attachments = DB::connection('attachments')
                ->table('applicant_attachments')
                ->select('id', 'applicant_id', 'attachment_name')
                ->whereIn('applicant_id', $applicantIds)
                ->orderBy('id', 'asc')
                ->get()
                ->groupBy('applicant_id');
        }

        foreach ($applicants as $applicant) {
            $group = $attachments->get($applicant->id);
            $first = $group ? $group->first() : null;

            // These fields keep the API shape expected by the frontend
            $applicant->attachment_id = $first->id ?? null;
            $applicant->attachment_name = $first->attachment_name ?? null;
            $applicant->attachment_path = null; // no filesystem path when using DB storage
        }

        $position = DB::table('positions')->where('active', 1)->orderBy('name', 'desc')->get();
        $step = DB::table('salary_steps')->where('active', 1)->orderBy('id', 'asc')->get();
        $grade = DB::table('salary_grades')->where('active', 1)->orderBy('id', 'asc')->get();
        $department = DB::table('departments')->where('active', 1)->orderBy('id', 'asc')->get();

        if ($type == 1) {
            // Plantilla
            $plantilla = DB::table('plantillas')
                ->join('positions', 'positions.id', '=', 'plantillas.position_id')
                ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
                ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
                ->leftJoin('departments', 'departments.id', '=', 'plantillas.department_id')
                ->select(
                    'plantillas.id',
                    'plantillas.employee_id',
                    'plantillas.code',
                    'positions.id as position_id',
                    'salary_steps.id as step_id',
                    'salary_grades.id as grade_id',
                    'departments.id as department_id',
                    'plantillas.eligibility as eligibility',
                    'plantillas.experience as experience',
                    'plantillas.training as training',
                    'plantillas.education as education',
                    'plantillas.unit as unit',
                    'plantillas.publication_from as publication_from',
                    'plantillas.publication_to as publication_to',
                    'plantillas.status as status',
                    'plantillas.active'
                )
                ->where('plantillas.id', $id)
                ->get();

            $educations = DB::table('plantilla_education')
                ->leftJoin('academic_level', 'academic_level.id', '=', 'plantilla_education.academic_level_id')
                ->where('plantilla_education.plantilla_id', $id)
                ->select(
                    'plantilla_education.*',
                    'academic_level.name as academic_level'
                )
                ->get();
            // Format work experience
            $employments_raw = DB::table('plantilla_work_experience')->where('plantilla_id', $id)->get();
            $employments = $employments_raw->map(function ($item) {
                return (object)[
                    'position' => $item->position ?? 'N/A',
                    'years' => $item->years ?? 'N/A'
                ];
            });

            // Format eligibility/examinations - join with eligibilities table
            $examinations = DB::table('plantilla_eligibility')
                ->leftJoin('eligibilities', 'eligibilities.id', '=', 'plantilla_eligibility.examination_id')
                ->where('plantilla_eligibility.plantilla_id', $id)
                ->select('eligibilities.name')
                ->get();

            // Format trainings - map to expected structure
            $trainings_raw = DB::table('plantilla_trainings')->where('plantilla_id', $id)->get();
            $trainings = $trainings_raw->map(function ($item) {
                return (object)[
                    'name' => $item->training ?? 'N/A',
                    'date' => 'N/A', // Not stored in plantilla_trainings
                    'hours' => $item->hours ?? 'N/A'
                ];
            });

            $eligibilities = DB::table('eligibilities')->where('active', true)->orderBy('name', 'asc')->get();

            $competency = DB::table('competencies as a')
                ->leftjoin('subcompetencies as b', 'a.id', '=', 'b.competency_id')
                ->leftjoin('plantilla_competencies as c', function ($join) use ($id) {
                    $join->on('b.id', '=', 'c.subcompetency_id');
                    $join->on('c.plantilla_id', '=', DB::raw("'" . $id . "'"));
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

            // Filter competencies to only include active ones (assign = 1)
            $competency = $competency->filter(function ($item) {
                return $item->assign == 1;
            });
        } else {
            // Non-Plantilla
            $plantilla = DB::table('non_plantillas')
                ->join('positions', 'positions.id', '=', 'non_plantillas.position_id')
                ->leftJoin('departments', 'departments.id', '=', 'non_plantillas.department_id')
                ->select(
                    'non_plantillas.id',
                    DB::raw('0 as employee_id'),
                    DB::raw('NULL as code'),
                    'positions.id as position_id',
                    DB::raw('NULL as step_id'),
                    DB::raw('NULL as grade_id'),
                    'departments.id as department_id',
                    'non_plantillas.eligibility as eligibility',
                    'non_plantillas.experience as experience',
                    'non_plantillas.training as training',
                    'non_plantillas.education as education',
                    DB::raw('NULL as unit'),
                    'non_plantillas.publication_from as publication_from',
                    'non_plantillas.publication_to as publication_to',
                    DB::raw('NULL as status'),
                    'non_plantillas.vacant as active',
                    'non_plantillas.qualification',
                    'non_plantillas.description'
                )
                ->where('non_plantillas.id', $id)
                ->get();

            $educations = collect([]);
            $employments = collect([]);
            $examinations = collect([]);
            $trainings = collect([]);
            $eligibilities = DB::table('eligibilities')->where('active', true)->orderBy('name', 'asc')->get();
            $competency = collect([]);
        }

        $grouped_arr = [];

        foreach ($competency as $element) {
            // Competency is already filtered to only include active ones (assign = 1)
            $elemName = $element->name;

            if (!isset($grouped_arr[$elemName])) {
                $grouped_arr[$elemName] = [];
            }

            array_push($grouped_arr[$elemName], $element);
        }

        // Return success response even if no applicants found, so position details can still be displayed
        return $this->successResponse([
            'applicants' => $applicants,
            'attachments' => $attachments,
            'plantilla' => $plantilla,
            'position' => $position,
            'step' => $step,
            'grade' => $grade,
            'department' => $department,
            'educations' => $educations,
            'employments' => $employments->values()->all(),
            'examinations' => $examinations->values()->all(),
            'trainings' => $trainings->values()->all(),
            'eligibilities' => $eligibilities,
            'competency' => $competency->values()->all(),
            'grouped_arr' => $grouped_arr
        ], $applicants->isEmpty() ? 'No applicants found for this position.' : 'Applicant hiring page data loaded successfully');
    }

    public function download($id)
    {
        try {
            // Reject invalid attachment ID to avoid DB errors and return clear 400
            if ($id === null || $id === '' || !is_numeric($id) || (int) $id <= 0) {
                return $this->errorResponse('Invalid attachment ID.', null, 400);
            }
            $id = (int) $id;

            // 1) Try to load from the attachments database (new BLOB / base64 storage)
            $attachment = DB::connection('attachments')
                ->table('applicant_attachments')
                ->select('id', 'applicant_id', 'attachment_name', 'file_content', 'file_type', 'file_size', 'path')
                ->where('id', $id)
                ->first();

            if ($attachment && !empty($attachment->file_content)) {
                // Decode base64 content; if decoding fails assume raw binary
                $binary = base64_decode($attachment->file_content, true);
                if ($binary === false) {
                    $binary = $attachment->file_content;
                }

                // Determine MIME type
                $mimeType = $attachment->file_type;
                if (!$mimeType) {
                    $extension = strtolower(pathinfo($attachment->attachment_name ?? '', PATHINFO_EXTENSION));
                    $mimeTypes = [
                        'pdf' => 'application/pdf',
                        'jpg' => 'image/jpeg',
                        'jpeg' => 'image/jpeg',
                        'png' => 'image/png',
                        'gif' => 'image/gif',
                        'bmp' => 'image/bmp',
                        'webp' => 'image/webp',
                        'doc' => 'application/msword',
                        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'xls' => 'application/vnd.ms-excel',
                        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ];
                    $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
                }

                $inline = request()->query('inline', false);
                $safeFilename = preg_replace('/[^\w\s\-\.]/', '_', $attachment->attachment_name ?? 'document');

                $disposition = ($inline ? 'inline' : 'attachment') . '; filename="' . addslashes($safeFilename) . '"';

                return response($binary, 200)
                    ->header('Content-Type', $mimeType)
                    ->header('Content-Disposition', $disposition)
                    ->header('Cache-Control', 'public, max-age=3600');
            }

            // 2) Fallback for legacy records that still rely on filesystem storage
            $legacyAttachment = DB::table('applicant_attachments as a')
                ->join('applicant_headers as b', 'a.applicant_id', '=', 'b.id')
                ->select(
                    'b.applicant_no',
                    'a.attachment_name',
                    'a.path'
                )
                ->where('a.id', $id)
                ->first();

            if (!$legacyAttachment) {
                return $this->errorResponse('Attachment not found.', null, 404);
            }

            // Resolve file path: prefer LOCAL storage first
            $resumeDir = storage_path('app' . DIRECTORY_SEPARATOR . 'resume');
            $baseName = $legacyAttachment->applicant_no . '_' . $legacyAttachment->attachment_name;
            $pathToFile = null;
            $candidates = [];

            $candidates[] = $resumeDir . DIRECTORY_SEPARATOR . $baseName;
            $candidates[] = storage_path('app/resume/' . $baseName);
            try {
                $candidates[] = Storage::disk('local')->path('resume' . DIRECTORY_SEPARATOR . $baseName);
            } catch (\Throwable $e) {
                // ignore
            }

            if (!empty($legacyAttachment->path)) {
                $candidates[] = $legacyAttachment->path;
                $candidates[] = str_replace(['/', '\\'], [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $legacyAttachment->path);
            }

            foreach (array_unique(array_filter($candidates)) as $candidate) {
                if ($candidate && file_exists($candidate) && is_file($candidate)) {
                    $pathToFile = $candidate;
                    break;
                }
            }

            if (!$pathToFile || !is_readable($pathToFile)) {
                if (!is_dir($resumeDir)) {
                    @mkdir($resumeDir, 0755, true);
                }
                $fullPath = $resumeDir . DIRECTORY_SEPARATOR . $baseName;
                return $this->errorResponse(
                    'File not found or not readable. Place the file at: ' . $fullPath . ' (or re-upload the attachment for this applicant.)',
                    null,
                    404
                );
            }

            $extension = strtolower(pathinfo($legacyAttachment->attachment_name, PATHINFO_EXTENSION));
            $mimeTypes = [
                'pdf' => 'application/pdf',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'bmp' => 'image/bmp',
                'webp' => 'image/webp'
            ];
            $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
            if (function_exists('mime_content_type')) {
                $detected = @mime_content_type($pathToFile);
                if ($detected && $detected !== 'application/octet-stream') {
                    $mimeType = $detected;
                }
            }

            $inline = request()->query('inline', false);
            $safeFilename = preg_replace('/[^\w\s\-\.]/', '_', $legacyAttachment->attachment_name);

            if ($inline) {
                return response()->file($pathToFile, [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => 'inline; filename="' . addslashes($safeFilename) . '"',
                    'Cache-Control' => 'public, max-age=3600'
                ]);
            }
            return response()->download($pathToFile, $safeFilename, [
                'Content-Type' => $mimeType
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download file: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function download_zip($id, $position)
    {
        try {
            $zip_file = $position . '.zip';
            $zip = new \ZipArchive();
            $zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            $files = DB::table('applicant_details as a')
                ->join('applicant_attachments as b', 'a.applicant_id', '=', 'b.applicant_id')
                ->join('applicant_headers as c', 'a.applicant_id', '=', 'c.id')
                ->select(
                    'c.applicant_no',
                    DB::raw("CONCAT(c.first_name,' ',c.last_name) as name"),
                    'b.path',
                    'b.attachment_name'
                )
                ->where('a.position_applied_id', $id)
                ->get();

            if ($files->isEmpty()) {
                return $this->errorResponse('No files found for this position.', null, 404);
            }

            foreach ($files as $file) {
                $folder = $file->applicant_no . '-' . $file->name . '/' . $file->applicant_no . '_' . $file->attachment_name;
                $filePath = $file->path;
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, $folder);
                }
            }

            $zip->close();

            if (!file_exists($zip_file)) {
                return $this->errorResponse('Failed to create zip file.', null, 500);
            }

            // Return zip file info in JSON instead of direct download
            return $this->successResponse([
                'zip_file' => $zip_file,
                'file_count' => $files->count(),
                'download_url' => url('/api/download-zip/' . $id . '/' . $position)
            ], 'Zip file created successfully');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create zip file: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function process($applicant_id, $position_applied_id, $is_plantilla, $type_id)
    {
        try {
            $employee_id = DB::table('employees')->max('id');
            $employee_id = $employee_id + 1;

            if ($type_id == 1) {

                // update application to hired
                DB::table('applicant_details')
                    ->where([
                        'applicant_id' => $applicant_id,
                        'position_applied_id' => $position_applied_id
                    ])->update(['application_status_id' => 5]);

                // update other application to not to proceed
                DB::table('applicant_details')
                    ->where(
                        'applicant_id',
                        '=',
                        $applicant_id
                    )
                    ->where(
                        'position_applied_id',
                        '<>',
                        $position_applied_id
                    )
                    ->update(['application_status_id' => 3]);

                // check if with plantilla
                if ($is_plantilla == 1) {

                    // insert employee records
                    $applicant_info = DB::table('applicant_headers')
                        ->where('id', $applicant_id)
                        ->get();

                    $plantilla_info = DB::table('plantillas')
                        ->where('id', $position_applied_id)
                        ->get();

                    // Employee Infor Insert
                    $employee_info = array(
                        // basic info
                        'id' => $employee_id,
                        'photo' => $applicant_info[0]->photo,
                        'employee_no' => $applicant_info[0]->applicant_no,
                        'access_no' => random_int(100000, 999999),
                        'email' => $applicant_info[0]->email,
                        'mobile_no' => $applicant_info[0]->mobile_no,
                        'telephone_no' => '',
                        'tin_no' => '',
                        'gsis_no' => '',
                        'sss_no' => '',
                        'pagibig_no' => '',
                        'philhealth_no' => '',
                        'name_prefix_id' => 0,
                        'first_name' => $applicant_info[0]->first_name,
                        'middle_name' => $applicant_info[0]->middle_name,
                        'last_name' => $applicant_info[0]->last_name,
                        'name_suffix_id' => 0,
                        'birth_place' => '',
                        'birthdate' => $applicant_info[0]->birth_date,
                        'age' => $applicant_info[0]->age,
                        'height' => 0,
                        'weight' => 0,
                        'gender_id' => $applicant_info[0]->gender,
                        'civil_status_id' => 0,
                        'citizenship_id' => 0,
                        'religion_id' => 0,
                        'blood_type_id' => 0,
                        // address info
                        'ra_region' => '',
                        'ra_province' => '',
                        'ra_city' => '',
                        'ra_barangay' => '',
                        'ra_house_no' => '',
                        'ra_street' => '',
                        'ra_village' => '',
                        'pa_region' => '',
                        'pa_province' => '',
                        'pa_city' => '',
                        'pa_barangay' => '',
                        'pa_house_no' => '',
                        'pa_street' => '',
                        'pa_village' => '',
                        // family info
                        'father_name_prefix_id' => 0,
                        'father_first_name' => '',
                        'father_middle_name' => '',
                        'father_last_name' => '',
                        'father_name_suffix_id' => 0,
                        'mother_name_prefix_id' => 0,
                        'mother_first_name' => '',
                        'mother_middle_name' => '',
                        'mother_last_name' => '',
                        'mother_name_suffix_id' => 0,
                        'spouse_name_prefix_id' => 0,
                        'spouse_first_name' => '',
                        'spouse_middle_name' => '',
                        'spouse_last_name' => '',
                        'spouse_name_suffix_id' => 0,
                        'spouse_occupation' => '',
                        'spouse_employer' => '',
                        'spouse_business_address' => '',

                        // dual citizenship info
                        'is_dual_citizent' => false,
                        'by_birth' => false,
                        'by_naturalization' => false,
                        'indicate_country' => '',
                        // work info
                        'position_id' => $plantilla_info[0]->position_id,
                        'position_applied_id' => $plantilla_info[0]->position_id,
                        'plantilla_id' => $position_applied_id,
                        'salary_grade_id' => $plantilla_info[0]->salary_grade_id,
                        'salary_step_id' => $plantilla_info[0]->salary_step_id,
                        'date_applied' => $applicant_info[0]->application_date,
                        'company_id' => 1,
                        'branch_id' => 1,
                        'department_id' => $plantilla_info[0]->department_id,
                        // Plantilla hires default to employment type "Plantilla" (employment_types.id = 1)
                        'employment_type_id' => $plantilla_info[0]->employment_type_id,
                        'date_hired' => now(),
                        'application_status_id' => 5,
                        'is_plantilla' => true,
                        'is_teaching' => false,

                        // default data
                        'active' => true,
                        'is_employee' => true,
                    );

                    DB::unprepared('SET IDENTITY_INSERT employees ON');
                    DB::table('employees')->insert($employee_info);
                    DB::unprepared('SET IDENTITY_INSERT employees OFF');
                    // update plantilla table
                    DB::table('plantillas')->where('id', $position_applied_id)->update(['employee_id' => $employee_id, 'status' => 'Occupied']);
                } else {

                    // insert employee records
                    $applicant_info = DB::table('applicant_headers')
                        ->where('id', $applicant_id)
                        ->get();

                    $non_plantilla_info = DB::table('non_plantillas')
                        ->where('id', $position_applied_id)
                        ->get();

                    // Employee Infor Insert
                    $employee_info = array(
                        // basic info
                        'id' => $employee_id,
                        'photo' => $applicant_info[0]->photo,
                        'employee_no' => $applicant_info[0]->applicant_no,
                        'access_no' => random_int(100000, 999999),
                        'email' => $applicant_info[0]->email,
                        'mobile_no' => $applicant_info[0]->mobile_no,
                        'telephone_no' => '',
                        'tin_no' => '',
                        'gsis_no' => '',
                        'sss_no' => '',
                        'pagibig_no' => '',
                        'philhealth_no' => '',
                        'name_prefix_id' => 0,
                        'first_name' => $applicant_info[0]->first_name,
                        'middle_name' => $applicant_info[0]->middle_name,
                        'last_name' => $applicant_info[0]->last_name,
                        'name_suffix_id' => 0,
                        'birth_place' => '',
                        'birthdate' => $applicant_info[0]->birth_date,
                        'age' => $applicant_info[0]->age,
                        'height' => 0,
                        'weight' => 0,
                        'gender_id' => $applicant_info[0]->gender,
                        'civil_status_id' => 0,
                        'citizenship_id' => 0,
                        'religion_id' => 0,
                        'blood_type_id' => 0,
                        // address info
                        'ra_region' => '',
                        'ra_province' => '',
                        'ra_city' => '',
                        'ra_barangay' => '',
                        'ra_house_no' => '',
                        'ra_street' => '',
                        'ra_village' => '',
                        'pa_region' => '',
                        'pa_province' => '',
                        'pa_city' => '',
                        'pa_barangay' => '',
                        'pa_house_no' => '',
                        'pa_street' => '',
                        'pa_village' => '',
                        // family info
                        'father_name_prefix_id' => 0,
                        'father_first_name' => '',
                        'father_middle_name' => '',
                        'father_last_name' => '',
                        'father_name_suffix_id' => 0,
                        'mother_name_prefix_id' => 0,
                        'mother_first_name' => '',
                        'mother_middle_name' => '',
                        'mother_last_name' => '',
                        'mother_name_suffix_id' => 0,
                        'spouse_name_prefix_id' => 0,
                        'spouse_first_name' => '',
                        'spouse_middle_name' => '',
                        'spouse_last_name' => '',
                        'spouse_name_suffix_id' => 0,
                        'spouse_occupation' => '',
                        'spouse_employer' => '',
                        'spouse_business_address' => '',

                        // dual citizenship info
                        'is_dual_citizent' => false,
                        'by_birth' => false,
                        'by_naturalization' => false,
                        'indicate_country' => '',
                        // work info
                        'position_id' => $non_plantilla_info[0]->position_id,
                        'position_applied_id' => $non_plantilla_info[0]->position_id,
                        'plantilla_id' => 0,
                        'salary_grade_id' => 0,
                        'salary_step_id' => 0,
                        'date_applied' => $applicant_info[0]->application_date,
                        'company_id' => 1,
                        'branch_id' => 1,
                        'department_id' => $non_plantilla_info[0]->department_id,
                        // Non-plantilla hires use the configured employee_type_id from non_plantillas
                        'employment_type_id' => $non_plantilla_info[0]->employee_type_id ?? 0,
                        'date_hired' => now(),
                        'application_status_id' => 5,
                        'is_plantilla' => false,
                        'is_teaching' => false,

                        // default data
                        'active' => true,
                        'is_employee' => true,
                    );

                    DB::unprepared('SET IDENTITY_INSERT employees ON');
                    DB::table('employees')->insert($employee_info);
                    DB::unprepared('SET IDENTITY_INSERT employees OFF');

                    // DB::table('employees')->insert($employee_info);

                    // update non-plantilla table
                    $query = DB::table('non_plantillas')->where('id', $position_applied_id);
                    $query->decrement('vacant');
                }

                $user_id = DB::table('applicant_headers')->where('id', $applicant_id)->get();

                DB::table('users')->where('id', $user_id[0]->user_id)->update(['is_applicant' => 0, 'employee_no' => $applicant_info[0]->applicant_no]);

                // get applicant data to email
                if ($is_plantilla == 1) {
                    $applicant = DB::table('applicant_headers as a')
                        ->join('applicant_details as b', 'a.id', '=', 'b.applicant_id')
                        ->join('plantillas as c', 'b.position_applied_id', '=', 'c.id')
                        ->join('positions as d', 'c.position_id', '=', 'd.id')
                        ->select(
                            DB::raw("CONCAT(a.first_name,' ',a.last_name) as name"),
                            'a.email',
                            'd.name as position'
                        )
                        ->where([
                            'a.id' => $applicant_id,
                            'b.position_applied_id' => $position_applied_id
                        ])
                        ->get();
                } else {
                    $applicant = DB::table('applicant_headers as a')
                        ->join('applicant_details as b', 'a.id', '=', 'b.applicant_id')
                        ->join('non_plantillas as c', 'b.position_applied_id', '=', 'c.id')
                        ->join('positions as d', 'c.position_id', '=', 'd.id')
                        ->select(
                            DB::raw("CONCAT(a.first_name,' ',a.last_name) as name"),
                            'a.email',
                            'd.name as position'
                        )
                        ->where([
                            'a.id' => $applicant_id,
                            'b.position_applied_id' => $position_applied_id
                        ])
                        ->get();
                }

                // // send email verification here
                Notification::send($applicant, new EmailApplicantHiring($applicant));

                return $this->successResponse([
                    'applicant_id' => $applicant_id,
                    'employee_id' => $employee_id,
                    'position_applied_id' => $position_applied_id,
                    'is_plantilla' => $is_plantilla,
                    'action' => 'hired'
                ], 'Applicant hired successfully');
            } else {
                // Tag Applicant as not Qualify
                DB::table('applicant_details')->where([
                    'applicant_id' => $applicant_id,
                    'position_applied_id' => $position_applied_id
                ])->update(['application_status_id' => 2]);

                return $this->successResponse([
                    'applicant_id' => $applicant_id,
                    'position_applied_id' => $position_applied_id,
                    'action' => 'not_qualified'
                ], 'Applicant marked as not qualified');
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process applicant: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function Info($id)
    {

        $app_key = env("APP_KEY", "");

        $applicant = DB::table('applicant_headers as a')
            ->leftJoin('genders as b', 'a.gender', '=', 'b.id')
            ->select('a.*', 'b.name as gender')
            ->where('a.id', $id)
            ->first();

        if (!$applicant) {
            return response()->json([
                'success' => false,
                'message' => 'Applicant not found',
            ], 404);
        }

        // PDS stub employee: match applicant_no (ideal), else same email (ApplicantsController often saves random "APP - …" employee_no).
        $emp_id = 0;
        $employeeRow = DB::table('employees')->where('employee_no', $applicant->applicant_no)->first();
        if ($employeeRow) {
            $emp_id = (int) $employeeRow->id;
        }
        if ($emp_id === 0 && !empty(trim((string) ($applicant->email ?? '')))) {
            $byEmail = DB::table('employees')
                ->whereRaw('LTRIM(RTRIM(LOWER(CAST(email AS NVARCHAR(500))))) = ?', [strtolower(trim($applicant->email))])
                ->orderBy('id')
                ->first();
            if ($byEmail) {
                $emp_id = (int) $byEmail->id;
            }
        }

        if ($emp_id == 0) {
            $dummy_employee_info =
                array(
                    'id' => 0,
                    'photo' => '',
                    'employee_no' => '',
                    'access_no' => '',
                    'name_prefix_id' => 0,
                    'first_name' => '',
                    'middle_name' => '',
                    'last_name' => '',
                    'name_suffix_id' => 0,
                    'birth_place' => '',
                    'birthdate' => '',
                    'age' => '',
                    'gender_id' => 0,
                    'height' => '',
                    'weight' => '',
                    'blood_type_id' => 0,
                    'email' => '',
                    'mobile_no' => '',
                    'telephone_no' => '',
                    'citizenship_id' => 0,
                    'civil_status_id' => 0,
                    'religion_id' => 0,
                    'is_dual_citizent' => false,
                    'by_birth' => false,
                    'by_naturalization' => false,
                    'indicate_country' => '',
                    'ra_region' => '',
                    'ra_province' => '',
                    'ra_city' => '',
                    'ra_house_no' => '',
                    'ra_barangay' => '',
                    'ra_street' => '',
                    'ra_village' => '',
                    'pa_region' => '',
                    'pa_province' => '',
                    'pa_city' => '',
                    'pa_house_no' => '',
                    'pa_barangay' => '',
                    'pa_street' => '',
                    'pa_village' => '',
                    'father_name_prefix_id' => 0,
                    'father_first_name' => '',
                    'father_middle_name' => '',
                    'father_last_name' => '',
                    'father_name_suffix_id' => 0,
                    'mother_name_prefix_id' => '',
                    'mother_first_name' => '',
                    'mother_middle_name' => '',
                    'mother_last_name' => '',
                    'mother_name_suffix_id' => 0,
                    'spouse_name_prefix_id' => 0,
                    'spouse_first_name' => '',
                    'spouse_middle_name' => '',
                    'spouse_last_name' => '',
                    'spouse_name_suffix_id' => 0,
                    'spouse_occupation' => '',
                    'spouse_employer' => '',
                    'spouse_business_address' => '',
                    'company_id' => 1,
                    'branch_id' => 0,
                    'department_id' => 0,
                    'division_id' => 0,
                    'section_id' => 0,
                    'employment_type_id' => 0,
                    'position_id' => 0,
                    'plantilla_id' => 0,
                    'is_plantilla' => false,
                    'is_employee' => true,
                    'is_teaching' => false,
                    'date_hired' => '',
                    'tin_no' => '',
                    'gsis_no' => '',
                    'sss_no' => '',
                    'pagibig_no' => '',
                    'philhealth_no' => '',
                    'salary' => '',
                    'tax_amount' => '',
                    'gsis_amount' => '',
                    'sss_amount' => '',
                    'pagibig_amount' => '',
                    'philhealth_amount' => '',
                    'payroll_interval_id' => 0,
                    'end_date' => '',
                    'account_no' => ''
                );

            $plantillas_selected = DB::table('plantillas')
                ->select('salary_grade_id', 'salary_step_id')
                ->where('id', 0)->get();

            $employee_info = (object) $dummy_employee_info;
            $employee_info = collect([$employee_info]);
        } else {

            $employee_info = DB::table('employees')
                ->select(
                    'id',
                    'photo',
                    'employee_no',
                    'access_no',
                    'name_prefix_id',
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN first_name ELSE dbo.ufn_DecryptString(first_name,'$app_key') END as first_name"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN middle_name ELSE dbo.ufn_DecryptString(middle_name,'$app_key') END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN last_name ELSE dbo.ufn_DecryptString(last_name,'$app_key') END as last_name"),
                    'name_suffix_id',
                    'birth_place',
                    'birthdate',
                    'age',
                    'gender_id',
                    'height',
                    'weight',
                    'blood_type_id',
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN email ELSE dbo.ufn_DecryptString(email,'$app_key') END as email"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mobile_no ELSE dbo.ufn_DecryptString(mobile_no,'$app_key') END as mobile_no"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN telephone_no ELSE dbo.ufn_DecryptString(telephone_no,'$app_key') END as telephone_no"),
                    'citizenship_id',
                    'civil_status_id',
                    'religion_id',
                    'is_dual_citizent',
                    'by_birth',
                    'by_naturalization',
                    'indicate_country',
                    'ra_postal_id',
                    'ra_region',
                    'ra_province',
                    'ra_city',
                    'ra_house_no',
                    'ra_barangay',
                    'ra_street',
                    'ra_village',
                    'pa_postal_id',
                    'pa_region',
                    'pa_province',
                    'pa_city',
                    'pa_house_no',
                    'pa_barangay',
                    'pa_street',
                    'pa_village',
                    'father_name_prefix_id',
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN father_first_name ELSE dbo.ufn_DecryptString(father_first_name,'$app_key') END as father_first_name"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN father_middle_name ELSE dbo.ufn_DecryptString(father_middle_name,'$app_key') END as father_middle_name"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN father_last_name ELSE dbo.ufn_DecryptString(father_last_name,'$app_key') END as father_last_name"),
                    'father_name_suffix_id',
                    'mother_name_prefix_id',
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mother_first_name ELSE dbo.ufn_DecryptString(mother_first_name,'$app_key') END as mother_first_name"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mother_middle_name ELSE dbo.ufn_DecryptString(mother_middle_name,'$app_key') END as mother_middle_name"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN mother_last_name ELSE dbo.ufn_DecryptString(mother_last_name,'$app_key') END as mother_last_name"),
                    'mother_name_suffix_id',
                    'spouse_name_prefix_id',
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_first_name ELSE dbo.ufn_DecryptString(spouse_first_name,'$app_key') END as spouse_first_name"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_middle_name ELSE dbo.ufn_DecryptString(spouse_middle_name,'$app_key') END as spouse_middle_name"),
                    DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_last_name ELSE dbo.ufn_DecryptString(spouse_last_name,'$app_key') END as spouse_last_name"),
                    'spouse_name_suffix_id',
                    // DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_occupation ELSE dbo.ufn_DecryptString(spouse_occupation,'$app_key') END as spouse_occupation"),
                    // DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_employer ELSE dbo.ufn_DecryptString(spouse_employer,'$app_key') END as spouse_employer"),
                    // DB::raw("CASE WHEN ISNULL(is_encrypted,0) = 0 THEN spouse_business_address ELSE dbo.ufn_DecryptString(spouse_business_address,'$app_key') END as spouse_business_address"),
                    'spouse_occupation',
                    'spouse_employer',
                    'spouse_business_address',
                    'spouse_mobile_no',
                    'company_id',
                    'branch_id',
                    'department_id',
                    'division_id',
                    'section_id',
                    'employment_type_id',
                    'position_id',
                    'plantilla_id',
                    'is_plantilla',
                    'is_employee',
                    'is_teaching',
                    'date_hired',
                    'tin_no',
                    'gsis_no',
                    'sss_no',
                    'pagibig_no',
                    'philhealth_no',
                    'salary',
                    'tax_amount',
                    'gsis_amount',
                    'sss_amount',
                    'pagibig_amount',
                    'philhealth_amount',
                    'payroll_interval_id',
                    'end_date',
                    'account_no'
                )
                ->where('id', $emp_id)
                ->orderBy('employees.first_name', 'asc')
                ->get();

            $plantillas_selected = DB::table('plantillas')
                ->select('salary_grade_id', 'salary_step_id')
                ->where('id', $employee_info[0]->plantilla_id)->get();
        }

        $data = DB::table('plantillas')
            ->join('positions', 'positions.id', '=', 'plantillas.position_id')
            ->join('salary_steps', 'salary_steps.id', '=', 'plantillas.salary_step_id')
            ->join('salary_grades', 'salary_grades.id', '=', 'plantillas.salary_grade_id')
            ->join('departments', 'departments.id', '=', 'plantillas.department_id')
            ->join('applicant_details as a', 'a.position_applied_id', '=', 'plantillas.id')
            ->join('applicant_headers as b', 'a.applicant_id', '=', 'b.id')
            ->join('application_status as f', 'a.application_status_id', '=', 'f.id')
            ->select('plantillas.id', 'plantillas.code', 'positions.name as position', 'salary_steps.name as step', 'salary_grades.name as grade', 'departments.name as department', 'plantillas.eligibility as eligibility', 'plantillas.experience as experience', 'plantillas.training as training', 'plantillas.education as education', 'plantillas.unit as unit', 'plantillas.publication_from as publication_from', 'plantillas.publication_to as publication_to', 'plantillas.status as status', 'plantillas.active', 'f.name as application_status')
            ->where('a.is_plantilla', true)
            ->where('b.id', $applicant->id)
            ->orderBy('positions.name', 'asc')
            ->get();

        $non_plantillas = DB::table('non_plantillas as a')
            ->join('positions as b', 'a.position_id', '=', 'b.id')
            ->join('departments as c', 'a.department_id', '=', 'c.id')
            ->join('applicant_details as d', 'd.position_applied_id', '=', 'a.id')
            ->join('applicant_headers as e', 'd.applicant_id', '=', 'e.id')
            ->join('application_status as f', 'd.application_status_id', '=', 'f.id')
            ->select(
                'a.id',
                'a.position_id',
                'b.name as position',
                'a.salary',
                'c.name as department',
                'a.eligibility',
                'a.experience',
                'a.education',
                'a.training',
                'a.description',
                'a.qualification',
                'a.vacant',
                'a.publication_from',
                'a.publication_to',
                'f.name as application_status',
                DB::raw(
                    "case when a.status = 0 then 'Inactive' else 'Active' end as status"
                )
            )
            ->where('d.is_plantilla', false)
            ->where('e.id', $applicant->id)
            ->orderBy('b.name', 'asc')
            ->get();

        $prefixes = DB::table('name_prefixes')->where('active', true)->orderBy('id', 'asc')->get();
        $suffixes = DB::table('name_suffixes')->where('active', true)->orderBy('name', 'asc')->get();
        $genders = DB::table('genders')->where('active', true)->orderBy('name', 'asc')->get();
        $civil_status = DB::table('civil_status')->where('active', true)->orderBy('id', 'asc')->get();
        $citizenships = DB::table('citizenships')->where('active', true)->orderBy('id', 'asc')->get();
        $religions = DB::table('religions')->where('active', true)->orderBy('id', 'asc')->get();
        $blood_types = DB::table('blood_types')->where('active', true)->orderBy('name', 'asc')->get();
        $companies = DB::table('companies')->get();
        $branches = DB::table('branches')->orderBy('id', 'asc')->get();
        $departments = DB::table('departments')->where('active', true)->orderBy('name', 'asc')->get();
        $employment_types = DB::table('employment_types')->where('active', true)->orderBy('id', 'asc')->get();
        $positions = DB::table('positions')->where('active', true)->orderBy('name', 'asc')->get();

        // Get Plantilla
        $plantilla_emp = DB::table('plantillas')->where(['employee_id' => $emp_id, 'active' => true]);
        $plantillas = DB::table('plantillas')->where(['employee_id' => 0, 'active' => true])->union($plantilla_emp)->get();

        $salary_grades = DB::table('salary_grades')->where('active', true)->orderBy('id', 'asc')->get();
        $salary_steps = DB::table('salary_steps')->where('active', true)->orderBy('id', 'asc')->get();
        $blood_types = DB::table('blood_types')->where('active', true)->orderBy('id', 'asc')->get();
        $payroll_intervals = DB::table('payroll_intervals')->where('active', true)->orderBy('id', 'asc')->get();
        $eligibilities = DB::table('eligibilities')->where('active', true)->orderBy('name', 'asc')->get();
        $learnings = DB::table('learnings')->where('active', true)->orderBy('name', 'asc')->get();
        $divisions = DB::table('divisions')->where('active', true)->orderBy('name', 'asc')->get();
        $sections = DB::table('sections')->where('active', true)->orderBy('name', 'asc')->get();

        $applicant_id = $applicant->id;

        $children = DB::table('employee_children')->where('employee_id', $emp_id)->get();
        $educations = DB::table('employee_educations')->where('employee_id', $emp_id)->orderBy('employee_educations.graduated_year', 'desc')->get();
        $service_records = DB::table('service_records')->where('employee_id', $emp_id)->get();
        // Work history: employee_employment_records (by stub employee_id) + Work_Experience (applicant PDS uses Reference_id = applicant_no)
        $employmentMerged = collect();
        if ($emp_id > 0) {
            foreach (DB::table('employee_employment_records')->where('employee_id', $emp_id)->orderByDesc('work_start_date')->get() as $row) {
                $employmentMerged->push((object) [
                    'position' => $row->position ?? '',
                    'work_company' => $row->work_company ?? '',
                    'work_start_date' => $row->work_start_date ?? '',
                    'work_end_date' => $row->work_end_date ?? '',
                    'duration' => data_get($row, 'duration'),
                ]);
            }
        }
        foreach (DB::table('Work_Experience')
            ->where('Reference_id', $applicant->applicant_no)
            ->orderByDesc('Work_start_date')
            ->get() as $we) {
            $employmentMerged->push((object) [
                'position' => $we->Position ?? '',
                'work_company' => $we->Office_name ?? '',
                'work_start_date' => $we->Work_start_date ?? '',
                'work_end_date' => $we->Work_end_date ?? '',
                'duration' => $we->Duration ?? null,
            ]);
        }
        $employments = $employmentMerged->unique(function ($item) {
            return ($item->position ?? '') . '|' . ($item->work_company ?? '') . '|' . (string) ($item->work_start_date ?? '');
        })->values();

        // Examinations / Eligibility: enrich with eligibilities table name so UI can display eligibility type
        $examinations = DB::table('employee_examinations as ex')
            ->leftJoin('eligibilities as el', 'el.id', '=', 'ex.eligibility_id')
            ->select(
                'ex.*',
                // Standardize the display field used by frontend tables (EmployeeDetails uses this too)
                DB::raw("ISNULL(ex.eligibility_description, el.name) as eligibility_description"),
                'el.name as eligibility_name'
            )
            ->where('ex.employee_id', $emp_id)
            ->get();
        $trainings = DB::table('employee_trainings as t')
            ->leftJoin('learnings as l', 'l.id', '=', 't.learning_id')
            ->select('t.*', 'l.name as learning')
            ->where('t.employee_id', $emp_id)
            ->orderByDesc('t.training_from')
            ->get();
        $organizations = DB::table('employee_organizations')->where('employee_id', $emp_id)->get();
        $recognitions = DB::table('employee_recognations')->where('employee_id', $emp_id)->get();
        $skills = DB::table('employee_skills')->where('employee_id', $emp_id)->get();
        $memberships = DB::table('employee_memberships')->where('employee_id', $emp_id)->get();
        $references = DB::table('employee_references')->where('employee_id', $emp_id)->get();
        $dependents = DB::table('employee_dependents')->where('employee_id', $emp_id)->get();
        $documents = DB::connection('attachments')
            ->table('employee_documents')
            ->where('employee_id', $emp_id)
            ->get();

        // Debug: log documents fetched for applicant Info modal
        \Log::info('ApplicantHiringController@Info documents debug', [
            'applicant_id' => $applicant_id,
            'employee_id' => $emp_id,
            'documents_count' => $documents->count(),
            'document_ids' => $documents->pluck('employee_document_id'),
        ]);

        $loans = DB::table('loan_applications as a')
            ->join('deductions as b', 'a.deduction_id', '=', 'b.id')
            ->select(
                'b.name',
                'a.loan_amount',
                'a.payment',
                'a.balance'
            )
            ->where([
                'a.is_approve' => true,
                'a.employee_id' => $emp_id
            ])
            ->get();

        $payroll_period_id = DB::table('payroll_incomes')->max('payroll_period_id');

        $incomes = DB::table('payroll_incomes as a')
            ->join('incomes as b', 'a.income_id', '=', 'b.id')
            ->select(
                'b.name',
                'a.amount'
            )
            ->where([
                'a.employee_id' => $emp_id,
                'a.payroll_period_id' => $payroll_period_id
            ])
            ->get();

        $salary_grade_steps = DB::table('salary_grades as a')
            ->crossJoin('salary_steps as b')
            ->select(
                DB::raw("CONVERT(nvarchar(50),a.id) + ' - ' + CONVERT(nvarchar(50),b.id) as id"),
                DB::raw("CONVERT(nvarchar(50),a.id) + ' - ' + CONVERT(nvarchar(50),b.id) as name")
            )
            ->get();

        $quesionaires = DB::table('employee_pds_answers as a')
            ->join('pds_questionaires as b', 'a.question_id', '=', 'b.id')
            ->select(
                'b.id',
                'b.code',
                'b.questions',
                'a.is_yes',
                'a.is_no',
                'a.yes_details',
                'a.case_status',
                'a.date_filed'
            )
            ->where('a.employee_id', $emp_id)
            ->orderBy('b.id', 'asc')
            ->get();

        if ($quesionaires->isEmpty()) {
            $quesionaires = DB::table('pds_questionaires')->orderBy('id', 'asc')->get();
        }

        $eete_ratings = DB::table('eete_ratings')->get();

        if ($eete_ratings->isEmpty()) {
            $eete_ratings = [
                'id' => 0,
                'education_rating' => null,
                'experience_rating' => null,
                'training_rating' => null,
                'eligibility_rating' => null
            ];

            $eete_ratings = (object) $eete_ratings;
            $eete_ratings = collect([$eete_ratings]);
        }

        $applicant_eete_ratings = DB::table('applicant_eete_ratings')->where('applicant_id', $applicant_id)->get();

        if ($applicant_eete_ratings->isEmpty()) {
            $applicant_eete_ratings = [
                'id' => 0,
                'applicant_id' => $applicant_id,
                'education_rating' => null,
                'experience_rating' => null,
                'training_rating' => null,
                'eligibility_rating' => null,
                'reviewed_status_id' => null,
                'is_education_passed' => null,
                'is_experience_passed' => null,
                'is_training_passed' => null,
                'is_eligibility_passed' => null,
                'interview' => null,
                'bonus' => null,
            ];

            $applicant_eete_ratings = (object) $applicant_eete_ratings;
            $applicant_eete_ratings = collect([$applicant_eete_ratings]);
        }

        return $this->successResponse([
            'applicant' => $applicant,
            'data' => $data,
            'non_plantillas' => $non_plantillas,
            'prefixes' => $prefixes,
            'suffixes' => $suffixes,
            'genders' => $genders,
            'civil_status' => $civil_status,
            'citizenships' => $citizenships,
            'religions' => $religions,
            'blood_types' => $blood_types,
            'companies' => $companies,
            'branches' => $branches,
            'departments' => $departments,
            'employment_types' => $employment_types,
            'positions' => $positions,
            'plantillas' => $plantillas,
            'salary_grades' => $salary_grades,
            'salary_steps' => $salary_steps,
            'payroll_intervals' => $payroll_intervals,
            'eligibilities' => $eligibilities,
            'learnings' => $learnings,
            'employee_info' => $employee_info,
            'plantillas_selected' => $plantillas_selected,
            'children' => $children,
            'educations' => $educations,
            'service_records' => $service_records,
            'employments' => $employments,
            'examinations' => $examinations,
            'trainings' => $trainings,
            'organizations' => $organizations,
            'recognitions' => $recognitions,
            'skills' => $skills,
            'memberships' => $memberships,
            'references' => $references,
            'loans' => $loans,
            'incomes' => $incomes,
            'divisions' => $divisions,
            'sections' => $sections,
            'dependents' => $dependents,
            'documents' => $documents,
            'salary_grade_steps' => $salary_grade_steps,
            'quesionaires' => $quesionaires,
            'eete_ratings' => $eete_ratings,
            'applicant_eete_ratings' => $applicant_eete_ratings
        ], 'Applicant hiring applicant info data loaded successfully');
    }

    public function rating(Request $request, $id)
    {
        $app_key = config('app.key'); // Get the app key for decryption
        $data = [
            'applicant_id' => $request->applicant_id,
            'education_rating' => $request->education_rating,
            'experience_rating' => $request->experience_rating,
            'training_rating' => $request->training_rating,
            'eligibility_rating' => $request->eligibility_rating,
            'reviewed_by' => Auth::user()->id,
            'reviewed_date' => now(),
            'reviewed_status_id' => $request->reviewed_status_id,
            'is_education_passed' => $request->is_education_passed == 1 ? true : false,
            'is_experience_passed' => $request->is_experience_passed == 1 ? true : false,
            'is_training_passed' => $request->is_training_passed == 1 ? true : false,
            'is_eligibility_passed' => $request->is_eligibility_passed == 1 ? true : false,
            'interview' => $request->input('interview', null),
            'bonus' => $request->input('bonus', null),
        ];

        // ✅ Update or insert the applicant's ratings
        DB::table('applicant_eete_ratings')->updateOrInsert(
            ['applicant_id' => $request->applicant_id],
            $data
        );

        // ✅ Get the applicant info from applicant_headers using applicant_id
        $applicant = DB::table('applicant_headers')
            ->where('id', $request->applicant_id)
            ->first();

        // ✅ Get applicant details for position_applied_id
        $applicantDetails = DB::table('applicant_details')
            ->where('applicant_id', $request->applicant_id)
            ->first();

        // ✅ Get the plantilla info for the applied position
        $plantillaInfo = DB::table('plantillas')
            ->where('id', $applicantDetails->position_applied_id ?? null)
            ->first();

        // ✅ Get position info for the applicant
        $positionInfo = DB::table('positions')
            ->where('id', $plantillaInfo->position_id ?? null)
            ->first();

        // ✅ Get department info for the applicant
        $departmentInfo = DB::table('departments')
            ->where('id', $plantillaInfo->department_id ?? null)
            ->first();

        // ✅ Get the employee with position_id = 181 and decrypt the name
        $employee = DB::table('employees as a')
            ->join('positions as p', 'a.position_id', '=', 'p.id')
            ->select([
                DB::raw("
                    CASE
                        WHEN ISNULL(a.is_encrypted, 0) = 0 THEN a.last_name
                        ELSE dbo.ufn_DecryptString(a.last_name, '$app_key')
                    END as last_name
                "),
                DB::raw("
                    CASE
                        WHEN ISNULL(a.is_encrypted, 0) = 0 THEN a.first_name
                        ELSE dbo.ufn_DecryptString(a.first_name, '$app_key')
                    END as first_name
                "),
                DB::raw("
                    CASE
                        WHEN ISNULL(a.is_encrypted, 0) = 0 THEN a.middle_name
                        ELSE dbo.ufn_DecryptString(a.middle_name, '$app_key')
                    END as middle_name
                "),
                'a.position_id',
                'a.pa_region_name',
                'a.pa_province_name',
                'a.pa_city_name',
            ])
            ->where('p.name', 'MUNICIPAL MAYOR')  // Match by position name
            ->first();

        // ✅ Get position name for the employee
        $employeePosition = DB::table('positions')
            ->where('id', $employee->position_id ?? null)
            ->first();

        // ✅ Prepare email data with decrypted employee info
        $emailData = [
            'applicant_name' => trim($applicant->first_name . ' ' . $applicant->middle_name . ' ' . $applicant->last_name),
            'position_name' => $positionInfo->name ?? 'Unknown Position',
            'department_name' => $departmentInfo->name ?? 'Unknown Department',
            'employee_name' => trim(($employee->first_name ?? '') . ' ' . ($employee->middle_name ?? '') . ' ' . ($employee->last_name ?? '')),
            'employee_position' => $employeePosition->name ?? 'Unknown Position',
            'pa_region_name' => $employee->pa_region_name ?? 'Unknown Region',
            'pa_province_name' => $employee->pa_province_name ?? 'Unknown Province',
            'pa_city_name' => $employee->pa_city_name ?? 'Unknown City',
            'date_sent' => now()->format('Y-m-d H:i:s'),
            'reviewed_status_id' => $request->reviewed_status_id,
        ];

        // ✅ Send email ONLY when explicitly requested and status is Qualified (2)
        if (
            $applicant &&
            !empty($applicant->email) &&
            $request->reviewed_status_id == 2 &&
            ($request->boolean('send_email', false) === true)
        ) {
            // Send notification to the applicant with updated email data
            Notification::route('mail', $applicant->email)
                ->notify(new EmailApplicantStatus($emailData));
        }

        // ✅ Return success message
        return $this->successResponse([
            'applicant_id' => $request->applicant_id,
            'reviewed_status_id' => $request->reviewed_status_id,
            'email_sent' => !empty($applicant->email) && $request->reviewed_status_id == 2
        ], 'Rating saved successfully and email sent.');
    }
}
