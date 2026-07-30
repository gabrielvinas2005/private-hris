<?php

namespace App\Http\Controllers;

use App\EmployeeDocument;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

            if ($request->hasFile('attachments')) {
                $files = $request->file('attachments');
                $ctr = 0;

                foreach ($files as $file) {
                    try {
                        if ($ctr > 0) {
                            $employee_document_id = 0;
                        }

                        $file_name = $file->getClientOriginalName();
                        $extension = $file->getClientOriginalExtension();

                        if ($employee_document_id == 0) {
                            $employee_document_id = DB::table('employee_documents')->max('employee_document_id') + 1;
                        } else {
                            $employee_document_id = $employee_document_id;
                        }

                        $file_path = "\\employee_documents\\$employee_no\HRMS\\$document_type_id\\$employee_document_id";
                        $base_path = storage_path('app' . $file_path);
                        $zip_file = $base_path . '\\' . $file_name;

                        $request->attachments[$ctr]->storeAs($file_path, $file_name);

                        if ($this->zip($zip_file, $base_path, $this->password)) {
                            $employee_document_data = [
                                'employee_id' => $employee_id,
                                'name' => $description,
                                'description' => $description,
                                'attachment_name' => $file_name,
                                'path' => $file_path,
                                'extension' => $extension,
                                'document_type_id' => $document_type_id,
                            ];

                            EmployeeDocument::updateOrCreate(['employee_document_id' => $employee_document_id], $employee_document_data);
                            
                            $processed_files[] = [
                                'file_name' => $file_name,
                                'employee_document_id' => $employee_document_id,
                                'file_size' => $file->getSize(),
                                'extension' => $extension
                            ];
                        } else {
                            $errors[] = "Failed to process file: $file_name";
                        }

                        Storage::disk('local')->delete($file_path . '\\' . $file_name);
                    } catch (\Exception $e) {
                        $errors[] = "Error processing file: " . $file->getClientOriginalName() . " - " . $e->getMessage();
                    }

                    $ctr++;
                }
            } else {
                if ($employee_document_id > 0) {
                    $employee_document_old = DB::table('employee_documents')->where('employee_document_id', $employee_document_id)->get();

                    if ($employee_document_old->isEmpty()) {
                        return $this->notFoundResponse('Employee document not found');
                    }

                    $old_path = $employee_document_old[0]->path;
                    $new_path = "\\employee_documents\\$employee_no\HRMS\\$document_type_id\\$employee_document_id";

                    if ($old_path != $new_path) {
                        Storage::move($old_path, $new_path);

                        $employee_document_data = [
                            'name' => $description,
                            'description' => $description,
                            'document_type_id' => $document_type_id,
                            'path' => $new_path,
                        ];
                    } else {
                        $employee_document_data = [
                            'name' => $description,
                            'description' => $description,
                            'document_type_id' => $document_type_id,
                        ];
                    }

                    EmployeeDocument::where('employee_document_id', $employee_document_id)->update($employee_document_data);
                    
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
            $employee_documents = DB::table('employee_documents as a')
                ->leftJoin('document_types as b', 'a.document_type_id', '=', 'b.id')
                ->select(
                    'a.employee_document_id',
                    'a.description',
                    'a.attachment_name',
                    'a.created_at',
                    'a.document_type_id',
                    'b.name as document_type'
                )
                ->where('a.employee_id', $employee_id)
                ->get();

            return $this->successResponse($employee_documents, 'Employee documents retrieved successfully');
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

            $employee_documents = DB::table('employee_documents as a')
                ->leftJoin('document_types as b', 'a.document_type_id', '=', 'b.id')
                ->select(
                    'a.employee_document_id',
                    'a.description',
                    'a.attachment_name',
                    'a.created_at',
                    'a.document_type_id',
                    'b.name as document_type'
                )
                ->where('a.employee_id', $employee_id)
                ->whereDate('a.created_at', '>=', $date_from)
                ->whereDate('a.created_at', '<=', $date_to)
                ->get();

            return $this->successResponse([
                'documents' => $employee_documents,
                'date_range' => [
                    'from' => $date_from,
                    'to' => $date_to
                ],
                'total_documents' => $employee_documents->count()
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

            $file = storage_path('app' . $employee_document[0]->path . '\V_1.zip');
            $path = storage_path('app' . $employee_document[0]->path);
            $file_extracted = $employee_document[0]->path . '\file';

            if (!File::exists($file)) {
                return $this->notFoundResponse('File does not exist in the server');
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

            $file = storage_path('app' . $employee_document[0]->path . '\V_1.zip');
            $path = storage_path('app' . $employee_document[0]->path);
            $file_name = $employee_document[0]->attachment_name;
            $file_extracted = $employee_document[0]->path . '\file';

            if (!File::exists($file)) {
                return $this->notFoundResponse('File does not exist in the server');
            }

            if ($this->unzip($file, $path, $this->password)) {
                $file_name = Storage::allFiles($file_extracted);
                $file_path1 =  $employee_document[0]->path . '\\file' . '\\' . basename($file_name[0]);
                $file_path2 = 'public' . $employee_document[0]->path . '\\file' . '\\' . basename($file_name[0]);
                $file_path = str_replace('\\', '/', $employee_document[0]->path) . '/file' . '/' . basename($file_name[0]);
            } else {
                return $this->errorResponse('Failed to unzip file');
            }

            $preview_file_path = public_path('storage' . $employee_document[0]->path);

            if (!File::exists(public_path('storage' . $file_path1))) {
                Storage::copy($file_path1, $file_path2);
                File::deleteDirectory(storage_path('app' . $file_path1));
            } else {
                File::deleteDirectory(storage_path('app' . $file_path1));
            }

            if (File::exists(storage_path('app' . $file_extracted))) {
                File::deleteDirectory(storage_path('app' . $file_extracted));
            }

            return $this->successResponse([
                'file_path' => $file_path,
                'file' => $file,
                'employee_document_id' => $employee_document_id,
                'preview_url' => url('storage' . $file_path),
                'file_name' => basename($file_name[0]),
                'file_size' => File::size(public_path('storage' . $file_path))
            ], 'Employee document preview data retrieved successfully');
        } catch (\Exception $e) {
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
