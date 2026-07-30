<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class PdfFieldMappingController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get PDF field mappings for a specific form type
     */
    public function getMappings($formType)
    {
        try {
            $mappingPath = $this->getMappingFilePath($formType);
            
            if (File::exists($mappingPath)) {
                $content = File::get($mappingPath);
                $mappings = json_decode($content, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $this->successResponse([
                        'mappings' => $mappings,
                    ], 'Mappings retrieved successfully');
                }
            }

            return $this->successResponse([
                'mappings' => [],
            ], 'No mappings found');
        } catch (\Exception $e) {
            Log::error('PDF Field Mapping Get Error: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve mappings: ' . $e->getMessage());
        }
    }

    /**
     * Save PDF field mappings for a specific form type
     */
    public function saveMappings(Request $request, $formType)
    {
        try {
            $request->validate([
                'mappings' => 'required|array',
            ]);

            $mappings = $request->input('mappings');
            $mappingPath = $this->getMappingFilePath($formType);
            $mappingDir = dirname($mappingPath);

            // Create directory if it doesn't exist
            if (!File::exists($mappingDir)) {
                File::makeDirectory($mappingDir, 0755, true);
            }

            // Write mappings to JSON file with pretty formatting
            $jsonContent = json_encode($mappings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            // Log the path for debugging
            Log::info('Saving PDF mappings', [
                'form_type' => $formType,
                'file_path' => $mappingPath,
                'mappings_count' => count($mappings),
                'directory_exists' => File::exists($mappingDir),
            ]);
            
            File::put($mappingPath, $jsonContent);
            
            // Verify the file was written
            if (!File::exists($mappingPath)) {
                throw new \Exception("File was not created at path: {$mappingPath}");
            }

            return $this->successResponse([
                'form_type' => $formType,
                'mappings_count' => count($mappings),
                'file_path' => $mappingPath,
            ], 'Mappings saved successfully');
        } catch (\Exception $e) {
            Log::error('PDF Field Mapping Save Error: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to save mappings: ' . $e->getMessage());
        }
    }

    /**
     * Get the file path for a form type's mapping file
     */
    private function getMappingFilePath($formType)
    {
        // Map form types to their storage keys
        $formTypeMap = [
            'pagibig_mdf' => 'pagibig_mdf',
            'philhealth_pmrf' => 'philhealth_pmrf',
            'bir_form_2305' => 'bir_form_2305',
        ];

        $storageKey = $formTypeMap[$formType] ?? $formType;
        
        // Store in backend storage directory (accessible in Docker)
        // storage_path() returns pr-backend/storage, so app/pdfMappings = storage/app/pdfMappings
        $basePath = storage_path('app/pdfMappings');
        
        return $basePath . '/' . $storageKey . '.json';
    }
}
