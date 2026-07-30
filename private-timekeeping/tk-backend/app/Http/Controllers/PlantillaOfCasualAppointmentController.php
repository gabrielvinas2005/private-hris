<?php

namespace App\Http\Controllers;

use App\Department;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PlantillaOfCasualAppointmentController extends Controller
{
    use ApiResponse, GeneratesPdf;
use App\Traits\GeneratesPdf;

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
            $departments = DB::table('departments')
                ->select('id', 'name')
                ->get();

            $signatories = array(
                'signatory1' => '',
                'position1' => '',
                'date1' => '',
                'signatory2' => '',
                'position2' => '',
                'date2' => '',
                'signatory3' => '',
                'position3' => '',
                'date3' => '',
            );
            $data = array(
                'source_of_funds' => '',
                'date_received' =>  '',
                'date_action' => '',
            );
            return $this->successResponse([
                'departments' => $departments,
                'signatories' => $signatories,
                'data' => $data
            ], 'Plantilla of casual appointment data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve plantilla of casual appointment data: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'department' => 'required|exists:departments,id',
                'source_of_funds' => 'required|string|max:255',
                'date_received' => 'required|date',
                'date_action' => 'required|date',
                'signatory1' => 'required|string|max:255',
                'position1' => 'required|string|max:255',
                'date1' => 'required|date',
                'signatory2' => 'required|string|max:255',
                'position2' => 'required|string|max:255',
                'date2' => 'required|date',
                'signatory3' => 'required|string|max:255',
                'position3' => 'required|string|max:255',
                'date3' => 'required|date',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $app_key = env("APP_KEY", "");
            $company = DB::table('companies')
                ->select('name', 'address')
                ->first();
            $currentDate = now()->format('F d, Y');

            $dtl = DB::table('employee_promotions as a')
            ->leftJoin('employees as b', 'b.id', '=', 'a.employee_id')
            ->leftJoin('departments as c', 'c.id', '=', 'a.department_id')
            ->leftJoin('positions as d', 'd.id', '=', 'a.position_id')
            ->leftJoin('name_suffixes as e', 'e.id', '=', 'b.name_suffix_id')
            ->leftJoin('employment_types as f', 'f.id', '=', 'a.employment_type_id')
            ->leftJoin('promotion_natures as g', 'g.id', '=', 'a.nature_of_appointment_id')
            ->select(
                'a.new_salary',
                'a.date_position_appointed',
                DB::raw("
                CASE
                    WHEN ISNULL(b.is_encrypted, 0) = 0 THEN b.last_name
                    ELSE RTRIM([dbo].[ufn_DecryptString](b.last_name, '$app_key'))
                END as last_name"),
        DB::raw("
                CASE
                    WHEN ISNULL(b.is_encrypted, 0) = 0 THEN b.first_name
                    ELSE RTRIM([dbo].[ufn_DecryptString](b.first_name, '$app_key'))
                END as first_name"),
        DB::raw("
                CASE
                    WHEN ISNULL(b.is_encrypted, 0) = 0 THEN b.middle_name
                    ELSE RTRIM([dbo].[ufn_DecryptString](b.middle_name, '$app_key'))
                END as middle_name"),

            'c.name as department',
            'd.name as position',
            'e.name as suffix',
            'f.name as employment_type',
            'g.name as nature_of_appointment',
            )
            ->distinct()
            ->where('a.department_id', $request->department)
            ->where('a.employment_type_id', 2)
            ->orderBy('last_name', 'asc')
            ->get();

            if ($dtl->isEmpty()) {
                return $this->errorResponse('No casual appointment data found for the selected department.');
            }

            $data = array(
                'source_of_funds' => $request->source_of_funds,
                'date_received' => $request->date_received,
                'date_action' => $request->date_action,
            );

            $signatories = array(
                'signatory1' => $request->signatory1,
                'position1' => $request->position1,
                'date1' => $request->date1,
                'signatory2' => $request->signatory2,
                'position2' => $request->position2,
                'date2' => $request->date2,
                'signatory3' => $request->signatory3,
                'position3' => $request->position3,
                'date3' => $request->date3,
            );

            $pdf = PDF::loadView('casual_appointment.casual_appointment_print', compact(
                'dtl', 'company', 'signatories', 'data','currentDate'))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('tabloid', 'landscape');
            $pdfContent = $pdf->output();

            $filename = 'plantilla_of_casual_appointment_' . $request->department . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate plantilla of casual appointment report: ' . $e->getMessage());
        }
    }
}
