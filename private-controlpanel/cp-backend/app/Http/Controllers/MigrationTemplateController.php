<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class MigrationTemplateController extends Controller
{
    use ApiResponse;

    function download($template)
    {
        try {
            $validator = validator(['template' => $template], [
                'template' => 'required|in:migrate_branches,migrate_offices,migrate_divisions,migrate_sections,migrate_employment_types'
            ], [
                'template.required' => 'Template parameter is required.',
                'template.in' => 'Invalid template type. Allowed values: migrate_branches, migrate_offices, migrate_divisions, migrate_sections, migrate_employment_types'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $filePath = null;
            $fileName = null;

            switch ($template) {
                case 'migrate_branches':
                    $filePath = storage_path('app/template/migrate_branches.xlsx');
                    $fileName = 'migrate_branches.xlsx';
                    break;
                case 'migrate_offices':
                    $filePath = storage_path('app/template/migrate_offices.xlsx');
                    $fileName = 'migrate_offices.xlsx';
                    break;
                case 'migrate_divisions':
                    $filePath = storage_path('app/template/migrate_divisions.xlsx');
                    $fileName = 'migrate_divisions.xlsx';
                    break;
                case 'migrate_sections':
                    $filePath = storage_path('app/template/migrate_sections.xlsx');
                    $fileName = 'migrate_sections.xlsx';
                    break;
                case 'migrate_employment_types':
                    $filePath = storage_path('app/template/migrate_employment_types.xlsx');
                    $fileName = 'migrate_employment_types.xlsx';
                    break;
            }

            if (!file_exists($filePath)) {
                return $this->notFoundResponse('Template file not found.');
            }

            $fileContent = file_get_contents($filePath);
            $base64Content = base64_encode($fileContent);

            return $this->successResponse([
                'file_content' => $base64Content,
                'filename' => $fileName,
                'content_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'file_size' => strlen($fileContent),
                'template_type' => $template
            ], 'Migration template downloaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to download migration template: ' . $e->getMessage());
        }
    }
}
