<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\PassSlip;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PDF;

class PassSlipController extends Controller
{
    use ApiResponse;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->except(['generatePDF']);
    }

    /**
     * Display a listing of pass slips for the authenticated user.
     */
    public function index($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            // Get employee ID from user ID
            $emp_id_data = DB::table('users as a')
                ->join('employees as b', 'b.employee_no', '=', 'a.employee_no')
                ->select('b.id')
                ->where('a.id', $id)
                ->first();

            if (!$emp_id_data) {
                return $this->errorResponse('Employee not found', 404);
            }

            $emp_id = $emp_id_data->id;

            // Get pass slips for the employee
            $pass_slips = DB::table('pass_slips as a')
                ->leftJoin('employees as b', 'b.id', '=', 'a.approved_by')
                ->select(
                    'a.id',
                    'a.employee_id',
                    'a.date',
                    'a.time_out',
                    'a.time_in',
                    'a.destination',
                    'a.purpose',
                    'a.status',
                    'a.remarks',
                    'a.division_chief',
                    'a.approved_at',
                    'a.created_at',
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as approved_by_name")
                )
                ->where('a.employee_id', $emp_id)
                ->where('a.active', true)
                ->orderBy('a.date', 'desc')
                ->orderBy('a.created_at', 'desc')
                ->get();

            // Check if user is an approver and get supervised employees
            $supervised_employee_ids = [];
            
            // Check approver_headers and approver_details to get employees this user supervises
            $approver_details = DB::table('approver_headers as ah')
                ->join('approver_details as ad', 'ah.id', '=', 'ad.approver_id')
                ->where(function ($query) use ($emp_id) {
                    $query->where('ah.approver_id_1', $emp_id)
                        ->orWhere('ah.approver_id_2', $emp_id)
                        ->orWhere('ah.approver_id_3', $emp_id)
                        ->orWhere('ah.branch_approver_id_1', $emp_id)
                        ->orWhere('ah.division_approver_id_1', $emp_id)
                        ->orWhere('ah.section_approver_id_1', $emp_id);
                })
                ->select('ad.employee_id')
                ->distinct()
                ->get();

            foreach ($approver_details as $detail) {
                $supervised_employee_ids[] = $detail->employee_id;
            }

            $is_approver = count($supervised_employee_ids) > 0;

            // Get pass slips for approval if user is an approver
            $pass_slips_for_approval = [];
            if ($is_approver) {
                $pass_slips_for_approval = DB::table('pass_slips as a')
                    ->join('employees as e', 'e.id', '=', 'a.employee_id')
                    ->leftJoin('employees as b', 'b.id', '=', 'a.approved_by')
                    ->select(
                        'a.id',
                        'a.employee_id',
                        'a.date',
                        'a.time_out',
                        'a.time_in',
                        'a.destination',
                        'a.purpose',
                        'a.status',
                        'a.remarks',
                        'a.division_chief',
                        'a.approved_at',
                        'a.created_at',
                        DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                    CONCAT(e.first_name,' ',e.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) 
                                END as employee_name"),
                        DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                    CONCAT(b.first_name,' ',b.last_name)
                                ELSE
                                    RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                                END as approved_by_name")
                    )
                    ->whereIn('a.employee_id', $supervised_employee_ids)
                    ->where('a.active', true)
                    ->orderBy('a.date', 'asc')
                    ->orderBy('a.created_at', 'asc')
                    ->get();
            }

            return $this->successResponse([
                'pass_slips' => $pass_slips,
                'pass_slips_for_approval' => $pass_slips_for_approval,
                'is_approver' => $is_approver,
                'employee_id' => $emp_id
            ], 'Pass slips loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve pass slips: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified pass slip.
     */
    public function show($id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $pass_slip = DB::table('pass_slips as a')
                ->join('employees as e', 'e.id', '=', 'a.employee_id')
                ->leftJoin('employees as b', 'b.id', '=', 'a.approved_by')
                ->select(
                    'a.*',
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                CONCAT(e.first_name,' ',e.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) 
                            END as employee_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as approved_by_name")
                )
                ->where('a.id', $id)
                ->first();

            if (!$pass_slip) {
                return $this->errorResponse('Pass slip not found', 404);
            }

            return $this->successResponse(['pass_slip' => $pass_slip], 'Pass slip retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve pass slip: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created pass slip.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:employees,id',
                'date' => 'required|date',
                'time_out' => 'nullable|date_format:H:i',
                'time_in' => 'nullable|date_format:H:i',
                'destination' => 'nullable|string',
                'purpose' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            $pass_slip = PassSlip::create([
                'employee_id' => $request->employee_id,
                'date' => $request->date,
                'time_out' => $request->time_out,
                'time_in' => $request->time_in,
                'destination' => $request->destination,
                'purpose' => $request->purpose,
                'status' => 'pending',
                'active' => true
            ]);

            // Create audit log
            Audit::create([
                'user_id' => Auth::id(),
                'action' => 'CREATE',
                'model' => 'PassSlip',
                'model_id' => $pass_slip->id,
                'changes' => json_encode($pass_slip->toArray()),
                'ip_address' => $request->ip()
            ]);

            return $this->successResponse(['pass_slip' => $pass_slip], 'Pass slip created successfully', 201);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create pass slip: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified pass slip.
     */
    public function update(Request $request, $id)
    {
        try {
            $pass_slip = PassSlip::findOrFail($id);

            // Check if pass slip is already approved/disapproved
            if ($pass_slip->status !== 'pending') {
                return $this->errorResponse('Cannot update pass slip that has been ' . $pass_slip->status, 403);
            }

            $validator = Validator::make($request->all(), [
                'date' => 'sometimes|required|date',
                'time_out' => 'nullable|date_format:H:i',
                'time_in' => 'nullable|date_format:H:i',
                'destination' => 'nullable|string',
                'purpose' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            $old_data = $pass_slip->toArray();

            $pass_slip->update($request->only([
                'date',
                'time_out',
                'time_in',
                'destination',
                'purpose'
            ]));

            // Create audit log
            Audit::create([
                'user_id' => Auth::id(),
                'action' => 'UPDATE',
                'model' => 'PassSlip',
                'model_id' => $pass_slip->id,
                'changes' => json_encode([
                    'old' => $old_data,
                    'new' => $pass_slip->toArray()
                ]),
                'ip_address' => $request->ip()
            ]);

            return $this->successResponse(['pass_slip' => $pass_slip], 'Pass slip updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update pass slip: ' . $e->getMessage());
        }
    }

    /**
     * Approve or disapprove a pass slip.
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $pass_slip = PassSlip::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:approved,disapproved',
                'remarks' => 'nullable|string',
                'division_chief' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            // Get approver employee ID
            $user = Auth::user();
            $approver = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$approver) {
                return $this->errorResponse('Approver employee not found', 404);
            }

            $old_data = $pass_slip->toArray();

            $pass_slip->update([
                'status' => $request->status,
                'remarks' => $request->remarks,
                'division_chief' => $request->division_chief,
                'approved_by' => $approver->id,
                'approved_at' => now()
            ]);

            // Create audit log
            Audit::create([
                'user_id' => Auth::id(),
                'action' => 'UPDATE_STATUS',
                'model' => 'PassSlip',
                'model_id' => $pass_slip->id,
                'changes' => json_encode([
                    'old' => $old_data,
                    'new' => $pass_slip->toArray(),
                    'action' => $request->status
                ]),
                'ip_address' => $request->ip()
            ]);

            return $this->successResponse(['pass_slip' => $pass_slip], 'Pass slip ' . $request->status . ' successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update pass slip status: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified pass slip (soft delete).
     */
    public function destroy(Request $request, $id)
    {
        try {
            $pass_slip = PassSlip::findOrFail($id);

            // Check if pass slip belongs to the authenticated user
            $user = Auth::user();
            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            if (!$employee || $pass_slip->employee_id !== $employee->id) {
                return $this->errorResponse('Unauthorized to delete this pass slip', 403);
            }

            // Check if pass slip is already approved
            if ($pass_slip->status === 'approved') {
                return $this->errorResponse('Cannot delete approved pass slip', 403);
            }

            $old_data = $pass_slip->toArray();

            // Soft delete by setting active to false
            $pass_slip->update(['active' => false]);

            // Create audit log
            Audit::create([
                'user_id' => Auth::id(),
                'action' => 'DELETE',
                'model' => 'PassSlip',
                'model_id' => $pass_slip->id,
                'changes' => json_encode($old_data),
                'ip_address' => $request->ip()
            ]);

            return $this->successResponse(null, 'Pass slip deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete pass slip: ' . $e->getMessage());
        }
    }

    /**
     * Generate PDF for pass slip.
     */
    public function generatePDF(Request $request, $id)
    {
        try {
            // Manual authentication check - accept token from query parameter or header
            $token = $request->query('token') ?? $request->bearerToken();
            
            if (!$token) {
                return response('Unauthorized', 401)
                    ->header('Content-Type', 'text/plain');
            }

            // Verify token using Sanctum
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            
            if (!$accessToken || !$accessToken->tokenable) {
                return response('Invalid or expired token', 401)
                    ->header('Content-Type', 'text/plain');
            }

            // Token is valid, proceed with PDF generation
            $app_key = env("APP_KEY", "");

            $pass_slip = DB::table('pass_slips as a')
                ->join('employees as e', 'e.id', '=', 'a.employee_id')
                ->leftJoin('employees as b', 'b.id', '=', 'a.approved_by')
                ->select(
                    'a.*',
                    DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                CONCAT(e.first_name,' ',e.middle_name,' ',e.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](e.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.middle_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](e.last_name,'$app_key')) 
                            END as employee_full_name"),
                    DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key')) 
                            END as approved_by_name")
                )
                ->where('a.id', $id)
                ->first();

            if (!$pass_slip) {
                return response('Pass slip not found', 404)
                    ->header('Content-Type', 'text/plain');
            }

            // Generate PDF
            $pdf = PDF::loadView('pass_slip_pdf', compact('pass_slip'));
            $pdf->setPaper('A4');
            
            // Use stream() for inline display in iframe
            return $pdf->stream('pass_slip_' . $id . '.pdf');
        } catch (\Exception $e) {
            \Log::error('Pass slip PDF generation error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response('Failed to generate PDF: ' . $e->getMessage(), 500)
                ->header('Content-Type', 'text/plain');
        }
    }
}
