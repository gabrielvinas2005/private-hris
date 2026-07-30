<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

trait GeneratesPdf
{
    /**
     * Generate PDF using the centralized PDF controller
     */
    protected function generatePdfFromHtml(string $html, array $options = [])
    {
        $defaultOptions = [
            'paper_size' => 'A4',
            'orientation' => 'portrait',
            'filename' => 'report_' . date('Ymd_His')
        ];

        $options = array_merge($defaultOptions, $options);

        try {
            $response = Http::post(url('/api/reports/pdf'), [
                'html' => $html,
                'paper_size' => $options['paper_size'],
                'orientation' => $options['orientation'],
                'filename' => $options['filename']
            ]);

            if ($response->successful()) {
                return $response->body();
            }

            throw new \Exception('PDF generation failed: ' . $response->body());
        } catch (\Exception $e) {
            \Log::error('PDF generation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate PDF from template and data using centralized controller
     */
    protected function generatePdfFromTemplate(string $template, array $data, array $options = [])
    {
        $defaultOptions = [
            'paper_size' => 'A4',
            'orientation' => 'portrait',
            'filename' => 'report_' . date('Ymd_His')
        ];

        $options = array_merge($defaultOptions, $options);

        try {
            $response = Http::post(url('/api/reports/pdf/template'), [
                'template' => $template,
                'data' => $data,
                'paper_size' => $options['paper_size'],
                'orientation' => $options['orientation'],
                'filename' => $options['filename']
            ]);

            if ($response->successful()) {
                return $response->body();
            }

            throw new \Exception('PDF generation failed: ' . $response->body());
        } catch (\Exception $e) {
            \Log::error('PDF generation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate PDF with custom styles
     */
    protected function generatePdfWithStyles(string $html, string $styles = '', array $options = [])
    {
        $defaultOptions = [
            'paper_size' => 'A4',
            'orientation' => 'portrait',
            'filename' => 'report_' . date('Ymd_His')
        ];

        $options = array_merge($defaultOptions, $options);

        try {
            $response = Http::post(url('/api/reports/pdf/styles'), [
                'html' => $html,
                'styles' => $styles,
                'paper_size' => $options['paper_size'],
                'orientation' => $options['orientation'],
                'filename' => $options['filename']
            ]);

            if ($response->successful()) {
                return $response->body();
            }

            throw new \Exception('PDF generation failed: ' . $response->body());
        } catch (\Exception $e) {
            \Log::error('PDF generation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send PDF response with proper headers
     */
    protected function sendPdfResponse(string $pdfContent, string $filename)
    {
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Length', strlen($pdfContent));
    }

    /**
     * Generate PDF for preview (returns PDF content for iframe display)
     */
    protected function generatePdfPreview(string $html, array $options = [])
    {
        $defaultOptions = [
            'paper_size' => 'A4',
            'orientation' => 'portrait',
            'filename' => 'report_' . date('Ymd_His')
        ];

        $options = array_merge($defaultOptions, $options);

        try {
            $response = Http::post(url('/api/reports/pdf/preview'), [
                'html' => $html,
                'paper_size' => $options['paper_size'],
                'orientation' => $options['orientation'],
                'filename' => $options['filename']
            ]);

            if ($response->successful()) {
                return $response->body();
            }

            throw new \Exception('PDF preview generation failed: ' . $response->body());
        } catch (\Exception $e) {
            \Log::error('PDF preview generation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate PDF preview from template and data
     */
    protected function generatePdfPreviewFromTemplate(string $template, array $data, array $options = [])
    {
        $defaultOptions = [
            'paper_size' => 'A4',
            'orientation' => 'portrait',
            'filename' => 'report_' . date('Ymd_His')
        ];

        $options = array_merge($defaultOptions, $options);

        try {
            $response = Http::post(url('/api/reports/pdf/preview/template'), [
                'template' => $template,
                'data' => $data,
                'paper_size' => $options['paper_size'],
                'orientation' => $options['orientation'],
                'filename' => $options['filename']
            ]);

            if ($response->successful()) {
                return $response->body();
            }

            throw new \Exception('PDF preview generation failed: ' . $response->body());
        } catch (\Exception $e) {
            \Log::error('PDF preview generation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Convert legacy PDF generation to new centralized approach
     */
    protected function convertLegacyPdfGeneration(string $template, array $data, array $options = [])
    {
        // Generate HTML from template
        $html = view($template, $data)->render();
        
        // Use centralized PDF generation
        return $this->generatePdfFromHtml($html, $options);
    }
}
