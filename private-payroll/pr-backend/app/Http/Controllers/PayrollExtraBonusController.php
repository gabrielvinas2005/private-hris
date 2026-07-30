<?php

namespace App\Http\Controllers;

use PDF;
use App\Http\Controllers\Controller;
use App\Support\PayrollBenefitsEmployeeScope;
use App\Support\ReportDivisionFilter;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PayrollExtraBonusController extends Controller
{
    use ApiResponse;
    
    public function index()
    {
        try {
            $extra_bonus_payrolls = DB::table('extra_bonus_payroll_headers as a')
                ->join('extra_bonus_types as b', 'a.extra_bonus_type_id', '=', 'b.id')
                ->leftJoin('divisions as div', 'a.department_id', '=', 'div.id')
                ->leftJoin('departments as c', 'a.department_id', '=', 'c.id')
                ->select(
                    'a.id',
                    'b.name as extra_bonus_type',
                    DB::raw('COALESCE(div.name, c.name) as department'),
                    DB::raw('COALESCE(div.name, c.name) as division'),
                    'a.year_id',
                    'a.is_posted'
                )
                ->get();

            return $this->successResponse($extra_bonus_payrolls, 'Payroll extra bonus list retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve payroll extra bonus list: ' . $e->getMessage());
        }
    }

    public function show($extra_bonus_id)
    {
        try {
            $app_key = env("APP_KEY", "");

            $extra_bonuses = DB::table('extra_bonus_types')->where('active', true)->orderBy('name', 'asc')->get();
            $divisions = ReportDivisionFilter::activeDivisions();
            $extra_bonus_payrolls = DB::table("extra_bonus_payroll_headers")->where('id', $extra_bonus_id)->get();

            if ($extra_bonus_payrolls->isNotEmpty()) {
                $header = $extra_bonus_payrolls[0];
                $resolvedDivisionId = ReportDivisionFilter::resolveHeaderDivisionId(
                    $header->department_id
                );
                if ($resolvedDivisionId) {
                    $header->department_id = $resolvedDivisionId;
                }
                $extra_bonus_payrolls = collect([$header]);
            }

            $employees = DB::table('extra_bonus_payroll_headers as a')
                ->join('extra_bonus_payroll_details as b', 'a.id', '=', 'b.extra_bonus_id')
                ->join('employees as c', 'b.employee_id', '=', 'c.id')
                ->leftJoin('positions as d', 'c.position_id', '=', 'd.id')
                ->select(
                    'c.id as employee_id',
                    'c.employee_no',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                        CONCAT(c.first_name,' ',c.last_name)
                    ELSE
                        RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                    END as name"),
                    'd.name as position',
                    'c.salary',
                    'b.amount'
                )
                ->where('a.id', $extra_bonus_id)
                ->orderBy('name', 'asc')
                ->get();

            if ($extra_bonus_payrolls->isEmpty()) {
                $extra_bonus_payrolls = [
                    'id' => 0,
                    'extra_bonus_type_id' => 0,
                    'department_id' => 0,
                    'year_id' => 0,
                    'is_posted' => false
                ];

                $extra_bonus_payrolls = (object)$extra_bonus_payrolls;
                $extra_bonus_payrolls = collect([$extra_bonus_payrolls]);
            }

            return $this->successResponse([
                'extra_bonuses' => $extra_bonuses,
                'divisions' => $divisions,
                'departments' => $divisions,
                'employees' => $employees,
                'extra_bonus_payrolls' => $extra_bonus_payrolls
            ], 'Payroll extra bonus form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load payroll extra bonus form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'extra_bonus_type_id' => 'required',
                'department_id' => 'required',
                'year_id' => 'required',
                'employee_id' => 'required|array',
                'employee_id.*' => 'required|integer',
                'amount' => 'required|array',
                'amount.*' => 'required|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $extra_bonus_id = $request->extra_bonus_id;
            $created_count = 0;
            $updated_count = 0;

            $divisionId = $request->department_id === 'all'
                ? $request->department_id
                : (ReportDivisionFilter::resolveId($request) ?? $request->department_id);

            if ($extra_bonus_id == 0) {
                DB::table('extra_bonus_payroll_headers')->insert([
                    'extra_bonus_type_id' => $request->extra_bonus_type_id,
                    'department_id' => $divisionId,
                    'year_id' => $request->year_id,
                ]);

                $extra_bonus_id = DB::table('extra_bonus_payroll_headers')->max('id');
            } else {
                DB::table('extra_bonus_payroll_headers')
                    ->where(['id' => $extra_bonus_id])
                    ->update([
                        'extra_bonus_type_id' => $request->extra_bonus_type_id,
                        'department_id' => $divisionId,
                        'year_id' => $request->year_id,
                    ]);
            }

            $employee_data = $request->all();
            $data = [];

            for ($i = 0; $i < count($employee_data['employee_id']); $i++) {
                $data = [
                    'amount' => $employee_data['amount'][$i],
                ];

                // Check if record exists
                $existing = DB::table('extra_bonus_payroll_details')
                    ->where([
                        'extra_bonus_id' => $extra_bonus_id,
                        'employee_id' => $employee_data['employee_id'][$i]
                    ])
                    ->exists();

                DB::table('extra_bonus_payroll_details')->updateOrInsert([
                    'extra_bonus_id' => $extra_bonus_id,
                    'employee_id' => $employee_data['employee_id'][$i]
                ], $data);

                if ($existing) {
                    $updated_count++;
                } else {
                    $created_count++;
                }
            }

            return $this->successResponse([
                'extra_bonus_id' => $extra_bonus_id,
                'created_count' => $created_count,
                'updated_count' => $updated_count,
                'total_processed' => count($employee_data['employee_id'])
            ], 'Successfully Saved Extra Bonus Payroll!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save extra bonus payroll: ' . $e->getMessage());
        }
    }

    public function loadEmployees($extra_bonus_type_id, $department_id, $year_id)
    {
        try {
            $app_key = env("APP_KEY", "");
            $isAllDepartments = $department_id === 'all';
            $divisionId = $isAllDepartments ? null : (int) $department_id;

            if (!$isAllDepartments && $divisionId) {
                if (!DB::table('divisions')->where('id', $divisionId)->exists()) {
                    $resolved = ReportDivisionFilter::resolveHeaderDivisionId($divisionId);
                    $divisionId = $resolved ?: $divisionId;
                }
            }

            $legacyHeaderIds = ($isAllDepartments || !$divisionId)
                ? []
                : ReportDivisionFilter::legacyHeaderDepartmentIdsForDivision($divisionId);

            $employee_data = DB::table('employees as a')
                ->join('positions as b', 'a.position_id', '=', 'b.id')
                ->select(
                    'a.id as employee_id',
                    'a.employee_no',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted,0) = 0 THEN
                        CONCAT(a.first_name,' ',a.last_name)
                    ELSE
                        RTRIM([dbo].[ufn_DecryptString](a.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](a.last_name,'$app_key')) 
                    END as name"),
                    'b.name as position',
                    'a.salary',
                    DB::raw("CAST(0 AS DECIMAL(18,2)) as amount")
                )
                ->where('a.active', true)
                ->where('a.is_employee', true);

            PayrollBenefitsEmployeeScope::apply($employee_data, 'a');

            if (!$isAllDepartments && $divisionId) {
                $employee_data->where('a.division_id', $divisionId);
            }

            $employee_data->whereNotIn('a.id', function ($query) use ($extra_bonus_type_id, $year_id, $isAllDepartments, $legacyHeaderIds) {
                $query->select('b.employee_id')
                    ->from('extra_bonus_payroll_headers as a')
                    ->join('extra_bonus_payroll_details as b', 'a.id', '=', 'b.extra_bonus_id')
                    ->where('a.extra_bonus_type_id', $extra_bonus_type_id)
                    ->where('a.year_id', $year_id);
                if (!$isAllDepartments && !empty($legacyHeaderIds)) {
                    $query->whereIn('a.department_id', $legacyHeaderIds);
                }
            });

            $existing = DB::table('extra_bonus_payroll_headers as a')
                ->join('extra_bonus_payroll_details as b', 'a.id', '=', 'b.extra_bonus_id')
                ->join('employees as c', 'b.employee_id', '=', 'c.id')
                ->leftJoin('positions as d', 'c.position_id', '=', 'd.id')
                ->select(
                    'c.id as employee_id',
                    'c.employee_no',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                        CONCAT(c.first_name,' ',c.last_name)
                    ELSE
                        RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                    END as name"),
                    'd.name as position',
                    'c.salary',
                    'b.amount'
                )
                ->where('a.extra_bonus_type_id', $extra_bonus_type_id)
                ->where('a.year_id', $year_id);

            PayrollBenefitsEmployeeScope::apply($existing, 'c');

            if (!$isAllDepartments && !empty($legacyHeaderIds)) {
                $existing->whereIn('a.department_id', $legacyHeaderIds);
            }

            $employees = $existing->union($employee_data)->orderBy('name', 'asc')->get();

            return $this->successResponse($employees, 'Payroll extra bonus table retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load payroll extra bonus employees: ' . $e->getMessage());
        }
    }

    public function process($extra_bonus_id, $type_id)
    {
        try {
            if ($type_id == 1) {
                DB::table('extra_bonus_payroll_headers')->where('id', $extra_bonus_id)->update(['is_posted' => true]);

                return $this->successResponse(['extra_bonus_id' => $extra_bonus_id], 'Successfully Posted Extra Bonus Payroll!');
            } else {
                DB::table('extra_bonus_payroll_headers')->where('id', $extra_bonus_id)->update(['is_posted' => false]);

                return $this->successResponse(['extra_bonus_id' => $extra_bonus_id], 'Successfully Unposted Extra Bonus Payroll!');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to process extra bonus payroll: ' . $e->getMessage());
        }
    }

    public function destroy($extra_bonus_id)
    {
        try {
            $header = DB::table('extra_bonus_payroll_headers')
                ->where('id', $extra_bonus_id)
                ->first();

            if (!$header) {
                return $this->notFoundResponse('Extra bonus payroll record not found.');
            }

            if ($header->is_posted) {
                return $this->errorResponse(
                    'Cannot delete a posted extra bonus payroll. Unpost it first, then delete.',
                    400
                );
            }

            DB::transaction(function () use ($extra_bonus_id) {
                DB::table('extra_bonus_payroll_details')
                    ->where('extra_bonus_id', $extra_bonus_id)
                    ->delete();

                DB::table('extra_bonus_payroll_headers')
                    ->where('id', $extra_bonus_id)
                    ->delete();
            });

            return $this->successResponse(
                null,
                'Extra bonus payroll deleted successfully.'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                'Failed to delete extra bonus payroll: ' . $e->getMessage()
            );
        }
    }

    public function bulkPost(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'records' => 'required|array',
                'records.*.id' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $records = $request->records;
            $posted_count = 0;
            $unposted_count = 0;
            $failed_count = 0;

            foreach ($records as $record) {
                try {
                    $id = $record['id'];
                    $type_id = $record['type_id'] ?? 1; // Default to post (type_id = 1)

                    if ($type_id == 1) {
                        // Post the record
                        DB::table('extra_bonus_payroll_headers')
                            ->where('id', $id)
                            ->update(['is_posted' => true]);
                        $posted_count++;
                    } else {
                        // Unpost the record
                        DB::table('extra_bonus_payroll_headers')
                            ->where('id', $id)
                            ->update(['is_posted' => false]);
                        $unposted_count++;
                    }
                } catch (\Exception $e) {
                    $failed_count++;
                    \Log::error('Bulk post error for record ' . ($record['id'] ?? 'unknown') . ': ' . $e->getMessage());
                }
            }

            return $this->successResponse([
                'posted_count' => $posted_count,
                'unposted_count' => $unposted_count,
                'failed_count' => $failed_count,
                'total_processed' => count($records)
            ], "Bulk post completed: {$posted_count} posted, {$unposted_count} unposted, {$failed_count} failed");
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to bulk post extra bonus payroll: ' . $e->getMessage());
        }
    }

    public function report()
    {
        try {
            $app_key = env("APP_KEY", "");

            $extra_bonuses = DB::table('extra_bonus_types')->where('active', true)->orderBy('name', 'asc')->get();
            $divisions = ReportDivisionFilter::activeDivisions();

            $signatory_options = DB::table('employees as a')
                ->leftJoin('positions as b', 'a.position_id', '=', 'b.id')
                ->where(['a.is_employee' => true, 'a.active' => true])
                ->select(
                    'a.id',
                    DB::raw("CASE WHEN ISNULL(a.is_encrypted, 0) = 0 THEN
                        CONCAT(RTRIM(a.last_name), ', ', RTRIM(a.first_name), ' ', RTRIM(ISNULL(SUBSTRING(a.middle_name, 1, 1), '')), '.')
                    ELSE
                        CONCAT(RTRIM(dbo.ufn_DecryptString(a.last_name, '$app_key')), ', ', RTRIM(dbo.ufn_DecryptString(a.first_name, '$app_key')), ' ', RTRIM(ISNULL(SUBSTRING(dbo.ufn_DecryptString(a.middle_name, '$app_key'), 1, 1), '')), '.')
                    END as name"),
                    'b.name as position'
                )
                ->orderBy('a.last_name', 'asc')
                ->orderBy('a.first_name', 'asc')
                ->get();

            return $this->successResponse([
                'extra_bonuses' => $extra_bonuses,
                'divisions' => $divisions,
                'departments' => $divisions,
                'signatory_options' => $signatory_options
            ], 'Payroll extra bonus report form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load payroll extra bonus report form data: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'extra_bonus_type_id' => 'required',
                    'division_id' => 'required_without:department_id',
                    'department_id' => 'required_without:division_id',
                    'year_id' => 'required',
                    'signatory_1' => 'required|string|max:255',
                    'signatory_position_1' => 'required|string|max:255',
                    'signatory_2' => 'required|string|max:255',
                    'signatory_position_2' => 'required|string|max:255',
                    'signatory_3' => 'required|string|max:255',
                    'signatory_position_3' => 'required|string|max:255',
                    'signatory_4' => 'required|string|max:255',
                    'signatory_position_4' => 'required|string|max:255',
                    'signatory_5' => 'required|string|max:255',
                    'signatory_position_5' => 'required|string|max:255',
                ],
                [],
                [
                    'extra_bonus_type_id' => 'Extra Bonus Type',
                    'division_id' => 'Division',
                    'department_id' => 'Division',
                    'year_id' => 'Year',
                    'signatory_1' => 'Signatory 1 (Name)',
                    'signatory_position_1' => 'Signatory 1 (Position)',
                    'signatory_2' => 'Signatory 2 (Name)',
                    'signatory_position_2' => 'Signatory 2 (Position)',
                    'signatory_3' => 'Signatory 3 (Name)',
                    'signatory_position_3' => 'Signatory 3 (Position)',
                    'signatory_4' => 'Signatory 4 (Name)',
                    'signatory_position_4' => 'Signatory 4 (Position)',
                    'signatory_5' => 'Signatory 5 (Name)',
                    'signatory_position_5' => 'Signatory 5 (Position)',
                ]
            );

            if ($validator->fails()) {
                return $this->validationErrorResponse(
                    $validator->errors(),
                    'Please complete all required fields before generating the report.'
                );
            }

            $divisionId = ReportDivisionFilter::resolveId($request);
            if (!$divisionId) {
                return $this->validationErrorResponse(
                    ['division_id' => ['Please select a Division.']],
                    'Please select a Division before generating the report.'
                );
            }

            $headerDepartmentIds = ReportDivisionFilter::legacyHeaderDepartmentIdsForDivision($divisionId);
            $yearId = (int) $request->year_id;
            $bonusTypeId = (int) $request->extra_bonus_type_id;
            $divisionName = ReportDivisionFilter::displayName($divisionId);
            $selectedBonusName = DB::table('extra_bonus_types')
                ->where('id', $bonusTypeId)
                ->value('name');

            $app_key = env("APP_KEY", "");
            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $baseQuery = function () use ($headerDepartmentIds, $divisionId, $yearId, $bonusTypeId) {
                return DB::table('extra_bonus_payroll_headers as a')
                    ->join('extra_bonus_payroll_details as b', 'a.id', '=', 'b.extra_bonus_id')
                    ->join('employees as c', 'b.employee_id', '=', 'c.id')
                    ->where('a.year_id', $yearId)
                    ->where('a.extra_bonus_type_id', $bonusTypeId)
                    ->where('b.amount', '>', 0)
                    ->where(function ($query) use ($headerDepartmentIds, $divisionId) {
                        $query->whereIn('a.department_id', $headerDepartmentIds)
                            ->orWhere('c.division_id', $divisionId);
                    });
            };

            $extra_bonus_payrolls = $baseQuery()
                ->join('positions as d', 'c.position_id', '=', 'd.id')
                ->leftJoin('divisions as e', 'c.division_id', '=', 'e.id')
                ->join('extra_bonus_types as f', 'a.extra_bonus_type_id', '=', 'f.id')
                ->select(
                    'c.id as employee_id',
                    'c.employee_no',
                    DB::raw("CASE WHEN ISNULL(c.is_encrypted,0) = 0 THEN
                        CONCAT(c.first_name,' ',c.last_name)
                    ELSE
                        RTRIM([dbo].[ufn_DecryptString](c.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](c.last_name,'$app_key')) 
                    END as name"),
                    'd.name as position',
                    'e.name as department',
                    'f.name as extra_bonus_type',
                    'f.code',
                    'c.salary',
                    'b.amount',
                    'a.year_id'
                )
                ->orderBy('name', 'asc')
                ->get();

            if ($extra_bonus_payrolls->isEmpty()) {
                $headersForDivisionYear = DB::table('extra_bonus_payroll_headers as a')
                    ->join('extra_bonus_types as t', 'a.extra_bonus_type_id', '=', 't.id')
                    ->where('a.year_id', $yearId)
                    ->where(function ($query) use ($headerDepartmentIds, $divisionId) {
                        $query->whereIn('a.department_id', $headerDepartmentIds)
                            ->orWhereExists(function ($sub) use ($divisionId) {
                                $sub->select(DB::raw(1))
                                    ->from('extra_bonus_payroll_details as d')
                                    ->join('employees as e', 'd.employee_id', '=', 'e.id')
                                    ->whereColumn('d.extra_bonus_id', 'a.id')
                                    ->where('e.division_id', $divisionId);
                            });
                    })
                    ->select('t.name')
                    ->distinct()
                    ->pluck('name')
                    ->filter()
                    ->values();

                if ($headersForDivisionYear->isNotEmpty()) {
                    $available = $headersForDivisionYear->join(', ');
                    return $this->errorResponse(
                        'No extra bonus report data for "' . ($selectedBonusName ?: 'selected bonus type') . '" '
                        . 'in ' . $divisionName . ' for year ' . $yearId . '. '
                        . 'Encoded bonus type(s) for this division and year: ' . $available . '. '
                        . 'Select the same Extra Bonus Type as in Payroll Benefits.',
                        400
                    );
                }

                return $this->errorResponse(
                    'No extra bonus payroll data found for ' . $divisionName . ', year ' . $yearId
                    . ', and bonus type "' . ($selectedBonusName ?: 'selected') . '". '
                    . 'Encode extra bonus in Payroll Benefits for that division and year, then try again.',
                    400
                );
            }

            $signatories = [
                'signatory_1' => $request->signatory_1,
                'signatory_position_1' => $request->signatory_position_1,
                'signatory_2' => $request->signatory_2,
                'signatory_position_2' => $request->signatory_position_2,
                'signatory_3' => $request->signatory_3,
                'signatory_position_3' => $request->signatory_position_3,
                'signatory_4' => $request->signatory_4,
                'signatory_position_4' => $request->signatory_position_4,
                'signatory_5' => $request->signatory_5,
                'signatory_position_5' => $request->signatory_position_5,
            ];
            
            $companies = DB::table('companies')->get();

            $pdf = PDF::loadView('payroll_extra_bonus.payroll_extra_bonus_print', compact(
                'extra_bonus_payrolls',
                'image',
                'signatories',
                'companies',
            ))
                ->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('tabloid', 'landscape');
            $pdfContent = $pdf->output();

            $filename = 'payroll_extra_bonus_report_' . $request->extra_bonus_type_id . '_' . $divisionId . '_' . $request->year_id . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate payroll extra bonus report: ' . $e->getMessage());
        }
    }
}
