<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function serveImage(Request $request, $path)
    {
        // Construct the full path to the image
        $fullPath = public_path('dist/img/' . $path);
        
        // Check if file exists
        if (!file_exists($fullPath)) {
            return response()->json(['error' => 'Image not found'], 404);
        }
        
        // Get file content
        $fileContent = file_get_contents($fullPath);
        
        // Get MIME type
        $mimeType = mime_content_type($fullPath);
        
        // Return image with proper CORS headers
        return response($fileContent)
            ->header('Content-Type', $mimeType)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->header('Cache-Control', 'public, max-age=31536000');
    }
}
