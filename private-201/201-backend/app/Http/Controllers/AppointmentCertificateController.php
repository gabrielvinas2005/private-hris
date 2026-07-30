<?php

namespace App\Http\Controllers;

use App\Helpers\CompanyHelper;
use PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use \NumberFormatter;
use Svg\Tag\Rect;
use App\Traits\ApiResponse;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc as WordJc;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AppointmentCertificateController extends Controller
{
    use ApiResponse;

    private function resolveAppliedEmploymentType($positionAppliedId, $isPlantilla, $fallback = '')
    {
        $fallback = trim((string) $fallback);
        if ($fallback !== '') {
            return $fallback;
        }

        if (!$positionAppliedId) {
            return '';
        }

        $typeId = null;
        if ((int) $isPlantilla === 1) {
            // Source of truth for plantilla employment type
            $typeId = DB::table('plantillas')
                ->where('id', (int) $positionAppliedId)
                ->value('employment_type_Id');
        } else {
            $typeId = DB::table('non_plantillas')->where('id', (int) $positionAppliedId)->value('employment_type_Id');
        }

        if (!$typeId) {
            return '';
        }

        return (string) (DB::table('employment_types')->where('id', (int) $typeId)->value('name') ?? '');
    }

    /**
     * Hiring typically copies applicant_headers.applicant_no into employees.employee_no; employee_no on the header may stay empty.
     */
    private function appointmentCertificateLinkedEmployeeIdFromApplicantHeaderId(int $applicantHeaderId): ?int
    {
        $header = DB::table('applicant_headers')
            ->select('applicant_no', 'employee_no')
            ->where('id', $applicantHeaderId)
            ->first();
        if (!$header) {
            return null;
        }
        $applicantNo = trim((string) ($header->applicant_no ?? ''));
        $employeeNo = trim((string) ($header->employee_no ?? ''));
        if ($applicantNo === '' && $employeeNo === '') {
            return null;
        }

        $id = DB::table('employees')
            ->where('active', true)
            ->where('is_employee', true)
            ->where(function ($q) use ($applicantNo, $employeeNo) {
                if ($applicantNo !== '') {
                    $q->where('employee_no', $applicantNo);
                }
                if ($employeeNo !== '' && strcasecmp($employeeNo, $applicantNo) !== 0) {
                    $q->orWhere('employee_no', $employeeNo);
                }
            })
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * Matches frontend employeeAppointmentDisplayName(): text before first " - ".
     */
    private function appointmentCertificateDropdownDisplayHead(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }
        $sep = ' - ';
        $i = strpos($name, $sep);
        $head = $i === false ? $name : substr($name, 0, $i);

        return strtolower(preg_replace('/\s+/u', ' ', trim($head)));
    }

    /**
     * When multiple rows show the same appointee label, keep the best candidate (prefer employee id over applicant).
     *
     * @param  iterable<int, object>  $rows
     * @return array<int, object>
     */
    private function dedupeAppointmentCertificateDropdownRows(iterable $rows): array
    {
        $best = [];
        foreach ($rows as $row) {
            $head = $this->appointmentCertificateDropdownDisplayHead($row->name ?? null);
            $key = $head !== '' ? $head : ('id:' . ($row->id ?? ''));
            $id = (int) ($row->id ?? 0);
            $score = ($id > 0 ? 1_000_000 : 0)
                + min(strlen((string) ($row->name ?? '')), 50_000)
                + (trim((string) ($row->employee_no ?? '')) !== '' ? 100 : 0);

            if (!isset($best[$key]) || $score > $best[$key]['score']) {
                $best[$key] = ['row' => $row, 'score' => $score];
            }
        }

        $out = array_column($best, 'row');
        usort($out, static function ($a, $b) {
            return strcmp((string) ($a->name ?? ''), (string) ($b->name ?? ''));
        });

        return $out;
    }

    /**
     * Appointment rows for an employee or promotion id (same query for PDF and Word).
     */
    private function appointmentCertificateAppointmentRowsFromEmployeeOrPromotion(int $employeeId): Collection
    {
        $app_key = env('APP_KEY', '');

        return DB::table('employees as b')
            ->leftJoin('employee_promotions as a', 'a.employee_id', '=', 'b.id')
            ->join('positions as c', 'b.position_id', '=', 'c.id')
            ->leftJoin('departments as d', 'b.department_id', '=', 'd.id')
            ->leftJoin('genders as e', 'b.gender_id', '=', 'e.id')
            ->leftJoin('name_prefixes as f', 'b.name_prefix_id', '=', 'f.id')
            ->leftJoin('promotion_natures as g', 'g.id', '=', 'a.nature_of_appointment_id')
            ->leftJoin('employment_types as h', 'h.id', '=', 'b.employment_type_id')
            ->leftJoin('plantillas as j', 'b.id', '=', 'j.employee_id')
            ->select(
                'a.id',
                DB::raw("CASE WHEN ISNULL(b.is_encrypted,0) = 0 THEN
                                CONCAT(b.first_name,' ',b.last_name)
                            ELSE
                                RTRIM([dbo].[ufn_DecryptString](b.first_name,'$app_key'))+' '+RTRIM([dbo].[ufn_DecryptString](b.last_name,'$app_key'))
                            END as name"),
                'c.name as position',
                'd.name as department',
                'b.ra_region',
                'b.ra_province',
                'b.ra_city',
                'b.ra_barangay',
                'b.pa_region',
                'b.pa_province',
                'b.pa_city',
                'b.pa_barangay',
                'b.salary_grade_id',
                'b.salary_step_id',
                'b.salary',
                DB::raw("COALESCE(g.name, 'Original') as nature"),
                'h.name as employment_type',
                'j.code',
                'j.employment_type_Id'
            )
            ->where(function ($query) use ($employeeId) {
                $query->where('b.id', $employeeId)
                    ->orWhere('a.id', $employeeId);
            })
            ->get();
    }

    protected function loadJsonRecords(string $filename, string $recordsKey = 'RECORDS'): array
    {
        $candidates = [
            base_path($filename),
            resource_path($filename),
            resource_path('data/' . $filename),
            public_path($filename),
            base_path('public/' . $filename),
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                $json = json_decode(file_get_contents($path), true);
                return is_array($json) && isset($json[$recordsKey]) ? $json[$recordsKey] : ($json ?? []);
            }
        }

        throw new \RuntimeException('Reference JSON not found: ' . $filename);
    }

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
            $app_key = env("APP_KEY", "");

            $employees = DB::table('employees')
                ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
                ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
                ->join('positions', 'positions.id', '=', 'employees.position_id')
                // One promotion row per employee (otherwise the UI shows the same person twice when
                // employeeAppointmentDisplayName() only shows the substring before the first " - ").
                ->leftJoin('employee_promotions', function ($join) {
                    $join->on('employees.id', '=', 'employee_promotions.employee_id')
                        ->whereRaw('employee_promotions.id = (
                            SELECT TOP 1 ep2.id
                            FROM employee_promotions ep2
                            WHERE ep2.employee_id = employees.id
                            ORDER BY CASE WHEN ep2.date_of_effectivity IS NULL THEN 1 ELSE 0 END,
                                     ep2.date_of_effectivity DESC,
                                     ep2.id DESC
                        )');
                })
                ->leftJoin('employment_types', 'employment_types.id', '=', 'employees.employment_type_id')
                ->leftJoin('promotion_natures', 'promotion_natures.id', '=', 'employee_promotions.nature_of_appointment_id')
                ->select(
                    'employees.photo',
                    DB::raw("CASE WHEN ISNULL(employee_promotions.id,0) = 0 THEN employees.id ELSE employee_promotions.id END as id"),
                    'employees.employee_no',
                    'employees.email',
                    'employment_types.name as employment_type',
                    'positions.name as position',
                    'departments.name as department',
                    'branches.name as branch',
                    DB::raw("CASE WHEN ISNULL(employees.is_encrypted,0) = 0 THEN
                                    CONCAT(
                                        employees.first_name,
                                        CASE
                                            WHEN NULLIF(LTRIM(RTRIM(ISNULL(employees.middle_name, ''))), '') IS NULL
                                                THEN ' '
                                            ELSE CONCAT(' ', SUBSTRING(employees.middle_name,1,1), '. ')
                                        END,
                                        employees.last_name,
                                        ' - ', ISNULL(positions.name, ''),
                                        ' - ', ISNULL(promotion_natures.name, 'Original'),
                                        ' - ', ISNULL(employee_promotions.date_of_effectivity, '')
                                    )
                                ELSE
                                    CONCAT(
                                        RTRIM(ISNULL([dbo].[ufn_DecryptString](employees.first_name,'$app_key'), '')),
                                        CASE
                                            WHEN NULLIF(LTRIM(RTRIM(ISNULL([dbo].[ufn_DecryptString](employees.middle_name,'$app_key'), ''))), '') IS NULL
                                                THEN ' '
                                            ELSE CONCAT(' ', UPPER(SUBSTRING([dbo].[ufn_DecryptString](employees.middle_name,'$app_key'),1,1)), '. ')
                                        END,
                                        RTRIM(ISNULL([dbo].[ufn_DecryptString](employees.last_name,'$app_key'), '')),
                                        ' - ', RTRIM(ISNULL(positions.name,'')),
                                        ' - ', RTRIM(ISNULL(promotion_natures.name,'Original')),
                                        ' - ', RTRIM(ISNULL(employee_promotions.date_of_effectivity,''))
                                    )
                                END as name"),
                )
                ->where([
                    'employees.active' => true,
                    'employees.is_employee' => true,
                ]);

            $applicants = DB::table('applicant_headers')
                ->leftJoin('applicant_details as ad', function ($join) {
                    $join->on('ad.applicant_id', '=', 'applicant_headers.id')
                        ->whereRaw("ad.id = (
                            SELECT TOP 1 ad2.id
                            FROM applicant_details as ad2
                            WHERE ad2.applicant_id = applicant_headers.id
                            ORDER BY CASE WHEN ISNULL(ad2.application_status_id, 0) IN (6,5) THEN 0 ELSE 1 END, ad2.id DESC
                        )");
                })
                ->leftJoin('plantillas as p', function ($join) {
                    $join->on('ad.position_applied_id', '=', 'p.id')
                        ->whereRaw('ISNULL(ad.is_plantilla, 1) = 1');
                })
                ->leftJoin('non_plantillas as np', function ($join) {
                    $join->on('ad.position_applied_id', '=', 'np.id')
                        ->whereRaw('ISNULL(ad.is_plantilla, 1) = 0');
                })
                ->leftJoin('positions as pos_p', 'p.position_id', '=', 'pos_p.id')
                ->leftJoin('positions as pos_np', 'np.position_id', '=', 'pos_np.id')
                ->leftJoin('departments as dep_p', 'p.department_id', '=', 'dep_p.id')
                ->leftJoin('departments as dep_np', 'np.department_id', '=', 'dep_np.id')
                ->leftJoin('employment_types as et_p', 'et_p.id', '=', 'p.employment_type_id')
                ->leftJoin('employment_types as et_np', 'et_np.id', '=', 'np.employee_type_id')
                ->leftJoin('employees as ae', function ($join) {
                    $join->where('ae.is_employee', true)
                        ->where('ae.active', true)
                        ->where(function ($q) {
                            $q->whereColumn('ae.employee_no', 'applicant_headers.employee_no')
                                ->orWhereColumn('ae.employee_no', 'applicant_headers.applicant_no');
                        });
                })
                // Do not list applicants who already have an active employee record (avoids duplicate names in dropdown).
                ->whereNull('ae.id')
                ->leftJoin('branches as br_ae', 'br_ae.id', '=', 'ae.branch_id')
                ->leftJoin('departments as dep_ae', 'dep_ae.id', '=', 'ae.department_id')
                ->leftJoin('positions as pos_ae', 'pos_ae.id', '=', 'ae.position_id')
                ->leftJoin('employment_types', 'employment_types.id', '=', 'ae.employment_type_id')
                ->select(
                    'applicant_headers.photo',
                    DB::raw('-applicant_headers.id as id'),
                    'applicant_headers.employee_no',
                    'applicant_headers.email',
                    DB::raw('COALESCE(et_p.name, et_np.name, employment_types.name) as employment_type'),
                    DB::raw('COALESCE(pos_np.name, pos_p.name, pos_ae.name) as position'),
                    DB::raw('COALESCE(dep_np.name, dep_p.name, dep_ae.name) as department'),
                    'br_ae.name as branch',
                    DB::raw("CONCAT(
                        applicant_headers.first_name,
                        CASE
                            WHEN NULLIF(LTRIM(RTRIM(ISNULL(applicant_headers.middle_name, ''))), '') IS NULL
                                THEN ' '
                            ELSE CONCAT(' ', SUBSTRING(applicant_headers.middle_name, 1, 1), '. ')
                        END,
                        applicant_headers.last_name,
                        ' - ', ISNULL(COALESCE(pos_np.name, pos_p.name, pos_ae.name), ''),
                        ' - Original'
                    ) as name"),
                );

            $data = $employees
                ->unionAll($applicants)
                ->orderBy('name', 'asc')
                ->get();

            $data = $this->dedupeAppointmentCertificateDropdownRows($data);

            return $this->successResponse($data, 'Appointment certificates retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve appointment certificates: ' . $e->getMessage());
        }
    }

    public function sendEmail(Request $request)
    {
        try {
            $employeeId = (int) $request->input('employee');
            if ($employeeId === 0) {
                return $this->errorResponse('Please select employee.', 400);
            }

            if ($employeeId < 0) {
                $recipient = DB::table('applicant_headers')
                    ->select(
                        'email',
                        DB::raw("CONCAT(first_name, ' ', last_name) as name")
                    )
                    ->where('id', abs($employeeId))
                    ->first();
            } else {
                $app_key = env("APP_KEY", "");
                $recipient = DB::table('employee_promotions as ep')
                    ->join('employees as e', 'e.id', '=', 'ep.employee_id')
                    ->select(
                        'e.email',
                        DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                    CONCAT(e.first_name, ' ', e.last_name)
                                ELSE
                                    CONCAT(
                                        RTRIM(ISNULL([dbo].[ufn_DecryptString](e.first_name,'$app_key'),'')),
                                        ' ',
                                        RTRIM(ISNULL([dbo].[ufn_DecryptString](e.last_name,'$app_key'),''))
                                    )
                                END as name")
                    )
                    ->where('ep.id', $employeeId)
                    ->first();

                if (!$recipient) {
                    $recipient = DB::table('employees as e')
                        ->select(
                            'e.email',
                            DB::raw("CASE WHEN ISNULL(e.is_encrypted,0) = 0 THEN
                                        CONCAT(e.first_name, ' ', e.last_name)
                                    ELSE
                                        CONCAT(
                                            RTRIM(ISNULL([dbo].[ufn_DecryptString](e.first_name,'$app_key'),'')),
                                            ' ',
                                            RTRIM(ISNULL([dbo].[ufn_DecryptString](e.last_name,'$app_key'),''))
                                        )
                                    END as name")
                        )
                        ->where('e.id', $employeeId)
                        ->first();
                }
            }

            if (!$recipient || empty($recipient->email)) {
                return $this->errorResponse('Selected employee/applicant has no email address.', 400);
            }

            $pdfResponse = $this->print(new Request($request->all()));
            $pdfContent = $pdfResponse->getContent();

            $pdfPath = storage_path('app/temp_appointment_' . abs($employeeId) . '_' . time() . '.pdf');
            file_put_contents($pdfPath, $pdfContent);

            $emailData = ['name' => trim($recipient->name ?? 'Applicant')];
            \Notification::route('mail', $recipient->email)
                ->notify(new \App\Notifications\EmailAppointmentCertificate($emailData, $pdfPath));

            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }

            return $this->successResponse(
                ['email' => $recipient->email],
                'Appointment certificate email sent successfully.'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to send appointment certificate email: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        try {
            if ($request->employee == null) {
                return $this->errorResponse('Please select employee.', 400);
            }

            $employeeId = (int) $request->employee;

            $appointments = $this->appointmentCertificateAppointmentRowsFromEmployeeOrPromotion($employeeId);

            // Applicant fallback: if selection is an applicant id, prefer linked employee row (same person as employees table).
            if ($appointments->isEmpty()) {
                $applicantId = $employeeId < 0 ? abs($employeeId) : $employeeId;
                $linkedEmployeeId = $this->appointmentCertificateLinkedEmployeeIdFromApplicantHeaderId($applicantId);
                if ($linkedEmployeeId !== null) {
                    $appointments = $this->appointmentCertificateAppointmentRowsFromEmployeeOrPromotion($linkedEmployeeId);
                }
            }

            if ($appointments->isEmpty()) {
                $applicantId = $employeeId < 0 ? abs($employeeId) : $employeeId;
                $appointments = DB::table('applicant_headers as ah')
                    ->leftJoin('applicant_details as ad', function ($join) {
                        $join->on('ad.applicant_id', '=', 'ah.id')
                            ->whereRaw("ad.id = (
                                SELECT TOP 1 ad2.id
                                FROM applicant_details as ad2
                                WHERE ad2.applicant_id = ah.id
                                ORDER BY CASE WHEN ISNULL(ad2.application_status_id, 0) IN (6,5) THEN 0 ELSE 1 END, ad2.id DESC
                            )");
                    })
                    ->leftJoin('plantillas as p', function ($join) {
                        $join->on('ad.position_applied_id', '=', 'p.id')
                            ->whereRaw('ISNULL(ad.is_plantilla, 1) = 1');
                    })
                    ->leftJoin('non_plantillas as np', function ($join) {
                        $join->on('ad.position_applied_id', '=', 'np.id')
                            ->whereRaw('ISNULL(ad.is_plantilla, 1) = 0');
                    })
                    ->leftJoin('positions as pos_p', 'p.position_id', '=', 'pos_p.id')
                    ->leftJoin('positions as pos_np', 'np.position_id', '=', 'pos_np.id')
                    ->leftJoin('departments as dep_p', 'p.department_id', '=', 'dep_p.id')
                    ->leftJoin('departments as dep_np', 'np.department_id', '=', 'dep_np.id')
                    ->leftJoin('employment_types as et_p', 'et_p.id', '=', 'p.employment_type_id')
                    ->leftJoin('employment_types as et_np', 'et_np.id', '=', 'np.employee_type_id')
                    ->leftJoin('salary_schedules as ss', function ($join) {
                        $join->where('ss.active', 1);
                    })
                    ->leftJoin('salary_schedules_details as ssd', function ($join) {
                        $join->on('ssd.salary_schedule_id', '=', 'ss.id')
                            ->on('ssd.salary_grade_id', '=', 'p.salary_grade_id')
                            ->on('ssd.salary_step_id', '=', 'p.salary_step_id');
                    })
                    ->select(
                        DB::raw('-ah.id as id'),
                        DB::raw("CONCAT(ah.first_name,' ',ah.last_name) as name"),
                        DB::raw("ISNULL(COALESCE(pos_np.name, pos_p.name), '') as position"),
                        DB::raw("ISNULL(COALESCE(dep_np.name, dep_p.name), '') as department"),
                        DB::raw("'' as ra_region"),
                        DB::raw("'' as ra_province"),
                        DB::raw("'' as ra_city"),
                        DB::raw("'' as ra_barangay"),
                        DB::raw("'' as pa_region"),
                        DB::raw("'' as pa_province"),
                        DB::raw("'' as pa_city"),
                        DB::raw("'' as pa_barangay"),
                        DB::raw("ISNULL(CAST(p.salary_grade_id as varchar(10)), '') as salary_grade_id"),
                        DB::raw("ISNULL(CAST(p.salary_step_id as varchar(10)), '') as salary_step_id"),
                        DB::raw("COALESCE(ssd.amount, np.salary, 0) as salary"),
                        DB::raw("'Original' as nature"),
                        DB::raw("ISNULL(COALESCE(et_p.name, et_np.name), '') as employment_type"),
                        DB::raw("ISNULL(p.code, '') as code"),
                        'ad.position_applied_id',
                        DB::raw("ISNULL(ad.is_plantilla, 1) as is_plantilla")
                    )
                    ->where('ah.id', $applicantId)
                    ->get();

                if (!$appointments->isEmpty()) {
                    $appointments[0]->employment_type = $this->resolveAppliedEmploymentType(
                        $appointments[0]->position_applied_id ?? null,
                        $appointments[0]->is_plantilla ?? 1,
                        $appointments[0]->employment_type ?? ''
                    );
                }
            }

            if ($appointments->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $natureInput = trim((string) $request->input('nature', ''));
            if ($natureInput !== '') {
                foreach ($appointments as $appt) {
                    $appt->nature = $natureInput;
                }
            }

            $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
            $salary_word = $f->format(isset($appointments[0]->salary) ? $appointments[0]->salary : 0);


            // Load reference data via robust path resolution
            $region_data = array_filter($this->loadJsonRecords('refregion.json'));
            $province_data = array_filter($this->loadJsonRecords('refprovince.json'));
            $city_data = array_filter($this->loadJsonRecords('refcitymun.json'));
            $brgy_data = array_filter($this->loadJsonRecords('refbrgy.json'));

            $ra_region = collect($region_data)->where("regCode", isset($appointments[0]->ra_region) ? $appointments[0]->ra_region : '')->all();
            $pa_region = collect($region_data)->where("regCode", isset($appointments[0]->pa_region) ? $appointments[0]->pa_region : '')->all();

            // set collection for address
            $ra_province = collect($province_data)->where("provCode", isset($appointments[0]->ra_province) ? $appointments[0]->ra_province : '')->all();
            $pa_province = collect($province_data)->where("provCode", isset($appointments[0]->pa_province) ? $appointments[0]->pa_province : '')->all();

            $ra_city = collect($city_data)->where("citymunCode", isset($appointments[0]->ra_city) ? $appointments[0]->ra_city : '')->all();
            $pa_city = collect($city_data)->where("citymunCode", isset($appointments[0]->pa_city) ? $appointments[0]->pa_city : '')->all();

            $ra_brgy = collect($brgy_data)->where("brgyCode", isset($appointments[0]->ra_barangay) ? $appointments[0]->ra_barangay : '')->all();
            $pa_brgy = collect($brgy_data)->where("brgyCode", isset($appointments[0]->pa_barangay) ? $appointments[0]->pa_barangay : '')->all();

            // loop address to get indexes
            for ($i = 0; $i <= count($region_data); $i++) {
                if (isset($ra_region[$i]['regDesc'])) {
                    $ra_region_id = $i;
                }
            }

            for ($i = 0; $i <= count($region_data); $i++) {
                if (isset($pa_region[$i]['regDesc'])) {
                    $pa_region_id = $i;
                }
            }

            for ($i = 0; $i <= count($province_data); $i++) {
                if (isset($ra_province[$i]['provDesc'])) {
                    $ra_province_id = $i;
                }
            }

            for ($i = 0; $i <= count($province_data); $i++) {
                if (isset($pa_province[$i]['provDesc'])) {
                    $pa_province_id = $i;
                }
            }

            for ($i = 0; $i <= count($city_data); $i++) {
                if (isset($ra_city[$i]['citymunDesc'])) {
                    $ra_city_id = $i;
                }
            }

            for ($i = 0; $i <= count($city_data); $i++) {
                if (isset($pa_city[$i]['citymunDesc'])) {
                    $pa_city_id = $i;
                }
            }

            for ($i = 0; $i <= count($brgy_data); $i++) {
                if (isset($ra_brgy[$i]['brgyDesc'])) {
                    $ra_brgy_id = $i;
                }
            }

            for ($i = 0; $i <= count($brgy_data); $i++) {
                if (isset($pa_brgy[$i]['brgyDesc'])) {
                    $pa_brgy_id = $i;
                }
            }

            $address = collect(array(
                'ra_region' => !isset($ra_region_id) ? '' : $ra_region[$ra_region_id]['regDesc'],
                'pa_region' => !isset($pa_region_id) ? '' : $pa_region[$pa_region_id]['regDesc'],
                'ra_province' => !isset($ra_province_id) ? '' : $ra_province[$ra_province_id]['provDesc'],
                'pa_province' => !isset($pa_province_id) ? '' : $pa_province[$pa_province_id]['provDesc'],
                'ra_city' => !isset($ra_city_id) ? '' : $ra_city[$ra_city_id]['citymunDesc'],
                'pa_city' => !isset($pa_city_id) ? '' : $pa_city[$pa_city_id]['citymunDesc'],
                'ra_brgy' => !isset($ra_brgy_id) ? '' : $ra_brgy[$ra_brgy_id]['brgyDesc'],
                'pa_brgy' => !isset($pa_brgy_id) ? '' : $pa_brgy[$pa_brgy_id]['brgyDesc']
            ));
            // end of getting address data

            $signatories = array(
                'signatory' => $request->signatory,
                'position' => $request->position,
                'vice' => $request->vice,
                'who' => $request->who,
                'note' => $request->note,
                'cs_date' => $request->cs_date,
                'cs_resolution_no' => $request->cs_resolution_no,
                'cs_resolution_series' => $request->cs_resolution_series,
                'hrmo' => $request->hrmo,
                'hrmpsb' => $request->hrmpsb,
                'publish_at' => $request->publish_at,
                'publish_from' => $request->publish_from,
                'publish_to' => $request->publish_to,
                'posted_at' => $request->posted_at,
                'posted_from' => $request->posted_from,
                'posted_to' => $request->posted_to,
                'started_on' => $request->started_on,
                'deliberation_on' => $request->deliberation_on,
            );

            // Form "Nature of appointment" must drive the PDF (not only DB promotion_natures).
            $certificateNature = trim((string) $request->input('nature', ''));

            $pdf = PDF::loadView('appointment_certificates.appointment_certificate_print_2025', compact(
                'appointments',
                'signatories',
                'address',
                'salary_word',
                'certificateNature'
            ))->setOptions(['defaultFont' => 'times new roman']);
            $pdf->setPaper('A4');

            // Generate PDF content as base64 for API response
            $pdfContent = $pdf->output();
            $base64Content = base64_encode($pdfContent);

            $filename = 'appointment_certificate_' . $request->employee . '_' . date('Y-m-d') . '.pdf';

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate appointment certificate: ' . $e->getMessage());
        }
    }

    /**
     * Generate appointment certificate as DOCX using PhpWord.
     */
    public function word(Request $request)
    {
        try {
            if ($request->employee == null) {
                return $this->errorResponse('Please select employee.', 400);
            }

            $employeeId = (int) $request->employee;

            $appointments = $this->appointmentCertificateAppointmentRowsFromEmployeeOrPromotion($employeeId);

            if ($appointments->isEmpty()) {
                $applicantId = $employeeId < 0 ? abs($employeeId) : $employeeId;
                $linkedEmployeeId = $this->appointmentCertificateLinkedEmployeeIdFromApplicantHeaderId($applicantId);
                if ($linkedEmployeeId !== null) {
                    $appointments = $this->appointmentCertificateAppointmentRowsFromEmployeeOrPromotion($linkedEmployeeId);
                }
            }

            if ($appointments->isEmpty()) {
                $applicantId = $employeeId < 0 ? abs($employeeId) : $employeeId;
                $appointments = DB::table('applicant_headers as ah')
                    ->leftJoin('applicant_details as ad', function ($join) {
                        $join->on('ad.applicant_id', '=', 'ah.id')
                            ->whereRaw("ad.id = (
                                SELECT TOP 1 ad2.id
                                FROM applicant_details as ad2
                                WHERE ad2.applicant_id = ah.id
                                ORDER BY CASE WHEN ISNULL(ad2.application_status_id, 0) IN (6,5) THEN 0 ELSE 1 END, ad2.id DESC
                            )");
                    })
                    ->leftJoin('plantillas as p', function ($join) {
                        $join->on('ad.position_applied_id', '=', 'p.id')
                            ->whereRaw('ISNULL(ad.is_plantilla, 1) = 1');
                    })
                    ->leftJoin('non_plantillas as np', function ($join) {
                        $join->on('ad.position_applied_id', '=', 'np.id')
                            ->whereRaw('ISNULL(ad.is_plantilla, 1) = 0');
                    })
                    ->leftJoin('positions as pos_p', 'p.position_id', '=', 'pos_p.id')
                    ->leftJoin('positions as pos_np', 'np.position_id', '=', 'pos_np.id')
                    ->leftJoin('departments as dep_p', 'p.department_id', '=', 'dep_p.id')
                    ->leftJoin('departments as dep_np', 'np.department_id', '=', 'dep_np.id')
                    ->leftJoin('employment_types as et_p', 'et_p.id', '=', 'p.employment_type_id')
                    ->leftJoin('employment_types as et_np', 'et_np.id', '=', 'np.employee_type_id')
                    ->leftJoin('salary_schedules as ss', function ($join) {
                        $join->where('ss.active', 1);
                    })
                    ->leftJoin('salary_schedules_details as ssd', function ($join) {
                        $join->on('ssd.salary_schedule_id', '=', 'ss.id')
                            ->on('ssd.salary_grade_id', '=', 'p.salary_grade_id')
                            ->on('ssd.salary_step_id', '=', 'p.salary_step_id');
                    })
                    ->select(
                        DB::raw('-ah.id as id'),
                        DB::raw("CONCAT(ah.first_name,' ',ah.last_name) as name"),
                        DB::raw("ISNULL(COALESCE(pos_np.name, pos_p.name), '') as position"),
                        DB::raw("ISNULL(COALESCE(dep_np.name, dep_p.name), '') as department"),
                        DB::raw("'' as ra_region"),
                        DB::raw("'' as ra_province"),
                        DB::raw("'' as ra_city"),
                        DB::raw("'' as ra_barangay"),
                        DB::raw("'' as pa_region"),
                        DB::raw("'' as pa_province"),
                        DB::raw("'' as pa_city"),
                        DB::raw("'' as pa_barangay"),
                        DB::raw("ISNULL(CAST(p.salary_grade_id as varchar(10)), '') as salary_grade_id"),
                        DB::raw("ISNULL(CAST(p.salary_step_id as varchar(10)), '') as salary_step_id"),
                        DB::raw("COALESCE(ssd.amount, np.salary, 0) as salary"),
                        DB::raw("'Original' as nature"),
                        DB::raw("ISNULL(COALESCE(et_p.name, et_np.name), '') as employment_type"),
                        DB::raw("ISNULL(p.code, '') as code"),
                        'ad.position_applied_id',
                        DB::raw("ISNULL(ad.is_plantilla, 1) as is_plantilla")
                    )
                    ->where('ah.id', $applicantId)
                    ->get();

                if (!$appointments->isEmpty()) {
                    $appointments[0]->employment_type = $this->resolveAppliedEmploymentType(
                        $appointments[0]->position_applied_id ?? null,
                        $appointments[0]->is_plantilla ?? 1,
                        $appointments[0]->employment_type ?? ''
                    );
                }
            }

            if ($appointments->isEmpty()) {
                return $this->notFoundResponse('Employee not found');
            }

            $natureInput = trim((string) $request->input('nature', ''));
            if ($natureInput !== '') {
                foreach ($appointments as $appt) {
                    $appt->nature = $natureInput;
                }
            }

            $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
            $salary_word = $f->format(isset($appointments[0]->salary) ? $appointments[0]->salary : 0);

            // Load reference data
            $region_data = array_filter($this->loadJsonRecords('refregion.json'));
            $province_data = array_filter($this->loadJsonRecords('refprovince.json'));
            $city_data = array_filter($this->loadJsonRecords('refcitymun.json'));
            $brgy_data = array_filter($this->loadJsonRecords('refbrgy.json'));

            $ra_region = collect($region_data)->where("regCode", isset($appointments[0]->ra_region) ? $appointments[0]->ra_region : '')->all();
            $pa_region = collect($region_data)->where("regCode", isset($appointments[0]->pa_region) ? $appointments[0]->pa_region : '')->all();
            $ra_province = collect($province_data)->where("provCode", isset($appointments[0]->ra_province) ? $appointments[0]->ra_province : '')->all();
            $pa_province = collect($province_data)->where("provCode", isset($appointments[0]->pa_province) ? $appointments[0]->pa_province : '')->all();
            $ra_city = collect($city_data)->where("citymunCode", isset($appointments[0]->ra_city) ? $appointments[0]->ra_city : '')->all();
            $pa_city = collect($city_data)->where("citymunCode", isset($appointments[0]->pa_city) ? $appointments[0]->pa_city : '')->all();
            $ra_brgy = collect($brgy_data)->where("brgyCode", isset($appointments[0]->ra_barangay) ? $appointments[0]->ra_barangay : '')->all();
            $pa_brgy = collect($brgy_data)->where("brgyCode", isset($appointments[0]->pa_barangay) ? $appointments[0]->pa_barangay : '')->all();

            // Get address indexes
            for ($i = 0; $i <= count($region_data); $i++) {
                if (isset($ra_region[$i]['regDesc'])) $ra_region_id = $i;
            }
            for ($i = 0; $i <= count($region_data); $i++) {
                if (isset($pa_region[$i]['regDesc'])) $pa_region_id = $i;
            }
            for ($i = 0; $i <= count($province_data); $i++) {
                if (isset($ra_province[$i]['provDesc'])) $ra_province_id = $i;
            }
            for ($i = 0; $i <= count($province_data); $i++) {
                if (isset($pa_province[$i]['provDesc'])) $pa_province_id = $i;
            }
            for ($i = 0; $i <= count($city_data); $i++) {
                if (isset($ra_city[$i]['citymunDesc'])) $ra_city_id = $i;
            }
            for ($i = 0; $i <= count($city_data); $i++) {
                if (isset($pa_city[$i]['citymunDesc'])) $pa_city_id = $i;
            }
            for ($i = 0; $i <= count($brgy_data); $i++) {
                if (isset($ra_brgy[$i]['brgyDesc'])) $ra_brgy_id = $i;
            }
            for ($i = 0; $i <= count($brgy_data); $i++) {
                if (isset($pa_brgy[$i]['brgyDesc'])) $pa_brgy_id = $i;
            }

            $address = collect(array(
                'ra_region' => !isset($ra_region_id) ? '' : $ra_region[$ra_region_id]['regDesc'],
                'pa_region' => !isset($pa_region_id) ? '' : $pa_region[$pa_region_id]['regDesc'],
                'ra_province' => !isset($ra_province_id) ? '' : $ra_province[$ra_province_id]['provDesc'],
                'pa_province' => !isset($pa_province_id) ? '' : $pa_province[$pa_province_id]['provDesc'],
                'ra_city' => !isset($ra_city_id) ? '' : $ra_city[$ra_city_id]['citymunDesc'],
                'pa_city' => !isset($pa_city_id) ? '' : $pa_city[$pa_city_id]['citymunDesc'],
                'ra_brgy' => !isset($ra_brgy_id) ? '' : $ra_brgy[$ra_brgy_id]['brgyDesc'],
                'pa_brgy' => !isset($pa_brgy_id) ? '' : $pa_brgy[$pa_brgy_id]['brgyDesc']
            ));

            $signatories = array(
                'signatory' => $request->signatory ?? '',
                'position' => $request->position ?? '',
                'vice' => $request->vice ?? '',
                'who' => $request->who ?? '',
                'note' => $request->note ?? '',
                'cs_date' => $request->cs_date ?? '',
                'cs_resolution_no' => $request->cs_resolution_no ?? '',
                'cs_resolution_series' => $request->cs_resolution_series ?? '',
                'hrmo' => $request->hrmo ?? '',
                'hrmpsb' => $request->hrmpsb ?? '',
                'publish_at' => $request->publish_at ?? '',
                'publish_from' => $request->publish_from ?? '',
                'publish_to' => $request->publish_to ?? '',
                'posted_at' => $request->posted_at ?? '',
                'posted_from' => $request->posted_from ?? '',
                'posted_to' => $request->posted_to ?? '',
                'started_on' => $request->started_on ?? '',
                'deliberation_on' => $request->deliberation_on ?? '',
            );

            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(9); // Minimized font size

            $section = $phpWord->addSection([
                'marginTop' => Converter::inchToTwip(0.6),
                'marginRight' => Converter::inchToTwip(0.5),
                'marginBottom' => Converter::inchToTwip(0.6),
                'marginLeft' => Converter::inchToTwip(0.5),
                'pageSizeW' => Converter::inchToTwip(8.27),
                'pageSizeH' => Converter::inchToTwip(11.69),
            ]);

            // Paragraph styles with first line indentation
            $phpWord->addParagraphStyle('indent', [
                'indentation' => ['firstLine' => Converter::inchToTwip(0.5)],
                'spaceAfter' => 0,
            ]);

            // Paragraph style to center Authorized Official text relative to underline (text is longer, needs right shift)
            $phpWord->addParagraphStyle('authLabelCenter', [
                'indentation' => ['left' => Converter::inchToTwip(0.5)],
                'spaceAfter' => 0,
            ]);

            // Paragraph style to center Date text relative to underline (text is shorter, needs right shift)
            $phpWord->addParagraphStyle('dateLabelCenter', [
                'indentation' => ['left' => Converter::inchToTwip(1.0)],
                'spaceAfter' => 0,
            ]);

            // "For Regulated Agencies" textbox at top right (separate table, own cell) - ONLY ONCE at top of page 1
            $regAgenciesTable = $section->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'cellMargin' => 0,
            ]);
            $regAgenciesTable->addRow();
            $regAgenciesCell = $regAgenciesTable->addCell(Converter::inchToTwip(1.27));

            // Inner bordered cell for "For Regulated Agencies"
            $regAgenciesInnerTable = $regAgenciesCell->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.02),
                'alignment' => WordJc::END,
            ]);
            $regAgenciesInnerTable->addRow();
            $regAgenciesInnerCell = $regAgenciesInnerTable->addCell();
            $regAgenciesInnerCell->addText('For Accredited/Deregulated Agencies', ['size' => 9, 'italic' => true, 'bold' => true], ['alignment' => WordJc::CENTER]);

            $section->addTextBreak(0.2);

            // Only process the first appointment to avoid duplication
            $appointment = $appointments->first();

            // Main outer border
            $mainTable = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.08),
            ]);
            $mainTable->addRow();
            $mainCell = $mainTable->addCell();

            // Inner white box
            $innerTable = $mainCell->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.06),
            ]);
            $innerTable->addRow();
            $innerCell = $innerTable->addCell();

            // CS Form header (left aligned, not bold for Revised 2018)
            $innerCell->addText('CS Form No. 33-A', ['size' => 9]);
            $innerCell->addText('Revised 2018', ['size' => 9]);
            $innerCell->addTextBreak(0.4);

            // Header section (centered)
            $innerCell->addText('Republic of the Philippines', ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $innerCell->addText(CompanyHelper::getName(), ['size' => 9], ['alignment' => WordJc::CENTER]);
            $innerCell->addText(CompanyHelper::getAddress(), ['size' => 9], ['alignment' => WordJc::CENTER]);
            $innerCell->addTextBreak(0.5);

            // Appointee name
            $innerCell->addText('Mr./Mrs./Ms.: ' . strtoupper($appointment->name ?? ''), ['size' => 9, 'bold' => true]);
            $innerCell->addTextBreak(0.3);

            // Paragraph 1 with first-line indentation
            $appointmentRun = $innerCell->addTextRun('indent');
            $appointmentRun->addText('You are hereby appointed as ', ['size' => 9]);
            $appointmentRun->addText($appointment->position ?? '', ['size' => 9, 'bold' => true]);
            $appointmentRun->addText(' SG ', ['size' => 9]);
            $appointmentRun->addText(($appointment->salary_grade_id ?? '') . ' / ' . ($appointment->salary_step_id ?? ''), ['size' => 9, 'bold' => true]);
            $appointmentRun->addText(' under ', ['size' => 9]);
            $appointmentRun->addText($appointment->employment_type ?? '', ['size' => 9, 'bold' => true]);
            $appointmentRun->addText(' status at the ', ['size' => 9]);
            $appointmentRun->addText($appointment->department ?? '', ['size' => 9, 'bold' => true]);
            $appointmentRun->addText(' with a compensation rate of ', ['size' => 9]);
            $appointmentRun->addText(strtoupper($salary_word), ['size' => 9, 'bold' => true]);
            $appointmentRun->addText(' P ', ['size' => 9]);
            $appointmentRun->addText(number_format($appointment->salary ?? 0, 2, '.', ','), ['size' => 9, 'bold' => true, 'underline' => 'single']);
            $appointmentRun->addText(' pesos per month.', ['size' => 9]);

            $innerCell->addTextBreak(0.3);

            // Paragraph 2 with first-line indentation
            $natureRun = $innerCell->addTextRun('indent');
            $natureRun->addText('The nature of this appointment is ', ['size' => 9]);
            $natureText = $appointment->nature ?? '';
            if (!empty($natureText)) {
                $natureRun->addText($natureText, ['size' => 9, 'bold' => true]);
            } else {
                $natureRun->addText('____________________', ['size' => 9, 'underline' => 'single']);
            }
            $natureRun->addText(' vice ', ['size' => 9]);
            $viceText = $signatories['vice'] == '' ? '____________________' : $signatories['vice'];
            $natureRun->addText($viceText, ['size' => 9, 'underline' => 'single']);
            $natureRun->addText(' who ', ['size' => 9]);
            $whoText = $signatories['who'] == '' ? '____________________' : $signatories['who'];
            $natureRun->addText($whoText, ['size' => 9, 'underline' => 'single']);
            $natureRun->addText(' with Plantilla Item No. ', ['size' => 9]);
            $natureRun->addText($appointment->code ?? '', ['size' => 9, 'bold' => true, 'underline' => 'single']);
            $natureRun->addText(' Page 1.', ['size' => 9]);

            $innerCell->addTextBreak(0.5);

            // Signatory section (right aligned)
            $signTable = $innerCell->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'cellMargin' => 0,
            ]);
            $signTable->addRow();
            $signTable->addCell(Converter::inchToTwip(4));
            $signCell = $signTable->addCell(Converter::inchToTwip(3));
            $signCell->addText('Very truly yours,', ['size' => 9], ['alignment' => WordJc::START]);
            $signCell->addTextBreak(0.3);
            // Signature line
            $signCell->addText('______________________', ['size' => 9], ['alignment' => WordJc::CENTER]);
            $signCell->addText($signatories['signatory'], ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $signCell->addText($signatories['position'], ['size' => 9], ['alignment' => WordJc::CENTER]);
            $signCell->addTextBreak(0.2);
            // Date line
            $signCell->addText('______________________', ['size' => 9], ['alignment' => WordJc::CENTER]);
            $signCell->addText('Date of Signing', ['size' => 9], ['alignment' => WordJc::CENTER]);

            // CSC Action section (separate bordered box at bottom)
            $section->addTextBreak(0.3);
            $cscTable = $mainCell->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.06),
            ]);
            $cscTable->addRow();
            $cscCell = $cscTable->addCell();

            // CSC ACTION header at top left
            $cscCell->addText('CSC ACTION:', ['size' => 9, 'bold' => true]);
            $cscCell->addTextBreak(0.3);

            // Authorized Official section - underline slightly indented, label centered relative to underline
            $authTable = $cscCell->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'cellMargin' => 0,
            ]);
            $authTable->addRow();
            // Left spacing for indentation
            $authTable->addCell(Converter::inchToTwip(0.3));
            // Underline cell (about one-third width)
            $authUnderlineCell = $authTable->addCell(Converter::inchToTwip(4));
            $authUnderlineCell->addText('__________________________', ['size' => 9], ['alignment' => WordJc::START]);
            // Right spacing
            $authTable->addCell(Converter::inchToTwip(4));

            // Authorized Official label centered relative to the underline
            $authLabelTable = $cscCell->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'cellMargin' => 0,
            ]);
            $authLabelTable->addRow();
            $authLabelTable->addCell(Converter::inchToTwip(0.3));
            $authLabelCell = $authLabelTable->addCell(Converter::inchToTwip(4));
            $authLabelCell->addText('Authorized Official', ['size' => 9, 'bold' => true], 'authLabelCenter');
            $authLabelTable->addCell(Converter::inchToTwip(4));

            $cscCell->addTextBreak(0.3);

            // Date section - underline aligned with Authorized Official, label centered relative to underline
            $dateTable = $cscCell->addTable([
                'borderSize' => 0,
                'cellMargin' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $dateTable->addRow();
            // Left spacing for indentation (same as above)
            $dateTable->addCell(Converter::inchToTwip(0.3));
            // Underline cell (same width as above)
            $dateUnderlineCell = $dateTable->addCell(Converter::inchToTwip(4));
            $dateUnderlineCell->addText('__________________________', ['size' => 9], ['alignment' => WordJc::START]);
            // Right spacing
            $dateTable->addCell(Converter::inchToTwip(4));

            // Date label centered relative to the underline
            $dateLabelTable = $cscCell->addTable([
                'borderSize' => 0,
                'cellMargin' => 0,
                'borderColor' => 'FFFFFF',
            ]);
            $dateLabelTable->addRow();
            $dateLabelTable->addCell(Converter::inchToTwip(0.3));
            $dateLabelCell = $dateLabelTable->addCell(Converter::inchToTwip(4));
            $dateLabelCell->addText('Date', ['size' => 9, 'bold' => true], 'dateLabelCenter');
            $dateStampCell = $dateLabelTable->addCell(Converter::inchToTwip(4));
            $dateStampCell->addText('(Stamp of Date of Release)', ['size' => 9, 'italic' => true], ['alignment' => WordJc::END]);

            // Certification 1 (moved to page 1)
            $section->addTextBreak(0.3);
            $cert1Table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.08),
            ]);
            $cert1Table->addRow();
            $cert1Cell = $cert1Table->addCell();

            $innerCert1 = $cert1Cell->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.06),
            ]);
            $innerCert1->addRow();
            $innerCert1Cell = $innerCert1->addCell();

            $innerCert1Cell->addText('Certification', ['size' => 12, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $innerCert1Cell->addTextBreak(0.3);

            $cert1Run = $innerCert1Cell->addTextRun('indent');
            $cert1Run->addText('This is to certify that all requirements and supporting papers pursuant to ', ['size' => 9]);
            $cert1Run->addText('CSC MC No. 24, s. 2017, as amended', ['size' => 9, 'bold' => true]);
            $cert1Run->addText(', have been complied with, reviewed and found to be in order.', ['size' => 9]);

            $innerCert1Cell->addTextBreak(0.3);

            $cert1Run2 = $innerCert1Cell->addTextRun('indent');
            $cert1Run2->addText('The position was published at ', ['size' => 9]);
            $publishAt = $signatories['publish_at'] == '' ? '___________________________' : $signatories['publish_at'];
            $cert1Run2->addText($publishAt, ['size' => 9, 'bold' => true, 'underline' => 'single']);
            $cert1Run2->addText(' from ', ['size' => 9]);
            $publishFrom = $signatories['publish_from'] == '' ? '___________' : $signatories['publish_from'];
            $cert1Run2->addText($publishFrom, ['size' => 9, 'bold' => true, 'underline' => 'single']);
            $cert1Run2->addText(' to ', ['size' => 9]);
            $publishTo = $signatories['publish_to'] == '' ? '___________' : $signatories['publish_to'];
            $cert1Run2->addText($publishTo, ['size' => 9, 'bold' => true, 'underline' => 'single']);
            $cert1Run2->addText(' and posted in ', ['size' => 9]);
            $postedAt = $signatories['posted_at'] == '' ? '_____________________________________' : $signatories['posted_at'];
            $cert1Run2->addText($postedAt, ['size' => 9, 'bold' => true, 'underline' => 'single']);
            $cert1Run2->addText(' from ', ['size' => 9]);
            $postedFrom = $signatories['posted_from'] == '' ? '___________' : $signatories['posted_from'];
            $cert1Run2->addText($postedFrom, ['size' => 9, 'bold' => true, 'underline' => 'single']);
            $cert1Run2->addText(' to ', ['size' => 9]);
            $postedTo = $signatories['posted_to'] == '' ? '___________' : $signatories['posted_to'];
            $cert1Run2->addText($postedTo, ['size' => 9, 'bold' => true, 'underline' => 'single']);
            $cert1Run2->addText(' in consonance with RA No.7041. The assessment by the Human Resource Merit Promotion and Selection Board (HRMPSB) started on ', ['size' => 9]);
            $startedOn = $signatories['started_on'] == '' ? '______________' : date('M d, Y', strtotime($signatories['started_on']));
            $cert1Run2->addText($startedOn, ['size' => 9, 'bold' => true, 'underline' => 'single']);
            $cert1Run2->addText('.', ['size' => 9]);

            $innerCert1Cell->addTextBreak(0.5);

            $hrmoTable = $innerCert1Cell->addTable([
                'borderSize' => 0,
                'cellMargin' => 0,
            ]);
            $hrmoTable->addRow();
            $hrmoTable->addCell(Converter::inchToTwip(4));
            $hrmoCell = $hrmoTable->addCell(Converter::inchToTwip(3));
            $hrmoName = $signatories['hrmo'] == '' ? '___________________________' : $signatories['hrmo'];
            $hrmoCell->addText($hrmoName, ['size' => 9, 'bold' => true, 'underline' => 'single'], ['alignment' => WordJc::CENTER]);
            $hrmoCell->addText('Highest Ranking HRMO', ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);

            // Certification 2
            $section->addTextBreak(0.3);
            $cert2Table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.08),
            ]);
            $cert2Table->addRow();
            $cert2Cell = $cert2Table->addCell();

            $innerCert2 = $cert2Cell->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.06),
            ]);
            $innerCert2->addRow();
            $innerCert2Cell = $innerCert2->addCell();

            $innerCert2Cell->addText('Certification', ['size' => 12, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $innerCert2Cell->addTextBreak(0.3);

            $cert2Run = $innerCert2Cell->addTextRun('indent');
            $cert2Run->addText('This is to certify that the appointee has been screened and found qualified by the majority of the HRMPSB/Placement Committee during the deliberation held on ', ['size' => 9]);
            $deliberationOn = $signatories['deliberation_on'] == '' ? '__________________' : date('M d, Y', strtotime($signatories['deliberation_on']));
            $cert2Run->addText($deliberationOn, ['size' => 9, 'bold' => true, 'underline' => 'single']);
            $cert2Run->addText('.', ['size' => 9]);

            $innerCert2Cell->addTextBreak(0.5);

            $hrmpsbTable = $innerCert2Cell->addTable([
                'borderSize' => 0,
                'cellMargin' => 0,
            ]);
            $hrmpsbTable->addRow();
            $hrmpsbTable->addCell(Converter::inchToTwip(4));
            $hrmpsbCell = $hrmpsbTable->addCell(Converter::inchToTwip(3));
            $hrmpsbName = $signatories['hrmpsb'] == '' ? '___________________________________________' : $signatories['hrmpsb'];
            $hrmpsbCell->addText($hrmpsbName, ['size' => 9, 'bold' => true, 'underline' => 'single'], ['alignment' => WordJc::CENTER]);
            $hrmpsbCell->addText('Chairperson, HRMPSB/Placement Committee', ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);

            // Page 2 - CSC/HRMO Notation and Acknowledgment
            $section->addPageBreak();

            // CSC/HRMO Notation (replicates the updated PDF table structure)
            $section->addTextBreak(0.3);

            // Outer bordered table for the notation block
            $cscOuterTable = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.08),
            ]);
            $cscOuterTable->addRow();
            $cscOuterCell = $cscOuterTable->addCell();

            // Inner table with header and body (4 columns)
            $cscTable = $cscOuterCell->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.04),
            ]);

            // Header row: ACTION ON APPOINTMENTS (spanning 3 cols) | Recorded by
            $headerRow = $cscTable->addRow();
            $actionHeaderCell = $headerRow->addCell(Converter::inchToTwip(5.5), [
                'gridSpan' => 3,
                'vMerge' => 'restart',
            ]);
            $actionHeaderCell->addText('ACTION ON APPOINTMENTS', ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);

            $recordedHeaderCell = $headerRow->addCell(Converter::inchToTwip(2));
            $recordedHeaderCell->addText('Recorded by', ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);

            // Row 1 - Validated per RAI
            $row = $cscTable->addRow();
            $cell1 = $row->addCell(Converter::inchToTwip(3.8));
            $cell1->addText('☐  Validated per RAI for the month of ____________', ['size' => 9]);

            // Middle column with only top/bottom borders (no left/right)
            $cell2 = $row->addCell(Converter::inchToTwip(0.85), [
                'borderTopSize' => 6,
                'borderBottomSize' => 6,
                'borderLeftSize' => 0,
                'borderRightSize' => 0,
            ]);
            $cell2->addText('', ['size' => 9]);

            $cell3 = $row->addCell(Converter::inchToTwip(0.85));
            $cell3->addText('', ['size' => 9]);

            $cell4 = $row->addCell(Converter::inchToTwip(2));
            $cell4->addText('____________________', ['size' => 9], ['alignment' => WordJc::CENTER]);

            // Row 2 - Invalidated per CSCRO/FO letter dated
            $row = $cscTable->addRow();
            $cell1 = $row->addCell(Converter::inchToTwip(3.8));
            $cell1->addText('☐  Invalidated per CSCRO/FO letter dated ____________', ['size' => 9]);

            $cell2 = $row->addCell(Converter::inchToTwip(0.85), [
                'borderTopSize' => 6,
                'borderBottomSize' => 6,
                'borderLeftSize' => 0,
                'borderRightSize' => 0,
            ]);
            $cell2->addText('', ['size' => 9]);

            $cell3 = $row->addCell(Converter::inchToTwip(0.85));
            $cell3->addText('', ['size' => 9]);

            $cell4 = $row->addCell(Converter::inchToTwip(2));
            $cell4->addText('____________________', ['size' => 9], ['alignment' => WordJc::CENTER]);

            // Row 3 - Appeal header with DATE FILED / STATUS
            $row = $cscTable->addRow();
            $cell1 = $row->addCell(Converter::inchToTwip(3.8));
            $cell1->addText('☐  Appeal', ['size' => 9]);

            $cell2 = $row->addCell(Converter::inchToTwip(0.85));
            $cell2->addText('DATE FILED', ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);

            $cell3 = $row->addCell(Converter::inchToTwip(0.85));
            $cell3->addText('STATUS', ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);

            $cell4 = $row->addCell(Converter::inchToTwip(2));
            $cell4->addText('____________________', ['size' => 9], ['alignment' => WordJc::CENTER]);

            // Row 4 - CSCRO/ CSC-Commission
            $row = $cscTable->addRow();
            $cell1 = $row->addCell(Converter::inchToTwip(3.8));
            $cell1->addText('    ☐  CSCRO/ CSC-Commission', ['size' => 9]);

            $cell2 = $row->addCell(Converter::inchToTwip(0.85));
            $cell2->addText('_____________', ['size' => 9], ['alignment' => WordJc::CENTER]);

            $cell3 = $row->addCell(Converter::inchToTwip(0.85));
            $cell3->addText('_____________', ['size' => 9], ['alignment' => WordJc::CENTER]);

            $cell4 = $row->addCell(Converter::inchToTwip(2));
            $cell4->addText('____________________', ['size' => 9], ['alignment' => WordJc::CENTER]);

            // Row 5 - Petition for Review header
            $row = $cscTable->addRow();
            $cell1 = $row->addCell(Converter::inchToTwip(3.8));
            $cell1->addText('☐  Petition for Review', ['size' => 9]);

            $cell2 = $row->addCell(Converter::inchToTwip(0.85));
            $cell2->addText('', ['size' => 9]);

            $cell3 = $row->addCell(Converter::inchToTwip(0.85));
            $cell3->addText('', ['size' => 9]);

            $cell4 = $row->addCell(Converter::inchToTwip(2));
            $cell4->addText('____________________', ['size' => 9], ['alignment' => WordJc::CENTER]);

            // Row 6 - CSC-Commission
            $row = $cscTable->addRow();
            $cell1 = $row->addCell(Converter::inchToTwip(3.8));
            $cell1->addText('    ☐  CSC-Commission', ['size' => 9]);

            $cell2 = $row->addCell(Converter::inchToTwip(0.85));
            $cell2->addText('_____________', ['size' => 9], ['alignment' => WordJc::CENTER]);

            $cell3 = $row->addCell(Converter::inchToTwip(0.85));
            $cell3->addText('_____________', ['size' => 9], ['alignment' => WordJc::CENTER]);

            $cell4 = $row->addCell(Converter::inchToTwip(2));
            $cell4->addText('____________________', ['size' => 9], ['alignment' => WordJc::CENTER]);

            // Row 7 - Court of Appeals
            $row = $cscTable->addRow();
            $cell1 = $row->addCell(Converter::inchToTwip(3.8));
            $cell1->addText('    ☐  Court of Appeals', ['size' => 9]);

            $cell2 = $row->addCell(Converter::inchToTwip(0.85));
            $cell2->addText('_____________', ['size' => 9], ['alignment' => WordJc::CENTER]);

            $cell3 = $row->addCell(Converter::inchToTwip(0.85));
            $cell3->addText('_____________', ['size' => 9], ['alignment' => WordJc::CENTER]);

            $cell4 = $row->addCell(Converter::inchToTwip(2));
            $cell4->addText('____________________', ['size' => 9], ['alignment' => WordJc::CENTER]);

            // Row 8 - Supreme Court
            $row = $cscTable->addRow();
            $cell1 = $row->addCell(Converter::inchToTwip(3.8));
            $cell1->addText('    ☐  Supreme Court', ['size' => 9]);

            $cell2 = $row->addCell(Converter::inchToTwip(0.85));
            $cell2->addText('_____________', ['size' => 9], ['alignment' => WordJc::CENTER]);

            $cell3 = $row->addCell(Converter::inchToTwip(0.85));
            $cell3->addText('_____________', ['size' => 9], ['alignment' => WordJc::CENTER]);

            $cell4 = $row->addCell(Converter::inchToTwip(2));
            $cell4->addText('____________________', ['size' => 9], ['alignment' => WordJc::CENTER]);

            // Acknowledgment section
            $section->addTextBreak(0.3);
            $ackTable = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.08),
            ]);
            $ackTable->addRow();
            $ackCell = $ackTable->addCell();

            $ackInner = $ackCell->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => Converter::inchToTwip(0.06),
            ]);
            $ackInner->addRow();
            $ackLeftCell = $ackInner->addCell(Converter::inchToTwip(3));
            $ackLeftCell->addText('Original Copy-for the Agency', ['size' => 9]);
            $ackLeftCell->addText('OriginalCopy-for the Civil Service Commission', ['size' => 9]);
            $ackLeftCell->addText('Original Copy-for the Appointee', ['size' => 9]);

            $ackRightCell = $ackInner->addCell(Converter::inchToTwip(4.5));
            $ackRightCell->addText('Acknowledgement', ['size' => 9, 'bold' => true], ['alignment' => WordJc::CENTER]);
            $ackRightCell->addTextBreak(0.2);
            $ackRightCell->addText('Received original/photocopyof appointment on _____________', ['size' => 9], ['alignment' => WordJc::CENTER]);
            $ackRightCell->addText('____________________________', ['size' => 9], ['alignment' => WordJc::CENTER]);
            $ackRightCell->addText('Appointee', ['size' => 9], ['alignment' => WordJc::CENTER]);

            $filename = 'appointment_certificate_' . $request->employee . '_' . date('Y-m-d_His') . '.docx';
            $tempPath = storage_path('app/' . $filename);

            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate appointment certificate DOCX: ' . $e->getMessage());
        }
    }
}
