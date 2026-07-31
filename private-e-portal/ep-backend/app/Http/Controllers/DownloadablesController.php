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
            $downloadables = DB::table('downloadables')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($row) {
                    return [
                        'id' => $row->id,
                        'title' => $row->title,
                        'description' => $row->description,
                        'category' => $row->category,
                        'file_name' => $row->file_name,
                        'created_at' => $row->created_at,
                    ];
                });

            return $this->successResponse(['downloadables' => $downloadables]);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load downloadables: ' . $e->getMessage());
        }
    }

    public function download($id)
    {
        try {
            $file = DB::table('downloadables')->where('id', $id)->first();
            
            if (!$file || !Storage::exists($file->file_path)) {
                return $this->errorResponse('File not found', 404);
            }

            return Storage::download($file->file_path, $file->file_name);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to download file: ' . $e->getMessage());
        }
    }
}