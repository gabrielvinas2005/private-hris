<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Helpers\CompanyHelper;
use Auth;
use Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Notifications\EmailApplicantStatus;
use App\Notifications\EmailApplicantShortlisted;
use App\Traits\ApiResponse;

class ApplicantShortlistingController extends Controller
{
    use ApiResponse;

    /**
     * Recruitment: Proceed applicant to next step for a specific applied position.
     * This updates applicant_details.application_status_id (per position) and also syncs applicant_headers.application_status_id.
     *
     * NOTE: The system's existing "Proceed" status is commonly application_status_id = 4.
     */
    public function proceedNextStep(Request $request, $applicant_id, $position_applied_id = null)
    {
        try {
            $applicantId = (int) $applicant_id;
            $positionAppliedId = $position_applied_id !== null ? (int) $position_applied_id : null;

            $nextStepStatusId = 4; // "Proceed to next step"

            $detailsQuery = DB::table('applicant_details')->where('applicant_id', $applicantId);
            if ($positionAppliedId !== null && $positionAppliedId > 0) {
                $detailsQuery->where('position_applied_id', $positionAppliedId);
            }

            $updated = $detailsQuery->update([
                'application_status_id' => $nextStepStatusId
            ]);

            // Keep header in sync (used by some reports)
            DB::table('applicant_headers')->where('id', $applicantId)->update([
                'application_status_id' => $nextStepStatusId
            ]);

            return $this->successResponse([
                'applicant_id' => $applicantId,
                'position_applied_id' => $positionAppliedId,
                'updated_rows' => $updated,
                'application_status_id' => $nextStepStatusId,
            ], 'Applicant moved to next step successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to proceed applicant to next step: ' . $e->getMessage());
        }
    }

    /**
     * Check if applicant_shortlisted has position_applied_id column (per-position shortlisting).
     */
    private function hasShortlistPositionColumn(): bool
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlsrv') {
            $result = DB::selectOne(
                "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'applicant_shortlisted' AND COLUMN_NAME = 'position_applied_id'"
            );
            return $result !== null;
        }
        return \Schema::hasColumn('applicant_shortlisted', 'position_applied_id');
    }

    public function index()
    {
        try {
            $hasPositionColumn = $this->hasShortlistPositionColumn();

            $applicantsQuery = DB::table('applicant_headers as a')
                ->join('applicant_details as b', 'a.id', '=', 'b.applicant_id')
                ->leftJoin('plantillas as c', function ($join) {
                    $join->on('c.id', '=', 'b.position_applied_id')
                        ->whereRaw('ISNULL(b.is_plantilla, 1) = 1');
                })
                ->leftJoin('non_plantillas as np', function ($join) {
                    $join->on('np.id', '=', 'b.position_applied_id')
                        ->whereRaw('ISNULL(b.is_plantilla, 1) = 0');
                })
                ->leftJoin('positions as dp', 'dp.id', '=', 'c.position_id')
                ->leftJoin('positions as dnp', 'dnp.id', '=', 'np.position_id')
                ->leftJoin('departments as ep', 'ep.id', '=', 'c.department_id')
                ->leftJoin('departments as enp', 'enp.id', '=', 'np.department_id')
                ->leftJoin('salary_steps as f', 'f.id', '=', 'c.salary_step_id')
                ->leftJoin('salary_grades as g', 'g.id', '=', 'c.salary_grade_id')
                ->leftJoin('genders as h', 'h.id', '=', 'a.gender')
                ->join('applicant_eete_ratings as i', 'a.id', '=', 'i.applicant_id')
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
                    'a.resume',
                    'a.application_status_id',
                    'a.application_date',
                    'c.id as plantilla_id',
                    DB::raw('ISNULL(c.code, NULL) as code'),
                    DB::raw('COALESCE(dp.name, dnp.name) as position'),
                    'f.name as salary_step',
                    'g.name as salary_grade',
                    DB::raw('COALESCE(ep.name, enp.name) as department'),
                    DB::raw('COALESCE(c.eligibility, np.eligibility) as eligibility'),
                    DB::raw('COALESCE(c.experience, np.experience) as experience'),
                    DB::raw('COALESCE(c.training, np.training) as training'),
                    DB::raw('COALESCE(c.education, np.education) as education'),
                    DB::raw('COALESCE(c.unit, NULL) as unit'),
                    DB::raw('COALESCE(c.publication_from, np.publication_from) as publication_from'),
                    DB::raw('COALESCE(c.publication_to, np.publication_to) as publication_to'),
                    DB::raw('ISNULL(b.is_plantilla, 1) as is_plantilla'),
                    'b.position_applied_id',
                    DB::raw("isnull(i.reviewed_status_id,0) as status_id"),
                    'i.education_rating',
                    'i.experience_rating',
                    'i.training_rating',
                    'i.eligibility_rating',
                    DB::raw('CAST(0 AS decimal(18,2)) as rating')
                )
                ->where('i.reviewed_status_id', 2)
                ->where(function ($q) {
                    $q->whereRaw('(ISNULL(i.is_education_passed, 0) = 1 OR i.education_rating = 1)')
                      ->whereRaw('(ISNULL(i.is_experience_passed, 0) = 1 OR i.experience_rating = 1)')
                      ->whereRaw('(ISNULL(i.is_training_passed, 0) = 1 OR i.training_rating = 1)')
                      ->whereRaw('(ISNULL(i.is_eligibility_passed, 0) = 1 OR i.eligibility_rating = 1)');
                })
                ->where(function ($q) {
                    $q->where(function ($qp) {
                        $qp->whereRaw('ISNULL(b.is_plantilla, 1) = 1')
                            ->where('c.employee_id', 0);
                    })->orWhere(function ($qn) {
                        $qn->whereRaw('ISNULL(b.is_plantilla, 1) = 0')
                            ->whereRaw('ISNULL(np.status, 0) = 1');
                    });
                })
                ->whereRaw("(b.application_status_id = 1 OR b.application_status_id = 2)");

            // Exclude already-hired applicants from the shortlisting list
            // (application_status.id = 6 is "Hired" in the existing system)
            // Use applicant_details.application_status_id (per-position), and keep header check as extra guard.
            $applicantsQuery
                ->whereRaw('ISNULL(b.application_status_id, 0) <> 6')
                ->whereRaw('ISNULL(a.application_status_id, 0) <> 6');

            if ($hasPositionColumn) {
                $applicantsQuery->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('applicant_shortlisted as sl')
                        ->whereColumn('sl.applicant_id', 'a.id')
                        ->where(function ($q) {
                            $q->whereColumn('sl.position_applied_id', 'b.position_applied_id')
                                ->orWhereNull('sl.position_applied_id');
                        });
                });
            } else {
                $applicantsQuery->whereNotIn('a.id', function ($query) {
                    $query->select('applicant_id')->from('applicant_shortlisted');
                });
            }

            $applicants = $applicantsQuery->orderBy('rating', 'desc')->get();

            $shortlistedQuery = DB::table('applicant_headers as a')
                ->join('applicant_details as b', 'a.id', '=', 'b.applicant_id')
                ->leftJoin('plantillas as c', function ($join) {
                    $join->on('c.id', '=', 'b.position_applied_id')
                        ->whereRaw('ISNULL(b.is_plantilla, 1) = 1');
                })
                ->leftJoin('non_plantillas as np', function ($join) {
                    $join->on('np.id', '=', 'b.position_applied_id')
                        ->whereRaw('ISNULL(b.is_plantilla, 1) = 0');
                })
                ->leftJoin('positions as dp', 'dp.id', '=', 'c.position_id')
                ->leftJoin('positions as dnp', 'dnp.id', '=', 'np.position_id')
                ->leftJoin('departments as ep', 'ep.id', '=', 'c.department_id')
                ->leftJoin('departments as enp', 'enp.id', '=', 'np.department_id')
                ->leftJoin('salary_steps as f', 'f.id', '=', 'c.salary_step_id')
                ->leftJoin('salary_grades as g', 'g.id', '=', 'c.salary_grade_id')
                ->leftJoin('genders as h', 'h.id', '=', 'a.gender')
                ->leftJoin('application_status as j', 'a.application_status_id', '=', 'j.id')
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
                    'a.resume',
                    'a.application_status_id',
                    'j.name as application_status',
                    'a.application_date',
                    'c.id as plantilla_id',
                    DB::raw('ISNULL(c.code, NULL) as code'),
                    DB::raw('COALESCE(dp.name, dnp.name) as position'),
                    'f.name as salary_step',
                    'g.name as salary_grade',
                    DB::raw('COALESCE(ep.name, enp.name) as department'),
                    DB::raw('COALESCE(c.eligibility, np.eligibility) as eligibility'),
                    DB::raw('COALESCE(c.experience, np.experience) as experience'),
                    DB::raw('COALESCE(c.training, np.training) as training'),
                    DB::raw('COALESCE(c.education, np.education) as education'),
                    DB::raw('COALESCE(c.unit, NULL) as unit'),
                    DB::raw('COALESCE(c.publication_from, np.publication_from) as publication_from'),
                    DB::raw('COALESCE(c.publication_to, np.publication_to) as publication_to'),
                    DB::raw('ISNULL(b.is_plantilla, 1) as is_plantilla'),
                    'b.position_applied_id',
                    'i.rating',
                    'i.id as shortlisted_id'
                );

            $shortlistedQuery->where(function ($q) {
                $q->where(function ($qp) {
                    $qp->whereRaw('ISNULL(b.is_plantilla, 1) = 1')
                        ->where('c.employee_id', 0);
                })->orWhere(function ($qn) {
                    $qn->whereRaw('ISNULL(b.is_plantilla, 1) = 0')
                        ->whereRaw('ISNULL(np.status, 0) = 1');
                });
            });

            // Exclude hired applicants from the "Shortlisted" tab as well
            // Use applicant_details.application_status_id (per-position), plus header as extra guard.
            $shortlistedQuery
                ->whereRaw('ISNULL(b.application_status_id, 0) <> 6')
                ->whereRaw('ISNULL(a.application_status_id, 0) <> 6');

            if ($hasPositionColumn) {
                $shortlistedQuery->join('applicant_shortlisted as i', function ($join) {
                    $join->on('a.id', '=', 'i.applicant_id')
                        ->where(function ($q) {
                            $q->whereColumn('b.position_applied_id', '=', 'i.position_applied_id')
                                ->orWhereNull('i.position_applied_id');
                        });
                });
            } else {
                $shortlistedQuery->join('applicant_shortlisted as i', 'a.id', '=', 'i.applicant_id');
            }

            $shortlisted = $shortlistedQuery->orderBy('i.rating', 'desc')->get();

            return $this->successResponse([
                'applicants' => $applicants,
                'shortlisted' => $shortlisted
            ], 'Applicant shortlisting data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve applicant shortlisting data: ' . $e->getMessage());
        }
    }

    public function add($id, $rating, $status_id, $position_applied_id = null)
    {
        try {
            $app_key = config('app.key'); // Get the app key for decryption

            if ($status_id == 1) {
                $data = [
                    'applicant_id' => $id,
                    'rating' => $rating,
                    'processed_by' => Auth::user()->id,
                    'processed_date' => now()
                ];
                if ($this->hasShortlistPositionColumn()) {
                    $positionAppliedId = $position_applied_id && (int) $position_applied_id > 0
                        ? (int) $position_applied_id
                        : null;
                    $data['position_applied_id'] = $positionAppliedId;
                }

                DB::table('applicant_shortlisted')->insert($data);

                // Notify applicant by email when shortlisted
                $applicant = DB::table('applicant_headers')->where('id', $id)->first();
                if ($applicant && !empty(trim($applicant->email ?? ''))) {
                    $positionAppliedId = $data['position_applied_id'] ?? null;
                    if ($positionAppliedId === null) {
                        $detail = DB::table('applicant_details')->where('applicant_id', $id)->first();
                        $positionAppliedId = $detail->position_applied_id ?? null;
                    }
                    $plantillaInfo = $positionAppliedId
                        ? DB::table('plantillas')->where('id', $positionAppliedId)->first()
                        : null;
                    $positionInfo = $plantillaInfo
                        ? DB::table('positions')->where('id', $plantillaInfo->position_id ?? null)->first()
                        : null;
                    $departmentInfo = $plantillaInfo
                        ? DB::table('departments')->where('id', $plantillaInfo->department_id ?? null)->first()
                        : null;
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
                        ->where('p.name', 'MUNICIPAL MAYOR')
                        ->first();
                    $employeePosition = $employee
                        ? DB::table('positions')->where('id', $employee->position_id ?? null)->first()
                        : null;
                    $emailData = [
                        'applicant_name' => trim($applicant->first_name . ' ' . ($applicant->middle_name ?? '') . ' ' . $applicant->last_name),
                        'position_name' => $positionInfo->name ?? 'Unknown Position',
                        'department_name' => $departmentInfo->name ?? 'Unknown Department',
                        'employee_name' => $employee ? trim(($employee->first_name ?? '') . ' ' . ($employee->middle_name ?? '') . ' ' . ($employee->last_name ?? '')) : CompanyHelper::getName(),
                        'employee_position' => $employeePosition ? ($employeePosition->name ?? 'Unknown Position') : 'Unknown Position',
                        'pa_region_name' => $employee->pa_region_name ?? '',
                        'pa_province_name' => $employee->pa_province_name ?? '',
                        'pa_city_name' => $employee->pa_city_name ?? '',
                        'date_sent' => now()->format('Y-m-d H:i:s'),
                    ];
                    try {
                        Notification::route('mail', $applicant->email)
                            ->notify(new EmailApplicantShortlisted($emailData));
                    } catch (\Exception $e) {
                        // Log but do not fail the shortlist action
                        \Log::warning('Shortlist email failed for applicant ' . $id . ': ' . $e->getMessage());
                    }
                }

                return $this->successResponse([
                    'applicant_id' => $id,
                    'rating' => $rating,
                    'status_id' => $status_id,
                    'action' => 'shortlisted'
                ], 'Applicant shortlisted successfully');
            } elseif ($status_id == 2) {
                DB::table('applicant_headers')->where('id', $id)->update(['application_status_id' => 2]);

                // ✅ Get applicant info from applicant_headers using applicant_id
                $applicant = DB::table('applicant_headers')
                    ->where('id', $id)
                    ->first();

                if (!$applicant) {
                    return $this->notFoundResponse('Applicant not found');
                }

                // ✅ Get applicant details for position_applied_id
                $applicantDetails = DB::table('applicant_details')
                    ->where('applicant_id', $id)
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
                    'reviewed_status_id' => 2,
                ];

                // ✅ Send email only if applicant has a valid email and status_id == 2
                if ($applicant && !empty($applicant->email)) {
                    Notification::route('mail', $applicant->email)
                        ->notify(new EmailApplicantStatus($emailData));
                }

                return $this->successResponse([
                    'applicant_id' => $id,
                    'status_id' => $status_id,
                    'action' => 'rejected',
                    'email_sent' => !empty($applicant->email)
                ], 'Applicant rejected and notification sent');
            } else {
                DB::table('applicant_headers')->where('id', $id)->update(['status_id' => 1]);

                return $this->successResponse([
                    'applicant_id' => $id,
                    'status_id' => $status_id,
                    'action' => 'status_updated'
                ], 'Applicant status updated successfully');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process applicant shortlisting: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $shortlisted = DB::table('applicant_shortlisted')->where('id', $id)->first();

            if (!$shortlisted) {
                return $this->notFoundResponse('Shortlisted record not found');
            }

            DB::table('applicant_shortlisted')->where('id', $id)->delete();

            return $this->successResponse([
                'shortlisted_id' => $id,
                'action' => 'deleted'
            ], 'Shortlisted record deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete shortlisted record: ' . $e->getMessage());
        }
    }
}
