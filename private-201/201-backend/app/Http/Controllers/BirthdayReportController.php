<?php

namespace App\Http\Controllers;

use PDF;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BirthdayReportController extends Controller
{
    use ApiResponse;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Generate PDF report for birthday summary
     */
    public function print(Request $request)
    {
        try {
            $app_key = env("APP_KEY", "");
            $month = $request->input('month', null); // Optional month filter (1-12)

            // Build query
            $query = DB::table('employees')
                ->select(
                    'employees.id',
                    'employees.birthdate',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN 
                                employees.first_name 
                            ELSE 
                                dbo.ufn_DecryptString(employees.first_name,'$app_key') 
                            END as first_name"),
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN 
                                employees.middle_name 
                            ELSE 
                                dbo.ufn_DecryptString(employees.middle_name,'$app_key') 
                            END as middle_name"),
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN 
                                employees.last_name 
                            ELSE 
                                dbo.ufn_DecryptString(employees.last_name,'$app_key') 
                            END as last_name")
                )
                ->where('employees.is_employee', true)
                ->where('employees.active', true)
                ->whereNotNull('employees.birthdate');

            // Filter by month if provided
            if ($month && is_numeric($month) && $month >= 1 && $month <= 12) {
                $query->whereRaw('DATEPART(MONTH, employees.birthdate) = ?', [$month]);
            }

            $employees = $query->orderByRaw('DATEPART(MONTH, employees.birthdate), DATEPART(DAY, employees.birthdate)')
                ->get();

            // Process employees: format name and birthdate
            $processedEmployees = $employees->map(function ($employee) {
                // Format name as "Last, First Middle"
                $lastName = trim($employee->last_name ?? '');
                $firstName = trim($employee->first_name ?? '');
                $middleName = trim($employee->middle_name ?? '');
                
                $middleInitial = !empty($middleName) ? ' ' . substr($middleName, 0, 1) . '.' : '';
                $name = $lastName . (!empty($lastName) && !empty($firstName) ? ', ' : '') . $firstName . $middleInitial;

                // Format birthdate as "Month Day, Year"
                $birthdate = null;
                $birthdateFormatted = 'N/A';
                if ($employee->birthdate) {
                    try {
                        $birthdate = Carbon::parse($employee->birthdate);
                        $birthdateFormatted = $birthdate->format('F j, Y');
                    } catch (\Exception $e) {
                        $birthdateFormatted = 'N/A';
                    }
                }

                return (object) [
                    'name' => $name,
                    'birthdate' => $birthdate,
                    'birthdate_formatted' => $birthdateFormatted,
                    'month' => $birthdate ? $birthdate->month : null,
                ];
            })->filter(function ($employee) {
                return $employee->birthdate !== null;
            });

            // Group by month
            $employeesByMonth = $processedEmployees->groupBy(function ($employee) {
                if ($employee->birthdate) {
                    return $employee->birthdate->format('F'); // Full month name
                }
                return 'Unknown';
            });

            // Sort months chronologically
            $monthOrder = [
                'January' => 1, 'February' => 2, 'March' => 3, 'April' => 4,
                'May' => 5, 'June' => 6, 'July' => 7, 'August' => 8,
                'September' => 9, 'October' => 10, 'November' => 11, 'December' => 12
            ];

            $sortedMonths = $employeesByMonth->sortBy(function ($employees, $monthName) use ($monthOrder) {
                return $monthOrder[$monthName] ?? 99;
            });

            // Handle empty result
            if ($processedEmployees->isEmpty()) {
                return $this->errorResponse('No employees found with birthdate information.', 404);
            }

            // Split into two columns
            $allMonths = $sortedMonths->keys()->toArray();
            $totalMonths = count($allMonths);
            $midPoint = ceil($totalMonths / 2);

            $leftColumnMonths = [];
            $rightColumnMonths = [];

            $currentIndex = 0;
            foreach ($sortedMonths as $monthName => $monthEmployees) {
                if ($currentIndex < $midPoint) {
                    $leftColumnMonths[$monthName] = $monthEmployees->values()->all();
                } else {
                    $rightColumnMonths[$monthName] = $monthEmployees->values()->all();
                }
                $currentIndex++;
            }

            // Generate PDF
            $pdf = PDF::loadView('Birthdate.Birthdate', [
                'leftColumnMonths' => $leftColumnMonths,
                'rightColumnMonths' => $rightColumnMonths,
            ])->setOptions(['defaultFont' => 'sans-serif']);

            $pdf->setPaper('A4', 'portrait');
            $pdfContent = $pdf->output();

            $filename = 'birthday_summary';
            if ($month && is_numeric($month) && $month >= 1 && $month <= 12) {
                $monthName = Carbon::create(2000, $month, 1)->format('F');
                $filename .= '_' . strtolower($monthName);
            }
            $filename .= '_' . date('Y-m-d') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            \Log::error('Failed to generate birthday report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return $this->serverErrorResponse('Failed to generate birthday report: ' . $e->getMessage());
        }
    }
}
