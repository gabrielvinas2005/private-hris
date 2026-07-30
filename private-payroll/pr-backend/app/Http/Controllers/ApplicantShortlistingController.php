<?php

namespace App\Http\Controllers;

use Auth;
use Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Notifications\EmailApplicantStatus;
use App\Traits\ApiResponse;

class ApplicantShortlistingController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $applicants = DB::table('applicant_headers as a')
                ->join('applicant_details as b', 'a.id', '=', 'b.applicant_id')
                ->join('plantillas as c', 'c.id', '=', 'b.position_applied_id')
                ->join('positions as d', 'd.id', '=', 'c.position_id')
                ->join('departments as e', 'e.id', '=', 'c.department_id')
                ->join('salary_steps as f', 'f.id', '=', 'c.salary_step_id')
                ->join('salary_grades as g', 'g.id', '=', 'c.salary_grade_id')
                ->join('genders as h', 'h.id', '=', 'a.gender')
                ->join('applicant_eete_ratings as i', 'a.id', '=', 'i.applicant_id')
                // ->crossJoin('eete_ratings as j')
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
                    DB::raw("isnull(i.reviewed_status_id,0) as status_id"),
                    'i.education_rating',
                    'i.experience_rating',
                    'i.training_rating',
                    'i.eligibility_rating',
                    DB::raw('CAST(0 AS decimal(18,2)) as rating')
                )
                ->where([
                    'c.employee_id' => 0,
                    'i.reviewed_status_id' => 1
                ])
                ->whereRaw("(b.application_status_id = 1 OR b.application_status_id = 2)")
                ->whereNotIn('a.id', function ($query) {
                    $query->select('applicant_id')->from('applicant_shortlisted')->get();
                })
                ->orderBy('rating', 'desc')
                ->get();

            $shortlisted = DB::table('applicant_headers as a')
                ->join('applicant_details as b', 'a.id', '=', 'b.applicant_id')
                ->join('plantillas as c', 'c.id', '=', 'b.position_applied_id')
                ->join('positions as d', 'd.id', '=', 'c.position_id')
                ->join('departments as e', 'e.id', '=', 'c.department_id')
                ->join('salary_steps as f', 'f.id', '=', 'c.salary_step_id')
                ->join('salary_grades as g', 'g.id', '=', 'c.salary_grade_id')
                ->join('genders as h', 'h.id', '=', 'a.gender')
                ->join('applicant_shortlisted as i', 'a.id', '=', 'i.applicant_id')
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
                    'i.rating',
                    'i.id as shortlisted_id'
                )
                ->where([
                    'b.application_status_id' => 1,
                    'c.employee_id' => 0
                ])
                ->orderBy('i.rating', 'desc')
                ->get();

            return $this->successResponse([
                'applicants' => $applicants,
                'shortlisted' => $shortlisted
            ], 'Applicant shortlisting data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve applicant shortlisting data: ' . $e->getMessage());
        }
    }

    public function add($id, $rating, $status_id)
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

                DB::table('applicant_shortlisted')->insert($data);

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
