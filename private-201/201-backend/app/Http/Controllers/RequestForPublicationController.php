<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Traits\ApiResponse;

class RequestForPublicationController extends Controller
{
    use ApiResponse;

    /**
     * Get approved plantillas for publication request dropdown.
     */
    public function plantillas()
    {
        try {
            $today = now()->toDateString();

            $plantillas = DB::table('plantillas as p')
                ->join('positions as pos', 'pos.id', '=', 'p.position_id')
                ->join('salary_grades as sg', 'sg.id', '=', 'p.salary_grade_id')
                ->join('salary_steps as ss', 'ss.id', '=', 'p.salary_step_id')
                ->join('salary_schedules_details as ssd', function ($join) {
                    $join->on('p.salary_grade_id', '=', 'ssd.salary_grade_id')
                        ->on('p.salary_step_id', '=', 'ssd.salary_step_id');
                })
                ->join('salary_schedules as ssched', function ($join) {
                    $join->on('ssched.id', '=', 'ssd.salary_schedule_id')
                        ->where('ssched.active', 1);
                })
                ->leftJoin('departments as d', 'd.id', '=', 'p.department_id')
                ->select(
                    'p.id',
                    'p.code',
                    'pos.name as position_name',
                    'sg.name as salary_grade_name',
                    'ss.name as salary_step_name',
                    'd.name as department_name',
                    'p.eligibility',
                    'p.experience',
                    'p.training',
                    'p.education',
                    'p.unit',
                    'p.publication_from',
                    'p.publication_to',
                    'p.status',
                    'p.approved',
                    'ssd.amount as monthly_salary'
                )
                // Only active, approved plantillas
                ->where('p.active', 1)
                ->where('p.approved', 1)
                // Only those with a publication period that covers today
                ->whereNotNull('p.publication_from')
                ->whereNotNull('p.publication_to')
                ->whereDate('p.publication_from', '<=', $today)
                ->whereDate('p.publication_to', '>=', $today)
                ->orderBy('p.code')
                ->get();

            return $this->successResponse($plantillas, 'Approved plantillas retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve approved plantillas for publication', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to retrieve approved plantillas.');
        }
    }

    /**
     * Store a new request for publication.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'plantilla_id'   => 'required|integer|exists:plantillas,id',
            'date_requested' => 'required|date',
            'remarks'        => 'nullable|string|max:500',
            'status'         => 'nullable|string|in:Pending,Approved,Rejected',
        ]);

        // Default status to Pending if not provided
        if (empty($validated['status'])) {
            $validated['status'] = 'Pending';
        }

        try {
            $id = DB::table('request_for_publication')->insertGetId([
                'plantilla_id'   => $validated['plantilla_id'],
                'date_requested' => $validated['date_requested'],
                'remarks'        => $validated['remarks'] ?? null,
                'status'         => $validated['status'],
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $record = DB::table('request_for_publication')->where('id', $id)->first();

            return $this->successResponse($record, 'Request for publication created successfully.', 201);
        } catch (\Exception $e) {
            Log::error('Failed to create request for publication', [
                'error'   => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return $this->serverErrorResponse('Failed to create request for publication.');
        }
    }

    /**
     * List existing requests for publication.
     */
    public function index()
    {
        try {
            $requests = DB::table('request_for_publication as rfp')
                ->join('plantillas as p', 'p.id', '=', 'rfp.plantilla_id')
                ->join('positions as pos', 'pos.id', '=', 'p.position_id')
                ->select(
                    'rfp.id',
                    'rfp.date_requested',
                    'rfp.remarks',
                    'rfp.status',
                    'p.code as plantilla_code',
                    'pos.name as position_name',
                    'rfp.created_at'
                )
                ->orderByDesc('rfp.created_at')
                ->get();

            return $this->successResponse($requests, 'Requests for publication retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve requests for publication', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to retrieve requests for publication.');
        }
    }

    /**
     * Get all employees and company data for form selection.
     */
    public function getFormData()
    {
        try {
            $app_key = env("APP_KEY", "");

            // Get all active employees
            $hrmoEmployees = DB::table('employees as e')
                ->leftJoin('positions as p', 'e.position_id', '=', 'p.id')
                ->select(
                    'e.id',
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                CONCAT(e.first_name,' ',COALESCE(e.middle_name,''),' ',e.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+COALESCE(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')),'')+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                            END as name"),
                    'p.name as position_title',
                    'p.id as position_id'
                )
                ->where('e.active', 1)
                ->where('e.is_employee', 1)
                ->orderBy('e.first_name', 'asc')
                ->orderBy('e.last_name', 'asc')
                ->get();

            // Get company data
            $companies = DB::table('companies')->get();
            $company = $companies->isNotEmpty() ? $companies[0] : null;

            $publicationEmail = null;
            if ($company) {
                if (Schema::hasColumn('companies', 'publication_hrmo_email') && !empty($company->publication_hrmo_email)) {
                    $publicationEmail = $company->publication_hrmo_email;
                } else {
                    $publicationEmail = $company->email ?? null;
                }
            }

            return $this->successResponse([
                'hrmo_employees' => $hrmoEmployees,
                'company' => $company ? [
                    'id' => $company->id,
                    'email' => $company->email,
                    'address' => $company->address,
                    'publication_hrmo_email' => $publicationEmail,
                ] : null,
            ], 'Form data retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve form data', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to retrieve form data.');
        }
    }

    /**
     * Save agency email used on the publication form (4th contact row).
     */
    public function saveHrmoEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        try {
            $company = DB::table('companies')->orderBy('id')->first();
            if (!$company) {
                return $this->errorResponse('Company record not found.', 404);
            }

            $update = ['updated_at' => now()];
            if (Schema::hasColumn('companies', 'publication_hrmo_email')) {
                $update['publication_hrmo_email'] = $validated['email'];
            } else {
                $update['email'] = $validated['email'];
            }

            DB::table('companies')->where('id', $company->id)->update($update);

            return $this->successResponse(
                ['publication_hrmo_email' => $validated['email']],
                'Publication contact email saved successfully.'
            );
        } catch (\Exception $e) {
            Log::error('Failed to save publication HRMO email', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to save publication contact email.');
        }
    }

    /**
     * Generate PDF report for request for publication (CS Form No. 9 style).
     */
    public function print(Request $request)
    {
        $validated = $request->validate([
            'plantilla_ids' => 'nullable|array',
            'plantilla_ids.*' => 'integer|exists:plantillas,id',
            'hrmo_employee_id' => 'nullable|integer|exists:employees,id',
            'hrmo_email' => 'nullable|email|max:255',
        ]);

        try {
            $today = now()->toDateString();

            $query = DB::table('plantillas as p')
                ->join('positions as pos', 'pos.id', '=', 'p.position_id')
                ->leftJoin('departments as d', 'd.id', '=', 'p.department_id')
                ->leftJoin('salary_grades as sg', 'sg.id', '=', 'p.salary_grade_id')
                ->leftJoin('salary_steps as ss', 'ss.id', '=', 'p.salary_step_id')
                ->leftJoin('salary_schedules_details as ssd', function ($join) {
                    $join->on('p.salary_grade_id', '=', 'ssd.salary_grade_id')
                        ->on('p.salary_step_id', '=', 'ssd.salary_step_id');
                })
                ->leftJoin('salary_schedules as ssched', function ($join) {
                    $join->on('ssched.id', '=', 'ssd.salary_schedule_id')
                        ->where('ssched.active', 1);
                })
                ->select(
                    'p.id',
                    'p.code as plantilla_item_no',
                    'pos.name as position_title',
                    'sg.name as salary_grade_name',
                    'ss.name as salary_step_name',
                    'ssd.amount as monthly_salary',
                    'p.education',
                    'p.training',
                    'p.experience',
                    'p.eligibility',
                    DB::raw('NULL as competency'),
                    'd.name as place_of_assignment'
                )
                ->where('p.active', 1)
                ->where('p.approved', 1)
                ->whereNotNull('p.publication_from')
                ->whereNotNull('p.publication_to')
                ->whereDate('p.publication_from', '<=', $today)
                ->whereDate('p.publication_to', '>=', $today);

            if (!empty($validated['plantilla_ids'])) {
                $query->whereIn('p.id', $validated['plantilla_ids']);
            }

            $plantillas = $query->orderBy('p.code')->get();

            if ($plantillas->isEmpty()) {
                return $this->errorResponse('No plantillas available for publication.', 404);
            }

            // Fit on a single page: keep at most 10 rows and pad blanks in the view
            $plantillas = $plantillas->take(10);

            // Fetch company data for address fallback
            $companies = DB::table('companies')->get();
            $companyAddress = $companies->isNotEmpty() ? $companies[0]->address : '(Office Address)';
            $fallbackEmail = $companies->isNotEmpty() ? $companies[0]->email : 'mail@agency.gov.ph';

            // Fetch HRMO employee data
            $app_key = env("APP_KEY", "");
            $hrmo = null;

            if (!empty($validated['hrmo_employee_id'])) {
                // Fetch selected employee
                $hrmo = DB::table('employees as e')
                    ->leftJoin('positions as p', 'e.position_id', '=', 'p.id')
                    ->select(
                        DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                    CONCAT(e.first_name,' ',COALESCE(e.middle_name,''),' ',e.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+COALESCE(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')),'')+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                                END as name"),
                        'p.name as position_title',
                        DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.email ELSE dbo.ufn_DecryptString(e.email,'$app_key') END as email")
                    )
                    ->where('e.id', $validated['hrmo_employee_id'])
                    ->where('e.active', 1)
                    ->first();
            }

            // If no employee selected or not found, try to find HRMO by position
            if (!$hrmo) {
                $hrmo = DB::table('employees as e')
                    ->leftJoin('positions as p', 'e.position_id', '=', 'p.id')
                    ->select(
                        DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                    CONCAT(e.first_name,' ',COALESCE(e.middle_name,''),' ',e.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+COALESCE(RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key')),'')+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key'))
                                END as name"),
                        'p.name as position_title',
                        DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN e.email ELSE dbo.ufn_DecryptString(e.email,'$app_key') END as email")
                    )
                    ->where(function ($query) {
                        $query->where('p.name', 'LIKE', '%HRMO%')
                            ->orWhere('p.name', 'LIKE', '%Human Resource Management Officer%')
                            ->orWhere('p.name', 'LIKE', '%Human Resource%');
                    })
                    ->where('e.active', 1)
                    ->first();
            }

            $hrmoName = $hrmo ? $hrmo->name : 'HRMO';
            $hrmoPosition = $hrmo ? $hrmo->position_title : '(Position Title)';

            $savedPublicationEmail = null;
            if ($companies->isNotEmpty() && Schema::hasColumn('companies', 'publication_hrmo_email')) {
                $companyRow = $companies[0];
                if (!empty($companyRow->publication_hrmo_email)) {
                    $savedPublicationEmail = $companyRow->publication_hrmo_email;
                }
            }

            if (!empty($validated['hrmo_email'])) {
                $hrmoEmail = $validated['hrmo_email'];
            } elseif (!empty($savedPublicationEmail)) {
                $hrmoEmail = $savedPublicationEmail;
            } elseif ($hrmo && !empty($hrmo->email)) {
                $hrmoEmail = $hrmo->email;
            } else {
                $hrmoEmail = $fallbackEmail;
            }

            $pdf = app('dompdf.wrapper');
            $pdf->loadView('request_for_publication.print_2025', [
                'plantillas' => $plantillas,
                'printedDate' => now(),
                'hrmoName' => $hrmoName,
                'hrmoPosition' => $hrmoPosition,
                'companyAddress' => $companyAddress,
                'hrmoEmail' => $hrmoEmail,
            ])->setPaper('A4', 'landscape');

            return $pdf->download('request_for_publication.pdf');
        } catch (\Exception $e) {
            Log::error('Failed to generate request for publication report', ['error' => $e->getMessage()]);
            return $this->serverErrorResponse('Failed to generate report.');
        }
    }
}
