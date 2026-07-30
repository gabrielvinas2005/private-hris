<?php

namespace App\Http\Controllers;

use App\DownloadableForm;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadableFormController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        // Require auth for management actions, but allow unauthenticated preview
        $this->middleware('auth', ['except' => ['preview']]);
    }

    public function index()
    {
        try {
            $forms = DownloadableForm::orderBy('FormName', 'asc')->get();
            return $this->successResponse($forms, 'Downloadable forms retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve downloadable forms: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'FormName' => 'required|string|max:255',
                'file' => 'nullable|file|max:10240',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $encoded = null;
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $contents = file_get_contents($file->getRealPath());
                $encoded = json_encode([
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'data' => base64_encode($contents),
                ]);
            } else {
                $encoded = $request->FormFile;
            }

            $form = DownloadableForm::create([
                'FormName' => $request->FormName,
                'FormFile' => $encoded,
            ]);

            return $this->successResponse($form, 'Downloadable form created successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to create downloadable form: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $form = DownloadableForm::find($id);

            if (!$form) {
                return $this->notFoundResponse('Downloadable form not found');
            }

            $validator = validator($request->all(), [
                'FormName' => 'required|string|max:255',
                'file' => 'nullable|file|max:10240',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $form->FormName = $request->FormName;

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $contents = file_get_contents($file->getRealPath());
                $form->FormFile = json_encode([
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'data' => base64_encode($contents),
                ]);
            } elseif ($request->has('FormFile')) {
                $form->FormFile = $request->FormFile;
            }
            $form->save();

            return $this->successResponse($form, 'Downloadable form updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update downloadable form: ' . $e->getMessage());
        }
    }

    public function download($id)
    {
        try {
            $form = DownloadableForm::find($id);

            if (!$form || !$form->FormFile) {
                return $this->notFoundResponse('Downloadable form not found');
            }

            $meta = json_decode($form->FormFile, true);
            if (!is_array($meta) || empty($meta['data'])) {
                return $this->serverErrorResponse('Stored file data is invalid');
            }

            $binary = base64_decode($meta['data']);
            $mime = $meta['mime'] ?? 'application/octet-stream';
            $name = $meta['name'] ?? ($form->FormName . '.dat');

            return response($binary, 200)
                ->header('Content-Type', $mime)
                ->header('Content-Disposition', 'attachment; filename="' . $name . '"');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to download downloadable form: ' . $e->getMessage());
        }
    }

    public function preview($id)
    {
        try {
            $form = DownloadableForm::find($id);

            if (!$form || !$form->FormFile) {
                return $this->notFoundResponse('Downloadable form not found');
            }

            $meta = json_decode($form->FormFile, true);
            if (!is_array($meta) || empty($meta['data'])) {
                return $this->serverErrorResponse('Stored file data is invalid');
            }

            $binary = base64_decode($meta['data']);
            $mime = $meta['mime'] ?? 'application/octet-stream';
            $name = $meta['name'] ?? ($form->FormName . '.dat');

            return response($binary, 200)
                ->header('Content-Type', $mime)
                ->header('Content-Disposition', 'inline; filename="' . $name . '"');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to preview downloadable form: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $form = DownloadableForm::find($id);

            if (!$form) {
                return $this->notFoundResponse('Downloadable form not found');
            }

            $form->delete();

            return $this->successResponse(['deleted_id' => $id], 'Downloadable form deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete downloadable form: ' . $e->getMessage());
        }
    }
}


