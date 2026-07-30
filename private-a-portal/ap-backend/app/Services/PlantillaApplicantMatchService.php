<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlantillaApplicantMatchService
{
    /**
     * Check whether an applicant's PDS satisfies a plantilla position's requirements.
     *
     * @return array{meets_requirements: bool, missing_requirements: string[]}
     */
    public function evaluate(int $plantillaId, int $employeeId, ?string $applicantNo = null): array
    {
        $plantilla = DB::table('plantillas')->where('id', $plantillaId)->first();
        if (!$plantilla) {
            return [
                'meets_requirements' => false,
                'missing_requirements' => ['Position not found.'],
            ];
        }

        $profile = $this->loadApplicantProfile($employeeId, $applicantNo);
        $missing = [];

        $this->validateEligibility($plantillaId, $plantilla, $profile, $missing);
        $this->validateEducation($plantillaId, $plantilla, $profile, $missing);
        $this->validateExperience($plantillaId, $plantilla, $profile, $missing);
        $this->validateTraining($plantillaId, $plantilla, $profile, $missing);

        return [
            'meets_requirements' => empty($missing),
            'missing_requirements' => $missing,
        ];
    }

    private function loadApplicantProfile(int $employeeId, ?string $applicantNo): array
    {
        $eligibilityIds = DB::table('employee_examinations')
            ->where('employee_id', $employeeId)
            ->where('eligibility_id', '>', 0)
            ->pluck('eligibility_id')
            ->unique()
            ->values()
            ->all();

        $eligibilityNames = DB::table('employee_examinations as ee')
            ->join('eligibilities as e', 'e.id', '=', 'ee.eligibility_id')
            ->where('ee.employee_id', $employeeId)
            ->pluck('e.name')
            ->filter()
            ->values()
            ->all();

        $educations = DB::table('employee_educations')
            ->where('employee_id', $employeeId)
            ->get();

        $employments = DB::table('employee_employment_records')
            ->where('employee_id', $employeeId)
            ->get();

        $trainings = DB::table('employee_trainings')
            ->where('employee_id', $employeeId)
            ->get();

        $workExperience = collect();
        if ($applicantNo) {
            try {
                $workExperience = DB::table('Work_Experience')
                    ->where('Reference_id', $applicantNo)
                    ->get();
            } catch (\Throwable $e) {
                $workExperience = collect();
            }
        }

        return [
            'eligibility_ids' => $eligibilityIds,
            'eligibility_names' => $eligibilityNames,
            'educations' => $educations,
            'employments' => $employments,
            'trainings' => $trainings,
            'work_experience' => $workExperience,
        ];
    }

    private function validateEligibility(int $plantillaId, $plantilla, array $profile, array &$missing): void
    {
        $requiredRows = DB::table('plantilla_eligibility')
            ->join('eligibilities', 'eligibilities.id', '=', 'plantilla_eligibility.examination_id')
            ->where('plantilla_eligibility.plantilla_id', $plantillaId)
            ->select(
                'plantilla_eligibility.examination_id',
                'eligibilities.name as eligibility_name'
            )
            ->get();

        if ($requiredRows->isNotEmpty()) {
            $requiredIds = $requiredRows->pluck('examination_id')->map(fn ($id) => (int) $id)->all();
            $applicantIds = array_map('intval', $profile['eligibility_ids']);

            $matchedById = !empty(array_intersect($requiredIds, $applicantIds));

            if (!$matchedById) {
                $requiredNames = $requiredRows->pluck('eligibility_name')->filter()->all();
                $matchedByName = $this->anyTextMatchesList(
                    $profile['eligibility_names'],
                    $requiredNames
                );

                if (!$matchedByName) {
                    $label = implode(', ', $requiredNames);
                    $missing[] = 'Eligibility: ' . ($label ?: 'Required eligibility not found in your PDS');
                }
            }

            return;
        }

        $text = trim((string) ($plantilla->eligibility ?? ''));
        if ($text === '') {
            return;
        }

        if (!$this->textMatchesApplicantList($text, $profile['eligibility_names'])) {
            $missing[] = 'Eligibility: ' . $text;
        }
    }

    private function validateEducation(int $plantillaId, $plantilla, array $profile, array &$missing): void
    {
        $requiredRows = DB::table('plantilla_education')
            ->where('plantilla_id', $plantillaId)
            ->get();

        if ($requiredRows->isNotEmpty()) {
            $matched = false;
            foreach ($requiredRows as $required) {
                foreach ($profile['educations'] as $applicantEdu) {
                    if ($this->educationRowMatches($required, $applicantEdu)) {
                        $matched = true;
                        break 2;
                    }
                }
            }

            if (!$matched) {
                $programs = $requiredRows->pluck('program')->filter()->unique()->values()->all();
                $missing[] = 'Education: ' . (implode(', ', $programs) ?: 'Required education not found in your PDS');
            }

            return;
        }

        $text = trim((string) ($plantilla->education ?? ''));
        if ($text === '') {
            return;
        }

        $programs = $profile['educations']->pluck('program')->filter()->all();
        if (!$this->textMatchesApplicantList($text, $programs)) {
            $missing[] = 'Education: ' . $text;
        }
    }

    private function validateExperience(int $plantillaId, $plantilla, array $profile, array &$missing): void
    {
        $requiredRows = DB::table('plantilla_work_experience')
            ->where('plantilla_id', $plantillaId)
            ->get();

        if ($requiredRows->isNotEmpty()) {
            $matched = false;
            foreach ($requiredRows as $required) {
                if ($this->experienceRowMatches($required, $profile)) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $labels = $requiredRows->map(function ($row) {
                    $position = trim((string) ($row->position ?? ''));
                    $years = (int) ($row->years ?? 0);
                    if ($position && $years > 0) {
                        return "{$position} ({$years}+ years)";
                    }
                    return $position ?: 'Required work experience';
                })->filter()->unique()->values()->all();

                $missing[] = 'Experience: ' . implode(', ', $labels);
            }

            return;
        }

        $text = trim((string) ($plantilla->experience ?? ''));
        if ($text === '') {
            return;
        }

        $positions = $this->collectApplicantPositions($profile);
        if (!$this->textMatchesApplicantList($text, $positions)) {
            $missing[] = 'Experience: ' . $text;
        }
    }

    private function validateTraining(int $plantillaId, $plantilla, array $profile, array &$missing): void
    {
        $requiredRows = DB::table('plantilla_trainings')
            ->where('plantilla_id', $plantillaId)
            ->get();

        if ($requiredRows->isNotEmpty()) {
            foreach ($requiredRows as $required) {
                if (!$this->trainingRowMatches($required, $profile['trainings'])) {
                    $label = trim((string) ($required->training ?? 'Required training'));
                    $hours = (float) ($required->hours ?? 0);
                    if ($hours > 0) {
                        $label .= " ({$hours} hours)";
                    }
                    $missing[] = 'Training: ' . $label;
                }
            }

            return;
        }

        $text = trim((string) ($plantilla->training ?? ''));
        if ($text === '') {
            return;
        }

        $trainingNames = $profile['trainings']->pluck('training')->filter()->all();
        if (!$this->textMatchesApplicantList($text, $trainingNames)) {
            $missing[] = 'Training: ' . $text;
        }
    }

    private function educationRowMatches($required, $applicantEdu): bool
    {
        $reqProgram = trim((string) ($required->program ?? ''));
        $appProgram = trim((string) ($applicantEdu->program ?? ''));

        if ($reqProgram === '' || $appProgram === '') {
            return false;
        }

        if (!$this->textFuzzyMatch($reqProgram, $appProgram)) {
            return false;
        }

        $reqLevel = (int) ($required->academic_level_id ?? 0);
        $appLevel = (int) ($applicantEdu->academic_level_id ?? 0);

        if ($reqLevel > 0 && $appLevel > 0 && $appLevel < $reqLevel) {
            return false;
        }

        return true;
    }

    private function experienceRowMatches($required, array $profile): bool
    {
        $reqPosition = trim((string) ($required->position ?? ''));
        $reqYears = (int) ($required->years ?? 0);

        if ($reqPosition === '') {
            return false;
        }

        foreach ($profile['employments'] as $employment) {
            $position = trim((string) ($employment->position ?? ''));
            if ($position === '' || !$this->textFuzzyMatch($reqPosition, $position)) {
                continue;
            }

            $years = $this->yearsBetween(
                $employment->work_start_date ?? null,
                $employment->work_end_date ?? null
            );

            if ($reqYears <= 0 || $years >= $reqYears) {
                return true;
            }
        }

        foreach ($profile['work_experience'] as $work) {
            $position = trim((string) ($work->Position ?? $work->position ?? ''));
            if ($position === '' || !$this->textFuzzyMatch($reqPosition, $position)) {
                continue;
            }

            $years = $this->yearsBetween(
                $work->Work_start_date ?? $work->work_start_date ?? null,
                $work->Work_end_date ?? $work->work_end_date ?? null
            );

            if ($reqYears <= 0 || $years >= $reqYears) {
                return true;
            }
        }

        return false;
    }

    private function trainingRowMatches($required, Collection $applicantTrainings): bool
    {
        $reqName = trim((string) ($required->training ?? ''));
        if ($reqName === '') {
            return true;
        }

        $reqHours = (float) ($required->hours ?? 0);

        foreach ($applicantTrainings as $training) {
            $name = trim((string) ($training->training ?? ''));
            if ($name === '' || !$this->textFuzzyMatch($reqName, $name)) {
                continue;
            }

            $hours = (float) ($training->hours ?? 0);
            if ($reqHours <= 0 || $hours >= $reqHours) {
                return true;
            }
        }

        return false;
    }

    private function collectApplicantPositions(array $profile): array
    {
        $positions = [];

        foreach ($profile['employments'] as $employment) {
            if (!empty($employment->position)) {
                $positions[] = (string) $employment->position;
            }
            if (!empty($employment->work_company)) {
                $positions[] = (string) $employment->work_company;
            }
        }

        foreach ($profile['work_experience'] as $work) {
            if (!empty($work->Position)) {
                $positions[] = (string) $work->Position;
            }
            if (!empty($work->position)) {
                $positions[] = (string) $work->position;
            }
        }

        return array_values(array_unique(array_filter($positions)));
    }

    private function yearsBetween($start, $end): float
    {
        if (empty($start)) {
            return 0;
        }

        try {
            $startDate = new \DateTime((string) $start);
            $endDate = !empty($end) ? new \DateTime((string) $end) : new \DateTime();
            $diff = $startDate->diff($endDate);

            return max(0, $diff->y + ($diff->m / 12));
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function textMatchesApplicantList(string $requiredText, array $applicantValues): bool
    {
        $parts = $this->splitRequirementText($requiredText);
        if (empty($parts)) {
            return true;
        }

        foreach ($parts as $part) {
            if ($this->anyTextMatchesList($applicantValues, [$part])) {
                return true;
            }
        }

        return false;
    }

    private function anyTextMatchesList(array $applicantValues, array $requiredValues): bool
    {
        foreach ($requiredValues as $required) {
            $required = trim((string) $required);
            if ($required === '') {
                continue;
            }

            foreach ($applicantValues as $value) {
                if ($this->textFuzzyMatch($required, (string) $value)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function splitRequirementText(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $parts = preg_split('/\s*(?:,|;|\bor\b)\s*/i', $text) ?: [];

        return array_values(array_filter(array_map('trim', $parts)));
    }

    private function textFuzzyMatch(string $needle, string $haystack): bool
    {
        $needle = $this->normalizeText($needle);
        $haystack = $this->normalizeText($haystack);

        if ($needle === '' || $haystack === '') {
            return false;
        }

        return str_contains($haystack, $needle) || str_contains($needle, $haystack);
    }

    private function normalizeText(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return $value;
    }
}
