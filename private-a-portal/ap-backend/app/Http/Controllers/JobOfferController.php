<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JobOfferController extends Controller
{
    /**
     * Get job offers for the authenticated applicant
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJobOffers(Request $request)
    {
        try {
            $userId = Auth::user()->id;
            
            // Get applicant ID from user ID
            $applicant = DB::table('applicant_headers')
                ->where('user_id', $userId)
                ->first();
            
            if (!$applicant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Applicant not found',
                    'data' => []
                ], 404);
            }
            
            // Fetch job offers (applications with "For Hiring" status)
            $jobOffers = DB::table('applicant_details as ad')
                ->join('applicant_headers as ah', 'ad.applicant_id', '=', 'ah.id')
                ->join('application_status as ast', 'ad.application_status_id', '=', 'ast.id')
                ->join('plantillas as p', 'ad.position_applied_id', '=', 'p.id')
                ->join('positions as pos', 'p.position_id', '=', 'pos.id')
                ->join('departments as dept', 'p.department_id', '=', 'dept.id')
                ->join('salary_grades as sg', 'p.salary_grade_id', '=', 'sg.id')
                ->join('salary_steps as ss', 'p.salary_step_id', '=', 'ss.id')
                // Join with active salary schedule
                ->leftJoin('salary_schedules as sch', function($join) {
                    $join->where('sch.active', '=', 1);
                })
                // Join with salary_schedules_details to get actual salary amount
                ->leftJoin('salary_schedules_details as ssd', function($join) {
                    $join->on('ssd.salary_grade_id', '=', 'p.salary_grade_id')
                         ->on('ssd.salary_step_id', '=', 'p.salary_step_id')
                         ->on('ssd.salary_schedule_id', '=', 'sch.id');
                })
                ->select(
                    'ad.id as application_id',
                    'pos.name as position',
                    'p.code as job_code',
                    'dept.name as department',
                    'p.unit as location',
                    'sg.name as grade',
                    'ss.name as step',
                    'ssd.amount as salary',
                    'ast.name as status',
                    'ad.created_at as applied_date',
                    'p.eligibility',
                    'p.experience',
                    'p.education',
                    'p.training'
                )
                ->where('ah.id', $applicant->id)
                ->where('ad.is_plantilla', true)
                ->where('ad.application_status_id', 5) // Status ID 5 = "For Hiring"
                ->orderBy('ad.created_at', 'desc')
                ->get();
            
            // Format the response
            $formattedOffers = $jobOffers->map(function($offer) {
                $requirements = [];
                if ($offer->eligibility) {
                    $requirements[] = "Eligibility: {$offer->eligibility}";
                }
                if ($offer->education) {
                    $requirements[] = "Education: {$offer->education}";
                }
                if ($offer->experience) {
                    $requirements[] = "Experience: {$offer->experience}";
                }
                if ($offer->training) {
                    $requirements[] = "Training: {$offer->training}";
                }
                
                return [
                    'id' => $offer->application_id,
                    'position' => $offer->position,
                    'jobCode' => $offer->job_code,
                    'department' => $offer->department,
                    'location' => $offer->location ?? 'N/A',
                    'grade' => $offer->grade,
                    'step' => $offer->step,
                    'salary' => $offer->salary ? (float) $offer->salary : null,
                    'status' => $offer->status,
                    'appliedDate' => $offer->applied_date,
                    'requirements' => $requirements,
                    'canAccept' => true,
                    'canReject' => true,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $formattedOffers
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch job offers',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
