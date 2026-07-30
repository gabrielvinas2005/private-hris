<?php

namespace App\Http\Controllers;

use App\Helpers\CompanyHelper;
use Auth;
use App\Audit;
use App\Position;
use App\Support\PositionDescriptionChecks;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc as WordJc;

class PositionDescriptionController extends Controller
{
    use ApiResponse;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get all positions for position description
     */
    public function index()
    {
        try {
            $data = Position::all();
            return $this->successResponse($data, 'Positions retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve positions: ' . $e->getMessage());
        }
    }

    /**
     * Get all PDF records
     */
    public function getPdfRecords()
    {
        try {
            // Join PDF with salary_grades and positions tables (main position)
            $pdfRecords = DB::table('PDF')
                ->leftJoin('salary_grades', 'PDF.salarygrade_id', '=', 'salary_grades.id')
                ->leftJoin('positions as main_positions', 'PDF.position_id', '=', 'main_positions.id')
                ->select(
                    'PDF.*',
                    'salary_grades.name as salary_grade_name',
                    'salary_grades.id as salary_grade_id_from_table',
                    'main_positions.name as position_title'
                )
                ->orderBy('PDF.Created_at', 'desc')
                ->get();

            // Add other related data
            foreach ($pdfRecords as $record) {
                // If join didn't populate main position title, attempt a safe fallback lookup
                if (empty($record->position_title)) {
                    try {
                        if (isset($record->position_id) && $record->position_id) {
                            $fallbackMain = DB::table('positions')->where('id', $record->position_id)->first();
                            if ($fallbackMain) {
                                $record->position_title = $fallbackMain->name;
                            }
                        }
                    } catch (\Exception $e) {
                        // ignore fallback errors
                    }
                 }
                
                // Debug: Log salary grade and position information
                \Log::info('PDF Record Debug:', [
                    'pdf_id' => $record->id,
                    'salarygrade_id' => $record->salarygrade_id,
                    'salary_grade_name' => $record->salary_grade_name ?? 'NULL',
                    'salary_grade_id_from_table' => $record->salary_grade_id_from_table ?? 'NULL',
                    'position_id' => $record->position_id ?? 'NULL',
                    'position_title' => $record->position_title ?? 'NULL',
                ]);
                
                // We now have both position titles:
                // - position_title (from PDF table itself)
                // - supervised_position_title (from positions table join)
                
                // Fallback: If salary_grade_name is null, try to fetch it manually
                if (empty($record->salary_grade_name) && isset($record->salarygrade_id) && $record->salarygrade_id) {
                    $salaryGrade = DB::table('salary_grades')->where('id', $record->salarygrade_id)->first();
                    if ($salaryGrade) {
                        $record->salary_grade_name = $salaryGrade->name;
                    }
                }
                
                // Try to get employee data
                if (isset($record->Employee_no) && $record->Employee_no) {
                    $employee = DB::table('employees')->where('employee_no', $record->Employee_no)->first();
                    if ($employee) {
                        $record->first_name = $employee->first_name;
                        $record->last_name = $employee->last_name;
                    }
                }
                
                // Initialize empty arrays for related data
                $record->sodar_records = [];
                $record->core_competencies = [];
                $record->leadership_competencies = [];
                
                // Try to get SODAR records
                try {
                    $sodarRecords = DB::table('SODAR')
                        ->where('PDF_id', $record->id)
                        ->get();
                    
                    foreach ($sodarRecords as $sodar) {
                        $competencyLevel = DB::table('Competency_level')
                            ->where('id', $sodar->Competencylevel_id)
                            ->first();
                        
                        $record->sodar_records[] = [
                            'percentage' => $sodar->Percetage ?? '',
                            'responsibilities' => $sodar->Responsibilities ?? '',
                            'competency_level' => $competencyLevel->Level ?? 'Unknown'
                        ];
                    }
                } catch (\Exception $e) {
                    // Skip SODAR if there's an error
                }
                
                // Try to get core competencies
                try {
                    $coreCompetencies = DB::table('PDF_Corecompetencies')
                        ->where('PDF_id', $record->id)
                        ->get();
                    
                    foreach ($coreCompetencies as $core) {
                        $competencyLevel = DB::table('Competency_level')
                            ->where('id', $core->CompetencyLevel_id)
                            ->first();
                        
                        $record->core_competencies[] = [
                            'percentage' => $core->Percetage ?? '',
                            'responsibilities' => $core->Responsibilities ?? '',
                            'competency_level' => $competencyLevel->Level ?? 'Unknown'
                        ];
                    }
                } catch (\Exception $e) {
                    // Skip core competencies if there's an error
                }
                
                // Try to get leadership competencies
                try {
                    $leadershipCompetencies = DB::table('PDF_LeadershipCompetencies')
                        ->where('PDF_id', $record->id)
                        ->get();
                    
                    foreach ($leadershipCompetencies as $leadership) {
                        $competencyLevel = DB::table('Competency_level')
                            ->where('id', $leadership->CompetencyLevel_id)
                            ->first();
                        
                        $record->leadership_competencies[] = [
                            'competency' => $leadership->Competency ?? '',
                            'competency_level' => $competencyLevel->Level ?? 'Unknown'
                        ];
                    }
                } catch (\Exception $e) {
                    // Skip leadership competencies if there's an error
                }
            }
            
            return $this->successResponse($pdfRecords, 'PDF records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve PDF records: ' . $e->getMessage());
        }
    }

    /**
     * Get PDF record by ID
     */
    public function getPdfRecord($id)
    {
        try {
            $pdfRecord = DB::table('PDF')->where('id', $id)->first();
            
            if (!$pdfRecord) {
                return $this->errorResponse('PDF record not found', 404);
            }

            // Get related SODAR records
            $sodarRecords = DB::table('SODAR')->where('PDF_id', $id)->get();
            
            // Get related core competencies
            $coreCompetencies = DB::table('PDF_Corecompetencies')->where('PDF_id', $id)->get();
            
            // Get related leadership competencies
            $leadershipCompetencies = DB::table('PDF_LeadershipCompetencies')->where('PDF_id', $id)->get();

            // Get related supervised positions (new table)
            $supervisedPositions = DB::table('PDF_SupervisedPositions')
                ->where('PDF_id', $id)
                ->get();

            $data = [
                'pdf' => $pdfRecord,
                'sodar' => $sodarRecords,
                'core_competencies' => $coreCompetencies,
                'leadership_competencies' => $leadershipCompetencies,
                'supervised_positions' => $supervisedPositions
            ];

            return $this->successResponse($data, 'PDF record with related data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve PDF record: ' . $e->getMessage());
        }
    }

    /**
     * Create new PDF record
     */
    public function createPdfRecord(Request $request)
    {
        try {
            $data = $request->validate([
                'Employee_no' => 'required|string|max:250',
                'position_id' => 'nullable|integer',
                'item_number' => 'required|string|max:250',
                'salarygrade_id' => 'nullable|integer',
                'equiptment' => 'nullable|string|max:250',
                'stakeholders' => 'nullable|string|max:1000',
                'working_Condition' => 'nullable|string|max:1000',
                'unit_description' => 'nullable|string',
                'position_description' => 'nullable|string',
                'education' => 'nullable|string',
                'experience' => 'nullable|string',
                'training' => 'nullable|string',
                'eigibility' => 'nullable|string',
                'supervisor' => 'nullable|string|max:250',
                'employee_date' => 'nullable|date',
                'supervisor_date' => 'nullable|date',
                'immediate_supervisor_position_id' => 'nullable|integer',
                'next_higher_supervisor_position_id' => 'nullable|integer'
            ]);

            $id = DB::table('PDF')->insertGetId($data);

            // Log audit
            Audit::create([
                'user_id' => Auth::id(),
                'action' => 'Created PDF record',
                'table_name' => 'PDF',
                'record_id' => $id,
                'old_values' => null,
                'new_values' => json_encode($data)
            ]);

            return $this->successResponse(['id' => $id], 'PDF record created successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create PDF record: ' . $e->getMessage());
        }
    }

    /**
     * Update PDF record
     */
    public function updatePdfRecord(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'Employee_no' => 'sometimes|string|max:250',
                'position_id' => 'nullable|integer',
                'item_number' => 'sometimes|string|max:250',
                'salarygrade_id' => 'nullable|integer',
                'equiptment' => 'nullable|string|max:250',
                'stakeholders' => 'nullable|string|max:1000',
                'working_Condition' => 'nullable|string|max:1000',
                'unit_description' => 'nullable|string',
                'position_description' => 'nullable|string',
                'education' => 'nullable|string',
                'experience' => 'nullable|string',
                'training' => 'nullable|string',
                'eigibility' => 'nullable|string',
                'supervisor' => 'nullable|string|max:250',
                'employee_date' => 'nullable|date',
                'supervisor_date' => 'nullable|date',
                'immediate_supervisor_position_id' => 'nullable|integer',
                'next_higher_supervisor_position_id' => 'nullable|integer'
            ]);

            $oldRecord = DB::table('PDF')->where('id', $id)->first();
            
            if (!$oldRecord) {
                return $this->errorResponse('PDF record not found', 404);
            }

            DB::table('PDF')->where('id', $id)->update($data);

            // Log audit
            Audit::create([
                'user_id' => Auth::id(),
                'action' => 'Updated PDF record',
                'table_name' => 'PDF',
                'record_id' => $id,
                'old_values' => json_encode($oldRecord),
                'new_values' => json_encode($data)
            ]);

            return $this->successResponse(null, 'PDF record updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update PDF record: ' . $e->getMessage());
        }
    }

    /**
     * Get plantilla item number by position ID
     */
    public function getPlantillaItemNumber($positionId)
    {
        try {
            $plantilla = DB::table('plantillas')
                ->where('position_id', $positionId)
                ->where('active', true)
                ->first();
            
            if ($plantilla) {
                return $this->successResponse(['item_number' => $plantilla->code], 'Plantilla item number retrieved successfully');
            }
            
            return $this->successResponse(['item_number' => null], 'No active plantilla found for this position');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to get plantilla item number: ' . $e->getMessage());
        }
    }

    /**
     * Delete PDF record
     */
    public function deletePdfRecord($id)
    {
        try {
            $record = DB::table('PDF')->where('id', $id)->first();
            
            if (!$record) {
                return $this->errorResponse('PDF record not found', 404);
            }

            // Delete related records first
            DB::table('SODAR')->where('PDF_id', $id)->delete();
            DB::table('PDF_Corecompetencies')->where('PDF_id', $id)->delete();
            DB::table('PDF_LeadershipCompetencies')->where('PDF_id', $id)->delete();
            DB::table('PDF_SupervisedPositions')->where('PDF_id', $id)->delete();

            // Delete main record
            DB::table('PDF')->where('id', $id)->delete();

            // Log audit
            Audit::create([
                'user_id' => Auth::id(),
                'action' => 'Deleted PDF record',
                'table_name' => 'PDF',
                'record_id' => $id,
                'old_values' => json_encode($record),
                'new_values' => null
            ]);

            return $this->successResponse(null, 'PDF record deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete PDF record: ' . $e->getMessage());
        }
    }

    /**
     * Add SODAR record
     */
    public function addSodarRecord(Request $request)
    {
        try {
            $data = $request->validate([
                'PDF_id' => 'required|integer',
                'Percetage' => 'required|string|max:50',
                'Responsibilities' => 'required|string|max:50',
                'Competencylevel_id' => 'required|integer'
            ]);

            $id = DB::table('SODAR')->insertGetId($data);

            // Log audit
            Audit::create([
                'user_id' => Auth::id(),
                'action' => 'Created SODAR record',
                'table_name' => 'SODAR',
                'record_id' => $id,
                'old_values' => null,
                'new_values' => json_encode($data)
            ]);

            return $this->successResponse(['id' => $id], 'SODAR record created successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create SODAR record: ' . $e->getMessage());
        }
    }

    /**
     * Delete SODAR record
     */
    public function deleteSodarRecord($id)
    {
        try {
            DB::table('SODAR')->where('id', $id)->delete();
            return $this->successResponse(null, 'SODAR record deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete SODAR record: ' . $e->getMessage());
        }
    }

    /**
     * Add Core Competency record
     */
    public function addCoreCompetency(Request $request)
    {
        try {
            $data = $request->validate([
                'PDF_id' => 'required|integer',
                'Competency' => 'required|string|max:250',
                'CompetencyLevel_id' => 'required|integer'
            ]);

            $id = DB::table('PDF_Corecompetencies')->insertGetId($data);

            // Log audit
            Audit::create([
                'user_id' => Auth::id(),
                'action' => 'Created Core Competency record',
                'table_name' => 'PDF_Corecompetencies',
                'record_id' => $id,
                'old_values' => null,
                'new_values' => json_encode($data)
            ]);

            return $this->successResponse(['id' => $id], 'Core Competency record created successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create Core Competency record: ' . $e->getMessage());
        }
    }

    /**
     * Delete Core Competency
     */
    public function deleteCoreCompetency($id)
    {
        try {
            DB::table('PDF_Corecompetencies')->where('id', $id)->delete();
            return $this->successResponse(null, 'Core Competency deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete Core Competency: ' . $e->getMessage());
        }
    }

    /**
     * Add Leadership Competency record
     */
    public function addLeadershipCompetency(Request $request)
    {
        try {
            $data = $request->validate([
                'PDF_id' => 'required|integer',
                // Accept correct field name from frontend
                'Competency' => 'required|string|max:250',
                'CompetencyLevel_id' => 'required|integer'
            ]);

            // Map to database column casing if necessary
            if (!isset($data['COmpetency']) && isset($data['Competency'])) {
                $data['COmpetency'] = $data['Competency'];
                // Remove key that is not an actual DB column to avoid SQL error
                unset($data['Competency']);
            }

            $id = DB::table('PDF_LeadershipCompetencies')->insertGetId($data);

            // Log audit
            Audit::create([
                'user_id' => Auth::id(),
                'action' => 'Created Leadership Competency record',
                'table_name' => 'PDF_LeadershipCompetencies',
                'record_id' => $id,
                'old_values' => null,
                'new_values' => json_encode($data)
            ]);

            return $this->successResponse(['id' => $id], 'Leadership Competency record created successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create Leadership Competency record: ' . $e->getMessage());
        }
    }

    /**
     * Delete Leadership Competency
     */
    public function deleteLeadershipCompetency($id)
    {
        try {
            DB::table('PDF_LeadershipCompetencies')->where('id', $id)->delete();
            return $this->successResponse(null, 'Leadership Competency deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete Leadership Competency: ' . $e->getMessage());
        }
    }

    /**
     * Add Supervised Position record
     */
    public function addSupervisedPosition(Request $request)
    {
        try {
            $data = $request->validate([
                'PDF_id' => 'required|integer',
                'supervised_positionTitle_ID' => 'nullable|integer',
                'supervised_item_number' => 'nullable|string|max:250',
            ]);

            $id = DB::table('PDF_SupervisedPositions')->insertGetId($data);

            Audit::create([
                'user_id' => Auth::id(),
                'action' => 'Created Supervised Position record',
                'table_name' => 'PDF_SupervisedPositions',
                'record_id' => $id,
                'old_values' => null,
                'new_values' => json_encode($data),
            ]);

            return $this->successResponse(['id' => $id], 'Supervised Position record created successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create Supervised Position record: ' . $e->getMessage());
        }
    }

    /**
     * Delete Supervised Position
     */
    public function deleteSupervisedPosition($id)
    {
        try {
            DB::table('PDF_SupervisedPositions')->where('id', $id)->delete();
            return $this->successResponse(null, 'Supervised Position deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete Supervised Position: ' . $e->getMessage());
        }
    }

    /**
     * Get all competency levels
     */
    public function getCompetencyLevels()
    {
        try {
            $data = DB::table('Competency_level')->get();
            return $this->successResponse($data, 'Competency levels retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve competency levels: ' . $e->getMessage());
        }
    }

    /**
     * Get work experience records
     */
    public function getWorkExperience($referenceId = null)
    {
        try {
            $query = DB::table('Work_Experience');
            
            if ($referenceId) {
                $query->where('Reference_id', $referenceId);
            }
            
            $data = $query->get();
            return $this->successResponse($data, 'Work experience records retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve work experience records: ' . $e->getMessage());
        }
    }

    /**
     * Add work experience record
     */
    public function addWorkExperience(Request $request)
    {
        try {
            $data = $request->validate([
                'Reference_id' => 'required|string|max:250',
                'Position' => 'required|string|max:250',
                'Work_start_date' => 'required|date',
                'Work_end_date' => 'nullable|date',
                'Duration' => 'nullable|string|max:50',
                'Office_name' => 'required|string|max:250',
                'Office_Address' => 'required|string|max:250',
                'Immediate_supervisor' => 'nullable|string|max:250',
                'List_Of_Accomplishment' => 'nullable|string',
                'Summary_of_Duties' => 'nullable|string'
            ]);

            $id = DB::table('Work_Experience')->insertGetId($data);

            // Log audit
            Audit::create([
                'user_id' => Auth::id(),
                'action' => 'Created Work Experience record',
                'table_name' => 'Work_Experience',
                'record_id' => $id,
                'old_values' => null,
                'new_values' => json_encode($data)
            ]);

            return $this->successResponse(['id' => $id], 'Work experience record created successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create work experience record: ' . $e->getMessage());
        }
    }

    /**
     * Generate Position Description PDF using database data
     */
    public function print(Request $request)
    {
        try {
            $pdfId = $request->input('pdf_id');
            
            if ($pdfId) {
                // Get data from database with position information
                $app_key = config('app.key');
                $pdfRecord = DB::table('PDF')
                    ->leftJoin('positions', 'PDF.position_id', '=', 'positions.id')
                    ->leftJoin('salary_grades', 'PDF.salarygrade_id', '=', 'salary_grades.id')
                    ->leftJoin('employees as emp', 'PDF.Employee_no', '=', 'emp.employee_no')
                    ->leftJoin('employees as sup', 'PDF.supervisor', '=', 'sup.employee_no')
                    ->select(
                        'PDF.*',
                        'positions.name as position_title',
                        'positions.description as position_description_from_position',
                        DB::raw('salary_grades.name as salary_grade_name'),
                        // Decrypt employee name parts if encrypted
                        DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.first_name ELSE dbo.ufn_DecryptString(emp.first_name, '$app_key') END as emp_first"),
                        DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.middle_name ELSE dbo.ufn_DecryptString(emp.middle_name, '$app_key') END as emp_middle"),
                        DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.last_name ELSE dbo.ufn_DecryptString(emp.last_name, '$app_key') END as emp_last"),
                        // Decrypt supervisor name parts if encrypted
                        DB::raw("CASE WHEN ISNULL(sup.is_encrypted,0) = 0 THEN sup.first_name ELSE dbo.ufn_DecryptString(sup.first_name, '$app_key') END as sup_first"),
                        DB::raw("CASE WHEN ISNULL(sup.is_encrypted,0) = 0 THEN sup.middle_name ELSE dbo.ufn_DecryptString(sup.middle_name, '$app_key') END as sup_middle"),
                        DB::raw("CASE WHEN ISNULL(sup.is_encrypted,0) = 0 THEN sup.last_name ELSE dbo.ufn_DecryptString(sup.last_name, '$app_key') END as sup_last")
                    )
                    ->where('PDF.id', $pdfId)
                    ->first();
                
                if (!$pdfRecord) {
                    return $this->errorResponse('PDF record not found', 404);
                }

                // Get related data
                $sodarRecords = DB::table('SODAR')
                    ->leftJoin('Competency_level', 'SODAR.Competencylevel_id', '=', 'Competency_level.id')
                    ->where('SODAR.PDF_id', $pdfId)
                    ->select('SODAR.*', 'Competency_level.Level as competency_level')
                    ->orderBy('SODAR.id')
                    ->get();
                $coreCompetencies = DB::table('PDF_Corecompetencies')
                    ->leftJoin('Competency_level', 'PDF_Corecompetencies.CompetencyLevel_id', '=', 'Competency_level.id')
                    ->where('PDF_Corecompetencies.PDF_id', $pdfId)
                    ->select('PDF_Corecompetencies.*', 'Competency_level.Level as competency_level')
                    ->orderBy('PDF_Corecompetencies.id')
                    ->get();
                $leadershipCompetencies = DB::table('PDF_LeadershipCompetencies')
                    ->leftJoin('Competency_level', 'PDF_LeadershipCompetencies.CompetencyLevel_id', '=', 'Competency_level.id')
                    ->where('PDF_LeadershipCompetencies.PDF_id', $pdfId)
                    ->select('PDF_LeadershipCompetencies.*', 'Competency_level.Level as competency_level')
                    ->orderBy('PDF_LeadershipCompetencies.id')
                    ->get();

                // Prepare data for PDF aligned with report structure
                $employeeFullname = trim(implode(' ', array_filter([
                    $pdfRecord->emp_first ?? '',
                    $pdfRecord->emp_middle ?? '',
                    $pdfRecord->emp_last ?? '',
                ])));

                $supervisorFullname = trim(implode(' ', array_filter([
                    $pdfRecord->sup_first ?? '',
                    $pdfRecord->sup_middle ?? '',
                    $pdfRecord->sup_last ?? '',
                ])));

                $hasEmployeeDateOverride = $request->exists('employee_date');
                $hasSupervisorDateOverride = $request->exists('supervisor_date');
                $employeeDateInput = $request->input('employee_date');
                $supervisorDateInput = $request->input('supervisor_date');

                $employeeDate = $hasEmployeeDateOverride
                    ? (!empty($employeeDateInput) ? date('F d, Y', strtotime($employeeDateInput)) : '')
                    : (!empty($pdfRecord->employee_date) ? date('F d, Y', strtotime($pdfRecord->employee_date)) : date('F d, Y'));
                $supervisorDate = $hasSupervisorDateOverride
                    ? (!empty($supervisorDateInput) ? date('F d, Y', strtotime($supervisorDateInput)) : '')
                    : (!empty($pdfRecord->supervisor_date) ? date('F d, Y', strtotime($pdfRecord->supervisor_date)) : date('F d, Y'));

                // Fetch supervised positions (up to 7 rows) from new table, with legacy fallback
                $supervisedPositionsList = [];

                try {
                    $supervisedRows = DB::table('PDF_SupervisedPositions')
                        ->leftJoin('positions', 'PDF_SupervisedPositions.supervised_positionTitle_ID', '=', 'positions.id')
                        ->where('PDF_SupervisedPositions.PDF_id', $pdfId)
                        ->select(
                            'PDF_SupervisedPositions.*',
                            'positions.name as title'
                        )
                        ->orderBy('PDF_SupervisedPositions.id')
                        ->limit(7)
                        ->get();

                    foreach ($supervisedRows as $row) {
                        $supervisedPositionsList[] = [
                            'title' => $row->title ?? 'N/A',
                            'item_number' => $row->supervised_item_number ?? 'N/A',
                        ];
                    }
                } catch (\Exception $e) {
                    // If anything goes wrong with the new table, fall back to legacy fields
                }

                // Legacy fallback if no rows in new table
                if (count($supervisedPositionsList) === 0) {
                    $legacyTitle = 'N/A';
                    $legacyItem = 'N/A';

                    if ($pdfRecord->supervised_positionTitle_ID) {
                        $supervisedPosition = DB::table('positions')
                            ->where('id', $pdfRecord->supervised_positionTitle_ID)
                            ->first();
                        if ($supervisedPosition) {
                            $legacyTitle = $supervisedPosition->name ?? 'N/A';
                        }

                        $supervisedPlantilla = DB::table('plantillas')
                            ->where('position_id', $pdfRecord->supervised_positionTitle_ID)
                            ->where('active', true)
                            ->first();
                        if ($supervisedPlantilla) {
                            $legacyItem = $supervisedPlantilla->code ?? 'N/A';
                        } elseif ($pdfRecord->supervised_item_number) {
                            $legacyItem = $pdfRecord->supervised_item_number;
                        }
                    } elseif ($pdfRecord->supervised_item_number) {
                        $legacyItem = $pdfRecord->supervised_item_number;
                    }

                    $supervisedPositionsList[] = [
                        'title' => $legacyTitle,
                        'item_number' => $legacyItem,
                    ];
                }

                $firstSupervised = $supervisedPositionsList[0] ?? ['title' => 'N/A', 'item_number' => 'N/A'];

                $positionData = [
                    // Position information from positions table
                    'position_title' => $pdfRecord->position_title ?? '',
                    'statement_of_duties' => $pdfRecord->position_description_from_position ?? '',
                    
                    // PDF record data
                    'item_number' => $pdfRecord->item_number ?? '',
                    'employee_no' => $pdfRecord->Employee_no ?? '',
                    'salary_grade' => PositionDescriptionChecks::salaryGradeNumber($pdfRecord->salary_grade_name ?? ''),
                    'salary_step' => '',
                    'salary_amount' => $pdfRecord->salary ?? '',
                    'authorized_salary' => $pdfRecord->salary ?? '',
                    'other_compensation' => $pdfRecord->other_compensation ?? '',
                    'equipment' => $pdfRecord->equiptment ?? '',
                    'machines_tools' => $pdfRecord->equiptment ?? 'Computer/Laptop, Printer, Telephone, External Hard Drive, Switches, UTP Cable, Television, Headset and Microphone',
                    'stakeholders' => $pdfRecord->stakeholders ?? '',
                    'working_condition' => $pdfRecord->working_Condition ?? '',
                    'stakeholders_checks' => PositionDescriptionChecks::parseStakeholders($pdfRecord->stakeholders ?? null),
                    'working_condition_checks' => PositionDescriptionChecks::parseWorkingCondition($pdfRecord->working_Condition ?? null),
                    
                    // Report sections 19.a and 19.b
                    'unit_function' => $pdfRecord->unit_description ?? '', // 19.a
                    'job_summary' => $pdfRecord->position_description ?? '', // 19.b
                    
                    // Requirements
                    'education' => $pdfRecord->education ?? '',
                    'experience' => $pdfRecord->experience ?? '',
                    'training' => $pdfRecord->training ?? '',
                    'eligibility' => $pdfRecord->eigibility ?? '',
                    'supervisor' => $pdfRecord->supervisor ?? '',
                    'employee_name' => $employeeFullname,
                    'supervisor_name' => $supervisorFullname,
                    'employee_date' => $employeeDate,
                    'supervisor_date' => $supervisorDate,
                    
                    // Section 15 - Supervised Position
                    'supervised_position_title' => $firstSupervised['title'],
                    'supervised_item_number' => $firstSupervised['item_number'],
                    'supervised_positions' => $supervisedPositionsList,
                    
                    // Placeholders; will be filled below
                    'core_competencies' => '',
                    'core_level' => '',
                    'leadership_competencies' => '',
                    'leadership_level' => ''
                ];

                // Map SODAR into duty rows (percentage, description, level)
                $index = 1;
                foreach ($sodarRecords as $sr) {
                    if ($index > 20) { break; }
                    // Parse percentage (accept values like '10', '10%', '10.5').
                    // Legacy records may store fraction form (e.g., 1 = 100%).
                    $raw = is_null($sr->Percetage) ? '0' : (string)$sr->Percetage;
                    $num = (float)str_replace('%', '', trim($raw));
                    if (strpos($raw, '%') === false && $num > 0 && $num <= 1) {
                        $num = $num * 100;
                    }
                    if ($num <= 0) { continue; }
                    // Write row
                    $formattedPercent = fmod($num, 1.0) > 0
                        ? rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.')
                        : number_format($num, 0, '.', '');
                    $positionData['duty_' . $index . '_percentage'] = $formattedPercent . '%';
                    $positionData['duty_' . $index . '_description'] = $sr->Responsibilities ?? '';
                    $positionData['duty_' . $index . '_level'] = $sr->competency_level ?? '';
                    $index++;
                }

                // Map core competencies to newline string and set representative levels
                if ($coreCompetencies->count() > 0) {
                    $positionData['core_competencies'] = implode("\n", $coreCompetencies->pluck('Competency')->filter()->all());
                    $coreLevels = $coreCompetencies->pluck('competency_level')->filter()->unique()->values()->all();
                    $positionData['core_level'] = implode("\n", $coreLevels);
                }

                // Map leadership competencies to newline string and set a representative level
                if ($leadershipCompetencies->count() > 0) {
                    $positionData['leadership_competencies'] = implode("\n", $leadershipCompetencies->map(function($lc){ return $lc->COmpetency ?? $lc->Competency; })->filter()->all());
                    $levels = $leadershipCompetencies->pluck('competency_level')->filter()->unique()->values()->all();
                    $positionData['leadership_level'] = implode("\n", $levels);
                }

                // Handle supervisor position titles (sections 13 & 14)
                // Check request first, then database, then defaults
                $immediateSupervisorPositionId = $request->input('immediate_supervisor_position_id') ?? $pdfRecord->immediate_supervisor_position_id ?? null;
                $nextHigherSupervisorPositionId = $request->input('next_higher_supervisor_position_id') ?? $pdfRecord->next_higher_supervisor_position_id ?? null;

                if ($immediateSupervisorPositionId) {
                    $immediatePosition = DB::table('positions')->where('id', $immediateSupervisorPositionId)->first();
                    $positionData['immediate_supervisor_position_title'] = $immediatePosition->name ?? '';
                } else {
                    // Default hardcoded values if not provided
                    $positionData['immediate_supervisor_position_title'] = "SUPERVISING\nTRADE-INDUSTRY\nDEVELOPMENT SPECIALIST\n\nCHIEF TRADE-INDUSTRY\nDEVELOPMENT SPECIALIST";
                }

                if ($nextHigherSupervisorPositionId) {
                    $nextHigherPosition = DB::table('positions')->where('id', $nextHigherSupervisorPositionId)->first();
                    $positionData['next_higher_supervisor_position_title'] = $nextHigherPosition->name ?? '';
                } else {
                    // Default hardcoded values if not provided
                    $positionData['next_higher_supervisor_position_title'] = "DEPUTY EXECUTIVE DIRECTOR\n\nEXECUTIVE DIRECTOR";
                }
            } else {
                // Fallback to form data
            $positionData = $request->all();
            
            // Get position details if position ID is provided
            if (isset($positionData['position']) && $positionData['position']) {
                $position = Position::find($positionData['position']);
                if ($position) {
                    $positionData['position_title'] = $position->name;  
                    $positionData['item_number'] = $position->code;
                }
            }

            // Handle supervisor position titles (sections 13 & 14)
            $immediateSupervisorPositionId = $request->input('immediate_supervisor_position_id');
            $nextHigherSupervisorPositionId = $request->input('next_higher_supervisor_position_id');

            if ($immediateSupervisorPositionId) {
                $immediatePosition = DB::table('positions')->where('id', $immediateSupervisorPositionId)->first();
                $positionData['immediate_supervisor_position_title'] = $immediatePosition->name ?? '';
            } else {
                // Default hardcoded values if not provided
                $positionData['immediate_supervisor_position_title'] = "SUPERVISING\nTRADE-INDUSTRY\nDEVELOPMENT SPECIALIST\n\nCHIEF TRADE-INDUSTRY\nDEVELOPMENT SPECIALIST";
            }

            if ($nextHigherSupervisorPositionId) {
                $nextHigherPosition = DB::table('positions')->where('id', $nextHigherSupervisorPositionId)->first();
                $positionData['next_higher_supervisor_position_title'] = $nextHigherPosition->name ?? '';
            } else {
                // Default hardcoded values if not provided
                $positionData['next_higher_supervisor_position_title'] = "DEPUTY EXECUTIVE DIRECTOR\n\nEXECUTIVE DIRECTOR";
            }

            // Set default values for missing fields
            $positionData = array_merge([
                'position_title' => '',
                'item_number' => '',
                'salary_grade' => '',
                'step' => '',
                'department' => '',
                'division' => '',
                'office' => '',
                'location' => '',
                'position_summary' => '',
                'education' => '',
                'experience' => '',
                'training' => '',
                'eligibility' => '',
                'competencies' => '',
                'reports_to' => '',
                'supervises' => '',
                'coordinates_with' => '',
                'performance_indicators' => '',
                'work_environment' => '',
                'physical_demands' => '',
                'travel_requirements' => '',
                'prepared_by' => '',
                'prepared_by_position' => '',
                'prepared_date' => '',
                'reviewed_by' => '',
                'reviewed_by_position' => '',
                'reviewed_date' => '',
                'approved_by' => '',
                'approved_by_position' => '',
                'approved_date' => ''
            ], $positionData);
            }

            // Generate PDF
            $pdf = Pdf::loadView('Position_Description.PositionDescription_new', compact('positionData'));
            $pdf->setPaper('A4', 'portrait');

            // Return as raw PDF bytes for axios blob consumption
            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="position_description_' . date('Y-m-d') . '.pdf"'
            ]);

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Download Position Description DOCX
     */
    public function downloadDocx(Request $request)
    {
        try {
            $pdfId = $request->input('pdf_id');
            
            // Use same data fetching logic as print method
            if ($pdfId) {
                $app_key = config('app.key');
                $pdfRecord = DB::table('PDF')
                    ->leftJoin('positions', 'PDF.position_id', '=', 'positions.id')
                    ->leftJoin('salary_grades', 'PDF.salarygrade_id', '=', 'salary_grades.id')
                    ->leftJoin('employees as emp', 'PDF.Employee_no', '=', 'emp.employee_no')
                    ->leftJoin('employees as sup', 'PDF.supervisor', '=', 'sup.employee_no')
                    ->select(
                        'PDF.*',
                        'positions.name as position_title',
                        'positions.description as position_description_from_position',
                        DB::raw('salary_grades.name as salary_grade_name'),
                        DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.first_name ELSE dbo.ufn_DecryptString(emp.first_name, '$app_key') END as emp_first"),
                        DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.middle_name ELSE dbo.ufn_DecryptString(emp.middle_name, '$app_key') END as emp_middle"),
                        DB::raw("CASE WHEN ISNULL(emp.is_encrypted,0) = 0 THEN emp.last_name ELSE dbo.ufn_DecryptString(emp.last_name, '$app_key') END as emp_last"),
                        DB::raw("CASE WHEN ISNULL(sup.is_encrypted,0) = 0 THEN sup.first_name ELSE dbo.ufn_DecryptString(sup.first_name, '$app_key') END as sup_first"),
                        DB::raw("CASE WHEN ISNULL(sup.is_encrypted,0) = 0 THEN sup.middle_name ELSE dbo.ufn_DecryptString(sup.middle_name, '$app_key') END as sup_middle"),
                        DB::raw("CASE WHEN ISNULL(sup.is_encrypted,0) = 0 THEN sup.last_name ELSE dbo.ufn_DecryptString(sup.last_name, '$app_key') END as sup_last")
                    )
                    ->where('PDF.id', $pdfId)
                    ->first();
                
                if (!$pdfRecord) {
                    return $this->errorResponse('PDF record not found', 404);
                }

                $sodarRecords = DB::table('SODAR')
                    ->leftJoin('Competency_level', 'SODAR.Competencylevel_id', '=', 'Competency_level.id')
                    ->where('SODAR.PDF_id', $pdfId)
                    ->select('SODAR.*', 'Competency_level.Level as competency_level')
                    ->orderBy('SODAR.id')
                    ->get();
                $coreCompetencies = DB::table('PDF_Corecompetencies')
                    ->leftJoin('Competency_level', 'PDF_Corecompetencies.CompetencyLevel_id', '=', 'Competency_level.id')
                    ->where('PDF_Corecompetencies.PDF_id', $pdfId)
                    ->select('PDF_Corecompetencies.*', 'Competency_level.Level as competency_level')
                    ->orderBy('PDF_Corecompetencies.id')
                    ->get();
                $leadershipCompetencies = DB::table('PDF_LeadershipCompetencies')
                    ->leftJoin('Competency_level', 'PDF_LeadershipCompetencies.CompetencyLevel_id', '=', 'Competency_level.id')
                    ->where('PDF_LeadershipCompetencies.PDF_id', $pdfId)
                    ->select('PDF_LeadershipCompetencies.*', 'Competency_level.Level as competency_level')
                    ->orderBy('PDF_LeadershipCompetencies.id')
                    ->get();

                $employeeFullname = trim(implode(' ', array_filter([
                    $pdfRecord->emp_first ?? '',
                    $pdfRecord->emp_middle ?? '',
                    $pdfRecord->emp_last ?? '',
                ])));

                $supervisorFullname = trim(implode(' ', array_filter([
                    $pdfRecord->sup_first ?? '',
                    $pdfRecord->sup_middle ?? '',
                    $pdfRecord->sup_last ?? '',
                ])));

                $hasEmployeeDateOverride = $request->exists('employee_date');
                $hasSupervisorDateOverride = $request->exists('supervisor_date');
                $employeeDateInput = $request->input('employee_date');
                $supervisorDateInput = $request->input('supervisor_date');

                $employeeDate = $hasEmployeeDateOverride
                    ? (!empty($employeeDateInput) ? date('F d, Y', strtotime($employeeDateInput)) : '')
                    : (!empty($pdfRecord->employee_date) ? date('F d, Y', strtotime($pdfRecord->employee_date)) : date('F d, Y'));
                $supervisorDate = $hasSupervisorDateOverride
                    ? (!empty($supervisorDateInput) ? date('F d, Y', strtotime($supervisorDateInput)) : '')
                    : (!empty($pdfRecord->supervisor_date) ? date('F d, Y', strtotime($pdfRecord->supervisor_date)) : date('F d, Y'));

                $positionData = [
                    'position_title' => $pdfRecord->position_title ?? '',
                    'item_number' => $pdfRecord->item_number ?? '',
                    'salary_grade' => $pdfRecord->salary_grade_name ?? '',
                    'authorized_salary' => $pdfRecord->salary ?? '',
                    'other_compensation' => $pdfRecord->other_compensation ?? '',
                    'machines_tools' => $pdfRecord->equiptment ?? 'Computer/Laptop, Printer, Telephone, External Hard Drive, Switches, UTP Cable, Television, Headset and Microphone',
                    'unit_function' => $pdfRecord->unit_description ?? '',
                    'job_summary' => $pdfRecord->position_description ?? '',
                    'education' => $pdfRecord->education ?? '',
                    'experience' => $pdfRecord->experience ?? '',
                    'training' => $pdfRecord->training ?? '',
                    'eligibility' => $pdfRecord->eigibility ?? '',
                    'employee_name' => $employeeFullname,
                    'supervisor_name' => $supervisorFullname,
                    'employee_date' => $employeeDate,
                    'supervisor_date' => $supervisorDate,
                ];

                // Map SODAR records
                $index = 1;
                foreach ($sodarRecords as $sr) {
                    if ($index > 20) break;
                    $raw = is_null($sr->Percetage) ? '0' : (string)$sr->Percetage;
                    $num = (float)str_replace('%', '', trim($raw));
                    if (strpos($raw, '%') === false && $num > 0 && $num <= 1) {
                        $num = $num * 100;
                    }
                    if ($num <= 0) continue;
                    $formattedPercent = fmod($num, 1.0) > 0
                        ? rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.')
                        : number_format($num, 0, '.', '');
                    $positionData['duty_' . $index . '_percentage'] = $formattedPercent . '%';
                    $positionData['duty_' . $index . '_description'] = $sr->Responsibilities ?? '';
                    $positionData['duty_' . $index . '_level'] = $sr->competency_level ?? '';
                    $index++;
                }

                if ($coreCompetencies->count() > 0) {
                    $positionData['core_competencies'] = implode("\n", $coreCompetencies->pluck('Competency')->filter()->all());
                    $coreLevels = $coreCompetencies->pluck('competency_level')->filter()->unique()->values()->all();
                    $positionData['core_level'] = count($coreLevels) ? implode("\n", $coreLevels) : 'INTERMEDIATE';
                } else {
                    $positionData['core_competencies'] = "Technologically Savvy\nEffective Communication\nCustomer Focus\nResults Driven\nTeam Player\nKnowledge Management";
                    $positionData['core_level'] = 'INTERMEDIATE';
                }

                if ($leadershipCompetencies->count() > 0) {
                    $positionData['leadership_competencies'] = implode("\n", $leadershipCompetencies->map(function($lc){ return $lc->COmpetency ?? $lc->Competency; })->filter()->all());
                    $levels = $leadershipCompetencies->pluck('competency_level')->filter()->unique()->values()->all();
                    $positionData['leadership_level'] = count($levels) ? implode("\n", $levels) : 'INTERMEDIATE';
                } else {
                    $positionData['leadership_competencies'] = "Building collaborative, inclusive working relationships\nManaging performance and coaching results\nLeading change\nThinking strategically and creatively\nCreating and nurturing a high performing organization";
                    $positionData['leadership_level'] = 'INTERMEDIATE';
                }

                // Handle supervisor position titles (sections 13 & 14) for DOCX
                // Check request first, then database, then defaults
                $immediateSupervisorPositionId = $request->input('immediate_supervisor_position_id') ?? $pdfRecord->immediate_supervisor_position_id ?? null;
                $nextHigherSupervisorPositionId = $request->input('next_higher_supervisor_position_id') ?? $pdfRecord->next_higher_supervisor_position_id ?? null;

                if ($immediateSupervisorPositionId) {
                    $immediatePosition = DB::table('positions')->where('id', $immediateSupervisorPositionId)->first();
                    $positionData['immediate_supervisor_position_title'] = $immediatePosition->name ?? '';
                } else {
                    // Default hardcoded values if not provided
                    $positionData['immediate_supervisor_position_title'] = "SUPERVISING\nTRADE-INDUSTRY\nDEVELOPMENT SPECIALIST\n\nCHIEF TRADE-INDUSTRY\nDEVELOPMENT SPECIALIST";
                }

                if ($nextHigherSupervisorPositionId) {
                    $nextHigherPosition = DB::table('positions')->where('id', $nextHigherSupervisorPositionId)->first();
                    $positionData['next_higher_supervisor_position_title'] = $nextHigherPosition->name ?? '';
                } else {
                    // Default hardcoded values if not provided
                    $positionData['next_higher_supervisor_position_title'] = "DEPUTY EXECUTIVE DIRECTOR\n\nEXECUTIVE DIRECTOR";
                }
            } else {
                $positionData = $request->all();
            if (isset($positionData['position']) && $positionData['position']) {
                $position = Position::find($positionData['position']);
                if ($position) {
                    $positionData['position_title'] = $position->name;
                    $positionData['item_number'] = $position->code;
                }
            }

            // Handle supervisor position titles (sections 13 & 14) for DOCX fallback
            $immediateSupervisorPositionId = $request->input('immediate_supervisor_position_id');
            $nextHigherSupervisorPositionId = $request->input('next_higher_supervisor_position_id');

            if ($immediateSupervisorPositionId) {
                $immediatePosition = DB::table('positions')->where('id', $immediateSupervisorPositionId)->first();
                $positionData['immediate_supervisor_position_title'] = $immediatePosition->name ?? '';
            } else {
                // Default hardcoded values if not provided
                $positionData['immediate_supervisor_position_title'] = "SUPERVISING\nTRADE-INDUSTRY\nDEVELOPMENT SPECIALIST\n\nCHIEF TRADE-INDUSTRY\nDEVELOPMENT SPECIALIST";
            }

            if ($nextHigherSupervisorPositionId) {
                $nextHigherPosition = DB::table('positions')->where('id', $nextHigherSupervisorPositionId)->first();
                $positionData['next_higher_supervisor_position_title'] = $nextHigherPosition->name ?? '';
            } else {
                // Default hardcoded values if not provided
                $positionData['next_higher_supervisor_position_title'] = "DEPUTY EXECUTIVE DIRECTOR\n\nEXECUTIVE DIRECTOR";
            }

            $positionData = array_merge([
                'position_title' => '',
                'item_number' => '',
                'salary_grade' => '',
                    'authorized_salary' => '',
                    'other_compensation' => '',
                    'machines_tools' => 'Computer/Laptop, Printer, Telephone, External Hard Drive, Switches, UTP Cable, Television, Headset and Microphone',
                    'unit_function' => '',
                    'job_summary' => '',
                'education' => '',
                'experience' => '',
                'training' => '',
                'eligibility' => '',
                    'core_competencies' => "Technologically Savvy\nEffective Communication\nCustomer Focus\nResults Driven\nTeam Player\nKnowledge Management",
                    'core_level' => 'INTERMEDIATE',
                    'leadership_competencies' => "Building collaborative, inclusive working relationships\nManaging performance and coaching results\nLeading change\nThinking strategically and creatively\nCreating and nurturing a high performing organization",
                    'leadership_level' => 'INTERMEDIATE',
                    'employee_name' => '',
                    'supervisor_name' => '',
                    'employee_date' => date('F d, Y'),
                    'supervisor_date' => date('F d, Y'),
            ], $positionData);
            }

            // Generate DOCX
            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(9); // Minimized font size

            $section = $phpWord->addSection([
                'marginTop' => Converter::inchToTwip(0.47),
                'marginRight' => Converter::inchToTwip(0.47),
                'marginBottom' => Converter::inchToTwip(0.47),
                'marginLeft' => Converter::inchToTwip(0.47),
                'pageSizeW' => Converter::inchToTwip(8.27),
                'pageSizeH' => Converter::inchToTwip(11.69),
            ]);

            // Paragraph styles with first line indentation
            $phpWord->addParagraphStyle('indent', [
                'indentation' => ['firstLine' => Converter::inchToTwip(0.2)],
                'spaceAfter' => 0,
            ]);

            // Header table (1. Position Title)
            $headerTable = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.04),
            ]);
            $headerTable->addRow();
            $leftCell = $headerTable->addCell(Converter::inchToTwip(3.3));
            $leftCell->addText('Republic of the Philippines', ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $leftCell->addText('POSITION DESCRIPTION FORM', ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $leftCell->addText('DBM-CSC Form No. 1', ['size' => 8, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $leftCell->addText('(Revised version No. 1, 2017)', ['size' => 8], ['alignment' => WordJc::CENTER]);
            
            $rightCell = $headerTable->addCell(Converter::inchToTwip(4.5));
            $rightCell->addText('1. POSITION TITLE (as approved by authorized agency) with parenthetical title', ['size' => 9, 'bold' => true]);
            $rightCell->addText($positionData['position_title'] ?? '', ['size' => 9], ['alignment' => WordJc::CENTER]);

            // 2-3 Item Number and Salary Grade
            $section->addTextBreak(0.2);
            $table23 = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.08),
            ]);
            $table23->addRow();
            $table23->addCell(Converter::inchToTwip(3.9))->addText('2. ITEM NUMBER', ['size' => 9, 'bold' => true]);
            $table23->addCell(Converter::inchToTwip(3.9))->addText('3. SALARY GRADE', ['size' => 9, 'bold' => true]);
            $table23->addRow();
            $table23->addCell()->addText($positionData['item_number'] ?? '', ['size' => 9], ['alignment' => WordJc::CENTER]);
            $table23->addCell()->addText($positionData['salary_grade'] ?? '', ['size' => 9], ['alignment' => WordJc::CENTER]);

            // 4. Local Government
            $section->addTextBreak(0.2);
            $table4 = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.04),
            ]);
            $table4->addRow();
            $table4->addCell(null, ['gridSpan' => 3])->addText('4. FOR LOCAL GOVERNMENT POSITION, ENUMERATE GOVERNMENTAL UNIT AND CLASS', ['size' => 9, 'bold' => true]);
            $table4->addRow();
            $table4->addCell(Converter::inchToTwip(2.6))->addText("Province\nCity\nMunicipality", ['size' => 9], ['alignment' => WordJc::CENTER]);
            $table4->addCell(Converter::inchToTwip(2.6))->addText("1st Class\n2nd Class\n3rd Class\n4th Class", ['size' => 9], ['alignment' => WordJc::CENTER]);
            $table4->addCell(Converter::inchToTwip(2.6))->addText("5th Class\n6th Class\nSpecial", ['size' => 9], ['alignment' => WordJc::CENTER]);

            // 5-8 Organization Info
            $section->addTextBreak(0.2);
            $table58 = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.04),
            ]);
            $table58->addRow();
            $table58->addCell(Converter::inchToTwip(3.9))->addText('5. DEPARTMENT, CORPORATION OR AGENCY/ LOCAL GOVERNMENT', ['size' => 9, 'bold' => true]);
            $table58->addCell(Converter::inchToTwip(3.9))->addText('6. BUREAU OR OFFICE', ['size' => 9, 'bold' => true]);
            $table58->addRow();
            $table58->addCell()->addText('DEPARTMENT OF TRADE AND INDUSTRY', ['size' => 9], ['alignment' => WordJc::CENTER]);
            $table58->addCell()->addText(strtoupper(CompanyHelper::getName()), ['size' => 9], ['alignment' => WordJc::CENTER]);
            $table58->addRow();
            $table58->addCell()->addText('7. DEPARTMENT / BRANCH / DIVISION', ['size' => 9, 'bold' => true]);
            $table58->addCell()->addText('8. WORKSTATION / PLACE OF WORK', ['size' => 9, 'bold' => true]);
            $table58->addRow();
            $table58->addCell()->addText('Office of the Executive Director (OED)', ['size' => 9], ['alignment' => WordJc::CENTER]);
            $table58->addCell()->addText(strtoupper(CompanyHelper::getAddress()), ['size' => 9], ['alignment' => WordJc::CENTER]);

            // 9-12 Appropriation
            $section->addTextBreak(0.2);
            $table912 = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.04),
            ]);
            $table912->addRow();
            $table912->addCell(Converter::inchToTwip(1.95))->addText('9. PRESENT APPROP ACT', ['size' => 9, 'bold' => true]);
            $table912->addCell(Converter::inchToTwip(1.95))->addText('10. PREVIOUS APPROP ACT', ['size' => 9, 'bold' => true]);
            $table912->addCell(Converter::inchToTwip(1.95))->addText('11. SALARY AUTHORIZED', ['size' => 9, 'bold' => true]);
            $table912->addCell(Converter::inchToTwip(1.95))->addText('12. OTHER COMPENSATION', ['size' => 9, 'bold' => true]);
            $table912->addRow();
            $table912->addCell()->addText('', ['size' => 9], ['alignment' => WordJc::CENTER]);
            $table912->addCell()->addText('', ['size' => 9], ['alignment' => WordJc::CENTER]);
            $table912->addCell()->addText($positionData['authorized_salary'] ?? '', ['size' => 9], ['alignment' => WordJc::CENTER]);
            $table912->addCell()->addText($positionData['other_compensation'] ?? '', ['size' => 9], ['alignment' => WordJc::CENTER]);

            // 13-14 Supervisor
            $section->addTextBreak(0.2);
            $table1314 = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.04),
            ]);
            $table1314->addRow();
            $table1314->addCell(Converter::inchToTwip(3.9))->addText('13. POSITION TITLE OF IMMEDIATE SUPERVISOR', ['size' => 9, 'bold' => true]);
            $table1314->addCell(Converter::inchToTwip(3.9))->addText('14. POSITION TITLE OF NEXT HIGHER SUPERVISOR', ['size' => 9, 'bold' => true]);
            $table1314->addRow();
            $immediateSupervisorText = $positionData['immediate_supervisor_position_title'] ?? "SUPERVISING\nTRADE-INDUSTRY\nDEVELOPMENT SPECIALIST\n\nCHIEF TRADE-INDUSTRY\nDEVELOPMENT SPECIALIST";
            $nextHigherSupervisorText = $positionData['next_higher_supervisor_position_title'] ?? "DEPUTY EXECUTIVE DIRECTOR\n\nEXECUTIVE DIRECTOR";
            $table1314->addCell()->addText($immediateSupervisorText, ['size' => 9], ['alignment' => WordJc::CENTER]);
            $table1314->addCell()->addText($nextHigherSupervisorText, ['size' => 9], ['alignment' => WordJc::CENTER]);

            // 15. Supervised
            $section->addTextBreak(0.2);
            $table15 = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.04),
            ]);
            $table15->addRow();
            $table15->addCell(null, ['gridSpan' => 2])->addText('15. POSITION TITLE, AND ITEM OF THOSE DIRECTLY SUPERVISED', ['size' => 9, 'bold' => true]);
            $table15->addRow();
            $table15->addCell(Converter::inchToTwip(3.9))->addText('POSITION TITLE', ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $table15->addCell(Converter::inchToTwip(3.9))->addText('ITEM NUMBER', ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $table15->addRow();
            $table15->addCell()->addText('N/A', ['size' => 9], ['alignment' => WordJc::CENTER]);
            $table15->addCell()->addText('N/A', ['size' => 9], ['alignment' => WordJc::CENTER]);

            // 16. Machines
            $section->addTextBreak(0.2);
            $table16 = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.04),
            ]);
            $table16->addRow();
            $table16->addCell()->addText('16. MACHINE, EQUIPMENT, TOOLS, ETC., USED REGULARLY IN PERFORMANCE OF WORK', ['size' => 9, 'bold' => true]);
            $table16->addRow();
            $table16->addCell()->addText($positionData['machines_tools'] ?? '', ['size' => 9], ['alignment' => WordJc::CENTER]);

            // 17. Contacts
            $section->addTextBreak(0.2);
            $table17 = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.04),
            ]);
            $table17->addRow();
            $table17->addCell(null, ['gridSpan' => 7])->addText('17. CONTACTS / CLIENTS / STAKEHOLDERS', ['size' => 9, 'bold' => true]);
            $table17->addRow();
            $table17->addCell(Converter::inchToTwip(1.1))->addText('17a. Internal', ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $table17->addCell(Converter::inchToTwip(1.1))->addText('Occasional', ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $table17->addCell(Converter::inchToTwip(1.1))->addText('Frequent', ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $table17->addCell(Converter::inchToTwip(1.1))->addText('17b. External', ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $table17->addCell(Converter::inchToTwip(1.1))->addText('Occasional', ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $table17->addCell(Converter::inchToTwip(1.1))->addText('Frequent', ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $table17->addCell(Converter::inchToTwip(0.75))->addText('', ['size' => 9]);
            $internal = ['Executive / Managerial','Supervisors','Non-Supervisors','Staff'];
            $external = ['General Public','Other Agencies','Others (Please Specify):'];
            $rows = max(count($internal), count($external));
            for($i=0;$i<$rows;$i++) {
                $table17->addRow();
                $table17->addCell()->addText($internal[$i] ?? '', ['size' => 9], ['alignment' => WordJc::CENTER]);
                $table17->addCell()->addText('', ['size' => 9]);
                $table17->addCell()->addText('', ['size' => 9]);
                $table17->addCell()->addText($external[$i] ?? '', ['size' => 9], ['alignment' => WordJc::CENTER]);
                $table17->addCell()->addText('', ['size' => 9]);
                $table17->addCell()->addText('', ['size' => 9]);
                $table17->addCell()->addText('', ['size' => 9]);
            }

            // 18. Working Condition
            $section->addTextBreak(0.2);
            $table18 = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.04),
            ]);
            $table18->addRow();
            $table18->addCell(null, ['gridSpan' => 2])->addText('18. WORKING CONDITION', ['size' => 9, 'bold' => true]);
            $table18->addRow();
            $table18->addCell(Converter::inchToTwip(3.9))->addText('Office Work', ['size' => 9], ['alignment' => WordJc::CENTER]);
            $table18->addCell(Converter::inchToTwip(3.9))->addText('Other/s (Please Specify)', ['size' => 9], ['alignment' => WordJc::CENTER]);
            $table18->addRow();
            $table18->addCell()->addText('Field Work', ['size' => 9], ['alignment' => WordJc::CENTER]);
            $table18->addCell()->addText('', ['size' => 9]);

            // 19. Unit Function
            $section->addTextBreak(0.2);
            $table19 = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.04),
            ]);
            $table19->addRow();
            $table19->addCell()->addText('19. BRIEF DESCRIPTION OF THE GENERAL FUNCTION OF THE UNIT OR SECTION', ['size' => 9, 'bold' => true]);
            $table19->addRow();
            $unitCell = $table19->addCell();
            $unitText = $positionData['unit_function'] ?? '';
            if (!empty($unitText)) {
                $lines = explode("\n", $unitText);
                foreach ($lines as $line) {
                    if (!empty(trim($line))) {
                        $unitCell->addText(trim($line), ['size' => 9], 'indent');
                    }
                }
            }

            // 20. Job Summary
            $section->addTextBreak(0.2);
            $table20 = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.04),
            ]);
            $table20->addRow();
            $table20->addCell()->addText('20. BRIEF DESCRIPTION OF THE GENERAL FUNCTION OF THE POSITION (Job Summary)', ['size' => 9, 'bold' => true]);
            $table20->addRow();
            $summaryCell = $table20->addCell();
            $summaryText = $positionData['job_summary'] ?? '';
            if (!empty($summaryText)) {
                $lines = explode("\n", $summaryText);
                foreach ($lines as $line) {
                    if (!empty(trim($line))) {
                        $summaryCell->addText(trim($line), ['size' => 9], 'indent');
                    }
                }
            }

            // 21. Qualification Standards
            $section->addTextBreak(0.2);
            $table21a = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.04),
            ]);
            $table21a->addRow();
            $table21a->addCell()->addText('21. QUALIFICATION STANDARDS', ['size' => 9, 'bold' => true]);
            
            $section->addTextBreak(0.2);
            $table21b = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.04),
            ]);
            $table21b->addRow();
            $table21b->addCell(Converter::inchToTwip(1.95))->addText('21a. Education', ['size' => 9, 'bold' => true]);
            $table21b->addCell(Converter::inchToTwip(1.95))->addText('21b. Experience', ['size' => 9, 'bold' => true]);
            $table21b->addCell(Converter::inchToTwip(1.95))->addText('21c. Training', ['size' => 9, 'bold' => true]);
            $table21b->addCell(Converter::inchToTwip(1.95))->addText('21d. Eligibility', ['size' => 9, 'bold' => true]);
            $table21b->addRow();
            $eduCell = $table21b->addCell();
            $eduText = $positionData['education'] ?? '';
            if (!empty($eduText)) {
                $lines = explode("\n", $eduText);
                foreach ($lines as $line) {
                    if (!empty(trim($line))) {
                        $eduCell->addText(trim($line), ['size' => 9], 'indent');
                    }
                }
            }
            $expCell = $table21b->addCell();
            $expText = $positionData['experience'] ?? '';
            if (!empty($expText)) {
                $lines = explode("\n", $expText);
                foreach ($lines as $line) {
                    if (!empty(trim($line))) {
                        $expCell->addText(trim($line), ['size' => 9], 'indent');
                    }
                }
            }
            $trainCell = $table21b->addCell();
            $trainText = $positionData['training'] ?? '';
            if (!empty($trainText)) {
                $lines = explode("\n", $trainText);
                foreach ($lines as $line) {
                    if (!empty(trim($line))) {
                        $trainCell->addText(trim($line), ['size' => 9], 'indent');
                    }
                }
            }
            $eligCell = $table21b->addCell();
            $eligText = $positionData['eligibility'] ?? '';
            if (!empty($eligText)) {
                $lines = explode("\n", $eligText);
                foreach ($lines as $line) {
                    if (!empty(trim($line))) {
                        $eligCell->addText(trim($line), ['size' => 9], 'indent');
                    }
                }
            }

            // 21e. Core Competencies
            $table21b->addRow();
            $table21b->addCell(null, ['gridSpan' => 4])->addText('21e. Core Competencies', ['size' => 9, 'bold' => true]);
            $table21b->addRow();
            $coreCell = $table21b->addCell(null, ['gridSpan' => 3]);
            $coreText = $positionData['core_competencies'] ?? '';
            if (!empty($coreText)) {
                $lines = explode("\n", $coreText);
                foreach ($lines as $line) {
                    if (!empty(trim($line))) {
                        $coreCell->addText(trim($line), ['size' => 9], 'indent');
                    }
                }
            }
            $table21b->addCell(Converter::inchToTwip(1.95))->addText("Competency Level\n\n" . ($positionData['core_level'] ?? 'INTERMEDIATE'), ['size' => 9], ['alignment' => WordJc::CENTER]);

            // 21f. Leadership Competencies
            $table21b->addRow();
            $table21b->addCell(null, ['gridSpan' => 4])->addText('21f. Leadership Competencies', ['size' => 9, 'bold' => true]);
            $table21b->addRow();
            $leadCell = $table21b->addCell(null, ['gridSpan' => 3]);
            $leadText = $positionData['leadership_competencies'] ?? '';
            if (!empty($leadText)) {
                $lines = explode("\n", $leadText);
                foreach ($lines as $line) {
                    if (!empty(trim($line))) {
                        $leadCell->addText(trim($line), ['size' => 9], 'indent');
                    }
                }
            }
            $table21b->addCell(Converter::inchToTwip(1.95))->addText("Competency Level\n\n" . ($positionData['leadership_level'] ?? 'INTERMEDIATE'), ['size' => 9], ['alignment' => WordJc::CENTER]);

            // 22. Duties and Responsibilities
            $section->addTextBreak(0.2);
            $table22 = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.04),
            ]);
            $table22->addRow();
            $table22->addCell(Converter::inchToTwip(1.56))->addText('22. Percentage of Working Time', ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $table22->addCell(Converter::inchToTwip(4.68))->addText('STATEMENT OF DUTIES AND RESPONSIBILITIES (Technical Competencies)', ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $table22->addCell(Converter::inchToTwip(1.56))->addText('Competency Level', ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);
            
            for($i=1;$i<=20;$i++) {
                $desc = $positionData['duty_' . $i . '_description'] ?? '';
                if (!empty($desc)) {
                    $table22->addRow();
                    $table22->addCell()->addText($positionData['duty_' . $i . '_percentage'] ?? '', ['size' => 9], ['alignment' => WordJc::CENTER]);
                    $dutyCell = $table22->addCell();
                    $lines = explode("\n", $desc);
                    foreach ($lines as $line) {
                        if (!empty(trim($line))) {
                            $dutyCell->addText(trim($line), ['size' => 9], 'indent');
                        }
                    }
                    $table22->addCell()->addText($positionData['duty_' . $i . '_level'] ?? '', ['size' => 9], ['alignment' => WordJc::CENTER]);
                }
            }

            // 23. Acknowledgment
            $section->addTextBreak(0.2);
            $table23 = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.04),
            ]);
            $table23->addRow();
            $table23->addCell()->addText('23. ACKNOWLEDGMENT AND ACCEPTANCE', ['size' => 9, 'bold' => true]);
            $table23->addRow();
            $ackCell = $table23->addCell();
            $ackCell->addText('I have received a copy of this position description. It has been discussed with me and I have freely chosen to comply with the performance and behavioral/values expectations contained herein.', ['size' => 9], 'indent');
            
            $section->addTextBreak(0.3);
            $signTable = $section->addTable([
                'borderSize' => 0,
                'cellMargin' => 0,
            ]);
            $signTable->addRow();
            $empSignCell = $signTable->addCell(Converter::inchToTwip(2.6));
            $empSignCell->addText($positionData['employee_name'] ?? '', ['size' => 8, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $empSignCell->addText('________________________', ['size' => 8], ['alignment' => WordJc::CENTER]);
            $empSignCell->addText($positionData['employee_date'] ?? '', ['size' => 8], ['alignment' => WordJc::CENTER]);
            $empSignCell->addText("Employee's Name, Date and Signature", ['size' => 8], ['alignment' => WordJc::CENTER]);
            
            $signTable->addCell(Converter::inchToTwip(1.3));
            
            $supSignCell = $signTable->addCell(Converter::inchToTwip(2.6));
            $supSignCell->addText($positionData['supervisor_name'] ?? '', ['size' => 8, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $supSignCell->addText('________________________', ['size' => 8], ['alignment' => WordJc::CENTER]);
            $supSignCell->addText($positionData['supervisor_date'] ?? '', ['size' => 8], ['alignment' => WordJc::CENTER]);
            $supSignCell->addText("Supervisor's Name, Date and Signature", ['size' => 8], ['alignment' => WordJc::CENTER]);

            $filename = 'position_description_' . date('Y-m-d_His') . '.docx';
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate DOCX: ' . $e->getMessage());
        }
    }
}
