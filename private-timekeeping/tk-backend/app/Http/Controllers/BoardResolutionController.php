<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use App\Traits\GeneratesPdf;

class BoardResolutionController extends Controller
{
    use ApiResponse, GeneratesPdf;
    public function index()
    {
        try {
            $app_key = env("APP_KEY", "");

            $data = DB::table('employees')
                ->join('branches', 'branches.id', '=', 'employees.branch_id')
                ->join('departments', 'departments.id', '=', 'employees.department_id')
                ->join('positions', 'positions.id', '=', 'employees.position_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->select(
                    'employees.photo',
                    'employees.id',
                    'employees.employee_no',
                    'employees.email',
                    'employment_types.name as employment_type',
                    'positions.name as position',
                    'departments.name as department',
                    'branches.name as branch',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(employees.first_name,' ',substring(employees.middle_name,1,1),'. ',employees.last_name,' - ',positions.name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](employees.first_name,'$app_key'))+' '+UPPER(substring([dbo].[ufn_DecryptString](employees.middle_name,'$app_key'),1,1))+'. '+RTRIM([dbo].[ufn_DecryptString](employees.last_name,'$app_key')+' - '+RTRIM(positions.name)) 
                                END as name"),
                )
                ->orderBy('employees.first_name', 'asc')
                ->where(['employees.is_employee' => true, 'employees.active' => true])
                ->get();

            return $this->successResponse($data, 'Board resolutions retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve board resolutions: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            if (!$request->has('employee') || !$request->has('signatory') || !$request->has('position')) {
                return $this->errorResponse('Employee ID, signatory, and position are required.');
            }

            $app_key = env("APP_KEY", "");

            $board_resolutions =
                DB::table('employees as a')
                ->join('positions as c', 'a.position_id', '=', 'c.id')
                ->join('departments as d', 'a.department_id', '=', 'd.id')
                ->join('genders as e', 'a.gender_id', '=', 'e.id')
                ->leftJoin('name_prefixes as f', 'a.name_prefix_id', '=', 'f.id')
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                                CONCAT(a.first_name,' ',a.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                            END as name"),
                    'a.ra_region',
                    'a.ra_province',
                    'a.ra_city',
                    'a.ra_barangay',
                    'a.pa_region',
                    'a.pa_province',
                    'a.pa_city',
                    'a.pa_barangay',
                    'c.name as position',
                    'd.name as department',
                    'e.name as gender',
                    DB::raw("CONCAT(f.name,' ',a.last_name) as name_sig"),
                )
                ->where('a.id', $request->employee)
                ->get();

            if ($board_resolutions->isEmpty()) {
                return $this->notFoundResponse('Employee not found.');
            }

            $signatories = array(
                'signatory' => $request->signatory,
                'position' => $request->position,
            );

            $pdf = PDF::loadView('board_resolutions.board_resolution', compact(
                'board_resolutions',
                'signatories'
            ))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            
            // Generate PDF content as base64 for API response
            $pdfContent = $pdf->output();
            $base64Content = base64_encode($pdfContent);

            $employee = $board_resolutions->first();

            $filename = 'board_resolution_' . $request->employee . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate board resolution PDF: ' . $e->getMessage());
        }
    }
}
