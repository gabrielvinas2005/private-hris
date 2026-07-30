<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CosPlaceholderAttachment extends Command
{
    protected $signature = 'cos:placeholder-attachment {attachment_id : The non_dtr_attachments id}';

    protected $description = 'Create a minimal placeholder PDF for a COS attachment so preview works until the real file is copied in.';

    public function handle(): int
    {
        $attachmentId = (int) $this->argument('attachment_id');
        $attachment = DB::table('non_dtr_attachments')->where('id', $attachmentId)->first();

        if (!$attachment) {
            $this->error("Attachment with id {$attachmentId} not found in non_dtr_attachments.");
            return self::FAILURE;
        }

        $filePath = trim(str_replace('\\', '/', $attachment->file_path), '/');
        $fullPath = storage_path('app/' . $filePath);
        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                $this->error("Could not create directory: {$dir}");
                return self::FAILURE;
            }
        }

        if (file_exists($fullPath)) {
            $this->warn("File already exists: {$fullPath}");
            $this->info('Preview should work. Replace with the real PDF if needed.');
            return self::SUCCESS;
        }

        $minimalPdf = $this->minimalPdfContent();
        if (file_put_contents($fullPath, $minimalPdf) === false) {
            $this->error("Could not write file: {$fullPath}");
            return self::FAILURE;
        }

        $this->info("Placeholder PDF created: {$fullPath}");
        $this->info('Preview should now work. Replace with the real file when you have it.');
        return self::SUCCESS;
    }

    /**
     * Minimal valid PDF (single blank page) so the file can be opened and preview works.
     */
    private function minimalPdfContent(): string
    {
        return "%PDF-1.4\n"
            . "1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            . "2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            . "3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R>>endobj\n"
            . "xref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000052 00000 n \n0000000101 00000 n \n"
            . "trailer<</Size 4/Root 1 0 R>>\nstartxref\n178\n%%EOF\n";
    }
}
