<?php

namespace App\Http\Controllers;

use App\EmployeeDocument;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

class EmployeeDocumentController extends Controller
{
    use ApiResponse;

    protected $password = 'Z!pF!l3_P@$$w0rd';

    public function store(Request $request, $employee_document_id)
    {
        try {
            $validator = validator($request->all(), [
                'employee_no' => 'required|string',
                'employee_id' => 'required|integer|exists:employees,id',
                'description' => 'required|string|min:1',
                'document_type_id' => 'required|integer|exists:document_types,id',
                'attachments.*' => 'nullable|file|max:10240' // 10MB max
            ], [
                'employee_no.required' => 'Employee number is required.',
                'employee_id.required' => 'Employee ID is required.',
                'employee_id.exists' => 'Selected employee does not exist.',
                'description.required' => 'Document description is required.',
                'document_type_id.required' => 'Document type is required.',
                'document_type_id.exists' => 'Selected document type does not exist.',
                'attachments.*.file' => 'Each attachment must be a valid file.',
                'attachments.*.max' => 'Each attachment must not exceed 10MB.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $employee_no = $request->employee_no;
            $employee_id = $request->employee_id;
            $description = $request->description;
            $document_type_id = $request->document_type_id;
            $processed_files = [];
            $errors = [];

            // Allowed file extensions
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xlsx', 'xls'];
            $maxSize = 10 * 1024 * 1024; // 10MB

            if ($request->hasFile('attachments')) {
                $files = $request->file('attachments');
                
                // Validate maximum 5 documents can be uploaded at once
                $fileCount = count($files);
                if ($fileCount > 5) {
                    return $this->validationErrorResponse([
                        'attachments' => ['You can upload a maximum of 5 documents at once. Please select 5 or fewer documents.']
                    ]);
                }

                foreach ($files as $index => $file) {
                    try {
                        $file_name = $file->getClientOriginalName();
                        $extension = strtolower($file->getClientOriginalExtension());

                        // Validate file extension
                        if (!in_array($extension, $allowedExtensions)) {
                            $errors[] = "Invalid file type for: $file_name. Allowed types: " . implode(', ', $allowedExtensions);
                            continue;
                        }

                        // Validate file size
                        if ($file->getSize() > $maxSize) {
                            $errors[] = "File too large for: $file_name. Maximum size is 10MB.";
                            continue;
                        }

                        // Read file content and encode as base64
                        $fileContent = file_get_contents($file->getRealPath());
                        $encodedContent = base64_encode($fileContent);
                        $fileSize = $file->getSize();
                        $fileType = $file->getClientMimeType();

                        // Optional: Store file on disk as backup (same structure as before for compatibility)
                        $file_path = "\\employee_documents\\$employee_no\HRMS\\$document_type_id";
                        $storageFileName = 'DOCS' . $employee_id . '_' . $file_name;
                        $file->storeAs('employee_documents', $storageFileName);

                        // Prepare payload for attachments database
                        $attachmentsPayload = [
                            'employee_id' => $employee_id,
                            'name' => $description,
                            'document_type_id' => $document_type_id,
                            'description' => $description,
                            'attachment_name' => $file_name,
                            'extension' => $extension,
                            'file_content' => $encodedContent,
                            'file_size' => $fileSize,
                            'file_type' => $fileType,
                            'path' => storage_path('app/employee_documents/' . $storageFileName), // Optional: disk path for backup
                            'updated_at' => now(),
                        ];

                        if ($employee_document_id > 0 && $index === 0) {
                            // Update existing document (only for first file if employee_document_id is provided)
                            $existingDoc = DB::connection('attachments')
                                ->table('employee_documents')
                                ->where('employee_document_id', $employee_document_id)
                                ->where('employee_id', $employee_id)
                                ->first();

                            if (!$existingDoc) {
                                $errors[] = "Document with ID $employee_document_id not found for employee $employee_id";
                                continue;
                            }

                            DB::connection('attachments')
                                ->table('employee_documents')
                                ->where('employee_document_id', $employee_document_id)
                                ->where('employee_id', $employee_id)
                                ->update($attachmentsPayload);

                            $processed_files[] = [
                                'file_name' => $file_name,
                                'employee_document_id' => $employee_document_id,
                                'file_size' => $fileSize,
                                'extension' => $extension
                            ];
                        } else {
                            // Insert new document (ID is auto-generated by identity column)
                            $attachmentsPayload['created_at'] = now();
                            $newId = DB::connection('attachments')
                                ->table('employee_documents')
                                ->insertGetId($attachmentsPayload);

                            if ($newId) {
                                $processed_files[] = [
                                    'file_name' => $file_name,
                                    'employee_document_id' => $newId,
                                    'file_size' => $fileSize,
                                    'extension' => $extension
                                ];
                            } else {
                                $errors[] = "Failed to save document: $file_name";
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::error('EmployeeDocumentController - Store Error', [
                            'employee_id' => $employee_id,
                            'filename' => $file->getClientOriginalName(),
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        $errors[] = "Error processing file: " . $file->getClientOriginalName() . " - " . $e->getMessage();
                    }
                }
            } else {
                // Update metadata only (no new file uploaded)
                if ($employee_document_id > 0) {
                    $existingDoc = DB::connection('attachments')
                        ->table('employee_documents')
                        ->where('employee_document_id', $employee_document_id)
                        ->where('employee_id', $employee_id)
                        ->first();

                    if (!$existingDoc) {
                        return $this->notFoundResponse('Employee document not found');
                    }

                    $updateData = [
                        'name' => $description,
                        'description' => $description,
                        'document_type_id' => $document_type_id,
                        'updated_at' => now(),
                    ];

                    DB::connection('attachments')
                        ->table('employee_documents')
                        ->where('employee_document_id', $employee_document_id)
                        ->where('employee_id', $employee_id)
                        ->update($updateData);

                    $processed_files[] = [
                        'employee_document_id' => $employee_document_id,
                        'description' => $description,
                        'document_type_id' => $document_type_id
                    ];
                }
            }

            return $this->successResponse([
                'processed_files' => $processed_files,
                'total_processed' => count($processed_files),
                'errors' => $errors,
                'has_errors' => !empty($errors)
            ], 'Employee documents processed successfully');
        } catch (\Exception $e) {
            \Log::error('EmployeeDocumentController - Store Exception', [
                'employee_id' => $request->employee_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->serverErrorResponse('Failed to process employee documents: ' . $e->getMessage());
        }
    }

    public function getAttachments($employee_document_id)
    {
        try {
            $employee_document = DB::table('employee_documents')
                ->where('employee_document_id', $employee_document_id)
                ->get();

            if ($employee_document->isEmpty()) {
                return $this->notFoundResponse('Employee document not found');
            }

            return $this->successResponse($employee_document, 'Employee document attachments retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve employee document attachments: ' . $e->getMessage());
        }
    }

    public function delete($employee_document_id)
    {
        try {
            $employee_document = DB::table('employee_documents')->where('employee_document_id', $employee_document_id)->get();

            if ($employee_document->isEmpty()) {
                return $this->notFoundResponse('Employee document not found');
            }

            $file = $employee_document[0]->path . '\V_1.zip';

            if (Storage::exists($file)) {
                Storage::deleteDirectory($employee_document[0]->path);
            }

            DB::table('employee_documents')->where('employee_document_id', $employee_document_id)->delete();

            return $this->successResponse([
                'deleted_id' => $employee_document_id,
                'deleted_name' => $employee_document[0]->attachment_name ?? 'Unknown',
                'deleted_path' => $employee_document[0]->path
            ], 'Employee document deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete employee document: ' . $e->getMessage());
        }
    }

    public function loadDocuments($employee_id)
    {
        try {
            // Load from attachments database (source of truth for documents)
            $attachmentsDocuments = DB::connection('attachments')
                ->table('employee_documents')
                ->where('employee_id', $employee_id)
                ->orderByDesc('created_at')
                ->get();

            // Get document type names from main DB
            $typeIds = $attachmentsDocuments->pluck('document_type_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $documentTypes = [];
            if (!empty($typeIds)) {
                $documentTypes = DB::table('document_types')
                    ->whereIn('id', $typeIds)
                    ->pluck('name', 'id')
                    ->toArray();
            }

            // Map document type names into documents collection
            $mapped = $attachmentsDocuments->map(function ($doc) use ($documentTypes) {
                $doc->document_type = $documentTypes[$doc->document_type_id] ?? null;
                return $doc;
            });

            return $this->successResponse($mapped, 'Employee documents retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load employee documents: ' . $e->getMessage());
        }
    }

    public function loadDocumentsRange($employee_id, $date_from, $date_to)
    {
        try {
            $validator = validator([
                'date_from' => $date_from,
                'date_to' => $date_to
            ], [
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from'
            ], [
                'date_from.required' => 'Date from is required.',
                'date_from.date' => 'Date from must be a valid date.',
                'date_to.required' => 'Date to is required.',
                'date_to.date' => 'Date to must be a valid date.',
                'date_to.after_or_equal' => 'Date to must be greater than or equal to date from.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Load from attachments database (source of truth for documents)
            $attachmentsDocuments = DB::connection('attachments')
                ->table('employee_documents')
                ->where('employee_id', $employee_id)
                ->whereDate('created_at', '>=', $date_from)
                ->whereDate('created_at', '<=', $date_to)
                ->orderByDesc('created_at')
                ->get();

            // Get document type names from main DB
            $typeIds = $attachmentsDocuments->pluck('document_type_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $documentTypes = [];
            if (!empty($typeIds)) {
                $documentTypes = DB::table('document_types')
                    ->whereIn('id', $typeIds)
                    ->pluck('name', 'id')
                    ->toArray();
            }

            // Map document type names into documents collection
            $mapped = $attachmentsDocuments->map(function ($doc) use ($documentTypes) {
                $doc->document_type = $documentTypes[$doc->document_type_id] ?? null;
                return $doc;
            });

            return $this->successResponse([
                'documents' => $mapped,
                'date_range' => [
                    'from' => $date_from,
                    'to' => $date_to
                ],
                'total_documents' => $mapped->count()
            ], 'Employee documents retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load employee documents: ' . $e->getMessage());
        }
    }

    public function download($employee_document_id)
    {
        try {
            $employee_document = DB::table('employee_documents')->where('employee_document_id', $employee_document_id)->get();

            if ($employee_document->isEmpty()) {
                return $this->notFoundResponse('Employee document not found');
            }

            \Log::info('=== Download Document Debug ===');
            \Log::info('Document ID: ' . $employee_document_id);
            \Log::info('Path from DB: ' . $employee_document[0]->path);
            \Log::info('Employee ID: ' . $employee_document[0]->employee_id);
            \Log::info('Attachment name: ' . $employee_document[0]->attachment_name);

            $file = storage_path('app' . $employee_document[0]->path . '\V_1.zip');
            $path = storage_path('app' . $employee_document[0]->path);
            $file_extracted = $employee_document[0]->path . '\file';

            \Log::info('Looking for zip file at: ' . $file);
            \Log::info('File exists: ' . (File::exists($file) ? 'YES' : 'NO'));

            if (!File::exists($file)) {
                return $this->notFoundResponse('File does not exist in the server. Expected: ' . $file);
            }

            if ($this->unzip($file, $path, $this->password)) {
                $file_name = Storage::allFiles($file_extracted);
                $file_name_str = basename($file_name[0]);

                return $this->successResponse([
                    'file_path' => $path . '/file' . '/' . $file_name_str,
                    'file_name' => $file_name_str,
                    'employee_document_id' => $employee_document_id,
                    'download_url' => url('download/' . $employee_document_id)
                ], 'File ready for download');
            } else {
                return $this->errorResponse('Failed to unzip file');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to prepare file for download: ' . $e->getMessage());
        }
    }

    public function preview($employee_document_id)
    {
        try {
            $employee_document = DB::table('employee_documents')->where('employee_document_id', $employee_document_id)->get();

            if ($employee_document->isEmpty()) {
                return $this->notFoundResponse('Employee document not found');
            }

            \Log::info('=== Preview Document Debug ===');
            \Log::info('Document ID: ' . $employee_document_id);
            \Log::info('Path from DB: ' . $employee_document[0]->path);
            \Log::info('Employee ID: ' . $employee_document[0]->employee_id);
            \Log::info('Attachment name: ' . $employee_document[0]->attachment_name);

            // Normalize the path from database (ensure it starts with / and uses forward slashes)
            $doc_path = $employee_document[0]->path;
            $doc_path = str_replace('\\', '/', $doc_path);
            if (substr($doc_path, 0, 1) !== '/') {
                $doc_path = '/' . $doc_path;
            }

            $file = storage_path('app' . $doc_path . '/V_1.zip');
            $path = storage_path('app' . $doc_path);
            $file_name = $employee_document[0]->attachment_name;
            $file_extracted = $doc_path . '/file';

            \Log::info('Looking for zip file at: ' . $file);
            \Log::info('File exists: ' . (File::exists($file) ? 'YES' : 'NO'));

            if (!File::exists($file)) {
                return $this->notFoundResponse('File does not exist in the server. Expected: ' . $file);
            }

            $file_path1 = null;
            $file_path2 = null;
            $file_path = null;
            $extracted_file_name = null;

            if ($this->unzip($file, $path, $this->password)) {
                $file_name = Storage::allFiles($file_extracted);
                $extracted_file_name = basename($file_name[0]);
                // Use normalized path with forward slashes
                $file_path1 = $doc_path . '/file/' . $extracted_file_name;
                $file_path2 = 'public' . $doc_path . '/file/' . $extracted_file_name;
                $file_path = $doc_path . '/file/' . $extracted_file_name;
            } else {
                return $this->errorResponse('Failed to unzip file');
            }

            // Paths are already normalized with forward slashes
            $normalized_file_path1 = $file_path1;
            $normalized_file_path2 = $file_path2;

            // Source file path in app storage
            $source_file_path = storage_path('app' . $normalized_file_path1);

            // Destination file path in public storage
            $public_storage_path = public_path('storage' . $normalized_file_path1);

            // Check if file already exists in public storage
            if (!File::exists($public_storage_path)) {
                // File doesn't exist, copy it to public storage
                try {
                    // Ensure the source file exists
                    if (!File::exists($source_file_path)) {
                        \Log::error('Source file does not exist: ' . $source_file_path);
                        return $this->errorResponse('Source file not found after extraction');
                    }

                    // Ensure the target directory exists
                    $public_dir = dirname($public_storage_path);
                    if (!File::exists($public_dir)) {
                        File::makeDirectory($public_dir, 0755, true);
                    }

                    // Copy the file using File::copy() for cross-disk operations
                    File::copy($source_file_path, $public_storage_path);

                    \Log::info('File copied successfully from ' . $source_file_path . ' to ' . $public_storage_path);
                } catch (\Exception $e) {
                    // Log the error with full details
                    \Log::error('Error copying file to public storage: ' . $e->getMessage());
                    \Log::error('Source: ' . $source_file_path);
                    \Log::error('Destination: ' . $public_storage_path);
                    \Log::error('Stack trace: ' . $e->getTraceAsString());

                    // If copy fails, return error with details
                    if (!File::exists($public_storage_path)) {
                        return $this->errorResponse('Failed to copy file to public storage: ' . $e->getMessage());
                    }
                }
            }

            // Clean up extracted files from app storage (after successful copy)
            if ($source_file_path && File::exists($source_file_path)) {
                try {
                    // Only delete the file, not the entire directory (in case other files exist)
                    File::delete($source_file_path);
                } catch (\Exception $e) {
                    // Ignore cleanup errors
                    \Log::warning('Failed to cleanup source file: ' . $e->getMessage());
                }
            }

            // Clean up extracted directory if empty
            $normalized_extracted = str_replace('\\', '/', $file_extracted);
            if (File::exists(storage_path('app' . $normalized_extracted))) {
                try {
                    // Only delete if directory is empty or contains only the file we just deleted
                    $files = Storage::allFiles($normalized_extracted);
                    if (empty($files)) {
                        File::deleteDirectory(storage_path('app' . $normalized_extracted));
                    }
                } catch (\Exception $e) {
                    // Ignore cleanup errors
                    \Log::warning('Failed to cleanup extracted directory: ' . $e->getMessage());
                }
            }

            // Verify the file exists in public storage before returning
            if (!File::exists($public_storage_path)) {
                \Log::error('Preview file verification failed. Expected path: ' . $public_storage_path);
                \Log::error('Source file path: ' . $source_file_path);
                \Log::error('Normalized file path 1: ' . $normalized_file_path1);
                return $this->errorResponse('Failed to prepare file for preview. File not found in public storage.');
            }

            return $this->successResponse([
                'file_path' => $file_path,
                'file' => $file,
                'employee_document_id' => $employee_document_id,
                'preview_url' => url('storage' . $file_path),
                'file_name' => $extracted_file_name,
                'file_size' => File::size($public_storage_path)
            ], 'Employee document preview data retrieved successfully');
        } catch (\Exception $e) {
            \Log::error('Preview exception: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return $this->serverErrorResponse('Failed to prepare document preview: ' . $e->getMessage());
        }
    }

    public function deletePreview($document_id)
    {
        try {
            $employee_document = DB::table('employee_documents')->where('employee_document_id', $document_id)->get();

            if ($employee_document->isEmpty()) {
                return $this->notFoundResponse('Employee document not found');
            }

            $path = public_path('storage' . $employee_document[0]->path);

            if (File::exists($path)) {
                File::deleteDirectory($path);
                return $this->successResponse([
                    'deleted_id' => $document_id,
                    'deleted_path' => $employee_document[0]->path
                ], 'Preview file deleted successfully');
            } else {
                return $this->notFoundResponse('Preview file does not exist');
            }
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete preview file: ' . $e->getMessage());
        }
    }

    private function unzip(string $file, string $path, string $password)
    {
        $zip = new ZipArchive();

        if ($zip->open($file) === true) {
            $zip->setPassword($password);
            $zip->extractTo($path);
            $zip->close();
        } else {
            return false;
        }

        return true;
    }

    private function zip(string $file, string $path, string $password)
    {
        set_time_limit(16000);

        $zip = new ZipArchive;

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $zipFileName = $path . '\V_1.zip';

        try {
            if ($zip->open($zipFileName, ZipArchive::CREATE) === TRUE) {

                $baseName = 'file\\' . basename($file);
                $zip->addFile($file, $baseName);

                // set password
                if (!$zip->setPassword($password)) {
                    throw new RuntimeException('Set password failed');
                }

                //encrypt the file with AES-256
                if (!$zip->setEncryptionName($baseName, ZipArchive::EM_AES_256)) {
                    throw new RuntimeException(sprintf('Set encryption failed: %s', $baseName));
                }

                $zip->close();
            } else {
                return false;
            }
        } catch (\Throwable $th) {
            throw $th;
        }

        return true;
    }
}
