<?php

namespace App\Http\Controllers;

use Auth;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DownloadablesController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $downloadables = [];

            // 1. Fetch from downloadable_forms (HR Setup / CP)
            if (DB::getSchemaBuilder()->hasTable('downloadable_forms')) {
                $forms = DB::table('downloadable_forms')
                    ->orderBy('FormName', 'asc')
                    ->get();

                foreach ($forms as $form) {
                    $fileName = $form->FormName;
                    $mime = null;
                    if (!empty($form->FormFile)) {
                        $meta = json_decode($form->FormFile, true);
                        if (is_array($meta) && !empty($meta['name'])) {
                            $fileName = $meta['name'];
                            $mime = $meta['mime'] ?? null;
                        }
                    }

                    $downloadables[] = [
                        'id' => 'form_' . $form->FormId,
                        'raw_id' => $form->FormId,
                        'source' => 'downloadable_forms',
                        'title' => $form->FormName,
                        'description' => 'HR Setup Downloadable Document',
                        'category' => 'HR Form',
                        'file_name' => $fileName,
                        'mime_type' => $mime,
                        'created_at' => $form->created_at ?? $form->updated_at ?? null,
                    ];
                }
            }

            // 2. Fetch from downloadables table if available
            if (DB::getSchemaBuilder()->hasTable('downloadables')) {
                $docs = DB::table('downloadables')
                    ->orderBy('created_at', 'desc')
                    ->get();

                foreach ($docs as $doc) {
                    $downloadables[] = [
                        'id' => 'doc_' . $doc->id,
                        'raw_id' => $doc->id,
                        'source' => 'downloadables',
                        'title' => $doc->title,
                        'description' => $doc->description ?? '',
                        'category' => $doc->category ?? 'General',
                        'file_name' => $doc->file_name,
                        'mime_type' => null,
                        'created_at' => $doc->created_at,
                    ];
                }
            }

            return $this->successResponse(['downloadables' => $downloadables]);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load downloadables: ' . $e->getMessage());
        }
    }

    public function download($id)
    {
        try {
            // Check if ID is prefixed or numeric
            if (strpos($id, 'doc_') === 0) {
                $rawId = substr($id, 4);
                return $this->downloadFromDocsTable($rawId);
            }

            $rawId = str_replace('form_', '', $id);

            // First try downloadable_forms
            if (DB::getSchemaBuilder()->hasTable('downloadable_forms')) {
                $form = DB::table('downloadable_forms')->where('FormId', $rawId)->first();
                if ($form && !empty($form->FormFile)) {
                    $meta = json_decode($form->FormFile, true);
                    if (is_array($meta) && !empty($meta['data'])) {
                        $binary = base64_decode($meta['data']);
                        $mime = $meta['mime'] ?? 'application/octet-stream';
                        $name = $meta['name'] ?? ($form->FormName . '.dat');

                        return response($binary, 200)
                            ->header('Content-Type', $mime)
                            ->header('Content-Disposition', 'attachment; filename="' . $name . '"');
                    }
                }
            }

            // Fallback to downloadables table
            return $this->downloadFromDocsTable($rawId);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to download file: ' . $e->getMessage());
        }
    }

    public function preview($id)
    {
        try {
            if (strpos($id, 'doc_') === 0) {
                $rawId = substr($id, 4);
                return $this->previewFromDocsTable($rawId);
            }

            $rawId = str_replace('form_', '', $id);

            // First try downloadable_forms
            if (DB::getSchemaBuilder()->hasTable('downloadable_forms')) {
                $form = DB::table('downloadable_forms')->where('FormId', $rawId)->first();
                if ($form && !empty($form->FormFile)) {
                    $meta = json_decode($form->FormFile, true);
                    if (is_array($meta) && !empty($meta['data'])) {
                        $binary = base64_decode($meta['data']);
                        $mime = $meta['mime'] ?? 'application/octet-stream';
                        $name = $meta['name'] ?? ($form->FormName . '.dat');

                        return response($binary, 200)
                            ->header('Content-Type', $mime)
                            ->header('Content-Disposition', 'inline; filename="' . $name . '"');
                    }
                }
            }

            // Fallback to downloadables table
            return $this->previewFromDocsTable($rawId);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to preview file: ' . $e->getMessage());
        }
    }

    private function downloadFromDocsTable($id)
    {
        if (DB::getSchemaBuilder()->hasTable('downloadables')) {
            $file = DB::table('downloadables')->where('id', $id)->first();
            if ($file && Storage::exists($file->file_path)) {
                return Storage::download($file->file_path, $file->file_name);
            }
        }
        return $this->errorResponse('File not found', 404);
    }

    private function previewFromDocsTable($id)
    {
        if (DB::getSchemaBuilder()->hasTable('downloadables')) {
            $file = DB::table('downloadables')->where('id', $id)->first();
            if ($file && Storage::exists($file->file_path)) {
                $mime = Storage::mimeType($file->file_path) ?? 'application/octet-stream';
                return response(Storage::get($file->file_path), 200)
                    ->header('Content-Type', $mime)
                    ->header('Content-Disposition', 'inline; filename="' . $file->file_name . '"');
            }
        }
        return $this->errorResponse('File not found', 404);
    }
}