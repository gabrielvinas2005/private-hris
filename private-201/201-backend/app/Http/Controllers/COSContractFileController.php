<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;

class COSContractFileController extends Controller
{
    use ApiResponse;

    public function download($id)
    {
        try {
            $attachment = DB::connection('attachments')
                ->table('cos_contractfile')
                ->select('id', 'cos_contract_id', 'file_name', 'file_content', 'file_type', 'file_size')
                ->where('id', (int) $id)
                ->first();

            if (!$attachment || empty($attachment->file_content)) {
                return $this->notFoundResponse('Contract attachment not found.');
            }

            $binary = base64_decode($attachment->file_content, true);
            if ($binary === false) {
                $binary = $attachment->file_content;
            }

            $mimeType = $attachment->file_type;
            if (!$mimeType) {
                $extension = strtolower(pathinfo($attachment->file_name ?? '', PATHINFO_EXTENSION));
                $mimeTypes = [
                    'pdf' => 'application/pdf',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'doc' => 'application/msword',
                    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ];
                $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
            }

            $safeFilename = preg_replace('/[^\w\s\-\.]/', '_', $attachment->file_name ?? 'contract-file');
            $disposition = 'attachment; filename="' . addslashes($safeFilename) . '"';

            return response($binary, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', $disposition)
                ->header('Cache-Control', 'public, max-age=3600');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to download contract attachment: ' . $e->getMessage());
        }
    }
}
